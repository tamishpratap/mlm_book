import { Link } from 'react-router-dom';
import {
  Building2,
  Users,
  FileText,
  Star,
  Megaphone,
  Plus,
  ArrowRight,
  CheckCircle2,
  ExternalLink,
} from 'lucide-react';
import { getAvatarUrl, getCoverUrl } from '../../../utils/assetHelper';

export function BusinessPagesOverview({ businessPages }) {
  const pagesList = businessPages?.pages || [];
  const totalPages = businessPages?.total_pages || 0;
  const totalFollowers = businessPages?.total_followers || 0;
  const totalPosts = businessPages?.total_posts || 0;
  const totalReviews = businessPages?.total_reviews || 0;

  return (
    <section className="dash-wallet-section" aria-label="My Business Pages">
      <div className="dash-section-header">
        <div className="dash-section-title">
          <div className="dash-section-icon" style={{ backgroundColor: 'rgba(147, 51, 234, 0.1)', color: '#9333ea' }}>
            <Building2 size={20} />
          </div>
          <div>
            <h2>My Business Pages ({totalPages})</h2>
            <p style={{ margin: 0, fontSize: '0.825rem', color: '#64748b' }}>
              Manage brand presence, follower audience, reviews & page campaigns
            </p>
          </div>
        </div>

        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
          <Link
            to="/member/business-pages/create"
            className="dash-btn-primary"
            style={{ padding: '8px 14px', fontSize: '0.825rem', background: '#9333ea' }}
          >
            <Plus size={15} />
            <span>New Page</span>
          </Link>
          <Link
            to="/member/business-pages"
            className="dash-btn-outline"
            style={{ padding: '8px 14px', fontSize: '0.825rem' }}
          >
            <span>View All</span>
            <ArrowRight size={14} />
          </Link>
        </div>
      </div>

      {/* Aggregate Stats Summary Bar */}
      {totalPages > 0 && (
        <div
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: '12px',
            flexWrap: 'wrap',
            marginBottom: '18px',
            padding: '12px 16px',
            backgroundColor: '#f8fafc',
            border: '1px solid #e2e8f0',
            borderRadius: '12px',
            fontSize: '0.825rem',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '6px', color: '#475569' }}>
            <Building2 size={15} color="#9333ea" />
            <span>Owned Pages: <strong style={{ color: '#1e293b' }}>{totalPages}</strong></span>
          </div>
          <span style={{ color: '#cbd5e1' }}>•</span>
          <div style={{ display: 'flex', alignItems: 'center', gap: '6px', color: '#475569' }}>
            <Users size={15} color="#2563eb" />
            <span>Total Followers: <strong style={{ color: '#1e293b' }}>{totalFollowers}</strong></span>
          </div>
          <span style={{ color: '#cbd5e1' }}>•</span>
          <div style={{ display: 'flex', alignItems: 'center', gap: '6px', color: '#475569' }}>
            <FileText size={15} color="#059669" />
            <span>Published Posts: <strong style={{ color: '#1e293b' }}>{totalPosts}</strong></span>
          </div>
          <span style={{ color: '#cbd5e1' }}>•</span>
          <div style={{ display: 'flex', alignItems: 'center', gap: '6px', color: '#475569' }}>
            <Star size={15} color="#d97706" />
            <span>Reviews: <strong style={{ color: '#1e293b' }}>{totalReviews}</strong></span>
          </div>
        </div>
      )}

      {/* Pages Grid or Empty State */}
      {pagesList.length === 0 ? (
        <div className="dash-empty-biz-card">
          <div
            style={{
              width: '54px',
              height: '54px',
              borderRadius: '16px',
              backgroundColor: '#f3e8ff',
              color: '#9333ea',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
            }}
          >
            <Building2 size={28} />
          </div>
          <h3>Launch Your First Business Page</h3>
          <p>
            Build your professional brand presence, engage targeted customers, showcase products, and run ad campaigns across MLM Book.
          </p>
          <Link
            to="/member/business-pages/create"
            className="dash-btn-primary"
            style={{ background: '#9333ea' }}
          >
            <Plus size={16} />
            <span>Create Business Page</span>
          </Link>
        </div>
      ) : (
        <div className="dash-biz-grid">
          {pagesList.map((p) => {
            const logoUrl = p.logo_url || (p.logo ? getAvatarUrl(p.logo) : null);
            const coverUrl = p.cover_url || (p.cover_photo ? getCoverUrl(p.cover_photo) : null);

            return (
              <div key={p.id} className="dash-biz-card">
                <div
                  className="dash-biz-cover"
                  style={coverUrl ? { backgroundImage: `url(${coverUrl})` } : undefined}
                />

                <div className="dash-biz-body">
                  <div className="dash-biz-avatar-row">
                    <img
                      src={logoUrl || '/member_assets/images/dashboard/image/profile.png'}
                      alt={p.page_name}
                      className="dash-biz-logo"
                      onError={(e) => {
                        e.currentTarget.src = '/member_assets/images/dashboard/image/profile.png';
                      }}
                    />
                    <span
                      style={{
                        fontSize: '0.725rem',
                        fontWeight: 700,
                        padding: '3px 8px',
                        borderRadius: '8px',
                        textTransform: 'uppercase',
                        backgroundColor: p.status === 'active' ? '#dcfce7' : '#fee2e2',
                        color: p.status === 'active' ? '#15803d' : '#b91c1c',
                      }}
                    >
                      {p.status}
                    </span>
                  </div>

                  <h3 className="dash-biz-name">
                    <span>{p.page_name}</span>
                    {p.is_verified && <CheckCircle2 size={15} color="#2563eb" />}
                  </h3>

                  <p className="dash-biz-category">
                    {p.category || 'Business Page'}
                  </p>

                  <div className="dash-biz-metrics-row">
                    <div className="dash-biz-stat-item">
                      <span className="dash-biz-stat-num">{p.followers_count || 0}</span>
                      <span className="dash-biz-stat-lbl">Followers</span>
                    </div>
                    <div className="dash-biz-stat-item">
                      <span className="dash-biz-stat-num">{p.posts_count || 0}</span>
                      <span className="dash-biz-stat-lbl">Posts</span>
                    </div>
                    <div className="dash-biz-stat-item">
                      <span className="dash-biz-stat-num">{p.reviews_count || 0}</span>
                      <span className="dash-biz-stat-lbl">Reviews</span>
                    </div>
                    <div className="dash-biz-stat-item">
                      <span className="dash-biz-stat-num" style={{ color: p.active_campaigns_count > 0 ? '#2563eb' : '#64748b' }}>
                        {p.active_campaigns_count || 0}
                      </span>
                      <span className="dash-biz-stat-lbl">Ads</span>
                    </div>
                  </div>

                  <div className="dash-biz-card-footer">
                    <Link
                      to={`/member/business-pages/${p.slug}`}
                      className="member-button member-button--secondary"
                      style={{ flex: 1, padding: '7px 10px', fontSize: '0.8rem', justifyContent: 'center' }}
                    >
                      <ExternalLink size={13} />
                      <span>View Page</span>
                    </Link>

                    <Link
                      to={`/member/business-pages/${p.slug}?tab=ads`}
                      className="member-button member-button--primary"
                      style={{ padding: '7px 12px', fontSize: '0.8rem', gap: '5px' }}
                      title="Run Ad on this Page"
                    >
                      <Megaphone size={13} />
                      <span>Ads</span>
                    </Link>
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

export default BusinessPagesOverview;
