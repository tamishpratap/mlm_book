import apiClient from './apiClient';

/**
 * Member Friendship, Follow, People, and Blocking API Service
 */
export const friendApi = {
  /**
   * Get paginated accepted friends for the authenticated member
   */
  async getFriends(page = 1) {
    const response = await apiClient.get('/friends', {
      params: { page },
    });
    return response.data;
  },

  /**
   * Get pending incoming and outgoing connection requests
   */
  async getFriendRequests() {
    const response = await apiClient.get('/friends/requests');
    return response.data;
  },

  /**
   * Send a connection / friend request to a member
   */
  async sendFriendRequest(memberId) {
    const response = await apiClient.post(`/friends/request/${memberId}`);
    return response.data;
  },

  /**
   * Accept an incoming connection / friend request
   */
  async acceptFriendRequest(friendshipId) {
    const response = await apiClient.post(`/friend-requests/${friendshipId}/accept`);
    return response.data;
  },

  /**
   * Reject an incoming connection / friend request
   */
  async rejectFriendRequest(friendshipId) {
    const response = await apiClient.post(`/friend-requests/${friendshipId}/reject`);
    return response.data;
  },

  /**
   * Cancel an outgoing connection / friend request
   */
  async cancelFriendRequest(friendshipId) {
    const response = await apiClient.delete(`/friend-requests/${friendshipId}/cancel`);
    return response.data;
  },

  /**
   * Remove / unfriend an existing connection
   */
  async removeFriend(memberId) {
    const response = await apiClient.delete(`/friends/${memberId}/remove`);
    return response.data;
  },

  /**
   * Get friends of a specific member
   */
  async getMemberFriends(memberId, page = 1) {
    const response = await apiClient.get(`/people/${memberId}/friends`, {
      params: { page },
    });
    return response.data;
  },

  /**
   * Get suggested new connections with search, country, and quick filter
   */
  async getSuggestions(params = {}) {
    const response = await apiClient.get('/people/suggestions', {
      params,
    });
    return response.data;
  },

  /**
   * Get public member profile with tabs and metrics
   */
  async getMemberProfile(memberId, tab = 'timeline') {
    const response = await apiClient.get(`/people/${memberId}`, {
      params: { tab },
    });
    return response.data;
  },

  /**
   * Toggle follow / unfollow on a member
   */
  async toggleFollow(memberId) {
    const response = await apiClient.post(`/people/${memberId}/follow`);
    return response.data;
  },

  /**
   * Get paginated followers of a member
   */
  async getFollowers(memberId, page = 1) {
    const response = await apiClient.get(`/people/${memberId}/followers`, {
      params: { page },
    });
    return response.data;
  },

  /**
   * Get paginated following list of a member
   */
  async getFollowing(memberId, page = 1) {
    const response = await apiClient.get(`/people/${memberId}/following`, {
      params: { page },
    });
    return response.data;
  },

  /**
   * Get paginated list of blocked / disconnected members
   */
  async getBlockedUsers(page = 1) {
    const response = await apiClient.get('/blocked-users', {
      params: { page },
    });
    return response.data;
  },

  /**
   * Get paginated list of disconnected members
   */
  async getDisconnections(page = 1) {
    return this.getBlockedUsers(page);
  },

  /**
   * Block / disconnect a member
   */
  async blockUser(memberId) {
    const response = await apiClient.post(`/people/${memberId}/block`);
    return response.data;
  },

  /**
   * Unblock a member
   */
  async unblockUser(memberId) {
    const response = await apiClient.delete(`/people/${memberId}/unblock`);
    return response.data;
  },
};

export default friendApi;
