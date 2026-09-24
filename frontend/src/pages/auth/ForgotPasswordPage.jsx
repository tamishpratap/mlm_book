import { useState } from 'react';
import { Link } from 'react-router-dom';
import authApi from '../../api/authApi';
import useBranding from '../../hooks/useBranding';
import { BRAND_LOGO } from '../../utils/assetHelper';

export function ForgotPasswordPage() {
  const { logoUrl, siteName } = useBranding();
  const [email, setEmail] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [statusMessage, setStatusMessage] = useState('');
  const [errorMessage, setErrorMessage] = useState('');
  const [emailError, setEmailError] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    setStatusMessage('');
    setErrorMessage('');
    setEmailError('');

    if (!email.trim()) {
      setEmailError('Please enter your email address.');
      return;
    }

    setIsSubmitting(true);

    try {
      const res = await authApi.forgotPassword({ email });
      setStatusMessage(res?.message || 'A password reset link has been sent to your email address.');
      setEmail('');
    } catch (err) {
      if (err.response) {
        const { status, data } = err.response;
        if (status === 422 && data.errors?.email) {
          setEmailError(Array.isArray(data.errors.email) ? data.errors.email[0] : data.errors.email);
        } else if (status === 429) {
          setErrorMessage(data?.message || 'Too many reset attempts. Please try again later.');
        } else {
          setErrorMessage(data?.message || 'Unable to process your request. Please try again.');
        }
      } else {
        setErrorMessage('Unable to connect to the server. Please check your connection.');
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <main className="member-auth-page">
      <section className="member-auth-shell" aria-labelledby="member-forgot-title">
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
                <span>Password Security & Account Access</span>
              </div>

              <h1 className="member-auth-heading">
                Forgot Password?
                <span className="member-auth-gradient-text">We've got you<br />covered</span>
              </h1>
              <p className="member-auth-description">Don't worry! Enter your email address and we'll send you<br />a secure link to reset your password.</p>
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
              <h2 className="member-auth-title" id="member-forgot-title">Forgot Password</h2>
              <p className="member-auth-subtitle">Enter your registered email address to receive a reset link.</p>
            </header>

            {statusMessage && (
              <div className="member-auth-alert member-auth-alert--success" role="status" style={{ background: '#ecfdf5', borderColor: '#a7f3d0', color: '#065f46', marginBottom: '20px' }}>
                <svg viewBox="0 0 24 24" aria-hidden="true" style={{ color: '#10b981' }}><circle cx="12" cy="12" r="9" /><path d="m9 12 2 2 4-4" /></svg>
                <span>{statusMessage}</span>
              </div>
            )}

            {errorMessage && (
              <div className="member-auth-alert" role="alert">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 8v5" /><path d="M12 17h.01" /></svg>
                <span>{errorMessage}</span>
              </div>
            )}

            <form className="member-auth-form" onSubmit={handleSubmit} noValidate>
              <div className="member-auth-field">
                <label className="member-auth-label" htmlFor="email">Email address</label>
                <div className={`member-auth-input-wrap ${emailError ? 'member-auth-input-wrap--error' : ''}`}>
                  <svg className="member-auth-input-icon" viewBox="0 0 24 24" aria-hidden="true"><rect width="18" height="14" x="3" y="5" rx="2" /><path d="m3 7 9 6 9-6" /></svg>
                  <input
                    className="member-auth-input"
                    id="email"
                    name="email"
                    type="email"
                    value={email}
                    onChange={(e) => {
                      setEmail(e.target.value);
                      if (emailError) setEmailError('');
                      if (errorMessage) setErrorMessage('');
                    }}
                    placeholder="Enter your registered email address"
                    autoComplete="email"
                    required
                    autoFocus
                    aria-invalid={emailError ? 'true' : 'false'}
                    aria-describedby={emailError ? 'member-email-error' : undefined}
                  />
                </div>
                {emailError && (
                  <p className="member-auth-error" id="member-email-error" role="alert">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 8v5" /><path d="M12 17h.01" /></svg>
                    <span>{emailError}</span>
                  </p>
                )}
              </div>

              <button className="member-auth-submit" type="submit" disabled={isSubmitting}>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 2L11 13" /><path d="M22 2l-7 20-4-9-9-4 20-7z" /></svg>
                <span>{isSubmitting ? 'Sending Link…' : 'Send Reset Link'}</span>
              </button>
            </form>

            <p className="member-auth-security">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3v8Z" /><path d="m9 12 2 2 4-4" /></svg>
              <span>Your data is protected and secure with us.</span>
            </p>
            <p className="member-auth-switch"><Link to="/member/login">&larr; Back to Login</Link></p>
          </div>
        </div>
      </section>
    </main>
  );
}

export default ForgotPasswordPage;
