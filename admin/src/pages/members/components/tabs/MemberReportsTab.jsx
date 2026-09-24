import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Check, X, Eye, Trash2 } from 'lucide-react';
import { StatusBadge } from '../../../../components/common/StatusBadge';
import { EmptyState } from '../../../../components/common/EmptyState';
import { reportsApi } from '../../../../api';
import { useToast } from '../../../../hooks/useToast';

export function MemberReportsTab({
  reports = [],
  onRefresh,
}) {
  const { showSuccess, showError } = useToast();
  const [actionLoading, setActionLoading] = useState(false);

  const handleUpdateStatus = async (report, status) => {
    if (actionLoading) return;
    const reportId = report.id;
    const reportType = report.type || 'post';
    setActionLoading(true);
    try {
      await reportsApi.updateReportStatus(reportType, reportId, status);
      showSuccess(`Report #${reportId} marked as ${status}.`);
      onRefresh?.();
    } catch (err) {
      showError(err.message || 'Failed to update report status.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleDeleteReport = async (report) => {
    if (actionLoading) return;
    const reportId = report.id;
    const reportType = report.type || 'post';
    if (!window.confirm(`Are you sure you want to delete report ticket #${reportId}? This will remove the report record.`)) {
      return;
    }
    setActionLoading(true);
    try {
      await reportsApi.deleteReport(reportType, reportId);
      showSuccess(`Report ticket #${reportId} deleted successfully.`);
      onRefresh?.();
    } catch (err) {
      showError(err.message || 'Failed to delete report ticket.');
    } finally {
      setActionLoading(false);
    }
  };

  if (reports.length === 0) {
    return (
      <EmptyState
        title="Clean Moderation Record"
        description="No policy reports or moderation flags recorded for this member."
      />
    );
  }

  return (
    <div className="pt-2">
      <div className="overflow-x-auto border border-slate-200 rounded-xl bg-white">
        <table className="w-full text-left text-xs border-collapse">
          <thead>
            <tr className="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
              <th className="py-3 px-4">Report ID</th>
              <th className="py-3 px-4">Target Entity</th>
              <th className="py-3 px-4">Reason</th>
              <th className="py-3 px-4">Description</th>
              <th className="py-3 px-4">Status</th>
              <th className="py-3 px-4">Reported On</th>
              <th className="py-3 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {reports.map((report) => {
              const status = report.status || 'pending';
              const reportType = report.type || 'post';

              return (
                <tr key={report.id} className="hover:bg-slate-50/80 transition-colors">
                  <td className="py-3 px-4 font-mono font-bold text-slate-800 whitespace-nowrap">
                    #{report.id}
                  </td>
                  <td className="py-3 px-4 whitespace-nowrap">
                    <span className="bg-slate-100 text-slate-700 font-semibold px-2 py-0.5 rounded-md text-[10px]">
                      {report.type_label || 'Post'} #{report.target_id || report.post_id || 'N/A'}
                    </span>
                  </td>
                  <td className="py-3 px-4 font-bold text-red-600 whitespace-nowrap">
                    {report.reason || 'Policy Violation'}
                  </td>
                  <td className="py-3 px-4 max-w-xs truncate text-slate-700">
                    {report.description || 'No additional details provided'}
                  </td>
                  <td className="py-3 px-4 whitespace-nowrap">
                    <StatusBadge
                      status={status === 'resolved' ? 'resolved' : (status === 'dismissed' ? 'inactive' : 'pending')}
                      label={status === 'resolved' ? 'Resolved' : (status === 'dismissed' ? 'Dismissed' : 'Pending Review')}
                    />
                  </td>
                  <td className="py-3 px-4 text-slate-500 whitespace-nowrap">
                    {report.created_at_human || 'Recently'}
                  </td>
                  <td className="py-3 px-4 text-right whitespace-nowrap">
                    <div className="inline-flex items-center justify-end gap-1.5 sm:gap-2">
                      <Link
                        to={`/admin/reports/${reportType}/${report.id}`}
                        className="p-1.5 rounded-md text-blue-600 hover:bg-blue-50 transition-colors"
                        title="View Report Inspection"
                      >
                        <Eye className="w-3.5 h-3.5" />
                      </Link>

                      {status !== 'resolved' && (
                        <button
                          type="button"
                          onClick={() => handleUpdateStatus(report, 'resolved')}
                          disabled={actionLoading}
                          className="p-1.5 rounded-md text-emerald-600 hover:bg-emerald-50 transition-colors disabled:opacity-50"
                          title="Mark Resolved"
                        >
                          <Check className="w-3.5 h-3.5" />
                        </button>
                      )}

                      {status !== 'dismissed' && (
                        <button
                          type="button"
                          onClick={() => handleUpdateStatus(report, 'dismissed')}
                          disabled={actionLoading}
                          className="p-1.5 rounded-md text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors disabled:opacity-50"
                          title="Dismiss Report"
                        >
                          <X className="w-3.5 h-3.5" />
                        </button>
                      )}

                      <button
                        type="button"
                        onClick={() => handleDeleteReport(report)}
                        disabled={actionLoading}
                        className="p-1.5 rounded-md text-rose-500 hover:text-rose-700 hover:bg-rose-50 transition-colors disabled:opacity-50"
                        title="Delete Report Ticket"
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    </div>
  );
}

export default MemberReportsTab;
