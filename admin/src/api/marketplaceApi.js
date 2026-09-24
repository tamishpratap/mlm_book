import { http } from './client';

/**
 * Marketplace Management API Service
 * Interacts with MarketplaceManagementController.
 */
export const marketplaceApi = {
  getProducts: (params = {}, signal) => http.get('/marketplace', params, { signal }),
  getProduct: (productId, signal) => http.get(`/marketplace/${productId}`, {}, { signal }),
  updateStatus: (productId, status) => http.post(`/marketplace/${productId}/status`, { status }),
  toggleFeatured: (productId) => http.post(`/marketplace/${productId}/toggle-featured`),
  deleteProduct: (productId) => http.delete(`/marketplace/${productId}`),
  bulkAction: (action, ids) => http.post('/marketplace/bulk-action', { action, ids }),
  exportCsv: (params = {}) => http.download('/marketplace/export', params),
};

export default marketplaceApi;
