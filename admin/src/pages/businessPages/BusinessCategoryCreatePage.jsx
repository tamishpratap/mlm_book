import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { ArrowLeft, PlusCircle } from 'lucide-react';
import { Button } from 'primereact/button';
import { PageHeader } from '../../components/common/PageHeader';
import { CategoryForm } from './components/CategoryForm';
import { useToast } from '../../hooks/useToast';
import { businessPagesApi } from '../../api';

/**
 * BusinessCategoryCreatePage
 * Standalone page for creating new Business Page Categories.
 * Provides a dedicated route (/admin/business-pages/categories/create)
 * with full breadcrumb navigation, validation, and layout integration.
 */
export function BusinessCategoryCreatePage() {
  const navigate = useNavigate();
  const { showSuccess, showError } = useToast();

  const [formData, setFormData] = useState({
    name: '',
    slug: '',
    description: '',
    order: 0,
    is_active: true,
  });
  const [formErrors, setFormErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  const handleFieldChange = (field, value) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
    if (formErrors[field]) {
      setFormErrors((prev) => ({ ...prev, [field]: null }));
    }
  };

  const handleSubmit = async (e) => {
    if (e && e.preventDefault) {
      e.preventDefault();
    }

    const trimmedName = (formData.name || '').trim();
    const clientErrors = {};

    if (!trimmedName) {
      clientErrors.name = 'Category name is required.';
    } else if (trimmedName.length < 2) {
      clientErrors.name = 'Category name must be at least 2 characters.';
    }

    if (Object.keys(clientErrors).length > 0) {
      setFormErrors(clientErrors);
      showError('Please correct the validation errors in the form.');
      return;
    }

    setSubmitting(true);
    setFormErrors({});

    try {
      const payload = {
        name: trimmedName,
        slug: (formData.slug || '').trim() || undefined,
        description: (formData.description || '').trim() || null,
        order: parseInt(formData.order, 10) || 0,
        is_active: Boolean(formData.is_active),
      };

      const res = await businessPagesApi.createCategory(payload);
      showSuccess(res?.message || `Category "${trimmedName}" created successfully.`);
      navigate('/admin/business-pages/categories');
    } catch (err) {
      console.error('Failed to create category:', err);
      // Map Laravel 422 validation errors if available
      const rawErrors = err?.errors || err?.response?.data?.errors;
      if (rawErrors && typeof rawErrors === 'object') {
        const mappedErrors = {};
        Object.keys(rawErrors).forEach((key) => {
          const val = rawErrors[key];
          mappedErrors[key] = Array.isArray(val) ? val[0] : String(val);
        });
        setFormErrors(mappedErrors);
      }

      showError(err?.message || 'Failed to create business page category.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleCancel = () => {
    navigate('/admin/business-pages/categories');
  };

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <PageHeader
        title="Add Business Category"
        subtitle="Create a new classification category for the business pages directory."
        breadcrumbs={[
          { label: 'Business Pages', to: '/admin/business-pages' },
          { label: 'Categories', to: '/admin/business-pages/categories' },
          { label: 'Create' },
        ]}
        actions={
          <Link to="/admin/business-pages/categories">
            <Button
              label="Back to Categories"
              icon={<ArrowLeft className="w-3.5 h-3.5 mr-1.5" />}
              className="p-button-outlined p-button-secondary text-xs"
            />
          </Link>
        }
      />

      {/* Main Content Form Card */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-2xs p-5 sm:p-7 max-w-2xl">
        <div className="flex items-center gap-2 mb-5 pb-4 border-b border-slate-100">
          <div className="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
            <PlusCircle className="w-4 h-4" />
          </div>
          <div>
            <h2 className="text-sm font-bold text-slate-800">Category Details</h2>
            <p className="text-[11px] text-slate-500">
              Provide the taxonomy information and display settings for this category.
            </p>
          </div>
        </div>

        <CategoryForm
          formData={formData}
          onChange={handleFieldChange}
          errors={formErrors}
          onSubmit={handleSubmit}
          onCancel={handleCancel}
          submitting={submitting}
          isEdit={false}
          showCancel={true}
          submitLabel="Create Category"
          cancelLabel="Back to Categories"
        />
      </div>
    </div>
  );
}

export default BusinessCategoryCreatePage;
