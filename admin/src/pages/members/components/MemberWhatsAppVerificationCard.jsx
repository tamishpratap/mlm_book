import React from 'react';
import { MessageSquare, CheckCircle2, XCircle, AlertTriangle, ShieldCheck, Clock } from 'lucide-react';
import { Button } from 'primereact/button';
import { StatusBadge } from '../../../components/common/StatusBadge';

export function MemberWhatsAppVerificationCard({
  member,
  onApprove,
  onReject,
  loading = false,
}) {
  if (!member) return null;

  const isVerified = Boolean(member.mobile_verified_at || member.is_verified);
  const isPending = Boolean(!isVerified && member.mobile_verification_requested_at);
  const hasPhone = Boolean(member.phone);

  const formattedRequestedAt = member.mobile_verification_requested_at
    ? new Date(member.mobile_verification_requested_at).toLocaleString()
    : 'Not requested';

  const formattedVerifiedAt = member.mobile_verified_at
    ? new Date(member.mobile_verified_at).toLocaleString()
    : null;

  return (
    <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden mb-6">
      {/* Header */}
      <div className="px-6 py-4 bg-slate-50/80 border-b border-slate-200/80 flex items-center justify-between flex-wrap gap-2">
        <div className="flex items-center space-x-2.5">
          <div className="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100">
            <MessageSquare className="w-4 h-4" />
          </div>
          <div>
            <h3 className="text-sm font-bold text-slate-800">
              WhatsApp Verification
            </h3>
            <p className="text-[11px] text-slate-500">
              Manual verification of incoming WhatsApp &ldquo;Hi&rdquo; message against registered phone number.
            </p>
          </div>
        </div>

        <div className="flex items-center space-x-2">
          {isVerified ? (
            <StatusBadge status="verified" label="VERIFIED MEMBER" />
          ) : isPending ? (
            <StatusBadge status="pending" label="PENDING VERIFICATION" />
          ) : (
            <StatusBadge status="unverified" label="UNVERIFIED MEMBER" />
          )}
        </div>
      </div>

      {/* Body Content */}
      <div className="p-6 space-y-5">
        {/* Verification Data Grid */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {/* Member ID */}
          <div className="bg-slate-50 rounded-xl p-3.5 border border-slate-200/60">
            <span className="text-[11px] text-slate-500 font-medium block">Member ID:</span>
            <code className="text-xs font-mono font-bold text-blue-600 mt-0.5 block">
              {member.user_id}
            </code>
          </div>

          {/* Member Name */}
          <div className="bg-slate-50 rounded-xl p-3.5 border border-slate-200/60">
            <span className="text-[11px] text-slate-500 font-medium block">Name:</span>
            <span className="text-xs font-bold text-slate-900 mt-0.5 block truncate">
              {member.name}
            </span>
          </div>

          {/* Email */}
          <div className="bg-slate-50 rounded-xl p-3.5 border border-slate-200/60">
            <span className="text-[11px] text-slate-500 font-medium block">Email:</span>
            <span className="text-xs text-slate-700 mt-0.5 block truncate" title={member.email}>
              {member.email}
            </span>
          </div>

          {/* Registered WhatsApp Phone (Unmasked & Prominent) */}
          <div className="bg-emerald-50/60 rounded-xl p-3.5 border border-emerald-200/70">
            <span className="text-[11px] text-emerald-800 font-medium block">
              Registered WhatsApp:
            </span>
            <span className="text-sm font-mono font-extrabold text-emerald-900 mt-0.5 block tracking-wide">
              {hasPhone ? member.phone : 'No Phone Registered'}
            </span>
          </div>
        </div>

        {/* Timestamps Row */}
        <div className="flex flex-wrap items-center gap-x-6 gap-y-2 text-xs text-slate-600 bg-slate-50/50 p-3 rounded-lg border border-slate-100">
          <div className="flex items-center space-x-1.5">
            <Clock className="w-3.5 h-3.5 text-slate-400" />
            <span className="text-slate-500">Verification Requested:</span>
            <strong className="text-slate-800">{formattedRequestedAt}</strong>
          </div>

          {isVerified && formattedVerifiedAt && (
            <div className="flex items-center space-x-1.5">
              <ShieldCheck className="w-3.5 h-3.5 text-emerald-600" />
              <span className="text-slate-500">Verified On:</span>
              <strong className="text-emerald-700">{formattedVerifiedAt}</strong>
            </div>
          )}
        </div>

        {/* Manual Comparison Instruction Box */}
        <div className="bg-amber-50/80 rounded-xl p-4 border border-amber-200/70 flex items-start space-x-3">
          <AlertTriangle className="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
          <div className="text-xs text-amber-900 leading-relaxed">
            <strong className="font-semibold block mb-0.5">Admin Manual Comparison Instruction:</strong>
            Check the incoming WhatsApp &ldquo;Hi&rdquo; message on the official WhatsApp account and compare the sender number with the registered WhatsApp number above (<span className="font-mono font-bold text-amber-950">{member.phone || 'N/A'}</span>).
          </div>
        </div>

        {/* Action Controls */}
        <div className="flex flex-wrap items-center justify-end gap-3 pt-2 border-t border-slate-100">
          {!isVerified ? (
            <>
              <Button
                type="button"
                label={loading ? 'Processing...' : 'Reject / Dismiss Request'}
                icon="pi pi-times-circle"
                severity="warning"
                outlined
                size="small"
                onClick={onReject}
                disabled={loading || !isPending}
                className="text-xs"
              />
              <Button
                type="button"
                label={loading ? 'Approving...' : 'Approve WhatsApp Verification'}
                icon="pi pi-check-circle"
                severity="success"
                size="small"
                onClick={onApprove}
                disabled={loading || !hasPhone || !isPending}
                title={!isPending ? 'Member has not submitted a WhatsApp verification request' : undefined}
                className="text-xs bg-emerald-600 hover:bg-emerald-700 border-emerald-600"
              />
            </>
          ) : (
            <div className="flex items-center space-x-3">
              <span className="inline-flex items-center text-xs font-semibold text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200">
                <ShieldCheck className="w-4 h-4 mr-1.5 text-emerald-600" />
                Account Verified
              </span>
              <Button
                type="button"
                label={loading ? 'Processing...' : 'Revoke Verification'}
                icon="pi pi-undo"
                severity="danger"
                outlined
                size="small"
                onClick={onReject}
                disabled={loading}
                className="text-xs"
              />
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

export default MemberWhatsAppVerificationCard;
