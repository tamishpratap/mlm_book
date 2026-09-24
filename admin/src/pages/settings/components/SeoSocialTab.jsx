import { useState, useEffect, useMemo } from 'react';
import { Save, RotateCcw } from 'lucide-react';
import { InputText } from 'primereact/inputtext';
import { InputTextarea } from 'primereact/inputtextarea';
import { Button } from 'primereact/button';

export function SeoSocialTab({ initialValues = {}, onSave, loading = false }) {
  const defaults = useMemo(() => ({
    meta_title: initialValues?.meta_title ?? 'MLM Book Enterprise Social & Commerce Platform',
    meta_keywords: initialValues?.meta_keywords ?? 'mlm, social network, marketplace, business pages, communities',
    meta_description: initialValues?.meta_description ?? 'Connect, shop, organize communities, and grow your business network on MLM Book.',
    social_facebook: initialValues?.social_facebook ?? 'https://facebook.com',
    social_twitter: initialValues?.social_twitter ?? 'https://x.com',
    social_instagram: initialValues?.social_instagram ?? 'https://instagram.com',
    social_linkedin: initialValues?.social_linkedin ?? '',
    social_youtube: initialValues?.social_youtube ?? '',
    social_telegram: initialValues?.social_telegram ?? '',
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
    onSave({ ...formData, group: 'seo' });
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6 pt-2">
      {/* SEO Section */}
      <div className="space-y-4">
        <h6 className="text-xs font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-2">
          SEO Parameters
        </h6>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label className="text-xs font-bold text-slate-700 block mb-1.5">
              Default Meta Title
            </label>
            <InputText
              value={formData.meta_title}
              onChange={(e) => handleChange('meta_title', e.target.value)}
              className="w-full text-xs"
              disabled={loading}
            />
          </div>

          <div>
            <label className="text-xs font-bold text-slate-700 block mb-1.5">
              Meta Keywords
            </label>
            <InputText
              value={formData.meta_keywords}
              onChange={(e) => handleChange('meta_keywords', e.target.value)}
              className="w-full text-xs"
              disabled={loading}
            />
          </div>

          <div className="sm:col-span-2">
            <label className="text-xs font-bold text-slate-700 block mb-1.5">
              Default Meta Description
            </label>
            <InputTextarea
              value={formData.meta_description}
              onChange={(e) => handleChange('meta_description', e.target.value)}
              rows={2}
              className="w-full text-xs"
              disabled={loading}
            />
          </div>
        </div>
      </div>

      {/* Social Links Section */}
      <div className="space-y-4 pt-2">
        <h6 className="text-xs font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-2">
          Official Social Links
        </h6>
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label className="text-xs font-bold text-slate-700 block mb-1.5">
              Facebook URL
            </label>
            <InputText
              value={formData.social_facebook}
              onChange={(e) => handleChange('social_facebook', e.target.value)}
              className="w-full text-xs"
              placeholder="https://facebook.com/..."
              disabled={loading}
            />
          </div>

          <div>
            <label className="text-xs font-bold text-slate-700 block mb-1.5">
              Twitter / X URL
            </label>
            <InputText
              value={formData.social_twitter}
              onChange={(e) => handleChange('social_twitter', e.target.value)}
              className="w-full text-xs"
              placeholder="https://x.com/..."
              disabled={loading}
            />
          </div>

          <div>
            <label className="text-xs font-bold text-slate-700 block mb-1.5">
              Instagram URL
            </label>
            <InputText
              value={formData.social_instagram}
              onChange={(e) => handleChange('social_instagram', e.target.value)}
              className="w-full text-xs"
              placeholder="https://instagram.com/..."
              disabled={loading}
            />
          </div>

          <div>
            <label className="text-xs font-bold text-slate-700 block mb-1.5">
              LinkedIn URL
            </label>
            <InputText
              value={formData.social_linkedin}
              onChange={(e) => handleChange('social_linkedin', e.target.value)}
              className="w-full text-xs"
              placeholder="https://linkedin.com/in/..."
              disabled={loading}
            />
          </div>

          <div>
            <label className="text-xs font-bold text-slate-700 block mb-1.5">
              YouTube URL
            </label>
            <InputText
              value={formData.social_youtube}
              onChange={(e) => handleChange('social_youtube', e.target.value)}
              className="w-full text-xs"
              placeholder="https://youtube.com/..."
              disabled={loading}
            />
          </div>

          <div>
            <label className="text-xs font-bold text-slate-700 block mb-1.5">
              Telegram Channel / Group URL
            </label>
            <InputText
              value={formData.social_telegram}
              onChange={(e) => handleChange('social_telegram', e.target.value)}
              className="w-full text-xs"
              placeholder="https://t.me/..."
              disabled={loading}
            />
          </div>
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
            label="Save SEO & Social Links"
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

export default SeoSocialTab;
