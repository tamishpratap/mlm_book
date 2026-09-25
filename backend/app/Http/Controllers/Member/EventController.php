<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureMemberMobileVerified;
use App\Models\AdCampaign;
use App\Models\AdCampaignActivity;
use App\Models\AdReward;
use App\Models\AdRewardRule;
use App\Models\BusinessPageCategory;
use App\Models\Event;
use App\Models\EventInvitation;
use App\Models\EventResponse;
use App\Models\Member;
use App\Models\Post;
use App\Models\Setting;
use App\Notifications\SystemNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $member = auth('member')->user();
        $rawTab = strtolower((string) $request->query('tab', 'all'));
        $tab = in_array($rawTab, ['my', 'my_events', 'my-events']) ? 'my' : 'all';

        // 1. My Responded Event IDs (going or interested)
        $myRespondedIds = $member
            ? EventResponse::query()
                ->where('member_id', $member->id)
                ->whereIn('response', ['going', 'interested'])
                ->pluck('event_id')
            : collect();

        // Total count of my events & RSVPs for tab badge
        $myEventsCount = $member
            ? Event::query()
                ->where(function ($q) use ($member, $myRespondedIds) {
                    $q->where('organizer_id', $member->id)->orWhereIn('id', $myRespondedIds);
                })
                ->count()
            : 0;

        // Dynamic count of creator-owned fundable event campaigns for Add Fund tab badge
        $fundableEventsCount = $member
            ? Event::query()
                ->where('organizer_id', $member->id)
                ->whereHas('campaign', function ($cq) use ($member) {
                    $cq->where('campaign_type', AdCampaign::TYPE_EVENT)
                       ->where('member_id', $member->id);
                })
                ->count()
            : 0;

        $categories = BusinessPageCategory::getActiveCategoryNames();

        $rawTab = $request->query('tab', 'all');
        $tab = in_array($rawTab, ['add-fund', 'add_fund', 'funding', 'fund'], true)
            ? 'add-fund'
            : ($rawTab === 'my' ? 'my' : 'all');

        // Handle Add Fund Tab: Creator Event Campaign Funding Interface
        if ($tab === 'add-fund') {
            if (!$member) {
                if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthenticated. Please log in to view event campaign funding.',
                    ], 401);
                }
                return redirect()->route('member.login');
            }

            $minActiveReward = (float) (AdRewardRule::getMinimumActiveRewardAmount(AdRewardRule::TYPE_EVENT) ?? 0.05);
            $lowBudgetThreshold = (float) Setting::get('ad_campaign_low_budget_threshold', 1.00);
            $feePercent = (float) Setting::get('campaign_platform_fee_percent', 2.50);

            $fundableQuery = Event::query()
                ->where('organizer_id', $member->id)
                ->whereHas('campaign', function ($cq) use ($member) {
                    $cq->where('campaign_type', AdCampaign::TYPE_EVENT)
                       ->where('member_id', $member->id);
                })
                ->with(['campaign', 'organizer'])
                ->withCount(['responses as guests_count']);

            if ($request->filled('search')) {
                $search = '%'.$request->query('search').'%';
                $fundableQuery->where(function ($q) use ($search) {
                    $q->where('title', 'like', $search)
                        ->orWhere('description', 'like', $search)
                        ->orWhere('location_city', 'like', $search)
                        ->orWhere('category', 'like', $search);
                });
            }

            $fundableEvents = $fundableQuery->latest('created_at')->paginate(12)->withQueryString();

            $fundableEvents->getCollection()->transform(function ($ev) use ($minActiveReward, $lowBudgetThreshold) {
                $camp = $ev->campaign;
                $initialBudget = (float) ($camp?->budget ?? 0.00);
                $additionalFunding = (float) ($camp?->additional_funding ?? 0.00);
                $totalFunded = round($initialBudget + $additionalFunding, 4);
                $spent = round((float) ($camp?->spent_amount ?? 0.00), 4);
                $remaining = round((float) ($camp?->remaining_amount ?? 0.00), 4);

                $isExhausted = ($remaining < $minActiveReward) ||
                    in_array($camp?->status, [AdCampaign::STATUS_STOPPED, AdCampaign::STATUS_BUDGET_EXHAUSTED], true);
                $isLowBudget = (!$isExhausted && $remaining <= $lowBudgetThreshold);
                $canReactivate = ($camp?->status === AdCampaign::STATUS_STOPPED && $remaining >= $minActiveReward);

                $ev->campaign_details = $camp ? [
                    'id' => $camp->id,
                    'campaign_id' => $camp->campaign_id,
                    'campaign_name' => $camp->campaign_name,
                    'budget' => $initialBudget,
                    'additional_funding' => $additionalFunding,
                    'total_funded' => $totalFunded,
                    'spent_amount' => $spent,
                    'remaining_amount' => $remaining,
                    'currency' => $camp->currency ?? 'USD',
                    'status' => $camp->status,
                    'approval_status' => $camp->approval_status,
                    'display_status' => $camp->display_status,
                    'is_low_budget' => $isLowBudget,
                    'is_exhausted' => $isExhausted,
                    'can_reactivate' => $canReactivate,
                    'minimum_event_reward' => $minActiveReward,
                ] : null;

                return $ev;
            });

            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'tab' => 'add-fund',
                    'my_events_count' => $myEventsCount,
                    'fundable_events_count' => $fundableEventsCount,
                    'available_ad_funds' => round((float) ($member->p2p_wallet ?? 0.00), 2),
                    'platform_fee_percent' => $feePercent,
                    'events' => $fundableEvents,
                    'categories' => $categories,
                ]);
            }

            return view('member.events.index', compact('fundableEvents', 'categories', 'tab', 'myEventsCount', 'fundableEventsCount'));
        }

        // 2. Build Query for the Active Tab (all or my)
        if ($tab === 'my') {
            $query = Event::query()
                ->with(['organizer', 'campaign'])
                ->withCount(['responses as guests_count'])
                ->where(function ($q) use ($member, $myRespondedIds) {
                    $q->where('organizer_id', $member->id)->orWhereIn('id', $myRespondedIds);
                });
        } else {
            $query = Event::query()
                ->with(['organizer', 'campaign'])
                ->withCount(['responses as guests_count'])
                ->where('status', 'published')
                ->where('privacy', 'public')
                ->notPassed();
        }

        // Apply Search
        if ($request->filled('search')) {
            $search = '%'.$request->query('search').'%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                    ->orWhere('description', 'like', $search)
                    ->orWhere('location_city', 'like', $search)
                    ->orWhere('category', 'like', $search);
            });
        }

        // Apply Category Filter
        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        // Apply Type Filter (online / offline)
        if ($request->filled('type')) {
            $query->where('event_type', $request->query('type'));
        }

        // Apply Timeframe Filter
        if ($request->filled('timeframe')) {
            match ($request->query('timeframe')) {
                'today' => $query->whereDate('start_date', now()->toDateString()),
                'tomorrow' => $query->whereDate('start_date', now()->addDay()->toDateString()),
                'this_week' => $query->whereBetween('start_date', [now()->startOfWeek(), now()->endOfWeek()]),
                'this_month' => $query->whereMonth('start_date', now()->month),
                default => null,
            };
        } elseif ($tab === 'all') {
            $query->whereDate('start_date', '>=', now()->toDateString());
        }

        // Order & Paginate
        $events = $tab === 'my'
            ? $query->orderBy('start_date', 'desc')->paginate(12)->withQueryString()
            : $query->orderBy('start_date', 'asc')->paginate(12)->withQueryString();

        $minActiveReward = (float) (AdRewardRule::getMinimumActiveRewardAmount(AdRewardRule::TYPE_EVENT) ?? 0.05);
        $maxReward = AdRewardRule::getMaximumActiveRewardAmount(AdRewardRule::TYPE_EVENT);
        $maxRewardFormatted = null;
        if ($maxReward !== null) {
            $formattedNumber = rtrim(rtrim(sprintf('%.4f', $maxReward), '0'), '.');
            if (strpos($formattedNumber, '.') !== false && strlen(substr($formattedNumber, strpos($formattedNumber, '.') + 1)) == 1) {
                $formattedNumber .= '0';
            }
            $maxRewardFormatted = '$' . $formattedNumber;
        }

        $myRewardCampaignIds = $member
            ? AdReward::query()
                ->where('member_id', $member->id)
                ->where('status', AdReward::STATUS_CREDITED)
                ->pluck('ad_campaign_id')
                ->all()
            : [];

        $myResponseMap = $member
            ? EventResponse::query()
                ->where('member_id', $member->id)
                ->pluck('response', 'event_id')
                ->all()
            : [];

        $events->getCollection()->transform(function ($ev) use ($member, $minActiveReward, $maxReward, $maxRewardFormatted, $myRewardCampaignIds, $myResponseMap) {
            $isOrganizer = $member && $ev->isOrganizer($member->id);
            $camp = $ev->campaign;
            $alreadyRewarded = ($camp && in_array($camp->id, $myRewardCampaignIds, true));
            $isCampaignEligible = $camp
                && $camp->approval_status === AdCampaign::APPROVAL_APPROVED
                && in_array($camp->status, [AdCampaign::STATUS_APPROVED, AdCampaign::STATUS_ACTIVE], true)
                && $minActiveReward !== null
                && (float) $camp->remaining_amount >= (float) $minActiveReward
                && !$ev->hasPassed();

            $ev->is_organizer = $isOrganizer;
            $ev->is_paid = (bool) $camp;
            $ev->user_response = $myResponseMap[$ev->id] ?? null;
            $ev->earn_up_to_usd = $maxReward;
            $ev->earn_up_to_formatted = $maxRewardFormatted;
            $ev->already_rewarded = $alreadyRewarded;
            $ev->is_campaign_eligible = $isCampaignEligible;

            if ($camp) {
                $ev->campaign_details = [
                    'id' => $camp->id,
                    'campaign_id' => $camp->campaign_id,
                    'campaign_name' => $camp->campaign_name,
                    'budget' => (float) $camp->budget,
                    'remaining_amount' => (float) $camp->remaining_amount,
                    'status' => $camp->status,
                    'approval_status' => $camp->approval_status,
                    'display_status' => $camp->display_status,
                    'is_eligible' => $isCampaignEligible,
                    'already_rewarded' => $alreadyRewarded,
                    'earn_up_to_usd' => $maxReward,
                    'earn_up_to_formatted' => $maxRewardFormatted,
                ];
            }

            return $ev;
        });

        $myEvents = $tab === 'my' ? $events->items() : [];

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'tab' => $tab,
                'my_events_count' => $myEventsCount,
                'fundable_events_count' => $fundableEventsCount,
                'available_ad_funds' => $member ? round((float) ($member->p2p_wallet ?? 0.00), 2) : 0.00,
                'platform_fee_percent' => (float) Setting::get('campaign_platform_fee_percent', 2.50),
                'earn_up_to_usd' => (float) $maxReward,
                'earn_up_to_formatted' => $maxRewardFormatted,
                'minimum_event_reward' => (float) $minActiveReward,
                'my_events' => $myEvents,
                'events' => $events,
                'categories' => $categories,
            ]);
        }

        return view('member.events.index', compact('events', 'categories', 'tab', 'myEventsCount', 'fundableEventsCount', 'myEvents'));
    }

    public function create(Request $request)
    {
        $categories = BusinessPageCategory::getActiveCategoryNames();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'categories' => $categories,
                'today' => now()->toDateString(),
                'current_time' => now()->format('H:i'),
            ]);
        }

        return view('member.events.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $activeCategories = BusinessPageCategory::getActiveCategoryNames();
        $categoryValidation = ['required', 'string'];
        if (!empty($activeCategories)) {
            $categoryValidation[] = Rule::in($activeCategories);
        }

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => $categoryValidation,
            'event_type' => ['required', 'in:online,offline'],
            'privacy' => ['required', 'in:public,private,friends_only'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable'],
            'end_time' => ['nullable'],
            'location_address' => ['nullable', 'string'],
            'location_city' => ['nullable', 'string'],
            'google_maps_link' => ['nullable', 'url'],
            'meeting_link' => ['nullable', 'url'],
            'cover_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'budget' => ['nullable', 'numeric', 'min:1.00', 'max:999999999.99'],
            'campaign_budget' => ['nullable', 'numeric', 'min:1.00', 'max:999999999.99'],
            'create_campaign' => ['nullable', 'boolean'],
            'currency' => ['nullable', 'in:USD'],
            'idempotency_key' => ['nullable', 'string', 'max:128'],
        ], [
            'category.required' => 'Please select an event category.',
            'category.in' => 'The selected event category is invalid or inactive.',
            'start_date.after_or_equal' => 'The event start date must be today or a future date.',
            'budget.min' => 'Minimum campaign budget is $1.00 USD.',
            'campaign_budget.min' => 'Minimum campaign budget is $1.00 USD.',
            'budget.numeric' => 'Campaign budget must be a valid numeric dollar amount.',
            'campaign_budget.numeric' => 'Campaign budget must be a valid numeric dollar amount.',
            'currency.in' => 'Only USD currency is supported for event campaigns.',
        ]);

        $validator->after(function ($validator) use ($request) {
            $startDate = $request->input('start_date');
            $startTime = $request->input('start_time');

            if ($startDate) {
                try {
                    $timeString = $startTime ? trim((string) $startTime) : null;
                    if ($timeString) {
                        if (strlen($timeString) === 5) {
                            $timeString .= ':00';
                        }
                        $startDateTime = \Carbon\Carbon::parse("{$startDate} {$timeString}");
                    } else {
                        $startDateTime = \Carbon\Carbon::parse("{$startDate} 00:00:00");
                    }

                    if ($startDateTime->lessThanOrEqualTo(now())) {
                        $validator->errors()->add(
                            'start_time',
                            'Start time must be later than the current time when the event starts today.'
                        );
                    }
                } catch (\Throwable $e) {
                    $validator->errors()->add('start_time', 'Invalid start date or time format.');
                }
            }
        });

        $validated = $validator->validate();

        $member = auth('member')->user();

        if (! $member || (method_exists($member, 'isMobileVerified') ? !$member->isMobileVerified() : !$member->mobile_verified_at)) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => EnsureMemberMobileVerified::UNVERIFIED_MESSAGE,
                    'needs_verification' => true,
                ], 403);
            }
            return redirect()->route('member.account.security')->with('error', EnsureMemberMobileVerified::UNVERIFIED_MESSAGE);
        }

        $idempotencyKey = $request->filled('idempotency_key') ? trim((string) $request->input('idempotency_key')) : null;

        // Fast-path Idempotency Check: if an event was already created with this idempotency key
        if ($idempotencyKey) {
            $cachedEventId = Cache::get("event_create_{$member->id}_{$idempotencyKey}");
            if ($cachedEventId) {
                $existingEvent = Event::with(['organizer', 'campaign'])->find($cachedEventId);
                if ($existingEvent) {
                    if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                        return response()->json([
                            'success' => true,
                            'message' => 'Event already created.',
                            'event' => $existingEvent,
                            'is_paid' => $existingEvent->isPaidCampaign(),
                            'is_replay' => true,
                        ], 200);
                    }
                    return redirect()->route('member.events.show', $existingEvent)->with('info', 'Event already created.');
                }
            }

            // DB fallback: check if an event campaign exists with this idempotency key for this member
            $existingCampaign = AdCampaign::where('member_id', $member->id)
                ->where('campaign_type', AdCampaign::TYPE_EVENT)
                ->where('target_audience', 'like', '%"idempotency_key":"' . $idempotencyKey . '"%')
                ->first();

            if ($existingCampaign && $existingCampaign->event_id) {
                $existingEvent = Event::with(['organizer', 'campaign'])->find($existingCampaign->event_id);
                if ($existingEvent) {
                    Cache::put("event_create_{$member->id}_{$idempotencyKey}", $existingEvent->id, now()->addHours(24));
                    if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                        return response()->json([
                            'success' => true,
                            'message' => 'Event already created.',
                            'event' => $existingEvent,
                            'is_paid' => $existingEvent->isPaidCampaign(),
                            'is_replay' => true,
                        ], 200);
                    }
                    return redirect()->route('member.events.show', $existingEvent)->with('info', 'Event already created.');
                }
            }
        }

        // Duplicate submission guard (rapid resubmit within 5 seconds with same title, category, start_date)
        $recentDuplicate = Event::where('organizer_id', $member->id)
            ->where('title', $validated['title'])
            ->where('category', $validated['category'])
            ->where('start_date', $validated['start_date'])
            ->where('created_at', '>=', now()->subSeconds(5))
            ->latest()
            ->first();

        if ($recentDuplicate) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Event already created.',
                    'event' => $recentDuplicate->load(['organizer', 'campaign']),
                    'is_paid' => $recentDuplicate->isPaidCampaign(),
                    'is_replay' => true,
                ], 200);
            }
            return redirect()->route('member.events.show', $recentDuplicate)->with('info', 'Event already created.');
        }

        $slug = Str::slug($validated['title']).'-'.Str::random(6);

        $coverPath = null;
        if ($request->hasFile('cover_photo')) {
            $coverPath = $this->storeFile($request->file('cover_photo'), 'uploads/events/covers');
        }

        $rawBudget = $request->input('budget') ?? $request->input('campaign_budget');
        $hasBudget = $rawBudget !== null && $rawBudget !== '' && (float) $rawBudget > 0;
        $campaignBudget = $hasBudget ? round((float) $rawBudget, 2) : 0.00;
        $createCampaignFlag = $request->boolean('create_campaign');
        $isPaid = $hasBudget || $createCampaignFlag;

        $feePercent = (float) Setting::get('campaign_platform_fee_percent', 2.50);
        $feeAmount = round($campaignBudget * ($feePercent / 100), 2);
        $totalWalletDebit = round($campaignBudget + $feeAmount, 2);

        $event = DB::transaction(function () use (
            $member,
            $validated,
            $slug,
            $coverPath,
            $request,
            $isPaid,
            $hasBudget,
            $campaignBudget,
            $feePercent,
            $feeAmount,
            $totalWalletDebit,
            $idempotencyKey
        ) {
            $newEvent = Event::query()->create([
                'organizer_id' => $member->id,
                'title' => $validated['title'],
                'slug' => $slug,
                'short_description' => $validated['short_description'] ?? null,
                'description' => $validated['description'] ?? null,
                'category' => $validated['category'],
                'event_type' => $validated['event_type'],
                'privacy' => $validated['privacy'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
                'location_address' => $validated['location_address'] ?? null,
                'location_city' => $validated['location_city'] ?? null,
                'google_maps_link' => $validated['google_maps_link'] ?? null,
                'meeting_link' => $validated['meeting_link'] ?? null,
                'cover_photo' => $coverPath,
                'status' => 'published',
            ]);

            // Auto add organizer response as "going"
            EventResponse::query()->create([
                'event_id' => $newEvent->id,
                'member_id' => $member->id,
                'response' => 'going',
            ]);

            if ($isPaid) {
                if ($hasBudget) {
                    /** @var Member $lockedMember */
                    $lockedMember = Member::where('id', $member->id)->lockForUpdate()->first();
                    $availableFunds = (float) ($lockedMember->p2p_wallet ?? 0.00);

                    if ($availableFunds < $totalWalletDebit) {
                        $shortfall = round($totalWalletDebit - $availableFunds, 2);
                        throw ValidationException::withMessages([
                            'budget' => [
                                "Insufficient advertising funds to allocate initial event campaign budget. Campaign Budget: \${$campaignBudget} USD, Platform Fee ({$feePercent}%): \${$feeAmount} USD, Total Required: \${$totalWalletDebit} USD, Available: \${$availableFunds} USD. Shortfall: \${$shortfall} USD. Please deposit funds first."
                            ],
                        ]);
                    }

                    // Deduct total debit (budget + platform fee) from advertiser's Fund Wallet (p2p_wallet)
                    $lockedMember->p2p_wallet = round($availableFunds - $totalWalletDebit, 2);
                    $lockedMember->save();

                    $fundingHistory = [
                        [
                            'type' => 'initial_allocation',
                            'amount' => $campaignBudget,
                            'fee_percent' => $feePercent,
                            'fee_amount' => $feeAmount,
                            'wallet_debit' => $totalWalletDebit,
                            'idempotency_key' => $idempotencyKey,
                            'funded_at' => now()->toIso8601String(),
                            'funded_by_member_id' => $member->id,
                        ]
                    ];

                    $minEventReward = AdRewardRule::getMinimumActiveRewardAmount(AdRewardRule::TYPE_EVENT) ?? 0.05;
                    $isEligibleToActivate = ($newEvent->status === 'published') && ($campaignBudget >= $minEventReward);

                    $campaign = AdCampaign::create([
                        'campaign_type' => AdCampaign::TYPE_EVENT,
                        'event_id' => $newEvent->id,
                        'member_id' => $member->id,
                        'business_page_id' => null,
                        'post_id' => null,
                        'campaign_name' => $newEvent->title,
                        'budget' => $campaignBudget,
                        'additional_funding' => 0.00,
                        'total_funded' => $campaignBudget,
                        'spent_amount' => 0.00,
                        'remaining_amount' => $campaignBudget,
                        'currency' => 'USD',
                        'fee_percent' => $feePercent,
                        'fee_amount' => $feeAmount,
                        'wallet_debit' => $totalWalletDebit,
                        'target_audience' => [
                            'funding_history' => $fundingHistory,
                            'idempotency_key' => $idempotencyKey,
                        ],
                        'status' => $isEligibleToActivate ? AdCampaign::STATUS_ACTIVE : AdCampaign::STATUS_DRAFT,
                        'approval_status' => $isEligibleToActivate ? AdCampaign::APPROVAL_APPROVED : AdCampaign::APPROVAL_PENDING,
                        'approved_at' => $isEligibleToActivate ? now() : null,
                    ]);
                } else {
                    // Unfunded draft campaign (zero wallet debit)
                    $campaign = AdCampaign::create([
                        'campaign_type' => AdCampaign::TYPE_EVENT,
                        'event_id' => $newEvent->id,
                        'member_id' => $member->id,
                        'business_page_id' => null,
                        'post_id' => null,
                        'campaign_name' => $newEvent->title,
                        'budget' => 0.00,
                        'additional_funding' => 0.00,
                        'total_funded' => 0.00,
                        'spent_amount' => 0.00,
                        'remaining_amount' => 0.00,
                        'currency' => 'USD',
                        'fee_percent' => $feePercent,
                        'fee_amount' => 0.00,
                        'wallet_debit' => 0.00,
                        'status' => AdCampaign::STATUS_DRAFT,
                        'approval_status' => AdCampaign::APPROVAL_PENDING,
                    ]);
                }
            }

            if ($idempotencyKey) {
                Cache::put("event_create_{$member->id}_{$idempotencyKey}", $newEvent->id, now()->addHours(24));
            }

            return $newEvent;
        });

        \App\Services\AdminNotificationService::notify(
            title: 'New Event Created',
            message: sprintf('"%s" was scheduled for %s by %s.', $event->title, $event->start_date ? $event->start_date->format('M d, Y') : 'upcoming', $member->name),
            icon: 'calendar',
            sourceType: 'event',
            sourceId: (string) $event->id,
            actionUrl: '/admin/events/' . $event->id,
            metadata: ['event_id' => $event->id]
        );

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Event published successfully!',
                'event' => $event->load(['organizer', 'campaign']),
                'is_paid' => $event->isPaidCampaign(),
            ], 201);
        }

        return redirect()->route('member.events.show', $event)->with('success', 'Event published successfully!');
    }

    public function show(Request $request, Event $event)
    {
        $event->load(['organizer', 'campaign']);

        $member = auth('member')->user();
        $userResponse = $event->userResponse($member?->id);

        $goingCount = $event->goingCount();
        $interestedCount = $event->interestedCount();

        $attendees = EventResponse::query()
            ->with(['member:id,name,email,user_id,profile_photo,phone,city,country,bio'])
            ->where('event_id', $event->id)
            ->whereIn('response', ['going', 'interested'])
            ->latest('updated_at')
            ->get();

        $goingMembers = $attendees->where('response', 'going')->pluck('member')->filter()->values();
        $interestedMembers = $attendees->where('response', 'interested')->pluck('member')->filter()->values();

        $posts = Post::query()
            ->with(['member', 'comments.member', 'likes', 'reactions'])
            ->where('event_id', $event->id)
            ->latest('created_at')
            ->paginate(10);

        $campaign = $event->campaign;
        $isOrganizer = $event->isOrganizer($member?->id);
        $isVerified = $member && method_exists($member, 'isMobileVerified') && $member->isMobileVerified();

        // Paid campaign status is visible to all members for viewing
        $exposeCampaign = (bool) $campaign;

        $minReward = AdRewardRule::getMinimumActiveRewardAmount(AdRewardRule::TYPE_EVENT);
        $alreadyRewarded = false;
        if ($member && $campaign) {
            $alreadyRewarded = AdReward::where('ad_campaign_id', $campaign->id)
                ->where('member_id', $member->id)
                ->where('status', AdReward::STATUS_CREDITED)
                ->exists();
        }

        $isCampaignEligible = $campaign
            && $campaign->approval_status === AdCampaign::APPROVAL_APPROVED
            && in_array($campaign->status, [AdCampaign::STATUS_APPROVED, AdCampaign::STATUS_ACTIVE], true)
            && $minReward !== null
            && (float) $campaign->remaining_amount >= (float) $minReward
            && !$event->hasPassed();

        $maxReward = AdRewardRule::getMaximumActiveRewardAmount(AdRewardRule::TYPE_EVENT);
        $maxRewardFormatted = null;
        if ($maxReward !== null) {
            $formattedNumber = rtrim(rtrim(sprintf('%.4f', $maxReward), '0'), '.');
            if (strpos($formattedNumber, '.') !== false && strlen(substr($formattedNumber, strpos($formattedNumber, '.') + 1)) == 1) {
                $formattedNumber .= '0';
            }
            $maxRewardFormatted = '$' . $formattedNumber;
        }

        $campaignData = $exposeCampaign ? [
            'id' => $campaign->id,
            'campaign_id' => $campaign->campaign_id,
            'campaign_name' => $campaign->campaign_name,
            'budget' => (float) $campaign->budget,
            'remaining_amount' => (float) $campaign->remaining_amount,
            'status' => $campaign->status,
            'approval_status' => $campaign->approval_status,
            'display_status' => $campaign->display_status,
            'is_eligible' => $isCampaignEligible,
            'already_rewarded' => $alreadyRewarded,
            'earn_up_to_usd' => (float) $maxReward,
            'earn_up_to_formatted' => $maxRewardFormatted,
        ] : null;

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'event' => $event,
                'user_response' => $userResponse,
                'going_count' => $goingCount,
                'interested_count' => $interestedCount,
                'going_members' => $goingMembers,
                'interested_members' => $interestedMembers,
                'is_organizer' => $isOrganizer,
                'is_paid' => (bool) $exposeCampaign,
                'campaign' => $campaignData,
                'already_rewarded' => $alreadyRewarded,
                'posts' => $posts,
            ]);
        }

        return view('member.events.show', compact('event', 'userResponse', 'goingCount', 'interestedCount', 'posts'));
    }

    public function edit(Request $request, Event $event)
    {
        if (! $event->isOrganizer(auth('member')->id())) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
            }
            abort(403);
        }

        $categories = array_values(array_unique(array_merge(
            BusinessPageCategory::getActiveCategoryNames(),
            filled($event->category) ? [$event->category] : []
        )));

        $event->load(['organizer', 'campaign']);
        $campaign = $event->campaign;

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'event' => $event,
                'categories' => $categories,
                'today' => now()->toDateString(),
                'current_time' => now()->format('H:i'),
                'is_paid' => $event->isPaidCampaign(),
                'campaign' => $campaign ? [
                    'id' => $campaign->id,
                    'campaign_id' => $campaign->campaign_id,
                    'campaign_name' => $campaign->campaign_name,
                    'budget' => (float) $campaign->budget,
                    'remaining_amount' => (float) $campaign->remaining_amount,
                    'status' => $campaign->status,
                    'approval_status' => $campaign->approval_status,
                ] : null,
            ]);
        }

        return view('member.events.edit', compact('event', 'categories'));
    }

    public function update(Request $request, Event $event)
    {
        if (! $event->isOrganizer(auth('member')->id())) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
            }
            abort(403);
        }

        $allowedCategories = array_values(array_unique(array_merge(
            BusinessPageCategory::getActiveCategoryNames(),
            filled($event->category) ? [$event->category] : []
        )));
        $categoryValidation = ['required', 'string'];
        if (!empty($allowedCategories)) {
            $categoryValidation[] = Rule::in($allowedCategories);
        }

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => $categoryValidation,
            'event_type' => ['required', 'in:online,offline'],
            'privacy' => ['required', 'in:public,private,friends_only'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable'],
            'end_time' => ['nullable'],
            'location_address' => ['nullable', 'string'],
            'meeting_link' => ['nullable', 'url'],
            'cover_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'category.required' => 'Please select an event category.',
            'category.in' => 'The selected event category is invalid or inactive.',
            'start_date.after_or_equal' => 'The event start date must be today or a future date.',
        ]);

        $validator->after(function ($validator) use ($request) {
            $startDate = $request->input('start_date');
            $startTime = $request->input('start_time');

            if ($startDate) {
                try {
                    $timeString = $startTime ? trim((string) $startTime) : null;
                    if ($timeString) {
                        if (strlen($timeString) === 5) {
                            $timeString .= ':00';
                        }
                        $startDateTime = \Carbon\Carbon::parse("{$startDate} {$timeString}");
                    } else {
                        $startDateTime = \Carbon\Carbon::parse("{$startDate} 00:00:00");
                    }

                    if ($startDateTime->lessThanOrEqualTo(now())) {
                        $validator->errors()->add(
                            'start_time',
                            'Start time must be later than the current time when the event starts today.'
                        );
                    }
                } catch (\Throwable $e) {
                    $validator->errors()->add('start_time', 'Invalid start date or time format.');
                }
            }
        });

        $validated = $validator->validate();

        $oldCover = $event->cover_photo;

        if ($request->hasFile('cover_photo')) {
            $validated['cover_photo'] = $this->storeFile($request->file('cover_photo'), 'uploads/events/covers');
        }

        $event->update($validated);

        if ($request->hasFile('cover_photo') && $oldCover && \Illuminate\Support\Facades\File::exists(public_path($oldCover))) {
            \Illuminate\Support\Facades\File::delete(public_path($oldCover));
        }

        // Synchronize campaign name if campaign exists, but NEVER create duplicate campaign or alter financial state
        $campaign = $event->campaign;
        if ($campaign) {
            $campaign->campaign_name = $event->title;
            $campaign->save();
        }

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Event updated successfully!',
                'event' => $event->fresh(['organizer', 'campaign']),
                'is_paid' => $event->isPaidCampaign(),
                'campaign' => $campaign ? [
                    'id' => $campaign->id,
                    'campaign_id' => $campaign->campaign_id,
                    'campaign_name' => $campaign->campaign_name,
                    'budget' => (float) $campaign->budget,
                    'remaining_amount' => (float) $campaign->remaining_amount,
                    'status' => $campaign->status,
                ] : null,
            ]);
        }

        return redirect()->route('member.events.show', $event)->with('success', 'Event updated!');
    }

    public function destroy(Request $request, Event $event)
    {
        if (! $event->isOrganizer(auth('member')->id())) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
            }
            abort(403);
        }

        $event->delete();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Event cancelled and removed.',
            ]);
        }

        return redirect()->route('member.events.index')->with('success', 'Event cancelled and removed.');
    }

    public function respond(Request $request, Event $event)
    {
        $member = auth('member')->user();

        if (! $member || (method_exists($member, 'isMobileVerified') ? !$member->isMobileVerified() : !$member->mobile_verified_at)) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please verify your phone number first before proceeding.',
                    'error_code' => 'PHONE_VERIFICATION_REQUIRED',
                    'verified_required' => true,
                    'needs_verification' => true,
                ], 403);
            }
            return back()->with('error', 'Please verify your phone number first before proceeding.');
        }

        $validated = $request->validate([
            'response' => ['required', 'in:going,interested,maybe,not_going'],
        ]);

        $responseRecord = EventResponse::query()->updateOrCreate(
            ['event_id' => $event->id, 'member_id' => $member->id],
            ['response' => $validated['response']]
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'response' => $validated['response'],
                'going_count' => $event->goingCount(),
                'interested_count' => $event->interestedCount(),
                'message' => 'Event response updated to '.ucfirst($validated['response']),
            ]);
        }

        return back()->with('success', 'Response updated!');
    }

    public function invite(Request $request, Event $event)
    {
        $validated = $request->validate([
            'invited_id' => ['required', 'exists:members,id'],
        ]);

        $inviter = auth('member')->user();

        if (! $inviter->mobile_verified_at) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account verification required. Please verify your mobile number via WhatsApp to invite members.',
                    'needs_verification' => true,
                ], 403);
            }
            return back()->with('error', 'Please verify your mobile number via WhatsApp first.');
        }

        EventInvitation::query()->firstOrCreate(
            ['event_id' => $event->id, 'invited_id' => $validated['invited_id']],
            ['inviter_id' => $inviter->id, 'status' => 'pending']
        );

        Member::query()->find($validated['invited_id'])?->notify(new SystemNotification(
            'Event invitation',
            "{$inviter->name} invited you to {$event->title}.",
            route('member.events.show', $event)
        ));

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Event invitation sent!']);
        }

        return back()->with('success', 'Invitation sent!');
    }

    public function storePost(Request $request, Event $event)
    {
        $member = auth('member')->user();

        if (! $member->mobile_verified_at) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account verification required. Please verify your mobile number via WhatsApp to post in event discussions.',
                    'needs_verification' => true,
                ], 403);
            }
            return back()->with('error', 'Please verify your mobile number via WhatsApp first.');
        }

        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $mime = (string) $file->getMimeType();
            $ext = strtolower($file->getClientOriginalExtension() ?: '');
            if (str_starts_with($mime, 'video/') || in_array($ext, ['mp4', 'mov', 'webm', 'm4v', 'avi', 'mkv'], true) || in_array($mime, ['video/mp4', 'video/webm', 'video/quicktime'], true)) {
                throw ValidationException::withMessages([
                    'media' => 'Videos can only be posted from a Business Page.',
                ]);
            }
        }

        $validated = $request->validate([
            'body' => ['required_without:media', 'nullable', 'string'],
            'media' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $mediaType = null;
        $mediaPath = null;

        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $mediaType = 'image';
            $mediaPath = $this->storeFile($file, 'uploads/posts/images');
        }

        $post = Post::query()->create([
            'member_id' => $member->id,
            'event_id' => $event->id,
            'body' => $validated['body'] ?? null,
            'media_type' => $mediaType,
            'media_path' => $mediaPath,
        ]);

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Posted in Event Discussion!',
                'post' => $post->load(['member', 'comments.member', 'likes', 'reactions']),
            ], 201);
        }

        return redirect()->route('member.events.show', $event)->with('success', 'Posted in Event Discussion!');
    }

    public function outreach(Request $request, Event $event)
    {
        $member = auth('member')->user();
        if (! $event->isOrganizer($member->id)) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only the event host can access the Host Outreach & Contact Center.',
                ], 403);
            }
            abort(403);
        }

        $event->load(['organizer:id,name,email,user_id,profile_photo,phone,mobile_verified_at']);

        $attendees = EventResponse::query()
            ->with(['member:id,name,email,user_id,profile_photo,phone,mobile_verified_at,city,country,bio,created_at'])
            ->where('event_id', $event->id)
            ->whereIn('response', ['going', 'interested'])
            ->latest('updated_at')
            ->get();

        $goingMembers = $attendees->where('response', 'going')->map(function ($item) {
            $m = $item->member;
            if (! $m) return null;
            $mArray = $m->toArray();
            $mArray['response_type'] = 'going';
            $mArray['responded_at'] = $item->updated_at?->toIso8601String() ?? $item->created_at?->toIso8601String();
            return $mArray;
        })->filter()->values();

        $interestedMembers = $attendees->where('response', 'interested')->map(function ($item) {
            $m = $item->member;
            if (! $m) return null;
            $mArray = $m->toArray();
            $mArray['response_type'] = 'interested';
            $mArray['responded_at'] = $item->updated_at?->toIso8601String() ?? $item->created_at?->toIso8601String();
            return $mArray;
        })->filter()->values();

        $allMembers = $goingMembers->concat($interestedMembers)->values();

        $stats = [
            'total_attendees' => $allMembers->count(),
            'going_count' => $goingMembers->count(),
            'interested_count' => $interestedMembers->count(),
            'with_phone_count' => $allMembers->filter(fn ($m) => ! empty($m['phone']))->count(),
            'with_email_count' => $allMembers->filter(fn ($m) => ! empty($m['email']))->count(),
            'verified_members_count' => $allMembers->filter(fn ($m) => ! empty($m['is_verified']) || ! empty($m['mobile_verified_at']))->count(),
        ];

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'event' => $event,
                'stats' => $stats,
                'going_members' => $goingMembers,
                'interested_members' => $interestedMembers,
                'all_members' => $allMembers,
            ]);
        }

        return view('member.events.outreach', compact('event', 'stats', 'goingMembers', 'interestedMembers', 'allMembers'));
    }

    private function storeFile($file, string $directory): ?string
    {
        try {
            $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
            $filename = time().'_'.Str::random(10).'.'.$ext;
            $type = str_contains($directory, 'cover') ? 'cover' : 'post';

            return app(\App\Http\Controllers\ImageCompressionController::class)->compressAndStore(
                $file,
                $directory,
                $type,
                $filename,
                'public_uploads'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Event image store failed: " . $e->getMessage());
            return null;
        }
    }
}
