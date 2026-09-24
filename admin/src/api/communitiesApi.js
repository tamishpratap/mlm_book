import { http } from './client';
import { buildFormData } from './utils/uploadHelper';

/**
 * Communities Management API Service
 * Interacts with CommunityManagementController.
 */
export const communitiesApi = {
  getCommunities: (params = {}, signal) => http.get('/communities', params, { signal }),
  getCommunity: (communityId, signal) => http.get(`/communities/${communityId}`, {}, { signal }),
  getCommunityEditData: (communityId) => http.get(`/communities/${communityId}/edit`),
  updateCommunity: (communityId, data, files = {}) => {
    const formData = buildFormData({ ...data, _method: 'PUT' }, files);
    return http.upload(`/communities/${communityId}`, formData);
  },
  updateStatus: (communityId, status) => http.post(`/communities/${communityId}/status`, { status }),
  handleJoinRequest: (communityId, membershipId, action) =>
    http.post(`/communities/${communityId}/join-requests/${membershipId}`, { action }),
  resolveReport: (reportId, status, notes = '') =>
    http.post(`/communities/reports/${reportId}/resolve`, { status, notes }),
  deleteCommunity: (communityId) => http.delete(`/communities/${communityId}`),
  bulkAction: (action, ids) => http.post('/communities/bulk-action', { action, ids }),
  exportCsv: (params = {}) => http.download('/communities/export', params),
};

export default communitiesApi;
