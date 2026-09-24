<div
    class="story-modal post-report-modal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="post-report-modal-title"
    data-post-report-modal
>
    <button class="story-modal__backdrop" type="button" aria-label="Cancel Report" data-post-report-cancel></button>

    <div class="story-modal__panel post-report-modal__panel">
        <header class="story-modal__header">
            <div>
                <span>Report Content</span>
                <h2 id="post-report-modal-title">Report Post</h2>
            </div>
            <button class="story-modal__close" type="button" aria-label="Close Modal" data-post-report-cancel>
                <i data-lucide="x" aria-hidden="true"></i>
            </button>
        </header>

        <form
            class="post-report-modal__form"
            method="POST"
            action=""
            data-post-report-form
        >
            @csrf
            <div class="post-report-modal__body">
                <p class="post-report-modal__subtitle">Please select a reason why you are reporting this post:</p>

                <div class="post-report-modal__reasons">
                    @foreach (['Spam', 'Fake News', 'Harassment', 'Violence', 'Adult Content', 'Hate Speech', 'Other'] as $reason)
                        <label class="post-report-modal__reason-label">
                            <input type="radio" name="reason" value="{{ $reason }}" required @if($loop->first) checked @endif>
                            <span>{{ $reason }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="post-report-modal__input-wrap">
                    <label for="report-description" class="post-report-modal__textarea-label">Additional Details (Optional)</label>
                    <textarea
                        id="report-description"
                        class="post-report-modal__textarea"
                        name="description"
                        placeholder="Provide more context..."
                        aria-label="Additional details"
                        maxlength="500"
                        rows="3"
                    ></textarea>
                </div>
            </div>

            <footer class="post-report-modal__footer">
                <button class="post-report-modal__btn-cancel" type="button" data-post-report-cancel>Cancel</button>
                <button class="post-report-modal__btn-submit" type="submit">Send Report</button>
            </footer>
        </form>
    </div>
</div>
