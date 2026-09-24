import { useState, useRef, useEffect } from 'react';
import { Image, Send, X, Megaphone, Crop, Plus } from 'lucide-react';
import postApi from '../../api/postApi';
import communityApi from '../../api/communityApi';
import businessApi from '../../api/businessApi';
import { getAvatarUrl } from '../../utils/assetHelper';
import { ImageAdjustmentModal } from './modals/ImageAdjustmentModal';
import { ModalPortal } from '../common/ModalPortal';
import { useImageModeration } from '../../hooks/useImageModeration';
import { useVideoModeration } from '../../hooks/useVideoModeration';
import { MODERATION_CONTEXTS, POLICY_ACTIONS } from '../../config/imageModerationPolicy';
import { ImageModerationScanModal } from '../moderation/ImageModerationScanModal';

function getInitials(name) {
  if (!name) return 'M';
  const parts = name.trim().split(/\s+/);
  return parts.slice(0, 2).map((p) => p[0].toUpperCase()).join('') || 'M';
}

export function PostComposer({
  currentUser,
  onPostCreated,
  communitySlug,
  communityName,
  isCommunityAdmin = false,
  businessPage,
  placeholder,
  allowVideo,
}) {
  const [body, setBody] = useState('');
  const [rawImageFile, setRawImageFile] = useState(null);
  const [mediaFile, setMediaFile] = useState(null);
  const [mediaPreviewUrl, setMediaPreviewUrl] = useState(null);
  const [adjustmentState, setAdjustmentState] = useState(null);
  const [isAdjustModalOpen, setIsAdjustModalOpen] = useState(false);
  const [isAnnouncement, setIsAnnouncement] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [feedback, setFeedback] = useState({ type: '', message: '' });
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [composerAvatarError, setComposerAvatarError] = useState(false);
  const canUploadVideo = allowVideo !== undefined ? Boolean(allowVideo) : Boolean(businessPage);

  const moderationContext = businessPage
    ? MODERATION_CONTEXTS.BUSINESS_IMAGE
    : (communitySlug ? MODERATION_CONTEXTS.COMMUNITY_IMAGE : MODERATION_CONTEXTS.POST_IMAGE);

  const {
    scanImage,
    progress: modProgress,
    decision: modDecision,
    error: modError,
    cancel: cancelModeration,
    reset: resetModeration,
  } = useImageModeration({ context: moderationContext });

  const {
    scanVideo,
    cancel: cancelVideoModeration,
  } = useVideoModeration({ context: moderationContext });

  const [isScanModalOpen, setIsScanModalOpen] = useState(false);
  const [scanStatus, setScanStatus] = useState('IDLE');

  const fileInputRef = useRef(null);
  const textareaRef = useRef(null);

  const handleCreatePost = () => {
    setIsCreateModalOpen(true);
  };

  const handleMediaChange = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    // Check size limit: images <= 5MB, videos <= 25MB
    const isImage = file.type.startsWith('image/');
    const isVideo = file.type.startsWith('video/');

    if (!canUploadVideo && isVideo) {
      setFeedback({
        type: 'error',
        message: 'Videos can only be posted from a Business Page.',
      });
      if (e.target) e.target.value = '';
      return;
    }

    if (!isImage && !isVideo) {
      setFeedback({
        type: 'error',
        message: canUploadVideo
          ? 'Please choose a JPG, PNG, WEBP, MP4, WEBM, or MOV file.'
          : 'Please choose a JPG, PNG, or WEBP file.',
      });
      if (e.target) e.target.value = '';
      return;
    }

    if (isImage && file.size > 5 * 1024 * 1024) {
      setFeedback({ type: 'error', message: 'Images may not be larger than 5 MB.' });
      return;
    }

    if (isVideo && file.size > 25 * 1024 * 1024) {
      setFeedback({ type: 'error', message: 'Videos may not be larger than 25 MB.' });
      return;
    }

    if (isImage) {
      setRawImageFile(file);
      setIsCreateModalOpen(true);
      // Open the Image Adjustment Modal so user can frame, zoom, and crop before publishing
      setIsAdjustModalOpen(true);
    } else {
      setRawImageFile(null);
      setAdjustmentState(null);
      setMediaFile(file);
      setMediaPreviewUrl(URL.createObjectURL(file));
      setIsCreateModalOpen(true);
    }
    setFeedback({ type: '', message: '' });
  };

  const handleApplyAdjustment = ({ file: adjustedFile, previewUrl, adjustmentState: state }) => {
    if (mediaPreviewUrl) {
      URL.revokeObjectURL(mediaPreviewUrl);
    }
    setMediaFile(adjustedFile);
    setMediaPreviewUrl(previewUrl);
    setAdjustmentState(state);
    setIsAdjustModalOpen(false);
    setIsCreateModalOpen(true);
  };

  const handleCancelAdjustment = () => {
    setIsAdjustModalOpen(false);
    // If no media was previously confirmed, clear input
    if (!mediaFile && fileInputRef.current) {
      fileInputRef.current.value = '';
      setRawImageFile(null);
    }
  };

  const handleRemoveMedia = () => {
    setRawImageFile(null);
    setMediaFile(null);
    setAdjustmentState(null);
    resetModeration();
    if (mediaPreviewUrl) {
      URL.revokeObjectURL(mediaPreviewUrl);
      setMediaPreviewUrl(null);
    }
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  const handleSubmit = async (e) => {
    if (e?.preventDefault) e.preventDefault();
    if (!body.trim() && !mediaFile) {
      setFeedback({
        type: 'error',
        message: 'Please provide some text or select an image/video to publish.',
      });
      return;
    }

    setIsSubmitting(true);
    setFeedback({ type: '', message: '' });

    // Pre-flight Client-Side Moderation (Images & Videos)
    const isImageMedia = mediaFile && mediaFile.type && mediaFile.type.startsWith('image/');
    const isVideoMedia = mediaFile && mediaFile.type && mediaFile.type.startsWith('video/');

    if (!canUploadVideo && isVideoMedia) {
      setFeedback({
        type: 'error',
        message: 'Videos can only be posted from a Business Page.',
      });
      setIsSubmitting(false);
      return;
    }

    if (isImageMedia) {
      setIsScanModalOpen(true);
      setScanStatus('SCANNING');

      try {
        const decision = await scanImage(mediaFile);

        if (decision.action === POLICY_ACTIONS.BLOCK) {
          setScanStatus('BLOCKED');
          setFeedback({
            type: 'error',
            message: decision.userMessage,
          });
          setIsSubmitting(false);
          return;
        }

        if (decision.isSystemError) {
          setScanStatus('ERROR');
          setFeedback({
            type: 'error',
            message: decision.userMessage,
          });
          setIsSubmitting(false);
          return;
        }

        // Image passed moderation successfully
        setIsScanModalOpen(false);
        setScanStatus('IDLE');
      } catch {
        setScanStatus('ERROR');
        setFeedback({
          type: 'error',
          message: 'Unable to verify image safety. Please try again.',
        });
        setIsSubmitting(false);
        return;
      }
    } else if (isVideoMedia) {
      setIsScanModalOpen(true);
      setScanStatus('SCANNING');

      try {
        const decision = await scanVideo(mediaFile);

        if (decision.action === POLICY_ACTIONS.BLOCK) {
          setScanStatus('BLOCKED');
          setFeedback({
            type: 'error',
            message: decision.userMessage,
          });
          setIsSubmitting(false);
          return;
        }

        setIsScanModalOpen(false);
        setScanStatus('IDLE');
      } catch {
        setScanStatus('ERROR');
        setFeedback({
          type: 'error',
          message: 'Unable to verify video safety. Please try again.',
        });
        setIsSubmitting(false);
        return;
      }
    }

    const formData = new FormData();
    if (body.trim()) {
      formData.append('body', body.trim());
    }
    if (mediaFile) {
      formData.append('media', mediaFile);
    }
    if (communitySlug && isAnnouncement) {
      formData.append('is_announcement', '1');
    }

    try {
      let response;
      if (businessPage) {
        response = await businessApi.storeBusinessPost(businessPage.slug || businessPage.id, formData);
      } else if (communitySlug) {
        response = await communityApi.createCommunityPost(communitySlug, formData);
      } else {
        response = await postApi.createPost(formData);
      }

      setBody('');
      setIsAnnouncement(false);
      handleRemoveMedia();
      setIsCreateModalOpen(false);
      setFeedback({
        type: 'success',
        message: response.message || 'Your post has been published.',
      });
      if (onPostCreated && response.post) {
        onPostCreated(response.post);
      }
      setTimeout(() => {
        setFeedback({ type: '', message: '' });
      }, 4000);
    } catch (err) {
      setFeedback({
        type: 'error',
        message: err.response?.data?.message || 'We could not publish your post. Please try again.',
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  const displayAvatarUrl = businessPage
    ? (businessPage.logo ? getAvatarUrl(businessPage.logo) : (businessPage.logo_url ? getAvatarUrl(businessPage.logo_url) : null))
    : (currentUser?.profile_photo ? getAvatarUrl(currentUser.profile_photo) : null);
  const displayName = businessPage?.page_name || currentUser?.name || 'Member';
  const isVideo = mediaFile?.type?.startsWith('video/');

  useEffect(() => {
    setComposerAvatarError(false);
  }, [displayAvatarUrl]);

  // Object URL cleanup on unmount
  useEffect(() => {
    return () => {
      if (mediaPreviewUrl) {
        URL.revokeObjectURL(mediaPreviewUrl);
      }
    };
  }, [mediaPreviewUrl]);

  return (
    <>
      <div className="card composer">
        <div
          className="composer__input-row"
          onClick={handleCreatePost}
          style={{ cursor: 'pointer' }}
        >
          {displayAvatarUrl && !composerAvatarError ? (
            <img
              className="avatar"
              src={displayAvatarUrl}
              alt={displayName}
              onError={() => setComposerAvatarError(true)}
            />
          ) : (
            <span
              className="avatar post-avatar-initials"
              style={{
                width: '42px',
                height: '42px',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                background: 'linear-gradient(135deg, #176bff, #7146ed)',
                color: '#fff',
                fontWeight: 700,
                borderRadius: '50%',
              }}
            >
              {getInitials(displayName)}
            </span>
          )}

          <div
            className="composer__prompt"
            role="button"
            tabIndex={0}
            onClick={() => setIsCreateModalOpen(true)}
            onKeyDown={(e) => {
              if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                setIsCreateModalOpen(true);
              }
            }}
            style={{
              cursor: 'pointer',
              display: 'flex',
              alignItems: 'center',
              minHeight: '40px',
              color: body.trim() ? 'var(--color-text-main, #0f172a)' : 'var(--color-text-secondary, #64748b)',
              userSelect: 'none',
              width: '100%',
            }}
          >
            {body.trim()
              ? body
              : placeholder ||
                (businessPage
                  ? `Publish an update for ${businessPage.page_name}...`
                  : communityName
                  ? `Write something to ${communityName}...`
                  : `What's on your mind, ${currentUser?.name || 'Member'}?`)}
          </div>
        </div>

        <div className="composer__create-row">
          <button
            type="button"
            className="member-button member-button--primary composer__create-btn"
            onClick={() => setIsCreateModalOpen(true)}
            aria-label="Create Post"
          >
            <Plus size={16} aria-hidden="true" />
            <span>Create Post</span>
          </button>
        </div>

        <div className="composer__actions">
          <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
            <label className="composer__action" htmlFor="post-card-media-input" style={{ cursor: 'pointer' }}>
              <Image size={18} className="action-icon action-icon--photo" aria-hidden="true" />
              <span>{canUploadVideo ? 'Photo / video' : 'Photo'}</span>
              <input
                id="post-card-media-input"
                ref={fileInputRef}
                name="media"
                type="file"
                accept={canUploadVideo ? 'image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime' : 'image/jpeg,image/png,image/webp'}
                onChange={handleMediaChange}
              />
            </label>
          </div>

          <button
            className="composer__submit member-button"
            type="button"
            onClick={() => setIsCreateModalOpen(true)}
          >
            <Send size={16} aria-hidden="true" />
            <span>Post</span>
          </button>
        </div>

        {feedback.message && (
          <p
            className="composer__feedback"
            role="status"
            aria-live="polite"
            style={{
              marginTop: '8px',
              fontSize: '0.85rem',
              color: feedback.type === 'error' ? 'var(--color-danger, #ff4168)' : 'var(--color-success, #20c875)',
              fontWeight: 500,
            }}
          >
            {feedback.message}
          </p>
        )}
      </div>

      {/* Standard Full-Viewport Centered Create Post Modal */}
      {isCreateModalOpen && (
        <ModalPortal isOpen={isCreateModalOpen} onClose={() => setIsCreateModalOpen(false)}>
          <div
            className="card fb-post-create-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="create-post-modal-title"
            style={{
              width: '100%',
              maxWidth: '560px',
              maxHeight: 'min(90vh, 720px)',
              display: 'flex',
              flexDirection: 'column',
              borderRadius: '20px',
              overflow: 'hidden',
              backgroundColor: '#ffffff',
              boxShadow: '0 25px 50px -12px rgba(15, 23, 42, 0.35)',
              border: '1px solid rgba(226, 232, 240, 0.9)',
              position: 'relative',
              zIndex: 1,
              opacity: 1,
            }}
          >
            {/* Header */}
            <header
              style={{
                padding: '16px 20px',
                borderBottom: '1px solid var(--color-border-soft, #f1f5f9)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                position: 'relative',
                backgroundColor: '#ffffff',
              }}
            >
              <div style={{ flex: 1, textAlign: 'center' }}>
                <h2
                  id="create-post-modal-title"
                  style={{
                    margin: 0,
                    fontSize: '1.2rem',
                    fontWeight: 800,
                    color: 'var(--color-text-main, #0f172a)',
                  }}
                >
                  Create Post
                </h2>
              </div>
              <button
                type="button"
                aria-label="Close"
                onClick={() => setIsCreateModalOpen(false)}
                style={{
                  position: 'absolute',
                  right: '16px',
                  top: '50%',
                  transform: 'translateY(-50%)',
                  width: '34px',
                  height: '34px',
                  borderRadius: '50%',
                  backgroundColor: '#f1f5f9',
                  border: 'none',
                  display: 'grid',
                  placeItems: 'center',
                  cursor: 'pointer',
                  color: '#475569',
                }}
              >
                <X size={18} />
              </button>
            </header>

            {/* Scrollable Body */}
            <div
              style={{
                flex: 1,
                overflowY: 'auto',
                padding: '18px 20px',
                display: 'flex',
                flexDirection: 'column',
                gap: '14px',
              }}
            >
              {/* Author Row */}
              <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                {displayAvatarUrl && !composerAvatarError ? (
                  <img
                    className="avatar"
                    src={displayAvatarUrl}
                    alt={displayName}
                    style={{ width: '44px', height: '44px', borderRadius: '50%', objectFit: 'cover' }}
                    onError={() => setComposerAvatarError(true)}
                  />
                ) : (
                  <span
                    className="avatar post-avatar-initials"
                    style={{
                      width: '44px',
                      height: '44px',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      background: 'linear-gradient(135deg, #176bff, #7146ed)',
                      color: '#fff',
                      fontWeight: 700,
                      borderRadius: '50%',
                    }}
                  >
                    {getInitials(displayName)}
                  </span>
                )}
                <div>
                  <div style={{ fontWeight: 700, fontSize: '0.95rem', color: '#0f172a' }}>{displayName}</div>
                  {communityName && (
                    <span style={{ fontSize: '0.75rem', color: '#64748b' }}>Posting in {communityName}</span>
                  )}
                </div>
              </div>

              {/* Text Input */}
              <textarea
                ref={textareaRef}
                autoFocus
                name="body"
                maxLength={5000}
                rows={4}
                placeholder={
                  placeholder ||
                  (businessPage
                    ? `Publish an update for ${businessPage.page_name}...`
                    : communityName
                    ? `Write something to ${communityName}...`
                    : `What's on your mind, ${currentUser?.name || 'Member'}?`)
                }
                value={body}
                onChange={(e) => setBody(e.target.value)}
                style={{
                  width: '100%',
                  border: 'none',
                  outline: 'none',
                  resize: 'none',
                  fontSize: '1.05rem',
                  lineHeight: 1.5,
                  color: '#0f172a',
                  fontFamily: 'inherit',
                  minHeight: '90px',
                  boxSizing: 'border-box',
                  background: 'transparent',
                }}
              />

              {/* Media Preview inside Modal */}
              {mediaPreviewUrl && (
                <div
                  style={{
                    position: 'relative',
                    borderRadius: '12px',
                    overflow: 'hidden',
                    border: '1px solid var(--color-border-soft, #e2e8f0)',
                    background: '#0f172a',
                  }}
                >
                  <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center' }}>
                    {isVideo ? (
                      <video
                        src={mediaPreviewUrl}
                        controls
                        playsInline
                        style={{ width: '100%', maxHeight: '360px' }}
                      />
                    ) : (
                      <img
                        src={mediaPreviewUrl}
                        alt="Selected media preview"
                        style={{ width: '100%', maxHeight: '360px', objectFit: 'contain', display: 'block' }}
                      />
                    )}
                  </div>

                  <div
                    style={{
                      position: 'absolute',
                      top: '10px',
                      right: '10px',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '6px',
                      zIndex: 5,
                    }}
                  >
                    {!isVideo && (
                      <button
                        type="button"
                        onClick={() => setIsAdjustModalOpen(true)}
                        title="Adjust / Frame Image"
                        style={{
                          background: 'rgba(15, 23, 42, 0.85)',
                          backdropFilter: 'blur(4px)',
                          color: '#ffffff',
                          border: '1px solid rgba(255, 255, 255, 0.25)',
                          borderRadius: '20px',
                          padding: '5px 12px',
                          fontSize: '0.75rem',
                          fontWeight: 700,
                          display: 'inline-flex',
                          alignItems: 'center',
                          gap: '5px',
                          cursor: 'pointer',
                        }}
                      >
                        <Crop size={14} />
                        <span>Adjust Framing</span>
                      </button>
                    )}

                    <button
                      type="button"
                      aria-label="Remove selected media"
                      onClick={handleRemoveMedia}
                      style={{
                        width: '30px',
                        height: '30px',
                        borderRadius: '50%',
                        background: 'rgba(15, 23, 42, 0.85)',
                        color: '#ffffff',
                        border: '1px solid rgba(255, 255, 255, 0.25)',
                        display: 'grid',
                        placeItems: 'center',
                        cursor: 'pointer',
                      }}
                    >
                      <X size={16} aria-hidden="true" />
                    </button>
                  </div>
                </div>
              )}

              {/* Add to Post action bar */}
              <div
                style={{
                  padding: '12px 14px',
                  borderRadius: '12px',
                  border: '1px solid var(--color-border-soft, #e2e8f0)',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  background: '#f8fafc',
                }}
              >
                <span style={{ fontSize: '0.85rem', fontWeight: 600, color: '#475569' }}>
                  Add to your post
                </span>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <label
                    htmlFor="modal-post-media-input"
                    style={{
                      display: 'inline-flex',
                      alignItems: 'center',
                      gap: '6px',
                      padding: '6px 12px',
                      borderRadius: '8px',
                      background: '#ffffff',
                      border: '1px solid #cbd5e1',
                      cursor: 'pointer',
                      fontSize: '0.8rem',
                      fontWeight: 600,
                      color: '#334155',
                    }}
                  >
                    <Image size={18} color="#22c55e" />
                    <span>{canUploadVideo ? 'Photo / video' : 'Photo'}</span>
                    <input
                      id="modal-post-media-input"
                      type="file"
                      accept={canUploadVideo ? 'image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime' : 'image/jpeg,image/png,image/webp'}
                      onChange={handleMediaChange}
                      style={{ display: 'none' }}
                    />
                  </label>

                  {isCommunityAdmin && (
                    <button
                      type="button"
                      onClick={() => setIsAnnouncement((prev) => !prev)}
                      style={{
                        background: isAnnouncement ? 'rgba(234, 88, 12, 0.12)' : '#ffffff',
                        color: isAnnouncement ? '#ea580c' : '#334155',
                        border: isAnnouncement ? '1px solid #ea580c' : '1px solid #cbd5e1',
                        borderRadius: '8px',
                        padding: '6px 12px',
                        cursor: 'pointer',
                        fontSize: '0.8rem',
                        fontWeight: 600,
                        display: 'inline-flex',
                        alignItems: 'center',
                        gap: '6px',
                      }}
                    >
                      <Megaphone size={16} color={isAnnouncement ? '#ea580c' : 'currentColor'} />
                      <span>{isAnnouncement ? 'Announcement' : 'Mark Announcement'}</span>
                    </button>
                  )}
                </div>
              </div>

              {feedback.message && (
                <p
                  className="composer__feedback"
                  style={{
                    margin: 0,
                    fontSize: '0.85rem',
                    color: feedback.type === 'error' ? 'var(--color-danger, #ff4168)' : 'var(--color-success, #20c875)',
                    fontWeight: 500,
                  }}
                >
                  {feedback.message}
                </p>
              )}
            </div>

            {/* Modal Footer Submit */}
            <footer style={{ padding: '14px 20px', borderTop: '1px solid var(--color-border-soft, #f1f5f9)' }}>
              <button
                type="button"
                className="member-button member-button--primary"
                onClick={handleSubmit}
                disabled={isSubmitting || (!body.trim() && !mediaFile)}
                style={{
                  width: '100%',
                  padding: '12px',
                  fontSize: '0.95rem',
                  fontWeight: 700,
                  justifyContent: 'center',
                }}
              >
                <Send size={16} aria-hidden="true" />
                <span>{isSubmitting ? 'Posting...' : 'Post'}</span>
              </button>
            </footer>
          </div>
        </ModalPortal>
      )}

      {/* Pre-Publish Image Adjustment Modal */}
      {isAdjustModalOpen && rawImageFile && (
        <ImageAdjustmentModal
          isOpen={isAdjustModalOpen}
          file={rawImageFile}
          initialAdjustment={adjustmentState}
          onApply={handleApplyAdjustment}
          onCancel={handleCancelAdjustment}
          currentUser={currentUser}
          postBody={body}
        />
      )}

      {/* Pre-Flight Client-Side Image Moderation Modal */}
      <ImageModerationScanModal
        isOpen={isScanModalOpen}
        status={scanStatus}
        progress={modProgress}
        decision={modDecision}
        error={modError}
        onCancel={() => {
          cancelModeration();
          setIsScanModalOpen(false);
          setScanStatus('IDLE');
          setIsSubmitting(false);
        }}
        onRetry={() => {
          handleSubmit();
        }}
        onAcknowledge={() => {
          handleRemoveMedia();
          setIsScanModalOpen(false);
          setScanStatus('IDLE');
          setIsSubmitting(false);
        }}
      />
    </>
  );
}

export default PostComposer;
