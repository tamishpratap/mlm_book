<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\BlockedUser;
use App\Models\Friendship;
use App\Models\Member;
use App\Models\ProfileVisit;
use Illuminate\Http\Request;

class SocialFeaturesController extends Controller
{
    public function suggestions(Request $request)
    {
        $currentMember = auth('member')->user();
        if (! $currentMember) {
            return redirect()->route('member.login');
        }

        // 1. Exclude logged in user, active connected members, and pending request members
        $activeFriendshipIds = Friendship::query()
            ->forMember($currentMember->id)
            ->whereIn('status', [Friendship::STATUS_ACCEPTED, Friendship::STATUS_PENDING])
            ->get(['member_one_id', 'member_two_id'])
            ->toBase()
            ->map(fn (Friendship $f) => $f->member_one_id === $currentMember->id ? $f->member_two_id : $f->member_one_id);

        // 2. Exclude blocked members (both directions)
        $blockedMemberIds = BlockedUser::query()
            ->where('member_id', $currentMember->id)
            ->pluck('blocked_member_id')
            ->concat(
                BlockedUser::query()
                    ->where('blocked_member_id', $currentMember->id)
                    ->pluck('member_id')
            );

        $excludeIds = $activeFriendshipIds
            ->concat($blockedMemberIds)
            ->push($currentMember->id)
            ->unique()
            ->values();

        // 3. Dynamic list of countries from database member records
        $availableCountries = Member::query()
            ->sociallyEligible()
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->distinct()
            ->orderBy('country')
            ->pluck('country');

        // 4. Build base query for new connection discovery
        $query = Member::query()
            ->sociallyEligible()
            ->whereNotIn('id', $excludeIds);

        // Country filter
        $selectedCountry = trim((string) $request->input('country', ''));
        if ($selectedCountry !== '') {
            $query->where('country', $selectedCountry);
        }

        // Member search across Name, Handle/user_id, City, Country, Bio
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('user_id', 'LIKE', "%{$search}%")
                    ->orWhere('city', 'LIKE', "%{$search}%")
                    ->orWhere('country', 'LIKE', "%{$search}%")
                    ->orWhere('bio', 'LIKE', "%{$search}%");
            });
        }

        // Quick Filters: 'all', 'new', 'mutual', 'nearby'
        $activeFilter = (string) $request->input('filter', 'all');

        if ($activeFilter === 'mutual') {
            $myAcceptedIds = $currentMember->acceptedFriendIds();
            if (! empty($myAcceptedIds)) {
                $mutualCandidateIds = Friendship::query()
                    ->accepted()
                    ->where(function ($q) use ($myAcceptedIds) {
                        $q->whereIn('member_one_id', $myAcceptedIds)
                            ->orWhereIn('member_two_id', $myAcceptedIds);
                    })
                    ->get(['member_one_id', 'member_two_id'])
                    ->toBase()
                    ->map(fn (Friendship $f) => in_array($f->member_one_id, $myAcceptedIds, true) ? $f->member_two_id : $f->member_one_id)
                    ->unique()
                    ->values();

                $query->whereIn('id', $mutualCandidateIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($activeFilter === 'nearby') {
            if ($currentMember->city || $currentMember->country) {
                $query->where(function ($q) use ($currentMember) {
                    if ($currentMember->city) {
                        $q->where('city', $currentMember->city);
                    }
                    if ($currentMember->country) {
                        $q->orWhere('country', $currentMember->country);
                    }
                });
            }
        }

        // Default ordering: prioritize city match when on default 'all' view without search/country filter, otherwise newest first
        if ($activeFilter === 'all' && $currentMember->city && $search === '' && $selectedCountry === '') {
            $query->orderByRaw('CASE WHEN city = ? THEN 0 ELSE 1 END', [$currentMember->city]);
        }

        $suggestions = $query
            ->latest('created_at')
            ->paginate(12)
            ->withQueryString();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            $myAcceptedIds = $currentMember ? $currentMember->acceptedFriendIds() : [];

            $suggestions->getCollection()->transform(function (Member $member) use ($myAcceptedIds) {
                $memberFriendIds = $member->acceptedFriendIds();
                $mutualIds = array_values(array_intersect($myAcceptedIds, $memberFriendIds));
                $member->setAttribute('mutual_count', count($mutualIds));
                $member->setAttribute('mutual_friends', ! empty($mutualIds)
                    ? Member::whereIn('id', array_slice($mutualIds, 0, 3))->get(['id', 'name', 'profile_photo'])->all()
                    : []
                );
                return $member;
            });

            return response()->json([
                'success' => true,
                'suggestions' => $suggestions,
                'available_countries' => $availableCountries,
                'selected_country' => $selectedCountry,
                'search' => $search,
                'active_filter' => $activeFilter,
                'total' => $suggestions->total(),
            ]);
        }

        return view('member.people.suggestions', compact(
            'suggestions',
            'availableCountries',
            'selectedCountry',
            'search',
            'activeFilter'
        ));
    }

    public function visitors(Request $request)
    {
        $currentMember = auth('member')->user();

        $visitors = ProfileVisit::query()
            ->with('visitor')
            ->where('profile_owner_id', $currentMember->id)
            ->orderByDesc('visited_at')
            ->paginate(15);

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'visitors' => $visitors,
                'total' => $visitors->total(),
            ]);
        }

        return view('member.profile.visitors', compact('visitors'));
    }
}
