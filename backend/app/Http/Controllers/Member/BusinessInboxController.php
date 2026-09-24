<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\BusinessConversation;
use App\Models\BusinessMessage;
use App\Models\BusinessNotification;
use App\Models\BusinessPage;
use App\Models\BusinessQuickReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BusinessInboxController extends Controller
{
    public function index(Request $request, BusinessPage $businessPage)
    {
        $currentMember = auth('member')->user();

        if (! $businessPage->canAccessInbox($currentMember->id)) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action. Only authorized team members can access Business Inbox.'], 403);
            }
            abort(403, 'Unauthorized action. Only authorized team members can access Business Inbox.');
        }

        $filter = $request->query('filter', 'all');
        $q = trim($request->query('q', ''));

        $query = BusinessConversation::query()
            ->where('business_page_id', $businessPage->id)
            ->with(['customer', 'latestMessage']);

        if (filled($q)) {
            $query->where(function ($cq) use ($q) {
                $cq->whereHas('customer', function ($mq) use ($q) {
                    $mq->where('name', 'like', "%{$q}%")
                        ->orWhere('user_id', 'like', "%{$q}%");
                })->orWhereHas('messages', function ($msgq) use ($q) {
                    $msgq->where('message', 'like', "%{$q}%");
                });
            });
        }

        if ($filter === 'unread') {
            $query->whereHas('messages', function ($msgq) {
                $msgq->where('sender_type', 'customer')->where('is_read', false);
            });
        } elseif ($filter === 'starred') {
            $query->where('is_starred', true);
        } elseif ($filter === 'requests') {
            $query->where('status', 'pending_request');
        } elseif ($filter === 'archived') {
            $query->where('status', 'archived');
        } elseif ($filter === 'closed') {
            $query->where('status', 'closed');
        } else {
            $query->whereIn('status', ['active', 'pending_request']);
        }

        $conversations = $query->orderBy('is_pinned', 'desc')
            ->orderBy('last_message_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $quickReplies = BusinessQuickReply::where('business_page_id', $businessPage->id)->get();
        $notifications = BusinessNotification::where('business_page_id', $businessPage->id)
            ->latest()
            ->take(20)
            ->get();

        $canReply = $businessPage->canReplyInInbox($currentMember->id);
        $isOwner = $businessPage->isOwner($currentMember->id);

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'business_page' => $businessPage,
                'conversations' => $conversations,
                'quick_replies' => $quickReplies,
                'notifications' => $notifications,
                'unread_count' => $businessPage->unreadMessagesCount(),
                'filter' => $filter,
                'can_reply' => $canReply,
                'is_owner' => $isOwner,
            ]);
        }

        return view('member.business-pages.inbox.index', compact(
            'businessPage',
            'conversations',
            'quickReplies',
            'notifications',
            'filter',
            'canReply',
            'isOwner'
        ));
    }

    public function startConversation(Request $request, BusinessPage $businessPage)
    {
        $member = auth('member')->user();

        if ($businessPage->isOwner($member->id)) {
            return response()->json(['success' => false, 'message' => 'Page owners cannot send customer messages to their own page.'], 403);
        }

        $isFollower = $businessPage->isFollowedBy($member->id);
        $status = $isFollower ? 'active' : 'pending_request';

        $conversation = BusinessConversation::firstOrCreate(
            [
                'business_page_id' => $businessPage->id,
                'customer_id' => $member->id,
            ],
            [
                'status' => $status,
                'is_starred' => false,
                'is_pinned' => false,
                'last_message_at' => now(),
            ]
        );

        $initialMessageText = trim($request->input('message', ''));
        if (filled($initialMessageText)) {
            BusinessMessage::create([
                'business_conversation_id' => $conversation->id,
                'sender_id' => $member->id,
                'sender_type' => 'customer',
                'message' => $initialMessageText,
                'is_read' => false,
            ]);

            $conversation->update(['last_message_at' => now()]);

            BusinessNotification::create([
                'business_page_id' => $businessPage->id,
                'member_id' => $member->id,
                'type' => 'new_message',
                'title' => 'New message from ' . $member->name,
                'data' => [
                    'customer_name' => $member->name,
                    'message_snippet' => Str::limit($initialMessageText, 80),
                    'conversation_id' => $conversation->id,
                ],
                'is_read' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Conversation started with ' . $businessPage->page_name . '!',
            'conversation_id' => $conversation->id,
        ]);
    }

    public function showConversation(Request $request, BusinessPage $businessPage, BusinessConversation $conversation)
    {
        $member = auth('member')->user();

        if ((int) $conversation->business_page_id !== (int) $businessPage->id) {
            return response()->json(['success' => false, 'message' => 'Invalid conversation.'], 422);
        }

        $isTeam = $businessPage->canAccessInbox($member->id);
        $isCustomer = (int) $conversation->customer_id === (int) $member->id;

        if (! $isTeam && ! $isCustomer) {
            return response()->json(['success' => false, 'message' => 'Unauthorized conversation access.'], 403);
        }

        // Mark messages read for business team
        if ($isTeam) {
            BusinessMessage::where('business_conversation_id', $conversation->id)
                ->where('sender_type', 'customer')
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);
        }

        $messages = BusinessMessage::where('business_conversation_id', $conversation->id)
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'conversation' => $conversation->load('customer'),
            'messages' => $messages,
        ]);
    }

    public function sendMessage(Request $request, BusinessPage $businessPage, BusinessConversation $conversation)
    {
        $member = auth('member')->user();

        if ((int) $conversation->business_page_id !== (int) $businessPage->id) {
            return response()->json(['success' => false, 'message' => 'Invalid conversation.'], 422);
        }

        $isTeam = $businessPage->canReplyInInbox($member->id);
        $isCustomer = (int) $conversation->customer_id === (int) $member->id;

        if (! $isTeam && ! $isCustomer) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to send message in this conversation.'], 403);
        }

        $senderType = $isTeam ? 'business' : 'customer';

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:5000', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,zip', 'max:10240', 'required_without:message'],
        ]);

        $attachmentPath = null;
        $attachmentType = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $mime = $file->getMimeType();
            $directory = 'uploads/business_pages/attachments';
            $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
            $filename = sprintf('attach_%d_%d_%s.%s', $member->id, time(), Str::random(6), $ext);

            $attachmentType = str_starts_with($mime, 'image/') ? 'image' : 'document';

            $attachmentPath = app(\App\Http\Controllers\ImageCompressionController::class)->compressAndStore(
                $file,
                $directory,
                'general',
                $filename,
                'public_uploads'
            );
        }

        $msg = BusinessMessage::create([
            'business_conversation_id' => $conversation->id,
            'sender_id' => $member->id,
            'sender_type' => $senderType,
            'message' => filled($validated['message'] ?? null) ? trim($validated['message']) : null,
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
            'is_read' => false,
        ]);

        $conversation->update(['last_message_at' => now()]);

        if ($senderType === 'customer') {
            BusinessNotification::create([
                'business_page_id' => $businessPage->id,
                'member_id' => $member->id,
                'type' => 'new_message',
                'title' => 'New message from ' . $member->name,
                'data' => [
                    'customer_name' => $member->name,
                    'message_snippet' => Str::limit($msg->message ?? 'Attachment', 80),
                    'conversation_id' => $conversation->id,
                ],
                'is_read' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $msg->load('sender'),
        ]);
    }

    public function handleRequest(Request $request, BusinessPage $businessPage, BusinessConversation $conversation)
    {
        $member = auth('member')->user();

        if (! $businessPage->canReplyInInbox($member->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $action = $request->input('action', 'accept');

        if ($action === 'accept') {
            $conversation->update(['status' => 'active']);

            return response()->json(['success' => true, 'message' => 'Message request accepted. You can now chat!']);
        }

        $conversation->update(['status' => 'archived']);

        return response()->json(['success' => true, 'message' => 'Message request ignored.']);
    }

    public function toggleStar(Request $request, BusinessPage $businessPage, BusinessConversation $conversation)
    {
        $member = auth('member')->user();

        if (! $businessPage->canAccessInbox($member->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $newState = ! $conversation->is_starred;
        $conversation->update(['is_starred' => $newState]);

        return response()->json(['success' => true, 'is_starred' => $newState]);
    }

    public function togglePin(Request $request, BusinessPage $businessPage, BusinessConversation $conversation)
    {
        $member = auth('member')->user();

        if (! $businessPage->canAccessInbox($member->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $newState = ! $conversation->is_pinned;
        $conversation->update(['is_pinned' => $newState]);

        return response()->json(['success' => true, 'is_pinned' => $newState]);
    }

    public function updateStatus(Request $request, BusinessPage $businessPage, BusinessConversation $conversation)
    {
        $member = auth('member')->user();

        if (! $businessPage->canAccessInbox($member->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $status = $request->input('status', 'active');
        if (! in_array($status, ['active', 'archived', 'closed'], true)) {
            $status = 'active';
        }

        $conversation->update(['status' => $status]);

        return response()->json(['success' => true, 'status' => $status, 'message' => 'Conversation status updated.']);
    }

    public function storeQuickReply(Request $request, BusinessPage $businessPage)
    {
        $member = auth('member')->user();

        if (! $businessPage->canReplyInInbox($member->id)) {
            return response()->json(['success' => false, 'message' => 'Only page admins or editors can manage quick replies.'], 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'shortcut' => ['nullable', 'string', 'max:50'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        $qr = BusinessQuickReply::create([
            'business_page_id' => $businessPage->id,
            'title' => trim($validated['title']),
            'shortcut' => filled($validated['shortcut'] ?? null) ? trim($validated['shortcut']) : null,
            'message' => trim($validated['message']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Quick Reply saved!',
            'quick_reply' => $qr,
        ]);
    }

    public function destroyQuickReply(Request $request, BusinessPage $businessPage, BusinessQuickReply $quickReply)
    {
        $member = auth('member')->user();

        if (! $businessPage->canReplyInInbox($member->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        if ((int) $quickReply->business_page_id !== (int) $businessPage->id) {
            return response()->json(['success' => false, 'message' => 'Invalid quick reply.'], 422);
        }

        $quickReply->delete();

        return response()->json(['success' => true, 'message' => 'Quick reply deleted.']);
    }

    public function notifications(Request $request, BusinessPage $businessPage)
    {
        $member = auth('member')->user();

        if (! $businessPage->canAccessInbox($member->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $filter = $request->input('filter', 'all');
        $query = BusinessNotification::where('business_page_id', $businessPage->id);

        if ($filter === 'unread') {
            $query->where('is_read', false);
        }

        $list = $query->latest()->take(50)->get();
        $unreadCount = BusinessNotification::where('business_page_id', $businessPage->id)->where('is_read', false)->count();

        return response()->json([
            'success' => true,
            'notifications' => $list,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markNotificationsRead(Request $request, BusinessPage $businessPage)
    {
        $member = auth('member')->user();

        if (! $businessPage->canAccessInbox($member->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        BusinessNotification::where('business_page_id', $businessPage->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true, 'message' => 'All business notifications marked as read.']);
    }

    public function markSingleNotificationRead(Request $request, BusinessPage $businessPage, BusinessNotification $notification)
    {
        $member = auth('member')->user();

        if (! $businessPage->canAccessInbox($member->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        if ((int) $notification->business_page_id !== (int) $businessPage->id) {
            return response()->json(['success' => false, 'message' => 'Invalid notification.'], 422);
        }

        $notification->update(['is_read' => true]);

        $unreadCount = BusinessNotification::where('business_page_id', $businessPage->id)->where('is_read', false)->count();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
            'notification' => $notification,
            'unread_count' => $unreadCount,
        ]);
    }

    public function destroyNotification(Request $request, BusinessPage $businessPage, BusinessNotification $notification)
    {
        $member = auth('member')->user();

        if (! $businessPage->canAccessInbox($member->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        if ((int) $notification->business_page_id !== (int) $businessPage->id) {
            return response()->json(['success' => false, 'message' => 'Invalid notification.'], 422);
        }

        $notification->delete();

        $unreadCount = BusinessNotification::where('business_page_id', $businessPage->id)->where('is_read', false)->count();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted.',
            'unread_count' => $unreadCount,
        ]);
    }

    public function clearAllNotifications(Request $request, BusinessPage $businessPage)
    {
        $member = auth('member')->user();

        if (! $businessPage->canAccessInbox($member->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        BusinessNotification::where('business_page_id', $businessPage->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'All business notifications cleared.',
            'unread_count' => 0,
        ]);
    }
}
