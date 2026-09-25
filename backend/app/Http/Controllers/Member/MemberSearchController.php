<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\BusinessPage;
use App\Models\Community;
use App\Models\Event;
use App\Models\Friendship;
use App\Models\Member;
use App\Models\Post;
use App\Services\MemberUserIdService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MemberSearchController extends Controller
{
    private const TYPES = ['all', 'members', 'pages', 'groups', 'posts', 'events'];

    public function __construct(private MemberUserIdService $userIds) {}

    public function index(Request $request)
    {
        [$search, $type] = $this->validatedSearch($request);
        $data = $this->searchData($search, $type);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'search' => $search,
                'query' => $search,
                'type' => $type,
                'counts' => $data['counts'],
                'state' => $data['state'],
                'members' => $data['members'],
                'communities' => $data['communities'],
                'pages' => $data['pages'],
                'events' => $data['events'],
                'posts' => $data['posts'],
                'friendships' => $data['friendships'],
            ]);
        }

        return view('member.search.index', $data);
    }

    public function results(Request $request)
    {
        [$search, $type] = $this->validatedSearch($request);
        $data = $this->searchData($search, $type);
        $pagination = '';

        if ($type === 'members' && $data['members']?->hasPages()) {
            $pagination = $data['members']
                ->links('member.search.pagination')
                ->render();
        } elseif ($type === 'groups' && $data['communities']?->hasPages()) {
            $pagination = $data['communities']
                ->links('member.search.pagination')
                ->render();
        } elseif ($type === 'pages' && $data['pages']?->hasPages()) {
            $pagination = $data['pages']
                ->links('member.search.pagination')
                ->render();
        } elseif ($type === 'events' && $data['events']?->hasPages()) {
            $pagination = $data['events']
                ->links('member.search.pagination')
                ->render();
        } elseif ($type === 'posts' && $data['posts']?->hasPages()) {
            $pagination = $data['posts']
                ->links('member.search.pagination')
                ->render();
        }

        return response()->json([
            'success' => true,
            'search' => $search,
            'query' => $search,
            'type' => $type,
            'counts' => $data['counts'],
            'state' => $data['state'],
            'members' => $data['members'],
            'communities' => $data['communities'],
            'pages' => $data['pages'],
            'events' => $data['events'],
            'posts' => $data['posts'],
            'friendships' => $data['friendships'],
            'html' => view('member.search.partials.results', $data)->render(),
            'pagination' => $pagination,
        ]);
    }

    private function validatedSearch(Request $request): array
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'in:'.implode(',', self::TYPES)],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        return [
            trim((string) ($validated['q'] ?? '')),
            $validated['type'] ?? 'all',
        ];
    }

    private function searchData(string $search, string $type): array
    {
        $counts = array_fill_keys(self::TYPES, 0);
        $members = null;
        $communities = null;
        $pages = null;
        $events = null;
        $posts = null;
        $state = 'results';
        $unavailableTypes = [];
        $friendships = collect();

        if (mb_strlen($search) < 2) {
            $state = ($search === '') ? 'initial' : 'short';

            return [
                'search' => $search,
                'type' => $type,
                'counts' => $counts,
                'members' => $members,
                'communities' => $communities,
                'pages' => $pages,
                'events' => $events,
                'posts' => $posts,
                'state' => $state,
                'unavailableTypes' => $unavailableTypes,
                'friendships' => $friendships,
            ];
        }

        $communityQuery = $this->communityQuery($search);
        $counts['groups'] = (clone $communityQuery)->count();

        $memberQuery = $this->memberQuery($search);
        $counts['members'] = (clone $memberQuery)->count();

        $pageQuery = $this->pageQuery($search);
        $counts['pages'] = (clone $pageQuery)->count();

        $eventQuery = $this->eventQuery($search);
        $counts['events'] = (clone $eventQuery)->count();

        $postQuery = $this->postQuery($search);
        $counts['posts'] = (clone $postQuery)->count();

        $counts['all'] = $counts['members'] + $counts['groups'] + $counts['pages'] + $counts['events'] + $counts['posts'];

        if ($type === 'all') {
            $members = (clone $memberQuery)->limit(5)->get();
            $communities = (clone $communityQuery)->limit(6)->get();
            $pages = (clone $pageQuery)->limit(6)->get();
            $events = (clone $eventQuery)->limit(6)->get();
            $posts = (clone $postQuery)->limit(5)->get();

            $state = ($members->isEmpty() && $communities->isEmpty() && $pages->isEmpty() && $events->isEmpty() && $posts->isEmpty()) ? 'empty' : 'results';
        } elseif ($type === 'members') {
            $members = $memberQuery
                ->paginate(12)
                ->withPath(route('member.search'))
                ->withQueryString();
            $state = $members->isEmpty() ? 'empty' : 'results';
        } elseif ($type === 'groups') {
            $communities = $communityQuery
                ->paginate(12)
                ->withPath(route('member.search'))
                ->withQueryString();
            $state = $communities->isEmpty() ? 'empty' : 'results';
        } elseif ($type === 'pages') {
            $pages = $pageQuery
                ->paginate(12)
                ->withPath(route('member.search'))
                ->withQueryString();
            $state = $pages->isEmpty() ? 'empty' : 'results';
        } elseif ($type === 'events') {
            $events = $eventQuery
                ->paginate(12)
                ->withPath(route('member.search'))
                ->withQueryString();
            $state = $events->isEmpty() ? 'empty' : 'results';
        } elseif ($type === 'posts') {
            $posts = $postQuery
                ->paginate(10)
                ->withPath(route('member.search'))
                ->withQueryString();
            $state = $posts->isEmpty() ? 'empty' : 'results';
        } else {
            $state = 'unavailable';
        }

        if ($members && $members->isNotEmpty()) {
            $memberIds = $members->pluck('id');
            $currentMemberId = auth('member')->id();

            $friendships = Friendship::query()
                ->forMember($currentMemberId)
                ->where(function ($query) use ($memberIds) {
                    $query->whereIn('member_one_id', $memberIds)
                        ->orWhereIn('member_two_id', $memberIds);
                })
                ->get()
                ->keyBy(fn (Friendship $friendship) => $friendship->member_one_id === $currentMemberId
                    ? $friendship->member_two_id
                    : $friendship->member_one_id);
        }

        if ($communities && count($communities) > 0) {
            $items = $communities instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
                ? $communities->items()
                : $communities;
            Community::appendMembershipStatusToCollection($items, auth('member')->id());
        }

        return [
            'search' => $search,
            'type' => $type,
            'counts' => $counts,
            'members' => $members,
            'communities' => $communities,
            'pages' => $pages,
            'events' => $events,
            'posts' => $posts,
            'state' => $state,
            'unavailableTypes' => $unavailableTypes,
            'friendships' => $friendships,
        ];
    }

    private function eventQuery(string $search)
    {
        $currentMemberId = auth('member')->id();
        $query = Event::query()
            ->with(['organizer'])
            ->withCount(['responses as guests_count'])
            ->where('status', 'published')
            ->where(function ($q) use ($currentMemberId) {
                $q->where('privacy', 'public')
                    ->orWhere('privacy', 'friends_only')
                    ->orWhere('organizer_id', $currentMemberId);
            })
            ->notPassed();

        if ($search !== '') {
            $term = '%'.$search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('short_description', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('category', 'like', $term)
                    ->orWhere('event_type', 'like', $term)
                    ->orWhere('location_address', 'like', $term)
                    ->orWhere('location_city', 'like', $term)
                    ->orWhere('location_state', 'like', $term)
                    ->orWhere('location_country', 'like', $term)
                    ->orWhereHas('organizer', function ($ownerQuery) use ($term) {
                        $ownerQuery->where('name', 'like', $term)
                            ->orWhere('user_id', 'like', $term);
                    });
            });
        }

        return $query->latest('start_date');
    }

    private function pageQuery(string $search)
    {
        $query = BusinessPage::publicPages()
            ->with('owner');

        if ($search !== '') {
            $term = '%'.$search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('page_name', 'like', $term)
                    ->orWhere('page_username', 'like', $term)
                    ->orWhere('category', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('city', 'like', $term)
                    ->orWhere('state', 'like', $term)
                    ->orWhere('country', 'like', $term)
                    ->orWhereHas('owner', function ($ownerQuery) use ($term) {
                        $ownerQuery->where('name', 'like', $term);
                    });
            });
        }

        return $query->latest('created_at');
    }

    private function postQuery(string $search)
    {
        $query = Post::query()
            ->with(['member', 'likes', 'reactions'])
            ->withCount([
                'likes', 'reactions', 'shares', 'savedPosts',
                'comments' => fn ($cq) => $cq->whereNull('parent_id'),
            ])
            ->whereNull('group_id')
            ->whereNull('event_id');

        if ($search !== '') {
            $term = '%'.$search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('body', 'like', $term)
                    ->orWhereHas('member', function ($memberQuery) use ($term) {
                        $memberQuery->where('name', 'like', $term)
                            ->orWhere('user_id', 'like', $term);
                    });
            });
        }

        return $query->latest('created_at');
    }

    private function communityQuery(string $search)
    {
        $query = Community::query()
            ->with('owner')
            ->where('visibility', '!=', 'secret');

        if ($search !== '') {
            $term = '%'.$search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('category', 'like', $term)
                    ->orWhereHas('owner', function ($ownerQuery) use ($term) {
                        $ownerQuery->where('name', 'like', $term);
                    });
            });
        }

        return $query->latest('created_at');
    }

    private function memberQuery(string $search)
    {
        $normalizedUserIdSearch = $this->userIds->normalize($search);
        $lowerNameSearch = Str::lower($search);

        return Member::query()
            ->sociallyEligible()
            ->select(['id', 'name', 'user_id', 'profile_photo', 'bio', 'city', 'country', 'mobile_verified_at', 'created_at', 'updated_at'])
            ->where('id', '!=', auth('member')->id())
            ->where(function ($query) use ($search, $normalizedUserIdSearch) {
                $query
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('user_id', 'like', '%'.$normalizedUserIdSearch.'%');
            })
            ->orderByRaw(
                'CASE
                    WHEN LOWER(user_id) = ? THEN 1
                    WHEN LOWER(name) = ? THEN 2
                    WHEN LOWER(user_id) LIKE ? THEN 3
                    WHEN LOWER(name) LIKE ? THEN 4
                    ELSE 5
                END',
                [
                    $normalizedUserIdSearch,
                    $lowerNameSearch,
                    $normalizedUserIdSearch.'%',
                    $lowerNameSearch.'%',
                ],
            )
            ->orderBy('name');
    }
}

