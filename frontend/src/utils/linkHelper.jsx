import { Link } from 'react-router-dom';
import { normalizeNotificationUrl } from './notificationNavigation';

/**
 * Punctuation characters commonly found at the end of URLs in natural text sentences.
 */
const TRAILING_PUNCTUATION = ['.', ',', ';', ':', '!', '?', '\'', '"'];

/**
 * Determines whether a given URL string points to an internal MLM Book application route.
 * Rejects non-web or dangerous schemes (javascript:, data:, vbscript:).
 * Excludes backend endpoints and asset directories so they do not get routed into the SPA router.
 *
 * @param {string} rawUrl
 * @returns {boolean}
 */
export function isInternalAppUrl(rawUrl) {
  if (!rawUrl || typeof rawUrl !== 'string') return false;

  const lower = rawUrl.toLowerCase().trim();
  if (
    lower.startsWith('javascript:') ||
    lower.startsWith('data:') ||
    lower.startsWith('vbscript:')
  ) {
    return false;
  }

  // Direct relative routes
  if (rawUrl.startsWith('/')) {
    return rawUrl.startsWith('/member') || rawUrl === '/';
  }

  const fullUrl = rawUrl.startsWith('www.') ? `https://${rawUrl}` : rawUrl;

  try {
    const parsed = new URL(fullUrl);
    const host = parsed.hostname.toLowerCase();
    const currentHost =
      typeof window !== 'undefined' && window.location?.hostname
        ? window.location.hostname.toLowerCase()
        : '';

    const isMlmHost =
      host === 'mlmbookai.com' ||
      host === 'www.mlmbookai.com' ||
      (currentHost && host === currentHost);

    if (!isMlmHost) return false;

    const pathname = parsed.pathname || '';
    // Must be an SPA route (starts with /member or root /)
    // Exclude backend APIs or uploaded media paths
    if (
      pathname.startsWith('/backend') ||
      pathname.startsWith('/storage') ||
      pathname.startsWith('/uploads') ||
      pathname.startsWith('/api')
    ) {
      return false;
    }

    return pathname.startsWith('/member') || pathname === '/';
  } catch {
    return false;
  }
}

/**
 * Parses a plain text string into an array of text and URL tokens.
 * Handles trailing punctuation, balanced parentheses, query strings, and hashes safely.
 *
 * @param {string} text
 * @returns {Array<{ type: 'text' | 'url', value?: string, rawUrl?: string, displayedText?: string }>}
 */
export function parseContentWithLinks(text) {
  if (!text || typeof text !== 'string') return [];

  // Recognize http://, https://, and www. links
  const urlRegex = /(https?:\/\/[^\s<]+|www\.[^\s<]+)/gi;
  const parts = [];
  let lastIndex = 0;
  let match;

  const appendText = (str) => {
    if (!str) return;
    const last = parts[parts.length - 1];
    if (last && last.type === 'text') {
      last.value += str;
    } else {
      parts.push({ type: 'text', value: str });
    }
  };

  while ((match = urlRegex.exec(text)) !== null) {
    if (match.index > lastIndex) {
      appendText(text.slice(lastIndex, match.index));
    }

    let rawUrl = match[0];
    let trailingPunct = '';

    // Strip trailing punctuation while respecting balanced brackets/parentheses
    while (rawUrl.length > 0) {
      const lastChar = rawUrl[rawUrl.length - 1];
      if (TRAILING_PUNCTUATION.includes(lastChar)) {
        trailingPunct = lastChar + trailingPunct;
        rawUrl = rawUrl.slice(0, -1);
      } else if (
        lastChar === ')' &&
        (rawUrl.match(/\(/g) || []).length < (rawUrl.match(/\)/g) || []).length
      ) {
        trailingPunct = lastChar + trailingPunct;
        rawUrl = rawUrl.slice(0, -1);
      } else if (
        lastChar === ']' &&
        (rawUrl.match(/\[/g) || []).length < (rawUrl.match(/\]/g) || []).length
      ) {
        trailingPunct = lastChar + trailingPunct;
        rawUrl = rawUrl.slice(0, -1);
      } else if (
        lastChar === '}' &&
        (rawUrl.match(/\{/g) || []).length < (rawUrl.match(/\}/g) || []).length
      ) {
        trailingPunct = lastChar + trailingPunct;
        rawUrl = rawUrl.slice(0, -1);
      } else {
        break;
      }
    }

    if (rawUrl) {
      parts.push({
        type: 'url',
        rawUrl,
        displayedText: rawUrl,
      });
    }

    if (trailingPunct) {
      appendText(trailingPunct);
    }

    lastIndex = match.index + match[0].length;
  }

  if (lastIndex < text.length) {
    appendText(text.slice(lastIndex));
  }

  return parts;
}

/**
 * Resolves destination route for internal URLs, reusing the existing normalizeNotificationUrl
 * or extracting the pathname, search, and hash.
 *
 * @param {string} fullUrl
 * @returns {string}
 */
export function resolveInternalRoute(fullUrl) {
  const normalized = normalizeNotificationUrl(fullUrl);
  if (normalized) {
    return normalized;
  }

  try {
    const parsed = new URL(fullUrl.startsWith('/') ? `http://localhost${fullUrl}` : fullUrl);
    let pathname = parsed.pathname || '';
    if (pathname.length > 1 && pathname.endsWith('/')) {
      pathname = pathname.slice(0, -1);
    }
    return `${pathname}${parsed.search || ''}${parsed.hash || ''}`;
  } catch {
    return fullUrl;
  }
}

/**
 * Renders post/comment text with valid clickable links.
 * - Internal URLs use React Router's <Link> for SPA client-side navigation.
 * - External URLs open safely in a new tab with target="_blank" and rel="noopener noreferrer".
 * - Calls e.stopPropagation() so parent card interactions (e.g. ad or card clicks) are not triggered.
 * - Rejects unsafe schemes (javascript:, data:).
 *
 * @param {string} content
 * @returns {React.ReactNode}
 */
export function renderContentWithLinks(content) {
  if (!content || typeof content !== 'string') {
    return content || null;
  }

  // Fast path: if no URL prefix exists, return content unchanged with 0 overhead
  if (
    !content.includes('http://') &&
    !content.includes('https://') &&
    !content.includes('www.')
  ) {
    return content;
  }

  const parts = parseContentWithLinks(content);
  if (!parts.length) {
    return content;
  }

  return parts.map((part, index) => {
    if (part.type !== 'url') {
      return part.value;
    }

    const { rawUrl, displayedText } = part;
    const lower = rawUrl.toLowerCase().trim();

    // Defense in depth: do not linkify unsafe schemes
    if (
      lower.startsWith('javascript:') ||
      lower.startsWith('data:') ||
      lower.startsWith('vbscript:')
    ) {
      return displayedText;
    }

    const fullUrl = rawUrl.startsWith('www.') ? `https://${rawUrl}` : rawUrl;

    if (isInternalAppUrl(rawUrl)) {
      const internalPath = resolveInternalRoute(fullUrl);

      return (
        <Link
          key={`content-link-${index}`}
          to={internalPath}
          className="post-content-link"
          onClick={(e) => {
            e.stopPropagation();
          }}
        >
          {displayedText}
        </Link>
      );
    }

    return (
      <a
        key={`content-link-${index}`}
        href={fullUrl}
        target="_blank"
        rel="noopener noreferrer"
        className="post-content-link"
        onClick={(e) => {
          e.stopPropagation();
        }}
      >
        {displayedText}
      </a>
    );
  });
}

export default renderContentWithLinks;
