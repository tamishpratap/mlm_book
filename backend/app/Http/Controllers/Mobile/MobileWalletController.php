<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\AdDeposit;
use App\Models\AdReward;
use App\Models\AdRewardRule;
use App\Models\Member;
use App\Models\MemberVerificationOtp;
use App\Models\Setting;
use App\Services\BscTransactionVerifierService;
use App\Services\MemberOtpService;
use App\Services\RewardRuleResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MobileWalletController extends Controller
{
    /**
     * Get Reward Wallet & Financial Overview for mobile.
     */
    public function summary(Request $request, RewardRuleResolver $ruleResolver): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();
        $member = $member->fresh() ?? $member;

        $tierResolution = $ruleResolver->resolveForMember($member);
        $totalEarned = (float) AdReward::where('member_id', $member->id)->where('status', AdReward::STATUS_CREDITED)->sum('reward_amount_usd');

        return response()->json([
            'success' => true,
            'wallet' => [
                'reward_balance' => (float) ($member->reward_balance ?? 0.00),
                'reward_balance_formatted' => '$' . number_format((float) ($member->reward_balance ?? 0.00), 4) . ' USD',
                'ad_balance' => (float) ($member->ad_balance ?? 0.00),
                'ad_balance_formatted' => '$' . number_format((float) ($member->ad_balance ?? 0.00), 2) . ' USDT',
                'total_earned' => round($totalEarned, 4),
                'total_earned_formatted' => '$' . number_format($totalEarned, 4) . ' USD',
                'is_mobile_verified' => $member->isMobileVerified(),
                'direct_verified_referrals' => $member->getVerifiedDirectReferralCount(),
                'current_tier' => [
                    'eligible' => $tierResolution['eligible'] ?? false,
                    'reward_amount_usd' => $tierResolution['reward_amount_usd'] ?? 0.00,
                    'reward_amount_exact' => $tierResolution['reward_amount_exact'] ?? '0.0000',
                    'range_label' => $tierResolution['matched_range']['label'] ?? 'Standard',
                ],
                'wallet_address' => $member->reward_wallet_address,
                'wallet_network' => $member->reward_wallet_network ?? 'BEP-20',
                'wallet_currency' => $member->reward_wallet_currency ?? 'USDT',
                'is_wallet_verified' => !empty($member->reward_wallet_address) && $member->reward_wallet_verified_at !== null,
            ],
        ]);
    }

    /**
     * Send email OTP to link or update Web3 USDT (BEP-20) destination address.
     */
    public function sendWalletOtp(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $validated = $request->validate([
            'wallet_address' => ['required', 'string', 'regex:/^0x[a-fA-F0-9]{40}$/'],
        ], [
            'wallet_address.regex' => 'Please enter a valid BSC (BEP-20) hexadecimal wallet address starting with 0x.',
        ]);

        $otpCode = (string) random_int(100000, 999999);

        // Store OTP in member_verification_otps
        MemberVerificationOtp::create([
            'member_id' => $member->id,
            'purpose' => MemberOtpService::PURPOSE_WEB3_WALLET_VERIFICATION,
            'otp_code' => $otpCode,
            'pending_value' => strtolower($validated['wallet_address']),
            'expires_at' => now()->addMinutes(10),
        ]);

        // Send OTP mail
        try {
            Mail::raw("Your MLM Book Web3 Wallet linking verification code is: {$otpCode}. Valid for 10 minutes.", function ($m) use ($member) {
                $m->to($member->email)->subject('Web3 Wallet Verification Code - MLM Book');
            });
        } catch (\Throwable $e) {
            // Log fallback
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent to your registered email.',
        ]);
    }

    /**
     * Verify OTP and link destination USDT (BEP-20) address.
     */
    public function verifyWalletOtp(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $validated = $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $otpRecord = MemberVerificationOtp::query()
            ->where('member_id', $member->id)
            ->where('purpose', MemberOtpService::PURPOSE_WEB3_WALLET_VERIFICATION)
            ->where('otp_code', $validated['otp'])
            ->where('expires_at', '>', now())
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (!$otpRecord) {
            return response()->json(['message' => 'Invalid or expired verification code.'], 422);
        }

        $otpRecord->update(['verified_at' => now()]);

        $member->reward_wallet_address = $otpRecord->pending_value;
        $member->reward_wallet_network = 'BEP-20';
        $member->reward_wallet_currency = 'USDT';
        $member->reward_wallet_verified_at = now();
        $member->save();

        return response()->json([
            'success' => true,
            'message' => 'USDT (BEP-20) wallet address linked and verified successfully.',
            'wallet_address' => $member->reward_wallet_address,
        ]);
    }

    /**
     * Get deposit configuration (admin wallet address & QR) for deposits.
     */
    public function depositConfig(): JsonResponse
    {
        $qrPath = Setting::get('deposit_qr_image');
        $qrUrl = $qrPath ? (str_starts_with($qrPath, 'http') ? $qrPath : asset($qrPath)) : null;

        return response()->json([
            'success' => true,
            'config' => [
                'crypto_wallet_address' => Setting::get('deposit_crypto_wallet_address', '0x1234567890abcdef1234567890abcdef12345678'),
                'network' => 'BEP-20 (BNB Smart Chain)',
                'token' => 'USDT',
                'qr_url' => $qrUrl,
                'min_deposit' => 1.00,
                'instructions' => 'Send USDT via BEP-20 network to the address above. After completing transaction, enter the Transaction Hash below to verify and credit funds automatically.',
            ],
        ]);
    }

    /**
     * Submit a deposit transaction hash and verify on-chain.
     */
    public function submitDeposit(Request $request, BscTransactionVerifierService $verifier): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $validated = $request->validate([
            'amount_usdt' => ['required', 'numeric', 'min:1'],
            'transaction_hash' => ['required', 'string', 'min:10', 'max:150'],
        ]);

        $txHash = trim($validated['transaction_hash']);

        // Check duplicate hash
        $exists = AdDeposit::where(function ($q) use ($txHash) {
            $q->where('transaction_reference', $txHash)->orWhere('transaction_hash', $txHash);
        })->whereIn('status', [AdDeposit::STATUS_APPROVED, AdDeposit::STATUS_PENDING])->exists();

        if ($exists) {
            return response()->json(['message' => 'This transaction hash has already been submitted or processed.'], 422);
        }

        $adminWallet = Setting::get('deposit_crypto_wallet_address', '');

        $deposit = AdDeposit::create([
            'member_id' => $member->id,
            'amount_inr' => (float) $validated['amount_usdt'] * 90,
            'submitted_amount' => (float) $validated['amount_usdt'],
            'expected_usd_amount' => (float) $validated['amount_usdt'],
            'currency_in' => 'USDT',
            'currency_out' => 'USDT',
            'network' => 'BEP-20',
            'token' => 'USDT',
            'transaction_hash' => $txHash,
            'transaction_reference' => $txHash,
            'status' => AdDeposit::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        // Attempt live on-chain verification
        $isVerified = false;
        if (!empty($adminWallet)) {
            try {
                $verification = $verifier->verifyTransaction($txHash, (float) $validated['amount_usdt'], $adminWallet);
                if ($verification['verified'] ?? false) {
                    $deposit->update([
                        'status' => AdDeposit::STATUS_APPROVED,
                        'verified_amount' => $verification['verified_amount'] ?? $validated['amount_usdt'],
                        'verified_at' => now(),
                        'verification_status' => 'verified',
                    ]);
                    $member->creditAdBalance((float) $validated['amount_usdt']);
                    $isVerified = true;
                }
            } catch (\Throwable $e) {
                // Keep as pending for admin review
            }
        }

        return response()->json([
            'success' => true,
            'message' => $isVerified ? 'Blockchain deposit verified! Funds credited to ad balance.' : 'Deposit submitted for verification. It will be credited upon confirmation.',
            'deposit_id' => $deposit->deposit_id,
            'status' => $deposit->status,
            'is_auto_credited' => $isVerified,
            'new_ad_balance' => (float) $member->fresh()->ad_balance,
        ]);
    }

    /**
     * Get member's deposit history.
     */
    public function depositHistory(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $deposits = AdDeposit::where('member_id', $member->id)
            ->latest()
            ->paginate(15)
            ->through(function ($d) {
                return [
                    'id' => $d->id,
                    'deposit_id' => $d->deposit_id,
                    'amount' => (float) ($d->submitted_amount ?? $d->expected_usd_amount ?? 0),
                    'currency' => 'USDT',
                    'tx_hash' => $d->transaction_hash,
                    'status' => $d->status,
                    'date' => $d->created_at?->format('M d, Y H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'deposits' => $deposits,
        ]);
    }
}
