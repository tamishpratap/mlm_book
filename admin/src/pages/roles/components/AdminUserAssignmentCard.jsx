import { useState } from 'react';
import { Users, Check } from 'lucide-react';
import { Dropdown } from 'primereact/dropdown';
import { Button } from 'primereact/button';
import { rolesApi } from '../../../api';
import { useToast } from '../../../hooks/useToast';

export function AdminUserAssignmentCard({
  adminUsers = [],
  roles = [],
  onAssigned,
}) {
  const { showSuccess, showError } = useToast();
  const [selectedRoles, setSelectedRoles] = useState({});
  const [submittingUser, setSubmittingUser] = useState(null);

  const handleRoleChange = (userId, roleId) => {
    setSelectedRoles((prev) => ({
      ...prev,
      [userId]: roleId,
    }));
  };

  const handleAssign = async (userId) => {
    const roleId = selectedRoles[userId];
    if (!roleId) {
      showError('Please select a role to assign.');
      return;
    }

    setSubmittingUser(userId);
    try {
      await rolesApi.assignUserRole(userId, roleId);
      showSuccess('Role assigned successfully.');
      onAssigned();
    } catch (err) {
      showError(err.message || 'Failed to assign role to admin.');
    } finally {
      setSubmittingUser(null);
    }
  };

  const roleOptions = roles.map((r) => ({
    label: r.name,
    value: r.id,
  }));

  return (
    <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden mt-6">
      <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <div className="flex items-center space-x-2">
          <Users className="w-4 h-4 text-blue-600" />
          <h3 className="text-sm font-bold text-slate-800">Admin User Role Assignment</h3>
        </div>
        <span className="text-xs text-slate-400 font-medium">
          {adminUsers.length} Administrators
        </span>
      </div>

      <div className="overflow-x-auto">
        <table className="w-full text-left text-xs border-collapse">
          <thead>
            <tr className="bg-slate-50/80 border-b border-slate-100 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
              <th className="py-3 px-6">Admin Name</th>
              <th className="py-3 px-6">Email</th>
              <th className="py-3 px-6">Current Role</th>
              <th className="py-3 px-6 text-right">Assign New Role</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {adminUsers.map((user) => {
              const currentRole = user.roles?.[0];
              const selectedRoleVal = selectedRoles[user.id] ?? currentRole?.id ?? '';
              const isSubmitting = submittingUser === user.id;

              return (
                <tr key={user.id} className="hover:bg-slate-50/60 transition-colors">
                  <td className="py-3.5 px-6 font-bold text-slate-900 whitespace-nowrap">
                    {user.name}
                  </td>
                  <td className="py-3.5 px-6 whitespace-nowrap">
                    <code className="text-xs font-mono text-slate-600 bg-slate-100 px-2 py-0.5 rounded">
                      {user.email}
                    </code>
                  </td>
                  <td className="py-3.5 px-6 whitespace-nowrap">
                    {user.roles && user.roles.length > 0 ? (
                      user.roles.map((r) => (
                        <span
                          key={r.id}
                          className="inline-block text-[11px] font-bold text-blue-700 bg-blue-50 border border-blue-100 px-2.5 py-0.5 rounded-full mr-1.5"
                        >
                          {r.name}
                        </span>
                      ))
                    ) : (
                      <span className="text-[11px] text-slate-400 font-semibold bg-slate-100 px-2 py-0.5 rounded">
                        No Role Assigned
                      </span>
                    )}
                  </td>
                  <td className="py-3.5 px-6 text-right whitespace-nowrap">
                    <div className="inline-flex items-center justify-end gap-2">
                      <Dropdown
                        value={selectedRoleVal}
                        options={roleOptions}
                        onChange={(e) => handleRoleChange(user.id, e.value)}
                        placeholder="Select Role"
                        className="text-xs w-44"
                        disabled={isSubmitting}
                      />
                      <Button
                        label="Assign"
                        icon={isSubmitting ? 'pi pi-spin pi-spinner' : <Check className="w-3 h-3 mr-1" />}
                        size="small"
                        severity="success"
                        outlined
                        onClick={() => handleAssign(user.id)}
                        loading={isSubmitting}
                        className="text-xs"
                      />
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

export default AdminUserAssignmentCard;
