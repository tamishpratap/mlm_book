import { useState, useEffect, useRef, useMemo, useCallback } from 'react';
import { ModalPortal } from '../common/ModalPortal';
import {
  X,
  Camera,
  ZoomIn,
  ZoomOut,
  RotateCcw,
  Check,
  Move,
  Trash2,
  AlertCircle,
} from 'lucide-react';


export function ProfileImageAdjustModal({
  isOpen,
  type = 'avatar', // 'avatar' | 'cover'
  file,
  onSave,
  onCancel,
  onRemove = null,
  hasExistingPhoto = false,
  isUploading = false,
  errorMessage = null,
}) {
  const [imageSrc, setImageSrc] = useState(null);
  const [naturalDimensions, setNaturalDimensions] = useState({ width: 0, height: 0 });
  const [zoom, setZoom] = useState(1);
  const [pan, setPan] = useState({ x: 0, y: 0 });
  const [isDragging, setIsDragging] = useState(false);
  const [dragStart, setDragStart] = useState({ x: 0, y: 0 });
  const [containerSize, setContainerSize] = useState({ width: 560, height: 380 });
  const [touchPinchDist, setTouchPinchDist] = useState(null);

  const containerRef = useRef(null);
  const imageElementRef = useRef(null);

  const isAvatar = type === 'avatar';
  const modalTitle = isAvatar ? 'Adjust Profile Photo' : 'Adjust Cover Photo';
  const modalSubtitle = isAvatar
    ? 'Drag to reposition and zoom your photo to fit the circular profile avatar.'
    : 'Drag to reposition and zoom your photo to fit the header cover banner.';

  // Target aspect ratio: 1:1 for Avatar, ~3.2:1 for Cover (matching Profile Header banner)
  const targetRatio = isAvatar ? 1 : 16 / 5; // 3.2:1



  // Load image safely using FileReader
  useEffect(() => {
    if (!file) {
      setImageSrc(null);
      setNaturalDimensions({ width: 0, height: 0 });
      return;
    }

    let isCancelled = false;
    const reader = new FileReader();

    reader.onload = (e) => {
      if (isCancelled) return;
      const dataUrl = e.target?.result;
      if (!dataUrl) return;

      setImageSrc(dataUrl);

      const img = new window.Image();
      img.onload = () => {
        if (isCancelled) return;
        setNaturalDimensions({
          width: img.naturalWidth || 800,
          height: img.naturalHeight || 600,
        });
        setZoom(1);
        setPan({ x: 0, y: 0 });
      };
      img.onerror = () => {
        console.error('[ProfileImageAdjustModal] Failed to decode image dimensions.');
      };
      img.src = dataUrl;
    };

    reader.readAsDataURL(file);

    return () => {
      isCancelled = true;
    };
  }, [file, type]);

  // Update container size dynamically on mount / resize
  useEffect(() => {
    if (!isOpen || !containerRef.current) return;

    const updateSize = () => {
      if (containerRef.current) {
        const rect = containerRef.current.getBoundingClientRect();
        setContainerSize({
          width: Math.max(280, Math.floor(rect.width || 560)),
          height: Math.max(260, Math.floor(rect.height || 380)),
        });
      }
    };

    updateSize();
    window.addEventListener('resize', updateSize);
    return () => window.removeEventListener('resize', updateSize);
  }, [isOpen]);

  // Handle wheel zoom via non-passive event listener
  useEffect(() => {
    const container = containerRef.current;
    if (!container) return;

    const handleWheelEvent = (e) => {
      e.preventDefault();
      const zoomDelta = e.deltaY < 0 ? 0.08 : -0.08;
      setZoom((prev) => Math.max(1, Math.min(3.5, parseFloat((prev + zoomDelta).toFixed(2)))));
    };

    container.addEventListener('wheel', handleWheelEvent, { passive: false });
    return () => container.removeEventListener('wheel', handleWheelEvent);
  }, [isOpen]);

  // Close on Escape key
  useEffect(() => {
    if (!isOpen) return;
    const handleKeyDown = (e) => {
      if (e.key === 'Escape' && !isUploading) onCancel?.();
    };
    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, isUploading, onCancel]);

  // Calculate crop frame geometry based on container and aspect ratio
  const padding = isAvatar ? 24 : 20;
  const maxAvailableWidth = Math.max(200, containerSize.width - padding * 2);
  const maxAvailableHeight = Math.max(160, containerSize.height - padding * 2);

  let frameWidth = isAvatar ? Math.min(260, maxAvailableWidth, maxAvailableHeight) : maxAvailableWidth;
  let frameHeight = frameWidth / targetRatio;

  if (frameHeight > maxAvailableHeight) {
    frameHeight = maxAvailableHeight;
    frameWidth = frameHeight * targetRatio;
  }

  // Base scale to ensure the image completely covers the crop box at zoom = 1
  let baseScale = 1;
  if (naturalDimensions.width > 0 && naturalDimensions.height > 0 && frameWidth > 0 && frameHeight > 0) {
    const scaleX = frameWidth / naturalDimensions.width;
    const scaleY = frameHeight / naturalDimensions.height;
    baseScale = Math.max(scaleX, scaleY);
  }

  const effectiveScale = Math.max(0.0001, baseScale * zoom);
  const renderedImgWidth = (naturalDimensions.width || frameWidth) * effectiveScale;
  const renderedImgHeight = (naturalDimensions.height || frameHeight) * effectiveScale;

  // Maximum allowed pan offsets so the image never pulls empty/blank space inside the frame
  const maxPanX = Math.max(0, (renderedImgWidth - frameWidth) / 2);
  const maxPanY = Math.max(0, (renderedImgHeight - frameHeight) / 2);

  // Clamp pan within valid bounds
  const clampedPan = useMemo(() => ({
    x: Math.max(-maxPanX, Math.min(maxPanX, pan.x || 0)),
    y: Math.max(-maxPanY, Math.min(maxPanY, pan.y || 0)),
  }), [maxPanX, maxPanY, pan.x, pan.y]);

  // Mouse & Touch Drag Handlers
  const handleStartDrag = (clientX, clientY) => {
    if (isUploading) return;
    setIsDragging(true);
    setDragStart({
      x: clientX - clampedPan.x,
      y: clientY - clampedPan.y,
    });
  };

  const handleMoveDrag = (clientX, clientY) => {
    if (!isDragging || isUploading) return;
    const newX = clientX - dragStart.x;
    const newY = clientY - dragStart.y;
    setPan({
      x: Math.max(-maxPanX, Math.min(maxPanX, newX)),
      y: Math.max(-maxPanY, Math.min(maxPanY, newY)),
    });
  };

  const handleEndDrag = () => {
    setIsDragging(false);
  };

  // Touch Pinch-to-Zoom
  const getTouchDistance = (touches) => {
    if (touches.length < 2) return 0;
    const dx = touches[0].clientX - touches[1].clientX;
    const dy = touches[0].clientY - touches[1].clientY;
    return Math.sqrt(dx * dx + dy * dy);
  };

  const handleTouchStart = (e) => {
    if (isUploading) return;
    if (e.touches.length === 1) {
      handleStartDrag(e.touches[0].clientX, e.touches[0].clientY);
    } else if (e.touches.length === 2) {
      setIsDragging(false);
      setTouchPinchDist(getTouchDistance(e.touches));
    }
  };

  const handleTouchMove = (e) => {
    if (isUploading) return;
    if (e.touches.length === 1 && isDragging) {
      handleMoveDrag(e.touches[0].clientX, e.touches[0].clientY);
    } else if (e.touches.length === 2 && touchPinchDist) {
      const newDist = getTouchDistance(e.touches);
      const diff = (newDist - touchPinchDist) / 200;
      setZoom((prev) => Math.max(1, Math.min(3.5, parseFloat((prev + diff).toFixed(2)))));
      setTouchPinchDist(newDist);
    }
  };

  const handleTouchEnd = () => {
    setIsDragging(false);
    setTouchPinchDist(null);
  };

  // Reset Position & Zoom
  const handleReset = () => {
    if (isUploading) return;
    setZoom(1);
    setPan({ x: 0, y: 0 });
  };

  // Generate cropped image Blob and trigger save
  const handleSave = useCallback(() => {
    if (!imageElementRef.current || naturalDimensions.width === 0 || naturalDimensions.height === 0 || !file || isUploading) {
      return;
    }

    try {
      // Calculate source image crop coordinates
      const cropWidthOnSource = frameWidth / effectiveScale;
      const cropHeightOnSource = frameHeight / effectiveScale;

      const cropXOnSource =
        (naturalDimensions.width - cropWidthOnSource) / 2 - clampedPan.x / effectiveScale;
      const cropYOnSource =
        (naturalDimensions.height - cropHeightOnSource) / 2 - clampedPan.y / effectiveScale;

      const sx = Math.max(0, Math.min(naturalDimensions.width - cropWidthOnSource, cropXOnSource));
      const sy = Math.max(0, Math.min(naturalDimensions.height - cropHeightOnSource, cropYOnSource));
      const sw = Math.min(naturalDimensions.width - sx, cropWidthOnSource);
      const sh = Math.min(naturalDimensions.height - sy, cropHeightOnSource);

      // Target canvas resolution (High Quality: 600x600 for avatar, 1440x450 for cover)
      let canvasWidth = isAvatar ? 600 : 1440;
      let canvasHeight = isAvatar ? 600 : Math.round(canvasWidth / targetRatio);

      // Avoid upscaling beyond source image crop resolution
      if (sw < canvasWidth) {
        canvasWidth = Math.max(isAvatar ? 300 : 720, Math.round(sw));
        canvasHeight = Math.round(canvasWidth / targetRatio);
      }

      const canvas = document.createElement('canvas');
      canvas.width = canvasWidth;
      canvas.height = canvasHeight;
      const ctx = canvas.getContext('2d');

      if (!ctx) {
        throw new Error('Canvas 2D context unavailable');
      }

      ctx.imageSmoothingEnabled = true;
      ctx.imageSmoothingQuality = 'high';

      // Draw cropped slice from source image
      ctx.drawImage(imageElementRef.current, sx, sy, sw, sh, 0, 0, canvasWidth, canvasHeight);

      const mimeType = file.type === 'image/png' ? 'image/png' : 'image/jpeg';
      const quality = mimeType === 'image/png' ? undefined : 0.92;

      canvas.toBlob(
        async (blob) => {
          if (!blob) {
            console.error('[ProfileImageAdjustModal] Canvas toBlob returned null');
            return;
          }

          const croppedFile = new File([blob], file.name || `${type}_photo.jpg`, {
            type: mimeType,
            lastModified: Date.now(),
          });

          onSave?.(croppedFile);
        },
        mimeType,
        quality
      );
    } catch (err) {
      console.error('[ProfileImageAdjustModal] Error cropping image:', err);
    }
  }, [
    imageElementRef,
    naturalDimensions,
    frameWidth,
    frameHeight,
    effectiveScale,
    clampedPan,
    targetRatio,
    file,
    isAvatar,
    type,
    isUploading,
    onSave,
  ]);

  if (!isOpen || !file) return null;

  return (
    <ModalPortal
      isOpen={isOpen}
      onClose={() => !isUploading && onCancel?.()}
      closeOnBackdropClick={!isUploading}
      closeOnEsc={!isUploading}
      depth={1}
    >
      {/* Modal Dialog Card */}
      <div
        className="profile-crop-modal-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="profile-crop-modal-title"
        style={{
          position: 'relative',
          maxWidth: isAvatar ? '520px' : '640px',
          width: '100%',
          maxHeight: '92vh',
          display: 'flex',
          flexDirection: 'column',
          borderRadius: '20px',
          overflow: 'hidden',
          background: '#ffffff',
          boxShadow: '0 25px 50px -12px rgba(15, 23, 42, 0.4)',
          border: '1px solid rgba(226, 232, 240, 0.8)',
        }}
      >
        {/* Header */}
        <header
          style={{
            padding: '18px 22px',
            borderBottom: '1px solid #f1f5f9',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            background: '#ffffff',
            flexShrink: 0,
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
            <span
              style={{
                width: '40px',
                height: '40px',
                borderRadius: '12px',
                background: 'linear-gradient(135deg, rgba(79, 70, 229, 0.12), rgba(124, 58, 237, 0.12))',
                color: '#4f46e5',
                display: 'grid',
                placeItems: 'center',
                flexShrink: 0,
              }}
            >
              <Camera size={20} />
            </span>
            <div>
              <span
                style={{
                  fontSize: '0.72rem',
                  fontWeight: 700,
                  color: '#6366f1',
                  textTransform: 'uppercase',
                  letterSpacing: '0.06em',
                  display: 'block',
                  marginBottom: '2px',
                }}
              >
                Preview & Crop
              </span>
              <h2
                id="profile-crop-modal-title"
                style={{
                  margin: 0,
                  fontSize: '1.2rem',
                  fontWeight: 800,
                  color: '#0f172a',
                }}
              >
                {modalTitle}
              </h2>
            </div>
          </div>

          <button
            type="button"
            aria-label="Close modal"
            onClick={onCancel}
            disabled={isUploading}
            style={{
              width: '34px',
              height: '34px',
              borderRadius: '50%',
              background: '#f1f5f9',
              border: 'none',
              display: 'grid',
              placeItems: 'center',
              cursor: isUploading ? 'not-allowed' : 'pointer',
              color: '#475569',
              transition: 'background 0.15s ease',
            }}
          >
            <X size={18} aria-hidden="true" />
          </button>
        </header>

        {/* Modal Body */}
        <div
          style={{
            flex: 1,
            overflowY: 'auto',
            padding: '20px 22px',
            display: 'flex',
            flexDirection: 'column',
            gap: '16px',
          }}
        >
          {/* Subtitle / Instructions */}
          <p style={{ margin: 0, fontSize: '0.85rem', color: '#64748b', lineHeight: 1.45 }}>
            {modalSubtitle}
          </p>

          {/* Error Banner if any */}
          {errorMessage && (
            <div
              style={{
                padding: '12px 16px',
                background: '#fee2e2',
                color: '#b91c1c',
                borderRadius: '12px',
                fontSize: '0.85rem',
                display: 'flex',
                alignItems: 'center',
                gap: '10px',
                border: '1px solid #fca5a5',
              }}
              role="alert"
            >
              <AlertCircle size={16} style={{ flexShrink: 0 }} />
              <span>{errorMessage}</span>
            </div>
          )}

          {/* Interactive Crop Viewport */}
          <div
            ref={containerRef}
            onMouseDown={(e) => handleStartDrag(e.clientX, e.clientY)}
            onMouseMove={(e) => handleMoveDrag(e.clientX, e.clientY)}
            onMouseUp={handleEndDrag}
            onMouseLeave={handleEndDrag}
            onTouchStart={handleTouchStart}
            onTouchMove={handleTouchMove}
            onTouchEnd={handleTouchEnd}
            onTouchCancel={handleTouchEnd}
            style={{
              position: 'relative',
              width: '100%',
              height: isAvatar ? '320px' : '260px',
              background: '#0f172a',
              borderRadius: '14px',
              overflow: 'hidden',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              cursor: isUploading ? 'wait' : isDragging ? 'grabbing' : 'grab',
              userSelect: 'none',
              touchAction: 'none',
            }}
          >
            {/* Hidden image element used for canvas rendering */}
            {imageSrc && (
              <img
                ref={imageElementRef}
                src={imageSrc}
                alt="Crop Source"
                style={{ display: 'none' }}
              />
            )}

            {/* Cropper Box Overlay with Frame */}
            <div
              style={{
                position: 'relative',
                width: `${frameWidth}px`,
                height: `${frameHeight}px`,
                boxShadow: '0 0 0 9999px rgba(15, 23, 42, 0.75)',
                border: '3px solid #4f46e5',
                borderRadius: isAvatar ? '50%' : '14px',
                overflow: 'hidden',
                pointerEvents: 'none',
                transition: 'border-color 0.15s ease',
              }}
            >
              {/* Rendered Positioned & Scaled Image */}
              {imageSrc && (
                <img
                  src={imageSrc}
                  alt="Crop Preview"
                  style={{
                    position: 'absolute',
                    left: '50%',
                    top: '50%',
                    width: `${renderedImgWidth}px`,
                    height: `${renderedImgHeight}px`,
                    maxWidth: 'none',
                    maxHeight: 'none',
                    transform: `translate(-50%, -50%) translate(${clampedPan.x}px, ${clampedPan.y}px)`,
                    pointerEvents: 'none',
                    objectFit: 'fill',
                  }}
                />
              )}

              {/* Grid Lines Overlay */}
              <div
                style={{
                  position: 'absolute',
                  inset: 0,
                  display: 'grid',
                  gridTemplateColumns: '1fr 1fr 1fr',
                  gridTemplateRows: '1fr 1fr 1fr',
                  pointerEvents: 'none',
                  border: isAvatar ? 'none' : '1px solid rgba(255, 255, 255, 0.15)',
                  borderRadius: isAvatar ? '50%' : '14px',
                }}
              >
                <div style={{ borderRight: '1px solid rgba(255, 255, 255, 0.2)', borderBottom: '1px solid rgba(255, 255, 255, 0.2)' }} />
                <div style={{ borderRight: '1px solid rgba(255, 255, 255, 0.2)', borderBottom: '1px solid rgba(255, 255, 255, 0.2)' }} />
                <div style={{ borderBottom: '1px solid rgba(255, 255, 255, 0.2)' }} />
                <div style={{ borderRight: '1px solid rgba(255, 255, 255, 0.2)', borderBottom: '1px solid rgba(255, 255, 255, 0.2)' }} />
                <div style={{ borderRight: '1px solid rgba(255, 255, 255, 0.2)', borderBottom: '1px solid rgba(255, 255, 255, 0.2)' }} />
                <div style={{ borderBottom: '1px solid rgba(255, 255, 255, 0.2)' }} />
                <div style={{ borderRight: '1px solid rgba(255, 255, 255, 0.2)' }} />
                <div style={{ borderRight: '1px solid rgba(255, 255, 255, 0.2)' }} />
                <div />
              </div>

              {/* Floating Drag Hint */}
              <div
                style={{
                  position: 'absolute',
                  bottom: '10px',
                  left: '50%',
                  transform: 'translateX(-50%)',
                  background: 'rgba(15, 23, 42, 0.8)',
                  backdropFilter: 'blur(4px)',
                  color: '#ffffff',
                  padding: '4px 10px',
                  borderRadius: '12px',
                  fontSize: '0.72rem',
                  fontWeight: 600,
                  display: 'flex',
                  alignItems: 'center',
                  gap: '5px',
                  pointerEvents: 'none',
                  whiteSpace: 'nowrap',
                }}
              >
                <Move size={12} />
                <span>Drag to reposition framing</span>
              </div>
            </div>
          </div>

          {/* Zoom Controls & Slider */}
          <div
            style={{
              background: '#f8fafc',
              padding: '14px 18px',
              borderRadius: '14px',
              border: '1px solid #e2e8f0',
              display: 'flex',
              flexDirection: 'column',
              gap: '10px',
            }}
          >
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
              <span style={{ fontSize: '0.82rem', fontWeight: 700, color: '#334155', display: 'flex', alignItems: 'center', gap: '6px' }}>
                <ZoomIn size={15} color="#4f46e5" />
                <span>Zoom Level</span>
              </span>
              <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                <span style={{ fontSize: '0.8rem', fontWeight: 700, color: '#64748b' }}>
                  {Math.round(zoom * 100)}%
                </span>
                <button
                  type="button"
                  onClick={handleReset}
                  disabled={isUploading}
                  title="Reset position and zoom"
                  style={{
                    background: '#ffffff',
                    border: '1px solid #cbd5e1',
                    borderRadius: '8px',
                    padding: '4px 10px',
                    fontSize: '0.75rem',
                    fontWeight: 600,
                    color: '#475569',
                    cursor: isUploading ? 'not-allowed' : 'pointer',
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: '4px',
                  }}
                >
                  <RotateCcw size={12} />
                  <span>Reset</span>
                </button>
              </div>
            </div>

            <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
              <button
                type="button"
                aria-label="Zoom Out"
                onClick={() => setZoom((prev) => Math.max(1, parseFloat((prev - 0.1).toFixed(2))))}
                disabled={zoom <= 1 || isUploading}
                style={{
                  width: '32px',
                  height: '32px',
                  borderRadius: '8px',
                  background: '#ffffff',
                  border: '1px solid #cbd5e1',
                  display: 'grid',
                  placeItems: 'center',
                  cursor: zoom <= 1 || isUploading ? 'not-allowed' : 'pointer',
                  opacity: zoom <= 1 ? 0.5 : 1,
                  color: '#334155',
                }}
              >
                <ZoomOut size={16} />
              </button>

              <input
                type="range"
                min="1"
                max="3.5"
                step="0.01"
                value={zoom}
                disabled={isUploading}
                onChange={(e) => setZoom(parseFloat(e.target.value) || 1)}
                style={{
                  flex: 1,
                  accentColor: '#4f46e5',
                  cursor: isUploading ? 'not-allowed' : 'pointer',
                  height: '6px',
                }}
              />

              <button
                type="button"
                aria-label="Zoom In"
                onClick={() => setZoom((prev) => Math.min(3.5, parseFloat((prev + 0.1).toFixed(2))))}
                disabled={zoom >= 3.5 || isUploading}
                style={{
                  width: '32px',
                  height: '32px',
                  borderRadius: '8px',
                  background: '#ffffff',
                  border: '1px solid #cbd5e1',
                  display: 'grid',
                  placeItems: 'center',
                  cursor: zoom >= 3.5 || isUploading ? 'not-allowed' : 'pointer',
                  opacity: zoom >= 3.5 ? 0.5 : 1,
                  color: '#334155',
                }}
              >
                <ZoomIn size={16} />
              </button>
            </div>
          </div>
        </div>

        {/* Modal Footer Actions */}
        <footer
          style={{
            padding: '16px 22px',
            borderTop: '1px solid #f1f5f9',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            background: '#ffffff',
            gap: '12px',
            flexShrink: 0,
          }}
        >
          {/* Optional Remove Photo */}
          {hasExistingPhoto && onRemove ? (
            <button
              type="button"
              className="member-button member-button--danger"
              onClick={onRemove}
              disabled={isUploading}
              style={{ fontSize: '13px', padding: '8px 14px' }}
            >
              <Trash2 size={15} />
              <span>{isAvatar ? 'Remove Photo' : 'Remove Cover'}</span>
            </button>
          ) : (
            <div />
          )}

          <div style={{ display: 'flex', gap: '10px' }}>
            <button
              type="button"
              className="member-button member-button--secondary"
              onClick={onCancel}
              disabled={isUploading}
              style={{ minWidth: '90px' }}
            >
              Cancel
            </button>
            <button
              type="button"
              className="member-button member-button--primary"
              onClick={handleSave}
              disabled={isUploading}
              style={{ minWidth: '130px' }}
            >
              <Check size={16} />
              <span>{isUploading ? 'Saving...' : isAvatar ? 'Save Photo' : 'Save Cover'}</span>
            </button>
          </div>
        </footer>
      </div>

    </ModalPortal>
  );
}

export default ProfileImageAdjustModal;
