@extends('admin.layouts.master')
@section('title', 'Analytics & Business Intelligence Dashboard')
@section('page-subtitle', 'Real-time metrics, growth trends, engagement analytics, and directory performance.')

@section('content')
<!-- Date Filter & Actions Bar -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form action="{{ route('admin.analytics.index') }}" method="GET" class="row g-3 align-items-center">
            <div class="col-md-3">
                <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="7days" {{ $period === '7days' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="30days" {{ $period === '30days' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="year" {{ $period === 'year' ? 'selected' : '' }}>Past Year</option>
                </select>
            </div>

            <div class="col-md-3">
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm" placeholder="From Date">
            </div>

            <div class="col-md-3">
                <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm" placeholder="To Date">
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fa fa-filter me-1"></i> Apply Filter</button>
                <a href="{{ route('admin.analytics.export') }}" class="btn btn-sm btn-outline-primary" title="Export BI Metrics CSV"><i class="fa fa-download"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Executive Metric Stat Cards Grid -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Total Members" 
            :value="$totalMembers" 
            icon="users" 
            variant="primary" 
            :subtext="$newMembersCount . ' new in period'" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Posts Published" 
            :value="$totalPosts" 
            icon="file-text" 
            variant="success" 
            :subtext="$periodPostsCount . ' posts in period'" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Business Pages" 
            :value="$totalBusinessPages" 
            icon="briefcase" 
            variant="info" 
            :subtext="$verifiedBusinessPagesCount . ' verified pages'" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Marketplace Listings" 
            :value="$totalProducts" 
            icon="shopping-bag" 
            variant="warning" 
            :subtext="$activeProductsCount . ' active listings'" 
        />
    </div>
</div>

<!-- Secondary Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="p-3 bg-white border rounded text-center">
            <small class="text-muted text-uppercase d-block" style="font-size: 11px;">Communities</small>
            <h4 class="fw-bold text-dark mb-0">{{ $totalCommunities }}</h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 bg-white border rounded text-center">
            <small class="text-muted text-uppercase d-block" style="font-size: 11px;">Events Scheduled</small>
            <h4 class="fw-bold text-dark mb-0">{{ $totalEvents }}</h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 bg-white border rounded text-center">
            <small class="text-muted text-uppercase d-block" style="font-size: 11px;">Platform Reports</small>
            <h4 class="fw-bold text-danger mb-0">{{ $totalReports }}</h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 bg-white border rounded text-center">
            <small class="text-muted text-uppercase d-block" style="font-size: 11px;">Notifications Delivered</small>
            <h4 class="fw-bold text-primary mb-0">{{ number_format($totalNotifications) }}</h4>
        </div>
    </div>
</div>

<!-- Tabbed Analytics Modules -->
<x-admin.card>
    <x-admin.tabs id="analyticsTabs" :tabs="[
        'growth' => 'User Growth & Demographics',
        'content' => 'Content & Engagement',
        'directory' => 'Directory & Commerce',
        'events' => 'Events & Moderation'
    ]">
        <!-- Growth Pane -->
        <div class="tab-pane fade show active" id="growth-pane" role="tabpanel" aria-labelledby="growth-tab">
            <div class="row g-4">
                <div class="col-md-6">
                    <h6 class="fw-bold text-primary mb-3">Member Demographics</h6>
                    <table class="table table-sm table-borderless bg-light p-3 rounded">
                        <tr>
                            <td class="text-muted fw-bold">Total Members:</td>
                            <td class="fw-bold text-dark">{{ number_format($totalMembers) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Verified Members:</td>
                            <td><span class="badge bg-success">{{ number_format($verifiedMembersCount) }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Gender Distribution:</td>
                            <td>
                                @foreach($genderDistribution as $g => $cnt)
                                    <span class="badge bg-light-primary text-primary me-1">{{ ucfirst($g ?: 'Unspecified') }}: {{ $cnt }}</span>
                                @endforeach
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="col-md-6">
                    <h6 class="fw-bold text-primary mb-3">Top Active Creators</h6>
                    <x-admin.table :headers="['Member', 'User ID', 'Posts Published']" :empty="$topCreators->isEmpty()" emptyMessage="No creator statistics available.">
                        @foreach($topCreators as $creator)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img class="rounded-circle" src="{{ $creator->avatar_url }}" style="width: 28px; height: 28px; object-fit: cover;">
                                        <span class="fw-bold text-dark small">{{ $creator->name }}</span>
                                    </div>
                                </td>
                                <td><code>{{ $creator->user_id }}</code></td>
                                <td><span class="badge bg-primary">{{ $creator->posts_count }}</span></td>
                            </tr>
                        @endforeach
                    </x-admin.table>
                </div>
            </div>
        </div>

        <!-- Content Pane -->
        <div class="tab-pane fade" id="content-pane" role="tabpanel" aria-labelledby="content-tab">
            <div class="row g-3 text-center mb-4">
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted text-uppercase" style="font-size: 11px;">Total Likes</small>
                        <h4 class="fw-bold text-primary mb-0">{{ number_format($totalLikes) }}</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted text-uppercase" style="font-size: 11px;">Total Comments</small>
                        <h4 class="fw-bold text-info mb-0">{{ number_format($totalComments) }}</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted text-uppercase" style="font-size: 11px;">Total Shares</small>
                        <h4 class="fw-bold text-success mb-0">{{ number_format($totalShares) }}</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted text-uppercase" style="font-size: 11px;">Story Views</small>
                        <h4 class="fw-bold text-warning mb-0">{{ number_format($totalStoryViews) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Directory Pane -->
        <div class="tab-pane fade" id="directory-pane" role="tabpanel" aria-labelledby="directory-tab">
            <div class="row g-4">
                <div class="col-md-6">
                    <h6 class="fw-bold text-primary mb-3">Top Communities by Membership</h6>
                    <x-admin.table :headers="['Community', 'Category', 'Members Count']" :empty="$topCommunities->isEmpty()" emptyMessage="No community analytics available.">
                        @foreach($topCommunities as $c)
                            <tr>
                                <td class="fw-bold text-dark">{{ $c->name }}</td>
                                <td><span class="badge bg-light-primary text-primary">{{ $c->category }}</span></td>
                                <td><span class="badge bg-success">{{ $c->members_count }}</span></td>
                            </tr>
                        @endforeach
                    </x-admin.table>
                </div>

                <div class="col-md-6">
                    <h6 class="fw-bold text-primary mb-3">Top Viewed Marketplace Products</h6>
                    <x-admin.table :headers="['Product', 'Price', 'Saves / Wishlist']" :empty="$topProducts->isEmpty()" emptyMessage="No product analytics available.">
                        @foreach($topProducts as $p)
                            <tr>
                                <td class="fw-bold text-dark text-truncate" style="max-width: 180px;">{{ $p->title }}</td>
                                <td class="text-success fw-bold">${{ number_format($p->price, 2) }}</td>
                                <td><span class="badge bg-info">{{ $p->saved_products_count }}</span></td>
                            </tr>
                        @endforeach
                    </x-admin.table>
                </div>
            </div>
        </div>

        <!-- Events & Moderation Pane -->
        <div class="tab-pane fade" id="events-pane" role="tabpanel" aria-labelledby="events-tab">
            <div class="row g-4">
                <div class="col-md-6">
                    <h6 class="fw-bold text-primary mb-3">Event Stats</h6>
                    <table class="table table-sm table-borderless bg-light p-3 rounded">
                        <tr>
                            <td class="text-muted fw-bold">Total Events:</td>
                            <td class="fw-bold text-dark">{{ $totalEvents }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Upcoming Scheduled Events:</td>
                            <td><span class="badge bg-success">{{ $upcomingEventsCount }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Total Member RSVPs:</td>
                            <td><span class="badge bg-info">{{ $totalEventResponses }}</span></td>
                        </tr>
                    </table>
                </div>

                <div class="col-md-6">
                    <h6 class="fw-bold text-primary mb-3">Moderation & Reports Stats</h6>
                    <table class="table table-sm table-borderless bg-light p-3 rounded">
                        <tr>
                            <td class="text-muted fw-bold">Total Platform Reports:</td>
                            <td class="fw-bold text-danger">{{ $totalReports }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Pending Review Queue:</td>
                            <td><span class="badge bg-warning text-dark">{{ $pendingReports }}</span></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </x-admin.tabs>
</x-admin.card>
@endsection
