import apiClient from './apiClient';

/**
 * Member Withdrawal API Service
 */
export const withdrawalApi = {
  /**
   * Fetch member withdrawal configuration, statistics, and history.
   */
  async getWithdrawals() {
    const response = await apiClient.get('/withdrawals');
    return response.data;
  },

  /**
   * Submit a new withdrawal request.
   * @param {Object} data - { gross_amount, wallet_address, remarks }
   */
  async submitWithdrawal(data) {
    const response = await apiClient.post('/withdrawals', data);
    return response.data;
  },

  /**
   * Submit full Fund Wallet (p2p_wallet) withdrawal request with zero service fee
   */
  async submitFundWalletWithdrawal() {
    const response = await apiClient.post('/withdrawals/fund-wallet');
    return response.data;
  },
};

export default withdrawalApi;
