@php
    $membership = $community
        ->members()
        ->where('member_id', auth('member')->id())
        ->first();
    $currentLevel = $membership->notification_level ?? 'all';
@endphp

<div class="modal fade community-share-modal" id="notificationPreferencesModal" tabindex="-1"
    aria-labelledby="preferencesModalTitle-{{ $community->id }}" aria-hidden="true"
    data-community-preferences-modal-id="{{ $community->id }}" style="display: none !important;" hidden>
    <div class="modal-dialog modal-dialog-centered" style="max-width: 520px; width: 100%; margin: auto;">
        <div class="modal-content community-share-modal__card"
            style="border-radius: 18px; border: none; position: relative; z-index: 10; width: 100%; background: #ffffff; box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25); box-sizing: border-box; padding: 24px; overflow: hidden; margin: auto;">
            <button type="button" class="community-share-modal__close" aria-label="Close modal"
                data-preferences-modal-close data-bs-dismiss="modal"
                style="position: absolute; top: 16px; right: 16px; z-index: 20; width: 32px; height: 32px; border-radius: 50%; background: #f1f5f9; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #475569; transition: all 0.2s ease;">
                <i data-lucide="x" aria-hidden="true" style="width: 18px; height: 18px;"></i>
            </button>

            <h3 id="preferencesModalTitle-{{ $community->id }}"
                style="font-size: 17px; font-weight: 800; margin: 0 0 4px 0; color: #0f172a;">
                <i data-lucide="bell" style="width: 18px; height: 18px; color: #4f7df3; vertical-align: middle;"></i>
                Notification Preferences
            </h3>
            <p style="font-size: 12.5px; color: #64748b; margin: 0 0 16px 0;">
                Customize notification settings for {{ $community->name }}.
            </p>

            <form method="POST" action="{{ route('member.community.preferences.update', $community) }}"
                data-community-preferences-form>
                @csrf
                <div style="margin-bottom: 14px;">
                    <label
                        style="font-size: 12px; font-weight: 700; display: block; margin-bottom: 4px; color: #1e293b;">Notification
                        Frequency</label>
                    <select name="notification_level" class="community-search-input"
                        style="padding: 8px 12px; width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px;"
                        required>
                        <option value="all" {{ $currentLevel === 'all' ? 'selected' : '' }}>All Notifications (Posts,
                            Comments, Announcements)</option>
                        <option value="important_only" {{ $currentLevel === 'important_only' ? 'selected' : '' }}>
                            Important Only (Pinned Posts & Announcements)</option>
                        <option value="announcements_only"
                            {{ $currentLevel === 'announcements_only' ? 'selected' : '' }}>Announcements Only</option>
                        <option value="posts_only" {{ $currentLevel === 'posts_only' ? 'selected' : '' }}>New Posts Only
                        </option>
                        <option value="muted" {{ $currentLevel === 'muted' ? 'selected' : '' }}>Mute All Notifications
                        </option>
                    </select>
                </div>

                <div style="margin-bottom: 18px;">
                    <label
                        style="font-size: 12px; font-weight: 700; display: block; margin-bottom: 4px; color: #1e293b;">Mute
                        Notifications Temporarily</label>
                    <select name="mute_duration" class="community-search-input"
                        style="padding: 8px 12px; width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px;">
                        <option value="none">Do Not Mute</option>
                        <option value="1h">Mute for 1 Hour</option>
                        <option value="8h">Mute for 8 Hours</option>
                        <option value="24h">Mute for 24 Hours</option>
                        <option value="7d">Mute for 7 Days</option>
                        <option value="forever">Mute Permanently</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="member-button member-button--secondary" data-preferences-modal-close
                        data-bs-dismiss="modal" style="border-radius: 10px; padding: 8px 16px;">Cancel</button>
                    <button type="submit" class="member-button member-button--primary"
                        style="border-radius: 10px; padding: 8px 18px;">Save Preferences</button>
                </div>
            </form>
        </div>
    </div>
</div>
