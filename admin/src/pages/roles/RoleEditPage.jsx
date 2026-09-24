import { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { ArrowLeft, Save, Shield, Lock } from 'lucide-react';
import { InputText } from 'primereact/inputtext';
import { InputTextarea } from 'primereact/inputtextarea';
import { Button } from 'primereact/button';
import { PageHeader } from '../../components/common/PageHeader';
import { FormField } from '../../components/common/FormField';
import { FormSection } from '../../components/common/FormSection';
import { ErrorState } from '../../components/common/ErrorState';
import { PermissionGroupSelector } from './components/PermissionGroupSelector';
import { useToast } from '../../hooks/useToast';
import { rolesApi } from '../../api';

export function RoleEditPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { showSuccess, showError } = useToast();

  const [role, setRole] = useState(null);
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    permissions: [],
  });
  const [groupedPermissions, setGroupedPermissions] = useState({});
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [fetchError, setFetchError] = useState(null);

  const loadRole = () => {
    setLoading(true);
    setFetchError(null);
    rolesApi
      .getRoleEditData(id)
      .then((res) => {
        const roleData = res?.role || res?.data?.role || res;
        const perms = res?.permissions || res?.data?.permissions || {};

        setRole(roleData);
        setGroupedPermissions(perms);
        setFormData({
          name: roleData?.name || '',
          description: roleData?.description || '',
          permissions: (roleData?.permissions || []).map((p) => p.id),
        });
      })
      .catch((err) => {
        setFetchError(err.message || 'Failed to load role edit data.');
      })
      .finally(() => {
        setLoading(false);
      });
  };

  useEffect(() => {
    let isMounted = true;
    rolesApi
      .getRoleEditData(id)
      .then((res) => {
        if (!isMounted) return;
        const roleData = res?.role || res?.data?.role || res;
        const perms = res?.permissions || res?.data?.permissions || {};

        setRole(roleData);
        setGroupedPermissions(perms);
        setFormData({
          name: roleData?.name || '',
          description: roleData?.description || '',
          permissions: (roleData?.permissions || []).map((p) => p.id),
        });
      })
      .catch((err) => {
        if (!isMounted) return;
        setFetchError(err.message || 'Failed to load role edit data.');
      })
      .finally(() => {
        if (isMounted) setLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [id]);

  const handleChange = (field, value) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
    if (errors[field]) {
      setErrors((prev) => ({ ...prev, [field]: null }));
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setErrors({});

    try {
      await rolesApi.updateRole(id, formData);
      showSuccess(`Role "${formData.name}" updated successfully.`);
      navigate('/admin/roles');
    } catch (err) {
      if (err.errors) {
        setErrors(err.errors);
      }
      showError(err.message || 'Failed to update role.');
    } finally {
      setSubmitting(false);
    }
  };

  if (fetchError) {
    return (
      <div className="space-y-6">
        <PageHeader
          title="Edit Role"
          breadcrumbs={[{ label: 'Roles Directory', to: '/admin/roles' }, { label: 'Edit Role' }]}
        />
        <ErrorState
          title="Role Not Found"
          message={fetchError}
          onRetry={loadRole}
        />
      </div>
    );
  }

  const isSystemRole = Boolean(role?.is_system);

  return (
    <div className="space-y-6 max-w-5xl">
      {/* Page Header */}
      <PageHeader
        title={`Edit Role: ${role?.name || 'Loading...'}`}
        subtitle="Edit role details and assign module permissions."
        breadcrumbs={[
          { label: 'Roles Directory', to: '/admin/roles' },
          { label: role?.name || 'Edit Role' },
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

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Section 1: Role Information */}
        <FormSection
          icon={Shield}
          title="Role Identity & Protection"
          description="Edit role title and description. System-protected roles cannot be renamed."
        >
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <FormField
              label="Role Name"
              htmlFor="name"
              required={!isSystemRole}
              error={errors.name}
              helpText={isSystemRole ? 'Core system role name is locked and protected.' : 'Role title displayed across admin.'}
            >
              <div className="relative">
                <InputText
                  id="name"
                  value={formData.name}
                  onChange={(e) => handleChange('name', e.target.value)}
                  placeholder="Enter role title..."
                  className="w-full text-xs"
                  disabled={isSystemRole || submitting || loading}
                  required={!isSystemRole}
                />
                {isSystemRole && (
                  <Lock className="w-3.5 h-3.5 text-amber-500 absolute right-3 top-1/2 -translate-y-1/2" />
                )}
              </div>
            </FormField>

            <FormField
              label="Description"
              htmlFor="description"
              error={errors.description}
              helpText="Brief summary of duties and platform scope."
            >
              <InputTextarea
                id="description"
                value={formData.description}
                onChange={(e) => handleChange('description', e.target.value)}
                rows={2}
                placeholder="Describe role responsibilities..."
                className="w-full text-xs"
                disabled={submitting || loading}
              />
            </FormField>
          </div>
        </FormSection>

        {/* Section 2: Permission Assignments */}
        <FormSection
          icon={Shield}
          title="Assign Module Permissions"
          description="Grant specific granular access rules across platform features."
        >
          {loading ? (
            <div className="p-8 text-center text-xs text-slate-400">
              Loading permission settings...
            </div>
          ) : (
            <PermissionGroupSelector
              groupedPermissions={groupedPermissions}
              selectedPermissionIds={formData.permissions}
              onChange={(updated) => handleChange('permissions', updated)}
              disabled={submitting}
            />
          )}
        </FormSection>

        {/* Form Actions */}
        <div className="flex flex-wrap items-center justify-end gap-2.5 sm:gap-3 pt-4 border-t border-slate-200">
          <Button
            type="button"
            label="Cancel"
            size="small"
            onClick={() => navigate('/admin/roles')}
            disabled={submitting}
            className="p-button-outlined p-button-secondary text-xs"
          />
          <Button
            type="submit"
            label={submitting ? 'Saving Changes...' : 'Update Role Permissions'}
            icon={submitting ? 'pi pi-spin pi-spinner' : <Save className="w-3.5 h-3.5 mr-1.5" />}
            size="small"
            loading={submitting}
            className="p-button-primary text-xs"
          />
        </div>
      </form>
    </div>
  );
}

export default RoleEditPage;
