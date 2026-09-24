import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import {
  MapPin,
  Clock,
  Calendar,
  ExternalLink,
} from 'lucide-react';
import { Button } from 'primereact/button';
import { StatusBadge } from '../../../components/common/StatusBadge';
import { getMemberCoverUrl, getMemberAvatarUrl } from '../../../utils/mediaHelper';

export function MemberProfileBanner({
  member,
  counts = {},
  onToggleBlock,
  onToggleStatus,
  onOpenConnections,
  actionLoading = false,
}) {
  const navigate = useNavigate();
  if (!member) return null;

  const displayName = member.name || 'Anonymous Member';
  const initial = displayName.charAt(0).toUpperCase();
  const isVerified = Boolean(member.mobile_verified_at || member.is_verified);
  const isBlocked = Boolean(member.blocked_at || member.is_blocked || member.status === 'blocked');

  const coverUrl = getMemberCoverUrl(member);
  const avatarUrl = getMemberAvatarUrl(member);
  const [coverError, setCoverError] = useState(false);
  const [avatarError, setAvatarError] = useState(false);

  useEffect(() => {
    setCoverError(false);
  }, [coverUrl]);

  useEffect(() => {
    setAvatarError(false);
  }, [avatarUrl]);

  return (
    <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
      {/* Cover image banner */}
      <div className="relative h-44 sm:h-52 bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-900 overflow-hidden">
        {coverUrl && !coverError ? (
          <img
            src={coverUrl}
            alt="Cover"
            className="w-full h-full object-cover opacity-90 transition-opacity duration-300"
            onError={() => setCoverError(true)}
          />
        ) : (
          <div className="absolute inset-0 bg-radial from-blue-500/10 to-transparent pointer-events-none" />
        )}
      </div>

      {/* Profile info strip */}
      <div className="px-6 pb-6 pt-0 relative bg-white">
        <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-4 -mt-14 mb-4">
          {/* Left: Avatar + Identity */}
          <div className="flex flex-col sm:flex-row items-center sm:items-end space-y-3 sm:space-y-0 sm:space-x-4 text-center sm:text-left">
            <div className="relative shrink-0">
              {avatarUrl && !avatarError ? (
                <img
                  src={avatarUrl}
                  alt={displayName}
                  className="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl object-cover ring-4 ring-white shadow-lg bg-white"
                  onError={() => setAvatarError(true)}
                />
              ) : (
                <div className="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl bg-blue-600 text-white font-extrabold text-3xl flex items-center justify-center ring-4 ring-white shadow-lg">
                  {initial}
                </div>
              )}
            </div>

            <div className="pb-1">
              <div className="flex items-center justify-center sm:justify-start space-x-2 flex-wrap gap-y-1">
                <h2 className="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                  {displayName}
                </h2>
                <code className="text-xs font-mono font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md border border-blue-100">
                  {member.user_id}
                </code>

                <StatusBadge
                  status={isVerified ? 'verified' : 'unverified'}
                  label={isVerified ? 'VERIFIED MEMBER' : 'UNVERIFIED MEMBER'}
                />
                {isBlocked && (
                  <StatusBadge status="blocked" label="BLOCKED MEMBER" />
                )}
              </div>

              <div className="flex items-center justify-center sm:justify-start space-x-4 text-xs text-slate-500 mt-2 flex-wrap gap-y-1">
                <span className="flex items-center">
                  <MapPin className="w-3.5 h-3.5 mr-1 text-blue-500" />
                  {[member.city, member.country].filter(Boolean).join(', ') || 'Location not specified'}
                </span>
                <span>•</span>
                <span className="flex items-center">
                  <Clock className="w-3.5 h-3.5 mr-1 text-slate-400" />
                  {member.is_online ? (
                    <strong className="text-emerald-600">Online Now</strong>
                  ) : (
                    <span>{member.online_status_label || 'Active Recently'}</span>
                  )}
                </span>
                <span>•</span>
                <span className="flex items-center">
                  <Calendar className="w-3.5 h-3.5 mr-1 text-slate-400" />
                  Joined {member.created_at_human || 'Recently'}
                </span>
              </div>
            </div>
          </div>

          {/* Right: Actions */}
          <div className="flex flex-wrap items-center justify-center sm:justify-end gap-2.5 sm:gap-3 pb-1">
            <Link to={`/admin/members/${member.id}/edit`} className="inline-flex">
              <Button
                label="Edit Member"
                icon="pi pi-user-edit"
                size="small"
                className="p-button-primary text-xs"
              />
            </Link>

            {isBlocked ? (
              <Button
                label={actionLoading ? 'Unblocking...' : 'Unblock Member'}
                icon="pi pi-check-circle"
                size="small"
                severity="success"
                onClick={onToggleBlock}
                disabled={actionLoading}
                loading={actionLoading}
                className="text-xs"
              />
            ) : (
              <Button
                label={actionLoading ? 'Blocking...' : 'Block Member'}
                icon="pi pi-ban"
                size="small"
                severity="danger"
                outlined
                onClick={onToggleBlock}
                disabled={actionLoading}
                loading={actionLoading}
                className="text-xs"
              />
            )}

            {isVerified ? (
              <Button
                label="Revoke Verification"
                icon="pi pi-times-circle"
                size="small"
                outlined
                severity="secondary"
                onClick={() => onToggleStatus('unverify')}
                loading={actionLoading}
                className="text-xs"
              />
            ) : (
              <Button
                label="Verify Member"
                icon="pi pi-check"
                size="small"
                severity="success"
                onClick={() => onToggleStatus('verify')}
                loading={actionLoading}
                className="text-xs"
              />
            )}

            <Button
              label="Back"
              icon="pi pi-arrow-left"
              size="small"
              onClick={() => navigate('/admin/members')}
              className="p-button-outlined p-button-secondary text-xs"
            />
          </div>
        </div>

        {/* Quick Metrics Bar */}
        <div className="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2 pt-4 border-t border-slate-100 text-center">
          <div className="p-2.5 bg-slate-50/80 rounded-xl">
            <span className="text-lg font-extrabold text-blue-600 block">{counts.posts || 0}</span>
            <span className="text-[10px] font-bold uppercase text-slate-400">Posts</span>
          </div>
          <div className="p-2.5 bg-slate-50/80 rounded-xl">
            <span className="text-lg font-extrabold text-sky-600 block">{counts.stories || 0}</span>
            <span className="text-[10px] font-bold uppercase text-slate-400">Stories</span>
          </div>
          <div className="p-2.5 bg-slate-50/80 rounded-xl">
            <span className="text-lg font-extrabold text-indigo-600 block">{counts.communities || 0}</span>
            <span className="text-[10px] font-bold uppercase text-slate-400">Communities</span>
          </div>
          <div className="p-2.5 bg-slate-50/80 rounded-xl">
            <span className="text-lg font-extrabold text-emerald-600 block">{counts.products || 0}</span>
            <span className="text-[10px] font-bold uppercase text-slate-400">Marketplace</span>
          </div>
          <div className="p-2.5 bg-slate-50/80 rounded-xl">
            <span className="text-lg font-extrabold text-purple-600 block">{counts.events || 0}</span>
            <span className="text-[10px] font-bold uppercase text-slate-400">Events</span>
          </div>
          <div className="p-2.5 bg-slate-50/80 rounded-xl">
            <span className="text-lg font-extrabold text-red-600 block">{counts.reports || 0}</span>
            <span className="text-[10px] font-bold uppercase text-slate-400">Reports</span>
          </div>
          <button
            type="button"
            onClick={onOpenConnections}
            className="p-2.5 bg-blue-50 hover:bg-blue-100/80 rounded-xl transition-colors cursor-pointer text-center"
            title="Click to view all network connections"
          >
            <span className="text-lg font-extrabold text-slate-800 block flex items-center justify-center">
              {counts.connections || 0} <ExternalLink className="w-3 h-3 ml-1 text-blue-600" />
            </span>
            <span className="text-[10px] font-bold uppercase text-blue-600">Connections</span>
          </button>
        </div>
      </div>
    </div>
  );
}

export default MemberProfileBanner;
