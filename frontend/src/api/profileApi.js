import apiClient from './apiClient';

/**
 * Member Profile API Service
 */
export const profileApi = {
  /**
   * Get own profile data including stats, timeline, photos, videos, friends, stories, saved
   * @param {Object} [params] - { tab, page }
   */
  async getProfile(params = {}) {
    const response = await apiClient.get('/profile', { params });
    return response.data;
  },

  /**
   * Get profile edit form data
   */
  async getEditProfile() {
    const response = await apiClient.get('/profile/edit');
    return response.data;
  },

  /**
   * Update profile details
   * @param {Object} data - { name, bio, date_of_birth, gender, phone, city, country, website }
   */
  async updateProfile(data) {
    const response = await apiClient.put('/profile', data);
    return response.data;
  },

  /**
   * Upload / replace profile photo
   * @param {FormData} formData - with 'profile_photo' file
   */
  async updateProfilePhoto(formData) {
    const response = await apiClient.post('/profile/photo', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },

  /**
   * Remove profile photo
   */
  async removeProfilePhoto() {
    const response = await apiClient.delete('/profile/photo');
    return response.data;
  },

  /**
   * Upload / replace cover photo
   * @param {FormData} formData - with 'cover_photo' file
   */
  async updateCoverPhoto(formData) {
    const response = await apiClient.post('/profile/cover', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },

  /**
   * Remove cover photo
   */
  async removeCoverPhoto() {
    const response = await apiClient.delete('/profile/cover');
    return response.data;
  },

  /**
   * Get list of profile visitors
   * @param {number} [page=1]
   */
  async getVisitors(page = 1) {
    const response = await apiClient.get('/profile/visitors', { params: { page } });
    return response.data;
  },
};

export default profileApi;
