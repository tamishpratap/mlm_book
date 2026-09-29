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
  Award
} from 'lucide-react';

export function RewardPage() {
  const { siteName } = useContext(BrandingContext) || {};
  const platformName = siteName || 'MLM Book';

  // Calculator State
  const [directReferrals, setDirectReferrals] = useState(10);
  const [dailyAdViews, setDailyAdViews] = useState(25);
  const [teamSize, setTeamSize] = useState(150);

  // Dynamic Monthly Estimate Calculation Formula
  const estimatedEarnings = useMemo(() => {
    // 1. Personal ad interactions: ~$0.05 per engagement * 30 days
    const personalAdEarnings = dailyAdViews * 0.05 * 30;

    // 2. Direct referral bonus & ongoing commission (~$2.50/month active direct member)
    const directReferralEarnings = directReferrals * 2.50;

    // 3. Team downline overriding volume (~$0.45/month per active downline member)
    const downlineEarnings = teamSize * 0.45;

    // Total monthly projected USDT
    const total = personalAdEarnings + directReferralEarnings + downlineEarnings;
    return Math.round(total * 100) / 100;
  }, [directReferrals, dailyAdViews, teamSize]);

  const earningStreams = [
    {
      title: '1. Ad Engagement & Click Rewards',
      icon: <TrendingUp className="w-6 h-6 text-blue-600" />,
      badge: 'Daily Active Income',
      desc: 'Advertisers fund targeted campaigns directly via our advertising engine. Every verified view, video play, or click transfers instant reward credits straight into your Reward Wallet.',
      points: [
        'Earn up to $0.10+ per verified ad engagement',
        'Daily ad viewing caps prevent fatigue and ensure high advertiser ROI',
        'Instant credit without pending approval queues'
      ]
    },
    {
      title: '2. Multi-Level Introducer Commissions',
      icon: <Users className="w-6 h-6 text-indigo-600" />,
      badge: 'Network Leverage',
      desc: 'Introduce new members using your unique sponsor link. You earn immediate direct bonuses on their initial activity plus multi-generation overriding commissions across team depth.',
      points: [
        'Direct sponsor bonuses on active referral onboarding',
        'Generational commissions across multiple downline tiers',
        'Automated genealogy tree tracking in member dashboard'
      ]
    },
    {
      title: '3. Rank Pools & Leadership Bonuses',
      icon: <Award className="w-6 h-6 text-amber-600" />,
      badge: 'Executive Milestones',
      desc: 'As your personal network and active downline grow, you unlock achievement ranks (Bronze, Silver, Gold, Platinum, Diamond) granting shares of our global platform profit pool.',
      points: [
        'Dedicated monthly pool distributions for qualified ranks',
        'Lifetime badges displayed on member and community profiles',
        'Higher daily withdrawal limits and VIP priority support'
      ]
    },
    {
      title: '4. Community & Content Creator Bounties',
      icon: <Zap className="w-6 h-6 text-emerald-600" />,
      badge: 'Creator Economy',
      desc: 'Top community leaders who cultivate vibrant discussion hubs and creators who publish viral video posts receive platform creator bounties directly from community growth funds.',
      points: [
        'Creator incentives for highly engaged posts and video watch reels',
        'Community moderation milestone rewards',
        'Direct tips and support from community followers'
      ]
    }
  ];

  return (
    <div style={{ padding: '60px 0 80px' }}>
      <div className="pub-container">
        {/* ====================================================================
            1. HERO SECTION
            ==================================================================== */}
        <div className="pub-section-header" style={{ marginBottom: '60px' }}>
          <div className="pub-badge pub-badge-emerald">
            <span className="pub-badge-dot"></span>
            <span>Transparent Tokenomics & Compensation</span>
          </div>
          <h1 className="pub-title-hero" style={{ fontSize: 'clamp(2.25rem, 4.5vw, 3.5rem)' }}>
            Empowering Your Time With <br />
            <span className="pub-title-gradient-emerald">Real Member Rewards</span>
          </h1>
          <p className="pub-desc-hero" style={{ margin: '0 auto' }}>
            Unlike traditional social media that keeps 100% of user-generated ad revenue, {platformName} redistributes economic value back to the members who create, engage, and grow our network.
          </p>
        </div>

        {/* ====================================================================
            2. EARNING STREAMS GRID
            ==================================================================== */}
        <div className="pub-grid-2" style={{ marginBottom: '80px' }}>
          {earningStreams.map((stream, idx) => (
            <div key={idx} className="pub-card" style={{ padding: '36px' }}>
              <div className="pub-card-glow-line"></div>
              
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '16px' }}>
                <div className="pub-icon-wrapper" style={{ width: '48px', height: '48px', marginBottom: 0, background: '#f8fafc', border: '1px solid #e2e8f0' }}>
                  {stream.icon}
                </div>
                <span className="pub-badge" style={{ margin: 0, fontSize: '0.72rem', padding: '4px 10px' }}>
                  {stream.badge}
                </span>
              </div>

              <h2 style={{ fontSize: '1.35rem', fontWeight: '800', color: '#0f172a', marginBottom: '12px' }}>
                {stream.title}
              </h2>

              <p style={{ color: '#475569', fontSize: '0.95rem', lineHeight: '1.6', marginBottom: '20px' }}>
                {stream.desc}
              </p>

              <ul className="pub-checklist">
                {stream.points.map((pt, pIdx) => (
                  <li key={pIdx} className="pub-checklist-item">
                    <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                    <span>{pt}</span>
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>

        {/* ====================================================================
            3. DUAL-WALLET ARCHITECTURE COMPARISON
            ==================================================================== */}
        <section style={{ marginBottom: '80px' }}>
          <div className="pub-section-header">
            <div className="pub-badge pub-badge-amber">
              <span className="pub-badge-dot"></span>
              <span>Financial Infrastructure</span>
            </div>
            <h2 className="pub-section-title">The Dual-Wallet System Explained</h2>
            <p className="pub-section-desc">
              To guarantee bulletproof accounting, security compliance, and fraud protection, {platformName} operates two dedicated wallet channels.
            </p>
          </div>

          <div className="pub-grid-2">
            {/* Fund Wallet Card */}
            <div className="pub-card" style={{ border: '1px solid #e2e8f0', background: '#ffffff', boxShadow: 'var(--pub-shadow-md)' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '14px', marginBottom: '20px' }}>
                <div className="pub-icon-wrapper pub-icon-cyan" style={{ width: '52px', height: '52px', marginBottom: 0 }}>
                  <Wallet className="w-6 h-6" />
                </div>
                <div>
                  <span style={{ fontSize: '0.75rem', fontWeight: '700', color: '#0284c7', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                    Business & Operations
                  </span>
                  <h3 style={{ fontSize: '1.4rem', fontWeight: '800', color: '#0f172a', margin: 0 }}>
                    Fund Wallet
                  </h3>
                </div>
              </div>

              <p style={{ color: '#475569', lineHeight: '1.6', fontSize: '0.95rem', marginBottom: '24px' }}>
                The Fund Wallet is your operational capital hub. Use it to deposit funds, deploy marketing campaigns, sponsor events, and run verified business features.
              </p>

              <div style={{ background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: '12px', padding: '16px', display: 'flex', flexDirection: 'column', gap: '10px' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#334155', fontSize: '0.88rem' }}>
                  <CheckCircle2 className="w-4 h-4 text-cyan-600 flex-shrink-0" />
                  <span>Funding pay-per-click targeted ad campaigns</span>
                </div>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#334155', fontSize: '0.88rem' }}>
                  <CheckCircle2 className="w-4 h-4 text-cyan-600 flex-shrink-0" />
                  <span>Business page upgrades & verification fees</span>
                </div>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#334155', fontSize: '0.88rem' }}>
                  <CheckCircle2 className="w-4 h-4 text-cyan-600 flex-shrink-0" />
                  <span>Peer-to-peer balance transfers to team members</span>
                </div>
              </div>
            </div>

            {/* Reward Wallet Card */}
            <div className="pub-card" style={{ border: '1px solid #e2e8f0', background: '#ffffff', boxShadow: 'var(--pub-shadow-md)' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '14px', marginBottom: '20px' }}>
                <div className="pub-icon-wrapper pub-icon-emerald" style={{ width: '52px', height: '52px', marginBottom: 0 }}>
                  <Gift className="w-6 h-6" />
                </div>
                <div>
                  <span style={{ fontSize: '0.75rem', fontWeight: '700', color: '#059669', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                    Cashable Income
                  </span>
                  <h3 style={{ fontSize: '1.4rem', fontWeight: '800', color: '#0f172a', margin: 0 }}>
                    Reward Wallet
                  </h3>
                </div>
              </div>

              <p style={{ color: '#475569', lineHeight: '1.6', fontSize: '0.95rem', marginBottom: '24px' }}>
                The Reward Wallet is exclusively for your accumulated earnings. All engagement rewards, downline referral commissions, and rank bonuses accumulate here.
              </p>

              <div style={{ background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: '12px', padding: '16px', display: 'flex', flexDirection: 'column', gap: '10px' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#334155', fontSize: '0.88rem' }}>
                  <CheckCircle2 className="w-4 h-4 text-emerald-600 flex-shrink-0" />
                  <span>Ad click & engagement payouts received automatically</span>
                </div>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#334155', fontSize: '0.88rem' }}>
                  <CheckCircle2 className="w-4 h-4 text-emerald-600 flex-shrink-0" />
                  <span>Direct Web3 USDT withdrawals to external address</span>
                </div>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#334155', fontSize: '0.88rem' }}>
                  <CheckCircle2 className="w-4 h-4 text-emerald-600 flex-shrink-0" />
                  <span>Complete tamper-proof ledger audit histories</span>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* ====================================================================
            4. INTERACTIVE REWARD CALCULATOR (Light Theme)
            ==================================================================== */}
        <section style={{ marginBottom: '80px' }}>
          <div className="pub-calc-box">
            <div style={{ textAlign: 'center', maxWidth: '600px', margin: '0 auto 36px' }}>
              <div className="pub-badge pub-badge-indigo">
                <Calculator className="w-4 h-4" />
                <span>Income Simulator</span>
              </div>
              <h2 style={{ fontSize: 'clamp(1.75rem, 3.5vw, 2.25rem)', fontWeight: '800', color: '#0f172a', marginBottom: '10px' }}>
                Estimate Your Monthly Potential
              </h2>
              <p style={{ color: '#64748b', fontSize: '0.95rem' }}>
                Adjust the sliders below to visualize potential monthly earnings based on your personal ad engagement and network team size.
              </p>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '32px' }}>
              {/* Slider 1: Direct Referrals */}
              <div className="pub-slider-group">
                <div className="pub-slider-header">
                  <span className="pub-slider-label">Direct Referrals (Tier 1)</span>
                  <span className="pub-slider-val">{directReferrals} Members</span>
                </div>
                <input
                  type="range"
                  min="0"
                  max="100"
                  value={directReferrals}
                  onChange={(e) => setDirectReferrals(Number(e.target.value))}
                  className="pub-range-input"
                />
                <span style={{ fontSize: '0.78rem', color: '#64748b', marginTop: '6px', display: 'block' }}>
                  Active members you personally introduced
                </span>
              </div>

              {/* Slider 2: Daily Ad Engagements */}
              <div className="pub-slider-group">
                <div className="pub-slider-header">
                  <span className="pub-slider-label">Daily Ad Engagements</span>
                  <span className="pub-slider-val">{dailyAdViews} Ads / Day</span>
                </div>
                <input
                  type="range"
                  min="5"
                  max="100"
                  value={dailyAdViews}
                  onChange={(e) => setDailyAdViews(Number(e.target.value))}
                  className="pub-range-input"
                />
                <span style={{ fontSize: '0.78rem', color: '#64748b', marginTop: '6px', display: 'block' }}>
                  Sponsored campaigns you view or click each day
                </span>
              </div>

              {/* Slider 3: Downline Network Size */}
              <div className="pub-slider-group">
                <div className="pub-slider-header">
                  <span className="pub-slider-label">Total Downline Team Size</span>
                  <span className="pub-slider-val">{teamSize} Members</span>
                </div>
                <input
                  type="range"
                  min="0"
                  max="1500"
                  step="10"
                  value={teamSize}
                  onChange={(e) => setTeamSize(Number(e.target.value))}
                  className="pub-range-input"
                />
                <span style={{ fontSize: '0.78rem', color: '#64748b', marginTop: '6px', display: 'block' }}>
                  Combined indirect members across generational tiers
                </span>
              </div>
            </div>

            {/* Projected Output Card */}
            <div className="pub-calc-result-box">
              <div className="pub-calc-result-title">Projected Monthly Earnings</div>
              <div className="pub-calc-result-number">
                ${estimatedEarnings.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} USDT
              </div>
              <p style={{ color: '#065f46', fontSize: '0.82rem', margin: '8px 0 0' }}>
                *Simulated calculation for illustrative purposes based on standard platform engagement metrics and active advertiser budgets.
              </p>
            </div>
          </div>
        </section>

        {/* ====================================================================
            5. WITHDRAWAL RULES & SAFETY
            ==================================================================== */}
        <section className="pub-card" style={{ padding: '36px', marginBottom: '60px' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '14px', marginBottom: '20px' }}>
            <div className="pub-icon-wrapper pub-icon-emerald" style={{ width: '48px', height: '48px', marginBottom: 0 }}>
              <ShieldCheck className="w-6 h-6" />
            </div>
            <div>
              <h3 style={{ fontSize: '1.35rem', fontWeight: '800', color: '#0f172a', margin: 0 }}>
                Withdrawal Protocol & Security
              </h3>
              <p style={{ color: '#64748b', fontSize: '0.88rem', margin: 0 }}>
                Transparent payout requirements protecting member assets against fraud.
              </p>
            </div>
          </div>

          <div className="pub-grid-3">
            <div style={{ background: '#f8fafc', padding: '18px', borderRadius: '12px', border: '1px solid #e2e8f0' }}>
              <div style={{ fontWeight: '700', color: '#0f172a', marginBottom: '6px' }}>1. Verified Web3 Address</div>
              <p style={{ color: '#475569', fontSize: '0.85rem', margin: 0 }}>
                Bind and verify your BEP-20 / TRC-20 USDT wallet address from your Account Security settings to enable withdrawals.
              </p>
            </div>

            <div style={{ background: '#f8fafc', padding: '18px', borderRadius: '12px', border: '1px solid #e2e8f0' }}>
              <div style={{ fontWeight: '700', color: '#0f172a', marginBottom: '6px' }}>2. Fair-Play Anti-Bot Checks</div>
              <p style={{ color: '#475569', fontSize: '0.85rem', margin: 0 }}>
                Our automated algorithm verifies genuine engagement signals to keep our ad network high quality and compliant.
              </p>
            </div>

            <div style={{ background: '#f8fafc', padding: '18px', borderRadius: '12px', border: '1px solid #e2e8f0' }}>
              <div style={{ fontWeight: '700', color: '#0f172a', marginBottom: '6px' }}>3. Rapid Processing Times</div>
              <p style={{ color: '#475569', fontSize: '0.85rem', margin: 0 }}>
                Approved withdrawal requests are automatically dispatched onto the blockchain ledger with full TXID tracking.
              </p>
            </div>
          </div>
        </section>

        {/* ====================================================================
            6. BOTTOM CTA
            ==================================================================== */}
        <div style={{ textAlign: 'center' }}>
          <h2 style={{ fontSize: '1.75rem', fontWeight: '800', color: '#0f172a', marginBottom: '14px' }}>
            Start Building Your Reward Stream Today
          </h2>
          <p style={{ color: '#64748b', fontSize: '1rem', marginBottom: '24px' }}>
            Free registration. No subscription commitments. Immediate earning capability.
          </p>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '14px', flexWrap: 'wrap' }}>
            <Link to="/member/register" className="pub-btn pub-btn-accent pub-btn-lg">
              <Sparkles className="w-5 h-5" />
              <span>Register & Start Earning</span>
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
