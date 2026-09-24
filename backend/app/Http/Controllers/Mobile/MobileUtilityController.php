<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\BusinessPage;
use App\Models\Community;
use App\Models\Event;
use App\Models\FeedbackSuggestion;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MobileUtilityController extends Controller
{
    /**
     * List member feedback & suggestions.
     */
    public function feedbackList(Request $request): JsonResponse
    {
        $member = $request->user();

        $feedbacks = FeedbackSuggestion::query()
            ->where('member_id', $member->id)
            ->latest('created_at')
            ->take(50)
            ->get();

        return response()->json([
            'success' => true,
            'feedbacks' => $feedbacks,
        ]);
    }

    /**
     * Submit new feedback or suggestion.
     */
    public function storeFeedback(Request $request): JsonResponse
    {
        $member = $request->user();

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:feedback,suggestion,idea,other,bug_report,complaint'],
            'subject' => ['required', 'string', 'max:191'],
            'message' => ['required', 'string', 'min:5', 'max:5000'],
        ]);

        $feedback = FeedbackSuggestion::create([
            'member_id' => $member->id,
            'type' => strtolower(trim($validated['type'])),
            'subject' => trim($validated['subject']),
            'message' => trim($validated['message']),
            'status' => 'new',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you! Your feedback has been submitted successfully.',
            'feedback' => $feedback,
        ], 201);
    }

    /**
     * Global Search across members, communities, business pages, and posts.
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim($request->query('q', ''));
        $type = $request->query('type', 'all');

        if (empty($query)) {
            return response()->json([
                'success' => true,
                'query' => '',
                'type' => $type,
                'members' => [],
                'communities' => [],
                'pages' => [],
                'posts' => [],
            ]);
        }

        $members = [];
        $communities = [];
        $pages = [];
        $posts = [];

        if (in_array($type, ['all', 'members'])) {
            $currentMember = $request->attributes->get('mobile_actor') ?? auth('member')->user() ?? $request->user();
            $myAcceptedIds = $currentMember ? $currentMember->acceptedFriendIds() : [];

            $members = Member::withoutGlobalScopes()
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('user_id', 'like', "%{$query}%")
                      ->orWhere('city', 'like', "%{$query}%")
                      ->orWhere('country', 'like', "%{$query}%")
                      ->orWhere('email', 'like', "%{$query}%");
                })
                ->limit(20)
                ->get(['id', 'name', 'user_id', 'email', 'profile_photo', 'city', 'country', 'status', 'mobile_verified_at'])
                ->map(function ($m) use ($currentMember, $myAcceptedIds) {
                    $memberFriendIds = $m->acceptedFriendIds();
                    $mutualIds = array_values(array_intersect($myAcceptedIds, $memberFriendIds));

                    $state = 'none';
                    if ($currentMember) {
                        $friendship = \App\Models\Friendship::between($currentMember->id, $m->id)->first();
                        if ($friendship) {
                            $state = $friendship->status === 'accepted' ? 'friends' : ($friendship->requested_by_id === $currentMember->id ? 'pending_sent' : 'pending_received');
                        }
                    }

                    return [
                        'id' => $m->id,
                        'name' => $m->name,
                        'user_id' => $m->user_id,
                        'avatar_url' => $m->avatar_url,
                        'city' => $m->city,
                        'country' => $m->country,
                        'is_verified' => $m->isMobileVerified(),
                        'mutual_count' => count($mutualIds),
                        'friendship_state' => $state,
                    ];
                });
        }

        if (in_array($type, ['all', 'communities', 'groups'])) {
            $communities = Community::query()
                ->where('name', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->limit(15)
                ->get(['id', 'name', 'slug', 'description', 'avatar', 'cover_image', 'members_count']);
        }

        if (in_array($type, ['all', 'pages'])) {
            $pages = BusinessPage::query()
                ->where('name', 'like', "%{$query}%")
                ->orWhere('category', 'like', "%{$query}%")
                ->limit(15)
                ->get(['id', 'name', 'slug', 'category', 'logo', 'banner', 'website']);
        }

        if (in_array($type, ['all', 'posts'])) {
            $posts = Post::query()
                ->with('member:id,name,user_id,profile_photo')
                ->where('body', 'like', "%{$query}%")
                ->latest()
                ->limit(15)
                ->get();
        }

        return response()->json([
            'success' => true,
            'query' => $query,
            'type' => $type,
            'members' => $members,
            'communities' => $communities,
            'pages' => $pages,
            'posts' => $posts,
        ]);
    }

    /**
     * Get account settings overview.
     */
    public function accountSettings(Request $request): JsonResponse
    {
        /** @var Member|null $member */
        $member = $request->attributes->get('mobile_actor') ?? auth('member')->user() ?? $request->user();
        if (! $member) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        return response()->json([
            'success' => true,
            'member' => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'user_id' => $member->user_id,
                'phone' => $member->phone,
                'bio' => $member->bio,
                'date_of_birth' => $member->date_of_birth ? substr($member->date_of_birth, 0, 10) : null,
                'gender' => $member->gender,
                'city' => $member->city,
                'country' => $member->country,
                'website' => $member->website,
                'profile_photo' => $member->profile_photo,
                'cover_photo' => $member->cover_photo,
                'is_verified' => (bool) $member->is_verified,
                'introducer_id' => $member->introducer_id,
            ],
        ]);
    }

    /**
     * Update account personal profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var Member|null $member */
        $member = $request->attributes->get('mobile_actor') ?? auth('member')->user() ?? $request->user();
        if (! $member) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'bio' => ['nullable', 'string', 'max:500'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', 'in:male,female,other,prefer_not_to_say'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'string', 'max:255'],
        ]);

        $member->update([
            'name' => trim($validated['name']),
            'phone' => isset($validated['phone']) ? trim($validated['phone']) : $member->phone,
            'bio' => isset($validated['bio']) ? trim($validated['bio']) : $member->bio,
            'date_of_birth' => isset($validated['date_of_birth']) ? $validated['date_of_birth'] : $member->date_of_birth,
            'gender' => isset($validated['gender']) ? $validated['gender'] : $member->gender,
            'city' => isset($validated['city']) ? trim($validated['city']) : $member->city,
            'country' => isset($validated['country']) ? trim($validated['country']) : $member->country,
            'website' => isset($validated['website']) ? trim($validated['website']) : $member->website,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'member' => $member->fresh(),
        ]);
    }

    /**
     * Change account password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        /** @var Member|null $member */
        $member = $request->attributes->get('mobile_actor') ?? auth('member')->user() ?? $request->user();
        if (! $member) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6'],
        ]);

        if (! Hash::check($validated['current_password'], $member->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password does not match.',
            ], 422);
        }

        $member->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully.',
        ]);
    }
}
