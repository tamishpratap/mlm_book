import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Button } from 'primereact/button';
import { PageHeader } from '../../components/common/PageHeader';
import { ErrorState } from '../../components/common/ErrorState';
import { useToast } from '../../hooks/useToast';
import { dashboardApi } from '../../api';

// Subcomponents
import { QuickNavigation } from './components/QuickNavigation';
import { KpiStatCards } from './components/KpiStatCards';
import { ModuleSummaryCards } from './components/ModuleSummaryCards';
import { TrendAreaChart } from './components/TrendAreaChart';
import { BusinessCategoriesCard } from './components/BusinessCategoriesCard';
import { RecentMembersTable } from './components/RecentMembersTable';
import { RecentBusinessPagesTable } from './components/RecentBusinessPagesTable';
import { RecentPostsFeed } from './components/RecentPostsFeed';
import { UpcomingEventsList } from './components/UpcomingEventsList';
import { SystemStatusCard } from './components/SystemStatusCard';
import { DashboardSkeleton } from './components/DashboardSkeleton';

export function DashboardPage() {
  const [data, setData] = useState({
    // Section 1: KPI Metrics
    totalMembers: 0,
    newMembersToday: 0,
    newMembersThisWeek: 0,
    newMembersThisMonth: 0,
    activeMembers: 0,
    inactiveMembers: 0,
    pendingConnections: 0,
    totalConnections: 0,
    // Section 2: Content
    totalPosts: 0,
    postsToday: 0,
    postsThisWeek: 0,
    activeStories: 0,
    storiesToday: 0,
    mediaPosts: 0,
    // Section 3: Business Pages
    totalBusinessPages: 0,
    activeBusinessPages: 0,
    pendingBusinessPages: 0,
    verifiedBusinessPages: 0,
    publicBusinessPages: 0,
    // Section 4: Communities
    totalCommunities: 0,
    activeCommunities: 0,
    totalCommunityMembers: 0,
    pendingCommunityRequests: 0,
    // Section 5: Marketplace
    totalProducts: 0,
    activeProducts: 0,
    pendingProducts: 0,
    closedProducts: 0,
    // Section 6: Events
    totalEvents: 0,
    upcomingEventsCount: 0,
    pastEventsCount: 0,
    eventsThisMonthCount: 0,
    // Section 7 & 8: Moderation & Notifications
    pendingReports: 0,
    totalReports: 0,
    blockedMembers: 0,
    unreadNotifications: 0,
    totalNotifications: 0,
    // Section 9: 14-day Trend
    trendDates: [],
    memberRegTrend: [],
    postsTrend: [],
    storiesTrend: [],
    // Section 10: Categories
    businessCategories: [],
    // Section 11-14: Recents
    recentMembers: [],
    recentBusinessPages: [],
    recentPosts: [],
    upcomingEvents: [],
  });

  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);
  const { showSuccess, showError } = useToast();

  const loadData = () => {
    setLoading(true);
    setError(null);
    dashboardApi.getOverview()
      .then((response) => {
        if (response && typeof response === 'object' && !response.includes?.('<!DOCTYPE html>')) {
          setData((prev) => ({ ...prev, ...response }));
        }
      })
      .catch((err) => {
        setError(err.message || 'Failed to fetch platform dashboard metrics.');
      })
      .finally(() => {
        setLoading(false);
      });
  };

  const handleRefresh = () => {
    setRefreshing(true);
    dashboardApi.getOverview()
      .then((response) => {
        if (response && typeof response === 'object' && !response.includes?.('<!DOCTYPE html>')) {
          setData((prev) => ({ ...prev, ...response }));
        }
        showSuccess('Dashboard metrics updated.');
      })
      .catch((err) => {
        showError(err.message || 'Failed to refresh metrics.');
      })
      .finally(() => {
        setRefreshing(false);
      });
  };

  useEffect(() => {
    let isMounted = true;
    dashboardApi.getOverview()
      .then((response) => {
        if (!isMounted) return;
        if (response && typeof response === 'object' && !response.includes?.('<!DOCTYPE html>')) {
          setData((prev) => ({ ...prev, ...response }));
        }
      })
      .catch((err) => {
        if (!isMounted) return;
        setError(err.message || 'Failed to fetch platform dashboard metrics.');
      })
      .finally(() => {
        if (isMounted) setLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, []);

  if (loading) {
    return <DashboardSkeleton />;
  }

  if (error && !data.totalMembers && !data.totalPosts) {
    return (
      <div className="space-y-6">
        <PageHeader
          title="Admin Overview Dashboard"
          subtitle="Comprehensive platform analytics, activity monitoring, and system metrics."
        />
        <ErrorState
          title="Failed to Load Dashboard Metrics"
          message={error}
          onRetry={loadData}
        />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <PageHeader
        title="Admin Overview Dashboard"
        subtitle="Comprehensive platform analytics, activity monitoring, and system metrics."
        actions={
          <div className="flex flex-wrap items-center gap-2.5 sm:gap-3">
            <Button
              label={refreshing ? 'Refreshing...' : 'Refresh Metrics'}
              icon={refreshing ? 'pi pi-spin pi-spinner' : 'pi pi-refresh'}
              size="small"
              onClick={handleRefresh}
              disabled={refreshing}
              className="p-button-outlined p-button-secondary text-xs"
            />
            <Link to="/admin/analytics" className="inline-flex">
              <Button
                label="Full Analytics BI"
                icon="pi pi-chart-bar"
                size="small"
                className="p-button-primary text-xs"
              />
            </Link>
          </div>
        }
      />

      {/* SECTION 17: Quick Navigation Bar */}
      <QuickNavigation pendingReports={data.pendingReports} />

      {/* SECTION 1: Top 8 KPI Metric Cards */}
      <KpiStatCards data={data} />

      {/* SECTIONS 2, 3, 4, 5, 6, 7: Module Summary Cards Grid */}
      <ModuleSummaryCards data={data} />

      {/* SECTION 9 & 10: Platform Trend Chart & MLM Business Categories */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-4 items-stretch">
        <div className="lg:col-span-8">
          <TrendAreaChart
            dates={data.trendDates}
            memberTrend={data.memberRegTrend}
            postsTrend={data.postsTrend}
          />
        </div>
        <div className="lg:col-span-4">
          <BusinessCategoriesCard
            categories={data.businessCategories}
            totalBusinessPages={data.totalBusinessPages}
          />
        </div>
      </div>

      {/* SECTIONS 11 & 12: Recent Members & Recent Business Pages Tables */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 items-stretch">
        <RecentMembersTable members={data.recentMembers} />
        <RecentBusinessPagesTable pages={data.recentBusinessPages} />
      </div>

      {/* SECTIONS 13, 14 & 16: Recent Posts Feed, Upcoming Events & System Status */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 items-stretch">
        {/* Left Column: Recent Posts */}
        <RecentPostsFeed posts={data.recentPosts} />

        {/* Right Column: Upcoming Events & System Status */}
        <div className="flex flex-col space-y-4 justify-between">
          <UpcomingEventsList events={data.upcomingEvents} />
          <SystemStatusCard totalNotifications={data.totalNotifications} />
        </div>
      </div>
    </div>
  );
}

export default DashboardPage;
