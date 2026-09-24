import { Link } from 'react-router-dom';
import { UserPlus, Eye, ArrowRight } from 'lucide-react';
import { StatusBadge } from '../../../components/common/StatusBadge';

export function RecentMembersTable({ members = [] }) {
  const hasMembers = members && members.length > 0;

  return (
    <div className="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden flex flex-col justify-between h-full">
      {/* Header */}
      <div className="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center">
          <UserPlus className="w-4 h-4 text-blue-600 mr-2" /> Recently Registered Members
        </h4>
        <Link
          to="/admin/members"
          className="text-xs font-semibold text-blue-600 hover:text-blue-700 inline-flex items-center"
        >
          View All <ArrowRight className="w-3.5 h-3.5 ml-1" />
        </Link>
      </div>

      {/* Body Table */}
      <div className="overflow-x-auto flex-1">
        {!hasMembers ? (
          <div className="p-8 text-center text-xs text-slate-400">
            No platform members registered yet.
          </div>
        ) : (
          <table className="w-full text-left border-collapse text-xs">
            <thead>
              <tr className="bg-slate-50/80 border-b border-slate-100 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                <th className="py-2.5 px-4">Member</th>
                <th className="py-2.5 px-4">User ID</th>
                <th className="py-2.5 px-4">Verification</th>
                <th className="py-2.5 px-4">Joined</th>
                <th className="py-2.5 px-4 text-right">Action</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {members.map((member, idx) => {
                const isVerified = Boolean(member.mobile_verified_at || member.is_verified || member.status === 'verified');
                const displayName = member.name || 'Anonymous Member';
                const initial = displayName.charAt(0).toUpperCase();

                return (
                  <tr key={member.id || idx} className="hover:bg-slate-50/80 transition-colors">
                    <td className="py-2.5 px-4">
                      <div className="flex items-center space-x-2.5">
                        {member.avatar_url || member.profile_photo ? (
                          <img
                            src={member.avatar_url || member.profile_photo}
                            alt={displayName}
                            className="w-7 h-7 rounded-full object-cover ring-1 ring-slate-200"
                            onError={(e) => {
                              e.target.style.display = 'none';
                            }}
                          />
                        ) : (
                          <div className="w-7 h-7 rounded-full bg-blue-100 text-blue-700 font-bold text-xs flex items-center justify-center">
                            {initial}
                          </div>
                        )}
                        <div>
                          <span className="font-semibold text-slate-800 block truncate max-w-[120px] sm:max-w-[160px]">
                            {displayName}
                          </span>
                          <span className="text-[10px] text-slate-400 block">
                            {member.city ? `${member.city}, ${member.country || ''}` : (member.country || 'Worldwide')}
                          </span>
                        </div>
                      </div>
                    </td>

                    <td className="py-2.5 px-4">
                      <code className="text-[11px] font-mono text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded-sm border border-blue-100">
                        {member.user_id || `#${member.id}`}
                      </code>
                    </td>

                    <td className="py-2.5 px-4">
                      <StatusBadge
                        status={isVerified ? 'verified' : 'unverified'}
                        label={isVerified ? 'VERIFIED MEMBER' : 'UNVERIFIED MEMBER'}
                      />
                    </td>

                    <td className="py-2.5 px-4 text-slate-500 whitespace-nowrap">
                      {member.created_at_human || member.joined || 'Recently'}
                    </td>

                    <td className="py-2.5 px-4 text-right">
                      <Link
                        to={`/admin/members/${member.id}`}
                        className="inline-flex p-1.5 rounded-md text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                        title="Inspect Profile"
                      >
                        <Eye className="w-4 h-4" />
                      </Link>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        )}
      </div>
    </div>
  );
}

export default RecentMembersTable;
