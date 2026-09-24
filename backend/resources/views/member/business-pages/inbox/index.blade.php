@extends('member.layouts.app')

@section('title', 'Business Inbox - ' . $businessPage->page_name)

@section('content')
<div class="biz-page">
    <header class="biz-header" style="margin-bottom: 16px;">
        <div class="biz-header__info">
            <h1>
                <i data-lucide="message-square" aria-hidden="true" style="color: #4f7df3;"></i> Business Inbox - {{ $businessPage->page_name }}
                @if ($businessPage->unreadMessagesCount() > 0)
                    <span class="biz-badge biz-badge--category" style="background: #e53e3e; color: #fff; vertical-align: middle; margin-left: 8px;">
                        {{ $businessPage->unreadMessagesCount() }} New
                    </span>
                @endif
            </h1>
            <p>Manage customer conversations, saved quick replies, and business notifications.</p>
        </div>
        <div class="biz-header__actions">
            <button type="button" class="member-button member-button--secondary" onclick="openQuickRepliesModal()">
                <i data-lucide="zap" aria-hidden="true"></i> Quick Replies
            </button>
            <button type="button" class="member-button member-button--secondary" onclick="toggleNotificationsDrawer()">
                <i data-lucide="bell" aria-hidden="true"></i> Notifications
                @if ($businessPage->unreadNotificationsCount() > 0)
                    <span style="background: #e53e3e; color: #fff; border-radius: 50%; padding: 2px 6px; font-size: 11px;">{{ $businessPage->unreadNotificationsCount() }}</span>
                @endif
            </button>
            <a href="{{ route('member.business-pages.show', $businessPage) }}" class="member-button member-button--primary">
                <i data-lucide="arrow-left" aria-hidden="true"></i> Back to Profile
            </a>
        </div>
    </header>

    <!-- Messenger Split 3-Column Layout -->
    <div style="display: grid; grid-template-columns: 320px 1fr 280px; gap: 16px; min-height: 600px; align-items: start;" id="bizInboxLayout">
        
        <!-- Left Panel: Conversations List -->
        <div class="biz-info-card" style="padding: 14px; display: flex; flex-direction: column; gap: 12px; height: 600px; overflow-y: auto;">
            <!-- Filter Tabs -->
            <div style="display: flex; gap: 6px; overflow-x: auto; padding-bottom: 4px; border-bottom: 1px solid #e7ecf4;">
                <a href="{{ route('member.business-pages.inbox.index', [$businessPage, 'filter' => 'all']) }}" class="biz-nav-tab {{ $filter === 'all' ? 'is-active' : '' }}" style="padding: 6px 10px; font-size: 12px;">All</a>
                <a href="{{ route('member.business-pages.inbox.index', [$businessPage, 'filter' => 'unread']) }}" class="biz-nav-tab {{ $filter === 'unread' ? 'is-active' : '' }}" style="padding: 6px 10px; font-size: 12px;">Unread</a>
                <a href="{{ route('member.business-pages.inbox.index', [$businessPage, 'filter' => 'starred']) }}" class="biz-nav-tab {{ $filter === 'starred' ? 'is-active' : '' }}" style="padding: 6px 10px; font-size: 12px;">Starred</a>
                <a href="{{ route('member.business-pages.inbox.index', [$businessPage, 'filter' => 'requests']) }}" class="biz-nav-tab {{ $filter === 'requests' ? 'is-active' : '' }}" style="padding: 6px 10px; font-size: 12px;">Requests</a>
                <a href="{{ route('member.business-pages.inbox.index', [$businessPage, 'filter' => 'archived']) }}" class="biz-nav-tab {{ $filter === 'archived' ? 'is-active' : '' }}" style="padding: 6px 10px; font-size: 12px;">Archived</a>
            </div>

            <!-- Search Conversations Bar -->
            <form method="GET" action="{{ route('member.business-pages.inbox.index', $businessPage) }}">
                <input type="hidden" name="filter" value="{{ $filter }}">
                <input type="text" name="q" value="{{ request('q') }}" class="biz-search-input" style="font-size: 13px; padding: 8px 12px;" placeholder="Search customer name or message...">
            </form>

            <!-- Conversations Items List -->
            <div style="display: flex; flex-direction: column; gap: 8px; flex: 1;" id="conversationsListContainer">
                @forelse ($conversations as $conv)
                    @php
                        $unread = $conv->unreadCountForBusiness();
                        $lastMsg = $conv->latestMessage;
                    @endphp
                    <div id="convItem-{{ $conv->id }}" class="biz-conv-item" onclick="loadBizConversation({{ $conv->id }})" style="padding: 10px 12px; border-radius: 12px; border: 1px solid #e7ecf4; cursor: pointer; transition: all 0.2s; background: {{ $unread > 0 ? '#edf3ff' : '#fff' }};">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                            <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                                <img src="{{ $conv->customer->profile_photo ? asset($conv->customer->profile_photo) : asset('member_assets/images/dashboard/image/profile.png') }}" alt="{{ $conv->customer->name }}" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover;">
                                <div style="min-width: 0;">
                                    <strong style="font-size: 13.5px; color: #1d2738; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $conv->customer->name }}</strong>
                                    <span style="font-size: 11.5px; color: #687386; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        {{ $lastMsg ? ($lastMsg->sender_type === 'business' ? 'You: ' : '') . Str::limit($lastMsg->message ?? 'Attachment', 25) : 'No messages' }}
                                    </span>
                                </div>
                            </div>
                            <div style="text-align: right; flex-shrink: 0;">
                                @if ($unread > 0)
                                    <span style="background: #4f7df3; color: #fff; font-size: 11px; font-weight: 700; border-radius: 50%; padding: 2px 6px; display: inline-block;">{{ $unread }}</span>
                                @endif
                                <span style="font-size: 10.5px; color: #98a2b3; display: block; margin-top: 2px;">{{ $conv->last_message_at ? $conv->last_message_at->diffForHumans(null, true, true) : '' }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div style="text-align: center; padding: 30px 10px; color: #98a2b3;">
                        <i data-lucide="inbox" style="width: 24px; height: 24px; margin-bottom: 6px;"></i>
                        <p style="font-size: 13px; margin: 0;">No conversations found in {{ $filter }}.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Middle Panel: Chat Thread Window -->
        <div class="biz-info-card" style="padding: 0; display: flex; flex-direction: column; height: 600px; overflow: hidden; background: #fff;">
            <!-- Active Conversation Header -->
            <div id="chatThreadHeader" style="padding: 14px 18px; border-bottom: 1px solid #e7ecf4; display: flex; align-items: center; justify-content: space-between; background: #fafbfc;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div id="activeCustomerAvatar" style="width: 40px; height: 40px; border-radius: 50%; background: #edf3ff; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #4f7df3;">
                        <i data-lucide="user"></i>
                    </div>
                    <div>
                        <strong id="activeCustomerName" style="font-size: 15px; color: #1d2738; display: block;">Select a Conversation</strong>
                        <span id="activeCustomerUsername" style="font-size: 12px; color: #98a2b3;">Click any conversation on the left to start chatting</span>
                    </div>
                </div>

                <div id="activeConvActions" style="display: none; align-items: center; gap: 8px;">
                    <button type="button" class="mini-button" onclick="toggleActiveStar()" id="starConvBtn" title="Star conversation"><i data-lucide="star"></i></button>
                    <button type="button" class="mini-button" onclick="toggleActivePin()" id="pinConvBtn" title="Pin conversation"><i data-lucide="pin"></i></button>
                    <button type="button" class="mini-button" onclick="archiveActiveConv()" title="Archive conversation"><i data-lucide="archive"></i></button>
                </div>
            </div>

            <!-- Messages Thread Scroll Box -->
            <div id="chatMessagesThread" style="flex: 1; padding: 18px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; background: #f8fafc;">
                <div class="biz-phase-placeholder" style="margin: auto;">
                    <div class="biz-phase-placeholder__icon"><i data-lucide="message-square" style="width: 28px; height: 28px;"></i></div>
                    <h3>No Conversation Selected</h3>
                    <p>Select a customer conversation from the list to view chat history and respond!</p>
                </div>
            </div>

            <!-- Message Request Actions Bar (For pending requests) -->
            <div id="messageRequestBar" style="display: none; padding: 10px 18px; background: rgba(247, 185, 64, 0.1); border-top: 1px solid rgba(247, 185, 64, 0.3); align-items: center; justify-content: space-between;">
                <span style="font-size: 13px; color: #b7791f; font-weight: 600;"><i data-lucide="help-circle"></i> Message Request from Non-Follower</span>
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="member-button member-button--primary" style="padding: 4px 10px; font-size: 12px;" onclick="handleActiveRequest('accept')">Accept</button>
                    <button type="button" class="member-button member-button--secondary" style="padding: 4px 10px; font-size: 12px;" onclick="handleActiveRequest('reject')">Ignore</button>
                </div>
            </div>

            <!-- Message Composer Box -->
            <form id="chatMessageForm" method="POST" style="padding: 12px 18px; border-top: 1px solid #e7ecf4; background: #fff;" enctype="multipart/form-data">
                @csrf
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <textarea name="message" id="chatMessageInput" rows="2" class="biz-search-input" style="height: auto; padding: 10px; font-size: 13.5px;" placeholder="Type your response... (Press Enter or click Send)" disabled></textarea>

                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <!-- File Attachment Button -->
                            <label class="mini-button" style="cursor: pointer;" title="Attach photo or document">
                                <i data-lucide="paperclip"></i>
                                <input type="file" name="attachment" id="chatAttachmentInput" style="display: none;" onchange="updateAttachmentLabel(this)">
                            </label>
                            <span id="chatAttachmentName" style="font-size: 11.5px; color: #687386;"></span>

                            <!-- Quick Reply Picker Dropdown -->
                            @if ($quickReplies->count() > 0)
                                <select id="quickReplyPicker" class="biz-filter-select" style="font-size: 12px; padding: 4px 8px;" onchange="applyQuickReply(this.value)">
                                    <option value="">Insert Quick Reply...</option>
                                    @foreach ($quickReplies as $qr)
                                        <option value="{{ $qr->message }}">{{ $qr->title }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        <button type="submit" id="sendMsgBtn" class="member-button member-button--primary" style="padding: 6px 14px; font-size: 13px;" disabled>
                            <i data-lucide="send"></i> Send
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Right Panel: Customer Profile & Quick Replies Drawer -->
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <!-- Customer Summary Card -->
            <div class="biz-info-card" id="customerProfileDrawer" style="padding: 16px;">
                <h3 class="biz-info-card__title" style="font-size: 14px;"><i data-lucide="user" style="color: #4f7df3;"></i> Customer Details</h3>
                <div id="customerProfileContent" style="text-align: center; padding: 10px 0;">
                    <p style="font-size: 13px; color: #98a2b3; font-style: italic; margin: 0;">Select a conversation to view customer details.</p>
                </div>
            </div>

            <!-- Saved Quick Replies List Box -->
            <div class="biz-info-card" style="padding: 16px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <h3 class="biz-info-card__title" style="font-size: 14px; margin: 0;"><i data-lucide="zap" style="color: #f7b940;"></i> Quick Replies</h3>
                    @if ($canReply)
                        <button type="button" class="mini-button" onclick="openQuickRepliesModal()" style="color: #4f7df3;"><i data-lucide="plus"></i> Add</button>
                    @endif
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px; max-height: 220px; overflow-y: auto;">
                    @forelse ($quickReplies as $qr)
                        <div style="padding: 8px 10px; border-radius: 8px; background: #f8fafc; border: 1px solid #e7ecf4; font-size: 12px; cursor: pointer;" onclick="applyQuickReply('{{ addslashes($qr->message) }}')">
                            <strong style="color: #1d2738; display: block;">{{ $qr->title }}</strong>
                            <span style="color: #687386; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $qr->message }}</span>
                        </div>
                    @empty
                        <p style="font-size: 12px; color: #98a2b3; font-style: italic; margin: 0;">No quick replies created yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Replies Manager Modal -->
<div class="biz-modal-overlay" id="bizQuickRepliesModal" hidden>
    <div class="biz-modal" role="dialog" aria-labelledby="quickRepliesTitle" aria-modal="true">
        <div class="biz-modal__header">
            <h3 id="quickRepliesTitle"><i data-lucide="zap" style="color: #f7b940;"></i> Saved Quick Replies</h3>
            <button type="button" class="icon-button" onclick="closeQuickRepliesModal()"><i data-lucide="x"></i></button>
        </div>

        <form id="bizQuickReplyForm" method="POST" action="{{ route('member.business-pages.inbox.quick-replies.store', $businessPage) }}">
            @csrf
            <div class="biz-modal__body">
                <div class="form-group">
                    <label style="font-size: 13px; font-weight: 700; color: #1d2738; margin-bottom: 6px; display: block;">Title / Label</label>
                    <input type="text" name="title" class="biz-search-input" placeholder="e.g. Greeting, Business Hours, Pricing FAQ..." required maxlength="255">
                </div>

                <div class="form-group">
                    <label style="font-size: 13px; font-weight: 700; color: #1d2738; margin-bottom: 6px; display: block;">Shortcut (Optional)</label>
                    <input type="text" name="shortcut" class="biz-search-input" placeholder="e.g. /greeting, /pricing" maxlength="50">
                </div>

                <div class="form-group">
                    <label style="font-size: 13px; font-weight: 700; color: #1d2738; margin-bottom: 6px; display: block;">Message Template</label>
                    <textarea name="message" rows="4" class="biz-search-input" style="height: auto; padding: 10px;" placeholder="Type saved response text..." required maxlength="3000"></textarea>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 10px;">
                    <button type="button" class="member-button member-button--secondary" onclick="closeQuickRepliesModal()">Cancel</button>
                    <button type="submit" class="member-button member-button--primary">Save Quick Reply</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
let currentConvId = null;

function loadBizConversation(convId) {
    currentConvId = convId;
    const url = '{{ url("member/business-pages/" . $businessPage->slug . "/inbox/conversations") }}/' + convId;

    fetch(url, {
        headers: { 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) { alert(data.message); return; }

        const conv = data.conversation;
        const customer = conv.customer;
        const messages = data.messages;

        // Update Header
        document.getElementById('activeCustomerName').innerText = customer.name;
        document.getElementById('activeCustomerUsername').innerText = '@' + (customer.user_id || 'customer');
        document.getElementById('activeCustomerAvatar').innerHTML = customer.profile_photo 
            ? `<img src="${customer.profile_photo}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">`
            : `<span>${customer.name.substring(0, 1)}</span>`;
        document.getElementById('activeConvActions').style.display = 'flex';

        // Update Request Bar
        const reqBar = document.getElementById('messageRequestBar');
        if (conv.status === 'pending_request') {
            reqBar.style.display = 'flex';
        } else {
            reqBar.style.display = 'none';
        }

        // Enable Composer
        document.getElementById('chatMessageInput').disabled = false;
        document.getElementById('sendMsgBtn').disabled = false;
        const form = document.getElementById('chatMessageForm');
        form.action = '{{ url("member/business-pages/" . $businessPage->slug . "/inbox/conversations") }}/' + convId + '/messages';

        // Render Messages
        const thread = document.getElementById('chatMessagesThread');
        thread.innerHTML = '';
        messages.forEach(msg => {
            const isBiz = msg.sender_type === 'business';
            const msgDiv = document.createElement('div');
            msgDiv.style.alignSelf = isBiz ? 'flex-end' : 'flex-start';
            msgDiv.style.maxWidth = '75%';

            let attachHtml = '';
            if (msg.attachment_path) {
                if (msg.attachment_type === 'image') {
                    attachHtml = `<img src="/${msg.attachment_path}" style="max-width:200px;border-radius:10px;margin-top:6px;">`;
                } else {
                    attachHtml = `<a href="/${msg.attachment_path}" target="_blank" style="color:#4f7df3;font-size:12px;display:block;margin-top:4px;"><i data-lucide="file-text"></i> Attachment</a>`;
                }
            }

            msgDiv.innerHTML = `
                <div style="padding: 10px 14px; border-radius: 14px; background: ${isBiz ? 'linear-gradient(135deg, #4f7df3 0%, #8a2be2 100%)' : '#fff'}; color: ${isBiz ? '#fff' : '#1d2738'}; border: ${isBiz ? 'none' : '1px solid #e7ecf4'}; font-size: 13.5px;">
                    ${msg.message ? `<p style="margin:0;white-space:pre-line;">${msg.message}</p>` : ''}
                    ${attachHtml}
                    <span style="font-size: 10px; opacity: 0.7; display: block; text-align: right; margin-top: 4px;">${new Date(msg.created_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</span>
                </div>
            `;
            thread.appendChild(msgDiv);
        });

        thread.scrollTop = thread.scrollHeight;
        if (window.lucide) { window.lucide.createIcons(); }

        // Update Right Drawer
        document.getElementById('customerProfileContent').innerHTML = `
            <img src="${customer.profile_photo || '/member_assets/images/dashboard/image/profile.png'}" style="width:60px;height:60px;border-radius:50%;object-fit:cover;margin-bottom:8px;">
            <strong style="font-size:14.5px;color:#1d2738;display:block;">${customer.name}</strong>
            <span style="font-size:12px;color:#98a2b3;">@${customer.user_id || 'customer'}</span>
            <div style="margin-top:10px;padding-top:10px;border-top:1px solid #e7ecf4;font-size:12px;color:#687386;text-align:left;">
                <p style="margin:4px 0;"><strong>Status:</strong> ${conv.status}</p>
                <p style="margin:4px 0;"><strong>Customer ID:</strong> ${customer.id}</p>
            </div>
        `;
    });
}

document.getElementById('chatMessageForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    if (!currentConvId) return;
    const form = this;
    const btn = document.getElementById('sendMsgBtn');
    btn.disabled = true;

    fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        if (data.success) {
            form.reset();
            document.getElementById('chatAttachmentName').innerText = '';
            loadBizConversation(currentConvId);
        } else {
            alert(data.message || 'Failed to send message.');
        }
    });
});

