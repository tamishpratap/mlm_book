@extends('member.layouts.app')

@section('title', $categoryName . ' Businesses - Directory')

@section('content')
<div class="biz-page">
    <!-- Category Hero Banner -->
    <div class="biz-hero" style="padding: 24px; border-radius: 18px; background: linear-gradient(135deg, #4f7df3 0%, #8a2be2 100%); color: #fff; margin-bottom: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <a href="{{ route('member.business-pages.directory.index') }}" style="color: rgba(255,255,255,0.8); font-size: 13px; text-decoration: underline; display: inline-flex; align-items: center; gap: 4px; margin-bottom: 8px;">
                    <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Directory
                </a>
                <h1 style="font-size: 28px; font-weight: 800; color: #fff; margin: 0;">{{ $categoryName }} Businesses</h1>
                <p style="font-size: 14px; color: rgba(255,255,255,0.85); margin: 6px 0 0 0;">Explore top verified companies and services in {{ $categoryName }}.</p>
            </div>

            <span class="biz-badge" style="background: rgba(255, 255, 255, 0.2); color: #fff; font-size: 14px; padding: 8px 16px;">
                {{ $pages->total() }} Businesses
            </span>
        </div>
    </div>

    <!-- Filter & Sort Bar -->
    <div class="biz-info-card" style="padding: 16px; margin-bottom: 20px;">
        <form method="GET" action="{{ route('member.business-pages.directory.category', \App\Models\BusinessPage::categorySlug($categoryName)) }}" style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <input type="text" name="q" value="{{ $filters['q'] }}" class="biz-search-input" placeholder="Search within {{ $categoryName }}...">
            </div>

            <div style="display: flex; align-items: center; gap: 10px;">
                <select name="sort" class="biz-filter-select" onchange="this.form.submit()">
                    <option value="popular" {{ $filters['sort'] === 'popular' ? 'selected' : '' }}>Most Popular</option>
                    <option value="newest" {{ $filters['sort'] === 'newest' ? 'selected' : '' }}>Newest First</option>
                    <option value="alphabetical" {{ $filters['sort'] === 'alphabetical' ? 'selected' : '' }}>Alphabetical (A-Z)</option>
                </select>
                <button type="submit" class="member-button member-button--primary" style="padding: 8px 16px;">Filter</button>
            </div>
        </form>
    </div>

    <!-- Category Pages Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
        @forelse ($pages as $businessPage)
            @include('member.business-pages.partials.card', compact('businessPage'))
        @empty
            <div class="biz-phase-placeholder" style="grid-column: 1 / -1; width: 100%;">
                <div class="biz-phase-placeholder__icon"><i data-lucide="tag" style="width: 28px; height: 28px;"></i></div>
                <h3>No Businesses Found in {{ $categoryName }}</h3>
                <p>Be the first member to create a business page in this category!</p>
                <a href="{{ route('member.business-pages.create') }}" class="member-button member-button--primary" style="margin-top: 10px; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                    <i data-lucide="plus-circle" aria-hidden="true"></i> <span>Create Business Page</span>
                </a>
            </div>
        @endforelse
    </div>

    @if ($pages->hasPages())
        <div class="pagination-wrapper" style="margin-top: 24px;">
            {{ $pages->links('member.search.pagination') }}
        </div>
    @endif
</div>
@endsection
