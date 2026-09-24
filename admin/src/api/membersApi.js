import { http } from './client';
import { buildFormData } from './utils/uploadHelper';

/**
 * Members Management API Service
 * Interacts with MemberManagementController.
 */
export const membersApi = {
  /**
   * Fetch active members list with filters.
   * Route: GET /admin/members or /admin/members/active
   */
  getActiveMembers: (params = {}, signal) => http.get('/members/active', params, { signal }),

  /**
   * Fetch pending verification member requests.
   * Route: GET /admin/members/pending
   */
  getPendingMembers: (params = {}, signal) => http.get('/members/pending', params, { signal }),

  /**
   * Fetch blocked members list.
   * Route: GET /admin/members/blocked
   */
  getBlockedMembers: (params = {}, signal) => http.get('/members/blocked', params, { signal }),

  /**
   * Fetch detailed member profile & related collections.
   * Route: GET /admin/members/{member}
   */
  getMember: (memberId, signal) => http.get(`/members/${memberId}`, {}, { signal }),

  /**
   * Fetch member edit form data.
   * Route: GET /admin/members/{member}/edit
   */
  getMemberEditData: (memberId) => http.get(`/members/${memberId}/edit`),

  /**
   * Update member profile details (supports profile/cover photo uploads).
   * Route: PUT /admin/members/{member} (sent as POST with _method: PUT for multipart support)
   */
  updateMember: (memberId, data, files = {}) => {
    const formData = buildFormData({ ...data, _method: 'PUT' }, files);
    return http.upload(`/members/${memberId}`, formData);
  },

  /**
   * Update member status.
   * Route: POST /admin/members/{member}/status
   */
  updateStatus: (memberId, action) => http.post(`/members/${memberId}/status`, { action }),

  /**
   * Block member.
   * Route: POST /admin/members/{member}/block
   */
  blockMember: (memberId) => http.post(`/members/${memberId}/block`),

  /**
   * Unblock member.
   * Route: POST /admin/members/{member}/unblock
   */
  unblockMember: (memberId) => http.post(`/members/${memberId}/unblock`),

  /**
   * Approve pending member request.
   * Route: POST /admin/members/{member}/approve
   */
  approveMember: (memberId) => http.post(`/members/${memberId}/approve`),

  /**
   * Reject pending member request.
   * Route: POST /admin/members/{member}/reject
   */
  rejectMember: (memberId) => http.post(`/members/${memberId}/reject`),

  /**
   * Remove member from community.
   * Route: POST /admin/members/{member}/communities/{community}/remove
   */
  removeCommunity: (memberId, communityId) =>
    http.post(`/members/${memberId}/communities/${communityId}/remove`),

  /**
   * Delete member.
   * Route: DELETE /admin/members/{member}
   */
  deleteMember: (memberId) => http.delete(`/members/${memberId}`),

  /**
   * Apply bulk action on selected members.
   * Route: POST /admin/members/bulk-action
   */
  bulkAction: (action, ids) => http.post('/members/bulk-action', { action, ids }),

  /**
   * Download CSV export.
   * Route: GET /admin/members/export/{format?}
   */
  exportCsv: (paramsOrType = 'active', maybeParams = {}) => {
    const query =
      typeof paramsOrType === 'string'
        ? { type: paramsOrType, ...maybeParams }
        : { type: 'active', ...paramsOrType };
    return http.download(`/members/export/csv`, query);
  },
};

export default membersApi;
