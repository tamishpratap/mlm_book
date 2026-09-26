import { useState, useEffect, useRef } from 'react';
import {
  QrCode,
  Wallet,
  DollarSign,
  Upload,
  Save,
  RefreshCw,
  FileText,
  Copy,
  Check,
  ShieldCheck,
  Percent,
} from 'lucide-react';
import { Button } from 'primereact/button';
import { InputText } from 'primereact/inputtext';
import { InputTextarea } from 'primereact/inputtextarea';
import { Toast } from 'primereact/toast';
import { PageHeader } from '../../components/common/PageHeader';
import { LoadingSpinner } from '../../components/common/LoadingSpinner';
import { ErrorState } from '../../components/common/ErrorState';
import { fundsApi } from '../../api';

const ASSET_BASE = (import.meta.env.VITE_ASSET_URL || import.meta.env.VITE_BACKEND_URL || '').replace(/\/+$/, '');

function resolveAssetUrl(url) {
  if (!url) return null;
  if (url.startsWith('http://') || url.startsWith('https://')) {
    try {
      const u = new URL(url);
      if (u.hostname === '127.0.0.1' || u.hostname === 'localhost' || u.hostname === 'mytesting.fun') {
        return ASSET_BASE ? `${ASSET_BASE}${u.pathname}` : u.pathname;
      }
      if (u.hostname === 'mlmbookai.com' && u.protocol === 'http:') {
        u.protocol = 'https:';
        return u.toString();
      }
    } catch {
      // fallback
    }
    return url;
  }
  const cleanPath = url.startsWith('/') ? url : `/${url}`;
  return ASSET_BASE ? `${ASSET_BASE}${cleanPath}` : cleanPath;
}

