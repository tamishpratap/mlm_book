import { useState, useMemo, useContext, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { BrandingContext } from '../../context/brandingContextDef';
import {
  Briefcase,
  Building2,
  Sparkles,
  TrendingUp,
  Wallet,
  Users,
  ShieldCheck,
  CheckCircle2,
  ArrowRight,
  Coins,
  Award,
  Zap,
  Target,
  Layers,
  ChevronDown,
  TvMinimalPlay,
  UserCheck,
  BarChart3,
  BadgeCheck,
  Lock,
  Sliders,
  DollarSign,
  Rss,
  MessageSquare
} from 'lucide-react';

export function EcosystemPage() {
  const { siteName } = useContext(BrandingContext) || {};
  const platformName = siteName || 'MLM Book';

  useEffect(() => {
    document.title = `${platformName} Ecosystem — Business Pages & User Rewards Synergy`;
  }, [platformName]);

  // Perspective Filter Tab
  const [activeTab, setActiveTab] = useState('all'); // 'all' | 'business' | 'rewards'

  // Simulator State
  const [simMode, setSimMode] = useState('business'); // 'business' | 'user'

  // Business Simulator Controls
  const [adBudget, setAdBudget] = useState(150); // USDT

  // User Simulator Controls
  const [dailyAdViews, setDailyAdViews] = useState(25);
  const [campaignActions, setCampaignActions] = useState(6);

  // FAQ Accordion State
  const [openFaq, setOpenFaq] = useState(null);

  // Business Campaign Impact Calculations
  const businessMetrics = useMemo(() => {
    const verifiedHumanViews = Math.floor(adBudget * 18);
    const guaranteedEngagements = Math.floor(adBudget * 3.2);
    const estimatedDirectInquiries = Math.floor(adBudget * 0.45);
    const avgCostPerVerifiedClick = (adBudget / Math.max(guaranteedEngagements, 1)).toFixed(2);

    return {
      verifiedHumanViews: verifiedHumanViews.toLocaleString(),
      guaranteedEngagements: guaranteedEngagements.toLocaleString(),
      estimatedDirectInquiries: estimatedDirectInquiries.toLocaleString(),
      avgCostPerVerifiedClick,
    };
  }, [adBudget]);

  // User Earnings Calculations based on Campaign Engagements
  const userMetrics = useMemo(() => {
    const personalAdEarnings = dailyAdViews * 0.05 * 30; // ~$0.05 per ad
    const campaignActionEarnings = campaignActions * 0.25 * 30; // ~$0.25 per qualifying campaign action
    const total = personalAdEarnings + campaignActionEarnings;

    return {
      personal: Math.round(personalAdEarnings * 100) / 100,
      campaigns: Math.round(campaignActionEarnings * 100) / 100,
      total: Math.round(total * 100) / 100,
    };
  }, [dailyAdViews, campaignActions]);

  const faqs = [
    {
      q: 'How do Business Pages eliminate advertising bot fraud?',
      a: 'Unlike traditional ad networks where bot farms waste up to 40% of ad spend, MLM Book requires WhatsApp phone verification for active rewarded users. Advertisers only pay when a real, verified human member views, watches, or clicks their campaign.',
    },
    {
      q: 'How and when do users get rewarded for campaign interactions?',
      a: 'Whenever an eligible member views a sponsored post, watches a creator video campaign, or interacts with a verified business offer, reward credits are immediately and transparently deposited into their Reward Wallet in USDT.',
    },
    {
      q: 'What is the difference between Fund Wallet and Reward Wallet?',
      a: 'MLM Book uses a Dual-Wallet Architecture for total audit transparency. The Fund Wallet is used for business operations, depositing capital, and launching ad campaigns. The Reward Wallet collects all member earnings and is exclusively for cashable Web3 withdrawals.',
      id: 'ads',
      badge: 'Advertising Engine',
      title: '3. Peer-to-Peer Targeted Ad Engine',
      desc: 'A transparent advertising system where advertisers pay directly for verified real-user attention, and viewers earn real monetary rewards for their engagement.',
      icon: <TrendingUp className="w-6 h-6 text-blue-600" />,
      features: [
        {
          title: 'Precision Campaign Budgeting',
          desc: 'Set daily budgets, cost-per-click (CPC), and impression caps funded seamlessly through your internal Fund Wallet.'
        },
        {
          title: 'Anti-Fraud & View Verification',
          desc: 'Automated rate-limiting, IP verification, and unique interaction detection to prevent bot clicks and wasted ad spend.'
        },
        {
          title: 'People Engaged Analytics',
          desc: 'Real-time drilldown reports showing unique members who viewed, clicked, and engaged with each sponsored campaign.'
        },
        {
          title: 'Instant Value Distribution',
          desc: 'Every valid click or engagement immediately credits the member’s Reward account with zero delay.'
        }
      ]
    },
    {
      q: 'Can any business get the official Blue Verification Badge?',
      a: 'Any legitimate merchant, organization, or brand can apply. Administrators review official registration documents or business profiles to grant the Blue Check Badge, giving the company trust prominence in the Business Directory.',
    },
    {
      q: 'How do members qualify for campaign rewards?',
      a: 'To qualify for rewards, members must complete mobile/WhatsApp verification, browse active sponsored campaigns in the Socials or Watch feed, and complete the required engagement condition defined by the campaign.',
    },
    },
      id: 'financial',
      badge: 'Dual-Wallet System',
      title: '5. Dual-Wallet Financial Infrastructure',
      desc: 'Separating business operation funds from cashable member rewards provides institutional-grade accounting, total transparency, and seamless Web3 liquidity.',
      icon: <Wallet className="w-6 h-6 text-amber-600" />,
      features: [
        {
          title: 'Fund Wallet',
          desc: 'Used for depositing capital, funding business advertising campaigns, creating premium events, and member-to-member balance transfers.'
        },
        {
          title: 'Reward (Cashable Rewards)',
          desc: 'Receives all earnings from ad interactions, referral commissions, and rank bonuses. Cleanly isolated for audit and payout.'
        },
        {
          title: 'Web3 On-Chain Verification',
          desc: 'Members bind their personal EVM/crypto wallet address with security checks for direct, tamper-proof USDT payouts.'
        },
        {
          title: 'Instant Deposit & Automated Ledger',
          desc: 'Real-time crypto and gateway balance replenishment with verifiable ledger transaction histories.'
        }
      ]
    },
    {
      id: 'mlm',
      badge: 'Affiliate Architecture',
      title: '6. Multi-Level Referral & Network Tree',
      desc: 'A decentralized affiliate growth engine that rewards network builders with multi-tier generational bonuses and rank advancement pools.',
      icon: <GitBranch className="w-6 h-6 text-rose-600" />,
      features: [
        {
          title: 'Personalized Introducer Links',
          desc: 'Every member receives a unique referral URL and QR code with 100% free registration. New community signups automatically connect to your referral network.'
        },
        {
          title: 'Multi-Generation Team Commissions',
          desc: 'Earn overriding percentages on ad engagement and campaign activities generated across your multi-tier downline.'
        },
        {
          title: 'Rank Milestone Rules (RewardRankRule)',
          desc: 'Advance through achievement tiers based on team volume and active direct referrals to unlock higher payout ceilings.'
        },
        {
          title: 'Real-Time Downline Analytics',
          desc: 'Monitor team volume, active members, pending referrals, and historical commission splits from your member dashboard.'
        }
      ]
    }
  ];

  return (
    <div style={{ paddingBottom: '90px' }}>
      {/* ====================================================================
          1. HERO SECTION
          ==================================================================== */}
      <section className="eco-hero">
        <div className="pub-badge pub-badge-indigo" style={{ margin: '0 auto 12px' }}>
          <Sparkles className="w-4 h-4" />
          <span>The Unified Economic Engine</span>
          <span style={{ background: '#3b82f6', color: '#fff', padding: '2px 8px', borderRadius: '12px', fontSize: '0.72rem', fontWeight: '700' }}>
            Dual-Sided Marketplace
          </span>
        </div>

        <h1 className="eco-hero-title">
          Where <span className="pub-title-gradient">Verified Businesses Grow</span> & <br />
          Active Users <span style={{ color: '#059669' }}>Earn Real Rewards</span>
        </h1>

        <p className="eco-hero-desc">
          {platformName} bridges authentic commercial brands with real, verified human audiences.
          Businesses eliminate bot ad waste and gain genuine customers, while members earn direct,
          transparent USDT rewards for their daily digital engagement.
        </p>

        {/* Quick Action Navigation */}
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '14px', flexWrap: 'wrap' }}>
          <Link to="/member/register" className="pub-btn pub-btn-primary pub-btn-lg">
            <Sparkles className="w-5 h-5" />
            <span>Join & Start Earning</span>
            <ArrowRight className="w-5 h-5" />
          </Link>
          <a href="#simulator" className="pub-btn pub-btn-outline pub-btn-lg">
            <Sliders className="w-5 h-5" />
            <span>Try Campaign Simulator</span>
          </a>
        </div>

        {/* Metrics Strip */}
        <div className="eco-metrics-bar">
          <div className="eco-metric-item">
            <span className="eco-metric-val" style={{ color: '#7c3aed' }}>100% Real Humans</span>
            <span className="eco-metric-lbl">WhatsApp Phone Verified</span>
          </div>
          <div className="eco-metric-item">
            <span className="eco-metric-val" style={{ color: '#2563eb' }}>Blue Badge Pages</span>
            <span className="eco-metric-lbl">Documented Trust Profiles</span>
          </div>
          <div className="eco-metric-item">
            <span className="eco-metric-val" style={{ color: '#059669' }}>Zero Bot Waste</span>
            <span className="eco-metric-lbl">Pay-Per-Real-Engagement</span>
          </div>
          <div className="eco-metric-item">
            <span className="eco-metric-val" style={{ color: '#d97706' }}>Instant Web3 USDT</span>
            <span className="eco-metric-lbl">Cashable Dual-Wallet Payouts</span>
          </div>
        </div>
      </section>

      <div className="pub-container">
        {/* ====================================================================
            2. PERSPECTIVE SWITCHER TABS
            ==================================================================== */}
        <div className="eco-tabs-nav" role="tablist">
          <button
            type="button"
            className={`eco-tab-btn ${activeTab === 'all' ? 'active' : ''}`}
            onClick={() => setActiveTab('all')}
          >
            <Layers className="w-4 h-4" />
            <span>Complete Dual Engine</span>
          </button>
          <button
            type="button"
            className={`eco-tab-btn ${activeTab === 'business' ? 'active' : ''}`}
            onClick={() => setActiveTab('business')}
          >
            <Building2 className="w-4 h-4" />
            <span>For Businesses (Pages & Ads)</span>
          </button>
          <button
            type="button"
            className={`eco-tab-btn ${activeTab === 'rewards' ? 'active' : ''}`}
            onClick={() => setActiveTab('rewards')}
          >
            <Coins className="w-4 h-4" />
            <span>For Users (Get Rewards)</span>
          </button>
        </div>

        {/* ====================================================================
            3. THE 4-STEP SYNERGISTIC CYCLE (CLOSED-LOOP ENGINE)
            ==================================================================== */}
        <div style={{ textAlign: 'center', marginBottom: '20px' }}>
          <div className="pub-badge pub-badge-cyan" style={{ margin: '0 auto 10px' }}>
            <RefreshCwIcon className="w-4 h-4" />
            <span>How the Ecosystem Cycles</span>
          </div>
          <h2 style={{ fontSize: 'clamp(1.5rem, 3vw, 2.2rem)', fontWeight: '800', color: '#0f172a', margin: '0 0 10px' }}>
            The Closed-Loop Win-Win Engine
          </h2>
          <p style={{ color: '#64748b', fontSize: '0.98rem', maxWidth: '640px', margin: '0 auto' }}>
            How business promotional budgets transform directly into user income through genuine digital interactions.
          </p>
        </div>

        <div className="eco-flow-grid">
          {/* Step 1 */}
          <div className="eco-step-card">
            <div className="eco-step-num eco-step-num--blue">01</div>
            <h3 className="eco-step-title">Business Launches Verified Page & Campaign</h3>
            <p className="eco-step-desc">
              Merchants create their verified brand profile, showcase product inventory, and fund targeted pay-per-engagement ad campaigns via their Fund Wallet.
            </p>
            <div style={{ marginTop: 'auto', paddingTop: '16px', display: 'flex', alignItems: 'center', gap: '6px', fontSize: '0.78rem', color: '#2563eb', fontWeight: '600' }}>
              <Building2 className="w-3.5 h-3.5" />
              <span>Targeted CPC / View Caps</span>
            </div>
          </div>

          {/* Step 2 */}
          <div className="eco-step-card">
            <div className="eco-step-num eco-step-num--purple">02</div>
            <h3 className="eco-step-title">Anti-Fraud Precision Delivery</h3>
            <p className="eco-step-desc">
              Proprietary rate-limiting and WhatsApp verification algorithms route campaign placements only to authentic, phone-verified real members. Zero bots allowed.
            </p>
            <div style={{ marginTop: 'auto', paddingTop: '16px', display: 'flex', alignItems: 'center', gap: '6px', fontSize: '0.78rem', color: '#7c3aed', fontWeight: '600' }}>
              <ShieldCheck className="w-3.5 h-3.5" />
              <span>Zero Bot Fraud Guarantee</span>
            </div>
          </div>

          {/* Step 3 */}
          <div className="eco-step-card">
            <div className="eco-step-num eco-step-num--cyan">03</div>
            <h3 className="eco-step-title">Real Users Discover & Engage</h3>
            <p className="eco-step-desc">
              Active members view sponsored business posts, watch creator videos, explore company offerings, and inquire directly via integrated messaging channels.
            </p>
            <div style={{ marginTop: 'auto', paddingTop: '16px', display: 'flex', alignItems: 'center', gap: '6px', fontSize: '0.78rem', color: '#0891b2', fontWeight: '600' }}>
              <Users className="w-3.5 h-3.5" />
              <span>Authentic Brand Attention</span>
            </div>
          </div>

          {/* Step 4 */}
          <div className="eco-step-card">
            <div className="eco-step-num eco-step-num--emerald">04</div>
            <h3 className="eco-step-title">Instant Rewards Credited to User Wallet</h3>
            <p className="eco-step-desc">
              Qualified member interactions instantly transfer reward USDT straight to the member’s Reward Wallet, ready for immediate Web3 on-chain withdrawal.
            </p>
            <div style={{ marginTop: 'auto', paddingTop: '16px', display: 'flex', alignItems: 'center', gap: '6px', fontSize: '0.78rem', color: '#059669', fontWeight: '600' }}>
              <Wallet className="w-3.5 h-3.5" />
              <span>Direct Web3 Payout</span>
            </div>
          </div>
        </div>

        {/* ====================================================================
            4. BUSINESS PAGES SUITE ("According to Business Page")
            ==================================================================== */}
        {(activeTab === 'all' || activeTab === 'business') && (
          <section style={{ marginTop: '20px', marginBottom: '60px' }}>
            <div className="pub-section-header">
              <div className="pub-badge pub-badge-cyan">
                <Briefcase className="w-4 h-4" />
                <span>Enterprise & Commercial Suite</span>
              </div>
              <h2 className="pub-section-title">According to Business Pages: Built for Commercial Scale</h2>
              <p className="pub-section-desc">
                Everything brand owners, merchants, and entrepreneurs need to establish authentic digital authority and acquire loyal paying customers.
              </p>
            </div>

            <div className="eco-suite-grid">
              {/* Card 1 */}
              <div className="eco-suite-card eco-suite-card--business">
                <div className="eco-suite-icon-row">
                  <div className="pub-icon-wrapper pub-icon-blue" style={{ width: '46px', height: '46px', marginBottom: 0 }}>
                    <BadgeCheck className="w-5 h-5" />
                  </div>
                  <span className="eco-tag eco-tag--blue">Trust Authority</span>
                </div>
                <h3 style={{ fontSize: '1.2rem', fontWeight: '700', color: '#0f172a', margin: '0 0 10px' }}>
                  Official Blue Check Verification
                </h3>
                <p style={{ color: '#64748b', fontSize: '0.9rem', lineHeight: '1.55', margin: '0 0 16px' }}>
                  Boost brand confidence with official verification badges. Verified pages enjoy priority ranking in the public Business Directory and elevated customer response rates.
                </p>
                <ul className="pub-checklist" style={{ marginTop: 'auto' }}>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Document-backed verification review</span>
                  </li>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Top-tier placement in Business Directory</span>
                  </li>
                </ul>
              </div>

              {/* Card 2 */}
              <div className="eco-suite-card eco-suite-card--business">
                <div className="eco-suite-icon-row">
                  <div className="pub-icon-wrapper pub-icon-purple" style={{ width: '46px', height: '46px', marginBottom: 0 }}>
                    <Target className="w-5 h-5" />
                  </div>
                  <span className="eco-tag eco-tag--purple">Campaign Engine</span>
                </div>
                <h3 style={{ fontSize: '1.2rem', fontWeight: '700', color: '#0f172a', margin: '0 0 10px' }}>
                  Targeted Pay-Per-Engagement Ads
                </h3>
                <p style={{ color: '#64748b', fontSize: '0.9rem', lineHeight: '1.55', margin: '0 0 16px' }}>
                  Launch precision ad campaigns funded directly through your internal Fund Wallet. Set daily budget limits, cost-per-click (CPC), and maximum views with instant delivery.
                </p>
                <ul className="pub-checklist" style={{ marginTop: 'auto' }}>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Custom CPC, impressions & view caps</span>
                  </li>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Sponsored video reels and feed cards</span>
                  </li>
                </ul>
              </div>

              {/* Card 3 */}
              <div className="eco-suite-card eco-suite-card--business">
                <div className="eco-suite-icon-row">
                  <div className="pub-icon-wrapper pub-icon-emerald" style={{ width: '46px', height: '46px', marginBottom: 0 }}>
                    <Users className="w-5 h-5" />
                  </div>
                  <span className="eco-tag eco-tag--emerald">Collaboration</span>
                </div>
                <h3 style={{ fontSize: '1.2rem', fontWeight: '700', color: '#0f172a', margin: '0 0 10px' }}>
                  Granular Team Roles & Management
                </h3>
                <p style={{ color: '#64748b', fontSize: '0.9rem', lineHeight: '1.55', margin: '0 0 16px' }}>
                  Empower your sales, marketing, and customer support staff. Assign multi-user permissions (Owner, Manager, Moderator, Analyst) without sharing account passwords.
                </p>
                <ul className="pub-checklist" style={{ marginTop: 'auto' }}>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Role-based team permission delegations</span>
                  </li>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Secure activity logs for team actions</span>
                  </li>
                </ul>
              </div>

              {/* Card 4 */}
              <div className="eco-suite-card eco-suite-card--business">
                <div className="eco-suite-icon-row">
                  <div className="pub-icon-wrapper pub-icon-cyan" style={{ width: '46px', height: '46px', marginBottom: 0 }}>
                    <BarChart3 className="w-5 h-5" />
                  </div>
                  <span className="eco-tag eco-tag--blue">Audience Insights</span>
                </div>
                <h3 style={{ fontSize: '1.2rem', fontWeight: '700', color: '#0f172a', margin: '0 0 10px' }}>
                  "People Engaged" Analytics
                </h3>
                <p style={{ color: '#64748b', fontSize: '0.9rem', lineHeight: '1.55', margin: '0 0 16px' }}>
                  Gain unmatched transparency with drill-down audit logs. See the verified member profiles who viewed, clicked, and engaged with each sponsored campaign in real time.
                </p>
                <ul className="pub-checklist" style={{ marginTop: 'auto' }}>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Unique member engagement records</span>
                  </li>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Click-through-rate (CTR) transparency</span>
                  </li>
                </ul>
              </div>

              {/* Card 5 */}
              <div className="eco-suite-card eco-suite-card--business">
                <div className="eco-suite-icon-row">
                  <div className="pub-icon-wrapper pub-icon-amber" style={{ width: '46px', height: '46px', marginBottom: 0 }}>
                    <Building2 className="w-5 h-5" />
                  </div>
                  <span className="eco-tag eco-tag--amber">Directory Placement</span>
                </div>
                <h3 style={{ fontSize: '1.2rem', fontWeight: '700', color: '#0f172a', margin: '0 0 10px' }}>
                  Interactive Catalog & Storefront
                </h3>
                <p style={{ color: '#64748b', fontSize: '0.9rem', lineHeight: '1.55', margin: '0 0 16px' }}>
                  Showcase products, offerings, operating hours, customer contact links, and official social handles all under one branded hub on the public web.
                </p>
                <ul className="pub-checklist" style={{ marginTop: 'auto' }}>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Custom banners, bio, and catalog listings</span>
                  </li>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Direct WhatsApp inquiry button integration</span>
                  </li>
                </ul>
              </div>

              {/* Card 6 */}
              <div className="eco-suite-card eco-suite-card--business">
                <div className="eco-suite-icon-row">
                  <div className="pub-icon-wrapper pub-icon-emerald" style={{ width: '46px', height: '46px', marginBottom: 0 }}>
                    <MessageSquare className="w-5 h-5" />
                  </div>
                  <span className="eco-tag eco-tag--emerald">Reputation</span>
                </div>
                <h3 style={{ fontSize: '1.2rem', fontWeight: '700', color: '#0f172a', margin: '0 0 10px' }}>
                  Verified Reviews & Community Inquiries
                </h3>
                <p style={{ color: '#64748b', fontSize: '0.9rem', lineHeight: '1.55', margin: '0 0 16px' }}>
                  Collect organic 5-star reviews and upvotes from verified members. Respond directly to customer inquiries to cultivate lasting consumer trust and brand credibility.
                </p>
                <ul className="pub-checklist" style={{ marginTop: 'auto' }}>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Anti-spam review verification controls</span>
                  </li>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Owner reply threads & direct message leads</span>
                  </li>
                </ul>
              </div>
            </div>
          </section>
        )}

        {/* ====================================================================
            5. USER REWARDS SUITE ("Get Rewards for User")
            ==================================================================== */}
        {(activeTab === 'all' || activeTab === 'rewards') && (
          <section style={{ marginTop: '20px', marginBottom: '60px' }}>
            <div className="pub-section-header">
              <div className="pub-badge pub-badge-emerald">
                <Coins className="w-4 h-4" />
                <span>Member Experience & Rewards</span>
              </div>
              <h2 className="pub-section-title">Get Rewards for Users: Monetize Your Daily Attention</h2>
              <p className="pub-section-desc">
                Engage with authentic business campaigns, discover captivating media, join communities, and earn real, cashable USDT rewards credited straight to your Wallet.
              </p>
            </div>

            <div className="eco-suite-grid">
              {/* Card 1 */}
              <div className="eco-suite-card eco-suite-card--user">
                <div className="eco-suite-icon-row">
                  <div className="pub-icon-wrapper pub-icon-emerald" style={{ width: '46px', height: '46px', marginBottom: 0 }}>
                    <TrendingUp className="w-5 h-5" />
                  </div>
                  <span className="eco-tag eco-tag--emerald">Campaign Earnings</span>
                </div>
                <h3 style={{ fontSize: '1.2rem', fontWeight: '700', color: '#0f172a', margin: '0 0 10px' }}>
                  Qualifying Campaign Interaction Rewards
                </h3>
                <p style={{ color: '#64748b', fontSize: '0.9rem', lineHeight: '1.55', margin: '0 0 16px' }}>
                  Get paid cashable USDT whenever you view sponsored business posts or complete qualifying campaign actions. Reward credits are immediately updated in your transparent ledger.
                </p>
                <ul className="pub-checklist" style={{ marginTop: 'auto' }}>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Instant credit per verified campaign interaction</span>
                  </li>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Clear, transparent reward qualification rules</span>
                  </li>
                </ul>
              </div>

              {/* Card 2 - Replaced with Watch Video Hub & Discovery */}
              <div className="eco-suite-card eco-suite-card--user">
                <div className="eco-suite-icon-row">
                  <div className="pub-icon-wrapper pub-icon-indigo" style={{ width: '46px', height: '46px', marginBottom: 0 }}>
                    <TvMinimalPlay className="w-5 h-5" />
                  </div>
                  <span className="eco-tag eco-tag--blue">Media Discovery</span>
                </div>
                <h3 style={{ fontSize: '1.2rem', fontWeight: '700', color: '#0f172a', margin: '0 0 10px' }}>
                  Watch Video Hub & Creator Reels
                </h3>
                <p style={{ color: '#64748b', fontSize: '0.9rem', lineHeight: '1.55', margin: '0 0 16px' }}>
                  Discover captivating video content through the dedicated Watch Hub. Enjoy short-form creator reels, watch product video showcases, and engage with verified media.
                </p>
                <ul className="pub-checklist" style={{ marginTop: 'auto' }}>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Explore vertical video reels & publisher feeds</span>
                  </li>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Support content creators & brand showcases</span>
                  </li>
                </ul>
              </div>

              {/* Card 3 - Replaced with Community Groups & Networking */}
              <div className="eco-suite-card eco-suite-card--user">
                <div className="eco-suite-icon-row">
                  <div className="pub-icon-wrapper pub-icon-amber" style={{ width: '46px', height: '46px', marginBottom: 0 }}>
                    <Users className="w-5 h-5" />
                  </div>
                  <span className="eco-tag eco-tag--amber">Community Hubs</span>
                </div>
                <h3 style={{ fontSize: '1.2rem', fontWeight: '700', color: '#0f172a', margin: '0 0 10px' }}>
                  Community Discussion Hubs
                </h3>
                <p style={{ color: '#64748b', fontSize: '0.9rem', lineHeight: '1.55', margin: '0 0 16px' }}>
                  Join public or private interest-based communities. Connect with fellow members, share strategies, participate in discussions, and expand your professional circle.
                </p>
                <ul className="pub-checklist" style={{ marginTop: 'auto' }}>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Join niche groups around shared passions</span>
                  </li>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Community moderation and active timelines</span>
                  </li>
                </ul>
              </div>

              {/* Card 4 - Socials Feed & Ephemeral Stories */}
              <div className="eco-suite-card eco-suite-card--user">
                <div className="eco-suite-icon-row">
                  <div className="pub-icon-wrapper pub-icon-purple" style={{ width: '46px', height: '46px', marginBottom: 0 }}>
                    <Rss className="w-5 h-5" />
                  </div>
                  </div>
                  <span className="eco-tag eco-tag--emerald">Campaign Earnings</span>
                </div>
                <h3 style={{ fontSize: '1.2rem', fontWeight: '700', color: '#0f172a', margin: '0 0 10px' }}>
                  Qualifying Campaign Interaction Rewards
                </h3>
                <p style={{ color: '#64748b', fontSize: '0.9rem', lineHeight: '1.55', margin: '0 0 16px' }}>
                  Get paid cashable USDT whenever you view sponsored business posts or complete qualifying campaign actions. Reward credits are immediately updated in your transparent ledger.
                </p>
                <ul className="pub-checklist" style={{ marginTop: 'auto' }}>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Instant credit per verified campaign interaction</span>
                  </li>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Clear, transparent reward qualification rules</span>
                  </li>
                </ul>
              </div>

              {/* Card 2 - Replaced with Watch Video Hub & Discovery */}
              <div className="eco-suite-card eco-suite-card--user">
                <div className="eco-suite-icon-row">
                  <div className="pub-icon-wrapper pub-icon-indigo" style={{ width: '46px', height: '46px', marginBottom: 0 }}>
                    <TvMinimalPlay className="w-5 h-5" />
                  </div>
                  <span className="eco-tag eco-tag--blue">Media Discovery</span>
                </div>
                <h3 style={{ fontSize: '1.2rem', fontWeight: '700', color: '#0f172a', margin: '0 0 10px' }}>
                  Watch Video Hub & Creator Reels
                </h3>
                <p style={{ color: '#64748b', fontSize: '0.9rem', lineHeight: '1.55', margin: '0 0 16px' }}>
                  Discover captivating video content through the dedicated Watch Hub. Enjoy short-form creator reels, watch product video showcases, and engage with verified media.
                </p>
                <ul className="pub-checklist" style={{ marginTop: 'auto' }}>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Explore vertical video reels & publisher feeds</span>
                  </li>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Support content creators & brand showcases</span>
                  </li>
                </ul>
              </div>

              {/* Card 3 - Replaced with Community Groups & Networking */}
              <div className="eco-suite-card eco-suite-card--user">
                <div className="eco-suite-icon-row">
                  <div className="pub-icon-wrapper pub-icon-amber" style={{ width: '46px', height: '46px', marginBottom: 0 }}>
                    <Users className="w-5 h-5" />
                  </div>
                  <span className="eco-tag eco-tag--amber">Community Hubs</span>
                </div>
                <h3 style={{ fontSize: '1.2rem', fontWeight: '700', color: '#0f172a', margin: '0 0 10px' }}>
                  Community Discussion Hubs
                </h3>
                <p style={{ color: '#64748b', fontSize: '0.9rem', lineHeight: '1.55', margin: '0 0 16px' }}>
                  Join public or private interest-based communities. Connect with fellow members, share strategies, participate in discussions, and expand your professional circle.
                </p>
                <ul className="pub-checklist" style={{ marginTop: 'auto' }}>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Join niche groups around shared passions</span>
                  </li>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Community moderation and active timelines</span>
                  </li>
                </ul>
              </div>

              {/* Card 4 - Socials Feed & Ephemeral Stories */}
              <div className="eco-suite-card eco-suite-card--user">
                <div className="eco-suite-icon-row">
                  <div className="pub-icon-wrapper pub-icon-purple" style={{ width: '46px', height: '46px', marginBottom: 0 }}>
                    <Rss className="w-5 h-5" />
                  </div>
                  <span className="eco-tag eco-tag--purple">Social Network</span>
                </div>
                <h3 style={{ fontSize: '1.2rem', fontWeight: '700', color: '#0f172a', margin: '0 0 10px' }}>
                  Socials Feed & 24-Hour Stories
                </h3>
                <p style={{ color: '#64748b', fontSize: '0.9rem', lineHeight: '1.55', margin: '0 0 16px' }}>
                  Post updates, share photos, react with emojis, leave nested comments, and broadcast 24-hour visual stories to stay connected with friends and colleagues.
                </p>
                <ul className="pub-checklist" style={{ marginTop: 'auto' }}>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Interactive posts, reactions & nested threads</span>
                  </li>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>24-Hour disappearing visual stories</span>
                  </li>
                </ul>
              </div>

              {/* Card 5 - Dual-Wallet Accounting Isolation */}
              <div className="eco-suite-card eco-suite-card--user">
                <div className="eco-suite-icon-row">
                  <div className="pub-icon-wrapper pub-icon-blue" style={{ width: '46px', height: '46px', marginBottom: 0 }}>
                    <Lock className="w-5 h-5" />
                  </div>
                  <span className="eco-tag eco-tag--blue">Accounting Safety</span>
                </div>
                <h3 style={{ fontSize: '1.2rem', fontWeight: '700', color: '#0f172a', margin: '0 0 10px' }}>
                  Dual-Wallet Accounting Isolation
                </h3>
                <p style={{ color: '#64748b', fontSize: '0.9rem', lineHeight: '1.55', margin: '0 0 16px' }}>
                  Your operational capital (Fund Wallet) and cashable earnings (Reward Wallet) are strictly isolated. No commingling of funds, guaranteeing complete ledger transparency.
                </p>
                <ul className="pub-checklist" style={{ marginTop: 'auto' }}>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Tamper-proof internal accounting ledger</span>
                  </li>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Duplicate reward prevention rules</span>
                  </li>
                </ul>
              </div>

              {/* Card 6 - Instant Web3 USDT Withdrawals */}
              <div className="eco-suite-card eco-suite-card--user">
                <div className="eco-suite-icon-row">
                  <div className="pub-icon-wrapper pub-icon-emerald" style={{ width: '46px', height: '46px', marginBottom: 0 }}>
                    <Wallet className="w-5 h-5" />
                  </div>
                  <span className="eco-tag eco-tag--emerald">Web3 Liquidity</span>
                </div>
                <h3 style={{ fontSize: '1.2rem', fontWeight: '700', color: '#0f172a', margin: '0 0 10px' }}>
                  Instant Web3 USDT Withdrawals
                </h3>
                <p style={{ color: '#64748b', fontSize: '0.9rem', lineHeight: '1.55', margin: '0 0 16px' }}>
                  Bind your personal EVM crypto wallet address once with secure WhatsApp PIN confirmation. Withdraw your earned rewards in USDT directly to your on-chain wallet.
                </p>
                <ul className="pub-checklist" style={{ marginTop: 'auto' }}>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Decentralized crypto payout flexibility</span>
                  </li>
                  <li className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>Zero lockup periods on qualifying rewards</span>
                  </li>
                </ul>
              </div>
            </div>
          </section>
        )}

        {/* ====================================================================
            6. INTERACTIVE SIMULATOR / CALCULATOR (NO "ROI" WORDS)
            ==================================================================== */}
        <section id="simulator" className="eco-simulator">
          <div style={{ textAlign: 'center', marginBottom: '32px' }}>
            <div className="pub-badge pub-badge-indigo" style={{ margin: '0 auto 10px' }}>
              <Sliders className="w-4 h-4" />
              <span>Interactive Campaign & Rewards Simulator</span>
            </div>
            <h2 style={{ fontSize: 'clamp(1.4rem, 2.8vw, 2.1rem)', fontWeight: '800', color: '#0f172a', margin: '0 0 8px' }}>
              Estimate Your Value in the Ecosystem
            </h2>
            <p style={{ color: '#64748b', fontSize: '0.95rem', maxWidth: '580px', margin: '0 auto 20px' }}>
              Toggle between the Business Campaign view and the Member Reward view to see realistic performance metrics.
            </p>

            {/* Toggle Modes */}
            <div style={{ display: 'inline-flex', background: '#f1f5f9', padding: '4px', borderRadius: '12px', gap: '6px' }}>
              <button
                type="button"
                className={`pub-btn ${simMode === 'business' ? 'pub-btn-primary' : 'pub-btn-ghost'}`}
                style={{ padding: '8px 18px', fontSize: '0.88rem', borderRadius: '8px' }}
                onClick={() => setSimMode('business')}
              >
                <Building2 className="w-4 h-4" />
                <span>Business Campaign Impact</span>
              </button>
              <button
                type="button"
                className={`pub-btn ${simMode === 'user' ? 'pub-btn-accent' : 'pub-btn-ghost'}`}
                style={{ padding: '8px 18px', fontSize: '0.88rem', borderRadius: '8px' }}
                onClick={() => setSimMode('user')}
              >
                <Coins className="w-4 h-4" />
                <span>User Earnings View</span>
              </button>
            </div>
          </div>

          {/* Business Mode Simulator */}
          {simMode === 'business' ? (
            <div className="eco-sim-grid">
              <div className="eco-sim-controls">
                <div className="eco-sim-control-group">
                  <div className="eco-sim-control-header">
                    <span className="eco-sim-control-label">Monthly Campaign Ad Budget:</span>
                    <span className="eco-sim-control-val">${adBudget} USDT</span>
                  </div>
                  <input
                    type="range"
                    min="50"
                    max="1000"
                    step="25"
                    value={adBudget}
                    onChange={(e) => setAdBudget(Number(e.target.value))}
                    className="eco-slider"
                    aria-label="Ad Budget Slider"
                  />
                  <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.75rem', color: '#94a3b8' }}>
                    <span>$50 Startup</span>
                    <span>$500 Growth</span>
                    <span>$1,000 Scale</span>
                  </div>
                </div>

                <div style={{ background: '#f8fafc', padding: '16px', borderRadius: '12px', border: '1px solid #e2e8f0', fontSize: '0.88rem', color: '#475569' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#059669', fontWeight: '700', marginBottom: '6px' }}>
                    <ShieldCheck className="w-4 h-4" />
                    <span>100% WhatsApp Verified Humans</span>
                  </div>
                  Every interaction is tracked with unique device verification, guaranteeing your ad budget is never wasted on synthetic traffic or automated click farms.
                </div>
              </div>

              {/* Business Output Card */}
              <div className="eco-sim-results-card">
                <div style={{ fontSize: '0.85rem', fontWeight: '700', color: '#2563eb', textTransform: 'uppercase', letterSpacing: '0.06em' }}>
                  Projected Campaign Outreach
                </div>

                <div className="eco-sim-result-row">
                  <span className="eco-sim-result-lbl">
                    <UserCheck className="w-4 h-4 text-blue-600" />
                    <span>Real Human Impressions</span>
                  </span>
                  <span className="eco-sim-result-num">{businessMetrics.verifiedHumanViews}</span>
                </div>

                <div className="eco-sim-result-row">
                  <span className="eco-sim-result-lbl">
                    <Target className="w-4 h-4 text-purple-600" />
                    <span>Verified Clicks & Views</span>
                  </span>
                  <span className="eco-sim-result-num">{businessMetrics.guaranteedEngagements}</span>
                </div>

                <div className="eco-sim-result-row">
                  <span className="eco-sim-result-lbl">
                    <Building2 className="w-4 h-4 text-emerald-600" />
                    <span>Estimated Direct Inquiries</span>
                  </span>
                  <span className="eco-sim-result-num">{businessMetrics.estimatedDirectInquiries}</span>
                </div>

                <div className="eco-sim-result-row">
                  <span className="eco-sim-result-lbl">
                    <DollarSign className="w-4 h-4 text-amber-600" />
                    <span>Avg. Cost / Verified Click</span>
                  </span>
                  <span className="eco-sim-result-num" style={{ color: '#059669' }}>${businessMetrics.avgCostPerVerifiedClick}</span>
                </div>

                <div style={{ marginTop: '8px', textAlign: 'center' }}>
                  <Link to="/member/business-pages/create" className="pub-btn pub-btn-primary" style={{ width: '100%' }}>
                    <span>Launch Campaign from Business Page</span>
                    <ArrowRight className="w-4 h-4" />
                  </Link>
                </div>
              </div>
            </div>
          ) : (
            /* User Mode Simulator */
            <div className="eco-sim-grid">
              <div className="eco-sim-controls">
                {/* Control 1 */}
                <div className="eco-sim-control-group">
                  <div className="eco-sim-control-header">
                    <span className="eco-sim-control-label">Daily Sponsored Content Viewed:</span>
                    <span className="eco-sim-control-val">{dailyAdViews} views / day</span>
                  </div>
                  <input
                    type="range"
                    min="5"
                    max="60"
                    step="5"
                    value={dailyAdViews}
                    onChange={(e) => setDailyAdViews(Number(e.target.value))}
                    className="eco-slider"
                    aria-label="Daily Ads Slider"
                  />
                  <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.75rem', color: '#94a3b8' }}>
                    <span>5 views</span>
                    <span>30 views</span>
                    <span>60 views</span>
                  </div>
                </div>

                {/* Control 2 */}
                <div className="eco-sim-control-group">
                  <div className="eco-sim-control-header">
                    <span className="eco-sim-control-label">Qualifying Campaign Actions Completed:</span>
                    <span className="eco-sim-control-val">{campaignActions} actions / day</span>
                  </div>
                  <input
                    type="range"
                    min="1"
                    max="20"
                    step="1"
                    value={campaignActions}
                    onChange={(e) => setCampaignActions(Number(e.target.value))}
                    className="eco-slider"
                    aria-label="Campaign Actions Slider"
                  />
                  <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.75rem', color: '#94a3b8' }}>
                    <span>1 action</span>
                    <span>10 actions</span>
                    <span>20 actions</span>
                  </div>
                </div>

                <div style={{ background: '#f8fafc', padding: '16px', borderRadius: '12px', border: '1px solid #e2e8f0', fontSize: '0.88rem', color: '#475569' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#059669', fontWeight: '700', marginBottom: '6px' }}>
                    <ShieldCheck className="w-4 h-4" />
                    <span>Direct Wallet Crediting</span>
                  </div>
                  All qualified campaign rewards are credited directly into your personal Reward Wallet in USDT with zero lockup on approved balances.
                </div>
              </div>

              {/* User Output Card */}
              <div className="eco-sim-results-card" style={{ background: 'linear-gradient(145deg, #f8fafc 0%, #ecfdf5 100%)', borderColor: '#a7f3d0' }}>
                <div style={{ fontSize: '0.85rem', fontWeight: '700', color: '#059669', textTransform: 'uppercase', letterSpacing: '0.06em' }}>
                  Projected Monthly Reward Income
                </div>

                <div className="eco-sim-result-row">
                  <span className="eco-sim-result-lbl">
                    <TrendingUp className="w-4 h-4 text-emerald-600" />
                    <span>Sponsored Content Views</span>
                  </span>
                  <span className="eco-sim-result-num">${userMetrics.personal} USDT</span>
                </div>

                <div className="eco-sim-result-row">
                  <span className="eco-sim-result-lbl">
                    <Target className="w-4 h-4 text-indigo-600" />
                    <span>Campaign Actions Completed</span>
                  </span>
                  <span className="eco-sim-result-num">${userMetrics.campaigns} USDT</span>
                </div>

                <div className="eco-sim-result-row" style={{ paddingTop: '8px' }}>
                  <span className="eco-sim-result-lbl" style={{ fontWeight: '700', color: '#0f172a' }}>
                    <span>Estimated Monthly Total</span>
                  </span>
                  <span className="eco-sim-result-num eco-sim-result-num--highlight">${userMetrics.total} USDT</span>
                </div>

                <div style={{ marginTop: '8px', textAlign: 'center' }}>
                  <Link to="/member/register" className="pub-btn pub-btn-accent" style={{ width: '100%' }}>
                    <span>Create Member Account & Start Earning</span>
                    <ArrowRight className="w-4 h-4" />
                  </Link>
                </div>
              </div>
            </div>
          )}
        </section>

        {/* ====================================================================
            7. COMPARISON MATRIX (MLM BOOK VS TRADITIONAL SOCIAL NETWORKS)
            ==================================================================== */}
        <section style={{ marginBottom: '60px' }}>
          <div className="pub-section-header">
            <div className="pub-badge pub-badge-amber">
              <Award className="w-4 h-4" />
              <span>The Competitive Advantage</span>
            </div>
            <h2 className="pub-section-title">Why the {platformName} Ecosystem Wins</h2>
            <p className="pub-section-desc">
              Compare our verified human-centric model against traditional big tech advertising platforms.
            </p>
          </div>

          <div className="eco-table-card">
            <table className="eco-comp-table">
              <thead>
                <tr>
                  <th style={{ width: '25%' }}>Key Capability</th>
                  <th className="highlight-col" style={{ width: '40%' }}>
                    {platformName} (Business & Rewards)
                  </th>
                  <th style={{ width: '35%' }}>Legacy Big Tech Networks</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><strong>Ad Revenue Share</strong></td>
                  <td className="highlight-col">Distributed directly to verified users in USDT</td>
                  <td>100% kept by the tech platform</td>
                </tr>
                <tr>
                  <td><strong>Traffic Authenticity</strong></td>
                  <td className="highlight-col">100% WhatsApp verified real human members</td>
                  <td>High bot fraud, fake clicks and click farms</td>
                </tr>
                <tr>
                  <td><strong>Business Discovery</strong></td>
                  <td className="highlight-col">Permanent Business Directory & Category listings</td>
                  <td>Buried by algorithms unless you pay endless boosts</td>
                </tr>
                <tr>
                  <td><strong>Earnings Barrier</strong></td>
                  <td className="highlight-col">Earn cashable rewards from Day 1 for campaign actions</td>
                  <td>Requires 10,000+ followers or strict eligibility</td>
                </tr>
                <tr>
                  <td><strong>Payout Method</strong></td>
                  <td className="highlight-col">Instant Web3 crypto wallet withdrawals</td>
                  <td>60-90 days Net banking terms with high fee cuts</td>
                </tr>
                <tr>
                  <td><strong>Business Team Roles</strong></td>
                  <td className="highlight-col">Multi-user delegated roles without password sharing</td>
                  <td>Clunky, complicated enterprise managers</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        {/* ====================================================================
            8. TRUST & ANTI-FRAUD ARCHITECTURE
            ==================================================================== */}
        <section style={{ marginBottom: '60px' }}>
          <div className="pub-section-header">
            <div className="pub-badge pub-badge-cyan">
              <ShieldCheck className="w-4 h-4" />
              <span>Built Around Trust & Security</span>
            </div>
            <h2 className="pub-section-title">How Integrity is Built Into the Code</h2>
            <p className="pub-section-desc">
              Multi-layer verification protocols safeguard advertiser budgets and protect honest community earnings.
            </p>
          </div>

          <div className="pub-grid-3">
            <div className="pub-card" style={{ border: '1px solid #e2e8f0' }}>
              <div className="pub-icon-wrapper pub-icon-emerald" style={{ width: '46px', height: '46px' }}>
                <UserCheck className="w-5 h-5" />
              </div>
              <h3 style={{ fontSize: '1.15rem', fontWeight: '700', color: '#0f172a', marginBottom: '8px' }}>
                One-Person One-Account
              </h3>
              <p style={{ color: '#64748b', fontSize: '0.88rem', lineHeight: '1.55', margin: 0 }}>
                Every rewarding member must verify their mobile phone number via WhatsApp PIN verification. Multiple automated accounts and spoofing devices are immediately barred.
              </p>
            </div>

            <div className="pub-card" style={{ border: '1px solid #e2e8f0' }}>
              <div className="pub-icon-wrapper pub-icon-indigo" style={{ width: '46px', height: '46px' }}>
                <Lock className="w-5 h-5" />
              </div>
              <h3 style={{ fontSize: '1.15rem', fontWeight: '700', color: '#0f172a', marginBottom: '8px' }}>
                Dual-Wallet Isolation
              </h3>
              <p style={{ color: '#64748b', fontSize: '0.88rem', lineHeight: '1.55', margin: 0 }}>
                Business deposit funds (Fund Wallet) and cashable member rewards (Reward Wallet) are tracked in independent accounting ledgers to prevent accounting discrepancies.
              </p>
            </div>

            <div className="pub-card" style={{ border: '1px solid #e2e8f0' }}>
              <div className="pub-icon-wrapper pub-icon-amber" style={{ width: '46px', height: '46px' }}>
                <ShieldCheck className="w-5 h-5" />
              </div>
              <h3 style={{ fontSize: '1.15rem', fontWeight: '700', color: '#0f172a', marginBottom: '8px' }}>
                Verified Business Badges
              </h3>
              <p style={{ color: '#64748b', fontSize: '0.88rem', lineHeight: '1.55', margin: 0 }}>
                Official Blue Check Badges are granted only after administrative review of business documentation, ensuring our community interacts with verified, reputable merchants.
              </p>
              </p>
              <p style={{ color: '#475569', fontSize: '0.92rem', lineHeight: '1.6' }}>
                Scale an international network community with 0 registration fees. Leverage automated introducer links, real-time team connection tracking, and rank bonuses.
              </p>
              <ul className="pub-checklist">
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Multi-tier generational overrides</span>
                </li>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Real-time team connection dashboards</span>
                </li>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Monthly rank pool profit splits</span>
                </li>
              </ul>
            </div>
          </div>
        </section>

        {/* ====================================================================
            9. FAQ ACCORDION
            ==================================================================== */}
        <section style={{ marginBottom: '60px' }}>
          <div className="pub-section-header">
            <div className="pub-badge pub-badge-purple">
              <span>FAQ</span>
            </div>
            <h2 className="pub-section-title">Frequently Asked Questions</h2>
            <p className="pub-section-desc">
              Common questions from business owners and earning members about our ecosystem.
            </p>
          </div>

          <div style={{ maxWidth: '820px', margin: '0 auto' }}>
            {faqs.map((faq, idx) => {
              const isOpen = openFaq === idx;
              return (
                <div key={idx} className="pub-faq-item">
                  <button
                    type="button"
                    className="pub-faq-trigger"
                    onClick={() => setOpenFaq(isOpen ? null : idx)}
                    aria-expanded={isOpen}
                  >
                    <span>{faq.q}</span>
                    <ChevronDown
                      className="w-5 h-5"
                      style={{
                        transform: isOpen ? 'rotate(180deg)' : 'rotate(0)',
                        transition: 'transform 0.2s ease',
                        color: '#64748b',
                      }}
                    />
                  </button>
                  {isOpen && <div className="pub-faq-body">{faq.a}</div>}
                </div>
              );
            })}
          </div>
        </section>

        {/* ====================================================================
            10. DUAL ACTION CONVERSION BANNER
            ==================================================================== */}
        <div className="eco-dual-cta">
          {/* Business Call to Action */}
          <div className="eco-cta-box eco-cta-box--business">
            <div className="pub-badge pub-badge-blue" style={{ width: 'fit-content', marginBottom: '14px' }}>
              <Building2 className="w-3.5 h-3.5" />
              <span>For Business Brands</span>
            </div>
            <h3 style={{ fontSize: '1.45rem', fontWeight: '800', color: '#0f172a', margin: '0 0 10px' }}>
              Ready to Grow with 100% Real Human Reach?
            </h3>
            <p style={{ color: '#475569', fontSize: '0.92rem', lineHeight: '1.6', margin: '0 0 24px' }}>
              Create your official verified Business Page, showcase your products and services, and launch high-performing targeted ad campaigns today.
            </p>
            <div style={{ marginTop: 'auto' }}>
              <Link to="/member/business-pages/create" className="pub-btn pub-btn-primary" style={{ width: '100%' }}>
                <span>Create Your Business Page</span>
                <ArrowRight className="w-4 h-4" />
              </Link>
            </div>
          </div>

          {/* User Call to Action */}
          <div className="eco-cta-box eco-cta-box--user">
            <div className="pub-badge pub-badge-emerald" style={{ width: 'fit-content', marginBottom: '14px' }}>
              <Coins className="w-3.5 h-3.5" />
              <span>For Earning Members</span>
            </div>
            <h3 style={{ fontSize: '1.45rem', fontWeight: '800', color: '#0f172a', margin: '0 0 10px' }}>
              Ready to Monetize Your Social Attention?
            </h3>
            <p style={{ color: '#475569', fontSize: '0.92rem', lineHeight: '1.6', margin: '0 0 24px' }}>
              Join MLM Book for free, view sponsored business posts, watch creator videos, explore vibrant communities, and withdraw USDT rewards directly.
            </p>
            <div style={{ marginTop: 'auto' }}>
              <Link to="/member/register" className="pub-btn pub-btn-accent" style={{ width: '100%' }}>
                <span>Register & Start Earning</span>
                <Sparkles className="w-4 h-4" />
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

// Helper icon component
function RefreshCwIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
      <path d="M3 3v5h5" />
      <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16" />
      <path d="M16 21h5v-5" />
    </svg>
  );
}

export default EcosystemPage;
