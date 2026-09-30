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
            company_name: 'MLM Book Enterprise',
            support_email: 'support@mlmbook.com',
            phone: '+1 (800) 123-4567',
            website: 'https://mlmbook.com',
            address: '123 Enterprise Way, Suite 500, Tech City',
            social_telegram: 'https://t.me/mlmbook',
            social_twitter: 'https://twitter.com/mlmbook',
            social_facebook: 'https://www.facebook.com/profile.php?id=61593794263511',
            social_linkedin: 'https://linkedin.com/company/mlmbook',
            social_youtube: 'https://youtube.com/mlmbook',
            social_instagram: 'https://www.instagram.com/mlmbook29/',
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
};

export default publicApi;
