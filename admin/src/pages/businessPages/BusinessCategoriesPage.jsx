import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import {
  Building,
  Search,
  Plus,
  Edit2,
  Trash2,
  CheckCircle,
  Slash,
} from 'lucide-react';
import { Button } from 'primereact/button';
import { Dropdown } from 'primereact/dropdown';
import { Dialog } from 'primereact/dialog';
import { AdminSearchInput } from '../../components/common/AdminSearchInput';
import { PageHeader } from '../../components/common/PageHeader';
import { StatusBadge } from '../../components/common/StatusBadge';
import { EmptyState } from '../../components/common/EmptyState';
import { ErrorState } from '../../components/common/ErrorState';
import { LoadingSpinner } from '../../components/common/LoadingSpinner';
import { CategoryForm } from './components/CategoryForm';
import { useToast } from '../../hooks/useToast';
import { confirmHelper } from '../../utils/confirmHelper';
import { businessPagesApi } from '../../api';

/**
 * BusinessCategoriesPage
 * Category management page for Business Pages.
 * Displays all categories, active/disabled states, live business page counts,
 * and allows searching, filtering, creating, editing, and deleting categories.
 */
export function BusinessCategoriesPage() {
  const { showSuccess, showError } = useToast();

  const [categories, setCategories] = useState([]);
  const [categoryPageCounts, setCategoryPageCounts] = useState({});
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Dialog State
  const [modalOpen, setModalOpen] = useState(false);
  const [editingCategory, setEditingCategory] = useState(null);
  const [formData, setFormData] = useState({
    name: '',
    slug: '',
    description: '',
    order: 0,
    is_active: true,
  });
  const [formErrors, setFormErrors] = useState({});
  const [saving, setSaving] = useState(false);

  const fetchCategories = () => {
    setLoading(true);
    setError(null);
    const params = {
      q: search || undefined,
      status: status || undefined,
    };
    businessPagesApi
      .getCategories(params)
      .then((res) => {
        const items =
          res?.categories?.data ||
          res?.data ||
          (Array.isArray(res?.categories) ? res.categories : null) ||
          (Array.isArray(res) ? res : []);
        setCategories(Array.isArray(items) ? items : []);
        setCategoryPageCounts(
          res?.categoryPageCounts && typeof res.categoryPageCounts === 'object'
            ? res.categoryPageCounts
            : {}
        );
      })
      .catch((err) => {
        setError(err?.message || 'Failed to load business categories.');
      })
      .finally(() => {
        setLoading(false);
      });
  };

  useEffect(() => {
    let isMounted = true;
    businessPagesApi
      .getCategories({})
      .then((res) => {
        if (!isMounted) return;
        const items =
          res?.categories?.data ||
          res?.data ||
          (Array.isArray(res?.categories) ? res.categories : null) ||
          (Array.isArray(res) ? res : []);
        setCategories(Array.isArray(items) ? items : []);
        setCategoryPageCounts(
          res?.categoryPageCounts && typeof res.categoryPageCounts === 'object'
            ? res.categoryPageCounts
            : {}
        );
      })
      .catch((err) => {
        if (!isMounted) return;
        setError(err?.message || 'Failed to load business categories.');
      })
      .finally(() => {
        if (isMounted) setLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, []);

  // Safe client-side filtered categories
  const filteredCategories = (categories || []).filter((cat) => {
    if (!cat) return false;
    if (status === 'active' && !cat.is_active) return false;
    if (status === 'inactive' && cat.is_active) return false;
    if (search && search.trim()) {
      const q = search.trim().toLowerCase();
      const nameMatches = (cat.name || '').toLowerCase().includes(q);
      const slugMatches = (cat.slug || '').toLowerCase().includes(q);
      const descMatches = (cat.description || '').toLowerCase().includes(q);
      if (!nameMatches && !slugMatches && !descMatches) return false;
    }
    return true;
  });

  const handleOpenCreate = () => {
    setEditingCategory(null);
    setFormData({ name: '', slug: '', description: '', order: 0, is_active: true });
    setFormErrors({});
    setModalOpen(true);
  };

  const handleOpenEdit = (category) => {
    setEditingCategory(category);
    setFormData({
      name: category.name || '',
      slug: category.slug || '',
      description: category.description || '',
      order: category.order ?? 0,
      is_active: Boolean(category.is_active),
    });
    setFormErrors({});
    setModalOpen(true);
  };

  const handleFormFieldChange = (field, value) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
    if (formErrors[field]) {
      setFormErrors((prev) => ({ ...prev, [field]: null }));
    }
  };

  const handleSave = async (e) => {
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
      return;
    }

    setSaving(true);
    setFormErrors({});

    try {
      const payload = {
        name: trimmedName,
        slug: (formData.slug || '').trim() || undefined,
        description: (formData.description || '').trim() || null,
        order: parseInt(formData.order, 10) || 0,
        is_active: Boolean(formData.is_active),
      };

      if (editingCategory) {
        await businessPagesApi.updateCategory(editingCategory.id, payload);
        showSuccess('Category updated successfully.');
      } else {
        await businessPagesApi.createCategory(payload);
        showSuccess('Category created successfully.');
      }
      setModalOpen(false);
      fetchCategories();
    } catch (err) {
      console.error('Failed to save category:', err);
      const rawErrors = err?.errors || err?.response?.data?.errors;
      if (rawErrors && typeof rawErrors === 'object') {
        const mappedErrors = {};
        Object.keys(rawErrors).forEach((key) => {
          const val = rawErrors[key];
          mappedErrors[key] = Array.isArray(val) ? val[0] : String(val);
        });
        setFormErrors(mappedErrors);
      }
      showError(err?.message || 'Failed to save category.');
    } finally {
      setSaving(false);
    }
  };

  const handleToggleStatus = async (category) => {
    try {
      const res = await businessPagesApi.toggleCategoryStatus(category.id);
      showSuccess(res?.message || `Category "${category.name}" status updated.`);
      fetchCategories();
    } catch (err) {
      showError(err?.message || 'Failed to update category status.');
    }
  };

  const handleDelete = (category) => {
    const usageCount = categoryPageCounts[category.name] || 0;
    confirmHelper.confirm({
      header: 'Delete Category',
      message:
        usageCount > 0
          ? `Warning: This category currently has ${usageCount} business page(s) assigned. Deleting will unset their category. Proceed?`
          : `Are you sure you want to permanently delete category "${category.name}"?`,
      icon: 'pi pi-trash text-red-500',
      acceptClassName: 'p-button-danger text-xs',
      onAccept: async () => {
        try {
          await businessPagesApi.deleteCategory(category.id);
          showSuccess('Category deleted successfully.');
          fetchCategories();
        } catch (err) {
          showError(err?.message || 'Failed to delete category.');
        }
      },
    });
  };

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <PageHeader
        title="Business Categories Manager"
        subtitle="Organize industry classification, sorting orders, and merchant assignments."
        breadcrumbs={[
          { label: 'Business Pages', to: '/admin/business-pages' },
          { label: 'Categories' },
        ]}
        actions={
          <div className="flex items-center gap-2">
            <Link to="/admin/business-pages/categories/create">
              <Button
                label="Add Category"
                icon={<Plus className="w-3.5 h-3.5 mr-1.5" />}
                className="p-button-primary text-xs"
              />
            </Link>
          </div>
        }
      />

      {/* Filter Bar */}
      <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs flex flex-wrap items-center justify-between gap-3">
        <div className="flex items-center space-x-2 flex-1 max-w-md">
          <AdminSearchInput
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            onClear={() => setSearch('')}
            placeholder="Search by category name, slug or description..."
          />
        </div>

        <div className="flex flex-wrap items-center gap-2">
          <Dropdown
            value={status}
            options={[
              { label: 'All Statuses', value: '' },
              { label: 'Active', value: 'active' },
              { label: 'Inactive', value: 'inactive' },
            ]}
            onChange={(e) => setStatus(e.value)}
            className="text-xs w-36"
            placeholder="Status Filter"
          />

          <Button
            icon="pi pi-refresh"
            onClick={fetchCategories}
            className="p-button-outlined p-button-secondary text-xs"
            tooltip="Refresh"
          />
        </div>
      </div>

      <div className="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        {loading ? (
          <div className="p-12 flex justify-center">
            <LoadingSpinner message="Loading categories..." />
          </div>
        ) : error ? (
          <div className="p-8">
            <ErrorState title="Failed to Load Categories" message={error} onRetry={fetchCategories} />
          </div>
        ) : categories.length === 0 ? (
          <div className="p-8">
            <EmptyState
              title="No Categories Found"
              message="No business categories created yet."
              icon={Building}
              action={
                <Link to="/admin/business-pages/categories/create">
                  <Button
                    label="Create Category"
                    icon={<Plus className="w-3.5 h-3.5 mr-1.5" />}
                    className="p-button-primary text-xs mt-3"
                  />
                </Link>
              }
            />
          </div>
        ) : filteredCategories.length === 0 ? (
          <div className="p-8">
            <EmptyState
              title="No Matching Categories"
              message="No business categories matched your search criteria."
              icon={Search}
              action={
                <Button
                  label="Clear Filters"
                  icon="pi pi-filter-slash"
                  onClick={() => {
                    setSearch('');
                    setStatus('');
                  }}
                  className="p-button-outlined p-button-secondary text-xs mt-3"
                />
              }
            />
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs text-slate-700">
              <thead className="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase border-b border-slate-200">
                <tr>
                  <th className="p-3.5">Category Name</th>
                  <th className="p-3.5">Slug</th>
                  <th className="p-3.5">Order</th>
                  <th className="p-3.5">Pages Count</th>
                  <th className="p-3.5">Status</th>
                  <th className="p-3.5 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {filteredCategories.map((cat) => (
                  <tr key={cat.id} className="hover:bg-slate-50 transition-colors">
                    <td className="p-3.5 font-semibold text-slate-800">
                      <div>
                        <div>{cat.name}</div>
                        {cat.description && (
                          <div className="text-[11px] font-normal text-slate-400 mt-0.5 max-w-sm truncate">
                            {cat.description}
                          </div>
                        )}
                      </div>
                    </td>
                    <td className="p-3.5 font-mono text-[11px] text-slate-500">{cat.slug}</td>
                    <td className="p-3.5 text-slate-600">{cat.order ?? 0}</td>
                    <td className="p-3.5">
                      <span className="inline-flex items-center text-[10px] font-bold bg-slate-100 text-slate-700 px-2 py-0.5 rounded-full">
                        {categoryPageCounts[cat.name] ?? 0}
                      </span>
                    </td>
                    <td className="p-3.5">
                      <StatusBadge status={cat.is_active ? 'active' : 'blocked'} />
                    </td>
                    <td className="p-3.5 text-right whitespace-nowrap">
                      <div className="inline-flex items-center justify-end gap-1.5 sm:gap-2">
                        <Button
                          icon={
                            cat.is_active ? (
                              <Slash className="w-3.5 h-3.5 text-amber-500" />
                            ) : (
                              <CheckCircle className="w-3.5 h-3.5 text-emerald-500" />
                            )
                          }
                          onClick={() => handleToggleStatus(cat)}
                          className="p-button-text p-button-secondary p-button-sm p-0 w-7 h-7"
                          tooltip={cat.is_active ? 'Disable category' : 'Enable category'}
                        />
                        <Button
                          icon={<Edit2 className="w-3.5 h-3.5" />}
                          onClick={() => handleOpenEdit(cat)}
                          className="p-button-text p-button-secondary p-button-sm p-0 w-7 h-7"
                          tooltip="Edit category"
                        />
                        <Button
                          icon={<Trash2 className="w-3.5 h-3.5 text-red-500" />}
                          onClick={() => handleDelete(cat)}
                          className="p-button-text p-button-danger p-button-sm p-0 w-7 h-7"
                          tooltip="Delete category"
                        />
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Edit Category Modal Dialog */}
      <Dialog
        header={editingCategory ? `Edit Category: ${editingCategory.name}` : 'Create Business Category'}
        visible={modalOpen}
        onHide={() => setModalOpen(false)}
        className="w-full max-w-md"
      >
        <div className="pt-2">
          <CategoryForm
            formData={formData}
            onChange={handleFormFieldChange}
            errors={formErrors}
            onSubmit={handleSave}
            onCancel={() => setModalOpen(false)}
            submitting={saving}
            isEdit={Boolean(editingCategory)}
            showCancel={true}
            submitLabel={editingCategory ? 'Save Changes' : 'Create Category'}
          />
        </div>
      </Dialog>
    </div>
  );
}

export default BusinessCategoriesPage;
