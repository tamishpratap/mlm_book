<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $member = auth('member')->user();
        $filter = $request->query('filter', 'all');

        $query = $member->notifications()->latest();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'friends') {
            $query->where(function ($q) {
                $q->where('data->category', 'friends')
                  ->orWhere('type', 'like', '%Friend%');
            });
        } elseif ($filter === 'stories') {
            $query->where(function ($q) {
                $q->where('data->category', 'stories')
                  ->orWhere('type', 'like', '%Story%');
            });
        } elseif ($filter === 'posts') {
            $query->where(function ($q) {
                $q->where('data->category', 'posts')
                  ->orWhere('type', 'like', '%Post%');
            });
        } elseif ($filter === 'comments') {
            $query->where(function ($q) {
                $q->where('data->category', 'comments')
                  ->orWhere('type', 'like', '%Comment%');
            });
        } elseif ($filter === 'system') {
            $query->where(function ($q) {
                $q->where('data->category', 'system')
                  ->orWhere('type', 'like', '%System%')
                  ->orWhere('type', 'like', '%AdminBroadcast%')
                  ->orWhere('type', 'like', '%Announcement%')
                  ->orWhere('data->source', 'ADMIN')
                  ->orWhere('data->type', 'ADMIN_ANNOUNCEMENT')
                  ->orWhere('data->sender', 'Admin Broadcast');
            });
        } elseif ($filter === 'announcements') {
            $query->where(function ($q) {
                $q->where('data->type', 'ADMIN_ANNOUNCEMENT')
                  ->orWhere('type', 'like', '%AdminBroadcast%')
                  ->orWhere('type', 'like', '%Announcement%')
                  ->orWhere('data->sender', 'Admin Broadcast');
            });
        }

        $notifications = $query->paginate(20)->withQueryString();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            $formattedNotifications = (clone $notifications)->through(function ($n) {
                return $this->transformNotificationForApi($n);
            });

            return response()->json([
                'success' => true,
                'notifications' => $formattedNotifications,
                'unread_count' => $member->unreadNotifications()->count(),
                'filter' => $filter,
                'total' => $notifications->total(),
            ]);
        }

        return view('member.notifications.index', compact('notifications', 'filter'));
    }

    public function dropdown()
    {
        $member = auth('member')->user();
        $rawNotifications = $member->notifications()->latest()->limit(8)->get();
        $notifications = $rawNotifications->map(fn ($n) => $this->transformNotificationForApi($n));

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $member->unreadNotifications()->count(),
            'html' => view('member.notifications.partials.dropdown', ['notifications' => $rawNotifications])->render(),
        ]);
    }

    public function poll(Request $request)
    {
        $member = auth('member')->user();
        $clientUnreadCount = (int) $request->query('unread_count', 0);
        $currentUnreadCount = $member->unreadNotifications()->count();

        $rawNotifications = $member->notifications()->latest()->limit(8)->get();
        $notifications = $rawNotifications->map(fn ($n) => $this->transformNotificationForApi($n));

        return response()->json([
            'success' => true,
            'unread_count' => $currentUnreadCount,
            'has_new' => $currentUnreadCount > $clientUnreadCount,
            'notifications' => $notifications,
            'html' => view('member.notifications.partials.dropdown', ['notifications' => $rawNotifications])->render(),
        ]);
    }

    public function markAsRead(Request $request, string $notificationId)
    {
        $member = auth('member')->user();
        $notification = $member->notifications()->findOrFail($notificationId);
        $notification->markAsRead();
        $url = $this->safeNotificationUrl($notification->data['url'] ?? null);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read.',
                'unread_count' => $member->unreadNotifications()->count(),
                'url' => $url,
            ]);
        }

        return redirect()->to($url)->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead(Request $request)
    {
        $member = auth('member')->user();
        $member->unreadNotifications()->update(['read_at' => now()]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'All notifications have been marked as read.',
                'unread_count' => 0,
            ]);
        }

        return back()->with('success', 'All notifications have been marked as read.');
    }

    public function show(Request $request, string $notificationId)
    {
        $member = auth('member')->user();
        if (! $member) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $notification = $member->notifications()->where('id', $notificationId)->first();
        if (! $notification) {
            return response()->json(['success' => false, 'message' => 'Notification not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'notification' => $this->transformNotificationForApi($notification),
        ]);
    }

    public function destroy(Request $request, string $notificationId)
    {
        $member = auth('member')->user();
        $notification = $member->notifications()->findOrFail($notificationId);
        $notification->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Notification deleted.',
                'unread_count' => $member->unreadNotifications()->count(),
            ]);
        }

        return back()->with('success', 'Notification deleted.');
    }

    public function clearAll(Request $request)
    {
        $member = auth('member')->user();
        $member->notifications()->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'All notifications cleared.',
                'unread_count' => 0,
            ]);
        }

        return back()->with('success', 'All notifications cleared.');
    }

    private function safeNotificationUrl(mixed $url): string
    {
        if (! is_string($url)) {
            return route('member.notifications.index');
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || ! str_starts_with($path, '/member/')) {
            return route('member.notifications.index');
        }

        return url($path);
    }

    /**
     * Transform a database notification model into an authoritative API representation.
     * Guarantees consistent machine-readable notification_type, source, and data payload.
     *
     * @param mixed $notification
     * @return array<string, mixed>
     */
    public function transformNotificationForApi(mixed $notification): array
    {
        if (! $notification) {
            return [];
        }

        $id = (string) ($notification->id ?? '');
        $type = (string) ($notification->type ?? '');
        $notifiableType = (string) ($notification->notifiable_type ?? '');
        $notifiableId = $notification->notifiable_id ?? null;
        $readAt = $notification->read_at;
        $createdAt = $notification->created_at;
        $updatedAt = $notification->updated_at;

        $rawData = $notification->data ?? [];
        if (is_string($rawData)) {
            $data = json_decode($rawData, true) ?: [];
        } elseif (is_array($rawData)) {
            $data = $rawData;
        } else {
            $data = (array) $rawData;
        }

        // Check if this is an Admin Announcement
        $isAdminAnnouncement = $type === 'App\\Notifications\\AdminBroadcastNotification'
            || str_contains($type, 'AdminBroadcast')
            || str_contains($type, 'AdminAnnouncement')
            || ($data['type'] ?? null) === 'ADMIN_ANNOUNCEMENT'
            || ($data['notification_type'] ?? null) === 'ADMIN_ANNOUNCEMENT'
            || ($data['source'] ?? null) === 'ADMIN'
            || ($data['source_type'] ?? null) === 'ADMIN'
            || ($data['sender'] ?? null) === 'Admin Broadcast'
            || ($data['reference_type'] ?? null) === 'admin_announcement';

        if ($isAdminAnnouncement) {
            $notificationType = 'ADMIN_ANNOUNCEMENT';
            $source = 'ADMIN';

            // Enrich and normalize payload
            $data['type'] = 'ADMIN_ANNOUNCEMENT';
            $data['notification_type'] = 'ADMIN_ANNOUNCEMENT';
            $data['source'] = 'ADMIN';
            $data['source_type'] = 'ADMIN';
            $data['category'] = 'system';
            $data['reference_type'] = $data['reference_type'] ?? 'admin_announcement';
            $data['actor_id'] = $data['actor_id'] ?? 0;
            $data['actor_name'] = $data['actor_name'] ?? 'Admin Announcement';
            $data['sender'] = $data['sender'] ?? 'Admin Broadcast';
            $data['icon'] = $data['icon'] ?? 'bell';
            $data['url'] = $data['url'] ?? (function_exists('route') ? route('member.notifications.index') : '/member/notifications');
            $data['title'] = $data['title'] ?? 'Admin Announcement';
            $data['message'] = $data['message'] ?? ($data['body'] ?? 'You have a new announcement from administration.');
            $data['body'] = $data['body'] ?? $data['message'];
        } else {
            // Map known notification types
            $baseClass = class_basename($type);
            $source = 'MEMBER';
            $notificationType = match ($baseClass) {
                'PostReactionNotification' => 'POST_REACTION',
                'PostCommentNotification' => 'POST_COMMENT',
                'CommentReplyNotification' => 'COMMENT_REPLY',
                'CommentReactionNotification' => 'COMMENT_REACTION',
                'PostLikeNotification' => 'POST_LIKE',
                'PostShareNotification' => 'POST_SHARE',
                'SendPostToFriendNotification' => 'SEND_POST_TO_FRIEND',
                'StoryLikeNotification' => 'STORY_LIKE',
                'StoryReactionNotification' => 'STORY_REACTION',
                'StoryReplyNotification' => 'STORY_REPLY',
                'StoryViewNotification' => 'STORY_VIEW',
                'FriendRequestReceivedNotification' => 'CONNECTION_REQUEST',
                'FriendRequestAcceptedNotification' => 'CONNECTION_ACCEPTED',
                'FriendRequestRejectedNotification' => 'CONNECTION_REJECTED',
                'FriendCreatedPostNotification' => 'FRIEND_POST',
                'CommunityNotification' => 'COMMUNITY',
                'SystemNotification' => 'SYSTEM',
                default => strtoupper(str_replace(['_', '-'], '', \Illuminate\Support\Str::snake(str_replace('Notification', '', $baseClass)))),
            };

            if ($baseClass === 'SystemNotification' || ($data['category'] ?? null) === 'system') {
                $source = 'SYSTEM';
            } elseif ($baseClass === 'CommunityNotification') {
                $source = 'COMMUNITY';
            }

            if (! isset($data['notification_type'])) {
                $data['notification_type'] = $notificationType;
            }
            if (! isset($data['source'])) {
                $data['source'] = $source;
            }
            if (! isset($data['source_type'])) {
                $data['source_type'] = $source;
            }
        }

        return [
            'id' => $id,
            'type' => $type,
            'notification_type' => $notificationType,
            'source' => $source,
            'source_type' => $source,
            'notifiable_type' => $notifiableType,
            'notifiable_id' => $notifiableId,
            'data' => $data,
            'read_at' => $readAt instanceof \Carbon\CarbonInterface ? $readAt->toISOString() : $readAt,
            'created_at' => $createdAt instanceof \Carbon\CarbonInterface ? $createdAt->toISOString() : $createdAt,
            'updated_at' => $updatedAt instanceof \Carbon\CarbonInterface ? $updatedAt->toISOString() : $updatedAt,
        ];
    }
}
