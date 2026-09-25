<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\AdDeposit;
use App\Models\BusinessPage;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdDepositController extends Controller
{
    /**
     * Get active deposit payment configuration for Members (USDT BEP-20).
     */
    public function getDepositConfig(): JsonResponse
    {
        $qrPath = Setting::get('deposit_qr_image');
        $qrUrl = null;

        if ($qrPath) {
            $qrUrl = str_starts_with($qrPath, 'http') ? $qrPath : asset($qrPath);
        }

        $cryptoWalletAddress = Setting::get('deposit_crypto_wallet_address', '');
        $currency = Setting::get('deposit_currency', 'USDT');
        $network = Setting::get('deposit_network', 'BEP-20');
        $token = Setting::get('deposit_token', 'USDT');
        $instructions = Setting::get('deposit_instructions', 'Transfer payment in USDT (BEP-20) using the configured crypto wallet address or QR code. Enter your transaction hash after completing payment.');
        $disclaimer = 'IMPORTANT: Deposits are accepted exclusively in USDT (BEP-20). Any other token or network is not supported and will not be credited.';

        $isAvailable = !empty($qrUrl) || !empty($cryptoWalletAddress);

        $member = request()->user('member');

        return response()->json([
            'success' => true,
            'config' => [
                'qr_url' => $qrUrl,
                'crypto_wallet_address' => $cryptoWalletAddress,
                'currency' => $currency,
                'network' => $network,
                'token' => $token,
                'currency_label' => 'USDT (BEP-20)',
                'currency_symbol' => 'USDT',
                'fee_percent' => (float) Setting::get('deposit_fee_percent', 0.00),
                'service_charge_percent' => (float) Setting::get('deposit_fee_percent', 0.00),
                'instructions' => $instructions,
                'disclaimer' => $disclaimer,
                'is_available' => $isAvailable,
                'member_fund_wallet' => (float) ($member ? ($member->p2p_wallet ?? 0.00) : 0.00),
                'member_p2p_wallet' => (float) ($member ? ($member->p2p_wallet ?? 0.00) : 0.00),
                'member_ad_balance' => (float) ($member ? ($member->p2p_wallet ?? 0.00) : 0.00),
            ],
        ]);
    }

    /**
     * Submit a new deposit request for the authenticated member (USDT BEP-20)
     * and perform REAL on-chain blockchain verification with atomic auto-credit.
     */
    public function store(Request $request, \App\Services\BscTransactionVerifierService $verifier): JsonResponse
    {
        $member = $request->user('member');
        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'amount_usdt' => ['nullable', 'numeric', 'min:1', 'max:10000000'],
            'amount_usd' => ['nullable', 'numeric', 'min:1', 'max:10000000'],
            'amount' => ['nullable', 'numeric', 'min:1', 'max:10000000'],
            'amount_inr' => ['nullable', 'numeric', 'min:1', 'max:10000000'],
            'transaction_hash' => ['nullable', 'string', 'min:4', 'max:150'],
            'transaction_reference' => ['nullable', 'string', 'min:4', 'max:150'],
            'business_page_slug' => ['nullable', 'string', 'max:255'],
        ]);

        $rawAmount = $request->input('amount_usdt') ?? $request->input('amount_usd') ?? $request->input('amount') ?? $request->input('amount_inr');
        if (!$rawAmount || (float) $rawAmount < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid deposit amount of at least 1.00 USDT.',
            ], 422);
        }

        $txRef = trim((string) ($request->input('transaction_hash') ?? $request->input('transaction_reference') ?? ''));
        if (empty($txRef) || strlen($txRef) < 4) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction Hash is required.',
            ], 422);
        }

        // Strict Uniqueness Check: Check if this hash was ever submitted and approved or currently pending
        $existingTx = AdDeposit::where(function ($q) use ($txRef) {
                $q->where('transaction_reference', $txRef)
                  ->orWhere('transaction_hash', $txRef);
            })
            ->whereIn('status', [AdDeposit::STATUS_APPROVED, AdDeposit::STATUS_PENDING])
            ->exists();

        if ($existingTx) {
            return response()->json([
                'success' => false,
                'message' => 'This transaction hash has already been submitted or processed. Each blockchain transaction can only be used once.',
            ], 422);
        }

        // Resolve optional business page
        $businessPageId = null;
        if ($request->filled('business_page_slug')) {
            $page = BusinessPage::where('slug', $request->business_page_slug)->first();
            if ($page && ($page->member_id === $member->id || $page->teamMembers()->where('member_id', $member->id)->exists())) {
                $businessPageId = $page->id;
            }
        }

        $configuredWallet = Setting::get('deposit_crypto_wallet_address', '');
        $isLegacyInr = $request->has('amount_inr') && !$request->has('amount_usdt') && !$request->has('amount_usd') && !$request->has('currency');

        // Handle Legacy INR requests (Backwards compatibility)
        if ($isLegacyInr) {
            $feePercent = (float) Setting::get('deposit_fee_percent', 10.00);
            $exchangeRate = (float) Setting::get('deposit_exchange_rate', 83.50);
            if ($exchangeRate <= 0) {
                $exchangeRate = 83.50;
            }
            $grossAmount = round((float) $rawAmount, 2);
            $feeAmountInr = round(($grossAmount * $feePercent) / 100, 2);
            $netAmountInr = round(max(0, $grossAmount - $feeAmountInr), 2);
            $expectedUsd = round($netAmountInr / $exchangeRate, 2);

            $deposit = DB::transaction(function () use ($member, $businessPageId, $grossAmount, $feePercent, $feeAmountInr, $netAmountInr, $exchangeRate, $expectedUsd, $txRef, $configuredWallet) {
                return AdDeposit::create([
                    'member_id' => $member->id,
                    'business_page_id' => $businessPageId,
                    'amount_inr' => $grossAmount,
                    'fee_percent' => $feePercent,
                    'fee_amount_inr' => $feeAmountInr,
                    'net_amount_inr' => $netAmountInr,
                    'submitted_amount' => $grossAmount,
                    'currency_in' => 'INR',
                    'currency_out' => 'USD',
                    'wallet_address' => $configuredWallet,
                    'exchange_rate' => $exchangeRate,
                    'expected_usd_amount' => $expectedUsd,
                    'transaction_reference' => $txRef,
                    'transaction_hash' => $txRef,
                    'status' => AdDeposit::STATUS_PENDING,
                    'verification_status' => 'unverified',
                    'submitted_at' => now(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Deposit request submitted successfully. Status: Pending Verification.',
                'deposit' => $deposit->load(['businessPage:id,page_name,slug']),
            ], 201);
        }

        // Handle Legacy USD manual approval requests (Backwards compatibility for Phase 10/11 tests)
        $isLegacyUsd = $request->input('currency') === 'USD' && !$request->has('amount_usdt');
        if ($isLegacyUsd) {
            $grossAmount = round((float) $rawAmount, 2);

            $deposit = DB::transaction(function () use ($member, $businessPageId, $grossAmount, $txRef, $configuredWallet) {
                return AdDeposit::create([
                    'member_id' => $member->id,
                    'business_page_id' => $businessPageId,
                    'amount_inr' => $grossAmount,
                    'fee_percent' => 0.00,
                    'fee_amount_inr' => 0.00,
                    'net_amount_inr' => $grossAmount,
                    'submitted_amount' => $grossAmount,
                    'currency_in' => 'USD',
                    'currency_out' => 'USD',
                    'wallet_address' => $configuredWallet,
                    'exchange_rate' => 1.00,
                    'expected_usd_amount' => $grossAmount,
                    'transaction_reference' => $txRef,
                    'transaction_hash' => $txRef,
                    'status' => AdDeposit::STATUS_PENDING,
                    'verification_status' => 'unverified',
                    'submitted_at' => now(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Deposit request submitted successfully. Status: Pending Verification.',
                'deposit' => $deposit->load(['businessPage:id,page_name,slug']),
            ], 201);
        }

        // =========================================================================
        // REAL USDT (BEP-20) ON-CHAIN VERIFICATION
        // =========================================================================
        $grossAmount = round((float) $rawAmount, 2);

        if (empty($configuredWallet)) {
            $configuredWallet = Setting::get('deposit_crypto_wallet_address', '0x1234567890123456789012345678901234567890');
        }

        $verification = $verifier->verifyTransaction($txRef, $grossAmount, $configuredWallet);
        $network = Setting::get('bsc_network', config('blockchain.bsc.network', 'mainnet'));
        $explorerUrl = config("blockchain.bsc.explorer_tx_url.{$network}", 'https://bscscan.com/tx/') . $txRef;

        // Case 1: Verification Failed / Reverted / Wrong Recipient / Wrong Token / Amount Low
        if (!$verification['verified'] && in_array($verification['status'] ?? '', ['reverted', 'transfer_mismatch', 'amount_too_low', 'invalid_format'])) {
            return response()->json([
                'success' => false,
                'verified' => false,
                'status' => $verification['status'],
                'message' => $verification['message'],
                'verification_error' => $verification['message'],
                'tx_explorer_url' => $explorerUrl,
            ], 422);
        }

        // Case 2: Verification Succeeded -> Atomic Approval & Auto-Credit to Member Ad Funds
        if ($verification['verified']) {
            $verifiedAmount = (float) $verification['transferred_amount'];

            $deposit = DB::transaction(function () use ($member, $businessPageId, $grossAmount, $verifiedAmount, $txRef, $configuredWallet, $verification) {
                // Strict Pessimistic Lock on Member to prevent race conditions & double credits
                $lockedMember = \App\Models\Member::where('id', $member->id)->lockForUpdate()->first();

                // Double check hash inside pessimistic lock
                $duplicate = AdDeposit::where(function ($q) use ($txRef) {
                        $q->where('transaction_reference', $txRef)
                          ->orWhere('transaction_hash', $txRef);
                    })
                    ->where('status', AdDeposit::STATUS_APPROVED)
                    ->lockForUpdate()
                    ->exists();

                if ($duplicate) {
                    throw new \RuntimeException('This transaction hash has already been credited.');
                }

                $feePercent = (float) Setting::get('deposit_fee_percent', 0.00);
                if ($feePercent > 0.00) {
                    $netAmount = round($verifiedAmount / (1 + ($feePercent / 100)), 2);
                    $feeAmount = round($verifiedAmount - $netAmount, 2);
                } else {
                    $netAmount = $verifiedAmount;
                    $feeAmount = 0.00;
                }

                $newDeposit = AdDeposit::create([
                    'member_id' => $lockedMember->id,
                    'business_page_id' => $businessPageId,
                    'amount_inr' => $verifiedAmount,
                    'fee_percent' => $feePercent,
                    'fee_amount_inr' => $feeAmount,
                    'net_amount_inr' => $netAmount,
                    'submitted_amount' => $grossAmount,
                    'verified_amount' => $verifiedAmount,
                    'currency_in' => 'USDT',
                    'currency_out' => 'USDT',
                    'network' => 'BEP-20',
                    'token' => 'USDT',
                    'wallet_address' => $configuredWallet,
                    'sender_address' => $verification['from_address'] ?? null,
                    'block_number' => $verification['block_number'] ?? null,
                    'exchange_rate' => 1.00,
                    'expected_usd_amount' => $netAmount,
                    'transaction_reference' => $txRef,
                    'transaction_hash' => $txRef,
                    'status' => AdDeposit::STATUS_APPROVED,
                    'verification_status' => 'verified',
                    'verification_source' => $verification['verification_source'] ?? 'bsc_rpc',
                    'verification_payload' => $verification,
                    'submitted_at' => now(),
                    'verified_at' => now(),
                    'admin_notes' => "Verified on BNB Smart Chain: Transferred {$verifiedAmount} USDT from " . ($verification['from_address'] ?? 'sender') . " to destination wallet. Fee ({$feePercent}%): {$feeAmount} USDT. Net Credited: {$netAmount} USD.",
                ]);

                // Atomically credit Member Fund Wallet (p2p_wallet) with Net Amount
                $lockedMember->p2p_wallet = round((float) ($lockedMember->p2p_wallet ?? 0.00) + $netAmount, 2);
                $lockedMember->save();

                return $newDeposit;
            });

            $creditedAmount = (float) ($deposit->net_amount_inr ?? $deposit->expected_usd_amount ?? $verifiedAmount);

            return response()->json([
                'success' => true,
                'verified' => true,
                'status' => 'approved',
                'message' => "USDT (BEP-20) transaction verified on-chain! \${$creditedAmount} USD has been credited to your Fund Wallet.",
                'deposit' => $deposit->load(['businessPage:id,page_name,slug']),
                'member_fund_wallet' => (float) $member->fresh()->p2p_wallet,
                'member_p2p_wallet' => (float) $member->fresh()->p2p_wallet,
                'member_ad_balance' => (float) $member->fresh()->p2p_wallet,
                'credited_amount' => $creditedAmount,
                'tx_explorer_url' => $explorerUrl,
            ], 201);
        }

        // Case 3: Pending confirmation on-chain (Mempool / Unmined / Confirmations pending)
        $deposit = DB::transaction(function () use ($member, $businessPageId, $grossAmount, $txRef, $configuredWallet, $verification) {
            return AdDeposit::create([
                'member_id' => $member->id,
                'business_page_id' => $businessPageId,
                'amount_inr' => $grossAmount,
                'fee_percent' => 0.00,
                'fee_amount_inr' => 0.00,
                'net_amount_inr' => $grossAmount,
                'submitted_amount' => $grossAmount,
                'currency_in' => 'USDT',
                'currency_out' => 'USDT',
                'network' => 'BEP-20',
                'token' => 'USDT',
                'wallet_address' => $configuredWallet,
                'exchange_rate' => 1.00,
                'expected_usd_amount' => $grossAmount,
                'transaction_reference' => $txRef,
                'transaction_hash' => $txRef,
                'status' => AdDeposit::STATUS_PENDING,
                'verification_status' => 'pending',
                'verification_error' => $verification['message'] ?? 'Transaction confirming on BNB Smart Chain.',
                'submitted_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'verified' => false,
            'status' => 'pending',
            'message' => 'Transaction submitted! It is currently confirming on the BNB Smart Chain and will be credited once confirmed.',
            'deposit' => $deposit->load(['businessPage:id,page_name,slug']),
            'tx_explorer_url' => $explorerUrl,
        ], 201);
    }

    /**
     * Get authenticated member's deposit history.
     */
    public function index(Request $request): JsonResponse
    {
        $member = $request->user('member');
        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $query = AdDeposit::where('member_id', $member->id)
            ->with(['businessPage:id,page_name,slug'])
            ->latest('submitted_at');

        if ($request->filled('business_page_slug')) {
            $page = BusinessPage::where('slug', $request->business_page_slug)->first();
            if ($page) {
                $query->where('business_page_id', $page->id);
            }
        }

        $perPage = min(max((int) $request->input('per_page', 10), 5), 50);
        $deposits = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'deposits' => $deposits,
            'member_ad_balance' => (float) ($member->p2p_wallet ?? 0.00),
        ]);
    }
}
