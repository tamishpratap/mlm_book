import apiClient from './apiClient';

/**
 * Member Community API Service
 */
export const communityApi = {
  /**
   * Get paginated communities list with tab filtering and category/search
   * @param {Object} params - { tab: 'all'|'my'|'discover', search, category, page }
   */
  async getCommunities(params = {}) {
    const response = await apiClient.get('/community', { params });
    return response.data;
  },

  /**
   * Get single community details, feed, members, and role info
   * @param {string} slug
   * @param {string} [tab='feed']
   */
  async getCommunity(slug, tab) {
    const response = await apiClient.get(`/community/${slug}`, {
      params: tab ? { tab } : undefined,
    });
    return response.data;
  },

  /**
   * Get category and visibility options for community creation
   */
  async getCreateData() {
    const response = await apiClient.get('/community/create');
    return response.data;
  },

  /**
   * Create a new community
   * @param {FormData} formData
   */
  async createCommunity(formData) {
    const response = await apiClient.post('/community', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },

  /**
   * Get community edit form data
   * @param {string} slug
   */
  async getEditData(slug) {
    const response = await apiClient.get(`/community/${slug}/edit`);
    return response.data;
  },

  /**
   * Update community settings
   * @param {string} slug
   * @param {FormData|Object} data
   */
  async updateCommunity(slug, data) {
    if (data instanceof FormData) {
      data.append('_method', 'PUT');
      const response = await apiClient.post(`/community/${slug}`, data, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
      return response.data;
    }
    const response = await apiClient.put(`/community/${slug}`, data);
    return response.data;
  },

  /**
   * Delete a community (owner only)
   * @param {string} slug
   */
  async deleteCommunity(slug) {
    const response = await apiClient.delete(`/community/${slug}`);
    return response.data;
  },

  /**
   * Get Discovery page data (featured, trending, suggested, category breakdown, paginated explore)
   * @param {Object} params - { search, category, sort, visibility, page }
   */
  async getDiscovery(params = {}) {
    const response = await apiClient.get('/community/discover', { params });
    return response.data;
  },

  /**
   * Live AJAX search for communities
   * @param {string} q
   */
  async searchAjax(q) {
    const response = await apiClient.get('/community/search-ajax', {
      params: { q },
    });
    return response.data;
  },

  /**
   * Join a community (instant or request based on privacy)
   * @param {string} slug
   */
  async join(slug) {
    const response = await apiClient.post(`/community/${slug}/join`);
    return response.data;
  },

  /**
   * Leave a community
   * @param {string} slug
   */
  async leave(slug) {
    const response = await apiClient.post(`/community/${slug}/leave`);
    return response.data;
  },

  /**
   * Update community cover photo
   * @param {string} slug
   * @param {FormData} formData
   */
  async updateCover(slug, formData) {
    const response = await apiClient.post(`/community/${slug}/cover`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },

  /**
   * Remove community cover photo
   * @param {string} slug
   */
  async removeCover(slug) {
    const response = await apiClient.post(`/community/${slug}/cover/remove`);
    return response.data;
  },

  /**
   * Update community logo
   * @param {string} slug
   * @param {FormData} formData
   */
  async updateLogo(slug, formData) {
    const response = await apiClient.post(`/community/${slug}/logo`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },

  /**
   * Remove community logo
   * @param {string} slug
   */
  async removeLogo(slug) {
    const response = await apiClient.post(`/community/${slug}/logo/remove`);
    return response.data;
  },

  /**
   * Create a post inside a community
   * @param {string} slug
   * @param {FormData} formData
   */
  async createCommunityPost(slug, formData) {
    const response = await apiClient.post(`/community/${slug}/posts`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },

  /**
   * Toggle pin state for a community post (Admin only)
   * @param {string} slug
   * @param {number|string} postId
   */
  async togglePinPost(slug, postId) {
    const response = await apiClient.post(`/community/${slug}/posts/${postId}/pin`);
    return response.data;
  },

  /**
   * Toggle announcement badge for a community post (Admin only)
   * @param {string} slug
   * @param {number|string} postId
   */
  async toggleAnnouncementPost(slug, postId) {
    const response = await apiClient.post(`/community/${slug}/posts/${postId}/announcement`);
    return response.data;
  },

  /**
   * Delete a community post
   * @param {string} slug
   * @param {number|string} postId
   */
  async deleteCommunityPost(slug, postId) {
    const response = await apiClient.delete(`/community/${slug}/posts/${postId}`);
    return response.data;
  },

  /**
   * Get community admin & moderation panel data
   * @param {string} slug
   * @param {string} [tab='overview']
   */
  async getAdminPanel(slug, tab = 'overview') {
    const response = await apiClient.get(`/community/${slug}/admin`, {
      params: tab ? { tab } : undefined,
    });
    return response.data;
  },

  /**
   * Ban a member from community
   * @param {string} slug
   * @param {Object} payload - { member_id, reason, duration }
   */
  async banMember(slug, payload) {
    const response = await apiClient.post(`/community/${slug}/moderation/ban`, payload);
    return response.data;
  },

  /**
   * Unban a member from community
   * @param {string} slug
   * @param {Object} payload - { member_id }
   */
  async unbanMember(slug, payload) {
    const response = await apiClient.post(`/community/${slug}/moderation/unban`, payload);
    return response.data;
  },

  /**
   * Mute a member in community
   * @param {string} slug
   * @param {Object} payload - { member_id, reason, duration }
   */
  async muteMember(slug, payload) {
    const response = await apiClient.post(`/community/${slug}/moderation/mute`, payload);
    return response.data;
  },

  /**
   * Unmute a member in community
   * @param {string} slug
   * @param {Object} payload - { member_id }
   */
  async unmuteMember(slug, payload) {
    const response = await apiClient.post(`/community/${slug}/moderation/unmute`, payload);
    return response.data;
  },

  /**
   * Issue a warning to a member
   * @param {string} slug
   * @param {Object} payload - { member_id, reason }
   */
  async warnMember(slug, payload) {
    const response = await apiClient.post(`/community/${slug}/moderation/warn`, payload);
    return response.data;
  },

  /**
   * Submit a content or member report
   * @param {string} slug
   * @param {Object} payload - { reportable_type, reportable_id, reason, details }
   */
  async submitReport(slug, payload) {
    const response = await apiClient.post(`/community/${slug}/reports`, payload);
    return response.data;
  },

  /**
   * Handle (resolve/approve/reject) a moderation report
   * @param {string} slug
   * @param {number|string} reportId
   * @param {Object} payload - { status, delete_content }
   */
  async handleReport(slug, reportId, payload) {
    const response = await apiClient.post(`/community/${slug}/reports/${reportId}/handle`, payload);
    return response.data;
  },

  /**
   * Update community governance & moderation settings
   * @param {string} slug
   * @param {Object} payload
   */
  async updateGovernanceSettings(slug, payload) {
    const response = await apiClient.put(`/community/${slug}/settings`, payload);
    return response.data;
  },

  /**
   * Transfer community ownership (Owner only)
   * @param {string} slug
   * @param {Object} payload - { new_owner_id }
   */
  async transferOwnership(slug, payload) {
    const response = await apiClient.post(`/community/${slug}/transfer-ownership`, payload);
    return response.data;
  },

  /**
   * Get community notifications list
   * @param {string} [category='all'] - 'all' | 'unread' | 'announcements' | 'moderation'
   * @param {number} [page=1]
   */
  async getCommunityNotifications(category = 'all', page = 1) {
    const response = await apiClient.get('/community/notifications', {
      params: { category, page },
    });
    return response.data;
  },

  /**
   * Mark a community notification as read
   * @param {string} id
   */
  async markCommunityNotificationRead(id) {
    const response = await apiClient.post(`/community/notifications/${id}/read`);
    return response.data;
  },

  /**
   * Mark all community notifications as read
   */
  async markAllCommunityNotificationsRead() {
    const response = await apiClient.post('/community/notifications/mark-all-read');
    return response.data;
  },

  /**
   * Get unread community notifications count
   */
  async getUnreadCommunityNotificationsCount() {
    const response = await apiClient.get('/community/notifications/unread-count');
    return response.data;
  },

  /**
   * Update member's notification preferences for a community
   * @param {string} slug
   * @param {Object} payload - { notification_level, mute_duration }
   */
  async updateNotificationPreferences(slug, payload) {
    const response = await apiClient.post(`/community/${slug}/preferences`, payload);
    return response.data;
  },

  /**
   * Get community activity timeline feed
   * @param {string} slug
   * @param {string} [filter='all'] - 'all' | 'posts' | 'members' | 'announcements' | 'moderation' | 'settings'
   * @param {number} [page=1]
   */
  async getActivityTimeline(slug, filter = 'all', page = 1) {
    const response = await apiClient.get(`/community/${slug}/activity`, {
      params: { filter, page },
    });
    return response.data;
  },

  /**
   * Get community analytics & performance metrics
   * @param {string} slug
   * @param {string} [period='7days'] - '7days' | '30days' | '90days' | 'this_year' | 'all'
   */
  async getCommunityAnalytics(slug, period = '7days') {
    const response = await apiClient.get(`/community/${slug}/analytics`, {
      params: { period },
    });
    return response.data;
  },

  /**
   * Export community analytics data as CSV file
   * @param {string} slug
   */
  async exportCommunityAnalyticsCsv(slug) {
    const response = await apiClient.get(`/community/${slug}/analytics/export`, {
      responseType: 'blob',
    });
    return response.data;
  },
};

export default communityApi;
