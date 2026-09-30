import { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import {
  Shield,
  Lock,
  Eye,
  FileText,
  UserCheck,
  Share2,
  Database,
  Bell,
  Wallet,
  CheckCircle2,
  Printer,
  ChevronRight,
  ArrowLeft,
  KeyRound,
  Users,
} from 'lucide-react';
import useBranding from '../../hooks/useBranding';
import '../../styles/legal-pages.css';

export function PrivacyPolicyPage() {
  const { logoUrl, siteName } = useBranding();
  const navigate = useNavigate();
  const [activeSection, setActiveSection] = useState('intro');

  useEffect(() => {
    window.scrollTo(0, 0);
    document.title = `Privacy Policy | ${siteName || 'MLM Book'}`;
  }, [siteName]);

  const handlePrint = () => {
    window.print();
  };

  const sections = [
    { id: 'intro', num: '1', title: 'Introduction & Scope', icon: Shield },
    { id: 'data-collection', num: '2', title: 'Information We Collect', icon: Database },
    { id: 'data-usage', num: '3', title: 'How We Use Information', icon: Eye },
    { id: 'introducer-system', num: '4', title: 'Introducers & Social Graph', icon: Users },
    { id: 'privacy-controls', num: '5', title: 'Granular Privacy & Visibility', icon: Lock },
    { id: 'business-ads', num: '6', title: 'Business Pages & Ad Rewards', icon: Wallet },
    { id: 'data-sharing', num: '7', title: 'Data Sharing & Disclosure', icon: Share2 },
    { id: 'security-retention', num: '8', title: 'Security & Data Retention', icon: KeyRound },
    { id: 'user-rights', num: '9', title: 'Your Rights & Choices', icon: UserCheck },
    { id: 'contact-updates', num: '10', title: 'Policy Updates & Contact', icon: Bell },
  ];

  return (
    <div className="legal-page-root">
      {/* Top Header */}
      <header className="legal-header">
        <div className="legal-header-inner">
          <Link to="/" className="legal-header-brand" aria-label="Go to home">
            <img src={logoUrl} alt={siteName} className="legal-header-logo" />
            <div className="legal-header-title-wrap">
              <span className="legal-header-title">{siteName}</span>
              <span className="legal-header-badge">Privacy &amp; Trust Center</span>
            </div>
          </Link>

          <div className="legal-header-actions">
            <button
              onClick={() => navigate(-1)}
              className="legal-nav-pill legal-nav-pill--ghost"
              type="button"
            >
              <ArrowLeft size={16} />
              <span>Back</span>
            </button>

            <Link to="/terms-and-conditions" className="legal-nav-pill legal-nav-pill--switch">
              <FileText size={16} />
              <span>Terms of Service</span>
            </Link>

            <button
              onClick={handlePrint}
              className="legal-nav-pill legal-nav-pill--ghost"
              type="button"
              title="Print document"
            >
              <Printer size={16} />
              <span>Print</span>
            </button>

            <Link to="/member/register" className="legal-nav-pill legal-nav-pill--primary">
              <span>Create Account</span>
            </Link>
          </div>
        </div>
      </header>

      {/* Hero Banner */}
      <section className="legal-hero">
        <div className="legal-hero-glow" />
        <div className="legal-hero-container">
          <div className="legal-hero-tag">
            <Shield size={14} />
            <span>Official Legal Policy</span>
          </div>
          <h1 className="legal-hero-title">MLM Book Privacy Policy</h1>
          <p className="legal-hero-desc">
            We are committed to safeguarding your personal data, transparent networking,
            and providing granular privacy controls across our connected social ecosystem.
          </p>

          <div className="legal-hero-meta">
            <div className="legal-hero-meta-item">
              <CheckCircle2 size={15} color="#10b981" />
              <span>Version: 2.4 (Active)</span>
            </div>
            <div className="legal-hero-meta-item">
              <span>Last Updated: September 2026</span>
            </div>
            <div className="legal-hero-meta-item">
              <span>Applicable Globally to All Registered Members</span>
            </div>
          </div>

          <div className="legal-tabs-bar">
            <Link to="/privacy-policy" className="legal-tab-btn is-active">
              <Shield size={16} />
              <span>Privacy Policy</span>
            </Link>
            <Link to="/terms-and-conditions" className="legal-tab-btn">
              <FileText size={16} />
              <span>Terms &amp; Conditions</span>
            </Link>
          </div>
        </div>
      </section>

      {/* Main Grid Layout */}
      <main className="legal-layout">
        {/* Sticky Sidebar Navigation */}
        <aside className="legal-sidebar">
          <div className="legal-sidebar-sticky">
            <h3 className="legal-sidebar-title">
              <Shield size={14} />
              <span>Policy Navigation</span>
            </h3>

            <ul className="legal-toc-list">
              {sections.map((sec) => (
                <li key={sec.id}>
                  <a
                    href={`#${sec.id}`}
                    className={`legal-toc-link ${activeSection === sec.id ? 'is-active' : ''}`}
                    onClick={() => setActiveSection(sec.id)}
                  >
                    <span className="legal-toc-number">{sec.num}.</span>
                    <span>{sec.title}</span>
                  </a>
                </li>
              ))}
            </ul>

            <div className="legal-sidebar-divider" />

            <div className="legal-sidebar-help">
              <strong>Need Clarification?</strong>
              <p style={{ margin: '4px 0 8px', fontSize: '12px' }}>
                Our compliance team is ready to assist you regarding your privacy settings and personal data.
              </p>
              <Link
                to="/member/feedback-suggestions"
                style={{ color: 'var(--legal-primary)', fontWeight: 600, textDecoration: 'none', display: 'inline-flex', alignItems: 'center', gap: '4px' }}
              >
                Contact Privacy Desk <ChevronRight size={13} />
              </Link>
            </div>
          </div>
        </aside>

        {/* Content Area */}
        <article className="legal-content">
          {/* Important Highlight Box */}
          <div className="legal-callout">
            <Shield className="legal-callout-icon" size={24} />
            <div className="legal-callout-text">
              <h4>Our Core Privacy Commitment</h4>
              <p>
                At MLM Book, we believe you should always own and command your digital footprint.
                We never sell your sensitive personal data to third-party data brokers.
                Your communication, contact coordinates, wallet records, and network connections are protected
                by modern encryption and customizable privacy toggles.
              </p>
            </div>
          </div>

          {/* Section 1: Introduction & Scope */}
          <section id="intro" className="legal-section-card">
            <div className="legal-section-header">
              <div className="legal-section-icon-badge">
                <Shield size={20} />
              </div>
              <div className="legal-section-title-wrap">
                <span className="legal-section-tag">Section 1</span>
                <h2 className="legal-section-title">Introduction &amp; Scope</h2>
              </div>
            </div>
            <div className="legal-section-body">
              <p>
                This Privacy Policy describes how <strong>MLM Book</strong> (&ldquo;we&rdquo;, &ldquo;us&rdquo;, &ldquo;our&rdquo;,
                or the &ldquo;Platform&rdquo;) collects, stores, processes, and safeguards the information you provide when accessing
                our web application, social networking feeds, business portal, digital wallet, community boards, and associated digital services.
              </p>
              <p>
                By creating an account, browsing public content, connecting with other members, or participating in rewarded
                advertising campaigns on MLM Book, you acknowledge that you have read, understood, and consented to the practices described herein.
              </p>
            </div>
          </section>

          {/* Section 2: Information We Collect */}
          <section id="data-collection" className="legal-section-card">
            <div className="legal-section-header">
              <div className="legal-section-icon-badge">
                <Database size={20} />
              </div>
              <div className="legal-section-title-wrap">
                <span className="legal-section-tag">Section 2</span>
                <h2 className="legal-section-title">Information We Collect</h2>
              </div>
            </div>
            <div className="legal-section-body">
              <p>
                To provide an authentic community, prevent automated bot registrations, and ensure legitimate wallet and business transactions,
                we collect specific categories of data:
              </p>

              <div className="legal-grid-features">
                <div className="legal-feature-box">
                  <strong>Account Identification</strong>
                  <p>Full legal name, chosen User ID, email address, password hash (one-way bcrypt), country code, and WhatsApp/mobile number for verification.</p>
                </div>
                <div className="legal-feature-box">
                  <strong>Network &amp; Introducer Graph</strong>
                  <p>Attributed Introducer User ID, referral connections, member friends list, and follow relationships.</p>
                </div>
                <div className="legal-feature-box">
                  <strong>Profile &amp; Social Content</strong>
                  <p>Profile avatar, cover images, biographical text, posts, photos, video uploads, stories, comments, and community discussions.</p>
                </div>
                <div className="legal-feature-box">
                  <strong>Wallet &amp; Transaction Logs</strong>
                  <p>Deposit records, transaction hash/receipt references, withdrawal payment requests, and verified campaign rewards.</p>
                </div>
              </div>

              <h4>Automatically Collected Technical Data</h4>
              <p>
                When you access MLM Book, our servers automatically log technical metadata including your Internet Protocol (IP) address,
                browser type, operating system, timestamped session tokens, device fingerprint, and referral URLs to protect against fraudulent intrusions.
              </p>
            </div>
          </section>

          {/* Section 3: How We Use Information */}
          <section id="data-usage" className="legal-section-card">
            <div className="legal-section-header">
              <div className="legal-section-icon-badge">
                <Eye size={20} />
              </div>
              <div className="legal-section-title-wrap">
                <span className="legal-section-tag">Section 3</span>
                <h2 className="legal-section-title">How We Use Your Information</h2>
              </div>
            </div>
            <div className="legal-section-body">
              <p>We process your data strictly for legitimate operational purposes:</p>
              <ul className="legal-bullets">
                <li><strong>Account Authentication &amp; Verification:</strong> Validating your mobile number and email to ensure legitimate humans populate the network.</li>
                <li><strong>Social Community Features:</strong> Rendering your personalized social feed, stories, watch hub, and delivering direct messages.</li>
                <li><strong>Referral Hierarchy Tracking:</strong> Correctly linking members to their designated introducers and computing legitimate referral incentives.</li>
                <li><strong>Financial Processing:</strong> Facilitating transparent deposit verification, internal wallet updates, and withdrawal fulfillment.</li>
                <li><strong>Trust &amp; Safety:</strong> Monitoring for fraudulent activity, fake engagement spam, prohibited schemes, or abusive behavior.</li>
                <li><strong>Service Notifications:</strong> Sending transactional alerts, security warnings, verification OTP codes, and critical updates.</li>
              </ul>
            </div>
          </section>

          {/* Section 4: Introducers & Social Graph */}
          <section id="introducer-system" className="legal-section-card">
            <div className="legal-section-header">
              <div className="legal-section-icon-badge">
                <Users size={20} />
              </div>
              <div className="legal-section-title-wrap">
                <span className="legal-section-tag">Section 4</span>
                <h2 className="legal-section-title">Introducers &amp; Social Graph Transparency</h2>
              </div>
            </div>
            <div className="legal-section-body">
              <p>
                MLM Book features an introducer-based network model. When you register with an introducer ID:
              </p>
              <ul className="legal-bullets">
                <li>Your Introducer can see your public member name, User ID, and registration date in their team connections list.</li>
                <li>Sensitive personal information—such as your account password, private wallet transaction details, or private messages—is <strong>never</strong> shared with your Introducer.</li>
                <li>If you join without an introducer, your account remains independent in the global MLM Book community.</li>
              </ul>
            </div>
          </section>

          {/* Section 5: Granular Privacy & Visibility */}
          <section id="privacy-controls" className="legal-section-card">
            <div className="legal-section-header">
              <div className="legal-section-icon-badge">
                <Lock size={20} />
              </div>
              <div className="legal-section-title-wrap">
                <span className="legal-section-tag">Section 5</span>
                <h2 className="legal-section-title">Granular Privacy &amp; Visibility Controls</h2>
              </div>
            </div>
            <div className="legal-section-body">
              <p>
                We equip every member with privacy controls located directly in their Account and Content creation interfaces:
              </p>
              <div className="legal-grid-features">
                <div className="legal-feature-box">
                  <strong>Post Visibility Toggles</strong>
                  <p>Choose between &lsquo;Public&rsquo; (anyone on MLM Book), &lsquo;Friends Only&rsquo; (connected contacts only), or &lsquo;Only Me&rsquo; (private drafts).</p>
                </div>
                <div className="legal-feature-box">
                  <strong>Mobile &amp; Email Concealment</strong>
                  <p>Your primary contact coordinates are kept confidential and are not broadcasted to other members unless you explicitly permit contact sharing.</p>
                </div>
                <div className="legal-feature-box">
                  <strong>Disconnection &amp; Blocking</strong>
                  <p>Instantly block unwanted accounts from viewing your profile, sending messages, or interacting with your published media.</p>
                </div>
                <div className="legal-feature-box">
                  <strong>Community Moderation</strong>
                  <p>Community creators hold full discretion to set privacy levels (Public, Private, or Secret) and manage discussion permissions.</p>
                </div>
              </div>
            </div>
          </section>

          {/* Section 6: Business Pages & Ad Rewards */}
          <section id="business-ads" className="legal-section-card">
            <div className="legal-section-header">
              <div className="legal-section-icon-badge">
                <Wallet size={20} />
              </div>
              <div className="legal-section-title-wrap">
                <span className="legal-section-tag">Section 6</span>
                <h2 className="legal-section-title">Business Pages &amp; Sponsored Ad Rewards</h2>
              </div>
            </div>
            <div className="legal-section-body">
              <p>
                MLM Book enables verified businesses to run promotional campaigns and reward engaged members:
              </p>
              <ul className="legal-bullets">
                <li>When interacting with a sponsored campaign, anonymized engagement metrics (impressions, clicks, verified view durations) are logged to substantiate reward payouts.</li>
                <li>If a sponsored ad campaign includes an optional lead capture or profile-sharing consent prompt, your contact information is shared <strong>only</strong> after your affirmative checkbox consent.</li>
                <li>All engagement records are subject to anti-bot verification to protect advertising integrity.</li>
              </ul>
            </div>
          </section>

          {/* Section 7: Data Sharing & Disclosure */}
          <section id="data-sharing" className="legal-section-card">
            <div className="legal-section-header">
              <div className="legal-section-icon-badge">
                <Share2 size={20} />
              </div>
              <div className="legal-section-title-wrap">
                <span className="legal-section-tag">Section 7</span>
                <h2 className="legal-section-title">Data Sharing &amp; Third-Party Disclosure</h2>
              </div>
            </div>
            <div className="legal-section-body">
              <p>
                We do not sell, rent, or trade your personal information. We disclose data solely under the following limited conditions:
              </p>
              <ul className="legal-bullets">
                <li><strong>Service Providers:</strong> Cloud hosting, database clusters, SMS/WhatsApp delivery gateways, and email dispatchers bound by strict data processing confidentiality.</li>
                <li><strong>Legal &amp; Regulatory Obligations:</strong> Where disclosure is required by enforceable law, court subpoena, or governmental fraud investigation.</li>
                <li><strong>Platform Protection:</strong> To enforce our Terms of Service, investigate systemic financial abuse, or protect the vital interests of community members.</li>
              </ul>
            </div>
          </section>

          {/* Section 8: Security & Retention */}
          <section id="security-retention" className="legal-section-card">
            <div className="legal-section-header">
              <div className="legal-section-icon-badge">
                <KeyRound size={20} />
              </div>
              <div className="legal-section-title-wrap">
                <span className="legal-section-tag">Section 8</span>
                <h2 className="legal-section-title">Security &amp; Data Retention</h2>
              </div>
            </div>
            <div className="legal-section-body">
              <p>
                We employ robust physical, technical, and managerial safeguards to protect your personal information:
              </p>
              <ul className="legal-bullets">
                <li>All communication between your device and MLM Book is encrypted using Transport Layer Security (TLS 1.3 / HTTPS).</li>
                <li>User passwords are hashed using high-cost bcrypt algorithms and cannot be decrypted by platform administrators.</li>
                <li>Financial transaction records are retained in compliance with standard statutory accounting, anti-money laundering, and auditing guidelines.</li>
              </ul>
            </div>
          </section>

          {/* Section 9: Your Rights & Choices */}
          <section id="user-rights" className="legal-section-card">
            <div className="legal-section-header">
              <div className="legal-section-icon-badge">
                <UserCheck size={20} />
              </div>
              <div className="legal-section-title-wrap">
                <span className="legal-section-tag">Section 9</span>
                <h2 className="legal-section-title">Your Rights &amp; Choices</h2>
              </div>
            </div>
            <div className="legal-section-body">
              <p>Depending on your jurisdiction, you possess the following rights regarding your personal information:</p>
              <ul className="legal-bullets">
                <li><strong>Right of Access:</strong> You can view and export your profile, connections, and activity records via Account Settings.</li>
                <li><strong>Right of Rectification:</strong> You may correct inaccurate or outdated information anytime via Profile Edit.</li>
                <li><strong>Right to Deletion:</strong> You can request account closure and personal data erasure, subject to mandatory financial record-keeping laws.</li>
                <li><strong>Opt-out of Communications:</strong> Adjust email and in-app notification preferences in Account Settings.</li>
              </ul>
            </div>
          </section>

          {/* Section 10: Updates & Contact */}
          <section id="contact-updates" className="legal-section-card">
            <div className="legal-section-header">
              <div className="legal-section-icon-badge">
                <Bell size={20} />
              </div>
              <div className="legal-section-title-wrap">
                <span className="legal-section-tag">Section 10</span>
                <h2 className="legal-section-title">Policy Updates &amp; Contact Desk</h2>
              </div>
            </div>
            <div className="legal-section-body">
              <p>
                We may periodically revise this Privacy Policy to reflect platform features, legal standards, or emerging technologies.
                When substantial revisions occur, we will notify you through platform announcements or email notices prior to the effective date.
              </p>
              <div className="legal-highlight-box">
                <strong>Data Protection &amp; Privacy Contact:</strong>
                <br />
                For inquiries regarding this policy or data subject requests, please submit an inquiry via our{' '}
                <Link to="/member/feedback-suggestions" style={{ color: 'var(--legal-primary)', fontWeight: 600 }}>
                  Feedback &amp; Support Portal
                </Link>{' '}
                or reach our compliance desk at <strong>privacy@mlmbook.com</strong>.
              </div>
            </div>
          </section>
        </article>
      </main>

      {/* Footer Banner */}
      <footer className="legal-footer-banner">
        <div className="legal-footer-inner">
          <div className="legal-footer-text">
            &copy; {new Date().getFullYear()} {siteName}. All rights reserved. Transparent, privacy-first community architecture.
          </div>
          <div className="legal-footer-links">
            <Link to="/terms-and-conditions" className="legal-footer-link">Terms &amp; Conditions</Link>
            <Link to="/privacy-policy" className="legal-footer-link">Privacy Policy</Link>
            <Link to="/member/register" className="legal-footer-link">Register</Link>
            <Link to="/member/login" className="legal-footer-link">Login</Link>
          </div>
        </div>
      </footer>
    </div>
  );
}

export default PrivacyPolicyPage;
