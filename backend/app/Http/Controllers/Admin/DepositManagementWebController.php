<?php

namespace App\Http\Controllers\Admin;

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

class DepositManagementWebController extends Controller
{
    /**
     * Display listing of manual and DApp deposit requests.
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'all');
        $search = trim((string) $request->input('search', ''));
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Query import_funds as primary deposit table
        $query = ImportFund::with('member')->orderBy('id', 'desc');

        // Filter by Status
        if ($status === 'pending') {
            $query->whereIn('deposit_status', [ImportFund::STATUS_PENDING, ImportFund::STATUS_VERIFIED])
                  ->orWhere(function ($q) {
                      $q->whereNull('deposit_status')->where('status', 'Pending');
                  });
        } elseif ($status === 'verified') {
            $query->where('deposit_status', ImportFund::STATUS_VERIFIED);
        } elseif ($status === 'approved') {
            $query->where(function ($q) {
                $q->where('deposit_status', ImportFund::STATUS_APPROVED)
                  ->orWhere('status', 'Approved');
            });
        } elseif ($status === 'rejected') {
            $query->where(function ($q) {
                $q->where('deposit_status', ImportFund::STATUS_REJECTED)
                  ->orWhere('status', 'Rejected');
            });
        }

        // Search by Tx Hash, Order ID, User ID or Member Name/Email
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_hash', 'like', "%{$search}%")
                  ->orWhere('txnid', 'like', "%{$search}%")
                  ->orWhere('orderid', 'like', "%{$search}%")
                  ->orWhere('user_id', 'like', "%{$search}%")
                  ->orWhere('memberid', 'like', "%{$search}%")
                  ->orWhereHas('member', function ($mq) use ($search) {
                      $mq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%")
                         ->orWhere('user_id', 'like', "%{$search}%");
                  });
            });
        }

        // Date Range Filter
        if (!empty($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $deposits = $query->paginate(20)->withQueryString();

        // Metrics for KPI Cards
        $pendingCount = ImportFund::whereIn('deposit_status', [ImportFund::STATUS_PENDING, ImportFund::STATUS_VERIFIED])
            ->orWhere(function ($q) {
                $q->whereNull('deposit_status')->where('status', 'Pending');
            })->count();

        $approvedCount = ImportFund::where('deposit_status', ImportFund::STATUS_APPROVED)
            ->orWhere('status', 'Approved')->count();

        $approvedVolume = (float) ImportFund::where(function ($q) {
            $q->where('deposit_status', ImportFund::STATUS_APPROVED)
              ->orWhere('status', 'Approved');
        })->sum('amount');

        $rejectedCount = ImportFund::where('deposit_status', ImportFund::STATUS_REJECTED)
            ->orWhere('status', 'Rejected')->count();

        $totalCount = ImportFund::count();

        $network = Setting::get('bsc_network', config('blockchain.bsc.network', 'mainnet'));
        $explorerBaseUrl = config("blockchain.bsc.explorer_tx_url.{$network}", 'https://bscscan.com/tx/');

        return view('admin.deposits.index', compact(
            'deposits',
            'pendingCount',
            'approvedCount',
            'approvedVolume',
            'rejectedCount',
            'totalCount',
            'status',
            'search',
            'dateFrom',
            'dateTo',
            'explorerBaseUrl',
            'network'
        ));
    }

    /**
     * Perform on-chain blockchain verification for a deposit from Admin side.
     * Checks hash, amount, recipient wallet, and transaction status on BNB Smart Chain.
     */
    public function verifyOnChain(Request $request, $id, BscTransactionVerifierService $verifier): JsonResponse
    {
        $deposit = ImportFund::with('member')->find($id);
        if (!$deposit) {
            return response()->json([
                'success' => false,
                'message' => 'Deposit record not found.',
            ], 404);
        }

        $txHash = trim($deposit->transaction_hash ?: $deposit->txnid);
        if (empty($txHash)) {
            return response()->json([
                'success' => false,
                'message' => 'No transaction hash found on this deposit record.',
            ], 422);
        }

        $expectedAmount = (float) $deposit->amount;
        $recipientWallet = Setting::get('deposit_crypto_wallet_address', '');
        if (empty($recipientWallet)) {
            $recipientWallet = '0x55d398326f99059fF775485246999027B3197955';
        }

        // Run on-chain verification
        $verification = $verifier->verifyTransaction(
            $txHash,
            $expectedAmount,
            $recipientWallet,
            10.00,
            true, // require exact amount match
            $deposit->wallet_address
        );

        // Update deposit verification status
        $newVerificationStatus = $verification['verified'] ? 'verified' : 'failed';
        $deposit->update([
            'verification_status' => $newVerificationStatus,
            'verification_payload' => $verification,
            'verified_at' => $verification['verified'] ? now() : $deposit->verified_at,
        ]);

        // Sync to ad_deposits if matching record exists
        AdDeposit::where('transaction_hash', $txHash)
            ->orWhere('transaction_reference', $txHash)
            ->update([
                'verification_status' => $newVerificationStatus,
                'verification_payload' => $verification,
            ]);

        $network = Setting::get('bsc_network', config('blockchain.bsc.network', 'mainnet'));
        $explorerUrl = config("blockchain.bsc.explorer_tx_url.{$network}", 'https://bscscan.com/tx/') . $txHash;

        return response()->json([
            'success' => true,
            'verified' => $verification['verified'],
            'status' => $verification['status'] ?? ($verification['verified'] ? 'verified' : 'failed'),
            'message' => $verification['message'] ?? 'Verification completed.',
            'data' => [
                'deposit_id' => $deposit->orderid ?: "DEP-{$deposit->id}",
                'amount_usdt' => $expectedAmount,
                'transferred_amount' => $verification['transferred_amount'] ?? null,
                'transaction_hash' => $txHash,
                'sender_address' => $verification['from_address'] ?? $deposit->wallet_address,
                'recipient_address' => $verification['to_address'] ?? $recipientWallet,
                'network' => $verification['network'] ?? 'BEP-20',
                'token' => $verification['token'] ?? 'USDT',
                'block_number' => $verification['block_number'] ?? null,
                'confirmations' => $verification['confirmations'] ?? null,
                'explorer_url' => $explorerUrl,
                'member_name' => $deposit->member?->name ?: $deposit->user_id,
            ],
        ]);
    }

