import { useContext, useEffect, useRef } from 'react';
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
  PlayCircle,
  MessageSquare,
  Award,
  CheckCircle2,
  ChevronRight,
  Layers,
  Gift
} from 'lucide-react';

export function LandingPage() {
  const { siteName } = useContext(BrandingContext) || {};
  const { isAuthenticated } = useContext(AuthContext) || {};
  const canvasRef = useRef(null);

  const platformName = siteName || 'MLM Book';

  // Live Interactive Network Constellation Animation
  useEffect(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    const heroSection = canvas.closest('.pub-hero-section') || canvas.parentElement;
    if (!heroSection) return;

    const ctx = canvas.getContext('2d');
    let animationFrameId;
    let width = 0;
    let height = 0;

    const resize = () => {
      if (!canvas || !heroSection) return;
      const rect = heroSection.getBoundingClientRect();
      const dpr = window.devicePixelRatio || 1;
      width = rect.width;
      height = rect.height;
      canvas.width = width * dpr;
      canvas.height = height * dpr;
      canvas.style.width = `${width}px`;
      canvas.style.height = `${height}px`;
      ctx.setTransform(1, 0, 0, 1, 0, 0);
      ctx.scale(dpr, dpr);
    };

    resize();
    window.addEventListener('resize', resize);

    // Mouse interaction
    let mouse = { x: null, y: null, radius: 160 };
    const handleMouseMove = (e) => {
      const rect = heroSection.getBoundingClientRect();
      mouse.x = e.clientX - rect.left;
      mouse.y = e.clientY - rect.top;
    };
    const handleMouseLeave = () => {
      mouse.x = null;
      mouse.y = null;
    };

    heroSection.addEventListener('mousemove', handleMouseMove);
    heroSection.addEventListener('mouseleave', handleMouseLeave);

    // Network Node Configuration
    const count = Math.min(Math.max(Math.floor(width / 26), 32), 65);
    const nodes = [];
    const colors = ['#3b82f6', '#10b981', '#6366f1', '#0ea5e9'];

    for (let i = 0; i < count; i++) {
      nodes.push({
        x: Math.random() * (width || 1200),
        y: Math.random() * (height || 600),
        vx: (Math.random() - 0.5) * 1.1,
        vy: (Math.random() - 0.5) * 1.1,
        radius: Math.random() * 2.5 + 2.5,
        color: colors[i % colors.length],
        pulse: Math.random() * Math.PI * 2,
        pulseSpeed: 0.03 + Math.random() * 0.02,
      });
    }

    const maxDist = 140;

    const animate = () => {
      ctx.clearRect(0, 0, width, height);

      // 1. Draw connecting web lines between nearby nodes
      for (let i = 0; i < nodes.length; i++) {
        for (let j = i + 1; j < nodes.length; j++) {
          const dx = nodes[i].x - nodes[j].x;
          const dy = nodes[i].y - nodes[j].y;
          const dist = Math.sqrt(dx * dx + dy * dy);

          if (dist < maxDist) {
            const alpha = (1 - dist / maxDist) * 0.35;
            ctx.beginPath();
            ctx.moveTo(nodes[i].x, nodes[i].y);
            ctx.lineTo(nodes[j].x, nodes[j].y);
            ctx.strokeStyle = `rgba(79, 125, 243, ${alpha})`;
            ctx.lineWidth = 1.3;
            ctx.stroke();
          }
        }
      }

      // 2. Draw dynamic interactive connections to mouse cursor
      if (mouse.x !== null && mouse.y !== null) {
        for (let i = 0; i < nodes.length; i++) {
          const dx = nodes[i].x - mouse.x;
          const dy = nodes[i].y - mouse.y;
          const dist = Math.sqrt(dx * dx + dy * dy);

          if (dist < mouse.radius) {
            const alpha = (1 - dist / mouse.radius) * 0.6;
            ctx.beginPath();
            ctx.moveTo(nodes[i].x, nodes[i].y);
            ctx.lineTo(mouse.x, mouse.y);
            ctx.strokeStyle = `rgba(16, 185, 129, ${alpha})`;
            ctx.lineWidth = 1.6;
            ctx.stroke();
          }
        }
      }

      // 3. Move, pulse, and render glowing nodes
      for (let i = 0; i < nodes.length; i++) {
        const n = nodes[i];
        n.x += n.vx;
        n.y += n.vy;

        if (n.x < 0 || n.x > width) n.vx *= -1;
        if (n.y < 0 || n.y > height) n.vy *= -1;

        n.pulse += n.pulseSpeed;
        const currentRadius = n.radius + Math.sin(n.pulse) * 0.9;

        // Outer glow halo
        ctx.beginPath();
        ctx.arc(n.x, n.y, currentRadius + 3.5, 0, Math.PI * 2);
        ctx.fillStyle = n.color;
        ctx.globalAlpha = 0.25;
        ctx.fill();

        // Inner solid core
        ctx.beginPath();
        ctx.arc(n.x, n.y, currentRadius, 0, Math.PI * 2);
        ctx.fillStyle = n.color;
        ctx.globalAlpha = 0.9;
        ctx.fill();
        ctx.globalAlpha = 1;
      }

      animationFrameId = requestAnimationFrame(animate);
    };

    animate();

    return () => {
      window.removeEventListener('resize', resize);
      heroSection.removeEventListener('mousemove', handleMouseMove);
      heroSection.removeEventListener('mouseleave', handleMouseLeave);
      cancelAnimationFrame(animationFrameId);
    };
  }, []);

  return (
    <div>
      {/* ====================================================================
          1. HERO SECTION (Living Network Constellation & Ecosystem Preview)
          ==================================================================== */}
      <section className="pub-hero-section">
        {/* Modern Fluid Aurora & Interactive Network Layer */}
        <div className="pub-hero-aurora-bg" aria-hidden="true">
          {/* Live Interactive Constellation Canvas */}
          <canvas ref={canvasRef} className="pub-network-canvas" />

          {/* Subtle Clean Tech Dot Grid */}
          <div className="pub-hero-dots"></div>

          {/* Morphing Liquid Aurora Mesh Gradient Blobs */}
          <div className="pub-aurora-blob blob-1"></div>
          <div className="pub-aurora-blob blob-2"></div>
          <div className="pub-aurora-blob blob-3"></div>
          <div className="pub-aurora-blob blob-4"></div>

          {/* Floating Soft Ambient Bokeh Particles */}
          <div className="pub-bokeh-particle p-1"></div>
          <div className="pub-bokeh-particle p-2"></div>
          <div className="pub-bokeh-particle p-3"></div>
          <div className="pub-bokeh-particle p-4"></div>
          <div className="pub-bokeh-particle p-5"></div>
        </div>

        <div className="pub-container">
          <div className="pub-hero-grid">
            {/* Left Content */}
            <div>
              <div className="pub-badge">
                <span className="pub-badge-dot"></span>
                <span>Next-Gen Social Network • Web3 Commerce • Member Rewards</span>
              </div>

              <h1 className="pub-title-hero">
                Connect, Collaborate & <br />
                <span className="pub-title-gradient">Monetize Your Network</span>
              </h1>

              <p className="pub-desc-hero">
                Welcome to <strong style={{ color: '#0f172a' }}>{platformName}</strong> — an all-in-one ecosystem fusing rich social networking, verified enterprise business directories, peer-to-peer advertising, and an automated multi-tier reward system.
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
                      <span>Join Now & Register</span>
                      <ArrowRight className="w-5 h-5" />
                    </Link>
                    <Link to="/member/login" className="pub-btn pub-btn-outline pub-btn-lg">
                      <span>Member Login</span>
                    </Link>
                  </>
                )}
                <Link to="/ecosystem" className="pub-btn pub-btn-ghost pub-btn-lg">
                  <Layers className="w-5 h-5" />
                  <span>Explore Ecosystem</span>
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
                  <Wallet className="w-4 h-4 text-indigo-600" />
                  <span style={{ fontWeight: '500' }}>Web3 Wallet Compatible</span>
                </div>
              </div>
            </div>

            {/* Right Visual: Interactive Ecosystem Preview Card */}
            <div className="pub-hero-visual">
              {/* Floating Notification 1 (Top-Right) */}
              <div className="pub-float-card pub-float-1">
                <div style={{ width: '38px', height: '38px', borderRadius: '10px', background: '#ecfdf5', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#059669', flexShrink: 0 }}>
                  <Gift className="w-5 h-5" />
                </div>
                <div>
                  <div style={{ fontSize: '0.86rem', fontWeight: '700', color: '#0f172a', whiteSpace: 'nowrap' }}>+25.00 USDT Earned</div>
                  <div style={{ fontSize: '0.72rem', color: '#64748b', whiteSpace: 'nowrap' }}>Ad Campaign Engagement</div>
                </div>
              </div>

              {/* Floating Notification 2 (Bottom-Left) */}
              <div className="pub-float-card pub-float-2">
                <div style={{ width: '38px', height: '38px', borderRadius: '10px', background: '#eef2ff', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#4f46e5', flexShrink: 0 }}>
                  <Users className="w-5 h-5" />
                </div>
                <div>
                  <div style={{ fontSize: '0.86rem', fontWeight: '700', color: '#0f172a', whiteSpace: 'nowrap' }}>New Introducer Referral</div>
                  <div style={{ fontSize: '0.72rem', color: '#64748b', whiteSpace: 'nowrap' }}>Level 1 Sponsor Commission</div>
                </div>
              </div>

              {/* Main Ecosystem Interactive Mockup Card */}
              <div className="pub-hero-mockup-card">
                {/* Card Interior Header */}
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', borderBottom: '1px solid #e2e8f0', paddingBottom: '16px', marginBottom: '20px' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                    <div style={{ width: '10px', height: '10px', borderRadius: '50%', background: '#ef4444' }}></div>
                    <div style={{ width: '10px', height: '10px', borderRadius: '50%', background: '#f59e0b' }}></div>
                    <div style={{ width: '10px', height: '10px', borderRadius: '50%', background: '#10b981' }}></div>
                  </div>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                    <span className="pub-radar-dot"></span>
                    <span style={{ fontSize: '0.76rem', color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.08em', fontWeight: '700' }}>
                      Live Activity Pulse
                    </span>
                  </div>
                </div>

                <div style={{ display: 'flex', flexDirection: 'column', gap: '14px' }}>
                  <div className="pub-mockup-stat-row">
                    <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                      <div className="pub-icon-wrapper pub-icon-emerald" style={{ width: '40px', height: '40px', marginBottom: 0, borderRadius: '10px' }}>
                        <Wallet className="w-4 h-4" />
                      </div>
                      <div>
                        <div style={{ fontSize: '0.85rem', fontWeight: '700', color: '#0f172a' }}>Reward Wallet Balance</div>
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
          2. WHAT IS THIS PLATFORM? (Core Definition)
          ==================================================================== */}
      <section className="pub-section" style={{ background: '#ffffff', borderTop: '1px solid #e2e8f0', borderBottom: '1px solid #e2e8f0' }}>
        <div className="pub-container">
          <div className="pub-section-header">
            <div className="pub-badge pub-badge-cyan">
              <span className="pub-badge-dot"></span>
              <span>Platform Architecture</span>
            </div>
            <h2 className="pub-section-title">What is {platformName}?</h2>
            <p className="pub-section-desc">
              A comprehensive digital network engineered to solve the broken economics of modern social media. Instead of big tech keeping all advertising profits, {platformName} returns revenue to active members, businesses, and network builders.
            </p>
          </div>

          <div className="pub-grid-3">
            {/* Card 1 */}
            <div className="pub-card">
              <div className="pub-card-glow-line"></div>
              <div className="pub-icon-wrapper pub-icon-indigo">
                <Globe className="w-6 h-6" />
              </div>
              <h3 style={{ fontSize: '1.25rem', fontWeight: '800', color: '#0f172a', marginBottom: '12px' }}>
                Full Social Platform
              </h3>
              <p style={{ color: '#475569', lineHeight: '1.6', fontSize: '0.95rem' }}>
                Create rich posts, share interactive stories, post short-form Watch videos, connect with friends, and engage in topic-based community discussion hubs.
              </p>
              <ul className="pub-checklist" style={{ marginTop: '16px' }}>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Posts, Reactions & Nested Comments</span>
                </li>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>24-Hour Disappearing Stories</span>
                </li>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Short-form Watch Video Feed</span>
                </li>
              </ul>
            </div>

            {/* Card 2 */}
            <div className="pub-card">
              <div className="pub-card-glow-line"></div>
              <div className="pub-icon-wrapper pub-icon-emerald">
                <Briefcase className="w-6 h-6" />
              </div>
              <h3 style={{ fontSize: '1.25rem', fontWeight: '800', color: '#0f172a', marginBottom: '12px' }}>
                Enterprise Business Pages
              </h3>
              <p style={{ color: '#475569', lineHeight: '1.6', fontSize: '0.95rem' }}>
                Launch verified brand profiles, showcase products and services, assign team members with granular roles, and gather transparent customer reviews.
              </p>
              <ul className="pub-checklist" style={{ marginTop: '16px' }}>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Official Blue Check Verification</span>
                </li>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Team Role & Permission Control</span>
                </li>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Direct Customer Business Inbox</span>
                </li>
              </ul>
            </div>

            {/* Card 3 */}
            <div className="pub-card">
              <div className="pub-card-glow-line"></div>
              <div className="pub-icon-wrapper pub-icon-amber">
                <Gift className="w-6 h-6" />
              </div>
              <h3 style={{ fontSize: '1.25rem', fontWeight: '800', color: '#0f172a', marginBottom: '12px' }}>
                Multi-Stream Rewards
              </h3>
              <p style={{ color: '#475569', lineHeight: '1.6', fontSize: '0.95rem' }}>
                Earn direct income for ad clicks, impression engagements, community leadership, and introducer referral commissions across your entire downline.
              </p>
              <ul className="pub-checklist" style={{ marginTop: '16px' }}>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Watch & Click Ad Incentives</span>
                </li>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Multi-Tier Sponsor Commissions</span>
                </li>
                <li className="pub-checklist-item">
                  <CheckCircle2 className="w-4 h-4 pub-check-icon" />
                  <span>Instant Web3 USDT Withdrawals</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      {/* ====================================================================
          3. HOW IT WORKS: 3-STEP ONBOARDING
          ==================================================================== */}
      <section className="pub-section">
        <div className="pub-container">
          <div className="pub-section-header">
            <div className="pub-badge pub-badge-emerald">
              <span className="pub-badge-dot"></span>
              <span>Fast Track</span>
            </div>
            <h2 className="pub-section-title">How To Get Started</h2>
            <p className="pub-section-desc">
              Join thousands of active network members earning daily rewards in three simple steps.
            </p>
          </div>

          <div className="pub-steps-flow">
            {/* Step 1 */}
            <div className="pub-card">
              <div className="pub-step-num">01</div>
              <h3 style={{ fontSize: '1.2rem', fontWeight: '800', color: '#0f172a', marginBottom: '10px' }}>
                Register Free Account
              </h3>
              <p style={{ color: '#475569', lineHeight: '1.6', fontSize: '0.95rem' }}>
                Sign up with your email or Google Account. Enter an Introducer code from your sponsor to activate multi-tier referral tree benefits.
              </p>
            </div>

            {/* Step 2 */}
            <div className="pub-card">
              <div className="pub-step-num">02</div>
              <h3 style={{ fontSize: '1.2rem', fontWeight: '800', color: '#0f172a', marginBottom: '10px' }}>
                Engage & Promote
              </h3>
              <p style={{ color: '#475569', lineHeight: '1.6', fontSize: '0.95rem' }}>
                Create your profile, interact with sponsored ad campaigns, join communities, or fund your own business ads via your Fund Wallet.
              </p>
            </div>

            {/* Step 3 */}
            <div className="pub-card">
              <div className="pub-step-num">03</div>
              <h3 style={{ fontSize: '1.2rem', fontWeight: '800', color: '#0f172a', marginBottom: '10px' }}>
                Collect & Withdraw
              </h3>
              <p style={{ color: '#475569', lineHeight: '1.6', fontSize: '0.95rem' }}>
                All rewards accumulate automatically in your Reward Wallet. Verify your crypto address and withdraw instantly to your Web3 wallet.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* ====================================================================
          4. BOTTOM CTA BANNER (Light High-Impact Styling)
          ==================================================================== */}
      <section className="pub-section" style={{ paddingTop: '20px' }}>
        <div className="pub-container">
          <div style={{
            background: 'linear-gradient(135deg, #eef4ff 0%, #ecfdf5 100%)',
            border: '1px solid #c7d2fe',
            borderRadius: '24px',
            padding: '56px 40px',
            textAlign: 'center',
            position: 'relative',
            overflow: 'hidden',
            boxShadow: 'var(--pub-shadow-lg)'
          }}>
            <div style={{ maxWidth: '640px', margin: '0 auto' }}>
              <div className="pub-badge pub-badge-amber">
                <span className="pub-badge-dot"></span>
                <span>Immediate Access</span>
              </div>
              <h2 style={{ fontSize: 'clamp(2rem, 4vw, 2.75rem)', fontWeight: '800', color: '#0f172a', marginBottom: '16px' }}>
                Ready to Join the Revolution?
              </h2>
              <p style={{ color: '#475569', fontSize: '1.1rem', lineHeight: '1.6', marginBottom: '32px' }}>
                Create your member account today, secure your network username, and start building your financial and social capital.
              </p>
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '14px', flexWrap: 'wrap' }}>
                <Link to="/member/register" className="pub-btn pub-btn-accent pub-btn-lg">
                  <Sparkles className="w-5 h-5" />
                  <span>Create Free Account</span>
                  <ArrowRight className="w-5 h-5" />
                </Link>
                <Link to="/member/login" className="pub-btn pub-btn-outline pub-btn-lg">
                  <span>Sign In to Member Portal</span>
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
