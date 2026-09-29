import { useContext } from 'react';
import { Link } from 'react-router-dom';
import { BrandingContext } from '../../context/brandingContextDef';
import {
  Layers,
  Globe,
  Briefcase,
  TrendingUp,
  Users,
  Wallet,
  ShieldCheck,
  CheckCircle2,
  ArrowRight,
  Sparkles,
  GitBranch,
  Award
} from 'lucide-react';

export function EcosystemPage() {
  const { siteName } = useContext(BrandingContext) || {};
  const platformName = siteName || 'MLM Book';

  const ecosystemModules = [
    {
      id: 'social',
      badge: 'Interactive Media',
      title: '1. Social Network & Creator Hub',
      desc: 'A full-featured modern social media experience designed to drive organic reach, authentic connections, and viral content distribution without invasive algorithmic censorship.',
      icon: <Globe className="w-6 h-6 text-indigo-600" />,
      features: [
        {
          title: 'Feed & Multimedia Posts',
          desc: 'Share high-resolution images, rich text, external links, and multimedia content with rich emoji reactions, nested comments, and thread sharing.'
        },
        {
          title: '24-Hour Stories',
          desc: 'Publish ephemeral visual stories with tap-through navigation, view counts, and instant story replies straight to your direct messages.'
        },
        {
          title: 'Watch & Short-Form Video',
          desc: 'Explore a dedicated vertical video feed for creator reels, product teasers, and educational video snippets.'
        },
        {
          title: 'Real-Time Direct Messaging',
          desc: 'One-on-one direct chat allowing seamless conversation, media sharing, and business lead inquiries directly between members.'
        }
      ]
    },
    {
      id: 'business',
      badge: 'Enterprise Suite',
      title: '2. Verified Business Pages & Directory',
      desc: 'Empowering local merchants, global brands, and independent entrepreneurs to establish an official, trust-backed commercial identity.',
      icon: <Briefcase className="w-6 h-6 text-emerald-600" />,
      features: [
        {
          title: 'Official Blue Verification Badge',
          desc: 'Admins verify business documentation to grant high-trust verification badges that boost consumer credibility and directory placement.'
        },
        {
          title: 'Collaborative Team Management',
          desc: 'Assign multiple team members with granular permission roles (Owner, Manager, Moderator, Analyst) without sharing passwords.'
        },
        {
          title: 'Transparent Customer Reviews & Upvoting',
          desc: 'Genuine, verified customer reviews with 5-star ratings, community upvoting, and official owner response threads.'
        },
        {
          title: 'Categorized Business Directory',
          desc: 'Publicly searchable company listings organized by industry categories, location, and verified badges.'
        }
      ]
    },
    {
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
          desc: 'Every valid click or engagement immediately credits the member’s Reward Wallet with zero delay.'
        }
      ]
    },
    {
      id: 'communities',
      badge: 'Collaborative Groups',
      title: '4. Communities & Discussion Hubs',
      desc: 'Vibrant interest-based groups where members build micro-networks, organize masterminds, discuss strategies, and share exclusive content.',
      icon: <Users className="w-6 h-6 text-purple-600" />,
      features: [
        {
          title: 'Public & Private Communities',
          desc: 'Create open topic clubs or private invite-only VIP mastermind chambers with custom join requirements.'
        },
        {
          title: 'Community Moderation Suite',
          desc: 'Empower community leaders with member bans, mutes, warning strikes, and complete audit logging.'
        },
        {
          title: 'Invite Links & QR Sharing',
          desc: 'Grow community membership quickly using dedicated invite links and track which members referred newcomers.'
        },
        {
          title: 'Community Activity Timelines',
          desc: 'Chronological activity dashboards to track engagement metrics, top contributors, and community growth trends.'
        }
      ]
    },
    {
      id: 'financial',
      badge: 'Dual-Wallet System',
      title: '5. Dual-Wallet Financial Infrastructure',
      desc: 'Separating business operation funds from cashable member rewards provides institutional-grade accounting, total transparency, and seamless Web3 liquidity.',
      icon: <Wallet className="w-6 h-6 text-amber-600" />,
      features: [
        {
          title: 'Fund Wallet (P2P Operations)',
          desc: 'Used for depositing capital, funding business advertising campaigns, creating premium events, and peer-to-peer balance transfers.'
        },
        {
          title: 'Reward Wallet (Cashable Income)',
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
          desc: 'Every member receives a unique sponsor URL and QR code. New signups automatically nest under your genealogy tree.'
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
    <div style={{ padding: '60px 0 80px' }}>
      <div className="pub-container">
        {/* ====================================================================
            1. HERO
            ==================================================================== */}
        <div className="pub-section-header" style={{ marginBottom: '64px' }}>
          <div className="pub-badge pub-badge-cyan">
            <span className="pub-badge-dot"></span>
            <span>Comprehensive System Breakdown</span>
          </div>
          <h1 className="pub-title-hero" style={{ fontSize: 'clamp(2.25rem, 4.5vw, 3.5rem)' }}>
            The Complete <span className="pub-title-gradient">{platformName} Ecosystem</span>
          </h1>
          <p className="pub-desc-hero" style={{ margin: '0 auto' }}>
            Discover how our six core technological pillars synchronize to empower creators, business brands, advertisers, and community networkers in a unified, reward-driven digital economy.
          </p>
        </div>

        {/* ====================================================================
            2. ECOSYSTEM ARCHITECTURE PILLARS
            ==================================================================== */}
        <div style={{ display: 'flex', flexDirection: 'column', gap: '32px' }}>
          {ecosystemModules.map((mod) => (
            <div 
              key={mod.id}
              className="pub-card" 
              style={{
                border: '1px solid #e2e8f0',
                background: '#ffffff',
                padding: '40px',
                boxShadow: 'var(--pub-shadow-md)'
              }}
            >
              <div className="pub-card-glow-line"></div>
              
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '16px', marginBottom: '20px' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '16px' }}>
                  <div className="pub-icon-wrapper" style={{ width: '50px', height: '50px', marginBottom: 0, background: '#f8fafc', border: '1px solid #e2e8f0' }}>
                    {mod.icon}
                  </div>
                  <div>
                    <span className="pub-badge" style={{ marginBottom: '6px', fontSize: '0.72rem', padding: '4px 10px' }}>
                      {mod.badge}
                    </span>
                    <h2 style={{ fontSize: '1.45rem', fontWeight: '800', color: '#0f172a', margin: 0 }}>
                      {mod.title}
                    </h2>
                  </div>
                </div>
              </div>

              <p style={{ color: '#475569', fontSize: '1.02rem', lineHeight: '1.6', marginBottom: '28px', maxWidth: '880px' }}>
                {mod.desc}
              </p>

              {/* Sub-Features 4-Column Grid */}
              <div className="pub-grid-4">
                {mod.features.map((feat, fIdx) => (
                  <div 
                    key={fIdx}
                    style={{
                      background: '#f8fafc',
                      border: '1px solid #e2e8f0',
                      borderRadius: '14px',
                      padding: '20px',
                      transition: 'all 0.2s ease'
                    }}
                  >
                    <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '10px' }}>
                      <CheckCircle2 className="w-4 h-4 text-emerald-600 flex-shrink-0" />
                      <h3 style={{ fontSize: '0.95rem', fontWeight: '700', color: '#0f172a', margin: 0 }}>
                        {feat.title}
                      </h3>
                    </div>
                    <p style={{ color: '#64748b', fontSize: '0.85rem', lineHeight: '1.5', margin: 0 }}>
                      {feat.desc}
                    </p>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>

        {/* ====================================================================
            3. PERSONA VALUE MATRIX
            ==================================================================== */}
        <section style={{ marginTop: '80px' }}>
          <div className="pub-section-header">
            <div className="pub-badge pub-badge-emerald">
              <span className="pub-badge-dot"></span>
              <span>Value Propositions</span>
            </div>
            <h2 className="pub-section-title">How You Benefit in This Ecosystem</h2>
            <p className="pub-section-desc">
              Tailored capabilities built for every participant in our decentralized platform.
            </p>
          </div>

          <div className="pub-grid-3">
            {/* For Users */}
            <div className="pub-card">
              <div className="pub-icon-wrapper pub-icon-indigo">
                <Users className="w-6 h-6" />
              </div>
              <h3 style={{ fontSize: '1.25rem', fontWeight: '800', color: '#0f172a', marginBottom: '12px' }}>
                For Creators & Members
              </h3>
              <p style={{ color: '#475569', fontSize: '0.92rem', lineHeight: '1.6' }}>
                Monetize your social time. View sponsored business posts, watch creator video clips, grow your personal brand, and earn cashable rewards for your digital attention.
              </p>
              <ul className="pub-checklist">
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Daily engagement rewards</span>
                </li>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>No subscription or hidden fees</span>
                </li>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Direct Web3 USDT withdrawals</span>
                </li>
              </ul>
            </div>

            {/* For Businesses */}
            <div className="pub-card">
              <div className="pub-icon-wrapper pub-icon-emerald">
                <Briefcase className="w-6 h-6" />
              </div>
              <h3 style={{ fontSize: '1.25rem', fontWeight: '800', color: '#0f172a', marginBottom: '12px' }}>
                For Brands & Merchants
              </h3>
              <p style={{ color: '#475569', fontSize: '0.92rem', lineHeight: '1.6' }}>
                Acquire real customers, not bots. Build verified business authority, manage sales teams with role permissions, and run hyper-targeted pay-per-engagement campaigns.
              </p>
              <ul className="pub-checklist">
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Verified blue check trust badge</span>
                </li>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Zero bot waste click verification</span>
                </li>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Dedicated customer direct inbox</span>
                </li>
              </ul>
            </div>

            {/* For Affiliate Leaders */}
            <div className="pub-card">
              <div className="pub-icon-wrapper pub-icon-amber">
                <Award className="w-6 h-6" />
              </div>
              <h3 style={{ fontSize: '1.25rem', fontWeight: '800', color: '#0f172a', marginBottom: '12px' }}>
                For Network & MLM Leaders
              </h3>
              <p style={{ color: '#475569', fontSize: '0.92rem', lineHeight: '1.6' }}>
                Scale an international network organization. Leverage automated introducer trees, real-time downline genealogy tracking, and rank pool bonuses.
              </p>
              <ul className="pub-checklist">
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Multi-tier generational overrides</span>
                </li>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Real-time genealogy tree charts</span>
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
            4. CALL TO ACTION
            ==================================================================== */}
        <div style={{ marginTop: '70px', textAlign: 'center' }}>
          <h2 style={{ fontSize: '1.75rem', fontWeight: '800', color: '#0f172a', marginBottom: '14px' }}>
            Experience the Complete Ecosystem Today
          </h2>
          <p style={{ color: '#64748b', fontSize: '1.05rem', marginBottom: '28px', maxWidth: '540px', margin: '0 auto 28px' }}>
            Join our rapidly expanding community and unlock the future of social networking and decentralized rewards.
          </p>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '14px', flexWrap: 'wrap' }}>
            <Link to="/member/register" className="pub-btn pub-btn-primary pub-btn-lg">
              <Sparkles className="w-5 h-5" />
              <span>Create Your Account</span>
              <ArrowRight className="w-5 h-5" />
            </Link>
            <Link to="/rewards" className="pub-btn pub-btn-outline pub-btn-lg">
              <span>View Reward Breakdown</span>
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}

export default EcosystemPage;
