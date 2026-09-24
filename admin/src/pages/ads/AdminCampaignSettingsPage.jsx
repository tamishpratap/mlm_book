import { useState, useEffect, useRef, useCallback } from 'react';
import {
  Percent,
  DollarSign,
  Save,
  RefreshCw,
  ShieldCheck,
  Calculator,
} from 'lucide-react';
import { Button } from 'primereact/button';
import { InputText } from 'primereact/inputtext';
import { Toast } from 'primereact/toast';
import { PageHeader } from '../../components/common/PageHeader';
import { LoadingSpinner } from '../../components/common/LoadingSpinner';
import { ErrorState } from '../../components/common/ErrorState';
import { adCampaignsApi } from '../../api';

export function AdminCampaignSettingsPage() {
  const toast = useRef(null);

  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState(null);

  // Fee configuration
  const [feePercent, setFeePercent] = useState('2.5');
  const [testBudget, setTestBudget] = useState('30.00');

  const fetchSettings = useCallback(() => {
    setIsLoading(true);
    setError(null);
    adCampaignsApi
      .getSettings()
      .then((res) => {
        const s = res.settings || {};
        setFeePercent(String(s.campaign_platform_fee_percent ?? 2.5));
      })
      .catch((err) => {
        setError(err.response?.data?.message || 'Failed to load campaign settings.');
      })
      .finally(() => {
        setIsLoading(false);
      });
  }, []);

  useEffect(() => {
    let ignore = false;
    adCampaignsApi
      .getSettings()
      .then((res) => {
        if (!ignore) {
          const s = res.settings || {};
          setFeePercent(String(s.campaign_platform_fee_percent ?? 2.5));
          setIsLoading(false);
        }
      })
      .catch((err) => {
        if (!ignore) {
          setError(err.response?.data?.message || 'Failed to load campaign settings.');
          setIsLoading(false);
        }
      });
    return () => {
      ignore = true;
    };
  }, []);

  const handleSave = async (e) => {
    e.preventDefault();

    const numFee = parseFloat(feePercent);
    if (isNaN(numFee) || numFee < 0 || numFee > 100) {
      toast.current?.show({
        severity: 'warn',
        summary: 'Validation Error',
        detail: 'Campaign Platform Fee must be a valid number between 0% and 100%.',
        life: 4000,
      });
      return;
    }

    setIsSaving(true);
    try {
      const res = await adCampaignsApi.updateSettings({
        campaign_platform_fee_percent: numFee,
      });

      if (res && res.settings) {
        setFeePercent(String(res.settings.campaign_platform_fee_percent));
      }

      toast.current?.show({
        severity: 'success',
        summary: 'Settings Saved',
        detail: res.message || 'Campaign Platform Fee updated successfully.',
        life: 4000,
      });
    } catch (err) {
      toast.current?.show({
        severity: 'error',
        summary: 'Save Failed',
        detail: err.response?.data?.message || 'Failed to update campaign settings.',
        life: 5000,
      });
    } finally {
      setIsSaving(false);
    }
  };

  const parsedFee = parseFloat(feePercent) || 0;
  const parsedBudget = parseFloat(testBudget) || 0;
  const calculatedFee = (parsedBudget * (parsedFee / 100));
  const totalDebit = (parsedBudget + calculatedFee);

  if (isLoading) {
    return (
      <div className="p-6">
        <PageHeader
          title="Campaign Settings"
          subtitle="Configure platform billing fees and parameters for advertising campaigns."
        />
        <div className="card mt-4 p-8 flex justify-center items-center">
          <LoadingSpinner message="Loading campaign settings..." />
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-6">
        <PageHeader
          title="Campaign Settings"
          subtitle="Configure platform billing fees and parameters for advertising campaigns."
        />
        <div className="card mt-4 p-6">
          <ErrorState
            title="Failed to Load Settings"
            message={error}
            onRetry={fetchSettings}
          />
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <Toast ref={toast} position="top-right" />

      <PageHeader
        title="Campaign Settings"
        subtitle="Configure platform billing fees and parameters for advertising campaigns."
        actions={
          <Button
            label="Refresh"
            icon={<RefreshCw className="w-4 h-4 mr-2" />}
            className="p-button-outlined p-button-secondary p-button-sm rounded-xl font-semibold"
            onClick={fetchSettings}
            disabled={isSaving}
          />
        }
      />

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left Column: Settings Form */}
        <div className="lg:col-span-2 space-y-6">
          <div className="card p-6 bg-white rounded-2xl border border-slate-200 shadow-sm">
            <div className="flex items-center gap-3 pb-4 mb-5 border-b border-slate-100">
              <div className="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                <Percent className="w-5 h-5" />
              </div>
              <div>
                <h3 className="text-base font-bold text-slate-900 m-0">Platform Fee Configuration</h3>
                <p className="text-xs text-slate-500 m-0 mt-0.5">
                  Set the administrative platform fee applied on top of advertiser campaign budgets.
                </p>
              </div>
            </div>

            <form onSubmit={handleSave} className="space-y-5">
              <div>
                <label className="block text-xs font-bold text-slate-800 mb-1.5 flex items-center gap-1.5">
                  <span>Campaign Platform Fee (%)</span>
                  <span className="text-red-500 font-bold">*</span>
                </label>
                <div className="relative">
                  <InputText
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    value={feePercent}
                    onChange={(e) => setFeePercent(e.target.value)}
                    className="w-full pl-10 pr-12 has-start-icon has-end-addon text-sm font-bold font-mono rounded-xl bg-white text-slate-900 border border-slate-300 placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                    placeholder="2.5"
                    disabled={isSaving}
                    required
                  />
                  <div className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                    <Percent className="w-4 h-4" />
                  </div>
                  <div className="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-600 font-bold text-xs bg-slate-100 px-2 py-0.5 rounded">
                    %
                  </div>
                </div>
                <p className="text-xs text-slate-500 mt-1.5 leading-relaxed">
                  The percentage added to the advertiser&apos;s campaign budget upon creation. Default is <strong className="text-slate-800">2.5%</strong>.
                </p>
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-800 mb-1.5">
                  Billing Currency
                </label>
                <div className="relative">
                  <InputText
                    type="text"
                    value="USD ($) - US Dollar"
                    disabled
                    className="w-full pl-10 has-start-icon bg-slate-100 border border-slate-200 text-slate-600 font-bold rounded-xl cursor-not-allowed"
                  />
                  <div className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                    <DollarSign className="w-4 h-4" />
                  </div>
                </div>
                <p className="text-xs text-slate-500 mt-1.5">
                  All campaign budgets, platform fees, and member advertising wallets operate strictly in USD.
                </p>
              </div>

              <div className="pt-3 border-t border-slate-100 flex items-center justify-between">
                <span className="text-xs text-slate-500">
                  Canonical Configuration: <code>settings.campaign_platform_fee_percent</code>
                </span>
                <Button
                  type="submit"
                  label={isSaving ? 'Saving Changes...' : 'Save Settings'}
                  icon={isSaving ? <RefreshCw className="w-4 h-4 mr-2 animate-spin" /> : <Save className="w-4 h-4 mr-2" />}
                  className="p-button-primary rounded-xl font-bold"
                  disabled={isSaving}
                />
              </div>
            </form>
          </div>

          {/* Historical Safety Note */}
          <div className="card p-5 bg-blue-50/80 border border-blue-200 rounded-2xl flex items-start gap-3.5 shadow-sm">
            <ShieldCheck className="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" />
            <div className="text-xs text-blue-950 leading-relaxed">
              <strong className="block font-bold text-sm mb-1 text-blue-950">
                Immutable Historical Campaign Snapshots
              </strong>
              When an advertiser creates a campaign, the platform snapshots the exact fee percentage, fee amount, and wallet debit.
              Modifying this percentage will apply strictly to <strong>future campaigns</strong>. Existing campaigns remain untouched.
            </div>
          </div>
        </div>

        {/* Right Column: Live Calculation Simulation */}
        <div className="space-y-6">
          <div className="card p-6 bg-white rounded-2xl border border-slate-200 shadow-sm">
            <div className="flex items-center gap-2.5 pb-3 mb-4 border-b border-slate-100">
              <Calculator className="w-5 h-5 text-indigo-600" />
              <h4 className="text-base font-bold text-slate-900 m-0">Live Fee Simulator</h4>
            </div>

            <div className="space-y-4">
              <div>
                <label htmlFor="sample-budget" className="block text-xs font-bold text-slate-800 mb-1">
                  Sample Campaign Budget ($)
                </label>
                <div className="relative">
                  <InputText
                    id="sample-budget"
                    type="number"
                    step="5"
                    min="1"
                    value={testBudget}
                    onChange={(e) => setTestBudget(e.target.value)}
                    className="w-full pl-10 pr-3.5 has-start-addon text-sm font-bold font-mono rounded-xl bg-white text-slate-900 border border-slate-300 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                  />
                  <div className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs font-bold">
                    $
                  </div>
                </div>
              </div>

              <div className="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2.5 text-xs">
                <div className="flex justify-between items-center text-slate-700">
                  <span className="font-medium">Campaign Running Budget:</span>
                  <span className="font-bold text-slate-900 text-sm font-mono">${parsedBudget.toFixed(2)} USD</span>
                </div>

                <div className="flex justify-between items-center text-slate-700">
                  <span className="font-medium">Platform Fee ({parsedFee.toFixed(2)}%):</span>
                  <span className="font-bold text-blue-600 text-sm font-mono">+${calculatedFee.toFixed(2)} USD</span>
                </div>

                <div className="pt-2 border-t border-slate-200 flex justify-between items-center">
                  <span className="font-bold text-slate-900">Total Wallet Debit:</span>
                  <span className="font-extrabold text-indigo-600 text-base font-mono">
                    ${totalDebit.toFixed(2)} USD
                  </span>
                </div>
              </div>

              <div className="p-3.5 bg-emerald-50/80 border border-emerald-200 rounded-xl text-emerald-950 text-xs leading-relaxed">
                <strong>Budget Protection Rule:</strong> The entire <strong>${parsedBudget.toFixed(2)} USD</strong> belongs exclusively to the campaign&apos;s ad impressions and clicks. The <strong>${calculatedFee.toFixed(2)} USD</strong> fee is retained by the platform.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export default AdminCampaignSettingsPage;
