/**
 * Asset URL Resolver for MLM_Book Member Platform
 * Resolves local paths, uploaded media, and placeholder fallbacks.
 */

const env = (typeof import.meta !== 'undefined' && import.meta.env) ? import.meta.env : {};
const ASSET_BASE = (env.VITE_ASSET_URL || env.VITE_BACKEND_URL || 'https://mlmbookai.com').replace(/\/+$/, '');

/**
 * Normalizes an image or asset URL.
 * - Leaves valid external URLs (e.g., Google OAuth avatars) intact.
 * - Upgrades insecure http://mlmbookai.com to https://.
 * - Migrates old test domains (mytesting.fun, 127.0.0.1, localhost) to the canonical ASSET_BASE while keeping query params.
 */
export function cleanHostPath(path) {
  if (!path) return path;
  const normalized = String(path).replace(/\\/g, '/');
  if (normalized.startsWith('http://') || normalized.startsWith('https://')) {
    try {
      const urlObj = new URL(normalized);
      const host = urlObj.hostname.toLowerCase();

      // Migrate known legacy/test hosts to the configured asset base
      if (host === 'mytesting.fun' || host === '127.0.0.1' || host === 'localhost') {
        const fullRelative = `${urlObj.pathname}${urlObj.search}`;
        return ASSET_BASE ? `${ASSET_BASE}${fullRelative}` : fullRelative;
      }

      // Upgrade production domain from HTTP to HTTPS
      if (host === 'mlmbookai.com' && urlObj.protocol === 'http:') {
        urlObj.protocol = 'https:';
        return urlObj.toString();
      }

      // External or production URL: return intact
      return normalized;
    } catch {
      return normalized;
    }
  }
  return normalized;
}

/**
 * Prefixes a relative path with the configured asset/backend origin if present.
 * Never prepends domain to already absolute URLs.
 */
export function prefixUrl(path) {
  if (!path) return path;
  const cleaned = cleanHostPath(path);
  if (cleaned.startsWith('http://') || cleaned.startsWith('https://')) {
    return cleaned;
  }
  const cleanPath = cleaned.startsWith('/') ? cleaned : `/${cleaned}`;
  return ASSET_BASE ? `${ASSET_BASE}${cleanPath}` : cleanPath;
}

export const DEFAULT_AVATAR = prefixUrl('/member_assets/images/dashboard/image/profile.png');
export const BRAND_LOGO = prefixUrl('/logo/logo.png');

/**
 * Known legacy test uploads or template logo paths that must not override
 * the canonical platform logo asset (backend/public/logo/logo.png).
 */
const LEGACY_LOGO_PATTERNS = [
  'qgj0u7nn7guiztsohxurnbaufvywxsougwcv5ddd',
  '0krtsgmrcm1l3ohgamh4hdkrcth7qb31ndconzdf',
  'gjpkkfxtj2eqktarl7ksbspvk0i0i5aer3nmwizb',
  'vyxguwwxdbpkkl0grwbashpxsgwv3o8xc61nblm9',
  'member_assets/images/dashboard/image/logo',
  'member_assets/images/login/logo',
  'dashboard/image/logo',
  'login/logo',
];

/**
 * Checks whether a raw path or URL points to an older template or test logo.
 */
export function isLegacyOrTestLogo(raw) {
  if (!raw || typeof raw !== 'string') return false;
  const lower = raw.toLowerCase();
  return LEGACY_LOGO_PATTERNS.some((pattern) => lower.includes(pattern));
}

/**
 * Resolves a reliable, usable URL for platform branding assets (logo, dark logo, favicon).
 * Handles absolute URLs, relative storage paths, blob previews, dev proxy origins, and cache-busting.
 *
 * @param {string} raw - Asset path or URL
 * @param {string} [fallback=BRAND_LOGO] - Fallback URL if raw is missing
 * @returns {string}
 */
