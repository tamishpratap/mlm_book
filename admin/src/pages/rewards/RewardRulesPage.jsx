import { useState, useEffect, useRef } from 'react';
import {
  Award,
  Edit2,
  Power,
  RefreshCw,
  AlertTriangle,
  CheckCircle2,
  ShieldCheck,
  Calculator,
  TrendingUp,
  Info,
  Users,
  Layers,
  Sparkles,
  UserCheck,
} from 'lucide-react';
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { Toast } from 'primereact/toast';
import { InputSwitch } from 'primereact/inputswitch';
import { PageHeader } from '../../components/common/PageHeader';
import { StatusBadge } from '../../components/common/StatusBadge';
import { LoadingSpinner } from '../../components/common/LoadingSpinner';
import { ErrorState } from '../../components/common/ErrorState';
import { rewardManagementApi } from '../../api';

// Distinct badge colors for the 5 fixed ranks
const RANK_BADGES = {
  advertiser: {
    bg: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800',
    iconColor: 'text-blue-500',
  },
  influencer: {
    bg: 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-800',
    iconColor: 'text-purple-500',
  },
  leaders: {
    bg: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800',
    iconColor: 'text-emerald-500',
  },
  pro_leaders: {
    bg: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800',
    iconColor: 'text-amber-500',
  },
  master_leaders: {
    bg: 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-300 dark:border-rose-800',
    iconColor: 'text-rose-500',
  },
};

