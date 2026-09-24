import { useState, useEffect, useRef } from 'react';
import {
  Wallet,
  Clock,
  CheckCircle,
  XCircle,
  Search,
  RefreshCw,
  Copy,
  DollarSign,
  Info,
  ExternalLink,
  ShieldCheck,
  AlertCircle,
} from 'lucide-react';
import { Button } from 'primereact/button';
import { InputText } from 'primereact/inputtext';
import { InputTextarea } from 'primereact/inputtextarea';
import { Dropdown } from 'primereact/dropdown';
import { Dialog } from 'primereact/dialog';
import { Toast } from 'primereact/toast';
import { AdminSearchInput } from '../../components/common/AdminSearchInput';
import { PageHeader } from '../../components/common/PageHeader';
import { LoadingSpinner } from '../../components/common/LoadingSpinner';
import { ErrorState } from '../../components/common/ErrorState';
import { fundsApi } from '../../api';

function StatusBadge({ status, verificationStatus }) {
  if (status === 'approved') {
    return (
      <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 border border-emerald-200">
        <CheckCircle className="w-3.5 h-3.5 mr-1" />
        Approved & Credited
      </span>
    );
  }

  if (status === 'rejected') {
    return (
      <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-700 border border-rose-200">
        <XCircle className="w-3.5 h-3.5 mr-1" />
        Rejected
      </span>
    );
  }

  if (verificationStatus === 'verified') {
    return (
      <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 border border-blue-200">
        <ShieldCheck className="w-3.5 h-3.5 mr-1" />
        Verified — Pending Approval
      </span>
    );
  }

  if (verificationStatus === 'failed') {
    return (
      <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-700 border border-rose-200">
        <AlertCircle className="w-3.5 h-3.5 mr-1" />
        Verification Failed
      </span>
    );
  }

  return (
    <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 border border-amber-200">
      <Clock className="w-3.5 h-3.5 mr-1" />
      Confirming On-Chain
    </span>
  );
}

