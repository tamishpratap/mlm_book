import { useState, useRef } from 'react';
import { Upload, CheckCircle2 } from 'lucide-react';
import { Button } from 'primereact/button';
import { getBrandingAssetUrl, BRAND_LOGO } from '../../../utils/mediaHelper';
import { useBranding } from '../../../hooks/useBranding';

export function BrandingSettingsTab({ initialValues = {}, onUpload, loading = false }) {
  const { updateBranding } = useBranding();

  const [logoFile, setLogoFile] = useState(null);
  const [darkLogoFile, setDarkLogoFile] = useState(null);
  const [faviconFile, setFaviconFile] = useState(null);

  const [logoPreview, setLogoPreview] = useState(null);
  const [darkLogoPreview, setDarkLogoPreview] = useState(null);
  const [faviconPreview, setFaviconPreview] = useState(null);

  const logoInputRef = useRef(null);
  const darkLogoInputRef = useRef(null);
  const faviconInputRef = useRef(null);

  const handleFileChange = (e, setFile, setPreview, currentPreview) => {
    const file = e.target.files?.[0];
    if (file) {
      if (currentPreview && currentPreview.startsWith('blob:')) {
        URL.revokeObjectURL(currentPreview);
      }
      setFile(file);
      setPreview(URL.createObjectURL(file));
    }
  };

  const handleCancelSelection = (setFile, setPreview, inputRef, currentPreview) => {
    if (currentPreview && currentPreview.startsWith('blob:')) {
      URL.revokeObjectURL(currentPreview);
    }
    setFile(null);
    setPreview(null);
    if (inputRef.current) {
      inputRef.current.value = '';
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const files = {};
    if (logoFile) files.site_logo = logoFile;
    if (darkLogoFile) files.site_dark_logo = darkLogoFile;
    if (faviconFile) files.site_favicon = faviconFile;

    if (Object.keys(files).length === 0) {
      return;
    }

    try {
      const res = await onUpload({ group: 'branding' }, files);

      // Clean up blob preview URLs
      if (logoPreview && logoPreview.startsWith('blob:')) URL.revokeObjectURL(logoPreview);
      if (darkLogoPreview && darkLogoPreview.startsWith('blob:')) URL.revokeObjectURL(darkLogoPreview);
      if (faviconPreview && faviconPreview.startsWith('blob:')) URL.revokeObjectURL(faviconPreview);

      // Reset local file states
      setLogoFile(null);
      setDarkLogoFile(null);
      setFaviconFile(null);
      setLogoPreview(null);
      setDarkLogoPreview(null);
      setFaviconPreview(null);

      // Clear physical file input fields
      if (logoInputRef.current) logoInputRef.current.value = '';
      if (darkLogoInputRef.current) darkLogoInputRef.current.value = '';
      if (faviconInputRef.current) faviconInputRef.current.value = '';

      if (res?.branding) {
        updateBranding(res.branding);
        try {
          localStorage.setItem('mlm_branding_updated', Date.now().toString());
        } catch {
          // ignore
        }
      }
    } catch {
      // Error handled by parent page
    }
  };

  const currentLogo = logoPreview || getBrandingAssetUrl(initialValues.site_logo_url || initialValues.site_logo, BRAND_LOGO);
  const currentDarkLogo = darkLogoPreview || getBrandingAssetUrl(initialValues.site_dark_logo_url || initialValues.site_dark_logo, BRAND_LOGO);
  const currentFavicon = faviconPreview || getBrandingAssetUrl(initialValues.site_favicon_url || initialValues.site_favicon, '/favicon.svg');

  const hasSelectedFiles = Boolean(logoFile || darkLogoFile || faviconFile);

  return (
    <form onSubmit={handleSubmit} className="space-y-6 pt-2">
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
        {/* Main Light Logo */}
        <div className="border border-slate-200 rounded-2xl p-4 text-center bg-slate-50 flex flex-col justify-between">
          <div>
            <div className="flex items-center justify-between mb-3">
              <h6 className="text-xs font-bold text-slate-800 uppercase tracking-wider">Main Light Logo</h6>
              {logoFile ? (
                <span className="inline-flex items-center text-[10px] font-semibold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                  New File
                </span>
              ) : (
                <span className="inline-flex items-center text-[10px] font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                  <CheckCircle2 className="w-3 h-3 mr-1" /> Active
                </span>
              )}
            </div>

            <div className="h-20 flex items-center justify-center bg-white rounded-xl border border-slate-200/60 p-2 mb-3 shadow-2xs">
              <img
                src={currentLogo}
                alt="Main Logo"
                className="max-h-14 max-w-full object-contain"
                onError={(e) => {
                  e.currentTarget.onerror = null;
                  e.currentTarget.src = BRAND_LOGO;
                }}
              />
            </div>
            <p className="text-[11px] text-slate-500 mb-2">Used for light backgrounds and general header displays.</p>
          </div>

          <div className="space-y-2">
            <input
              ref={logoInputRef}
              type="file"
              accept="image/png,image/jpeg,image/webp,image/svg+xml"
              onChange={(e) => handleFileChange(e, setLogoFile, setLogoPreview, logoPreview)}
              className="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
              disabled={loading}
            />
            {logoFile && (
              <div className="flex items-center justify-between text-[11px] text-slate-600 bg-white px-2 py-1 rounded-md border border-slate-200">
                <span className="truncate max-w-[160px] font-medium">{logoFile.name}</span>
                <button
                  type="button"
                  onClick={() => handleCancelSelection(setLogoFile, setLogoPreview, logoInputRef, logoPreview)}
                  className="text-red-500 hover:text-red-700 text-[10px] font-semibold ml-1 cursor-pointer"
                >
                  Cancel
                </button>
              </div>
            )}
          </div>
        </div>

        {/* Dark Theme Logo */}
        <div className="border border-slate-700 rounded-2xl p-4 text-center bg-slate-900 text-white flex flex-col justify-between">
          <div>
            <div className="flex items-center justify-between mb-3">
              <h6 className="text-xs font-bold text-slate-200 uppercase tracking-wider">Dark Theme Logo</h6>
              {darkLogoFile ? (
                <span className="inline-flex items-center text-[10px] font-semibold text-amber-300 bg-amber-900/60 px-2 py-0.5 rounded-full border border-amber-700">
                  New File
                </span>
              ) : (
                <span className="inline-flex items-center text-[10px] font-medium text-emerald-300 bg-emerald-950/60 px-2 py-0.5 rounded-full border border-emerald-800">
                  <CheckCircle2 className="w-3 h-3 mr-1" /> Active
                </span>
              )}
            </div>

            <div className="h-20 flex items-center justify-center bg-slate-800 rounded-xl border border-slate-700 p-2 mb-3 shadow-2xs">
              <img
                src={currentDarkLogo}
                alt="Dark Logo"
                className="max-h-14 max-w-full object-contain"
                onError={(e) => {
                  e.currentTarget.onerror = null;
                  e.currentTarget.src = BRAND_LOGO;
                }}
              />
            </div>
            <p className="text-[11px] text-slate-400 mb-2">Used for dark sidebars and contrast-rich UI areas.</p>
          </div>

          <div className="space-y-2">
            <input
              ref={darkLogoInputRef}
              type="file"
              accept="image/png,image/jpeg,image/webp,image/svg+xml"
              onChange={(e) => handleFileChange(e, setDarkLogoFile, setDarkLogoPreview, darkLogoPreview)}
              className="w-full text-xs text-slate-400 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-slate-200 hover:file:bg-slate-700 cursor-pointer"
              disabled={loading}
            />
            {darkLogoFile && (
              <div className="flex items-center justify-between text-[11px] text-slate-300 bg-slate-800 px-2 py-1 rounded-md border border-slate-700">
                <span className="truncate max-w-[160px] font-medium">{darkLogoFile.name}</span>
                <button
                  type="button"
                  onClick={() => handleCancelSelection(setDarkLogoFile, setDarkLogoPreview, darkLogoInputRef, darkLogoPreview)}
                  className="text-red-400 hover:text-red-300 text-[10px] font-semibold ml-1 cursor-pointer"
                >
                  Cancel
                </button>
              </div>
            )}
          </div>
        </div>

        {/* Favicon Icon */}
        <div className="border border-slate-200 rounded-2xl p-4 text-center bg-slate-50 flex flex-col justify-between">
          <div>
            <div className="flex items-center justify-between mb-3">
              <h6 className="text-xs font-bold text-slate-800 uppercase tracking-wider">Favicon Icon</h6>
              {faviconFile ? (
                <span className="inline-flex items-center text-[10px] font-semibold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                  New File
                </span>
              ) : (
                <span className="inline-flex items-center text-[10px] font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                  <CheckCircle2 className="w-3 h-3 mr-1" /> Active
                </span>
              )}
            </div>

            <div className="h-20 flex items-center justify-center bg-white rounded-xl border border-slate-200/60 p-2 mb-3 shadow-2xs">
              <img
                src={currentFavicon}
                alt="Favicon"
                className="w-10 h-10 object-contain"
                onError={(e) => {
                  e.currentTarget.onerror = null;
                  e.currentTarget.src = '/favicon.svg';
                }}
              />
            </div>
            <p className="text-[11px] text-slate-500 mb-2">Browser tab icon (.ico, .png, .svg recommended).</p>
          </div>

          <div className="space-y-2">
            <input
              ref={faviconInputRef}
              type="file"
              accept=".ico,image/x-icon,image/png,image/svg+xml,image/webp"
              onChange={(e) => handleFileChange(e, setFaviconFile, setFaviconPreview, faviconPreview)}
              className="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
              disabled={loading}
            />
            {faviconFile && (
              <div className="flex items-center justify-between text-[11px] text-slate-600 bg-white px-2 py-1 rounded-md border border-slate-200">
                <span className="truncate max-w-[160px] font-medium">{faviconFile.name}</span>
                <button
                  type="button"
                  onClick={() => handleCancelSelection(setFaviconFile, setFaviconPreview, faviconInputRef, faviconPreview)}
                  className="text-red-500 hover:text-red-700 text-[10px] font-semibold ml-1 cursor-pointer"
                >
                  Cancel
                </button>
              </div>
            )}
          </div>
        </div>
      </div>

      <div className="pt-4 border-t border-slate-100 flex items-center justify-between">
        <span className="text-xs text-slate-500">
          Supported formats: PNG, JPG, WebP, SVG, ICO (Max 5MB for logos, 2MB for favicon)
        </span>
        <Button
          type="submit"
          label={loading ? 'Uploading...' : 'Upload Branding Assets'}
          icon={<Upload className="w-3.5 h-3.5 mr-1.5" />}
          size="small"
          loading={loading}
          disabled={loading || !hasSelectedFiles}
          className="p-button-primary text-xs"
        />
      </div>
    </form>
  );
}

export default BrandingSettingsTab;
