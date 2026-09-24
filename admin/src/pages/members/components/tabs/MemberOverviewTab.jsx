import { User, MapPin } from 'lucide-react';
import { StatusBadge } from '../../../../components/common/StatusBadge';

export function MemberOverviewTab({ member }) {
  if (!member) return null;

  const isVerified = Boolean(member.mobile_verified_at || member.is_verified);
  const isBlocked = Boolean(member.blocked_at || member.is_blocked || member.status === 'blocked');

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
      {/* 1. Account & Personal Info */}
      <div>
        <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider mb-3 flex items-center">
          <User className="w-3.5 h-3.5 mr-1.5 text-blue-600" /> Basic Information
        </h4>
        <div className="bg-slate-50/80 rounded-xl p-4 border border-slate-200/60 space-y-2.5 text-xs">
          <div className="flex justify-between py-1 border-b border-slate-200/50">
            <span className="text-slate-500 font-medium">Full Name:</span>
            <strong className="text-slate-900">{member.name}</strong>
          </div>
          <div className="flex justify-between py-1 border-b border-slate-200/50">
            <span className="text-slate-500 font-medium">User ID:</span>
            <code className="text-blue-600 font-mono font-semibold">{member.user_id}</code>
          </div>
          <div className="flex justify-between py-1 border-b border-slate-200/50">
            <span className="text-slate-500 font-medium">Email:</span>
            <span className="text-slate-800">{member.email}</span>
          </div>
          <div className="flex justify-between py-1 border-b border-slate-200/50 items-center">
            <span className="text-slate-500 font-medium">Phone:</span>
            <span className="text-slate-800 font-mono">{member.phone || 'Not provided'}</span>
          </div>
          <div className="flex justify-between py-1 border-b border-slate-200/50 items-center">
            <span className="text-slate-500 font-medium">Mobile Verification:</span>
            <StatusBadge
              status={isVerified ? 'verified' : member.mobile_verification_requested_at ? 'pending' : 'unverified'}
              label={isVerified ? 'VERIFIED MEMBER' : member.mobile_verification_requested_at ? 'PENDING VERIFICATION' : 'UNVERIFIED MEMBER'}
            />
          </div>
          {member.mobile_verification_requested_at && (
            <div className="flex justify-between py-1 border-b border-slate-200/50">
              <span className="text-slate-500 font-medium">Verification Requested:</span>
              <span className="text-slate-700">{new Date(member.mobile_verification_requested_at).toLocaleString()}</span>
            </div>
          )}
          {isVerified && member.mobile_verified_at && (
            <div className="flex justify-between py-1 border-b border-slate-200/50">
              <span className="text-slate-500 font-medium">Verified At:</span>
              <span className="text-slate-700">{new Date(member.mobile_verified_at).toLocaleString()}</span>
            </div>
          )}
          <div className="flex justify-between py-1 border-b border-slate-200/50 items-center">
            <span className="text-slate-500 font-medium">Account Status:</span>
            <StatusBadge
              status={isBlocked ? 'blocked' : 'active'}
              label={isBlocked ? 'BLOCKED MEMBER' : 'ACTIVE'}
            />
          </div>
          {isBlocked && member.blocked_at && (
            <div className="flex justify-between py-1 border-b border-slate-200/50">
              <span className="text-slate-500 font-medium">Blocked At:</span>
              <span className="text-red-600 font-medium">{new Date(member.blocked_at).toLocaleString()}</span>
            </div>
          )}
          <div className="flex justify-between py-1 border-b border-slate-200/50 items-center">
            <span className="text-slate-500 font-medium">Referral Eligibility:</span>
            <span className={isVerified ? "text-emerald-700 font-semibold" : "text-amber-700 font-semibold"}>
              {isVerified ? 'Eligible (Mobile Verified)' : 'Not Eligible (Mobile Unverified)'}
            </span>
          </div>
          <div className="flex justify-between py-1 border-b border-slate-200/50">
            <span className="text-slate-500 font-medium">Introducer (Referrer):</span>
            {member.introducer_id ? (
              <code className="text-emerald-600 font-mono font-semibold">@{member.introducer_id}</code>
            ) : (
              <span className="text-slate-400">Direct Registration (None)</span>
            )}
          </div>
          <div className="flex justify-between py-1 border-b border-slate-200/50">
            <span className="text-slate-500 font-medium">Direct Referrals:</span>
            <span className="inline-flex items-center justify-center font-bold text-xs px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-100">
              {member.direct_referral_count ?? 0}
            </span>
          </div>
          <div className="flex justify-between py-1 border-b border-slate-200/50">
            <span className="text-slate-500 font-medium">Gender:</span>
            <span className="text-slate-800 capitalize">{member.gender || 'Not specified'}</span>
          </div>
          <div className="flex justify-between pt-1">
            <span className="text-slate-500 font-medium">Date of Birth:</span>
            <span className="text-slate-800">{member.date_of_birth || 'Not specified'}</span>
          </div>
        </div>
      </div>

      {/* 2. Location & Biography */}
      <div>
        <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider mb-3 flex items-center">
          <MapPin className="w-3.5 h-3.5 mr-1.5 text-indigo-600" /> Location & Biography
        </h4>
        <div className="bg-slate-50/80 rounded-xl p-4 border border-slate-200/60 space-y-2.5 text-xs">
          <div className="flex justify-between py-1 border-b border-slate-200/50">
            <span className="text-slate-500 font-medium">City:</span>
            <span className="text-slate-800">{member.city || 'N/A'}</span>
          </div>
          <div className="flex justify-between py-1 border-b border-slate-200/50">
            <span className="text-slate-500 font-medium">Country:</span>
            <span className="text-slate-800">{member.country || 'N/A'}</span>
          </div>
          <div className="flex justify-between py-1 border-b border-slate-200/50">
            <span className="text-slate-500 font-medium">Website:</span>
            {member.website ? (
              <a
                href={member.website}
                target="_blank"
                rel="noreferrer"
                className="text-blue-600 hover:underline font-medium"
              >
                {member.website}
              </a>
            ) : (
              <span className="text-slate-400">N/A</span>
            )}
          </div>
          <div className="flex justify-between py-1 border-b border-slate-200/50">
            <span className="text-slate-500 font-medium">Joined On:</span>
            <span className="text-slate-800">{member.created_at_human || 'Recently'}</span>
          </div>
          <div className="pt-2">
            <span className="text-slate-500 font-medium block mb-1">Biography / About Member:</span>
            <p className="text-slate-700 bg-white p-3 rounded-lg border border-slate-200/80 leading-relaxed text-xs">
              {member.bio || 'No bio details provided by member.'}
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}

export default MemberOverviewTab;
