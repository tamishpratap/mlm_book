import { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { Download, Megaphone, Eye, CheckCircle2 } from 'lucide-react';
import { Button } from 'primereact/button';
import { Checkbox } from 'primereact/checkbox';
import { Paginator } from 'primereact/paginator';
import { PageHeader } from '../../components/common/PageHeader';
import { StatusBadge } from '../../components/common/StatusBadge';
import { EmptyState } from '../../components/common/EmptyState';
import { ErrorState } from '../../components/common/ErrorState';
import { useToast } from '../../hooks/useToast';
import { notificationsApi, downloadBlobFromResponse } from '../../api';

// Subcomponents
import { NotificationStatsCards } from './components/NotificationStatsCards';
import { NotificationFilterBar } from './components/NotificationFilterBar';
import { NotificationBulkActionsBar } from './components/NotificationBulkActionsBar';

export function NotificationsListPage() {
  const { showSuccess, showError } = useToast();

  const [notifications, setNotifications] = useState([]);
  const [stats, setStats] = useState({
    totalCount: 0,
    sentTodayCount: 0,
    readCount: 0,
    queuedJobsCount: 0,
  });

  const [filters, setFilters] = useState({});
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

  const fetchNotifications = useCallback(async (page = 1, currentFilters = filters) => {
    setLoading(true);
    setError(null);
    try {
      const params = {
        page,
        ...currentFilters,
      };
      const res = await notificationsApi.getNotifications(params);
      const notesData = res?.notifications?.data || res?.paginatedNotifications?.data || res?.data?.notifications?.data || (Array.isArray(res?.notifications) ? res.notifications : Array.isArray(res?.data) ? res.data : Array.isArray(res) ? res : []);
      const paginatorData = res?.notifications || res?.paginatedNotifications || res?.data?.notifications || {};

      setNotifications(notesData);
      setStats({
        totalCount: res?.totalCount ?? paginatorData?.total ?? notesData.length,
        sentTodayCount: res?.sentTodayCount ?? 0,
        readCount: res?.readCount ?? 0,
        queuedJobsCount: res?.queuedJobsCount ?? 0,
      });

      setPagination({
        page: paginatorData?.current_page || page,
        perPage: paginatorData?.per_page || 15,
        total: paginatorData?.total || notesData.length,
      });

      setSelectedIds([]);
    } catch (err) {
      setError(err.message || 'Failed to load notifications.');
    } finally {
      setLoading(false);
    }
  }, [filters]);

  useEffect(() => {
    let isMounted = true;
    notificationsApi.getNotifications({ page: 1 })
      .then((res) => {
        if (!isMounted) return;
        const notesData = res?.notifications?.data || res?.paginatedNotifications?.data || res?.data?.notifications?.data || (Array.isArray(res?.notifications) ? res.notifications : Array.isArray(res?.data) ? res.data : Array.isArray(res) ? res : []);
        const paginatorData = res?.notifications || res?.paginatedNotifications || res?.data?.notifications || {};

        setNotifications(notesData);
        setStats({
          totalCount: res?.totalCount ?? paginatorData?.total ?? notesData.length,
          sentTodayCount: res?.sentTodayCount ?? 0,
          readCount: res?.readCount ?? 0,
          queuedJobsCount: res?.queuedJobsCount ?? 0,
        });

        setPagination({
          page: paginatorData?.current_page || 1,
          perPage: paginatorData?.per_page || 15,
          total: paginatorData?.total || notesData.length,
        });
      })
      .catch((err) => {
        if (!isMounted) return;
        setError(err.message || 'Failed to load notifications.');
      })
      .finally(() => {
        if (isMounted) setLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, []);

  const handleFilter = (newFilters) => {
    setFilters(newFilters);
    fetchNotifications(1, newFilters);
  };

  const handlePageChange = (e) => {
    const newPage = e.page + 1;
    fetchNotifications(newPage, filters);
  };

  const handleMarkRead = async (id) => {
    setActionLoading(true);
    try {
      await notificationsApi.markRead(id);
      showSuccess(`Notification marked as read.`);
      fetchNotifications(pagination.page, filters);
    } catch (err) {
      showError(err.message || 'Failed to update notification.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleBulkAction = async (action) => {
    setActionLoading(true);
    try {
      await notificationsApi.bulkAction(action, selectedIds);
      showSuccess(`Selected notifications marked as ${action}.`);
      fetchNotifications(pagination.page, filters);
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
      const blob = await notificationsApi.exportCsv(filters);
      await downloadBlobFromResponse(blob, 'notifications_export.csv');
      showSuccess('Notifications CSV downloaded.');
    } catch (err) {
      showError(err.message || 'Failed to export notifications.');
    } finally {
      setExporting(false);
    }
  };

  const handleSelectAll = () => {
    if (selectedIds.length === notifications.length) {
      setSelectedIds([]);
    } else {
      setSelectedIds(notifications.map((n) => n.id));
    }
  };

  const handleSelectRow = (id) => {
    setSelectedIds((prev) =>
      prev.includes(id) ? prev.filter((item) => item !== id) : [...prev, id]
    );
  };

  const allSelected = notifications.length > 0 && selectedIds.length === notifications.length;

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <PageHeader
        title="Notifications & Communication Center"
        subtitle="Monitor, filter, and dispatch platform notifications and broadcast announcements."
        breadcrumbs={[{ label: 'Communication', to: '/admin/notifications' }, { label: 'Notifications Queue' }]}
        actions={
          <div className="flex flex-wrap items-center gap-2.5 sm:gap-3">
            <Link to="/admin/notifications/broadcast" className="inline-flex">
              <Button
                label="Send Broadcast Announcement"
                icon={<Megaphone className="w-3.5 h-3.5 mr-1.5" />}
                size="small"
                className="p-button-primary text-xs"
              />
            </Link>
            <Button
              label={exporting ? 'Exporting...' : 'Export CSV'}
              icon={exporting ? 'pi pi-spin pi-spinner' : <Download className="w-3.5 h-3.5 mr-1.5" />}
              size="small"
              onClick={handleExportCsv}
              disabled={exporting}
              loading={exporting}
              className="p-button-outlined p-button-secondary text-xs"
            />
          </div>
        }
      />

      {error ? (
        <ErrorState
          title="Failed to Load Notifications"
          message={error}
          onRetry={() => fetchNotifications(pagination.page, filters)}
        />
      ) : (
        <>
          {/* Summary Metric Cards */}
          <NotificationStatsCards stats={stats} loading={loading} />

          {/* Directory Card */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 sm:p-6">
            {/* Filter Bar */}
            <NotificationFilterBar onFilter={handleFilter} loading={loading} />

            {/* Bulk Actions */}
            <NotificationBulkActionsBar
              selectedCount={selectedIds.length}
              onExecuteBulkAction={handleBulkAction}
              loading={actionLoading}
            />

            {/* Table */}
            {notifications.length === 0 && !loading ? (
              <EmptyState
                title="No Notifications Found"
                description="There are currently no notification records matching your filter criteria."
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
                          disabled={notifications.length === 0 || loading}
                        />
                      </th>
                      <th className="py-3 px-4 w-20">ID</th>
                      <th className="py-3 px-4">Channel</th>
                      <th className="py-3 px-4 min-w-[220px]">Notification Title & Message</th>
                      <th className="py-3 px-4">Recipient</th>
                      <th className="py-3 px-4">Status</th>
                      <th className="py-3 px-4">Sent Date</th>
                      <th className="py-3 px-4 text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {notifications.map((n) => {
                      const isSelected = selectedIds.includes(n.id);
                      const shortId = typeof n.id === 'string' && n.id.length > 8 ? n.id.substring(0, 8) : n.id;

                      return (
                        <tr
                          key={n.id}
                          className={`hover:bg-slate-50/80 transition-colors ${isSelected ? 'bg-blue-50/40' : ''}`}
                        >
                          <td className="py-3 px-4 text-center">
                            <Checkbox
                              checked={isSelected}
                              onChange={() => handleSelectRow(n.id)}
                            />
                          </td>

                          {/* ID */}
                          <td className="py-3 px-4 font-mono font-bold text-blue-600">
                            #{shortId}
                          </td>

                          {/* Channel */}
                          <td className="py-3 px-4 whitespace-nowrap">
                            <span className={`inline-block text-[10px] font-bold px-2 py-0.5 rounded-full border ${n.channel === 'In-App' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-sky-50 text-sky-700 border-sky-200'}`}>
                              {n.channel || 'In-App'}
                            </span>
                          </td>

                          {/* Title & Message */}
                          <td className="py-3 px-4 max-w-xs">
                            <Link
                              to={`/admin/notifications/${n.id}`}
                              className="font-bold text-slate-900 hover:text-blue-600 truncate block text-xs"
                            >
                              {n.title || 'Platform Notification'}
                            </Link>
                            <span className="text-[11px] text-slate-400 truncate block">
                              {n.message}
                            </span>
                          </td>

                          {/* Recipient */}
                          <td className="py-3 px-4 whitespace-nowrap">
                            <div className="flex items-center space-x-2">
                              <div className="w-6 h-6 rounded-full bg-slate-200 overflow-hidden shrink-0">
                                {n.recipient_avatar ? (
                                  <img src={n.recipient_avatar} alt={n.recipient_name} className="w-full h-full object-cover" />
                                ) : (
                                  <div className="w-full h-full flex items-center justify-center text-[9px] font-bold text-slate-600">
                                    {(n.recipient_name || 'M').charAt(0)}
                                  </div>
                                )}
                              </div>
                              <div>
                                <span className="font-bold text-slate-900 block text-xs">{n.recipient_name}</span>
                                <code className="text-[10px] text-slate-400 font-mono">{n.recipient_user_id}</code>
                              </div>
                            </div>
                          </td>

                          {/* Status */}
                          <td className="py-3 px-4 whitespace-nowrap">
                            <StatusBadge status={n.is_read ? 'read' : 'unread'} />
                          </td>

                          {/* Sent Date */}
                          <td className="py-3 px-4 text-slate-500 whitespace-nowrap">
                            {n.created_at_human || 'Recently'}
                          </td>

                          {/* Actions */}
                          <td className="py-3 px-4 text-right whitespace-nowrap">
                            <div className="inline-flex items-center justify-end gap-1.5 sm:gap-2">
                              <Link
                                to={`/admin/notifications/${n.id}`}
                                className="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition-colors inline-flex"
                                title="Inspect Notification"
                              >
                                <Eye className="w-3.5 h-3.5" />
                              </Link>

                              {!n.is_read && (
                                <button
                                  type="button"
                                  onClick={() => handleMarkRead(n.id)}
                                  disabled={actionLoading}
                                  className="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors"
                                  title="Mark Read"
                                >
                                  <CheckCircle2 className="w-3.5 h-3.5" />
                                </button>
                              )}
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

export default NotificationsListPage;
