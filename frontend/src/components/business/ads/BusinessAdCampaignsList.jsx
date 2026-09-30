import { useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Megaphone,
  Plus,
  Search,
  AlertCircle,
  Eye,
  DollarSign,
  RefreshCw,
  MousePointerClick,
  Users,
  X,
  Wallet,
} from 'lucide-react';
import businessApi from '../../../api/businessApi';
import { formatAdAmount } from '../../../utils/adFormatters';
import { CreateAdCampaignModal } from './CreateAdCampaignModal';
import { AdCampaignDetailModal } from './AdCampaignDetailModal';
import { AddFundModal } from './AddFundModal';
import { AddFundsToCampaignModal } from './AddFundsToCampaignModal';
import { getMediaUrl } from '../../../utils/assetHelper';

export function BusinessAdCampaignsList({
  page,
  availablePosts = [],
  isOwner = false,
  isTeamAdmin = false,
}) {
  const navigate = useNavigate();
  const [campaigns, setCampaigns] = useState([]);
  const [metrics, setMetrics] = useState({
    total_campaigns: 0,
    active_campaigns: 0,
    pending_review: 0,
    total_budget: 0,
    total_spent: 0,
    total_remaining: 0,
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [viewMode, setViewMode] = useState('running'); // 'running' | 'closed'
  const [fetchedPosts, setFetchedPosts] = useState([]);
  const [hasPageContent, setHasPageContent] = useState(null);
  const [contentError, setContentError] = useState('');

  // Modals state
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showAddFundModal, setShowAddFundModal] = useState(false);
  const [selectedPostForAd, setSelectedPostForAd] = useState(null);
  const [detailCampaign, setDetailCampaign] = useState(null);
  const [detailInitialTab, setDetailInitialTab] = useState('overview');
  const [topUpCampaign, setTopUpCampaign] = useState(null);
  const [previewCampaign, setPreviewCampaign] = useState(null);

  const fetchCampaigns = useCallback(async () => {
    if (!page?.slug) return;
    setLoading(true);
    setError('');

    try {
      let finalStatus = statusFilter;
      if (!finalStatus) {
        if (viewMode === 'running') {
          finalStatus = 'draft,approved,active,paused';
        } else if (viewMode === 'pending') {
          finalStatus = 'pending_review';
        } else if (viewMode === 'rejected') {
          finalStatus = 'rejected';
        } else if (viewMode === 'stopped') {
          finalStatus = 'stopped';
        } else {
          finalStatus = 'completed,cancelled';
        }
      }

      const params = {
        page: currentPage,
        status: finalStatus,
        q: searchQuery || undefined,
      };

      const res = await businessApi.getAdCampaigns(page.slug, params);
      if (res?.campaigns) {
        setCampaigns(res.campaigns.data || []);
        setCurrentPage(res.campaigns.current_page || 1);
      }
      if (res?.metrics) {
        setMetrics(res.metrics);
      }
      if (res?.available_posts) {
        setFetchedPosts(res.available_posts);
      }
      if (typeof res?.has_content === 'boolean') {
        setHasPageContent(res.has_content);
      } else if (typeof res?.posts_count === 'number') {
        setHasPageContent(res.posts_count > 0);
      }
    } catch (err) {
      console.error('Failed to load ad campaigns:', err);
      setError(err.response?.data?.message || 'Failed to load advertising campaigns.');
    } finally {
      setLoading(false);
    }
  }, [page, currentPage, statusFilter, searchQuery, viewMode]);

  useEffect(() => {
    fetchCampaigns();
  }, [fetchCampaigns]);

  // When viewMode changes, reset status filter and page
  useEffect(() => {
    setStatusFilter('');
    setCurrentPage(1);
  }, [viewMode]);

  const effectivePosts = (fetchedPosts && fetchedPosts.length > 0) ? fetchedPosts : availablePosts;
  const hasContent = hasPageContent !== null
    ? hasPageContent
    : ((effectivePosts && effectivePosts.length > 0) || Boolean(page?.posts_count > 0));

  const handleOpenCreate = (post = null) => {
    setContentError('');
    if (!post && !hasContent) {
      setContentError('Please add at least one post or photo to your Business Page before running an ad campaign.');
    }
    setSelectedPostForAd(post);
    setShowCreateModal(true);
  };

  const handleCampaignCreated = (newCampaign) => {
    setViewMode('running');
    fetchCampaigns();
    if (newCampaign) {
      setDetailCampaign(newCampaign);
    }
  };

  const handleCampaignUpdated = () => {
    fetchCampaigns();
  };

  const getStatusBadge = (status, approvalStatus, isBudgetLow = false, isBudgetExhausted = false) => {
    if (status === 'rejected' || approvalStatus === 'rejected') {
      return { bg: '#fee2e2', color: '#b91c1c', label: 'Rejected' };
    }
    if (isBudgetExhausted || status === 'budget_exhausted') {
      return { bg: '#fee2e2', color: '#b91c1c', label: 'Budget Exhausted' };
    }
    if (isBudgetLow && (status === 'active' || status === 'approved')) {
      return { bg: '#fef3c7', color: '#b45309', label: 'Budget Low' };
    }
    switch (status) {
      case 'active':
        return { bg: '#dcfce7', color: '#15803d', label: 'Active' };
      case 'approved':
        return { bg: '#dbeafe', color: '#1d4ed8', label: 'Approved' };
      case 'pending_review':
        return { bg: '#fef3c7', color: '#b45309', label: 'Pending Review' };
      case 'paused':
        return { bg: '#ffedd5', color: '#c2410c', label: 'Paused' };
      case 'stopped':
        return { bg: '#fee2e2', color: '#dc2626', label: 'Stopped' };
      case 'completed':
        return { bg: '#f1f5f9', color: '#475569', label: 'Completed' };
      case 'cancelled':
        return { bg: '#fee2e2', color: '#b91c1c', label: 'Closed / Cancelled' };
      default:
        return { bg: '#f3f4f6', color: '#6b7280', label: 'Draft' };
    }
  };

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: '24px' }}>
      {/* Metrics Overview Cards */}
      <div
        style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fit, minmax(170px, 1fr))',
          gap: '14px',
        }}
      >
        <div className="card" style={{ padding: '18px', borderRadius: '16px', border: '1px solid #bbf7d0', backgroundColor: '#f0fdf4' }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <span style={{ fontSize: '12.5px', fontWeight: 700, color: '#166534' }}>Available Ad Funds</span>
            <div style={{ padding: '6px', borderRadius: '8px', backgroundColor: 'rgba(22, 163, 74, 0.15)', color: '#16a34a' }}>
              <Wallet size={15} />
            </div>
          </div>
          <div style={{ fontSize: '20px', fontWeight: 800, color: '#15803d', marginTop: '8px' }}>
            ${formatAdAmount(metrics.available_ad_funds !== undefined ? metrics.available_ad_funds : (metrics.member_ad_balance || 0))}
          </div>
          <div style={{ fontSize: '11.5px', color: '#166534', marginTop: '4px', fontWeight: 500 }}>Approved ready balance (USD)</div>
        </div>

        <div className="card" style={{ padding: '18px', borderRadius: '16px' }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <span style={{ fontSize: '12.5px', fontWeight: 600, color: 'var(--color-text-secondary, #64748b)' }}>Total Budget</span>
            <div style={{ padding: '6px', borderRadius: '8px', backgroundColor: 'rgba(79, 125, 243, 0.1)', color: '#4f7df3' }}>
              <DollarSign size={15} />
            </div>
          </div>
          <div style={{ fontSize: '20px', fontWeight: 800, color: 'var(--color-text-main, #1e293b)', marginTop: '8px' }}>
            ${formatAdAmount(metrics.total_budget || 0)}
          </div>
          <div style={{ fontSize: '11.5px', color: '#64748b', marginTop: '4px' }}>Allocated across campaigns</div>
        </div>

        <div className="card" style={{ padding: '18px', borderRadius: '16px' }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <span style={{ fontSize: '12.5px', fontWeight: 600, color: 'var(--color-text-secondary, #64748b)' }}>Campaign Remaining</span>
            <div style={{ padding: '6px', borderRadius: '8px', backgroundColor: 'rgba(30, 41, 59, 0.08)', color: '#334155' }}>
              <DollarSign size={15} />
            </div>
          </div>
          <div style={{ fontSize: '20px', fontWeight: 800, color: '#334155', marginTop: '8px' }}>
            ${formatAdAmount(metrics.total_remaining || 0)}
          </div>
          <div style={{ fontSize: '11.5px', color: '#64748b', marginTop: '4px' }}>Unspent campaign budget</div>
        </div>

        <div className="card" style={{ padding: '18px', borderRadius: '16px' }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <span style={{ fontSize: '12.5px', fontWeight: 600, color: 'var(--color-text-secondary, #64748b)' }}>Impressions</span>
            <div style={{ padding: '6px', borderRadius: '8px', backgroundColor: 'rgba(14, 165, 233, 0.1)', color: '#0ea5e9' }}>
              <Eye size={15} />
            </div>
          </div>
          <div style={{ fontSize: '20px', fontWeight: 800, color: '#0ea5e9', marginTop: '8px' }}>
            {Number(metrics.total_impressions || 0).toLocaleString()}
          </div>
          <div style={{ fontSize: '11.5px', color: '#64748b', marginTop: '4px' }}>Ad feed views</div>
        </div>

        <div className="card" style={{ padding: '18px', borderRadius: '16px' }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <span style={{ fontSize: '12.5px', fontWeight: 600, color: 'var(--color-text-secondary, #64748b)' }}>Clicks & CTR</span>
            <div style={{ padding: '6px', borderRadius: '8px', backgroundColor: 'rgba(99, 102, 241, 0.1)', color: '#6366f1' }}>
              <MousePointerClick size={15} />
            </div>
          </div>
          <div style={{ fontSize: '20px', fontWeight: 800, color: '#6366f1', marginTop: '8px' }}>
            {Number(metrics.total_clicks || 0).toLocaleString()}{' '}
            <span style={{ fontSize: '13px', fontWeight: 600, color: '#64748b' }}>
              ({metrics.average_ctr !== undefined ? Number(metrics.average_ctr).toFixed(2) : '0.00'}%)
            </span>
          </div>
          <div style={{ fontSize: '11.5px', color: '#64748b', marginTop: '4px' }}>Real clicks & CTR</div>
        </div>

        <div className="card" style={{ padding: '18px', borderRadius: '16px' }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <span style={{ fontSize: '12.5px', fontWeight: 600, color: 'var(--color-text-secondary, #64748b)' }}>Active / Pending</span>
            <div style={{ padding: '6px', borderRadius: '8px', backgroundColor: 'rgba(217, 119, 6, 0.1)', color: '#d97706' }}>
              <Megaphone size={15} />
            </div>
          </div>
          <div style={{ fontSize: '20px', fontWeight: 800, color: '#d97706', marginTop: '8px' }}>
            {(() => {
              let activeCount = Number(metrics.active_campaigns ?? 0);
              const approvedCount = Number(metrics.approved_campaigns ?? 0);
              if (activeCount === 0 && approvedCount > 0) {
                activeCount = approvedCount;
              }
              if (activeCount === 0 && Array.isArray(campaigns) && campaigns.length > 0) {
                const runningInList = campaigns.filter((c) => c.status === 'active' || c.status === 'approved').length;
                if (runningInList > 0) {
                  activeCount = runningInList;
                }
              }
              const pendingCount = Number(metrics.pending_review ?? 0);
              return `${activeCount} / ${pendingCount}`;
            })()}
          </div>
          <div style={{ fontSize: '11.5px', color: '#64748b', marginTop: '4px' }}>Active vs in-review ads</div>
        </div>

        <div className="card" style={{ padding: '18px', borderRadius: '16px' }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <span style={{ fontSize: '12.5px', fontWeight: 600, color: 'var(--color-text-secondary, #64748b)' }}>Rejected</span>
            <div style={{ padding: '6px', borderRadius: '8px', backgroundColor: 'rgba(239, 68, 68, 0.1)', color: '#ef4444' }}>
              <AlertCircle size={15} />
            </div>
          </div>
          <div style={{ fontSize: '20px', fontWeight: 800, color: '#ef4444', marginTop: '8px' }}>
            {Number(metrics.rejected_campaigns ?? 0)}
          </div>
          <div style={{ fontSize: '11.5px', color: '#64748b', marginTop: '4px' }}>Rejected campaigns</div>
        </div>

        <div className="card" style={{ padding: '18px', borderRadius: '16px' }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <span style={{ fontSize: '12.5px', fontWeight: 600, color: 'var(--color-text-secondary, #64748b)' }}>Stopped / Paused</span>
            <div style={{ padding: '6px', borderRadius: '8px', backgroundColor: 'rgba(100, 116, 139, 0.1)', color: '#64748b' }}>
              <Megaphone size={15} />
            </div>
          </div>
          <div style={{ fontSize: '20px', fontWeight: 800, color: '#64748b', marginTop: '8px' }}>
            {Number(metrics.stopped_campaigns ?? 0)} / {Number(metrics.paused_campaigns ?? 0)}
          </div>
          <div style={{ fontSize: '11.5px', color: '#64748b', marginTop: '4px' }}>Stopped vs paused ads</div>
        </div>

        <div className="card" style={{ padding: '18px', borderRadius: '16px' }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <span style={{ fontSize: '12.5px', fontWeight: 600, color: 'var(--color-text-secondary, #64748b)' }}>Closed</span>
            <div style={{ padding: '6px', borderRadius: '8px', backgroundColor: 'rgba(71, 85, 105, 0.1)', color: '#475569' }}>
              <Megaphone size={15} />
            </div>
          </div>
          <div style={{ fontSize: '20px', fontWeight: 800, color: '#475569', marginTop: '8px' }}>
            {Number(metrics.closed_campaigns ?? 0)}
          </div>
          <div style={{ fontSize: '11.5px', color: '#64748b', marginTop: '4px' }}>Completed or cancelled</div>
        </div>
      </div>

      {/* Control Header & Filters */}
      <div className="card" style={{ padding: '18px 20px', borderRadius: '16px' }}>
        <div
          style={{
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            flexWrap: 'wrap',
            gap: '14px',
            marginBottom: '16px',
            borderBottom: '1px solid #e2e8f0',
            paddingBottom: '16px',
          }}
        >
          {/* View Mode Toggle */}
          <div
            style={{
              display: 'flex',
              alignItems: 'center',
              backgroundColor: '#f1f5f9',
              borderRadius: '10px',
              padding: '4px',
              flexWrap: 'wrap',
              gap: '4px',
            }}
          >
            {[
              { id: 'running', label: 'Running Ads' },
              { id: 'pending', label: 'Pending Ads' },
              { id: 'rejected', label: 'Rejected Ads' },
              { id: 'stopped', label: 'Stop Ads' },
              { id: 'closed', label: 'Closed Ads' }
            ].map((tab) => (
              <button
                key={tab.id}
                type="button"
                onClick={() => setViewMode(tab.id)}
                style={{
                  padding: '8px 16px',
                  borderRadius: '8px',
                  fontSize: '13px',
                  fontWeight: 600,
                  backgroundColor: viewMode === tab.id ? '#ffffff' : 'transparent',
                  color: viewMode === tab.id ? '#0f172a' : '#64748b',
                  border: 'none',
                  boxShadow: viewMode === tab.id ? '0 2px 4px rgba(0,0,0,0.05)' : 'none',
                  cursor: 'pointer',
                  transition: 'all 0.2s',
                }}
              >
                {tab.label}
              </button>
            ))}
          </div>

          {/* Right: Actions */}
          {(isOwner || isTeamAdmin) && (
            <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
              <button
                type="button"
                className="btn"
                onClick={() => navigate('/member/deposit')}
                style={{
                  padding: '9px 16px',
                  borderRadius: '10px',
                  fontSize: '13px',
                  fontWeight: 700,
                  backgroundColor: '#f0fdf4',
                  color: '#16a34a',
                  border: '1px solid #bbf7d0',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '6px',
                  cursor: 'pointer',
                  transition: 'all 0.2s',
                }}
              >
                <Wallet size={15} />
                <span>Add Fund</span>
              </button>

              <button
                type="button"
                className="btn btn-primary"
                onClick={() => handleOpenCreate(null)}
                title={!hasContent ? 'Please add at least one post or photo to your Business Page before running an ad campaign.' : undefined}
                style={{
                  padding: '9px 20px',
                  borderRadius: '10px',
                  fontSize: '13.5px',
                  fontWeight: 700,
                  backgroundColor: !hasContent ? '#94a3b8' : '#4f7df3',
                  color: '#ffffff',
                  border: 'none',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '8px',
                  cursor: 'pointer',
                  boxShadow: !hasContent ? 'none' : '0 4px 12px rgba(79, 125, 243, 0.25)',
                }}
              >
                <Plus size={16} />
                <span>Create Ad Campaign</span>
              </button>
            </div>
          )}
        </div>

        <div
          style={{
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            flexWrap: 'wrap',
            gap: '14px',
          }}
        >
          {/* Left: Search & Status Filter */}
          <div style={{ display: 'flex', alignItems: 'center', gap: '12px', flex: 1, minWidth: '260px' }}>
            <div style={{ position: 'relative', flex: 1, maxWidth: '320px' }}>
              <Search
                size={16}
                style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: '#94a3b8' }}
              />
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Search campaigns..."
                className="biz-search-input"
                style={{ width: '100%', paddingLeft: '36px', borderRadius: '10px' }}
              />
            </div>



            <button
              type="button"
              className="mini-button"
              onClick={fetchCampaigns}
              title="Refresh campaigns"
              style={{ borderRadius: '10px', padding: '9px' }}
            >
              <RefreshCw size={15} />
            </button>
          </div>
        </div>
      </div>

      {/* Zero Content Warning Banner */}
      {!loading && !hasContent && (
        <div
          style={{
            padding: '14px 18px',
            borderRadius: '14px',
            backgroundColor: '#fffbeb',
            border: '1px solid #fde68a',
            color: '#92400e',
            display: 'flex',
            alignItems: 'center',
            gap: '12px',
          }}
        >
          <AlertCircle size={20} style={{ color: '#d97706', flexShrink: 0 }} />
          <div style={{ fontSize: '13.5px', fontWeight: 500, lineHeight: 1.4 }}>
            Please add at least one post or photo to your Business Page before running an ad campaign.
          </div>
        </div>
      )}

      {contentError && (
        <div
          style={{
            padding: '14px 18px',
            borderRadius: '14px',
            backgroundColor: '#fef2f2',
            border: '1px solid #fecaca',
            color: '#b91c1c',
            display: 'flex',
            alignItems: 'center',
            gap: '10px',
          }}
        >
          <AlertCircle size={18} style={{ flexShrink: 0 }} />
          <span style={{ fontSize: '13.5px' }}>{contentError}</span>
        </div>
      )}

      {/* Campaigns Listing */}
      {error && (
        <div
          style={{
            padding: '16px',
            borderRadius: '14px',
            backgroundColor: '#fef2f2',
            border: '1px solid #fecaca',
            color: '#b91c1c',
            display: 'flex',
            alignItems: 'center',
            gap: '10px',
          }}
        >
          <AlertCircle size={18} />
          <span>{error}</span>
        </div>
      )}

      {loading ? (
        <div style={{ padding: '60px 20px', textAlign: 'center', color: '#94a3b8' }}>
          <RefreshCw size={28} className="animate-spin" style={{ margin: '0 auto 12px' }} />
          <p style={{ margin: 0, fontSize: '14px' }}>Loading ad campaigns...</p>
        </div>
      ) : campaigns.length === 0 ? (
        <div className="card" style={{ padding: '60px 20px', textAlign: 'center', borderRadius: '18px' }}>
          <div
            style={{
              width: '64px',
              height: '64px',
              borderRadius: '20px',
              backgroundColor: 'rgba(79, 125, 243, 0.1)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              color: '#4f7df3',
              margin: '0 auto 16px',
            }}
          >
            <Megaphone size={30} />
          </div>
          <h3 style={{ fontSize: '17px', fontWeight: 800, margin: '0 0 6px 0', color: 'var(--color-text-main, #1e293b)' }}>
            No Ad Campaigns Yet
          </h3>
          <p style={{ color: 'var(--color-text-secondary, #64748b)', margin: '0 auto 20px', maxWidth: '420px', fontSize: '13.5px', lineHeight: 1.5 }}>
            Promote your business posts to reach thousands of platform members across MLM_Book feeds.
          </p>
          {(isOwner || isTeamAdmin) && (
            <button
              type="button"
              className="btn btn-primary"
              onClick={() => handleOpenCreate(null)}
              title={!hasContent ? 'Please add at least one post or photo to your Business Page before running an ad campaign.' : undefined}
              style={{
                padding: '10px 24px',
                borderRadius: '10px',
                fontWeight: 700,
                fontSize: '14px',
                backgroundColor: !hasContent ? '#94a3b8' : '#4f7df3',
                color: '#ffffff',
                border: 'none',
                cursor: 'pointer',
              }}
            >
              Start First Campaign
            </button>
          )}
        </div>
      ) : (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '14px' }}>
          {campaigns.map((camp) => {
            const isLow = Boolean(camp.is_budget_low);
            const isExhausted = Boolean(camp.is_budget_exhausted || camp.status === 'budget_exhausted');
            const badge = getStatusBadge(camp.status, camp.approval_status, isLow, isExhausted);
            
            const origBudget = parseFloat(camp.budget) || 0;
            const additionalFunding = parseFloat(camp.additional_funding) || 0;
            const totalFunded = parseFloat(camp.total_funded) || (origBudget + additionalFunding);
            const spentVal = parseFloat(camp.spent_amount) || 0;
            const remainVal = Math.max(0, parseFloat((totalFunded - spentVal).toFixed(4)));

            return (
              <div
                key={camp.id}
                className="card"
                style={{
                  padding: '20px',
                  borderRadius: '16px',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  flexWrap: 'wrap',
                  gap: '16px',
                  border: isExhausted ? '1px solid #fecaca' : isLow ? '1px solid #fde68a' : '1px solid var(--color-border, #e2e8f0)',
                  backgroundColor: isExhausted ? '#fffafa' : isLow ? '#fffdfa' : '#ffffff',
                  transition: 'transform 0.15s ease, box-shadow 0.15s ease',
                }}
              >
                {/* Left: Info */}
                <div style={{ display: 'flex', alignItems: 'center', gap: '16px', flex: 1, minWidth: '260px' }}>
                  <div
                    style={{
                      width: '48px',
                      height: '48px',
                      borderRadius: '14px',
                      backgroundColor: isExhausted ? 'rgba(239, 68, 68, 0.1)' : isLow ? 'rgba(245, 158, 11, 0.1)' : 'rgba(79, 125, 243, 0.1)',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      color: isExhausted ? '#ef4444' : isLow ? '#f59e0b' : '#4f7df3',
                      flexShrink: 0,
                    }}
                  >
                    <Megaphone size={22} />
                  </div>

                  <div>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '10px', flexWrap: 'wrap' }}>
                      <h4 style={{ margin: 0, fontSize: '15px', fontWeight: 800, color: 'var(--color-text-main, #1e293b)' }}>
                        {camp.campaign_name}
                      </h4>
                      <span
                        style={{
                          padding: '2px 8px',
                          borderRadius: '12px',
                          backgroundColor: badge.bg,
                          color: badge.color,
                          fontWeight: 700,
                          fontSize: '11px',
                        }}
                      >
                        {badge.label}
                      </span>
                    </div>

                    <div style={{ fontSize: '12.5px', color: 'var(--color-text-secondary, #64748b)', marginTop: '4px', display: 'flex', alignItems: 'center', gap: '12px', flexWrap: 'wrap' }}>
                      <span>ID: <code>{camp.campaign_id || camp.id}</code></span>
                      {camp.post && <span>• Promoted Post #{camp.post.id}</span>}
                      <span>
                        • Start:{' '}
                        {camp.start_at ? new Date(camp.start_at).toLocaleDateString() : 'Immediate'}
                      </span>
                    </div>

                    {camp.rejection_reason && (camp.status === 'rejected' || camp.approval_status === 'rejected') && (
                      <div style={{ marginTop: '6px', fontSize: '12px', color: '#b91c1c', fontWeight: 600 }}>
                        Rejection reason: {camp.rejection_reason}
                      </div>
                    )}
                  </div>
                </div>

                {/* Middle: Metrics & Financials */}
                <div style={{ display: 'flex', alignItems: 'center', gap: '18px', textAlign: 'right', flexWrap: 'wrap' }}>
                  <div>
                    <div style={{ fontSize: '11px', color: '#64748b', fontWeight: 600 }}>Original / Funded</div>
                    <div style={{ fontSize: '13.5px', fontWeight: 800, color: '#4f7df3' }}>
                      ${formatAdAmount(origBudget)}{additionalFunding > 0 && <span style={{ fontSize: '11px', color: '#16a34a' }}> (+${formatAdAmount(additionalFunding)})</span>}
                    </div>
                  </div>

                  <div>
                    <div style={{ fontSize: '11px', color: '#64748b', fontWeight: 600 }}>Total Funded</div>
                    <div style={{ fontSize: '13.5px', fontWeight: 800, color: '#1e293b' }}>
                      ${formatAdAmount(totalFunded)}
                    </div>
                  </div>

                  <div>
                    <div style={{ fontSize: '11px', color: '#64748b', fontWeight: 600 }}>Rewards Paid</div>
                    <div style={{ fontSize: '13.5px', fontWeight: 800, color: '#dc2626' }}>
                      ${formatAdAmount(spentVal)}
                    </div>
                  </div>

                  <div>
                    <div style={{ fontSize: '11px', color: '#64748b', fontWeight: 600 }}>Remaining</div>
                    <div
                      style={{
                        fontSize: '13.5px',
                        fontWeight: 800,
                        color: isExhausted ? '#b91c1c' : isLow ? '#b45309' : '#15803d',
                      }}
                    >
                      ${formatAdAmount(remainVal)}
                    </div>
                  </div>
                </div>

                {/* Right: Actions */}
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  {(isOwner || isTeamAdmin) && (
                    <button
                      type="button"
                      className="btn"
                      onClick={() => setTopUpCampaign(camp)}
                      style={{
                        padding: '7px 12px',
                        borderRadius: '8px',
                        fontSize: '12.5px',
                        fontWeight: 700,
                        backgroundColor: isExhausted ? '#fee2e2' : isLow ? '#fef3c7' : '#f0fdf4',
                        color: isExhausted ? '#b91c1c' : isLow ? '#b45309' : '#16a34a',
                        border: isExhausted ? '1px solid #fecaca' : isLow ? '1px solid #fde68a' : '1px solid #bbf7d0',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '6px',
                        cursor: 'pointer',
                      }}
                      title="Add funds to this campaign"
                    >
                      <Wallet size={14} />
                      <span>Add Funds</span>
                    </button>
                  )}

                  <button
                    type="button"
                    className="btn"
                    onClick={() => {
                      navigate(`/member/business-pages/${page.slug || page.id}/ads/${camp.campaign_id || camp.id}/people-engaged`);
                    }}
                    style={{
                      padding: '7px 14px',
                      borderRadius: '8px',
                      fontSize: '12.5px',
                      fontWeight: 700,
                      backgroundColor: '#eff6ff',
                      color: '#2563eb',
                      border: '1px solid #bfdbfe',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '6px',
                      cursor: 'pointer',
                      transition: 'all 0.15s ease',
                    }}
                    title="Open dedicated campaign People Engaged full page"
                  >
                    <Users size={14} />
                    <span>People Engaged</span>
                  </button>

                  <button
                    type="button"
                    className="btn btn-secondary"
                    onClick={() => setPreviewCampaign(camp)}
                    disabled={!camp.post || (!camp.post.media_urls?.length && !camp.post.media_path)}
                    style={{
                      padding: '7px 14px',
                      borderRadius: '8px',
                      fontSize: '12.5px',
                      fontWeight: 600,
                      display: 'flex',
                      alignItems: 'center',
                      gap: '6px',
                      opacity: (!camp.post || (!camp.post.media_urls?.length && !camp.post.media_path)) ? 0.5 : 1,
                    }}
                    title="View post media"
                  >
                    <Eye size={14} />
                    <span>View</span>
                  </button>

                  <button
                    type="button"
                    className="btn btn-secondary"
                    onClick={() => {
                      setDetailInitialTab('overview');
                      setDetailCampaign(camp);
                    }}
                    style={{
                      padding: '7px 14px',
                      borderRadius: '8px',
                      fontSize: '12.5px',
                      fontWeight: 600,
                      display: 'flex',
                      alignItems: 'center',
                      gap: '6px',
                    }}
                  >
                    <Eye size={14} />
                    <span>Details</span>
                  </button>
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* Modals */}
      {showCreateModal && (
        <CreateAdCampaignModal
          page={page}
          initialPost={selectedPostForAd}
          availablePosts={effectivePosts}
          hasPageContent={hasContent}
          availableAdFunds={Number(metrics.available_ad_funds !== undefined ? metrics.available_ad_funds : (metrics.member_ad_balance || 0))}
          platformFeePercent={Number(metrics.campaign_platform_fee_percent ?? 0)}
          onAddFundClick={() => {
            setShowCreateModal(false);
            navigate('/member/deposit');
          }}
          onOpenExistingCampaign={(existing) => {
            setShowCreateModal(false);
            setTopUpCampaign(existing);
          }}
          onClose={() => {
            setShowCreateModal(false);
            setSelectedPostForAd(null);
          }}
          onCampaignCreated={handleCampaignCreated}
        />
      )}

      {detailCampaign && (
        <AdCampaignDetailModal
          page={page}
          campaign={detailCampaign}
          isOwner={isOwner || isTeamAdmin}
          initialTab={detailInitialTab}
          onClose={() => setDetailCampaign(null)}
          onCampaignUpdated={handleCampaignUpdated}
          onOpenTopUp={(c) => setTopUpCampaign(c)}
        />
      )}

      {topUpCampaign && (
        <AddFundsToCampaignModal
          page={page}
          campaign={topUpCampaign}
          availableAdFunds={Number(metrics.available_ad_funds !== undefined ? metrics.available_ad_funds : (metrics.member_ad_balance || 0))}
          platformFeePercent={Number(metrics.campaign_platform_fee_percent ?? 0)}
          onClose={() => setTopUpCampaign(null)}
          onCampaignUpdated={() => {
            setTopUpCampaign(null);
            fetchCampaigns();
          }}
          onOpenDepositModal={() => {
            setTopUpCampaign(null);
            navigate('/member/deposit');
          }}
        />
      )}

      {showAddFundModal && (
        <AddFundModal
          page={page}
          isOpen={showAddFundModal}
          onClose={() => setShowAddFundModal(false)}
          onDepositSubmitted={() => {
            fetchCampaigns();
          }}
        />
      )}

      {/* Preview Media Modal */}
      {previewCampaign && (
        <div
          style={{
            position: 'fixed',
            top: 0,
            left: 0,
            width: '100vw',
            height: '100vh',
            backgroundColor: 'rgba(15, 23, 42, 0.75)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            zIndex: 9999,
            padding: '20px',
          }}
          onClick={() => setPreviewCampaign(null)}
        >
          <div
            style={{
              backgroundColor: '#ffffff',
              borderRadius: '16px',
              padding: '24px',
              maxWidth: '600px',
              width: '100%',
              boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.25)',
              position: 'relative',
              maxHeight: '90vh',
              overflowY: 'auto',
            }}
            onClick={(e) => e.stopPropagation()}
          >
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
              <h3 style={{ margin: 0, fontSize: '18px', fontWeight: 800, color: '#1e293b' }}>
                Ad Preview: {previewCampaign.campaign_name}
              </h3>
              <button
                type="button"
                className="mini-button"
                onClick={() => setPreviewCampaign(null)}
                style={{ padding: '6px', borderRadius: '50%' }}
              >
                <X size={18} />
              </button>
            </div>
            
            <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
              {previewCampaign.post?.body && (
                <p style={{ margin: 0, fontSize: '14px', color: '#334155', lineHeight: 1.5 }}>
                  {previewCampaign.post.body}
                </p>
              )}
              {previewCampaign.post?.media_urls && previewCampaign.post.media_urls.length > 0 ? (
                <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
                  {previewCampaign.post.media_urls.map((url, idx) => {
                    const isVid = previewCampaign.post.media_type === 'video' || /\.(mp4|webm|mov|ogg)$/i.test(url);
                    return isVid ? (
                      <video
                        key={idx}
                        src={getMediaUrl(url)}
                        controls
                        style={{
                          width: '100%',
                          borderRadius: '12px',
                          maxHeight: '400px',
                          backgroundColor: '#000',
                          border: '1px solid #e2e8f0',
                        }}
                      />
                    ) : (
                      <img
                        key={idx}
                        src={getMediaUrl(url)}
                        alt="Ad Media"
                        style={{
                          width: '100%',
                          borderRadius: '12px',
                          objectFit: 'contain',
                          maxHeight: '400px',
                          backgroundColor: '#f8fafc',
                          border: '1px solid #e2e8f0',
                        }}
                      />
                    );
                  })}
                </div>
              ) : previewCampaign.post?.media_path ? (
                (() => {
                  const isVid = previewCampaign.post.media_type === 'video' || /\.(mp4|webm|mov|ogg)$/i.test(previewCampaign.post.media_path);
                  return isVid ? (
                    <video
                      src={getMediaUrl(previewCampaign.post.media_path)}
                      controls
                      style={{
                        width: '100%',
                        borderRadius: '12px',
                        maxHeight: '400px',
                        backgroundColor: '#000',
                        border: '1px solid #e2e8f0',
                      }}
                    />
                  ) : (
                    <img
                      src={getMediaUrl(previewCampaign.post.media_path)}
                      alt="Ad Media"
                      style={{
                        width: '100%',
                        borderRadius: '12px',
                        objectFit: 'contain',
                        maxHeight: '400px',
                        backgroundColor: '#f8fafc',
                        border: '1px solid #e2e8f0',
                      }}
                    />
                  );
                })()
              ) : (
                <div style={{ padding: '30px', textAlign: 'center', color: '#64748b', backgroundColor: '#f1f5f9', borderRadius: '12px' }}>
                  No media available for this ad.
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default BusinessAdCampaignsList;
