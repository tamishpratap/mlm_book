/**
 * Media Helper Utility for MLM Book Admin Portal
 * Handles safe URL resolution, protocol upgrades, and media type detection
 * across different deployment environments and storage configurations.
 */

const env = (typeof import.meta !== 'undefined' && import.meta.env) ? import.meta.env : {};
const ASSET_BASE = (env.VITE_ASSET_URL || env.VITE_BACKEND_URL || 'https://mlmbookai.com').replace(/\/+$/, '');

/**
 * Prefixes a relative path with the configured asset/backend origin.
 */
export function prefixUrl(path) {
  if (!path) return path;
  const normalized = String(path).replace(/\\/g, '/');
  if (
    normalized.startsWith('http://') ||
    normalized.startsWith('https://') ||
    normalized.startsWith('data:') ||
    normalized.startsWith('blob:')
  ) {
    return normalized;
  }
  const cleanPath = normalized.startsWith('/') ? normalized : `/${normalized}`;
  return ASSET_BASE ? `${ASSET_BASE}${cleanPath}` : cleanPath;
}

export const BRAND_LOGO = prefixUrl('/logo/logo.png');

/**
 * Resolves a reliable, usable URL for a story media item.
 * Prioritizes media_url, with safe fallback to media_path, image, or image_url.
 * Normalizes relative paths, handles legacy prefixes, and upgrades HTTP to HTTPS if on secure origin.
 *
 * @param {Object|string} story - Story object or raw path string
 * @returns {string|null} - Absolute or root-relative media URL, or null
 */
export function getStoryMediaUrl(story) {
  if (!story) return null;

  const raw = typeof story === 'string'
    ? story
    : story.media_url || story.media_path || story.image_url || story.image || story.file_url || story.file;

  if (!raw || typeof raw !== 'string') return null;

  const trimmed = raw.trim();
  if (!trimmed) return null;

  // Case 1: Fully qualified URL (http:// or https://)
  if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
    // Protocol upgrade: If page is loaded over HTTPS, upgrade HTTP matching origin/domain
    if (typeof window !== 'undefined' && window.location.protocol === 'https:' && trimmed.startsWith('http://')) {
      try {
        const parsed = new URL(trimmed);
        if (parsed.hostname === window.location.hostname || parsed.hostname === 'mlmbookai.com') {
          return trimmed.replace('http://', 'https://');
        }
      } catch {
        // preserve trimmed
      }
    }
    return trimmed;
  }

  // Case 2: Relative path cleanup
  let clean = trimmed.startsWith('/') ? trimmed : `/${trimmed}`;

  // If erroneously prefixed with /storage/uploads/, normalize to /uploads/
  if (clean.startsWith('/storage/uploads/')) {
    clean = clean.replace('/storage/uploads/', '/uploads/');
  }

  // In development, if VITE_API_BASE_URL has an origin (e.g. http://localhost:8000), prepend origin
  const apiBase = import.meta.env?.VITE_API_BASE_URL || '';
  if (apiBase.startsWith('http://') || apiBase.startsWith('https://')) {
    try {
      const origin = new URL(apiBase).origin;
      return `${origin}${clean}`;
    } catch {
      // fallback to clean relative path
    }
  }

  return clean;
}

/**
 * Checks whether a story represents video media.
 * Case-insensitive against media_type ('video', 'VIDEO', 'video/mp4')
 * and falls back to inspecting filename extensions.
 *
 * @param {Object} story
 * @param {string} [resolvedUrl]
 * @returns {boolean}
 */
export function isVideoStory(story, resolvedUrl) {
  if (story?.media_type) {
    const type = String(story.media_type).toLowerCase().trim();
    if (type === 'video' || type.startsWith('video/')) return true;
    if (type === 'image' || type.startsWith('image/')) return false;
  }

  const targetUrl = resolvedUrl || getStoryMediaUrl(story);
  if (targetUrl) {
    const cleanUrl = targetUrl.split('?')[0].toLowerCase();
    if (/\.(mp4|webm|ogg|mov|m4v|mkv)$/i.test(cleanUrl)) return true;
  }

  return false;
}

/**
 * Resolves a reliable, usable URL for a member's cover banner photo.
 * Prioritizes cover_banner_url, cover_photo_url, with safe fallback to cover_photo / cover / cover_image.
 * Normalizes relative paths, handles legacy prefixes, and upgrades HTTP to HTTPS if on secure origin.
 *
 * @param {Object|string} member - Member object or raw path string
 * @returns {string|null} - Absolute or root-relative cover URL, or null
 */
