import { useState, useEffect, useRef } from 'react';
import {
  Gift,
  Plus,
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
} from 'lucide-react';
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { Toast } from 'primereact/toast';
import { InputSwitch } from 'primereact/inputswitch';
import { PageHeader } from '../../components/common/PageHeader';
import { StatusBadge } from '../../components/common/StatusBadge';
import { LoadingSpinner } from '../../components/common/LoadingSpinner';
import { ErrorState } from '../../components/common/ErrorState';
import { adRewardRulesApi } from '../../api';

export function AdRewardRulesPage() {
  const toast = useRef(null);

  const [rules, setRules] = useState([]);
  const [previews, setPreviews] = useState([]);
  const [isConfigComplete, setIsConfigComplete] = useState(true);
  const [incompleteWarning, setIncompleteWarning] = useState(null);
  const [continuityErrors, setContinuityErrors] = useState([]);
  const [maxPermissibleReward, setMaxPermissibleReward] = useState(0.05);

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Modal State for Add/Edit
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [editingRule, setEditingRule] = useState(null);

  // Form Fields
  const [minReferrals, setMinReferrals] = useState('0');
  const [maxReferrals, setMaxReferrals] = useState('');
  const [isUnlimited, setIsUnlimited] = useState(false);
  const [rewardAmount, setRewardAmount] = useState('');
  const [isActive, setIsActive] = useState(true);
  const [formError, setFormError] = useState(null);

  // Live Simulator State
  const [simReferralCount, setSimReferralCount] = useState('8');
  const [simResult, setSimResult] = useState(null);
  const [simLoading, setSimLoading] = useState(false);

  const fetchRules = async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await adRewardRulesApi.getRules();
      if (res.success) {
        setRules(res.rules || []);
        setPreviews(res.previews || []);
        setIsConfigComplete(res.is_configuration_complete);
        setIncompleteWarning(res.incomplete_warning);
        setContinuityErrors(res.continuity_errors || []);
        if (res.max_permissible_reward_usd) {
          setMaxPermissibleReward(res.max_permissible_reward_usd);
        }
      }
    } catch (err) {
      console.error('Failed to load ad reward rules:', err);
      setError(err.response?.data?.message || err.message || 'Failed to load ad reward rules.');
    } finally {
      setLoading(false);
    }
  };

  const runSimulation = async (count) => {
    const num = parseInt(count, 10);
    if (isNaN(num) || num < 0) {
      setSimResult(null);
      return;
    }

    setSimLoading(true);
    try {
      const res = await adRewardRulesApi.previewResolution(num);
      if (res.success) {
        setSimResult(res);
      }
    } catch (err) {
      console.error('Simulation preview failed:', err);
    } finally {
      setSimLoading(false);
    }
  };

  const handleSimCountChange = (val) => {
    setSimReferralCount(val);
    runSimulation(val);
  };

  useEffect(() => {
    let isMounted = true;
    adRewardRulesApi
      .getRules()
      .then((res) => {
        if (!isMounted) return;
        if (res.success) {
          setRules(res.rules || []);
          setPreviews(res.previews || []);
          setIsConfigComplete(res.is_configuration_complete);
          setIncompleteWarning(res.incomplete_warning);
          setContinuityErrors(res.continuity_errors || []);
          if (res.max_permissible_reward_usd) {
            setMaxPermissibleReward(res.max_permissible_reward_usd);
          }
        }
      })
      .catch((err) => {
        if (!isMounted) return;
        console.error('Failed to load ad reward rules:', err);
        setError(err.response?.data?.message || err.message || 'Failed to load ad reward rules.');
      })
      .finally(() => {
        if (isMounted) setLoading(false);
      });

    adRewardRulesApi
      .previewResolution(8)
      .then((res) => {
        if (!isMounted) return;
        if (res.success) setSimResult(res);
      })
      .catch(() => {});

    return () => {
      isMounted = false;
    };
  }, []);

  const openCreateModal = () => {
    setEditingRule(null);
    setMinReferrals('0');
    setMaxReferrals('');
    setIsUnlimited(false);
    setRewardAmount('');
    setIsActive(true);
    setFormError(null);
    setIsModalOpen(true);
  };

  const openEditModal = (rule) => {
    setEditingRule(rule);
    setMinReferrals(String(rule.min_referrals ?? 0));
    if (rule.max_referrals === null || rule.max_referrals === undefined) {
      setMaxReferrals('');
      setIsUnlimited(true);
    } else {
      setMaxReferrals(String(rule.max_referrals));
      setIsUnlimited(false);
    }
    setRewardAmount(rule.reward_amount !== null && rule.reward_amount !== undefined ? String(rule.reward_amount) : '');
    setIsActive(Boolean(rule.is_active));
    setFormError(null);
    setIsModalOpen(true);
  };

  const handleSaveRule = async (e) => {
    e.preventDefault();
    setFormError(null);

    const min = parseInt(minReferrals, 10);
    if (isNaN(min) || min < 0) {
      setFormError('Minimum referrals must be an integer 0 or greater.');
      return;
    }

    let max = null;
    if (!isUnlimited) {
      max = parseInt(maxReferrals, 10);
      if (isNaN(max) || max < min) {
        setFormError('Maximum referrals must be an integer greater than or equal to minimum referrals.');
        return;
      }
    }

    let reward = null;
    if (rewardAmount.trim() !== '') {
      reward = parseFloat(rewardAmount);
      if (isNaN(reward) || reward < 0) {
        setFormError('Reward amount cannot be negative.');
        return;
      }
      if (reward > maxPermissibleReward) {
        setFormError(`Reward amount cannot exceed $${maxPermissibleReward.toFixed(3)} USD.`);
        return;
      }
    }

    const payload = {
      min_referrals: min,
      max_referrals: max,
      reward_amount: reward,
      is_active: isActive,
    };

    setIsSaving(true);
    try {
      if (editingRule) {
        const res = await adRewardRulesApi.updateRule(editingRule.id, payload);
        toast.current?.show({
          severity: 'success',
          summary: 'Rule Updated',
          detail: res.message || 'Reward rule updated successfully.',
          life: 4000,
        });
      } else {
        const res = await adRewardRulesApi.createRule(payload);
        toast.current?.show({
          severity: 'success',
          summary: 'Rule Created',
          detail: res.message || 'Reward rule created successfully.',
          life: 4000,
        });
      }
      setIsModalOpen(false);
      fetchRules();
    } catch (err) {
      const msg = err.response?.data?.message || err.message || 'Failed to save reward rule.';
      setFormError(msg);
      toast.current?.show({
        severity: 'error',
        summary: 'Validation Error',
        detail: msg,
        life: 5000,
      });
    } finally {
      setIsSaving(false);
    }
  };

  const handleToggleStatus = async (rule) => {
    try {
      const res = await adRewardRulesApi.toggleStatus(rule.id);
      toast.current?.show({
        severity: 'success',
        summary: 'Status Updated',
        detail: res.message || 'Reward rule status changed successfully.',
        life: 3000,
      });
      fetchRules();
    } catch (err) {
      const msg = err.response?.data?.message || err.message || 'Failed to update rule status.';
      toast.current?.show({
        severity: 'error',
        summary: 'Action Blocked',
        detail: msg,
        life: 5000,
      });
    }
  };

  if (loading) {
    return (
      <div className="space-y-6">
        <PageHeader
          title="Ad Reward Rules"
          subtitle="Configure reward amounts based on direct verified referrals."
        />
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-12 flex justify-center items-center">
          <LoadingSpinner message="Loading dynamic reward rules..." />
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="space-y-6">
        <PageHeader
          title="Ad Reward Rules"
          subtitle="Configure reward amounts based on direct verified referrals."
        />
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
          <ErrorState
            title="Failed to Load Reward Rules"
            message={error}
            onRetry={fetchRules}
          />
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <Toast ref={toast} position="top-right" />

      {/* Page Header */}
      <PageHeader
        title="Ad Reward Rules"
        subtitle="Configure dynamic reward amounts based on member direct verified referral counts."
        breadcrumbs={[
          { label: 'Admin', to: '/admin/dashboard' },
          { label: 'Ads Management', to: '/admin/ad-campaigns' },
          { label: 'Ad Reward Rules' },
        ]}
        actions={
          <div className="flex items-center gap-2">
            <Button
              label="Refresh"
              icon={<RefreshCw className="w-3.5 h-3.5 mr-1.5" />}
              size="small"
              className="p-button-outlined p-button-secondary rounded-xl text-xs font-semibold px-3 py-2"
              onClick={fetchRules}
            />
            <Button
              label="Add Reward Rule"
              icon={<Plus className="w-3.5 h-3.5 mr-1.5" />}
              size="small"
              className="p-button-primary rounded-xl text-xs font-semibold px-3.5 py-2"
              onClick={openCreateModal}
            />
          </div>
        }
      />

      {/* Main Two-Column Layout */}
      <div className="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
        {/* LEFT COLUMN: Main Configuration & Tiers Table (Span 8) */}
        <div className="xl:col-span-8 space-y-6 min-w-0">
          {/* Configuration Required Banner if 0-5 is incomplete */}
          {!isConfigComplete && incompleteWarning && (
            <div className="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex items-start gap-3.5 shadow-sm">
              <div className="w-8 h-8 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600 shrink-0 mt-0.5">
                <AlertTriangle className="w-4 h-4" />
              </div>
              <div className="text-xs leading-relaxed flex-1">
                <strong className="block font-bold text-sm text-amber-950 mb-0.5">
                  Configuration Required: 0–5 Direct Verified Referrals Tier
                </strong>
                <p className="m-0 text-amber-800">
                  {incompleteWarning} The reward for the 0–5 referral range is pending Admin input. Click <strong>Edit</strong> on the 0–5 rule below to set the authoritative reward amount (e.g. <code className="bg-amber-100 px-1 py-0.5 rounded font-mono text-amber-900">$0.025 USD</code>).
                </p>
              </div>
            </div>
          )}

          {/* Continuity Warnings Banner if active ranges overlap or have gaps */}
          {continuityErrors.length > 0 && (
            <div className="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-900 flex items-start gap-3.5 shadow-sm">
              <div className="w-8 h-8 rounded-xl bg-red-100 flex items-center justify-center text-red-600 shrink-0 mt-0.5">
                <AlertTriangle className="w-4 h-4" />
              </div>
              <div className="text-xs leading-relaxed flex-1">
                <strong className="block font-bold text-sm text-red-950 mb-1">
                  Active Tier Continuity Issues Detected
                </strong>
                <ul className="list-disc pl-4 space-y-1 text-red-800 m-0">
                  {continuityErrors.map((err, idx) => (
                    <li key={idx}>{err}</li>
                  ))}
                </ul>
              </div>
            </div>
          )}

          {/* Configured Reward Tiers Table Card */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="p-5 border-b border-slate-100 flex items-center justify-between">
              <div className="flex items-center gap-3">
                <div className="p-2 rounded-xl bg-indigo-100 text-indigo-600">
                  <Gift className="w-5 h-5" />
                </div>
                <div>
                  <h3 className="text-base font-bold text-slate-900 m-0">
                    Configured Reward Tiers
                  </h3>
                  <p className="text-xs text-slate-500 m-0 mt-0.5">
                    Deterministic slabs evaluated from lowest minimum referrals ascending.
                  </p>
                </div>
              </div>
              <span className="text-xs font-semibold px-2.5 py-1 bg-slate-100 text-slate-600 rounded-full">
                {rules.length} {rules.length === 1 ? 'Tier' : 'Tiers'}
              </span>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm text-slate-600">
                <thead className="text-xs font-semibold text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-4 py-3.5">Direct Verified Referrals</th>
                    <th className="px-4 py-3.5">Reward Amount</th>
                    <th className="px-4 py-3.5 text-center">Status</th>
                    <th className="px-4 py-3.5">Last Updated</th>
                    <th className="px-4 py-3.5 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {rules.length === 0 ? (
                    <tr>
                      <td colSpan={5} className="py-8 text-center text-slate-400">
                        No reward rules configured yet. Click <strong>Add Reward Rule</strong> to create one.
                      </td>
                    </tr>
                  ) : (
                    rules.map((rule) => {
                      const isUnconfigured = rule.reward_amount === null || rule.reward_amount === undefined;
                      const rangeLabel = rule.max_referrals !== null
                        ? `${rule.min_referrals}–${rule.max_referrals}`
                        : `${rule.min_referrals}+`;

                      return (
                        <tr
                          key={rule.id}
                          className={`hover:bg-slate-50 transition-colors ${
                            !rule.is_active ? 'opacity-60 bg-slate-50/40' : ''
                          }`}
                        >
                          {/* Direct Verified Referrals */}
                          <td className="px-4 py-3.5">
                            <div className="flex items-center gap-2">
                              <span className="font-mono font-bold text-xs px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-200">
                                {rangeLabel} refs
                              </span>
                              {rule.max_referrals === null && (
                                <span className="text-[11px] text-slate-400 font-medium">
                                  (Unlimited)
                                </span>
                              )}
                            </div>
                          </td>

                          {/* Reward Amount */}
                          <td className="px-4 py-3.5">
                            {isUnconfigured ? (
                              <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-amber-50 text-amber-700 text-xs font-semibold border border-amber-200">
                                <AlertTriangle className="w-3.5 h-3.5 shrink-0" />
                                Configuration Required
                              </span>
                            ) : (
                              <span className="font-bold font-mono text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-md border border-emerald-200/60">
                                +${parseFloat(rule.reward_amount).toFixed(4)} USD
                              </span>
                            )}
                          </td>

                          {/* Status */}
                          <td className="px-4 py-3.5 text-center">
                            <StatusBadge status={rule.is_active ? 'active' : 'inactive'} />
                          </td>

                          {/* Last Updated */}
                          <td className="px-4 py-3.5 text-xs text-slate-600 whitespace-nowrap">
                            <div className="font-medium text-slate-800">
                              {rule.updated_by?.name || rule.created_by?.name || 'System'}
                            </div>
                            <div className="text-[11px] text-slate-400">
                              {rule.updated_at ? new Date(rule.updated_at).toLocaleDateString() : '—'}
                            </div>
                          </td>

                          {/* Actions */}
                          <td className="px-4 py-3.5 text-right whitespace-nowrap">
                            <div className="flex items-center justify-end gap-1.5">
                              <Button
                                icon={<Edit2 className="w-3.5 h-3.5 mr-1" />}
                                label="Edit"
                                size="small"
                                className="p-button-outlined p-button-secondary rounded-xl text-xs py-1.5 px-3 h-8 font-semibold"
                                onClick={() => openEditModal(rule)}
                              />
                              <Button
                                icon={<Power className="w-3.5 h-3.5 mr-1" />}
                                label={rule.is_active ? 'Disable' : 'Enable'}
                                size="small"
                                className={`rounded-xl text-xs py-1.5 px-3 h-8 font-semibold ${
                                  rule.is_active
                                    ? 'p-button-outlined p-button-danger'
                                    : 'p-button-outlined p-button-success'
                                }`}
                                onClick={() => handleToggleStatus(rule)}
                                title={rule.is_active ? 'Safely deactivate tier without deleting historical data' : 'Activate tier'}
                              />
                            </div>
                          </td>
                        </tr>
                      );
                    })
                  )}
                </tbody>
              </table>
            </div>

            {/* Footer Explanations */}
            <div className="p-4 bg-slate-50 border-t border-slate-100 text-xs space-y-1.5">
              <div className="flex items-center gap-1.5 text-slate-800 font-bold">
                <Info className="w-4 h-4 text-indigo-500 shrink-0" />
                <span>Authoritative Verification & Referral Rules:</span>
              </div>
              <p className="m-0 text-xs leading-relaxed text-slate-600 pl-5.5">
                • Slabs are evaluated strictly by <strong className="text-slate-800 font-semibold">Direct Verified Referrals</strong> (referred members who completed WhatsApp/mobile verification).
                <br />
                • Historical reward records remain immutable. Disabling a rule preserves audit trails and future resolution safety.
              </p>
            </div>
          </div>

          {/* Immutability & Safety Card */}
          <div className="p-4 bg-indigo-50/80 border border-indigo-200 rounded-2xl flex items-start gap-3.5 text-xs text-indigo-950 shadow-sm">
            <div className="w-8 h-8 rounded-xl bg-indigo-100 flex items-center justify-center text-indigo-600 shrink-0 mt-0.5">
              <ShieldCheck className="w-4 h-4" />
            </div>
            <div className="space-y-1 leading-relaxed flex-1">
              <strong className="block text-sm font-bold text-indigo-950">
                Dynamic Reward Rules Configuration Guarantees
              </strong>
              <p className="m-0 text-indigo-900">
                Deterministic slabs are evaluated strictly by <strong className="font-semibold text-indigo-950">Direct Verified Referrals</strong>. Historical reward snapshots remain permanent and immutable when rules or tiers are updated.
              </p>
            </div>
          </div>
        </div>

        {/* RIGHT COLUMN: Previews & Live Simulator (Span 4) */}
        <div className="xl:col-span-4 space-y-6 min-w-0">
          {/* Predefined Slabs Preview Card */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4">
            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
              <div className="flex items-center gap-2.5">
                <div className="p-2 rounded-xl bg-indigo-100 text-indigo-600">
                  <TrendingUp className="w-4 h-4" />
                </div>
                <div>
                  <h4 className="text-sm font-bold text-slate-900 m-0">
                    Standard Reward Previews
                  </h4>
                  <p className="text-xs text-slate-500 m-0">Resolved rate per referral count</p>
                </div>
              </div>
            </div>

            <div className="space-y-2">
              {previews.map((p, idx) => (
                <div
                  key={idx}
                  className="flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs transition-colors hover:bg-slate-100"
                >
                  <div className="flex items-center gap-2.5 min-w-0">
                    <div className="w-6 h-6 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-500 shrink-0">
                      <Users className="w-3.5 h-3.5" />
                    </div>
                    <div className="truncate">
                      <span className="font-bold text-slate-800">
                        {p.direct_verified_referrals} {p.direct_verified_referrals === 1 ? 'referral' : 'referrals'}
                      </span>
                      <span className="ml-1.5 text-xs text-slate-500 font-mono font-medium">
                        ({p.range_label})
                      </span>
                    </div>
                  </div>

                  <div className="shrink-0 text-right pl-2">
                    {p.is_configured ? (
                      <span className="font-mono font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                        ${p.reward_amount_usd.toFixed(3)} USD
                      </span>
                    ) : (
                      <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 text-xs font-semibold border border-amber-200 whitespace-nowrap">
                        <AlertTriangle className="w-3 h-3 shrink-0" />
                        Config Required
                      </span>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Interactive Live Simulator Card */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4">
            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
              <div className="flex items-center gap-2.5">
                <div className="p-2 rounded-xl bg-sky-100 text-sky-600">
                  <Calculator className="w-4 h-4" />
                </div>
                <div>
                  <h4 className="text-sm font-bold text-slate-900 m-0">
                    Live Referral Simulator
                  </h4>
                  <p className="text-xs text-slate-500 m-0">Simulate rule resolution on demand</p>
                </div>
              </div>
            </div>

            <div className="space-y-3">
              <div>
                <label htmlFor="simReferralCount" className="block text-xs font-bold text-slate-700 mb-1.5">
                  Test Direct Verified Referral Count:
                </label>
                <div className="relative flex items-center">
                  <div className="absolute left-3.5 flex items-center pointer-events-none text-slate-400">
                    <Users className="w-4 h-4" />
                  </div>
                  <input
                    id="simReferralCount"
                    type="number"
                    min="0"
                    step="1"
                    value={simReferralCount}
                    onChange={(e) => handleSimCountChange(e.target.value)}
                    className="w-full h-11 pl-10 pr-3 has-start-icon text-sm font-bold font-mono text-slate-900 bg-white border border-slate-300 rounded-xl placeholder:text-slate-400 focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 focus:outline-none transition-all"
                    placeholder="e.g. 8"
                  />
                </div>
                <p className="text-[11px] text-slate-500 mt-1 mb-0">
                  Enter any referral count to verify live rule matching.
                </p>
              </div>

              {simLoading ? (
                <div className="p-4 bg-slate-50 rounded-xl border border-slate-200 text-center text-xs text-slate-500">
                  <RefreshCw className="w-4 h-4 animate-spin mx-auto mb-1.5 text-sky-500" />
                  Resolving reward rule...
                </div>
              ) : simResult ? (
                <div className="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-2.5 text-xs">
                  <div className="flex justify-between items-center">
                    <span className="font-semibold text-slate-700">Matched Tier Range:</span>
                    <span className="font-bold text-slate-900 font-mono bg-white px-2.5 py-1 rounded-lg border border-slate-200 shadow-xs">
                      {simResult.matched_rule
                        ? `${simResult.matched_rule.min_referrals}–${
                            simResult.matched_rule.max_referrals !== null
                              ? `${simResult.matched_rule.max_referrals} refs`
                              : 'Unlimited'
                          }`
                        : 'No Rule Matched'}
                    </span>
                  </div>

                  <div className="pt-2.5 border-t border-slate-200 flex justify-between items-center">
                    <span className="font-bold text-slate-800">
                      Resolved Reward:
                    </span>
                    {simResult.is_configured ? (
                      <span className="text-sm font-black text-indigo-700 font-mono bg-indigo-50 px-2.5 py-1 rounded-lg border border-indigo-200">
                        ${simResult.reward_amount_usd.toFixed(3)} USD
                      </span>
                    ) : (
                      <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-amber-50 text-amber-700 text-xs font-semibold border border-amber-200">
                        <AlertTriangle className="w-3 h-3" />
                        Config Required
                      </span>
                    )}
                  </div>
                </div>
              ) : (
                <div className="p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-center text-xs text-slate-500">
                  Enter a valid count above to simulate.
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Add / Edit Rule Modal Dialog */}
      <Dialog
        header={editingRule ? 'Edit Ad Reward Rule' : 'Create New Ad Reward Rule'}
        visible={isModalOpen}
        style={{ width: '500px' }}
        breakpoints={{ '960px': '75vw', '641px': '92vw' }}
        onHide={() => !isSaving && setIsModalOpen(false)}
        draggable={false}
        className="rounded-2xl"
      >
        <form onSubmit={handleSaveRule} className="space-y-4 pt-1">
          {formError && (
            <div className="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-start gap-2.5 shadow-xs">
              <AlertTriangle className="w-4 h-4 text-red-500 shrink-0 mt-0.5" />
              <span className="font-medium leading-relaxed">{formError}</span>
            </div>
          )}

          {/* Minimum Referrals */}
          <div className="space-y-1.5">
            <label htmlFor="min-referrals" className="block text-xs font-bold text-slate-800">
              Minimum Direct Verified Referrals <span className="text-red-500 font-bold">*</span>
            </label>
            <input
              id="min-referrals"
              type="number"
              min="0"
              step="1"
              value={minReferrals}
              onChange={(e) => setMinReferrals(e.target.value)}
              placeholder="0"
              className="w-full h-11 px-3.5 text-sm font-semibold font-mono text-slate-900 bg-white border border-slate-300 rounded-xl placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition-all disabled:opacity-50 disabled:bg-slate-100 disabled:cursor-not-allowed"
              required
              disabled={isSaving}
            />
            <p className="text-xs text-slate-500 m-0">
              Inclusive lower bound (e.g. 0, 6, 15).
            </p>
          </div>

          {/* Maximum Referrals & Unlimited Checkbox */}
          <div className="space-y-1.5">
            <div className="flex items-center justify-between">
              <label htmlFor="max-referrals" className="block text-xs font-bold text-slate-800">
                Maximum Direct Verified Referrals
              </label>
              <label htmlFor="unlimited-max" className="flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer select-none">
                <input
                  id="unlimited-max"
                  type="checkbox"
                  checked={isUnlimited}
                  onChange={(e) => {
                    setIsUnlimited(e.target.checked);
                    if (e.target.checked) setMaxReferrals('');
                  }}
                  disabled={isSaving}
                  className="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500 cursor-pointer"
                />
                <span>Unlimited (No max)</span>
              </label>
            </div>

            <input
              id="max-referrals"
              type="number"
              min={minReferrals || '0'}
              step="1"
              value={maxReferrals}
              onChange={(e) => setMaxReferrals(e.target.value)}
              placeholder={isUnlimited ? 'Unlimited (NULL)' : '5'}
              className="w-full h-11 px-3.5 text-sm font-semibold font-mono text-slate-900 bg-white border border-slate-300 rounded-xl placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition-all disabled:opacity-50 disabled:bg-slate-100 disabled:cursor-not-allowed"
              disabled={isUnlimited || isSaving}
              required={!isUnlimited}
            />
            <p className="text-xs text-slate-500 m-0">
              Inclusive upper bound (e.g. 5, 14, or check unlimited for top tier).
            </p>
          </div>

          {/* Reward Amount ($ USD) */}
          <div className="space-y-1.5">
            <div className="flex items-center justify-between">
              <label htmlFor="reward-amount" className="block text-xs font-bold text-slate-800">
                Reward Amount ($ USD)
              </label>
              <span className="text-xs text-slate-500 font-mono font-medium">
                Max: ${maxPermissibleReward.toFixed(3)} USD
              </span>
            </div>
            <div className="relative flex items-center">
              <span className="absolute left-3.5 text-sm font-bold text-slate-500 pointer-events-none">
                $
              </span>
              <input
                id="reward-amount"
                type="number"
                min="0"
                max={String(maxPermissibleReward)}
                step="0.001"
                value={rewardAmount}
                onChange={(e) => setRewardAmount(e.target.value)}
                placeholder="0.025"
                className="w-full h-11 pl-10 pr-3.5 has-start-addon text-sm font-bold font-mono text-slate-900 bg-white border border-slate-300 rounded-xl placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition-all disabled:opacity-50 disabled:bg-slate-100 disabled:cursor-not-allowed"
                disabled={isSaving}
              />
            </div>
            <p className="text-xs text-slate-500 m-0">
              Exact decimal precision (e.g. <code className="font-mono text-indigo-600">0.025</code>, <code className="font-mono text-indigo-600">0.035</code>, <code className="font-mono text-indigo-600">0.050</code>).
            </p>
          </div>

          {/* Status Switch */}
          <div className="flex items-center justify-between p-4 rounded-xl bg-slate-50 border border-slate-200">
            <div className="space-y-0.5 pr-4">
              <div className="text-xs font-bold text-slate-900">Rule Active Status</div>
              <p className="text-xs text-slate-500 m-0">
                Only active rules are evaluated by the reward calculation engine.
              </p>
            </div>
            <div className="shrink-0 flex items-center">
              <InputSwitch
                checked={isActive}
                onChange={(e) => setIsActive(e.value)}
                disabled={isSaving}
              />
            </div>
          </div>

          {/* Modal Action Buttons */}
          <div className="pt-4 mt-2 border-t border-slate-100 flex items-center justify-end gap-3">
            <Button
              type="button"
              label="Cancel"
              className="p-button-outlined p-button-secondary rounded-xl text-xs font-semibold px-5 py-2.5 h-10 min-w-[100px]"
              onClick={() => setIsModalOpen(false)}
              disabled={isSaving}
            />
            <Button
              type="submit"
              label={isSaving ? 'Saving...' : 'Save Rule'}
              icon={isSaving ? <RefreshCw className="w-4 h-4 mr-2 animate-spin" /> : <CheckCircle2 className="w-4 h-4 mr-2" />}
              className="p-button-primary rounded-xl text-xs font-bold px-6 py-2.5 h-10 min-w-[130px]"
              disabled={isSaving}
            />
          </div>
        </form>
      </Dialog>
    </div>
  );
}

export default AdRewardRulesPage;
