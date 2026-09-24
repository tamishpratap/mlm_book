import { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import {
  Briefcase,
  Download,
  Trash2,
  CheckCircle,
  XCircle,
  ShieldCheck,
  Users,
  MessageSquare,
  FileText,
  Building,
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
import { businessPagesApi, downloadBlobFromResponse } from '../../api';

export function BusinessPagesListPage() {
  const navigate = useNavigate();
  const { showSuccess, showError } = useToast();

  const handleViewPage = (pageItem) => {
    const identifier = pageItem?.id ?? pageItem?.slug ?? pageItem?.page_id;
    if (!identifier) {
      showError('Unable to open business page details: Missing business page identifier.');
      return;
    }
    navigate(`/admin/business-pages/${identifier}`);
  };

  const [pages, setPages] = useState([]);
  const [stats, setStats] = useState({
    totalCount: 0,
    activeCount: 0,
    verifiedCount: 0,
    pendingVerificationCount: 0,
  });

  const [search, setSearch] = useState('');
  const [verificationStatus, setVerificationStatus] = useState('');
  const [status, setStatus] = useState('');
  const [pagination, setPagination] = useState({ page: 1, perPage: 15, total: 0 });
  const [selectedIds, setSelectedIds] = useState([]);
  const [loading, setLoading] = useState(true);
  const [exporting, setExporting] = useState(false);
  const [actionLoading, setActionLoading] = useState(false);
  const [error, setError] = useState(null);

  const fetchPages = (page = 1) => {
    setLoading(true);
    setError(null);
    const params = {
      page,
      q: search || undefined,
      verification_status: verificationStatus || undefined,
      status: status || undefined,
    };
    businessPagesApi
      .getPages(params)
      .then((res) => {
        const items = res?.businessPages?.data || res?.data || (Array.isArray(res) ? res : []);
        const paginator = res?.businessPages || {};

        setPages(items);
        setStats({
          totalCount: res?.totalCount ?? paginator?.total ?? items.length,
          activeCount: res?.activeCount ?? 0,
          verifiedCount: res?.verifiedCount ?? 0,
          pendingVerificationCount: res?.pendingVerificationCount ?? 0,
        });
        setPagination({
          page: paginator?.current_page || page,
          perPage: paginator?.per_page || 15,
          total: paginator?.total || items.length,
        });
        setSelectedIds([]);
      })
      .catch((err) => {
        setError(err.message || 'Failed to load business pages.');
      })
      .finally(() => {
        setLoading(false);
      });
  };

  useEffect(() => {
    let isMounted = true;
    businessPagesApi
      .getPages({ page: 1 })
      .then((res) => {
        if (!isMounted) return;
        const items = res?.businessPages?.data || res?.data || (Array.isArray(res) ? res : []);
        const paginator = res?.businessPages || {};

        setPages(items);
        setStats({
          totalCount: res?.totalCount ?? paginator?.total ?? items.length,
          activeCount: res?.activeCount ?? 0,
          verifiedCount: res?.verifiedCount ?? 0,
          pendingVerificationCount: res?.pendingVerificationCount ?? 0,
        });
        setPagination({
          page: paginator?.current_page || 1,
          perPage: paginator?.per_page || 15,
          total: paginator?.total || items.length,
        });
      })
      .catch((err) => {
        if (!isMounted) return;
        setError(err.message || 'Failed to load business pages.');
      })
      .finally(() => {
        if (isMounted) setLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, []);

  const handleStatusChange = async (pageItem, newStatus) => {
    setActionLoading(true);
    try {
      await businessPagesApi.updateStatus(pageItem.id, newStatus);
      showSuccess(`Business page status changed to ${newStatus}.`);
      fetchPages(pagination.page);
    } catch (err) {
      showError(err.message || 'Failed to update status.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleDelete = (pageItem) => {
    confirmHelper.confirm({
      header: 'Delete Business Page',
      message: `Are you sure you want to permanently delete "${pageItem.page_name}"?`,
      icon: 'pi pi-trash text-red-500',
      acceptClassName: 'p-button-danger text-xs',
      onAccept: async () => {
        setActionLoading(true);
        try {
          await businessPagesApi.deletePage(pageItem.id);
          showSuccess('Business page deleted successfully.');
          fetchPages(pagination.page);
        } catch (err) {
          showError(err.message || 'Failed to delete business page.');
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
      message: `Are you sure you want to apply "${action}" to ${selectedIds.length} business page(s)?`,
      icon: 'pi pi-exclamation-triangle text-amber-500',
      acceptClassName: action === 'delete' ? 'p-button-danger text-xs' : 'p-button-primary text-xs',
      onAccept: async () => {
        setActionLoading(true);
        try {
          await businessPagesApi.bulkAction(action, selectedIds);
          showSuccess(`Bulk action applied.`);
          fetchPages(pagination.page);
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
        verification_status: verificationStatus || undefined,
        status: status || undefined,
      };
      const blob = await businessPagesApi.exportCsv(params);
      await downloadBlobFromResponse(blob, 'business_pages_export.csv');
      showSuccess('Business pages CSV downloaded successfully.');
    } catch (err) {
      showError(err.message || 'Failed to export CSV.');
    } finally {
      setExporting(false);
    }
  };

  const toggleSelectAll = (checked) => {
    if (checked) {
      setSelectedIds(pages.map((p) => p.id));
    } else {
      setSelectedIds([]);
    }
  };

  const toggleSelectOne = (id) => {
    setSelectedIds((prev) =>
      prev.includes(id) ? prev.filter((i) => i !== id) : [...prev, id]
    );
  };

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <PageHeader
        title="Business Pages Directory"
        subtitle="Manage verified merchant profiles, category assignments, and brand pages."
        breadcrumbs={[{ label: 'Business Pages' }]}
        actions={
          <div className="flex items-center space-x-2">
            <Link to="/admin/business-pages/categories">
              <Button
                label="Manage Categories"
                icon={<Building className="w-3.5 h-3.5 mr-1.5" />}
                className="p-button-outlined p-button-primary text-xs"
              />
            </Link>
            <Button
              label={exporting ? 'Exporting...' : 'Export CSV'}
              icon={exporting ? 'pi pi-spin pi-spinner mr-1.5' : <Download className="w-3.5 h-3.5 mr-1.5" />}
              onClick={handleExport}
              disabled={exporting}
              loading={exporting}
              className="p-button-outlined p-button-secondary text-xs"
            />
          </div>
        }
      />

      {/* Stats Cards */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500">Total Pages</span>
            <div className="p-2 rounded-lg bg-blue-50 text-blue-600">
              <Briefcase className="w-4 h-4" />
            </div>
          </div>
          <p className="text-2xl font-bold text-slate-800 mt-2">{stats.totalCount}</p>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500">Active Brands</span>
            <div className="p-2 rounded-lg bg-emerald-50 text-emerald-600">
              <CheckCircle className="w-4 h-4" />
            </div>
          </div>
          <p className="text-2xl font-bold text-emerald-600 mt-2">{stats.activeCount}</p>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500">Verified Pages</span>
            <div className="p-2 rounded-lg bg-indigo-50 text-indigo-600">
              <ShieldCheck className="w-4 h-4" />
            </div>
          </div>
          <p className="text-2xl font-bold text-indigo-600 mt-2">{stats.verifiedCount}</p>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500">Pending Verification</span>
            <div className="p-2 rounded-lg bg-amber-50 text-amber-600">
              <ShieldCheck className="w-4 h-4" />
            </div>
          </div>
          <p className="text-2xl font-bold text-amber-600 mt-2">{stats.pendingVerificationCount}</p>
        </div>
      </div>

      {/* Filter Bar */}
      <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs flex flex-wrap items-center justify-between gap-3">
        <div className="flex items-center space-x-2 flex-1 max-w-md">
          <AdminSearchInput
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            onClear={() => setSearch('')}
            placeholder="Search by brand name, page ID, username..."
          />
        </div>

        <div className="flex items-center space-x-2 flex-wrap gap-2">
          <Dropdown
            value={verificationStatus}
            options={[
              { label: 'All Verifications', value: '' },
              { label: 'Verified', value: 'verified' },
              { label: 'Unverified', value: 'unverified' },
              { label: 'Pending Review', value: 'pending' },
            ]}
            onChange={(e) => setVerificationStatus(e.value)}
            className="text-xs w-40"
            placeholder="Verification"
          />

          <Dropdown
            value={status}
            options={[
              { label: 'All Statuses', value: '' },
              { label: 'Active', value: 'active' },
              { label: 'Inactive / Suspended', value: 'inactive' },
            ]}
            onChange={(e) => setStatus(e.value)}
            className="text-xs w-36"
            placeholder="Status Filter"
          />

          <Button
            icon="pi pi-refresh"
            onClick={() => fetchPages(1)}
            className="p-button-outlined p-button-secondary text-xs"
            tooltip="Refresh"
          />
        </div>
      </div>

      {/* Bulk Action Bar */}
      {selectedIds.length > 0 && (
        <div className="p-3 bg-blue-50 border border-blue-200 rounded-xl flex items-center justify-between flex-wrap gap-2">
          <span className="text-xs font-semibold text-blue-900">
            {selectedIds.length} page(s) selected
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
            <LoadingSpinner message="Loading business pages..." />
          </div>
        ) : error ? (
          <div className="p-8">
            <ErrorState title="Failed to Load Pages" message={error} onRetry={() => fetchPages(1)} />
          </div>
        ) : pages.length === 0 ? (
          <div className="p-8">
            <EmptyState
              title="No Business Pages Found"
              message="No business pages match your current filter parameters."
              icon={Briefcase}
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
                        checked={pages.length > 0 && selectedIds.length === pages.length}
                        onChange={(e) => toggleSelectAll(e.checked)}
                      />
                    </th>
                    <th className="p-3.5">Brand Profile</th>
                    <th className="p-3.5">Category</th>
                    <th className="p-3.5">Owner / Admin</th>
                    <th className="p-3.5 text-center">Audience</th>
                    <th className="p-3.5">Status</th>
                    <th className="p-3.5 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {pages.map((pageItem) => (
                    <tr key={pageItem.id} className="hover:bg-slate-50/80 transition-colors">
                      <td className="p-3.5 text-center">
                        <Checkbox
                          checked={selectedIds.includes(pageItem.id)}
                          onChange={() => toggleSelectOne(pageItem.id)}
                        />
                      </td>
                      <td className="p-3.5">
                        <div className="font-semibold text-slate-900 flex items-center">
                          {pageItem.page_name}
                          {pageItem.is_verified && (
                            <ShieldCheck className="w-3.5 h-3.5 text-blue-600 ml-1 inline shrink-0" />
                          )}
                        </div>
                        <div className="text-[10px] text-slate-400 font-mono">
                          @{pageItem.page_username || pageItem.page_id}
                        </div>
                      </td>
                      <td className="p-3.5">
                        <span className="inline-block text-[10px] font-semibold bg-slate-100 text-slate-700 px-2 py-0.5 rounded-sm border border-slate-200">
                          {pageItem.category || 'General'}
                        </span>
                      </td>
                      <td className="p-3.5">
                        <div className="font-semibold text-slate-800">
                          {pageItem.owner?.name || 'Unknown Owner'}
                        </div>
                        <div className="text-[10px] text-slate-400">
                          {pageItem.owner?.email || 'N/A'}
                        </div>
                      </td>
                      <td className="p-3.5 text-center">
                        <div className="flex items-center justify-center space-x-2 text-[11px] text-slate-500">
                          <span className="flex items-center" title="Followers">
                            <Users className="w-3 h-3 text-blue-500 mr-0.5" /> {pageItem.accepted_followers_count ?? 0}
                          </span>
                          <span className="flex items-center" title="Posts">
                            <FileText className="w-3 h-3 text-emerald-500 mr-0.5" /> {pageItem.posts_count ?? 0}
                          </span>
                          <span className="flex items-center" title="Reviews">
                            <MessageSquare className="w-3 h-3 text-purple-500 mr-0.5" /> {pageItem.reviews_count ?? 0}
                          </span>
                        </div>
                      </td>
                      <td className="p-3.5">
                        <StatusBadge status={pageItem.status || 'active'} />
                      </td>
                      <td className="p-3.5 text-right whitespace-nowrap">
                        <div className="inline-flex items-center justify-end gap-1.5 sm:gap-2">
                          <Button
                            icon={<Eye className="w-3.5 h-3.5 text-blue-600" />}
                            onClick={() => handleViewPage(pageItem)}
                            className="p-button-text p-button-secondary p-button-sm p-0 w-7 h-7"
                            tooltip="View business page details"
                            aria-label="View business page details"
                          />
                          <Button
                            icon={pageItem.status === 'active' ? <XCircle className="w-3.5 h-3.5 text-amber-500" /> : <CheckCircle className="w-3.5 h-3.5 text-emerald-600" />}
                            onClick={() => handleStatusChange(pageItem, pageItem.status === 'active' ? 'inactive' : 'active')}
                            className="p-button-text p-button-secondary p-button-sm p-0 w-7 h-7"
                            tooltip={pageItem.status === 'active' ? 'Suspend page' : 'Activate page'}
                          />
                          <Button
                            icon={<Trash2 className="w-3.5 h-3.5 text-red-500" />}
                            onClick={() => handleDelete(pageItem)}
                            className="p-button-text p-button-danger p-button-sm p-0 w-7 h-7"
                            tooltip="Delete page"
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
              <span>Showing {pages.length} of {pagination.total} entries</span>
              <Paginator
                first={(pagination.page - 1) * pagination.perPage}
                rows={pagination.perPage}
                totalRecords={pagination.total}
                onPageChange={(e) => fetchPages(e.page + 1)}
                className="p-paginator-sm"
              />
            </div>
          </>
        )}
      </div>
    </div>
  );
}

export default BusinessPagesListPage;
