@extends('admin.layouts.master')
@section('title', 'Admin Overview')
@section('page-subtitle', 'Comprehensive platform analytics, activity monitoring, and system metrics.')

@section('content')
<!-- SECTION 17: QUICK ACTIONS BAR -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body py-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span class="fw-bold text-dark small text-uppercase" style="letter-spacing: 0.05em;">
                <i data-feather="zap" class="text-primary me-1" style="width: 16px; height: 16px;"></i> Quick Navigation:
            </span>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.deposits.index') }}" class="btn btn-sm btn-outline-success fw-bold">
                    <i data-feather="dollar-sign" style="width: 14px; height: 14px;"></i> Deposit Requests
                </a>
                <a href="{{ route('admin.members.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i data-feather="users" style="width: 14px; height: 14px;"></i> Members
                </a>
                <a href="{{ route('admin.posts.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i data-feather="file-text" style="width: 14px; height: 14px;"></i> Posts
                </a>
                <a href="{{ route('admin.stories.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i data-feather="clock" style="width: 14px; height: 14px;"></i> Stories
                </a>
                <a href="{{ route('admin.business-pages.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i data-feather="briefcase" style="width: 14px; height: 14px;"></i> Business Pages
                </a>
                <a href="{{ route('admin.communities.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i data-feather="globe" style="width: 14px; height: 14px;"></i> Communities
                </a>
                <a href="{{ route('admin.marketplace.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i data-feather="shopping-bag" style="width: 14px; height: 14px;"></i> Marketplace
                </a>
                <a href="{{ route('admin.events.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i data-feather="calendar" style="width: 14px; height: 14px;"></i> Events
                </a>
                <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i data-feather="alert-circle" style="width: 14px; height: 14px;"></i> Reports
                    @if($pendingReports > 0)
                        <span class="badge bg-danger rounded-pill ms-1" style="font-size: 10px;">{{ $pendingReports }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.analytics.index') }}" class="btn btn-sm btn-outline-primary">
                    <i data-feather="bar-chart-2" style="width: 14px; height: 14px;"></i> Full Analytics BI
                </a>
            </div>
        </div>
    </div>
</div>

<!-- SECTION 1: TOP 8 KPI METRIC CARDS -->
<div class="row g-3 mb-4">
    <!-- 1. Total Members -->
    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-12">
        <x-admin.stat-card 
            title="Total Members" 
            :value="number_format($totalMembers)" 
            icon="users" 
            variant="primary" 
            subtext="Registered Platform Accounts" 
        />
    </div>

    <!-- 2. New Members Today -->
    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-12">
        <x-admin.stat-card 
            title="New Members Today" 
            :value="number_format($newMembersToday)" 
            icon="user-plus" 
            variant="success" 
            subtext="Joined within last 24h" 
        />
    </div>

    <!-- 3. New Members This Week -->
    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-12">
        <x-admin.stat-card 
            title="New This Week" 
            :value="number_format($newMembersThisWeek)" 
            icon="calendar" 
            variant="info" 
            subtext="Current week registrations" 
        />
    </div>

    <!-- 4. New Members This Month -->
    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-12">
        <x-admin.stat-card 
            title="New This Month" 
            :value="number_format($newMembersThisMonth)" 
            icon="trending-up" 
            variant="primary" 
            subtext="Current month onboarding" 
        />
    </div>

    <!-- 5. Active Members -->
    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-12">
        <x-admin.stat-card 
            title="Active Members" 
            :value="number_format($activeMembers)" 
            icon="activity" 
            variant="success" 
            subtext="Active in past 7 days" 
        />
    </div>

    <!-- 6. Inactive Members -->
    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-12">
        <x-admin.stat-card 
            title="Inactive Members" 
            :value="number_format($inactiveMembers)" 
            icon="user-x" 
            variant="secondary" 
            subtext="No activity in 7+ days" 
        />
    </div>

    <!-- 7. Pending Connection Requests -->
    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-12">
        <x-admin.stat-card 
            title="Pending Requests" 
            :value="number_format($pendingConnections)" 
            icon="user-check" 
            variant="warning" 
            subtext="Awaiting user acceptance" 
        />
    </div>

    <!-- 8. Total Connections -->
    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-12">
        <x-admin.stat-card 
            title="Total Connections" 
            :value="number_format($totalConnections)" 
            icon="share-2" 
            variant="info" 
            subtext="Established friend connections" 
        />
    </div>
</div>

<!-- SECTION: CRYPTO DEPOSITS & MANUAL REQUESTS OVERVIEW -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-left: 4px solid #10B981 !important;">
            <div class="card-body p-3 p-md-4">
                <div class="row align-items-center">
                    <div class="col-lg-4 col-md-12 mb-3 mb-lg-0">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle p-3 bg-success bg-opacity-10 text-success me-3">
                                <i data-feather="dollar-sign" style="width: 28px; height: 28px;"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1 text-dark">Deposit Requests Management</h5>
                                <p class="text-muted small mb-0">BEP-20 USDT On-Chain Verification &amp; Fund Wallet Credits</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-7 mb-3 mb-md-0">
                        <div class="row text-center text-sm-start">
                            <div class="col-4 border-end">
                                <span class="text-muted d-block small">Pending Requests</span>
                                <h4 class="fw-bold mb-0 {{ $pendingDepositsCount > 0 ? 'text-warning' : 'text-dark' }}">
                                    {{ $pendingDepositsCount }}
                                    @if($pendingDepositsCount > 0)
                                        <span class="badge bg-warning text-dark rounded-pill" style="font-size: 10px;">Action Req.</span>
                                    @endif
                                </h4>
                            </div>
                            <div class="col-4 border-end">
                                <span class="text-muted d-block small">Approved Total</span>
                                <h4 class="fw-bold mb-0 text-success">${{ number_format($totalDepositedAmount, 2) }}</h4>
                            </div>
                            <div class="col-4">
                                <span class="text-muted d-block small">Approved Count</span>
                                <h4 class="fw-bold mb-0 text-primary">{{ $approvedDepositsCount }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-5 text-md-end">
                        <div class="d-flex gap-2 justify-content-lg-end">
                            <a href="{{ route('admin.deposits.index') }}" class="btn btn-primary fw-semibold btn-sm">
                                <i data-feather="list" class="me-1" style="width: 14px; height: 14px;"></i> View Requests
                            </a>
                            <a href="{{ route('admin.deposits.settings') }}" class="btn btn-outline-secondary btn-sm" title="Deposit Settings">
                                <i data-feather="settings" style="width: 14px; height: 14px;"></i> Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SECTIONS 2, 3, 4, 5, 6, 7: MODULE SUMMARY GRIDS -->
<div class="row g-3 mb-4">
    <!-- SECTION 2: Content Overview -->
    <div class="col-xl-4 col-md-6 col-12">
        <div class="card h-100 mb-0 shadow-sm border-0">
            <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between">
                <h6 class="card-title fw-bold mb-0">
                    <i data-feather="file-text" class="text-primary me-2" style="width: 18px; height: 18px;"></i>Content Overview
                </h6>
                <a href="{{ route('admin.posts.index') }}" class="btn btn-sm btn-ghost p-0 text-primary small">View All</a>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Total Posts</span>
                    <strong class="text-dark">{{ number_format($totalPosts) }}</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Posts Created Today</span>
                    <span class="badge bg-light-primary text-primary fw-bold">{{ number_format($postsToday) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Posts This Week</span>
                    <span class="badge bg-light-info text-info fw-bold">{{ number_format($postsThisWeek) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Active Live Stories</span>
                    <span class="badge bg-light-success text-success fw-bold">{{ number_format($activeStories) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Stories Posted Today</span>
                    <span class="text-dark">{{ number_format($storiesToday) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-2">
                    <span class="text-muted small">Media & Image Posts</span>
                    <strong class="text-dark">{{ number_format($mediaPosts) }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 3 & 4: Business Pages & Communities -->
    <div class="col-xl-4 col-md-6 col-12">
        <div class="card h-100 mb-0 shadow-sm border-0">
            <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between">
                <h6 class="card-title fw-bold mb-0">
                    <i data-feather="briefcase" class="text-info me-2" style="width: 18px; height: 18px;"></i>Business & Communities
                </h6>
                <a href="{{ route('admin.business-pages.index') }}" class="btn btn-sm btn-ghost p-0 text-primary small">Manage</a>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Total Business Pages</span>
                    <strong class="text-dark">{{ number_format($totalBusinessPages) }}</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Verified Business Pages</span>
                    <span class="badge bg-light-success text-success fw-bold">{{ number_format($verifiedBusinessPages) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Pending Verification Pages</span>
                    <span class="badge bg-light-warning text-warning fw-bold">{{ number_format($pendingBusinessPages) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Total Communities</span>
                    <strong class="text-dark">{{ number_format($totalCommunities) }}</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Community Memberships</span>
                    <span class="badge bg-light-primary text-primary fw-bold">{{ number_format($totalCommunityMembers) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-2">
                    <span class="text-muted small">Pending Join Requests</span>
                    <span class="badge bg-light-secondary text-secondary fw-bold">{{ number_format($pendingCommunityRequests) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 5, 6, 7: Marketplace, Events & Moderation -->
    <div class="col-xl-4 col-md-12 col-12">
        <div class="card h-100 mb-0 shadow-sm border-0">
            <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between">
                <h6 class="card-title fw-bold mb-0">
                    <i data-feather="shield" class="text-warning me-2" style="width: 18px; height: 18px;"></i>Marketplace, Events & Trust
                </h6>
                <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-ghost p-0 text-primary small">Reports</a>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Marketplace Listings</span>
                    <strong class="text-dark">{{ number_format($totalProducts) }}</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Active Products</span>
                    <span class="badge bg-light-success text-success fw-bold">{{ number_format($activeProducts) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Upcoming Events</span>
                    <span class="badge bg-light-info text-info fw-bold">{{ number_format($upcomingEventsCount) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Events This Month</span>
                    <span class="badge bg-light-primary text-primary fw-bold">{{ number_format($eventsThisMonthCount) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Pending Content Reports</span>
                    @if($pendingReports > 0)
                        <span class="badge bg-danger rounded-pill fw-bold">{{ number_format($pendingReports) }}</span>
                    @else
                        <span class="badge bg-light-success text-success">0 Clean</span>
                    @endif
                </div>
                <div class="d-flex justify-content-between align-items-center pt-2">
                    <span class="text-muted small">Blocked Members</span>
                    <span class="text-muted">{{ number_format($blockedMembers) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SECTION 9 & 10: INTERACTIVE PLATFORM ANALYTICS & BUSINESS CATEGORIES -->
<div class="row g-4 mb-4">
    <!-- Member Registration Trend Chart -->
    <div class="col-xl-8 col-lg-12">
        <div class="card h-100 mb-0 shadow-sm border-0">
            <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="card-title mb-0 fw-semibold">Member Registration Trend</h5>
                    <p class="text-muted small mb-0 mt-1">Real-time daily account onboarding over past 14 days</p>
                </div>
                <a href="{{ route('admin.analytics.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i data-feather="bar-chart" style="width: 14px; height: 14px;"></i> Full Analytics
                </a>
            </div>
            <div class="card-body">
                <div id="memberTrendChart" style="min-height: 280px;"></div>
            </div>
        </div>
    </div>

    <!-- SECTION 10: Business Pages by MLM Category -->
    <div class="col-xl-4 col-lg-12">
        <div class="card h-100 mb-0 shadow-sm border-0">
            <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="card-title mb-0 fw-semibold">MLM Business Categories</h5>
                    <p class="text-muted small mb-0 mt-1">Directory distribution by industry</p>
                </div>
                <a href="{{ route('admin.business-pages.index') }}" class="btn btn-sm btn-ghost p-0 text-primary small">All Pages</a>
            </div>
            <div class="card-body">
                @if($businessCategories->isEmpty())
                    <x-admin.empty-state 
                        icon="briefcase" 
                        title="No Categories Yet" 
                        description="Category distribution metrics will display here as business pages are published." 
                    />
                @else
                    <div class="d-flex flex-column gap-3">
                        @foreach($businessCategories as $bc)
                            @php
                                $pct = $totalBusinessPages > 0 ? round(($bc->count / $totalBusinessPages) * 100) : 0;
                            @endphp
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-semibold text-dark small">{{ $bc->category }}</span>
                                    <span class="text-muted small">{{ $bc->count }} pages ({{ $pct }}%)</span>
                                </div>
                                <div class="progress" style="height: 6px; border-radius: 4px; background-color: #f1f5f9;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $pct }}%; border-radius: 4px;" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- SECTION 11, 12, 13, 14: RECENT PLATFORM RECORDS TABLES -->
<div class="row g-4 mb-4">
    <!-- SECTION 11: Recent Members -->
    <div class="col-xl-6 col-12">
        <div class="card h-100 mb-0 shadow-sm border-0">
            <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between">
                <h6 class="card-title fw-bold mb-0">
                    <i data-feather="user-plus" class="text-primary me-2" style="width: 18px; height: 18px;"></i>Recently Registered Members
                </h6>
                <a href="{{ route('admin.members.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                @if($recentMembers->isEmpty())
                    <div class="p-4 text-center text-muted small">No members registered yet.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Member</th>
                                    <th>User ID</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentMembers as $rm)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="{{ $rm->avatar_url }}" alt="{{ $rm->name }}" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                                                <div>
                                                    <div class="fw-semibold text-dark small">{{ $rm->name }}</div>
                                                    <small class="text-muted" style="font-size: 11px;">{{ $rm->city ?: 'Worldwide' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><code class="text-primary small">{{ $rm->user_id }}</code></td>
                                        <td>
                                            @if($rm->mobile_verified_at)
                                                <span class="badge bg-light-success text-success" style="font-size: 10px;">Verified</span>
                                            @else
                                                <span class="badge bg-light-warning text-warning" style="font-size: 10px;">Pending</span>
                                            @endif
                                        </td>
                                        <td><small class="text-muted">{{ $rm->created_at?->diffForHumans() }}</small></td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.members.show', $rm) }}" class="btn btn-sm btn-icon btn-outline-primary" title="Inspect Profile">
                                                <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- SECTION 12: Recent Business Pages -->
    <div class="col-xl-6 col-12">
        <div class="card h-100 mb-0 shadow-sm border-0">
            <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between">
                <h6 class="card-title fw-bold mb-0">
                    <i data-feather="briefcase" class="text-info me-2" style="width: 18px; height: 18px;"></i>Recent Business Pages
                </h6>
                <a href="{{ route('admin.business-pages.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                @if($recentBusinessPages->isEmpty())
                    <div class="p-4 text-center text-muted small">No business pages created yet.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Page Name</th>
                                    <th>Category</th>
                                    <th>Verification</th>
                                    <th>Created</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentBusinessPages as $rbp)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark small">{{ $rbp->page_name }}</div>
                                        </td>
                                        <td><span class="badge bg-light-primary text-primary" style="font-size: 11px;">{{ $rbp->category ?: 'General' }}</span></td>
                                        <td>
                                            @if($rbp->is_verified)
                                                <span class="badge bg-light-success text-success" style="font-size: 10px;">Verified</span>
                                            @else
                                                <span class="badge bg-light-secondary text-secondary" style="font-size: 10px;">Unverified</span>
                                            @endif
                                        </td>
                                        <td><small class="text-muted">{{ $rbp->created_at?->diffForHumans() }}</small></td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.business-pages.show', $rbp->id) }}" class="btn btn-sm btn-icon btn-outline-primary" title="View Details">
                                                <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- SECTION 13 & 14 & 16: RECENT POSTS, UPCOMING EVENTS, ADMIN ACTIVITY -->
<div class="row g-4">
    <!-- SECTION 13: Recent Posts Feed -->
    <div class="col-xl-6 col-12">
        <div class="card h-100 mb-0 shadow-sm border-0">
            <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between">
                <h6 class="card-title fw-bold mb-0">
                    <i data-feather="message-square" class="text-primary me-2" style="width: 18px; height: 18px;"></i>Recent Community Posts
                </h6>
                <a href="{{ route('admin.posts.index') }}" class="btn btn-sm btn-outline-primary">All Posts</a>
            </div>
            <div class="card-body">
                @if($recentPosts->isEmpty())
                    <x-admin.empty-state icon="file-text" title="No Posts Published" description="Platform posts will appear here as members publish updates." />
                @else
                    <div class="d-flex flex-column gap-3">
                        @foreach($recentPosts as $post)
                            <div class="d-flex align-items-start gap-3 p-2 rounded hover-bg" style="transition: background 0.2s;">
                                <img src="{{ $post->member?->avatar_url ?? asset('admin_assets/images/avtar/7.jpg') }}" alt="Author" class="rounded-circle mt-1" style="width: 36px; height: 36px; object-fit: cover;">
                                <div class="flex-grow-1 min-width-0">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <strong class="text-dark small">{{ $post->member?->name ?? 'System Member' }}</strong>
                                        <small class="text-muted" style="font-size: 11px;">{{ $post->created_at?->diffForHumans() }}</small>
                                    </div>
                                    <p class="text-muted small mb-1 text-truncate" style="max-width: 420px;">
                                        {{ $post->body ?: '[Media Attachment / Story]' }}
                                    </p>
                                    @if($post->media_type)
                                        <span class="badge bg-light-info text-info" style="font-size: 10px;">
                                            <i data-feather="image" style="width: 10px; height: 10px;"></i> {{ ucfirst($post->media_type) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- SECTION 14 & 16: Upcoming Events & Admin Action Logs -->
    <div class="col-xl-6 col-12">
        <div class="d-flex flex-column gap-4">
            <!-- SECTION 14: Upcoming Events Schedule -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between">
                    <h6 class="card-title fw-bold mb-0">
                        <i data-feather="calendar" class="text-info me-2" style="width: 18px; height: 18px;"></i>Upcoming Platform Events
                    </h6>
                    <a href="{{ route('admin.events.index') }}" class="btn btn-sm btn-ghost p-0 text-primary small">All Events</a>
                </div>
                <div class="card-body">
                    @if($upcomingEvents->isEmpty())
                        <div class="py-3 text-center text-muted">
                            <i data-feather="calendar" class="mb-2 text-muted" style="width: 28px; height: 28px;"></i>
                            <p class="mb-0 small">No upcoming events scheduled at this moment.</p>
                        </div>
                    @else
                        <div class="d-flex flex-column gap-2">
                            @foreach($upcomingEvents as $evt)
                                <div class="d-flex justify-content-between align-items-center p-2 rounded bg-light">
                                    <div>
                                        <strong class="text-dark small d-block">{{ $evt->title }}</strong>
                                        <small class="text-muted">{{ $evt->location_city ?: 'Online' }} • {{ $evt->start_time ?: 'All Day' }}</small>
                                    </div>
                                    <span class="badge bg-primary">{{ \Carbon\Carbon::parse($evt->start_date)->format('M d') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- SECTION 16: Recent Admin Activity / System Status -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between">
                    <h6 class="card-title fw-bold mb-0">
                        <i data-feather="activity" class="text-success me-2" style="width: 18px; height: 18px;"></i>System Status & Logs
                    </h6>
                    <span class="badge bg-light-success text-success">Live Operational</span>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                        <span class="text-muted small">PHP Runtime Environment</span>
                        <strong class="text-dark small">PHP {{ PHP_VERSION }}</strong>
                    </div>
                    <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                        <span class="text-muted small">Laravel Framework</span>
                        <strong class="text-dark small">Laravel {{ app()->version() }}</strong>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-2">
                        <span class="text-muted small">System Notifications Stream</span>
                        <strong class="text-dark small">{{ $totalNotifications }} Delivered</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('admin-scripts')
<script src="{{ asset('admin_assets/js/chart/apex-chart/apex-chart.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Render 14-day Real Member Registration Area Chart
        const trendDates = @json($trendDates);
        const memberCounts = @json($memberRegTrend);
        const postCounts = @json($postsTrend);

        const options = {
            series: [{
                name: 'New Members',
                data: memberCounts
            }, {
                name: 'Posts Published',
                data: postCounts
            }],
            chart: {
                height: 280,
                type: 'area',
                toolbar: {
                    show: false
                },
                zoom: {
                    enabled: false
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            colors: ['#176bff', '#10b981'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.45,
                    opacityTo: 0.05,
                    stops: [0, 95, 100]
                }
            },
            xaxis: {
                categories: trendDates,
                labels: {
                    style: {
                        colors: '#64748b',
                        fontSize: '12px'
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: '#64748b',
                        fontSize: '12px'
                    },
                    formatter: function (val) {
                        return Math.floor(val);
                    }
                },
                min: 0
            },
            tooltip: {
                x: {
                    format: 'dd/MM'
                }
            },
            grid: {
                borderColor: '#f1f5f9',
                strokeDashArray: 3
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right'
            }
        };

        const chartElement = document.querySelector("#memberTrendChart");
        if (chartElement && typeof ApexCharts !== 'undefined') {
            const chart = new ApexCharts(chartElement, options);
            chart.render();
        }
    });
</script>
@endpush
