import { useState, useEffect, useCallback, useMemo } from 'react';
import { Link } from 'react-router-dom';
import {
  X,
  Megaphone,
  Calendar,
  AlertTriangle,
  AlertCircle,
  CheckCircle2,
  PauseCircle,
  PlayCircle,
  StopCircle,
  ShieldCheck,
  Clock,
  Send,
  FileText,
  MousePointerClick,
  TrendingUp,
  DollarSign,
  Users,
  Search,
  RefreshCw,
  Gift,
  ExternalLink,
  ChevronLeft,
  ChevronRight,
  MessageSquare,
  Phone,
  Mail,
  Copy,
  Check,
  Star,
  LayoutGrid,
  LayoutList,
  Download,
  Sparkles,
  ShieldAlert,
  Award,
  RotateCcw,
  PhoneOff,
  MailX,
  BarChart2,
  FileSpreadsheet,
} from 'lucide-react';
import businessApi from '../../../api/businessApi';
import { getAvatarUrl, getMediaUrl } from '../../../utils/assetHelper';
import { VerifiedBadge } from '../../../components/common/VerifiedBadge';
import { ModalPortal } from '../../common/ModalPortal';
import '../../../styles/member-business-pages.css';

export function AdCampaignDetailModal({
  page,
  campaign,
  isOwner = false,
  initialTab = 'overview',
  onClose,
  onCampaignUpdated,
  onOpenTopUp = null,
}) {
  const [currentCampaign, setCurrentCampaign] = useState(campaign);
  const [activeTab, setActiveTab] = useState(initialTab); // 'overview' | 'engagements' | 'analytics'

  // Actions & feedback state
  const [isActionLoading, setIsActionLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');
  const [successMsg, setSuccessMsg] = useState('');

  // Search & Filtering state
  const [searchInputValue, setSearchInputValue] = useState('');
  const [engagementSearch, setEngagementSearch] = useState('');
  const [engagementActionFilter, setEngagementActionFilter] = useState('all'); // all, interested, clicked, visited_landing_page, rewarded
  const [rewardStatusFilter, setRewardStatusFilter] = useState('all'); // all, rewarded, not_rewarded, failed
  const [verificationFilter, setVerificationFilter] = useState('all'); // all, verified, unverified
  const [datePreset, setDatePreset] = useState('all'); // all, today, yesterday, last_7_days, last_30_days, custom
  const [customStartDate, setCustomStartDate] = useState('');
  const [customEndDate, setCustomEndDate] = useState('');
  const [sortOrder, setSortOrder] = useState('newest'); // newest, oldest, highest_reward, lowest_reward

  // Audience Member Selection state
  const [selectedMemberIds, setSelectedMemberIds] = useState([]);

  // Server-Side Export State
  const [isExporting, setIsExporting] = useState(false);
  const [exportDropdownOpen, setExportDropdownOpen] = useState(false);

  // Bulk Contact Center Modal State
  const [bulkContactModalOpen, setBulkContactModalOpen] = useState(false);
  const [contactMethod, setContactMethod] = useState('whatsapp'); // 'whatsapp' | 'email' | 'copy'
  const [contactValidationData, setContactValidationData] = useState(null);
  const [isValidatingContact, setIsValidatingContact] = useState(false);
  const [customContactMessage, setCustomContactMessage] = useState('');

  // Pagination & items state
  const [engagements, setEngagements] = useState([]);
  const [engagementsLoading, setEngagementsLoading] = useState(false);
  const [engagementsError, setEngagementsError] = useState('');
  const [engagementPage, setEngagementPage] = useState(1);
  const [filteredUniqueMembers, setFilteredUniqueMembers] = useState(0);
  const [engagementPagination, setEngagementPagination] = useState({
    total: 0,
    current_page: 1,
    last_page: 1,
    per_page: 15,
  });
  const [engagementSummary, setEngagementSummary] = useState({
    total_engagements: 0,
    total_engaged_members: 0,
    interested_count: 0,
    click_count: 0,
    landing_visit_count: 0,
    reward_qualified_count: 0,
    total_rewards_count: 0,
    total_rewards_paid: 0,
    remaining_budget: 0,
    verified_members_count: 0,
    financials: {
      original_budget: 0,
      additional_funding: 0,
      total_funded: 0,
      platform_fee_percent: 0,
      platform_fee_amount: 0,
      spent_amount: 0,
      remaining_budget: 0,
    },
    rates: {
      ctr_percent: 0,
      landing_conversion_rate: 0,
      reward_conversion_rate: 0,
    },
    tier_breakdown: [],
    daily_trends: [],
  });

  // UI modes & tools
  const [viewMode, setViewMode] = useState('table'); // 'table' | 'grid'
  const [copiedKey, setCopiedKey] = useState(null);

  // Dedicated Member Detail Drill-Down Modal state
  const [selectedMemberModal, setSelectedMemberModal] = useState(null);
  const [memberDetailLoading, setMemberDetailLoading] = useState(false);
  const [memberDetailError, setMemberDetailError] = useState('');

  // Debounce search input (300ms)
  useEffect(() => {
    const timer = setTimeout(() => {
      setEngagementSearch(searchInputValue.trim());
      setEngagementPage(1);
    }, 300);
    return () => clearTimeout(timer);
  }, [searchInputValue]);

  // Fetch engagements from backend using authoritative multi-dimensional query
  const fetchEngagements = useCallback(async () => {
    if (!page?.slug || !currentCampaign) return;
    const cId = currentCampaign.campaign_id || currentCampaign.id;
    setEngagementsLoading(true);
    setEngagementsError('');

    try {
      const res = await businessApi.getCampaignEngagements(page.slug, cId, {
        action: engagementActionFilter !== 'all' ? engagementActionFilter : undefined,
        reward_status: rewardStatusFilter !== 'all' ? rewardStatusFilter : undefined,
        verification: verificationFilter !== 'all' ? verificationFilter : undefined,
        date_preset: datePreset !== 'all' ? datePreset : undefined,
        start_date: datePreset === 'custom' && customStartDate ? customStartDate : undefined,
        end_date: datePreset === 'custom' && customEndDate ? customEndDate : undefined,
        sort: sortOrder,
        q: engagementSearch || undefined,
        page: engagementPage,
        per_page: 15,
      });

      if (res.success && res.engagements) {
        setEngagements(res.engagements.data || []);
        setFilteredUniqueMembers(res.engagements.filtered_unique_members || 0);
        setEngagementPagination({
          total: res.engagements.total || 0,
          current_page: res.engagements.current_page || 1,
          last_page: res.engagements.last_page || 1,
          per_page: res.engagements.per_page || 15,
        });
        if (res.summary) {
          setEngagementSummary({
            total_engagements: Number(res.summary.total_engagements || 0),
            total_engaged_members: Number(res.summary.total_engaged_members || 0),
            interested_count: Number(res.summary.interested_count || 0),
            click_count: Number(res.summary.click_count || 0),
            landing_visit_count: Number(res.summary.landing_visit_count || 0),
            reward_qualified_count: Number(res.summary.reward_qualified_count || 0),
            total_rewards_count: Number(res.summary.total_rewards_count || 0),
            total_rewards_paid: Number(res.summary.total_rewards_paid || 0),
            remaining_budget: Number(res.summary.remaining_budget || 0),
            verified_members_count: Number(res.summary.verified_members_count || 0),
            financials: res.summary.financials || {
              original_budget: Number(currentCampaign.budget || 0),
              additional_funding: Number(currentCampaign.additional_funding || 0),
              total_funded: Number(currentCampaign.total_funded || 0),
              platform_fee_percent: 0,
              platform_fee_amount: Number(currentCampaign.fee_amount || 0),
              spent_amount: Number(currentCampaign.spent_amount || 0),
              remaining_budget: Number(currentCampaign.remaining_amount || 0),
            },
            rates: res.summary.rates || {
              ctr_percent: 0,
              landing_conversion_rate: 0,
              reward_conversion_rate: 0,
            },
            tier_breakdown: res.summary.tier_breakdown || [],
            daily_trends: res.summary.daily_trends || [],
          });
        }
      }
    } catch (err) {
      console.error('Failed to load campaign engagements:', err);
      setEngagementsError(err.response?.data?.message || 'Failed to load campaign audience records.');
    } finally {
      setEngagementsLoading(false);
    }
  }, [
    page,
    currentCampaign,
    engagementActionFilter,
    rewardStatusFilter,
    verificationFilter,
    datePreset,
    customStartDate,
    customEndDate,
    sortOrder,
    engagementSearch,
    engagementPage,
  ]);

  useEffect(() => {
    if (activeTab === 'engagements' || activeTab === 'analytics') {
      fetchEngagements();
    }
  }, [activeTab, fetchEngagements]);

  // Clear all filters back to defaults
  const handleClearAllFilters = () => {
    setSearchInputValue('');
    setEngagementSearch('');
    setEngagementActionFilter('all');
    setRewardStatusFilter('all');
    setVerificationFilter('all');
    setDatePreset('all');
    setCustomStartDate('');
    setCustomEndDate('');
    setSortOrder('newest');
    setEngagementPage(1);
  };

  // Determine if any filters are active
  const hasActiveFilters = useMemo(() => {
    return (
      Boolean(engagementSearch) ||
      engagementActionFilter !== 'all' ||
      rewardStatusFilter !== 'all' ||
      verificationFilter !== 'all' ||
      datePreset !== 'all' ||
      sortOrder !== 'newest'
    );
  }, [engagementSearch, engagementActionFilter, rewardStatusFilter, verificationFilter, datePreset, sortOrder]);

  // =========================================================
  // AUDIENCE MEMBER SELECTION LOGIC
  // =========================================================
  const currentPageMemberIds = useMemo(() => {
    return engagements
      .map((item) => item.user?.id)
      .filter((id) => Boolean(id));
  }, [engagements]);

  const uniqueCurrentPageMemberIds = useMemo(() => {
    return Array.from(new Set(currentPageMemberIds));
  }, [currentPageMemberIds]);

  const isAllOnPageSelected = useMemo(() => {
    if (uniqueCurrentPageMemberIds.length === 0) return false;
    return uniqueCurrentPageMemberIds.every((id) => selectedMemberIds.includes(id));
  }, [uniqueCurrentPageMemberIds, selectedMemberIds]);

  const handleToggleSelectMember = (memberId) => {
    if (!memberId) return;
    setSelectedMemberIds((prev) =>
      prev.includes(memberId) ? prev.filter((id) => id !== memberId) : [...prev, memberId]
    );
  };

  const handleToggleSelectAllOnPage = () => {
    if (isAllOnPageSelected) {
      setSelectedMemberIds((prev) =>
        prev.filter((id) => !uniqueCurrentPageMemberIds.includes(id))
      );
    } else {
      setSelectedMemberIds((prev) =>
        Array.from(new Set([...prev, ...uniqueCurrentPageMemberIds]))
      );
    }
  };

  const handleClearSelection = () => {
    setSelectedMemberIds([]);
  };

  // Selected items breakdown
  const selectedEngagements = useMemo(() => {
    return engagements.filter((item) => item.user?.id && selectedMemberIds.includes(item.user.id));
  }, [engagements, selectedMemberIds]);

  const selectedPhones = useMemo(() => {
    const phones = selectedEngagements
      .map((item) => item.user?.phone)
      .filter((p) => Boolean(p) && String(p).trim() !== '')
      .map((p) => String(p).trim());
    return Array.from(new Set(phones));
  }, [selectedEngagements]);

  const selectedEmails = useMemo(() => {
    const emails = selectedEngagements
      .map((item) => item.user?.email)
      .filter((e) => Boolean(e) && String(e).trim() !== '')
      .map((e) => String(e).trim());
    return Array.from(new Set(emails));
  }, [selectedEngagements]);

  // =========================================================
  // SERVER-SIDE CSV EXPORT ENGINE
  // =========================================================
  const handleServerExport = async (exportScope = 'filtered') => {
    if (!page?.slug || !currentCampaign) return;
    const cId = currentCampaign.campaign_id || currentCampaign.id;
    setIsExporting(true);
    setExportDropdownOpen(false);

    try {
      const params = exportScope === 'all' ? { mode: 'audience' } : {
        action: engagementActionFilter !== 'all' ? engagementActionFilter : undefined,
        reward_status: rewardStatusFilter !== 'all' ? rewardStatusFilter : undefined,
        verification: verificationFilter !== 'all' ? verificationFilter : undefined,
        date_preset: datePreset !== 'all' ? datePreset : undefined,
        start_date: datePreset === 'custom' && customStartDate ? customStartDate : undefined,
        end_date: datePreset === 'custom' && customEndDate ? customEndDate : undefined,
        sort: sortOrder,
        q: engagementSearch || undefined,
        selected_ids: exportScope === 'selected' ? selectedMemberIds.join(',') : undefined,
        mode: 'audience',
      };

      const blob = await businessApi.exportCampaignEngagements(page.slug, cId, params);
      const url = window.URL.createObjectURL(new Blob([blob], { type: 'text/csv;charset=utf-8;' }));
      const link = document.createElement('a');
      const filename = `Campaign_${cId}_${exportScope}_Audience_${new Date().toISOString().slice(0, 10)}.csv`;
      link.href = url;
      link.setAttribute('download', filename);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
    } catch (err) {
      console.error('Failed to export campaign audience:', err);
      // Fallback to client CSV if server stream encounters CORS/blob issue
      if (exportScope === 'selected') {
        handleExportSelectedCSVFallback();
      } else {
        handleExportAllCSVFallback();
      }
    } finally {
      setIsExporting(false);
    }
  };

  // Open Bulk Contact Center Confirmation Dialog
  const handleOpenBulkContactModal = async (method = 'whatsapp') => {
    if (selectedMemberIds.length === 0) return;
    setContactMethod(method);
    setBulkContactModalOpen(true);
    setIsValidatingContact(true);
    setContactValidationData(null);
    setCustomContactMessage(`Hi, regarding our ad campaign "${currentCampaign.campaign_name}" on MLM_Book:`);

    try {
      const cId = currentCampaign.campaign_id || currentCampaign.id;
      const res = await businessApi.contactCampaignAudience(page.slug, cId, {
        member_ids: selectedMemberIds,
        method: method,
      });

      if (res.success) {
        setContactValidationData(res);
      }
    } catch (err) {
      console.error('Failed to validate contact audience:', err);
      // Fallback to local validation
      setContactValidationData({
        total_requested: selectedMemberIds.length,
        total_valid_in_campaign: selectedMemberIds.length,
        contactable_count: method === 'email' ? selectedEmails.length : selectedPhones.length,
        unavailable_count: selectedMemberIds.length - (method === 'email' ? selectedEmails.length : selectedPhones.length),
        unauthorized_count: 0,
        contactable_members: selectedEngagements.map((e) => e.user).filter(Boolean),
        unavailable_members: [],
      });
    } finally {
      setIsValidatingContact(false);
    }
  };

  const handleExecuteBulkContact = () => {
    if (!contactValidationData) return;

    if (contactMethod === 'whatsapp') {
      const phones = contactValidationData.contactable_members
        ?.map((m) => m.phone)
        .filter(Boolean)
        .map((p) => String(p).replace(/[^0-9+]/g, ''));

      if (phones && phones.length > 0) {
        navigator.clipboard.writeText(phones.join(', '));
        setCopiedKey('bulk_whatsapp_executed');
        setTimeout(() => setCopiedKey(null), 3000);
        // Open first contactable WhatsApp link
        const firstPhone = phones[0];
        window.open(`https://wa.me/${firstPhone}?text=${encodeURIComponent(customContactMessage)}`, '_blank');
      }
    } else if (contactMethod === 'email') {
      const emails = contactValidationData.contactable_members
        ?.map((m) => m.email)
        .filter(Boolean);

      if (emails && emails.length > 0) {
        navigator.clipboard.writeText(emails.join(', '));
        setCopiedKey('bulk_email_executed');
        setTimeout(() => setCopiedKey(null), 3000);
        window.location.href = `mailto:${emails.join(',')}?subject=${encodeURIComponent(`MLM_Book Ad Campaign: ${currentCampaign.campaign_name}`)}&body=${encodeURIComponent(customContactMessage)}`;
      }
    } else {
      // Copy mode
      const details = contactValidationData.contactable_members
        ?.map((m) => `${m.name} (${m.phone || 'No phone'}, ${m.email || 'No email'})`)
        .join('\n');
      navigator.clipboard.writeText(details || '');
      setCopiedKey('bulk_copied_executed');
      setTimeout(() => setCopiedKey(null), 3000);
    }

    setBulkContactModalOpen(false);
  };

  const handleCopySelectedPhones = () => {
    if (selectedPhones.length === 0) {
      alert('None of the selected members have an authorized phone number.');
      return;
    }
    navigator.clipboard.writeText(selectedPhones.join(', '));
    setCopiedKey('selected_phones');
    setTimeout(() => setCopiedKey(null), 2500);
  };

  const handleCopySelectedEmails = () => {
    if (selectedEmails.length === 0) {
      alert('None of the selected members have an authorized email address.');
      return;
    }
    navigator.clipboard.writeText(selectedEmails.join(', '));
    setCopiedKey('selected_emails');
    setTimeout(() => setCopiedKey(null), 2500);
  };

  const handleExportSelectedCSVFallback = () => {
    if (selectedEngagements.length === 0) return;
    const headers = ['Member ID', 'Display Name', 'Username', 'Verified', 'Action Event', 'Reward Amount USD', 'Referral Tier Snapshot', 'Mobile / WhatsApp', 'Email', 'City', 'Country', 'Event Date & Time'];
    const rows = selectedEngagements.map((item) => [
      item.user?.id || '',
      `"${(item.user?.name || 'Visitor').replace(/"/g, '""')}"`,
      `"${(item.user?.username || '').replace(/"/g, '""')}"`,
      item.user?.is_verified ? 'Yes' : 'No',
      `"${(item.action_label || item.action || '').replace(/"/g, '""')}"`,
      item.reward_amount_usd ? item.reward_amount_usd.toFixed(4) : '0.0000',
      `"${(item.tier_label || '').replace(/"/g, '""')}"`,
      `"${(item.user?.phone || '').replace(/"/g, '""')}"`,
      `"${(item.user?.email || '').replace(/"/g, '""')}"`,
      `"${(item.user?.city || '').replace(/"/g, '""')}"`,
      `"${(item.user?.country || '').replace(/"/g, '""')}"`,
      `"${item.created_at ? new Date(item.created_at).toLocaleString() : ''}"`,
    ]);

    const csvContent = [headers.join(','), ...rows.map((r) => r.join(','))].join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `Campaign_${currentCampaign.campaign_id || currentCampaign.id}_Selected_${selectedMemberIds.length}_Members.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  const handleExportAllCSVFallback = () => {
    if (engagements.length === 0) return;
    const headers = ['Member ID', 'Display Name', 'Username', 'Verified', 'Action Event', 'Reward Amount USD', 'Referral Tier Snapshot', 'Mobile / WhatsApp', 'Email', 'City', 'Country', 'Event Date & Time'];
    const rows = engagements.map((item) => [
      item.user?.id || '',
      `"${(item.user?.name || 'Visitor').replace(/"/g, '""')}"`,
      `"${(item.user?.username || '').replace(/"/g, '""')}"`,
      item.user?.is_verified ? 'Yes' : 'No',
      `"${(item.action_label || item.action || '').replace(/"/g, '""')}"`,
      item.reward_amount_usd ? item.reward_amount_usd.toFixed(4) : '0.0000',
      `"${(item.tier_label || '').replace(/"/g, '""')}"`,
      `"${(item.user?.phone || '').replace(/"/g, '""')}"`,
      `"${(item.user?.email || '').replace(/"/g, '""')}"`,
      `"${(item.user?.city || '').replace(/"/g, '""')}"`,
      `"${(item.user?.country || '').replace(/"/g, '""')}"`,
      `"${item.created_at ? new Date(item.created_at).toLocaleString() : ''}"`,
    ]);

    const csvContent = [headers.join(','), ...rows.map((r) => r.join(','))].join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `Campaign_${currentCampaign.campaign_id || currentCampaign.id}_Audience.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  // Open complete single-member engagement drill-down modal
  const handleOpenMemberDetail = async (memberUser, initialEvent = null) => {
    if (!memberUser || !memberUser.id) return;
    const cId = currentCampaign.campaign_id || currentCampaign.id;

    setSelectedMemberModal({
      member: memberUser,
      campaign: currentCampaign,
      summary: {
        total_activities: initialEvent ? 1 : 0,
        interested_count: initialEvent?.action === 'Interested' ? 1 : 0,
        clicks_count: initialEvent?.action === 'Clicked' || initialEvent?.action === 'Ad Click' ? 1 : 0,
        landing_visits_count: initialEvent?.action?.includes('Landing') ? 1 : 0,
        reward_status: initialEvent?.reward_amount_usd > 0 ? 'rewarded' : 'not_rewarded',
        is_rewarded: initialEvent?.reward_amount_usd > 0,
        reward_amount_usd: initialEvent?.reward_amount_usd || 0,
        reward_formatted: initialEvent?.reward_formatted || '$0.00',
      },
      reward_detail: initialEvent?.reward_amount_usd > 0 ? {
        reward_amount_usd: initialEvent.reward_amount_usd,
        reward_formatted: initialEvent.reward_formatted,
        tier_label: initialEvent.tier_label,
        direct_verified_referral_count: initialEvent.direct_verified_referral_count,
        qualifying_event_id: initialEvent.event_id,
        status: initialEvent.status || 'credited',
        credited_at: initialEvent.created_at,
        transaction_reference: 'REW-AD-' + (initialEvent.id || 'REF'),
      } : null,
      timeline: initialEvent ? [initialEvent] : [],
    });

    setMemberDetailLoading(true);
    setMemberDetailError('');

    try {
      const res = await businessApi.getCampaignMemberEngagement(page.slug, cId, memberUser.id);
      if (res.success) {
        setSelectedMemberModal({
          member: res.member || memberUser,
          campaign: res.campaign || currentCampaign,
          summary: res.summary || {},
          reward_detail: res.reward_detail || null,
          timeline: res.timeline || [],
        });
      }
    } catch (err) {
      console.error('Failed to load member campaign drill-down:', err);
      try {
        const fallbackRes = await businessApi.getCampaignEngagements(page.slug, cId, {
          member_id: memberUser.id,
          per_page: 50,
        });
        if (fallbackRes.success && fallbackRes.engagements?.data) {
          const events = fallbackRes.engagements.data;
          const rewardEvt = events.find((e) => e.reward_amount_usd > 0);
          setSelectedMemberModal({
            member: memberUser,
            campaign: currentCampaign,
            summary: {
              total_activities: events.length,
              interested_count: events.filter((e) => e.action?.includes('Interested')).length,
              clicks_count: events.filter((e) => e.action?.includes('Click')).length,
              landing_visits_count: events.filter((e) => e.action?.includes('Landing')).length,
              reward_status: rewardEvt ? 'rewarded' : 'not_rewarded',
              is_rewarded: Boolean(rewardEvt),
              reward_amount_usd: rewardEvt?.reward_amount_usd || 0,
              reward_formatted: rewardEvt?.reward_formatted || '$0.00',
            },
            reward_detail: rewardEvt ? {
              reward_amount_usd: rewardEvt.reward_amount_usd,
              reward_formatted: rewardEvt.reward_formatted,
              tier_label: rewardEvt.tier_label,
              direct_verified_referral_count: rewardEvt.direct_verified_referral_count,
              qualifying_event_id: rewardEvt.event_id,
              status: rewardEvt.status || 'credited',
              credited_at: rewardEvt.created_at,
              transaction_reference: 'REW-AD-' + (rewardEvt.id || 'REF'),
            } : null,
            timeline: events,
          });
        }
      } catch {
        setMemberDetailError(err.response?.data?.message || 'Failed to load member interaction details.');
      }
    } finally {
      setMemberDetailLoading(false);
    }
  };

  const handleCopy = (text, key) => {
    if (!text) return;
    navigator.clipboard.writeText(text);
    setCopiedKey(key);
    setTimeout(() => setCopiedKey(null), 2000);
  };

  // Copy all phone numbers from current list
  const handleCopyAllPhones = () => {
    const phones = engagements
      .map((item) => item.user?.phone)
      .filter((p) => Boolean(p) && String(p).trim() !== '')
      .map((p) => String(p).trim());

    const uniquePhones = Array.from(new Set(phones));
    if (uniquePhones.length === 0) {
      alert('No phone numbers available in the current filtered audience list.');
      return;
    }

    navigator.clipboard.writeText(uniquePhones.join(', '));
    setCopiedKey('all_phones');
    setTimeout(() => setCopiedKey(null), 2500);
  };

  // Copy all email addresses from current list
  const handleCopyAllEmails = () => {
    const emails = engagements
      .map((item) => item.user?.email)
      .filter((e) => Boolean(e) && String(e).trim() !== '')
      .map((e) => String(e).trim());

    const uniqueEmails = Array.from(new Set(emails));
    if (uniqueEmails.length === 0) {
      alert('No email addresses available in the current filtered audience list.');
      return;
    }

    navigator.clipboard.writeText(uniqueEmails.join(', '));
    setCopiedKey('all_emails');
    setTimeout(() => setCopiedKey(null), 2500);
  };

  if (!currentCampaign) return null;

  const handleAction = async (actionType) => {
    setIsActionLoading(true);
    setErrorMsg('');
    setSuccessMsg('');

    try {
      let res;
      const cId = currentCampaign.campaign_id || currentCampaign.id;

      if (actionType === 'submit') {
        res = await businessApi.submitAdCampaign(page.slug, cId);
        setSuccessMsg('Campaign submitted for review successfully!');
      } else if (actionType === 'pause') {
        res = await businessApi.pauseAdCampaign(page.slug, cId);
        setSuccessMsg('Campaign paused.');
      } else if (actionType === 'resume') {
        res = await businessApi.resumeAdCampaign(page.slug, cId);
        setSuccessMsg('Campaign resumed successfully.');
      } else if (actionType === 'stop') {
        res = await businessApi.stopAdCampaign(page.slug, cId);
        setSuccessMsg('Campaign stopped.');
      }

      if (res?.campaign) {
        setCurrentCampaign(res.campaign);
        if (onCampaignUpdated) {
          onCampaignUpdated(res.campaign);
        }
      }
    } catch (err) {
      console.error(`Failed to ${actionType} campaign:`, err);
      setErrorMsg(err.response?.data?.message || `Failed to perform action. Please try again.`);
    } finally {
      setIsActionLoading(false);
    }
  };

  const status = currentCampaign.status;
  const approvalStatus = currentCampaign.approval_status;
  const post = currentCampaign.post;

  const origBudget = parseFloat(currentCampaign.budget) || 0;
  const additionalFunding = parseFloat(currentCampaign.additional_funding) || 0;
  const totalFunded = parseFloat(currentCampaign.total_funded) || (origBudget + additionalFunding);
  const feePercent = currentCampaign.fee_percent !== undefined && currentCampaign.fee_percent !== null && !isNaN(Number(currentCampaign.fee_percent))
    ? parseFloat(currentCampaign.fee_percent)
    : 0;
  const feeAmount = currentCampaign.fee_amount !== undefined && currentCampaign.fee_amount !== null && !isNaN(Number(currentCampaign.fee_amount))
    ? parseFloat(currentCampaign.fee_amount)
    : (feePercent > 0 ? Math.round(totalFunded * (feePercent / 100) * 100) / 100 : 0);
  const spent = parseFloat(currentCampaign.spent_amount) || 0;
  const remaining = parseFloat(currentCampaign.remaining_amount) || Math.max(0, totalFunded - spent);

  const isLow = Boolean(currentCampaign.is_budget_low);
  const isExhausted = Boolean(currentCampaign.is_budget_exhausted || status === 'budget_exhausted');

  const getStatusBadge = () => {
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
        return { bg: '#dcfce7', color: '#15803d', label: 'Active / Delivering' };
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

  const badge = getStatusBadge();

  // Action badge formatting helper
  const getActionPill = (actionStr, typeStr) => {
    const act = (actionStr || '').toLowerCase();
    const typ = (typeStr || '').toLowerCase();

    if (act.includes('interested') || typ === 'interested') {
      return {
        bg: '#fffbeb',
        color: '#b45309',
        border: '#fde68a',
        icon: <Star size={12} />,
        label: 'Interested',
      };
    }
    if (act.includes('reward') || typ === 'reward') {
      return {
        bg: '#ecfdf5',
        color: '#047857',
        border: '#a7f3d0',
        icon: <CheckCircle2 size={12} />,
        label: 'Rewarded Visit',
      };
    }
    if (act.includes('visit') || typ === 'visited_landing_page') {
      return {
        bg: '#faf5ff',
        color: '#7e22ce',
        border: '#e9d5ff',
        icon: <ExternalLink size={12} />,
        label: 'Visited Landing Page',
      };
    }
    if (act.includes('click') || typ === 'click' || typ === 'clicks') {
      return {
        bg: '#eff6ff',
        color: '#1d4ed8',
        border: '#bfdbfe',
        icon: <MousePointerClick size={12} />,
        label: 'Ad Click',
      };
    }
    if (act.includes('fail') || typ === 'failed') {
      return {
        bg: '#fff1f2',
        color: '#be123c',
        border: '#fecdd3',
        icon: <AlertCircle size={12} />,
        label: 'Failed Attempt',
      };
    }
    return {
      bg: '#f8fafc',
      color: '#475569',
      border: '#e2e8f0',
      icon: <Users size={12} />,
      label: actionStr || 'Activity',
    };
  };

  return (
    <ModalPortal
      isOpen={Boolean(currentCampaign)}
      onClose={() => { if (!isActionLoading) onClose(); }}
      overlayClassName="campaign-detail-modal-overlay"
    >
      <div
        className="card campaign-detail-modal-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="campaign-detail-modal-title"
        style={{
          width: '100%',
          maxWidth: activeTab === 'overview' ? '780px' : '1140px',
          maxHeight: 'min(92vh, 800px)',
          display: 'flex',
          flexDirection: 'column',
          borderRadius: '20px',
          overflow: 'hidden',
          boxShadow: '0 25px 50px -12px rgba(15, 23, 42, 0.35)',
          background: 'var(--color-card-bg, #ffffff)',
          transition: 'max-width 0.2s ease',
        }}
      >
        {/* Header */}
        <div
          className="campaign-detail-modal-header"
          style={{
            padding: '18px 24px',
            borderBottom: '1px solid var(--color-border, #e2e8f0)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            background: 'linear-gradient(to right, #f8fafc, #ffffff)',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '14px' }}>
            <div
              style={{
                width: '44px',
                height: '44px',
                borderRadius: '12px',
                backgroundColor: 'rgba(79, 125, 243, 0.12)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                color: '#4f7df3',
                flexShrink: 0,
              }}
            >
              <Megaphone size={22} />
            </div>
            <div>
              <div style={{ display: 'flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' }}>
                <h2 style={{ fontSize: '18px', fontWeight: 800, margin: 0, color: 'var(--color-text-main, #1e293b)' }}>
                  {currentCampaign.campaign_name}
                </h2>
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
              <p style={{ margin: '2px 0 0', fontSize: '12.5px', color: 'var(--color-text-secondary, #64748b)' }}>
                ID: <code>{currentCampaign.campaign_id || currentCampaign.id}</code> &bull; Business: <strong>{page.page_name}</strong>
              </p>
            </div>
          </div>

          <button
            type="button"
            className="mini-button"
            onClick={onClose}
            disabled={isActionLoading}
            style={{ borderRadius: '50%', padding: '8px' }}
            aria-label="Close"
          >
            <X size={18} />
          </button>
        </div>

        {/* Tab Navigation */}
        <div
          className="campaign-detail-modal-tabs"
          style={{
            display: 'flex',
            borderBottom: '1px solid var(--color-border, #e2e8f0)',
            backgroundColor: 'var(--color-subtle-bg, #f8fafc)',
            padding: '0 24px',
            gap: '8px',
          }}
        >
          <button
            type="button"
            onClick={() => setActiveTab('overview')}
            style={{
              padding: '12px 16px',
              fontSize: '13.5px',
              fontWeight: 700,
              border: 'none',
              background: 'none',
              cursor: 'pointer',
              color: activeTab === 'overview' ? '#4f7df3' : '#64748b',
              borderBottom: activeTab === 'overview' ? '2.5px solid #4f7df3' : '2.5px solid transparent',
              display: 'flex',
              alignItems: 'center',
              gap: '6px',
            }}
          >
            <TrendingUp size={16} />
            <span>Overview & Financials</span>
          </button>

          <button
            type="button"
            onClick={() => setActiveTab('engagements')}
            style={{
              padding: '12px 16px',
              fontSize: '13.5px',
              fontWeight: 700,
              border: 'none',
              background: 'none',
              cursor: 'pointer',
              color: activeTab === 'engagements' ? '#4f7df3' : '#64748b',
              borderBottom: activeTab === 'engagements' ? '2.5px solid #4f7df3' : '2.5px solid transparent',
              display: 'flex',
              alignItems: 'center',
              gap: '6px',
            }}
          >
            <Users size={16} />
            <span>People Engaged ({engagementSummary.total_engaged_members})</span>
          </button>

          <button
            type="button"
            onClick={() => setActiveTab('analytics')}
            style={{
              padding: '12px 16px',
              fontSize: '13.5px',
              fontWeight: 700,
              border: 'none',
              background: 'none',
              cursor: 'pointer',
              color: activeTab === 'analytics' ? '#4f7df3' : '#64748b',
              borderBottom: activeTab === 'analytics' ? '2.5px solid #4f7df3' : '2.5px solid transparent',
              display: 'flex',
              alignItems: 'center',
              gap: '6px',
            }}
          >
            <BarChart2 size={16} />
            <span>Advanced Analytics & Reconciliation</span>
          </button>

          <Link
            to={`/member/business-pages/${page.slug || page.id}/ads/${currentCampaign.campaign_id || currentCampaign.id}/people-engaged`}
            style={{
              marginLeft: 'auto',
              padding: '8px 12px',
              fontSize: '12.5px',
              fontWeight: 700,
              color: '#2563eb',
              textDecoration: 'none',
              display: 'inline-flex',
              alignItems: 'center',
              gap: '5px',
              alignSelf: 'center',
            }}
            title="Open Dedicated Full-Page Experience"
          >
            <span>Full-Page View</span>
            <ExternalLink size={13} />
          </Link>
        </div>

        {/* Modal Body */}
        <div
          className="campaign-detail-modal-body"
          style={{ padding: '24px', overflowY: 'auto', flex: 1, display: 'flex', flexDirection: 'column', gap: '18px' }}
        >
          {errorMsg && (
            <div
              style={{
                padding: '12px 16px',
                borderRadius: '12px',
                backgroundColor: '#fef2f2',
                border: '1px solid #fecaca',
                color: '#b91c1c',
                fontSize: '13.5px',
              }}
            >
              {errorMsg}
            </div>
          )}

          {successMsg && (
            <div
              style={{
                padding: '12px 16px',
                borderRadius: '12px',
                backgroundColor: '#f0fdf4',
                border: '1px solid #bbf7d0',
                color: '#15803d',
                fontSize: '13.5px',
              }}
            >
              {successMsg}
            </div>
          )}

          {/* ========================================================= */}
          {/* TAB 1: OVERVIEW & FINANCIALS                             */}
          {/* ========================================================= */}
          {activeTab === 'overview' && (
            <>
              {/* Rejection Alert */}
              {(status === 'rejected' || approvalStatus === 'rejected') && (
                <div
                  style={{
                    padding: '16px',
                    borderRadius: '14px',
                    backgroundColor: '#fef2f2',
                    border: '1px solid #f87171',
                    display: 'flex',
                    alignItems: 'flex-start',
                    gap: '12px',
                  }}
                >
                  <AlertTriangle size={22} color="#dc2626" style={{ flexShrink: 0, marginTop: '2px' }} />
                  <div>
                    <h4 style={{ margin: '0 0 4px 0', fontSize: '14px', fontWeight: 800, color: '#991b1b' }}>
                      Campaign Rejected by Administrator
                    </h4>
                    <p style={{ margin: 0, fontSize: '13px', color: '#b91c1c', lineHeight: 1.5 }}>
                      <strong>Reason:</strong> {currentCampaign.rejection_reason || 'Content does not meet advertising guidelines.'}
                    </p>
                  </div>
                </div>
              )}

              {/* Status & Timing Overview */}
              <div
                style={{
                  padding: '16px',
                  borderRadius: '14px',
                  backgroundColor: 'var(--color-subtle-bg, #f8fafc)',
                  border: '1px solid var(--color-border, #e2e8f0)',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  flexWrap: 'wrap',
                  gap: '12px',
                }}
              >
                <div>
                  <span style={{ fontSize: '11.5px', fontWeight: 600, color: '#64748b', textTransform: 'uppercase' }}>
                    Current Status
                  </span>
                  <div style={{ marginTop: '4px' }}>
                    <span
                      style={{
                        padding: '4px 12px',
                        borderRadius: '12px',
                        fontSize: '12.5px',
                        fontWeight: 700,
                        backgroundColor: badge.bg,
                        color: badge.color,
                        display: 'inline-block',
                      }}
                    >
                      {badge.label}
                    </span>
                  </div>
                </div>

                <div>
                  <span style={{ fontSize: '11.5px', fontWeight: 600, color: '#64748b', textTransform: 'uppercase' }}>
                    Schedule
                  </span>
                  <div style={{ fontSize: '13px', fontWeight: 700, color: 'var(--color-text-main, #1e293b)', marginTop: '4px' }}>
                    {currentCampaign.start_at ? new Date(currentCampaign.start_at).toLocaleDateString() : 'Immediate'}
                    {currentCampaign.end_at ? ` - ${new Date(currentCampaign.end_at).toLocaleDateString()}` : ' (Continuous)'}
                  </div>
                </div>

                <div>
                  <span style={{ fontSize: '11.5px', fontWeight: 600, color: '#64748b', textTransform: 'uppercase' }}>
                    Approval Status
                  </span>
                  <div style={{ fontSize: '13px', fontWeight: 700, color: 'var(--color-text-main, #1e293b)', marginTop: '4px', textTransform: 'capitalize' }}>
                    {approvalStatus ? approvalStatus.replace('_', ' ') : 'Pending Review'}
                  </div>
                </div>
              </div>

              {/* Promoted Post Preview Card */}
              {post && (
                <div
                  style={{
                    border: '1px solid var(--color-border, #e2e8f0)',
                    borderRadius: '14px',
                    padding: '16px',
                    backgroundColor: 'var(--color-card-bg, #ffffff)',
                  }}
                >
                  <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '10px' }}>
                    <span style={{ fontSize: '12.5px', fontWeight: 700, color: '#64748b', display: 'flex', alignItems: 'center', gap: '6px' }}>
                      <FileText size={15} color="#4f7df3" />
                      Promoted Post Preview
                    </span>
                    <span style={{ fontSize: '11.5px', color: '#94a3b8' }}>ID #{post.id}</span>
                  </div>

                  <p style={{ fontSize: '13.5px', color: 'var(--color-text-main, #334155)', margin: '0 0 10px 0', lineHeight: 1.5 }}>
                    {post.body || 'No post text content.'}
                  </p>

                  {post.media_urls && post.media_urls.length > 0 && (
                    <div style={{ display: 'flex', gap: '8px', overflowX: 'auto', paddingBottom: '4px' }}>
                      {post.media_urls.map((url, idx) => (
                        <img
                          key={idx}
                          src={getMediaUrl(url)}
                          alt="Post Media"
                          style={{
                            width: '90px',
                            height: '90px',
                            borderRadius: '10px',
                            objectFit: 'cover',
                            border: '1px solid #e2e8f0',
                          }}
                        />
                      ))}
                    </div>
                  )}

                  {currentCampaign.destination_link && (
                    <div style={{ marginTop: '12px', fontSize: '12.5px' }}>
                      <span style={{ color: '#64748b' }}>Destination Link: </span>
                      <a
                        href={currentCampaign.destination_link}
                        target="_blank"
                        rel="noreferrer"
                        style={{ color: '#4f7df3', fontWeight: 600, wordBreak: 'break-all' }}
                      >
                        {currentCampaign.destination_link}
                      </a>
                    </div>
                  )}
                </div>
              )}

              {/* Financial Ledger Section */}
              <div
                style={{
                  border: isExhausted ? '1px solid #fecaca' : isLow ? '1px solid #fde68a' : '1px solid var(--color-border, #e2e8f0)',
                  borderRadius: '16px',
                  padding: '20px',
                  backgroundColor: isExhausted ? '#fffafa' : isLow ? '#fffdfa' : 'var(--color-subtle-bg, #f8fafc)',
                }}
              >
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '16px' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                    <DollarSign size={18} color="#4f7df3" />
                    <h3 style={{ margin: 0, fontSize: '15px', fontWeight: 800, color: 'var(--color-text-main, #1e293b)' }}>
                      Campaign Financials & Wallet Deductions
                    </h3>
                  </div>

                  {isOwner && onOpenTopUp && (
                    <button
                      type="button"
                      className="btn"
                      onClick={() => onOpenTopUp(currentCampaign)}
                      style={{
                        padding: '6px 14px',
                        borderRadius: '8px',
                        fontSize: '12px',
                        fontWeight: 700,
                        backgroundColor: '#f0fdf4',
                        color: '#16a34a',
                        border: '1px solid #bbf7d0',
                        cursor: 'pointer',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '6px',
                      }}
                    >
                      <Gift size={13} />
                      <span>Add Funds</span>
                    </button>
                  )}
                </div>

                <div
                  style={{
                    display: 'grid',
                    gridTemplateColumns: 'repeat(auto-fit, minmax(130px, 1fr))',
                    gap: '12px',
                    marginBottom: '16px',
                  }}
                >
                  <div style={{ padding: '12px', borderRadius: '12px', backgroundColor: '#ffffff', border: '1px solid #e2e8f0' }}>
                    <span style={{ fontSize: '11px', color: '#64748b', fontWeight: 600 }}>Original Budget</span>
                    <div style={{ fontSize: '16px', fontWeight: 800, color: '#4f7df3', marginTop: '4px' }}>
                      ${origBudget.toFixed(2)}
                    </div>
                  </div>

                  {additionalFunding > 0 && (
                    <div style={{ padding: '12px', borderRadius: '12px', backgroundColor: '#ffffff', border: '1px solid #e2e8f0' }}>
                      <span style={{ fontSize: '11px', color: '#64748b', fontWeight: 600 }}>Top-Up Added</span>
                      <div style={{ fontSize: '16px', fontWeight: 800, color: '#16a34a', marginTop: '4px' }}>
                        +${additionalFunding.toFixed(2)}
                      </div>
                    </div>
                  )}

                  <div style={{ padding: '12px', borderRadius: '12px', backgroundColor: '#ffffff', border: '1px solid #e2e8f0' }}>
                    <span style={{ fontSize: '11px', color: '#64748b', fontWeight: 600 }}>Total Funded</span>
                    <div style={{ fontSize: '16px', fontWeight: 800, color: '#1e293b', marginTop: '4px' }}>
                      ${totalFunded.toFixed(2)}
                    </div>
                  </div>

                  {feeAmount > 0 && (
                    <div style={{ padding: '12px', borderRadius: '12px', backgroundColor: '#ffffff', border: '1px solid #e2e8f0' }}>
                      <span style={{ fontSize: '11px', color: '#64748b', fontWeight: 600 }}>Platform Fee ({feePercent}%)</span>
                      <div style={{ fontSize: '16px', fontWeight: 800, color: '#64748b', marginTop: '4px' }}>
                        ${feeAmount.toFixed(2)}
                      </div>
                    </div>
                  )}

                  <div style={{ padding: '12px', borderRadius: '12px', backgroundColor: '#ffffff', border: '1px solid #e2e8f0' }}>
                    <span style={{ fontSize: '11px', color: '#64748b', fontWeight: 600 }}>Rewards Paid</span>
                    <div style={{ fontSize: '16px', fontWeight: 800, color: '#dc2626', marginTop: '4px' }}>
                      ${spent.toFixed(2)}
                    </div>
                  </div>

                  <div style={{ padding: '12px', borderRadius: '12px', backgroundColor: '#ffffff', border: '1px solid #e2e8f0' }}>
                    <span style={{ fontSize: '11px', color: '#64748b', fontWeight: 600 }}>Remaining Budget</span>
                    <div
                      style={{
                        fontSize: '16px',
                        fontWeight: 800,
                        color: isExhausted ? '#b91c1c' : isLow ? '#b45309' : '#15803d',
                        marginTop: '4px',
                      }}
                    >
                      ${remaining.toFixed(2)}
                    </div>
                  </div>
                </div>

                {isExhausted && (
                  <div
                    style={{
                      padding: '10px 14px',
                      borderRadius: '10px',
                      backgroundColor: '#fee2e2',
                      border: '1px solid #fecaca',
                      color: '#991b1b',
                      fontSize: '12.5px',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '8px',
                    }}
                  >
                    <AlertTriangle size={16} />
                    <span>
                      <strong>Budget Exhausted:</strong> Ad delivery has paused. Add funds to resume showing this ad across member feeds.
                    </span>
                  </div>
                )}
              </div>
            </>
          )}

          {/* ========================================================= */}
          {/* TAB 2: PEOPLE ENGAGED & AUDIENCE CONTACT CENTER          */}
          {/* ========================================================= */}
          {activeTab === 'engagements' && (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
              {/* Event-Style Audience Hero Banner */}
              <div
                style={{
                  padding: '20px 24px',
                  borderRadius: '16px',
                  background: 'linear-gradient(135deg, #1e293b 0%, #0f172a 100%)',
                  color: '#ffffff',
                  boxShadow: '0 4px 20px rgba(15, 23, 42, 0.15)',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  flexWrap: 'wrap',
                  gap: '16px',
                }}
              >
                <div>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '6px' }}>
                    <span
                      style={{
                        fontSize: '11px',
                        fontWeight: 800,
                        letterSpacing: '0.05em',
                        padding: '3px 9px',
                        borderRadius: '20px',
                        backgroundColor: 'rgba(79, 125, 243, 0.25)',
                        color: '#93c5fd',
                        border: '1px solid rgba(147, 197, 253, 0.3)',
                        textTransform: 'uppercase',
                      }}
                    >
                      Campaign Audience & Lead Center
                    </span>
                    <span style={{ fontSize: '12px', color: '#94a3b8' }}>•</span>
                    <span style={{ fontSize: '12px', color: '#cbd5e1' }}>
                      Objective: <strong>{currentCampaign.campaign_objective?.replace('_', ' ') || 'Brand Awareness'}</strong>
                    </span>
                  </div>
                  <h3 style={{ margin: 0, fontSize: '18px', fontWeight: 800, color: '#ffffff' }}>
                    {currentCampaign.campaign_name}
                  </h3>
                  <p style={{ margin: '4px 0 0', fontSize: '12.5px', color: '#94a3b8' }}>
                    Select and directly connect with verified members who engaged with your ad campaign.
                  </p>
                </div>

                {/* Quick Batch Tools & Server Export Dropdown */}
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap', position: 'relative' }}>
                  <button
                    type="button"
                    onClick={handleCopyAllPhones}
                    style={{
                      padding: '8px 14px',
                      borderRadius: '10px',
                      fontSize: '12.5px',
                      fontWeight: 700,
                      backgroundColor: copiedKey === 'all_phones' ? '#16a34a' : 'rgba(37, 211, 102, 0.15)',
                      color: copiedKey === 'all_phones' ? '#ffffff' : '#4ade80',
                      border: '1px solid rgba(74, 222, 128, 0.4)',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '6px',
                      cursor: 'pointer',
                      transition: 'all 0.15s ease',
                    }}
                    title="Copy all WhatsApp phone numbers in current list"
                  >
                    {copiedKey === 'all_phones' ? <Check size={14} /> : <Phone size={14} />}
                    <span>{copiedKey === 'all_phones' ? 'Copied Phones!' : 'Copy WhatsApps'}</span>
                  </button>

                  <button
                    type="button"
                    onClick={handleCopyAllEmails}
                    style={{
                      padding: '8px 14px',
                      borderRadius: '10px',
                      fontSize: '12.5px',
                      fontWeight: 700,
                      backgroundColor: copiedKey === 'all_emails' ? '#16a34a' : 'rgba(255, 255, 255, 0.1)',
                      color: copiedKey === 'all_emails' ? '#ffffff' : '#e2e8f0',
                      border: '1px solid rgba(255, 255, 255, 0.2)',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '6px',
                      cursor: 'pointer',
                      transition: 'all 0.15s ease',
                    }}
                    title="Copy all email addresses in current list"
                  >
                    {copiedKey === 'all_emails' ? <Check size={14} /> : <Mail size={14} />}
                    <span>{copiedKey === 'all_emails' ? 'Copied Emails!' : 'Copy Emails'}</span>
                  </button>

                  {/* Server-Side CSV Export Button & Menu */}
                  <div style={{ position: 'relative' }}>
                    <button
                      type="button"
                      onClick={() => setExportDropdownOpen((prev) => !prev)}
                      disabled={isExporting}
                      style={{
                        padding: '8px 14px',
                        borderRadius: '10px',
                        fontSize: '12.5px',
                        fontWeight: 700,
                        backgroundColor: '#4f7df3',
                        color: '#ffffff',
                        border: 'none',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '6px',
                        cursor: isExporting ? 'not-allowed' : 'pointer',
                        boxShadow: '0 2px 8px rgba(79, 125, 243, 0.4)',
                      }}
                      title="Download campaign audience records as CSV"
                    >
                      <Download size={14} className={isExporting ? 'animate-spin' : ''} />
                      <span>{isExporting ? 'Exporting...' : 'Export CSV'}</span>
                    </button>

                    {exportDropdownOpen && (
                      <div
                        style={{
                          position: 'absolute',
                          right: 0,
                          top: '100%',
                          marginTop: '6px',
                          width: '210px',
                          backgroundColor: '#ffffff',
                          borderRadius: '12px',
                          boxShadow: '0 10px 25px rgba(0,0,0,0.2)',
                          border: '1px solid #e2e8f0',
                          zIndex: 50,
                          overflow: 'hidden',
                          display: 'flex',
                          flexDirection: 'column',
                          padding: '4px',
                        }}
                      >
                        <button
                          type="button"
                          onClick={() => handleServerExport('filtered')}
                          style={{
                            padding: '9px 12px',
                            textAlign: 'left',
                            fontSize: '12.5px',
                            fontWeight: 600,
                            color: '#1e293b',
                            backgroundColor: 'transparent',
                            border: 'none',
                            borderRadius: '8px',
                            cursor: 'pointer',
                            display: 'flex',
                            alignItems: 'center',
                            gap: '8px',
                          }}
                          onMouseEnter={(e) => (e.currentTarget.style.backgroundColor = '#f1f5f9')}
                          onMouseLeave={(e) => (e.currentTarget.style.backgroundColor = 'transparent')}
                        >
                          <FileSpreadsheet size={14} color="#4f7df3" />
                          <span>Export Filtered Audience</span>
                        </button>

                        <button
                          type="button"
                          onClick={() => handleServerExport('all')}
                          style={{
                            padding: '9px 12px',
                            textAlign: 'left',
                            fontSize: '12.5px',
                            fontWeight: 600,
                            color: '#1e293b',
                            backgroundColor: 'transparent',
                            border: 'none',
                            borderRadius: '8px',
                            cursor: 'pointer',
                            display: 'flex',
                            alignItems: 'center',
                            gap: '8px',
                          }}
                          onMouseEnter={(e) => (e.currentTarget.style.backgroundColor = '#f1f5f9')}
                          onMouseLeave={(e) => (e.currentTarget.style.backgroundColor = 'transparent')}
                        >
                          <Download size={14} color="#16a34a" />
                          <span>Export All Campaign Data</span>
                        </button>
                      </div>
                    )}
                  </div>
                </div>
              </div>

              {/* 7 KPI Summary Cards Grid (Authoritative entire-campaign counters) */}
              <div
                style={{
                  display: 'grid',
                  gridTemplateColumns: 'repeat(auto-fit, minmax(130px, 1fr))',
                  gap: '10px',
                }}
              >
                {/* 1. Total Engaged Activities */}
                <div style={{ padding: '14px', borderRadius: '14px', backgroundColor: '#eff6ff', border: '1px solid #dbeafe' }}>
                  <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                    <span style={{ fontSize: '11px', fontWeight: 700, color: '#1d4ed8', textTransform: 'uppercase' }}>
                      Total Activity
                    </span>
                    <Users size={14} color="#2563eb" />
                  </div>
                  <div style={{ fontSize: '20px', fontWeight: 800, color: '#1e3a8a', marginTop: '4px' }}>
                    {engagementSummary.total_engagements}
                  </div>
                  <div style={{ fontSize: '11px', color: '#60a5fa', marginTop: '2px' }}>All campaign events</div>
                </div>

                {/* 2. Unique Engaged Members */}
                <div style={{ padding: '14px', borderRadius: '14px', backgroundColor: '#eef2ff', border: '1px solid #e0e7ff' }}>
                  <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                    <span style={{ fontSize: '11px', fontWeight: 700, color: '#4338ca', textTransform: 'uppercase' }}>
                      Unique Members
                    </span>
                    <Sparkles size={14} color="#4f46e5" />
                  </div>
                  <div style={{ fontSize: '20px', fontWeight: 800, color: '#312e81', marginTop: '4px' }}>
                    {engagementSummary.total_engaged_members}
                  </div>
                  <div style={{ fontSize: '11px', color: '#818cf8', marginTop: '2px' }}>Distinct participants</div>
                </div>

                {/* 3. Interested Leads */}
                <div style={{ padding: '14px', borderRadius: '14px', backgroundColor: '#fffbeb', border: '1px solid #fef3c7' }}>
                  <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                    <span style={{ fontSize: '11px', fontWeight: 700, color: '#b45309', textTransform: 'uppercase' }}>
                      Interested
                    </span>
                    <Star size={14} color="#d97706" />
                  </div>
                  <div style={{ fontSize: '20px', fontWeight: 800, color: '#78350f', marginTop: '4px' }}>
                    {engagementSummary.interested_count}
                  </div>
                  <div style={{ fontSize: '11px', color: '#f59e0b', marginTop: '2px' }}>Clicked "Interested"</div>
                </div>

                {/* 4. Rewarded Members */}
                <div style={{ padding: '14px', borderRadius: '14px', backgroundColor: '#ecfdf5', border: '1px solid #d1fae5' }}>
                  <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                    <span style={{ fontSize: '11px', fontWeight: 700, color: '#047857', textTransform: 'uppercase' }}>
                      Rewarded
                    </span>
                    <CheckCircle2 size={14} color="#059669" />
                  </div>
                  <div style={{ fontSize: '20px', fontWeight: 800, color: '#064e3b', marginTop: '4px' }}>
                    {engagementSummary.total_rewards_count}
                  </div>
                  <div style={{ fontSize: '11px', color: '#34d399', marginTop: '2px' }}>Paid wallet reward</div>
                </div>

                {/* 5. Landing Visits */}
                <div style={{ padding: '14px', borderRadius: '14px', backgroundColor: '#faf5ff', border: '1px solid #f3e8ff' }}>
                  <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                    <span style={{ fontSize: '11px', fontWeight: 700, color: '#7e22ce', textTransform: 'uppercase' }}>
                      Landing Visits
                    </span>
                    <ExternalLink size={14} color="#9333ea" />
                  </div>
                  <div style={{ fontSize: '20px', fontWeight: 800, color: '#581c87', marginTop: '4px' }}>
                    {engagementSummary.landing_visit_count}
                  </div>
                  <div style={{ fontSize: '11px', color: '#c084fc', marginTop: '2px' }}>Visited destination</div>
                </div>

                {/* 6. Total Rewards Paid (USD) */}
                <div style={{ padding: '14px', borderRadius: '14px', backgroundColor: '#f0fdf4', border: '1px solid #dcfce7' }}>
                  <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                    <span style={{ fontSize: '11px', fontWeight: 700, color: '#15803d', textTransform: 'uppercase' }}>
                      Rewards Paid
                    </span>
                    <DollarSign size={14} color="#16a34a" />
                  </div>
                  <div style={{ fontSize: '18px', fontWeight: 800, color: '#14532d', marginTop: '4px' }}>
                    ${engagementSummary.total_rewards_paid.toFixed(3)}
                  </div>
                  <div style={{ fontSize: '11px', color: '#4ade80', marginTop: '2px' }}>Total USD credited</div>
                </div>

                {/* 7. Verified Leads */}
                <div style={{ padding: '14px', borderRadius: '14px', backgroundColor: '#fdf4ff', border: '1px solid #fae8ff' }}>
                  <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                    <span style={{ fontSize: '11px', fontWeight: 700, color: '#a21caf', textTransform: 'uppercase' }}>
                      Verified Leads
                    </span>
                    <ShieldCheck size={14} color="#c026d3" />
                  </div>
                  <div style={{ fontSize: '20px', fontWeight: 800, color: '#701a75', marginTop: '4px' }}>
                    {engagementSummary.verified_members_count}
                  </div>
                  <div style={{ fontSize: '11px', color: '#e879f9', marginTop: '2px' }}>With WhatsApp verified</div>
                </div>
              </div>

              {/* Action Filter Segment Tabs */}
              <div
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: '8px',
                  overflowX: 'auto',
                  paddingBottom: '4px',
                }}
              >
                {[
                  { id: 'all', label: 'All Activity', count: engagementSummary.total_engagements },
                  { id: 'interested', label: 'Interested Leads', count: engagementSummary.interested_count },
                  { id: 'rewarded', label: 'Rewarded Members', count: engagementSummary.total_rewards_count },
                  { id: 'visited_landing_page', label: 'Landing Visits', count: engagementSummary.landing_visit_count },
                  { id: 'clicked', label: 'Ad Clicks', count: engagementSummary.click_count },
                ].map((tab) => {
                  const isSelected = engagementActionFilter === tab.id;
                  return (
                    <button
                      key={tab.id}
                      type="button"
                      onClick={() => {
                        setEngagementActionFilter(tab.id);
                        setEngagementPage(1);
                      }}
                      style={{
                        padding: '8px 14px',
                        borderRadius: '10px',
                        fontSize: '12.5px',
                        fontWeight: 700,
                        border: '1px solid',
                        borderColor: isSelected ? '#4f7df3' : 'var(--color-border, #e2e8f0)',
                        backgroundColor: isSelected ? '#4f7df3' : 'var(--color-card-bg, #ffffff)',
                        color: isSelected ? '#ffffff' : 'var(--color-text-secondary, #64748b)',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '6px',
                        cursor: 'pointer',
                        whiteSpace: 'nowrap',
                        transition: 'all 0.15s ease',
                      }}
                    >
                      <span>{tab.label}</span>
                      <span
                        style={{
                          fontSize: '11px',
                          padding: '1px 6px',
                          borderRadius: '8px',
                          backgroundColor: isSelected ? 'rgba(255, 255, 255, 0.25)' : '#f1f5f9',
                          color: isSelected ? '#ffffff' : '#64748b',
                          fontWeight: 800,
                        }}
                      >
                        {tab.count}
                      </span>
                    </button>
                  );
                })}
              </div>

              {/* Search Bar & Multi-Dimensional Control Strip */}
              <div
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  flexWrap: 'wrap',
                  gap: '10px',
                }}
              >
                {/* Search Field */}
                <div style={{ position: 'relative', flex: 1, minWidth: '260px' }}>
                  <Search
                    size={15}
                    style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: '#94a3b8' }}
                  />
                  <input
                    type="text"
                    value={searchInputValue}
                    onChange={(e) => setSearchInputValue(e.target.value)}
                    placeholder="Search by member name, username or ID..."
                    style={{
                      width: '100%',
                      padding: '9px 12px 9px 36px',
                      borderRadius: '10px',
                      border: '1px solid var(--color-border, #cbd5e1)',
                      fontSize: '13px',
                      backgroundColor: 'var(--color-input-bg, #ffffff)',
                      color: 'var(--color-text-main, #1e293b)',
                    }}
                  />
                  {searchInputValue && (
                    <button
                      type="button"
                      onClick={() => {
                        setSearchInputValue('');
                        setEngagementSearch('');
                        setEngagementPage(1);
                      }}
                      style={{
                        position: 'absolute',
                        right: '10px',
                        top: '50%',
                        transform: 'translateY(-50%)',
                        background: 'none',
                        border: 'none',
                        color: '#94a3b8',
                        cursor: 'pointer',
                        padding: '2px',
                      }}
                      title="Clear search"
                    >
                      <X size={14} />
                    </button>
                  )}
                </div>

                {/* Filter Controls Strip */}
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' }}>
                  {/* Reward Filter Dropdown */}
                  <select
                    value={rewardStatusFilter}
                    onChange={(e) => {
                      setRewardStatusFilter(e.target.value);
                      setEngagementPage(1);
                    }}
                    style={{
                      padding: '8px 12px',
                      borderRadius: '8px',
                      border: '1px solid var(--color-border, #cbd5e1)',
                      backgroundColor: 'var(--color-input-bg, #ffffff)',
                      color: 'var(--color-text-main, #1e293b)',
                      fontSize: '12.5px',
                      fontWeight: 600,
                      cursor: 'pointer',
                    }}
                    title="Filter by reward qualification status"
                  >
                    <option value="all">Reward: All</option>
                    <option value="rewarded">Rewarded Only</option>
                    <option value="not_rewarded">Not Rewarded</option>
                    <option value="failed">Failed / Rejected</option>
                  </select>

                  {/* Verification Filter Dropdown */}
                  <select
                    value={verificationFilter}
                    onChange={(e) => {
                      setVerificationFilter(e.target.value);
                      setEngagementPage(1);
                    }}
                    style={{
                      padding: '8px 12px',
                      borderRadius: '8px',
                      border: '1px solid var(--color-border, #cbd5e1)',
                      backgroundColor: 'var(--color-input-bg, #ffffff)',
                      color: 'var(--color-text-main, #1e293b)',
                      fontSize: '12.5px',
                      fontWeight: 600,
                      cursor: 'pointer',
                    }}
                    title="Filter by WhatsApp mobile verification state"
                  >
                    <option value="all">Verification: All</option>
                    <option value="verified">Verified Leads Only</option>
                    <option value="unverified">Unverified Only</option>
                  </select>

                  {/* Date Filter Dropdown */}
                  <select
                    value={datePreset}
                    onChange={(e) => {
                      setDatePreset(e.target.value);
                      setEngagementPage(1);
                    }}
                    style={{
                      padding: '8px 12px',
                      borderRadius: '8px',
                      border: '1px solid var(--color-border, #cbd5e1)',
                      backgroundColor: 'var(--color-input-bg, #ffffff)',
                      color: 'var(--color-text-main, #1e293b)',
                      fontSize: '12.5px',
                      fontWeight: 600,
                      cursor: 'pointer',
                    }}
                    title="Filter activity by date range"
                  >
                    <option value="all">Date: All Time</option>
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="last_7_days">Last 7 Days</option>
                    <option value="last_30_days">Last 30 Days</option>
                    <option value="custom">Custom Range...</option>
                  </select>

                  {/* Sort Selector */}
                  <select
                    value={sortOrder}
                    onChange={(e) => {
                      setSortOrder(e.target.value);
                      setEngagementPage(1);
                    }}
                    style={{
                      padding: '8px 12px',
                      borderRadius: '8px',
                      border: '1px solid var(--color-border, #cbd5e1)',
                      backgroundColor: 'var(--color-input-bg, #ffffff)',
                      color: 'var(--color-text-main, #1e293b)',
                      fontSize: '12.5px',
                      fontWeight: 600,
                      cursor: 'pointer',
                    }}
                    title="Sort campaign activities"
                  >
                    <option value="newest">Sort: Newest First</option>
                    <option value="oldest">Sort: Oldest First</option>
                    <option value="highest_reward">Highest Reward</option>
                    <option value="lowest_reward">Lowest Reward</option>
                  </select>

                  {/* View Mode Switcher */}
                  <div
                    style={{
                      display: 'flex',
                      backgroundColor: 'var(--color-subtle-bg, #f1f5f9)',
                      padding: '3px',
                      borderRadius: '8px',
                    }}
                  >
                    <button
                      type="button"
                      onClick={() => setViewMode('table')}
                      style={{
                        padding: '5px 8px',
                        borderRadius: '6px',
                        border: 'none',
                        backgroundColor: viewMode === 'table' ? '#ffffff' : 'transparent',
                        color: viewMode === 'table' ? '#4f7df3' : '#64748b',
                        boxShadow: viewMode === 'table' ? '0 1px 3px rgba(0,0,0,0.1)' : 'none',
                        cursor: 'pointer',
                        display: 'flex',
                        alignItems: 'center',
                      }}
                      title="Table View"
                    >
                      <LayoutList size={15} />
                    </button>
                    <button
                      type="button"
                      onClick={() => setViewMode('grid')}
                      style={{
                        padding: '5px 8px',
                        borderRadius: '6px',
                        border: 'none',
                        backgroundColor: viewMode === 'grid' ? '#ffffff' : 'transparent',
                        color: viewMode === 'grid' ? '#4f7df3' : '#64748b',
                        boxShadow: viewMode === 'grid' ? '0 1px 3px rgba(0,0,0,0.1)' : 'none',
                        cursor: 'pointer',
                        display: 'flex',
                        alignItems: 'center',
                      }}
                      title="Card Grid View"
                    >
                      <LayoutGrid size={15} />
                    </button>
                  </div>

                  {/* Refresh Button */}
                  <button
                    type="button"
                    onClick={fetchEngagements}
                    disabled={engagementsLoading}
                    style={{
                      padding: '7px 12px',
                      borderRadius: '8px',
                      border: '1px solid var(--color-border, #cbd5e1)',
                      backgroundColor: 'var(--color-card-bg, #ffffff)',
                      color: '#64748b',
                      cursor: 'pointer',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '6px',
                      fontSize: '12.5px',
                      fontWeight: 600,
                    }}
                    title="Refresh audience list"
                  >
                    <RefreshCw size={14} className={engagementsLoading ? 'animate-spin' : ''} />
                    <span>Refresh</span>
                  </button>
                </div>
              </div>

              {/* Custom Date Range Inputs (when datePreset === 'custom') */}
              {datePreset === 'custom' && (
                <div
                  style={{
                    padding: '12px 16px',
                    borderRadius: '12px',
                    backgroundColor: '#f8fafc',
                    border: '1px solid #e2e8f0',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '12px',
                    flexWrap: 'wrap',
                  }}
                >
                  <span style={{ fontSize: '12.5px', fontWeight: 700, color: '#475569', display: 'flex', alignItems: 'center', gap: '6px' }}>
                    <Calendar size={15} color="#4f7df3" />
                    Custom Activity Date Range:
                  </span>

                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                    <input
                      type="date"
                      value={customStartDate}
                      onChange={(e) => {
                        setCustomStartDate(e.target.value);
                        setEngagementPage(1);
                      }}
                      style={{
                        padding: '6px 10px',
                        borderRadius: '8px',
                        border: '1px solid #cbd5e1',
                        fontSize: '12.5px',
                      }}
                    />
                    <span style={{ fontSize: '12px', color: '#94a3b8' }}>to</span>
                    <input
                      type="date"
                      value={customEndDate}
                      onChange={(e) => {
                        setCustomEndDate(e.target.value);
                        setEngagementPage(1);
                      }}
                      style={{
                        padding: '6px 10px',
                        borderRadius: '8px',
                        border: '1px solid #cbd5e1',
                        fontSize: '12.5px',
                      }}
                    />
                  </div>
                </div>
              )}

              {/* Active Filter Chips Bar & Clear All */}
              {hasActiveFilters && (
                <div
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    flexWrap: 'wrap',
                    gap: '8px',
                    padding: '8px 12px',
                    borderRadius: '10px',
                    backgroundColor: '#f1f5f9',
                  }}
                >
                  <div style={{ display: 'flex', alignItems: 'center', gap: '6px', flexWrap: 'wrap' }}>
                    <span style={{ fontSize: '11.5px', fontWeight: 700, color: '#64748b', textTransform: 'uppercase' }}>
                      Active Filters:
                    </span>

                    {engagementSearch && (
                      <span
                        style={{
                          padding: '3px 8px',
                          borderRadius: '6px',
                          backgroundColor: '#ffffff',
                          border: '1px solid #cbd5e1',
                          fontSize: '11.5px',
                          fontWeight: 600,
                          color: '#1e293b',
                          display: 'inline-flex',
                          alignItems: 'center',
                          gap: '4px',
                        }}
                      >
                        Search: "{engagementSearch}"
                        <X
                          size={12}
                          style={{ cursor: 'pointer', color: '#94a3b8' }}
                          onClick={() => {
                            setSearchInputValue('');
                            setEngagementSearch('');
                          }}
                        />
                      </span>
                    )}

                    {engagementActionFilter !== 'all' && (
                      <span
                        style={{
                          padding: '3px 8px',
                          borderRadius: '6px',
                          backgroundColor: '#ffffff',
                          border: '1px solid #cbd5e1',
                          fontSize: '11.5px',
                          fontWeight: 600,
                          color: '#1e293b',
                          display: 'inline-flex',
                          alignItems: 'center',
                          gap: '4px',
                        }}
                      >
                        Action: {engagementActionFilter.replace('_', ' ')}
                        <X
                          size={12}
                          style={{ cursor: 'pointer', color: '#94a3b8' }}
                          onClick={() => setEngagementActionFilter('all')}
                        />
                      </span>
                    )}

                    {rewardStatusFilter !== 'all' && (
                      <span
                        style={{
                          padding: '3px 8px',
                          borderRadius: '6px',
                          backgroundColor: '#ffffff',
                          border: '1px solid #cbd5e1',
                          fontSize: '11.5px',
                          fontWeight: 600,
                          color: '#1e293b',
                          display: 'inline-flex',
                          alignItems: 'center',
                          gap: '4px',
                        }}
                      >
                        Reward: {rewardStatusFilter.replace('_', ' ')}
                        <X
                          size={12}
                          style={{ cursor: 'pointer', color: '#94a3b8' }}
                          onClick={() => setRewardStatusFilter('all')}
                        />
                      </span>
                    )}

                    {verificationFilter !== 'all' && (
                      <span
                        style={{
                          padding: '3px 8px',
                          borderRadius: '6px',
                          backgroundColor: '#ffffff',
                          border: '1px solid #cbd5e1',
                          fontSize: '11.5px',
                          fontWeight: 600,
                          color: '#1e293b',
                          display: 'inline-flex',
                          alignItems: 'center',
                          gap: '4px',
                        }}
                      >
                        Verification: {verificationFilter}
                        <X
                          size={12}
                          style={{ cursor: 'pointer', color: '#94a3b8' }}
                          onClick={() => setVerificationFilter('all')}
                        />
                      </span>
                    )}

                    {datePreset !== 'all' && (
                      <span
                        style={{
                          padding: '3px 8px',
                          borderRadius: '6px',
                          backgroundColor: '#ffffff',
                          border: '1px solid #cbd5e1',
                          fontSize: '11.5px',
                          fontWeight: 600,
                          color: '#1e293b',
                          display: 'inline-flex',
                          alignItems: 'center',
                          gap: '4px',
                        }}
                      >
                        Date: {datePreset.replace(/_/g, ' ')}
                        <X
                          size={12}
                          style={{ cursor: 'pointer', color: '#94a3b8' }}
                          onClick={() => setDatePreset('all')}
                        />
                      </span>
                    )}

                    {sortOrder !== 'newest' && (
                      <span
                        style={{
                          padding: '3px 8px',
                          borderRadius: '6px',
                          backgroundColor: '#ffffff',
                          border: '1px solid #cbd5e1',
                          fontSize: '11.5px',
                          fontWeight: 600,
                          color: '#1e293b',
                          display: 'inline-flex',
                          alignItems: 'center',
                          gap: '4px',
                        }}
                      >
                        Sort: {sortOrder.replace('_', ' ')}
                        <X
                          size={12}
                          style={{ cursor: 'pointer', color: '#94a3b8' }}
                          onClick={() => setSortOrder('newest')}
                        />
                      </span>
                    )}
                  </div>

                  <button
                    type="button"
                    onClick={handleClearAllFilters}
                    style={{
                      border: 'none',
                      background: 'none',
                      color: '#dc2626',
                      fontSize: '12px',
                      fontWeight: 700,
                      cursor: 'pointer',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '4px',
                      padding: '2px 6px',
                    }}
                  >
                    <RotateCcw size={12} />
                    <span>Clear All Filters</span>
                  </button>
                </div>
              )}

              {/* Distinct Filter Result Count Banner */}
              <div
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  fontSize: '12.5px',
                  color: '#64748b',
                  padding: '2px 4px',
                }}
              >
                <div>
                  Campaign Audience: <strong>{engagementSummary.total_engaged_members} members</strong> &bull; Showing <strong>{filteredUniqueMembers || (engagements.length > 0 ? Array.from(new Set(engagements.map((e) => e.user?.id).filter(Boolean))).length : 0)} members</strong> ({engagementPagination.total} activity records)
                </div>

                <div style={{ fontSize: '11.5px', color: '#94a3b8' }}>
                  Total Events: <strong>{engagementSummary.total_engagements}</strong>
                </div>
              </div>

              {/* ========================================================= */}
              {/* BULK SELECTION ACTION BAR (When members are selected)     */}
              {/* ========================================================= */}
              {selectedMemberIds.length > 0 && (
                <div
                  style={{
                    padding: '12px 18px',
                    borderRadius: '14px',
                    background: 'linear-gradient(135deg, #1e293b 0%, #0f172a 100%)',
                    color: '#ffffff',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    flexWrap: 'wrap',
                    gap: '12px',
                    boxShadow: '0 4px 16px rgba(15, 23, 42, 0.2)',
                    border: '1px solid rgba(255, 255, 255, 0.1)',
                  }}
                >
                  <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                    <span
                      style={{
                        padding: '4px 10px',
                        borderRadius: '8px',
                        backgroundColor: '#4f7df3',
                        color: '#ffffff',
                        fontSize: '12px',
                        fontWeight: 800,
                      }}
                    >
                      {selectedMemberIds.length} Selected
                    </span>
                    <span style={{ fontSize: '12.5px', color: '#cbd5e1' }}>
                      ({selectedPhones.length} with WhatsApp, {selectedEmails.length} with Email)
                    </span>
                  </div>

                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' }}>
                    {/* Bulk Contact Button (Opens confirmation dialog) */}
                    <button
                      type="button"
                      onClick={() => handleOpenBulkContactModal('whatsapp')}
                      style={{
                        padding: '6px 13px',
                        borderRadius: '8px',
                        fontSize: '12px',
                        fontWeight: 700,
                        backgroundColor: 'rgba(37, 211, 102, 0.2)',
                        color: '#4ade80',
                        border: '1px solid rgba(74, 222, 128, 0.4)',
                        cursor: 'pointer',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '5px',
                      }}
                      title="Open Reach-Out Center for Selected Members"
                    >
                      <MessageSquare size={13} />
                      <span>Contact Selected</span>
                    </button>

                    <button
                      type="button"
                      onClick={handleCopySelectedPhones}
                      disabled={selectedPhones.length === 0}
                      style={{
                        padding: '6px 12px',
                        borderRadius: '8px',
                        fontSize: '12px',
                        fontWeight: 700,
                        backgroundColor: copiedKey === 'selected_phones' ? '#16a34a' : 'rgba(255, 255, 255, 0.1)',
                        color: copiedKey === 'selected_phones' ? '#ffffff' : '#e2e8f0',
                        border: '1px solid rgba(255, 255, 255, 0.2)',
                        cursor: selectedPhones.length === 0 ? 'not-allowed' : 'pointer',
                        opacity: selectedPhones.length === 0 ? 0.5 : 1,
                        display: 'flex',
                        alignItems: 'center',
                        gap: '5px',
                        transition: 'all 0.15s ease',
                      }}
                      title="Copy WhatsApp numbers of selected members"
                    >
                      {copiedKey === 'selected_phones' ? <Check size={13} /> : <Phone size={13} />}
                      <span>{copiedKey === 'selected_phones' ? 'Copied Phones!' : `Copy WhatsApps (${selectedPhones.length})`}</span>
                    </button>

                    <button
                      type="button"
                      onClick={handleCopySelectedEmails}
                      disabled={selectedEmails.length === 0}
                      style={{
                        padding: '6px 12px',
                        borderRadius: '8px',
                        fontSize: '12px',
                        fontWeight: 700,
                        backgroundColor: copiedKey === 'selected_emails' ? '#16a34a' : 'rgba(255, 255, 255, 0.1)',
                        color: copiedKey === 'selected_emails' ? '#ffffff' : '#e2e8f0',
                        border: '1px solid rgba(255, 255, 255, 0.2)',
                        cursor: selectedEmails.length === 0 ? 'not-allowed' : 'pointer',
                        opacity: selectedEmails.length === 0 ? 0.5 : 1,
                        display: 'flex',
                        alignItems: 'center',
                        gap: '5px',
                      }}
                      title="Copy emails of selected members"
                    >
                      {copiedKey === 'selected_emails' ? <Check size={13} /> : <Mail size={13} />}
                      <span>{copiedKey === 'selected_emails' ? 'Copied Emails!' : `Copy Emails (${selectedEmails.length})`}</span>
                    </button>

                    <button
                      type="button"
                      onClick={() => handleServerExport('selected')}
                      disabled={isExporting}
                      style={{
                        padding: '6px 12px',
                        borderRadius: '8px',
                        fontSize: '12px',
                        fontWeight: 700,
                        backgroundColor: '#4f7df3',
                        color: '#ffffff',
                        border: 'none',
                        cursor: isExporting ? 'not-allowed' : 'pointer',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '5px',
                      }}
                      title="Download selected members as CSV"
                    >
                      <Download size={13} className={isExporting ? 'animate-spin' : ''} />
                      <span>Export Selected</span>
                    </button>

                    <button
                      type="button"
                      onClick={handleClearSelection}
                      style={{
                        padding: '6px 10px',
                        borderRadius: '8px',
                        fontSize: '12px',
                        fontWeight: 600,
                        backgroundColor: 'transparent',
                        color: '#f87171',
                        border: '1px solid rgba(248, 113, 113, 0.4)',
                        cursor: 'pointer',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '4px',
                      }}
                      title="Clear member selections"
                    >
                      <X size={13} />
                      <span>Clear</span>
                    </button>
                  </div>
                </div>
              )}

              {/* Engagement Records Container */}
              <div
                style={{
                  border: '1px solid var(--color-border, #e2e8f0)',
                  borderRadius: '16px',
                  overflow: 'hidden',
                  backgroundColor: '#ffffff',
                }}
              >
                {engagementsLoading ? (
                  <div style={{ padding: '60px 20px', textAlign: 'center', color: '#64748b', fontSize: '13.5px' }}>
                    <RefreshCw size={28} className="animate-spin" style={{ margin: '0 auto 12px auto', display: 'block', color: '#4f7df3' }} />
                    Loading campaign audience and engagement records...
                  </div>
                ) : engagementsError ? (
                  <div style={{ padding: '32px 20px', textAlign: 'center', color: '#dc2626', fontSize: '13.5px' }}>
                    <AlertCircle size={28} style={{ margin: '0 auto 8px auto', display: 'block' }} />
                    <p style={{ margin: '0 0 12px 0', fontWeight: 600 }}>{engagementsError}</p>
                    <button
                      type="button"
                      className="btn btn-secondary"
                      onClick={fetchEngagements}
                      style={{ padding: '6px 14px', borderRadius: '8px', fontSize: '12.5px' }}
                    >
                      Retry Loading
                    </button>
                  </div>
                ) : engagements.length === 0 ? (
                  /* ========================================================= */
                  /* DISTINCT EMPTY STATES                                     */
                  /* ========================================================= */
                  <div style={{ padding: '60px 24px', textAlign: 'center', color: '#94a3b8' }}>
                    <div
                      style={{
                        width: '54px',
                        height: '54px',
                        borderRadius: '16px',
                        backgroundColor: 'rgba(79, 125, 243, 0.1)',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        color: '#4f7df3',
                        margin: '0 auto 14px auto',
                      }}
                    >
                      <Users size={26} />
                    </div>

                    {hasActiveFilters ? (
                      <div>
                        <h4 style={{ fontSize: '15px', fontWeight: 800, color: 'var(--color-text-main, #1e293b)', margin: '0 0 6px 0' }}>
                          No Matching Engagement Records
                        </h4>
                        <p style={{ fontSize: '13px', color: 'var(--color-text-secondary, #64748b)', margin: '0 0 14px 0', maxWidth: '440px', marginLeft: 'auto', marginRight: 'auto', lineHeight: 1.5 }}>
                          No activity events matched your active filters and search criteria. Try clearing or relaxing your filters.
                        </p>
                        <button
                          type="button"
                          className="btn btn-secondary"
                          onClick={handleClearAllFilters}
                          style={{ padding: '7px 16px', borderRadius: '8px', fontSize: '12.5px', fontWeight: 600 }}
                        >
                          Clear All Filters
                        </button>
                      </div>
                    ) : (
                      <div>
                        <h4 style={{ fontSize: '15px', fontWeight: 800, color: 'var(--color-text-main, #1e293b)', margin: '0 0 6px 0' }}>
                          No Engagement Recorded Yet
                        </h4>
                        <p style={{ fontSize: '13px', color: 'var(--color-text-secondary, #64748b)', margin: 0, maxWidth: '420px', marginLeft: 'auto', marginRight: 'auto', lineHeight: 1.5 }}>
                          When verified members view, express interest, or click your sponsored ad across their socials feed, their privacy-safe interactions and contact links will appear here.
                        </p>
                      </div>
                    )}
                  </div>
                ) : viewMode === 'table' ? (
                  /* ========================================================= */
                  /* TABLE VIEW                                                */
                  /* ========================================================= */
                  <div style={{ overflowX: 'auto' }}>
                    <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left', fontSize: '13px' }}>
                      <thead>
                        <tr
                          style={{
                            backgroundColor: 'var(--color-subtle-bg, #f8fafc)',
                            borderBottom: '1px solid #e2e8f0',
                            color: '#64748b',
                            fontSize: '11.5px',
                            fontWeight: 700,
                            letterSpacing: '0.04em',
                            textTransform: 'uppercase',
                          }}
                        >
                          {/* Select All Checkbox Column */}
                          <th style={{ padding: '12px 14px', width: '40px', textAlign: 'center' }}>
                            <input
                              type="checkbox"
                              checked={isAllOnPageSelected}
                              onChange={handleToggleSelectAllOnPage}
                              aria-label="Select all on current page"
                              style={{ cursor: 'pointer', width: '15px', height: '15px' }}
                            />
                          </th>
                          <th style={{ padding: '12px 16px' }}>Member / Lead</th>
                          <th style={{ padding: '12px 14px' }}>Action Event</th>
                          <th style={{ padding: '12px 14px' }}>Reward</th>
                          <th style={{ padding: '12px 14px' }}>Mobile / WhatsApp</th>
                          <th style={{ padding: '12px 14px' }}>Email</th>
                          <th style={{ padding: '12px 14px', textAlign: 'right' }}>Timestamp</th>
                          <th style={{ padding: '12px 16px', textAlign: 'center' }}>Contact Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        {engagements.map((item) => {
                          const pill = getActionPill(item.action_label || item.action, item.type);
                          const user = item.user;
                          const phoneClean = user?.phone ? String(user.phone).replace(/[^0-9+]/g, '') : null;
                          const hasPhone = Boolean(phoneClean);
                          const hasEmail = Boolean(user?.email);
                          const isReward = item.reward_amount_usd > 0;
                          const isSelected = user?.id ? selectedMemberIds.includes(user.id) : false;
                          const waMessage = encodeURIComponent(
                            `Hi ${user?.name || 'there'}, regarding our ad campaign "${currentCampaign.campaign_name}" on MLM_Book:`
                          );

                          return (
                            <tr
                              key={item.id}
                              style={{
                                borderBottom: '1px solid #f1f5f9',
                                backgroundColor: isSelected ? 'rgba(79, 125, 243, 0.05)' : 'transparent',
                                transition: 'background-color 0.15s ease',
                              }}
                              onMouseEnter={(e) => {
                                if (!isSelected) e.currentTarget.style.backgroundColor = '#f8fafc';
                              }}
                              onMouseLeave={(e) => {
                                if (!isSelected) e.currentTarget.style.backgroundColor = 'transparent';
                              }}
                            >
                              {/* Selection Checkbox */}
                              <td style={{ padding: '12px 14px', textAlign: 'center' }}>
                                {user?.id ? (
                                  <input
                                    type="checkbox"
                                    checked={isSelected}
                                    onChange={() => handleToggleSelectMember(user.id)}
                                    aria-label={`Select ${user.name}`}
                                    style={{ cursor: 'pointer', width: '15px', height: '15px' }}
                                  />
                                ) : (
                                  <span style={{ color: '#cbd5e1' }}>-</span>
                                )}
                              </td>

                              {/* Member Column */}
                              <td style={{ padding: '12px 16px' }}>
                                <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                                  <img
                                    src={getAvatarUrl(user?.avatar_url || user?.profile_photo)}
                                    alt={user?.name || 'Visitor'}
                                    style={{
                                      width: '36px',
                                      height: '36px',
                                      borderRadius: '50%',
                                      objectFit: 'cover',
                                      border: '1.5px solid #e2e8f0',
                                      flexShrink: 0,
                                      cursor: user?.id ? 'pointer' : 'default',
                                    }}
                                    onClick={() => user?.id && handleOpenMemberDetail(user, item)}
                                    onError={(e) => {
                                      e.target.src = '/member_assets/images/dashboard/image/profile.png';
                                    }}
                                  />

                                  <div>
                                    <div style={{ display: 'flex', alignItems: 'center', gap: '4px' }}>
                                      <span
                                        onClick={() => user?.id && handleOpenMemberDetail(user, item)}
                                        style={{
                                          fontWeight: 700,
                                          color: 'var(--color-text-main, #1e293b)',
                                          cursor: user?.id ? 'pointer' : 'default',
                                        }}
                                        title={user?.id ? 'Click to inspect member campaign timeline' : undefined}
                                      >
                                        {user?.name || 'Platform Visitor'}
                                      </span>
                                      {user?.is_verified && <VerifiedBadge member={user} size={15} />}
                                    </div>

                                    <div style={{ fontSize: '11.5px', color: '#64748b', display: 'flex', alignItems: 'center', gap: '6px' }}>
                                      {user?.username && <span>@{user.username}</span>}
                                      {(user?.city || user?.country) && (
                                        <span>&bull; {[user.city, user.country].filter(Boolean).join(', ')}</span>
                                      )}
                                    </div>
                                  </div>
                                </div>
                              </td>

                              {/* Action Column */}
                              <td style={{ padding: '12px 14px' }}>
                                <span
                                  style={{
                                    display: 'inline-flex',
                                    alignItems: 'center',
                                    gap: '5px',
                                    padding: '4px 10px',
                                    borderRadius: '16px',
                                    fontSize: '11.5px',
                                    fontWeight: 700,
                                    backgroundColor: pill.bg,
                                    color: pill.color,
                                    border: `1px solid ${pill.border}`,
                                  }}
                                >
                                  {pill.icon}
                                  <span>{pill.label}</span>
                                </span>
                              </td>

                              {/* Reward Column (Authoritative snapshot) */}
                              <td style={{ padding: '12px 14px' }}>
                                {isReward ? (
                                  <div>
                                    <div style={{ fontWeight: 800, color: '#059669', fontSize: '13px' }}>
                                      {item.reward_formatted}
                                    </div>
                                    {item.tier_label && (
                                      <div style={{ fontSize: '10.5px', color: '#047857', fontWeight: 600 }}>
                                        {item.tier_label}
                                      </div>
                                    )}
                                  </div>
                                ) : (
                                  <span style={{ color: '#94a3b8', fontSize: '12.5px' }}>$0.00</span>
                                )}
                              </td>

                              {/* Mobile / WhatsApp Column */}
                              <td style={{ padding: '12px 14px' }}>
                                {hasPhone ? (
                                  <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                                    <a
                                      href={`https://wa.me/${phoneClean}?text=${waMessage}`}
                                      target="_blank"
                                      rel="noreferrer"
                                      style={{
                                        color: '#15803d',
                                        fontWeight: 600,
                                        fontSize: '12.5px',
                                        textDecoration: 'none',
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: '4px',
                                      }}
                                      title="Open WhatsApp Chat"
                                    >
                                      <span>{user.phone}</span>
                                    </a>
                                    <button
                                      type="button"
                                      onClick={() => handleCopy(user.phone, `phone_${item.id}`)}
                                      style={{
                                        background: 'none',
                                        border: 'none',
                                        color: copiedKey === `phone_${item.id}` ? '#16a34a' : '#94a3b8',
                                        cursor: 'pointer',
                                        padding: '2px',
                                      }}
                                      title="Copy phone number"
                                    >
                                      {copiedKey === `phone_${item.id}` ? <Check size={13} /> : <Copy size={13} />}
                                    </button>
                                  </div>
                                ) : (
                                  <span style={{ color: '#cbd5e1', fontSize: '12px', fontStyle: 'italic' }}>
                                    Not provided
                                  </span>
                                )}
                              </td>

                              {/* Email Column */}
                              <td style={{ padding: '12px 14px' }}>
                                {hasEmail ? (
                                  <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                                    <a
                                      href={`mailto:${user.email}?subject=${encodeURIComponent(`MLM_Book Ad Campaign: ${currentCampaign.campaign_name}`)}`}
                                      style={{
                                        color: '#4f7df3',
                                        fontWeight: 600,
                                        fontSize: '12.5px',
                                        textDecoration: 'none',
                                      }}
                                      title="Send Email"
                                    >
                                      {user.email}
                                    </a>
                                    <button
                                      type="button"
                                      onClick={() => handleCopy(user.email, `email_${item.id}`)}
                                      style={{
                                        background: 'none',
                                        border: 'none',
                                        color: copiedKey === `email_${item.id}` ? '#16a34a' : '#94a3b8',
                                        cursor: 'pointer',
                                        padding: '2px',
                                      }}
                                      title="Copy email address"
                                    >
                                      {copiedKey === `email_${item.id}` ? <Check size={13} /> : <Copy size={13} />}
                                    </button>
                                  </div>
                                ) : (
                                  <span style={{ color: '#cbd5e1', fontSize: '12px', fontStyle: 'italic' }}>
                                    Not provided
                                  </span>
                                )}
                              </td>

                              {/* Timestamp Column */}
                              <td style={{ padding: '12px 14px', textAlign: 'right', fontSize: '12px', color: '#64748b' }}>
                                {item.created_at ? new Date(item.created_at).toLocaleString() : 'N/A'}
                              </td>

                              {/* Contact Actions Column */}
                              <td style={{ padding: '12px 16px', textAlign: 'center' }}>
                                <div style={{ display: 'inline-flex', alignItems: 'center', gap: '6px' }}>
                                  {hasPhone ? (
                                    <a
                                      href={`https://wa.me/${phoneClean}?text=${waMessage}`}
                                      target="_blank"
                                      rel="noreferrer"
                                      style={{
                                        padding: '5px 8px',
                                        borderRadius: '7px',
                                        backgroundColor: '#dcfce7',
                                        color: '#15803d',
                                        display: 'inline-flex',
                                        alignItems: 'center',
                                        textDecoration: 'none',
                                      }}
                                      title="Chat on WhatsApp"
                                    >
                                      <Phone size={13} />
                                    </a>
                                  ) : (
                                    <span
                                      style={{
                                        padding: '5px 8px',
                                        borderRadius: '7px',
                                        backgroundColor: '#f1f5f9',
                                        color: '#cbd5e1',
                                        display: 'inline-flex',
                                        alignItems: 'center',
                                        cursor: 'not-allowed',
                                      }}
                                      title="No phone number provided"
                                    >
                                      <PhoneOff size={13} />
                                    </span>
                                  )}

                                  {hasEmail ? (
                                    <a
                                      href={`mailto:${user.email}`}
                                      style={{
                                        padding: '5px 8px',
                                        borderRadius: '7px',
                                        backgroundColor: '#eff6ff',
                                        color: '#2563eb',
                                        display: 'inline-flex',
                                        alignItems: 'center',
                                        textDecoration: 'none',
                                      }}
                                      title="Send Email"
                                    >
                                      <Mail size={13} />
                                    </a>
                                  ) : (
                                    <span
                                      style={{
                                        padding: '5px 8px',
                                        borderRadius: '7px',
                                        backgroundColor: '#f1f5f9',
                                        color: '#cbd5e1',
                                        display: 'inline-flex',
                                        alignItems: 'center',
                                        cursor: 'not-allowed',
                                      }}
                                      title="No email address provided"
                                    >
                                      <MailX size={13} />
                                    </span>
                                  )}

                                  {user?.id && (
                                    <button
                                      type="button"
                                      onClick={() => handleOpenMemberDetail(user, item)}
                                      style={{
                                        padding: '5px 8px',
                                        borderRadius: '7px',
                                        backgroundColor: '#f1f5f9',
                                        color: '#475569',
                                        border: 'none',
                                        display: 'inline-flex',
                                        alignItems: 'center',
                                        cursor: 'pointer',
                                      }}
                                      title="Inspect Complete Member Campaign Detail & Timeline"
                                    >
                                      <Clock size={13} />
                                    </button>
                                  )}
                                </div>
                              </td>
                            </tr>
                          );
                        })}
                      </tbody>
                    </table>
                  </div>
                ) : (
                  /* ========================================================= */
                  /* GRID CARDS VIEW                                           */
                  /* ========================================================= */
                  <div
                    style={{
                      padding: '16px',
                      display: 'grid',
                      gridTemplateColumns: 'repeat(auto-fill, minmax(280px, 1fr))',
                      gap: '14px',
                    }}
                  >
                    {engagements.map((item) => {
                      const pill = getActionPill(item.action_label || item.action, item.type);
                      const user = item.user;
                      const phoneClean = user?.phone ? String(user.phone).replace(/[^0-9+]/g, '') : null;
                      const hasPhone = Boolean(phoneClean);
                      const hasEmail = Boolean(user?.email);
                      const isReward = item.reward_amount_usd > 0;
                      const isSelected = user?.id ? selectedMemberIds.includes(user.id) : false;
                      const waMessage = encodeURIComponent(
                        `Hi ${user?.name || 'there'}, regarding our ad campaign "${currentCampaign.campaign_name}" on MLM_Book:`
                      );

                      return (
                        <div
                          key={item.id}
                          style={{
                            padding: '16px',
                            borderRadius: '14px',
                            border: isSelected ? '1.5px solid #4f7df3' : '1px solid #e2e8f0',
                            backgroundColor: isSelected ? 'rgba(79, 125, 243, 0.03)' : '#ffffff',
                            boxShadow: '0 1px 3px rgba(0,0,0,0.05)',
                            display: 'flex',
                            flexDirection: 'column',
                            justifyContent: 'space-between',
                            gap: '12px',
                            position: 'relative',
                          }}
                        >
                          <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: '8px' }}>
                            <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                              {user?.id && (
                                <input
                                  type="checkbox"
                                  checked={isSelected}
                                  onChange={() => handleToggleSelectMember(user.id)}
                                  aria-label={`Select ${user.name}`}
                                  style={{ cursor: 'pointer', width: '15px', height: '15px' }}
                                />
                              )}

                              <img
                                src={getAvatarUrl(user?.avatar_url || user?.profile_photo)}
                                alt={user?.name || 'Visitor'}
                                style={{
                                  width: '42px',
                                  height: '42px',
                                  borderRadius: '50%',
                                  objectFit: 'cover',
                                  border: '1.5px solid #e2e8f0',
                                  cursor: user?.id ? 'pointer' : 'default',
                                }}
                                onClick={() => user?.id && handleOpenMemberDetail(user, item)}
                                onError={(e) => {
                                  e.target.src = '/member_assets/images/dashboard/image/profile.png';
                                }}
                              />
                              <div>
                                <div style={{ display: 'flex', alignItems: 'center', gap: '4px' }}>
                                  <h4
                                    onClick={() => user?.id && handleOpenMemberDetail(user, item)}
                                    style={{
                                      margin: 0,
                                      fontSize: '13.5px',
                                      fontWeight: 800,
                                      color: 'var(--color-text-main, #1e293b)',
                                      cursor: user?.id ? 'pointer' : 'default',
                                    }}
                                  >
                                    {user?.name || 'Visitor'}
                                  </h4>
                                  {user?.is_verified && <VerifiedBadge member={user} size={14} />}
                                </div>
                                <div style={{ fontSize: '11.5px', color: '#64748b' }}>
                                  {user?.username ? `@${user.username}` : 'Guest Interaction'}
                                </div>
                              </div>
                            </div>

                            <span
                              style={{
                                display: 'inline-flex',
                                alignItems: 'center',
                                gap: '4px',
                                padding: '3px 8px',
                                borderRadius: '12px',
                                fontSize: '11px',
                                fontWeight: 700,
                                backgroundColor: pill.bg,
                                color: pill.color,
                                border: `1px solid ${pill.border}`,
                              }}
                            >
                              {pill.icon}
                              <span>{pill.label}</span>
                            </span>
                          </div>

                          {/* Reward and Timestamp */}
                          <div
                            style={{
                              padding: '10px 12px',
                              borderRadius: '10px',
                              backgroundColor: '#f8fafc',
                              border: '1px solid #f1f5f9',
                              display: 'flex',
                              alignItems: 'center',
                              justifyContent: 'space-between',
                            }}
                          >
                            <div>
                              <span style={{ fontSize: '10.5px', color: '#64748b', textTransform: 'uppercase', fontWeight: 600 }}>
                                Reward
                              </span>
                              <div style={{ fontSize: '13px', fontWeight: 800, color: isReward ? '#059669' : '#94a3b8' }}>
                                {isReward ? item.reward_formatted : '$0.00'}
                              </div>
                            </div>

                            <div style={{ textAlign: 'right' }}>
                              <span style={{ fontSize: '10.5px', color: '#64748b', textTransform: 'uppercase', fontWeight: 600 }}>
                                Time
                              </span>
                              <div style={{ fontSize: '11.5px', color: '#475569', fontWeight: 600 }}>
                                {item.created_at ? new Date(item.created_at).toLocaleDateString() : 'N/A'}
                              </div>
                            </div>
                          </div>

                          {/* Contact CTAs */}
                          <div style={{ display: 'flex', alignItems: 'center', gap: '6px', paddingTop: '4px' }}>
                            {hasPhone ? (
                              <a
                                href={`https://wa.me/${phoneClean}?text=${waMessage}`}
                                target="_blank"
                                rel="noreferrer"
                                style={{
                                  flex: 1,
                                  padding: '7px 10px',
                                  borderRadius: '8px',
                                  backgroundColor: '#dcfce7',
                                  color: '#15803d',
                                  fontSize: '12px',
                                  fontWeight: 700,
                                  textDecoration: 'none',
                                  display: 'flex',
                                  alignItems: 'center',
                                  justifyContent: 'center',
                                  gap: '5px',
                                }}
                              >
                                <Phone size={13} />
                                <span>WhatsApp</span>
                              </a>
                            ) : (
                              <span
                                style={{
                                  flex: 1,
                                  padding: '7px 10px',
                                  borderRadius: '8px',
                                  backgroundColor: '#f1f5f9',
                                  color: '#cbd5e1',
                                  fontSize: '12px',
                                  fontWeight: 600,
                                  display: 'flex',
                                  alignItems: 'center',
                                  justifyContent: 'center',
                                  gap: '5px',
                                  cursor: 'not-allowed',
                                }}
                              >
                                <PhoneOff size={13} />
                                <span>No Phone</span>
                              </span>
                            )}

                            {hasEmail ? (
                              <a
                                href={`mailto:${user.email}`}
                                style={{
                                  flex: 1,
                                  padding: '7px 10px',
                                  borderRadius: '8px',
                                  backgroundColor: '#eff6ff',
                                  color: '#2563eb',
                                  fontSize: '12px',
                                  fontWeight: 700,
                                  textDecoration: 'none',
                                  display: 'flex',
                                  alignItems: 'center',
                                  justifyContent: 'center',
                                  gap: '5px',
                                }}
                              >
                                <Mail size={13} />
                                <span>Email</span>
                              </a>
                            ) : (
                              <span
                                style={{
                                  flex: 1,
                                  padding: '7px 10px',
                                  borderRadius: '8px',
                                  backgroundColor: '#f1f5f9',
                                  color: '#cbd5e1',
                                  fontSize: '12px',
                                  fontWeight: 600,
                                  display: 'flex',
                                  alignItems: 'center',
                                  justifyContent: 'center',
                                  gap: '5px',
                                  cursor: 'not-allowed',
                                }}
                              >
                                <MailX size={13} />
                                <span>No Email</span>
                              </span>
                            )}

                            {user?.id && (
                              <button
                                type="button"
                                onClick={() => handleOpenMemberDetail(user, item)}
                                style={{
                                  padding: '7px 10px',
                                  borderRadius: '8px',
                                  backgroundColor: '#f1f5f9',
                                  color: '#475569',
                                  border: 'none',
                                  cursor: 'pointer',
                                  display: 'flex',
                                  alignItems: 'center',
                                  justifyContent: 'center',
                                }}
                                title="Inspect Activity Timeline"
                              >
                                <Clock size={14} />
                              </button>
                            )}
                          </div>
                        </div>
                      );
                    })}
                  </div>
                )}

                {/* Pagination Controls */}
                {engagementPagination.last_page > 1 && (
                  <div
                    style={{
                      padding: '12px 20px',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'space-between',
                      borderTop: '1px solid #e2e8f0',
                      backgroundColor: 'var(--color-subtle-bg, #f8fafc)',
                      fontSize: '12.5px',
                      color: '#64748b',
                      flexWrap: 'wrap',
                      gap: '10px',
                    }}
                  >
                    <span>
                      Page <strong>{engagementPagination.current_page}</strong> of <strong>{engagementPagination.last_page}</strong> ({engagementPagination.total} total matching events)
                    </span>

                    <div style={{ display: 'flex', gap: '6px' }}>
                      <button
                        type="button"
                        disabled={engagementPage <= 1}
                        onClick={() => setEngagementPage((p) => Math.max(1, p - 1))}
                        style={{
                          padding: '6px 12px',
                          borderRadius: '8px',
                          border: '1px solid #cbd5e1',
                          backgroundColor: '#ffffff',
                          cursor: engagementPage <= 1 ? 'not-allowed' : 'pointer',
                          opacity: engagementPage <= 1 ? 0.5 : 1,
                          display: 'flex',
                          alignItems: 'center',
                          gap: '4px',
                          fontWeight: 600,
                        }}
                      >
                        <ChevronLeft size={14} />
                        <span>Previous</span>
                      </button>

                      <button
                        type="button"
                        disabled={engagementPage >= engagementPagination.last_page}
                        onClick={() => setEngagementPage((p) => p + 1)}
                        style={{
                          padding: '6px 12px',
                          borderRadius: '8px',
                          border: '1px solid #cbd5e1',
                          backgroundColor: '#ffffff',
                          cursor: engagementPage >= engagementPagination.last_page ? 'not-allowed' : 'pointer',
                          opacity: engagementPage >= engagementPagination.last_page ? 0.5 : 1,
                          display: 'flex',
                          alignItems: 'center',
                          gap: '4px',
                          fontWeight: 600,
                        }}
                      >
                        <span>Next</span>
                        <ChevronRight size={14} />
                      </button>
                    </div>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* ========================================================= */}
          {/* TAB 3: ADVANCED ANALYTICS & REWARD RECONCILIATION         */}
          {/* ========================================================= */}
          {activeTab === 'analytics' && (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '20px' }}>
              {/* Analytics Header Summary */}
              <div
                style={{
                  padding: '20px 24px',
                  borderRadius: '16px',
                  background: 'linear-gradient(135deg, #1e293b 0%, #0f172a 100%)',
                  color: '#ffffff',
                  boxShadow: '0 4px 20px rgba(15, 23, 42, 0.15)',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  flexWrap: 'wrap',
                  gap: '16px',
                }}
              >
                <div>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '6px' }}>
                    <span
                      style={{
                        fontSize: '11px',
                        fontWeight: 800,
                        letterSpacing: '0.05em',
                        padding: '3px 9px',
                        borderRadius: '20px',
                        backgroundColor: 'rgba(79, 125, 243, 0.25)',
                        color: '#93c5fd',
                        border: '1px solid rgba(147, 197, 253, 0.3)',
                        textTransform: 'uppercase',
                      }}
                    >
                      Campaign Performance & Financial Reconciliation
                    </span>
                  </div>
                  <h3 style={{ margin: 0, fontSize: '18px', fontWeight: 800, color: '#ffffff' }}>
                    {currentCampaign.campaign_name}
                  </h3>
                  <p style={{ margin: '4px 0 0', fontSize: '12.5px', color: '#94a3b8' }}>
                    Authoritative reconciliation across ad engagement activities, reward payouts, and dynamic tier slabs.
                  </p>
                </div>

                <button
                  type="button"
                  onClick={fetchEngagements}
                  disabled={engagementsLoading}
                  style={{
                    padding: '8px 14px',
                    borderRadius: '10px',
                    fontSize: '12.5px',
                    fontWeight: 700,
                    backgroundColor: 'rgba(255, 255, 255, 0.1)',
                    color: '#ffffff',
                    border: '1px solid rgba(255, 255, 255, 0.2)',
                    cursor: 'pointer',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '6px',
                  }}
                >
                  <RefreshCw size={14} className={engagementsLoading ? 'animate-spin' : ''} />
                  <span>Refresh Analytics</span>
                </button>
              </div>

              {/* Conversion & Engagement Rates Strip */}
              <div>
                <h4 style={{ fontSize: '13.5px', fontWeight: 800, color: 'var(--color-text-main, #1e293b)', margin: '0 0 10px 0', display: 'flex', alignItems: 'center', gap: '6px' }}>
                  <TrendingUp size={16} color="#4f7df3" />
                  Key Conversion & Engagement Metrics
                </h4>

                <div
                  style={{
                    display: 'grid',
                    gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))',
                    gap: '12px',
                  }}
                >
                  <div style={{ padding: '16px', borderRadius: '14px', backgroundColor: '#eff6ff', border: '1px solid #dbeafe' }}>
                    <div style={{ fontSize: '11.5px', fontWeight: 700, color: '#1d4ed8', textTransform: 'uppercase' }}>
                      Click-Through Rate (CTR)
                    </div>
                    <div style={{ fontSize: '24px', fontWeight: 800, color: '#1e3a8a', marginTop: '6px' }}>
                      {engagementSummary.rates?.ctr_percent || 0}%
                    </div>
                    <div style={{ fontSize: '11.5px', color: '#60a5fa', marginTop: '4px' }}>
                      {engagementSummary.click_count} clicks / {engagementSummary.total_engagements} total events
                    </div>
                  </div>

                  <div style={{ padding: '16px', borderRadius: '14px', backgroundColor: '#faf5ff', border: '1px solid #f3e8ff' }}>
                    <div style={{ fontSize: '11.5px', fontWeight: 700, color: '#7e22ce', textTransform: 'uppercase' }}>
                      Landing Visit Rate
                    </div>
                    <div style={{ fontSize: '24px', fontWeight: 800, color: '#581c87', marginTop: '6px' }}>
                      {engagementSummary.rates?.landing_conversion_rate || 0}%
                    </div>
                    <div style={{ fontSize: '11.5px', color: '#c084fc', marginTop: '4px' }}>
                      {engagementSummary.landing_visit_count} landing visits / {engagementSummary.click_count} clicks
                    </div>
                  </div>

                  <div style={{ padding: '16px', borderRadius: '14px', backgroundColor: '#ecfdf5', border: '1px solid #d1fae5' }}>
                    <div style={{ fontSize: '11.5px', fontWeight: 700, color: '#047857', textTransform: 'uppercase' }}>
                      Reward Conversion Rate
                    </div>
                    <div style={{ fontSize: '24px', fontWeight: 800, color: '#064e3b', marginTop: '6px' }}>
                      {engagementSummary.rates?.reward_conversion_rate || 0}%
                    </div>
                    <div style={{ fontSize: '11.5px', color: '#34d399', marginTop: '4px' }}>
                      {engagementSummary.total_rewards_count} rewarded / {engagementSummary.total_engaged_members} unique members
                    </div>
                  </div>
                </div>
              </div>

              {/* Dynamic Referral Tier Breakdown */}
              <div
                style={{
                  padding: '18px 20px',
                  borderRadius: '16px',
                  backgroundColor: '#ffffff',
                  border: '1px solid var(--color-border, #e2e8f0)',
                }}
              >
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '14px' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                    <Award size={18} color="#4f7df3" />
                    <h4 style={{ margin: 0, fontSize: '14.5px', fontWeight: 800, color: 'var(--color-text-main, #1e293b)' }}>
                      Historical Dynamic Referral Tier Payout Breakdown
                    </h4>
                  </div>
                  <span style={{ fontSize: '12px', color: '#64748b' }}>
                    Total: <strong>{engagementSummary.total_rewards_count} rewards paid</strong> (${engagementSummary.total_rewards_paid.toFixed(4)} USD)
                  </span>
                </div>

                {!engagementSummary.tier_breakdown || engagementSummary.tier_breakdown.length === 0 ? (
                  <div style={{ padding: '24px', textAlign: 'center', color: '#94a3b8', fontSize: '13px' }}>
                    No reward payouts have occurred yet for this campaign.
                  </div>
                ) : (
                  <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
                    {engagementSummary.tier_breakdown.map((tier, idx) => (
                      <div
                        key={idx}
                        style={{
                          padding: '12px 16px',
                          borderRadius: '12px',
                          backgroundColor: '#f8fafc',
                          border: '1px solid #e2e8f0',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'space-between',
                          flexWrap: 'wrap',
                          gap: '12px',
                        }}
                      >
                        <div style={{ flex: 1, minWidth: '200px' }}>
                          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '6px' }}>
                            <span style={{ fontSize: '13px', fontWeight: 700, color: '#1e293b' }}>
                              {tier.tier_label}
                            </span>
                            <span style={{ fontSize: '12.5px', fontWeight: 800, color: '#059669' }}>
                              ${tier.total_amount.toFixed(4)} USD ({tier.percentage}%)
                            </span>
                          </div>

                          <div style={{ width: '100%', height: '7px', borderRadius: '4px', backgroundColor: '#e2e8f0', overflow: 'hidden' }}>
                            <div
                              style={{
                                width: `${Math.min(100, Math.max(5, tier.percentage))}%`,
                                height: '100%',
                                backgroundColor: '#4f7df3',
                                borderRadius: '4px',
                              }}
                            />
                          </div>
                        </div>

                        <div style={{ textAlign: 'right', fontSize: '12px', color: '#64748b' }}>
                          <strong>{tier.count}</strong> rewards claimed
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>

              {/* 30-Day Daily Activity Trends */}
              <div
                style={{
                  padding: '18px 20px',
                  borderRadius: '16px',
                  backgroundColor: '#ffffff',
                  border: '1px solid var(--color-border, #e2e8f0)',
                }}
              >
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '14px' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                    <BarChart2 size={18} color="#4f7df3" />
                    <h4 style={{ margin: 0, fontSize: '14.5px', fontWeight: 800, color: 'var(--color-text-main, #1e293b)' }}>
                      Daily Activity & Engagement Velocity (Last 30 Days)
                    </h4>
                  </div>
                </div>

                {!engagementSummary.daily_trends || engagementSummary.daily_trends.length === 0 ? (
                  <div style={{ padding: '24px', textAlign: 'center', color: '#94a3b8', fontSize: '13px' }}>
                    No daily activity recorded in the last 30 days.
                  </div>
                ) : (
                  <div style={{ overflowX: 'auto' }}>
                    <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left', fontSize: '12.5px' }}>
                      <thead>
                        <tr style={{ backgroundColor: '#f8fafc', borderBottom: '1px solid #e2e8f0', color: '#64748b', fontWeight: 700, textTransform: 'uppercase', fontSize: '11px' }}>
                          <th style={{ padding: '10px 14px' }}>Date</th>
                          <th style={{ padding: '10px 14px' }}>Interested</th>
                          <th style={{ padding: '10px 14px' }}>Clicks</th>
                          <th style={{ padding: '10px 14px' }}>Landing Visits</th>
                          <th style={{ padding: '10px 14px' }}>Rewarded</th>
                          <th style={{ padding: '10px 14px', textAlign: 'right' }}>Total Events</th>
                        </tr>
                      </thead>
                      <tbody>
                        {engagementSummary.daily_trends.map((day, idx) => {
                          const dayTotal = day.interested + day.clicks + day.landing_visits + day.rewards;
                          return (
                            <tr key={idx} style={{ borderBottom: '1px solid #f1f5f9' }}>
                              <td style={{ padding: '10px 14px', fontWeight: 600, color: '#1e293b' }}>
                                {new Date(day.date).toLocaleDateString()}
                              </td>
                              <td style={{ padding: '10px 14px', color: '#b45309' }}>{day.interested}</td>
                              <td style={{ padding: '10px 14px', color: '#1d4ed8' }}>{day.clicks}</td>
                              <td style={{ padding: '10px 14px', color: '#7e22ce' }}>{day.landing_visits}</td>
                              <td style={{ padding: '10px 14px', color: '#047857', fontWeight: 700 }}>{day.rewards}</td>
                              <td style={{ padding: '10px 14px', textAlign: 'right', fontWeight: 800, color: '#1e293b' }}>
                                {dayTotal}
                              </td>
                            </tr>
                          );
                        })}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>

              {/* Financial Balance Reconciliation Card */}
              <div
                style={{
                  padding: '18px 20px',
                  borderRadius: '16px',
                  backgroundColor: '#f8fafc',
                  border: '1px solid #e2e8f0',
                }}
              >
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '12px' }}>
                  <DollarSign size={18} color="#16a34a" />
                  <h4 style={{ margin: 0, fontSize: '14.5px', fontWeight: 800, color: '#1e293b' }}>
                    Authoritative Financial Accounting Reconciliation
                  </h4>
                </div>

                <div
                  style={{
                    display: 'grid',
                    gridTemplateColumns: 'repeat(auto-fit, minmax(140px, 1fr))',
                    gap: '12px',
                  }}
                >
                  <div style={{ padding: '12px', borderRadius: '10px', backgroundColor: '#ffffff', border: '1px solid #e2e8f0' }}>
                    <span style={{ fontSize: '11px', color: '#64748b', fontWeight: 600 }}>Total Funded</span>
                    <div style={{ fontSize: '16px', fontWeight: 800, color: '#1e293b', marginTop: '3px' }}>
                      ${(engagementSummary.financials?.total_funded || totalFunded).toFixed(2)}
                    </div>
                  </div>

                  {((engagementSummary.financials?.platform_fee_amount || feeAmount) > 0) && (
                    <div style={{ padding: '12px', borderRadius: '10px', backgroundColor: '#ffffff', border: '1px solid #e2e8f0' }}>
                      <span style={{ fontSize: '11px', color: '#64748b', fontWeight: 600 }}>Platform Fee ({feePercent}%)</span>
                      <div style={{ fontSize: '16px', fontWeight: 800, color: '#64748b', marginTop: '3px' }}>
                        ${(engagementSummary.financials?.platform_fee_amount || feeAmount).toFixed(2)}
                      </div>
                    </div>
                  )}

                  <div style={{ padding: '12px', borderRadius: '10px', backgroundColor: '#ffffff', border: '1px solid #e2e8f0' }}>
                    <span style={{ fontSize: '11px', color: '#64748b', fontWeight: 600 }}>Authoritative Rewards Paid</span>
                    <div style={{ fontSize: '16px', fontWeight: 800, color: '#dc2626', marginTop: '3px' }}>
                      ${engagementSummary.total_rewards_paid.toFixed(4)}
                    </div>
                  </div>

                  <div style={{ padding: '12px', borderRadius: '10px', backgroundColor: '#ffffff', border: '1px solid #e2e8f0' }}>
                    <span style={{ fontSize: '11px', color: '#64748b', fontWeight: 600 }}>Current Running Budget</span>
                    <div style={{ fontSize: '16px', fontWeight: 800, color: isExhausted ? '#b91c1c' : '#15803d', marginTop: '3px' }}>
                      ${(engagementSummary.financials?.remaining_budget || remaining).toFixed(2)}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>

        {/* Footer Actions */}
        <div
          className="campaign-detail-modal-footer"
          style={{
            padding: '16px 24px',
            borderTop: '1px solid var(--color-border, #e2e8f0)',
            backgroundColor: 'var(--color-subtle-bg, #f8fafc)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            flexWrap: 'wrap',
            gap: '12px',
          }}
        >
          <div className="campaign-detail-modal-footer__info" style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <span style={{ fontSize: '12px', color: '#64748b' }}>
              Created: {currentCampaign.created_at ? new Date(currentCampaign.created_at).toLocaleDateString() : 'N/A'}
            </span>
          </div>

          <div className="campaign-detail-modal-footer__actions" style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            {/* Contextual lifecycle buttons for Overview tab */}
            {activeTab === 'overview' && isOwner && (
              <>
                {/* Draft -> Submit */}
                {status === 'draft' && (
                  <button
                    type="button"
                    className="btn btn-primary"
                    onClick={() => handleAction('submit')}
                    disabled={isActionLoading}
                    style={{
                      padding: '8px 18px',
                      borderRadius: '8px',
                      fontSize: '13px',
                      fontWeight: 700,
                      backgroundColor: '#4f7df3',
                      color: '#ffffff',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '6px',
                    }}
                  >
                    <Send size={15} />
                    <span>Submit for Review</span>
                  </button>
                )}

                {/* Active -> Pause */}
                {status === 'active' && (
                  <button
                    type="button"
                    className="btn"
                    onClick={() => handleAction('pause')}
                    disabled={isActionLoading}
                    style={{
                      padding: '8px 18px',
                      borderRadius: '8px',
                      fontSize: '13px',
                      fontWeight: 700,
                      backgroundColor: '#fff7ed',
                      color: '#c2410c',
                      border: '1px solid #ffedd5',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '6px',
                    }}
                  >
                    <PauseCircle size={15} />
                    <span>Pause Campaign</span>
                  </button>
                )}

                {/* Paused -> Resume */}
                {status === 'paused' && (
                  <button
                    type="button"
                    className="btn"
                    onClick={() => handleAction('resume')}
                    disabled={isActionLoading}
                    style={{
                      padding: '8px 18px',
                      borderRadius: '8px',
                      fontSize: '13px',
                      fontWeight: 700,
                      backgroundColor: '#f0fdf4',
                      color: '#15803d',
                      border: '1px solid #bbf7d0',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '6px',
                    }}
                  >
                    <PlayCircle size={15} />
                    <span>Resume Campaign</span>
                  </button>
                )}

                {/* Active/Paused -> Stop */}
                {(status === 'active' || status === 'paused' || status === 'approved') && (
                  <button
                    type="button"
                    className="btn"
                    onClick={() => handleAction('stop')}
                    disabled={isActionLoading}
                    style={{
                      padding: '8px 16px',
                      borderRadius: '8px',
                      fontSize: '13px',
                      fontWeight: 700,
                      backgroundColor: '#fef2f2',
                      color: '#b91c1c',
                      border: '1px solid #fecaca',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '6px',
                    }}
                  >
                    <StopCircle size={15} />
                    <span>Stop Campaign</span>
                  </button>
                )}
              </>
            )}

            <button
              type="button"
              className="btn btn-secondary campaign-detail-modal-close-btn"
              onClick={onClose}
              disabled={isActionLoading}
              style={{
                padding: '8px 18px',
                borderRadius: '8px',
                fontSize: '13px',
                fontWeight: 600,
              }}
            >
              Close
            </button>
          </div>
        </div>
      </div>

      {/* ========================================================= */}
      {/* BULK CONTACT OUTREACH CONFIRMATION MODAL                  */}
      {/* ========================================================= */}
      {bulkContactModalOpen && (
        <ModalPortal
          isOpen={bulkContactModalOpen}
          onClose={() => !isValidatingContact && setBulkContactModalOpen(false)}
          depth={1}
        >
          <div
            className="card"
            role="dialog"
            aria-modal="true"
            aria-labelledby="bulk-contact-modal-title"
            style={{
              width: '100%',
              maxWidth: '560px',
              borderRadius: '20px',
              overflow: 'hidden',
              boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.35)',
              backgroundColor: '#ffffff',
              display: 'flex',
              flexDirection: 'column',
            }}
          >
            {/* Modal Header */}
            <div
              style={{
                padding: '18px 22px',
                borderBottom: '1px solid #e2e8f0',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                background: 'linear-gradient(135deg, #1e293b 0%, #0f172a 100%)',
                color: '#ffffff',
              }}
            >
              <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                <MessageSquare size={20} color="#4f7df3" />
                <h3 style={{ margin: 0, fontSize: '16px', fontWeight: 800, color: '#ffffff' }}>
                  Audience Outreach Center
                </h3>
              </div>
              <button
                type="button"
                className="mini-button"
                onClick={() => setBulkContactModalOpen(false)}
                style={{
                  borderRadius: '50%',
                  padding: '6px',
                  backgroundColor: 'rgba(255, 255, 255, 0.1)',
                  color: '#ffffff',
                  border: 'none',
                }}
              >
                <X size={16} />
              </button>
            </div>

            {/* Modal Body */}
            <div style={{ padding: '20px 22px', display: 'flex', flexDirection: 'column', gap: '16px' }}>
              <div style={{ fontSize: '13px', color: '#475569', lineHeight: 1.5 }}>
                You are preparing to reach out to <strong>{selectedMemberIds.length} selected members</strong> from campaign <strong>{currentCampaign.campaign_name}</strong>.
              </div>

              {/* Method Selection Tabs */}
              <div>
                <span style={{ fontSize: '11.5px', fontWeight: 700, color: '#64748b', textTransform: 'uppercase', display: 'block', marginBottom: '6px' }}>
                  Select Reach-Out Method:
                </span>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '8px' }}>
                  <button
                    type="button"
                    onClick={() => {
                      setContactMethod('whatsapp');
                      handleOpenBulkContactModal('whatsapp');
                    }}
                    style={{
                      padding: '10px 12px',
                      borderRadius: '10px',
                      border: '1.5px solid',
                      borderColor: contactMethod === 'whatsapp' ? '#16a34a' : '#e2e8f0',
                      backgroundColor: contactMethod === 'whatsapp' ? '#f0fdf4' : '#ffffff',
                      color: contactMethod === 'whatsapp' ? '#15803d' : '#475569',
                      fontSize: '12.5px',
                      fontWeight: 700,
                      cursor: 'pointer',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      gap: '6px',
                    }}
                  >
                    <Phone size={14} />
                    <span>WhatsApp</span>
                  </button>

                  <button
                    type="button"
                    onClick={() => {
                      setContactMethod('email');
                      handleOpenBulkContactModal('email');
                    }}
                    style={{
                      padding: '10px 12px',
                      borderRadius: '10px',
                      border: '1.5px solid',
                      borderColor: contactMethod === 'email' ? '#2563eb' : '#e2e8f0',
                      backgroundColor: contactMethod === 'email' ? '#eff6ff' : '#ffffff',
                      color: contactMethod === 'email' ? '#1d4ed8' : '#475569',
                      fontSize: '12.5px',
                      fontWeight: 700,
                      cursor: 'pointer',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      gap: '6px',
                    }}
                  >
                    <Mail size={14} />
                    <span>Email</span>
                  </button>

                  <button
                    type="button"
                    onClick={() => {
                      setContactMethod('copy');
                      handleOpenBulkContactModal('copy');
                    }}
                    style={{
                      padding: '10px 12px',
                      borderRadius: '10px',
                      border: '1.5px solid',
                      borderColor: contactMethod === 'copy' ? '#4f7df3' : '#e2e8f0',
                      backgroundColor: contactMethod === 'copy' ? '#eef2ff' : '#ffffff',
                      color: contactMethod === 'copy' ? '#4338ca' : '#475569',
                      fontSize: '12.5px',
                      fontWeight: 700,
                      cursor: 'pointer',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      gap: '6px',
                    }}
                  >
                    <Copy size={14} />
                    <span>Copy List</span>
                  </button>
                </div>
              </div>

              {/* Validation Summary Card */}
              {isValidatingContact ? (
                <div style={{ padding: '16px', textAlign: 'center', color: '#64748b', fontSize: '13px' }}>
                  <RefreshCw size={18} className="animate-spin" style={{ margin: '0 auto 6px auto', display: 'block', color: '#4f7df3' }} />
                  Verifying campaign audience contact eligibility...
                </div>
              ) : contactValidationData && (
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
                  <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                    <span style={{ fontSize: '12.5px', fontWeight: 700, color: '#1e293b' }}>
                      Contactability Breakdown
                    </span>
                    <span style={{ fontSize: '11.5px', color: '#64748b' }}>
                      Total Selected: <strong>{contactValidationData.total_valid_in_campaign}</strong>
                    </span>
                  </div>

                  <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '8px' }}>
                    <div style={{ padding: '8px 12px', borderRadius: '8px', backgroundColor: '#f0fdf4', border: '1px solid #bbf7d0' }}>
                      <div style={{ fontSize: '11px', color: '#166534', fontWeight: 600 }}>Available to Reach</div>
                      <div style={{ fontSize: '16px', fontWeight: 800, color: '#15803d', marginTop: '2px' }}>
                        {contactValidationData.contactable_count} members
                      </div>
                    </div>

                    <div style={{ padding: '8px 12px', borderRadius: '8px', backgroundColor: '#fef2f2', border: '1px solid #fecaca' }}>
                      <div style={{ fontSize: '11px', color: '#991b1b', fontWeight: 600 }}>No Contact Info</div>
                      <div style={{ fontSize: '16px', fontWeight: 800, color: '#b91c1c', marginTop: '2px' }}>
                        {contactValidationData.unavailable_count} members
                      </div>
                    </div>
                  </div>
                </div>
              )}

              {/* Message Composer Preview */}
              <div>
                <label style={{ fontSize: '12px', fontWeight: 700, color: '#475569', display: 'block', marginBottom: '6px' }}>
                  Message Context Template:
                </label>
                <textarea
                  value={customContactMessage}
                  onChange={(e) => setCustomContactMessage(e.target.value)}
                  rows={3}
                  style={{
                    width: '100%',
                    padding: '10px 12px',
                    borderRadius: '10px',
                    border: '1px solid #cbd5e1',
                    fontSize: '12.5px',
                    color: '#1e293b',
                    resize: 'vertical',
                  }}
                  placeholder="Enter message context for the outreach..."
                />
              </div>
            </div>

            {/* Modal Footer */}
            <div
              style={{
                padding: '14px 22px',
                borderTop: '1px solid #e2e8f0',
                backgroundColor: '#f8fafc',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'flex-end',
                gap: '10px',
              }}
            >
              <button
                type="button"
                className="btn btn-secondary"
                onClick={() => setBulkContactModalOpen(false)}
                style={{ padding: '8px 16px', borderRadius: '8px', fontSize: '12.5px' }}
              >
                Cancel
              </button>

              <button
                type="button"
                onClick={handleExecuteBulkContact}
                disabled={isValidatingContact || !contactValidationData || contactValidationData.contactable_count === 0}
                style={{
                  padding: '8px 18px',
                  borderRadius: '8px',
                  fontSize: '13px',
                  fontWeight: 700,
                  backgroundColor: contactMethod === 'whatsapp' ? '#16a34a' : '#4f7df3',
                  color: '#ffffff',
                  border: 'none',
                  cursor: (!contactValidationData || contactValidationData.contactable_count === 0) ? 'not-allowed' : 'pointer',
                  opacity: (!contactValidationData || contactValidationData.contactable_count === 0) ? 0.5 : 1,
                  display: 'flex',
                  alignItems: 'center',
                  gap: '6px',
                }}
              >
                <Check size={14} />
                <span>
                  {contactMethod === 'whatsapp' ? `Launch WhatsApp (${contactValidationData?.contactable_count || 0})` : contactMethod === 'email' ? `Compose Email (${contactValidationData?.contactable_count || 0})` : `Copy List (${contactValidationData?.contactable_count || 0})`}
                </span>
              </button>
            </div>
          </div>
        </ModalPortal>
      )}

      {/* ========================================================= */}
      {/* PHASE 4: COMPLETE MEMBER ENGAGEMENT & REWARD DRILL-DOWN   */}
      {/* ========================================================= */}
      {selectedMemberModal && (
        <ModalPortal
          isOpen={Boolean(selectedMemberModal)}
          onClose={() => setSelectedMemberModal(null)}
          depth={1}
        >
          <div
            className="card"
            role="dialog"
            aria-modal="true"
            aria-labelledby="member-drilldown-modal-title"
            style={{
              width: '100%',
              maxWidth: '680px',
              maxHeight: 'min(90vh, 760px)',
              borderRadius: '20px',
              overflow: 'hidden',
              boxShadow: '0 25px 50px -12px rgba(15, 23, 42, 0.35)',
              backgroundColor: '#ffffff',
              display: 'flex',
              flexDirection: 'column',
            }}
          >
            {/* Member Modal Header */}
            <div
              style={{
                padding: '18px 24px',
                borderBottom: '1px solid #e2e8f0',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                background: 'linear-gradient(135deg, #1e293b 0%, #0f172a 100%)',
                color: '#ffffff',
              }}
            >
              <div style={{ display: 'flex', alignItems: 'center', gap: '14px' }}>
                <img
                  src={getAvatarUrl(selectedMemberModal.member.avatar_url || selectedMemberModal.member.profile_photo)}
                  alt={selectedMemberModal.member.name}
                  style={{
                    width: '48px',
                    height: '48px',
                    borderRadius: '50%',
                    objectFit: 'cover',
                    border: '2px solid #4f7df3',
                  }}
                  onError={(e) => {
                    e.target.src = '/member_assets/images/dashboard/image/profile.png';
                  }}
                />
                <div>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                    <h3 style={{ margin: 0, fontSize: '16px', fontWeight: 800, color: '#ffffff' }}>
                      {selectedMemberModal.member.name}
                    </h3>
                    {selectedMemberModal.member.is_verified && (
                      <VerifiedBadge member={selectedMemberModal.member} size={16} />
                    )}
                  </div>
                  <div style={{ fontSize: '12px', color: '#94a3b8', marginTop: '2px' }}>
                    <span>@{selectedMemberModal.member.username || 'member'}</span>
                    {(selectedMemberModal.member.city || selectedMemberModal.member.country) && (
                      <span> &bull; {[selectedMemberModal.member.city, selectedMemberModal.member.country].filter(Boolean).join(', ')}</span>
                    )}
                    {selectedMemberModal.member.joined_at && (
                      <span> &bull; Joined {new Date(selectedMemberModal.member.joined_at).toLocaleDateString()}</span>
                    )}
                  </div>
                </div>
              </div>

              <button
                type="button"
                className="mini-button"
                onClick={() => setSelectedMemberModal(null)}
                style={{
                  borderRadius: '50%',
                  padding: '7px',
                  backgroundColor: 'rgba(255, 255, 255, 0.1)',
                  color: '#ffffff',
                  border: 'none',
                }}
              >
                <X size={16} />
              </button>
            </div>

            {/* Member Modal Body */}
            <div style={{ padding: '20px 24px', overflowY: 'auto', flex: 1, display: 'flex', flexDirection: 'column', gap: '16px' }}>
              {/* Campaign Context Strip */}
              <div
                style={{
                  padding: '12px 16px',
                  borderRadius: '12px',
                  backgroundColor: '#f8fafc',
                  border: '1px solid #e2e8f0',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  flexWrap: 'wrap',
                  gap: '8px',
                }}
              >
                <div style={{ fontSize: '12.5px', color: '#475569' }}>
                  Campaign: <strong>{selectedMemberModal.campaign?.campaign_name || currentCampaign.campaign_name}</strong>
                  <span style={{ color: '#94a3b8', marginLeft: '6px' }}>
                    (ID: <code>{selectedMemberModal.campaign?.campaign_id || currentCampaign.campaign_id}</code>)
                  </span>
                </div>

                <div style={{ fontSize: '11.5px', color: '#64748b' }}>
                  Business Page: <strong>{page.page_name}</strong>
                </div>
              </div>

              {/* 4 Member Activity Summary Counters */}
              <div
                style={{
                  display: 'grid',
                  gridTemplateColumns: 'repeat(4, 1fr)',
                  gap: '10px',
                }}
              >
                <div style={{ padding: '12px', borderRadius: '12px', backgroundColor: '#eff6ff', border: '1px solid #dbeafe', textAlign: 'center' }}>
                  <span style={{ fontSize: '10.5px', fontWeight: 700, color: '#1d4ed8', textTransform: 'uppercase' }}>
                    Total Events
                  </span>
                  <div style={{ fontSize: '18px', fontWeight: 800, color: '#1e3a8a', marginTop: '3px' }}>
                    {selectedMemberModal.summary?.total_activities || selectedMemberModal.timeline?.length || 0}
                  </div>
                </div>

                <div style={{ padding: '12px', borderRadius: '12px', backgroundColor: '#fffbeb', border: '1px solid #fef3c7', textAlign: 'center' }}>
                  <span style={{ fontSize: '10.5px', fontWeight: 700, color: '#b45309', textTransform: 'uppercase' }}>
                    Interested
                  </span>
                  <div style={{ fontSize: '18px', fontWeight: 800, color: '#78350f', marginTop: '3px' }}>
                    {selectedMemberModal.summary?.interested_count || 0}
                  </div>
                </div>

                <div style={{ padding: '12px', borderRadius: '12px', backgroundColor: '#f5f3ff', border: '1px solid #ede9fe', textAlign: 'center' }}>
                  <span style={{ fontSize: '10.5px', fontWeight: 700, color: '#6d28d9', textTransform: 'uppercase' }}>
                    Clicks
                  </span>
                  <div style={{ fontSize: '18px', fontWeight: 800, color: '#4c1d95', marginTop: '3px' }}>
                    {selectedMemberModal.summary?.clicks_count || 0}
                  </div>
                </div>

                <div style={{ padding: '12px', borderRadius: '12px', backgroundColor: '#ecfdf5', border: '1px solid #d1fae5', textAlign: 'center' }}>
                  <span style={{ fontSize: '10.5px', fontWeight: 700, color: '#047857', textTransform: 'uppercase' }}>
                    Reward Status
                  </span>
                  <div
                    style={{
                      fontSize: '13px',
                      fontWeight: 800,
                      color: selectedMemberModal.summary?.is_rewarded ? '#065f46' : '#64748b',
                      marginTop: '5px',
                    }}
                  >
                    {selectedMemberModal.summary?.is_rewarded ? selectedMemberModal.summary?.reward_formatted : 'Not Rewarded'}
                  </div>
                </div>
              </div>

              {/* Authoritative Reward Detail Section */}
              {selectedMemberModal.reward_detail ? (
                <div
                  style={{
                    padding: '16px 20px',
                    borderRadius: '14px',
                    backgroundColor: '#ecfdf5',
                    border: '1.5px solid #a7f3d0',
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '12px',
                  }}
                >
                  <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                      <Award size={20} color="#059669" />
                      <h4 style={{ margin: 0, fontSize: '14.5px', fontWeight: 800, color: '#064e3b' }}>
                        Authoritative Reward Snapshot
                      </h4>
                    </div>
                    <span
                      style={{
                        padding: '3px 10px',
                        borderRadius: '12px',
                        backgroundColor: '#10b981',
                        color: '#ffffff',
                        fontSize: '11px',
                        fontWeight: 800,
                        textTransform: 'uppercase',
                      }}
                    >
                      {selectedMemberModal.reward_detail.status || 'Credited'}
                    </span>
                  </div>

                  <div
                    style={{
                      display: 'grid',
                      gridTemplateColumns: 'repeat(auto-fit, minmax(140px, 1fr))',
                      gap: '10px',
                      backgroundColor: '#ffffff',
                      padding: '12px 16px',
                      borderRadius: '10px',
                      border: '1px solid #d1fae5',
                    }}
                  >
                    <div>
                      <span style={{ fontSize: '11px', color: '#64748b', fontWeight: 600 }}>Reward Paid</span>
                      <div style={{ fontSize: '15px', fontWeight: 800, color: '#059669', marginTop: '2px' }}>
                        {selectedMemberModal.reward_detail.reward_formatted}
                      </div>
                    </div>

                    <div>
                      <span style={{ fontSize: '11px', color: '#64748b', fontWeight: 600 }}>Direct Referrals at Reward</span>
                      <div style={{ fontSize: '15px', fontWeight: 800, color: '#1e293b', marginTop: '2px' }}>
                        {selectedMemberModal.reward_detail.direct_verified_referral_count} referrals
                      </div>
                    </div>

                    <div>
                      <span style={{ fontSize: '11px', color: '#64748b', fontWeight: 600 }}>Applied Dynamic Tier</span>
                      <div style={{ fontSize: '13px', fontWeight: 800, color: '#4f7df3', marginTop: '2px' }}>
                        {selectedMemberModal.reward_detail.tier_label || 'Standard Tier'}
                      </div>
                    </div>

                    <div>
                      <span style={{ fontSize: '11px', color: '#64748b', fontWeight: 600 }}>Credited Date & Time</span>
                      <div style={{ fontSize: '12.5px', fontWeight: 700, color: '#475569', marginTop: '2px' }}>
                        {selectedMemberModal.reward_detail.credited_at
                          ? new Date(selectedMemberModal.reward_detail.credited_at).toLocaleString()
                          : 'N/A'}
                      </div>
                    </div>
                  </div>

                  {selectedMemberModal.reward_detail.transaction_reference && (
                    <div style={{ fontSize: '11.5px', color: '#047857' }}>
                      Ledger Ref: <code>{selectedMemberModal.reward_detail.transaction_reference}</code>
                      {selectedMemberModal.reward_detail.qualifying_event_id && (
                        <span> &bull; Event: <code>{selectedMemberModal.reward_detail.qualifying_event_id}</code></span>
                      )}
                    </div>
                  )}
                </div>
              ) : selectedMemberModal.summary?.reward_status === 'failed' ? (
                <div
                  style={{
                    padding: '14px 18px',
                    borderRadius: '12px',
                    backgroundColor: '#fff1f2',
                    border: '1px solid #fecdd3',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '10px',
                    color: '#9f1239',
                  }}
                >
                  <ShieldAlert size={20} color="#be123c" style={{ flexShrink: 0 }} />
                  <div style={{ fontSize: '13px', lineHeight: 1.4 }}>
                    <strong>Qualification Unsuccessful:</strong> Member attempted to qualify, but the reward was rejected (e.g. unverified mobile, budget depleted, or already rewarded once).
                  </div>
                </div>
              ) : (
                <div
                  style={{
                    padding: '14px 18px',
                    borderRadius: '12px',
                    backgroundColor: '#f8fafc',
                    border: '1px solid #e2e8f0',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '10px',
                    color: '#64748b',
                  }}
                >
                  <Clock size={18} color="#94a3b8" style={{ flexShrink: 0 }} />
                  <div style={{ fontSize: '13px', lineHeight: 1.4 }}>
                    <strong>Not Rewarded:</strong> Member interacted with this campaign (e.g., clicked or viewed) but has not yet completed a qualified landing page visit.
                  </div>
                </div>
              )}

              {/* Authorized Contact Information Card */}
              <div
                style={{
                  padding: '14px 18px',
                  borderRadius: '14px',
                  backgroundColor: '#f8fafc',
                  border: '1px solid #e2e8f0',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  flexWrap: 'wrap',
                  gap: '12px',
                }}
              >
                <div>
                  <div style={{ fontSize: '11px', fontWeight: 700, color: '#64748b', textTransform: 'uppercase', marginBottom: '4px' }}>
                    Authorized Direct Reach-Out
                  </div>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '14px', flexWrap: 'wrap', fontSize: '13px' }}>
                    {selectedMemberModal.member.phone && (
                      <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                        <Phone size={14} color="#16a34a" />
                        <span style={{ fontWeight: 600 }}>{selectedMemberModal.member.phone}</span>
                      </div>
                    )}
                    {selectedMemberModal.member.email && (
                      <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                        <Mail size={14} color="#2563eb" />
                        <span style={{ fontWeight: 600 }}>{selectedMemberModal.member.email}</span>
                      </div>
                    )}
                  </div>
                </div>

                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  {selectedMemberModal.member.phone && (
                    <a
                      href={`https://wa.me/${String(selectedMemberModal.member.phone).replace(/[^0-9+]/g, '')}?text=${encodeURIComponent(`Hi ${selectedMemberModal.member.name}, regarding our ad campaign "${currentCampaign.campaign_name}" on MLM_Book:`)}`}
                      target="_blank"
                      rel="noreferrer"
                      style={{
                        padding: '7px 12px',
                        borderRadius: '8px',
                        backgroundColor: '#dcfce7',
                        color: '#15803d',
                        fontWeight: 700,
                        fontSize: '12px',
                        textDecoration: 'none',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '5px',
                      }}
                    >
                      <Phone size={13} />
                      <span>WhatsApp Chat</span>
                    </a>
                  )}

                  {selectedMemberModal.member.email && (
                    <a
                      href={`mailto:${selectedMemberModal.member.email}?subject=${encodeURIComponent(`MLM_Book Ad Campaign: ${currentCampaign.campaign_name}`)}`}
                      style={{
                        padding: '7px 12px',
                        borderRadius: '8px',
                        backgroundColor: '#eff6ff',
                        color: '#2563eb',
                        fontWeight: 700,
                        fontSize: '12px',
                        textDecoration: 'none',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '5px',
                      }}
                    >
                      <Mail size={13} />
                      <span>Send Email</span>
                    </a>
                  )}
                </div>
              </div>

              {/* Complete Chronological Activity Timeline */}
              <div>
                <div style={{ fontSize: '13.5px', fontWeight: 800, color: 'var(--color-text-main, #1e293b)', marginBottom: '12px', display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <Clock size={16} color="#4f7df3" />
                  <span>Chronological Campaign Activity Audit</span>
                  <span style={{ fontSize: '11px', color: '#94a3b8', fontWeight: 600 }}>
                    ({selectedMemberModal.timeline?.length || 0} recorded events)
                  </span>
                </div>

                {memberDetailLoading ? (
                  <div style={{ padding: '30px 20px', textAlign: 'center', color: '#64748b', fontSize: '13px' }}>
                    <RefreshCw size={22} className="animate-spin" style={{ margin: '0 auto 8px auto', display: 'block', color: '#4f7df3' }} />
                    Loading interaction timeline...
                  </div>
                ) : memberDetailError ? (
                  <div style={{ padding: '20px', textAlign: 'center', color: '#dc2626', fontSize: '13px' }}>
                    {memberDetailError}
                  </div>
                ) : !selectedMemberModal.timeline || selectedMemberModal.timeline.length === 0 ? (
                  <div style={{ padding: '24px', textAlign: 'center', color: '#94a3b8', fontSize: '13px' }}>
                    No recorded timeline events for this member.
                  </div>
                ) : (
                  <div style={{ display: 'flex', flexDirection: 'column', gap: '10px', position: 'relative' }}>
                    {selectedMemberModal.timeline.map((evt, idx) => {
                      const pill = getActionPill(evt.action_label || evt.action, evt.type);
                      const isReward = evt.reward_amount_usd > 0;

                      return (
                        <div
                          key={evt.id || idx}
                          style={{
                            padding: '12px 16px',
                            borderRadius: '12px',
                            border: '1px solid #e2e8f0',
                            backgroundColor: '#ffffff',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'space-between',
                            gap: '12px',
                            transition: 'all 0.15s ease',
                          }}
                        >
                          <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                            <div
                              style={{
                                width: '34px',
                                height: '34px',
                                borderRadius: '50%',
                                backgroundColor: pill.bg,
                                color: pill.color,
                                border: `1px solid ${pill.border}`,
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                flexShrink: 0,
                              }}
                            >
                              {pill.icon}
                            </div>

                            <div>
                              <div style={{ fontSize: '13px', fontWeight: 700, color: 'var(--color-text-main, #1e293b)' }}>
                                {pill.label}
                              </div>
                              <div style={{ fontSize: '11.5px', color: '#94a3b8' }}>
                                {evt.created_at ? new Date(evt.created_at).toLocaleString() : 'N/A'}
                                {evt.event_id && (
                                  <span style={{ marginLeft: '6px' }}>&bull; Ref: <code>{evt.event_id}</code></span>
                                )}
                              </div>
                            </div>
                          </div>

                          <div style={{ textAlign: 'right' }}>
                            {isReward ? (
                              <div>
                                <div style={{ fontSize: '13px', fontWeight: 800, color: '#059669' }}>
                                  {evt.reward_formatted}
                                </div>
                                {evt.tier_label && (
                                  <div style={{ fontSize: '10.5px', color: '#047857', fontWeight: 600 }}>
                                    {evt.tier_label}
                                  </div>
                                )}
                              </div>
                            ) : (
                              <span style={{ fontSize: '12px', color: '#94a3b8' }}>$0.00</span>
                            )}
                          </div>
                        </div>
                      );
                    })}
                  </div>
                )}
              </div>
            </div>

            {/* Member Modal Footer */}
            <div
              style={{
                padding: '14px 24px',
                borderTop: '1px solid #e2e8f0',
                backgroundColor: '#f8fafc',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'flex-end',
                gap: '10px',
              }}
            >
              <button
                type="button"
                className="btn btn-secondary"
                onClick={() => setSelectedMemberModal(null)}
                style={{ padding: '7px 18px', borderRadius: '8px', fontSize: '13px', fontWeight: 600 }}
              >
                Back to People Engaged
              </button>
            </div>
          </div>
        </ModalPortal>
      )}
    </ModalPortal>
  );
}
