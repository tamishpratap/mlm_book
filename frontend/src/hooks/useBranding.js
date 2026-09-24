import { useContext } from 'react';
import { BrandingContext } from '../context/brandingContextDef';
import { BRAND_LOGO } from '../utils/assetHelper';

const FALLBACK_BRANDING = {
  branding: {
    site_name: 'MLM Book',
    site_description: 'Enterprise MLM Book Social & Commerce Platform.',
    site_logo: 'logo/logo.png',
    site_logo_url: BRAND_LOGO,
    site_dark_logo: 'logo/logo.png',
    site_dark_logo_url: BRAND_LOGO,
    site_favicon: 'favicon.ico',
    site_favicon_url: '/logo/logo.png',
  },
  brandLogo: BRAND_LOGO,
  logoUrl: BRAND_LOGO,
  darkLogoUrl: BRAND_LOGO,
  faviconUrl: '/logo/logo.png',
  siteName: 'MLM Book',
  siteDescription: 'Enterprise MLM Book Social & Commerce Platform.',
  loading: false,
  refreshBranding: () => {},
};

/**
 * Hook to consume canonical platform branding in member frontend components.
 * Guarantees a safe fallback object so caller never throws if rendered outside provider.
 */
export function useBranding() {
  const context = useContext(BrandingContext);
  if (!context) {
    return FALLBACK_BRANDING;
  }
  return context;
}

export default useBranding;
