import apiClient from './apiClient';

export const verificationApi = {
  /**
   * Get current member verification status
   */
  async getStatus() {
    const response = await apiClient.get('/account/verification-status');
    return response.data;
  },

  /**
   * Initiate WhatsApp "Hi" verification flow
   */
  async initiateVerification() {
    const response = await apiClient.post('/account/verification/initiate');
    return response.data;
  },

  /**
   * Submit WhatsApp "Hi" verification request
   */
  async submitVerificationRequest() {
    const response = await apiClient.post('/account/verification/submit-hi');
    return response.data;
  },

  /**
   * Send WhatsApp OTP to the specified mobile number
   * @param {string} mobileNumber e.g. +91 9876543210
   */
  async sendWhatsAppOtp(mobileNumber) {
    const response = await apiClient.post('/account/mobile/send-otp', {
      mobile_number: mobileNumber,
    });
    return response.data;
  },

  /**
   * Verify the 6-digit WhatsApp OTP
   * @param {string} otp 6-digit OTP code
   */
  async verifyWhatsAppOtp(otp) {
    const response = await apiClient.post('/account/mobile/verify-otp', {
      mobile_otp: otp,
    });
    return response.data;
  },
  /**
   * Update phone number for an unverified member
   * @param {string} phone
   */
  async updateUnverifiedPhone(phone) {
    const response = await apiClient.put('/account/unverified-phone', {
      phone,
    });
    return response.data;
  },

  /**
   * Submit a phone change request for an already-verified member
   * @param {string} newPhone
   */
  async requestPhoneChange(newPhone) {
    const response = await apiClient.post('/account/phone-change-request', {
      new_phone: newPhone,
    });
    return response.data;
  },

  /**
   * Confirm sending the WhatsApp message for a phone change request
   * @param {number|string} requestId
   */
  async confirmPhoneChangeWhatsApp(requestId) {
    const response = await apiClient.post(`/account/phone-change-request/${requestId}/confirm-whatsapp`);
    return response.data;
  },

  /**
   * Cancel a pending phone change request
   * @param {number|string} requestId
   */
  async cancelPhoneChangeRequest(requestId) {
    const response = await apiClient.delete(`/account/phone-change-request/${requestId}`);
    return response.data;
  },
};

export default verificationApi;
