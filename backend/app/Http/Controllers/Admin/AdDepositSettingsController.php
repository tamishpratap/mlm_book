<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdDeposit;
use App\Models\Member;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdDepositSettingsController extends Controller
{
    /**
     * Get current deposit settings (QR, Crypto Wallet Address, USDT BEP-20, 0% fee).
     */
    public function getSettings(): JsonResponse
    {
        $qrPath = Setting::get('deposit_qr_image');
        $qrUrl = null;

        if ($qrPath) {
            $qrUrl = str_starts_with($qrPath, 'http') ? $qrPath : asset($qrPath);
        }

        return response()->json([
            'success' => true,
            'settings' => [
                'deposit_qr_image' => $qrPath,
                'deposit_qr_url' => $qrUrl,
                'deposit_crypto_wallet_address' => Setting::get('deposit_crypto_wallet_address', ''),
                'deposit_currency' => Setting::get('deposit_currency', 'USDT'),
                'deposit_network' => Setting::get('deposit_network', 'BEP-20'),
                'deposit_token' => Setting::get('deposit_token', 'USDT'),
                'deposit_currency_label' => 'USDT (BEP-20)',
                'deposit_currency_symbol' => 'USDT',
                'deposit_fee_percent' => (float) Setting::get('deposit_fee_percent', 0.00),
                'service_charge_percent' => (float) Setting::get('deposit_fee_percent', 0.00),
                'deposit_instructions' => Setting::get('deposit_instructions', 'Transfer payment in USDT (BEP-20) using the configured crypto wallet address or QR code. Enter your transaction hash after completing payment.'),
                'deposit_disclaimer' => 'IMPORTANT: Deposits are accepted exclusively in USDT (BEP-20). Any other token or network is not supported and will not be credited.',
                // Legacy preserved keys
                'deposit_upi_id' => Setting::get('deposit_upi_id', ''),
                'deposit_exchange_rate' => (float) Setting::get('deposit_exchange_rate', 83.50),
                'deposit_min_inr' => (float) Setting::get('deposit_min_inr', 100.00),
            ],
        ]);
    }

    /**
     * Update deposit settings (Crypto Wallet Address, QR image, Instructions, Service Charge / Fee).
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $request->validate([
            'deposit_crypto_wallet_address' => ['nullable', 'string', 'regex:/^0x[a-fA-F0-9]{40}$/'],
            'deposit_instructions' => ['nullable', 'string', 'max:5000'],
            'deposit_qr_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],
            'deposit_fee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'service_charge_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'deposit_crypto_wallet_address.regex' => 'Destination crypto wallet address must be a valid 42-character BSC/EVM address starting with 0x.',
        ]);

        if ($request->hasFile('deposit_qr_image')) {
            $file = $request->file('deposit_qr_image');
            $path = app(\App\Http\Controllers\ImageCompressionController::class)->compressAndStore(
                $file,
                'deposit_qr',
                'qr',
                null,
                'public'
            );
            if ($path) {
                Setting::set('deposit_qr_image', 'storage/' . $path, 'funds');
                $publicDestDir = dirname(public_path('storage/' . $path));
                if (!file_exists($publicDestDir)) {
                    @mkdir($publicDestDir, 0755, true);
                }
                @copy(storage_path('app/public/' . $path), public_path('storage/' . $path));
            }
        }

        if ($request->has('deposit_crypto_wallet_address')) {
            Setting::set('deposit_crypto_wallet_address', trim($request->input('deposit_crypto_wallet_address')), 'funds');
        }

        // Canonical active configuration: USDT BEP-20 token
        Setting::set('deposit_currency', 'USDT', 'funds');
        Setting::set('deposit_network', 'BEP-20', 'funds');
        Setting::set('deposit_token', 'USDT', 'funds');

        // Dynamic Service Charge / Deposit Fee Percent
        if ($request->has('deposit_fee_percent') || $request->has('service_charge_percent')) {
            $feeVal = $request->input('deposit_fee_percent', $request->input('service_charge_percent'));
            $feePercent = round(max(0.00, min(100.00, (float) $feeVal)), 2);
            Setting::set('deposit_fee_percent', number_format($feePercent, 2, '.', ''), 'funds');
        }

        if ($request->has('deposit_instructions')) {
            Setting::set('deposit_instructions', trim($request->input('deposit_instructions', '')), 'funds');
        }

        return $this->getSettings();
    }

    /**
     * List all deposit requests for Admin overview and data dashboard.
     */
    public function getDeposits(Request $request): JsonResponse
    {
        $query = AdDeposit::with(['member:id,name,user_id,email,phone,profile_photo,p2p_wallet', 'businessPage:id,page_name,slug,logo', 'verifiedBy:id,name,email']);

        // Filter by status
        if ($request->filled('status') && in_array($request->status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $request->status);
        }

        // Search by member name/email or transaction reference / hash
        if ($request->filled('search')) {
            $s = '%' . trim($request->search) . '%';
            $query->where(function ($q) use ($s) {
                $q->where('deposit_id', 'LIKE', $s)
                  ->orWhere('transaction_reference', 'LIKE', $s)
                  ->orWhere('transaction_hash', 'LIKE', $s)
                  ->orWhereHas('member', function ($mq) use ($s) {
                      $mq->where('name', 'LIKE', $s)
                         ->orWhere('email', 'LIKE', $s)
                         ->orWhere('user_id', 'LIKE', $s);
                  })
                  ->orWhereHas('businessPage', function ($bq) use ($s) {
                      $bq->where('page_name', 'LIKE', $s);
                  });
            });
        }

        $perPage = min(max((int) $request->input('per_page', 15), 5), 100);
        $deposits = $query->latest('submitted_at')->paginate($perPage);

        // Calculate summary metrics
        $allQuery = AdDeposit::query();
        $totalVolumeUsdt = (float) (clone $allQuery)->sum(DB::raw('COALESCE(submitted_amount, expected_usd_amount, amount_inr)'));
        $totalPendingUsdt = (float) (clone $allQuery)->where('status', AdDeposit::STATUS_PENDING)->sum(DB::raw('COALESCE(submitted_amount, expected_usd_amount, amount_inr)'));
        $totalApprovedUsdt = (float) (clone $allQuery)->where('status', AdDeposit::STATUS_APPROVED)->sum(DB::raw('COALESCE(submitted_amount, expected_usd_amount, amount_inr)'));

        $metrics = [
            'total_deposits' => (int) $allQuery->count(),
            'total_requests' => (int) $allQuery->count(),
            'pending_count' => (int) (clone $allQuery)->where('status', AdDeposit::STATUS_PENDING)->count(),
            'approved_count' => (int) (clone $allQuery)->where('status', AdDeposit::STATUS_APPROVED)->count(),
            'rejected_count' => (int) (clone $allQuery)->where('status', AdDeposit::STATUS_REJECTED)->count(),
            'total_volume_usdt' => $totalVolumeUsdt,
            'total_pending_usdt' => $totalPendingUsdt,
            'total_approved_usdt' => $totalApprovedUsdt,
            // Backwards-compatible metric keys
            'total_expected_usd' => $totalPendingUsdt,
            'total_approved_usd' => $totalApprovedUsdt,
        ];

        return response()->json([
            'success' => true,
            'deposits' => $deposits,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Approve a pending deposit request and atomically credit USD advertising funds to the Member.
     */
    public function approveDeposit(Request $request, $id): JsonResponse
    {
        $admin = auth('admin')->user();

        $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return DB::transaction(function () use ($id, $admin, $request) {
            // Lock deposit row to prevent race conditions & double credits
            $deposit = AdDeposit::where('id', $id)
                ->orWhere('deposit_id', $id)
                ->lockForUpdate()
                ->first();

            if (!$deposit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Deposit request not found.',
                ], 404);
            }

            // Check if already processed (Idempotency)
            if ($deposit->status !== AdDeposit::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => "This deposit request has already been processed (Current status: {$deposit->status}).",
                    'deposit' => $deposit,
                ], 422);
            }

            // Lock member row
            $member = Member::where('id', $deposit->member_id)
                ->lockForUpdate()
                ->first();

            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => 'The associated member account could not be found.',
                ], 422);
            }

            // Determine authoritative USD credit
            $grossAmount = (float) ($deposit->verified_amount > 0 ? $deposit->verified_amount : ($deposit->submitted_amount > 0 ? $deposit->submitted_amount : $deposit->amount_inr));
            $feePercent = (float) ($deposit->fee_percent ?? Setting::get('deposit_fee_percent', 0.00));
            if ($feePercent > 0.00) {
                $netAmount = (float) ($deposit->net_amount_inr > 0 ? $deposit->net_amount_inr : round($grossAmount / (1 + ($feePercent / 100)), 2));
                $feeAmount = (float) ($deposit->fee_amount_inr > 0 ? $deposit->fee_amount_inr : round($grossAmount - $netAmount, 2));
                // Deduct service charge: Member receives Net Amount (gross / (1 + feePercent/100))
                $usdCredit = $netAmount;
            } elseif ($deposit->currency_in === 'USD' || (float) $deposit->exchange_rate === 1.00) {
                // Exact credit when zero fee
                $usdCredit = (float) ($deposit->expected_usd_amount > 0 ? $deposit->expected_usd_amount : $grossAmount);
            } else {
                // Legacy INR deposit snapshot calculation
                $storedGrossInr = (float) $deposit->amount_inr;
                $storedFeePercent = (float) ($deposit->fee_percent ?? 0.00);
                $storedFeeInr = (float) ($deposit->fee_amount_inr ?? round(($storedGrossInr * $storedFeePercent) / 100, 2));
                $storedNetInr = (float) ($deposit->net_amount_inr > 0 ? $deposit->net_amount_inr : round(max(0, $storedGrossInr - $storedFeeInr), 2));
                $storedRate = (float) ($deposit->exchange_rate > 0 ? $deposit->exchange_rate : 83.50);

                $usdCredit = (float) ($deposit->expected_usd_amount > 0 ? $deposit->expected_usd_amount : round($storedNetInr / $storedRate, 2));
            }

            if ($usdCredit <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Calculated USD credit amount is invalid ($0.00).',
                ], 422);
            }

            // Sync deposit fee columns
            $deposit->fee_percent = $feePercent;
            $deposit->fee_amount_inr = $feeAmount;
            $deposit->net_amount_inr = $usdCredit;
            $deposit->expected_usd_amount = $usdCredit;

            // 1. Credit Member's Fund Wallet (p2p_wallet)
            $previousP2pBalance = (float) ($member->p2p_wallet ?? 0.00);
            $newP2pBalance = round($previousP2pBalance + $usdCredit, 2);
            $member->p2p_wallet = $newP2pBalance;
            $member->save();

            // 2. Mark deposit Approved with audit timestamps and admin identity
            $deposit->status = AdDeposit::STATUS_APPROVED;
            $deposit->verified_at = now();
            $deposit->verified_by = $admin ? $admin->id : null;
            if ($request->filled('admin_notes')) {
                $deposit->admin_notes = trim($request->input('admin_notes'));
            }
            $deposit->save();

            // 3. Sync corresponding record in import_funds table
            $importFund = \App\Models\ImportFund::where(function ($q) use ($deposit) {
                    if (!empty($deposit->transaction_hash)) {
                        $q->where('transaction_hash', $deposit->transaction_hash)->orWhere('txnid', $deposit->transaction_hash);
                    }
                    if (!empty($deposit->transaction_reference)) {
                        $q->orWhere('transaction_hash', $deposit->transaction_reference)->orWhere('txnid', $deposit->transaction_reference);
                    }
                    if (!empty($deposit->deposit_id)) {
                        $q->orWhere('orderid', $deposit->deposit_id);
                    }
                })
                ->first();

            if ($importFund) {
                $importFund->deposit_status = \App\Models\ImportFund::STATUS_APPROVED;
                $importFund->status = \App\Models\ImportFund::LEGACY_APPROVED;
                $importFund->verified_at = now();
                $importFund->verified_by = $admin ? $admin->id : null;
                if ($request->filled('admin_notes')) {
                    $importFund->admin_notes = trim($request->input('admin_notes'));
                }
                $importFund->save();
            }

            return response()->json([
                'success' => true,
                'message' => "Deposit #{$deposit->deposit_id} successfully approved! \${$usdCredit} USD credited to {$member->name}'s Fund Wallet.",
                'deposit' => $deposit->fresh(['member:id,name,user_id,email,p2p_wallet', 'businessPage:id,page_name,slug', 'verifiedBy:id,name,email']),
                'credited_usd_amount' => $usdCredit,
                'previous_fund_wallet' => $previousP2pBalance,
                'new_fund_wallet' => $newP2pBalance,
                'previous_ad_balance' => $previousP2pBalance,
                'new_ad_balance' => $newP2pBalance,
            ]);
        });
    }

    /**
     * Reject a pending deposit request (no funds credited).
     */
    public function rejectDeposit(Request $request, $id): JsonResponse
    {
        $admin = auth('admin')->user();

        $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return DB::transaction(function () use ($id, $admin, $request) {
            // Lock deposit row
            $deposit = AdDeposit::where('id', $id)
                ->orWhere('deposit_id', $id)
                ->lockForUpdate()
                ->first();

            if (!$deposit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Deposit request not found.',
                ], 404);
            }

            // Check if already processed
            if ($deposit->status !== AdDeposit::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => "This deposit request has already been processed (Current status: {$deposit->status}).",
                    'deposit' => $deposit,
                ], 422);
            }

            $deposit->status = AdDeposit::STATUS_REJECTED;
            $deposit->verified_at = now();
            $deposit->verified_by = $admin ? $admin->id : null;
            $deposit->rejection_reason = trim($request->input('rejection_reason', 'Deposit rejected by administrator.'));
            if ($request->filled('admin_notes')) {
                $deposit->admin_notes = trim($request->input('admin_notes'));
            }
            $deposit->save();

            // Sync corresponding record in import_funds table
            $importFund = \App\Models\ImportFund::where(function ($q) use ($deposit) {
                    if (!empty($deposit->transaction_hash)) {
                        $q->where('transaction_hash', $deposit->transaction_hash)->orWhere('txnid', $deposit->transaction_hash);
                    }
                    if (!empty($deposit->transaction_reference)) {
                        $q->orWhere('transaction_hash', $deposit->transaction_reference)->orWhere('txnid', $deposit->transaction_reference);
                    }
                    if (!empty($deposit->deposit_id)) {
                        $q->orWhere('orderid', $deposit->deposit_id);
                    }
                })
                ->first();

            if ($importFund) {
                $importFund->deposit_status = \App\Models\ImportFund::STATUS_REJECTED;
                $importFund->status = \App\Models\ImportFund::LEGACY_CANCELLED;
                $importFund->verified_at = now();
                $importFund->verified_by = $admin ? $admin->id : null;
                $importFund->rejection_reason = $deposit->rejection_reason;
                if ($request->filled('admin_notes')) {
                    $importFund->admin_notes = trim($request->input('admin_notes'));
                }
                $importFund->save();
            }

            return response()->json([
                'success' => true,
                'message' => "Deposit #{$deposit->deposit_id} has been rejected.",
                'deposit' => $deposit->fresh(['member:id,name,user_id,email,p2p_wallet', 'businessPage:id,page_name,slug', 'verifiedBy:id,name,email']),
            ]);
        });
    }

    /**
     * Re-verify a deposit on-chain (BNB Smart Chain BEP-20) and atomically credit if confirmed.
     */
    public function reverifyDeposit(Request $request, $id, \App\Services\BscTransactionVerifierService $verifier): JsonResponse
    {
        $admin = auth('admin')->user();

        $deposit = AdDeposit::where('id', $id)
            ->orWhere('deposit_id', $id)
            ->first();

        if (!$deposit) {
            return response()->json([
                'success' => false,
                'message' => 'Deposit record not found.',
            ], 404);
        }

        $txHash = $deposit->transaction_hash ?: $deposit->transaction_reference;
        if (empty($txHash)) {
            return response()->json([
                'success' => false,
                'message' => 'No transaction hash found for this deposit.',
            ], 422);
        }

        $configuredWallet = Setting::get('deposit_crypto_wallet_address', '');
        if (empty($configuredWallet)) {
            return response()->json([
                'success' => false,
                'message' => 'Deposit destination crypto wallet address is not configured in settings.',
            ], 422);
        }

        $expectedAmount = (float) ($deposit->submitted_amount ?: ($deposit->expected_usd_amount ?: $deposit->amount_inr));
        $verification = $verifier->verifyTransaction($txHash, $expectedAmount, $configuredWallet);

        if (!$verification['verified']) {
            $deposit->verification_status = $verification['status'] ?? 'failed';
            $deposit->verification_error = $verification['message'] ?? 'Verification failed';
            $deposit->save();

            return response()->json([
                'success' => false,
                'verified' => false,
                'status' => $verification['status'],
                'message' => $verification['message'],
                'deposit' => $deposit->fresh(['member:id,name,user_id,email,p2p_wallet', 'businessPage:id,page_name,slug']),
            ], 422);
        }

        // Verification succeeded!
        $verifiedAmount = (float) $verification['transferred_amount'];

        return DB::transaction(function () use ($deposit, $verifiedAmount, $verification, $admin) {
            // Lock deposit & member
            $lockedDeposit = AdDeposit::where('id', $deposit->id)->lockForUpdate()->first();
            $member = Member::where('id', $deposit->member_id)->lockForUpdate()->first();

            $previouslyApproved = $lockedDeposit->status === AdDeposit::STATUS_APPROVED;

            $lockedDeposit->status = AdDeposit::STATUS_APPROVED;
            $lockedDeposit->verified_amount = $verifiedAmount;
            $lockedDeposit->verification_status = 'verified';
            $lockedDeposit->verification_source = $verification['verification_source'] ?? 'bsc_rpc';
            $lockedDeposit->verification_error = null;
            $lockedDeposit->verification_payload = $verification;
            $lockedDeposit->sender_address = $verification['from_address'] ?? $lockedDeposit->sender_address;
            $lockedDeposit->block_number = $verification['block_number'] ?? $lockedDeposit->block_number;
            $lockedDeposit->verified_at = now();
            $lockedDeposit->verified_by = $admin ? $admin->id : null;
            $lockedDeposit->save();

            $feePercent = (float) ($lockedDeposit->fee_percent ?? Setting::get('deposit_fee_percent', 0.00));
            $netAmount = $feePercent > 0.00 ? round($verifiedAmount / (1 + ($feePercent / 100)), 2) : $verifiedAmount;
            $feeAmount = round($verifiedAmount - $netAmount, 2);

            $lockedDeposit->fee_percent = $feePercent;
            $lockedDeposit->fee_amount_inr = $feeAmount;
            $lockedDeposit->net_amount_inr = $netAmount;
            $lockedDeposit->expected_usd_amount = $netAmount;
            $lockedDeposit->save();

            if (!$previouslyApproved && $member) {
                $member->p2p_wallet = round((float) ($member->p2p_wallet ?? 0.00) + $netAmount, 2);
                $member->save();
            }

            return response()->json([
                'success' => true,
                'verified' => true,
                'message' => "Deposit #{$lockedDeposit->deposit_id} successfully verified on-chain! \${$netAmount} USD credited to {$member->name}'s Fund Wallet.",
                'deposit' => $lockedDeposit->fresh(['member:id,name,user_id,email,p2p_wallet', 'businessPage:id,page_name,slug', 'verifiedBy:id,name,email']),
                'credited_amount' => $netAmount,
            ]);
        });
    }
}