export function DepositsListPage() {
  const toast = useRef(null);

  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [deposits, setDeposits] = useState([]);
  const [metrics, setMetrics] = useState({});
  const [copiedId, setCopiedId] = useState(null);
  const [reverifyingId, setReverifyingId] = useState(null);

  // Filters
  const [statusFilter, setStatusFilter] = useState('all');
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);

  // Modal Actions
  const [showApproveModal, setShowApproveModal] = useState(false);
  const [approveTarget, setApproveTarget] = useState(null);
  const [approveNotes, setApproveNotes] = useState('');
  const [isApproving, setIsApproving] = useState(false);

  const [showRejectModal, setShowRejectModal] = useState(false);
  const [rejectTarget, setRejectTarget] = useState(null);
  const [rejectNotes, setRejectNotes] = useState('');
  const [isRejecting, setIsRejecting] = useState(false);

  const fetchDeposits = () => {
    setIsLoading(true);
    setError(null);
    const params = {
      page,
      status: statusFilter !== 'all' ? statusFilter : undefined,
      search: search.trim() || undefined,
    };

    fundsApi
      .getDeposits(params)
      .then((res) => {
        const data = res.deposits || {};
        setDeposits(data.data || (Array.isArray(data) ? data : []));
        setMetrics(res.metrics || {});
      })
      .catch((err) => {
        setError(err.response?.data?.message || 'Failed to load deposit requests.');
      })
      .finally(() => {
        setIsLoading(false);
      });
  };

  useEffect(() => {
    let isMounted = true;
    const params = {
      page,
      status: statusFilter !== 'all' ? statusFilter : undefined,
      search: search.trim() || undefined,
    };

    fundsApi
      .getDeposits(params)
      .then((res) => {
        if (!isMounted) return;
        const data = res.deposits || {};
        setDeposits(data.data || (Array.isArray(data) ? data : []));
        setMetrics(res.metrics || {});
      })
      .catch((err) => {
        if (!isMounted) return;
        setError(err.response?.data?.message || 'Failed to load deposit requests.');
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [page, statusFilter, search]);

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    setPage(1);
    fetchDeposits();
  };

  const handleCopy = (text, id) => {
    if (text && navigator.clipboard) {
      navigator.clipboard.writeText(text);
      setCopiedId(id);
      setTimeout(() => setCopiedId(null), 2000);
    }
  };

  const showSuccess = (msg) => {
    toast.current?.show({
      severity: 'success',
      summary: 'Success',
      detail: msg,
      life: 4000,
    });
  };

  const showError = (msg) => {
    toast.current?.show({
      severity: 'error',
      summary: 'Action Failed',
      detail: msg,
      life: 5000,
    });
  };

  const handleReverify = async (id) => {
    setReverifyingId(id);
    try {
      const res = await fundsApi.reverifyDeposit(id);
      showSuccess(res.message || 'Deposit successfully verified on-chain!');
      fetchDeposits();
    } catch (err) {
      showError(err.response?.data?.message || 'On-chain verification failed.');
      fetchDeposits();
    } finally {
      setReverifyingId(null);
    }
  };

  // Trigger Approve
  const openApproveModal = (dep) => {
    setApproveTarget(dep);
    setApproveNotes('');
    setShowApproveModal(true);
  };

  const handleConfirmApprove = async () => {
    if (!approveTarget) return;

    setIsApproving(true);
    try {
      const res = await fundsApi.approveDeposit(approveTarget.id, {
        admin_notes: approveNotes.trim() || undefined,
      });

      showSuccess(res.message || 'Deposit approved and USD funds credited successfully!');
      setShowApproveModal(false);
      setApproveTarget(null);
      fetchDeposits();
    } catch (err) {
      showError(err.response?.data?.message || 'Failed to approve deposit request.');
    } finally {
      setIsApproving(false);
    }
  };

  // Trigger Reject
  const openRejectModal = (dep) => {
    setRejectTarget(dep);
    setRejectNotes('');
    setShowRejectModal(true);
  };

  const handleConfirmReject = async () => {
    if (!rejectTarget) return;

    setIsRejecting(true);
    try {
      const res = await fundsApi.rejectDeposit(rejectTarget.id, {
        admin_notes: rejectNotes.trim() || undefined,
      });

      showSuccess(res.message || 'Deposit request rejected.');
      setShowRejectModal(false);
      setRejectTarget(null);
      fetchDeposits();
    } catch (err) {
      showError(err.response?.data?.message || 'Failed to reject deposit request.');
    } finally {
      setIsRejecting(false);
    }
  };

  const statusOptions = [
    { label: 'All Statuses', value: 'all' },
    { label: 'Pending Review', value: 'pending' },
    { label: 'Approved & Verified', value: 'approved' },
    { label: 'Rejected', value: 'rejected' },
  ];

  return (
    <div className="space-y-6 max-w-7xl mx-auto pb-12">
      <Toast ref={toast} />

      <PageHeader
        title="Deposit Dashboard (USDT BEP-20)"
        subtitle="Real-time on-chain monitoring and verification for Member USDT (BEP-20) advertising deposits"
        actions={
          <Button
            type="button"
            label="Refresh"
            icon={<RefreshCw className={`w-4 h-4 mr-2 ${isLoading ? 'animate-spin' : ''}`} />}
            onClick={fetchDeposits}
            className="p-button-outlined p-button-sm border-slate-300 text-slate-700"
          />
        }
      />

      {/* Dashboard Mode Notice */}
      <div className="bg-emerald-50 border border-emerald-200 rounded-xl p-3.5 flex items-center justify-between text-xs text-emerald-900">
        <div className="flex items-center gap-2">
          <ShieldCheck className="w-4 h-4 text-emerald-600 flex-shrink-0" />
          <span>
            <strong>On-Chain Verification Engine Active:</strong> Deposits are verified against <strong>BNB Smart Chain (BEP-20)</strong> and automatically credited upon confirmed transfer.
          </span>
        </div>
        <span className="font-semibold bg-emerald-100 px-2 py-0.5 rounded text-[11px]">
          Token: USDT (BEP-20)
        </span>
      </div>

      {/* KPI Overview Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500">Total Requests</span>
            <Wallet className="w-4 h-4 text-blue-500" />
          </div>
          <p className="text-xl font-bold text-slate-800 mt-2">
            {metrics.total_requests || metrics.total_deposits || 0}
          </p>
          <span className="text-[11px] text-slate-400">All submissions</span>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500">Pending Requests</span>
            <Clock className="w-4 h-4 text-amber-500" />
          </div>
          <p className="text-xl font-bold text-amber-600 mt-2">
            {metrics.pending_count || 0}
          </p>
          <span className="text-[11px] text-slate-400">Awaiting blockchain confirmation</span>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500">Verified & Auto-Credited</span>
            <CheckCircle className="w-4 h-4 text-emerald-500" />
          </div>
          <p className="text-xl font-bold text-emerald-600 mt-2">
            {metrics.approved_count || 0}
          </p>
          <span className="text-[11px] text-slate-400">
            {Number(metrics.total_approved_usdt || metrics.total_approved_usd || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })} USDT credited
          </span>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500">Total USDT Volume</span>
            <DollarSign className="w-4 h-4 text-indigo-600" />
          </div>
          <p className="text-xl font-bold text-indigo-600 mt-2">
            {Number(metrics.total_volume_usdt || metrics.total_expected_usd || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })} USDT
          </p>
          <span className="text-[11px] text-slate-400">Total BEP-20 deposit volume</span>
        </div>
      </div>

      {/* Filter & Search Bar */}
      <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-3">
        <div className="flex flex-wrap items-center gap-3 flex-1 min-w-[300px]">
          <div className="flex-1 max-w-sm">
            <AdminSearchInput
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              onClear={() => setSearch('')}
              onSubmit={handleSearchSubmit}
              placeholder="Search member, email, hash, deposit ID..."
            />
          </div>

          <Button
            type="button"
            label="Search"
            onClick={handleSearchSubmit}
            className="p-button-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg px-4"
          />
        </div>

        <div className="flex items-center gap-2">
          <span className="text-xs font-semibold text-slate-500">Status:</span>
          <Dropdown
            value={statusFilter}
            options={statusOptions}
            onChange={(e) => {
              setStatusFilter(e.value);
              setPage(1);
            }}
            className="text-xs border border-slate-300 rounded-lg bg-white"
          />
        </div>
      </div>

      {/* Deposit Requests Table */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        {isLoading ? (
          <div className="p-12">
            <LoadingSpinner message="Loading deposit records..." />
          </div>
        ) : error ? (
          <div className="p-8">
            <ErrorState message={error} onRetry={fetchDeposits} />
          </div>
        ) : deposits.length === 0 ? (
          <div className="text-center py-16 px-4">
            <Wallet className="w-12 h-12 text-slate-300 mx-auto mb-3" />
            <h4 className="text-base font-bold text-slate-700">
              No Deposit Requests Found
            </h4>
            <p className="text-xs text-slate-400 max-w-sm mx-auto mt-1">
              When members submit USDT (BEP-20) deposit requests from their Business Page Ads portal, they will appear here.
            </p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-slate-600">
              <thead className="text-xs font-semibold text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                <tr>
                  <th className="px-4 py-3.5">Deposit ID</th>
                  <th className="px-4 py-3.5">Member</th>
                  <th className="px-4 py-3.5">Business Page</th>
                  <th className="px-4 py-3.5 text-right">Amount (USDT)</th>
                  <th className="px-4 py-3.5">Transaction Hash</th>
                  <th className="px-4 py-3.5 text-center">On-Chain Verification</th>
                  <th className="px-4 py-3.5 text-center">Submitted At</th>
                  <th className="px-4 py-3.5 text-center">Action</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {deposits.map((dep) => {
                  const usdtAmount = Number(dep.amount_usdt !== undefined ? dep.amount_usdt : (dep.submitted_amount || dep.expected_usd_amount || dep.amount_inr || 0));
                  const txHash = dep.transaction_hash || dep.transaction_reference || '';
                  const isVerified = dep.status === 'approved' || dep.verification_status === 'verified';
                  const explorerUrl = txHash ? `https://bscscan.com/tx/${txHash}` : null;

                  return (
                    <tr key={dep.id} className="hover:bg-slate-50 transition-colors">
                      <td className="px-4 py-3.5 font-mono text-xs font-bold text-slate-900">
                        {dep.deposit_id || `DEP-${dep.id}`}
                        <div className="text-[10px] text-slate-400 font-sans font-normal mt-0.5">
                          Network: {dep.network || 'BEP-20'}
                        </div>
                      </td>

                      <td className="px-4 py-3.5">
                        {dep.member ? (
                          <div>
                            <div className="text-xs font-semibold text-slate-800">
                              {dep.member.name}
                            </div>
                            <div className="text-[11px] text-slate-400">{dep.member.email}</div>
                          </div>
                        ) : (
                          <span className="text-slate-400 italic">Unknown</span>
                        )}
                      </td>

                      <td className="px-4 py-3.5 text-xs text-slate-700">
                        {dep.business_page?.page_name || 'N/A'}
                      </td>

                      <td className="px-4 py-3.5 text-right font-bold text-slate-900">
                        {usdtAmount.toLocaleString('en-US', { minimumFractionDigits: 2 })} USDT
                      </td>

                      <td className="px-4 py-3.5">
                        <div className="flex items-center gap-1.5 font-mono text-xs text-slate-700">
                          <span className="max-w-[150px] truncate" title={txHash}>
                            {txHash || 'N/A'}
                          </span>
                          {txHash && (
                            <>
                              <button
                                type="button"
                                onClick={() => handleCopy(txHash, dep.id)}
                                className="text-slate-400 hover:text-slate-600 p-1"
                                title="Copy Transaction Hash"
                              >
                                <Copy size={12} />
                              </button>
                              {explorerUrl && (
                                <a
                                  href={explorerUrl}
                                  target="_blank"
                                  rel="noopener noreferrer"
                                  className="text-indigo-500 hover:text-indigo-700 p-1"
                                  title="View on BscScan"
                                >
                                  <ExternalLink size={12} />
                                </a>
                              )}
                            </>
                          )}
                          {copiedId === dep.id && (
                            <span className="text-[10px] text-emerald-600 font-bold">Copied!</span>
                          )}
                        </div>
                        {dep.sender_address && (
                          <div className="text-[10px] text-slate-400 font-mono mt-0.5 truncate max-w-[170px]" title={`From: ${dep.sender_address}`}>
                            From: {dep.sender_address.slice(0, 8)}...{dep.sender_address.slice(-6)}
                          </div>
                        )}
                      </td>

                      <td className="px-4 py-3.5 text-center">
                        <StatusBadge status={dep.status} verificationStatus={dep.verification_status} />
                        {dep.block_number && (
                          <div className="text-[10px] text-slate-400 font-mono mt-0.5">
                            Block #{dep.block_number}
                          </div>
                        )}
                      </td>

                      <td className="px-4 py-3.5 text-center text-xs text-slate-500 font-mono">
                        {dep.submitted_at ? new Date(dep.submitted_at).toLocaleString() : 'N/A'}
                      </td>

                      <td className="px-4 py-3.5 text-center">
                        {dep.status === 'pending' ? (
                          <div className="flex items-center justify-center gap-1.5 flex-wrap">
                            <button
                              type="button"
                              onClick={() => openApproveModal(dep)}
                              className="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition-colors shadow-sm"
                            >
                              <CheckCircle className="w-3.5 h-3.5 mr-1" />
                              Approve
                            </button>
                            <button
                              type="button"
                              onClick={() => openRejectModal(dep)}
                              className="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg bg-rose-50 text-rose-700 border border-rose-300 hover:bg-rose-100 transition-colors"
                            >
                              <XCircle className="w-3.5 h-3.5 mr-1" />
                              Reject
                            </button>
                            {dep.verification_status !== 'verified' && (
                              <button
                                type="button"
                                onClick={() => handleReverify(dep.id)}
                                disabled={reverifyingId === dep.id}
                                title="Re-Verify on Blockchain"
                                className="inline-flex items-center p-1 text-xs font-medium rounded-lg border border-indigo-200 text-indigo-600 hover:bg-indigo-50 transition-colors disabled:opacity-50"
                              >
                                <RefreshCw className={`w-3.5 h-3.5 ${reverifyingId === dep.id ? 'animate-spin' : ''}`} />
                              </button>
                            )}
                          </div>
                        ) : dep.status === 'approved' ? (
                          <span className="inline-flex items-center text-xs font-semibold text-emerald-600">
                            <CheckCircle className="w-3.5 h-3.5 mr-1" /> Credited
                          </span>
                        ) : (
                          <span className="inline-flex items-center text-xs font-semibold text-rose-500">
                            <XCircle className="w-3.5 h-3.5 mr-1" /> Rejected
                          </span>
                        )}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Approve Confirmation Modal */}
      {approveTarget && (
        <Dialog
          header={
            <div className="flex items-center gap-2 text-slate-800 font-bold">
              <CheckCircle className="w-5 h-5 text-emerald-600" />
              <span>Approve Deposit & Credit USD Funds</span>
            </div>
          }
          visible={showApproveModal}
          onHide={() => !isApproving && setShowApproveModal(false)}
          style={{ width: '90vw', maxWidth: '560px' }}
          className="p-fluid"
        >
          <div className="space-y-4 pt-2">
            {/* Member & Deposit Meta Card */}
            <div className="bg-slate-50 p-3.5 rounded-xl border border-slate-200 text-xs space-y-2">
              <div className="flex justify-between">
                <span className="text-slate-500 font-semibold">Member:</span>
                <span className="font-bold text-slate-800">
                  {approveTarget.member?.name} ({approveTarget.member?.email})
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-500 font-semibold">Deposit ID:</span>
                <span className="font-mono font-bold text-slate-700">
                  {approveTarget.deposit_id || `DEP-${approveTarget.id}`}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-500 font-semibold">Transaction Hash:</span>
                <span className="font-mono font-bold text-indigo-600 break-all">
                  {approveTarget.transaction_hash || approveTarget.transaction_reference}
                </span>
              </div>
              {(approveTarget.member?.p2p_wallet !== undefined || approveTarget.member?.ad_balance !== undefined) && (
                <div className="flex justify-between border-t border-slate-200 pt-1.5">
                  <span className="text-slate-500 font-semibold">Current Fund Wallet:</span>
                  <span className="font-bold text-slate-700">
                    ${Number(approveTarget.member.p2p_wallet !== undefined ? approveTarget.member.p2p_wallet : approveTarget.member.ad_balance).toFixed(2)} USD
                  </span>
                </div>
              )}
            </div>

            {/* Authoritative Financial Snapshot Calculation */}
            <div className="bg-emerald-50/70 p-4 rounded-xl border border-emerald-200 space-y-2.5">
              <div className="text-[11px] font-extrabold uppercase tracking-wider text-emerald-800">
                Authoritative Deposit Credit (100% Exact Credit to Fund Wallet)
              </div>

              <div className="text-xs space-y-1.5">
                <div className="flex justify-between text-slate-700">
                  <span>Deposit Amount:</span>
                  <span className="font-bold">
                    ${Number(approveTarget.amount_usdt !== undefined ? approveTarget.amount_usdt : (approveTarget.submitted_amount || approveTarget.expected_usd_amount || approveTarget.amount_inr || 0)).toLocaleString('en-US', { minimumFractionDigits: 2 })} USD
                  </span>
                </div>
              </div>

              {/* Exact Credited USD Highlight */}
              <div className="mt-3 p-3 bg-white rounded-lg border border-emerald-300 flex items-center justify-between">
                <div>
                  <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                    Authoritative USD Credit
                  </span>
                  <div className="text-2xl font-black text-emerald-600">
                    +${Number(approveTarget.amount_usdt !== undefined ? approveTarget.amount_usdt : (approveTarget.submitted_amount || approveTarget.expected_usd_amount || approveTarget.amount_inr || 0)).toFixed(2)} USD
                  </div>
                </div>
                {(approveTarget.member?.p2p_wallet !== undefined || approveTarget.member?.ad_balance !== undefined) && (
                  <div className="text-right">
                    <span className="text-[10px] text-slate-400 uppercase">New Fund Wallet Balance</span>
                    <div className="text-base font-bold text-indigo-600">
                      ${(Number(approveTarget.member.p2p_wallet !== undefined ? approveTarget.member.p2p_wallet : approveTarget.member.ad_balance) + Number(approveTarget.amount_usdt !== undefined ? approveTarget.amount_usdt : (approveTarget.submitted_amount || approveTarget.expected_usd_amount || approveTarget.amount_inr || 0))).toFixed(2)} USD
                    </div>
                  </div>
                )}
              </div>
            </div>

            {/* Optional Admin Notes */}
            <div>
              <label className="block text-xs font-bold text-slate-700 mb-1">
                Admin Notes (Optional)
              </label>
              <InputTextarea
                value={approveNotes}
                onChange={(e) => setApproveNotes(e.target.value)}
                placeholder="e.g. Verified via Blockchain Explorer on 01/09/2026..."
                rows={2}
                className="w-full text-xs rounded-lg border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 p-2.5 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
              />
            </div>

            <div className="flex items-center gap-2 text-[11px] text-slate-500">
              <Info className="w-4 h-4 text-blue-500 flex-shrink-0" />
              <span>
                Funds will be immediately and atomically credited to the member’s advertising balance. Double-credit is prevented.
              </span>
            </div>

            {/* Modal Actions */}
            <div className="flex justify-end gap-2 pt-2 border-t border-slate-200">
              <Button
                type="button"
                label="Cancel"
                onClick={() => setShowApproveModal(false)}
                disabled={isApproving}
                className="p-button-text p-button-sm text-slate-600"
              />
              <Button
                type="button"
                label={isApproving ? 'Crediting Funds...' : 'Approve & Credit USD'}
                icon={isApproving ? <RefreshCw className="w-4 h-4 mr-1.5 animate-spin" /> : <CheckCircle className="w-4 h-4 mr-1.5" />}
                onClick={handleConfirmApprove}
                disabled={isApproving}
                className="p-button-sm bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2 rounded-lg border-none"
              />
            </div>
          </div>
        </Dialog>
      )}

      {/* Reject Confirmation Modal */}
      {rejectTarget && (
        <Dialog
          header={
            <div className="flex items-center gap-2 text-rose-700 font-bold">
              <XCircle className="w-5 h-5 text-rose-600" />
              <span>Reject Deposit Request</span>
            </div>
          }
          visible={showRejectModal}
          onHide={() => !isRejecting && setShowRejectModal(false)}
          style={{ width: '90vw', maxWidth: '500px' }}
          className="p-fluid"
        >
          <div className="space-y-4 pt-2">
            <div className="bg-rose-50 p-3.5 rounded-xl border border-rose-200 text-xs space-y-1.5">
              <div className="flex justify-between">
                <span className="text-slate-500 font-semibold">Deposit ID:</span>
                <span className="font-mono font-bold text-slate-800">
                  {rejectTarget.deposit_id || `DEP-${rejectTarget.id}`}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-500 font-semibold">Amount:</span>
                <span className="font-bold text-slate-800">
                  ${Number(rejectTarget.amount_usdt !== undefined ? rejectTarget.amount_usdt : (rejectTarget.submitted_amount || rejectTarget.expected_usd_amount || rejectTarget.amount_inr || 0)).toFixed(2)} USD
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-500 font-semibold">Member:</span>
                <span className="font-bold text-slate-800">
                  {rejectTarget.member?.name}
                </span>
              </div>
            </div>

            <div>
              <label className="block text-xs font-bold text-slate-700 mb-1">
                Reason for Rejection (Optional)
              </label>
              <InputTextarea
                value={rejectNotes}
                onChange={(e) => setRejectNotes(e.target.value)}
                placeholder="e.g. Transaction hash not found on chain, incorrect amount..."
                rows={3}
                className="w-full text-xs rounded-lg border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 p-2.5 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"
              />
            </div>

            <div className="text-[11px] text-slate-500">
              * Rejecting will mark the request as Rejected. No advertising funds will be credited.
            </div>

            <div className="flex justify-end gap-2 pt-2 border-t border-slate-200">
              <Button
                type="button"
                label="Cancel"
                onClick={() => setShowRejectModal(false)}
                disabled={isRejecting}
                className="p-button-text p-button-sm text-slate-600"
              />
              <Button
                type="button"
                label={isRejecting ? 'Rejecting...' : 'Reject Request'}
                icon={isRejecting ? <RefreshCw className="w-4 h-4 mr-1.5 animate-spin" /> : <XCircle className="w-4 h-4 mr-1.5" />}
                onClick={handleConfirmReject}
                disabled={isRejecting}
                className="p-button-sm bg-rose-600 hover:bg-rose-700 text-white font-bold px-4 py-2 rounded-lg border-none"
              />
            </div>
          </div>
        </Dialog>
      )}

      <Toast ref={toast} position="top-right" />
    </div>
  );
}

export default DepositsListPage;
