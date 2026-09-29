import { useState } from 'react';
import { Link } from 'react-router-dom';
import {
  History,
  ArrowDownLeft,
  ArrowUpRight,
  ExternalLink,
  CheckCircle2,
  Clock,
  XCircle,
} from 'lucide-react';

export function RecentTransactionsCard({ transactions }) {
  const [activeTab, setActiveTab] = useState('deposits'); // 'deposits' | 'withdrawals'

  const deposits = transactions?.deposits || [];
  const withdrawals = transactions?.withdrawals || [];

  const getStatusBadge = (status) => {
    const s = String(status || '').toLowerCase();
    if (s === 'approved' || s === 'verified') {
      return (
        <span style={{ display: 'inline-flex', alignItems: 'center', gap: '4px', color: '#15803d', backgroundColor: '#dcfce7', padding: '3px 8px', borderRadius: '12px', fontSize: '0.725rem', fontWeight: 700 }}>
          <CheckCircle2 size={12} />
          <span>Approved</span>
        </span>
      );
    }
    if (s === 'pending') {
      return (
        <span style={{ display: 'inline-flex', alignItems: 'center', gap: '4px', color: '#b45309', backgroundColor: '#fef3c7', padding: '3px 8px', borderRadius: '12px', fontSize: '0.725rem', fontWeight: 700 }}>
          <Clock size={12} />
          <span>Pending</span>
        </span>
      );
    }
    return (
      <span style={{ display: 'inline-flex', alignItems: 'center', gap: '4px', color: '#b91c1c', backgroundColor: '#fee2e2', padding: '3px 8px', borderRadius: '12px', fontSize: '0.725rem', fontWeight: 700 }}>
        <XCircle size={12} />
        <span>{status}</span>
      </span>
    );
  };

  return (
    <section className="dash-wallet-section" aria-label="Recent Financial Activity">
      <div className="dash-section-header">
        <div className="dash-section-title">
          <div className="dash-section-icon" style={{ backgroundColor: 'rgba(16, 185, 129, 0.1)', color: '#10b981' }}>
            <History size={20} />
          </div>
          <div>
            <h2>Recent Financial Activity</h2>
            <p style={{ margin: 0, fontSize: '0.825rem', color: '#64748b' }}>
              Your latest deposits and payout withdrawal records
            </p>
          </div>
        </div>

        {/* Tab Switcher */}
        <div style={{ display: 'flex', gap: '4px', backgroundColor: '#f1f5f9', padding: '3px', borderRadius: '10px' }}>
          <button
            type="button"
            onClick={() => setActiveTab('deposits')}
            style={{
              padding: '6px 14px',
              borderRadius: '8px',
              border: 'none',
              fontSize: '0.8rem',
              fontWeight: 700,
              cursor: 'pointer',
              backgroundColor: activeTab === 'deposits' ? '#ffffff' : 'transparent',
              color: activeTab === 'deposits' ? '#1e293b' : '#64748b',
              boxShadow: activeTab === 'deposits' ? '0 1px 3px rgba(0,0,0,0.1)' : 'none',
              transition: 'all 0.15s ease',
            }}
          >
            Deposits ({deposits.length})
          </button>
          <button
            type="button"
            onClick={() => setActiveTab('withdrawals')}
            style={{
              padding: '6px 14px',
              borderRadius: '8px',
              border: 'none',
              fontSize: '0.8rem',
              fontWeight: 700,
              cursor: 'pointer',
              backgroundColor: activeTab === 'withdrawals' ? '#ffffff' : 'transparent',
              color: activeTab === 'withdrawals' ? '#1e293b' : '#64748b',
              boxShadow: activeTab === 'withdrawals' ? '0 1px 3px rgba(0,0,0,0.1)' : 'none',
              transition: 'all 0.15s ease',
            }}
          >
            Withdrawals ({withdrawals.length})
          </button>
        </div>
      </div>

      {activeTab === 'deposits' ? (
        deposits.length === 0 ? (
          <div style={{ textAlign: 'center', padding: '30px 10px', color: '#94a3b8', fontSize: '0.85rem' }}>
            No deposit records found yet.
          </div>
        ) : (
          <div className="dash-tx-table-wrapper">
            <table className="dash-tx-table">
              <thead>
                <tr>
                  <th>Deposit ID</th>
                  <th>Submitted</th>
                  <th>Fee</th>
                  <th>Net Credited</th>
                  <th>Status</th>
                  <th>Tx Hash</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                {deposits.map((d) => (
                  <tr key={d.id}>
                    <td>
                      <strong style={{ fontFamily: 'monospace', color: '#2563eb' }}>{d.deposit_id}</strong>
                    </td>
                    <td>{d.submitted_amount} {d.currency}</td>
                    <td style={{ color: '#64748b' }}>
                      {d.fee_amount > 0 ? `${d.fee_amount} (${d.fee_percent}%)` : '0%'}
                    </td>
                    <td>
                      <strong style={{ color: '#15803d' }}>+${Number(d.net_amount).toFixed(2)} USD</strong>
                    </td>
                    <td>{getStatusBadge(d.status)}</td>
                    <td>
                      {d.transaction_hash ? (
                        <a
                          href={`https://bscscan.com/tx/${d.transaction_hash}`}
                          target="_blank"
                          rel="noopener noreferrer"
                          style={{
                            color: '#4f7df3',
                            display: 'inline-flex',
                            alignItems: 'center',
                            gap: '4px',
                            fontFamily: 'monospace',
                            fontSize: '0.775rem',
                          }}
                          title={d.transaction_hash}
                        >
                          <span>{d.transaction_hash.slice(0, 8)}...</span>
                          <ExternalLink size={11} />
                        </a>
                      ) : (
                        <span style={{ color: '#94a3b8' }}>—</span>
                      )}
                    </td>
                    <td style={{ color: '#64748b', fontSize: '0.8rem' }}>
                      {d.created_at ? new Date(d.created_at).toLocaleDateString() : '—'}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )
      ) : (
        withdrawals.length === 0 ? (
          <div style={{ textAlign: 'center', padding: '30px 10px', color: '#94a3b8', fontSize: '0.85rem' }}>
            No withdrawal records found yet.
          </div>
        ) : (
          <div className="dash-tx-table-wrapper">
            <table className="dash-tx-table">
              <thead>
                <tr>
                  <th>Request ID</th>
                  <th>Gross Amount</th>
                  <th>Service Charge</th>
                  <th>Net Payout</th>
                  <th>Status</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                {withdrawals.map((w) => (
                  <tr key={w.id}>
                    <td>
                      <strong style={{ fontFamily: 'monospace', color: '#9333ea' }}>{w.request_id}</strong>
                    </td>
                    <td>${Number(w.gross_amount).toFixed(2)} USD</td>
                    <td style={{ color: '#64748b' }}>${Number(w.service_charge).toFixed(2)}</td>
                    <td>
                      <strong style={{ color: '#b45309' }}>${Number(w.net_amount).toFixed(2)} USD</strong>
                    </td>
                    <td>{getStatusBadge(w.status)}</td>
                    <td style={{ color: '#64748b', fontSize: '0.8rem' }}>
                      {w.created_at ? new Date(w.created_at).toLocaleDateString() : '—'}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )
      )}
    </section>
  );
}

export default RecentTransactionsCard;
