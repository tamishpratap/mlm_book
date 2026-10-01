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
        $rawTab = $request->query('tab', 'all');
        $tab = in_array($rawTab, ['all', 'my'], true) ? $rawTab : 'all';

        // My Business Pages
        $myPagesQuery = BusinessPage::query()
            ->with('owner')
            ->where('member_id', $member->id);

        $myCount = $myPagesQuery->count();
        $myPages = $myPagesQuery->latest()->get()->map(function (BusinessPage $p) use ($member) {
            return [
                'id' => $p->id,
                'page_name' => $p->page_name,
                'page_username' => $p->page_username,
                'slug' => $p->slug,
                'category' => $p->category ?? 'General',
                'description' => $p->description,
                'website' => $p->website,
                'email' => $p->email,
                'phone' => $p->phone,
                'city' => $p->city,
                'state' => $p->state,
                'country' => $p->country,
                'avatar_url' => $p->logo,
                'banner_url' => $p->cover_photo,
                'visibility' => $p->visibility ?? 'public',
                'is_verified' => (bool) $p->is_verified,
                'is_owner' => true,
                'created_at' => $p->created_at?->toIso8601String(),
            ];
        });

        // Main Query according to tab & filters
        $query = BusinessPage::query()->with('owner');

        if ($tab === 'my') {
            $query->where('member_id', $member->id);
        } else {
            $query->where(function ($q) use ($member) {
                $q->where('visibility', 'public')
                  ->orWhere('member_id', $member->id);
            });
        }

        if ($request->filled('search')) {
            $search = '%'.$request->query('search').'%';
            $query->where(function ($q) use ($search) {
                $q->where('page_name', 'like', $search)
                    ->orWhere('page_username', 'like', $search)
                    ->orWhere('description', 'like', $search)
                    ->orWhere('category', 'like', $search)
                    ->orWhere('city', 'like', $search)
                    ->orWhere('country', 'like', $search);
            });
        }

        if ($request->filled('category') && $request->query('category') !== 'All') {
            $query->where('category', $request->query('category'));
        }

        $pages = $query->latest('created_at')->paginate(15)->through(function (BusinessPage $p) use ($member) {
            return [
                'id' => $p->id,
                'page_name' => $p->page_name,
                'page_username' => $p->page_username,
                'slug' => $p->slug,
                'category' => $p->category ?? 'General',
                'description' => $p->description,
                'website' => $p->website,
                'email' => $p->email,
                'phone' => $p->phone,
                'city' => $p->city,
                'state' => $p->state,
                'country' => $p->country,
                'avatar_url' => $p->logo,
                'banner_url' => $p->cover_photo,
                'visibility' => $p->visibility ?? 'public',
                'is_verified' => (bool) $p->is_verified,
                'is_owner' => $p->member_id === $member->id,
                'created_at' => $p->created_at?->toIso8601String(),
            ];
        });

        $categories = BusinessPage::categories();
        if (empty($categories)) {
            $categories = [
                'Technology & IT',
                'Financial Services',
                'E-Commerce & Retail',
                'Health & Wellness',
                'Real Estate',
                'Education & Training',
                'Marketing & Advertising',
                'Direct Selling & MLM',
                'Entertainment',
                'Food & Beverages',
                'Travel & Tourism',
                'Consulting',
            ];
        }

        // Fetch active countries from countries table
        $countries = DB::table('countries')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'iso2', 'phone_code', 'flag_emoji']);

        return response()->json([
            'success' => true,
            'pages' => $pages,
            'my_pages' => $myPages,
            'my_count' => $myCount,
            'categories' => $categories,
            'countries' => $countries,
            'visibilities' => BusinessPage::VISIBILITIES,
        ]);
    }

    /**
     * Create a new Business Page from mobile.
     */
    public function storeBusinessPage(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $pageName = trim((string) ($request->input('page_name') ?? $request->input('name') ?? ''));
        if (empty($pageName)) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a business page name.',
            ], 422);
        }

        $baseSlug = Str::slug($pageName);
        $slug = $baseSlug;
        $counter = 1;
        while (BusinessPage::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $baseUsername = Str::slug($pageName, '');
        $pageUsername = $baseUsername;
        $uCounter = 1;
        while (BusinessPage::where('page_username', $pageUsername)->exists()) {
            $pageUsername = $baseUsername . $uCounter++;
        }

        $coverPath = null;
        if ($request->hasFile('cover_photo')) {
            $coverPath = $this->storeFile($request->file('cover_photo'), 'uploads/business_pages/covers');
        } elseif ($request->hasFile('cover')) {
            $coverPath = $this->storeFile($request->file('cover'), 'uploads/business_pages/covers');
        }

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $this->storeFile($request->file('logo'), 'uploads/business_pages/logos');
        } elseif ($request->hasFile('avatar')) {
            $logoPath = $this->storeFile($request->file('avatar'), 'uploads/business_pages/logos');
        }

        $code = trim((string) $request->input('phone_country_code', ''));
        $digits = trim((string) ($request->input('phone_number') ?? $request->input('phone') ?? ''));
        $phoneCombined = filled($digits) ? (filled($code) ? ($code . ' ' . $digits) : $digits) : null;

        $page = BusinessPage::create([
            'page_id' => 'biz_' . Str::random(10),
            'member_id' => $member->id,
            'page_name' => $pageName,
            'page_username' => $request->filled('page_username') ? trim((string) $request->input('page_username')) : $pageUsername,
            'slug' => $slug,
            'category' => $request->input('category', 'Technology & IT'),
            'description' => $request->input('description') ?? $request->input('bio'),
            'website' => $request->input('website'),
            'email' => $request->input('email'),
            'phone' => $phoneCombined,
            'address' => $request->input('address') ?? $request->input('street_address'),
            'city' => $request->input('city'),
            'state' => $request->input('state'),
            'country' => $request->input('country', 'India'),
            'visibility' => $request->input('visibility', 'public'),
            'cover_photo' => $coverPath,
            'logo' => $logoPath,
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Business Page created successfully.',
            'page' => [
                'id' => $page->id,
                'page_name' => $page->page_name,
                'page_username' => $page->page_username,
                'slug' => $page->slug,
                'category' => $page->category,
                'description' => $page->description,
                'website' => $page->website,
                'email' => $page->email,
                'phone' => $page->phone,
                'city' => $page->city,
                'state' => $page->state,
                'country' => $page->country,
                'avatar_url' => $page->logo,
                'banner_url' => $page->cover_photo,
                'visibility' => $page->visibility,
                'is_verified' => (bool) $page->is_verified,
                'is_owner' => true,
                'created_at' => $page->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Show detail of a business page for mobile.
     */
    public function businessPageDetail(Request $request, string $slug): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();
        $page = BusinessPage::where('slug', $slug)->with('owner:id,name,user_id,profile_photo')->firstOrFail();

        return response()->json([
            'success' => true,
            'page' => [
                'id' => $page->id,
                'page_name' => $page->page_name,
                'page_username' => $page->page_username,
                'slug' => $page->slug,
                'category' => $page->category ?? 'Technology & IT',
                'description' => $page->description,
                'website' => $page->website,
                'email' => $page->email,
                'phone' => $page->phone,
                'city' => $page->city,
                'state' => $page->state,
                'country' => $page->country,
                'avatar_url' => $page->logo,
                'banner_url' => $page->cover_photo,
                'visibility' => $page->visibility ?? 'public',
                'is_verified' => (bool) $page->is_verified,
                'is_owner' => $page->member_id === $member->id,
                'created_at' => $page->created_at?->toIso8601String(),
                'owner' => [
                    'id' => $page->owner?->id,
                    'name' => $page->owner?->name,
                    'user_id' => $page->owner?->user_id,
                    'profile_photo' => $page->owner?->profile_photo,
                ],
            ],
        ]);
    }

    /**
     * Delete business page.
     */
    public function destroyBusinessPage(Request $request, string $slug): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();
        $page = BusinessPage::where('slug', $slug)->firstOrFail();

        if ($page->member_id !== $member->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this business page.',
            ], 403);
        }

        $page->delete();

        return response()->json([
            'success' => true,
            'message' => 'Business page deleted successfully.',
        ]);
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
        $feePercent = (float) Setting::get('campaign_platform_fee_percent', 0.0);
        $feeAmount = round($budget * ($feePercent / 100), 2);
        $totalDebit = round($budget + $feeAmount, 2);

        $availableFunds = (float) ($member->p2p_wallet ?? 0.00);
        if ($availableFunds < $totalDebit) {
            $shortfallMsg = $feeAmount > 0
                ? "Insufficient ad funds. Required: \${$totalDebit} (Budget \${$budget} + \${$feeAmount} platform fee). Available: \${$availableFunds} USD. Please deposit funds first."
                : "Insufficient ad funds. Required: \${$totalDebit} (Budget \${$budget}). Available: \${$availableFunds} USD. Please deposit funds first.";
            return response()->json([
                'message' => $shortfallMsg,
            ], 422);
        }

        $campaign = DB::transaction(function () use ($member, $page, $validated, $budget, $feePercent, $feeAmount, $totalDebit, $availableFunds) {
            // Deduct funds from member Fund Wallet (p2p_wallet)
            $member->p2p_wallet = round($availableFunds - $totalDebit, 4);
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
            'new_ad_balance' => (float) $member->fresh()->p2p_wallet,
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
                'new_wallet_balance' => (float) $member->fresh()->wallet,
                'new_reward_balance' => (float) $member->fresh()->wallet,
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
        $rawTab = $request->query('tab', 'all');
        $tab = in_array($rawTab, ['all', 'joined', 'my', 'discover'], true) ? $rawTab : 'all';

        // Joined count
        $joinedCount = Community::query()
            ->whereHas('members', function ($mq) use ($member) {
                $mq->where('member_id', $member->id)->where('status', 'accepted');
            })
            ->count();

        // My count
        $myCount = Community::query()
            ->where('owner_id', $member->id)
            ->count();

        $query = Community::query()->with('owner:id,name,user_id,profile_photo');

        if ($tab === 'joined') {
            $query->whereHas('members', function ($mq) use ($member) {
                $mq->where('member_id', $member->id)->where('status', 'accepted');
            });
        } elseif ($tab === 'my') {
            $query->where('owner_id', $member->id);
        } elseif ($tab === 'discover') {
            $query->where('visibility', '!=', 'secret')
                ->where('owner_id', '!=', $member->id)
                ->whereDoesntHave('members', function ($mq) use ($member) {
                    $mq->where('member_id', $member->id)->where('status', 'accepted');
                });
        } else {
            $query->where('visibility', '!=', 'secret');
        }

        if ($request->filled('search')) {
            $search = '%'.$request->query('search').'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('description', 'like', $search)
                    ->orWhere('category', 'like', $search);
            });
        }

        if ($request->filled('category') && $request->query('category') !== 'All') {
            $query->where('category', $request->query('category'));
        }

        $communities = $query->latest('created_at')->paginate(15)->through(function (Community $c) use ($member) {
            $isMember = $c->members()->where('member_id', $member->id)->where('status', 'accepted')->exists();
            $isPending = $c->members()->where('member_id', $member->id)->where('status', 'pending')->exists();

            return [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'description' => $c->description,
                'category' => $c->category ?? 'Technology',
                'visibility' => $c->visibility ?? 'public',
                'members_count' => $c->members_count ?? $c->members()->where('status', 'accepted')->count(),
                'avatar_url' => $c->logo ?? $c->avatar ?? null,
                'banner_url' => $c->cover_photo ?? $c->cover_image ?? null,
                'owner_name' => $c->owner?->name ?? 'Community Member',
                'owner' => [
                    'id' => $c->owner?->id,
                    'name' => $c->owner?->name,
                    'profile_photo' => $c->owner?->profile_photo,
                ],
                'is_member' => $isMember,
                'is_pending' => $isPending,
                'is_owner' => $c->owner_id === $member->id,
            ];
        });

        $categories = defined(Community::class.'::CATEGORIES') ? Community::CATEGORIES : [
            'Technology',
            'Business',
            'Crypto',
            'Marketing',
            'Gaming',
            'Education',
            'Lifestyle',
            'Health & Wellness',
            'E-Commerce',
            'MLM & Direct Sales',
        ];

        return response()->json([
            'success' => true,
            'communities' => $communities,
            'joined_count' => $joinedCount,
            'my_count' => $myCount,
            'categories' => $categories,
            'visibilities' => Community::VISIBILITIES,
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

    private function storeFile($file, string $directory): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $fileName = Str::uuid() . '.' . $extension;
        $type = str_contains($directory, 'logo') || str_contains($directory, 'avatar') ? 'logo' : 'cover';

        $storedPath = app(\App\Http\Controllers\ImageCompressionController::class)->compressAndStore(
            $file,
            $directory,
            $type,
            $fileName,
            'public_uploads'
        );

        return $storedPath ?: ($directory . '/' . $fileName);
    }
}
