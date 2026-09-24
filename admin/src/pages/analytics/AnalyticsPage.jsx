import { useState, useEffect, useCallback } from 'react';
import { useSearchParams, Link } from 'react-router-dom';
import { Download, Eye, CheckCircle2 } from 'lucide-react';
import { Button } from 'primereact/button';
import { TabView, TabPanel } from 'primereact/tabview';
import { PageHeader } from '../../components/common/PageHeader';
import { ErrorState } from '../../components/common/ErrorState';
import { useToast } from '../../hooks/useToast';
import { analyticsApi, downloadBlobFromResponse } from '../../api';

// Subcomponents
import { AnalyticsStatsCards } from './components/AnalyticsStatsCards';
import { AnalyticsFilterBar } from './components/AnalyticsFilterBar';
import { AnalyticsGrowthChart } from './components/AnalyticsGrowthChart';
import { AnalyticsSkeleton } from './components/AnalyticsSkeleton';

export function AnalyticsPage() {
  const { showSuccess, showError } = useToast();
  const [searchParams, setSearchParams] = useSearchParams();

  const [analyticsData, setAnalyticsData] = useState(null);
  const [filters, setFilters] = useState(() => ({
    period: searchParams.get('period') || (searchParams.get('date_from') ? undefined : '30days'),
    date_from: searchParams.get('date_from') || undefined,
    date_to: searchParams.get('date_to') || undefined,
  }));
  const [loading, setLoading] = useState(true);
  const [exporting, setExporting] = useState(false);
  const [error, setError] = useState(null);
  const [activeTab, setActiveTab] = useState(0);

  const fetchAnalytics = useCallback(async (currentFilters = filters) => {
    setLoading(true);
    setError(null);
    try {
      const res = await analyticsApi.getAnalytics(currentFilters);
      const data = res?.data || res;
      setAnalyticsData(data);
    } catch (err) {
      setError(err.message || 'Failed to load analytics dashboard data.');
    } finally {
      setLoading(false);
    }
  }, [filters]);

  useEffect(() => {
    fetchAnalytics(filters);
  }, []);

  const handleFilter = (newFilters) => {
    setFilters(newFilters);
    const next = new URLSearchParams();
    if (newFilters.period && newFilters.period !== 'custom') next.set('period', newFilters.period);
    if (newFilters.date_from) next.set('date_from', newFilters.date_from);
    if (newFilters.date_to) next.set('date_to', newFilters.date_to);
    setSearchParams(next, { replace: true });
    fetchAnalytics(newFilters);
  };

  const handleExportCsv = async (exportType = 'general') => {
    if (exporting) return; // Prevent duplicate clicks
    setExporting(true);
    try {
      const isBi = exportType === 'bi';
      const exportParams = {
        ...filters,
        type: isBi ? 'bi' : 'general',
      };

      const datePart = filters.date_from
        ? `${filters.date_from}${filters.date_to ? `-to-${filters.date_to}` : ''}`
        : new Date().toISOString().split('T')[0];

      const fallbackFilename = isBi
        ? `analytics-bi-metrics-${datePart}.csv`
        : `analytics-export-${datePart}.csv`;

      // 1. Send export request & receive binary blob
      const blob = isBi
        ? await analyticsApi.exportBiCsv(exportParams)
        : await analyticsApi.exportCsv(exportParams);

      // 2. Validate blob, extract filename from header or fallback, and trigger browser download
      await downloadBlobFromResponse(blob, fallbackFilename);

      // 3. Show success message ONLY after the download process is successfully triggered
      showSuccess(
        isBi
          ? 'Analytics Business Intelligence CSV exported successfully.'
          : 'Analytics CSV exported successfully.'
      );
    } catch (err) {
      showError(err.message || 'Failed to export analytics CSV.');
    } finally {
      setExporting(false);
    }
  };

  if (loading && !analyticsData) {
    return <AnalyticsSkeleton />;
  }

  if (error && !analyticsData) {
    return (
      <div className="space-y-6">
        <PageHeader
          title="Analytics & Business Intelligence"
          breadcrumbs={[{ label: 'Analytics' }]}
        />
        <ErrorState
          title="Analytics Service Unavailable"
          message={error}
          onRetry={() => fetchAnalytics(filters)}
        />
      </div>
    );
  }

  const data = analyticsData || {};
  const genderDist = data.genderDistribution || {};
  const topCreators = data.topCreators || [];
  const topCommunities = data.topCommunities || [];
  const topProducts = data.topProducts || [];

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <PageHeader
        title="Analytics & Business Intelligence Dashboard"
        subtitle="Real-time metrics, growth trends, engagement analytics, and directory performance."
        breadcrumbs={[{ label: 'Dashboard', to: '/admin/dashboard' }, { label: 'Analytics & BI' }]}
        actions={
          <Button
            label={exporting ? 'Exporting...' : 'Export BI Metrics CSV'}
            icon={exporting ? 'pi pi-spin pi-spinner' : <Download className="w-3.5 h-3.5 mr-1.5" />}
            size="small"
            onClick={() => handleExportCsv('bi')}
            disabled={exporting}
            loading={exporting}
            className="p-button-outlined p-button-secondary text-xs"
          />
        }
      />

      {/* Filter Bar */}
      <AnalyticsFilterBar
        dateFrom={filters.date_from || ''}
        dateTo={filters.date_to || ''}
        onFilter={handleFilter}
        onExport={() => handleExportCsv('general')}
        loading={loading}
        exporting={exporting}
      />

      {/* Metric Stat Cards */}
      <AnalyticsStatsCards data={data} loading={loading} />

      {/* Growth Trend SVG Chart */}
      <AnalyticsGrowthChart
        dates={data.chartDates || []}
        counts={data.chartCounts || []}
        loading={loading}
      />

      {/* Tabbed Detailed Analytics Modules */}
      <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 sm:p-6">
        <TabView activeIndex={activeTab} onTabChange={(e) => setActiveTab(e.index)}>
          {/* Tab 1: User Growth & Demographics */}
          <TabPanel header="User Growth & Demographics">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
              {/* Demographics Card */}
              <div className="space-y-3 bg-slate-50 p-4 rounded-xl border border-slate-100 text-xs">
                <h5 className="font-bold text-slate-800 uppercase text-[11px]">Member Demographics</h5>
                <div className="flex justify-between py-1.5 border-b border-slate-200/60">
                  <span className="text-slate-500">Total Registered Members:</span>
                  <strong className="text-slate-800 font-bold">{Number(data.totalMembers || 0).toLocaleString()}</strong>
                </div>
                <div className="flex justify-between py-1.5 border-b border-slate-200/60">
                  <span className="text-slate-500">Verified Mobile Accounts:</span>
                  <span className="inline-flex items-center text-xs font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                    <CheckCircle2 className="w-3 h-3 mr-1 text-emerald-600" />
                    {Number(data.verifiedMembersCount || 0).toLocaleString()}
                  </span>
                </div>
                <div className="pt-2">
                  <span className="text-slate-500 block mb-2 font-medium">Gender Distribution:</span>
                  <div className="flex flex-wrap gap-1.5">
                    {Object.entries(genderDist).map(([gender, count]) => (
                      <span
                        key={gender}
                        className="inline-block text-[11px] font-semibold text-blue-700 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-100"
                      >
                        {gender ? gender.charAt(0).toUpperCase() + gender.slice(1) : 'Unspecified'}: <strong>{count}</strong>
                      </span>
                    ))}
                  </div>
                </div>
              </div>

              {/* Top Creators Table */}
              <div className="space-y-2">
                <h5 className="font-bold text-slate-800 uppercase text-[11px]">Top Active Content Creators</h5>
                {topCreators.length === 0 ? (
                  <div className="p-6 text-center text-xs text-slate-400 bg-slate-50 rounded-xl">
                    No creator statistics recorded yet.
                  </div>
                ) : (
                  <div className="overflow-x-auto border border-slate-200 rounded-xl bg-white">
                    <table className="w-full text-left text-xs border-collapse">
                      <thead>
                        <tr className="bg-slate-50/80 border-b border-slate-200 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                          <th className="py-2 px-3">Member</th>
                          <th className="py-2 px-3">User ID</th>
                          <th className="py-2 px-3 text-right">Posts</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100">
                        {topCreators.map((creator) => (
                          <tr key={creator.id} className="hover:bg-slate-50/60">
                            <td className="py-2 px-3 flex items-center space-x-2">
                              <div className="w-6 h-6 rounded-full bg-slate-200 overflow-hidden shrink-0">
                                {creator.avatar_url ? (
                                  <img src={creator.avatar_url} alt={creator.name} className="w-full h-full object-cover" />
                                ) : (
                                  <div className="w-full h-full flex items-center justify-center text-[9px] font-bold text-slate-600">
                                    {(creator.name || 'M').charAt(0)}
                                  </div>
                                )}
                              </div>
                              <span className="font-semibold text-slate-800 truncate">{creator.name}</span>
                            </td>
                            <td className="py-2 px-3 font-mono text-[11px] text-blue-600">{creator.user_id}</td>
                            <td className="py-2 px-3 text-right font-extrabold text-slate-900">{creator.posts_count}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>
            </div>
          </TabPanel>

          {/* Tab 2: Content & Engagement */}
          <TabPanel header="Content & Engagement">
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-2">
              <div className="p-4 bg-blue-50/60 rounded-xl border border-blue-100 text-center">
                <span className="text-[10px] font-bold uppercase text-blue-600 tracking-wider block mb-1">Total Likes</span>
                <span className="text-xl font-extrabold text-blue-900">{Number(data.totalLikes || 0).toLocaleString()}</span>
              </div>

              <div className="p-4 bg-sky-50/60 rounded-xl border border-sky-100 text-center">
                <span className="text-[10px] font-bold uppercase text-sky-600 tracking-wider block mb-1">Total Comments</span>
                <span className="text-xl font-extrabold text-sky-900">{Number(data.totalComments || 0).toLocaleString()}</span>
              </div>

              <div className="p-4 bg-emerald-50/60 rounded-xl border border-emerald-100 text-center">
                <span className="text-[10px] font-bold uppercase text-emerald-600 tracking-wider block mb-1">Total Shares</span>
                <span className="text-xl font-extrabold text-emerald-900">{Number(data.totalShares || 0).toLocaleString()}</span>
              </div>

              <div className="p-4 bg-amber-50/60 rounded-xl border border-amber-100 text-center">
                <span className="text-[10px] font-bold uppercase text-amber-600 tracking-wider block mb-1">Story Views</span>
                <span className="text-xl font-extrabold text-amber-900">{Number(data.totalStoryViews || 0).toLocaleString()}</span>
              </div>
            </div>
          </TabPanel>

          {/* Tab 3: Directory & Commerce */}
          <TabPanel header="Directory & Commerce">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
              {/* Top Communities */}
              <div className="space-y-2">
                <h5 className="font-bold text-slate-800 uppercase text-[11px]">Top Communities by Membership</h5>
                {topCommunities.length === 0 ? (
                  <div className="p-6 text-center text-xs text-slate-400 bg-slate-50 rounded-xl">
                    No community data available.
                  </div>
                ) : (
                  <div className="overflow-x-auto border border-slate-200 rounded-xl bg-white">
                    <table className="w-full text-left text-xs border-collapse">
                      <thead>
                        <tr className="bg-slate-50/80 border-b border-slate-200 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                          <th className="py-2 px-3">Community</th>
                          <th className="py-2 px-3">Category</th>
                          <th className="py-2 px-3 text-right">Members</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100">
                        {topCommunities.map((comm) => (
                          <tr key={comm.id} className="hover:bg-slate-50/60">
                            <td className="py-2 px-3 font-semibold text-slate-800">{comm.name}</td>
                            <td className="py-2 px-3 text-slate-500">{comm.category || 'General'}</td>
                            <td className="py-2 px-3 text-right font-extrabold text-emerald-600">{comm.members_count}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>

              {/* Top Products */}
              <div className="space-y-2">
                <h5 className="font-bold text-slate-800 uppercase text-[11px]">Top Viewed Marketplace Products</h5>
                {topProducts.length === 0 ? (
                  <div className="p-6 text-center text-xs text-slate-400 bg-slate-50 rounded-xl">
                    No marketplace product data available.
                  </div>
                ) : (
                  <div className="overflow-x-auto border border-slate-200 rounded-xl bg-white">
                    <table className="w-full text-left text-xs border-collapse">
                      <thead>
                        <tr className="bg-slate-50/80 border-b border-slate-200 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                          <th className="py-2 px-3">Product</th>
                          <th className="py-2 px-3">Price</th>
                          <th className="py-2 px-3 text-right">Wishlist</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100">
                        {topProducts.map((p) => (
                          <tr key={p.id} className="hover:bg-slate-50/60">
                            <td className="py-2 px-3 font-semibold text-slate-800 truncate max-w-[150px]">{p.title}</td>
                            <td className="py-2 px-3 font-extrabold text-emerald-600">${Number(p.price || 0).toFixed(2)}</td>
                            <td className="py-2 px-3 text-right font-bold text-sky-600">{p.saved_products_count ?? 0}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>
            </div>
          </TabPanel>

          {/* Tab 4: Events & Moderation */}
          <TabPanel header="Events & Moderation">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
              <div className="space-y-3 bg-slate-50 p-4 rounded-xl border border-slate-100 text-xs">
                <h5 className="font-bold text-slate-800 uppercase text-[11px]">Platform Events Summary</h5>
                <div className="flex justify-between py-1.5 border-b border-slate-200/60">
                  <span className="text-slate-500">Total Platform Events:</span>
                  <strong className="text-slate-800 font-bold">{data.totalEvents ?? 0}</strong>
                </div>
                <div className="flex justify-between py-1.5 border-b border-slate-200/60">
                  <span className="text-slate-500">Upcoming Events:</span>
                  <span className="font-bold text-emerald-600">{data.upcomingEventsCount ?? 0}</span>
                </div>
                <div className="flex justify-between pt-1.5">
                  <span className="text-slate-500">Total Member RSVPs:</span>
                  <strong className="text-slate-800">{data.totalEventResponses ?? 0}</strong>
                </div>
              </div>

              <div className="space-y-3 bg-slate-50 p-4 rounded-xl border border-slate-100 text-xs">
                <h5 className="font-bold text-slate-800 uppercase text-[11px]">Moderation Queue Summary</h5>
                <div className="flex justify-between py-1.5 border-b border-slate-200/60">
                  <span className="text-slate-500">Total Platform Reports:</span>
                  <strong className="text-red-600 font-bold">{data.totalReports ?? 0}</strong>
                </div>
                <div className="flex justify-between py-1.5 border-b border-slate-200/60">
                  <span className="text-slate-500">Pending Review Queue:</span>
                  <span className="font-bold text-amber-600">{data.pendingReports ?? 0}</span>
                </div>
                <div className="pt-2 flex justify-end">
                  <Link to="/admin/reports">
                    <Button
                      label="Open Moderation Queue"
                      icon={<Eye className="w-3.5 h-3.5 mr-1" />}
                      size="small"
                      className="p-button-outlined p-button-primary text-xs"
                    />
                  </Link>
                </div>
              </div>
            </div>
          </TabPanel>
        </TabView>
      </div>
    </div>
  );
}

export default AnalyticsPage;
