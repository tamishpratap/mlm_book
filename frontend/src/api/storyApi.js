import apiClient from './apiClient';

/**
 * Member Stories API Service
 */
export const storyApi = {
  /**
   * Fetch stories feed for the current member and accepted connections
   */
  async getStories() {
    const response = await apiClient.get('/stories');
    return response.data;
  },

  /**
   * Fetch single story details and linear playback queue
   */
  async getStory(storyId) {
    const response = await apiClient.get(`/stories/${storyId}`);
    return response.data;
  },

  /**
   * Create / upload a new story (supports image or video)
   */
  async createStory(formData) {
    const response = await apiClient.post('/stories', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },

  /**
   * Delete a story (owner only)
   */
  async deleteStory(storyId) {
    const response = await apiClient.delete(`/stories/${storyId}`);
    return response.data;
  },

  /**
   * Toggle like on a story
   */
  async toggleLike(storyId) {
    const response = await apiClient.post(`/stories/${storyId}/like`);
    return response.data;
  },

  /**
   * React to a story with custom emotion (like, love, haha, wow, sad, angry)
   */
  async reactToStory(storyId, reaction = 'like') {
    const response = await apiClient.post(`/stories/${storyId}/react`, { reaction });
    return response.data;
  },

  /**
   * Get viewers list for a story (owner only)
   */
  async getViewers(storyId) {
    const response = await apiClient.get(`/stories/${storyId}/viewers`);
    return response.data;
  },

  /**
   * Get reactors list for a story (owner only)
   */
  async getReactors(storyId) {
    const response = await apiClient.get(`/stories/${storyId}/reactors`);
    return response.data;
  },

  /**
   * Get direct replies sent to a story
   */
  async getReplies(storyId) {
    const response = await apiClient.get(`/stories/${storyId}/replies`);
    return response.data;
  },

  /**
   * Send a direct reply to a story author
   */
  async replyToStory(storyId, message) {
    const response = await apiClient.post(`/stories/${storyId}/reply`, { message });
    return response.data;
  },

  /**
   * Delete a story reply
   */
  async deleteReply(replyId) {
    const response = await apiClient.delete(`/story-replies/${replyId}`);
    return response.data;
  },
};

export default storyApi;
