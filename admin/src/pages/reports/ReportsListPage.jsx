import { useState, useEffect, useCallback } from 'react';
import { useSearchParams, Link } from 'react-router-dom';
import { Download, Eye, CheckCircle2, Trash2, Video, Image as ImageIcon } from 'lucide-react';
import { Button } from 'primereact/button';
import { Checkbox } from 'primereact/checkbox';
import { Paginator } from 'primereact/paginator';
import { PageHeader } from '../../components/common/PageHeader';
import { StatusBadge } from '../../components/common/StatusBadge';
import { EmptyState } from '../../components/common/EmptyState';
import { ErrorState } from '../../components/common/ErrorState';
import { useToast } from '../../hooks/useToast';
import { confirmHelper } from '../../utils/confirmHelper';
import { reportsApi, downloadBlobFromResponse } from '../../api';

// Subcomponents
import { ReportStatsCards } from './components/ReportStatsCards';
import { ReportFilterBar } from './components/ReportFilterBar';
import { ReportBulkActionsBar } from './components/ReportBulkActionsBar';

export function ReportsListPage() {
  const { showSuccess, showError } = useToast();
  const [searchParams, setSearchParams] = useSearchParams();

  const [reports, setReports] = useState([]);
  const [stats, setStats] = useState({
    totalCount: 0,
    postCount: 0,
    commCount: 0,
    productCount: 0,
  });

  const [filters, setFilters] = useState(() => ({
    q: searchParams.get('q') || '',
    source_type: searchParams.get('source_type') || '',
    reason: searchParams.get('reason') || '',
    status: searchParams.get('status') || '',
    date_from: searchParams.get('date_from') || '',
    date_to: searchParams.get('date_to') || '',
  }));
  const [pagination, setPagination] = useState({
    page: 1,
    perPage: 15,
    total: 0,
  });

  const [selectedKeys, setSelectedKeys] = useState([]);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [exporting, setExporting] = useState(false);
  const [error, setError] = useState(null);

  const fetchReports = useCallback(async (page = 1, currentFilters = filters) => {
    setLoading(true);
    setError(null);
    try {
      const params = {
        page,
        ...currentFilters,
      };
      const res = await reportsApi.getReports(params);
      const reportsData = res?.paginatedReports?.data || res?.data?.reports?.data || res?.data || (Array.isArray(res) ? res : []);
      const paginatorData = res?.paginatedReports || res?.data?.reports || {};

      setReports(reportsData);
      setStats({
        totalCount: res?.totalCount ?? paginatorData?.total ?? reportsData.length,
        postCount: res?.postCount ?? 0,
        commCount: res?.commCount ?? 0,
        productCount: res?.productCount ?? 0,
      });

      setPagination({
        page: paginatorData?.current_page || page,
        perPage: paginatorData?.per_page || 15,
        total: paginatorData?.total || reportsData.length,
      });

      setSelectedKeys([]);
    } catch (err) {
      setError(err.message || 'Failed to load moderation reports.');
    } finally {
      setLoading(false);
    }
  }, [filters]);

  useEffect(() => {
    fetchReports(1, filters);
  }, []);

  const handleFilter = (newFilters) => {
    setFilters(newFilters);
    const next = new URLSearchParams();
    Object.entries(newFilters).forEach(([k, v]) => {
      if (v) next.set(k, v);
    });
    setSearchParams(next, { replace: true });
    fetchReports(1, newFilters);
  };

  const handlePageChange = (e) => {
    const newPage = e.page + 1;
    fetchReports(newPage, filters);
  };

  const handleResolveSingle = async (report) => {
    setActionLoading(true);
    try {
      await reportsApi.updateReportStatus(report.type, report.id, 'resolved');
      showSuccess(`Report #${report.id} marked as resolved.`);
      fetchReports(pagination.page, filters);
    } catch (err) {
      showError(err.message || 'Failed to resolve report.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleDeleteReport = (report) => {
    confirmHelper.confirm({
      header: 'Delete Report Ticket?',
      message: `Are you sure you want to permanently delete Report #${report.id}? The reported content and users will remain unchanged.`,
      acceptClassName: 'p-button-danger text-xs',
      onAccept: async () => {
        setActionLoading(true);
        try {
          await reportsApi.deleteReport(report.type, report.id);
          showSuccess(`Report #${report.id} ticket deleted successfully.`);
          fetchReports(pagination.page, filters);
        } catch (err) {
          showError(err.message || 'Failed to delete report ticket.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  const handleBulkAction = async (action) => {
    setActionLoading(true);
    try {
      await reportsApi.bulkAction(action, selectedKeys);
      showSuccess(`Selected reports marked as ${action}.`);
      fetchReports(pagination.page, filters);
    } catch (err) {
      showError(err.message || 'Failed to execute bulk action.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleExportCsv = async () => {
    if (exporting) return;
    setExporting(true);
    try {
      const blob = await reportsApi.exportCsv(filters);
      await downloadBlobFromResponse(blob, 'reports_export.csv');
      showSuccess('Reports CSV downloaded.');
    } catch (err) {
      showError(err.message || 'Failed to export reports.');
    } finally {
      setExporting(false);
    }
  };

  const handleSelectAll = () => {
    if (selectedKeys.length === reports.length) {
      setSelectedKeys([]);
    } else {
      setSelectedKeys(reports.map((r) => `${r.type}_${r.id}`));
    }
  };

  const handleSelectRow = (key) => {
    setSelectedKeys((prev) =>
      prev.includes(key) ? prev.filter((item) => item !== key) : [...prev, key]
    );
  };

  const allSelected = reports.length > 0 && selectedKeys.length === reports.length;

  const getTypeBadgeClass = (type) => {
    switch (type) {
      case 'post':
        return 'bg-blue-50 text-blue-700 border-blue-200';
      case 'community':
        return 'bg-emerald-50 text-emerald-700 border-emerald-200';
      case 'product':
        return 'bg-amber-50 text-amber-700 border-amber-200';
      case 'business_review':
        return 'bg-purple-50 text-purple-700 border-purple-200';
      default:
        return 'bg-slate-50 text-slate-700 border-slate-200';
    }
  };

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <PageHeader
        title="Platform Reports Management"
        subtitle="Central moderation queue for reported posts, communities, products, and reviews."
        breadcrumbs={[{ label: 'Moderation', to: '/admin/reports' }, { label: 'Reports Queue' }]}
        actions={
          <Button
            label={exporting ? 'Exporting...' : 'Export CSV'}
            icon={exporting ? 'pi pi-spin pi-spinner' : <Download className="w-3.5 h-3.5 mr-1.5" />}
            size="small"
            onClick={handleExportCsv}
            disabled={exporting}
            loading={exporting}
            className="p-button-outlined p-button-secondary text-xs"
          />
        }
      />

      {error ? (
        <ErrorState
          title="Failed to Load Reports"
          message={error}
          onRetry={() => fetchReports(pagination.page, filters)}
        />
      ) : (
        <>
          {/* Summary Metric Cards */}
          <ReportStatsCards stats={stats} loading={loading} />

          {/* Directory Card */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 sm:p-6">
            {/* Filter Bar */}
            <ReportFilterBar filters={filters} onFilter={handleFilter} loading={loading} />

            {/* Bulk Actions */}
            <ReportBulkActionsBar
              selectedCount={selectedKeys.length}
              onExecuteBulkAction={handleBulkAction}
              loading={actionLoading}
            />

            {/* Table */}
            {reports.length === 0 && !loading ? (
              <EmptyState
                title="No Reports in Queue"
                description="There are currently no reports matching your filter criteria."
              />
            ) : (
              <div className="overflow-x-auto border border-slate-200 rounded-2xl bg-white">
                <table className="w-full text-left text-xs border-collapse">
                  <thead>
                    <tr className="bg-slate-50/90 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                      <th className="py-3 px-4 w-10 text-center">
                        <Checkbox
                          checked={allSelected}
                          onChange={handleSelectAll}
                          disabled={reports.length === 0 || loading}
                        />
                      </th>
                      <th className="py-3 px-4 w-20">ID</th>
                      <th className="py-3 px-4">Source Type</th>
                      <th className="py-3 px-4 min-w-[220px]">Reported Target</th>
                      <th className="py-3 px-4">Reporter</th>
                      <th className="py-3 px-4">Reason</th>
                      <th className="py-3 px-4">Status</th>
                      <th className="py-3 px-4">Created Date</th>
                      <th className="py-3 px-4 text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {reports.map((report) => {
                      const rowKey = `${report.type}_${report.id}`;
                      const isSelected = selectedKeys.includes(rowKey);
                      const isResolved = report.status === 'resolved';

                      return (
                        <tr
                          key={rowKey}
                          className={`hover:bg-slate-50/80 transition-colors ${isSelected ? 'bg-blue-50/40' : ''}`}
                        >
                          <td className="py-3 px-4 text-center">
                            <Checkbox
                              checked={isSelected}
                              onChange={() => handleSelectRow(rowKey)}
                            />
                          </td>

                          {/* ID */}
                          <td className="py-3 px-4 font-mono font-bold text-slate-700">
                            #{report.id}
                          </td>

                          {/* Source Type */}
                          <td className="py-3 px-4 whitespace-nowrap">
                            <span className={`inline-block text-[10px] font-bold px-2 py-0.5 rounded-full border ${getTypeBadgeClass(report.type)}`}>
                              {report.type_label || report.type}
                            </span>
                          </td>

                          {/* Target */}
                          <td className="py-3 px-4 max-w-xs">
                            <div className="flex items-start space-x-2.5">
                              {report.target_media_url ? (
                                <div className="w-9 h-9 rounded-lg overflow-hidden bg-slate-100 border border-slate-200 shrink-0 flex items-center justify-center">
                                  {report.target_media_type === 'video' ? (
                                    <div className="w-full h-full bg-slate-900 text-purple-400 flex items-center justify-center" title="Video Post">
                                      <Video className="w-4 h-4" />
                                    </div>
                                  ) : (
                                    <img
                                      src={report.target_media_url}
                                      alt="Thumbnail"
                                      className="w-full h-full object-cover"
                                      onError={(e) => {
                                        e.currentTarget.style.display = 'none';
                                        if (e.currentTarget.parentElement) {
                                          e.currentTarget.parentElement.innerHTML = '<span class="text-[9px] text-slate-400 font-mono">IMG</span>';
                                        }
                                      }}
                                    />
                                  )}
                                </div>
                              ) : null}
                              <div className="min-w-0 flex-1">
                                <Link
                                  to={`/admin/reports/${report.type}/${report.id}`}
                                  className="font-bold text-slate-900 hover:text-blue-600 truncate block text-xs"
                                >
                                  {report.target_title || 'Report Target'}
                                </Link>
                                <span className="text-[11px] text-slate-400 block truncate">
                                  By: {report.target_owner || 'System'}
                                </span>
                              </div>
                            </div>
                          </td>

                          {/* Reporter */}
                          <td className="py-3 px-4 whitespace-nowrap">
                            <div className="flex items-center space-x-2">
                              <div className="w-6 h-6 rounded-full bg-slate-200 overflow-hidden shrink-0">
                                {report.reporter_avatar ? (
                                  <img src={report.reporter_avatar} alt={report.reporter_name} className="w-full h-full object-cover" />
                                ) : (
                                  <div className="w-full h-full flex items-center justify-center text-[9px] font-bold text-slate-600">
                                    {(report.reporter_name || 'A').charAt(0)}
                                  </div>
                                )}
                              </div>
                              <div>
                                <span className="font-bold text-slate-900 block text-xs">{report.reporter_name}</span>
                                <code className="text-[10px] text-slate-400 font-mono">{report.reporter_user_id}</code>
                              </div>
                            </div>
                          </td>

                          {/* Reason */}
                          <td className="py-3 px-4 whitespace-nowrap">
                            <span className="inline-block text-[10px] font-bold text-red-700 bg-red-50 px-2 py-0.5 rounded-full border border-red-200">
                              {report.reason}
                            </span>
                          </td>

                          {/* Status */}
                          <td className="py-3 px-4 whitespace-nowrap">
                            <StatusBadge status={report.status || 'pending'} />
                          </td>

                          {/* Created Date */}
                          <td className="py-3 px-4 text-slate-500 whitespace-nowrap">
                            {report.created_at_human || 'Recently'}
                          </td>

                          {/* Actions */}
                          <td className="py-3 px-4 text-right whitespace-nowrap">
                            <div className="inline-flex items-center justify-end gap-1.5 sm:gap-2">
                              <Link
                                to={`/admin/reports/${report.type}/${report.id}`}
                                className="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition-colors inline-flex"
                                title="Inspect Report"
                              >
                                <Eye className="w-3.5 h-3.5" />
                              </Link>

                              {!isResolved && (
                                <button
                                  type="button"
                                  onClick={() => handleResolveSingle(report)}
                                  disabled={actionLoading}
                                  className="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors"
                                  title="Mark Resolved"
                                >
                                  <CheckCircle2 className="w-3.5 h-3.5" />
                                </button>
                              )}

                              <button
                                type="button"
                                onClick={() => handleDeleteReport(report)}
                                disabled={actionLoading}
                                className="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors"
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
            )}

            {/* Pagination */}
            {pagination.total > pagination.perPage && (
              <div className="pt-4 border-t border-slate-100 flex justify-end">
                <Paginator
                  first={(pagination.page - 1) * pagination.perPage}
                  rows={pagination.perPage}
                  totalRecords={pagination.total}
                  onPageChange={handlePageChange}
                  className="text-xs"
                />
              </div>
            )}
          </div>
        </>
      )}
    </div>
  );
}

export default ReportsListPage;
