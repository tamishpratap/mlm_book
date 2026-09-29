import { Link } from 'react-router-dom';
import {
  Megaphone,
  Eye,
  MousePointerClick,
  Percent,
  Clock,
  CheckCircle2,
  AlertCircle,
  ArrowRight,
  TrendingUp,
  Image as ImageIcon,
} from 'lucide-react';
import { getMediaUrl } from '../../../utils/assetHelper';

export function AdCampaignsOverview({ adCampaigns }) {
  const metrics = adCampaigns?.metrics || {};
  const recentList = adCampaigns?.recent_campaigns || [];

  const totalCampaigns = Number(metrics?.total_campaigns || 0);
  const activeCampaigns = Number(metrics?.active_campaigns || 0);
  const totalBudget = Number(metrics?.total_budget || 0);
  const totalSpent = Number(metrics?.total_spent || 0);
  const totalRemaining = Number(metrics?.total_remaining || 0);
  const totalImpressions = Number(metrics?.total_impressions || 0);
  const totalClicks = Number(metrics?.total_clicks || 0);
  const averageCtr = Number(metrics?.average_ctr || 0);

  const getStatusClass = (status) => {
    switch (status?.toLowerCase()) {
      case 'active':
        return 'dash-status--active';
      case 'approved':
        return 'dash-status--approved';
      case 'pending_review':
        return 'dash-status--pending_review';
      case 'paused':
        return 'dash-status--paused';
      case 'completed':
        return 'dash-status--completed';
      case 'cancelled':
        return 'dash-status--cancelled';
      default:
        return 'dash-status--paused';
    }
  };

  return (
    <section className="dash-wallet-section" aria-label="My Ad Campaigns Performance">
      <div className="dash-section-header">
        <div className="dash-section-title">
          <div className="dash-section-icon" style={{ backgroundColor: 'rgba(59, 130, 246, 0.1)', color: '#2563eb' }}>
            <Megaphone size={20} />
          </div>
          <div>
            <h2>Advertising Campaigns ({totalCampaigns})</h2>
            <p style={{ margin: 0, fontSize: '0.825rem', color: '#64748b' }}>
              Promoted posts delivery, impressions, click-through rates & ad funds
            </p>
          </div>
        </div>

        <Link
          to="/member/business-pages"
          className="dash-btn-outline"
          style={{ padding: '8px 14px', fontSize: '0.825rem' }}
        >
          <span>All Campaigns</span>
          <ArrowRight size={14} />
        </Link>
      </div>

      {/* 4 Metric Pills */}
      <div className="dash-campaigns-metrics-bar">
        <div className="dash-camp-pill">
          <div className="dash-camp-pill-icon" style={{ backgroundColor: '#dbeafe', color: '#2563eb' }}>
            <Megaphone size={16} />
          </div>
          <div>
            <div className="dash-camp-pill-val">{activeCampaigns}</div>
            <div className="dash-camp-pill-lbl">Active Ads</div>
          </div>
        </div>

        <div className="dash-camp-pill">
          <div className="dash-camp-pill-icon" style={{ backgroundColor: '#dcfce7', color: '#16a34a' }}>
            <TrendingUp size={16} />
          </div>
          <div>
            <div className="dash-camp-pill-val">
              ${totalBudget.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
            </div>
            <div className="dash-camp-pill-lbl">Total Budget Funded</div>
          </div>
        </div>

        <div className="dash-camp-pill">
          <div className="dash-camp-pill-icon" style={{ backgroundColor: '#fef3c7', color: '#d97706' }}>
            <Eye size={16} />
          </div>
          <div>
            <div className="dash-camp-pill-val">{totalImpressions.toLocaleString()}</div>
            <div className="dash-camp-pill-lbl">Impressions Delivered</div>
          </div>
        </div>

        <div className="dash-camp-pill">
          <div className="dash-camp-pill-icon" style={{ backgroundColor: '#f3e8ff', color: '#9333ea' }}>
            <MousePointerClick size={16} />
          </div>
          <div>
            <div className="dash-camp-pill-val">
              {totalClicks} <span style={{ fontSize: '0.75rem', fontWeight: 600, color: '#64748b' }}>({averageCtr}%)</span>
            </div>
            <div className="dash-camp-pill-lbl">Clicks & Avg CTR</div>
          </div>
        </div>
      </div>

      {/* Campaigns List or Empty State */}
      {recentList.length === 0 ? (
        <div className="dash-empty-biz-card" style={{ borderColor: '#bfdbfe', background: '#f8fafc' }}>
          <div
            style={{
              width: '54px',
              height: '54px',
              borderRadius: '16px',
              backgroundColor: '#eff6ff',
              color: '#2563eb',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
            }}
          >
            <Megaphone size={28} />
          </div>
          <h3>No Active Ad Campaigns Yet</h3>
          <p>
            Promote any post on your Business Page to drive platform-wide reach, member impressions, and customer interest.
          </p>
          <Link
            to="/member/business-pages"
            className="dash-btn-primary"
            style={{ background: '#2563eb' }}
          >
            <Megaphone size={16} />
            <span>Create Your First Ad</span>
          </Link>
        </div>
      ) : (
        <div className="dash-campaigns-list">
          {recentList.map((c) => {
            const mediaUrl = c.promoted_post?.media_path
              ? getMediaUrl(c.promoted_post.media_path)
              : null;
            const budgetNum = Number(c.budget || 0);
            const spentNum = Number(c.spent_amount || 0);
            const remainingNum = Number(c.remaining_amount || 0);
            const spentPct = budgetNum > 0 ? Math.min(100, Math.round((spentNum / budgetNum) * 100)) : 0;

            return (
              <div key={c.id} className="dash-campaign-item">
                <div className="dash-campaign-info-col">
                  {mediaUrl ? (
                    <img
                      src={mediaUrl}
                      alt="Ad Preview"
                      className="dash-campaign-thumb"
                      onError={(e) => {
                        e.currentTarget.style.display = 'none';
                      }}
                    />
                  ) : (
                    <div className="dash-campaign-thumb" style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#94a3b8' }}>
                      <ImageIcon size={22} />
                    </div>
                  )}

                  <div>
                    <h4 className="dash-campaign-title">{c.campaign_name}</h4>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' }}>
                      <span className={`dash-campaign-status-badge ${getStatusClass(c.status)}`}>
                        ● {c.status}
                      </span>
                      {c.created_at && (
                        <span style={{ fontSize: '0.725rem', color: '#64748b' }}>
                          Created {new Date(c.created_at).toLocaleDateString()}
                        </span>
                      )}
                    </div>
                  </div>
                </div>

                <div className="dash-campaign-budget-col">
                  <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.8rem', marginBottom: '5px' }}>
                    <span style={{ color: '#475569' }}>
                      Spent: <strong>${spentNum.toFixed(2)}</strong> / ${budgetNum.toFixed(2)} USD
                    </span>
                    <span style={{ fontWeight: 700, color: '#16a34a' }}>
                      ${remainingNum.toFixed(2)} rem.
                    </span>
                  </div>
                  <div style={{ height: '7px', width: '100%', backgroundColor: '#e2e8f0', borderRadius: '4px', overflow: 'hidden' }}>
                    <div
                      style={{
                        height: '100%',
                        width: `${spentPct}%`,
                        backgroundColor: '#2563eb',
                        borderRadius: '4px',
                        transition: 'width 0.3s ease',
                      }}
                    />
                  </div>
                </div>

                <div className="dash-campaign-stats-col">
                  <div style={{ textAlign: 'center' }}>
                    <span style={{ fontWeight: 800, color: '#1e293b', display: 'block' }}>{c.impressions_count || 0}</span>
                    <span style={{ fontSize: '0.7rem', color: '#64748b' }}>Views</span>
                  </div>
                  <div style={{ textAlign: 'center' }}>
                    <span style={{ fontWeight: 800, color: '#1e293b', display: 'block' }}>{c.clicks_count || 0}</span>
                    <span style={{ fontSize: '0.7rem', color: '#64748b' }}>Clicks</span>
                  </div>
                  <div style={{ textAlign: 'center' }}>
                    <span style={{ fontWeight: 800, color: '#2563eb', display: 'block' }}>{c.ctr || 0}%</span>
                    <span style={{ fontSize: '0.7rem', color: '#64748b' }}>CTR</span>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </section>
  );
}

export default AdCampaignsOverview;