    /**
     * Accept / Approve a deposit request.
     * Atomically credits member funds and marks deposit as approved.
     */
    public function approve(Request $request, $id)
    {
        $deposit = ImportFund::with('member')->findOrFail($id);

        if ($deposit->deposit_status === ImportFund::STATUS_APPROVED || $deposit->status === 'Approved') {
            return back()->with('error', 'This deposit request has already been approved and credited.');
        }

        $adminNotes = trim((string) $request->input('admin_notes', ''));

        DB::transaction(function () use ($deposit, $adminNotes) {
            // Lock deposit record
            $lockedDeposit = ImportFund::where('id', $deposit->id)->lockForUpdate()->first();
            if ($lockedDeposit->deposit_status === ImportFund::STATUS_APPROVED) {
                return;
            }

            // Find member to credit
            $member = Member::where('id', $lockedDeposit->member_id)
                ->orWhere('user_id', $lockedDeposit->user_id)
                ->orWhere('user_id', $lockedDeposit->memberid)
                ->lockForUpdate()
                ->first();

            $amount = (float) $lockedDeposit->amount;

            if ($member) {
                // Atomically credit member Fund Wallet (p2p_wallet)
                $member->increment('p2p_wallet', $amount);
            }

            // Update ImportFund
            $lockedDeposit->update([
                'deposit_status' => ImportFund::STATUS_APPROVED,
                'status' => ImportFund::LEGACY_APPROVED,
                'verification_status' => 'verified',
                'verified_at' => now(),
                'verified_by' => auth()->id(),
                'admin_notes' => !empty($adminNotes) ? $adminNotes : ($lockedDeposit->admin_notes ?: 'Approved by Admin'),
            ]);

            // Sync with AdDeposit
            $txHash = $lockedDeposit->transaction_hash ?: $lockedDeposit->txnid;
            if ($txHash) {
                AdDeposit::where(function ($q) use ($txHash) {
                    $q->where('transaction_hash', $txHash)
                      ->orWhere('transaction_reference', $txHash);
                })->update([
                    'status' => AdDeposit::STATUS_APPROVED,
                    'verification_status' => 'verified',
                    'verified_by' => auth()->id(),
                    'admin_notes' => !empty($adminNotes) ? $adminNotes : 'Approved by Admin',
                ]);
            }
        });

        $memberName = $deposit->member?->name ?: ($deposit->user_id ?: 'Member');
        return back()->with('success', "Deposit of \${$deposit->amount} USDT successfully approved and credited to {$memberName}'s Fund Wallet!");
    }

