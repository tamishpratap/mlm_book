import { useState, useEffect, useCallback } from 'react';
import { useParams, Link, useSearchParams } from 'react-router-dom';
import {
  BarChart3,
  ArrowLeft,
  Download,
  FileText,
  Activity,
  TrendingUp,
  Zap,
  Smile,
  Users,
  Newspaper,
  Star,
  Award,
  MessageSquare,
  Eye,
  UsersRound,
  ThumbsUp,
  Monitor,
  Smartphone,
  Tablet,
  AlertCircle,
  Pin,
} from 'lucide-react';
import businessApi from '../../api/businessApi';

const PERIOD_OPTIONS = [
  { value: 'today', label: 'Today' },
  { value: 'yesterday', label: 'Yesterday' },
  { value: '7days', label: 'Last 7 Days' },
  { value: '30days', label: 'Last 30 Days' },
  { value: '90days', label: 'Last 90 Days' },
  { value: 'this_year', label: 'This Year' },
  { value: 'all', label: 'Lifetime' },
];

function formatDate(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

export function BusinessAnalyticsPage() {
  const { slug } = useParams();
  const [searchParams, setSearchParams] = useSearchParams();

  const selectedPeriod = searchParams.get('period') || '30days';

  const [analyticsData, setAnalyticsData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  const fetchAnalytics = useCallback(() => {
    if (!slug) return Promise.resolve(null);
    return businessApi.getAnalytics(slug, { period: selectedPeriod });
  }, [slug, selectedPeriod]);

  useEffect(() => {
    let isMounted = true;

    fetchAnalytics()
      .then((res) => {
        if (isMounted && res && res.success) {
          setAnalyticsData(res);
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load Business Analytics.');
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [fetchAnalytics]);

  const handlePeriodChange = (newPeriod) => {
    const newParams = new URLSearchParams(searchParams);
    newParams.set('period', newPeriod);
    setSearchParams(newParams);
  };

  const handleExport = (format) => {
    alert(`Exporting Business Analytics report in ${format.toUpperCase()} format...\n(Export feature architecture is prepared).`);
  };

  if (isLoading) {
    return (
      <div style={{ textAlign: 'center', padding: '80px', color: 'var(--color-text-secondary)' }}>
        Loading Business Analytics platform...
      </div>
    );
  }

  if (error || !analyticsData?.business_page) {
    return (
      <div className="card" style={{ maxWidth: '640px', margin: '40px auto', padding: '32px', textAlign: 'center' }}>
        <AlertCircle size={40} color="#dc2626" style={{ margin: '0 auto 12px auto' }} />
        <h2 style={{ fontSize: '18px', color: '#111827', marginBottom: '8px' }}>Analytics Access Restricted</h2>
        <p style={{ color: '#687386', marginBottom: '20px', fontSize: '14px' }}>
          {error || 'Only authorized team members can view Business Analytics.'}
        </p>
        <Link to={`/member/business-pages/${slug}`} className="member-button member-button--secondary">
          <ArrowLeft size={16} />
          <span>Back to Business Page</span>
        </Link>
      </div>
    );
  }

  const page = analyticsData.business_page;
  const scores = analyticsData.scores || {
    health_score: 50,
    growth_score: 50,
    engagement_score: 50,
    satisfaction_score: 50,
  };
  const ratingDistribution = analyticsData.rating_distribution || { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 };
  const deviceBreakdown = analyticsData.device_breakdown || {};
  const topPosts = analyticsData.top_posts || [];
  const totalReviews = Math.max(1, Number(analyticsData.reviews_count || 0));
  const totalViews = Math.max(1, Number(analyticsData.views_count || 0));

  return (
    <div className="biz-page" style={{ maxWidth: '1100px', margin: '16px auto', padding: '0 16px' }}>
      {/* Hero Analytics Header */}
      <header
        className="biz-header"
        style={{
          marginBottom: '20px',
          background: 'linear-gradient(135deg, #1e293b 0%, #0f172a 100%)',
          padding: '24px',
          borderRadius: '18px',
          color: '#fff',
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          flexWrap: 'wrap',
          gap: '16px',
        }}
      >
        <div className="biz-header__info">
          <span
            className="biz-badge biz-badge--category"
            style={{
              background: 'rgba(79, 125, 243, 0.2)',
              color: '#60a5fa',
              border: '1px solid rgba(96, 165, 250, 0.3)',
              marginBottom: '8px',
              display: 'inline-flex',
              alignItems: 'center',
              gap: '6px',
            }}
          >
            <BarChart3 size={14} />
            <span>Enterprise Analytics & Growth Platform</span>
          </span>
          <h1 style={{ fontSize: '24px', fontWeight: 800, color: '#fff', margin: '0 0 6px 0' }}>
            {page.page_name} Performance Insights
          </h1>
          <p style={{ fontSize: '13.5px', color: '#94a3b8', margin: 0 }}>
            Comprehensive audience growth, post engagement, customer reviews, and health metrics.
          </p>
        </div>

        <div className="biz-header__actions" style={{ display: 'flex', alignItems: 'center', gap: '10px', flexWrap: 'wrap' }}>
          {/* Period Selector */}
          <select
            value={selectedPeriod}
            onChange={(e) => handlePeriodChange(e.target.value)}
            className="biz-filter-select"
            style={{ background: '#fff', color: '#1d2738', padding: '8px 12px', fontSize: '13px', borderRadius: '10px' }}
          >
            {PERIOD_OPTIONS.map((opt) => (
              <option key={opt.value} value={opt.value}>
                {opt.label}
              </option>
            ))}
          </select>

          {/* Export Buttons */}
          <button
            type="button"
            className="member-button member-button--secondary"
            onClick={() => handleExport('csv')}
            style={{ background: 'rgba(255, 255, 255, 0.1)', color: '#fff', border: '1px solid rgba(255, 255, 255, 0.2)' }}
          >
            <Download size={14} />
            <span>Export CSV</span>
          </button>
          <button
            type="button"
            className="member-button member-button--secondary"
            onClick={() => handleExport('pdf')}
            style={{ background: 'rgba(255, 255, 255, 0.1)', color: '#fff', border: '1px solid rgba(255, 255, 255, 0.2)' }}
          >
            <FileText size={14} />
            <span>Export PDF</span>
          </button>
          <Link to={`/member/business-pages/${slug}`} className="member-button member-button--primary">
            <ArrowLeft size={15} />
            <span>Back to Profile</span>
          </Link>
        </div>
      </header>

      {/* Health Diagnostic Scorecards Grid */}
      <div
        style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))',
          gap: '16px',
          marginBottom: '24px',
        }}
      >
        <div
          className="biz-info-card"
          style={{
            padding: '20px',
            background: 'linear-gradient(135deg, rgba(79, 125, 243, 0.08), rgba(138, 43, 226, 0.08))',
            border: '1px solid rgba(79, 125, 243, 0.2)',
            borderRadius: '16px',
          }}
        >
          <span style={{ fontSize: '12px', color: '#687386', fontWeight: 700, textTransform: 'uppercase', display: 'flex', alignItems: 'center', gap: '6px', marginBottom: '6px' }}>
            <Activity size={14} color="#4f7df3" />
            <span>Business Health Score</span>
          </span>
          <strong style={{ fontSize: '32px', fontWeight: 900, color: '#4f7df3', display: 'block' }}>
            {scores.health_score}/100
          </strong>
          <div style={{ height: '6px', borderRadius: '3px', background: '#e7ecf4', marginTop: '8px', overflow: 'hidden' }}>
            <div style={{ height: '100%', width: `${scores.health_score}%`, background: '#4f7df3', borderRadius: '3px' }} />
          </div>
        </div>

        <div
          className="biz-info-card"
          style={{
            padding: '20px',
            background: 'linear-gradient(135deg, rgba(32, 200, 117, 0.08), rgba(79, 125, 243, 0.08))',
            border: '1px solid rgba(32, 200, 117, 0.2)',
            borderRadius: '16px',
          }}
        >
          <span style={{ fontSize: '12px', color: '#687386', fontWeight: 700, textTransform: 'uppercase', display: 'flex', alignItems: 'center', gap: '6px', marginBottom: '6px' }}>
            <TrendingUp size={14} color="#20c875" />
            <span>Growth Index</span>
          </span>
          <strong style={{ fontSize: '32px', fontWeight: 900, color: '#20c875', display: 'block' }}>
            {scores.growth_score}/100
          </strong>
          <div style={{ height: '6px', borderRadius: '3px', background: '#e7ecf4', marginTop: '8px', overflow: 'hidden' }}>
            <div style={{ height: '100%', width: `${scores.growth_score}%`, background: '#20c875', borderRadius: '3px' }} />
          </div>
        </div>

        <div
          className="biz-info-card"
          style={{
            padding: '20px',
            background: 'linear-gradient(135deg, rgba(138, 43, 226, 0.08), rgba(247, 185, 64, 0.08))',
            border: '1px solid rgba(138, 43, 226, 0.2)',
            borderRadius: '16px',
          }}
        >
          <span style={{ fontSize: '12px', color: '#687386', fontWeight: 700, textTransform: 'uppercase', display: 'flex', alignItems: 'center', gap: '6px', marginBottom: '6px' }}>
            <Zap size={14} color="#8a2be2" />
            <span>Engagement Index</span>
          </span>
          <strong style={{ fontSize: '32px', fontWeight: 900, color: '#8a2be2', display: 'block' }}>
            {scores.engagement_score}/100
          </strong>
          <div style={{ height: '6px', borderRadius: '3px', background: '#e7ecf4', marginTop: '8px', overflow: 'hidden' }}>
            <div style={{ height: '100%', width: `${scores.engagement_score}%`, background: '#8a2be2', borderRadius: '3px' }} />
          </div>
        </div>

        <div
          className="biz-info-card"
          style={{
            padding: '20px',
            background: 'linear-gradient(135deg, rgba(247, 185, 64, 0.08), rgba(32, 200, 117, 0.08))',
            border: '1px solid rgba(247, 185, 64, 0.2)',
            borderRadius: '16px',
          }}
        >
          <span style={{ fontSize: '12px', color: '#687386', fontWeight: 700, textTransform: 'uppercase', display: 'flex', alignItems: 'center', gap: '6px', marginBottom: '6px' }}>
            <Smile size={14} color="#f7b940" />
            <span>Satisfaction Index</span>
          </span>
          <strong style={{ fontSize: '32px', fontWeight: 900, color: '#b7791f', display: 'block' }}>
            {scores.satisfaction_score}/100
          </strong>
          <div style={{ height: '6px', borderRadius: '3px', background: '#e7ecf4', marginTop: '8px', overflow: 'hidden' }}>
            <div style={{ height: '100%', width: `${scores.satisfaction_score}%`, background: '#f7b940', borderRadius: '3px' }} />
          </div>
        </div>
      </div>

      {/* Overview Statistic Cards Grid */}
      <div
        style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fill, minmax(220px, 1fr))',
          gap: '16px',
          marginBottom: '24px',
        }}
      >
        <div className="biz-info-card" style={{ padding: '16px', borderRadius: '14px', background: '#fff', border: '1px solid #e5e7eb' }}>
          <div style={{ display: 'flex', alignItems: 'center', justifySelf: 'stretch', justifyContent: 'space-between' }}>
            <div>
              <span style={{ fontSize: '12px', color: '#687386' }}>Total Followers</span>
              <strong style={{ fontSize: '22px', color: '#1d2738', display: 'block', marginTop: '2px' }}>
                {analyticsData.followers_count ?? 0}
              </strong>
            </div>
            <div style={{ width: '40px', height: '40px', borderRadius: '12px', background: 'rgba(79, 125, 243, 0.1)', color: '#4f7df3', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              <Users size={20} />
            </div>
          </div>
        </div>

        <div className="biz-info-card" style={{ padding: '16px', borderRadius: '14px', background: '#fff', border: '1px solid #e5e7eb' }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <div>
              <span style={{ fontSize: '12px', color: '#687386' }}>Timeline Posts</span>
              <strong style={{ fontSize: '22px', color: '#1d2738', display: 'block', marginTop: '2px' }}>
                {analyticsData.posts_count ?? 0}
              </strong>
            </div>
            <div style={{ width: '40px', height: '40px', borderRadius: '12px', background: 'rgba(32, 200, 117, 0.1)', color: '#20c875', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              <Newspaper size={20} />
            </div>
          </div>
        </div>

        <div className="biz-info-card" style={{ padding: '16px', borderRadius: '14px', background: '#fff', border: '1px solid #e5e7eb' }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <div>
              <span style={{ fontSize: '12px', color: '#687386' }}>Total Reviews</span>
              <strong style={{ fontSize: '22px', color: '#1d2738', display: 'block', marginTop: '2px' }}>
                {analyticsData.reviews_count ?? 0}
              </strong>
            </div>
            <div style={{ width: '40px', height: '40px', borderRadius: '12px', background: 'rgba(247, 185, 64, 0.1)', color: '#f7b940', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              <Star size={20} />
            </div>
          </div>
        </div>

        <div className="biz-info-card" style={{ padding: '16px', borderRadius: '14px', background: '#fff', border: '1px solid #e5e7eb' }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <div>
              <span style={{ fontSize: '12px', color: '#687386' }}>Average Rating</span>
              <strong style={{ fontSize: '22px', color: '#1d2738', display: 'block', marginTop: '2px' }}>
                {analyticsData.avg_rating ?? 0} ★
              </strong>
            </div>
            <div style={{ width: '40px', height: '40px', borderRadius: '12px', background: 'rgba(247, 185, 64, 0.1)', color: '#f7b940', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              <Award size={20} />
            </div>
          </div>
        </div>

        <div className="biz-info-card" style={{ padding: '16px', borderRadius: '14px', background: '#fff', border: '1px solid #e5e7eb' }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <div>
              <span style={{ fontSize: '12px', color: '#687386' }}>Customer Inquiries</span>
              <strong style={{ fontSize: '22px', color: '#1d2738', display: 'block', marginTop: '2px' }}>
                {analyticsData.conversations_count ?? 0}
              </strong>
            </div>
            <div style={{ width: '40px', height: '40px', borderRadius: '12px', background: 'rgba(138, 43, 226, 0.1)', color: '#8a2be2', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              <MessageSquare size={20} />
            </div>
          </div>
        </div>

        <div className="biz-info-card" style={{ padding: '16px', borderRadius: '14px', background: '#fff', border: '1px solid #e5e7eb' }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <div>
              <span style={{ fontSize: '12px', color: '#687386' }}>Page Views</span>
              <strong style={{ fontSize: '22px', color: '#1d2738', display: 'block', marginTop: '2px' }}>
                {analyticsData.views_count ?? 0}
              </strong>
            </div>
            <div style={{ width: '40px', height: '40px', borderRadius: '12px', background: 'rgba(79, 125, 243, 0.1)', color: '#4f7df3', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              <Eye size={20} />
            </div>
          </div>
        </div>

        <div className="biz-info-card" style={{ padding: '16px', borderRadius: '14px', background: '#fff', border: '1px solid #e5e7eb' }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <div>
              <span style={{ fontSize: '12px', color: '#687386' }}>Team Size</span>
              <strong style={{ fontSize: '22px', color: '#1d2738', display: 'block', marginTop: '2px' }}>
                {analyticsData.team_count ?? 1}
              </strong>
            </div>
            <div style={{ width: '40px', height: '40px', borderRadius: '12px', background: 'rgba(32, 200, 117, 0.1)', color: '#20c875', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              <UsersRound size={20} />
            </div>
          </div>
        </div>

        <div className="biz-info-card" style={{ padding: '16px', borderRadius: '14px', background: '#fff', border: '1px solid #e5e7eb' }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <div>
              <span style={{ fontSize: '12px', color: '#687386' }}>Feedback Index</span>
              <strong style={{ fontSize: '14.5px', color: '#20c875', display: 'block', marginTop: '6px' }}>
                {analyticsData.recommendation_badge || 'No Reviews Yet'}
              </strong>
            </div>
            <div style={{ width: '40px', height: '40px', borderRadius: '12px', background: 'rgba(32, 200, 117, 0.1)', color: '#20c875', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              <ThumbsUp size={20} />
            </div>
          </div>
        </div>
      </div>

      {/* Analytics Breakdown Split Cards */}
      <div
        style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fit, minmax(320px, 1fr))',
          gap: '20px',
          marginBottom: '24px',
        }}
      >
        {/* Rating Distribution */}
        <div className="biz-info-card" style={{ padding: '20px', background: '#fff', borderRadius: '16px', border: '1px solid #e5e7eb' }}>
          <h3 className="biz-info-card__title" style={{ fontSize: '15px', fontWeight: 700, margin: '0 0 14px 0', display: 'flex', alignItems: 'center', gap: '8px' }}>
            <Star size={18} color="#f7b940" />
            <span>Ratings & Review Breakdown</span>
          </h3>
          <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
            {[5, 4, 3, 2, 1].map((st) => {
              const cnt = Number(ratingDistribution[st] || 0);
              const pct = Math.round((cnt / totalReviews) * 100);
              return (
                <div key={st} style={{ display: 'flex', alignItems: 'center', gap: '10px', fontSize: '13px', color: '#687386' }}>
                  <span style={{ width: '55px', fontWeight: 600 }}>{st} Stars</span>
                  <div style={{ flex: 1, height: '10px', borderRadius: '5px', background: '#edf3ff', overflow: 'hidden' }}>
                    <div style={{ height: '100%', width: `${pct}%`, background: '#f7b940', borderRadius: '5px' }} />
                  </div>
                  <span style={{ width: '40px', textAlign: 'right', fontWeight: 700, color: '#1d2738' }}>{cnt}</span>
                </div>
              );
            })}
          </div>
        </div>

        {/* Visitor Devices Breakdown */}
        <div className="biz-info-card" style={{ padding: '20px', background: '#fff', borderRadius: '16px', border: '1px solid #e5e7eb' }}>
          <h3 className="biz-info-card__title" style={{ fontSize: '15px', fontWeight: 700, margin: '0 0 14px 0', display: 'flex', alignItems: 'center', gap: '8px' }}>
            <Monitor size={18} color="#4f7df3" />
            <span>Visitor Device Breakdown</span>
          </h3>
          <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
            {[
              { key: 'desktop', label: 'Desktop Computers', icon: Monitor },
              { key: 'mobile', label: 'Mobile Phones', icon: Smartphone },
              { key: 'tablet', label: 'Tablets', icon: Tablet },
            ].map(({ key, label, icon: Icon }) => {
              const dCnt = Number(deviceBreakdown[key] || 0);
              const dPct = Math.round((dCnt / totalViews) * 100);
              return (
                <div key={key}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: '13px', color: '#1d2738', marginBottom: '4px' }}>
                    <span style={{ display: 'flex', alignItems: 'center', gap: '6px', fontWeight: 600 }}>
                      <Icon size={14} color="#687386" />
                      <span>{label}</span>
                    </span>
                    <span>
                      {dCnt} ({dPct}%)
                    </span>
                  </div>
                  <div style={{ height: '8px', borderRadius: '4px', background: '#edf3ff', overflow: 'hidden' }}>
                    <div style={{ height: '100%', width: `${dPct}%`, background: '#4f7df3', borderRadius: '4px' }} />
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      </div>

      {/* Top Posts Matrix Table */}
      <div className="biz-info-card" style={{ padding: '20px', background: '#fff', borderRadius: '16px', border: '1px solid #e5e7eb', marginBottom: '24px' }}>
        <h3 className="biz-info-card__title" style={{ fontSize: '15px', fontWeight: 700, margin: '0 0 14px 0', display: 'flex', alignItems: 'center', gap: '8px' }}>
          <Newspaper size={18} color="#4f7df3" />
          <span>Recent Posts Engagement Matrix</span>
        </h3>

        <div style={{ overflowX: 'auto' }}>
          <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '13px' }}>
            <thead>
              <tr style={{ borderBottom: '2px solid #e7ecf4', textAlign: 'left', color: '#687386' }}>
                <th style={{ padding: '10px' }}>Post Content</th>
                <th style={{ padding: '10px' }}>Published Date</th>
                <th style={{ padding: '10px', textAlign: 'center' }}>Likes</th>
                <th style={{ padding: '10px', textAlign: 'center' }}>Comments</th>
                <th style={{ padding: '10px', textAlign: 'center' }}>Status</th>
              </tr>
            </thead>
            <tbody>
              {topPosts.length === 0 ? (
                <tr>
                  <td colSpan={5} style={{ textAlign: 'center', padding: '24px', color: '#98a2b3' }}>
                    No timeline posts published yet.
                  </td>
                </tr>
              ) : (
                topPosts.map((tp) => (
                  <tr key={tp.id} style={{ borderBottom: '1px solid #f1f5f9' }}>
                    <td style={{ padding: '10px', color: '#1d2738', fontWeight: 600, maxWidth: '300px' }}>
                      {tp.content ? (
                        <span style={{ display: 'block', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                          {tp.content}
                        </span>
                      ) : (
                        <span style={{ color: '#98a2b3', fontStyle: 'italic' }}>Media / Attachment Post</span>
                      )}
                    </td>
                    <td style={{ padding: '10px', color: '#98a2b3' }}>{formatDate(tp.created_at)}</td>
                    <td style={{ padding: '10px', textAlign: 'center', fontWeight: 700, color: '#4f7df3' }}>
                      {tp.likes_count ?? (Array.isArray(tp.likes) ? tp.likes.length : 0)}
                    </td>
                    <td style={{ padding: '10px', textAlign: 'center', fontWeight: 700, color: '#20c875' }}>
                      {tp.comments_count ?? (Array.isArray(tp.comments) ? tp.comments.length : 0)}
                    </td>
                    <td style={{ padding: '10px', textAlign: 'center' }}>
                      {tp.is_pinned ? (
                        <span className="biz-badge biz-badge--category" style={{ fontSize: '10px', display: 'inline-flex', alignItems: 'center', gap: '3px' }}>
                          <Pin size={10} />
                          <span>Pinned</span>
                        </span>
                      ) : (
                        <span className="biz-badge" style={{ fontSize: '10px', background: '#edf3ff', color: '#4f7df3' }}>
                          Active
                        </span>
                      )}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}

export default BusinessAnalyticsPage;
