import { http } from './client';
import { buildFormData } from './utils/uploadHelper';

/**
 * Business Pages & Categories Management API Service
 * Interacts with BusinessPageManagementController and BusinessPageCategoryManagementController.
 */
export const businessPagesApi = {
  // Business Pages
  getPages: (params = {}, signal) => http.get('/business-pages', params, { signal }),
  getPage: (pageId, signal) => http.get(`/business-pages/${pageId}`, {}, { signal }),
  getPageEditData: (pageId) => http.get(`/business-pages/${pageId}/edit`),
  updatePage: (pageId, data, files = {}) => {
    const formData = buildFormData({ ...data, _method: 'PUT' }, files);
    return http.upload(`/business-pages/${pageId}`, formData);
  },
  updateStatus: (pageId, status) => http.post(`/business-pages/${pageId}/status`, { status }),
  handleVerification: (pageId, data) => http.post(`/business-pages/${pageId}/verification`, data),
  deletePage: (pageId) => http.delete(`/business-pages/${pageId}`),
  bulkAction: (action, ids) => http.post('/business-pages/bulk-action', { action, ids }),
  exportCsv: (params = {}) => http.download('/business-pages/export', params),

  // Categories
  getCategories: (params = {}, signal) => http.get('/business-pages/categories', params, { signal }),
  createCategory: (data) => http.post('/business-pages/categories', data),
  updateCategory: (categoryId, data) => http.put(`/business-pages/categories/${categoryId}`, data),
  toggleCategoryStatus: (categoryId) =>
    http.post(`/business-pages/categories/${categoryId}/toggle-status`),
  reassignCategoryPages: (categoryId, targetCategoryId) =>
    http.post(`/business-pages/categories/${categoryId}/reassign`, {
      target_category_id: targetCategoryId,
    }),
  deleteCategory: (categoryId) => http.delete(`/business-pages/categories/${categoryId}`),
};

export default businessPagesApi;
