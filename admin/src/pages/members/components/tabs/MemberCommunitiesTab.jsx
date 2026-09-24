import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Eye, UserX } from 'lucide-react';
import { StatusBadge } from '../../../../components/common/StatusBadge';
import { EmptyState } from '../../../../components/common/EmptyState';
import { confirmHelper } from '../../../../utils/confirmHelper';
import { membersApi } from '../../../../api';
import { useToast } from '../../../../hooks/useToast';

export function MemberCommunitiesTab({
  communities = [],
  member,
  onRefresh,
}) {
  const { showSuccess, showError } = useToast();
  const [actionLoading, setActionLoading] = useState(false);

  const handleRemoveMembership = (community) => {
    confirmHelper.confirm({
      header: 'Remove Community Membership',
      message: `Are you sure you want to remove member ${member?.name} from community "${community.name}"?`,
      acceptLabel: 'Remove Membership',
      acceptClassName: 'p-button-danger text-xs',
      onAccept: async () => {
        setActionLoading(true);
        try {
          await membersApi.removeCommunity(member.id, community.id);
          showSuccess(`Removed membership from ${community.name}.`);
          onRefresh();
        } catch (err) {
          showError(err.message || 'Failed to remove membership.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  if (communities.length === 0) {
    return (
      <EmptyState
        title="No Communities Joined"
        description="This member has not joined any groups or communities."
      />
    );
  }

  return (
    <div className="pt-2">
      <div className="overflow-x-auto border border-slate-200 rounded-xl bg-white">
        <table className="w-full text-left text-xs border-collapse">
          <thead>
            <tr className="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
              <th className="py-3 px-4">Community Name</th>
              <th className="py-3 px-4">Role</th>
              <th className="py-3 px-4">Status</th>
              <th className="py-3 px-4">Joined Date</th>
              <th className="py-3 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {communities.map((community, idx) => {
              const role = community.pivot?.role || community.role || 'member';
              const status = community.pivot?.status || community.status || 'accepted';

              return (
                <tr key={community.id || idx} className="hover:bg-slate-50/80 transition-colors">
                  <td className="py-3 px-4 font-bold text-slate-900 whitespace-nowrap">
                    {community.name}
                  </td>
                  <td className="py-3 px-4 whitespace-nowrap">
                    <span className="bg-blue-50 text-blue-700 font-semibold px-2 py-0.5 rounded-md text-[10px] uppercase">
                      {role}
                    </span>
                  </td>
                  <td className="py-3 px-4 whitespace-nowrap">
                    <StatusBadge status={status} />
                  </td>
                  <td className="py-3 px-4 text-slate-500 whitespace-nowrap">
                    {community.joined_at_human || community.created_at_human || 'Recently'}
                  </td>
                  <td className="py-3 px-4 text-right whitespace-nowrap">
                    <div className="inline-flex items-center justify-end gap-1.5 sm:gap-2">
                      <Link
                        to={`/admin/communities/${community.id}`}
                        className="p-1.5 rounded-md text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition-colors inline-flex"
                        title="View Community Details"
                      >
                        <Eye className="w-3.5 h-3.5" />
                      </Link>

                      <button
                        type="button"
                        onClick={() => handleRemoveMembership(community)}
                        disabled={actionLoading}
                        className="p-1.5 rounded-md text-slate-500 hover:text-red-600 hover:bg-red-50 transition-colors"
                        title="Remove Membership"
                      >
                        <UserX className="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    </div>
  );
}

export default MemberCommunitiesTab;
