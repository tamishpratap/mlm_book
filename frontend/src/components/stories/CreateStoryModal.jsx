import { useState, useEffect, useRef } from 'react';
import { X, Image, Video, FileImage, FileVideo, Send } from 'lucide-react';
import storyApi from '../../api/storyApi';
import { ModalPortal } from '../common/ModalPortal';

export function CreateStoryModal({ isOpen, onClose, onStoryCreated }) {
  const [caption, setCaption] = useState('');
  const [mediaFile, setMediaFile] = useState(null);
  const [mediaPreviewUrl, setMediaPreviewUrl] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState(null);
  const fileInputRef = useRef(null);
  // Object URL cleanup on unmount
  useEffect(() => {
    return () => {
      if (mediaPreviewUrl) {
        URL.revokeObjectURL(mediaPreviewUrl);
      }
    };
  }, [mediaPreviewUrl]);

  useEffect(() => {
    if (!isOpen) return;
    const handleKeyDown = (e) => {
      if (e.key === 'Escape') onClose();
    };
    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, onClose]);

  if (!isOpen) return null;

  const handleMediaChange = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const isImage = file.type.startsWith('image/');
    const isVideo = file.type.startsWith('video/');

    if (isVideo) {
      setError('Videos can only be posted from a Business Page.');
      if (e.target) e.target.value = '';
      return;
    }

    if (!isImage) {
      setError('Please select a JPG, PNG, or WebP image file.');
      if (e.target) e.target.value = '';
      return;
    }

    if (isImage && file.size > 5 * 1024 * 1024) {
      setError('The image must not be larger than 5 MB.');
      if (e.target) e.target.value = '';
      return;
    }

    setMediaFile(file);
    setMediaPreviewUrl(URL.createObjectURL(file));
    setError(null);
  };

  const handleRemoveMedia = () => {
    setMediaFile(null);
    if (mediaPreviewUrl) {
      URL.revokeObjectURL(mediaPreviewUrl);
      setMediaPreviewUrl(null);
    }
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!mediaFile) {
      setError('Please select an image for your story.');
      return;
    }

    const isVideo = mediaFile && mediaFile.type && mediaFile.type.startsWith('video/');
    if (isVideo) {
      setError('Videos can only be posted from a Business Page.');
      return;
    }

    setIsSubmitting(true);
    setError(null);

    const formData = new FormData();
    formData.append('media', mediaFile);
    if (caption.trim()) {
      formData.append('caption', caption.trim());
    }

    try {
      const response = await storyApi.createStory(formData);
      handleRemoveMedia();
      setCaption('');
      onClose();
      if (onStoryCreated) {
        onStoryCreated(response?.story || response);
      }
    } catch (err) {
      setError(
        err.response?.data?.message ||
          err.response?.data?.errors?.media?.[0] ||
          'Failed to upload story. Please try again.'
      );
    } finally {
      setIsSubmitting(false);
    }
  };

  const isVideo = mediaFile?.type?.startsWith('video/');

  return (
    <ModalPortal isOpen={isOpen} onClose={onClose}>
      <section className="story-modal__panel fb-story-panel" role="dialog" aria-modal="true" aria-labelledby="create-story-title">
        <form onSubmit={handleSubmit}>
          <header className="story-modal__header fb-story-header">
            <div className="fb-story-header__titles">
              <h2 id="create-story-title" className="fb-story-header__title">
                Create Story
              </h2>
            </div>
            <button
              className="story-modal__close fb-story-header__close"
              type="button"
              aria-label="Close Create Story"
              onClick={onClose}
            >
              <X size={18} aria-hidden="true" />
            </button>
          </header>

          <div className="story-modal__body fb-story-body">
            {!mediaPreviewUrl ? (
              <label
                className="story-upload fb-story-dropzone"
                htmlFor="story-media-upload"
              >
                <div className="fb-story-dropzone__icons">
                  <span
                    className="fb-story-dropzone__badge fb-story-dropzone__badge--photo"
                    title="Upload Photo"
                  >
                    <Image size={20} aria-hidden="true" />
                  </span>
                </div>
                <strong className="fb-story-dropzone__heading">Add Photo</strong>
                <span className="fb-story-dropzone__subheading">
                  Drag and drop or click to browse image
                </span>
                <div className="fb-story-dropzone__types">
                  <span className="fb-chip">
                    <FileImage size={13} aria-hidden="true" /> JPG, PNG, WebP (max 5MB)
                  </span>
                </div>
                <input
                  id="story-media-upload"
                  ref={fileInputRef}
                  name="media"
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  required
                  onChange={handleMediaChange}
                />
              </label>
            ) : (
              <div className="story-preview fb-story-preview">
                <div className="story-preview__media fb-story-preview__media">
                  {isVideo ? (
                    <video
                      src={mediaPreviewUrl}
                      controls
                      playsInline
                      style={{ width: '100%', maxHeight: '320px' }}
                    />
                  ) : (
                    <img
                      src={mediaPreviewUrl}
                      alt="Story preview"
                      style={{ width: '100%', maxHeight: '320px', objectFit: 'contain' }}
                    />
                  )}
                </div>
                <button
                  className="fb-story-preview__remove"
                  type="button"
                  aria-label="Remove selected media"
                  onClick={handleRemoveMedia}
                  title="Remove Media"
                >
                  <X size={14} aria-hidden="true" />
                  <span>Remove</span>
                </button>
              </div>
            )}

            {error && (
              <p
                className="story-form__error fb-story-error"
                style={{ color: 'var(--color-danger, #ff4168)', fontSize: '0.85rem', marginTop: '8px' }}
              >
                {error}
              </p>
            )}

            {/* Caption Area */}
            <div className="story-caption fb-story-caption" style={{ marginTop: '12px' }}>
              <div className="fb-story-caption__header">
                <label htmlFor="story-caption-field" className="fb-story-caption__label">
                  Caption <small className="text-muted">(Optional)</small>
                </label>
                <span className="fb-story-caption__counter">
                  {caption.length} / 500
                </span>
              </div>
              <textarea
                id="story-caption-field"
                name="caption"
                maxLength={500}
                rows={3}
                placeholder="Add a short caption..."
                value={caption}
                onChange={(e) => setCaption(e.target.value)}
              />
            </div>
          </div>

          <div className="story-modal__actions fb-story-footer">
            <button
              className="story-button story-button--secondary fb-story-btn fb-story-btn--secondary"
              type="button"
              onClick={onClose}
              disabled={isSubmitting}
            >
              Cancel
            </button>
            <button
              className="story-button story-button--primary fb-story-btn fb-story-btn--primary"
              type="submit"
              disabled={isSubmitting || !mediaFile}
            >
              <Send size={15} aria-hidden="true" />
              <span>{isSubmitting ? 'Sharing...' : 'Share Story'}</span>
            </button>
          </div>
        </form>
      </section>

    </ModalPortal>
  );
}

export default CreateStoryModal;
