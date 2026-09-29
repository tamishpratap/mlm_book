<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateMemberRequest;
use App\Models\Member;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MemberManagementController extends Controller
{
    /**
     * Display a listing of ALL platform members regardless of verification or account status.
     */
    public function all(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to'   => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
        ]);

        $search = trim((string) $request->input('q'));
        $verificationStatus = $request->input('verification_status') ?: $request->input('status');
        $accountStatus = $request->input('account_status');
        $country = $request->input('country');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = Member::query();

        // 1. Search Filter (User ID, Name, Email, Phone, City, Country)
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('user_id', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('country', 'like', "%{$search}%");
            });
        }

        // 2. Verification Status Filter
        if ($verificationStatus === 'verified') {
            $query->whereNotNull('mobile_verified_at');
        } elseif ($verificationStatus === 'unverified') {
            $query->whereNull('mobile_verified_at');
        } elseif ($verificationStatus === 'pending') {
            $query->whereNull('mobile_verified_at')->whereNotNull('mobile_verification_requested_at');
        }

        // 3. Account Status Filter (active / blocked)
        if ($accountStatus === 'blocked') {
            $query->whereNotNull('blocked_at');
        } elseif ($accountStatus === 'active' || $accountStatus === 'unblocked') {
            $query->whereNull('blocked_at');
        }

        // 4. Country Filter
        if (!empty($country)) {
            $query->where('country', $country);
        }

        // 5. Date Range Filter
        if (!empty($dateFrom)) {
            $startDate = Carbon::parse($dateFrom)->startOfDay();
            $endDate = !empty($dateTo) ? Carbon::parse($dateTo)->endOfDay() : Carbon::parse($dateFrom)->endOfDay();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } elseif (!empty($dateTo)) {
            $query->where('created_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        $perPage = $request->integer('per_page', 15);
        $members = $query->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();

        // Metrics Summary across the entire platform
        $totalCount = Member::count();
        $verifiedCount = Member::whereNotNull('mobile_verified_at')->count();
        $unverifiedCount = Member::whereNull('mobile_verified_at')->count();
        $activeCount = Member::whereNull('blocked_at')->whereNotNull('mobile_verified_at')->count();
        $pendingCount = Member::whereNull('blocked_at')->whereNull('mobile_verified_at')->count();
        $blockedCount = Member::whereNotNull('blocked_at')->count();
        $activeTodayCount = Member::where('last_seen_at', '>=', now()->subHours(24))->count();

        // Distinct Countries for Filter Dropdown
        $countries = Member::whereNotNull('country')
            ->where('country', '!=', '')
            ->distinct()
            ->pluck('country');

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'members' => $members,
                'total' => $members->total(),
                'totalCount' => $totalCount,
                'verifiedCount' => $verifiedCount,
                'unverifiedCount' => $unverifiedCount,
                'activeCount' => $activeCount,
                'pendingCount' => $pendingCount,
                'blockedCount' => $blockedCount,
                'activeTodayCount' => $activeTodayCount,
                'countries' => $countries,
                'metrics' => [
                    'totalCount' => $totalCount,
                    'verifiedCount' => $verifiedCount,
                    'unverifiedCount' => $unverifiedCount,
                    'activeCount' => $activeCount,
                    'pendingCount' => $pendingCount,
                    'blockedCount' => $blockedCount,
                    'activeTodayCount' => $activeTodayCount,
                ],
            ]);
        }

        return view('admin.members.index', compact(
            'members',
            'search',
            'country',
            'dateFrom',
            'dateTo',
            'totalCount',
            'verifiedCount',
            'unverifiedCount',
            'activeCount',
            'pendingCount',
            'blockedCount',
            'activeTodayCount',
            'countries'
        ));
    }

    /**
     * Establish a secure member session for the authenticated admin to open the Member Panel.
     */
    public function openMemberPanel(Request $request, Member $member)
    {
        $admin = auth('admin')->user();
        if (!$admin) {
            return response()->json(['message' => 'Unauthorized administrator access.'], 403);
        }

        // Establish member session securely
        \Illuminate\Support\Facades\Auth::guard('member')->login($member);
        $request->session()->save();

        $frontendUrl = rtrim((string) (config('app.frontend_url') ?: config('app.url', 'https://mlmbookai.com')), '/');
        $targetUrl = $frontendUrl . '/member/dashboard';

        return response()->json([
            'success' => true,
            'message' => "Secure session established for member {$member->name} ({$member->user_id}).",
            'redirect_url' => $targetUrl,
            'member' => [
                'id' => $member->id,
                'name' => $member->name,
                'user_id' => $member->user_id,
                'email' => $member->email,
                'is_blocked' => $member->isBlocked(),
                'is_verified' => $member->isMobileVerified(),
            ],
        ]);
    }

    /**
     * Redirect authenticated admin directly to member panel dashboard.
     */
    public function redirectToMemberPanel(Request $request, Member $member)
    {
        $admin = auth('admin')->user();
        if (!$admin) {
            abort(403, 'Unauthorized administrator access.');
        }

        \Illuminate\Support\Facades\Auth::guard('member')->login($member);
        $request->session()->save();

        $frontendUrl = rtrim((string) (config('app.frontend_url') ?: config('app.url', 'https://mlmbookai.com')), '/');
        return redirect()->away($frontendUrl . '/member/dashboard');
    }

    /**
     * Display a listing of Active (verified) members.
     */
    public function active(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to'   => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
        ]);

        $search = trim((string) $request->input('q'));
        $status = $request->input('status');
        $country = $request->input('country');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = Member::query()->whereNull('blocked_at');

        // Search Filter (User ID, Name, Email, Phone, City, Country)
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('user_id', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('country', 'like', "%{$search}%");
            });
        }

        // Activity / Verification Status Filter
        if ($status === 'all') {
            // Show all unblocked members (both verified and unverified)
        } elseif ($status === 'unverified') {
            $query->whereNull('mobile_verified_at');
        } elseif ($status === 'active') {
            $query->whereNotNull('mobile_verified_at')->where('last_seen_at', '>=', now()->subDays(7));
        } elseif ($status === 'inactive') {
            $query->where(function ($q) {
                $q->whereNull('last_seen_at')
                  ->orWhere('last_seen_at', '<', now()->subDays(7));
            });
        } else {
            // Default on Active Directory (and 'verified'): only mobile-verified members
            $query->whereNotNull('mobile_verified_at');
        }

        // Country Filter
        if (!empty($country)) {
            $query->where('country', $country);
        }

        // Date Range Filter
        if (!empty($dateFrom)) {
            $startDate = Carbon::parse($dateFrom)->startOfDay();
            $endDate = !empty($dateTo) ? Carbon::parse($dateTo)->endOfDay() : Carbon::parse($dateFrom)->endOfDay();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } elseif (!empty($dateTo)) {
            $query->where('created_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        $members = $query->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        // Metrics Summary
        $totalCount = Member::count();
        $verifiedCount = Member::whereNotNull('mobile_verified_at')->count();
        $unverifiedCount = Member::whereNull('mobile_verified_at')->count();
        $activeCount = Member::whereNull('blocked_at')->whereNotNull('mobile_verified_at')->count();
        $pendingCount = Member::whereNull('blocked_at')->whereNull('mobile_verified_at')->whereNotNull('mobile_verification_requested_at')->count();
        $blockedCount = Member::whereNotNull('blocked_at')->count();
        $activeTodayCount = Member::where('last_seen_at', '>=', now()->subHours(24))->count();

        // Distinct Countries for Filter Dropdown
        $countries = Member::whereNotNull('country')
            ->where('country', '!=', '')
            ->distinct()
            ->pluck('country');

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'members' => $members,
                'totalCount' => $totalCount,
                'verifiedCount' => $verifiedCount,
                'unverifiedCount' => $unverifiedCount,
                'activeCount' => $activeCount,
                'pendingCount' => $pendingCount,
                'blockedCount' => $blockedCount,
                'activeTodayCount' => $activeTodayCount,
                'countries' => $countries,
                'metrics' => [
                    'totalCount' => $totalCount,
                    'verifiedCount' => $verifiedCount,
                    'unverifiedCount' => $unverifiedCount,
                    'activeCount' => $activeCount,
                    'pendingCount' => $pendingCount,
                    'blockedCount' => $blockedCount,
                    'activeTodayCount' => $activeTodayCount,
                ],
            ]);
        }

        return view('admin.members.index', compact(
            'members',
            'search',
            'status',
            'country',
            'dateFrom',
            'dateTo',
            'totalCount',
            'verifiedCount',
            'unverifiedCount',
            'activeCount',
            'pendingCount',
            'blockedCount',
            'activeTodayCount',
            'countries'
        ));
    }

    /**
     * Display a listing of Pending member account requests.
     */
    public function pending(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to'   => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
        ]);

        $search = trim((string) $request->input('q'));
        $status = $request->input('status');
        $country = $request->input('country');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = Member::query()
            ->whereNull('blocked_at')
            ->whereNull('mobile_verified_at');

        if ($status === 'not_requested' || $status === 'unverified') {
            $query->whereNull('mobile_verification_requested_at');
        } elseif ($status === 'all') {
            // all unverified
        } else {
            $query->whereNotNull('mobile_verification_requested_at');
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('user_id', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('country', 'like', "%{$search}%");
            });
        }

        if (!empty($country)) {
            $query->where('country', $country);
        }

        if (!empty($dateFrom)) {
            $startDate = Carbon::parse($dateFrom)->startOfDay();
            $endDate = !empty($dateTo) ? Carbon::parse($dateTo)->endOfDay() : Carbon::parse($dateFrom)->endOfDay();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } elseif (!empty($dateTo)) {
            $query->where('created_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        $members = $query->orderBy('mobile_verification_requested_at', 'asc')
            ->orderBy('created_at', 'asc')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        $totalCount = Member::count();
        $verifiedCount = Member::whereNotNull('mobile_verified_at')->count();
        $unverifiedCount = Member::whereNull('mobile_verified_at')->count();
        $activeCount = Member::whereNull('blocked_at')->whereNotNull('mobile_verified_at')->count();
        $pendingCount = Member::whereNull('blocked_at')->whereNull('mobile_verified_at')->whereNotNull('mobile_verification_requested_at')->count();
        $blockedCount = Member::whereNotNull('blocked_at')->count();

        $countries = Member::whereNotNull('country')
            ->where('country', '!=', '')
            ->distinct()
            ->pluck('country');

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'members' => $members,
                'totalCount' => $totalCount,
                'verifiedCount' => $verifiedCount,
                'unverifiedCount' => $unverifiedCount,
                'activeCount' => $activeCount,
                'pendingCount' => $pendingCount,
                'blockedCount' => $blockedCount,
                'countries' => $countries,
                'metrics' => [
                    'totalCount' => $totalCount,
                    'verifiedCount' => $verifiedCount,
                    'unverifiedCount' => $unverifiedCount,
                    'activeCount' => $activeCount,
                    'pendingCount' => $pendingCount,
                    'blockedCount' => $blockedCount,
                ],
            ]);
        }

        return view('admin.members.pending', compact(
            'members',
            'search',
            'country',
            'dateFrom',
            'dateTo',
            'totalCount',
            'verifiedCount',
            'unverifiedCount',
            'activeCount',
            'pendingCount',
            'blockedCount',
            'countries'
        ));
    }

    /**
     * Display a listing of Blocked members.
     */
    public function blocked(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to'   => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
        ]);

        $search = trim((string) $request->input('q'));
        $country = $request->input('country');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = Member::query()->whereNotNull('blocked_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('user_id', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('country', 'like', "%{$search}%");
            });
        }

        if (!empty($country)) {
            $query->where('country', $country);
        }

        if (!empty($dateFrom)) {
            $startDate = Carbon::parse($dateFrom)->startOfDay();
            $endDate = !empty($dateTo) ? Carbon::parse($dateTo)->endOfDay() : Carbon::parse($dateFrom)->endOfDay();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } elseif (!empty($dateTo)) {
            $query->where('created_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        $members = $query->latest('blocked_at')->paginate(15)->withQueryString();

        $totalCount = Member::count();
        $verifiedCount = Member::whereNotNull('mobile_verified_at')->count();
        $unverifiedCount = Member::whereNull('mobile_verified_at')->count();
        $activeCount = Member::whereNull('blocked_at')->whereNotNull('mobile_verified_at')->count();
        $pendingCount = Member::whereNull('blocked_at')->whereNull('mobile_verified_at')->count();
        $blockedCount = Member::whereNotNull('blocked_at')->count();

        $countries = Member::whereNotNull('country')
            ->where('country', '!=', '')
            ->distinct()
            ->pluck('country');

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'members' => $members,
                'totalCount' => $totalCount,
                'verifiedCount' => $verifiedCount,
                'unverifiedCount' => $unverifiedCount,
                'activeCount' => $activeCount,
                'pendingCount' => $pendingCount,
                'blockedCount' => $blockedCount,
                'countries' => $countries,
                'metrics' => [
                    'totalCount' => $totalCount,
                    'verifiedCount' => $verifiedCount,
                    'unverifiedCount' => $unverifiedCount,
                    'activeCount' => $activeCount,
                    'pendingCount' => $pendingCount,
                    'blockedCount' => $blockedCount,
                ],
            ]);
        }

        return view('admin.members.blocked', compact(
            'members',
            'search',
            'country',
            'dateFrom',
            'dateTo',
            'totalCount',
            'verifiedCount',
            'unverifiedCount',
            'activeCount',
            'pendingCount',
            'blockedCount',
            'countries'
        ));
    }

    /**
     * Block a member account.
     */
    public function block(Request $request, Member $member)
    {
        $member->update(['blocked_at' => now()]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Member {$member->name} ({$member->user_id}) has been blocked.",
                'member' => $member->fresh(),
            ]);
        }

        return redirect()->back()->with('success', "Member {$member->name} ({$member->user_id}) has been blocked.");
    }

    /**
     * Unblock a member account.
     */
    public function unblock(Request $request, Member $member)
    {
        $member->update(['blocked_at' => null]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Member {$member->name} ({$member->user_id}) has been unblocked.",
                'member' => $member->fresh(),
            ]);
        }

        return redirect()->back()->with('success', "Member {$member->name} ({$member->user_id}) has been unblocked.");
    }

    /**
     * Approve a pending member WhatsApp verification request (marks mobile as verified).
     */
    public function approve(Request $request, Member $member)
    {
        // 1. Idempotency guard: if already verified, do not overwrite timestamp or send duplicate notifications
        if ($member->isMobileVerified()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => "Member {$member->name} ({$member->user_id}) is already verified.",
                    'member' => $member,
                    'already_verified' => true,
                ]);
            }

            return redirect()->back()->with('info', "Member {$member->name} ({$member->user_id}) is already verified.");
        }

        // 2. Member must have a registered phone number
        if (empty($member->phone)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member does not have a registered WhatsApp/mobile number.',
                ], 422);
            }

            return redirect()->back()->with('error', 'Member does not have a registered WhatsApp/mobile number.');
        }

        // 3. Member must have requested verification
        if (empty($member->mobile_verification_requested_at)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member has not submitted a WhatsApp verification request.',
                ], 422);
            }

            return redirect()->back()->with('error', 'Member has not submitted a WhatsApp verification request.');
        }

        // 4. Mark verified, preserving phone and ensuring timestamps are recorded
        $requestTime = $member->mobile_verification_requested_at ?? now();
        $member->update([
            'mobile_verified_at' => now(),
            'mobile_verification_requested_at' => $requestTime,
        ]);
        $member->qualifyReferral();

        // 5. Notify member
        try {
            $member->notify(new \App\Notifications\SystemNotification(
                'WhatsApp Verification Approved',
                'Your WhatsApp mobile number has been verified successfully. Your verified badge is now active!',
                '/account/settings'
            ));
        } catch (\Throwable $e) {
            \Log::warning('Failed to send verification approval notification: ' . $e->getMessage());
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Member {$member->name} ({$member->user_id}) mobile number has been verified / approved.",
                'member' => $member->fresh(),
            ]);
        }

        return redirect()->back()->with('success', "Member {$member->name} ({$member->user_id}) mobile number has been verified / approved.");
    }

    /**
     * Reject or dismiss a pending member WhatsApp verification request.
     */
    public function reject(Request $request, Member $member)
    {
        // Dismiss verification request: clear request timestamp, keep mobile_verified_at null
        $member->update([
            'mobile_verification_requested_at' => null,
            'mobile_verified_at' => null,
        ]);

        // Notify member
        try {
            $member->notify(new \App\Notifications\SystemNotification(
                'WhatsApp Verification Update',
                'Your WhatsApp verification request was dismissed. Please ensure you send "Hi" from your registered number before requesting verification again.',
                '/account/settings'
            ));
        } catch (\Throwable $e) {
            \Log::warning('Failed to send verification rejection notification: ' . $e->getMessage());
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Member {$member->name} ({$member->user_id}) verification request has been dismissed.",
                'member' => $member->fresh(),
            ]);
        }

        return redirect()->back()->with('info', "Member {$member->name} ({$member->user_id}) verification request has been dismissed.");
    }

    /**
     * Display the specified member's read-only profile & statistics.
     */
    public function show(Member $member)
    {
        $member->loadCount(['posts', 'stories', 'joinedCommunities', 'events', 'reports']);
        $productsCount = Product::where('member_id', $member->id)->count();

        $posts = $member->posts()->with(['originalPost.member'])->withCount(['likes', 'comments'])->latest()->get();
        $stories = $member->stories()->latest()->get();
        $communities = $member->joinedCommunities()->latest()->get();
        $products = Product::where('member_id', $member->id)->latest()->get();
        $events = $member->events()->latest()->get();
        $postIds = $member->posts()->pluck('id');
        $reports = \App\Models\ReportedPost::with(['member', 'post'])
            ->where(function ($query) use ($member, $postIds) {
                $query->where('member_id', $member->id)
                      ->orWhereIn('post_id', $postIds);
            })
            ->latest()
            ->get()
            ->map(function ($r) use ($member, $postIds) {
                $r->setAttribute('type', 'post');
                $r->setAttribute('type_label', 'Post');
                $r->setAttribute('target_id', $r->post_id);
                $r->setAttribute('target_title', $r->post ? ('Post #' . $r->post->id) : 'Post (Deleted)');
                $r->setAttribute('created_at_human', $r->created_at ? $r->created_at->diffForHumans() : 'Recently');
                $r->setAttribute('is_reporter', $r->member_id === $member->id);
                $r->setAttribute('is_target', in_array($r->post_id, $postIds->all(), true));
                return $r;
            });

        // Connections List (Accepted active connections in either direction)
        $friendships = \App\Models\Friendship::query()
            ->forMember($member->id)
            ->accepted()
            ->with(['memberOne', 'memberTwo'])
            ->orderByDesc('accepted_at')
            ->get();

        $connections = $friendships->map(function ($f) use ($member) {
            $other = $f->otherMember($member->id);
            if ($other) {
                $other->setAttribute('connected_at', $f->accepted_at ?? $f->created_at);
            }
            return $other;
        })->filter()->values();

        $connectionsCount = $connections->count();
        $friendsCount = $connectionsCount;

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'member' => $member,
                'posts' => $posts,
                'stories' => $stories,
                'communities' => $communities,
                'products' => $products,
                'productsCount' => $productsCount,
                'events' => $events,
                'reports' => $reports,
                'connections' => $connections,
                'connectionsCount' => $connectionsCount,
                'friendsCount' => $friendsCount,
            ]);
        }

        return view('admin.members.show', compact(
            'member',
            'posts',
            'stories',
            'communities',
            'products',
            'productsCount',
            'events',
            'reports',
            'connections',
            'connectionsCount',
            'friendsCount'
        ));
    }

    /**
     * Show form for editing the selected member's profile.
     */
    public function edit(Member $member)
    {
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'member' => $member,
            ]);
        }

        return view('admin.members.edit', compact('member'));
    }

    /**
     * Update the selected member's profile.
     */
    public function update(UpdateMemberRequest $request, Member $member)
    {
        $validated = $request->validated();

        $updateData = [
            'name' => $validated['name'],
            'user_id' => $validated['user_id'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'city' => $validated['city'] ?? null,
            'country' => $validated['country'] ?? null,
            'website' => $validated['website'] ?? null,
            'bio' => $validated['bio'] ?? null,
        ];

        $oldPhoto = $member->profile_photo;
        $oldCover = $member->cover_photo;

        // 1. Handle Profile Photo
        if ($request->boolean('remove_profile_photo')) {
            $updateData['profile_photo'] = null;
        } elseif ($request->hasFile('profile_photo')) {
            $newPhoto = $this->storeFile($request->file('profile_photo'), 'uploads/profile', 'profile', $member->id);
            if ($newPhoto) {
                $updateData['profile_photo'] = $newPhoto;
            }
        }

        // 2. Handle Cover Photo
        if ($request->boolean('remove_cover_photo')) {
            $updateData['cover_photo'] = null;
        } elseif ($request->hasFile('cover_photo')) {
            $newCover = $this->storeFile($request->file('cover_photo'), 'uploads/cover', 'cover', $member->id);
            if ($newCover) {
                $updateData['cover_photo'] = $newCover;
            }
        }

        // 3. Handle Verification State
        if ($request->has('is_verified')) {
            if ($request->boolean('is_verified') && !$member->mobile_verified_at) {
                $updateData['mobile_verified_at'] = now();
            } elseif (!$request->boolean('is_verified') && $member->mobile_verified_at) {
                $updateData['mobile_verified_at'] = null;
            }
        }

        $member->update($updateData);
        if (! empty($updateData['mobile_verified_at'])) {
            $member->qualifyReferral();
        }

        // ONLY delete old files after DB update succeeds
        if ($request->boolean('remove_profile_photo') || ($request->hasFile('profile_photo') && ! empty($updateData['profile_photo']))) {
            if ($oldPhoto) {
                $this->deleteFile($oldPhoto, 'uploads/profile');
            }
        }
        if ($request->boolean('remove_cover_photo') || ($request->hasFile('cover_photo') && ! empty($updateData['cover_photo']))) {
            if ($oldCover) {
                $this->deleteFile($oldCover, 'uploads/cover');
            }
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Member profile for {$member->name} ({$member->user_id}) updated successfully.",
                'member' => $member->fresh(),
            ]);
        }

        return redirect()->route('admin.members.show', $member)->with('success', "Member profile for {$member->name} ({$member->user_id}) updated successfully.");
    }

    /**
     * Store uploaded file securely.
     */
    private function storeFile(UploadedFile $file, string $directory, string $prefix, int $id): ?string
    {
        try {
            $filename = sprintf(
                '%s_%d_%s_%s.%s',
                $prefix,
                $id,
                now()->format('YmdHis'),
                Str::random(6),
                $file->getClientOriginalExtension() ?: 'jpg'
            );

            $type = $prefix === 'cover' ? 'cover' : 'avatar';

            return app(\App\Http\Controllers\ImageCompressionController::class)->compressAndStore(
                $file,
                $directory,
                $type,
                $filename,
                'public_uploads'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Log::error("Failed to store {$prefix} image: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete an existing public file safely.
     */
    private function deleteFile(?string $path, string $directory): void
    {
        if (empty($path)) {
            return;
        }

        $cleanPath = ltrim(parse_url($path, PHP_URL_PATH) ?? $path, '/');
        if (str_starts_with($cleanPath, $directory)) {
            $fullPath = public_path($cleanPath);
            if (File::exists($fullPath) && is_file($fullPath)) {
                File::delete($fullPath);
            }
        }
    }

    /**
     * Toggle or update member verification / block status.
     */
    public function updateStatus(Request $request, Member $member)
    {
        $action = $request->input('action');

        if ($action === 'verify' || $action === 'approve') {
            $member->update(['mobile_verified_at' => now()]);
            $member->qualifyReferral();
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => "Member {$member->name} ({$member->user_id}) mobile number has been verified.", 'member' => $member]);
            }
            return redirect()->back()->with('success', "Member {$member->name} ({$member->user_id}) mobile number has been verified.");
        } elseif ($action === 'unverify' || $action === 'reject') {
            $member->update(['mobile_verified_at' => null]);
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => "Member {$member->name} ({$member->user_id}) verification status revoked / marked unverified.", 'member' => $member->fresh()]);
            }
            return redirect()->back()->with('info', "Member {$member->name} ({$member->user_id}) verification status revoked / marked unverified.");
        } elseif ($action === 'block') {
            $member->update(['blocked_at' => now()]);
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => "Member {$member->name} ({$member->user_id}) has been blocked.", 'member' => $member->fresh()]);
            }
            return redirect()->back()->with('success', "Member {$member->name} ({$member->user_id}) has been blocked.");
        } elseif ($action === 'unblock') {
            $member->update(['blocked_at' => null]);
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => "Member {$member->name} ({$member->user_id}) has been unblocked.", 'member' => $member->fresh()]);
            }
            return redirect()->back()->with('success', "Member {$member->name} ({$member->user_id}) has been unblocked.");
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => false, 'message' => 'Invalid status action requested.'], 422);
        }

        return redirect()->back()->with('error', 'Invalid status action requested.');
    }

    /**
     * Process bulk actions on selected members.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:verify,unverify,approve,reject,block,unblock,delete',
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:members,id'
        ]);

        $action = $request->input('action');
        $ids = $request->input('ids');

        if ($action === 'verify' || $action === 'approve') {
            Member::whereIn('id', $ids)->update(['mobile_verified_at' => now()]);
            $affectedMembers = Member::whereIn('id', $ids)->get();
            foreach ($affectedMembers as $m) {
                $m->qualifyReferral();
            }
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => count($ids) . ' member(s) mobile numbers have been verified.']);
            }
            return redirect()->back()->with('success', count($ids) . ' member(s) mobile numbers have been verified.');
        } elseif ($action === 'unverify' || $action === 'reject') {
            Member::whereIn('id', $ids)->update(['mobile_verified_at' => null]);
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => count($ids) . ' member(s) verification status revoked / marked unverified.']);
            }
            return redirect()->back()->with('info', count($ids) . ' member(s) verification status revoked / marked unverified.');
        } elseif ($action === 'block') {
            Member::whereIn('id', $ids)->update(['blocked_at' => now()]);
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => count($ids) . ' member(s) have been blocked.']);
            }
            return redirect()->back()->with('success', count($ids) . ' member(s) have been blocked.');
        } elseif ($action === 'unblock') {
            Member::whereIn('id', $ids)->update(['blocked_at' => null]);
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => count($ids) . ' member(s) have been unblocked.']);
            }
            return redirect()->back()->with('success', count($ids) . ' member(s) have been unblocked.');
        } elseif ($action === 'delete') {
            Member::whereIn('id', $ids)->delete();
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => count($ids) . ' members have been removed.']);
            }
            return redirect()->back()->with('success', count($ids) . ' members have been removed.');
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => false, 'message' => 'Action failed.'], 422);
        }

        return redirect()->back()->with('error', 'Action failed.');
    }

    /**
     * Export members list to CSV stream.
     */
    public function export(Request $request, string $format = 'csv')
    {
        $type = $request->query('type', 'active');
        $search = trim((string) $request->input('q'));
        $country = $request->input('country');
        $status = $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($type === 'all') {
            $query = Member::query();
            if ($status === 'verified') {
                $query->whereNotNull('mobile_verified_at');
            } elseif ($status === 'unverified') {
                $query->whereNull('mobile_verified_at');
            } elseif ($status === 'pending') {
                $query->whereNull('mobile_verified_at')->whereNotNull('mobile_verification_requested_at');
            }
            if ($request->input('account_status') === 'blocked') {
                $query->whereNotNull('blocked_at');
            } elseif ($request->input('account_status') === 'active' || $request->input('account_status') === 'unblocked') {
                $query->whereNull('blocked_at');
            }
            $filename = 'all_members_export_' . date('Y_m_d_His') . '.csv';
        } elseif ($type === 'pending' || $type === 'unverified') {
            $query = Member::query()->whereNull('blocked_at')->whereNull('mobile_verified_at');
            if ($status === 'requested' || $status === 'pending') {
                $query->whereNotNull('mobile_verification_requested_at');
            } elseif ($status === 'not_requested' || $status === 'unverified') {
                $query->whereNull('mobile_verification_requested_at');
            }
            $query->orderByRaw('mobile_verification_requested_at IS NULL ASC, mobile_verification_requested_at DESC, created_at DESC');
            $filename = 'unverified_members_export_' . date('Y_m_d_His') . '.csv';
        } elseif ($type === 'blocked') {
            $query = Member::query()->whereNotNull('blocked_at');
            $filename = 'blocked_members_export_' . date('Y_m_d_His') . '.csv';
        } else {
            $query = Member::query()->whereNull('blocked_at');
            if ($status === 'all') {
                // all unblocked
            } elseif ($status === 'unverified') {
                $query->whereNull('mobile_verified_at');
            } elseif ($status === 'active') {
                $query->whereNotNull('mobile_verified_at')->where('last_seen_at', '>=', now()->subDays(7));
            } elseif ($status === 'inactive') {
                $query->where(function ($q) {
                    $q->whereNull('last_seen_at')
                      ->orWhere('last_seen_at', '<', now()->subDays(7));
                });
            } elseif ($status === 'verified') {
                $query->whereNotNull('mobile_verified_at');
            }
            $filename = 'members_export_' . date('Y_m_d_His') . '.csv';
        }

        // Search Filter (User ID, Name, Email, Phone, City, Country)
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('user_id', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('country', 'like', "%{$search}%");
            });
        }

        // Country Filter
        if (!empty($country)) {
            $query->where('country', $country);
        }

        // Date Range Filter
        if (!empty($dateFrom)) {
            $startDate = Carbon::parse($dateFrom)->startOfDay();
            $endDate = !empty($dateTo) ? Carbon::parse($dateTo)->endOfDay() : Carbon::parse($dateFrom)->endOfDay();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } elseif (!empty($dateTo)) {
            $query->where('created_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        $query->latest('created_at');

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, ['ID', 'User ID', 'Name', 'Email', 'Phone', 'City', 'Country', 'Mobile Verification Status', 'Account Status', 'Direct Referrals', 'Referral Eligible', 'Joined Date']);

            $query->chunk(200, function ($members) use ($file) {
                foreach ($members as $m) {
                    fputcsv($file, [
                        $m->id,
                        $m->user_id,
                        $m->name,
                        $m->email,
                        $m->phone ?? 'N/A',
                        $m->city ?? 'N/A',
                        $m->country ?? 'N/A',
                        $m->mobile_verified_at ? 'VERIFIED MEMBER' : 'UNVERIFIED MEMBER',
                        $m->blocked_at ? 'Blocked' : ($m->isOnline() ? 'Online' : 'Active'),
                        $m->direct_referral_count ?? 0,
                        $m->isMobileVerified() ? 'Yes' : 'No',
                        $m->created_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Remove the specified member.
     */
    public function destroy(Member $member)
    {
        $name = $member->name;
        $userId = $member->user_id;

        $member->delete();

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Member {$name} ({$userId}) deleted successfully.",
            ]);
        }

        return redirect()->route('admin.members.index')->with('success', "Member {$name} ({$userId}) deleted successfully.");
    }

    /**
     * Remove a member's membership from a community.
     */
    public function removeCommunity(Member $member, \App\Models\Community $community)
    {
        $member->joinedCommunities()->detach($community->id);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Member {$member->name} ({$member->user_id}) has been removed from community '{$community->name}'.",
            ]);
        }

        return redirect()->back()->with('success', "Member {$member->name} ({$member->user_id}) has been removed from community '{$community->name}'.");
    }

    /**
     * Search/lookup members for Security and Wallet Address management.
     */
    public function search(Request $request)
    {
        $search = trim((string) $request->input('q'));
        $memberId = $request->input('member_id') ?? $request->input('id');

        $query = Member::query();

        if (!empty($memberId)) {
            $query->where('id', $memberId);
        } elseif ($search !== '') {
            $cleanUser = ltrim($search, '@');
            $query->where(function ($q) use ($search, $cleanUser) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('user_id', 'like', "%{$cleanUser}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('wallet_address', 'like', "%{$search}%");
                if (is_numeric($search)) {
                    $q->orWhere('id', (int) $search);
                }
            });
        }

        $members = $query->select([
            'id',
            'name',
            'user_id',
            'email',
            'phone',
            'profile_photo',
            'wallet_address',
            'mobile_verified_at',
            'blocked_at',
            'created_at',
        ])
        ->latest('created_at')
        ->limit(25)
        ->get();

        return response()->json([
            'success' => true,
            'members' => $members,
        ]);
    }

    /**
     * Display Security management page.
     */
    public function securityView(Request $request)
    {
        $selectedMember = null;
        $memberId = $request->input('member_id') ?? $request->input('id');
        if ($memberId) {
            $selectedMember = Member::find($memberId);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'selected_member' => $selectedMember,
            ]);
        }

        return view('admin.members.security', compact('selectedMember'));
    }

    /**
     * Display Wallet Address management page.
     */
    public function walletAddressView(Request $request)
    {
        $selectedMember = null;
        $memberId = $request->input('member_id') ?? $request->input('id');
        if ($memberId) {
            $selectedMember = Member::find($memberId);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'selected_member' => $selectedMember,
            ]);
        }

        return view('admin.members.wallet-address', compact('selectedMember'));
    }

    /**
     * Update/reset the selected member's password.
     */
    public function updatePassword(Request $request, Member $member)
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Please enter a new password.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        $member->password = Hash::make($request->input('password'));
        $member->save();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Password for member {$member->name} ({$member->user_id}) has been updated successfully.",
                'member' => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'user_id' => $member->user_id,
                    'email' => $member->email,
                ],
            ]);
        }

        return redirect()->back()->with('success', "Password for member {$member->name} ({$member->user_id}) has been updated successfully.");
    }

    /**
     * Update/replace the selected member's wallet address.
     */
    public function updateWalletAddress(Request $request, Member $member)
    {
        $request->validate([
            'wallet_address' => [
                'required',
                'string',
                'regex:/^0x[a-fA-F0-9]{40}$/',
            ],
        ], [
            'wallet_address.required' => 'Please enter a valid wallet address.',
            'wallet_address.regex' => 'Please enter a valid USDT (BEP-20) BNB Smart Chain wallet address (e.g. 0x71C...3972).',
        ]);

        $newAddress = trim($request->input('wallet_address'));
        $member->wallet_address = $newAddress;
        $member->save();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Wallet address for member {$member->name} ({$member->user_id}) has been updated successfully.",
                'member' => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'user_id' => $member->user_id,
                    'email' => $member->email,
                    'wallet_address' => $member->wallet_address,
                ],
            ]);
        }

        return redirect()->back()->with('success', "Wallet address for member {$member->name} ({$member->user_id}) has been updated successfully.");
    }
}
