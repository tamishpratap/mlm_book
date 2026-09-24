import apiClient from './apiClient';

/**
 * Member Watch / Video Portal API Service
 */
export const watchApi = {
  /**
   * Fetch watch video feed with filter and pagination
   * @param {string} filter - 'all' | 'trending' | 'my_videos' | 'saved'
   * @param {number} page
   */
  async getWatchFeed(filter = 'all', page = 1) {
    const response = await apiClient.get('/watch', {
      params: { filter, page },
    });
    return response.data;
  },

  /**
   * Send connection request to a suggested creator
   * @param {number} memberId
   */
  async connectCreator(memberId) {
    const response = await apiClient.post(`/friends/request/${memberId}`);
    return response.data;
  },
};

export default watchApi;
