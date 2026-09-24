import apiClient from './apiClient';

/**
 * Member Account, Security & Verification API Service
 */
export const accountApi = {
  /**
   * Get account settings data and active OTP verification status
   */
  async getSettings() {
    const response = await apiClient.get('/account/settings');
    return response.data;
  },

  /**
   * Update account settings
   * @param {Object} data - { name }
   */
  async updateSettings(data) {
    const response = await apiClient.put('/account/settings', data);
    return response.data;
  },

  /**
   * Get security info
   */
  async getSecurity() {
    const response = await apiClient.get('/account/security');
    return response.data;
  },

  /**
   * Update member password
   * @param {Object} data - { current_password, password, password_confirmation }
   */
  async updatePassword(data) {
    const response = await apiClient.put('/account/password', data);
    return response.data;
  },

  /**
   * Send mobile verification OTP code
   * @param {Object} data - { mobile_number } (E.164 format: e.g., +919876543210)
   */
  async sendMobileOtp(data) {
    const response = await apiClient.post('/account/mobile/send-otp', data);
    return response.data;
  },

  /**
   * Verify mobile OTP code
   * @param {Object} data - { mobile_otp } (6 digits)
   */
  async verifyMobileOtp(data) {
    const response = await apiClient.post('/account/mobile/verify-otp', data);
    return response.data;
  },

  /**
   * Send email verification OTP code
   * @param {Object} data - { new_email }
   */
  async sendEmailOtp(data) {
    const response = await apiClient.post('/account/email/send-otp', data);
    return response.data;
  },

  /**
   * Verify email OTP code
   * @param {Object} data - { email_otp } (6 digits)
   */
  async verifyEmailOtp(data) {
    const response = await apiClient.post('/account/email/verify-otp', data);
    return response.data;
  },

  /**
   * Check / preview introducer validity
   * @param {string} introducerId
   */
  async checkIntroducer(introducerId) {
    const response = await apiClient.get('/account/check-introducer', {
      params: { introducer_id: introducerId },
    });
    return response.data;
  },

  /**
   * Claim / add missed introducer ID
   * @param {Object} data - { introducer_id }
   */
  async claimIntroducer(data) {
    const response = await apiClient.post('/account/introducer', data);
    return response.data;
  },

  /**
   * Get member reward wallet summary and balance
   */
  async getRewardWallet() {
    const response = await apiClient.get('/rewards/wallet');
    return response.data;
  },

  /**
   * Get member reward history ledger
   */
  async getRewardHistory(params = {}) {
    const response = await apiClient.get('/rewards/history', { params });
    return response.data;
  },

  /**
   * Request WhatsApp OTP to set or change USDT (BEP-20) reward wallet address
   * @param {Object} data - { wallet_address }
   */
  async sendRewardWalletOtp(data) {
    const response = await apiClient.post('/rewards/wallet/send-otp', data);
    return response.data;
  },

  /**
   * Verify WhatsApp OTP and activate USDT (BEP-20) reward wallet address
   * @param {Object} data - { otp, wallet_address }
   */
  async verifyRewardWalletOtp(data) {
    const response = await apiClient.post('/rewards/wallet/verify-otp', data);
    return response.data;
  },
};

export default accountApi;
