<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusinessDirectoryController extends Controller
{
    /**
     * Display the member directory listing.
     * Shows all listed user profiles except the authenticated member.
     */
    public function directory(Request $request)
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $search = trim((string) ($request->query('q') ?? $request->query('search') ?? ''));
        $country = trim((string) $request->query('country', ''));
        $sort = trim((string) $request->query('sort', 'newest'));

        $query = Member::query()
            ->where('id', '!=', $member->id)
            ->whereNull('blocked_at')
            ->withCount(['sentDirectMessages as unread_messages_count' => function ($q) use ($member) {
                $q->where('receiver_id', $member->id)
                  ->where('is_read', false);
            }])
            ->withMax(['sentDirectMessages as latest_unread_message_at' => function ($q) use ($member) {
                $q->where('receiver_id', $member->id)
                  ->where('is_read', false);
            }], 'created_at');

        if (!empty($search)) {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('user_id', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhere('country', 'like', $like);
            });
        }

        if (!empty($country) && $country !== 'All') {
            $query->where('country', $country);
        }

        $query->orderByRaw('latest_unread_message_at IS NULL ASC');
        $query->orderBy('latest_unread_message_at', 'desc');

        if ($sort === 'name') {
            $query->orderBy('name', 'asc');
        } elseif ($sort === 'oldest') {
            $query->orderBy('id', 'asc');
        } else {
            $query->orderBy('id', 'desc');
        }

        $members = $query->paginate(16)->through(function (Member $m) {
            return [
                'id' => $m->id,
                'name' => $m->name,
                'user_id' => $m->user_id,
                'email' => $m->email,
                'country' => $m->country ?? 'Global',
                'city' => $m->city,
                'profile_photo' => $m->profile_photo ? asset($m->profile_photo) : null,
                'avatar_url' => $m->profile_photo ? asset($m->profile_photo) : null,
                'bio' => $m->bio,
                'is_verified' => (bool) $m->mobile_verified_at,
                'created_at' => $m->created_at?->format('M d, Y'),
                'unread_messages_count' => $m->unread_messages_count ?? 0,
            ];
        });

        // Dynamic list of active countries from countries table
        $availableCountries = DB::table('countries')
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->toArray();

        if (empty($availableCountries)) {
            $availableCountries = Member::query()
                ->whereNotNull('country')
                ->where('country', '!=', '')
                ->distinct()
                ->pluck('country')
                ->sort()
                ->values()
                ->toArray();
        }

        $featuredMembers = Member::query()
            ->where('id', '!=', $member->id)
            ->whereNull('blocked_at')
            ->whereNotNull('profile_photo')
            ->latest()
            ->take(4)
            ->get()
            ->map(function (Member $m) {
                return [
                    'id' => $m->id,
                    'name' => $m->name,
                    'user_id' => $m->user_id,
                    'email' => $m->email,
                    'country' => $m->country ?? 'Global',
                    'city' => $m->city,
                    'profile_photo' => $m->profile_photo ? asset($m->profile_photo) : null,
                    'is_verified' => (bool) $m->mobile_verified_at,
                ];
            });

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'members' => $members,
                'featured_members' => $featuredMembers,
                'available_countries' => $availableCountries,
                'filters' => [
                    'q' => $search,
                    'country' => $country,
                    'sort' => $sort,
                ],
            ]);
        }

        return view('member.business-pages.directory.index', compact(
            'members',
            'featuredMembers',
            'availableCountries',
            'search',
            'country'
        ));
    }

    public function search(Request $request)
    {
        return $this->directory($request);
    }
}

