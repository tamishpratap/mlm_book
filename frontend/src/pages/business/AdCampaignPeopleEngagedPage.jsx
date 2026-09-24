import { useState, useEffect, useCallback, useMemo } from 'react';
import { useParams, Link, useNavigate, useSearchParams } from 'react-router-dom';
import {
  Users,
  CheckCircle2,
  Star,
  Phone,
  Mail,
  MessageCircle,
  Copy,
  Check,
  Search,
  ArrowLeft,
  Calendar,
  MapPin,
  Video,
  Download,
  Share2,
  ExternalLink,
  ShieldCheck,
  Sparkles,
  LayoutGrid,
  List,
  Filter,
  User,
  AlertCircle,
  PhoneCall,
  Send,
  Eye,
  DollarSign,
  TrendingUp,
  MousePointerClick,
  RefreshCw,
  Gift,
  Award,
  BarChart2,
  ChevronLeft,
  ChevronRight,
  SlidersHorizontal,
  ArrowUpDown,
  RotateCcw,
  CheckSquare,
  Square,
  PhoneOff,
  MailX,
  X,
  Percent,
  Wallet,
  Megaphone,
} from 'lucide-react';
import businessApi from '../../api/businessApi';
import useAuth from '../../hooks/useAuth';
import VerifiedBadge from '../../components/common/VerifiedBadge';
import MemberAvatar from '../../components/common/MemberAvatar';
import { getAvatarUrl, getInitials, getMediaUrl } from '../../utils/assetHelper';
import { AddFundsToCampaignModal } from '../../components/business/ads/AddFundsToCampaignModal';
import { AddFundModal } from '../../components/business/ads/AddFundModal';

