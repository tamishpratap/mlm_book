import { http } from './client';

/**
 * Authentication API Service
 * Interacts with Laravel LoginController (admin guard) via /api/admin/...
 */
export const authApi = {
  /**
   * Initialize CSRF / session.
   */
  initCsrf: async () => {
    return true;
  },

  /**
   * Submit admin login credentials.
   * Route: POST /api/admin/login
   */
  login: async (credentials) => {
    const response = await http.post('/login', credentials);
    return response;
  },

  /**
   * Log out authenticated admin.
   * Route: POST /api/admin/logout
   */
  logout: async () => {
    try {
      await http.post('/logout');
    } catch {
      // Continue cleanup even if server is unreachable
    }
  },

  /**
   * Verify current session / authenticated admin profile.
   * Route: GET /api/admin/me
   * Defaults to silentAuth: true so session probing does not trigger auth:expired event.
   */
  checkSession: async (options = {}) => {
    const response = await http.get('/me', {}, { silentAuth: true, ...options });
    return response;
  },
};

export default authApi;
