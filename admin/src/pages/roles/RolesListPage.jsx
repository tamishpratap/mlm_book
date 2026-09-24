import { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { Shield, Plus, Grid } from 'lucide-react';
import { Button } from 'primereact/button';
import { PageHeader } from '../../components/common/PageHeader';
import { ErrorState } from '../../components/common/ErrorState';
import { useToast } from '../../hooks/useToast';
import { rolesApi } from '../../api';

// Subcomponents
import { RoleStatsCards } from './components/RoleStatsCards';
import { RoleFilterBar } from './components/RoleFilterBar';
import { RoleTable } from './components/RoleTable';
import { AdminUserAssignmentCard } from './components/AdminUserAssignmentCard';
import { RoleSkeleton } from './components/RoleSkeleton';

export function RolesListPage() {
  const { showSuccess, showError } = useToast();

  const [roles, setRoles] = useState([]);
  const [adminUsers, setAdminUsers] = useState([]);
  const [stats, setStats] = useState({
    totalRoles: 0,
    customRoles: 0,
    totalUsersAssigned: 0,
    totalPermissions: 45,
  });
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [error, setError] = useState(null);

  const fetchRolesData = useCallback(async (searchQuery = '') => {
    setLoading(true);
    setError(null);
    try {
      const params = {};
      if (searchQuery) params.q = searchQuery;

      const res = await rolesApi.getRoles(params);
      const rolesList = res?.roles || res?.data?.roles || (Array.isArray(res) ? res : []);
      setRoles(rolesList);
      setAdminUsers(res?.adminUsers || res?.data?.adminUsers || []);
      setStats({
        totalRoles: res?.totalRoles ?? rolesList.length,
        customRoles: res?.customRoles ?? rolesList.filter((r) => !r.is_system).length,
        totalUsersAssigned: res?.totalUsersAssigned ?? res?.adminUsers?.length ?? 0,
        totalPermissions: res?.totalPermissions ?? 45,
      });
    } catch (err) {
      setError(err.message || 'Failed to load roles and permissions directory.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    let isMounted = true;
    rolesApi.getRoles({ q: search })
      .then((res) => {
        if (!isMounted) return;
        const rolesList = res?.roles || res?.data?.roles || (Array.isArray(res) ? res : []);
        setRoles(rolesList);
        setAdminUsers(res?.adminUsers || res?.data?.adminUsers || []);
        setStats({
          totalRoles: res?.totalRoles ?? rolesList.length,
          customRoles: res?.customRoles ?? rolesList.filter((r) => !r.is_system).length,
          totalUsersAssigned: res?.totalUsersAssigned ?? res?.adminUsers?.length ?? 0,
          totalPermissions: res?.totalPermissions ?? 45,
        });
      })
      .catch((err) => {
        if (!isMounted) return;
        setError(err.message || 'Failed to load roles and permissions directory.');
      })
      .finally(() => {
        if (isMounted) setLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [search]);

  const handleFilter = (query) => {
    setSearch(query);
  };

  const handleDeleteRole = async (role) => {
    setActionLoading(true);
    try {
      await rolesApi.deleteRole(role.id);
      showSuccess(`Role "${role.name}" deleted successfully.`);
      fetchRolesData(search);
    } catch (err) {
      showError(err.message || 'Failed to delete role.');
    } finally {
      setActionLoading(false);
    }
  };

  if (loading && roles.length === 0) {
    return <RoleSkeleton />;
  }

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <PageHeader
        title="Roles & Permissions (RBAC)"
        subtitle="Manage enterprise admin roles, permission matrix, and user access control."
        breadcrumbs={[{ label: 'System Access', to: '/admin/roles' }, { label: 'Roles Directory' }]}
        actions={
          <div className="flex flex-wrap items-center gap-2.5 sm:gap-3">
            <Link to="/admin/roles/matrix" className="inline-flex">
              <Button
                label="Permission Matrix"
                icon={<Grid className="w-3.5 h-3.5 mr-1.5" />}
                size="small"
                className="p-button-outlined p-button-secondary text-xs"
              />
            </Link>
            <Link to="/admin/roles/create" className="inline-flex">
              <Button
                label="Create Custom Role"
                icon={<Plus className="w-3.5 h-3.5 mr-1.5" />}
                size="small"
                className="p-button-primary text-xs"
              />
            </Link>
          </div>
        }
      />

      {error ? (
        <ErrorState
          title="Failed to Load Roles"
          message={error}
          onRetry={() => fetchRolesData(search)}
        />
      ) : (
        <>
          {/* Summary Stat Cards */}
          <RoleStatsCards stats={stats} loading={loading} />

          {/* Roles Directory Card */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 sm:p-6">
            <div className="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
              <div className="flex items-center space-x-2">
                <Shield className="w-4 h-4 text-blue-600" />
                <h3 className="text-sm font-bold text-slate-800">Admin Roles Directory</h3>
              </div>
              <span className="text-xs text-slate-400 font-medium">
                {roles.length} Roles Defined
              </span>
            </div>

            {/* Filter Bar */}
            <RoleFilterBar
              initialSearch={search}
              onFilter={handleFilter}
              loading={loading}
            />

            {/* Roles Table */}
            <RoleTable
              roles={roles}
              onDeleteRole={handleDeleteRole}
              actionLoading={actionLoading}
            />
          </div>

          {/* Admin User Role Assignment Card */}
          {adminUsers.length > 0 && (
            <AdminUserAssignmentCard
              adminUsers={adminUsers}
              roles={roles}
              onAssigned={() => fetchRolesData(search)}
            />
          )}
        </>
      )}
    </div>
  );
}

export default RolesListPage;
