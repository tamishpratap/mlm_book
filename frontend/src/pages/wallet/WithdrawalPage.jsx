import { useState, useEffect, useCallback } from 'react';
import {
  ArrowDownToLine,
  Wallet,
  Clock,
  CheckCircle2,
  AlertCircle,
  ShieldCheck,
  RefreshCw,
  Info,
  DollarSign,
  TrendingDown,
  ArrowRight,
  UserCheck,
  Percent,
} from 'lucide-react';
import withdrawalApi from '../../api/withdrawalApi';
import useAuth from '../../hooks/useAuth';
import '../../styles/member-withdrawal.css';

export function WithdrawalPage() {
  const { user } = useAuth();

  // Data states
  const [memberData, setMemberData] = useState(null);
  const [config, setConfig] = useState({
    service_charge_percent: 10.0,
    minimum_amount: 5.0,
    currency_symbol: '$',
  });
  const [stats, setStats] = useState({
    pending_amount: 0,
    approved_amount: 0,
    total_requested: 0,
    total_count: 0,
  });
  const [withdrawals, setWithdrawals] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [loadError, setLoadError] = useState(null);

  // Form states
  const [grossAmount, setGrossAmount] = useState('');
  const [remarks, setRemarks] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [formError, setFormError] = useState(null);
  const [formSuccess, setFormSuccess] = useState(null);

  // Fetch withdrawal data
  const fetchData = useCallback(async () => {
    try {
      setIsLoading(true);
      setLoadError(null);
      const res = await withdrawalApi.getWithdrawals();
      if (res && res.success) {
        setMemberData(res.member);
        if (res.config) setConfig(res.config);
        if (res.stats) setStats(res.stats);
        if (res.withdrawals) setWithdrawals(res.withdrawals);
      }
    } catch (err) {
      console.error('Failed to load withdrawal details:', err);
      setLoadError(err.response?.data?.message || 'Failed to load withdrawal data.');
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  // Real-time calculation
  const parsedGross = parseFloat(grossAmount) || 0;
  const serviceChargePercent = config?.service_charge_percent || 10.0;
  const calculatedServiceCharge = parsedGross > 0 ? +(parsedGross * (serviceChargePercent / 100)).toFixed(2) : 0;
  const calculatedNetAmount = parsedGross > 0 ? +(parsedGross - calculatedServiceCharge).toFixed(2) : 0;

  // Quick select amounts
  const handleQuickAmount = (val) => {
    setGrossAmount(val.toString());
    setFormError(null);
  };

  // Submit Handler
  const handleSubmit = async (e) => {
    e.preventDefault();
    setFormError(null);
    setFormSuccess(null);

    const amount = parseFloat(grossAmount);
    const minAmount = config?.minimum_amount || 5.0;

    if (!amount || isNaN(amount) || amount <= 0) {
      setFormError('Please enter a valid withdrawal gross amount.');
      return;
    }

    if (amount < minAmount) {
      setFormError(`Minimum withdrawal amount is ${config.currency_symbol || '$'}${minAmount.toFixed(2)}.`);
      return;
    }

    try {
      setIsSubmitting(true);
      const payload = {
        gross_amount: amount,
        remarks: remarks.trim(),
      };

      const res = await withdrawalApi.submitWithdrawal(payload);

      if (res && res.success) {
        setFormSuccess(res.message || 'Withdrawal request submitted successfully!');
        setGrossAmount('');
        setRemarks('');
        // Refresh data to show newly submitted pending request
        await fetchData();
      } else {
        setFormError(res.message || 'Failed to submit withdrawal request.');
      }
    } catch (err) {
      console.error('Withdrawal error:', err);
      const serverMsg = err.response?.data?.message;
      const validationErrors = err.response?.data?.errors;
      if (validationErrors) {
        const firstError = Object.values(validationErrors)[0];
        setFormError(Array.isArray(firstError) ? firstError[0] : firstError);
      } else {
        setFormError(serverMsg || 'An error occurred while submitting withdrawal request.');
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="withdrawal-page-container">
      {/* Header */}
      <header className="withdrawal-header">
        <h1 className="withdrawal-header__title">
          <ArrowDownToLine size={28} color="#2563eb" />
          <span>Withdrawal Section</span>
        </h1>
        <p className="withdrawal-header__desc">
          Request a withdrawal from your account. All requests are submitted with <strong>Pending</strong> status for admin review with a standard 10% service charge deduction.
        </p>
      </header>

      {/* Summary Stats */}
      <section className="withdrawal-stats-grid" aria-label="Withdrawal Statistics">
        <div className="withdrawal-stat-card">
          <div className="withdrawal-stat-icon withdrawal-stat-icon--primary">
            <Wallet size={22} />
          </div>
          <div className="withdrawal-stat-info">
            <span className="withdrawal-stat-label">Wallet Balance</span>
            <span className="withdrawal-stat-value">
              ${parseFloat(memberData?.wallet || user?.wallet || 0).toFixed(4)}
            </span>
          </div>
        </div>

        <div className="withdrawal-stat-card">
          <div className="withdrawal-stat-icon withdrawal-stat-icon--warning">
            <Clock size={22} />
          </div>
          <div className="withdrawal-stat-info">
            <span className="withdrawal-stat-label">Pending Requests</span>
            <span className="withdrawal-stat-value">
              ${parseFloat(stats.pending_amount || 0).toFixed(2)}
            </span>
          </div>
        </div>

        <div className="withdrawal-stat-card">
          <div className="withdrawal-stat-icon withdrawal-stat-icon--purple">
            <DollarSign size={22} />
          </div>
          <div className="withdrawal-stat-info">
            <span className="withdrawal-stat-label">Cancelled Requests</span>
            <span className="withdrawal-stat-value">
              ${parseFloat(stats.cancelled_amount || 0).toFixed(2)}
            </span>
          </div>
        </div>

        <div className="withdrawal-stat-card">
          <div className="withdrawal-stat-icon withdrawal-stat-icon--success">
            <CheckCircle2 size={22} />
          </div>
          <div className="withdrawal-stat-info">
            <span className="withdrawal-stat-label">Total Paid Out</span>
            <span className="withdrawal-stat-value">
              ${parseFloat(stats.approved_amount || 0).toFixed(2)}
            </span>
          </div>
        </div>
      </section>

      {/* Main Grid: Form + Info Breakdown */}
      <div className="withdrawal-content-grid">
        {/* Left Column: Form & Real-time Calculator */}
        <div className="withdrawal-card">
          <div className="withdrawal-card__header">
            <h2 className="withdrawal-card__title">
              <DollarSign size={20} color="#2563eb" />
              <span>Create Withdrawal Request</span>
            </h2>
            <span className="breakdown-badge--charge">
              10% Service Charge
            </span>
          </div>

          <div className="withdrawal-card__body">
            {formSuccess && (
              <div className="withdrawal-alert withdrawal-alert--success" role="alert">
                <CheckCircle2 size={20} />
                <div>
                  <strong>Request Submitted!</strong>
                  <p style={{ margin: '0.2rem 0 0' }}>{formSuccess}</p>
                </div>
              </div>
            )}

            {formError && (
              <div className="withdrawal-alert withdrawal-alert--error" role="alert">
                <AlertCircle size={20} />
                <div>
                  <strong>Error</strong>
                  <p style={{ margin: '0.2rem 0 0' }}>{formError}</p>
                </div>
              </div>
            )}

            <form onSubmit={handleSubmit}>
              {/* Gross Amount Input */}
              <div className="withdrawal-form-group">
                <label htmlFor="gross_amount" className="withdrawal-form-label">
                  Withdrawal Gross Amount <span style={{ color: '#ef4444' }}>*</span>
                </label>
                <div className="withdrawal-input-wrap">
                  <span className="withdrawal-input-prefix">$</span>
                  <input
                    id="gross_amount"
                    name="gross_amount"
                    type="number"
                    step="0.01"
                    min={config.minimum_amount || 5}
                    placeholder="0.00"
                    className="withdrawal-input withdrawal-input--with-prefix"
                    value={grossAmount}
                    onChange={(e) => {
                      setGrossAmount(e.target.value);
                      if (formError) setFormError(null);
                    }}
                    required
                  />
                </div>

                {/* Quick select buttons */}
                <div className="withdrawal-quick-amounts">
                  {[25, 50, 100, 250, 500].map((amt) => (
                    <button
                      key={amt}
                      type="button"
                      className={`withdrawal-quick-btn ${parsedGross === amt ? 'is-active' : ''}`}
                      onClick={() => handleQuickAmount(amt)}
                    >
                      ${amt}
                    </button>
                  ))}
                  {memberData?.wallet > 0 && (
                    <button
                      type="button"
                      className="withdrawal-quick-btn"
                      onClick={() => handleQuickAmount(Math.floor(memberData.wallet))}
                    >
                      Max Wallet (${Math.floor(memberData.wallet)})
                    </button>
                  )}
                </div>
              </div>

              {/* Real-time Calculation Breakdown Box */}
              <div className="withdrawal-breakdown-box">
                <div className="breakdown-row">
                  <span className="breakdown-label">
                    <span>Gross Withdrawal Amount</span>
                  </span>
                  <span className="breakdown-value">
                    ${parsedGross.toFixed(2)} USD
                  </span>
                </div>

                <div className="breakdown-row breakdown-row--border">
                  <span className="breakdown-label" style={{ color: '#dc2626' }}>
                    <Percent size={15} />
                    <span>Platform Service Charge ({serviceChargePercent}%)</span>
                  </span>
                  <span className="breakdown-value" style={{ color: '#dc2626' }}>
                    - ${calculatedServiceCharge.toFixed(2)} USD
                  </span>
                </div>

                <div className="breakdown-row breakdown-row--total">
                  <span className="breakdown-label">
                    <span>Net Payable Amount</span>
                  </span>
                  <span className="breakdown-value">
                    ${calculatedNetAmount.toFixed(2)} USD
                  </span>
                </div>
              </div>

              {/* Remarks / Notes */}
              <div className="withdrawal-form-group">
                <label htmlFor="remarks" className="withdrawal-form-label">
                  Remarks / Notes (Optional)
                </label>
                <textarea
                  id="remarks"
                  name="remarks"
                  className="withdrawal-textarea"
                  placeholder="Add any specific instructions or references for the admin..."
                  value={remarks}
                  onChange={(e) => setRemarks(e.target.value)}
                  maxLength={500}
                />
              </div>

              {/* Submit Button */}
              <button
                type="submit"
                className="withdrawal-submit-btn"
                disabled={isSubmitting || parsedGross <= 0}
              >
                {isSubmitting ? (
                  <>
                    <RefreshCw size={18} className="animate-spin" />
                    <span>Processing Request...</span>
                  </>
                ) : (
                  <>
                    <span>Submit Pending Withdrawal</span>
                    <ArrowRight size={18} />
                  </>
                )}
              </button>
            </form>
          </div>
        </div>

        {/* Right Column: Member Details & Policy Rules */}
        <div>
          {/* Member Details Card */}
          <div className="withdrawal-card" style={{ marginBottom: '1.5rem' }}>
            <div className="withdrawal-card__header">
              <h2 className="withdrawal-card__title">
                <UserCheck size={18} color="#2563eb" />
                <span>Member Details</span>
              </h2>
            </div>
            <div className="withdrawal-card__body">
              <div className="member-identity-pill">
                <div className="member-identity-row">
                  <span>User ID / Member ID</span>
                  <strong>{memberData?.user_id || user?.user_id || 'N/A'}</strong>
                </div>
                <div className="member-identity-row">
                  <span>Name</span>
                  <span>{memberData?.name || user?.name || 'N/A'}</span>
                </div>
                <div className="member-identity-row">
                  <span>Email</span>
                  <span>{memberData?.email || user?.email || 'N/A'}</span>
                </div>
                {memberData?.phone && (
                  <div className="member-identity-row">
                    <span>Phone</span>
                    <span>{memberData.phone}</span>
                  </div>
                )}
              </div>

              <div style={{ fontSize: '0.875rem', color: '#475569' }}>
                <p style={{ margin: '0 0 0.5rem', fontWeight: 600, color: '#1e293b' }}>
                  Verification Status:
                </p>
                <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                  <ShieldCheck size={18} color={memberData?.is_verified ? '#10b981' : '#f59e0b'} />
                  <span>{memberData?.is_verified ? 'Verified Member' : 'Standard Member'}</span>
                </div>
              </div>
            </div>
          </div>

          {/* Guidelines & Terms Card */}
          <div className="withdrawal-card">
            <div className="withdrawal-card__header">
              <h2 className="withdrawal-card__title">
                <Info size={18} color="#2563eb" />
                <span>Withdrawal Policy</span>
              </h2>
            </div>
            <div className="withdrawal-card__body">
              <ul className="withdrawal-rule-list">
                <li className="withdrawal-rule-item">
                  <TrendingDown size={18} color="#d97706" />
                  <span>
                    <strong>10% Platform Deduction:</strong> A 10% service charge is automatically deducted from your gross amount. You will receive the net amount.
                  </span>
                </li>
                <li className="withdrawal-rule-item">
                  <Clock size={18} color="#2563eb" />
                  <span>
                    <strong>Pending Review:</strong> All requests are placed in <strong>Pending</strong> status and queued for verification and approval by the administrative team.
                  </span>
                </li>
                <li className="withdrawal-rule-item">
                  <CheckCircle2 size={18} color="#059669" />
                  <span>
                    <strong>Minimum Payout:</strong> Minimum withdrawal amount is ${config.minimum_amount?.toFixed(2) || '5.00'} USD.
                  </span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      {/* Past Withdrawal Requests History */}
      <section className="withdrawal-card" aria-label="Withdrawal History">
        <div className="withdrawal-card__header">
          <div className="withdrawal-history-header" style={{ marginBottom: 0 }}>
            <h2 className="withdrawal-history-title" style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
              <Clock size={20} color="#2563eb" />
              <span>Withdrawal Requests History</span>
            </h2>
          </div>
          <button
            type="button"
            className="withdrawal-quick-btn"
            onClick={fetchData}
            disabled={isLoading}
            title="Refresh history"
            style={{ display: 'flex', alignItems: 'center', gap: '4px' }}
          >
            <RefreshCw size={14} className={isLoading ? 'animate-spin' : ''} />
            <span>Refresh</span>
          </button>
        </div>

        <div className="withdrawal-card__body" style={{ padding: 0 }}>
          {isLoading && withdrawals.length === 0 ? (
            <div className="withdrawal-empty-state">
              <RefreshCw size={32} className="animate-spin" style={{ margin: '0 auto 1rem' }} />
              <p>Loading withdrawal requests...</p>
            </div>
          ) : withdrawals.length === 0 ? (
            <div className="withdrawal-empty-state">
              <div className="withdrawal-empty-icon">
                <ArrowDownToLine size={28} />
              </div>
              <h3 style={{ fontSize: '1.1rem', color: '#1e293b', margin: '0 0 0.35rem' }}>
                No Withdrawal Requests Yet
              </h3>
              <p style={{ margin: 0, fontSize: '0.9rem' }}>
                When you request a withdrawal, your pending and processed transactions will appear here.
              </p>
            </div>
          ) : (
            <div className="withdrawal-table-responsive">
              <table className="withdrawal-table">
                <thead>
                  <tr>
                    <th>Request ID</th>
                    <th>Date</th>
                    <th>Gross Amount</th>
                    <th>Service Charge (10%)</th>
                    <th>Net Amount</th>
                    <th>Wallet Address</th>
                    <th>Status</th>
                    <th>Remarks</th>
                  </tr>
                </thead>
                <tbody>
                  {withdrawals.map((item) => {
                    const statusClass =
                      item.status?.toLowerCase() === 'approved' || item.status?.toLowerCase() === 'verified'
                        ? 'status-badge--approved'
                        : item.status?.toLowerCase() === 'cancelled'
                        ? 'status-badge--cancelled'
                        : 'status-badge--pending';

                    return (
                      <tr key={item.id}>
                        <td style={{ fontWeight: 600, fontFamily: 'monospace', color: '#0f172a' }}>
                          {item.request_id || `#${item.id}`}
                        </td>
                        <td style={{ color: '#64748b', fontSize: '0.85rem' }}>
                          {item.request_date
                            ? new Date(item.request_date).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit',
                              })
                            : item.created_at
                            ? new Date(item.created_at).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric',
                              })
                            : '-'}
                        </td>
                        <td style={{ fontWeight: 600, color: '#1e293b' }}>
                          ${parseFloat(item.gross_amount || 0).toFixed(2)}
                        </td>
                        <td style={{ color: '#dc2626', fontWeight: 600 }}>
                          - ${parseFloat(item.service_charge || 0).toFixed(2)}
                        </td>
                        <td style={{ color: '#059669', fontWeight: 700, fontSize: '0.95rem' }}>
                          ${parseFloat(item.net_amount || 0).toFixed(2)}
                        </td>
                        <td style={{ fontSize: '0.85rem', color: '#475569', maxWidth: '160px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }} title={item.wallet_address}>
                          {item.wallet_address || '-'}
                        </td>
                        <td>
                          <span className={`status-badge ${statusClass}`}>
                            {item.status || 'Pending'}
                          </span>
                        </td>
                        <td style={{ fontSize: '0.825rem', color: '#64748b', maxWidth: '160px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }} title={item.remarks}>
                          {item.remarks || '-'}
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </section>
    </div>
  );
}

export default WithdrawalPage;
