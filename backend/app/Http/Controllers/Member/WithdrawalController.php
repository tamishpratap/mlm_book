<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\WithdrawalRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WithdrawalController extends Controller
{
    /**
     * Service charge percentage (10% standard platform deduction).
     */
    public const SERVICE_CHARGE_PERCENT = 10.00;

    /**
     * Minimum gross withdrawal allowed.
     */
    public const MINIMUM_WITHDRAWAL_AMOUNT = 5.00;

    /**
     * Get member withdrawal summary, calculation rules, and past requests history.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var Member|null $member */
        $member = $request->user('member');

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $member = $member->fresh() ?? $member;

        // Fetch member's past requests (matching either member_id or memberid)
        $withdrawals = WithdrawalRequest::query()
            ->where(function ($query) use ($member) {
                $query->where('member_id', $member->id)
                    ->orWhere('memberid', $member->user_id);
            })
            ->orderBy('id', 'desc')
            ->get();

        // Calculate statistics
        $pendingAmount = (float) $withdrawals->where('status', WithdrawalRequest::STATUS_PENDING)->sum('gross_amount');
        $cancelledAmount = (float) $withdrawals->where('status', WithdrawalRequest::STATUS_CANCELLED)->sum('gross_amount');
        $approvedAmount = (float) $withdrawals->whereIn('status', [WithdrawalRequest::STATUS_APPROVED, WithdrawalRequest::STATUS_VERIFIED])->sum('net_amount');
        $totalRequested = (float) $withdrawals->sum('gross_amount');

        return response()->json([
            'success' => true,
            'member' => [
                'id' => $member->id,
                'user_id' => $member->user_id,
                'name' => $member->name,
                'email' => $member->email,
                'phone' => $member->phone,
                'wallet' => (float) ($member->wallet ?? 0.00),
                'wallet_address' => $member->wallet_address ?? '',
                'ad_balance' => (float) ($member->p2p_wallet ?? 0.00),
                'reward_balance' => (float) ($member->wallet ?? 0.00),
                'reward_wallet_address' => $member->wallet_address ?? '',
                'reward_wallet_verified' => $member->hasVerifiedRewardWallet(),
                'status' => $member->status,
                'is_verified' => $member->is_verified,
            ],
            'config' => [
                'service_charge_percent' => self::SERVICE_CHARGE_PERCENT,
                'minimum_amount' => self::MINIMUM_WITHDRAWAL_AMOUNT,
                'currency' => 'USD',
                'currency_symbol' => '$',
            ],
            'stats' => [
                'pending_amount' => $pendingAmount,
                'cancelled_amount' => $cancelledAmount,
                'approved_amount' => $approvedAmount,
                'total_requested' => $totalRequested,
                'total_count' => $withdrawals->count(),
                'pending_count' => $withdrawals->where('status', WithdrawalRequest::STATUS_PENDING)->count(),
                'cancelled_count' => $withdrawals->where('status', WithdrawalRequest::STATUS_CANCELLED)->count(),
            ],
            'withdrawals' => $withdrawals,
        ]);
    }

    /**
     * Submit a new withdrawal request with 10% service charge deduction.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var Member|null $member */
        $member = $request->user('member');

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $request->validate([
            'gross_amount' => [
                'required',
                'numeric',
                'min:' . self::MINIMUM_WITHDRAWAL_AMOUNT,
                'max:1000000',
            ],
            'remarks' => ['nullable', 'string', 'max:500'],
        ], [
            'gross_amount.required' => 'Please enter the withdrawal amount.',
            'gross_amount.numeric' => 'The withdrawal amount must be a valid number.',
            'gross_amount.min' => 'Minimum withdrawal amount is $' . number_format(self::MINIMUM_WITHDRAWAL_AMOUNT, 2) . '.',
        ]);

        $grossAmount = round((float) $request->input('gross_amount'), 2);

        // 10% Service Charge Deduction
        $serviceCharge = round($grossAmount * (self::SERVICE_CHARGE_PERCENT / 100), 2);
        $netAmount = round($grossAmount - $serviceCharge, 2);

        // Generate unique human-readable Request ID: e.g. WD20260918-XXXXXX
        $requestId = 'WD' . date('Ymd') . '-' . strtoupper(Str::random(6));

        // Use member's wallet address or reward wallet address
        $walletAddress = $member->wallet_address ?? '';

        // 1. Check if member has an existing pending withdrawal
        $pendingCount = WithdrawalRequest::query()
            ->where(function ($query) use ($member) {
                $query->where('member_id', $member->id)
                      ->orWhere('memberid', $member->user_id);
            })
            ->where('status', 'Pending')
            ->count();

        if (empty($walletAddress)) {
            return response()->json([
                'success' => false,
                'message' => 'Please set your wallet address in your profile before making a withdrawal request.',
            ], 422);
        }

        if ($pendingCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Your last withdrawal request is still pending. Please wait for admin approval.',
            ], 422);
        }

        // 2. Check balance against wallet
        $walletBalance = (float) ($member->wallet ?? 0.00);
        if ($grossAmount > $walletBalance) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Amount Entered. Amount cannot be greater than Wallet balance ($' . number_format($walletBalance, 4) . ').',
            ], 422);
        }

        // 3. Check if account is active
        if (strtolower((string) $member->status) !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not active. Please complete verification to withdraw money.',
            ], 422);
        }

        // Deduct from wallet balance atomically
        $member->wallet = max(0, $walletBalance - $grossAmount);
        $member->save();

        $withdrawal = WithdrawalRequest::create([
            'member_id' => $member->id,
            'memberid' => $member->user_id ?? (string) $member->id,
            'name' => $member->name,
            'request_id' => $requestId,
            'request_date' => now(),
            'payment_date' => null,
            'txnid' => null,
            'wallet_address' => $walletAddress ?: null,
            'gross_amount' => $grossAmount,
            'service_charge' => $serviceCharge,
            'net_amount' => $netAmount,
            'remarks' => $request->input('remarks') ? trim($request->input('remarks')) : null,
            'type' => WithdrawalRequest::TYPE_USER,
            'status' => WithdrawalRequest::STATUS_PENDING,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal request submitted successfully! Your request is currently Pending for admin review.',
            'withdrawal' => $withdrawal,
            'details' => [
                'request_id' => $withdrawal->request_id,
                'gross_amount' => $withdrawal->gross_amount,
                'service_charge' => $withdrawal->service_charge,
                'service_charge_percent' => self::SERVICE_CHARGE_PERCENT . '%',
                'net_amount' => $withdrawal->net_amount,
                'status' => $withdrawal->status,
                'request_date' => $withdrawal->request_date->toIso8601String(),
            ],
        ], 201);
    }
}
