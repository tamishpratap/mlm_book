import { useState, useEffect } from 'react';
import { getMemberAvatarUrl } from '../../utils/mediaHelper';

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
 * - Automatically falls back to user initials badge with gradient on load error or missing photo
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
  const initials = getInitials(displayName);

  // Determine photo candidate
  const candidate = src || getMemberAvatarUrl(member);

  // If candidate is a default placeholder, treat as no custom photo
  const isDefaultPlaceholder = candidate && (
    candidate.includes('default.png') ||
    candidate.includes('member_assets/images/dashboard/image/profile.png') ||
    candidate.includes('dashboard/image/profile.png')
  );

  const resolvedUrl = candidate && !isDefaultPlaceholder ? candidate : null;

  useEffect(() => {
    setImgError(false);
  }, [resolvedUrl]);

  if (resolvedUrl && !imgError) {
    return (
      <img
        src={resolvedUrl}
        alt=""
        className={`${size} rounded-full object-cover border border-slate-200 shadow-2xs shrink-0 ring-1 ring-slate-200/60 ${className}`}
        loading="lazy"
        onError={() => setImgError(true)}
        {...restProps}
      />
    );
  }

  return (
    <div
      className={`${size} rounded-full text-white flex items-center justify-center font-bold ${textSize} shrink-0 shadow-xs ring-2 ring-white/80 select-none ${className}`}
      style={{
        background: 'linear-gradient(135deg, #176bff, #7146ed)',
        color: '#ffffff',
        ...style,
      }}
      title={displayName}
      role="img"
      aria-label={`${displayName} initials`}
      {...restProps}
    >
      {initials}
    </div>
  );
}

export default MemberAvatar;