export function DepositSettingsPage() {
  const toast = useRef(null);

  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState(null);

  // Form State (USD + Crypto Wallet + Dynamic Service Charge)
  const [cryptoWalletAddress, setCryptoWalletAddress] = useState('');
  const [serviceChargePercent, setServiceChargePercent] = useState('0.00');
  const [instructions, setInstructions] = useState('');
  const [currentQrUrl, setCurrentQrUrl] = useState(null);
  const [copiedWallet, setCopiedWallet] = useState(false);

  // File Upload State
  const [selectedFile, setSelectedFile] = useState(null);
  const [previewUrl, setPreviewUrl] = useState(null);
  const fileInputRef = useRef(null);

  const fetchSettings = () => {
    setIsLoading(true);
    setError(null);
    fundsApi
      .getSettings()
      .then((res) => {
        const s = res.settings || {};
        setCryptoWalletAddress(s.deposit_crypto_wallet_address || '');
        setServiceChargePercent(
          s.deposit_fee_percent !== undefined
            ? String(s.deposit_fee_percent)
            : (s.service_charge_percent !== undefined ? String(s.service_charge_percent) : '0.00')
        );
        setInstructions(s.deposit_instructions || '');
        setCurrentQrUrl(s.deposit_qr_url || null);
      })
      .catch((err) => {
        setError(err.response?.data?.message || 'Failed to load deposit settings.');
      })
      .finally(() => {
        setIsLoading(false);
      });
  };

  useEffect(() => {
    let isMounted = true;
    fundsApi
      .getSettings()
      .then((res) => {
        if (!isMounted) return;
        const s = res.settings || {};
        setCryptoWalletAddress(s.deposit_crypto_wallet_address || '');
        setServiceChargePercent(
          s.deposit_fee_percent !== undefined
            ? String(s.deposit_fee_percent)
            : (s.service_charge_percent !== undefined ? String(s.service_charge_percent) : '0.00')
        );
        setInstructions(s.deposit_instructions || '');
        setCurrentQrUrl(s.deposit_qr_url || null);
      })
      .catch((err) => {
        if (!isMounted) return;
        setError(err.response?.data?.message || 'Failed to load deposit settings.');
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, []);

  const handleFileChange = (e) => {
    const file = e.target.files?.[0];
    if (file) {
      if (file.size > 5 * 1024 * 1024) {
        toast.current?.show({
          severity: 'warn',
          summary: 'File Too Large',
          detail: 'QR Code image must not exceed 5MB.',
          life: 4000,
        });
        return;
      }
      setSelectedFile(file);
      const url = URL.createObjectURL(file);
      setPreviewUrl(url);
    }
  };

  const handleCopyWallet = () => {
    if (cryptoWalletAddress && navigator.clipboard) {
      navigator.clipboard.writeText(cryptoWalletAddress);
      setCopiedWallet(true);
      setTimeout(() => setCopiedWallet(false), 2000);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    setIsSaving(true);
    try {
      const payload = {
        deposit_crypto_wallet_address: cryptoWalletAddress.trim(),
        deposit_instructions: instructions.trim(),
        deposit_fee_percent: parseFloat(serviceChargePercent) || 0,
        service_charge_percent: parseFloat(serviceChargePercent) || 0,
      };

      const files = {};
      if (selectedFile) {
        files.deposit_qr_image = selectedFile;
      }

      const res = await fundsApi.updateSettings(payload, files);
      const s = res.settings || {};
      setCryptoWalletAddress(s.deposit_crypto_wallet_address || '');
      setServiceChargePercent(
        s.deposit_fee_percent !== undefined
          ? String(s.deposit_fee_percent)
          : (s.service_charge_percent !== undefined ? String(s.service_charge_percent) : '0.00')
      );
      setInstructions(s.deposit_instructions || '');
      setCurrentQrUrl(s.deposit_qr_url || null);
      setSelectedFile(null);
      setPreviewUrl(null);

      toast.current?.show({
        severity: 'success',
        summary: 'Settings Saved',
        detail: 'Deposit Service Charge, Crypto Wallet Address, and Payment QR settings updated successfully.',
        life: 4000,
      });
    } catch (err) {
      toast.current?.show({
        severity: 'error',
        summary: 'Save Failed',
        detail: err.response?.data?.message || 'Failed to update deposit settings.',
        life: 5000,
      });
    } finally {
      setIsSaving(false);
    }
  };

  if (isLoading) {
    return <LoadingSpinner message="Loading deposit settings..." />;
  }

  if (error) {
    return <ErrorState message={error} onRetry={fetchSettings} />;
  }

  return (
    <div className="space-y-6 max-w-5xl mx-auto pb-12">
      <Toast ref={toast} />

      <PageHeader
        title="Deposit Settings (USDT BEP-20)"
        subtitle="Configure Payment QR Code and Crypto Wallet Address for Member USDT (BEP-20) advertising deposits"
      />

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Card 1: Payment QR Code */}
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
          <div className="flex items-center gap-3 mb-4">
            <div className="p-2.5 rounded-xl bg-blue-100 text-blue-600">
              <QrCode className="w-5 h-5" />
            </div>
            <div>
              <h3 className="text-base font-bold text-slate-900">
                Payment QR Code
              </h3>
              <p className="text-xs text-slate-500">
                Upload your official merchant or crypto payment QR code image shown to members when adding advertising funds.
              </p>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
            {/* QR Preview Box */}
            <div className="flex flex-col items-center justify-center p-4 bg-slate-50 rounded-xl border-2 border-dashed border-slate-200 aspect-square max-w-[220px] mx-auto w-full">
              {previewUrl ? (
                <div className="relative group w-full h-full flex items-center justify-center">
                  <img
                    src={previewUrl}
                    alt="New QR Preview"
                    className="max-h-full max-w-full object-contain rounded-lg shadow-sm"
                  />
                  <span className="absolute bottom-1 bg-amber-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">
                    New (Unsaved)
                  </span>
                </div>
              ) : currentQrUrl ? (
                <div className="relative w-full h-full flex items-center justify-center">
                  <img
                    src={resolveAssetUrl(currentQrUrl) || currentQrUrl}
                    alt="Active Payment QR"
                    className="max-h-full max-w-full object-contain rounded-lg shadow-sm"
                  />
                </div>
              ) : (
                <div className="text-center p-4">
                  <QrCode className="w-12 h-12 text-slate-300 mx-auto mb-2" />
                  <span className="text-xs text-slate-400 font-medium">No QR Code Uploaded</span>
                </div>
              )}
            </div>

            {/* QR Upload Controls */}
            <div className="md:col-span-2 space-y-3">
              <input
                type="file"
                ref={fileInputRef}
                onChange={handleFileChange}
                accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml"
                className="hidden"
              />

              <div className="flex flex-wrap items-center gap-3">
                <Button
                  type="button"
                  label={currentQrUrl || previewUrl ? 'Change QR Image' : 'Upload QR Image'}
                  icon={<Upload className="w-4 h-4 mr-2" />}
                  onClick={() => fileInputRef.current?.click()}
                  className="p-button-outlined p-button-sm border-blue-600 text-blue-600 hover:bg-blue-50"
                />

                {previewUrl && (
                  <Button
                    type="button"
                    label="Cancel Selection"
                    className="p-button-text p-button-secondary p-button-sm text-slate-500"
                    onClick={() => {
                      setSelectedFile(null);
                      setPreviewUrl(null);
                    }}
                  />
                )}
              </div>

              <div className="text-xs text-slate-400 space-y-1">
                <p>• Supported formats: PNG, JPG, JPEG, WEBP, SVG.</p>
                <p>• Maximum file size: 5 MB.</p>
                <p>• Recommended square resolution: 500x500 px or higher.</p>
              </div>
            </div>
          </div>
        </div>

        {/* Card 2: Crypto Wallet Address */}
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <div className="p-2.5 rounded-xl bg-emerald-100 text-emerald-600">
                <Wallet className="w-5 h-5" />
              </div>
              <div>
                <h3 className="text-base font-bold text-slate-900">
                  Crypto Wallet Address (BEP-20)
                </h3>
                <p className="text-xs text-slate-500">
                  Destination BEP-20 crypto wallet address shown to members for USDT advertising fund payments.
                </p>
              </div>
            </div>

            {cryptoWalletAddress && (
              <Button
                type="button"
                label={copiedWallet ? 'Copied' : 'Copy Wallet Address'}
                icon={copiedWallet ? <Check className="w-3.5 h-3.5 mr-1 text-emerald-600" /> : <Copy className="w-3.5 h-3.5 mr-1" />}
                onClick={handleCopyWallet}
                className="p-button-outlined p-button-sm text-xs border-slate-300 text-slate-700"
              />
            )}
          </div>

          <div>
            <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
              Crypto Wallet Address (BNB Smart Chain)
            </label>
            <InputText
              value={cryptoWalletAddress}
              onChange={(e) => setCryptoWalletAddress(e.target.value)}
              placeholder="Enter BEP-20 crypto wallet address (0x...)"
              className="w-full text-sm font-mono border border-slate-300 rounded-lg px-3.5 py-2.5 bg-white text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
            />
            <span className="text-[11px] text-slate-400 mt-1.5 block">
              Members will make advertising-fund payments in USDT (BEP-20) using the configured crypto wallet address or QR code.
            </span>
          </div>
        </div>

        {/* Card 3: Payment Currency & Dynamic Service Charge */}
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-5">
          <div className="flex items-center justify-between flex-wrap gap-2">
            <div className="flex items-center gap-3">
              <div className="p-2.5 rounded-xl bg-indigo-100 text-indigo-600">
                <Percent className="w-5 h-5" />
              </div>
              <div>
                <h3 className="text-base font-bold text-slate-900">
                  Deposit Service Charge & Fee Policy
                </h3>
                <p className="text-xs text-slate-500">
                  Configure dynamic service charge / platform fee percentage deducted on Member USDT deposits.
                </p>
              </div>
            </div>

            <div className="flex items-center gap-1.5 bg-slate-100 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600">
              <span>Active Status:</span>
              <span className={`px-2 py-0.5 rounded-md font-bold text-xs ${
                parseFloat(serviceChargePercent) > 0 
                  ? 'bg-amber-100 text-amber-800' 
                  : 'bg-emerald-100 text-emerald-800'
              }`}>
                {parseFloat(serviceChargePercent) > 0 ? `${parseFloat(serviceChargePercent)}% Service Charge` : '0% (Zero Fee)'}
              </span>
            </div>
          </div>

          {/* Service Charge Input & Presets */}
          <div className="bg-slate-50/70 border border-slate-200 rounded-xl p-4 space-y-3">
            <label className="block text-xs font-bold uppercase tracking-wider text-slate-700">
              Service Charge Percentage (%)
            </label>
            <div className="flex flex-wrap items-center gap-3">
              <div className="relative max-w-xs w-full">
                <InputText
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  value={serviceChargePercent}
                  onChange={(e) => setServiceChargePercent(e.target.value)}
                  placeholder="0.00"
                  className="w-full text-base font-bold border border-slate-300 rounded-lg pl-3.5 pr-10 py-2.5 bg-white text-slate-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                />
                <span className="absolute right-3.5 top-1/2 -translate-y-1/2 font-bold text-slate-400 text-sm">
                  %
                </span>
              </div>

              {/* Preset Buttons */}
              <div className="flex items-center gap-1.5 flex-wrap">
                {[
                  { label: '0% (Free)', val: '0.00' },
                  { label: '2%', val: '2.00' },
                  { label: '5%', val: '5.00' },
                  { label: '10%', val: '10.00' },
                  { label: '15%', val: '15.00' },
                ].map((preset) => (
                  <button
                    key={preset.val}
                    type="button"
                    onClick={() => setServiceChargePercent(preset.val)}
                    className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-all border ${
                      parseFloat(serviceChargePercent) === parseFloat(preset.val)
                        ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm'
                        : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100 hover:text-slate-900'
                    }`}
                  >
                    {preset.label}
                  </button>
                ))}
              </div>
            </div>

            {/* Live Calculation Simulation Box */}
            <div className={`p-3 rounded-lg border text-xs leading-relaxed ${
              parseFloat(serviceChargePercent) > 0
                ? 'bg-amber-50/80 border-amber-200 text-amber-900'
                : 'bg-emerald-50/80 border-emerald-200 text-emerald-900'
            }`}>
              {parseFloat(serviceChargePercent) > 0 ? (
                <div>
                  <strong>⚡ Dynamic Service Charge Active (On Top):</strong> Service charge is added on top of the deposit amount. Example: If member deposits <strong>100.00 USD</strong> with <strong>{parseFloat(serviceChargePercent)}%</strong> fee, the total amount payable is <strong>{(100 * (1 + parseFloat(serviceChargePercent) / 100)).toFixed(2)} USDT</strong>. The member receives <strong>${( (100 * (1 + parseFloat(serviceChargePercent) / 100)) / (1 + parseFloat(serviceChargePercent) / 100) ).toFixed(2)} USD</strong> into their Fund Wallet (Formula: Received Amount / (100 + {parseFloat(serviceChargePercent)}%)).
                </div>
              ) : (
                <div>
                  <strong>✨ Zero Fee Mode Active:</strong> 100% of deposited USDT will be credited to member accounts without any deductions (1 USDT = 1 USD).
                </div>
              )}
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-1">
            {/* Currency Card */}
            <div className="p-4 rounded-xl bg-slate-50 border border-slate-200">
              <div className="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">
                Payment Currency
              </div>
              <div className="text-2xl font-black text-indigo-600">
                USDT (BEP-20)
              </div>
              <span className="text-[11px] text-slate-400 mt-1 block">
                BNB Smart Chain Network
              </span>
            </div>

            {/* Payment Method Card */}
            <div className="p-4 rounded-xl bg-slate-50 border border-slate-200">
              <div className="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">
                Payment Method
              </div>
              <div className="text-xl font-black text-slate-800">
                Crypto + QR
              </div>
              <span className="text-[11px] text-slate-400 mt-1 block">
                Wallet address & scan QR
              </span>
            </div>

            {/* Credit Policy Card */}
            <div className={`p-4 rounded-xl border ${
              parseFloat(serviceChargePercent) > 0
                ? 'bg-amber-50/60 border-amber-200'
                : 'bg-emerald-50/60 border-emerald-200'
            }`}>
              <div className={`text-xs font-bold uppercase tracking-wider mb-1 ${
                parseFloat(serviceChargePercent) > 0 ? 'text-amber-800' : 'text-emerald-800'
              }`}>
                Deposit Fee
              </div>
              <div className={`text-2xl font-black ${
                parseFloat(serviceChargePercent) > 0 ? 'text-amber-600' : 'text-emerald-600'
              }`}>
                {parseFloat(serviceChargePercent) > 0 ? `${parseFloat(serviceChargePercent)}% Fee` : '0% (Free)'}
              </div>
              <span className="text-[11px] text-slate-400 mt-1 block">
                {parseFloat(serviceChargePercent) > 0
                  ? `Net Credit: ${(100 - parseFloat(serviceChargePercent)).toFixed(2)}%`
                  : '1 USDT = 1 USDT ad credit'}
              </span>
            </div>
          </div>

          <div className="p-3.5 rounded-xl bg-indigo-50/60 border border-indigo-200/80 flex items-center gap-2.5 text-xs text-indigo-800">
            <ShieldCheck className="w-5 h-5 text-indigo-600 flex-shrink-0" />
            <span>
              <strong>USDT (BEP-20) Rule:</strong> Deposits are accepted exclusively in <strong>USDT (BEP-20)</strong>. Changes to the service charge take effect immediately for all subsequent deposits.
            </span>
          </div>
        </div>

        {/* Card 4: Instructions for Members */}
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-3">
          <div className="flex items-center gap-3">
            <div className="p-2.5 rounded-xl bg-amber-100 text-amber-600">
              <FileText className="w-5 h-5" />
            </div>
            <div>
              <h3 className="text-base font-bold text-slate-900">
                Member Payment Instructions
              </h3>
              <p className="text-xs text-slate-500">
                Custom guide displayed on the Member Add Fund portal.
              </p>
            </div>
          </div>

          <InputTextarea
            value={instructions}
            onChange={(e) => setInstructions(e.target.value)}
            rows={3}
            placeholder="e.g. 1. Transfer payment in USD to the crypto wallet address or scan QR. 2. Submit transaction reference hash below."
            className="w-full text-sm border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 rounded-lg p-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
          />
        </div>

        {/* Save Bar */}
        <div className="flex items-center justify-between pt-2">
          <div className="text-xs text-slate-400">
            * Changes take effect immediately for new payment configurations. Historical records remain safely preserved.
          </div>

          <Button
            type="submit"
            disabled={isSaving}
            label={isSaving ? 'Saving Settings...' : 'Save Deposit Settings'}
            icon={isSaving ? <RefreshCw className="w-4 h-4 mr-2 animate-spin" /> : <Save className="w-4 h-4 mr-2" />}
            className="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-md transition-all"
          />
        </div>
      </form>
    </div>
  );
}

export default DepositSettingsPage;
