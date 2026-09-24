import apiClient from './apiClient';

/**
 * Platform Branding API Service for Member Frontend
 * Fetches canonical platform branding (site_name, site_logo_url, site_dark_logo_url, site_favicon_url).
 * Endpoint: /api/member/branding (with graceful fallback to /api/branding)
 */
export const brandingApi = {
  getBranding: async () => {
    try {
      const response = await apiClient.get('/branding');
      return response.data;
    } catch (error) {
      // If /api/member/branding fails, attempt root /api/branding fallback
      try {
        const fallbackRes = await apiClient.get('/branding', { baseURL: '/api' });
        return fallbackRes.data;
      } catch {
        throw error;
      }
    }
  },
};

export default brandingApi;
