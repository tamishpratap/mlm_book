import { http } from './client';

/**
 * Role-Based Access Control (RBAC) API Service
 * Interacts with RoleManagementController.
 */
export const rolesApi = {
  getRoles: (params = {}, signal) => http.get('/roles', params, { signal }),
  getMatrix: (signal) => http.get('/roles/matrix', {}, { signal }),
  updateMatrix: (matrixData) => http.post('/roles/matrix', { matrix: matrixData }),
  getCreateData: (signal) => http.get('/roles/create', {}, { signal }),
  createRole: (data) => http.post('/roles', data),
  getRoleEditData: (roleId, signal) => http.get(`/roles/${roleId}/edit`, {}, { signal }),
  updateRole: (roleId, data) => http.put(`/roles/${roleId}`, data),
  deleteRole: (roleId) => http.delete(`/roles/${roleId}`),
  assignUserRole: (userId, roleId) => http.post('/roles/assign-user', { user_id: userId, role_id: roleId }),
};

export default rolesApi;
