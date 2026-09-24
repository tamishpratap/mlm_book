import { useState, useEffect, useRef, useMemo, useCallback } from 'react';
import {
  AlertTriangle,
  ExternalLink,
  Image as ImageIcon,
  RefreshCw,
  Video,
} from 'lucide-react';
import {
  getPostMediaCandidates,
  isVideoPost,
} from '../../../utils/mediaHelper';

/**
 * Media viewer for Report inspection.
 * Supports image and video with candidate fallback cascade, safety watchdog timer,
 * skeleton shimmer, error fallback, controlled aspect ratio, and direct external link.
 */
export function ReportMediaViewer({
  mediaType,
  mediaUrl,
  mediaPath,
  label = 'Attached Media',
}) {
  const candidateUrls = useMemo(() => {
    return getPostMediaCandidates({
      media_url: mediaUrl,
      media_path: mediaPath,
      media_type: mediaType,
    });
  }, [mediaUrl, mediaPath, mediaType]);

  const candidateKey = candidateUrls.join('|');
  const [prevKey, setPrevKey] = useState(candidateKey);
  const [candidateIndex, setCandidateIndex] = useState(0);
  const [imageStatus, setImageStatus] = useState(() => (candidateUrls.length > 0 ? 'loading' : 'error'));
  const imgRef = useRef(null);

  // Adjust state synchronously during render when candidates change (standard React 19 pattern)
  if (prevKey !== candidateKey) {
    setPrevKey(candidateKey);
    setCandidateIndex(0);
    setImageStatus(candidateUrls.length > 0 ? 'loading' : 'error');
  }

  const activeUrl = candidateUrls[candidateIndex] || null;

  const isVideo = useMemo(() => {
    return isVideoPost({ media_type: mediaType, media_url: mediaUrl, media_path: mediaPath }, activeUrl);
  }, [mediaType, mediaUrl, mediaPath, activeUrl]);

  // Handle load error: cascade to next candidate URL or trigger error state
  const handleMediaError = useCallback(() => {
    setCandidateIndex((prev) => {
      if (prev + 1 < candidateUrls.length) {
        return prev + 1;
      }
      setImageStatus('error');
      return prev;
    });
  }, [candidateUrls.length]);

  const handleMediaLoad = useCallback(() => {
    setImageStatus('loaded');
  }, []);

  // Immediate check if image is already cached/completed by the browser
  useEffect(() => {
    if (!isVideo && imgRef.current) {
      if (imgRef.current.complete) {
        if (imgRef.current.naturalWidth > 0) {
          setImageStatus('loaded');
        } else if (imgRef.current.src) {
          handleMediaError();
        }
      }
    }
  }, [activeUrl, isVideo, handleMediaError]);

  // Safety watchdog timer: prevent getting stuck on 'loading' indefinitely
  useEffect(() => {
    if (imageStatus !== 'loading') return;
    const timer = setTimeout(() => {
      handleMediaError();
    }, 6000);

    return () => clearTimeout(timer);
  }, [imageStatus, handleMediaError]);

  if (!mediaUrl && !mediaPath && candidateUrls.length === 0) {
    return null;
  }

  const directLinkUrl = activeUrl || mediaUrl || null;

  return (
    <div className="space-y-2.5 min-w-0">
      {/* Header Label and Truncated Media Path */}
      <div className="flex items-center justify-between gap-3 min-w-0">
        <span className="text-[10px] font-bold uppercase tracking-wider text-slate-500 shrink-0 flex items-center">
          {isVideo ? (
            <Video className="w-3.5 h-3.5 mr-1 text-purple-600" />
          ) : (
            <ImageIcon className="w-3.5 h-3.5 mr-1 text-blue-600" />
          )}
          {label} ({isVideo ? 'Video' : 'Image'})
        </span>
        {mediaPath && (
          <span
            className="text-[10px] text-slate-400 font-mono truncate max-w-[220px] sm:max-w-[320px] min-w-0 text-right"
            title={mediaPath}
          >
            {mediaPath}
          </span>
        )}
      </div>

      {/* Controlled Media Preview Container */}
      <div className="bg-slate-950 rounded-xl overflow-hidden border border-slate-800 text-center relative group min-h-[220px] max-h-[500px] flex items-center justify-center shadow-inner">
        {isVideo ? (
          <div className="w-full h-full flex items-center justify-center p-2 bg-black min-h-[220px]">
            {activeUrl ? (
              <video
                src={activeUrl}
                controls
                preload="metadata"
                className="max-h-[480px] w-full object-contain rounded-lg"
                onError={handleMediaError}
              >
                Your browser does not support the video tag.
              </video>
            ) : (
              <div className="py-8 text-center text-slate-400 text-xs">
                <Video className="w-8 h-8 text-slate-600 mx-auto mb-2" />
                <span>Video media unavailable</span>
              </div>
            )}
          </div>
        ) : (
          <>
            {/* Loading State: Centered Placeholder Icon + Balanced Spacing */}
            {imageStatus === 'loading' && (
              <div className="absolute inset-0 flex flex-col items-center justify-center bg-slate-950/95 z-10 space-y-3 p-6">
                <div className="w-12 h-12 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center shadow-md animate-pulse">
                  <ImageIcon className="w-6 h-6 text-slate-500" />
                </div>
                <div className="flex items-center space-x-2 text-slate-400 text-xs">
                  <RefreshCw className="w-3.5 h-3.5 animate-spin text-blue-400 shrink-0" />
                  <span className="font-medium tracking-wide">Loading media preview...</span>
                </div>
              </div>
            )}

            {/* Error Fallback Card */}
            {imageStatus === 'error' && (
              <div className="py-8 px-4 text-center text-slate-400 text-xs space-y-2.5 z-10 max-w-sm mx-auto">
                <div className="w-10 h-10 rounded-full bg-slate-900 border border-slate-800 flex items-center justify-center mx-auto text-amber-500 shadow-xs">
                  <AlertTriangle className="w-5 h-5" />
                </div>
                <div>
                  <p className="text-slate-200 font-semibold">Media file preview unavailable</p>
                  <p className="text-[11px] text-slate-500 font-mono truncate max-w-xs mx-auto mt-0.5" title={mediaPath || directLinkUrl}>
                    {mediaPath || directLinkUrl}
                  </p>
                </div>
                {directLinkUrl && (
                  <div className="pt-1">
                    <a
                      href={directLinkUrl}
                      target="_blank"
                      rel="noreferrer"
                      className="inline-flex items-center text-blue-400 hover:text-blue-300 text-xs font-semibold hover:underline"
                    >
                      Open direct URL <ExternalLink className="w-3 h-3 ml-1" />
                    </a>
                  </div>
                )}
              </div>
            )}

            {/* Image Preview with Smooth Fade-in and Object-Contain */}
            {activeUrl ? (
              <a
                href={activeUrl}
                target="_blank"
                rel="noreferrer"
                title="Click to open full resolution image in a new tab"
                className="w-full h-full flex items-center justify-center p-2"
              >
                <img
                  ref={imgRef}
                  src={activeUrl}
                  alt="Reported Content Media"
                  loading="eager"
                  decoding="async"
                  onLoad={handleMediaLoad}
                  onError={handleMediaError}
                  className={`max-h-[480px] w-auto max-w-full mx-auto object-contain transition-all duration-300 ${
                    imageStatus === 'loaded'
                      ? 'opacity-100 scale-100'
                      : 'opacity-0 scale-95 pointer-events-none'
                  }`}
                />
              </a>
            ) : null}

            {/* Floating External Link Button */}
            {imageStatus === 'loaded' && directLinkUrl && (
              <a
                href={directLinkUrl}
                target="_blank"
                rel="noreferrer"
                className="absolute top-3 right-3 px-2.5 py-1.5 rounded-lg bg-slate-900/85 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-700/60 shadow-lg opacity-0 group-hover:opacity-100 transition-opacity text-xs inline-flex items-center gap-1.5 backdrop-blur-xs"
                title="Open full resolution in new tab"
              >
                <ExternalLink className="w-3.5 h-3.5" />
                <span className="text-[11px] font-medium hidden sm:inline">Open original</span>
              </a>
            )}
          </>
        )}
      </div>
    </div>
  );
}

export default ReportMediaViewer;

