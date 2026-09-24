import { useState } from 'react';
import { Link, useParams, useSearchParams, useNavigate } from 'react-router-dom';
import authApi from '../../api/authApi';
import useBranding from '../../hooks/useBranding';
import { BRAND_LOGO } from '../../utils/assetHelper';

export function ResetPasswordPage() {
  const { logoUrl, siteName } = useBranding();
  const { token } = useParams();
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();

  const [formData, setFormData] = useState({
    token: token || '',
    email: searchParams.get('email') || '',
    password: '',
    password_confirmation: '',
  });

  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errors, setErrors] = useState({});
  const [generalError, setGeneralError] = useState('');

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    if (errors[name]) {
      setErrors((prev) => ({ ...prev, [name]: null }));
    }
    if (generalError) {
      setGeneralError('');
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    setGeneralError('');

    const clientErrors = {};
    if (!formData.email.trim()) clientErrors.email = ['Please enter your email address.'];
    if (!formData.password) clientErrors.password = ['Please enter a new password.'];
    else if (formData.password.length < 8) clientErrors.password = ['Password must be at least 8 characters long.'];
    if (formData.password !== formData.password_confirmation) {
      clientErrors.password_confirmation = ['Password confirmation does not match.'];
    }

    if (Object.keys(clientErrors).length > 0) {
      setErrors(clientErrors);
      return;
    }

    setIsSubmitting(true);

    try {
      const res = await authApi.resetPassword(formData);
      navigate('/member/login', {
        replace: true,
        state: { success: res?.message || 'Your password has been changed successfully. Please log in.' },
      });
    } catch (err) {
      if (err.response) {
        const { status, data } = err.response;
        if (status === 422 && data.errors) {
          setErrors(data.errors);
          if (data.message && !data.errors.email && !data.errors.password) {
            setGeneralError(data.message);
          }
        } else if (status === 429) {
          setGeneralError(data?.message || 'Too many reset attempts. Please try again later.');
        } else {
          setGeneralError(data?.message || 'Unable to reset password. Please request a new link.');
        }
      } else {
        setGeneralError('Unable to connect to the server. Please check your connection.');
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <main className="member-auth-page member-reset-page">
      <section className="member-auth-shell" aria-labelledby="member-reset-title">
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
                Create New Password
                <span className="member-auth-gradient-text">Secure your<br />account</span>
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
        <div className="member-auth-form-panel member-reset-form-panel">
          <span className="member-auth-ring member-auth-ring--form-top" aria-hidden="true" />
          <span className="member-auth-ring member-auth-ring--form-bottom" aria-hidden="true" />
          <span className="member-auth-dot member-auth-dot--four" aria-hidden="true" />
          <span className="member-auth-dot member-auth-dot--five" aria-hidden="true" />
          <span className="member-auth-dot-grid member-auth-dot-grid--form" aria-hidden="true" />

          <div className="member-auth-form-wrap">
            <header className="member-auth-form-heading">
              <h2 className="member-auth-title" id="member-reset-title">Create New Password</h2>
              <p className="member-auth-subtitle">Enter your new password below to update your account.</p>
            </header>

            {generalError && (
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
                >
                  <circle cx="12" cy="12" r="10" />
                  <line x1="12" y1="8" x2="12" y2="12" />
                  <line x1="12" y1="16" x2="12.01" y2="16" />
                </svg>
                <span>{generalError}</span>
              </div>
            )}

            <form className="member-auth-form member-reset-form" onSubmit={handleSubmit} noValidate>
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
                    placeholder="Enter your registered email address"
                    autoComplete="email"
                    required
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
                <label className="member-auth-label" htmlFor="password">New Password</label>
                <div className={`member-auth-input-wrap ${errors.password ? 'member-auth-input-wrap--error' : ''}`}>
                  <svg className="member-auth-input-icon" viewBox="0 0 24 24" aria-hidden="true"><rect width="16" height="12" x="4" y="9" rx="2" /><path d="M8 9V6a4 4 0 0 1 8 0v3" /><path d="M12 14v2" /></svg>
                  <input
                    className="member-auth-input member-auth-input--password"
                    id="password"
                    name="password"
                    type={showPassword ? 'text' : 'password'}
                    value={formData.password}
                    onChange={handleChange}
                    placeholder="Enter new password (min. 8 chars)"
                    autoComplete="new-password"
                    required
                    autoFocus
                    aria-invalid={errors.password ? 'true' : 'false'}
                    aria-describedby={errors.password ? 'member-password-error' : undefined}
                  />
                  <button
                    className="member-auth-password-toggle"
                    type="button"
                    aria-label={showPassword ? 'Hide password' : 'Show password'}
                    onClick={() => setShowPassword(!showPassword)}
                  >
                    {showPassword ? (
                      <svg className="member-auth-eye member-auth-eye--hide" viewBox="0 0 24 24" aria-hidden="true" style={{ display: 'block' }}><path d="m3 3 18 18" /><path d="M10.6 6.2A10 10 0 0 1 12 6c6 0 9.5 6 9.5 6a16 16 0 0 1-2.1 2.7" /><path d="M6.3 6.4C3.9 8.1 2.5 12 2.5 12s3.5 6 9.5 6a9.8 9.8 0 0 0 3.1-.5" /></svg>
                    ) : (
                      <svg className="member-auth-eye member-auth-eye--show" viewBox="0 0 24 24" aria-hidden="true" style={{ display: 'block' }}><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" /><circle cx="12" cy="12" r="2.5" /></svg>
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

              <div className="member-auth-field">
                <label className="member-auth-label" htmlFor="password_confirmation">Confirm New Password</label>
                <div className={`member-auth-input-wrap ${errors.password_confirmation ? 'member-auth-input-wrap--error' : ''}`}>
                  <svg className="member-auth-input-icon" viewBox="0 0 24 24" aria-hidden="true"><rect width="16" height="12" x="4" y="9" rx="2" /><path d="M8 9V6a4 4 0 0 1 8 0v3" /><path d="M12 14v2" /></svg>
                  <input
                    className="member-auth-input member-auth-input--password"
                    id="password_confirmation"
                    name="password_confirmation"
                    type={showConfirmPassword ? 'text' : 'password'}
                    value={formData.password_confirmation}
                    onChange={handleChange}
                    placeholder="Re-enter your new password"
                    autoComplete="new-password"
                    required
                  />
                  <button
                    className="member-auth-password-toggle"
                    type="button"
                    aria-label={showConfirmPassword ? 'Hide confirm password' : 'Show confirm password'}
                    onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                  >
                    {showConfirmPassword ? (
                      <svg className="member-auth-eye member-auth-eye--hide" viewBox="0 0 24 24" aria-hidden="true" style={{ display: 'block' }}><path d="m3 3 18 18" /><path d="M10.6 6.2A10 10 0 0 1 12 6c6 0 9.5 6 9.5 6a16 16 0 0 1-2.1 2.7" /><path d="M6.3 6.4C3.9 8.1 2.5 12 2.5 12s3.5 6 9.5 6a9.8 9.8 0 0 0 3.1-.5" /></svg>
                    ) : (
                      <svg className="member-auth-eye member-auth-eye--show" viewBox="0 0 24 24" aria-hidden="true" style={{ display: 'block' }}><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" /><circle cx="12" cy="12" r="2.5" /></svg>
                    )}
                  </button>
                </div>
                {errors.password_confirmation && (
                  <p className="member-auth-error" id="member-confirm-password-error" role="alert">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 8v5" /><path d="M12 17h.01" /></svg>
                    <span>{Array.isArray(errors.password_confirmation) ? errors.password_confirmation[0] : errors.password_confirmation}</span>
                  </p>
                )}
              </div>

              <button className="member-auth-submit" type="submit" disabled={isSubmitting}>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" /><path d="m10 17 5-5-5-5" /><path d="M15 12H3" /></svg>
                <span>{isSubmitting ? 'Resetting Password…' : 'Reset Password'}</span>
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

export default ResetPasswordPage;

