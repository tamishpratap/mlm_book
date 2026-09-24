import { useState, useEffect, useCallback, useMemo } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
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
  List,
  Filter,
  User,
  AlertCircle,
  PhoneCall,
} from 'lucide-react';
import eventApi from '../../api/eventApi';
import useAuth from '../../hooks/useAuth';
import VerifiedBadge from '../../components/common/VerifiedBadge';
import MemberAvatar from '../../components/common/MemberAvatar';
import { getAvatarUrl, getInitials } from '../../utils/assetHelper';
import AddFundsToEventCampaignModal from '../../components/events/AddFundsToEventCampaignModal';
import { DollarSign, TrendingUp, Wallet, Play, RefreshCw } from 'lucide-react';

export function EventOutreachPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user: currentUser } = useAuth();

  const [data, setData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  // Filters & Search
  const [searchQuery, setSearchQuery] = useState('');
  const [activeTab, setActiveTab] = useState('all'); // all, going, interested, with_phone, verified
  const [viewMode, setViewMode] = useState('table'); // table

  // Copy feedback states
  const [copiedKey, setCopiedKey] = useState(null);

  // Paid Campaign Analytics State
  const [campaignAnalytics, setCampaignAnalytics] = useState(null);
  const [showAddFundsModal, setShowAddFundsModal] = useState(false);
  const [campaignActionLoading, setCampaignActionLoading] = useState(false);

  const fetchOutreachData = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    try {
      const res = await eventApi.getOutreach(id);
      if (res && res.success) {
        setData(res);
      } else {
        setError(res?.message || 'Failed to load outreach data.');
      }

      // Check if event has a paid campaign and fetch analytics
      try {
        const campRes = await eventApi.getEventCampaignAnalytics(id);
        if (campRes && campRes.success) {
          setCampaignAnalytics(campRes);
        }
      } catch (e) {
        // Not a paid event or unauthorized
        setCampaignAnalytics(null);
      }
    } catch (err) {
      if (err.response?.status === 403) {
        setError('Unauthorized: Only the event host can access the Outreach & Contact Center.');
      } else {
        setError(err.response?.data?.message || 'Failed to load event outreach details.');
      }
    } finally {
      setIsLoading(false);
    }
  }, [id]);

  useEffect(() => {
    fetchOutreachData();
  }, [fetchOutreachData]);

  const event = data?.event || {};
  const stats = data?.stats || {
    total_attendees: 0,
    going_count: 0,
    interested_count: 0,
    with_phone_count: 0,
    with_email_count: 0,
    verified_members_count: 0,
  };

  const allMembers = useMemo(() => {
    return data?.all_members || [];
  }, [data]);

  const rewardMap = useMemo(() => {
    const map = {};
    if (campaignAnalytics?.participants?.data) {
      for (const p of campaignAnalytics.participants.data) {
        map[p.member_id] = p;
      }
    }
    return map;
  }, [campaignAnalytics]);

  // Filtered members list
  const filteredMembers = useMemo(() => {
    let list = [...allMembers];

    // Tab filter
    if (activeTab === 'going') {
      list = list.filter((m) => m.response_type === 'going');
    } else if (activeTab === 'interested') {
      list = list.filter((m) => m.response_type === 'interested');
    } else if (activeTab === 'with_phone') {
      list = list.filter((m) => Boolean(m.phone && m.phone.trim().length > 0));
    } else if (activeTab === 'verified') {
      list = list.filter((m) => Boolean(m.is_verified || m.mobile_verified_at));
    } else if (activeTab === 'rewarded') {
      list = list.filter((m) => Boolean(rewardMap[m.id]?.is_rewarded));
    }

    // Search query filter
    if (searchQuery.trim()) {
      const q = searchQuery.toLowerCase().trim();
      list = list.filter((m) => {
        const name = (m.name || '').toLowerCase();
        const username = (m.user_id || '').toLowerCase();
        const phone = (m.phone || '').toLowerCase();
        const email = (m.email || '').toLowerCase();
        const city = (m.city || '').toLowerCase();
        const country = (m.country || '').toLowerCase();
        return (
          name.includes(q) ||
          username.includes(q) ||
          phone.includes(q) ||
          email.includes(q) ||
          city.includes(q) ||
          country.includes(q)
        );
      });
    }

    return list;
  }, [allMembers, activeTab, searchQuery]);

  const handleReactivateCampaign = async () => {
    if (campaignActionLoading) return;
    setCampaignActionLoading(true);
    try {
      const res = await eventApi.reactivateEventCampaign(id);
      if (res && res.success) {
        // Refresh analytics
        const updatedRes = await eventApi.getEventCampaignAnalytics(id);
        if (updatedRes && updatedRes.success) {
          setCampaignAnalytics(updatedRes);
        }
        alert('Campaign reactivated successfully!');
      }
    } catch (err) {
      const msg = err?.response?.data?.message || 'Failed to reactivate campaign.';
      alert(msg);
    } finally {
      setCampaignActionLoading(false);
    }
  };

  const handleCampaignFunded = async (updatedCampaign) => {
    try {
      const campRes = await eventApi.getEventCampaignAnalytics(id);
      if (campRes && campRes.success) {
        setCampaignAnalytics(campRes);
      }
    } catch (e) {
      // ignore
    }
  };

  const handleCopy = (text, key) => {
    if (!text) return;
    navigator.clipboard.writeText(text);
    setCopiedKey(key);
    setTimeout(() => setCopiedKey(null), 2500);
  };

  const handleCopyAllPhones = () => {
    const phones = filteredMembers
      .map((m) => m.phone)
      .filter(Boolean)
      .map((p) => p.trim());
    if (phones.length === 0) {
      alert('No phone numbers available in current filter.');
      return;
    }
    navigator.clipboard.writeText(phones.join(', '));
    setCopiedKey('all_phones');
    setTimeout(() => setCopiedKey(null), 2500);
  };

  const handleCopyAllEmails = () => {
    const emails = filteredMembers
      .map((m) => m.email)
      .filter(Boolean)
      .map((e) => e.trim());
    if (emails.length === 0) {
      alert('No email addresses available in current filter.');
      return;
    }
    navigator.clipboard.writeText(emails.join(', '));
    setCopiedKey('all_emails');
    setTimeout(() => setCopiedKey(null), 2500);
  };

  const handleExportCSV = () => {
    if (filteredMembers.length === 0) {
      alert('No member records to export.');
      return;
    }

    const headers = ['Name', 'Username', 'RSVP Status', 'Phone', 'Email', 'Verified', 'City', 'Country'];
    const rows = filteredMembers.map((m) => [
      `"${(m.name || '').replace(/"/g, '""')}"`,
      `"${(m.user_id || '').replace(/"/g, '""')}"`,
      `"${m.response_type === 'going' ? 'Going' : 'Interested'}"`,
      `"${(m.phone || '').replace(/"/g, '""')}"`,
      `"${(m.email || '').replace(/"/g, '""')}"`,
      `"${m.is_verified || m.mobile_verified_at ? 'Yes' : 'No'}"`,
      `"${(m.city || '').replace(/"/g, '""')}"`,
      `"${(m.country || '').replace(/"/g, '""')}"`,
    ]);

    const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map((r) => r.join(','))].join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `Event_${event.id || 'outreach'}_Attendees_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  const getCleanWhatsAppNumber = (phoneStr) => {
    if (!phoneStr) return '';
    return phoneStr.replace(/[^0-9]/g, '');
  };

  if (isLoading) {
    return (
      <div className="event-outreach-loading" style={{ maxWidth: '1140px', margin: '40px auto', padding: '0 20px', textAlign: 'center' }}>
        <div className="card" style={{ padding: '60px 20px', borderRadius: '18px' }}>
          <div className="spinner fa-spin" style={{ margin: '0 auto 16px', width: '36px', height: '36px', border: '3px solid #e2e8f0', borderTopColor: '#059669', borderRadius: '50%' }} />
          <h2 style={{ fontSize: '18px', fontWeight: 700, margin: '0 0 8px 0' }}>Loading Host Outreach &amp; Contact Center...</h2>
          <p style={{ color: '#64748b', margin: 0, fontSize: '14px' }}>Gathering attendee contacts, phone numbers, and WhatsApp links.</p>
        </div>
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="event-outreach-error" style={{ maxWidth: '800px', margin: '40px auto', padding: '0 20px' }}>
        <div className="card" style={{ padding: '36px', borderRadius: '18px', textAlign: 'center', background: '#fff', border: '1px solid #fee2e2' }}>
          <AlertCircle size={44} color="#dc2626" style={{ margin: '0 auto 14px' }} />
          <h2 style={{ fontSize: '20px', fontWeight: 800, color: '#991b1b', margin: '0 0 8px 0' }}>Access Restricted</h2>
          <p style={{ color: '#4b5563', fontSize: '14.5px', marginBottom: '24px', lineHeight: 1.5 }}>
            {error || 'You do not have host permissions for this event.'}
          </p>
          <div style={{ display: 'flex', justifyContent: 'center', gap: '12px' }}>
            <button type="button" className="member-button member-button--secondary" onClick={() => navigate(-1)}>
              <ArrowLeft size={16} />
              <span>Go Back</span>
            </button>
            <Link to={`/member/events/${id}`} className="member-button member-button--primary">
              View Event Page
            </Link>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div
      className="event-outreach-page"
      style={{
        width: '100%',
        maxWidth: '1180px',
        minWidth: 0,
        margin: '24px auto',
        padding: '0 20px 60px 20px',
        boxSizing: 'border-box',
      }}
    >
      {/* Top Breadcrumb & Event Context Bar */}
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '20px', flexWrap: 'wrap', gap: '12px', width: '100%', maxWidth: '100%', boxSizing: 'border-box' }}>
        <Link
          to={`/member/events/${event.id}`}
          className="member-button member-button--secondary"
          style={{ padding: '8px 14px', fontSize: '13.5px', borderRadius: '10px' }}
        >
          <ArrowLeft size={16} aria-hidden="true" />
          <span>Back to Event Details</span>
        </Link>

        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
          <span className="community-badge" style={{ background: '#ecfdf5', color: '#065f46', border: '1px solid #a7f3d0', fontSize: '12px', fontWeight: 700, padding: '4px 10px', display: 'inline-flex', alignItems: 'center', gap: '5px' }}>
            <ShieldCheck size={14} color="#10b981" /> Host Contact Portal
          </span>
          <Link to={`/member/events/${event.id}/edit`} className="member-button member-button--secondary" style={{ padding: '7px 12px', fontSize: '13px' }}>
            Edit Event
          </Link>
        </div>
      </div>

      {/* Main Hero Header Card */}
      <header
        className="card"
        style={{
          background: 'linear-gradient(135deg, #064e3b 0%, #065f46 60%, #047857 100%)',
          color: '#ffffff',
          borderRadius: '20px',
          padding: '28px',
          marginBottom: '24px',
          boxShadow: '0 10px 25px -5px rgba(6, 78, 59, 0.25)',
          width: '100%',
          maxWidth: '100%',
          boxSizing: 'border-box',
          overflow: 'hidden',
        }}
      >
        <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', flexWrap: 'wrap', gap: '20px', width: '100%', maxWidth: '100%', boxSizing: 'border-box' }}>
          <div style={{ flex: '1 1 320px', minWidth: 0, maxWidth: '100%' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '8px', flexWrap: 'wrap' }}>
              <span style={{ fontSize: '12px', fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.05em', background: 'rgba(255, 255, 255, 0.18)', padding: '3px 9px', borderRadius: '6px' }}>
                {event.category || 'Event'}
              </span>
              <span style={{ fontSize: '12.5px', opacity: 0.9 }}>•</span>
              <span style={{ fontSize: '13px', opacity: 0.9, display: 'inline-flex', alignItems: 'center', gap: '4px' }}>
                {event.event_type === 'online' ? <Video size={13} /> : <MapPin size={13} />}
                {event.event_type === 'online' ? 'Online Event' : (event.location_city || 'In-Person')}
              </span>
            </div>

            <h1 style={{ fontSize: '26px', fontWeight: 800, margin: '0 0 10px 0', letterSpacing: '-0.02em', color: '#ffffff', wordBreak: 'break-word' }}>
              Host Outreach &amp; Contact Center
            </h1>

            <p style={{ margin: 0, fontSize: '15px', color: 'rgba(255, 255, 255, 0.9)', maxWidth: '720px', lineHeight: 1.5, wordBreak: 'break-word' }}>
              Manage, contact, and broadcast to all members interested in or attending <strong style={{ color: '#ffffff' }}>{event.title}</strong>.
            </p>
          </div>

          {/* Quick Batch Actions Box */}
          <div
            style={{
              display: 'flex',
              gap: '10px',
              flexWrap: 'wrap',
              alignItems: 'center',
              flexShrink: 0,
              maxWidth: '100%',
            }}
          >
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
                whiteSpace: 'nowrap',
              }}
              onClick={handleCopyAllPhones}
              title="Copy all phone numbers separated by comma for WhatsApp/SMS list"
            >
              {copiedKey === 'all_phones' ? <Check size={15} /> : <Phone size={15} />}
              <span>{copiedKey === 'all_phones' ? 'All Phones Copied!' : 'Copy All Phones'}</span>
            </button>

            <button
              type="button"
              className="member-button"
              style={{
                background: copiedKey === 'all_emails' ? '#10b981' : 'rgba(255, 255, 255, 0.2)',
                color: '#ffffff',
                border: '1px solid rgba(255, 255, 255, 0.35)',
                fontWeight: 600,
                fontSize: '13px',
                padding: '9px 15px',
                borderRadius: '10px',
                whiteSpace: 'nowrap',
              }}
              onClick={handleCopyAllEmails}
              title="Copy all email addresses separated by comma"
            >
              {copiedKey === 'all_emails' ? <Check size={15} /> : <Mail size={15} />}
              <span>{copiedKey === 'all_emails' ? 'All Emails Copied!' : 'Copy All Emails'}</span>
            </button>

            <button
              type="button"
              className="member-button"
              style={{
                background: '#ffffff',
                color: '#065f46',
                border: 'none',
                fontWeight: 700,
                fontSize: '13px',
                padding: '9px 15px',
                borderRadius: '10px',
                whiteSpace: 'nowrap',
              }}
              onClick={handleExportCSV}
              title="Download structured CSV contact list"
            >
              <Download size={15} />
              <span>Export CSV</span>
            </button>
          </div>
        </div>
      </header>

      {/* KPI Metrics Summary Row */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(190px, 1fr))', gap: '16px', marginBottom: '24px', width: '100%', maxWidth: '100%', boxSizing: 'border-box' }}>
        <div className="card" style={{ padding: '18px', borderRadius: '16px', border: '1px solid #e2e8f0', background: '#ffffff' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
            <div style={{ width: '44px', height: '44px', borderRadius: '12px', background: '#eff6ff', color: '#2563eb', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              <Users size={22} />
            </div>
            <div>
              <strong style={{ fontSize: '22px', fontWeight: 800, color: '#1e293b', display: 'block', lineHeight: 1.1 }}>
                {stats.total_attendees}
              </strong>
              <span style={{ fontSize: '12.5px', color: '#64748b', fontWeight: 500 }}>Total Responded</span>
            </div>
          </div>
        </div>

        <div className="card" style={{ padding: '18px', borderRadius: '16px', border: '1px solid #e2e8f0', background: '#ffffff' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
            <div style={{ width: '44px', height: '44px', borderRadius: '12px', background: '#ecfdf5', color: '#059669', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              <CheckCircle2 size={22} />
            </div>
            <div>
              <strong style={{ fontSize: '22px', fontWeight: 800, color: '#065f46', display: 'block', lineHeight: 1.1 }}>
                {stats.going_count}
              </strong>
              <span style={{ fontSize: '12.5px', color: '#64748b', fontWeight: 500 }}>Confirmed Going</span>
            </div>
          </div>
        </div>

        <div className="card" style={{ padding: '18px', borderRadius: '16px', border: '1px solid #e2e8f0', background: '#ffffff' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
            <div style={{ width: '44px', height: '44px', borderRadius: '12px', background: '#fffbeb', color: '#d97706', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              <Star size={22} />
            </div>
            <div>
              <strong style={{ fontSize: '22px', fontWeight: 800, color: '#92400e', display: 'block', lineHeight: 1.1 }}>
                {stats.interested_count}
              </strong>
              <span style={{ fontSize: '12.5px', color: '#64748b', fontWeight: 500 }}>Interested Leads</span>
            </div>
          </div>
        </div>

        <div className="card" style={{ padding: '18px', borderRadius: '16px', border: '1px solid #e2e8f0', background: '#ffffff' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
            <div style={{ width: '44px', height: '44px', borderRadius: '12px', background: 'rgba(37, 211, 102, 0.12)', color: '#16a34a', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              <PhoneCall size={22} />
            </div>
            <div>
              <strong style={{ fontSize: '22px', fontWeight: 800, color: '#15803d', display: 'block', lineHeight: 1.1 }}>
                {stats.with_phone_count}
              </strong>
              <span style={{ fontSize: '12.5px', color: '#64748b', fontWeight: 500 }}>With Mobile / WhatsApp</span>
            </div>
          </div>
        </div>

        <div className="card" style={{ padding: '18px', borderRadius: '16px', border: '1px solid #e2e8f0', background: '#ffffff' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
            <div style={{ width: '44px', height: '44px', borderRadius: '12px', background: '#f0fdf4', color: '#16a34a', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              <ShieldCheck size={22} />
            </div>
            <div>
              <strong style={{ fontSize: '22px', fontWeight: 800, color: '#15803d', display: 'block', lineHeight: 1.1 }}>
                {stats.verified_members_count}
              </strong>
              <span style={{ fontSize: '12.5px', color: '#64748b', fontWeight: 500 }}>Verified Members</span>
            </div>
          </div>
        </div>
      </div>

      {/* PAID EVENT CAMPAIGN & BUDGET ANALYTICS SECTION */}
      {campaignAnalytics && campaignAnalytics.budget_metrics && (
        <section
          className="card"
          style={{
            background: '#ffffff',
            borderRadius: '20px',
            border: '1px solid #e2e8f0',
            padding: '24px',
            marginBottom: '24px',
            boxShadow: '0 4px 16px rgba(0,0,0,0.04)',
            width: '100%',
            maxWidth: '100%',
            boxSizing: 'border-box',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '16px', marginBottom: '20px', paddingBottom: '16px', borderBottom: '1px solid #f1f5f9' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
              <div
                style={{
                  width: '42px',
                  height: '42px',
                  borderRadius: '12px',
                  background: 'linear-gradient(135deg, #10b981, #059669)',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  color: '#ffffff',
                }}
              >
                <DollarSign size={22} />
              </div>
              <div>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <h2 style={{ fontSize: '18px', fontWeight: 800, margin: 0, color: '#0f172a' }}>
                    Paid Campaign &amp; Budget Performance
                  </h2>
                  <span
                    style={{
                      fontSize: '11px',
                      fontWeight: 700,
                      padding: '2px 8px',
                      borderRadius: '6px',
                      background: campaignAnalytics.budget_metrics.is_exhausted
                        ? '#fee2e2'
                        : campaignAnalytics.budget_metrics.is_low_budget
                        ? '#fef3c7'
                        : '#dcfce7',
                      color: campaignAnalytics.budget_metrics.is_exhausted
                        ? '#b91c1c'
                        : campaignAnalytics.budget_metrics.is_low_budget
                        ? '#b45309'
                        : '#15803d',
                      border: `1px solid ${
                        campaignAnalytics.budget_metrics.is_exhausted
                          ? '#fca5a5'
                          : campaignAnalytics.budget_metrics.is_low_budget
                          ? '#fde68a'
                          : '#86efac'
                      }`,
                    }}
                  >
                    {campaignAnalytics.budget_metrics.is_exhausted
                      ? 'Budget Exhausted'
                      : campaignAnalytics.budget_metrics.is_low_budget
                      ? 'Budget Low'
                      : (campaignAnalytics.budget_metrics.display_status || 'Active')}
                  </span>
                </div>
                <p style={{ margin: '3px 0 0 0', fontSize: '13px', color: '#64748b' }}>
                  Authoritative server-side accounting, real-time budget depletion, and participant payout tracking.
                </p>
              </div>
            </div>

            <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
              {campaignAnalytics.budget_metrics.financial_reconciliation?.reconciles_exactly && (
                <span
                  style={{
                    fontSize: '12px',
                    fontWeight: 700,
                    color: '#059669',
                    background: '#ecfdf5',
                    padding: '6px 12px',
                    borderRadius: '8px',
                    border: '1px solid #a7f3d0',
                  }}
                  title="Total Funded = Spent Rewards + Remaining Budget exactly matches to the penny."
                >
                  ✓ 100% Reconciled
                </span>
              )}
              <button
                type="button"
                className="member-button member-button--primary"
                onClick={() => setShowAddFundsModal(true)}
                style={{
                  padding: '7px 14px',
                  borderRadius: '10px',
                  fontSize: '13px',
                  fontWeight: 700,
                  backgroundColor: '#10b981',
                  borderColor: '#059669',
                  color: '#ffffff',
                }}
              >
                <Wallet size={14} />
                <span>Add Campaign Funds</span>
              </button>
            </div>
          </div>

          {/* Exhausted Alert Banner */}
          {campaignAnalytics.budget_metrics.is_exhausted && (
            <div
              style={{
                background: '#fef2f2',
                border: '1px solid #fecaca',
                borderRadius: '12px',
                padding: '14px 18px',
                marginBottom: '20px',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                flexWrap: 'wrap',
                gap: '12px',
              }}
            >
              <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                <AlertCircle size={20} color="#dc2626" />
                <span style={{ fontSize: '13.5px', color: '#991b1b', fontWeight: 600 }}>
                  <strong>Campaign Budget Exhausted:</strong> Remaining running balance (${Number(campaignAnalytics.budget_metrics.remaining_amount).toFixed(2)} USD) is below the minimum active reward threshold (${Number(campaignAnalytics.budget_metrics.minimum_event_reward).toFixed(4)} USD). Top up funds to reactivate and resume reward payouts.
                </span>
              </div>
              <div style={{ display: 'flex', gap: '8px' }}>
                <button
                  type="button"
                  className="member-button member-button--secondary"
                  onClick={() => setShowAddFundsModal(true)}
                  style={{ fontSize: '12.5px', padding: '6px 12px' }}
                >
                  Add Funds
                </button>
                {campaignAnalytics.budget_metrics.can_reactivate && (
                  <button
                    type="button"
                    className="member-button member-button--primary"
                    onClick={handleReactivateCampaign}
                    disabled={campaignActionLoading}
                    style={{ fontSize: '12.5px', padding: '6px 12px', backgroundColor: '#16a34a' }}
                  >
                    <Play size={13} />
                    <span>{campaignActionLoading ? 'Reactivating...' : 'Reactivate Campaign'}</span>
                  </button>
                )}
              </div>
            </div>
          )}

          {/* Low Budget Alert Banner */}
          {campaignAnalytics.budget_metrics.is_low_budget && !campaignAnalytics.budget_metrics.is_exhausted && (
            <div
              style={{
                background: '#fffbeb',
                border: '1px solid #fde68a',
                borderRadius: '12px',
                padding: '14px 18px',
                marginBottom: '20px',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                flexWrap: 'wrap',
                gap: '12px',
              }}
            >
              <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                <AlertCircle size={20} color="#d97706" />
                <span style={{ fontSize: '13.5px', color: '#92400e', fontWeight: 600 }}>
                  <strong>Low Campaign Budget:</strong> Remaining balance (${Number(campaignAnalytics.budget_metrics.remaining_amount).toFixed(2)} USD) is approaching exhaustion. Top up soon to ensure uninterrupted reward payouts.
                </span>
              </div>
              <button
                type="button"
                className="member-button member-button--secondary"
                onClick={() => setShowAddFundsModal(true)}
                style={{ fontSize: '12.5px', padding: '6px 12px' }}
              >
                Add Funds
              </button>
            </div>
          )}

          {/* 4 Budget KPI Cards */}
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '16px' }}>
            <div style={{ background: '#f8fafc', borderRadius: '14px', border: '1px solid #e2e8f0', padding: '16px' }}>
              <span style={{ fontSize: '12px', color: '#64748b', fontWeight: 600, display: 'block', marginBottom: '4px' }}>
                Total Funded Budget
              </span>
              <strong style={{ fontSize: '22px', fontWeight: 800, color: '#0f172a' }}>
                ${Number(campaignAnalytics.budget_metrics.total_funded).toFixed(2)}
              </strong>
              <span style={{ fontSize: '11.5px', color: '#94a3b8', display: 'block', marginTop: '4px' }}>
                Initial: ${Number(campaignAnalytics.budget_metrics.initial_budget).toFixed(2)} + Top-up: ${Number(campaignAnalytics.budget_metrics.additional_funding).toFixed(2)}
              </span>
            </div>

            <div style={{ background: '#f8fafc', borderRadius: '14px', border: '1px solid #e2e8f0', padding: '16px' }}>
              <span style={{ fontSize: '12px', color: '#64748b', fontWeight: 600, display: 'block', marginBottom: '4px' }}>
                Rewards Paid Out
              </span>
              <strong style={{ fontSize: '22px', fontWeight: 800, color: '#16a34a' }}>
                ${Number(campaignAnalytics.budget_metrics.spent_amount).toFixed(2)}
              </strong>
              <span style={{ fontSize: '11.5px', color: '#94a3b8', display: 'block', marginTop: '4px' }}>
                {campaignAnalytics.participant_metrics.rewarded_participants} rewarded participants
              </span>
            </div>

            <div style={{ background: '#f8fafc', borderRadius: '14px', border: '1px solid #e2e8f0', padding: '16px' }}>
              <span style={{ fontSize: '12px', color: '#64748b', fontWeight: 600, display: 'block', marginBottom: '4px' }}>
                Remaining Running Budget
              </span>
              <strong style={{ fontSize: '22px', fontWeight: 800, color: campaignAnalytics.budget_metrics.is_exhausted ? '#dc2626' : (campaignAnalytics.budget_metrics.is_low_budget ? '#d97706' : '#0284c7') }}>
                ${Number(campaignAnalytics.budget_metrics.remaining_amount).toFixed(2)}
              </strong>
              <span style={{ fontSize: '11.5px', color: '#94a3b8', display: 'block', marginTop: '4px' }}>
                USD Available
              </span>
            </div>

            <div style={{ background: '#f8fafc', borderRadius: '14px', border: '1px solid #e2e8f0', padding: '16px' }}>
              <span style={{ fontSize: '12px', color: '#64748b', fontWeight: 600, display: 'block', marginBottom: '4px' }}>
                Min Valid Reward
              </span>
              <strong style={{ fontSize: '22px', fontWeight: 800, color: '#6366f1' }}>
                ${Number(campaignAnalytics.budget_metrics.minimum_event_reward).toFixed(4)}
              </strong>
              <span style={{ fontSize: '11.5px', color: '#94a3b8', display: 'block', marginTop: '4px' }}>
                Per verified participant
              </span>
            </div>
          </div>
        </section>
      )}

      {/* Main Directory & Outreach Container */}
      <section className="card" style={{ background: '#ffffff', borderRadius: '20px', border: '1px solid #e2e8f0', padding: '24px', width: '100%', maxWidth: '100%', boxSizing: 'border-box' }}>
        {/* Filter Controls Bar */}
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '16px', marginBottom: '20px', paddingBottom: '16px', borderBottom: '1px solid #f1f5f9' }}>
          {/* Tabs */}
          <div style={{ display: 'flex', gap: '8px', flexWrap: 'wrap', alignItems: 'center' }}>
            <button
              type="button"
              className={`event-attendees-tab-btn ${activeTab === 'all' ? 'active' : ''}`}
              onClick={() => setActiveTab('all')}
              style={{ padding: '7px 14px', borderRadius: '10px', fontSize: '13px', fontWeight: 700 }}
            >
              <Users size={14} />
              <span>All Responses ({stats.total_attendees})</span>
            </button>

            <button
              type="button"
              className={`event-attendees-tab-btn ${activeTab === 'going' ? 'active' : ''}`}
              onClick={() => setActiveTab('going')}
              style={{ padding: '7px 14px', borderRadius: '10px', fontSize: '13px', fontWeight: 700 }}
            >
              <CheckCircle2 size={14} color="#10b981" />
              <span>Going ({stats.going_count})</span>
            </button>

            <button
              type="button"
              className={`event-attendees-tab-btn ${activeTab === 'interested' ? 'active' : ''}`}
              onClick={() => setActiveTab('interested')}
              style={{ padding: '7px 14px', borderRadius: '10px', fontSize: '13px', fontWeight: 700 }}
            >
              <Star size={14} color="#f59e0b" />
              <span>Interested Leads ({stats.interested_count})</span>
            </button>

            <button
              type="button"
              className={`event-attendees-tab-btn ${activeTab === 'with_phone' ? 'active' : ''}`}
              onClick={() => setActiveTab('with_phone')}
              style={{ padding: '7px 14px', borderRadius: '10px', fontSize: '13px', fontWeight: 700 }}
            >
              <Phone size={14} color="#25D366" />
              <span>Has Mobile ({stats.with_phone_count})</span>
            </button>

            <button
              type="button"
              className={`event-attendees-tab-btn ${activeTab === 'verified' ? 'active' : ''}`}
              onClick={() => setActiveTab('verified')}
              style={{ padding: '7px 14px', borderRadius: '10px', fontSize: '13px', fontWeight: 700 }}
            >
              <ShieldCheck size={14} color="#10b981" />
              <span>Verified Only ({stats.verified_members_count})</span>
            </button>

            {campaignAnalytics && (
              <button
                type="button"
                className={`event-attendees-tab-btn ${activeTab === 'rewarded' ? 'active' : ''}`}
                onClick={() => setActiveTab('rewarded')}
                style={{ padding: '7px 14px', borderRadius: '10px', fontSize: '13px', fontWeight: 700, background: activeTab === 'rewarded' ? '#dcfce7' : '', color: activeTab === 'rewarded' ? '#15803d' : '' }}
              >
                <DollarSign size={14} color="#10b981" />
                <span>Rewarded Members ({campaignAnalytics.participant_metrics?.rewarded_participants || 0})</span>
              </button>
            )}
          </div>

          {/* Search Input & View Mode Toggle */}
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <div style={{ position: 'relative', minWidth: '240px' }}>
              <Search size={15} color="#94a3b8" style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)' }} />
              <input
                type="text"
                placeholder="Search name, phone, email, city..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="form-control"
                style={{ width: '100%', padding: '8px 12px 8px 34px', borderRadius: '10px', border: '1px solid #cbd5e1', fontSize: '13px' }}
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
            </div>
          </div>
        </div>

        {/* Member Directory Content */}
        {filteredMembers.length === 0 ? (
          <div style={{ textAlign: 'center', padding: '50px 20px', color: '#64748b' }}>
            <Users size={40} color="#cbd5e1" style={{ margin: '0 auto 12px' }} />
            <h3 style={{ fontSize: '16px', fontWeight: 700, margin: '0 0 6px 0', color: '#334155' }}>
              No attendees found matching current criteria
            </h3>
            <p style={{ fontSize: '13.5px', margin: 0 }}>
              {searchQuery ? 'Try clearing or changing your search terms.' : 'When members RSVP to your event, their mobile numbers and contact details will appear here.'}
            </p>
          </div>
        ) : viewMode === 'table' ? (
          /* Table View */
          <div style={{ overflowX: 'auto', width: '100%', maxWidth: '100%', boxSizing: 'border-box' }}>
            <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left', fontSize: '13.5px' }}>
              <thead>
                <tr style={{ borderBottom: '2px solid #e2e8f0', color: '#64748b', fontSize: '12px', textTransform: 'uppercase', letterSpacing: '0.04em' }}>
                  <th style={{ padding: '12px 14px' }}>Member</th>
                  <th style={{ padding: '12px 14px' }}>Status</th>
                  <th style={{ padding: '12px 14px' }}>Mobile / WhatsApp Number</th>
                  <th style={{ padding: '12px 14px' }}>Email Address</th>
                  <th style={{ padding: '12px 14px' }}>Location</th>
                  <th style={{ padding: '12px 14px', textAlign: 'right' }}>Quick Contact Actions</th>
                </tr>
              </thead>
              <tbody>
                {filteredMembers.map((member) => {
                  const avatarUrl = getAvatarUrl(member.profile_photo);
                  const initials = getInitials(member.name);
                  const isGoing = member.response_type === 'going';
                  const cleanPhone = getCleanWhatsAppNumber(member.phone);
                  const emailSubject = encodeURIComponent(`Regarding Event: ${event.title}`);
                  const emailBody = encodeURIComponent(`Hi ${member.name},\n\nThank you for your interest in "${event.title}".\n\n`);
                  const waText = encodeURIComponent(`Hi ${member.name}, I am the host of "${event.title}". Thank you for your interest in our event!`);

                  return (
                    <tr
                      key={`${member.id}-${member.response_type}`}
                      style={{
                        borderBottom: '1px solid #f1f5f9',
                        transition: 'background 0.15s ease',
                      }}
                      className="event-attendee-row"
                    >
                      {/* Member Info */}
                      <td style={{ padding: '14px' }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                          <Link to={`/member/people/${member.id}`} style={{ flexShrink: 0, textDecoration: 'none' }}>
                            <MemberAvatar member={member} size={42} />
                          </Link>
                          <div>
                            <Link
                              to={`/member/people/${member.id}`}
                              style={{ fontWeight: 700, color: '#0f172a', textDecoration: 'none', display: 'inline-flex', alignItems: 'center' }}
                            >
                              <span>{member.name}</span>
                              <VerifiedBadge member={member} size={14} />
                            </Link>
                            <span style={{ fontSize: '12px', color: '#64748b', display: 'block' }}>
                              @{member.user_id || 'member'}
                            </span>
                          </div>
                        </div>
                      </td>

                      {/* Status */}
                      <td style={{ padding: '14px' }}>
                        <span
                          style={{
                            display: 'inline-flex',
                            alignItems: 'center',
                            gap: '4px',
                            padding: '3px 9px',
                            borderRadius: '8px',
                            fontSize: '12px',
                            fontWeight: 700,
                            background: isGoing ? '#ecfdf5' : '#fffbeb',
                            color: isGoing ? '#065f46' : '#92400e',
                            border: `1px solid ${isGoing ? '#a7f3d0' : '#fde68a'}`,
                          }}
                        >
                          {isGoing ? <CheckCircle2 size={12} /> : <Star size={12} />}
                          <span>{isGoing ? 'Going' : 'Interested'}</span>
                        </span>
                      </td>

                      {/* Mobile Number */}
                      <td style={{ padding: '14px' }}>
                        {member.phone ? (
                          <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                            <Phone size={13} color="#059669" />
                            <strong style={{ color: '#0f172a', fontSize: '13.5px', fontFamily: 'monospace' }}>
                              {member.phone}
                            </strong>
                            <button
                              type="button"
                              onClick={() => handleCopy(member.phone, `phone-${member.id}`)}
                              style={{ background: 'transparent', border: 'none', cursor: 'pointer', padding: '2px 4px', color: copiedKey === `phone-${member.id}` ? '#10b981' : '#94a3b8' }}
                              title="Copy mobile number"
                            >
                              {copiedKey === `phone-${member.id}` ? <Check size={13} /> : <Copy size={13} />}
                            </button>
                          </div>
                        ) : (
                          <span style={{ color: '#94a3b8', fontSize: '12.5px', fontStyle: 'italic' }}>
                            Not provided
                          </span>
                        )}
                      </td>

                      {/* Email */}
                      <td style={{ padding: '14px' }}>
                        {member.email ? (
                          <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                            <span style={{ color: '#334155' }}>{member.email}</span>
                            <button
                              type="button"
                              onClick={() => handleCopy(member.email, `email-${member.id}`)}
                              style={{ background: 'transparent', border: 'none', cursor: 'pointer', padding: '2px 4px', color: copiedKey === `email-${member.id}` ? '#10b981' : '#94a3b8' }}
                              title="Copy email"
                            >
                              {copiedKey === `email-${member.id}` ? <Check size={13} /> : <Copy size={13} />}
                            </button>
                          </div>
                        ) : (
                          <span style={{ color: '#94a3b8', fontSize: '12.5px' }}>Not available</span>
                        )}
                      </td>

                      {/* Location */}
                      <td style={{ padding: '14px', color: '#64748b', fontSize: '12.5px' }}>
                        {[member.city, member.country].filter(Boolean).join(', ') || '—'}
                      </td>

                      {/* Quick Contact Actions */}
                      <td style={{ padding: '14px', textAlign: 'right' }}>
                        <div style={{ display: 'inline-flex', gap: '6px', alignItems: 'center' }}>
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
                                padding: '6px 10px',
                                fontSize: '12px',
                                fontWeight: 700,
                                borderRadius: '8px',
                              }}
                              title={`Chat on WhatsApp (${member.phone})`}
                            >
                              <MessageCircle size={13} />
                              <span>WhatsApp</span>
                            </a>
                          )}

                          {member.phone && (
                            <a
                              href={`tel:${member.phone}`}
                              className="member-button member-button--secondary"
                              style={{ padding: '6px 10px', fontSize: '12px', borderRadius: '8px' }}
                              title={`Call ${member.phone}`}
                            >
                              <Phone size={13} />
                              <span>Call</span>
                            </a>
                          )}

                          {member.email && (
                            <a
                              href={`mailto:${member.email}?subject=${emailSubject}&body=${emailBody}`}
                              className="member-button member-button--secondary"
                              style={{ padding: '6px 10px', fontSize: '12px', borderRadius: '8px' }}
                              title={`Send Email to ${member.email}`}
                            >
                              <Mail size={13} />
                              <span>Email</span>
                            </a>
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
          /* Grid Cards View */
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(min(100%, 280px), 1fr))', gap: '16px', width: '100%', maxWidth: '100%', boxSizing: 'border-box' }}>
            {filteredMembers.map((member) => {
              const avatarUrl = getAvatarUrl(member.profile_photo);
              const initials = getInitials(member.name);
              const isGoing = member.response_type === 'going';
              const cleanPhone = getCleanWhatsAppNumber(member.phone);
              const emailSubject = encodeURIComponent(`Regarding Event: ${event.title}`);
              const emailBody = encodeURIComponent(`Hi ${member.name},\n\nThank you for your interest in "${event.title}".\n\n`);
              const waText = encodeURIComponent(`Hi ${member.name}, I am the host of "${event.title}". Thank you for your interest in our event!`);

              return (
                <div
                  key={`card-${member.id}-${member.response_type}`}
                  style={{
                    background: '#f8fafc',
                    borderRadius: '16px',
                    border: '1px solid #e2e8f0',
                    padding: '18px',
                    display: 'flex',
                    flexDirection: 'column',
                    justifyContent: 'space-between',
                    gap: '14px',
                  }}
                >
                  {/* Top Profile Header */}
                  <div>
                    <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: '10px', marginBottom: '12px' }}>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                        <Link to={`/member/people/${member.id}`} style={{ flexShrink: 0, textDecoration: 'none' }}>
                          <MemberAvatar member={member} size={46} />
                        </Link>
                        <div>
                          <Link
                            to={`/member/people/${member.id}`}
                            style={{ fontWeight: 800, fontSize: '15px', color: '#0f172a', textDecoration: 'none', display: 'inline-flex', alignItems: 'center' }}
                          >
                            <span>{member.name}</span>
                            <VerifiedBadge member={member} size={15} />
                          </Link>
                          <span style={{ fontSize: '12px', color: '#64748b', display: 'block' }}>
                            @{member.user_id || 'member'}
                          </span>
                        </div>
                      </div>

                      <span
                        style={{
                          display: 'inline-flex',
                          alignItems: 'center',
                          gap: '4px',
                          padding: '3px 9px',
                          borderRadius: '8px',
                          fontSize: '11.5px',
                          fontWeight: 700,
                          background: isGoing ? '#ecfdf5' : '#fffbeb',
                          color: isGoing ? '#065f46' : '#92400e',
                          border: `1px solid ${isGoing ? '#a7f3d0' : '#fde68a'}`,
                        }}
                      >
                        {isGoing ? <CheckCircle2 size={12} /> : <Star size={12} />}
                        <span>{isGoing ? 'Going' : 'Interested'}</span>
                      </span>
                    </div>

                    {/* Contact Info Pills */}
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '8px', fontSize: '13px' }}>
                      {/* Mobile */}
                      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '6px 10px', background: '#ffffff', borderRadius: '8px', border: '1px solid #e2e8f0' }}>
                        <span style={{ display: 'inline-flex', alignItems: 'center', gap: '6px', color: '#059669', fontWeight: 600 }}>
                          <Phone size={13} />
                          <span>Mobile:</span>
                        </span>
                        {member.phone ? (
                          <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                            <strong style={{ color: '#0f172a', fontFamily: 'monospace' }}>{member.phone}</strong>
                            <button
                              type="button"
                              onClick={() => handleCopy(member.phone, `phone-${member.id}`)}
                              style={{ background: 'transparent', border: 'none', cursor: 'pointer', padding: '2px 4px', color: copiedKey === `phone-${member.id}` ? '#10b981' : '#94a3b8' }}
                              title="Copy mobile number"
                            >
                              {copiedKey === `phone-${member.id}` ? <Check size={13} /> : <Copy size={13} />}
                            </button>
                          </div>
                        ) : (
                          <span style={{ color: '#94a3b8', fontStyle: 'italic', fontSize: '12px' }}>Not provided</span>
                        )}
                      </div>

                      {/* Email */}
                      {member.email && (
                        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '6px 10px', background: '#ffffff', borderRadius: '8px', border: '1px solid #e2e8f0' }}>
                          <span style={{ display: 'inline-flex', alignItems: 'center', gap: '6px', color: '#64748b' }}>
                            <Mail size={13} />
                            <span>Email:</span>
                          </span>
                          <span style={{ color: '#0f172a', fontSize: '12.5px', maxWidth: '170px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                            {member.email}
                          </span>
                        </div>
                      )}

                      {/* Location */}
                      {(member.city || member.country) && (
                        <div style={{ display: 'flex', alignItems: 'center', gap: '6px', color: '#64748b', fontSize: '12px', paddingLeft: '4px' }}>
                          <MapPin size={12} color="#ef4444" />
                          <span>{[member.city, member.country].filter(Boolean).join(', ')}</span>
                        </div>
                      )}
                    </div>
                  </div>

                  {/* Actions Bar */}
                  <div style={{ display: 'flex', gap: '8px', paddingTop: '10px', borderTop: '1px solid #e2e8f0' }}>
                    {cleanPhone ? (
                      <a
                        href={`https://wa.me/${cleanPhone}?text=${waText}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="member-button"
                        style={{
                          flex: 1,
                          background: '#25D366',
                          color: '#ffffff',
                          border: 'none',
                          padding: '8px 12px',
                          fontSize: '12.5px',
                          fontWeight: 700,
                          borderRadius: '8px',
                          justifyContent: 'center',
                        }}
                      >
                        <MessageCircle size={14} />
                        <span>WhatsApp</span>
                      </a>
                    ) : member.email ? (
                      <a
                        href={`mailto:${member.email}?subject=${emailSubject}&body=${emailBody}`}
                        className="member-button member-button--primary"
                        style={{ flex: 1, padding: '8px 12px', fontSize: '12.5px', justifyContent: 'center' }}
                      >
                        <Mail size={14} />
                        <span>Send Email</span>
                      </a>
                    ) : null}

                    {member.phone && (
                      <a
                        href={`tel:${member.phone}`}
                        className="member-button member-button--secondary"
                        style={{ padding: '8px 12px', fontSize: '12.5px' }}
                        title={`Call ${member.phone}`}
                      >
                        <Phone size={14} />
                      </a>
                    )}

                    {cleanPhone && member.email && (
                      <a
                        href={`mailto:${member.email}?subject=${emailSubject}&body=${emailBody}`}
                        className="member-button member-button--secondary"
                        style={{ padding: '8px 12px', fontSize: '12.5px' }}
                        title={`Send Email to ${member.email}`}
                      >
                        <Mail size={14} />
                      </a>
                    )}

                    <Link
                      to={`/member/people/${member.id}`}
                      className="member-button member-button--secondary"
                      style={{ padding: '8px 12px', fontSize: '12.5px' }}
                      title="View Profile"
                    >
                      <User size={14} />
                    </Link>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </section>
      {/* Add Funds Modal */}
      {showAddFundsModal && (
        <AddFundsToEventCampaignModal
          event={event}
          campaign={campaignAnalytics?.campaign}
          availableAdFunds={data?.available_ad_funds}
          platformFeePercent={campaignAnalytics?.budget_metrics?.fee_percent || 2.5}
          onClose={() => setShowAddFundsModal(false)}
          onCampaignUpdated={handleCampaignFunded}
        />
      )}
    </div>
  );
}

export default EventOutreachPage;
