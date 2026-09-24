import { http } from './client';

/**
 * Admin Withdrawal Requests Management API Service
 */
export const withdrawalsApi = {
  /**
   * List all member withdrawal requests with filters
   * @param {Object} [params] - { status, type, search, date_from, date_to, page, per_page, sort_by, sort_dir }
   */
  getWithdrawals: (params = {}) => http.get('/funds/withdrawals', params),

  /**
   * Get single withdrawal request details
   * @param {number|string} id - Withdrawal ID or Request ID
   */
  getWithdrawal: (id) => http.get(`/funds/withdrawals/${id}`),

  /**
   * Get summary financial and count metrics
   */
  getMetrics: () => http.get('/funds/withdrawals/metrics'),

  /**
   * Action 1: Accept / Approve a pending withdrawal request
   * @param {number|string} id - Withdrawal ID
   * @param {Object} [data] - { txnid, admin_notes }
   */
  acceptWithdrawal: (id, data = {}) => http.post(`/funds/withdrawals/${id}/accept`, data),

  /**
   * Action 2: Verify a withdrawal request (Address & transaction verification)
   * @param {number|string} id - Withdrawal ID
   * @param {Object} [data] - { txnid, admin_notes }
   */
  verifyWithdrawal: (id, data = {}) => http.post(`/funds/withdrawals/${id}/verify`, data),

  /**
   * Action 3: Reject a pending withdrawal request (and auto-refund gross amount to wallet)
   * @param {number|string} id - Withdrawal ID
   * @param {Object} [data] - { rejection_reason, admin_notes }
   */
  rejectWithdrawal: (id, data = {}) => http.post(`/funds/withdrawals/${id}/reject`, data),
};

export default withdrawalsApi;
