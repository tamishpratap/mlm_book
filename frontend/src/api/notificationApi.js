import apiClient from './apiClient';

/**
 * Member Notifications API Service
 */
export const notificationApi = {
  /**
   * Get paginated notifications for the authenticated member with optional category filter
   * @param {Object} params - { filter: 'all' | 'unread' | 'friends' | 'stories' | 'posts' | 'comments' | 'system', page: number }
   */
  async getNotifications(params = {}) {
    const response = await apiClient.get('/notifications', {
      params,
    });
    return response.data;
  },

  /**
   * Get latest notifications for dropdown
   */
  async getDropdownNotifications() {
    const response = await apiClient.get('/notifications/dropdown');
    return response.data;
  },

  /**
   * Poll for new notifications and unread count
   * @param {number} unreadCount - Current unread count from client
   */
  async poll(unreadCount = 0) {
    const response = await apiClient.get('/notifications/poll', {
      params: { unread_count: unreadCount },
    });
    return response.data;
  },

  /**
   * Get single notification details by ID
   * @param {string} notificationId
   */
  async getNotification(notificationId) {
    const response = await apiClient.get(`/notifications/${notificationId}`);
    return response.data;
  },

  /**
   * Mark a single notification as read
   * @param {string} notificationId
   */
  async markAsRead(notificationId) {
    const response = await apiClient.post(`/notifications/${notificationId}/read`);
    return response.data;
  },

  /**
   * Mark all unread notifications as read
   */
  async markAllAsRead() {
    const response = await apiClient.post('/notifications/read-all');
    return response.data;
  },

  /**
   * Delete a single notification
   * @param {string} notificationId
   */
  async deleteNotification(notificationId) {
    const response = await apiClient.delete(`/notifications/${notificationId}`);
    return response.data;
  },

  /**
   * Clear all notifications for the member
   */
  async clearAll() {
    const response = await apiClient.delete('/notifications/clear-all');
    return response.data;
  },
};

export default notificationApi;
