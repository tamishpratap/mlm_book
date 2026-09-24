import { http } from './client';

/**
 * Central Moderation Queue API Service
 * Interacts with ReportManagementController.
 */
export const reportsApi = {
  getReports: (params = {}, signal) => http.get('/reports', params, { signal }),
  getReport: (type, id, signal) => http.get(`/reports/${type}/${id}`, {}, { signal }),
  updateReportStatus: (typeOrId, idOrStatus, maybeStatus, notes = '') => {
    if (
      typeof idOrStatus === 'string' &&
      (idOrStatus === 'resolved' || idOrStatus === 'dismissed' || idOrStatus === 'pending')
    ) {
      const type = 'post';
      const id = typeOrId;
      const status = idOrStatus;
      const finalNotes = typeof maybeStatus === 'string' ? maybeStatus : '';
      return http.post(`/reports/${type}/${id}/status`, { status, notes: finalNotes });
    }
    const type = typeOrId || 'post';
    const id = idOrStatus;
    const status = maybeStatus;
    return http.post(`/reports/${type}/${id}/status`, { status, notes });
  },
  bulkAction: (action, items) =>
    http.post('/reports/bulk-action', { action, items, ids: items }),
  deleteReport: (typeOrId, maybeId) => {
    if (maybeId === undefined || maybeId === null) {
      return http.delete(`/reports/post/${typeOrId}`);
    }
    const type = typeOrId || 'post';
    return http.delete(`/reports/${type}/${maybeId}`);
  },
  deleteReportTarget: (type, id) => http.delete(`/reports/${type}/${id}/target`),
  exportCsv: (params = {}) => http.download('/reports/export', params),
};

export default reportsApi;
