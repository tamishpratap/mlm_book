import { useState, useEffect, useCallback } from 'react';
import { useLocation, useNavigate, Link } from 'react-router-dom';
import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column';
import { Eye, UserX, CheckCircle2, Trash2, UserPlus, XCircle } from 'lucide-react';
import { Button } from 'primereact/button';
import { PageHeader } from '../../components/common/PageHeader';
import { StatusBadge } from '../../components/common/StatusBadge';
import { EmptyState } from '../../components/common/EmptyState';
import { ErrorState } from '../../components/common/ErrorState';
import { useToast } from '../../hooks/useToast';
import { confirmHelper } from '../../utils/confirmHelper';
import { membersApi, downloadBlobFromResponse } from '../../api';

// Subcomponents
import { MemberStatsCards } from './components/MemberStatsCards';
import { MemberFilterBar } from './components/MemberFilterBar';
import { MemberBulkActionsBar } from './components/MemberBulkActionsBar';

export function MembersListPage({ defaultMode }) {
  const location = useLocation();
  const navigate = useNavigate();
  const { showSuccess, showError, showInfo } = useToast();

  // Determine mode from pathname or prop
  const mode = defaultMode || (
    location.pathname.includes('/pending')
      ? 'pending'
      : location.pathname.includes('/blocked')
      ? 'blocked'
      : 'active'
  );

  const [members, setMembers] = useState([]);
  const [selectedMembers, setSelectedMembers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [exporting, setExporting] = useState(false);
  const [actionLoading, setActionLoading] = useState(false);

  // Pagination & Counts
  const [totalRecords, setTotalRecords] = useState(0);
  const [currentPage, setCurrentPage] = useState(1);
  const [pageSize, setPageSize] = useState(15);
  const [metrics, setMetrics] = useState({
    totalCount: 0,
    activeCount: 0,
    pendingCount: 0,
    blockedCount: 0,
  });
  const [countries, setCountries] = useState([]);

  // Filter State
  const [filters, setFilters] = useState(() => {
    const params = new URLSearchParams(location.search);
    return {
      q: params.get('q') || '',
      status: params.get('status') || '',
      country: params.get('country') || '',
      date_from: params.get('date_from') || '',
      date_to: params.get('date_to') || '',
    };
  });

  const getPageMeta = () => {
    switch (mode) {
      case 'pending':
        return {
          title: 'WhatsApp Verification Queue',
          subtitle: 'Review and approve members who sent "Hi" on WhatsApp from their registered phone number.',
          breadcrumbs: [{ label: 'Members', to: '/admin/members' }, { label: 'WhatsApp Verification Queue' }],
        };
      case 'blocked':
        return {
          title: 'Blocked Members Directory',
          subtitle: 'Manage suspended or restricted platform user accounts.',
          breadcrumbs: [{ label: 'Members', to: '/admin/members' }, { label: 'Blocked Accounts' }],
        };
      case 'active':
      default:
        return {
          title: 'Verified Members Directory',
          subtitle: 'Manage, inspect, and monitor active verified platform members.',
          breadcrumbs: [{ label: 'Members' }, { label: 'Verified Members' }],
        };
    }
  };

  const pageMeta = getPageMeta();

  // Load Data
  const loadMembers = useCallback(async () => {
    setLoading(true);
    setError(null);

    const searchParams = new URLSearchParams(location.search);
    const queryParams = {
      page: currentPage,
      per_page: pageSize,
      q: searchParams.get('q') || undefined,
      status: searchParams.get('status') || undefined,
      country: searchParams.get('country') || undefined,
      date_from: searchParams.get('date_from') || undefined,
      date_to: searchParams.get('date_to') || undefined,
    };

    try {
      let response;
      if (mode === 'pending') {
        response = await membersApi.getPendingMembers(queryParams);
      } else if (mode === 'blocked') {
        response = await membersApi.getBlockedMembers(queryParams);
      } else {
        response = await membersApi.getActiveMembers(queryParams);
      }

      const dataList = Array.isArray(response?.members?.data)
        ? response.members.data
        : Array.isArray(response?.data?.data)
        ? response.data.data
        : Array.isArray(response?.data)
        ? response.data
        : Array.isArray(response?.members)
        ? response.members
        : Array.isArray(response)
        ? response
        : [];
      const total = response?.members?.total || response?.total || response?.totalCount || response?.meta?.total || dataList.length;

      setMembers(dataList);
      setTotalRecords(total);

      if (response?.metrics) {
        setMetrics((prev) => ({ ...prev, ...response.metrics }));
      } else if (response?.totalCount !== undefined) {
        setMetrics({
          totalCount: response.totalCount || 0,
          activeCount: response.activeCount || 0,
          pendingCount: response.pendingCount || 0,
          blockedCount: response.blockedCount || 0,
        });
      }

      if (response?.countries) {
        setCountries(response.countries);
      }
    } catch (err) {
      setError(err.message || 'Failed to load member records.');
    } finally {
      setLoading(false);
    }
  }, [mode, currentPage, pageSize, location.search]);

  useEffect(() => {
    loadMembers();
  }, [loadMembers]);

  // Keep local filter inputs synced if URL changes
  useEffect(() => {
    const params = new URLSearchParams(location.search);
    setFilters({
      q: params.get('q') || '',
      status: params.get('status') || '',
      country: params.get('country') || '',
      date_from: params.get('date_from') || '',
      date_to: params.get('date_to') || '',
    });
  }, [location.search]);

  // Sync URL search params on filter apply
  const handleApplyFilters = () => {
    const searchParams = new URLSearchParams();
    if (filters.q) searchParams.set('q', filters.q);
    if (filters.status) searchParams.set('status', filters.status);
    if (filters.country) searchParams.set('country', filters.country);
    if (filters.date_from) searchParams.set('date_from', filters.date_from);
    if (filters.date_to) searchParams.set('date_to', filters.date_to);

    navigate({ search: searchParams.toString() }, { replace: true });
    setCurrentPage(1);
  };

  const handleResetFilters = () => {
    setFilters({ q: '', status: '', country: '', date_from: '', date_to: '' });
    navigate({ search: '' }, { replace: true });
    setCurrentPage(1);
  };

  const handleFilterChange = (key, value) => {
    setFilters((prev) => ({ ...prev, [key]: value }));
  };

  // Export CSV
  const handleExportCsv = async () => {
    if (exporting) return;
    setExporting(true);
    try {
      const exportParams = {
        type: mode,
        q: filters.q || undefined,
        status: filters.status || undefined,
        country: filters.country || undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
      };
      const blob = await membersApi.exportCsv(exportParams);
      const fallbackFilename = `${mode}_members_export.csv`;
      await downloadBlobFromResponse(blob, fallbackFilename);
      showSuccess(`Exported ${mode} members CSV file successfully.`);
    } catch (err) {
      showError(err.message || 'Failed to export CSV.');
    } finally {
      setExporting(false);
    }
  };

  const handleApproveMember = (member) => {
    confirmHelper.confirm({
      header: 'Approve WhatsApp Verification',
      message: `Approve WhatsApp verification for ${member.name} (${member.user_id})? Please verify that you received their "Hi" message from registered number ${member.phone}.`,
      icon: 'pi pi-check-circle text-emerald-500',
      acceptLabel: 'Approve Verification',
      rejectLabel: 'Cancel',
      acceptClassName: 'p-button-success text-xs',
      onAccept: async () => {
        setActionLoading(true);
        try {
          const res = await membersApi.approveMember(member.id);
          showSuccess(res?.message || `Member ${member.name} verified successfully.`);
          loadMembers();
        } catch (err) {
          showError(err.message || 'Failed to approve member verification.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  const handleRejectMember = (member) => {
    confirmHelper.confirm({
      header: 'Dismiss Verification Request',
      message: `Dismiss WhatsApp verification request for ${member.name} (${member.user_id})? The member will remain unverified.`,
      icon: 'pi pi-exclamation-triangle text-amber-500',
      acceptLabel: 'Dismiss Request',
      rejectLabel: 'Cancel',
      acceptClassName: 'p-button-warning text-xs',
      onAccept: async () => {
        setActionLoading(true);
        try {
          const res = await membersApi.rejectMember(member.id);
          showInfo(res?.message || `Verification request for ${member.name} dismissed.`);
          loadMembers();
        } catch (err) {
          showError(err.message || 'Failed to dismiss verification request.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  const handleBlockMember = (member) => {
    confirmHelper.confirmBlock({
      userName: `${member.name} (${member.user_id})`,
      onAccept: async () => {
        setActionLoading(true);
        try {
          await membersApi.blockMember(member.id);
          showInfo(`Member ${member.name} has been blocked.`);
          loadMembers();
        } catch (err) {
          showError(err.message || 'Failed to block member.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  const handleUnblockMember = (member) => {
    confirmHelper.confirmUnblock({
      userName: `${member.name} (${member.user_id})`,
      onAccept: async () => {
        setActionLoading(true);
        try {
          await membersApi.unblockMember(member.id);
          showSuccess(`Member ${member.name} has been unblocked.`);
          loadMembers();
        } catch (err) {
          showError(err.message || 'Failed to unblock member.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  const handleDeleteMember = (member) => {
    confirmHelper.confirmDelete({
      itemName: `${member.name} (${member.user_id})`,
      onAccept: async () => {
        setActionLoading(true);
        try {
          await membersApi.deleteMember(member.id);
          showSuccess(`Member ${member.name} deleted successfully.`);
          loadMembers();
        } catch (err) {
          showError(err.message || 'Failed to delete member.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  // Bulk Actions
  const handleApplyBulkAction = async (action, ids) => {
    setActionLoading(true);
    try {
      await membersApi.bulkAction(action, ids);
      showSuccess(`Bulk ${action} applied to ${ids.length} member(s).`);
      setSelectedMembers([]);
      loadMembers();
    } catch (err) {
      showError(err.message || 'Bulk action failed.');
    } finally {
      setActionLoading(false);
    }
  };

  // Column Templates
  const avatarBodyTemplate = (rowData) => {
    const initial = (rowData.name || 'M').charAt(0).toUpperCase();
    return (
      <div className="flex items-center space-x-2">
        {rowData.avatar_url || rowData.profile_photo ? (
          <img
            src={rowData.avatar_url || rowData.profile_photo}
            alt={rowData.name}
            className="w-9 h-9 rounded-full object-cover ring-1 ring-slate-200"
            onError={(e) => {
              e.target.style.display = 'none';
            }}
          />
        ) : (
          <div className="w-9 h-9 rounded-full bg-blue-100 text-blue-700 font-bold text-xs flex items-center justify-center">
            {initial}
          </div>
        )}
      </div>
    );
  };

  const userIdBodyTemplate = (rowData) => (
    <code className="text-xs font-mono font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-sm border border-blue-100">
      {rowData.user_id}
    </code>
  );

  const nameBodyTemplate = (rowData) => (
    <div>
      <span className="font-bold text-slate-800 block text-xs truncate max-w-[160px]">
        {rowData.name}
      </span>
      <span className="text-[11px] text-slate-400 block truncate max-w-[180px]">
        {rowData.email}
      </span>
    </div>
  );

  const locationBodyTemplate = (rowData) => {
    const loc = [rowData.city, rowData.country].filter(Boolean).join(', ');
    return (
      <span className="text-xs text-slate-600">
        {loc || 'N/A'}
      </span>
    );
  };

  const directReferralsBodyTemplate = (rowData) => {
    const count = rowData.direct_referral_count ?? 0;
    return (
      <div className="flex items-center gap-1.5">
        <span className="inline-flex items-center justify-center font-bold text-xs px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-100">
          {count}
        </span>
        {rowData.introducer_id && (
          <span className="text-[10px] text-slate-400 font-mono" title={`Introduced by @${rowData.introducer_id}`}>
            (via @{rowData.introducer_id})
          </span>
        )}
      </div>
    );
  };

  const statusBodyTemplate = (rowData) => {
    const isVerified = Boolean(rowData.mobile_verified_at || rowData.is_verified);
    const isPending = Boolean(!isVerified && rowData.mobile_verification_requested_at);
    const isBlocked = Boolean(rowData.blocked_at || rowData.is_blocked || rowData.status === 'blocked');
    return (
      <div className="flex items-center space-x-1.5 flex-wrap gap-y-1">
        <StatusBadge
          status={isVerified ? 'verified' : isPending ? 'pending' : 'unverified'}
          label={isVerified ? 'VERIFIED MEMBER' : isPending ? 'PENDING VERIFICATION' : 'UNVERIFIED MEMBER'}
        />
        {isBlocked && (
          <StatusBadge status="blocked" label="BLOCKED MEMBER" />
        )}
        {rowData.is_online && (
          <span className="bg-emerald-500 text-white text-[8px] font-bold px-1.5 py-0.2 rounded-full uppercase">
            Online
          </span>
        )}
      </div>
    );
  };

  const actionsBodyTemplate = (rowData) => {
    const isBlocked = Boolean(rowData.blocked_at || rowData.is_blocked || mode === 'blocked');
    const isPending = Boolean(!rowData.mobile_verified_at && rowData.mobile_verification_requested_at);

    return (
      <div className="inline-flex items-center justify-end gap-1.5 sm:gap-2">
        {mode === 'pending' && isPending && (
          <>
            <button
              type="button"
              onClick={() => handleApproveMember(rowData)}
              disabled={actionLoading}
              className="p-1.5 rounded-md text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 transition-colors cursor-pointer"
              title="Approve WhatsApp Verification"
            >
              <CheckCircle2 className="w-4 h-4" />
            </button>
            <button
              type="button"
              onClick={() => handleRejectMember(rowData)}
              disabled={actionLoading}
              className="p-1.5 rounded-md text-amber-600 hover:text-amber-700 hover:bg-amber-50 transition-colors cursor-pointer"
              title="Dismiss Verification Request"
            >
              <XCircle className="w-4 h-4" />
            </button>
          </>
        )}

        <Link
          to={`/admin/members/${rowData.id}`}
          className="p-1.5 rounded-md text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition-colors"
          title="Inspect Profile"
        >
          <Eye className="w-4 h-4" />
        </Link>

        {isBlocked ? (
          <button
            type="button"
            onClick={() => handleUnblockMember(rowData)}
            disabled={actionLoading}
            className="p-1.5 rounded-md text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 transition-colors cursor-pointer"
            title="Unblock Member"
          >
            <CheckCircle2 className="w-4 h-4" />
          </button>
        ) : (
          <button
            type="button"
            onClick={() => handleBlockMember(rowData)}
            disabled={actionLoading}
            className="p-1.5 rounded-md text-slate-500 hover:text-amber-600 hover:bg-amber-50 transition-colors cursor-pointer"
            title="Block Member"
            aria-label="Block Member"
          >
            <UserX className="w-4 h-4" />
          </button>
        )}

        <button
          type="button"
          onClick={() => handleDeleteMember(rowData)}
          disabled={actionLoading}
          className="p-1.5 rounded-md text-slate-500 hover:text-red-600 hover:bg-red-50 transition-colors cursor-pointer"
          title="Delete Member"
        >
          <Trash2 className="w-4 h-4" />
        </button>
      </div>
    );
  };

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <PageHeader
        title={pageMeta.title}
        subtitle={pageMeta.subtitle}
        breadcrumbs={pageMeta.breadcrumbs}
        actions={
          <div className="flex flex-wrap items-center gap-2">
            <Button
              label={exporting ? 'Exporting...' : 'Export CSV'}
              icon="pi pi-download"
              size="small"
              onClick={handleExportCsv}
              disabled={exporting}
              loading={exporting}
              className="p-button-outlined p-button-primary text-xs"
            />
          </div>
        }
      />

      {/* Metric Stat Cards */}
      <MemberStatsCards
        totalCount={metrics.totalCount}
        activeCount={metrics.activeCount}
        pendingCount={metrics.pendingCount}
        blockedCount={metrics.blockedCount}
        currentMode={mode}
      />

      {/* Main Table Card */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-2xs p-5">
        {/* Filter Bar */}
        <MemberFilterBar
          filters={filters}
          countries={countries}
          onFilterChange={handleFilterChange}
          onApplyFilters={handleApplyFilters}
          onResetFilters={handleResetFilters}
          loading={loading}
          showStatusFilter={mode === 'active'}
        />

        {/* Bulk Actions */}
        <MemberBulkActionsBar
          selectedIds={selectedMembers.map((m) => m.id)}
          onApplyBulkAction={handleApplyBulkAction}
          loading={actionLoading}
          currentMode={mode}
        />

        {/* Error State */}
        {error && (
          <ErrorState
            title="Failed to Load Members"
            message={error}
            onRetry={loadMembers}
          />
        )}

        {/* PrimeReact DataTable */}
        {!error && (
          <div className="overflow-x-auto">
            <DataTable
              value={members}
              selection={selectedMembers}
              onSelectionChange={(e) => setSelectedMembers(e.value)}
              dataKey="id"
              loading={loading}
              paginator
              rows={pageSize}
              totalRecords={totalRecords}
              lazy
              first={(currentPage - 1) * pageSize}
              onPage={(e) => {
                setCurrentPage(e.page + 1);
                setPageSize(e.rows);
              }}
              rowsPerPageOptions={[10, 15, 25, 50]}
              emptyMessage={
                <EmptyState
                  icon={UserPlus}
                  title="No Members Found"
                  description="No platform members match your search or filter criteria."
                />
              }
              className="p-datatable-sm"
              rowHover
            >
              <Column selectionMode="multiple" headerStyle={{ width: '3rem' }} />
              <Column header="Avatar" body={avatarBodyTemplate} style={{ width: '4rem' }} />
              <Column header="Member ID" body={userIdBodyTemplate} sortable field="user_id" />
              <Column header="Name & Email" body={nameBodyTemplate} sortable field="name" />
              <Column header="Direct Referrals" field="direct_referral_count" body={directReferralsBodyTemplate} sortable style={{ minWidth: '8.5rem' }} />
              <Column
                field="phone"
                header={mode === 'pending' ? 'Registered WhatsApp' : 'Phone'}
                body={(r) => (
                  <span className="font-mono text-xs font-semibold text-slate-800 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                    {r.phone || 'N/A'}
                  </span>
                )}
              />
              {mode === 'pending' && (
                <Column
                  header="Requested At"
                  body={(r) => (
                    <span className="text-xs text-slate-600 font-medium" title={r.mobile_verification_requested_at}>
                      {r.mobile_verification_requested_at
                        ? new Date(r.mobile_verification_requested_at).toLocaleString()
                        : 'N/A'}
                    </span>
                  )}
                  sortable
                  field="mobile_verification_requested_at"
                />
              )}
              <Column header="Location" body={locationBodyTemplate} />
              <Column header="Status" body={statusBodyTemplate} />
              {mode !== 'pending' && (
                <Column
                  field="created_at_human"
                  header="Joined Date"
                  body={(r) => r.created_at_formatted || r.created_at_human || 'Recently'}
                />
              )}
              <Column
                header="Actions"
                body={actionsBodyTemplate}
                headerStyle={{ textAlign: 'right' }}
                style={{ width: mode === 'pending' ? '11rem' : '8rem', textAlign: 'right' }}
              />
            </DataTable>
          </div>
        )}
      </div>
    </div>
  );
}

export default MembersListPage;
