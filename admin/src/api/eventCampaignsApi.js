import { http } from './client';

/**
 * Admin Event Campaigns Management API Service
 * Interacts with Admin\\EventCampaignManagementController.
 */
export const eventCampaignsApi = {
  getCampaigns: (params = {}, signal) => http.get('/event-campaigns', params, { signal }),
  getCampaign: (campaignId, signal) => http.get(`/event-campaigns/${campaignId}`, {}, { signal }),
  getParticipants: (campaignId, params = {}, signal) => http.get(`/event-campaigns/${campaignId}/participants`, params, { signal }),
  pauseCampaign: (campaignId) => http.post(`/event-campaigns/${campaignId}/pause`),
  resumeCampaign: (campaignId) => http.post(`/event-campaigns/${campaignId}/resume`),
  stopCampaign: (campaignId) => http.post(`/event-campaigns/${campaignId}/stop`),
};

export default eventCampaignsApi;
