import axios from 'axios';
import { normalizeError } from './utils/errorNormalizer';
import { adaptHtmlResponse } from './utils/htmlAdapter';
import { extractFilenameFromHeader } from './utils/downloadHelper';

// Duplicate request tracker for mutative operations
const activeMutations = new Set();

// Throttle tracker to prevent duplicate auth:expired event storms
let lastAuthExpiredDispatch = 0;

function getMutationKey(config) {
  const method = (config.method || 'get').toLowerCase();
  if (method === 'get') return null;
  return `${method}:${config.url}`;
}

/**
 * Retrieve cookie value by name (used for XSRF-TOKEN).
 */
function getCookie(name) {
  const match = document.cookie.match(new RegExp('(^|;\\s*)(' + name + ')=([^;]*)'));
  return match ? decodeURIComponent(match[3]) : null;
}

// Create Central Axios Instance
const rawBaseUrl = import.meta.env.VITE_API_BASE_URL || '/api/admin';
const normalizedBaseUrl = rawBaseUrl.endsWith('/') ? rawBaseUrl : `${rawBaseUrl}/`;

export const apiClient = axios.create({
  baseURL: normalizedBaseUrl,
  withCredentials: true, // Send session and CSRF cookies with every request
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
  },
  timeout: 30000,
});

// Request Interceptor: Attach CSRF token and prevent duplicate submissions
apiClient.interceptors.request.use(
  (config) => {
    // Ensure relative URLs don't override the /api/admin baseURL prefix
    if (config.url && !config.url.startsWith('http') && !config.url.startsWith('/api/admin')) {
      if (config.url.startsWith('/')) {
        config.url = config.url.slice(1);
      }
    }

    // 1. Read CSRF token from cookie if present
    const xsrfToken = getCookie('XSRF-TOKEN');
    if (xsrfToken && !config.headers['X-XSRF-TOKEN']) {
      config.headers['X-XSRF-TOKEN'] = xsrfToken;
    }

    // 2. Prevent duplicate in-flight mutations
    const mutationKey = getMutationKey(config);
    if (mutationKey) {
      if (activeMutations.has(mutationKey) && !config.allowConcurrent) {
        return Promise.reject({
          status: 409,
          message: 'Operation already in progress. Please wait.',
          isDuplicate: true,
        });
      }
      activeMutations.add(mutationKey);
    }

    // 3. Development logging
    if (import.meta.env.DEV) {
      console.debug(`[API Request] [${config.method?.toUpperCase()}] ${config.url}`);
    }

    return config;
  },
  (error) => Promise.reject(normalizeError(error))
);

// Response Interceptor: Error translation, auth expiry notification, and mutation unlock
apiClient.interceptors.response.use(
  (response) => {
    const mutationKey = getMutationKey(response.config);
    if (mutationKey) {
      activeMutations.delete(mutationKey);
    }

    // If responseType was 'blob', attach content-disposition and filename to the Blob instance
    if (response.config?.responseType === 'blob' && response.data instanceof Blob) {
      const disposition =
        response.headers?.['content-disposition'] ||
        response.headers?.['Content-Disposition'] ||
        '';
      response.data.contentDisposition = disposition;
      response.data.filename = extractFilenameFromHeader(disposition, '');
    }

    // If Laravel returned a Blade HTML string, parse and extract into structured JSON
    if (
      typeof response.data === 'string' &&
      (response.data.includes('<!DOCTYPE') || response.data.includes('<html'))
    ) {
      return adaptHtmlResponse(response.data, response.config?.url || '');
    }

    return response.data;
  },
  async (error) => {
    if (error.config) {
      const mutationKey = getMutationKey(error.config);
      if (mutationKey) {
        activeMutations.delete(mutationKey);
      }
    }

    // If server returned a 4xx/5xx error when responseType was 'blob',
    // parse the Blob text to JSON so normalizeError can extract the actual Laravel validation/server error
    if (error.response?.data instanceof Blob) {
      try {
        const errorText = await error.response.data.text();
        const parsedJson = JSON.parse(errorText);
        error.response.data = parsedJson;
      } catch {
        // Not JSON text; keep original blob
      }
    }

    const normalized = normalizeError(error);

    // Dispatch global authentication expired event for 401 Unauthorized or 419 CSRF Token Mismatch
    // ONLY for genuine session expirations on protected requests (not silent probes, login attempts, or while on login page)
    const reqConfig = error.config || {};
    const isSilent = Boolean(reqConfig.silentAuth || reqConfig.skipAuthExpired);
    const isLoginEndpoint = Boolean(
      reqConfig.url && (reqConfig.url.includes('/login') || reqConfig.url.endsWith('login'))
    );
    const pathname = typeof window !== 'undefined' ? window.location.pathname : '';
    const isLoginPage = pathname.endsWith('/login') || pathname.includes('/admin/login');

    if (
      normalized.isAuthError &&
      !isSilent &&
      !isLoginEndpoint &&
      !isLoginPage &&
      typeof window !== 'undefined'
    ) {
      const now = Date.now();
      if (now - lastAuthExpiredDispatch > 3000) {
        lastAuthExpiredDispatch = now;
        window.dispatchEvent(new CustomEvent('auth:expired', { detail: normalized }));
      }
    }

    if (import.meta.env.DEV && !normalized.raw?.isDuplicate) {
      console.warn(`[API Error ${normalized.status}] ${normalized.message}`, normalized.errors || '');
    }

    return Promise.reject(normalized);
  }
);

/**
 * Standard HTTP Request Helpers
 */
export const http = {
  get: (url, params = {}, config = {}) => apiClient.get(url, { params, ...config }),
  post: (url, data = {}, config = {}) => apiClient.post(url, data, config),
  put: (url, data = {}, config = {}) => apiClient.put(url, data, config),
  patch: (url, data = {}, config = {}) => apiClient.patch(url, data, config),
  delete: (url, params = {}, config = {}) => apiClient.delete(url, { params, ...config }),

  /**
   * Multipart Form-Data Upload Helper
   * Allows browser/Axios to set Content-Type with correct boundary automatically.
   */
  upload: (url, formData, config = {}) =>
    apiClient.post(url, formData, {
      ...config,
      headers: {
        // Do not force Content-Type; browser/Axios automatically sets multipart/form-data with boundary
        ...(config.headers || {}),
      },
    }),

  /**
   * Binary / Blob File Download Helper
   */
  download: (url, params = {}, config = {}) =>
    apiClient.get(url, {
      params,
      responseType: 'blob',
      ...config,
    }),
};

export default apiClient;
