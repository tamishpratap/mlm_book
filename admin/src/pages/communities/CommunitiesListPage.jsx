import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Globe,
  Download,
  Trash2,
  CheckCircle,
  XCircle,
  Lock,
  Eye,
} from 'lucide-react';
import { Button } from 'primereact/button';
import { Dropdown } from 'primereact/dropdown';
import { Checkbox } from 'primereact/checkbox';
import { AdminSearchInput } from '../../components/common/AdminSearchInput';
import { Paginator } from 'primereact/paginator';
import { PageHeader } from '../../components/common/PageHeader';
import { StatusBadge } from '../../components/common/StatusBadge';
import { EmptyState } from '../../components/common/EmptyState';
import { ErrorState } from '../../components/common/ErrorState';
import { LoadingSpinner } from '../../components/common/LoadingSpinner';
import { useToast } from '../../hooks/useToast';
import { confirmHelper } from '../../utils/confirmHelper';
import { communitiesApi, downloadBlobFromResponse } from '../../api';

export function CommunitiesListPage() {
  const navigate = useNavigate();
  const { showSuccess, showError } = useToast();

  const handleViewCommunity = (comm) => {
    const identifier = comm?.id ?? comm?.community_id ?? comm?.slug;
    if (!identifier) {
      showError('Unable to open community details: Missing community identifier.');
      return;
    }
    navigate(`/admin/communities/${identifier}`);
  };

  const [communities, setCommunities] = useState([]);
  const [stats, setStats] = useState({
    totalCount: 0,
    activeCount: 0,
    publicCount: 0,
    privateCount: 0,
  });

  const [search, setSearch] = useState('');
  const [visibility, setVisibility] = useState('');
  const [status, setStatus] = useState('');
  const [pagination, setPagination] = useState({ page: 1, perPage: 15, total: 0 });
  const [selectedIds, setSelectedIds] = useState([]);
  const [loading, setLoading] = useState(true);
  const [exporting, setExporting] = useState(false);
  const [actionLoading, setActionLoading] = useState(false);
  const [error, setError] = useState(null);

  const fetchCommunities = (page = 1) => {
    setLoading(true);
    setError(null);
    const params = {
      page,
      q: search || undefined,
      visibility: visibility || undefined,
      status: status || undefined,
    };
    communitiesApi
      .getCommunities(params)
      .then((res) => {
        const items = res?.communities?.data || res?.data || (Array.isArray(res) ? res : []);
        const paginator = res?.communities || {};

        setCommunities(items);
        const total = res?.totalCount ?? paginator?.total ?? items.length;
        const priv = res?.privateCount ?? 0;
        setStats({
          totalCount: total,
          activeCount: res?.activeCount ?? 0,
          publicCount: res?.publicCount ?? Math.max(0, total - priv),
          privateCount: priv,
        });
        setPagination({
          page: paginator?.current_page || page,
          perPage: paginator?.per_page || 15,
          total: paginator?.total || items.length,
        });
        setSelectedIds([]);
      })
      .catch((err) => {
        setError(err.message || 'Failed to load communities.');
      })
      .finally(() => {
        setLoading(false);
      });
  };

  useEffect(() => {
    let isMounted = true;
    communitiesApi
      .getCommunities({ page: 1 })
      .then((res) => {
        if (!isMounted) return;
        const items = res?.communities?.data || res?.data || (Array.isArray(res) ? res : []);
        const paginator = res?.communities || {};

        setCommunities(items);
        const total = res?.totalCount ?? paginator?.total ?? items.length;
        const priv = res?.privateCount ?? 0;
        setStats({
          totalCount: total,
          activeCount: res?.activeCount ?? 0,
          publicCount: res?.publicCount ?? Math.max(0, total - priv),
          privateCount: priv,
        });
        setPagination({
          page: paginator?.current_page || 1,
          perPage: paginator?.per_page || 15,
          total: paginator?.total || items.length,
        });
      })
      .catch((err) => {
        if (!isMounted) return;
        setError(err.message || 'Failed to load communities.');
      })
      .finally(() => {
        if (isMounted) setLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, []);

  const handleStatusChange = async (community, newStatus) => {
    setActionLoading(true);
    try {
      await communitiesApi.updateStatus(community.id, newStatus);
      showSuccess(`Community status changed to ${newStatus}.`);
      fetchCommunities(pagination.page);
    } catch (err) {
      showError(err.message || 'Failed to update status.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleDelete = (community) => {
    confirmHelper.confirm({
      header: 'Delete Community',
      message: `Are you sure you want to permanently delete community "${community.name}"?`,
      icon: 'pi pi-trash text-red-500',
      acceptClassName: 'p-button-danger text-xs',
      onAccept: async () => {
        setActionLoading(true);
        try {
          await communitiesApi.deleteCommunity(community.id);
          showSuccess('Community deleted successfully.');
          fetchCommunities(pagination.page);
        } catch (err) {
          showError(err.message || 'Failed to delete community.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  const handleBulkAction = async (action) => {
    if (!selectedIds.length) return;
    confirmHelper.confirm({
      header: 'Bulk Action',
      message: `Are you sure you want to apply "${action}" to ${selectedIds.length} community/communities?`,
      icon: 'pi pi-exclamation-triangle text-amber-500',
      acceptClassName: action === 'delete' ? 'p-button-danger text-xs' : 'p-button-primary text-xs',
      onAccept: async () => {
        setActionLoading(true);
        try {
          await communitiesApi.bulkAction(action, selectedIds);
          showSuccess(`Bulk action applied.`);
          fetchCommunities(pagination.page);
        } catch (err) {
          showError(err.message || `Failed to execute bulk action.`);
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  const handleExport = async () => {
    if (exporting) return;
    setExporting(true);
    try {
      const params = {
        q: search || undefined,
        visibility: visibility || undefined,
        status: status || undefined,
      };
      const blob = await communitiesApi.exportCsv(params);
      await downloadBlobFromResponse(blob, 'communities_export.csv');
      showSuccess('Communities CSV downloaded successfully.');
    } catch (err) {
      showError(err.message || 'Failed to export CSV.');
    } finally {
      setExporting(false);
    }
  };

  const handleSelectAll = (e) => {
    const isChecked =
      typeof e === 'boolean'
        ? e
        : e?.target
        ? e.target.checked
        : selectedIds.length !== communities.length;

    if (isChecked) {
      setSelectedIds(communities.map((c) => c.id));
    } else {
      setSelectedIds([]);
    }
  };

  const handleSelectOne = (id) => {
    setSelectedIds((prev) =>
      prev.includes(id) ? prev.filter((item) => item !== id) : [...prev, id]
    );
  };

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <PageHeader
        title="Communities & Groups Directory"
        subtitle="Oversee user communities, membership requests, and group-level moderation."
        breadcrumbs={[{ label: 'Communities' }]}
        actions={
          <Button
            label={exporting ? 'Exporting...' : 'Export CSV'}
            icon={exporting ? 'pi pi-spin pi-spinner mr-1.5' : <Download className="w-3.5 h-3.5 mr-1.5" />}
            onClick={handleExport}
            disabled={exporting}
            loading={exporting}
            className="p-button-outlined p-button-secondary text-xs"
          />
        }
      />

      {/* Stats Cards */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500">Total Communities</span>
            <div className="p-2 rounded-lg bg-blue-50 text-blue-600">
              <Globe className="w-4 h-4" />
            </div>
          </div>
          <p className="text-2xl font-bold text-slate-800 mt-2">{stats.totalCount}</p>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500">Active Groups</span>
            <div className="p-2 rounded-lg bg-emerald-50 text-emerald-600">
              <CheckCircle className="w-4 h-4" />
            </div>
          </div>
          <p className="text-2xl font-bold text-emerald-600 mt-2">{stats.activeCount}</p>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500">Public Groups</span>
            <div className="p-2 rounded-lg bg-indigo-50 text-indigo-600">
              <Eye className="w-4 h-4" />
            </div>
          </div>
          <p className="text-2xl font-bold text-indigo-600 mt-2">{stats.publicCount}</p>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500">Private Groups</span>
            <div className="p-2 rounded-lg bg-purple-50 text-purple-600">
              <Lock className="w-4 h-4" />
            </div>
          </div>
          <p className="text-2xl font-bold text-purple-600 mt-2">{stats.privateCount}</p>
        </div>
      </div>

      {/* Filter Bar */}
      <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs flex flex-wrap items-center justify-between gap-3">
        <div className="flex items-center space-x-2 flex-1 max-w-md">
          <AdminSearchInput
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            onClear={() => setSearch('')}
            placeholder="Search community name, slug, owner..."
          />
        </div>

        <div className="flex flex-wrap items-center gap-2">
          <Dropdown
            value={visibility}
            options={[
              { label: 'All Visibility', value: '' },
              { label: 'Public', value: 'public' },
              { label: 'Private', value: 'private' },
            ]}
            onChange={(e) => setVisibility(e.value)}
            className="text-xs w-36"
            placeholder="Visibility"
          />

          <Dropdown
            value={status}
            options={[
              { label: 'All Statuses', value: '' },
              { label: 'Active', value: 'active' },
              { label: 'Suspended', value: 'suspended' },
            ]}
            onChange={(e) => setStatus(e.value)}
            className="text-xs w-36"
            placeholder="Status Filter"
          />

          <Button
            icon="pi pi-refresh"
            onClick={() => fetchCommunities(1)}
            className="p-button-outlined p-button-secondary text-xs"
            tooltip="Refresh"
          />
        </div>
      </div>

      {/* Bulk Action Bar */}
      {selectedIds.length > 0 && (
        <div className="p-3 bg-blue-50 border border-blue-200 rounded-xl flex items-center justify-between flex-wrap gap-2">
          <span className="text-xs font-semibold text-blue-900">
            {selectedIds.length} community/communities selected
          </span>
          <div className="flex flex-wrap items-center gap-2">
            <Button
              label="Activate"
              size="small"
              onClick={() => handleBulkAction('activate')}
              className="p-button-success text-xs py-1"
              disabled={actionLoading}
            />
            <Button
              label="Suspend"
              size="small"
              onClick={() => handleBulkAction('suspend')}
              className="p-button-warning text-xs py-1"
              disabled={actionLoading}
            />
            <Button
              label="Delete"
              size="small"
              onClick={() => handleBulkAction('delete')}
              className="p-button-danger text-xs py-1"
              disabled={actionLoading}
            />
          </div>
        </div>
      )}

      {/* Table */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        {loading ? (
          <div className="p-12 flex justify-center">
            <LoadingSpinner message="Loading communities..." />
          </div>
        ) : error ? (
          <div className="p-8">
            <ErrorState title="Failed to Load Communities" message={error} onRetry={() => fetchCommunities(1)} />
          </div>
        ) : communities.length === 0 ? (
          <div className="p-8">
            <EmptyState
              title="No Communities Found"
              description="No communities match your current filter parameters."
              icon={Globe}
            />
          </div>
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs text-slate-700">
                <thead className="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase border-b border-slate-200">
                  <tr>
                    <th className="p-3.5 w-10 text-center">
                      <Checkbox
                        checked={communities.length > 0 && selectedIds.length === communities.length}
                        onChange={(e) => handleSelectAll(e.checked)}
                      />
                    </th>
                    <th className="p-3.5">Community Group</th>
                    <th className="p-3.5">Creator</th>
                    <th className="p-3.5">Visibility</th>
                    <th className="p-3.5 text-center">Members</th>
                    <th className="p-3.5 text-center">Pending</th>
                    <th className="p-3.5">Status</th>
                    <th className="p-3.5 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {communities.map((comm) => (
                    <tr key={comm.id} className="hover:bg-slate-50 transition-colors">
                      <td className="p-3.5 text-center">
                        <Checkbox
                          checked={selectedIds.includes(comm.id)}
                          onChange={() => handleSelectOne(comm.id)}
                        />
                      </td>
                      <td className="p-3.5">
                        <div className="flex items-center space-x-3">
                          <div className="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center font-bold text-blue-600 overflow-hidden shrink-0 border border-slate-200">
                            {comm.avatar_url || comm.cover_url || comm.logo || comm.cover_photo ? (
                              <img
                                src={comm.avatar_url || comm.cover_url || comm.logo || comm.cover_photo}
                                alt={comm.name || 'Community'}
                                onError={(e) => {
                                  e.target.style.display = 'none';
                                }}
                                className="w-full h-full object-cover"
                              />
                            ) : (
                              (comm.name || 'C').charAt(0).toUpperCase()
                            )}
                          </div>
                          <div>
                            <p className="font-semibold text-slate-800 text-xs">{comm.name || 'Unnamed Community'}</p>
                            <p className="text-[11px] text-slate-400 font-mono">/{comm.slug || comm.id || ''}</p>
                          </div>
                        </div>
                      </td>
                      <td className="p-3.5">
                        <p className="text-slate-800 font-medium">{comm.owner?.name || comm.creator?.name || 'Platform Admin'}</p>
                        <p className="text-[11px] text-slate-400">{comm.owner?.user_id || comm.creator?.user_id || (comm.owner_id ? `ID: ${comm.owner_id}` : (comm.user_id ? `ID: ${comm.user_id}` : 'Platform Admin'))}</p>
                      </td>
                      <td className="p-3.5">
                        {comm.is_private || comm.visibility === 'private' ? (
                          <span className="inline-flex items-center text-[10px] font-semibold bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full border border-amber-200">
                            <Lock className="w-2.5 h-2.5 mr-1" /> Private
                          </span>
                        ) : (
                          <span className="inline-flex items-center text-[10px] font-semibold bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full border border-blue-200">
                            <Eye className="w-2.5 h-2.5 mr-1" /> Public
                          </span>
                        )}
                      </td>
                      <td className="p-3.5 text-center font-bold text-slate-800">
                        {comm.accepted_members_count ?? 0}
                      </td>
                      <td className="p-3.5 text-center">
                        {comm.pending_members_count > 0 ? (
                          <span className="inline-flex items-center text-[10px] font-bold bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full">
                            {comm.pending_members_count} Pending
                          </span>
                        ) : (
                          <span className="text-slate-400 text-[11px]">0</span>
                        )}
                      </td>
                      <td className="p-3.5">
                        <StatusBadge status={comm.status || 'active'} />
                      </td>
                      <td className="p-3.5 text-right whitespace-nowrap">
                        <div className="inline-flex items-center justify-end gap-1.5 sm:gap-2">
                          <Button
                            icon={<Eye className="w-3.5 h-3.5 text-blue-600" />}
                            onClick={() => handleViewCommunity(comm)}
                            className="p-button-text p-button-secondary p-button-sm p-0 w-7 h-7"
                            tooltip="View community details"
                            aria-label="View community details"
                          />
                          <Button
                            icon={comm.status === 'active' ? <XCircle className="w-3.5 h-3.5 text-amber-500" /> : <CheckCircle className="w-3.5 h-3.5 text-emerald-600" />}
                            onClick={() => handleStatusChange(comm, comm.status === 'active' ? 'suspended' : 'active')}
                            className="p-button-text p-button-secondary p-button-sm p-0 w-7 h-7"
                            tooltip={comm.status === 'active' ? 'Suspend group' : 'Activate group'}
                          />
                          <Button
                            icon={<Trash2 className="w-3.5 h-3.5 text-red-500" />}
                            onClick={() => handleDelete(comm)}
                            className="p-button-text p-button-danger p-button-sm p-0 w-7 h-7"
                            tooltip="Delete community"
                          />
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {/* Paginator */}
            <div className="p-3 border-t border-slate-200 flex justify-between items-center text-xs text-slate-500">
              <span>Showing {communities.length} of {pagination.total} entries</span>
              <Paginator
                first={(pagination.page - 1) * pagination.perPage}
                rows={pagination.perPage}
                totalRecords={pagination.total}
                onPageChange={(e) => fetchCommunities(e.page + 1)}
                className="p-paginator-sm"
              />
            </div>
          </>
        )}
      </div>
    </div>
  );
}

export default CommunitiesListPage;
