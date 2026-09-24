import apiClient from './apiClient';

/**
 * Direct Message API Service for Generic 1-on-1 Member Messaging
 */
export const directMessageApi = {
  /**
   * Get direct message conversations list and active conversation data
   * @param {number|string} [memberId] - Optional active partner member ID
   */
  async getMessages(memberId = null) {
    const url = memberId ? `/messages/chat/${memberId}` : '/messages';
    const response = await apiClient.get(url);
    return response.data;
  },

  /**
   * Fetch message thread history with a specific member and mark unread as read
   * @param {number|string} memberId
   */
  async fetchMessages(memberId) {
    const response = await apiClient.get(`/messages/${memberId}/fetch`);
    return response.data;
  },

  /**
   * Send a direct message (text and/or file attachment) to a member
   * @param {number|string} memberId
   * @param {FormData|Object} data - { message, attachment }
   */
  async sendMessage(memberId, data) {
    const response = await apiClient.post(`/messages/${memberId}`, data, {
      headers: data instanceof FormData ? { 'Content-Type': 'multipart/form-data' } : undefined,
    });
    return response.data;
  },
};

export default directMessageApi;
