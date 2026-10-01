import { http } from './client';
import { buildFormData } from './utils/uploadHelper';

/**
 * Platform Settings & Maintenance API Service
 * Interacts with SettingManagementController.
 */
export const settingsApi = {
  getSettings: () => http.get('/settings'),
  getBranding: () => http.get('/branding'),
  updateSettings: (settingsData, brandingFiles = {}) => {
    const hasFiles = Object.keys(brandingFiles || {}).some(
      (key) => brandingFiles[key] instanceof File || brandingFiles[key] instanceof Blob
    );

    if (hasFiles) {
      const formData = buildFormData(settingsData, brandingFiles);
      return http.upload('/settings', formData);
    }

    return http.post('/settings', settingsData);
  },
  clearCache: (type = 'all') => http.post('/settings/clear-cache', { type }),
  toggleMaintenance: (action, secret = '') =>
    http.post('/settings/maintenance', { action, secret }),
  getContactMessages: () => http.get('/settings/contact-messages'),
  deleteContactMessage: (id) => http.delete(`/settings/contact-messages/${id}`),
  updateContactMessageStatus: (id, status) =>
    http.post(`/settings/contact-messages/${id}/status`, { status }),
};

export default settingsApi;
