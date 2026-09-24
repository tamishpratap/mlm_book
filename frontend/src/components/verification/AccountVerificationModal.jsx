import { useState, useEffect } from 'react';
import {
  ShieldCheck,
  CheckCircle2,
  X,
  ArrowRight,
  Sparkles,
  Lock,
  MessageSquare,
  AlertCircle,
  Clock,
  ExternalLink,
  ShieldAlert,
} from 'lucide-react';
import verificationApi from '../../api/verificationApi';
import useAuth from '../../hooks/useAuth';
import { ModalPortal } from '../common/ModalPortal';
import { resolveWhatsAppVerificationUrl, isMemberMobileVerified } from '../../utils/whatsappVerification';

export function AccountVerificationModal({ isOpen, onClose, onVerified, initialError = null, promptMessage = null }) {
  const { user, setUser, refreshUser } = useAuth();

  // Steps: 'registered_phone' | 'send_hi' | 'pending' | 'success'
  const [step, setStep] = useState('registered_phone');
  const [maskedPhone, setMaskedPhone] = useState('');
  const [whatsappUrl, setWhatsappUrl] = useState('');
  const [whatsappDestination, setWhatsappDestination] = useState('');
  const [serverMember, setServerMember] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(initialError || null);
  const activeMember = serverMember || user;

  // Mask phone helper (e.g. +91 98**** 3210)
  const formatMaskedPhone = (phone) => {
    if (!phone) return '';
    const clean = phone.replace(/[^\d+]/g, '');
    if (clean.length <= 6) return clean;
    const start = clean.slice(0, clean.startsWith('+') ? 5 : 4);
    const end = clean.slice(-2);
    return `${start}****${end}`;
  };

  // Initialize or reset state when modal opens
  useEffect(() => {
    let isMounted = true;

    if (isOpen) {
      setError(initialError || null);

      const rawPhone = user?.phone || '';
      if (rawPhone) {
        setMaskedPhone(formatMaskedPhone(rawPhone));
      }

      const isAlreadyVerified = isMemberMobileVerified(user);
      const isAlreadyPending = Boolean(
        !isAlreadyVerified && (
          user?.is_verification_pending ||
          user?.mobile_verification_requested_at ||
          user?.verification_status === 'pending'
        )
      );

      if (isAlreadyVerified) {
        setStep('success');
        setError(null);
      } else if (isAlreadyPending) {
        setStep('pending');
      } else {
        setStep('registered_phone');
      }

      // Fetch fresh verification status & WhatsApp destination from backend
      verificationApi.getStatus()
        .then((res) => {
          if (!isMounted || !res) return;

          if (res.masked_phone) {
            setMaskedPhone(res.masked_phone);
          } else if (res.phone) {
            setMaskedPhone(formatMaskedPhone(res.phone));
          }

          if (res.member) {
            setServerMember(res.member);
          }
          if (res.whatsapp_url) {
            setWhatsappUrl(resolveWhatsAppVerificationUrl(res.whatsapp_url, res.whatsapp_destination || whatsappDestination, res.member || activeMember));
          }
          if (res.whatsapp_destination) {
            setWhatsappDestination(res.whatsapp_destination);
          }

          if (res.is_verified) {
            setStep('success');
            setError(null);

            const verifiedMemberData = res.member || {
              ...user,
              is_verified: true,
              mobile_verified_at: res.mobile_verified_at || new Date().toISOString(),
              verification_status: 'verified',
            };

            if (setUser) {
              setUser((prev) => ({
                ...prev,
                ...verifiedMemberData,
                is_verified: true,
                mobile_verified_at: res.mobile_verified_at || verifiedMemberData.mobile_verified_at || prev?.mobile_verified_at,
                verification_status: 'verified',
              }));
            }
            if (refreshUser) {
              refreshUser();
            }
            onVerified?.(verifiedMemberData);
          } else if (res.is_pending || res.mobile_verification_requested_at) {
            setStep('pending');
          }
        })
        .catch((err) => {
          console.warn('Could not fetch verification status from server:', err);
        });
    }

    return () => {
      isMounted = false;
    };
  }, [isOpen, user]);

  if (!isOpen) return null;

  // Step 1 -> Step 2: Proceed to WhatsApp "Hi" instructions
  const handleProceedToWhatsApp = async () => {
    setIsLoading(true);
    setError(null);

    try {
      const res = await verificationApi.initiateVerification();
      if (res && res.success) {
        if (res.member) {
          setServerMember(res.member);
        }
        if (res.whatsapp_url) {
          setWhatsappUrl(resolveWhatsAppVerificationUrl(res.whatsapp_url, res.whatsapp_destination || whatsappDestination, res.member || activeMember));
        }
        if (res.masked_phone) {
          setMaskedPhone(res.masked_phone);
        }
        if (res.is_verified) {
          setStep('success');
          setError(null);

          const verifiedMemberData = res.member || {
            ...user,
            is_verified: true,
            mobile_verified_at: res.mobile_verified_at || new Date().toISOString(),
            verification_status: 'verified',
          };

          if (setUser) {
            setUser((prev) => ({
              ...prev,
              ...verifiedMemberData,
              is_verified: true,
              mobile_verified_at: res.mobile_verified_at || verifiedMemberData.mobile_verified_at || prev?.mobile_verified_at,
              verification_status: 'verified',
            }));
          }
          if (refreshUser) {
            refreshUser();
          }
          onVerified?.(verifiedMemberData);
          return;
        }
        if (res.is_pending) {
          setStep('pending');
          return;
        }
        setStep('send_hi');
      } else {
        setError(res?.message || 'Unable to initiate WhatsApp verification. Please try again.');
      }
    } catch (err) {
      const msg = err.response?.data?.errors?.phone?.[0]
        || err.response?.data?.message
        || 'Unable to proceed to WhatsApp verification. Please check your profile.';
      setError(msg);
    } finally {
      setIsLoading(false);
    }
  };

  // Open WhatsApp in new window / app
  const handleOpenWhatsApp = () => {
    const targetUrl = resolveWhatsAppVerificationUrl(whatsappUrl, whatsappDestination, activeMember);
    window.open(targetUrl, '_blank', 'noopener,noreferrer');
  };

  // Step 2 -> Step 3: Member submits that they sent "Hi"
  const handleSubmitSentHi = async () => {
    setIsLoading(true);
    setError(null);

    try {
      const res = await verificationApi.submitVerificationRequest();
      if (res && res.success) {
        // Update user state in auth context to pending
        if (setUser && res.member) {
          setUser((prev) => ({
            ...prev,
            ...res.member,
            is_verified: false,
            is_verification_pending: true,
            verification_status: 'pending',
            mobile_verification_requested_at: res.mobile_verification_requested_at || new Date().toISOString(),
          }));
        }
        if (refreshUser) {
          refreshUser();
        }
        setStep('pending');
      } else {
        setError(res?.message || 'Failed to submit verification request. Please try again.');
      }
    } catch (err) {
      let msg = err.response?.data?.errors?.request?.[0]
        || err.response?.data?.errors?.phone?.[0]
        || err.response?.data?.message
        || 'Failed to submit verification request. Please try again.';

      if (
        typeof msg !== 'string' ||
        msg.includes('SQLSTATE') ||
        msg.includes('QueryException') ||
        msg.includes('Integrity constraint') ||
        msg.includes('Duplicate entry')
      ) {
        msg = 'Failed to submit verification request. Please try again later.';
      }

      setError(msg);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <ModalPortal isOpen={isOpen} onClose={onClose} depth={1}>
      <div
        className="card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="verification-modal-title"
        style={{
          maxWidth: '480px',
          width: '100%',
          maxHeight: 'min(90vh, 760px)',
          overflowY: 'auto',
          borderRadius: '24px',
          backgroundColor: '#ffffff',
          boxShadow: '0 25px 50px -12px rgba(15, 23, 42, 0.35)',
          border: '1px solid rgba(226, 232, 240, 0.8)',
          position: 'relative',
          animation: 'modalSlideUp 0.25s ease-out forwards',
        }}
        onClick={(e) => e.stopPropagation()}
      >
        {/* Close Button */}
        <button
          type="button"
          onClick={onClose}
          aria-label="Close verification modal"
          style={{
            position: 'absolute',
            top: '18px',
            right: '18px',
            width: '36px',
            height: '36px',
            borderRadius: '50%',
            border: 'none',
            background: '#f1f5f9',
            color: '#64748b',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            cursor: 'pointer',
            zIndex: 10,
            transition: 'all 0.15s ease',
          }}
        >
          <X size={18} />
        </button>

        {/* Modal Header Strip */}
        <div
          style={{
            background: step === 'pending'
              ? 'linear-gradient(135deg, #d97706 0%, #f59e0b 100%)'
              : 'linear-gradient(135deg, #059669 0%, #10b981 100%)',
            padding: '30px 24px 24px',
            borderRadius: '24px 24px 0 0',
            color: '#ffffff',
            textAlign: 'center',
            transition: 'background 0.3s ease',
          }}
        >
          <div
            style={{
              width: '64px',
              height: '64px',
              borderRadius: '20px',
              background: 'rgba(255, 255, 255, 0.2)',
              backdropFilter: 'blur(8px)',
              margin: '0 auto 14px',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              boxShadow: '0 8px 16px rgba(0, 0, 0, 0.1)',
            }}
          >
            {step === 'success' ? (
              <CheckCircle2 size={36} color="#ffffff" />
            ) : step === 'pending' ? (
              <Clock size={36} color="#ffffff" />
            ) : step === 'send_hi' ? (
              <MessageSquare size={34} color="#ffffff" />
            ) : (
              <ShieldCheck size={36} color="#ffffff" />
            )}
          </div>

          <h2
            id="verification-modal-title"
            style={{ fontSize: '20px', fontWeight: 800, margin: '0 0 6px 0', color: '#ffffff' }}
          >
            {step === 'success'
              ? 'Account Verified'
              : step === 'pending'
              ? 'Verification Pending'
              : step === 'send_hi'
              ? 'Send WhatsApp "Hi"'
              : 'Verify Your Account'}
          </h2>
          <p style={{ margin: 0, fontSize: '13px', color: 'rgba(255, 255, 255, 0.9)', lineHeight: 1.4 }}>
            {step === 'success'
              ? 'Your verified member status is active!'
              : step === 'pending'
              ? 'Your verification request is awaiting admin approval.'
              : step === 'send_hi'
              ? 'Send "Hi" to our official WhatsApp number from your registered phone.'
              : 'Verify your WhatsApp number to unlock eligible earning actions.'}
          </p>
        </div>

        {/* Modal Body */}
        <div style={{ padding: '24px' }}>
          {/* Dynamic Error Alert - strictly suppressed during success step */}
          {step !== 'success' && error && error !== 'Please verify your phone number first before proceeding.' && (
            <div
              style={{
                display: 'flex',
                alignItems: 'center',
                gap: '10px',
                padding: '10px 14px',
                borderRadius: '12px',
                background: '#fff1f2',
                border: '1px solid #fda4af',
                color: '#be123c',
                fontSize: '13px',
                marginBottom: '16px',
              }}
            >
              <AlertCircle size={16} style={{ flexShrink: 0 }} />
              <div style={{ flex: 1 }}>{error}</div>
            </div>
          )}

          {/* STEP 1: Registered Number Confirmation */}
          {step === 'registered_phone' && (
            <div>
              {/* Context Prompt: Action that triggered verification requirement */}
              {promptMessage && (
                <div
                  style={{
                    background: '#eff6ff',
                    border: '1px solid #bfdbfe',
                    borderRadius: '14px',
                    padding: '12px 16px',
                    marginBottom: '16px',
                    display: 'flex',
                    alignItems: 'flex-start',
                    gap: '10px',
                    color: '#1e40af',
                    fontSize: '13px',
                    lineHeight: 1.45,
                  }}
                >
                  <Lock size={16} color="#2563eb" style={{ flexShrink: 0, marginTop: '2px' }} />
                  <div style={{ flex: 1 }}>
                    <strong style={{ display: 'block', fontSize: '13px', color: '#1e3a8a', marginBottom: '2px' }}>
                      Action Requires Verification
                    </strong>
                    <span>{promptMessage}</span>
                  </div>
                </div>
              )}

              {/* Alert: Why verification is required */}
              <div
                style={{
                  background: '#fef2f2',
                  border: '1px solid #fecaca',
                  borderRadius: '14px',
                  padding: '14px 16px',
                  marginBottom: '16px',
                }}
              >
                <div style={{ display: 'flex', alignItems: 'flex-start', gap: '10px' }}>
                  <AlertCircle size={18} color="#dc2626" style={{ flexShrink: 0, marginTop: '2px' }} />
                  <div style={{ flex: 1 }}>
                    <strong style={{ display: 'block', fontSize: '13px', color: '#991b1b', marginBottom: '4px', lineHeight: 1.4 }}>
                      Phone verification is required before proceeding.
                    </strong>
                    <p style={{ margin: '0 0 6px 0', fontSize: '12.5px', color: '#b91c1c', lineHeight: 1.45 }}>
                      Verification is required to unlock earning actions and continue with reward-eligible activities.
                    </p>
                    <p style={{ margin: 0, fontSize: '12px', color: '#047857', fontWeight: 600, lineHeight: 1.4 }}>
                      No OTP needed. Simply send "Hi" to our official WhatsApp number to verify.
                    </p>
                  </div>
                </div>
              </div>

              {/* Registered Phone Display Card (Masked, Non-Editable) */}
              <div
                style={{
                  background: '#f8fafc',
                  border: '1.5px solid #e2e8f0',
                  borderRadius: '14px',
                  padding: '16px',
                  marginBottom: '18px',
                }}
              >
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '8px' }}>
                  <span style={{ fontSize: '12px', fontWeight: 700, color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
                    Registered WhatsApp Number
                  </span>
                  <span
                    style={{
                      display: 'inline-flex',
                      alignItems: 'center',
                      gap: '4px',
                      fontSize: '11px',
                      fontWeight: 700,
                      color: '#059669',
                      background: '#ecfdf5',
                      padding: '2px 8px',
                      borderRadius: '999px',
                      border: '1px solid #a7f3d0',
                    }}
                  >
                    <Lock size={11} />
                    <span>Registered Phone</span>
                  </span>
                </div>

                <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                  <div
                    style={{
                      width: '38px',
                      height: '38px',
                      borderRadius: '10px',
                      background: '#e0f2fe',
                      color: '#0284c7',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      flexShrink: 0,
                    }}
                  >
                    <MessageSquare size={20} />
                  </div>
                  <div>
                    <strong style={{ fontSize: '16px', color: '#0f172a', letterSpacing: '0.5px', display: 'block' }}>
                      {maskedPhone || user?.phone || 'No phone on profile'}
                    </strong>
                    <span style={{ fontSize: '12px', color: '#64748b' }}>
                      Verified via Email OTP during registration
                    </span>
                  </div>
                </div>

                <div style={{ marginTop: '10px', paddingTop: '10px', borderTop: '1px dashed #cbd5e1', fontSize: '11.5px', color: '#64748b', lineHeight: 1.4 }}>
                  🔒 For your security, you do not need to enter another number. We verify using this registered WhatsApp number.
                </div>
              </div>

              {/* Verified Member Benefits */}
              <div
                style={{
                  background: '#f0fdf4',
                  border: '1px solid #bbf7d0',
                  borderRadius: '14px',
                  padding: '14px 16px',
                  marginBottom: '20px',
                }}
              >
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '10px' }}>
                  <Sparkles size={16} color="#059669" />
                  <strong style={{ fontSize: '13.5px', color: '#065f46' }}>Verified Member Benefits</strong>
                </div>
                <ul
                  style={{
                    margin: 0,
                    padding: 0,
                    listStyle: 'none',
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '8px',
                  }}
                >
                  <li style={{ display: 'flex', alignItems: 'flex-start', gap: '8px', fontSize: '12.5px', color: '#047857', lineHeight: 1.45 }}>
                    <CheckCircle2 size={15} color="#059669" style={{ flexShrink: 0, marginTop: '2px' }} />
                    <span>Unlock eligible earning actions on paid ads and paid events</span>
                  </li>
                  <li style={{ display: 'flex', alignItems: 'flex-start', gap: '8px', fontSize: '12.5px', color: '#047857', lineHeight: 1.45 }}>
                    <CheckCircle2 size={15} color="#059669" style={{ flexShrink: 0, marginTop: '2px' }} />
                    <span>Receive rewards after completing eligible actions</span>
                  </li>
                  <li style={{ display: 'flex', alignItems: 'flex-start', gap: '8px', fontSize: '12.5px', color: '#047857', lineHeight: 1.45 }}>
                    <CheckCircle2 size={15} color="#059669" style={{ flexShrink: 0, marginTop: '2px' }} />
                    <span>Official Green Verified Tick badge on your profile and posts</span>
                  </li>
                  <li style={{ display: 'flex', alignItems: 'flex-start', gap: '8px', fontSize: '12.5px', color: '#047857', lineHeight: 1.45 }}>
                    <CheckCircle2 size={15} color="#059669" style={{ flexShrink: 0, marginTop: '2px' }} />
                    <span>Build greater trust and credibility within the community</span>
                  </li>
                </ul>
              </div>

              {/* Action Button: Proceed to WhatsApp */}
              <button
                type="button"
                className="member-button member-button--primary"
                onClick={handleProceedToWhatsApp}
                disabled={isLoading || (!maskedPhone && !user?.phone)}
                style={{
                  width: '100%',
                  padding: '13px',
                  borderRadius: '12px',
                  background: 'linear-gradient(135deg, #059669 0%, #10b981 100%)',
                  color: '#ffffff',
                  fontSize: '14.5px',
                  fontWeight: 700,
                  justifyContent: 'center',
                  gap: '8px',
                  border: 'none',
                  cursor: isLoading ? 'not-allowed' : 'pointer',
                  boxShadow: '0 4px 12px rgba(16, 185, 129, 0.25)',
                }}
              >
                <span>{isLoading ? 'Preparing WhatsApp Link...' : 'Continue to WhatsApp Verification'}</span>
                {!isLoading && <ArrowRight size={16} />}
              </button>
            </div>
          )}

          {/* STEP 2: Send WhatsApp "Hi" */}
          {step === 'send_hi' && (
            <div>
              {/* Instructions Box */}
              <div
                style={{
                  background: '#f8fafc',
                  border: '1px solid #e2e8f0',
                  borderRadius: '16px',
                  padding: '18px 16px',
                  marginBottom: '20px',
                }}
              >
                <h4 style={{ fontSize: '14px', fontWeight: 800, color: '#0f172a', margin: '0 0 12px 0' }}>
                  Verification Instructions:
                </h4>

                <div style={{ display: 'flex', flexDirection: 'column', gap: '14px' }}>
                  {/* Instruction Step 1 */}
                  <div style={{ display: 'flex', alignItems: 'flex-start', gap: '10px' }}>
                    <div
                      style={{
                        width: '24px',
                        height: '24px',
                        borderRadius: '50%',
                        background: '#059669',
                        color: '#ffffff',
                        fontSize: '12px',
                        fontWeight: 800,
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        flexShrink: 0,
                        marginTop: '1px',
                      }}
                    >
                      1
                    </div>
                    <div style={{ fontSize: '13px', color: '#334155', lineHeight: 1.45 }}>
                      Tap <strong>"Open WhatsApp"</strong> below to open our official WhatsApp chat with <strong>"Hi"</strong> pre-filled.
                    </div>
                  </div>

                  {/* Instruction Step 2 */}
                  <div style={{ display: 'flex', alignItems: 'flex-start', gap: '10px' }}>
                    <div
                      style={{
                        width: '24px',
                        height: '24px',
                        borderRadius: '50%',
                        background: '#059669',
                        color: '#ffffff',
                        fontSize: '12px',
                        fontWeight: 800,
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        flexShrink: 0,
                        marginTop: '1px',
                      }}
                    >
                      2
                    </div>
                    <div style={{ fontSize: '13px', color: '#334155', lineHeight: 1.45 }}>
                      Send the message <strong>"Hi"</strong> from your registered number (<strong>{maskedPhone || user?.phone}</strong>).
                    </div>
                  </div>

                  {/* Instruction Step 3 */}
                  <div style={{ display: 'flex', alignItems: 'flex-start', gap: '10px' }}>
                    <div
                      style={{
                        width: '24px',
                        height: '24px',
                        borderRadius: '50%',
                        background: '#059669',
                        color: '#ffffff',
                        fontSize: '12px',
                        fontWeight: 800,
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        flexShrink: 0,
                        marginTop: '1px',
                      }}
                    >
                      3
                    </div>
                    <div style={{ fontSize: '13px', color: '#334155', lineHeight: 1.45 }}>
                      Return here and tap <strong>"I have sent Hi"</strong> to submit your verification request for admin approval.
                    </div>
                  </div>
                </div>
              </div>

              {/* Action 1: Open WhatsApp Deep Link */}
              <button
                type="button"
                onClick={handleOpenWhatsApp}
                style={{
                  width: '100%',
                  padding: '13px',
                  borderRadius: '12px',
                  background: '#25D366',
                  color: '#ffffff',
                  fontSize: '14.5px',
                  fontWeight: 700,
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  gap: '8px',
                  border: 'none',
                  cursor: 'pointer',
                  marginBottom: '12px',
                  boxShadow: '0 4px 12px rgba(37, 211, 102, 0.3)',
                  transition: 'background 0.15s ease',
                }}
              >
                <MessageSquare size={18} />
                <span>Open WhatsApp (Pre-filled "Hi")</span>
                <ExternalLink size={15} />
              </button>

              {/* Action 2: I Have Sent Hi Button */}
              <button
                type="button"
                onClick={handleSubmitSentHi}
                disabled={isLoading}
                style={{
                  width: '100%',
                  padding: '13px',
                  borderRadius: '12px',
                  background: 'linear-gradient(135deg, #059669 0%, #10b981 100%)',
                  color: '#ffffff',
                  fontSize: '14.5px',
                  fontWeight: 700,
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  gap: '8px',
                  border: 'none',
                  cursor: isLoading ? 'not-allowed' : 'pointer',
                  boxShadow: '0 4px 12px rgba(16, 185, 129, 0.25)',
                }}
              >
                <CheckCircle2 size={18} />
                <span>{isLoading ? 'Submitting Request...' : 'I have sent Hi'}</span>
              </button>

              {/* Back to Step 1 */}
              <div style={{ textAlign: 'center', marginTop: '16px' }}>
                <button
                  type="button"
                  onClick={() => setStep('registered_phone')}
                  style={{
                    background: 'none',
                    border: 'none',
                    color: '#64748b',
                    fontSize: '12.5px',
                    cursor: 'pointer',
                    padding: 0,
                  }}
                >
                  ← Back to phone details
                </button>
              </div>
            </div>
          )}

          {/* STEP 3: Verification Request Pending */}
          {step === 'pending' && (
            <div style={{ textAlign: 'center', padding: '6px 0' }}>
              <div
                style={{
                  display: 'inline-flex',
                  alignItems: 'center',
                  gap: '8px',
                  background: '#fef3c7',
                  border: '1px solid #fde68a',
                  padding: '8px 16px',
                  borderRadius: '999px',
                  color: '#b45309',
                  fontWeight: 700,
                  fontSize: '13.5px',
                  marginBottom: '16px',
                }}
              >
                <Clock size={16} color="#d97706" />
                <span>Pending Admin Approval</span>
              </div>

              <h3 style={{ fontSize: '18px', fontWeight: 800, color: '#0f172a', margin: '0 0 10px 0' }}>
                Verification Request Submitted!
              </h3>

              <p style={{ fontSize: '13px', color: '#64748b', margin: '0 0 16px 0', lineHeight: 1.55 }}>
                We have received your verification request for <strong>{maskedPhone || user?.phone}</strong>.
                Our team is reviewing your WhatsApp "Hi" message to manually verify your account.
              </p>

              <div
                style={{
                  background: '#fffbeb',
                  border: '1px solid #fde68a',
                  borderRadius: '12px',
                  padding: '14px',
                  marginBottom: '20px',
                  textAlign: 'left',
                  fontSize: '12.5px',
                  color: '#92400e',
                  lineHeight: 1.45,
                }}
              >
                <div style={{ display: 'flex', gap: '8px', alignItems: 'flex-start' }}>
                  <ShieldAlert size={16} color="#d97706" style={{ flexShrink: 0, marginTop: '2px' }} />
                  <div>
                    <strong>What happens next?</strong>
                    <div style={{ marginTop: '4px' }}>
                      Once an administrator reviews your WhatsApp message, your official <strong>Green Verified Tick</strong> badge will be activated automatically.
                    </div>
                  </div>
                </div>
              </div>

              {/* Action: Re-open WhatsApp if they forgot to send */}
              <button
                type="button"
                onClick={handleOpenWhatsApp}
                style={{
                  width: '100%',
                  padding: '11px',
                  borderRadius: '12px',
                  background: '#f1f5f9',
                  color: '#334155',
                  fontSize: '13px',
                  fontWeight: 600,
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  gap: '6px',
                  border: '1px solid #cbd5e1',
                  cursor: 'pointer',
                  marginBottom: '10px',
                }}
              >
                <MessageSquare size={15} />
                <span>Open WhatsApp Again</span>
                <ExternalLink size={13} />
              </button>

              {/* Done button */}
              <button
                type="button"
                className="member-button member-button--primary"
                onClick={onClose}
                style={{
                  width: '100%',
                  padding: '12px',
                  borderRadius: '12px',
                  background: 'linear-gradient(135deg, #059669 0%, #10b981 100%)',
                  color: '#ffffff',
                  fontSize: '14px',
                  fontWeight: 700,
                  justifyContent: 'center',
                  border: 'none',
                  cursor: 'pointer',
                }}
              >
                Done
              </button>
            </div>
          )}

          {/* STEP 4: Verification Success (Already Verified) */}
          {step === 'success' && (
            <div style={{ textAlign: 'center', padding: '10px 0' }}>
              <div
                style={{
                  display: 'inline-flex',
                  alignItems: 'center',
                  gap: '8px',
                  background: '#f0fdf4',
                  border: '1px solid #bbf7d0',
                  padding: '8px 16px',
                  borderRadius: '999px',
                  color: '#047857',
                  fontWeight: 700,
                  fontSize: '14px',
                  marginBottom: '16px',
                }}
              >
                <CheckCircle2 size={18} color="#10b981" />
                <span>Green Tick Active</span>
              </div>

              <h3 style={{ fontSize: '18px', fontWeight: 800, color: '#0f172a', margin: '0 0 8px 0' }}>
                You are a Verified Member!
              </h3>
              <p style={{ fontSize: '13px', color: '#64748b', margin: '0 0 16px 0', lineHeight: 1.5 }}>
                Your mobile number <strong>{maskedPhone || user?.phone}</strong> is verified on WhatsApp. The official green tick badge is now active on your account.
              </p>

              <div
                style={{
                  background: '#ecfdf5',
                  border: '1px solid #a7f3d0',
                  borderRadius: '12px',
                  padding: '12px 16px',
                  marginBottom: '20px',
                  fontSize: '12.5px',
                  color: '#065f46',
                  lineHeight: 1.45,
                  fontWeight: 600,
                  textAlign: 'left',
                }}
              >
                Phone verification complete! You can now continue with the existing earning flow.
              </div>

              <button
                type="button"
                className="member-button member-button--primary"
                onClick={() => {
                  onVerified?.(activeMember);
                  onClose();
                }}
                style={{
                  width: '100%',
                  padding: '12px',
                  borderRadius: '12px',
                  background: 'linear-gradient(135deg, #059669 0%, #10b981 100%)',
                  color: '#ffffff',
                  fontSize: '14px',
                  fontWeight: 700,
                  justifyContent: 'center',
                  border: 'none',
                  cursor: 'pointer',
                }}
              >
                Done
              </button>
            </div>
          )}
        </div>
      </div>
    </ModalPortal>
  );
}

export default AccountVerificationModal;
