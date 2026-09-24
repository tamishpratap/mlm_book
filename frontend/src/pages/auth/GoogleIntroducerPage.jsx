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

export function GoogleIntroducerPage() {
  const { logoUrl, siteName } = useBranding();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();

  const token = searchParams.get('token') || '';
  const initialRef = (searchParams.get('ref') || searchParams.get('introducer') || '')
    .toLowerCase()
    .replace(/[^a-z0-9_]/g, '');

  const [pendingUser, setPendingUser] = useState(null);
  const [isLoadingPending, setIsLoadingPending] = useState(true);
  const [loadError, setLoadError] = useState('');

  // Phone / WhatsApp Number state
  const [phone, setPhone] = useState('');
  const [countryCode, setCountryCode] = useState('+91');
  const [phoneStatus, setPhoneStatus] = useState('idle'); // 'idle' | 'checking' | 'available' | 'taken' | 'invalid'
  const [phoneMessage, setPhoneMessage] = useState('');
  const [phoneError, setPhoneError] = useState('');
  const phoneCheckTimer = useRef(null);
  const latestPhoneRequestId = useRef(0);

  // Introducer state (optional)
  const [introducerId, setIntroducerId] = useState(initialRef);
  const [manualIntroducerVal, setManualIntroducerVal] = useState(initialRef);
  const [isEditingIntroducer, setIsEditingIntroducer] = useState(false);
  const [introducerStatus, setIntroducerStatus] = useState('idle'); // 'idle' | 'checking' | 'valid' | 'invalid' | 'ineligible'
  const [introducerName, setIntroducerName] = useState('');
  const [introducerMessage, setIntroducerMessage] = useState('');
  const introducerCheckTimer = useRef(null);

  const [isSubmitting, setIsSubmitting] = useState(false);
  const [formError, setFormError] = useState('');

  const isPhoneFormatValid = useCallback((num, code = '+91') => {
    const rawDigits = (num || '').replace(/\D/g, '');
    if (!rawDigits) return false;
    if (code === '+91') {
      const national = rawDigits.startsWith('91') && rawDigits.length === 12 ? rawDigits.slice(2) : rawDigits;
      return national.length === 10;
    }
    return rawDigits.length >= 7 && rawDigits.length <= 15;
  }, []);

  const checkPhoneAvailability = useCallback(async (num, code = '+91') => {
    const rawDigits = (num || '').replace(/\D/g, '');
    if (!rawDigits) {
      setPhoneStatus('idle');
      setPhoneMessage('');
      return;
    }

    if (code === '+91') {
      const national = rawDigits.startsWith('91') && rawDigits.length === 12 ? rawDigits.slice(2) : rawDigits;
      if (national.length !== 10) {
        setPhoneStatus('invalid');
        setPhoneMessage('Please enter a valid 10-digit mobile number.');
        return;
      }
    } else if (rawDigits.length < 7 || rawDigits.length > 15) {
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
        setPhoneError('');
      } else {
        setPhoneStatus('taken');
        setPhoneMessage(res.message || 'This number already exists. Please use the existing login option.');
      }
    } catch (err) {
      if (requestId !== latestPhoneRequestId.current) {
        return;
      }
      const serverMessage = err.response?.data?.message || err.response?.data?.errors?.phone?.[0];
      setPhoneStatus('invalid');
      setPhoneMessage(serverMessage || 'Could not verify phone number.');
    }
  }, []);

  const handlePhoneChange = (e) => {
    const val = e.target.value;
    setPhone(val);
    setPhoneError('');
    setFormError('');

    if (phoneCheckTimer.current) {
      clearTimeout(phoneCheckTimer.current);
    }

    const cleanDigits = val.replace(/\D/g, '');
    if (!cleanDigits) {
      setPhoneStatus('idle');
      setPhoneMessage('');
      return;
    }

    if (countryCode === '+91') {
      const national = cleanDigits.startsWith('91') && cleanDigits.length === 12 ? cleanDigits.slice(2) : cleanDigits;
      if (national.length < 10) {
        setPhoneStatus('idle');
        setPhoneMessage('Enter 10 digits for your WhatsApp/mobile number.');
        return;
      }
    } else if (cleanDigits.length < 7) {
      setPhoneStatus('idle');
      setPhoneMessage('Enter at least 7 digits for your phone number.');
      return;
    }

    phoneCheckTimer.current = setTimeout(() => {
      checkPhoneAvailability(cleanDigits, countryCode);
    }, 300);
  };

  const handleCountryCodeChange = (e) => {
    const code = e.target.value;
    setCountryCode(code);
    setPhoneError('');
    if (phone) {
      const cleanDigits = phone.replace(/\D/g, '');
      if (cleanDigits.length >= 7) {
        checkPhoneAvailability(cleanDigits, code);
      }
    }
  };

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
        setIntroducerId(res.user_id);
      } else if (res.exists) {
        setIntroducerStatus('ineligible');
        setIntroducerName(res.name || '');
        setIntroducerMessage(res.message || 'The selected Introducer is not eligible to refer members until their mobile number is verified.');
        setIntroducerId(res.user_id);
      } else {
        setIntroducerStatus('invalid');
        setIntroducerName('');
        setIntroducerMessage(res.message || 'The selected Introducer ID does not exist.');
        setIntroducerId(val);
      }
    } catch {
      setIntroducerStatus('idle');
      setIntroducerMessage('');
    }
  }, []);

  // Fetch pending google user profile on mount
  useEffect(() => {
    if (!token) {
      setIsLoadingPending(false);
      setLoadError('Invalid or missing signup session. Please start Google sign-in again.');
      return;
    }

    let isMounted = true;
    (async () => {
      try {
        const res = await authApi.getPendingGoogleSignup(token);
        if (!isMounted) return;
        setPendingUser(res);
        setIsLoadingPending(false);

        if (res.phone) {
          setPhone(res.phone);
          checkPhoneAvailability(res.phone, '+91');
        }

        const activeRef = res.ref || initialRef;
        if (activeRef) {
          setIntroducerId(activeRef);
          setManualIntroducerVal(activeRef);
          resolveIntroducer(activeRef);
        }
      } catch (err) {
        if (!isMounted) return;
        setIsLoadingPending(false);
        setLoadError(
          err.response?.data?.message ||
          err.response?.data?.error ||
          'Your Google signup session has expired or is invalid. Please sign in with Google again.'
        );
      }
    })();

    return () => {
      isMounted = false;
    };
  }, [token, initialRef, resolveIntroducer, checkPhoneAvailability]);

  const handleIntroducerChange = (e) => {
    const rawVal = e.target.value;
    const cleanVal = rawVal.toLowerCase().replace(/[^a-z0-9_]/g, '');
    setManualIntroducerVal(cleanVal);
    setIntroducerId(cleanVal);
    setFormError('');

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

  const isPhoneValid = isPhoneFormatValid(phone, countryCode) && phoneStatus === 'available' && !phoneError;
  const isNextDisabled = isSubmitting || !isPhoneValid || phoneStatus === 'checking' || introducerStatus === 'checking';

  const handleNext = async (e) => {
    e.preventDefault();
    setFormError('');
    setPhoneError('');

    if (!phone.trim()) {
      setPhoneError('Phone Number / WhatsApp Number is required.');
      return;
    }

    if (!isPhoneFormatValid(phone, countryCode)) {
      setPhoneError('Please enter a valid WhatsApp/mobile number.');
      return;
    }

    if (phoneStatus === 'taken') {
      setPhoneError('This number already exists. Please use the existing login option.');
      return;
    }

    if (introducerId && introducerStatus === 'invalid') {
      setFormError('Please enter a valid Introducer User ID or leave it blank.');
      return;
    }

    if (introducerId && introducerStatus === 'ineligible') {
      setFormError('The selected Introducer is not eligible to refer members until their mobile is verified.');
      return;
    }

    setIsSubmitting(true);
    try {
      const payload = {
        token,
        phone: phone.trim(),
        country_code: countryCode,
        introducer_id: introducerId.trim() || null,
      };
      const res = await authApi.completeGoogleSignup(payload);
      navigate(res.redirect || '/member/login?success=account_created', {
        replace: true,
        state: { success: 'Your account has been created successfully. You can now log in.' },
      });
    } catch (err) {
      const phoneErr = err.response?.data?.errors?.phone?.[0];
      if (phoneErr) {
        setPhoneError(phoneErr);
        if (phoneErr.includes('already exists')) {
          setPhoneStatus('taken');
        }
      }
      const introducerErr = err.response?.data?.errors?.introducer_id?.[0];
      const msg =
        err.response?.data?.message ||
        err.response?.data?.error ||
        introducerErr ||
        phoneErr ||
        'Could not complete registration. Please try again.';
      setFormError(msg);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <main className="member-auth-page">
      <section className="member-auth-shell" aria-labelledby="google-introducer-title">
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
                <span>Welcome to MLM Book</span>
              </div>

              <h1 className="member-auth-heading">
                Almost there!
                <span className="member-auth-gradient-text">Complete your<br />account setup</span>
              </h1>
              <p className="member-auth-description">
                Connect with the community member who invited you, or get started directly.
              </p>
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

          <div className="member-auth-form-wrap" style={{ maxWidth: '440px' }}>
            <header className="member-auth-form-heading">
              <h2 className="member-auth-title" id="google-introducer-title">Complete Your Account</h2>
              <p className="member-auth-subtitle">
                {pendingUser ? `Welcome, ${pendingUser.name}! Who introduced you to MLM Book?` : 'Who introduced you to MLM Book?'}
              </p>
            </header>

            {isLoadingPending ? (
              <div style={{ textAlign: 'center', padding: '36px 0', color: 'var(--color-text-secondary, #64748b)' }}>
                <div className="member-auth-loading-spinner" style={{ margin: '0 auto 12px' }} />
                <span>Preparing your Google signup…</span>
              </div>
            ) : loadError ? (
              <div style={{ textAlign: 'center', padding: '20px 0' }}>
                <div className="member-auth-error" role="alert" style={{ marginBottom: '20px', padding: '14px 18px', borderRadius: '10px', background: '#fef2f2', border: '1px solid #fee2e2', color: '#b91c1c', fontSize: '14px' }}>
                  <span>{loadError}</span>
                </div>
                <Link to="/member/login" className="member-button member-button--primary" style={{ display: 'inline-block', textDecoration: 'none', padding: '10px 24px' }}>
                  Back to Login
                </Link>
              </div>
            ) : (
              <form className="member-auth-form" onSubmit={handleNext} noValidate>
                {formError && (
                  <div className="member-auth-error" role="alert" style={{ marginBottom: '16px', padding: '12px 16px', borderRadius: '10px', background: '#fef2f2', border: '1px solid #fee2e2', color: '#b91c1c', fontSize: '14px' }}>
                    <span>{formError}</span>
                  </div>
                )}

                {/* Change 1 & 2: Required Phone / WhatsApp Number Field ABOVE Introducer ID */}
                <div className="member-auth-field" data-member-phone-field>
                  <label className="member-auth-label" htmlFor="googlePhone">
                    Phone Number / WhatsApp Number <span style={{ color: '#dc2626' }}>*</span>
                  </label>
                  <div
                    className={`member-auth-input-wrap ${
                      phoneError || phoneStatus === 'taken' || phoneStatus === 'invalid'
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
                      id="googleCountryCode"
                      name="country_code"
                      value={countryCode}
                      onChange={handleCountryCodeChange}
                      aria-label="Country Code"
                      disabled={isSubmitting}
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
                      id="googlePhone"
                      name="phone"
                      type="tel"
                      value={phone}
                      onChange={handlePhoneChange}
                      placeholder="Enter your WhatsApp number"
                      autoComplete="tel"
                      required
                      disabled={isSubmitting}
                      aria-invalid={phoneError || phoneStatus === 'taken' || phoneStatus === 'invalid' ? 'true' : 'false'}
                      aria-describedby="google-phone-feedback"
                      style={{ border: 'none', paddingLeft: '0', flex: 1 }}
                    />
                  </div>

                  {phoneError || phoneStatus === 'taken' ? (
                    <div className="member-auth-error" id="google-phone-feedback" role="alert" style={{ display: 'flex', flexDirection: 'column', gap: '4px', marginTop: '6px' }}>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                        <svg viewBox="0 0 24 24" aria-hidden="true" style={{ width: '16px', height: '16px', flexShrink: 0 }}>
                          <circle cx="12" cy="12" r="9" /><path d="M12 8v5" /><path d="M12 17h.01" />
                        </svg>
                        <span>{phoneError || phoneMessage}</span>
                      </div>
                      {(phoneStatus === 'taken' || phoneError?.includes('already exists')) && (
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
                      id="google-phone-feedback"
                      className="member-user-id-feedback is-valid"
                      style={{ color: '#059669', display: 'flex', alignItems: 'center', gap: '6px', fontSize: '13px', marginTop: '6px' }}
                    >
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#059669" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                        <polyline points="20 6 9 17 4 12" />
                      </svg>
                      <span>Phone number is available.</span>
                    </div>
                  ) : phoneStatus === 'checking' ? (
                    <span className="member-register-confirm-help" id="google-phone-feedback" style={{ display: 'block', marginTop: '6px' }}>
                      Checking phone number availability…
                    </span>
                  ) : phoneStatus === 'invalid' ? (
                    <p className="member-auth-error" id="google-phone-feedback" role="alert" style={{ marginTop: '6px' }}>
                      <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 8v5" /><path d="M12 17h.01" /></svg>
                      <span>{phoneMessage}</span>
                    </p>
                  ) : (
                    <span className="member-register-confirm-help" id="google-phone-feedback" style={{ display: 'block', marginTop: '6px' }}>
                      WhatsApp / mobile number is required to complete your account.
                    </span>
                  )}
                </div>

                {/* Change 6: Introducer ID Field (Optional, BELOW Phone Field) */}
                <div className="member-auth-field" data-member-introducer-id-field style={{ marginTop: '18px' }}>
                  <label className="member-auth-label" htmlFor="googleIntroducerId">
                    Introducer ID <span style={{ fontWeight: 'normal', color: 'var(--color-text-secondary, #64748b)' }}>(Optional)</span>
                  </label>

                  <div
                    className={`member-auth-input-wrap ${
                      introducerStatus === 'invalid' || introducerStatus === 'ineligible'
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
                      id="googleIntroducerId"
                      name="introducer_id"
                      type="text"
                      value={
                        isEditingIntroducer
                          ? manualIntroducerVal
                          : introducerName
                          ? introducerName
                          : introducerId
                      }
                      onChange={handleIntroducerChange}
                      onFocus={() => {
                        if (!isEditingIntroducer && introducerName) {
                          setIsEditingIntroducer(true);
                          setManualIntroducerVal(introducerId);
                        }
                      }}
                      placeholder="Referrer's User ID (if referred)"
                      autoComplete="off"
                      maxLength={30}
                      spellCheck="false"
                      autoCapitalize="none"
                      disabled={isSubmitting}
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
                          setManualIntroducerVal(introducerId);
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

                  {introducerStatus === 'valid' && introducerName ? (
                    <div
                      className="member-user-id-feedback is-valid"
                      style={{ color: '#059669', display: 'flex', alignItems: 'center', gap: '6px', fontSize: '13px', marginTop: '6px' }}
                    >
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#059669" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                        <polyline points="20 6 9 17 4 12" />
                      </svg>
                      <span>Referred by <strong>{introducerName}</strong> (@{introducerId})</span>
                    </div>
                  ) : introducerStatus === 'ineligible' ? (
                    <p className="member-auth-error" role="alert" style={{ marginTop: '6px' }}>
                      <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 8v5" /><path d="M12 17h.01" /></svg>
                      <span>{introducerMessage || 'The selected Introducer is not eligible to refer members until their mobile number is verified.'}</span>
                    </p>
                  ) : introducerStatus === 'invalid' ? (
                    <p className="member-auth-error" role="alert" style={{ marginTop: '6px' }}>
                      <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 8v5" /><path d="M12 17h.01" /></svg>
                      <span>{introducerMessage || 'The selected Introducer ID does not exist.'}</span>
                    </p>
                  ) : introducerStatus === 'checking' ? (
                    <span className="member-register-confirm-help" style={{ display: 'block', marginTop: '6px' }}>
                      Verifying Introducer details…
                    </span>
                  ) : (
                    <span className="member-register-confirm-help" style={{ display: 'block', marginTop: '6px' }}>
                      Leave blank if you do not have an introducer.
                    </span>
                  )}
                </div>

                {/* Change 3, 4, 5: Next Button ONLY (Skip removed), disabled until valid phone is entered */}
                <div style={{ marginTop: '24px' }}>
                  <button
                    className="member-auth-submit"
                    type="submit"
                    disabled={isNextDisabled}
                    style={{
                      width: '100%',
                      margin: 0,
                      opacity: isNextDisabled ? 0.6 : 1,
                      cursor: isNextDisabled ? 'not-allowed' : 'pointer',
                    }}
                  >
                    <span>{isSubmitting ? 'Completing…' : 'Next'}</span>
                  </button>
                </div>
              </form>
            )}

            <p className="member-auth-security" style={{ marginTop: '24px' }}>
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3v8Z" /><path d="m9 12 2 2 4-4" /></svg>
              <span>Your data is protected and secure with us.</span>
            </p>
          </div>
        </div>
      </section>
    </main>
  );
}

export default GoogleIntroducerPage;
