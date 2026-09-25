import { useState, useEffect, useRef, useCallback } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import useAuth from '../../hooks/useAuth';
import useBranding from '../../hooks/useBranding';
import authApi from '../../api/authApi';
import { BRAND_LOGO } from '../../utils/assetHelper';

export function LoginPage() {
  const { login } = useAuth();
  const { logoUrl, siteName } = useBranding();
  const navigate = useNavigate();
  const location = useLocation();

  const searchParams = new URLSearchParams(location.search);
  const rawError = searchParams.get('error') || '';
  const isSignupRequired = rawError === 'signup_required' || searchParams.get('signup_required') === '1';

  const successParam = searchParams.get('success') || searchParams.get('status') || '';
  const initialSuccess = location.state?.success ||
    (successParam === 'account_created' || successParam === 'created' || searchParams.get('created') === '1'
      ? 'Your account has been created successfully. You can now log in.'
      : successParam);

  const [formData, setFormData] = useState({
    email: searchParams.get('email') || '',
    password: '',
  });
  const [showPassword, setShowPassword] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errors, setErrors] = useState({});
  const [showSignupRequired, setShowSignupRequired] = useState(isSignupRequired);

  const BLOCKED_ACCOUNT_MESSAGE = 'Your account has been blocked by the admin. You cannot log in.';
  const isGoogleBlockedError = rawError && rawError.toLowerCase().includes('blocked');

  // Single authoritative alert state: { type: 'success' | 'error', message: string } | null
  const [alert, setAlert] = useState(() => {
    if (isSignupRequired) return null;
    if (isGoogleBlockedError) {
      return { type: 'error', message: BLOCKED_ACCOUNT_MESSAGE };
    }
    if (rawError) {
      return { type: 'error', message: rawError };
    }
    if (initialSuccess) {
      return { type: 'success', message: initialSuccess };
    }
    return null;
  });

  const alertTimerRef = useRef(null);

  const clearAlertTimer = useCallback(() => {
    if (alertTimerRef.current) {
      clearTimeout(alertTimerRef.current);
      alertTimerRef.current = null;
    }
  }, []);

  const clearAlert = useCallback(() => {
    clearAlertTimer();
    setAlert(null);
    setShowSignupRequired(false);
  }, [clearAlertTimer]);

  const triggerAlert = useCallback((type, message, autoDismiss = true) => {
    clearAlertTimer();
    setShowSignupRequired(false);
    if (!message) {
      setAlert(null);
      return;
    }
    setAlert({ type, message });
    if (autoDismiss && message !== BLOCKED_ACCOUNT_MESSAGE) {
      alertTimerRef.current = setTimeout(() => {
        setAlert(null);
        alertTimerRef.current = null;
      }, 3000);
    }
  }, [clearAlertTimer]);

  useEffect(() => {
    // Start auto-dismiss timer for initial alert if present (skip for blocked account alert)
    if (alert && alert.message !== BLOCKED_ACCOUNT_MESSAGE) {
      alertTimerRef.current = setTimeout(() => {
        setAlert(null);
        alertTimerRef.current = null;
      }, 3000);
    }

    // Clean transient query params and history state so browser refresh does not re-display stale flash messages
    try {
      const sp = new URLSearchParams(window.location.search);
      let queryChanged = false;
      ['created', 'success', 'status'].forEach((param) => {
        if (sp.has(param)) {
          sp.delete(param);
          queryChanged = true;
        }
      });
      if (sp.has('error') && !isSignupRequired) {
        sp.delete('error');
        queryChanged = true;
      }

      const newSearch = sp.toString();
      const newUrl = window.location.pathname + (newSearch ? `?${newSearch}` : '') + window.location.hash;

      let nextHistoryState = window.history.state;
      if (window.history.state?.usr?.success) {
        const nextUsr = { ...window.history.state.usr };
        delete nextUsr.success;
        nextHistoryState = { ...window.history.state, usr: nextUsr };
      }

      if (queryChanged || window.history.state?.usr?.success) {
        window.history.replaceState(nextHistoryState, '', newUrl);
      }
    } catch {
      // Graceful fallback if history manipulation is restricted
    }

    return () => {
      clearAlertTimer();
    };
  }, [clearAlertTimer]);

  const refFromUrl = searchParams.get('ref') || searchParams.get('introducer') || '';
  const googleRedirectUrl = authApi.getGoogleAuthUrl(refFromUrl);
  const registerUrl = refFromUrl ? `/member/register?ref=${encodeURIComponent(refFromUrl)}` : '/member/register';

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    if (errors[name]) {
      setErrors((prev) => ({ ...prev, [name]: null }));
    }
    if (alert?.type === 'error') {
      if (alert.message === BLOCKED_ACCOUNT_MESSAGE) {
        if (name === 'email') {
          clearAlert();
        }
      } else {
        clearAlert();
      }
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    clearAlert();

    // Client UX validation
    const clientErrors = {};
    if (!formData.email.trim()) {
      clientErrors.email = ['Please enter your email address.'];
    }
    if (!formData.password) {
      clientErrors.password = ['Please enter your password.'];
    }
    if (Object.keys(clientErrors).length > 0) {
      setErrors(clientErrors);
      return;
    }

    setIsSubmitting(true);

    try {
      await login(formData);
      const destination = location.state?.from?.pathname || '/member/dashboard';
      navigate(destination, { replace: true });
    } catch (err) {
      let errorMessage = 'An error occurred while logging in. Please try again.';
      let autoDismiss = true;

      if (err.response) {
        const { status, data } = err.response;

        // Structured check for blocked member account
        const isBlocked =
          data?.code === 'ACCOUNT_BLOCKED' ||
          (status === 403 && (
            data?.message?.toLowerCase().includes('blocked') ||
            data?.errors?.email?.[0]?.toLowerCase().includes('blocked')
          ));

        if (isBlocked) {
          setErrors({}); // Do not duplicate error under email input
          triggerAlert('error', BLOCKED_ACCOUNT_MESSAGE, false);
          return;
        }

        // Field-specific validation errors
        if (data && data.errors && typeof data.errors === 'object') {
          setErrors(data.errors);
        }

        // Prominent top-level alert message
        if (data && data.message) {
          errorMessage = data.message;
        } else if (data && data.error) {
          errorMessage = data.error;
        } else if (status === 422) {
          errorMessage = 'The provided email or password is incorrect.';
        } else if (status === 401) {
          errorMessage = 'Invalid email or password. Please try again.';
        } else if (status === 403) {
          errorMessage = 'Access denied. You do not have permission to log in.';
        } else if (status === 429) {
          errorMessage = 'Too many login attempts. Please try again in a few moments.';
        } else if (status >= 500) {
          errorMessage = 'Server error occurred. Please try again later.';
        }
      } else if (err.request) {
        errorMessage = 'Unable to connect to the backend server. Please verify your connection.';
      } else {
        errorMessage = err.message || 'An unexpected error occurred. Please try again.';
      }
      triggerAlert('error', errorMessage, autoDismiss);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <main className="member-auth-page">
      <section className="member-auth-shell" aria-labelledby="member-login-title">
        {/* Visual Showcase Panel */}
        <div className="member-auth-visual">
          <span className="member-auth-decoration member-auth-decoration--top-left" aria-hidden="true" />
          <span className="member-auth-decoration member-auth-decoration--bottom-left" aria-hidden="true" />
          <span className="member-auth-ring member-auth-ring--visual" aria-hidden="true" />
          <span className="member-auth-dot member-auth-dot--one" aria-hidden="true" />
          <span className="member-auth-dot member-auth-dot--two" aria-hidden="true" />
          <span className="member-auth-dot member-auth-dot--three" aria-hidden="true" />
          <span className="member-auth-dot-grid member-auth-dot-grid--visual" aria-hidden="true" />

          <div className="member-auth-visual-content">
            <Link className="member-auth-logo" to="/member/login" aria-label={`${siteName || 'MLM Book'} Member Login`}>
              <img
                className="mlm-book-logo mlm-book-auth-logo"
                src={logoUrl || BRAND_LOGO}
                alt={siteName || 'MLM Book'}
                width="96"
                height="96"
                onError={(e) => {
                  if (e.currentTarget.src !== BRAND_LOGO) {
                    e.currentTarget.src = BRAND_LOGO;
                  }
                }}
              />
            </Link>

            <div className="member-auth-message">
              <div className="member-auth-badge">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <path d="m12 3-1.4 3.6L7 8l3.6 1.4L12 13l1.4-3.6L17 8l-3.6-1.4L12 3Z" />
                  <path d="m5 14-.8 2.2L2 17l2.2.8L5 20l.8-2.2L8 17l-2.2-.8L5 14Z" />
                  <path d="m19 13-.8 2.2-2.2.8 2.2.8L19 19l.8-2.2L22 16l-2.2-.8L19 13Z" />
                </svg>
                <span>Your community, your world</span>
              </div>

              <h1 className="member-auth-heading">
                Welcome back
                <span className="member-auth-gradient-text">Let’s continue<br />your journey</span>
              </h1>
              <p className="member-auth-description">Connect with friends, share meaningful moments,<br />and discover new stories together.</p>
            </div>

            <div className="member-auth-media" aria-hidden="true">
              <div className="member-auth-photo member-auth-photo--one">
                <img src="/member_assets/images/login/story_2.jpg" alt="" />
                <span><img src="/member_assets/images/login/profile_1.jpg" alt="" /></span>
              </div>
              <div className="member-auth-photo member-auth-photo--two">
                <img src="/member_assets/images/login/story_3.jpg" alt="" />
                <span><img src="/member_assets/images/login/profile_2.jpg" alt="" /></span>
              </div>
              <div className="member-auth-photo member-auth-photo--three">
                <img src="/member_assets/images/login/story_5.jpg" alt="" />
                <span><img src="/member_assets/images/login/profile_4.png" alt="" /></span>
              </div>

              <div className="member-auth-community-card">
                <div className="member-auth-community-avatars">
                  <img src="/member_assets/images/login/profile_1.jpg" alt="" />
                  <img src="/member_assets/images/login/profile_2.jpg" alt="" />
                  <img src="/member_assets/images/login/profile_5.png" alt="" />
                  <span>+12k</span>
                </div>
                <div>
                  <strong>12k+</strong>
                  <small>people connect daily</small>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Form Panel */}
        <div className="member-auth-form-panel">
          <span className="member-auth-ring member-auth-ring--form-top" aria-hidden="true" />
          <span className="member-auth-ring member-auth-ring--form-bottom" aria-hidden="true" />
          <span className="member-auth-dot member-auth-dot--four" aria-hidden="true" />
          <span className="member-auth-dot member-auth-dot--five" aria-hidden="true" />
          <span className="member-auth-dot-grid member-auth-dot-grid--form" aria-hidden="true" />

          <div className="member-auth-form-wrap">
            <header className="member-auth-form-heading">
              <h2 className="member-auth-title" id="member-login-title">Welcome back!</h2>
              <p className="member-auth-subtitle">Enter your details to access your account.</p>
            </header>

            {alert?.type === 'success' ? (
              <div className="member-auth-alert member-auth-alert--success" role="status" style={{ background: '#ecfdf5', borderColor: '#a7f3d0', color: '#065f46', marginBottom: '20px' }}>
                <svg viewBox="0 0 24 24" aria-hidden="true" style={{ color: '#10b981' }}><circle cx="12" cy="12" r="9" /><path d="m9 12 2 2 4-4" /></svg>
                <span>{alert.message}</span>
              </div>
            ) : alert?.type === 'error' ? (
              <div
                className="member-auth-alert member-auth-alert--error"
                role="alert"
                style={{
                  display: 'flex',
                  alignItems: 'flex-start',
                  gap: '10px',
                  background: '#fff7f7',
                  border: '1px solid #fecaca',
                  borderRadius: '12px',
                  padding: '14px 16px',
                  marginBottom: '20px',
                  color: '#c22f2f',
                  fontSize: '13.5px',
                  lineHeight: '1.45',
                  boxSizing: 'border-box',
                  width: '100%',
                }}
              >
                <svg
                  width="20"
                  height="20"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="#dc2626"
                  strokeWidth="2"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  style={{ flexShrink: 0, marginTop: '1px' }}
                  aria-hidden="true"
                >
                  <circle cx="12" cy="12" r="10" />
                  <line x1="12" y1="8" x2="12" y2="12" />
                  <line x1="12" y1="16" x2="12.01" y2="16" />
                </svg>
                <span style={{ fontWeight: 500 }}>{alert.message}</span>
              </div>
            ) : showSignupRequired ? (
              <div
                className="member-auth-alert member-auth-alert--signup-required"
                role="alert"
                style={{
                  background: '#eff6ff',
                  borderColor: '#bfdbfe',
                  border: '1px solid #bfdbfe',
                  borderRadius: '12px',
                  padding: '16px',
                  marginBottom: '20px',
                  display: 'flex',
                  flexDirection: 'column',
                  gap: '12px',
                }}
              >
                <div style={{ display: 'flex', alignItems: 'flex-start', gap: '10px' }}>
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563eb" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{ flexShrink: 0, marginTop: '2px' }}>
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="8" x2="12" y2="12" />
                    <line x1="12" y1="16" x2="12.01" y2="16" />
                  </svg>
                  <div>
                    <strong style={{ display: 'block', fontSize: '15px', color: '#1e3a8a', marginBottom: '4px', fontWeight: 600 }}>
                      Create Your MLM Book Account First
                    </strong>
                    <p style={{ margin: 0, fontSize: '13.5px', lineHeight: '1.45', color: '#1e40af' }}>
                      We couldn’t find an MLM Book account linked to this Google account. Please create your account first, then log in with Google.
                    </p>
                  </div>
                </div>

                <div style={{ display: 'flex', gap: '10px', marginTop: '2px' }}>
                  <Link
                    to={registerUrl}
                    className="member-button member-button--primary"
                    style={{
                      display: 'inline-flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      gap: '6px',
                      background: '#2563eb',
                      color: '#ffffff',
                      fontWeight: 600,
                      fontSize: '13.5px',
                      padding: '8px 16px',
                      borderRadius: '8px',
                      textDecoration: 'none',
                      border: 'none',
                      cursor: 'pointer',
                    }}
                  >
                    <span>Create Account</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M5 12h14" /><path d="m12 5 7 7-7 7" /></svg>
                  </Link>

                  <button
                    type="button"
                    onClick={() => {
                      clearAlert();
                      navigate(refFromUrl ? `/member/login?ref=${encodeURIComponent(refFromUrl)}` : '/member/login', { replace: true });
                    }}
                    style={{
                      background: '#ffffff',
                      color: '#475569',
                      border: '1px solid #cbd5e1',
                      borderRadius: '8px',
                      fontSize: '13px',
                      fontWeight: 500,
                      padding: '8px 14px',
                      cursor: 'pointer',
                    }}
                  >
                    Back to Login
                  </button>
                </div>
              </div>
            ) : null}

            <form className="member-auth-form" onSubmit={handleSubmit} noValidate>
              <div className="member-auth-field">
                <label className="member-auth-label" htmlFor="email">Email address</label>
                <div className={`member-auth-input-wrap ${errors.email ? 'member-auth-input-wrap--error' : ''}`}>
                  <svg className="member-auth-input-icon" viewBox="0 0 24 24" aria-hidden="true"><rect width="18" height="14" x="3" y="5" rx="2" /><path d="m3 7 9 6 9-6" /></svg>
                  <input
                    className="member-auth-input"
                    id="email"
                    name="email"
                    type="email"
                    value={formData.email}
                    onChange={handleChange}
                    placeholder="Enter your email address"
                    autoComplete="email"
                    required
                    autoFocus
                    aria-invalid={errors.email ? 'true' : 'false'}
                    aria-describedby={errors.email ? 'member-email-error' : undefined}
                  />
                </div>
                {errors.email && (
                  <p className="member-auth-error" id="member-email-error" role="alert">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 8v5" /><path d="M12 17h.01" /></svg>
                    <span>{Array.isArray(errors.email) ? errors.email[0] : errors.email}</span>
                  </p>
                )}
              </div>

              <div className="member-auth-field">
                <div className="member-auth-label-row">
                  <label className="member-auth-label" htmlFor="password">Password</label>
                  <Link className="member-auth-forgot" to="/member/forgot-password">Forgot password?</Link>
                </div>
                <div className={`member-auth-input-wrap ${errors.password ? 'member-auth-input-wrap--error' : ''}`}>
                  <svg className="member-auth-input-icon" viewBox="0 0 24 24" aria-hidden="true"><rect width="16" height="12" x="4" y="9" rx="2" /><path d="M8 9V6a4 4 0 0 1 8 0v3" /><path d="M12 14v2" /></svg>
                  <input
                    className="member-auth-input member-auth-input--password"
                    id="password"
                    name="password"
                    type={showPassword ? 'text' : 'password'}
                    value={formData.password}
                    onChange={handleChange}
                    placeholder="Enter your password"
                    autoComplete="current-password"
                    required
                    aria-invalid={errors.password ? 'true' : 'false'}
                    aria-describedby={errors.password ? 'member-password-error' : undefined}
                  />
                  <button
                    className={`member-auth-password-toggle ${showPassword ? 'is-visible' : ''}`}
                    type="button"
                    aria-label={showPassword ? 'Hide password' : 'Show password'}
                    aria-controls="password"
                    onClick={() => setShowPassword((prev) => !prev)}
                  >
                    {showPassword ? (
                      <svg
                        className="member-auth-eye member-auth-eye--hide"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                        style={{ display: 'block', opacity: 1, pointerEvents: 'none' }}
                      >
                        <path d="m2 2 20 20" />
                        <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
                        <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" />
                        <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61" />
                      </svg>
                    ) : (
                      <svg
                        className="member-auth-eye member-auth-eye--show"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                        style={{ display: 'block', opacity: 1, pointerEvents: 'none' }}
                      >
                        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
                        <circle cx="12" cy="12" r="3" />
                      </svg>
                    )}
                  </button>
                </div>
                {errors.password && (
                  <p className="member-auth-error" id="member-password-error" role="alert">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 8v5" /><path d="M12 17h.01" /></svg>
                    <span>{Array.isArray(errors.password) ? errors.password[0] : errors.password}</span>
                  </p>
                )}
              </div>

              <button className="member-auth-submit" type="submit" disabled={isSubmitting}>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" /><path d="m10 17 5-5-5-5" /><path d="M15 12H3" /></svg>
                <span>{isSubmitting ? 'Logging in…' : 'Login'}</span>
              </button>

              <div className="member-auth-divider" role="separator"><span>or continue with</span></div>

              <a className="member-auth-google" href={googleRedirectUrl}>
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <path fill="#4285F4" d="M21.6 12.23c0-.71-.06-1.4-.18-2.07H12v3.92h5.38a4.6 4.6 0 0 1-2 3.02v2.54h3.24c1.9-1.75 2.98-4.33 2.98-7.41Z" />
                  <path fill="#34A853" d="M12 22c2.7 0 4.98-.9 6.63-2.42l-3.24-2.54c-.9.6-2.05.96-3.39.96-2.61 0-4.82-1.76-5.61-4.13H3.04v2.62A10 10 0 0 0 12 22Z" />
                  <path fill="#FBBC05" d="M6.39 13.87A6 6 0 0 1 6.07 12c0-.65.11-1.28.32-1.87V7.51H3.04A10 10 0 0 0 2 12c0 1.61.38 3.14 1.04 4.49l3.35-2.62Z" />
                  <path fill="#EA4335" d="M12 6c1.47 0 2.79.51 3.82 1.5l2.88-2.88A9.65 9.65 0 0 0 12 2a10 10 0 0 0-8.96 5.51l3.35 2.62C7.18 7.76 9.39 6 12 6Z" />
                </svg>
                <span>Login with Google</span>
              </a>
            </form>

            <p className="member-auth-security">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3v8Z" /><path d="m9 12 2 2 4-4" /></svg>
              <span>Your data is protected and secure with us.</span>
            </p>
            <p className="member-auth-switch">Don’t have an account? <Link to="/member/register">Create account</Link></p>
          </div>
        </div>
      </section>
    </main>
  );
}

export default LoginPage;
