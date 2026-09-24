/**
 * Notification Sound Manager
 *
 * Provides safe, throttled, professional audio notification playback
 * for genuinely new notifications arriving in the member session.
 *
 * Includes:
 * 1. Primary path: /sounds/notification.mp3 (5.4 KB, ~0.8s)
 * 2. Embedded Base64 Data URI fallback (zero network dependency)
 * 3. Modern browser AudioContext + HTMLAudioElement autoplay unlocking
 * 4. Resilient initial-snapshot tracking and session-bound notification detection
 */

const STORAGE_KEY = 'mlm_notification_sound_enabled';
const MIN_SOUND_INTERVAL_MS = 1500; // Throttle to prevent audio spam
const MAX_KNOWN_IDS = 1000; // Bounded cache to avoid memory leaks in long sessions

/**
 * Resolves the publicly accessible browser URL for notification.mp3.
 * Prioritizes VITE_BACKEND_URL (backend/public/sounds/notification.mp3),
 * falling back to VITE_ASSET_URL or relative root path.
 */
export function resolveNotificationSoundUrl() {
  const env = (typeof import.meta !== 'undefined' && import.meta.env) ? import.meta.env : {};
  const backendUrl = (env.VITE_BACKEND_URL || '').replace(/\/+$/, '');
  if (backendUrl) {
    return `${backendUrl}/sounds/notification.mp3`;
  }
  const assetUrl = (env.VITE_ASSET_URL || '').replace(/\/+$/, '');
  if (assetUrl) {
    return `${assetUrl}/sounds/notification.mp3`;
  }
  return '/sounds/notification.mp3';
}

// Embedded Base64 Data URI fallback of notification.mp3 (5,458 bytes)
const SOUND_DATA_URI =
  'data:audio/mp3;base64,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o13rLRWxGasK8Xu+JQ0Ii0KuV/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////6ksAzsasAHD1VOafh7YAAACXAAAAE////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////+pLAWGP/gDFgAS4AAAAgAAAlwAAABP////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////+wBLH/////cv////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////qSwFll/4AwYAEuAAAAIAAAJcAAAAT//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAP/6kMBQs/+AMUABLgAAACAAACXAAAAEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA==';

let audioInstance = null;
let audioCtx = null;
let lastSoundTime = 0;
let isUnlocked = false;

// Snapshot state to isolate genuinely new notifications from existing ones
let sessionStartTime = Date.now();
let currentUserId = null;
let isInitialSnapshotTaken = false;
const knownNotificationIds = new Set();

/**
 * Adds an ID to the known notification set with bounded capacity.
 * Automatically evicts oldest entries once MAX_KNOWN_IDS is exceeded.
 */
function rememberNotificationId(idStr) {
  if (!idStr) return;
  if (knownNotificationIds.has(idStr)) return;
  if (knownNotificationIds.size >= MAX_KNOWN_IDS) {
    const oldest = knownNotificationIds.values().next().value;
    if (oldest !== undefined) {
      knownNotificationIds.delete(oldest);
    }
  }
  knownNotificationIds.add(idStr);
}

/**
 * Check if notification sounds are enabled in user preferences
 * Defaults to true.
 */
export function isSoundEnabled() {
  if (typeof window === 'undefined') return false;
  try {
    const pref = localStorage.getItem(STORAGE_KEY);
    return pref !== 'false';
  } catch {
    return true;
  }
}

/**
 * Toggle or set notification sound preference in localStorage
 * @param {boolean} enabled
 */
export function setSoundEnabled(enabled) {
  if (typeof window === 'undefined') return;
  try {
    localStorage.setItem(STORAGE_KEY, enabled ? 'true' : 'false');
  } catch {
    // Ignore localStorage errors
  }
}

/**
 * Get or lazily create the singleton Audio instance
 */
function getAudioInstance() {
  if (typeof window === 'undefined' || typeof Audio === 'undefined') {
    return null;
  }

  if (!audioInstance) {
    const soundUrl = resolveNotificationSoundUrl();
    try {
      audioInstance = new Audio(soundUrl);
      audioInstance.preload = 'auto';
      audioInstance.volume = 0.65; // Soft, comfortable listening volume

      // If network fails to fetch remote soundUrl, fall back to embedded Data URI
      audioInstance.addEventListener('error', () => {
        if (audioInstance && audioInstance.src !== SOUND_DATA_URI) {
          audioInstance.src = SOUND_DATA_URI;
          audioInstance.load();
        }
      });
    } catch {
      try {
        audioInstance = new Audio(SOUND_DATA_URI);
        audioInstance.volume = 0.65;
      } catch {
        return null;
      }
    }
  }

  return audioInstance;
}

/**
 * Unlock audio playback upon first user interaction (click, tap, keypress)
 * to comply with modern browser autoplay policies.
 */
function unlockAudio() {
  if (isUnlocked || typeof window === 'undefined') return;

  // 1. Resume AudioContext if supported
  try {
    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
    if (AudioContextClass) {
      if (!audioCtx) {
        audioCtx = new AudioContextClass();
      }
      if (audioCtx.state === 'suspended') {
        audioCtx.resume().catch(() => {});
      }
    }
  } catch {
    // Ignore AudioContext errors
  }

  // 2. Prime Audio element by playing muted then immediately pausing
  const audio = getAudioInstance();
  if (audio) {
    try {
      const prevMuted = audio.muted;
      audio.muted = true;
      const playPromise = audio.play();
      if (playPromise !== undefined && typeof playPromise.then === 'function') {
        playPromise
          .then(() => {
            audio.pause();
            audio.currentTime = 0;
            audio.muted = prevMuted;
            isUnlocked = true;
          })
          .catch(() => {
            audio.muted = prevMuted;
          });
      } else {
        isUnlocked = true;
      }
    } catch {
      // Ignore
    }
  } else {
    isUnlocked = true;
  }

  window.removeEventListener('click', unlockAudio);
  window.removeEventListener('keydown', unlockAudio);
  window.removeEventListener('touchstart', unlockAudio);
}

