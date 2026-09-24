import React from 'react';
import { isMemberMobileVerified } from '../../utils/whatsappVerification';

/**
 * VerifiedBadge Component
 * Renders an official green tick verification badge if the member's mobile number is verified.
 *
 * @param {Object} props
 * @param {Object} props.member - Member object (checks `is_verified` or `mobile_verified_at`)
 * @param {number} [props.size=15] - Size of the badge in pixels
 * @param {boolean} [props.showText=false] - Whether to show "Verified" text pill
 * @param {boolean} [props.showUnverified=false] - Whether to show "Unverified" indicator when not verified
 * @param {string} [props.className=''] - Additional CSS classes
 * @param {Object} [props.style={}] - Additional inline styles
 */
export function VerifiedBadge({
  member,
  size = 15,
  showText = false,
  showUnverified = false,
  className = '',
  style = {},
}) {
  const isVerified = isMemberMobileVerified(member);

  if (isVerified) {
    return (
      <span
        className={`verified-member-badge verified-member-badge--active ${className}`}
        title="Verified Member • Mobile Verified on WhatsApp"
        aria-label="Verified Member"
        style={{
          display: 'inline-flex',
          alignItems: 'center',
          gap: showText ? '4px' : '0',
          verticalAlign: 'middle',
          marginLeft: '4px',
          flexShrink: 0,
          ...style,
        }}
      >
        <svg
          width={size}
          height={size}
          viewBox="0 0 24 24"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
          style={{ display: 'block', flexShrink: 0 }}
        >
          {/* Outer circle with gradient fill */}
          <circle cx="12" cy="12" r="11" fill="#10B981" />
          {/* Inner crisp white checkmark */}
          <path
            d="M7.5 12.5L10.5 15.5L16.5 9"
            stroke="#FFFFFF"
            strokeWidth="2.5"
            strokeLinecap="round"
            strokeLinejoin="round"
          />
        </svg>
        {showText && (
          <span
            style={{
              fontSize: `${Math.max(11, size * 0.8)}px`,
              fontWeight: 700,
              color: '#059669',
              lineHeight: 1,
            }}
          >
            Verified
          </span>
        )}
      </span>
    );
  }

  if (showUnverified) {
    return (
      <span
        className={`verified-member-badge verified-member-badge--unverified ${className}`}
        title="Unverified Member • Mobile number not verified yet"
        aria-label="Unverified Member"
        style={{
          display: 'inline-flex',
          alignItems: 'center',
          gap: showText ? '4px' : '0',
          verticalAlign: 'middle',
          marginLeft: '4px',
          flexShrink: 0,
          ...style,
        }}
      >
        <svg
          width={size}
          height={size}
          viewBox="0 0 24 24"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
          style={{ display: 'block', flexShrink: 0 }}
        >
          <circle cx="12" cy="12" r="11" fill="#E2E8F0" stroke="#94A3B8" strokeWidth="1.5" strokeDasharray="3 3" />
          <path
            d="M12 8V12M12 16H12.01"
            stroke="#94A3B8"
            strokeWidth="2"
            strokeLinecap="round"
          />
        </svg>
        {showText && (
          <span
            style={{
              fontSize: `${Math.max(11, size * 0.8)}px`,
              fontWeight: 600,
              color: '#94a3b8',
              lineHeight: 1,
            }}
          >
            Unverified
          </span>
        )}
      </span>
    );
  }

  return null;
}

export default VerifiedBadge;
