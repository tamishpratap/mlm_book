@if ($activeMember)
    @php
        $activePhoto = $activeMember->profile_photo && file_exists(public_path($activeMember->profile_photo)) ? asset($activeMember->profile_photo) : null;
        $activeInitials = collect(preg_split('/\s+/', trim($activeMember->name)))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') ?: 'M';
    @endphp
    <div class="chat-header">
        <a class="chat-header__user" href="{{ route('member.people.show', $activeMember) }}">
            <div class="chat-header__avatar">
                @if ($activePhoto)
                    <img src="{{ $activePhoto }}" alt="{{ $activeMember->name }}">
                @else
                    <span>{{ $activeInitials }}</span>
                @endif
            </div>
            <div class="chat-header__info">
                <strong>{{ $activeMember->name }}</strong>
                <small>{{ $activeMember->user_id ? '@'.$activeMember->user_id : 'Member' }}</small>
            </div>
        </a>
        <div class="chat-header__actions">
            <a class="member-button member-button--secondary" href="{{ route('member.people.show', $activeMember) }}">
                <i data-lucide="user"></i> View Profile
            </a>
        </div>
    </div>

    <div class="chat-body" id="chatMessagesBody" data-fetch-url="{{ route('member.messages.fetch', $activeMember) }}">
        @include('member.messages.partials.messages_list', ['messages' => $messages, 'currentMember' => $currentMember])
    </div>

    <form class="chat-footer" id="chatSendMessageForm" action="{{ route('member.messages.send', $activeMember) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <label class="chat-file-btn" title="Attach file">
            <i data-lucide="paperclip"></i>
            <input type="file" name="attachment" id="chatAttachmentInput" accept="image/*,.pdf,.doc,.docx" hidden>
        </label>
        <input type="text" name="message" id="chatMessageInput" class="chat-input" placeholder="Type a message..." autocomplete="off">
        <button type="submit" class="chat-send-btn" title="Send Message">
            <i data-lucide="send"></i>
        </button>
    </form>
@else
    <div class="chat-empty-state">
        <i data-lucide="message-square" style="width: 64px; height: 64px; opacity: 0.3; margin-bottom: 12px;"></i>
        <h3>Your Messages</h3>
        <p>Select a friend or member from the left menu to start chatting.</p>
    </div>
@endif
