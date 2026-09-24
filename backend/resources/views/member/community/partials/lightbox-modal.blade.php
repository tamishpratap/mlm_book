<div class="community-share-modal" id="communityLightboxModal" hidden aria-hidden="true" role="dialog">
    <div class="community-share-modal__overlay" data-lightbox-close></div>
    <div class="community-share-modal__card" style="max-width: 820px; width: 92%; background: #0b0f19; border-color: rgba(255,255,255,0.1); color: #ffffff; padding: 0;">
        <button type="button" class="community-share-modal__close" aria-label="Close modal" data-lightbox-close style="top: 16px; right: 16px; background: rgba(0,0,0,0.6);">
            <i data-lucide="x" aria-hidden="true"></i>
        </button>

        <div style="display: flex; flex-direction: column; max-height: 85vh;">
            <div style="flex: 1; background: #000000; display: flex; align-items: center; justify-content: center; min-height: 320px; max-height: 65vh; overflow: hidden;" id="lightboxMediaContainer">
                <!-- Dynamically populated by JS -->
            </div>

            <div style="padding: 16px 20px; background: rgba(16,24,40,0.95); border-top: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                <div>
                    <strong id="lightboxAuthor" style="font-size: 14px; color: #ffffff;">Member</strong>
                    <span aria-hidden="true">·</span>
                    <span id="lightboxDate" style="font-size: 12px; color: rgba(255,255,255,0.7);">Date</span>
                    <p id="lightboxBody" style="font-size: 13px; color: rgba(255,255,255,0.85); margin: 4px 0 0 0; line-height: 1.4;"></p>
                </div>

                <a id="lightboxDownload" href="" download class="member-button member-button--secondary" style="padding: 6px 14px; font-size: 12px; color: #ffffff; border-color: rgba(255,255,255,0.2);">
                    <i data-lucide="download" style="width: 14px; height: 14px;"></i> Download
                </a>
            </div>
        </div>
    </div>
</div>
