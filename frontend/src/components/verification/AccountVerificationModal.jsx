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
  Loader2,
  Edit3,
  PhoneForwarded,
  XCircle,
} from 'lucide-react';
import verificationApi from '../../api/verificationApi';
import useAuth from '../../hooks/useAuth';
import { ModalPortal } from '../common/ModalPortal';
import { resolveWhatsAppVerificationUrl, isMemberMobileVerified } from '../../utils/whatsappVerification';

export function AccountVerificationModal({ isOpen, onClose, onVerified, initialError = null, promptMessage = null }) {
  const { user, setUser, refreshUser } = useAuth();

  // Steps: 
  // 'registered_phone' | 'send_hi' | 'pending' | 'success' 
  // | 'change_number_form' | 'change_number_whatsapp' | 'change_number_pending'
  const [step, setStep] = useState('registered_phone');
  const [maskedPhone, setMaskedPhone] = useState('');
  const [rawPhone, setRawPhone] = useState('');
  const [whatsappUrl, setWhatsappUrl] = useState('');
  const [whatsappDestination, setWhatsappDestination] = useState('');
  const [serverMember, setServerMember] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(initialError || null);
  const activeMember = serverMember || user;

  // Unverified edit state
  const [isEditingPhone, setIsEditingPhone] = useState(false);
  const [editPhoneInput, setEditPhoneInput] = useState('');
  const [isSubmittingPhone, setIsSubmittingPhone] = useState(false);

  // Phone change request state
  const [newPhoneInput, setNewPhoneInput] = useState('');
  const [pendingChangeRequest, setPendingChangeRequest] = useState(null);
  const [isSubmittingChangeRequest, setIsSubmittingChangeRequest] = useState(false);
  const [isConfirmingWhatsApp, setIsConfirmingWhatsApp] = useState(false);
  const [isCancellingChange, setIsCancellingChange] = useState(false);

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
      setIsEditingPhone(false);

      const rPhone = user?.phone || '';
      if (rPhone) {
        setRawPhone(rPhone);
        setMaskedPhone(formatMaskedPhone(rPhone));
        setEditPhoneInput(rPhone);
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

          if (res.phone) {
            setRawPhone(res.phone);
            setEditPhoneInput(res.phone);
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

          // Check if there is an active pending phone change request
          if (res.has_pending_phone_change && res.pending_phone_change_request) {
            setPendingChangeRequest(res.pending_phone_change_request);
            setStep('change_number_pending');
            return;
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

  // Save unverified phone update
  const handleSaveUnverifiedPhone = async (e) => {
    e?.preventDefault();
    if (!editPhoneInput || editPhoneInput.trim().length < 7) {
      setError('Please enter a valid phone number with country code (e.g. +91 9876543210).');
      return;
    }

    setIsSubmittingPhone(true);
    setError(null);

    try {
      const res = await verificationApi.updateUnverifiedPhone(editPhoneInput.trim());
      if (res && res.success) {
        setRawPhone(res.phone);
        setMaskedPhone(res.masked_phone || formatMaskedPhone(res.phone));
        if (res.whatsapp_url) {
          setWhatsappUrl(res.whatsapp_url);
        }
        if (setUser && res.member) {
          setUser((prev) => ({
            ...prev,
            ...res.member,
          }));
        }
        setIsEditingPhone(false);
      } else {
        setError(res?.message || 'Failed to update phone number.');
      }
    } catch (err) {
      const msg = err.response?.data?.errors?.phone?.[0]
        || err.response?.data?.message
        || 'Failed to update phone number. Please check format.';
      setError(msg);
    } finally {
      setIsSubmittingPhone(false);
    }
  };

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
  const handleOpenWhatsApp = (customUrl = null) => {
    const targetUrl = customUrl || resolveWhatsAppVerificationUrl(whatsappUrl, whatsappDestination, activeMember);
    window.open(targetUrl, '_blank', 'noopener,noreferrer');
  };

  // Step 2 -> Step 3: Member submits that they sent "Hi"
  const handleSubmitSentHi = async () => {
    setIsLoading(true);
    setError(null);

    try {
      const res = await verificationApi.submitVerificationRequest();
      if (res && res.success) {
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

  // Submit phone change request
  const handleSubmitChangeRequest = async (e) => {
    e?.preventDefault();
    if (!newPhoneInput || newPhoneInput.trim().length < 7) {
      setError('Please enter a valid new phone number with country code (e.g. +91 9876543210).');
      return;
    }

    setIsSubmittingChangeRequest(true);
    setError(null);

    try {
      const res = await verificationApi.requestPhoneChange(newPhoneInput.trim());
      if (res && res.success) {
        setPendingChangeRequest(res.change_request);
        if (res.whatsapp_url) {
          setWhatsappUrl(res.whatsapp_url);
        }
        setStep('change_number_whatsapp');
      } else {
        setError(res?.message || 'Failed to submit change request.');
      }
    } catch (err) {
      const msg = err.response?.data?.errors?.new_phone?.[0]
        || err.response?.data?.message
        || 'Failed to submit phone change request.';
      setError(msg);
    } finally {
      setIsSubmittingChangeRequest(false);
    }
  };

  // Confirm sent WhatsApp for change request
  const handleConfirmChangeWhatsApp = async () => {
    if (!pendingChangeRequest?.id) return;
    setIsConfirmingWhatsApp(true);
    setError(null);

    try {
      const res = await verificationApi.confirmPhoneChangeWhatsApp(pendingChangeRequest.id);
      if (res && res.success) {
        setPendingChangeRequest(res.change_request);
        setStep('change_number_pending');
      } else {
        setError(res?.message || 'Failed to confirm WhatsApp request.');
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to confirm WhatsApp request.');
    } finally {
      setIsConfirmingWhatsApp(false);
    }
  };

  // Cancel pending change request
  const handleCancelChangeRequest = async () => {
    if (!pendingChangeRequest?.id) return;
    if (!window.confirm('Are you sure you want to cancel this phone number change request?')) {
      return;
    }

    setIsCancellingChange(true);
    setError(null);

    try {
      const res = await verificationApi.cancelPhoneChangeRequest(pendingChangeRequest.id);
      if (res && res.success) {
        setPendingChangeRequest(null);
        setStep('success');
      } else {
        setError(res?.message || 'Failed to cancel change request.');
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to cancel change request.');
    } finally {
      setIsCancellingChange(false);
    }
  };

  const isPendingStep = step === 'pending' || step === 'change_number_pending';

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
            background: isPendingStep
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
            ) : isPendingStep ? (
              <Clock size={36} color="#ffffff" />
            ) : step === 'send_hi' || step === 'change_number_whatsapp' ? (
              <MessageSquare size={34} color="#ffffff" />
            ) : step === 'change_number_form' ? (
              <PhoneForwarded size={34} color="#ffffff" />
            ) : (
              <ShieldCheck size={36} color="#ffffff" />
            )}
          </div>

          <h2
            id="verification-modal-title"
            style={{
              margin: '0 0 6px 0',
              fontSize: '20px',
              fontWeight: 800,
              color: '#ffffff',
              letterSpacing: '-0.3px',
            }}
          >
            {step === 'success'
              ? 'Account Fully Verified'
              : step === 'change_number_pending'
              ? 'Change Request Pending'
              : step === 'change_number_form'
              ? 'Change Phone Number'
              : step === 'change_number_whatsapp'
              ? 'Send Change Request'
              : step === 'pending'
              ? 'Verification Pending'
              : step === 'send_hi'
              ? 'Send WhatsApp Message'
              : 'Verify WhatsApp Number'}
          </h2>

          <p
            style={{
              margin: 0,
              fontSize: '13px',
              color: 'rgba(255, 255, 255, 0.9)',
              lineHeight: 1.45,
            }}
          >
            {step === 'success'
              ? 'Your mobile number is verified. Green tick badge is active.'
              : step === 'change_number_pending'
              ? 'Your number change request is under administrator review.'
              : step === 'change_number_form'
              ? 'Request a change of your verified WhatsApp number.'
              : step === 'change_number_whatsapp'
              ? 'Send verification message from your new mobile number.'
              : step === 'pending'
              ? 'Your verification request has been submitted for admin review.'
              : step === 'send_hi'
              ? 'Send "Hi" to our official number to verify your identity.'
              : 'Verify your WhatsApp mobile number to unlock verified benefits.'}
          </p>
        </div>

        {/* Modal Body */}
        <div style={{ padding: '24px' }}>
          {error && (
            <div
              style={{
                display: 'flex',
                alignItems: 'flex-start',
                gap: '10px',
                padding: '12px 14px',
                borderRadius: '12px',
                background: '#fef2f2',
                border: '1px solid #fecaca',
                color: '#b91c1c',
                fontSize: '13px',
                marginBottom: '16px',
                lineHeight: 1.4,
              }}
            >
              <AlertCircle size={18} color="#dc2626" style={{ flexShrink: 0, marginTop: '2px' }} />
              <div style={{ flex: 1 }}>{error}</div>
            </div>
          )}

          {/* STEP 1: REGISTERED PHONE OVERVIEW (UNVERIFIED) */}
          {step === 'registered_phone' && (
            <div>
              {promptMessage && (
                <div
                  style={{
                    background: '#eff6ff',
                    border: '1px solid #bfdbfe',
                    borderRadius: '12px',
                    padding: '12px 14px',
                    marginBottom: '16px',
                    display: 'flex',
                    alignItems: 'flex-start',
                    gap: '10px',
                    fontSize: '12.5px',
                    color: '#1d4ed8',
                    lineHeight: 1.4,
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

              {/* Registered Phone Display Card with Edit Toggle */}
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
                    WhatsApp Number to Verify
                  </span>
                  <button
                    type="button"
                    onClick={() => {
                      setError(null);
                      setIsEditingPhone(!isEditingPhone);
                      setEditPhoneInput(rawPhone || user?.phone || '');
                    }}
                    style={{
                      display: 'inline-flex',
                      alignItems: 'center',
                      gap: '4px',
                      fontSize: '11.5px',
                      fontWeight: 700,
                      color: '#2563eb',
                      background: '#eff6ff',
                      border: '1px solid #bfdbfe',
                      padding: '3px 10px',
                      borderRadius: '999px',
                      cursor: 'pointer',
                    }}
                  >
                    <Edit3 size={11} />
                    <span>{isEditingPhone ? 'Cancel Edit' : 'Edit Number'}</span>
                  </button>
                </div>

                {isEditingPhone ? (
                  <form onSubmit={handleSaveUnverifiedPhone} style={{ marginTop: '8px' }}>
                    <input
                      type="tel"
                      value={editPhoneInput}
                      onChange={(e) => setEditPhoneInput(e.target.value)}
                      placeholder="+91 9876543210"
                      required
                      style={{
                        width: '100%',
                        padding: '10px 12px',
                        borderRadius: '8px',
                        border: '1.5px solid #cbd5e1',
                        fontSize: '14px',
                        boxSizing: 'border-box',
                        marginBottom: '8px',
                      }}
                    />
                    <div style={{ display: 'flex', gap: '8px', justifyContent: 'flex-end' }}>
                      <button
                        type="button"
                        onClick={() => setIsEditingPhone(false)}
                        className="member-button member-button--secondary"
                        style={{ padding: '6px 12px', fontSize: '12px' }}
                      >
                        Cancel
                      </button>
                      <button
                        type="submit"
                        disabled={isSubmittingPhone || !editPhoneInput.trim()}
                        className="member-button member-button--primary"
                        style={{ padding: '6px 12px', fontSize: '12px', gap: '4px' }}
                      >
                        {isSubmittingPhone ? (
                          <>
                            <Loader2 size={12} className="animate-spin" />
                            <span>Saving...</span>
                          </>
                        ) : (
                          <>
                            <CheckCircle2 size={12} />
                            <span>Save</span>
                          </>
                        )}
                      </button>
                    </div>
                  </form>
                ) : (
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
                        {maskedPhone || rawPhone || user?.phone || 'No phone registered'}
                      </strong>
                      <span style={{ fontSize: '12px', color: '#64748b' }}>
                        No OTP needed. Simply send "Hi" on WhatsApp to verify.
                      </span>
                    </div>
                  </div>
                )}
              </div>

              {/* Verified Benefits Card */}
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
                    fontSize: '12.5px',
                    color: '#166534',
                  }}
                >
                  <li style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                    <CheckCircle2 size={15} color="#10b981" />
                    <span>Official Green Verified Tick badge on your profile and posts</span>
                  </li>
                  <li style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                    <CheckCircle2 size={15} color="#10b981" />
                    <span>Full access to Ad Earning, Referral Rewards, and P2P transfers</span>
                  </li>
                  <li style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                    <CheckCircle2 size={15} color="#10b981" />
                    <span>Protected account identity and verified badge permanence</span>
                  </li>
                </ul>
              </div>

              {/* Action Button: Proceed to WhatsApp */}
              <button
                type="button"
                className="member-button member-button--primary"
                onClick={handleProceedToWhatsApp}
                disabled={isLoading || (!maskedPhone && !rawPhone)}
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
                }}
              >
                {isLoading ? (
                  <>
                    <Loader2 size={16} className="animate-spin" />
                    <span>Preparing WhatsApp Link...</span>
                  </>
                ) : (
                  <>
                    <span>Continue to WhatsApp Verification</span>
                    <ArrowRight size={16} />
                  </>
                )}
              </button>
            </div>
          )}

          {/* STEP 2: SEND "HI" INSTRUCTIONS */}
          {step === 'send_hi' && (
            <div>
              <div
                style={{
                  background: '#f8fafc',
                  border: '1px solid #e2e8f0',
                  borderRadius: '14px',
                  padding: '16px',
                  marginBottom: '18px',
                }}
              >
                <ol style={{ margin: 0, paddingLeft: '18px', fontSize: '13px', color: '#334155', lineHeight: 1.6 }}>
                  <li>Tap <strong>"Open WhatsApp"</strong> below. A chat will open with <strong>"Hi"</strong> pre-filled.</li>
                  <li>Send <strong>"Hi"</strong> from your registered number (<strong>{maskedPhone || rawPhone}</strong>).</li>
                  <li>Return here and tap <strong>"I have sent Hi"</strong>.</li>
                </ol>
              </div>

              <button
                type="button"
                onClick={() => handleOpenWhatsApp()}
                style={{
                  width: '100%',
                  padding: '13px',
                  borderRadius: '12px',
                  background: '#25D366',
                  color: '#ffffff',
                  fontSize: '14px',
                  fontWeight: 700,
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  gap: '8px',
                  border: 'none',
                  cursor: 'pointer',
                  marginBottom: '12px',
                }}
              >
                <MessageSquare size={17} />
                <span>Open WhatsApp (Pre-filled "Hi")</span>
                <ExternalLink size={14} />
              </button>

              <button
                type="button"
                onClick={handleSubmitSentHi}
                disabled={isLoading}
                className="member-button member-button--primary"
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
                }}
              >
                {isLoading ? (
                  <>
                    <Loader2 size={16} className="animate-spin" />
                    <span>Submitting Request...</span>
                  </>
                ) : (
                  <>
                    <CheckCircle2 size={16} />
                    <span>I have sent Hi</span>
                  </>
                )}
              </button>

              <div style={{ textAlign: 'center', marginTop: '12px' }}>
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

          {/* STEP 3: INITIAL VERIFICATION PENDING REVIEW */}
          {step === 'pending' && (
            <div>
              <div
                style={{
                  background: '#fffbeb',
                  border: '1px solid #fde68a',
                  borderRadius: '14px',
                  padding: '16px',
                  marginBottom: '18px',
                }}
              >
                <strong style={{ display: 'block', fontSize: '13.5px', color: '#92400e', marginBottom: '4px' }}>
                  Verification Request Under Review
                </strong>
                <p style={{ margin: 0, fontSize: '12.5px', color: '#78350f', lineHeight: 1.5 }}>
                  We received your request from <strong>{maskedPhone || rawPhone}</strong>. Our administrators
                  review each incoming WhatsApp message to maintain network authenticity.
                </p>
              </div>

              <button
                type="button"
                onClick={() => handleOpenWhatsApp()}
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

          {/* STEP 4: CHANGE NUMBER FORM (VERIFIED MEMBER) */}
          {step === 'change_number_form' && (
            <div>
              <div
                style={{
                  background: '#f0fdf4',
                  border: '1px solid #bbf7d0',
                  borderRadius: '12px',
                  padding: '12px 16px',
                  marginBottom: '16px',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                }}
              >
                <div>
                  <span style={{ fontSize: '11px', color: '#166534', fontWeight: 700, display: 'block' }}>
                    CURRENT ACTIVE NUMBER
                  </span>
                  <strong style={{ fontSize: '14px', color: '#14532d' }}>
                    {maskedPhone || rawPhone || user?.phone}
                  </strong>
                </div>
                <span
                  style={{
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: '4px',
                    fontSize: '11px',
                    fontWeight: 700,
                    color: '#059669',
                    background: '#ffffff',
                    padding: '2px 8px',
                    borderRadius: '999px',
                    border: '1px solid #a7f3d0',
                  }}
                >
                  <CheckCircle2 size={11} /> Verified
                </span>
              </div>

              <form onSubmit={handleSubmitChangeRequest}>
                <div style={{ marginBottom: '16px' }}>
                  <label
                    htmlFor="modal-new-phone-input"
                    style={{ display: 'block', fontSize: '13px', fontWeight: 700, color: '#334155', marginBottom: '6px' }}
                  >
                    New WhatsApp Mobile Number
                  </label>
                  <input
                    id="modal-new-phone-input"
                    type="tel"
                    value={newPhoneInput}
                    onChange={(e) => setNewPhoneInput(e.target.value)}
                    placeholder="+91 9876543210"
                    required
                    style={{
                      width: '100%',
                      padding: '11px 14px',
                      borderRadius: '10px',
                      border: '1.5px solid #cbd5e1',
                      fontSize: '14px',
                      boxSizing: 'border-box',
                    }}
                  />
                  <span style={{ fontSize: '11.5px', color: '#64748b', display: 'block', marginTop: '5px' }}>
                    Include country code (e.g. +91 for India, +1 for US/Canada).
                  </span>
                </div>

                <div style={{ display: 'flex', gap: '10px', justifyContent: 'flex-end' }}>
                  <button
                    type="button"
                    onClick={() => {
                      setError(null);
                      setStep('success');
                    }}
                    disabled={isSubmittingChangeRequest}
                    className="member-button member-button--secondary"
                    style={{ fontSize: '13px' }}
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={isSubmittingChangeRequest || !newPhoneInput.trim()}
                    className="member-button member-button--primary"
                    style={{ fontSize: '13px', gap: '6px' }}
                  >
                    {isSubmittingChangeRequest ? (
                      <>
                        <Loader2 size={14} className="animate-spin" />
                        <span>Submitting...</span>
                      </>
                    ) : (
                      <>
                        <ArrowRight size={14} />
                        <span>Continue</span>
                      </>
                    )}
                  </button>
                </div>
              </form>
            </div>
          )}

          {/* STEP 5: SEND WHATSAPP MESSAGE FOR CHANGE REQUEST */}
          {step === 'change_number_whatsapp' && (
            <div>
              <div
                style={{
                  background: '#f8fafc',
                  border: '1px solid #e2e8f0',
                  borderRadius: '14px',
                  padding: '16px',
                  marginBottom: '18px',
                }}
              >
                <ol style={{ margin: 0, paddingLeft: '18px', fontSize: '13px', color: '#334155', lineHeight: 1.6 }}>
                  <li>Tap <strong>"Open WhatsApp"</strong> below. It has <strong>"PHONE NUMBER CHANGE REQUEST"</strong> pre-filled.</li>
                  <li>Send the message from your <strong>new number ({pendingChangeRequest?.new_phone || newPhoneInput})</strong>.</li>
                  <li>Return here and tap <strong>"I have sent Change Request"</strong>.</li>
                </ol>
              </div>

              <button
                type="button"
                onClick={() => handleOpenWhatsApp(pendingChangeRequest?.whatsapp_url || whatsappUrl)}
                style={{
                  width: '100%',
                  padding: '13px',
                  borderRadius: '12px',
                  background: '#25D366',
                  color: '#ffffff',
                  fontSize: '14px',
                  fontWeight: 700,
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  gap: '8px',
                  border: 'none',
                  cursor: 'pointer',
                  marginBottom: '12px',
                }}
              >
                <MessageSquare size={17} />
                <span>Open WhatsApp (Pre-filled Change Request)</span>
                <ExternalLink size={14} />
              </button>

              <button
                type="button"
                onClick={handleConfirmChangeWhatsApp}
                disabled={isConfirmingWhatsApp}
                className="member-button member-button--primary"
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
                  cursor: isConfirmingWhatsApp ? 'not-allowed' : 'pointer',
                }}
              >
                {isConfirmingWhatsApp ? (
                  <>
                    <Loader2 size={16} className="animate-spin" />
                    <span>Confirming Request...</span>
                  </>
                ) : (
                  <>
                    <CheckCircle2 size={16} />
                    <span>I have sent Change Request</span>
                  </>
                )}
              </button>
            </div>
          )}

          {/* STEP 6: CHANGE REQUEST PENDING ADMIN REVIEW */}
          {step === 'change_number_pending' && (
            <div>
              <div
                style={{
                  background: '#fffbeb',
                  border: '1.5px solid #fde68a',
                  borderRadius: '14px',
                  padding: '16px',
                  marginBottom: '18px',
                  textAlign: 'left',
                }}
              >
                <strong style={{ display: 'block', fontSize: '13.5px', color: '#92400e', marginBottom: '6px' }}>
                  Phone Number Change Request: Pending Admin Approval
                </strong>
                <p style={{ margin: '0 0 12px 0', fontSize: '12.5px', color: '#78350f', lineHeight: 1.5 }}>
                  Your request is awaiting admin approval. Your current number remains active and protected.
                </p>

                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px' }}>
                  <div style={{ padding: '8px', background: '#ffffff', borderRadius: '8px', border: '1px solid #e2e8f0' }}>
                    <span style={{ fontSize: '10.5px', color: '#059669', fontWeight: 700, display: 'block' }}>
                      ACTIVE NUMBER
                    </span>
                    <strong style={{ fontSize: '12.5px', color: '#0f172a' }}>
                      {pendingChangeRequest?.masked_old_phone || pendingChangeRequest?.old_phone || maskedPhone}
                    </strong>
                  </div>
                  <div style={{ padding: '8px', background: '#fef3c7', borderRadius: '8px', border: '1px solid #fde68a' }}>
                    <span style={{ fontSize: '10.5px', color: '#b45309', fontWeight: 700, display: 'block' }}>
                      REQUESTED (NEW)
                    </span>
                    <strong style={{ fontSize: '12.5px', color: '#0f172a' }}>
                      {pendingChangeRequest?.masked_new_phone || pendingChangeRequest?.new_phone}
                    </strong>
                  </div>
                </div>
              </div>

              <div style={{ display: 'flex', gap: '8px', marginBottom: '12px' }}>
                <button
                  type="button"
                  onClick={() => handleOpenWhatsApp(pendingChangeRequest?.whatsapp_url || whatsappUrl)}
                  className="member-button member-button--secondary"
                  style={{ flex: 1, fontSize: '12.5px', gap: '4px', justifyContent: 'center' }}
                >
                  <MessageSquare size={14} />
                  <span>Open WhatsApp</span>
                </button>

                <button
                  type="button"
                  onClick={handleCancelChangeRequest}
                  disabled={isCancellingChange}
                  style={{
                    padding: '8px 12px',
                    borderRadius: '10px',
                    border: '1px solid #fecaca',
                    background: '#fef2f2',
                    color: '#dc2626',
                    cursor: isCancellingChange ? 'not-allowed' : 'pointer',
                    fontSize: '12.5px',
                    fontWeight: 600,
                    display: 'flex',
                    alignItems: 'center',
                    gap: '4px',
                  }}
                >
                  <XCircle size={14} />
                  <span>{isCancellingChange ? 'Cancelling...' : 'Cancel Request'}</span>
                </button>
              </div>

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
                Close
              </button>
            </div>
          )}

          {/* STEP 7: FULLY VERIFIED (NO ACTIVE CHANGE REQUEST) */}
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
                Phone verification complete! You can now continue with all earning and platform activities.
              </div>

              <div style={{ display: 'flex', gap: '10px', justifyContent: 'center' }}>
                <button
                  type="button"
                  onClick={() => {
                    setError(null);
                    setNewPhoneInput('');
                    setStep('change_number_form');
                  }}
                  className="member-button member-button--secondary"
                  style={{ flex: 1, fontSize: '13px', gap: '6px', justifyContent: 'center' }}
                >
                  <PhoneForwarded size={14} />
                  <span>Change Number</span>
                </button>

                <button
                  type="button"
                  className="member-button member-button--primary"
                  onClick={() => {
                    onVerified?.(activeMember);
                    onClose();
                  }}
                  style={{
                    flex: 1,
                    padding: '12px',
                    borderRadius: '12px',
                    background: 'linear-gradient(135deg, #059669 0%, #10b981 100%)',
                    color: '#ffffff',
                    fontSize: '13.5px',
                    fontWeight: 700,
                    justifyContent: 'center',
                    border: 'none',
                    cursor: 'pointer',
                  }}
                >
                  Done
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </ModalPortal>
  );
}

export default AccountVerificationModal;
