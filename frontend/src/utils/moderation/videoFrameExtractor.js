/**
 * MLM BOOK AI - Video Keyframe Extraction & Moderation Engine
 * Phase 3: Hardened Video Keyframe Engine Foundation
 *
 * Capabilities:
 * - Deterministic Keyframe Sampling (configurable 5 to 8 frames, default 6)
 * - Safe offscreen HTML5 <video> decoding with timeout protection
 * - Cancellation token support (AbortSignal) on seek & frame extraction
 * - Guaranteed Object URL revocation and canvas memory disposal
 * - Structured Video Moderation API contract returning frame-by-frame & aggregate scores
 * - Policy-agnostic output (delivers raw metrics for platform evaluation)
 */

import { MODERATION_ERROR_CODES, classifyImage, createModerationError } from './nsfwModerator.js';

const DEFAULT_CONFIG = Object.freeze({
  sampleCount: 6,
  startMargin: 0.05,
  endMargin: 0.95,
  maxFrameDimension: 400,
  seekTimeoutMs: 6000,
  metadataTimeoutMs: 12000,
});

/**
 * Calculates evenly distributed timestamps across a video duration.
 *
 * @param {number} duration Duration in seconds
 * @param {number} count Number of sample timestamps
 * @param {number} startMargin Margin from video start (0.05 = 5%)
 * @param {number} endMargin Margin from video end (0.95 = 95%)
 * @returns {number[]} Array of timestamps in seconds
 */
export function calculateSampleTimestamps(
  duration,
  count = 6,
  startMargin = 0.05,
  endMargin = 0.95
) {
  if (!duration || duration <= 0 || !Number.isFinite(duration)) {
    return [];
  }

  const validCount = Math.min(Math.max(Math.floor(count), 3), 12);

  // For ultra-short videos (<= 1.0s), sample evenly
  if (duration <= 1.0) {
    const timestamps = [];
    const step = duration / (validCount + 1);
    for (let i = 1; i <= validCount; i++) {
      timestamps.push(Math.round(step * i * 100) / 100);
    }
    return timestamps;
  }

  const startSec = duration * Math.max(0, Math.min(startMargin, 0.4));
  const endSec = duration * Math.min(1.0, Math.max(endMargin, 0.6));
  const span = endSec - startSec;
  const step = span / (validCount - 1);

  const timestamps = [];
  for (let i = 0; i < validCount; i++) {
    const ts = Math.round((startSec + i * step) * 100) / 100;
    timestamps.push(Math.min(ts, duration - 0.05));
  }

  return timestamps;
}

/**
 * Extracts keyframes from a video File or Blob by seeking through an offscreen video element.
 *
 * @param {File|Blob} videoBlob Video file or blob
 * @param {Object} options Configuration overrides (sampleCount, signal, seekTimeoutMs)
 * @returns {Promise<Object>} Keyframe extraction result
 */
