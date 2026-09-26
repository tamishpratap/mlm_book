import { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import {
  WalletCards,
  Check,
  Copy,
  Edit3,
  Send,
  ShieldCheck,
  AlertTriangle,
  Info,
  Shield,
  UserRound,
  RefreshCw,
  KeyRound,
  CheckCircle2,
  ExternalLink,
  Award,
  TrendingUp,
} from 'lucide-react';
import accountApi from '../../api/accountApi';
import useAuth from '../../hooks/useAuth';

export function Web3WalletPage() {
  const { user, setUser } = useAuth();

  const [walletData, setWalletData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  // Address editing & OTP state
  const [isEditingAddress, setIsEditingAddress] = useState(false);
  const [walletAddressInput, setWalletAddressInput] = useState('');
  const [otpInput, setOtpInput] = useState('');
  const [otpSent, setOtpSent] = useState(false);
  const [otpCooldown, setOtpCooldown] = useState(0);
  const [demoOtp, setDemoOtp] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [actionError, setActionError] = useState('');
  const [actionSuccess, setActionSuccess] = useState('');
  const [copiedAddress, setCopiedAddress] = useState(false);

  // OTP Cooldown Countdown
  useEffect(() => {
    let timer;
    if (otpCooldown > 0) {
      timer = setInterval(() => {
        setOtpCooldown((prev) => Math.max(0, prev - 1));
      }, 1000);
    }
    return () => clearInterval(timer);
  }, [otpCooldown]);

  const loadWalletDetails = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    try {
      const res = await accountApi.getRewardWallet();
      if (res && res.success) {
        setWalletData(res.wallet);
      }
    } catch (err) {
      console.error('Failed to load Web3 wallet details:', err);
      setError(err.response?.data?.message || 'Failed to load Web3 wallet details.');
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    loadWalletDetails();
  }, [loadWalletDetails]);

  const handleStartEdit = () => {
    setWalletAddressInput(walletData?.wallet_address || '');
    setIsEditingAddress(true);
    setOtpSent(false);
    setOtpInput('');
    setActionError('');
    setActionSuccess('');
  };

  const handleCancelEdit = () => {
    setIsEditingAddress(false);
    setOtpSent(false);
    setOtpInput('');
    setWalletAddressInput('');
    setActionError('');
    setActionSuccess('');
  };

  const handleSendOtp = async (e) => {
    e?.preventDefault();
    setActionError('');
    setActionSuccess('');

    const cleanAddress = walletAddressInput.trim();
    if (!cleanAddress) {
      setActionError('Please enter your USDT (BEP-20) wallet address.');
      return;
    }

    if (!/^0x[a-fA-F0-9]{40}$/.test(cleanAddress)) {
      setActionError('Please enter a valid USDT (BEP-20) BNB Smart Chain wallet address starting with 0x (42 characters).');
      return;
    }

    setIsSubmitting(true);
    try {
      const res = await accountApi.sendRewardWalletOtp({ wallet_address: cleanAddress });
      if (res.success) {
        setOtpSent(true);
        setOtpCooldown(60);
        setActionSuccess(res.message || 'Verification code sent to your registered email.');
        if (res.demo_otp) {
          setDemoOtp(res.demo_otp);
        }
      } else {
        setActionError(res.message || 'Failed to send verification code.');
      }
    } catch (err) {
      const msg = err.response?.data?.message || err.response?.data?.errors?.wallet_address?.[0] || 'Failed to send email verification code.';
      setActionError(msg);
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleVerifyOtp = async (e) => {
    e?.preventDefault();
    setActionError('');
    setActionSuccess('');

    const cleanOtp = otpInput.trim().replace(/\D/g, '');
    if (!cleanOtp || cleanOtp.length < 6) {
      setActionError('Please enter the 6-digit email verification code.');
      return;
    }

    setIsSubmitting(true);
    try {
      const res = await accountApi.verifyRewardWalletOtp({
        otp: cleanOtp,
        wallet_address: walletAddressInput.trim(),
      });

      if (res.success) {
        setActionSuccess(res.message || 'Web3 USDT (BEP-20) wallet address verified and activated successfully!');
        setIsEditingAddress(false);
        setOtpSent(false);
        setOtpInput('');
        setDemoOtp('');
        // Reload wallet details and update Auth context
        loadWalletDetails();
        accountApi.getSettings().then((d) => {
          if (d?.member) setUser(d.member);
        });
      } else {
        setActionError(res.message || 'Failed to verify OTP.');
      }
    } catch (err) {
      const msg = err.response?.data?.message || err.response?.data?.errors?.otp?.[0] || 'Invalid verification code.';
      setActionError(msg);
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleCopyAddress = (addr) => {
    if (!addr) return;
    navigator.clipboard.writeText(addr).then(() => {
      setCopiedAddress(true);
      setTimeout(() => setCopiedAddress(false), 2500);
    }).catch(() => {});
  };

  if (isLoading) {
    return (
      <div style={{ textAlign: 'center', padding: '60px', color: 'var(--color-text-secondary)' }}>
        Loading Web3 USDT Wallet details...
      </div>
    );
  }

  const isVerified = Boolean(user?.is_verified || user?.mobile_verified_at || walletData?.is_verified);
  const activeAddress = walletData?.wallet_address || user?.reward_wallet_address;
  const isAddressVerified = Boolean(walletData?.has_verified_wallet || (walletData?.wallet_status === 'verified' && activeAddress) || (user?.reward_wallet_address && user?.reward_wallet_verified_at));
  const userRegisteredEmail = walletData?.masked_email || walletData?.email || user?.email || '';

  return (
    <div style={{ maxWidth: '920px', margin: '20px auto', padding: '0 16px' }}>
      {/* Header */}
      <header className="member-page-heading" style={{ marginBottom: '24px', display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '12px' }}>
        <div>
          <h1 style={{ fontSize: '24px', fontWeight: 700, margin: '0 0 4px 0', display: 'flex', alignItems: 'center', gap: '10px' }}>
            <WalletCards size={26} color="#0284c7" />
            <span>Web3 USDT Wallet</span>
          </h1>
          <p style={{ margin: 0, color: 'var(--color-text-secondary)', fontSize: '14px' }}>
            Manage your verified USDT (BEP-20) wallet address.
          </p>
        </div>
        <div style={{ display: 'flex', gap: '8px' }}>
          <Link className="member-button member-button--secondary" to="/member/account/settings">
            <Shield size={16} aria-hidden="true" />
            <span>Account Settings</span>
          </Link>
          <Link className="member-button member-button--secondary" to="/member/profile">
            <UserRound size={16} aria-hidden="true" />
            <span>Profile</span>
          </Link>
        </div>
      </header>

      {/* Global Error Banner */}
      {error && (
        <div style={{ padding: '12px 18px', background: '#fee2e2', color: '#b91c1c', borderRadius: '12px', marginBottom: '20px', fontSize: '14px' }}>
          {error}
        </div>
      )}

      {/* Dynamic Member Rank & Reward Rate Overview Card */}
      <section
        className="member-card"
        style={{
          background: '#ffffff',
          borderRadius: '16px',
          border: '1px solid #e5e7eb',
          padding: '20px 24px',
          boxShadow: '0 1px 3px 0 rgba(0, 0, 0, 0.05)',
          marginBottom: '24px',
        }}
      >
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '16px' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '14px' }}>
            <div
              style={{
                width: '46px',
                height: '46px',
                borderRadius: '12px',
                backgroundColor: walletData?.current_rank && walletData.current_rank !== 'No Rank' ? '#f5f3ff' : '#f1f5f9',
                color: walletData?.current_rank && walletData.current_rank !== 'No Rank' ? '#7c3aed' : '#64748b',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
              }}
            >
              <Award size={24} />
            </div>
            <div>
              <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                <span style={{ fontSize: '11.5px', fontWeight: 700, color: '#64748b', textTransform: 'uppercase' }}>
                  Current Member Rank
                </span>
                <span
                  style={{
                    fontSize: '11px',
                    fontWeight: 700,
                    color: walletData?.current_rank && walletData.current_rank !== 'No Rank' ? '#7c3aed' : '#64748b',
                    background: walletData?.current_rank && walletData.current_rank !== 'No Rank' ? '#f5f3ff' : '#f1f5f9',
                    border: `1px solid ${walletData?.current_rank && walletData.current_rank !== 'No Rank' ? '#ddd6fe' : '#e2e8f0'}`,
                    padding: '2px 8px',
                    borderRadius: '8px',
                  }}
                >
                  {walletData?.current_rank || user?.current_rank || 'No Rank'}
                </span>
              </div>
              <h2 style={{ fontSize: '19px', fontWeight: 800, color: '#1e293b', margin: '4px 0 0 0' }}>
                {walletData?.current_rank && walletData.current_rank !== 'No Rank' ? walletData.current_rank : 'Unranked Member'}
              </h2>
            </div>
          </div>

          <div style={{ display: 'flex', alignItems: 'center', gap: '24px', flexWrap: 'wrap' }}>
            <div>
              <div style={{ fontSize: '11px', fontWeight: 700, color: '#64748b', textTransform: 'uppercase' }}>
                Verified Referrals
              </div>
              <div style={{ fontSize: '16px', fontWeight: 800, color: '#1e293b' }}>
                {walletData?.user_referrals ?? 0}
              </div>
            </div>
            <div style={{ width: '1px', height: '28px', backgroundColor: '#e2e8f0' }} />
            <div>
              <div style={{ fontSize: '11px', fontWeight: 700, color: '#64748b', textTransform: 'uppercase' }}>
                Verified Connections
              </div>
              <div style={{ fontSize: '16px', fontWeight: 800, color: '#1e293b' }}>
                {walletData?.verified_connections ?? walletData?.user_team ?? 0}
              </div>
            </div>
            <div style={{ width: '1px', height: '28px', backgroundColor: '#e2e8f0' }} />
            <div>
              <div style={{ fontSize: '11px', fontWeight: 700, color: '#64748b', textTransform: 'uppercase' }}>
                Campaign Reward Rate
              </div>
              <div style={{ fontSize: '16px', fontWeight: 800, color: '#047857' }}>
                {walletData?.rank_reward_amount_exact ? `$${walletData.rank_reward_amount_exact} USD` : (walletData?.reward_range_formatted || '$0.0250 – $0.1000 USD')}
              </div>
            </div>
          </div>
        </div>

        {/* Next Rank progress line */}
        {walletData?.next_rank && (
          <div
            style={{
              marginTop: '16px',
              paddingTop: '12px',
              borderTop: '1px solid #f1f5f9',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              flexWrap: 'wrap',
              gap: '8px',
              fontSize: '12.5px',
              color: '#6d28d9',
            }}
          >
            <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
              <TrendingUp size={16} color="#7c3aed" />
              <span>
                Next Rank: <strong>{walletData.next_rank.rank}</strong> (${walletData.next_rank.reward_amount_exact} USD/ad)
              </span>
            </div>
            <span style={{ color: '#64748b' }}>
              Need {walletData.next_rank.referrals_needed} more referral{walletData.next_rank.referrals_needed === 1 ? '' : 's'} &amp; {walletData.next_rank.connections_needed} more verified connection{walletData.next_rank.connections_needed === 1 ? '' : 's'}
            </span>
          </div>
        )}

        {!walletData?.next_rank && walletData?.current_rank && walletData.current_rank !== 'No Rank' && (
          <div
            style={{
              marginTop: '14px',
              paddingTop: '10px',
              borderTop: '1px solid #f1f5f9',
              display: 'flex',
              alignItems: 'center',
              gap: '6px',
              fontSize: '12px',
              color: '#86198f',
              fontWeight: 600,
            }}
          >
            <Award size={15} color="#c026d3" />
            <span>Highest Rank Achieved! You qualify for the maximum campaign reward rate.</span>
          </div>
        )}
      </section>

      {/* Main Web3 Wallet Card */}
      <section
        className="member-card"
        style={{
          background: '#ffffff',
          borderRadius: '16px',
          border: '1px solid #e5e7eb',
          padding: '24px',
          boxShadow: '0 1px 3px 0 rgba(0, 0, 0, 0.05)',
          marginBottom: '24px',
        }}
      >
        {/* Card Header */}
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', paddingBottom: '16px', borderBottom: '1px solid #f1f5f9', marginBottom: '20px', flexWrap: 'wrap', gap: '10px' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
            <div
              style={{
                width: '42px',
                height: '42px',
                borderRadius: '12px',
                backgroundColor: '#e0f2fe',
                color: '#0284c7',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
              }}
            >
              <KeyRound size={22} />
            </div>
            <div>
              <h2 style={{ fontSize: '17px', fontWeight: 800, color: '#1e293b', margin: 0 }}>
                USDT (BEP-20) Destination Address
              </h2>
              <p style={{ margin: 0, fontSize: '12.5px', color: '#64748b' }}>
                BNB Smart Chain Network
              </p>
            </div>
          </div>

          <div>
            {isAddressVerified ? (
              <span
                style={{
                  fontSize: '12px',
                  fontWeight: 700,
                  color: '#047857',
                  backgroundColor: '#ecfdf5',
                  border: '1px solid #a7f3d0',
                  padding: '4px 12px',
                  borderRadius: '10px',
                  display: 'inline-flex',
                  alignItems: 'center',
                  gap: '5px',
                }}
              >
                <CheckCircle2 size={14} color="#059669" />
                Verified
              </span>
            ) : (
              <span
                style={{
                  fontSize: '12px',
                  fontWeight: 700,
                  color: '#64748b',
                  backgroundColor: '#f1f5f9',
                  border: '1px solid #e2e8f0',
                  padding: '4px 12px',
                  borderRadius: '10px',
                }}
              >
                Not Configured
              </span>
            )}
          </div>
        </div>

        {/* Action Alerts */}
        {actionError && (
          <div
            style={{
              padding: '12px 16px',
              borderRadius: '10px',
              backgroundColor: '#fef2f2',
              border: '1px solid #fecaca',
              color: '#b91c1c',
              fontSize: '13px',
              marginBottom: '18px',
            }}
          >
            {actionError}
          </div>
        )}

        {actionSuccess && (
          <div
            style={{
              padding: '12px 16px',
              borderRadius: '10px',
              backgroundColor: '#ecfdf5',
              border: '1px solid #a7f3d0',
              color: '#047857',
              fontSize: '13px',
              marginBottom: '18px',
              fontWeight: 600,
            }}
          >
            {actionSuccess}
          </div>
        )}

        {/* View Mode */}
        {!isEditingAddress ? (
          <div>
            {activeAddress ? (
              <div>
                <div style={{ marginBottom: '16px' }}>
                  <label style={{ display: 'block', fontSize: '12px', fontWeight: 700, color: '#64748b', textTransform: 'uppercase', marginBottom: '6px' }}>
                    Active Public Wallet Address
                  </label>
                  <div
                    style={{
                      padding: '14px 16px',
                      backgroundColor: '#f8fafc',
                      border: '1px solid #cbd5e1',
                      borderRadius: '12px',
                      fontFamily: 'monospace',
                      fontSize: '14px',
                      color: '#0f172a',
                      wordBreak: 'break-all',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'space-between',
                      gap: '12px',
                    }}
                  >
                    <span>{activeAddress}</span>
                    <button
                      type="button"
                      onClick={() => handleCopyAddress(activeAddress)}
                      style={{
                        background: '#ffffff',
                        border: '1px solid #cbd5e1',
                        borderRadius: '8px',
                        padding: '6px 12px',
                        color: copiedAddress ? '#059669' : '#334155',
                        cursor: 'pointer',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '5px',
                        fontSize: '12px',
                        fontWeight: 700,
                        flexShrink: 0,
                      }}
                    >
                      {copiedAddress ? (
                        <>
                          <Check size={14} color="#059669" />
                          <span>Copied!</span>
                        </>
                      ) : (
                        <>
                          <Copy size={14} />
                          <span>Copy</span>
                        </>
                      )}
                    </button>
                  </div>
                </div>

                <div
                  style={{
                    padding: '12px 16px',
                    backgroundColor: '#f0fdf4',
                    border: '1px solid #bbf7d0',
                    borderRadius: '10px',
                    fontSize: '12.5px',
                    color: '#166534',
                    marginBottom: '20px',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '8px',
                  }}
                >
                  <Check size={16} color="#16a34a" />
                  <span>This verified address is active on BNB Smart Chain (BEP-20). Email OTP verification is required to change it.</span>
                </div>

                <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                  <button
                    type="button"
                    onClick={handleStartEdit}
                    style={{
                      padding: '8px 18px',
                      backgroundColor: '#ffffff',
                      border: '1px solid #cbd5e1',
                      borderRadius: '8px',
                      fontSize: '13px',
                      fontWeight: 700,
                      color: '#1e293b',
                      cursor: 'pointer',
                      display: 'inline-flex',
                      alignItems: 'center',
                      gap: '6px',
                    }}
                  >
                    <Edit3 size={14} />
                    <span>Change Wallet Address</span>
                  </button>

                  <button
                    type="button"
                    onClick={loadWalletDetails}
                    style={{
                      padding: '8px 14px',
                      backgroundColor: '#f8fafc',
                      border: '1px solid #e2e8f0',
                      borderRadius: '8px',
                      fontSize: '12.5px',
                      color: '#64748b',
                      cursor: 'pointer',
                      display: 'inline-flex',
                      alignItems: 'center',
                      gap: '5px',
                    }}
                  >
                    <RefreshCw size={13} />
                    <span>Refresh</span>
                  </button>
                </div>
              </div>
            ) : (
              <div>
                <div style={{ padding: '24px', textAlign: 'center', backgroundColor: '#f8fafc', borderRadius: '12px', border: '1px dashed #cbd5e1', marginBottom: '20px' }}>
                  <WalletCards size={36} style={{ margin: '0 auto 10px auto', display: 'block', color: '#94a3b8' }} />
                  <h3 style={{ fontSize: '15px', fontWeight: 700, color: '#334155', margin: '0 0 4px 0' }}>
                    No Web3 Wallet Configured
                  </h3>
                  <p style={{ fontSize: '13px', color: '#64748b', margin: 0 }}>
                    Add your public USDT (BEP-20) wallet address. Registered email OTP verification is required for activation.
                  </p>
                </div>

                <button
                  type="button"
                  onClick={handleStartEdit}
                  style={{
                    padding: '10px 20px',
                    backgroundColor: '#0284c7',
                    color: '#ffffff',
                    border: 'none',
                    borderRadius: '10px',
                    fontSize: '13.5px',
                    fontWeight: 700,
                    cursor: 'pointer',
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: '8px',
                    boxShadow: '0 2px 4px rgba(2, 132, 199, 0.25)',
                  }}
                >
                  <KeyRound size={16} />
                  <span>Add Web3 Wallet Address</span>
                </button>
              </div>
            )}
          </div>
        ) : (
          /* Edit / Add Address Form */
          <div style={{ backgroundColor: '#ffffff', padding: '20px', borderRadius: '12px', border: '1px solid #e2e8f0' }}>
            <form onSubmit={otpSent ? handleVerifyOtp : handleSendOtp}>
              {/* Network & Security Notice */}
              <div
                style={{
                  padding: '12px 16px',
                  backgroundColor: '#eff6ff',
                  border: '1px solid #bfdbfe',
                  borderRadius: '10px',
                  fontSize: '13px',
                  color: '#1e40af',
                  marginBottom: '20px',
                  lineHeight: '1.5',
                }}
              >
                <div style={{ fontWeight: 800, marginBottom: '2px', display: 'flex', alignItems: 'center', gap: '6px' }}>
                  <Info size={16} />
                  <span>Network &amp; Security Notice</span>
                </div>
                Use only a wallet you control on the <strong>USDT (BEP-20) network (BNB Smart Chain)</strong>.
                <br />
                OTP verifies your account authorization for this wallet address. Never enter a private key, seed phrase, or wallet password.
              </div>

              {/* Registered Email Information Display */}
              <div style={{ marginBottom: '16px', padding: '10px 14px', backgroundColor: '#f8fafc', borderRadius: '8px', border: '1px solid #e2e8f0' }}>
                <span style={{ fontSize: '12px', fontWeight: 700, color: '#64748b', textTransform: 'uppercase', display: 'block', marginBottom: '2px' }}>
                  Registered Account Email
                </span>
                <span style={{ fontSize: '13.5px', fontWeight: 600, color: '#1e293b' }}>
                  {userRegisteredEmail}
                </span>
              </div>

              {/* Wallet Address Input */}
              <div style={{ marginBottom: '18px' }}>
                <label style={{ display: 'block', fontSize: '13px', fontWeight: 700, color: '#334155', marginBottom: '6px' }}>
                  USDT (BEP-20) Wallet Address
                </label>
                <input
                  type="text"
                  value={walletAddressInput}
                  onChange={(e) => setWalletAddressInput(e.target.value)}
                  disabled={otpSent || isSubmitting}
                  placeholder="0x..."
                  style={{
                    width: '100%',
                    padding: '11px 14px',
                    fontSize: '14px',
                    fontFamily: 'monospace',
                    borderRadius: '10px',
                    border: '1px solid #cbd5e1',
                    backgroundColor: otpSent ? '#f8fafc' : '#ffffff',
                    boxSizing: 'border-box',
                  }}
                />
                <div style={{ fontSize: '11.5px', color: '#64748b', marginTop: '5px' }}>
                  Must be a valid 42-character BNB Smart Chain hexadecimal address starting with 0x.
                </div>
              </div>

              {/* OTP Input (When OTP is sent) */}
              {otpSent && (
                <div style={{ marginBottom: '20px', padding: '16px', backgroundColor: '#f0fdf4', borderRadius: '10px', border: '1px solid #bbf7d0' }}>
                  <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '8px' }}>
                    <label style={{ fontSize: '13px', fontWeight: 700, color: '#166534' }}>
                      Enter 6-Digit Email Code
                    </label>
                    {demoOtp && (
                      <button
                        type="button"
                        onClick={() => setOtpInput(demoOtp)}
                        style={{
                          fontSize: '11px',
                          fontWeight: 700,
                          color: '#047857',
                          backgroundColor: '#dcfce7',
                          border: '1px solid #86efac',
                          padding: '2px 8px',
                          borderRadius: '6px',
                          cursor: 'pointer',
                        }}
                      >
                        Auto-fill Demo Code: {demoOtp}
                      </button>
                    )}
                  </div>
                  <input
                    type="text"
                    maxLength={8}
                    value={otpInput}
                    onChange={(e) => setOtpInput(e.target.value.replace(/\D/g, ''))}
                    placeholder="123456"
                    disabled={isSubmitting}
                    style={{
                      width: '100%',
                      maxWidth: '240px',
                      padding: '11px 16px',
                      fontSize: '20px',
                      fontWeight: 800,
                      letterSpacing: '5px',
                      textAlign: 'center',
                      borderRadius: '10px',
                      border: '2px solid #059669',
                      backgroundColor: '#ffffff',
                      boxSizing: 'border-box',
                    }}
                  />
                  <div style={{ fontSize: '12px', color: '#15803d', marginTop: '8px' }}>
                    Verification code sent to registered email: <strong>{userRegisteredEmail}</strong>
                  </div>
                </div>
              )}

              {/* Form Actions */}
              <div style={{ display: 'flex', alignItems: 'center', gap: '10px', flexWrap: 'wrap' }}>
                {!otpSent ? (
                  <button
                    type="submit"
                    disabled={isSubmitting}
                    style={{
                      padding: '10px 22px',
                      backgroundColor: '#0284c7',
                      color: '#ffffff',
                      border: 'none',
                      borderRadius: '10px',
                      fontSize: '13px',
                      fontWeight: 700,
                      cursor: isSubmitting ? 'not-allowed' : 'pointer',
                      display: 'inline-flex',
                      alignItems: 'center',
                      gap: '6px',
                    }}
                  >
                    <Send size={14} />
                    <span>{isSubmitting ? 'Sending Code...' : 'Send Email OTP'}</span>
                  </button>
                ) : (
                  <>
                    <button
                      type="submit"
                      disabled={isSubmitting || otpInput.trim().length < 6}
                      style={{
                        padding: '10px 22px',
                        backgroundColor: '#059669',
                        color: '#ffffff',
                        border: 'none',
                        borderRadius: '10px',
                        fontSize: '13px',
                        fontWeight: 700,
                        cursor: (isSubmitting || otpInput.trim().length < 6) ? 'not-allowed' : 'pointer',
                        opacity: (isSubmitting || otpInput.trim().length < 6) ? 0.65 : 1,
                        display: 'inline-flex',
                        alignItems: 'center',
                        gap: '6px',
                      }}
                    >
                      <Check size={15} />
                      <span>{isSubmitting ? 'Verifying...' : 'Verify Wallet'}</span>
                    </button>

                    <button
                      type="button"
                      disabled={otpCooldown > 0 || isSubmitting}
                      onClick={handleSendOtp}
                      style={{
                        padding: '10px 16px',
                        backgroundColor: '#f1f5f9',
                        color: '#475569',
                        border: '1px solid #cbd5e1',
                        borderRadius: '10px',
                        fontSize: '12.5px',
                        fontWeight: 600,
                        cursor: otpCooldown > 0 ? 'not-allowed' : 'pointer',
                        opacity: otpCooldown > 0 ? 0.6 : 1,
                      }}
                    >
                      {otpCooldown > 0 ? `Resend OTP (${otpCooldown}s)` : 'Resend OTP'}
                    </button>
                  </>
                )}

                <button
                  type="button"
                  onClick={handleCancelEdit}
                  disabled={isSubmitting}
                  style={{
                    padding: '10px 16px',
                    backgroundColor: '#ffffff',
                    color: '#64748b',
                    border: '1px solid #cbd5e1',
                    borderRadius: '10px',
                    fontSize: '12.5px',
                    fontWeight: 600,
                    cursor: 'pointer',
                  }}
                >
                  Cancel
                </button>
              </div>
            </form>
          </div>
        )}
      </section>

      {/* Network Information & FAQs Card */}
      <section
        className="member-card"
        style={{
          background: '#ffffff',
          borderRadius: '16px',
          border: '1px solid #e5e7eb',
          padding: '24px',
          boxShadow: '0 1px 3px 0 rgba(0, 0, 0, 0.05)',
        }}
      >
        <h3 style={{ fontSize: '15px', fontWeight: 800, color: '#1e293b', margin: '0 0 12px 0' }}>
          About Web3 USDT (BEP-20) Destination Wallet
        </h3>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(260px, 1fr))', gap: '16px', fontSize: '13px', color: '#475569', lineHeight: 1.5 }}>
          <div style={{ padding: '12px 14px', backgroundColor: '#f8fafc', borderRadius: '10px', border: '1px solid #e2e8f0' }}>
            <strong style={{ color: '#0f172a', display: 'block', marginBottom: '4px' }}>
              Supported Token &amp; Network
            </strong>
            This wallet address accepts exclusively USDT tokens on the BNB Smart Chain (BEP-20). Do not use TRC-20, ERC-20, or other networks.
          </div>

          <div style={{ padding: '12px 14px', backgroundColor: '#f8fafc', borderRadius: '10px', border: '1px solid #e2e8f0' }}>
            <strong style={{ color: '#0f172a', display: 'block', marginBottom: '4px' }}>
              Email OTP Authorization
            </strong>
            Any initial addition or subsequent address change requires a 6-digit Email OTP sent directly to your registered account email to authorize modifications.
          </div>

          <div style={{ padding: '12px 14px', backgroundColor: '#f8fafc', borderRadius: '10px', border: '1px solid #e2e8f0' }}>
            <strong style={{ color: '#0f172a', display: 'block', marginBottom: '4px' }}>
              Safe Non-Custodial Address
            </strong>
            The platform only stores your public receiving address. We never ask for, custody, or store private keys, passwords, or recovery seed phrases.
          </div>
        </div>
      </section>
    </div>
  );
}

export default Web3WalletPage;