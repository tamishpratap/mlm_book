import { useState, useEffect, useMemo } from 'react';
import { Save, RotateCcw } from 'lucide-react';
import { InputText } from 'primereact/inputtext';
import { InputTextarea } from 'primereact/inputtextarea';
import { Button } from 'primereact/button';

export function ContactSettingsTab({ initialValues = {}, onSave, loading = false }) {
  const defaults = useMemo(() => ({
    company_name: initialValues?.company_name ?? 'MLM Book Enterprise',
    support_email: initialValues?.support_email ?? 'support@mlmbook.com',
    phone: initialValues?.phone ?? '+1 (800) 123-4567',
    website: initialValues?.website ?? 'https://mlmbook.com',
    address: initialValues?.address ?? '123 Enterprise Way, Suite 500, Tech City',
  }), [initialValues]);

  const [formData, setFormData] = useState(defaults);

  useEffect(() => {
    setFormData(defaults);
  }, [defaults]);

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
    onSave({ ...formData, group: 'contact' });
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6 pt-2">
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        {/* Company Name */}
        <div>
          <label className="text-xs font-bold text-slate-700 block mb-1.5">
            Company Name
          </label>
          <InputText
            value={formData.company_name}
            onChange={(e) => handleChange('company_name', e.target.value)}
            placeholder="Official Enterprise Name"
            className="w-full text-xs"
            disabled={loading}
          />
        </div>

        {/* Support Email */}
        <div>
          <label className="text-xs font-bold text-slate-700 block mb-1.5">
            Support Email
          </label>
          <InputText
            type="email"
            value={formData.support_email}
            onChange={(e) => handleChange('support_email', e.target.value)}
            placeholder="support@company.com"
            className="w-full text-xs"
            disabled={loading}
          />
        </div>

        {/* Phone */}
        <div>
          <label className="text-xs font-bold text-slate-700 block mb-1.5">
            Support Phone Number
          </label>
          <InputText
            value={formData.phone}
            onChange={(e) => handleChange('phone', e.target.value)}
            placeholder="+1 (800) 123-4567"
            className="w-full text-xs"
            disabled={loading}
          />
        </div>

        {/* Website */}
        <div>
          <label className="text-xs font-bold text-slate-700 block mb-1.5">
            Official Website URL
          </label>
          <InputText
            value={formData.website}
            onChange={(e) => handleChange('website', e.target.value)}
            placeholder="https://mlmbook.com"
            className="w-full text-xs"
            disabled={loading}
          />
        </div>

        {/* Address */}
        <div className="sm:col-span-2">
          <label className="text-xs font-bold text-slate-700 block mb-1.5">
            Office Physical Address
          </label>
          <InputTextarea
            value={formData.address}
            onChange={(e) => handleChange('address', e.target.value)}
            rows={2}
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
            label="Save Contact Information"
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

export default ContactSettingsTab;
