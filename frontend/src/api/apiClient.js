import axios from 'axios';

/**
 * Central Axios API Client for MLM_Book Member Frontend
 * Base URL: /api/member
 * Automatically passes session cookies & CSRF tokens.
 */
const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api/member',
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
});

// Helper to extract cookies by name
function getCookie(name) {
  const match = document.cookie.match(new RegExp('(^|;\\s*)(' + name + ')=([^;]*)'));
  return match ? decodeURIComponent(match[3]) : null;
}

// Request Interceptor: Attach CSRF Token if available
apiClient.interceptors.request.use(
  (config) => {
    const xsrfToken = getCookie('XSRF-TOKEN');
    if (xsrfToken) {
      config.headers['X-XSRF-TOKEN'] = xsrfToken;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Response Interceptor: Normalize and dispatch auth events
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response ? error.response.status : null;

    if (status === 401) {
      // Dispatches custom event for AuthContext to sync unauthenticated state
      window.dispatchEvent(new CustomEvent('member:unauthenticated'));
    } else if (status === 403) {
      const data = error.response?.data;
      if (data && data.requires_mobile_verification) {
        window.dispatchEvent(new CustomEvent('member:mobile-verification-required', { detail: data }));
      }
    }

    return Promise.reject(error);
  }
);

export default apiClient;
