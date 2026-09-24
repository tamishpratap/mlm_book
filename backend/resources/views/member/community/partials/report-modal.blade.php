<div class="community-share-modal" id="communityReportModal" hidden aria-hidden="true" role="dialog">
    <div class="community-share-modal__overlay" data-report-modal-close></div>
    <div class="community-share-modal__card" style="max-width: 480px; padding: 24px;">
        <button type="button" class="community-share-modal__close" aria-label="Close modal" data-report-modal-close>
            <i data-lucide="x" aria-hidden="true"></i>
        </button>

        <h3 style="font-size: 17px; font-weight: 800; margin: 0 0 6px 0; color: var(--color-text-main);">
            <i data-lucide="flag" style="width: 18px; height: 18px; color: #ef4444; vertical-align: middle;"></i> Submit Report
        </h3>
        <p style="font-size: 12.5px; color: var(--color-text-secondary); margin: 0 0 16px 0;">
            Help keep our community safe and respectful by reporting violations.
        </p>

        <form method="POST" action="" data-community-report-form>
            @csrf
            <input type="hidden" name="reportable_type" id="reportableType">
            <input type="hidden" name="reportable_id" id="reportableId">

            <div style="margin-bottom: 14px;">
                <label style="font-size: 12px; font-weight: 700; display: block; margin-bottom: 4px;">Reason for Report</label>
                <select name="reason" class="community-search-input" style="padding: 8px 12px;" required>
                    <option value="Spam or Unwanted Promotion">Spam or Unwanted Promotion</option>
                    <option value="Harassment or Hate Speech">Harassment or Hate Speech</option>
                    <option value="Inappropriate or Explicit Content">Inappropriate or Explicit Content</option>
                    <option value="False Information or Scam">False Information or Scam</option>
                    <option value="Violence or Harmful Behavior">Violence or Harmful Behavior</option>
                    <option value="Other Violation">Other Violation</option>
                </select>
            </div>

            <div style="margin-bottom: 18px;">
                <label style="font-size: 12px; font-weight: 700; display: block; margin-bottom: 4px;">Additional Details (Optional)</label>
                <textarea name="details" class="community-search-input" rows="3" style="padding: 8px 12px;" placeholder="Describe why this content violates community guidelines..."></textarea>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="member-button member-button--secondary" data-report-modal-close>Cancel</button>
                <button type="submit" class="member-button member-button--primary" style="background: #ef4444; border-color: #ef4444;">Submit Report</button>
            </div>
        </form>
    </div>
</div>
