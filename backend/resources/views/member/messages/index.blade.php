@extends('member.layouts.app')

@section('title', 'Messages')
@section('body-class', 'messages-page')

@push('styles')
<style>
.messages-container {
    display: grid;
    grid-template-columns: 320px 1fr;
    height: calc(100vh - 90px);
    max-width: 1280px;
    margin: 16px auto;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    overflow: hidden;
    border: 1px solid #e5e7eb;
}

@media (max-width: 768px) {
    .messages-container {
        grid-template-columns: 1fr;
        height: calc(100vh - 120px);
        margin: 8px;
    }
    .messages-sidebar {
        display: {{ $activeMember ? 'none' : 'block' }};
    }
    .messages-main {
        display: {{ $activeMember ? 'block' : 'none' }};
    }
}

.messages-sidebar {
    border-right: 1px solid #f0f0f0;
    display: flex;
    flex-direction: column;
    background: #fafafa;
}

.messages-sidebar__header {
    padding: 16px;
    border-bottom: 1px solid #eeeeee;
    background: #ffffff;
}

.messages-sidebar__header h2 {
    font-size: 20px;
    font-weight: 700;
    color: #111827;
    margin: 0;
}

.messages-sidebar__list {
    flex: 1;
    overflow-y: auto;
    padding: 8px;
}

.conversation-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 8px;
    text-decoration: none;
    color: inherit;
    transition: background 0.15s ease;
    margin-bottom: 4px;
    position: relative;
}

.conversation-item:hover, .conversation-item.is-active {
    background: #eef2ff;
}

.conversation-item__avatar {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    background: #6366f1;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 16px;
    overflow: hidden;
    flex-shrink: 0;
}

.conversation-item__avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.conversation-item__details {
    flex: 1;
    min-width: 0;
}

.conversation-item__name {
    font-weight: 600;
    font-size: 15px;
    color: #1f2937;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.conversation-item__preview {
    font-size: 13px;
    color: #6b7280;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 2px;
}

.conversation-item__badge {
    background: #ef4444;
    color: white;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 10px;
    flex-shrink: 0;
}

.messages-main {
    display: flex;
    flex-direction: column;
    height: 100%;
    background: #ffffff;
}

.chat-header {
    padding: 14px 20px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #ffffff;
}

.chat-header__user {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: inherit;
}

.chat-header__avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #6366f1;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    overflow: hidden;
}

.chat-header__avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.chat-header__info strong {
    display: block;
    font-size: 16px;
    color: #111827;
}

.chat-header__info small {
    color: #6b7280;
    font-size: 12px;
}

.chat-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: #f9fafb;
}

.chat-bubble-wrapper {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    max-width: 75%;
}

.chat-bubble-wrapper--mine {
    align-self: flex-end;
    flex-direction: row-reverse;
}

.chat-bubble-wrapper--other {
    align-self: flex-start;
}

.chat-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #6366f1;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 600;
    overflow: hidden;
    flex-shrink: 0;
}

.chat-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.chat-bubble {
    padding: 10px 14px;
    border-radius: 16px;
    font-size: 14px;
    line-height: 1.45;
    position: relative;
}

.chat-bubble-wrapper--mine .chat-bubble {
    background: #4f46e5;
    color: #ffffff;
    border-bottom-right-radius: 4px;
}

.chat-bubble-wrapper--other .chat-bubble {
    background: #ffffff;
    color: #1f2937;
    border-bottom-left-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    border: 1px solid #e5e7eb;
}

.chat-bubble__time {
    display: block;
    font-size: 10px;
    margin-top: 4px;
    opacity: 0.75;
    text-align: right;
}

.chat-footer {
    padding: 12px 16px;
    border-top: 1px solid #f0f0f0;
    display: flex;
    align-items: center;
    gap: 10px;
    background: #ffffff;
}

.chat-file-btn {
    color: #6b7280;
    cursor: pointer;
    padding: 8px;
    border-radius: 50%;
    transition: background 0.15s;
}

.chat-file-btn:hover {
    background: #f3f4f6;
    color: #4f46e5;
}