export function RewardRulesPage() {
  const toast = useRef(null);

  const [rules, setRules] = useState([]);
  const [isConfigComplete, setIsConfigComplete] = useState(true);
  const [incompleteWarning, setIncompleteWarning] = useState(null);
  const [orderWarnings, setOrderWarnings] = useState([]);
  const [activeSetValidation, setActiveSetValidation] = useState(null);

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Modal State for Edit
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [editingRule, setEditingRule] = useState(null);

  // Form Fields
  const [referralRequirement, setReferralRequirement] = useState('0');
  const [teamRequirement, setTeamRequirement] = useState('1');
  const [rewardAmount, setRewardAmount] = useState('');
  const [isActive, setIsActive] = useState(true);
  const [formError, setFormError] = useState(null);

  // Live Simulator / Preview State
  const [simReferralCount, setSimReferralCount] = useState('15');
  const [simTeamCount, setSimTeamCount] = useState('50');
  const [simResult, setSimResult] = useState(null);
  const [simLoading, setSimLoading] = useState(false);

  const fetchRules = async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await rewardManagementApi.getRules();
      if (res.success) {
        setRules(res.rules || []);
        setIsConfigComplete(res.is_configuration_complete);
        setIncompleteWarning(res.incomplete_warning);
        setOrderWarnings(res.order_warnings || []);
        if (res.active_set_validation) {
          setActiveSetValidation(res.active_set_validation);
        }
      }
    } catch (err) {
      console.error('Failed to load rank-based reward rules:', err);
      setError(err.response?.data?.message || err.message || 'Failed to load rank-based reward rules.');
    } finally {
      setLoading(false);
    }
  };

  const runSimulation = async (refCount, teamCount) => {
    const refs = parseInt(refCount, 10);
    const team = parseInt(teamCount, 10);

    if (isNaN(refs) || refs < 0 || isNaN(team) || team < 0) {
      setSimResult(null);
      return;
    }

    setSimLoading(true);
    try {
      const res = await rewardManagementApi.previewResolution(refs, team);
      if (res.success) {
        setSimResult(res);
      }
    } catch (err) {
      console.error('Simulation preview failed:', err);
      setSimResult(null);
    } finally {
      setSimLoading(false);
    }
  };

  useEffect(() => {
    fetchRules();
  }, []);

  useEffect(() => {
    if (rules.length > 0) {
      runSimulation(simReferralCount, simTeamCount);
    }
  }, [rules, simReferralCount, simTeamCount]);

  const openEditModal = (rule) => {
    setEditingRule(rule);
    setReferralRequirement(String(rule.referral_requirement ?? 0));
    setTeamRequirement(String(rule.team_requirement ?? 0));
    setRewardAmount(rule.reward_amount !== null ? String(rule.reward_amount) : '');
    setIsActive(Boolean(rule.is_active));
    setFormError(null);
    setIsModalOpen(true);
  };

  const handleSaveRule = async (e) => {
    e.preventDefault();
    setFormError(null);

    const refNum = parseInt(referralRequirement, 10);
    if (isNaN(refNum) || refNum < 0) {
      setFormError('Referral requirement must be a non-negative integer (0 or greater).');
      return;
    }

    const teamNum = parseInt(teamRequirement, 10);
    if (isNaN(teamNum) || teamNum < 0) {
      setFormError('Team requirement must be a non-negative integer (0 or greater).');
      return;
    }

    if (!rewardAmount || isNaN(parseFloat(rewardAmount)) || parseFloat(rewardAmount) < 0) {
      setFormError('Reward amount must be a positive number.');
      return;
    }

    // Decimal precision validation (max 4 decimals)
    const dotPos = rewardAmount.indexOf('.');
    if (dotPos !== -1 && rewardAmount.substring(dotPos + 1).length > 4) {
      setFormError('Reward amount precision cannot exceed 4 decimal places.');
      return;
    }

    setIsSaving(true);
    try {
      const payload = {
        referral_requirement: refNum,
        team_requirement: teamNum,
        reward_amount: parseFloat(rewardAmount),
        is_active: isActive,
      };

      const res = await rewardManagementApi.updateRule(editingRule.id, payload);

      if (res.success) {
        toast.current?.show({
          severity: 'success',
          summary: 'Rule Updated',
          detail: res.message || `Rank rule for ${editingRule.rank_name} saved successfully.`,
          life: 3000,
        });
        setIsModalOpen(false);
        fetchRules();
      }
    } catch (err) {
      console.error('Save rule error:', err);
      setFormError(err.response?.data?.message || err.message || 'Failed to save rank rule.');
    } finally {
      setIsSaving(false);
    }
  };

  const handleToggleStatus = async (rule) => {
    try {
      const res = await rewardManagementApi.toggleStatus(rule.id);
      if (res.success) {
        toast.current?.show({
          severity: 'success',
          summary: 'Status Updated',
          detail: res.message || `${rule.rank_name} status updated.`,
          life: 3000,
        });
        fetchRules();
      }
    } catch (err) {
      console.error('Toggle status error:', err);
      toast.current?.show({
        severity: 'error',
        summary: 'Action Failed',
        detail: err.response?.data?.message || err.message || 'Failed to toggle rank rule status.',
        life: 4000,
      });
    }
  };

  const formatTeam = (teamCount) => {
    const count = parseInt(teamCount, 10);
    if (isNaN(count)) return '0 Users';
    return count === 1 ? '1 User' : `${count.toLocaleString()} Users`;
  };

  const formatReward = (amount) => {
    if (amount === null || amount === undefined || amount === '') return 'Not Set';
    return `$${parseFloat(amount).toFixed(4)} USD`;
  };

  return (
    <div className="space-y-6">
      <Toast ref={toast} />

      <PageHeader
        title="Reward Rules"
        subtitle="Manage the 5 authoritative rank rules controlling reward eligibility for Events and Ad Campaigns."
        actions={
          <Button
            label="Refresh"
            icon={<RefreshCw className={`w-4 h-4 mr-2 ${loading ? 'animate-spin' : ''}`} />}
            className="p-button-outlined p-button-sm text-sm"
            onClick={fetchRules}
            disabled={loading}
          />
        }
      />

      {/* Completeness / Warning Banners */}
      {!isConfigComplete && incompleteWarning && (
        <div className="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/50 rounded-xl p-4 flex items-start space-x-3 text-amber-800 dark:text-amber-300">
          <AlertTriangle className="w-5 h-5 flex-shrink-0 mt-0.5" />
          <div className="space-y-1">
            <h4 className="font-semibold text-sm">Reward Configuration Warning</h4>
            <p className="text-sm opacity-90">{incompleteWarning}</p>
          </div>
        </div>
      )}

      {orderWarnings.length > 0 && (
        <div className="bg-orange-50 dark:bg-orange-950/30 border border-orange-200 dark:border-orange-800/50 rounded-xl p-4 flex items-start space-x-3 text-orange-800 dark:text-orange-300">
          <AlertTriangle className="w-5 h-5 flex-shrink-0 mt-0.5" />
          <div className="space-y-1">
            <h4 className="font-semibold text-sm">Rank Hierarchy Logical Order Warning</h4>
            <ul className="list-disc list-inside text-xs opacity-90 space-y-0.5">
              {orderWarnings.map((w, idx) => (
                <li key={idx}>{w}</li>
              ))}
            </ul>
          </div>
        </div>
      )}

      {isConfigComplete && orderWarnings.length === 0 && (
        <div className="bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/50 rounded-xl p-4 flex items-center justify-between text-emerald-800 dark:text-emerald-300">
          <div className="flex items-center space-x-3">
            <ShieldCheck className="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
            <div>
              <h4 className="font-semibold text-sm">Authoritative Dynamic Rank Rules Active</h4>
              <p className="text-xs text-emerald-700/80 dark:text-emerald-400/80">
                All 5 ranks are configured and enforce centralized reward resolution across Events and Ads.
              </p>
            </div>
          </div>
          <span className="text-xs font-semibold px-2.5 py-1 bg-emerald-100 dark:bg-emerald-900/50 text-emerald-800 dark:text-emerald-300 rounded-full">
            5 / 5 Ranks Active
          </span>
        </div>
      )}

      {/* Main Content Area */}
      {loading ? (
        <div className="bg-white dark:bg-neutral-900 rounded-xl border border-neutral-200 dark:border-neutral-800 p-12">
          <LoadingSpinner message="Loading rank reward rules..." />
        </div>
      ) : error ? (
        <ErrorState message={error} onRetry={fetchRules} />
      ) : (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* PRIMARY REWARD RULES TABLE (Visible Columns: Rank | Referral | Team | Reward) */}
          <div className="lg:col-span-2 space-y-4">
            <div className="bg-white dark:bg-neutral-900 rounded-xl border border-neutral-200 dark:border-neutral-800 shadow-sm overflow-hidden">
              <div className="px-6 py-4 border-b border-neutral-200 dark:border-neutral-800 flex items-center justify-between">
                <div>
                  <h3 className="font-semibold text-neutral-900 dark:text-white flex items-center gap-2">
                    <Award className="w-4 h-4 text-primary-500" />
                    Centralized Rank Rules Table
                  </h3>
                  <p className="text-xs text-neutral-500 mt-0.5">
                    Highest qualifying rank is automatically awarded when both Referral &amp; Team conditions are met.
                  </p>
                </div>
              </div>

              <div className="overflow-x-auto">
                <table className="w-full text-left text-sm">
                  <thead className="bg-neutral-50 dark:bg-neutral-800/50 text-neutral-600 dark:text-neutral-400 text-xs uppercase tracking-wider border-b border-neutral-200 dark:border-neutral-800">
                    <tr>
                      <th className="py-3.5 px-6 font-semibold">Rank</th>
                      <th className="py-3.5 px-6 font-semibold">Referral</th>
                      <th className="py-3.5 px-6 font-semibold">Team</th>
                      <th className="py-3.5 px-6 font-semibold">Reward</th>
                      <th className="py-3.5 px-6 text-right font-semibold">Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                    {rules.map((rule) => {
                      const badge = RANK_BADGES[rule.rank_key] || {
                        bg: 'bg-neutral-100 text-neutral-700 border-neutral-200',
                        iconColor: 'text-neutral-500',
                      };

                      return (
                        <tr
                          key={rule.id}
                          className="hover:bg-neutral-50/75 dark:hover:bg-neutral-800/40 transition-colors"
                        >
                          {/* COLUMN 1: RANK */}
                          <td className="py-4 px-6">
                            <div className="flex items-center space-x-3">
                              <span
                                className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold border ${badge.bg}`}
                              >
                                <Award className={`w-3.5 h-3.5 ${badge.iconColor}`} />
                                {rule.rank_name}
                              </span>
                              {!rule.is_active && (
                                <span className="text-[10px] uppercase font-bold text-neutral-400 dark:text-neutral-500 bg-neutral-100 dark:bg-neutral-800 px-2 py-0.5 rounded">
                                  Inactive
                                </span>
                              )}
                            </div>
                          </td>

                          {/* COLUMN 2: REFERRAL */}
                          <td className="py-4 px-6">
                            <div className="font-semibold text-neutral-900 dark:text-neutral-100 flex items-center gap-1.5">
                              <UserCheck className="w-3.5 h-3.5 text-neutral-400" />
                              <span>{rule.referral_requirement}</span>
                              <span className="text-xs font-normal text-neutral-400">Direct</span>
                            </div>
                          </td>

                          {/* COLUMN 3: TEAM */}
                          <td className="py-4 px-6">
                            <div className="font-semibold text-neutral-900 dark:text-neutral-100 flex items-center gap-1.5">
                              <Users className="w-3.5 h-3.5 text-neutral-400" />
                              <span>{formatTeam(rule.team_requirement)}</span>
                            </div>
                          </td>

                          {/* COLUMN 4: REWARD */}
                          <td className="py-4 px-6">
                            <div className="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-base">
                              {formatReward(rule.reward_amount)}
                            </div>
                          </td>

                          {/* ACTIONS */}
                          <td className="py-4 px-6 text-right">
                            <div className="flex items-center justify-end space-x-2">
                              <button
                                type="button"
                                onClick={() => openEditModal(rule)}
                                className="p-1.5 rounded-lg text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:text-white dark:hover:bg-neutral-800 transition-colors"
                                title="Edit Rank Rule"
                              >
                                <Edit2 className="w-4 h-4" />
                              </button>
                              <button
                                type="button"
                                onClick={() => handleToggleStatus(rule)}
                                className={`p-1.5 rounded-lg transition-colors ${
                                  rule.is_active
                                    ? 'text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40'
                                    : 'text-neutral-400 hover:bg-neutral-100 dark:hover:bg-neutral-800'
                                }`}
                                title={rule.is_active ? 'Disable Rank' : 'Enable Rank'}
                              >
                                <Power className="w-4 h-4" />
                              </button>
                            </div>
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            </div>

            {/* Explanatory Architecture Footer Card */}
            <div className="bg-neutral-50 dark:bg-neutral-800/30 rounded-xl p-4 border border-neutral-200 dark:border-neutral-800 text-xs text-neutral-500 space-y-1">
              <p className="font-semibold text-neutral-700 dark:text-neutral-300">
                Authoritative Central Resolution Architecture:
              </p>
              <p>
                - When an Event or Ad reward is triggered, the system evaluates the authenticated member&apos;s direct verified referrals and recursive verified team count.
              </p>
              <p>
                - The highest rank where both <code className="font-mono bg-neutral-200 dark:bg-neutral-700 px-1 py-0.5 rounded">Referral &gt;= Configured</code> and <code className="font-mono bg-neutral-200 dark:bg-neutral-700 px-1 py-0.5 rounded">Team &gt;= Configured</code> is awarded.
              </p>
            </div>
          </div>

          {/* SIMULATOR & PREVIEW PANEL (Section 34) */}
          <div className="space-y-6">
            <div className="bg-white dark:bg-neutral-900 rounded-xl border border-neutral-200 dark:border-neutral-800 shadow-sm p-6 space-y-5">
              <div className="flex items-center space-x-2 border-b border-neutral-200 dark:border-neutral-800 pb-3">
                <Calculator className="w-5 h-5 text-primary-500" />
                <h3 className="font-semibold text-neutral-900 dark:text-white">Rank &amp; Reward Simulator</h3>
              </div>
              <p className="text-xs text-neutral-500">
                Test how the central rank engine resolves a user given their direct verified referral and team count in real-time.
              </p>

              <div className="space-y-4">
                <div>
                  <label className="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 mb-1">
                    Direct Verified Referrals
                  </label>
                  <input
                    type="number"
                    min="0"
                    value={simReferralCount}
                    onChange={(e) => setSimReferralCount(e.target.value)}
                    className="w-full px-3 py-2 text-sm bg-neutral-50 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                    placeholder="e.g. 15"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 mb-1">
                    Verified Team Members
                  </label>
                  <input
                    type="number"
                    min="0"
                    value={simTeamCount}
                    onChange={(e) => setSimTeamCount(e.target.value)}
                    className="w-full px-3 py-2 text-sm bg-neutral-50 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                    placeholder="e.g. 50"
                  />
                </div>
              </div>

              {/* Simulation Result Box */}
              <div className="mt-4 pt-4 border-t border-neutral-200 dark:border-neutral-800">
                <div className="text-xs font-semibold uppercase tracking-wider text-neutral-400 mb-3">
                  Simulation Result
                </div>

                {simLoading ? (
                  <div className="py-6 text-center text-xs text-neutral-400">
                    <RefreshCw className="w-4 h-4 animate-spin mx-auto mb-1" />
                    Calculating qualification...
                  </div>
                ) : simResult?.eligible && simResult?.rank ? (
                  <div className="bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-950/30 dark:to-teal-950/20 border border-emerald-200 dark:border-emerald-800/50 rounded-xl p-4 space-y-3">
                    <div className="flex items-center justify-between">
                      <span className="text-xs font-semibold text-emerald-800 dark:text-emerald-300">
                        Matched Rank:
                      </span>
                      <span className="px-2.5 py-0.5 text-xs font-bold rounded-md bg-emerald-200 dark:bg-emerald-900 text-emerald-900 dark:text-emerald-200">
                        {simResult.rank}
                      </span>
                    </div>

                    <div className="flex items-baseline justify-between border-t border-emerald-200/60 dark:border-emerald-800/40 pt-2">
                      <span className="text-xs text-neutral-600 dark:text-neutral-400">Applicable Reward:</span>
                      <span className="text-lg font-bold font-mono text-emerald-600 dark:text-emerald-400">
                        ${parseFloat(simResult.reward).toFixed(4)} USD
                      </span>
                    </div>

                    <div className="text-[11px] text-neutral-500 space-y-0.5 pt-1">
                      <div>Required Referrals: &gt;= {simResult.referral_requirement} (User has {simResult.user_referrals})</div>
                      <div>Required Team: &gt;= {simResult.team_requirement} (User has {simResult.user_team})</div>
                    </div>
                  </div>
                ) : (
                  <div className="bg-neutral-50 dark:bg-neutral-800/40 border border-neutral-200 dark:border-neutral-800 rounded-xl p-4 text-center space-y-1">
                    <div className="text-xs font-semibold text-neutral-600 dark:text-neutral-400">
                      No Qualifying Rank
                    </div>
                    <p className="text-[11px] text-neutral-400">
                      User does not meet the minimum requirements for any active rank.
                    </p>
                    <div className="text-sm font-mono font-bold text-neutral-500 pt-1">$0.0000 USD</div>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* EDIT MODAL (Section 36) */}
      <Dialog
        header={`Edit Rank Rule — ${editingRule?.rank_name || ''}`}
        visible={isModalOpen}
        onHide={() => !isSaving && setIsModalOpen(false)}
        className="w-full max-w-lg"
      >
        <form onSubmit={handleSaveRule} className="space-y-4 pt-2">
          {formError && (
            <div className="p-3 text-xs bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg text-rose-700 dark:text-rose-300">
              {formError}
            </div>
          )}

          {/* Fixed Rank Display */}
          <div>
            <label className="block text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">
              Rank
            </label>
            <input
              type="text"
              disabled
              value={editingRule?.rank_name || ''}
              className="w-full px-3 py-2 text-sm bg-neutral-100 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-lg font-semibold text-neutral-700 dark:text-neutral-300 cursor-not-allowed"
            />
            <p className="text-[11px] text-neutral-400 mt-1">
              Rank identities are fixed canonical tiers (Priority {editingRule?.priority || 1} of 5).
            </p>
          </div>

          {/* Referral Requirement */}
          <div>
            <label className="block text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">
              Referral Requirement (Direct Verified) <span className="text-rose-500">*</span>
            </label>
            <input
              type="number"
              min="0"
              required
              value={referralRequirement}
              onChange={(e) => setReferralRequirement(e.target.value)}
              className="w-full px-3 py-2 text-sm bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
              placeholder="e.g. 0"
            />
            <p className="text-[11px] text-neutral-400 mt-1">
              Minimum number of directly referred members with completed mobile verification.
            </p>
          </div>

          {/* Team Requirement */}
          <div>
            <label className="block text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">
              Team Requirement (Verified Downline) <span className="text-rose-500">*</span>
            </label>
            <input
              type="number"
              min="0"
              required
              value={teamRequirement}
              onChange={(e) => setTeamRequirement(e.target.value)}
              className="w-full px-3 py-2 text-sm bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
              placeholder="e.g. 1"
            />
            <p className="text-[11px] text-neutral-400 mt-1">
              Minimum total verified downline members across the user&apos;s entire referral tree.
            </p>
          </div>

          {/* Reward Amount */}
          <div>
            <label className="block text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">
              Reward Amount (USD) <span className="text-rose-500">*</span>
            </label>
            <div className="relative">
              <span className="absolute left-3 top-2 text-sm text-neutral-400 font-mono">$</span>
              <input
                type="number"
                step="0.0001"
                min="0"
                required
                value={rewardAmount}
                onChange={(e) => setRewardAmount(e.target.value)}
                className="w-full pl-7 pr-3 py-2 text-sm font-mono bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                placeholder="0.0250"
              />
            </div>
            <p className="text-[11px] text-neutral-400 mt-1">
              Authoritative payout amount credited to the user&apos;s Reward Wallet when qualifying for this rank.
            </p>
          </div>

          {/* Active Status */}
          <div className="flex items-center justify-between pt-2">
            <div>
              <span className="text-xs font-medium text-neutral-700 dark:text-neutral-300">Rule Active</span>
              <p className="text-[11px] text-neutral-400">Enable or disable this rank for reward qualification.</p>
            </div>
            <InputSwitch checked={isActive} onChange={(e) => setIsActive(e.value)} />
          </div>

          {/* Modal Actions */}
          <div className="flex justify-end space-x-2 pt-4 border-t border-neutral-200 dark:border-neutral-800">
            <Button
              type="button"
              label="Cancel"
              className="p-button-text p-button-sm text-sm"
              onClick={() => setIsModalOpen(false)}
              disabled={isSaving}
            />
            <Button
              type="submit"
              label={isSaving ? 'Saving...' : 'Save Configuration'}
              className="p-button-primary p-button-sm text-sm"
              disabled={isSaving}
            />
          </div>
        </form>
      </Dialog>
    </div>
  );
}

export default RewardRulesPage;