export function getMemberCoverUrl(member) {
  if (!member) return null;

  if (typeof member === 'string') {
    if (member.startsWith('blob:') || member.startsWith('data:')) {
      return member;
    }
  }

  const raw = typeof member === 'string'
    ? member
    : member.cover_banner_url || member.cover_photo_url || member.cover_photo || member.cover_image || member.cover;

  if (!raw || typeof raw !== 'string') return null;

  const trimmed = raw.trim();
  if (!trimmed) return null;

  if (trimmed.startsWith('blob:') || trimmed.startsWith('data:')) {
    return trimmed;
  }

  // Case 1: Fully qualified URL (http:// or https://)
  if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
    if (typeof window !== 'undefined' && window.location.protocol === 'https:' && trimmed.startsWith('http://')) {
      try {
        const parsed = new URL(trimmed);
        if (parsed.hostname === window.location.hostname || parsed.hostname === 'mlmbookai.com') {
          return trimmed.replace('http://', 'https://');
        }
      } catch {
        // preserve trimmed
      }
    }
    return trimmed;
  }

  // Case 2: Relative path cleanup
  let clean = trimmed.startsWith('/') ? trimmed : `/${trimmed}`;

  if (clean.startsWith('/storage/uploads/')) {
    clean = clean.replace('/storage/uploads/', '/uploads/');
  }

  if (!clean.startsWith('/uploads/') && !clean.startsWith('/storage/')) {
    clean = `/uploads/cover${clean}`;
  }

  const apiBase = import.meta.env?.VITE_API_BASE_URL || '';
  if (apiBase.startsWith('http://') || apiBase.startsWith('https://')) {
    try {
      const origin = new URL(apiBase).origin;
      return `${origin}${clean}`;
    } catch {
      // fallback
    }
  }

  return clean;
}

/**
 * Resolves a reliable, usable URL for a member's avatar / profile photo.
 * Prioritizes avatar_url, profile_photo_url, with fallback to profile_photo / avatar.
 *
 * @param {Object|string} member - Member object or raw path string
 * @returns {string|null} - Absolute or root-relative avatar URL, or null
 */
export function getMemberAvatarUrl(member) {
  if (!member) return null;

  if (typeof member === 'string') {
    if (member.startsWith('blob:') || member.startsWith('data:')) {
      return member;
    }
  }

  const raw = typeof member === 'string'
    ? member
    : member.avatar_url || member.profile_photo_url || member.profile_photo || member.avatar;

  if (!raw || typeof raw !== 'string') return null;

  const trimmed = raw.trim();
  if (!trimmed) return null;

  if (trimmed.startsWith('blob:') || trimmed.startsWith('data:')) {
    return trimmed;
  }
  
    // Filter out default template/placeholder avatars
  if (
    trimmed.includes('default.png') ||
    trimmed.includes('dashboard/image/profile.png') ||
    trimmed.includes('member_assets/images')
  ) {
    return null;
  }

  // Case 1: Fully qualified URL (http:// or https://)
  if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
    if (typeof window !== 'undefined' && window.location.protocol === 'https:' && trimmed.startsWith('http://')) {
      try {
        const parsed = new URL(trimmed);
        if (parsed.hostname === window.location.hostname || parsed.hostname === 'mlmbookai.com') {
          return trimmed.replace('http://', 'https://');
        }
      } catch {
        // preserve trimmed
      }
    }
    return trimmed;
  }

  // Case 2: Relative path cleanup
  let clean = trimmed.startsWith('/') ? trimmed : `/${trimmed}`;

  if (clean.startsWith('/storage/uploads/')) {
    clean = clean.replace('/storage/uploads/', '/uploads/');
  }

  if (!clean.startsWith('/uploads/') && !clean.startsWith('/storage/') && !clean.startsWith('/member_assets/')) {
    clean = `/uploads/profile${clean}`;
  }

  const apiBase = import.meta.env?.VITE_API_BASE_URL || '';
  if (apiBase.startsWith('http://') || apiBase.startsWith('https://')) {
    try {
      const origin = new URL(apiBase).origin;
      return `${origin}${clean}`;
    } catch {
      // fallback
    }
  }

  return clean;
}

/**
 * Resolves a reliable, usable URL for platform branding assets (logo, dark logo, favicon).
 * Handles absolute URLs, relative storage paths, blob previews, dev proxy origins, and cache-busting.
 *
 * @param {string} raw - Asset path or URL
 * @param {string} [fallback='/logo/logo.png'] - Fallback URL if raw is missing
 * @returns {string}
 */
