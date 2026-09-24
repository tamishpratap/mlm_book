import { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import {
  MailCheck,
  Send,
  BadgeCheck,
  Shield,
  UserRound,
} from 'lucide-react';
import accountApi from '../../api/accountApi';
import useAuth from '../../hooks/useAuth';
import MobileVerificationCard from '../../components/verification/MobileVerificationCard';
import IntroducerSettingsCard from '../../components/account/IntroducerSettingsCard';

export function AccountSettingsPage() {
  const { user, setUser } = useAuth();

  const [settingsData, setSettingsData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  // Email verification state
  const [newEmail, setNewEmail] = useState('');
  const [emailOtp, setEmailOtp] = useState('');
  const [emailOtpPending, setEmailOtpPending] = useState(null);
  const [emailCooldown, setEmailCooldown] = useState(0);
  const [isSendingEmailOtp, setIsSendingEmailOtp] = useState(false);
  const [isVerifyingEmailOtp, setIsVerifyingEmailOtp] = useState(false);
  const [emailSuccessMsg, setEmailSuccessMsg] = useState(null);
  const [emailErrorMsg, setEmailErrorMsg] = useState(null);

  const fetchSettings = useCallback(() => {
    return accountApi.getSettings();
  }, []);

  useEffect(() => {
    let isMounted = true;

    fetchSettings()
      .then((res) => {
        if (isMounted && res && res.success) {
          setSettingsData(res);
          setEmailOtpPending(res.email_otp_pending || null);
          setEmailCooldown(res.email_cooldown || 0);
          if (res.member) {
            setUser(res.member);
          }
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load account settings.');
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [fetchSettings, setUser]);

  // Email Cooldown countdown timer
  useEffect(() => {
    if (emailCooldown <= 0) return;
    const timer = setInterval(() => {
      setEmailCooldown((prev) => (prev > 1 ? prev - 1 : 0));
    }, 1000);
    return () => clearInterval(timer);
  }, [emailCooldown]);

  // Send Email OTP
  const handleSendEmailOtp = async (e) => {
    e.preventDefault();
    if (isSendingEmailOtp || emailCooldown > 0 || !newEmail.trim()) return;

    setIsSendingEmailOtp(true);
    setEmailSuccessMsg(null);
    setEmailErrorMsg(null);

    try {
      const res = await accountApi.sendEmailOtp({ new_email: newEmail.trim() });
      if (res && res.success) {
        setEmailSuccessMsg(res.message || 'Verification code sent to your new email.');
        setEmailCooldown(60);
        fetchSettings().then((d) => d?.success && setEmailOtpPending(d.email_otp_pending));
      } else {
        setEmailErrorMsg(res?.message || 'Failed to send email verification code.');
      }
    } catch (err) {
      setEmailErrorMsg(err.response?.data?.message || 'Failed to send email verification code.');
    } finally {
      setIsSendingEmailOtp(false);
    }
  };

  // Verify Email OTP
  const handleVerifyEmailOtp = async (e) => {
    e.preventDefault();
    if (isVerifyingEmailOtp || !emailOtp.trim()) return;

    setIsVerifyingEmailOtp(true);
    setEmailSuccessMsg(null);
    setEmailErrorMsg(null);

    try {
      const res = await accountApi.verifyEmailOtp({ email_otp: emailOtp.trim() });
      if (res && res.success) {
        setEmailSuccessMsg('Email address updated successfully.');
        setEmailOtp('');
        setNewEmail('');
        setEmailOtpPending(null);
        if (res.member) setUser(res.member);
        fetchSettings().then((d) => d?.success && setSettingsData(d));
      }
    } catch (err) {
      setEmailErrorMsg(err.response?.data?.message || 'Invalid or expired verification code.');
    } finally {
      setIsVerifyingEmailOtp(false);
    }
  };

  if (isLoading) {
    return (
      <div style={{ textAlign: 'center', padding: '60px', color: 'var(--color-text-secondary)' }}>
        Loading account settings...
      </div>
    );
  }

  const member = settingsData?.member || user || {};

  return (
    <div className="account-page-wrapper" style={{ maxWidth: '920px', margin: '20px auto', padding: '0 16px', boxSizing: 'border-box', width: '100%' }}>
      <header className="member-page-heading">
        <div className="member-page-heading__content">
          <h1 style={{ fontSize: '24px', fontWeight: 700, margin: '0 0 4px 0' }}>Account Settings</h1>
          <p style={{ margin: 0, color: 'var(--color-text-secondary)', fontSize: '14px' }}>
            Securely manage your verified mobile number and sign-in email.
          </p>
        </div>
        <div className="member-page-heading__actions">
          <Link className="member-button member-button--secondary" to="/member/account/security">
            <Shield size={16} aria-hidden="true" />
            <span>Security</span>
          </Link>
          <Link className="member-button member-button--secondary" to="/member/profile">
            <UserRound size={16} aria-hidden="true" />
            <span>Profile</span>
          </Link>
        </div>
      </header>

      {error && (
        <div style={{ padding: '12px 18px', background: '#fee2e2', color: '#b91c1c', borderRadius: '12px', marginBottom: '20px', fontSize: '14px' }}>
          {error}
        </div>
      )}

      <div className="account-settings-grid" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 360px), 1fr))', gap: '24px', width: '100%', minWidth: 0, boxSizing: 'border-box' }}>
        {/* MOBILE VERIFICATION CARD */}
        <MobileVerificationCard
          member={member}
          onVerified={() => fetchSettings().then((d) => d?.success && setSettingsData(d))}
        />

        {/* INTRODUCER / REFERRAL CARD */}
        <IntroducerSettingsCard
          member={member}
          introducer={settingsData?.introducer}
          onIntroducerAdded={() => fetchSettings().then((d) => d?.success && setSettingsData(d))}
        />

        {/* EMAIL CHANGE & VERIFICATION CARD */}
        <section className="member-card verification-card account-email-card" style={{ background: '#fff', borderRadius: '16px', border: '1px solid #e5e7eb', padding: '24px', width: '100%', maxWidth: '100%', minWidth: 0, boxSizing: 'border-box', overflow: 'hidden' }}>
          <header style={{ display: 'flex', alignItems: 'center', marginBottom: '16px', paddingBottom: '12px', borderBottom: '1px solid #f0f0f0' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
              <div style={{ width: '40px', height: '40px', borderRadius: '10px', background: '#f5f3ff', color: '#8b5cf6', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                <MailCheck size={20} />
              </div>
              <div>
                <h2 style={{ fontSize: '16px', fontWeight: 700, color: '#111827', margin: 0 }}>Email Address</h2>
              </div>
            </div>
          </header>

          <div style={{ background: '#f9fafb', padding: '12px 14px', borderRadius: '10px', marginBottom: '16px', width: '100%', minWidth: 0, boxSizing: 'border-box' }}>
            <span style={{ display: 'block', fontSize: '11px', color: '#9ca3af', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Current Email</span>
            <strong style={{ fontSize: '14px', color: '#111827', display: 'block', overflowWrap: 'anywhere', wordBreak: 'break-all' }}>{member.email}</strong>
          </div>

          {emailSuccessMsg && (
            <div style={{ padding: '10px 14px', background: '#dcfce7', color: '#15803d', borderRadius: '8px', fontSize: '13px', marginBottom: '14px' }}>
              {emailSuccessMsg}
            </div>
          )}

          {emailErrorMsg && (
            <div style={{ padding: '10px 14px', background: '#fee2e2', color: '#b91c1c', borderRadius: '8px', fontSize: '13px', marginBottom: '14px' }}>
              {emailErrorMsg}
            </div>
          )}

          {/* Send Email OTP Form */}
          <form onSubmit={handleSendEmailOtp} style={{ display: 'flex', flexDirection: 'column', gap: '14px', width: '100%', minWidth: 0 }}>
            <div>
              <label style={{ display: 'block', fontSize: '13px', fontWeight: 600, color: '#374151', marginBottom: '6px' }}>
                New email address
              </label>
              <input
                type="email"
                value={newEmail}
                onChange={(e) => setNewEmail(e.target.value)}
                maxLength={255}
                placeholder="new.email@example.com"
                required
                className="form-control"
                style={{ width: '100%', maxWidth: '100%', minWidth: 0, padding: '10px 14px', borderRadius: '10px', border: '1px solid #d1d5db', fontSize: '14px', boxSizing: 'border-box' }}
              />
            </div>

            <div style={{ display: 'flex', justifyContent: 'flex-end', width: '100%' }}>
              <button
                type="submit"
                className="member-button member-button--primary"
                disabled={isSendingEmailOtp || emailCooldown > 0}
                style={{ fontSize: '13px', minHeight: '40px', display: 'inline-flex', alignItems: 'center', gap: '6px' }}
              >
                <Send size={14} />
                <span>
                  {emailCooldown > 0
                    ? `Resend in ${emailCooldown}s`
                    : emailOtpPending
                    ? 'Resend Code'
                    : 'Send Code'}
                </span>
              </button>
            </div>
          </form>

          {/* Verify Email OTP Panel */}
          {emailOtpPending && (
            <div style={{ marginTop: '20px', paddingTop: '16px', borderTop: '1px solid #f0f0f0' }}>
              <div style={{ marginBottom: '10px' }}>
                <strong style={{ fontSize: '13px', color: '#111827' }}>Enter the Email Code</strong>
                <span style={{ display: 'block', fontSize: '12px', color: '#6b7280' }}>
                  Code sent to {emailOtpPending?.destination || emailOtpPending?.pending_value || newEmail || 'your new email address'}.
                </span>
              </div>
              <form onSubmit={handleVerifyEmailOtp} style={{ display: 'flex', gap: '8px', flexWrap: 'wrap', width: '100%', minWidth: 0 }}>
                <input
                  type="text"
                  value={emailOtp}
                  onChange={(e) => setEmailOtp(e.target.value)}
                  maxLength={6}
                  pattern="[0-9]{6}"
                  placeholder="000000"
                  required
                  style={{
                    flex: '1 1 140px',
                    minWidth: 0,
                    maxWidth: '100%',
                    boxSizing: 'border-box',
                    padding: '10px 14px',
                    borderRadius: '10px',
                    border: '1px solid #d1d5db',
                    fontSize: '15px',
                    letterSpacing: '3px',
                    textAlign: 'center',
                    fontWeight: 700,
                  }}
                />
                <button
                  type="submit"
                  className="member-button member-button--primary"
                  disabled={isVerifyingEmailOtp || emailOtp.length < 6}
                  style={{ flexShrink: 0, minHeight: '42px' }}
                >
                  <BadgeCheck size={16} />
                  <span>Verify</span>
                </button>
              </form>
            </div>
          )}
        </section>
      </div>
    </div>
  );
}

export default AccountSettingsPage;
