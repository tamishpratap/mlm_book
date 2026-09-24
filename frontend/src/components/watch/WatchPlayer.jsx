import { useState, useRef, useEffect, useCallback } from 'react';
import { Play, Pause, Volume2, VolumeX, Maximize } from 'lucide-react';

function formatTime(seconds) {
  if (isNaN(seconds) || seconds < 0) return '0:00';
  const mins = Math.floor(seconds / 60);
  const secs = Math.floor(seconds % 60);
  return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
}

export function WatchPlayer({ src, authorName = 'Member' }) {
  const [isPlaying, setIsPlaying] = useState(false);
  const [isMuted, setIsMuted] = useState(true);
  const [volume, setVolume] = useState(1);
  const [currentTime, setCurrentTime] = useState(0);
  const [duration, setDuration] = useState(0);
  const [progressPercent, setProgressPercent] = useState(0);
  const [userPaused, setUserPaused] = useState(false);

  const playerRef = useRef(null);
  const videoRef = useRef(null);
  const progressBarRef = useRef(null);
  const hasScrolledAwayRef = useRef(false);

  // Synchronize initial audio state on mount, source change, or state updates
  useEffect(() => {
    const videoEl = videoRef.current;
    if (!videoEl) return;
    videoEl.muted = isMuted;
    videoEl.volume = volume;
  }, [src, isMuted, volume]);

  // Synchronize state from HTML5 video element events
  const handleVolumeChangeSync = useCallback(() => {
    const videoEl = videoRef.current;
    if (!videoEl) return;
    const currentMuted = Boolean(videoEl.muted || videoEl.volume === 0);
    setIsMuted(currentMuted);
    setVolume(videoEl.volume);
  }, []);

  const handlePlay = useCallback(() => {
    setIsPlaying(true);
    const videoEl = videoRef.current;
    if (videoEl) {
      setIsMuted(Boolean(videoEl.muted || videoEl.volume === 0));
      setVolume(videoEl.volume);
    }
  }, []);

  const handlePause = useCallback(() => {
    setIsPlaying(false);
  }, []);

  // Pause playback when browser tab becomes hidden
  useEffect(() => {
    const handleVisibilityChange = () => {
      if (document.visibilityState === 'hidden') {
        const videoEl = videoRef.current;
        if (videoEl && !videoEl.paused) {
          videoEl.pause();
          setIsPlaying(false);
        }
      }
    };
    document.addEventListener('visibilitychange', handleVisibilityChange);
    return () => {
      document.removeEventListener('visibilitychange', handleVisibilityChange);
    };
  }, []);

  // Viewport Autoplay & Scroll-away Auto-pause Observer
  useEffect(() => {
    const playerEl = playerRef.current;
    const videoEl = videoRef.current;
    if (!playerEl || !videoEl || !('IntersectionObserver' in window)) return;

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          // Bypassed if video is currently fullscreen
          const isFullscreen =
            document.fullscreenElement === playerEl ||
            document.fullscreenElement === videoEl ||
            document.webkitFullscreenElement === playerEl ||
            document.webkitFullscreenElement === videoEl;
          if (isFullscreen) return;

          if (entry.isIntersecting && entry.intersectionRatio >= 0.2) {
            // Initial appearance: autoplay muted only if not user-paused and not scrolled-away
            if (videoEl.paused && !userPaused && !hasScrolledAwayRef.current) {
              videoEl.muted = true;
              setIsMuted(true);
              videoEl
                .play()
                .then(() => {
                  setIsPlaying(true);
                })
                .catch(() => {});
            }
          } else {
            // Out of viewport: pause video element and stop audio
            if (!videoEl.paused) {
              videoEl.pause();
              setIsPlaying(false);
              hasScrolledAwayRef.current = true;
            }
          }
        });
      },
      { threshold: [0, 0.2] }
    );

    observer.observe(playerEl);
    return () => {
      observer.disconnect();
      if (videoEl && !videoEl.paused) {
        videoEl.pause();
      }
    };
  }, [userPaused]);

  const togglePlay = useCallback(() => {
    const videoEl = videoRef.current;
    if (!videoEl) return;

    if (videoEl.paused || videoEl.ended) {
      // Pause other playing videos
      document.querySelectorAll('video').forEach((v) => {
        if (v !== videoEl && !v.paused) {
          v.pause();
        }
      });

      videoEl
        .play()
        .then(() => {
          setIsPlaying(true);
          setUserPaused(false);
          hasScrolledAwayRef.current = false;
          setIsMuted(Boolean(videoEl.muted || videoEl.volume === 0));
        })
        .catch((err) => {
          console.log('Video playback error:', err);
        });
    } else {
      videoEl.pause();
      setIsPlaying(false);
      setUserPaused(true);
      hasScrolledAwayRef.current = true;
    }
  }, []);

  const handleTimeUpdate = () => {
    const videoEl = videoRef.current;
    if (!videoEl || !videoEl.duration) return;
    setCurrentTime(videoEl.currentTime);
    setProgressPercent((videoEl.currentTime / videoEl.duration) * 100);
  };

  const handleLoadedMetadata = () => {
    const videoEl = videoRef.current;
    if (videoEl) {
      setDuration(videoEl.duration);
      setIsMuted(Boolean(videoEl.muted || videoEl.volume === 0));
      setVolume(videoEl.volume);
    }
  };

  const handleVideoEnded = () => {
    setIsPlaying(false);
    setProgressPercent(100);
  };

  const handleProgressBarClick = (e) => {
    const barEl = progressBarRef.current;
    const videoEl = videoRef.current;
    if (!barEl || !videoEl || !videoEl.duration) return;

    const rect = barEl.getBoundingClientRect();
    const pos = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
    videoEl.currentTime = pos * videoEl.duration;
  };

  const toggleMute = (e) => {
    e.stopPropagation();
    const videoEl = videoRef.current;
    if (!videoEl) return;
    const currentlyMuted = Boolean(videoEl.muted || videoEl.volume === 0);
    const nextMuted = !currentlyMuted;
    videoEl.muted = nextMuted;
    if (!nextMuted && videoEl.volume === 0) {
      videoEl.volume = 0.5;
      setVolume(0.5);
    }
    setIsMuted(nextMuted);
  };

  const handleVolumeChange = (e) => {
    e.stopPropagation();
    const newVol = parseFloat(e.target.value);
    const videoEl = videoRef.current;
    if (!videoEl) return;
    videoEl.volume = newVol;
    setVolume(newVol);
    const shouldMute = newVol === 0;
    videoEl.muted = shouldMute;
    setIsMuted(shouldMute);
  };

  const toggleFullscreen = (e) => {
    e.stopPropagation();
    const playerEl = playerRef.current;
    const videoEl = videoRef.current;
    if (!playerEl) return;

    if (document.fullscreenElement) {
      document.exitFullscreen().catch(() => {});
    } else {
      if (playerEl.requestFullscreen) {
        playerEl.requestFullscreen();
      } else if (playerEl.webkitRequestFullscreen) {
        playerEl.webkitRequestFullscreen();
      } else if (videoEl && videoEl.requestFullscreen) {
        videoEl.requestFullscreen();
      }
    }
  };

  const formattedSrc = src?.startsWith('http') || src?.startsWith('/') ? src : `/${src}`;

  return (
    <div
      ref={playerRef}
      className={`watch-player ${isPlaying ? 'is-playing' : 'is-paused'}`}
      data-watch-player
    >
      <video
        ref={videoRef}
        src={formattedSrc}
        muted={isMuted}
        playsInline
        preload="metadata"
        aria-label={`Video by ${authorName}`}
        onClick={togglePlay}
        onTimeUpdate={handleTimeUpdate}
        onLoadedMetadata={handleLoadedMetadata}
        onVolumeChange={handleVolumeChangeSync}
        onPlay={handlePlay}
        onPlaying={handlePlay}
        onPause={handlePause}
        onEnded={handleVideoEnded}
      />

      {/* Overlay Play Button - strictly hidden while playing */}
      {!isPlaying && (
        <button
          className="watch-player__overlay-play"
          type="button"
          aria-label="Play video"
          data-watch-overlay-play
          onClick={(e) => {
            e.stopPropagation();
            togglePlay();
          }}
        >
          <Play size={28} style={{ marginLeft: '3px' }} aria-hidden="true" />
        </button>
      )}

      {/* Bottom Control Bar */}
      <div className="watch-player__controls" data-watch-controls>
        <div
          ref={progressBarRef}
          className="watch-player__progress-bar"
          data-watch-progress-bar
          onClick={handleProgressBarClick}
        >
          <div
            className="watch-player__progress-fill"
            data-watch-progress-fill
            style={{ width: `${progressPercent}%` }}
          />
        </div>

        <div className="watch-player__controls-row">
          <div className="watch-player__controls-left">
            <button
              className="watch-player__btn"
              type="button"
              aria-label={isPlaying ? 'Pause' : 'Play'}
              data-watch-play-btn
              onClick={(e) => {
                e.stopPropagation();
                togglePlay();
              }}
            >
              {isPlaying ? <Pause size={18} aria-hidden="true" /> : <Play size={18} aria-hidden="true" />}
            </button>

            <div className="watch-player__volume-group">
              <button
                className="watch-player__btn"
                type="button"
                aria-label={isMuted || volume === 0 ? 'Unmute video' : 'Mute video'}
                data-watch-mute-btn
                onClick={toggleMute}
              >
                {isMuted || volume === 0 ? (
                  <VolumeX size={18} aria-hidden="true" />
                ) : (
                  <Volume2 size={18} aria-hidden="true" />
                )}
              </button>
              <input
                className="watch-player__volume-slider"
                type="range"
                min="0"
                max="1"
                step="0.05"
                value={isMuted ? 0 : volume}
                onChange={handleVolumeChange}
                aria-label="Volume"
                data-watch-volume-slider
              />
            </div>

            <span className="watch-player__time" data-watch-time>
              {formatTime(currentTime)} / {formatTime(duration)}
            </span>
          </div>

          <div className="watch-player__controls-right">
            <button
              className="watch-player__btn"
              type="button"
              aria-label="Toggle Fullscreen"
              data-watch-fullscreen-btn
              onClick={toggleFullscreen}
            >
              <Maximize size={18} aria-hidden="true" />
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

export default WatchPlayer;
