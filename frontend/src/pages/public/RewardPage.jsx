import { useState, useMemo, useContext } from 'react';
import { Link } from 'react-router-dom';
import { BrandingContext } from '../../context/brandingContextDef';
import {
  Gift,
  TrendingUp,
  Wallet,
  Users,
  CheckCircle2,
  ArrowRight,
  Sparkles,
  Calculator,
  ShieldCheck,
  Zap,
  Award,
  RefreshCw,
  PlayCircle,
  PauseCircle,
  DollarSign,
  Layers,
  Lock,
  ChevronDown,
  ChevronUp,
  Check,
  HelpCircle
} from 'lucide-react';

export function RewardPage() {
  const { siteName } = useContext(BrandingContext) || {};
  const platformName = siteName || 'MLM Book';

  // Active selected rank for the deep-dive showcase
  const [activeRankKey, setActiveRankKey] = useState('advertiser');

  // FAQ Accordion State
  const [openFaq, setOpenFaq] = useState(null);
  const toggleFaq = (idx) => {
    setOpenFaq(openFaq === idx ? null : idx);
  };

  // --------------------------------------------------------------------------
  // 1. Authoritative Rank Configuration (Synced with Admin Panel)
  // --------------------------------------------------------------------------
  const rankList = [
    {
      key: 'advertiser',
      name: 'Advertiser',
      priority: 1,
      referrals: 0,
      team: 0,
      reward: 0.0250,
      color: '#3b82f6',
      badgeClass: 'pub-rank-advertiser',
      tagline: 'Day-1 Instant Starter',
      summary: 'Start earning immediately upon mobile verification — zero direct referrals and zero team required!',
      perks: [
        '$0.0250 USD per verified ad engagement',
        'Available instantly on day one of joining',
        'No sponsor downline requirement',
        'Withdrawal unlocked once minimum threshold ($5.00) is reached'
      ]
    },
    {
      key: 'influencer',
      name: 'Influencer',
      priority: 2,
      referrals: 10,
      team: 0,
      reward: 0.1000,
      color: '#a855f7',
      badgeClass: 'pub-rank-influencer',
      tagline: '4x Earnings Multiplier',
      summary: 'Invite just 10 direct verified network connections to quadruple your reward rate to 10¢ per ad!',
      perks: [
        '$0.1000 USD per verified ad view (400% higher rate!)',
        'Requires only 10 direct mobile-verified referrals',
        '0 team downline members needed',
        'Featured profile badge in community explore feeds'
      ]
    },
    {
      key: 'leaders',
      name: 'Leaders',
      priority: 3,
      referrals: 15,
      team: 100,
      reward: 0.2500,
      color: '#10b981',
      badgeClass: 'pub-rank-leaders',
      tagline: 'Quarter-Dollar Executive Tier',
      summary: 'Cultivate an active community of 100 verified members to earn a full quarter dollar ($0.25) per ad!',
      perks: [
        '$0.2500 USD per verified ad engagement (10x starter tier)',
        'Requires 15 direct referrals & 100 verified team connections',
        'Priority broadcast reach for your business page posts',
        'Expedited VIP withdrawal processing queue'
      ]
    },
    {
      key: 'pro_leaders',
      name: 'Pro Leaders',
      priority: 4,
      referrals: 25,
      team: 500,
      reward: 0.5000,
      color: '#f59e0b',
      badgeClass: 'pub-rank-pro_leaders',
      tagline: 'Half-Dollar Premier Tier',
      summary: 'Expand your organizational reach to 500 verified connections for $0.50 USD on every single sponsored campaign!',
      perks: [
        '$0.5000 USD per verified ad view (20x starter tier)',
        'Requires 25 direct referrals & 500 verified team connections',
        'Early access to exclusive high-budget sponsor campaigns',
        'Exclusive Pro Leader badge recognized network-wide'
      ]
    },
    {
      key: 'master_leaders',
      name: 'Master Leaders',
      priority: 5,
      referrals: 50,
      team: 1000,
      reward: 1.0000,
      color: '#ef4444',
      badgeClass: 'pub-rank-master_leaders',
      tagline: 'Apex $1.00 Per Ad Tier',
      summary: 'The pinnacle of leadership: earn a full $1.00 USD cash reward for every verified ad interaction!',
      perks: [
        '$1.0000 USD per verified ad engagement (40x starter tier)',
        'Requires 50 direct referrals & 1,000 verified team connections',
        'Top-tier leadership revenue share & global pool eligibility',
        'Dedicated account manager & instant automated payouts'
      ]
    }
  ];

  const currentSelectedRank = rankList.find((r) => r.key === activeRankKey) || rankList[0];

  // --------------------------------------------------------------------------
  // 2. Interactive Calculator Simulator State & Resolver
  // --------------------------------------------------------------------------
  const [calcReferrals, setCalcReferrals] = useState(10);
  const [calcTeam, setCalcTeam] = useState(50);
  const [calcDailyAds, setCalcDailyAds] = useState(15);

  const simulation = useMemo(() => {
    // Exact logic from RewardRankResolver.php
    let qualified = rankList[0]; // Default Advertiser (0 direct, 0 team)
    
    // Evaluate descending priority order
    for (let i = rankList.length - 1; i >= 0; i--) {
      const r = rankList[i];
      if (calcReferrals >= r.referrals && calcTeam >= r.team) {
        qualified = r;
        break;
      }
    }

    // Determine next target rank
    const nextRank = rankList.find((r) => r.priority > qualified.priority);
    const referralsNeeded = nextRank ? Math.max(0, nextRank.referrals - calcReferrals) : 0;
    const teamNeeded = nextRank ? Math.max(0, nextRank.team - calcTeam) : 0;

    // Daily & Monthly projections
    const dailyEarnings = calcDailyAds * qualified.reward;
    const monthlyEarnings = dailyEarnings * 30;

    return {
      qualifiedRank: qualified,
      nextRank,
      referralsNeeded,
      teamNeeded,
      dailyEarnings: Math.round(dailyEarnings * 10000) / 10000,
      monthlyEarnings: Math.round(monthlyEarnings * 100) / 100
    };
  }, [calcReferrals, calcTeam, calcDailyAds]);

  // --------------------------------------------------------------------------
  // 3. FAQ Items
  // --------------------------------------------------------------------------
  const faqs = [
    {
      q: 'Do I have to invest money or pay a fee to earn rewards on MLM Book?',
      a: 'Absolutely not! Joining MLM Book is 100% free. Any registered member who completes mobile/WhatsApp verification qualifies for the Advertiser rank on day one ($0.0250 per ad view) with zero referrals or fees required.'
    },
    {
      q: 'If I close or stop my ad campaign, where does my unspent money go?',
      a: 'Your funds are 100% protected. If you "Stop/Pause" a campaign, the remaining budget is safely frozen inside the campaign for you to restart anytime. If you "Close/Cancel" the campaign, 100% of the remaining unspent amount is INSTANTLY refunded back to your Fund Wallet with ZERO deductions and ZERO penalties. You can use it for another ad or withdraw it anytime directly.'
    },
    {
      q: 'Can I earn rewards from viewing my own ads or posts?',
      a: 'No. To guarantee genuine value for business advertisers, the platform enforces an authoritative rule (OWNER_REWARD_PROHIBITED) that prevents campaign owners from earning rewards on their own campaigns.'
    },
    {
      q: 'Where do service charges apply on MLM Book?',
      a: 'Service charge applies ONLY on Fund Deposits, and its percentage is dynamically configured by the Admin in platform settings. There is 0.00% service charge on creating ads, zero deductions on earning rewards, and 100% full payout on withdrawals.'
    },
    {
      q: 'How fast are withdrawal requests processed?',
      a: 'Withdrawals are sent via Web3 USDT (BEP-20) blockchain transfers. Once verified by our automated risk and admin review systems, the transaction hash (TXID) is generated and funds arrive directly in your Web3 wallet.'
    }
  ];

  return (
    <div style={{ padding: '60px 0 90px' }}>
      <div className="pub-container">
        {/* ====================================================================
            1. HERO SECTION
            ==================================================================== */}
        <div className="pub-section-header" style={{ marginBottom: '50px' }}>
          <div className="pub-badge pub-badge-emerald pub-anim-pulse" style={{ marginBottom: '16px' }}>
            <Sparkles className="w-4 h-4 text-emerald-600" />
            <span>100% Transparent Pay-Per-Engagement Platform</span>
          </div>

          <h1 className="pub-title-hero" style={{ fontSize: 'clamp(2.3rem, 4.8vw, 3.6rem)' }}>
            Transparent Ranks. Real USDT Rewards. <br />
            <span className="pub-title-gradient-emerald">Guaranteed Ad Fund Protection.</span>
          </h1>

          <p className="pub-desc-hero" style={{ margin: '0 auto', maxWidth: '780px' }}>
            Welcome to the social economy built exclusively for networkers. Discover how our dynamic rank tiers pay you up to <strong style={{ color: '#0f172a' }}>$1.00 USD per ad</strong>, how advertisers launch campaigns, and how remaining budgets are <strong style={{ color: '#059669' }}>100% refunded</strong> without penalty.
          </p>

          {/* Quick Metrics Bar */}
          <div
            style={{
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '24px',
              flexWrap: 'wrap',
              marginTop: '32px'
            }}
          >
            <div style={{ background: '#ffffff', padding: '12px 20px', borderRadius: '12px', border: '1px solid #e2e8f0', boxShadow: 'var(--pub-shadow-sm)', display: 'flex', alignItems: 'center', gap: '10px' }}>
              <Award className="w-5 h-5 text-blue-600" />
              <div style={{ textAlign: 'left' }}>
                <div style={{ fontSize: '0.72rem', color: '#64748b', fontWeight: '700', textTransform: 'uppercase' }}>Starter Reward</div>
                <div style={{ fontSize: '1rem', fontWeight: '800', color: '#0f172a' }}>$0.0250 / Ad (0 Referrals)</div>
              </div>
            </div>

            <div style={{ background: '#ffffff', padding: '12px 20px', borderRadius: '12px', border: '1px solid #e2e8f0', boxShadow: 'var(--pub-shadow-sm)', display: 'flex', alignItems: 'center', gap: '10px' }}>
              <TrendingUp className="w-5 h-5 text-emerald-600" />
              <div style={{ textAlign: 'left' }}>
                <div style={{ fontSize: '0.72rem', color: '#64748b', fontWeight: '700', textTransform: 'uppercase' }}>Apex Rank Reward</div>
                <div style={{ fontSize: '1rem', fontWeight: '800', color: '#0f172a' }}>$1.0000 / Ad (Master Leader)</div>
              </div>
            </div>

            <div style={{ background: '#ffffff', padding: '12px 20px', borderRadius: '12px', border: '1px solid #e2e8f0', boxShadow: 'var(--pub-shadow-sm)', display: 'flex', alignItems: 'center', gap: '10px' }}>
              <ShieldCheck className="w-5 h-5 text-indigo-600" />
              <div style={{ textAlign: 'left' }}>
                <div style={{ fontSize: '0.72rem', color: '#64748b', fontWeight: '700', textTransform: 'uppercase' }}>Ad Close Refund</div>
                <div style={{ fontSize: '1rem', fontWeight: '800', color: '#059669' }}>100% Instant To Fund Wallet</div>
              </div>
            </div>
          </div>
        </div>

        {/* ====================================================================
            2. SECTION 1: RANK-WISE REWARD TIERS (Interactive Cards)
            ==================================================================== */}
        <section style={{ marginBottom: '80px' }}>
          <div className="pub-section-header" style={{ marginBottom: '32px' }}>
            <div className="pub-badge pub-badge-indigo">
              <Award className="w-4 h-4" />
              <span>Compensation Structure</span>
            </div>
            <h2 className="pub-section-title">5-Tier Rank & Reward Matrix</h2>
            <p className="pub-section-desc">
              Your reward per ad scales dynamically based on your verified direct referrals and verified community team connections. Click any card below to explore full details.
            </p>
          </div>

          {/* 5 Rank Cards Grid */}
          <div className="pub-rank-grid">
            {rankList.map((rank) => {
              const isSelected = activeRankKey === rank.key;
              return (
                <div
                  key={rank.key}
                  className={`pub-rank-card ${rank.badgeClass} ${isSelected ? 'active' : ''}`}
                  onClick={() => setActiveRankKey(rank.key)}
                >
                  <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '8px' }}>
                    <span className="pub-rank-badge" style={{ backgroundColor: rank.color }}>
                      <Award className="w-3.5 h-3.5" />
                      <span>{rank.name}</span>
                    </span>
                    <span style={{ fontSize: '0.75rem', fontWeight: '700', color: '#94a3b8' }}>
                      Tier #{rank.priority}
                    </span>
                  </div>

                  <div className="pub-rank-reward-val">
                    ${rank.reward.toFixed(4)}{' '}
                    <span style={{ fontSize: '0.85rem', fontWeight: '600', color: '#64748b' }}>USD / ad</span>
                  </div>

                  <div style={{ borderTop: '1px solid #f1f5f9', margin: '12px 0 10px', paddingTop: '10px' }}>
                    <div className="pub-rank-meta-row">
                      <Users className="w-3.5 h-3.5" style={{ color: rank.color }} />
                      <span><strong>{rank.referrals}</strong> Direct Referrals</span>
                    </div>
                    <div className="pub-rank-meta-row">
                      <Layers className="w-3.5 h-3.5" style={{ color: rank.color }} />
                      <span><strong>{rank.team.toLocaleString()}</strong> Team Connections</span>
                    </div>
                  </div>

                  <div style={{ marginTop: 'auto', paddingTop: '10px', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                    <span style={{ fontSize: '0.75rem', fontWeight: '600', color: rank.color }}>
                      {isSelected ? '● Currently Viewing' : 'Click to inspect'}
                    </span>
                    {isSelected && <Check className="w-4 h-4" style={{ color: rank.color }} />}
                  </div>
                </div>
              );
            })}
          </div>

          {/* Active Rank Deep Dive Card */}
          <div
            className="pub-card"
            style={{
              padding: '36px',
              border: `2px solid ${currentSelectedRank.color}`,
              background: 'linear-gradient(180deg, #ffffff 0%, #fafafa 100%)',
              boxShadow: 'var(--pub-shadow-lg)'
            }}
          >
            <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', flexWrap: 'wrap', gap: '20px', marginBottom: '24px' }}>
              <div>
                <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '8px' }}>
                  <span
                    style={{
                      backgroundColor: currentSelectedRank.color,
                      color: '#ffffff',
                      padding: '4px 12px',
                      borderRadius: '9999px',
                      fontSize: '0.85rem',
                      fontWeight: '800',
                      display: 'inline-flex',
                      alignItems: 'center',
                      gap: '6px'
                    }}
                  >
                    <Award className="w-4 h-4" />
                    <span>Rank: {currentSelectedRank.name}</span>
                  </span>
                  <span style={{ fontSize: '0.9rem', color: '#64748b', fontWeight: '600' }}>
                    ({currentSelectedRank.tagline})
                  </span>
                </div>
                <h3 style={{ fontSize: '1.6rem', fontWeight: '800', color: '#0f172a', margin: 0 }}>
                  Earn <span style={{ color: currentSelectedRank.color }}>${currentSelectedRank.reward.toFixed(4)} USD</span> on Every Ad Engagement
                </h3>
                <p style={{ color: '#475569', fontSize: '0.95rem', marginTop: '6px', maxWidth: '700px' }}>
                  {currentSelectedRank.summary}
                </p>
              </div>

              <div
                style={{
                  background: '#ffffff',
                  border: '1px solid #e2e8f0',
                  borderRadius: '16px',
                  padding: '16px 24px',
                  textAlign: 'center',
                  boxShadow: 'var(--pub-shadow-sm)'
                }}
              >
                <div style={{ fontSize: '0.78rem', color: '#64748b', fontWeight: '700', textTransform: 'uppercase' }}>
                  Required Metrics
                </div>
                <div style={{ fontSize: '1.25rem', fontWeight: '800', color: '#0f172a', marginTop: '4px' }}>
                  {currentSelectedRank.referrals} Direct • {currentSelectedRank.team} Team
                </div>
                <span style={{ fontSize: '0.75rem', color: '#10b981', fontWeight: '600' }}>
                  ✓ Mobile Verification Mandatory
                </span>
              </div>
            </div>

            {/* Perks Grid */}
            <div style={{ background: '#ffffff', borderRadius: '14px', border: '1px solid #e2e8f0', padding: '24px' }}>
              <div style={{ fontSize: '0.85rem', fontWeight: '700', color: '#0f172a', marginBottom: '14px', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                Key Benefits & Privileges at {currentSelectedRank.name} Rank:
              </div>
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '14px' }}>
                {currentSelectedRank.perks.map((perk, pIdx) => (
                  <div key={pIdx} style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                    <div
                      style={{
                        width: '24px',
                        height: '24px',
                        borderRadius: '50%',
                        backgroundColor: `${currentSelectedRank.color}15`,
                        color: currentSelectedRank.color,
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        flexShrink: 0
                      }}
                    >
                      <Check className="w-3.5 h-3.5" />
                    </div>
                    <span style={{ color: '#334155', fontSize: '0.92rem', fontWeight: '500' }}>{perk}</span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </section>

        {/* ====================================================================
            3. SECTION 2: HOW AD ENGINE WORKS (Interactive Animated Pipeline)
            ==================================================================== */}
        <section style={{ marginBottom: '80px' }}>
          <div className="pub-section-header" style={{ marginBottom: '36px' }}>
            <div className="pub-badge pub-badge-amber">
              <Zap className="w-4 h-4" />
              <span>Lifecycle Workflow</span>
            </div>
            <h2 className="pub-section-title">How the Ad & Reward Engine Works</h2>
            <p className="pub-section-desc">
              From advertiser budget allocation to genuine member engagement and real-time wallet payout — transparent at every stage.
            </p>
          </div>

          <div className="pub-flow-grid">
            {/* Step 1 */}
            <div className="pub-flow-card">
              <span className="pub-flow-num">1</span>
              <div className="pub-icon-wrapper pub-icon-cyan" style={{ width: '48px', height: '48px', marginBottom: '16px' }}>
                <PlayCircle className="w-6 h-6 text-sky-600" />
              </div>
              <h3 style={{ fontSize: '1.2rem', fontWeight: '800', color: '#0f172a', marginBottom: '8px' }}>
                1. Advertiser Funds Campaign
              </h3>
              <p style={{ color: '#475569', fontSize: '0.88rem', lineHeight: '1.6' }}>
                Advertisers allocate campaign budget directly from their <strong>Fund Wallet</strong>. They choose targeting, banner/video assets, and daily spend limits.
              </p>
              <div style={{ marginTop: '12px', fontSize: '0.78rem', color: '#0284c7', fontWeight: '600' }}>
                ✓ Securely locked in campaign running budget
              </div>
            </div>

            {/* Step 2 */}
            <div className="pub-flow-card">
              <span className="pub-flow-num">2</span>
              <div className="pub-icon-wrapper pub-icon-indigo" style={{ width: '48px', height: '48px', marginBottom: '16px' }}>
                <Layers className="w-6 h-6 text-indigo-600" />
              </div>
              <h3 style={{ fontSize: '1.2rem', fontWeight: '800', color: '#0f172a', marginBottom: '8px' }}>
                2. Ad Broadcast & Feed Delivery
              </h3>
              <p style={{ color: '#475569', fontSize: '0.88rem', lineHeight: '1.6' }}>
                Once approved, the ad is broadcasted across the active MLM Book feed, community explore reels, and member engagement dashboards.
              </p>
              <div style={{ marginTop: '12px', fontSize: '0.78rem', color: '#6366f1', fontWeight: '600' }}>
                ✓ Reaches 100% targeted network marketers
              </div>
            </div>

            {/* Step 3 */}
            <div className="pub-flow-card">
              <span className="pub-flow-num">3</span>
              <div className="pub-icon-wrapper pub-icon-emerald" style={{ width: '48px', height: '48px', marginBottom: '16px' }}>
                <ShieldCheck className="w-6 h-6 text-emerald-600" />
              </div>
              <h3 style={{ fontSize: '1.2rem', fontWeight: '800', color: '#0f172a', marginBottom: '8px' }}>
                3. Verified Engagement (Anti-Bot)
              </h3>
              <p style={{ color: '#475569', fontSize: '0.88rem', lineHeight: '1.6' }}>
                A member views or clicks the ad. The system enforces strict security: <strong>Mobile must be verified</strong>, <strong>owner cannot earn from self</strong>, and max 1 reward per user per campaign.
              </p>
              <div style={{ marginTop: '12px', fontSize: '0.78rem', color: '#059669', fontWeight: '600' }}>
                ✓ Zero bots & fraud prevention guarantee
              </div>
            </div>

            {/* Step 4 */}
            <div className="pub-flow-card">
              <span className="pub-flow-num">4</span>
              <div className="pub-icon-wrapper pub-icon-amber" style={{ width: '48px', height: '48px', marginBottom: '16px' }}>
                <DollarSign className="w-6 h-6 text-amber-600" />
              </div>
              <h3 style={{ fontSize: '1.2rem', fontWeight: '800', color: '#0f172a', marginBottom: '8px' }}>
                4. Instant Atomic Payout
              </h3>
              <p style={{ color: '#475569', fontSize: '0.88rem', lineHeight: '1.6' }}>
                The exact reward based on the member’s rank ($0.025 to $1.00) is deducted from the campaign’s remaining amount and <strong>credited instantly to the member's Reward balance</strong>.
              </p>
              <div style={{ marginTop: '12px', fontSize: '0.78rem', color: '#d97706', fontWeight: '600' }}>
                ✓ Real-time ledger accounting with 0 delay
              </div>
            </div>
          </div>
        </section>

        {/* ====================================================================
            4. SECTION 3: WHAT HAPPENS IF YOU CLOSE AN AD? (Unspent Budget Safety)
            ==================================================================== */}
        <section style={{ marginBottom: '80px' }}>
          <div className="pub-section-header" style={{ marginBottom: '32px' }}>
            <div className="pub-badge pub-badge-emerald">
              <ShieldCheck className="w-4 h-4" />
              <span>Advertiser Capital Protection</span>
            </div>
            <h2 className="pub-section-title">What Happens If You Stop or Close Your Ad?</h2>
            <p className="pub-section-desc">
              Wondering where your money goes if you decide not to run an ad anymore? Here is the exact backend logic protecting every cent of your budget.
            </p>
          </div>

          <div className="pub-grid-2">
            {/* Action 1: Stop / Pause */}
            <div className="pub-card" style={{ border: '1px solid #cbd5e1', background: '#ffffff', padding: '32px' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '16px' }}>
                <div style={{ width: '44px', height: '44px', borderRadius: '12px', background: '#fef3c7', color: '#d97706', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                  <PauseCircle className="w-6 h-6" />
                </div>
                <div>
                  <span style={{ fontSize: '0.72rem', fontWeight: '700', color: '#b45309', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                    Option 1: Temporary Hold
                  </span>
                  <h3 style={{ fontSize: '1.35rem', fontWeight: '800', color: '#0f172a', margin: 0 }}>
                    Stop / Pause Campaign
                  </h3>
                </div>
              </div>

              <p style={{ color: '#475569', fontSize: '0.92rem', lineHeight: '1.6', marginBottom: '20px' }}>
                If you want to temporarily halt an ad to edit graphics, update copy, or adjust target audiences:
              </p>

              <div style={{ background: '#fffbeb', border: '1px solid #fde68a', borderRadius: '12px', padding: '16px', marginBottom: '16px' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#92400e', fontWeight: '700', fontSize: '0.9rem', marginBottom: '6px' }}>
                  <Lock className="w-4 h-4 flex-shrink-0" />
                  <span>Remaining Budget is Safely Preserved</span>
                </div>
                <p style={{ color: '#78350f', fontSize: '0.85rem', margin: 0 }}>
                  Your remaining unspent amount stays 100% locked and safe on the campaign. No money is lost, and no new rewards will be deducted while stopped.
                </p>
              </div>

              <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#475569', fontSize: '0.86rem' }}>
                <CheckCircle2 className="w-4 h-4 text-amber-600 flex-shrink-0" />
                <span>You can click <strong>Restart</strong> at any time to resume delivery instantly.</span>
              </div>
            </div>

            {/* Action 2: Close / Cancel with 100% Refund */}
            <div className="pub-card" style={{ border: '2px solid #a7f3d0', background: 'linear-gradient(180deg, #ffffff 0%, #f0fdf4 100%)', padding: '32px', boxShadow: 'var(--pub-shadow-md)' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '16px' }}>
                <div style={{ width: '44px', height: '44px', borderRadius: '12px', background: '#d1fae5', color: '#059669', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                  <RefreshCw className="w-6 h-6" />
                </div>
                <div>
                  <span style={{ fontSize: '0.72rem', fontWeight: '800', color: '#059669', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                    Option 2: 100% Immediate Refund
                  </span>
                  <h3 style={{ fontSize: '1.35rem', fontWeight: '800', color: '#0f172a', margin: 0 }}>
                    Close / Cancel Campaign
                  </h3>
                </div>
              </div>

              <p style={{ color: '#475569', fontSize: '0.92rem', lineHeight: '1.6', marginBottom: '20px' }}>
                If you no longer wish to run the ad and want your remaining budget back immediately:
              </p>

              <div style={{ background: '#ecfdf5', border: '1px solid #6ee7b7', borderRadius: '12px', padding: '16px', marginBottom: '16px' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#065f46', fontWeight: '800', fontSize: '0.92rem', marginBottom: '6px' }}>
                  <CheckCircle2 className="w-4 h-4 flex-shrink-0" />
                  <span>100% Refunded to Fund Wallet</span>
                </div>
                <p style={{ color: '#047857', fontSize: '0.86rem', margin: 0 }}>
                  The backend automatically releases the exact remaining balance (<code>remaining_amount</code>) and credits it back to your <strong>Fund Wallet</strong> instantly.
                </p>
              </div>

              <div style={{ display: 'flex', flexDirection: 'column', gap: '8px', fontSize: '0.86rem', color: '#166534' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <Check className="w-4 h-4 text-emerald-600 flex-shrink-0" />
                  <span><strong>Zero Penalties</strong> — No cancellation or restocking fees charged.</span>
                </div>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <Check className="w-4 h-4 text-emerald-600 flex-shrink-0" />
                  <span><strong>100% Full Payout</strong> — Withdraw your refunded balance to your Web3 wallet anytime!</span>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* ====================================================================
            5. SECTION 4: DUAL WALLET ARCHITECTURE & WITHDRAWAL PROTOCOL
            ==================================================================== */}
        <section style={{ marginBottom: '80px' }}>
          <div className="pub-section-header" style={{ marginBottom: '32px' }}>
            <div className="pub-badge pub-badge-indigo">
              <Wallet className="w-4 h-4" />
              <span>Financial Infrastructure</span>
            </div>
            <h2 className="pub-section-title">Dual-Wallet System & Withdrawal Rules</h2>
            <p className="pub-section-desc">
              Clean separation between operating funds and earned member rewards guarantees transparent accounting and fast payouts.
            </p>
          </div>

          <div className="pub-grid-2" style={{ marginBottom: '32px' }}>
            {/* Wallet 1: Fund Wallet */}
            <div className="pub-card" style={{ padding: '32px', border: '1px solid #e2e8f0', background: '#ffffff' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '14px', marginBottom: '20px' }}>
                <div className="pub-icon-wrapper pub-icon-cyan" style={{ width: '50px', height: '50px', marginBottom: 0 }}>
                  <Wallet className="w-6 h-6 text-sky-600" />
                </div>
                <div>
                  <span style={{ fontSize: '0.72rem', fontWeight: '800', color: '#0284c7', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                    Business & Advertising
                  </span>
                  <h3 style={{ fontSize: '1.35rem', fontWeight: '800', color: '#0f172a', margin: 0 }}>
                    Fund Wallet
                  </h3>
                </div>
              </div>

              <p style={{ color: '#475569', fontSize: '0.92rem', lineHeight: '1.6', marginBottom: '20px' }}>
                Your dedicated wallet for launching campaigns, business page boosts, and peer-to-peer balance transfers.
              </p>

              <div style={{ background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: '12px', padding: '18px', display: 'flex', flexDirection: 'column', gap: '12px' }}>
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', borderBottom: '1px solid #e2e8f0', paddingBottom: '8px' }}>
                  <span style={{ color: '#64748b', fontSize: '0.85rem' }}>Deposit Methods:</span>
                  <span style={{ fontWeight: '700', color: '#0f172a', fontSize: '0.88rem' }}>Instant Web3 USDT (BEP-20)</span>
                </div>
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', borderBottom: '1px solid #e2e8f0', paddingBottom: '8px' }}>
                  <span style={{ color: '#64748b', fontSize: '0.85rem' }}>Minimum Withdrawal:</span>
                  <span style={{ fontWeight: '700', color: '#0f172a', fontSize: '0.88rem' }}>$5.00 USD</span>
                </div>
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                  <span style={{ color: '#64748b', fontSize: '0.85rem' }}>Campaign Refund Destination:</span>
                  <span style={{ fontWeight: '700', color: '#0284c7', fontSize: '0.88rem' }}>Direct to Fund Wallet</span>
                </div>
              </div>
            </div>

            {/* Wallet 2: Reward */}
            <div className="pub-card" style={{ padding: '32px', border: '1px solid #e2e8f0', background: '#ffffff' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '14px', marginBottom: '20px' }}>
                <div className="pub-icon-wrapper pub-icon-emerald" style={{ width: '50px', height: '50px', marginBottom: 0 }}>
                  <Gift className="w-6 h-6 text-emerald-600" />
                </div>
                <div>
                  <span style={{ fontSize: '0.72rem', fontWeight: '800', color: '#059669', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                    Cashable Member Rewards
                  </span>
                  <h3 style={{ fontSize: '1.35rem', fontWeight: '800', color: '#0f172a', margin: 0 }}>
                    Reward
                  </h3>
                </div>
              </div>

              <p style={{ color: '#475569', fontSize: '0.92rem', lineHeight: '1.6', marginBottom: '20px' }}>
                Collects all your verified ad view payouts, and rank bonuses in real time.
              </p>

              <div style={{ background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: '12px', padding: '18px', display: 'flex', flexDirection: 'column', gap: '12px' }}>
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', borderBottom: '1px solid #e2e8f0', paddingBottom: '8px' }}>
                  <span style={{ color: '#64748b', fontSize: '0.85rem' }}>Earning Sources:</span>
                  <span style={{ fontWeight: '700', color: '#0f172a', fontSize: '0.88rem' }}>Ad views ($0.025 - $1.00) + Referrals</span>
                </div>
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', borderBottom: '1px solid #e2e8f0', paddingBottom: '8px' }}>
                  <span style={{ color: '#64748b', fontSize: '0.85rem' }}>Minimum Withdrawal:</span>
                  <span style={{ fontWeight: '700', color: '#0f172a', fontSize: '0.88rem' }}>$5.00 USD</span>
                </div>
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                  <span style={{ color: '#64748b', fontSize: '0.85rem' }}>Rejection Guarantee:</span>
                  <span style={{ fontWeight: '700', color: '#059669', fontSize: '0.88rem' }}>100% Gross Refund to Wallet</span>
                </div>
              </div>
            </div>
          </div>

          {/* Safety & Withdrawal Process Banner */}
          <div
            style={{
              background: '#f8fafc',
              border: '1px solid #e2e8f0',
              borderRadius: '16px',
              padding: '24px 28px',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              flexWrap: 'wrap',
              gap: '20px'
            }}
          >
            <div style={{ display: 'flex', alignItems: 'center', gap: '14px' }}>
              <ShieldCheck className="w-8 h-8 text-emerald-600 flex-shrink-0" />
              <div>
                <h4 style={{ fontSize: '1.05rem', fontWeight: '800', color: '#0f172a', margin: '0 0 4px' }}>
                  Safe, Verified Withdrawal Workflow
                </h4>
                <p style={{ color: '#64748b', fontSize: '0.85rem', margin: 0 }}>
                  Enter your BEP-20 address &rarr; Request ID generated &rarr; Admin reviews & transmits on blockchain &rarr; 100% refund if ever rejected.
                </p>
              </div>
            </div>

            <Link
              to="/member/login"
              className="pub-btn pub-btn-sm pub-btn-primary"
              style={{ whiteSpace: 'nowrap' }}
            >
              <span>Access Your Wallet</span>
              <ArrowRight className="w-4 h-4" />
            </Link>
          </div>
        </section>

        {/* ====================================================================
            6. SECTION 5: INTERACTIVE EARNINGS SIMULATOR (Live Resolver Calculation)
            ==================================================================== */}
        <section style={{ marginBottom: '80px' }}>
          <div className="pub-calc-box">
            <div style={{ textAlign: 'center', maxWidth: '620px', margin: '0 auto 36px' }}>
              <div className="pub-badge pub-badge-indigo">
                <Calculator className="w-4 h-4" />
                <span>Dynamic Simulator</span>
              </div>
              <h2 style={{ fontSize: 'clamp(1.75rem, 3.5vw, 2.25rem)', fontWeight: '800', color: '#0f172a', marginBottom: '10px' }}>
                Simulate Your Rank & Earnings
              </h2>
              <p style={{ color: '#64748b', fontSize: '0.95rem' }}>
                Drag the sliders to see what rank you automatically unlock and how much you can earn every day and month with our updated payout model.
              </p>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '32px', marginBottom: '32px' }}>
              {/* Slider 1: Direct Referrals */}
              <div className="pub-slider-group">
                <div className="pub-slider-header">
                  <span className="pub-slider-label">Direct Verified Referrals</span>
                  <span className="pub-slider-val">{calcReferrals} Direct</span>
                </div>
                <input
                  type="range"
                  min="0"
                  max="60"
                  value={calcReferrals}
                  onChange={(e) => setCalcReferrals(Number(e.target.value))}
                  className="pub-range-input"
                />
                <span style={{ fontSize: '0.78rem', color: '#64748b', marginTop: '6px', display: 'block' }}>
                  Members registered directly through your sponsor link
                </span>
              </div>

              {/* Slider 2: Team Connections */}
              <div className="pub-slider-group">
                <div className="pub-slider-header">
                  <span className="pub-slider-label">Verified Team / Connections</span>
                  <span className="pub-slider-val">{calcTeam} Users</span>
                </div>
                <input
                  type="range"
                  min="0"
                  max="1200"
                  step="10"
                  value={calcTeam}
                  onChange={(e) => setCalcTeam(Number(e.target.value))}
                  className="pub-range-input"
                />
                <span style={{ fontSize: '0.78rem', color: '#64748b', marginTop: '6px', display: 'block' }}>
                  Accepted friendship connections with verified mobile
                </span>
              </div>

              {/* Slider 3: Daily Ad Views */}
              <div className="pub-slider-group">
                <div className="pub-slider-header">
                  <span className="pub-slider-label">Daily Ads Viewed</span>
                  <span className="pub-slider-val">{calcDailyAds} Ads / Day</span>
                </div>
                <input
                  type="range"
                  min="1"
                  max="50"
                  value={calcDailyAds}
                  onChange={(e) => setCalcDailyAds(Number(e.target.value))}
                  className="pub-range-input"
                />
                <span style={{ fontSize: '0.78rem', color: '#64748b', marginTop: '6px', display: 'block' }}>
                  Sponsored campaigns you explore each day
                </span>
              </div>
            </div>

            {/* Simulated Result Box */}
            <div
              style={{
                background: '#ffffff',
                border: '2px solid #e2e8f0',
                borderRadius: '20px',
                padding: '30px',
                boxShadow: 'var(--pub-shadow-md)',
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fit, minmax(260px, 1fr))',
                gap: '24px',
                alignItems: 'center'
              }}
            >
              {/* Qualified Rank Status */}
              <div>
                <div style={{ fontSize: '0.78rem', color: '#64748b', fontWeight: '700', textTransform: 'uppercase', marginBottom: '6px' }}>
                  Your Simulated Rank
                </div>
                <div style={{ display: 'inline-flex', alignItems: 'center', gap: '8px', padding: '6px 16px', borderRadius: '9999px', background: `${simulation.qualifiedRank.color}15`, color: simulation.qualifiedRank.color, fontWeight: '800', fontSize: '1.2rem', marginBottom: '8px' }}>
                  <Award className="w-5 h-5" />
                  <span>{simulation.qualifiedRank.name}</span>
                </div>
                <div style={{ fontSize: '0.88rem', color: '#475569' }}>
                  Payout Rate: <strong style={{ color: '#0f172a' }}>${simulation.qualifiedRank.reward.toFixed(4)} USD</strong> per ad
                </div>

                {simulation.nextRank && (
                  <div style={{ marginTop: '12px', fontSize: '0.8rem', color: '#6366f1', background: '#eef2ff', padding: '8px 12px', borderRadius: '8px', display: 'inline-block' }}>
                    ✦ Need {simulation.referralsNeeded > 0 ? `${simulation.referralsNeeded} more direct` : ''} {simulation.referralsNeeded > 0 && simulation.teamNeeded > 0 ? 'and ' : ''} {simulation.teamNeeded > 0 ? `${simulation.teamNeeded} more team` : ''} for <strong>{simulation.nextRank.name} (${simulation.nextRank.reward.toFixed(4)}/ad)</strong>
                  </div>
                )}
              </div>

              {/* Earnings Projections */}
              <div style={{ display: 'flex', flexDirection: 'column', gap: '14px' }}>
                <div style={{ background: '#f8fafc', padding: '16px 20px', borderRadius: '12px', border: '1px solid #e2e8f0', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                  <span style={{ color: '#64748b', fontSize: '0.9rem', fontWeight: '600' }}>Daily Potential:</span>
                  <span style={{ fontSize: '1.3rem', fontWeight: '800', color: '#0f172a' }}>
                    ${simulation.dailyEarnings.toFixed(2)} USDT
                  </span>
                </div>

                <div style={{ background: 'linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%)', padding: '18px 20px', borderRadius: '12px', border: '1px solid #a7f3d0', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                  <div>
                    <span style={{ color: '#065f46', fontSize: '0.78rem', fontWeight: '700', textTransform: 'uppercase', display: 'block' }}>Monthly Projected Rewards</span>
                    <span style={{ color: '#047857', fontSize: '0.8rem' }}>(30 Days personal engagement)</span>
                  </div>
                  <span style={{ fontSize: '1.8rem', fontWeight: '900', color: '#065f46' }}>
                    ${simulation.monthlyEarnings.toFixed(2)} USDT
                  </span>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* ====================================================================
            7. SECTION 6: FREQUENTLY ASKED QUESTIONS
            ==================================================================== */}
        <section style={{ marginBottom: '80px' }}>
          <div className="pub-section-header" style={{ marginBottom: '32px' }}>
            <div className="pub-badge pub-badge-cyan">
              <HelpCircle className="w-4 h-4" />
              <span>Got Questions?</span>
            </div>
            <h2 className="pub-section-title">Frequently Asked Questions</h2>
            <p className="pub-section-desc">
              Everything you need to know about rewards, advertising budgets, and withdrawals on {platformName}.
            </p>
          </div>

          <div style={{ maxWidth: '820px', margin: '0 auto' }}>
            {faqs.map((faq, idx) => {
              const isOpen = openFaq === idx;
              return (
                <div
                  key={idx}
                  className={`pub-faq-card ${isOpen ? 'open' : ''}`}
                  onClick={() => toggleFaq(idx)}
                >
                  <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '12px' }}>
                    <span style={{ fontSize: '1.05rem', fontWeight: '700', color: '#0f172a' }}>
                      {faq.q}
                    </span>
                    {isOpen ? (
                      <ChevronUp className="w-5 h-5 text-blue-600 flex-shrink-0" />
                    ) : (
                      <ChevronDown className="w-5 h-5 text-slate-400 flex-shrink-0" />
                    )}
                  </div>
                  {isOpen && (
                    <p style={{ color: '#475569', fontSize: '0.92rem', lineHeight: '1.65', marginTop: '12px', borderTop: '1px solid #f1f5f9', paddingTop: '12px', marginBottom: 0 }}>
                      {faq.a}
                    </p>
                  )}
                </div>
              );
            })}
          </div>
        </section>

        {/* ====================================================================
            8. BOTTOM CTA
            ==================================================================== */}
        <div style={{ textAlign: 'center', background: 'linear-gradient(180deg, #f8fafc 0%, #ffffff 100%)', border: '1px solid #e2e8f0', borderRadius: '24px', padding: '48px 32px', boxShadow: 'var(--pub-shadow-md)' }}>
          <div className="pub-badge pub-badge-emerald" style={{ marginBottom: '14px' }}>
            <Sparkles className="w-4 h-4" />
            <span>Join 100% Free • Start Earning in 60 Seconds</span>
          </div>
          <h2 style={{ fontSize: 'clamp(1.8rem, 3.8vw, 2.5rem)', fontWeight: '800', color: '#0f172a', marginBottom: '14px' }}>
            Ready to Turn Your Network Time Into Real USDT?
          </h2>
          <p style={{ color: '#64748b', fontSize: '1rem', maxWidth: '640px', margin: '0 auto 28px' }}>
            Create your account today, verify your mobile, and earn your first reward from active ad campaigns with zero upfront costs.
          </p>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '14px', flexWrap: 'wrap' }}>
            <Link to="/member/register" className="pub-btn pub-btn-accent pub-btn-lg">
              <Sparkles className="w-5 h-5" />
              <span>Register Free & Start Earning</span>
              <ArrowRight className="w-5 h-5" />
            </Link>
            <Link to="/member/login" className="pub-btn pub-btn-outline pub-btn-lg">
              <span>Member Sign In</span>
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}

export default RewardPage;