export function getBrandingAssetUrl(raw, fallback = BRAND_LOGO) {
  if (!raw || typeof raw !== 'string') return fallback;

  const trimmed = raw.trim();
  if (!trimmed) return fallback;

  // Filter out known legacy test uploads and template logos
  if (isLegacyOrTestLogo(trimmed)) {
    return fallback;
  }

  // Explicit canonical platform logo references
  const normalizedLower = trimmed.toLowerCase();
  if (
    normalizedLower === 'logo/logo.png' ||
    normalizedLower === '/logo/logo.png' ||
    normalizedLower.startsWith('logo/logo.png?') ||
    normalizedLower.startsWith('/logo/logo.png?')
  ) {
    return BRAND_LOGO;
  }

  // Case 1: In-memory Blob preview or data URL
  if (trimmed.startsWith('blob:') || trimmed.startsWith('data:')) {
    return trimmed;
  }

  // Case 2: Fully qualified URL (http:// or https://)
  if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
    try {
      const parsed = new URL(trimmed);
      const parsedPath = parsed.pathname.toLowerCase();
      if (parsedPath === '/logo/logo.png' || parsedPath.endsWith('/logo/logo.png')) {
        return BRAND_LOGO;
      }
    } catch {
      // ignore
    }

    if (typeof window !== 'undefined') {
      const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
      if (isLocal && (trimmed.includes('mlmbookai.com') || trimmed.includes('mytesting.fun'))) {
        try {
          const parsed = new URL(trimmed);
          return `${parsed.pathname}${parsed.search}`;
        } catch {
          // ignore
        }
      }

      if (window.location.protocol === 'https:' && trimmed.startsWith('http://') && !trimmed.includes('localhost') && !trimmed.includes('127.0.0.1')) {
        return trimmed.replace('http://', 'https://');
      }
    }
    return trimmed;
  }

  // Case 3: Relative path cleanup (ensure leading slash)
  const clean = trimmed.startsWith('/') ? trimmed : `/${trimmed}`;
  if (clean === '/logo/logo.png' || clean.startsWith('/logo/logo.png?')) {
    return BRAND_LOGO;
  }

  // If in browser on localhost/127.0.0.1 without custom VITE_ASSET_URL or VITE_BACKEND_URL, keep root-relative so Vite proxy handles it
  if (typeof window !== 'undefined') {
    const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    if (isLocal && !env.VITE_ASSET_URL && !env.VITE_BACKEND_URL) {
      return clean;
    }
  }

  return prefixUrl(clean);
}

/**
 * Resolves avatar image path with graceful fallback
 */
export function getAvatarUrl(photoPath) {
  if (!photoPath) {
    return DEFAULT_AVATAR;
  }
  if (typeof photoPath === 'object') {
    photoPath = photoPath.profile_photo_url || photoPath.profile_photo || photoPath.avatar_url || '';
  }
  if (!photoPath || typeof photoPath !== 'string') {
    return DEFAULT_AVATAR;
  }
  const trimmed = photoPath.trim();
  if (!trimmed || trimmed === 'default.png' || trimmed.includes('dashboard/image/profile.png')) {
    return DEFAULT_AVATAR;
  }
  let cleaned = cleanHostPath(trimmed);
  if (cleaned.startsWith('http://') || cleaned.startsWith('https://')) {
    return cleaned;
  }
  cleaned = cleaned.replace(/^\/+/, '');
  if (cleaned.startsWith('uploads/')) {
    return prefixUrl(`/${cleaned}`);
  }
  if (cleaned.startsWith('storage/')) {
    return prefixUrl(`/${cleaned}`);
  }
  if (cleaned.startsWith('member/')) {
    return prefixUrl(`/storage/${cleaned}`);
  }
  if (
    cleaned.startsWith('member_assets/') ||
    cleaned.startsWith('f_assets/') ||
    cleaned.startsWith('admin_assets/') ||
    cleaned.startsWith('logo/')
  ) {
    return prefixUrl(`/${cleaned}`);
  }
  return prefixUrl(`/uploads/profile/${cleaned}`);
}

/**
 * Resolves cover photo URL
 */
export function getCoverUrl(coverPath) {
  if (!coverPath) {
    return null;
  }
  let cleaned = cleanHostPath(coverPath);
  if (cleaned.startsWith('http://') || cleaned.startsWith('https://')) {
    return cleaned;
  }
  cleaned = cleaned.replace(/^\/+/, '');
  if (cleaned.startsWith('uploads/')) {
    return prefixUrl(`/${cleaned}`);
  }
  if (cleaned.startsWith('storage/')) {
    return prefixUrl(`/${cleaned}`);
  }
  if (cleaned.startsWith('member/')) {
    return prefixUrl(`/storage/${cleaned}`);
  }
  return prefixUrl(`/uploads/cover/${cleaned}`);
}

/**
 * Resolves uploaded media URL (posts, stories, marketplace, communities)
 */
export function getMediaUrl(mediaPath, folder = 'posts/images') {
  if (!mediaPath) return null;
  if (typeof mediaPath !== 'string') return null;
  if (mediaPath.startsWith('blob:') || mediaPath.startsWith('data:')) {
    return mediaPath;
  }
  const cleaned = cleanHostPath(mediaPath);
  if (cleaned.startsWith('http://') || cleaned.startsWith('https://')) {
    return cleaned;
  }
  if (cleaned.startsWith('/')) {
    return prefixUrl(cleaned);
  }
  if (cleaned.startsWith('uploads/') || cleaned.startsWith('storage/')) {
    return prefixUrl(`/${cleaned}`);
  }
  if (cleaned.startsWith('member/')) {
    return prefixUrl(`/storage/${cleaned}`);
  }
  return prefixUrl(`/uploads/${folder}/${cleaned}`);
}

/**
 * Resolves user initials from full name
 */
export function getInitials(name) {
  if (!name) return 'MB';
  const parts = name.trim().split(/\s+/);
  return parts.slice(0, 2).map((p) => p[0].toUpperCase()).join('') || 'MB';
}