if (typeof window !== 'undefined') {
  window.addEventListener('click', unlockAudio, { once: true, passive: true });
  window.addEventListener('keydown', unlockAudio, { once: true, passive: true });
  window.addEventListener('touchstart', unlockAudio, { once: true, passive: true });
}

/**
 * Play notification sound safely with debouncing, fallback, and autoplay handling
 */
export function playNotificationSound() {
  if (typeof window === 'undefined') return false;
  if (!isSoundEnabled()) return false;

  const now = Date.now();
  // Prevent audio spam if multiple notifications arrive in rapid succession
  if (now - lastSoundTime < MIN_SOUND_INTERVAL_MS) {
    return false;
  }
  lastSoundTime = now;

  const audio = getAudioInstance();
  if (!audio) return false;

  try {
    audio.currentTime = 0;
    const playPromise = audio.play();
    if (playPromise !== undefined && typeof playPromise.catch === 'function') {
      playPromise.catch(() => {
        // If play failed on current src, try falling back to data URI
        try {
          if (audio.src !== SOUND_DATA_URI) {
            audio.src = SOUND_DATA_URI;
            audio.load();
            audio.currentTime = 0;
            audio.play().catch(() => {});
          }
        } catch {
          // Fail silently
        }
      });
    }
    return true;
  } catch {
    // Synchronous failure fallback
    try {
      if (audio.src !== SOUND_DATA_URI) {
        audio.src = SOUND_DATA_URI;
        audio.currentTime = 0;
        audio.play().catch(() => {});
      }
    } catch {
      // Fail silently
    }
    return false;
  }
}

/**
 * Reset notification snapshot tracking (e.g. on logout or user switch)
 * @param {string|number|null} [newUserId=null]
 */
export function resetNotificationSoundState(newUserId = null) {
  currentUserId = newUserId ? String(newUserId) : null;
  isInitialSnapshotTaken = false;
  sessionStartTime = Date.now();
  knownNotificationIds.clear();
}

/**
 * Record a list of notification IDs as known baseline without playing sound.
 * Useful when opening dropdowns or viewing historical lists.
 * @param {Array} notifications
 */
export function markNotificationsAsKnown(notifications) {
  if (!Array.isArray(notifications)) return;
  notifications.forEach((n) => {
    if (n && n.id) {
      rememberNotificationId(String(n.id));
    }
  });
}

/**
 * Process a list of notifications and play sound ONLY if genuinely new
 * unread notifications are detected after the initial snapshot.
 *
 * @param {Array} notifications - List of notification objects
 * @param {string|number} [userId] - ID of current authenticated member
 * @returns {boolean} true if sound was triggered, false otherwise
 */
export function processNewNotifications(notifications, userId) {
  if (!Array.isArray(notifications) || notifications.length === 0) return false;

  const effectiveUserId = userId ? String(userId) : null;

  // If a different authenticated user logged in, reset snapshot
  if (effectiveUserId && currentUserId && currentUserId !== effectiveUserId) {
    resetNotificationSoundState(effectiveUserId);
  } else if (effectiveUserId && !currentUserId) {
    currentUserId = effectiveUserId;
  }

  // 1. Initial Snapshot: On first fetch/mount/refresh, record all existing IDs
  // without playing sound. This establishes the baseline for the active session.
  if (!isInitialSnapshotTaken) {
    notifications.forEach((n) => {
      if (n && n.id) {
        rememberNotificationId(String(n.id));
      }
    });
    isInitialSnapshotTaken = true;
    return false;
  }

  // 2. Subsequent updates: detect genuinely new unread notifications
  let hasGenuinelyNew = false;
  notifications.forEach((n) => {
    if (n && n.id) {
      const idStr = String(n.id);
      if (!knownNotificationIds.has(idStr)) {
        rememberNotificationId(idStr);

        // Age guard: A notification created significantly before this active
        // browser session began is historical, not a genuinely new arrival.
        let isRecent = true;
        if (n.created_at) {
          const createdTime = new Date(n.created_at).getTime();
          if (!Number.isNaN(createdTime) && createdTime < sessionStartTime - 30000) {
            isRecent = false;
          }
        }

        // Only trigger sound if the new notification is unread and recent
        const isUnread = !n.read_at && !n.read && !n.is_read;
        if (isUnread && isRecent) {
          hasGenuinelyNew = true;
        }
      }
    }
  });

  if (hasGenuinelyNew) {
    playNotificationSound();
    return true;
  }

  return false;
}

// Expose manual test function in development/debugging
if (typeof window !== 'undefined') {
  window.__playNotificationSoundTest = playNotificationSound;
}

export default {
  playNotificationSound,
  processNewNotifications,
  markNotificationsAsKnown,
  resetNotificationSoundState,
  isSoundEnabled,
  setSoundEnabled,
  resolveNotificationSoundUrl,
};

