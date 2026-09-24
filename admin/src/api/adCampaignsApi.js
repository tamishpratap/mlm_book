import { http } from './client';

/**
 * Advertising Campaigns Management API Service
 * Interacts with AdCampaignManagementController.
 */
export const adCampaignsApi = {
  getCampaigns: (params = {}, signal) => http.get('/ad-campaigns', params, { signal }),
  getCampaign: (campaignId, signal) => http.get(`/ad-campaigns/${campaignId}`, {}, { signal }),
  approveCampaign: (campaignId) => http.post(`/ad-campaigns/${campaignId}/approve`),
  rejectCampaign: (campaignId, reason) =>
    http.post(`/ad-campaigns/${campaignId}/reject`, { rejection_reason: reason }),
  pauseCampaign: (campaignId) => http.post(`/ad-campaigns/${campaignId}/pause`),
  resumeCampaign: (campaignId) => http.post(`/ad-campaigns/${campaignId}/resume`),
  stopCampaign: (campaignId) => http.post(`/ad-campaigns/${campaignId}/stop`),
  restartCampaign: (campaignId) => http.post(`/ad-campaigns/${campaignId}/restart`),
  getAnalytics: (params = {}, signal) => http.get('/ad-campaigns/analytics', params, { signal }),
  getRewards: (params = {}, signal) => http.get('/ad-campaigns/rewards', params, { signal }),
  getCampaignRewards: (campaignId, params = {}, signal) =>
    http.get(`/ad-campaigns/${campaignId}/rewards`, params, { signal }),
  exportCampaigns: (params = {}, signal) => http.get('/ad-campaigns/export', params, { signal }),
  getSettings: (signal) => http.get('/ad-campaigns/settings', {}, { signal }),
  updateSettings: (payload) => http.post('/ad-campaigns/settings', payload),
};

export default adCampaignsApi;
