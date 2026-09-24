/**
 * MLM BOOK AI - Reusable React Image Moderation Hook
 * Phase 4: Client-Side Pre-Flight Image Moderation
 *
 * Capabilities:
 * - Worker-Aware: Uses off-thread Web Worker by default with transparent main-thread fallback
 * - Policy-Driven: Evaluates raw probabilities against centralized platform policies
 * - Concurrency Protection: Single-active scan lock prevents duplicate submissions & race conditions
 * - Short-Lived Identity Cache: In-memory Map keyed by file metadata avoids redundant ML inferences on unchanged files
 * - Multi-Image Batch Support: Controlled sequential / bounded batch classification with progress tracking
 * - Strict Lifecycle Cleanup: Aborts pending scans and releases object resources on unmount
 * - Safe Error Handling: Scanner errors and timeouts halt uploads safely without automatic bypass
 */

import { useState, useRef, useEffect, useCallback } from 'react';
import { classifyWithWorkerOrFallback } from '../utils/moderation/nsfwWorkerManager';
import {
  evaluateModerationPolicy,
  POLICY_ACTIONS,
  MODERATION_CONTEXTS,
} from '../config/imageModerationPolicy';

// In-memory short-lived identity deduplication cache (file metadata -> policy decision)
// Stored outside component lifecycle so identical file identity is preserved across re-renders
const scanResultCache = new Map();
const MAX_CACHE_ENTRIES = 50;

/**
 * Computes a cache key based on immutable file properties and policy context
 */
function getFileCacheKey(file, context) {
  if (!file) return null;
  const name = file.name || 'blob';
  const size = file.size || 0;
  const lastModified = file.lastModified || 0;
  return `${name}_${size}_${lastModified}_${context}`;
}

/**
 * Stores a decision in the short-lived memory cache with LRU eviction
 */
function setCachedDecision(key, decision) {
  if (!key) return;
  if (scanResultCache.size >= MAX_CACHE_ENTRIES) {
    const oldestKey = scanResultCache.keys().next().value;
    scanResultCache.delete(oldestKey);
  }
  scanResultCache.set(key, decision);
}

/**
 * Custom hook for React client-side image moderation
 *
 * @param {Object} options
 * @param {string} options.context Moderation context (default: POST_IMAGE)
 * @param {Function} options.onBlocked Optional callback when an image is blocked
 * @param {Function} options.onAllowed Optional callback when an image is allowed
 * @param {Function} options.onError Optional callback when a scan fails
 * @param {number} options.timeoutMs Optional timeout in milliseconds (default: 25000)
 */
