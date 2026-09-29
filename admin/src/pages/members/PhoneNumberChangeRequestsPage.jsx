import { useState, useEffect, useCallback } from 'react';
import {
  CheckCircle2,
  XCircle,
  Clock,
  Search,
  RefreshCw,
  PhoneForwarded,
  ArrowRight,
  ShieldCheck,
  ShieldAlert,
  AlertCircle,
  Eye,
  MessageSquare,
  User,
  Calendar,
  X,
  Loader2,
} from 'lucide-react';
import { PageHeader } from '../../components/common/PageHeader';
import { useToast } from '../../hooks/useToast';
import { phoneChangeRequestsApi } from '../../api';

export function PhoneNumberChangeRequestsPage() {
  const { showSuccess, showError } = useToast();

  const [requests, setRequests] = useState([]);
  const [loading, setLoading] = useState(true);
  const [statusTab, setStatusTab] = useState('pending'); // 'pending' | 'approved' | 'rejected' | 'all'
  const [searchQuery, setSearchQuery] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');

  // Pagination
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalRecords, setTotalRecords] = useState(0);

  // Metrics
  const [metrics, setMetrics] = useState({
    totalCount: 0,
    pendingCount: 0,
    approvedCount: 0,
    rejectedCount: 0,
  });

  // Action Modals State
  const [approveModalRequest, setApproveModalRequest] = useState(null);
  const [rejectModalRequest, setRejectModalRequest] = useState(null);
  const [detailModalRequest, setDetailModalRequest] = useState(null);
  const [rejectionReason, setRejectionReason] = useState('Verification details could not be validated.');
  const [actionLoading, setActionLoading] = useState(false);

  // Debounce search
  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSearch(searchQuery);
      setCurrentPage(1);
    }, 400);
    return () => clearTimeout(timer);
  }, [searchQuery]);

  // Load Data
  const loadRequests = useCallback(async () => {
    setLoading(true);
    try {
      const params = {
        page: currentPage,
        status: statusTab === 'all' ? undefined : statusTab,
        q: debouncedSearch.trim() || undefined,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
      };

      const res = await phoneChangeRequestsApi.getRequests(params);
      if (res && res.success) {
        setRequests(res.requests?.data || []);
        setTotalPages(res.requests?.last_page || 1);
        setTotalRecords(res.requests?.total || 0);
        setMetrics({
          totalCount: res.totalCount || 0,
          pendingCount: res.pendingCount || 0,
          approvedCount: res.approvedCount || 0,
          rejectedCount: res.rejectedCount || 0,
        });
      }
    } catch (err) {
      console.error('Failed to load phone change requests:', err);
      showError('Failed to load phone change requests.');
    } finally {
      setLoading(false);
    }
  }, [currentPage, statusTab, debouncedSearch, dateFrom, dateTo, showError]);

  useEffect(() => {
    loadRequests();
  }, [loadRequests]);

  // Handle Approve
  const handleConfirmApprove = async () => {
    if (!approveModalRequest) return;
    setActionLoading(true);

    try {
      const res = await phoneChangeRequestsApi.approveRequest(approveModalRequest.id);
      if (res && res.success) {
        showSuccess(res.message || 'Phone number change request approved successfully.');
        setApproveModalRequest(null);
        loadRequests();
      } else {
        showError(res?.message || 'Approval failed.');
      }
    } catch (err) {
      const msg = err.response?.data?.message || 'Failed to approve request.';
      showError(msg);
    } finally {
      setActionLoading(false);
    }
  };

  // Handle Reject
  const handleConfirmReject = async () => {
    if (!rejectModalRequest) return;
    setActionLoading(true);

    try {
      const res = await phoneChangeRequestsApi.rejectRequest(
        rejectModalRequest.id,
        rejectionReason.trim() || undefined
      );
      if (res && res.success) {
        showSuccess(res.message || 'Phone number change request rejected.');
        setRejectModalRequest(null);
        loadRequests();
      } else {
        showError(res?.message || 'Rejection failed.');
      }
    } catch (err) {
      const msg = err.response?.data?.message || 'Failed to reject request.';
      showError(msg);
    } finally {
      setActionLoading(false);
    }
  };

  const formatDate = (isoString) => {
    if (!isoString) return '—';
    try {
      const d = new Date(isoString);
      return d.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      });
    } catch {
      return isoString;
    }
  };

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <PageHeader
        title="Phone Number Change Requests"
        subtitle="Review, verify, and approve verified member WhatsApp phone number change requests."
        breadcrumbs={[
          { label: 'Members', to: '/admin/members' },
          { label: 'Phone Change Requests' },
        ]}
      />

      {/* Metrics Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div
          onClick={() => { setStatusTab('pending'); setCurrentPage(1); }}
          className={`bg-white rounded-xl p-5 border transition-all cursor-pointer shadow-sm ${
            statusTab === 'pending' ? 'border-amber-500 ring-2 ring-amber-100' : 'border-slate-200 hover:border-slate-300'
          }`}
        >
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-slate-500 uppercase tracking-wider">Pending Review</span>
            <div className="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600">
              <Clock className="w-5 h-5" />
            </div>
          </div>
          <div className="mt-2 flex items-baseline gap-2">
            <span className="text-2xl font-black text-slate-900">{metrics.pendingCount}</span>
            <span className="text-xs text-amber-600 font-semibold">Requires Action</span>
          </div>
        </div>

        <div
          onClick={() => { setStatusTab('approved'); setCurrentPage(1); }}
          className={`bg-white rounded-xl p-5 border transition-all cursor-pointer shadow-sm ${
            statusTab === 'approved' ? 'border-emerald-500 ring-2 ring-emerald-100' : 'border-slate-200 hover:border-slate-300'
          }`}
        >
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-slate-500 uppercase tracking-wider">Approved Requests</span>
            <div className="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600">
              <CheckCircle2 className="w-5 h-5" />
            </div>
          </div>
          <div className="mt-2 flex items-baseline gap-2">
            <span className="text-2xl font-black text-slate-900">{metrics.approvedCount}</span>
            <span className="text-xs text-emerald-600 font-semibold">Updated</span>
          </div>
        </div>

        <div
          onClick={() => { setStatusTab('rejected'); setCurrentPage(1); }}
          className={`bg-white rounded-xl p-5 border transition-all cursor-pointer shadow-sm ${
            statusTab === 'rejected' ? 'border-rose-500 ring-2 ring-rose-100' : 'border-slate-200 hover:border-slate-300'
          }`}
        >
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-slate-500 uppercase tracking-wider">Rejected Requests</span>
            <div className="w-9 h-9 rounded-lg bg-rose-50 flex items-center justify-center text-rose-600">
              <XCircle className="w-5 h-5" />
            </div>
          </div>
          <div className="mt-2 flex items-baseline gap-2">
            <span className="text-2xl font-black text-slate-900">{metrics.rejectedCount}</span>
            <span className="text-xs text-rose-600 font-semibold">Dismissed</span>
          </div>
        </div>

        <div
          onClick={() => { setStatusTab('all'); setCurrentPage(1); }}
          className={`bg-white rounded-xl p-5 border transition-all cursor-pointer shadow-sm ${
            statusTab === 'all' ? 'border-blue-500 ring-2 ring-blue-100' : 'border-slate-200 hover:border-slate-300'
          }`}
        >
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Lifetime</span>
            <div className="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
              <PhoneForwarded className="w-5 h-5" />
            </div>
          </div>
          <div className="mt-2 flex items-baseline gap-2">
            <span className="text-2xl font-black text-slate-900">{metrics.totalCount}</span>
            <span className="text-xs text-slate-500 font-medium">All History</span>
          </div>
        </div>
      </div>

      {/* Filters & Tabs Header */}
      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 space-y-4">
        {/* Tabs */}
        <div className="flex items-center gap-2 border-b border-slate-100 pb-3 overflow-x-auto">
          <button
            type="button"
            onClick={() => { setStatusTab('pending'); setCurrentPage(1); }}
            className={`px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 transition-colors cursor-pointer ${
              statusTab === 'pending'
                ? 'bg-amber-50 text-amber-700 border border-amber-200'
                : 'text-slate-600 hover:bg-slate-50'
            }`}
          >
            <Clock className="w-4 h-4" />
            <span>Pending</span>
            {metrics.pendingCount > 0 && (
              <span className="bg-amber-500 text-white text-xs px-2 py-0.5 rounded-full font-bold">
                {metrics.pendingCount}
              </span>
            )}
          </button>

          <button
            type="button"
            onClick={() => { setStatusTab('approved'); setCurrentPage(1); }}
            className={`px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 transition-colors cursor-pointer ${
              statusTab === 'approved'
                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                : 'text-slate-600 hover:bg-slate-50'
            }`}
          >
            <CheckCircle2 className="w-4 h-4" />
            <span>Approved</span>
          </button>

          <button
            type="button"
            onClick={() => { setStatusTab('rejected'); setCurrentPage(1); }}
            className={`px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 transition-colors cursor-pointer ${
              statusTab === 'rejected'
                ? 'bg-rose-50 text-rose-700 border border-rose-200'
                : 'text-slate-600 hover:bg-slate-50'
            }`}
          >
            <XCircle className="w-4 h-4" />
            <span>Rejected</span>
          </button>

          <button
            type="button"
            onClick={() => { setStatusTab('all'); setCurrentPage(1); }}
            className={`px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 transition-colors cursor-pointer ${
              statusTab === 'all'
                ? 'bg-slate-100 text-slate-800 border border-slate-300'
                : 'text-slate-600 hover:bg-slate-50'
            }`}
          >
            <span>All Requests</span>
          </button>
        </div>

        {/* Search & Date Controls */}
        <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
          <div className="relative flex-1">
            <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Search by member name, User ID, old number, or new number..."
              className="w-full pl-9 pr-4 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
            />
            {searchQuery && (
              <button
                type="button"
                onClick={() => setSearchQuery('')}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
              >
                <X className="w-4 h-4" />
              </button>
            )}
          </div>

          <div className="flex items-center gap-2">
            <input
              type="date"
              value={dateFrom}
              onChange={(e) => { setDateFrom(e.target.value); setCurrentPage(1); }}
              className="text-xs py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-700"
              title="Date From"
            />
            <span className="text-slate-400 text-xs">to</span>
            <input
              type="date"
              value={dateTo}
              onChange={(e) => { setDateTo(e.target.value); setCurrentPage(1); }}
              className="text-xs py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-700"
              title="Date To"
            />

            {(dateFrom || dateTo) && (
              <button
                type="button"
                onClick={() => { setDateFrom(''); setDateTo(''); setCurrentPage(1); }}
                className="p-2 text-slate-500 hover:text-slate-700 text-xs font-semibold"
                title="Clear Dates"
              >
                Clear
              </button>
            )}

            <button
              type="button"
              onClick={loadRequests}
              disabled={loading}
              className="p-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 transition-colors"
              title="Refresh"
            >
              <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
            </button>
          </div>
        </div>
      </div>

      {/* Requests Data Table */}
      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse text-sm">
            <thead>
              <tr className="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-bold text-xs uppercase tracking-wider">
                <th className="py-3.5 px-4">Request</th>
                <th className="py-3.5 px-4">Member</th>
                <th className="py-3.5 px-4">Active (Old) Number</th>
                <th className="py-3.5 px-4">Requested (New) Number</th>
                <th className="py-3.5 px-4">WhatsApp Status</th>
                <th className="py-3.5 px-4">Request Date</th>
                <th className="py-3.5 px-4">Status</th>
                <th className="py-3.5 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {loading && requests.length === 0 ? (
                <tr>
                  <td colSpan={8} className="py-12 text-center text-slate-400">
                    <div className="flex flex-col items-center justify-center gap-2">
                      <Loader2 className="w-6 h-6 animate-spin text-emerald-600" />
                      <span>Loading phone change requests...</span>
                    </div>
                  </td>
                </tr>
              ) : requests.length === 0 ? (
                <tr>
                  <td colSpan={8} className="py-12 text-center text-slate-400">
                    <div className="flex flex-col items-center justify-center gap-2">
                      <PhoneForwarded className="w-8 h-8 text-slate-300" />
                      <span className="font-medium text-slate-600">No phone change requests found.</span>
                      <span className="text-xs text-slate-400">
                        {statusTab === 'pending'
                          ? 'There are currently no requests awaiting administrator approval.'
                          : 'Try adjusting your filters or search term.'}
                      </span>
                    </div>
                  </td>
                </tr>
              ) : (
                requests.map((req) => (
                  <tr key={req.id} className="hover:bg-slate-50/80 transition-colors">
                    {/* Request ID */}
                    <td className="py-3.5 px-4 font-mono font-bold text-xs text-slate-500">
                      #{req.id}
                    </td>

                    {/* Member */}
                    <td className="py-3.5 px-4">
                      <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs uppercase flex-shrink-0">
                          {req.member?.name ? req.member.name.charAt(0) : 'U'}
                        </div>
                        <div>
                          <div className="font-bold text-slate-800 leading-tight">
                            {req.member?.name || 'Unknown Member'}
                          </div>
                          <div className="text-xs text-slate-500 font-mono">
                            {req.member?.user_id}
                          </div>
                        </div>
                      </div>
                    </td>

                    {/* Old Number */}
                    <td className="py-3.5 px-4 font-mono text-xs">
                      <div className="flex items-center gap-1.5 text-slate-700 font-semibold">
                        <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600 flex-shrink-0" />
                        <span>{req.old_phone}</span>
                      </div>
                      <span className="text-[10px] text-slate-400 uppercase font-bold tracking-wider">
                        Active Verified
                      </span>
                    </td>

                    {/* New Number */}
                    <td className="py-3.5 px-4 font-mono text-xs">
                      <div className="flex items-center gap-1.5 text-blue-700 font-bold bg-blue-50/80 px-2 py-1 rounded-md border border-blue-200/60 w-fit">
                        <ArrowRight className="w-3 h-3 text-blue-500" />
                        <span>{req.new_phone}</span>
                      </div>
                    </td>

                    {/* WhatsApp Status */}
                    <td className="py-3.5 px-4 text-xs">
                      {req.whatsapp_verified_at ? (
                        <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                          <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" />
                          <span>WhatsApp Sent</span>
                        </span>
                      ) : (
                        <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                          <Clock className="w-3.5 h-3.5 text-amber-600" />
                          <span>Not Confirmed</span>
                        </span>
                      )}
                    </td>

                    {/* Request Date */}
                    <td className="py-3.5 px-4 text-xs text-slate-500">
                      {formatDate(req.created_at)}
                    </td>

                    {/* Status */}
                    <td className="py-3.5 px-4">
                      {req.status === 'pending' ? (
                        <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                          <Clock className="w-3 h-3" /> Pending
                        </span>
                      ) : req.status === 'approved' ? (
                        <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                          <CheckCircle2 className="w-3 h-3" /> Approved
                        </span>
                      ) : (
                        <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                          <XCircle className="w-3 h-3" /> Rejected
                        </span>
                      )}
                    </td>

                    {/* Actions */}
                    <td className="py-3.5 px-4 text-right">
                      <div className="flex items-center justify-end gap-1.5">
                        <button
                          type="button"
                          onClick={() => setDetailModalRequest(req)}
                          className="p-1.5 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                          title="View Details"
                        >
                          <Eye className="w-4 h-4" />
                        </button>

                        {req.status === 'pending' && (
                          <>
                            <button
                              type="button"
                              onClick={() => setApproveModalRequest(req)}
                              className="p-1.5 rounded-lg text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 transition-colors cursor-pointer"
                              title="Approve Request"
                            >
                              <CheckCircle2 className="w-4 h-4" />
                            </button>
                            <button
                              type="button"
                              onClick={() => {
                                setRejectModalRequest(req);
                                setRejectionReason('Verification details could not be validated.');
                              }}
                              className="p-1.5 rounded-lg text-rose-600 hover:text-rose-700 hover:bg-rose-50 transition-colors cursor-pointer"
                              title="Reject Request"
                            >
                              <XCircle className="w-4 h-4" />
                            </button>
                          </>
                        )}
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination Footer */}
        {totalPages > 1 && (
          <div className="flex items-center justify-between px-4 py-3 border-t border-slate-200 bg-slate-50/50">
            <span className="text-xs text-slate-500">
              Showing page <strong>{currentPage}</strong> of <strong>{totalPages}</strong> ({totalRecords} records)
            </span>
            <div className="flex items-center gap-1">
              <button
                type="button"
                onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
                disabled={currentPage === 1}
                className="px-3 py-1 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-40"
              >
                Previous
              </button>
              <button
                type="button"
                onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}
                disabled={currentPage === totalPages}
                className="px-3 py-1 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-40"
              >
                Next
              </button>
            </div>
          </div>
        )}
      </div>

      {/* ===================================================================== */}
      {/* MODAL 1: APPROVE CONFIRMATION MODAL                                   */}
      {/* ===================================================================== */}
      {approveModalRequest && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in">
          <div className="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4">
            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
              <div className="flex items-center gap-2 text-emerald-700">
                <CheckCircle2 className="w-5 h-5 text-emerald-600" />
                <h3 className="font-bold text-base text-slate-900">Approve Phone Change</h3>
              </div>
              <button
                type="button"
                onClick={() => setApproveModalRequest(null)}
                className="text-slate-400 hover:text-slate-600"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <div className="space-y-3">
              <p className="text-xs text-slate-600 leading-relaxed">
                You are about to approve the phone number change for{' '}
                <strong>{approveModalRequest.member?.name}</strong> ({approveModalRequest.member?.user_id}).
              </p>

              <div className="bg-slate-50 rounded-xl p-3 border border-slate-200 space-y-2 text-xs">
                <div className="flex justify-between">
                  <span className="text-slate-500 font-medium">Current Active Number:</span>
                  <span className="font-mono font-bold text-slate-700">{approveModalRequest.old_phone}</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-slate-500 font-medium">New Approved Number:</span>
                  <span className="font-mono font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                    {approveModalRequest.new_phone}
                  </span>
                </div>
              </div>

              <div className="bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-800 leading-relaxed">
                <strong>Important:</strong> This action runs in an atomic database transaction. The member's registered phone number will be updated immediately and their green verified badge will remain active.
              </div>
            </div>

            <div className="flex gap-2 justify-end pt-3 border-t border-slate-100">
              <button
                type="button"
                onClick={() => setApproveModalRequest(null)}
                disabled={actionLoading}
                className="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={handleConfirmApprove}
                disabled={actionLoading}
                className="px-4 py-2 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white flex items-center gap-1.5 shadow-sm shadow-emerald-600/20"
              >
                {actionLoading ? (
                  <>
                    <Loader2 className="w-3.5 h-3.5 animate-spin" />
                    <span>Approving...</span>
                  </>
                ) : (
                  <>
                    <CheckCircle2 className="w-3.5 h-3.5" />
                    <span>Confirm Approval</span>
                  </>
                )}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ===================================================================== */}
      {/* MODAL 2: REJECT CONFIRMATION MODAL                                    */}
      {/* ===================================================================== */}
      {rejectModalRequest && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in">
          <div className="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4">
            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
              <div className="flex items-center gap-2 text-rose-700">
                <XCircle className="w-5 h-5 text-rose-600" />
                <h3 className="font-bold text-base text-slate-900">Reject Phone Change</h3>
              </div>
              <button
                type="button"
                onClick={() => setRejectModalRequest(null)}
                className="text-slate-400 hover:text-slate-600"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <div className="space-y-3">
              <p className="text-xs text-slate-600 leading-relaxed">
                Rejecting this request will keep{' '}
                <strong>{rejectModalRequest.member?.name}</strong>'s current verified phone number{' '}
                <span className="font-mono font-bold">({rejectModalRequest.old_phone})</span> active.
              </p>

              <div>
                <label
                  htmlFor="rejection-reason-input"
                  className="block text-xs font-bold text-slate-700 mb-1"
                >
                  Rejection Reason (Sent to Member)
                </label>
                <textarea
                  id="rejection-reason-input"
                  rows={3}
                  value={rejectionReason}
                  onChange={(e) => setRejectionReason(e.target.value)}
                  className="w-full p-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all"
                  placeholder="Explain why this request is being rejected..."
                />
              </div>
            </div>

            <div className="flex gap-2 justify-end pt-3 border-t border-slate-100">
              <button
                type="button"
                onClick={() => setRejectModalRequest(null)}
                disabled={actionLoading}
                className="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={handleConfirmReject}
                disabled={actionLoading}
                className="px-4 py-2 text-xs font-bold rounded-xl bg-rose-600 hover:bg-rose-700 text-white flex items-center gap-1.5 shadow-sm shadow-rose-600/20"
              >
                {actionLoading ? (
                  <>
                    <Loader2 className="w-3.5 h-3.5 animate-spin" />
                    <span>Rejecting...</span>
                  </>
                ) : (
                  <>
                    <XCircle className="w-3.5 h-3.5" />
                    <span>Confirm Rejection</span>
                  </>
                )}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ===================================================================== */}
      {/* MODAL 3: AUDIT TRAIL / DETAILS MODAL                                  */}
      {/* ===================================================================== */}
      {detailModalRequest && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in">
          <div className="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4">
            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
              <div className="flex items-center gap-2">
                <PhoneForwarded className="w-5 h-5 text-emerald-600" />
                <h3 className="font-bold text-base text-slate-900">
                  Request Audit Details #{detailModalRequest.id}
                </h3>
              </div>
              <button
                type="button"
                onClick={() => setDetailModalRequest(null)}
                className="text-slate-400 hover:text-slate-600"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <div className="space-y-3 text-xs">
              <div className="grid grid-cols-2 gap-3 bg-slate-50 p-3 rounded-xl border border-slate-200">
                <div>
                  <span className="text-slate-400 font-bold block">MEMBER NAME</span>
                  <span className="font-bold text-slate-800">{detailModalRequest.member?.name}</span>
                </div>
                <div>
                  <span className="text-slate-400 font-bold block">USER ID</span>
                  <span className="font-mono text-slate-800">{detailModalRequest.member?.user_id}</span>
                </div>
                <div>
                  <span className="text-slate-400 font-bold block">CURRENT NUMBER</span>
                  <span className="font-mono font-bold text-slate-800">{detailModalRequest.old_phone}</span>
                </div>
                <div>
                  <span className="text-slate-400 font-bold block">REQUESTED NUMBER</span>
                  <span className="font-mono font-bold text-blue-700">{detailModalRequest.new_phone}</span>
                </div>
              </div>

              <div className="space-y-2 p-3 bg-white border border-slate-200 rounded-xl">
                <div className="flex justify-between py-1 border-b border-slate-100">
                  <span className="text-slate-500 font-medium">Status</span>
                  <span className="font-bold uppercase tracking-wider">{detailModalRequest.status}</span>
                </div>
                <div className="flex justify-between py-1 border-b border-slate-100">
                  <span className="text-slate-500 font-medium">Requested On</span>
                  <span>{formatDate(detailModalRequest.created_at)}</span>
                </div>
                <div className="flex justify-between py-1 border-b border-slate-100">
                  <span className="text-slate-500 font-medium">Member WhatsApp Confirmation</span>
                  <span>{detailModalRequest.whatsapp_verified_at ? formatDate(detailModalRequest.whatsapp_verified_at) : 'Not confirmed'}</span>
                </div>
                {detailModalRequest.approved_at && (
                  <div className="flex justify-between py-1 border-b border-slate-100">
                    <span className="text-slate-500 font-medium">Approved At</span>
                    <span className="text-emerald-700 font-semibold">{formatDate(detailModalRequest.approved_at)}</span>
                  </div>
                )}
                {detailModalRequest.approver && (
                  <div className="flex justify-between py-1 border-b border-slate-100">
                    <span className="text-slate-500 font-medium">Approved By (Admin)</span>
                    <span className="font-semibold text-slate-700">{detailModalRequest.approver?.name}</span>
                  </div>
                )}
                {detailModalRequest.rejected_at && (
                  <div className="flex justify-between py-1 border-b border-slate-100">
                    <span className="text-slate-500 font-medium">Rejected At</span>
                    <span className="text-rose-700 font-semibold">{formatDate(detailModalRequest.rejected_at)}</span>
                  </div>
                )}
                {detailModalRequest.rejecter && (
                  <div className="flex justify-between py-1 border-b border-slate-100">
                    <span className="text-slate-500 font-medium">Rejected By (Admin)</span>
                    <span className="font-semibold text-slate-700">{detailModalRequest.rejecter?.name}</span>
                  </div>
                )}
                {detailModalRequest.rejection_reason && (
                  <div className="py-1">
                    <span className="text-slate-500 font-medium block mb-1">Rejection Reason</span>
                    <p className="bg-rose-50 text-rose-800 p-2 rounded-lg border border-rose-200">
                      {detailModalRequest.rejection_reason}
                    </p>
                  </div>
                )}
              </div>
            </div>

            <div className="flex justify-end pt-3 border-t border-slate-100">
              <button
                type="button"
                onClick={() => setDetailModalRequest(null)}
                className="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default PhoneNumberChangeRequestsPage;
