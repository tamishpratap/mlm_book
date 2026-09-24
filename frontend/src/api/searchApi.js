import apiClient from './apiClient';

/**
 * Member Unified Search API Service
 */
export const searchApi = {
  /**
   * Perform unified search across all or specific categories
   * @param {string} query
   * @param {string} type - 'all' | 'members' | 'pages' | 'groups' | 'posts' | 'events'
   * @param {number} page
   */
  async search(query = '', type = 'all', page = 1) {
    const response = await apiClient.get('/search/results', {
      params: { q: query, type, page },
    });
    return response.data;
  },

  /**
   * Send connection request to a searched member
   * @param {number} memberId
   */
  async connectMember(memberId) {
    const response = await apiClient.post(`/friends/request/${memberId}`);
    return response.data;
  },

  /**
   * Accept connection request from search results
   * @param {number} friendshipId
   */
  async acceptConnection(friendshipId) {
    const response = await apiClient.post(`/friend-requests/${friendshipId}/accept`);
    return response.data;
  },

  /**
   * Cancel connection request from search results
   * @param {number} friendshipId
   */
  async cancelConnection(friendshipId) {
    const response = await apiClient.delete(`/friend-requests/${friendshipId}/cancel`);
    return response.data;
  },
};

export default searchApi;
