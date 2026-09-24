import apiClient from './apiClient';

/**
 * Member Dashboard API
 */
export const dashboardApi = {
  /**
   * Fetch dashboard data (member information, shortcuts, platform capabilities)
   */
  async getDashboard() {
    const response = await apiClient.get('/dashboard');
    return response.data;
  },
};

export default dashboardApi;
