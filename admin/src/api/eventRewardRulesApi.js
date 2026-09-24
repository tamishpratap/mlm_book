import { http } from './client';

/**
 * Event Reward Rules API Service
 * Interacts with Admin\EventRewardRuleController.
 */
export const eventRewardRulesApi = {
  getRules: (params = {}, signal) => http.get('/event-reward-rules', params, { signal }),
  createRule: (payload) => http.post('/event-reward-rules', payload),
  updateRule: (id, payload) => http.put(`/event-reward-rules/${id}`, payload),
  toggleStatus: (id) => http.post(`/event-reward-rules/${id}/toggle-status`),
  previewResolution: (referralCount) => http.post('/event-reward-rules/preview', { referral_count: referralCount }),
  validateActiveSet: (signal) => http.get('/event-reward-rules/validate-active-set', {}, { signal }),
};

export default eventRewardRulesApi;
