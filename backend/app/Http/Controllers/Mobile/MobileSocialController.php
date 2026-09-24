<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\BlockedUser;
use App\Models\DirectMessage;
use App\Models\Follower;
use App\Models\Friendship;
use App\Models\Member;
use App\Notifications\FriendRequestAcceptedNotification;
use App\Notifications\FriendRequestReceivedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileSocialController extends Controller
{
    /**
     * Get member's own profile and stats.
     */
    public function myProfile(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();
        $member = $member->fresh() ?? $member;

        return response()->json([
            'success' => true,
            'profile' => [
                'id' => $member->id,
                'name' => $member->name,
                'user_id' => $member->user_id,
                'email' => $member->email,
                'phone' => $member->phone,
                'bio' => $member->bio,
                'avatar_url' => $member->avatar_url,
                'cover_photo_url' => $member->cover_photo_url,
                'is_verified' => $member->isMobileVerified(),
                'direct_referrals_count' => $member->getVerifiedDirectReferralCount(),
                'friends_count' => count($member->acceptedFriendIds()),
                'followers_count' => $member->followers()->count(),
                'following_count' => $member->following()->count(),
                'ad_balance' => (float) $member->ad_balance,
                'reward_balance' => (float) $member->reward_balance,
            ],
        ]);
    }

    /**
     * Get public profile of another member.
     */
    public function memberProfile(Request $request, int $memberId): JsonResponse
    {
        /** @var Member $viewer */
        $viewer = auth('member')->user();
        $target = Member::findOrFail($memberId);

        $isFriend = Friendship::query()->forMember($viewer->id)->where(function ($q) use ($target) {
            $q->where('member_one_id', $target->id)->orWhere('member_two_id', $target->id);
        })->where('status', 'accepted')->exists();

        $isFollowing = Follower::where('follower_id', $viewer->id)->where('following_id', $target->id)->exists();

        return response()->json([
            'success' => true,
            'profile' => [
                'id' => $target->id,
                'name' => $target->name,
                'user_id' => $target->user_id,
                'bio' => $target->bio,
                'avatar_url' => $target->avatar_url,
                'cover_photo_url' => $target->cover_photo_url,
                'is_verified' => $target->isMobileVerified(),
                'friends_count' => count($target->acceptedFriendIds()),
                'followers_count' => $target->followers()->count(),
                'is_friend' => $isFriend,
                'is_following' => $isFollowing,
            ],
        ]);
    }

    /**
     * Get friends list.
     */
    public function friends(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = $request->attributes->get('mobile_actor') ?? auth('member')->user() ?? $request->user();
        if (! $member) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        $friendIds = $member->acceptedFriendIds();

        $friends = Member::whereIn('id', $friendIds)->get()->map(function (Member $f) {
            return [
                'id' => $f->id,
                'name' => $f->name,
                'user_id' => $f->user_id,
                'avatar_url' => $f->avatar_url,
                'is_verified' => $f->isMobileVerified(),
                'city' => $f->city,
                'country' => $f->country,
            ];
        });

        return response()->json([
            'success' => true,
            'friends' => $friends,
        ]);
    }

    /**
     * Get suggested new connections with filtering, search, and country selection.
     */
    public function suggestedFriends(Request $request): JsonResponse
    {
        /** @var Member $currentMember */
        $currentMember = $request->attributes->get('mobile_actor') ?? auth('member')->user() ?? $request->user();
        if (! $currentMember) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
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
            ->pluck('country')
            ->values();

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
        } elseif ($activeFilter === 'new') {
            $query->latest('created_at');
        }

        if ($activeFilter === 'all' && $currentMember->city && $search === '' && $selectedCountry === '') {
            $query->orderByRaw('CASE WHEN city = ? THEN 0 ELSE 1 END', [$currentMember->city]);
        }

        $perPage = (int) $request->input('per_page', 20);
        $paginated = $query->latest('created_at')->paginate($perPage);

        $myAcceptedIds = $currentMember->acceptedFriendIds();

        $suggestions = $paginated->getCollection()->map(function (Member $m) use ($myAcceptedIds) {
            $memberFriendIds = $m->acceptedFriendIds();
            $mutualIds = array_values(array_intersect($myAcceptedIds, $memberFriendIds));
            return [
                'id' => $m->id,
                'name' => $m->name,
                'user_id' => $m->user_id,
                'avatar_url' => $m->avatar_url,
                'profile_photo' => $m->profile_photo,
                'city' => $m->city,
                'country' => $m->country,
                'bio' => $m->bio,
                'is_verified' => $m->isMobileVerified(),
                'mutual_count' => count($mutualIds),
                'mutual_friends' => ! empty($mutualIds)
                    ? Member::whereIn('id', array_slice($mutualIds, 0, 3))->get(['id', 'name', 'profile_photo'])->all()
                    : [],
                'friendship_state' => 'none',
                'friendship_id' => null,
            ];
        });

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
            ],
            'available_countries' => $availableCountries,
            'selected_country' => $selectedCountry,
            'search' => $search,
            'active_filter' => $activeFilter,
        ]);
    }

    /**
     * Send friend request to a target member.
     */
    public function sendFriendRequest(Request $request, int $targetMemberId): JsonResponse
    {
        /** @var Member $member */
        $member = $request->attributes->get('mobile_actor') ?? auth('member')->user() ?? $request->user();
        if (! $member) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        if ($member->id === $targetMemberId) {
            return response()->json(['message' => 'Cannot send request to yourself.'], 422);
        }

        $target = Member::find($targetMemberId);
        if (! $target) {
            return response()->json(['message' => 'Member not found.'], 404);
        }

        [$one, $two] = Friendship::normalizePair($member->id, $targetMemberId);
        $friendship = Friendship::query()->between($one, $two)->first();

        if (! $friendship) {
            $friendship = Friendship::create([
                'member_one_id' => $one,
                'member_two_id' => $two,
                'requested_by_id' => $member->id,
                'status' => Friendship::STATUS_PENDING,
            ]);
        } elseif ($friendship->status === Friendship::STATUS_ACCEPTED) {
            return response()->json([
                'success' => true,
                'message' => 'You are already connected.',
                'status' => 'friends',
                'friendship_id' => $friendship->id,
            ]);
        } else {
            $friendship->update([
                'requested_by_id' => $member->id,
                'status' => Friendship::STATUS_PENDING,
                'accepted_at' => null,
                'rejected_at' => null,
            ]);
        }

        try {
            $target->notify(new FriendRequestReceivedNotification($member, $friendship));
        } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Friend request sent.',
            'status' => 'pending_sent',
            'friendship_id' => $friendship->id,
        ]);
    }

    /**
     * Cancel an outgoing connection request.
     */
    public function cancelFriendRequest(Request $request, int $id): JsonResponse
    {
        $member = $request->attributes->get('mobile_actor') ?? auth('member')->user() ?? $request->user();
        if (! $member) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $friendship = Friendship::find($id);
        if (! $friendship) {
            [$one, $two] = Friendship::normalizePair($member->id, $id);
            $friendship = Friendship::query()->between($one, $two)->first();
        }

        if ($friendship && $friendship->status === Friendship::STATUS_PENDING) {
            $friendship->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Connection request cancelled.',
            'status' => 'none',
        ]);
    }

    /**
     * Get incoming and outgoing friend requests.
     */
    public function friendRequests(Request $request): JsonResponse
    {
        $member = $request->attributes->get('mobile_actor') ?? auth('member')->user() ?? $request->user();
        if (! $member) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $baseQuery = Friendship::query()
            ->forMember($member->id)
            ->where('status', Friendship::STATUS_PENDING)
            ->with(['memberOne', 'memberTwo'])
            ->latest();

        $incoming = (clone $baseQuery)->where('requested_by_id', '!=', $member->id)->get();
        $outgoing = (clone $baseQuery)->where('requested_by_id', $member->id)->get();

        $myFriendIds = $member->acceptedFriendIds();

        $incomingList = $incoming->map(function ($req) use ($member, $myFriendIds) {
            $sender = $req->otherMember($member->id);
            $senderFriendIds = $sender ? $sender->acceptedFriendIds() : [];
            $mutualIds = array_values(array_intersect($myFriendIds, $senderFriendIds));
            return [
                'id' => $req->id,
                'friendship_id' => $req->id,
                'status' => $req->status,
                'created_at' => $req->created_at?->diffForHumans() ?? '',
                'member' => $sender ? [
                    'id' => $sender->id,
                    'name' => $sender->name,
                    'user_id' => $sender->user_id,
                    'avatar_url' => $sender->avatar_url,
                    'is_verified' => $sender->isMobileVerified(),
                ] : null,
                'mutual_count' => count($mutualIds),
            ];
        });

        $outgoingList = $outgoing->map(function ($req) use ($member, $myFriendIds) {
            $receiver = $req->otherMember($member->id);
            $receiverFriendIds = $receiver ? $receiver->acceptedFriendIds() : [];
            $mutualIds = array_values(array_intersect($myFriendIds, $receiverFriendIds));
            return [
                'id' => $req->id,
                'friendship_id' => $req->id,
                'status' => $req->status,
                'created_at' => $req->created_at?->diffForHumans() ?? '',
                'member' => $receiver ? [
                    'id' => $receiver->id,
                    'name' => $receiver->name,
                    'user_id' => $receiver->user_id,
                    'avatar_url' => $receiver->avatar_url,
                    'is_verified' => $receiver->isMobileVerified(),
                ] : null,
                'mutual_count' => count($mutualIds),
            ];
        });

        return response()->json([
            'success' => true,
            'incoming' => $incomingList,
            'outgoing' => $outgoingList,
            'incoming_count' => $incomingList->count(),
            'outgoing_count' => $outgoingList->count(),
        ]);
    }

    /**
     * Accept or decline an incoming friend request.
     */
    public function respondFriendRequest(Request $request, int $id): JsonResponse
    {
        $member = $request->attributes->get('mobile_actor') ?? auth('member')->user() ?? $request->user();
        if (! $member) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $action = $request->input('action', 'accept');

        $friendship = Friendship::find($id);
        if (! $friendship) {
            [$one, $two] = Friendship::normalizePair($member->id, $id);
            $friendship = Friendship::query()->between($one, $two)->first();
        }

        if (! $friendship) {
            return response()->json(['message' => 'Request not found.'], 404);
        }

        if ($action === 'accept') {
            $friendship->update([
                'status' => Friendship::STATUS_ACCEPTED,
                'accepted_at' => now(),
            ]);
            try {
                $other = $friendship->otherMember($member->id);
                if ($other) {
                    $other->notify(new FriendRequestAcceptedNotification($member, $friendship));
                }
            } catch (\Throwable $e) {}

            return response()->json([
                'success' => true,
                'message' => 'Connection request accepted.',
                'status' => 'friends',
            ]);
        } else {
            $friendship->delete();
            return response()->json([
                'success' => true,
                'message' => 'Connection request declined.',
                'status' => 'declined',
            ]);
        }
    }

    /**
     * Remove / unfriend an existing connection.
     */
    public function removeFriend(Request $request, int $targetMemberId): JsonResponse
    {
        $member = $request->attributes->get('mobile_actor') ?? auth('member')->user() ?? $request->user();
        if (! $member) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        [$one, $two] = Friendship::normalizePair($member->id, $targetMemberId);
        $friendship = Friendship::query()->between($one, $two)->first();
        if ($friendship) {
            $friendship->delete();
        }

        // Add to blocked / disconnected user list to prevent immediate re-suggestions
        BlockedUser::query()->firstOrCreate([
            'member_id' => $member->id,
            'blocked_member_id' => $targetMemberId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Connection removed.',
        ]);
    }

    /**
     * Toggle follow a member.
     */
    public function toggleFollow(Request $request, int $targetMemberId): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $existing = Follower::where('follower_id', $member->id)->where('following_id', $targetMemberId)->first();
        if ($existing) {
            $existing->delete();
            $following = false;
        } else {
            Follower::create(['follower_id' => $member->id, 'following_id' => $targetMemberId]);
            $following = true;
        }

        return response()->json([
            'success' => true,
            'is_following' => $following,
        ]);
    }

    /**
     * Get 1-on-1 direct chat conversation list.
     */
    public function conversations(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        // Get members with whom user has exchanged direct messages
        $sentIds = DirectMessage::where('sender_id', $member->id)->pluck('receiver_id');
        $receivedIds = DirectMessage::where('receiver_id', $member->id)->pluck('sender_id');
        $allPartnerIds = $sentIds->concat($receivedIds)->unique()->values();

        $partners = Member::whereIn('id', $allPartnerIds)->get()->map(function (Member $p) use ($member) {
            $lastMsg = DirectMessage::where(function ($q) use ($member, $p) {
                $q->where('sender_id', $member->id)->where('receiver_id', $p->id);
            })->orWhere(function ($q) use ($member, $p) {
                $q->where('sender_id', $p->id)->where('receiver_id', $member->id);
            })->latest()->first();

            $unreadCount = DirectMessage::where('sender_id', $p->id)->where('receiver_id', $member->id)->where('is_read', false)->count();

            return [
                'partner_id' => $p->id,
                'partner_name' => $p->name,
                'partner_avatar' => $p->avatar_url,
                'last_message' => $lastMsg?->body ?? '',
                'last_message_time' => $lastMsg?->created_at?->diffForHumans() ?? '',
                'unread_count' => $unreadCount,
            ];
        });

        return response()->json([
            'success' => true,
            'conversations' => $partners,
        ]);
    }

    /**
     * Get chat messages between authenticated user and another member.
     */
    public function chatMessages(Request $request, int $partnerId): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();
        $partner = Member::findOrFail($partnerId);

        // Mark incoming messages as read
        DirectMessage::where('sender_id', $partnerId)->where('receiver_id', $member->id)->update(['is_read' => true]);

        $messages = DirectMessage::where(function ($q) use ($member, $partnerId) {
            $q->where('sender_id', $member->id)->where('receiver_id', $partnerId);
        })->orWhere(function ($q) use ($member, $partnerId) {
            $q->where('sender_id', $partnerId)->where('receiver_id', $member->id);
        })->orderBy('created_at', 'asc')->get()->map(function ($m) use ($member) {
            return [
                'id' => $m->id,
                'body' => $m->body,
                'is_mine' => $m->sender_id === $member->id,
                'time' => $m->created_at?->format('H:i'),
                'is_read' => (bool) $m->is_read,
            ];
        });

        return response()->json([
            'success' => true,
            'partner' => [
                'id' => $partner->id,
                'name' => $partner->name,
                'avatar_url' => $partner->avatar_url,
            ],
            'messages' => $messages,
        ]);
    }

    /**
     * Send direct message to another member.
     */
    public function sendMessage(Request $request, int $partnerId): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $msg = DirectMessage::create([
            'sender_id' => $member->id,
            'receiver_id' => $partnerId,
            'body' => trim($validated['body']),
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $msg->id,
                'body' => $msg->body,
                'is_mine' => true,
                'time' => 'Just now',
            ],
        ], 201);
    }

    /**
     * Get member notifications.
     */
    public function notifications(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $notifications = $member->notifications()->latest()->paginate(20)->through(function ($n) {
            $data = is_array($n->data) ? $n->data : json_decode($n->data ?? '{}', true);

            return [
                'id' => $n->id,
                'title' => $data['title'] ?? $data['subject'] ?? 'Notification',
                'message' => $data['message'] ?? $data['body'] ?? 'You have a new update.',
                'type' => $data['category'] ?? $data['type'] ?? 'general',
                'is_read' => $n->read_at !== null,
                'created_at' => $n->created_at?->diffForHumans() ?? '',
            ];
        });

        $unreadCount = $member->unreadNotifications()->count();

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark single notification as read.
     */
    public function markNotificationRead(Request $request, string $id): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $notification = $member->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'unread_count' => $member->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();
        $member->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'unread_count' => 0,
            'message' => 'All notifications marked as read.',
        ]);
    }

    /**
     * Upload or update profile photo.
     */
    public function updateProfilePhoto(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $file = $request->file('profile_photo') ?? $request->file('photo');
        if (! $file) {
            return response()->json([
                'success' => false,
                'message' => 'Please select an image file to upload.',
            ], 422);
        }

        $request->validate([
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $dest = public_path('uploads/profile');
        if (! file_exists($dest)) {
            mkdir($dest, 0755, true);
        }

        $filename = 'profile_' . $member->id . '_' . time() . '_' . \Illuminate\Support\Str::random(8) . '.' . $file->getClientOriginalExtension();
        $file->move($dest, $filename);

        $path = 'uploads/profile/' . $filename;

        if ($member->profile_photo && file_exists(public_path($member->profile_photo))) {
            @unlink(public_path($member->profile_photo));
        }

        $member->update(['profile_photo' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Your profile photo has been updated.',
            'profile_photo' => $path,
            'photo_url' => asset($path),
            'member' => $member->fresh(),
        ]);
    }

    /**
     * Remove profile photo.
     */
    public function removeProfilePhoto(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        if ($member->profile_photo && file_exists(public_path($member->profile_photo))) {
            @unlink(public_path($member->profile_photo));
        }

        $member->update(['profile_photo' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Your profile photo has been removed.',
            'member' => $member->fresh(),
        ]);
    }

    /**
     * Upload or update cover photo.
     */
    public function updateCoverPhoto(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $file = $request->file('cover_photo') ?? $request->file('cover');
        if (! $file) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a cover image file to upload.',
            ], 422);
        }

        $request->validate([
            'cover_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $dest = public_path('uploads/cover');
        if (! file_exists($dest)) {
            mkdir($dest, 0755, true);
        }

        $filename = 'cover_' . $member->id . '_' . time() . '_' . \Illuminate\Support\Str::random(8) . '.' . $file->getClientOriginalExtension();
        $file->move($dest, $filename);

        $path = 'uploads/cover/' . $filename;

        if ($member->cover_photo && file_exists(public_path($member->cover_photo))) {
            @unlink(public_path($member->cover_photo));
        }

        $member->update(['cover_photo' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Your cover photo has been updated.',
            'cover_photo' => $path,
            'photo_url' => asset($path),
            'member' => $member->fresh(),
        ]);
    }

    /**
     * Remove cover photo.
     */
    public function removeCoverPhoto(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        if ($member->cover_photo && file_exists(public_path($member->cover_photo))) {
            @unlink(public_path($member->cover_photo));
        }

        $member->update(['cover_photo' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Your cover photo has been removed.',
            'member' => $member->fresh(),
        ]);
    }
}
