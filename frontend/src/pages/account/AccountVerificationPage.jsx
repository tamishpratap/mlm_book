import { Link } from 'react-router-dom';
import {
  ShieldCheck,
  Sparkles,
  Award,
  Users,
  Shield,
  ArrowLeft,
} from 'lucide-react';
import useAuth from '../../hooks/useAuth';
import VerifiedBadge from '../../components/common/VerifiedBadge';
import MobileVerificationCard from '../../components/verification/MobileVerificationCard';

export function AccountVerificationPage() {
  const { user } = useAuth();

  return (
    <div className="account-verification-page">
      {/* Top Header Row */}
      <div className="account-verification-header">
        <div className="account-verification-header__content">
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '4px' }}>
            <Link to="/member/profile" style={{ display: 'inline-flex', alignItems: 'center', gap: '4px', fontSize: '13px', color: '#64748b', textDecoration: 'none' }}>
              <ArrowLeft size={14} /> My Profile
            </Link>
          </div>
          <h1 className="account-verification-title">
            <span>Account Verification</span>
            <VerifiedBadge member={user} size={22} showText={true} />
          </h1>
        </div>

        <div className="account-verification-header__actions">
          <Link to="/member/account/security" className="member-button member-button--secondary" style={{ fontSize: '12.5px' }}>
            <Shield size={14} />
            <span>Security Settings</span>
          </Link>
        </div>
      </div>

      {/* Grid: Main Verification Card + Benefits Column */}
      <div className="account-verification-grid">
        {/* Left Column: Interactive Verification Box */}
        <div className="account-verification-grid__main">
          <MobileVerificationCard />
        </div>

        {/* Right Column: Why Get Verified Benefits */}
        <div className="account-verification-grid__benefits">
          <div className="card verification-card account-verification-benefits-card">
            <h3 style={{ fontSize: '15px', fontWeight: 800, margin: '0 0 16px 0', display: 'flex', alignItems: 'center', gap: '8px', color: '#0f172a' }}>
              <Sparkles size={18} color="#059669" />
              <span>Why Get Verified?</span>
            </h3>

            <div style={{ display: 'flex', flexDirection: 'column', gap: '14px' }}>
              <div style={{ display: 'flex', alignItems: 'flex-start', gap: '12px' }}>
                <div style={{ width: '36px', height: '36px', borderRadius: '10px', background: '#f0fdf4', color: '#16a34a', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                  <Award size={18} />
                </div>
                <div style={{ minWidth: 0, flex: 1 }}>
                  <strong style={{ fontSize: '13.5px', color: '#0f172a', display: 'block' }}>Official Green Tick</strong>
                  <span style={{ fontSize: '12px', color: '#64748b', wordBreak: 'break-word' }}>Stand out on posts, comments, profile, and community directory.</span>
                </div>
              </div>

              <div style={{ display: 'flex', alignItems: 'flex-start', gap: '12px' }}>
                <div style={{ width: '36px', height: '36px', borderRadius: '10px', background: '#eff6ff', color: '#2563eb', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                  <Users size={18} />
                </div>
                <div style={{ minWidth: 0, flex: 1 }}>
                  <strong style={{ fontSize: '13.5px', color: '#0f172a', display: 'block' }}>Higher Network Trust</strong>
                  <span style={{ fontSize: '12px', color: '#64748b', wordBreak: 'break-word' }}>Verified accounts receive 3x more connection requests and inquiries.</span>
                </div>
              </div>

              <div style={{ display: 'flex', alignItems: 'flex-start', gap: '12px' }}>
                <div style={{ width: '36px', height: '36px', borderRadius: '10px', background: '#fef3c7', color: '#d97706', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                  <ShieldCheck size={18} />
                </div>
                <div style={{ minWidth: 0, flex: 1 }}>
                  <strong style={{ fontSize: '13.5px', color: '#0f172a', display: 'block' }}>Anti-Spam Protection</strong>
                  <span style={{ fontSize: '12px', color: '#64748b', wordBreak: 'break-word' }}>Assures other members that you are a genuine network professional.</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export default AccountVerificationPage;
