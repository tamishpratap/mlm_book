import { useState, useEffect, useCallback } from 'react';
import { useParams, Link } from 'react-router-dom';
import {
  BarChart3,
  Download,
  ArrowLeft,
  Activity,
  UserPlus,
  MessageSquare,
  Award,
  Flame,
  ThumbsUp,
  RotateCw,
  AlertTriangle,
} from 'lucide-react';
import communityApi from '../../api/communityApi';
import MemberAvatar from '../../components/common/MemberAvatar';

function formatRelativeTime(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  const now = new Date();
  const diffInSeconds = Math.floor((now - date) / 1000);

  if (diffInSeconds < 60) return 'just now';
  if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
  if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
  if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`;
  return date.toLocaleDateString();
}

function getInitials(name) {
  if (!name) return 'M';
  const parts = name.trim().split(/\s+/);
  return parts.slice(0, 2).map((p) => p[0].toUpperCase()).join('') || 'M';
}

export function CommunityAnalyticsPage() {
  const { slug } = useParams();

  const [period, setPeriod] = useState('7days');
  const [data, setData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [isExporting, setIsExporting] = useState(false);
  const [error, setError] = useState(null);

  const fetchAnalytics = useCallback(
    (p) => {
      if (!slug) return Promise.resolve(null);
      return communityApi.getCommunityAnalytics(slug, p);
    },
    [slug]
  );

  useEffect(() => {
    let isMounted = true;

    fetchAnalytics(period)
      .then((res) => {
        if (isMounted && res) {
          setData(res);
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load community analytics.');
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [fetchAnalytics, period]);

  const handleRefresh = () => {
    setIsRefreshing(true);
    fetchAnalytics(period)
      .then((res) => {
        if (res) setData(res);
      })
      .catch(() => {})
      .finally(() => setIsRefreshing(false));
  };

  const handleExport = async () => {
    if (!slug || isExporting) return;
    setIsExporting(true);
    try {
      const blob = await communityApi.exportCommunityAnalyticsCsv(slug);
      const url = window.URL.createObjectURL(new Blob([blob], { type: 'text/csv' }));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `community_analytics_${slug}_${new Date().toISOString().slice(0, 10)}.csv`);
      document.body.appendChild(link);
      link.click();
      link.parentNode.removeChild(link);
    } catch {
      alert('Failed to export CSV file.');
    } finally {
      setIsExporting(false);
    }
  };

  const community = data?.community;
  const healthScore = data?.health_score ?? 0;
  const healthLabel = data?.health_label ?? 'Needs Attention';

  const totalMembers = data?.total_members ?? 0;
  const activeMembers = data?.active_members ?? 0;
  const newMembersInPeriod = data?.new_members_in_period ?? 0;
  const totalPosts = data?.total_posts ?? 0;
  const postsInPeriod = data?.posts_in_period ?? 0;
  const totalAnnouncements = data?.total_announcements ?? 0;
  const pendingRequests = data?.pending_requests ?? 0;
  const pendingReports = data?.pending_reports ?? 0;
  const bannedMembers = data?.banned_members ?? 0;

  const retentionRate = Math.round((activeMembers / Math.max(1, totalMembers)) * 100);

  const dailyLabels = data?.daily_labels || [];
  const dailyMemberJoins = data?.daily_member_joins || [];
  const dailyPostCreations = data?.daily_post_creations || [];

  const maxJoins = Math.max(1, ...(dailyMemberJoins.length > 0 ? dailyMemberJoins : [1]));
  const maxPosts = Math.max(1, ...(dailyPostCreations.length > 0 ? dailyPostCreations : [1]));

  const topContributors = data?.top_contributors || [];
  const topPosts = data?.top_posts || [];

  if (isLoading) {
    return (
      <div className="community-page" style={{ maxWidth: '1080px', margin: '20px auto', textAlign: 'center', padding: '60px' }}>
        <p style={{ color: 'var(--color-text-secondary, #64748b)' }}>Loading community analytics...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="community-page" style={{ width: '100%', margin: '0 auto' }}>
        <div className="card" style={{ padding: '32px', textAlign: 'center', borderRadius: '16px' }}>
          <AlertTriangle size={48} color="#ef4444" style={{ margin: '0 auto 12px' }} />
          <h2 style={{ fontSize: '18px', fontWeight: 800, margin: '0 0 8px 0', color: '#0f172a' }}>Access Restricted</h2>
          <p style={{ fontSize: '14px', color: '#64748b', margin: '0 0 20px 0' }}>{error}</p>
          <Link to={`/member/community/${slug}`} className="member-button member-button--primary">
            Back to Community
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="community-page" style={{ width: '100%', margin: '0 auto' }}>
      {/* Hero Analytics Header */}
      <div
        className="card"
        style={{
          background: 'linear-gradient(135deg, rgba(79, 125, 243, 0.15), rgba(125, 66, 240, 0.15))',
          border: '1px solid var(--color-border-soft, #e2e8f0)',
          borderRadius: '16px',
          padding: '24px',
          marginBottom: '24px',
        }}
      >
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '16px' }}>
          <div>
            <span
              className="community-badge"
              style={{
                background: 'var(--color-primary, #4f7df3)',
                color: '#ffffff',
                marginBottom: '6px',
                display: 'inline-flex',
                alignItems: 'center',
                gap: '4px',
              }}
            >
              <BarChart3 size={12} aria-hidden="true" />
              <span>Enterprise Analytics Platform</span>
            </span>
            <h1 style={{ fontSize: '24px', fontWeight: 900, color: 'var(--color-text-main, #0f172a)', margin: '0 0 4px 0' }}>
              {community?.name || 'Community'} Insights & Performance
            </h1>
            <p style={{ fontSize: '13.5px', color: 'var(--color-text-secondary, #64748b)', margin: 0 }}>
              Comprehensive growth metrics, member activity, post engagement, and health diagnostics.
            </p>
          </div>

          <div style={{ display: 'flex', gap: '10px', alignItems: 'center', flexWrap: 'wrap' }}>
            {/* Filter Dropdown */}
            <select
              className="community-search-input"
              value={period}
              onChange={(e) => setPeriod(e.target.value)}
              style={{ padding: '8px 14px', fontSize: '13px', width: 'auto', background: '#fff' }}
            >
              <option value="7days">Last 7 Days</option>
              <option value="30days">Last 30 Days</option>
              <option value="90days">Last 90 Days</option>
              <option value="this_year">This Year</option>
              <option value="all">All Time</option>
            </select>

            <button
              type="button"
              className="member-button member-button--secondary"
              onClick={handleRefresh}
              disabled={isRefreshing}
              title="Refresh Analytics"
              style={{ padding: '8px 12px' }}
            >
              <RotateCw size={14} className={isRefreshing ? 'fa-spin' : ''} aria-hidden="true" />
            </button>

            {/* Export CSV Button */}
            <button
              type="button"
              className="member-button member-button--secondary"
              onClick={handleExport}
              disabled={isExporting}
              style={{ padding: '8px 16px', fontSize: '13px' }}
            >
              <Download size={14} aria-hidden="true" />
              <span>{isExporting ? 'Exporting...' : 'Export CSV'}</span>
            </button>

            <Link
              to={`/member/community/${slug}`}
              className="member-button member-button--secondary"
              style={{ padding: '8px 14px', fontSize: '13px' }}
            >
              <ArrowLeft size={14} aria-hidden="true" />
              <span>Back</span>
            </Link>
          </div>
        </div>
      </div>

      {/* Community Health Diagnostics Card */}
      <section className="card fb-section-card" style={{ marginBottom: '24px', padding: '20px', borderRadius: '16px' }}>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '16px' }}>
          <div>
            <h3 style={{ fontSize: '16px', fontWeight: 800, color: 'var(--color-text-main, #0f172a)', margin: '0 0 4px 0', display: 'flex', alignItems: 'center', gap: '6px' }}>
              <Activity size={18} color="#4f7df3" aria-hidden="true" />
              <span>Community Health Index</span>
            </h3>
            <p style={{ fontSize: '13px', color: 'var(--color-text-secondary, #64748b)', margin: 0 }}>
              Composite health score evaluated against growth rates, member retention, post frequency, and moderation activity.
            </p>
          </div>

          <div style={{ display: 'flex', alignItems: 'center', gap: '16px' }}>
            <div style={{ textAlign: 'right' }}>
              <span style={{ fontSize: '28px', fontWeight: 900, color: 'var(--color-primary, #4f7df3)' }}>
                {healthScore}/100
              </span>
              <div style={{ fontSize: '12px', fontWeight: 700, color: '#10b981' }}>
                Status: {healthLabel}
              </div>
            </div>
          </div>
        </div>

        <div
          style={{
            marginTop: '16px',
            width: '100%',
            height: '10px',
            background: 'var(--color-surface-alt, #f1f5f9)',
            borderRadius: '6px',
            overflow: 'hidden',
            position: 'relative',
          }}
        >
          <div
            style={{
              height: '100%',
              width: `${Math.min(100, Math.max(0, healthScore))}%`,
              background: 'linear-gradient(90deg, #4f7df3, #7d42f0)',
              borderRadius: '6px',
              transition: 'width 0.6s ease',
            }}
          />
        </div>
      </section>

      {/* Overview Statistics Cards Grid */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: '14px', marginBottom: '24px' }}>
        <div className="card" style={{ padding: '16px', borderRadius: '12px' }}>
          <div style={{ fontSize: '12px', color: 'var(--color-text-secondary, #64748b)', fontWeight: 600 }}>Total Members</div>
          <div style={{ fontSize: '24px', fontWeight: 900, color: 'var(--color-text-main, #0f172a)', marginTop: '4px' }}>
            {Number(totalMembers).toLocaleString()}
          </div>
          <span style={{ fontSize: '11px', color: 'var(--color-primary, #4f7df3)' }}>
            +{Number(newMembersInPeriod).toLocaleString()} in selected period
          </span>
        </div>

        <div className="card" style={{ padding: '16px', borderRadius: '12px' }}>
          <div style={{ fontSize: '12px', color: 'var(--color-text-secondary, #64748b)', fontWeight: 600 }}>Active Members (30d)</div>
          <div style={{ fontSize: '24px', fontWeight: 900, color: '#10b981', marginTop: '4px' }}>
            {Number(activeMembers).toLocaleString()}
          </div>
          <span style={{ fontSize: '11px', color: 'var(--color-text-secondary, #64748b)' }}>
            {retentionRate}% retention rate
          </span>
        </div>

        <div className="card" style={{ padding: '16px', borderRadius: '12px' }}>
          <div style={{ fontSize: '12px', color: 'var(--color-text-secondary, #64748b)', fontWeight: 600 }}>Total Posts</div>
          <div style={{ fontSize: '24px', fontWeight: 900, color: 'var(--color-text-main, #0f172a)', marginTop: '4px' }}>
            {Number(totalPosts).toLocaleString()}
          </div>
          <span style={{ fontSize: '11px', color: 'var(--color-primary, #4f7df3)' }}>
            +{Number(postsInPeriod).toLocaleString()} in selected period
          </span>
        </div>

        <div className="card" style={{ padding: '16px', borderRadius: '12px' }}>
          <div style={{ fontSize: '12px', color: 'var(--color-text-secondary, #64748b)', fontWeight: 600 }}>Announcements</div>
          <div style={{ fontSize: '24px', fontWeight: 900, color: '#7d42f0', marginTop: '4px' }}>
            {Number(totalAnnouncements).toLocaleString()}
          </div>
          <span style={{ fontSize: '11px', color: 'var(--color-text-secondary, #64748b)' }}>Official broadcasts</span>
        </div>

        <div className="card" style={{ padding: '16px', borderRadius: '12px' }}>
          <div style={{ fontSize: '12px', color: 'var(--color-text-secondary, #64748b)', fontWeight: 600 }}>Pending Join Requests</div>
          <div style={{ fontSize: '24px', fontWeight: 900, color: '#f59e0b', marginTop: '4px' }}>
            {Number(pendingRequests).toLocaleString()}
          </div>
          <span style={{ fontSize: '11px', color: 'var(--color-text-secondary, #64748b)' }}>Awaiting approval</span>
        </div>

        <div className="card" style={{ padding: '16px', borderRadius: '12px' }}>
          <div style={{ fontSize: '12px', color: 'var(--color-text-secondary, #64748b)', fontWeight: 600 }}>Pending Moderation Reports</div>
          <div style={{ fontSize: '24px', fontWeight: 900, color: '#ef4444', marginTop: '4px' }}>
            {Number(pendingReports).toLocaleString()}
          </div>
          <span style={{ fontSize: '11px', color: 'var(--color-text-secondary, #64748b)' }}>Member flags</span>
        </div>

        <div className="card" style={{ padding: '16px', borderRadius: '12px' }}>
          <div style={{ fontSize: '12px', color: 'var(--color-text-secondary, #64748b)', fontWeight: 600 }}>Active Member Bans</div>
          <div style={{ fontSize: '24px', fontWeight: 900, color: '#6b7280', marginTop: '4px' }}>
            {Number(bannedMembers).toLocaleString()}
          </div>
          <span style={{ fontSize: '11px', color: 'var(--color-text-secondary, #64748b)' }}>Enforced restrictions</span>
        </div>
      </div>

      {/* 7-Day Growth & Post Creation Charts */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(320px, 1fr))', gap: '20px', marginBottom: '24px' }}>
        {/* Member Growth Chart */}
        <section className="card fb-section-card" style={{ padding: '20px', borderRadius: '16px' }}>
          <h3 style={{ fontSize: '15px', fontWeight: 800, margin: '0 0 16px 0', color: 'var(--color-text-main, #0f172a)', display: 'flex', alignItems: 'center', gap: '6px' }}>
            <UserPlus size={16} color="#4f7df3" aria-hidden="true" />
            <span>7-Day Member Growth Trend</span>
          </h3>
          <div style={{ display: 'flex', alignItems: 'flex-end', justifyContent: 'space-between', gap: '8px', height: '140px', paddingTop: '20px' }}>
            {dailyLabels.map((label, index) => {
              const val = dailyMemberJoins[index] ?? 0;
              const heightPct = Math.round((val / maxJoins) * 100);
              return (
                <div key={label} style={{ flex: 1, display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '6px', height: '100%' }}>
                  <span style={{ fontSize: '10.5px', fontWeight: 700, color: 'var(--color-text-main, #0f172a)' }}>
                    {val}
                  </span>
                  <div
                    style={{
                      flex: 1,
                      width: '100%',
                      maxWidth: '24px',
                      background: 'var(--color-surface-alt, #f1f5f9)',
                      borderRadius: '4px 4px 0 0',
                      display: 'flex',
                      alignItems: 'flex-end',
                      overflow: 'hidden',
                    }}
                  >
                    <div
                      style={{
                        width: '100%',
                        height: `${Math.max(6, heightPct)}%`,
                        background: 'var(--color-primary, #4f7df3)',
                        borderRadius: '4px 4px 0 0',
                        transition: 'height 0.4s ease',
                      }}
                    />
                  </div>
                  <span style={{ fontSize: '10px', color: 'var(--color-text-secondary, #64748b)', whiteSpace: 'nowrap' }}>
                    {label}
                  </span>
                </div>
              );
            })}
          </div>
        </section>

        {/* Post Creation Chart */}
        <section className="card fb-section-card" style={{ padding: '20px', borderRadius: '16px' }}>
          <h3 style={{ fontSize: '15px', fontWeight: 800, margin: '0 0 16px 0', color: 'var(--color-text-main, #0f172a)', display: 'flex', alignItems: 'center', gap: '6px' }}>
            <MessageSquare size={16} color="#7d42f0" aria-hidden="true" />
            <span>7-Day Post Creation Trend</span>
          </h3>
          <div style={{ display: 'flex', alignItems: 'flex-end', justifyContent: 'space-between', gap: '8px', height: '140px', paddingTop: '20px' }}>
            {dailyLabels.map((label, index) => {
              const val = dailyPostCreations[index] ?? 0;
              const heightPct = Math.round((val / maxPosts) * 100);
              return (
                <div key={label} style={{ flex: 1, display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '6px', height: '100%' }}>
                  <span style={{ fontSize: '10.5px', fontWeight: 700, color: 'var(--color-text-main, #0f172a)' }}>
                    {val}
                  </span>
                  <div
                    style={{
                      flex: 1,
                      width: '100%',
                      maxWidth: '24px',
                      background: 'var(--color-surface-alt, #f1f5f9)',
                      borderRadius: '4px 4px 0 0',
                      display: 'flex',
                      alignItems: 'flex-end',
                      overflow: 'hidden',
                    }}
                  >
                    <div
                      style={{
                        width: '100%',
                        height: `${Math.max(6, heightPct)}%`,
                        background: '#7d42f0',
                        borderRadius: '4px 4px 0 0',
                        transition: 'height 0.4s ease',
                      }}
                    />
                  </div>
                  <span style={{ fontSize: '10px', color: 'var(--color-text-secondary, #64748b)', whiteSpace: 'nowrap' }}>
                    {label}
                  </span>
                </div>
              );
            })}
          </div>
        </section>
      </div>

      {/* Top Contributors & Top Engaged Posts Grid */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(340px, 1fr))', gap: '20px' }}>
        {/* Top Contributors */}
        <section className="card fb-section-card" style={{ padding: '20px', borderRadius: '16px' }}>
          <header className="fb-section-card__header" style={{ marginBottom: '16px' }}>
            <h2 style={{ fontSize: '16px', fontWeight: 800, margin: 0, display: 'flex', alignItems: 'center', gap: '6px' }}>
              <Award size={18} color="#f59e0b" aria-hidden="true" />
              <span>Top Community Contributors</span>
            </h2>
          </header>

          <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
            {topContributors.length === 0 ? (
              <div style={{ fontSize: '13px', color: 'var(--color-text-secondary, #64748b)', textAlign: 'center', padding: '24px' }}>
                No posts published yet to calculate top contributors.
              </div>
            ) : (
              topContributors.map((contrib, rank) => {
                return (
                  <div
                    key={contrib.id}
                    className="card"
                    style={{
                      padding: '10px 14px',
                      borderRadius: '12px',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'space-between',
                      background: 'var(--color-surface, #ffffff)',
                    }}
                  >
                    <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                      <span style={{ fontSize: '14px', fontWeight: 900, color: 'var(--color-primary, #4f7df3)', minWidth: '20px' }}>
                        #{rank + 1}
                      </span>
                      <MemberAvatar member={contrib} size={32} />
                      <strong style={{ fontSize: '13.5px', color: 'var(--color-text-main, #0f172a)' }}>
                        {contrib.name}
                      </strong>
                    </div>

                    <span className="community-badge">
                      {contrib.posts_count} {contrib.posts_count === 1 ? 'post' : 'posts'}
                    </span>
                  </div>
                );
              })
            )}
          </div>
        </section>

        {/* Top Engaged Posts */}
        <section className="card fb-section-card" style={{ padding: '20px', borderRadius: '16px' }}>
          <header className="fb-section-card__header" style={{ marginBottom: '16px' }}>
            <h2 style={{ fontSize: '16px', fontWeight: 800, margin: 0, display: 'flex', alignItems: 'center', gap: '6px' }}>
              <Flame size={18} color="#ef4444" aria-hidden="true" />
              <span>Top Engaged Discussions</span>
            </h2>
          </header>

          <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
            {topPosts.length === 0 ? (
              <div style={{ fontSize: '13px', color: 'var(--color-text-secondary, #64748b)', textAlign: 'center', padding: '24px' }}>
                No discussion posts available to calculate engagement metrics.
              </div>
            ) : (
              topPosts.map((post) => (
                <div
                  key={post.id}
                  className="card"
                  style={{
                    padding: '12px 14px',
                    borderRadius: '12px',
                    background: 'var(--color-surface, #ffffff)',
                  }}
                >
                  <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                    <strong style={{ fontSize: '13px', color: 'var(--color-text-main, #0f172a)' }}>
                      {post.member?.name || 'Member'}
                    </strong>
                    <span style={{ fontSize: '11px', color: 'var(--color-text-secondary, #64748b)' }}>
                      {formatRelativeTime(post.created_at)}
                    </span>
                  </div>

                  <p style={{ fontSize: '13px', color: 'var(--color-text-secondary, #64748b)', margin: '4px 0', lineHeight: 1.4 }}>
                    {post.body?.length > 80 ? `${post.body.slice(0, 80)}...` : post.body}
                  </p>

                  <div style={{ display: 'flex', gap: '12px', fontSize: '11.5px', color: 'var(--color-primary, #4f7df3)', fontWeight: 600, marginTop: '4px' }}>
                    <span style={{ display: 'inline-flex', alignItems: 'center', gap: '4px' }}>
                      <ThumbsUp size={12} aria-hidden="true" />
                      <span>{post.likes_count ?? 0} likes</span>
                    </span>
                    <span style={{ display: 'inline-flex', alignItems: 'center', gap: '4px' }}>
                      <MessageSquare size={12} aria-hidden="true" />
                      <span>{post.comments_count ?? 0} comments</span>
                    </span>
                  </div>
                </div>
              ))
            )}
          </div>
        </section>
      </div>
    </div>
  );
}

export default CommunityAnalyticsPage;
