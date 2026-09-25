import { http } from './client';

/**
 * Centralized Reward Management API Service
 * Interacts with Admin\RewardManagementController.
 */
export const rewardManagementApi = {
  // Rules
  getRules: (params = {}, signal) => http.get('/reward-rules', params, { signal }),
  createRule: (payload) => http.post('/reward-rules', payload),
  updateRule: (id, payload) => http.put(`/reward-rules/${id}`, payload),
  toggleStatus: (id) => http.post(`/reward-rules/${id}/toggle-status`),
  previewResolution: (referralCount, teamCount = 0) => http.post('/reward-rules/preview', { referral_count: referralCount, team_count: teamCount }),
  validateActiveSet: (signal) => http.get('/reward-rules/validate-active-set', {}, { signal }),

  // History
  getEventRewardHistory: (params = {}, signal) => http.get('/reward-history/events', params, { signal }),
  getAdRewardHistory: (params = {}, signal) => http.get('/reward-history/ads', params, { signal }),
  getHistoryMetrics: (signal) => http.get('/reward-history/metrics', {}, { signal }),
};

export default rewardManagementApi;
