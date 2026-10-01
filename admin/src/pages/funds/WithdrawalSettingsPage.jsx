import { useState, useEffect, useRef } from 'react';
import {
  Percent,
  DollarSign,
  Save,
  RefreshCw,
  FileText,
  ShieldCheck,
  AlertCircle,
  CheckCircle2,
  TrendingDown,
  Info,
  Clock,
  ArrowRight,
  Sliders,
  Power,
} from 'lucide-react';
import { Button } from 'primereact/button';
import { InputText } from 'primereact/inputtext';
import { InputTextarea } from 'primereact/inputtextarea';
import { Toast } from 'primereact/toast';
import { PageHeader } from '../../components/common/PageHeader';
import { LoadingSpinner } from '../../components/common/LoadingSpinner';
import { ErrorState } from '../../components/common/ErrorState';
import { withdrawalsApi } from '../../api';

export function WithdrawalSettingsPage() {
  const toast = useRef(null);

  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState(null);

  // Form States
  const [serviceChargePercent, setServiceChargePercent] = useState('10.00');
  const [minWithdrawalAmount, setMinWithdrawalAmount] = useState('5.00');
  const [maxWithdrawalAmount, setMaxWithdrawalAmount] = useState('10000.00');
  const [withdrawalStatus, setWithdrawalStatus] = useState('enabled'); // 'enabled' | 'disabled'
  const [instructions, setInstructions] = useState('');

  // Interactive Live Simulator Test Gross
  const [simTestAmount, setSimTestAmount] = useState('100.00');

  const fetchSettings = () => {
    setIsLoading(true);
    setError(null);
    withdrawalsApi
      .getSettings()
      .then((res) => {
        const s = res?.settings || {};
        const fee = s.withdrawal_service_charge_percent ?? s.service_charge_percent;
        setServiceChargePercent(fee !== undefined ? String(fee) : '10.00');
        setMinWithdrawalAmount(
          s.minimum_withdrawal_amount !== undefined ? String(s.minimum_withdrawal_amount) : '5.00'
        );
        setMaxWithdrawalAmount(
          s.maximum_withdrawal_amount !== undefined ? String(s.maximum_withdrawal_amount) : '10000.00'
        );
        setWithdrawalStatus(s.withdrawal_status || 'enabled');
        setInstructions(
          s.withdrawal_instructions ||
            'Withdrawals are processed in USDT (BEP-20) to your verified payout wallet address. Processing takes 15-60 minutes after admin approval.'
        );
      })
      .catch((err) => {
        console.error('Failed to load withdrawal settings:', err);
        setError(err.response?.data?.message || 'Failed to load withdrawal settings.');
      })
      .finally(() => {
        setIsLoading(false);
      });
  };

  useEffect(() => {
    fetchSettings();
  }, []);

  const handleSave = async (e) => {
    if (e && e.preventDefault) e.preventDefault();

    const parsedFee = parseFloat(serviceChargePercent);
    if (isNaN(parsedFee) || parsedFee < 0 || parsedFee > 100) {
      toast.current?.show({
        severity: 'warn',
        summary: 'Invalid Fee',
        detail: 'Service charge percentage must be between 0.00% and 100.00%.',
        life: 4000,
      });
      return;
    }

    const parsedMin = parseFloat(minWithdrawalAmount);
    if (isNaN(parsedMin) || parsedMin < 0.01) {
      toast.current?.show({
        severity: 'warn',
        summary: 'Invalid Minimum Limit',
        detail: 'Minimum withdrawal amount must be at least $0.01.',
        life: 4000,
      });
      return;
    }

    const parsedMax = parseFloat(maxWithdrawalAmount);
    if (!isNaN(parsedMax) && parsedMax > 0 && parsedMax < parsedMin) {
      toast.current?.show({
        severity: 'warn',
        summary: 'Invalid Limits',
        detail: 'Maximum withdrawal amount cannot be less than minimum withdrawal amount.',
        life: 4000,
      });
      return;
    }

    setIsSaving(true);
    try {
      const res = await withdrawalsApi.updateSettings({
        withdrawal_service_charge_percent: parsedFee,
        service_charge_percent: parsedFee,
        minimum_withdrawal_amount: parsedMin,
        maximum_withdrawal_amount: isNaN(parsedMax) ? 0 : parsedMax,
        withdrawal_status: withdrawalStatus,
        withdrawal_instructions: instructions,
      });

      const s = res?.settings || {};
      const updatedFee = s.withdrawal_service_charge_percent ?? s.service_charge_percent;
      if (updatedFee !== undefined) setServiceChargePercent(String(updatedFee));
      if (s.minimum_withdrawal_amount !== undefined) setMinWithdrawalAmount(String(s.minimum_withdrawal_amount));
      if (s.maximum_withdrawal_amount !== undefined) setMaxWithdrawalAmount(String(s.maximum_withdrawal_amount));
      if (s.withdrawal_status) setWithdrawalStatus(s.withdrawal_status);
      if (s.withdrawal_instructions !== undefined) setInstructions(s.withdrawal_instructions);

      toast.current?.show({
        severity: 'success',
        summary: 'Settings Saved',
        detail: `Withdrawal settings updated! Service charge set to ${parsedFee.toFixed(2)}%.`,
        life: 4000,
      });
    } catch (err) {
      console.error('Failed to update withdrawal settings:', err);
      toast.current?.show({
        severity: 'error',
        summary: 'Save Failed',
        detail: err.response?.data?.message || 'Failed to save withdrawal settings. Please try again.',
        life: 5000,
      });
    } finally {
      setIsSaving(false);
    }
  };

  // Calculation for live simulation
  const numFee = Math.max(0, Math.min(100, parseFloat(serviceChargePercent) || 0));
  const testGross = Math.max(0, parseFloat(simTestAmount) || 0);
  const simDeduction = +(testGross * (numFee / 100)).toFixed(2);
  const simNet = +(testGross - simDeduction).toFixed(2);

  return (
    <div className="space-y-6">
      <Toast ref={toast} />

      <PageHeader
        title="Withdrawal Settings"
        subtitle="Configure dynamic service charge percentage, payout limits, and platform withdrawal rules."
        breadcrumbs={[
          { label: 'Funds & Withdrawals', to: '/admin/funds/withdrawals' },
          { label: 'Withdrawal Settings' },
        ]}
        actions={
          <div className="flex items-center gap-2.5">
            <Button
              type="button"
              severity="secondary"
              outlined
              icon={<RefreshCw className={`w-4 h-4 mr-1.5 ${isLoading ? 'animate-spin' : ''}`} />}
              label="Refresh"
              onClick={fetchSettings}
              disabled={isLoading || isSaving}
              className="text-xs font-semibold px-3 py-2 border-slate-300 text-slate-700 hover:bg-slate-50"
            />
            <Button
              type="button"
              icon={<Save className="w-4 h-4 mr-1.5" />}
              label={isSaving ? 'Saving...' : 'Save Settings'}
              onClick={handleSave}
              disabled={isLoading || isSaving}
              loading={isSaving}
              className="text-xs font-semibold px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white border-indigo-600 shadow-sm transition-all"
            />
          </div>
        }
      />

      {isLoading ? (
        <LoadingSpinner message="Loading withdrawal settings..." />
      ) : error ? (
        <ErrorState message={error} onRetry={fetchSettings} />
      ) : (
        <form onSubmit={handleSave} className="space-y-6">
          {/* Top Status Banner */}
          <div
            className={`p-4 rounded-2xl border flex items-center justify-between flex-wrap gap-3 ${
              withdrawalStatus === 'disabled'
                ? 'bg-rose-50 border-rose-200 text-rose-800'
                : numFee === 0
                ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
                : 'bg-indigo-50/80 border-indigo-200 text-indigo-900'
            }`}
          >
            <div className="flex items-center gap-3">
              <div
                className={`p-2.5 rounded-xl ${
                  withdrawalStatus === 'disabled'
                    ? 'bg-rose-100 text-rose-600'
                    : numFee === 0
                    ? 'bg-emerald-100 text-emerald-600'
                    : 'bg-indigo-100 text-indigo-600'
                }`}
              >
                {withdrawalStatus === 'disabled' ? (
                  <Power className="w-5 h-5" />
                ) : (
                  <Percent className="w-5 h-5" />
                )}
              </div>
              <div>
                <div className="text-sm font-bold flex items-center gap-2">
                  <span>Dynamic Service Charge Status:</span>
                  <span
                    className={`px-2.5 py-0.5 rounded-full text-xs font-extrabold ${
                      withdrawalStatus === 'disabled'
                        ? 'bg-rose-200 text-rose-900'
                        : numFee === 0
                        ? 'bg-emerald-200 text-emerald-900'
                        : 'bg-indigo-200 text-indigo-900'
                    }`}
                  >
                    {withdrawalStatus === 'disabled'
                      ? 'PAUSED (Withdrawals Disabled)'
                      : numFee === 0
                      ? '0.00% Zero Fee Mode'
                      : `${numFee.toFixed(2)}% Active Service Charge`}
                  </span>
                </div>
                <p className="text-xs opacity-90 mt-0.5">
                  {withdrawalStatus === 'disabled'
                    ? 'Members currently cannot submit new withdrawal requests while system is in maintenance mode.'
                    : numFee === 0
                    ? 'Members receive 100% of their requested amount with zero deductions.'
                    : `Every member withdrawal will automatically deduct ${numFee.toFixed(
                        2
                      )}% as platform service charge, crediting the net amount.`}
                </p>
              </div>
            </div>

            <div className="flex items-center gap-2">
              <span className="text-xs font-medium text-slate-500">Payout Token:</span>
              <span className="px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-xs font-bold text-slate-700 flex items-center gap-1 shadow-sm">
                <span className="w-2 h-2 rounded-full bg-emerald-500"></span>
                USDT (BEP-20)
              </span>
            </div>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {/* Left 7 Columns: Fee Settings & Limits */}
            <div className="lg:col-span-7 space-y-6">
              {/* Card 1: Dynamic Service Charge Policy */}
              <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-5">
                <div className="flex items-center gap-3">
                  <div className="p-2.5 rounded-xl bg-indigo-100 text-indigo-600">
                    <Percent className="w-5 h-5" />
                  </div>
                  <div>
                    <h3 className="text-base font-bold text-slate-900">
                      Withdrawal Service Charge (%)
                    </h3>
                    <p className="text-xs text-slate-500">
                      Specify the fee percentage deducted from member wallet withdrawals.
                    </p>
                  </div>
                </div>

                <div className="bg-slate-50/80 border border-slate-200 rounded-xl p-4 space-y-4">
                  <div>
                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                      Service Charge Percentage
                    </label>
                    <div className="relative max-w-sm">
                      <InputText
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        value={serviceChargePercent}
                        onChange={(e) => setServiceChargePercent(e.target.value)}
                        placeholder="10.00"
                        className="w-full text-lg font-bold border border-slate-300 rounded-xl pl-4 pr-12 py-3 bg-white text-slate-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 shadow-sm"
                      />
                      <span className="absolute right-4 top-1/2 -translate-y-1/2 font-bold text-slate-500 text-base pointer-events-none">
                        %
                      </span>
                    </div>
                    <p className="text-xs text-slate-500 mt-1.5">
                      Set to <strong>0%</strong> for zero-fee withdrawals. Any rate up to 100% is supported.
                    </p>
                  </div>

                  {/* Preset quick buttons */}
                  <div>
                    <span className="block text-xs font-semibold text-slate-500 mb-2">
                      Quick Select Presets:
                    </span>
                    <div className="flex items-center gap-2 flex-wrap">
                      {[
                        { label: '0% (Free)', val: '0.00' },
                        { label: '2%', val: '2.00' },
                        { label: '5%', val: '5.00' },
                        { label: '8%', val: '8.00' },
                        { label: '10% (Standard)', val: '10.00' },
                        { label: '15%', val: '15.00' },
                        { label: '20%', val: '20.00' },
                      ].map((preset) => {
                        const isSelected =
                          parseFloat(serviceChargePercent) === parseFloat(preset.val);
                        return (
                          <button
                            key={preset.val}
                            type="button"
                            onClick={() => setServiceChargePercent(preset.val)}
                            className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-all border ${
                              isSelected
                                ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm'
                                : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100 hover:text-slate-900'
                            }`}
                          >
                            {preset.label}
                          </button>
                        );
                      })}
                    </div>
                  </div>
                </div>
              </div>

              {/* Card 2: Withdrawal Minimum & Maximum Limits */}
              <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-5">
                <div className="flex items-center gap-3">
                  <div className="p-2.5 rounded-xl bg-amber-100 text-amber-700">
                    <Sliders className="w-5 h-5" />
                  </div>
                  <div>
                    <h3 className="text-base font-bold text-slate-900">
                      Withdrawal Limits & Security Thresholds
                    </h3>
                    <p className="text-xs text-slate-500">
                      Define the minimum and maximum gross amounts allowed per withdrawal request.
                    </p>
                  </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  {/* Minimum Withdrawal Amount */}
                  <div className="bg-slate-50/80 border border-slate-200 rounded-xl p-4 space-y-2">
                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-700">
                      Minimum Withdrawal ($ USD)
                    </label>
                    <div className="relative">
                      <span className="absolute left-3.5 top-1/2 -translate-y-1/2 font-bold text-slate-400 text-sm">
                        $
                      </span>
                      <InputText
                        type="number"
                        step="0.01"
                        min="0.01"
                        max="1000000"
                        value={minWithdrawalAmount}
                        onChange={(e) => setMinWithdrawalAmount(e.target.value)}
                        placeholder="5.00"
                        className="w-full text-base font-bold border border-slate-300 rounded-lg pl-8 pr-3 py-2 bg-white text-slate-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                      />
                    </div>
                    <p className="text-[11px] text-slate-500">
                      Requests below this amount will be rejected with an informative error message.
                    </p>
                  </div>

                  {/* Maximum Withdrawal Amount */}
                  <div className="bg-slate-50/80 border border-slate-200 rounded-xl p-4 space-y-2">
                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-700">
                      Maximum Withdrawal ($ USD)
                    </label>
                    <div className="relative">
                      <span className="absolute left-3.5 top-1/2 -translate-y-1/2 font-bold text-slate-400 text-sm">
                        $
                      </span>
                      <InputText
                        type="number"
                        step="1"
                        min="0"
                        max="10000000"
                        value={maxWithdrawalAmount}
                        onChange={(e) => setMaxWithdrawalAmount(e.target.value)}
                        placeholder="10000.00"
                        className="w-full text-base font-bold border border-slate-300 rounded-lg pl-8 pr-3 py-2 bg-white text-slate-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                      />
                    </div>
                    <p className="text-[11px] text-slate-500">
                      Per-transaction limit. Enter <strong>0</strong> for unlimited maximum payout.
                    </p>
                  </div>
                </div>
              </div>

              {/* Card 3: System Availability & Maintenance Mode */}
              <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-5">
                <div className="flex items-center gap-3">
                  <div className="p-2.5 rounded-xl bg-purple-100 text-purple-700">
                    <Power className="w-5 h-5" />
                  </div>
                  <div>
                    <h3 className="text-base font-bold text-slate-900">
                      Withdrawal System Availability
                    </h3>
                    <p className="text-xs text-slate-500">
                      Enable or temporarily pause user withdrawal requests across the platform.
                    </p>
                  </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <button
                    type="button"
                    onClick={() => setWithdrawalStatus('enabled')}
                    className={`p-4 rounded-xl border text-left transition-all flex items-start gap-3 ${
                      withdrawalStatus === 'enabled'
                        ? 'border-emerald-500 bg-emerald-50/60 ring-2 ring-emerald-500/20'
                        : 'border-slate-200 bg-white hover:bg-slate-50'
                    }`}
                  >
                    <div
                      className={`w-4 h-4 rounded-full mt-0.5 border flex items-center justify-center ${
                        withdrawalStatus === 'enabled'
                          ? 'border-emerald-600 bg-emerald-600'
                          : 'border-slate-300 bg-white'
                      }`}
                    >
                      {withdrawalStatus === 'enabled' && (
                        <span className="w-1.5 h-1.5 rounded-full bg-white"></span>
                      )}
                    </div>
                    <div>
                      <div className="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                        <span className="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Enabled (Normal Live)
                      </div>
                      <p className="text-[11px] text-slate-500 mt-0.5">
                        Members can submit withdrawal requests from their wallet balance anytime.
                      </p>
                    </div>
                  </button>

                  <button
                    type="button"
                    onClick={() => setWithdrawalStatus('disabled')}
                    className={`p-4 rounded-xl border text-left transition-all flex items-start gap-3 ${
                      withdrawalStatus === 'disabled'
                        ? 'border-rose-500 bg-rose-50/60 ring-2 ring-rose-500/20'
                        : 'border-slate-200 bg-white hover:bg-slate-50'
                    }`}
                  >
                    <div
                      className={`w-4 h-4 rounded-full mt-0.5 border flex items-center justify-center ${
                        withdrawalStatus === 'disabled'
                          ? 'border-rose-600 bg-rose-600'
                          : 'border-slate-300 bg-white'
                      }`}
                    >
                      {withdrawalStatus === 'disabled' && (
                        <span className="w-1.5 h-1.5 rounded-full bg-white"></span>
                      )}
                    </div>
                    <div>
                      <div className="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                        <span className="w-2 h-2 rounded-full bg-rose-500"></span>
                        Disabled (Maintenance)
                      </div>
                      <p className="text-[11px] text-slate-500 mt-0.5">
                        Withdrawal requests will be blocked with a maintenance notice.
                      </p>
                    </div>
                  </button>
                </div>
              </div>
            </div>

            {/* Right 5 Columns: Live Calculator Simulator & Member Instructions */}
            <div className="lg:col-span-5 space-y-6">
              {/* Simulator Card */}
              <div className="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white rounded-2xl shadow-xl p-6 border border-indigo-900/50 space-y-5">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <div className="p-2 rounded-lg bg-indigo-500/20 text-indigo-400">
                      <Sliders className="w-4 h-4" />
                    </div>
                    <span className="text-xs font-bold tracking-wider uppercase text-indigo-300">
                      Live Calculation Simulator
                    </span>
                  </div>
                  <span className="text-[11px] px-2 py-0.5 rounded-md bg-indigo-500/30 text-indigo-200 font-semibold">
                    Real-time
                  </span>
                </div>

                <div className="space-y-2">
                  <label className="text-xs text-slate-300 font-medium">
                    Test Gross Withdrawal Amount ($):
                  </label>
                  <div className="relative">
                    <span className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-bold">
                      $
                    </span>
                    <input
                      type="number"
                      step="1"
                      min="1"
                      value={simTestAmount}
                      onChange={(e) => setSimTestAmount(e.target.value)}
                      placeholder="100.00"
                      className="w-full bg-white/10 border border-white/20 rounded-xl pl-8 pr-4 py-2 text-white font-bold text-lg placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:bg-white/15"
                    />
                  </div>
                </div>

                {/* Calculation breakdown */}
                <div className="bg-white/5 border border-white/10 rounded-xl p-4 space-y-3">
                  <div className="flex justify-between items-center text-xs">
                    <span className="text-slate-300">Gross Withdrawal:</span>
                    <span className="font-bold text-white">${testGross.toFixed(2)} USD</span>
                  </div>

                  <div className="flex justify-between items-center text-xs">
                    <span className="text-amber-300 flex items-center gap-1">
                      <TrendingDown className="w-3.5 h-3.5" />
                      Platform Service Charge ({numFee.toFixed(2)}%):
                    </span>
                    <span className="font-bold text-amber-300">-${simDeduction.toFixed(2)} USD</span>
                  </div>

                  <div className="pt-2 border-t border-white/10 flex justify-between items-center">
                    <div>
                      <span className="text-xs text-emerald-400 font-semibold block">
                        Net Payout to Member:
                      </span>
                      <span className="text-[10px] text-slate-400">Sent via BEP-20 USDT</span>
                    </div>
                    <span className="text-xl font-extrabold text-emerald-400">
                      ${simNet.toFixed(2)} USDT
                    </span>
                  </div>
                </div>

                {/* Progress bar showing fee retention */}
                <div className="space-y-1.5">
                  <div className="flex justify-between text-[11px] text-slate-400">
                    <span>Member Payout ({Math.max(0, 100 - numFee).toFixed(1)}%)</span>
                    <span>Fee Retained ({numFee.toFixed(1)}%)</span>
                  </div>
                  <div className="w-full h-2.5 rounded-full bg-white/10 overflow-hidden flex">
                    <div
                      className="bg-emerald-500 transition-all duration-300 h-full"
                      style={{ width: `${Math.max(0, 100 - numFee)}%` }}
                    />
                    <div
                      className="bg-amber-500 transition-all duration-300 h-full"
                      style={{ width: `${Math.min(100, numFee)}%` }}
                    />
                  </div>
                </div>

                <div className="p-3 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-[11px] text-indigo-200 flex items-start gap-2">
                  <Info className="w-4 h-4 shrink-0 mt-0.5 text-indigo-400" />
                  <span>
                    When a member enters ${testGross.toFixed(2)} on their withdrawal form, they will
                    see exactly <strong>-${simDeduction.toFixed(2)}</strong> service charge and will
                    receive <strong>${simNet.toFixed(2)}</strong> USDT in their BEP-20 wallet.
                  </span>
                </div>
              </div>

              {/* Card 4: Member Payout Instructions */}
              <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                <div className="flex items-center gap-3">
                  <div className="p-2.5 rounded-xl bg-blue-100 text-blue-700">
                    <FileText className="w-5 h-5" />
                  </div>
                  <div>
                    <h3 className="text-sm font-bold text-slate-900">Member Withdrawal Policy Note</h3>
                    <p className="text-xs text-slate-500">
                      Instructions displayed on the user's withdrawal portal.
                    </p>
                  </div>
                </div>

                <InputTextarea
                  value={instructions}
                  onChange={(e) => setInstructions(e.target.value)}
                  rows={4}
                  placeholder="Enter withdrawal instructions, processing turnaround time, and policy for members..."
                  className="w-full text-xs border border-slate-300 rounded-xl p-3 bg-white text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 shadow-sm"
                />

                <div className="flex items-center gap-2 text-xs text-slate-500">
                  <Clock className="w-3.5 h-3.5 text-slate-400" />
                  <span>Standard settlement window: 15 to 60 minutes after approval.</span>
                </div>
              </div>
            </div>
          </div>

          {/* Bottom Action Footer */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex items-center justify-between flex-wrap gap-3">
            <div className="flex items-center gap-2 text-xs text-slate-600">
              <ShieldCheck className="w-4 h-4 text-emerald-600" />
              <span>
                Changes take effect instantly for all future member withdrawal calculations.
              </span>
            </div>

            <div className="flex items-center gap-3">
              <Button
                type="button"
                severity="secondary"
                outlined
                label="Reset"
                onClick={fetchSettings}
                disabled={isLoading || isSaving}
                className="text-xs font-semibold px-4 py-2 border-slate-300 text-slate-700 hover:bg-slate-50"
              />
              <Button
                type="submit"
                icon={<Save className="w-4 h-4 mr-1.5" />}
                label={isSaving ? 'Saving Settings...' : 'Save Settings'}
                disabled={isLoading || isSaving}
                loading={isSaving}
                className="text-xs font-semibold px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white border-indigo-600 shadow-sm transition-all"
              />
            </div>
          </div>
        </form>
      )}
    </div>
  );
}

export default WithdrawalSettingsPage;
