import { useRef, useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import useAuth from '../../hooks/useAuth';
import { getAvatarUrl } from '../../utils/assetHelper';
import VerifiedBadge from '../../components/common/VerifiedBadge';
import {
  ChevronDown,
  ChevronRight,
  UserRound,
  Pencil,
  UserRoundCheck,
  Settings,
  ShieldCheck,
  Gift,
  Wallet,
  WalletCards,
  Coins,
  LogOut,
} from 'lucide-react';

export function ProfileDropdown({ isOpen, onToggle, onClose, onOpenReferral, onOpenVerification, onOpenRewardWallet }) {
  const { user, logout } = useAuth();
  const menuRef = useRef(null);
  const triggerRef = useRef(null);
  const dropdownRef = useRef(null);
  const scrollAreaRef = useRef(null);
  const navigate = useNavigate();
  const [avatarImgError, setAvatarImgError] = useState(false);

  useEffect(() => {
    setAvatarImgError(false);
  }, [user?.profile_photo]);

  // Close dropdown on click outside
  useEffect(() => {
    function handleClickOutside(event) {
      if (menuRef.current && !menuRef.current.contains(event.target)) {
        if (onClose) onClose();
      }
    }

    if (isOpen) {
      document.addEventListener('mousedown', handleClickOutside);
    }
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [isOpen, onClose]);

  // Reset internal scroll to top when dropdown opens
  useEffect(() => {
    if (isOpen && scrollAreaRef.current) {
      scrollAreaRef.current.scrollTop = 0;
    }
  }, [isOpen]);

  // Keyboard navigation inside dropdown and on trigger
  useEffect(() => {
    if (!isOpen) return;

    function handleKeyDown(event) {
      if (event.key === 'Escape') {
        event.preventDefault();
        if (onClose) onClose();
        if (triggerRef.current) {
          triggerRef.current.focus();
        }
        return;
      }

      if (!dropdownRef.current) return;
      const items = Array.from(
        dropdownRef.current.querySelectorAll('a[role="menuitem"], button[role="menuitem"]')
      );
      const currentIndex = items.indexOf(document.activeElement);

      if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();
        const direction = event.key === 'ArrowDown' ? 1 : -1;
        const nextIndex = (currentIndex + direction + items.length) % items.length;
        const nextItem = items[nextIndex];
        if (nextItem) {
          nextItem.focus();
          nextItem.scrollIntoView({ block: 'nearest' });
        }
      } else if (event.key === 'Home') {
        event.preventDefault();
        items[0]?.focus();
        items[0]?.scrollIntoView({ block: 'nearest' });
      } else if (event.key === 'End') {
        event.preventDefault();
        items[items.length - 1]?.focus();
        items[items.length - 1]?.scrollIntoView({ block: 'nearest' });
      }
    }

    document.addEventListener('keydown', handleKeyDown);
    return () => {
      document.removeEventListener('keydown', handleKeyDown);
    };
  }, [isOpen, onClose]);

  const handleTriggerKeyDown = (e) => {
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      if (!isOpen && onToggle) {
        onToggle();
      }
      setTimeout(() => {
        if (dropdownRef.current) {
          const firstItem = dropdownRef.current.querySelector('[role="menuitem"]');
          firstItem?.focus();
        }
      }, 50);
    }
  };

  const handleLogout = async () => {
    if (onClose) onClose();
    await logout();
    navigate('/member/login');
  };

  if (!user) return null;

  const initials = user.name
    ? user.name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .substring(0, 2)
        .toUpperCase()
    : 'MB';

  const avatarUrl = getAvatarUrl(user.profile_photo);

  return (
    <div className="profile-menu-wrap" ref={menuRef} data-profile-menu>
      <button
        ref={triggerRef}
        className="profile-button profile-trigger"
        type="button"
        aria-label="Open profile menu"
        aria-expanded={isOpen}
        aria-controls="member-profile-dropdown"
        data-profile-trigger
        onClick={onToggle}
        onKeyDown={handleTriggerKeyDown}
      >
        <span className="profile-trigger__avatar">
          {user.profile_photo && !avatarImgError ? (
            <img src={avatarUrl} alt={user.name} onError={() => setAvatarImgError(true)} />
          ) : (
            <span
              className="avatar post-avatar-initials"
              style={{
                width: '100%',
                height: '100%',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                background: 'linear-gradient(135deg, #176bff, #7146ed)',
                color: '#fff',
                fontWeight: 700,
                fontSize: '0.85rem',
                borderRadius: '50%',
              }}
            >
              {initials}
            </span>
          )}
          <span className="online-dot" aria-hidden="true" />
        </span>
        <span className="profile-trigger__name" style={{ display: 'inline-flex', alignItems: 'center' }}>
          <span>{user.name}</span>
          <VerifiedBadge member={user} size={14} />
        </span>
        <ChevronDown size={16} className="profile-trigger__chevron" aria-hidden="true" />
      </button>

      <div
        ref={dropdownRef}
        className={`profile-dropdown ${isOpen ? 'is-open' : ''}`}
        id="member-profile-dropdown"
        role="menu"
        aria-label="Member profile menu"
        data-profile-dropdown
        hidden={!isOpen}
      >
        {/* Profile Header / Identity (fixed at top) */}
        <div className="profile-dropdown__header">
          <Link
            className="profile-dropdown__summary"
            to="/member/profile"
            role="menuitem"
            onClick={onClose}
          >
            {user.profile_photo && !avatarImgError ? (
              <img src={avatarUrl} alt={user.name} onError={() => setAvatarImgError(true)} />
            ) : (
              <span
                className="avatar post-avatar-initials"
                style={{
                  width: '40px',
                  height: '40px',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  background: 'linear-gradient(135deg, #176bff, #7146ed)',
                  color: '#fff',
                  fontWeight: 700,
                  fontSize: '0.95rem',
                  borderRadius: '50%',
                }}
              >
                {initials}
              </span>
            )}
            <span>
              <strong style={{ display: 'inline-flex', alignItems: 'center' }}>
                <span>{user.name}</span>
                <VerifiedBadge member={user} size={14} />
              </strong>
              <small>{user.email}</small>
              <em>View your profile</em>
            </span>
          </Link>
        </div>

        <div className="profile-dropdown__divider" />

        {/* Scrollable Menu Area (internal vertical scroll only) */}
        <div className="profile-dropdown__menu-scroll" ref={scrollAreaRef}>
          <Link
            className="profile-dropdown__item"
            to="/member/account/verify"
            role="menuitem"
            onClick={onClose}
            style={{
              background: user?.is_verified || user?.mobile_verified_at ? 'rgba(16, 185, 129, 0.06)' : 'rgba(245, 158, 11, 0.08)',
            }}
          >
            <span>
              <ShieldCheck size={18} color={user?.is_verified || user?.mobile_verified_at ? '#10b981' : '#f59e0b'} />
            </span>
            <div style={{ flex: 1, minWidth: 0, display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '8px' }}>
              <strong style={{ color: user?.is_verified || user?.mobile_verified_at ? '#065f46' : '#92400e', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                Account Verification
              </strong>
              {user?.is_verified || user?.mobile_verified_at ? (
                <span style={{ flexShrink: 0, fontSize: '11px', fontWeight: 700, color: '#10b981', background: '#dcfce7', padding: '2px 8px', borderRadius: '10px' }}>
                  Verified
                </span>
              ) : (
                <span style={{ flexShrink: 0, fontSize: '11px', fontWeight: 700, color: '#b45309', background: '#fef3c7', padding: '2px 8px', borderRadius: '10px' }}>
                  Get Verified
                </span>
              )}
            </div>
            <ChevronRight size={16} />
          </Link>

          <button
            className="profile-dropdown__item"
            type="button"
            role="menuitem"
            onClick={() => {
              onClose();
              if (onOpenReferral) {
                onOpenReferral();
              }
            }}
            style={{ width: '100%', background: 'none', border: 'none', textAlign: 'left', cursor: 'pointer' }}
          >
            <span>
              <Gift size={18} color="#176bff" />
            </span>
            <div style={{ flex: 1, minWidth: 0, display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '8px' }}>
              <strong style={{ color: '#1e293b', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>Referral Link &amp; ID</strong>
              {user?.is_verified || user?.mobile_verified_at ? (
                <span style={{ flexShrink: 0, fontSize: '11px', fontWeight: 700, color: '#176bff', background: '#eff6ff', padding: '2px 8px', borderRadius: '10px' }}>
                  Active
                </span>
              ) : (
                <span style={{ flexShrink: 0, fontSize: '11px', fontWeight: 700, color: '#94a3b8', background: '#f1f5f9', padding: '2px 8px', borderRadius: '10px' }}>
                  Inactive
                </span>
              )}
            </div>
            <ChevronRight size={16} />
          </button>

          <Link
            className="profile-dropdown__item"
            to="/member/deposit"
            role="menuitem"
            onClick={onClose}
          >
            <span>
              <Coins size={18} color="#059669" />
            </span>
            <div style={{ flex: 1, minWidth: 0, display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '8px' }}>
              <strong style={{ color: '#1e293b', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>Fund Wallet</strong>
              <span style={{ flexShrink: 0, fontSize: '11px', fontWeight: 800, color: '#047857', background: '#ecfdf5', padding: '2px 8px', borderRadius: '10px' }}>
                ${parseFloat(user?.p2p_wallet ?? user?.fund_wallet ?? 0).toFixed(2)} USD
              </span>
            </div>
            <ChevronRight size={16} />
          </Link>

          <button
            className="profile-dropdown__item"
            type="button"
            role="menuitem"
            onClick={() => {
              onClose();
              if (onOpenRewardWallet) {
                onOpenRewardWallet();
              }
            }}
            style={{ width: '100%', background: 'none', border: 'none', textAlign: 'left', cursor: 'pointer' }}
          >
            <span>
              <Wallet size={18} color="#059669" />
            </span>
            <div style={{ flex: 1, minWidth: 0, display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '8px' }}>
              <strong style={{ color: '#1e293b', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>Wallet</strong>
              <span style={{ flexShrink: 0, fontSize: '11px', fontWeight: 800, color: '#047857', background: '#ecfdf5', padding: '2px 8px', borderRadius: '10px' }}>
                ${parseFloat(user?.wallet ?? user?.reward_balance ?? 0).toFixed(4)} USD
              </span>
            </div>
            <ChevronRight size={16} />
          </button>

          <Link
            className="profile-dropdown__item"
            to="/member/web3-wallet"
            role="menuitem"
            onClick={onClose}
          >
            <span>
              <WalletCards size={18} color="#0284c7" />
            </span>
            <div style={{ flex: 1, minWidth: 0, display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '8px' }}>
              <strong style={{ color: '#1e293b', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>Web3 USDT Wallet</strong>
              {user?.reward_wallet_address && user?.reward_wallet_verified_at ? (
                <span style={{ flexShrink: 0, fontSize: '11px', fontWeight: 700, color: '#047857', background: '#ecfdf5', padding: '2px 8px', borderRadius: '10px' }}>
                  Verified
                </span>
              ) : (
                <span style={{ flexShrink: 0, fontSize: '11px', fontWeight: 700, color: '#64748b', background: '#f1f5f9', padding: '2px 8px', borderRadius: '10px' }}>
                  Not Configured
                </span>
              )}
            </div>
            <ChevronRight size={16} />
          </Link>

          <Link
            className="profile-dropdown__item"
            to="/member/profile"
            role="menuitem"
            onClick={onClose}
          >
            <span>
              <UserRound size={18} />
            </span>
            <strong>View Profile</strong>
            <ChevronRight size={16} />
          </Link>

          <Link
            className="profile-dropdown__item"
            to="/member/profile/edit"
            role="menuitem"
            onClick={onClose}
          >
            <span>
              <Pencil size={18} />
            </span>
            <strong>Edit Profile</strong>
            <ChevronRight size={16} />
          </Link>

          <Link
            className="profile-dropdown__item"
            to="/member/friend-requests"
            role="menuitem"
            onClick={onClose}
          >
            <span>
              <UserRoundCheck size={18} />
            </span>
            <strong>Friend Requests</strong>
            <ChevronRight size={16} />
          </Link>

          <Link
            className="profile-dropdown__item"
            to="/member/account/settings"
            role="menuitem"
            onClick={onClose}
          >
            <span>
              <Settings size={18} />
            </span>
            <strong>Account Settings</strong>
            <ChevronRight size={16} />
          </Link>

          <Link
            className="profile-dropdown__item"
            to="/member/account/security"
            role="menuitem"
            onClick={onClose}
          >
            <span>
              <ShieldCheck size={18} />
            </span>
            <strong>Password &amp; Security</strong>
            <ChevronRight size={16} />
          </Link>
        </div>

        <div className="profile-dropdown__divider" />

        {/* Profile Dropdown Footer (Logout - fixed at bottom) */}
        <div className="profile-dropdown__footer">
          <button
            className="profile-dropdown__item profile-dropdown__item--logout"
            type="button"
            role="menuitem"
            onClick={handleLogout}
            style={{ width: '100%', background: 'none', border: 'none', textAlign: 'left', cursor: 'pointer' }}
          >
            <span>
              <LogOut size={18} />
            </span>
            <strong>Logout</strong>
          </button>
        </div>
      </div>
    </div>
  );
}

export default ProfileDropdown;
