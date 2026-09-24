import apiClient from './apiClient';

export const eventApi = {
  /**
   * Get events listing with search & filters
   * @param {Object} [params] - { search, timeframe, type, category, page }
   */
  async getEvents(params = {}) {
    const response = await apiClient.get('/events', { params });
    return response.data;
  },

  /**
   * Get create event metadata (categories)
   */
  async getCreateEventData() {
    const response = await apiClient.get('/events/create');
    return response.data;
  },

  /**
   * Create a new event
   * @param {FormData|Object} data
   */
  async createEvent(data) {
    const response = await apiClient.post('/events', data, {
      headers: data instanceof FormData ? { 'Content-Type': 'multipart/form-data' } : undefined,
    });
    return response.data;
  },

  /**
   * Get single event detail
   * @param {number|string} id
   * @param {number} [page=1] - for discussion posts pagination
   */
  async getEvent(id, page = 1) {
    const response = await apiClient.get(`/events/${id}`, {
      params: { page },
    });
    return response.data;
  },

  /**
   * Get edit event metadata and form values
   * @param {number|string} id
   */
  async getEditEventData(id) {
    const response = await apiClient.get(`/events/${id}/edit`);
    return response.data;
  },

  /**
   * Update an existing event
   * @param {number|string} id
   * @param {FormData|Object} data
   */
  async updateEvent(id, data) {
    let payload = data;
    const config = {};

    if (data instanceof FormData) {
      data.append('_method', 'PUT');
      payload = data;
      config.headers = { 'Content-Type': 'multipart/form-data' };
      const response = await apiClient.post(`/events/${id}`, payload, config);
      return response.data;
    }

    const response = await apiClient.put(`/events/${id}`, payload);
    return response.data;
  },

  /**
   * Cancel and delete an event
   * @param {number|string} id
   */
  async deleteEvent(id) {
    const response = await apiClient.delete(`/events/${id}`);
    return response.data;
  },

  /**
   * Respond to an event (RSVP)
   * @param {number|string} id
   * @param {'going'|'interested'|'maybe'|'not_going'} responseStatus
   */
  async respondToEvent(id, responseStatus) {
    const response = await apiClient.post(`/events/${id}/respond`, {
      response: responseStatus,
    });
    return response.data;
  },

  /**
   * Invite a member to an event
   * @param {number|string} id
   * @param {number|string} invitedId
   */
  async inviteMember(id, invitedId) {
    const response = await apiClient.post(`/events/${id}/invite`, {
      invited_id: invitedId,
    });
    return response.data;
  },

  /**
   * Post in the event discussion feed
   * @param {number|string} id
   * @param {FormData|Object} data
   */
  async storeEventPost(id, data) {
    const response = await apiClient.post(`/events/${id}/posts`, data, {
      headers: data instanceof FormData ? { 'Content-Type': 'multipart/form-data' } : undefined,
    });
    return response.data;
  },

  /**
   * Get Host Outreach & Contact Center data (Host only)
   * @param {number|string} id
   */
  async getOutreach(id) {
    const response = await apiClient.get(`/events/${id}/outreach`);
    return response.data;
  },

  /**
   * Get campaign foundation for an event (Host only)
   * @param {number|string} id
   */
  async getEventCampaign(id) {
    const response = await apiClient.get(`/events/${id}/campaign`);
    return response.data;
  },

  /**
   * Create or retrieve campaign foundation for an event (Host only)
   * @param {number|string} id
   * @param {Object} [data]
   */
  async createEventCampaign(id, data = {}) {
    const response = await apiClient.post(`/events/${id}/campaign`, data);
    return response.data;
  },

  /**
   * Allocate initial budget to event campaign (Host only)
   * @param {number|string} id
   * @param {Object} data { budget: number, currency?: string, idempotency_key?: string }
   */
  async allocateEventCampaignBudget(id, data) {
    const response = await apiClient.post(`/events/${id}/campaign/allocate-budget`, data);
    return response.data;
  },

  /**
   * Add funds to event campaign (Host only)
   * @param {number|string} id
   * @param {Object} data { amount: number, idempotency_key?: string }
   */
  async addFundsToEventCampaign(id, data) {
    const response = await apiClient.post(`/events/${id}/campaign/add-funds`, data);
    return response.data;
  },

  /**
   * Activate event campaign (Host only)
   * @param {number|string} id
   * @param {Object} [data]
   */
  async activateEventCampaign(id, data = {}) {
    const response = await apiClient.post(`/events/${id}/campaign/activate`, data);
    return response.data;
  },

  /**
   * Pause event campaign (Host only)
   * @param {number|string} id
   */
  async pauseEventCampaign(id) {
    const response = await apiClient.post(`/events/${id}/campaign/pause`);
    return response.data;
  },

  /**
   * Resume event campaign (Host only)
   * @param {number|string} id
   */
  async resumeEventCampaign(id) {
    const response = await apiClient.post(`/events/${id}/campaign/resume`);
    return response.data;
  },

  /**
   * Get personalized event reward preview for the authenticated verified member
   * @param {number|string} id
   * @param {number|string|null} [campaignId]
   */
  async getEventRewardPreview(id, campaignId = null) {
    const params = campaignId ? { campaign_id: campaignId } : {};
    const response = await apiClient.get(`/events/${id}/reward-preview`, { params });
    return response.data;
  },

  /**
   * Qualify for Paid Event reward by expressing interest
   * @param {number|string} id
   * @param {Object} [payload]
   */
  async qualifyEventInterest(id, payload = {}) {
    const response = await apiClient.post(`/events/${id}/campaign/qualify-interest`, payload);
    return response.data;
  },

  /**
   * Get creator participant and budget analytics for an event campaign (Host only)
   * @param {number|string} id
   * @param {Object} [params] - { q, reward_status, date_preset, page, per_page }
   */
  async getEventCampaignAnalytics(id, params = {}) {
    const response = await apiClient.get(`/events/${id}/campaign/analytics`, { params });
    return response.data;
  },

  /**
   * Get paginated itemized participants for an event campaign (Host only)
   * @param {number|string} id
   * @param {Object} [params] - { q, reward_status, date_preset, page, per_page }
   */
  async getEventCampaignParticipants(id, params = {}) {
    const response = await apiClient.get(`/events/${id}/campaign/participants`, { params });
    return response.data;
  },

  /**
   * Reactivate an exhausted or stopped event campaign after adding funds (Host only)
   * @param {number|string} id
   * @param {Object} [data]
   */
  async reactivateEventCampaign(id, data = {}) {
    const response = await apiClient.post(`/events/${id}/campaign/reactivate`, data);
    return response.data;
  },
};

export default eventApi;
