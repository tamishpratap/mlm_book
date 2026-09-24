<div class="biz-modal-overlay" id="bizShareModal" hidden>
    <div class="biz-modal" role="dialog" aria-labelledby="shareModalTitle" aria-modal="true">
        <div class="biz-modal__header">
            <h3 id="shareModalTitle">
                <i data-lucide="share-2" style="color: #4f7df3;"></i> Share Business Page
            </h3>
            <button type="button" class="icon-button" onclick="closeBizShareModal()" aria-label="Close modal">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="biz-modal__body">
            <p style="margin: 0; color: #687386; font-size: 13.5px;">
                Share <strong>{{ $businessPage->page_name }}</strong> with your network across social platforms:
            </p>

            <div class="biz-share-grid">
                <button type="button" class="biz-share-btn" onclick="copyBizLink('{{ route('member.business-pages.show', $businessPage) }}')">
                    <i data-lucide="link"></i>
                    <span>Copy Link</span>
                </button>

                <a href="https://api.whatsapp.com/send?text={{ urlencode('Check out ' . $businessPage->page_name . ': ' . route('member.business-pages.show', $businessPage)) }}" target="_blank" class="biz-share-btn">
                    <i data-lucide="message-circle"></i>
                    <span>WhatsApp</span>
                </a>

                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('member.business-pages.show', $businessPage)) }}" target="_blank" class="biz-share-btn">
                    <i data-lucide="facebook"></i>
                    <span>Facebook</span>
                </a>

                <a href="https://t.me/share/url?url={{ urlencode(route('member.business-pages.show', $businessPage)) }}&text={{ urlencode($businessPage->page_name) }}" target="_blank" class="biz-share-btn">
                    <i data-lucide="send"></i>
                    <span>Telegram</span>
                </a>
            </div>

            <div class="form-group" style="margin-top: 8px;">
                <label style="font-size: 12px; font-weight: 600; color: #687386; margin-bottom: 4px; display: block;">Direct URL</label>
                <div style="display: flex; gap: 8px;">
                    <input type="text" readonly value="{{ route('member.business-pages.show', $businessPage) }}" class="biz-search-input" id="bizDirectLinkInput" style="height: 38px; font-size: 12.5px;">
                    <button type="button" class="member-button member-button--secondary" onclick="copyBizLinkInput()" style="height: 38px;">
                        Copy
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openBizShareModal() {
    document.getElementById('bizShareModal').removeAttribute('hidden');
}
function closeBizShareModal() {
    document.getElementById('bizShareModal').setAttribute('hidden', 'true');
}
function copyBizLink(url) {
    navigator.clipboard.writeText(url).then(() => {
        alert('Business Page link copied to clipboard!');
    });
}
function copyBizLinkInput() {
    const input = document.getElementById('bizDirectLinkInput');
    input.select();
    navigator.clipboard.writeText(input.value).then(() => {
        alert('Business Page link copied to clipboard!');
    });
}
</script>