    /**
     * Reject a deposit request with reason.
     */
    public function reject(Request $request, $id)
    {
        $deposit = ImportFund::with('member')->findOrFail($id);

        if ($deposit->deposit_status === ImportFund::STATUS_APPROVED || $deposit->status === 'Approved') {
            return back()->with('error', 'Cannot reject an already approved deposit.');
        }

        $rejectionReason = trim((string) $request->input('rejection_reason', 'Rejected by administrator.'));

        $deposit->update([
            'deposit_status' => ImportFund::STATUS_REJECTED,
            'status' => ImportFund::LEGACY_REJECTED,
            'rejection_reason' => $rejectionReason,
            'admin_notes' => "Rejected: {$rejectionReason}",
            'verified_by' => auth()->id(),
        ]);

        $txHash = $deposit->transaction_hash ?: $deposit->txnid;
        if ($txHash) {
            AdDeposit::where(function ($q) use ($txHash) {
                $q->where('transaction_hash', $txHash)
                  ->orWhere('transaction_reference', $txHash);
            })->update([
                'status' => AdDeposit::STATUS_REJECTED,
                'admin_notes' => "Rejected: {$rejectionReason}",
            ]);
        }

        return back()->with('success', "Deposit request #{$deposit->id} has been marked as rejected.");
    }

    /**
     * Display deposit configuration settings page.
     */
    public function settings()
    {
        $cryptoWallet = Setting::get('deposit_crypto_wallet_address', '');
        $network = Setting::get('bsc_network', config('blockchain.bsc.network', 'mainnet'));
        $usdtContract = Setting::get('bsc_usdt_contract', config("blockchain.bsc.usdt_contract.{$network}", BscTransactionVerifierService::DEFAULT_MAINNET_USDT_CONTRACT));
        $instructions = Setting::get('deposit_instructions', 'Transfer payment in USDT (BEP-20) to the crypto wallet address.');
        $qrImage = Setting::get('deposit_qr_image', '');
        $minDeposit = 10.00;
        $depositFeePercent = (float) Setting::get('deposit_fee_percent', 0.00);

        return view('admin.deposits.settings', compact(
            'cryptoWallet',
            'network',
            'usdtContract',
            'instructions',
            'qrImage',
            'minDeposit',
            'depositFeePercent'
        ));
    }

    /**
     * Update deposit configuration settings.
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'crypto_wallet_address' => ['nullable', 'string', 'regex:/^0x[a-fA-F0-9]{40}$/'],
            'bsc_network' => ['required', 'in:mainnet,testnet'],
            'deposit_instructions' => ['nullable', 'string', 'max:1000'],
            'qr_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'deposit_fee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'service_charge_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        if ($request->filled('crypto_wallet_address')) {
            Setting::set('deposit_crypto_wallet_address', trim($request->input('crypto_wallet_address')));
        }

        Setting::set('bsc_network', $request->input('bsc_network'));

        if ($request->has('deposit_fee_percent') || $request->has('service_charge_percent')) {
            $feeVal = $request->input('deposit_fee_percent', $request->input('service_charge_percent'));
            $feePercent = round(max(0.00, min(100.00, (float) $feeVal)), 2);
            Setting::set('deposit_fee_percent', number_format($feePercent, 2, '.', ''));
        }

        if ($request->has('deposit_instructions')) {
            Setting::set('deposit_instructions', trim($request->input('deposit_instructions')));
        }

        if ($request->hasFile('qr_image')) {
            $path = $request->file('qr_image')->store('uploads/deposit', 'public');
            Setting::set('deposit_qr_image', 'storage/' . $path);
            
            // Ensure public storage copy exists on Windows environments
            $publicDestDir = public_path('storage/uploads/deposit');
            if (!file_exists($publicDestDir)) {
                @mkdir($publicDestDir, 0755, true);
            }
            @copy(storage_path('app/public/' . $path), public_path('storage/' . $path));
        }

        return back()->with('success', 'Deposit settings updated successfully.');
    }
}
