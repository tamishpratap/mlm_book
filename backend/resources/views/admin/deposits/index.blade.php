@extends('admin.layouts.master')
@section('title', 'Manual Deposit Requests')
@section('page-subtitle', 'Review, on-chain verify, approve, and reject member USDT BEP-20 deposits.')

@push('admin-styles')
<style>
    .deposit-badge-verified {
        background-color: #ecfdf5;
        color: #059669;
        border: 1px solid #a7f3d0;
        font-weight: 600;
    }
    .deposit-badge-pending {
        background-color: #fffbeb;
        color: #d97706;
        border: 1px solid #fde68a;
        font-weight: 600;
    }
    .deposit-badge-failed {
        background-color: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
        font-weight: 600;
    }
    .deposit-badge-approved {
        background-color: #f0fdf4;
        color: #16a34a;
        border: 1px solid #86efac;
        font-weight: 700;
    }
    .hash-truncate {
        max-width: 140px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-family: monospace;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Top KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #f59e0b !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Pending Approval</span>
                            <h3 class="fw-bold text-warning mb-0 mt-1">{{ $pendingCount }}</h3>
                            <span class="text-muted small">Awaiting Admin Action</span>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                            <i data-feather="clock" style="width: 24px; height: 24px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #10b981 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Total Approved</span>
                            <h3 class="fw-bold text-success mb-0 mt-1">{{ $approvedCount }}</h3>
                            <span class="text-muted small">${{ number_format($approvedVolume, 2) }} USDT Credited</span>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                            <i data-feather="check-circle" style="width: 24px; height: 24px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #ef4444 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Rejected Requests</span>
                            <h3 class="fw-bold text-danger mb-0 mt-1">{{ $rejectedCount }}</h3>
                            <span class="text-muted small">Invalid or Denied</span>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-3 rounded-circle text-danger">
                            <i data-feather="x-circle" style="width: 24px; height: 24px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #3b82f6 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Total Requests</span>
                            <h3 class="fw-bold text-primary mb-0 mt-1">{{ $totalCount }}</h3>
                            <span class="text-muted small">All Time Volume</span>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                            <i data-feather="dollar-sign" style="width: 24px; height: 24px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Action Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.deposits.index') }}" method="GET" class="row g-2 align-items-center">
                <!-- Status Filter Tabs / Select -->
                <div class="col-md-3">
                    <div class="btn-group w-100" role="group">
                        <a href="{{ route('admin.deposits.index', ['status' => 'all']) }}" class="btn btn-sm {{ $status === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                            All ({{ $totalCount }})
                        </a>
                        <a href="{{ route('admin.deposits.index', ['status' => 'pending']) }}" class="btn btn-sm {{ $status === 'pending' ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary' }}">
                            Pending ({{ $pendingCount }})
                        </a>
                        <a href="{{ route('admin.deposits.index', ['status' => 'approved']) }}" class="btn btn-sm {{ $status === 'approved' ? 'btn-success' : 'btn-outline-secondary' }}">
                            Approved
                        </a>
                    </div>
                </div>

                <!-- Search Input -->
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i data-feather="search" style="width: 14px; height: 14px;"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search Tx Hash, User ID, Member Name, Email..." value="{{ $search }}">
                        @if(!empty($search))
                            <a href="{{ route('admin.deposits.index', ['status' => $status]) }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                        @endif
                    </div>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i data-feather="filter" style="width: 14px; height: 14px;" class="me-1"></i> Apply Filter
                    </button>
                </div>

                <div class="col-md-2 text-end">
                    <a href="{{ route('admin.deposits.settings') }}" class="btn btn-sm btn-outline-dark w-100">
                        <i data-feather="settings" style="width: 14px; height: 14px;" class="me-1"></i> Deposit Settings
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Deposit Records Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold text-dark">
                <i data-feather="list" class="text-primary me-2" style="width: 18px; height: 18px;"></i>
                Deposit Requests Queue
            </h5>
            <span class="badge bg-light text-secondary border">Network: BEP-20 (Binance Smart Chain)</span>
        </div>

        <div class="card-body p-0">
            @if($deposits->isEmpty())
                <div class="text-center py-5">
                    <div class="mb-3 text-muted">
                        <i data-feather="inbox" style="width: 48px; height: 48px; stroke-width: 1.5;"></i>
                    </div>
                    <h5 class="fw-bold text-secondary">No Deposit Requests Found</h5>
                    <p class="text-muted small">No deposit records match your current filter criteria.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small text-muted">
                            <tr>
                                <th class="ps-3">Deposit ID</th>
                                <th>Member / User</th>
                                <th>Amount (USDT)</th>
                                <th>Transaction Hash</th>
                                <th>Sender Wallet</th>
                                <th class="text-center">On-Chain Status</th>
                                <th>Submitted At</th>
                                <th class="text-center pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($deposits as $deposit)
                                @php
                                    $isPending = in_array($deposit->deposit_status, ['pending', 'verified']) || ($deposit->status === 'Pending');
                                    $isApproved = $deposit->deposit_status === 'approved' || $deposit->status === 'Approved';
                                    $isRejected = $deposit->deposit_status === 'rejected' || $deposit->status === 'Rejected';
                                    $txHash = $deposit->transaction_hash ?: $deposit->txnid;
                                    $explorerUrl = $txHash ? $explorerBaseUrl . $txHash : null;
                                @endphp
                                <tr>
                                    <!-- Deposit ID -->
                                    <td class="ps-3 font-monospace small fw-bold text-dark">
                                        {{ $deposit->orderid ?: "DEP-{$deposit->id}" }}
                                        <div class="text-muted fw-normal" style="font-size: 11px;">#{{ $deposit->id }}</div>
                                    </td>

                                    <!-- Member Info -->
                                    <td>
                                        @if($deposit->member)
                                            <div class="fw-bold text-dark">{{ $deposit->member->name }}</div>
                                            <div class="small text-muted font-monospace">{{ $deposit->member->user_id }}</div>
                                            <div class="small text-muted" style="font-size: 11px;">{{ $deposit->member->email }}</div>
                                            <div class="text-success small fw-semibold">Fund Wallet: ${{ number_format($deposit->member->p2p_wallet ?? 0, 2) }}</div>
                                        @else
                                            <div class="fw-bold text-dark">{{ $deposit->user_id ?: $deposit->memberid }}</div>
                                            <span class="badge bg-secondary">Legacy Member</span>
                                        @endif
                                    </td>

                                    <!-- Amount -->
                                    <td>
                                        <span class="fs-6 fw-bold text-dark">${{ number_format($deposit->amount, 2) }}</span>
                                        <span class="small text-muted d-block">USDT (BEP-20)</span>
                                    </td>

                                    <!-- Transaction Hash with copy and explorer link -->
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="hash-truncate" title="{{ $txHash }}">
                                                {{ $txHash ?: 'N/A' }}
                                            </span>
                                            @if($txHash)
                                                <button type="button" class="btn btn-sm btn-link p-0 text-muted" onclick="navigator.clipboard.writeText('{{ $txHash }}'); alert('Transaction hash copied!');" title="Copy Tx Hash">
                                                    <i data-feather="copy" style="width: 13px; height: 13px;"></i>
                                                </button>
                                                @if($explorerUrl)
                                                    <a href="{{ $explorerUrl }}" target="_blank" rel="noopener noreferrer" class="text-primary" title="View on BscScan">
                                                        <i data-feather="external-link" style="width: 13px; height: 13px;"></i>
                                                    </a>
                                                @endif
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Sender Wallet -->
                                    <td class="small font-monospace text-muted">
                                        @if($deposit->wallet_address)
                                            <span title="{{ $deposit->wallet_address }}">
                                                {{ substr($deposit->wallet_address, 0, 6) }}...{{ substr($deposit->wallet_address, -4) }}
                                            </span>
                                        @else
                                            <span class="text-muted">Direct DApp</span>
                                        @endif
                                    </td>

                                    <!-- On-Chain Status Badge -->
                                    <td class="text-center">
                                        @if($isApproved)
                                            <span class="badge deposit-badge-approved px-2 py-1 rounded-pill">
                                                <i data-feather="check" style="width: 12px; height: 12px;" class="me-1"></i> Approved &amp; Credited
                                            </span>
                                        @elseif($isRejected)
                                            <span class="badge deposit-badge-failed px-2 py-1 rounded-pill">
                                                <i data-feather="x" style="width: 12px; height: 12px;" class="me-1"></i> Rejected
                                            </span>
                                        @elseif($deposit->verification_status === 'verified')
                                            <span class="badge deposit-badge-verified px-2 py-1 rounded-pill">
                                                <i data-feather="shield" style="width: 12px; height: 12px;" class="me-1"></i> Verified On-Chain
                                            </span>
                                            <div class="text-muted small" style="font-size: 10px;">Awaiting Admin</div>
                                        @elseif($deposit->verification_status === 'failed')
                                            <span class="badge deposit-badge-failed px-2 py-1 rounded-pill">
                                                <i data-feather="alert-triangle" style="width: 12px; height: 12px;" class="me-1"></i> Verification Failed
                                            </span>
                                        @else
                                            <span class="badge deposit-badge-pending px-2 py-1 rounded-pill">
                                                <i data-feather="clock" style="width: 12px; height: 12px;" class="me-1"></i> Pending Verification
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Submitted At -->
                                    <td class="small text-muted font-monospace">
                                        {{ $deposit->created_at ? $deposit->created_at->format('M d, Y H:i') : 'N/A' }}
                                    </td>

                                    <!-- Actions Column -->
                                    <td class="text-center pe-3">
                                        @if($isPending)
                                            <div class="d-flex justify-content-center gap-1 flex-wrap">
                                                <!-- Verify On-Chain Button -->
                                                <button type="button" class="btn btn-sm btn-outline-info text-dark" onclick="verifyDeposit({{ $deposit->id }})" id="verify-btn-{{ $deposit->id }}" title="Check live blockchain transaction on BSC">
                                                    <i data-feather="refresh-cw" style="width: 13px; height: 13px;" class="me-1"></i> Verify On-Chain
                                                </button>

                                                <!-- Approve Button -->
                                                <button type="button" class="btn btn-sm btn-success" onclick="openApproveModal({{ $deposit->id }}, '{{ $deposit->orderid ?: 'DEP-'.$deposit->id }}', '{{ $deposit->member?->name ?: $deposit->user_id }}', '{{ $deposit->amount }}', '{{ $txHash }}')">
                                                    <i data-feather="check-circle" style="width: 13px; height: 13px;" class="me-1"></i> Approve
                                                </button>

                                                <!-- Reject Button -->
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="openRejectModal({{ $deposit->id }}, '{{ $deposit->orderid ?: 'DEP-'.$deposit->id }}', '{{ $deposit->amount }}')">
                                                    <i data-feather="x-circle" style="width: 13px; height: 13px;" class="me-1"></i> Reject
                                                </button>
                                            </div>
                                        @elseif($isApproved)
                                            <span class="text-success small fw-bold">
                                                <i data-feather="check-circle" style="width: 14px; height: 14px;" class="me-1"></i> Credited
                                            </span>
                                        @else
                                            <span class="text-danger small fw-bold">
                                                <i data-feather="x-circle" style="width: 14px; height: 14px;" class="me-1"></i> Denied
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="p-3 border-top d-flex justify-content-between align-items-center">
                    <span class="small text-muted">Showing {{ $deposits->firstItem() }} to {{ $deposits->lastItem() }} of {{ $deposits->total() }} records</span>
                    {{ $deposits->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 1: On-Chain Live Verification Results Dialog -->
<!-- ========================================================================= -->
<div class="modal fade" id="onchainResultModal" tabindex="-1" aria-labelledby="onchainResultModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="onchainResultModalLabel">
                    <i data-feather="shield" class="text-primary me-2"></i> On-Chain Verification Report
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="onchainModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Checking Blockchain...</span>
                    </div>
                    <div class="mt-2 text-muted small">Querying BNB Smart Chain JSON-RPC nodes...</div>
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success btn-sm" id="modalQuickApproveBtn" style="display: none;">
                    <i data-feather="check" style="width: 14px; height: 14px;" class="me-1"></i> Proceed to Approve
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 2: Approve Deposit Confirmation Modal -->
<!-- ========================================================================= -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="approveForm" method="POST" action="">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold" id="approveModalLabel">
                        <i data-feather="check-circle" class="me-2"></i> Approve Deposit &amp; Credit USD
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="bg-light p-3 rounded mb-3 border">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Deposit ID:</span>
                            <span class="font-monospace fw-bold" id="approveDepositId"></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Member:</span>
                            <span class="fw-bold text-dark" id="approveMemberName"></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Deposit Amount:</span>
                            <span class="fw-bold text-success fs-5" id="approveAmount"></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Tx Hash:</span>
                            <span class="font-monospace small text-truncate" style="max-width: 250px;" id="approveTxHash"></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="adminNotes" class="form-label small fw-bold">Admin Notes (Optional)</label>
                        <textarea name="admin_notes" id="adminNotes" class="form-control form-control-sm" rows="2" placeholder="e.g. Verified on BscScan. Approved on-chain."></textarea>
                    </div>

                    <div class="alert alert-info py-2 small mb-0 d-flex align-items-center">
                        <i data-feather="info" class="me-2" style="width: 16px; height: 16px;"></i>
                        <span>This action will atomically credit the member's <strong>Fund Wallet</strong> (<code>p2p_wallet</code>) and prevent any duplicate credits.</span>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm fw-bold">
                        <i data-feather="check" class="me-1"></i> Confirm &amp; Credit Fund Wallet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 3: Reject Deposit Confirmation Modal -->
<!-- ========================================================================= -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="rejectForm" method="POST" action="">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold" id="rejectModalLabel">
                        <i data-feather="x-circle" class="me-2"></i> Reject Deposit Request
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="bg-light p-3 rounded mb-3 border">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Deposit ID:</span>
                            <span class="font-monospace fw-bold" id="rejectDepositId"></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Amount:</span>
                            <span class="fw-bold text-danger" id="rejectAmount"></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="rejectionReason" class="form-label small fw-bold">Reason for Rejection (Optional)</label>
                        <textarea name="rejection_reason" id="rejectionReason" class="form-control form-control-sm" rows="3" placeholder="e.g. Transaction hash not found on chain, wrong amount transferred, or test hash." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm fw-bold">
                        <i data-feather="x" class="me-1"></i> Reject Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('admin-scripts')
<script>
    // 1. On-Chain Live Verification via AJAX
    function verifyDeposit(id) {
        const modalEl = document.getElementById('onchainResultModal');
        const modal = new bootstrap.Modal(modalEl);
        const bodyEl = document.getElementById('onchainModalBody');
        const quickApproveBtn = document.getElementById('modalQuickApproveBtn');
        const verifyBtn = document.getElementById('verify-btn-' + id);

        quickApproveBtn.style.display = 'none';
        bodyEl.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Verifying on BNB Smart Chain...</span>
                </div>
                <div class="mt-2 fw-semibold text-dark">Querying BNB Smart Chain JSON-RPC...</div>
                <div class="text-muted small">Inspecting transaction receipt and ERC-20 Transfer logs</div>
            </div>
        `;
        modal.show();

        fetch("{{ url('admin/deposits') }}/" + id + "/verify-onchain", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        })
        .then(res => res.json())
        .then(res => {
            if (res.verified) {
                const d = res.data;
                bodyEl.innerHTML = `
                    <div class="alert alert-success border-success d-flex align-items-center mb-3">
                        <i data-feather="check-circle" class="text-success me-2" style="width: 24px; height: 24px;"></i>
                        <div>
                            <strong class="d-block">Transaction Verified on BNB Smart Chain!</strong>
                            <span class="small">${res.message}</span>
                        </div>
                    </div>

                    <div class="bg-light p-3 rounded border text-sm">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Deposit ID:</span>
                            <span class="font-monospace fw-bold text-dark">${d.deposit_id}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Transferred Amount:</span>
                            <strong class="text-success fs-6">${d.transferred_amount} USDT</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Token / Network:</span>
                            <span>${d.token} (${d.network})</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Sender Wallet:</span>
                            <span class="font-monospace small text-truncate" style="max-width: 200px;" title="${d.sender_address}">${d.sender_address}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Recipient Wallet:</span>
                            <span class="font-monospace small text-truncate" style="max-width: 200px;" title="${d.recipient_address}">${d.recipient_address}</span>
                        </div>
                        ${d.block_number ? `
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Block Number:</span>
                                <span class="font-monospace">#${d.block_number}</span>
                            </div>
                        ` : ''}
                        <div class="border-top pt-2 mt-2 text-center">
                            <a href="${d.explorer_url}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">
                                <i data-feather="external-link" style="width: 14px; height: 14px;" class="me-1"></i> View on BscScan Explorer
                            </a>
                        </div>
                    </div>
                `;
                quickApproveBtn.style.display = 'inline-block';
                quickApproveBtn.onclick = function() {
                    modal.hide();
                    openApproveModal(id, d.deposit_id, d.member_name, d.amount_usdt, d.transaction_hash);
                };
            } else {
                bodyEl.innerHTML = `
                    <div class="alert alert-danger border-danger mb-3">
                        <div class="fw-bold mb-1"><i data-feather="x-circle" class="me-1"></i> Blockchain Verification Failed</div>
                        <div class="small">${res.message || 'Transaction could not be confirmed on BNB Smart Chain.'}</div>
                    </div>
                    <div class="p-3 bg-light rounded text-muted small">
                        Please check if the transaction hash is correct, or if the transaction is still pending block confirmation on BscScan.
                    </div>
                `;
            }
            if (typeof feather !== 'undefined') feather.replace();
        })
        .catch(err => {
            bodyEl.innerHTML = `
                <div class="alert alert-danger border-danger">
                    <strong>Error:</strong> Failed to connect to server for verification.
                </div>
            `;
        });
    }

    // 2. Open Approve Modal
    function openApproveModal(id, depositId, memberName, amount, txHash) {
        document.getElementById('approveDepositId').textContent = depositId;
        document.getElementById('approveMemberName').textContent = memberName;
        document.getElementById('approveAmount').textContent = '$' + Number(amount).toFixed(2) + ' USDT';
        document.getElementById('approveTxHash').textContent = txHash;
        document.getElementById('approveForm').action = "{{ url('admin/deposits') }}/" + id + "/approve";

        const modal = new bootstrap.Modal(document.getElementById('approveModal'));
        modal.show();
    }

    // 3. Open Reject Modal
    function openRejectModal(id, depositId, amount) {
        document.getElementById('rejectDepositId').textContent = depositId;
        document.getElementById('rejectAmount').textContent = '$' + Number(amount).toFixed(2) + ' USDT';
        document.getElementById('rejectForm').action = "{{ url('admin/deposits') }}/" + id + "/reject";

        const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
        modal.show();
    }
</script>
@endpush
