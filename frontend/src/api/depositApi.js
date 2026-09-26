import apiClient from './apiClient';

/**
 * Member Deposit API Service for DApp & Manual Deposit Verification System
 */
export const depositApi = {
  /**
   * Get deposit configuration (network, chain_id, usdt_contract, destination wallet, minimum)
   */
  async getConfig() {
    const response = await apiClient.get('/deposit/config');
    return response.data;
  },

  /**
   * Perform independent on-chain blockchain verification for Manual Deposit Request
   * @param {Object} data - { amount, transaction_hash, wallet_address }
   */
  async verifyManualDeposit(data) {
    const response = await apiClient.post('/deposit/verify', data);
    return response.data;
  },

  /**
   * Submit verified manual deposit request to Admin Panel
   * @param {Object} data - { amount, transaction_hash, wallet_address }
   */
  async submitManualDepositRequest(data) {
    const response = await apiClient.post('/deposit/manual-request', data);
    return response.data;
  },

  /**
   * Submit completed DApp blockchain transaction details
   * @param {Object} data - { amount, transaction_hash, wallet_address }
   */
  async submitDappDeposit(data) {
    const response = await apiClient.post('/deposit/dapp', data);
    return response.data;
  },

  /**
   * Get authenticated member's deposit history
   * @param {Object} [params] - { page, per_page }
   */
  async getHistory(params = {}) {
    const response = await apiClient.get('/deposit/history', { params });
    return response.data;
  },

  /**
   * Request full withdrawal of Fund Wallet (p2p_wallet) balance to member payout wallet address with 0% fee
   */
  async withdrawFundWallet() {
    const response = await apiClient.post('/deposit/withdraw-fund');
    return response.data;
  },
};

export default depositApi;
