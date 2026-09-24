@php
    $hasLogo = $community->logo && file_exists(public_path($community->logo));
    $hasCover = $community->cover_photo && file_exists(public_path($community->cover_photo));
    $initials =
        collect(preg_split('/\s+/', trim($community->name)))
            ->filter()
            ->take(2)
            ->map(fn($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
            ->implode('') ?:
        'C';
    $communityUrl = route('member.community.show', $community->slug);
    $encodedUrl = urlencode($communityUrl);
    $encodedTitle = urlencode('Join ' . $community->name . ' community on MLM Book!');
    $currentMember = auth('member')->user();
    $isAdmin = $currentMember && $community->isAdmin($currentMember->id);
@endphp

<div class="modal fade community-share-modal" id="inviteShareModal" tabindex="-1"
    aria-labelledby="shareModalTitle-{{ $community->id }}" aria-hidden="true"
    data-community-share-modal-id="{{ $community->id }}" style="display: none;" hidden>
    <div class="modal-dialog modal-dialog-centered" style="max-width: 680px; width: 100%; margin: auto;">
        <div class="modal-content community-share-modal__card"
            style="position: relative; z-index: 10; width: 100%; border: none; background: #ffffff; border-radius: 18px; box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25); box-sizing: border-box; overflow: hidden; margin: auto;">
            <button type="button" class="community-share-modal__close" aria-label="Close modal" data-share-modal-close
                data-bs-dismiss="modal"
                style="position: absolute; top: 16px; right: 16px; z-index: 20; width: 32px; height: 32px; border-radius: 50%; background: #f1f5f9; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #475569; transition: all 0.2s ease;">
                <i data-lucide="x" aria-hidden="true" style="width: 18px; height: 18px;"></i>
            </button>

            <div
                style="padding: 24px; max-height: calc(100vh - 100px); max-height: calc(100dvh - 100px); overflow-y: auto;">
                <!-- Header Info -->
                <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 20px;">
                    <div class="community-card__avatar"
                        style="width: 52px; height: 52px; flex-shrink: 0; margin: 0; border-radius: 12px; overflow: hidden; background: #e2e8f0;">
                        @if ($hasLogo)
                            <img src="{{ asset($community->logo) }}" alt="{{ $community->name }} logo"
                                style="width: 100%; height: 100%; object-fit: cover;">
                        @else
                            <div class="community-avatar-initials"
                                style="font-size: 18px; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #4f7df3; color: #fff; font-weight: 800;">
                                {{ $initials }}</div>
                        @endif
                    </div>
                    <div>
                        <h3 id="shareModalTitle-{{ $community->id }}"
                            style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 0 0 2px 0;">
                            Invite & Share {{ $community->name }}
                        </h3>
                        <p style="font-size: 13px; color: #64748b; margin: 0;">
                            Share link publicly or invite your personal network.
                        </p>
                    </div>
                </div>

                <!-- SECTION 1: SHARE COMMUNITY -->
                <div
                    style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 18px; margin-bottom: 20px;">
                    <h4
                        style="font-size: 13.5px; font-weight: 700; color: #1e293b; margin: 0 0 12px 0; display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="share-2" style="width: 16px; height: 16px; color: #4f7df3;"></i> Section 1:
                        Share Community Link
                    </h4>

                    <!-- Copy Link Input & Button -->
                    <div style="margin-bottom: 14px;">
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <input type="text" readonly value="{{ $communityUrl }}" class="community-search-input"
                                style="flex: 1; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; background: #ffffff; color: #0f172a; font-family: monospace; box-sizing: border-box;"
                                data-community-share-url="{{ $community->id }}">
                            <button type="button" class="member-button member-button--primary"
                                style="padding: 10px 18px; font-size: 13px; font-weight: 600; border-radius: 10px; flex-shrink: 0; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;"
                                data-community-copy-btn="{{ $community->id }}">
                                <i data-lucide="copy" style="width: 15px; height: 15px;"></i> Copy Link
                            </button>
                        </div>
                    </div>

                    <!-- Social Share Icons/Buttons Grid -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 8px;">
                        <!-- WhatsApp -->
                        <a href="https://api.whatsapp.com/send?text={{ $encodedTitle }}%20{{ $encodedUrl }}"
                            target="_blank" rel="noopener noreferrer" class="member-button member-button--secondary"
                            style="justify-content: center; padding: 8px 10px; font-size: 12px; color: #25d366; border-color: rgba(37, 211, 102, 0.3); border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                            <i data-lucide="message-circle" style="width: 14px; height: 14px;"></i> WhatsApp
                        </a>
                        <!-- Telegram -->
                        <a href="https://t.me/share/url?url={{ $encodedUrl }}&text={{ $encodedTitle }}"
                            target="_blank" rel="noopener noreferrer" class="member-button member-button--secondary"
                            style="justify-content: center; padding: 8px 10px; font-size: 12px; color: #229ed9; border-color: rgba(34, 158, 217, 0.3); border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                            <i data-lucide="send" style="width: 14px; height: 14px;"></i> Telegram
                        </a>
                        <!-- Facebook -->
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ $encodedUrl }}" target="_blank"
                            rel="noopener noreferrer" class="member-button member-button--secondary"
                            style="justify-content: center; padding: 8px 10px; font-size: 12px; color: #1877f2; border-color: rgba(24, 119, 242, 0.3); border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                            <i data-lucide="facebook" style="width: 14px; height: 14px;"></i> Facebook
                        </a>
                        <!-- X (Twitter) -->
                        <a href="https://twitter.com/intent/tweet?url={{ $encodedUrl }}&text={{ $encodedTitle }}"
                            target="_blank" rel="noopener noreferrer" class="member-button member-button--secondary"
                            style="justify-content: center; padding: 8px 10px; font-size: 12px; color: #0f1419; border-color: rgba(15, 20, 25, 0.2); border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                            <i data-lucide="twitter" style="width: 14px; height: 14px;"></i> X (Twitter)
                        </a>
                        <!-- Email -->
                        <a href="mailto:?subject={{ $encodedTitle }}&body=You%20are%20invited%20to%20join%20the%20community%20{{ urlencode($community->name) }}:%20{{ $encodedUrl }}"
                            class="member-button member-button--secondary"
                            style="justify-content: center; padding: 8px 10px; font-size: 12px; color: #334155; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                            <i data-lucide="mail" style="width: 14px; height: 14px;"></i> Email
                        </a>
                        <!-- Native Share API -->
                        <button type="button" class="member-button member-button--secondary"
                            style="justify-content: center; padding: 8px 10px; font-size: 12px; color: #4f7df3; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;"
                            data-community-native-share data-title="{{ $community->name }}"
                            data-url="{{ $communityUrl }}">
                            <i data-lucide="share-2" style="width: 14px; height: 14px;"></i> Native Share
                        </button>
                    </div>
                </div>

                <!-- SECTION 2: INVITE MEMBERS (FRIENDS) -->
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 18px;">
                    <div
                        style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                        <h4
                            style="font-size: 13.5px; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 6px;">
                            <i data-lucide="user-plus" style="width: 16px; height: 16px; color: #20c875;"></i> Section
                            2: Invite Your Friends
                        </h4>
                        <span style="font-size: 12px; color: #64748b;"
                            data-community-friends-count-label="{{ $community->id }}">Loading friends...</span>
                    </div>

                    <!-- Friends list container -->
                    <div id="communityFriendsListContainer-{{ $community->id }}"
                        data-get-friends-url="{{ route('member.community.invites.friends', $community) }}"
                        style="max-height: 240px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; padding-right: 4px; margin-bottom: 16px;">
                        <div style="padding: 24px; text-align: center; color: #64748b; font-size: 13px;">
                            <i data-lucide="loader-2" class="spin"
                                style="width: 18px; height: 18px; vertical-align: middle; margin-right: 6px;"></i>
                            Loading your friends list...
                        </div>
                    </div>

                    <!-- Submit Action -->
                    <div
                        style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid #e2e8f0; padding-top: 14px; gap: 10px; flex-wrap: wrap;">
                        <label
                            style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; color: #475569; cursor: pointer; user-select: none;">
                            <input type="checkbox" id="selectAllFriends-{{ $community->id }}"
                                data-community-select-all-friends="{{ $community->id }}"
                                style="width: 15px; height: 15px; cursor: pointer; accent-color: #4f7df3;" disabled>
                            Select All Available
                        </label>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <button type="button" class="member-button member-button--secondary"
                                data-share-modal-close data-bs-dismiss="modal"
                                style="padding: 10px 18px; font-size: 13px; font-weight: 600; border-radius: 10px; cursor: pointer;">
                                <i data-lucide="x" style="width: 15px; height: 15px;"></i> Close
                            </button>
                            <button type="button" class="member-button member-button--primary"
                                id="sendCommunityInvitesBtn-{{ $community->id }}"
                                data-community-send-invites="{{ route('member.community.invites.send', $community) }}"
                                data-community-id="{{ $community->id }}"
                                style="padding: 10px 20px; font-size: 13px; font-weight: 600; border-radius: 10px; cursor: pointer;"
                                disabled>
                                <i data-lucide="send" style="width: 15px; height: 15px;"></i> Invite Selected
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
