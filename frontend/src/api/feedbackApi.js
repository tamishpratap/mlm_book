import apiClient from './apiClient';

/**
 * Member Feedback & Suggestions API Service
 */
export const feedbackApi = {
  /**
   * Fetch authenticated member's submitted feedbacks and suggestions
   */
  async getMyFeedbacks() {
    const response = await apiClient.get('/feedback-suggestions');
    return response.data;
  },

  /**
   * Submit new feedback, suggestion, idea, or inquiry
   * @param {Object} data - { type, subject, message }
   */
  async submitFeedback(data) {
    const response = await apiClient.post('/feedback-suggestions', data);
    return response.data;
  },
};

export default feedbackApi;
