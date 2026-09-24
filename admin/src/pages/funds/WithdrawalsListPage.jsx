import { useState, useEffect, useRef } from 'react';
import {
  Wallet,
  Clock,
  CheckCircle2,
  XCircle,
  ShieldCheck,
  Search,
  RefreshCw,
  Copy,
  DollarSign,
  ArrowUpRight,
  User,
  Mail,
  Phone,
  Check,
  AlertTriangle,
  Eye,
  Info,
  ExternalLink,
  Percent,
} from 'lucide-react';
import { Button } from 'primereact/button';
import { InputText } from 'primereact/inputtext';
import { InputTextarea } from 'primereact/inputtextarea';
import { Dialog } from 'primereact/dialog';
import { Toast } from 'primereact/toast';
import { Tag } from 'primereact/tag';
import { PageHeader } from '../../components/common/PageHeader';
import { LoadingSpinner } from '../../components/common/LoadingSpinner';
import { ErrorState } from '../../components/common/ErrorState';
import { EmptyState } from '../../components/common/EmptyState';
import { MemberAvatar } from '../../components/common/MemberAvatar';
import { withdrawalsApi } from '../../api';

function WithdrawalStatusBadge({ status }) {
  const normalized = (status || '').toLowerCase().trim();

  if (normalized === 'approved') {
    return (
      <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
        <CheckCircle2 className="w-3.5 h-3.5" />
        Approved
      </span>
    );
  }

  if (normalized === 'verified') {
    return (
      <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
        <ShieldCheck className="w-3.5 h-3.5" />
        Verified
      </span>
    );
  }

  if (normalized === 'cancelled' || normalized === 'rejected') {
    return (
      <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
        <XCircle className="w-3.5 h-3.5" />
        Cancelled
      </span>
    );
  }

  return (
    <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
      <Clock className="w-3.5 h-3.5 animate-pulse" />
      Pending Review
    </span>
  );
}