.chat-input {
    flex: 1;
    border: 1px solid #d1d5db;
    border-radius: 20px;
    padding: 10px 16px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.15s;
}

.chat-input:focus {
    border-color: #4f46e5;
}

.chat-send-btn {
    background: #4f46e5;
    color: white;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.15s ease;
}

.chat-send-btn:hover {
    background: #4338ca;
}

.chat-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #6b7280;
    text-align: center;
    padding: 20px;
}

.chat-empty-thread {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    color: #9ca3af;
    text-align: center;
}
</style>
@endpush

@section('content')
<div class="messages-container">
    <aside class="messages-sidebar">
        <div class="messages-sidebar__header">
            <h2>Messages</h2>
        </div>
        <div class="messages-sidebar__list">
            @forelse ($conversations as $conv)
                @php
                    $pMember = $conv->member;
                    $pPhoto = $pMember->profile_photo && file_exists(public_path($pMember->profile_photo)) ? asset($pMember->profile_photo) : null;
                    $pInitials = collect(preg_split('/\s+/', trim($pMember->name)))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') ?: 'M';
                    $isActive = $activeMember && $activeMember->id === $pMember->id;
                @endphp
                <a class="conversation-item {{ $isActive ? 'is-active' : '' }}" href="{{ route('member.messages.chat', $pMember) }}">
                    <div class="conversation-item__avatar">
                        @if ($pPhoto)
                            <img src="{{ $pPhoto }}" alt="{{ $pMember->name }}">
                        @else
                            <span>{{ $pInitials }}</span>
                        @endif
                    </div>
                    <div class="conversation-item__details">
                        <div class="conversation-item__name">{{ $pMember->name }}</div>
                        <div class="conversation-item__preview">
                            @if ($conv->latestMessage)
                                {{ Str::limit($conv->latestMessage->message ?: 'Attachment', 30) }}
                            @else
                                Start conversation
                            @endif
                        </div>
                    </div>
                    @if ($conv->unreadCount > 0)
                        <span class="conversation-item__badge">{{ $conv->unreadCount }}</span>
                    @endif
                </a>
            @empty
                <div style="padding: 24px 16px; text-align: center; color: #9ca3af; font-size: 14px;">
                    No friends or active chats yet. Add friends to start messaging!
                </div>
            @endforelse
        </div>
    </aside>

    <main class="messages-main" id="chatThreadContainer">
        @include('member.messages.partials.chat_thread', [
            'activeMember' => $activeMember,
            'messages' => $messages,
            'currentMember' => $currentMember,
        ])
    </main>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chatBody = document.getElementById('chatMessagesBody');
    if (chatBody) {
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    // Auto-scroll function
    function scrollToBottom() {
        if (chatBody) {
            chatBody.scrollTop = chatBody.scrollHeight;
        }
    }

    // AJAX Message Submit
    const sendForm = document.getElementById('chatSendMessageForm');
    if (sendForm) {
        sendForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(sendForm);
            const messageInput = document.getElementById('chatMessageInput');
            const attachmentInput = document.getElementById('chatAttachmentInput');

            if (!messageInput.value.trim() && (!attachmentInput.files || attachmentInput.files.length === 0)) {
                return;
            }

            fetch(sendForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    messageInput.value = '';
                    if (attachmentInput) attachmentInput.value = '';
                    fetchLatestMessages();
                }
            })
            .catch(err => console.error('Error sending message:', err));
        });
    }

    // Live Polling every 3 seconds for new messages
    function fetchLatestMessages() {
        if (!chatBody) return;
        const fetchUrl = chatBody.getAttribute('data-fetch-url');
        if (!fetchUrl) return;

        fetch(fetchUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.html) {
                const isAtBottom = chatBody.scrollHeight - chatBody.scrollTop <= chatBody.clientHeight + 100;
                chatBody.innerHTML = data.html;
                if (window.lucide) window.lucide.createIcons();
                if (isAtBottom) scrollToBottom();
            }
        })
        .catch(err => console.error('Error fetching messages:', err));
    }

    if (chatBody) {
        setInterval(fetchLatestMessages, 3000);
    }
});
</script>
@endpush
