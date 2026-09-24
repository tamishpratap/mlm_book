import { Link } from 'react-router-dom';
import { Edit2, Trash2, Users, Key, Lock } from 'lucide-react';
import { EmptyState } from '../../../components/common/EmptyState';
import { confirmHelper } from '../../../utils/confirmHelper';

export function RoleTable({
  roles = [],
  onDeleteRole,
  actionLoading = false,
}) {
  const handleDelete = (role) => {
    if (role.is_system) return;
    confirmHelper.confirmDelete({
      header: 'Delete Custom Role',
      message: `Are you sure you want to permanently delete custom role "${role.name}"? This action cannot be undone.`,
      onAccept: () => onDeleteRole(role),
    });
  };

  if (roles.length === 0) {
    return (
      <EmptyState
        title="No Admin Roles Found"
        description="No administrative roles matched your search criteria."
      />
    );
  }

  return (
    <div className="overflow-x-auto border border-slate-200 rounded-2xl bg-white shadow-2xs">
      <table className="w-full text-left text-xs border-collapse">
        <thead>
          <tr className="bg-slate-50/90 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
            <th className="py-3 px-4">Role ID</th>
            <th className="py-3 px-4">Role Name & Description</th>
            <th className="py-3 px-4">Slug</th>
            <th className="py-3 px-4">Assigned Users</th>
            <th className="py-3 px-4">Permissions Count</th>
            <th className="py-3 px-4">Protection Status</th>
            <th className="py-3 px-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-100">
          {roles.map((role) => {
            const userCount = role.users?.length || 0;
            const permCount = role.permissions?.length || 0;
            const isSystem = Boolean(role.is_system);

            return (
              <tr key={role.id} className="hover:bg-slate-50/80 transition-colors">
                {/* ID */}
                <td className="py-3.5 px-4 font-mono font-bold text-blue-600 whitespace-nowrap">
                  #{role.id}
                </td>

                {/* Name & Description */}
                <td className="py-3.5 px-4 max-w-sm">
                  <div className="font-bold text-slate-900 text-xs">{role.name}</div>
                  <p className="text-slate-500 text-[11px] truncate mt-0.5">
                    {role.description || 'No description provided.'}
                  </p>
                </td>

                {/* Slug */}
                <td className="py-3.5 px-4 whitespace-nowrap">
                  <code className="text-xs font-mono font-semibold text-slate-700 bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200/60">
                    {role.slug}
                  </code>
                </td>

                {/* Assigned Users */}
                <td className="py-3.5 px-4 whitespace-nowrap">
                  <span className="inline-flex items-center text-xs font-bold text-blue-700 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-100">
                    <Users className="w-3.5 h-3.5 mr-1.5 text-blue-500" />
                    {userCount} Admins
                  </span>
                </td>

                {/* Permissions Count */}
                <td className="py-3.5 px-4 whitespace-nowrap">
                  <span className="inline-flex items-center text-xs font-bold text-sky-700 bg-sky-50 px-2.5 py-1 rounded-lg border border-sky-100">
                    <Key className="w-3.5 h-3.5 mr-1.5 text-sky-500" />
                    {isSystem && role.slug === 'super-admin' ? 'All (45) Permissions' : `${permCount} Permissions`}
                  </span>
                </td>

                {/* Protection Status */}
                <td className="py-3.5 px-4 whitespace-nowrap">
                  {isSystem ? (
                    <span className="inline-flex items-center text-[11px] font-bold text-amber-800 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200">
                      <Lock className="w-3 h-3 mr-1 text-amber-600" /> System Core
                    </span>
                  ) : (
                    <span className="inline-flex items-center text-[11px] font-bold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">
                      Custom Role
                    </span>
                  )}
                </td>

                {/* Actions */}
                <td className="py-3.5 px-4 text-right whitespace-nowrap">
                  <div className="inline-flex items-center justify-end gap-1.5 sm:gap-2">
                    <Link
                      to={`/admin/roles/${role.id}/edit`}
                      className="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition-colors border border-transparent hover:border-blue-200 inline-flex"
                      title="Edit Role Permissions"
                    >
                      <Edit2 className="w-3.5 h-3.5" />
                    </Link>

                    {!isSystem && (
                      <button
                        type="button"
                        onClick={() => handleDelete(role)}
                        disabled={actionLoading}
                        className="p-1.5 rounded-lg text-slate-500 hover:text-red-600 hover:bg-red-50 transition-colors border border-transparent hover:border-red-200"
                        title="Delete Role"
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                      </button>
                    )}
                  </div>
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
}

export default RoleTable;
