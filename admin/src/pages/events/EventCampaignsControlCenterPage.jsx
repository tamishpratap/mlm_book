import { useState, useEffect, useCallback } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import {
  Calendar,
  Search,
  CheckCircle,
  XCircle,
  PauseCircle,
  PlayCircle,
  StopCircle,
  Eye,
  AlertTriangle,
  DollarSign,
  RefreshCw,
  Clock,
  TrendingUp,
  Users,
  ShieldCheck,
  ExternalLink,
  Sliders,
  Layers,
  Award,
} from 'lucide-react';
import { Button } from 'primereact/button';
import { InputText } from 'primereact/inputtext';
import { Dropdown } from 'primereact/dropdown';
import { Paginator } from 'primereact/paginator';
import { Dialog } from 'primereact/dialog';
import { AdminSearchInput } from '../../components/common/AdminSearchInput';
import { PageHeader } from '../../components/common/PageHeader';
import { StatusBadge } from '../../components/common/StatusBadge';
import { EmptyState } from '../../components/common/EmptyState';
import { ErrorState } from '../../components/common/ErrorState';
import { LoadingSpinner } from '../../components/common/LoadingSpinner';
import { AdminDateFilter } from '../../components/admin/AdminDateFilter';
import { useToast } from '../../hooks/useToast';
import { eventCampaignsApi } from '../../api';

const STATUS_OPTIONS = [
  { label: 'All Statuses', value: '' },
  { label: 'Active', value: 'active' },
  { label: 'Budget Low', value: 'low_budget' },
  { label: 'Budget Exhausted', value: 'exhausted' },
  { label: 'Paused', value: 'paused' },
  { label: 'Stopped', value: 'stopped' },
  { label: 'Completed', value: 'completed' },
  { label: 'Draft', value: 'draft' },
];

const BUDGET_STATE_OPTIONS = [
  { label: 'All Budgets', value: '' },
  { label: 'Low Budget (Approaching Exhaustion)', value: 'low_budget' },
  { label: 'Exhausted (Below Min Reward)', value: 'exhausted' },
  { label: 'Funded (> $0.00)', value: 'funded' },
];

