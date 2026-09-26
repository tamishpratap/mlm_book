<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\AdDeposit;
use App\Models\ImportFund;
use App\Models\Member;
use App\Models\Setting;
use App\Services\BscTransactionVerifierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DepositVerificationController extends Controller
{
    /**
     * Get active deposit configuration for Member User Panel (USDT BEP-20).
     */
    public function getDepositConfig(Request $request): JsonResponse
    {
        $member = $request->user('member') ?? $request->user();

        $network = Setting::get('bsc_network', config('blockchain.bsc.network', 'mainnet'));
        $usdtContract = Setting::get('bsc_usdt_contract', config("blockchain.bsc.usdt_contract.{$network}", BscTransactionVerifierService::DEFAULT_MAINNET_USDT_CONTRACT));
        $cryptoWalletAddress = Setting::get('deposit_crypto_wallet_address', '');
        $qrPath = Setting::get('deposit_qr_image');
        $qrUrl = null;
        if ($qrPath) {
            $qrUrl = str_starts_with($qrPath, 'http') ? $qrPath : asset($qrPath);
        }
        $instructions = Setting::get('deposit_instructions', 'Transfer payment in USDT (BEP-20) using the configured crypto wallet address or QR code. Enter your transaction hash after completing payment.');
        $chainId = $network === 'testnet' ? 97 : 56;
        $chainIdHex = '0x' . dechex($chainId);
        $minDeposit = 10.00;
        $isTestMode = (bool) config('blockchain.bsc.test_mode', env('APP_ENV') === 'local');
        $isLocal = app()->environment('local', 'testing');
        $activeTestMode = $isTestMode && $isLocal;
        $feePercent = (float) Setting::get('deposit_fee_percent', 0.00);
        $stats = $this->getMemberDepositStats($member);

        return response()->json([
            'success' => true,
            'config' => [
                'currency' => 'USDT',
                'network' => 'BEP-20',
                'network_name' => $network === 'testnet' ? 'BNB Smart Chain Testnet' : 'BNB Smart Chain Mainnet',
                'chain_id' => $chainId,
                'chain_id_hex' => $chainIdHex,
                'usdt_contract' => $usdtContract,
                'crypto_wallet_address' => $cryptoWalletAddress,
                'qr_code_image' => $qrPath,
                'qr_code_url' => $qrUrl,
                'qr_url' => $qrUrl,
                'deposit_instructions' => $instructions,
                'deposit_fee_percent' => $feePercent,
                'service_charge_percent' => $feePercent,
                'min_deposit' => $minDeposit,
                'min_deposit_label' => '$10.00 USD equivalent',
                'is_test_mode' => $activeTestMode,
                'member_id' => $member ? $member->id : null,
                'member_user_id' => $member ? $member->user_id : null,
                'member_name' => $member ? $member->name : null,
                'member_fund_wallet' => $stats['fund_wallet'],
                'member_p2p_wallet' => $stats['p2p_wallet'],
                'member_ad_balance' => (float) ($stats['fund_wallet'] ?? 0.00),
                'member_wallet_address' => $member ? ($member->wallet_address ?? '') : '',
                'min_withdrawal_amount' => (float) Setting::get('minimum_withdrawal_amount', 5.00),
                'todays_deposit' => $stats['todays_deposit'],
                'total_deposit' => $stats['total_deposit'],
                'pending_deposit' => $stats['pending_deposit'],
                'stats' => $stats,
                'explorer_url' => config("blockchain.bsc.explorer_tx_url.{$network}", 'https://bscscan.com/tx/'),
            ],
        ]);
    }

    /**
     * Independent verification endpoint for Section 2 (Manual Deposit Request).
     * Verifies that the entered transaction hash corresponds to an actual blockchain transaction
     * and that the entered amount matches the on-chain transfer.
     * DOES NOT create a record or submit to admin yet.
     */
    public function verify(Request $request, BscTransactionVerifierService $verifier): JsonResponse
    {
        $member = $request->user('member') ?? $request->user();
        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'amount' => ['required', 'numeric', 'min:10', 'max:10000000'],
            'transaction_hash' => ['required', 'string', 'regex:/^0x[a-fA-F0-9]{64}$/'],
            'wallet_address' => ['nullable', 'string', 'regex:/^0x[a-fA-F0-9]{40}$/'],
        ], [
            'amount.min' => 'Minimum deposit amount is $10 USD equivalent.',
            'transaction_hash.regex' => 'Invalid transaction hash format. Must be a 66-character hexadecimal hash starting with 0x.',
        ]);

        $amount = round((float) $request->input('amount'), 2);
        $txHash = trim($request->input('transaction_hash'));
        $walletAddress = $request->input('wallet_address') ? trim($request->input('wallet_address')) : null;

        // 1. Check if hash was already approved or credited anywhere
        $alreadyCredited = ImportFund::where(function ($q) use ($txHash) {
                $q->where('transaction_hash', $txHash)->orWhere('txnid', $txHash);
            })
            ->where('deposit_status', ImportFund::STATUS_APPROVED)
            ->exists()
            || AdDeposit::where(function ($q) use ($txHash) {
                $q->where('transaction_hash', $txHash)->orWhere('transaction_reference', $txHash);
            })
            ->where('status', AdDeposit::STATUS_APPROVED)
            ->exists();

        if ($alreadyCredited) {
            return response()->json([
                'success' => false,
                'verified' => false,
                'status' => 'already_credited',
                'message' => 'This transaction hash has already been credited. Each blockchain transaction can only be used once.',
            ], 422);
        }

        // 2. Check if hash is already pending / submitted
        $alreadyPending = ImportFund::where(function ($q) use ($txHash) {
                $q->where('transaction_hash', $txHash)->orWhere('txnid', $txHash);
            })
            ->whereIn('deposit_status', [ImportFund::STATUS_PENDING, ImportFund::STATUS_VERIFIED])
            ->exists()
            || AdDeposit::where(function ($q) use ($txHash) {
                $q->where('transaction_hash', $txHash)->orWhere('transaction_reference', $txHash);
            })
            ->where('status', AdDeposit::STATUS_PENDING)
            ->exists();

        if ($alreadyPending) {
            return response()->json([
                'success' => false,
                'verified' => false,
                'status' => 'already_submitted',
                'message' => 'This transaction hash has already been submitted and is currently awaiting admin approval.',
            ], 422);
        }

        // 3. Perform on-chain blockchain verification
        $recipientWallet = Setting::get('deposit_crypto_wallet_address', '');
        if (empty($recipientWallet)) {
            $recipientWallet = '0x55d398326f99059fF775485246999027B3197955'; // Fallback for testing
        }

        $feePercent = (float) Setting::get('deposit_fee_percent', 0.00);

        $verification = $verifier->verifyTransaction(
            $txHash,
            $amount,
            $recipientWallet,
            10.00,
            true, // require exact amount match
            $walletAddress
        );

        // If not matched directly and feePercent > 0, check alternative matching
        // (e.g. user entered desired base amount, on-chain was base * (1 + fee/100))
        if (!$verification['verified'] && $feePercent > 0.00) {
            $possibleTotals = [
                round($amount * (1 + ($feePercent / 100)), 2),
                round($amount / (1 + ($feePercent / 100)), 2),
            ];

            foreach ($possibleTotals as $altAmount) {
                if ($altAmount >= 10.00 && abs($altAmount - $amount) > 0.01) {
                    $altVerification = $verifier->verifyTransaction(
                        $txHash,
                        $altAmount,
                        $recipientWallet,
                        10.00,
                        true,
                        $walletAddress
                    );
                    if ($altVerification['verified']) {
                        $verification = $altVerification;
                        break;
                    }
                }
            }
        }

        if (!$verification['verified']) {
            return response()->json([
                'success' => false,
                'verified' => false,
                'status' => $verification['status'] ?? 'verification_failed',
                'message' => $verification['message'] ?? 'Blockchain verification failed.',
                'verification_error' => $verification['message'] ?? null,
            ], 422);
        }

        $network = Setting::get('bsc_network', config('blockchain.bsc.network', 'mainnet'));
        $explorerUrl = config("blockchain.bsc.explorer_tx_url.{$network}", 'https://bscscan.com/tx/') . $txHash;

        $verifiedAmount = (float) $verification['transferred_amount'];

        // Internal calculation formula: Net Amount = Received Amount / (1 + service_charge / 100)
        // Equivalent to: (Received Amount / (100 + service charge)) * 100
        if ($feePercent > 0.00) {
            $netAmount = round($verifiedAmount / (1 + ($feePercent / 100)), 2);
            $feeAmount = round($verifiedAmount - $netAmount, 2);
        } else {
            $netAmount = $verifiedAmount;
            $feeAmount = 0.00;
        }

        return response()->json([
            'success' => true,
            'verified' => true,
            'status' => 'verified',
            'message' => 'Transaction Verified Successfully. You can now submit your deposit request to the admin.',
            'data' => [
                'amount' => $verifiedAmount,
                'transferred_amount' => $verifiedAmount,
                'base_amount' => $netAmount,
                'net_amount' => $netAmount,
                'net_credit' => $netAmount,
                'fee_amount' => $feeAmount,
                'fee_percent' => $feePercent,
                'service_charge_percent' => $feePercent,
                'transaction_hash' => $txHash,
                'wallet_address' => $verification['from_address'],
                'recipient_address' => $verification['to_address'],
                'network' => $verification['network'] ?? 'BEP-20',
                'token' => $verification['token'] ?? 'USDT',
                'contract_address' => $verification['contract_address'] ?? null,
                'block_number' => $verification['block_number'] ?? null,
                'confirmations' => $verification['confirmations'] ?? 1,
                'explorer_url' => $explorerUrl,
                'transaction_status' => 'Success',
            ],
        ]);
    }

    /**
     * Submit verified manual deposit request to Admin Panel.
     * Records transaction in `import_funds` (and `ad_deposits` for unified admin management).
     */
    public function submitManualRequest(Request $request, BscTransactionVerifierService $verifier): JsonResponse
    {
        $member = $request->user('member') ?? $request->user();
        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'amount' => ['required', 'numeric', 'min:10', 'max:10000000'],
            'transaction_hash' => ['required', 'string', 'regex:/^0x[a-fA-F0-9]{64}$/'],
            'wallet_address' => ['nullable', 'string', 'regex:/^0x[a-fA-F0-9]{40}$/'],
        ], [
            'amount.min' => 'Minimum deposit amount is $10 USD equivalent.',
            'transaction_hash.regex' => 'Invalid transaction hash format. Must be a 66-character hexadecimal hash starting with 0x.',
            'wallet_address.regex' => 'Invalid wallet address format. Must be a valid 42-character BSC (BEP-20) address starting with 0x.',
        ]);

        $amount = round((float) $request->input('amount'), 2);
        $txHash = trim($request->input('transaction_hash'));
        $walletAddress = $request->input('wallet_address') ? trim($request->input('wallet_address')) : null;

        $recipientWallet = Setting::get('deposit_crypto_wallet_address', '');
        if (empty($recipientWallet)) {
            $recipientWallet = '0x55d398326f99059fF775485246999027B3197955';
        }

        $feePercent = (float) Setting::get('deposit_fee_percent', 0.00);

        // Re-verify on-chain (zero-trust backend rule)
        $verification = $verifier->verifyTransaction(
            $txHash,
            $amount,
            $recipientWallet,
            10.00,
            true,
            $walletAddress
        );

        if (!$verification['verified'] && $feePercent > 0.00) {
            $possibleTotals = [
                round($amount * (1 + ($feePercent / 100)), 2),
                round($amount / (1 + ($feePercent / 100)), 2),
            ];

            foreach ($possibleTotals as $altAmount) {
                if ($altAmount >= 10.00 && abs($altAmount - $amount) > 0.01) {
                    $altVerification = $verifier->verifyTransaction(
                        $txHash,
                        $altAmount,
                        $recipientWallet,
                        10.00,
                        true,
                        $walletAddress
                    );
                    if ($altVerification['verified']) {
                        $verification = $altVerification;
                        break;
                    }
                }
            }
        }

        if (!$verification['verified']) {
            return response()->json([
                'success' => false,
                'status' => $verification['status'] ?? 'verification_failed',
                'message' => $verification['message'] ?? 'Transaction verification failed on BNB Smart Chain.',
            ], 422);
        }

        return DB::transaction(function () use ($member, $amount, $txHash, $verification, $recipientWallet) {
            // Lock and check duplicate inside transaction
            $existing = ImportFund::where(function ($q) use ($txHash) {
                    $q->where('transaction_hash', $txHash)->orWhere('txnid', $txHash);
                })
                ->whereIn('deposit_status', [ImportFund::STATUS_APPROVED, ImportFund::STATUS_VERIFIED, ImportFund::STATUS_PENDING])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'This transaction hash has already been submitted or processed.',
                ], 422);
            }

            $fromAddress = $verification['from_address'] ?? null;
            $verifiedAmount = (float) $verification['transferred_amount'];

            // Calculate dynamic Service Charge / Platform Fee on top:
            // Formula requested: Net Amount = Received Amount / (1 + feePercent / 100)
            $feePercent = (float) Setting::get('deposit_fee_percent', 0.00);
            if ($feePercent > 0.00) {
                $netAmount = round($verifiedAmount / (1 + ($feePercent / 100)), 2);
                $feeAmount = round($verifiedAmount - $netAmount, 2);
            } else {
                $netAmount = $verifiedAmount;
                $feeAmount = 0.00;
            }

            // 1. Create record in import_funds
            $importFund = ImportFund::create([
                'memberid' => substr($member->user_id, 0, 20),
                'user_id' => $member->user_id,
                'member_id' => $member->id,
                'txnid' => substr($txHash, 0, 100),
                'transaction_hash' => $txHash,
                'amount' => $netAmount,
                'type' => 'Add',
                'wallet_type' => 'USDT',
                'wallet_address' => $fromAddress,
                'network' => $verification['network'] ?? 'BEP-20',
                'token' => $verification['token'] ?? 'USDT',
                'contract_address' => $verification['contract_address'] ?? null,
                'added_by' => 'User',
                'status' => ImportFund::LEGACY_PENDING,
                'verification_status' => 'verified',
                'deposit_status' => ImportFund::STATUS_VERIFIED, // Verified — Awaiting Admin Approval
                'verification_payload' => $verification,
                'admin_notes' => "Manual request verified on-chain: {$verifiedAmount} USDT. Service Charge ({$feePercent}%): {$feeAmount} USDT. Net: {$netAmount} USD.",
                'mode' => 'Mannual',
                'verified_at' => now(),
            ]);

            // 2. Also register in ad_deposits to allow unified admin review
            $adDeposit = AdDeposit::create([
                'member_id' => $member->id,
                'amount_inr' => $verifiedAmount,
                'fee_percent' => $feePercent,
                'fee_amount_inr' => $feeAmount,
                'net_amount_inr' => $netAmount,
                'submitted_amount' => $amount,
                'verified_amount' => $verifiedAmount,
                'currency_in' => 'USDT',
                'currency_out' => 'USDT',
                'network' => 'BEP-20',
                'token' => 'USDT',
                'wallet_address' => $recipientWallet,
                'sender_address' => $fromAddress,
                'block_number' => $verification['block_number'] ?? null,
                'exchange_rate' => 1.00,
                'expected_usd_amount' => $netAmount,
                'transaction_reference' => $txHash,
                'transaction_hash' => $txHash,
                'status' => AdDeposit::STATUS_PENDING,
                'verification_status' => 'verified',
                'verification_source' => $verification['verification_source'] ?? 'bsc_rpc',
                'verification_payload' => $verification,
                'submitted_at' => now(),
                'admin_notes' => "Manual request verified on-chain: {$verifiedAmount} USDT to {$recipientWallet}. Service Charge ({$feePercent}%): {$feeAmount} USDT. Net: {$netAmount} USD. Awaiting Admin Approval.",
            ]);

            $network = Setting::get('bsc_network', config('blockchain.bsc.network', 'mainnet'));
            $explorerUrl = config("blockchain.bsc.explorer_tx_url.{$network}", 'https://bscscan.com/tx/') . $txHash;

            return response()->json([
                'success' => true,
                'status' => 'verified',
                'message' => "Your deposit request has been submitted to the Admin Panel. Status: Verified — Awaiting Admin Approval (Net Credit: \${$netAmount} USD).",
                'deposit' => [
                    'id' => $importFund->id,
                    'orderid' => $importFund->orderid,
                    'amount' => $verifiedAmount,
                    'net_amount' => $netAmount,
                    'fee_amount' => $feeAmount,
                    'fee_percent' => $feePercent,
                    'transaction_hash' => $txHash,
                    'wallet_address' => $fromAddress,
                    'network' => 'BEP-20',
                    'token' => 'USDT',
                    'deposit_status' => ImportFund::STATUS_VERIFIED,
                    'status_label' => 'Verified — Awaiting Admin Approval',
                    'explorer_url' => $explorerUrl,
                    'created_at' => $importFund->created_at->toISOString(),
                ],
            ], 201);
        });
    }

    /**
     * Submit DApp direct deposit transaction.
     * The user's wallet has executed the transaction; backend verifies on-chain and stores record.
     */
    public function submitDappDeposit(Request $request, BscTransactionVerifierService $verifier): JsonResponse
    {
        $member = $request->user('member') ?? $request->user();
        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'amount' => ['required', 'numeric', 'min:10', 'max:10000000'],
            'transaction_hash' => ['required', 'string', 'regex:/^0x[a-fA-F0-9]{64}$/'],
            'wallet_address' => ['nullable', 'string', 'regex:/^0x[a-fA-F0-9]{40}$/'],
        ], [
            'amount.min' => 'Minimum deposit amount is $10 USD equivalent.',
            'transaction_hash.regex' => 'Invalid transaction hash format.',
        ]);

        $amount = round((float) $request->input('amount'), 2);
        $txHash = trim($request->input('transaction_hash'));
        $walletAddress = $request->input('wallet_address') ? trim($request->input('wallet_address')) : null;

        $recipientWallet = Setting::get('deposit_crypto_wallet_address', '');
        if (empty($recipientWallet)) {
            $recipientWallet = '0x55d398326f99059fF775485246999027B3197955';
        }

        // On-chain verification
        $verification = $verifier->verifyTransaction(
            $txHash,
            $amount,
            $recipientWallet,
            10.00,
            true,
            $walletAddress
        );

        if (!$verification['verified']) {
            $rejectionReason = $verification['message'] ?? 'Blockchain on-chain verification failed.';

            // Record rejected attempt in import_funds & ad_deposits for admin audit log
            try {
                ImportFund::create([
                    'memberid' => substr($member->user_id, 0, 20),
                    'user_id' => $member->user_id,
                    'member_id' => $member->id,
                    'txnid' => substr($txHash, 0, 100),
                    'transaction_hash' => $txHash,
                    'amount' => $amount,
                    'type' => 'Add',
                    'wallet_type' => 'USDT',
                    'wallet_address' => $walletAddress,
                    'network' => 'BEP-20',
                    'token' => 'USDT',
                    'added_by' => 'User',
                    'status' => ImportFund::LEGACY_REJECTED,
                    'verification_status' => 'failed',
                    'deposit_status' => ImportFund::STATUS_REJECTED,
                    'verification_payload' => $verification,
                    'rejection_reason' => $rejectionReason,
                    'admin_notes' => "Web3 DApp Instant Deposit rejected on-chain: {$rejectionReason}",
                    'mode' => 'Online',
                ]);

                AdDeposit::create([
                    'member_id' => $member->id,
                    'amount_inr' => $amount,
                    'submitted_amount' => $amount,
                    'verified_amount' => 0.00,
                    'currency_in' => 'USDT',
                    'currency_out' => 'USDT',
                    'network' => 'BEP-20',
                    'token' => 'USDT',
                    'wallet_address' => $recipientWallet,
                    'sender_address' => $walletAddress,
                    'exchange_rate' => 1.00,
                    'expected_usd_amount' => $amount,
                    'transaction_reference' => $txHash,
                    'transaction_hash' => $txHash,
                    'status' => AdDeposit::STATUS_REJECTED,
                    'verification_status' => 'failed',
                    'verification_source' => $verification['verification_source'] ?? 'bsc_rpc',
                    'verification_error' => $rejectionReason,
                    'verification_payload' => $verification,
                    'rejection_reason' => $rejectionReason,
                    'submitted_at' => now(),
                    'admin_notes' => "Web3 DApp Instant Deposit rejected on-chain: {$rejectionReason}",
                ]);
            } catch (\Throwable $e) {
                // Ignore duplicate error on rejected attempt logging
            }

            return response()->json([
                'success' => false,
                'status' => 'rejected',
                'message' => $rejectionReason,
            ], 422);
        }

        return DB::transaction(function () use ($member, $amount, $txHash, $verification, $recipientWallet) {
            // Strict duplicate check inside transaction
            $existing = ImportFund::where(function ($q) use ($txHash) {
                    $q->where('transaction_hash', $txHash)->orWhere('txnid', $txHash);
                })
                ->whereIn('deposit_status', [ImportFund::STATUS_APPROVED, ImportFund::STATUS_VERIFIED, ImportFund::STATUS_PENDING])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'This transaction hash has already been recorded.',
                ], 422);
            }

            $fromAddress = $verification['from_address'] ?? null;
            $verifiedAmount = (float) $verification['transferred_amount'];

            // Calculate dynamic Service Charge / Platform Fee on top:
            // Formula: Net Amount = Received Amount / (1 + feePercent / 100)
            $feePercent = (float) Setting::get('deposit_fee_percent', 0.00);
            if ($feePercent > 0.00) {
                $netAmount = round($verifiedAmount / (1 + ($feePercent / 100)), 2);
                $feeAmount = round($verifiedAmount - $netAmount, 2);
            } else {
                $netAmount = $verifiedAmount;
                $feeAmount = 0.00;
            }

            // 1. Atomically Credit Member's Fund Wallet (p2p_wallet) immediately!
            $lockedMember = \App\Models\Member::where('id', $member->id)->lockForUpdate()->first();
            $previousP2pBalance = (float) ($lockedMember->p2p_wallet ?? 0.00);
            $newP2pBalance = round($previousP2pBalance + $netAmount, 2);
            $lockedMember->p2p_wallet = $newP2pBalance;
            $lockedMember->save();

            // 2. Record in import_funds table as APPROVED
            $importFund = ImportFund::create([
                'memberid' => substr($member->user_id, 0, 20),
                'user_id' => $member->user_id,
                'member_id' => $member->id,
                'txnid' => substr($txHash, 0, 100),
                'transaction_hash' => $txHash,
                'amount' => $netAmount,
                'type' => 'Add',
                'wallet_type' => 'USDT',
                'wallet_address' => $fromAddress,
                'network' => $verification['network'] ?? 'BEP-20',
                'token' => $verification['token'] ?? 'USDT',
                'contract_address' => $verification['contract_address'] ?? null,
                'added_by' => 'User',
                'status' => ImportFund::LEGACY_APPROVED, // 'Approved'
                'verification_status' => 'verified',
                'deposit_status' => ImportFund::STATUS_APPROVED, // 'approved'
                'verification_payload' => $verification,
                'admin_notes' => "Web3 DApp Instant Deposit: Auto-approved on-chain. {$verifiedAmount} USDT from {$fromAddress}. Net: \${$netAmount} USD credited directly to Fund Wallet.",
                'mode' => 'Online',
                'verified_at' => now(),
            ]);

            // 3. Register in ad_deposits table as APPROVED (No pending approval needed by admin)
            $adDeposit = AdDeposit::create([
                'member_id' => $member->id,
                'amount_inr' => $verifiedAmount,
                'fee_percent' => $feePercent,
                'fee_amount_inr' => $feeAmount,
                'net_amount_inr' => $netAmount,
                'submitted_amount' => $amount,
                'verified_amount' => $verifiedAmount,
                'currency_in' => 'USDT',
                'currency_out' => 'USDT',
                'network' => 'BEP-20',
                'token' => 'USDT',
                'wallet_address' => $recipientWallet,
                'sender_address' => $fromAddress,
                'block_number' => $verification['block_number'] ?? null,
                'exchange_rate' => 1.00,
                'expected_usd_amount' => $netAmount,
                'transaction_reference' => $txHash,
                'transaction_hash' => $txHash,
                'status' => AdDeposit::STATUS_APPROVED, // 'approved'
                'verification_status' => 'verified',
                'verification_source' => $verification['verification_source'] ?? 'bsc_rpc',
                'verification_payload' => $verification,
                'submitted_at' => now(),
                'verified_at' => now(),
                'admin_notes' => "Web3 DApp Instant Deposit: Auto-approved on-chain. Received: {$verifiedAmount} USDT. Net credited: \${$netAmount} USD to Member Fund Wallet.",
            ]);

            $network = Setting::get('bsc_network', config('blockchain.bsc.network', 'mainnet'));
            $explorerUrl = config("blockchain.bsc.explorer_tx_url.{$network}", 'https://bscscan.com/tx/') . $txHash;

            return response()->json([
                'success' => true,
                'status' => 'approved',
                'message' => "Web3 DApp deposit of \${$verifiedAmount} USDT successfully completed and auto-approved! \${$netAmount} USD has been credited directly to your Fund Wallet.",
                'deposit' => [
                    'id' => $importFund->id,
                    'orderid' => $importFund->orderid,
                    'amount' => $verifiedAmount,
                    'net_amount' => $netAmount,
                    'fee_amount' => $feeAmount,
                    'fee_percent' => $feePercent,
                    'transaction_hash' => $txHash,
                    'wallet_address' => $fromAddress,
                    'network' => 'BEP-20',
                    'token' => 'USDT',
                    'deposit_status' => ImportFund::STATUS_APPROVED,
                    'status_label' => 'Approved & Credited',
                    'explorer_url' => $explorerUrl,
                    'created_at' => $importFund->created_at->toISOString(),
                ],
                'new_fund_wallet' => $newP2pBalance,
                'previous_fund_wallet' => $previousP2pBalance,
            ], 201);
        });
    }

    /**
     * Get authenticated member's deposit history from import_funds.
     */
    public function history(Request $request): JsonResponse
    {
        $member = $request->user('member') ?? $request->user();
        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $perPage = min(max((int) $request->input('per_page', 10), 5), 50);

        $network = Setting::get('bsc_network', config('blockchain.bsc.network', 'mainnet'));
        $explorerBaseUrl = config("blockchain.bsc.explorer_tx_url.{$network}", 'https://bscscan.com/tx/');

        $deposits = ImportFund::where(function ($q) use ($member) {
                $q->where('member_id', $member->id)
                  ->orWhere('user_id', $member->user_id)
                  ->orWhere('memberid', $member->user_id)
                  ->orWhere('memberid', (string) $member->id);
            })
            ->latest('created_at')
            ->paginate($perPage);

        // Map status label & explorer links
        $deposits->getCollection()->transform(function ($item) use ($explorerBaseUrl) {
            $status = $item->deposit_status ?: strtolower($item->status);
            $label = match ($status) {
                'approved' => 'Approved',
                'verified' => 'Verified — Awaiting Admin Approval',
                'rejected', 'cancelled' => 'Rejected',
                default => 'Pending Verification',
            };

            return [
                'id' => $item->id,
                'orderid' => $item->orderid,
                'amount' => (float) $item->amount,
                'transaction_hash' => $item->transaction_hash ?: $item->txnid,
                'wallet_address' => $item->wallet_address,
                'network' => $item->network ?: 'BEP-20',
                'token' => $item->token ?: 'USDT',
                'mode' => $item->mode ?: 'Online',
                'status' => $status,
                'status_label' => $label,
                'verification_status' => $item->verification_status,
                'admin_notes' => $item->admin_notes,
                'rejection_reason' => $item->rejection_reason,
                'explorer_url' => $item->transaction_hash ? ($explorerBaseUrl . $item->transaction_hash) : null,
                'created_at' => $item->created_at ? $item->created_at->toISOString() : null,
                'verified_at' => $item->verified_at ? $item->verified_at->toISOString() : null,
            ];
        });

        $stats = $this->getMemberDepositStats($member);

        return response()->json([
            'success' => true,
            'deposits' => $deposits,
            'member_fund_wallet' => $stats['fund_wallet'],
            'member_p2p_wallet' => $stats['p2p_wallet'],
            'member_ad_balance' => (float) ($stats['fund_wallet'] ?? 0.00),
            'todays_deposit' => $stats['todays_deposit'],
            'total_deposit' => $stats['total_deposit'],
            'pending_deposit' => $stats['pending_deposit'],
            'stats' => $stats,
        ]);
    }

    /**
     * Compute aggregated deposit statistics for authenticated member.
     * Includes Fund Wallet balance, Today's Deposit, Total Lifetime Deposit, and Pending Deposits.
     */
    private function getMemberDepositStats($member): array
    {
        if (!$member) {
            return [
                'fund_wallet' => 0.00,
                'p2p_wallet' => 0.00,
                'todays_deposit' => 0.00,
                'total_deposit' => 0.00,
                'pending_deposit' => 0.00,
                'approved_count' => 0,
            ];
        }

        // Refresh member record to guarantee latest p2p_wallet balance
        $fresh = $member->fresh() ?? $member;
        $fundWallet = (float) ($fresh->p2p_wallet ?? 0.00);

        try {

        $memberCondition = function ($q) use ($member) {
            $q->where('member_id', $member->id)
              ->orWhere('user_id', $member->user_id)
              ->orWhere('memberid', $member->user_id)
              ->orWhere('memberid', (string) $member->id);
        };

        // 1. Approved / Credited Deposits
        $approvedQuery = ImportFund::where($memberCondition)->where(function ($q) {
            $q->where('status', 'Success')
              ->orWhere('deposit_status', 'approved');
        });

        $totalDeposit = round((float) (clone $approvedQuery)->sum('amount'), 2);
        $todaysDeposit = round((float) (clone $approvedQuery)->where('created_at', '>=', now()->startOfDay())->sum('amount'), 2);
        $approvedCount = (int) (clone $approvedQuery)->count();

        // 2. Pending / Verified Deposits (Awaiting Admin Approval)
        $pendingQuery = ImportFund::where($memberCondition)->where(function ($q) {
            $q->where('status', 'Pending')
              ->orWhereIn('deposit_status', ['pending', 'verified']);
        });

            $pendingDeposit = round((float) $pendingQuery->sum('amount'), 2);
        } catch (\Throwable $e) {
            Log::warning("Could not compute member deposit stats: " . $e->getMessage());
            $totalDeposit = 0.00;
            $todaysDeposit = 0.00;
            $approvedCount = 0;
            $pendingDeposit = 0.00;
        }

        $pendingDeposit = round((float) $pendingQuery->sum('amount'), 2);

        return [
            'fund_wallet' => $fundWallet,
            'p2p_wallet' => $fundWallet,
            'todays_deposit' => $todaysDeposit,
            'total_deposit' => $totalDeposit,
            'pending_deposit' => $pendingDeposit,
            'approved_count' => $approvedCount,
        ];
    }
}
