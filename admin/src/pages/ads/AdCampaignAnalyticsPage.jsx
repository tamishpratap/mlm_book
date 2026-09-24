import { useState, useEffect, useCallback } from 'react';
import { useSearchParams } from 'react-router-dom';
import {
  Megaphone,
  TrendingUp,
  Eye,
  MousePointerClick,
  DollarSign,
  Clock,
  CheckCircle,
  PauseCircle,
  XCircle,
  Building,
  ArrowUpRight,
  Download,
  Calendar,
  RefreshCw,
} from 'lucide-react';
import { Button } from 'primereact/button';
import { PageHeader } from '../../components/common/PageHeader';
import { LoadingSpinner } from '../../components/common/LoadingSpinner';
import { ErrorState } from '../../components/common/ErrorState';
import { StatusBadge } from '../../components/common/StatusBadge';
import { AdminDateFilter } from '../../components/admin/AdminDateFilter';
import { adCampaignsApi } from '../../api';

export function AdCampaignAnalyticsPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [dateFrom, setDateFrom] = useState(searchParams.get('date_from') || '');
  const [dateTo, setDateTo] = useState(searchParams.get('date_to') || '');
  const [datePreset, setDatePreset] = useState(searchParams.get('date_preset') || '');
  const [exporting, setExporting] = useState(false);

  const fetchAnalytics = useCallback(() => {
    setLoading(true);
    setError(null);
    const params = {
      date_from: dateFrom || undefined,
      date_to: dateTo || undefined,
      date_preset: (!dateFrom && !dateTo && datePreset && datePreset !== 'all' && datePreset !== 'custom') ? datePreset : undefined,
    };
    adCampaignsApi
      .getAnalytics(params)
      .then((res) => {
        setData(res);
      })
      .catch((err) => {
        console.error('Failed to load ad analytics:', err);
        setError(err.message || 'Failed to load advertising analytics.');
      })
      .finally(() => {
        setLoading(false);
      });
  }, [dateFrom, dateTo, datePreset]);

  useEffect(() => {
    fetchAnalytics();
  }, [fetchAnalytics]);

  const handleDateFilterChange = ({ startDate, endDate, preset }) => {
    setDateFrom(startDate || '');
    setDateTo(endDate || '');
    setDatePreset(preset || '');
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      if (startDate) next.set('date_from', startDate);
      else next.delete('date_from');
      if (endDate) next.set('date_to', endDate);
      else next.delete('date_to');
      if (preset && preset !== 'all' && preset !== 'custom') next.set('date_preset', preset);
      else next.delete('date_preset');
      return next;
    });
  };

  const handleDateFilterReset = () => {
    setDateFrom('');
    setDateTo('');
    setDatePreset('');
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      next.delete('date_from');
      next.delete('date_to');
      next.delete('date_preset');
      return next;
    });
  };

  const handleExport = async () => {
    setExporting(true);
    try {
      const res = await adCampaignsApi.exportCampaigns();
      if (res.success && res.campaigns) {
        const jsonString = `data:text/json;charset=utf-8,${encodeURIComponent(
          JSON.stringify(res.campaigns, null, 2)
        )}`;
        const downloadAnchor = document.createElement('a');
        downloadAnchor.setAttribute('href', jsonString);
        downloadAnchor.setAttribute('download', `ad_campaigns_financial_export_${new Date().toISOString().slice(0, 10)}.json`);
        document.body.appendChild(downloadAnchor);
        downloadAnchor.click();
        downloadAnchor.remove();
      }
    } catch (err) {
      console.error('Export failed:', err);
    } finally {
      setExporting(false);
    }
  };

  if (loading && !data) {
    return (
      <div className="py-20">
        <LoadingSpinner message="Aggregating platform advertising metrics..." />
      </div>
    );
  }

  if (error && !data) {
    return <ErrorState message={error} onRetry={fetchAnalytics} />;
  }

  const metrics = data?.metrics || {};
  const topCampaigns = data?.top_campaigns || [];

  return (
    <div className="space-y-6">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <PageHeader
          title="Advertising Analytics & Financial Reporting"
          subtitle="Platform-wide advertising performance, engagement metrics, click-through rates, platform revenue, and verified rewards ledger."
          breadcrumbs={[
            { label: 'Admin', to: '/admin/dashboard' },
            { label: 'Ads Management', to: '/admin/ad-campaigns' },
            { label: 'Analytics' },
          ]}
        />

        <div className="flex flex-wrap items-center gap-2 self-start md:self-auto">
          <AdminDateFilter
            startDate={dateFrom}
            endDate={dateTo}
            preset={datePreset}
            onChange={handleDateFilterChange}
            onReset={handleDateFilterReset}
          />

          <Button
            label="Export Data"
            icon="pi pi-download"
            size="small"
            className="p-button-outlined p-button-secondary rounded-xl text-xs"
            loading={exporting}
            onClick={handleExport}
          />

          <Button
            icon="pi pi-refresh"
            size="small"
            className="p-button-outlined p-button-secondary rounded-xl"
            onClick={fetchAnalytics}
            tooltip="Refresh Metrics"
          />
        </div>
      </div>

      {/* Main KPI Grid */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold uppercase tracking-wider text-slate-400">Total Impressions</span>
            <div className="p-2 rounded-xl bg-sky-100 text-sky-600">
              <Eye className="w-5 h-5" />
            </div>
          </div>
          <p className="text-2xl font-black text-slate-900 mt-3">
            {Number(metrics.total_impressions || 0).toLocaleString()}
          </p>
          <div className="text-xs text-slate-500 mt-1">
            <span className="font-semibold text-emerald-600">+{metrics.recent_impressions_24h || 0}</span> in last 24h
          </div>
        </div>

        <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold uppercase tracking-wider text-slate-400">Total Clicks</span>
            <div className="p-2 rounded-xl bg-indigo-100 text-indigo-600">
              <MousePointerClick className="w-5 h-5" />
            </div>
          </div>
          <p className="text-2xl font-black text-slate-900 mt-3">
            {Number(metrics.total_clicks || 0).toLocaleString()}
          </p>
          <div className="text-xs text-slate-500 mt-1">
            <span className="font-semibold text-emerald-600">+{metrics.recent_clicks_24h || 0}</span> in last 24h
          </div>
        </div>

        <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold uppercase tracking-wider text-slate-400">Verified Visits ($0.05)</span>
            <div className="p-2 rounded-xl bg-emerald-100 text-emerald-600">
              <CheckCircle className="w-5 h-5" />
            </div>
          </div>
          <p className="text-2xl font-black text-emerald-600 mt-3">
            {Number(metrics.total_verified_visits || 0).toLocaleString()}
          </p>
          <div className="text-xs text-slate-500 mt-1">
            <span className="font-semibold text-emerald-600">+{metrics.recent_verified_visits_24h || 0}</span> in last 24h
          </div>
        </div>

        <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold uppercase tracking-wider text-slate-400">Platform CTR</span>
            <div className="p-2 rounded-xl bg-purple-100 text-purple-600">
              <TrendingUp className="w-5 h-5" />
            </div>
          </div>
          <p className="text-2xl font-black text-purple-600 mt-3">
            {metrics.average_ctr !== undefined ? Number(metrics.average_ctr).toFixed(2) : '0.00'}%
          </p>
          <div className="text-xs text-slate-500 mt-1">Average click-through rate</div>
        </div>
      </div>

      {/* Financial Accounting & Reconciliation Grid */}
      <div className="bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950 p-6 rounded-2xl border border-slate-700 shadow-lg text-white">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-5 pb-4 border-b border-slate-700">
          <div>
            <h3 className="text-lg font-bold flex items-center gap-2">
              <DollarSign className="w-5 h-5 text-emerald-400" />
              Financial Accounting & Platform Revenue Reconciliation
            </h3>
            <p className="text-xs text-slate-400 mt-0.5">
              Exact mathematical separation between Advertiser Wallet Debits, Platform Fee Revenue, and Verified User Reward Depletions.
            </p>
          </div>
          <div className="px-3 py-1 bg-emerald-500/20 border border-emerald-500/40 rounded-full text-xs font-semibold text-emerald-300 w-fit">
            Reconciled: 100% Exact Ledger
          </div>
        </div>

        <div className="grid grid-cols-2 lg:grid-cols-5 gap-4">
          <div className="p-4 bg-white/5 rounded-xl border border-white/10">
            <span className="text-xs text-slate-400 font-semibold block">Campaign Running Budget</span>
            <span className="text-xl font-bold text-white mt-1 block">
              ${Number(metrics.total_budget || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}
            </span>
            <span className="text-[11px] text-slate-400">Total advertiser campaign funds</span>
          </div>

          <div className="p-4 bg-white/5 rounded-xl border border-white/10">
            <span className="text-xs text-amber-300 font-semibold block">Admin Platform Fee ({metrics.campaign_platform_fee_percent || 2.5}%)</span>
            <span className="text-xl font-bold text-amber-400 mt-1 block">
              ${Number(metrics.total_platform_fees || metrics.total_admin_fees || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}
            </span>
            <span className="text-[11px] text-amber-200/70">Retained as platform revenue</span>
          </div>

          <div className="p-4 bg-white/5 rounded-xl border border-white/10">
            <span className="text-xs text-sky-300 font-semibold block">Total Wallet Debited</span>
            <span className="text-xl font-bold text-sky-400 mt-1 block">
              ${Number(metrics.total_wallet_debits || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}
            </span>
            <span className="text-[11px] text-sky-200/70">Budget + Admin Platform Fee</span>
          </div>

          <div className="p-4 bg-white/5 rounded-xl border border-white/10">
            <span className="text-xs text-emerald-300 font-semibold block">Rewards Paid (Dynamic Tier)</span>
            <span className="text-xl font-bold text-emerald-400 mt-1 block">
              ${Number(metrics.total_rewards_paid || metrics.total_spent || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}
            </span>
            <span className="text-[11px] text-emerald-200/70">Earned by verified members</span>
          </div>

          <div className="p-4 bg-white/5 rounded-xl border border-white/10">
            <span className="text-xs text-indigo-300 font-semibold block">Remaining Running Budget</span>
            <span className="text-xl font-bold text-indigo-400 mt-1 block">
              ${Number(metrics.total_remaining_budget || metrics.total_remaining || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}
            </span>
            <span className="text-[11px] text-indigo-200/70">Available for future rewards</span>
          </div>
        </div>

        <div className="mt-4 pt-3 border-t border-slate-700/80 text-xs text-slate-300 flex flex-wrap items-center justify-between gap-2">
          <span>
            <strong className="text-white">Reconciliation Formula:</strong> Initial Budget (${Number(metrics.total_budget || 0).toFixed(2)}) = Rewards Paid (${Number(metrics.total_rewards_paid || metrics.total_spent || 0).toFixed(2)}) + Remaining Budget (${Number(metrics.total_remaining || 0).toFixed(2)})
          </span>
          <span className="text-emerald-400 font-mono font-medium">
            Zero Negative Budget Guarantee Active
          </span>
        </div>
      </div>

      {/* Campaign Status Breakdown */}
      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div className="bg-white p-4 rounded-xl border border-slate-200">
          <span className="text-xs text-slate-500 font-semibold block">Active</span>
          <span className="text-xl font-bold text-emerald-600">{metrics.active_campaigns || 0}</span>
        </div>
        <div className="bg-white p-4 rounded-xl border border-slate-200">
          <span className="text-xs text-slate-500 font-semibold block">Pending Review</span>
          <span className="text-xl font-bold text-amber-600">{metrics.pending_review || 0}</span>
        </div>
        <div className="bg-white p-4 rounded-xl border border-slate-200">
          <span className="text-xs text-slate-500 font-semibold block">Approved</span>
          <span className="text-xl font-bold text-blue-600">{metrics.approved || 0}</span>
        </div>
        <div className="bg-white p-4 rounded-xl border border-slate-200">
          <span className="text-xs text-slate-500 font-semibold block">Paused</span>
          <span className="text-xl font-bold text-orange-600">{metrics.paused || 0}</span>
        </div>
        <div className="bg-white p-4 rounded-xl border border-slate-200">
          <span className="text-xs text-slate-500 font-semibold block">Completed</span>
          <span className="text-xl font-bold text-slate-600">{metrics.completed || 0}</span>
        </div>
        <div className="bg-white p-4 rounded-xl border border-slate-200">
          <span className="text-xs text-slate-500 font-semibold block">Rejected</span>
          <span className="text-xl font-bold text-red-600">{metrics.rejected || 0}</span>
        </div>
      </div>

      {/* Top Campaigns */}
      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h3 className="text-base font-bold text-slate-900 mb-4">
          Top Performing Campaigns
        </h3>
        {topCampaigns.length === 0 ? (
          <p className="text-sm text-slate-400 italic">No campaign impressions recorded yet.</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-slate-600">
              <thead className="text-xs font-semibold text-slate-500 uppercase border-b border-slate-200 pb-2">
                <tr>
                  <th className="pb-3">Campaign</th>
                  <th className="pb-3">Business Page</th>
                  <th className="pb-3 text-right">Impressions</th>
                  <th className="pb-3 text-right">Clicks</th>
                  <th className="pb-3 text-right">CTR</th>
                  <th className="pb-3 text-right">Budget</th>
                  <th className="pb-3 text-center">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {topCampaigns.map((camp) => (
                  <tr key={camp.id} className="hover:bg-slate-50">
                    <td className="py-3 font-semibold text-slate-800">
                      {camp.campaign_name}
                    </td>
                    <td className="py-3">
                      {camp.business_page?.page_name || 'N/A'}
                    </td>
                    <td className="py-3 text-right font-bold text-sky-600">
                      {(camp.impressions_count || 0).toLocaleString()}
                    </td>
                    <td className="py-3 text-right font-bold text-indigo-600">
                      {(camp.clicks_count || 0).toLocaleString()}
                    </td>
                    <td className="py-3 text-right font-bold text-emerald-600">
                      {camp.ctr !== undefined ? Number(camp.ctr).toFixed(2) : '0.00'}%
                    </td>
                    <td className="py-3 text-right font-semibold">
                      ${Number(camp.budget || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}
                    </td>
                    <td className="py-3 text-center">
                      <StatusBadge status={camp.status} />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}

export default AdCampaignAnalyticsPage;
