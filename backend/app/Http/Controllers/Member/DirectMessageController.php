<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\DirectMessage;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DirectMessageController extends Controller
{
    public function index(Request $request, $member = null)
    {
        $currentMember = auth('member')->user();

        $activeMember = $member instanceof Member ? $member : ($member ? Member::find((int) $member) : null);

        // Get list of all conversation partners (people messaged or who messaged them)
        $sentIds = DirectMessage::where('sender_id', $currentMember->id)->pluck('receiver_id')->all();
        $receivedIds = DirectMessage::where('receiver_id', $currentMember->id)->pluck('sender_id')->all();

        $allPartnerIds = collect(array_merge($sentIds, $receivedIds));
        if ($activeMember && $activeMember->id) {
            $allPartnerIds->push($activeMember->id);
        }

        $allPartnerIds = $allPartnerIds
            ->unique()
            ->filter(fn ($id) => (int) $id !== (int) $currentMember->id)
            ->values();

        $conversations = Member::query()
            ->whereIn('id', $allPartnerIds)
            ->get()
            ->map(function ($partner) use ($currentMember) {
                $latestMessage = DirectMessage::between($currentMember->id, $partner->id)
                    ->latest()
                    ->first();

                $unreadCount = DirectMessage::query()
                    ->where('sender_id', $partner->id)
                    ->where('receiver_id', $currentMember->id)
                    ->where('is_read', false)
                    ->count();

                return (object) [
                    'member' => $partner,
                    'latestMessage' => $latestMessage,
                    'unreadCount' => $unreadCount,
                    'lastActiveAt' => $latestMessage?->created_at ?? $partner->created_at,
                ];
            })
            ->sortByDesc('lastActiveAt')
            ->values();

        if (! $activeMember && $conversations->isNotEmpty()) {
            $activeMember = $conversations->first()->member;
        }

        $messages = collect();
        if ($activeMember) {
            $messages = DirectMessage::between($currentMember->id, $activeMember->id)
                ->with(['sender', 'receiver'])
                ->orderBy('created_at', 'asc')
                ->get();

            // Mark unread messages as read
            DirectMessage::query()
                ->where('sender_id', $activeMember->id)
                ->where('receiver_id', $currentMember->id)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);
        }

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'conversations' => $conversations,
                'active_member' => $activeMember,
                'active_member_id' => $activeMember?->id,
                'messages' => $messages->map(function ($msg) use ($currentMember) {
                    $isMine = (int) $msg->sender_id === (int) $currentMember->id;

                    return [
                        'id' => $msg->id,
                        'sender_id' => $msg->sender_id,
                        'receiver_id' => $msg->receiver_id,
                        'message' => $msg->message,
                        'attachment' => $msg->attachment ? asset($msg->attachment) : null,
                        'is_mine' => $isMine,
                        'time' => $msg->created_at->format('h:i A'),
                        'date' => $msg->created_at->format('M d, Y'),
                        'created_at' => $msg->created_at,
                    ];
                }),
                'unread_total_count' => DirectMessage::unread($currentMember->id)->count(),
            ]);
        }

        return view('member.messages.index', compact(
            'currentMember',
            'conversations',
            'activeMember',
            'messages',
        ));
    }

    public function chat($member)
    {
        return $this->index(request(), $member);
    }

    public function fetchMessages($member)
    {
        $currentMember = auth('member')->user();
        $targetMember = $member instanceof Member ? $member : Member::findOrFail((int) $member);

        $messages = DirectMessage::between($currentMember->id, $targetMember->id)
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark unread messages as read
        DirectMessage::query()
            ->where('sender_id', $targetMember->id)
            ->where('receiver_id', $currentMember->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'messages' => $messages->map(function ($msg) use ($currentMember) {
                $isMine = (int) $msg->sender_id === (int) $currentMember->id;

                return [
                    'id' => $msg->id,
                    'sender_id' => $msg->sender_id,
                    'receiver_id' => $msg->receiver_id,
                    'message' => $msg->message,
                    'attachment' => $msg->attachment ? asset($msg->attachment) : null,
                    'is_mine' => $isMine,
                    'time' => $msg->created_at->format('h:i A'),
                    'date' => $msg->created_at->format('M d, Y'),
                    'created_at' => $msg->created_at,
                ];
            }),
            'html' => view('member.messages.partials.messages_list', [
                'messages' => $messages,
                'currentMember' => $currentMember,
            ])->render(),
        ]);
    }

    public function sendMessage(Request $request, $member)
    {
        $currentMember = auth('member')->user();
        $targetMember = $member instanceof Member ? $member : Member::findOrFail((int) $member);

        if ((int) $currentMember->id === (int) $targetMember->id) {
            return response()->json(['success' => false, 'message' => 'Cannot message yourself.'], 422);
        }

        $request->validate([
            'message' => 'nullable|string|max:5000',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,webp,gif,pdf,doc,docx|max:10240',
        ]);

        if (blank($request->message) && ! $request->hasFile('attachment')) {
            return response()->json(['success' => false, 'message' => 'Please type a message or upload an attachment.'], 422);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time().'_'.Str::random(8).'.'.$file->getClientOriginalExtension();

            $attachmentPath = app(\App\Http\Controllers\ImageCompressionController::class)->compressAndStore(
                $file,
                'uploads/messages',
                'general',
                $filename,
                'public_uploads'
            );
        }

        $directMessage = DirectMessage::create([
            'sender_id' => $currentMember->id,
            'receiver_id' => $targetMember->id,
            'message' => $request->message,
            'attachment' => $attachmentPath,
            'is_read' => false,
        ]);

        $targetMember->notify(new \App\Notifications\DirectMessageNotification($currentMember, $directMessage));

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully.',
                'data' => [
                    'id' => $directMessage->id,
                    'sender_id' => $directMessage->sender_id,
                    'receiver_id' => $directMessage->receiver_id,
                    'message' => $directMessage->message,
                    'attachment' => $directMessage->attachment ? asset($directMessage->attachment) : null,
                    'is_mine' => true,
                    'time' => $directMessage->created_at->format('h:i A'),
                    'date' => $directMessage->created_at->format('M d, Y'),
                    'created_at' => $directMessage->created_at,
                ],
            ]);
        }

        return back()->with('success', 'Message sent successfully.');
    }
}