export function getBrandingAssetUrl(raw, fallback = BRAND_LOGO) {
  if (!raw || typeof raw !== 'string') return fallback;

  const trimmed = raw.trim();
  if (!trimmed) return fallback;

  // Case 1: In-memory Blob preview or data URL
  if (trimmed.startsWith('blob:') || trimmed.startsWith('data:')) {
    return trimmed;
  }

  // Case 2: Fully qualified URL (http:// or https://)
  if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
    if (typeof window !== 'undefined') {
      const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
      if (isLocal && !env.VITE_ASSET_URL && !env.VITE_BACKEND_URL && trimmed.includes('mlmbookai.com')) {
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

  // If in browser on localhost/127.0.0.1 without custom VITE_ASSET_URL or VITE_BACKEND_URL, keep root-relative
  if (typeof window !== 'undefined') {
    const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    if (isLocal && !env.VITE_ASSET_URL && !env.VITE_BACKEND_URL) {
      return clean;
    }
  }

  return prefixUrl(clean);
}

/**
 * Generates an ordered list of candidate URLs to load a post/report media attachment.
 * Prioritizes local proxy/relative paths when on localhost/127.0.0.1 to avoid remote 404s,
 * includes direct URLs, normalized relative paths, and XAMPP/Apache fallback paths.
 *
 * @param {Object|string} target - Post or Report Target object or media URL string
 * @param {string} [fallbackPath] - Optional secondary path or URL
 * @returns {string[]}
 */
export function getPostMediaCandidates(target, fallbackPath = null) {
  const candidates = [];
  const seen = new Set();

  const add = (url) => {
    if (!url || typeof url !== 'string') return;
    const trimmed = url.trim();
    if (!trimmed || seen.has(trimmed)) return;
    seen.add(trimmed);
    candidates.push(trimmed);
  };

  if (!target && !fallbackPath) return candidates;

  const rawUrl = typeof target === 'string'
    ? target
    : (target?.media_url || target?.image_url || target?.file_url || null);

  const rawPath = typeof target === 'object' && target !== null
    ? (target.media_path || target.image || target.file || fallbackPath)
    : fallbackPath;

  const isLocalHost = typeof window !== 'undefined' &&
    (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1');

  // 1. If rawUrl exists
  if (rawUrl && typeof rawUrl === 'string') {
    const trimmedUrl = rawUrl.trim();
    if (trimmedUrl.startsWith('blob:') || trimmedUrl.startsWith('data:')) {
      add(trimmedUrl);
      return candidates;
    }

    if (trimmedUrl.startsWith('http://') || trimmedUrl.startsWith('https://')) {
      // If on localhost and URL is on mlmbookai.com, prioritize local relative path first
      if (isLocalHost && trimmedUrl.includes('mlmbookai.com')) {
        try {
          const parsed = new URL(trimmedUrl);
          add(`${parsed.pathname}${parsed.search}`);
        } catch {
          // ignore
        }
      }

      add(trimmedUrl);

      // Protocol upgrade candidate
      if (typeof window !== 'undefined' && window.location.protocol === 'https:' && trimmedUrl.startsWith('http://')) {
        add(trimmedUrl.replace('http://', 'https://'));
      }
    } else {
      // Relative URL passed
      let clean = trimmedUrl.startsWith('/') ? trimmedUrl : `/${trimmedUrl}`;
      if (clean.startsWith('/storage/uploads/')) {
        clean = clean.replace('/storage/uploads/', '/uploads/');
      }
      add(clean);
    }
  }

  // 2. If rawPath exists
  if (rawPath && typeof rawPath === 'string') {
    const trimmedPath = rawPath.trim();
    if (
      trimmedPath.startsWith('http://') ||
      trimmedPath.startsWith('https://') ||
      trimmedPath.startsWith('blob:') ||
      trimmedPath.startsWith('data:')
    ) {
      add(trimmedPath);
    } else {
      let cleanPath = trimmedPath.startsWith('/') ? trimmedPath : `/${trimmedPath}`;
      if (cleanPath.startsWith('/storage/uploads/')) {
        cleanPath = cleanPath.replace('/storage/uploads/', '/uploads/');
      }
      add(cleanPath);

      // On localhost/XAMPP, also add Apache virtual/physical subfolder fallback if path begins with /uploads/
      if (isLocalHost && cleanPath.startsWith('/uploads/')) {
        add(`/backend/backend/public${cleanPath}`);
      }
    }
  }

  return candidates;
}

/**
 * Resolves the primary, canonical URL for a post / report media attachment.
 *
 * @param {Object|string} target - Post or Report Target object or media URL string
 * @param {string} [fallbackPath] - Optional secondary path or URL
 * @returns {string|null}
 */
export function getPostMediaUrl(target, fallbackPath = null) {
  const candidates = getPostMediaCandidates(target, fallbackPath);
  return candidates.length > 0 ? candidates[0] : null;
}

/**
 * Checks whether a post/target represents video media.
 * Case-insensitive against media_type ('video', 'VIDEO', 'video/mp4')
 * and falls back to inspecting filename extensions.
 *
 * @param {Object|string} target
 * @param {string} [resolvedUrl]
 * @returns {boolean}
 */
export function isVideoPost(target, resolvedUrl = null) {
  if (target && typeof target === 'object' && target.media_type) {
    const type = String(target.media_type).toLowerCase().trim();
    if (type === 'video' || type.startsWith('video/')) return true;
    if (type === 'image' || type.startsWith('image/')) return false;
  }

  const urlToCheck = resolvedUrl || (typeof target === 'string' ? target : (target?.media_url || target?.media_path));
  if (urlToCheck && typeof urlToCheck === 'string') {
    const cleanUrl = urlToCheck.split('?')[0].toLowerCase();
    if (/\.(mp4|webm|ogg|mov|m4v|mkv)$/i.test(cleanUrl)) return true;
  }

  return false;
}

export default {
  getStoryMediaUrl,
  isVideoStory,
  getMemberCoverUrl,
  getMemberAvatarUrl,
  getBrandingAssetUrl,
  getPostMediaCandidates,
  getPostMediaUrl,
  isVideoPost,
};

