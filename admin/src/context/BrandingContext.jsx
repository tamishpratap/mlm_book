import { useState, useEffect, useCallback } from 'react';
import { BrandingContext } from './brandingContextDef';
import { settingsApi } from '../api';
import { getBrandingAssetUrl, BRAND_LOGO } from '../utils/mediaHelper';

const DEFAULT_BRANDING = {
  site_name: 'MLM Book',
  site_description: 'Enterprise MLM Book Social & Commerce Platform.',
  site_logo: 'logo/logo.png',
  site_logo_url: BRAND_LOGO,
  site_dark_logo: 'logo/logo.png',
  site_dark_logo_url: BRAND_LOGO,
  site_favicon: 'favicon.ico',
  site_favicon_url: '/favicon.svg',
};

/**
 * Dynamically updates the favicon <link> in document.head.
 */
function updateDocumentFavicon(rawUrl) {
  if (typeof document === 'undefined' || !rawUrl) return;

  const resolvedUrl = getBrandingAssetUrl(rawUrl, '/favicon.svg');
  let link = document.querySelector("link[rel*='icon']");

  if (!link) {
    link = document.createElement('link');
    link.rel = 'icon';
    document.head.appendChild(link);
  }

  link.href = resolvedUrl;

  const cleanUrl = resolvedUrl.split('?')[0].toLowerCase();
  if (cleanUrl.endsWith('.svg')) {
    link.type = 'image/svg+xml';
  } else if (cleanUrl.endsWith('.ico')) {
    link.type = 'image/x-icon';
  } else if (cleanUrl.endsWith('.png')) {
    link.type = 'image/png';
  }
}

export function BrandingProvider({ children }) {
  const [branding, setBranding] = useState(DEFAULT_BRANDING);
  const [loading, setLoading] = useState(true);

  // Sync favicon whenever branding favicon URL updates
  useEffect(() => {
    if (branding?.site_favicon_url || branding?.site_favicon) {
      updateDocumentFavicon(branding.site_favicon_url || branding.site_favicon);
    }
  }, [branding?.site_favicon_url, branding?.site_favicon]);

  // Fetch active platform branding on mount
  useEffect(() => {
    let mounted = true;

    settingsApi
      .getBranding()
      .then((res) => {
        if (!mounted) return;
        const data = res?.branding || res?.data?.branding;
        if (data) {
          setBranding((prev) => ({
            ...prev,
            ...data,
            site_logo_url: getBrandingAssetUrl(data.site_logo_url || data.site_logo, prev.site_logo_url),
            site_dark_logo_url: getBrandingAssetUrl(data.site_dark_logo_url || data.site_dark_logo, prev.site_dark_logo_url),
            site_favicon_url: getBrandingAssetUrl(data.site_favicon_url || data.site_favicon, prev.site_favicon_url),
          }));
        }
      })
      .catch(() => {
        // Silently preserve defaults on network/auth issue
      })
      .finally(() => {
        if (mounted) setLoading(false);
      });

    return () => {
      mounted = false;
    };
  }, []);

  // Method to immediately update branding across all components without reload
  const updateBranding = useCallback((newData = {}) => {
    if (!newData || typeof newData !== 'object') return;

    setBranding((prev) => {
      const next = { ...prev, ...newData };

      // Ensure resolved URLs exist
      if (newData.site_logo || newData.site_logo_url) {
        next.site_logo_url = getBrandingAssetUrl(newData.site_logo_url || newData.site_logo, prev.site_logo_url);
      }
      if (newData.site_dark_logo || newData.site_dark_logo_url) {
        next.site_dark_logo_url = getBrandingAssetUrl(newData.site_dark_logo_url || newData.site_dark_logo, prev.site_dark_logo_url);
      }
      if (newData.site_favicon || newData.site_favicon_url) {
        next.site_favicon_url = getBrandingAssetUrl(newData.site_favicon_url || newData.site_favicon, prev.site_favicon_url);
      }

      return next;
    });
  }, []);

  return (
    <BrandingContext.Provider value={{ branding, updateBranding, loading }}>
      {children}
    </BrandingContext.Provider>
  );
}

export default BrandingProvider;
