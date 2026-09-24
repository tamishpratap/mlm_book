<div
    class="story-modal fb-story-modal"
    id="create-story-modal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="create-story-title"
    data-story-create-modal
    data-open-on-errors="{{ $errors->getBag('story')->any() ? 'true' : 'false' }}"
    hidden
>
    <button class="story-modal__backdrop" type="button" aria-label="Close Create Story" data-story-create-close></button>
    <section class="story-modal__panel fb-story-panel">
        <form method="POST" action="{{ route('member.stories.store') }}" enctype="multipart/form-data" data-story-form>
            @csrf
            <header class="story-modal__header fb-story-header">
                <div class="fb-story-header__titles">
                    <h2 id="create-story-title" class="fb-story-header__title">Create Story</h2>
                </div>
                <button class="story-modal__close fb-story-header__close" type="button" aria-label="Close Create Story" data-story-create-close>
                    <i data-lucide="x" aria-hidden="true"></i>
                </button>
            </header>

            <div class="story-modal__body fb-story-body">
                <!-- Drop & Drag Upload Zone -->
                <label class="story-upload fb-story-dropzone" for="story-media" data-story-dropzone>
                    <div class="fb-story-dropzone__icons">
                        <span class="fb-story-dropzone__badge fb-story-dropzone__badge--photo" title="Upload Photo">
                            <i data-lucide="image" aria-hidden="true"></i>
                        </span>
                        <span class="fb-story-dropzone__badge fb-story-dropzone__badge--video" title="Upload Video">
                            <i data-lucide="video" aria-hidden="true"></i>
                        </span>
                    </div>
                    <strong class="fb-story-dropzone__heading">Add Photo or Video</strong>
                    <span class="fb-story-dropzone__subheading">Drag and drop or click to browse media</span>
                    <div class="fb-story-dropzone__types">
                        <span class="fb-chip"><i data-lucide="file-image" aria-hidden="true"></i> JPG, PNG, WebP (max 5MB)</span>
                        <span class="fb-chip"><i data-lucide="file-video" aria-hidden="true"></i> MP4, WebM, MOV (max 50MB)</span>
                    </div>
                    <input
                        id="story-media"
                        name="media"
                        type="file"
                        accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime"
                        required
                        data-story-media
                    >
                </label>
                @error('media', 'story')<p class="story-form__error fb-story-error">{{ $message }}</p>@enderror

                <!-- Live Media Preview -->
                <div class="story-preview fb-story-preview" data-story-preview hidden>
                    <div class="story-preview__media fb-story-preview__media" data-story-preview-media></div>
                    <button class="fb-story-preview__remove" type="button" aria-label="Remove selected media" data-story-media-remove title="Remove Media">
                        <i data-lucide="x" aria-hidden="true"></i>
                        <span>Remove</span>
                    </button>
                </div>

                <!-- Caption Area -->
                <div class="story-caption fb-story-caption">
                    <div class="fb-story-caption__header">
                        <label for="story-caption" class="fb-story-caption__label">Caption <small class="text-muted">(Optional)</small></label>
                        <span class="fb-story-caption__counter" data-story-char-counter>0 / 500</span>
                    </div>
                    <textarea
                        id="story-caption"
                        name="caption"
                        maxlength="500"
                        rows="3"
                        placeholder="Add a short caption..."
                        data-story-caption-input
                    >{{ old('caption') }}</textarea>
                </div>
                @error('caption', 'story')<p class="story-form__error fb-story-error">{{ $message }}</p>@enderror

                <p class="story-form__feedback fb-story-feedback" role="status" aria-live="polite" data-story-feedback></p>
            </div>

            <div class="story-modal__actions fb-story-footer">
                <button class="story-button story-button--secondary fb-story-btn fb-story-btn--secondary" type="button" data-story-create-close>Cancel</button>
                <button class="story-button story-button--primary fb-story-btn fb-story-btn--primary" type="submit" data-story-submit disabled>
                    <i data-lucide="send" aria-hidden="true" data-story-submit-icon></i>
                    <span data-story-submit-label>Share Story</span>
                </button>
            </div>
        </form>
    </section>
</div>
