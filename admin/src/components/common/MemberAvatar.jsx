import { useState, useEffect } from 'react';
import { getMemberAvatarUrl, DEFAULT_AVATAR } from '../../utils/mediaHelper';

/**
 * Extracts 1-2 uppercase initials from a display name
 */
export function getInitials(name) {
  if (!name) return 'M';
  const parts = String(name).trim().split(/\s+/);
  return parts.slice(0, 2).map((p) => p[0].toUpperCase()).join('') || 'M';
}

/**
 * Global MemberAvatar component for MLM Book Admin Portal
 *
 * Matches User Panel behavior:
 * - Renders member profile photo if valid and reachable
 * - Discards default template placeholder paths (e.g. profile.png)
 * - Automatically falls back to default avatar image on load error or missing photo
 * - Never displays browser broken-image icons or leaking alt text
 */
export function MemberAvatar({
  member,
  src,
  name,
  size = 'w-10 h-10',
  className = '',
  style = {},
  textSize = 'text-xs',
  ...restProps
}) {
  const [imgError, setImgError] = useState(false);

  const displayName = name || member?.name || member?.user_id || 'Member';

  // Determine photo candidate
  const candidate = src || getMemberAvatarUrl(member);

  // If candidate is a default placeholder, treat as no custom photo
  const isDefaultPlaceholder = candidate && (
    candidate.includes('default.png') ||
    candidate.includes('member_assets/images/dashboard/image/profile.png') ||
    candidate.includes('dashboard/image/profile.png') ||
    candidate.includes('default-avatar.png')
  );

  const resolvedUrl = candidate && !isDefaultPlaceholder ? candidate : null;

  useEffect(() => {
    setImgError(false);
  }, [resolvedUrl]);

  if (resolvedUrl && !imgError) {
    return (
      <img
        src={resolvedUrl}
        alt={displayName}
        className={`${size} rounded-full object-cover border border-slate-200 shadow-2xs shrink-0 ring-1 ring-slate-200/60 ${className}`}
        loading="lazy"
        onError={() => setImgError(true)}
        style={style}
        {...restProps}
      />
    );
  }

  const fallbackSrc = DEFAULT_AVATAR || '/default-avatar.png';

  return (
    <img
      src={fallbackSrc}
      alt={displayName}
      className={`${size} rounded-full object-cover border border-slate-200 shadow-2xs shrink-0 ring-1 ring-slate-200/60 ${className}`}
      loading="lazy"
      style={style}
      {...restProps}
    />
  );
}

export default MemberAvatar;
