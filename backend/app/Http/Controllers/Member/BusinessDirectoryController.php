<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\BusinessPage;
use Illuminate\Http\Request;

class BusinessDirectoryController extends Controller
{
    public function directory(Request $request)
    {
        $member = auth('member')->user();

        $filters = [
            'q' => trim($request->query('q', '')),
            'category' => trim($request->query('category', '')),
            'country' => trim($request->query('country', '')),
            'state' => trim($request->query('state', '')),
            'city' => trim($request->query('city', '')),
            'sort' => trim($request->query('sort', 'popular')),
            'verified_only' => $request->boolean('verified_only'),
        ];

        $featuredPages = BusinessPage::publicPages()
            ->with('owner')
            ->featured()
            ->latest()
            ->take(4)
            ->get();

        $trendingPages = BusinessPage::publicPages()
            ->with('owner')
            ->trending()
            ->take(6)
            ->get();

        $recommendedPages = BusinessPage::publicPages()
            ->with('owner')
            ->where('is_verified', true)
            ->latest()
            ->take(4)
            ->get();

        // Count pages per category
        $categoryCounts = BusinessPage::publicPages()
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        $categoriesList = [];
        $activeCategories = \App\Models\BusinessPageCategory::getActiveCategories();
        foreach ($activeCategories as $cat) {
            $categoriesList[] = [
                'name' => $cat->name,
                'slug' => $cat->slug,
                'count' => $categoryCounts[$cat->name] ?? 0,
            ];
        }

        $directoryPages = BusinessPage::publicPages()
            ->with('owner')
            ->searchFilter($filters)
            ->paginate(12)
            ->withQueryString();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'directory_pages' => $directoryPages,
                'featured_pages' => $featuredPages,
                'trending_pages' => $trendingPages,
                'recommended_pages' => $recommendedPages,
                'categories_list' => $categoriesList,
                'filters' => $filters,
            ]);
        }

        return view('member.business-pages.directory.index', compact(
            'directoryPages',
            'featuredPages',
            'trendingPages',
            'recommendedPages',
            'categoriesList',
            'filters'
        ));
    }

    public function category(Request $request, string $category)
    {
        $categoryName = BusinessPage::findCategoryBySlug($category);

        if (! $categoryName) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Business Category Not Found.'], 404);
            }
            abort(404, 'Business Category Not Found.');
        }

        $filters = [
            'q' => trim($request->query('q', '')),
            'category' => $categoryName,
            'country' => trim($request->query('country', '')),
            'state' => trim($request->query('state', '')),
            'city' => trim($request->query('city', '')),
            'sort' => trim($request->query('sort', 'popular')),
            'verified_only' => $request->boolean('verified_only'),
        ];

        $pages = BusinessPage::publicPages()
            ->with('owner')
            ->searchFilter($filters)
            ->paginate(12)
            ->withQueryString();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'category_name' => $categoryName,
                'pages' => $pages,
                'filters' => $filters,
            ]);
        }

        return view('member.business-pages.directory.category', compact(
            'categoryName',
            'pages',
            'filters'
        ));
    }

    public function search(Request $request)
    {
        $filters = [
            'q' => trim($request->query('q', '')),
            'category' => trim($request->query('category', '')),
            'country' => trim($request->query('country', '')),
            'state' => trim($request->query('state', '')),
            'city' => trim($request->query('city', '')),
            'sort' => trim($request->query('sort', 'popular')),
            'verified_only' => $request->boolean('verified_only'),
        ];

        $pages = BusinessPage::publicPages()
            ->with('owner')
            ->searchFilter($filters)
            ->paginate(12);

        return response()->json([
            'success' => true,
            'total' => $pages->total(),
            'current_page' => $pages->currentPage(),
            'last_page' => $pages->lastPage(),
            'data' => $pages->items(),
        ]);
    }
}
