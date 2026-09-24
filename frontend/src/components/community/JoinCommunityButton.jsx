import { useState, useEffect } from 'react';
import { Crown, CheckCircle2, LogOut, Clock, UserPlus } from 'lucide-react';
import communityApi from '../../api/communityApi';
import useAuth from '../../hooks/useAuth';
import AccountVerificationModal from '../verification/AccountVerificationModal';
import { isMemberMobileVerified } from '../../utils/whatsappVerification';

export function JoinCommunityButton({
  community,
  isOwner = false,
  isMember = false,
  isPending = false,
  role = 'member',
  onStateChange,
}) {
  const { user: currentUser } = useAuth();
  const isVerified = isMemberMobileVerified(currentUser);
  const [showVerifyModal, setShowVerifyModal] = useState(false);

  const [localIsMember, setLocalIsMember] = useState(Boolean(isMember));
  const [localIsPending, setLocalIsPending] = useState(Boolean(isPending));
  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    setLocalIsMember(Boolean(isMember));
    setLocalIsPending(Boolean(isPending));
  }, [isMember, isPending]);

  const handleJoin = async (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (!isVerified) {
      setShowVerifyModal(true);
      return;
    }
    if (isLoading || !community?.slug) return;
    setIsLoading(true);
    try {
      const res = await communityApi.join(community.slug);
      if (res && res.success) {
        if (res.status === 'accepted') {
          setLocalIsMember(true);
          setLocalIsPending(false);
          if (onStateChange) onStateChange('member', res.member_count);
        } else {
          setLocalIsMember(false);
          setLocalIsPending(true);
          if (onStateChange) onStateChange('pending', res.member_count);
        }
      }
    } catch (err) {
      const errMsg = err?.response?.data?.message || err?.message || '';
      if (err?.response?.status === 422 && errMsg.toLowerCase().includes('already a member')) {
        // Reconcile client state with backend reality
        setLocalIsMember(true);
        setLocalIsPending(false);
        if (onStateChange) onStateChange('member');
      } else if (err?.response?.status === 422 && errMsg.toLowerCase().includes('already pending')) {
        setLocalIsMember(false);
        setLocalIsPending(true);
        if (onStateChange) onStateChange('pending');
      } else {
        alert(errMsg || 'Failed to join community. Please try again.');
      }
    } finally {
      setIsLoading(false);
    }
  };

  const handleLeave = async (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (isLoading || !community?.slug) return;
    if (!window.confirm(`Are you sure you want to leave "${community.name}"?`)) return;

    setIsLoading(true);
    try {
      const res = await communityApi.leave(community.slug);
      if (res && res.success) {
        setLocalIsMember(false);
        setLocalIsPending(false);
        if (onStateChange) onStateChange('none', res.member_count);
      }
    } catch (err) {
      const errMsg = err?.response?.data?.message || err?.message || '';
      if (err?.response?.status === 422 && errMsg.toLowerCase().includes('not an active member')) {
        // Reconcile client state with backend reality
        setLocalIsMember(false);
        setLocalIsPending(false);
        if (onStateChange) onStateChange('none');
      } else {
        alert(errMsg || 'Failed to leave community. Please try again.');
      }
    } finally {
      setIsLoading(false);
    }
  };

  if (isOwner) {
    return (
      <span
        className="community-badge community-badge--category community-card__badge-owner"
        style={{
          background: 'var(--color-primary-soft, rgba(79, 125, 243, 0.12))',
          color: 'var(--color-primary, #4f7df3)',
          padding: '6px 14px',
          fontSize: '12.5px',
          fontWeight: 600,
        }}
      >
        <Crown size={14} aria-hidden="true" />
        <span>Owner</span>
      </span>
    );
  }

  if (localIsMember) {
    return (
      <div className="community-joined-actions">
        <span
          className="community-badge community-badge--category community-card__badge-joined"
          style={{
            background: 'rgba(34, 197, 94, 0.12)',
            color: '#16a34a',
            padding: '6px 12px',
            fontSize: '12.5px',
            fontWeight: 600,
          }}
        >
          <CheckCircle2 size={14} aria-hidden="true" />
          <span>
            Joined {role && role !== 'member' && role !== 'owner' ? `(${role.charAt(0).toUpperCase() + role.slice(1)})` : ''}
          </span>
        </span>
        <button
          type="button"
          className="member-button member-button--secondary community-card__btn-leave"
          style={{ padding: '6px 12px', fontSize: '12px', height: '34px', minHeight: '34px' }}
          onClick={handleLeave}
          disabled={isLoading}
        >
          <LogOut size={13} aria-hidden="true" />
          <span>{isLoading ? 'Leaving...' : 'Leave Community'}</span>
        </button>
      </div>
    );
  }

  if (localIsPending) {
    return (
      <button
        type="button"
        className="member-button member-button--secondary community-card__btn-pending"
        style={{ cursor: 'default', opacity: 0.85, height: '36px', minHeight: '36px', fontSize: '12.5px' }}
        disabled
      >
        <Clock size={14} aria-hidden="true" />
        <span>Requested</span>
      </button>
    );
  }

  return (
    <>
      <button
        type="button"
        className="member-button member-button--primary community-card__btn-join"
        style={{ height: '36px', minHeight: '36px', fontSize: '12.5px', padding: '0 14px' }}
        onClick={handleJoin}
        disabled={isLoading}
      >
        <UserPlus size={14} aria-hidden="true" />
        <span>
          {isLoading
            ? 'Joining...'
            : community?.visibility === 'public'
              ? 'Join Community'
              : 'Request to Join'}
        </span>
      </button>

      {showVerifyModal && (
        <AccountVerificationModal
          isOpen={showVerifyModal}
          promptMessage="Please verify your mobile number through WhatsApp before joining a community."
          onClose={() => setShowVerifyModal(false)}
          onVerified={() => {
            setShowVerifyModal(false);
          }}
        />
      )}
    </>
  );
}

export default JoinCommunityButton;
