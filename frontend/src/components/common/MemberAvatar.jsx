import { useState, useEffect } from 'react';
import { getAvatarUrl, DEFAULT_AVATAR } from '../../utils/assetHelper';

/**
 * Extracts 1-2 initials from a display name
 */
function getInitials(name) {
  if (!name) return 'M';
  const parts = String(name).trim().split(/\s+/);
  return parts.slice(0, 2).map((p) => p[0].toUpperCase()).join('') || 'M';
}

/**
 * Global Shared Member Avatar Component for MLM_Book
 *
 * Ensures consistent, reliable member profile image rendering across:
 * - Incoming Connection Requests
 * - My Connections
 * - New Connections / Find Friends
 * - Disconnections
 * - Profile Pages
 *
 * Features:
 * - Uses authoritative member profile photo (NEVER cover photo/banner)
 * - Safe error handling: switches to clean initials/fallback on load failure
 * - Never shows native browser broken-image icons or alt text
 * - Responsive sizing and circular object-fit styling
 */
export function MemberAvatar({
  member,
  src,
  name,
  alt,
  size = 48,
  className = '',
  style = {},
  fallbackType = 'initials', // 'initials' | 'default'
  ...restProps
}) {
  const [imgError, setImgError] = useState(false);

  // Authoritative identity resolution
  const displayName = name || member?.name || member?.user_id || 'Member';
  const imgAlt = alt !== undefined ? alt : displayName;

  // Determine photo candidate: strictly profile photo, NEVER cover_photo
  const candidate = src
    || member?.profile_photo_url
    || member?.profile_photo
    || (member?.avatar_url && !member.avatar_url.includes('profile.png') ? member.avatar_url : null);

  const isDefaultPlaceholder = candidate && (
    candidate.includes('default.png') ||
    candidate.includes('member_assets/images/dashboard/image/profile.png')
  );

  const resolvedUrl = candidate && !isDefaultPlaceholder ? getAvatarUrl(candidate) : null;

  // Reset error state if image source changes
  useEffect(() => {
    setImgError(false);
  }, [resolvedUrl]);

  const avatarStyle = {
    width: `${size}px`,
    height: `${size}px`,
    minWidth: `${size}px`,
    minHeight: `${size}px`,
    borderRadius: '50%',
    flexShrink: 0,
    ...style,
  };

  // 1. If valid photo and no load error, render image
  if (resolvedUrl && !imgError) {
    return (
      <img
        src={resolvedUrl}
        alt={imgAlt}
        className={`member-avatar-img ${className}`}
        style={{
          ...avatarStyle,
          objectFit: 'cover',
          display: 'block',
        }}
        loading="lazy"
        onError={() => setImgError(true)}
        {...restProps}
      />
    );
  }

  // 2. Fallback: Default system avatar image if requested
  if (fallbackType === 'default') {
    return (
      <img
        src={DEFAULT_AVATAR}
        alt={imgAlt}
        className={`member-avatar-img member-avatar-img--default ${className}`}
        style={{
          ...avatarStyle,
          objectFit: 'cover',
          display: 'block',
        }}
        loading="lazy"
        {...restProps}
      />
    );
  }

  // 3. Fallback: Clean initials badge (default standard for social lists)
  const fontSize = Math.max(10, Math.round(size * 0.38));

  return (
    <span
      className={`avatar post-avatar-initials member-avatar-initials ${className}`}
      style={{
        background: style.background || 'linear-gradient(135deg, #176bff, #7146ed)',
        color: style.color || '#fff',
        ...avatarStyle,
        fontSize: `${fontSize}px`,
        display: 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
        fontWeight: 700,
        userSelect: 'none',
      }}
      role="img"
      aria-label={`${displayName} initials`}
      {...restProps}
    >
      {getInitials(displayName)}
    </span>
  );
}

export default MemberAvatar;
