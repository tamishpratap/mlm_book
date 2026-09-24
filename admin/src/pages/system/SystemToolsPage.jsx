import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import {
  Cpu,
  Database,
  Server,
  FileText,
  RotateCw,
  Trash2,
  HardDrive,
  CheckCircle2,
  AlertTriangle,
  FileCode,
} from 'lucide-react';
import { Button } from 'primereact/button';
import { PageHeader } from '../../components/common/PageHeader';
import { ErrorState } from '../../components/common/ErrorState';
import { LoadingSpinner } from '../../components/common/LoadingSpinner';
import { useToast } from '../../hooks/useToast';
import { confirmHelper } from '../../utils/confirmHelper';
import { systemApi } from '../../api';

export function SystemToolsPage() {
  const { showSuccess, showError } = useToast();

  const [healthData, setHealthData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [error, setError] = useState(null);

  const fetchHealth = () => {
    setLoading(true);
    setError(null);
    systemApi
      .getSystemHealth()
      .then((res) => {
        setHealthData(res);
      })
      .catch((err) => {
        setError(err.message || 'Failed to load system diagnostics.');
      })
      .finally(() => {
        setLoading(false);
      });
  };

  useEffect(() => {
    let isMounted = true;
    systemApi
      .getSystemHealth()
      .then((res) => {
        if (!isMounted) return;
        setHealthData(res);
      })
      .catch((err) => {
        if (!isMounted) return;
        setError(err.message || 'Failed to load system diagnostics.');
      })
      .finally(() => {
        if (isMounted) setLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, []);

  const handleRetryJob = async (jobId) => {
    setActionLoading(true);
    try {
      await systemApi.retryFailedJob(jobId);
      showSuccess(`Retried failed queue job #${jobId}.`);
      fetchHealth();
    } catch (err) {
      showError(err.message || 'Failed to retry job.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleDeleteJob = (jobId) => {
    confirmHelper.confirm({
      header: 'Delete Failed Job',
      message: `Are you sure you want to delete failed job record #${jobId}?`,
      icon: 'pi pi-trash text-red-500',
      acceptClassName: 'p-button-danger text-xs',
      onAccept: async () => {
        setActionLoading(true);
        try {
          await systemApi.deleteFailedJob(jobId);
          showSuccess(`Deleted failed job #${jobId}.`);
          fetchHealth();
        } catch (err) {
          showError(err.message || 'Failed to delete job.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  const env = healthData?.envInfo || {
    laravel_version: '12.x',
    php_version: '8.3.x',
    db_driver: 'mysql',
    app_env: 'local',
    app_debug: 'True',
    cache_driver: 'file',
    session_driver: 'file',
    queue_driver: 'database',
    timezone: 'UTC',
    maintenance_status: 'Live',
    storage_symlink: 'Linked',
    log_file_size: healthData?.logSizeFormatted || '0 B',
  };

  const failedJobs = healthData?.failedJobs?.data || [];

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <PageHeader
        title="System Health & Maintenance"
        subtitle="Server environment inspection, queue backlog management, and Artisan utilities."
        breadcrumbs={[{ label: 'System Tools' }]}
        actions={
          <div className="flex flex-wrap items-center gap-2.5 sm:gap-3">
            <Link to="/admin/system/logs" className="inline-flex">
              <Button
                label="View Laravel Logs"
                icon={<FileText className="w-3.5 h-3.5 mr-1.5" />}
                className="p-button-outlined p-button-primary text-xs"
              />
            </Link>
            <Button
              icon="pi pi-refresh"
              onClick={fetchHealth}
              className="p-button-outlined p-button-secondary text-xs"
              tooltip="Refresh status"
            />
          </div>
        }
      />

      {loading ? (
        <div className="p-12 flex justify-center bg-white rounded-xl border border-slate-200">
          <LoadingSpinner message="Checking system diagnostics..." />
        </div>
      ) : error ? (
        <div className="p-8 bg-white rounded-xl border border-slate-200">
          <ErrorState title="System Diagnostic Check Failed" message={error} onRetry={fetchHealth} />
        </div>
      ) : (
        <>
          {/* Health Status Cards */}
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
              <div className="flex items-center justify-between">
                <span className="text-xs font-semibold text-slate-500">Database Connectivity</span>
                <Database className="w-4 h-4 text-blue-600" />
              </div>
              <div className="flex items-center space-x-2 mt-2">
                <CheckCircle2 className="w-5 h-5 text-emerald-500" />
                <span className="text-sm font-bold text-slate-800">Connected ({env.db_driver})</span>
              </div>
            </div>

            <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
              <div className="flex items-center justify-between">
                <span className="text-xs font-semibold text-slate-500">Active Queue Status</span>
                <Server className="w-4 h-4 text-purple-600" />
              </div>
              <div className="flex items-center justify-between mt-2">
                <div>
                  <span className="text-xl font-bold text-slate-800">{healthData?.pendingJobsCount ?? 0}</span>
                  <span className="text-xs text-slate-500 ml-1">Pending</span>
                </div>
                <div>
                  <span className={`text-xl font-bold ${(healthData?.failedJobsCount ?? 0) > 0 ? 'text-red-500' : 'text-slate-800'}`}>
                    {healthData?.failedJobsCount ?? 0}
                  </span>
                  <span className="text-xs text-slate-500 ml-1">Failed</span>
                </div>
              </div>
            </div>

            <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
              <div className="flex items-center justify-between">
                <span className="text-xs font-semibold text-slate-500">Log File Storage</span>
                <HardDrive className="w-4 h-4 text-indigo-600" />
              </div>
              <div className="flex items-center space-x-2 mt-2">
                <FileCode className="w-5 h-5 text-indigo-500" />
                <span className="text-sm font-bold text-slate-800">{healthData?.logSizeFormatted || 'Active'}</span>
              </div>
            </div>
          </div>

          {/* Environment Specs Grid */}
          <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-2xs">
            <h3 className="text-sm font-bold text-slate-800 mb-4 flex items-center">
              <Cpu className="w-4 h-4 mr-2 text-blue-600" />
              Environment & Runtime Specifications
            </h3>
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 text-xs">
              <div className="p-3 bg-slate-50 rounded-lg border border-slate-100">
                <span className="text-[10px] uppercase font-bold text-slate-400 block mb-1">Laravel Engine</span>
                <span className="font-mono font-semibold text-slate-800">{env.laravel_version}</span>
              </div>
              <div className="p-3 bg-slate-50 rounded-lg border border-slate-100">
                <span className="text-[10px] uppercase font-bold text-slate-400 block mb-1">PHP Version</span>
                <span className="font-mono font-semibold text-slate-800">{env.php_version}</span>
              </div>
              <div className="p-3 bg-slate-50 rounded-lg border border-slate-100">
                <span className="text-[10px] uppercase font-bold text-slate-400 block mb-1">Environment Mode</span>
                <span className="font-semibold text-slate-800 capitalize">{env.app_env}</span>
              </div>
              <div className="p-3 bg-slate-50 rounded-lg border border-slate-100">
                <span className="text-[10px] uppercase font-bold text-slate-400 block mb-1">Debug Output</span>
                <span className={`font-semibold ${env.app_debug === 'True' ? 'text-amber-600' : 'text-slate-800'}`}>{env.app_debug}</span>
              </div>
              <div className="p-3 bg-slate-50 rounded-lg border border-slate-100">
                <span className="text-[10px] uppercase font-bold text-slate-400 block mb-1">Cache Driver</span>
                <span className="font-mono font-semibold text-slate-800">{env.cache_driver}</span>
              </div>
              <div className="p-3 bg-slate-50 rounded-lg border border-slate-100">
                <span className="text-[10px] uppercase font-bold text-slate-400 block mb-1">Session Store</span>
                <span className="font-mono font-semibold text-slate-800">{env.session_driver}</span>
              </div>
              <div className="p-3 bg-slate-50 rounded-lg border border-slate-100">
                <span className="text-[10px] uppercase font-bold text-slate-400 block mb-1">Queue Connection</span>
                <span className="font-mono font-semibold text-slate-800">{env.queue_driver}</span>
              </div>
              <div className="p-3 bg-slate-50 rounded-lg border border-slate-100">
                <span className="text-[10px] uppercase font-bold text-slate-400 block mb-1">Timezone</span>
                <span className="font-mono font-semibold text-slate-800">{env.timezone}</span>
              </div>
            </div>
          </div>

          {/* Failed Jobs Table */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
            <div className="p-4 border-b border-slate-200 flex items-center justify-between">
              <h3 className="text-sm font-bold text-slate-800 flex items-center">
                <AlertTriangle className="w-4 h-4 mr-2 text-amber-500" />
                Failed Queue Jobs Backlog ({healthData?.failedJobsCount ?? 0})
              </h3>
            </div>

            {failedJobs.length === 0 ? (
              <div className="p-8 text-center text-xs text-slate-500">
                <CheckCircle2 className="w-8 h-8 text-emerald-500 mx-auto mb-2" />
                All background queue jobs processed cleanly. Zero failed jobs.
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs text-slate-700">
                  <thead className="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase border-b border-slate-200">
                    <tr>
                      <th className="p-3.5">ID</th>
                      <th className="p-3.5">Queue / Connection</th>
                      <th className="p-3.5">Exception Summary</th>
                      <th className="p-3.5">Failed At</th>
                      <th className="p-3.5 text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {failedJobs.map((job) => (
                      <tr key={job.id} className="hover:bg-slate-50/80 transition-colors">
                        <td className="p-3.5 font-mono font-bold text-slate-800">#{job.id}</td>
                        <td className="p-3.5">
                          <span className="font-semibold text-slate-900">{job.queue}</span>
                          <span className="text-[10px] text-slate-400 block font-mono">{job.connection}</span>
                        </td>
                        <td className="p-3.5 max-w-md font-mono text-[11px] text-red-600 truncate">
                          {job.exception ? job.exception.split('\n')[0] : 'Unknown error'}
                        </td>
                        <td className="p-3.5 text-[11px] text-slate-500 font-mono">
                          {job.failed_at ? new Date(job.failed_at).toLocaleString() : 'N/A'}
                        </td>
                        <td className="p-3.5 text-right whitespace-nowrap">
                          <div className="inline-flex items-center justify-end gap-1.5 sm:gap-2">
                            <Button
                              icon={<RotateCw className="w-3.5 h-3.5 text-blue-600" />}
                              onClick={() => handleRetryJob(job.id)}
                              className="p-button-text p-button-secondary p-button-sm p-0 w-7 h-7"
                              tooltip="Retry job"
                              disabled={actionLoading}
                            />
                            <Button
                              icon={<Trash2 className="w-3.5 h-3.5 text-red-500" />}
                              onClick={() => handleDeleteJob(job.id)}
                              className="p-button-text p-button-danger p-button-sm p-0 w-7 h-7"
                              tooltip="Delete job record"
                              disabled={actionLoading}
                            />
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </>
      )}
    </div>
  );
}

export default SystemToolsPage;
