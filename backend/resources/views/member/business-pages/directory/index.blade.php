@extends('member.layouts.app')

@section('title', 'Business Directory - Explore Companies & Services')

@section('content')
<div class="biz-page" style="padding: 32px; box-sizing: border-box;">
    <!-- Hero Directory Search Header -->
    <div class="biz-hero" style="padding: 40px 48px; border-radius: 20px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff; margin-bottom: 42px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;">
        <div style="max-width: 800px; width: 100%; margin: 0 auto; text-align: center; display: flex; flex-direction: column; align-items: center;">
            <span class="biz-badge biz-badge--category" style="background: rgba(79, 125, 243, 0.2); color: #60a5fa; border: 1px solid rgba(96, 165, 250, 0.3); margin-bottom: 18px; display: inline-flex; align-items: center; gap: 6px;">
                <i data-lucide="compass" style="width: 14px; height: 14px;"></i> Business Directory & Discovery
            </span>
            <h1 style="font-size: 32px; font-weight: 800; color: #fff; margin: 0 0 18px 0; line-height: 1.25;">Find & Connect with Verified Businesses</h1>
            <p style="font-size: 15px; color: #94a3b8; margin: 0 auto 34px; max-width: 700px; line-height: 1.6;">Discover top-rated enterprises, local services, startups, and professional network partners.</p>

            <!-- Search Panel -->
            <form method="GET" action="{{ route('member.business-pages.directory.index') }}" id="directorySearchForm" style="display: flex; flex-direction: column; background: rgba(255, 255, 255, 0.08); padding: 32px; border-radius: 16px; backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.15); width: 100%; box-sizing: border-box;">
                <!-- First Row: Search Input, Category Dropdown, Search Button -->
                <div style="display: flex; align-items: center; gap: 16px; width: 100%; box-sizing: border-box; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 0; max-width: none; height: 56px;">
                        <input type="text" name="q" value="{{ $filters['q'] }}" class="biz-search-input" style="height: 56px; line-height: 56px; padding: 0 18px; border-radius: 14px; background: #fff; color: #1d2738; font-size: 14.5px; width: 100%; box-sizing: border-box;" placeholder="Search business name, username, keyword, city...">
                    </div>
                    <div style="width: 260px; flex-shrink: 0; height: 56px;">
                        <select name="category" class="biz-filter-select" style="height: 56px; line-height: 56px; padding: 0 18px; border-radius: 14px; background: #fff; color: #1d2738; font-size: 14.5px; width: 100%; box-sizing: border-box;">
                            <option value="">All Categories</option>
                            @foreach ($categoriesList as $cat)
                                <option value="{{ $cat['name'] }}" {{ $filters['category'] === $cat['name'] ? 'selected' : '' }}>
                                    {{ $cat['name'] }} ({{ $cat['count'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="member-button member-button--primary" style="height: 56px; width: 210px; flex-shrink: 0; padding: 0 20px; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-size: 14.5px; font-weight: 600; box-sizing: border-box;">
                        <i data-lucide="search" style="width: 18px; height: 18px;"></i> Search Directory
                    </button>
                </div>

                <!-- Second Row: City, Country, Verified Checkbox, Sort Dropdown -->
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; font-size: 13.5px; width: 100%; margin-top: 18px; box-sizing: border-box;">
                    <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                        <input type="text" name="city" value="{{ $filters['city'] }}" placeholder="City..." class="biz-search-input" style="height: 44px; line-height: 44px; width: 130px; padding: 0 16px; border-radius: 12px; font-size: 13px; background: #fff; color: #1d2738; box-sizing: border-box;">
                        <input type="text" name="country" value="{{ $filters['country'] }}" placeholder="Country..." class="biz-search-input" style="height: 44px; line-height: 44px; width: 130px; padding: 0 16px; border-radius: 12px; font-size: 13px; background: #fff; color: #1d2738; box-sizing: border-box;">
                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; color: #cbd5e1; height: 44px; user-select: none;">
                            <input type="checkbox" name="verified_only" value="1" {{ $filters['verified_only'] ? 'checked' : '' }} onchange="this.form.submit()" style="width: 16px; height: 16px; margin: 0; cursor: pointer; accent-color: #20c875;">
                            <span style="display: inline-flex; align-items: center; gap: 4px;"><i data-lucide="badge-check" style="width: 16px; height: 16px; color: #20c875;"></i> Verified Only</span>
                        </label>
                    </div>

                    <div style="display: flex; align-items: center; gap: 10px; margin-left: auto;">
                        <span style="color: #94a3b8; font-weight: 500;">Sort By:</span>
                        <select name="sort" class="biz-filter-select" style="height: 44px; line-height: 44px; padding: 0 16px; border-radius: 12px; font-size: 13px; background: #fff; color: #1d2738; box-sizing: border-box;" onchange="this.form.submit()">
                            <option value="popular" {{ $filters['sort'] === 'popular' ? 'selected' : '' }}>Most Popular / Trending</option>
                            <option value="newest" {{ $filters['sort'] === 'newest' ? 'selected' : '' }}>Newest Created</option>
                            <option value="oldest" {{ $filters['sort'] === 'oldest' ? 'selected' : '' }}>Oldest Created</option>
                            <option value="alphabetical" {{ $filters['sort'] === 'alphabetical' ? 'selected' : '' }}>Alphabetical (A-Z)</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Featured Businesses Carousel Banner -->
    @if ($featuredPages->count() > 0)
        <div style="margin-bottom: 36px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                <h3 style="font-size: 18px; font-weight: 700; color: #1d2738; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="sparkles" style="color: #f7b940;"></i> Featured Businesses
                </h3>
                <span class="biz-badge biz-badge--verified" style="font-size: 11px;">Verified Partners</span>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px;">
                @foreach ($featuredPages as $fp)
                    @include('member.business-pages.partials.card', ['businessPage' => $fp])
                @endforeach
            </div>
        </div>
    @endif

    <!-- Categories Grid Section -->
    <div class="biz-info-card" style="padding: 32px; border-radius: 18px; background: #ffffff; border: 1px solid #e7ecf4; box-shadow: var(--shadow-sm, 0 2px 8px rgba(34, 49, 78, 0.035)); margin-bottom: 40px;">
        <h3 class="biz-info-card__title" style="margin-bottom: 24px; padding-bottom: 14px; border-bottom: 1px solid #e7ecf4; font-size: 17px; font-weight: 700; color: #1d2738; display: flex; align-items: center; gap: 8px;">
            <i data-lucide="grid" style="color: #4f7df3;"></i> Browse by Business Category
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 24px;">
            @foreach ($categoriesList as $cat)
                <a href="{{ route('member.business-pages.directory.category', $cat['slug']) }}" style="padding: 22px; border-radius: 16px; min-height: 110px; background: #f8fafc; border: 1px solid #e7ecf4; text-decoration: none; transition: all 0.2s ease; display: flex; flex-direction: column; justify-content: center; align-items: flex-start; box-sizing: border-box;" class="biz-category-tile">
                    <strong style="font-size: 14px; color: #1d2738; display: block; margin-bottom: 8px; margin-top: 0; font-weight: 700; line-height: 1.3;">{{ $cat['name'] }}</strong>
                    <span style="font-size: 12px; color: #687386; font-weight: 600; margin-top: 6px;">{{ $cat['count'] }} {{ \Illuminate\Support\Str::plural('Page', $cat['count']) }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Trending Businesses Showcase -->
    @if ($trendingPages->count() > 0 && empty($filters['q']) && empty($filters['category']))
        <div style="margin-bottom: 36px;">
            <h3 style="font-size: 18px; font-weight: 700; color: #1d2738; margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="trending-up" style="color: #8a2be2;"></i> Trending & Fast Growing Pages
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px;">
                @foreach ($trendingPages as $tp)
                    @include('member.business-pages.partials.card', ['businessPage' => $tp])
                @endforeach
            </div>
        </div>
    @endif

    <!-- All Business Directory Results Grid -->
    <div>
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
            <h3 style="font-size: 18px; font-weight: 700; color: #1d2738; margin: 0;">
                Business Directory Results ({{ $directoryPages->total() }})
            </h3>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px;" id="directoryResultsGrid">
            @forelse ($directoryPages as $businessPage)
                @include('member.business-pages.partials.card', compact('businessPage'))
            @empty
                <div class="biz-phase-placeholder" style="grid-column: 1 / -1; width: 100%; padding: 48px 24px;">
                    <div class="biz-phase-placeholder__icon"><i data-lucide="building-2" style="width: 28px; height: 28px;"></i></div>
                    <h3>No Business Pages Found</h3>
                    <p>No business pages match your selected search criteria or filters.</p>
                    <a href="{{ route('member.business-pages.directory.index') }}" class="member-button member-button--primary" style="margin-top: 14px; display: inline-block;">Clear Filters</a>
                </div>
            @endforelse
        </div>

        @if ($directoryPages->hasPages())
            <div class="pagination-wrapper" style="margin-top: 32px;">
                {{ $directoryPages->links('member.search.pagination') }}
            </div>
        @endif
    </div>
</div>
@endsection