export async function extractVideoKeyframes(videoBlob, options = {}) {
  const config = { ...DEFAULT_CONFIG, ...options };
  const startTime = performance.now();

  if (config.signal && config.signal.aborted) {
    throw createModerationError(
      MODERATION_ERROR_CODES.CANCELLED,
      'Video extraction cancelled before start.'
    );
  }

  if (!videoBlob || !(videoBlob instanceof Blob)) {
    throw createModerationError(
      MODERATION_ERROR_CODES.INVALID_VIDEO,
      'Invalid video input: expected a browser File or Blob.'
    );
  }

  if (videoBlob.type && !videoBlob.type.startsWith('video/')) {
    throw createModerationError(
      MODERATION_ERROR_CODES.INVALID_VIDEO,
      `Unsupported video MIME type: ${videoBlob.type}`
    );
  }

  const videoElement = document.createElement('video');
  videoElement.preload = 'metadata';
  videoElement.muted = true;
  videoElement.playsInline = true;
  videoElement.autoplay = false;

  let objectUrl = null;

  const cleanupVideo = () => {
    try {
      videoElement.pause();
      videoElement.removeAttribute('src');
      videoElement.load();
    } catch {
      // Best-effort pause/unload
    }
    if (objectUrl && typeof URL !== 'undefined' && typeof URL.revokeObjectURL === 'function') {
      try {
        URL.revokeObjectURL(objectUrl);
        objectUrl = null;
      } catch {
        // Best-effort revoke
      }
    }
  };

  try {
    objectUrl = URL.createObjectURL(videoBlob);
    videoElement.src = objectUrl;

    // 1. Wait for video metadata to load (duration, dimensions)
    const metadataStart = performance.now();
    await new Promise((resolve, reject) => {
      let timer = null;

      const onLoadedMetadata = () => {
        if (timer) clearTimeout(timer);
        resolve();
      };

      const onError = (e) => {
        if (timer) clearTimeout(timer);
        reject(
          createModerationError(
            MODERATION_ERROR_CODES.INVALID_VIDEO,
            'Browser media decoder failed to decode video metadata.',
            e
          )
        );
      };

      timer = setTimeout(() => {
        videoElement.removeEventListener('loadedmetadata', onLoadedMetadata);
        videoElement.removeEventListener('error', onError);
        reject(
          createModerationError(
            MODERATION_ERROR_CODES.TIMEOUT,
            `Timed out waiting for video metadata after ${config.metadataTimeoutMs}ms.`
          )
        );
      }, config.metadataTimeoutMs);

      videoElement.addEventListener('loadedmetadata', onLoadedMetadata, { once: true });
      videoElement.addEventListener('error', onError, { once: true });
    });

    const metadataTimeMs = Math.round(performance.now() - metadataStart);
    const duration = videoElement.duration;
    const videoWidth = videoElement.videoWidth;
    const videoHeight = videoElement.videoHeight;

    if (!duration || duration <= 0 || !Number.isFinite(duration) || !videoWidth || !videoHeight) {
      throw createModerationError(
        MODERATION_ERROR_CODES.INVALID_VIDEO,
        'Video metadata invalid: unreadable dimensions or zero duration.'
      );
    }

    // 2. Calculate sample timestamps
    const timestamps = calculateSampleTimestamps(
      duration,
      config.sampleCount,
      config.startMargin,
      config.endMargin
    );

    // Calculate downscaled canvas dimensions
    let canvasWidth = videoWidth;
    let canvasHeight = videoHeight;
    const maxDim = config.maxFrameDimension;

    if (videoWidth > maxDim || videoHeight > maxDim) {
      if (videoWidth > videoHeight) {
        canvasWidth = maxDim;
        canvasHeight = Math.round((videoHeight / videoWidth) * maxDim);
      } else {
        canvasHeight = maxDim;
        canvasWidth = Math.round((videoWidth / videoHeight) * maxDim);
      }
    }

    const offscreenCanvas = document.createElement('canvas');
    offscreenCanvas.width = canvasWidth;
    offscreenCanvas.height = canvasHeight;
    const ctx = offscreenCanvas.getContext('2d', { willReadFrequently: true });

    if (!ctx) {
      throw createModerationError(
        MODERATION_ERROR_CODES.BROWSER_UNSUPPORTED,
        'Unable to acquire 2D canvas context for video keyframe extraction.'
      );
    }

    const extractedFrames = [];
    const frameExtractStart = performance.now();

    // 3. Sequentially seek and extract each frame
    for (let i = 0; i < timestamps.length; i++) {
      if (config.signal && config.signal.aborted) {
        throw createModerationError(
          MODERATION_ERROR_CODES.CANCELLED,
          `Video extraction cancelled at frame ${i + 1}.`
        );
      }

      const targetTime = timestamps[i];

      await new Promise((resolve, reject) => {
        let timer = null;

        const onSeeked = () => {
          if (timer) clearTimeout(timer);
          resolve();
        };

        timer = setTimeout(() => {
          videoElement.removeEventListener('seeked', onSeeked);
          reject(
            createModerationError(
              MODERATION_ERROR_CODES.TIMEOUT,
              `Timed out seeking video to ${targetTime}s after ${config.seekTimeoutMs}ms.`
            )
          );
        }, config.seekTimeoutMs);

        videoElement.addEventListener('seeked', onSeeked, { once: true });
        videoElement.currentTime = targetTime;
      });

      // Render frame to working canvas
      ctx.drawImage(videoElement, 0, 0, canvasWidth, canvasHeight);

      // Clone frame canvas
      const frameCanvas = document.createElement('canvas');
      frameCanvas.width = canvasWidth;
      frameCanvas.height = canvasHeight;
      const frameCtx = frameCanvas.getContext('2d');
      frameCtx.drawImage(offscreenCanvas, 0, 0);

      extractedFrames.push({
        frameIndex: i + 1,
        timestamp: targetTime,
        canvas: frameCanvas,
        width: canvasWidth,
        height: canvasHeight,
        cleanup: () => {
          frameCanvas.width = 0;
          frameCanvas.height = 0;
        },
      });
    }

    // Clean scratch canvas
    offscreenCanvas.width = 0;
    offscreenCanvas.height = 0;

    const frameExtractTimeMs = Math.round(performance.now() - frameExtractStart);
    const totalTimeMs = Math.round(performance.now() - startTime);

    return {
      success: true,
      metadata: {
        duration,
        videoWidth,
        videoHeight,
        frameWidth: canvasWidth,
        frameHeight: canvasHeight,
        sampleCount: extractedFrames.length,
        timestamps,
      },
      timing: {
        metadataTimeMs,
        frameExtractTimeMs,
        totalTimeMs,
      },
      frames: extractedFrames,
    };
  } catch (err) {
    return {
      success: false,
      error: {
        code: err.code || MODERATION_ERROR_CODES.FRAME_EXTRACTION_FAILED,
        message: err.message || 'Keyframe extraction failed.',
        originalError: err.originalError || null,
      },
      timing: {
        totalTimeMs: Math.round(performance.now() - startTime),
      },
      frames: [],
    };
  } finally {
    cleanupVideo();
  }
}

