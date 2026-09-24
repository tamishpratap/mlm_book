import React from 'react';
import { InputText } from 'primereact/inputtext';
import { InputSwitch } from 'primereact/inputswitch';
import { Button } from 'primereact/button';
import { Save, X } from 'lucide-react';

/**
 * CategoryForm
 * Reusable form component for Creating and Editing Business Page Categories.
 * Provides consistent validation styling, help text, and submission handling.
 */
export function CategoryForm({
  formData = { name: '', slug: '', description: '', order: 0, is_active: true },
  onChange,
  errors = {},
  onSubmit,
  onCancel,
  submitting = false,
  isEdit = false,
  showCancel = true,
  submitLabel,
  cancelLabel = 'Cancel',
}) {
  const handleChange = (field, value) => {
    if (onChange) {
      onChange(field, value);
    }
  };

  const nameError = errors?.name;
  const slugError = errors?.slug;
  const orderError = errors?.order;
  const descriptionError = errors?.description;

  return (
    <form onSubmit={onSubmit} className="space-y-4 text-left">
      {/* Category Name */}
      <div>
        <label className="block text-xs font-semibold text-slate-700 mb-1">
          Category Name <span className="text-red-500">*</span>
        </label>
        <InputText
          value={formData?.name ?? ''}
          onChange={(e) => handleChange('name', e.target.value)}
          placeholder="e.g. Technology & Software"
          className={`w-full text-xs py-2 ${nameError ? 'p-invalid border-red-500' : ''}`}
          disabled={submitting}
        />
        {nameError ? (
          <span className="text-[11px] text-red-500 mt-1 block font-medium">{nameError}</span>
        ) : (
          <span className="text-[11px] text-slate-400 mt-1 block">
            Must be unique. Automatically generates slug identifier.
          </span>
        )}
      </div>

      {/* Slug */}
      <div>
        <label className="block text-xs font-semibold text-slate-700 mb-1">
          Slug (Optional)
        </label>
        <InputText
          value={formData?.slug ?? ''}
          onChange={(e) => handleChange('slug', e.target.value)}
          placeholder="auto-generated-if-blank"
          className={`w-full text-xs py-2 font-mono ${slugError ? 'p-invalid border-red-500' : ''}`}
          disabled={submitting}
        />
        {slugError ? (
          <span className="text-[11px] text-red-500 mt-1 block font-medium">{slugError}</span>
        ) : (
          <span className="text-[11px] text-slate-400 mt-1 block">
            Custom URL identifier. Leave blank to auto-generate from name.
          </span>
        )}
      </div>

      {/* Display Order */}
      <div>
        <label className="block text-xs font-semibold text-slate-700 mb-1">
          Display Order
        </label>
        <InputText
          type="number"
          min="0"
          max="9999"
          value={formData?.order ?? 0}
          onChange={(e) => handleChange('order', parseInt(e.target.value, 10) || 0)}
          className={`w-full text-xs py-2 ${orderError ? 'p-invalid border-red-500' : ''}`}
          disabled={submitting}
        />
        {orderError ? (
          <span className="text-[11px] text-red-500 mt-1 block font-medium">{orderError}</span>
        ) : (
          <span className="text-[11px] text-slate-400 mt-1 block">
            Lower numbers appear first in member selection dropdowns.
          </span>
        )}
      </div>

      {/* Description */}
      <div>
        <label className="block text-xs font-semibold text-slate-700 mb-1">
          Description <span className="text-slate-400 font-normal">(Optional)</span>
        </label>
        <textarea
          value={formData?.description ?? ''}
          onChange={(e) => handleChange('description', e.target.value)}
          placeholder="Brief description of this business category niche..."
          className={`w-full text-xs p-2.5 bg-white text-slate-800 placeholder:text-slate-400 border rounded-lg focus:outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 ${
            descriptionError ? 'border-red-500' : 'border-slate-300'
          }`}
          rows={3}
          disabled={submitting}
        />
        {descriptionError && (
          <span className="text-[11px] text-red-500 mt-1 block font-medium">{descriptionError}</span>
        )}
      </div>

      {/* Active Status Switch */}
      <div className="flex items-center justify-between p-3 rounded-lg bg-slate-50 border border-slate-200">
        <div>
          <span className="block text-xs font-semibold text-slate-800">
            Active Status
          </span>
          <span className="block text-[11px] text-slate-500">
            Active categories are visible to members when creating business pages.
          </span>
        </div>
        <InputSwitch
          checked={Boolean(formData?.is_active ?? true)}
          onChange={(e) => handleChange('is_active', e.value)}
          disabled={submitting}
        />
      </div>

      {/* Form Action Buttons */}
      <div className="flex flex-wrap items-center justify-end gap-2.5 sm:gap-3 pt-3 border-t border-slate-200">
        {showCancel && onCancel && (
          <Button
            type="button"
            label={cancelLabel}
            icon={<X className="w-3.5 h-3.5 mr-1.5" />}
            onClick={onCancel}
            className="p-button-outlined p-button-secondary text-xs"
            disabled={submitting}
          />
        )}
        <Button
          type="submit"
          label={
            submitting
              ? 'Saving...'
              : submitLabel || (isEdit ? 'Save Changes' : 'Create Category')
          }
          icon={<Save className="w-3.5 h-3.5 mr-1.5" />}
          className="p-button-primary text-xs"
          loading={submitting}
          disabled={submitting}
        />
      </div>
    </form>
  );
}

export default CategoryForm;