export function AdCampaignPeopleEngagedPage() {
  const { slug, campaignId } = useParams();
  const [searchParams, setSearchParams] = useSearchParams();
  const navigate = useNavigate();
  const { user: currentUser } = useAuth();

  // Campaign & Audience State
  const [data, setData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [isRefreshing, setIsRefreshing] = useState(false);

  // Filters & State
  const [verificationFilter, setVerificationFilter] = useState(searchParams.get('verification') || 'all');
  const [datePreset, setDatePreset] = useState(searchParams.get('date_preset') || 'all');
  const [startDate, setStartDate] = useState(searchParams.get('start_date') || '');
  const [endDate, setEndDate] = useState(searchParams.get('end_date') || '');
  const [sortOrder, setSortOrder] = useState(searchParams.get('sort') || 'newest');
  const [searchQuery, setSearchQuery] = useState(searchParams.get('q') || '');
  const [debouncedSearch, setDebouncedSearch] = useState(searchParams.get('q') || '');
  const [currentPage, setCurrentPage] = useState(parseInt(searchParams.get('page') || '1', 10));
  const [viewMode, setViewMode] = useState('table'); // 'table' | 'grid'

  // Selection state
  const [selectedMemberIds, setSelectedMemberIds] = useState([]);

  // Copy feedback state
  const [copiedKey, setCopiedKey] = useState(null);

  // Contact Center & Outreach Modal State
  const [showContactModal, setShowContactModal] = useState(false);
  const [contactTargetMembers, setContactTargetMembers] = useState([]);
  const [contactValidationData, setContactValidationData] = useState(null);
  const [isValidatingContact, setIsValidatingContact] = useState(false);

  // Top-Up Funds Modal State
  const [showTopUpModal, setShowTopUpModal] = useState(false);
  const [showAddFundModal, setShowAddFundModal] = useState(false);

  // Analytics Collapsible Section State
  const [showAnalyticsSection, setShowAnalyticsSection] = useState(true);

  // Debounce search query (300ms)
  useEffect(() => {
    const handler = setTimeout(() => {
      setDebouncedSearch(searchQuery);
    }, 300);
    return () => clearTimeout(handler);
  }, [searchQuery]);

  // Sync URL search params
  useEffect(() => {
    const params = new URLSearchParams();
    if (verificationFilter !== 'all') params.set('verification', verificationFilter);
    if (datePreset !== 'all') params.set('date_preset', datePreset);
    if (startDate) params.set('start_date', startDate);
    if (endDate) params.set('end_date', endDate);
    if (sortOrder !== 'newest') params.set('sort', sortOrder);
    if (debouncedSearch) params.set('q', debouncedSearch);
    if (currentPage > 1) params.set('page', String(currentPage));

    setSearchParams(params, { replace: true });
  }, [
    verificationFilter,
    datePreset,
    startDate,
    endDate,
    sortOrder,
    debouncedSearch,
    currentPage,
    setSearchParams,
  ]);

  // Fetch Campaign Engagements & Audience
  const fetchEngagements = useCallback(
    async (isBackground = false) => {
      if (!slug || !campaignId) return;
      if (!isBackground) setIsLoading(true);
      else setIsRefreshing(true);
      setError(null);

      try {
        const params = {
          verification: verificationFilter !== 'all' ? verificationFilter : undefined,
          date_preset: datePreset !== 'all' ? datePreset : undefined,
          start_date: startDate || undefined,
          end_date: endDate || undefined,
          sort: sortOrder,
          q: debouncedSearch.trim() || undefined,
          page: currentPage,
          per_page: 15,
        };

        const res = await businessApi.getCampaignEngagements(slug, campaignId, params);
        if (res && res.success) {
          setData(res);
        } else {
          setError(res?.message || 'Failed to load campaign engagement audience.');
        }
      } catch (err) {
        if (err.response?.status === 403) {
          setError('Unauthorized: You do not have permission to view engagement data for this campaign.');
        } else if (err.response?.status === 404) {
          setError('Campaign or Business Page not found.');
        } else {
          setError(err.response?.data?.message || 'Failed to load campaign engagement data.');
        }
      } finally {
        setIsLoading(false);
        setIsRefreshing(false);
      }
    },
    [slug, campaignId, verificationFilter, datePreset, startDate, endDate, sortOrder, debouncedSearch, currentPage]
  );

  useEffect(() => {
    fetchEngagements();
  }, [fetchEngagements]);

  // Extract objects safely
  const campaign = data?.campaign || {};
  const businessPage = campaign?.business_page || data?.business_page || {};
  const summary = data?.summary || {
    total_engagements: 0,
    total_engaged_members: 0,
    interested_count: 0,
    click_count: 0,
    landing_count: 0,
    rewarded_count: 0,
    reward_qualified_count: 0,
    not_rewarded_count: 0,
    total_rewards_paid: 0.0,
    remaining_budget: 0.0,
    verified_members_count: 0,
    rates: {
      ctr_percentage: 0.0,
      landing_conversion_rate: 0.0,
      reward_conversion_rate: 0.0,
    },
    financials: {
      budget: 0.0,
      additional_funding: 0.0,
      total_funded: 0.0,
      fee_amount: 0.0,
      spent_amount: 0.0,
      remaining_budget: 0.0,
      balance_reconciled: true,
    },
    tier_breakdown: [],
  };

  const engagementsPaginated = data?.engagements || {
    data: [],
    current_page: 1,
    total: 0,
    last_page: 1,
    filtered_unique_members: 0,
  };

  const engagementItems = engagementsPaginated.data || [];

  // Copy text helper
  const handleCopy = (text, key) => {
    if (!text) return;
    navigator.clipboard.writeText(text);
    setCopiedKey(key);
    setTimeout(() => setCopiedKey(null), 2500);
  };

  // Copy all phone numbers across currently visible/filtered audience
  const handleCopyAllPhones = () => {
    const phones = engagementItems
      .map((item) => item.user?.phone)
      .filter(Boolean)
      .map((p) => p.trim());

    if (phones.length === 0) {
      alert('No phone numbers available in current view.');
      return;
    }
    navigator.clipboard.writeText(phones.join(', '));
    setCopiedKey('all_phones');
    setTimeout(() => setCopiedKey(null), 2500);
  };

  // Copy all email addresses
  const handleCopyAllEmails = () => {
    const emails = engagementItems
      .map((item) => item.user?.email)
      .filter(Boolean)
      .map((e) => e.trim());

    if (emails.length === 0) {
      alert('No email addresses available in current view.');
      return;
    }
    navigator.clipboard.writeText(emails.join(', '));
    setCopiedKey('all_emails');
    setTimeout(() => setCopiedKey(null), 2500);
  };

  // CSV Export
  const handleExportCSV = async (mode = 'audience', onlySelected = false) => {
    try {
      const params = {
        verification: verificationFilter !== 'all' ? verificationFilter : undefined,
        date_preset: datePreset !== 'all' ? datePreset : undefined,
        start_date: startDate || undefined,
        end_date: endDate || undefined,
        sort: sortOrder,
        q: debouncedSearch.trim() || undefined,
        mode: mode, // 'audience' | 'activities'
        selected_ids: onlySelected && selectedMemberIds.length > 0 ? selectedMemberIds.join(',') : undefined,
      };

      const response = await businessApi.exportCampaignEngagements(slug, campaignId, params);

      // Handle blob download
      const blob = new Blob([response.data || response], { type: 'text/csv;charset=utf-8;' });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute(
        'download',
        `Campaign_${campaign.campaign_id || campaign.id}_${mode}_${new Date().toISOString().slice(0, 10)}.csv`
      );
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
    } catch (err) {
      alert('Failed to export campaign data. Please try again.');
    }
  };

  // Selection handlers
  const handleToggleSelectMember = (memberId) => {
    if (!memberId) return;
    setSelectedMemberIds((prev) =>
      prev.includes(memberId) ? prev.filter((id) => id !== memberId) : [...prev, memberId]
    );
  };

  const handleSelectAllOnPage = () => {
    const pageMemberIds = engagementItems.map((item) => item.user?.id).filter(Boolean);
    const allSelected = pageMemberIds.every((id) => selectedMemberIds.includes(id));
    if (allSelected) {
      setSelectedMemberIds((prev) => prev.filter((id) => !pageMemberIds.includes(id)));
    } else {
      setSelectedMemberIds((prev) => Array.from(new Set([...prev, ...pageMemberIds])));
    }
  };

  const handleClearSelection = () => {
    setSelectedMemberIds([]);
  };

  // Pre-flight Contact Center validation
  const handleOpenContactModal = async (targetMembers = []) => {
    const targets = targetMembers.length > 0 ? targetMembers : engagementItems.filter((i) => selectedMemberIds.includes(i.user?.id)).map((i) => i.user);
    if (!targets || targets.length === 0) {
      alert('Please select at least one member to contact.');
      return;
    }

    setContactTargetMembers(targets);
    setShowContactModal(true);
    setIsValidatingContact(true);
    setContactValidationData(null);

    try {
      const memberIds = targets.map((m) => m.id).filter(Boolean);
      const res = await businessApi.contactCampaignAudience(slug, campaignId, {
        member_ids: memberIds,
      });
      if (res && res.success) {
        setContactValidationData(res);
      }
    } catch (err) {
      // Safe error handling without logging sensitive contact data
    } finally {
      setIsValidatingContact(false);
    }
  };

  // Helper formatting functions
  const formatMoney = (val) => {
    const num = parseFloat(val) || 0;
    return `$${num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
  };

  const getCleanWhatsAppNumber = (phoneStr) => {
    if (!phoneStr) return '';
    return phoneStr.replace(/[^0-9]/g, '');
  };

  const hasActiveFilters = useMemo(() => {
    return (
      verificationFilter !== 'all' ||
      datePreset !== 'all' ||
      startDate !== '' ||
      endDate !== '' ||
      debouncedSearch !== ''
    );
  }, [verificationFilter, datePreset, startDate, endDate, debouncedSearch]);

  const handleResetFilters = () => {
    setVerificationFilter('all');
    setDatePreset('all');
    setStartDate('');
    setEndDate('');
    setSearchQuery('');
    setDebouncedSearch('');
    setCurrentPage(1);
  };

  // Campaign status badge calculation
  const getCampaignStatusBadge = (status, approvalStatus, isLow = false, isExhausted = false) => {
    if (status === 'rejected' || approvalStatus === 'rejected') {
      return { bg: '#fee2e2', color: '#b91c1c', label: 'Rejected' };
    }
    if (isExhausted || status === 'budget_exhausted') {
      return { bg: '#fee2e2', color: '#b91c1c', label: 'Budget Exhausted' };
    }
    if (isLow && (status === 'active' || status === 'approved')) {
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
        return { bg: '#f1f5f9', color: '#475569', label: 'Stopped' };
      case 'completed':
        return { bg: '#f1f5f9', color: '#475569', label: 'Completed' };
      default:
        return { bg: '#f3f4f6', color: '#6b7280', label: 'Draft' };
    }
  };

  if (isLoading) {
    return (
      <div className="container" style={{ maxWidth: '1240px', margin: '40px auto', padding: '0 20px', textAlign: 'center' }}>
        <div className="card" style={{ padding: '60px 20px', borderRadius: '20px' }}>
          <div
            className="spinner fa-spin"
            style={{
              margin: '0 auto 16px',
              width: '38px',
              height: '38px',
              border: '3px solid #e2e8f0',
              borderTopColor: '#2563eb',
              borderRadius: '50%',
            }}
          />
          <h2 style={{ fontSize: '19px', fontWeight: 800, margin: '0 0 8px 0', color: '#1e293b' }}>
            Loading Ad Campaign People Engaged...
          </h2>
          <p style={{ color: '#64748b', margin: 0, fontSize: '14px' }}>
            Gathering authoritative campaign engagement history, lead intelligence, and contact data.
          </p>
        </div>
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="container" style={{ maxWidth: '840px', margin: '40px auto', padding: '0 20px' }}>
        <div
          className="card"
          style={{ padding: '36px', borderRadius: '20px', textAlign: 'center', background: '#fff', border: '1px solid #fee2e2' }}
        >
          <AlertCircle size={46} color="#dc2626" style={{ margin: '0 auto 14px' }} />
          <h2 style={{ fontSize: '20px', fontWeight: 800, color: '#991b1b', margin: '0 0 8px 0' }}>Access Restricted / Not Found</h2>
          <p style={{ color: '#4b5563', fontSize: '14.5px', marginBottom: '24px', lineHeight: 1.5 }}>
            {error || 'You do not have advertiser permissions for this campaign.'}
          </p>
          <div style={{ display: 'flex', justifyContent: 'center', gap: '12px' }}>
            <Link to={`/member/business-pages/${slug}?tab=ads`} className="member-button member-button--secondary">
              <ArrowLeft size={16} />
              <span>Back to Business Page Ads</span>
            </Link>
          </div>
        </div>
      </div>
    );
  }

  const campStatusBadge = getCampaignStatusBadge(
    campaign.status,
    campaign.approval_status,
    Boolean(campaign.is_budget_low),
    Boolean(campaign.is_budget_exhausted || campaign.status === 'budget_exhausted')
  );

  const totalFundedAmount = parseFloat(summary.financials?.total_funded || campaign.total_funded || campaign.budget || 0);
  const spentAmount = parseFloat(summary.financials?.spent_amount || campaign.spent_amount || 0);
  const remainingAmount = parseFloat(summary.financials?.remaining_budget || campaign.remaining_amount || 0);
  const budgetSpentPct = totalFundedAmount > 0 ? Math.min(100, Math.round((spentAmount / totalFundedAmount) * 100)) : 0;

  return (
    <div
      className="biz-page ad-campaign-people-engaged-page"
      style={{
        width: '100%',
        maxWidth: '1400px',
        margin: '0 auto',
        padding: '24px 20px 60px 20px',
        boxSizing: 'border-box',
      }}
    >
      {/* Top Breadcrumb & Navigation Bar */}
      <div
        style={{
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
          marginBottom: '20px',
          flexWrap: 'wrap',
          gap: '12px',
        }}
      >
        <div style={{ display: 'flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' }}>
          <Link
            to={`/member/business-pages/${slug}?tab=ads`}
            className="member-button member-button--secondary"
            style={{ padding: '8px 14px', fontSize: '13px', borderRadius: '10px', display: 'inline-flex', alignItems: 'center', gap: '6px' }}
          >
            <ArrowLeft size={15} />
            <span>Back to Ads</span>
          </Link>

          <span style={{ color: '#94a3b8', fontSize: '13px' }}>/</span>

          <Link
            to={`/member/business-pages/${slug}`}
            style={{ color: '#64748b', fontSize: '13px', fontWeight: 600, textDecoration: 'none' }}
          >
            {businessPage.page_name || 'Business Page'}
          </Link>

          <span style={{ color: '#94a3b8', fontSize: '13px' }}>/</span>

          <span style={{ color: '#1e293b', fontSize: '13px', fontWeight: 700 }}>
            {campaign.campaign_name || 'Campaign'}
          </span>

          <span style={{ color: '#94a3b8', fontSize: '13px' }}>/</span>

          <span style={{ color: '#2563eb', fontSize: '13px', fontWeight: 700 }}>People Engaged</span>
        </div>

        {/* Top Right Quick Actions */}
        <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
          <button
            type="button"
            className="member-button member-button--secondary"
            onClick={() => fetchEngagements(true)}
            disabled={isRefreshing}
            style={{ padding: '7px 12px', fontSize: '12.5px', borderRadius: '8px' }}
            title="Refresh engagement data"
          >
            <RefreshCw size={14} className={isRefreshing ? 'animate-spin' : ''} />
            <span>Refresh</span>
          </button>

          <button
            type="button"
            className="member-button"
            onClick={() => setShowTopUpModal(true)}
            style={{
              padding: '7px 14px',
              fontSize: '12.5px',
              borderRadius: '8px',
              backgroundColor: '#f0fdf4',
              color: '#15803d',
              border: '1px solid #bbf7d0',
              fontWeight: 700,
              display: 'inline-flex',
              alignItems: 'center',
              gap: '6px',
            }}
          >
            <Wallet size={14} />
            <span>Add Funds</span>
          </button>
        </div>
      </div>

      {/* Main Hero Header Card (Event-style polished gradient container) */}
      <header
        className="card"
        style={{
          background: 'linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #064e3b 100%)',
          color: '#ffffff',
          borderRadius: '24px',
          padding: '28px 32px',
          marginBottom: '24px',
          boxShadow: '0 12px 30px -8px rgba(15, 23, 42, 0.35)',
          position: 'relative',
          overflow: 'hidden',
        }}
      >
        <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', flexWrap: 'wrap', gap: '24px', position: 'relative', zIndex: 2 }}>
          <div style={{ flex: 1, minWidth: '300px' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '10px', flexWrap: 'wrap' }}>
              <span
                style={{
                  fontSize: '11.5px',
                  fontWeight: 800,
                  textTransform: 'uppercase',
                  letterSpacing: '0.06em',
                  background: 'rgba(255, 255, 255, 0.18)',
                  padding: '4px 10px',
                  borderRadius: '6px',
                  color: '#ffffff',
                }}
              >
                Ad Campaign Audience
              </span>

              <span
                style={{
                  fontSize: '11.5px',
                  fontWeight: 700,
                  padding: '3px 10px',
                  borderRadius: '12px',
                  backgroundColor: campStatusBadge.bg,
                  color: campStatusBadge.color,
                }}
              >
                {campStatusBadge.label}
              </span>

              <span style={{ fontSize: '13px', opacity: 0.85, display: 'inline-flex', alignItems: 'center', gap: '5px' }}>
                <Megaphone size={13} />
                <span>ID: {campaign.campaign_id || campaign.id}</span>
              </span>

              {campaign.post && (
                <span style={{ fontSize: '13px', opacity: 0.85 }}>
                  • Promoted Post #{campaign.post.id}
                </span>
              )}
            </div>

            <h1
              style={{
                fontSize: '28px',
                fontWeight: 800,
                margin: '0 0 10px 0',
                letterSpacing: '-0.02em',
                color: '#ffffff',
                lineHeight: 1.2,
              }}
            >
              {campaign.campaign_name || 'Campaign Audience & People Engaged'}
            </h1>

            <p style={{ margin: 0, fontSize: '14.5px', color: 'rgba(255, 255, 255, 0.88)', maxWidth: '780px', lineHeight: 1.5 }}>
              Manage, analyze, and contact members who interacted with this sponsored campaign across platform feeds.
              Real-time authoritative audit log with dynamic referral tier reward reconciliation.
            </p>
          </div>

          {/* Quick Action Batch Controls */}
          <div style={{ display: 'flex', gap: '10px', flexWrap: 'wrap', alignItems: 'center' }}>
            <button
              type="button"
              className="member-button"
              style={{
                background: copiedKey === 'all_phones' ? '#10b981' : '#25D366',
                color: '#ffffff',
                border: 'none',
                fontWeight: 700,
                fontSize: '13px',
                padding: '9px 15px',
                borderRadius: '10px',
                boxShadow: '0 4px 12px rgba(37, 211, 102, 0.3)',
                cursor: 'pointer',
              }}
              onClick={handleCopyAllPhones}
              title="Copy all phone numbers from current list"
            >
              {copiedKey === 'all_phones' ? <Check size={15} /> : <Phone size={15} />}
              <span>{copiedKey === 'all_phones' ? 'Phones Copied!' : 'Copy Phones'}</span>
            </button>

            <button
              type="button"
              className="member-button"
              style={{
                background: copiedKey === 'all_emails' ? '#10b981' : 'rgba(255, 255, 255, 0.18)',
                color: '#ffffff',
                border: '1px solid rgba(255, 255, 255, 0.3)',
                fontWeight: 600,
                fontSize: '13px',
                padding: '9px 15px',
                borderRadius: '10px',
                cursor: 'pointer',
              }}
              onClick={handleCopyAllEmails}
              title="Copy all email addresses from current list"
            >
              {copiedKey === 'all_emails' ? <Check size={15} /> : <Mail size={15} />}
              <span>{copiedKey === 'all_emails' ? 'Emails Copied!' : 'Copy Emails'}</span>
            </button>

            <button
              type="button"
              className="member-button"
              style={{
                background: '#ffffff',
                color: '#0f172a',
                border: 'none',
                fontWeight: 700,
                fontSize: '13px',
                padding: '9px 16px',
                borderRadius: '10px',
                cursor: 'pointer',
              }}
              onClick={() => handleExportCSV('audience')}
              title="Export audience as CSV"
            >
              <Download size={15} />
              <span>Export CSV</span>
            </button>
          </div>
        </div>

        {/* Financial Progress Strip */}
        <div
          style={{
            marginTop: '24px',
            paddingTop: '18px',
            borderTop: '1px solid rgba(255, 255, 255, 0.15)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            flexWrap: 'wrap',
            gap: '16px',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '20px', flexWrap: 'wrap' }}>
            <div>
              <span style={{ fontSize: '11.5px', color: 'rgba(255, 255, 255, 0.7)', display: 'block' }}>Total Funded</span>
              <strong style={{ fontSize: '15px', color: '#ffffff', fontWeight: 800 }}>{formatMoney(totalFundedAmount)}</strong>
            </div>

            <div style={{ width: '1px', height: '26px', background: 'rgba(255, 255, 255, 0.15)' }} />

            <div>
              <span style={{ fontSize: '11.5px', color: 'rgba(255, 255, 255, 0.7)', display: 'block' }}>Rewards Paid</span>
              <strong style={{ fontSize: '15px', color: '#f87171', fontWeight: 800 }}>{formatMoney(spentAmount)}</strong>
            </div>

            <div style={{ width: '1px', height: '26px', background: 'rgba(255, 255, 255, 0.15)' }} />

            <div>
              <span style={{ fontSize: '11.5px', color: 'rgba(255, 255, 255, 0.7)', display: 'block' }}>Remaining Budget</span>
              <strong style={{ fontSize: '15px', color: '#4ade80', fontWeight: 800 }}>{formatMoney(remainingAmount)}</strong>
            </div>
          </div>

          {/* Budget Consumption Bar */}
          <div style={{ flex: 1, maxWidth: '280px', minWidth: '180px' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '11.5px', color: 'rgba(255, 255, 255, 0.8)', marginBottom: '4px' }}>
              <span>Budget Spent</span>
              <span>{budgetSpentPct}%</span>
            </div>
            <div style={{ width: '100%', height: '6px', borderRadius: '4px', background: 'rgba(255, 255, 255, 0.2)', overflow: 'hidden' }}>
              <div style={{ width: `${budgetSpentPct}%`, height: '100%', background: budgetSpentPct > 90 ? '#ef4444' : '#10b981', borderRadius: '4px' }} />
            </div>
          </div>
        </div>
      </header>

      {/* 7 Lifetime KPI Summary Cards (Event-style structural composition) */}
      <div
        style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fit, minmax(160px, 1fr))',
          gap: '14px',
          marginBottom: '24px',
        }}
      >
        <div className="card" style={{ padding: '16px 18px', borderRadius: '16px', background: '#ffffff', border: '1px solid #e2e8f0' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <div style={{ width: '40px', height: '40px', borderRadius: '12px', background: '#eff6ff', color: '#2563eb', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              <Users size={20} />
            </div>
            <div>
              <strong style={{ fontSize: '20px', fontWeight: 800, color: '#1e293b', display: 'block', lineHeight: 1.1 }}>
                {summary.total_engaged_members}
              </strong>
              <span style={{ fontSize: '12px', color: '#64748b', fontWeight: 600 }}>Unique Members</span>
            </div>
          </div>
        </div>

        <div className="card" style={{ padding: '16px 18px', borderRadius: '16px', background: '#ffffff', border: '1px solid #e2e8f0' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <div style={{ width: '40px', height: '40px', borderRadius: '12px', background: '#f8fafc', color: '#475569', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              <Eye size={20} />
            </div>
            <div>
              <strong style={{ fontSize: '20px', fontWeight: 800, color: '#334155', display: 'block', lineHeight: 1.1 }}>
                {summary.total_engagements}
              </strong>
              <span style={{ fontSize: '12px', color: '#64748b', fontWeight: 600 }}>Total Engagements</span>
            </div>
          </div>
        </div>

        <div className="card" style={{ padding: '16px 18px', borderRadius: '16px', background: '#ffffff', border: '1px solid #e2e8f0' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <div style={{ width: '40px', height: '40px', borderRadius: '12px', background: '#fffbeb', color: '#d97706', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              <Star size={20} />
            </div>
            <div>
              <strong style={{ fontSize: '20px', fontWeight: 800, color: '#92400e', display: 'block', lineHeight: 1.1 }}>
                {summary.interested_count}
              </strong>
              <span style={{ fontSize: '12px', color: '#64748b', fontWeight: 600 }}>Interested Leads</span>
            </div>
          </div>
        </div>

        <div className="card" style={{ padding: '16px 18px', borderRadius: '16px', background: '#ffffff', border: '1px solid #e2e8f0' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <div style={{ width: '40px', height: '40px', borderRadius: '12px', background: '#eef2ff', color: '#4f46e5', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              <MousePointerClick size={20} />
            </div>
            <div>
              <strong style={{ fontSize: '20px', fontWeight: 800, color: '#4338ca', display: 'block', lineHeight: 1.1 }}>
                {summary.click_count}
              </strong>
              <span style={{ fontSize: '12px', color: '#64748b', fontWeight: 600 }}>
                Clicks ({summary.rates?.ctr_percentage || '0.00'}%)
              </span>
            </div>
          </div>
        </div>

        <div className="card" style={{ padding: '16px 18px', borderRadius: '16px', background: '#ffffff', border: '1px solid #e2e8f0' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <div style={{ width: '40px', height: '40px', borderRadius: '12px', background: '#faf5ff', color: '#9333ea', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              <ExternalLink size={20} />
            </div>
            <div>
              <strong style={{ fontSize: '20px', fontWeight: 800, color: '#7e22ce', display: 'block', lineHeight: 1.1 }}>
                {summary.landing_count}
              </strong>
              <span style={{ fontSize: '12px', color: '#64748b', fontWeight: 600 }}>Landing Visits</span>
            </div>
          </div>
        </div>

        <div className="card" style={{ padding: '16px 18px', borderRadius: '16px', background: '#ffffff', border: '1px solid #e2e8f0' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <div style={{ width: '40px', height: '40px', borderRadius: '12px', background: '#ecfdf5', color: '#059669', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              <CheckCircle2 size={20} />
            </div>
            <div>
              <strong style={{ fontSize: '20px', fontWeight: 800, color: '#065f46', display: 'block', lineHeight: 1.1 }}>
                {summary.rewarded_count}
              </strong>
              <span style={{ fontSize: '12px', color: '#64748b', fontWeight: 600 }}>Rewarded Members</span>
            </div>
          </div>
        </div>

        <div className="card" style={{ padding: '16px 18px', borderRadius: '16px', background: '#ffffff', border: '1px solid #e2e8f0' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <div style={{ width: '40px', height: '40px', borderRadius: '12px', background: '#f0fdf4', color: '#16a34a', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              <DollarSign size={20} />
            </div>
            <div>
              <strong style={{ fontSize: '20px', fontWeight: 800, color: '#15803d', display: 'block', lineHeight: 1.1 }}>
                {formatMoney(summary.total_rewards_paid)}
              </strong>
              <span style={{ fontSize: '12px', color: '#64748b', fontWeight: 600 }}>Total Rewards Paid</span>
            </div>
          </div>
        </div>
      </div>

      {/* Collapsible Advanced Analytics & Dynamic Tier Breakdown */}
      <div className="card" style={{ borderRadius: '20px', background: '#ffffff', border: '1px solid #e2e8f0', marginBottom: '24px', overflow: 'hidden' }}>
        <div
          style={{
            padding: '16px 24px',
            borderBottom: showAnalyticsSection ? '1px solid #f1f5f9' : 'none',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            cursor: 'pointer',
            background: '#fafafa',
          }}
          onClick={() => setShowAnalyticsSection(!showAnalyticsSection)}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <BarChart2 size={18} color="#2563eb" />
            <span style={{ fontSize: '14.5px', fontWeight: 700, color: '#1e293b' }}>
              Advanced Conversion Analytics &amp; Campaign Overview
            </span>
          </div>
          <span style={{ fontSize: '12.5px', color: '#64748b', fontWeight: 600 }}>
            {showAnalyticsSection ? 'Hide Details' : 'Show Details'}
          </span>
        </div>

        {showAnalyticsSection && (
          <div style={{ padding: '24px' }}>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '24px' }}>
              {/* Left: Conversion Rates */}
              <div>
                <h4 style={{ fontSize: '13.5px', fontWeight: 700, color: '#334155', margin: '0 0 14px 0' }}>
                  Funnel Conversion Velocity
                </h4>
                <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
                  <div>
                    <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '13px', marginBottom: '4px' }}>
                      <span style={{ color: '#64748b' }}>Click-Through Rate (CTR)</span>
                      <strong style={{ color: '#1e293b' }}>{summary.rates?.ctr_percentage || '0.00'}%</strong>
                    </div>
                    <div style={{ width: '100%', height: '8px', background: '#f1f5f9', borderRadius: '4px', overflow: 'hidden' }}>
                      <div
                        style={{
                          width: `${Math.min(100, parseFloat(summary.rates?.ctr_percentage || 0))}%`,
                          height: '100%',
                          background: '#4f46e5',
                          borderRadius: '4px',
                        }}
                      />
                    </div>
                  </div>

                  <div>
                    <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '13px', marginBottom: '4px' }}>
                      <span style={{ color: '#64748b' }}>Landing Visit Conversion (Clicks → Visits)</span>
                      <strong style={{ color: '#1e293b' }}>{summary.rates?.landing_conversion_rate || '0.00'}%</strong>
                    </div>
                    <div style={{ width: '100%', height: '8px', background: '#f1f5f9', borderRadius: '4px', overflow: 'hidden' }}>
                      <div
                        style={{
                          width: `${Math.min(100, parseFloat(summary.rates?.landing_conversion_rate || 0))}%`,
                          height: '100%',
                          background: '#9333ea',
                          borderRadius: '4px',
                        }}
                      />
                    </div>
                  </div>

                  <div>
                    <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '13px', marginBottom: '4px' }}>
                      <span style={{ color: '#64748b' }}>Reward Qualification Rate (Visits → Rewarded)</span>
                      <strong style={{ color: '#1e293b' }}>{summary.rates?.reward_conversion_rate || '0.00'}%</strong>
                    </div>
                    <div style={{ width: '100%', height: '8px', background: '#f1f5f9', borderRadius: '4px', overflow: 'hidden' }}>
                      <div
                        style={{
                          width: `${Math.min(100, parseFloat(summary.rates?.reward_conversion_rate || 0))}%`,
                          height: '100%',
                          background: '#10b981',
                          borderRadius: '4px',
                        }}
                      />
                    </div>
                  </div>
                </div>
              </div>

              {/* Right: Qualified Rewards & Payout Summary */}
              <div>
                <h4 style={{ fontSize: '13.5px', fontWeight: 700, color: '#334155', margin: '0 0 14px 0' }}>
                  Qualified Rewards &amp; Payout Summary
                </h4>
                <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
                  <div
                    style={{
                      padding: '12px 16px',
                      background: '#f8fafc',
                      borderRadius: '10px',
                      border: '1px solid #e2e8f0',
                      display: 'flex',
                      justifyContent: 'space-between',
                      alignItems: 'center',
                    }}
                  >
                    <span style={{ fontSize: '13px', fontWeight: 600, color: '#64748b' }}>
                      Unique Qualified Rewarded Users
                    </span>
                    <span style={{ fontSize: '14px', fontWeight: 700, color: '#16a34a' }}>
                      {summary.total_rewards_count || 0} members
                    </span>
                  </div>

                  <div
                    style={{
                      padding: '12px 16px',
                      background: '#f8fafc',
                      borderRadius: '10px',
                      border: '1px solid #e2e8f0',
                      display: 'flex',
                      justifyContent: 'space-between',
                      alignItems: 'center',
                    }}
                  >
                    <span style={{ fontSize: '13px', fontWeight: 600, color: '#64748b' }}>
                      Total Dynamic Rewards Credited
                    </span>
                    <span style={{ fontSize: '14px', fontWeight: 700, color: '#2563eb' }}>
                      {formatMoney(summary.total_rewards_paid || 0)}
                    </span>
                  </div>

                  <div
                    style={{
                      padding: '12px 16px',
                      background: '#f8fafc',
                      borderRadius: '10px',
                      border: '1px solid #e2e8f0',
                      display: 'flex',
                      justifyContent: 'space-between',
                      alignItems: 'center',
                    }}
                  >
                    <span style={{ fontSize: '13px', fontWeight: 600, color: '#64748b' }}>
                      Remaining Campaign Budget
                    </span>
                    <span style={{ fontSize: '14px', fontWeight: 700, color: '#0f172a' }}>
                      {formatMoney(summary.remaining_budget || 0)}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Main Audience Table / Grid Section (Event-style Directory) */}
      <section
        className="card"
        style={{
          background: '#ffffff',
          borderRadius: '24px',
          border: '1px solid #e2e8f0',
          padding: '24px',
          boxShadow: '0 4px 16px rgba(0, 0, 0, 0.04)',
        }}
      >
        {/* Filter Navigation Tabs Bar */}
        <div
          style={{
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            flexWrap: 'wrap',
            gap: '16px',
            marginBottom: '20px',
            paddingBottom: '16px',
            borderBottom: '1px solid #f1f5f9',
          }}
        >
          {/* Section Header with Qualified Count */}
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
              <Users size={18} color="#2563eb" />
              <span style={{ fontSize: '15px', fontWeight: 700, color: '#0f172a' }}>People Engaged</span>
            </div>
            <span
              style={{
                padding: '3px 10px',
                borderRadius: '12px',
                fontSize: '12px',
                fontWeight: 700,
                backgroundColor: '#ecfdf5',
                color: '#059669',
              }}
            >
              Qualified &amp; Rewarded ({engagementsPaginated.total})
            </span>
          </div>

          {/* Search & View Mode Toggle */}
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px', flexWrap: 'wrap' }}>
            <div style={{ position: 'relative', minWidth: '240px' }}>
              <Search
                size={15}
                color="#94a3b8"
                style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)' }}
              />
              <input
                type="text"
                placeholder="Search name, phone, email, username..."
                value={searchQuery}
                onChange={(e) => {
                  setSearchQuery(e.target.value);
                  setCurrentPage(1);
                }}
                className="form-control"
                style={{
                  width: '100%',
                  padding: '8px 12px 8px 34px',
                  borderRadius: '10px',
                  border: '1px solid #cbd5e1',
                  fontSize: '13px',
                }}
              />
            </div>

            <div style={{ display: 'flex', background: '#f1f5f9', borderRadius: '10px', padding: '2px' }}>
              <button
                type="button"
                onClick={() => setViewMode('table')}
                style={{
                  border: 'none',
                  background: viewMode === 'table' ? '#ffffff' : 'transparent',
                  color: viewMode === 'table' ? '#0f172a' : '#64748b',
                  padding: '6px 10px',
                  borderRadius: '8px',
                  cursor: 'pointer',
                  boxShadow: viewMode === 'table' ? '0 1px 3px rgba(0,0,0,0.1)' : 'none',
                }}
                title="Table View"
              >
                <List size={16} />
              </button>
              <button
                type="button"
                onClick={() => setViewMode('grid')}
                style={{
                  border: 'none',
                  background: viewMode === 'grid' ? '#ffffff' : 'transparent',
                  color: viewMode === 'grid' ? '#0f172a' : '#64748b',
                  padding: '6px 10px',
                  borderRadius: '8px',
                  cursor: 'pointer',
                  boxShadow: viewMode === 'grid' ? '0 1px 3px rgba(0,0,0,0.1)' : 'none',
                }}
                title="Grid Cards View"
              >
                <LayoutGrid size={16} />
              </button>
            </div>
          </div>
        </div>

        {/* Secondary Filter Controls Bar (Verification, Date Preset, Sorting) */}
        <div
          style={{
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            flexWrap: 'wrap',
            gap: '12px',
            marginBottom: '18px',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px', flexWrap: 'wrap' }}>
            <select
              value={verificationFilter}
              onChange={(e) => {
                setVerificationFilter(e.target.value);
                setCurrentPage(1);
              }}
              className="biz-filter-select"
              style={{ padding: '7px 12px', fontSize: '12.5px', borderRadius: '8px' }}
            >
              <option value="all">All Verification</option>
              <option value="verified">Verified Only</option>
              <option value="unverified">Unverified Only</option>
            </select>

            <select
              value={datePreset}
              onChange={(e) => {
                setDatePreset(e.target.value);
                setCurrentPage(1);
              }}
              className="biz-filter-select"
              style={{ padding: '7px 12px', fontSize: '12.5px', borderRadius: '8px' }}
            >
              <option value="all">All Time</option>
              <option value="today">Today</option>
              <option value="last_7_days">Last 7 Days</option>
              <option value="last_30_days">Last 30 Days</option>
              <option value="custom">Custom Range</option>
            </select>

            {datePreset === 'custom' && (
              <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                <input
                  type="date"
                  value={startDate}
                  onChange={(e) => {
                    setStartDate(e.target.value);
                    setCurrentPage(1);
                  }}
                  className="biz-search-input"
                  style={{ padding: '5px 10px', fontSize: '12px', borderRadius: '8px' }}
                />
                <span style={{ fontSize: '12px', color: '#94a3b8' }}>to</span>
                <input
                  type="date"
                  value={endDate}
                  onChange={(e) => {
                    setEndDate(e.target.value);
                    setCurrentPage(1);
                  }}
                  className="biz-search-input"
                  style={{ padding: '5px 10px', fontSize: '12px', borderRadius: '8px' }}
                />
              </div>
            )}

            <select
              value={sortOrder}
              onChange={(e) => {
                setSortOrder(e.target.value);
                setCurrentPage(1);
              }}
              className="biz-filter-select"
              style={{ padding: '7px 12px', fontSize: '12.5px', borderRadius: '8px' }}
            >
              <option value="newest">Newest First</option>
              <option value="oldest">Oldest First</option>
              <option value="name_asc">Name A-Z</option>
            </select>

            {hasActiveFilters && (
              <button
                type="button"
                className="member-button member-button--secondary"
                onClick={handleResetFilters}
                style={{ padding: '6px 12px', fontSize: '12px', borderRadius: '8px' }}
              >
                <RotateCcw size={12} />
                <span>Reset Filters</span>
              </button>
            )}
          </div>

          <div style={{ fontSize: '13px', color: '#64748b' }}>
            Showing <strong>{engagementItems.length}</strong> of <strong>{engagementsPaginated.total}</strong> results
          </div>
        </div>

        {/* Bulk Selection Action Bar */}
        {selectedMemberIds.length > 0 && (
          <div
            style={{
              padding: '12px 18px',
              borderRadius: '12px',
              backgroundColor: '#eff6ff',
              border: '1px solid #bfdbfe',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              marginBottom: '16px',
              flexWrap: 'wrap',
              gap: '12px',
            }}
          >
            <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
              <span style={{ fontSize: '13px', fontWeight: 700, color: '#1e40af' }}>
                {selectedMemberIds.length} members selected
              </span>
              <button
                type="button"
                className="btn btn-link"
                onClick={handleClearSelection}
                style={{ fontSize: '12px', color: '#3b82f6', padding: 0, textDecoration: 'underline' }}
              >
                Clear Selection
              </button>
            </div>

            <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
              <button
                type="button"
                className="member-button member-button--primary"
                onClick={() => handleOpenContactModal()}
                style={{ padding: '6px 14px', fontSize: '12.5px', borderRadius: '8px' }}
              >
                <MessageCircle size={14} />
                <span>Contact Selected</span>
              </button>

              <button
                type="button"
                className="member-button member-button--secondary"
                onClick={() => handleExportCSV('audience', true)}
                style={{ padding: '6px 14px', fontSize: '12.5px', borderRadius: '8px' }}
              >
                <Download size={14} />
                <span>Export Selected</span>
              </button>
            </div>
          </div>
        )}

        {/* Audience Content Table / Grid */}
        {engagementItems.length === 0 ? (
          <div style={{ textAlign: 'center', padding: '60px 20px', color: '#64748b' }}>
            <Users size={44} color="#cbd5e1" style={{ margin: '0 auto 12px' }} />
            <h3 style={{ fontSize: '17px', fontWeight: 700, margin: '0 0 6px 0', color: '#334155' }}>
              No audience members found matching criteria
            </h3>
            <p style={{ fontSize: '13.5px', margin: '0 0 16px 0', maxWidth: '440px', marginLeft: 'auto', marginRight: 'auto' }}>
              {hasActiveFilters
                ? 'Try clearing or modifying your search keywords and filter settings.'
                : 'When members interact with your sponsored campaign, their engagement history and contact details will appear here.'}
            </p>
            {hasActiveFilters && (
              <button
                type="button"
                className="member-button member-button--secondary"
                onClick={handleResetFilters}
                style={{ padding: '8px 16px', fontSize: '13px', borderRadius: '10px' }}
              >
                Clear All Filters
              </button>
            )}
          </div>
        ) : viewMode === 'table' ? (
          /* Table View (Events-style Table Layout) */
          <div style={{ overflowX: 'auto', width: '100%', borderRadius: '12px' }}>
            <table
              style={{
                width: '100%',
                minWidth: '880px',
                borderCollapse: 'collapse',
                textAlign: 'left',
                fontSize: '13.5px',
              }}
            >
              <thead>
                <tr
                  style={{
                    borderBottom: '2px solid #e2e8f0',
                    color: '#64748b',
                    fontSize: '12px',
                    textTransform: 'uppercase',
                    letterSpacing: '0.04em',
                    backgroundColor: '#f8fafc',
                  }}
                >
                  <th style={{ padding: '14px 16px', width: '48px', textAlign: 'center' }}>
                    <input
                      type="checkbox"
                      checked={
                        engagementItems.length > 0 &&
                        engagementItems.every((i) => selectedMemberIds.includes(i.user?.id))
                      }
                      onChange={handleSelectAllOnPage}
                      style={{ cursor: 'pointer', width: '16px', height: '16px', accentColor: '#2563eb' }}
                    />
                  </th>
                  <th style={{ padding: '14px 18px', width: '30%', minWidth: '240px' }}>Member</th>
                  <th style={{ padding: '14px 18px', width: '20%', minWidth: '160px' }}>Activity Type</th>
                  <th style={{ padding: '14px 18px', width: '50%', minWidth: '380px' }}>Contact Options</th>
                </tr>
              </thead>
              <tbody>
                {engagementItems.map((item, index) => {
                  const m = item.user || {};
                  const isSelected = selectedMemberIds.includes(m.id);
                  const cleanPhone = getCleanWhatsAppNumber(m.phone);
                  const avatarUrl = getAvatarUrl(m.profile_photo);
                  const initials = getInitials(m.name || 'Member');
                  const waText = encodeURIComponent(`Hi ${m.name || 'there'}, regarding our ad campaign "${campaign.campaign_name || 'Campaign'}":`);
                  const emailSubject = encodeURIComponent(`Regarding Campaign: ${campaign.campaign_name || 'Sponsored Campaign'}`);
                  const emailBody = encodeURIComponent(`Hi ${m.name || 'there'},\n\nThank you for engaging with "${campaign.campaign_name || 'our campaign'}".\n\n`);

                  return (
                    <tr
                      key={item.id || index}
                      style={{
                        borderBottom: '1px solid #f1f5f9',
                        backgroundColor: isSelected ? '#f8fafc' : 'transparent',
                        transition: 'background-color 0.15s ease',
                      }}
                      className="event-attendee-row"
                    >
                      {/* Checkbox */}
                      <td style={{ padding: '14px 16px', textAlign: 'center', verticalAlign: 'middle' }}>
                        <input
                          type="checkbox"
                          checked={isSelected}
                          onChange={() => handleToggleSelectMember(m.id)}
                          style={{ cursor: 'pointer', width: '16px', height: '16px', accentColor: '#2563eb' }}
                        />
                      </td>

                      {/* Member Info */}
                      <td style={{ padding: '14px 18px', verticalAlign: 'middle' }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                          <Link to={`/member/people/${m.id}`} style={{ flexShrink: 0, textDecoration: 'none' }}>
                            <MemberAvatar member={m} size={42} />
                          </Link>

                          <div style={{ minWidth: 0 }}>
                            <Link
                              to={`/member/people/${m.id}`}
                              style={{
                                fontWeight: 700,
                                color: '#0f172a',
                                textDecoration: 'none',
                                display: 'inline-flex',
                                alignItems: 'center',
                                gap: '5px',
                                fontSize: '13.5px',
                              }}
                            >
                              <span style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                                {m.name || 'Anonymous User'}
                              </span>
                              <VerifiedBadge member={m} size={14} />
                            </Link>
                            <span style={{ fontSize: '12px', color: '#64748b', display: 'block' }}>
                              @{m.username || m.user_id || `member_${m.id}`}
                            </span>
                          </div>
                        </div>
                      </td>

                      {/* Activity Type */}
                      <td style={{ padding: '14px 18px', verticalAlign: 'middle' }}>
                        <span
                          style={{
                            padding: '4px 10px',
                            borderRadius: '8px',
                            fontSize: '12px',
                            fontWeight: 700,
                            backgroundColor: '#ecfdf5',
                            color: '#065f46',
                            border: '1px solid #a7f3d0',
                            display: 'inline-flex',
                            alignItems: 'center',
                            gap: '5px',
                          }}
                        >
                          <CheckCircle2 size={13} color="#059669" />
                          <span>{item.action_label || 'Rewarded Visit'}</span>
                        </span>
                      </td>

                      {/* Contact Options (Mobile/WhatsApp Number + Action Buttons) */}
                      <td style={{ padding: '14px 18px', verticalAlign: 'middle' }}>
                        <div
                          style={{
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'space-between',
                            gap: '16px',
                            flexWrap: 'nowrap',
                          }}
                        >
                          {/* Authorized Mobile/WhatsApp Number */}
                          {m.phone ? (
                            <div style={{ display: 'flex', alignItems: 'center', gap: '6px', whiteSpace: 'nowrap' }}>
                              <Phone size={13} color="#059669" style={{ flexShrink: 0 }} />
                              <strong style={{ color: '#0f172a', fontSize: '13px', fontFamily: 'monospace', fontWeight: 700 }}>
                                {m.phone}
                              </strong>
                              <button
                                type="button"
                                onClick={() => handleCopy(m.phone, `phone-${m.id || index}`)}
                                style={{
                                  background: 'transparent',
                                  border: 'none',
                                  cursor: 'pointer',
                                  padding: '2px 4px',
                                  color: copiedKey === `phone-${m.id || index}` ? '#10b981' : '#94a3b8',
                                  display: 'inline-flex',
                                  alignItems: 'center',
                                }}
                                title="Copy mobile number"
                              >
                                {copiedKey === `phone-${m.id || index}` ? <Check size={12} /> : <Copy size={12} />}
                              </button>
                            </div>
                          ) : (
                            <span style={{ color: '#94a3b8', fontSize: '12.5px', fontStyle: 'italic', whiteSpace: 'nowrap' }}>
                              Not Available
                            </span>
                          )}

                          {/* Quick Contact Action Buttons */}
                          <div style={{ display: 'inline-flex', alignItems: 'center', gap: '6px', flexShrink: 0 }}>
                            {cleanPhone && (
                              <a
                                href={`https://wa.me/${cleanPhone}?text=${waText}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="member-button"
                                style={{
                                  background: '#25D366',
                                  color: '#ffffff',
                                  border: 'none',
                                  padding: '6px 12px',
                                  fontSize: '12px',
                                  fontWeight: 700,
                                  borderRadius: '8px',
                                  textDecoration: 'none',
                                  display: 'inline-flex',
                                  alignItems: 'center',
                                  gap: '5px',
                                }}
                                title={`Chat on WhatsApp (${m.phone})`}
                              >
                                <MessageCircle size={13} />
                                <span>WhatsApp</span>
                              </a>
                            )}

                            {m.phone && (
                              <a
                                href={`tel:${m.phone}`}
                                className="member-button member-button--secondary"
                                style={{
                                  padding: '6px 12px',
                                  fontSize: '12px',
                                  borderRadius: '8px',
                                  textDecoration: 'none',
                                  display: 'inline-flex',
                                  alignItems: 'center',
                                  gap: '5px',
                                }}
                                title={`Call ${m.phone}`}
                              >
                                <Phone size={13} />
                                <span>Call</span>
                              </a>
                            )}

                            {m.email && (
                              <a
                                href={`mailto:${m.email}?subject=${emailSubject}&body=${emailBody}`}
                                className="member-button member-button--secondary"
                                style={{
                                  padding: '6px 12px',
                                  fontSize: '12px',
                                  borderRadius: '8px',
                                  textDecoration: 'none',
                                  display: 'inline-flex',
                                  alignItems: 'center',
                                  gap: '5px',
                                }}
                                title={`Send Email to ${m.email}`}
                              >
                                <Mail size={13} />
                                <span>Email</span>
                              </a>
                            )}
                          </div>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        ) : (
          /* Grid Cards View (Responsive, Non-Overflowing with Profile Navigation) */
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(310px, 1fr))', gap: '18px' }}>
            {engagementItems.map((item, index) => {
              const m = item.user || {};
              const isSelected = selectedMemberIds.includes(m.id);
              const cleanPhone = getCleanWhatsAppNumber(m.phone);
              const avatarUrl = getAvatarUrl(m.profile_photo);
              const initials = getInitials(m.name || 'Member');
              const waText = encodeURIComponent(`Hi ${m.name || 'there'}, regarding our ad campaign "${campaign.campaign_name || 'Campaign'}":`);
              const emailSubject = encodeURIComponent(`Regarding Campaign: ${campaign.campaign_name || 'Sponsored Campaign'}`);
              const emailBody = encodeURIComponent(`Hi ${m.name || 'there'},\n\nThank you for engaging with "${campaign.campaign_name || 'our campaign'}".\n\n`);

              return (
                <div
                  key={item.id || index}
                  className="card"
                  style={{
                    padding: '18px',
                    borderRadius: '16px',
                    border: isSelected ? '1px solid #bfdbfe' : '1px solid #e2e8f0',
                    backgroundColor: isSelected ? '#f8fafc' : '#ffffff',
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '14px',
                    boxShadow: isSelected ? '0 4px 12px rgba(37, 99, 235, 0.08)' : '0 1px 3px rgba(0, 0, 0, 0.04)',
                    transition: 'border-color 0.15s, background-color 0.15s, box-shadow 0.15s',
                  }}
                >
                  {/* Card Header: Checkbox + Avatar + Member Identity + Rewarded Visit Badge */}
                  <div
                    style={{
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'space-between',
                      gap: '10px',
                      flexWrap: 'wrap',
                      borderBottom: '1px solid #f1f5f9',
                      paddingBottom: '12px',
                    }}
                  >
                    {/* Left: Checkbox + Avatar + Member Info */}
                    <div
                      style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: '10px',
                        flex: '1 1 180px',
                        minWidth: 0,
                      }}
                    >
                      <input
                        type="checkbox"
                        checked={isSelected}
                        onChange={() => handleToggleSelectMember(m.id)}
                        style={{
                          cursor: 'pointer',
                          width: '16px',
                          height: '16px',
                          accentColor: '#2563eb',
                          flexShrink: 0,
                        }}
                        aria-label={`Select ${m.name || 'Member'}`}
                      />

                      {/* Avatar Link */}
                      <Link
                        to={`/member/people/${m.id}`}
                        style={{
                          flexShrink: 0,
                          textDecoration: 'none',
                          display: 'block',
                          borderRadius: '50%',
                        }}
                        title={`View ${m.name || 'Member'}'s profile`}
                      >
                        <MemberAvatar member={m} size={40} />
                      </Link>

                      {/* Member Info: Name + Verified Badge + Username */}
                      <div style={{ flex: 1, minWidth: 0 }}>
                        <Link
                          to={`/member/people/${m.id}`}
                          style={{
                            fontWeight: 700,
                            color: '#0f172a',
                            textDecoration: 'none',
                            display: 'inline-flex',
                            alignItems: 'center',
                            gap: '4px',
                            fontSize: '13.5px',
                            maxWidth: '100%',
                          }}
                          title={`View ${m.name || 'Member'}'s profile`}
                        >
                          <span
                            style={{
                              overflow: 'hidden',
                              textOverflow: 'ellipsis',
                              whiteSpace: 'nowrap',
                              display: 'inline-block',
                            }}
                          >
                            {m.name || 'Anonymous User'}
                          </span>
                          <VerifiedBadge member={m} size={14} />
                        </Link>

                        <Link
                          to={`/member/people/${m.id}`}
                          style={{
                            fontSize: '12px',
                            color: '#64748b',
                            display: 'block',
                            textDecoration: 'none',
                            overflow: 'hidden',
                            textOverflow: 'ellipsis',
                            whiteSpace: 'nowrap',
                            marginTop: '1px',
                          }}
                          title={`@${m.username || m.user_id || `member_${m.id}`}`}
                        >
                          @{m.username || m.user_id || `member_${m.id}`}
                        </Link>
                      </div>
                    </div>

                    {/* Right: Rewarded Visit Badge */}
                    <div style={{ flexShrink: 0, marginLeft: 'auto' }}>
                      <span
                        style={{
                          padding: '3px 8px',
                          borderRadius: '6px',
                          fontSize: '11px',
                          fontWeight: 700,
                          backgroundColor: '#ecfdf5',
                          color: '#065f46',
                          border: '1px solid #a7f3d0',
                          display: 'inline-flex',
                          alignItems: 'center',
                          gap: '4px',
                          whiteSpace: 'nowrap',
                        }}
                      >
                        <CheckCircle2 size={12} color="#059669" />
                        <span>{item.action_label || 'Rewarded Visit'}</span>
                      </span>
                    </div>
                  </div>

                  {/* Contact Information Box */}
                  <div
                    style={{
                      padding: '10px 12px',
                      background: '#f8fafc',
                      borderRadius: '10px',
                      border: '1px solid #f1f5f9',
                      fontSize: '12.5px',
                      display: 'flex',
                      justifyContent: 'space-between',
                      alignItems: 'center',
                      gap: '8px',
                    }}
                  >
                    <div style={{ display: 'flex', alignItems: 'center', gap: '6px', minWidth: 0 }}>
                      <Phone size={13} color="#059669" style={{ flexShrink: 0 }} />
                      <span style={{ color: '#64748b', fontSize: '12px' }}>Mobile / WA:</span>
                      <strong
                        style={{
                          fontFamily: 'monospace',
                          color: m.phone ? '#0f172a' : '#94a3b8',
                          fontSize: '12.5px',
                          overflow: 'hidden',
                          textOverflow: 'ellipsis',
                          whiteSpace: 'nowrap',
                        }}
                      >
                        {m.phone || 'Not Available'}
                      </strong>
                    </div>

                    {m.phone && (
                      <button
                        type="button"
                        onClick={() => handleCopy(m.phone, `card-phone-${m.id || index}`)}
                        style={{
                          background: 'transparent',
                          border: 'none',
                          cursor: 'pointer',
                          padding: '2px 4px',
                          color: copiedKey === `card-phone-${m.id || index}` ? '#10b981' : '#94a3b8',
                          display: 'inline-flex',
                          alignItems: 'center',
                          flexShrink: 0,
                        }}
                        title="Copy mobile number"
                        aria-label="Copy mobile number"
                      >
                        {copiedKey === `card-phone-${m.id || index}` ? <Check size={13} /> : <Copy size={13} />}
                      </button>
                    )}
                  </div>

                  {/* Quick Contact Action Buttons */}
                  <div
                    style={{
                      display: 'flex',
                      alignItems: 'center',
                      gap: '8px',
                      flexWrap: 'wrap',
                      marginTop: 'auto',
                      paddingTop: '2px',
                    }}
                  >
                    {cleanPhone && (
                      <a
                        href={`https://wa.me/${cleanPhone}?text=${waText}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="member-button"
                        style={{
                          padding: '6px 12px',
                          borderRadius: '8px',
                          backgroundColor: '#25D366',
                          color: '#ffffff',
                          border: 'none',
                          fontSize: '12px',
                          fontWeight: 700,
                          textDecoration: 'none',
                          display: 'inline-flex',
                          alignItems: 'center',
                          gap: '5px',
                          flex: '1 1 auto',
                          justifyContent: 'center',
                        }}
                        title={`Chat on WhatsApp (${m.phone})`}
                      >
                        <MessageCircle size={13} />
                        <span>WhatsApp</span>
                      </a>
                    )}

                    {m.phone && (
                      <a
                        href={`tel:${m.phone}`}
                        className="member-button member-button--secondary"
                        style={{
                          padding: '6px 12px',
                          borderRadius: '8px',
                          fontSize: '12px',
                          textDecoration: 'none',
                          display: 'inline-flex',
                          alignItems: 'center',
                          gap: '5px',
                          flex: '1 1 auto',
                          justifyContent: 'center',
                        }}
                        title={`Call ${m.phone}`}
                      >
                        <Phone size={13} />
                        <span>Call</span>
                      </a>
                    )}

                    {m.email && (
                      <a
                        href={`mailto:${m.email}?subject=${emailSubject}&body=${emailBody}`}
                        className="member-button member-button--secondary"
                        style={{
                          padding: '6px 12px',
                          borderRadius: '8px',
                          fontSize: '12px',
                          textDecoration: 'none',
                          display: 'inline-flex',
                          alignItems: 'center',
                          gap: '5px',
                          flex: '1 1 auto',
                          justifyContent: 'center',
                        }}
                        title={`Send Email to ${m.email}`}
                      >
                        <Mail size={13} />
                        <span>Email</span>
                      </a>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        )}

        {/* Server-Side Pagination Controls */}
        {engagementsPaginated.last_page > 1 && (
          <div
            style={{
              marginTop: '24px',
              paddingTop: '16px',
              borderTop: '1px solid #f1f5f9',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              flexWrap: 'wrap',
              gap: '12px',
            }}
          >
            <span style={{ fontSize: '13px', color: '#64748b' }}>
              Page <strong>{engagementsPaginated.current_page}</strong> of{' '}
              <strong>{engagementsPaginated.last_page}</strong>
            </span>

            <div style={{ display: 'flex', gap: '6px' }}>
              <button
                type="button"
                className="member-button member-button--secondary"
                disabled={currentPage <= 1}
                onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
                style={{ padding: '6px 12px', fontSize: '12.5px', borderRadius: '8px' }}
              >
                <ChevronLeft size={14} />
                <span>Previous</span>
              </button>

              <button
                type="button"
                className="member-button member-button--secondary"
                disabled={currentPage >= engagementsPaginated.last_page}
                onClick={() => setCurrentPage((p) => Math.min(engagementsPaginated.last_page, p + 1))}
                style={{ padding: '6px 12px', fontSize: '12.5px', borderRadius: '8px' }}
              >
                <span>Next</span>
                <ChevronRight size={14} />
              </button>
            </div>
          </div>
        )}
      </section>

      {/* Top-Up Campaign Modal */}
      {showTopUpModal && (
        <AddFundsToCampaignModal
          page={businessPage}
          campaign={campaign}
          availableAdFunds={Number(data?.metrics?.available_ad_funds ?? data?.metrics?.member_ad_balance ?? 0)}
          platformFeePercent={Number(data?.metrics?.campaign_platform_fee_percent ?? 2.5)}
          onClose={() => setShowTopUpModal(false)}
          onCampaignUpdated={() => {
            setShowTopUpModal(false);
            fetchEngagements(true);
          }}
          onOpenDepositModal={() => {
            setShowTopUpModal(false);
            setShowAddFundModal(true);
          }}
        />
      )}

      {/* Deposit Ad Funds Modal */}
      {showAddFundModal && (
        <AddFundModal
          page={businessPage}
          isOpen={showAddFundModal}
          onClose={() => setShowAddFundModal(false)}
          onDepositSubmitted={() => {
            fetchEngagements(true);
          }}
        />
      )}
    </div>
  );
}

export default AdCampaignPeopleEngagedPage;