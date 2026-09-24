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
                'min_deposit' => $minDeposit,
                'min_deposit_label' => '$10.00 USD equivalent',
                'is_test_mode' => $activeTestMode,
                'member_id' => $member ? $member->id : null,
                'member_user_id' => $member ? $member->user_id : null,
                'member_name' => $member ? $member->name : null,
                'member_fund_wallet' => $stats['fund_wallet'],
                'member_p2p_wallet' => $stats['p2p_wallet'],
                'member_ad_balance' => (float) ($member ? ($stats['fund_wallet'] ?: ($member->ad_balance ?? 0.00)) : 0.00),
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

        $verification = $verifier->verifyTransaction(
            $txHash,
            $amount,
            $recipientWallet,
            10.00,
            true, // require exact amount match
            $walletAddress
        );

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

        return response()->json([
            'success' => true,
            'verified' => true,
            'status' => 'verified',
            'message' => 'Transaction Verified Successfully. You can now submit your deposit request to the admin.',
            'data' => [
                'amount' => (float) $verification['transferred_amount'],
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
        ]);

        $amount = round((float) $request->input('amount'), 2);
        $txHash = trim($request->input('transaction_hash'));
        $walletAddress = $request->input('wallet_address') ? trim($request->input('wallet_address')) : null;

        $recipientWallet = Setting::get('deposit_crypto_wallet_address', '');
        if (empty($recipientWallet)) {
            $recipientWallet = '0x55d398326f99059fF775485246999027B3197955';
        }

        // Re-verify on-chain (zero-trust backend rule)
        $verification = $verifier->verifyTransaction(
            $txHash,
            $amount,
            $recipientWallet,
            10.00,
            true,
            $walletAddress
        );

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

            // 1. Create record in import_funds
            $importFund = ImportFund::create([
                'memberid' => substr($member->user_id, 0, 20),
                'user_id' => $member->user_id,
                'member_id' => $member->id,
                'txnid' => substr($txHash, 0, 100),
                'transaction_hash' => $txHash,
                'amount' => $verifiedAmount,
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
                'admin_notes' => "Manual request submitted after verified on-chain transfer of {$verifiedAmount} USDT.",
                'mode' => 'Mannual',
                'verified_at' => now(),
            ]);

            // 2. Also register in ad_deposits to allow unified admin review
            $adDeposit = AdDeposit::create([
                'member_id' => $member->id,
                'amount_inr' => $verifiedAmount,
                'fee_percent' => 0.00,
                'fee_amount_inr' => 0.00,
                'net_amount_inr' => $verifiedAmount,
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
                'expected_usd_amount' => $verifiedAmount,
                'transaction_reference' => $txHash,
                'transaction_hash' => $txHash,
                'status' => AdDeposit::STATUS_PENDING,
                'verification_status' => 'verified',
                'verification_source' => $verification['verification_source'] ?? 'bsc_rpc',
                'verification_payload' => $verification,
                'submitted_at' => now(),
                'admin_notes' => "Manual request verified on-chain: {$verifiedAmount} USDT transferred to {$recipientWallet}. Awaiting Admin Approval.",
            ]);

            $network = Setting::get('bsc_network', config('blockchain.bsc.network', 'mainnet'));
            $explorerUrl = config("blockchain.bsc.explorer_tx_url.{$network}", 'https://bscscan.com/tx/') . $txHash;

            return response()->json([
                'success' => true,
                'status' => 'verified',
                'message' => 'Your deposit request has been submitted to the Admin Panel. Status: Verified — Awaiting Admin Approval.',
                'deposit' => [
                    'id' => $importFund->id,
                    'orderid' => $importFund->orderid,
                    'amount' => $verifiedAmount,
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
            return response()->json([
                'success' => false,
                'status' => $verification['status'] ?? 'verification_failed',
                'message' => $verification['message'] ?? 'Blockchain verification failed.',
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

            // 1. Record in import_funds table
            $importFund = ImportFund::create([
                'memberid' => substr($member->user_id, 0, 20),
                'user_id' => $member->user_id,
                'member_id' => $member->id,
                'txnid' => substr($txHash, 0, 100),
                'transaction_hash' => $txHash,
                'amount' => $verifiedAmount,
                'type' => 'Add',
                'wallet_type' => 'USDT',
                'wallet_address' => $fromAddress,
                'network' => $verification['network'] ?? 'BEP-20',
                'token' => $verification['token'] ?? 'USDT',
                'contract_address' => $verification['contract_address'] ?? null,
                'added_by' => 'User',
                'status' => ImportFund::LEGACY_PENDING,
                'verification_status' => 'verified',
                'deposit_status' => ImportFund::STATUS_VERIFIED,
                'verification_payload' => $verification,
                'admin_notes' => "DApp deposit: {$verifiedAmount} USDT transferred from {$fromAddress} on BNB Smart Chain.",
                'mode' => 'Online',
                'verified_at' => now(),
            ]);

            // 2. Also register in ad_deposits for unified admin review & tracking
            AdDeposit::create([
                'member_id' => $member->id,
                'amount_inr' => $verifiedAmount,
                'fee_percent' => 0.00,
                'fee_amount_inr' => 0.00,
                'net_amount_inr' => $verifiedAmount,
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
                'expected_usd_amount' => $verifiedAmount,
                'transaction_reference' => $txHash,
                'transaction_hash' => $txHash,
                'status' => AdDeposit::STATUS_PENDING,
                'verification_status' => 'verified',
                'verification_source' => $verification['verification_source'] ?? 'bsc_rpc',
                'verification_payload' => $verification,
                'submitted_at' => now(),
                'admin_notes' => "DApp deposit verified on-chain: {$verifiedAmount} USDT from {$fromAddress}. Awaiting Admin Approval.",
            ]);

            $network = Setting::get('bsc_network', config('blockchain.bsc.network', 'mainnet'));
            $explorerUrl = config("blockchain.bsc.explorer_tx_url.{$network}", 'https://bscscan.com/tx/') . $txHash;

            return response()->json([
                'success' => true,
                'status' => 'verified',
                'message' => "DApp deposit of \${$verifiedAmount} USDT successfully completed and recorded! Awaiting Admin Approval.",
                'deposit' => [
                    'id' => $importFund->id,
                    'orderid' => $importFund->orderid,
                    'amount' => $verifiedAmount,
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
            'member_ad_balance' => (float) ($member ? ($stats['fund_wallet'] ?: ($member->ad_balance ?? 0.00)) : 0.00),
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