export function WithdrawalsListPage() {
  const toast = useRef(null);

  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [withdrawals, setWithdrawals] = useState([]);
  const [metrics, setMetrics] = useState({});
  const [copiedId, setCopiedId] = useState(null);

  // Filters & State
  const [statusFilter, setStatusFilter] = useState('Pending'); // default to pending requests
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalCount, setTotalCount] = useState(0);

  // Selected item for details modal
  const [selectedWithdrawal, setSelectedWithdrawal] = useState(null);
  const [showDetailsModal, setShowDetailsModal] = useState(false);

  // Action: Accept Modal
  const [showAcceptModal, setShowAcceptModal] = useState(false);
  const [acceptTarget, setAcceptTarget] = useState(null);
  const [acceptTxnId, setAcceptTxnId] = useState('');
  const [acceptNotes, setAcceptNotes] = useState('');
  const [isAccepting, setIsAccepting] = useState(false);

  // Action: Verify Modal
  const [showVerifyModal, setShowVerifyModal] = useState(false);
  const [verifyTarget, setVerifyTarget] = useState(null);
  const [verifyTxnId, setVerifyTxnId] = useState('');
  const [verifyNotes, setVerifyNotes] = useState('');
  const [isVerifying, setIsVerifying] = useState(false);

  // Action: Reject Modal
  const [showRejectModal, setShowRejectModal] = useState(false);
  const [rejectTarget, setRejectTarget] = useState(null);
  const [rejectReason, setRejectReason] = useState('');
  const [isRejecting, setIsRejecting] = useState(false);

  const fetchWithdrawals = () => {
    setIsLoading(true);
    setError(null);

    const params = {
      page,
      status: statusFilter !== 'all' ? statusFilter : undefined,
      search: search.trim() || undefined,
    };

    withdrawalsApi
      .getWithdrawals(params)
      .then((res) => {
        const paginatedData = res.withdrawals || {};
        setWithdrawals(paginatedData.data || (Array.isArray(paginatedData) ? paginatedData : []));
        setTotalPages(paginatedData.last_page || 1);
        setTotalCount(paginatedData.total || 0);
        setMetrics(res.metrics || {});
      })
      .catch((err) => {
        setError(err.response?.data?.message || 'Failed to load withdrawal requests.');
      })
      .finally(() => {
        setIsLoading(false);
      });
  };

  useEffect(() => {
    fetchWithdrawals();
  }, [page, statusFilter]);

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    setPage(1);
    fetchWithdrawals();
  };

  const copyToClipboard = (text, label) => {
    if (!text) return;
    navigator.clipboard.writeText(text);
    setCopiedId(text);
    setTimeout(() => setCopiedId(null), 2000);
    toast.current?.show({
      severity: 'info',
      summary: 'Copied',
      detail: `${label} copied to clipboard`,
      life: 2500,
    });
  };

  // Open Handlers
  const handleOpenAccept = (w) => {
    setAcceptTarget(w);
    setAcceptTxnId('');
    setAcceptNotes('');
    setShowAcceptModal(true);
  };

  const handleOpenVerify = (w) => {
    setVerifyTarget(w);
    setVerifyTxnId(w.txnid || '');
    setVerifyNotes('');
    setShowVerifyModal(true);
  };

  const handleOpenReject = (w) => {
    setRejectTarget(w);
    setRejectReason('');
    setShowRejectModal(true);
  };

  const handleOpenDetails = (w) => {
    setSelectedWithdrawal(w);
    setShowDetailsModal(true);
  };

  // Submit Actions
  const handleConfirmAccept = () => {
    if (!acceptTarget) return;
    setIsAccepting(true);

    withdrawalsApi
      .acceptWithdrawal(acceptTarget.id, {
        txnid: acceptTxnId.trim() || undefined,
        admin_notes: acceptNotes.trim() || undefined,
      })
      .then((res) => {
        toast.current?.show({
          severity: 'success',
          summary: 'Accepted & Approved',
          detail: res.message || `Withdrawal #${acceptTarget.request_id} approved successfully!`,
          life: 4000,
        });
        setShowAcceptModal(false);
        setAcceptTarget(null);
        fetchWithdrawals();
      })
      .catch((err) => {
        toast.current?.show({
          severity: 'error',
          summary: 'Approval Failed',
          detail: err.response?.data?.message || 'Failed to accept withdrawal request.',
          life: 4000,
        });
      })
      .finally(() => {
        setIsAccepting(false);
      });
  };

  const handleConfirmVerify = () => {
    if (!verifyTarget) return;
    setIsVerifying(true);

    withdrawalsApi
      .verifyWithdrawal(verifyTarget.id, {
        txnid: verifyTxnId.trim() || undefined,
        admin_notes: verifyNotes.trim() || undefined,
      })
      .then((res) => {
        toast.current?.show({
          severity: 'success',
          summary: 'Request Verified',
          detail: res.message || `Withdrawal #${verifyTarget.request_id} verified successfully!`,
          life: 4000,
        });
        setShowVerifyModal(false);
        setVerifyTarget(null);
        fetchWithdrawals();
      })
      .catch((err) => {
        toast.current?.show({
          severity: 'error',
          summary: 'Verification Failed',
          detail: err.response?.data?.message || 'Failed to verify withdrawal request.',
          life: 4000,
        });
      })
      .finally(() => {
        setIsVerifying(false);
      });
  };

  const handleConfirmReject = () => {
    if (!rejectTarget) return;
    setIsRejecting(true);

    withdrawalsApi
      .rejectWithdrawal(rejectTarget.id, {
        rejection_reason: rejectReason.trim() || undefined,
        admin_notes: rejectReason.trim() || undefined,
      })
      .then((res) => {
        toast.current?.show({
          severity: 'warn',
          summary: 'Withdrawal Rejected',
          detail: res.message || `Withdrawal #${rejectTarget.request_id} rejected and refunded to wallet.`,
          life: 5000,
        });
        setShowRejectModal(false);
        setRejectTarget(null);
        fetchWithdrawals();
      })
      .catch((err) => {
        toast.current?.show({
          severity: 'error',
          summary: 'Rejection Failed',
          detail: err.response?.data?.message || 'Failed to reject withdrawal request.',
          life: 4000,
        });
      })
      .finally(() => {
        setIsRejecting(false);
      });
  };

  return (
    <div className="space-y-6">
      <Toast ref={toast} position="top-right" />

      {/* Page Header */}
      <PageHeader
        title="Withdrawal Requests"
        subtitle="Review, verify, and process member withdrawal requests with automated fee calculations and instant wallet reconciliation."
        breadcrumbs={[
          { label: 'Funds Management', to: '/admin/funds/deposits' },
          { label: 'Withdrawal Requests' },
        ]}
        actions={
          <div className="flex items-center gap-2">
            <Button
              type="button"
              icon={<RefreshCw className={`w-4 h-4 mr-1.5 ${isLoading ? 'animate-spin' : ''}`} />}
              label="Refresh Data"
              className="p-button-outlined p-button-sm border-slate-300 text-slate-700 hover:bg-slate-100"
              onClick={() => fetchWithdrawals()}
              disabled={isLoading}
            />
          </div>
        }
      />

      {/* Top Financial KPI Metrics */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* 1. Pending Queue */}
        <div
          onClick={() => setStatusFilter('Pending')}
          className={`cursor-pointer transition-all duration-200 p-5 rounded-2xl border ${statusFilter === 'Pending'
            ? 'bg-amber-50/70 border-amber-300 ring-2 ring-amber-400/40 shadow-sm'
            : 'bg-white border-slate-200/80 hover:border-amber-200 hover:bg-amber-50/30 shadow-xs'
            }`}
        >
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-amber-700 uppercase tracking-wider">
              Pending Queue
            </span>
            <div className="w-8 h-8 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600">
              <Clock className="w-4 h-4 animate-pulse" />
            </div>
          </div>
          <div className="mt-3 flex items-baseline gap-2">
            <span className="text-2xl font-bold text-slate-900">
              ${Number(metrics.pending_net || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
            </span>
            <span className="text-xs font-semibold text-amber-600">Net Payable</span>
          </div>
          <div className="mt-2 flex items-center justify-between text-xs text-slate-500">
            <span>{metrics.pending_count || 0} requests awaiting review</span>
            <span className="font-medium text-slate-600">
              Gross: ${Number(metrics.pending_gross || 0).toFixed(2)}
            </span>
          </div>
        </div>

        {/* 2. Approved Payouts */}
        <div
          onClick={() => setStatusFilter('Approved')}
          className={`cursor-pointer transition-all duration-200 p-5 rounded-2xl border ${statusFilter === 'Approved'
            ? 'bg-emerald-50/70 border-emerald-300 ring-2 ring-emerald-400/40 shadow-sm'
            : 'bg-white border-slate-200/80 hover:border-emerald-200 hover:bg-emerald-50/30 shadow-xs'
            }`}
        >
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-emerald-700 uppercase tracking-wider">
              Approved Payouts
            </span>
            <div className="w-8 h-8 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600">
              <CheckCircle2 className="w-4 h-4" />
            </div>
          </div>
          <div className="mt-3 flex items-baseline gap-2">
            <span className="text-2xl font-bold text-slate-900">
              ${Number(metrics.approved_net || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
            </span>
            <span className="text-xs font-semibold text-emerald-600">Disbursed</span>
          </div>
          <div className="mt-2 flex items-center justify-between text-xs text-slate-500">
            <span>{metrics.approved_count || 0} approved transfers</span>
            <span className="font-medium text-slate-600">
              Fee: ${Number((metrics.approved_gross || 0) - (metrics.approved_net || 0)).toFixed(2)}
            </span>
          </div>
        </div>

        {/* 3. Verified Transfers */}
        <div
          onClick={() => setStatusFilter('Verified')}
          className={`cursor-pointer transition-all duration-200 p-5 rounded-2xl border ${statusFilter === 'Verified'
            ? 'bg-blue-50/70 border-blue-300 ring-2 ring-blue-400/40 shadow-sm'
            : 'bg-white border-slate-200/80 hover:border-blue-200 hover:bg-blue-50/30 shadow-xs'
            }`}
        >
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-blue-700 uppercase tracking-wider">
              Verified Transfers
            </span>
            <div className="w-8 h-8 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600">
              <ShieldCheck className="w-4 h-4" />
            </div>
          </div>
          <div className="mt-3 flex items-baseline gap-2">
            <span className="text-2xl font-bold text-slate-900">
              ${Number(metrics.verified_net || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
            </span>
            <span className="text-xs font-semibold text-blue-600">Verified</span>
          </div>
          <div className="mt-2 flex items-center justify-between text-xs text-slate-500">
            <span>{metrics.verified_count || 0} verified requests</span>
            <span className="font-medium text-slate-600">Address Confirmed</span>
          </div>
        </div>

        {/* 4. Cancelled & Refunded */}
        <div
          onClick={() => setStatusFilter('Cancelled')}
          className={`cursor-pointer transition-all duration-200 p-5 rounded-2xl border ${statusFilter === 'Cancelled'
            ? 'bg-rose-50/70 border-rose-300 ring-2 ring-rose-400/40 shadow-sm'
            : 'bg-white border-slate-200/80 hover:border-rose-200 hover:bg-rose-50/30 shadow-xs'
            }`}
        >
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-rose-700 uppercase tracking-wider">
              Refunded to Users
            </span>
            <div className="w-8 h-8 rounded-xl bg-rose-100 flex items-center justify-center text-rose-600">
              <XCircle className="w-4 h-4" />
            </div>
          </div>
          <div className="mt-3 flex items-baseline gap-2">
            <span className="text-2xl font-bold text-slate-900">
              ${Number(metrics.cancelled_gross || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
            </span>
            <span className="text-xs font-semibold text-rose-600">Reconciled</span>
          </div>
          <div className="mt-2 flex items-center justify-between text-xs text-slate-500">
            <span>{metrics.cancelled_count || 0} cancelled requests</span>
            <span className="font-medium text-slate-600">Funds Restored</span>
          </div>
        </div>
      </div>

      {/* Filter Tabs & Search Controls */}
      <div className="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs space-y-4">
        <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          {/* Status Navigation Tabs */}
          <div className="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 scrollbar-none">
            {[
              { label: 'Pending Review', value: 'Pending', count: metrics.pending_count },
              { label: 'All Requests', value: 'all', count: metrics.total_count },
              { label: 'Approved', value: 'Approved', count: metrics.approved_count },
              { label: 'Verified', value: 'Verified', count: metrics.verified_count },
              { label: 'Cancelled', value: 'Cancelled', count: metrics.cancelled_count },
            ].map((tab) => {
              const isActive = statusFilter === tab.value;
              return (
                <button
                  key={tab.value}
                  type="button"
                  onClick={() => {
                    setStatusFilter(tab.value);
                    setPage(1);
                  }}
                  className={`flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all ${isActive
                    ? 'bg-slate-900 text-white shadow-xs'
                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                    }`}
                >
                  <span>{tab.label}</span>
                  {tab.count !== undefined && (
                    <span
                      className={`px-1.5 py-0.5 rounded-full text-[11px] font-bold ${isActive
                        ? 'bg-white/20 text-white'
                        : tab.value === 'Pending' && tab.count > 0
                          ? 'bg-amber-100 text-amber-800'
                          : 'bg-slate-100 text-slate-600'
                        }`}
                    >
                      {tab.count}
                    </span>
                  )}
                </button>
              );
            })}
          </div>

          {/* Search Input Bar */}
          <form onSubmit={handleSearchSubmit} className="flex items-center gap-2 w-full md:w-80">
            <div className="relative flex-1">
              <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
              <input
                type="text"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder="Search member, ID, request, tx..."
                className="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-transparent transition-all"
              />
              {search && (
                <button
                  type="button"
                  onClick={() => {
                    setSearch('');
                    setPage(1);
                  }}
                  className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs"
                >
                  ×
                </button>
              )}
            </div>
            <Button
              type="submit"
              label="Search"
              className="p-button-sm bg-slate-900 hover:bg-slate-800 text-white text-xs px-3.5 py-2 rounded-xl border-none"
            />
          </form>
        </div>
      </div>

      {/* Main Content Area */}
      {isLoading ? (
        <div className="py-20 flex justify-center">
          <LoadingSpinner message="Loading withdrawal requests..." />
        </div>
      ) : error ? (
        <ErrorState message={error} onRetry={fetchWithdrawals} />
      ) : withdrawals.length === 0 ? (
        <EmptyState
          icon={Wallet}
          title={statusFilter === 'Pending' ? 'No Pending Withdrawal Requests' : 'No Withdrawal Requests Found'}
          message={
            statusFilter === 'Pending'
              ? 'All member withdrawal requests have been processed! New submissions will appear here.'
              : 'No withdrawal records match the current filter or search criteria.'
          }
        />
      ) : (
        <div className="space-y-4">
          {/* Requests Table */}
          <div className="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-xs">
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                    <th className="py-3.5 px-4">Member Information</th>
                    <th className="py-3.5 px-4">Request Details</th>
                    <th className="py-3.5 px-4">Financials (Gross / Fee / Net)</th>
                    <th className="py-3.5 px-4">Payout Destination</th>
                    <th className="py-3.5 px-4">Status</th>
                    <th className="py-3.5 px-4 text-right">Admin Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 text-xs">
                  {withdrawals.map((w) => {
                    const member = w.member || {};
                    const isPending = w.status === 'Pending';
                    const isApproved = w.status === 'Approved';
                    const isVerified = w.status === 'Verified';

                    return (
                      <tr
                        key={w.id}
                        className={`hover:bg-slate-50/60 transition-colors ${isPending ? 'bg-amber-50/15' : ''
                          }`}
                      >
                        {/* 1. Member Information */}
                        <td className="py-4 px-4 align-top">
                          <div className="flex items-start gap-3">
                            <MemberAvatar member={member} name={w.name} />
                            <div className="min-w-0 space-y-1">
                              <div className="flex items-center gap-1.5 flex-wrap">
                                <span className="font-bold text-slate-900 truncate">
                                  {w.name || member.name || 'Unnamed Member'}
                                </span>
                                {member.is_verified && (
                                  <span className="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-semibold bg-blue-100 text-blue-700">
                                    Verified
                                  </span>
                                )}
                              </div>
                              <div className="flex items-center gap-1 text-slate-500 font-mono text-[11px]">
                                <span>@{w.memberid || member.user_id || `id_${w.member_id}`}</span>
                                <button
                                  type="button"
                                  onClick={() => copyToClipboard(w.memberid || member.user_id, 'Member ID')}
                                  className="hover:text-slate-800"
                                  title="Copy Member ID"
                                >
                                  <Copy className="w-3 h-3" />
                                </button>
                              </div>
                              <div className="text-[11px] text-slate-500 flex flex-col gap-0.5">
                                {member.email && <span className="truncate">{member.email}</span>}
                                {member.phone && <span>{member.phone}</span>}
                              </div>
                              {member.wallet !== undefined && (
                                <div className="pt-0.5">
                                  <span className="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700">
                                    Wallet Balance: ${Number(member.wallet || 0).toFixed(2)}
                                  </span>
                                </div>
                              )}
                            </div>
                          </div>
                        </td>

                        {/* 2. Request Details */}
                        <td className="py-4 px-4 align-top space-y-1.5">
                          <div className="flex items-center gap-1.5">
                            <span className="font-mono font-semibold text-slate-900 text-[11px] bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                              {w.request_id}
                            </span>
                            <button
                              type="button"
                              onClick={() => copyToClipboard(w.request_id, 'Request ID')}
                              className="text-slate-400 hover:text-slate-700 p-0.5"
                              title="Copy Request ID"
                            >
                              <Copy className="w-3.5 h-3.5" />
                            </button>
                          </div>
                          <div className="text-[11px] text-slate-500 space-y-0.5">
                            <div>
                              <span className="text-slate-400">Date: </span>
                              <span className="font-medium text-slate-700">
                                {new Date(w.request_date).toLocaleString('en-US', {
                                  month: 'short',
                                  day: 'numeric',
                                  year: 'numeric',
                                  hour: '2-digit',
                                  minute: '2-digit',
                                })}
                              </span>
                            </div>
                            {/* <div>
                              <span className="text-slate-400">Type: </span>
                              <span className="font-medium text-slate-700">{w.type || 'User'}</span>
                            </div> */}
                          </div>
                          {w.remarks && (
                            <div className="p-2 rounded-lg bg-slate-50 border border-slate-100 text-[11px] text-slate-600 italic">
                              "{w.remarks}"
                            </div>
                          )}
                        </td>

                        {/* 3. Financial Breakdown */}
                        <td className="py-4 px-4 align-top space-y-1.5">
                          <div>
                            <span className="text-slate-400 text-[11px]">Net Payout:</span>
                            <div className="text-base font-extrabold text-emerald-600">
                              ${Number(w.net_amount || 0).toFixed(2)}
                            </div>
                          </div>
                          <div className="text-[11px] space-y-0.5 border-t border-slate-100 pt-1 text-slate-500">
                            <div className="flex justify-between gap-2">
                              <span>Gross Requested:</span>
                              <span className="font-semibold text-slate-800">
                                ${Number(w.gross_amount || 0).toFixed(2)}
                              </span>
                            </div>
                            <div className="flex justify-between gap-2 text-rose-600">
                              <span>Service Charge (10%):</span>
                              <span className="font-semibold">
                                -${Number(w.service_charge || 0).toFixed(2)}
                              </span>
                            </div>
                          </div>
                        </td>

                        {/* 4. Payout Destination */}
                        <td className="py-4 px-4 align-top space-y-1.5">
                          {w.wallet_address ? (
                            <div className="space-y-1">
                              <span className="text-slate-400 text-[10px] uppercase font-bold tracking-wider">
                                Wallet Address
                              </span>
                              <div className="flex items-center gap-1.5 bg-slate-50 p-1.5 rounded-lg border border-slate-200">
                                <span className="font-mono text-[11px] text-slate-800 truncate max-w-[150px]">
                                  {w.wallet_address}
                                </span>
                                <button
                                  type="button"
                                  onClick={() => copyToClipboard(w.wallet_address, 'Wallet Address')}
                                  className="text-slate-400 hover:text-slate-700"
                                  title="Copy Wallet Address"
                                >
                                  <Copy className="w-3.5 h-3.5" />
                                </button>
                              </div>
                            </div>
                          ) : (
                            <div className="text-slate-400 italic text-[11px]">
                              Standard Internal / Bank Payout
                            </div>
                          )}

                          {w.txnid && (
                            <div className="space-y-0.5 pt-1">
                              <span className="text-slate-400 text-[10px] uppercase font-bold tracking-wider">
                                Txn / Reference ID
                              </span>
                              <div className="flex items-center gap-1 font-mono text-[11px] text-blue-700">
                                <span className="truncate max-w-[140px]">{w.txnid}</span>
                                <button
                                  type="button"
                                  onClick={() => copyToClipboard(w.txnid, 'Txn ID')}
                                  className="text-slate-400 hover:text-blue-900"
                                  title="Copy Txn ID"
                                >
                                  <Copy className="w-3 h-3" />
                                </button>
                              </div>
                            </div>
                          )}

                          {w.payment_date && (
                            <div className="text-[10px] text-slate-500">
                              Paid on: {new Date(w.payment_date).toLocaleDateString()}
                            </div>
                          )}

                          {w.admin_notes && (
                            <div className="text-[11px] text-slate-600 bg-amber-50/60 p-1.5 rounded border border-amber-100">
                              <span className="font-semibold text-amber-800">Admin Note: </span>
                              {w.admin_notes}
                            </div>
                          )}
                        </td>

                        {/* 5. Status Badge */}
                        <td className="py-4 px-4 align-top">
                          <WithdrawalStatusBadge status={w.status} />
                        </td>

                        {/* 6. Admin Actions (The 3 action buttons + View Details) */}
                        <td className="py-4 px-4 align-top text-right">
                          <div className="flex flex-col sm:flex-row items-end sm:items-center justify-end gap-1.5">
                            {isPending ? (
                              <>
                                {/* BUTTON 1: ACCEPT */}
                                <button
                                  type="button"
                                  onClick={() => handleOpenAccept(w)}
                                  className="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white shadow-2xs hover:shadow-xs transition-all"
                                  title="Accept and approve this withdrawal"
                                >
                                  <Check className="w-3.5 h-3.5" />
                                  Accept
                                </button>

                                {/* BUTTON 2: VERIFY */}
                                <button
                                  type="button"
                                  onClick={() => handleOpenVerify(w)}
                                  className="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white shadow-2xs hover:shadow-xs transition-all"
                                  title="Verify payout details or mark verified"
                                >
                                  <ShieldCheck className="w-3.5 h-3.5" />
                                  Verify
                                </button>

                                {/* BUTTON 3: REJECT */}
                                <button
                                  type="button"
                                  onClick={() => handleOpenReject(w)}
                                  className="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition-all"
                                  title="Reject request and refund gross amount to user wallet"
                                >
                                  <XCircle className="w-3.5 h-3.5" />
                                  Reject
                                </button>
                              </>
                            ) : null}

                            {/* View Full Details Button */}
                            <button
                              type="button"
                              onClick={() => handleOpenDetails(w)}
                              className="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 border border-slate-200 transition-all"
                              title="View full request and audit details"
                            >
                              <Eye className="w-4 h-4" />
                            </button>
                          </div>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>

            {/* Pagination Bar */}
            {totalPages > 1 && (
              <div className="flex items-center justify-between px-4 py-3 border-t border-slate-200 bg-slate-50/50">
                <span className="text-xs text-slate-500">
                  Showing page {page} of {totalPages} ({totalCount} total records)
                </span>
                <div className="flex items-center gap-2">
                  <Button
                    label="Previous"
                    className="p-button-sm p-button-outlined border-slate-300 text-slate-700 text-xs px-3 py-1 rounded-lg"
                    onClick={() => setPage((p) => Math.max(1, p - 1))}
                    disabled={page <= 1 || isLoading}
                  />
                  <Button
                    label="Next"
                    className="p-button-sm p-button-outlined border-slate-300 text-slate-700 text-xs px-3 py-1 rounded-lg"
                    onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                    disabled={page >= totalPages || isLoading}
                  />
                </div>
              </div>
            )}
          </div>
        </div>
      )}

      {/* ========================================================================= */}
      {/* ACTION MODAL 1: ACCEPT WITHDRAWAL */}
      {/* ========================================================================= */}
      <Dialog
        header="Accept Withdrawal Request"
        visible={showAcceptModal}
        onHide={() => setShowAcceptModal(false)}
        className="w-full max-w-lg"
        closable={!isAccepting}
      >
        {acceptTarget && (
          <div className="space-y-4 pt-2">
            <div className="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-slate-800 space-y-2">
              <div className="flex items-center justify-between">
                <span className="text-xs font-semibold text-emerald-800">
                  Request #{acceptTarget.request_id}
                </span>
                <span className="text-xs px-2 py-0.5 bg-emerald-200/80 text-emerald-900 rounded font-bold">
                  Approve Payout
                </span>
              </div>
              <div className="flex items-baseline justify-between border-t border-emerald-200/60 pt-2">
                <span className="text-xs text-slate-600">Net Disbursed Amount:</span>
                <span className="text-xl font-extrabold text-emerald-700">
                  ${Number(acceptTarget.net_amount || 0).toFixed(2)} USD
                </span>
              </div>
              <div className="text-[11px] text-slate-600 flex justify-between">
                <span>Member: <strong>{acceptTarget.name}</strong> (@{acceptTarget.memberid})</span>
                <span>Gross: ${Number(acceptTarget.gross_amount || 0).toFixed(2)}</span>
              </div>
            </div>

            <div className="space-y-1.5">
              <label className="text-xs font-semibold text-slate-700">
                Transaction ID / Reference Hash <span className="text-slate-400 font-normal">(Optional)</span>
              </label>
              <InputText
                value={acceptTxnId}
                onChange={(e) => setAcceptTxnId(e.target.value)}
                placeholder="e.g. 0xabc123... or Bank Transfer Ref #987654"
                className="w-full p-inputtext-sm text-xs"
                disabled={isAccepting}
              />
              <p className="text-[11px] text-slate-500">
                Enter payout transaction hash or reference number for the member's payment record.
              </p>
            </div>

            <div className="space-y-1.5">
              <label className="text-xs font-semibold text-slate-700">
                Admin Approval Notes <span className="text-slate-400 font-normal">(Optional)</span>
              </label>
              <InputTextarea
                value={acceptNotes}
                onChange={(e) => setAcceptNotes(e.target.value)}
                placeholder="Optional notes or remarks for internal records..."
                rows={2}
                className="w-full text-xs"
                disabled={isAccepting}
              />
            </div>

            <div className="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
              <Button
                label="Cancel"
                className="p-button-text p-button-sm text-slate-600 text-xs"
                onClick={() => setShowAcceptModal(false)}
                disabled={isAccepting}
              />
              <Button
                label={isAccepting ? 'Processing...' : 'Confirm & Accept'}
                icon={<Check className="w-4 h-4 mr-1.5" />}
                className="bg-emerald-600 hover:bg-emerald-700 text-white p-button-sm text-xs px-4 py-2 rounded-xl border-none shadow-xs font-semibold"
                onClick={handleConfirmAccept}
                loading={isAccepting}
              />
            </div>
          </div>
        )}
      </Dialog>

      {/* ========================================================================= */}
      {/* ACTION MODAL 2: VERIFY WITHDRAWAL */}
      {/* ========================================================================= */}
      <Dialog
        header="Verify Withdrawal Request"
        visible={showVerifyModal}
        onHide={() => setShowVerifyModal(false)}
        className="w-full max-w-lg"
        closable={!isVerifying}
      >
        {verifyTarget && (
          <div className="space-y-4 pt-2">
            <div className="p-4 rounded-xl bg-blue-50 border border-blue-200 text-slate-800 space-y-2">
              <div className="flex items-center justify-between">
                <span className="text-xs font-semibold text-blue-800">
                  Request #{verifyTarget.request_id}
                </span>
                <span className="text-xs px-2 py-0.5 bg-blue-200/80 text-blue-900 rounded font-bold">
                  Address & Account Verification
                </span>
              </div>
              <div className="flex items-baseline justify-between border-t border-blue-200/60 pt-2">
                <span className="text-xs text-slate-600">Net Payable Amount:</span>
                <span className="text-xl font-extrabold text-blue-700">
                  ${Number(verifyTarget.net_amount || 0).toFixed(2)} USD
                </span>
              </div>
              <div className="text-[11px] text-slate-600">
                Destination: <span className="font-mono">{verifyTarget.wallet_address || 'Default User Account'}</span>
              </div>
            </div>

            <div className="space-y-1.5">
              <label className="text-xs font-semibold text-slate-700">
                Transaction ID / Reference Hash <span className="text-slate-400 font-normal">(Optional)</span>
              </label>
              <InputText
                value={verifyTxnId}
                onChange={(e) => setVerifyTxnId(e.target.value)}
                placeholder="e.g. 0x9f3... or Verified on-chain hash"
                className="w-full p-inputtext-sm text-xs"
                disabled={isVerifying}
              />
            </div>

            <div className="space-y-1.5">
              <label className="text-xs font-semibold text-slate-700">
                Verification Notes <span className="text-slate-400 font-normal">(Optional)</span>
              </label>
              <InputTextarea
                value={verifyNotes}
                onChange={(e) => setVerifyNotes(e.target.value)}
                placeholder="Notes regarding account address verification..."
                rows={2}
                className="w-full text-xs"
                disabled={isVerifying}
              />
            </div>

            <div className="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
              <Button
                label="Cancel"
                className="p-button-text p-button-sm text-slate-600 text-xs"
                onClick={() => setShowVerifyModal(false)}
                disabled={isVerifying}
              />
              <Button
                label={isVerifying ? 'Verifying...' : 'Mark as Verified'}
                icon={<ShieldCheck className="w-4 h-4 mr-1.5" />}
                className="bg-blue-600 hover:bg-blue-700 text-white p-button-sm text-xs px-4 py-2 rounded-xl border-none shadow-xs font-semibold"
                onClick={handleConfirmVerify}
                loading={isVerifying}
              />
            </div>
          </div>
        )}
      </Dialog>

      {/* ========================================================================= */}
      {/* ACTION MODAL 3: REJECT WITHDRAWAL */}
      {/* ========================================================================= */}
      <Dialog
        header="Reject Withdrawal Request"
        visible={showRejectModal}
        onHide={() => setShowRejectModal(false)}
        className="w-full max-w-lg"
        closable={!isRejecting}
      >
        {rejectTarget && (
          <div className="space-y-4 pt-2">
            <div className="p-4 rounded-xl bg-rose-50 border border-rose-200 text-slate-800 space-y-2">
              <div className="flex items-center gap-2 text-rose-800 font-bold text-xs">
                <AlertTriangle className="w-4 h-4 text-rose-600 shrink-0" />
                Automatic Wallet Balance Refund Notice
              </div>
              <p className="text-xs text-rose-700 leading-relaxed">
                Rejecting request <strong>#{rejectTarget.request_id}</strong> will cancel the withdrawal and
                instantly refund the full gross amount of{' '}
                <span className="font-extrabold text-rose-900 underline">
                  ${Number(rejectTarget.gross_amount || 0).toFixed(2)} USD
                </span>{' '}
                back to member <strong>{rejectTarget.name}</strong>'s wallet balance.
              </p>
            </div>

            <div className="space-y-1.5">
              <label className="text-xs font-semibold text-slate-700">
                Rejection Reason <span className="text-slate-400 font-normal">(Visible in record)</span>
              </label>
              <InputTextarea
                value={rejectReason}
                onChange={(e) => setRejectReason(e.target.value)}
                placeholder="Please enter a reason for rejecting this withdrawal (e.g. Invalid payout address, Verification mismatch)..."
                rows={3}
                className="w-full text-xs"
                disabled={isRejecting}
              />
            </div>

            <div className="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
              <Button
                label="Cancel"
                className="p-button-text p-button-sm text-slate-600 text-xs"
                onClick={() => setShowRejectModal(false)}
                disabled={isRejecting}
              />
              <Button
                label={isRejecting ? 'Refunding...' : 'Confirm Rejection & Refund'}
                icon={<XCircle className="w-4 h-4 mr-1.5" />}
                className="bg-rose-600 hover:bg-rose-700 text-white p-button-sm text-xs px-4 py-2 rounded-xl border-none shadow-xs font-semibold"
                onClick={handleConfirmReject}
                loading={isRejecting}
              />
            </div>
          </div>
        )}
      </Dialog>

      {/* ========================================================================= */}
      {/* DETAILS MODAL: FULL AUDIT VIEW */}
      {/* ========================================================================= */}
      <Dialog
        header="Withdrawal Request Audit Details"
        visible={showDetailsModal}
        onHide={() => setShowDetailsModal(false)}
        className="w-full max-w-xl"
      >
        {selectedWithdrawal && (
          <div className="space-y-4 pt-2 text-xs">
            {/* Header info */}
            <div className="flex items-center justify-between pb-3 border-b border-slate-200">
              <div>
                <span className="text-slate-400 text-[11px]">Request Identifier</span>
                <div className="font-mono font-bold text-sm text-slate-900">
                  {selectedWithdrawal.request_id}
                </div>
              </div>
              <WithdrawalStatusBadge status={selectedWithdrawal.status} />
            </div>

            {/* Member Details */}
            <div className="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
              <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                Member Profile Information
              </span>
              <div className="flex items-center gap-3 pb-2.5 border-b border-slate-200/70">
                <MemberAvatar
                  member={selectedWithdrawal.member}
                  name={selectedWithdrawal.name}
                  size="w-12 h-12"
                  textSize="text-sm"
                />
                <div className="min-w-0">
                  <div className="flex items-center gap-1.5 flex-wrap">
                    <span className="font-bold text-slate-900 text-sm">{selectedWithdrawal.name}</span>
                    {selectedWithdrawal.member?.is_verified && (
                      <span className="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-semibold bg-blue-100 text-blue-700">
                        Verified
                      </span>
                    )}
                  </div>
                  <div className="text-slate-500 font-mono text-xs">
                    @{selectedWithdrawal.memberid || selectedWithdrawal.member?.user_id}
                  </div>
                </div>
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <span className="text-slate-500">Full Name:</span>
                  <div className="font-semibold text-slate-900">{selectedWithdrawal.name}</div>
                </div>
                <div>
                  <span className="text-slate-500">Member ID:</span>
                  <div className="font-mono font-medium text-slate-900">@{selectedWithdrawal.memberid}</div>
                </div>
                {selectedWithdrawal.member?.email && (
                  <div>
                    <span className="text-slate-500">Email:</span>
                    <div className="text-slate-900 truncate">{selectedWithdrawal.member.email}</div>
                  </div>
                )}
                {selectedWithdrawal.member?.phone && (
                  <div>
                    <span className="text-slate-500">Phone:</span>
                    <div className="text-slate-900">{selectedWithdrawal.member.phone}</div>
                  </div>
                )}
                {selectedWithdrawal.member?.wallet !== undefined && (
                  <div>
                    <span className="text-slate-500">Current Wallet Balance:</span>
                    <div className="font-bold text-emerald-700">
                      ${Number(selectedWithdrawal.member.wallet || 0).toFixed(2)}
                    </div>
                  </div>
                )}
              </div>
            </div>

            {/* Financial Details */}
            <div className="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
              <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                Financial Breakdown
              </span>
              <div className="grid grid-cols-3 gap-2">
                <div className="p-2.5 rounded-lg bg-white border border-slate-200">
                  <span className="text-slate-500 text-[10px]">Gross Requested</span>
                  <div className="font-bold text-slate-900 text-sm">
                    ${Number(selectedWithdrawal.gross_amount || 0).toFixed(2)}
                  </div>
                </div>
                <div className="p-2.5 rounded-lg bg-white border border-slate-200">
                  <span className="text-slate-500 text-[10px]">Service Fee (10%)</span>
                  <div className="font-bold text-rose-600 text-sm">
                    -${Number(selectedWithdrawal.service_charge || 0).toFixed(2)}
                  </div>
                </div>
                <div className="p-2.5 rounded-lg bg-emerald-50 border border-emerald-200">
                  <span className="text-emerald-700 font-semibold text-[10px]">Net Payable</span>
                  <div className="font-extrabold text-emerald-700 text-sm">
                    ${Number(selectedWithdrawal.net_amount || 0).toFixed(2)}
                  </div>
                </div>
              </div>
            </div>

            {/* Transaction / Processing Details */}
            <div className="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
              <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                Payout & Processing Timestamps
              </span>
              <div className="space-y-1.5 text-[11px]">
                <div className="flex justify-between">
                  <span className="text-slate-500">Request Date:</span>
                  <span className="font-medium text-slate-800">
                    {new Date(selectedWithdrawal.request_date).toLocaleString()}
                  </span>
                </div>
                {selectedWithdrawal.payment_date && (
                  <div className="flex justify-between">
                    <span className="text-slate-500">Payment Date:</span>
                    <span className="font-medium text-slate-800">
                      {new Date(selectedWithdrawal.payment_date).toLocaleString()}
                    </span>
                  </div>
                )}
                {selectedWithdrawal.wallet_address && (
                  <div className="flex justify-between">
                    <span className="text-slate-500">Wallet Address:</span>
                    <span className="font-mono text-slate-800">{selectedWithdrawal.wallet_address}</span>
                  </div>
                )}
                {selectedWithdrawal.txnid && (
                  <div className="flex justify-between">
                    <span className="text-slate-500">Txn ID / Reference:</span>
                    <span className="font-mono text-blue-700 font-semibold">{selectedWithdrawal.txnid}</span>
                  </div>
                )}
                {selectedWithdrawal.remarks && (
                  <div className="pt-1 border-t border-slate-200">
                    <span className="text-slate-500">User Remarks:</span>
                    <div className="italic text-slate-700">{selectedWithdrawal.remarks}</div>
                  </div>
                )}
                {selectedWithdrawal.admin_notes && (
                  <div className="pt-1 border-t border-slate-200">
                    <span className="text-slate-500">Admin Notes:</span>
                    <div className="font-medium text-amber-900 bg-amber-50 p-1.5 rounded">
                      {selectedWithdrawal.admin_notes}
                    </div>
                  </div>
                )}
              </div>
            </div>

            <div className="flex justify-end pt-2">
              <Button
                label="Close"
                className="p-button-outlined p-button-sm border-slate-300 text-slate-700 text-xs px-4"
                onClick={() => setShowDetailsModal(false)}
              />
            </div>
          </div>
        )}
      </Dialog>
    </div>
  );
}

export default WithdrawalsListPage;
