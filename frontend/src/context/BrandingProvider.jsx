import { useState, useEffect, useCallback, useMemo } from 'react';
import { BrandingContext } from './brandingContextDef';
import brandingApi from '../api/brandingApi';
import { getBrandingAssetUrl, BRAND_LOGO } from '../utils/assetHelper';

const DEFAULT_BRANDING = {
  site_name: 'MLM Book',
  site_description: 'Enterprise MLM Book Social & Commerce Platform.',
  site_logo: 'logo/logo.png',
  site_logo_url: BRAND_LOGO,
  site_dark_logo: 'logo/logo.png',
  site_dark_logo_url: BRAND_LOGO,
  site_favicon: 'favicon.ico',
  site_favicon_url: '/logo/logo.png',
};

/**
 * Dynamically updates document title and favicon <link> in document.head.
 */
function updateDocumentBranding(branding) {
  if (typeof document === 'undefined' || !branding) return;

  // 1. Dynamic Favicon synchronization
  const rawFavicon = branding.site_favicon_url || branding.site_favicon;
  if (rawFavicon) {
    const resolvedFavicon = getBrandingAssetUrl(rawFavicon, '/logo/logo.png');
    let link = document.querySelector("link[rel*='icon']");

    if (!link) {
      link = document.createElement('link');
      link.rel = 'icon';
      document.head.appendChild(link);
    }

    link.href = resolvedFavicon;

    const cleanUrl = resolvedFavicon.split('?')[0].toLowerCase();
    if (cleanUrl.endsWith('.svg')) {
      link.type = 'image/svg+xml';
    } else if (cleanUrl.endsWith('.ico')) {
      link.type = 'image/x-icon';
    } else if (cleanUrl.endsWith('.png')) {
      link.type = 'image/png';
    }
  }

  // 2. Dynamic Title synchronization
  if (branding.site_name && branding.site_name !== 'MLM Book') {
    if (document.title.includes('MLM Book')) {
      document.title = document.title.replace('MLM Book', branding.site_name);
    }
  }
}

export function BrandingProvider({ children }) {
  const [branding, setBranding] = useState(DEFAULT_BRANDING);
  const [loading, setLoading] = useState(true);

  // Sync favicon and document title whenever branding state changes
  useEffect(() => {
    updateDocumentBranding(branding);
  }, [branding]);

  // Fetch canonical platform branding from backend
  const fetchBranding = useCallback(async () => {
    try {
      const res = await brandingApi.getBranding();
      const data = res?.branding || res?.data?.branding;
      if (data) {
        setBranding((prev) => ({
          ...prev,
          ...data,
          site_logo_url: getBrandingAssetUrl(data.site_logo_url || data.site_logo, BRAND_LOGO),
          site_dark_logo_url: getBrandingAssetUrl(data.site_dark_logo_url || data.site_dark_logo, BRAND_LOGO),
          site_favicon_url: getBrandingAssetUrl(data.site_favicon_url || data.site_favicon, prev.site_favicon_url || '/logo/logo.png'),
        }));
      }
    } catch {
      // Silently retain defaults on network or offline error
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchBranding();

    // Re-fetch branding if another tab or event updates branding
    const handleStorageChange = (e) => {
      if (e.key === 'mlm_branding_updated') {
        fetchBranding();
      }
    };
    const handleCustomRefresh = () => {
      fetchBranding();
    };

    window.addEventListener('storage', handleStorageChange);
    window.addEventListener('member:refresh-branding', handleCustomRefresh);
    return () => {
      window.removeEventListener('storage', handleStorageChange);
      window.removeEventListener('member:refresh-branding', handleCustomRefresh);
    };
  }, [fetchBranding]);

  const value = useMemo(() => {
    const resolvedLogo = getBrandingAssetUrl(branding.site_logo_url || branding.site_logo, BRAND_LOGO);
    const resolvedDarkLogo = getBrandingAssetUrl(branding.site_dark_logo_url || branding.site_dark_logo, BRAND_LOGO);
    const resolvedFavicon = getBrandingAssetUrl(branding.site_favicon_url || branding.site_favicon, '/logo/logo.png');

    return {
      branding,
      brandLogo: BRAND_LOGO,
      logoUrl: resolvedLogo || BRAND_LOGO,
      darkLogoUrl: resolvedDarkLogo || BRAND_LOGO,
      faviconUrl: resolvedFavicon,
      siteName: branding.site_name || 'MLM Book',
      siteDescription: branding.site_description || '',
      loading,
      refreshBranding: fetchBranding,
    };
  }, [branding, loading, fetchBranding]);

  return (
    <BrandingContext.Provider value={value}>
      {children}
    </BrandingContext.Provider>
  );
}

export default BrandingProvider;
