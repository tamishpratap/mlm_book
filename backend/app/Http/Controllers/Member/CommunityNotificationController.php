<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\CommunityAuditLog;
use App\Models\CommunityMember;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CommunityNotificationController extends Controller
{
    public function index(Request $request)
    {
        $member = auth('member')->user();

        $category = $request->query('category', 'all');

        $query = $member->notifications()
            ->where('data->category', 'community');

        if ($category === 'unread') {
            $query->whereNull('read_at');
        } elseif ($category === 'announcements') {
            $query->where('data->action', 'announcement');
        } elseif ($category === 'moderation') {
            $query->where('data->action', 'moderation_action');
        }

        $notifications = $query->latest()->paginate(20)->withQueryString();
        $unreadCount = $member->unreadNotifications()->where('data->category', 'community')->count();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unreadCount,
                'category' => $category,
            ]);
        }

        return view('member.community.notifications-index', compact('notifications', 'unreadCount', 'category'));
    }

    public function markAsRead(Request $request, string $id)
    {
        $member = auth('member')->user();

        $notification = $member->unreadNotifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
            'unread_count' => $member->unreadNotifications()->where('data->category', 'community')->count(),
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $member = auth('member')->user();

        $member->unreadNotifications()
            ->where('data->category', 'community')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'All community notifications marked as read.',
            'unread_count' => 0,
        ]);
    }

    public function unreadCount(Request $request)
    {
        $member = auth('member')->user();

        $count = $member->unreadNotifications()
            ->where('data->category', 'community')
            ->count();

        return response()->json([
            'unread_count' => $count,
        ]);
    }

    public function updatePreferences(Request $request, Community $community)
    {
        $member = auth('member')->user();

        $membership = CommunityMember::where('community_id', $community->id)
            ->where('member_id', $member->id)
            ->firstOrFail();

        $validated = $request->validate([
            'notification_level' => ['required', 'string', 'in:all,important_only,announcements_only,posts_only,muted'],
            'mute_duration' => ['nullable', 'string', 'in:none,1h,8h,24h,7d,forever'],
        ]);

        $mutedUntil = null;
        $level = $validated['notification_level'];

        if (! empty($validated['mute_duration']) && $validated['mute_duration'] !== 'none') {
            $mutedUntil = match ($validated['mute_duration']) {
                '1h' => now()->addHour(),
                '8h' => now()->addHours(8),
                '24h' => now()->addDay(),
                '7d' => now()->addDays(7),
                'forever' => now()->addYears(10),
                default => null,
            };

            if ($validated['mute_duration'] === 'forever') {
                $level = 'muted';
            }
        }

        $membership->update([
            'notification_level' => $level,
            'muted_until' => $mutedUntil,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated for ' . $community->name . '.',
        ]);
    }

    public function activityTimeline(Request $request, Community $community)
    {
        $member = auth('member')->user();

        if (! $community->isPublic() && ! $community->isMember($member->id)) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Private community activity feed is restricted to members.'], 403);
            }
            abort(403, 'Private community activity feed is restricted to members.');
        }

        $filter = $request->query('filter', 'all');

        $query = $community->auditLogs()->with('actor');

        if ($filter === 'posts') {
            $query->whereIn('action', ['post.created', 'post.pinned', 'post.deleted']);
        } elseif ($filter === 'members') {
            $query->whereIn('action', ['member.joined', 'member.left', 'member.banned', 'member.muted', 'member.promoted']);
        } elseif ($filter === 'announcements') {
            $query->where('action', 'announcement.created');
        } elseif ($filter === 'moderation') {
            $query->whereIn('action', ['member.banned', 'member.muted', 'member.warned', 'report.approved']);
        } elseif ($filter === 'invites') {
            $query->whereIn('action', ['invite.created', 'invite.accepted', 'invite.revoked']);
        } elseif ($filter === 'settings') {
            $query->whereIn('action', ['settings.updated', 'ownership.transferred']);
        }

        $activities = $query->latest()->paginate(20)->withQueryString();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'community' => $community,
                'activities' => $activities,
                'filter' => $filter,
            ]);
        }

        return view('member.community.activity-timeline', compact('community', 'activities', 'filter'));
    }
}
