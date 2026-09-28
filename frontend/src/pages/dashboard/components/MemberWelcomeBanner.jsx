import { useState } from 'react';
import { Link } from 'react-router-dom';
import {
  CheckCircle2,
  Copy,
  Check,
  PlusCircle,
  Megaphone,
  Wallet,
  Building2,
  Share2,
  Award,
} from 'lucide-react';
import { getAvatarUrl } from '../../../utils/assetHelper';

export function MemberWelcomeBanner({ member, referralNetwork }) {
  const [copiedCode, setCopiedCode] = useState(false);
  const [copiedLink, setCopiedLink] = useState(false);

  const avatarUrl = member?.avatar_url || (member?.profile_photo ? getAvatarUrl(member.profile_photo) : null);
  const rank = referralNetwork?.reward_rank || member?.reward_rank || 'Free Member';
  const origin = typeof window !== 'undefined' && window.location?.origin ? window.location.origin : '';
  const referralUrl = `${origin}/member/register?ref=${encodeURIComponent(member?.user_id || '')}`;

  const handleCopyCode = () => {
    if (!member?.user_id) return;
    navigator.clipboard.writeText(member.user_id);
    setCopiedCode(true);
    setTimeout(() => setCopiedCode(false), 2000);
  };

  const handleCopyLink = () => {
    if (!referralUrl) return;
    navigator.clipboard.writeText(referralUrl);
    setCopiedLink(true);
    setTimeout(() => setCopiedLink(false), 2000);
  };

  return (
    <section className="dash-welcome-banner" aria-label="Member Welcome Header">
      <div className="dash-welcome-info">
        <div className="dash-avatar-wrapper">
          <img
            src={avatarUrl || '/member_assets/images/dashboard/image/profile.png'}
            alt={member?.name || 'Member'}
            className="dash-avatar-img"
            onError={(e) => {
              e.currentTarget.src = '/member_assets/images/dashboard/image/profile.png';
            }}
          />
          {member?.is_verified && (
            <div className="dash-verified-badge" title="Mobile Verified Member">
              <CheckCircle2 size={16} />
            </div>
          )}
        </div>

        <div className="dash-welcome-text">
          <h1>
            <span>Welcome, {member?.name || 'Member'}!</span>
            <span className="dash-rank-badge">
              <Award size={13} />
              <span>{rank}</span>
            </span>
          </h1>

          <div className="dash-meta-tags">
            {member?.user_id && (
              <div className="dash-meta-item">
                <span>ID: <strong>{member.user_id}</strong></span>
                <button
                  type="button"
                  onClick={handleCopyCode}
                  className="dash-copy-btn"
                  title="Copy User ID"
                  aria-label="Copy User ID"
                >
                  {copiedCode ? <Check size={12} color="#10b981" /> : <Copy size={12} />}
                  <span>{copiedCode ? 'Copied' : 'Copy'}</span>
                </button>
              </div>
            )}

            <div className="dash-meta-item">
              <button
                type="button"
                onClick={handleCopyLink}
                className="dash-copy-btn"
                style={{ background: 'rgba(59, 130, 246, 0.25)', borderColor: '#3b82f6' }}
                title="Copy Referral Link"
              >
                <Share2 size={12} />
                <span>{copiedLink ? 'Link Copied!' : 'Referral Link'}</span>
              </button>
            </div>

            {member?.created_at && (
              <div className="dash-meta-item" style={{ opacity: 0.85 }}>
                <span>Joined {new Date(member.created_at).toLocaleDateString(undefined, { month: 'short', year: 'numeric' })}</span>
              </div>
            )}
          </div>
        </div>
      </div>

      <div className="dash-header-actions">
        <Link to="/member/deposit" className="dash-btn-primary">
          <Wallet size={16} />
          <span>+ Add Funds</span>
        </Link>
        <Link to="/member/business-pages/create" className="dash-btn-secondary">
          <Building2 size={16} />
          <span>+ Business Page</span>
        </Link>
        <Link to="/member/business-pages" className="dash-btn-secondary">
          <Megaphone size={16} />
          <span>Run Ad</span>
        </Link>
      </div>
    </section>
  );
}

export default MemberWelcomeBanner;
