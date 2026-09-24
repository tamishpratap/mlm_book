import { useState, useEffect, useCallback } from 'react';
import {
  Wallet,
  QrCode,
  Copy,
  CheckCircle2,
  AlertCircle,
  ArrowRight,
  ShieldCheck,
  RefreshCw,
  ExternalLink,
} from 'lucide-react';
import businessApi from '../../../api/businessApi';
import { getMediaUrl } from '../../../utils/assetHelper';

const USDT_PRESETS = [10, 25, 50, 100, 250, 500];

export function AddFundView({ page = null, onDepositSubmitted = null, showHistory = true }) {
  const [config, setConfig] = useState(null);
  const [deposits, setDeposits] = useState([]);
  const [memberAdBalance, setMemberAdBalance] = useState(0.00);
  const [isLoadingConfig, setIsLoadingConfig] = useState(true);
  const [isLoadingHistory, setIsLoadingHistory] = useState(true);
  const [copiedWallet, setCopiedWallet] = useState(false);
  const [qrLoadFailed, setQrLoadFailed] = useState(false);

  // Form State (USDT BEP-20)
  const [amountUsdt, setAmountUsdt] = useState('50');
  const [transactionHash, setTransactionHash] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errors, setErrors] = useState({});
  const [successMessage, setSuccessMessage] = useState('');
  const [errorMessage, setErrorMessage] = useState('');

  // Fetch Config
  const fetchConfig = useCallback(async () => {
    setIsLoadingConfig(true);
    try {
      const res = await businessApi.getDepositSettings();
      if (res && res.config) {
        setConfig(res.config);
        if (res.config.member_ad_balance !== undefined) {
          setMemberAdBalance(Number(res.config.member_ad_balance) || 0.00);
        }
      }
    } catch {
      // Ignore
    } finally {
      setIsLoadingConfig(false);
    }
  }, []);

  // Fetch Deposit History (Only when showHistory is true)
  const fetchHistory = useCallback(async () => {
    if (!showHistory) return;
    setIsLoadingHistory(true);
    try {
      const params = page?.slug ? { business_page_slug: page.slug } : {};
      const res = await businessApi.getMemberDeposits(params);
      setDeposits(res.deposits?.data || (Array.isArray(res.deposits) ? res.deposits : []));
      if (res.member_ad_balance !== undefined) {
        setMemberAdBalance(Number(res.member_ad_balance) || 0.00);
      }
    } catch {
      // Ignore
    } finally {
      setIsLoadingHistory(false);
    }
  }, [page, showHistory]);

  useEffect(() => {
    fetchConfig();
    if (showHistory) {
      fetchHistory();
    }
  }, [fetchConfig, fetchHistory, showHistory]);

  const handleCopyWallet = () => {
    const address = config?.crypto_wallet_address;
    if (address && navigator.clipboard) {
      navigator.clipboard.writeText(address);
      setCopiedWallet(true);
      setTimeout(() => setCopiedWallet(false), 2500);
    }
  };

  const grossUsdt = parseFloat(amountUsdt) || 0;
  const expectedCreditUsdt = grossUsdt > 0 ? grossUsdt.toFixed(2) : '0.00';

  const validate = () => {
    const errs = {};

    if (!amountUsdt || isNaN(grossUsdt) || grossUsdt < 1) {
      errs.amount = 'Please enter a valid deposit amount of at least 1.00 USDT.';
    }

    if (!transactionHash.trim()) {
      errs.transactionHash = 'Transaction Hash is required.';
    } else if (transactionHash.trim().length < 4) {
      errs.transactionHash = 'Transaction Hash must be at least 4 characters.';
    }

    setErrors(errs);
    return Object.keys(errs).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSuccessMessage('');
    setErrorMessage('');

    if (!validate()) return;
    if (isSubmitting) return;

    setIsSubmitting(true);

    try {
      const payload = {
        amount_usdt: grossUsdt,
        amount_usd: grossUsdt,
        currency: 'USDT',
        network: 'BEP-20',
        token: 'USDT',
        transaction_hash: transactionHash.trim(),
        business_page_slug: page?.slug || null,
      };

      const res = await businessApi.createDeposit(payload);

      if (res.verified || res.status === 'approved') {
        setSuccessMessage(res.message || `USDT (BEP-20) transaction verified on-chain! $${res.credited_amount || grossUsdt} USD has been credited to your advertising balance.`);
      } else {
        setSuccessMessage(res.message || 'USDT (BEP-20) deposit submitted. Status: Confirming on BNB Smart Chain.');
      }

      if (res.member_ad_balance !== undefined) {
        setMemberAdBalance(Number(res.member_ad_balance));
      }

      setAmountUsdt('50');
      setTransactionHash('');
      setErrors({});
      fetchHistory();

      if (onDepositSubmitted) {
        onDepositSubmitted(res.deposit);
      }
    } catch (err) {
      setErrorMessage(err.response?.data?.message || err.response?.data?.verification_error || 'Failed to submit deposit request. Please verify your transaction hash.');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: '24px' }}>
      {/* Top Banner / Instructions */}
      <div
        style={{
          padding: '20px 24px',
          borderRadius: '16px',
          backgroundColor: '#eff6ff',
          border: '1px solid #bfdbfe',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
          flexWrap: 'wrap',
          gap: '16px',
        }}
      >
        <div style={{ display: 'flex', alignItems: 'flex-start', gap: '16px', flex: 1, minWidth: '280px' }}>
          <div style={{ padding: '8px', borderRadius: '12px', backgroundColor: '#dbeafe', color: '#2563eb', flexShrink: 0 }}>
            <Wallet size={24} />
          </div>
          <div>
            <h3 style={{ fontSize: '16px', fontWeight: 800, color: '#1e3a8a', margin: '0 0 4px 0' }}>
              Add Advertising Funds — USDT (BEP-20)
            </h3>
            <p style={{ fontSize: '13px', color: '#1e40af', margin: 0, lineHeight: 1.5 }}>
              Send your payment in USDT (BEP-20) to the configured crypto wallet address or scan the QR code below, then submit your transaction hash.
            </p>
          </div>
        </div>

        {/* Current Available Ad Funds */}
        <div
          style={{
            padding: '12px 18px',
            borderRadius: '12px',
            backgroundColor: '#ffffff',
            border: '1px solid #bfdbfe',
            boxShadow: '0 1px 3px rgba(0,0,0,0.05)',
            textAlign: 'right',
            flexShrink: 0,
          }}
        >
          <span style={{ fontSize: '11px', fontWeight: 700, color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
            Available Ad Funds
          </span>
          <div style={{ fontSize: '22px', fontWeight: 900, color: '#2563eb', marginTop: '2px' }}>
            ${memberAdBalance.toFixed(2)} USD
          </div>
        </div>
      </div>

      {/* Main Grid: Payment Details & Deposit Form */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(320px, 1fr))', gap: '24px' }}>
        {/* Left Card: Payment Information (QR + Crypto Wallet Address + Mandatory Disclaimer) */}
        <div className="card" style={{ padding: '24px', borderRadius: '16px', display: 'flex', flexDirection: 'column', gap: '20px' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <QrCode size={20} color="#4f7df3" />
            <h4 style={{ fontSize: '15px', fontWeight: 800, color: 'var(--color-text-main, #1e293b)', margin: 0 }}>
              Payment Information
            </h4>
          </div>

          {isLoadingConfig ? (
            <div style={{ textAlign: 'center', padding: '40px', color: '#64748b', fontSize: '13px' }}>
              Loading payment details...
            </div>
          ) : !config?.is_available ? (
            <div
              style={{
                padding: '24px',
                textAlign: 'center',
                borderRadius: '12px',
                backgroundColor: '#f8fafc',
                border: '1px dashed #cbd5e1',
                color: '#64748b',
                fontSize: '13px',
              }}
            >
              <AlertCircle size={28} color="#94a3b8" style={{ margin: '0 auto 8px auto' }} />
              <p style={{ fontWeight: 600, margin: '0 0 4px 0', color: '#334155' }}>
                Payment details are currently unavailable.
              </p>
              <p style={{ margin: 0, fontSize: '12px' }}>
                Please check back shortly or contact platform administration.
              </p>
            </div>
          ) : (
            <>
              {/* QR Code Container */}
              <div
                style={{
                  display: 'flex',
                  flexDirection: 'column',
                  alignItems: 'center',
                  padding: '20px',
                  borderRadius: '14px',
                  backgroundColor: 'var(--color-subtle-bg, #f8fafc)',
                  border: '1px solid var(--color-border, #e2e8f0)',
                }}
              >
                {config.qr_url && !qrLoadFailed ? (
                  <div style={{ width: '180px', height: '180px', position: 'relative', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                    <img
                      src={getMediaUrl(config.qr_url) || config.qr_url}
                      alt="Payment QR Code"
                      style={{ maxWidth: '100%', maxHeight: '100%', objectFit: 'contain', borderRadius: '10px' }}
                      onError={() => setQrLoadFailed(true)}
                    />
                  </div>
                ) : (
                  <div style={{ textAlign: 'center', padding: '20px', color: '#64748b', fontSize: '12.5px' }}>
                    <QrCode size={40} color="#cbd5e1" style={{ margin: '0 auto 8px auto' }} />
                    <p style={{ margin: 0, fontWeight: 600, color: '#475569' }}>
                      {qrLoadFailed ? 'Payment QR is currently unavailable' : 'Please use the BEP-20 crypto wallet address below to complete your payment.'}
                    </p>
                    <span style={{ fontSize: '11px', color: '#94a3b8', marginTop: '4px', display: 'block' }}>
                      Transfer USDT (BEP-20) directly to the configured wallet address
                    </span>
                  </div>
                )}
                <span style={{ fontSize: '11.5px', color: '#64748b', marginTop: '10px', fontWeight: 600, textAlign: 'center' }}>
                  Scan QR code or use the BEP-20 wallet address below
                </span>
              </div>

              {/* Crypto Wallet Address Box */}
              <div
                style={{
                  padding: '14px 16px',
                  borderRadius: '12px',
                  backgroundColor: '#ffffff',
                  border: '1px solid var(--color-border, #e2e8f0)',
                  display: 'flex',
                  flexDirection: 'column',
                  gap: '10px',
                }}
              >
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '8px' }}>
                  <span style={{ fontSize: '11px', color: '#64748b', fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                    BEP-20 Wallet Address
                  </span>
                  <span style={{ fontSize: '11px', color: '#16a34a', fontWeight: 700, backgroundColor: '#f0fdf4', border: '1px solid #bbf7d0', padding: '2px 8px', borderRadius: '6px' }}>
                    Payment: USDT (BEP-20)
                  </span>
                </div>

                {config.crypto_wallet_address ? (
                  <div
                    style={{
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'space-between',
                      gap: '10px',
                      backgroundColor: '#f8fafc',
                      padding: '8px 12px',
                      borderRadius: '8px',
                      border: '1px solid #e2e8f0',
                      flexWrap: 'wrap',
                    }}
                  >
                    <span
                      style={{
                        fontSize: '12.5px',
                        fontFamily: 'monospace',
                        fontWeight: 700,
                        color: '#1e293b',
                        wordBreak: 'break-all',
                        overflowWrap: 'anywhere',
                        lineHeight: 1.4,
                        flex: '1 1 200px',
                      }}
                      title={config.crypto_wallet_address}
                    >
                      {config.crypto_wallet_address}
                    </span>

                    <button
                      type="button"
                      onClick={handleCopyWallet}
                      style={{
                        padding: '7px 12px',
                        borderRadius: '8px',
                        border: '1px solid #4f7df3',
                        backgroundColor: copiedWallet ? '#22c55e' : 'rgba(79, 125, 243, 0.08)',
                        color: copiedWallet ? '#ffffff' : '#4f7df3',
                        fontWeight: 700,
                        fontSize: '12px',
                        cursor: 'pointer',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '6px',
                        flexShrink: 0,
                        transition: 'all 0.2s',
                        whiteSpace: 'nowrap',
                      }}
                    >
                      {copiedWallet ? (
                        <>
                          <CheckCircle2 size={13} />
                          <span>Copied!</span>
                        </>
                      ) : (
                        <>
                          <Copy size={13} />
                          <span>Copy Wallet Address</span>
                        </>
                      )}
                    </button>
                  </div>
                ) : (
                  <div style={{ padding: '10px 12px', borderRadius: '8px', backgroundColor: '#f8fafc', border: '1px dashed #cbd5e1', color: '#64748b', fontSize: '12px', fontStyle: 'italic' }}>
                    Wallet Address Not Configured
                  </div>
                )}
              </div>

              {/* MANDATORY DISCLAIMER BOX */}
              <div
                style={{
                  padding: '14px 16px',
                  borderRadius: '12px',
                  backgroundColor: '#fef2f2',
                  border: '1px solid #fecaca',
                  display: 'flex',
                  alignItems: 'flex-start',
                  gap: '12px',
                }}
              >
                <AlertCircle size={20} color="#dc2626" style={{ flexShrink: 0, marginTop: '1px' }} />
                <div style={{ fontSize: '12.5px', color: '#991b1b', lineHeight: 1.5, fontWeight: 500 }}>
                  <strong style={{ color: '#b91c1c', fontWeight: 800 }}>IMPORTANT:</strong> Deposits are accepted exclusively in <strong>USDT (BEP-20)</strong>. Any other token or network is not supported and will not be credited.
                </div>
              </div>
            </>
          )}
        </div>

        {/* Right Card: Deposit Submission Form (USDT BEP-20) */}
        <div className="card" style={{ padding: '24px', borderRadius: '16px', display: 'flex', flexDirection: 'column', gap: '18px' }}>
          <div>
            <h4 style={{ fontSize: '15px', fontWeight: 800, color: 'var(--color-text-main, #1e293b)', margin: '0 0 4px 0' }}>
              Submit Deposit Request
            </h4>
            <p style={{ fontSize: '12.5px', color: '#64748b', margin: 0 }}>
              After transferring USDT (BEP-20) to the wallet address or scanning the QR code, enter the deposit amount and transaction hash below.
            </p>
          </div>

          {successMessage && (
            <div
              style={{
                padding: '12px 16px',
                borderRadius: '10px',
                backgroundColor: '#f0fdf4',
                border: '1px solid #bbf7d0',
                color: '#166534',
                fontSize: '13px',
                display: 'flex',
                alignItems: 'center',
                gap: '10px',
              }}
            >
              <CheckCircle2 size={18} color="#16a34a" style={{ flexShrink: 0 }} />
              <div>
                <strong>{successMessage}</strong>
                <div style={{ fontSize: '11.5px', color: '#15803d', marginTop: '2px' }}>
                  Your deposit has been queued for verification.
                </div>
              </div>
            </div>
          )}

          {errorMessage && (
            <div
              style={{
                padding: '12px 16px',
                borderRadius: '10px',
                backgroundColor: '#fef2f2',
                border: '1px solid #fecaca',
                color: '#991b1b',
                fontSize: '13px',
                display: 'flex',
                alignItems: 'center',
                gap: '10px',
              }}
            >
              <AlertCircle size={18} color="#dc2626" style={{ flexShrink: 0 }} />
              <span>{errorMessage}</span>
            </div>
          )}

          <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
            {/* Amount Input (USDT) */}
            <div>
              <label style={{ display: 'block', fontSize: '13px', fontWeight: 700, marginBottom: '6px', color: '#334155' }}>
                Deposit Amount (USDT) *
              </label>
              <div style={{ position: 'relative', display: 'flex', alignItems: 'center' }}>
                <input
                  type="number"
                  step="1"
                  min="1"
                  value={amountUsdt}
                  onChange={(e) => setAmountUsdt(e.target.value)}
                  placeholder="50"
                  className="biz-search-input"
                  style={{ width: '100%', paddingLeft: '14px', paddingRight: '60px', borderRadius: '10px' }}
                />
                <span style={{ position: 'absolute', right: '14px', top: '50%', transform: 'translateY(-50%)', fontWeight: 800, fontSize: '12px', color: '#64748b', pointerEvents: 'none', userSelect: 'none' }}>
                  USDT
                </span>
              </div>
              {errors.amount && (
                <span style={{ color: '#ef4444', fontSize: '12px', marginTop: '4px', display: 'block' }}>{errors.amount}</span>
              )}

              {/* USDT Presets */}
              <div style={{ display: 'flex', flexWrap: 'wrap', gap: '6px', marginTop: '8px' }}>
                {USDT_PRESETS.map((p) => (
                  <button
                    key={p}
                    type="button"
                    onClick={() => setAmountUsdt(String(p))}
                    style={{
                      padding: '5px 10px',
                      borderRadius: '6px',
                      border: String(p) === String(amountUsdt) ? '1px solid #4f7df3' : '1px solid #e2e8f0',
                      backgroundColor: String(p) === String(amountUsdt) ? 'rgba(79, 125, 243, 0.1)' : '#ffffff',
                      color: String(p) === String(amountUsdt) ? '#4f7df3' : '#475569',
                      fontWeight: 600,
                      fontSize: '12px',
                      cursor: 'pointer',
                    }}
                  >
                    {p} USDT
                  </button>
                ))}
              </div>
            </div>

            {/* Exact USDT Credit Summary */}
            <div
              style={{
                padding: '14px 16px',
                borderRadius: '12px',
                backgroundColor: '#f8fafc',
                border: '1px solid #e2e8f0',
                display: 'flex',
                flexDirection: 'column',
                gap: '8px',
              }}
            >
              <div style={{ fontSize: '11px', fontWeight: 800, color: '#475569', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                Financial Summary & Credit Preview
              </div>

              <div style={{ display: 'flex', flexDirection: 'column', gap: '5px', fontSize: '12.5px' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', color: '#334155' }}>
                  <span>Deposit Amount:</span>
                  <strong>{grossUsdt.toFixed(2)} USDT</strong>
                </div>

                <div style={{ display: 'flex', justifyContent: 'space-between', color: '#64748b' }}>
                  <span>Network:</span>
                  <span className="font-semibold text-slate-700">BEP-20 (BNB Smart Chain)</span>
                </div>

                <div style={{ display: 'flex', justifyContent: 'space-between', color: '#64748b' }}>
                  <span>Deposit Fee:</span>
                  <span className="font-semibold text-emerald-600">0% (0.00 USDT)</span>
                </div>

                <div style={{ display: 'flex', justifyContent: 'space-between', color: '#2563eb', borderTop: '1px solid #e2e8f0', paddingTop: '6px' }}>
                  <span style={{ fontWeight: 700 }}>Expected Ad Credit:</span>
                  <strong style={{ fontWeight: 800, fontSize: '14px' }}>${expectedCreditUsdt} USD</strong>
                </div>
              </div>

              <div style={{ fontSize: '11px', color: '#64748b', marginTop: '2px', display: 'flex', alignItems: 'center', gap: '4px' }}>
                <ShieldCheck size={13} color="#16a34a" />
                <span>Exact Credit Model: 100% of deposited USDT will be credited to your advertising balance.</span>
              </div>
            </div>

            {/* Transaction Hash Input */}
            <div>
              <label style={{ display: 'block', fontSize: '13px', fontWeight: 700, marginBottom: '6px', color: '#334155' }}>
                Transaction Hash *
              </label>
              <input
                type="text"
                value={transactionHash}
                onChange={(e) => setTransactionHash(e.target.value)}
                placeholder="Enter BEP-20 transaction hash (e.g. 0x...)"
                className="biz-search-input"
                style={{ width: '100%', padding: '10px 12px', borderRadius: '10px', fontFamily: 'monospace' }}
              />
              {errors.transactionHash && (
                <span style={{ color: '#ef4444', fontSize: '12px', marginTop: '4px', display: 'block' }}>
                  {errors.transactionHash}
                </span>
              )}
              <span style={{ fontSize: '11px', color: '#64748b', marginTop: '4px', display: 'block' }}>
                Paste the transaction hash from your wallet after completing the USDT (BEP-20) payment.
              </span>
            </div>

            {/* Submit Button */}
            <button
              type="submit"
              disabled={isSubmitting}
              style={{
                width: '100%',
                padding: '12px',
                borderRadius: '10px',
                backgroundColor: '#4f7df3',
                color: '#ffffff',
                fontWeight: 700,
                fontSize: '14px',
                border: 'none',
                cursor: isSubmitting ? 'not-allowed' : 'pointer',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '8px',
                boxShadow: '0 4px 12px rgba(79, 125, 243, 0.25)',
                opacity: isSubmitting ? 0.7 : 1,
              }}
            >
              {isSubmitting ? (
                <>
                  <RefreshCw size={16} className="animate-spin" />
                  <span>Submitting Deposit...</span>
                </>
              ) : (
                <>
                  <span>Submit USDT Deposit Request</span>
                  <ArrowRight size={16} />
                </>
              )}
            </button>
          </form>
        </div>
      </div>

      {/* Bottom Section: Deposit History Table (Only on Main Page) */}
      {showHistory && (
        <div className="card" style={{ padding: '24px', borderRadius: '16px' }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '16px', flexWrap: 'wrap', gap: '12px' }}>
            <div>
              <h4 style={{ fontSize: '15px', fontWeight: 800, color: 'var(--color-text-main, #1e293b)', margin: 0 }}>
                Deposit History & Status
              </h4>
              <p style={{ fontSize: '12px', color: '#64748b', margin: '2px 0 0 0' }}>
                Track the status of all your submitted USDT (BEP-20) deposit requests.
              </p>
            </div>

            <button
              type="button"
              onClick={fetchHistory}
              disabled={isLoadingHistory}
              title="Refresh history"
              style={{
                display: 'inline-flex',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '6px',
                padding: '7px 14px',
                borderRadius: '8px',
                border: '1px solid #cbd5e1',
                backgroundColor: '#ffffff',
                color: '#334155',
                fontWeight: 600,
                fontSize: '12.5px',
                cursor: isLoadingHistory ? 'not-allowed' : 'pointer',
                whiteSpace: 'nowrap',
                boxShadow: '0 1px 2px rgba(0, 0, 0, 0.05)',
                transition: 'all 0.15s ease',
                flexShrink: 0,
                opacity: isLoadingHistory ? 0.7 : 1,
              }}
            >
              <RefreshCw size={13.5} className={isLoadingHistory ? 'animate-spin' : ''} style={{ flexShrink: 0 }} />
              <span>{isLoadingHistory ? 'Refreshing...' : 'Refresh'}</span>
            </button>
          </div>

          {isLoadingHistory ? (
            <div style={{ textAlign: 'center', padding: '30px', color: '#64748b', fontSize: '13px' }}>
              Loading deposit history...
            </div>
          ) : deposits.length === 0 ? (
            <div style={{ textAlign: 'center', padding: '36px 16px', color: '#64748b', backgroundColor: '#f8fafc', borderRadius: '12px' }}>
              <Wallet size={32} color="#cbd5e1" style={{ margin: '0 auto 8px auto' }} />
              <p style={{ fontWeight: 600, margin: '0 0 2px 0', color: '#334155', fontSize: '13.5px' }}>
                No deposit requests submitted yet
              </p>
              <p style={{ margin: 0, fontSize: '12px' }}>
                When you submit a USDT (BEP-20) deposit request above, its status will appear here.
              </p>
            </div>
          ) : (
            <div style={{ overflowX: 'auto' }}>
              <table style={{ width: '100%', textAlign: 'left', borderCollapse: 'collapse', fontSize: '13px' }}>
                <thead>
                  <tr style={{ borderBottom: '1px solid #e2e8f0', color: '#64748b', fontSize: '11.5px', textTransform: 'uppercase' }}>
                    <th style={{ padding: '10px 12px' }}>Deposit ID</th>
                    <th style={{ padding: '10px 12px', textAlign: 'right' }}>Amount (USDT)</th>
                    <th style={{ padding: '10px 12px' }}>Transaction Hash</th>
                    <th style={{ padding: '10px 12px', textAlign: 'center' }}>Status</th>
                    <th style={{ padding: '10px 12px', textAlign: 'right' }}>Date</th>
                  </tr>
                </thead>
                <tbody>
                  {deposits.map((dep) => {
                    const usdtVal = Number(dep.amount_usdt !== undefined ? dep.amount_usdt : (dep.submitted_amount || dep.expected_usd_amount || dep.amount_inr || 0));
                    const txHash = dep.transaction_hash || dep.transaction_reference || '';

                    return (
                      <tr key={dep.id} style={{ borderBottom: '1px solid #f1f5f9' }}>
                        <td style={{ padding: '12px', fontFamily: 'monospace', fontWeight: 700, color: '#1e293b' }}>
                          {dep.deposit_id || `DEP-${dep.id}`}
                        </td>
                        <td style={{ padding: '12px', textAlign: 'right', fontWeight: 700, color: '#334155' }}>
                          {usdtVal.toFixed(2)} USDT
                        </td>
                        <td style={{ padding: '12px', fontFamily: 'monospace', fontSize: '12px', color: '#334155' }}>
                          <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                            <span style={{ maxWidth: '160px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }} title={txHash}>
                              {txHash || 'N/A'}
                            </span>
                            {txHash && (
                              <a
                                href={`https://bscscan.com/tx/${txHash}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                style={{ color: '#6366f1', display: 'inline-flex', alignItems: 'center' }}
                                title="View on BscScan"
                              >
                                <ExternalLink size={12} />
                              </a>
                            )}
                          </div>
                        </td>
                        <td style={{ padding: '12px', textAlign: 'center' }}>
                          <span
                            style={{
                              padding: '4px 10px',
                              borderRadius: '999px',
                              fontSize: '11px',
                              fontWeight: 700,
                              backgroundColor:
                                dep.status === 'approved'
                                  ? '#dcfce7'
                                  : dep.status === 'rejected'
                                  ? '#fee2e2'
                                  : '#fef3c7',
                              color:
                                dep.status === 'approved'
                                  ? '#15803d'
                                  : dep.status === 'rejected'
                                  ? '#b91c1c'
                                  : '#b45309',
                            }}
                          >
                            {dep.status === 'approved'
                              ? 'Approved'
                              : dep.status === 'rejected'
                              ? 'Rejected'
                              : 'Pending Verification'}
                          </span>
                        </td>
                        <td style={{ padding: '12px', textAlign: 'right', color: '#64748b', fontSize: '12px' }}>
                          {dep.submitted_at ? new Date(dep.submitted_at).toLocaleDateString() : 'N/A'}
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

export default AddFundView;
