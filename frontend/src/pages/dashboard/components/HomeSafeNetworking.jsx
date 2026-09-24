import { Link } from 'react-router-dom';
import { ShieldCheck, AtSign, UserCheck, Lock, Bell, ArrowRight } from 'lucide-react';

export function HomeSafeNetworking({ memberHandle }) {
  const displayHandle = memberHandle ? (memberHandle.startsWith('@') ? memberHandle : `@${memberHandle}`) : '@member';

  return (
    <section className="card home-section home-safe-networking-section" aria-labelledby="safe-networking-heading">
      <div className="home-section__header" style={{ marginBottom: '24px' }}>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '12px' }}>
          <div>
            <div className="home-hub-badge home-hub-badge--green">
              <ShieldCheck size={13} aria-hidden="true" />
              <span>Trust, Safety & Governance</span>
            </div>
            <h2 className="home-section__title" id="safe-networking-heading" style={{ fontSize: '1.35rem' }}>
              <Lock size={22} aria-hidden="true" />
              <span>Safe Social Networking & Privacy Architecture</span>
            </h2>
            <p className="home-section__desc">
              Your security, privacy, and connection management are safeguarded at every level across the MLM Book platform.
            </p>
          </div>

          <Link to="/member/account/security" className="member-button member-button--secondary" style={{ fontSize: '13px' }}>
            <span>Security Settings</span>
            <ArrowRight size={14} aria-hidden="true" />
          </Link>
        </div>
      </div>

      <div className="home-grid home-grid--2">
        <div className="home-security-box-v2">
          <ul className="home-security-list">
            <li className="home-security-item">
              <div className="home-security-icon-wrap home-security-icon-wrap--blue">
                <AtSign size={20} aria-hidden="true" />
              </div>
              <div className="home-security-content">
                <div className="home-security-top">
                  <strong>Unique Verified Handles</strong>
                  <span className="home-security-tag">Verified</span>
                </div>
                <p>
                  Every member receives a normalized, uniquely verified handle (e.g. <code>{displayHandle}</code>) preventing identity spoofing and enabling precise discovery.
                </p>
              </div>
            </li>

            <li className="home-security-item">
              <div className="home-security-icon-wrap home-security-icon-wrap--green">
                <UserCheck size={20} aria-hidden="true" />
              </div>
              <div className="home-security-content">
                <div className="home-security-top">
                  <strong>Connection Authorization</strong>
                  <span className="home-security-tag home-security-tag--green">Zero Spam</span>
                </div>
                <p>
                  Full control over your network. Manage incoming requests, inspect mutual connection counts, block abusive accounts, and keep interactions strictly approved.
                </p>
              </div>
            </li>
          </ul>
        </div>

        <div className="home-security-box-v2">
          <ul className="home-security-list">
            <li className="home-security-item">
              <div className="home-security-icon-wrap home-security-icon-wrap--purple">
                <Lock size={20} aria-hidden="true" />
              </div>
              <div className="home-security-content">
                <div className="home-security-top">
                  <strong>Encrypted Data & Privacy</strong>
                  <span className="home-security-tag home-security-tag--purple">Encrypted</span>
                </div>
                <p>
                  Fine-grained visibility toggles for posts (Public, Friends, Only Me), email concealment, 2FA security options, and instant privacy controls.
                </p>
              </div>
            </li>

            <li className="home-security-item">
              <div className="home-security-icon-wrap home-security-icon-wrap--amber">
                <Bell size={20} aria-hidden="true" />
              </div>
              <div className="home-security-content">
                <div className="home-security-top">
                  <strong>Real-Time Activity Alerts</strong>
                  <span className="home-security-tag home-security-tag--amber">Instant</span>
                </div>
                <p>
                  Instant header notifications for connection requests, direct messages, community invites, and mentions keep you in control without notification fatigue.
                </p>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </section>
  );
}

export default HomeSafeNetworking;
