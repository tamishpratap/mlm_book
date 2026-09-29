import { useState } from 'react';
import {
  Users,
  Award,
  Copy,
  Check,
  Share2,
  UserCheck,
  MessageCircle,
  HelpCircle,
  Sparkles,
} from 'lucide-react';

export function ReferralNetworkCard({ referralNetwork }) {
  const [copied, setCopied] = useState(false);

  const userId = referralNetwork?.user_id || '';
  const rank = referralNetwork?.reward_rank || 'Free Member';
  const directCount = Number(referralNetwork?.direct_referrals_count || 0);
  const verifiedCount = Number(referralNetwork?.verified_direct_referrals_count || 0);
  const origin = typeof window !== 'undefined' && window.location?.origin ? window.location.origin : '';
  const referralUrl = `${origin}/member/register?ref=${encodeURIComponent(userId || '')}`;
  const sponsor = referralNetwork?.sponsor;

  const handleCopy = () => {
    if (!referralUrl) return;
    navigator.clipboard.writeText(referralUrl);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const shareText = encodeURIComponent(
    `Join me on MLM Book — the business & social platform to grow your network, run targeted ads, and earn verified rewards!\n\nRegister using my link:\n${referralUrl}`
  );

  return (
    <section className="dash-wallet-section dash-network-box" aria-label="Referral Network and Rank">
      <div className="dash-section-header" style={{ marginBottom: '16px' }}>
        <div className="dash-section-title">
          <div className="dash-section-icon" style={{ backgroundColor: 'rgba(168, 85, 247, 0.15)', color: '#9333ea' }}>
            <Users size={20} />
          </div>
          <div>
            <h2 style={{ color: '#581c87' }}>Referral Network & Rank Status</h2>
            <p style={{ margin: 0, fontSize: '0.825rem', color: '#7e22ce' }}>
              Your direct referrals, current reward rank tier & sponsorship
            </p>
          </div>
        </div>

        <div className="dash-rank-badge" style={{ fontSize: '0.85rem', padding: '5px 14px' }}>
          <Sparkles size={14} />
          <span>{rank}</span>
        </div>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: '14px', marginBottom: '18px' }}>
        {/* Direct Referrals */}
        <div style={{ backgroundColor: '#ffffff', borderRadius: '14px', padding: '14px 18px', border: '1px solid #e9d5ff' }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '6px' }}>
            <span style={{ fontSize: '0.75rem', fontWeight: 700, color: '#6b21a8', textTransform: 'uppercase' }}>
              Direct Signups
            </span>
            <Users size={16} color="#9333ea" />
          </div>
          <div style={{ fontSize: '1.6rem', fontWeight: 800, color: '#3b0764' }}>
            {directCount}
          </div>
          <p style={{ margin: 0, fontSize: '0.75rem', color: '#7e22ce' }}>
            Registered using your referral code
          </p>
        </div>

        {/* Verified Referrals */}
        <div style={{ backgroundColor: '#ffffff', borderRadius: '14px', padding: '14px 18px', border: '1px solid #e9d5ff' }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '6px' }}>
            <span style={{ fontSize: '0.75rem', fontWeight: 700, color: '#15803d', textTransform: 'uppercase' }}>
              Verified Referrals
            </span>
            <UserCheck size={16} color="#16a34a" />
          </div>
          <div style={{ fontSize: '1.6rem', fontWeight: 800, color: '#14532d' }}>
            {verifiedCount}
          </div>
          <p style={{ margin: 0, fontSize: '0.75rem', color: '#166534' }}>
            Completed mobile OTP verification
          </p>
        </div>

        {/* Sponsor / Introducer Info */}
        <div style={{ backgroundColor: '#ffffff', borderRadius: '14px', padding: '14px 18px', border: '1px solid #e9d5ff' }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '6px' }}>
            <span style={{ fontSize: '0.75rem', fontWeight: 700, color: '#6b21a8', textTransform: 'uppercase' }}>
              Introducer / Sponsor
            </span>
            <Award size={16} color="#9333ea" />
          </div>
          {sponsor ? (
            <div>
              <div style={{ fontSize: '1rem', fontWeight: 700, color: '#3b0764' }}>
                {sponsor.name}
              </div>
              <p style={{ margin: 0, fontSize: '0.75rem', color: '#7e22ce' }}>
                ID: {sponsor.user_id}
              </p>
            </div>
          ) : (
            <div>
              <div style={{ fontSize: '0.9rem', fontWeight: 600, color: '#64748b' }}>
                No Sponsor Linked
              </div>
              <p style={{ margin: 0, fontSize: '0.75rem', color: '#94a3b8' }}>
                Direct Platform Member
              </p>
            </div>
          )}
        </div>
      </div>

      {/* Share Box */}
      <div style={{ backgroundColor: '#ffffff', borderRadius: '14px', padding: '16px', border: '1px solid #e9d5ff' }}>
        <label style={{ display: 'block', fontSize: '0.8rem', fontWeight: 700, color: '#4c1d95', marginBottom: '8px' }}>
          Your Direct Referral Link
        </label>
        <div className="dash-referral-input-group">
          <input
            type="text"
            readOnly
            value={referralUrl}
            className="dash-referral-input"
          />
          <button
            type="button"
            onClick={handleCopy}
            className="dash-btn-primary"
            style={{ padding: '6px 14px', fontSize: '0.8rem', borderRadius: '8px', background: '#9333ea' }}
          >
            {copied ? <Check size={14} color="#ffffff" /> : <Copy size={14} />}
            <span>{copied ? 'Copied!' : 'Copy'}</span>
          </button>
          <a
            href={`https://wa.me/?text=${shareText}`}
            target="_blank"
            rel="noopener noreferrer"
            className="dash-btn-primary"
            style={{
              padding: '6px 12px',
              fontSize: '0.8rem',
              borderRadius: '8px',
              backgroundColor: '#25D366',
              textDecoration: 'none',
            }}
            title="Share on WhatsApp"
          >
            <MessageCircle size={14} />
            <span>WhatsApp</span>
          </a>
        </div>
      </div>
    </section>
  );
}

export default ReferralNetworkCard;
