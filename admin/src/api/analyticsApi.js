import { http } from './client';

/**
 * Analytics & Business Intelligence API Service
 * Interacts with AnalyticsController.
 */
export const analyticsApi = {
  getAnalytics: (params = {}, signal) => http.get('/analytics', params, { signal }),
  exportCsv: (params = {}) => http.download('/analytics/export', { type: 'general', ...params }),
  exportBiCsv: (params = {}) => http.download('/analytics/export', { type: 'bi', ...params }),
};

export default analyticsApi;
