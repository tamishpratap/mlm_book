import { http } from './client';

/**
 * Notifications & Broadcasts API Service
 * Interacts with NotificationManagementController.
 */
export const notificationsApi = {
  getNotifications: (params = {}, signal) => http.get('/notifications', params, { signal }),
  getUnreadCount: (signal) => http.get('/notifications/unread-count', {}, { signal }),
  getDropdownData: (signal) => http.get('/notifications/dropdown', {}, { signal }),
  getNotification: (notificationId, signal) => http.get(`/notifications/${notificationId}`, {}, { signal }),
  sendBroadcast: (broadcastData) => http.post('/notifications/broadcast', broadcastData),
  markRead: (notificationId) => http.post(`/notifications/${notificationId}/read`),
  markAllRead: () => http.post('/notifications/mark-all-read'),
  bulkAction: (action, ids) => http.post('/notifications/bulk-action', { action, ids }),
  exportCsv: (params = {}) => http.download('/notifications/export', params),
};

export default notificationsApi;
