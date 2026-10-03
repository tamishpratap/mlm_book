import apiClient from './apiClient';

/**
 * Public Platform API for Landing, Ecosystem, Rewards, and Dynamic Contact Us Pages.
 */
export const publicApi = {
  /**
   * Fetch public platform settings including dynamic contact details & branding.
   * Managed via Admin -> Platform Settings -> Contact & SEO/Social tabs.
   */
  getContactInfo: async () => {
    try {
      const response = await apiClient.get('/contact-info');
      return response.data;
    } catch (error) {
      try {
        const fallbackRes = await apiClient.get('/contact-info', { baseURL: '/api' });
        return fallbackRes.data;
      } catch {
        // Fallback default values if backend server is unreachable
        return {
          success: true,
          branding: {
            site_name: 'MLM Book',
            site_description: 'Enterprise MLM Book Social & Commerce Platform.',
          },
          contact: {
            company_name: 'MLM Book AI',
            support_email: 'support@mlmbookai.com',
            phone: '+44 7472962940',
            website: 'https://mlmbookai.com',
            address: '',
            social_facebook: 'https://www.facebook.com/profile.php?id=61593794263511',
            social_instagram: 'https://www.instagram.com/mlmbookai/',
            social_linkedin: 'https://linkedin.com/company/mlmbook',
            social_youtube: 'https://www.youtube.com/@MLMBookAIOfficail',
            social_telegram: 'https://t.me/mlmbook',
            business_hours: 'Monday - Friday: 9:00 AM - 6:00 PM (UTC)',
          },
        };
      }
    }
  },

  /**
   * Submit an inquiry through the Contact Us page form.
   */
  submitContactForm: async (formData) => {
    try {
      const response = await apiClient.post('/contact', formData);
      return response.data;
    } catch (error) {
      try {
        const fallbackRes = await apiClient.post('/contact', formData, { baseURL: '/api' });
        return fallbackRes.data;
      } catch (err) {
        throw err;
      }
    }
  },

  /**
   * Fetch active reward rank rules from database table reward_rank_rules.
   */
  getRewardRankRules: async () => {
    try {
      const response = await apiClient.get('/reward-rank-rules');
      return response.data;
    } catch (error) {
      try {
        const fallbackRes = await apiClient.get('/reward-rank-rules', { baseURL: '/api' });
        return fallbackRes.data;
      } catch (err) {
        return null;
      }
    }
  },
};

export default publicApi;
