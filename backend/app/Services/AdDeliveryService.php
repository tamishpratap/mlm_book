<?php

namespace App\Services;

use App\Models\AdCampaign;
use App\Models\AdReward;
use App\Models\AdRewardRule;
use App\Models\Event;
use App\Models\Friendship;
use App\Models\Member;
use App\Models\Post;
use App\Models\RewardRankRule;
use Illuminate\Support\Collection;

class AdDeliveryService
{
    /**
     * Resolve viewer member model.
     */
    public function resolveViewer($viewerMember = null): ?Member
    {
        if ($viewerMember instanceof Member) {
            return $viewerMember;
        } elseif (is_numeric($viewerMember)) {
            return Member::find($viewerMember);
        } else {
            return auth('member')->user();
        }
    }

    /**
     * Fetch active approved ad campaigns eligible for social feed delivery.
     * Unverified members are eligible to view active campaigns.
     *
     * @param int $limit Maximum number of campaigns to return
     * @param int|Member|null $viewerMember The authenticated member requesting the feed
     * @return Collection<int, AdCampaign>
    /**
     * Fetch active approved ad campaigns eligible for social feed delivery.
     * Unverified members are eligible to view active campaigns.
     *
     * @param int $limit Maximum number of campaigns to return
     * @param int|Member|null $viewerMember The authenticated member requesting the feed
     * @return Collection<int, AdCampaign>
     */
    public function getEligibleCampaigns(int $limit = 20, $viewerMember = null): Collection
    {
        $viewer = $this->resolveViewer($viewerMember);

        $now = now();

        // 1. Maintain campaign states if expired by date or budget
        $this->maintainCampaignLifecycles($now);

        $minReward = AdRewardRule::getMinimumActiveRewardAmount(AdRewardRule::TYPE_BUSINESS_AD);
        if ($minReward === null) {
            return collect();
        }

        // 2. Fetch eligible campaigns ordered by budget desc, recency desc, id desc
        return AdCampaign::query()
            ->with([
                'businessPage',
                'owner:id,name,user_id,email,profile_photo',
                'post' => function ($q) {
                    $q->with(['member', 'businessPage'])
                        ->withCount([
                            'likes',
                            'reactions',
                            'shares',
                            'savedPosts',
                            'comments' => fn ($cq) => $cq->whereNull('parent_id'),
                        ]);
                },
            ])
            ->where(function ($q) {
                $q->where('campaign_type', AdCampaign::TYPE_BUSINESS_AD)
                    ->orWhereNull('campaign_type');
            })
            ->where('approval_status', AdCampaign::APPROVAL_APPROVED)
            ->whereIn('status', [AdCampaign::STATUS_APPROVED, AdCampaign::STATUS_ACTIVE])
            ->where('budget', '>=', $minReward)
            ->where('remaining_amount', '>=', $minReward)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
            })
            ->whereHas('businessPage', function ($q) {
                $q->where('status', 'active');
            })
            ->whereHas('post', function ($pq) {
                $pq->where(function ($vq) {
                    $vq->where('media_type', '!=', 'video')
                        ->orWhereNull('media_type')
                        ->orWhereColumn('posts.business_page_id', 'ad_campaigns.business_page_id');
                });
            })
            ->orderByDesc('remaining_amount')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->take($limit)
            ->get();
    }

    /**
     * Fetch active approved event campaigns eligible for social feed delivery.
     * Unverified members are eligible to view active event campaigns.
     *
     * @param int $limit Maximum number of campaigns to return
     * @param int|Member|null $viewerMember The authenticated member requesting the feed
     * @return Collection<int, AdCampaign>
     */
    public function getEligibleEventCampaigns(int $limit = 20, $viewerMember = null): Collection
    {
        $viewer = $this->resolveViewer($viewerMember);

        $now = now();
        $this->maintainCampaignLifecycles($now);

        $minEventReward = AdRewardRule::getMinimumActiveRewardAmount(AdRewardRule::TYPE_EVENT);
        if ($minEventReward === null) {
            return collect();
        }

        return AdCampaign::query()
            ->with([
                'event' => function ($q) {
                    $q->with(['organizer:id,name,user_id,email,profile_photo,mobile_verified_at'])
                        ->withCount(['responses as guests_count']);
                },
                'owner:id,name,user_id,email,profile_photo',
            ])
            ->eligibleEventForDelivery()
            ->orderByDesc('remaining_amount')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->take($limit)
            ->get();
    }

    /**
     * Fetch all active approved Business Ads and Event Campaigns in ONE common ranking pool.
     *
     * Strict Ranking Rules:
     * 1. Primary: Higher eligible campaign budget (remaining_amount) first
     * 2. Secondary: Newer content/campaign (created_at) first
     * 3. Tie-breaker: Deterministic unique identifier (id) first
     *
     * @param int|Member|null $viewerMember The viewer member
     * @return Collection<int, Post>
     */
    public function getRankedPaidFeedItems($viewerMember = null): Collection
    {
        $viewer = $this->resolveViewer($viewerMember);
        $now = now();

        $this->maintainCampaignLifecycles($now);

        $minReward = AdRewardRule::getMinimumActiveRewardAmount(AdRewardRule::TYPE_BUSINESS_AD);
        $minEventReward = AdRewardRule::getMinimumActiveRewardAmount(AdRewardRule::TYPE_EVENT);

        // 1. Fetch eligible Business Ads
        $businessAds = collect();
        if ($minReward !== null) {
            $businessAds = AdCampaign::query()
                ->with([
                    'businessPage',
                    'owner:id,name,user_id,email,profile_photo',
                    'post' => function ($q) {
                        $q->with(['member', 'businessPage'])
                            ->withCount([
                                'likes',
                                'reactions',
                                'shares',
                                'savedPosts',
                                'comments' => fn ($cq) => $cq->whereNull('parent_id'),
                            ]);
                    },
                ])
                ->where(function ($q) {
                    $q->where('campaign_type', AdCampaign::TYPE_BUSINESS_AD)
                        ->orWhereNull('campaign_type');
                })
                ->where('approval_status', AdCampaign::APPROVAL_APPROVED)
                ->whereIn('status', [AdCampaign::STATUS_APPROVED, AdCampaign::STATUS_ACTIVE])
                ->where('budget', '>=', $minReward)
                ->where('remaining_amount', '>=', $minReward)
                ->where(function ($q) use ($now) {
                    $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
                })
                ->whereHas('businessPage', function ($q) {
                    $q->where('status', 'active');
                })
                ->whereHas('post', function ($pq) {
                    $pq->where(function ($vq) {
                        $vq->where('media_type', '!=', 'video')
                            ->orWhereNull('media_type')
                            ->orWhereColumn('posts.business_page_id', 'ad_campaigns.business_page_id');
                    });
                })
                ->get();
        }

        // 2. Fetch eligible Event Campaigns
        $eventCampaigns = collect();
        if ($minEventReward !== null) {
            $eventCampaigns = AdCampaign::query()
                ->with([
                    'event' => function ($q) {
                        $q->with(['organizer:id,name,user_id,email,profile_photo,mobile_verified_at'])
                            ->withCount(['responses as guests_count']);
                    },
                    'owner:id,name,user_id,email,profile_photo',
                ])
                ->eligibleEventForDelivery()
                ->get();
        }

        // 3. Merge into one common collection
        $allCampaigns = $businessAds->concat($eventCampaigns);

        // 4. Strict filter before ranking: verify validity, state, and date expiration
        $eligibleCampaigns = $allCampaigns->filter(function (AdCampaign $c) {
            if ($c->campaign_type === AdCampaign::TYPE_EVENT) {
                return $c->isEligibleEventForDelivery();
            }
            return $c->isEligibleForDelivery();
        });

        // 5. Common Paid Content Ranking
        $rankedCampaigns = $eligibleCampaigns->sort(function (AdCampaign $a, AdCampaign $b) {
            // Primary Sort: Authoritative current eligible campaign budget (remaining_amount)
            $budgetA = (float) ($a->remaining_amount ?? $a->budget ?? 0);
            $budgetB = (float) ($b->remaining_amount ?? $b->budget ?? 0);

            if (abs($budgetA - $budgetB) > 0.00001) {
                return $budgetA < $budgetB ? 1 : -1; // Higher budget first
            }

            // Secondary Sort: Newer content/campaign first (created_at DESC)
            $timeA = $a->created_at ? $a->created_at->timestamp : 0;
            $timeB = $b->created_at ? $b->created_at->timestamp : 0;

            if ($timeA !== $timeB) {
                return $timeA < $timeB ? 1 : -1; // Newer first
            }

            // Deterministic Tie-Breaker: Unique ID DESC
            return $b->id <=> $a->id;
        })->values();

        // 6. Format into transient Post representations in ranked order
        $rankedItems = collect();
        $seenCampaignIds = [];

        foreach ($rankedCampaigns as $campaign) {
            if (in_array($campaign->id, $seenCampaignIds, true)) {
                continue;
            }

            if ($campaign->campaign_type === AdCampaign::TYPE_EVENT) {
                $post = $this->formatSponsoredEventPost($campaign, $viewer);
            } else {
                $post = $this->formatSponsoredPost($campaign, $viewer);
            }

            if ($post) {
                $seenCampaignIds[] = $campaign->id;
                $rankedItems->push($post);
            }
        }

        return $rankedItems;
    }

    /**
     * Retrieve eligible organic published events (upcoming or ongoing) matching visibility rules.
     *
     * @param int|Member|null $viewerMember
     * @param array $excludeEventIds
     * @return Collection<int, Post>
     */
    public function getEligibleOrganicEvents($viewerMember = null, array $excludeEventIds = []): Collection
    {
        $viewer = $this->resolveViewer($viewerMember);

        $query = Event::query()
            ->with([
                'organizer:id,name,user_id,email,profile_photo,mobile_verified_at',
                'campaign',
            ])
            ->withCount(['responses as guests_count'])
            ->where('status', Event::STATUS_PUBLISHED)
            ->notPassed();

        if (!empty($excludeEventIds)) {
            $query->whereNotIn('id', $excludeEventIds);
        }

        if ($viewer) {
            $friendIds = Friendship::query()
                ->forMember($viewer->id)
                ->accepted()
                ->get(['member_one_id', 'member_two_id'])
                ->map(fn (Friendship $f) => $f->member_one_id === $viewer->id ? $f->member_two_id : $f->member_one_id)
                ->all();

            $query->where(function ($q) use ($viewer, $friendIds) {
                // 1. Viewer's own events (always visible to organizer)
                $q->where('organizer_id', $viewer->id)
                    // 2. Friends' events with public or friends_only privacy
                    ->orWhere(function ($fq) use ($friendIds) {
                        $fq->whereIn('organizer_id', $friendIds)
                            ->whereIn('privacy', ['public', 'friends_only']);
                    })
                    // 3. Public events from any user
                    ->orWhere('privacy', 'public');
            });
        } else {
            $query->where('privacy', 'public');
        }

        $events = $query->latest('created_at')->take(20)->get();

        $formatted = collect();
        foreach ($events as $event) {
            $post = $this->formatEventPost($event, $viewer);
            if ($post) {
                $formatted->push($post);
            }
        }

        return $formatted;
    }

    /**
     * Retrieve all feed delivery items (both ranked paid campaigns and eligible organic events for verified members).
     *
     * @param int|Member|null $viewerMember
     * @return Collection<int, Post>
     */
    public function getFeedDeliveryItems($viewerMember = null): Collection
    {
        $viewer = $this->resolveViewer($viewerMember);

        // 1. Fetch common pool of eligible paid items (business ads + event campaigns)
        $rankedPaidItems = $this->getRankedPaidFeedItems($viewer);

        // If viewer is unverified, strictly only paid items are visible in Socials feed
        if (!$viewer || !$viewer->isMobileVerified()) {
            return $rankedPaidItems;
        }

        // 2. For verified members: also fetch eligible published upcoming/current events
        $paidEventIds = $rankedPaidItems->pluck('event_id')->filter()->all();
        $organicEvents = $this->getEligibleOrganicEvents($viewer, $paidEventIds);

        if ($organicEvents->isEmpty()) {
            return $rankedPaidItems;
        }

        // Separate viewer's own events (priority placement) from other community events
        $viewerId = $viewer->id;
        $ownEvents = $organicEvents->filter(fn ($item) => (int) $item->member_id === (int) $viewerId)->values();
        $otherEvents = $organicEvents->reject(fn ($item) => (int) $item->member_id === (int) $viewerId)->values();

        // Own created events are prioritized at the top for creator visibility,
        // followed by paid campaigns (ranked by budget), followed by other community events
        return $ownEvents->concat($rankedPaidItems)->concat($otherEvents);
    }

    /**
     * Intersperse eligible sponsored ads and events into an organic post collection.
     *
     * @param Collection $postsCollection Organic posts
     * @param int|Member|null $viewerMember Authenticated viewer member
     * @param int $frequency Interval between sponsored ads (default every 5 posts)
     * @param int $adLimit Maximum number of sponsored campaigns to retrieve
     * @param int $page Current pagination page
     * @param bool $hasMorePages Whether more organic pages exist
     * @param int $perPage Target items per page
     * @return Collection
     */
    public function injectAdsIntoFeed(
        Collection $postsCollection,
        $viewerMember = null,
        int $frequency = 5,
        int $adLimit = 20,
        int $page = 1,
        bool $hasMorePages = false,
        int $perPage = 10
    ): Collection {
        $viewer = $this->resolveViewer($viewerMember);

        // Retrieve delivery items (paid campaigns + eligible community/own events)
        $sponsoredItems = $this->getFeedDeliveryItems($viewer);

        if ($sponsoredItems->isEmpty()) {
            return $postsCollection;
        }

        // If an active sponsored campaign is in the organic collection, prioritize the sponsored representation
        $sponsoredPostIds = $sponsoredItems->pluck('id')->filter()->all();
        $postsCollection = $postsCollection->reject(function ($item) use ($sponsoredPostIds) {
            return in_array($item->id, $sponsoredPostIds, true);
        })->values();

        $totalAds = $sponsoredItems->count();
        if ($totalAds === 0) {
            return $postsCollection;
        }

        // If organic feed is empty, deliver all eligible sponsored items directly
        if ($postsCollection->isEmpty()) {
            return $sponsoredItems;
        }

        // Progressive pagination: allow up to 5 ads/events per page slice so neither ads nor events are dropped
        $slotsPerPage = max(5, (int) ceil($perPage / 2));
        $startOffset = ($page - 1) * $slotsPerPage;

        if ($startOffset >= $totalAds) {
            return $postsCollection;
        }

        if ($hasMorePages) {
            $pageAds = $sponsoredItems->slice($startOffset, $slotsPerPage)->values();
        } else {
            $pageAds = $sponsoredItems->slice($startOffset)->values();
        }

        $totalPageAds = $pageAds->count();
        if ($totalPageAds === 0) {
            return $postsCollection;
        }

        $newCollection = collect();
        $adIndex = 0;

        // Sponsored Ads & Events receive priority placement on Page 1 (first item where applicable)
        if ($page === 1 && $totalPageAds > 0) {
            $sponsoredPost = $pageAds[$adIndex];
            if ($sponsoredPost) {
                $newCollection->push($sponsoredPost);
                $adIndex++;
            }
        }

        // Intersperse subsequent sponsored items every $interval posts
        $interval = max(2, min($frequency, 3));
        foreach ($postsCollection as $index => $item) {
            $newCollection->push($item);

            $shouldInsertAd = ($adIndex < $totalPageAds) && (($index + 1) % $interval === 0);

            if ($shouldInsertAd) {
                $sponsoredPost = $pageAds[$adIndex];
                if ($sponsoredPost) {
                    $newCollection->push($sponsoredPost);
                    $adIndex++;
                }
            }
        }

        // Append any remaining page ads (e.g. on last page or when organic post count is small)
        while ($adIndex < $totalPageAds) {
            $sponsoredPost = $pageAds[$adIndex];
            if ($sponsoredPost) {
                $newCollection->push($sponsoredPost);
            }
            $adIndex++;
        }

        return $newCollection;
    }

    /**
     * Format an ad campaign's post as a sponsored feed item.
     */
    public function formatSponsoredPost(AdCampaign $campaign, $viewerMember = null): ?Post
    {
        if (!$campaign->post) {
            return null;
        }

        // Platform Rule: Video creation/publishing is allowed ONLY through a Business Page.
        // If the post has media_type === 'video', it MUST belong to an active Business Page
        // and its business_page_id must match the campaign's business_page_id.
        if ($campaign->post->media_type === 'video') {
            if (
                empty($campaign->post->business_page_id)
                || (int) $campaign->post->business_page_id !== (int) $campaign->business_page_id
                || empty($campaign->businessPage)
                || $campaign->businessPage->status !== 'active'
            ) {
                return null;
            }
        }

        $viewer = $this->resolveViewer($viewerMember);
        $viewerId = $viewer?->id;

        $alreadyRewarded = false;
        if ($viewerId) {
            $alreadyRewarded = AdReward::where('ad_campaign_id', $campaign->id)
                ->where('member_id', $viewerId)
                ->where('status', AdReward::STATUS_CREDITED)
                ->exists();
        }

        $maxReward = RewardRankRule::getMaximumActiveRewardAmount() ?? AdRewardRule::getMaximumActiveRewardAmount(AdRewardRule::TYPE_BUSINESS_AD) ?? 0.0500;
        $maxRewardFormatted = null;
        if ($maxReward !== null) {
            $maxRewardFormatted = '$' . number_format((float) $maxReward, 4, '.', '');
        }

        $isOwner = $viewerId ? $campaign->isOwner($viewerId) : false;

        $post = clone $campaign->post;
        $post->is_sponsored = true;
        $post->is_owner = $isOwner;
        $post->type = 'sponsored_ad';
        $post->content_type = 'PAID_AD';
        $post->ad_campaign_id = $campaign->campaign_id;
        $post->ad_campaign_raw_id = $campaign->id;
        $post->ad_campaign = [
            'id' => $campaign->id,
            'campaign_id' => $campaign->campaign_id,
            'campaign_type' => AdCampaign::TYPE_BUSINESS_AD,
            'campaign_name' => $campaign->campaign_name,
            'member_id' => $campaign->member_id,
            'is_owner' => $isOwner,
            'budget' => (float) $campaign->budget,
            'currency' => $campaign->currency ?? 'USD',
            'spent_amount' => (float) $campaign->spent_amount,
            'remaining_amount' => (float) $campaign->remaining_amount,
            'status' => $campaign->status,
            'already_rewarded' => $alreadyRewarded,
            'reward_amount_usd' => $isOwner ? null : (float) $maxReward,
            'earn_up_to_usd' => $isOwner ? null : (float) $maxReward,
            'earn_up_to_formatted' => $isOwner ? null : $maxRewardFormatted,
            'business_page' => $campaign->businessPage,
        ];
        $post->business_page = $campaign->businessPage;
        if ($campaign->businessPage) {
            $post->setRelation('businessPage', $campaign->businessPage);
        }

        return $post;
    }

    /**
     * Format an event as a feed post item (supporting both sponsored campaigns and organic events).
     */
    public function formatEventPost(Event $event, $viewerMember = null, ?AdCampaign $campaign = null): ?Post
    {
        if ($event->hasPassed()) {
            return null;
        }

        $viewer = $this->resolveViewer($viewerMember);
        $viewerId = $viewer?->id;

        $campaign = $campaign ?? $event->campaign;
        $hasActiveCampaign = $campaign && $campaign->isEligibleEventForDelivery();

        $maxReward = null;
        $maxRewardFormatted = null;
        $alreadyRewarded = false;

        if ($hasActiveCampaign) {
            $maxReward = RewardRankRule::getMaximumActiveRewardAmount() ?? AdRewardRule::getMaximumActiveRewardAmount(AdRewardRule::TYPE_EVENT) ?? 0.0500;
            if ($maxReward !== null) {
                $maxRewardFormatted = '$' . number_format((float) $maxReward, 4, '.', '');
            }

            if ($viewerId) {
                $alreadyRewarded = AdReward::where('ad_campaign_id', $campaign->id)
                    ->where('member_id', $viewerId)
                    ->where('status', AdReward::STATUS_CREDITED)
                    ->exists();
            }
        }

        $isOwner = ($viewerId && $campaign) ? $campaign->isOwner($viewerId) : false;
        $isOrganizer = $viewerId ? (bool) $event->isOrganizer($viewerId) : false;
        $isEffectiveOwner = $isOwner || $isOrganizer;

        $coverPhotoUrl = $event->cover_photo_url;
        if (!$coverPhotoUrl && $event->cover_photo) {
            $coverPhotoUrl = str_starts_with($event->cover_photo, 'http')
                ? $event->cover_photo
                : asset($event->cover_photo);
        }

        $organizerPhotoUrl = null;
        if ($event->organizer?->profile_photo) {
            $organizerPhotoUrl = str_starts_with($event->organizer->profile_photo, 'http')
                ? $event->organizer->profile_photo
                : asset($event->organizer->profile_photo);
        }

        // Construct transient Post representation for feed parity
        $post = new Post();
        $post->setKeyType('string');
        $post->incrementing = false;
        $post->id = $hasActiveCampaign ? ('event_campaign_' . $campaign->id) : ('event_' . $event->id);
        $post->member_id = $event->organizer_id;
        $post->body = $event->description ?? $event->short_description;
        $post->media_type = $event->cover_photo ? 'image' : null;
        $post->media_path = $event->cover_photo;
        $post->media_url = $coverPhotoUrl;
        $post->created_at = $hasActiveCampaign ? ($campaign->created_at ?? $event->created_at) : $event->created_at;
        $post->is_sponsored = $hasActiveCampaign;
        $post->is_owner = $isEffectiveOwner;
        $post->type = $hasActiveCampaign ? 'sponsored_event' : 'event';
        $underlyingPostId = $campaign?->post_id;
        if (! $underlyingPostId && $event->relationLoaded('posts')) {
            $underlyingPostId = $event->posts->first()?->id;
        }
        $post->post_id = $underlyingPostId;
        $post->source_post_id = $underlyingPostId;
        $post->ad_campaign_id = $hasActiveCampaign ? $campaign->campaign_id : null;
        $post->ad_campaign_raw_id = $hasActiveCampaign ? $campaign->id : null;
        $post->event_id = $event->id;
        $post->event = [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description ?? $event->short_description,
            'short_description' => $event->short_description,
            'event_type' => $event->event_type,
            'category' => $event->category,
            'privacy' => $event->privacy,
            'start_date' => $event->start_date?->toDateString(),
            'start_time' => $event->start_time,
            'end_date' => $event->end_date?->toDateString(),
            'end_time' => $event->end_time,
            'location_venue' => $event->location_venue,
            'location_city' => $event->location_city,
            'cover_photo' => $event->cover_photo,
            'cover_photo_url' => $coverPhotoUrl,
            'is_organizer' => $isOrganizer,
            'is_owner' => $isEffectiveOwner,
            'organizer' => $event->organizer ? [
                'id' => $event->organizer->id,
                'name' => $event->organizer->name,
                'user_id' => $event->organizer->user_id,
                'profile_photo' => $event->organizer->profile_photo,
                'profile_photo_url' => $organizerPhotoUrl,
                'mobile_verified_at' => $event->organizer->mobile_verified_at,
            ] : null,
            'guests_count' => (int) ($event->guests_count ?? 0),
        ];

        if ($hasActiveCampaign) {
            $post->ad_campaign = [
                'id' => $campaign->id,
                'campaign_id' => $campaign->campaign_id,
                'campaign_type' => AdCampaign::TYPE_EVENT,
                'post_id' => $underlyingPostId,
                'source_post_id' => $underlyingPostId,
                'campaign_name' => $campaign->campaign_name,
                'member_id' => $campaign->member_id,
                'is_owner' => $isEffectiveOwner,
                'budget' => (float) $campaign->budget,
                'currency' => $campaign->currency ?? 'USD',
                'spent_amount' => (float) $campaign->spent_amount,
                'remaining_amount' => (float) $campaign->remaining_amount,
                'status' => $campaign->status,
                'already_rewarded' => $alreadyRewarded,
                'reward_amount_usd' => $isEffectiveOwner ? null : (float) $maxReward,
                'earn_up_to_usd' => $isEffectiveOwner ? null : (float) $maxReward,
                'earn_up_to_formatted' => $isEffectiveOwner ? null : $maxRewardFormatted,
            ];
        } else {
            $post->ad_campaign = null;
        }

        $post->setRelation('member', $event->organizer);

        return $post;
    }

    /**
     * Format an event campaign as a sponsored event feed item.
     */
    public function formatSponsoredEventPost(AdCampaign $campaign, $viewerMember = null): ?Post
    {
        if (!$campaign->event) {
            return null;
        }

        return $this->formatEventPost($campaign->event, $viewerMember, $campaign);
    }

    /**
     * Auto-transition exhausted campaigns.
     */
    public function maintainCampaignLifecycles($now): void
    {
        $minReward = RewardRankRule::getMinimumActiveRewardAmount() ?? AdRewardRule::getMinimumActiveRewardAmount(AdRewardRule::TYPE_BUSINESS_AD);
        if ($minReward !== null) {
            AdCampaign::query()
                ->where(function ($q) {
                    $q->where('campaign_type', AdCampaign::TYPE_BUSINESS_AD)
                        ->orWhereNull('campaign_type');
                })
                ->where('remaining_amount', '<', $minReward)
                ->whereIn('status', [AdCampaign::STATUS_APPROVED, AdCampaign::STATUS_ACTIVE])
                ->update(['status' => AdCampaign::STATUS_BUDGET_EXHAUSTED]);
        }

        $minEventReward = RewardRankRule::getMinimumActiveRewardAmount() ?? AdRewardRule::getMinimumActiveRewardAmount(AdRewardRule::TYPE_EVENT);
        if ($minEventReward !== null) {
            AdCampaign::query()
                ->where('campaign_type', AdCampaign::TYPE_EVENT)
                ->where('remaining_amount', '<', $minEventReward)
                ->whereIn('status', [AdCampaign::STATUS_APPROVED, AdCampaign::STATUS_ACTIVE])
                ->update(['status' => AdCampaign::STATUS_BUDGET_EXHAUSTED]);
        }
    }
}
