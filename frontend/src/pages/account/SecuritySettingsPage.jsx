import { useState } from 'react';
import { Link } from 'react-router-dom';
import {
  ShieldCheck,
  Eye,
  EyeOff,
  Settings,
  Info,
  CheckCircle2,
} from 'lucide-react';
import accountApi from '../../api/accountApi';
import useAuth from '../../hooks/useAuth';

export function SecuritySettingsPage() {
  const { user } = useAuth();

  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');

  const [showCurrentPass, setShowCurrentPass] = useState(false);
  const [showNewPass, setShowNewPass] = useState(false);
  const [showConfirmPass, setShowConfirmPass] = useState(false);

  const [isUpdating, setIsUpdating] = useState(false);
  const [successMessage, setSuccessMessage] = useState(null);
  const [generalError, setGeneralError] = useState(null);
  const [fieldErrors, setFieldErrors] = useState({});

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (isUpdating) return;

    setIsUpdating(true);
    setSuccessMessage(null);
    setGeneralError(null);
    setFieldErrors({});

    try {
      const res = await accountApi.updatePassword({
        current_password: currentPassword,
        password: newPassword,
        password_confirmation: passwordConfirmation,
      });

      if (res && res.success) {
        setSuccessMessage('Your password has been changed successfully.');
        setCurrentPassword('');
        setNewPassword('');
        setPasswordConfirmation('');
      }
    } catch (err) {
      if (err.response?.status === 422 && err.response?.data?.errors) {
        setFieldErrors(err.response.data.errors);
      } else {
        setGeneralError(err.response?.data?.message || 'Failed to update password.');
      }
    } finally {
      setIsUpdating(false);
    }
  };

  const member = user || {};

  return (
    <div className="account-page-wrapper settings-shell" style={{ maxWidth: '680px', margin: '20px auto', padding: '0 16px' }}>
      <header className="member-page-heading">
        <div className="member-page-heading__content">
          <h1 style={{ fontSize: '24px', fontWeight: 700, margin: '0 0 4px 0' }}>Password &amp; Security</h1>
          <p style={{ margin: 0, color: 'var(--color-text-secondary)', fontSize: '14px' }}>
            Use a strong, unique password to protect your account.
          </p>
        </div>
        <div className="member-page-heading__actions">
          <Link className="member-button member-button--secondary" to="/member/account/settings">
            <Settings size={16} aria-hidden="true" />
            <span>Account Settings</span>
          </Link>
        </div>
      </header>

      {successMessage && (
        <div style={{ padding: '14px 18px', background: '#dcfce7', color: '#15803d', borderRadius: '12px', marginBottom: '20px', fontWeight: 600, fontSize: '14px', display: 'flex', alignItems: 'center', gap: '8px' }}>
          <CheckCircle2 size={18} />
          <span>{successMessage}</span>
        </div>
      )}

      {generalError && (
        <div style={{ padding: '14px 18px', background: '#fee2e2', color: '#b91c1c', borderRadius: '12px', marginBottom: '20px', fontSize: '14px' }}>
          {generalError}
        </div>
      )}

      {/* WhatsApp Mobile Verification Card */}
      <section className="member-card settings-card security-verification-card" style={{ background: '#fff', borderRadius: '16px', border: '1px solid #e5e7eb', marginBottom: '20px' }}>
        <div className="security-verification-card__row">
          <div className="security-verification-card__info">
            <div
              style={{
                width: '46px',
                height: '46px',
                borderRadius: '14px',
                background: member.is_verified || member.mobile_verified_at ? '#f0fdf4' : '#fffbeb',
                color: member.is_verified || member.mobile_verified_at ? '#16a34a' : '#d97706',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                flexShrink: 0,
              }}
            >
              <ShieldCheck size={24} />
            </div>
            <div>
              <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '4px' }}>
                <h2 style={{ fontSize: '17px', fontWeight: 800, color: '#111827', margin: 0 }}>
                  WhatsApp Mobile Verification
                </h2>
                {member.is_verified || member.mobile_verified_at ? (
                  <span style={{ fontSize: '11.5px', fontWeight: 700, color: '#15803d', background: '#dcfce7', padding: '2px 8px', borderRadius: '10px' }}>
                    Verified
                  </span>
                ) : (
                  <span style={{ fontSize: '11.5px', fontWeight: 700, color: '#b45309', background: '#fef3c7', padding: '2px 8px', borderRadius: '10px' }}>
                    Unverified
                  </span>
                )}
              </div>
              <p style={{ margin: '0 0 6px 0', color: '#6b7280', fontSize: '13px', lineHeight: 1.4 }}>
                {member.is_verified || member.mobile_verified_at
                  ? `Your mobile number (${member.phone || 'Verified'}) is confirmed on WhatsApp. Your account displays the official Green Verified Tick.`
                  : 'Verify your mobile number via WhatsApp OTP to unlock your Green Verified Tick badge and boost member trust.'}
              </p>
            </div>
          </div>

          <div className="security-verification-card__action">
            <Link
              to="/member/account/verify"
              className={`member-button ${member.is_verified || member.mobile_verified_at ? 'member-button--secondary' : 'member-button--primary'}`}
              style={{
                padding: '10px 18px',
                fontSize: '13.5px',
                fontWeight: 700,
                background: member.is_verified || member.mobile_verified_at ? undefined : 'linear-gradient(135deg, #059669 0%, #10b981 100%)',
                border: member.is_verified || member.mobile_verified_at ? undefined : 'none',
                color: '#ffffff',
              }}
            >
              <ShieldCheck size={16} />
              <span>{member.is_verified || member.mobile_verified_at ? 'Manage Verification' : 'Verify via WhatsApp'}</span>
            </Link>
          </div>
        </div>
      </section>

      <section className="member-card settings-card" style={{ background: '#fff', borderRadius: '16px', border: '1px solid #e5e7eb' }}>
        <header style={{ marginBottom: '20px', paddingBottom: '14px', borderBottom: '1px solid #f0f0f0' }}>
          <h2 style={{ fontSize: '18px', fontWeight: 700, color: '#111827', margin: '0 0 4px 0' }}>Change password</h2>
          <p style={{ margin: 0, color: '#6b7280', fontSize: '13px' }}>Confirm your current password before choosing a new one.</p>
        </header>

        {member.google_id && (
          <div
            style={{
              padding: '12px 16px',
              background: '#eff6ff',
              color: '#1e40af',
              borderRadius: '10px',
              fontSize: '13px',
              display: 'flex',
              alignItems: 'center',
              gap: '10px',
              marginBottom: '20px',
            }}
          >
            <Info size={18} color="#3b82f6" style={{ flexShrink: 0 }} />
            <span>This account is connected to Google. Changing the password still requires the current account password.</span>
          </div>
        )}

        <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: '18px' }}>
          {/* Current Password */}
          <div>
            <label style={{ display: 'block', fontSize: '13px', fontWeight: 600, color: '#374151', marginBottom: '6px' }}>
              Current Password <span style={{ color: '#ef4444' }}>*</span>
            </label>
            <div style={{ position: 'relative' }}>
              <input
                type={showCurrentPass ? 'text' : 'password'}
                value={currentPassword}
                onChange={(e) => setCurrentPassword(e.target.value)}
                required
                className="form-control"
                style={{ width: '100%', padding: '10px 42px 10px 14px', borderRadius: '10px', border: '1px solid #d1d5db', fontSize: '14px' }}
              />
              <button
                type="button"
                onClick={() => setShowCurrentPass(!showCurrentPass)}
                style={{ position: 'absolute', right: '12px', top: '50%', transform: 'translateY(-50%)', background: 'transparent', border: 'none', cursor: 'pointer', color: '#9ca3af' }}
              >
                {showCurrentPass ? <EyeOff size={16} /> : <Eye size={16} />}
              </button>
            </div>
            {fieldErrors.current_password && (
              <p style={{ color: '#dc2626', fontSize: '12px', marginTop: '4px', margin: '4px 0 0 0' }}>{fieldErrors.current_password[0]}</p>
            )}
          </div>

          {/* New Password */}
          <div>
            <label style={{ display: 'block', fontSize: '13px', fontWeight: 600, color: '#374151', marginBottom: '6px' }}>
              New Password <span style={{ color: '#ef4444' }}>*</span>
            </label>
            <div style={{ position: 'relative' }}>
              <input
                type={showNewPass ? 'text' : 'password'}
                value={newPassword}
                onChange={(e) => setNewPassword(e.target.value)}
                minLength={8}
                required
                className="form-control"
                style={{ width: '100%', padding: '10px 42px 10px 14px', borderRadius: '10px', border: '1px solid #d1d5db', fontSize: '14px' }}
              />
              <button
                type="button"
                onClick={() => setShowNewPass(!showNewPass)}
                style={{ position: 'absolute', right: '12px', top: '50%', transform: 'translateY(-50%)', background: 'transparent', border: 'none', cursor: 'pointer', color: '#9ca3af' }}
              >
                {showNewPass ? <EyeOff size={16} /> : <Eye size={16} />}
              </button>
            </div>
            <p style={{ margin: '4px 0 0 0', fontSize: '12px', color: '#9ca3af' }}>
              Use at least 8 characters and choose a password different from your current one.
            </p>
            {fieldErrors.password && (
              <p style={{ color: '#dc2626', fontSize: '12px', marginTop: '4px', margin: '4px 0 0 0' }}>{fieldErrors.password[0]}</p>
            )}
          </div>

          {/* Confirm New Password */}
          <div>
            <label style={{ display: 'block', fontSize: '13px', fontWeight: 600, color: '#374151', marginBottom: '6px' }}>
              Confirm New Password <span style={{ color: '#ef4444' }}>*</span>
            </label>
            <div style={{ position: 'relative' }}>
              <input
                type={showConfirmPass ? 'text' : 'password'}
                value={passwordConfirmation}
                onChange={(e) => setPasswordConfirmation(e.target.value)}
                minLength={8}
                required
                className="form-control"
                style={{ width: '100%', padding: '10px 42px 10px 14px', borderRadius: '10px', border: '1px solid #d1d5db', fontSize: '14px' }}
              />
              <button
                type="button"
                onClick={() => setShowConfirmPass(!showConfirmPass)}
                style={{ position: 'absolute', right: '12px', top: '50%', transform: 'translateY(-50%)', background: 'transparent', border: 'none', cursor: 'pointer', color: '#9ca3af' }}
              >
                {showConfirmPass ? <EyeOff size={16} /> : <Eye size={16} />}
              </button>
            </div>
          </div>

          <div className="security-form-actions" style={{ display: 'flex', justifyContent: 'flex-end', marginTop: '12px', paddingTop: '16px', borderTop: '1px solid #f0f0f0' }}>
            <button
              type="submit"
              className="member-button member-button--primary"
              disabled={isUpdating}
            >
              <ShieldCheck size={16} aria-hidden="true" />
              <span>{isUpdating ? 'Updating...' : 'Update Password'}</span>
            </button>
          </div>
        </form>
      </section>
    </div>
  );
}

export default SecuritySettingsPage;
