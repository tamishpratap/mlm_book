import { http } from './client';

/**
 * Advertising Reward Rules API Service
 * Interacts with Admin\AdRewardRuleController.
 */
export const adRewardRulesApi = {
  getRules: (params = {}, signal) => http.get('/ad-reward-rules', params, { signal }),
  createRule: (payload) => http.post('/ad-reward-rules', payload),
  updateRule: (id, payload) => http.put(`/ad-reward-rules/${id}`, payload),
  toggleStatus: (id) => http.post(`/ad-reward-rules/${id}/toggle-status`),
  previewResolution: (referralCount) => http.post('/ad-reward-rules/preview', { referral_count: referralCount }),
};

export default adRewardRulesApi;
