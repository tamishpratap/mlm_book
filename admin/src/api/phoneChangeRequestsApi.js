import { http } from './client';

/**
 * Phone Number Change Requests API Service
 * Interacts with Admin PhoneNumberChangeRequestController
 */
export const phoneChangeRequestsApi = {
  /**
   * Fetch listing of phone number change requests.
   * Route: GET /admin/phone-change-requests
   */
  getRequests: (params = {}, signal) => http.get('/phone-change-requests', params, { signal }),

  /**
   * Fetch single request details.
   * Route: GET /admin/phone-change-requests/{id}
   */
  getRequest: (id, signal) => http.get(`/phone-change-requests/${id}`, {}, { signal }),

  /**
   * Atomically approve a change request.
   * Route: POST /admin/phone-change-requests/{id}/approve
   */
  approveRequest: (id) => http.post(`/phone-change-requests/${id}/approve`),

  /**
   * Atomically reject a change request.
   * Route: POST /admin/phone-change-requests/{id}/reject
   */
  rejectRequest: (id, reason) => http.post(`/phone-change-requests/${id}/reject`, { reason }),
};

export default phoneChangeRequestsApi;
