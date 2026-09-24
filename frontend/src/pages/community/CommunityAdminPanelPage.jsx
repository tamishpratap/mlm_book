import { useState, useEffect, useCallback } from 'react';
import { useParams, Link } from 'react-router-dom';
import {
  ArrowLeft,
  ExternalLink,
  Users,
  FileText,
  Clock,
  Flag,
  UserX,
  VolumeX,
  Volume2,
  LayoutDashboard,
  ShieldAlert,
  ShieldCheck,
  Settings,
  Zap,
  CheckCircle,
  Info,
  Smile,
  Save,
  RotateCw,
  AlertTriangle,
  BarChart3,
} from 'lucide-react';
import communityApi from '../../api/communityApi';
import { ModalPortal } from '../../components/common/ModalPortal';

export function CommunityAdminPanelPage() {
  const { slug } = useParams();

  const [activeTab, setActiveTab] = useState('overview');
  const [data, setData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState(null);
  const [feedback, setFeedback] = useState({ type: '', message: '' });

  // Settings form states
  const [settingsForm, setSettingsForm] = useState({
    name: '',
    category: '',
    visibility: 'public',
    posting_permissions: 'everyone',
    join_approval_mode: 'instant',
    description: '',
    rules: '',
  });
  const [isSavingSettings, setIsSavingSettings] = useState(false);

  // Transfer ownership state
  const [newOwnerId, setNewOwnerId] = useState('');
  const [isTransferring, setIsTransferring] = useState(false);

  // Quick Action Modal states (Warn, Mute, Ban)
  const [actionModal, setActionModal] = useState({
    type: null, // 'warn' | 'mute' | 'ban'
    memberId: '',
    reason: '',
    duration: '24h',
    isSubmitting: false,
  });

  const fetchAdminData = useCallback(
    (tab) => {
      if (!slug) return Promise.resolve(null);
      return communityApi.getAdminPanel(slug, tab);
    },
    [slug]
  );

  useEffect(() => {
    let isMounted = true;

    fetchAdminData(activeTab)
      .then((res) => {
        if (isMounted && res) {
          setData(res);
          if (res.community) {
            setSettingsForm({
              name: res.community.name || '',
              category: res.community.category || 'Technology',
              visibility: res.community.visibility || 'public',
              posting_permissions: res.community.posting_permissions || 'everyone',
              join_approval_mode: res.community.join_approval_mode || 'instant',
              description: res.community.description || '',
              rules: res.community.rules || '',
            });
          }
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Access to Community Admin Panel is restricted.');
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [fetchAdminData, activeTab]);

  const handleRefresh = () => {
    setIsRefreshing(true);
    fetchAdminData(activeTab)
      .then((res) => {
        if (res) setData(res);
      })
      .catch(() => {})
      .finally(() => setIsRefreshing(false));
  };

  const handleReportAction = async (reportId, status, deleteContent = false) => {
    if (!slug) return;
    try {
      const res = await communityApi.handleReport(slug, reportId, {
        status,
        delete_content: deleteContent,
      });
      setFeedback({ type: 'success', message: res.message || 'Report updated.' });
      handleRefresh();
      setTimeout(() => setFeedback({ type: '', message: '' }), 4000);
    } catch (err) {
      setFeedback({ type: 'error', message: err.response?.data?.message || 'Failed to update report.' });
    }
  };

  const handleUnbanMember = async (memberId) => {
    if (!slug) return;
    try {
      const res = await communityApi.unbanMember(slug, { member_id: memberId });
      setFeedback({ type: 'success', message: res.message || 'Member unbanned.' });
      handleRefresh();
      setTimeout(() => setFeedback({ type: '', message: '' }), 4000);
    } catch (err) {
      setFeedback({ type: 'error', message: err.response?.data?.message || 'Failed to unban member.' });
    }
  };

  const handleUnmuteMember = async (memberId) => {
    if (!slug) return;
    try {
      const res = await communityApi.unmuteMember(slug, { member_id: memberId });
      setFeedback({ type: 'success', message: res.message || 'Member unmuted.' });
      handleRefresh();
      setTimeout(() => setFeedback({ type: '', message: '' }), 4000);
    } catch (err) {
      setFeedback({ type: 'error', message: err.response?.data?.message || 'Failed to unmute member.' });
    }
  };

  const handleSaveSettings = async (e) => {
    e.preventDefault();
    if (isSavingSettings || !slug) return;
    setIsSavingSettings(true);
    try {
      const res = await communityApi.updateGovernanceSettings(slug, settingsForm);
      setFeedback({ type: 'success', message: res.message || 'Governance settings updated successfully.' });
      handleRefresh();
      setTimeout(() => setFeedback({ type: '', message: '' }), 4000);
    } catch (err) {
      setFeedback({ type: 'error', message: err.response?.data?.message || 'Failed to save settings.' });
    } finally {
      setIsSavingSettings(false);
    }
  };

  const handleTransferOwnership = async (e) => {
    e.preventDefault();
    if (isTransferring || !slug || !newOwnerId) return;
    if (!window.confirm('Are you sure you want to transfer full ownership? This cannot be undone.')) return;

    setIsTransferring(true);
    try {
      const res = await communityApi.transferOwnership(slug, { new_owner_id: parseInt(newOwnerId, 10) });
      setFeedback({ type: 'success', message: res.message || 'Ownership transferred.' });
      handleRefresh();
      setTimeout(() => setFeedback({ type: '', message: '' }), 4000);
    } catch (err) {
      setFeedback({ type: 'error', message: err.response?.data?.message || 'Failed to transfer ownership.' });
    } finally {
      setIsTransferring(false);
    }
  };

  const handleModalSubmit = async (e) => {
    e.preventDefault();
    if (!actionModal.type || !actionModal.memberId || actionModal.isSubmitting || !slug) return;

    setActionModal((prev) => ({ ...prev, isSubmitting: true }));
    try {
      let res;
      if (actionModal.type === 'warn') {
        res = await communityApi.warnMember(slug, {
          member_id: parseInt(actionModal.memberId, 10),
          reason: actionModal.reason.trim(),
        });
      } else if (actionModal.type === 'mute') {
        res = await communityApi.muteMember(slug, {
          member_id: parseInt(actionModal.memberId, 10),
          reason: actionModal.reason.trim(),
          duration: actionModal.duration,
        });
      } else if (actionModal.type === 'ban') {
        res = await communityApi.banMember(slug, {
          member_id: parseInt(actionModal.memberId, 10),
          reason: actionModal.reason.trim(),
          duration: actionModal.duration,
        });
      }
      setFeedback({ type: 'success', message: res?.message || 'Action executed successfully.' });
      setActionModal({ type: null, memberId: '', reason: '', duration: '24h', isSubmitting: false });
      handleRefresh();
      setTimeout(() => setFeedback({ type: '', message: '' }), 4000);
    } catch (err) {
      setFeedback({ type: 'error', message: err.response?.data?.message || 'Failed to execute moderation action.' });
      setActionModal((prev) => ({ ...prev, isSubmitting: false }));
    }
  };

  if (isLoading && !data) {
    return (
      <div style={{ padding: '40px', textAlign: 'center', color: 'var(--color-text-secondary)' }}>
        Loading community moderation dashboard...
      </div>
    );
  }

  if (error || !data?.community) {
    return (
      <div>
        <div style={{ marginBottom: '16px' }}>
          <Link
            to={`/member/community/${slug}`}
            style={{
              display: 'inline-flex',
              alignItems: 'center',
              gap: '6px',
              color: 'var(--color-text-secondary)',
              fontSize: '0.875rem',
              textDecoration: 'none',
            }}
          >
            <ArrowLeft size={16} />
            <span>Back to Community</span>
          </Link>
        </div>
        <div className="notification-empty" role="alert">
          <h2>Access Restricted</h2>
          <p>{error || 'You do not have moderator or administrator privileges in this community.'}</p>
          <Link to={`/member/community/${slug}`} className="member-button member-button--primary" style={{ marginTop: '12px' }}>
            Return to Community
          </Link>
        </div>
      </div>
    );
  }

  const community = data.community;
  const isOwner = Boolean(data.is_owner);
  const isAdmin = Boolean(data.is_admin);

  const reportsList = data.reports?.data || data.reports || [];
  const auditLogsList = data.audit_logs?.data || data.audit_logs || [];
  const bannedList = data.banned_members || [];
  const mutedList = data.muted_members || [];
  const warningsList = data.warnings || [];
  const acceptedMembers = community.accepted_members || [];

  return (
    <div className="community-page community-admin-container">
      {/* Feedback Toast */}
      {feedback.message && (
        <div
          style={{
            padding: '12px 16px',
            borderRadius: '10px',
            marginBottom: '16px',
            fontSize: '0.875rem',
            fontWeight: 500,
            background: feedback.type === 'error' ? '#fee2e2' : '#dcfce7',
            color: feedback.type === 'error' ? '#b91c1c' : '#15803d',
            border: `1px solid ${feedback.type === 'error' ? '#fca5a5' : '#86efac'}`,
          }}
        >
          {feedback.message}
        </div>
      )}

      {/* Admin Header Banner */}
      <div className="community-admin-header">
        <div className="community-admin-header__top">
          <Link to={`/member/community/${community.slug}`} className="community-admin-btn community-admin-btn--secondary community-admin-btn--sm">
            <ArrowLeft size={14} aria-hidden="true" />
            <span>Back to Community</span>
          </Link>

          <div style={{ display: 'flex', gap: '10px', alignItems: 'center' }}>
            <button
              type="button"
              className="community-admin-btn community-admin-btn--secondary community-admin-btn--sm"
              onClick={handleRefresh}
              disabled={isRefreshing}
              title="Refresh Dashboard"
            >
              <RotateCw size={14} className={isRefreshing ? 'fa-spin' : ''} />
            </button>
            <Link
              to={`/member/community/${community.slug}/analytics`}
              className="community-admin-btn community-admin-btn--secondary community-admin-btn--sm"
              title="View Community Analytics"
            >
              <BarChart3 size={14} aria-hidden="true" />
              <span>Analytics</span>
            </Link>
            <Link
              to={`/member/community/${community.slug}`}
              target="_blank"
              rel="noreferrer"
              className="community-admin-btn community-admin-btn--secondary community-admin-btn--sm"
              title="View Public Community Page"
            >
              <ExternalLink size={14} aria-hidden="true" />
              <span>View Public View</span>
            </Link>
          </div>
        </div>

        <div className="community-admin-header__title-area">
          <div className="community-admin-header__title-row">
            <h1 className="community-admin-header__title">{community.name}</h1>
            <span className="community-admin-badge">
              <ShieldCheck size={14} aria-hidden="true" />
              <span>Admin & Governance</span>
            </span>
          </div>
          <p className="community-admin-header__desc">
            Manage community access, member permissions, moderation reports, audit logs, and global governance settings.
          </p>
        </div>
      </div>

      {/* Overview Stats Grid */}
      <div className="community-admin-stats-grid">
        <div className="community-admin-stat-card community-admin-stat-card--blue">
          <div className="community-admin-stat-card__header">
            <span className="community-admin-stat-card__label">Total Members</span>
            <div className="community-admin-stat-card__icon-wrap">
              <Users size={16} aria-hidden="true" />
            </div>
          </div>
          <div className="community-admin-stat-card__value">{Number(data.member_count || 0).toLocaleString()}</div>
        </div>

        <div className="community-admin-stat-card community-admin-stat-card--indigo">
          <div className="community-admin-stat-card__header">
            <span className="community-admin-stat-card__label">Total Posts</span>
            <div className="community-admin-stat-card__icon-wrap">
              <FileText size={16} aria-hidden="true" />
            </div>
          </div>
          <div className="community-admin-stat-card__value">{Number(data.post_count || 0).toLocaleString()}</div>
        </div>

        <div className="community-admin-stat-card community-admin-stat-card--amber">
          <div className="community-admin-stat-card__header">
            <span className="community-admin-stat-card__label">Pending Requests</span>
            <div className="community-admin-stat-card__icon-wrap">
              <Clock size={16} aria-hidden="true" />
            </div>
          </div>
          <div className="community-admin-stat-card__value">{Number(data.pending_requests_count || 0).toLocaleString()}</div>
        </div>

        <div className="community-admin-stat-card community-admin-stat-card--rose">
          <div className="community-admin-stat-card__header">
            <span className="community-admin-stat-card__label">Pending Reports</span>
            <div className="community-admin-stat-card__icon-wrap">
              <Flag size={16} aria-hidden="true" />
            </div>
          </div>
          <div className="community-admin-stat-card__value">{Number(data.pending_reports_count || 0).toLocaleString()}</div>
        </div>

        <div className="community-admin-stat-card community-admin-stat-card--slate">
          <div className="community-admin-stat-card__header">
            <span className="community-admin-stat-card__label">Banned Members</span>
            <div className="community-admin-stat-card__icon-wrap">
              <UserX size={16} aria-hidden="true" />
            </div>
          </div>
          <div className="community-admin-stat-card__value">{Number(data.banned_count || 0).toLocaleString()}</div>
        </div>

        <div className="community-admin-stat-card community-admin-stat-card--purple">
          <div className="community-admin-stat-card__header">
            <span className="community-admin-stat-card__label">Muted Members</span>
            <div className="community-admin-stat-card__icon-wrap">
              <VolumeX size={16} aria-hidden="true" />
            </div>
          </div>
          <div className="community-admin-stat-card__value">{Number(data.muted_count || 0).toLocaleString()}</div>
        </div>
      </div>

      {/* Admin Navigation Tabs */}
      <nav className="community-admin-nav" aria-label="Admin panel navigation">
        <button
          type="button"
          className={`community-admin-tab ${activeTab === 'overview' ? 'is-active' : ''}`}
          onClick={() => setActiveTab('overview')}
        >
          <LayoutDashboard size={15} aria-hidden="true" />
          <span>Overview</span>
        </button>

        <button
          type="button"
          className={`community-admin-tab ${activeTab === 'reports' ? 'is-active' : ''}`}
          onClick={() => setActiveTab('reports')}
        >
          <Flag size={15} aria-hidden="true" />
          <span>Reports Queue</span>
          {data.pending_reports_count > 0 && (
            <span className="community-admin-tab__count">{data.pending_reports_count}</span>
          )}
        </button>

        <button
          type="button"
          className={`community-admin-tab ${activeTab === 'audit_logs' ? 'is-active' : ''}`}
          onClick={() => setActiveTab('audit_logs')}
        >
          <ShieldAlert size={15} aria-hidden="true" />
          <span>Audit Logs</span>
        </button>

        <button
          type="button"
          className={`community-admin-tab ${activeTab === 'bans' ? 'is-active' : ''}`}
          onClick={() => setActiveTab('bans')}
        >
          <UserX size={15} aria-hidden="true" />
          <span>Banned Members</span>
          {data.banned_count > 0 && (
            <span className="community-admin-tab__count">{data.banned_count}</span>
          )}
        </button>

        <button
          type="button"
          className={`community-admin-tab ${activeTab === 'mutes' ? 'is-active' : ''}`}
          onClick={() => setActiveTab('mutes')}
        >
          <VolumeX size={15} aria-hidden="true" />
          <span>Muted Members</span>
          {data.muted_count > 0 && (
            <span className="community-admin-tab__count">{data.muted_count}</span>
          )}
        </button>

        {isAdmin && (
          <button
            type="button"
            className={`community-admin-tab ${activeTab === 'settings' ? 'is-active' : ''}`}
            onClick={() => setActiveTab('settings')}
          >
            <Settings size={15} aria-hidden="true" />
            <span>Governance & Settings</span>
          </button>
        )}
      </nav>

      {/* Tab 1: OVERVIEW */}
      {activeTab === 'overview' && (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '24px' }}>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(360px, 1fr))', gap: '24px' }}>
            {/* Governance Summary */}
            <section className="community-admin-card">
              <header className="community-admin-card__header">
                <div>
                  <h2 className="community-admin-card__title">
                    <ShieldCheck size={16} aria-hidden="true" />
                    <span>Governance & Permissions</span>
                  </h2>
                  <p className="community-admin-card__subtitle">Current posting, join mode, and privacy configuration.</p>
                </div>
              </header>
              <div style={{ display: 'flex', flexDirection: 'column', gap: '16px', fontSize: '14px' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', borderBottom: '1px solid #f1f5f9', paddingBottom: '12px' }}>
                  <span style={{ color: '#64748b', fontWeight: 500 }}>Posting Permissions</span>
                  <span style={{ background: '#eff6ff', color: '#2563eb', fontWeight: 700, padding: '4px 12px', borderRadius: '20px', fontSize: '12.5px' }}>
                    {(community.posting_permissions || 'everyone').replace('_', ' ').toUpperCase()}
                  </span>
                </div>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', borderBottom: '1px solid #f1f5f9', paddingBottom: '12px' }}>
                  <span style={{ color: '#64748b', fontWeight: 500 }}>Join Approval Mode</span>
                  <span style={{ background: '#f0fdf4', color: '#16a34a', fontWeight: 700, padding: '4px 12px', borderRadius: '20px', fontSize: '12.5px' }}>
                    {(community.join_approval_mode || 'instant').replace('_', ' ').toUpperCase()}
                  </span>
                </div>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  <span style={{ color: '#64748b', fontWeight: 500 }}>Community Privacy</span>
                  <span style={{ background: '#f8fafc', color: '#475569', fontWeight: 700, padding: '4px 12px', borderRadius: '20px', fontSize: '12.5px', border: '1px solid #e2e8f0' }}>
                    {(community.visibility || 'public').toUpperCase()}
                  </span>
                </div>
              </div>
            </section>

            {/* Quick Actions Shortcuts */}
            <section className="community-admin-card">
              <header className="community-admin-card__header">
                <div>
                  <h2 className="community-admin-card__title">
                    <Zap size={16} aria-hidden="true" />
                    <span>Quick Shortcuts & Moderation Actions</span>
                  </h2>
                  <p className="community-admin-card__subtitle">Fast access to member discipline and review queues.</p>
                </div>
              </header>
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' }}>
                <button
                  type="button"
                  className="community-admin-btn community-admin-btn--secondary"
                  onClick={() => setActionModal({ type: 'warn', memberId: '', reason: '', duration: '', isSubmitting: false })}
                  style={{ height: 'auto', padding: '14px', flexDirection: 'column', textAlign: 'center', gap: '6px' }}
                >
                  <AlertTriangle size={20} color="#f59e0b" />
                  <span style={{ fontSize: '13px', fontWeight: 600 }}>Warn Member</span>
                  <small style={{ color: '#64748b', fontSize: '11px' }}>Issue official warning</small>
                </button>

                <button
                  type="button"
                  className="community-admin-btn community-admin-btn--secondary"
                  onClick={() => setActionModal({ type: 'mute', memberId: '', reason: '', duration: '24h', isSubmitting: false })}
                  style={{ height: 'auto', padding: '14px', flexDirection: 'column', textAlign: 'center', gap: '6px' }}
                >
                  <VolumeX size={20} color="#8b5cf6" />
                  <span style={{ fontSize: '13px', fontWeight: 600 }}>Mute Member</span>
                  <small style={{ color: '#64748b', fontSize: '11px' }}>Temporary mute</small>
                </button>

                <button
                  type="button"
                  className="community-admin-btn community-admin-btn--secondary"
                  onClick={() => setActionModal({ type: 'ban', memberId: '', reason: '', duration: '7d', isSubmitting: false })}
                  style={{ height: 'auto', padding: '14px', flexDirection: 'column', textAlign: 'center', gap: '6px', borderColor: '#fecaca', background: '#fff5f5' }}
                >
                  <UserX size={20} color="#ef4444" />
                  <span style={{ fontSize: '13px', color: '#dc2626', fontWeight: 600 }}>Ban Member</span>
                  <small style={{ color: '#991b1b', fontSize: '11px' }}>Community restriction</small>
                </button>

                <button
                  type="button"
                  className="community-admin-btn community-admin-btn--secondary"
                  onClick={() => setActiveTab('reports')}
                  style={{ height: 'auto', padding: '14px', flexDirection: 'column', textAlign: 'center', gap: '6px' }}
                >
                  <Flag size={20} color="#ef4444" />
                  <span style={{ fontSize: '13px', fontWeight: 600 }}>Reports ({data.pending_reports_count})</span>
                  <small style={{ color: '#64748b', fontSize: '11px' }}>Review safety queue</small>
                </button>
              </div>
            </section>
          </div>

          {/* Recent Warnings History */}
          {warningsList.length > 0 && (
            <section className="community-admin-card">
              <header className="community-admin-card__header">
                <div>
                  <h2 className="community-admin-card__title">
                    <AlertTriangle size={16} color="#f59e0b" aria-hidden="true" />
                    <span>Recent Member Warnings</span>
                  </h2>
                  <p className="community-admin-card__subtitle">History of warnings issued to community members.</p>
                </div>
              </header>
              <div className="community-admin-table-wrap">
                <table className="community-admin-table">
                  <thead>
                    <tr>
                      <th>Member</th>
                      <th>Warned By</th>
                      <th>Reason</th>
                      <th>Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    {warningsList.map((w) => (
                      <tr key={w.id}>
                        <td><strong>{w.member?.name || 'Member'}</strong></td>
                        <td>{w.warned_by?.name || 'Moderator'}</td>
                        <td>{w.reason}</td>
                        <td style={{ color: '#64748b' }}>{new Date(w.created_at).toLocaleDateString()}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </section>
          )}
        </div>
      )}

      {/* Tab 2: REPORTS QUEUE */}
      {activeTab === 'reports' && (
        <section className="community-admin-card">
          <header className="community-admin-card__header">
            <div>
              <h2 className="community-admin-card__title">
                <Flag size={16} color="#ef4444" aria-hidden="true" />
                <span>Moderation Reports Queue</span>
              </h2>
              <p className="community-admin-card__subtitle">Review and resolve safety reports submitted by community members.</p>
            </div>
          </header>

          <div className="community-admin-table-wrap">
            <table className="community-admin-table">
              <thead>
                <tr>
                  <th>Reporter</th>
                  <th>Target Content</th>
                  <th>Reason & Details</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th style={{ textAlign: 'right' }}>Action</th>
                </tr>
              </thead>
              <tbody>
                {reportsList.length === 0 ? (
                  <tr>
                    <td colSpan={6} style={{ padding: '36px', textAlign: 'center', color: '#64748b' }}>
                      <CheckCircle size={32} color="#10b981" style={{ marginBottom: '8px', display: 'block', margin: '0 auto 8px auto' }} />
                      <strong>No moderation reports in queue.</strong>
                    </td>
                  </tr>
                ) : (
                  reportsList.map((report) => (
                    <tr key={report.id}>
                      <td><strong>{report.reporter?.name || 'Member'}</strong></td>
                      <td>
                        <span style={{ background: '#f1f5f9', color: '#475569', padding: '4px 10px', borderRadius: '8px', fontSize: '11.5px', fontWeight: 700 }}>
                          {(report.reportable_type || 'post').toUpperCase()} #{report.reportable_id}
                        </span>
                      </td>
                      <td>
                        <strong style={{ color: '#1e293b', display: 'block' }}>{report.reason}</strong>
                        {report.details && <div style={{ fontSize: '12px', color: '#64748b', marginTop: '2px' }}>{report.details}</div>}
                      </td>
                      <td>
                        <span
                          style={{
                            fontSize: '11.5px',
                            fontWeight: 700,
                            padding: '4px 10px',
                            borderRadius: '20px',
                            background: report.status === 'pending' ? '#ffe4e6' : report.status === 'resolved' || report.status === 'approved' ? '#dcfce7' : '#f1f5f9',
                            color: report.status === 'pending' ? '#e11d48' : report.status === 'resolved' || report.status === 'approved' ? '#166534' : '#64748b',
                          }}
                        >
                          {report.status?.toUpperCase()}
                        </span>
                      </td>
                      <td style={{ color: '#64748b' }}>{new Date(report.created_at).toLocaleDateString()}</td>
                      <td style={{ textAlign: 'right' }}>
                        {report.status === 'pending' ? (
                          <div style={{ display: 'inline-flex', gap: '6px' }}>
                            <button
                              type="button"
                              className="community-admin-btn community-admin-btn--primary community-admin-btn--sm"
                              onClick={() => handleReportAction(report.id, 'resolved', false)}
                            >
                              Resolve
                            </button>
                            <button
                              type="button"
                              className="community-admin-btn community-admin-btn--danger community-admin-btn--sm"
                              onClick={() => handleReportAction(report.id, 'approved', true)}
                            >
                              Delete Content
                            </button>
                          </div>
                        ) : (
                          <span style={{ fontSize: '12px', color: '#94a3b8' }}>Resolved by {report.resolved_by?.name || 'Admin'}</span>
                        )}
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </section>
      )}

      {/* Tab 3: AUDIT LOGS */}
      {activeTab === 'audit_logs' && (
        <section className="community-admin-card">
          <header className="community-admin-card__header">
            <div>
              <h2 className="community-admin-card__title">
                <ShieldAlert size={16} aria-hidden="true" />
                <span>Moderation Audit Logs</span>
              </h2>
              <p className="community-admin-card__subtitle">Complete historical record of admin actions and governance changes.</p>
            </div>
          </header>

          <div className="community-admin-table-wrap">
            <table className="community-admin-table">
              <thead>
                <tr>
                  <th>Actor</th>
                  <th>Action Recorded</th>
                  <th>Target Reference</th>
                  <th>Timestamp</th>
                </tr>
              </thead>
              <tbody>
                {auditLogsList.length === 0 ? (
                  <tr>
                    <td colSpan={4} style={{ padding: '36px', textAlign: 'center', color: '#64748b' }}>
                      <Info size={32} color="#94a3b8" style={{ marginBottom: '8px', display: 'block', margin: '0 auto 8px auto' }} />
                      No audit log entries recorded yet.
                    </td>
                  </tr>
                ) : (
                  auditLogsList.map((log) => (
                    <tr key={log.id}>
                      <td><strong>{log.actor?.name || 'System'}</strong></td>
                      <td>
                        <span style={{ background: '#eff6ff', color: '#2563eb', padding: '4px 10px', borderRadius: '8px', fontSize: '12px', fontWeight: 700 }}>
                          {log.action}
                        </span>
                      </td>
                      <td style={{ color: '#64748b' }}>
                        {log.target_type ? `${log.target_type} #${log.target_id}` : 'N/A'}
                      </td>
                      <td style={{ color: '#64748b' }}>{new Date(log.created_at).toLocaleString()}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </section>
      )}

      {/* Tab 4: BANNED MEMBERS */}
      {activeTab === 'bans' && (
        <section className="community-admin-card">
          <header className="community-admin-card__header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <div>
              <h2 className="community-admin-card__title">
                <UserX size={16} color="#64748b" aria-hidden="true" />
                <span>Banned Members</span>
              </h2>
              <p className="community-admin-card__subtitle">Members restricted from joining or interacting with this community.</p>
            </div>
            <button
              type="button"
              className="community-admin-btn community-admin-btn--danger community-admin-btn--sm"
              onClick={() => setActionModal({ type: 'ban', memberId: '', reason: '', duration: 'permanent', isSubmitting: false })}
            >
              <UserX size={14} />
              <span>Ban a Member</span>
            </button>
          </header>

          <div className="community-admin-table-wrap">
            <table className="community-admin-table">
              <thead>
                <tr>
                  <th>Banned Member</th>
                  <th>Banned By</th>
                  <th>Reason</th>
                  <th>Ban Duration</th>
                  <th style={{ textAlign: 'right' }}>Action</th>
                </tr>
              </thead>
              <tbody>
                {bannedList.length === 0 ? (
                  <tr>
                    <td colSpan={5} style={{ padding: '36px', textAlign: 'center', color: '#64748b' }}>
                      <Smile size={32} color="#10b981" style={{ marginBottom: '8px', display: 'block', margin: '0 auto 8px auto' }} />
                      No banned members in this community.
                    </td>
                  </tr>
                ) : (
                  bannedList.map((ban) => (
                    <tr key={ban.id}>
                      <td><strong>{ban.member?.name || 'Member'}</strong></td>
                      <td style={{ color: '#64748b' }}>{ban.banned_by?.name || 'Admin'}</td>
                      <td>{ban.reason}</td>
                      <td>
                        <span style={{ background: '#f1f5f9', color: '#475569', padding: '4px 10px', borderRadius: '8px', fontSize: '12px', fontWeight: 700 }}>
                          {ban.is_permanent ? 'Permanent' : ban.expires_at ? new Date(ban.expires_at).toLocaleDateString() : 'Active'}
                        </span>
                      </td>
                      <td style={{ textAlign: 'right' }}>
                        {isAdmin && (
                          <button
                            type="button"
                            className="community-admin-btn community-admin-btn--secondary community-admin-btn--sm"
                            onClick={() => handleUnbanMember(ban.member_id)}
                          >
                            Unban Member
                          </button>
                        )}
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </section>
      )}

      {/* Tab 5: MUTED MEMBERS */}
      {activeTab === 'mutes' && (
        <section className="community-admin-card">
          <header className="community-admin-card__header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <div>
              <h2 className="community-admin-card__title">
                <VolumeX size={16} color="#8b5cf6" aria-hidden="true" />
                <span>Muted Members</span>
              </h2>
              <p className="community-admin-card__subtitle">Members restricted from posting new content or comments.</p>
            </div>
            <button
              type="button"
              className="community-admin-btn community-admin-btn--secondary community-admin-btn--sm"
              onClick={() => setActionModal({ type: 'mute', memberId: '', reason: '', duration: '24h', isSubmitting: false })}
            >
              <VolumeX size={14} color="#8b5cf6" />
              <span>Mute a Member</span>
            </button>
          </header>

          <div className="community-admin-table-wrap">
            <table className="community-admin-table">
              <thead>
                <tr>
                  <th>Muted Member</th>
                  <th>Muted By</th>
                  <th>Reason</th>
                  <th>Mute Expires</th>
                  <th style={{ textAlign: 'right' }}>Action</th>
                </tr>
              </thead>
              <tbody>
                {mutedList.length === 0 ? (
                  <tr>
                    <td colSpan={5} style={{ padding: '36px', textAlign: 'center', color: '#64748b' }}>
                      <Volume2 size={32} color="#10b981" style={{ marginBottom: '8px', display: 'block', margin: '0 auto 8px auto' }} />
                      No muted members in this community.
                    </td>
                  </tr>
                ) : (
                  mutedList.map((mute) => (
                    <tr key={mute.id}>
                      <td><strong>{mute.member?.name || 'Member'}</strong></td>
                      <td style={{ color: '#64748b' }}>{mute.muted_by?.name || 'Moderator'}</td>
                      <td>{mute.reason}</td>
                      <td style={{ color: '#8b5cf6', fontWeight: 700 }}>
                        {mute.expires_at ? new Date(mute.expires_at).toLocaleString() : 'Active'}
                      </td>
                      <td style={{ textAlign: 'right' }}>
                        <button
                          type="button"
                          className="community-admin-btn community-admin-btn--secondary community-admin-btn--sm"
                          onClick={() => handleUnmuteMember(mute.member_id)}
                        >
                          Unmute
                        </button>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </section>
      )}

      {/* Tab 6: GOVERNANCE & SETTINGS */}
      {activeTab === 'settings' && isAdmin && (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '24px' }}>
          <section className="community-admin-card">
            <header className="community-admin-card__header">
              <div>
                <h2 className="community-admin-card__title">
                  <Settings size={16} aria-hidden="true" />
                  <span>Community Governance & Settings</span>
                </h2>
                <p className="community-admin-card__subtitle">Configure community name, category, posting rules, join approval, and guidelines.</p>
              </div>
            </header>

            <form onSubmit={handleSaveSettings}>
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '24px', marginBottom: '18px' }}>
                <div>
                  <label className="community-admin-label">Community Name</label>
                  <input
                    type="text"
                    className="community-admin-input"
                    value={settingsForm.name}
                    onChange={(e) => setSettingsForm({ ...settingsForm, name: e.target.value })}
                    required
                  />
                </div>

                <div>
                  <label className="community-admin-label">Category</label>
                  <select
                    className="community-admin-select"
                    value={settingsForm.category}
                    onChange={(e) => setSettingsForm({ ...settingsForm, category: e.target.value })}
                    required
                  >
                    {[
                      'Technology',
                      'Business',
                      'Education',
                      'Gaming',
                      'Sports',
                      'Finance',
                      'Crypto',
                      'Entertainment',
                      'Lifestyle',
                      'Health',
                      'Other',
                    ].map((cat) => (
                      <option key={cat} value={cat}>
                        {cat}
                      </option>
                    ))}
                  </select>
                </div>
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '24px', marginBottom: '18px' }}>
                <div>
                  <label className="community-admin-label">Posting Permissions</label>
                  <select
                    className="community-admin-select"
                    value={settingsForm.posting_permissions}
                    onChange={(e) => setSettingsForm({ ...settingsForm, posting_permissions: e.target.value })}
                    required
                  >
                    <option value="everyone">Everyone</option>
                    <option value="members_only">Members Only</option>
                    <option value="moderators_admins">Moderators & Admins Only</option>
                    <option value="admins_only">Admins Only</option>
                    <option value="owner_only">Owner Only</option>
                  </select>
                </div>

                <div>
                  <label className="community-admin-label">Join Approval Mode</label>
                  <select
                    className="community-admin-select"
                    value={settingsForm.join_approval_mode}
                    onChange={(e) => setSettingsForm({ ...settingsForm, join_approval_mode: e.target.value })}
                    required
                  >
                    <option value="instant">Instant Join</option>
                    <option value="approval_required">Approval Required by Admin</option>
                    <option value="invite_only">Invite Only</option>
                  </select>
                </div>
              </div>

              <div style={{ marginBottom: '18px' }}>
                <label className="community-admin-label">Community Description</label>
                <textarea
                  className="community-admin-textarea"
                  rows={4}
                  value={settingsForm.description}
                  onChange={(e) => setSettingsForm({ ...settingsForm, description: e.target.value })}
                />
              </div>

              <div style={{ marginBottom: '24px' }}>
                <label className="community-admin-label">Community Guidelines & Rules</label>
                <textarea
                  className="community-admin-textarea"
                  rows={5}
                  value={settingsForm.rules}
                  onChange={(e) => setSettingsForm({ ...settingsForm, rules: e.target.value })}
                  placeholder="1. Be respectful&#10;2. No spam or self-promotion"
                />
              </div>

              <button
                type="submit"
                className="community-admin-btn community-admin-btn--primary"
                disabled={isSavingSettings}
              >
                <Save size={14} aria-hidden="true" />
                <span>{isSavingSettings ? 'Saving...' : 'Save Governance Settings'}</span>
              </button>
            </form>
          </section>

          {/* Transfer Ownership Section */}
          {isOwner && (
            <section className="community-admin-card" style={{ borderColor: '#fecaca', background: '#fffdfd' }}>
              <header className="community-admin-card__header" style={{ borderBottomColor: '#fee2e2' }}>
                <div>
                  <h2 className="community-admin-card__title" style={{ color: '#dc2626' }}>
                    <ShieldAlert size={16} color="#dc2626" aria-hidden="true" />
                    <span>Transfer Community Ownership</span>
                  </h2>
                  <p className="community-admin-card__subtitle">
                    Transfer full administrative ownership of {community.name} to another active member.
                  </p>
                </div>
              </header>

              <form onSubmit={handleTransferOwnership}>
                <div style={{ display: 'flex', gap: '16px', alignItems: 'center', maxWidth: '540px', flexWrap: 'wrap' }}>
                  <select
                    className="community-admin-select"
                    style={{ flex: 1, minWidth: '220px' }}
                    value={newOwnerId}
                    onChange={(e) => setNewOwnerId(e.target.value)}
                    required
                  >
                    <option value="">Select New Owner...</option>
                    {acceptedMembers
                      .filter((cm) => cm.member_id !== community.owner_id)
                      .map((cm) => (
                        <option key={cm.id} value={cm.member_id}>
                          {cm.member?.name || 'Member'} ({cm.role || 'member'})
                        </option>
                      ))}
                  </select>
                  <button
                    type="submit"
                    className="community-admin-btn community-admin-btn--danger"
                    disabled={isTransferring || !newOwnerId}
                  >
                    {isTransferring ? 'Transferring...' : 'Transfer Ownership'}
                  </button>
                </div>
              </form>
            </section>
          )}
        </div>
      )}

      {/* Moderation Action Modal (Warn / Mute / Ban) */}
      {actionModal.type && (
        <ModalPortal
          isOpen={Boolean(actionModal.type)}
          onClose={() => setActionModal({ type: null, memberId: '', reason: '', duration: '24h', isSubmitting: false })}
        >
          <div
            className="card"
            role="dialog"
            aria-modal="true"
            aria-labelledby="moderation-action-modal-title"
            style={{ maxWidth: '480px', width: '100%', padding: '24px', borderRadius: '16px', background: '#fff' }}
          >
            <h3 id="moderation-action-modal-title" style={{ fontSize: '18px', fontWeight: 800, margin: '0 0 8px 0', textTransform: 'capitalize' }}>
              {actionModal.type === 'warn' ? 'Issue Official Warning' : actionModal.type === 'mute' ? 'Mute Member' : 'Ban Member'}
            </h3>
            <p style={{ fontSize: '13px', color: '#64748b', margin: '0 0 16px 0' }}>
              {actionModal.type === 'warn'
                ? 'Send a formal conduct warning recorded in community audit logs.'
                : actionModal.type === 'mute'
                ? 'Temporarily prevent member from publishing posts and comments.'
                : 'Restrict member from accessing or interacting with this community.'}
            </p>

            <form onSubmit={handleModalSubmit}>
              <div style={{ marginBottom: '14px' }}>
                <label className="community-admin-label">Select Member *</label>
                <select
                  className="community-admin-select"
                  value={actionModal.memberId}
                  onChange={(e) => setActionModal({ ...actionModal, memberId: e.target.value })}
                  required
                >
                  <option value="">Choose active member...</option>
                  {acceptedMembers
                    .filter((cm) => cm.member_id !== community.owner_id)
                    .map((cm) => (
                      <option key={cm.id} value={cm.member_id}>
                        {cm.member?.name || 'Member'} ({cm.role || 'member'})
                      </option>
                    ))}
                </select>
              </div>

              {actionModal.type !== 'warn' && (
                <div style={{ marginBottom: '14px' }}>
                  <label className="community-admin-label">Duration *</label>
                  <select
                    className="community-admin-select"
                    value={actionModal.duration}
                    onChange={(e) => setActionModal({ ...actionModal, duration: e.target.value })}
                    required
                  >
                    {actionModal.type === 'mute' ? (
                      <>
                        <option value="1h">1 Hour</option>
                        <option value="12h">12 Hours</option>
                        <option value="24h">24 Hours</option>
                        <option value="3d">3 Days</option>
                        <option value="7d">7 Days</option>
                        <option value="30d">30 Days</option>
                      </>
                    ) : (
                      <>
                        <option value="1d">1 Day</option>
                        <option value="7d">7 Days</option>
                        <option value="30d">30 Days</option>
                        <option value="permanent">Permanent</option>
                      </>
                    )}
                  </select>
                </div>
              )}

              <div style={{ marginBottom: '20px' }}>
                <label className="community-admin-label">Reason *</label>
                <textarea
                  className="community-admin-textarea"
                  rows={3}
                  value={actionModal.reason}
                  onChange={(e) => setActionModal({ ...actionModal, reason: e.target.value })}
                  placeholder="Specify rule violation or reasoning..."
                  required
                />
              </div>

              <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '10px' }}>
                <button
                  type="button"
                  className="community-admin-btn community-admin-btn--secondary"
                  onClick={() => setActionModal({ type: null, memberId: '', reason: '', duration: '24h', isSubmitting: false })}
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className={`community-admin-btn ${actionModal.type === 'ban' ? 'community-admin-btn--danger' : 'community-admin-btn--primary'}`}
                  disabled={actionModal.isSubmitting || !actionModal.memberId}
                >
                  {actionModal.isSubmitting ? 'Executing...' : `Confirm ${actionModal.type?.toUpperCase()}`}
                </button>
              </div>
            </form>
          </div>
        </ModalPortal>
      )}
    </div>
  );
}

export default CommunityAdminPanelPage;
