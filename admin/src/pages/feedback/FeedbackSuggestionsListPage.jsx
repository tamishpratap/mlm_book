import { useState, useEffect, useCallback } from 'react';
import { useSearchParams, Link } from 'react-router-dom';
import { Download, Eye, CheckCircle2, Trash2, MessageSquare, ShieldCheck, User } from 'lucide-react';
import { Button } from 'primereact/button';
import { Checkbox } from 'primereact/checkbox';
import { Paginator } from 'primereact/paginator';
import { PageHeader } from '../../components/common/PageHeader';
import { StatusBadge } from '../../components/common/StatusBadge';
import { EmptyState } from '../../components/common/EmptyState';
import { ErrorState } from '../../components/common/ErrorState';
import { useToast } from '../../hooks/useToast';
import { confirmHelper } from '../../utils/confirmHelper';
import { feedbackSuggestionsApi, downloadBlobFromResponse } from '../../api';

// Subcomponents
import { FeedbackStatsCards } from './components/FeedbackStatsCards';
import { FeedbackFilterBar } from './components/FeedbackFilterBar';
import { FeedbackBulkActionsBar } from './components/FeedbackBulkActionsBar';
import { FeedbackDetailModal } from './components/FeedbackDetailModal';

export function FeedbackSuggestionsListPage() {
  const { showSuccess, showError } = useToast();
  const [searchParams, setSearchParams] = useSearchParams();

  const [feedbacks, setFeedbacks] = useState([]);
  const [stats, setStats] = useState({
    totalCount: 0,
    newCount: 0,
    inReviewCount: 0,
    resolvedCount: 0,
  });

  const [filters, setFilters] = useState(() => ({
    q: searchParams.get('q') || '',
    type: searchParams.get('type') || '',
    status: searchParams.get('status') || '',
    date_from: searchParams.get('date_from') || '',
    date_to: searchParams.get('date_to') || '',
  }));

  const [pagination, setPagination] = useState({
    page: 1,
    perPage: 15,
    total: 0,
  });

  const [selectedIds, setSelectedIds] = useState([]);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [exporting, setExporting] = useState(false);
  const [error, setError] = useState(null);

  // Detail Modal State
  const [activeItem, setActiveItem] = useState(null);
  const [modalVisible, setModalVisible] = useState(false);

  const fetchFeedbacks = useCallback(async (page = 1, currentFilters = filters) => {
    setLoading(true);
    setError(null);
    try {
      const params = {
        page,
        ...currentFilters,
      };
      const res = await feedbackSuggestionsApi.getFeedbacks(params);
      const dataItems = res?.feedbacks?.data || res?.data?.feedbacks?.data || (Array.isArray(res) ? res : []);
      const paginatorData = res?.feedbacks || res?.data?.feedbacks || {};

      setFeedbacks(dataItems);
      if (res?.stats) {
        setStats(res.stats);
      }

      setPagination({
        page: paginatorData?.current_page || page,
        perPage: paginatorData?.per_page || 15,
        total: paginatorData?.total || dataItems.length,
      });

      setSelectedIds([]);
    } catch (err) {
      setError(err.message || 'Failed to load feedback and suggestions.');
    } finally {
      setLoading(false);
    }
  }, [filters]);

  useEffect(() => {
    fetchFeedbacks(1, filters);
  }, []);

  const handleFilter = (newFilters) => {
    setFilters(newFilters);
    const next = new URLSearchParams();
    Object.entries(newFilters).forEach(([k, v]) => {
      if (v) next.set(k, v);
    });
    setSearchParams(next, { replace: true });
    fetchFeedbacks(1, newFilters);
  };

  const handlePageChange = (e) => {
    const newPage = e.page + 1;
    fetchFeedbacks(newPage, filters);
  };

  const handleOpenDetail = (item) => {
    setActiveItem(item);
    setModalVisible(true);
  };

  const handleDetailUpdate = (updatedItem) => {
    setFeedbacks((prev) =>
      prev.map((f) => (f.id === updatedItem.id ? { ...f, ...updatedItem } : f))
    );
    // Refresh stats in background
    feedbackSuggestionsApi.getFeedbacks({ page: pagination.page, ...filters }).then((res) => {
      if (res?.stats) setStats(res.stats);
    }).catch(() => {});
  };

  const handleResolveSingle = async (item) => {
    setActionLoading(true);
    try {
      await feedbackSuggestionsApi.updateStatus(item.id, 'resolved');
      showSuccess(`Submission #${item.id} marked as resolved.`);
      fetchFeedbacks(pagination.page, filters);
    } catch (err) {
      showError(err.message || 'Failed to resolve submission.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleDeleteSingle = (item) => {
    confirmHelper.confirm({
      header: 'Delete Submission?',
      message: `Are you sure you want to permanently delete submission #${item.id}? This action cannot be undone.`,
      acceptClassName: 'p-button-danger text-xs',
      onAccept: async () => {
        setActionLoading(true);
        try {
          await feedbackSuggestionsApi.deleteFeedback(item.id);
          showSuccess(`Submission #${item.id} deleted successfully.`);
          fetchFeedbacks(pagination.page, filters);
        } catch (err) {
          showError(err.message || 'Failed to delete submission.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  const handleBulkAction = async (action) => {
    setActionLoading(true);
    try {
      const res = await feedbackSuggestionsApi.bulkAction(action, selectedIds);
      showSuccess(res?.message || 'Bulk action executed successfully.');
      fetchFeedbacks(pagination.page, filters);
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
      const blob = await feedbackSuggestionsApi.exportCsv(filters);
      await downloadBlobFromResponse(blob, 'feedback_suggestions_export.csv');
      showSuccess('Feedback CSV exported successfully.');
    } catch (err) {
      showError(err.message || 'Failed to export feedback CSV.');
    } finally {
      setExporting(false);
    }
  };

  const handleSelectAll = () => {
    if (selectedIds.length === feedbacks.length) {
      setSelectedIds([]);
    } else {
      setSelectedIds(feedbacks.map((f) => f.id));
    }
  };

  const handleSelectRow = (id) => {
    setSelectedIds((prev) =>
      prev.includes(id) ? prev.filter((item) => item !== id) : [...prev, id]
    );
  };

  const allSelected = feedbacks.length > 0 && selectedIds.length === feedbacks.length;

  const getTypeBadgeClass = (type) => {
    switch (type) {
      case 'suggestion':
        return 'bg-purple-50 text-purple-700 border-purple-200';
      case 'idea':
        return 'bg-amber-50 text-amber-700 border-amber-200';
      case 'complaint':
        return 'bg-rose-50 text-rose-700 border-rose-200';
      case 'bug_report':
        return 'bg-red-50 text-red-700 border-red-200';
      case 'other':
        return 'bg-slate-50 text-slate-700 border-slate-200';
      default:
        return 'bg-blue-50 text-blue-700 border-blue-200';
    }
  };

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <PageHeader
        title="Feedback & Suggestions"
        subtitle="Manage member-submitted ideas, platform feedback, and user suggestions."
        breadcrumbs={[
          { label: 'Security & Oversight', to: '/admin/reports' },
          { label: 'Feedback & Suggestions' },
        ]}
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
          title="Failed to Load Feedback"
          message={error}
          onRetry={() => fetchFeedbacks(pagination.page, filters)}
        />
      ) : (
        <>
          {/* Summary Metric Cards */}
          <FeedbackStatsCards stats={stats} loading={loading} />

          {/* Directory Card */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 sm:p-6">
            {/* Filter Bar */}
            <FeedbackFilterBar filters={filters} onFilter={handleFilter} loading={loading} />

            {/* Bulk Actions */}
            <FeedbackBulkActionsBar
              selectedCount={selectedIds.length}
              onExecuteBulkAction={handleBulkAction}
              loading={actionLoading}
            />

            {/* Table */}
            {feedbacks.length === 0 && !loading ? (
              <EmptyState
                title="No Feedback Submissions Found"
                description="There are currently no submissions matching your filter criteria."
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
                          disabled={feedbacks.length === 0 || loading}
                        />
                      </th>
                      <th className="py-3 px-4 w-16">ID</th>
                      <th className="py-3 px-4 min-w-[160px]">Member</th>
                      <th className="py-3 px-4 w-28">Type</th>
                      <th className="py-3 px-4 min-w-[200px]">Subject & Message</th>
                      <th className="py-3 px-4 w-24">Status</th>
                      <th className="py-3 px-4 w-28 whitespace-nowrap">Submitted</th>
                      <th className="py-3 px-4 text-right w-24">Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {feedbacks.map((item) => {
                      const isSelected = selectedIds.includes(item.id);
                      const isResolved = item.status === 'resolved';
                      const member = item.member;

                      return (
                        <tr
                          key={item.id}
                          className={`hover:bg-slate-50/80 transition-colors ${
                            isSelected ? 'bg-blue-50/40' : ''
                          }`}
                        >
                          {/* Checkbox */}
                          <td className="py-3 px-4 text-center">
                            <Checkbox
                              checked={isSelected}
                              onChange={() => handleSelectRow(item.id)}
                            />
                          </td>

                          {/* ID */}
                          <td className="py-3 px-4 font-mono font-bold text-slate-700">
                            #{item.id}
                          </td>

                          {/* Member */}
                          <td className="py-3 px-4 whitespace-nowrap">
                            <div className="flex items-center space-x-2.5">
                              <div className="w-7 h-7 rounded-full bg-slate-200 overflow-hidden shrink-0 flex items-center justify-center text-[10px] font-bold text-slate-600">
                                {member?.avatar_url || member?.profile_photo ? (
                                  <img
                                    src={member.avatar_url || member.profile_photo}
                                    alt={member.name}
                                    className="w-full h-full object-cover"
                                  />
                                ) : (
                                  (member?.name || 'M').charAt(0).toUpperCase()
                                )}
                              </div>
                              <div className="min-w-0">
                                {member?.id ? (
                                  <Link
                                    to={`/admin/members/${member.id}`}
                                    className="font-bold text-slate-900 hover:text-blue-600 truncate block text-xs"
                                  >
                                    {member.name}
                                  </Link>
                                ) : (
                                  <span className="font-bold text-slate-900 truncate block text-xs">
                                    {member?.name || 'Anonymous'}
                                  </span>
                                )}
                                <span className="text-[10px] text-slate-400 font-mono block">
                                  {member?.user_id ? `@${member.user_id}` : `ID: ${item.member_id}`}
                                </span>
                              </div>
                            </div>
                          </td>

                          {/* Type */}
                          <td className="py-3 px-4 whitespace-nowrap">
                            <span
                              className={`inline-block text-[10px] font-bold px-2 py-0.5 rounded-full border ${getTypeBadgeClass(
                                item.type
                              )}`}
                            >
                              {item.type_label || item.type}
                            </span>
                          </td>

                          {/* Subject & Message */}
                          <td className="py-3 px-4 max-w-sm">
                            <button
                              type="button"
                              onClick={() => handleOpenDetail(item)}
                              className="text-left group cursor-pointer block w-full"
                            >
                              <div className="font-bold text-slate-900 group-hover:text-blue-600 truncate text-xs">
                                {item.subject}
                              </div>
                              <div className="text-[11px] text-slate-400 truncate mt-0.5">
                                {item.message}
                              </div>
                            </button>
                            {item.admin_response && (
                              <div className="mt-1 flex items-center text-[10px] text-emerald-600 font-medium">
                                <ShieldCheck className="w-3 h-3 mr-1" />
                                <span>Admin responded</span>
                              </div>
                            )}
                          </td>

                          {/* Status */}
                          <td className="py-3 px-4 whitespace-nowrap">
                            <StatusBadge status={item.status} />
                          </td>

                          {/* Created Date */}
                          <td className="py-3 px-4 text-slate-500 whitespace-nowrap text-[11px]">
                            {item.created_at_human || 'Recently'}
                          </td>

                          {/* Actions */}
                          <td className="py-3 px-4 text-right whitespace-nowrap">
                            <div className="inline-flex items-center justify-end gap-1.5 sm:gap-2">
                              <button
                                type="button"
                                onClick={() => handleOpenDetail(item)}
                                className="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition-colors inline-flex cursor-pointer"
                                title="Inspect Submission"
                              >
                                <Eye className="w-3.5 h-3.5" />
                              </button>

                              {!isResolved && (
                                <button
                                  type="button"
                                  onClick={() => handleResolveSingle(item)}
                                  disabled={actionLoading}
                                  className="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors cursor-pointer"
                                  title="Mark Resolved"
                                >
                                  <CheckCircle2 className="w-3.5 h-3.5" />
                                </button>
                              )}

                              <button
                                type="button"
                                onClick={() => handleDeleteSingle(item)}
                                disabled={actionLoading}
                                className="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors cursor-pointer"
                                title="Delete Submission"
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

      {/* Detail Modal */}
      <FeedbackDetailModal
        visible={modalVisible}
        onHide={() => setModalVisible(false)}
        feedback={activeItem}
        onUpdateSuccess={handleDetailUpdate}
      />
    </div>
  );
}

export default FeedbackSuggestionsListPage;
