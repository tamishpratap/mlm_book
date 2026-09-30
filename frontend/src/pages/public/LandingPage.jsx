import { useContext, useState } from 'react';
import { Link } from 'react-router-dom';
import { BrandingContext } from '../../context/brandingContextDef';
import { AuthContext } from '../../context/AuthContext';
import {
  Globe,
  Sparkles,
  ArrowRight,
  ShieldCheck,
  Zap,
  Users,
  Briefcase,
  TrendingUp,
  Wallet,
  CheckCircle2,
  XCircle,
  ChevronRight,
  Layers,
  Gift,
  Target,
  BarChart3,
  Flame,
  Coins,
  Check,
  X,
  MessageSquare,
  Award,
  PlayCircle,
  TvMinimalPlay,
  Smartphone,
  UserCheck,
  CheckSquare,
  Lock,
  Building2,
  Sliders,
  Info,
  UserPlus,
  Eye,
  RefreshCw,
  ExternalLink,
  DollarSign
} from 'lucide-react';

export function LandingPage() {
  const { siteName } = useContext(BrandingContext) || {};
  const { isAuthenticated } = useContext(AuthContext) || {};

  const platformName = siteName || 'MLM Book';

  // State for the interactive member dashboard preview
  const [activePortalTab, setActivePortalTab] = useState('feed');

  return (
    <div>
      {/* ====================================================================
          1. HERO SECTION (Clean, High-Readability Hero & Floating Preview)
          ==================================================================== */}
      <section className="pub-hero-section">
        {/* Calm Ambient Background - Zero Distraction For Text Readability */}
        <div className="pub-hero-ambient-bg" aria-hidden="true">
          <div className="pub-ambient-glow-right"></div>
          <div className="pub-ambient-soft-mesh"></div>
        </div>

        <div className="pub-container">
          <div className="pub-hero-grid">
            {/* Left Content */}
            <div>
              <div className="pub-badge">
                <span className="pub-badge-dot"></span>
                <span>The Premier Social Network for Network Marketers & Web3 Commerce</span>
              </div>

              <h1 className="pub-title-hero">
                Connect, Collaborate & <br />
                <span className="pub-title-gradient">Monetize Your Network</span>
              </h1>

              <p className="pub-desc-hero">
                Welcome to <strong style={{ color: '#0f172a' }}>{platformName}</strong> — an exclusive, all-in-one ecosystem uniting verified direct selling leaders, enterprise business directories, peer-to-peer advertising, and an automated multi-tier crypto reward engine.
              </p>

              <div className="pub-hero-actions">
                {isAuthenticated ? (
                  <Link to="/member/home" className="pub-btn pub-btn-primary pub-btn-lg">
                    <span>Go to Member Dashboard</span>
                    <ArrowRight className="w-5 h-5" />
                  </Link>
                ) : (
                  <>
                    <Link to="/member/register" className="pub-btn pub-btn-primary pub-btn-lg">
                      <Sparkles className="w-5 h-5" />
                      <span>Join Now & Register Free</span>
                      <ArrowRight className="w-5 h-5" />
                    </Link>
                    <Link to="/member/login" className="pub-btn pub-btn-outline pub-btn-lg">
                      <span>Member Login</span>
                    </Link>
                  </>
                )}
                <Link to="/rewards" className="pub-btn pub-btn-ghost pub-btn-lg">
                  <Coins className="w-5 h-5" />
                  <span>View Reward System</span>
                </Link>
              </div>

              {/* Trust Badges */}
              <div style={{ display: 'flex', alignItems: 'center', gap: '24px', flexWrap: 'wrap', paddingTop: '10px' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#475569', fontSize: '0.88rem' }}>
                  <ShieldCheck className="w-4 h-4 text-emerald-600" />
                  <span style={{ fontWeight: '500' }}>Verified Business Pages</span>
                </div>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#475569', fontSize: '0.88rem' }}>
                  <Zap className="w-4 h-4 text-blue-600" />
                  <span style={{ fontWeight: '500' }}>Instant Ad Reward Payouts</span>
                </div>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#475569', fontSize: '0.88rem' }}>
                  <Users className="w-4 h-4 text-indigo-600" />
                  <span style={{ fontWeight: '500' }}>Direct Selling Community</span>
                </div>
              </div>
            </div>

            {/* Right Visual Floating Mockup */}
            <div className="pub-hero-mockup-wrapper">
              <div className="pub-floating-badge pub-floating-badge-top">
                <span className="pub-pulse-indicator"></span>
                <span>Active Global Networkers Live</span>
              </div>

              <div className="pub-floating-badge pub-floating-badge-bottom">
                <Sparkles className="w-4 h-4 text-amber-500" />
                <span style={{ fontWeight: '600', color: '#0f172a' }}>USDT Rewards Streaming</span>
              </div>

              <div className="pub-mockup-card">
                <div className="pub-mockup-header">
                  <div className="pub-mockup-dots">
                    <span className="pub-mockup-dot red"></span>
                    <span className="pub-mockup-dot yellow"></span>
                    <span className="pub-mockup-dot green"></span>
                  </div>
                  <div className="pub-mockup-title">mlmbook.com/dashboard</div>
                </div>

                <div className="pub-mockup-body">
                  <div className="pub-mockup-user-bar">
                    <div className="pub-mockup-avatar">
                      <span>JD</span>
                    </div>
                    <div>
                      <div style={{ fontSize: '0.95rem', fontWeight: '700', color: '#0f172a' }}>Global Top Leader</div>
                      <div style={{ fontSize: '0.78rem', color: '#64748b' }}>Verified Diamond Executive</div>
                    </div>
                    <div style={{ marginLeft: 'auto' }}>
                      <span className="pub-status-pill green">Active Rank #4</span>
                    </div>
                  </div>

                  <div className="pub-mockup-stat-row">
                    <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                      <div className="pub-icon-wrapper pub-icon-emerald" style={{ width: '40px', height: '40px', marginBottom: 0, borderRadius: '10px' }}>
                        <Wallet className="w-4 h-4" />
                      </div>
                      <div>
                        <div style={{ fontSize: '0.85rem', fontWeight: '700', color: '#0f172a' }}>Reward Balance</div>
                        <div style={{ fontSize: '0.75rem', color: '#64748b' }}>Available for Web3 Payout</div>
                      </div>
                    </div>
                    <span style={{ fontSize: '1.1rem', fontWeight: '800', color: '#059669' }}>1,480.50 USDT</span>
                  </div>

                  <div className="pub-mockup-stat-row">
                    <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                      <div className="pub-icon-wrapper pub-icon-cyan" style={{ width: '40px', height: '40px', marginBottom: 0, borderRadius: '10px' }}>
                        <TrendingUp className="w-4 h-4" />
                      </div>
                      <div>
                        <div style={{ fontSize: '0.85rem', fontWeight: '700', color: '#0f172a' }}>Active Ad Campaigns</div>
                        <div style={{ fontSize: '0.75rem', color: '#64748b' }}>High-CTR Audience Outreach</div>
                      </div>
                    </div>
                    <span style={{ fontSize: '0.9rem', fontWeight: '700', color: '#0284c7' }}>99.4% Delivery</span>
                  </div>

                  <div className="pub-mockup-stat-row">
                    <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                      <div className="pub-icon-wrapper pub-icon-purple" style={{ width: '40px', height: '40px', marginBottom: 0, borderRadius: '10px' }}>
                        <Users className="w-4 h-4" />
                      </div>
                      <div>
                        <div style={{ fontSize: '0.85rem', fontWeight: '700', color: '#0f172a' }}>Community Network Tree</div>
                        <div style={{ fontSize: '0.75rem', color: '#64748b' }}>Active Tier 1-5 Members</div>
                      </div>
                    </div>
                    <span style={{ fontSize: '0.9rem', fontWeight: '700', color: '#7e22ce' }}>3,240 Members</span>
                  </div>
                </div>

                <div style={{ marginTop: '20px', textAlign: 'center' }}>
                  <Link to="/rewards" style={{ color: '#4f7df3', fontSize: '0.85rem', fontWeight: '600', textDecoration: 'none', display: 'inline-flex', alignItems: 'center', gap: '4px' }}>
                    <span>Learn how rewards are calculated</span>
                    <ChevronRight className="w-4 h-4" />
                  </Link>
                </div>
              </div>
            </div>
          </div>

          {/* Stats Bar */}
          <div className="pub-stats-strip">
            <div className="pub-stat-item">
              <div className="pub-stat-value">50,000+</div>
              <div className="pub-stat-label">Verified Members</div>
            </div>
            <div className="pub-stat-item">
              <div className="pub-stat-value">12,400+</div>
              <div className="pub-stat-label">Business Pages</div>
            </div>
            <div className="pub-stat-item">
              <div className="pub-stat-value">$1.2M+</div>
              <div className="pub-stat-label">Rewards Distributed</div>
            </div>
            <div className="pub-stat-item">
              <div className="pub-stat-value">99.8%</div>
              <div className="pub-stat-label">Uptime & Reliability</div>
            </div>
          </div>
        </div>
      </section>

      {/* ====================================================================
          SECTION 1: WHAT CAN YOU DO ON MLM BOOK?
          ==================================================================== */}
      <section className="pub-section" style={{ background: '#ffffff', borderTop: '1px solid #e2e8f0', borderBottom: '1px solid #e2e8f0' }}>
        <div className="pub-container">
          <div className="pub-section-header">
            <div className="pub-badge pub-badge-indigo">
              <Sparkles className="w-3.5 h-3.5" />
              <span>Full-Stack Platform Capabilities</span>
            </div>
            <h2 className="pub-section-title">What Can You Do on MLM Book?</h2>
            <p className="pub-section-desc">
              Everything networkers and direct-selling enterprises need in one unified ecosystem. Connect with leaders, build massive teams, run high-converting ads, and get paid daily.
            </p>
          </div>

          <div className="pub-grid-3">
            {/* Capability 1 */}
            <div className="pub-card">
              <div className="pub-card-glow-line"></div>
              <div className="pub-icon-wrapper pub-icon-indigo">
                <Globe className="w-6 h-6" />
              </div>
              <h3 style={{ fontSize: '1.25rem', fontWeight: '800', color: '#0f172a', marginBottom: '12px' }}>
                100% MLM Social Networking
              </h3>
              <p style={{ color: '#475569', lineHeight: '1.6', fontSize: '0.94rem' }}>
                Share business plans, motivation, event pictures, and short Watch reels without getting shadowbanned. Every single user here is interested in network marketing.
              </p>
              <ul className="pub-checklist" style={{ marginTop: '16px' }}>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Interactive Posts, Polls & Nested Comments</span>
                </li>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>24-Hour Stories & Short-Form Video Reels</span>
                </li>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Private Direct Messaging & Community Groups</span>
                </li>
              </ul>
            </div>

            {/* Capability 2 */}
            <div className="pub-card">
              <div className="pub-card-glow-line"></div>
              <div className="pub-icon-wrapper pub-icon-emerald">
                <Briefcase className="w-6 h-6" />
              </div>
              <h3 style={{ fontSize: '1.25rem', fontWeight: '800', color: '#0f172a', marginBottom: '12px' }}>
                Verified Business Directory
              </h3>
              <p style={{ color: '#475569', lineHeight: '1.6', fontSize: '0.94rem' }}>
                Launch an official business profile for your MLM company or team. Showcase compensation plans, product catalogs, and earn a verified blue checkmark.
              </p>
              <ul className="pub-checklist" style={{ marginTop: '16px' }}>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Blue Checkmark Verified Brand Legitimacy</span>
                </li>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Product Showcases & Compensation Overviews</span>
                </li>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Team Sub-Accounts with Granular Roles</span>
                </li>
              </ul>
            </div>

            {/* Capability 3 */}
            <div className="pub-card">
              <div className="pub-card-glow-line"></div>
              <div className="pub-icon-wrapper pub-icon-cyan">
                <Target className="w-6 h-6" />
              </div>
              <h3 style={{ fontSize: '1.25rem', fontWeight: '800', color: '#0f172a', marginBottom: '12px' }}>
                Targeted MLM Ad Engine
              </h3>
              <p style={{ color: '#475569', lineHeight: '1.6', fontSize: '0.94rem' }}>
                Traditional ad networks ban direct sales. Here, you can launch precision pay-per-click and pay-per-view ads to reach motivated network leaders worldwide.
              </p>
              <ul className="pub-checklist" style={{ marginTop: '16px' }}>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Target by Country, MLM Company & Experience</span>
                </li>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Pay Only For Verified Human Interactions</span>
                </li>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>100% Instant Refund If You Stop or Close Ads</span>
                </li>
              </ul>
            </div>

            {/* Capability 4 */}
            <div className="pub-card">
              <div className="pub-card-glow-line"></div>
              <div className="pub-icon-wrapper pub-icon-amber">
                <Gift className="w-6 h-6" />
              </div>
              <h3 style={{ fontSize: '1.25rem', fontWeight: '800', color: '#0f172a', marginBottom: '12px' }}>
                Multi-Stream Earning Engine
              </h3>
              <p style={{ color: '#475569', lineHeight: '1.6', fontSize: '0.94rem' }}>
                Other platforms make billions from your data while giving you $0. On MLM Book, you earn daily USDT rewards for viewing ads, inviting leaders, and climbing rank tiers.
              </p>
              <ul className="pub-checklist" style={{ marginTop: '16px' }}>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Earn $0.025 to $1.00 USD Per Ad Engagement</span>
                </li>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Direct Introducer & Downline Overrides</span>
                </li>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>5-Tier Dynamic Rank Leadership Bonuses</span>
                </li>
              </ul>
            </div>

            {/* Capability 5 */}
            <div className="pub-card">
              <div className="pub-card-glow-line"></div>
              <div className="pub-icon-wrapper pub-icon-purple">
                <Users className="w-6 h-6" />
              </div>
              <h3 style={{ fontSize: '1.25rem', fontWeight: '800', color: '#0f172a', marginBottom: '12px' }}>
                Team & Downline Management
              </h3>
              <p style={{ color: '#475569', lineHeight: '1.6', fontSize: '0.94rem' }}>
                Build, manage, and communicate with your downline members in real time. Share exclusive training materials, schedule zoom calls, and monitor team volume.
              </p>
              <ul className="pub-checklist" style={{ marginTop: '16px' }}>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Visual 5-Generation Genealogy Tree</span>
                </li>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>1-Click Team Broadcast Messages</span>
                </li>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Leadership Recognition & Rank Badges</span>
                </li>
              </ul>
            </div>

            {/* Capability 6 */}
            <div className="pub-card">
              <div className="pub-card-glow-line"></div>
              <div className="pub-icon-wrapper pub-icon-rose">
                <Wallet className="w-6 h-6" />
              </div>
              <h3 style={{ fontSize: '1.25rem', fontWeight: '800', color: '#0f172a', marginBottom: '12px' }}>
                Web3 Crypto Wallet Integration
              </h3>
              <p style={{ color: '#475569', lineHeight: '1.6', fontSize: '0.94rem' }}>
                Forget complicated bank delays or frozen merchant accounts. Deposit advertising funds and withdraw your earnings directly in USDT (BEP20).
              </p>
              <ul className="pub-checklist" style={{ marginTop: '16px' }}>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Dual Wallet: Fund Wallet vs Reward</span>
                </li>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>0% Fee on Fund Wallet Withdrawals</span>
                </li>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Fast 24/7 Web3 Blockchain Payouts</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      {/* ====================================================================
          NEW SECTION: INSIDE YOUR MEMBER DASHBOARD (Interactive Live Preview)
          (Directly showcasing what members get when logged in)
          ==================================================================== */}
      <section className="pub-section" style={{ background: '#f8fafc' }}>
        <div className="pub-container">
          <div className="pub-section-header" style={{ marginBottom: '36px' }}>
            <div className="pub-badge pub-badge-emerald pub-anim-pulse">
              <Eye className="w-3.5 h-3.5" />
              <span>Member Experience Preview</span>
            </div>
            <h2 className="pub-section-title">Inside Your Member Dashboard</h2>
            <p className="pub-section-desc">
              Wondering what you see after logging in? Explore the real live modules that empower 50,000+ networkers every day. Click through the tabs below.
            </p>
          </div>

          {/* Interactive Portal Tabs */}
          <div className="pub-portal-tabs-nav">
            <button
              type="button"
              className={`pub-portal-tab-btn ${activePortalTab === 'feed' ? 'active' : ''}`}
              onClick={() => setActivePortalTab('feed')}
            >
              <MessageSquare className="w-4 h-4 text-blue-600" />
              <span>1. Socials Feed</span>
            </button>

            <button
              type="button"
              className={`pub-portal-tab-btn ${activePortalTab === 'watch' ? 'active' : ''}`}
              onClick={() => setActivePortalTab('watch')}
            >
              <TvMinimalPlay className="w-4 h-4 text-purple-600" />
              <span>2. Watch-To-Earn Hub</span>
            </button>

            <button
              type="button"
              className={`pub-portal-tab-btn ${activePortalTab === 'team' ? 'active' : ''}`}
              onClick={() => setActivePortalTab('team')}
            >
              <Users className="w-4 h-4 text-emerald-600" />
              <span>3. Team Connections</span>
            </button>

            <button
              type="button"
              className={`pub-portal-tab-btn ${activePortalTab === 'wallet' ? 'active' : ''}`}
              onClick={() => setActivePortalTab('wallet')}
            >
              <Wallet className="w-4 h-4 text-amber-600" />
              <span>4. Dual-Wallet Hub</span>
            </button>
          </div>

          {/* Tab Content Display Card */}
          <div className="pub-portal-preview-card">
            {/* Top Browser Bar */}
            <div className="pub-mock-browser-bar">
              <div className="pub-mock-browser-dots">
                <span className="pub-mock-dot" style={{ background: '#ef4444' }}></span>
                <span className="pub-mock-dot" style={{ background: '#f59e0b' }}></span>
                <span className="pub-mock-dot" style={{ background: '#10b981' }}></span>
              </div>
              <div style={{ background: '#ffffff', borderRadius: '8px', padding: '4px 14px', fontSize: '0.78rem', color: '#64748b', border: '1px solid #e2e8f0', display: 'flex', alignItems: 'center', gap: '6px' }}>
                <Lock className="w-3 h-3 text-emerald-600" />
                <span>mlmbook.com/member/{activePortalTab}</span>
              </div>
              <div style={{ marginLeft: 'auto', display: 'flex', alignItems: 'center', gap: '8px' }}>
                <span style={{ fontSize: '0.75rem', fontWeight: '700', color: '#059669', background: '#ecfdf5', padding: '3px 8px', borderRadius: '6px' }}>
                  ● Verified Session
                </span>
              </div>
            </div>

            {/* Dynamic Tab Body */}
            <div style={{ padding: '36px' }}>
              {/* TAB 1: SOCIALS FEED */}
              {activePortalTab === 'feed' && (
                <div>
                  <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '14px', marginBottom: '20px' }}>
                    <div>
                      <span style={{ fontSize: '0.75rem', fontWeight: '800', color: '#3b82f6', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                        Live Social Stream
                      </span>
                      <h3 style={{ fontSize: '1.45rem', fontWeight: '800', color: '#0f172a', margin: '2px 0 0' }}>
                        Member Feed, Business Updates & Stories
                      </h3>
                    </div>
                    <Link to="/member/register" className="pub-btn pub-btn-sm pub-btn-primary">
                      <span>Join The Discussion</span>
                      <ArrowRight className="w-4 h-4" />
                    </Link>
                  </div>

                  <p style={{ color: '#475569', fontSize: '0.94rem', lineHeight: '1.6', marginBottom: '24px' }}>
                    Unlike generic social platforms where sharing your direct-selling opportunity gets you shadowbanned, MLM Book is built 100% for network marketing. Post updates, run community polls, upload video testimonials, and connect with motivated builders.
                  </p>

                  {/* Mock Feed Post Card */}
                  <div style={{ background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: '16px', padding: '24px' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '14px' }}>
                      <div style={{ width: '44px', height: '44px', borderRadius: '50%', background: '#3b82f6', color: '#ffffff', display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: '800', fontSize: '1rem' }}>
                        RS
                      </div>
                      <div>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                          <span style={{ fontWeight: '800', color: '#0f172a', fontSize: '0.95rem' }}>Rajesh Sharma</span>
                          <CheckCircle2 className="w-4 h-4 text-blue-600" />
                          <span style={{ fontSize: '0.72rem', background: '#dbeafe', color: '#1d4ed8', fontWeight: '700', padding: '2px 6px', borderRadius: '4px' }}>
                            Master Leader ($1.00/ad)
                          </span>
                        </div>
                        <span style={{ fontSize: '0.78rem', color: '#64748b' }}>Founder, Global Wellness Leaders • 2h ago</span>
                      </div>
                    </div>

                    <p style={{ color: '#334155', fontSize: '0.92rem', lineHeight: '1.6', marginBottom: '16px' }}>
                      🚀 Incredible team milestone! Our organization just crossed 500 verified members across 6 countries on MLM Book. Our business page has generated over 1,200 targeted leads this month alone. Network marketing is about empowering people! #DirectSelling #Leadership
                    </p>

                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', borderTop: '1px solid #e2e8f0', paddingTop: '12px', color: '#64748b', fontSize: '0.82rem' }}>
                      <span style={{ display: 'flex', alignItems: 'center', gap: '4px' }}>
                        ❤️ 184 Likes • 42 Comments
                      </span>
                      <span style={{ display: 'flex', alignItems: 'center', gap: '4px', color: '#3b82f6', fontWeight: '600' }}>
                        🔗 26 Network Shares
                      </span>
                    </div>
                  </div>
                </div>
              )}

              {/* TAB 2: WATCH-TO-EARN HUB */}
              {activePortalTab === 'watch' && (
                <div>
                  <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '14px', marginBottom: '20px' }}>
                    <div>
                      <span style={{ fontSize: '0.75rem', fontWeight: '800', color: '#a855f7', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                        Video-To-Earn Experience
                      </span>
                      <h3 style={{ fontSize: '1.45rem', fontWeight: '800', color: '#0f172a', margin: '2px 0 0' }}>
                        Watch Video Reels & Earn Instant Crypto
                      </h3>
                    </div>
                    <Link to="/rewards" className="pub-btn pub-btn-sm pub-btn-outline">
                      <span>Reward Details</span>
                      <ArrowRight className="w-4 h-4" />
                    </Link>
                  </div>

                  <p style={{ color: '#475569', fontSize: '0.94rem', lineHeight: '1.6', marginBottom: '24px' }}>
                    Our dedicated Watch hub showcases video pitches, product unveilings, and executive training. Members receive real USDT rewards for watching verified campaigns, while advertisers enjoy guaranteed retention and full presentation views.
                  </p>

                  <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '20px' }}>
                    <div style={{ background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: '16px', padding: '20px' }}>
                      <div style={{ position: 'relative', height: '160px', background: 'linear-gradient(135deg, #1e293b 0%, #0f172a 100%)', borderRadius: '12px', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#ffffff', marginBottom: '14px' }}>
                        <PlayCircle className="w-12 h-12 text-white opacity-90" />
                        <span style={{ position: 'absolute', bottom: '10px', right: '10px', background: 'rgba(0,0,0,0.7)', padding: '2px 8px', borderRadius: '4px', fontSize: '0.75rem' }}>
                          0:45 / 1:00
                        </span>
                        <span style={{ position: 'absolute', top: '10px', left: '10px', background: '#10b981', color: '#ffffff', padding: '3px 8px', borderRadius: '4px', fontSize: '0.72rem', fontWeight: '700' }}>
                          ⚡ Reward Active
                        </span>
                      </div>
                      <div style={{ fontWeight: '700', color: '#0f172a', fontSize: '0.95rem', marginBottom: '4px' }}>
                        Global Crypto Ecosystem Presentation
                      </div>
                      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginTop: '10px', fontSize: '0.8rem', color: '#64748b' }}>
                        <span>Earned: <strong>$0.2500 USDT</strong></span>
                        <span style={{ color: '#059669', fontWeight: '700' }}>✓ 100% Watched</span>
                      </div>
                    </div>

                    <div style={{ background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: '16px', padding: '20px', display: 'flex', flexDirection: 'column', justifyContent: 'center' }}>
                      <div style={{ fontSize: '0.85rem', fontWeight: '700', color: '#0f172a', marginBottom: '10px' }}>
                        Why Watch-To-Earn Works:
                      </div>
                      <div style={{ display: 'flex', flexDirection: 'column', gap: '10px', fontSize: '0.85rem', color: '#475569' }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                          <CheckCircle2 className="w-4 h-4 text-emerald-600 flex-shrink-0" />
                          <span>Guaranteed minimum view duration prevents quick skips</span>
                        </div>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                          <CheckCircle2 className="w-4 h-4 text-emerald-600 flex-shrink-0" />
                          <span>Payout matches your rank: $0.025 to $1.00 USD</span>
                        </div>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                          <CheckCircle2 className="w-4 h-4 text-emerald-600 flex-shrink-0" />
                          <span>Zero bots: Mobile OTP verified human members only</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              )}

              {/* TAB 3: TEAM GENEALOGY & CONNECTIONS */}
              {activePortalTab === 'team' && (
                <div>
                  <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '14px', marginBottom: '20px' }}>
                    <div>
                      <span style={{ fontSize: '0.75rem', fontWeight: '800', color: '#10b981', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                        Genealogy & Relationship Tracking
                      </span>
                      <h3 style={{ fontSize: '1.45rem', fontWeight: '800', color: '#0f172a', margin: '2px 0 0' }}>
                        Visual Downline Tree & Verified Connections
                      </h3>
                    </div>
                    <Link to="/member/register" className="pub-btn pub-btn-sm pub-btn-primary">
                      <span>Start Building Your Team</span>
                      <ArrowRight className="w-4 h-4" />
                    </Link>
                  </div>

                  <p style={{ color: '#475569', fontSize: '0.94rem', lineHeight: '1.6', marginBottom: '24px' }}>
                    Track your direct introducers, monitor connection requests, and unlock rank milestones. On MLM Book, <strong style={{ color: '#0f172a' }}>Team Count = Accepted Verified Connections</strong>. Build genuine relationships and watch your rank tier scale up.
                  </p>

                  <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(260px, 1fr))', gap: '16px' }}>
                    <div style={{ background: '#ffffff', border: '1px solid #e2e8f0', borderRadius: '14px', padding: '18px', boxShadow: 'var(--pub-shadow-sm)' }}>
                      <div style={{ fontSize: '0.75rem', color: '#64748b', fontWeight: '700', textTransform: 'uppercase' }}>Direct Referrals</div>
                      <div style={{ fontSize: '1.6rem', fontWeight: '900', color: '#0f172a', marginTop: '4px' }}>18 Active</div>
                      <div style={{ fontSize: '0.78rem', color: '#059669', marginTop: '4px' }}>✓ 100% Mobile Verified</div>
                    </div>

                    <div style={{ background: '#ffffff', border: '1px solid #e2e8f0', borderRadius: '14px', padding: '18px', boxShadow: 'var(--pub-shadow-sm)' }}>
                      <div style={{ fontSize: '0.75rem', color: '#64748b', fontWeight: '700', textTransform: 'uppercase' }}>Verified Team Connections</div>
                      <div style={{ fontSize: '1.6rem', fontWeight: '900', color: '#3b82f6', marginTop: '4px' }}>142 Users</div>
                      <div style={{ fontSize: '0.78rem', color: '#64748b', marginTop: '4px' }}>Connected Across 8 Countries</div>
                    </div>

                    <div style={{ background: '#ffffff', border: '1px solid #e2e8f0', borderRadius: '14px', padding: '18px', boxShadow: 'var(--pub-shadow-sm)' }}>
                      <div style={{ fontSize: '0.75rem', color: '#64748b', fontWeight: '700', textTransform: 'uppercase' }}>Current Rank</div>
                      <div style={{ fontSize: '1.4rem', fontWeight: '900', color: '#10b981', marginTop: '4px' }}>Leaders Rank</div>
                      <div style={{ fontSize: '0.78rem', color: '#0f172a', fontWeight: '700', marginTop: '4px' }}>$0.2500 USD / Ad View</div>
                    </div>
                  </div>
                </div>
              )}

              {/* TAB 4: DUAL-WALLET HUB */}
              {activePortalTab === 'wallet' && (
                <div>
                  <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '14px', marginBottom: '20px' }}>
                    <div>
                      <span style={{ fontSize: '0.75rem', fontWeight: '800', color: '#f59e0b', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                        Financial Infrastructure
                      </span>
                      <h3 style={{ fontSize: '1.45rem', fontWeight: '800', color: '#0f172a', margin: '2px 0 0' }}>
                        Dual-Wallet Accounting & Web3 Withdrawals
                      </h3>
                    </div>
                    <Link to="/rewards" className="pub-btn pub-btn-sm pub-btn-outline">
                      <span>View Fee Rules</span>
                      <ArrowRight className="w-4 h-4" />
                    </Link>
                  </div>

                  <p style={{ color: '#475569', fontSize: '0.94rem', lineHeight: '1.6', marginBottom: '24px' }}>
                    Separating business marketing funds from member earnings guarantees clear audit trails. Request payouts with zero delays via Binance Smart Chain (USDT BEP-20).
                  </p>

                  <div className="pub-grid-2">
                    <div style={{ background: '#ffffff', border: '1px solid #e2e8f0', borderRadius: '14px', padding: '22px', boxShadow: 'var(--pub-shadow-sm)' }}>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '12px' }}>
                        <div className="pub-icon-wrapper pub-icon-cyan" style={{ width: '38px', height: '38px', marginBottom: 0 }}>
                          <Wallet className="w-5 h-5 text-sky-600" />
                        </div>
                        <div>
                          <div style={{ fontSize: '0.75rem', color: '#64748b', fontWeight: '700', textTransform: 'uppercase' }}>Operational Capital</div>
                          <div style={{ fontSize: '1.1rem', fontWeight: '800', color: '#0f172a' }}>Fund Wallet</div>
                        </div>
                      </div>
                      <div style={{ fontSize: '1.7rem', fontWeight: '900', color: '#0284c7', margin: '8px 0' }}>$85.50 USDT</div>
                      <div style={{ fontSize: '0.8rem', color: '#059669', fontWeight: '700' }}>✓ 0.00% Withdrawal Fee (Zero Deductions!)</div>
                      <div style={{ fontSize: '0.78rem', color: '#64748b', marginTop: '4px' }}>Used for funding ads & transfers</div>
                    </div>

                    <div style={{ background: '#ffffff', border: '1px solid #e2e8f0', borderRadius: '14px', padding: '22px', boxShadow: 'var(--pub-shadow-sm)' }}>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '12px' }}>
                        <div className="pub-icon-wrapper pub-icon-emerald" style={{ width: '38px', height: '38px', marginBottom: 0 }}>
                          <Gift className="w-5 h-5 text-emerald-600" />
                        </div>
                        <div>
                          <div style={{ fontSize: '0.75rem', color: '#64748b', fontWeight: '700', textTransform: 'uppercase' }}>Cashable Rewards</div>
                          <div style={{ fontSize: '1.1rem', fontWeight: '800', color: '#0f172a' }}>Reward</div>
                        </div>
                      </div>
                      <div style={{ fontSize: '1.7rem', fontWeight: '900', color: '#059669', margin: '8px 0' }}>$142.75 USDT</div>
                      <div style={{ fontSize: '0.8rem', color: '#059669', fontWeight: '700' }}>0.00% Withdrawal Fee (Zero Deductions!) • Min $5.00</div>
                      <div style={{ fontSize: '0.78rem', color: '#64748b', marginTop: '4px' }}>All ad engagement & downline rewards</div>
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      </section>

      {/* ====================================================================
          NEW SECTION: THE 6 CORE PLATFORM ENGINES (Unified Ecosystem)
          (Directly from HomeCapabilities.jsx)
          ==================================================================== */}
      <section className="pub-section" style={{ background: '#ffffff', borderTop: '1px solid #e2e8f0', borderBottom: '1px solid #e2e8f0' }}>
        <div className="pub-container">
          <div className="pub-section-header">
            <div className="pub-badge pub-badge-indigo">
              <Layers className="w-3.5 h-3.5" />
              <span>Full System Architecture</span>
            </div>
            <h2 className="pub-section-title">The 6 Core Platform Engines</h2>
            <p className="pub-section-desc">
              Every tool a modern network marketing professional needs to build a scalable global enterprise, all accessible through one unified login.
            </p>
          </div>

          <div className="pub-grid-3">
            {/* Engine 1: Socials */}
            <div className="pub-capability-card">
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '14px' }}>
                <div className="pub-icon-wrapper pub-icon-indigo" style={{ width: '44px', height: '44px', marginBottom: 0 }}>
                  <MessageSquare className="w-5 h-5 text-blue-600" />
                </div>
                <span className="pub-badge" style={{ fontSize: '0.72rem', padding: '4px 10px', margin: 0 }}>Social Feed</span>
              </div>
              <h3 style={{ fontSize: '1.2rem', fontWeight: '800', color: '#0f172a', marginBottom: '8px' }}>
                Socials Hub
              </h3>
              <p style={{ color: '#475569', fontSize: '0.9rem', lineHeight: '1.6', marginBottom: '18px' }}>
                Post multimedia updates, share company wins, and engage with networkers worldwide without censorship or algorithmic shadowbans.
              </p>
              <div style={{ marginTop: 'auto' }}>
                <span style={{ fontSize: '0.82rem', fontWeight: '700', color: '#4f7df3', display: 'inline-flex', alignItems: 'center', gap: '4px' }}>
                  <span>Interactive Feeds & Stories</span>
                  <ChevronRight className="w-4 h-4" />
                </span>
              </div>
            </div>

            {/* Engine 2: Watch */}
            <div className="pub-capability-card">
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '14px' }}>
                <div className="pub-icon-wrapper pub-icon-purple" style={{ width: '44px', height: '44px', marginBottom: 0 }}>
                  <TvMinimalPlay className="w-5 h-5 text-purple-600" />
                </div>
                <span className="pub-badge" style={{ fontSize: '0.72rem', padding: '4px 10px', margin: 0 }}>Video Media</span>
              </div>
              <h3 style={{ fontSize: '1.2rem', fontWeight: '800', color: '#0f172a', marginBottom: '8px' }}>
                Watch-To-Earn Hub
              </h3>
              <p style={{ color: '#475569', fontSize: '0.9rem', lineHeight: '1.6', marginBottom: '18px' }}>
                Discover high-converting video pitches and product reels. Earn verified USDT rewards simply for exploring sponsored business content.
              </p>
              <div style={{ marginTop: 'auto' }}>
                <span style={{ fontSize: '0.82rem', fontWeight: '700', color: '#a855f7', display: 'inline-flex', alignItems: 'center', gap: '4px' }}>
                  <span>Up to $1.00 / Video</span>
                  <ChevronRight className="w-4 h-4" />
                </span>
              </div>
            </div>

            {/* Engine 3: Business Directory */}
            <div className="pub-capability-card">
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '14px' }}>
                <div className="pub-icon-wrapper pub-icon-amber" style={{ width: '44px', height: '44px', marginBottom: 0 }}>
                  <Building2 className="w-5 h-5 text-amber-600" />
                </div>
                <span className="pub-badge" style={{ fontSize: '0.72rem', padding: '4px 10px', margin: 0 }}>Discovery</span>
              </div>
              <h3 style={{ fontSize: '1.2rem', fontWeight: '800', color: '#0f172a', marginBottom: '8px' }}>
                Verified Business Directory
              </h3>
              <p style={{ color: '#475569', fontSize: '0.9rem', lineHeight: '1.6', marginBottom: '18px' }}>
                Explore legitimate network marketing companies, review compensation plans, and give your brand global discoverability.
              </p>
              <div style={{ marginTop: 'auto' }}>
                <span style={{ fontSize: '0.82rem', fontWeight: '700', color: '#d97706', display: 'inline-flex', alignItems: 'center', gap: '4px' }}>
                  <span>Official Enterprise Profiles</span>
                  <ChevronRight className="w-4 h-4" />
                </span>
              </div>
            </div>

            {/* Engine 4: Communities */}
            <div className="pub-capability-card">
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '14px' }}>
                <div className="pub-icon-wrapper pub-icon-emerald" style={{ width: '44px', height: '44px', marginBottom: 0 }}>
                  <Users className="w-5 h-5 text-emerald-600" />
                </div>
                <span className="pub-badge" style={{ fontSize: '0.72rem', padding: '4px 10px', margin: 0 }}>Community</span>
              </div>
              <h3 style={{ fontSize: '1.2rem', fontWeight: '800', color: '#0f172a', marginBottom: '8px' }}>
                Focused Communities
              </h3>
              <p style={{ color: '#475569', fontSize: '0.9rem', lineHeight: '1.6', marginBottom: '18px' }}>
                Build or join dedicated team groups, share private compensation strategies, and organize leadership masterminds.
              </p>
              <div style={{ marginTop: 'auto' }}>
                <span style={{ fontSize: '0.82rem', fontWeight: '700', color: '#059669', display: 'inline-flex', alignItems: 'center', gap: '4px' }}>
                  <span>Private Team Rooms</span>
                  <ChevronRight className="w-4 h-4" />
                </span>
              </div>
            </div>

            {/* Engine 5: Connections */}
            <div className="pub-capability-card">
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '14px' }}>
                <div className="pub-icon-wrapper pub-icon-cyan" style={{ width: '44px', height: '44px', marginBottom: 0 }}>
                  <UserPlus className="w-5 h-5 text-sky-600" />
                </div>
                <span className="pub-badge" style={{ fontSize: '0.72rem', padding: '4px 10px', margin: 0 }}>Networking</span>
              </div>
              <h3 style={{ fontSize: '1.2rem', fontWeight: '800', color: '#0f172a', marginBottom: '8px' }}>
                Connections Network
              </h3>
              <p style={{ color: '#475569', fontSize: '0.9rem', lineHeight: '1.6', marginBottom: '18px' }}>
                Expand your high-trust rolodex. Accepted verified connections build your Team Count and advance your rank to unlock higher ad earnings.
              </p>
              <div style={{ marginTop: 'auto' }}>
                <span style={{ fontSize: '0.82rem', fontWeight: '700', color: '#0284c7', display: 'inline-flex', alignItems: 'center', gap: '4px' }}>
                  <span>Verified Friendship Network</span>
                  <ChevronRight className="w-4 h-4" />
                </span>
              </div>
            </div>

            {/* Engine 6: Ad Studio */}
            <div className="pub-capability-card">
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '14px' }}>
                <div className="pub-icon-wrapper pub-icon-rose" style={{ width: '44px', height: '44px', marginBottom: 0 }}>
                  <Target className="w-5 h-5 text-rose-600" />
                </div>
                <span className="pub-badge" style={{ fontSize: '0.72rem', padding: '4px 10px', margin: 0 }}>Growth</span>
              </div>
              <h3 style={{ fontSize: '1.2rem', fontWeight: '800', color: '#0f172a', marginBottom: '8px' }}>
                Self-Serve Ad Studio
              </h3>
              <p style={{ color: '#475569', fontSize: '0.9rem', lineHeight: '1.6', marginBottom: '18px' }}>
                Launch high-CTR promotions in minutes. Track clicks, view completion times, and enjoy 100% immediate unspent budget refunds on campaign close.
              </p>
              <div style={{ marginTop: 'auto' }}>
                <span style={{ fontSize: '0.82rem', fontWeight: '700', color: '#e11d48', display: 'inline-flex', alignItems: 'center', gap: '4px' }}>
                  <span>Precision MLM Reach</span>
                  <ChevronRight className="w-4 h-4" />
                </span>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* ====================================================================
          SECTION 2: HOW MLM BOOK WORKS (4-Step Clear Journey)
          ==================================================================== */}
      <section className="pub-section">
        <div className="pub-container">
          <div className="pub-section-header">
            <div className="pub-badge pub-badge-emerald">
              <Zap className="w-3.5 h-3.5" />
              <span>Simple 4-Step Pathway</span>
            </div>
            <h2 className="pub-section-title">How MLM Book Works</h2>
            <p className="pub-section-desc">
              Joining and succeeding on MLM Book is straightforward. Here is how active members and entrepreneurs turn their daily networking into predictable rewards.
            </p>
          </div>

          <div className="pub-grid-4">
            {/* Step 1 */}
            <div className="pub-card" style={{ position: 'relative' }}>
              <div className="pub-step-num">01</div>
              <h3 style={{ fontSize: '1.2rem', fontWeight: '800', color: '#0f172a', marginBottom: '10px' }}>
                Join Free With Sponsor
              </h3>
              <p style={{ color: '#475569', fontSize: '0.92rem', lineHeight: '1.6' }}>
                Sign up in under 60 seconds with your email or Google Account. Enter an Introducer Code to immediately connect with a verified team leader and qualify for day-one Advertiser rank ($0.0250/ad).
              </p>
            </div>

            {/* Step 2 */}
            <div className="pub-card" style={{ position: 'relative' }}>
              <div className="pub-step-num">02</div>
              <h3 style={{ fontSize: '1.2rem', fontWeight: '800', color: '#0f172a', marginBottom: '10px' }}>
                Complete Mobile OTP
              </h3>
              <p style={{ color: '#475569', fontSize: '0.92rem', lineHeight: '1.6' }}>
                Verify your WhatsApp or Mobile number with instant OTP to secure your account, prevent multi-accounting, and unlock your Reward for instant withdrawals.
              </p>
            </div>

            {/* Step 3 */}
            <div className="pub-card" style={{ position: 'relative' }}>
              <div className="pub-step-num">03</div>
              <h3 style={{ fontSize: '1.2rem', fontWeight: '800', color: '#0f172a', marginBottom: '10px' }}>
                Explore, Engage & Earn
              </h3>
              <p style={{ color: '#475569', fontSize: '0.92rem', lineHeight: '1.6' }}>
                Publish rich posts, engage in discussions, watch partner video ads for rewards, or launch targeted business promotions with full budget protection.
              </p>
            </div>

            {/* Step 4 */}
            <div className="pub-card" style={{ position: 'relative' }}>
              <div className="pub-step-num">04</div>
              <h3 style={{ fontSize: '1.2rem', fontWeight: '800', color: '#0f172a', marginBottom: '10px' }}>
                Scale & Collect USDT
              </h3>
              <p style={{ color: '#475569', fontSize: '0.92rem', lineHeight: '1.6' }}>
                All commissions from ad views, direct introducers, and downline volume credit instantly to your Reward account. Withdraw straight to your personal Web3 USDT wallet 24/7.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* ====================================================================
          SECTION 3: HOW MLM BOOK ADVERTISING WORKS (High-ROI Engine)
          ==================================================================== */}
      <section className="pub-section" style={{ background: '#f8fafc', borderTop: '1px solid #e2e8f0', borderBottom: '1px solid #e2e8f0' }}>
        <div className="pub-container">
          <div className="pub-section-header">
            <div className="pub-badge pub-badge-amber">
              <TrendingUp className="w-3.5 h-3.5" />
              <span>For Business Growth & Customer Acquisition</span>
            </div>
            <h2 className="pub-section-title">How MLM Book Advertising Works</h2>
            <p className="pub-section-desc">
              Why spend thousands on Facebook or Google only to get banned or ignored? Discover how our targeted advertising engine delivers real, engaged networkers.
            </p>
          </div>

          <div className="pub-grid-2" style={{ marginBottom: '40px' }}>
            {/* Left: The Old Problem */}
            <div className="pub-card pub-split-card" style={{ borderLeft: '4px solid #ef4444' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '14px' }}>
                <div style={{ width: '36px', height: '36px', borderRadius: '10px', background: '#fee2e2', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#dc2626' }}>
                  <XCircle className="w-5 h-5" />
                </div>
                <h3 style={{ fontSize: '1.25rem', fontWeight: '800', color: '#991b1b' }}>The Problem with Traditional Ad Networks</h3>
              </div>
              <p style={{ color: '#475569', fontSize: '0.93rem', lineHeight: '1.6', marginBottom: '18px' }}>
                Promoting network marketing, crypto, or direct sales on standard social media is like swimming against a tsunami:
              </p>
              <div className="pub-comparison-pill bad">
                <X className="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" />
                <span style={{ fontSize: '0.88rem' }}><strong>Frequent Account Bans:</strong> FB and Google algorithms instantly flag MLM, crypto, and direct sales as policy violations.</span>
              </div>
              <div className="pub-comparison-pill bad">
                <X className="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" />
                <span style={{ fontSize: '0.88rem' }}><strong>Massive Wasted Ad Spend:</strong> Ads are shown to generic users who have zero interest in network marketing, resulting in terrible conversion rates.</span>
              </div>
              <div className="pub-comparison-pill bad">
                <X className="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" />
                <span style={{ fontSize: '0.88rem' }}><strong>Zero Incentive for Viewers:</strong> Viewers find ads annoying and skip them immediately without paying attention to your presentation.</span>
              </div>
            </div>

            {/* Right: The MLM Book Solution */}
            <div className="pub-card pub-split-card featured" style={{ borderLeft: '4px solid #10b981' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '14px' }}>
                <div style={{ width: '36px', height: '36px', borderRadius: '10px', background: '#d1fae5', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#059669' }}>
                  <CheckCircle2 className="w-5 h-5" />
                </div>
                <h3 style={{ fontSize: '1.25rem', fontWeight: '800', color: '#065f46' }}>The MLM Book Precision Solution</h3>
              </div>
              <p style={{ color: '#475569', fontSize: '0.93rem', lineHeight: '1.6', marginBottom: '18px' }}>
                We built an advertising system specifically engineered for direct sellers, companies, and network leaders:
              </p>
              <div className="pub-comparison-pill good">
                <Check className="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" />
                <span style={{ fontSize: '0.88rem' }}><strong>100% MLM Target Audience:</strong> Every viewer on MLM Book understands the direct selling model and actively seeks business growth.</span>
              </div>
              <div className="pub-comparison-pill good">
                <Check className="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" />
                <span style={{ fontSize: '0.88rem' }}><strong>Guaranteed View Duration & High CTR:</strong> Members receive crypto rewards for watching your campaign, ensuring they absorb your full message.</span>
              </div>
              <div className="pub-comparison-pill good">
                <Check className="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" />
                <span style={{ fontSize: '0.88rem' }}><strong>100% Unspent Refund Guarantee:</strong> Cancel or close any ad at any time to receive your exact remaining balance back into your Fund Wallet instantly!</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* ====================================================================
          SECTION 4: HOW MEMBER REWARDS WORK (5-Tier Rank Aligned)
          ==================================================================== */}
      <section className="pub-section">
        <div className="pub-container">
          <div className="pub-section-header">
            <div className="pub-badge pub-badge-cyan">
              <Coins className="w-3.5 h-3.5" />
              <span>Automated Reward Architecture</span>
            </div>
            <h2 className="pub-section-title">How Member Rewards Work</h2>
            <p className="pub-section-desc">
              MLM Book believes in sharing the platform's commercial ad revenue directly with the community that drives engagement. Here is how your daily actions convert into withdrawable USDT.
            </p>
          </div>

          <div className="pub-grid-3">
            {/* Reward 1 */}
            <div className="pub-card">
              <div className="pub-icon-wrapper pub-icon-emerald">
                <Flame className="w-6 h-6" />
              </div>
              <div style={{ fontSize: '0.8rem', fontWeight: '700', color: '#059669', textTransform: 'uppercase', letterSpacing: '0.05em', marginBottom: '6px' }}>
                Stream 01
              </div>
              <h3 style={{ fontSize: '1.25rem', fontWeight: '800', color: '#0f172a', marginBottom: '10px' }}>
                Ad Engagement Rewards
              </h3>
              <p style={{ color: '#475569', fontSize: '0.93rem', lineHeight: '1.6' }}>
                Get paid real USDT simply for viewing partner ad campaigns, watching brand videos, and engaging with verified business pages. Every view has a guaranteed payout credited instantly to your wallet.
              </p>
            </div>

            {/* Reward 2 */}
            <div className="pub-card">
              <div className="pub-icon-wrapper pub-icon-indigo">
                <Users className="w-6 h-6" />
              </div>
              <div style={{ fontSize: '0.8rem', fontWeight: '700', color: '#4f46e5', textTransform: 'uppercase', letterSpacing: '0.05em', marginBottom: '6px' }}>
                Stream 02
              </div>
              <h3 style={{ fontSize: '1.25rem', fontWeight: '800', color: '#0f172a', marginBottom: '10px' }}>
                Level 1 Introducer Bonus
              </h3>
              <p style={{ color: '#475569', fontSize: '0.93rem', lineHeight: '1.6' }}>
                Whenever you share your personal referral link or QR code, every new member who joins with your sponsor code becomes part of your direct downline. You receive immediate commissions on their activity.
              </p>
            </div>

            {/* Reward 3 */}
            <div className="pub-card">
              <div className="pub-icon-wrapper pub-icon-purple">
                <Layers className="w-6 h-6" />
              </div>
              <div style={{ fontSize: '0.8rem', fontWeight: '700', color: '#7e22ce', textTransform: 'uppercase', letterSpacing: '0.05em', marginBottom: '6px' }}>
                Stream 03
              </div>
              <h3 style={{ fontSize: '1.25rem', fontWeight: '800', color: '#0f172a', marginBottom: '10px' }}>
                Tier 1 to 5 Team Overrides
              </h3>
              <p style={{ color: '#475569', fontSize: '0.93rem', lineHeight: '1.6' }}>
                Your earnings don't stop at direct referrals. As your downline leaders introduce others across 5 complete tiers, automated override commissions flow continuously up into your Reward account.
              </p>
            </div>

            {/* Reward 4: Updated with 5 Ranks */}
            <div className="pub-card">
              <div className="pub-icon-wrapper pub-icon-amber">
                <Award className="w-6 h-6" />
              </div>
              <div style={{ fontSize: '0.8rem', fontWeight: '700', color: '#d97706', textTransform: 'uppercase', letterSpacing: '0.05em', marginBottom: '6px' }}>
                Stream 04
              </div>
              <h3 style={{ fontSize: '1.25rem', fontWeight: '800', color: '#0f172a', marginBottom: '10px' }}>
                5 Dynamic Leadership Ranks
              </h3>
              <p style={{ color: '#475569', fontSize: '0.93rem', lineHeight: '1.6' }}>
                Climb from <strong>Advertiser ($0.0250)</strong> up to <strong>Master Leader ($1.0000/ad)</strong> based on your verified direct referrals and connections. Earn up to 40x higher on every engagement!
              </p>
              <div style={{ marginTop: '10px' }}>
                <Link to="/rewards" style={{ fontSize: '0.82rem', fontWeight: '700', color: '#d97706', textDecoration: 'none', display: 'inline-flex', alignItems: 'center', gap: '4px' }}>
                  <span>View All 5 Rank Rules</span>
                  <ChevronRight className="w-4 h-4" />
                </Link>
              </div>
            </div>

            {/* Reward 5 */}
            <div className="pub-card">
              <div className="pub-icon-wrapper pub-icon-rose">
                <BarChart3 className="w-6 h-6" />
              </div>
              <div style={{ fontSize: '0.8rem', fontWeight: '700', color: '#e11d48', textTransform: 'uppercase', letterSpacing: '0.05em', marginBottom: '6px' }}>
                Stream 05
              </div>
              <h3 style={{ fontSize: '1.25rem', fontWeight: '800', color: '#0f172a', marginBottom: '10px' }}>
                Business Campaign Cashbacks
              </h3>
              <p style={{ color: '#475569', fontSize: '0.93rem', lineHeight: '1.6' }}>
                Companies and leaders who fund ad campaigns receive volume cashbacks and bonus promotional reach, lowering cost-per-lead and maximizing downline team expansion.
              </p>
            </div>

            {/* Reward 6 */}
            <div className="pub-card" style={{ background: 'linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%)', borderColor: '#bbf7d0' }}>
              <div className="pub-icon-wrapper pub-icon-emerald">
                <Wallet className="w-6 h-6" />
              </div>
              <div style={{ fontSize: '0.8rem', fontWeight: '700', color: '#059669', textTransform: 'uppercase', letterSpacing: '0.05em', marginBottom: '6px' }}>
                Payout Guarantee
              </div>
              <h3 style={{ fontSize: '1.25rem', fontWeight: '800', color: '#065f46', marginBottom: '10px' }}>
                Instant Web3 Withdrawals
              </h3>
              <p style={{ color: '#475569', fontSize: '0.93rem', lineHeight: '1.6' }}>
                No waiting for monthly pay cycles. Once your rewards hit the $5.00 minimum threshold, request an instant USDT withdrawal directly to your personal crypto wallet with full blockchain transparency.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* ====================================================================
          NEW SECTION: BUILT ON TRUST & ZERO BOT TOLERANCE (5 Safety Pillars)
          (Directly from HomeTrustVerification.jsx)
          ==================================================================== */}
      <section className="pub-section" style={{ background: '#f8fafc', borderTop: '1px solid #e2e8f0', borderBottom: '1px solid #e2e8f0' }}>
        <div className="pub-container">
          <div className="pub-section-header" style={{ marginBottom: '36px' }}>
            <div className="pub-badge pub-badge-emerald">
              <ShieldCheck className="w-3.5 h-3.5" />
              <span>Platform Integrity & Security</span>
            </div>
            <h2 className="pub-section-title">Built on Trust & Zero Bot Tolerance</h2>
            <p className="pub-section-desc">
              MLM Book protects advertisers against fake clicks and protects members against fraudulent offers with 5 automated platform guardrails.
            </p>
          </div>

          <div className="pub-grid-5" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(210px, 1fr))', gap: '18px' }}>
            {/* Pillar 1 */}
            <div className="pub-trust-card">
              <div style={{ width: '40px', height: '40px', borderRadius: '10px', background: '#dbeafe', color: '#2563eb', display: 'flex', alignItems: 'center', justifyContent: 'center', marginBottom: '14px' }}>
                <Smartphone className="w-5 h-5" />
              </div>
              <h3 style={{ fontSize: '1.05rem', fontWeight: '800', color: '#0f172a', marginBottom: '6px' }}>
                Mobile / WhatsApp OTP
              </h3>
              <p style={{ color: '#64748b', fontSize: '0.85rem', lineHeight: '1.5', margin: 0 }}>
                Mandatory phone verification eliminates multi-accounting and bot farms from draining budgets.
              </p>
            </div>

            {/* Pillar 2 */}
            <div className="pub-trust-card">
              <div style={{ width: '40px', height: '40px', borderRadius: '10px', background: '#d1fae5', color: '#059669', display: 'flex', alignItems: 'center', justifyContent: 'center', marginBottom: '14px' }}>
                <UserCheck className="w-5 h-5" />
              </div>
              <h3 style={{ fontSize: '1.05rem', fontWeight: '800', color: '#0f172a', marginBottom: '6px' }}>
                Verified Member Identity
              </h3>
              <p style={{ color: '#64748b', fontSize: '0.85rem', lineHeight: '1.5', margin: 0 }}>
                Authentic human networkers. Advertisers only pay for genuine engagement from real leaders.
              </p>
            </div>

            {/* Pillar 3 */}
            <div className="pub-trust-card">
              <div style={{ width: '40px', height: '40px', borderRadius: '10px', background: '#e0e7ff', color: '#4f46e5', display: 'flex', alignItems: 'center', justifyContent: 'center', marginBottom: '14px' }}>
                <CheckSquare className="w-5 h-5" />
              </div>
              <h3 style={{ fontSize: '1.05rem', fontWeight: '800', color: '#0f172a', marginBottom: '6px' }}>
                Campaign Review
              </h3>
              <p style={{ color: '#64748b', fontSize: '0.85rem', lineHeight: '1.5', margin: 0 }}>
                Every ad is screened for compliance to ensure our community only sees credible business offers.
              </p>
            </div>

            {/* Pillar 4 */}
            <div className="pub-trust-card">
              <div style={{ width: '40px', height: '40px', borderRadius: '10px', background: '#f3e8ff', color: '#9333ea', display: 'flex', alignItems: 'center', justifyContent: 'center', marginBottom: '14px' }}>
                <ShieldCheck className="w-5 h-5" />
              </div>
              <h3 style={{ fontSize: '1.05rem', fontWeight: '800', color: '#0f172a', marginBottom: '6px' }}>
                Anti-Self-Dealing Gate
              </h3>
              <p style={{ color: '#64748b', fontSize: '0.85rem', lineHeight: '1.5', margin: 0 }}>
                Campaign owners cannot earn from their own ads. Exactly 1 reward per user per campaign.
              </p>
            </div>

            {/* Pillar 5 */}
            <div className="pub-trust-card">
              <div style={{ width: '40px', height: '40px', borderRadius: '10px', background: '#fef3c7', color: '#d97706', display: 'flex', alignItems: 'center', justifyContent: 'center', marginBottom: '14px' }}>
                <RefreshCw className="w-5 h-5" />
              </div>
              <h3 style={{ fontSize: '1.05rem', fontWeight: '800', color: '#0f172a', marginBottom: '6px' }}>
                100% Unspent Refund
              </h3>
              <p style={{ color: '#64748b', fontSize: '0.85rem', lineHeight: '1.5', margin: 0 }}>
                Cancel an ad anytime — 100% of remaining unspent budget is refunded back to your Fund Wallet instantly.
              </p>
            </div>
          </div>

          {/* Ethical Platform Standards Disclaimer */}
          <div
            style={{
              marginTop: '32px',
              background: '#ffffff',
              border: '1px solid #e2e8f0',
              borderRadius: '14px',
              padding: '20px 24px',
              display: 'flex',
              alignItems: 'center',
              gap: '16px'
            }}
          >
            <Info className="w-6 h-6 text-indigo-600 flex-shrink-0" />
            <p style={{ color: '#475569', fontSize: '0.88rem', lineHeight: '1.6', margin: 0 }}>
              <strong style={{ color: '#0f172a' }}>Important Notice & Transparency Pledge:</strong> {platformName} is a genuine social & advertising technology platform. We do NOT offer fixed daily earnings, guaranteed passive ROI, or crypto investment programs. All rewards are conditionally issued based on authentic business advertising budgets and legitimate member engagement.
            </p>
          </div>
        </div>
      </section>

      {/* ====================================================================
          SECTION 5: WHY BUSINESSES USE MLM BOOK
          ==================================================================== */}
      <section className="pub-section" style={{ background: '#ffffff', borderTop: '1px solid #e2e8f0', borderBottom: '1px solid #e2e8f0' }}>
        <div className="pub-container">
          <div className="pub-section-header">
            <div className="pub-badge pub-badge-indigo">
              <Briefcase className="w-3.5 h-3.5" />
              <span>For Companies, Founders & Team Leaders</span>
            </div>
            <h2 className="pub-section-title">Why Businesses Use MLM Book</h2>
            <p className="pub-section-desc">
              Whether you are an established direct-selling enterprise, a growing product brand, or a top network leader, MLM Book provides the highest ROI marketing infrastructure in the industry.
            </p>
          </div>

          <div className="pub-grid-2">
            <div className="pub-card pub-split-card">
              <div>
                <div className="pub-icon-wrapper pub-icon-indigo" style={{ marginBottom: '18px' }}>
                  <ShieldCheck className="w-6 h-6" />
                </div>
                <h3 style={{ fontSize: '1.35rem', fontWeight: '800', color: '#0f172a', marginBottom: '12px' }}>
                  Build Unshakable Brand Authority
                </h3>
                <p style={{ color: '#475569', lineHeight: '1.6', fontSize: '0.94rem', marginBottom: '20px' }}>
                  Stand out from fly-by-night operations. Secure a verified business profile that displays your legal registration, corporate history, leadership credentials, and official compensation plans in a format networkers trust.
                </p>
                <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#334155', fontSize: '0.88rem' }}>
                    <CheckCircle2 className="w-4 h-4 text-emerald-600 flex-shrink-0" />
                    <span>Official verified blue badge distinguishing legitimate brands</span>
                  </div>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#334155', fontSize: '0.88rem' }}>
                    <CheckCircle2 className="w-4 h-4 text-emerald-600 flex-shrink-0" />
                    <span>Upload downloadable PDFs, brochures, and compensation overviews</span>
                  </div>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#334155', fontSize: '0.88rem' }}>
                    <CheckCircle2 className="w-4 h-4 text-emerald-600 flex-shrink-0" />
                    <span>Direct inquiry forms that route prospects straight to your sales team</span>
                  </div>
                </div>
              </div>
            </div>

            <div className="pub-card pub-split-card">
              <div>
                <div className="pub-icon-wrapper pub-icon-emerald" style={{ marginBottom: '18px' }}>
                  <TrendingUp className="w-6 h-6" />
                </div>
                <h3 style={{ fontSize: '1.35rem', fontWeight: '800', color: '#0f172a', marginBottom: '12px' }}>
                  Laser-Focused Distributor Acquisition
                </h3>
                <p style={{ color: '#475569', lineHeight: '1.6', fontSize: '0.94rem', marginBottom: '20px' }}>
                  Stop trying to convince uninterested audiences. Reach experienced distributors who are already primed for direct sales, active builders seeking new product lines, and leaders ready to migrate entire organizations.
                </p>
                <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#334155', fontSize: '0.88rem' }}>
                    <CheckCircle2 className="w-4 h-4 text-emerald-600 flex-shrink-0" />
                    <span>Zero wasted ad impressions — 100% of users understand direct sales</span>
                  </div>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#334155', fontSize: '0.88rem' }}>
                    <CheckCircle2 className="w-4 h-4 text-emerald-600 flex-shrink-0" />
                    <span>High conversion rates on webinars, product samples, and team signups</span>
                  </div>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#334155', fontSize: '0.88rem' }}>
                    <CheckCircle2 className="w-4 h-4 text-emerald-600 flex-shrink-0" />
                    <span>Real-time analytics dashboard with cost-per-lead tracking</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* ====================================================================
          SECTION 6: WHY MEMBERS JOIN MLM BOOK
          ==================================================================== */}
      <section className="pub-section">
        <div className="pub-container">
          <div className="pub-section-header">
            <div className="pub-badge pub-badge-cyan">
              <Users className="w-3.5 h-3.5" />
              <span>For Networkers, Affiliates & Builders</span>
            </div>
            <h2 className="pub-section-title">Why Members Join MLM Book</h2>
            <p className="pub-section-desc">
              Whether you are an aspiring direct seller or a seasoned master distributor, MLM Book is the digital headquarters you have always wished existed.
            </p>
          </div>

          <div className="pub-grid-3">
            <div className="pub-card">
              <div className="pub-icon-wrapper pub-icon-indigo">
                <Globe className="w-6 h-6" />
              </div>
              <h3 style={{ fontSize: '1.25rem', fontWeight: '800', color: '#0f172a', marginBottom: '10px' }}>
                Safe from Shadowbans
              </h3>
              <p style={{ color: '#475569', fontSize: '0.93rem', lineHeight: '1.6' }}>
                Tired of having your accounts suspended on mainstream networks for talking about compensation plans or product opportunities? On MLM Book, direct sales is celebrated. Post freely, connect authentically, and build without fear.
              </p>
            </div>

            <div className="pub-card">
              <div className="pub-icon-wrapper pub-icon-emerald">
                <Coins className="w-6 h-6" />
              </div>
              <h3 style={{ fontSize: '1.25rem', fontWeight: '800', color: '#0f172a', marginBottom: '10px' }}>
                Get Paid for Your Attention
              </h3>
              <p style={{ color: '#475569', fontSize: '0.93rem', lineHeight: '1.6' }}>
                Your attention has immense economic value. Instead of enriching Silicon Valley billionaires, our automated reward engine pays you real USDT for watching videos, evaluating campaigns, and participating in the platform.
              </p>
            </div>

            <div className="pub-card">
              <div className="pub-icon-wrapper pub-icon-purple">
                <TrendingUp className="w-6 h-6" />
              </div>
              <h3 style={{ fontSize: '1.25rem', fontWeight: '800', color: '#0f172a', marginBottom: '10px' }}>
                Global Cross-Border Team Growth
              </h3>
              <p style={{ color: '#475569', fontSize: '0.93rem', lineHeight: '1.6' }}>
                Direct selling is global. Connect with leaders across North America, Europe, Asia, Africa, and Latin America. Use our automated translation and messaging to launch teams in new territories without leaving your home.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* ====================================================================
          SECTION 7: WHY CHOOSE MLM BOOK? (The Ultimate Differentiator)
          ==================================================================== */}
      <section className="pub-section" style={{ background: '#f8fafc', borderTop: '1px solid #e2e8f0', borderBottom: '1px solid #e2e8f0' }}>
        <div className="pub-container">
          <div className="pub-section-header">
            <div className="pub-badge pub-badge-amber">
              <Sparkles className="w-3.5 h-3.5" />
              <span>Competitive Advantages</span>
            </div>
            <h2 className="pub-section-title">Why Choose MLM Book?</h2>
            <p className="pub-section-desc">
              How does MLM Book compare against traditional social platforms and outdated MLM forums? See the stark difference for yourself.
            </p>
          </div>

          <div style={{ maxWidth: '960px', margin: '0 auto', background: '#ffffff', borderRadius: '20px', border: '1px solid #e2e8f0', overflow: 'hidden', boxShadow: 'var(--pub-shadow-md)' }}>
            <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr 1fr', background: '#f1f5f9', padding: '18px 24px', fontWeight: '800', fontSize: '0.9rem', color: '#0f172a', borderBottom: '1px solid #e2e8f0' }}>
              <div>Feature / Capability</div>
              <div style={{ textAlign: 'center' }}>Traditional Social Networks</div>
              <div style={{ textAlign: 'center', color: '#4f7df3' }}>{platformName}</div>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr 1fr', padding: '16px 24px', borderBottom: '1px solid #f1f5f9', alignItems: 'center' }}>
              <div>
                <strong style={{ color: '#0f172a' }}>MLM-Friendly Content Policy</strong>
                <div style={{ fontSize: '0.8rem', color: '#64748b' }}>Ability to share compensation plans & products</div>
              </div>
              <div style={{ textAlign: 'center', color: '#dc2626' }}>❌ Instant Shadowban</div>
              <div style={{ textAlign: 'center', color: '#16a34a', fontWeight: '700' }}>✅ 100% Supported</div>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr 1fr', padding: '16px 24px', borderBottom: '1px solid #f1f5f9', alignItems: 'center' }}>
              <div>
                <strong style={{ color: '#0f172a' }}>Audience Relevance</strong>
                <div style={{ fontSize: '0.8rem', color: '#64748b' }}>Percentage of users interested in network marketing</div>
              </div>
              <div style={{ textAlign: 'center', color: '#dc2626' }}>Less than 0.5%</div>
              <div style={{ textAlign: 'center', color: '#16a34a', fontWeight: '700' }}>100% Dedicated</div>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr 1fr', padding: '16px 24px', borderBottom: '1px solid #f1f5f9', alignItems: 'center' }}>
              <div>
                <strong style={{ color: '#0f172a' }}>Ad Campaign Budget Protection</strong>
                <div style={{ fontSize: '0.8rem', color: '#64748b' }}>100% Instant refund on cancelled ad budget</div>
              </div>
              <div style={{ textAlign: 'center', color: '#dc2626' }}>❌ Strict No-Refunds</div>
              <div style={{ textAlign: 'center', color: '#16a34a', fontWeight: '700' }}>✅ 100% Refunded</div>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr 1fr', padding: '16px 24px', borderBottom: '1px solid #f1f5f9', alignItems: 'center' }}>
              <div>
                <strong style={{ color: '#0f172a' }}>Direct Selling Genealogy Tree</strong>
                <div style={{ fontSize: '0.8rem', color: '#64748b' }}>Automated visual tracking of downline depth</div>
              </div>
              <div style={{ textAlign: 'center', color: '#dc2626' }}>❌ Not Available</div>
              <div style={{ textAlign: 'center', color: '#16a34a', fontWeight: '700' }}>✅ 5-Generation Tree</div>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr 1fr', padding: '16px 24px', alignItems: 'center' }}>
              <div>
                <strong style={{ color: '#0f172a' }}>Member Crypto Payouts</strong>
                <div style={{ fontSize: '0.8rem', color: '#64748b' }}>Instant daily USDT rewards for ad views & activity</div>
              </div>
              <div style={{ textAlign: 'center', color: '#dc2626' }}>$0 (Platform keeps all)</div>
              <div style={{ textAlign: 'center', color: '#16a34a', fontWeight: '700' }}>✅ Daily Web3 USDT</div>
            </div>
          </div>
        </div>
      </section>

      {/* ====================================================================
          SECTION 8: BOTTOM CTA BANNER
          ==================================================================== */}
      <section className="pub-section" style={{ background: '#ffffff' }}>
        <div className="pub-container">
          <div style={{ background: 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)', borderRadius: '28px', padding: '64px 36px', textAlign: 'center', color: '#ffffff', position: 'relative', overflow: 'hidden', boxShadow: '0 24px 60px rgba(15, 23, 42, 0.25)' }}>
            <div style={{ position: 'relative', zIndex: 1, maxWidth: '680px', margin: '0 auto' }}>
              <div className="pub-badge" style={{ background: 'rgba(255, 255, 255, 0.12)', color: '#ffffff', border: '1px solid rgba(255, 255, 255, 0.2)', marginBottom: '20px' }}>
                <Sparkles className="w-3.5 h-3.5 text-amber-400" />
                <span>Join Over 50,000+ Verified Network Leaders</span>
              </div>

              <h2 style={{ fontSize: 'clamp(2rem, 4vw, 2.85rem)', fontWeight: '800', color: '#ffffff', marginBottom: '16px', lineHeight: '1.2' }}>
                Ready to Experience the Future of Network Marketing?
              </h2>

              <p style={{ color: '#cbd5e1', fontSize: '1.05rem', lineHeight: '1.6', marginBottom: '32px' }}>
                Sign up free in under a minute. Connect with top leaders in your company, promote your business without bans, and start collecting daily USDT rewards.
              </p>

              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '16px', flexWrap: 'wrap' }}>
                <Link to="/member/register" className="pub-btn pub-btn-accent pub-btn-lg">
                  <Sparkles className="w-5 h-5" />
                  <span>Create Free Account Now</span>
                  <ArrowRight className="w-5 h-5" />
                </Link>
                <Link to="/rewards" className="pub-btn pub-btn-white pub-btn-lg">
                  <Coins className="w-5 h-5 text-amber-500" />
                  <span>View Rewards System</span>
                </Link>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}

export default LandingPage;
