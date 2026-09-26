import { useState, useEffect, useCallback } from 'react';
import {
  X,
  Wallet,
  Gift,
  ShieldCheck,
  AlertTriangle,
  ChevronLeft,
  ChevronRight,
  RefreshCw,
  Check,
  Copy,
  Edit3,
  Send,
  KeyRound,
} from 'lucide-react';
import useAuth from '../../hooks/useAuth';
import { ModalPortal } from './ModalPortal';
import accountApi from '../../api/accountApi';

export function RewardWalletModal({ isOpen, onClose, onOpenVerification }) {
  const { user } = useAuth();
  const [walletData, setWalletData] = useState(null);
  const [history, setHistory] = useState([]);
  const [pagination, setPagination] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [currentPage, setCurrentPage] = useState(1);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  // Wallet Address Management State
  const [isEditingAddress, setIsEditingAddress] = useState(false);
  const [walletAddressInput, setWalletAddressInput] = useState('');
  const [otpInput, setOtpInput] = useState('');
  const [otpSent, setOtpSent] = useState(false);
  const [otpCooldown, setOtpCooldown] = useState(0);
  const [demoOtp, setDemoOtp] = useState('');
  const [walletLoading, setWalletLoading] = useState(false);
  const [walletError, setWalletError] = useState('');
  const [walletSuccess, setWalletSuccess] = useState('');
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

  const loadWallet = useCallback(async () => {
    if (!isOpen) return;
    setLoading(true);
    setError('');

    try {
      const [walletSettled, historySettled] = await Promise.allSettled([
        accountApi.getRewardWallet(),
        accountApi.getRewardHistory({ page: currentPage, per_page: 10 }),
      ]);

      let hasWalletSuccess = false;
      let hasHistorySuccess = false;

      if (walletSettled.status === 'fulfilled' && walletSettled.value?.success) {
        setWalletData(walletSettled.value.wallet);
        hasWalletSuccess = true;
      }

      if (historySettled.status === 'fulfilled' && historySettled.value?.success) {
        const hData = historySettled.value.rewards || {};
        setHistory(hData.data || []);
        setPagination({
          current_page: hData.current_page || 1,
          last_page: hData.last_page || 1,
          total: hData.total || 0,
        });
        hasHistorySuccess = true;
      }

      if (!hasHistorySuccess) {
        const historyErr = historySettled.status === 'rejected' ? historySettled.reason : null;
        console.error('Failed to load history:', historyErr);
        setError('Unable to load wallet data.');
      } else if (!hasWalletSuccess) {
        const walletErr = walletSettled.status === 'rejected' ? walletSettled.reason : null;
        console.error('Failed to load wallet:', walletErr);
        setError('Unable to load wallet data.');
      }
    } catch (err) {
      console.error('Failed to load wallet:', err);
      setError('Unable to load wallet data.');
    } finally {
      setLoading(false);
    }
  }, [isOpen, currentPage]);

  useEffect(() => {
    if (isOpen) {
      loadWallet();
    }
  }, [isOpen, loadWallet]);

  const handleStartEdit = () => {
    setWalletAddressInput(walletData?.wallet_address || '');
    setIsEditingAddress(true);
    setOtpSent(false);
    setOtpInput('');
    setWalletError('');
    setWalletSuccess('');
  };

  const handleCancelEdit = () => {
    setIsEditingAddress(false);
    setOtpSent(false);
    setOtpInput('');
    setWalletAddressInput('');
    setWalletError('');
    setWalletSuccess('');
  };

  const handleSendOtp = async (e) => {
    e?.preventDefault();
    setWalletError('');
    setWalletSuccess('');

    const cleanAddress = walletAddressInput.trim();
    if (!cleanAddress) {
      setWalletError('Please enter your USDT (BEP-20) wallet address.');
      return;
    }

    if (!/^0x[a-fA-F0-9]{40}$/.test(cleanAddress)) {
      setWalletError('Please enter a valid USDT (BEP-20) BNB Smart Chain wallet address starting with 0x (42 characters).');
      return;
    }

    setWalletLoading(true);
    try {
      const res = await accountApi.sendRewardWalletOtp({ wallet_address: cleanAddress });
      if (res.success) {
        setOtpSent(true);
        setOtpCooldown(60);
        setWalletSuccess(res.message || 'Verification code sent to your registered email.');
        if (res.demo_otp) {
          setDemoOtp(res.demo_otp);
        }
      } else {
        setWalletError(res.message || 'Failed to send verification code.');
      }
    } catch (err) {
      const msg = err.response?.data?.message || err.response?.data?.errors?.wallet_address?.[0] || 'Failed to send email verification code.';
      setWalletError(msg);
    } finally {
      setWalletLoading(false);
    }
  };

  const handleVerifyOtp = async (e) => {
    e?.preventDefault();
    setWalletError('');
    setWalletSuccess('');

    const cleanOtp = otpInput.trim().replace(/\D/g, '');
    if (!cleanOtp || cleanOtp.length < 6) {
      setWalletError('Please enter the 6-digit email verification code.');
      return;
    }

    setWalletLoading(true);
    try {
      const res = await accountApi.verifyRewardWalletOtp({
        otp: cleanOtp,
        wallet_address: walletAddressInput.trim(),
      });

      if (res.success) {
        setWalletSuccess(res.message || 'Wallet address verified successfully!');
        setIsEditingAddress(false);
        setOtpSent(false);
        setOtpInput('');
        setDemoOtp('');
        loadWallet();
      } else {
        setWalletError(res.message || 'Failed to verify OTP.');
      }
    } catch (err) {
      const msg = err.response?.data?.message || err.response?.data?.errors?.otp?.[0] || 'Invalid verification code.';
      setWalletError(msg);
    } finally {
      setWalletLoading(false);
    }
  };

  const handleCopyAddress = (addr) => {
    if (!addr) return;
    navigator.clipboard.writeText(addr).then(() => {
      setCopiedAddress(true);
      setTimeout(() => setCopiedAddress(false), 2500);
    }).catch(() => {});
  };

  if (!isOpen || !user) return null;

  const isVerified = Boolean(user.is_verified || user.mobile_verified_at);
  const balance = walletData ? (walletData.wallet ?? walletData.reward_balance ?? 0) : parseFloat(user.wallet ?? user.reward_balance ?? 0);
  const activeAddress = walletData?.wallet_address;
  const isAddressVerified = Boolean(walletData?.has_verified_wallet || (walletData?.wallet_status === 'verified' && activeAddress));

  return (
    <ModalPortal isOpen={isOpen} onClose={onClose}>
      <div
        className="card global-modal-panel reward-wallet-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="reward-wallet-modal-title"
        style={{
          width: '100%',
          maxWidth: '680px',
          maxHeight: 'min(90vh, 760px)',
          display: 'flex',
          flexDirection: 'column',
          backgroundColor: '#ffffff',
          borderRadius: '20px',
          boxShadow: '0 25px 50px -12px rgba(15, 23, 42, 0.35)',
          overflow: 'hidden',
          boxSizing: 'border-box',
        }}
      >
        {/* Header */}
        <div
          className="global-modal-header"
          style={{
            padding: '18px 24px',
            borderBottom: '1px solid #e2e8f0',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            background: 'linear-gradient(135deg, #047857 0%, #059669 100%)',
            color: '#ffffff',
            boxSizing: 'border-box',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px', minWidth: 0, flex: 1 }}>
            <div
              style={{
                width: '38px',
                height: '38px',
                borderRadius: '10px',
                backgroundColor: 'rgba(255, 255, 255, 0.2)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                flexShrink: 0,
              }}
            >
              <Wallet size={20} color="#ffffff" />
            </div>
            <div style={{ minWidth: 0, flex: 1 }}>
              <h3 id="reward-wallet-modal-title" style={{ margin: 0, fontSize: '17px', fontWeight: 800, color: '#ffffff', overflowWrap: 'break-word', wordBreak: 'break-word' }}>
                Wallet
              </h3>
              <p style={{ margin: 0, fontSize: '12px', color: 'rgba(255, 255, 255, 0.85)', overflowWrap: 'break-word', wordBreak: 'break-word' }}>
                USDT (BEP-20) Destination Address &amp; Interaction Rewards
              </p>
            </div>
          </div>
          <button
            type="button"
            onClick={onClose}
            aria-label="Close Wallet"
            style={{
              background: 'rgba(255, 255, 255, 0.15)',
              border: 'none',
              borderRadius: '50%',
              width: '32px',
              height: '32px',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              cursor: 'pointer',
              color: '#ffffff',
              flexShrink: 0,
              marginLeft: '8px',
            }}
          >
            <X size={18} />
          </button>
        </div>

        {/* Body */}
        <div className="global-modal-body reward-wallet-body" style={{ padding: '20px 24px', overflowY: 'auto', flex: 1, minWidth: 0, boxSizing: 'border-box' }}>
          {/* Verification Status Banner */}
          {!isVerified ? (
            <div
              style={{
                padding: '14px 16px',
                borderRadius: '12px',
                backgroundColor: '#fffbeb',
                border: '1px solid #fde68a',
                display: 'flex',
                alignItems: 'flex-start',
                gap: '12px',
                marginBottom: '20px',
              }}
            >
              <AlertTriangle size={20} color="#d97706" style={{ marginTop: '2px', flexShrink: 0 }} />
              <div style={{ flex: 1 }}>
                <div style={{ fontSize: '13.5px', fontWeight: 700, color: '#92400e' }}>
                  WhatsApp Mobile Verification Required
                </div>
                <div style={{ fontSize: '12.5px', color: '#b45309', marginTop: '2px' }}>
                  Only WhatsApp-verified members can configure their Wallet destination address and earn campaign rewards.
                </div>
                {onOpenVerification && (
                  <button
                    type="button"
                    onClick={() => {
                      onClose?.();
                      onOpenVerification();
                    }}
                    style={{
                      marginTop: '8px',
                      padding: '6px 14px',
                      borderRadius: '6px',
                      backgroundColor: '#d97706',
                      color: '#ffffff',
                      fontSize: '12px',
                      fontWeight: 700,
                      border: 'none',
                      cursor: 'pointer',
                    }}
                  >
                    Get Verified Now
                  </button>
                )}
              </div>
            </div>
          ) : (
            <div
              style={{
                padding: '10px 14px',
                borderRadius: '10px',
                backgroundColor: '#ecfdf5',
                border: '1px solid #a7f3d0',
                display: 'flex',
                alignItems: 'center',
                gap: '8px',
                marginBottom: '20px',
                fontSize: '12.5px',
                color: '#065f46',
                fontWeight: 600,
              }}
            >
              <ShieldCheck size={16} color="#059669" />
              <span>WhatsApp Verified ({user.phone}) — Eligible for campaign rewards &amp; wallet address setup</span>
            </div>
          )}

          {/* Balance Cards */}
          <div
            style={{
              display: 'grid',
              gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 150px), 1fr))',
              gap: '12px',
              marginBottom: '24px',
              width: '100%',
              boxSizing: 'border-box',
            }}
          >
            {/* Balance Card */}
            <div
              style={{
                padding: '16px',
                borderRadius: '12px',
                backgroundColor: '#f8fafc',
                border: '1px solid #e2e8f0',
              }}
            >
              <div style={{ fontSize: '11.5px', fontWeight: 700, color: '#64748b', textTransform: 'uppercase', marginBottom: '4px' }}>
                Wallet Balance
              </div>
              <div style={{ fontSize: '24px', fontWeight: 900, color: '#047857' }}>
                ${Number(balance).toFixed(4)} <span style={{ fontSize: '13px', fontWeight: 600, color: '#64748b' }}>USD</span>
              </div>
              <div style={{ fontSize: '11.5px', color: '#94a3b8', marginTop: '4px' }}>
                Separate from Advertising Funds
              </div>
            </div>

            {/* Total Rewards Earned */}
            <div
              style={{
                padding: '16px',
                borderRadius: '12px',
                backgroundColor: '#f8fafc',
                border: '1px solid #e2e8f0',
              }}
            >
              <div style={{ fontSize: '11.5px', fontWeight: 700, color: '#64748b', textTransform: 'uppercase', marginBottom: '4px' }}>
                Total Rewarded Campaigns
              </div>
              <div style={{ fontSize: '24px', fontWeight: 900, color: '#1e293b' }}>
                {walletData?.total_reward_count ?? (pagination.total || 0)}
              </div>
              <div style={{ fontSize: '11.5px', color: '#94a3b8', marginTop: '4px' }}>
                1 reward per campaign limit
              </div>
            </div>

            {/* Reward Rate */}
            <div
              style={{
                padding: '16px',
                borderRadius: '12px',
                backgroundColor: '#f8fafc',
                border: '1px solid #e2e8f0',
              }}
            >
              <div style={{ fontSize: '11.5px', fontWeight: 700, color: '#64748b', textTransform: 'uppercase', marginBottom: '4px' }}>
                Campaign Reward Rate
              </div>
              <div style={{ fontSize: '18px', fontWeight: 900, color: '#2563eb' }}>
                {walletData?.reward_range_formatted || '$0.025 – $0.050 USD'}
              </div>
              <div style={{ fontSize: '11.5px', color: '#94a3b8', marginTop: '4px' }}>
                Dynamic based on verified referrals
              </div>
            </div>
          </div>

          {/* USDT (BEP-20) Destination Wallet Address Section */}
          <div
            style={{
              padding: '18px 20px',
              borderRadius: '14px',
              backgroundColor: '#f8fafc',
              border: '1px solid #e2e8f0',
              marginBottom: '24px',
            }}
          >
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '12px', flexWrap: 'wrap', gap: '8px' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                <KeyRound size={17} color="#059669" />
                <h4 style={{ margin: 0, fontSize: '14.5px', fontWeight: 800, color: '#1e293b' }}>
                  Reward Destination Wallet Address
                </h4>
                <span
                  style={{
                    fontSize: '11px',
                    fontWeight: 700,
                    color: '#0369a1',
                    backgroundColor: '#e0f2fe',
                    padding: '2px 8px',
                    borderRadius: '6px',
                  }}
                >
                  USDT (BEP-20)
                </span>
              </div>

              {isAddressVerified ? (
                <span
                  style={{
                    fontSize: '11.5px',
                    fontWeight: 700,
                    color: '#047857',
                    backgroundColor: '#ecfdf5',
                    border: '1px solid #a7f3d0',
                    padding: '3px 10px',
                    borderRadius: '8px',
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: '4px',
                  }}
                >
                  <Check size={13} color="#059669" />
                  Verified
                </span>
              ) : (
                <span
                  style={{
                    fontSize: '11.5px',
                    fontWeight: 700,
                    color: '#64748b',
                    backgroundColor: '#f1f5f9',
                    border: '1px solid #e2e8f0',
                    padding: '3px 10px',
                    borderRadius: '8px',
                  }}
                >
                  Not Configured
                </span>
              )}
            </div>

            {/* Error & Success Messages */}
            {walletError && (
              <div
                style={{
                  padding: '10px 14px',
                  borderRadius: '8px',
                  backgroundColor: '#fef2f2',
                  border: '1px solid #fecaca',
                  color: '#b91c1c',
                  fontSize: '12.5px',
                  marginBottom: '14px',
                }}
              >
                {walletError}
              </div>
            )}

            {walletSuccess && (
              <div
                style={{
                  padding: '10px 14px',
                  borderRadius: '8px',
                  backgroundColor: '#ecfdf5',
                  border: '1px solid #a7f3d0',
                  color: '#047857',
                  fontSize: '12.5px',
                  marginBottom: '14px',
                  fontWeight: 600,
                }}
              >
                {walletSuccess}
              </div>
            )}

            {!isEditingAddress ? (
              <div>
                {activeAddress ? (
                  <div>
                    <div
                      style={{
                        padding: '12px 14px',
                        backgroundColor: '#ffffff',
                        border: '1px solid #cbd5e1',
                        borderRadius: '10px',
                        fontFamily: 'monospace',
                        fontSize: '13px',
                        color: '#0f172a',
                        wordBreak: 'break-all',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        gap: '10px',
                        marginBottom: '10px',
                      }}
                    >
                      <span>{activeAddress}</span>
                      <button
                        type="button"
                        onClick={() => handleCopyAddress(activeAddress)}
                        style={{
                          background: 'none',
                          border: 'none',
                          color: copiedAddress ? '#059669' : '#64748b',
                          cursor: 'pointer',
                          display: 'flex',
                          alignItems: 'center',
                          gap: '4px',
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

                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '8px' }}>
                      <div style={{ fontSize: '11.5px', color: '#64748b' }}>
                        BNB Smart Chain (BEP-20) network. OTP verification required to change.
                      </div>
                      <button
                        type="button"
                        onClick={handleStartEdit}
                        style={{
                          padding: '6px 14px',
                          backgroundColor: '#ffffff',
                          border: '1px solid #cbd5e1',
                          borderRadius: '8px',
                          fontSize: '12px',
                          fontWeight: 700,
                          color: '#334155',
                          cursor: 'pointer',
                          display: 'inline-flex',
                          alignItems: 'center',
                          gap: '5px',
                        }}
                      >
                        <Edit3 size={13} />
                        <span>Change Wallet Address</span>
                      </button>
                    </div>
                  </div>
                ) : (
                  <div>
                    <div style={{ fontSize: '12.5px', color: '#64748b', marginBottom: '12px' }}>
                      Add your USDT (BEP-20) wallet address to set up your destination address for earned campaign rewards. Registered email OTP verification is required to activate.
                    </div>
                    <button
                      type="button"
                      onClick={handleStartEdit}
                      style={{
                        padding: '8px 16px',
                        backgroundColor: '#059669',
                        color: '#ffffff',
                        border: 'none',
                        borderRadius: '8px',
                        fontSize: '12.5px',
                        fontWeight: 700,
                        cursor: 'pointer',
                        display: 'inline-flex',
                        alignItems: 'center',
                        gap: '6px',
                        boxShadow: '0 2px 4px rgba(5, 150, 105, 0.25)',
                      }}
                    >
                      <Wallet size={14} />
                      <span>Add Wallet Address</span>
                    </button>
                  </div>
                )}
              </div>
            ) : (
              <div style={{ backgroundColor: '#ffffff', padding: '16px', borderRadius: '10px', border: '1px solid #cbd5e1' }}>
                <form onSubmit={otpSent ? handleVerifyOtp : handleSendOtp}>
                  {/* Network Warning Microcopy */}
                  <div
                    style={{
                      padding: '10px 12px',
                      backgroundColor: '#eff6ff',
                      border: '1px solid #bfdbfe',
                      borderRadius: '8px',
                      fontSize: '12px',
                      color: '#1e40af',
                      marginBottom: '14px',
                      lineHeight: '1.4',
                    }}
                  >
                    <strong>⚠️ Important Network Notice:</strong> Only use a <strong>USDT wallet on the BEP-20 network (BNB Smart Chain)</strong>.
                    <br />
                    OTP verifies your account authorization for this wallet address. Make sure the address belongs to your USDT BEP-20 wallet before saving it.
                  </div>

                  {/* Registered Email info */}
                  <div style={{ marginBottom: '12px', padding: '8px 12px', backgroundColor: '#f8fafc', borderRadius: '6px', border: '1px solid #e2e8f0' }}>
                    <span style={{ fontSize: '11px', fontWeight: 700, color: '#64748b', textTransform: 'uppercase', display: 'block' }}>
                      Registered Account Email
                    </span>
                    <span style={{ fontSize: '12.5px', fontWeight: 600, color: '#1e293b' }}>
                      {walletData?.masked_email || walletData?.email || user.email}
                    </span>
                  </div>

                  {/* Wallet Address Input */}
                  <div style={{ marginBottom: '14px' }}>
                    <label style={{ display: 'block', fontSize: '12px', fontWeight: 700, color: '#334155', marginBottom: '4px' }}>
                      USDT (BEP-20) Wallet Address
                    </label>
                    <input
                      type="text"
                      value={walletAddressInput}
                      onChange={(e) => setWalletAddressInput(e.target.value)}
                      disabled={otpSent || walletLoading}
                      placeholder="0x..."
                      style={{
                        width: '100%',
                        padding: '9px 12px',
                        fontSize: '13px',
                        fontFamily: 'monospace',
                        borderRadius: '8px',
                        border: '1px solid #cbd5e1',
                        backgroundColor: otpSent ? '#f8fafc' : '#ffffff',
                        boxSizing: 'border-box',
                      }}
                    />
                    <div style={{ fontSize: '11px', color: '#64748b', marginTop: '4px' }}>
                      Must be a valid 42-character BNB Smart Chain hexadecimal address starting with 0x.
                    </div>
                  </div>

                  {/* OTP Input (when OTP is sent) */}
                  {otpSent && (
                    <div style={{ marginBottom: '16px', padding: '12px', backgroundColor: '#f0fdf4', borderRadius: '8px', border: '1px solid #bbf7d0' }}>
                      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '6px' }}>
                        <label style={{ fontSize: '12.5px', fontWeight: 700, color: '#166534' }}>
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
                              padding: '2px 6px',
                              borderRadius: '4px',
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
                        disabled={walletLoading}
                        style={{
                          width: '100%',
                          maxWidth: '220px',
                          padding: '10px 14px',
                          fontSize: '18px',
                          fontWeight: 800,
                          letterSpacing: '4px',
                          textAlign: 'center',
                          borderRadius: '8px',
                          border: '2px solid #059669',
                          backgroundColor: '#ffffff',
                          boxSizing: 'border-box',
                        }}
                      />
                      <div style={{ fontSize: '11.5px', color: '#15803d', marginTop: '6px' }}>
                        Verification code sent to registered email: <strong>{walletData?.masked_email || walletData?.email || user.email}</strong>
                      </div>
                    </div>
                  )}

                  {/* Form Actions */}
                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' }}>
                    {!otpSent ? (
                      <button
                        type="submit"
                        disabled={walletLoading}
                        style={{
                          padding: '8px 18px',
                          backgroundColor: '#059669',
                          color: '#ffffff',
                          border: 'none',
                          borderRadius: '8px',
                          fontSize: '12.5px',
                          fontWeight: 700,
                          cursor: walletLoading ? 'not-allowed' : 'pointer',
                          display: 'inline-flex',
                          alignItems: 'center',
                          gap: '6px',
                        }}
                      >
                        <Send size={13} />
                        <span>{walletLoading ? 'Sending Code...' : 'Send Email OTP'}</span>
                      </button>
                    ) : (
                      <>
                        <button
                          type="submit"
                          disabled={walletLoading || otpInput.trim().length < 6}
                          style={{
                            padding: '8px 18px',
                            backgroundColor: '#059669',
                            color: '#ffffff',
                            border: 'none',
                            borderRadius: '8px',
                            fontSize: '12.5px',
                            fontWeight: 700,
                            cursor: (walletLoading || otpInput.trim().length < 6) ? 'not-allowed' : 'pointer',
                            opacity: (walletLoading || otpInput.trim().length < 6) ? 0.65 : 1,
                            display: 'inline-flex',
                            alignItems: 'center',
                            gap: '6px',
                          }}
                        >
                          <Check size={14} />
                          <span>{walletLoading ? 'Verifying...' : 'Verify & Activate Wallet'}</span>
                        </button>

                        <button
                          type="button"
                          disabled={otpCooldown > 0 || walletLoading}
                          onClick={handleSendOtp}
                          style={{
                            padding: '8px 14px',
                            backgroundColor: '#f1f5f9',
                            color: '#475569',
                            border: '1px solid #cbd5e1',
                            borderRadius: '8px',
                            fontSize: '12px',
                            fontWeight: 600,
                            cursor: otpCooldown > 0 ? 'not-allowed' : 'pointer',
                            opacity: otpCooldown > 0 ? 0.6 : 1,
                          }}
                        >
                          {otpCooldown > 0 ? `Resend Code (${otpCooldown}s)` : 'Resend Code'}
                        </button>
                      </>
                    )}

                    <button
                      type="button"
                      onClick={handleCancelEdit}
                      disabled={walletLoading}
                      style={{
                        padding: '8px 14px',
                        backgroundColor: '#ffffff',
                        color: '#64748b',
                        border: '1px solid #cbd5e1',
                        borderRadius: '8px',
                        fontSize: '12px',
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
          </div>

          {/* Reward History Section */}
          <div>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '12px' }}>
              <h4 style={{ margin: 0, fontSize: '14px', fontWeight: 800, color: '#1e293b' }}>
                Reward History
              </h4>
              <button
                type="button"
                onClick={loadWallet}
                disabled={loading}
                style={{
                  background: 'none',
                  border: 'none',
                  color: '#64748b',
                  cursor: 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '4px',
                  fontSize: '12px',
                }}
              >
                <RefreshCw size={13} className={loading ? 'animate-spin' : ''} />
                Refresh
              </button>
            </div>

            {error ? (
              <div style={{ padding: '16px', textAlign: 'center', color: '#dc2626', fontSize: '13px' }}>
                {error}
              </div>
            ) : history.length === 0 ? (
              <div
                style={{
                  padding: '36px 16px',
                  textAlign: 'center',
                  backgroundColor: '#f8fafc',
                  borderRadius: '12px',
                  border: '1px dashed #cbd5e1',
                  color: '#94a3b8',
                }}
              >
                <Gift size={32} style={{ margin: '0 auto 8px auto', display: 'block', color: '#cbd5e1' }} />
                <div style={{ fontSize: '13.5px', fontWeight: 700, color: '#64748b' }}>No Campaign Rewards Yet</div>
                <div style={{ fontSize: '12px', marginTop: '4px' }}>
                  Visit sponsored ads in your feed to earn up to $0.050 USD per campaign interaction based on your direct verified referrals.
                </div>
              </div>
            ) : (
              <div style={{ border: '1px solid #e2e8f0', borderRadius: '10px', overflow: 'hidden', width: '100%', boxSizing: 'border-box' }}>
                {/* Desktop/Tablet Table View (>= 768px, specifically >= 1024px baseline preserved) */}
                <div className="reward-history-desktop-table" style={{ overflowX: 'auto', WebkitOverflowScrolling: 'touch', width: '100%' }}>
                  <table style={{ width: '100%', minWidth: '420px', borderCollapse: 'collapse', fontSize: '12.5px', textAlign: 'left' }}>
                    <thead>
                      <tr style={{ backgroundColor: '#f8fafc', borderBottom: '1px solid #e2e8f0', color: '#64748b', fontSize: '11px', textTransform: 'uppercase' }}>
                        <th style={{ padding: '8px 12px', fontWeight: 700 }}>Campaign</th>
                        <th style={{ padding: '8px 12px', fontWeight: 700 }}>Referral Tier</th>
                        <th style={{ padding: '8px 12px', fontWeight: 700, textAlign: 'right' }}>Amount</th>
                        <th style={{ padding: '8px 12px', fontWeight: 700, textAlign: 'right' }}>Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      {history.map((item) => (
                        <tr key={item.id} style={{ borderBottom: '1px solid #f1f5f9' }}>
                          <td style={{ padding: '10px 12px' }}>
                            <div style={{ fontWeight: 700, color: '#1e293b' }}>
                              {item.campaign?.campaign_name || 'Sponsored Campaign'}
                            </div>
                            {item.campaign?.business_page?.name && (
                              <div style={{ fontSize: '11px', color: '#64748b' }}>
                                {item.campaign.business_page.name}
                              </div>
                            )}
                          </td>
                          <td style={{ padding: '10px 12px' }}>
                            <span style={{ fontSize: '11px', fontWeight: 700, padding: '2px 7px', borderRadius: '6px', backgroundColor: '#eff6ff', color: '#1d4ed8', border: '1px solid #bfdbfe' }}>
                              {item.tier_label && item.tier_label !== 'Default' ? `Tier: ${item.tier_label} refs` : 'Standard'}
                            </span>
                            <div style={{ fontSize: '11px', color: '#64748b', marginTop: '2px' }}>
                              {item.direct_verified_referral_count !== null && item.direct_verified_referral_count !== undefined ? `${item.direct_verified_referral_count} verified` : ''}
                            </div>
                          </td>
                          <td style={{ padding: '10px 12px', textAlign: 'right' }}>
                            <span style={{ fontWeight: 800, color: '#047857' }}>
                              {item.reward_formatted || `+$${Number(item.reward_amount_usd || 0).toFixed(4)} USD`}
                            </span>
                          </td>
                          <td style={{ padding: '10px 12px', textAlign: 'right', color: '#64748b', fontSize: '11px' }}>
                            {item.created_at ? new Date(item.created_at).toLocaleDateString() : 'N/A'}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>

                {/* Mobile Responsive Cards View (< 768px) */}
                <div className="reward-history-mobile-cards" style={{ padding: '8px' }}>
                  {history.map((item) => (
                    <div
                      key={item.id}
                      className="reward-history-card"
                      style={{
                        backgroundColor: '#ffffff',
                        border: '1px solid #e2e8f0',
                        borderRadius: '10px',
                        padding: '12px 14px',
                        boxSizing: 'border-box',
                        width: '100%',
                        minWidth: 0,
                      }}
                    >
                      {/* Top Row: Campaign Name & Business Page on Left, Reward Amount on Right */}
                      <div
                        style={{
                          display: 'flex',
                          alignItems: 'flex-start',
                          justifyContent: 'space-between',
                          gap: '10px',
                          marginBottom: '10px',
                        }}
                      >
                        <div style={{ flex: 1, minWidth: 0 }}>
                          <span
                            style={{
                              fontSize: '10px',
                              fontWeight: 800,
                              color: '#64748b',
                              textTransform: 'uppercase',
                              letterSpacing: '0.05em',
                              display: 'block',
                              marginBottom: '2px',
                            }}
                          >
                            Campaign
                          </span>
                          <div
                            style={{
                              fontWeight: 700,
                              color: '#1e293b',
                              fontSize: '13px',
                              lineHeight: 1.35,
                              overflowWrap: 'anywhere',
                              wordBreak: 'break-word',
                            }}
                          >
                            {item.campaign?.campaign_name || 'Sponsored Campaign'}
                          </div>
                          {item.campaign?.business_page?.name && (
                            <div
                              style={{
                                fontSize: '11px',
                                color: '#64748b',
                                marginTop: '2px',
                                overflowWrap: 'anywhere',
                                wordBreak: 'break-word',
                              }}
                            >
                              {item.campaign.business_page.name}
                            </div>
                          )}
                        </div>

                        <div style={{ textAlign: 'right', flexShrink: 0 }}>
                          <span
                            style={{
                              fontSize: '10px',
                              fontWeight: 800,
                              color: '#64748b',
                              textTransform: 'uppercase',
                              letterSpacing: '0.05em',
                              display: 'block',
                              marginBottom: '2px',
                            }}
                          >
                            Reward
                          </span>
                          <span
                            style={{
                              fontWeight: 900,
                              color: '#047857',
                              fontSize: '14px',
                              display: 'inline-block',
                              whiteSpace: 'nowrap',
                            }}
                          >
                            {item.reward_formatted || `+$${Number(item.reward_amount_usd || 0).toFixed(4)} USD`}
                          </span>
                        </div>
                      </div>

                      {/* Bottom Row: Referral Tier & Verified Count on Left, Date on Right */}
                      <div
                        style={{
                          display: 'flex',
                          alignItems: 'flex-end',
                          justifyContent: 'space-between',
                          gap: '10px',
                          paddingTop: '8px',
                          borderTop: '1px solid #f1f5f9',
                          flexWrap: 'wrap',
                        }}
                      >
                        <div style={{ minWidth: 0 }}>
                          <span
                            style={{
                              fontSize: '10px',
                              fontWeight: 800,
                              color: '#64748b',
                              textTransform: 'uppercase',
                              letterSpacing: '0.05em',
                              display: 'block',
                              marginBottom: '4px',
                            }}
                          >
                            Referral Tier
                          </span>
                          <div style={{ display: 'flex', alignItems: 'center', gap: '6px', flexWrap: 'wrap' }}>
                            <span
                              style={{
                                fontSize: '11px',
                                fontWeight: 700,
                                padding: '2px 7px',
                                borderRadius: '6px',
                                backgroundColor: '#eff6ff',
                                color: '#1d4ed8',
                                border: '1px solid #bfdbfe',
                                display: 'inline-block',
                              }}
                            >
                              {item.tier_label && item.tier_label !== 'Default' ? `Tier: ${item.tier_label} refs` : 'Standard'}
                            </span>
                            {item.direct_verified_referral_count !== null && item.direct_verified_referral_count !== undefined && (
                              <span style={{ fontSize: '11.5px', color: '#64748b', fontWeight: 600 }}>
                                {item.direct_verified_referral_count} verified
                              </span>
                            )}
                          </div>
                        </div>

                        <div style={{ textAlign: 'right', flexShrink: 0 }}>
                          <span
                            style={{
                              fontSize: '10px',
                              fontWeight: 800,
                              color: '#64748b',
                              textTransform: 'uppercase',
                              letterSpacing: '0.05em',
                              display: 'block',
                              marginBottom: '2px',
                            }}
                          >
                            Date
                          </span>
                          <span style={{ color: '#475569', fontSize: '12px', fontWeight: 700, whiteSpace: 'nowrap' }}>
                            {item.created_at ? new Date(item.created_at).toLocaleDateString() : 'N/A'}
                          </span>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>

                {/* Shared Pagination */}
                {pagination.last_page > 1 && (
                  <div
                    style={{
                      padding: '8px 12px',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'space-between',
                      borderTop: '1px solid #e2e8f0',
                      backgroundColor: '#f8fafc',
                      fontSize: '11.5px',
                      color: '#64748b',
                    }}
                  >
                    <span>
                      Page {pagination.current_page} of {pagination.last_page}
                    </span>
                    <div style={{ display: 'flex', gap: '4px' }}>
                      <button
                        type="button"
                        disabled={currentPage <= 1}
                        onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
                        style={{
                          padding: '3px 6px',
                          borderRadius: '4px',
                          border: '1px solid #cbd5e1',
                          backgroundColor: '#ffffff',
                          cursor: currentPage <= 1 ? 'not-allowed' : 'pointer',
                          opacity: currentPage <= 1 ? 0.5 : 1,
                        }}
                      >
                        <ChevronLeft size={13} />
                      </button>
                      <button
                        type="button"
                        disabled={currentPage >= pagination.last_page}
                        onClick={() => setCurrentPage((p) => p + 1)}
                        style={{
                          padding: '3px 6px',
                          borderRadius: '4px',
                          border: '1px solid #cbd5e1',
                          backgroundColor: '#ffffff',
                          cursor: currentPage >= pagination.last_page ? 'not-allowed' : 'pointer',
                          opacity: currentPage >= pagination.last_page ? 0.5 : 1,
                        }}
                      >
                        <ChevronRight size={13} />
                      </button>
                    </div>
                  </div>
                )}
              </div>
            )}
          </div>
        </div>

        {/* Footer */}
        <div
          className="global-modal-footer"
          style={{
            padding: '14px 24px',
            borderTop: '1px solid #e2e8f0',
            display: 'flex',
            justifyContent: 'flex-end',
            backgroundColor: '#f8fafc',
            boxSizing: 'border-box',
          }}
        >
          <button
            type="button"
            className="button button-ghost"
            onClick={onClose}
          >
            Close
          </button>
        </div>
      </div>
    </ModalPortal>
  );
}

export default RewardWalletModal;