export function useImageModeration({
  context = MODERATION_CONTEXTS.POST_IMAGE,
  onBlocked = null,
  onAllowed = null,
  onError = null,
  timeoutMs = 25000,
} = {}) {
  const [isScanning, setIsScanning] = useState(false);
  const [progress, setProgress] = useState({
    completed: 0,
    total: 0,
    percentage: 0,
    currentFileName: '',
  });
  const [decision, setDecision] = useState(null);
  const [error, setError] = useState(null);

  const abortControllerRef = useRef(null);
  const isMountedRef = useRef(true);
  const activeScanLockRef = useRef(false);

  // Maintain mounted status and cancel active scans on unmount
  useEffect(() => {
    isMountedRef.current = true;
    return () => {
      isMountedRef.current = false;
      if (abortControllerRef.current) {
        abortControllerRef.current.abort();
        abortControllerRef.current = null;
      }
      activeScanLockRef.current = false;
    };
  }, []);

  /**
   * Cancels any currently active scan
   */
  const cancel = useCallback(() => {
    if (abortControllerRef.current) {
      abortControllerRef.current.abort();
      abortControllerRef.current = null;
    }
    activeScanLockRef.current = false;
    if (isMountedRef.current) {
      setIsScanning(false);
      setProgress({ completed: 0, total: 0, percentage: 0, currentFileName: '' });
    }
  }, []);

  /**
   * Resets the hook state back to idle
   */
  const reset = useCallback(() => {
    cancel();
    if (isMountedRef.current) {
      setDecision(null);
      setError(null);
      setProgress({ completed: 0, total: 0, percentage: 0, currentFileName: '' });
    }
  }, [cancel]);

  /**
   * Scans a single image File or Blob against the configured policy
   *
   * @param {File|Blob} file Image to classify
   * @param {Object} overrideOptions Context or signal overrides
   * @returns {Promise<Object>} Standardized policy decision
   */
  const scanImage = useCallback(
    async (file, overrideOptions = {}) => {
      if (!file) {
        throw new Error('No image file provided for moderation scan.');
      }

      // Check non-image files: if caller accidentally passed a PDF/Doc, reject or handle cleanly
      if (file.type && !file.type.startsWith('image/')) {
        const invalidDecision = {
          action: POLICY_ACTIONS.BLOCK,
          category: null,
          score: 0,
          threshold: 0,
          reasonCode: 'INVALID_INPUT',
          context: overrideOptions.context || context,
          userMessage: 'The selected file is not a supported image format.',
          predictions: [],
          probabilities: {},
          durationMs: 0,
          isSystemError: false,
        };
        if (isMountedRef.current) {
          setDecision(invalidDecision);
        }
        return invalidDecision;
      }

      const activeContext = overrideOptions.context || context;
      const cacheKey = getFileCacheKey(file, activeContext);

      // 1. In-memory Deduplication Cache Check
      if (cacheKey && scanResultCache.has(cacheKey)) {
        const cachedDecision = scanResultCache.get(cacheKey);
        if (isMountedRef.current) {
          setDecision(cachedDecision);
          setError(cachedDecision.isSystemError ? cachedDecision.userMessage : null);
        }
        if (cachedDecision.action === POLICY_ACTIONS.ALLOW && onAllowed) {
          onAllowed(cachedDecision);
        } else if (cachedDecision.action === POLICY_ACTIONS.BLOCK && onBlocked) {
          onBlocked(cachedDecision);
        }
        return cachedDecision;
      }

      // 2. Prevent duplicate concurrent scans (single-active lock)
      if (activeScanLockRef.current) {
        // A scan is already running; await it or cancel previous
        cancel();
      }

      activeScanLockRef.current = true;
      const controller = new AbortController();
      abortControllerRef.current = controller;

      if (isMountedRef.current) {
        setIsScanning(true);
        setError(null);
        setProgress({
          completed: 0,
          total: 1,
          percentage: 0,
          currentFileName: file.name || 'image',
        });
      }

      try {
        const rawResult = await classifyWithWorkerOrFallback(file, {
          signal: controller.signal,
          timeoutMs: overrideOptions.timeoutMs || timeoutMs,
        });

        if (controller.signal.aborted) {
          throw new Error('Scan cancelled');
        }

        // Evaluate platform policy
        const policyDecision = evaluateModerationPolicy(rawResult, activeContext);

        // Cache valid decisions
        if (cacheKey && !policyDecision.isSystemError) {
          setCachedDecision(cacheKey, policyDecision);
        }

        if (isMountedRef.current) {
          setDecision(policyDecision);
          setProgress({
            completed: 1,
            total: 1,
            percentage: 100,
            currentFileName: file.name || 'image',
          });
          if (policyDecision.isSystemError) {
            setError(policyDecision.userMessage);
          }
        }

        // Trigger callbacks
        if (policyDecision.action === POLICY_ACTIONS.ALLOW && onAllowed) {
          onAllowed(policyDecision);
        } else if (policyDecision.action === POLICY_ACTIONS.BLOCK && onBlocked) {
          onBlocked(policyDecision);
        }

        return policyDecision;
      } catch (err) {
        const isCancelled = controller.signal.aborted || err.name === 'AbortError' || err.code === 'CANCELLED';
        const rawErrorResult = {
          success: false,
          error: {
            code: isCancelled ? 'CANCELLED' : err.code || 'CLASSIFICATION_FAILED',
            message: err.message,
          },
          durationMs: 0,
        };

        const errorDecision = evaluateModerationPolicy(rawErrorResult, activeContext);

        if (isMountedRef.current) {
          setDecision(errorDecision);
          if (!isCancelled) {
            setError(errorDecision.userMessage);
            if (onError) onError(err);
          }
        }

        return errorDecision;
      } finally {
        activeScanLockRef.current = false;
        abortControllerRef.current = null;
        if (isMountedRef.current) {
          setIsScanning(false);
        }
      }
    },
    [context, onAllowed, onBlocked, onError, timeoutMs, cancel]
  );

  /**
   * Scans a batch of images sequentially or with controlled bounded concurrency.
   *
   * @param {Array<File|Blob>} files Array of images
   * @param {Object} batchOptions Options (context, concurrency, onProgress)
   * @returns {Promise<{ allAllowed: boolean, results: Array, blockedItems: Array, allowedFiles: Array }>}
   */
  const scanImages = useCallback(
    async (files, batchOptions = {}) => {
      if (!Array.isArray(files) || files.length === 0) {
        return {
          allAllowed: true,
          results: [],
          blockedItems: [],
          allowedFiles: [],
        };
      }

      const activeContext = batchOptions.context || context;
      const total = files.length;

      // Prevent duplicate concurrent scans
      if (activeScanLockRef.current) {
        cancel();
      }

      activeScanLockRef.current = true;
      const controller = new AbortController();
      abortControllerRef.current = controller;

      if (isMountedRef.current) {
        setIsScanning(true);
        setError(null);
        setProgress({
          completed: 0,
          total,
          percentage: 0,
          currentFileName: files[0]?.name || 'image 1',
        });
      }

      const results = [];
      const blockedItems = [];
      const allowedFiles = [];

      try {
        for (let i = 0; i < files.length; i++) {
          if (controller.signal.aborted) {
            break;
          }

          const file = files[i];
          const fileName = file.name || `photo_${i + 1}`;

          if (isMountedRef.current) {
            setProgress({
              completed: i,
              total,
              percentage: Math.round((i / total) * 100),
              currentFileName: fileName,
            });
          }

          // Check deduplication cache
          const cacheKey = getFileCacheKey(file, activeContext);
          let itemDecision;

          if (cacheKey && scanResultCache.has(cacheKey)) {
            itemDecision = scanResultCache.get(cacheKey);
          } else {
            const rawResult = await classifyWithWorkerOrFallback(file, {
              signal: controller.signal,
              timeoutMs: batchOptions.timeoutMs || timeoutMs,
            });

            itemDecision = evaluateModerationPolicy(rawResult, activeContext);
            if (cacheKey && !itemDecision.isSystemError) {
              setCachedDecision(cacheKey, itemDecision);
            }
          }

          const itemRecord = {
            index: i,
            file,
            fileName,
            decision: itemDecision,
          };

          results.push(itemRecord);

          if (itemDecision.action === POLICY_ACTIONS.BLOCK) {
            blockedItems.push(itemRecord);
          } else {
            allowedFiles.push(file);
          }

          if (batchOptions.onProgress && typeof batchOptions.onProgress === 'function') {
            batchOptions.onProgress({
              completed: i + 1,
              total,
              percentage: Math.round(((i + 1) / total) * 100),
              currentFileName: fileName,
              lastDecision: itemDecision,
            });
          }
        }

        const allAllowed = blockedItems.length === 0 && results.length === total;

        if (isMountedRef.current) {
          setProgress({
            completed: results.length,
            total,
            percentage: 100,
            currentFileName: '',
          });

          if (!allAllowed && blockedItems.length > 0) {
            const blockedNames = blockedItems.map((b) => b.fileName).join(', ');
            setError(`Restricted content detected in: ${blockedNames}`);
          }
        }

        const batchSummary = {
          allAllowed,
          results,
          blockedItems,
          allowedFiles,
        };

        if (allAllowed && onAllowed) {
          onAllowed(batchSummary);
        } else if (!allAllowed && onBlocked) {
          onBlocked(batchSummary);
        }

        return batchSummary;
      } catch (err) {
        if (isMountedRef.current) {
          setError(err.message || 'Batch verification failed.');
          if (onError) onError(err);
        }
        return {
          allAllowed: false,
          results,
          blockedItems,
          allowedFiles,
          error: err,
        };
      } finally {
        activeScanLockRef.current = false;
        abortControllerRef.current = null;
        if (isMountedRef.current) {
          setIsScanning(false);
        }
      }
    },
    [context, onAllowed, onBlocked, onError, timeoutMs, cancel]
  );

  return {
    scanImage,
    scanImages,
    isScanning,
    progress,
    decision,
    error,
    cancel,
    reset,
  };
}
