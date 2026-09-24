/**
 * MLM BOOK AI - Reusable React Video Moderation Hook
 * Phase 7: Client-Side Pre-Flight Video Moderation
 *
 * Capabilities:
 * - Offscreen Keyframe Extraction: Samples representative frames via HTML5 <video> & Canvas
 * - Web Worker Scoring: Classifies extracted frames off-thread using NSFWJS MobileNetV2
 * - Multi-Frame Aggregation: Aggregates peak probabilities & evaluates against centralized platform policies
 * - Progress Tracking: Provides real-time feedback (e.g. frame 2 of 6) for pre-flight UX
 * - Graceful Cancellation: Supports AbortController on unmount or user dismiss
 */

import { useState, useRef, useEffect, useCallback } from 'react';
import { moderateVideo } from '../utils/moderation/videoFrameExtractor';
import {
  evaluateVideoModerationPolicy,
  POLICY_ACTIONS,
  MODERATION_CONTEXTS,
} from '../config/imageModerationPolicy';

export function useVideoModeration({
  context = MODERATION_CONTEXTS.POST_IMAGE,
  onBlocked = null,
  onAllowed = null,
  onError = null,
} = {}) {
  const [isScanning, setIsScanning] = useState(false);
  const [progress, setProgress] = useState({
    completed: 0,
    total: 0,
    percentage: 0,
  });
  const [decision, setDecision] = useState(null);
  const [error, setError] = useState(null);

  const abortControllerRef = useRef(null);
  const isMountedRef = useRef(true);

  useEffect(() => {
    isMountedRef.current = true;
    return () => {
      isMountedRef.current = false;
      if (abortControllerRef.current) {
        abortControllerRef.current.abort();
        abortControllerRef.current = null;
      }
    };
  }, []);

  const cancel = useCallback(() => {
    if (abortControllerRef.current) {
      abortControllerRef.current.abort();
      abortControllerRef.current = null;
    }
    if (isMountedRef.current) {
      setIsScanning(false);
      setProgress({ completed: 0, total: 0, percentage: 0 });
    }
  }, []);

  const scanVideo = useCallback(
    async (videoBlob) => {
      if (!videoBlob) {
        return {
          action: POLICY_ACTIONS.BLOCK,
          isSystemError: true,
          userMessage: 'No video provided for safety verification.',
        };
      }

      if (abortControllerRef.current) {
        abortControllerRef.current.abort();
      }
      abortControllerRef.current = new AbortController();

      setIsScanning(true);
      setError(null);
      setProgress({ completed: 0, total: 6, percentage: 0 });

      try {
        const scanResult = await moderateVideo(videoBlob, {
          signal: abortControllerRef.current.signal,
          sampleCount: 6,
          onProgress: ({ completedFrames, totalFrames }) => {
            if (isMountedRef.current) {
              const pct = totalFrames > 0 ? Math.round((completedFrames / totalFrames) * 100) : 0;
              setProgress({
                completed: completedFrames,
                total: totalFrames,
                percentage: pct,
              });
            }
          },
        });

        const policyDecision = evaluateVideoModerationPolicy(scanResult, context);

        if (isMountedRef.current) {
          setDecision(policyDecision);
          setIsScanning(false);

          if (policyDecision.action === POLICY_ACTIONS.BLOCK && onBlocked) {
            onBlocked(policyDecision);
          } else if (policyDecision.action === POLICY_ACTIONS.ALLOW && onAllowed) {
            onAllowed(policyDecision);
          }
        }

        return policyDecision;
      } catch (err) {
        const isCancelled = err?.code === 'CANCELLED' || abortControllerRef.current?.signal?.aborted;
        const errDecision = {
          action: POLICY_ACTIONS.BLOCK,
          isSystemError: !isCancelled,
          userMessage: isCancelled
            ? 'Video verification cancelled.'
            : (err?.message || 'Video safety check encountered an error. Please try again.'),
        };

        if (isMountedRef.current) {
          setError(err);
          setDecision(errDecision);
          setIsScanning(false);

          if (onError && !isCancelled) {
            onError(err);
          }
        }

        return errDecision;
      }
    },
    [context, onBlocked, onAllowed, onError]
  );

  return {
    isScanning,
    progress,
    decision,
    error,
    scanVideo,
    cancel,
  };
}
