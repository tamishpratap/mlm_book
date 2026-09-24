@extends('member.layouts.app')

@section('title', 'Analytics & Growth Insights - ' . $businessPage->page_name)

@section('content')
<div class="biz-page">
    <!-- Hero Analytics Header -->
    <header class="biz-header" style="margin-bottom: 20px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding: 24px; border-radius: 18px; color: #fff;">
        <div class="biz-header__info">
            <span class="biz-badge biz-badge--category" style="background: rgba(79, 125, 243, 0.2); color: #60a5fa; border: 1px solid rgba(96, 165, 250, 0.3); margin-bottom: 8px; display: inline-block;">
                <i data-lucide="bar-chart-3" style="width: 14px; height: 14px;"></i> Enterprise Analytics & Growth Platform
            </span>
            <h1 style="font-size: 26px; font-weight: 800; color: #fff; margin: 0 0 6px 0;">
                {{ $businessPage->page_name }} Performance Insights
            </h1>
            <p style="font-size: 14px; color: #94a3b8; margin: 0;">Comprehensive audience growth, post engagement, customer reviews, and health metrics.</p>
        </div>

        <div class="biz-header__actions" style="margin-top: 14px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <!-- Period Selector -->
            <form method="GET" action="{{ route('member.business-pages.analytics.index', $businessPage) }}" id="analyticsPeriodForm">
                <select name="period" class="biz-filter-select" style="background: #fff; color: #1d2738; padding: 8px 12px; font-size: 13px;" onchange="this.form.submit()">
                    <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="yesterday" {{ $period === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="7days" {{ $period === '7days' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="30days" {{ $period === '30days' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="90days" {{ $period === '90days' ? 'selected' : '' }}>Last 90 Days</option>
                    <option value="this_year" {{ $period === 'this_year' ? 'selected' : '' }}>This Year</option>
                    <option value="all" {{ $period === 'all' ? 'selected' : '' }}>Lifetime</option>
                </select>
            </form>

            <!-- Export Buttons -->
            <button type="button" class="member-button member-button--secondary" onclick="triggerExport('csv')">
                <i data-lucide="download"></i> Export CSV
            </button>
            <button type="button" class="member-button member-button--secondary" onclick="triggerExport('pdf')">
                <i data-lucide="file-text"></i> Export PDF
            </button>
            <a href="{{ route('member.business-pages.show', $businessPage) }}" class="member-button member-button--primary">
                <i data-lucide="arrow-left"></i> Back to Profile
            </a>
        </div>
    </header>

    <!-- Health Diagnostic Scorecards Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="biz-info-card" style="padding: 18px; background: linear-gradient(135deg, rgba(79, 125, 243, 0.08), rgba(138, 43, 226, 0.08)); border: 1px solid rgba(79, 125, 243, 0.2);">
            <span style="font-size: 12px; color: #687386; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 6px;">
                <i data-lucide="activity" style="width: 14px; height: 14px; color: #4f7df3;"></i> Business Health Score
            </span>
            <strong style="font-size: 32px; font-weight: 900; color: #4f7df3;" id="scoreHealth">{{ $scores['health_score'] }}/100</strong>
            <div style="height: 6px; border-radius: 3px; background: #e7ecf4; margin-top: 8px; overflow: hidden;">
                <div style="height: 100%; width: {{ $scores['health_score'] }}%; background: #4f7df3;"></div>
            </div>
        </div>

        <div class="biz-info-card" style="padding: 18px; background: linear-gradient(135deg, rgba(32, 200, 117, 0.08), rgba(79, 125, 243, 0.08)); border: 1px solid rgba(32, 200, 117, 0.2);">
            <span style="font-size: 12px; color: #687386; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 6px;">
                <i data-lucide="trending-up" style="width: 14px; height: 14px; color: #20c875;"></i> Growth Index
            </span>
            <strong style="font-size: 32px; font-weight: 900; color: #20c875;" id="scoreGrowth">{{ $scores['growth_score'] }}/100</strong>
            <div style="height: 6px; border-radius: 3px; background: #e7ecf4; margin-top: 8px; overflow: hidden;">
                <div style="height: 100%; width: {{ $scores['growth_score'] }}%; background: #20c875;"></div>
            </div>
        </div>

        <div class="biz-info-card" style="padding: 18px; background: linear-gradient(135deg, rgba(138, 43, 226, 0.08), rgba(247, 185, 64, 0.08)); border: 1px solid rgba(138, 43, 226, 0.2);">
            <span style="font-size: 12px; color: #687386; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 6px;">
                <i data-lucide="zap" style="width: 14px; height: 14px; color: #8a2be2;"></i> Engagement Index
            </span>
            <strong style="font-size: 32px; font-weight: 900; color: #8a2be2;" id="scoreEngagement">{{ $scores['engagement_score'] }}/100</strong>
            <div style="height: 6px; border-radius: 3px; background: #e7ecf4; margin-top: 8px; overflow: hidden;">
                <div style="height: 100%; width: {{ $scores['engagement_score'] }}%; background: #8a2be2;"></div>
            </div>
        </div>

        <div class="biz-info-card" style="padding: 18px; background: linear-gradient(135deg, rgba(247, 185, 64, 0.08), rgba(32, 200, 117, 0.08)); border: 1px solid rgba(247, 185, 64, 0.2);">
            <span style="font-size: 12px; color: #687386; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 6px;">
                <i data-lucide="smile" style="width: 14px; height: 14px; color: #f7b940;"></i> Satisfaction Index
            </span>
            <strong style="font-size: 32px; font-weight: 900; color: #b7791f;" id="scoreSatisfaction">{{ $scores['satisfaction_score'] }}/100</strong>
            <div style="height: 6px; border-radius: 3px; background: #e7ecf4; margin-top: 8px; overflow: hidden;">
                <div style="height: 100%; width: {{ $scores['satisfaction_score'] }}%; background: #f7b940;"></div>
            </div>
        </div>
    </div>

    <!-- Overview Statistic Cards Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="biz-info-card" style="padding: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <span style="font-size: 12px; color: #687386;">Total Followers</span>
                    <strong style="font-size: 22px; color: #1d2738; display: block; margin-top: 2px;">{{ $followersCount }}</strong>
                </div>
                <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(79, 125, 243, 0.1); color: #4f7df3; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="users"></i>
                </div>
            </div>
        </div>

        <div class="biz-info-card" style="padding: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <span style="font-size: 12px; color: #687386;">Timeline Posts</span>
                    <strong style="font-size: 22px; color: #1d2738; display: block; margin-top: 2px;">{{ $postsCount }}</strong>
                </div>
                <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(32, 200, 117, 0.1); color: #20c875; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="newspaper"></i>
                </div>
            </div>
        </div>

        <div class="biz-info-card" style="padding: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <span style="font-size: 12px; color: #687386;">Total Reviews</span>
                    <strong style="font-size: 22px; color: #1d2738; display: block; margin-top: 2px;">{{ $reviewsCount }}</strong>
                </div>
                <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(247, 185, 64, 0.1); color: #f7b940; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="star"></i>
                </div>
            </div>
        </div>

        <div class="biz-info-card" style="padding: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <span style="font-size: 12px; color: #687386;">Average Rating</span>
                    <strong style="font-size: 22px; color: #1d2738; display: block; margin-top: 2px;">{{ $avgRating }} ★</strong>
                </div>
                <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(247, 185, 64, 0.1); color: #f7b940; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="award"></i>
                </div>
            </div>
        </div>

        <div class="biz-info-card" style="padding: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <span style="font-size: 12px; color: #687386;">Customer Inquiries</span>
                    <strong style="font-size: 22px; color: #1d2738; display: block; margin-top: 2px;">{{ $conversationsCount }}</strong>
                </div>
                <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(138, 43, 226, 0.1); color: #8a2be2; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="message-square"></i>
                </div>
            </div>
        </div>

        <div class="biz-info-card" style="padding: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <span style="font-size: 12px; color: #687386;">Page Views</span>
                    <strong style="font-size: 22px; color: #1d2738; display: block; margin-top: 2px;">{{ $viewsCount }}</strong>
                </div>
                <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(79, 125, 243, 0.1); color: #4f7df3; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="eye"></i>
                </div>
            </div>
        </div>

        <div class="biz-info-card" style="padding: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <span style="font-size: 12px; color: #687386;">Team Size</span>
                    <strong style="font-size: 22px; color: #1d2738; display: block; margin-top: 2px;">{{ $teamCount }}</strong>
                </div>
                <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(32, 200, 117, 0.1); color: #20c875; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="users-round"></i>
                </div>
            </div>
        </div>

        <div class="biz-info-card" style="padding: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <span style="font-size: 12px; color: #687386;">Feedback Index</span>
                    <strong style="font-size: 15px; color: #20c875; display: block; margin-top: 6px;">{{ $recommendationBadge }}</strong>
                </div>
                <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(32, 200, 117, 0.1); color: #20c875; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="thumbs-up"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Breakdown Split Cards -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;" id="analyticsBreakdownGrid">
        <!-- Rating Distribution -->
        <div class="biz-info-card" style="padding: 20px;">
            <h3 class="biz-info-card__title" style="font-size: 15px;">
                <i data-lucide="star" style="color: #f7b940;"></i> Ratings & Review Breakdown
            </h3>
            <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 14px;">
                @php $totalRev = max(1, $reviewsCount); @endphp
                @foreach ([5, 4, 3, 2, 1] as $st)
                    @php $cnt = $ratingDistribution[$st] ?? 0; $pct = round(($cnt / $totalRev) * 100); @endphp
                    <div style="display: flex; align-items: center; gap: 10px; font-size: 13px; color: #687386;">
                        <span style="width: 50px;">{{ $st }} Stars</span>
                        <div style="flex: 1; height: 10px; border-radius: 5px; background: #edf3ff; overflow: hidden;">
                            <div style="height: 100%; width: {{ $pct }}%; background: #f7b940; border-radius: 5px;"></div>
                        </div>
                        <span style="width: 40px; text-align: right; font-weight: 700; color: #1d2738;">{{ $cnt }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Visitor Devices Breakdown -->
        <div class="biz-info-card" style="padding: 20px;">
            <h3 class="biz-info-card__title" style="font-size: 15px;">
                <i data-lucide="monitor" style="color: #4f7df3;"></i> Visitor Device Breakdown
            </h3>
            <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 14px;">
                @php $totalViews = max(1, $viewsCount); @endphp
                @foreach (['desktop' => 'Desktop Computers', 'mobile' => 'Mobile Phones', 'tablet' => 'Tablets'] as $key => $label)
                    @php $dCnt = $deviceBreakdown[$key] ?? 0; $dPct = round(($dCnt / $totalViews) * 100); @endphp
                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px; color: #1d2738; margin-bottom: 4px;">
                            <span><strong>{{ $label }}</strong></span>
                            <span>{{ $dCnt }} ({{ $dPct }}%)</span>
                        </div>
                        <div style="height: 8px; border-radius: 4px; background: #edf3ff; overflow: hidden;">
                            <div style="height: 100%; width: {{ $dPct }}%; background: #4f7df3; border-radius: 4px;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Top Posts Matrix Table -->
    <div class="biz-info-card" style="padding: 20px; margin-bottom: 24px;">
        <h3 class="biz-info-card__title" style="font-size: 15px; margin-bottom: 14px;">
            <i data-lucide="newspaper" style="color: #4f7df3;"></i> Recent Posts Engagement Matrix
        </h3>
        <div style="overflow-x: auto;">
            <table class="biz-table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 2px solid #e7ecf4; text-align: left; color: #687386;">
                        <th style="padding: 10px;">Post Content</th>
                        <th style="padding: 10px;">Published Date</th>
                        <th style="padding: 10px; text-align: center;">Likes</th>
                        <th style="padding: 10px; text-align: center;">Comments</th>
                        <th style="padding: 10px; text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($topPosts as $tp)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 10px; color: #1d2738; font-weight: 600;">
                                {{ Str::limit($tp->content ?? 'Media Post', 60) }}
                            </td>
                            <td style="padding: 10px; color: #98a2b3;">{{ $tp->created_at->format('M d, Y') }}</td>
                            <td style="padding: 10px; text-align: center; font-weight: 700; color: #4f7df3;">{{ $tp->likes->count() }}</td>
                            <td style="padding: 10px; text-align: center; font-weight: 700; color: #20c875;">{{ $tp->comments->count() }}</td>
                            <td style="padding: 10px; text-align: center;">
                                @if ($tp->is_pinned)
                                    <span class="biz-badge biz-badge--category" style="font-size: 10px;">Pinned</span>
                                @else
                                    <span class="biz-badge" style="font-size: 10px; background: #edf3ff; color: #4f7df3;">Active</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px; color: #98a2b3;">No timeline posts published yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function triggerExport(format) {
    alert(`Exporting Business Analytics report in ${format.toUpperCase()} format...\n(Export feature architecture is prepared for Phase 10 integration).`);
}
</script>
@endsection
