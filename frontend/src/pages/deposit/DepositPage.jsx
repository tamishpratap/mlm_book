import { useState, useEffect, useCallback } from 'react';
import {
  Wallet,
  ArrowRight,
  CheckCircle2,
  AlertCircle,
  Clock,
  XCircle,
  ExternalLink,
  Copy,
  Check,
  ShieldCheck,
  RefreshCw,
  Coins,
  Send,
  HelpCircle,
  Sparkles,
  QrCode,
  TrendingUp,
  Percent,
} from 'lucide-react';
import depositApi from '../../api/depositApi';
import useAuth from '../../hooks/useAuth';
import {
  hasEthereumProvider,
  connectWallet,
  getConnectedAccount,
  getCurrentChainId,
  switchToBsc,
  sendBscUsdtTransfer,
  waitForReceipt,
} from '../../utils/web3Wallet';

const PRESET_AMOUNTS = [10, 25, 50, 100, 250, 500];

export function DepositPage() {
  const { user } = useAuth();

  // Active tab: 'dapp' | 'manual'
  const [activeTab, setActiveTab] = useState('dapp');

  // Configuration
  const [config, setConfig] = useState(null);
  const [isLoadingConfig, setIsLoadingConfig] = useState(true);

  // Web3 Wallet state
  const [walletAddress, setWalletAddress] = useState('');
  const [chainId, setChainId] = useState(null);
  const [isConnectingWallet, setIsConnectingWallet] = useState(false);
  const [walletError, setWalletError] = useState('');

  // DApp Deposit state
  const [dappAmount, setDappAmount] = useState('50');
  const [dappStep, setDappStep] = useState('idle'); // 'idle' | 'signing' | 'confirming' | 'verifying' | 'success' | 'error'
  const [dappError, setDappError] = useState('');
  const [dappSuccessData, setDappSuccessData] = useState(null);

  // Manual Deposit Request state
  const [manualAmount, setManualAmount] = useState('50');
  const [manualTxHash, setManualTxHash] = useState('');
  const [isVerifyingManual, setIsVerifyingManual] = useState(false);
  const [manualVerificationResult, setManualVerificationResult] = useState(null);
  const [manualVerificationError, setManualVerificationError] = useState('');
  const [isSubmittingManual, setIsSubmittingManual] = useState(false);
  const [manualSubmitSuccess, setManualSubmitSuccess] = useState(null);
  const [manualSubmitError, setManualSubmitError] = useState('');

  // History state
  const [history, setHistory] = useState([]);
  const [historyPagination, setHistoryPagination] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [isLoadingHistory, setIsLoadingHistory] = useState(true);
  const [historyPage, setHistoryPage] = useState(1);

  // Copy feedback & QR state
  const [copiedHash, setCopiedHash] = useState(null);
  const [qrImgFailed, setQrImgFailed] = useState(false);

  // Aggregated Deposit & Fund Wallet Statistics
  const [depositStats, setDepositStats] = useState({
    fund_wallet: Number(user?.p2p_wallet ?? user?.fund_wallet ?? 0),
    todays_deposit: 0,
    total_deposit: 0,
    pending_deposit: 0,
  });

  // 1. Fetch Config
  const loadConfig = useCallback(async () => {
    setIsLoadingConfig(true);
    try {
      const res = await depositApi.getConfig();
      if (res && res.success) {
        setConfig(res.config);
        setQrImgFailed(false);
        setDepositStats({
          fund_wallet: Number(res.config.member_fund_wallet ?? res.config.stats?.fund_wallet ?? user?.p2p_wallet ?? user?.fund_wallet ?? 0),
          todays_deposit: Number(res.config.todays_deposit ?? res.config.stats?.todays_deposit ?? 0),
          total_deposit: Number(res.config.total_deposit ?? res.config.stats?.total_deposit ?? 0),
          pending_deposit: Number(res.config.pending_deposit ?? res.config.stats?.pending_deposit ?? 0),
        });
      }
    } catch (err) {
      console.error('Failed to load deposit config:', err);
    } finally {
      setIsLoadingConfig(false);
    }
  }, [user]);

  // 2. Fetch History
  const loadHistory = useCallback(async (page = 1) => {
    setIsLoadingHistory(true);
    try {
      const res = await depositApi.getHistory({ page });
      if (res && res.success) {
        setHistory(res.deposits?.data || []);
        setHistoryPagination({
          current_page: res.deposits?.current_page || 1,
          last_page: res.deposits?.last_page || 1,
          total: res.deposits?.total || 0,
        });
        if (res.member_fund_wallet !== undefined || res.stats) {
          setDepositStats((prev) => ({
            ...prev,
            fund_wallet: Number(res.member_fund_wallet ?? res.stats?.fund_wallet ?? prev.fund_wallet),
            todays_deposit: Number(res.todays_deposit ?? res.stats?.todays_deposit ?? prev.todays_deposit),
            total_deposit: Number(res.total_deposit ?? res.stats?.total_deposit ?? prev.total_deposit),
            pending_deposit: Number(res.pending_deposit ?? res.stats?.pending_deposit ?? prev.pending_deposit),
          }));
        }
      }
    } catch (err) {
      console.error('Failed to load deposit history:', err);
    } finally {
      setIsLoadingHistory(false);
    }
  }, []);

  // 3. Check existing wallet connection on mount
  useEffect(() => {
    loadConfig();
    loadHistory(1);

    if (hasEthereumProvider()) {
      getConnectedAccount().then((acc) => {
        if (acc) setWalletAddress(acc);
      });
      getCurrentChainId().then((cId) => {
        if (cId) setChainId(cId);
      });

      const handleAccountsChanged = (accounts) => {
        setWalletAddress(accounts && accounts.length > 0 ? accounts[0].toLowerCase() : '');
      };

      const handleChainChanged = (newChainId) => {
        setChainId(parseInt(newChainId, 16));
      };

      window.ethereum?.on?.('accountsChanged', handleAccountsChanged);
      window.ethereum?.on?.('chainChanged', handleChainChanged);

      return () => {
        window.ethereum?.removeListener?.('accountsChanged', handleAccountsChanged);
        window.ethereum?.removeListener?.('chainChanged', handleChainChanged);
      };
    }
  }, [loadConfig, loadHistory]);

  // Connect Wallet Handler
  const handleConnectWallet = async () => {
    setIsConnectingWallet(true);
    setWalletError('');
    try {
      const acc = await connectWallet();
      setWalletAddress(acc);

      // Check network
      const isTestnet = config?.network_name?.includes('Testnet');
      const expectedChainId = isTestnet ? 97 : 56;
      const cId = await getCurrentChainId();
      setChainId(cId);

      if (cId !== expectedChainId) {
        await switchToBsc(isTestnet);
        const updatedChainId = await getCurrentChainId();
        setChainId(updatedChainId);
      }
    } catch (err) {
      setWalletError(err.message || 'Failed to connect wallet.');
    } finally {
      setIsConnectingWallet(false);
    }
  };

  // --------------------------------------------------------------------------
  // SECTION 1: DApp Deposit Action
  // --------------------------------------------------------------------------
  const handleDappDeposit = async (e) => {
    e?.preventDefault();
    setDappError('');
    setDappSuccessData(null);

    const baseAmount = parseFloat(dappAmount);
    if (isNaN(baseAmount) || baseAmount < 10) {
      setDappError('Minimum deposit amount is $10.00 USD equivalent.');
      return;
    }

    if (!hasEthereumProvider()) {
      setDappError('Please install MetaMask or another Web3 EVM wallet browser extension.');
      return;
    }

    if (!walletAddress) {
      try {
        await handleConnectWallet();
      } catch (err) {
        setDappError(err.message || 'Wallet connection is required to proceed.');
        return;
      }
    }

    const recipientAddress = config?.crypto_wallet_address;
    if (!recipientAddress) {
      setDappError('Deposit destination wallet is not configured. Please contact support.');
      return;
    }

    const isTestnet = config?.network_name?.includes('Testnet');
    const tokenContract = config?.usdt_contract;

    // Service charge added on top: Total Payable = Base * (1 + scPercent / 100)
    // Formula: Net Credit = Total Received / (1 + scPercent / 100)
    const scPercent = Number(config?.deposit_fee_percent ?? config?.service_charge_percent ?? 0);
    const totalToPay = scPercent > 0 ? Number((baseAmount * (1 + scPercent / 100)).toFixed(2)) : baseAmount;
    const feeAmount = scPercent > 0 ? Number((totalToPay - baseAmount).toFixed(2)) : 0;

    try {
      setDappStep('signing');

      // 1. Submit on-chain via Web3 wallet (Transfers totalToPay USDT including on-top fee)
      const txResult = await sendBscUsdtTransfer({
        recipientAddress,
        amountUsdt: totalToPay,
        tokenContractAddress: tokenContract,
        isTestnet,
      });

      const txHash = txResult.txHash;
      setDappStep('confirming');

      // 2. Poll for receipt (optional wait for fast block confirmation)
      await waitForReceipt(txHash, 10, 2000);

      setDappStep('verifying');

      // 3. Submit to backend for on-chain verification and database storage
      // Backend verifies totalToPay was transferred and credits Net Amount = totalToPay / (1 + scPercent/100)
      const serverRes = await depositApi.submitDappDeposit({
        amount: totalToPay,
        transaction_hash: txHash,
        wallet_address: txResult.fromAddress,
      });

      if (serverRes && serverRes.success) {
        setDappStep('success');
        setDappSuccessData({
          amount: totalToPay,
          baseAmount,
          feeAmount,
          feePercent: scPercent,
          netCredit: baseAmount,
          txHash,
          status: serverRes.deposit?.status_label || 'Verified — Awaiting Admin Approval',
          explorerUrl: serverRes.deposit?.explorer_url || (config?.explorer_url ? config.explorer_url + txHash : null),
        });
        loadHistory(1);
      } else {
        throw new Error(serverRes.message || 'Verification on backend failed.');
      }
    } catch (err) {
      setDappStep('error');
      setDappError(err.response?.data?.message || err.message || 'Deposit transaction failed.');
    }
  };

  // Local Testing Mode: Generate valid 66-character hex test hash (0x7e57...)
  const generateTestTxHash = () => {
    const chars = '0123456789abcdef';
    let randomHex = '';
    for (let i = 0; i < 60; i++) {
      randomHex += chars[Math.floor(Math.random() * chars.length)];
    }
    return `0x7e57${randomHex}`;
  };

  // Local Testing Mode: Simulate DApp deposit without gas fees or real tokens
  const handleSimulateDappDeposit = async () => {
    setDappError('');
    const baseAmount = parseFloat(dappAmount);
    if (isNaN(baseAmount) || baseAmount < 10) {
      setDappError('Minimum deposit amount is $10.00 USD equivalent.');
      return;
    }

    const scPercent = Number(config?.deposit_fee_percent ?? config?.service_charge_percent ?? 0);
    const totalToPay = scPercent > 0 ? Number((baseAmount * (1 + scPercent / 100)).toFixed(2)) : baseAmount;
    const feeAmount = scPercent > 0 ? Number((totalToPay - baseAmount).toFixed(2)) : 0;

    const testTxHash = generateTestTxHash();
    const testWallet = walletAddress || '0x7e57333333333333333333333333333333333333';

    try {
      setDappStep('signing');
      await new Promise(r => setTimeout(r, 600));
      setDappStep('confirming');
      await new Promise(r => setTimeout(r, 600));
      setDappStep('verifying');

      const serverRes = await depositApi.submitDappDeposit({
        amount: totalToPay,
        transaction_hash: testTxHash,
        wallet_address: testWallet,
      });

      if (serverRes && serverRes.success) {
        setDappStep('success');
        setDappSuccessData({
          amount: totalToPay,
          baseAmount,
          feeAmount,
          feePercent: scPercent,
          netCredit: baseAmount,
          txHash: testTxHash,
          status: serverRes.deposit?.status_label || 'Verified — Awaiting Admin Approval',
          explorerUrl: serverRes.deposit?.explorer_url || (config?.explorer_url ? config.explorer_url + testTxHash : null),
        });
        loadHistory(1);
      } else {
        throw new Error(serverRes.message || 'Verification on backend failed.');
      }
    } catch (err) {
      setDappStep('error');
      setDappError(err.response?.data?.message || err.message || 'Simulation failed.');
    }
  };

  // --------------------------------------------------------------------------
  // SECTION 2: Manual Deposit Verification & Submission
  // --------------------------------------------------------------------------
  const handleVerifyManual = async (e) => {
    e?.preventDefault();
    setManualVerificationError('');
    setManualVerificationResult(null);
    setManualSubmitSuccess(null);
    setManualSubmitError('');

    const amount = parseFloat(manualAmount);
    if (isNaN(amount) || amount < 10) {
      setManualVerificationError('Minimum deposit amount is $10.00 USD equivalent.');
      return;
    }

    const cleanHash = manualTxHash.trim();
    if (!cleanHash) {
      setManualVerificationError('Please enter the 66-character Transaction Hash (0x...).');
      return;
    }

    if (!/^0x[a-fA-F0-9]{64}$/.test(cleanHash)) {
      setManualVerificationError('Invalid hash format. Must be a 66-character hex string starting with 0x.');
      return;
    }

    setIsVerifyingManual(true);
    try {
      const res = await depositApi.verifyManualDeposit({
        amount,
        transaction_hash: cleanHash,
        wallet_address: walletAddress || undefined,
      });

      if (res && res.success && res.verified) {
        setManualVerificationResult(res.data);
      } else {
        setManualVerificationError(res.message || 'Transaction verification failed.');
      }
    } catch (err) {
      const msg = err.response?.data?.message || err.message || 'Verification failed. Please check the hash and amount.';
      setManualVerificationError(msg);
    } finally {
      setIsVerifyingManual(false);
    }
  };

  const handleSubmitManualRequest = async () => {
    if (!manualVerificationResult) return;

    setIsSubmittingManual(true);
    setManualSubmitError('');
    setManualSubmitSuccess(null);

    try {
      const res = await depositApi.submitManualDepositRequest({
        amount: manualVerificationResult.amount,
        transaction_hash: manualVerificationResult.transaction_hash,
        wallet_address: manualVerificationResult.wallet_address,
      });

      if (res && res.success) {
        setManualSubmitSuccess(res.message || 'Deposit request submitted to Admin successfully.');
        setManualVerificationResult(null);
        setManualTxHash('');
        loadHistory(1);
      } else {
        setManualSubmitError(res.message || 'Failed to submit request to admin.');
      }
    } catch (err) {
      setManualSubmitError(err.response?.data?.message || err.message || 'Failed to submit request.');
    } finally {
      setIsSubmittingManual(false);
    }
  };

  const copyToClipboard = (text) => {
    if (!text || !navigator.clipboard) return;
    navigator.clipboard.writeText(text);
    setCopiedHash(text);
    setTimeout(() => setCopiedHash(null), 2500);
  };

  const formatAddress = (addr) => {
    if (!addr) return '';
    return `${addr.substring(0, 6)}...${addr.substring(addr.length - 4)}`;
  };

  const isBscNetwork = chainId === 56 || chainId === 97;

  // Resolve Admin Uploaded QR Code Image URL (with fallback to dynamic address QR)
  const qrImageUrl = config?.qr_code_url || config?.qr_url || (config?.qr_code_image ? (config.qr_code_image.startsWith('http') ? config.qr_code_image : `/${config.qr_code_image.replace(/^\/+/, '')}`) : null);

  const fallbackQrUrl = config?.crypto_wallet_address
    ? `https://api.qrserver.com/v1/create-qr-code/?data=${encodeURIComponent(config.crypto_wallet_address)}&size=240x240`
    : '';

  const activeQrSrc = (!qrImgFailed && qrImageUrl) ? qrImageUrl : fallbackQrUrl;

  return (
    <main className="page-content member-deposit-page" style={{ padding: '1.5rem', maxWidth: '1100px', margin: '0 auto' }}>
      {/* Header Banner */}
      <header className="deposit-page-header" style={{ marginBottom: '2rem' }}>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '1rem' }}>
          <div>
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem', marginBottom: '0.25rem' }}>
              <div style={{ width: '40px', height: '40px', borderRadius: '12px', background: 'linear-gradient(135deg, #176bff, #7146ed)', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#fff' }}>
                <Wallet size={22} />
              </div>
              <h1 style={{ fontSize: '1.75rem', fontWeight: 800, color: '#111827', margin: 0 }}>
                Fund Wallet
              </h1>
            </div>
            <p style={{ color: '#6b7280', margin: 0, fontSize: '0.95rem' }}>
              Deposit funds via direct DApp Web3 wallet or submit an existing blockchain transaction for manual verification. Approved amounts are credited directly to your Fund Wallet.
            </p>
          </div>

        </div>
      </header>

      {/* Deposit & Wallet Stats Overview Cards (Visible across both tabs) */}
      <section style={{
        display: 'grid',
        gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))',
        gap: '1rem',
        marginBottom: '1.75rem'
      }}>
        {/* Card 1: Fund Wallet Balance */}
        <div style={{
          background: 'linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%)',
          border: '1px solid #bbf7d0',
          borderRadius: '16px',
          padding: '1.25rem',
          boxShadow: '0 2px 6px rgba(5, 150, 105, 0.05)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between'
        }}>
          <div>
            <span style={{ fontSize: '0.74rem', textTransform: 'uppercase', letterSpacing: '0.05em', color: '#047857', fontWeight: 700 }}>
              Fund Wallet Balance
            </span>
            <div style={{ fontSize: '1.5rem', fontWeight: 800, color: '#065f46', marginTop: '0.25rem' }}>
              ${depositStats.fund_wallet.toFixed(2)} <span style={{ fontSize: '0.8rem', fontWeight: 600 }}>USD</span>
            </div>
            <span style={{ fontSize: '0.74rem', color: '#059669', display: 'block', marginTop: '0.2rem' }}>
              Available in p2p_wallet
            </span>
          </div>
          <div style={{ width: '44px', height: '44px', borderRadius: '12px', background: '#dcfce7', color: '#059669', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
            <Wallet size={22} />
          </div>
        </div>

        {/* Card 2: Today's Deposit */}
        <div style={{
          background: 'linear-gradient(135deg, #ffffff 0%, #eff6ff 100%)',
          border: '1px solid #bfdbfe',
          borderRadius: '16px',
          padding: '1.25rem',
          boxShadow: '0 2px 6px rgba(23, 107, 255, 0.05)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between'
        }}>
          <div>
            <span style={{ fontSize: '0.74rem', textTransform: 'uppercase', letterSpacing: '0.05em', color: '#1d4ed8', fontWeight: 700 }}>
              Today's Deposit
            </span>
            <div style={{ fontSize: '1.5rem', fontWeight: 800, color: '#1e40af', marginTop: '0.25rem' }}>
              ${depositStats.todays_deposit.toFixed(2)} <span style={{ fontSize: '0.8rem', fontWeight: 600 }}>USDT</span>
            </div>
            <span style={{ fontSize: '0.74rem', color: '#2563eb', display: 'block', marginTop: '0.2rem' }}>
              Credited today
            </span>
          </div>
          <div style={{ width: '44px', height: '44px', borderRadius: '12px', background: '#dbeafe', color: '#1d4ed8', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
            <TrendingUp size={22} />
          </div>
        </div>

        {/* Card 3: Total Deposit */}
        <div style={{
          background: 'linear-gradient(135deg, #ffffff 0%, #faf5ff 100%)',
          border: '1px solid #e9d5ff',
          borderRadius: '16px',
          padding: '1.25rem',
          boxShadow: '0 2px 6px rgba(126, 34, 206, 0.05)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between'
        }}>
          <div>
            <span style={{ fontSize: '0.74rem', textTransform: 'uppercase', letterSpacing: '0.05em', color: '#7e22ce', fontWeight: 700 }}>
              Total Deposit
            </span>
            <div style={{ fontSize: '1.5rem', fontWeight: 800, color: '#6b21a8', marginTop: '0.25rem' }}>
              ${depositStats.total_deposit.toFixed(2)} <span style={{ fontSize: '0.8rem', fontWeight: 600 }}>USDT</span>
            </div>
            <span style={{ fontSize: '0.74rem', color: '#9333ea', display: 'block', marginTop: '0.2rem' }}>
              Lifetime approved
            </span>
          </div>
          <div style={{ width: '44px', height: '44px', borderRadius: '12px', background: '#f3e8ff', color: '#7e22ce', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
            <Coins size={22} />
          </div>
        </div>

        {/* Card 4: Pending Approval */}
        <div style={{
          background: 'linear-gradient(135deg, #ffffff 0%, #fffbeb 100%)',
          border: '1px solid #fde68a',
          borderRadius: '16px',
          padding: '1.25rem',
          boxShadow: '0 2px 6px rgba(217, 119, 6, 0.05)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between'
        }}>
          <div>
            <span style={{ fontSize: '0.74rem', textTransform: 'uppercase', letterSpacing: '0.05em', color: '#b45309', fontWeight: 700 }}>
              Pending Approval
            </span>
            <div style={{ fontSize: '1.5rem', fontWeight: 800, color: '#92400e', marginTop: '0.25rem' }}>
              ${depositStats.pending_deposit.toFixed(2)} <span style={{ fontSize: '0.8rem', fontWeight: 600 }}>USDT</span>
            </div>
            <span style={{ fontSize: '0.74rem', color: '#d97706', display: 'block', marginTop: '0.2rem' }}>
              Awaiting admin review
            </span>
          </div>
          <div style={{ width: '44px', height: '44px', borderRadius: '12px', background: '#fef3c7', color: '#b45309', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
            <Clock size={22} />
          </div>
        </div>
      </section>

      {/* Local Server Testing Mode Banner (Only appears in local dev environment) */}
      {config?.is_test_mode && (
        <div style={{
          background: 'linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%)',
          border: '1px solid #fcd34d',
          borderRadius: '16px',
          padding: '1rem 1.25rem',
          marginBottom: '1.5rem',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
          flexWrap: 'wrap',
          gap: '1rem',
          boxShadow: '0 2px 5px rgba(245, 158, 11, 0.08)'
        }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '0.85rem' }}>
            <div style={{ background: '#f59e0b', color: '#fff', padding: '0.5rem', borderRadius: '12px', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              <Sparkles size={22} />
            </div>
            <div>
              <div style={{ fontWeight: 800, color: '#92400e', fontSize: '0.96rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                <span>🧪 Local Server Testing Mode Active</span>
                <span style={{ background: '#fef3c7', border: '1px solid #f59e0b', color: '#b45309', fontSize: '0.7rem', padding: '0.15rem 0.45rem', borderRadius: '6px', fontWeight: 700 }}>
                  APP_ENV=local
                </span>
              </div>
              <div style={{ color: '#b45309', fontSize: '0.84rem', marginTop: '0.2rem' }}>
                You can test DApp deposits and manual verification without spending real USDT or gas fees. When deployed to the online production server, real BSC blockchain transactions are automatically required.
              </div>
            </div>
          </div>
          <div style={{ background: '#fff', border: '1px solid #fbbf24', padding: '0.45rem 0.85rem', borderRadius: '10px', fontSize: '0.8rem', fontWeight: 700, color: '#b45309' }}>
            Chain: BSC (Simulated &amp; Testnet Ready)
          </div>
        </div>
      )}

      {/* Tabs Selector */}
      <div style={{ display: 'flex', gap: '0.5rem', background: '#f3f4f6', padding: '0.35rem', borderRadius: '14px', marginBottom: '1.5rem', width: 'fit-content' }}>
        <button
          type="button"
          onClick={() => { setActiveTab('dapp'); setDappError(''); }}
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: '0.5rem',
            padding: '0.65rem 1.25rem',
            borderRadius: '10px',
            border: 'none',
            cursor: 'pointer',
            fontWeight: 600,
            fontSize: '0.92rem',
            transition: 'all 0.15s ease',
            background: activeTab === 'dapp' ? '#fff' : 'transparent',
            color: activeTab === 'dapp' ? '#176bff' : '#4b5563',
            boxShadow: activeTab === 'dapp' ? '0 1px 3px rgba(0,0,0,0.1)' : 'none',
          }}
        >
          <Sparkles size={16} />
          <span>Section 1 — DApp Deposit</span>
        </button>

        <button
          type="button"
          onClick={() => { setActiveTab('manual'); setManualVerificationError(''); }}
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: '0.5rem',
            padding: '0.65rem 1.25rem',
            borderRadius: '10px',
            border: 'none',
            cursor: 'pointer',
            fontWeight: 600,
            fontSize: '0.92rem',
            transition: 'all 0.15s ease',
            background: activeTab === 'manual' ? '#fff' : 'transparent',
            color: activeTab === 'manual' ? '#176bff' : '#4b5563',
            boxShadow: activeTab === 'manual' ? '0 1px 3px rgba(0,0,0,0.1)' : 'none',
          }}
        >
          <ShieldCheck size={16} />
          <span>Section 2 — Manual Verification Request</span>
        </button>
      </div>

      {/* SECTION 1: DAPP DEPOSIT */}
      {activeTab === 'dapp' && (
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(320px, 1fr))', gap: '1.5rem', marginBottom: '2.5rem' }}>
          {/* Main Deposit Form Card */}
          <div style={{ background: '#fff', border: '1px solid #e5e7eb', borderRadius: '20px', padding: '1.75rem', boxShadow: '0 4px 6px -1px rgba(0,0,0,0.05)' }}>
            <h2 style={{ fontSize: '1.25rem', fontWeight: 700, color: '#111827', margin: '0 0 0.5rem 0' }}>
              Web3 DApp Instant Deposit
            </h2>
            <p style={{ color: '#6b7280', fontSize: '0.88rem', margin: '0 0 1.25rem 0' }}>
              Connect your Web3 wallet (MetaMask, Trust Wallet, etc.) and complete the on-chain BEP-20 transfer.
            </p>

            {/* Fund Wallet & Deposit Quick Stats Banner inside Tab 1 */}
            <div style={{
              background: 'linear-gradient(135deg, #f8fafc 0%, #f0fdf4 100%)',
              border: '1px solid #bbf7d0',
              borderRadius: '14px',
              padding: '0.85rem 1.15rem',
              marginBottom: '1.25rem',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              flexWrap: 'wrap',
              gap: '0.85rem'
            }}>
              <div>
                <div style={{ fontSize: '0.72rem', textTransform: 'uppercase', letterSpacing: '0.04em', color: '#047857', fontWeight: 700 }}>
                  Your Fund Wallet Balance
                </div>
                <div style={{ fontSize: '1.3rem', fontWeight: 800, color: '#065f46', display: 'flex', alignItems: 'center', gap: '0.35rem' }}>
                  <span>${depositStats.fund_wallet.toFixed(2)}</span>
                  <span style={{ fontSize: '0.8rem', color: '#059669', fontWeight: 600 }}>USD</span>
                </div>
              </div>

              <div style={{ display: 'flex', gap: '1.25rem', flexWrap: 'wrap' }}>
                <div>
                  <div style={{ fontSize: '0.7rem', color: '#64748b', fontWeight: 600 }}>Today's Deposit</div>
                  <div style={{ fontSize: '0.95rem', fontWeight: 700, color: '#1d4ed8' }}>
                    ${depositStats.todays_deposit.toFixed(2)} <span style={{ fontSize: '0.72rem' }}>USDT</span>
                  </div>
                </div>
                <div>
                  <div style={{ fontSize: '0.7rem', color: '#64748b', fontWeight: 600 }}>Total Deposit</div>
                  <div style={{ fontSize: '0.95rem', fontWeight: 700, color: '#7e22ce' }}>
                    ${depositStats.total_deposit.toFixed(2)} <span style={{ fontSize: '0.72rem' }}>USDT</span>
                  </div>
                </div>
              </div>
            </div>

            {/* Wallet Status Box */}
            <div style={{ background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: '14px', padding: '1rem', marginBottom: '1.5rem' }}>
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '0.75rem' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '0.65rem' }}>
                  <span style={{ width: '10px', height: '10px', borderRadius: '50%', background: walletAddress ? '#10b981' : '#f59e0b', display: 'inline-block' }} />
                  <span style={{ fontSize: '0.88rem', fontWeight: 600, color: '#334155' }}>
                    {walletAddress ? `Connected: ${formatAddress(walletAddress)}` : 'Wallet Not Connected'}
                  </span>
                </div>

                {!walletAddress ? (
                  <button
                    type="button"
                    onClick={handleConnectWallet}
                    disabled={isConnectingWallet}
                    style={{
                      background: '#176bff',
                      color: '#fff',
                      border: 'none',
                      padding: '0.5rem 1rem',
                      borderRadius: '8px',
                      fontSize: '0.84rem',
                      fontWeight: 600,
                      cursor: 'pointer',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '0.4rem',
                    }}
                  >
                    {isConnectingWallet ? <RefreshCw size={14} className="animate-spin" /> : <Wallet size={14} />}
                    <span>Connect Wallet</span>
                  </button>
                ) : (
                  <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                    <span style={{ fontSize: '0.75rem', fontWeight: 600, background: isBscNetwork ? '#dcfce7' : '#fee2e2', color: isBscNetwork ? '#15803d' : '#b91c1c', padding: '0.25rem 0.6rem', borderRadius: '6px' }}>
                      {config?.network_name || 'BNB Smart Chain'}
                    </span>
                  </div>
                )}
              </div>
              {walletError && <p style={{ color: '#dc2626', fontSize: '0.8rem', margin: '0.5rem 0 0 0' }}>{walletError}</p>}
            </div>

            {/* Deposit Amount Input */}
            <form onSubmit={handleDappDeposit}>
              <div style={{ marginBottom: '1.25rem' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.4rem' }}>
                  <label htmlFor="dapp-amount" style={{ fontSize: '0.88rem', fontWeight: 600, color: '#374151' }}>
                    Desired Deposit Amount (USD)
                  </label>
                  <span style={{ fontSize: '0.78rem', color: '#059669', fontWeight: 600 }}>
                    Minimum Credit: $10.00 USD
                  </span>
                </div>

                <div style={{ position: 'relative' }}>
                  <input
                    id="dapp-amount"
                    type="number"
                    step="1"
                    min="10"
                    max="100000"
                    value={dappAmount}
                    onChange={(e) => setDappAmount(e.target.value)}
                    placeholder="50"
                    style={{
                      width: '100%',
                      padding: '0.75rem 4rem 0.75rem 1rem',
                      borderRadius: '12px',
                      border: '1px solid #d1d5db',
                      fontSize: '1.1rem',
                      fontWeight: 700,
                      color: '#111827',
                      outline: 'none',
                      boxSizing: 'border-box',
                    }}
                  />
                  <span style={{ position: 'absolute', right: '1rem', top: '50%', transform: 'translateY(-50%)', fontWeight: 700, color: '#6b7280', fontSize: '0.9rem' }}>
                    USD
                  </span>
                </div>

                {/* Preset Chips */}
                <div style={{ display: 'flex', flexWrap: 'wrap', gap: '0.4rem', marginTop: '0.65rem' }}>
                  {PRESET_AMOUNTS.map((preset) => (
                    <button
                      key={preset}
                      type="button"
                      onClick={() => setDappAmount(String(preset))}
                      style={{
                        padding: '0.35rem 0.75rem',
                        borderRadius: '8px',
                        border: '1px solid #e5e7eb',
                        background: parseFloat(dappAmount) === preset ? '#eff6ff' : '#f9fafb',
                        color: parseFloat(dappAmount) === preset ? '#176bff' : '#4b5563',
                        fontWeight: 600,
                        fontSize: '0.82rem',
                        cursor: 'pointer',
                      }}
                    >
                      ${preset}
                    </button>
                  ))}
                </div>

                {/* Live Deposit Preview into Fund Wallet with On-Top Service Charge */}
                {(() => {
                  const scPercent = Number(config?.deposit_fee_percent ?? config?.service_charge_percent ?? 0);
                  const base = parseFloat(dappAmount) || 0;
                  const total = scPercent > 0 ? Number((base * (1 + scPercent / 100)).toFixed(2)) : base;
                  const fee = scPercent > 0 ? Number((total - base).toFixed(2)) : 0;

                  return (
                    <div style={{
                      background: scPercent > 0 ? '#f0fdf4' : '#ecfdf5',
                      border: scPercent > 0 ? '1px solid #bbf7d0' : '1px solid #a7f3d0',
                      borderRadius: '12px',
                      padding: '0.85rem 1rem',
                      fontSize: '0.82rem',
                      marginTop: '0.85rem',
                      display: 'flex',
                      flexDirection: 'column',
                      gap: '0.5rem',
                    }}>
                      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '0.4rem' }}>
                        <span style={{ color: '#374151', fontWeight: 600 }}>Credit Target: <strong>Fund Wallet</strong></span>
                        {scPercent > 0 ? (
                          <span style={{ background: '#fef3c7', color: '#92400e', padding: '2px 8px', borderRadius: '6px', fontWeight: 700, fontSize: '0.75rem' }}>
                            ⚡ Service Charge: +{scPercent}% on top (+${fee.toFixed(2)} USDT)
                          </span>
                        ) : (
                          <span style={{ background: '#d1fae5', color: '#065f46', padding: '2px 8px', borderRadius: '6px', fontWeight: 700, fontSize: '0.75rem' }}>
                            ✨ 0% Service Charge (100% Credit)
                          </span>
                        )}
                      </div>

                      {/* Calculation Breakdown Grid */}
                      <div style={{
                        display: 'grid',
                        gridTemplateColumns: 'repeat(auto-fit, minmax(130px, 1fr))',
                        gap: '0.5rem',
                        background: '#ffffff',
                        padding: '0.65rem 0.75rem',
                        borderRadius: '8px',
                        border: '1px solid #dcfce7',
                        fontSize: '0.8rem',
                      }}>
                        <div>
                          <span style={{ color: '#6b7280', display: 'block', fontSize: '0.72rem' }}>Deposit Base:</span>
                          <strong style={{ color: '#111827' }}>${base.toFixed(2)} USD</strong>
                        </div>
                        {scPercent > 0 && (
                          <div>
                            <span style={{ color: '#6b7280', display: 'block', fontSize: '0.72rem' }}>Fee ({scPercent}% on top):</span>
                            <strong style={{ color: '#d97706' }}>+${fee.toFixed(2)} USDT</strong>
                          </div>
                        )}
                        <div>
                          <span style={{ color: '#6b7280', display: 'block', fontSize: '0.72rem' }}>Total To Pay (on-chain):</span>
                          <strong style={{ color: '#176bff', fontSize: '0.92rem' }}>${total.toFixed(2)} USDT</strong>
                        </div>
                        <div>
                          <span style={{ color: '#6b7280', display: 'block', fontSize: '0.72rem' }}>Credit Formula:</span>
                          <strong style={{ color: '#059669' }}>${total.toFixed(2)} / {1 + (scPercent / 100)} = ${base.toFixed(2)} USD</strong>
                        </div>
                      </div>

                      <div style={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        flexWrap: 'wrap',
                        gap: '0.4rem',
                        paddingTop: '0.4rem',
                        borderTop: '1px dashed #d1fae5',
                        color: '#065f46',
                        fontWeight: 600,
                      }}>
                        <span>
                          Fund Wallet Credit: <strong style={{ color: '#059669' }}>+${base.toFixed(2)} USD</strong>
                        </span>
                        <span>
                          New Balance: <strong>${(depositStats.fund_wallet + base).toFixed(2)} USD</strong>
                        </span>
                      </div>
                    </div>
                  );
                })()}
              </div>

              {/* Status or Error Notifications */}
              {dappError && (
                <div style={{ background: '#fef2f2', border: '1px solid #fecaca', color: '#b91c1c', padding: '0.85rem', borderRadius: '12px', marginBottom: '1.25rem', display: 'flex', gap: '0.65rem', alignItems: 'flex-start', fontSize: '0.88rem' }}>
                  <AlertCircle size={18} style={{ flexShrink: 0, marginTop: '2px' }} />
                  <div>{dappError}</div>
                </div>
              )}

              {/* Action Button */}
              {(() => {
                const scPercent = Number(config?.deposit_fee_percent ?? config?.service_charge_percent ?? 0);
                const base = parseFloat(dappAmount) || 10;
                const total = scPercent > 0 ? (base * (1 + scPercent / 100)).toFixed(2) : base.toFixed(2);

                return (
                  <button
                    type="submit"
                    disabled={dappStep === 'signing' || dappStep === 'confirming' || dappStep === 'verifying'}
                    style={{
                      width: '100%',
                      padding: '0.85rem',
                      borderRadius: '12px',
                      border: 'none',
                      background: 'linear-gradient(135deg, #176bff, #7146ed)',
                      color: '#fff',
                      fontWeight: 700,
                      fontSize: '1rem',
                      cursor: (dappStep === 'signing' || dappStep === 'confirming' || dappStep === 'verifying') ? 'not-allowed' : 'pointer',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      gap: '0.5rem',
                      boxShadow: '0 4px 12px rgba(23, 107, 255, 0.25)',
                      transition: 'opacity 0.2s ease',
                      opacity: (dappStep === 'signing' || dappStep === 'confirming' || dappStep === 'verifying') ? 0.75 : 1,
                    }}
                  >
                    {dappStep === 'signing' && <><RefreshCw size={18} className="animate-spin" /> Confirm ${total} USDT in wallet...</>}
                    {dappStep === 'confirming' && <><RefreshCw size={18} className="animate-spin" /> Confirming on BNB Smart Chain...</>}
                    {dappStep === 'verifying' && <><RefreshCw size={18} className="animate-spin" /> Verifying on backend...</>}
                    {dappStep === 'idle' && <><Send size={18} /> Pay ${total} USDT (Net Credit: ${base.toFixed(2)} USD)</>}
                    {dappStep === 'error' && <><Send size={18} /> Retry Deposit</>}
                    {dappStep === 'success' && <><CheckCircle2 size={18} /> Deposit More</>}
                  </button>
                );
              })()}

              {/* Local Dev Test Mode Simulation Action */}
              {config?.is_test_mode && (() => {
                const scPercent = Number(config?.deposit_fee_percent ?? config?.service_charge_percent ?? 0);
                const base = parseFloat(dappAmount) || 10;
                const total = scPercent > 0 ? (base * (1 + scPercent / 100)).toFixed(2) : base.toFixed(2);

                return (
                  <div style={{ marginTop: '1.25rem', padding: '1rem', background: '#f0fdf4', border: '1px dashed #86efac', borderRadius: '12px' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.45rem', flexWrap: 'wrap', gap: '0.5rem' }}>
                      <span style={{ fontSize: '0.82rem', fontWeight: 700, color: '#166534' }}>
                        🧪 Local Testing Action (Simulation):
                      </span>
                      <span style={{ fontSize: '0.74rem', color: '#15803d', fontWeight: 500 }}>
                        No wallet connection or gas needed
                      </span>
                    </div>
                    <button
                      type="button"
                      onClick={handleSimulateDappDeposit}
                      disabled={dappStep === 'signing' || dappStep === 'confirming' || dappStep === 'verifying'}
                      style={{
                        width: '100%',
                        padding: '0.75rem',
                        borderRadius: '10px',
                        border: 'none',
                        background: '#059669',
                        color: '#fff',
                        fontWeight: 700,
                        fontSize: '0.9rem',
                        cursor: (dappStep === 'signing' || dappStep === 'confirming' || dappStep === 'verifying') ? 'not-allowed' : 'pointer',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        gap: '0.5rem',
                        boxShadow: '0 2px 5px rgba(5, 150, 105, 0.25)',
                      }}
                    >
                      <Sparkles size={16} />
                      <span>⚡ One-Click Simulate DApp Transfer (${total} USDT → ${base.toFixed(2)} USD Net)</span>
                    </button>
                  </div>
                );
              })()}
            </form>

            {/* DApp Success Card */}
            {dappSuccessData && (
              <div style={{ marginTop: '1.5rem', background: '#f0fdf4', border: '1px solid #bbf7d0', borderRadius: '14px', padding: '1.25rem' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', color: '#15803d', fontWeight: 700, marginBottom: '0.5rem' }}>
                  <CheckCircle2 size={20} />
                  <span>DApp Deposit Successfully Submitted!</span>
                </div>
                <p style={{ color: '#166534', fontSize: '0.88rem', margin: '0 0 0.75rem 0' }}>
                  Your transfer of <strong>${dappSuccessData.amount} USDT</strong> has been verified on the blockchain. 
                  {dappSuccessData.feePercent > 0 && (
                    <span> (${dappSuccessData.baseAmount?.toFixed(2) || dappSuccessData.netCredit?.toFixed(2)} USD base + ${dappSuccessData.feeAmount?.toFixed(2)} USDT {dappSuccessData.feePercent}% service charge).</span>
                  )}
                  {" "}<strong>${dappSuccessData.netCredit?.toFixed(2) || dappSuccessData.baseAmount?.toFixed(2) || dappSuccessData.amount} USD</strong> will be credited to your Fund Wallet upon approval.
                </p>
                <div style={{ fontSize: '0.82rem', background: '#fff', padding: '0.65rem', borderRadius: '8px', border: '1px solid #dcfce7' }}>
                  <div style={{ color: '#6b7280', marginBottom: '0.25rem' }}>Transaction Hash:</div>
                  <div style={{ fontFamily: 'monospace', wordBreak: 'break-all', color: '#111827', fontWeight: 600 }}>
                    {dappSuccessData.txHash}
                  </div>
                  {dappSuccessData.explorerUrl && (
                    <a
                      href={dappSuccessData.explorerUrl}
                      target="_blank"
                      rel="noopener noreferrer"
                      style={{ display: 'inline-flex', alignItems: 'center', gap: '0.3rem', color: '#176bff', marginTop: '0.5rem', fontWeight: 600, textDecoration: 'none' }}
                    >
                      <span>View on BSCScan Explorer</span>
                      <ExternalLink size={13} />
                    </a>
                  )}
                </div>
              </div>
            )}
          </div>

          {/* Info & Requirements Card */}
          <div style={{ background: '#f9fafb', border: '1px solid #e5e7eb', borderRadius: '20px', padding: '1.75rem' }}>
            <h3 style={{ fontSize: '1.1rem', fontWeight: 700, color: '#111827', margin: '0 0 1rem 0' }}>
              Important Deposit Guidelines
            </h3>
            <ul style={{ listStyle: 'none', padding: 0, margin: 0, display: 'flex', flexDirection: 'column', gap: '0.85rem' }}>
              <li style={{ display: 'flex', gap: '0.65rem', alignItems: 'flex-start', fontSize: '0.88rem', color: '#4b5563' }}>
                <div style={{ background: '#eff6ff', color: '#176bff', padding: '0.25rem', borderRadius: '6px', flexShrink: 0 }}>
                  <ShieldCheck size={16} />
                </div>
                <div>
                  <strong>Network:</strong> Exclusively <strong>BNB Smart Chain (BEP-20)</strong>. Do not send via Ethereum (ERC-20) or Tron (TRC-20).
                </div>
              </li>
              <li style={{ display: 'flex', gap: '0.65rem', alignItems: 'flex-start', fontSize: '0.88rem', color: '#4b5563' }}>
                <div style={{ background: '#ecfdf5', color: '#059669', padding: '0.25rem', borderRadius: '6px', flexShrink: 0 }}>
                  <Coins size={16} />
                </div>
                <div>
                  <strong>Minimum Amount:</strong> $10.00 USDT. Transactions lower than $10 will be rejected.
                </div>
              </li>
              <li style={{ display: 'flex', gap: '0.65rem', alignItems: 'flex-start', fontSize: '0.88rem', color: '#4b5563' }}>
                <div style={{ background: '#fef3c7', color: '#d97706', padding: '0.25rem', borderRadius: '6px', flexShrink: 0 }}>
                  <HelpCircle size={16} />
                </div>
                <div>
                  <strong>Gas Fees:</strong> You need a small amount of <strong>BNB</strong> in your wallet to cover the minimal BSC blockchain gas fees.
                </div>
              </li>
              {Number(config?.deposit_fee_percent ?? config?.service_charge_percent ?? 0) > 0 ? (
                <li style={{ display: 'flex', gap: '0.65rem', alignItems: 'flex-start', fontSize: '0.88rem', color: '#4b5563' }}>
                  <div style={{ background: '#fef3c7', color: '#d97706', padding: '0.25rem', borderRadius: '6px', flexShrink: 0 }}>
                    <Percent size={16} />
                  </div>
                  <div>
                    <strong>Service Charge ({Number(config?.deposit_fee_percent ?? config?.service_charge_percent)}% on top):</strong> Platform fee is added on top of your deposit amount. E.g. for a $100 deposit, total transfer is 100 + {Number(config?.deposit_fee_percent ?? config?.service_charge_percent)}% = ${(100 * (1 + Number(config?.deposit_fee_percent ?? config?.service_charge_percent)/100)).toFixed(2)} USDT. Net Fund Wallet Credit = Received Amount / (100 + {Number(config?.deposit_fee_percent ?? config?.service_charge_percent)}%) = $100.00 USD.
                  </div>
                </li>
              ) : (
                <li style={{ display: 'flex', gap: '0.65rem', alignItems: 'flex-start', fontSize: '0.88rem', color: '#4b5563' }}>
                  <div style={{ background: '#ecfdf5', color: '#059669', padding: '0.25rem', borderRadius: '6px', flexShrink: 0 }}>
                    <ShieldCheck size={16} />
                  </div>
                  <div>
                    <strong>0% Platform Fee:</strong> Zero service charge. 100% of deposited USDT is credited to your Fund Wallet.
                  </div>
                </li>
              )}
            </ul>

            <div style={{ marginTop: '1.5rem', padding: '1rem', background: '#fff', border: '1px solid #e5e7eb', borderRadius: '12px' }}>
              <div style={{ fontSize: '0.75rem', textTransform: 'uppercase', color: '#6b7280', fontWeight: 600, marginBottom: '0.25rem' }}>
                Official Admin Destination Wallet
              </div>
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '0.5rem' }}>
                <code style={{ fontSize: '0.82rem', color: '#111827', wordBreak: 'break-all' }}>
                  {config?.crypto_wallet_address || 'Loading...'}
                </code>
                {config?.crypto_wallet_address && (
                  <button
                    type="button"
                    onClick={() => copyToClipboard(config.crypto_wallet_address)}
                    style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#6b7280', padding: '0.25rem' }}
                    title="Copy address"
                  >
                    {copiedHash === config.crypto_wallet_address ? <Check size={16} color="#059669" /> : <Copy size={16} />}
                  </button>
                )}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* SECTION 2: MANUAL DEPOSIT REQUEST */}
      {activeTab === 'manual' && (
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(320px, 1fr))', gap: '1.5rem', marginBottom: '2.5rem' }}>
          {/* Manual Form Card */}
          <div style={{ background: '#fff', border: '1px solid #e5e7eb', borderRadius: '20px', padding: '1.75rem', boxShadow: '0 4px 6px -1px rgba(0,0,0,0.05)' }}>
            <h2 style={{ fontSize: '1.25rem', fontWeight: 700, color: '#111827', margin: '0 0 0.5rem 0' }}>
              Manual Deposit Verification Request
            </h2>
            <p style={{ color: '#6b7280', fontSize: '0.88rem', margin: '0 0 1.25rem 0' }}>
              Already made a transfer? Enter the deposit amount and your blockchain Transaction Hash to verify on-chain before submitting to the admin.
            </p>

            {/* Fund Wallet & Deposit Quick Stats Banner inside Tab 2 */}
            <div style={{
              background: 'linear-gradient(135deg, #f8fafc 0%, #f0fdf4 100%)',
              border: '1px solid #bbf7d0',
              borderRadius: '14px',
              padding: '0.85rem 1.15rem',
              marginBottom: '1.25rem',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              flexWrap: 'wrap',
              gap: '0.85rem'
            }}>
              <div>
                <div style={{ fontSize: '0.72rem', textTransform: 'uppercase', letterSpacing: '0.04em', color: '#047857', fontWeight: 700 }}>
                  Your Fund Wallet Balance
                </div>
                <div style={{ fontSize: '1.3rem', fontWeight: 800, color: '#065f46', display: 'flex', alignItems: 'center', gap: '0.35rem' }}>
                  <span>${depositStats.fund_wallet.toFixed(2)}</span>
                  <span style={{ fontSize: '0.8rem', color: '#059669', fontWeight: 600 }}>USD</span>
                </div>
              </div>

              <div style={{ display: 'flex', gap: '1.25rem', flexWrap: 'wrap' }}>
                <div>
                  <div style={{ fontSize: '0.7rem', color: '#64748b', fontWeight: 600 }}>Today's Deposit</div>
                  <div style={{ fontSize: '0.95rem', fontWeight: 700, color: '#1d4ed8' }}>
                    ${depositStats.todays_deposit.toFixed(2)} <span style={{ fontSize: '0.72rem' }}>USDT</span>
                  </div>
                </div>
                <div>
                  <div style={{ fontSize: '0.7rem', color: '#64748b', fontWeight: 600 }}>Total Deposit</div>
                  <div style={{ fontSize: '0.95rem', fontWeight: 700, color: '#7e22ce' }}>
                    ${depositStats.total_deposit.toFixed(2)} <span style={{ fontSize: '0.72rem' }}>USDT</span>
                  </div>
                </div>
              </div>
            </div>

            <form onSubmit={handleVerifyManual}>
              {/* Amount */}
              <div style={{ marginBottom: '1.25rem' }}>
                <label htmlFor="manual-amount" style={{ display: 'block', fontSize: '0.88rem', fontWeight: 600, color: '#374151', marginBottom: '0.4rem' }}>
                  Transferred Amount (USDT) <span style={{ color: '#dc2626' }}>*</span>
                </label>
                <div style={{ position: 'relative' }}>
                  <input
                    id="manual-amount"
                    type="number"
                    step="0.01"
                    min="10"
                    value={manualAmount}
                    onChange={(e) => setManualAmount(e.target.value)}
                    placeholder="50.00"
                    required
                    style={{
                      width: '100%',
                      padding: '0.75rem 4rem 0.75rem 1rem',
                      borderRadius: '12px',
                      border: '1px solid #d1d5db',
                      fontSize: '1rem',
                      fontWeight: 600,
                      boxSizing: 'border-box',
                    }}
                  />
                  <span style={{ position: 'absolute', right: '1rem', top: '50%', transform: 'translateY(-50%)', fontWeight: 700, color: '#6b7280' }}>
                    USDT
                  </span>
                </div>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: '0.35rem', flexWrap: 'wrap', gap: '0.4rem' }}>
                  <small style={{ color: '#6b7280', fontSize: '0.78rem' }}>Minimum $10.00 USD equivalent.</small>
                  {Number(config?.deposit_fee_percent ?? config?.service_charge_percent ?? 0) > 0 && (
                    <span style={{ fontSize: '0.76rem', color: '#b45309', fontWeight: 600 }}>
                      💡 Tip: Service charge is +{Number(config?.deposit_fee_percent ?? config?.service_charge_percent)}% on top. (Transfer ${(100 * (1 + Number(config?.deposit_fee_percent ?? config?.service_charge_percent)/100)).toFixed(2)} USDT for $100.00 net credit).
                    </span>
                  )}
                </div>

                {/* Live Deposit Preview into Fund Wallet with On-Top Service Charge */}
                {(() => {
                  const scPercent = Number(config?.deposit_fee_percent ?? config?.service_charge_percent ?? 0);
                  const amt = parseFloat(manualAmount) || 0;
                  const net = scPercent > 0 ? Number((amt / (1 + scPercent / 100)).toFixed(2)) : amt;
                  const fee = scPercent > 0 ? Number((amt - net).toFixed(2)) : 0;

                  return (
                    <div style={{
                      background: scPercent > 0 ? '#f0fdf4' : '#ecfdf5',
                      border: scPercent > 0 ? '1px solid #bbf7d0' : '1px solid #a7f3d0',
                      borderRadius: '12px',
                      padding: '0.85rem 1rem',
                      fontSize: '0.82rem',
                      marginTop: '0.65rem',
                      display: 'flex',
                      flexDirection: 'column',
                      gap: '0.5rem',
                    }}>
                      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '0.4rem' }}>
                        <span style={{ color: '#374151', fontWeight: 600 }}>Target: <strong>Fund Wallet</strong></span>
                        {scPercent > 0 ? (
                          <span style={{ background: '#fef3c7', color: '#92400e', padding: '2px 8px', borderRadius: '6px', fontWeight: 700, fontSize: '0.75rem' }}>
                            ⚡ Service Charge: +{scPercent}% on top (-${fee.toFixed(2)} USDT)
                          </span>
                        ) : (
                          <span style={{ background: '#d1fae5', color: '#065f46', padding: '2px 8px', borderRadius: '6px', fontWeight: 700, fontSize: '0.75rem' }}>
                            ✨ 0% Service Charge (100% Credit)
                          </span>
                        )}
                      </div>

                      {/* Calculation Breakdown Grid */}
                      <div style={{
                        display: 'grid',
                        gridTemplateColumns: 'repeat(auto-fit, minmax(130px, 1fr))',
                        gap: '0.5rem',
                        background: '#ffffff',
                        padding: '0.65rem 0.75rem',
                        borderRadius: '8px',
                        border: '1px solid #dcfce7',
                        fontSize: '0.8rem',
                      }}>
                        <div>
                          <span style={{ color: '#6b7280', display: 'block', fontSize: '0.72rem' }}>Transferred Amount:</span>
                          <strong style={{ color: '#111827' }}>${amt.toFixed(2)} USDT</strong>
                        </div>
                        {scPercent > 0 && (
                          <div>
                            <span style={{ color: '#6b7280', display: 'block', fontSize: '0.72rem' }}>Fee ({scPercent}%):</span>
                            <strong style={{ color: '#d97706' }}>-${fee.toFixed(2)} USDT</strong>
                          </div>
                        )}
                        <div>
                          <span style={{ color: '#6b7280', display: 'block', fontSize: '0.72rem' }}>Net Wallet Credit:</span>
                          <strong style={{ color: '#059669', fontSize: '0.92rem' }}>+${net.toFixed(2)} USD</strong>
                        </div>
                        {scPercent > 0 && (
                          <div>
                            <span style={{ color: '#6b7280', display: 'block', fontSize: '0.72rem' }}>Formula:</span>
                            <strong style={{ color: '#047857' }}>${amt.toFixed(2)} / {1 + (scPercent / 100)} = ${net.toFixed(2)}</strong>
                          </div>
                        )}
                      </div>

                      <div style={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        flexWrap: 'wrap',
                        gap: '0.4rem',
                        paddingTop: '0.4rem',
                        borderTop: '1px dashed #d1fae5',
                        color: '#065f46',
                        fontWeight: 600,
                      }}>
                        <span>
                          Fund Wallet Credit: <strong style={{ color: '#059669' }}>+${net.toFixed(2)} USD</strong>
                        </span>
                        <span>
                          New Balance: <strong>${(depositStats.fund_wallet + net).toFixed(2)} USD</strong>
                        </span>
                      </div>
                    </div>
                  );
                })()}
              </div>

              {/* Transaction Hash */}
              <div style={{ marginBottom: '1.5rem' }}>
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '0.4rem', flexWrap: 'wrap', gap: '0.5rem' }}>
                  <label htmlFor="manual-txhash" style={{ fontSize: '0.88rem', fontWeight: 600, color: '#374151' }}>
                    Blockchain Transaction Hash <span style={{ color: '#dc2626' }}>*</span>
                  </label>

                  {/* Local Testing Mode Helper Buttons */}
                  {config?.is_test_mode && (
                    <div style={{ display: 'flex', gap: '0.4rem', flexWrap: 'wrap' }}>
                      <button
                        type="button"
                        onClick={() => {
                          const sc = Number(config?.deposit_fee_percent ?? config?.service_charge_percent ?? 0);
                          const amt = sc > 0 ? (100 * (1 + sc / 100)).toFixed(2) : '100.00';
                          setManualAmount(amt);
                          setManualTxHash(generateTestTxHash());
                          setManualVerificationResult(null);
                          setManualVerificationError('');
                        }}
                        style={{
                          background: '#eff6ff',
                          color: '#1d4ed8',
                          border: '1px solid #bfdbfe',
                          padding: '0.2rem 0.55rem',
                          borderRadius: '6px',
                          fontSize: '0.74rem',
                          fontWeight: 700,
                          cursor: 'pointer',
                        }}
                        title="Auto-fill test hash with $100 + fee (Net $100 USD credit)"
                      >
                        ⚡ Fill Test Hash ($100 Net)
                      </button>
                      <button
                        type="button"
                        onClick={() => {
                          setManualAmount('5.00');
                          setManualTxHash('0x7e5700000000000000000000000000000000000000000000000000000000100a');
                          setManualVerificationResult(null);
                          setManualVerificationError('');
                        }}
                        style={{
                          background: '#fef2f2',
                          color: '#b91c1c',
                          border: '1px solid #fecaca',
                          padding: '0.2rem 0.55rem',
                          borderRadius: '6px',
                          fontSize: '0.74rem',
                          fontWeight: 600,
                          cursor: 'pointer',
                        }}
                        title="Test failure when deposit is below $10 minimum"
                      >
                        Test &lt;$10 Rejection
                      </button>
                    </div>
                  )}
                </div>
                <input
                  id="manual-txhash"
                  type="text"
                  value={manualTxHash}
                  onChange={(e) => setManualTxHash(e.target.value)}
                  placeholder="0x123abc456def..."
                  required
                  style={{
                    width: '100%',
                    padding: '0.75rem 1rem',
                    borderRadius: '12px',
                    border: '1px solid #d1d5db',
                    fontSize: '0.92rem',
                    fontFamily: 'monospace',
                    boxSizing: 'border-box',
                  }}
                />
                <small style={{ color: '#6b7280', fontSize: '0.78rem' }}>
                  Must be a 66-character hexadecimal hash starting with 0x.
                </small>
              </div>

              {/* Verification Error */}
              {manualVerificationError && (
                <div style={{ background: '#fef2f2', border: '1px solid #fecaca', color: '#b91c1c', padding: '0.85rem', borderRadius: '12px', marginBottom: '1.25rem', display: 'flex', gap: '0.65rem', alignItems: 'flex-start', fontSize: '0.88rem' }}>
                  <AlertCircle size={18} style={{ flexShrink: 0, marginTop: '2px' }} />
                  <div>
                    <strong>Verification Failed:</strong> {manualVerificationError}
                  </div>
                </div>
              )}

              {/* Verify Transaction Button */}
              <button
                type="submit"
                disabled={isVerifyingManual}
                style={{
                  width: '100%',
                  padding: '0.85rem',
                  borderRadius: '12px',
                  border: '1px solid #176bff',
                  background: '#176bff',
                  color: '#fff',
                  fontWeight: 700,
                  fontSize: '0.98rem',
                  cursor: isVerifyingManual ? 'not-allowed' : 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  gap: '0.5rem',
                  opacity: isVerifyingManual ? 0.75 : 1,
                }}
              >
                {isVerifyingManual ? (
                  <>
                    <RefreshCw size={18} className="animate-spin" />
                    <span>Verifying on Blockchain...</span>
                  </>
                ) : (
                  <>
                    <ShieldCheck size={18} />
                    <span>Verify Transaction</span>
                  </>
                )}
              </button>
            </form>

            {/* VERIFICATION SUCCESS CARD */}
            {manualVerificationResult && (() => {
              const scPercent = Number(config?.deposit_fee_percent ?? config?.service_charge_percent ?? 0);
              const verifiedAmt = parseFloat(manualVerificationResult.amount) || 0;
              const netCredit = scPercent > 0 ? Number((verifiedAmt / (1 + scPercent / 100)).toFixed(2)) : verifiedAmt;
              const feeAmt = scPercent > 0 ? Number((verifiedAmt - netCredit).toFixed(2)) : 0;

              return (
                <div style={{ marginTop: '1.5rem', background: '#f0fdf4', border: '1px solid #86efac', borderRadius: '16px', padding: '1.25rem' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', color: '#15803d', fontWeight: 800, fontSize: '1rem', marginBottom: '0.75rem' }}>
                    <CheckCircle2 size={22} />
                    <span>Transaction Verified Successfully</span>
                  </div>

                  <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '0.75rem', background: '#fff', padding: '0.85rem', borderRadius: '10px', border: '1px solid #dcfce7', fontSize: '0.84rem', marginBottom: '1rem' }}>
                    <div>
                      <span style={{ color: '#6b7280', display: 'block' }}>Transferred Amount</span>
                      <strong style={{ color: '#111827', fontSize: '1.05rem' }}>${verifiedAmt.toFixed(2)} USDT</strong>
                    </div>
                    <div>
                      <span style={{ color: '#6b7280', display: 'block' }}>Network &amp; Token</span>
                      <strong>{manualVerificationResult.network} ({manualVerificationResult.token})</strong>
                    </div>

                    {scPercent > 0 && (
                      <>
                        <div>
                          <span style={{ color: '#6b7280', display: 'block' }}>Service Charge ({scPercent}% On Top)</span>
                          <strong style={{ color: '#d97706', fontSize: '0.95rem' }}>-${feeAmt.toFixed(2)} USDT</strong>
                        </div>
                        <div>
                          <span style={{ color: '#6b7280', display: 'block' }}>Net Fund Wallet Credit</span>
                          <strong style={{ color: '#059669', fontSize: '1.05rem' }}>+${netCredit.toFixed(2)} USD</strong>
                          <span style={{ fontSize: '0.72rem', color: '#047857', display: 'block' }}>
                            (${verifiedAmt.toFixed(2)} / {1 + (scPercent / 100)})
                          </span>
                        </div>
                      </>
                    )}

                    <div style={{ gridColumn: 'span 2' }}>
                      <span style={{ color: '#6b7280', display: 'block' }}>Sender Wallet</span>
                      <code style={{ fontSize: '0.78rem', wordBreak: 'break-all' }}>{manualVerificationResult.wallet_address}</code>
                    </div>
                    <div style={{ gridColumn: 'span 2' }}>
                      <span style={{ color: '#6b7280', display: 'block' }}>Transaction Hash</span>
                      <code style={{ fontSize: '0.78rem', wordBreak: 'break-all' }}>{manualVerificationResult.transaction_hash}</code>
                    </div>
                    <div style={{ gridColumn: 'span 2', background: '#ecfdf5', padding: '0.65rem 0.85rem', borderRadius: '8px', border: '1px solid #a7f3d0' }}>
                      <div style={{ fontSize: '0.75rem', color: '#065f46', fontWeight: 600 }}>Credit Destination:</div>
                      <div style={{ fontSize: '0.88rem', color: '#047857', fontWeight: 700, marginTop: '0.15rem' }}>
                        Fund Wallet (Current: ${depositStats.fund_wallet.toFixed(2)} USD → After Approval: ${(depositStats.fund_wallet + netCredit).toFixed(2)} USD)
                      </div>
                    </div>
                  </div>

                  {manualSubmitError && (
                    <div style={{ color: '#b91c1c', fontSize: '0.84rem', marginBottom: '0.75rem' }}>
                      {manualSubmitError}
                    </div>
                  )}

                  {/* Submit Deposit Request Button */}
                  <button
                    type="button"
                    onClick={handleSubmitManualRequest}
                    disabled={isSubmittingManual}
                    style={{
                      width: '100%',
                      padding: '0.85rem',
                      borderRadius: '12px',
                      border: 'none',
                      background: '#059669',
                      color: '#fff',
                      fontWeight: 700,
                      fontSize: '1rem',
                      cursor: isSubmittingManual ? 'not-allowed' : 'pointer',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      gap: '0.5rem',
                      boxShadow: '0 4px 12px rgba(5, 150, 105, 0.25)',
                    }}
                  >
                    {isSubmittingManual ? (
                      <>
                        <RefreshCw size={18} className="animate-spin" />
                        <span>Submitting Request to Admin...</span>
                      </>
                    ) : (
                      <>
                        <Send size={18} />
                        <span>Submit Deposit Request (${netCredit.toFixed(2)} USD Net Credit)</span>
                      </>
                    )}
                  </button>
                </div>
              );
            })()}

            {/* Submission Complete Feedback */}
            {manualSubmitSuccess && (
              <div style={{ marginTop: '1.5rem', background: '#eff6ff', border: '1px solid #bfdbfe', borderRadius: '14px', padding: '1.25rem', color: '#1e40af' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', fontWeight: 700, marginBottom: '0.25rem' }}>
                  <CheckCircle2 size={20} color="#2563eb" />
                  <span>Request Submitted to Admin Panel</span>
                </div>
                <p style={{ margin: 0, fontSize: '0.88rem' }}>
                  {manualSubmitSuccess} Your deposit status is currently <strong>Verified — Awaiting Admin Approval</strong>. You will see your <strong>Fund Wallet</strong> balance update as soon as the admin approves the request.
                </p>
              </div>
            )}
          </div>

          {/* QR Code, Wallet Address & Manual Guide Card */}
          <div style={{ background: '#f9fafb', border: '1px solid #e5e7eb', borderRadius: '20px', padding: '1.75rem', display: 'flex', flexDirection: 'column', gap: '1.25rem' }}>
            {/* Card Header with Badges */}
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '0.5rem' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                <div style={{ width: '34px', height: '34px', borderRadius: '10px', background: '#eff6ff', color: '#176bff', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                  <QrCode size={19} />
                </div>
                <div>
                  <h3 style={{ fontSize: '1.05rem', fontWeight: 700, color: '#111827', margin: 0 }}>
                    Official Deposit QR Code
                  </h3>
                  <span style={{ fontSize: '0.75rem', color: '#6b7280' }}>Scan to transfer BEP-20 USDT</span>
                </div>
              </div>
              <div style={{ display: 'flex', gap: '0.35rem', flexWrap: 'wrap' }}>
                <span style={{ background: '#eff6ff', color: '#1d4ed8', border: '1px solid #bfdbfe', fontSize: '0.72rem', fontWeight: 700, padding: '0.2rem 0.55rem', borderRadius: '6px' }}>
                  BEP-20 (BSC)
                </span>
                <span style={{ background: '#ecfdf5', color: '#047857', border: '1px solid #a7f3d0', fontSize: '0.72rem', fontWeight: 700, padding: '0.2rem 0.55rem', borderRadius: '6px' }}>
                  USDT
                </span>
              </div>
            </div>

            {/* QR Code Container */}
            <div style={{
              background: '#ffffff',
              border: '1px solid #e5e7eb',
              borderRadius: '16px',
              padding: '1.25rem',
              display: 'flex',
              flexDirection: 'column',
              alignItems: 'center',
              boxShadow: '0 2px 8px rgba(0,0,0,0.04)',
              textAlign: 'center'
            }}>
              <div style={{
                width: '210px',
                height: '210px',
                borderRadius: '12px',
                border: '2px dashed #93c5fd',
                padding: '6px',
                background: '#ffffff',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                position: 'relative'
              }}>
                {activeQrSrc ? (
                  <img
                    src={activeQrSrc}
                    alt="Deposit QR Code"
                    onError={() => setQrImgFailed(true)}
                    style={{
                      width: '100%',
                      height: '100%',
                      objectFit: 'contain',
                      borderRadius: '8px'
                    }}
                  />
                ) : (
                  <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', color: '#9ca3af' }}>
                    <RefreshCw size={24} className="animate-spin" />
                    <span style={{ fontSize: '0.75rem', marginTop: '0.5rem' }}>Loading QR...</span>
                  </div>
                )}
              </div>

              <div style={{ marginTop: '0.75rem', fontSize: '0.8rem', color: '#4b5563', fontWeight: 500 }}>
                Scan using Binance, Trust Wallet, MetaMask or any Web3 app
              </div>

              {qrImageUrl && !qrImgFailed && (
                <a
                  href={qrImageUrl}
                  target="_blank"
                  rel="noopener noreferrer"
                  style={{
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: '0.3rem',
                    fontSize: '0.75rem',
                    color: '#2563eb',
                    fontWeight: 600,
                    textDecoration: 'none',
                    marginTop: '0.4rem'
                  }}
                >
                  <span>Open Full Size QR</span>
                  <ExternalLink size={12} />
                </a>
              )}
            </div>

            {/* Official Destination BEP-20 Wallet Address */}
            <div style={{ background: '#ffffff', border: '1px solid #e5e7eb', borderRadius: '14px', padding: '1rem' }}>
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '0.4rem' }}>
                <span style={{ fontSize: '0.74rem', fontWeight: 700, color: '#6b7280', textTransform: 'uppercase', letterSpacing: '0.04em' }}>
                  Destination BEP-20 Wallet Address
                </span>
                {config?.crypto_wallet_address && (
                  <button
                    type="button"
                    onClick={() => copyToClipboard(config.crypto_wallet_address)}
                    style={{
                      background: copiedHash === config.crypto_wallet_address ? '#dcfce7' : '#f3f4f6',
                      border: '1px solid ' + (copiedHash === config.crypto_wallet_address ? '#86efac' : '#d1d5db'),
                      color: copiedHash === config.crypto_wallet_address ? '#15803d' : '#374151',
                      padding: '0.2rem 0.55rem',
                      borderRadius: '6px',
                      fontSize: '0.74rem',
                      fontWeight: 700,
                      cursor: 'pointer',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '0.3rem'
                    }}
                    title="Copy wallet address"
                  >
                    {copiedHash === config.crypto_wallet_address ? (
                      <>
                        <Check size={13} color="#15803d" />
                        <span>Copied!</span>
                      </>
                    ) : (
                      <>
                        <Copy size={13} />
                        <span>Copy</span>
                      </>
                    )}
                  </button>
                )}
              </div>
              <code style={{
                display: 'block',
                fontSize: '0.82rem',
                fontFamily: 'monospace',
                color: '#111827',
                fontWeight: 600,
                wordBreak: 'break-all',
                background: '#f9fafb',
                padding: '0.5rem 0.65rem',
                borderRadius: '8px',
                border: '1px solid #e5e7eb'
              }}>
                {config?.crypto_wallet_address || 'Loading destination address...'}
              </code>
            </div>

            {/* Admin Custom Instructions Notice (if present) */}
            {config?.deposit_instructions && (
              <div style={{
                background: '#eff6ff',
                border: '1px solid #bfdbfe',
                borderRadius: '12px',
                padding: '0.75rem 1rem',
                fontSize: '0.82rem',
                color: '#1e40af',
                lineHeight: 1.45
              }}>
                <strong style={{ display: 'block', marginBottom: '0.2rem' }}>Deposit Instructions:</strong>
                {config.deposit_instructions}
              </div>
            )}

            {/* Quick 3-Step Verification Guide */}
            <div>
              <div style={{ fontSize: '0.86rem', fontWeight: 700, color: '#374151', marginBottom: '0.65rem' }}>
                How to Complete Manual Deposit:
              </div>
              <div style={{ display: 'flex', flexDirection: 'column', gap: '0.65rem' }}>
                <div style={{ display: 'flex', gap: '0.65rem', alignItems: 'flex-start' }}>
                  <div style={{ width: '22px', height: '22px', borderRadius: '50%', background: '#176bff', color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: 700, flexShrink: 0, fontSize: '0.75rem' }}>
                    1
                  </div>
                  <div style={{ fontSize: '0.82rem', color: '#4b5563' }}>
                    <strong style={{ color: '#111827' }}>Send USDT:</strong> Scan QR or transfer BEP-20 USDT to the address above. Remember service charge is added on top (e.g. transfer $105 USDT for $100.00 Fund Wallet credit).
                  </div>
                </div>

                <div style={{ display: 'flex', gap: '0.65rem', alignItems: 'flex-start' }}>
                  <div style={{ width: '22px', height: '22px', borderRadius: '50%', background: '#176bff', color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: 700, flexShrink: 0, fontSize: '0.75rem' }}>
                    2
                  </div>
                  <div style={{ fontSize: '0.82rem', color: '#4b5563' }}>
                    <strong style={{ color: '#111827' }}>Copy Tx Hash:</strong> From your wallet or BSCScan explorer, copy the 66-character Transaction Hash (0x...).
                  </div>
                </div>

                <div style={{ display: 'flex', gap: '0.65rem', alignItems: 'flex-start' }}>
                  <div style={{ width: '22px', height: '22px', borderRadius: '50%', background: '#176bff', color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: 700, flexShrink: 0, fontSize: '0.75rem' }}>
                    3
                  </div>
                  <div style={{ fontSize: '0.82rem', color: '#4b5563' }}>
                    <strong style={{ color: '#111827' }}>Verify &amp; Submit:</strong> Enter the amount and hash on the left, click <em>Verify</em> and then <em>Submit</em> for instant admin review.
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* SECTION 3: TRANSACTION & DEPOSIT HISTORY */}
      <section style={{ background: '#fff', border: '1px solid #e5e7eb', borderRadius: '20px', padding: '1.75rem', boxShadow: '0 4px 6px -1px rgba(0,0,0,0.05)' }}>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '1.25rem', flexWrap: 'wrap', gap: '0.5rem' }}>
          <div>
            <h3 style={{ fontSize: '1.15rem', fontWeight: 700, color: '#111827', margin: 0 }}>
              Your Deposit History
            </h3>
            <p style={{ color: '#6b7280', fontSize: '0.85rem', margin: '0.15rem 0 0 0' }}>
              Real-time audit log of your blockchain transactions, verification results, and admin review statuses.
            </p>
          </div>

          <button
            type="button"
            onClick={() => loadHistory(historyPage)}
            disabled={isLoadingHistory}
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: '0.4rem',
              background: '#f3f4f6',
              border: 'none',
              padding: '0.5rem 0.85rem',
              borderRadius: '8px',
              cursor: 'pointer',
              fontSize: '0.82rem',
              fontWeight: 600,
              color: '#374151',
            }}
          >
            <RefreshCw size={14} className={isLoadingHistory ? 'animate-spin' : ''} />
            <span>Refresh</span>
          </button>
        </div>

        {/* History Table */}
        {isLoadingHistory ? (
          <div style={{ textAlign: 'center', padding: '3rem 0', color: '#6b7280' }}>
            <RefreshCw size={24} className="animate-spin" style={{ margin: '0 auto 0.75rem auto' }} />
            <div>Loading deposit records...</div>
          </div>
        ) : history.length === 0 ? (
          <div style={{ textAlign: 'center', padding: '3rem 1rem', background: '#f9fafb', borderRadius: '12px', border: '1px dashed #d1d5db' }}>
            <Wallet size={36} color="#9ca3af" style={{ margin: '0 auto 0.5rem auto' }} />
            <h4 style={{ margin: '0 0 0.25rem 0', color: '#374151', fontWeight: 600 }}>No Deposits Found</h4>
            <p style={{ color: '#6b7280', fontSize: '0.85rem', margin: 0 }}>
              You have not submitted any deposits yet. Use Section 1 or Section 2 above to get started.
            </p>
          </div>
        ) : (
          <div style={{ overflowX: 'auto' }}>
            <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left', fontSize: '0.88rem' }}>
              <thead>
                <tr style={{ borderBottom: '1px solid #e5e7eb', color: '#6b7280', fontSize: '0.78rem', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                  <th style={{ padding: '0.75rem 1rem' }}>Order / ID</th>
                  <th style={{ padding: '0.75rem 1rem' }}>Amount</th>
                  <th style={{ padding: '0.75rem 1rem' }}>Transaction Hash</th>
                  <th style={{ padding: '0.75rem 1rem' }}>Status</th>
                  <th style={{ padding: '0.75rem 1rem' }}>Date</th>
                </tr>
              </thead>
              <tbody>
                {history.map((item) => {
                  const status = item.status || 'pending';
                  const isApproved = status === 'approved';
                  const isVerified = status === 'verified';
                  const isRejected = status === 'rejected' || status === 'cancelled';

                  return (
                    <tr key={item.id} style={{ borderBottom: '1px solid #f3f4f6' }}>
                      <td style={{ padding: '0.9rem 1rem', fontWeight: 600, color: '#111827' }}>
                        {item.orderid || `#${item.id}`}
                        <span style={{ display: 'block', fontSize: '0.75rem', color: '#9ca3af', fontWeight: 400 }}>
                          Mode: {item.mode || 'Online'}
                        </span>
                      </td>

                      <td style={{ padding: '0.9rem 1rem', fontWeight: 700, color: '#111827' }}>
                        ${Number(item.amount).toFixed(2)} <span style={{ fontSize: '0.75rem', color: '#6b7280' }}>USDT</span>
                      </td>

                      <td style={{ padding: '0.9rem 1rem' }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '0.4rem' }}>
                          <code style={{ fontSize: '0.82rem', fontFamily: 'monospace', color: '#374151' }}>
                            {formatAddress(item.transaction_hash)}
                          </code>
                          {item.transaction_hash && (
                            <button
                              type="button"
                              onClick={() => copyToClipboard(item.transaction_hash)}
                              style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#9ca3af', padding: '0.2rem' }}
                              title="Copy transaction hash"
                            >
                              {copiedHash === item.transaction_hash ? <Check size={14} color="#059669" /> : <Copy size={14} />}
                            </button>
                          )}
                          {item.explorer_url && (
                            <a
                              href={item.explorer_url}
                              target="_blank"
                              rel="noopener noreferrer"
                              style={{ color: '#176bff', display: 'inline-flex', alignItems: 'center' }}
                              title="View on BSCScan"
                            >
                              <ExternalLink size={14} />
                            </a>
                          )}
                        </div>
                      </td>

                      <td style={{ padding: '0.9rem 1rem' }}>
                        {isApproved && (
                          <span style={{ display: 'inline-flex', alignItems: 'center', gap: '0.35rem', background: '#dcfce7', color: '#15803d', padding: '0.25rem 0.65rem', borderRadius: '9999px', fontSize: '0.78rem', fontWeight: 700 }}>
                            <CheckCircle2 size={13} />
                            <span>Approved</span>
                          </span>
                        )}
                        {isVerified && (
                          <span style={{ display: 'inline-flex', alignItems: 'center', gap: '0.35rem', background: '#fef3c7', color: '#b45309', padding: '0.25rem 0.65rem', borderRadius: '9999px', fontSize: '0.78rem', fontWeight: 700 }}>
                            <Clock size={13} />
                            <span>Verified — Awaiting Admin Approval</span>
                          </span>
                        )}
                        {isRejected && (
                          <span style={{ display: 'inline-flex', alignItems: 'center', gap: '0.35rem', background: '#fee2e2', color: '#b91c1c', padding: '0.25rem 0.65rem', borderRadius: '9999px', fontSize: '0.78rem', fontWeight: 700 }}>
                            <XCircle size={13} />
                            <span>Rejected</span>
                          </span>
                        )}
                        {!isApproved && !isVerified && !isRejected && (
                          <span style={{ display: 'inline-flex', alignItems: 'center', gap: '0.35rem', background: '#f3f4f6', color: '#4b5563', padding: '0.25rem 0.65rem', borderRadius: '9999px', fontSize: '0.78rem', fontWeight: 600 }}>
                            <Clock size={13} />
                            <span>Pending Verification</span>
                          </span>
                        )}
                      </td>

                      <td style={{ padding: '0.9rem 1rem', color: '#6b7280', fontSize: '0.82rem' }}>
                        {item.created_at ? new Date(item.created_at).toLocaleString() : '—'}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>

            {/* Pagination Controls */}
            {historyPagination.last_page > 1 && (
              <div style={{ display: 'flex', justifyContent: 'flex-end', alignItems: 'center', gap: '0.5rem', marginTop: '1.25rem' }}>
                <button
                  type="button"
                  onClick={() => {
                    const newP = Math.max(1, historyPage - 1);
                    setHistoryPage(newP);
                    loadHistory(newP);
                  }}
                  disabled={historyPage <= 1}
                  style={{ padding: '0.4rem 0.75rem', borderRadius: '6px', border: '1px solid #d1d5db', background: '#fff', cursor: historyPage <= 1 ? 'not-allowed' : 'pointer', opacity: historyPage <= 1 ? 0.5 : 1 }}
                >
                  Previous
                </button>
                <span style={{ fontSize: '0.84rem', color: '#6b7280' }}>
                  Page {historyPage} of {historyPagination.last_page}
                </span>
                <button
                  type="button"
                  onClick={() => {
                    const newP = Math.min(historyPagination.last_page, historyPage + 1);
                    setHistoryPage(newP);
                    loadHistory(newP);
                  }}
                  disabled={historyPage >= historyPagination.last_page}
                  style={{ padding: '0.4rem 0.75rem', borderRadius: '6px', border: '1px solid #d1d5db', background: '#fff', cursor: historyPage >= historyPagination.last_page ? 'not-allowed' : 'pointer', opacity: historyPage >= historyPagination.last_page ? 0.5 : 1 }}
                >
                  Next
                </button>
              </div>
            )}
          </div>
        )}
      </section>
    </main>
  );
}

export default DepositPage;
