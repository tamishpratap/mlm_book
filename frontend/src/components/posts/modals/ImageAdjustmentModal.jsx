import { useState, useEffect, useRef, useMemo, useCallback } from 'react';
import {
  X,
  Crop,
  ZoomIn,
  ZoomOut,
  RotateCcw,
  Check,
  Eye,
  Sliders,
  Sparkles,
  Move,
  Clock,
  MapPin,
  Video,
  Users,
} from 'lucide-react';
import { getAvatarUrl, getInitials } from '../../../utils/assetHelper';
import { ModalPortal } from '../../common/ModalPortal';

const ASPECT_RATIOS = [
  { id: 'original', label: 'Original', value: null },
  { id: '16:9', label: '16:9 (Wide)', value: 16 / 9 },
  { id: '1:1', label: '1:1 (Square)', value: 1 },
  { id: '4:5', label: '4:5 (Portrait)', value: 4 / 5 },
  { id: '4:3', label: '4:3 (Standard)', value: 4 / 3 },
];

export function ImageAdjustmentModal({
  isOpen,
  file,
  initialAdjustment = null,
  onApply,
  onCancel,
  currentUser = null,
  postBody = '',
  previewMode = 'post',
  eventData = null,
  businessData = null,
  communityData = null,
  defaultAspectRatioId = null,
}) {
  const [imageSrc, setImageSrc] = useState(null);
  const [naturalDimensions, setNaturalDimensions] = useState({ width: 0, height: 0 });
  const [aspectRatioId, setAspectRatioId] = useState(
    initialAdjustment?.aspectRatioId ||
      defaultAspectRatioId ||
      (previewMode === 'business_logo' || previewMode === 'community_logo'
        ? '1:1'
        : previewMode === 'event' || previewMode === 'business_cover' || previewMode === 'community_cover'
        ? '16:9'
        : '16:9')
  );
  const [zoom, setZoom] = useState(initialAdjustment?.zoom || 1);
  const [pan, setPan] = useState(initialAdjustment?.pan || { x: 0, y: 0 });
  const [activeTab, setActiveTab] = useState('adjust'); // 'adjust' | 'preview'
  const [isDragging, setIsDragging] = useState(false);
  const [dragStart, setDragStart] = useState({ x: 0, y: 0 });
  const [containerSize, setContainerSize] = useState({ width: 480, height: 360 });
  const [isProcessing, setIsProcessing] = useState(false);
  const [applyError, setApplyError] = useState(null);

  const containerRef = useRef(null);
  const imageElementRef = useRef(null);

  // Load image safely using FileReader to avoid premature object URL revocation
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

        // Set intelligent default aspect ratio if not previously set
        if (!initialAdjustment) {
          if (defaultAspectRatioId) {
            setAspectRatioId(defaultAspectRatioId);
          } else if (previewMode === 'business_logo' || previewMode === 'community_logo') {
            setAspectRatioId('1:1');
          } else if (previewMode === 'event' || previewMode === 'business_cover' || previewMode === 'community_cover') {
            setAspectRatioId('16:9');
          } else {
            const naturalRatio = (img.naturalWidth || 800) / (img.naturalHeight || 600);
            if (Math.abs(naturalRatio - 1) < 0.15) {
              setAspectRatioId('1:1');
            } else if (naturalRatio < 0.85) {
              setAspectRatioId('4:5');
            } else {
              setAspectRatioId('16:9');
            }
          }
          setZoom(1);
          setPan({ x: 0, y: 0 });
        }
      };
      img.onerror = () => {
        console.error('[ImageAdjustmentModal] Failed to decode image dimensions.');
      };
      img.src = dataUrl;
    };

    reader.onerror = () => {
      console.error('[ImageAdjustmentModal] FileReader error reading file.');
    };

    reader.readAsDataURL(file);

    return () => {
      isCancelled = true;
    };
  }, [file, initialAdjustment, defaultAspectRatioId, previewMode]);

  // Update container size on mount / resize (only when adjust tab is active)
  useEffect(() => {
    if (!isOpen || activeTab !== 'adjust' || !containerRef.current) return;

    const updateSize = () => {
      if (containerRef.current) {
        const rect = containerRef.current.getBoundingClientRect();
        if (rect.width > 0 && rect.height > 0) {
          setContainerSize({
            width: Math.max(180, Math.floor(rect.width)),
            height: Math.max(180, Math.floor(rect.height)),
          });
        }
      }
    };

    updateSize();
    window.addEventListener('resize', updateSize);
    return () => window.removeEventListener('resize', updateSize);
  }, [isOpen, activeTab]);

  // Handle wheel zoom via non-passive event listener
  useEffect(() => {
    const container = containerRef.current;
    if (!container) return;

    const handleWheelEvent = (e) => {
      e.preventDefault();
      const zoomDelta = e.deltaY < 0 ? 0.08 : -0.08;
      setZoom((prev) => Math.max(1, Math.min(3, parseFloat((prev + zoomDelta).toFixed(2)))));
    };

    container.addEventListener('wheel', handleWheelEvent, { passive: false });
    return () => container.removeEventListener('wheel', handleWheelEvent);
  }, [activeTab]);

  // Close on Escape key
  useEffect(() => {
    if (!isOpen) return;
    const handleKeyDown = (e) => {
      if (e.key === 'Escape') onCancel?.();
    };
    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, onCancel]);

  // Calculate crop frame geometry
  const selectedRatioConfig = ASPECT_RATIOS.find((r) => r.id === aspectRatioId) || ASPECT_RATIOS[0];
  const targetRatio =
    selectedRatioConfig.value ||
    (naturalDimensions.width > 0 && naturalDimensions.height > 0
      ? naturalDimensions.width / naturalDimensions.height
      : 16 / 9);

  // Maximum inner crop box within container
  const padding = 20;
  const maxAvailableWidth = Math.max(200, containerSize.width - padding * 2);
  const maxAvailableHeight = Math.max(160, containerSize.height - padding * 2);

  let frameWidth = maxAvailableWidth;
  let frameHeight = frameWidth / targetRatio;

  if (frameHeight > maxAvailableHeight) {
    frameHeight = maxAvailableHeight;
    frameWidth = frameHeight * targetRatio;
  }

  // Base scale to ensure the image covers the crop frame completely at zoom = 1
  let baseScale = 1;
  if (naturalDimensions.width > 0 && naturalDimensions.height > 0 && frameWidth > 0 && frameHeight > 0) {
    const scaleX = frameWidth / naturalDimensions.width;
    const scaleY = frameHeight / naturalDimensions.height;
    baseScale = Math.max(scaleX, scaleY);
  }

  const effectiveScale = Math.max(0.001, baseScale * zoom);
  const renderedImgWidth = (naturalDimensions.width || frameWidth) * effectiveScale;
  const renderedImgHeight = (naturalDimensions.height || frameHeight) * effectiveScale;

  // Maximum allowed pan offsets so the image doesn't pull empty space into the frame
  const maxPanX = Math.max(0, (renderedImgWidth - frameWidth) / 2);
  const maxPanY = Math.max(0, (renderedImgHeight - frameHeight) / 2);

  // Clamp pan within valid bounds
  const clampedPan = useMemo(() => ({
    x: Math.max(-maxPanX, Math.min(maxPanX, pan.x || 0)),
    y: Math.max(-maxPanY, Math.min(maxPanY, pan.y || 0)),
  }), [maxPanX, maxPanY, pan.x, pan.y]);

  // Drag handlers for mouse & touch
  const handleStartDrag = (clientX, clientY) => {
    setIsDragging(true);
    setDragStart({
      x: clientX - clampedPan.x,
      y: clientY - clampedPan.y,
    });
  };

  const handleMoveDrag = (clientX, clientY) => {
    if (!isDragging) return;
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

  // Reset framing
  const handleReset = () => {
    setZoom(1);
    setPan({ x: 0, y: 0 });
  };

  // Compute crop coordinates on source image and export canvas
  const handleApply = useCallback(async () => {
    if (isProcessing) return;
    if (!file || !imageSrc) {
      return;
    }

    setIsProcessing(true);
    setApplyError(null);

    try {
      // Ensure source image element is available and loaded
      let sourceImg = imageElementRef.current;
      if (!sourceImg || !sourceImg.complete || sourceImg.naturalWidth === 0) {
        sourceImg = await new Promise((resolve, reject) => {
          const img = new window.Image();
          img.crossOrigin = 'anonymous';
          img.onload = () => resolve(img);
          img.onerror = () => reject(new Error('Unable to decode source image.'));
          img.src = imageSrc;
        });
      }

      const natWidth = naturalDimensions.width || sourceImg.naturalWidth || sourceImg.width || 800;
      const natHeight = naturalDimensions.height || sourceImg.naturalHeight || sourceImg.height || 600;

      // Source image crop dimensions
      const cropWidthOnSource = Math.max(1, frameWidth / effectiveScale);
      const cropHeightOnSource = Math.max(1, frameHeight / effectiveScale);

      // Top-left on source image
      const cropXOnSource =
        (natWidth - cropWidthOnSource) / 2 - clampedPan.x / effectiveScale;
      const cropYOnSource =
        (natHeight - cropHeightOnSource) / 2 - clampedPan.y / effectiveScale;

      // Bounded source coordinates
      const sx = Math.max(0, Math.min(natWidth - cropWidthOnSource, cropXOnSource));
      const sy = Math.max(0, Math.min(natHeight - cropHeightOnSource, cropYOnSource));
      const sw = Math.max(1, Math.min(natWidth - sx, cropWidthOnSource));
      const sh = Math.max(1, Math.min(natHeight - sy, cropHeightOnSource));

      // Target canvas resolution (high resolution up to 2048px width)
      const maxCanvasWidth = Math.min(2048, Math.max(1080, Math.round(sw)));
      const canvasWidth = Math.max(100, maxCanvasWidth);
      const canvasHeight = Math.max(100, Math.round(canvasWidth / targetRatio));

      const canvas = document.createElement('canvas');
      canvas.width = canvasWidth;
      canvas.height = canvasHeight;
      const ctx = canvas.getContext('2d');

      if (!ctx) {
        throw new Error('Canvas 2D context not available');
      }

      ctx.imageSmoothingEnabled = true;
      ctx.imageSmoothingQuality = 'high';

      // Draw cropped slice from source image
      ctx.drawImage(sourceImg, sx, sy, sw, sh, 0, 0, canvasWidth, canvasHeight);

      const mimeType = file.type || 'image/jpeg';
      const quality = mimeType === 'image/png' ? undefined : 0.92;

      const blob = await new Promise((resolve) => {
        canvas.toBlob(
          (b) => {
            if (b) {
              resolve(b);
            } else {
              // Fallback to dataURL conversion if toBlob returned null
              try {
                const dataUrl = canvas.toDataURL(mimeType, quality);
                const arr = dataUrl.split(',');
                const bstr = atob(arr[1]);
                let n = bstr.length;
                const u8arr = new Uint8Array(n);
                while (n--) {
                  u8arr[n] = bstr.charCodeAt(n);
                }
                resolve(new Blob([u8arr], { type: mimeType }));
              } catch {
                resolve(null);
              }
            }
          },
          mimeType,
          quality
        );
      });

      if (!blob) {
        throw new Error('Failed to generate image blob');
      }

      const adjustedFile = new File([blob], file.name, {
        type: mimeType,
        lastModified: Date.now(),
      });

      const previewUrl = URL.createObjectURL(blob);

      onApply?.({
        file: adjustedFile,
        previewUrl,
        adjustmentState: {
          aspectRatioId,
          zoom,
          pan: clampedPan,
          outputDimensions: { width: canvasWidth, height: canvasHeight },
        },
      });

      setIsProcessing(false);
    } catch (err) {
      console.error('[ImageAdjustmentModal] Error cropping image:', err);
      setApplyError('Failed to apply image framing. Please try again.');
      setIsProcessing(false);
    }
  }, [
    isProcessing,
    file,
    imageSrc,
    imageElementRef,
    naturalDimensions,
    frameWidth,
    frameHeight,
    effectiveScale,
    clampedPan,
    targetRatio,
    aspectRatioId,
    zoom,
    onApply,
  ]);

  if (!isOpen || !file) return null;

  // Approximate output dimensions display
  const approxOutputWidth = naturalDimensions.width > 0 ? Math.round(frameWidth / effectiveScale) : 0;
  const approxOutputHeight = naturalDimensions.height > 0 ? Math.round(frameHeight / effectiveScale) : 0;

  const userAvatar = currentUser?.profile_photo ? getAvatarUrl(currentUser.profile_photo) : null;
  const userInitials = getInitials(currentUser?.name || 'Member');
  const previewScaleRatio = frameWidth > 0 ? maxAvailableWidth / frameWidth : 1;

  // Event preview helper variables
  const eventDateObj = eventData?.start_date ? new Date(eventData.start_date) : new Date();
  const eventMonth = !isNaN(eventDateObj.getTime())
    ? eventDateObj.toLocaleString('en-US', { month: 'short' }).toUpperCase()
    : 'JAN';
  const eventDay = !isNaN(eventDateObj.getTime())
    ? eventDateObj.getDate().toString().padStart(2, '0')
    : '01';

  let formattedEventTime = 'All Day';
  if (eventData?.start_time) {
    try {
      const [hours, minutes] = eventData.start_time.split(':');
      const h = parseInt(hours, 10);
      const ampm = h >= 12 ? 'PM' : 'AM';
      const formattedHours = h % 12 || 12;
      formattedEventTime = `${formattedHours}:${minutes} ${ampm}`;
    } catch {
      formattedEventTime = eventData.start_time;
    }
  }
  const isOnlineEvent = eventData?.event_type === 'online';
  const eventLocationLabel = isOnlineEvent
    ? 'Online Event'
    : (eventData?.location_address || eventData?.location_city || 'In-Person');

  // Business preview helper variables
  const bizName = businessData?.page_name || 'Your Business Name';
  const bizUsername = businessData?.page_username || 'business_brand';
  const bizCategory = businessData?.category || 'Enterprise MLM Plan';
  const bizInitials = bizName
    ? bizName
        .split(' ')
        .map((w) => w[0])
        .join('')
        .substring(0, 2)
        .toUpperCase()
    : 'BP';

  // Community preview helper variables
  const commName = communityData?.name || 'Your Community Name';
  const commCategory = communityData?.category || 'Community';
  const commInitials = commName
    ? commName
        .split(' ')
        .map((w) => w[0])
        .join('')
        .substring(0, 2)
        .toUpperCase()
    : 'CO';

  const logoPreviewSize = Math.min(140, Math.min(frameWidth, frameHeight));
  const logoScaleRatio = frameWidth > 0 ? logoPreviewSize / frameWidth : 1;

  const coverPreviewWidth = Math.min(maxAvailableWidth, 480);
  const coverScaleRatio = frameWidth > 0 ? coverPreviewWidth / frameWidth : 1;
  const coverPreviewHeight = Math.round(coverPreviewWidth / (targetRatio || (16 / 9)));

  return (
    <ModalPortal
      isOpen={isOpen}
      onClose={onCancel}
      depth={1}
      overlayClassName="image-adjustment-modal-overlay"
      containerStyle={{
        height: '100dvh',
        maxHeight: '100dvh',
      }}
    >
      <div
        className="story-modal__panel image-adjustment-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="image-adjust-modal-title"
        style={{
          position: 'relative',
          zIndex: 1,
          maxWidth: '680px',
          width: '100%',
          maxHeight: 'min(92vh, calc(100dvh - 32px))',
          display: 'flex',
          flexDirection: 'column',
          borderRadius: '18px',
          overflow: 'hidden',
          background: '#ffffff',
          boxShadow: '0 25px 50px -12px rgba(15, 23, 42, 0.35)',
          opacity: 1,
        }}
      >
        {/* Persistent source image element for canvas cropping operations - always mounted regardless of active tab */}
        {imageSrc && (
          <img
            ref={imageElementRef}
            src={imageSrc}
            alt=""
            style={{ display: 'none' }}
            aria-hidden="true"
          />
        )}
        {/* Header */}
        <header
          className="image-adjustment-modal__header"
          style={{
            padding: '16px 20px',
            borderBottom: '1px solid var(--color-border-soft, #f1f5f9)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            background: '#ffffff',
            flexShrink: 0,
            gap: '12px',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '12px', flex: 1, minWidth: 0 }}>
            <span
              className="image-adjustment-modal__header-icon"
              style={{
                width: '38px',
                height: '38px',
                borderRadius: '10px',
                background: 'rgba(79, 125, 243, 0.12)',
                color: '#4f7df3',
                display: 'grid',
                placeItems: 'center',
                flexShrink: 0,
              }}
            >
              <Crop size={20} />
            </span>
            <div style={{ flex: 1, minWidth: 0 }}>
              <span
                style={{
                  fontSize: '0.72rem',
                  fontWeight: 700,
                  color: 'var(--color-text-secondary, #64748b)',
                  textTransform: 'uppercase',
                  letterSpacing: '0.05em',
                  display: 'block',
                  whiteSpace: 'nowrap',
                  overflow: 'hidden',
                  textOverflow: 'ellipsis',
                }}
              >
                {previewMode === 'event'
                  ? 'Event Cover Photo Editor'
                  : previewMode === 'business_logo'
                  ? 'Business Logo Editor'
                  : previewMode === 'community_logo'
                  ? 'Community Logo Editor'
                  : previewMode === 'business_cover'
                  ? 'Business Cover Banner Editor'
                  : previewMode === 'community_cover'
                  ? 'Community Cover Photo Editor'
                  : 'Pre-Publish Media Editor'}
              </span>
              <h2
                id="image-adjust-modal-title"
                className="image-adjustment-modal__header-title"
                style={{
                  margin: 0,
                  fontSize: '1.15rem',
                  fontWeight: 800,
                  color: 'var(--color-text-main, #0f172a)',
                  lineHeight: 1.25,
                }}
              >
                Adjust & Frame Image
              </h2>
            </div>
          </div>

          <button
            type="button"
            aria-label="Close"
            className="image-adjustment-modal__close-btn"
            onClick={onCancel}
            style={{
              width: '32px',
              height: '32px',
              borderRadius: '50%',
              background: '#f1f5f9',
              border: 'none',
              display: 'grid',
              placeItems: 'center',
              cursor: 'pointer',
              color: '#475569',
              transition: 'background 0.15s ease',
              flexShrink: 0,
            }}
          >
            <X size={18} aria-hidden="true" />
          </button>
        </header>

        {/* View Mode Tabs (Adjust vs Exact Preview) */}
        <div
          className="image-adjustment-modal__tabs"
          style={{
            display: 'flex',
            gap: '8px',
            padding: '8px 20px',
            background: 'var(--color-surface-alt, #f8fafc)',
            borderBottom: '1px solid var(--color-border-soft, #e2e8f0)',
            flexShrink: 0,
          }}
        >
          <button
            type="button"
            className="image-adjustment-modal__tab-btn"
            onClick={() => {
              setActiveTab('adjust');
              setApplyError(null);
            }}
            style={{
              flex: 1,
              minWidth: 0,
              padding: '8px 12px',
              borderRadius: '8px',
              border: activeTab === 'adjust' ? '1px solid #4f7df3' : '1px solid transparent',
              background: activeTab === 'adjust' ? '#ffffff' : 'transparent',
              color: activeTab === 'adjust' ? '#4f7df3' : '#64748b',
              fontWeight: activeTab === 'adjust' ? 700 : 600,
              fontSize: '0.825rem',
              display: 'inline-flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '6px',
              cursor: 'pointer',
              boxShadow: activeTab === 'adjust' ? '0 2px 4px rgba(0,0,0,0.05)' : 'none',
            }}
          >
            <Sliders size={15} style={{ flexShrink: 0 }} />
            <span style={{ minWidth: 0, overflowWrap: 'anywhere' }}>Interactive Crop & Zoom</span>
          </button>

          <button
            type="button"
            className="image-adjustment-modal__tab-btn"
            onClick={() => {
              setActiveTab('preview');
              setApplyError(null);
            }}
            style={{
              flex: 1,
              minWidth: 0,
              padding: '8px 12px',
              borderRadius: '8px',
              border: activeTab === 'preview' ? '1px solid #4f7df3' : '1px solid transparent',
              background: activeTab === 'preview' ? '#ffffff' : 'transparent',
              color: activeTab === 'preview' ? '#4f7df3' : '#64748b',
              fontWeight: activeTab === 'preview' ? 700 : 600,
              fontSize: '0.825rem',
              display: 'inline-flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '6px',
              cursor: 'pointer',
              boxShadow: activeTab === 'preview' ? '0 2px 4px rgba(0,0,0,0.05)' : 'none',
            }}
          >
            <Eye size={15} style={{ flexShrink: 0 }} />
            <span style={{ minWidth: 0, overflowWrap: 'anywhere' }}>
              {previewMode === 'event'
                ? 'Exact Event Card Preview'
                : previewMode === 'business_logo'
                ? 'Exact Business Logo Preview'
                : previewMode === 'community_logo'
                ? 'Exact Community Logo Preview'
                : previewMode === 'business_cover'
                ? 'Exact Business Cover Preview'
                : previewMode === 'community_cover'
                ? 'Exact Community Cover Preview'
                : 'Exact Post Card Preview'}
            </span>
          </button>
        </div>

        {/* Modal Body */}
        <div
          className="image-adjustment-modal__body"
          style={{
            flex: 1,
            minHeight: 0,
            overflowY: 'auto',
            padding: '16px 20px',
            display: 'flex',
            flexDirection: 'column',
            gap: '16px',
            WebkitOverflowScrolling: 'touch',
            overscrollBehavior: 'contain',
          }}
        >
          {activeTab === 'adjust' ? (
            <>
              {/* Interactive Crop Viewport */}
              <div
                ref={containerRef}
                onMouseDown={(e) => handleStartDrag(e.clientX, e.clientY)}
                onMouseMove={(e) => handleMoveDrag(e.clientX, e.clientY)}
                onMouseUp={handleEndDrag}
                onMouseLeave={handleEndDrag}
                onTouchStart={(e) => {
                  if (e.touches && e.touches.length === 1) {
                    handleStartDrag(e.touches[0].clientX, e.touches[0].clientY);
                  }
                }}
                onTouchMove={(e) => {
                  if (e.touches && e.touches.length === 1) {
                    handleMoveDrag(e.touches[0].clientX, e.touches[0].clientY);
                  }
                }}
                onTouchEnd={handleEndDrag}
                onTouchCancel={handleEndDrag}
                className="image-adjustment-modal__cropper-viewport"
                style={{
                  position: 'relative',
                  width: '100%',
                  height: '360px',
                  background: '#0f172a',
                  borderRadius: '12px',
                  overflow: 'hidden',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  cursor: isDragging ? 'grabbing' : 'grab',
                  userSelect: 'none',
                  touchAction: 'none',
                }}
              >
                {/* Visible Cropper Box (Active Published Boundary) */}
                <div
                  style={{
                    position: 'relative',
                    width: `${frameWidth}px`,
                    height: `${frameHeight}px`,
                    boxShadow: '0 0 0 9999px rgba(15, 23, 42, 0.75)',
                    border: '2px solid rgba(79, 125, 243, 0.95)',
                    borderRadius:
                      (previewMode === 'business_logo' || previewMode === 'community_logo') && (aspectRatioId === '1:1' || !aspectRatioId)
                        ? '50%'
                        : '8px',
                    overflow: 'hidden',
                    pointerEvents: 'none',
                  }}
                >
                  {/* The rendered draggable image */}
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

                  {/* Grid Lines (Rule of Thirds) */}
                  <div
                    style={{
                      position: 'absolute',
                      inset: 0,
                      display: 'grid',
                      gridTemplateColumns: '1fr 1fr 1fr',
                      gridTemplateRows: '1fr 1fr 1fr',
                      pointerEvents: 'none',
                      border: '1px solid rgba(255, 255, 255, 0.15)',
                    }}
                  >
                    <div style={{ borderRight: '1px solid rgba(255, 255, 255, 0.25)', borderBottom: '1px solid rgba(255, 255, 255, 0.25)' }} />
                    <div style={{ borderRight: '1px solid rgba(255, 255, 255, 0.25)', borderBottom: '1px solid rgba(255, 255, 255, 0.25)' }} />
                    <div style={{ borderBottom: '1px solid rgba(255, 255, 255, 0.25)' }} />
                    <div style={{ borderRight: '1px solid rgba(255, 255, 255, 0.25)', borderBottom: '1px solid rgba(255, 255, 255, 0.25)' }} />
                    <div style={{ borderRight: '1px solid rgba(255, 255, 255, 0.25)', borderBottom: '1px solid rgba(255, 255, 255, 0.25)' }} />
                    <div style={{ borderBottom: '1px solid rgba(255, 255, 255, 0.25)' }} />
                    <div style={{ borderRight: '1px solid rgba(255, 255, 255, 0.25)' }} />
                    <div style={{ borderRight: '1px solid rgba(255, 255, 255, 0.25)' }} />
                    <div />
                  </div>

                  {/* Drag Prompt Tooltip */}
                  <div
                    style={{
                      position: 'absolute',
                      bottom: '8px',
                      left: '50%',
                      transform: 'translateX(-50%)',
                      background: 'rgba(15, 23, 42, 0.75)',
                      backdropFilter: 'blur(4px)',
                      color: '#ffffff',
                      padding: '3px 10px',
                      borderRadius: '12px',
                      fontSize: '0.72rem',
                      fontWeight: 600,
                      display: 'flex',
                      alignItems: 'center',
                      gap: '5px',
                      pointerEvents: 'none',
                    }}
                  >
                    <Move size={12} />
                    <span>Drag image to reposition framing</span>
                  </div>
                </div>
              </div>

              {/* Aspect Ratio Selector */}
              <div>
                <label
                  style={{
                    display: 'block',
                    fontSize: '0.78rem',
                    fontWeight: 700,
                    color: 'var(--color-text-main, #0f172a)',
                    marginBottom: '8px',
                  }}
                >
                  Aspect Ratio Frame
                </label>
                <div
                  style={{
                    display: 'grid',
                    gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 100px), 1fr))',
                    gap: '8px',
                  }}
                >
                  {ASPECT_RATIOS.map((ratio) => {
                    const isSelected = aspectRatioId === ratio.id;
                    return (
                      <button
                        key={ratio.id}
                        type="button"
                        onClick={() => {
                          setAspectRatioId(ratio.id);
                          setPan({ x: 0, y: 0 });
                          setZoom(1);
                        }}
                        style={{
                          padding: '7px 10px',
                          borderRadius: '8px',
                          border: isSelected ? '2px solid #4f7df3' : '1px solid #e2e8f0',
                          background: isSelected ? 'rgba(79, 125, 243, 0.08)' : '#ffffff',
                          color: isSelected ? '#4f7df3' : '#334155',
                          fontWeight: isSelected ? 700 : 500,
                          fontSize: '0.78rem',
                          cursor: 'pointer',
                          textAlign: 'center',
                          transition: 'all 0.15s ease',
                        }}
                      >
                        {ratio.label}
                      </button>
                    );
                  })}
                </div>
              </div>

              {/* Zoom Controls & Sliders */}
              <div
                style={{
                  background: 'var(--color-surface-alt, #f8fafc)',
                  padding: '12px 16px',
                  borderRadius: '12px',
                  border: '1px solid var(--color-border-soft, #e2e8f0)',
                  display: 'flex',
                  flexDirection: 'column',
                  gap: '10px',
                }}
              >
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                  <span style={{ fontSize: '0.8rem', fontWeight: 700, color: '#334155', display: 'flex', alignItems: 'center', gap: '6px' }}>
                    <ZoomIn size={14} color="#4f7df3" />
                    <span>Zoom Level</span>
                  </span>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                    <span style={{ fontSize: '0.78rem', fontWeight: 700, color: '#64748b' }}>
                      {Math.round(zoom * 100)}%
                    </span>
                    <button
                      type="button"
                      onClick={handleReset}
                      title="Reset Position & Zoom"
                      style={{
                        background: '#ffffff',
                        border: '1px solid #cbd5e1',
                        borderRadius: '6px',
                        padding: '3px 8px',
                        fontSize: '0.75rem',
                        fontWeight: 600,
                        color: '#475569',
                        cursor: 'pointer',
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
                    disabled={zoom <= 1}
                    style={{
                      width: '30px',
                      height: '30px',
                      borderRadius: '8px',
                      background: '#ffffff',
                      border: '1px solid #cbd5e1',
                      display: 'grid',
                      placeItems: 'center',
                      cursor: zoom <= 1 ? 'not-allowed' : 'pointer',
                      opacity: zoom <= 1 ? 0.5 : 1,
                      color: '#334155',
                    }}
                  >
                    <ZoomOut size={15} />
                  </button>

                  <input
                    type="range"
                    min="1"
                    max="3"
                    step="0.01"
                    value={zoom}
                    onChange={(e) => setZoom(parseFloat(e.target.value) || 1)}
                    style={{
                      flex: 1,
                      accentColor: '#4f7df3',
                      cursor: 'pointer',
                      height: '6px',
                    }}
                  />

                  <button
                    type="button"
                    aria-label="Zoom In"
                    onClick={() => setZoom((prev) => Math.min(3, parseFloat((prev + 0.1).toFixed(2))))}
                    disabled={zoom >= 3}
                    style={{
                      width: '30px',
                      height: '30px',
                      borderRadius: '8px',
                      background: '#ffffff',
                      border: '1px solid #cbd5e1',
                      display: 'grid',
                      placeItems: 'center',
                      cursor: zoom >= 3 ? 'not-allowed' : 'pointer',
                      opacity: zoom >= 3 ? 0.5 : 1,
                      color: '#334155',
                    }}
                  >
                    <ZoomIn size={15} />
                  </button>
                </div>
              </div>

              {/* Dimensions Information Bar */}
              <div
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  fontSize: '0.75rem',
                  color: '#64748b',
                  padding: '4px 8px',
                }}
              >
                <span>
                  <strong>Original:</strong> {naturalDimensions.width || '—'} × {naturalDimensions.height || '—'} px
                </span>
                <span>
                  <strong>Framed:</strong> ~{approxOutputWidth || '—'} × {approxOutputHeight || '—'} px
                </span>
              </div>
            </>
          ) : previewMode === 'business_logo' || previewMode === 'community_logo' ? (
            /* Exact Logo Published Preview */
            <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
              <div
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: '8px',
                  padding: '10px 14px',
                  background: 'rgba(79, 125, 243, 0.08)',
                  border: '1px solid rgba(79, 125, 243, 0.2)',
                  borderRadius: '10px',
                  fontSize: '0.8rem',
                  color: '#1e40af',
                }}
              >
                <Sparkles size={16} style={{ flexShrink: 0 }} />
                <span style={{ minWidth: 0, overflowWrap: 'anywhere' }}>
                  {previewMode === 'community_logo' ? (
                    <>
                      <strong>Exact Community Brand Preview:</strong> This is precisely how your logo will appear in your community header and directory cards.
                    </>
                  ) : (
                    <>
                      <strong>Exact Business Brand Preview:</strong> This is precisely how your logo will appear in your business header, directory cards, and verified member badge.
                    </>
                  )}
                </span>
              </div>

              {/* Simulated Profile Card Preview */}
              <div
                style={{
                  maxWidth: '400px',
                  margin: '0 auto',
                  width: '100%',
                  background: '#ffffff',
                  borderRadius: '16px',
                  border: '1px solid #e7ecf4',
                  boxShadow: '0 4px 18px rgba(15, 23, 42, 0.06)',
                  overflow: 'hidden',
                  textAlign: 'center',
                  padding: '28px 20px',
                }}
              >
                <div
                  style={{
                    width: `${logoPreviewSize}px`,
                    height: `${logoPreviewSize}px`,
                    borderRadius: '50%',
                    overflow: 'hidden',
                    position: 'relative',
                    boxShadow: '0 4px 16px rgba(0, 0, 0, 0.1)',
                    border: '3px solid #ffffff',
                    background: '#f8fafc',
                    margin: '0 auto 16px',
                  }}
                >
                  {imageSrc && (
                    <img
                      src={imageSrc}
                      alt={previewMode === 'community_logo' ? 'Community Logo Preview' : 'Business Logo Preview'}
                      style={{
                        position: 'absolute',
                        left: '50%',
                        top: '50%',
                        width: `${renderedImgWidth * logoScaleRatio}px`,
                        height: `${renderedImgHeight * logoScaleRatio}px`,
                        maxWidth: 'none',
                        maxHeight: 'none',
                        transform: `translate(-50%, -50%) translate(${clampedPan.x * logoScaleRatio}px, ${clampedPan.y * logoScaleRatio}px)`,
                        objectFit: 'fill',
                      }}
                    />
                  )}
                </div>

                <h3 style={{ margin: '0 0 4px', fontSize: '1.1rem', fontWeight: 800, color: '#0f172a' }}>
                  {previewMode === 'community_logo' ? commName : bizName}
                </h3>
                {previewMode !== 'community_logo' && (
                  <div style={{ fontSize: '0.8rem', color: '#64748b', marginBottom: '10px' }}>
                    @{bizUsername}
                  </div>
                )}
                <div style={{ display: 'inline-block', padding: '4px 12px', background: 'rgba(79, 125, 243, 0.1)', color: '#4f7df3', borderRadius: '12px', fontSize: '0.75rem', fontWeight: 600, marginTop: previewMode === 'community_logo' ? '6px' : '0' }}>
                  {previewMode === 'community_logo' ? commCategory : bizCategory}
                </div>
              </div>
            </div>
          ) : previewMode === 'business_cover' || previewMode === 'community_cover' ? (
            /* Exact Cover Banner Published Preview */
            <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
              <div
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: '8px',
                  padding: '10px 14px',
                  background: 'rgba(79, 125, 243, 0.08)',
                  border: '1px solid rgba(79, 125, 243, 0.2)',
                  borderRadius: '10px',
                  fontSize: '0.8rem',
                  color: '#1e40af',
                }}
              >
                <Sparkles size={16} style={{ flexShrink: 0 }} />
                <span style={{ minWidth: 0, overflowWrap: 'anywhere' }}>
                  {previewMode === 'community_cover' ? (
                    <>
                      <strong>Exact Community Hero Preview:</strong> This is precisely how your cover photo will appear across the top hero banner of your Community.
                    </>
                  ) : (
                    <>
                      <strong>Exact Business Page Header Preview:</strong> This is precisely how your cover photo will appear across the top hero banner of your Business Page.
                    </>
                  )}
                </span>
              </div>

              {/* Simulated Hero Container */}
              <div
                className="biz-hero"
                style={{
                  maxWidth: `${coverPreviewWidth}px`,
                  margin: '0 auto',
                  width: '100%',
                  background: '#ffffff',
                  borderRadius: '16px',
                  border: '1px solid #e7ecf4',
                  boxShadow: '0 4px 18px rgba(15, 23, 42, 0.06)',
                  overflow: 'hidden',
                }}
              >
                <div
                  className="biz-hero__cover"
                  style={{
                    position: 'relative',
                    width: '100%',
                    height: `${coverPreviewHeight}px`,
                    overflow: 'hidden',
                    background: 'linear-gradient(135deg, #1e293b 0%, #334155 100%)',
                  }}
                >
                  {imageSrc && (
                    <img
                      src={imageSrc}
                      alt={previewMode === 'community_cover' ? 'Community Cover Preview' : 'Business Cover Preview'}
                      style={{
                        position: 'absolute',
                        left: '50%',
                        top: '50%',
                        width: `${renderedImgWidth * coverScaleRatio}px`,
                        height: `${renderedImgHeight * coverScaleRatio}px`,
                        maxWidth: 'none',
                        maxHeight: 'none',
                        transform: `translate(-50%, -50%) translate(${clampedPan.x * coverScaleRatio}px, ${clampedPan.y * coverScaleRatio}px)`,
                        objectFit: 'fill',
                      }}
                    />
                  )}
                  <div
                    className="biz-hero__cover-overlay"
                    style={{
                      position: 'absolute',
                      inset: 0,
                      background: 'linear-gradient(to bottom, rgba(0,0,0,0.05) 0%, rgba(0,0,0,0.4) 100%)',
                    }}
                  />
                </div>

                {/* Hero Body Info */}
                <div style={{ padding: '14px 18px', display: 'flex', alignItems: 'center', gap: '14px' }}>
                  <div
                    style={{
                      width: '54px',
                      height: '54px',
                      borderRadius: '50%',
                      background: '#4f7df3',
                      color: '#ffffff',
                      display: 'grid',
                      placeItems: 'center',
                      fontWeight: 800,
                      fontSize: '1.1rem',
                      border: '3px solid #ffffff',
                      boxShadow: '0 2px 8px rgba(0,0,0,0.12)',
                      marginTop: '-28px',
                      zIndex: 2,
                    }}
                  >
                    {previewMode === 'community_cover' ? commInitials : bizInitials}
                  </div>
                  <div style={{ flex: 1, minWidth: 0 }}>
                    <h4 style={{ margin: '0 0 2px', fontSize: '15px', fontWeight: 700, color: '#0f172a', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                      {previewMode === 'community_cover' ? commName : bizName}
                    </h4>
                    <span style={{ fontSize: '12px', color: '#64748b' }}>
                      {previewMode === 'community_cover' ? commCategory : `${bizCategory} · @${bizUsername}`}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          ) : previewMode === 'event' ? (
            /* Exact Event Card Published Preview */
            <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
              <div
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: '8px',
                  padding: '10px 14px',
                  background: 'rgba(79, 125, 243, 0.08)',
                  border: '1px solid rgba(79, 125, 243, 0.2)',
                  borderRadius: '10px',
                  fontSize: '0.8rem',
                  color: '#1e40af',
                }}
              >
                <Sparkles size={16} style={{ flexShrink: 0 }} />
                <span style={{ minWidth: 0, overflowWrap: 'anywhere' }}>
                  <strong>Exact Event Card Rendering:</strong> This is precisely how your cover photo will appear in the published Event Card.
                </span>
              </div>

              {/* Simulated Event Card */}
              <article
                className="event-card"
                style={{
                  maxWidth: '380px',
                  margin: '0 auto',
                  width: '100%',
                  background: '#ffffff',
                  borderRadius: '20px',
                  border: '1px solid #e7ecf4',
                  boxShadow: '0 4px 18px rgba(15, 23, 42, 0.06)',
                  overflow: 'hidden',
                }}
              >
                {/* Event Card Cover Container */}
                <div
                  style={{
                    position: 'relative',
                    width: '100%',
                    height: '160px',
                    overflow: 'hidden',
                    background: 'linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%)',
                  }}
                >
                  {imageSrc && (
                    <img
                      src={imageSrc}
                      alt="Event Cover Preview"
                      style={{
                        position: 'absolute',
                        left: '50%',
                        top: '50%',
                        width: `${renderedImgWidth * previewScaleRatio}px`,
                        height: `${renderedImgHeight * previewScaleRatio}px`,
                        maxWidth: 'none',
                        maxHeight: 'none',
                        transform: `translate(-50%, -50%) translate(${clampedPan.x * previewScaleRatio}px, ${clampedPan.y * previewScaleRatio}px)`,
                        objectFit: 'fill',
                      }}
                    />
                  )}
                  <div className="event-card__cover-overlay" />

                  {/* Type Badge */}
                  <span
                    className={`event-card__badge ${isOnlineEvent ? 'badge-online' : 'badge-offline'}`}
                    style={{ position: 'absolute', top: '12px', left: '12px' }}
                  >
                    {isOnlineEvent ? <Video size={12} aria-hidden="true" /> : <MapPin size={12} aria-hidden="true" />}
                    <span>{isOnlineEvent ? 'Online' : 'In-Person'}</span>
                  </span>

                  {/* Category Tag */}
                  {eventData?.category && (
                    <span
                      className="event-card__category"
                      style={{ position: 'absolute', top: '12px', right: '12px' }}
                    >
                      <span>{eventData.category}</span>
                    </span>
                  )}
                </div>

                {/* Event Card Body */}
                <div className="event-card__body" style={{ padding: '16px 18px 18px', display: 'flex', flexDirection: 'column' }}>
                  <div className="event-card__main-row" style={{ display: 'flex', gap: '14px', alignItems: 'flex-start' }}>
                    <div className="event-card__date" style={{ flexShrink: 0 }}>
                      <span className="event-card__month">{eventMonth}</span>
                      <span className="event-card__day">{eventDay}</span>
                    </div>

                    <div className="event-card__info" style={{ flex: 1, minWidth: 0 }}>
                      <h3
                        className="event-card__title"
                        style={{
                          margin: '0 0 6px',
                          fontSize: '15px',
                          fontWeight: 700,
                          color: '#0f172a',
                          whiteSpace: 'nowrap',
                          overflow: 'hidden',
                          textOverflow: 'ellipsis',
                        }}
                      >
                        {eventData?.title || 'Your Event Title'}
                      </h3>

                      <div className="event-card__meta" style={{ display: 'flex', flexDirection: 'column', gap: '4px', fontSize: '12px', color: '#64748b' }}>
                        <span style={{ display: 'inline-flex', alignItems: 'center', gap: '5px' }}>
                          <Clock size={13} aria-hidden="true" />
                          <span>{formattedEventTime}</span>
                        </span>

                        <span style={{ display: 'inline-flex', alignItems: 'center', gap: '5px' }}>
                          {isOnlineEvent ? <Video size={13} aria-hidden="true" /> : <MapPin size={13} aria-hidden="true" />}
                          <span style={{ whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>{eventLocationLabel}</span>
                        </span>

                        <span style={{ display: 'inline-flex', alignItems: 'center', gap: '5px' }}>
                          <Users size={13} aria-hidden="true" />
                          <span>Hosted by <strong>{currentUser?.name || 'You'}</strong></span>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </article>
            </div>
          ) : (
            /* Exact Post Card Published Preview */
            <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
              <div
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: '8px',
                  padding: '10px 14px',
                  background: 'rgba(79, 125, 243, 0.08)',
                  border: '1px solid rgba(79, 125, 243, 0.2)',
                  borderRadius: '10px',
                  fontSize: '0.8rem',
                  color: '#1e40af',
                }}
              >
                <Sparkles size={16} style={{ flexShrink: 0 }} />
                <span style={{ minWidth: 0, overflowWrap: 'anywhere' }}>
                  <strong>Exact Live Feed Rendering:</strong> This is precisely how your image will appear in the published Post Card feed.
                </span>
              </div>

              {/* Simulated Feed Post Card */}
              <article
                className="card feed-post"
                style={{
                  border: '1px solid var(--color-border-soft, #e2e8f0)',
                  borderRadius: '16px',
                  boxShadow: '0 4px 12px rgba(0, 0, 0, 0.04)',
                  padding: '0',
                  overflow: 'hidden',
                  background: '#ffffff',
                  width: '100%',
                  maxWidth: '100%',
                  boxSizing: 'border-box',
                }}
              >
                {/* Post Header */}
                <header
                  className="post-header"
                  style={{
                    padding: '14px 18px',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '12px',
                  }}
                >
                  {userAvatar ? (
                    <img
                      className="avatar"
                      src={userAvatar}
                      alt={currentUser?.name || 'User'}
                      style={{ width: '42px', height: '42px', borderRadius: '50%', objectFit: 'cover' }}
                    />
                  ) : (
                    <span
                      className="avatar post-avatar-initials"
                      style={{ width: '42px', height: '42px', borderRadius: '50%', background: '#4f7df3', color: '#ffffff', display: 'grid', placeItems: 'center', fontWeight: 700 }}
                    >
                      {userInitials}
                    </span>
                  )}
                  <div className="post-header__meta">
                    <div>
                      <strong style={{ fontSize: '0.9rem', color: '#0f172a' }}>{currentUser?.name || 'Member'}</strong>
                    </div>
                    <span style={{ fontSize: '0.75rem', color: '#64748b' }}>Just now · Smart Feed</span>
                  </div>
                </header>

                {/* Post Body (if present) */}
                {postBody && (
                  <p
                    className="post-copy"
                    style={{
                      margin: '0 18px 12px',
                      color: '#334155',
                      fontSize: '0.875rem',
                      lineHeight: '1.5',
                    }}
                  >
                    {postBody}
                  </p>
                )}

                {/* Framed Image Rendering */}
                <div
                  style={{
                    position: 'relative',
                    width: '100%',
                    aspectRatio: `${targetRatio}`,
                    maxHeight: '480px',
                    overflow: 'hidden',
                    background: '#e7ebf2',
                  }}
                >
                  {imageSrc && (
                    <img
                      src={imageSrc}
                      alt="Exact Feed Preview"
                      style={{
                        position: 'absolute',
                        left: '50%',
                        top: '50%',
                        width: `${renderedImgWidth * previewScaleRatio}px`,
                        height: `${renderedImgHeight * previewScaleRatio}px`,
                        maxWidth: 'none',
                        maxHeight: 'none',
                        transform: `translate(-50%, -50%) translate(${clampedPan.x * previewScaleRatio}px, ${clampedPan.y * previewScaleRatio}px)`,
                        objectFit: 'fill',
                      }}
                    />
                  )}
                </div>

                {/* Simulated Post Actions */}
                <footer
                  style={{
                    padding: '10px 18px',
                    borderTop: '1px solid #f1f5f9',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    color: '#64748b',
                    fontSize: '0.8rem',
                    flexWrap: 'wrap',
                    gap: '8px',
                  }}
                >
                  <span>👍 Like</span>
                  <span>💬 0 Comments</span>
                  <span>↗ Share</span>
                </footer>
              </article>
            </div>
          )}
        </div>

        {/* Modal Footer */}
        <footer
          className="image-adjustment-modal__footer"
          style={{
            padding: '14px 20px',
            borderTop: '1px solid var(--color-border-soft, #f1f5f9)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: applyError ? 'space-between' : 'flex-end',
            gap: '10px',
            background: '#ffffff',
            flexShrink: 0,
            flexWrap: 'wrap',
          }}
        >
          {applyError && (
            <span style={{ fontSize: '0.8rem', color: 'var(--color-danger, #ef4444)', fontWeight: 600, flex: '1 1 100%' }}>
              {applyError}
            </span>
          )}
          <div
            className="image-adjustment-modal__footer-actions"
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: '10px',
              flexWrap: 'wrap',
            }}
          >
            <button
              type="button"
              className="member-button member-button--secondary image-adjustment-modal__footer-btn"
              onClick={onCancel}
              disabled={isProcessing}
              style={{
                padding: '8px 16px',
                borderRadius: '8px',
                fontWeight: 600,
                fontSize: '0.85rem',
                cursor: isProcessing ? 'not-allowed' : 'pointer',
                whiteSpace: 'nowrap',
              }}
            >
              Cancel
            </button>

            <button
              type="button"
              className="member-button member-button--primary image-adjustment-modal__footer-btn"
              onClick={handleApply}
              disabled={isProcessing}
              style={{
                padding: '8px 20px',
                borderRadius: '8px',
                fontWeight: 700,
                fontSize: '0.85rem',
                background: '#4f7df3',
                color: '#ffffff',
                display: 'inline-flex',
                alignItems: 'center',
                gap: '6px',
                cursor: isProcessing ? 'not-allowed' : 'pointer',
                boxShadow: '0 4px 10px rgba(79, 125, 243, 0.25)',
                whiteSpace: 'nowrap',
              }}
            >
              <Check size={16} style={{ flexShrink: 0 }} />
              <span>{isProcessing ? 'Applying Frame...' : 'Apply & Use Image'}</span>
            </button>
          </div>
        </footer>
      </div>
    </ModalPortal>
  );
}

export default ImageAdjustmentModal;
