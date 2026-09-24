import { http } from './client';

/**
 * Admin Feedback & Suggestions API Service
 * Interacts with FeedbackManagementController.
 */
export const feedbackSuggestionsApi = {
  getFeedbacks: (params = {}, signal) =>
    http.get('/feedback-suggestions', params, { signal }),

  getFeedback: (id, signal) =>
    http.get(`/feedback-suggestions/${id}`, {}, { signal }),

  updateStatus: (id, status) =>
    http.put(`/feedback-suggestions/${id}/status`, { status }),

  updateResponse: (id, admin_response, status) =>
    http.put(`/feedback-suggestions/${id}/response`, { admin_response, status }),

  deleteFeedback: (id) =>
    http.delete(`/feedback-suggestions/${id}`),

  bulkAction: (action, ids) =>
    http.post('/feedback-suggestions/bulk-action', { action, ids }),

  exportCsv: (params = {}) =>
    http.download('/feedback-suggestions/export', params),
};

export default feedbackSuggestionsApi;
