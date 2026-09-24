import { useState, useEffect, useRef, useCallback } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import authApi from '../../api/authApi';
import useBranding from '../../hooks/useBranding';
import { BRAND_LOGO } from '../../utils/assetHelper';

const COUNTRY_CODES = [
  { code: '+91', label: 'India (+91)' },
  { code: '+1', label: 'USA / Canada (+1)' },
  { code: '+44', label: 'UK (+44)' },
  { code: '+971', label: 'UAE (+971)' },
  { code: '+966', label: 'Saudi Arabia (+966)' },
  { code: '+65', label: 'Singapore (+65)' },
  { code: '+60', label: 'Malaysia (+60)' },
  { code: '+61', label: 'Australia (+61)' },
  { code: '+49', label: 'Germany (+49)' },
  { code: '+33', label: 'France (+33)' },
  { code: '+81', label: 'Japan (+81)' },
  { code: '+880', label: 'Bangladesh (+880)' },
  { code: '+977', label: 'Nepal (+977)' },
  { code: '+94', label: 'Sri Lanka (+94)' },
  { code: '+92', label: 'Pakistan (+92)' },
  { code: '+234', label: 'Nigeria (+234)' },
  { code: '+27', label: 'South Africa (+27)' },
  { code: '+55', label: 'Brazil (+55)' },
  { code: '+7', label: 'Russia (+7)' },
  { code: '+86', label: 'China (+86)' },
];

