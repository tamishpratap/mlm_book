import { http } from './client';
import { buildFormData } from './utils/uploadHelper';

/**
 * Posts & Moderation API Service
 * Interacts with PostManagementController.
 */
export const postsApi = {
  /**
   * Fetch posts list with search and filters.
   * Route: GET /admin/posts
   */
  getPosts: (params = {}, signal) => http.get('/posts', params, { signal }),

  /**
   * Fetch detailed post inspection data (media, comments, reports).
   * Route: GET /admin/posts/{post}
   */
  getPost: (postId, signal) => http.get(`/posts/${postId}`, {}, { signal }),

  /**
   * Update post content and attachments.
   * Route: PUT /admin/posts/{post}
   */
  updatePost: (postId, data, files = {}) => {
    const formData = buildFormData({ ...data, _method: 'PUT' }, files);
    return http.upload(`/posts/${postId}`, formData);
  },

  /**
   * Remove single attached media from post without deleting the post.
   * Route: DELETE /admin/posts/{post}/media
   */
  removeMedia: (postId) => http.delete(`/posts/${postId}/media`),

  /**
   * Toggle post hide / feed block.
   * Route: POST /admin/posts/{post}/toggle-hide
   */
  toggleHide: (postId) => http.post(`/posts/${postId}/toggle-hide`),

  /**
   * Update post report status.
   * Route: POST /admin/posts/reports/{report}/status
   */
  updateReportStatus: (reportId, status, notes = '') =>
    http.post(`/posts/reports/${reportId}/status`, { status, notes }),

  /**
   * Delete post.
   * Route: DELETE /admin/posts/{post}
   */
  deletePost: (postId) => http.delete(`/posts/${postId}`),

  /**
   * Apply bulk action on selected posts.
   * Route: POST /admin/posts/bulk-action
   */
  bulkAction: (action, ids) => http.post('/posts/bulk-action', { action, ids }),

  /**
   * Export posts CSV.
   * Route: GET /admin/posts/export
   */
  exportCsv: (params = {}) => http.download('/posts/export', params),
};

export default postsApi;
