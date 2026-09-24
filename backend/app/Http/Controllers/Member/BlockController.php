<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\BlockedUser;
use App\Models\Friendship;
use App\Models\Member;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    public function index(Request $request)
    {
        $currentMember = auth('member')->user();
        if (! $currentMember) {
            abort(401, 'Unauthenticated.');
        }

        $blockedMembers = Member::query()
            ->join('blocked_users', 'members.id', '=', 'blocked_users.blocked_member_id')
            ->where('blocked_users.member_id', $currentMember->id)
            ->select('members.*', 'blocked_users.created_at as disconnected_at')
            ->orderByDesc('blocked_users.created_at')
            ->paginate(15);

        $memberIds = $blockedMembers->pluck('id');
        $friendships = Friendship::query()
            ->forMember($currentMember->id)
            ->where(function ($q) use ($memberIds) {
                $q->whereIn('member_one_id', $memberIds)
                  ->orWhereIn('member_two_id', $memberIds);
            })
            ->get();

        $friendshipsByMember = [];
        foreach ($friendships as $f) {
            $otherId = $f->member_one_id === $currentMember->id ? $f->member_two_id : $f->member_one_id;
            $friendshipsByMember[$otherId] = $f;
        }

        $blockedMembers->getCollection()->transform(function ($member) use ($friendshipsByMember, $currentMember) {
            $f = $friendshipsByMember[$member->id] ?? null;
            $member->friendship_state = $f ? $f->stateFor($currentMember->id) : 'none';
            $member->friendship_id = $f ? $f->id : null;
            return $member;
        });

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'blocked_members' => $blockedMembers,
                'disconnected_members' => $blockedMembers,
                'total' => $blockedMembers->total(),
            ]);
        }

        return view('member.blocked_users.index', compact('blockedMembers'));
    }

    public function block(Request $request, Member $member)
    {
        $currentMember = auth('member')->user();
        abort_if($currentMember->is($member), 400, 'Cannot block yourself.');

        BlockedUser::query()->firstOrCreate([
            'member_id' => $currentMember->id,
            'blocked_member_id' => $member->id,
        ]);

        Friendship::query()
            ->where(function ($q) use ($currentMember, $member) {
                $q->where('member_one_id', $currentMember->id)->where('member_two_id', $member->id);
            })->orWhere(function ($q) use ($currentMember, $member) {
                $q->where('member_one_id', $member->id)->where('member_two_id', $currentMember->id);
            })->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Blocked '.$member->name,
            ]);
        }

        return redirect()->route('member.dashboard')->with('success', 'Blocked '.$member->name);
    }

    public function unblock(Request $request, Member $member)
    {
        $currentMember = auth('member')->user();

        BlockedUser::query()
            ->where('member_id', $currentMember->id)
            ->where('blocked_member_id', $member->id)
            ->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Unblocked '.$member->name,
            ]);
        }

        return back()->with('success', 'Unblocked '.$member->name);
    }
}
