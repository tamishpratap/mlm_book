import apiClient from './apiClient';

/**
 * Member Posts API Service
 */
export const postApi = {
  /**
   * Fetch paginated smart feed posts
   */
  async getFeed(page = 1) {
    const response = await apiClient.get(`/socials?page=${page}`);
    return response.data;
  },

  /**
   * Fetch single post by ID
   */
  async getPost(postId) {
    const response = await apiClient.get(`/posts/${postId}`);
    return response.data;
  },

  /**
   * Check for newer posts since latest_id
   */
  async checkNewPosts(latestId) {
    const response = await apiClient.get(`/feed/check-new?latest_id=${latestId}`);
    return response.data;
  },

  /**
   * Fetch saved posts with pagination
   */
  async getSavedPosts(page = 1) {
    const response = await apiClient.get(`/saved-posts?page=${page}`);
    return response.data;
  },

  /**
   * Create a new post (supports multipart with optional body & media)
   */
  async createPost(formData) {
    const response = await apiClient.post('/posts', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },

  /**
   * Delete a post
   */
  async deletePost(postId) {
    const response = await apiClient.delete(`/posts/${postId}`);
    return response.data;
  },

  /**
   * React or toggle like on a post
   */
  async reactToPost(postId, reaction = 'like') {
    const response = await apiClient.post(`/posts/${postId}/react`, { reaction });
    return response.data;
  },

  /**
   * Toggle like on a post
   */
  async toggleLike(postId) {
    const response = await apiClient.post(`/posts/${postId}/like`);
    return response.data;
  },

  /**
   * Get members who liked a post
   */
  async getLikers(postId) {
    const response = await apiClient.get(`/posts/${postId}/likers`);
    return response.data;
  },

  /**
   * Get all reactors on a post
   */
  async getReactors(postId) {
    const response = await apiClient.get(`/posts/${postId}/reactors`);
    return response.data;
  },

  /**
   * Get post comments with offset pagination
   */
  async getComments(postId, offset = 0, limit = 4) {
    const response = await apiClient.get(`/posts/${postId}/comments?offset=${offset}&limit=${limit}`);
    return response.data;
  },

  /**
   * Add a top-level comment
   */
  async createComment(postId, comment) {
    const response = await apiClient.post(`/posts/${postId}/comments`, { comment });
    return response.data;
  },

  /**
   * Update an existing comment or reply
   */
  async updateComment(commentId, comment) {
    const response = await apiClient.put(`/comments/${commentId}`, { comment });
    return response.data;
  },

  /**
   * Delete a comment or reply
   */
  async deleteComment(commentId) {
    const response = await apiClient.delete(`/comments/${commentId}`);
    return response.data;
  },

  /**
   * Get replies for a parent comment
   */
  async getReplies(commentId, offset = 0) {
    const response = await apiClient.get(`/comments/${commentId}/replies?offset=${offset}`);
    return response.data;
  },

  /**
   * Store a reply to a comment
   */
  async createReply(commentId, comment) {
    const response = await apiClient.post(`/comments/${commentId}/replies`, { comment });
    return response.data;
  },

  /**
   * React to a comment or reply
   */
  async reactToComment(commentId, reaction = 'like') {
    const response = await apiClient.post(`/comments/${commentId}/react`, { reaction });
    return response.data;
  },

  /**
   * Get reactors for a comment
   */
  async getCommentReactors(commentId) {
    const response = await apiClient.get(`/comments/${commentId}/reactors`);
    return response.data;
  },

  /**
   * Share a post to feed
   */
  async sharePost(postId, shareMessage) {
    const response = await apiClient.post(`/posts/${postId}/share`, {
      share_message: shareMessage,
    });
    return response.data;
  },

  /**
   * Send a post to specific accepted connections
   */
  async sendToFriends(postId, friendIds, message) {
    const response = await apiClient.post(`/posts/${postId}/send-to-friends`, {
      friend_ids: friendIds,
      message,
    });
    return response.data;
  },

  /**
   * Get list of members who shared a post
   */
  async getSharers(postId) {
    const response = await apiClient.get(`/posts/${postId}/sharers`);
    return response.data;
  },

  /**
   * Toggle save/bookmark on a post
   */
  async toggleSave(postId) {
    const response = await apiClient.post(`/posts/${postId}/save`);
    return response.data;
  },

  /**
   * Hide a post from feed
   */
  async hidePost(postId) {
    const response = await apiClient.post(`/posts/${postId}/hide`);
    return response.data;
  },

  /**
   * Report a post for moderation
   */
  async reportPost(postId, reason, description) {
    const response = await apiClient.post(`/posts/${postId}/report`, {
      reason,
      description,
    });
    return response.data;
  },

  /**
   * Toggle pin state of author's post
   */
  async togglePin(postId) {
    const response = await apiClient.post(`/posts/${postId}/pin`);
    return response.data;
  },

  /**
   * Get accepted friends list (for sharing modal)
   */
  async getFriends() {
    const response = await apiClient.get('/friends');
    return response.data;
  },

  /**
   * Record delivery impression for a sponsored ad campaign
   * @param {string|number} campaignId
   * @param {string} [impressionKey]
   * @param {string} [placement='social_feed']
   */
  async recordAdImpression(campaignId, impressionKey = null, placement = 'social_feed') {
    const response = await apiClient.post(`/ad-campaigns/${campaignId}/impression`, {
      impression_key: impressionKey,
      placement,
    });
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
   * Record Interest in a sponsored ad campaign
   * @param {string|number} campaignId
   * @param {Object} [data] - { consent_accepted: boolean }
   */
  async recordAdInterest(campaignId, data = {}) {
    const response = await apiClient.post(`/ad-campaigns/${campaignId}/interest`, data);
    return response.data;
  },

  /**
   * Get dynamic campaign reward preview (direct verified referrals & active rules)
   * @param {string|number} campaignId
   */
  async getCampaignRewardPreview(campaignId) {
    const response = await apiClient.get(`/ad-campaigns/${campaignId}/reward-preview`);
    return response.data;
  },

  /**
   * Get landing-visit reward status for a campaign on the target page (No follow required)
   * @param {string|number} campaignId
   */
  async getLandingRewardStatus(campaignId) {
    const response = await apiClient.get(`/ad-campaigns/${campaignId}/landing-reward-status`);
    return response.data;
  },

  /**
   * Qualify landing visit for a campaign to earn the $0.05 reward (No follow required)
   * @param {string|number} campaignId
   * @param {Object} [data] - { qualifying_event_id, landing_page_url }
   */
  async qualifyLandingVisit(campaignId, data = {}) {
    const response = await apiClient.post(`/ad-campaigns/${campaignId}/qualify-visit`, {
      qualifying_event_id: data.qualifying_event_id || `visit_${campaignId}_${Date.now()}`,
      landing_page_url: data.landing_page_url || window.location.href,
      ...data,
    });
    return response.data;
  },

  /**
   * Get follow-to-earn status for a campaign on the target page
   * @param {string|number} campaignId
   */
  async getFollowRewardStatus(campaignId) {
    const response = await apiClient.get(`/ad-campaigns/${campaignId}/follow-reward-status`);
    return response.data;
  },

  /**
   * Follow the business page and earn the $0.05 campaign reward
   * @param {string|number} campaignId
   * @param {Object} [data] - { landing_page_url }
   */
  async followToEarn(campaignId, data = {}) {
    const response = await apiClient.post(`/ad-campaigns/${campaignId}/follow-to-earn`, data);
    return response.data;
  },
};

export default postApi;
