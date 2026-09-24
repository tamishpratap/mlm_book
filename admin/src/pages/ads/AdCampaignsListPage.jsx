import { useState, useEffect, useCallback } from 'react';
import { useSearchParams } from 'react-router-dom';
import {
  Megaphone,
  Search,
  CheckCircle,
  XCircle,
  PauseCircle,
  PlayCircle,
  StopCircle,
  Eye,
  AlertTriangle,
  Calendar,
  DollarSign,
  RefreshCw,
  Clock,
  TrendingUp,
  BarChart2,
  Building,
  User,
  ExternalLink,
  AlertCircle,
  Image as ImageIcon,
  Video,
} from 'lucide-react';
import { Button } from 'primereact/button';
import { InputText } from 'primereact/inputtext';
import { Dropdown } from 'primereact/dropdown';
import { Paginator } from 'primereact/paginator';
import { Dialog } from 'primereact/dialog';
import { InputTextarea } from 'primereact/inputtextarea';
import { AdminSearchInput } from '../../components/common/AdminSearchInput';
import { PageHeader } from '../../components/common/PageHeader';
import { StatusBadge } from '../../components/common/StatusBadge';
import { EmptyState } from '../../components/common/EmptyState';
import { ErrorState } from '../../components/common/ErrorState';
import { LoadingSpinner } from '../../components/common/LoadingSpinner';
import { useToast } from '../../hooks/useToast';
import { adCampaignsApi } from '../../api';

