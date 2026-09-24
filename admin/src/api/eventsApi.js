import { http } from './client';
import { buildFormData } from './utils/uploadHelper';

/**
 * Events Management API Service
 * Interacts with EventManagementController.
 */
export const eventsApi = {
  getEvents: (params = {}, signal) => http.get('/events', params, { signal }),
  getEvent: (eventId, signal) => http.get(`/events/${eventId}`, {}, { signal }),
  getEventEditData: (eventId) => http.get(`/events/${eventId}/edit`),
  updateEvent: (eventId, data, files = {}) => {
    const formData = buildFormData({ ...data, _method: 'PUT' }, files);
    return http.upload(`/events/${eventId}`, formData);
  },
  updateStatus: (eventId, status) => http.post(`/events/${eventId}/status`, { status }),
  deleteEvent: (eventId) => http.delete(`/events/${eventId}`),
  bulkAction: (action, ids) => http.post('/events/bulk-action', { action, ids }),
  exportCsv: (params = {}) => http.download('/events/export', params),
};

export default eventsApi;
