import { Link } from 'react-router-dom';
import {
  Wallet,
  ArrowUpRight,
  ArrowDownLeft,
  Sparkles,
  TrendingUp,
  CreditCard,
  History,
  ShieldCheck,
} from 'lucide-react';

export function WalletIntelligenceCard({ wallets }) {
  const fundBalance = Number(wallets?.fund_wallet_balance || 0);
  const rewardBalance = Number(wallets?.reward_wallet_balance || 0);
  const totalDeposited = Number(wallets?.total_deposited || 0);
  const totalUsedAds = Number(wallets?.total_used_ads || 0);
  const activeAdRemaining = Number(wallets?.active_ad_remaining || 0);
  const totalWithdrawn = Number(wallets?.total_withdrawn || 0);
  const pendingWithdrawals = Number(wallets?.pending_withdrawals || 0);

  // Proportional percentages for the progress bar
  const totalBase = Math.max(totalDeposited, (fundBalance + totalUsedAds + totalWithdrawn)) || 1;
  const availablePct = Math.min(100, Math.max(0, (fundBalance / totalBase) * 100));
  const usedAdsPct = Math.min(100 - availablePct, Math.max(0, (totalUsedAds / totalBase) * 100));
  const withdrawnPct = Math.min(100 - availablePct - usedAdsPct, Math.max(0, (totalWithdrawn / totalBase) * 100));

  return (
    <section className="dash-wallet-section" aria-label="Wallet & Financial Intelligence">
      <div className="dash-section-header">
        <div className="dash-section-title">
          <div className="dash-section-icon" style={{ backgroundColor: 'rgba(37, 99, 235, 0.1)', color: '#2563eb' }}>
            <Wallet size={20} />
          </div>
          <div>
            <h2>Wallet & Funds Intelligence</h2>
            <p style={{ margin: 0, fontSize: '0.825rem', color: '#64748b' }}>
              Real-time balance, deposits, campaign allocations & earnings
            </p>
          </div>
        </div>

        <div style={{ display: 'flex', gap: '8px', flexWrap: 'wrap' }}>
          <Link to="/member/deposit" className="dash-btn-primary" style={{ padding: '8px 14px', fontSize: '0.825rem' }}>
            <ArrowDownLeft size={15} />
            <span>Deposit</span>
          </Link>
          <Link to="/member/withdrawal" className="dash-btn-outline" style={{ padding: '8px 14px', fontSize: '0.825rem' }}>
            <ArrowUpRight size={15} />
            <span>Withdraw</span>
          </Link>
        </div>
      </div>

      {/* 4 Hero Metric Cards */}
      <div className="dash-wallet-grid">
        {/* 1. Available Fund Wallet */}
        <div className="dash-metric-card dash-card--fund">
          <div className="dash-card-top">
            <span className="dash-card-label">Available Fund Wallet</span>
            <div className="dash-card-icon">
              <Wallet size={16} />
            </div>
          </div>
          <div>
            <div className="dash-card-value">
              ${fundBalance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
              <span style={{ fontSize: '0.9rem', fontWeight: 600, color: '#3b82f6', marginLeft: '4px' }}>USD</span>
            </div>
            <p className="dash-card-subtext">
              Ready for Ad Campaigns & Page Promotions
            </p>
          </div>
        </div>

        {/* 2. Total Funds Deposited */}
        <div className="dash-metric-card dash-card--deposited">
          <div className="dash-card-top">
            <span className="dash-card-label">Total Deposited</span>
            <div className="dash-card-icon">
              <ArrowDownLeft size={16} />
            </div>
          </div>
          <div>
            <div className="dash-card-value">
              ${totalDeposited.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
              <span style={{ fontSize: '0.9rem', fontWeight: 600, color: '#16a34a', marginLeft: '4px' }}>USD</span>
            </div>
            <p className="dash-card-subtext">
              Lifetime Approved Deposits (USDT BEP-20)
            </p>
          </div>
        </div>

        {/* 3. Funds Used / Committed on Ads */}
        <div className="dash-metric-card dash-card--spent">
          <div className="dash-card-top">
            <span className="dash-card-label">Used on Ad Campaigns</span>
            <div className="dash-card-icon">
              <CreditCard size={16} />
            </div>
          </div>
          <div>
            <div className="dash-card-value">
              ${totalUsedAds.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
              <span style={{ fontSize: '0.9rem', fontWeight: 600, color: '#9333ea', marginLeft: '4px' }}>USD</span>
            </div>
            <p className="dash-card-subtext">
              {activeAdRemaining > 0
                ? `$${activeAdRemaining.toFixed(2)} active in campaigns`
                : 'Allocated to advertising'}
            </p>
          </div>
        </div>

        {/* 4. Reward Earnings Wallet */}
        <div className="dash-metric-card dash-card--reward">
          <div className="dash-card-top">
            <span className="dash-card-label">Earn Reward</span>
            <div className="dash-card-icon">
              <Sparkles size={16} />
            </div>
          </div>
          <div>
            <div className="dash-card-value">
              ${rewardBalance.toLocaleString('en-US', { minimumFractionDigits: 4, maximumFractionDigits: 4 })}
              <span style={{ fontSize: '0.9rem', fontWeight: 600, color: '#d97706', marginLeft: '4px' }}>USDT</span>
            </div>
            <p className="dash-card-subtext">
              Earned from social rewards & ad tasks
            </p>
          </div>
        </div>
      </div>

      {/* Utilization & Fund Distribution Bar */}
      <div className="dash-utilization-container">
        <div className="dash-utilization-header">
          <span style={{ fontWeight: 700, color: '#1e293b', display: 'flex', alignItems: 'center', gap: '6px' }}>
            <TrendingUp size={15} color="#2563eb" />
            <span>Fund Utilization & Capital Allocation</span>
          </span>
          <span>
            {totalDeposited > 0 ? (
              <span><strong>{wallets?.utilization_rate || 0}%</strong> of deposited funds utilized</span>
            ) : (
              <span>No deposits yet</span>
            )}
          </span>
        </div>

        <div className="dash-progress-track">
          <div
            className="dash-progress-fill--available"
            style={{ width: `${availablePct}%` }}
            title={`Available Balance: $${fundBalance.toFixed(2)} (${availablePct.toFixed(1)}%)`}
          />
          <div
            className="dash-progress-fill--spent"
            style={{ width: `${usedAdsPct}%` }}
            title={`Used on Ads: $${totalUsedAds.toFixed(2)} (${usedAdsPct.toFixed(1)}%)`}
          />
          <div
            className="dash-progress-fill--withdrawn"
            style={{ width: `${withdrawnPct}%` }}
            title={`Withdrawn: $${totalWithdrawn.toFixed(2)} (${withdrawnPct.toFixed(1)}%)`}
          />
        </div>

        <div className="dash-utilization-legends">
          <div className="dash-legend-item">
            <div className="dash-legend-dot" style={{ backgroundColor: '#2563eb' }} />
            <span>Available Balance: <strong>${fundBalance.toFixed(2)} USD</strong></span>
          </div>
          <div className="dash-legend-item">
            <div className="dash-legend-dot" style={{ backgroundColor: '#9333ea' }} />
            <span>Allocated to Ads: <strong>${totalUsedAds.toFixed(2)} USD</strong></span>
          </div>
          {totalWithdrawn > 0 && (
            <div className="dash-legend-item">
              <div className="dash-legend-dot" style={{ backgroundColor: '#d97706' }} />
              <span>Withdrawn: <strong>${totalWithdrawn.toFixed(2)} USD</strong></span>
            </div>
          )}
          {pendingWithdrawals > 0 && (
            <div className="dash-legend-item" style={{ color: '#dc2626' }}>
              <span>⚠️ Pending Withdrawal: <strong>${pendingWithdrawals.toFixed(2)} USD</strong></span>
            </div>
          )}
        </div>
      </div>
    </section>
  );
}

export default WalletIntelligenceCard;