const STATUS_OPTIONS = [
  { label: 'All Statuses', value: '' },
  { label: 'Pending Review', value: 'pending_review' },
  { label: 'Approved', value: 'approved' },
  { label: 'Active', value: 'active' },
  { label: 'Paused', value: 'paused' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Completed', value: 'completed' },
  { label: 'Stopped', value: 'stopped' },
  { label: 'Draft', value: 'draft' },
];

const APPROVAL_OPTIONS = [
  { label: 'All Approvals', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' },
];

/**
 * Resolves the absolute or relative media URL for a promoted post safely.
 * Prioritizes media_url, then media_path fallback, normalizes paths, strips /storage/ prefix,
 * and handles protocol upgrades when running under HTTPS.
 */
function getPromotedMediaUrl(post) {
  if (!post) return null;
  // Prioritize media_url, with safe fallback to media_path, image, or image_url
  const raw = post.media_url || post.media_path || post.image || post.image_url;
  if (!raw || typeof raw !== 'string') return null;

  const trimmed = raw.trim();
  if (!trimmed) return null;

  // Fully qualified URL (http:// or https://)
  if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
    // Protocol upgrade: If page is loaded over HTTPS, upgrade matching-origin HTTP URLs to HTTPS to prevent mixed-content blocks
    if (typeof window !== 'undefined' && window.location.protocol === 'https:' && trimmed.startsWith('http://')) {
      try {
        const parsed = new URL(trimmed);
        if (parsed.hostname === window.location.hostname) {
          return trimmed.replace('http://', 'https://');
        }
      } catch {
        // preserve trimmed
      }
    }
    return trimmed;
  }

  // Ensure leading slash for relative path
  let clean = trimmed.startsWith('/') ? trimmed : `/${trimmed}`;

  // If erroneously prefixed with /storage/uploads/, normalize to /uploads/
  if (clean.startsWith('/storage/uploads/')) {
    clean = clean.replace('/storage/uploads/', '/uploads/');
  }

  // If backend provided relative path and VITE_API_BASE_URL has an origin
  const apiBase = import.meta.env.VITE_API_BASE_URL || '';
  if (apiBase.startsWith('http://') || apiBase.startsWith('https://')) {
    try {
      const origin = new URL(apiBase).origin;
      return `${origin}${clean}`;
    } catch {
      // fallback to clean relative path
    }
  }

  return clean;
}

function isVideoMedia(post, url) {
  if (post?.media_type) {
    const type = post.media_type.toLowerCase();
    if (type === 'video' || type.startsWith('video/')) return true;
    if (type === 'image' || type.startsWith('image/')) return false;
  }
  if (url) {
    const cleanUrl = url.split('?')[0].toLowerCase();
    if (/\.(mp4|webm|ogg|mov|m4v|mkv)$/i.test(cleanUrl)) return true;
  }
  return false;
}

function PromotedMediaPreview({ post }) {
  const [hasError, setHasError] = useState(false);
  const [isLoading, setIsLoading] = useState(true);

  if (!post) return null;

  const mediaUrl = getPromotedMediaUrl(post);

  // CASE 3 — NO MEDIA: If post legitimately has no media attached
  if (!mediaUrl && !post.media_path) {
    return (
      <div className="p-3 bg-slate-100 rounded-lg text-xs text-slate-500 italic">
        This promoted post has no attached media.
      </div>
    );
  }

  const isVideo = isVideoMedia(post, mediaUrl);

  const handleMediaError = () => {
    setIsLoading(false);
    setHasError(true);
    console.error('[AdCampaigns] Failed to load promoted media URL:', mediaUrl, 'for post:', post);
  };

  // CASE 4 — LOAD FAILURE: If media fails to load, render clean error state
  if (hasError) {
    return (
      <div className="p-4 bg-slate-100 border border-slate-200 rounded-lg text-center space-y-2">
        <div className="flex items-center justify-center gap-2 text-slate-600 text-xs font-semibold">
          <AlertCircle className="w-4 h-4 text-amber-500 shrink-0" />
          <span>Unable to load promoted media.</span>
        </div>
        <p className="text-[11px] text-slate-500">
          Promoted media is no longer available or could not be loaded.
        </p>
        {mediaUrl && (
          <div className="pt-1">
            <a
              href={mediaUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-700 hover:underline"
            >
              <span>Open media file in new tab</span>
              <ExternalLink className="w-3 h-3" />
            </a>
          </div>
        )}
      </div>
    );
  }

  // CASE 1 & CASE 2 — IMAGE / VIDEO RENDERER
  return (
    <div className="relative rounded-lg overflow-hidden bg-slate-50 border border-slate-200 flex items-center justify-center min-h-[140px] max-h-[420px] p-2">
      {isVideo ? (
        <video
          src={mediaUrl}
          controls
          preload="metadata"
          className="max-h-[400px] w-full object-contain rounded-lg shadow-sm"
          onError={handleMediaError}
        >
          Your browser does not support the video tag.
        </video>
      ) : (
        <>
          {isLoading && (
            <div className="absolute inset-0 flex items-center justify-center bg-slate-100/70 z-10">
              <RefreshCw className="w-5 h-5 text-slate-400 animate-spin" />
            </div>
          )}
          <img
            src={mediaUrl}
            alt={post.body ? post.body.slice(0, 40) : 'Promoted content'}
            className={`max-h-[400px] w-full object-contain rounded-lg transition-opacity duration-200 ${
              isLoading ? 'opacity-0' : 'opacity-100'
            }`}
            onLoad={() => setIsLoading(false)}
            onError={handleMediaError}
          />
        </>
      )}
    </div>
  );
}

export function AdCampaignsListPage() {
  const { showSuccess, showError } = useToast();
  const [searchParams, setSearchParams] = useSearchParams();

  const [campaigns, setCampaigns] = useState([]);
  const [metrics, setMetrics] = useState({
    total_campaigns: 0,
    pending_review: 0,
    approved: 0,
    active: 0,
    paused: 0,
    rejected: 0,
    completed: 0,
    total_budget: 0,
    total_spent: 0,
    total_remaining: 0,
    total_impressions: 0,
    total_clicks: 0,
    average_ctr: 0,
  });

  const [search, setSearch] = useState(searchParams.get('q') || '');
  const [status, setStatus] = useState(searchParams.get('status') || '');
  const [approvalStatus, setApprovalStatus] = useState(searchParams.get('approval_status') || '');
  const [pagination, setPagination] = useState({ page: 1, perPage: 15, total: 0 });
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [error, setError] = useState(null);

  // Synchronize internal filter states when URL query parameters change (e.g. sidebar navigation)
  useEffect(() => {
    setSearch(searchParams.get('q') || '');
    setStatus(searchParams.get('status') || '');
    setApprovalStatus(searchParams.get('approval_status') || '');
    setPagination((prev) => ({ ...prev, page: 1 }));
  }, [searchParams]);

  // Detail Modal State
  const [detailCampaign, setDetailCampaign] = useState(null);
  const [showDetailModal, setShowDetailModal] = useState(false);

  // Reject Modal State
  const [showRejectModal, setShowRejectModal] = useState(false);
  const [rejectionTarget, setRejectionTarget] = useState(null);
  const [rejectionReason, setRejectionReason] = useState('');

  const handleCloseDetail = () => {
    setShowDetailModal(false);
    setDetailCampaign(null);
  };

  const handleCloseReject = () => {
    setShowRejectModal(false);
    setRejectionTarget(null);
    setRejectionReason('');
  };

  // Component unmount cleanup to guarantee no orphan PrimeReact masks or body scroll locks remain
  useEffect(() => {
    return () => {
      document.body.classList.remove('p-overflow-hidden');
      const orphanMasks = document.querySelectorAll('.p-dialog-mask, .p-component-overlay');
      orphanMasks.forEach((el) => {
        if (!el.querySelector('.p-dialog:not([style*="display: none"])')) {
          el.remove();
        }
      });
    };
  }, []);

  const fetchCampaigns = useCallback((page = 1) => {
    setLoading(true);
    setError(null);
    const params = {
      page,
      per_page: pagination.perPage || 15,
      q: search || undefined,
      status: status || undefined,
      approval_status: approvalStatus || undefined,
    };

    adCampaignsApi
      .getCampaigns(params)
      .then((res) => {
        if (res?.campaigns) {
          setCampaigns(res.campaigns.data || []);
          setPagination({
            page: res.campaigns.current_page || 1,
            perPage: res.campaigns.per_page || 15,
            total: res.campaigns.total || 0,
          });
        }
        if (res?.metrics) {
          setMetrics(res.metrics);
        }
      })
      .catch((err) => {
        console.error('Failed to load campaigns:', err);
        setError(err.message || 'Failed to load ad campaigns.');
      })
      .finally(() => {
        setLoading(false);
      });
  }, [search, status, approvalStatus, pagination.perPage]);

  useEffect(() => {
    fetchCampaigns(pagination.page);
  }, [fetchCampaigns, pagination.page]);

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    setPagination((prev) => ({ ...prev, page: 1 }));
    fetchCampaigns(1);
  };

  const handleApprove = async (campaign) => {
    setActionLoading(true);
    try {
      const res = await adCampaignsApi.approveCampaign(campaign.id);
      showSuccess('Ad campaign approved successfully.');
      fetchCampaigns(pagination.page);
      if (detailCampaign && detailCampaign.id === campaign.id && res?.campaign) {
        setDetailCampaign(res.campaign);
      }
    } catch (err) {
      showError(err.message || 'Failed to approve campaign.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleOpenReject = (campaign) => {
    setShowDetailModal(false); // Close detail modal first to prevent dual backdrops
    setRejectionTarget(campaign);
    setRejectionReason('');
    setShowRejectModal(true);
  };

  const handleConfirmReject = async () => {
    if (!rejectionReason.trim()) {
      showError('Please provide a rejection reason.');
      return;
    }
    setActionLoading(true);
    try {
      const res = await adCampaignsApi.rejectCampaign(rejectionTarget.id, rejectionReason.trim());
      showSuccess('Ad campaign rejected.');
      handleCloseReject();
      handleCloseDetail();
      fetchCampaigns(pagination.page);
    } catch (err) {
      showError(err.message || 'Failed to reject campaign.');
    } finally {
      setActionLoading(false);
    }
  };

  const handlePause = async (campaign) => {
    setActionLoading(true);
    try {
      const res = await adCampaignsApi.pauseCampaign(campaign.id);
      showSuccess('Ad campaign paused.');
      fetchCampaigns(pagination.page);
      if (detailCampaign && detailCampaign.id === campaign.id && res?.campaign) {
        setDetailCampaign(res.campaign);
      }
    } catch (err) {
      showError(err.message || 'Failed to pause campaign.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleResume = async (campaign) => {
    setActionLoading(true);
    try {
      const res = await adCampaignsApi.resumeCampaign(campaign.id);
      showSuccess('Ad campaign resumed.');
      fetchCampaigns(pagination.page);
      if (detailCampaign && detailCampaign.id === campaign.id && res?.campaign) {
        setDetailCampaign(res.campaign);
      }
    } catch (err) {
      showError(err.message || 'Failed to resume campaign.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleStop = async (campaign) => {
    setActionLoading(true);
    try {
      const res = await adCampaignsApi.stopCampaign(campaign.id);
      showSuccess(res?.message || 'Ad campaign stopped.');
      fetchCampaigns(pagination.page);
      if (detailCampaign && detailCampaign.id === campaign.id && res?.campaign) {
        setDetailCampaign(res.campaign);
      }
    } catch (err) {
      showError(err.message || 'Failed to stop campaign.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleRestart = async (campaign) => {
    setActionLoading(true);
    try {
      const res = await adCampaignsApi.restartCampaign(campaign.id);
      showSuccess(res?.message || 'Ad campaign restarted successfully.');
      fetchCampaigns(pagination.page);
      if (detailCampaign && detailCampaign.id === campaign.id && res?.campaign) {
        setDetailCampaign(res.campaign);
      }
    } catch (err) {
      showError(err.message || 'Failed to restart campaign.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleOpenDetail = (campaign) => {
    setDetailCampaign(campaign);
    setShowDetailModal(true);
    if (campaign?.id) {
      adCampaignsApi
        .getCampaign(campaign.id)
        .then((res) => {
          if (res?.campaign) {
            setDetailCampaign((prev) => (prev?.id === campaign.id ? res.campaign : prev));
          }
        })
        .catch((err) => {
          console.debug('[AdCampaigns] Background detail refresh notice:', err);
        });
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <PageHeader
        title="Ads Control Center"
        subtitle="Manage, review, moderate, and monitor Business Page advertising campaigns platform-wide."
        breadcrumbs={[
          { label: 'Admin', to: '/admin/dashboard' },
          { label: 'Ads Management' },
        ]}
      />

      {/* KPI Overview Grid */}
      <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500">Total Campaigns</span>
            <Megaphone className="w-4 h-4 text-blue-500" />
          </div>
          <p className="text-xl font-bold text-slate-800 mt-2">
            {metrics.total_campaigns || 0}
          </p>
          <span className="text-[11px] text-slate-400">All registered ads</span>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500">Pending Review</span>
            <Clock className="w-4 h-4 text-amber-500" />
          </div>
          <p className="text-xl font-bold text-amber-600 mt-2">
            {metrics.pending_review || 0}
          </p>
          <span className="text-[11px] text-slate-400">Awaiting moderation</span>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500">Active / Approved</span>
            <CheckCircle className="w-4 h-4 text-emerald-500" />
          </div>
          <p className="text-xl font-bold text-emerald-600 mt-2">
            {metrics.active || 0} / {metrics.approved || 0}
          </p>
          <span className="text-[11px] text-slate-400">Live in feed</span>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500">Impressions</span>
            <Eye className="w-4 h-4 text-sky-500" />
          </div>
          <p className="text-xl font-bold text-sky-600 mt-2">
            {Number(metrics.total_impressions || 0).toLocaleString()}
          </p>
          <span className="text-[11px] text-slate-400">Total views served</span>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500">Clicks (Avg CTR)</span>
            <TrendingUp className="w-4 h-4 text-indigo-500" />
          </div>
          <p className="text-xl font-bold text-indigo-600 mt-2">
            {Number(metrics.total_clicks || 0).toLocaleString()}{' '}
            <span className="text-xs font-semibold text-slate-400">
              ({metrics.average_ctr !== undefined ? Number(metrics.average_ctr).toFixed(2) : '0.00'}%)
            </span>
          </p>
          <span className="text-[11px] text-slate-400">Platform interactions</span>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500">Total Budget (USD)</span>
            <DollarSign className="w-4 h-4 text-slate-600" />
          </div>
          <p className="text-xl font-bold text-slate-800 mt-2">
            ${Number(metrics.total_budget || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}
          </p>
          <span className="text-[11px] text-slate-400">Allocated ad spend</span>
        </div>
      </div>

      {/* Filter & Search Bar */}
      <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <form onSubmit={handleSearchSubmit} className="flex flex-wrap items-center justify-between gap-3">
          <div className="flex flex-wrap items-center gap-3 flex-1 min-w-[300px]">
            <div className="flex-1 max-w-sm">
              <AdminSearchInput
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                onClear={() => setSearch('')}
                placeholder="Search campaign, page, owner..."
              />
            </div>

            <Dropdown
              value={status}
              options={STATUS_OPTIONS}
              onChange={(e) => setStatus(e.value)}
              placeholder="Status"
              className="text-sm border border-slate-300 rounded-lg w-40 bg-white"
            />

            <Dropdown
              value={approvalStatus}
              options={APPROVAL_OPTIONS}
              onChange={(e) => setApprovalStatus(e.value)}
              placeholder="Approval"
              className="text-sm border border-slate-300 rounded-lg w-36 bg-white"
            />
          </div>

          <div className="flex items-center gap-2">
            <Button
              type="submit"
              label="Filter"
              icon="pi pi-filter"
              size="small"
              className="p-button-primary text-sm py-2 px-4"
            />
            <Button
              type="button"
              label="Reset"
              icon="pi pi-refresh"
              size="small"
              className="p-button-outlined p-button-secondary text-sm py-2 px-3"
              onClick={() => {
                setSearch('');
                setStatus('');
                setApprovalStatus('');
                setPagination((prev) => ({ ...prev, page: 1 }));
              }}
            />
          </div>
        </form>
      </div>

      {/* Main Table */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        {loading ? (
          <div className="py-16">
            <LoadingSpinner message="Loading advertising campaigns..." />
          </div>
        ) : error ? (
          <ErrorState message={error} onRetry={() => fetchCampaigns(pagination.page)} />
        ) : campaigns.length === 0 ? (
          <EmptyState
            icon={Megaphone}
            title="No Advertising Campaigns Found"
            description="There are no ad campaigns matching the current search criteria."
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-slate-600">
              <thead className="bg-slate-50 text-xs font-semibold text-slate-500 uppercase border-b border-slate-200">
                <tr>
                  <th className="px-4 py-3">Campaign</th>
                  <th className="px-4 py-3">Business Page</th>
                  <th className="px-4 py-3">Owner</th>
                  <th className="px-4 py-3 text-right">Budget</th>
                  <th className="px-4 py-3 text-right">Metrics (Views / Clicks)</th>
                  <th className="px-4 py-3 text-center">Status</th>
                  <th className="px-4 py-3 text-center">Approval</th>
                  <th className="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-200">
                {campaigns.map((camp) => (
                  <tr
                    key={camp.id}
                    className="hover:bg-slate-50/50 transition-colors"
                  >
                    {/* Campaign Info */}
                    <td className="px-4 py-3">
                      <div>
                        <div className="font-semibold text-slate-800">
                          {camp.campaign_name}
                        </div>
                        <div className="text-xs text-slate-400 font-mono">
                          {camp.campaign_id || `ID: ${camp.id}`}
                        </div>
                      </div>
                    </td>

                    {/* Business Page */}
                    <td className="px-4 py-3">
                      {camp.business_page ? (
                        <div className="flex items-center gap-2">
                          <Building className="w-4 h-4 text-blue-500 shrink-0" />
                          <span className="font-medium text-slate-700">
                            {camp.business_page.page_name}
                          </span>
                        </div>
                      ) : (
                        <span className="text-slate-400 italic">No Page</span>
                      )}
                    </td>

                    {/* Owner */}
                    <td className="px-4 py-3">
                      {camp.owner ? (
                        <div>
                          <div className="text-xs font-medium text-slate-700">
                            {camp.owner.name}
                          </div>
                          <div className="text-[11px] text-slate-400">{camp.owner.email}</div>
                        </div>
                      ) : (
                        <span className="text-slate-400 italic">Unknown</span>
                      )}
                    </td>

                    {/* Budget */}
                    <td className="px-4 py-3 text-right font-medium">
                      <div className="text-blue-600 font-semibold">
                        ${Number(camp.budget || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}
                      </div>
                      <div className="text-[11px] text-slate-400">
                        Rem: ${Number(camp.remaining_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}
                      </div>
                    </td>

                    {/* Metrics */}
                    <td className="px-4 py-3 text-right">
                      <div className="font-semibold text-slate-700">
                        {(camp.impressions_count || 0).toLocaleString()} views
                      </div>
                      <div className="text-xs text-indigo-600 font-medium">
                        {(camp.clicks_count || 0).toLocaleString()} clicks{' '}
                        <span className="text-slate-400">
                          ({camp.ctr !== undefined ? Number(camp.ctr).toFixed(2) : '0.00'}%)
                        </span>
                      </div>
                    </td>

                    {/* Status */}
                    <td className="px-4 py-3 text-center">
                      <StatusBadge status={camp.status} />
                    </td>

                    {/* Approval */}
                    <td className="px-4 py-3 text-center">
                      <StatusBadge status={camp.approval_status} />
                    </td>

                    {/* Actions */}
                    <td className="px-4 py-3 text-right">
                      <div className="flex items-center justify-end gap-1.5">
                        <Button
                          icon="pi pi-eye"
                          size="small"
                          className="p-button-text p-button-secondary p-button-rounded"
                          tooltip="View Details & Mod"
                          onClick={() => handleOpenDetail(camp)}
                        />

                        {camp.approval_status === 'pending' && (
                          <>
                            <Button
                              icon="pi pi-check"
                              size="small"
                              className="p-button-text p-button-success p-button-rounded"
                              tooltip="Approve Campaign"
                              loading={actionLoading}
                              onClick={() => handleApprove(camp)}
                            />
                            <Button
                              icon="pi pi-times"
                              size="small"
                              className="p-button-text p-button-danger p-button-rounded"
                              tooltip="Reject Campaign"
                              onClick={() => handleOpenReject(camp)}
                            />
                          </>
                        )}

                        {camp.approval_status === 'approved' && camp.status === 'active' && (
                          <>
                            <Button
                              icon="pi pi-pause"
                              size="small"
                              className="p-button-text p-button-warning p-button-rounded"
                              tooltip="Pause Campaign"
                              loading={actionLoading}
                              onClick={() => handlePause(camp)}
                            />
                            <Button
                              icon="pi pi-stop-circle"
                              size="small"
                              className="p-button-text p-button-danger p-button-rounded"
                              tooltip="Stop Campaign"
                              loading={actionLoading}
                              onClick={() => handleStop(camp)}
                            />
                          </>
                        )}

                        {camp.approval_status === 'approved' && camp.status === 'paused' && (
                          <>
                            <Button
                              icon="pi pi-play"
                              size="small"
                              className="p-button-text p-button-success p-button-rounded"
                              tooltip="Resume Campaign"
                              loading={actionLoading}
                              onClick={() => handleResume(camp)}
                            />
                            <Button
                              icon="pi pi-stop-circle"
                              size="small"
                              className="p-button-text p-button-danger p-button-rounded"
                              tooltip="Stop Campaign"
                              loading={actionLoading}
                              onClick={() => handleStop(camp)}
                            />
                          </>
                        )}

                        {camp.approval_status === 'approved' && camp.status === 'stopped' && (
                          <Button
                            icon="pi pi-play"
                            size="small"
                            className="p-button-text p-button-success p-button-rounded"
                            tooltip="Restart Campaign"
                            loading={actionLoading}
                            onClick={() => handleRestart(camp)}
                          />
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {/* Pagination */}
        {pagination.total > pagination.perPage && (
          <div className="p-3 border-t border-slate-200 bg-slate-50">
            <Paginator
              first={(pagination.page - 1) * pagination.perPage}
              rows={pagination.perPage}
              totalRecords={pagination.total}
              onPageChange={(e) => setPagination((prev) => ({ ...prev, page: e.page + 1 }))}
            />
          </div>
        )}
      </div>

      {/* Campaign Detail Modal */}
      <Dialog
        header={`Campaign: ${detailCampaign?.campaign_name || 'Campaign Details'}`}
        visible={Boolean(showDetailModal && detailCampaign)}
        onHide={handleCloseDetail}
        style={{ width: '90vw', maxWidth: '640px' }}
        className="p-fluid"
        dismissableMask
        modal
        blockScroll={false}
      >
        {detailCampaign && (
          <div className="space-y-4 pt-2">
            {/* Meta row */}
            <div className="grid grid-cols-2 gap-3 bg-slate-50 border border-slate-200 p-3 rounded-lg text-sm">
              <div>
                <span className="text-xs text-slate-400 block font-semibold">Business Page</span>
                <span className="font-semibold text-slate-800">
                  {detailCampaign.business_page?.page_name || 'N/A'}
                </span>
              </div>
              <div>
                <span className="text-xs text-slate-400 block font-semibold">Owner</span>
                <span className="font-semibold text-slate-800">
                  {detailCampaign.owner?.name || 'N/A'} ({detailCampaign.owner?.email})
                </span>
              </div>
              <div>
                <span className="text-xs text-slate-400 block font-semibold">Status / Approval</span>
                <div className="flex items-center gap-2 mt-1">
                  <StatusBadge status={detailCampaign.status} />
                  <StatusBadge status={detailCampaign.approval_status} />
                </div>
              </div>
              <div>
                <span className="text-xs text-slate-400 block font-semibold">Schedule</span>
                <span className="text-xs text-slate-700 font-medium">
                  Start: {detailCampaign.start_at ? new Date(detailCampaign.start_at).toLocaleDateString() : 'Immediate'}
                </span>
              </div>
            </div>

            {/* Performance Stats */}
            <div className="grid grid-cols-3 gap-3 text-center">
              <div className="p-3 bg-sky-50 rounded-lg border border-sky-200">
                <span className="text-xs text-slate-500 font-semibold block">Impressions</span>
                <span className="text-lg font-bold text-sky-600">
                  {(detailCampaign.impressions_count || 0).toLocaleString()}
                </span>
              </div>
              <div className="p-3 bg-indigo-50 rounded-lg border border-indigo-200">
                <span className="text-xs text-slate-500 font-semibold block">Clicks & CTR</span>
                <span className="text-lg font-bold text-indigo-600">
                  {(detailCampaign.clicks_count || 0).toLocaleString()}{' '}
                  <span className="text-xs text-slate-400">
                    ({detailCampaign.ctr !== undefined ? Number(detailCampaign.ctr).toFixed(2) : '0.00'}%)
                  </span>
                </span>
              </div>
              <div className="p-3 bg-emerald-50 rounded-lg border border-emerald-200">
                <span className="text-xs text-slate-500 font-semibold block">Verified Visits</span>
                <span className="text-lg font-bold text-emerald-600">
                  {((detailCampaign.rewards_count || detailCampaign.verified_visits) || 0).toLocaleString()}
                </span>
              </div>
            </div>

            {/* Financial Ledger Breakdown */}
            <div className="p-4 bg-slate-50 rounded-xl border border-slate-200">
              <span className="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-3">
                Financial Accounting & Budget Breakdown
              </span>
              <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                <div className="p-2.5 bg-white rounded-lg border border-slate-200">
                  <span className="text-slate-400 block font-medium">Running Budget</span>
                  <span className="text-sm font-bold text-slate-900">
                    ${Number(detailCampaign.budget || 0).toFixed(2)}
                  </span>
                </div>
                <div className="p-2.5 bg-white rounded-lg border border-slate-200">
                  <span className="text-slate-400 block font-medium">Admin Fee ({detailCampaign.fee_percent || 2.5}%)</span>
                  <span className="text-sm font-bold text-amber-600">
                    ${Number(detailCampaign.fee_amount || 0).toFixed(2)}
                  </span>
                </div>
                <div className="p-2.5 bg-white rounded-lg border border-slate-200">
                  <span className="text-slate-400 block font-medium">Total Wallet Debit</span>
                  <span className="text-sm font-bold text-sky-600">
                    ${Number(detailCampaign.wallet_debit || detailCampaign.budget || 0).toFixed(2)}
                  </span>
                </div>
                <div className="p-2.5 bg-white rounded-lg border border-slate-200">
                  <span className="text-slate-400 block font-medium">Rewards Paid ($0.05)</span>
                  <span className="text-sm font-bold text-emerald-600">
                    ${Number(detailCampaign.rewards_paid || detailCampaign.spent_amount || 0).toFixed(2)}
                  </span>
                </div>
                <div className="p-2.5 bg-white rounded-lg border border-slate-200">
                  <span className="text-slate-400 block font-medium">Remaining Budget</span>
                  <span className="text-sm font-bold text-indigo-600">
                    ${Number(detailCampaign.remaining_campaign_budget || detailCampaign.remaining_amount || 0).toFixed(2)}
                  </span>
                </div>
                <div className="p-2.5 bg-white rounded-lg border border-slate-200">
                  <span className="text-slate-400 block font-medium">Reconciliation</span>
                  <span className="text-xs font-bold text-emerald-600 flex items-center gap-1 mt-0.5">
                    <CheckCircle className="w-3.5 h-3.5" /> 100% Reconciled
                  </span>
                </div>
              </div>
            </div>

            {/* Rejection notice if rejected */}
            {detailCampaign.rejection_reason && (
              <div className="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                <strong>Rejection Reason:</strong> {detailCampaign.rejection_reason}
              </div>
            )}

            {/* Promoted Post Preview */}
            {(() => {
              const post = detailCampaign.promoted_post || detailCampaign.post;
              if (post) {
                const author = post.author || post.member;
                return (
                  <div className="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
                    <div className="flex items-center justify-between border-b border-slate-200 pb-2">
                      <div className="flex items-center gap-2">
                        <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                          Promoted Post #{post.id}
                        </span>
                        {post.media_type && (
                          <span className="text-[10px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded bg-slate-200 text-slate-700">
                            {post.media_type}
                          </span>
                        )}
                      </div>
                      {author && (
                        <span className="text-xs text-slate-500 flex items-center gap-1">
                          <User className="w-3.5 h-3.5" />
                          <span>{author.name}</span>
                          {author.user_id && (
                            <span className="text-slate-400">({author.user_id})</span>
                          )}
                        </span>
                      )}
                    </div>

                    {post.body && (
                      <p className="text-sm text-slate-800 whitespace-pre-line font-medium">
                        {post.body}
                      </p>
                    )}

                    <PromotedMediaPreview post={post} />
                  </div>
                );
              }

              if (detailCampaign.post_id) {
                return (
                  <div className="p-4 bg-amber-50 border border-amber-200 rounded-xl">
                    <div className="flex items-center gap-2 text-amber-800 text-sm font-semibold">
                      <AlertTriangle className="w-4 h-4 text-amber-600" />
                      <span>Promoted post is no longer available.</span>
                    </div>
                    <p className="text-xs text-amber-700 mt-1">
                      The original post (#{detailCampaign.post_id}) attached to this campaign was deleted or removed from the database.
                    </p>
                  </div>
                );
              }

              return (
                <div className="text-xs text-slate-400 italic p-3 bg-slate-50 border border-slate-200 rounded-lg">
                  No promoted post attached.
                </div>
              );
            })()}

            {/* Modal Actions */}
            <div className="flex items-center justify-end gap-2 pt-2 border-t border-slate-200">
              {detailCampaign.approval_status === 'pending' && (
                <>
                  <Button
                    label="Approve Campaign"
                    icon="pi pi-check"
                    className="p-button-success"
                    size="small"
                    loading={actionLoading}
                    onClick={() => handleApprove(detailCampaign)}
                  />
                  <Button
                    label="Reject"
                    icon="pi pi-times"
                    className="p-button-danger"
                    size="small"
                    onClick={() => handleOpenReject(detailCampaign)}
                  />
                </>
              )}

              {detailCampaign.approval_status === 'approved' && detailCampaign.status === 'active' && (
                <Button
                  label="Pause Campaign"
                  icon="pi pi-pause"
                  className="p-button-warning"
                  size="small"
                  loading={actionLoading}
                  onClick={() => handlePause(detailCampaign)}
                />
              )}

              {detailCampaign.approval_status === 'approved' && detailCampaign.status === 'paused' && (
                <Button
                  label="Resume Campaign"
                  icon="pi pi-play"
                  className="p-button-success"
                  size="small"
                  loading={actionLoading}
                  onClick={() => handleResume(detailCampaign)}
                />
              )}

              {['approved', 'active', 'paused'].includes(detailCampaign.status) && (
                <Button
                  label="Stop Campaign"
                  icon="pi pi-stop-circle"
                  className="p-button-outlined p-button-danger"
                  size="small"
                  loading={actionLoading}
                  onClick={() => handleStop(detailCampaign)}
                />
              )}

              {detailCampaign.status === 'stopped' && (
                <Button
                  label="Restart Campaign"
                  icon="pi pi-play"
                  className="p-button-success"
                  size="small"
                  loading={actionLoading}
                  onClick={() => handleRestart(detailCampaign)}
                />
              )}

              <Button
                label="Close"
                className="p-button-outlined p-button-secondary"
                size="small"
                onClick={handleCloseDetail}
              />
            </div>
          </div>
        )}
      </Dialog>

      {/* Reject Confirmation Dialog */}
      <Dialog
        header="Reject Advertising Campaign"
        visible={Boolean(showRejectModal && rejectionTarget)}
        onHide={handleCloseReject}
        style={{ width: '90vw', maxWidth: '480px' }}
        dismissableMask
        modal
        blockScroll={false}
      >
        <div className="space-y-4 pt-2">
          <p className="text-sm text-slate-600">
            Please enter a reason for rejecting{' '}
            <strong>{rejectionTarget?.campaign_name}</strong>. The Business Page owner will be notified of this reason.
          </p>

          <div>
            <label className="text-xs font-semibold text-slate-500 block mb-1">
              Rejection Reason <span className="text-red-500">*</span>
            </label>
            <InputTextarea
              value={rejectionReason}
              onChange={(e) => setRejectionReason(e.target.value)}
              rows={4}
              placeholder="e.g. Content violates advertising policies, prohibited financial claims, or low image resolution."
              className="w-full text-sm border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 p-2.5 rounded-lg focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"
            />
          </div>

          <div className="flex items-center justify-end gap-2 pt-2 border-t border-slate-200">
            <Button
              label="Cancel"
              className="p-button-outlined p-button-secondary"
              size="small"
              onClick={handleCloseReject}
            />
            <Button
              label="Reject Campaign"
              icon="pi pi-times"
              className="p-button-danger"
              size="small"
              loading={actionLoading}
              onClick={handleConfirmReject}
            />
          </div>
        </div>
      </Dialog>
    </div>
  );
}

export default AdCampaignsListPage;
