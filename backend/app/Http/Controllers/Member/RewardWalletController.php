<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Mail\MemberWeb3WalletVerificationOtp;
use App\Models\AdReward;
use App\Models\Member;
use App\Models\MemberVerificationOtp;
use App\Services\MemberOtpService;
use App\Services\WhatsAppService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class RewardWalletController extends Controller
{
    public function __construct(
        private MemberOtpService $otpService,
        private WhatsAppService $whatsAppService
    ) {}

    /**
     * Helper to mask email address for privacy in UI.
     * e.g., member@example.com -> m***r@e***.com
     */
    public static function maskEmail(?string $email): string
    {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'm***@***.com';
        }

        $parts = explode('@', $email);
        $name = $parts[0];
        $domain = $parts[1] ?? '';

        $maskedName = strlen($name) <= 2
            ? substr($name, 0, 1) . '***'
            : substr($name, 0, 1) . str_repeat('*', max(3, strlen($name) - 2)) . substr($name, -1);

        $domainParts = explode('.', $domain);
        if (count($domainParts) >= 2) {
            $domainName = $domainParts[0];
            $tld = implode('.', array_slice($domainParts, 1));
            $maskedDomain = substr($domainName, 0, 1) . '***.' . $tld;
        } else {
            $maskedDomain = $domain;
        }

        return $maskedName . '@' . $maskedDomain;
    }

    /**
     * Get the authenticated member's Reward Wallet summary and balance.
     */
    public function wallet(Request $request): JsonResponse
    {
        /** @var Member|null $member */
        $member = auth('member')->user();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $member = $member->fresh() ?? $member;

        $rewardBalance = (float) ($member->reward_balance ?? 0.00);
        $totalEarned = (float) AdReward::where('member_id', $member->id)
            ->where('status', AdReward::STATUS_CREDITED)
            ->sum('reward_amount_usd');
        $totalCount = AdReward::where('member_id', $member->id)
            ->where('status', AdReward::STATUS_CREDITED)
            ->count();

        $isVerified = method_exists($member, 'isMobileVerified') ? $member->isMobileVerified() : ($member->mobile_verified_at !== null);

        // Check if there is an active (unexpired) pending wallet OTP challenge
        $pendingOtp = MemberVerificationOtp::query()
            ->where('member_id', $member->id)
            ->whereIn('purpose', [
                MemberOtpService::PURPOSE_WEB3_WALLET_VERIFICATION,
                MemberOtpService::PURPOSE_REWARD_WALLET_CHANGE,
            ])
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        $hasVerifiedWallet = !empty($member->reward_wallet_address) && $member->reward_wallet_verified_at !== null;
        $walletStatus = $hasVerifiedWallet ? 'verified' : (!empty($member->reward_wallet_address) ? 'unverified' : 'not_added');

        $minReward = \App\Models\AdRewardRule::getMinimumActiveRewardAmount();
        $maxReward = (float) (\App\Models\AdRewardRule::getActiveRules()->max('reward_amount') ?? 0.05);

        return response()->json([
            'success' => true,
            'wallet' => [
                'reward_balance' => $rewardBalance,
                'reward_balance_formatted' => '$' . number_format($rewardBalance, 4) . ' USD',
                'total_rewards_earned' => round($totalEarned, 4),
                'total_rewards_earned_formatted' => '$' . number_format($totalEarned, 4) . ' USD',
                'total_reward_count' => $totalCount,
                'is_verified' => $isVerified,
                'phone' => $member->phone,
                'email' => $member->email,
                'masked_email' => self::maskEmail($member->email),
                'currency' => 'USD',
                'reward_per_interaction' => $maxReward,
                'min_reward_usd' => $minReward,
                'max_reward_usd' => $maxReward,
                'reward_range_formatted' => '$' . number_format($minReward, 3) . ' – $' . number_format($maxReward, 3) . ' USD',
                // Destination Wallet Details (USDT BEP-20)
                'wallet_address' => $member->reward_wallet_address,
                'wallet_network' => $member->reward_wallet_network ?? 'BEP-20',
                'wallet_currency' => $member->reward_wallet_currency ?? 'USDT',
                'wallet_status' => $walletStatus,
                'wallet_verified_at' => $member->reward_wallet_verified_at?->toIso8601String(),
                'has_verified_wallet' => $hasVerifiedWallet,
                'pending_wallet_address' => $pendingOtp?->pending_value,
                'has_pending_otp' => $pendingOtp !== null,
                'channel' => 'email',
                'instructions' => 'Enter your USDT (BEP-20) wallet address. A 6-digit email OTP verification is sent to your registered account email to verify and activate your destination address.',
            ],
        ]);
    }

    /**
     * Request an Email OTP to add or change Web3 USDT (BEP-20) wallet address.
     */
    public function sendWalletOtp(Request $request): JsonResponse
    {
        /** @var Member|null $member */
        $member = auth('member')->user();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $member = $member->fresh() ?? $member;

        // Ensure member has a registered email address
        if (empty($member->email)) {
            return response()->json([
                'success' => false,
                'message' => 'Please set a registered email address before configuring your Web3 Wallet address.',
                'errors' => ['wallet_address' => ['A registered account email is required before configuring your Web3 Wallet address.']],
            ], 422);
        }

        $validated = $request->validate([
            'wallet_address' => [
                'required',
                'string',
                'regex:/^0x[a-fA-F0-9]{40}$/',
            ],
        ], [
            'wallet_address.required' => 'Please enter your USDT (BEP-20) wallet address.',
            'wallet_address.regex' => 'Please enter a valid USDT (BEP-20) BNB Smart Chain wallet address (e.g. 0x71C...3972).',
        ]);

        $walletAddress = trim($validated['wallet_address']);

        // Rate limiting cooldown (60 seconds)
        $key = MemberOtpService::cooldownRateKey(MemberOtpService::PURPOSE_WEB3_WALLET_VERIFICATION, $member->getKey());
        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Please wait {$seconds} seconds before requesting a new email verification code.",
                'errors' => ['otp' => ["Please wait {$seconds} seconds before requesting a new email verification code."]],
            ], 422);
        }

        RateLimiter::hit($key, 60);

        // Create secure OTP challenge with pending wallet address bound to registered email
        $result = $this->otpService->createOtp(
            $member,
            MemberOtpService::PURPOSE_WEB3_WALLET_VERIFICATION,
            $member->email,
            $walletAddress
        );

        // Send OTP via Email to registered account email
        try {
            Mail::to($member->email)->send(
                new MemberWeb3WalletVerificationOtp(
                    $result['code'],
                    $member->name,
                    $walletAddress
                )
            );
        } catch (Exception $e) {
            Log::error('Failed to send Web3 wallet verification email OTP', [
                'member_id' => $member->getKey(),
                'email' => $member->email,
                'error' => $e->getMessage(),
            ]);

            // Invalidate the challenge if mail delivery threw an error
            $this->otpService->invalidate($member, MemberOtpService::PURPOSE_WEB3_WALLET_VERIFICATION);

            return response()->json([
                'success' => false,
                'message' => 'Unable to send verification code to your registered email. Please try again.',
                'errors' => ['email' => ['Unable to send verification code. Please try again.']],
            ], 422);
        }

        Log::info('Web3 wallet verification Email OTP dispatched', [
            'member_id' => $member->getKey(),
            'email' => $member->email,
            'wallet_address' => $walletAddress,
        ]);

        $maskedEmail = self::maskEmail($member->email);

        $responsePayload = [
            'success' => true,
            'message' => "Verification code sent to your registered email ({$maskedEmail})",
            'email' => $maskedEmail,
            'wallet_address' => $walletAddress,
            'expires_in_minutes' => MemberOtpService::EXPIRY_MINUTES,
            'channel' => 'email',
        ];

        // Include demo OTP in local/demo environment for automated tests and developer testing
        if (app()->environment('local', 'development', 'testing')) {
            $responsePayload['demo_otp'] = $result['code'];
            $responsePayload['is_demo'] = true;
        }

        return response()->json($responsePayload);
    }

    /**
     * Verify Email OTP and activate the new Web3 USDT (BEP-20) wallet address (Atomic & Secure).
     */
    public function verifyWalletOtp(Request $request): JsonResponse
    {
        /** @var Member|null $member */
        $member = auth('member')->user();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validated = $request->validate([
            'otp' => ['required', 'string', 'min:4', 'max:8'],
            'wallet_address' => ['nullable', 'string'],
        ], [
            'otp.required' => 'Please enter the 6-digit email verification code.',
        ]);

        $otpCode = preg_replace('/\D/', '', $validated['otp']);
        $submittedAddress = !empty($validated['wallet_address']) ? trim($validated['wallet_address']) : null;

        // Determine which purpose record is pending (primary: web3_wallet_verification, fallback: reward_wallet_change)
        $purpose = MemberOtpService::PURPOSE_WEB3_WALLET_VERIFICATION;
        $hasWeb3Challenge = MemberVerificationOtp::query()
            ->where('member_id', $member->getKey())
            ->where('purpose', MemberOtpService::PURPOSE_WEB3_WALLET_VERIFICATION)
            ->whereNull('verified_at')
            ->exists();

        if (!$hasWeb3Challenge) {
            $hasLegacyChallenge = MemberVerificationOtp::query()
                ->where('member_id', $member->getKey())
                ->where('purpose', MemberOtpService::PURPOSE_REWARD_WALLET_CHANGE)
                ->whereNull('verified_at')
                ->exists();

            if ($hasLegacyChallenge) {
                $purpose = MemberOtpService::PURPOSE_REWARD_WALLET_CHANGE;
            }
        }

        $this->otpService->verifyOtp(
            $member,
            $purpose,
            $otpCode,
            'otp',
            function (MemberVerificationOtp $otp) use ($member, $submittedAddress): void {
                // Verify binding to wallet address if provided
                if ($submittedAddress && strcasecmp($otp->pending_value, $submittedAddress) !== 0) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'wallet_address' => ['The verification code was issued for a different wallet address. Please request a new code.'],
                    ]);
                }

                $member->update([
                    'reward_wallet_address' => $otp->pending_value,
                    'reward_wallet_network' => 'BEP-20',
                    'reward_wallet_currency' => 'USDT',
                    'reward_wallet_verified_at' => now(),
                ]);
            }
        );

        $freshMember = $member->fresh();

        return response()->json([
            'success' => true,
            'message' => 'USDT (BEP-20) wallet address verified and activated successfully via email verification!',
            'wallet' => [
                'wallet_address' => $freshMember->reward_wallet_address,
                'wallet_network' => $freshMember->reward_wallet_network ?? 'BEP-20',
                'wallet_currency' => $freshMember->reward_wallet_currency ?? 'USDT',
                'wallet_status' => 'verified',
                'wallet_verified_at' => $freshMember->reward_wallet_verified_at?->toIso8601String(),
                'has_verified_wallet' => true,
                'reward_balance' => (float) ($freshMember->reward_balance ?? 0.00),
            ],
        ]);
    }

    /**
     * Get itemized reward ledger history for the authenticated member.
     */
    public function history(Request $request): JsonResponse
    {
        /** @var Member|null $member */
        $member = auth('member')->user();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(50, max(1, (int) $request->input('per_page', 15)));

        $rewards = AdReward::where('member_id', $member->id)
            ->where('status', AdReward::STATUS_CREDITED)
            ->with([
                'adCampaign' => function ($cq) {
                    $cq->select(['id', 'campaign_id', 'campaign_name', 'business_page_id']);
                },
                'adCampaign.businessPage' => function ($bq) {
                    $bq->select(['id', 'page_name', 'slug', 'logo']);
                },
            ])
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        $mappedData = collect($rewards->items())->map(function (AdReward $r) {
            $campaign = $r->adCampaign;
            $bizPage = $campaign?->businessPage;
            $amount = (float) ($r->reward_amount_usd ?? 0.00);

            return [
                'id' => $r->id,
                'reward_amount_usd' => $amount,
                'reward_amount_exact' => number_format($amount, 4, '.', ''),
                'reward_formatted' => '+$' . number_format($amount, 4, '.', '') . ' USD',
                'direct_verified_referral_count' => $r->direct_verified_referral_count,
                'rule_min_referrals' => $r->rule_min_referrals,
                'rule_max_referrals' => $r->rule_max_referrals,
                'tier_label' => $r->tier_label,
                'rule_version' => $r->rule_version,
                'qualifying_event_id' => $r->qualifying_event_id,
                'landing_page_url' => $r->landing_page_url,
                'status' => $r->status,
                'created_at' => $r->created_at?->toIso8601String(),
                'processed_at' => $r->created_at?->toIso8601String(),
                'campaign' => $campaign ? [
                    'id' => $campaign->id,
                    'campaign_id' => $campaign->campaign_id,
                    'campaign_name' => $campaign->campaign_name,
                    'business_page' => $bizPage ? [
                        'id' => $bizPage->id,
                        'name' => $bizPage->page_name,
                        'slug' => $bizPage->slug,
                    ] : null,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'rewards' => [
                'data' => $mappedData,
                'current_page' => $rewards->currentPage(),
                'per_page' => $rewards->perPage(),
                'total' => $rewards->total(),
                'last_page' => $rewards->lastPage(),
            ],
            'summary' => [
                'reward_balance' => (float) ($member->reward_balance ?? 0.00),
                'total_reward_count' => $rewards->total(),
            ],
        ]);
    }
}