/**
 * MLM BOOK AI - Web Worker Moderation Engine
 * Phase 3: Hardened Off-Thread Tensor Inference Engine
 *
 * Message Protocol:
 * Incoming:
 * - { id, type: 'PING' }
 * - { id, type: 'INIT', payload: { modelUrl, indexedDbKey } }
 * - { id, type: 'CLASSIFY_IMAGE', payload: { imageBitmap, maxDimension } }
 * - { id, type: 'CLASSIFY_FRAME', payload: { imageBitmap, frameIndex, timestamp } }
 * - { id, type: 'CANCEL' }
 * - { id, type: 'DISPOSE' }
 *
 * Outgoing:
 * - { id, type: 'PONG', success: true }
 * - { id, type: 'READY', success: true, backend, elapsedMs }
 * - { id, type: 'RESULT', success: true, predictions, dominantClass, durationMs, ...extra }
 * - { id, type: 'CANCELLED', success: true }
 * - { id, type: 'ERROR', success: false, error: { code, message } }
 */

let tf = null;
let nsfwjs = null;
let model = null;
let isInitializing = false;
let activeJobId = null;

async function getWorkerRuntime() {
  if (tf && nsfwjs) {
    return { tf, nsfwjs };
  }

  const [tfMod, nsfwMod] = await Promise.all([
    import('@tensorflow/tfjs'),
    import('nsfwjs'),
  ]);

  tf = tfMod;
  nsfwjs = nsfwMod;

  if (typeof tf.enableProdMode === 'function') {
    tf.enableProdMode();
  }

  if (typeof tf.ready === 'function') {
    await tf.ready();
  }

  return { tf, nsfwjs };
}

self.onmessage = async (event) => {
  const data = event.data;
  if (!data || typeof data !== 'object') {
    self.postMessage({
      id: null,
      type: 'ERROR',
      success: false,
      error: { code: 'INVALID_MESSAGE', message: 'Malformed message received by worker.' },
    });
    return;
  }

  const { id, type, payload } = data;
  activeJobId = id;

  try {
    switch (type) {
      case 'PING': {
        self.postMessage({ id, type: 'PONG', success: true });
        break;
      }

      case 'INIT': {
        if (model) {
          self.postMessage({
            id,
            type: 'READY',
            success: true,
            backend: tf ? tf.getBackend() : 'unknown',
            elapsedMs: 0,
          });
          break;
        }

        if (isInitializing) {
          self.postMessage({
            id,
            type: 'ERROR',
            success: false,
            error: { code: 'BUSY', message: 'Worker is currently initializing.' },
          });
          break;
        }

        isInitializing = true;
        const startTime = performance.now();
        const runtime = await getWorkerRuntime();

        const modelUrl = payload?.modelUrl || '/models/nsfwjs/';
        const indexedDbKey = payload?.indexedDbKey || 'indexeddb://nsfwjs-mobilenetv2-v1';

        let loadedModel = null;

        // Try IndexedDB first
        if (indexedDbKey && typeof indexedDB !== 'undefined') {
          try {
            const list = runtime.tf.io && typeof runtime.tf.io.listModels === 'function'
              ? await runtime.tf.io.listModels()
              : null;
            if (list && list[indexedDbKey]) {
              loadedModel = await runtime.nsfwjs.load(indexedDbKey, { size: 224 });
            }
          } catch {
            loadedModel = null;
          }
        }

        // Fallback to local network asset
        if (!loadedModel) {
          loadedModel = await runtime.nsfwjs.load(modelUrl, { size: 224 });
          if (loadedModel?.model && typeof loadedModel.model.save === 'function') {
            try {
              await loadedModel.model.save(indexedDbKey);
            } catch {
              // Best-effort IndexedDB save
            }
          }
        }

        // Warm up inside tf.tidy
        try {
          runtime.tf.tidy(() => {
            const dummy = runtime.tf.zeros([1, 224, 224, 3]);
            if (loadedModel?.model?.predict) {
              loadedModel.model.predict(dummy);
            }
          });
        } catch {
          // Non-fatal warm-up
        }

        model = loadedModel;
        isInitializing = false;
        const elapsedMs = Math.round(performance.now() - startTime);

        self.postMessage({
          id,
          type: 'READY',
          success: true,
          backend: runtime.tf.getBackend() || 'unknown',
          elapsedMs,
        });
        break;
      }

      case 'CLASSIFY_IMAGE':
      case 'CLASSIFY_FRAME': {
        const startTime = performance.now();

        if (!model) {
          throw new Error('Worker model is not initialized. Call INIT first.');
        }

        const { imageBitmap, frameIndex, timestamp } = payload || {};
        if (!imageBitmap) {
          throw new Error('No ImageBitmap provided for classification.');
        }

        let rawPredictions;
        try {
          rawPredictions = await model.classify(imageBitmap, 5);
        } finally {
          // Immediately close transferable bitmap to free GPU memory
          if (typeof imageBitmap.close === 'function') {
            imageBitmap.close();
          }
        }

        // Check if job was cancelled while inferring
        if (activeJobId !== id) {
          self.postMessage({ id, type: 'CANCELLED', success: true });
          break;
        }

        const durationMs = Math.round(performance.now() - startTime);
        const probabilities = { Drawing: 0, Hentai: 0, Neutral: 0, Porn: 0, Sexy: 0 };
        let dominantClass = 'Neutral';
        let highestProbability = 0;

        for (const pred of rawPredictions) {
          if (pred && pred.className) {
            const prob = Math.round(pred.probability * 10000) / 10000;
            probabilities[pred.className] = prob;
            if (prob > highestProbability) {
              highestProbability = prob;
              dominantClass = pred.className;
            }
          }
        }

        self.postMessage({
          id,
          type: 'RESULT',
          success: true,
          probabilities,
          dominantClass,
          confidence: highestProbability,
          durationMs,
          backend: tf ? tf.getBackend() : 'unknown',
          ...(type === 'CLASSIFY_FRAME' ? { frameIndex, timestamp } : {}),
        });
        break;
      }

      case 'CANCEL': {
        activeJobId = null;
        self.postMessage({ id, type: 'CANCELLED', success: true });
        break;
      }

      case 'DISPOSE': {
        if (model && model.model && typeof model.model.dispose === 'function') {
          try {
            model.model.dispose();
          } catch {
            // Best-effort disposal
          }
        }
        model = null;
        self.postMessage({ id, type: 'READY', success: true, disposed: true });
        break;
      }

      default: {
        self.postMessage({
          id,
          type: 'ERROR',
          success: false,
          error: { code: 'UNKNOWN_TYPE', message: `Unknown worker action type: ${type}` },
        });
      }
    }
  } catch (err) {
    self.postMessage({
      id,
      type: 'ERROR',
      success: false,
      error: {
        code: err.code || 'WORKER_EXECUTION_FAILED',
        message: err.message || String(err),
      },
    });
  }
};
