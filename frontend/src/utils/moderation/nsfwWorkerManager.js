/**
 * MLM BOOK AI - Web Worker Client Manager
 * Phase 3: Off-Thread Moderation Manager with Guaranteed Main-Thread Fallback
 *
 * Capabilities:
 * - Singleton Web Worker lifecycle management
 * - Correlation of request/response via unique message IDs
 * - Zero-copy transfer of ImageBitmap objects to worker thread
 * - Automatic timeout and cancellation (AbortSignal) handling
 * - Transparent fallback to main-thread classifyImage() if Worker fails or is unsupported
 */

import { classifyImage, createModerationError, MODERATION_ERROR_CODES, MODEL_IDENTITY } from './nsfwModerator.js';

let workerInstance = null;
let isWorkerInitializing = false;
let workerInitPromise = null;
let messageSeq = 0;
const pendingRequests = new Map();

/**
 * Returns whether the Web Worker is currently initializing.
 */
export function getIsWorkerInitializing() {
  return isWorkerInitializing;
}

/**
 * Detects whether modern Web Worker + ImageBitmap transfer is supported in current environment.
 */
export function isWorkerSupported() {
  return (
    typeof window !== 'undefined' &&
    typeof Worker !== 'undefined' &&
    typeof createImageBitmap === 'function'
  );
}

/**
 * Initializes or returns the singleton Web Worker instance.
 */
export async function getWorker(options = {}) {
  if (!isWorkerSupported()) {
    return null;
  }

  if (workerInstance) {
    return workerInstance;
  }

  if (workerInitPromise) {
    return workerInitPromise;
  }

  workerInitPromise = (async () => {
    try {
      isWorkerInitializing = true;
      const worker = new Worker(new URL('/src/workers/nsfwWorker.js', import.meta.url), {
        type: 'module',
      });

      worker.onmessage = (event) => {
        const { id, type, success, error, ...payload } = event.data || {};
        if (!id || !pendingRequests.has(id)) {
          return;
        }

        const { resolve, reject, timer } = pendingRequests.get(id);
        pendingRequests.delete(id);
        if (timer) clearTimeout(timer);

        if (success) {
          resolve({ type, ...payload });
        } else {
          reject(
            createModerationError(
              error?.code || MODERATION_ERROR_CODES.CLASSIFICATION_FAILED,
              error?.message || 'Worker execution error.'
            )
          );
        }
      };

      worker.onerror = (err) => {
        // Reject all pending requests on worker crash
        for (const [, req] of pendingRequests.entries()) {
          if (req.timer) clearTimeout(req.timer);
          req.reject(
            createModerationError(
              MODERATION_ERROR_CODES.CLASSIFICATION_FAILED,
              'Web Worker crashed or threw uncaught error.',
              err
            )
          );
        }
        pendingRequests.clear();
        terminateWorker();
      };

      // Send INIT handshake
      const initId = ++messageSeq;
      await new Promise((resolve, reject) => {
        const timer = setTimeout(() => {
          pendingRequests.delete(initId);
          reject(
            createModerationError(
              MODERATION_ERROR_CODES.TIMEOUT,
              'Worker INIT handshake timed out.'
            )
          );
        }, options.timeoutMs || 25000);

        pendingRequests.set(initId, { resolve, reject, timer });
        worker.postMessage({
          id: initId,
          type: 'INIT',
          payload: {
            modelUrl: options.modelUrl || MODEL_IDENTITY.ASSET_URL,
            indexedDbKey: options.indexedDbKey || MODEL_IDENTITY.INDEXED_DB_KEY,
          },
        });
      });

      workerInstance = worker;
      return workerInstance;
    } catch {
      terminateWorker();
      return null;
    } finally {
      isWorkerInitializing = false;
      workerInitPromise = null;
    }
  })();

  return workerInitPromise;
}

/**
 * Classifies an image using the off-thread Web Worker if available,
 * seamlessly falling back to main-thread execution if unavailable or on error.
 *
 * @param {File|Blob|HTMLCanvasElement|HTMLImageElement} input
 * @param {Object} options Options (signal, timeoutMs)
 * @returns {Promise<Object>} Standardized classification contract
 */
export async function classifyWithWorkerOrFallback(input, options = {}) {
  const timeoutMs = options.timeoutMs || 25000;

  // Check cancellation upfront
  if (options.signal && options.signal.aborted) {
    throw createModerationError(
      MODERATION_ERROR_CODES.CANCELLED,
      'Classification cancelled before start.'
    );
  }

  // Attempt Web Worker classification
  if (isWorkerSupported()) {
    let bitmap = null;

    try {
      const worker = await getWorker(options);
      if (worker) {
        // Create transferable ImageBitmap
        if (typeof HTMLCanvasElement !== 'undefined' && input instanceof HTMLCanvasElement) {
          bitmap = await createImageBitmap(input);
        } else if (input instanceof Blob) {
          bitmap = await createImageBitmap(input);
        }

        if (bitmap) {
          const reqId = ++messageSeq;

          const resultPromise = new Promise((resolve, reject) => {
            let timer = null;

            if (options.signal) {
              const onAbort = () => {
                options.signal.removeEventListener('abort', onAbort);
                if (timer) clearTimeout(timer);
                pendingRequests.delete(reqId);
                worker.postMessage({ id: reqId, type: 'CANCEL' });
                reject(
                  createModerationError(
                    MODERATION_ERROR_CODES.CANCELLED,
                    'Classification cancelled by caller.'
                  )
                );
              };
              options.signal.addEventListener('abort', onAbort, { once: true });
            }

            timer = setTimeout(() => {
              pendingRequests.delete(reqId);
              reject(
                createModerationError(
                  MODERATION_ERROR_CODES.TIMEOUT,
                  `Worker classification timed out after ${timeoutMs}ms.`
                )
              );
            }, timeoutMs);

            pendingRequests.set(reqId, { resolve, reject, timer });

            // Post with zero-copy transfer
            worker.postMessage(
              {
                id: reqId,
                type: 'CLASSIFY_IMAGE',
                payload: { imageBitmap: bitmap },
              },
              [bitmap]
            );
          });

          const workerResponse = await resultPromise;

          return {
            success: true,
            mediaType: 'image',
            predictions: Object.entries(workerResponse.probabilities || {}).map(
              ([className, probability]) => ({ className, probability })
            ),
            probabilities: workerResponse.probabilities,
            dominantClass: workerResponse.dominantClass,
            confidence: workerResponse.confidence,
            durationMs: workerResponse.durationMs,
            backend: workerResponse.backend || 'worker',
            executionMode: 'worker',
          };
        }
      }
    } catch {
      // Worker execution failed or timed out — seamlessly fallback to main thread!
      if (bitmap && typeof bitmap.close === 'function') {
        try {
          bitmap.close();
        } catch {
          // Best-effort bitmap close
        }
      }
    }
  }

  // Guaranteed Main-Thread Fallback
  const mainResult = await classifyImage(input, options);
  return {
    ...mainResult,
    executionMode: 'main-thread-fallback',
  };
}

/**
 * Terminates the active Web Worker and resets singleton state.
 */
export function terminateWorker() {
  if (workerInstance) {
    try {
      workerInstance.terminate();
    } catch {
      // Best-effort termination
    }
    workerInstance = null;
  }
  isWorkerInitializing = false;
  workerInitPromise = null;
  pendingRequests.clear();
}
