import { useState, useEffect, useCallback } from 'react';
import { useSearchParams } from 'react-router-dom';
import {
  Gift,
  Search,
  CheckCircle,
  DollarSign,
  User,
  Megaphone,
  ExternalLink,
  ShieldCheck,
} from 'lucide-react';
import { Button } from 'primereact/button';
import { InputText } from 'primereact/inputtext';
import { Dropdown } from 'primereact/dropdown';
import { Paginator } from 'primereact/paginator';
import { AdminSearchInput } from '../../components/common/AdminSearchInput';
import { PageHeader } from '../../components/common/PageHeader';
import { StatusBadge } from '../../components/common/StatusBadge';
import { EmptyState } from '../../components/common/EmptyState';
import { ErrorState } from '../../components/common/ErrorState';
import { LoadingSpinner } from '../../components/common/LoadingSpinner';
import { useToast } from '../../hooks/useToast';
import { AdminDateFilter } from '../../components/admin/AdminDateFilter';
import { adCampaignsApi } from '../../api';

const STATUS_OPTIONS = [
  { label: 'All Statuses', value: '' },
  { label: 'Credited', value: 'credited' },
  { label: 'Pending', value: 'pending' },
  { label: 'Rejected', value: 'rejected' },
];

const TIER_OPTIONS = [
  { label: 'All Referral Tiers', value: '' },
  { label: 'Tier: 0–5 Referrals', value: '0-5' },
  { label: 'Tier: 6–14 Referrals', value: '6-14' },
  { label: 'Tier: 15+ Referrals', value: '15+' },
];

