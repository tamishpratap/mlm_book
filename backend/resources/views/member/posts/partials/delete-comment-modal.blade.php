<div
    class="story-modal delete-comment-modal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="delete-comment-modal-title"
    data-delete-comment-modal
>
    <button class="story-modal__backdrop" type="button" aria-label="Cancel Delete" data-delete-comment-cancel></button>

    <div class="story-modal__panel delete-comment-modal__panel">
        <header class="story-modal__header">
            <div>
                <span>Confirm Action</span>
                <h2 id="delete-comment-modal-title">Delete Comment?</h2>
            </div>
            <button class="story-modal__close" type="button" aria-label="Close Modal" data-delete-comment-cancel>
                <i data-lucide="x" aria-hidden="true"></i>
            </button>
        </header>

        <div class="delete-comment-modal__body">
            <p>Are you sure you want to delete this comment? This action cannot be undone.</p>
        </div>

        <footer class="delete-comment-modal__footer">
            <button class="delete-comment-modal__btn-cancel" type="button" data-delete-comment-cancel>Cancel</button>
            <button class="delete-comment-modal__btn-confirm" type="button" data-delete-comment-confirm>Delete</button>
        </footer>
    </div>
</div>
