<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Models\AdReward;
use App\Models\AdRewardRule;
use App\Models\BusinessPage;
use App\Models\Community;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\Member;
use App\Models\Setting;
use App\Services\RewardRuleResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MobileBusinessAndCampaignController extends Controller
{
    /**
     * Get Business Pages for mobile.
     */
    public function businessPages(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $myPages = BusinessPage::where('member_id', $member->id)->latest()->get()->map(function (BusinessPage $p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'category' => $p->category?->name ?? 'General',
                'avatar_url' => $p->profile_photo_url ?? asset('images/default_page.png'),
                'followers_count' => $p->followers()->count(),
                'rating' => round($p->reviews()->avg('rating') ?? 0, 1),
                'is_verified' => (bool) $p->is_verified,
            ];
        });

        $explorePages = BusinessPage::where('member_id', '!=', $member->id)->latest()->take(20)->get()->map(function (BusinessPage $p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'category' => $p->category?->name ?? 'General',
                'avatar_url' => $p->profile_photo_url ?? asset('images/default_page.png'),
                'followers_count' => $p->followers()->count(),
                'rating' => round($p->reviews()->avg('rating') ?? 0, 1),
                'is_verified' => (bool) $p->is_verified,
            ];
        });

        return response()->json([
            'success' => true,
            'my_pages' => $myPages,
            'explore_pages' => $explorePages,
        ]);
    }

    /**
     * Create a new Business Page from mobile.
     */
    public function storeBusinessPage(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
        ]);

        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug;
        $counter = 1;
        while (BusinessPage::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $page = BusinessPage::create([
            'member_id' => $member->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'bio' => $validated['bio'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'website' => $validated['website'] ?? null,
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Business Page created successfully.',
            'page' => [
                'id' => $page->id,
                'name' => $page->name,
                'slug' => $page->slug,
            ],
        ], 201);
    }

    /**
     * Get advertiser's ad campaigns for a business page.
     */
    public function pageCampaigns(Request $request, string $slug): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();
        $page = BusinessPage::where('slug', $slug)->firstOrFail();

        $campaigns = AdCampaign::where('business_page_id', $page->id)->latest()->get()->map(function (AdCampaign $c) {
            return [
                'id' => $c->id,
                'campaign_id' => $c->campaign_id,
                'campaign_name' => $c->campaign_name,
                'budget' => (float) $c->budget,
                'spent_amount' => (float) $c->spent_amount,
                'remaining_amount' => (float) $c->remaining_amount,
                'status' => $c->status,
                'approval_status' => $c->approval_status,
                'created_at' => $c->created_at?->format('M d, Y'),
            ];
        });

        return response()->json([
            'success' => true,
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Create Ad Campaign funded by Member's ad_balance.
     */
    public function storeAdCampaign(Request $request, string $slug): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();
        $page = BusinessPage::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'campaign_name' => ['required', 'string', 'max:255'],
            'budget' => ['required', 'numeric', 'min:5'],
        ]);

        $budget = (float) $validated['budget'];
        $feePercent = (float) Setting::get('campaign_platform_fee_percent', 2.5);
        $feeAmount = round($budget * ($feePercent / 100), 2);
        $totalDebit = round($budget + $feeAmount, 2);

        if (((float) ($member->ad_balance ?? 0.00)) < $totalDebit) {
            return response()->json([
                'message' => "Insufficient ad funds. Required: \${$totalDebit} (Budget \${$budget} + \${$feeAmount} platform fee). Please deposit funds first.",
            ], 422);
        }

        $campaign = DB::transaction(function () use ($member, $page, $validated, $budget, $feePercent, $feeAmount, $totalDebit) {
            // Deduct funds from member ad balance
            $member->ad_balance = round((float) $member->ad_balance - $totalDebit, 2);
            $member->save();

            return AdCampaign::create([
                'business_page_id' => $page->id,
                'member_id' => $member->id,
                'campaign_name' => $validated['campaign_name'],
                'budget' => $budget,
                'currency' => 'USDT',
                'fee_percent' => $feePercent,
                'fee_amount' => $feeAmount,
                'wallet_debit' => $totalDebit,
                'spent_amount' => 0.00,
                'remaining_amount' => $budget,
                'status' => AdCampaign::STATUS_PENDING_REVIEW,
                'approval_status' => AdCampaign::APPROVAL_PENDING,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Campaign submitted successfully for admin review.',
            'campaign_id' => $campaign->campaign_id,
            'new_ad_balance' => (float) $member->fresh()->ad_balance,
        ], 201);
    }

    /**
     * Sponsored Feed: Get active ads and events with reward incentive for viewer.
     */
    public function sponsoredFeed(Request $request, RewardRuleResolver $ruleResolver): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();
        $tier = $ruleResolver->resolveForMember($member);
        $potentialReward = $tier['reward_amount_exact'] ?? '0.0250';

        $campaigns = AdCampaign::query()
            ->where('status', AdCampaign::STATUS_ACTIVE)
            ->where('approval_status', AdCampaign::APPROVAL_APPROVED)
            ->where('remaining_amount', '>=', 0.025)
            ->where('member_id', '!=', $member->id)
            ->with(['businessPage:id,name,slug,profile_photo', 'post'])
            ->latest()
            ->take(15)
            ->get()
            ->map(function (AdCampaign $c) use ($member, $potentialReward) {
                $alreadyClaimed = AdReward::where('ad_campaign_id', $c->id)->where('member_id', $member->id)->exists();

                return [
                    'id' => $c->id,
                    'campaign_id' => $c->campaign_id,
                    'campaign_name' => $c->campaign_name,
                    'business_name' => $c->businessPage?->name ?? 'Sponsored',
                    'business_avatar' => $c->businessPage?->avatar_url,
                    'post_body' => $c->post?->body ?? 'Sponsored Business Announcement',
                    'media_url' => $c->post?->media_url,
                    'potential_reward_usd' => $potentialReward,
                    'is_claimed' => $alreadyClaimed,
                ];
            });

        return response()->json([
            'success' => true,
            'sponsored' => $campaigns,
            'user_reward_tier' => $potentialReward,
        ]);
    }

    /**
     * Claim reward for visiting / engaging with sponsored ad (qualify visit).
     */
    public function claimReward(Request $request, int $campaignId, RewardRuleResolver $ruleResolver): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        if (!method_exists($member, 'isMobileVerified') || !$member->isMobileVerified()) {
            return response()->json(['message' => 'Please verify your phone number first to earn ad rewards.'], 403);
        }

        $campaign = AdCampaign::findOrFail($campaignId);

        if ($campaign->isOwner($member->id)) {
            return response()->json(['message' => 'You cannot earn rewards from your own campaign.'], 403);
        }

        if (AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $member->id)->exists()) {
            return response()->json(['message' => 'You have already claimed your reward for this campaign.'], 422);
        }

        $resolution = $ruleResolver->resolveForMember($member);
        if (!$resolution['success']) {
            return response()->json(['message' => $resolution['message'] ?? 'Reward rule resolution failed.'], 422);
        }

        $rewardAmount = (float) $resolution['reward_amount_usd'];

        return DB::transaction(function () use ($campaign, $member, $rewardAmount, $resolution) {
            $lockedCampaign = AdCampaign::where('id', $campaign->id)->lockForUpdate()->first();
            $currentRemaining = (float) ($lockedCampaign->remaining_amount ?? 0);

            if ($currentRemaining < $rewardAmount) {
                return response()->json(['message' => 'Campaign budget is insufficient or exhausted.'], 422);
            }

            $lockedCampaign->remaining_amount = max(0, round($currentRemaining - $rewardAmount, 4));
            $lockedCampaign->spent_amount = round((float) $lockedCampaign->spent_amount + $rewardAmount, 4);

            if ($lockedCampaign->remaining_amount < 0.025) {
                $lockedCampaign->status = AdCampaign::STATUS_STOPPED;
            }
            $lockedCampaign->save();

            AdReward::create([
                'ad_campaign_id' => $lockedCampaign->id,
                'member_id' => $member->id,
                'ad_reward_rule_id' => $resolution['matched_rule_id'] ?? null,
                'direct_verified_referral_count' => $resolution['direct_verified_referral_count'] ?? 0,
                'reward_amount_usd' => $rewardAmount,
                'status' => AdReward::STATUS_CREDITED,
            ]);

            $member->creditRewardBalance($rewardAmount);

            return response()->json([
                'success' => true,
                'message' => "Congratulations! You earned \${$resolution['reward_amount_exact']} USD reward.",
                'reward_credited' => $rewardAmount,
                'new_reward_balance' => (float) $member->fresh()->reward_balance,
            ]);
        });
    }

    /**
     * Events listing for mobile.
     */
    public function events(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $events = Event::with(['organizer:id,name,user_id,profile_photo'])->latest()->paginate(15)->through(function (Event $e) use ($member) {
            $myResponse = EventResponse::where('event_id', $e->id)->where('member_id', $member->id)->value('response');

            return [
                'id' => $e->id,
                'title' => $e->title,
                'description' => $e->description,
                'location' => $e->location,
                'start_time' => $e->start_time?->format('M d, Y H:i'),
                'banner_url' => $e->banner_url,
                'organizer_name' => $e->organizer?->name ?? 'Organizer',
                'my_response' => $myResponse,
                'attendees_count' => $e->responses()->where('response', 'going')->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'events' => $events,
        ]);
    }

    /**
     * Respond to an event (Going / Interested).
     */
    public function respondEvent(Request $request, int $eventId): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();
        $response = $request->input('response', 'going'); // going or interested

        $event = Event::findOrFail($eventId);

        EventResponse::updateOrCreate([
            'event_id' => $event->id,
            'member_id' => $member->id,
        ], [
            'response' => $response,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Event response updated.',
            'response' => $response,
        ]);
    }

    /**
     * Create a new event from mobile.
     */
    public function storeEvent(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'location' => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'date'],
        ]);

        $event = Event::create([
            'organizer_id' => $member->id,
            'title' => trim($validated['title']),
            'description' => trim($validated['description']),
            'location' => trim($validated['location']),
            'start_time' => $validated['start_time'],
            'status' => 'published',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Event created successfully.',
            'event' => $event,
        ], 201);
    }

    /**
     * Communities listing for mobile.
     */
    public function communities(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();
        $tab = $request->query('tab', 'all');

        $query = Community::query()->with('owner:id,name,user_id,profile_photo');

        if ($tab === 'joined') {
            $query->whereHas('members', function ($mq) use ($member) {
                $mq->where('member_id', $member->id);
            });
        } elseif ($tab === 'discover') {
            $query->whereDoesntHave('members', function ($mq) use ($member) {
                $mq->where('member_id', $member->id);
            });
        }

        $communities = $query->latest()->paginate(15)->through(function (Community $c) use ($member) {
            $isMember = $c->members()->where('member_id', $member->id)->exists();

            return [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'description' => $c->description,
                'category' => $c->category ?? 'General',
                'visibility' => $c->visibility ?? 'public',
                'members_count' => $c->members_count ?? $c->members()->count(),
                'avatar_url' => $c->avatar ?? null,
                'banner_url' => $c->cover_image ?? null,
                'is_member' => $isMember,
                'is_owner' => $c->owner_id === $member->id,
            ];
        });

        return response()->json([
            'success' => true,
            'communities' => $communities,
        ]);
    }

    /**
     * Community detail for mobile.
     */
    public function communityDetail(Request $request, string $slug): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $community = Community::where('slug', $slug)->with('owner:id,name,user_id,profile_photo')->firstOrFail();
        $isMember = $community->members()->where('member_id', $member->id)->exists();

        return response()->json([
            'success' => true,
            'community' => [
                'id' => $community->id,
                'name' => $community->name,
                'slug' => $community->slug,
                'description' => $community->description,
                'category' => $community->category ?? 'General',
                'visibility' => $community->visibility ?? 'public',
                'members_count' => $community->members()->count(),
                'avatar_url' => $community->avatar ?? null,
                'banner_url' => $community->cover_image ?? null,
                'is_member' => $isMember,
                'is_owner' => $community->owner_id === $member->id,
                'owner' => $community->owner,
                'created_at' => $community->created_at?->format('M d, Y'),
            ],
        ]);
    }

    /**
     * Create a new community from mobile.
     */
    public function storeCommunity(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'max:100'],
            'visibility' => ['nullable', 'string', 'in:public,private,invite_only'],
        ]);

        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug;
        $counter = 1;
        while (Community::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $community = Community::create([
            'owner_id' => $member->id,
            'community_id' => 'COMM' . strtoupper(Str::random(8)),
            'name' => trim($validated['name']),
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'] ?? 'General',
            'visibility' => $validated['visibility'] ?? 'public',
            'status' => 'active',
            'members_count' => 1,
        ]);

        // Add owner as first member
        DB::table('community_members')->insert([
            'community_id' => $community->id,
            'member_id' => $member->id,
            'role' => 'admin',
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Community created successfully.',
            'community' => $community,
        ], 201);
    }

    /**
     * Join / Leave community toggle.
     */
    public function toggleCommunityJoin(Request $request, string $slug): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $community = Community::where('slug', $slug)->firstOrFail();

        $existing = DB::table('community_members')
            ->where('community_id', $community->id)
            ->where('member_id', $member->id)
            ->first();

        if ($existing) {
            DB::table('community_members')
                ->where('community_id', $community->id)
                ->where('member_id', $member->id)
                ->delete();

            $community->decrement('members_count');

            return response()->json([
                'success' => true,
                'joined' => false,
                'message' => 'Left community.',
            ]);
        }

        DB::table('community_members')->insert([
            'community_id' => $community->id,
            'member_id' => $member->id,
            'role' => 'member',
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $community->increment('members_count');

        return response()->json([
            'success' => true,
            'joined' => true,
            'message' => 'Joined community successfully!',
        ]);
    }
}