export function RegisterPage() {
  const { logoUrl, siteName } = useBranding();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();

  const initialRef = (searchParams.get('ref') || searchParams.get('introducer') || '')
    .toLowerCase()
    .replace(/[^a-z0-9_]/g, '');

  const rawError = searchParams.get('error') || '';
  const isAccountExists = rawError === 'account_exists' || searchParams.get('account_exists') === '1';

  const [formData, setFormData] = useState({
    name: '',
    introducer_id: initialRef,
    country_code: '+91',
    phone: '',
    email: searchParams.get('email') || '',
    password: '',
  });

  const googleRedirectUrl = authApi.getGoogleAuthUrl(formData.introducer_id || initialRef, 'register');

  // Phone validation & availability states
  const [phoneStatus, setPhoneStatus] = useState('idle'); // 'idle' | 'checking' | 'available' | 'taken' | 'invalid'
  const [phoneMessage, setPhoneMessage] = useState('');
  const phoneCheckTimer = useRef(null);
  const latestPhoneRequestId = useRef(0);

  // Introducer resolution states
  const [introducerStatus, setIntroducerStatus] = useState('idle'); // 'idle' | 'checking' | 'valid' | 'invalid' | 'ineligible'
  const [introducerName, setIntroducerName] = useState('');
  const [introducerMessage, setIntroducerMessage] = useState('');
  const [isEditingIntroducer, setIsEditingIntroducer] = useState(false);
  const [manualIntroducerVal, setManualIntroducerVal] = useState(initialRef);
  const introducerCheckTimer = useRef(null);

  const resolveIntroducer = useCallback(async (val) => {
    if (!val || val.length < 4) {
      setIntroducerStatus('idle');
      setIntroducerName('');
      setIntroducerMessage('');
      return;
    }

    setIntroducerStatus('checking');
    setIntroducerMessage('Verifying Introducer…');

    try {
      const res = await authApi.checkIntroducer(val);
      if (res.valid) {
        setIntroducerStatus('valid');
        setIntroducerName(res.name);
        setIntroducerMessage(res.message || `Introduced by ${res.name}`);
        setFormData((prev) => ({ ...prev, introducer_id: res.user_id }));
      } else if (res.exists) {
        setIntroducerStatus('ineligible');
        setIntroducerName(res.name || '');
        setIntroducerMessage(res.message || 'The selected Introducer is not eligible to refer members until their mobile number is verified.');
        setFormData((prev) => ({ ...prev, introducer_id: res.user_id }));
      } else {
        setIntroducerStatus('invalid');
        setIntroducerName('');
        setIntroducerMessage(res.message || 'The selected Introducer ID does not exist.');
        setFormData((prev) => ({ ...prev, introducer_id: val }));
      }
    } catch {
      setIntroducerStatus('idle');
      setIntroducerMessage('');
    }
  }, []);

  useEffect(() => {
    const refFromUrl = (searchParams.get('ref') || searchParams.get('introducer') || '')
      .toLowerCase()
      .replace(/[^a-z0-9_]/g, '');

    if (refFromUrl) {
      setFormData((prev) => ({ ...prev, introducer_id: refFromUrl }));
      setManualIntroducerVal(refFromUrl);
      setIsEditingIntroducer(false);
      resolveIntroducer(refFromUrl);
    } else {
      setFormData((prev) => ({ ...prev, introducer_id: '' }));
      setManualIntroducerVal('');
      setIntroducerName('');
      setIntroducerStatus('idle');
      setIntroducerMessage('');
      setIsEditingIntroducer(false);
    }
  }, [searchParams, resolveIntroducer]);

  const [showPassword, setShowPassword] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errors, setErrors] = useState({});
  const [generalError, setGeneralError] = useState('');
  const generalErrorTimerRef = useRef(null);

  const clearGeneralErrorTimer = useCallback(() => {
    if (generalErrorTimerRef.current) {
      clearTimeout(generalErrorTimerRef.current);
      generalErrorTimerRef.current = null;
    }
  }, []);

  const clearGeneralError = useCallback(() => {
    clearGeneralErrorTimer();
    setGeneralError('');
  }, [clearGeneralErrorTimer]);

  const triggerGeneralError = useCallback((msg) => {
    clearGeneralErrorTimer();
    setGeneralError(msg);
    if (msg) {
      generalErrorTimerRef.current = setTimeout(() => {
        setGeneralError('');
        generalErrorTimerRef.current = null;
      }, 3000);
    }
  }, [clearGeneralErrorTimer]);


  const checkPhoneAvailability = useCallback(async (num, code = '+91') => {
    const rawDigits = (num || '').replace(/\D/g, '');
    if (!rawDigits || rawDigits.length < 7 || rawDigits.length > 15) {
      setPhoneStatus('invalid');
      setPhoneMessage('Please enter a valid phone number (7 to 15 digits).');
      return;
    }

    const requestId = ++latestPhoneRequestId.current;
    setPhoneStatus('checking');
    setPhoneMessage('Checking phone number availability…');

    try {
      const res = await authApi.checkPhone(rawDigits, code);
      if (requestId !== latestPhoneRequestId.current) {
        return;
      }
      if (res.available) {
        setPhoneStatus('available');
        setPhoneMessage('Phone number is available.');
      } else {
        setPhoneStatus('taken');
        setPhoneMessage(res.message || 'This number already exists. Please use the existing login option.');
      }
    } catch (err) {
      if (requestId !== latestPhoneRequestId.current) {
        return;
      }
      const serverMessage = err.response?.data?.message;
      setPhoneStatus('invalid');
      setPhoneMessage(serverMessage || 'Could not verify phone number.');
    }
  }, []);

  const handlePhoneChange = (e) => {
    const val = e.target.value;
    setFormData((prev) => ({ ...prev, phone: val }));

    if (errors.phone) {
      setErrors((prev) => ({ ...prev, phone: null }));
    }
    clearGeneralError();

    if (phoneCheckTimer.current) {
      clearTimeout(phoneCheckTimer.current);
    }

    const cleanDigits = val.replace(/\D/g, '');
    if (!cleanDigits) {
      setPhoneStatus('idle');
      setPhoneMessage('');
      return;
    }

    if (cleanDigits.length < 10) {
      setPhoneStatus('idle');
      setPhoneMessage('Enter at least 10 digits for your phone number.');
      return;
    }

    phoneCheckTimer.current = setTimeout(() => {
      checkPhoneAvailability(cleanDigits, formData.country_code);
    }, 400);
  };

  const handleCountryCodeChange = (e) => {
    const code = e.target.value;
    setFormData((prev) => ({ ...prev, country_code: code }));
    if (formData.phone) {
      const cleanDigits = formData.phone.replace(/\D/g, '');
      if (cleanDigits.length >= 10) {
        checkPhoneAvailability(cleanDigits, code);
      }
    }
  };

  const handleIntroducerChange = (e) => {
    const rawVal = e.target.value;
    const cleanVal = rawVal.toLowerCase().replace(/[^a-z0-9_]/g, '');
    setManualIntroducerVal(cleanVal);
    setFormData((prev) => ({ ...prev, introducer_id: cleanVal }));

    if (errors.introducer_id) {
      setErrors((prev) => ({ ...prev, introducer_id: null }));
    }
    clearGeneralError();

    if (introducerCheckTimer.current) {
      clearTimeout(introducerCheckTimer.current);
    }

    if (!cleanVal) {
      setIntroducerStatus('idle');
      setIntroducerName('');
      setIntroducerMessage('');
      return;
    }

    introducerCheckTimer.current = setTimeout(() => {
      resolveIntroducer(cleanVal);
    }, 400);
  };

  useEffect(() => {
    return () => {
      if (phoneCheckTimer.current) {
        clearTimeout(phoneCheckTimer.current);
      }
      if (introducerCheckTimer.current) {
        clearTimeout(introducerCheckTimer.current);
      }
      clearGeneralErrorTimer();
    };
  }, [clearGeneralErrorTimer]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    if (errors[name]) {
      setErrors((prev) => ({ ...prev, [name]: null }));
    }
    clearGeneralError();
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    clearGeneralError();

    // Client UX check
    const clientErrors = {};
    if (!formData.name.trim()) clientErrors.name = ['Please enter your full name.'];
    if (!formData.phone.trim()) {
      clientErrors.phone = ['Please enter your WhatsApp/mobile number.'];
    } else {
      const digits = formData.phone.replace(/\D/g, '');
      if (digits.length < 7 || digits.length > 15) {
        clientErrors.phone = ['Please enter a valid phone number (7 to 15 digits).'];
      } else if (phoneStatus === 'taken') {
        clientErrors.phone = [phoneMessage || 'This number already exists. Please use the existing login option.'];
      }
    }
    if (!formData.email.trim()) clientErrors.email = ['Please enter your email address.'];
    if (!formData.password) clientErrors.password = ['Please enter a password.'];
    else if (formData.password.length < 8) clientErrors.password = ['Password must be at least 8 characters long.'];

    if (Object.keys(clientErrors).length > 0) {
      setErrors(clientErrors);
      return;
    }

    setIsSubmitting(true);

    try {
      const response = await authApi.register(formData);
      navigate('/member/register/verify', {
        state: {
          email: formData.email,
          name: formData.name,
          status: response?.message || 'A 6-digit verification code has been sent to your email address.',
        },
      });
    } catch (err) {
      if (err.response) {
        const { status, data } = err.response;
        if (status === 422 && data.errors) {
          setErrors(data.errors);
          if (data.message && Object.keys(data.errors).length === 0) {
            triggerGeneralError(data.message);
          }
        } else if (status === 429) {
          triggerGeneralError(data.message || 'Too many registration attempts. Please try again in a few moments.');
        } else {
          triggerGeneralError(data.message || 'An error occurred during registration. Please try again.');
        }
      } else {
        triggerGeneralError('Unable to connect to the server. Please check your connection.');
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <main className="member-auth-page member-register-page">
      <section className="member-auth-shell" aria-labelledby="member-register-title">
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
                <span>Your community, your world</span>
              </div>

              <h1 className="member-auth-heading">
                Join your community
                <span className="member-auth-gradient-text">Start a new<br />journey today</span>
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
        <div className="member-auth-form-panel member-register-form-panel">
          <span className="member-auth-ring member-auth-ring--form-top" aria-hidden="true" />
          <span className="member-auth-ring member-auth-ring--form-bottom" aria-hidden="true" />
          <span className="member-auth-dot member-auth-dot--four" aria-hidden="true" />
          <span className="member-auth-dot member-auth-dot--five" aria-hidden="true" />
          <span className="member-auth-dot-grid member-auth-dot-grid--form" aria-hidden="true" />

          <div className="member-auth-form-wrap">
            <header className="member-auth-form-heading">
              <h2 className="member-auth-title" id="member-register-title">Create your account</h2>
              <p className="member-auth-subtitle">Join the community and start your journey today.</p>
            </header>

            {isAccountExists ? (
              <div
                className="member-auth-alert member-auth-alert--account-exists"
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
                      Account Already Exists
                    </strong>
                    <p style={{ margin: 0, fontSize: '13.5px', lineHeight: '1.45', color: '#1e40af' }}>
                      An MLM Book account already exists for this Google account. Please log in instead.
                    </p>
                  </div>
                </div>

                <div style={{ display: 'flex', gap: '10px', marginTop: '2px' }}>
                  <Link
                    to="/member/login"
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
                    <span>Login</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" /><path d="m10 17 5-5-5-5" /><path d="M15 12H3" /></svg>
                  </Link>

                  <button
                    type="button"
                    onClick={() => {
                      clearGeneralError();
                      navigate(initialRef ? `/member/register?ref=${encodeURIComponent(initialRef)}` : '/member/register', { replace: true });
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
                    Dismiss
                  </button>
                </div>
              </div>
            ) : generalError ? (
              <div className="member-auth-error" role="alert" style={{ marginBottom: '18px', padding: '12px 16px', borderRadius: '10px', background: '#fef2f2', border: '1px solid #fee2e2', color: '#b91c1c', fontSize: '14px' }}>
                <span>{generalError}</span>
              </div>
            ) : null}

            <form className="member-auth-form member-register-form" onSubmit={handleSubmit} noValidate>
              <div className="member-auth-field" data-member-introducer-id-field>
                <label className="member-auth-label" htmlFor="introducerId">
                  Introducer <span className="member-register-confirm-help" style={{ fontWeight: 'normal', color: 'var(--color-text-secondary, #64748b)' }}>(Optional)</span>
                </label>
                <div
                  className={`member-auth-input-wrap ${
                    errors.introducer_id || introducerStatus === 'invalid' || introducerStatus === 'ineligible'
                      ? 'member-auth-input-wrap--error'
                      : ''
                  }`}
                  style={{ position: 'relative' }}
                >
                  <svg className="member-auth-input-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                  </svg>
                  <input
                    className="member-auth-input"
                    id="introducerId"
                    name="introducer_id"
                    type="text"
                    value={
                      isEditingIntroducer
                        ? manualIntroducerVal
                        : introducerName
                        ? introducerName
                        : formData.introducer_id
                    }
                    onChange={handleIntroducerChange}
                    onFocus={() => {
                      if (!isEditingIntroducer && introducerName) {
                        setIsEditingIntroducer(true);
                        setManualIntroducerVal(formData.introducer_id);
                      }
                    }}
                    placeholder="Referrer's User ID (if referred)"
                    autoComplete="off"
                    maxLength={30}
                    spellCheck="false"
                    autoCapitalize="none"
                    aria-describedby="introducerIdFeedback"
                    aria-invalid={
                      errors.introducer_id || introducerStatus === 'invalid' || introducerStatus === 'ineligible'
                        ? 'true'
                        : 'false'
                    }
                    style={
                      !isEditingIntroducer && introducerName && introducerStatus === 'valid'
                        ? { paddingRight: '70px', fontWeight: 600, color: '#1e293b' }
                        : {}
                    }
                  />
                  {!isEditingIntroducer && introducerName && (
                    <button
                      type="button"
                      onClick={() => {
                        setIsEditingIntroducer(true);
                        setManualIntroducerVal(formData.introducer_id);
                      }}
                      style={{
                        position: 'absolute',
                        right: '10px',
                        top: '50%',
                        transform: 'translateY(-50%)',
                        background: '#eff6ff',
                        border: '1px solid #bfdbfe',
                        borderRadius: '6px',
                        color: '#1d4ed8',
                        fontSize: '11px',
                        fontWeight: 600,
                        cursor: 'pointer',
                        padding: '3px 8px',
                        lineHeight: 1.4,
                      }}
                    >
                      Change
                    </button>
                  )}
                </div>

                {errors.introducer_id ? (
                  <p className="member-auth-error" id="introducerIdFeedback" role="alert">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 8v5" /><path d="M12 17h.01" /></svg>
                    <span>{Array.isArray(errors.introducer_id) ? errors.introducer_id[0] : errors.introducer_id}</span>
                  </p>
                ) : introducerStatus === 'valid' && introducerName ? (
                  <div
                    id="introducerIdFeedback"
                    className="member-user-id-feedback is-valid"
                    style={{ color: '#059669', display: 'flex', alignItems: 'center', gap: '6px', fontSize: '13px', marginTop: '6px' }}
                  >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#059669" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                      <polyline points="20 6 9 17 4 12" />
                    </svg>
                    <span>Referred by <strong>{introducerName}</strong> (@{formData.introducer_id})</span>
                  </div>
                ) : introducerStatus === 'ineligible' ? (
                  <p className="member-auth-error" id="introducerIdFeedback" role="alert">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 8v5" /><path d="M12 17h.01" /></svg>
                    <span>{introducerMessage || 'The selected Introducer is not eligible to refer members until their mobile number is verified.'}</span>
                  </p>
                ) : introducerStatus === 'invalid' ? (
                  <p className="member-auth-error" id="introducerIdFeedback" role="alert">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 8v5" /><path d="M12 17h.01" /></svg>
                    <span>{introducerMessage || 'The selected Introducer ID does not exist.'}</span>
                  </p>
                ) : introducerStatus === 'checking' ? (
                  <span className="member-register-confirm-help" id="introducerIdFeedback">
                    Verifying Introducer details…
                  </span>
                ) : (
                  <span className="member-register-confirm-help" id="introducerIdFeedback">
                    Leave blank if you do not have an introducer.
                  </span>
                )}
              </div>

              <div className="member-auth-field">
                <label className="member-auth-label" htmlFor="name">Full name</label>
                <div className={`member-auth-input-wrap ${errors.name ? 'member-auth-input-wrap--error' : ''}`}>
                  <svg className="member-auth-input-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4" /><path d="M4 21a8 8 0 0 1 16 0" /></svg>
                  <input
                    className="member-auth-input"
                    id="name"
                    name="name"
                    type="text"
                    value={formData.name}
                    onChange={handleChange}
                    placeholder="Enter your full name"
                    autoComplete="name"
                    required
                    autoFocus
                    aria-invalid={errors.name ? 'true' : 'false'}
                    aria-describedby={errors.name ? 'member-name-error' : undefined}
                  />
                </div>
                {errors.name && (
                  <p className="member-auth-error" id="member-name-error" role="alert">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 8v5" /><path d="M12 17h.01" /></svg>
                    <span>{Array.isArray(errors.name) ? errors.name[0] : errors.name}</span>
                  </p>
                )}
              </div>


              <div className="member-auth-field" data-member-phone-field>
                <label className="member-auth-label" htmlFor="phone">
                  WhatsApp / Mobile Number
                </label>
                <div
                  className={`member-auth-input-wrap ${
                    errors.phone || phoneStatus === 'taken' || phoneStatus === 'invalid'
                      ? 'member-auth-input-wrap--error'
                      : phoneStatus === 'available'
                      ? 'is-available'
                      : ''
                  }`}
                  style={{ display: 'flex', alignItems: 'center' }}
                >
                  <svg className="member-auth-input-icon" viewBox="0 0 24 24" aria-hidden="true" style={{ flexShrink: 0 }}>
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                  </svg>
                  <select
                    id="countryCode"
                    name="country_code"
                    value={formData.country_code}
                    onChange={handleCountryCodeChange}
                    aria-label="Country Code"
                    style={{
                      border: 'none',
                      outline: 'none',
                      background: 'transparent',
                      fontSize: '13px',
                      fontWeight: 600,
                      color: '#334155',
                      paddingRight: '6px',
                      cursor: 'pointer',
                      borderRight: '1px solid #e2e8f0',
                      marginRight: '8px',
                      height: '100%',
                    }}
                  >
                    {COUNTRY_CODES.map((item) => (
                      <option key={item.code} value={item.code}>
                        {item.code}
                      </option>
                    ))}
                  </select>
                  <input
                    className="member-auth-input"
                    id="phone"
                    name="phone"
                    type="tel"
                    value={formData.phone}
                    onChange={handlePhoneChange}
                    placeholder="Enter WhatsApp / mobile number"
                    autoComplete="tel"
                    required
                    aria-invalid={errors.phone || phoneStatus === 'taken' || phoneStatus === 'invalid' ? 'true' : 'false'}
                    aria-describedby="member-phone-feedback"
                    style={{ border: 'none', paddingLeft: '0', flex: 1 }}
                  />
                </div>

                {(errors.phone || phoneStatus === 'taken') ? (
                  <div className="member-auth-error" id="member-phone-feedback" role="alert" style={{ display: 'flex', flexDirection: 'column', gap: '4px', marginTop: '6px' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                      <svg viewBox="0 0 24 24" aria-hidden="true" style={{ width: '16px', height: '16px', flexShrink: 0 }}>
                        <circle cx="12" cy="12" r="9" /><path d="M12 8v5" /><path d="M12 17h.01" />
                      </svg>
                      <span>
                        {errors.phone
                          ? (Array.isArray(errors.phone) ? errors.phone[0] : errors.phone)
                          : phoneMessage}
                      </span>
                    </div>
                    {(phoneStatus === 'taken' || (typeof errors.phone === 'string' && errors.phone.includes('already exists')) || (Array.isArray(errors.phone) && errors.phone[0]?.includes('already exists'))) && (
                      <div style={{ marginTop: '2px', paddingLeft: '22px' }}>
                        <Link
                          to="/member/login"
                          style={{
                            color: '#2563eb',
                            fontWeight: 600,
                            textDecoration: 'underline',
                            fontSize: '12px',
                          }}
                        >
                          Go to Member Login &rarr;
                        </Link>
                      </div>
                    )}
                  </div>
                ) : phoneStatus === 'available' ? (
                  <div
                    id="member-phone-feedback"
                    className="member-user-id-feedback is-valid"
                    style={{ color: '#059669', display: 'flex', alignItems: 'center', gap: '6px', fontSize: '13px', marginTop: '6px' }}
                  >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#059669" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                      <polyline points="20 6 9 17 4 12" />
                    </svg>
                    <span>Phone number is available.</span>
                  </div>
                ) : (
                  <span className="member-register-confirm-help" id="member-phone-feedback">
                    WhatsApp / mobile number for notifications and verification.
                  </span>
                )}
              </div>

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
                    aria-invalid={errors.email ? 'true' : 'false'}
                    aria-describedby={errors.email ? 'member-register-email-error' : undefined}
                  />
                </div>
                {errors.email && (
                  <p className="member-auth-error" id="member-register-email-error" role="alert">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 8v5" /><path d="M12 17h.01" /></svg>
                    <span>{Array.isArray(errors.email) ? errors.email[0] : errors.email}</span>
                  </p>
                )}
              </div>

              <div className="member-auth-field">
                <label className="member-auth-label" htmlFor="password">Password</label>
                <div className={`member-auth-input-wrap ${errors.password ? 'member-auth-input-wrap--error' : ''}`}>
                  <svg className="member-auth-input-icon" viewBox="0 0 24 24" aria-hidden="true"><rect width="16" height="12" x="4" y="9" rx="2" /><path d="M8 9V6a4 4 0 0 1 8 0v3" /><path d="M12 14v2" /></svg>
                  <input
                    className="member-auth-input member-auth-input--password"
                    id="password"
                    name="password"
                    type={showPassword ? 'text' : 'password'}
                    value={formData.password}
                    onChange={handleChange}
                    placeholder="Create a strong password"
                    autoComplete="new-password"
                    required
                    aria-invalid={errors.password ? 'true' : 'false'}
                    aria-describedby={errors.password ? 'member-register-password-error' : 'member-password-help'}
                  />
                  <button
                    className="member-auth-password-toggle"
                    type="button"
                    aria-label={showPassword ? 'Hide password' : 'Show password'}
                    aria-controls="password"
                    onClick={() => setShowPassword(!showPassword)}
                  >
                    {showPassword ? (
                      <svg className="member-auth-eye member-auth-eye--hide" viewBox="0 0 24 24" aria-hidden="true" style={{ display: 'block' }}><path d="m3 3 18 18" /><path d="M10.6 6.2A10 10 0 0 1 12 6c6 0 9.5 6 9.5 6a16 16 0 0 1-2.1 2.7" /><path d="M6.3 6.4C3.9 8.1 2.5 12 2.5 12s3.5 6 9.5 6a9.8 9.8 0 0 0 3.1-.5" /></svg>
                    ) : (
                      <svg className="member-auth-eye member-auth-eye--show" viewBox="0 0 24 24" aria-hidden="true" style={{ display: 'block' }}><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" /><circle cx="12" cy="12" r="2.5" /></svg>
                    )}
                  </button>
                </div>
                <span className="member-register-confirm-help" id="member-password-help">Password must be at least 8 characters long.</span>
                {errors.password && (
                  <p className="member-auth-error" id="member-register-password-error" role="alert">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 8v5" /><path d="M12 17h.01" /></svg>
                    <span>{Array.isArray(errors.password) ? errors.password[0] : errors.password}</span>
                  </p>
                )}
              </div>


              <button className="member-auth-submit" type="submit" disabled={isSubmitting}>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M19 8v6" /><path d="M22 11h-6" /></svg>
                <span>{isSubmitting ? 'Creating account…' : 'Create account'}</span>
              </button>

              <div className="member-auth-divider" role="separator"><span>or continue with</span></div>

              <a className="member-auth-google" href={googleRedirectUrl}>
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <path fill="#4285F4" d="M21.6 12.23c0-.71-.06-1.4-.18-2.07H12v3.92h5.38a4.6 4.6 0 0 1-2 3.02v2.54h3.24c1.9-1.75 2.98-4.33 2.98-7.41Z" />
                  <path fill="#34A853" d="M12 22c2.7 0 4.98-.9 6.63-2.42l-3.24-2.54c-.9.6-2.05.96-3.39.96-2.61 0-4.82-1.76-5.61-4.13H3.04v2.62A10 10 0 0 0 12 22Z" />
                  <path fill="#FBBC05" d="M6.39 13.87A6 6 0 0 1 6.07 12c0-.65.11-1.28.32-1.87V7.51H3.04A10 10 0 0 0 2 12c0 1.61.38 3.14 1.04 4.49l3.35-2.62Z" />
                  <path fill="#EA4335" d="M12 6c1.47 0 2.79.51 3.82 1.5l2.88-2.88A9.65 9.65 0 0 0 12 2a10 10 0 0 0-8.96 5.51l3.35 2.62C7.18 7.76 9.39 6 12 6Z" />
                </svg>
                <span>Sign up with Google</span>
              </a>
            </form>

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

export default RegisterPage;
