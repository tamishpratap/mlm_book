import apiClient from './apiClient';

export const businessApi = {
  /**
   * Get business pages listing
   * @param {Object} [params] - { tab, search, category, page }
   */
  async getBusinessPages(params = {}) {
    const response = await apiClient.get('/business-pages', { params });
    return response.data;
  },

  /**
   * Get metadata for creating a business page
   */
  async getCreateBusinessPageData() {
    const response = await apiClient.get('/business-pages/create');
    return response.data;
  },

  /**
   * Create a new business page
   * @param {FormData|Object} data
   */
  async createBusinessPage(data) {
    const response = await apiClient.post('/business-pages', data, {
      headers: data instanceof FormData ? { 'Content-Type': 'multipart/form-data' } : undefined,
    });
    return response.data;
  },

  /**
   * Get single business page detail
   * @param {string} slug
   * @param {Object} [params] - { tab, page, q, sort }
   */
  async getBusinessPage(slug, params = {}) {
    const response = await apiClient.get(`/business-pages/${slug}`, { params });
    return response.data;
  },

  /**
   * Get metadata and existing data for editing a business page
   * @param {string} slug
   */
  async getEditBusinessPageData(slug) {
    const response = await apiClient.get(`/business-pages/${slug}/edit`);
    return response.data;
  },

  /**
   * Update an existing business page
   * @param {string} slug
   * @param {FormData|Object} data
   */
  async updateBusinessPage(slug, data) {
    let payload = data;
    const config = {};

    if (data instanceof FormData) {
      data.append('_method', 'PUT');
      payload = data;
      config.headers = { 'Content-Type': 'multipart/form-data' };
      const response = await apiClient.post(`/business-pages/${slug}`, payload, config);
      return response.data;
    }

    const response = await apiClient.put(`/business-pages/${slug}`, payload);
    return response.data;
  },

  /**
   * Delete a business page
   * @param {string} slug
   */
  async deleteBusinessPage(slug) {
    const response = await apiClient.delete(`/business-pages/${slug}`);
    return response.data;
  },

  /**
   * Update business page profile photo / logo
   * @param {string} slug
   * @param {FormData} formData
   */
  async updateProfilePhoto(slug, formData) {
    const response = await apiClient.post(`/business-pages/${slug}/photo`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return response.data;
  },

  /**
   * Remove business page profile photo / logo
   * @param {string} slug
   */
  async removeProfilePhoto(slug) {
    const response = await apiClient.delete(`/business-pages/${slug}/photo`);
    return response.data;
  },

  /**
   * Update business page cover photo
   * @param {string} slug
   * @param {FormData} formData
   */
  async updateCoverPhoto(slug, formData) {
    const response = await apiClient.post(`/business-pages/${slug}/cover`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return response.data;
  },

  /**
   * Remove business page cover photo
   * @param {string} slug
   */
  async removeCoverPhoto(slug) {
    const response = await apiClient.delete(`/business-pages/${slug}/cover`);
    return response.data;
  },

  /**
   * Toggle follow / unfollow on a business page
   * @param {string} slug
   */
  async toggleFollow(slug) {
    const response = await apiClient.post(`/business-pages/${slug}/follow`);
    return response.data;
  },

  /**
   * Publish a timeline post on a business page
   * @param {string} slug
   * @param {FormData|Object} data
   */
  async storeBusinessPost(slug, data) {
    const response = await apiClient.post(`/business-pages/${slug}/posts`, data, {
      headers: data instanceof FormData ? { 'Content-Type': 'multipart/form-data' } : undefined,
    });
    return response.data;
  },

  /**
   * Toggle pin on a business page timeline post
   * @param {string} slug
   * @param {number|string} postId
   */
  async togglePinBusinessPost(slug, postId) {
    const response = await apiClient.post(`/business-pages/${slug}/posts/${postId}/pin`);
    return response.data;
  },

  /**
   * Delete a business page timeline post
   * @param {string} slug
   * @param {number|string} postId
   */
  async deleteBusinessPost(slug, postId) {
    const response = await apiClient.delete(`/business-pages/${slug}/posts/${postId}`);
    return response.data;
  },

  /**
   * Get public business directory listings
   * @param {Object} [params] - { q, category, country, state, city, sort, verified_only, page }
   */
  async getDirectory(params = {}) {
    const response = await apiClient.get('/business-directory', { params });
    return response.data;
  },

  /**
   * Get businesses by category slug in directory
   * @param {string} categorySlug
   * @param {Object} [params] - { q, sort, page }
   */
  async getDirectoryCategory(categorySlug, params = {}) {
    const response = await apiClient.get(`/business-directory/categories/${categorySlug}`, { params });
    return response.data;
  },

  /**
   * Get Business Page team details, active members, pending invitations
   * @param {string} slug
   */
  async getTeam(slug) {
    const response = await apiClient.get(`/business-pages/${slug}/team`);
    return response.data;
  },

  /**
   * Invite a new team member
   * @param {string} slug
   * @param {Object} data - { invitee_id, role }
   */
  async inviteTeamMember(slug, data) {
    const response = await apiClient.post(`/business-pages/${slug}/team/invite`, data);
    return response.data;
  },

  /**
   * Cancel a pending team invitation
   * @param {string} slug
   * @param {number|string} invitationId
   */
  async cancelTeamInvitation(slug, invitationId) {
    const response = await apiClient.delete(`/business-pages/${slug}/team/invitations/${invitationId}`);
    return response.data;
  },

  /**
   * Change a team member's role
   * @param {string} slug
   * @param {number|string} memberId - ID in business_team_members
   * @param {string} role - 'admin', 'editor', 'moderator', 'analyst'
   */
  async updateTeamMemberRole(slug, memberId, role) {
    const response = await apiClient.put(`/business-pages/${slug}/team/members/${memberId}/role`, { role });
    return response.data;
  },

  /**
   * Remove a team member
   * @param {string} slug
   * @param {number|string} memberId - ID in business_team_members
   */
  async removeTeamMember(slug, memberId) {
    const response = await apiClient.delete(`/business-pages/${slug}/team/members/${memberId}`);
    return response.data;
  },

  /**
   * Accept a business page team invitation
   * @param {number|string} invitationId
   */
  async acceptInvitation(invitationId) {
    const response = await apiClient.post(`/business-invitations/${invitationId}/accept`);
    return response.data;
  },

  /**
   * Reject a business page team invitation
   * @param {number|string} invitationId
   */
  async rejectInvitation(invitationId) {
    const response = await apiClient.post(`/business-invitations/${invitationId}/reject`);
    return response.data;
  },

  /**
   * Handle (accept/reject) a follower request for a private business page
   * @param {string} slug
   * @param {number|string} followerId - ID in business_followers
   * @param {string} [action='accept'] - 'accept' or 'reject'
   */
  async handleFollowRequest(slug, followerId, action = 'accept') {
    const response = await apiClient.post(`/business-pages/${slug}/follow-requests/${followerId}`, { action });
    return response.data;
  },

  /**
   * Remove a follower from the business page
   * @param {string} slug
   * @param {number|string} followerId - ID in business_followers
   */
  async removeFollower(slug, followerId) {
    const response = await apiClient.delete(`/business-pages/${slug}/followers/${followerId}`);
    return response.data;
  },

  /**
   * Invite a friend or member to follow the business page
   * @param {string} slug
   * @param {number|string} inviteeId
   */
  async inviteToFollow(slug, inviteeId) {
    const response = await apiClient.post(`/business-pages/${slug}/invite-to-follow`, { invitee_id: inviteeId });
    return response.data;
  },

  /**
   * Submit a new review for a business page
   * @param {string} slug
   * @param {FormData|Object} data - { rating, recommendation, title, body, photos }
   */
  async storeReview(slug, data) {
    const response = await apiClient.post(`/business-pages/${slug}/reviews`, data, {
      headers: data instanceof FormData ? { 'Content-Type': 'multipart/form-data' } : undefined,
    });
    return response.data;
  },

  /**
   * Update an existing business review
   * @param {string} slug
   * @param {number|string} reviewId
   * @param {Object} data - { rating, recommendation, title, body }
   */
  async updateReview(slug, reviewId, data) {
    const response = await apiClient.put(`/business-pages/${slug}/reviews/${reviewId}`, data);
    return response.data;
  },

  /**
   * Delete a business review
   * @param {string} slug
   * @param {number|string} reviewId
   */
  async deleteReview(slug, reviewId) {
    const response = await apiClient.delete(`/business-pages/${slug}/reviews/${reviewId}`);
    return response.data;
  },

  /**
   * Post or update official team/owner reply to a review
   * @param {string} slug
   * @param {number|string} reviewId
   * @param {string} reply
   */
  async storeReviewReply(slug, reviewId, reply) {
    const response = await apiClient.post(`/business-pages/${slug}/reviews/${reviewId}/reply`, { reply });
    return response.data;
  },

  /**
   * Vote on a business review (helpful or unhelpful)
   * @param {string} slug
   * @param {number|string} reviewId
   * @param {'helpful'|'unhelpful'} [voteType='helpful']
   */
  async voteReview(slug, reviewId, voteType = 'helpful') {
    const response = await apiClient.post(`/business-pages/${slug}/reviews/${reviewId}/vote`, { vote_type: voteType });
    return response.data;
  },

  /**
   * Report an inappropriate review
   * @param {string} slug
   * @param {number|string} reviewId
   * @param {Object} data - { reason, details }
   */
  async reportReview(slug, reviewId, data) {
    const response = await apiClient.post(`/business-pages/${slug}/reviews/${reviewId}/report`, data);
    return response.data;
  },

  /**
   * Toggle visibility (hide/unhide) of a review (Admin/Owner only)
   * @param {string} slug
   * @param {number|string} reviewId
   */
  async toggleHideReview(slug, reviewId) {
    const response = await apiClient.post(`/business-pages/${slug}/reviews/${reviewId}/hide`);
    return response.data;
  },

  // -------------------------------------------------------------------------
  // Business Inbox & Customer Messaging
  // -------------------------------------------------------------------------

  /**
   * Get business inbox conversations & metadata
   * @param {string} slug
   * @param {Object} [params] - { filter, q, page }
   */
  async getInbox(slug, params = {}) {
    const response = await apiClient.get(`/business-pages/${slug}/inbox`, { params });
    return response.data;
  },

  /**
   * Customer initiates conversation with a business page
   * @param {string} slug
   * @param {Object} data - { message }
   */
  async startConversation(slug, data) {
    const response = await apiClient.post(`/business-pages/${slug}/inbox/chat`, data);
    return response.data;
  },

  /**
   * Get active conversation details and messages thread
   * @param {string} slug
   * @param {number|string} conversationId
   */
  async getConversation(slug, conversationId) {
    const response = await apiClient.get(`/business-pages/${slug}/inbox/conversations/${conversationId}`);
    return response.data;
  },

  /**
   * Send message or attachment in a conversation
   * @param {string} slug
   * @param {number|string} conversationId
   * @param {FormData|Object} data - { message, attachment }
   */
  async sendMessage(slug, conversationId, data) {
    const response = await apiClient.post(
      `/business-pages/${slug}/inbox/conversations/${conversationId}/messages`,
      data,
      {
        headers: data instanceof FormData ? { 'Content-Type': 'multipart/form-data' } : undefined,
      }
    );
    return response.data;
  },

  /**
   * Accept or reject a message request from a non-follower
   * @param {string} slug
   * @param {number|string} conversationId
   * @param {'accept'|'reject'} action
   */
  async handleMessageRequest(slug, conversationId, action = 'accept') {
    const response = await apiClient.post(
      `/business-pages/${slug}/inbox/conversations/${conversationId}/request`,
      { action }
    );
    return response.data;
  },

  /**
   * Star / Unstar conversation
   * @param {string} slug
   * @param {number|string} conversationId
   */
  async toggleStarConversation(slug, conversationId) {
    const response = await apiClient.post(
      `/business-pages/${slug}/inbox/conversations/${conversationId}/star`
    );
    return response.data;
  },

  /**
   * Pin / Unpin conversation
   * @param {string} slug
   * @param {number|string} conversationId
   */
  async togglePinConversation(slug, conversationId) {
    const response = await apiClient.post(
      `/business-pages/${slug}/inbox/conversations/${conversationId}/pin`
    );
    return response.data;
  },

  /**
   * Update conversation status (active, archived, closed)
   * @param {string} slug
   * @param {number|string} conversationId
   * @param {'active'|'archived'|'closed'} status
   */
  async updateConversationStatus(slug, conversationId, status) {
    const response = await apiClient.put(
      `/business-pages/${slug}/inbox/conversations/${conversationId}/status`,
      { status }
    );
    return response.data;
  },

  /**
   * Store a saved quick reply
   * @param {string} slug
   * @param {Object} data - { title, shortcut, message }
   */
  async storeQuickReply(slug, data) {
    const response = await apiClient.post(`/business-pages/${slug}/inbox/quick-replies`, data);
    return response.data;
  },

  /**
   * Delete a quick reply
   * @param {string} slug
   * @param {number|string} quickReplyId
   */
  async deleteQuickReply(slug, quickReplyId) {
    const response = await apiClient.delete(
      `/business-pages/${slug}/inbox/quick-replies/${quickReplyId}`
    );
    return response.data;
  },

  /**
   * Get business notifications
   * @param {string} slug
   * @param {Object} [params] - { filter }
   */
  async getInboxNotifications(slug, params = {}) {
    const response = await apiClient.get(`/business-pages/${slug}/notifications`, { params });
    return response.data;
  },

  /**
   * Mark all business notifications as read
   * @param {string} slug
   */
  async markInboxNotificationsRead(slug) {
    const response = await apiClient.post(`/business-pages/${slug}/notifications/read`);
    return response.data;
  },

  /**
   * Mark a single business notification as read
   * @param {string} slug
   * @param {number|string} notificationId
   */
  async markSingleNotificationRead(slug, notificationId) {
    const response = await apiClient.post(
      `/business-pages/${slug}/notifications/${notificationId}/read`
    );
    return response.data;
  },

  /**
   * Delete a single business notification
   * @param {string} slug
   * @param {number|string} notificationId
   */
  async deleteNotification(slug, notificationId) {
    const response = await apiClient.delete(
      `/business-pages/${slug}/notifications/${notificationId}`
    );
    return response.data;
  },

  /**
   * Clear all business notifications
   * @param {string} slug
   */
  async clearAllNotifications(slug) {
    const response = await apiClient.delete(`/business-pages/${slug}/notifications`);
    return response.data;
  },

  /**
   * Get business page verification data and status
   * @param {string} slug
   */
  async getVerification(slug) {
    const response = await apiClient.get(`/business-pages/${slug}/verification`);
    return response.data;
  },

  /**
   * Submit business page verification application with document
   * @param {string} slug
   * @param {FormData} formData - { document_type, document_number, document_file }
   */
  async submitVerification(slug, formData) {
    const response = await apiClient.post(`/business-pages/${slug}/verification`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return response.data;
  },

  /**
   * Get business page analytics insights
   * @param {string} slug
   * @param {Object} [params] - { period }
   */
  async getAnalytics(slug, params = {}) {
    const response = await apiClient.get(`/business-pages/${slug}/analytics`, { params });
    return response.data;
  },

  /**
   * Get business page dynamic analytics data
   * @param {string} slug
   * @param {Object} [params] - { period }
   */
  async getAnalyticsData(slug, params = {}) {
    const response = await apiClient.get(`/business-pages/${slug}/analytics/data`, { params });
    return response.data;
  },

  /**
   * Get ad campaigns for a business page
   * @param {string} slug
   * @param {Object} [params] - { status, approval_status, q, page }
   */
  async getAdCampaigns(slug, params = {}) {
    const response = await apiClient.get(`/business-pages/${slug}/ad-campaigns`, { params });
    return response.data;
  },

  /**
   * Get single ad campaign details
   * @param {string} slug
   * @param {string|number} campaignId
   */
  async getAdCampaign(slug, campaignId) {
    const response = await apiClient.get(`/business-pages/${slug}/ad-campaigns/${campaignId}`);
    return response.data;
  },

  /**
   * Create a new draft ad campaign
   * @param {string} slug
   * @param {Object} data - { campaign_name, post_id, budget, start_at, end_at, target_audience }
   */
  async createAdCampaign(slug, data) {
    const response = await apiClient.post(`/business-pages/${slug}/ad-campaigns`, data);
    return response.data;
  },

  /**
   * Update an existing draft ad campaign
   * @param {string} slug
   * @param {string|number} campaignId
   * @param {Object} data
   */
  async updateAdCampaign(slug, campaignId, data) {
    const response = await apiClient.put(`/business-pages/${slug}/ad-campaigns/${campaignId}`, data);
    return response.data;
  },

  /**
   * Submit draft ad campaign for admin review
   * @param {string} slug
   * @param {string|number} campaignId
   */
  async submitAdCampaign(slug, campaignId) {
    const response = await apiClient.post(`/business-pages/${slug}/ad-campaigns/${campaignId}/submit`);
    return response.data;
  },

  /**
   * Pause an active or approved ad campaign
   * @param {string} slug
   * @param {string|number} campaignId
   */
  async pauseAdCampaign(slug, campaignId) {
    const response = await apiClient.post(`/business-pages/${slug}/ad-campaigns/${campaignId}/pause`);
    return response.data;
  },

  /**
   * Resume a paused ad campaign
   * @param {string} slug
   * @param {string|number} campaignId
   */
  async resumeAdCampaign(slug, campaignId) {
    const response = await apiClient.post(`/business-pages/${slug}/ad-campaigns/${campaignId}/resume`);
    return response.data;
  },

  /**
   * Stop an ad campaign
   * @param {string} slug
   * @param {string|number} campaignId
   */
  async stopAdCampaign(slug, campaignId) {
    const response = await apiClient.post(`/business-pages/${slug}/ad-campaigns/${campaignId}/stop`);
    return response.data;
  },

  /**
   * Restart a stopped ad campaign
   * @param {string} slug
   * @param {string|number} campaignId
   */
  async restartAdCampaign(slug, campaignId) {
    const response = await apiClient.post(`/business-pages/${slug}/ad-campaigns/${campaignId}/restart`);
    return response.data;
  },

  /**
   * Close an ad campaign and refund remaining balance to p2p fund wallet
   * @param {string} slug
   * @param {string|number} campaignId
   */
  async closeAdCampaign(slug, campaignId) {
    const response = await apiClient.post(`/business-pages/${slug}/ad-campaigns/${campaignId}/close`);
    return response.data;
  },

  /**
   * Add funds (top-up) to an existing ad campaign and reactivate if exhausted
   * @param {string} slug
   * @param {string|number} campaignId
   * @param {Object} data - { amount }
   */
  async addFundsToAdCampaign(slug, campaignId, data) {
    const response = await apiClient.post(`/business-pages/${slug}/ad-campaigns/${campaignId}/add-funds`, data);
    return response.data;
  },

  /**
   * Check if a specific post already has an associated ad campaign
   * @param {string} slug
   * @param {string|number} postId
   */
  async checkPostAdCampaign(slug, postId) {
    const response = await apiClient.get(`/business-pages/${slug}/posts/${postId}/ad-campaign`);
    return response.data;
  },

  /**
   * Get aggregate ad analytics for a business page
   * @param {string} slug
   */
  async getPageAdAnalytics(slug) {
    const response = await apiClient.get(`/business-pages/${slug}/ad-campaigns/analytics`);
    return response.data;
  },

  /**
   * Get single campaign performance analytics
   * @param {string} slug
   * @param {string|number} campaignId
   */
  async getCampaignAnalytics(slug, campaignId) {
    const response = await apiClient.get(`/business-pages/${slug}/ad-campaigns/${campaignId}/analytics`);
    return response.data;
  },

  /**
   * Get itemized user engagements (clicks, landing-page visits, rewards) for a campaign
   * @param {string} slug
   * @param {string|number} campaignId
   * @param {Object} [params] - { action, q, page, per_page, verified_only, member_id }
   */
  async getCampaignEngagements(slug, campaignId, params = {}) {
    const response = await apiClient.get(`/business-pages/${slug}/ad-campaigns/${campaignId}/engagements`, { params });
    return response.data;
  },

  /**
   * Get single member's complete engagement timeline and reward drill-down on a campaign
   * @param {string} slug
   * @param {string|number} campaignId
   * @param {string|number} memberId
   */
  async getCampaignMemberEngagement(slug, campaignId, memberId) {
    const response = await apiClient.get(`/business-pages/${slug}/ad-campaigns/${campaignId}/engagements/${memberId}`);
    return response.data;
  },

  /**
   * Export campaign audience records or full activity log as CSV
   * @param {string} slug
   * @param {string|number} campaignId
   * @param {Object} [params] - { action, reward_status, verification, date_preset, start_date, end_date, sort, q, selected_ids, mode }
   */
  async exportCampaignEngagements(slug, campaignId, params = {}) {
    const response = await apiClient.get(`/business-pages/${slug}/ad-campaigns/${campaignId}/export-engagements`, {
      params,
      responseType: 'blob',
    });
    return response.data;
  },

  /**
   * Validate and prepare audience reach-out operations for selected campaign members
   * @param {string} slug
   * @param {string|number} campaignId
   * @param {Object} data - { member_ids, method, message }
   */
  async contactCampaignAudience(slug, campaignId, data) {
    const response = await apiClient.post(`/business-pages/${slug}/ad-campaigns/${campaignId}/contact-audience`, data);
    return response.data;
  },

  /**
   * Get active sponsored ads for verified member feed delivery
   */
  async getAdFeed() {
    const response = await apiClient.get('/ad-campaigns/feed');
    return response.data;
  },

  /**
   * Record click event for a sponsored ad campaign
   * @param {string|number} campaignId
   * @param {string} [clickKey]
   * @param {string} [placement='social_feed']
   */
  async recordAdClick(campaignId, clickKey = null, placement = 'social_feed') {
    const response = await apiClient.post(`/ad-campaigns/${campaignId}/click`, {
      click_key: clickKey,
      placement,
    });
    return response.data;
  },

  /**
   * Qualify a verified user landing-page visit for a sponsored ad campaign ($0.05 reward)
   * @param {string|number} campaignId
   * @param {Object} data - { qualifying_event_id, landing_page_url }
   */
  async qualifyAdVisit(campaignId, data = {}) {
    const response = await apiClient.post(`/ad-campaigns/${campaignId}/qualify-visit`, data);
    return response.data;
  },

  /**
   * Get active advertising deposit configuration (QR, UPI ID, Exchange Rate)
   */
  async getDepositSettings() {
    const response = await apiClient.get('/funds/deposit-settings');
    return response.data;
  },

  /**
   * Submit an advertising deposit request
   * @param {Object} data - { amount_inr, transaction_reference, business_page_slug }
   */
  async createDeposit(data) {
    const response = await apiClient.post('/funds/deposits', data);
    return response.data;
  },

  /**
   * Get authenticated member's advertising deposit history
   * @param {Object} [params] - { business_page_slug, page, per_page }
   */
  async getMemberDeposits(params = {}) {
    const response = await apiClient.get('/funds/deposits', { params });
    return response.data;
  },
};

export default businessApi;
