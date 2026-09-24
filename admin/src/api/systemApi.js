import { http } from './client';

/**
 * System Diagnostics, Logs & Failed Jobs API Service
 * Interacts with SystemToolsController.
 */
export const systemApi = {
  getSystemHealth: () => http.get('/system'),
  getLogs: (params = {}, signal) => http.get('/system/logs', params, { signal }),
  downloadLog: () => http.download('/system/logs/download'),
  clearLog: () => http.post('/system/logs/clear'),
  retryFailedJob: (jobId) => http.post(`/system/failed-jobs/${jobId}/retry`),
  deleteFailedJob: (jobId) => http.delete(`/system/failed-jobs/${jobId}`),
  runArtisan: (command) => http.post('/system/artisan', { command }),
};

export default systemApi;
