import apiClient from './apiClient';

/**
 * Member Authentication & Onboarding API
 */
export const authApi = {
  /**
   * Log in with email and password
   */
  async login(credentials) {
    const response = await apiClient.post('/login', credentials);
    return response.data;
  },

  /**
   * Register initial member account
   */
  async register(data) {
    const response = await apiClient.post('/register', data);
    return response.data;
  },

  /**
   * Check unique User ID availability
   */
  async checkUserId(userId) {
    const response = await apiClient.get('/register/check-user-id', {
      params: { user_id: userId },
    });
    return response.data;
  },

  /**
   * Check WhatsApp/mobile number availability
   */
  async checkPhone(phone, countryCode = '+91') {
    const response = await apiClient.get('/register/check-phone', {
      params: { phone, country_code: countryCode },
    });
    return response.data;
  },

  /**
   * Check Introducer ID and resolve referrer details
   */
  async checkIntroducer(introducerId) {
    const response = await apiClient.get('/register/check-introducer', {
      params: { introducer_id: introducerId },
    });
    return response.data;
  },

  /**
   * Fetch active pending registration session status
   */
  async getVerifyStatus() {
    const response = await apiClient.get('/register/verify');
    return response.data;
  },

  /**
   * Verify email registration 6-digit OTP
   */
  async verifyEmailOtp(data) {
    const response = await apiClient.post('/register/verify', data);
    return response.data;
  },

  /**
   * Resend email registration OTP
   */
  async resendRegistrationOtp() {
    const response = await apiClient.post('/register/resend-otp');
    return response.data;
  },

  /**
   * Cancel in-progress registration
   */
  async cancelRegistration() {
    const response = await apiClient.post('/register/cancel');
    return response.data;
  },

  /**
   * Send password recovery email link
   */
  async forgotPassword(data) {
    const response = await apiClient.post('/forgot-password', data);
    return response.data;
  },

  /**
   * Reset password with token
   */
  async resetPassword(data) {
    const response = await apiClient.post('/reset-password', data);
    return response.data;
  },

  /**
   * Fetch current authenticated member profile & counters
   */
  async getMe() {
    const response = await apiClient.get('/me');
    return response.data;
  },

  /**
   * Log out current member session
   */
  async logout() {
    const response = await apiClient.post('/logout');
    return response.data;
  },

  /**
   * Get Google OAuth redirect endpoint URL
   */
  getGoogleAuthUrl(ref = null, mode = 'login') {
    const backendUrl = import.meta.env.VITE_BACKEND_URL || '';
    let url = backendUrl
      ? `${backendUrl}/api/member/auth/google/redirect`
      : (import.meta.env.VITE_API_BASE_URL
        ? `${import.meta.env.VITE_API_BASE_URL}/auth/google/redirect`
        : 'https://mlmbookai.com/backend/public/api/member/auth/google/redirect');

    const params = new URLSearchParams();
    if (mode) {
      params.set('mode', mode);
    }
    if (ref && typeof ref === 'string' && ref.trim()) {
      params.set('ref', ref.trim());
    }

    const queryString = params.toString();
    if (queryString) {
      url += (url.includes('?') ? '&' : '?') + queryString;
    }

    return url;
  },

  /**
   * Fetch pending Google signup metadata
   */
  async getPendingGoogleSignup(token) {
    const response = await apiClient.get('/auth/google/pending', {
      params: { token },
    });
    return response.data;
  },

  /**
   * Complete Google registration with Introducer choice
   */
  async completeGoogleSignup(payload) {
    const response = await apiClient.post('/auth/google/complete', payload);
    return response.data;
  },
};

export default authApi;
