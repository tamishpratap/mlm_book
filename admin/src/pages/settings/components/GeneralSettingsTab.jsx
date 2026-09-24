import { useState, useEffect, useMemo } from 'react';
import { Save, RotateCcw } from 'lucide-react';
import { InputText } from 'primereact/inputtext';
import { InputTextarea } from 'primereact/inputtextarea';
import { Dropdown } from 'primereact/dropdown';
import { Button } from 'primereact/button';

export function GeneralSettingsTab({ initialValues = {}, onSave, loading = false }) {
  const defaults = useMemo(() => ({
    site_name: initialValues?.site_name ?? 'MLM Book',
    timezone: initialValues?.timezone ?? 'UTC',
    site_description: initialValues?.site_description ?? 'Enterprise MLM Book Social & Commerce Platform.',
    date_format: initialValues?.date_format ?? 'Y-m-d',
    currency: initialValues?.currency ?? 'USD ($)',
    pagination_size: String(initialValues?.pagination_size ?? '15'),
  }), [initialValues]);

  const [formData, setFormData] = useState(defaults);

  useEffect(() => {
    setFormData(defaults);
  }, [defaults]);

  const timezoneOptions = [
    { label: 'UTC', value: 'UTC' },
    { label: 'Asia/Kolkata (IST)', value: 'Asia/Kolkata' },
    { label: 'America/New_York (EST)', value: 'America/New_York' },
    { label: 'Europe/London (GMT)', value: 'Europe/London' },
  ];

  const dateFormatOptions = [
    { label: 'YYYY-MM-DD (2026-07-28)', value: 'Y-m-d' },
    { label: 'MMM DD, YYYY (Jul 28, 2026)', value: 'M d, Y' },
    { label: 'DD/MM/YYYY (28/07/2026)', value: 'd/m/Y' },
  ];

  const paginationOptions = [
    { label: '10 Items', value: '10' },
    { label: '15 Items', value: '15' },
    { label: '25 Items', value: '25' },
    { label: '50 Items', value: '50' },
  ];

  const handleChange = (field, value) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  const isDirty = useMemo(() => {
    return Object.keys(defaults).some(key => defaults[key] !== formData[key]);
  }, [defaults, formData]);

  const handleReset = () => {
    setFormData(defaults);
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    onSave({ ...formData, group: 'general' });
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6 pt-2">
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        {/* Site Name */}
        <div>
          <label className="text-xs font-bold text-slate-700 block mb-1.5">
            Site Name <span className="text-red-500">*</span>
          </label>
          <InputText
            value={formData.site_name}
            onChange={(e) => handleChange('site_name', e.target.value)}
            placeholder="Platform Title"
            className="w-full text-xs"
            required
            disabled={loading}
          />
        </div>

        {/* Default Timezone */}
        <div>
          <label className="text-xs font-bold text-slate-700 block mb-1.5">
            Default Timezone
          </label>
          <Dropdown
            value={formData.timezone}
            options={timezoneOptions}
            onChange={(e) => handleChange('timezone', e.value)}
            placeholder="Select Timezone"
            className="w-full text-xs"
            disabled={loading}
          />
        </div>

        {/* Site Description */}
        <div className="sm:col-span-2">
          <label className="text-xs font-bold text-slate-700 block mb-1.5">
            Site Description
          </label>
          <InputTextarea
            value={formData.site_description}
            onChange={(e) => handleChange('site_description', e.target.value)}
            rows={2}
            className="w-full text-xs"
            disabled={loading}
          />
        </div>

        {/* Date Format */}
        <div>
          <label className="text-xs font-bold text-slate-700 block mb-1.5">
            Date Format
          </label>
          <Dropdown
            value={formData.date_format}
            options={dateFormatOptions}
            onChange={(e) => handleChange('date_format', e.value)}
            className="w-full text-xs"
            disabled={loading}
          />
        </div>

        {/* Default Currency */}
        <div>
          <label className="text-xs font-bold text-slate-700 block mb-1.5">
            Default Currency
          </label>
          <InputText
            value={formData.currency}
            onChange={(e) => handleChange('currency', e.target.value)}
            placeholder="USD ($)"
            className="w-full text-xs"
            disabled={loading}
          />
        </div>

        {/* Pagination Size */}
        <div>
          <label className="text-xs font-bold text-slate-700 block mb-1.5">
            Admin Pagination Size
          </label>
          <Dropdown
            value={formData.pagination_size}
            options={paginationOptions}
            onChange={(e) => handleChange('pagination_size', e.value)}
            className="w-full text-xs"
            disabled={loading}
          />
        </div>
      </div>

      <div className="pt-4 border-t border-slate-100 flex items-center justify-between">
        <div>
          {isDirty && (
            <span className="text-xs font-medium text-amber-600 bg-amber-50 px-2.5 py-1 rounded-md border border-amber-200">
              Unsaved changes
            </span>
          )}
        </div>
        <div className="flex items-center gap-2">
          {isDirty && (
            <Button
              type="button"
              label="Reset"
              icon={<RotateCcw className="w-3.5 h-3.5 mr-1.5" />}
              size="small"
              onClick={handleReset}
              disabled={loading}
              className="p-button-outlined p-button-secondary text-xs"
            />
          )}
          <Button
            type="submit"
            label="Save General Settings"
            icon={<Save className="w-3.5 h-3.5 mr-1.5" />}
            size="small"
            loading={loading}
            disabled={loading || !isDirty}
            className="p-button-primary text-xs"
          />
        </div>
      </div>
    </form>
  );
}

export default GeneralSettingsTab;
