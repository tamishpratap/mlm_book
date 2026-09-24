<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Community;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommunityDiscoveryController extends Controller
{
    public function discover(Request $request)
    {
        $member = auth('member')->user();

        $search = $request->query('search');
        $category = $request->query('category');
        $sort = $request->query('sort', 'trending');
        $visibility = $request->query('visibility', 'all');

        // Featured Communities
        $featuredCommunities = Community::query()
            ->with('owner')
            ->featured()
            ->latest('created_at')
            ->take(6)
            ->get();

        // Trending Communities
        $trendingCommunities = Community::query()
            ->with('owner')
            ->trending()
            ->take(6)
            ->get();

        // Recommended Communities
        $suggestedCommunities = Community::query()
            ->with('owner')
            ->recommendedFor($member)
            ->latest('created_at')
            ->take(6)
            ->get();

        // Category Counts Mapping
        $categoriesWithCount = DB::table('communities')
            ->whereNull('deleted_at')
            ->where('visibility', '!=', 'secret')
            ->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        // Main Search & Exploration Query
        $query = Community::query()
            ->with('owner')
            ->where('visibility', '!=', 'secret');

        if (! empty($search)) {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('category', 'like', $term)
                    ->orWhere('tags', 'like', $term);
            });
        }

        if (! empty($category)) {
            $query->where('category', $category);
        }

        if ($visibility === 'public') {
            $query->where('visibility', 'public');
        } elseif ($visibility === 'private') {
            $query->whereIn('visibility', ['private', 'invite_only']);
        }

        if ($sort === 'popular') {
            $query->orderBy('member_count', 'desc');
        } elseif ($sort === 'newest') {
            $query->latest('created_at');
        } elseif ($sort === 'oldest') {
            $query->oldest('created_at');
        } elseif ($sort === 'featured') {
            $query->where('is_featured', true)->latest('created_at');
        } else {
            $query->orderByRaw('(member_count + (post_count * 3)) DESC');
        }

        $communities = $query->paginate(12)->withQueryString();

        // Efficiently append membership status attributes to all collections in a single query
        $allDiscoveryCommunities = array_merge(
            $featuredCommunities->all(),
            $trendingCommunities->all(),
            $suggestedCommunities->all(),
            $communities->items()
        );
        Community::appendMembershipStatusToCollection($allDiscoveryCommunities, $member?->id);

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'communities' => $communities,
                'featured_communities' => $featuredCommunities,
                'trending_communities' => $trendingCommunities,
                'suggested_communities' => $suggestedCommunities,
                'categories_with_count' => $categoriesWithCount,
                'total' => $communities->total(),
            ]);
        }

        return view('member.community.discover', compact(
            'communities',
            'featuredCommunities',
            'trendingCommunities',
            'suggestedCommunities',
            'categoriesWithCount',
            'search',
            'category',
            'sort',
            'visibility'
        ));
    }

    public function searchAjax(Request $request)
    {
        $queryText = trim($request->query('q', ''));

        if (strlen($queryText) < 2) {
            return response()->json(['results' => []]);
        }

        $term = '%' . $queryText . '%';

        $results = Community::query()
            ->where('visibility', '!=', 'secret')
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('category', 'like', $term)
                    ->orWhere('description', 'like', $term);
            })
            ->latest('member_count')
            ->take(8)
            ->get()
            ->map(function ($com) {
                $hasLogo = $com->logo && file_exists(public_path($com->logo));
                return [
                    'id' => $com->id,
                    'name' => $com->name,
                    'slug' => $com->slug,
                    'category' => $com->category,
                    'member_count' => number_format($com->member_count),
                    'visibility' => ucfirst($com->visibility),
                    'logo_url' => $hasLogo ? asset($com->logo) : null,
                    'url' => route('member.community.show', $com),
                ];
            });

        return response()->json([
            'results' => $results,
        ]);
    }
}
