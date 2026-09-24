<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\BlockedUser;
use App\Models\Friendship;
use App\Models\Member;
use App\Notifications\FriendRequestAcceptedNotification;
use App\Notifications\FriendRequestReceivedNotification;
use App\Notifications\FriendRequestRejectedNotification;
use App\Http\Middleware\EnsureMemberMobileVerified;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FriendshipController extends Controller
{
    public function index(Request $request)
    {
        $currentMember = auth('member')->user();
        $friends = $this->acceptedFriends($currentMember, 18);

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'friends' => $friends,
                'total' => $friends->total(),
                'current_member' => $currentMember,
            ]);
        }

        return view('member.friends.index', compact('currentMember', 'friends'));
    }

    public function requests(Request $request)
    {
        $currentMember = auth('member')->user();
        $baseQuery = Friendship::query()
            ->forMember($currentMember->id)
            ->where('status', Friendship::STATUS_PENDING)
            ->with(['memberOne', 'memberTwo'])
            ->latest();

        $incomingRequests = (clone $baseQuery)
            ->where('requested_by_id', '!=', $currentMember->id)
            ->get();
        $outgoingRequests = (clone $baseQuery)
            ->where('requested_by_id', $currentMember->id)
            ->get();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            $myFriendIds = $currentMember ? $currentMember->acceptedFriendIds() : [];

            $incomingList = $incomingRequests->map(function ($req) use ($currentMember, $myFriendIds) {
                $sender = $req->otherMember($currentMember->id);
                $senderFriendIds = $sender ? $sender->acceptedFriendIds() : [];
                $mutualIds = array_values(array_intersect($myFriendIds, $senderFriendIds));
                $mutualFriends = ! empty($mutualIds)
                    ? Member::whereIn('id', array_slice($mutualIds, 0, 3))->get(['id', 'name', 'profile_photo'])->all()
                    : [];

                return [
                    'id' => $req->id,
                    'friendship_id' => $req->id,
                    'status' => $req->status,
                    'created_at' => $req->created_at,
                    'friend' => $sender,
                    'friendship' => $req,
                    'friendship_state' => 'pending_received',
                    'mutual_count' => count($mutualIds),
                    'mutual_friends' => $mutualFriends,
                ];
            });

            $outgoingList = $outgoingRequests->map(function ($req) use ($currentMember, $myFriendIds) {
                $receiver = $req->otherMember($currentMember->id);
                $receiverFriendIds = $receiver ? $receiver->acceptedFriendIds() : [];
                $mutualIds = array_values(array_intersect($myFriendIds, $receiverFriendIds));
                $mutualFriends = ! empty($mutualIds)
                    ? Member::whereIn('id', array_slice($mutualIds, 0, 3))->get(['id', 'name', 'profile_photo'])->all()
                    : [];

                return [
                    'id' => $req->id,
                    'friendship_id' => $req->id,
                    'status' => $req->status,
                    'created_at' => $req->created_at,
                    'friend' => $receiver,
                    'friendship' => $req,
                    'friendship_state' => 'pending_sent',
                    'mutual_count' => count($mutualIds),
                    'mutual_friends' => $mutualFriends,
                ];
            });

            return response()->json([
                'success' => true,
                'incoming_requests' => $incomingList,
                'outgoing_requests' => $outgoingList,
                'incoming_count' => $incomingRequests->count(),
                'outgoing_count' => $outgoingRequests->count(),
                'current_member' => $currentMember,
            ]);
        }

        return view('member.friends.requests', compact(
            'currentMember',
            'incomingRequests',
            'outgoingRequests',
        ));
    }

    public function send(Request $request, Member $member)
    {
        $currentMember = auth('member')->user();

        if (! $currentMember || ! $currentMember->isSociallyEligible()) {
            return $this->errorResponse($request, EnsureMemberMobileVerified::UNVERIFIED_MESSAGE, 403);
        }

        if (! $member->isSociallyEligible()) {
            return $this->errorResponse($request, 'This member is not verified and cannot receive connection requests.', 403);
        }

        if ($currentMember->is($member)) {
            return $this->errorResponse($request, 'You cannot send a connection request to yourself.');
        }

        if ($member->hasBlocked($currentMember->id)) {
            return $this->errorResponse($request, 'Unable to send connection request to this member.', 403);
        }

        [$memberOneId, $memberTwoId] = Friendship::normalizePair($currentMember->id, $member->id);

        try {
            [$friendship, $message, $shouldNotify] = DB::transaction(function () use (
                $currentMember,
                $memberOneId,
                $memberTwoId,
                $member,
            ) {
                $friendship = Friendship::query()
                    ->between($memberOneId, $memberTwoId)
                    ->lockForUpdate()
                    ->first();

                if (! $friendship) {
                    $friendship = Friendship::create([
                        'member_one_id' => $memberOneId,
                        'member_two_id' => $memberTwoId,
                        'requested_by_id' => $currentMember->id,
                        'status' => Friendship::STATUS_PENDING,
                    ]);

                    return [$friendship, 'Connection request sent successfully.', true];
                }

                if ($friendship->status === Friendship::STATUS_ACCEPTED) {
                    return [$friendship, 'You are already connected.', false];
                }

                if ($friendship->status === Friendship::STATUS_PENDING) {
                    $message = $friendship->requested_by_id === $currentMember->id
                        ? 'Connection request has already been sent.'
                        : 'This Member has already sent you a connection request. You can accept or reject it.';

                    return [$friendship, $message, false];
                }

                $friendship->update([
                    'requested_by_id' => $currentMember->id,
                    'status' => Friendship::STATUS_PENDING,
                    'accepted_at' => null,
                    'rejected_at' => null,
                ]);

                return [$friendship, 'Connection request sent successfully.', true];
            });
        } catch (QueryException) {
            $shouldNotify = false;
            $friendship = Friendship::between($currentMember->id, $member->id)->first();

            if (! $friendship) {
                return $this->errorResponse($request, 'The connection request could not be sent. Please try again.');
            }

            $message = $friendship->stateFor($currentMember->id) === 'pending_received'
                ? 'This Member has already sent you a connection request. You can accept or reject it.'
                : ($friendship->status === Friendship::STATUS_ACCEPTED
                    ? 'You are already connected.'
                    : 'Connection request has already been sent.');
        }

        if ($shouldNotify) {
            $member->notify(new FriendRequestReceivedNotification($currentMember, $friendship));
        }

        return $this->successResponse($request, $member, $friendship, $message);
    }

    public function accept(Request $request, Friendship $friendship)
    {
        return $this->resolveRequest(
            $request,
            $friendship,
            Friendship::STATUS_ACCEPTED,
            'Connection request accepted successfully.',
        );
    }

    public function reject(Request $request, Friendship $friendship)
    {
        return $this->resolveRequest(
            $request,
            $friendship,
            Friendship::STATUS_REJECTED,
            'Connection request rejected.',
        );
    }

    public function cancel(Request $request, Friendship $friendship)
    {
        $currentMember = auth('member')->user();

        abort_unless(
            $friendship->status === Friendship::STATUS_PENDING
            && (int) $friendship->requested_by_id === (int) $currentMember->id,
            403,
            'You cannot cancel this connection request.'
        );

        $targetMember = $friendship->loadMissing(['memberOne', 'memberTwo'])->otherMember($currentMember->id);
        $friendship->delete();
        $friendship->status = 'cancelled';

        return $this->successResponse($request, $targetMember, $friendship, 'Connection request cancelled.');
    }

    public function memberFriends(Request $request, Member $member)
    {
        $currentMember = auth('member')->user();
        $isSelf = $currentMember && $currentMember->is($member);

        if (! $isSelf && ! $member->isSociallyEligible()) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member profile not found or is currently unavailable.',
                ], 404);
            }

            abort(404, 'Member profile not found or is currently unavailable.');
        }

        $friends = $this->acceptedFriends($member, 18);

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'member' => $member,
                'friends' => $friends,
                'total' => $friends->total(),
            ]);
        }

        return view('member.friends.member-friends', compact('member', 'friends'));
    }

    public function remove(Request $request, Member $member)
    {
        $currentMember = auth('member')->user();
        abort_if(! $currentMember, 401, 'Unauthenticated.');
        abort_if($currentMember->is($member), 400, 'Cannot disconnect from yourself.');

        $friendship = Friendship::query()
            ->between($currentMember->id, $member->id)
            ->first();

        if ($friendship) {
            $friendship->delete();
        }

        // Persist the disconnection: current member disconnected the target member
        BlockedUser::query()->firstOrCreate([
            'member_id' => $currentMember->id,
            'blocked_member_id' => $member->id,
        ]);

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Connection removed.',
                'state' => 'none',
                'member_id' => $member->id,
            ]);
        }

        return back()->with('success', 'Connection removed.');
    }

    private function resolveRequest(
        Request $request,
        Friendship $friendship,
        string $newStatus,
        string $message,
    ) {
        $currentMember = auth('member')->user();

        if ($newStatus === Friendship::STATUS_ACCEPTED) {
            $sender = $friendship->otherMember($currentMember->id);
            if (! $currentMember || ! $currentMember->isSociallyEligible() || ! $sender || ! $sender->isSociallyEligible()) {
                return $this->errorResponse($request, 'Both members must be verified to establish a connection.', 403);
            }
        }

        $updatedFriendship = DB::transaction(function () use ($friendship, $currentMember, $newStatus) {
            $lockedFriendship = Friendship::query()
                ->whereKey($friendship->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $lockedFriendship->status === Friendship::STATUS_PENDING
                && $lockedFriendship->requested_by_id !== $currentMember->id
                && $lockedFriendship->receiverId() === $currentMember->id,
                403,
            );

            $lockedFriendship->update([
                'status' => $newStatus,
                'accepted_at' => $newStatus === Friendship::STATUS_ACCEPTED ? now() : null,
                'rejected_at' => $newStatus === Friendship::STATUS_REJECTED ? now() : null,
            ]);

            if ($newStatus === Friendship::STATUS_ACCEPTED) {
                BlockedUser::query()
                    ->where(function ($q) use ($lockedFriendship) {
                        $q->where('member_id', $lockedFriendship->member_one_id)
                            ->where('blocked_member_id', $lockedFriendship->member_two_id);
                    })
                    ->orWhere(function ($q) use ($lockedFriendship) {
                        $q->where('member_id', $lockedFriendship->member_two_id)
                            ->where('blocked_member_id', $lockedFriendship->member_one_id);
                    })
                    ->delete();
            }

            return $lockedFriendship;
        });

        $targetMember = $updatedFriendship
            ->loadMissing(['memberOne', 'memberTwo'])
            ->otherMember($currentMember->id);

        $notification = $newStatus === Friendship::STATUS_ACCEPTED
            ? new FriendRequestAcceptedNotification($currentMember, $updatedFriendship)
            : new FriendRequestRejectedNotification($currentMember, $updatedFriendship);
        $targetMember->notify($notification);

        return $this->successResponse($request, $targetMember, $updatedFriendship, $message);
    }

    private function acceptedFriends(Member $member, int $perPage): LengthAwarePaginator
    {
        $friendships = Friendship::query()
            ->forMember($member->id)
            ->accepted()
            ->with(['memberOne', 'memberTwo'])
            ->orderByDesc('accepted_at')
            ->paginate($perPage)
            ->withQueryString();

        $friendships->setCollection(
            $friendships->getCollection()->map(
                fn (Friendship $friendship) => $friendship->otherMember($member->id),
            ),
        );

        return $friendships;
    }

    private function successResponse(
        Request $request,
        Member $targetMember,
        Friendship $friendship,
        string $message,
    ) {
        $friendshipState = $friendship->stateFor(auth('member')->id());

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'state' => $friendshipState,
                'member_id' => $targetMember->id,
                'html' => view('member.friends.partials.actions', compact(
                    'targetMember',
                    'friendship',
                    'friendshipState',
                ))->render(),
            ]);
        }

        return back()->with('success', $message);
    }

    private function errorResponse(Request $request, string $message, int $statusCode = 422)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $statusCode);
        }

        return back()->with('error', $message);
    }
}
