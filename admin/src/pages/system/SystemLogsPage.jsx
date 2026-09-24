import { useState, useEffect } from 'react';
import {
  Search,
  Download,
  Trash2,
  AlertTriangle,
  Info,
  AlertCircle,
  Bug,
  Terminal,
} from 'lucide-react';
import { Button } from 'primereact/button';
import { InputText } from 'primereact/inputtext';
import { Dropdown } from 'primereact/dropdown';
import { AdminSearchInput } from '../../components/common/AdminSearchInput';
import { PageHeader } from '../../components/common/PageHeader';
import { ErrorState } from '../../components/common/ErrorState';
import { LoadingSpinner } from '../../components/common/LoadingSpinner';
import { useToast } from '../../hooks/useToast';
import { confirmHelper } from '../../utils/confirmHelper';
import { systemApi, downloadBlobFromResponse } from '../../api';

export function SystemLogsPage() {
  const { showSuccess, showError } = useToast();

  const [logs, setLogs] = useState([]);
  const [search, setSearch] = useState('');
  const [level, setLevel] = useState('');
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [downloading, setDownloading] = useState(false);
  const [error, setError] = useState(null);

  const fetchLogs = () => {
    setLoading(true);
    setError(null);
    const params = {
      q: search || undefined,
      level: level || undefined,
    };
    systemApi
      .getLogs(params)
      .then((res) => {
        const entries = res?.logEntries || (Array.isArray(res) ? res : []);
        setLogs(entries);
      })
      .catch((err) => {
        setError(err.message || 'Failed to load Laravel logs.');
      })
      .finally(() => {
        setLoading(false);
      });
  };

  useEffect(() => {
    let isMounted = true;
    systemApi
      .getLogs({})
      .then((res) => {
        if (!isMounted) return;
        const entries = res?.logEntries || (Array.isArray(res) ? res : []);
        setLogs(entries);
      })
      .catch((err) => {
        if (!isMounted) return;
        setError(err.message || 'Failed to load Laravel logs.');
      })
      .finally(() => {
        if (isMounted) setLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, []);

  const handleClearLog = () => {
    confirmHelper.confirm({
      header: 'Clear Laravel Log File',
      message: 'Are you sure you want to empty storage/logs/laravel.log? This cannot be undone.',
      icon: 'pi pi-trash text-red-500',
      acceptClassName: 'p-button-danger text-xs',
      onAccept: async () => {
        setActionLoading(true);
        try {
          await systemApi.clearLog();
          showSuccess('Laravel log emptied successfully.');
          fetchLogs();
        } catch (err) {
          showError(err.message || 'Failed to clear logs.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  const handleDownload = async () => {
    if (downloading) return;
    setDownloading(true);
    try {
      const blob = await systemApi.downloadLog();
      await downloadBlobFromResponse(blob, 'laravel.log');
      showSuccess('Log file downloaded.');
    } catch (err) {
      showError(err.message || 'Failed to download log file.');
    } finally {
      setDownloading(false);
    }
  };

  const getLevelBadge = (lvl) => {
    const l = (lvl || '').toUpperCase();
    if (l === 'ERROR' || l === 'CRITICAL' || l === 'EMERGENCY') {
      return (
        <span className="inline-flex items-center text-[10px] font-bold bg-red-100 text-red-800 px-2 py-0.5 rounded-full">
          <AlertCircle className="w-2.5 h-2.5 mr-1" /> {l}
        </span>
      );
    }
    if (l === 'WARNING') {
      return (
        <span className="inline-flex items-center text-[10px] font-bold bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full">
          <AlertTriangle className="w-2.5 h-2.5 mr-1" /> {l}
        </span>
      );
    }
    if (l === 'DEBUG') {
      return (
        <span className="inline-flex items-center text-[10px] font-bold bg-purple-100 text-purple-800 px-2 py-0.5 rounded-full">
          <Bug className="w-2.5 h-2.5 mr-1" /> {l}
        </span>
      );
    }
    return (
      <span className="inline-flex items-center text-[10px] font-bold bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">
        <Info className="w-2.5 h-2.5 mr-1" /> {l || 'INFO'}
      </span>
    );
  };

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <PageHeader
        title="Laravel Server Log Viewer"
        subtitle="Safe tail-reader for storage/logs/laravel.log with level filtering."
        breadcrumbs={[
          { label: 'System Tools', to: '/admin/system' },
          { label: 'Server Logs' },
        ]}
        actions={
          <div className="flex flex-wrap items-center gap-2.5 sm:gap-3">
            <Button
              label={downloading ? 'Downloading...' : 'Download Log'}
              icon={downloading ? 'pi pi-spin pi-spinner' : <Download className="w-3.5 h-3.5 mr-1.5" />}
              onClick={handleDownload}
              disabled={downloading}
              loading={downloading}
              className="p-button-outlined p-button-secondary text-xs"
            />
            <Button
              label="Clear Log File"
              icon={<Trash2 className="w-3.5 h-3.5 mr-1.5 text-red-500" />}
              onClick={handleClearLog}
              className="p-button-outlined p-button-danger text-xs"
              disabled={actionLoading}
            />
          </div>
        }
      />

      {/* Filter Bar */}
      <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs flex flex-wrap items-center justify-between gap-3">
        <div className="flex items-center gap-2 flex-1 max-w-md">
          <AdminSearchInput
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            onClear={() => setSearch('')}
            placeholder="Search log messages or timestamps..."
            inputClassName="font-mono"
          />
        </div>

        <div className="flex flex-wrap items-center gap-2">
          <Dropdown
            value={level}
            options={[
              { label: 'All Log Levels', value: '' },
              { label: 'EMERGENCY', value: 'EMERGENCY' },
              { label: 'CRITICAL', value: 'CRITICAL' },
              { label: 'ERROR', value: 'ERROR' },
              { label: 'WARNING', value: 'WARNING' },
              { label: 'INFO', value: 'INFO' },
              { label: 'DEBUG', value: 'DEBUG' },
            ]}
            onChange={(e) => setLevel(e.value)}
            className="text-xs w-40"
            placeholder="Log Level"
          />

          <Button
            icon="pi pi-refresh"
            onClick={fetchLogs}
            className="p-button-outlined p-button-secondary text-xs"
            tooltip="Refresh logs"
          />
        </div>
      </div>

      {/* Log Output Console */}
      <div className="bg-slate-900 rounded-xl border border-slate-800 shadow-2xl overflow-hidden">
        <div className="p-3 bg-slate-950 border-b border-slate-800 flex items-center justify-between text-xs text-slate-400 font-mono">
          <span className="flex items-center space-x-2">
            <Terminal className="w-3.5 h-3.5 text-emerald-400" />
            <span>storage/logs/laravel.log (Recent 200 Entries)</span>
          </span>
          <span>{logs.length} matched log entries</span>
        </div>

        {loading ? (
          <div className="p-12 flex justify-center text-slate-400">
            <LoadingSpinner message="Reading server log buffer..." />
          </div>
        ) : error ? (
          <div className="p-8 text-center text-red-400 text-xs">
            <ErrorState title="Failed to Read Logs" message={error} onRetry={fetchLogs} />
          </div>
        ) : logs.length === 0 ? (
          <div className="p-12 text-center text-slate-500 text-xs font-mono">
            No matching log entries found in the active log buffer.
          </div>
        ) : (
          <div className="divide-y divide-slate-800/60 max-h-[650px] overflow-y-auto font-mono text-[11px]">
            {logs.map((entry, idx) => (
              <div key={idx} className="p-3 hover:bg-slate-800/40 transition-colors">
                <div className="flex items-center justify-between mb-1.5 flex-wrap gap-1">
                  <div className="flex items-center space-x-2">
                    {getLevelBadge(entry.level)}
                    <span className="text-slate-400 text-[10px]">[{entry.date}]</span>
                    {entry.env && (
                      <span className="text-slate-500 text-[10px]">({entry.env})</span>
                    )}
                  </div>
                </div>
                <div className="text-slate-200 break-words whitespace-pre-wrap leading-relaxed pl-1">
                  {entry.message || entry.raw}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}

export default SystemLogsPage;
