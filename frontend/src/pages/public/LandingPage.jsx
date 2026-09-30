
import { useEffect } from 'react';
import '../../styles/dashboard.css';

// 16 Standard Home Section Components from dashboard/components
import HomeHero from '../dashboard/components/HomeHero';
import HomeWhatIs from '../dashboard/components/HomeWhatIs';
import HomeCapabilities from '../dashboard/components/HomeCapabilities';
import HomeWorkflowSteps from '../dashboard/components/HomeWorkflowSteps';
import HomeAdvertisingProcess from '../dashboard/components/HomeAdvertisingProcess';
import HomeMemberRewards from '../dashboard/components/HomeMemberRewards';
import HomeRewardDeterminants from '../dashboard/components/HomeRewardDeterminants';
import HomeBusinessBenefits from '../dashboard/components/HomeBusinessBenefits';
import HomeMemberBenefits from '../dashboard/components/HomeMemberBenefits';
import HomeCampaignTypes from '../dashboard/components/HomeCampaignTypes';
import HomeRewardWallet from '../dashboard/components/HomeRewardWallet';
import HomeTrustVerification from '../dashboard/components/HomeTrustVerification';
import HomeEndToEndWorkflow from '../dashboard/components/HomeEndToEndWorkflow';
import HomeWhyChoose from '../dashboard/components/HomeWhyChoose';
import HomeFaqAccordion from '../dashboard/components/HomeFaqAccordion';
import HomeCtaBanner from '../dashboard/components/HomeCtaBanner';