export function AdminRewardHistoryPage() {
  const { showError } = useToast();
  const [searchParams, setSearchParams] = useSearchParams();

  const [rewards, setRewards] = useState([]);
  const [metrics, setMetrics] = useState({
    total_rewards_count: 0,
    total_rewards_paid: 0,
    total_verified_members: 0,
    total_rewarded_campaigns: 0,
    reward_rate_range: '$0.025 – $0.050 USD',
    min_reward_usd: 0.025,
    max_reward_usd: 0.050,
  });

  const [search, setSearch] = useState(searchParams.get('q') || '');
  const [status, setStatus] = useState(searchParams.get('status') || '');
  const [tier, setTier] = useState(searchParams.get('tier') || '');
  const [dateFrom, setDateFrom] = useState(searchParams.get('date_from') || '');
  const [dateTo, setDateTo] = useState(searchParams.get('date_to') || '');
  const [datePreset, setDatePreset] = useState(searchParams.get('date_preset') || '');
  const [pagination, setPagination] = useState({
    page: parseInt(searchParams.get('page') || '1', 10),
    perPage: 15,
    total: 0,
  });

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const fetchRewards = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const params = {
        page: pagination.page,
        q: search || undefined,
        status: status || undefined,
        tier: tier || undefined,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
        date_preset: (!dateFrom && !dateTo && datePreset && datePreset !== 'all' && datePreset !== 'custom') ? datePreset : undefined,
      };

      const res = await adCampaignsApi.getRewards(params);
      if (res.success) {
        setRewards(res.rewards?.data || []);
        setPagination((prev) => ({
          ...prev,
          total: res.rewards?.total || 0,
        }));
        if (res.metrics) {
          setMetrics(res.metrics);
        }
      }
    } catch (err) {
      console.error('Failed to load ad rewards history:', err);
      setError(err.message || 'Failed to load ad rewards ledger.');
      showError('Failed to load reward history.');
    } finally {
      setLoading(false);
    }
  }, [pagination.page, search, status, tier, dateFrom, dateTo, datePreset, showError]);

  useEffect(() => {
    fetchRewards();
  }, [fetchRewards]);

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    setPagination((prev) => ({ ...prev, page: 1 }));
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      if (search) next.set('q', search);
      else next.delete('q');
      next.set('page', '1');
      return next;
    });
  };

  const handleStatusChange = (val) => {
    setStatus(val);
    setPagination((prev) => ({ ...prev, page: 1 }));
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      if (val) next.set('status', val);
      else next.delete('status');
      next.set('page', '1');
      return next;
    });
  };

  const handleTierChange = (val) => {
    setTier(val);
    setPagination((prev) => ({ ...prev, page: 1 }));
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      if (val) next.set('tier', val);
      else next.delete('tier');
      next.set('page', '1');
      return next;
    });
  };

  const handleDateFilterChange = ({ startDate, endDate, preset }) => {
    setDateFrom(startDate || '');
    setDateTo(endDate || '');
    setDatePreset(preset || '');
    setPagination((prev) => ({ ...prev, page: 1 }));
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      if (startDate) next.set('date_from', startDate);
      else next.delete('date_from');
      if (endDate) next.set('date_to', endDate);
      else next.delete('date_to');
      if (preset && preset !== 'all' && preset !== 'custom') next.set('date_preset', preset);
      else next.delete('date_preset');
      next.set('page', '1');
      return next;
    });
  };

  const handleDateFilterReset = () => {
    setDateFrom('');
    setDateTo('');
    setDatePreset('');
    setPagination((prev) => ({ ...prev, page: 1 }));
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      next.delete('date_from');
      next.delete('date_to');
      next.delete('date_preset');
      next.set('page', '1');
      return next;
    });
  };

  const handlePageChange = (e) => {
    const nextPage = e.page + 1;
    setPagination((prev) => ({
      ...prev,
      page: nextPage,
    }));
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      next.set('page', String(nextPage));
      return next;
    });
  };

  return (
    <div className="space-y-6">
      <PageHeader
        title="Reward History & Verified Visits"
        subtitle="Comprehensive ledger of verified member landing-page interaction rewards (dynamic tiers based on direct verified referrals), automated campaign budget depletions, and audit trails."
        breadcrumbs={[
          { label: 'Admin', to: '/admin/dashboard' },
          { label: 'Ads Management', to: '/admin/ad-campaigns' },
          { label: 'Reward History' },
        ]}
      />

      {/* KPI Metric Cards */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold uppercase tracking-wider text-slate-400">Total Rewards Count</span>
            <div className="p-2 rounded-xl bg-emerald-100 text-emerald-600">
              <Gift className="w-5 h-5" />
            </div>
          </div>
          <p className="text-2xl font-black text-slate-900 mt-3">
            {Number(metrics.total_rewards_count || 0).toLocaleString()}
          </p>
          <div className="text-xs text-slate-500 mt-1">Total qualifying visit events</div>
        </div>

        <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold uppercase tracking-wider text-slate-400">Total Rewards Paid</span>
            <div className="p-2 rounded-xl bg-indigo-100 text-indigo-600">
              <DollarSign className="w-5 h-5" />
            </div>
          </div>
          <p className="text-2xl font-black text-indigo-600 mt-3">
            ${Number(metrics.total_rewards_paid || 0).toLocaleString('en-US', { minimumFractionDigits: 4 })}
          </p>
          <div className="text-xs text-slate-500 mt-1">{metrics.reward_rate_range || '$0.025 – $0.050 USD'} / visit</div>
        </div>

        <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold uppercase tracking-wider text-slate-400">Verified Members</span>
            <div className="p-2 rounded-xl bg-sky-100 text-sky-600">
              <ShieldCheck className="w-5 h-5" />
            </div>
          </div>
          <p className="text-2xl font-black text-sky-600 mt-3">
            {Number(metrics.total_verified_members || 0).toLocaleString()}
          </p>
          <div className="text-xs text-slate-500 mt-1">Unique rewarded users</div>
        </div>

        <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold uppercase tracking-wider text-slate-400">Campaigns Rewarded</span>
            <div className="p-2 rounded-xl bg-amber-100 text-amber-600">
              <Megaphone className="w-5 h-5" />
            </div>
          </div>
          <p className="text-2xl font-black text-amber-600 mt-3">
            {Number(metrics.total_rewarded_campaigns || 0).toLocaleString()}
          </p>
          <div className="text-xs text-slate-500 mt-1">Active / past ad campaigns</div>
        </div>
      </div>

      {/* Filters Bar */}
      <div className="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row gap-3 items-center justify-between">
        <form onSubmit={handleSearchSubmit} className="flex-1 w-full md:w-auto">
          <AdminSearchInput
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            onClear={() => setSearch('')}
            onSubmit={handleSearchSubmit}
            placeholder="Search member name, email, campaign, or event ID..."
          />
        </form>

        <div className="flex flex-wrap items-center gap-2 w-full md:w-auto justify-end">
          <Dropdown
            value={tier}
            options={TIER_OPTIONS}
            onChange={(e) => handleTierChange(e.value)}
            placeholder="Referral Tier"
            className="p-inputtext-sm bg-white border border-slate-300 rounded-xl"
          />

          <AdminDateFilter
            startDate={dateFrom}
            endDate={dateTo}
            preset={datePreset}
            onChange={handleDateFilterChange}
            onReset={handleDateFilterReset}
          />

          <Dropdown
            value={status}
            options={STATUS_OPTIONS}
            onChange={(e) => handleStatusChange(e.value)}
            placeholder="Status"
            className="p-inputtext-sm bg-white border border-slate-300 rounded-xl"
          />

          <Button
            icon="pi pi-refresh"
            size="small"
            className="p-button-outlined p-button-secondary rounded-xl"
            onClick={fetchRewards}
            tooltip="Refresh Ledger"
          />
        </div>
      </div>

      {/* Rewards Table */}
      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        {loading ? (
          <div className="py-20">
            <LoadingSpinner message="Fetching reward transactions ledger..." />
          </div>
        ) : error ? (
          <ErrorState message={error} onRetry={fetchRewards} />
        ) : rewards.length === 0 ? (
          <EmptyState
            title="No Reward Transactions Found"
            description="There are no qualifying landing-page rewards matching your search or filters."
            icon="pi pi-gift"
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-slate-600">
              <thead className="text-xs font-semibold text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                <tr>
                  <th className="px-4 py-3">Reward ID</th>
                  <th className="px-4 py-3">Campaign</th>
                  <th className="px-4 py-3">Verified Member</th>
                  <th className="px-4 py-3">Referral Tier Snapshot</th>
                  <th className="px-4 py-3 text-right">Reward Amount</th>
                  <th className="px-4 py-3">Qualifying Event</th>
                  <th className="px-4 py-3 text-center">Status</th>
                  <th className="px-4 py-3">Timestamp</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {rewards.map((r) => (
                  <tr key={r.id} className="hover:bg-slate-50 transition-colors">
                    <td className="px-4 py-3 font-mono font-bold text-slate-900">
                      #{r.id}
                    </td>
                    <td className="px-4 py-3">
                      <div className="font-semibold text-slate-800">
                        {r.ad_campaign?.campaign_name || `Campaign #${r.ad_campaign_id}`}
                      </div>
                      <div className="text-xs text-slate-400 font-mono">
                        {r.ad_campaign?.campaign_id || `ID: ${r.ad_campaign_id}`}
                      </div>
                    </td>
                    <td className="px-4 py-3">
                      <div className="font-medium text-slate-800 flex items-center gap-1.5">
                        <User className="w-3.5 h-3.5 text-slate-400" />
                        {r.member?.name || 'Unknown Member'}
                        {r.member?.mobile_verified_at && (
                          <CheckCircle className="w-3.5 h-3.5 text-emerald-500" title="Verified Member" />
                        )}
                      </div>
                      <div className="text-xs text-slate-400">{r.member?.email || r.member?.user_id}</div>
                    </td>
                    <td className="px-4 py-3">
                      <span className="font-semibold text-xs px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-200">
                        {r.tier_label ? `Tier: ${r.tier_label} refs` : 'Default'}
                      </span>
                      <div className="text-xs text-slate-400 mt-0.5">
                        {r.direct_verified_referral_count !== null && r.direct_verified_referral_count !== undefined ? `${r.direct_verified_referral_count} verified refs` : 'Legacy'}
                      </div>
                    </td>
                    <td className="px-4 py-3 text-right">
                      <span className="font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">
                        +${Number(r.reward_amount_usd || 0).toFixed(4)} USD
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <div className="font-mono text-xs text-slate-600 max-w-[180px] truncate" title={r.qualifying_event_id}>
                        {r.qualifying_event_id}
                      </div>
                      {r.landing_page_url && (
                        <a
                          href={r.landing_page_url}
                          target="_blank"
                          rel="noreferrer"
                          className="text-[11px] text-indigo-500 hover:underline flex items-center gap-1 mt-0.5 truncate max-w-[180px]"
                        >
                          <ExternalLink className="w-3 h-3 flex-shrink-0" />
                          {r.landing_page_url}
                        </a>
                      )}
                    </td>
                    <td className="px-4 py-3 text-center">
                      <StatusBadge status={r.status} />
                    </td>
                    <td className="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">
                      {new Date(r.created_at).toLocaleString()}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {/* Pagination */}
        {pagination.total > pagination.perPage && (
          <div className="p-3 border-t border-slate-200 bg-slate-50">
            <Paginator
              first={(pagination.page - 1) * pagination.perPage}
              rows={pagination.perPage}
              totalRecords={pagination.total}
              onPageChange={handlePageChange}
              template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
              currentPageReportTemplate="Showing {first} to {last} of {totalRecords} rewards"
            />
          </div>
        )}
      </div>
    </div>
  );
}

export default AdminRewardHistoryPage;
