import { useState, useEffect, useRef, useCallback } from 'react';
import { ModalPortal } from '../common/ModalPortal';
import {
  X,
  ZoomIn,
  ZoomOut,
  RotateCcw,
  Camera,
  Image as ImageIcon,
  AlertCircle,
  Loader2,
} from 'lucide-react';

export function ProfileMediaViewerModal({
  isOpen,
  type = 'avatar', // 'avatar' | 'cover'
  imageUrl,
  memberName = 'Member',
  onClose,
  onChangePhoto = null,
}) {
  const [zoom, setZoom] = useState(1);
  const [pan, setPan] = useState({ x: 0, y: 0 });
  const [isDragging, setIsDragging] = useState(false);
  const [dragStart, setDragStart] = useState({ x: 0, y: 0 });
  const [isLoading, setIsLoading] = useState(true);
  const [hasError, setHasError] = useState(false);

  const containerRef = useRef(null);

  const isAvatar = type === 'avatar';
  const title = isAvatar ? `${memberName}'s Profile Photo` : `${memberName}'s Cover Photo`;
  const sublabel = isAvatar ? 'Profile Photo' : 'Cover Photo';

  // Reset zoom, pan, and loading state when modal opens or image URL changes
  useEffect(() => {
    if (isOpen) {
      setZoom(1);
      setPan({ x: 0, y: 0 });
      setIsLoading(true);
      setHasError(false);
      setIsDragging(false);
    }
  }, [isOpen, imageUrl]);

  // Handle wheel zoom via non-passive event listener
  useEffect(() => {
    const container = containerRef.current;
    if (!container || !isOpen) return;

    const handleWheelEvent = (e) => {
      e.preventDefault();
      const delta = e.deltaY < 0 ? 0.15 : -0.15;
      setZoom((prev) => Math.max(1, Math.min(4, parseFloat((prev + delta).toFixed(2)))));
    };

    container.addEventListener('wheel', handleWheelEvent, { passive: false });
    return () => container.removeEventListener('wheel', handleWheelEvent);
  }, [isOpen]);

  // Mouse Drag Handlers for Panning when Zoomed
  const handleMouseDown = (e) => {
    if (zoom <= 1) return;
    setIsDragging(true);
    setDragStart({
      x: e.clientX - pan.x,
      y: e.clientY - pan.y,
    });
  };

  const handleMouseMove = (e) => {
    if (!isDragging || zoom <= 1) return;
    setPan({
      x: e.clientX - dragStart.x,
      y: e.clientY - dragStart.y,
    });
  };

  const handleMouseUp = () => {
    setIsDragging(false);
  };

  // Touch Drag Handlers for Panning when Zoomed on Mobile
  const handleTouchStart = (e) => {
    if (zoom <= 1) return;
    if (e.touches && e.touches.length === 1) {
      const touch = e.touches[0];
      setIsDragging(true);
      setDragStart({
        x: touch.clientX - pan.x,
        y: touch.clientY - pan.y,
      });
    }
  };

  const handleTouchMove = (e) => {
    if (!isDragging || zoom <= 1) return;
    if (e.touches && e.touches.length === 1) {
      const touch = e.touches[0];
      setPan({
        x: touch.clientX - dragStart.x,
        y: touch.clientY - dragStart.y,
      });
    }
  };

  const handleTouchEnd = () => {
    setIsDragging(false);
  };

  // Reset Zoom & Pan
  const handleResetZoom = useCallback(() => {
    setZoom(1);
    setPan({ x: 0, y: 0 });
  }, []);

  // Toggle Zoom on Double Click
  const handleDoubleClick = () => {
    if (zoom > 1) {
      handleResetZoom();
    } else {
      setZoom(2);
    }
  };

  if (!isOpen || !imageUrl) return null;

  return (
    <ModalPortal
      isOpen={isOpen}
      onClose={onClose}
      depth={1}
      containerStyle={{ padding: 0 }}
    >
      <div
        className="profile-media-viewer-overlay is-open"
        role="dialog"
        aria-modal="true"
        aria-labelledby="profile-media-viewer-title"
      >
        {/* Top Header Bar */}
        <header className="profile-media-viewer-header">
          {/* Row 1 on Mobile / Left on Desktop: Title & Info */}
          <div className="profile-media-viewer-header__top">
            <div className="profile-media-viewer-title-group">
              <span className="profile-media-viewer-icon" aria-hidden="true">
                <ImageIcon size={20} />
              </span>
              <div className="profile-media-viewer-text">
                <span className="profile-media-viewer-sublabel">{sublabel}</span>
                <h2 id="profile-media-viewer-title" className="profile-media-viewer-heading">
                  {title}
                </h2>
              </div>
            </div>

            {/* Mobile Close Button (anchored in Row 1 on mobile, hidden on desktop) */}
            <button
              type="button"
              className="profile-media-viewer-close-btn profile-media-viewer-close-btn--mobile"
              aria-label="Close viewer"
              onClick={onClose}
            >
              <X size={20} aria-hidden="true" />
            </button>
          </div>

          {/* Row 2 on Mobile / Right on Desktop: Action Controls */}
          <div className="profile-media-viewer-actions">
            {/* Zoom Controls */}
            <div className="profile-media-viewer-zoom-group">
              <button
                type="button"
                className="profile-media-viewer-zoom-btn"
                aria-label="Zoom Out"
                onClick={() => setZoom((prev) => Math.max(1, parseFloat((prev - 0.25).toFixed(2))))}
                disabled={zoom <= 1}
              >
                <ZoomOut size={16} aria-hidden="true" />
              </button>

              <span className="profile-media-viewer-zoom-level" aria-label={`Current zoom ${Math.round(zoom * 100)} percent`}>
                {Math.round(zoom * 100)}%
              </span>

              <button
                type="button"
                className="profile-media-viewer-zoom-btn"
                aria-label="Zoom In"
                onClick={() => setZoom((prev) => Math.min(4, parseFloat((prev + 0.25).toFixed(2))))}
                disabled={zoom >= 4}
              >
                <ZoomIn size={16} aria-hidden="true" />
              </button>

              {zoom > 1 && (
                <button
                  type="button"
                  className="profile-media-viewer-zoom-btn profile-media-viewer-zoom-btn--reset"
                  aria-label="Reset Zoom"
                  onClick={handleResetZoom}
                  title="Reset Zoom"
                >
                  <RotateCcw size={14} aria-hidden="true" />
                </button>
              )}
            </div>

            {/* Optional Change Photo Shortcut */}
            {onChangePhoto && (
              <button
                type="button"
                className="profile-media-viewer-change-btn"
                onClick={() => {
                  onClose?.();
                  onChangePhoto();
                }}
              >
                <Camera size={15} aria-hidden="true" />
                <span>{isAvatar ? 'Change Photo' : 'Change Cover'}</span>
              </button>
            )}

            {/* Desktop Close Button (hidden on mobile, visible on desktop) */}
            <button
              type="button"
              className="profile-media-viewer-close-btn profile-media-viewer-close-btn--desktop"
              aria-label="Close viewer"
              onClick={onClose}
            >
              <X size={20} aria-hidden="true" />
            </button>
          </div>
        </header>

        {/* Main Viewport Container */}
        <div
          ref={containerRef}
          className={`profile-media-viewer-stage ${type === 'cover' ? 'is-cover-stage' : 'is-avatar-stage'}`}
          onClick={(e) => {
            if (e.target === e.currentTarget) {
              onClose?.();
            }
          }}
          onMouseDown={handleMouseDown}
          onMouseMove={handleMouseMove}
          onMouseUp={handleMouseUp}
          onMouseLeave={handleMouseUp}
          onTouchStart={handleTouchStart}
          onTouchMove={handleTouchMove}
          onTouchEnd={handleTouchEnd}
          style={{
            cursor: zoom > 1 ? (isDragging ? 'grabbing' : 'grab') : 'default',
          }}
        >
          {/* Loading Spinner */}
          {isLoading && !hasError && (
            <div className="profile-media-viewer-loader">
              <Loader2 size={36} className="animate-spin" color="#818cf8" aria-hidden="true" />
              <span>Loading image...</span>
            </div>
          )}

          {/* Error Notice */}
          {hasError && (
            <div className="profile-media-viewer-error" role="alert">
              <AlertCircle size={36} aria-hidden="true" />
              <span>Unable to load image</span>
              <button
                type="button"
                className="member-button member-button--secondary"
                onClick={onClose}
              >
                Close
              </button>
            </div>
          )}

          {/* Rendered Full Image */}
          {!hasError && (
            <img
              src={imageUrl}
              alt={title}
              className={`profile-media-viewer-image ${isAvatar ? 'profile-media-viewer-image--avatar' : 'profile-media-viewer-image--cover'}`}
              onLoad={() => setIsLoading(false)}
              onError={() => {
                setIsLoading(false);
                setHasError(true);
              }}
              onDoubleClick={handleDoubleClick}
              style={{
                transform: `translate(${pan.x}px, ${pan.y}px) scale(${zoom})`,
                transition: isDragging ? 'none' : 'transform 0.15s ease-out',
                opacity: isLoading ? 0 : 1,
                pointerEvents: isLoading ? 'none' : 'auto',
              }}
            />
          )}
        </div>

        {/* Footer Info Hint */}
        <footer className="profile-media-viewer-footer">
          <span className="profile-media-viewer-footer-desktop">Double-click or scroll to zoom • Drag to pan • Press Esc to close</span>
          <span className="profile-media-viewer-footer-mobile">Double-tap to zoom • Drag to pan</span>
        </footer>
      </div>
    </ModalPortal>
  );
}

export default ProfileMediaViewerModal;
