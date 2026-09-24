import { useState, useEffect, useRef, useCallback } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import useAuth from '../../hooks/useAuth';
import useBranding from '../../hooks/useBranding';
import authApi from '../../api/authApi';
import { BRAND_LOGO } from '../../utils/assetHelper';

export function VerifyEmailPage() {
  const { refreshUser } = useAuth();
  const { logoUrl, siteName } = useBranding();
  const navigate = useNavigate();
  const location = useLocation();

  const [email, setEmail] = useState(location.state?.email || '');
  const [otp, setOtp] = useState('');
  const [cooldown, setCooldown] = useState(60);
  const [isVerifying, setIsVerifying] = useState(false);
  const [isResending, setIsResending] = useState(false);
  const [isCanceling, setIsCanceling] = useState(false);
  const [error, setError] = useState('');
  const [statusMessage, setStatusMessage] = useState(
    location.state?.status || 'A 6-digit verification code has been sent to your email address.'
  );

  const timerRef = useRef(null);

  // Countdown timer handler
  const startCooldownTimer = useCallback((seconds) => {
    if (timerRef.current) clearInterval(timerRef.current);
    setCooldown(seconds);
    timerRef.current = setInterval(() => {
      setCooldown((prev) => {
        if (prev <= 1) {
          clearInterval(timerRef.current);
          return 0;
        }
        return prev - 1;
      });
    }, 1000);
  }, []);

  // Fetch initial verify session state from server if email is missing
  useEffect(() => {
    let isMounted = true;

    async function checkStatus() {
      try {
        const res = await authApi.getVerifyStatus();
        if (!isMounted) return;
        if (res.valid) {
          setEmail(res.email);
          if (res.resend_cooldown > 0) {
            startCooldownTimer(res.resend_cooldown);
          } else {
            startCooldownTimer(0);
          }
        }
      } catch (err) {
        if (!isMounted) return;
        // If session expired or invalid, redirect to register
        if (err.response?.status === 422 || err.response?.status === 401) {
          navigate('/member/register', {
            replace: true,
            state: { error: 'Your registration session has expired. Please register again.' },
          });
        }
      }
    }

    if (!email) {
      checkStatus();
    } else {
      timerRef.current = setInterval(() => {
        setCooldown((prev) => {
          if (prev <= 1) {
            clearInterval(timerRef.current);
            return 0;
          }
          return prev - 1;
        });
      }, 1000);
    }

    return () => {
      isMounted = false;
      if (timerRef.current) clearInterval(timerRef.current);
    };
  }, [email, navigate, startCooldownTimer]);

  const handleOtpChange = (e) => {
    const val = e.target.value.replace(/[^0-9]/g, '').slice(0, 6);
    setOtp(val);
    if (error) setError('');
  };

  const handleVerify = async (e) => {
    e.preventDefault();
    setError('');
    setStatusMessage('');

    if (!otp || otp.length !== 6) {
      setError('Please enter the full 6-digit verification code.');
      return;
    }

    setIsVerifying(true);

    try {
      await authApi.verifyEmailOtp({ otp });
      await refreshUser();
      navigate('/member/dashboard', { replace: true });
    } catch (err) {
      if (err.response) {
        const { status, data } = err.response;
        if (status === 422) {
          const otpErr = data.errors?.otp;
          const emailErr = data.errors?.email;
          const userErr = data.errors?.user_id;

          if (otpErr) {
            setError(Array.isArray(otpErr) ? otpErr[0] : otpErr);
          } else if (emailErr || userErr) {
            navigate('/member/register', {
              state: { error: (emailErr && emailErr[0]) || (userErr && userErr[0]) },
            });
          } else {
            setError(data.message || 'The verification code is incorrect.');
          }
        } else if (status === 429) {
          setError(data.message || 'Too many attempts. Please try again in a few moments.');
        } else {
          setError(data.message || 'An error occurred during verification.');
        }
      } else {
        setError('Unable to connect to the server. Please check your connection.');
      }
    } finally {
      setIsVerifying(false);
    }
  };

  const handleResendOtp = async (e) => {
    e.preventDefault();
    if (cooldown > 0 || isResending) return;

    setIsResending(true);
    setError('');
    setStatusMessage('');

    try {
      const res = await authApi.resendRegistrationOtp();
      setStatusMessage(res?.message || 'A new 6-digit verification code has been sent to your email.');
      startCooldownTimer(res?.resend_cooldown || 60);
    } catch (err) {
      if (err.response) {
        const { data } = err.response;
        setError(data?.message || data?.errors?.otp?.[0] || 'Unable to resend code right now.');
      } else {
        setError('Network error. Please try again.');
      }
    } finally {
      setIsResending(false);
    }
  };

  const handleCancelRegistration = async (e) => {
    e.preventDefault();
    if (isCanceling) return;

    setIsCanceling(true);
    try {
      await authApi.cancelRegistration();
    } catch {
      // ignore
    } finally {
      setIsCanceling(false);
      navigate('/member/register', { replace: true });
    }
  };

  return (
    <main className="member-auth-page member-verify-page">
      <section className="member-auth-shell" aria-labelledby="member-verify-title">
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
            <Link className="member-auth-logo" to="/member/register" aria-label={`${siteName || 'MLM Book'} Member Registration`}>
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
                <span>Security & Privacy First</span>
              </div>

              <h1 className="member-auth-heading">
                Almost there!
                <span className="member-auth-gradient-text">Verify your<br />email address</span>
              </h1>
              <p className="member-auth-description">We verify every email address to keep the MLM Book community authentic, trusted, and secure.</p>
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
                  <strong>100%</strong>
                  <small>Verified community</small>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Form Panel */}
        <div className="member-auth-form-panel member-verify-form-panel">
          <span className="member-auth-ring member-auth-ring--form-top" aria-hidden="true" />
          <span className="member-auth-ring member-auth-ring--form-bottom" aria-hidden="true" />
          <span className="member-auth-dot member-auth-dot--four" aria-hidden="true" />
          <span className="member-auth-dot member-auth-dot--five" aria-hidden="true" />
          <span className="member-auth-dot-grid member-auth-dot-grid--form" aria-hidden="true" />

          <div className="member-auth-form-wrap">
            <header className="member-auth-form-heading">
              <div className="member-verify-icon-badge" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <rect width="20" height="16" x="2" y="4" rx="2" />
                  <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                </svg>
              </div>
              <h2 className="member-auth-title" id="member-verify-title">Verify Your Email</h2>
              <p className="member-auth-subtitle">Enter the 6-digit verification code sent to your email address.</p>
            </header>

            {email && (
              <div className="member-verify-destination">
                <span className="member-verify-destination-label">Verification code sent to:</span>
                <strong className="member-verify-destination-email">{email}</strong>
              </div>
            )}

            {statusMessage && (
              <div className="member-verify-alert member-verify-alert--success" role="alert">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="m9 12 2 2 4-4" /></svg>
                <span>{statusMessage}</span>
              </div>
            )}

            {error && (
              <div className="member-auth-error" role="alert" style={{ marginBottom: '16px', padding: '10px 14px', borderRadius: '8px', background: '#fef2f2', border: '1px solid #fee2e2', color: '#b91c1c', fontSize: '14px' }}>
                <span>{error}</span>
              </div>
            )}

            <form className="member-auth-form member-verify-form" onSubmit={handleVerify} id="memberVerifyForm" noValidate>
              <div className="member-auth-field">
                <label className="member-auth-label member-verify-code-label" htmlFor="otpInput">Verification Code</label>

                <div className={`member-verify-otp-container ${error ? 'member-auth-input-wrap--error' : ''}`}>
                  <input
                    className="member-verify-otp-input"
                    id="otpInput"
                    name="otp"
                    type="text"
                    inputMode="numeric"
                    pattern="[0-9]*"
                    autoComplete="one-time-code"
                    maxLength={6}
                    placeholder="• • • • • •"
                    value={otp}
                    onChange={handleOtpChange}
                    required
                    autoFocus
                    spellCheck="false"
                    aria-invalid={error ? 'true' : 'false'}
                    aria-describedby={error ? 'member-verify-otp-error' : undefined}
                  />
                </div>

                <span className="member-register-confirm-help member-verify-help">
                  Code expires in 10 minutes.
                </span>
              </div>

              <button className="member-auth-submit member-verify-submit" type="submit" disabled={isVerifying || otp.length !== 6}>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" /><polyline points="22 4 12 14.01 9 11.01" /></svg>
                <span>{isVerifying ? 'Verifying…' : 'Verify Email & Create Account'}</span>
              </button>
            </form>

            <div className="member-verify-actions">
              <div className="member-verify-resend-wrap">
                <span className="member-verify-resend-text">Didn't receive the code?</span>
                <form onSubmit={handleResendOtp} className="member-verify-inline-form">
                  <button
                    type="submit"
                    className="member-verify-resend-btn"
                    disabled={cooldown > 0 || isResending}
                  >
                    <span>
                      {isResending
                        ? 'Sending…'
                        : cooldown > 0
                        ? `Resend in ${cooldown}s`
                        : 'Resend Code'}
                    </span>
                  </button>
                </form>
              </div>

              <form onSubmit={handleCancelRegistration} className="member-verify-cancel-form">
                <button type="submit" className="member-verify-cancel-btn" disabled={isCanceling}>
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
                  <span>{isCanceling ? 'Cancelling…' : 'Change email or Start Over'}</span>
                </button>
              </form>
            </div>

            <p className="member-auth-switch member-register-login-link">Already have an account? <Link to="/member/login">Login</Link></p>

            <p className="member-auth-security member-register-security">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3v8Z" /><path d="m9 12 2 2 4-4" /></svg>
              <span>Your data is protected and secure with us.</span>
            </p>
          </div>
        </div>
      </section>
    </main>
  );
}

export default VerifyEmailPage;
