import { useState, useEffect } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { ArrowLeft, Save, Shield } from 'lucide-react';
import { InputText } from 'primereact/inputtext';
import { InputTextarea } from 'primereact/inputtextarea';
import { Button } from 'primereact/button';
import { PageHeader } from '../../components/common/PageHeader';
import { FormField } from '../../components/common/FormField';
import { FormSection } from '../../components/common/FormSection';
import { PermissionGroupSelector } from './components/PermissionGroupSelector';
import { useToast } from '../../hooks/useToast';
import { rolesApi } from '../../api';

export function RoleCreatePage() {
  const navigate = useNavigate();
  const { showSuccess, showError } = useToast();

  const [formData, setFormData] = useState({
    name: '',
    description: '',
    permissions: [],
  });
  const [groupedPermissions, setGroupedPermissions] = useState({});
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    let isMounted = true;
    rolesApi.getCreateData()
      .then((res) => {
        if (!isMounted) return;
        const perms = res?.permissions || res?.data?.permissions || {};
        setGroupedPermissions(perms);
      })
      .catch((err) => {
        if (!isMounted) return;
        showError(err.message || 'Failed to load permission options.');
      })
      .finally(() => {
        if (isMounted) setLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [showError]);

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
      await rolesApi.createRole(formData);
      showSuccess(`Custom role "${formData.name}" created successfully.`);
      navigate('/admin/roles');
    } catch (err) {
      if (err.errors) {
        setErrors(err.errors);
      }
      showError(err.message || 'Failed to create custom role.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="space-y-6 max-w-5xl">
      {/* Page Header */}
      <PageHeader
        title="Create Custom Role"
        subtitle="Define new custom admin role and assign module permissions."
        breadcrumbs={[
          { label: 'Roles Directory', to: '/admin/roles' },
          { label: 'Create Role' },
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
          title="Role Identity & Description"
          description="Specify unique role title and functional description."
        >
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <FormField
              label="Role Name"
              htmlFor="name"
              required
              error={errors.name}
              helpText="e.g. Content Manager, Finance Auditor"
            >
              <InputText
                id="name"
                value={formData.name}
                onChange={(e) => handleChange('name', e.target.value)}
                placeholder="Enter role title..."
                className="w-full text-xs"
                disabled={submitting}
                required
              />
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
                disabled={submitting}
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
              Loading permission groups...
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
            label={submitting ? 'Creating Role...' : 'Create Role'}
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

export default RoleCreatePage;
