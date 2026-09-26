<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\WithdrawalRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WithdrawalManagementController extends Controller
{
    /**
     * List withdrawal requests with filtering, searching, and financial summary metrics.
     */
    public function index(Request $request): JsonResponse
    {
        $query = WithdrawalRequest::with([
            'member:id,name,user_id,email,phone,wallet,wallet_address,profile_photo,mobile_verified_at,blocked_at'
        ]);

        // Filter by Status: Pending, Approved, Verified, Cancelled (or 'all')
        $statusInput = $request->input('status', $request->input('params.status'));
        if (!empty($statusInput) && strtolower($statusInput) !== 'all') {
            $normalizedStatus = strtolower(trim($statusInput));
            if ($normalizedStatus === 'rejected' || $normalizedStatus === 'cancelled') {
                $query->where('status', WithdrawalRequest::STATUS_CANCELLED);
            } elseif ($normalizedStatus === 'approved') {
                $query->where('status', WithdrawalRequest::STATUS_APPROVED);
            } elseif ($normalizedStatus === 'verified') {
                $query->where('status', WithdrawalRequest::STATUS_VERIFIED);
            } elseif ($normalizedStatus === 'pending') {
                $query->where('status', WithdrawalRequest::STATUS_PENDING);
            } else {
                $query->where('status', ucfirst($normalizedStatus));
            }
        }

        // Filter by Type: User, Auto
        $typeInput = $request->input('type', $request->input('params.type'));
        if (!empty($typeInput) && strtolower($typeInput) !== 'all') {
            $query->where('type', ucfirst(strtolower($typeInput)));
        }

        // Date Range filtering
        $dateFrom = $request->input('date_from', $request->input('params.date_from'));
        if (!empty($dateFrom)) {
            $query->whereDate('request_date', '>=', $dateFrom);
        }
        $dateTo = $request->input('date_to', $request->input('params.date_to'));
        if (!empty($dateTo)) {
            $query->whereDate('request_date', '<=', $dateTo);
        }

        // Global Search across Request ID, Member ID, Name, Wallet, Txn ID, Phone, Email
        $searchInput = $request->input('search', $request->input('params.search'));
        if (!empty($searchInput)) {
            $s = '%' . trim($searchInput) . '%';
            $query->where(function ($q) use ($s) {
                $q->where('request_id', 'LIKE', $s)
                  ->orWhere('memberid', 'LIKE', $s)
                  ->orWhere('name', 'LIKE', $s)
                  ->orWhere('txnid', 'LIKE', $s)
                  ->orWhere('wallet_address', 'LIKE', $s)
                  ->orWhere('remarks', 'LIKE', $s)
                  ->orWhere('admin_notes', 'LIKE', $s)
                  ->orWhereHas('member', function ($mq) use ($s) {
                      $mq->where('name', 'LIKE', $s)
                         ->orWhere('user_id', 'LIKE', $s)
                         ->orWhere('email', 'LIKE', $s)
                         ->orWhere('phone', 'LIKE', $s);
                  });
            });
        }

        // Ordering: pending requests first by default, then latest request date
        $sortField = $request->input('sort_by', 'id');
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sortField === 'id' || $sortField === 'request_date') {
            $query->orderByRaw("CASE WHEN status = 'Pending' THEN 0 ELSE 1 END")
                  ->orderBy($sortField, $sortDir);
        } else {
            $query->orderBy($sortField, $sortDir);
        }

        $perPage = min(max((int) $request->input('per_page', 15), 5), 100);
        $withdrawals = $query->paginate($perPage);

        // Aggregate Financial Metrics across all withdrawal requests
        $all = WithdrawalRequest::query();
        $metrics = [
            'total_count' => (int) (clone $all)->count(),
            'total_gross' => (float) (clone $all)->sum('gross_amount'),
            'total_net' => (float) (clone $all)->sum('net_amount'),
            'total_service_charge' => (float) (clone $all)->sum('service_charge'),

            'pending_count' => (int) (clone $all)->where('status', WithdrawalRequest::STATUS_PENDING)->count(),
            'pending_gross' => (float) (clone $all)->where('status', WithdrawalRequest::STATUS_PENDING)->sum('gross_amount'),
            'pending_net' => (float) (clone $all)->where('status', WithdrawalRequest::STATUS_PENDING)->sum('net_amount'),

            'approved_count' => (int) (clone $all)->where('status', WithdrawalRequest::STATUS_APPROVED)->count(),
            'approved_gross' => (float) (clone $all)->where('status', WithdrawalRequest::STATUS_APPROVED)->sum('gross_amount'),
            'approved_net' => (float) (clone $all)->where('status', WithdrawalRequest::STATUS_APPROVED)->sum('net_amount'),

            'verified_count' => (int) (clone $all)->where('status', WithdrawalRequest::STATUS_VERIFIED)->count(),
            'verified_gross' => (float) (clone $all)->where('status', WithdrawalRequest::STATUS_VERIFIED)->sum('gross_amount'),
            'verified_net' => (float) (clone $all)->where('status', WithdrawalRequest::STATUS_VERIFIED)->sum('net_amount'),

            'cancelled_count' => (int) (clone $all)->where('status', WithdrawalRequest::STATUS_CANCELLED)->count(),
            'cancelled_gross' => (float) (clone $all)->where('status', WithdrawalRequest::STATUS_CANCELLED)->sum('gross_amount'),
            'cancelled_net' => (float) (clone $all)->where('status', WithdrawalRequest::STATUS_CANCELLED)->sum('net_amount'),
        ];

        return response()->json([
            'success' => true,
            'withdrawals' => $withdrawals,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Get summary metrics for withdrawal requests.
     */
    public function metrics(): JsonResponse
    {
        $all = WithdrawalRequest::query();

        return response()->json([
            'success' => true,
            'metrics' => [
                'total_count' => (int) (clone $all)->count(),
                'total_gross' => (float) (clone $all)->sum('gross_amount'),
                'total_net' => (float) (clone $all)->sum('net_amount'),
                'pending_count' => (int) (clone $all)->where('status', WithdrawalRequest::STATUS_PENDING)->count(),
                'pending_gross' => (float) (clone $all)->where('status', WithdrawalRequest::STATUS_PENDING)->sum('gross_amount'),
                'pending_net' => (float) (clone $all)->where('status', WithdrawalRequest::STATUS_PENDING)->sum('net_amount'),
                'approved_count' => (int) (clone $all)->where('status', WithdrawalRequest::STATUS_APPROVED)->count(),
                'approved_net' => (float) (clone $all)->where('status', WithdrawalRequest::STATUS_APPROVED)->sum('net_amount'),
                'verified_count' => (int) (clone $all)->where('status', WithdrawalRequest::STATUS_VERIFIED)->count(),
                'verified_net' => (float) (clone $all)->where('status', WithdrawalRequest::STATUS_VERIFIED)->sum('net_amount'),
                'cancelled_count' => (int) (clone $all)->where('status', WithdrawalRequest::STATUS_CANCELLED)->count(),
                'cancelled_gross' => (float) (clone $all)->where('status', WithdrawalRequest::STATUS_CANCELLED)->sum('gross_amount'),
            ],
        ]);
    }

    /**
     * View detailed information for a single withdrawal request.
     */
    public function show($id): JsonResponse
    {
        $withdrawal = WithdrawalRequest::with([
            'member:id,name,user_id,email,phone,wallet,wallet_address,profile_photo,mobile_verified_at,blocked_at'
        ])
            ->where('id', $id)
            ->orWhere('request_id', $id)
            ->first();

        if (!$withdrawal) {
            return response()->json([
                'success' => false,
                'message' => 'Withdrawal request not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'withdrawal' => $withdrawal,
        ]);
    }

    /**
     * Action 1: Accept / Approve a pending withdrawal request.
     */
    public function accept(Request $request, $id): JsonResponse
    {
        $request->validate([
            'txnid' => ['nullable', 'string', 'max:255'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return DB::transaction(function () use ($id, $request) {
            /** @var WithdrawalRequest|null $withdrawal */
            $withdrawal = WithdrawalRequest::where('id', $id)
                ->orWhere('request_id', $id)
                ->lockForUpdate()
                ->first();

            if (!$withdrawal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Withdrawal request not found.',
                ], 404);
            }

            if ($withdrawal->status !== WithdrawalRequest::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => "This withdrawal request has already been processed (Current status: {$withdrawal->status}).",
                    'withdrawal' => $withdrawal,
                ], 422);
            }

            $withdrawal->status = WithdrawalRequest::STATUS_APPROVED;
            $withdrawal->payment_date = now();

            if ($request->filled('txnid')) {
                $withdrawal->txnid = trim($request->input('txnid'));
            }

            if ($request->filled('admin_notes')) {
                $withdrawal->admin_notes = trim($request->input('admin_notes'));
            }

            $withdrawal->save();

            return response()->json([
                'success' => true,
                'message' => "Withdrawal request #{$withdrawal->request_id} has been accepted and approved successfully!",
                'withdrawal' => $withdrawal->fresh([
                    'member:id,name,user_id,email,phone,wallet,wallet_address,profile_photo,mobile_verified_at,blocked_at'
                ]),
            ]);
        });
    }

    /**
     * Action 2: Verify a withdrawal request (Confirm payout address / transaction verification).
     */
    public function verify(Request $request, $id): JsonResponse
    {
        $request->validate([
            'txnid' => ['nullable', 'string', 'max:255'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return DB::transaction(function () use ($id, $request) {
            /** @var WithdrawalRequest|null $withdrawal */
            $withdrawal = WithdrawalRequest::where('id', $id)
                ->orWhere('request_id', $id)
                ->lockForUpdate()
                ->first();

            if (!$withdrawal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Withdrawal request not found.',
                ], 404);
            }

            $withdrawal->status = WithdrawalRequest::STATUS_VERIFIED;

            if (!$withdrawal->payment_date) {
                $withdrawal->payment_date = now();
            }

            if ($request->filled('txnid')) {
                $withdrawal->txnid = trim($request->input('txnid'));
            }

            if ($request->filled('admin_notes')) {
                $withdrawal->admin_notes = trim($request->input('admin_notes'));
            }

            $withdrawal->save();

            return response()->json([
                'success' => true,
                'message' => "Withdrawal request #{$withdrawal->request_id} has been verified successfully!",
                'withdrawal' => $withdrawal->fresh([
                    'member:id,name,user_id,email,phone,wallet,wallet_address,profile_photo,mobile_verified_at,blocked_at'
                ]),
            ]);
        });
    }

    /**
     * Action 3: Reject a pending withdrawal request and refund gross amount to member's wallet.
     */
    public function reject(Request $request, $id): JsonResponse
    {
        $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return DB::transaction(function () use ($id, $request) {
            /** @var WithdrawalRequest|null $withdrawal */
            $withdrawal = WithdrawalRequest::where('id', $id)
                ->orWhere('request_id', $id)
                ->lockForUpdate()
                ->first();

            if (!$withdrawal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Withdrawal request not found.',
                ], 404);
            }

            if ($withdrawal->status !== WithdrawalRequest::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => "This withdrawal request has already been processed (Current status: {$withdrawal->status}).",
                    'withdrawal' => $withdrawal,
                ], 422);
            }

            // Refund gross_amount back to Member's wallet
            $member = null;
            if ($withdrawal->member_id) {
                $member = Member::where('id', $withdrawal->member_id)->lockForUpdate()->first();
            }
            if (!$member && $withdrawal->memberid) {
                $member = Member::where('user_id', $withdrawal->memberid)->lockForUpdate()->first();
            }

            $refundedAmount = (float) $withdrawal->gross_amount;
            $previousBalance = 0.00;
            $newBalance = 0.00;
            $isFundWallet = str_contains(strtolower((string) $withdrawal->remarks), 'fund wallet') || str_contains(strtolower((string) $withdrawal->remarks), 'p2p_wallet');

            if ($member) {
                $previousBalance = (float) ($member->wallet ?? 0.00);
                $newBalance = round($previousBalance + $refundedAmount, 4);
                $member->wallet = $newBalance;
                $member->save();
            }

            $withdrawal->status = WithdrawalRequest::STATUS_CANCELLED;
            $notes = $request->input('admin_notes') ?: $request->input('rejection_reason') ?: 'Rejected by administrator';
            $withdrawal->admin_notes = trim($notes);
            $withdrawal->save();

            $targetWalletName = $isFundWallet ? "member's Fund Wallet (p2p_wallet)" : "member's wallet";

            return response()->json([
                'success' => true,
                'message' => "Withdrawal request #{$withdrawal->request_id} has been rejected and \${$refundedAmount} was refunded to the {$targetWalletName}.",
                'refunded_amount' => $refundedAmount,
                'previous_wallet_balance' => $previousBalance,
                'new_wallet_balance' => $newBalance,
                'withdrawal' => $withdrawal->fresh([
                    'member:id,name,user_id,email,phone,wallet,wallet_address,profile_photo,mobile_verified_at,blocked_at'
                ]),
            ]);
        });
    }
}
