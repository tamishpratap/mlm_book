import { http } from './client';
import { buildFormData } from './utils/uploadHelper';

/**
 * Admin Advertising Funds & Deposit Management API Service
 */
export const fundsApi = {
  /**
   * Get deposit settings (QR image, UPI ID, exchange rate, min INR)
   */
  getSettings: () => http.get('/funds/deposit-settings'),

  /**
   * Update deposit settings and upload QR image
   * @param {Object} settingsData - { deposit_crypto_wallet_address, deposit_instructions }
   * @param {Object} [files] - { deposit_qr_image: File }
   */
  updateSettings: (settingsData, files = {}) => {
    const formData = buildFormData(settingsData, files);
    return http.upload('/funds/deposit-settings', formData);
  },

  /**
   * List all member deposit requests with filters
   * @param {Object} [params] - { status, search, page, per_page }
   */
  getDeposits: (params = {}) => http.get('/funds/deposits', { params }),

  /**
   * Re-verify a deposit on-chain (BNB Smart Chain BEP-20) and atomically credit if confirmed
   * @param {number|string} id - Deposit ID
   */
  reverifyDeposit: (id) => http.post(`/funds/deposits/${id}/reverify`),

  /**
   * Approve a pending deposit request and credit member USD advertising funds
   * @param {number|string} id - Deposit ID
   * @param {Object} [data] - { admin_notes }
   */
  approveDeposit: (id, data = {}) => http.post(`/funds/deposits/${id}/approve`, data),

  /**
   * Reject a pending deposit request
   * @param {number|string} id - Deposit ID
   * @param {Object} [data] - { rejection_reason, admin_notes }
   */
  rejectDeposit: (id, data = {}) => http.post(`/funds/deposits/${id}/reject`, data),
};

export default fundsApi;
