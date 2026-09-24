import { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { ArrowLeft, Save, Shield, Folder, CheckSquare, Square } from 'lucide-react';
import { Button } from 'primereact/button';
import { Checkbox } from 'primereact/checkbox';
import { PageHeader } from '../../components/common/PageHeader';
import { ErrorState } from '../../components/common/ErrorState';
import { MatrixSkeleton } from './components/MatrixSkeleton';
import { useToast } from '../../hooks/useToast';
import { rolesApi } from '../../api';

export function PermissionMatrixPage() {
  const { showSuccess, showError } = useToast();

  const [roles, setRoles] = useState([]);
  const [permissions, setPermissions] = useState({});
  const [matrixState, setMatrixState] = useState({}); // { [roleId]: { [permId]: boolean } }
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);

  const fetchMatrixData = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await rolesApi.getMatrix();
      const rolesList = res?.roles || res?.data?.roles || [];
      const permsGrouped = res?.permissions || res?.data?.permissions || {};

      setRoles(rolesList);
      setPermissions(permsGrouped);

      // Build matrix state from existing relationships
      const initialMatrix = {};
      rolesList.forEach((role) => {
        initialMatrix[role.id] = {};
        const rolePermSlugs = new Set((role.permissions || []).map((p) => p.slug));

        Object.values(permsGrouped).forEach((groupPerms) => {
          groupPerms.forEach((perm) => {
            const hasPerm = role.is_system && role.slug === 'super-admin'
              ? true
              : rolePermSlugs.has(perm.slug);
            initialMatrix[role.id][perm.id] = Boolean(hasPerm);
          });
        });
      });

      setMatrixState(initialMatrix);
    } catch (err) {
      setError(err.message || 'Failed to load permission matrix data.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    let isMounted = true;
    rolesApi.getMatrix()
      .then((res) => {
        if (!isMounted) return;
        const rolesList = res?.roles || res?.data?.roles || [];
        const permsGrouped = res?.permissions || res?.data?.permissions || {};

        setRoles(rolesList);
        setPermissions(permsGrouped);

        const initialMatrix = {};
        rolesList.forEach((role) => {
          initialMatrix[role.id] = {};
          const rolePermSlugs = new Set((role.permissions || []).map((p) => p.slug));

          Object.values(permsGrouped).forEach((groupPerms) => {
            groupPerms.forEach((perm) => {
              const hasPerm = role.is_system && role.slug === 'super-admin'
                ? true
                : rolePermSlugs.has(perm.slug);
              initialMatrix[role.id][perm.id] = Boolean(hasPerm);
            });
          });
        });

        setMatrixState(initialMatrix);
      })
      .catch((err) => {
        if (!isMounted) return;
        setError(err.message || 'Failed to load permission matrix data.');
      })
      .finally(() => {
        if (isMounted) setLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, []);

  const handleToggleCell = (roleId, permId, disabled) => {
    if (disabled || saving) return;

    setMatrixState((prev) => ({
      ...prev,
      [roleId]: {
        ...prev[roleId],
        [permId]: !prev[roleId]?.[permId],
      },
    }));
  };

  const handleToggleGroupForRole = (roleId, groupPerms, disabled) => {
    if (disabled || saving) return;

    const groupPermIds = groupPerms.map((p) => p.id);
    const allChecked = groupPermIds.every((id) => matrixState[roleId]?.[id]);

    setMatrixState((prev) => {
      const updatedRoleState = { ...prev[roleId] };
      groupPermIds.forEach((id) => {
        updatedRoleState[id] = !allChecked;
      });
      return {
        ...prev,
        [roleId]: updatedRoleState,
      };
    });
  };

  const handleSaveMatrix = async (e) => {
    e.preventDefault();
    setSaving(true);
    try {
      // Build backend payload matching updateMatrix: matrix[role_id][perm_id] = 1
      const payloadMatrix = {};
      roles.forEach((role) => {
        payloadMatrix[role.id] = {};
        const roleState = matrixState[role.id] || {};
        Object.entries(roleState).forEach(([permId, isChecked]) => {
          if (isChecked) {
            payloadMatrix[role.id][permId] = 1;
          }
        });
      });

      await rolesApi.updateMatrix(payloadMatrix);
      showSuccess('Permission matrix updated successfully.');
      fetchMatrixData();
    } catch (err) {
      showError(err.message || 'Failed to save permission matrix.');
    } finally {
      setSaving(false);
    }
  };

  const groupKeys = Object.keys(permissions);

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <PageHeader
        title="Role Permission Matrix"
        subtitle="Interactive matrix to review and bulk assign permissions across all admin roles."
        breadcrumbs={[
          { label: 'Roles Directory', to: '/admin/roles' },
          { label: 'Interactive Matrix' },
        ]}
        actions={
          <Link to="/admin/roles">
            <Button
              label="Back to Roles"
              icon={<ArrowLeft className="w-3.5 h-3.5 mr-1.5" />}
              size="small"
              className="p-button-outlined p-button-secondary text-xs"
            />
          </Link>
        }
      />

      {loading ? (
        <MatrixSkeleton />
      ) : error ? (
        <ErrorState
          title="Matrix Data Unavailable"
          message={error}
          onRetry={fetchMatrixData}
        />
      ) : (
        <form onSubmit={handleSaveMatrix} className="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
          <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div className="flex items-center space-x-2">
              <Shield className="w-4 h-4 text-blue-600" />
              <h3 className="text-sm font-bold text-slate-800">Global Access Matrix</h3>
            </div>
            <div className="flex items-center space-x-2">
              <Button
                type="submit"
                label={saving ? 'Saving Changes...' : 'Save Permission Matrix'}
                icon={saving ? 'pi pi-spin pi-spinner' : <Save className="w-3.5 h-3.5 mr-1.5" />}
                size="small"
                loading={saving}
                className="p-button-primary text-xs"
              />
            </div>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs border-collapse">
              <thead>
                <tr className="bg-blue-900 text-white text-[11px] font-bold uppercase tracking-wider">
                  <th className="py-3 px-6 min-w-[260px]">Module / Permission Rule</th>
                  {roles.map((role) => (
                    <th key={role.id} className="py-3 px-4 text-center min-w-[150px]">
                      <div className="font-bold text-white text-xs">{role.name}</div>
                      <code className="text-[10px] text-blue-200 font-mono block mt-0.5 lowercase">
                        {role.slug}
                      </code>
                    </th>
                  ))}
                </tr>
              </thead>
              {groupKeys.map((groupKey) => {
                  const groupPerms = permissions[groupKey] || [];
                  const groupTitle = groupKey.replace(/_/g, ' ');

                  return (
                    <tbody key={groupKey} className="border-b border-slate-200">
                      {/* Group Header Row */}
                      <tr className="bg-slate-100/90 font-bold text-slate-800">
                        <td className="py-2.5 px-6 uppercase text-[11px] tracking-wider text-blue-800 flex items-center">
                          <Folder className="w-3.5 h-3.5 mr-1.5 text-blue-600" />
                          Module Group: {groupTitle}
                        </td>
                        {roles.map((role) => {
                          const isSuperAdmin = Boolean(role.is_system && role.slug === 'super-admin');
                          const allChecked = groupPerms.every((p) => matrixState[role.id]?.[p.id]);

                          return (
                            <td key={role.id} className="py-2 px-4 text-center bg-slate-100/60">
                              {!isSuperAdmin && (
                                <button
                                  type="button"
                                  onClick={() => handleToggleGroupForRole(role.id, groupPerms, isSuperAdmin)}
                                  className="text-[10px] font-semibold text-blue-600 hover:text-blue-800 inline-flex items-center space-x-1"
                                  title={`Toggle all ${groupTitle} for ${role.name}`}
                                >
                                  {allChecked ? (
                                    <CheckSquare className="w-3 h-3 text-blue-600" />
                                  ) : (
                                    <Square className="w-3 h-3 text-slate-400" />
                                  )}
                                  <span>Toggle All</span>
                                </button>
                              )}
                            </td>
                          );
                        })}
                      </tr>

                      {/* Individual Permission Rows */}
                      {groupPerms.map((perm) => (
                        <tr key={perm.id} className="hover:bg-slate-50/80 transition-colors">
                          <td className="py-3 px-6">
                            <div className="font-semibold text-slate-900">{perm.name}</div>
                            <code className="text-[10px] font-mono text-slate-400">
                              {perm.slug}
                            </code>
                          </td>

                          {roles.map((role) => {
                            const isSuperAdmin = Boolean(role.is_system && role.slug === 'super-admin');
                            const isChecked = isSuperAdmin ? true : Boolean(matrixState[role.id]?.[perm.id]);

                            return (
                              <td
                                key={role.id}
                                className="py-3 px-4 text-center cursor-pointer select-none"
                                onClick={() => handleToggleCell(role.id, perm.id, isSuperAdmin)}
                              >
                                <Checkbox
                                  checked={isChecked}
                                  onChange={() => handleToggleCell(role.id, perm.id, isSuperAdmin)}
                                  disabled={isSuperAdmin || saving}
                                />
                              </td>
                            );
                          })}
                        </tr>
                      ))}
                    </tbody>
                  );
                })}
            </table>
          </div>

          <div className="px-6 py-4 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
            <span className="text-xs text-slate-500">
              * Super Admin permissions are core-protected and automatically enabled across all platform modules.
            </span>
            <Button
              type="submit"
              label={saving ? 'Saving Changes...' : 'Save Permission Matrix'}
              icon={saving ? 'pi pi-spin pi-spinner' : <Save className="w-3.5 h-3.5 mr-1.5" />}
              size="small"
              loading={saving}
              className="p-button-primary text-xs"
            />
          </div>
        </form>
      )}
    </div>
  );
}

export default PermissionMatrixPage;
