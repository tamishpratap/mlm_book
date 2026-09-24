import { http } from './client';

/**
 * Stories Management API Service
 * Interacts with StoryManagementController.
 */
export const storiesApi = {
  /**
   * Fetch stories list with filters.
   * Route: GET /admin/stories
   */
  getStories: (params = {}, signal) => http.get('/stories', params, { signal }),

  /**
   * Fetch all stories list.
   * Route: GET /admin/stories
   */
  getAllStories: (params = {}, signal) => http.get('/stories', params, { signal }),

  /**
   * Fetch live/active stories list.
   * Route: GET /admin/stories?status=active
   */
  getLiveStories: (params = {}, signal) => http.get('/stories', { ...params, status: 'active' }, { signal }),

  /**
   * Fetch expired stories list.
   * Route: GET /admin/stories?status=expired
   */
  getExpiredStories: (params = {}, signal) => http.get('/stories', { ...params, status: 'expired' }, { signal }),

  /**
   * Fetch story detail (viewers, reactions, replies).
   * Route: GET /admin/stories/{story}
   */
  getStory: (storyId, signal) => http.get(`/stories/${storyId}`, {}, { signal }),

  /**
   * Delete story.
   * Route: DELETE /admin/stories/{story}
   */
  deleteStory: (storyId) => http.delete(`/stories/${storyId}`),

  /**
   * Apply bulk action on selected stories.
   * Route: POST /admin/stories/bulk-action
   */
  bulkAction: (action, ids) => http.post('/stories/bulk-action', { action, ids }),

  /**
   * Export stories CSV.
   * Route: GET /admin/stories/export
   */
  exportCsv: (params = {}) => http.download('/stories/export', params),
};

export default storiesApi;
