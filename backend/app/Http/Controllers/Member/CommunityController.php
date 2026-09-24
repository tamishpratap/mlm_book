<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureMemberMobileVerified;
use App\Models\Community;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CommunityController extends Controller
{
    public function index(Request $request)
    {
        $member = auth('member')->user();
        $rawTab = $request->query('tab', 'all');
        // Valid tabs: 'all', 'joined', 'my', 'discover'
        // Legacy alias: 'mine' maps to 'my'
        if ($rawTab === 'mine') {
            $tab = 'my';
        } elseif (in_array($rawTab, ['all', 'joined', 'my', 'discover'], true)) {
            $tab = $rawTab;
        } else {
            $tab = 'all';
        }

        // Joined Communities: strictly where current member has an accepted membership in community_members
        $joinedCommunities = Community::query()
            ->with('owner')
            ->whereHas('members', function ($mq) use ($member) {
                $mq->where('member_id', $member->id)->where('status', 'accepted');
            })
            ->latest()
            ->get();

        // My Communities: strictly where current member is the creator/owner (owner_id)
        $myCommunities = Community::query()
            ->with('owner')
            ->where('owner_id', $member->id)
            ->latest()
            ->get();

        // Main Query according to active tab scope
        if ($tab === 'joined') {
            $query = Community::query()
                ->with('owner')
                ->whereHas('members', function ($mq) use ($member) {
                    $mq->where('member_id', $member->id)->where('status', 'accepted');
                });
        } elseif ($tab === 'my') {
            $query = Community::query()
                ->with('owner')
                ->where('owner_id', $member->id);
        } elseif ($tab === 'discover') {
            $query = Community::query()
                ->with('owner')
                ->where('visibility', '!=', 'secret')
                ->where('owner_id', '!=', $member->id)
                ->whereDoesntHave('members', function ($mq) use ($member) {
                    $mq->where('member_id', $member->id)->where('status', 'accepted');
                });
        } else {
            // 'all' tab: all discoverable communities
            $query = Community::query()
                ->with('owner')
                ->where('visibility', '!=', 'secret');
        }

        if ($request->filled('search')) {
            $search = '%'.$request->query('search').'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('description', 'like', $search)
                    ->orWhere('category', 'like', $search);
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        $communities = $query->latest('created_at')->paginate(12)->withQueryString();
        $categories = Community::CATEGORIES;
        $visibilities = Community::VISIBILITIES;

        // Efficiently append membership status attributes to collections
        $allIndexCommunities = array_merge($joinedCommunities->all(), $myCommunities->all(), $communities->items());
        Community::appendMembershipStatusToCollection($allIndexCommunities, $member?->id);

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'communities' => $communities,
                'joined_communities' => $joinedCommunities,
                'joined_count' => $joinedCommunities->count(),
                'my_communities' => $myCommunities,
                'my_count' => $myCommunities->count(),
                'categories' => $categories,
                'visibilities' => $visibilities,
                'tab' => $tab,
                'total' => $communities->total(),
            ]);
        }

        return view('member.community.index', compact(
            'communities',
            'joinedCommunities',
            'myCommunities',
            'categories',
            'visibilities',
            'tab'
        ));
    }

    public function create(Request $request)
    {
        $categories = Community::CATEGORIES;
        $visibilities = Community::VISIBILITIES;

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'categories' => $categories,
                'visibilities' => $visibilities,
            ]);
        }

        return view('member.community.create', compact('categories', 'visibilities'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', 'string', 'in:'.implode(',', Community::CATEGORIES)],
            'visibility' => ['required', 'string', 'in:public,private,invite_only,secret'],
            'rules' => ['nullable', 'string', 'max:3000'],
            'tags' => ['nullable', 'string', 'max:255'],
            'cover_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $member = auth('member')->user();

        if (! $member || ! $member->isMobileVerified()) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => EnsureMemberMobileVerified::UNVERIFIED_MESSAGE,
                ], 403);
            }
            return back()->withInput()->with('error', EnsureMemberMobileVerified::UNVERIFIED_MESSAGE);
        }

        // Generate unique slug
        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug ?: 'community';
        if (Community::where('slug', $slug)->exists()) {
            $slug = $slug.'-'.Str::random(5);
        }

        // Generate unique community_id
        $communityId = 'comm_'.Str::random(10);

        // Store cover and logo if provided
        $coverPath = null;
        if ($request->hasFile('cover_photo')) {
            $coverPath = $this->storeFile($request->file('cover_photo'), 'uploads/communities/covers');
        }

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $this->storeFile($request->file('logo'), 'uploads/communities/logos');
        }

        $community = Community::create([
            'community_id' => $communityId,
            'owner_id' => $member->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'],
            'visibility' => $validated['visibility'],
            'status' => 'active',
            'rules' => $validated['rules'] ?? null,
            'tags' => $validated['tags'] ?? null,
            'cover_photo' => $coverPath,
            'logo' => $logoPath,
            'invite_code' => Str::random(10),
            'member_count' => 0,
            'post_count' => 0,
        ]);

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Community created successfully!',
                'community' => $community->load('owner'),
            ]);
        }

        return redirect()->route('member.community.show', $community)
            ->with('success', 'Community created successfully!');
    }

    public function show(Request $request, Community $community)
    {
        $currentMember = auth('member')->user();
        $community->load('owner');
        $community->syncMemberCount();
        $community->appendMembershipStatus($currentMember?->id);

        $isMember = $currentMember ? $community->isMember($currentMember->id) : false;
        $isPending = $currentMember ? $community->isPending($currentMember->id) : false;
        $isAdmin = $currentMember ? $community->isAdmin($currentMember->id) : false;
        $isOwner = $currentMember ? $community->isOwner($currentMember->id) : false;
        $memberRole = $currentMember ? $community->memberRole($currentMember->id) : null;

        $defaultTab = ($community->isPublic() || $isMember) ? 'feed' : 'about';
        $activeTab = $request->query('tab', $defaultTab);

        // Community Feed Posts
        $announcements = collect();
        $pinnedPost = null;
        $feedPosts = collect();

        if ($activeTab === 'feed' && ($community->isPublic() || $isMember)) {
            $announcements = Post::query()
                ->with(['member', 'comments.member', 'likes', 'reactions'])
                ->where('community_id', $community->id)
                ->where('is_announcement', true)
                ->latest()
                ->get();

            $pinnedPost = Post::query()
                ->with(['member', 'comments.member', 'likes', 'reactions'])
                ->where('community_id', $community->id)
                ->where('is_pinned', true)
                ->latest()
                ->first();

            $feedQuery = Post::query()
                ->with(['member', 'comments.member', 'likes', 'reactions'])
                ->where('community_id', $community->id)
                ->where('is_announcement', false);

            if ($pinnedPost) {
                $feedQuery->where('id', '!=', $pinnedPost->id);
            }

            $feedPosts = $feedQuery->latest()->paginate(10)->withQueryString();
        }

        // Tab Data Queries (Phase 6 Content Organization)
        $photos = collect();
        if ($activeTab === 'photos' && ($community->isPublic() || $isMember)) {
            $photos = Post::query()
                ->where('community_id', $community->id)
                ->where('media_type', 'image')
                ->whereNotNull('media_path')
                ->with('member')
                ->latest()
                ->paginate(18)
                ->withQueryString();
        }

        $videos = collect();
        if ($activeTab === 'videos' && ($community->isPublic() || $isMember)) {
            $videos = Post::query()
                ->where('community_id', $community->id)
                ->where('media_type', 'video')
                ->whereNotNull('media_path')
                ->with('member')
                ->latest()
                ->paginate(12)
                ->withQueryString();
        }

        $mediaItems = collect();
        if ($activeTab === 'media' && ($community->isPublic() || $isMember)) {
            $mediaItems = Post::query()
                ->where('community_id', $community->id)
                ->whereIn('media_type', ['image', 'video'])
                ->whereNotNull('media_path')
                ->with('member')
                ->latest()
                ->paginate(18)
                ->withQueryString();
        }

        $announcementsList = collect();
        if ($activeTab === 'announcements' && ($community->isPublic() || $isMember)) {
            $announcementsList = Post::query()
                ->with(['member', 'comments.member', 'likes', 'reactions'])
                ->where('community_id', $community->id)
                ->where('is_announcement', true)
                ->latest()
                ->paginate(10)
                ->withQueryString();
        }

        // Accepted Members Query (Strictly excluding the Community Owner)
        $membersQuery = $community->acceptedMembers()
            ->where('member_id', '!=', $community->owner_id)
            ->whereHas('member', fn ($mq) => $mq->sociallyEligible())
            ->with('member');
        if ($request->filled('role')) {
            $membersQuery->where('role', $request->query('role'));
        }
        $acceptedMembers = $membersQuery->latest('joined_at')->paginate(12)->withQueryString();

        // Pending Requests Query (Only loaded for Admin/Owner)
        $pendingRequests = collect();
        if ($isAdmin) {
            $pendingRequests = $community->pendingMembers()->with('member')->latest('created_at')->get();
        }

        $pendingCount = $pendingRequests->count();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'community' => $community,
                'active_tab' => $activeTab,
                'is_member' => $isMember,
                'is_pending' => $isPending,
                'is_admin' => $isAdmin,
                'is_owner' => $isOwner,
                'member_role' => $memberRole,
                'accepted_members' => $acceptedMembers,
                'pending_requests' => $pendingRequests,
                'pending_count' => $pendingCount,
                'feed_posts' => $feedPosts,
                'pinned_post' => $pinnedPost,
                'announcements' => $announcements,
                'photos' => $photos,
                'videos' => $videos,
                'media_items' => $mediaItems,
                'announcements_list' => $announcementsList,
            ]);
        }

        return view('member.community.show', compact(
            'community',
            'activeTab',
            'announcements',
            'pinnedPost',
            'feedPosts',
            'photos',
            'videos',
            'mediaItems',
            'announcementsList',
            'acceptedMembers',
            'pendingRequests',
            'pendingCount',
            'isMember',
            'isPending',
            'isAdmin',
            'isOwner'
        ));
    }

    public function edit(Request $request, Community $community)
    {
        $member = auth('member')->user();
        if (! $community->isOwner($member->id)) {
            abort(403, 'Unauthorized action.');
        }

        $categories = Community::CATEGORIES;
        $visibilities = Community::VISIBILITIES;

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'community' => $community,
                'categories' => $categories,
                'visibilities' => $visibilities,
            ]);
        }

        return view('member.community.edit', compact('community', 'categories', 'visibilities'));
    }

    public function update(Request $request, Community $community)
    {
        $member = auth('member')->user();
        if (! $community->isOwner($member->id)) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', 'string', 'in:'.implode(',', Community::CATEGORIES)],
            'visibility' => ['required', 'string', 'in:public,private,invite_only,secret'],
            'rules' => ['nullable', 'string', 'max:3000'],
            'tags' => ['nullable', 'string', 'max:255'],
            'cover_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $oldCover = $community->cover_photo;
        $oldLogo = $community->logo;

        if ($request->hasFile('cover_photo')) {
            $validated['cover_photo'] = $this->storeFile($request->file('cover_photo'), 'uploads/communities/covers');
        }

        if ($request->hasFile('logo')) {
            $validated['logo'] = $this->storeFile($request->file('logo'), 'uploads/communities/logos');
        }

        $community->update($validated);

        // Only delete old media after new upload is moderated and DB update succeeds
        if ($request->hasFile('cover_photo') && $oldCover && File::exists(public_path($oldCover))) {
            File::delete(public_path($oldCover));
        }
        if ($request->hasFile('logo') && $oldLogo && File::exists(public_path($oldLogo))) {
            File::delete(public_path($oldLogo));
        }

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Community updated successfully!',
                'community' => $community,
            ]);
        }

        return redirect()->route('member.community.show', $community)
            ->with('success', 'Community updated successfully!');
    }

    public function destroy(Request $request, Community $community)
    {
        $member = auth('member')->user();
        if (! $community->isOwner($member->id)) {
            abort(403, 'Unauthorized action.');
        }

        $community->delete();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Community deleted successfully.',
            ]);
        }

        return redirect()->route('member.community.index')
            ->with('success', 'Community deleted successfully.');
    }

    public function updateCover(Request $request, Community $community)
    {
        $member = auth('member')->user();
        if (! ($community->isOwner($member->id) || $community->isAdmin($member->id))) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'cover_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $oldCover = $community->cover_photo;
        $coverPath = $this->storeFile($request->file('cover_photo'), 'uploads/communities/covers');
        $community->update(['cover_photo' => $coverPath]);

        if ($oldCover && File::exists(public_path($oldCover))) {
            File::delete(public_path($oldCover));
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cover photo updated successfully!',
                'cover_url' => asset($coverPath),
            ]);
        }

        return redirect()->route('member.community.show', $community)
            ->with('success', 'Cover photo updated successfully!');
    }

    public function removeCover(Request $request, Community $community)
    {
        $member = auth('member')->user();
        if (! ($community->isOwner($member->id) || $community->isAdmin($member->id))) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }
            abort(403, 'Unauthorized action.');
        }

        if ($community->cover_photo && File::exists(public_path($community->cover_photo))) {
            File::delete(public_path($community->cover_photo));
        }

        $community->update(['cover_photo' => null]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cover photo removed.',
            ]);
        }

        return redirect()->route('member.community.show', $community)
            ->with('success', 'Cover photo removed.');
    }

    public function updateLogo(Request $request, Community $community)
    {
        $member = auth('member')->user();
        if (! ($community->isOwner($member->id) || $community->isAdmin($member->id))) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $oldLogo = $community->logo;
        $logoPath = $this->storeFile($request->file('logo'), 'uploads/communities/logos');
        $community->update(['logo' => $logoPath]);

        if ($oldLogo && File::exists(public_path($oldLogo))) {
            File::delete(public_path($oldLogo));
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Community logo updated successfully!',
                'logo_url' => asset($logoPath),
            ]);
        }

        return redirect()->route('member.community.show', $community)
            ->with('success', 'Community logo updated successfully!');
    }

    public function removeLogo(Request $request, Community $community)
    {
        $member = auth('member')->user();
        if (! ($community->isOwner($member->id) || $community->isAdmin($member->id))) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }
            abort(403, 'Unauthorized action.');
        }

        if ($community->logo && File::exists(public_path($community->logo))) {
            File::delete(public_path($community->logo));
        }

        $community->update(['logo' => null]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Community logo removed.',
            ]);
        }

        return redirect()->route('member.community.show', $community)
            ->with('success', 'Community logo removed.');
    }

    private function storeFile($file, string $directory): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $fileName = Str::uuid().'.'.$extension;
        $type = str_contains($directory, 'logo') ? 'logo' : 'cover';

        $storedPath = app(\App\Http\Controllers\ImageCompressionController::class)->compressAndStore(
            $file,
            $directory,
            $type,
            $fileName,
            'public_uploads'
        );

        return $storedPath ?: ($directory.'/'.$fileName);
    }
}
