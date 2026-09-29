import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import {
  CheckCircle2,
  MessageSquare,
  AlertCircle,
  ArrowRight,
  Clock,
  ExternalLink,
  ShieldCheck,
  Lock,
  Edit3,
  RefreshCw,
  PhoneCall,
  XCircle,
  Loader2,
  PhoneForwarded,
} from 'lucide-react';
import verificationApi from '../../api/verificationApi';
import useAuth from '../../hooks/useAuth';
import VerifiedBadge from '../common/VerifiedBadge';
import { resolveWhatsAppVerificationUrl, isMemberMobileVerified } from '../../utils/whatsappVerification';

export function MobileVerificationCard({
  member: propMember,
  onVerified,
  showProfileLink = true,
  cardClassName = 'card verification-card',
  style,
}) {
  const { user, setUser, refreshUser } = useAuth();
  const currentMember = propMember || user;

  // Steps: 
  // Verified flows: 'verified_hub' | 'change_number_form' | 'change_number_whatsapp' | 'change_number_pending'
  // Unverified flows: 'ready' | 'edit_unverified' | 'send_hi' | 'pending_hub'
  const [step, setStep] = useState('ready');
  const [isLoading, setIsLoading] = useState(false);
  const [isSubmittingPhone, setIsSubmittingPhone] = useState(false);
  const [isSubmittingChangeRequest, setIsSubmittingChangeRequest] = useState(false);
  const [isConfirmingWhatsApp, setIsConfirmingWhatsApp] = useState(false);
  const [isCancellingChange, setIsCancellingChange] = useState(false);

  const [error, setError] = useState(null);
  const [successMsg, setSuccessMsg] = useState(null);
  const [whatsappUrl, setWhatsappUrl] = useState('');
  const [whatsappDestination, setWhatsappDestination] = useState('');
  const [maskedPhone, setMaskedPhone] = useState('');
  const [rawPhone, setRawPhone] = useState(currentMember?.phone || '');

  // Form states
  const [editPhoneInput, setEditPhoneInput] = useState(currentMember?.phone || '');
  const [newPhoneInput, setNewPhoneInput] = useState('');
  const [pendingChangeRequest, setPendingChangeRequest] = useState(null);

  const formatMaskedPhone = (phone) => {
    if (!phone) return '';
    const clean = phone.replace(/[^\d+]/g, '');
    if (clean.length <= 6) return clean;
    const start = clean.slice(0, clean.startsWith('+') ? 5 : 4);
    const end = clean.slice(-2);
    return `${start}****${end}`;
  };

  const isVerified = isMemberMobileVerified(currentMember);
  const isPending = Boolean(
    !isVerified && (
      currentMember?.is_verification_pending ||
      currentMember?.mobile_verification_requested_at ||
      currentMember?.verification_status === 'pending'
    )
  );

  useEffect(() => {
    let isMounted = true;

    if (currentMember?.phone) {
      setRawPhone(currentMember.phone);
      setMaskedPhone(formatMaskedPhone(currentMember.phone));
      setEditPhoneInput(currentMember.phone);
    }

    if (isVerified) {
      setStep('verified_hub');
    } else if (isPending) {
      setStep('pending_hub');
    } else {
      setStep('ready');
    }

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
        if (res.whatsapp_url) {
          setWhatsappUrl(resolveWhatsAppVerificationUrl(res.whatsapp_url, res.whatsapp_destination || whatsappDestination, res.member || currentMember));
        }
        if (res.whatsapp_destination) {
          setWhatsappDestination(res.whatsapp_destination);
        }

        // Check for pending phone number change request
        if (res.has_pending_phone_change && res.pending_phone_change_request) {
          setPendingChangeRequest(res.pending_phone_change_request);
          setStep('change_number_pending');
          return;
        }

        if (res.is_verified) {
          setStep('verified_hub');
          setError(null);
          const verifiedMemberData = res.member || {
            ...user,
            is_verified: true,
            mobile_verified_at: res.mobile_verified_at || new Date().toISOString(),
            verification_status: 'verified',
          };
          if (setUser && (!user?.is_verified || !user?.mobile_verified_at)) {
            setUser((prev) => ({
              ...prev,
              ...verifiedMemberData,
              is_verified: true,
              mobile_verified_at: res.mobile_verified_at || verifiedMemberData.mobile_verified_at || prev?.mobile_verified_at,
              verification_status: 'verified',
            }));
          }
          if (refreshUser && (!user?.is_verified || !user?.mobile_verified_at)) {
            refreshUser();
          }
        } else if (res.is_pending || res.mobile_verification_requested_at) {
          setStep('pending_hub');
        }
      })
      .catch((err) => {
        console.warn('Could not load verification status for card:', err);
      });

    return () => {
      isMounted = false;
    };
  }, [isVerified, isPending, currentMember, user, setUser, refreshUser, whatsappDestination]);

  // Unverified: Update phone number
  const handleSaveUnverifiedPhone = async (e) => {
    e?.preventDefault();
    if (!editPhoneInput || editPhoneInput.trim().length < 7) {
      setError('Please enter a valid phone number with country code (e.g. +91 9876543210).');
      return;
    }

    setIsSubmittingPhone(true);
    setError(null);
    setSuccessMsg(null);

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
        setStep('ready');
        setSuccessMsg('Phone number updated successfully. You can now proceed to WhatsApp verification.');
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

  // Unverified: Proceed to WhatsApp "Hi" instructions
  const handleProceedToWhatsApp = async () => {
    setIsLoading(true);
    setError(null);
    setSuccessMsg(null);

    try {
      const res = await verificationApi.initiateVerification();
      if (res && res.success) {
        if (res.whatsapp_url) setWhatsappUrl(resolveWhatsAppVerificationUrl(res.whatsapp_url, res.whatsapp_destination || whatsappDestination, res.member || currentMember));
        if (res.masked_phone) setMaskedPhone(res.masked_phone);
        if (res.is_verified) {
          setStep('verified_hub');
          setError(null);
          const verifiedMemberData = res.member || {
            ...user,
            is_verified: true,
            mobile_verified_at: res.mobile_verified_at || new Date().toISOString(),
            verification_status: 'verified',
          };
          if (setUser && (!user?.is_verified || !user?.mobile_verified_at)) {
            setUser((prev) => ({
              ...prev,
              ...verifiedMemberData,
              is_verified: true,
              mobile_verified_at: res.mobile_verified_at || verifiedMemberData.mobile_verified_at || prev?.mobile_verified_at,
              verification_status: 'verified',
            }));
          }
          if (refreshUser && (!user?.is_verified || !user?.mobile_verified_at)) {
            refreshUser();
          }
          onVerified?.(verifiedMemberData);
          return;
        }
        if (res.is_pending) {
          setStep('pending_hub');
          return;
        }
        setStep('send_hi');
      } else {
        setError(res?.message || 'Unable to initiate WhatsApp verification. Please try again.');
      }
    } catch (err) {
      const msg = err.response?.data?.errors?.phone?.[0]
        || err.response?.data?.message
        || 'Unable to proceed to WhatsApp verification.';
      setError(msg);
    } finally {
      setIsLoading(false);
    }
  };

  const handleOpenWhatsApp = (customUrl = null) => {
    const targetUrl = customUrl || resolveWhatsAppVerificationUrl(whatsappUrl, whatsappDestination, currentMember);
    window.open(targetUrl, '_blank', 'noopener,noreferrer');
  };

  // Unverified: Submit that they sent "Hi"
  const handleSubmitSentHi = async () => {
    setIsLoading(true);
    setError(null);
    setSuccessMsg(null);

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
        setStep('pending_hub');
        setSuccessMsg('Your verification request has been submitted and is pending admin approval.');
      } else {
        setError(res?.message || 'Failed to submit verification request. Please try again.');
      }
    } catch (err) {
      let msg = err.response?.data?.errors?.request?.[0]
        || err.response?.data?.errors?.phone?.[0]
        || err.response?.data?.message
        || 'Failed to submit verification request.';

      if (typeof msg !== 'string' || msg.includes('SQLSTATE') || msg.includes('QueryException')) {
        msg = 'Failed to submit verification request. Please try again later.';
      }

      setError(msg);
    } finally {
      setIsLoading(false);
    }
  };

  // Verified: Submit Phone Number Change Request
  const handleSubmitChangeRequest = async (e) => {
    e?.preventDefault();
    if (!newPhoneInput || newPhoneInput.trim().length < 7) {
      setError('Please enter a valid new phone number with country code (e.g. +91 9876543210).');
      return;
    }

    setIsSubmittingChangeRequest(true);
    setError(null);
    setSuccessMsg(null);

    try {
      const res = await verificationApi.requestPhoneChange(newPhoneInput.trim());
      if (res && res.success) {
        setPendingChangeRequest(res.change_request);
        if (res.whatsapp_url) {
          setWhatsappUrl(res.whatsapp_url);
        }
        setStep('change_number_whatsapp');
        setSuccessMsg('Phone change request generated. Please send the WhatsApp verification message.');
      } else {
        setError(res?.message || 'Failed to submit phone change request.');
      }
    } catch (err) {
      const msg = err.response?.data?.errors?.new_phone?.[0]
        || err.response?.data?.message
        || 'Failed to submit phone change request. Please check number format.';
      setError(msg);
    } finally {
      setIsSubmittingChangeRequest(false);
    }
  };

  // Verified: Confirm sent WhatsApp for change request
  const handleConfirmChangeWhatsApp = async () => {
    if (!pendingChangeRequest?.id) return;
    setIsConfirmingWhatsApp(true);
    setError(null);
    setSuccessMsg(null);

    try {
      const res = await verificationApi.confirmPhoneChangeWhatsApp(pendingChangeRequest.id);
      if (res && res.success) {
        setPendingChangeRequest(res.change_request);
        setStep('change_number_pending');
        setSuccessMsg('WhatsApp verification confirmation recorded! Your request is pending admin approval.');
      } else {
        setError(res?.message || 'Failed to record WhatsApp confirmation.');
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to confirm WhatsApp request.');
    } finally {
      setIsConfirmingWhatsApp(false);
    }
  };

  // Verified: Cancel pending change request
  const handleCancelChangeRequest = async () => {
    if (!pendingChangeRequest?.id) return;
    if (!window.confirm('Are you sure you want to cancel this phone number change request?')) {
      return;
    }

    setIsCancellingChange(true);
    setError(null);
    setSuccessMsg(null);

    try {
      const res = await verificationApi.cancelPhoneChangeRequest(pendingChangeRequest.id);
      if (res && res.success) {
        setPendingChangeRequest(null);
        setStep('verified_hub');
        setSuccessMsg('Phone number change request has been cancelled.');
      } else {
        setError(res?.message || 'Failed to cancel change request.');
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to cancel change request.');
    } finally {
      setIsCancellingChange(false);
    }
  };

  return (
    <div style={style}>
      {error && (
        <div
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: '10px',
            padding: '14px 16px',
            borderRadius: '14px',
            background: '#fef2f2',
            border: '1px solid #fecaca',
            color: '#b91c1c',
            fontSize: '13.5px',
            marginBottom: '20px',
          }}
        >
          <AlertCircle size={20} style={{ flexShrink: 0 }} />
          <div style={{ flex: 1 }}>{error}</div>
        </div>
      )}

      {successMsg && (
        <div
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: '10px',
            padding: '14px 16px',
            borderRadius: '14px',
            background: '#f0fdf4',
            border: '1px solid #bbf7d0',
            color: '#15803d',
            fontSize: '13.5px',
            marginBottom: '20px',
          }}
        >
          <CheckCircle2 size={20} style={{ flexShrink: 0 }} />
          <div style={{ flex: 1 }}>{successMsg}</div>
        </div>
      )}

      {/* ========================================================================= */}
      {/* STATE 1: ALREADY VERIFIED HUB (NO ACTIVE CHANGE REQUEST)                   */}
      {/* ========================================================================= */}
      {step === 'verified_hub' && (
        <div
          className={cardClassName}
          style={{
            padding: '28px',
            borderRadius: '20px',
            textAlign: 'center',
            background: '#ffffff',
            border: '1px solid #e2e8f0',
          }}
        >
          <div
            style={{
              width: '80px',
              height: '80px',
              borderRadius: '24px',
              background: 'linear-gradient(135deg, #059669 0%, #10b981 100%)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              margin: '0 auto 16px',
              boxShadow: '0 10px 25px -5px rgba(16, 185, 129, 0.4)',
            }}
          >
            <CheckCircle2 size={44} color="#ffffff" />
          </div>

          <span
            style={{
              display: 'inline-flex',
              alignItems: 'center',
              gap: '6px',
              background: '#f0fdf4',
              border: '1px solid #bbf7d0',
              padding: '6px 14px',
              borderRadius: '999px',
              color: '#047857',
              fontWeight: 700,
              fontSize: '13px',
              marginBottom: '12px',
            }}
          >
            <VerifiedBadge member={currentMember} size={15} />
            <span>Verified WhatsApp Member</span>
          </span>

          <h2 style={{ fontSize: '20px', fontWeight: 800, color: '#0f172a', margin: '0 0 8px 0' }}>
            Your Account is Fully Verified
          </h2>

          <p style={{ fontSize: '13.5px', color: '#64748b', margin: '0 0 20px 0', lineHeight: 1.5 }}>
            Your registered mobile number <strong>{maskedPhone || currentMember?.phone}</strong> is
            verified. Your official green tick badge is displayed across all your posts, profile, and
            community interactions.
          </p>

          <div
            style={{
              display: 'grid',
              gridTemplateColumns: '1fr 1fr',
              gap: '12px',
              padding: '14px',
              borderRadius: '14px',
              background: '#f8fafc',
              border: '1px solid #e2e8f0',
              textAlign: 'left',
              marginBottom: '20px',
            }}
          >
            <div>
              <span style={{ fontSize: '11px', color: '#64748b', display: 'block' }}>
                Current Verified Number
              </span>
              <strong style={{ fontSize: '13.5px', color: '#0f172a' }}>
                {maskedPhone || currentMember?.phone || 'Verified'}
              </strong>
            </div>
            <div>
              <span style={{ fontSize: '11px', color: '#64748b', display: 'block' }}>
                Verification Channel
              </span>
              <strong
                style={{
                  fontSize: '13.5px',
                  color: '#10b981',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '4px',
                }}
              >
                <MessageSquare size={13} /> WhatsApp
              </strong>
            </div>
          </div>

          <div style={{ display: 'flex', gap: '10px', justifyContent: 'center', flexWrap: 'wrap' }}>
            <button
              type="button"
              onClick={() => {
                setError(null);
                setSuccessMsg(null);
                setNewPhoneInput('');
                setStep('change_number_form');
              }}
              className="member-button member-button--secondary"
              style={{ flex: '1 1 140px', justifyContent: 'center', gap: '6px' }}
            >
              <PhoneForwarded size={14} />
              <span>Change Number</span>
            </button>

            {showProfileLink && (
              <Link
                to="/member/profile"
                className="member-button member-button--primary"
                style={{ flex: '1 1 140px', justifyContent: 'center', textAlign: 'center' }}
              >
                <span>View My Profile</span>
              </Link>
            )}
          </div>
        </div>
      )}

      {/* ========================================================================= */}
      {/* STATE 2: VERIFIED MEMBER -> FORM TO REQUEST PHONE CHANGE                  */}
      {/* ========================================================================= */}
      {step === 'change_number_form' && (
        <div
          className={cardClassName}
          style={{
            padding: '28px',
            borderRadius: '20px',
            background: '#ffffff',
            border: '1px solid #e2e8f0',
          }}
        >
          <div style={{ marginBottom: '20px' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '6px' }}>
              <PhoneForwarded size={20} color="#059669" />
              <h2 style={{ fontSize: '18px', fontWeight: 800, margin: 0, color: '#0f172a' }}>
                Request Phone Number Change
              </h2>
            </div>
            <p style={{ fontSize: '13px', color: '#64748b', margin: 0, lineHeight: 1.5 }}>
              Enter your new WhatsApp number. Your existing verified number will remain active and protected until your change request is approved by an administrator.
            </p>
          </div>

          {/* Current Verified Number Card */}
          <div
            style={{
              background: '#f0fdf4',
              border: '1px solid #bbf7d0',
              borderRadius: '12px',
              padding: '12px 16px',
              marginBottom: '18px',
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
                {maskedPhone || currentMember?.phone}
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
            <div style={{ marginBottom: '18px' }}>
              <label
                htmlFor="new-phone-input"
                style={{ display: 'block', fontSize: '13px', fontWeight: 700, color: '#334155', marginBottom: '6px' }}
              >
                New WhatsApp Mobile Number
              </label>
              <input
                id="new-phone-input"
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
                  fontSize: '14.5px',
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
                  setStep('verified_hub');
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
                    <span>Submitting Request...</span>
                  </>
                ) : (
                  <>
                    <ArrowRight size={14} />
                    <span>Continue to WhatsApp</span>
                  </>
                )}
              </button>
            </div>
          </form>
        </div>
      )}

      {/* ========================================================================= */}
      {/* STATE 3: VERIFIED MEMBER -> SEND WHATSAPP MESSAGE FOR CHANGE REQUEST      */}
      {/* ========================================================================= */}
      {step === 'change_number_whatsapp' && (
        <div
          className={cardClassName}
          style={{
            padding: '28px',
            borderRadius: '20px',
            background: '#ffffff',
            border: '1px solid #e2e8f0',
          }}
        >
          <div style={{ marginBottom: '18px' }}>
            <h2 style={{ fontSize: '18px', fontWeight: 800, margin: '0 0 6px 0', color: '#0f172a' }}>
              Send Change Request via WhatsApp
            </h2>
            <p style={{ fontSize: '13px', color: '#64748b', margin: 0 }}>
              Verify ownership of your new number by sending the pre-filled change request message.
            </p>
          </div>

          <div
            style={{
              background: '#f8fafc',
              border: '1px solid #e2e8f0',
              borderRadius: '14px',
              padding: '16px',
              marginBottom: '20px',
            }}
          >
            <ol style={{ margin: 0, paddingLeft: '18px', fontSize: '13px', color: '#334155', lineHeight: 1.6 }}>
              <li>
                Tap <strong>"Open WhatsApp"</strong> below. It will open with <strong>"PHONE NUMBER CHANGE REQUEST"</strong> pre-filled.
              </li>
              <li>
                Send the message to our official support number from your <strong>new number ({pendingChangeRequest?.new_phone || newPhoneInput})</strong>.
              </li>
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

      {/* ========================================================================= */}
      {/* STATE 4: VERIFIED MEMBER -> CHANGE REQUEST PENDING ADMIN APPROVAL         */}
      {/* ========================================================================= */}
      {step === 'change_number_pending' && (
        <div
          className={cardClassName}
          style={{
            padding: '28px',
            borderRadius: '20px',
            textAlign: 'center',
            background: '#ffffff',
            border: '1.5px solid #fde68a',
          }}
        >
          <div
            style={{
              width: '72px',
              height: '72px',
              borderRadius: '22px',
              background: 'linear-gradient(135deg, #d97706 0%, #f59e0b 100%)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              margin: '0 auto 16px',
              boxShadow: '0 8px 20px -4px rgba(217, 119, 6, 0.35)',
            }}
          >
            <Clock size={38} color="#ffffff" />
          </div>

          <span
            style={{
              display: 'inline-flex',
              alignItems: 'center',
              gap: '6px',
              background: '#fef3c7',
              border: '1px solid #fde68a',
              padding: '6px 14px',
              borderRadius: '999px',
              color: '#b45309',
              fontWeight: 700,
              fontSize: '13px',
              marginBottom: '12px',
            }}
          >
            <Clock size={14} color="#d97706" />
            <span>Phone Change Request Pending Admin Approval</span>
          </span>

          <h2 style={{ fontSize: '19px', fontWeight: 800, color: '#0f172a', margin: '0 0 8px 0' }}>
            Change Request Under Review
          </h2>

          <p style={{ fontSize: '13.5px', color: '#64748b', margin: '0 0 20px 0', lineHeight: 1.5 }}>
            Your request to update your registered WhatsApp number is waiting for administrator approval. Your current number remains active and fully verified in the meantime.
          </p>

          {/* Old vs New Number Comparison Card */}
          <div
            style={{
              display: 'grid',
              gridTemplateColumns: '1fr 1fr',
              gap: '12px',
              padding: '14px',
              borderRadius: '14px',
              background: '#f8fafc',
              border: '1px solid #e2e8f0',
              textAlign: 'left',
              marginBottom: '20px',
            }}
          >
            <div style={{ padding: '8px', background: '#ffffff', borderRadius: '10px', border: '1px solid #e2e8f0' }}>
              <span style={{ fontSize: '11px', color: '#059669', fontWeight: 700, display: 'block', textTransform: 'uppercase' }}>
                Active Number (Current)
              </span>
              <strong style={{ fontSize: '13.5px', color: '#0f172a', display: 'block', marginTop: '2px' }}>
                {pendingChangeRequest?.masked_old_phone || pendingChangeRequest?.old_phone || maskedPhone}
              </strong>
              <span style={{ fontSize: '10.5px', color: '#059669', display: 'flex', alignItems: 'center', gap: '3px', marginTop: '4px' }}>
                <CheckCircle2 size={11} /> Active
              </span>
            </div>

            <div style={{ padding: '8px', background: '#fffbeb', borderRadius: '10px', border: '1px solid #fde68a' }}>
              <span style={{ fontSize: '11px', color: '#d97706', fontWeight: 700, display: 'block', textTransform: 'uppercase' }}>
                Requested Number (New)
              </span>
              <strong style={{ fontSize: '13.5px', color: '#0f172a', display: 'block', marginTop: '2px' }}>
                {pendingChangeRequest?.masked_new_phone || pendingChangeRequest?.new_phone}
              </strong>
              <span style={{ fontSize: '10.5px', color: '#b45309', display: 'flex', alignItems: 'center', gap: '3px', marginTop: '4px' }}>
                <Clock size={11} /> Pending Review
              </span>
            </div>
          </div>

          <div style={{ display: 'flex', gap: '10px', justifyContent: 'center', flexWrap: 'wrap' }}>
            <button
              type="button"
              onClick={() => handleOpenWhatsApp(pendingChangeRequest?.whatsapp_url || whatsappUrl)}
              className="member-button member-button--secondary"
              style={{ fontSize: '13px', gap: '6px' }}
            >
              <MessageSquare size={14} />
              <span>Open WhatsApp Again</span>
              <ExternalLink size={13} />
            </button>

            <button
              type="button"
              onClick={handleCancelChangeRequest}
              disabled={isCancellingChange}
              style={{
                fontSize: '13px',
                padding: '8px 14px',
                borderRadius: '10px',
                border: '1px solid #fecaca',
                background: '#fef2f2',
                color: '#dc2626',
                cursor: isCancellingChange ? 'not-allowed' : 'pointer',
                display: 'inline-flex',
                alignItems: 'center',
                gap: '5px',
                fontWeight: 600,
              }}
            >
              <XCircle size={14} />
              <span>{isCancellingChange ? 'Cancelling...' : 'Cancel Request'}</span>
            </button>
          </div>
        </div>
      )}

      {/* ========================================================================= */}
      {/* STATE 5: UNVERIFIED MEMBER -> READY TO VERIFY (CAN EDIT PHONE)            */}
      {/* ========================================================================= */}
      {step === 'ready' && (
        <div
          className={cardClassName}
          style={{
            padding: '28px',
            borderRadius: '20px',
            background: '#ffffff',
            border: '1px solid #e2e8f0',
          }}
        >
          <div style={{ marginBottom: '20px' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '6px' }}>
              <ShieldCheck size={20} color="#059669" />
              <h2 style={{ fontSize: '18px', fontWeight: 800, margin: 0, color: '#0f172a' }}>
                Verify Mobile via WhatsApp
              </h2>
            </div>
            <p style={{ fontSize: '13px', color: '#64748b', margin: 0 }}>
              Verify your registered WhatsApp mobile number to unlock verified member benefits.
            </p>
          </div>

          {/* Registered Phone Box with Edit option */}
          <div
            style={{
              background: '#f8fafc',
              border: '1.5px solid #e2e8f0',
              borderRadius: '14px',
              padding: '16px',
              marginBottom: '20px',
            }}
          >
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '8px' }}>
              <span style={{ fontSize: '11.5px', fontWeight: 700, color: '#64748b', textTransform: 'uppercase' }}>
                WhatsApp Number to Verify
              </span>
              <button
                type="button"
                onClick={() => {
                  setError(null);
                  setSuccessMsg(null);
                  setEditPhoneInput(rawPhone || currentMember?.phone || '');
                  setStep('edit_unverified');
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
                <span>Edit / Change Number</span>
              </button>
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
                }}
              >
                <MessageSquare size={18} />
              </div>
              <div>
                <strong style={{ fontSize: '16px', color: '#0f172a' }}>
                  {maskedPhone || rawPhone || 'No phone registered'}
                </strong>
                <div style={{ fontSize: '11.5px', color: '#64748b' }}>
                  No OTP required. Verify directly by sending "Hi" on WhatsApp.
                </div>
              </div>
            </div>
          </div>

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

      {/* ========================================================================= */}
      {/* STATE 6: UNVERIFIED MEMBER -> INLINE EDIT NUMBER FORM                     */}
      {/* ========================================================================= */}
      {step === 'edit_unverified' && (
        <div
          className={cardClassName}
          style={{
            padding: '28px',
            borderRadius: '20px',
            background: '#ffffff',
            border: '1px solid #e2e8f0',
          }}
        >
          <div style={{ marginBottom: '18px' }}>
            <h2 style={{ fontSize: '18px', fontWeight: 800, margin: '0 0 6px 0', color: '#0f172a' }}>
              Edit Unverified Phone Number
            </h2>
            <p style={{ fontSize: '13px', color: '#64748b', margin: 0 }}>
              Enter the WhatsApp phone number you wish to use and verify for this account.
            </p>
          </div>

          <form onSubmit={handleSaveUnverifiedPhone}>
            <div style={{ marginBottom: '18px' }}>
              <label
                htmlFor="unverified-phone-edit-input"
                style={{ display: 'block', fontSize: '13px', fontWeight: 700, color: '#334155', marginBottom: '6px' }}
              >
                Mobile Number (with Country Code)
              </label>
              <input
                id="unverified-phone-edit-input"
                type="tel"
                value={editPhoneInput}
                onChange={(e) => setEditPhoneInput(e.target.value)}
                placeholder="+91 9876543210"
                required
                style={{
                  width: '100%',
                  padding: '11px 14px',
                  borderRadius: '10px',
                  border: '1.5px solid #cbd5e1',
                  fontSize: '14.5px',
                  boxSizing: 'border-box',
                }}
              />
              <span style={{ fontSize: '11.5px', color: '#64748b', display: 'block', marginTop: '5px' }}>
                Example: +91 9876543210 or +1 2345678901.
              </span>
            </div>

            <div style={{ display: 'flex', gap: '10px', justifyContent: 'flex-end' }}>
              <button
                type="button"
                onClick={() => {
                  setError(null);
                  setStep('ready');
                }}
                disabled={isSubmittingPhone}
                className="member-button member-button--secondary"
                style={{ fontSize: '13px' }}
              >
                Cancel
              </button>

              <button
                type="submit"
                disabled={isSubmittingPhone || !editPhoneInput.trim()}
                className="member-button member-button--primary"
                style={{ fontSize: '13px', gap: '6px' }}
              >
                {isSubmittingPhone ? (
                  <>
                    <Loader2 size={14} className="animate-spin" />
                    <span>Saving...</span>
                  </>
                ) : (
                  <>
                    <CheckCircle2 size={14} />
                    <span>Save Number</span>
                  </>
                )}
              </button>
            </div>
          </form>
        </div>
      )}

      {/* ========================================================================= */}
      {/* STATE 7: UNVERIFIED MEMBER -> SEND "HI" INSTRUCTIONS                      */}
      {/* ========================================================================= */}
      {step === 'send_hi' && (
        <div
          className={cardClassName}
          style={{
            padding: '28px',
            borderRadius: '20px',
            background: '#ffffff',
            border: '1px solid #e2e8f0',
          }}
        >
          <div style={{ marginBottom: '18px' }}>
            <h2 style={{ fontSize: '18px', fontWeight: 800, margin: '0 0 6px 0', color: '#0f172a' }}>
              Send "Hi" to Verify
            </h2>
            <p style={{ fontSize: '13px', color: '#64748b', margin: 0 }}>
              Follow the instructions below to complete your verification request.
            </p>
          </div>

          <div
            style={{
              background: '#f8fafc',
              border: '1px solid #e2e8f0',
              borderRadius: '14px',
              padding: '16px',
              marginBottom: '20px',
            }}
          >
            <ol style={{ margin: 0, paddingLeft: '18px', fontSize: '13px', color: '#334155', lineHeight: 1.6 }}>
              <li>Tap <strong>"Open WhatsApp"</strong> below to open the chat with <strong>"Hi"</strong> pre-filled.</li>
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

          <div style={{ textAlign: 'center', marginTop: '14px' }}>
            <button
              type="button"
              onClick={() => setStep('ready')}
              style={{
                background: 'none',
                border: 'none',
                color: '#64748b',
                fontSize: '12px',
                cursor: 'pointer',
                padding: 0,
              }}
            >
              ← Back to phone details
            </button>
          </div>
        </div>
      )}

      {/* ========================================================================= */}
      {/* STATE 8: UNVERIFIED MEMBER -> INITIAL VERIFICATION PENDING REVIEW         */}
      {/* ========================================================================= */}
      {step === 'pending_hub' && (
        <div
          className={cardClassName}
          style={{
            padding: '28px',
            borderRadius: '20px',
            textAlign: 'center',
            background: '#ffffff',
            border: '1px solid #fde68a',
          }}
        >
          <div
            style={{
              width: '72px',
              height: '72px',
              borderRadius: '22px',
              background: 'linear-gradient(135deg, #d97706 0%, #f59e0b 100%)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              margin: '0 auto 16px',
              boxShadow: '0 8px 20px -4px rgba(217, 119, 6, 0.35)',
            }}
          >
            <Clock size={38} color="#ffffff" />
          </div>

          <span
            style={{
              display: 'inline-flex',
              alignItems: 'center',
              gap: '6px',
              background: '#fef3c7',
              border: '1px solid #fde68a',
              padding: '6px 14px',
              borderRadius: '999px',
              color: '#b45309',
              fontWeight: 700,
              fontSize: '13px',
              marginBottom: '12px',
            }}
          >
            <Clock size={14} color="#d97706" />
            <span>Verification Pending Admin Review</span>
          </span>

          <h2 style={{ fontSize: '19px', fontWeight: 800, color: '#0f172a', margin: '0 0 8px 0' }}>
            Verification Request Submitted
          </h2>

          <p style={{ fontSize: '13.5px', color: '#64748b', margin: '0 0 20px 0', lineHeight: 1.5 }}>
            We received your request from <strong>{maskedPhone || rawPhone}</strong>. Our administrators
            manually verify each incoming WhatsApp message to maintain network authenticity.
          </p>

          <div
            style={{
              background: '#fffbeb',
              border: '1px solid #fde68a',
              borderRadius: '14px',
              padding: '14px 16px',
              textAlign: 'left',
              fontSize: '12.5px',
              color: '#92400e',
              lineHeight: 1.45,
              marginBottom: '20px',
            }}
          >
            <strong>What happens next?</strong>
            <p style={{ margin: '4px 0 0 0' }}>
              Once an administrator reviews your WhatsApp message, your account will be activated with the Green Verified Tick badge.
            </p>
          </div>

          <div style={{ display: 'flex', gap: '10px', justifyContent: 'center' }}>
            <button
              type="button"
              onClick={() => handleOpenWhatsApp()}
              className="member-button member-button--secondary"
              style={{ fontSize: '13px', gap: '6px' }}
            >
              <MessageSquare size={14} />
              <span>Open WhatsApp Again</span>
              <ExternalLink size={13} />
            </button>
          </div>
        </div>
      )}
    </div>
  );
}

export default MobileVerificationCard;
