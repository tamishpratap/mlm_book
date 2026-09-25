<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureMemberMobileVerified;
use App\Models\AdCampaign;
use App\Models\AdClick;
use App\Models\AdImpression;
use App\Models\AdCampaignActivity;
use App\Models\AdReward;
use App\Models\AdRewardRule;
use App\Models\BusinessFollower;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\Post;
use App\Models\Setting;
use App\Services\AdCampaignEngagementService;
use App\Services\RewardRuleResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BusinessAdCampaignController extends Controller
{
    /**
     * Display a listing of advertising campaigns for the specified Business Page.
     */
    public function index(Request $request, BusinessPage $businessPage)
    {
        $member = auth('member')->user();
        if ($member) {
            $member = $member->fresh() ?? $member;
        }

        if (!$businessPage->isOwner($member->id) && !$businessPage->isTeamAdmin($member->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have permission to view ad campaigns for this Business Page.',
            ], 403);
        }

        $query = AdCampaign::query()
            ->where('business_page_id', $businessPage->id)
            ->with(['post', 'owner:id,name,user_id,email', 'approver:id,name,email'])
            ->withCount(['impressions', 'clicks']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->input('approval_status'));
        }

        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function ($sub) use ($q) {
                $sub->where('campaign_name', 'like', "%{$q}%")
                    ->orWhere('campaign_id', 'like', "%{$q}%");
            });
        }

        $campaigns = $query->latest()->paginate(15)->withQueryString();

        $campaigns->getCollection()->transform(function (AdCampaign $c) {
            $c->ctr = $c->ctr;
            return $c;
        });

        $pageCampaignIds = AdCampaign::where('business_page_id', $businessPage->id)->pluck('id');
        $totalImpressions = AdImpression::whereIn('ad_campaign_id', $pageCampaignIds)->count();
        $totalClicks = AdClick::whereIn('ad_campaign_id', $pageCampaignIds)->count();
        $avgCtr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0.00;

        $availableAdFunds = round((float) ($member->p2p_wallet ?? 0.00), 2);
        $campaignFeePercent = (float) Setting::get('campaign_platform_fee_percent', 2.50);

        $metrics = [
            'total_campaigns' => AdCampaign::where('business_page_id', $businessPage->id)->count(),
            'active_campaigns' => AdCampaign::where('business_page_id', $businessPage->id)->where('status', AdCampaign::STATUS_ACTIVE)->count(),
            'pending_review' => AdCampaign::where('business_page_id', $businessPage->id)->where('status', AdCampaign::STATUS_PENDING_REVIEW)->count(),
            'approved_campaigns' => AdCampaign::where('business_page_id', $businessPage->id)->where('status', AdCampaign::STATUS_APPROVED)->count(),
            'paused_campaigns' => AdCampaign::where('business_page_id', $businessPage->id)->where('status', AdCampaign::STATUS_PAUSED)->count(),
            'completed_campaigns' => AdCampaign::where('business_page_id', $businessPage->id)->where('status', AdCampaign::STATUS_COMPLETED)->count(),
            'total_budget' => round((float) AdCampaign::where('business_page_id', $businessPage->id)->sum('budget'), 2),
            'total_spent' => round((float) AdCampaign::where('business_page_id', $businessPage->id)->sum('spent_amount'), 2),
            'total_remaining' => round((float) AdCampaign::where('business_page_id', $businessPage->id)->sum('remaining_amount'), 2),
            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'average_ctr' => $avgCtr,
            'member_ad_balance' => $availableAdFunds,
            'available_ad_funds' => $availableAdFunds,
            'campaign_platform_fee_percent' => $campaignFeePercent,
        ];

        $postsCount = Post::where('business_page_id', $businessPage->id)->count();
        $availablePosts = Post::where('business_page_id', $businessPage->id)
            ->latest()
            ->take(50)
            ->get(['id', 'body', 'media_type', 'media_path', 'created_at']);

        return response()->json([
            'success' => true,
            'campaigns' => $campaigns,
            'metrics' => $metrics,
            'statuses' => AdCampaign::STATUSES,
            'approval_statuses' => AdCampaign::APPROVAL_STATUSES,
            'posts_count' => $postsCount,
            'has_content' => $postsCount > 0,
            'available_posts' => $availablePosts,
        ]);
    }

    /**
     * Store a new draft advertising campaign for the Business Page.
     */
    public function store(Request $request, BusinessPage $businessPage)
    {
        $member = auth('member')->user();

        if ($member && method_exists($member, 'isMobileVerified') && !$member->isMobileVerified()) {
            return response()->json([
                'success' => false,
                'message' => EnsureMemberMobileVerified::UNVERIFIED_MESSAGE,
            ], 403);
        }

        if (!$businessPage->isOwner($member->id) && !$businessPage->isTeamAdmin($member->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only the Business Page owner or page admins can create ad campaigns.',
            ], 403);
        }

        // Business Rule: Business Page MUST have at least one piece of content (post, photo, etc.)
        if ($businessPage->posts()->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Please add at least one post or photo to your Business Page before running an ad campaign.',
                'errors' => [
                    'business_page' => ['Please add at least one post or photo to your Business Page before running an ad campaign.'],
                ],
            ], 422);
        }

        $validated = $request->validate([
            'campaign_name' => ['required', 'string', 'max:255'],
            'post_id' => ['nullable', 'integer', 'exists:posts,id'],
            'budget' => ['required', 'numeric', 'gt:0', 'max:999999999.99'],
            'currency' => ['nullable', 'string', 'in:USD,usd'],
            'start_at' => ['nullable', 'date'],
            'target_audience' => ['nullable', 'array'],
        ]);

        // Validate Post Ownership: Post MUST belong to this Business Page
        if (!empty($validated['post_id'])) {
            $post = Post::find($validated['post_id']);
            if (!$post || (int) $post->business_page_id !== (int) $businessPage->id) {
                throw ValidationException::withMessages([
                    'post_id' => ['The selected post does not belong to this Business Page.'],
                ]);
            }

            // ONE POST = ONE CAMPAIGN RULE
            $existingCampaign = AdCampaign::where('post_id', $validated['post_id'])->first();
            if ($existingCampaign) {
                throw ValidationException::withMessages([
                    'post_id' => [
                        "This post already has an ad campaign ('{$existingCampaign->campaign_name}'). Add funds to the existing campaign instead of creating a new one."
                    ],
                ]);
            }
        }

        $campaignBudget = round((float) $validated['budget'], 2);
        $feePercent = (float) Setting::get('campaign_platform_fee_percent', 2.50);
        $feeAmount = round($campaignBudget * ($feePercent / 100), 2);
        $totalWalletDebit = round($campaignBudget + $feeAmount, 2);

        // Atomic budget reservation and fee debit from member's available ad balance
        $campaign = DB::transaction(function () use ($member, $businessPage, $validated, $campaignBudget, $feePercent, $feeAmount, $totalWalletDebit) {
            // Re-check concurrency inside transaction with lock
            if (!empty($validated['post_id'])) {
                $existingCampaignInTx = AdCampaign::where('post_id', $validated['post_id'])->lockForUpdate()->first();
                if ($existingCampaignInTx) {
                    throw ValidationException::withMessages([
                        'post_id' => [
                            "This post already has an ad campaign ('{$existingCampaignInTx->campaign_name}'). Add funds to the existing campaign instead of creating a new one."
                        ],
                    ]);
                }
            }

            /** @var Member $lockedMember */
            $lockedMember = Member::where('id', $member->id)->lockForUpdate()->first();
            $availableFunds = (float) ($lockedMember->p2p_wallet ?? 0.00);

            if ($availableFunds < $totalWalletDebit) {
                $shortfall = round($totalWalletDebit - $availableFunds, 2);
                throw ValidationException::withMessages([
                    'budget' => [
                        "Insufficient advertising funds. Campaign Budget: \${$campaignBudget} USD, Platform Fee ({$feePercent}%): \${$feeAmount} USD, Total Required: \${$totalWalletDebit} USD, Available: \${$availableFunds} USD. Shortfall: \${$shortfall} USD. Please add funds."
                    ],
                ]);
            }

            // Deduct total debit (budget + platform fee) from member's Fund Wallet (p2p_wallet)
            $lockedMember->p2p_wallet = round($availableFunds - $totalWalletDebit, 2);
            $lockedMember->save();

            return AdCampaign::create([
                'business_page_id' => $businessPage->id,
                'member_id' => $member->id,
                'post_id' => $validated['post_id'] ?? null,
                'campaign_name' => trim($validated['campaign_name']),
                'budget' => $campaignBudget,
                'additional_funding' => 0.00,
                'total_funded' => $campaignBudget,
                'currency' => 'USD',
                'fee_percent' => $feePercent,
                'fee_amount' => $feeAmount,
                'wallet_debit' => $totalWalletDebit,
                'spent_amount' => 0.00,
                'remaining_amount' => $campaignBudget,
                'start_at' => $validated['start_at'] ?? null,
                'target_audience' => $validated['target_audience'] ?? null,
                'status' => AdCampaign::STATUS_DRAFT,
                'approval_status' => AdCampaign::APPROVAL_PENDING,
            ]);
        });

        \App\Services\AdminNotificationService::notify(
            title: 'New Ad Campaign Created',
            message: sprintf('Campaign "%s" was created for "%s" and submitted for review.', $campaign->campaign_name, $businessPage->page_name),
            icon: 'megaphone',
            sourceType: 'ad_campaign',
            sourceId: (string) $campaign->id,
            actionUrl: '/admin/ad-campaigns',
            metadata: ['campaign_id' => $campaign->id, 'business_page_id' => $businessPage->id]
        );

        return response()->json([
            'success' => true,
            'message' => 'Ad campaign draft created successfully.',
            'campaign' => $campaign->load(['post', 'owner:id,name,user_id,email', 'businessPage:id,page_name,slug']),
        ], 201);
    }

    /**
     * Add funds (Top-up) to an existing ad campaign and reactivate if exhausted.
     * Owner-only and atomic with pessimistic locking.
     */
    public function addFunds(Request $request, BusinessPage $businessPage, $campaignIdentifier)
    {
        $member = auth('member')->user();
        if ($member) {
            $member = $member->fresh() ?? $member;
        }

        if ($member && method_exists($member, 'isMobileVerified') && !$member->isMobileVerified()) {
            return response()->json([
                'success' => false,
                'message' => EnsureMemberMobileVerified::UNVERIFIED_MESSAGE,
            ], 403);
        }

        $campaign = $this->resolveCampaign($businessPage, $campaignIdentifier);

        // Ownership and authorization verification
        if (!$businessPage->isOwner($member->id) && !$businessPage->isTeamAdmin($member->id) && (int) $campaign->member_id !== (int) $member->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only the campaign owner or business page admin can add funds to this campaign.',
            ], 403);
        }

        // Business Rule: Business Page MUST have at least one piece of content (post, photo, etc.)
        if ($businessPage->posts()->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Please add at least one post or photo to your Business Page before running an ad campaign.',
                'errors' => [
                    'business_page' => ['Please add at least one post or photo to your Business Page before running an ad campaign.'],
                ],
            ], 422);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1.00', 'max:999999999.99'],
        ], [
            'amount.required' => 'Please specify the amount of funds to add to the campaign.',
            'amount.min' => 'Minimum top-up amount is $1.00 USD.',
        ]);

        $topUpAmount = round((float) $validated['amount'], 2);
        $feePercent = (float) ($campaign->fee_percent ?? Setting::get('campaign_platform_fee_percent', 2.50));
        $feeAmount = round($topUpAmount * ($feePercent / 100), 2);
        $totalWalletDebit = round($topUpAmount + $feeAmount, 2);

        $updatedCampaign = DB::transaction(function () use ($campaign, $member, $topUpAmount, $feeAmount, $totalWalletDebit) {
            /** @var Member $lockedMember */
            $lockedMember = Member::where('id', $member->id)->lockForUpdate()->first();
            $availableFunds = (float) ($lockedMember->p2p_wallet ?? 0.00);

            if ($availableFunds < $totalWalletDebit) {
                $shortfall = round($totalWalletDebit - $availableFunds, 2);
                throw ValidationException::withMessages([
                    'amount' => [
                        "Insufficient advertising funds to add funds. Top-up Amount: \${$topUpAmount} USD, Platform Fee: \${$feeAmount} USD, Total Required: \${$totalWalletDebit} USD, Available: \${$availableFunds} USD. Shortfall: \${$shortfall} USD. Please deposit funds first."
                    ],
                ]);
            }

            // Deduct total debit (top-up + fee) from member's Fund Wallet (p2p_wallet)
            $lockedMember->p2p_wallet = round($availableFunds - $totalWalletDebit, 2);
            $lockedMember->save();

            /** @var AdCampaign $lockedCampaign */
            $lockedCampaign = AdCampaign::where('id', $campaign->id)->lockForUpdate()->first();

            $newAdditional = round((float) ($lockedCampaign->additional_funding ?? 0.00) + $topUpAmount, 2);
            $newTotalFunded = round((float) $lockedCampaign->budget + $newAdditional, 2);
            $newRemaining = round((float) ($lockedCampaign->remaining_amount ?? 0.00) + $topUpAmount, 2);
            $newFeeAmount = round((float) ($lockedCampaign->fee_amount ?? 0.00) + $feeAmount, 2);
            $newWalletDebit = round((float) ($lockedCampaign->wallet_debit ?? 0.00) + $totalWalletDebit, 2);

            $lockedCampaign->additional_funding = $newAdditional;
            $lockedCampaign->total_funded = $newTotalFunded;
            $lockedCampaign->remaining_amount = $newRemaining;
            $lockedCampaign->fee_amount = $newFeeAmount;
            $lockedCampaign->wallet_debit = $newWalletDebit;

            // Reactivate campaign if it was budget_exhausted, stopped, or completed due to budget exhaustion and now has >= min active reward
            $minActiveReward = AdRewardRule::getMinimumActiveRewardAmount();
            if ($newRemaining >= $minActiveReward &&
                in_array($lockedCampaign->status, [AdCampaign::STATUS_BUDGET_EXHAUSTED, AdCampaign::STATUS_STOPPED, AdCampaign::STATUS_COMPLETED], true) &&
                $lockedCampaign->approval_status === AdCampaign::APPROVAL_APPROVED) {
                $lockedCampaign->status = AdCampaign::STATUS_ACTIVE;
            }

            $lockedCampaign->save();

            return $lockedCampaign;
        });

        return response()->json([
            'success' => true,
            'message' => "Successfully added \${$topUpAmount} USD to campaign '{$campaign->campaign_name}'. New running budget: \${$updatedCampaign->remaining_amount} USD.",
            'campaign' => $updatedCampaign->fresh(['post', 'owner:id,name,user_id,email', 'businessPage:id,page_name,slug']),
            'available_ad_funds' => round((float) ($member->fresh()->p2p_wallet ?? 0.00), 2),
        ]);
    }

    /**
     * Check if a post already has an existing ad campaign.
     */
    public function checkPostCampaign(Request $request, BusinessPage $businessPage, Post $post)
    {
        $campaign = AdCampaign::where('post_id', $post->id)->first();

        return response()->json([
            'success' => true,
            'has_campaign' => $campaign !== null,
            'campaign' => $campaign ? $campaign->load(['owner:id,name,user_id,email', 'businessPage:id,page_name,slug']) : null,
        ]);
    }

    /**
     * Update an existing draft advertising campaign.
     */
    public function update(Request $request, BusinessPage $businessPage, $campaignIdentifier)
    {
        $member = auth('member')->user();
        $campaign = $this->resolveCampaign($businessPage, $campaignIdentifier);

        if (!$businessPage->isOwner($member->id) && !$businessPage->isTeamAdmin($member->id) && (int) $campaign->member_id !== (int) $member->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this ad campaign.',
            ], 403);
        }

        if ($campaign->status !== AdCampaign::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft campaigns can be edited. Current status is: ' . $campaign->status,
            ], 422);
        }

        $validated = $request->validate([
            'campaign_name' => ['sometimes', 'required', 'string', 'max:255'],
            'post_id' => ['nullable', 'integer', 'exists:posts,id'],
            'budget' => ['sometimes', 'required', 'numeric', 'gt:0', 'max:999999999.99'],
            'currency' => ['nullable', 'string', 'in:USD,usd'],
            'start_at' => ['nullable', 'date'],
            'target_audience' => ['nullable', 'array'],
        ]);

        if (array_key_exists('post_id', $validated) && !empty($validated['post_id'])) {
            $post = Post::find($validated['post_id']);
            if (!$post || (int) $post->business_page_id !== (int) $businessPage->id) {
                throw ValidationException::withMessages([
                    'post_id' => ['The selected post does not belong to this Business Page.'],
                ]);
            }
        }

        $updateData = [];
        if (isset($validated['campaign_name'])) {
            $updateData['campaign_name'] = trim($validated['campaign_name']);
        }
        if (array_key_exists('post_id', $validated)) {
            $updateData['post_id'] = $validated['post_id'];
        }
        if (array_key_exists('start_at', $validated)) {
            $updateData['start_at'] = $validated['start_at'];
        }
        if (array_key_exists('target_audience', $validated)) {
            $updateData['target_audience'] = $validated['target_audience'];
        }

        DB::transaction(function () use ($campaign, $member, $validated, &$updateData) {
            if (isset($validated['budget'])) {
                $newBudget = round((float) $validated['budget'], 2);
                $feePercent = (float) ($campaign->fee_percent ?? Setting::get('campaign_platform_fee_percent', 2.50));
                $newFeeAmount = round($newBudget * ($feePercent / 100), 2);
                $newTotalDebit = round($newBudget + $newFeeAmount, 2);

                $oldTotalDebit = (float) ($campaign->wallet_debit ?: round((float) $campaign->budget + ((float) $campaign->budget * ($feePercent / 100)), 2));
                $deltaDebit = round($newTotalDebit - $oldTotalDebit, 2);

                if ($deltaDebit > 0) {
                    /** @var Member $lockedMember */
                    $lockedMember = Member::where('id', $member->id)->lockForUpdate()->first();
                    $available = (float) ($lockedMember->p2p_wallet ?? 0.00);

                    if ($available < $deltaDebit) {
                        $shortfall = round($deltaDebit - $available, 2);
                        throw ValidationException::withMessages([
                            'budget' => [
                                "Insufficient advertising funds to increase budget. Additional required (budget + fee): \${$deltaDebit} USD, Available: \${$available} USD. Shortfall: \${$shortfall} USD."
                            ],
                        ]);
                    }

                    $lockedMember->p2p_wallet = round($available - $deltaDebit, 2);
                    $lockedMember->save();
                } elseif ($deltaDebit < 0) {
                    $refund = abs($deltaDebit);
                    /** @var Member $lockedMember */
                    $lockedMember = Member::where('id', $member->id)->lockForUpdate()->first();
                    if ($lockedMember) {
                        $lockedMember->p2p_wallet = round((float) ($lockedMember->p2p_wallet ?? 0.00) + $refund, 2);
                        $lockedMember->save();
                    }
                }

                $updateData['budget'] = $newBudget;
                $updateData['remaining_amount'] = $newBudget;
                $updateData['fee_percent'] = $feePercent;
                $updateData['fee_amount'] = $newFeeAmount;
                $updateData['wallet_debit'] = $newTotalDebit;
            }

            $campaign->update($updateData);
        });

        return response()->json([
            'success' => true,
            'message' => 'Ad campaign updated successfully.',
            'campaign' => $campaign->fresh(['post', 'owner:id,name,user_id,email', 'businessPage:id,page_name,slug']),
        ]);
    }

    /**
     * Display the specified campaign details.
     */
    public function show(Request $request, BusinessPage $businessPage, $campaignIdentifier)
    {
        $member = auth('member')->user();

        $campaign = $this->resolveCampaign($businessPage, $campaignIdentifier);

        if (!$businessPage->isOwner($member->id) && !$businessPage->isTeamAdmin($member->id) && (int) $campaign->member_id !== (int) $member->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view this ad campaign.',
            ], 403);
        }

        $campaign->load(['post', 'owner:id,name,user_id,email', 'businessPage:id,page_name,slug', 'approver:id,name,email'])
            ->loadCount(['impressions', 'clicks']);

        $campaign->ctr = $campaign->ctr;

        return response()->json([
            'success' => true,
            'campaign' => $campaign,
        ]);
    }

    /**
     * Submit a draft campaign for admin review.
     */
    public function submit(Request $request, BusinessPage $businessPage, $campaignIdentifier)
    {
        $member = auth('member')->user();
        $campaign = $this->resolveCampaign($businessPage, $campaignIdentifier);

        if (!$businessPage->isOwner($member->id) && !$businessPage->isTeamAdmin($member->id) && (int) $campaign->member_id !== (int) $member->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to submit this ad campaign.',
            ], 403);
        }

        // Business Rule: Business Page MUST have at least one piece of content (post, photo, etc.)
        if ($businessPage->posts()->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Please add at least one post or photo to your Business Page before running an ad campaign.',
                'errors' => [
                    'business_page' => ['Please add at least one post or photo to your Business Page before running an ad campaign.'],
                ],
            ], 422);
        }

        if ($campaign->status !== AdCampaign::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft campaigns can be submitted for review. Current status is: ' . $campaign->status,
            ], 422);
        }

        $campaign->submitForReview();

        return response()->json([
            'success' => true,
            'message' => 'Campaign submitted for review successfully. It will be reviewed by an administrator.',
            'campaign' => $campaign->fresh(['post', 'owner:id,name,user_id,email', 'businessPage:id,page_name,slug']),
        ]);
    }

    /**
     * Pause an active or approved campaign.
     */
    public function pause(Request $request, BusinessPage $businessPage, $campaignIdentifier)
    {
        $member = auth('member')->user();
        $campaign = $this->resolveCampaign($businessPage, $campaignIdentifier);

        if (!$businessPage->isOwner($member->id) && !$businessPage->isTeamAdmin($member->id) && (int) $campaign->member_id !== (int) $member->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to pause this ad campaign.',
            ], 403);
        }

        if (!$campaign->pause()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot pause campaign in current status: ' . $campaign->status,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Campaign paused successfully.',
            'campaign' => $campaign->fresh(['post', 'owner:id,name,user_id,email']),
        ]);
    }

    /**
     * Resume a paused campaign.
     */
    public function resume(Request $request, BusinessPage $businessPage, $campaignIdentifier)
    {
        $member = auth('member')->user();
        $campaign = $this->resolveCampaign($businessPage, $campaignIdentifier);

        if (!$businessPage->isOwner($member->id) && !$businessPage->isTeamAdmin($member->id) && (int) $campaign->member_id !== (int) $member->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to resume this ad campaign.',
            ], 403);
        }

        // Business Rule: Business Page MUST have at least one piece of content (post, photo, etc.)
        if ($businessPage->posts()->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Please add at least one post or photo to your Business Page before running an ad campaign.',
                'errors' => [
                    'business_page' => ['Please add at least one post or photo to your Business Page before running an ad campaign.'],
                ],
            ], 422);
        }

        if (!$campaign->resume()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot resume campaign. Ensure it is approved and currently paused.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Campaign resumed successfully.',
            'campaign' => $campaign->fresh(['post', 'owner:id,name,user_id,email']),
        ]);
    }

    /**
     * Stop/cancel a campaign.
     */
    public function stop(Request $request, BusinessPage $businessPage, $campaignIdentifier)
    {
        $member = auth('member')->user();
        $campaign = $this->resolveCampaign($businessPage, $campaignIdentifier);

        if (!$businessPage->isOwner($member->id) && !$businessPage->isTeamAdmin($member->id) && (int) $campaign->member_id !== (int) $member->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to stop this ad campaign.',
            ], 403);
        }

        $refundedAmount = 0.00;
        DB::transaction(function () use ($campaign, $member, &$refundedAmount) {
            if (!$campaign->stop()) {
                throw new \RuntimeException('Campaign is already stopped or finished.');
            }

            $unspent = (float) ($campaign->remaining_amount ?? 0.00);
            if ($unspent > 0) {
                /** @var Member $lockedMember */
                $lockedMember = Member::where('id', $member->id)->lockForUpdate()->first();
                if ($lockedMember) {
                    $lockedMember->p2p_wallet = round((float) ($lockedMember->p2p_wallet ?? 0.00) + $unspent, 2);
                    $lockedMember->save();
                    $refundedAmount = $unspent;
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => $refundedAmount > 0
                ? "Campaign stopped successfully. Unspent \${$refundedAmount} USD refunded to your advertising funds."
                : 'Campaign stopped successfully.',
            'refunded_amount' => $refundedAmount,
            'campaign' => $campaign->fresh(['post', 'owner:id,name,user_id,email']),
        ]);
    }

    /**
     * Resolve campaign by ID or campaign_id slug, ensuring it belongs to the given business page.
     */
    protected function resolveCampaign(BusinessPage $businessPage, $campaignIdentifier): AdCampaign
    {
        if ($campaignIdentifier instanceof AdCampaign) {
            return $campaignIdentifier;
        }

        $campaign = AdCampaign::where('business_page_id', $businessPage->id)
            ->where(function ($q) use ($campaignIdentifier) {
                if (is_numeric($campaignIdentifier)) {
                    $q->where('id', $campaignIdentifier)->orWhere('campaign_id', $campaignIdentifier);
                } else {
                    $q->where('campaign_id', $campaignIdentifier);
                }
            })
            ->firstOrFail();

        return $campaign;
    }

    /**
     * Record a delivery impression for an ad campaign with deduplication.
     */
    public function recordImpression(Request $request, $campaignIdentifier)
    {
        $member = auth('member')->user();

        if (!$member || !method_exists($member, 'isMobileVerified') || !$member->isMobileVerified()) {
            return response()->json([
                'success' => false,
                'message' => 'Only verified members are eligible for advertising delivery and impressions.',
                'verified_required' => true,
            ], 403);
        }

        $campaign = AdCampaign::query()
            ->where(function ($q) use ($campaignIdentifier) {
                if (is_numeric($campaignIdentifier)) {
                    $q->where('id', $campaignIdentifier)->orWhere('campaign_id', $campaignIdentifier);
                } else {
                    $q->where('campaign_id', $campaignIdentifier);
                }
            })
            ->first();

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Ad campaign not found.',
            ], 404);
        }

        $validated = $request->validate([
            'impression_key' => ['nullable', 'string', 'max:64'],
            'placement' => ['nullable', 'string', 'max:50'],
        ]);

        $impressionKey = $validated['impression_key'] ?? null;
        $placement = $validated['placement'] ?? 'social_feed';

        // Deduplication: if an impression with this impression_key already exists within last 5 minutes, ignore duplicate
        if ($impressionKey) {
            $recent = AdImpression::where('ad_campaign_id', $campaign->id)
                ->where('impression_key', $impressionKey)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->exists();

            if ($recent) {
                return response()->json([
                    'success' => true,
                    'message' => 'Duplicate impression ignored.',
                    'deduplicated' => true,
                ]);
            }
        }

        AdImpression::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $member->id,
            'post_id' => $campaign->post_id,
            'placement' => $placement,
            'impression_key' => $impressionKey,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Impression recorded successfully.',
        ]);
    }

    /**
     * Record a user click event for an ad campaign with deduplication.
     */
    public function recordClick(Request $request, $campaignIdentifier)
    {
        $member = auth('member')->user();

        if (!$member || !method_exists($member, 'isMobileVerified') || !$member->isMobileVerified()) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your phone number first before proceeding.',
                'error_code' => 'PHONE_VERIFICATION_REQUIRED',
                'verified_required' => true,
                'needs_verification' => true,
            ], 403);
        }

        $campaign = AdCampaign::query()
            ->where(function ($q) use ($campaignIdentifier) {
                if (is_numeric($campaignIdentifier)) {
                    $q->where('id', $campaignIdentifier)->orWhere('campaign_id', $campaignIdentifier);
                } else {
                    $q->where('campaign_id', $campaignIdentifier);
                }
            })
            ->first();

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Ad campaign not found.',
            ], 404);
        }

        $validated = $request->validate([
            'click_key' => ['nullable', 'string', 'max:64'],
            'placement' => ['nullable', 'string', 'max:50'],
        ]);

        $clickKey = $validated['click_key'] ?? null;
        $placement = $validated['placement'] ?? 'social_feed';

        // Deduplication: if a click with this click_key already exists within last 5 minutes, ignore duplicate
        if ($clickKey) {
            $recent = AdClick::where('ad_campaign_id', $campaign->id)
                ->where('click_key', $clickKey)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->exists();

            if ($recent) {
                return response()->json([
                    'success' => true,
                    'message' => 'Duplicate click ignored.',
                    'deduplicated' => true,
                ]);
            }
        }

        AdClick::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $member->id,
            'post_id' => $campaign->post_id,
            'placement' => $placement,
            'click_key' => $clickKey,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'created_at' => now(),
        ]);

        // Record authoritative Ad Campaign Activity
        app(AdCampaignEngagementService::class)->recordClick(
            $campaign,
            $member,
            $placement,
            $clickKey,
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'success' => true,
            'message' => 'Click recorded successfully.',
        ]);
    }

    /**
     * Qualify and record a verified user landing-page visit dynamic reward.
     * Atomically resolves reward from RewardRuleResolver, deducts exact amount from campaign
     * running budget, saves immutable rule snapshot, and auto-stops when remaining < MIN_ACTIVE_REWARD.
     */
    public function qualifyVisit(Request $request, $campaignIdentifier)
    {
        $member = auth('member')->user();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'rewarded' => false,
                'reward_amount_usd' => 0.00,
            ], 401);
        }

        // VERIFIED USERS ONLY: Must satisfy existing mobile-verified member logic
        if (!method_exists($member, 'isMobileVerified') || !$member->isMobileVerified()) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your phone number first before proceeding.',
                'error_code' => 'PHONE_VERIFICATION_REQUIRED',
                'verified_required' => true,
                'needs_verification' => true,
                'rewarded' => false,
                'reward_amount_usd' => 0.00,
            ], 403);
        }

        $campaign = AdCampaign::query()
            ->where(function ($q) use ($campaignIdentifier) {
                if (is_numeric($campaignIdentifier)) {
                    $q->where('id', $campaignIdentifier)->orWhere('campaign_id', $campaignIdentifier);
                } else {
                    $q->where('campaign_id', $campaignIdentifier);
                }
            })
            ->first();

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Ad campaign not found.',
                'rewarded' => false,
                'reward_amount_usd' => 0.00,
            ], 404);
        }

        // Authoritative Owner Gate: Campaign owners cannot earn rewards from their own campaigns
        if ($campaign->isOwner($member->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot earn rewards from your own campaign.',
                'error_code' => 'OWNER_REWARD_PROHIBITED',
                'is_owner' => true,
                'rewarded' => false,
                'reward_amount_usd' => 0.00,
            ], 403);
        }

        $validated = $request->validate([
            'qualifying_event_id' => ['required', 'string', 'max:100'],
            'landing_page_url' => ['nullable', 'string', 'max:2048'],
        ]);

        $qualifyingEventId = trim((string) $validated['qualifying_event_id']);
        $landingPageUrl = !empty($validated['landing_page_url']) ? trim((string) $validated['landing_page_url']) : null;

        // 1. Pre-check if member already received the 1-time reward for this campaign
        $alreadyRewarded = AdReward::where('ad_campaign_id', $campaign->id)
            ->where('member_id', $member->id)
            ->where('status', AdReward::STATUS_CREDITED)
            ->exists();

        if ($alreadyRewarded) {
            return response()->json([
                'success' => false,
                'message' => 'You have already claimed your reward for this campaign.',
                'rewarded' => false,
                'already_rewarded' => true,
                'reward_amount_usd' => 0.00,
                'member_wallet' => (float) ($member->wallet ?? 0.00),
                'member_reward_balance' => (float) ($member->wallet ?? 0.00),
            ], 422);
        }

        // Atomic Transaction with Pessimistic Row Locking
        $result = DB::transaction(function () use ($campaign, $member, $qualifyingEventId, $landingPageUrl, $request) {
            // Re-verify Member inside transaction with lock
            /** @var Member $lockedMember */
            $lockedMember = Member::where('id', $member->id)->lockForUpdate()->first();
            if (!$lockedMember || !method_exists($lockedMember, 'isMobileVerified') || !$lockedMember->isMobileVerified()) {
                return [
                    'success' => false,
                    'status_code' => 403,
                    'message' => 'Please verify your phone number first before proceeding.',
                    'error_code' => 'PHONE_VERIFICATION_REQUIRED',
                    'verified_required' => true,
                    'needs_verification' => true,
                    'rewarded' => false,
                    'reward_amount_usd' => 0.00,
                ];
            }

            /** @var AdCampaign $lockedCampaign */
            $lockedCampaign = AdCampaign::where('id', $campaign->id)->lockForUpdate()->first();

            if (!$lockedCampaign) {
                return [
                    'success' => false,
                    'status_code' => 404,
                    'message' => 'Ad campaign not found.',
                    'rewarded' => false,
                    'reward_amount_usd' => 0.00,
                ];
            }

            if ($lockedCampaign->isOwner($lockedMember->id)) {
                return [
                    'success' => false,
                    'status_code' => 403,
                    'message' => 'You cannot earn rewards from your own campaign.',
                    'error_code' => 'OWNER_REWARD_PROHIBITED',
                    'is_owner' => true,
                    'rewarded' => false,
                    'reward_amount_usd' => 0.00,
                ];
            }

            // 1. Authoritative resolution of member reward tier & amount
            $resolution = app(RewardRuleResolver::class)->resolveForMember($lockedMember);
            if (!$resolution['success']) {
                return [
                    'success' => false,
                    'status_code' => 422,
                    'message' => $resolution['message'] ?? 'Ad reward rule is not configured for your referral tier.',
                    'rewarded' => false,
                    'reward_amount_usd' => 0.00,
                ];
            }

            $rewardAmount = (float) $resolution['reward_amount_usd'];
            $rewardAmountExact = (string) $resolution['reward_amount_exact'];
            $snapshot = $resolution['snapshot'] ?? [];
            $minActiveReward = AdRewardRule::getMinimumActiveRewardAmount();

            // 2. Verify Campaign is Active and Approved
            if ($lockedCampaign->approval_status !== AdCampaign::APPROVAL_APPROVED ||
                !in_array($lockedCampaign->status, [AdCampaign::STATUS_ACTIVE, AdCampaign::STATUS_APPROVED], true)) {
                $isExhausted = (float) ($lockedCampaign->remaining_amount ?? 0.00) < $minActiveReward;
                return [
                    'success' => false,
                    'status_code' => 422,
                    'message' => $isExhausted
                        ? 'Campaign budget exhausted. Campaign is stopped.'
                        : 'Ad campaign is not active or approved. No reward can be processed.',
                    'campaign_status' => $lockedCampaign->status,
                    'remaining_budget' => (float) ($lockedCampaign->remaining_amount ?? 0.00),
                    'rewarded' => false,
                    'reward_amount_usd' => 0.00,
                    'exhausted' => $isExhausted,
                ];
            }

            $currentRemaining = (float) ($lockedCampaign->remaining_amount ?? 0.00);

            // 3. Check Campaign Running Budget Sufficiency for this member's reward tier
            if ($currentRemaining < $rewardAmount) {
                // Auto-stop campaign if remaining budget is globally exhausted (< MIN_ACTIVE_REWARD)
                if ($currentRemaining < $minActiveReward &&
                    !in_array($lockedCampaign->status, [AdCampaign::STATUS_STOPPED, AdCampaign::STATUS_COMPLETED], true)) {
                    $lockedCampaign->status = AdCampaign::STATUS_STOPPED;
                    $lockedCampaign->save();
                }

                return [
                    'success' => false,
                    'status_code' => 422,
                    'message' => $currentRemaining < $minActiveReward
                        ? 'Campaign budget exhausted. Campaign has been stopped automatically.'
                        : 'Campaign budget is insufficient for your reward tier.',
                    'campaign_status' => $lockedCampaign->status,
                    'remaining_budget' => $currentRemaining,
                    'rewarded' => false,
                    'reward_amount_usd' => 0.00,
                    'exhausted' => $currentRemaining < $minActiveReward,
                ];
            }

            // 4. Concurrency check inside transaction: exactly 1 reward per member per campaign
            $alreadyRewardedInTx = AdReward::where('ad_campaign_id', $lockedCampaign->id)
                ->where('member_id', $lockedMember->id)
                ->where('status', AdReward::STATUS_CREDITED)
                ->lockForUpdate()
                ->exists();

            if ($alreadyRewardedInTx) {
                return [
                    'success' => false,
                    'status_code' => 422,
                    'message' => 'Member has already received the one-time qualifying reward for this campaign.',
                    'rewarded' => false,
                    'reward_amount_usd' => 0.00,
                    'already_rewarded' => true,
                    'duplicate' => true,
                ];
            }

            // 5. Deduct exact reward amount from Campaign Running Budget & update Spent Amount
            $newRemaining = max(0.00, round($currentRemaining - $rewardAmount, 4));
            $newSpent = round((float) ($lockedCampaign->spent_amount ?? 0.00) + $rewardAmount, 4);

            $lockedCampaign->remaining_amount = $newRemaining;
            $lockedCampaign->spent_amount = $newSpent;

            // 6. Auto-Stop campaign if remaining budget is now less than MIN_ACTIVE_REWARD
            if ($newRemaining < $minActiveReward) {
                $lockedCampaign->status = AdCampaign::STATUS_STOPPED;
            }

            $lockedCampaign->save();

            // 7. Create AdReward record in ledger with complete immutable rule snapshot
            try {
                $reward = AdReward::create([
                    'ad_campaign_id' => $lockedCampaign->id,
                    'member_id' => $lockedMember->id,
                    'ad_reward_rule_id' => $snapshot['ad_reward_rule_id'] ?? null,
                    'direct_verified_referral_count' => $snapshot['direct_verified_referral_count'] ?? 0,
                    'rule_min_referrals' => $snapshot['min_referrals'] ?? null,
                    'rule_max_referrals' => $snapshot['max_referrals'] ?? null,
                    'rule_version' => (string) ($snapshot['rule_version'] ?? ''),
                    'reward_amount_usd' => $rewardAmount,
                    'qualifying_event_id' => $qualifyingEventId,
                    'landing_page_url' => $landingPageUrl,
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 500),
                    'status' => AdReward::STATUS_CREDITED,
                ]);

                // Record authoritative Rewarded Activity in ad_campaign_activities
                app(AdCampaignEngagementService::class)->recordRewarded(
                    $lockedCampaign,
                    $lockedMember,
                    $reward,
                    $request->ip(),
                    $request->userAgent()
                );
            } catch (\Throwable $e) {
                // Duplicate key or unique constraint catch -> safe rollback
                throw $e;
            }

            // 8. Credit Member's wallet balance atomically
            $lockedMember->wallet = round((float) ($lockedMember->wallet ?? 0.00) + $rewardAmount, 2);
            $lockedMember->save();
            $newWalletBalance = (float) $lockedMember->wallet;

            return [
                'success' => true,
                'status_code' => 200,
                'message' => "Qualifying visit confirmed. \${$rewardAmountExact} USD reward credited successfully to Wallet.",
                'rewarded' => true,
                'reward_amount_usd' => $rewardAmount,
                'reward_amount_exact' => $rewardAmountExact,
                'direct_verified_referrals' => $snapshot['direct_verified_referral_count'] ?? 0,
                'rule_snapshot' => $snapshot,
                'remaining_budget' => $newRemaining,
                'spent_budget' => $newSpent,
                'campaign_status' => $lockedCampaign->status,
                'reward_id' => $reward->id,
                'member_wallet' => $newWalletBalance,
                'member_reward_balance' => $newWalletBalance,
                'reward_wallet_balance' => $newWalletBalance,
            ];
        });

        $statusCode = $result['status_code'] ?? 200;
        unset($result['status_code']);

        return response()->json($result, $statusCode);
    }

    /**
     * Resolve campaign model by ID or campaign_id string.
     */
    protected function resolvePublicCampaign($campaignIdentifier): ?AdCampaign
    {
        return AdCampaign::query()
            ->where(function ($q) use ($campaignIdentifier) {
                if (is_numeric($campaignIdentifier)) {
                    $q->where('id', $campaignIdentifier)->orWhere('campaign_id', $campaignIdentifier);
                } else {
                    $q->where('campaign_id', $campaignIdentifier);
                }
            })
            ->first();
    }

    /**
     * Record Member Interest in an Ad Campaign from the feed.
     * Verified members only. Does NOT reward directly.
     */
    public function interest(Request $request, $campaignIdentifier)
    {
        $consent = $request->input('consent_accepted');
        if ($consent !== true && $consent !== 'true') {
            return response()->json([
                'success' => false,
                'message' => 'Please accept the profile-sharing consent before continuing.',
                'errors' => [
                    'consent_accepted' => ['Please accept the profile-sharing consent before continuing.'],
                ],
            ], 422);
        }

        $member = auth('member')->user();

        if (!$member || !method_exists($member, 'isMobileVerified') || !$member->isMobileVerified()) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your phone number first before proceeding.',
                'error_code' => 'PHONE_VERIFICATION_REQUIRED',
                'verified_required' => true,
                'needs_verification' => true,
            ], 403);
        }

        $campaign = $this->resolvePublicCampaign($campaignIdentifier);
        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Ad campaign not found.',
            ], 404);
        }

        // Authoritative Owner Gate: Campaign owners cannot submit participant interest or earn rewards from their own campaigns
        if ($campaign->isOwner($member->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot earn rewards from your own campaign.',
                'error_code' => 'OWNER_REWARD_PROHIBITED',
                'is_owner' => true,
            ], 403);
        }

        // Verify active & approved
        if ($campaign->approval_status !== AdCampaign::APPROVAL_APPROVED ||
            !in_array($campaign->status, [AdCampaign::STATUS_ACTIVE, AdCampaign::STATUS_APPROVED], true)) {
            return response()->json([
                'success' => false,
                'message' => 'This ad campaign is no longer active.',
                'campaign_status' => $campaign->status,
            ], 422);
        }

        // Record Interested interaction event in ad_clicks
        $clickKey = 'int_' . $campaign->id . '_' . $member->id . '_' . time();
        AdClick::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $member->id,
            'click_key' => $clickKey,
            'placement' => 'interested_feed_cta',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        // Record authoritative Ad Campaign Activity
        app(AdCampaignEngagementService::class)->recordInterested(
            $campaign,
            $member,
            $request->ip(),
            $request->userAgent(),
            ['click_key' => $clickKey]
        );

        $bizPage = $campaign->businessPage;
        $targetUrl = $bizPage ? "/member/business-pages/{$bizPage->slug}?campaign={$campaign->campaign_id}&action=follow_reward" : "/member/socials";

        return response()->json([
            'success' => true,
            'message' => 'Interest recorded. Redirecting to target business page.',
            'campaign_id' => $campaign->campaign_id,
            'business_page_slug' => $bizPage?->slug,
            'target_url' => $targetUrl,
            'reward_amount_usd' => AdCampaign::REWARD_AMOUNT,
        ]);
    }

    /**
     * Get campaign landing visit reward status on the target page.
     * Follow is NOT required.
     */
    /**
     * Get dynamic campaign reward preview status for the authenticated member.
     * Consumes authoritative RewardRuleResolver and active Admin reward slabs.
     */
    public function landingRewardStatus(Request $request, $campaignIdentifier)
    {
        $member = auth('member')->user();
        if ($member) {
            $member = $member->fresh() ?? $member;
        }

        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $isVerified = method_exists($member, 'isMobileVerified') && $member->isMobileVerified();

        if (!$isVerified) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your phone number first before proceeding.',
                'error_code' => 'PHONE_VERIFICATION_REQUIRED',
                'verified_required' => true,
                'needs_verification' => true,
                'is_verified' => false,
                'eligible_to_earn' => false,
            ], 403);
        }

        $campaign = $this->resolvePublicCampaign($campaignIdentifier);

        if (!$campaign) {
            return response()->json(['success' => false, 'message' => 'Ad campaign not found.'], 404);
        }

        $isOwner = $campaign->isOwner($member->id);

        $alreadyRewarded = AdReward::where('ad_campaign_id', $campaign->id)
            ->where('member_id', $member->id)
            ->where('status', AdReward::STATUS_CREDITED)
            ->exists();

        // Authoritative resolution of member's reward eligibility based on direct verified referrals
        $resolution = app(RewardRuleResolver::class)->resolveForMember($member);
        $rewardAmount = $resolution['success'] ? (float) $resolution['reward_amount_usd'] : AdCampaign::REWARD_AMOUNT;

        // Fetch all active admin rules for dynamic display in popup
        $activeRules = AdRewardRule::getActiveRules();
        $maxReward = $activeRules->isNotEmpty() ? (float) $activeRules->max('reward_amount') : AdCampaign::REWARD_AMOUNT;
        $maxPossibleReward = min(AdRewardRule::MAX_PERMISSIBLE_REWARD_USD, max(0.05, $maxReward));

        $rulesFormatted = $activeRules->map(function (AdRewardRule $r) use ($resolution) {
            $min = (int) $r->min_referrals;
            $max = $r->max_referrals !== null ? (int) $r->max_referrals : null;
            $isCurrent = $resolution['success'] && (int) ($resolution['matched_rule_id'] ?? 0) === (int) $r->id;
            $isUnconfigured = $r->reward_amount === null || $r->reward_amount === '';

            return [
                'id' => $r->id,
                'min_referrals' => $min,
                'max_referrals' => $max,
                'label' => $min . ($max !== null ? "–{$max}" : '+'),
                'reward_amount_usd' => !$isUnconfigured ? (float) $r->reward_amount : null,
                'reward_amount_exact' => !$isUnconfigured ? number_format((float) $r->reward_amount, 4, '.', '') : null,
                'reward_amount_formatted' => !$isUnconfigured ? '$' . number_format((float) $r->reward_amount, 3) . ' USD' : 'Config Required',
                'is_unconfigured' => $isUnconfigured,
                'is_current' => $isCurrent,
            ];
        })->values();

        $hasBudget = (float) ($campaign->remaining_amount ?? 0.00) >= $rewardAmount;
        $isActive = $campaign->approval_status === AdCampaign::APPROVAL_APPROVED &&
            in_array($campaign->status, [AdCampaign::STATUS_ACTIVE, AdCampaign::STATUS_APPROVED], true);

        return response()->json([
            'success' => true,
            'campaign' => [
                'id' => $campaign->id,
                'campaign_id' => $campaign->campaign_id,
                'campaign_name' => $campaign->campaign_name,
                'reward_amount_usd' => $isOwner ? 0.00 : $rewardAmount,
                'reward_amount_exact' => $isOwner ? '0.0000' : number_format($rewardAmount, 4, '.', ''),
                'business_page_id' => $campaign->business_page_id,
                'business_page_name' => $campaign->businessPage?->page_name,
                'business_page_slug' => $campaign->businessPage?->slug,
                'is_active' => $isActive,
                'has_budget' => $hasBudget,
                'remaining_amount' => (float) ($campaign->remaining_amount ?? 0.00),
                'is_owner' => $isOwner,
            ],
            'is_owner' => $isOwner,
            'is_verified' => $isVerified,
            'already_rewarded' => $alreadyRewarded,
            'eligible_to_earn' => !$isOwner && $isVerified && !$alreadyRewarded && $hasBudget && $isActive && $resolution['success'],
            'reward_amount_usd' => $isOwner ? 0.00 : $rewardAmount,
            'reward_amount_exact' => $isOwner ? '0.0000' : number_format($rewardAmount, 4, '.', ''),
            'max_possible_reward_usd' => $isOwner ? 0.00 : $maxPossibleReward,
            'max_possible_reward_label' => $isOwner ? null : ('Earn up to $' . number_format($maxPossibleReward, 2)),
            'member_reward' => $isOwner ? [
                'direct_verified_referral_count' => $resolution['direct_verified_referral_count'] ?? 0,
                'applicable_reward_usd' => 0.00,
                'applicable_reward_exact' => '0.0000',
                'currency' => 'USD',
                'currency_symbol' => '$',
                'matched_range' => null,
                'matched_rule_id' => null,
                'status' => 'owner_prohibited',
            ] : [
                'direct_verified_referral_count' => $resolution['direct_verified_referral_count'] ?? 0,
                'applicable_reward_usd' => $resolution['success'] ? (float) $resolution['reward_amount_usd'] : 0.00,
                'applicable_reward_exact' => $resolution['success'] ? $resolution['reward_amount_exact'] : '0.0000',
                'currency' => 'USD',
                'currency_symbol' => '$',
                'matched_range' => $resolution['matched_range'] ?? null,
                'matched_rule_id' => $resolution['matched_rule_id'] ?? null,
                'status' => $resolution['status'] ?? 'resolved',
            ],
            'all_active_rules' => $rulesFormatted,
        ]);
    }

    /**
     * Get follow-to-earn reward status for a campaign on the target page.
     */
    public function followRewardStatus(Request $request, $campaignIdentifier)
    {
        $member = auth('member')->user();

        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $isVerified = method_exists($member, 'isMobileVerified') && $member->isMobileVerified();
        $campaign = $this->resolvePublicCampaign($campaignIdentifier);

        if (!$campaign) {
            return response()->json(['success' => false, 'message' => 'Ad campaign not found.'], 404);
        }

        $isOwner = $campaign->isOwner($member->id);

        $alreadyRewarded = AdReward::where('ad_campaign_id', $campaign->id)
            ->where('member_id', $member->id)
            ->where('status', AdReward::STATUS_CREDITED)
            ->exists();

        $isFollowing = BusinessFollower::where('business_page_id', $campaign->business_page_id)
            ->where('member_id', $member->id)
            ->where('status', 'accepted')
            ->exists();

        $hasBudget = (float) ($campaign->remaining_amount ?? 0.00) >= AdCampaign::REWARD_AMOUNT;
        $isActive = $campaign->approval_status === AdCampaign::APPROVAL_APPROVED &&
            in_array($campaign->status, [AdCampaign::STATUS_ACTIVE, AdCampaign::STATUS_APPROVED], true);

        return response()->json([
            'success' => true,
            'campaign' => [
                'id' => $campaign->id,
                'campaign_id' => $campaign->campaign_id,
                'campaign_name' => $campaign->campaign_name,
                'reward_amount_usd' => $isOwner ? 0.00 : AdCampaign::REWARD_AMOUNT,
                'business_page_id' => $campaign->business_page_id,
                'business_page_name' => $campaign->businessPage?->page_name,
                'business_page_slug' => $campaign->businessPage?->slug,
                'is_active' => $isActive,
                'has_budget' => $hasBudget,
                'remaining_amount' => (float) ($campaign->remaining_amount ?? 0.00),
                'is_owner' => $isOwner,
            ],
            'is_owner' => $isOwner,
            'is_verified' => $isVerified,
            'already_rewarded' => $alreadyRewarded,
            'is_following' => $isFollowing,
            'eligible_to_earn' => !$isOwner && $isVerified && !$alreadyRewarded && $hasBudget && $isActive,
            'reward_amount_usd' => $isOwner ? 0.00 : AdCampaign::REWARD_AMOUNT,
        ]);
    }

    /**
     * Follow the business page and earn the $0.05 campaign reward (Atomic & Idempotent).
     * Sequence:
     * 1. Verified Member Check (WhatsApp/mobile verified)
     * 2. Active Campaign & Remaining Budget Check (>= $0.05)
     * 3. One-Time Reward Uniqueness Check (ad_rewards uniqueness)
     * 4. Follow Business Page Relation Verification / Creation
     * 5. Deduct $0.05 from Campaign Budget & Credit $0.05 to Member Reward Wallet
     * 6. Create Ledger Record in ad_rewards
     */
    public function followToEarn(Request $request, $campaignIdentifier)
    {
        $member = auth('member')->user();
        if ($member) {
            $member = $member->fresh() ?? $member;
        }

        // 1. Authoritative verification gate
        if (!$member || !method_exists($member, 'isMobileVerified') || !$member->isMobileVerified()) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your phone number first before proceeding.',
                'error_code' => 'PHONE_VERIFICATION_REQUIRED',
                'verified_required' => true,
                'needs_verification' => true,
                'rewarded' => false,
                'reward_amount_usd' => 0.00,
            ], 403);
        }

        $campaign = $this->resolvePublicCampaign($campaignIdentifier);
        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Ad campaign not found.',
                'rewarded' => false,
                'reward_amount_usd' => 0.00,
            ], 404);
        }

        // Authoritative Owner Gate: Campaign owners cannot earn rewards from their own campaigns
        if ($campaign->isOwner($member->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot earn rewards from your own campaign.',
                'error_code' => 'OWNER_REWARD_PROHIBITED',
                'is_owner' => true,
                'rewarded' => false,
                'reward_amount_usd' => 0.00,
            ], 403);
        }

        // Check if member already received the 1-time reward for this campaign
        $alreadyRewarded = AdReward::where('ad_campaign_id', $campaign->id)
            ->where('member_id', $member->id)
            ->where('status', AdReward::STATUS_CREDITED)
            ->exists();

        if ($alreadyRewarded) {
            // Ensure follow relation is active
            BusinessFollower::firstOrCreate(
                ['business_page_id' => $campaign->business_page_id, 'member_id' => $member->id],
                ['status' => 'accepted', 'followed_at' => now()]
            );

            return response()->json([
                'success' => true,
                'message' => 'You are following this page. You have already received the one-time qualifying reward for this campaign.',
                'rewarded' => false,
                'already_rewarded' => true,
                'reward_amount_usd' => 0.00,
                'is_following' => true,
                'member_wallet' => (float) ($member->wallet ?? 0.00),
                'member_reward_balance' => (float) ($member->wallet ?? 0.00),
            ]);
        }

        // Atomic Transaction with Pessimistic Row Locking
        $result = DB::transaction(function () use ($campaign, $member, $request) {
            // Lock Member Row
            /** @var Member $lockedMember */
            $lockedMember = Member::where('id', $member->id)->lockForUpdate()->first();
            if (!$lockedMember || !method_exists($lockedMember, 'isMobileVerified') || !$lockedMember->isMobileVerified()) {
                return [
                    'success' => false,
                    'status_code' => 403,
                    'message' => 'Please verify your phone number first before proceeding.',
                    'error_code' => 'PHONE_VERIFICATION_REQUIRED',
                    'verified_required' => true,
                    'needs_verification' => true,
                    'rewarded' => false,
                    'reward_amount_usd' => 0.00,
                ];
            }

            // Lock Campaign Row
            /** @var AdCampaign $lockedCampaign */
            $lockedCampaign = AdCampaign::where('id', $campaign->id)->lockForUpdate()->first();
            if (!$lockedCampaign) {
                return [
                    'success' => false,
                    'status_code' => 404,
                    'message' => 'Ad campaign not found.',
                    'rewarded' => false,
                    'reward_amount_usd' => 0.00,
                ];
            }

            if ($lockedCampaign->isOwner($lockedMember->id)) {
                return [
                    'success' => false,
                    'status_code' => 403,
                    'message' => 'You cannot earn rewards from your own campaign.',
                    'error_code' => 'OWNER_REWARD_PROHIBITED',
                    'is_owner' => true,
                    'rewarded' => false,
                    'reward_amount_usd' => 0.00,
                ];
            }

            // Resolve dynamic reward for member
            $resolution = app(RewardRuleResolver::class)->resolveForMember($lockedMember);
            if (!$resolution['success']) {
                return [
                    'success' => false,
                    'status_code' => 422,
                    'message' => $resolution['message'] ?? 'Ad reward rule is not configured for your referral tier.',
                    'rewarded' => false,
                    'reward_amount_usd' => 0.00,
                ];
            }

            $rewardAmount = (float) $resolution['reward_amount_usd'];
            $rewardAmountExact = (string) ($resolution['reward_amount_exact'] ?? sprintf('%.4f', $rewardAmount));
            $snapshot = $resolution['snapshot'] ?? [];
            $minActiveReward = AdRewardRule::getMinimumActiveRewardAmount();

            // Verify Campaign is Active and Approved
            if ($lockedCampaign->approval_status !== AdCampaign::APPROVAL_APPROVED ||
                !in_array($lockedCampaign->status, [AdCampaign::STATUS_ACTIVE, AdCampaign::STATUS_APPROVED], true)) {
                $isExhausted = (float) ($lockedCampaign->remaining_amount ?? 0.00) < $minActiveReward;
                return [
                    'success' => false,
                    'status_code' => 422,
                    'message' => $isExhausted
                        ? 'Campaign budget exhausted. Campaign is stopped.'
                        : 'Ad campaign is not active or approved.',
                    'campaign_status' => $lockedCampaign->status,
                    'remaining_budget' => (float) ($lockedCampaign->remaining_amount ?? 0.00),
                    'rewarded' => false,
                    'reward_amount_usd' => 0.00,
                    'exhausted' => $isExhausted,
                ];
            }

            $currentRemaining = (float) ($lockedCampaign->remaining_amount ?? 0.00);

            // Check running budget
            if ($currentRemaining < $rewardAmount) {
                if ($currentRemaining < $minActiveReward &&
                    !in_array($lockedCampaign->status, [AdCampaign::STATUS_STOPPED, AdCampaign::STATUS_COMPLETED], true)) {
                    $lockedCampaign->status = AdCampaign::STATUS_STOPPED;
                    $lockedCampaign->save();
                }

                return [
                    'success' => false,
                    'status_code' => 422,
                    'message' => $currentRemaining < $minActiveReward
                        ? 'Campaign budget exhausted. Campaign has been stopped automatically.'
                        : 'Campaign budget is insufficient for your reward tier.',
                    'campaign_status' => $lockedCampaign->status,
                    'remaining_budget' => $currentRemaining,
                    'rewarded' => false,
                    'reward_amount_usd' => 0.00,
                    'exhausted' => $currentRemaining < $minActiveReward,
                ];
            }

            // Concurrency Duplicate Check inside transaction
            $alreadyRewardedInTx = AdReward::where('ad_campaign_id', $lockedCampaign->id)
                ->where('member_id', $lockedMember->id)
                ->where('status', AdReward::STATUS_CREDITED)
                ->lockForUpdate()
                ->exists();

            if ($alreadyRewardedInTx) {
                return [
                    'success' => true,
                    'status_code' => 200,
                    'message' => 'You have already received the one-time qualifying reward for this campaign.',
                    'rewarded' => false,
                    'already_rewarded' => true,
                    'reward_amount_usd' => 0.00,
                    'is_following' => true,
                    'member_wallet' => (float) ($lockedMember->wallet ?? 0.00),
                    'member_reward_balance' => (float) ($lockedMember->wallet ?? 0.00),
                ];
            }

            // Complete Follow Action
            $follower = BusinessFollower::where('business_page_id', $lockedCampaign->business_page_id)
                ->where('member_id', $lockedMember->id)
                ->first();

            if (!$follower) {
                BusinessFollower::create([
                    'business_page_id' => $lockedCampaign->business_page_id,
                    'member_id' => $lockedMember->id,
                    'status' => 'accepted',
                    'followed_at' => now(),
                ]);
            } elseif ($follower->status !== 'accepted') {
                $follower->update([
                    'status' => 'accepted',
                    'followed_at' => now(),
                ]);
            }

            // Verify follow is active
            $isFollowActive = BusinessFollower::where('business_page_id', $lockedCampaign->business_page_id)
                ->where('member_id', $lockedMember->id)
                ->where('status', 'accepted')
                ->exists();

            if (!$isFollowActive) {
                return [
                    'success' => false,
                    'status_code' => 500,
                    'message' => 'Failed to establish page follow relationship.',
                    'rewarded' => false,
                    'reward_amount_usd' => 0.00,
                ];
            }

            // Deduct reward from Campaign Running Budget & update Spent Amount
            $newRemaining = max(0.00, round($currentRemaining - $rewardAmount, 4));
            $newSpent = round((float) ($lockedCampaign->spent_amount ?? 0.00) + $rewardAmount, 4);

            $lockedCampaign->remaining_amount = $newRemaining;
            $lockedCampaign->spent_amount = $newSpent;

            if ($newRemaining < $minActiveReward) {
                $lockedCampaign->status = AdCampaign::STATUS_STOPPED;
            }

            $lockedCampaign->save();

            // Create AdReward Record in Ledger with snapshot
            $qualifyingEventId = 'follow_reward_' . $lockedCampaign->id . '_' . $lockedMember->id . '_' . time();
            try {
                $reward = AdReward::create([
                    'ad_campaign_id' => $lockedCampaign->id,
                    'member_id' => $lockedMember->id,
                    'ad_reward_rule_id' => $snapshot['ad_reward_rule_id'] ?? null,
                    'direct_verified_referral_count' => $snapshot['direct_verified_referral_count'] ?? 0,
                    'rule_min_referrals' => $snapshot['min_referrals'] ?? null,
                    'rule_max_referrals' => $snapshot['max_referrals'] ?? null,
                    'rule_version' => (string) ($snapshot['rule_version'] ?? ''),
                    'reward_amount_usd' => $rewardAmount,
                    'qualifying_event_id' => $qualifyingEventId,
                    'landing_page_url' => $request->input('landing_page_url'),
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 500),
                    'status' => AdReward::STATUS_CREDITED,
                ]);
            } catch (\Throwable $e) {
                throw $e;
            }

            // Credit Member Wallet Balance atomically
            $lockedMember->wallet = round((float) ($lockedMember->wallet ?? 0.00) + $rewardAmount, 2);
            $lockedMember->save();
            $newWalletBalance = (float) $lockedMember->wallet;

            return [
                'success' => true,
                'status_code' => 200,
                'message' => "Page followed successfully! \${$rewardAmountExact} USD reward credited to your Wallet.",
                'rewarded' => true,
                'reward_amount_usd' => $rewardAmount,
                'reward_amount_exact' => $rewardAmountExact,
                'remaining_budget' => $newRemaining,
                'spent_budget' => $newSpent,
                'campaign_status' => $lockedCampaign->status,
                'reward_id' => $reward->id,
                'is_following' => true,
                'member_wallet' => $newWalletBalance,
                'member_reward_balance' => $newWalletBalance,
                'reward_wallet_balance' => $newWalletBalance,
            ];
        });

        $statusCode = $result['status_code'] ?? 200;
        unset($result['status_code']);

        return response()->json($result, $statusCode);
    }

    /**
     * Get active ad campaigns for feed delivery.
     * Accessible to both verified and unverified members for viewing.
     * Only returns campaigns that are approved, active, and have running_budget >= minActiveReward.
     */
    public function feed(Request $request)
    {
        $member = auth('member')->user();
        $limit = min(50, max(5, (int) $request->input('limit', 20)));

        $maxReward = AdRewardRule::getMaximumActiveRewardAmount(AdRewardRule::TYPE_BUSINESS_AD) ?? 0.0500;
        $maxRewardFormatted = null;
        if ($maxReward !== null) {
            $formattedNumber = rtrim(rtrim(sprintf('%.4f', $maxReward), '0'), '.');
            if (strpos($formattedNumber, '.') !== false && strlen(substr($formattedNumber, strpos($formattedNumber, '.') + 1)) == 1) {
                $formattedNumber .= '0';
            }
            $maxRewardFormatted = '$' . $formattedNumber;
        }

        $campaigns = AdCampaign::query()
            ->eligibleForDelivery()
            ->whereHas('businessPage', function ($bp) {
                $bp->whereHas('posts');
            })
            ->with([
                'businessPage:id,page_name,page_username,slug,logo',
                'post:id,body,media_type,media_path,created_at',
            ])
            ->latest('created_at')
            ->take($limit)
            ->get()
            ->map(function (AdCampaign $c) use ($member, $maxReward, $maxRewardFormatted) {
                $isOwner = ($member && !empty($member->id)) ? $c->isOwner($member->id) : false;
                $alreadyRewarded = ($member && !empty($member->id))
                    ? AdReward::where('ad_campaign_id', $c->id)
                        ->where('member_id', $member->id)
                        ->where('status', AdReward::STATUS_CREDITED)
                        ->exists()
                    : false;

                return [
                    'id' => $c->id,
                    'campaign_id' => $c->campaign_id,
                    'campaign_name' => $c->campaign_name,
                    'member_id' => $c->member_id,
                    'is_owner' => $isOwner,
                    'reward_amount_usd' => $isOwner ? null : $maxReward,
                    'earn_up_to_usd' => $isOwner ? null : $maxReward,
                    'earn_up_to_formatted' => $isOwner ? null : $maxRewardFormatted,
                    'business_page' => $c->businessPage,
                    'post' => $c->post,
                    'start_at' => $c->start_at,
                    'status' => $c->status,
                    'already_rewarded' => $alreadyRewarded,
                ];
            });

        return response()->json([
            'success' => true,
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Get aggregate analytics for all campaigns of a Business Page.
     */
    public function analytics(Request $request, BusinessPage $businessPage)
    {
        $member = auth('member')->user();

        if (!$businessPage->isOwner($member->id) && !$businessPage->isTeamAdmin($member->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view analytics for this Business Page.',
            ], 403);
        }

        $pageCampaignIds = AdCampaign::where('business_page_id', $businessPage->id)->pluck('id');
        $totalImpressions = AdImpression::whereIn('ad_campaign_id', $pageCampaignIds)->count();
        $totalClicks = AdClick::whereIn('ad_campaign_id', $pageCampaignIds)->count();
        $totalRewardsPaid = round((float) AdReward::whereIn('ad_campaign_id', $pageCampaignIds)->where('status', AdReward::STATUS_CREDITED)->sum('reward_amount_usd'), 2);
        $totalVerifiedVisits = AdReward::whereIn('ad_campaign_id', $pageCampaignIds)->where('status', AdReward::STATUS_CREDITED)->count();
        $avgCtr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0.00;

        $metrics = [
            'total_campaigns' => $pageCampaignIds->count(),
            'active_campaigns' => AdCampaign::where('business_page_id', $businessPage->id)->where('status', AdCampaign::STATUS_ACTIVE)->count(),
            'pending_review' => AdCampaign::where('business_page_id', $businessPage->id)->where('status', AdCampaign::STATUS_PENDING_REVIEW)->count(),
            'approved_campaigns' => AdCampaign::where('business_page_id', $businessPage->id)->where('status', AdCampaign::STATUS_APPROVED)->count(),
            'paused_campaigns' => AdCampaign::where('business_page_id', $businessPage->id)->where('status', AdCampaign::STATUS_PAUSED)->count(),
            'completed_campaigns' => AdCampaign::where('business_page_id', $businessPage->id)->where('status', AdCampaign::STATUS_COMPLETED)->count(),
            'total_budget' => round((float) AdCampaign::where('business_page_id', $businessPage->id)->sum('budget'), 2),
            'total_platform_fees' => round((float) AdCampaign::where('business_page_id', $businessPage->id)->sum('fee_amount'), 2),
            'total_wallet_debits' => round((float) AdCampaign::where('business_page_id', $businessPage->id)->sum('wallet_debit'), 2),
            'total_rewards_paid' => $totalRewardsPaid,
            'total_spent' => round((float) AdCampaign::where('business_page_id', $businessPage->id)->sum('spent_amount'), 2),
            'total_remaining' => round((float) AdCampaign::where('business_page_id', $businessPage->id)->sum('remaining_amount'), 2),
            'total_verified_visits' => $totalVerifiedVisits,
            'total_reward_count' => $totalVerifiedVisits,
            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'average_ctr' => $avgCtr,
        ];

        return response()->json([
            'success' => true,
            'metrics' => $metrics,
            'business_page' => [
                'id' => $businessPage->id,
                'page_name' => $businessPage->page_name,
                'slug' => $businessPage->slug,
            ],
        ]);
    }

    /**
     * Get detailed analytics for a single campaign.
     */
    public function campaignAnalytics(Request $request, BusinessPage $businessPage, $campaignIdentifier)
    {
        $member = auth('member')->user();
        $campaign = $this->resolveCampaign($businessPage, $campaignIdentifier);

        if (!$businessPage->isOwner($member->id) && !$businessPage->isTeamAdmin($member->id) && (int) $campaign->member_id !== (int) $member->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view this campaign analytics.',
            ], 403);
        }

        $impressionsCount = AdImpression::where('ad_campaign_id', $campaign->id)->count();
        $clicksCount = AdClick::where('ad_campaign_id', $campaign->id)->count();
        $rewardsCount = AdReward::where('ad_campaign_id', $campaign->id)->where('status', AdReward::STATUS_CREDITED)->count();
        $rewardsPaid = round((float) ($campaign->spent_amount ?? 0.00), 2);
        $remainingBudget = round((float) ($campaign->remaining_amount ?? 0.00), 2);
        $ctr = $impressionsCount > 0 ? round(($clicksCount / $impressionsCount) * 100, 2) : 0.00;

        return response()->json([
            'success' => true,
            'campaign' => $campaign->load(['post', 'owner:id,name,user_id,email', 'approver:id,name,email']),
            'metrics' => [
                'budget' => (float) $campaign->budget,
                'fee_percent' => (float) ($campaign->fee_percent ?? 2.50),
                'fee_amount' => (float) ($campaign->fee_amount ?? 0.00),
                'wallet_debit' => (float) ($campaign->wallet_debit ?? 0.00),
                'spent_amount' => $rewardsPaid,
                'rewards_paid' => $rewardsPaid,
                'remaining_amount' => $remainingBudget,
                'remaining_campaign_budget' => $remainingBudget,
                'verified_visits' => $rewardsCount,
                'rewards_count' => $rewardsCount,
                'impressions_count' => $impressionsCount,
                'clicks_count' => $clicksCount,
                'ctr' => $ctr,
                'financial_reconciliation' => [
                    'initial_campaign_budget' => (float) $campaign->budget,
                    'rewards_paid' => $rewardsPaid,
                    'remaining_campaign_budget' => $remainingBudget,
                    'reconciles_exactly' => round((float) $campaign->budget, 2) === round($rewardsPaid + $remainingBudget, 2),
                ],
            ],
        ]);
    }

    /**
     * Get itemized user engagement records (clicks, landing-page visits, rewards) for a member's campaign.
     */
    public function engagements(Request $request, BusinessPage $businessPage, $campaignIdentifier)
    {
        $member = auth('member')->user();
        $campaign = $this->resolveCampaign($businessPage, $campaignIdentifier);

        if (!$businessPage->isOwner($member->id) && !$businessPage->isTeamAdmin($member->id) && (int) $campaign->member_id !== (int) $member->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view engagements for this campaign.',
            ], 403);
        }

        // Authoritative query: ONLY successfully credited rewards for this specific campaign
        $query = AdReward::query()
            ->where('ad_campaign_id', $campaign->id)
            ->where('status', AdReward::STATUS_CREDITED)
            ->with(['member:id,name,user_id,email,phone,profile_photo,mobile_verified_at,city,country']);

        // Prevent manipulation to expose unrewarded interactions
        if (in_array($request->input('reward_status'), ['not_rewarded', 'pending', 'failed', 'rejected']) ||
            in_array($request->input('action'), ['interested', 'clicked', 'clicks', 'visited_landing_page'])) {
            $query->whereRaw('0 = 1');
        }

        // 1. Keyword search (name, user_id, email, phone, or member_id)
        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($sq) use ($search) {
                $sq->whereHas('member', function ($mq) use ($search) {
                    $mq->where('name', 'like', "%{$search}%")
                        ->orWhere('user_id', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
                if (is_numeric($search)) {
                    $sq->orWhere('ad_rewards.member_id', (int) $search);
                }
            });
        }

        // 2. Mobile verification filter
        $verifiedOnly = $request->boolean('verified_only') || $request->input('verification') === 'verified';
        $verificationFilter = $request->input('verification', $verifiedOnly ? 'verified' : 'all');
        if ($verificationFilter === 'verified') {
            $query->whereHas('member', fn ($mq) => $mq->whereNotNull('mobile_verified_at'));
        } elseif ($verificationFilter === 'unverified') {
            $query->whereHas('member', fn ($mq) => $mq->whereNull('mobile_verified_at'));
        }

        // 3. Date filtering
        $datePreset = $request->input('date_preset', 'all');
        if ($datePreset === 'today') {
            $query->where('ad_rewards.created_at', '>=', now()->startOfDay());
        } elseif ($datePreset === 'yesterday') {
            $query->whereBetween('ad_rewards.created_at', [
                now()->subDay()->startOfDay(),
                now()->subDay()->endOfDay(),
            ]);
        } elseif ($datePreset === 'last_7_days') {
            $query->where('ad_rewards.created_at', '>=', now()->subDays(7)->startOfDay());
        } elseif ($datePreset === 'last_30_days') {
            $query->where('ad_rewards.created_at', '>=', now()->subDays(30)->startOfDay());
        } elseif ($datePreset === 'custom' || $request->filled('start_date') || $request->filled('end_date') || $request->filled('date_from') || $request->filled('date_to')) {
            $startDate = $request->input('start_date', $request->input('date_from'));
            $endDate = $request->input('end_date', $request->input('date_to'));
            if ($startDate && $endDate && strtotime($startDate) > strtotime($endDate)) {
                $temp = $startDate;
                $startDate = $endDate;
                $endDate = $temp;
            }
            if ($startDate) {
                $query->where('ad_rewards.created_at', '>=', date('Y-m-d 00:00:00', strtotime($startDate)));
            }
            if ($endDate) {
                $query->where('ad_rewards.created_at', '<=', date('Y-m-d 23:59:59', strtotime($endDate)));
            }
        }

        // 4. Member ID filter (if specified)
        if ($request->filled('member_id')) {
            $query->where('ad_rewards.member_id', $request->input('member_id'));
        }

        // 5. Sorting
        $sort = $request->input('sort', 'newest');
        if ($sort === 'oldest') {
            $query->orderBy('ad_rewards.created_at', 'asc')->orderBy('ad_rewards.id', 'asc');
        } elseif ($sort === 'name_asc') {
            $query->join('members', 'ad_rewards.member_id', '=', 'members.id')
                ->select('ad_rewards.*')
                ->orderBy('members.name', 'asc');
        } elseif ($sort === 'highest_reward' || $sort === 'reward_desc') {
            $query->orderByDesc('ad_rewards.reward_amount_usd')->orderByDesc('ad_rewards.created_at');
        } elseif ($sort === 'lowest_reward' || $sort === 'reward_asc') {
            $query->orderBy('ad_rewards.reward_amount_usd', 'asc')->orderBy('ad_rewards.created_at', 'asc');
        } else {
            $query->orderByDesc('ad_rewards.created_at')->orderByDesc('ad_rewards.id');
        }

        // Server-Side Pagination
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(50, max(1, (int) $request->input('per_page', 15)));

        $paginated = $query->paginate($perPage, ['ad_rewards.*'], 'page', $page);

        $data = collect($paginated->items())->map(function (AdReward $reward) {
            $member = $reward->member;
            $rewardAmount = (float) ($reward->reward_amount_usd ?? 0.00);

            return [
                'id' => 'reward_' . $reward->id,
                'activity_id' => $reward->id,
                'event_id' => $reward->qualifying_event_id,
                'type' => 'reward',
                'action' => 'Rewarded Visit',
                'action_label' => 'Rewarded Visit',
                'reward_amount_usd' => $rewardAmount,
                'reward_amount_exact' => number_format($rewardAmount, 4, '.', ''),
                'status' => $reward->status,
                'created_at' => $reward->created_at?->toIso8601String(),
                'timestamp' => $reward->created_at?->timestamp ?? 0,
                'user' => $member ? [
                    'id' => $member->id,
                    'name' => $member->name,
                    'username' => $member->user_id,
                    'email' => $member->email,
                    'phone' => $member->phone,
                    'profile_photo' => $member->profile_photo,
                    'avatar' => $member->avatar_url,
                    'avatar_url' => $member->avatar_url,
                    'is_verified' => $member->mobile_verified_at !== null,
                    'city' => $member->city,
                    'country' => $member->country,
                ] : [
                    'id' => null,
                    'name' => 'Verified Member',
                    'username' => null,
                    'email' => null,
                    'phone' => null,
                    'profile_photo' => null,
                    'avatar' => null,
                    'avatar_url' => null,
                    'is_verified' => true,
                    'city' => null,
                    'country' => null,
                ],
            ];
        });

        $engagementService = app(AdCampaignEngagementService::class);
        $summary = $engagementService->getSummary($campaign);

        return response()->json([
            'success' => true,
            'campaign_id' => $campaign->id,
            'campaign_name' => $campaign->campaign_name,
            'campaign' => $campaign->loadMissing(['post', 'businessPage', 'owner:id,name,user_id,email']),
            'engagements' => [
                'data' => $data->values(),
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
                'filtered_unique_members' => $paginated->total(),
            ],
            'summary' => $summary,
        ]);
    }

    /**
     * Get complete member engagement history and reward drill-down on an ad campaign.
     * Authorized for Business Page Owner, Team Admin, or Campaign Creator.
     */
    public function memberEngagementDetail(Request $request, BusinessPage $businessPage, $campaignIdentifier, $memberIdentifier)
    {
        $member = auth('member')->user();
        $campaign = $this->resolveCampaign($businessPage, $campaignIdentifier);

        if (!$businessPage->isOwner($member->id) && !$businessPage->isTeamAdmin($member->id) && (int) $campaign->member_id !== (int) $member->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view member engagement details for this campaign.',
            ], 403);
        }

        /** @var Member|null $targetMember */
        $targetMember = Member::query()
            ->where(function ($q) use ($memberIdentifier) {
                if (is_numeric($memberIdentifier)) {
                    $q->where('id', $memberIdentifier)->orWhere('user_id', $memberIdentifier);
                } else {
                    $q->where('user_id', $memberIdentifier);
                }
            })
            ->first();

        if (!$targetMember) {
            return response()->json([
                'success' => false,
                'message' => 'Engaged member not found.',
            ], 404);
        }

        $detail = app(AdCampaignEngagementService::class)->getMemberEngagementDetail($campaign, $targetMember);

        return response()->json(array_merge([
            'success' => true,
        ], $detail));
    }

    /**
     * Export campaign audience records or full activity log as CSV.
     * Authorized for Business Page Owner, Team Admin, or Campaign Creator.
     */
    public function exportEngagements(Request $request, BusinessPage $businessPage, $campaignIdentifier)
    {
        $member = auth('member')->user();
        $campaign = $this->resolveCampaign($businessPage, $campaignIdentifier);

        if (!$businessPage->isOwner($member->id) && !$businessPage->isTeamAdmin($member->id) && (int) $campaign->member_id !== (int) $member->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to export audience for this campaign.',
            ], 403);
        }

        $filters = [
            'action' => $request->input('action', 'all'),
            'reward_status' => $request->input('reward_status', 'all'),
            'verification' => $request->input('verification', $request->boolean('verified_only') ? 'verified' : 'all'),
            'date_preset' => $request->input('date_preset', 'all'),
            'start_date' => $request->input('start_date', $request->input('date_from')),
            'end_date' => $request->input('end_date', $request->input('date_to')),
            'sort' => $request->input('sort', 'newest'),
            'q' => trim((string) $request->input('q')),
        ];

        $selectedMemberIds = [];
        if ($request->filled('selected_ids')) {
            $raw = $request->input('selected_ids');
            $selectedMemberIds = is_array($raw) ? $raw : explode(',', (string) $raw);
        }

        $mode = $request->input('mode', 'audience'); // 'audience' (deduplicated) | 'activities' (raw events)

        return app(AdCampaignEngagementService::class)->exportAudienceCsv($campaign, $filters, $selectedMemberIds, $mode);
    }

    /**
     * Validate and prepare audience reach-out operations for selected campaign members.
     * Authorized for Business Page Owner, Team Admin, or Campaign Creator.
     */
    public function contactAudience(Request $request, BusinessPage $businessPage, $campaignIdentifier)
    {
        $member = auth('member')->user();
        $campaign = $this->resolveCampaign($businessPage, $campaignIdentifier);

        if (!$businessPage->isOwner($member->id) && !$businessPage->isTeamAdmin($member->id) && (int) $campaign->member_id !== (int) $member->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to perform audience operations on this campaign.',
            ], 403);
        }

        $request->validate([
            'member_ids' => 'required|array|min:1',
            'member_ids.*' => 'numeric',
            'method' => 'nullable|string|in:whatsapp,email,copy',
            'message' => 'nullable|string|max:1000',
        ]);

        $memberIds = $request->input('member_ids', []);
        $method = $request->input('method', 'whatsapp');
        $message = $request->input('message');

        $result = app(AdCampaignEngagementService::class)->validateAndPrepareAudienceContact(
            $campaign,
            $memberIds,
            $method,
            $message
        );

        return response()->json(array_merge([
            'success' => true,
            'campaign_id' => $campaign->id,
            'campaign_name' => $campaign->campaign_name,
        ], $result));
    }
}
