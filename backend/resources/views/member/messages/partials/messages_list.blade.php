@forelse ($messages as $msg)
    @php
        $isMine = $msg->sender_id === $currentMember->id;
        $sender = $msg->sender;
        $avatar =
            $sender->profile_photo && file_exists(public_path($sender->profile_photo))
                ? asset($sender->profile_photo)
                : null;
        $initials =
            collect(preg_split('/\s+/', trim($sender->name)))
                ->take(2)
                ->map(fn($p) => mb_substr($p, 0, 1))
                ->implode('') ?:
            'M';
    @endphp
    <div class="chat-bubble-wrapper {{ $isMine ? 'chat-bubble-wrapper--mine' : 'chat-bubble-wrapper--other' }}">
        @if (!$isMine)
            <div class="chat-avatar">
                @if ($avatar)
                    <img src="{{ $avatar }}" alt="{{ $sender->name }}">
                @else
                    <span>{{ $initials }}</span>
                @endif
            </div>
        @endif
        <div class="chat-bubble">
            @if ($msg->message)
                <p class="chat-bubble__text">{{ $msg->message }}</p>
            @endif
            @if ($msg->attachment)
                <div class="chat-bubble__attachment">
                    @php
                        $ext = pathinfo($msg->attachment, PATHINFO_EXTENSION);
                        $isImage = in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                    @endphp
                    @if ($isImage)
                        <a href="{{ asset($msg->attachment) }}" target="_blank">
                            <img src="{{ asset($msg->attachment) }}" alt="Attachment"
                                style="max-width: 240px; border-radius: 8px; margin-top: 6px;">
                        </a>
                    @else
                        <a href="{{ asset($msg->attachment) }}" target="_blank" class="attachment-file-link">
                            <i data-lucide="paperclip"></i> View Attachment
                        </a>
                    @endif
                </div>
            @endif
            <span class="chat-bubble__time">{{ $msg->created_at->format('h:i A') }}</span>
        </div>
    </div>
@empty
    <div class="chat-empty-thread">
        <i data-lucide="messages-square" style="width: 48px; height: 48px; opacity: 0.4;"></i>
        <p>No messages yet. Say hello to start the conversation!</p>
    </div>
@endforelse
