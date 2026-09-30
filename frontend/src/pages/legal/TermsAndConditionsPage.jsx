import { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import {
  FileText,
  Shield,
  CheckCircle2,
  Users,
  AlertTriangle,
  Wallet,
  Scale,
  Ban,
  HelpCircle,
  Printer,
  ChevronRight,
  ArrowLeft,
  Building,
  DollarSign,
  Scroll,
} from 'lucide-react';
import useBranding from '../../hooks/useBranding';
import '../../styles/legal-pages.css';

export function TermsAndConditionsPage() {
  const { logoUrl, siteName } = useBranding();
  const navigate = useNavigate();
  const [activeSection, setActiveSection] = useState('acceptance');

  useEffect(() => {
    window.scrollTo(0, 0);
    document.title = `Terms & Conditions | ${siteName || 'MLM Book'}`;
  }, [siteName]);

  const handlePrint = () => {
    window.print();
  };

  const sections = [
    { id: 'acceptance', num: '1', title: 'Acceptance & Eligibility', icon: Shield },
    { id: 'accounts', num: '2', title: 'Account Registration & Security', icon: Users },
    { id: 'introducers', num: '3', title: 'Introducer & Referral Model', icon: Users },
    { id: 'conduct', num: '4', title: 'Community Conduct & Prohibitions', icon: Ban },
    { id: 'content-ip', num: '5', title: 'User Content & Intellectual Property', icon: Scroll },
    { id: 'business-campaigns', num: '6', title: 'Business Pages & Ad Campaigns', icon: Building },
    { id: 'wallet-financials', num: '7', title: 'Digital Wallet, Deposits & Withdrawals', icon: Wallet },
    { id: 'earnings-disclaimer', num: '8', title: 'Earnings & Risk Disclaimers', icon: DollarSign },
    { id: 'termination', num: '9', title: 'Suspension & Termination', icon: AlertTriangle },
    { id: 'liability', num: '10', title: 'Limitation of Liability & Indemnity', icon: Scale },
    { id: 'governing-law', num: '11', title: 'Governing Law & Disputes', icon: Scale },
    { id: 'modifications', num: '12', title: 'Changes & Contact Details', icon: HelpCircle },
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
              <span className="legal-header-badge">Legal &amp; Compliance Center</span>
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

            <Link to="/privacy-policy" className="legal-nav-pill legal-nav-pill--switch">
              <Shield size={16} />
              <span>Privacy Policy</span>
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
            <FileText size={14} />
            <span>Member Service Agreement</span>
          </div>
          <h1 className="legal-hero-title">Terms &amp; Conditions</h1>
          <p className="legal-hero-desc">
            These terms define the legal rights, obligations, and standards of conduct for every
            individual and enterprise participating in the MLM Book ecosystem.
          </p>

          <div className="legal-hero-meta">
            <div className="legal-hero-meta-item">
              <CheckCircle2 size={15} color="#10b981" />
              <span>Status: Legally Binding</span>
            </div>
            <div className="legal-hero-meta-item">
              <span>Effective Date: September 2026</span>
            </div>
            <div className="legal-hero-meta-item">
              <span>Binding on All Registered Members</span>
            </div>
          </div>

          <div className="legal-tabs-bar">
            <Link to="/terms-and-conditions" className="legal-tab-btn is-active">
              <FileText size={16} />
              <span>Terms &amp; Conditions</span>
            </Link>
            <Link to="/privacy-policy" className="legal-tab-btn">
              <Shield size={16} />
              <span>Privacy Policy</span>
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
              <FileText size={14} />
              <span>Terms Navigation</span>
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
              <strong>Member Agreement Notice</strong>
              <p style={{ margin: '4px 0 8px', fontSize: '12px' }}>
                By checking &ldquo;I agree to Terms &amp; Conditions&rdquo; during account registration, you form a legally binding contract with MLM Book.
              </p>
              <Link
                to="/member/feedback-suggestions"
                style={{ color: 'var(--legal-primary)', fontWeight: 600, textDecoration: 'none', display: 'inline-flex', alignItems: 'center', gap: '4px' }}
              >
                Legal Help Desk <ChevronRight size={13} />
              </Link>
            </div>
          </div>
        </aside>

        {/* Content Area */}
        <article className="legal-content">
          {/* Important Highlight Box */}
          <div className="legal-callout">
            <Scale className="legal-callout-icon" size={24} />
            <div className="legal-callout-text">
              <h4>Important Agreement Summary</h4>
              <p>
                Please read these Terms carefully before proceeding with registration. MLM Book provides
                social networking, content publishing, community boards, and advertising rewards. We do not
                promise guaranteed financial returns or automated wealth. Ethical participation and identity
                authenticity are strictly enforced.
              </p>
            </div>
          </div>

          {/* Section 1: Acceptance & Eligibility */}
          <section id="acceptance" className="legal-section-card">
            <div className="legal-section-header">
              <div className="legal-section-icon-badge">
                <Shield size={20} />
              </div>
              <div className="legal-section-title-wrap">
                <span className="legal-section-tag">Section 1</span>
                <h2 className="legal-section-title">Acceptance of Terms &amp; Eligibility</h2>
              </div>
            </div>
            <div className="legal-section-body">
              <p>
                These Terms and Conditions (&ldquo;Terms&rdquo;) constitute a legally enforceable agreement between you
                (&ldquo;User&rdquo;, &ldquo;Member&rdquo;, or &ldquo;You&rdquo;) and <strong>MLM Book</strong> (&ldquo;Company&rdquo;, &ldquo;we&rdquo;, &ldquo;us&rdquo;, or &ldquo;Platform&rdquo;).
              </p>
              <p>
                To qualify for registration and maintain an active account, you warrant and represent that:
              </p>
              <ul className="legal-bullets">
                <li>You are at least 18 years of age (or the legal age of majority in your jurisdiction of residence).</li>
                <li>You possess full legal capacity to enter into binding agreements.</li>
                <li>You have not been previously banned, suspended, or restricted from MLM Book for fraudulent behavior or terms violations.</li>
                <li>Your use of the Platform complies with all applicable local, national, and international laws.</li>
              </ul>
            </div>
          </section>

          {/* Section 2: Account Registration & Security */}
          <section id="accounts" className="legal-section-card">
            <div className="legal-section-header">
              <div className="legal-section-icon-badge">
                <Users size={20} />
              </div>
              <div className="legal-section-title-wrap">
                <span className="legal-section-tag">Section 2</span>
                <h2 className="legal-section-title">Account Registration, User ID &amp; Security</h2>
              </div>
            </div>
            <div className="legal-section-body">
              <p>
                When creating an account on MLM Book, you must provide truthful, current, and complete information:
              </p>
              <div className="legal-grid-features">
                <div className="legal-feature-box">
                  <strong>Single Account Policy</strong>
                  <p>Each individual is permitted one primary account. Creation of duplicate, proxy, or fake burner profiles is strictly prohibited and leads to immediate deactivation.</p>
                </div>
                <div className="legal-feature-box">
                  <strong>Unique User ID</strong>
                  <p>Each member is identified by a unique 10-character User ID. User IDs must not impersonate public figures, brands, or contain offensive terms.</p>
                </div>
                <div className="legal-feature-box">
                  <strong>Mobile &amp; WhatsApp Verification</strong>
                  <p>To curb automated spam, accounts require mobile verification. Failure to verify phone credentials may restrict reward eligibility and referral features.</p>
                </div>
                <div className="legal-feature-box">
                  <strong>Credential Confidentiality</strong>
                  <p>You are solely responsible for maintaining the confidentiality of your password and credentials. You must immediately report any unauthorized access.</p>
                </div>
              </div>
            </div>
          </section>

          {/* Section 3: Introducer & Referral Model */}
          <section id="introducers" className="legal-section-card">
            <div className="legal-section-header">
              <div className="legal-section-icon-badge">
                <Users size={20} />
              </div>
              <div className="legal-section-title-wrap">
                <span className="legal-section-tag">Section 3</span>
                <h2 className="legal-section-title">Introducer &amp; Referral Network Model</h2>
              </div>
            </div>
            <div className="legal-section-body">
              <p>
                MLM Book incorporates an introducer / referral architecture designed to foster community growth and collaborative networking:
              </p>
              <ul className="legal-bullets">
                <li><strong>Voluntary Introducer Relationship:</strong> You may register using an Introducer User ID or join independently. Once assigned and confirmed, the primary referral relationship is fixed to prevent network manipulation.</li>
                <li><strong>Independent Status:</strong> Introducers and members are independent community participants. No employment, agency, joint venture, or franchise relationship is formed between members and MLM Book.</li>
                <li><strong>Ethical Promotion:</strong> Members promoting MLM Book must never make false guarantees of earnings, unverified claims of guaranteed income, or engage in coercive recruitment tactics.</li>
              </ul>
            </div>
          </section>

          {/* Section 4: Community Conduct & Prohibitions */}
          <section id="conduct" className="legal-section-card">
            <div className="legal-section-header">
              <div className="legal-section-icon-badge">
                <Ban size={20} />
              </div>
              <div className="legal-section-title-wrap">
                <span className="legal-section-tag">Section 4</span>
                <h2 className="legal-section-title">Community Conduct &amp; Prohibited Activities</h2>
              </div>
            </div>
            <div className="legal-section-body">
              <p>To preserve safety and trust, the following activities are strictly prohibited across the Platform:</p>
              <ul className="legal-bullets">
                <li><strong>Harassment &amp; Hate Speech:</strong> Posting defamatory, discriminatory, threatening, violent, or abusive material toward any person or group.</li>
                <li><strong>Illegal Schemes &amp; Financial Fraud:</strong> Promoting fraudulent investments, unauthorized crypto doubles, unlicensed lottery pools, or unlawful pyramid operations.</li>
                <li><strong>Automated Scraping &amp; Bots:</strong> Utilizing scripts, bots, spiders, or automated crawlers to harvest user data, artificially inflate post engagement, or bypass security barriers.</li>
                <li><strong>Spam &amp; Unsolicited Promotion:</strong> Bombarding public communities or direct messages with unauthorized advertisements or bulk spam.</li>
                <li><strong>Intellectual Property Infringement:</strong> Uploading copyrighted music, videos, photos, or trademarks without authorized rights from the copyright holder.</li>
              </ul>
            </div>
          </section>

          {/* Section 5: User Content & Intellectual Property */}
          <section id="content-ip" className="legal-section-card">
            <div className="legal-section-header">
              <div className="legal-section-icon-badge">
                <Scroll size={20} />
              </div>
              <div className="legal-section-title-wrap">
                <span className="legal-section-tag">Section 5</span>
                <h2 className="legal-section-title">User Content &amp; Intellectual Property Rights</h2>
              </div>
            </div>
            <div className="legal-section-body">
              <p>
                <strong>Ownership:</strong> You retain complete ownership and intellectual property rights in the original text, photos, videos, and stories that you post on MLM Book.
              </p>
              <p>
                <strong>License to Platform:</strong> By submitting or publishing content on MLM Book, you grant us a worldwide, non-exclusive, royalty-free, transferable license to host, display, reproduce, adapt, and distribute your content solely for the purpose of operating, improving, and promoting the Platform and its community features according to your privacy settings.
              </p>
              <p>
                <strong>Right to Moderate:</strong> MLM Book reserves the right, but does not assume the obligation, to review, flag, or remove any content that violates these Terms or applicable laws.
              </p>
            </div>
          </section>

          {/* Section 6: Business Pages & Ad Campaigns */}
          <section id="business-campaigns" className="legal-section-card">
            <div className="legal-section-header">
              <div className="legal-section-icon-badge">
                <Building size={20} />
              </div>
              <div className="legal-section-title-wrap">
                <span className="legal-section-tag">Section 6</span>
                <h2 className="legal-section-title">Business Pages &amp; Advertising Campaigns</h2>
              </div>
            </div>
            <div className="legal-section-body">
              <p>
                Verified members can launch Business Pages and configure paid advertising campaigns to showcase products, services, or events:
              </p>
              <ul className="legal-bullets">
                <li><strong>Advertiser Responsibility:</strong> Advertisers must ensure their campaigns comply with consumer protection regulations, truth in advertising laws, and platform policies.</li>
                <li><strong>Campaign Budgets &amp; Rewards:</strong> Advertisers fund campaigns using allocated wallet deposits. Allocated budgets are dispersed as reward credits to verified members who fulfill interaction requirements.</li>
                <li><strong>Anti-Click Fraud:</strong> Fake clicks, self-referral clicks, emulator traffic, and coordinated engagement manipulation are detected via automated heuristics and result in campaign suspension with forfeiture of illicitly obtained credits.</li>
              </ul>
            </div>
          </section>

          {/* Section 7: Wallet, Deposits & Withdrawals */}
          <section id="wallet-financials" className="legal-section-card">
            <div className="legal-section-header">
              <div className="legal-section-icon-badge">
                <Wallet size={20} />
              </div>
              <div className="legal-section-title-wrap">
                <span className="legal-section-tag">Section 7</span>
                <h2 className="legal-section-title">Digital Wallet, Deposits, Rewards &amp; Withdrawals</h2>
              </div>
            </div>
            <div className="legal-section-body">
              <p>
                MLM Book features an integrated digital wallet system for managing deposits, advertising balances, earned reward credits, and withdrawals:
              </p>
              <div className="legal-grid-features">
                <div className="legal-feature-box">
                  <strong>Deposit Processing</strong>
                  <p>Deposits submitted via crypto, bank, or supported gateways require on-chain or administrative verification before balance credits are made available.</p>
                </div>
                <div className="legal-feature-box">
                  <strong>Withdrawal Thresholds &amp; Auditing</strong>
                  <p>Withdrawals are subject to minimum balance limits, security review, and phone verification. Suspicious accounts are frozen pending manual compliance audit.</p>
                </div>
                <div className="legal-feature-box">
                  <strong>Tax &amp; Regulatory Compliance</strong>
                  <p>Members are solely responsible for calculating, reporting, and remitting any applicable income or capital gains taxes arising from platform earnings in their jurisdiction.</p>
                </div>
                <div className="legal-feature-box">
                  <strong>Anti-Money Laundering (AML)</strong>
                  <p>We enforce AML policies. Structuring deposits to obscure illicit funds or attempting cross-border evasion will lead to permanent freezing and regulatory referral.</p>
                </div>
              </div>
            </div>
          </section>

          {/* Section 8: Earnings Disclaimers */}
          <section id="earnings-disclaimer" className="legal-section-card">
            <div className="legal-section-header">
              <div className="legal-section-icon-badge">
                <DollarSign size={20} />
              </div>
              <div className="legal-section-title-wrap">
                <span className="legal-section-tag">Section 8</span>
                <h2 className="legal-section-title">Earnings &amp; Business Opportunity Disclaimers</h2>
              </div>
            </div>
            <div className="legal-section-body">
              <div className="legal-highlight-box" style={{ borderLeftColor: '#f59e0b', background: '#fffbeb' }}>
                <strong style={{ color: '#92400e' }}>NO GUARANTEE OF FINANCIAL SUCCESS:</strong>
                <p style={{ margin: '6px 0 0', color: '#78350f', fontSize: '13.5px' }}>
                  MLM Book makes no representations, warranties, or guarantees regarding specific income, profits, or financial returns.
                  Any testimonials, example figures, or potential earnings mentioned on the Platform or by members represent potential outcomes
                  and are not assurances of actual results. Your success depends entirely on your own effort, skills, business acumen, and market conditions.
                </p>
              </div>
            </div>
          </section>

          {/* Section 9: Suspension & Termination */}
          <section id="termination" className="legal-section-card">
            <div className="legal-section-header">
              <div className="legal-section-icon-badge">
                <AlertTriangle size={20} />
              </div>
              <div className="legal-section-title-wrap">
                <span className="legal-section-tag">Section 9</span>
                <h2 className="legal-section-title">Suspension, Deactivation &amp; Account Termination</h2>
              </div>
            </div>
            <div className="legal-section-body">
              <p>
                We maintain the absolute right to suspend, terminate, or restrict access to any account without notice if:
              </p>
              <ul className="legal-bullets">
                <li>You breach or violate any provision of these Terms or the Privacy Policy.</li>
                <li>We detect fraudulent activity, identity falsification, duplicate multi-accounts, or bot engagement.</li>
                <li>Required by law enforcement or judicial order.</li>
                <li>Your account exhibits malicious behavior that threatens platform infrastructure or user safety.</li>
              </ul>
            </div>
          </section>

          {/* Section 10: Limitation of Liability */}
          <section id="liability" className="legal-section-card">
            <div className="legal-section-header">
              <div className="legal-section-icon-badge">
                <Scale size={20} />
              </div>
              <div className="legal-section-title-wrap">
                <span className="legal-section-tag">Section 10</span>
                <h2 className="legal-section-title">Limitation of Liability &amp; Indemnification</h2>
              </div>
            </div>
            <div className="legal-section-body">
              <p>
                To the maximum extent permitted by applicable law, MLM Book, its founders, directors, employees, and agents
                shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including loss of profits,
                loss of data, or business interruption arising out of your access to, or inability to access, the Platform.
              </p>
              <p>
                You agree to defend, indemnify, and hold harmless MLM Book against any third-party claims, liabilities, damages, or costs
                arising from your violation of these Terms or infringement of third-party rights.
              </p>
            </div>
          </section>

          {/* Section 11: Governing Law & Disputes */}
          <section id="governing-law" className="legal-section-card">
            <div className="legal-section-header">
              <div className="legal-section-icon-badge">
                <Scale size={20} />
              </div>
              <div className="legal-section-title-wrap">
                <span className="legal-section-tag">Section 11</span>
                <h2 className="legal-section-title">Governing Law &amp; Dispute Resolution</h2>
              </div>
            </div>
            <div className="legal-section-body">
              <p>
                These Terms shall be governed by and construed in accordance with the laws governing the Company&rsquo;s jurisdiction,
                without regard to conflict of law principles.
              </p>
              <p>
                Any dispute, controversy, or claim arising out of or relating to these Terms shall first be addressed through good-faith
                direct negotiation by contacting our compliance desk. If negotiation fails, disputes shall be resolved through binding arbitration.
              </p>
            </div>
          </section>

          {/* Section 12: Changes & Contact */}
          <section id="modifications" className="legal-section-card">
            <div className="legal-section-header">
              <div className="legal-section-icon-badge">
                <HelpCircle size={20} />
              </div>
              <div className="legal-section-title-wrap">
                <span className="legal-section-tag">Section 12</span>
                <h2 className="legal-section-title">Modifications to Terms &amp; Legal Inquiries</h2>
              </div>
            </div>
            <div className="legal-section-body">
              <p>
                We reserve the right to revise and update these Terms at any time. Material revisions will be posted on this page with an updated
                Effective Date and announced via platform notices. Your continued use of MLM Book following posted modifications signifies your
                binding acceptance of the updated terms.
              </p>
              <div className="legal-highlight-box">
                <strong>Legal &amp; Policy Support:</strong>
                <br />
                For any questions or legal notices regarding these Terms and Conditions, please submit a request via our{' '}
                <Link to="/member/feedback-suggestions" style={{ color: 'var(--legal-primary)', fontWeight: 600 }}>
                  Feedback &amp; Suggestions Channel
                </Link>{' '}
                or contact our legal counsel at <strong>legal@mlmbook.com</strong>.
              </div>
            </div>
          </section>
        </article>
      </main>

      {/* Footer Banner */}
      <footer className="legal-footer-banner">
        <div className="legal-footer-inner">
          <div className="legal-footer-text">
            &copy; {new Date().getFullYear()} {siteName}. All rights reserved. Transparent, legally compliant platform terms.
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

export default TermsAndConditionsPage;
