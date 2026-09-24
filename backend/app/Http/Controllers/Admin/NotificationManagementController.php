<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessNotification;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationManagementController extends Controller
{
    /**
     * Display a listing of notifications with search, channel & status filtering, and pagination.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('q'));
        $channel = $request->input('channel');
        $status = $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $notifications = collect();

        // 1. Database Notifications
        if (empty($channel) || $channel === 'in_app') {
            $dbNotes = DB::table('notifications')->get()->map(function ($n) {
                $data = json_decode($n->data, true) ?: [];
                $recipientName = 'System';
                $recipientUserId = 'N/A';
                $recipientAvatar = null;

                if ($n->notifiable_type === 'App\Models\Admin') {
                    $admin = \App\Models\Admin::find($n->notifiable_id);
                    $recipientName = $admin ? ($admin->name . ' (Admin)') : ('Admin #' . $n->notifiable_id);
                    $recipientUserId = $admin?->email ?? 'Admin';
                } else {
                    $member = Member::find($n->notifiable_id);
                    $recipientName = $member?->name ?? 'Member #' . $n->notifiable_id;
                    $recipientUserId = $member?->user_id ?? 'N/A';
                    $recipientAvatar = $member?->avatar_url;
                }

                return (object) [
                    'id' => $n->id,
                    'channel' => 'In-App',
                    'type' => Str::afterLast($n->type, '\\'),
                    'recipient_name' => $recipientName,
                    'recipient_user_id' => $recipientUserId,
                    'recipient_avatar' => $recipientAvatar,
                    'title' => $data['title'] ?? $data['message'] ?? 'Platform Notification',
                    'message' => $data['body'] ?? $data['message'] ?? 'Notification details',
                    'is_read' => !is_null($n->read_at),
                    'read_at' => $n->read_at,
                    'created_at' => \Carbon\Carbon::parse($n->created_at),
                    'raw_data' => $data,
                ];
            });
            $notifications = $notifications->concat($dbNotes);
        }

        // 2. Business Page Notifications
        if (empty($channel) || $channel === 'business') {
            $bizNotes = BusinessNotification::with('member')->get()->map(function ($n) {
                return (object) [
                    'id' => (string)$n->id,
                    'channel' => 'Business Alert',
                    'type' => $n->type,
                    'recipient_name' => $n->member?->name ?? 'N/A',
                    'recipient_user_id' => $n->member?->user_id ?? 'N/A',
                    'recipient_avatar' => $n->member?->avatar_url,
                    'title' => $n->title,
                    'message' => is_array($n->data) ? ($n->data['message'] ?? 'Business alert') : (string)$n->data,
                    'is_read' => $n->is_read,
                    'read_at' => $n->is_read ? $n->updated_at : null,
                    'created_at' => $n->created_at,
                    'raw_data' => $n->data,
                ];
            });
            $notifications = $notifications->concat($bizNotes);
        }

        // Filtering
        if ($search !== '') {
            $notifications = $notifications->filter(function ($n) use ($search) {
                return stripos($n->recipient_name, $search) !== false
                    || stripos($n->recipient_user_id, $search) !== false
                    || stripos($n->title, $search) !== false
                    || stripos($n->type, $search) !== false
                    || (string)$n->id === $search;
            });
        }

        if (!empty($status)) {
            $notifications = $notifications->filter(function ($n) use ($status) {
                if ($status === 'read') return $n->is_read;
                if ($status === 'unread') return !$n->is_read;
                return true;
            });
        }

        if (!empty($dateFrom)) {
            $notifications = $notifications->filter(function ($n) use ($dateFrom) {
                return $n->created_at && $n->created_at->format('Y-m-d') >= $dateFrom;
            });
        }

        if (!empty($dateTo)) {
            $notifications = $notifications->filter(function ($n) use ($dateTo) {
                return $n->created_at && $n->created_at->format('Y-m-d') <= $dateTo;
            });
        }

        // Sort latest
        $notifications = $notifications->sortByDesc('created_at')->values();

        // Paginate manually
        $page = (int) $request->input('page', 1);
        $perPage = 15;
        $paginatedNotifications = new \Illuminate\Pagination\LengthAwarePaginator(
            $notifications->slice(($page - 1) * $perPage, $perPage)->values(),
            $notifications->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Metrics Summary
        $totalCount = DB::table('notifications')->count() + BusinessNotification::count();
        $sentTodayCount = DB::table('notifications')->whereDate('created_at', now()->today())->count();
        $readCount = DB::table('notifications')->whereNotNull('read_at')->count();
        $queuedJobsCount = DB::table('jobs')->count();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'notifications' => $paginatedNotifications,
                'paginatedNotifications' => $paginatedNotifications,
                'totalCount' => $totalCount,
                'unreadCount' => $totalCount - $readCount,
                'readCount' => $readCount,
                'sentTodayCount' => $sentTodayCount,
                'queuedJobsCount' => $queuedJobsCount,
            ]);
        }

        return view('admin.notifications.index', compact(
            'paginatedNotifications',
            'search',
            'channel',
            'status',
            'dateFrom',
            'dateTo',
            'totalCount',
            'sentTodayCount',
            'readCount',
            'queuedJobsCount'
        ));
    }

    /**
     * Display detailed read-only inspection of a notification.
     */
    public function show(Request $request, string $id)
    {
        $notification = null;

        $dbNote = DB::table('notifications')->where('id', $id)->first();
        if ($dbNote) {
            $data = json_decode($dbNote->data, true) ?: [];
            $recipient = null;
            if ($dbNote->notifiable_type === 'App\Models\Admin') {
                $recipient = \App\Models\Admin::find($dbNote->notifiable_id);
            } else {
                $recipient = Member::find($dbNote->notifiable_id);
            }

            $notification = (object) [
                'id' => $dbNote->id,
                'channel' => 'In-App',
                'type' => Str::afterLast($dbNote->type, '\\'),
                'recipient' => $recipient,
                'title' => $data['title'] ?? $data['message'] ?? 'Platform Notification',
                'message' => $data['body'] ?? $data['message'] ?? 'Notification details',
                'is_read' => !is_null($dbNote->read_at),
                'read_at' => $dbNote->read_at,
                'created_at' => \Carbon\Carbon::parse($dbNote->created_at),
                'raw_data' => $data,
            ];
        } else {
            $bizNote = BusinessNotification::with('member')->find($id);
            if ($bizNote) {
                $notification = (object) [
                    'id' => (string)$bizNote->id,
                    'channel' => 'Business Alert',
                    'type' => $bizNote->type,
                    'recipient' => $bizNote->member,
                    'title' => $bizNote->title,
                    'message' => is_array($bizNote->data) ? ($bizNote->data['message'] ?? 'Business alert') : (string)$bizNote->data,
                    'is_read' => $bizNote->is_read,
                    'read_at' => $bizNote->is_read ? $bizNote->updated_at : null,
                    'created_at' => $bizNote->created_at,
                    'raw_data' => $bizNote->data,
                ];
            } else {
                if (request()->expectsJson() || request()->is('api/*')) {
                    return response()->json(['message' => 'Notification record not found.'], 404);
                }
                abort(404, 'Notification record not found.');
            }
        }

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'notification' => $notification,
            ]);
        }

        return view('admin.notifications.show', compact('notification'));
    }

    /**
     * Show broadcast announcement form.
     */
    public function broadcastForm()
    {
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'totalRecipients' => Member::count(),
            ]);
        }

        return view('admin.notifications.broadcast');
    }

    /**
     * Dispatch broadcast announcement to target member audience.
     */
    public function sendBroadcast(Request $request)
    {
        $request->validate([
            'audience' => 'required|string|in:all,active,business_owners,sellers',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        $audience = $request->input('audience');
        $title = $request->input('title');
        $message = $request->input('message');

        $query = Member::query();
        if ($audience === 'active') {
            $query->where('status', 'active');
        } elseif ($audience === 'business_owners') {
            $query->whereHas('businessPages');
        } elseif ($audience === 'sellers') {
            $query->whereHas('products');
        }

        $recipients = $query->get();

        $count = 0;
        $now = now();
        $broadcastNotification = new \App\Notifications\AdminBroadcastNotification(
            title: $title,
            broadcastMessage: $message,
            audience: $audience
        );
        $payload = $broadcastNotification->toArray(new \stdClass());

        foreach ($recipients as $member) {
            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\AdminBroadcastNotification',
                'notifiable_type' => 'App\\Models\\Member',
                'notifiable_id' => $member->id,
                'data' => json_encode($payload),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $count++;
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Broadcast announcement sent to {$count} members successfully.",
            ]);
        }

        return redirect()->route('admin.notifications.index')->with('success', "Broadcast announcement sent to {$count} members successfully.");
    }

    /**
     * Mark single notification as read.
     */
    public function markRead(Request $request, string $id)
    {
        $admin = auth('admin')->user();
        if ($admin) {
            $admin->notifications()->where('id', $id)->update(['read_at' => now()]);
        } else {
            DB::table('notifications')->where('id', $id)->update(['read_at' => now()]);
        }
        if (is_numeric($id)) {
            BusinessNotification::where('id', $id)->update(['is_read' => true]);
        }

        if ($request->wantsJson() || $request->ajax() || $request->is('api/*') || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Notification marked as read.']);
        }

        return redirect()->back()->with('success', "Notification marked as read.");
    }

    /**
     * Get unread notification count for the authenticated admin.
     */
    public function unreadCount(Request $request)
    {
        $admin = auth('admin')->user() ?: $request->user('admin') ?: auth()->user();
        $count = $admin ? $admin->unreadNotifications()->count() : 0;

        return response()->json([
            'success' => true,
            'count' => $count,
            'unread_count' => $count,
        ]);
    }

    /**
     * Get recent notifications and unread count for the authenticated admin's dropdown.
     */
    public function dropdown(Request $request)
    {
        $admin = auth('admin')->user() ?: $request->user('admin') ?: auth()->user();

        if (!$admin) {
            return response()->json([
                'success' => true,
                'unread_count' => 0,
                'count' => 0,
                'notifications' => [],
            ]);
        }

        $unreadCount = $admin->unreadNotifications()->count();
        $notifications = $admin->notifications()->take(15)->get()->map(function ($n) {
            $data = is_array($n->data) ? $n->data : (json_decode($n->data, true) ?: []);
            return [
                'id' => $n->id,
                'title' => $data['title'] ?? $data['message'] ?? 'Notification',
                'message' => $data['message'] ?? $data['body'] ?? '',
                'icon' => $data['icon'] ?? 'bell',
                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,
                'action_url' => $data['action_url'] ?? null,
                'time' => $n->created_at ? $n->created_at->diffForHumans() : '',
                'read' => !is_null($n->read_at),
                'read_at' => $n->read_at,
                'created_at' => $n->created_at ? $n->created_at->toIso8601String() : null,
            ];
        });

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
            'count' => $unreadCount,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark all notifications as read for the authenticated admin.
     */
    public function markAllRead(Request $request)
    {
        $admin = auth('admin')->user() ?: $request->user('admin') ?: auth()->user();

        if (!$admin) {
            if ($request->wantsJson() || $request->ajax() || $request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                    'unread_count' => 0,
                ], 401);
            }
            return redirect()->back()->with('error', 'Unauthenticated.');
        }

        $unreadCount = $admin->unreadNotifications()->count();

        if ($unreadCount > 0) {
            $admin->unreadNotifications()->update(['read_at' => now()]);
            $message = 'Notifications marked as read';
        } else {
            $message = 'No unread notifications';
        }

        if ($request->wantsJson() || $request->ajax() || $request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'unread_count' => 0,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Process bulk actions on selected notifications.
     */
    public function bulkAction(Request $request)
    {
        $action = $request->input('action');
        $ids = $request->input('ids', []);
        $numericIds = array_filter($ids, 'is_numeric');

        if ($action === 'read') {
            DB::table('notifications')->whereIn('id', $ids)->update(['read_at' => now()]);
            if (!empty($numericIds)) {
                BusinessNotification::whereIn('id', $numericIds)->update(['is_read' => true]);
            }
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => count($ids) . ' notifications marked as read.']);
            }
            return redirect()->back()->with('success', count($ids) . ' notifications marked as read.');
        } elseif ($action === 'delete') {
            DB::table('notifications')->whereIn('id', $ids)->delete();
            if (!empty($numericIds)) {
                BusinessNotification::whereIn('id', $numericIds)->delete();
            }
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => count($ids) . ' notifications deleted.']);
            }
            return redirect()->back()->with('success', count($ids) . ' notifications deleted.');
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => false, 'message' => 'Invalid bulk action.'], 422);
        }

        return redirect()->back()->with('error', 'Invalid bulk action.');
    }

    /**
     * Export notifications to CSV stream.
     */
    public function export(Request $request)
    {
        $status = $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = DB::table('notifications')->orderBy('created_at', 'desc');

        if ($status === 'read') {
            $query->whereNotNull('read_at');
        } elseif ($status === 'unread') {
            $query->whereNull('read_at');
        }

        if (!empty($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $filename = 'notifications_export_' . date('Y_m_d_His') . '.csv';

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
            fputcsv($file, ['Notification ID', 'Type', 'Recipient ID', 'Title / Message', 'Read Status', 'Created Date']);

            $query->chunk(200, function ($items) use ($file) {
                foreach ($items as $n) {
                    $data = json_decode($n->data, true) ?: [];
                    fputcsv($file, [
                        $n->id,
                        Str::afterLast($n->type, '\\'),
                        $n->notifiable_id,
                        $data['title'] ?? $data['message'] ?? 'Notification',
                        $n->read_at ? 'Read' : 'Unread',
                        $n->created_at,
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