export function LandingPage() {
  useEffect(() => {
    document.title = 'MLM Book - The Next-Generation Digital Social & Business Ecosystem';
  }, []);

  return (
    <div
      className="pub-home-landing-wrapper"
      style={{
        padding: '24px 16px 80px',
        maxWidth: '1240px',
        margin: '0 auto',
        width: '100%',
        boxSizing: 'border-box',
      }}
    >
      <main className="home-container" id="home-landing">
        {/* Section 1 — Hero */}
        <HomeHero />

        {/* Section 2 — What is MLM Book? (Ecosystem Overview) */}
        <HomeWhatIs />

        {/* Section 3 — What Can You Do on MLM Book? */}
        <HomeCapabilities />

        {/* Section 4 — How MLM Book Works */}
        <HomeWorkflowSteps />

        {/* Section 5 — How Advertising Works */}
        <HomeAdvertisingProcess />

        {/* Section 6 — How Member Rewards Work */}
        <HomeMemberRewards />

        {/* Section 7 — What Determines Your Reward? */}
        <HomeRewardDeterminants />

        {/* Section 8 — Business Owner Benefits */}
        <HomeBusinessBenefits />

        {/* Section 9 — Member Benefits */}
        <HomeMemberBenefits />

        {/* Section 10 — Business Campaigns & Event Campaigns */}
        <HomeCampaignTypes />

        {/* Section 11 — Reward Wallet */}
        <HomeRewardWallet />

        {/* Section 12 — Trust & Verification */}
        <HomeTrustVerification />

        {/* Section 13 — End-to-End Workflow */}
        <HomeEndToEndWorkflow />

        {/* Section 14 — Why MLM Book? */}
        <HomeWhyChoose />

        {/* Section 15 — FAQ */}
        <HomeFaqAccordion />

        {/* Section 16 — Final CTA */}
        <HomeCtaBanner />
      </main>

import { useContext, useState, useRef, useEffect } from 'react';
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

  const canvasRef = useRef(null);

  useEffect(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    let animationFrameId;

    const heroSection = canvas.closest('.pub-hero-section') || canvas.parentElement;

    let width = 0;
    let height = 0;

    const resize = () => {
      const rect = heroSection.getBoundingClientRect();
      width = rect.width;
      height = rect.height;
      const dpr = Math.min(window.devicePixelRatio || 1, 2);
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
          1. HERO SECTION (Living Network Constellation & Ecosystem Showcase)
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

            {/* Right Visual: Living Ecosystem Architecture Card */}
            <div className="pub-hero-visual">
              <div className="pub-visual-glow-ring"></div>

              <div className="pub-hero-mockup-card">
                {/* 1. Window Header Bar */}
                <div className="pub-hero-card-header">
                  <div className="pub-card-mac-dots">
                    <span className="dot red"></span>
                    <span className="dot yellow"></span>
                    <span className="dot green"></span>
                  </div>
                  <div className="pub-card-url-pill">
                    <Lock className="w-3 h-3 text-emerald-600" />
                    <span>mlmbook.com/community</span>
                  </div>
                  <div className="pub-card-status-badge">
                    <span className="pub-radar-dot"></span>
                    <span>100% Free Access</span>
                  </div>
                </div>

                {/* 2. Interactive Social Community Feed Spotlight */}
                <div className="pub-hero-social-post">
                  <div className="pub-post-author-row">
                    <div className="pub-author-info">
                      <div className="pub-author-avatar">MB</div>
                      <div>
                        <div className="pub-author-name">
                          <span>Direct Selling Community</span>
                          <CheckCircle2 className="w-3.5 h-3.5 text-blue-600" />
                        </div>
                        <div className="pub-author-sub">Global Networkers Feed • Live</div>
                      </div>
                    </div>
                    <span className="pub-post-badge">0% Shadowbans</span>
                  </div>

                  <p className="pub-post-content">
                    "Connect with top direct selling leaders worldwide. Share business presentations, recruit motivated partners, and earn verified rewards — completely free without censorship."
                  </p>

                  <div className="pub-post-actions-row">
                    <div className="pub-post-stats">
                      <span>❤️ 342 Likes</span>
                      <span>💬 58 Comments</span>
                      <span>🔗 24 Shares</span>
                    </div>
                    <span className="pub-reward-chip">
                      <Sparkles className="w-3.5 h-3.5 text-emerald-600" />
                      <span>Reward Active</span>
                    </span>
                  </div>
                </div>

                {/* 3. Three Spacious, High-Impact Value Pillars (NO ghic-pich!) */}
                <div className="pub-hero-triad">
                  <div className="pub-triad-card">
                    <div className="pub-triad-icon pub-icon-purple">
                      <TvMinimalPlay className="w-5 h-5 text-purple-600" />
                    </div>
                    <div className="pub-triad-title">Watch & Earn</div>
                    <div className="pub-triad-value" style={{ color: '#7e22ce' }}>$0.025 – $1.00</div>
                    <div className="pub-triad-sub">Per Video View</div>
                  </div>

                  <div className="pub-triad-card">
                    <div className="pub-triad-icon pub-icon-cyan">
                      <Users className="w-5 h-5 text-sky-600" />
                    </div>
                    <div className="pub-triad-title">Team Network</div>
                    <div className="pub-triad-value" style={{ color: '#0284c7' }}>5 Free Tiers</div>
                    <div className="pub-triad-sub">0 Joining Fee</div>
                  </div>

                  <div className="pub-triad-card">
                    <div className="pub-triad-icon pub-icon-emerald">
                      <Wallet className="w-5 h-5 text-emerald-600" />
                    </div>
                    <div className="pub-triad-title">Web3 Payouts</div>
                    <div className="pub-triad-value" style={{ color: '#059669' }}>100% Payout</div>
                    <div className="pub-triad-sub">Instant USDT</div>
                  </div>
                </div>

                {/* 4. Welcoming Trust & Benefits Ribbon */}
                <div className="pub-hero-free-banner">
                  <div className="pub-free-banner-text">
                    <CheckCircle2 className="w-4 h-4 text-emerald-600 flex-shrink-0" />
                    <span>Free Registration • No Investment Required</span>
                  </div>
                  <Link to="/rewards" className="pub-free-banner-link">
                    <span>View Reward Rules</span>
                    <ChevronRight className="w-3.5 h-3.5" />
                  </Link>
                </div>
              </div>
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
                  <span>Multi-Tier Community Team Connections</span>
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
                  <span>100% Payout on Fund Wallet Withdrawals</span>
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
                <strong style={{ color: '#0f172a' }}>Multi-Tier Community Referral Growth</strong>
                <div style={{ fontSize: '0.8rem', color: '#64748b' }}>100% Free registration with 5-tier referral rewards</div>
              </div>
              <div style={{ textAlign: 'center', color: '#dc2626' }}>❌ Not Available</div>
              <div style={{ textAlign: 'center', color: '#16a34a', fontWeight: '700' }}>✅ 5-Tier Referral Network</div>
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