/**
 * End-to-end video moderation orchestrator.
 * Extracts keyframes and classifies them sequentially, ensuring prompt canvas disposal.
 *
 * @param {File|Blob} videoBlob Video file or blob
 * @param {Object} options Configuration overrides (sampleCount, onProgress, signal)
 * @returns {Promise<Object>} Comprehensive video moderation contract
 */
export async function moderateVideo(videoBlob, options = {}) {
  const startTime = performance.now();
  const sampleCount = options.sampleCount || DEFAULT_CONFIG.sampleCount;

  // 1. Extract keyframes
  const extractResult = await extractVideoKeyframes(videoBlob, {
    ...options,
    sampleCount,
  });

  if (!extractResult.success) {
    return {
      success: false,
      mediaType: 'video',
      error: extractResult.error,
      timing: {
        totalElapsedMs: Math.round(performance.now() - startTime),
      },
    };
  }

  // 2. Classify each frame sequentially
  const frameResults = [];
  const classifyStart = performance.now();

  const maxProbabilities = {
    Drawing: 0,
    Hentai: 0,
    Neutral: 0,
    Porn: 0,
    Sexy: 0,
  };

  let dominantClassOverall = 'Neutral';
  let highestPeakProbability = 0;

  for (const frame of extractResult.frames) {
    if (options.signal && options.signal.aborted) {
      // Clean remaining canvases
      extractResult.frames.forEach((f) => f.cleanup && f.cleanup());
      throw createModerationError(
        MODERATION_ERROR_CODES.CANCELLED,
        'Video moderation cancelled during frame classification.'
      );
    }

    const frameClassifyStart = performance.now();
    const classification = await classifyImage(frame.canvas, options);
    const frameElapsed = Math.round(performance.now() - frameClassifyStart);

    // Explicitly dispose frame canvas immediately
    if (typeof frame.cleanup === 'function') {
      frame.cleanup();
    }

    if (classification.success) {
      const probs = classification.probabilities;
      for (const [cls, val] of Object.entries(probs)) {
        if (val > maxProbabilities[cls]) {
          maxProbabilities[cls] = val;
        }
        if (val > highestPeakProbability) {
          highestPeakProbability = val;
          dominantClassOverall = cls;
        }
      }

      const frameSummary = {
        frameIndex: frame.frameIndex,
        timestamp: frame.timestamp,
        probabilities: probs,
        dominantClass: classification.dominantClass,
        durationMs: frameElapsed,
      };

      frameResults.push(frameSummary);

      if (typeof options.onProgress === 'function') {
        try {
          options.onProgress({
            completedFrames: frameResults.length,
            totalFrames: extractResult.metadata.sampleCount,
            currentFrame: frameSummary,
          });
        } catch {
          // Ignore progress callback error
        }
      }
    }
  }

  const classifyTotalMs = Math.round(performance.now() - classifyStart);
  const totalElapsedMs = Math.round(performance.now() - startTime);

  return {
    success: true,
    mediaType: 'video',
    metadata: extractResult.metadata,
    summary: {
      totalFramesEvaluated: frameResults.length,
      peakProbabilities: maxProbabilities,
      dominantClassOverall,
      peakConfidence: highestPeakProbability,
    },
    frameResults,
    timing: {
      metadataTimeMs: extractResult.timing.metadataTimeMs,
      frameExtractTimeMs: extractResult.timing.frameExtractTimeMs,
      classifyTotalMs,
      totalElapsedMs,
      averagePerFrameMs:
        frameResults.length > 0 ? Math.round(classifyTotalMs / frameResults.length) : 0,
    },
  };
}
