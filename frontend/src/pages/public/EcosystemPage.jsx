import { useState, useContext, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { BrandingContext } from '../../context/brandingContextDef';
import {
  Sparkles,
  Building2,
  Users,
  Wallet,
  ShieldCheck,
  CheckCircle2,
  XCircle,
  ArrowRight,
  TrendingUp,
  TvMinimalPlay,
  Layers,
  ChevronDown,
  Megaphone,
  Briefcase,
  Smartphone,
  Award,
  Globe,
  Coins,
  Check,
  Sliders,
  DollarSign,
  AlertTriangle,
  Lock,
  Search,
  Share2,
} from 'lucide-react';

export function EcosystemPage() {
  const { siteName } = useContext(BrandingContext) || {};
  const platformName = siteName || 'MLM Book';

  useEffect(() => {
    document.title = `${platformName} Ecosystem ΓÇö The Dedicated Direct Selling, Social & Shared-Value Platform`;
  }, [platformName]);

  // Persona Switcher Tab
  const [activePersona, setActivePersona] = useState('leaders'); // 'leaders' | 'business' | 'members'

  // FAQ Accordion State
  const [openFaq, setOpenFaq] = useState(0);

  const toggleFaq = (index) => {
    setOpenFaq(openFaq === index ? null : index);
  };

  const faqs = [
    {
      q: `What is the core purpose and motive of ${platformName}?`,
      a: `${platformName} was created to give network marketing professionals, direct sellers, and independent business owners a dedicated, supportive home. Mainstream social networks frequently shadow-ban, penalize, or classify direct selling content as spam. ${platformName} provides a verified, bot-free environment where entrepreneurs can build their brand, promote compliant campaigns, connect with authentic people, and share digital advertising value directly with engaged community members.`,
    },
    {
      q: `Is ${platformName} an investment or passive ROI scheme?`,
      a: `No, absolutely not. ${platformName} is NOT an investment program, cryptocurrency speculation pool, or daily passive ROI scheme. All rewards are strictly performance-based and funded directly by verified business advertising budgets. Members earn rewards only by completing supported engagement actions on active sponsored campaigns.`,
    },
    {
      q: 'How does the Dual-Wallet Architecture protect platform members?',
      a: 'The Dual-Wallet structure cleanly segregates operations: the Fund Wallet (P2P Wallet) is exclusively used by advertisers and businesses to deposit USDT and fund advertising campaigns with 0% deduction. The Reward Wallet is dedicated entirely to crediting verified member campaign earnings and referral rewards. This ensures complete accounting transparency and guarantees available liquidity for member payouts.',
    },
    {
      q: 'Why does the platform require WhatsApp and mobile verification?',
      a: 'To permanently eliminate bot traffic, click-farms, and fake multi-accounts. On traditional ad platforms, up to 40% of ad spend is stolen by automated scripts. By verifying each member through an active mobile number, businesses are guaranteed 100% real human eyes, and legitimate members receive all the reward benefits.',
    },
    {
      q: 'How can businesses and direct sales brands advertise on the platform?',
      a: 'Any verified member can create a professional Business Page, publish promotional posts or product demonstration videos, and convert those posts into sponsored Ad Campaigns. Advertisers specify their USDT budget, set engagement parameters, and pay only when verified human members interact with their content.',
    },
    {
      q: 'How and when can members withdraw their earned rewards?',
      a: 'Once rewards are credited to your Reward Wallet, you can request an instant Web3 withdrawal at any time. Withdrawals are processed on the Binance Smart Chain (BSC BEP-20) in USDT directly to your verified private crypto wallet address, governed by dynamic platform fee settings configured in the system.',
    },
  ];

  return (
    <div style={{ paddingBottom: '90px' }}>
      {/* ====================================================================
          1. HERO SECTION: The Core Motive & Identity
          ==================================================================== */}
      <section className="eco-hero">
        <div className="pub-badge pub-badge-indigo" style={{ margin: '0 auto 14px' }}>
          <Sparkles className="w-4 h-4" />
          <span>The Purpose-Built Direct Selling & Commerce Network</span>
        </div>

        <h1 className="eco-hero-title">
          Where <span className="pub-title-gradient">Direct Sellers Network Freely</span>,{' '}
          <br className="hidden sm:inline" />
          Brands Grow & Active Engagement is <span style={{ color: '#059669' }}>Rewarded</span>
        </h1>

        <p className="eco-hero-desc">
          {platformName} is the first digital ecosystem built specifically to solve the challenges of
          network marketers and independent entrepreneurs. We unite authentic social networking,
          verified business discovery, targeted ad campaigns, and direct Web3 reward sharing into one
          transparent, bot-free platform.
        </p>

        {/* Action Buttons */}
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '14px', flexWrap: 'wrap' }}>
          <Link to="/member/register" className="pub-btn pub-btn-primary pub-btn-lg">
            <Sparkles className="w-5 h-5" />
            <span>Join Ecosystem Free</span>
            <ArrowRight className="w-5 h-5" />
          </Link>
          <a href="#how-it-works" className="pub-btn pub-btn-outline pub-btn-lg">
            <Sliders className="w-5 h-5" />
            <span>Explore How It Works</span>
          </a>
        </div>

        {/* 3 Balanced Real Platform Metrics */}
        <div className="eco-metrics-bar">
          <div className="eco-metric-item">
            <span className="eco-metric-val" style={{ color: '#7c3aed' }}>100% Real Humans</span>
            <span className="eco-metric-lbl">WhatsApp Phone Verified Community</span>
          </div>
          <div className="eco-metric-item">
            <span className="eco-metric-val" style={{ color: '#059669' }}>Zero Bot Ad Waste</span>
            <span className="eco-metric-lbl">Advertisers Reach Real Verified People</span>
          </div>
          <div className="eco-metric-item">
            <span className="eco-metric-val" style={{ color: '#2563eb' }}>Instant Web3 USDT</span>
            <span className="eco-metric-lbl">Cashable BEP-20 Dual-Wallet Payouts</span>
          </div>
        </div>
      </section>

      <div className="pub-container" id="how-it-works">
        {/* ====================================================================
            2. THE CORE MOTIVE: Traditional Platforms vs MLM Book
            ==================================================================== */}
        <div style={{ textAlign: 'center', marginTop: '48px', marginBottom: '20px' }}>
          <div className="pub-badge pub-badge-amber" style={{ margin: '0 auto 10px' }}>
            <AlertTriangle className="w-4 h-4" />
            <span>Why MLM Book Exists</span>
          </div>
          <h2 style={{ fontSize: 'clamp(1.5rem, 3vw, 2.2rem)', fontWeight: '800', color: '#0f172a', margin: '0 0 10px' }}>
            The Direct Selling Problem vs. The {platformName} Solution
          </h2>
          <p style={{ color: '#64748b', maxWidth: '720px', margin: '0 auto', fontSize: '1rem', lineHeight: '1.6' }}>
            For years, direct selling leaders and product creators have built businesses on borrowed digital ground.
            Here is how {platformName} changes the paradigm forever.
          </p>
        </div>

        <div className="eco-contrast-grid">
          {/* Problem Card: The Traditional Social Trap */}
          <div className="eco-contrast-card eco-contrast-card--problem">
            <div style={{ display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '16px' }}>
              <div style={{ width: '40px', height: '40px', borderRadius: '10px', background: '#ffedd5', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#ea580c' }}>
                <XCircle className="w-5 h-5" />
              </div>
              <div>
                <h3 style={{ fontSize: '1.15rem', fontWeight: '800', color: '#9a3412', margin: 0 }}>
                  The Mainstream Social Media Trap
                </h3>
                <span style={{ fontSize: '0.8rem', color: '#c2410c', fontWeight: '600' }}>
                  Facebook, Instagram, YouTube & LinkedIn
                </span>
              </div>
            </div>

            <p style={{ fontSize: '0.88rem', color: '#7c2d12', lineHeight: '1.6', margin: '0 0 16px' }}>
              Traditional Big Tech platforms treat network marketing and direct selling with hostility while taking 100% of the financial upside.
            </p>

            <ul className="eco-contrast-list">
              <li className="eco-contrast-item">
                <XCircle className="w-4 h-4 text-rose-500 shrink-0 mt-0.5" />
                <span>
                  <strong>Arbitrary Shadow-Banning & Account Bans:</strong> Using direct sales or team-building keywords often triggers automated account restrictions.
                </span>
              </li>
              <li className="eco-contrast-item">
                <XCircle className="w-4 h-4 text-rose-500 shrink-0 mt-0.5" />
                <span>
                  <strong>Massive Ad Fraud & Bot Waste:</strong> Advertisers pay for clicks where 30% to 50% are automated bot farms with zero real human conversions.
                </span>
              </li>
              <li className="eco-contrast-item">
                <XCircle className="w-4 h-4 text-rose-500 shrink-0 mt-0.5" />
                <span>
                  <strong>Zero Compensation for Member Attention:</strong> Users spend hours engaging, while big tech platforms pocket $billions in advertising revenue.
                </span>
              </li>
              <li className="eco-contrast-item">
                <XCircle className="w-4 h-4 text-rose-500 shrink-0 mt-0.5" />
                <span>
                  <strong>Zero Verified Business Trust:</strong> Anyone can spin up anonymous fake accounts, eroding buyer trust and legitimate business credibility.
                </span>
              </li>
            </ul>
          </div>

          {/* Solution Card: The MLM Book Ecosystem */}
          <div className="eco-contrast-card eco-contrast-card--solution">
            <div style={{ display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '16px' }}>
              <div style={{ width: '40px', height: '40px', borderRadius: '10px', background: '#dcfce7', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#16a34a' }}>
                <CheckCircle2 className="w-5 h-5" />
              </div>
              <div>
                <h3 style={{ fontSize: '1.15rem', fontWeight: '800', color: '#166534', margin: 0 }}>
                  The {platformName} Ecosystem
                </h3>
                <span style={{ fontSize: '0.8rem', color: '#15803d', fontWeight: '600' }}>
                  Purpose-Built, Verified & Fair-Share
                </span>
              </div>
            </div>

            <p style={{ fontSize: '0.88rem', color: '#14532d', lineHeight: '1.6', margin: '0 0 16px' }}>
              A compliant, welcoming digital ecosystem where networkers build freely, businesses get verified real leads, and members earn for their time.
            </p>

            <ul className="eco-contrast-list">
              <li className="eco-contrast-item">
                <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                <span>
                  <strong>A Safe, Dedicated Community Home:</strong> Network marketers can share product stories, team achievements, and video presentations without fear of censorship.
                </span>
              </li>
              <li className="eco-contrast-item">
                <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                <span>
                  <strong>100% Real Humans, 0% Bot Waste:</strong> Mandatory mobile/WhatsApp verification ensures every view, click, and engagement comes from a genuine person.
                </span>
              </li>
              <li className="eco-contrast-item">
                <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                <span>
                  <strong>Shared-Value Reward Economy:</strong> Advertiser budgets are shared directly with verified members who interact with campaigns into their Reward Wallet.
                </span>
              </li>
              <li className="eco-contrast-item">
                <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                <span>
                  <strong>Web3 Instant Blockchain Payouts:</strong> Transparent Dual-Wallet architecture with cashable USDT (BEP-20) withdrawals directly to your private wallet.
                </span>
              </li>
            </ul>
          </div>
        </div>

        {/* ====================================================================
            3. THE 4 FOUNDATIONAL PILLARS
            ==================================================================== */}
        <div style={{ textAlign: 'center', marginTop: '60px', marginBottom: '20px' }}>
          <div className="pub-badge pub-badge-indigo" style={{ margin: '0 auto 10px' }}>
            <Layers className="w-4 h-4" />
            <span>Platform Architecture</span>
          </div>
          <h2 style={{ fontSize: 'clamp(1.5rem, 3vw, 2.2rem)', fontWeight: '800', color: '#0f172a', margin: '0 0 10px' }}>
            The 4 Pillars of the {platformName} Ecosystem
          </h2>
          <p style={{ color: '#64748b', maxWidth: '720px', margin: '0 auto', fontSize: '1rem', lineHeight: '1.6' }}>
            Each component works in complete synergy to create a self-sustaining economic and social flywheel.
          </p>
        </div>

        <div className="eco-pillars-grid">
          {/* Pillar 1: Social Networking & Watch Hub */}
          <div className="eco-pillar-card">
            <div className="eco-pillar-card__top">
              <div className="eco-pillar-card__icon" style={{ background: '#eff6ff', color: '#2563eb' }}>
                <TvMinimalPlay className="w-6 h-6" />
              </div>
              <span style={{ fontSize: '0.75rem', fontWeight: '700', padding: '4px 10px', borderRadius: '20px', background: '#eff6ff', color: '#2563eb', border: '1px solid #bfdbfe' }}>
                Pillar 01
              </span>
            </div>

            <h3 className="eco-pillar-card__title">Social Network & Watch Media Hub</h3>
            <p className="eco-pillar-card__desc">
              Connect with fellow entrepreneurs through interactive feeds, rich stories, dedicated video reels, and niche discussion communities.
            </p>

            <ul className="eco-pillar-points">
              <li className="eco-pillar-point">
                <Check className="w-4 h-4 text-blue-600" />
                <span>Unrestricted sharing of network marketing & product stories</span>
              </li>
              <li className="eco-pillar-point">
                <Check className="w-4 h-4 text-blue-600" />
                <span>Dedicated Watch Video hub for training & video masterclasses</span>
              </li>
              <li className="eco-pillar-point">
                <Check className="w-4 h-4 text-blue-600" />
                <span>Topic-based communities for mastermind discussions</span>
              </li>
            </ul>

            <div style={{ marginTop: 'auto', paddingTop: '16px', borderTop: '1px solid #f1f5f9' }}>
              <Link to="/member/socials" style={{ color: '#2563eb', fontWeight: '700', fontSize: '0.88rem', textDecoration: 'none', display: 'inline-flex', alignItems: 'center', gap: '6px' }}>
                <span>Explore Socials & Watch</span>
                <ArrowRight className="w-4 h-4" />
              </Link>
            </div>
          </div>

          {/* Pillar 2: Business Pages & Directory */}
          <div className="eco-pillar-card">
            <div className="eco-pillar-card__top">
              <div className="eco-pillar-card__icon" style={{ background: '#f5f3ff', color: '#7c3aed' }}>
                <Building2 className="w-6 h-6" />
              </div>
              <span style={{ fontSize: '0.75rem', fontWeight: '700', padding: '4px 10px', borderRadius: '20px', background: '#f5f3ff', color: '#7c3aed', border: '1px solid #ddd6fe' }}>
                Pillar 02
              </span>
            </div>

            <h3 className="eco-pillar-card__title">Business Pages & Enterprise Directory</h3>
            <p className="eco-pillar-card__desc">
              Establish your commercial identity. Create branded business pages, list products and services, and get discovered globally.
            </p>

            <ul className="eco-pillar-points">
              <li className="eco-pillar-point">
                <Check className="w-4 h-4 text-purple-600" />
                <span>Dedicated brand profiles with product catalogues & business links</span>
              </li>
              <li className="eco-pillar-point">
                <Check className="w-4 h-4 text-purple-600" />
                <span>Searchable Business Directory categorized by industry</span>
              </li>
              <li className="eco-pillar-point">
                <Check className="w-4 h-4 text-purple-600" />
                <span>Direct customer inquiries & WhatsApp contact buttons</span>
              </li>
            </ul>

            <div style={{ marginTop: 'auto', paddingTop: '16px', borderTop: '1px solid #f1f5f9' }}>
              <Link to="/member/business-pages" style={{ color: '#7c3aed', fontWeight: '700', fontSize: '0.88rem', textDecoration: 'none', display: 'inline-flex', alignItems: 'center', gap: '6px' }}>
                <span>Manage Business Pages</span>
                <ArrowRight className="w-4 h-4" />
              </Link>
            </div>
          </div>

          {/* Pillar 3: Ad Campaign Engine */}
          <div className="eco-pillar-card">
            <div className="eco-pillar-card__top">
              <div className="eco-pillar-card__icon" style={{ background: '#fffbeb', color: '#d97706' }}>
                <Megaphone className="w-6 h-6" />
              </div>
              <span style={{ fontSize: '0.75rem', fontWeight: '700', padding: '4px 10px', borderRadius: '20px', background: '#fffbeb', color: '#d97706', border: '1px solid #fde68a' }}>
                Pillar 03
              </span>
            </div>

            <h3 className="eco-pillar-card__title">Targeted Ad Campaign Engine</h3>
            <p className="eco-pillar-card__desc">
              Convert your Business Page posts into high-performance advertising campaigns funded in USDT, delivered to 100% verified real humans.
            </p>

            <ul className="eco-pillar-points">
              <li className="eco-pillar-point">
                <Check className="w-4 h-4 text-amber-600" />
                <span>Zero bot fraud: pay only when real human members interact</span>
              </li>
              <li className="eco-pillar-point">
                <Check className="w-4 h-4 text-amber-600" />
                <span>Flexible USDT budgeting with real-time impression analytics</span>
              </li>
              <li className="eco-pillar-point">
                <Check className="w-4 h-4 text-amber-600" />
                <span>Administrative review ensuring safety, quality, and ethical standards</span>
              </li>
            </ul>

            <div style={{ marginTop: 'auto', paddingTop: '16px', borderTop: '1px solid #f1f5f9' }}>
              <Link to="/member/business-pages" style={{ color: '#d97706', fontWeight: '700', fontSize: '0.88rem', textDecoration: 'none', display: 'inline-flex', alignItems: 'center', gap: '6px' }}>
                <span>Launch Campaign</span>
                <ArrowRight className="w-4 h-4" />
              </Link>
            </div>
          </div>

          {/* Pillar 4: Dual-Wallet Reward Engine */}
          <div className="eco-pillar-card">
            <div className="eco-pillar-card__top">
              <div className="eco-pillar-card__icon" style={{ background: '#ecfdf5', color: '#059669' }}>
                <Wallet className="w-6 h-6" />
              </div>
              <span style={{ fontSize: '0.75rem', fontWeight: '700', padding: '4px 10px', borderRadius: '20px', background: '#ecfdf5', color: '#059669', border: '1px solid #a7f3d0' }}>
                Pillar 04
              </span>
            </div>

            <h3 className="eco-pillar-card__title">Shared-Value Reward Wallet</h3>
            <p className="eco-pillar-card__desc">
              When members engage with sponsored campaigns, advertising revenue is directly shared into their cashable Reward Wallet in USDT.
            </p>

            <ul className="eco-pillar-points">
              <li className="eco-pillar-point">
                <Check className="w-4 h-4 text-emerald-600" />
                <span>Direct USDT reward credits for authentic engagement</span>
              </li>
              <li className="eco-pillar-point">
                <Check className="w-4 h-4 text-emerald-600" />
                <span>Clean Dual-Wallet architecture separating ad capital from rewards</span>
              </li>
              <li className="eco-pillar-point">
                <Check className="w-4 h-4 text-emerald-600" />
                <span>Fast Web3 blockchain cashouts to personal BSC (BEP-20) wallets</span>
              </li>
            </ul>

            <div style={{ marginTop: 'auto', paddingTop: '16px', borderTop: '1px solid #f1f5f9' }}>
              <Link to="/member/web3-wallet" style={{ color: '#059669', fontWeight: '700', fontSize: '0.88rem', textDecoration: 'none', display: 'inline-flex', alignItems: 'center', gap: '6px' }}>
                <span>Access Reward Wallet</span>
                <ArrowRight className="w-4 h-4" />
              </Link>
            </div>
          </div>
        </div>

        {/* ====================================================================
            4. THE CLOSED-LOOP ECOSYSTEM FLOW
            ==================================================================== */}
        <div style={{ textAlign: 'center', marginTop: '60px', marginBottom: '20px' }}>
          <div className="pub-badge pub-badge-indigo" style={{ margin: '0 auto 10px' }}>
            <TrendingUp className="w-4 h-4" />
            <span>Economic Flywheel</span>
          </div>
          <h2 style={{ fontSize: 'clamp(1.5rem, 3vw, 2.2rem)', fontWeight: '800', color: '#0f172a', margin: '0 0 10px' }}>
            How the Closed-Loop Cycle Works
          </h2>
          <p style={{ color: '#64748b', maxWidth: '720px', margin: '0 auto', fontSize: '1rem', lineHeight: '1.6' }}>
            A self-sustaining, win-win journey where every participant benefits from transparent value creation.
          </p>
        </div>

        <div className="eco-journey-grid">
          {/* Step 1 */}
          <div className="eco-journey-card">
            <span className="eco-journey-step-badge eco-step-num--blue">01</span>
            <h4 style={{ fontSize: '1rem', fontWeight: '800', color: '#0f172a', margin: '0 0 6px' }}>
              Business Creates & Funds
            </h4>
            <p style={{ fontSize: '0.84rem', color: '#64748b', lineHeight: '1.5', margin: 0 }}>
              The business deposits USDT into their Fund Wallet and allocates a transparent budget to promote a Business Page post.
            </p>
          </div>

          {/* Step 2 */}
          <div className="eco-journey-card">
            <span className="eco-journey-step-badge eco-step-num--purple">02</span>
            <h4 style={{ fontSize: '1rem', fontWeight: '800', color: '#0f172a', margin: '0 0 6px' }}>
              Quality & Trust Moderation
            </h4>
            <p style={{ fontSize: '0.84rem', color: '#64748b', lineHeight: '1.5', margin: 0 }}>
              Administrators review the campaign content to ensure compliance, consumer protection, and ethical network marketing standards.
            </p>
          </div>

          {/* Step 3 */}
          <div className="eco-journey-card">
            <span className="eco-journey-step-badge eco-step-num--cyan">03</span>
            <h4 style={{ fontSize: '1rem', fontWeight: '800', color: '#0f172a', margin: '0 0 6px' }}>
              Delivered to Real Humans
            </h4>
            <p style={{ fontSize: '0.84rem', color: '#64748b', lineHeight: '1.5', margin: 0 }}>
              The approved campaign is broadcasted to mobile-verified members across the Socials feed and Watch Hub.
            </p>
          </div>

          {/* Step 4 */}
          <div className="eco-journey-card">
            <span className="eco-journey-step-badge eco-step-num--emerald">04</span>
            <h4 style={{ fontSize: '1rem', fontWeight: '800', color: '#0f172a', margin: '0 0 6px' }}>
              Genuine Member Engagement
            </h4>
            <p style={{ fontSize: '0.84rem', color: '#64748b', lineHeight: '1.5', margin: 0 }}>
              Interested members watch the presentation, engage with the brand, or inquire directly via WhatsApp.
            </p>
          </div>

          {/* Step 5 */}
          <div className="eco-journey-card">
            <span className="eco-journey-step-badge eco-step-num--purple">05</span>
            <h4 style={{ fontSize: '1rem', fontWeight: '800', color: '#0f172a', margin: '0 0 6px' }}>
              Reward Wallet Credit
            </h4>
            <p style={{ fontSize: '0.84rem', color: '#64748b', lineHeight: '1.5', margin: 0 }}>
              The ad budget is shared directly with qualifying members, depositing USDT into their dedicated Reward Wallet.
            </p>
          </div>

          {/* Step 6 */}
          <div className="eco-journey-card">
            <span className="eco-journey-step-badge eco-step-num--blue">06</span>
            <h4 style={{ fontSize: '1rem', fontWeight: '800', color: '#0f172a', margin: '0 0 6px' }}>
              Instant Web3 Cashout
            </h4>
            <p style={{ fontSize: '0.84rem', color: '#64748b', lineHeight: '1.5', margin: 0 }}>
              Members withdraw their accumulated reward balance directly to their personal BSC BEP-20 crypto wallet.
            </p>
          </div>
        </div>

        {/* ====================================================================
            5. DUAL-WALLET ARCHITECTURE EXPLAINED
            ==================================================================== */}
        <div style={{ textAlign: 'center', marginTop: '60px', marginBottom: '20px' }}>
          <div className="pub-badge pub-badge-emerald" style={{ margin: '0 auto 10px' }}>
            <Wallet className="w-4 h-4" />
            <span>Financial Integrity</span>
          </div>
          <h2 style={{ fontSize: 'clamp(1.5rem, 3vw, 2.2rem)', fontWeight: '800', color: '#0f172a', margin: '0 0 10px' }}>
            The Dual-Wallet Financial Architecture
          </h2>
          <p style={{ color: '#64748b', maxWidth: '720px', margin: '0 auto', fontSize: '1rem', lineHeight: '1.6' }}>
            Unlike murky platforms that mix user deposits with company funds, {platformName} operates on strict segregated wallet accounting.
          </p>
        </div>

        <div className="eco-wallet-split">
          {/* Fund Wallet Card */}
          <div className="eco-wallet-box eco-wallet-box--fund">
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '16px' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                <div style={{ width: '42px', height: '42px', borderRadius: '12px', background: '#eff6ff', color: '#2563eb', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                  <Coins className="w-5 h-5" />
                </div>
                <div>
                  <h3 style={{ fontSize: '1.2rem', fontWeight: '800', color: '#0f172a', margin: 0 }}>
                    Fund Wallet (P2P Wallet)
                  </h3>
                  <span style={{ fontSize: '0.78rem', color: '#2563eb', fontWeight: '700' }}>
                    Business Operations & Ad Budgeting
                  </span>
                </div>
              </div>
              <span style={{ fontSize: '0.75rem', fontWeight: '700', padding: '3px 8px', borderRadius: '8px', background: '#eff6ff', color: '#2563eb', border: '1px solid #bfdbfe' }}>
                0% Deposit Fee
              </span>
            </div>

            <p style={{ fontSize: '0.88rem', color: '#64748b', lineHeight: '1.6', margin: '0 0 16px' }}>
              Used by merchants, advertisers, and team leaders to manage business capital, fund advertising campaigns, and transfer funds to peers.
            </p>

            <ul style={{ listStyle: 'none', padding: 0, margin: '0 0 20px', display: 'flex', flexDirection: 'column', gap: '10px' }}>
              <li style={{ display: 'flex', alignItems: 'center', gap: '10px', fontSize: '0.86rem', color: '#334155' }}>
                <Check className="w-4 h-4 text-blue-600 shrink-0" />
                <span>Instant USDT deposits via automated Binance Smart Chain verification</span>
              </li>
              <li style={{ display: 'flex', alignItems: 'center', gap: '10px', fontSize: '0.86rem', color: '#334155' }}>
                <Check className="w-4 h-4 text-blue-600 shrink-0" />
                <span>Used to launch sponsored Business Page post & video campaigns</span>
              </li>
              <li style={{ display: 'flex', alignItems: 'center', gap: '10px', fontSize: '0.86rem', color: '#334155' }}>
                <Check className="w-4 h-4 text-blue-600 shrink-0" />
                <span>Zero fee full balance withdrawals if you choose not to run campaigns</span>
              </li>
            </ul>

            <div style={{ marginTop: 'auto', paddingTop: '16px', borderTop: '1px solid #f1f5f9' }}>
              <Link to="/member/fund-deposit" style={{ color: '#2563eb', fontWeight: '700', fontSize: '0.88rem', textDecoration: 'none', display: 'inline-flex', alignItems: 'center', gap: '6px' }}>
                <span>Fund Your Business Wallet</span>
                <ArrowRight className="w-4 h-4" />
              </Link>
            </div>
          </div>

          {/* Reward Wallet Card */}
          <div className="eco-wallet-box eco-wallet-box--reward">
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '16px' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                <div style={{ width: '42px', height: '42px', borderRadius: '12px', background: '#ecfdf5', color: '#059669', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                  <Award className="w-5 h-5" />
                </div>
                <div>
                  <h3 style={{ fontSize: '1.2rem', fontWeight: '800', color: '#0f172a', margin: 0 }}>
                    Reward Wallet
                  </h3>
                  <span style={{ fontSize: '0.78rem', color: '#059669', fontWeight: '700' }}>
                    Member Campaign Earnings
                  </span>
                </div>
              </div>
              <span style={{ fontSize: '0.75rem', fontWeight: '700', padding: '3px 8px', borderRadius: '8px', background: '#ecfdf5', color: '#059669', border: '1px solid #a7f3d0' }}>
                Cashable Web3
              </span>
            </div>

            <p style={{ fontSize: '0.88rem', color: '#64748b', lineHeight: '1.6', margin: '0 0 16px' }}>
              Exclusively dedicated to collecting your earned rewards from completed ad campaigns, video views, and verified introducer network growth.
            </p>

            <ul style={{ listStyle: 'none', padding: 0, margin: '0 0 20px', display: 'flex', flexDirection: 'column', gap: '10px' }}>
              <li style={{ display: 'flex', alignItems: 'center', gap: '10px', fontSize: '0.86rem', color: '#334155' }}>
                <Check className="w-4 h-4 text-emerald-600 shrink-0" />
                <span>Automated real-time credits upon satisfying campaign interaction rules</span>
              </li>
              <li style={{ display: 'flex', alignItems: 'center', gap: '10px', fontSize: '0.86rem', color: '#334155' }}>
                <Check className="w-4 h-4 text-emerald-600 shrink-0" />
                <span>Dynamic platform service charge deducted transparently on payout</span>
              </li>
              <li style={{ display: 'flex', alignItems: 'center', gap: '10px', fontSize: '0.86rem', color: '#334155' }}>
                <Check className="w-4 h-4 text-emerald-600 shrink-0" />
                <span>Instant request settlement in USDT (BEP-20) to your personal crypto wallet</span>
              </li>
            </ul>

            <div style={{ marginTop: 'auto', paddingTop: '16px', borderTop: '1px solid #f1f5f9' }}>
              <Link to="/member/withdrawal" style={{ color: '#059669', fontWeight: '700', fontSize: '0.88rem', textDecoration: 'none', display: 'inline-flex', alignItems: 'center', gap: '6px' }}>
                <span>Withdraw Rewards in USDT</span>
                <ArrowRight className="w-4 h-4" />
              </Link>
            </div>
          </div>
        </div>

        {/* Regulatory & No-Hype Declaration Banner */}
        <div className="eco-notice-banner">
          <ShieldCheck className="w-6 h-6 text-indigo-600 shrink-0 mt-0.5" />
          <div>
            <h4 style={{ fontSize: '0.95rem', fontWeight: '800', color: '#0f172a', margin: '0 0 4px' }}>
              Official Platform Integrity & Anti-Speculation Commitment
            </h4>
            <p style={{ fontSize: '0.84rem', color: '#475569', lineHeight: '1.6', margin: 0 }}>
              {platformName} is strictly a technology platform integrating social media, business promotion, and advertising revenue sharing. We do not provide financial investment advisory, daily ROI schemes, or speculative cryptocurrency trading. All distributed rewards originate strictly from verified advertising campaign budgets committed by business creators.
            </p>
          </div>
        </div>

        {/* ====================================================================
            6. PERSONA PERSPECTIVES: Who are you in the Ecosystem?
            ==================================================================== */}
        <div style={{ textAlign: 'center', marginTop: '60px', marginBottom: '20px' }}>
          <div className="pub-badge pub-badge-indigo" style={{ margin: '0 auto 10px' }}>
            <Users className="w-4 h-4" />
            <span>Tailored Experiences</span>
          </div>
          <h2 style={{ fontSize: 'clamp(1.5rem, 3vw, 2.2rem)', fontWeight: '800', color: '#0f172a', margin: '0 0 10px' }}>
            How {platformName} Works for You
          </h2>
          <p style={{ color: '#64748b', maxWidth: '720px', margin: '0 auto', fontSize: '1rem', lineHeight: '1.6' }}>
            Whether you lead a global direct sales team, run a commercial brand, or engage as an active member.
          </p>

          <div className="eco-tabs-nav" role="tablist">
            <button
              type="button"
              className={`eco-tab-btn ${activePersona === 'leaders' ? 'active' : ''}`}
              onClick={() => setActivePersona('leaders')}
            >
              <Users className="w-4 h-4" />
              <span>For Direct Sellers & Leaders</span>
            </button>
            <button
              type="button"
              className={`eco-tab-btn ${activePersona === 'business' ? 'active' : ''}`}
              onClick={() => setActivePersona('business')}
            >
              <Building2 className="w-4 h-4" />
              <span>For Business & Advertisers</span>
            </button>
            <button
              type="button"
              className={`eco-tab-btn ${activePersona === 'members' ? 'active' : ''}`}
              onClick={() => setActivePersona('members')}
            >
              <Award className="w-4 h-4" />
              <span>For Community Members</span>
            </button>
          </div>
        </div>

        {/* Persona 1: Leaders */}
        {activePersona === 'leaders' && (
          <div className="eco-suite-grid">
            <div className="eco-suite-card">
              <div style={{ width: '40px', height: '40px', borderRadius: '10px', background: '#eff6ff', color: '#2563eb', display: 'flex', alignItems: 'center', justifyContent: 'center', marginBottom: '14px' }}>
                <Share2 className="w-5 h-5" />
              </div>
              <h4 style={{ fontSize: '1.1rem', fontWeight: '800', color: '#0f172a', margin: '0 0 8px' }}>
                Zero Censorship Networking
              </h4>
              <p style={{ fontSize: '0.86rem', color: '#64748b', lineHeight: '1.6', margin: 0 }}>
                Share your leadership achievements, direct selling business presentations, and mentorship insights freely without fear of account suspensions.
              </p>
            </div>

            <div className="eco-suite-card">
              <div style={{ width: '40px', height: '40px', borderRadius: '10px', background: '#eff6ff', color: '#2563eb', display: 'flex', alignItems: 'center', justifyContent: 'center', marginBottom: '14px' }}>
                <TvMinimalPlay className="w-5 h-5" />
              </div>
              <h4 style={{ fontSize: '1.1rem', fontWeight: '800', color: '#0f172a', margin: '0 0 8px' }}>
                Watch Hub Masterclasses
              </h4>
              <p style={{ fontSize: '0.86rem', color: '#64748b', lineHeight: '1.6', margin: 0 }}>
                Publish long-form video training, opportunity overviews, and product testimonials into the dedicated Watch video hub for organic discovery.
              </p>
            </div>

            <div className="eco-suite-card">
              <div style={{ width: '40px', height: '40px', borderRadius: '10px', background: '#eff6ff', color: '#2563eb', display: 'flex', alignItems: 'center', justifyContent: 'center', marginBottom: '14px' }}>
                <Users className="w-5 h-5" />
              </div>
              <h4 style={{ fontSize: '1.1rem', fontWeight: '800', color: '#0f172a', margin: '0 0 8px' }}>
                Direct Introducer Network
              </h4>
              <p style={{ fontSize: '0.86rem', color: '#64748b', lineHeight: '1.6', margin: 0 }}>
                Build your verified personal referral network. When your introduced members engage in campaigns, earn multi-tier introducer rewards.
              </p>
            </div>
          </div>
        )}

        {/* Persona 2: Business & Advertisers */}
        {activePersona === 'business' && (
          <div className="eco-suite-grid">
            <div className="eco-suite-card">
              <div style={{ width: '40px', height: '40px', borderRadius: '10px', background: '#fffbeb', color: '#d97706', display: 'flex', alignItems: 'center', justifyContent: 'center', marginBottom: '14px' }}>
                <Building2 className="w-5 h-5" />
              </div>
              <h4 style={{ fontSize: '1.1rem', fontWeight: '800', color: '#0f172a', margin: '0 0 8px' }}>
                Business Directory Showcase
              </h4>
              <p style={{ fontSize: '0.86rem', color: '#64748b', lineHeight: '1.6', margin: 0 }}>
                Establish a documented Business Page with your logo, description, product links, and contact channels. Get indexed in the searchable global directory.
              </p>
            </div>

            <div className="eco-suite-card">
              <div style={{ width: '40px', height: '40px', borderRadius: '10px', background: '#fffbeb', color: '#d97706', display: 'flex', alignItems: 'center', justifyContent: 'center', marginBottom: '14px' }}>
                <Megaphone className="w-5 h-5" />
              </div>
              <h4 style={{ fontSize: '1.1rem', fontWeight: '800', color: '#0f172a', margin: '0 0 8px' }}>
                Guaranteed Real Human Views
              </h4>
              <p style={{ fontSize: '0.86rem', color: '#64748b', lineHeight: '1.6', margin: 0 }}>
                Every single viewer is verified via WhatsApp. Eliminate bot click fraud completely and enjoy transparent cost-per-engagement accounting.
              </p>
            </div>

            <div className="eco-suite-card">
              <div style={{ width: '40px', height: '40px', borderRadius: '10px', background: '#fffbeb', color: '#d97706', display: 'flex', alignItems: 'center', justifyContent: 'center', marginBottom: '14px' }}>
                <Coins className="w-5 h-5" />
              </div>
              <h4 style={{ fontSize: '1.1rem', fontWeight: '800', color: '#0f172a', margin: '0 0 8px' }}>
                Flexible Web3 Budgeting
              </h4>
              <p style={{ fontSize: '0.86rem', color: '#64748b', lineHeight: '1.6', margin: 0 }}>
                Fund your campaigns with USDT without high credit card processing fees or payment gateway lock-ins. Maintain total control over budget limits.
              </p>
            </div>
          </div>
        )}

        {/* Persona 3: Everyday Members */}
        {activePersona === 'members' && (
          <div className="eco-suite-grid">
            <div className="eco-suite-card">
              <div style={{ width: '40px', height: '40px', borderRadius: '10px', background: '#ecfdf5', color: '#059669', display: 'flex', alignItems: 'center', justifyContent: 'center', marginBottom: '14px' }}>
                <Award className="w-5 h-5" />
              </div>
              <h4 style={{ fontSize: '1.1rem', fontWeight: '800', color: '#0f172a', margin: '0 0 8px' }}>
                Earn for Authentic Engagement
              </h4>
              <p style={{ fontSize: '0.86rem', color: '#64748b', lineHeight: '1.6', margin: 0 }}>
                Browse verified sponsored posts, watch product presentations, and receive real USDT credited directly into your personal Reward Wallet.
              </p>
            </div>

            <div className="eco-suite-card">
              <div style={{ width: '40px', height: '40px', borderRadius: '10px', background: '#ecfdf5', color: '#059669', display: 'flex', alignItems: 'center', justifyContent: 'center', marginBottom: '14px' }}>
                <Search className="w-5 h-5" />
              </div>
              <h4 style={{ fontSize: '1.1rem', fontWeight: '800', color: '#0f172a', margin: '0 0 8px' }}>
                Discover Legitimate Opportunities
              </h4>
              <p style={{ fontSize: '0.86rem', color: '#64748b', lineHeight: '1.6', margin: 0 }}>
                Explore vetted direct selling companies, innovative products, and experienced mentors. Learn from genuine industry leaders.
              </p>
            </div>

            <div className="eco-suite-card">
              <div style={{ width: '40px', height: '40px', borderRadius: '10px', background: '#ecfdf5', color: '#059669', display: 'flex', alignItems: 'center', justifyContent: 'center', marginBottom: '14px' }}>
                <Wallet className="w-5 h-5" />
              </div>
              <h4 style={{ fontSize: '1.1rem', fontWeight: '800', color: '#0f172a', margin: '0 0 8px' }}>
                Instant Crypto Cashouts
              </h4>
              <p style={{ fontSize: '0.86rem', color: '#64748b', lineHeight: '1.6', margin: 0 }}>
                Withdraw your accumulated rewards directly to your private Binance Smart Chain (BEP-20) wallet with fast blockchain finality.
              </p>
            </div>
          </div>
        )}

        {/* ====================================================================
            7. FREQUENTLY ASKED QUESTIONS
            ==================================================================== */}
        <div style={{ textAlign: 'center', marginTop: '60px', marginBottom: '24px' }}>
          <div className="pub-badge pub-badge-indigo" style={{ margin: '0 auto 10px' }}>
            <Sparkles className="w-4 h-4" />
            <span>Clarity & Questions</span>
          </div>
          <h2 style={{ fontSize: 'clamp(1.5rem, 3vw, 2.2rem)', fontWeight: '800', color: '#0f172a', margin: '0 0 10px' }}>
            Frequently Asked Questions
          </h2>
          <p style={{ color: '#64748b', maxWidth: '720px', margin: '0 auto', fontSize: '1rem', lineHeight: '1.6' }}>
            Everything you need to understand the mechanism and motive of the {platformName} ecosystem.
          </p>
        </div>

        <div style={{ maxWidth: '860px', margin: '0 auto 60px' }}>
          {faqs.map((faq, idx) => (
            <div
              key={faq.q}
              className={`pub-faq-card ${openFaq === idx ? 'open' : ''}`}
              onClick={() => toggleFaq(idx)}
            >
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '16px' }}>
                <h3 style={{ fontSize: '1rem', fontWeight: '700', color: '#0f172a', margin: 0 }}>
                  {faq.q}
                </h3>
                <ChevronDown
                  className={`w-5 h-5 text-slate-400 transition-transform duration-200 shrink-0 ${
                    openFaq === idx ? 'rotate-180 text-blue-600' : ''
                  }`}
                />
              </div>

              {openFaq === idx && (
                <p style={{ fontSize: '0.9rem', color: '#475569', lineHeight: '1.65', margin: '14px 0 0', borderTop: '1px solid #f1f5f9', paddingTop: '12px' }}>
                  {faq.a}
                </p>
              )}
            </div>
          ))}
        </div>

        {/* ====================================================================
            8. FINAL CALL TO ACTION
            ==================================================================== */}
        <div
          style={{
            background: 'linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #1e1b4b 100%)',
            borderRadius: '24px',
            padding: '48px 32px',
            textAlign: 'center',
            color: '#ffffff',
            boxShadow: '0 20px 40px rgba(30, 27, 75, 0.25)',
            border: '1px solid rgba(255, 255, 255, 0.1)',
          }}
        >
          <div className="pub-badge" style={{ background: 'rgba(255, 255, 255, 0.15)', color: '#ffffff', margin: '0 auto 16px', border: '1px solid rgba(255, 255, 255, 0.2)' }}>
            <Sparkles className="w-4 h-4 text-amber-300" />
            <span>Join the Movement</span>
          </div>

          <h2 style={{ fontSize: 'clamp(1.6rem, 3.5vw, 2.4rem)', fontWeight: '800', margin: '0 0 14px', letterSpacing: '-0.02em' }}>
            Ready to Experience the Direct Selling Ecosystem Built for You?
          </h2>

          <p style={{ fontSize: '1.05rem', color: '#cbd5e1', maxWidth: '680px', margin: '0 auto 28px', lineHeight: '1.6' }}>
            Whether you want to build a thriving business brand or earn rewards for your genuine daily engagement, {platformName} is your dedicated home.
          </p>

          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '14px', flexWrap: 'wrap' }}>
            <Link
              to="/member/register"
              className="pub-btn pub-btn-lg"
              style={{ background: '#ffffff', color: '#1e1b4b', fontWeight: '800', boxShadow: '0 4px 14px rgba(0,0,0,0.15)' }}
            >
              <Sparkles className="w-5 h-5 text-indigo-600" />
              <span>Create Free Account</span>
              <ArrowRight className="w-5 h-5 text-indigo-600" />
            </Link>

            <Link
              to="/member/business-directory"
              className="pub-btn pub-btn-lg"
              style={{ background: 'rgba(255, 255, 255, 0.1)', color: '#ffffff', border: '1px solid rgba(255, 255, 255, 0.25)' }}
            >
              <Building2 className="w-5 h-5" />
              <span>Explore Business Directory</span>
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}

export default EcosystemPage;

