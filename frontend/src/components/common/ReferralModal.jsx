import { useState } from 'react';
import {
  X,
  Share2,
  Copy,
  Check,
  ShieldCheck,
  AlertTriangle,
  Gift,
  ExternalLink,
  MessageCircle,
  Send,
} from 'lucide-react';
import useAuth from '../../hooks/useAuth';
import { ModalPortal } from './ModalPortal';

export function ReferralModal({ isOpen, onClose, onOpenVerification }) {
  const { user } = useAuth();
  const [copiedId, setCopiedId] = useState(false);
  const [copiedLink, setCopiedLink] = useState(false);
  const [feedback, setFeedback] = useState(null);

  // Close on Escape key
  if (!isOpen || !user) return null;

  const isVerified = Boolean(user.is_verified || user.mobile_verified_at);
  const userId = user.user_id || '';

  // Construct environment-aware canonical referral URL
  const origin = typeof window !== 'undefined' ? window.location.origin : '';
  const referralUrl = `${origin}/member/register?ref=${userId}`;
  const shareTitle = `Join me on MLM Book! Connect, network, and grow together. Register using my referral link:`;
  const encodedUrl = encodeURIComponent(referralUrl);
  const encodedTitle = encodeURIComponent(shareTitle);

  const handleCopyId = () => {
    if (!isVerified) return;
    navigator.clipboard.writeText(userId).then(() => {
      setCopiedId(true);
      setFeedback('Referral ID copied to clipboard!');
      setTimeout(() => {
        setCopiedId(false);
        setFeedback(null);
      }, 2500);
    });
  };

  const handleCopyLink = () => {
    if (!isVerified) return;
    navigator.clipboard.writeText(referralUrl).then(() => {
      setCopiedLink(true);
      setFeedback('Referral link copied to clipboard!');
      setTimeout(() => {
        setCopiedLink(false);
        setFeedback(null);
      }, 2500);
    });
  };

  const handleNativeShare = async () => {
    if (!isVerified) return;
    if (typeof navigator !== 'undefined' && navigator.share) {
      try {
        await navigator.share({
          title: 'Join MLM Book',
          text: shareTitle,
          url: referralUrl,
        });
      } catch {
        // User dismissed share dialog
      }
    } else {
      handleCopyLink();
    }
  };

  const handleStartVerification = () => {
    onClose?.();
    if (onOpenVerification) {
      onOpenVerification();
    }
  };

  return (
    <ModalPortal isOpen={isOpen} onClose={onClose}>
      <div
        className="story-modal__panel card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="referral-modal-title"
        style={{
          maxWidth: '540px',
          width: '100%',
          maxHeight: 'min(90vh, 760px)',
          display: 'flex',
          flexDirection: 'column',
          borderRadius: '20px',
          overflow: 'hidden',
          backgroundColor: '#ffffff',
          boxShadow: '0 25px 50px -12px rgba(15, 23, 42, 0.35)',
          border: '1px solid rgba(226, 232, 240, 0.8)',
        }}
      >
        {/* Header Banner */}
        <header
          style={{
            padding: '20px 24px',
            borderBottom: '1px solid #f1f5f9',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            flexShrink: 0,
            background: isVerified
              ? 'linear-gradient(135deg, rgba(23, 107, 255, 0.05) 0%, rgba(113, 70, 237, 0.08) 100%)'
              : 'linear-gradient(135deg, rgba(245, 158, 11, 0.06) 0%, rgba(217, 119, 6, 0.09) 100%)',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
            <span
              style={{
                width: '42px',
                height: '42px',
                borderRadius: '12px',
                background: isVerified
                  ? 'linear-gradient(135deg, #176bff, #7146ed)'
                  : 'linear-gradient(135deg, #f59e0b, #d97706)',
                color: '#ffffff',
                display: 'grid',
                placeItems: 'center',
                flexShrink: 0,
                boxShadow: isVerified
                  ? '0 4px 12px rgba(23, 107, 255, 0.3)'
                  : '0 4px 12px rgba(245, 158, 11, 0.3)',
              }}
            >
              {isVerified ? <Gift size={22} /> : <AlertTriangle size={22} />}
            </span>
            <div>
              <span
                style={{
                  fontSize: '0.75rem',
                  fontWeight: 700,
                  letterSpacing: '0.05em',
                  textTransform: 'uppercase',
                  color: isVerified ? '#176bff' : '#b45309',
                }}
              >
                MLM Referral Program
              </span>
              <h2
                id="referral-modal-title"
                style={{
                  margin: 0,
                  fontSize: '1.2rem',
                  fontWeight: 800,
                  color: '#0f172a',
                }}
              >
                {isVerified ? 'Your Referral Details' : 'Referral Link Inactive'}
              </h2>
            </div>
          </div>

          <button
            type="button"
            aria-label="Close"
            onClick={onClose}
            style={{
              width: '34px',
              height: '34px',
              borderRadius: '50%',
              background: '#f1f5f9',
              border: 'none',
              display: 'grid',
              placeItems: 'center',
              cursor: 'pointer',
              color: '#64748b',
              transition: 'background 0.15s ease',
            }}
          >
            <X size={18} />
          </button>
        </header>

        {/* Modal Body */}
        <div
          style={{
            padding: '22px 24px 28px 24px',
            overflowY: 'auto',
            overflowX: 'hidden',
            flex: 1,
            minHeight: 0,
            WebkitOverflowScrolling: 'touch',
          }}
        >
          {/* Feedback Alert */}
          {feedback && (
            <div
              style={{
                marginBottom: '16px',
                padding: '10px 16px',
                background: 'rgba(32, 200, 117, 0.12)',
                color: '#16a34a',
                fontSize: '0.85rem',
                fontWeight: 700,
                borderRadius: '10px',
                display: 'flex',
                alignItems: 'center',
                gap: '8px',
              }}
            >
              <Check size={16} />
              <span>{feedback}</span>
            </div>
          )}

          {/* VERIFIED MEMBER CONTENT */}
          {isVerified ? (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '20px' }}>
              {/* Status Badge */}
              <div
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  padding: '10px 14px',
                  borderRadius: '12px',
                  background: 'rgba(16, 185, 129, 0.08)',
                  border: '1px solid rgba(16, 185, 129, 0.2)',
                }}
              >
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <ShieldCheck size={18} color="#10b981" />
                  <span style={{ fontSize: '0.85rem', fontWeight: 700, color: '#065f46' }}>
                    Mobile Verified Member
                  </span>
                </div>
                <span
                  style={{
                    fontSize: '0.75rem',
                    fontWeight: 700,
                    color: '#047857',
                    background: '#d1fae5',
                    padding: '3px 10px',
                    borderRadius: '20px',
                  }}
                >
                  Eligible to Refer
                </span>
              </div>

              {/* 1. YOUR REFERRAL ID */}
              <div>
                <label
                  style={{
                    display: 'block',
                    fontSize: '0.78rem',
                    fontWeight: 700,
                    color: '#64748b',
                    textTransform: 'uppercase',
                    letterSpacing: '0.04em',
                    marginBottom: '6px',
                  }}
                >
                  Your Referral ID
                </label>
                <div
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '10px',
                    background: '#f8fafc',
                    border: '1px solid #cbd5e1',
                    borderRadius: '12px',
                    padding: '8px 12px',
                    flexWrap: 'wrap',
                  }}
                >
                  <code
                    style={{
                      flex: '1 1 180px',
                      minWidth: 0,
                      fontFamily: 'monospace',
                      fontSize: '1rem',
                      fontWeight: 700,
                      color: '#1e293b',
                      wordBreak: 'break-all',
                    }}
                  >
                    @{userId}
                  </code>
                  <button
                    type="button"
                    onClick={handleCopyId}
                    className="member-button"
                    style={{
                      padding: '7px 14px',
                      fontSize: '0.8rem',
                      fontWeight: 700,
                      borderRadius: '8px',
                      background: copiedId ? '#20c875' : '#4f7df3',
                      color: '#ffffff',
                      border: 'none',
                      cursor: 'pointer',
                      display: 'inline-flex',
                      alignItems: 'center',
                      gap: '6px',
                      flexShrink: 0,
                    }}
                  >
                    {copiedId ? <Check size={14} /> : <Copy size={14} />}
                    <span>{copiedId ? 'Copied' : 'Copy ID'}</span>
                  </button>
                </div>
              </div>

              {/* 2. YOUR REFERRAL LINK */}
              <div>
                <label
                  style={{
                    display: 'block',
                    fontSize: '0.78rem',
                    fontWeight: 700,
                    color: '#64748b',
                    textTransform: 'uppercase',
                    letterSpacing: '0.04em',
                    marginBottom: '6px',
                  }}
                >
                  Your Referral Link
                </label>
                <div
                  style={{
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '8px',
                  }}
                >
                  <div
                    style={{
                      display: 'flex',
                      alignItems: 'center',
                      gap: '8px',
                      background: '#f8fafc',
                      border: '1px solid #cbd5e1',
                      borderRadius: '12px',
                      padding: '8px 12px',
                      flexWrap: 'wrap',
                    }}
                  >
                    <input
                      type="text"
                      readOnly
                      value={referralUrl}
                      onClick={(e) => e.target.select()}
                      style={{
                        flex: '1 1 180px',
                        minWidth: 0,
                        background: 'transparent',
                        border: 'none',
                        outline: 'none',
                        fontFamily: 'monospace',
                        fontSize: '0.825rem',
                        color: '#0f172a',
                      }}
                    />
                    <button
                      type="button"
                      onClick={handleCopyLink}
                      className="member-button"
                      style={{
                        padding: '7px 14px',
                        fontSize: '0.8rem',
                        fontWeight: 700,
                        borderRadius: '8px',
                        background: copiedLink ? '#20c875' : '#4f7df3',
                        color: '#ffffff',
                        border: 'none',
                        cursor: 'pointer',
                        display: 'inline-flex',
                        alignItems: 'center',
                        gap: '6px',
                        flexShrink: 0,
                      }}
                    >
                      {copiedLink ? <Check size={14} /> : <Copy size={14} />}
                      <span>{copiedLink ? 'Copied' : 'Copy Link'}</span>
                    </button>
                  </div>
                </div>
              </div>

              {/* 3. SHARE TO EXTERNAL NETWORKS */}
              <div>
                <label
                  style={{
                    display: 'block',
                    fontSize: '0.78rem',
                    fontWeight: 700,
                    color: '#64748b',
                    textTransform: 'uppercase',
                    letterSpacing: '0.04em',
                    marginBottom: '8px',
                  }}
                >
                  Share Link Via
                </label>
                <div
                  style={{
                    display: 'grid',
                    gridTemplateColumns: 'repeat(auto-fit, minmax(110px, 1fr))',
                    gap: '8px',
                  }}
                >
                  {/* WhatsApp */}
                  <a
                    href={`https://api.whatsapp.com/send?text=${encodedTitle}%20${encodedUrl}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    style={{
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      gap: '6px',
                      padding: '9px 10px',
                      borderRadius: '10px',
                      border: '1px solid rgba(37, 211, 102, 0.3)',
                      background: 'rgba(37, 211, 102, 0.06)',
                      color: '#15803d',
                      textDecoration: 'none',
                      fontSize: '0.8rem',
                      fontWeight: 700,
                    }}
                  >
                    <MessageCircle size={16} color="#25d366" />
                    <span>WhatsApp</span>
                  </a>

                  {/* Telegram */}
                  <a
                    href={`https://t.me/share/url?url=${encodedUrl}&text=${encodedTitle}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    style={{
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      gap: '6px',
                      padding: '9px 10px',
                      borderRadius: '10px',
                      border: '1px solid rgba(34, 158, 217, 0.3)',
                      background: 'rgba(34, 158, 217, 0.06)',
                      color: '#0284c7',
                      textDecoration: 'none',
                      fontSize: '0.8rem',
                      fontWeight: 700,
                    }}
                  >
                    <Send size={16} color="#229ed9" />
                    <span>Telegram</span>
                  </a>

                  {/* Facebook */}
                  <a
                    href={`https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    style={{
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      gap: '6px',
                      padding: '9px 10px',
                      borderRadius: '10px',
                      border: '1px solid rgba(24, 119, 242, 0.3)',
                      background: 'rgba(24, 119, 242, 0.06)',
                      color: '#1877f2',
                      textDecoration: 'none',
                      fontSize: '0.8rem',
                      fontWeight: 700,
                    }}
                  >
                    <ExternalLink size={16} color="#1877f2" />
                    <span>Facebook</span>
                  </a>

                  {/* Native Share */}
                  {typeof navigator !== 'undefined' && typeof navigator.share === 'function' && (
                    <button
                      type="button"
                      onClick={handleNativeShare}
                      style={{
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        gap: '6px',
                        padding: '9px 10px',
                        borderRadius: '10px',
                        border: '1px solid rgba(79, 125, 243, 0.3)',
                        background: 'rgba(79, 125, 243, 0.06)',
                        color: '#4f7df3',
                        cursor: 'pointer',
                        fontSize: '0.8rem',
                        fontWeight: 700,
                      }}
                    >
                      <Share2 size={16} color="#4f7df3" />
                      <span>Share</span>
                    </button>
                  )}
                </div>
              </div>

              {/* Informational Policy Note */}
              <div
                style={{
                  background: '#f8fafc',
                  border: '1px solid #e2e8f0',
                  borderRadius: '12px',
                  padding: '12px 14px',
                  fontSize: '0.78rem',
                  color: '#64748b',
                  lineHeight: '1.45',
                  wordBreak: 'break-word',
                  boxSizing: 'border-box',
                }}
              >
                <strong style={{ color: '#334155', display: 'block', marginBottom: '2px' }}>
                  How referral tracking works:
                </strong>
                When someone creates an account using your link, you are saved as their introducer. Once they complete mobile verification, your direct referral count increments automatically.
              </div>
            </div>
          ) : (
            /* UNVERIFIED MEMBER WARNING & CALL-TO-ACTION */
            <div style={{ display: 'flex', flexDirection: 'column', gap: '18px' }}>
              <div
                style={{
                  padding: '16px',
                  borderRadius: '14px',
                  background: 'rgba(245, 158, 11, 0.08)',
                  border: '1px solid rgba(245, 158, 11, 0.25)',
                }}
              >
                <div style={{ display: 'flex', gap: '12px', alignItems: 'flex-start' }}>
                  <AlertTriangle size={22} color="#d97706" style={{ flexShrink: 0, marginTop: '2px' }} />
                  <div>
                    <h3
                      style={{
                        margin: '0 0 6px 0',
                        fontSize: '0.95rem',
                        fontWeight: 800,
                        color: '#92400e',
                      }}
                    >
                      Mobile Verification Required
                    </h3>
                    <p
                      style={{
                        margin: 0,
                        fontSize: '0.825rem',
                        color: '#78350f',
                        lineHeight: '1.5',
                      }}
                    >
                      In accordance with platform security and MLM policy, only members with a verified mobile number can refer new members. Your referral link is currently inactive.
                    </p>
                  </div>
                </div>
              </div>

              {/* Disabled Preview */}
              <div style={{ opacity: 0.5, pointerEvents: 'none' }}>
                <label
                  style={{
                    display: 'block',
                    fontSize: '0.78rem',
                    fontWeight: 700,
                    color: '#64748b',
                    textTransform: 'uppercase',
                    marginBottom: '6px',
                  }}
                >
                  Referral Link (Locked)
                </label>
                <div
                  style={{
                    background: '#f1f5f9',
                    border: '1px dashed #cbd5e1',
                    borderRadius: '10px',
                    padding: '10px 14px',
                    fontSize: '0.85rem',
                    color: '#94a3b8',
                    fontFamily: 'monospace',
                  }}
                >
                  https://.../member/register?ref={userId}
                </div>
              </div>

              {/* Action Button */}
              <button
                type="button"
                onClick={handleStartVerification}
                className="member-button member-button--primary"
                style={{
                  width: '100%',
                  padding: '12px 18px',
                  fontSize: '0.9rem',
                  fontWeight: 800,
                  borderRadius: '12px',
                  background: 'linear-gradient(135deg, #176bff, #7146ed)',
                  color: '#ffffff',
                  border: 'none',
                  cursor: 'pointer',
                  display: 'inline-flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  gap: '8px',
                  boxShadow: '0 4px 14px rgba(23, 107, 255, 0.3)',
                }}
              >
                <ShieldCheck size={18} />
                <span>Verify Mobile Number to Activate</span>
              </button>
            </div>
          )}
        </div>
      </div>
    </ModalPortal>
  );
}

export default ReferralModal;