function updateAttachmentLabel(input) {
    const label = document.getElementById('chatAttachmentName');
    if (input.files && input.files[0]) {
        label.innerText = input.files[0].name;
    } else {
        label.innerText = '';
    }
}

function applyQuickReply(text) {
    if (!text) return;
    const input = document.getElementById('chatMessageInput');
    input.value = text;
}

function openQuickRepliesModal() {
    document.getElementById('bizQuickRepliesModal')?.removeAttribute('hidden');
}
function closeQuickRepliesModal() {
    document.getElementById('bizQuickRepliesModal')?.setAttribute('hidden', 'true');
}

document.getElementById('bizQuickReplyForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            closeQuickRepliesModal();
            location.reload();
        }
    });
});

function toggleActiveStar() {
    if (!currentConvId) return;
    const url = '{{ url("member/business-pages/" . $businessPage->slug . "/inbox/conversations") }}/' + currentConvId + '/star';
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        alert(data.is_starred ? 'Conversation starred.' : 'Star removed.');
    });
}

function toggleActivePin() {
    if (!currentConvId) return;
    const url = '{{ url("member/business-pages/" . $businessPage->slug . "/inbox/conversations") }}/' + currentConvId + '/pin';
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        alert(data.is_pinned ? 'Conversation pinned to top.' : 'Pin removed.');
    });
}

function archiveActiveConv() {
    if (!currentConvId) return;
    const url = '{{ url("member/business-pages/" . $businessPage->slug . "/inbox/conversations") }}/' + currentConvId + '/status';
    fetch(url, {
        method: 'PUT',
        body: JSON.stringify({ status: 'archived' }),
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        alert('Conversation archived.');
        location.reload();
    });
}

function handleActiveRequest(action) {
    if (!currentConvId) return;
    const url = '{{ url("member/business-pages/" . $businessPage->slug . "/inbox/conversations") }}/' + currentConvId + '/request';
    fetch(url, {
        method: 'POST',
        body: JSON.stringify({ action: action }),
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        loadBizConversation(currentConvId);
    });
}
</script>
@endsection
