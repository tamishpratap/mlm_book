import { useState, useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import {
  Shield,
  Mail,
  Lock,
  Eye,
  EyeOff,
  ArrowRight,
  AlertCircle,
  CheckCircle2,
  Activity,
  Loader2,
} from 'lucide-react';
import { useAuth } from '../../hooks/useAuth';
import { useBranding } from '../../hooks/useBranding';
import { useToast } from '../../hooks/useToast';
import { authApi } from '../../api';
import { BRAND_LOGO } from '../../utils/mediaHelper';

export function LoginPage() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [remember, setRemember] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [fieldErrors, setFieldErrors] = useState({});
  const [generalError, setGeneralError] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const { login, isAuthenticated, loading } = useAuth();
  const { branding } = useBranding();
  const { showSuccess, showInfo } = useToast();
  const navigate = useNavigate();
  const location = useLocation();

  const rawFrom = location.state?.from?.pathname;
  const from =
    rawFrom && rawFrom.startsWith('/admin') && !rawFrom.includes('/login')
      ? rawFrom
      : '/admin/dashboard';
  const websiteUrl = import.meta.env.VITE_APP_URL || 'https://mlmbookai.com';

  // If already authenticated, redirect to destination
  useEffect(() => {
    if (!loading && isAuthenticated) {
      navigate(from, { replace: true });
    }
  }, [isAuthenticated, loading, navigate, from]);

  // Initialize CSRF cookies on component mount
  useEffect(() => {
    authApi.initCsrf().catch(() => {});
  }, []);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setFieldErrors({});
    setGeneralError('');

    // Client-side input validation
    const errors = {};
    if (!email.trim()) errors.email = 'Email address is required.';
    if (!password) errors.password = 'Password is required.';

    if (Object.keys(errors).length > 0) {
      setFieldErrors(errors);
      return;
    }

    setSubmitting(true);
    try {
      await login({
        email: email.trim(),
        password,
        remember: remember ? 1 : 0,
      });
      showSuccess('Signed in successfully.');
      navigate(from, { replace: true });
    } catch (error) {
      if (error.errors && Object.keys(error.errors).length > 0) {
        setFieldErrors(error.errors);
        if (error.errors.email) {
          setGeneralError(error.errors.email);
        } else {
          setGeneralError(error.message || 'Invalid administrator credentials.');
        }
      } else {
        setGeneralError(error.message || 'Invalid administrator credentials. Please check your email and password.');
      }
    } finally {
      setSubmitting(false);
    }
  };

  const handleForgotPassword = (e) => {
    e.preventDefault();
    showInfo('For administrative security, password resets must be provisioned directly by a Super Administrator.');
  };

  return (
    <div className="relative min-h-screen w-full bg-[#0A0F1F] text-[#F8FAFC] flex flex-col lg:flex-row overflow-x-hidden select-none">
      {/* Background Ambient Radial Glows */}
      <div
        className="pointer-events-none absolute inset-0 z-0 overflow-hidden"
        aria-hidden="true"
      >
        <div
          className="absolute -top-32 left-1/2 -translate-x-1/2 w-[900px] h-[500px] rounded-full blur-[140px] opacity-70"
          style={{
            background: 'radial-gradient(circle, rgba(59,130,246,0.18) 0%, rgba(139,92,246,0.14) 50%, transparent 80%)',
          }}
        />
        <div
          className="absolute top-1/3 -left-48 w-[600px] h-[600px] rounded-full blur-[160px] opacity-40"
          style={{
            background: 'radial-gradient(circle, rgba(59,130,246,0.22) 0%, transparent 70%)',
          }}
        />
        <div
          className="absolute -bottom-48 -right-48 w-[700px] h-[700px] rounded-full blur-[170px] opacity-40"
          style={{
            background: 'radial-gradient(circle, rgba(139,92,246,0.20) 0%, transparent 70%)',
          }}
        />
      </div>

      {/* Top Right "Back to Website" Navigation Link */}
      <div className="absolute top-6 right-6 sm:top-8 sm:right-10 z-30">
        <a
          href={websiteUrl}
          target="_blank"
          rel="noopener noreferrer"
          className="inline-flex items-center space-x-2 text-xs sm:text-sm font-medium text-[#CBD5E1] hover:text-[#FFFFFF] transition-colors duration-200 py-1.5 px-3 rounded-lg hover:bg-white/[0.04] focus:outline-none focus:ring-2 focus:ring-blue-500/40"
        >
          <span>&larr; Back to Website</span>
        </a>
      </div>

      {/* =========================================================================
          LEFT SIDE: Premium Branding & Marketing Section (Approx 45% Desktop)
          ========================================================================= */}
      <section className="relative z-10 w-full lg:w-[45%] flex flex-col justify-between p-6 sm:p-10 lg:p-16 xl:p-20 overflow-hidden">
        {/* Subtle Futuristic Globe / Network Background Graphic */}
        <div
          className="pointer-events-none absolute inset-0 z-0 opacity-25 lg:opacity-35 flex items-center justify-center overflow-hidden"
          aria-hidden="true"
        >
          <svg
            className="w-[680px] h-[680px] max-w-none text-blue-500/40"
            viewBox="0 0 600 600"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <defs>
              <linearGradient id="netGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stopColor="#3B82F6" stopOpacity="0.8" />
                <stop offset="50%" stopColor="#6366F1" stopOpacity="0.5" />
                <stop offset="100%" stopColor="#8B5CF6" stopOpacity="0.8" />
              </linearGradient>
              <filter id="glow" x="-20%" y="-20%" width="140%" height="140%">
                <feGaussianBlur stdDeviation="3" result="blur" />
                <feComposite in="SourceGraphic" in2="blur" operator="over" />
              </filter>
            </defs>

            {/* Concentric Globe Rings */}
            <circle cx="300" cy="300" r="230" stroke="url(#netGrad)" strokeWidth="1" strokeDasharray="6 6" opacity="0.4" />
            <circle cx="300" cy="300" r="175" stroke="url(#netGrad)" strokeWidth="1" opacity="0.35" />
            <circle cx="300" cy="300" r="115" stroke="url(#netGrad)" strokeWidth="1" strokeDasharray="3 3" opacity="0.3" />

            {/* Latitude Ellipses */}
            <ellipse cx="300" cy="300" rx="230" ry="75" stroke="url(#netGrad)" strokeWidth="1" opacity="0.4" />
            <ellipse cx="300" cy="300" rx="230" ry="145" stroke="url(#netGrad)" strokeWidth="1" opacity="0.3" />

            {/* Longitude Ellipses */}
            <ellipse cx="300" cy="300" rx="75" ry="230" stroke="url(#netGrad)" strokeWidth="1" opacity="0.3" />
            <ellipse cx="300" cy="300" rx="150" ry="230" stroke="url(#netGrad)" strokeWidth="1" opacity="0.3" />

            {/* Diagonal Network Coordinates */}
            <line x1="120" y1="180" x2="250" y2="220" stroke="#3B82F6" strokeWidth="1.2" opacity="0.5" />
            <line x1="250" y1="220" x2="380" y2="170" stroke="#8B5CF6" strokeWidth="1.2" opacity="0.5" />
            <line x1="250" y1="220" x2="320" y2="340" stroke="#3B82F6" strokeWidth="1.2" opacity="0.6" />
            <line x1="320" y1="340" x2="440" y2="380" stroke="#8B5CF6" strokeWidth="1.2" opacity="0.5" />
            <line x1="320" y1="340" x2="200" y2="410" stroke="#3B82F6" strokeWidth="1.2" opacity="0.4" />
            <line x1="200" y1="410" x2="140" y2="330" stroke="#6366F1" strokeWidth="1.2" opacity="0.5" />
            <line x1="140" y1="330" x2="250" y2="220" stroke="#3B82F6" strokeWidth="1.2" opacity="0.4" />

            {/* Glowing Network Nodes */}
            <circle cx="250" cy="220" r="5" fill="#3B82F6" filter="url(#glow)" />
            <circle cx="380" cy="170" r="4" fill="#8B5CF6" filter="url(#glow)" />
            <circle cx="320" cy="340" r="6" fill="#60A5FA" filter="url(#glow)" />
            <circle cx="440" cy="380" r="4.5" fill="#C084FC" filter="url(#glow)" />
            <circle cx="200" cy="410" r="4" fill="#3B82F6" filter="url(#glow)" />
            <circle cx="140" cy="330" r="4.5" fill="#818CF8" filter="url(#glow)" />
            <circle cx="120" cy="180" r="3.5" fill="#60A5FA" opacity="0.7" />
          </svg>
        </div>

        {/* Top Branding Badge & Official Logo */}
        <div className="relative z-10">
          <div className="flex items-center space-x-3 mb-6">
            <img
              src={branding?.site_logo_url || branding?.site_dark_logo_url || BRAND_LOGO}
              alt={branding?.site_name || 'MLM Book'}
              className="h-10 sm:h-12 w-auto object-contain drop-shadow-md"
              onError={(e) => {
                if (e.currentTarget.src !== BRAND_LOGO) {
                  e.currentTarget.src = BRAND_LOGO;
                }
              }}
            />
          </div>

          <div className="inline-block">
            <span className="text-[11px] font-bold tracking-[0.25em] text-[#8BA7D8] uppercase">
              ADMIN CONSOLE
            </span>
          </div>

          {/* Main Visual Heading */}
          <h1 className="text-3xl sm:text-4xl lg:text-[48px] xl:text-[54px] font-extrabold text-[#F8FAFC] tracking-tight leading-[1.06] mt-4 mb-5">
            Global Platform<br />
            <span
              className="bg-clip-text text-transparent"
              style={{
                backgroundImage: 'linear-gradient(90deg, #3B82F6 0%, #8B5CF6 50%, #B85CF6 100%)',
              }}
            >
              Greater Possibilities
            </span>
          </h1>

          {/* Description */}
          <p className="text-[#B8C2D8] text-sm sm:text-base lg:text-[17px] leading-relaxed max-w-[500px]">
            Manage. Secure. Scale. &mdash; Everything you need to keep MLM Book running smoothly, all in one place.
          </p>
        </div>

        {/* Bottom Platform Statistics & Copyright Footer */}
        <div className="relative z-10 mt-12 lg:mt-16">
          {/* Visual Platform Statistics */}
          <div className="flex items-center space-x-4 sm:space-x-8 pb-8">
            <div className="pr-4 sm:pr-8 border-r border-white/[0.12]">
              <div className="text-2xl sm:text-3xl font-extrabold text-[#F8FAFC] tracking-tight">
                50K+
              </div>
              <div className="text-xs sm:text-sm font-medium text-[#94A3B8] mt-0.5">
                Members
              </div>
            </div>

            <div className="pr-4 sm:pr-8 border-r border-white/[0.12]">
              <div className="text-2xl sm:text-3xl font-extrabold text-[#F8FAFC] tracking-tight">
                1K+
              </div>
              <div className="text-xs sm:text-sm font-medium text-[#94A3B8] mt-0.5">
                Business Pages
              </div>
            </div>

            <div>
              <div className="text-2xl sm:text-3xl font-extrabold text-[#F8FAFC] tracking-tight">
                100+
              </div>
              <div className="text-xs sm:text-sm font-medium text-[#94A3B8] mt-0.5">
                Communities
              </div>
            </div>
          </div>

          {/* Copyright Notice */}
          <p className="text-xs text-[#64748B] pt-2">
            &copy; {new Date().getFullYear()} MLM Book Platform. All rights reserved.
          </p>
        </div>
      </section>

      {/* =========================================================================
          RIGHT SIDE: Glassmorphism Admin Login Card (Approx 55% Desktop)
          ========================================================================= */}
      <section className="relative z-10 w-full lg:w-[55%] flex items-center justify-center p-4 sm:p-8 lg:p-12 xl:p-16">
        <div
          className="w-full max-w-[540px] rounded-[22px] border border-white/[0.12] p-6 sm:p-10 shadow-[0_25px_70px_rgba(0,0,0,0.35)] transition-all duration-300"
          style={{
            background: 'rgba(20, 32, 58, 0.78)',
            backdropFilter: 'blur(18px)',
            WebkitBackdropFilter: 'blur(18px)',
          }}
        >
          {/* Card Header with Security Icon */}
          <div className="text-center mb-8">
            <div className="inline-flex items-center justify-center w-16 h-16 rounded-[18px] mb-4 shadow-lg shadow-blue-500/25 ring-1 ring-white/20 bg-gradient-to-tr from-[#3B82F6] to-[#8B5CF6]">
              <Shield className="w-8 h-8 text-white" />
            </div>
            <h2 className="text-2xl sm:text-[28px] font-bold text-[#F8FAFC] tracking-tight">
              Admin Sign In
            </h2>
            <p className="text-sm text-[#A8B3C7] mt-1.5 font-normal">
              Secure Access to MLM Book Administration
            </p>
          </div>

          {/* General Error Banner */}
          {generalError && (
            <div
              className="mb-6 p-4 rounded-xl text-xs sm:text-sm text-[#FCA5A5] flex items-start space-x-3 transition-all duration-200"
              style={{
                background: 'rgba(239, 68, 68, 0.12)',
                border: '1px solid rgba(239, 68, 68, 0.35)',
              }}
            >
              <AlertCircle className="w-5 h-5 text-red-400 shrink-0 mt-0.5" />
              <div className="leading-snug">{generalError}</div>
            </div>
          )}

          {/* Login Form */}
          <form onSubmit={handleSubmit} className="space-y-5">
            {/* Email Field */}
            <div>
              <label
                htmlFor="admin-email"
                className="block text-xs font-semibold text-[#CBD5E1] uppercase tracking-wider mb-2"
              >
                Administrator Email
              </label>
              <div
                className={`relative flex items-center h-[54px] rounded-xl border transition-all duration-200 ${
                  fieldErrors.email
                    ? 'border-red-500/80 bg-red-950/20'
                    : 'border-white/[0.12] bg-white/[0.07] focus-within:border-[#3B82F6] focus-within:ring-4 focus-within:ring-blue-500/15'
                }`}
              >
                <div className="pl-4 pr-2 text-[#94A3B8] pointer-events-none shrink-0">
                  <Mail className="w-5 h-5" />
                </div>
                <input
                  id="admin-email"
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="admin@example.com"
                  disabled={submitting}
                  autoComplete="email"
                  className="w-full h-full bg-transparent px-2 pr-4 text-sm text-[#F8FAFC] placeholder-[#94A3B8] focus:outline-none"
                />
              </div>
              {fieldErrors.email && (
                <span className="text-[11px] text-red-400 mt-1.5 block font-medium">
                  {fieldErrors.email}
                </span>
              )}
            </div>

            {/* Password Field */}
            <div>
              <label
                htmlFor="admin-password"
                className="block text-xs font-semibold text-[#CBD5E1] uppercase tracking-wider mb-2"
              >
                Password
              </label>
              <div
                className={`relative flex items-center h-[54px] rounded-xl border transition-all duration-200 ${
                  fieldErrors.password
                    ? 'border-red-500/80 bg-red-950/20'
                    : 'border-white/[0.12] bg-white/[0.07] focus-within:border-[#3B82F6] focus-within:ring-4 focus-within:ring-blue-500/15'
                }`}
              >
                <div className="pl-4 pr-2 text-[#94A3B8] pointer-events-none shrink-0">
                  <Lock className="w-5 h-5" />
                </div>
                <input
                  id="admin-password"
                  type={showPassword ? 'text' : 'password'}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="••••••••••••"
                  disabled={submitting}
                  autoComplete="current-password"
                  className="w-full h-full bg-transparent px-2 text-sm text-[#F8FAFC] placeholder-[#94A3B8] focus:outline-none"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword((prev) => !prev)}
                  tabIndex={-1}
                  className="p-2 mr-2 text-[#94A3B8] hover:text-white transition-colors duration-150 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/30"
                  aria-label={showPassword ? 'Hide password' : 'Show password'}
                >
                  {showPassword ? (
                    <EyeOff className="w-5 h-5" />
                  ) : (
                    <Eye className="w-5 h-5" />
                  )}
                </button>
              </div>
              {fieldErrors.password && (
                <span className="text-[11px] text-red-400 mt-1.5 block font-medium">
                  {fieldErrors.password}
                </span>
              )}
            </div>

            {/* Remember Me & Help/Support Row */}
            <div className="flex items-center justify-between pt-1">
              <label className="flex items-center space-x-2.5 cursor-pointer select-none">
                <input
                  type="checkbox"
                  id="remember-session"
                  checked={remember}
                  onChange={(e) => setRemember(e.target.checked)}
                  disabled={submitting}
                  className="w-4 h-4 rounded border-slate-700 bg-slate-900/80 text-blue-600 focus:ring-blue-500/30 focus:ring-offset-0 cursor-pointer"
                />
                <span className="text-xs font-medium text-[#CBD5E1]">
                  Remember this session
                </span>
              </label>

              <button
                type="button"
                onClick={handleForgotPassword}
                className="text-xs font-medium text-[#60A5FA] hover:text-[#93C5FD] transition-colors duration-150 focus:outline-none"
              >
                Forgot Password?
              </button>
            </div>

            {/* Submit Button */}
            <div className="pt-3">
              <button
                type="submit"
                disabled={submitting}
                className="w-full h-[54px] rounded-xl text-sm font-semibold text-white flex items-center justify-center space-x-2 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_12px_30px_rgba(99,102,241,0.35)] hover:brightness-105 active:translate-y-0 disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:translate-y-0 disabled:hover:shadow-none cursor-pointer"
                style={{
                  backgroundImage: 'linear-gradient(90deg, #3B82F6 0%, #6366F1 50%, #8B5CF6 100%)',
                }}
              >
                {submitting ? (
                  <>
                    <Loader2 className="w-5 h-5 animate-spin" />
                    <span>Authenticating...</span>
                  </>
                ) : (
                  <>
                    <span>Sign In to Admin Console</span>
                    <ArrowRight className="w-4 h-4 transition-transform group-hover:translate-x-1" />
                  </>
                )}
              </button>
            </div>
          </form>

          {/* Bottom Trust Features */}
          <div className="mt-8 pt-6 border-t border-white/[0.08]">
            <div className="grid grid-cols-3 divide-x divide-white/[0.08] bg-white/[0.02] border border-white/[0.07] rounded-[14px] py-3 text-center">
              <div className="flex flex-col items-center justify-center px-2">
                <Shield className="w-4 h-4 text-[#93C5FD] mb-1" />
                <span className="text-[11px] font-semibold text-[#CBD5E1]">Secure</span>
              </div>
              <div className="flex flex-col items-center justify-center px-2">
                <CheckCircle2 className="w-4 h-4 text-[#93C5FD] mb-1" />
                <span className="text-[11px] font-semibold text-[#CBD5E1]">Reliable</span>
              </div>
              <div className="flex flex-col items-center justify-center px-2">
                <Activity className="w-4 h-4 text-[#93C5FD] mb-1" />
                <span className="text-[11px] font-semibold text-[#CBD5E1]">Always On</span>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}

export default LoginPage;