export function EventCampaignsControlCenterPage() {
  const { showSuccess, showError } = useToast();
  const [searchParams, setSearchParams] = useSearchParams();

  const [campaigns, setCampaigns] = useState([]);
  const [metrics, setMetrics] = useState({
    total_event_campaigns: 0,
    active_campaigns: 0,
    paused_campaigns: 0,
    stopped_campaigns: 0,
    low_budget_campaigns: 0,
    exhausted_campaigns: 0,
    total_budget_funded: 0,
    total_spent: 0,
    total_remaining: 0,
    total_rewards_paid: 0,
    total_interested: 0,
    total_rewarded: 0,
    minimum_event_reward: 0.05,
  });

  const [ruleHealth, setRuleHealth] = useState({
    active_rules_count: 0,
    is_valid: true,
    errors: [],
    has_baseline_tier: true,
    min_reward_usd: 0.05,
    max_reward_usd: 0.05,
  });

  const [search, setSearch] = useState(searchParams.get('q') || '');
  const [status, setStatus] = useState(searchParams.get('status') || '');
  const [budgetState, setBudgetState] = useState(searchParams.get('budget_state') || '');
  const [dateFrom, setDateFrom] = useState(searchParams.get('date_from') || '');
  const [dateTo, setDateTo] = useState(searchParams.get('date_to') || '');
  const [preset, setPreset] = useState(searchParams.get('preset') || '');
  const [pagination, setPagination] = useState({
    page: parseInt(searchParams.get('page') || '1', 10),
    perPage: 15,
    total: 0,
  });
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [error, setError] = useState(null);

  // Detail Modal State
  const [detailCampaign, setDetailCampaign] = useState(null);
  const [showDetailModal, setShowDetailModal] = useState(false);
  const [loadingDetail, setLoadingDetail] = useState(false);

  // Confirmation Modal State
  const [confirmModal, setConfirmModal] = useState({
    show: false,
    title: '',
    message: '',
    action: null,
    targetId: null,
  });

  const fetchCampaigns = useCallback(
    (page = 1) => {
      setLoading(true);
      setError(null);

      const params = {
        page,
        per_page: pagination.perPage,
        q: search || undefined,
        status: status || undefined,
        budget_state: budgetState || undefined,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
        preset: (!dateFrom && !dateTo && preset && preset !== 'all' && preset !== 'custom') ? preset : undefined,
      };

      eventCampaignsApi
        .getCampaigns(params)
        .then((res) => {
          if (res?.success) {
            setCampaigns(res.campaigns?.data || []);
            setMetrics(res.metrics || {});
            setRuleHealth(res.rule_health || {});
            setPagination((prev) => ({
              ...prev,
              page: res.campaigns?.current_page || page,
              total: res.campaigns?.total || 0,
            }));
          } else {
            setError(res?.message || 'Failed to load event campaigns.');
          }
        })
        .catch((err) => {
          setError(err.message || 'Failed to load event campaigns.');
        })
        .finally(() => {
          setLoading(false);
        });
    },
    [pagination.perPage, search, status, budgetState, dateFrom, dateTo, preset]
  );

  useEffect(() => {
    fetchCampaigns(pagination.page);
  }, [fetchCampaigns, pagination.page]);

  const handleDateFilterChange = ({ startDate, endDate, preset: p }) => {
    setDateFrom(startDate || '');
    setDateTo(endDate || '');
    setPreset(p || '');
    setPagination((prev) => ({ ...prev, page: 1 }));
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      if (startDate) next.set('date_from', startDate);
      else next.delete('date_from');
      if (endDate) next.set('date_to', endDate);
      else next.delete('date_to');
      if (p && p !== 'all' && p !== 'custom') next.set('preset', p);
      else next.delete('preset');
      next.set('page', '1');
      return next;
    });
  };

  const handleDateFilterReset = () => {
    setDateFrom('');
    setDateTo('');
    setPreset('');
    setPagination((prev) => ({ ...prev, page: 1 }));
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      next.delete('date_from');
      next.delete('date_to');
      next.delete('preset');
      next.set('page', '1');
      return next;
    });
  };

  const handleInspect = (campaignId) => {
    setLoadingDetail(true);
    setShowDetailModal(true);
    eventCampaignsApi
      .getCampaign(campaignId)
      .then((res) => {
        if (res?.success) {
          setDetailCampaign(res);
        } else {
          showError(res?.message || 'Failed to load campaign details.');
          setShowDetailModal(false);
        }
      })
      .catch((err) => {
        showError(err.message || 'Failed to load campaign details.');
        setShowDetailModal(false);
      })
      .finally(() => {
        setLoadingDetail(false);
      });
  };

  const handlePause = (campaignId) => {
    setConfirmModal({
      show: true,
      title: 'Pause Event Campaign',
      message: 'Are you sure you want to pause this event campaign? Qualified members will temporarily not be eligible for rewards until resumed.',
      action: () => {
        setActionLoading(true);
        eventCampaignsApi
          .pauseCampaign(campaignId)
          .then((res) => {
            if (res?.success) {
              showSuccess(res.message || 'Campaign paused successfully.');
              fetchCampaigns(pagination.page);
              if (showDetailModal) handleInspect(campaignId);
            } else {
              showError(res?.message || 'Failed to pause campaign.');
            }
          })
          .catch((err) => showError(err.message || 'Failed to pause campaign.'))
          .finally(() => {
            setActionLoading(false);
            setConfirmModal((prev) => ({ ...prev, show: false }));
          });
      },
      targetId: campaignId,
    });
  };

  const handleResume = (campaignId) => {
    setConfirmModal({
      show: true,
      title: 'Resume Event Campaign',
      message: 'Resume this event campaign and re-enable member reward eligibility? (Requires remaining budget >= minimum valid reward)',
      action: () => {
        setActionLoading(true);
        eventCampaignsApi
          .resumeCampaign(campaignId)
          .then((res) => {
            if (res?.success) {
              showSuccess(res.message || 'Campaign resumed successfully.');
              fetchCampaigns(pagination.page);
              if (showDetailModal) handleInspect(campaignId);
            } else {
              showError(res?.message || 'Failed to resume campaign.');
            }
          })
          .catch((err) => showError(err.message || 'Failed to resume campaign.'))
          .finally(() => {
            setActionLoading(false);
            setConfirmModal((prev) => ({ ...prev, show: false }));
          });
      },
      targetId: campaignId,
    });
  };

  const handleStop = (campaignId) => {
    setConfirmModal({
      show: true,
      title: 'Stop Event Campaign',
      message: 'Are you sure you want to permanently stop this event campaign? Unspent budget will remain safe and historical rewards are preserved.',
      action: () => {
        setActionLoading(true);
        eventCampaignsApi
          .stopCampaign(campaignId)
          .then((res) => {
            if (res?.success) {
              showSuccess(res.message || 'Campaign stopped successfully.');
              fetchCampaigns(pagination.page);
              if (showDetailModal) handleInspect(campaignId);
            } else {
              showError(res?.message || 'Failed to stop campaign.');
            }
          })
          .catch((err) => showError(err.message || 'Failed to stop campaign.'))
          .finally(() => {
            setActionLoading(false);
            setConfirmModal((prev) => ({ ...prev, show: false }));
          });
      },
      targetId: campaignId,
    });
  };

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <PageHeader
        title="Event Campaigns Control Center"
        subtitle="Authoritative platform monitoring, campaign lifecycle control, and financial auditing for Paid Events."
        badge={{ text: 'Phase 16', color: 'emerald' }}
        actions={
          <div className="flex items-center gap-2">
            <Link to="/admin/events/reward-rules">
              <Button
                label="Reward Rules Engine"
                icon={<Sliders className="w-4 h-4 mr-2" />}
                className="p-button-outlined p-button-sm border-gray-300 text-gray-700 hover:bg-gray-50"
              />
            </Link>
            <Button
              label="Refresh"
              icon={<RefreshCw className={`w-4 h-4 mr-2 ${loading ? 'animate-spin' : ''}`} />}
              className="p-button-primary p-button-sm bg-indigo-600 hover:bg-indigo-700"
              onClick={() => fetchCampaigns(pagination.page)}
              disabled={loading}
            />
          </div>
        }
      />

      {/* Phase 10-11 Reward Rule Health Status Card */}
      <div className={`p-4 rounded-xl border flex flex-col md:flex-row items-start md:items-center justify-between gap-4 ${
        ruleHealth.is_valid && ruleHealth.active_rules_count > 0
          ? 'bg-emerald-50 border-emerald-200 text-emerald-900'
          : 'bg-amber-50 border-amber-200 text-amber-900'
      }`}>
        <div className="flex items-center gap-3">
          <div className={`p-2.5 rounded-lg ${ruleHealth.is_valid ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}`}>
            <Award className="w-6 h-6" />
          </div>
          <div>
            <div className="flex items-center gap-2">
              <h4 className="font-semibold text-sm">Reward Rules Engine Health</h4>
              <span className={`px-2 py-0.5 rounded text-xs font-semibold ${
                ruleHealth.is_valid ? 'bg-emerald-200 text-emerald-800' : 'bg-amber-200 text-amber-800'
              }`}>
                {ruleHealth.is_valid ? 'Validated & Operational' : 'Action Required'}
              </span>
            </div>
            <p className="text-xs mt-0.5 text-gray-600">
              {ruleHealth.active_rules_count} active rule tiers configured (Min: ${Number(ruleHealth.min_reward_usd || 0.05).toFixed(4)} USD, Max: ${Number(ruleHealth.max_reward_usd || 0.05).toFixed(4)} USD)
              {!ruleHealth.has_baseline_tier && ' • Warning: Baseline 0-referral tier is missing!'}
            </p>
          </div>
        </div>
        <Link to="/admin/events/reward-rules">
          <Button
            label="Configure Rules"
            icon={<ExternalLink className="w-4 h-4 ml-1.5" />}
            iconPos="right"
            className="p-button-sm p-button-outlined text-xs"
          />
        </Link>
      </div>

      {/* 6-Grid Metric KPI Cards */}
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <div className="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
          <div className="flex items-center justify-between text-gray-500 text-xs font-medium">
            <span>Event Campaigns</span>
            <Layers className="w-4 h-4 text-indigo-500" />
          </div>
          <p className="text-xl font-bold text-gray-900 mt-2">{metrics.total_event_campaigns ?? 0}</p>
          <p className="text-[11px] text-gray-500 mt-0.5">Total created</p>
        </div>

        <div className="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
          <div className="flex items-center justify-between text-gray-500 text-xs font-medium">
            <span>Active Running</span>
            <CheckCircle className="w-4 h-4 text-emerald-500" />
          </div>
          <p className="text-xl font-bold text-emerald-600 mt-2">{metrics.active_campaigns ?? 0}</p>
          <p className="text-[11px] text-emerald-700 mt-0.5">Live & rewardable</p>
        </div>

        <div className="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
          <div className="flex items-center justify-between text-gray-500 text-xs font-medium">
            <span>Budget Low</span>
            <AlertTriangle className="w-4 h-4 text-amber-500" />
          </div>
          <p className="text-xl font-bold text-amber-600 mt-2">{metrics.low_budget_campaigns ?? 0}</p>
          <p className="text-[11px] text-amber-700 mt-0.5">Near exhaustion</p>
        </div>

        <div className="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
          <div className="flex items-center justify-between text-gray-500 text-xs font-medium">
            <span>Exhausted</span>
            <XCircle className="w-4 h-4 text-rose-500" />
          </div>
          <p className="text-xl font-bold text-rose-600 mt-2">{metrics.exhausted_campaigns ?? 0}</p>
          <p className="text-[11px] text-rose-700 mt-0.5">Auto-stopped (&lt; min)</p>
        </div>

        <div className="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
          <div className="flex items-center justify-between text-gray-500 text-xs font-medium">
            <span>Total Funded</span>
            <DollarSign className="w-4 h-4 text-blue-500" />
          </div>
          <p className="text-xl font-bold text-gray-900 mt-2">
            ${Number(metrics.total_budget_funded ?? 0).toFixed(2)}
          </p>
          <p className="text-[11px] text-gray-500 mt-0.5">Remaining: ${Number(metrics.total_remaining ?? 0).toFixed(2)}</p>
        </div>

        <div className="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
          <div className="flex items-center justify-between text-gray-500 text-xs font-medium">
            <span>Rewards Paid</span>
            <TrendingUp className="w-4 h-4 text-purple-500" />
          </div>
          <p className="text-xl font-bold text-purple-600 mt-2">
            ${Number(metrics.total_rewards_paid ?? 0).toFixed(2)}
          </p>
          <p className="text-[11px] text-purple-700 mt-0.5">{metrics.total_rewarded ?? 0} claims</p>
        </div>
      </div>

      {/* Filter Toolbar */}
      <div className="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-wrap items-center gap-3">
        <div className="flex-1 min-w-[220px]">
          <AdminSearchInput
            value={search}
            onChange={(e) => {
              setSearch(e.target.value);
            }}
            onClear={() => {
              setSearch('');
              setPagination((prev) => ({ ...prev, page: 1 }));
              setSearchParams((prev) => {
                const next = new URLSearchParams(prev);
                next.delete('q');
                next.set('page', '1');
                return next;
              });
            }}
            onSubmit={(e) => {
              e.preventDefault();
              setPagination((prev) => ({ ...prev, page: 1 }));
              setSearchParams((prev) => {
                const next = new URLSearchParams(prev);
                if (search) next.set('q', search);
                else next.delete('q');
                next.set('page', '1');
                return next;
              });
            }}
            placeholder="Search event title, creator, or campaign ID..."
          />
        </div>

        <Dropdown
          value={status}
          options={STATUS_OPTIONS}
          onChange={(e) => {
            setStatus(e.value);
            setPagination((prev) => ({ ...prev, page: 1 }));
            setSearchParams((prev) => {
              const next = new URLSearchParams(prev);
              if (e.value) next.set('status', e.value);
              else next.delete('status');
              next.set('page', '1');
              return next;
            });
          }}
          placeholder="Status"
          className="w-40 text-sm border-gray-300 rounded-lg"
        />

        <Dropdown
          value={budgetState}
          options={BUDGET_STATE_OPTIONS}
          onChange={(e) => {
            setBudgetState(e.value);
            setPagination((prev) => ({ ...prev, page: 1 }));
            setSearchParams((prev) => {
              const next = new URLSearchParams(prev);
              if (e.value) next.set('budget_state', e.value);
              else next.delete('budget_state');
              next.set('page', '1');
              return next;
            });
          }}
          placeholder="Budget State"
          className="w-48 text-sm border-gray-300 rounded-lg"
        />

        <AdminDateFilter
          startDate={dateFrom}
          endDate={dateTo}
          preset={preset}
          onChange={handleDateFilterChange}
          onReset={handleDateFilterReset}
        />

        {(search || status || budgetState || dateFrom || dateTo || preset) && (
          <Button
            label="Clear All"
            icon={<XCircle className="w-4 h-4 mr-1" />}
            className="p-button-text p-button-sm text-gray-500 hover:text-gray-700 text-xs"
            onClick={() => {
              setSearch('');
              setStatus('');
              setBudgetState('');
              setDateFrom('');
              setDateTo('');
              setPreset('');
              setPagination((prev) => ({ ...prev, page: 1 }));
              setSearchParams(new URLSearchParams());
            }}
          />
        )}
      </div>

      {/* Campaigns Table */}
      <div className="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        {loading ? (
          <div className="p-12 flex justify-center">
            <LoadingSpinner message="Loading Event campaigns..." />
          </div>
        ) : error ? (
          <ErrorState message={error} onRetry={() => fetchCampaigns(pagination.page)} />
        ) : campaigns.length === 0 ? (
          <EmptyState
            icon={Calendar}
            title="No Event Campaigns Found"
            description="No event campaigns match the selected filters."
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-gray-600">
              <thead className="bg-gray-50 text-gray-700 font-semibold border-b border-gray-200 text-xs uppercase tracking-wider">
                <tr>
                  <th className="px-4 py-3.5">Campaign & Event</th>
                  <th className="px-4 py-3.5">Creator</th>
                  <th className="px-4 py-3.5">Status</th>
                  <th className="px-4 py-3.5">Budget & Spend</th>
                  <th className="px-4 py-3.5">Engagement</th>
                  <th className="px-4 py-3.5 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {campaigns.map((c) => {
                  const spent = Number(c.budget?.spent_amount || 0);
                  const total = Number(c.budget?.total_funded || 0);
                  const remaining = Number(c.budget?.remaining_amount || 0);
                  const percentSpent = total > 0 ? Math.min(100, Math.round((spent / total) * 100)) : 0;

                  return (
                    <tr key={c.id} className="hover:bg-gray-50/75 transition-colors">
                      {/* Campaign & Event */}
                      <td className="px-4 py-3">
                        <div className="font-semibold text-gray-900">{c.event?.title || c.campaign_name}</div>
                        <div className="text-xs text-gray-500 flex items-center gap-1.5 mt-0.5">
                          <code className="text-[11px] bg-gray-100 px-1 py-0.5 rounded text-gray-700 font-mono">
                            {c.campaign_id}
                          </code>
                          {c.event?.event_type && (
                            <span className="capitalize px-1.5 py-0.5 rounded text-[10px] bg-indigo-50 text-indigo-700 font-medium">
                              {c.event.event_type}
                            </span>
                          )}
                        </div>
                      </td>

                      {/* Creator */}
                      <td className="px-4 py-3">
                        <div className="font-medium text-gray-900 text-xs">{c.creator?.name || 'Unknown'}</div>
                        <div className="text-[11px] text-gray-500 flex items-center gap-1 mt-0.5">
                          <span>{c.creator?.email}</span>
                          {c.creator?.is_mobile_verified && (
                            <ShieldCheck className="w-3.5 h-3.5 text-emerald-600 inline" title="Mobile Verified" />
                          )}
                        </div>
                      </td>

                      {/* Status */}
                      <td className="px-4 py-3">
                        <div className="flex flex-col items-start gap-1">
                          {c.is_exhausted ? (
                            <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800">
                              Budget Exhausted
                            </span>
                          ) : c.is_low_budget ? (
                            <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800">
                              Budget Low
                            </span>
                          ) : (
                            <StatusBadge status={c.status} />
                          )}
                        </div>
                      </td>

                      {/* Budget & Spend */}
                      <td className="px-4 py-3 min-w-[160px]">
                        <div className="flex justify-between text-xs font-medium text-gray-900 mb-1">
                          <span>${spent.toFixed(2)} spent</span>
                          <span className="text-gray-500">${total.toFixed(2)} total</span>
                        </div>
                        <div className="w-full bg-gray-200 rounded-full h-1.5 overflow-hidden">
                          <div
                            className={`h-1.5 rounded-full ${
                              c.is_exhausted ? 'bg-rose-500' : c.is_low_budget ? 'bg-amber-500' : 'bg-emerald-500'
                            }`}
                            style={{ width: `${percentSpent}%` }}
                          />
                        </div>
                        <div className="text-[11px] text-gray-500 mt-1">
                          Remaining: <span className="font-medium text-gray-800">${remaining.toFixed(4)} USD</span>
                        </div>
                      </td>

                      {/* Engagement */}
                      <td className="px-4 py-3">
                        <div className="text-xs text-gray-900 font-medium flex items-center gap-1">
                          <Users className="w-3.5 h-3.5 text-gray-400" />
                          <span>{c.participation?.interested_count ?? 0} interested</span>
                        </div>
                        <div className="text-[11px] text-emerald-700 font-medium mt-0.5">
                          {c.participation?.rewarded_count ?? 0} rewarded (${Number(c.participation?.total_rewards_paid_usd ?? 0).toFixed(2)})
                        </div>
                      </td>

                      {/* Actions */}
                      <td className="px-4 py-3 text-right">
                        <div className="flex items-center justify-end gap-1">
                          <Button
                            icon={<Eye className="w-4 h-4" />}
                            className="p-button-text p-button-sm p-button-rounded text-gray-600 hover:text-indigo-600"
                            onClick={() => handleInspect(c.id)}
                            title="Inspect Details"
                          />
                          {c.actions?.can_pause && (
                            <Button
                              icon={<PauseCircle className="w-4 h-4 text-amber-600" />}
                              className="p-button-text p-button-sm p-button-rounded hover:bg-amber-50"
                              onClick={() => handlePause(c.id)}
                              disabled={actionLoading}
                              title="Pause Campaign"
                            />
                          )}
                          {c.actions?.can_resume && (
                            <Button
                              icon={<PlayCircle className="w-4 h-4 text-emerald-600" />}
                              className="p-button-text p-button-sm p-button-rounded hover:bg-emerald-50"
                              onClick={() => handleResume(c.id)}
                              disabled={actionLoading}
                              title="Resume Campaign"
                            />
                          )}
                          {c.actions?.can_stop && (
                            <Button
                              icon={<StopCircle className="w-4 h-4 text-rose-600" />}
                              className="p-button-text p-button-sm p-button-rounded hover:bg-rose-50"
                              onClick={() => handleStop(c.id)}
                              disabled={actionLoading}
                              title="Stop Campaign"
                            />
                          )}
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}

        {/* Paginator */}
        {pagination.total > pagination.perPage && (
          <div className="border-t border-gray-200 px-4 py-3 bg-gray-50 flex justify-end">
            <Paginator
              first={(pagination.page - 1) * pagination.perPage}
              rows={pagination.perPage}
              totalRecords={pagination.total}
              onPageChange={(e) => fetchCampaigns(e.page + 1)}
              className="border-none bg-transparent"
            />
          </div>
        )}
      </div>

      {/* Detailed Inspection Dialog */}
      <Dialog
        header={
          <div className="flex items-center gap-2">
            <Calendar className="w-5 h-5 text-indigo-600" />
            <span className="font-bold text-gray-900">Event Campaign Financial & Operational Audit</span>
          </div>
        }
        visible={showDetailModal}
        onHide={() => setShowDetailModal(false)}
        style={{ width: '90vw', maxWidth: '780px' }}
        modal
        className="rounded-2xl overflow-hidden"
      >
        {loadingDetail || !detailCampaign?.campaign ? (
          <div className="p-12 flex justify-center">
            <LoadingSpinner message="Auditing campaign data..." />
          </div>
        ) : (
          <div className="space-y-6 pt-2">
            {/* Event & Creator Profile */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-xl border border-gray-200">
              <div>
                <h5 className="text-xs uppercase font-semibold text-gray-500 tracking-wider">Event Details</h5>
                <h3 className="font-bold text-gray-900 text-base mt-1">{detailCampaign.campaign.event?.title}</h3>
                <p className="text-xs text-gray-600 mt-1">
                  Location: {detailCampaign.campaign.event?.location || 'Online'}
                </p>
                <div className="mt-2 flex items-center gap-2">
                  <code className="text-xs bg-white border border-gray-200 px-2 py-0.5 rounded font-mono text-gray-700">
                    ID: {detailCampaign.campaign.campaign_id}
                  </code>
                  <StatusBadge status={detailCampaign.campaign.status} />
                </div>
              </div>

              <div>
                <h5 className="text-xs uppercase font-semibold text-gray-500 tracking-wider">Creator & Verification</h5>
                <p className="font-medium text-gray-900 text-sm mt-1">{detailCampaign.campaign.creator?.name}</p>
                <p className="text-xs text-gray-600">{detailCampaign.campaign.creator?.email}</p>
                <p className="text-xs text-gray-600">{detailCampaign.campaign.creator?.phone || 'No phone recorded'}</p>
                <div className="mt-2">
                  {detailCampaign.campaign.creator?.is_mobile_verified ? (
                    <span className="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded">
                      <ShieldCheck className="w-3.5 h-3.5" /> Mobile / WhatsApp Verified
                    </span>
                  ) : (
                    <span className="inline-flex items-center gap-1 text-xs font-semibold text-rose-700 bg-rose-100 px-2 py-0.5 rounded">
                      <XCircle className="w-3.5 h-3.5" /> Mobile Not Verified
                    </span>
                  )}
                </div>
              </div>
            </div>

            {/* Financial Accounting Audit Table */}
            <div>
              <h5 className="text-xs uppercase font-semibold text-gray-700 tracking-wider mb-2 flex items-center justify-between">
                <span>Authoritative Financial Accounting</span>
                {detailCampaign.campaign.financials?.reconciliation?.is_balanced ? (
                  <span className="text-emerald-700 font-bold text-xs flex items-center gap-1">
                    <CheckCircle className="w-3.5 h-3.5" /> Perfectly Reconciled
                  </span>
                ) : (
                  <span className="text-rose-700 font-bold text-xs flex items-center gap-1">
                    <XCircle className="w-3.5 h-3.5" /> Discrepancy Detected
                  </span>
                )}
              </h5>
              <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                <div className="bg-white border border-gray-200 p-2.5 rounded-lg">
                  <span className="text-gray-500 block">Initial Budget</span>
                  <span className="font-bold text-gray-900 text-sm">
                    ${Number(detailCampaign.campaign.financials?.initial_budget ?? 0).toFixed(4)}
                  </span>
                </div>
                <div className="bg-white border border-gray-200 p-2.5 rounded-lg">
                  <span className="text-gray-500 block">Additional Funds</span>
                  <span className="font-bold text-gray-900 text-sm">
                    +${Number(detailCampaign.campaign.financials?.additional_funding ?? 0).toFixed(4)}
                  </span>
                </div>
                <div className="bg-white border border-gray-200 p-2.5 rounded-lg">
                  <span className="text-gray-500 block">Total Funded</span>
                  <span className="font-bold text-indigo-600 text-sm">
                    ${Number(detailCampaign.campaign.financials?.total_funded ?? 0).toFixed(4)}
                  </span>
                </div>
                <div className="bg-white border border-gray-200 p-2.5 rounded-lg">
                  <span className="text-gray-500 block">Remaining Budget</span>
                  <span className="font-bold text-emerald-600 text-sm">
                    ${Number(detailCampaign.campaign.financials?.remaining_amount ?? 0).toFixed(4)}
                  </span>
                </div>
              </div>
            </div>

            {/* Engagement & Rewards Summary */}
            <div className="border border-gray-200 rounded-xl p-4 bg-gray-50/50">
              <h5 className="text-xs uppercase font-semibold text-gray-700 tracking-wider mb-2">
                Participant Engagement & Historical Payouts
              </h5>
              <div className="grid grid-cols-3 gap-3 text-center">
                <div className="bg-white p-3 rounded-lg border border-gray-200">
                  <p className="text-lg font-bold text-gray-900">
                    {detailCampaign.campaign.participation?.interested_count ?? 0}
                  </p>
                  <p className="text-xs text-gray-500">Interested Attendees</p>
                </div>
                <div className="bg-white p-3 rounded-lg border border-gray-200">
                  <p className="text-lg font-bold text-emerald-600">
                    {detailCampaign.campaign.participation?.rewarded_count ?? 0}
                  </p>
                  <p className="text-xs text-emerald-700 font-medium">Rewarded Members</p>
                </div>
                <div className="bg-white p-3 rounded-lg border border-gray-200">
                  <p className="text-lg font-bold text-purple-600">
                    ${Number(detailCampaign.campaign.participation?.total_rewards_paid_usd ?? 0).toFixed(4)}
                  </p>
                  <p className="text-xs text-purple-700 font-medium">Total Rewards Paid</p>
                </div>
              </div>
            </div>

            {/* Recent Participants List */}
            <div>
              <h5 className="text-xs uppercase font-semibold text-gray-700 tracking-wider mb-2">
                Recent Participants & Tier Snapshots
              </h5>
              {detailCampaign.recent_participants?.length > 0 ? (
                <div className="border border-gray-200 rounded-xl overflow-hidden">
                  <table className="w-full text-xs text-left">
                    <thead className="bg-gray-100 text-gray-700 font-medium">
                      <tr>
                        <th className="px-3 py-2">Participant</th>
                        <th className="px-3 py-2">Responded</th>
                        <th className="px-3 py-2">Reward Status</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200">
                      {detailCampaign.recent_participants.map((p, idx) => (
                        <tr key={idx} className="hover:bg-gray-50">
                          <td className="px-3 py-2">
                            <span className="font-semibold text-gray-900">{p.name}</span>
                            <span className="block text-gray-500 text-[11px]">{p.email}</span>
                          </td>
                          <td className="px-3 py-2 text-gray-600">
                            {p.responded_at ? new Date(p.responded_at).toLocaleDateString() : 'N/A'}
                          </td>
                          <td className="px-3 py-2">
                            {p.is_rewarded ? (
                              <span className="inline-flex items-center gap-1 font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">
                                +${Number(p.reward?.reward_amount_usd ?? 0).toFixed(4)} USD (Tier {p.reward?.rule_tier})
                              </span>
                            ) : (
                              <span className="text-gray-500">Unpaid / Unrewarded</span>
                            )}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : (
                <p className="text-xs text-gray-500 italic">No participant records found for this event.</p>
              )}
            </div>
          </div>
        )}
      </Dialog>

      {/* Action Confirmation Dialog */}
      <Dialog
        header={confirmModal.title}
        visible={confirmModal.show}
        onHide={() => setConfirmModal((prev) => ({ ...prev, show: false }))}
        style={{ width: '450px' }}
        modal
        footer={
          <div className="flex justify-end gap-2">
            <Button
              label="Cancel"
              className="p-button-text p-button-sm text-gray-600"
              onClick={() => setConfirmModal((prev) => ({ ...prev, show: false }))}
              disabled={actionLoading}
            />
            <Button
              label="Confirm Action"
              className="p-button-primary p-button-sm bg-indigo-600 hover:bg-indigo-700"
              onClick={confirmModal.action}
              loading={actionLoading}
            />
          </div>
        }
      >
        <p className="text-sm text-gray-600">{confirmModal.message}</p>
      </Dialog>
    </div>
  );
}

export default EventCampaignsControlCenterPage;
