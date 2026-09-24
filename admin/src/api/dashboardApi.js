import { http } from './client';

/**
 * Dashboard Overview API Service
 * Interacts with DashboardController.
 */
export const dashboardApi = {
  /**
   * Fetch overview metrics, 14-day trends, and recent entity records.
   * Route: GET /admin/dashboard
   */
  getOverview: (params = {}, signal) => http.get('/dashboard', params, { signal }),
};

export default dashboardApi;
