import { useState, useEffect, useCallback } from 'react';
import { useParams, Link } from 'react-router-dom';
import {
  Activity,
  ArrowLeft,
  RotateCw,
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

export function CommunityActivityTimelinePage() {
  const { slug } = useParams();

  const [filter, setFilter] = useState('all');
  const [page, setPage] = useState(1);
  const [data, setData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState(null);

  const fetchActivity = useCallback(
    (f, p) => {
      if (!slug) return Promise.resolve(null);
      return communityApi.getActivityTimeline(slug, f, p);
    },
    [slug]
  );

  useEffect(() => {
    let isMounted = true;

    fetchActivity(filter, page)
      .then((res) => {
        if (isMounted && res) {
          setData(res);
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load community activity timeline.');
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [fetchActivity, filter, page]);

  const handleRefresh = () => {
    setIsRefreshing(true);
    fetchActivity(filter, page)
      .then((res) => {
        if (res) setData(res);
      })
      .catch(() => {})
      .finally(() => setIsRefreshing(false));
  };

  const community = data?.community;
  const activitiesList = data?.activities?.data || [];
  const pagination = data?.activities || {};

  return (
    <div className="community-activity-page">
      {/* Back button */}
      <div className="community-back-nav">
        <Link to={`/member/community/${slug}`} className="community-back-link">
          <ArrowLeft size={16} aria-hidden="true" />
          <span>Back to Community</span>
        </Link>
      </div>

      <section className="community-activity-card">
        {/* Header */}
        <header className="community-activity-header">
          <div className="community-activity-header__info">
            <h1>
              <Activity size={22} className="community-activity-header__icon" aria-hidden="true" />
              <span>{community?.name ? `${community.name} Activity Timeline` : 'Community Activity Timeline'}</span>
            </h1>
            <p>
              Chronological history of community events, posts, member joins, and governance updates.
            </p>
          </div>
        </header>

        {/* Activity Filters & Controls */}
        <div className="community-activity-controls">
          <div className="community-activity-tabs" role="tablist" aria-label="Activity Filter Tabs">
            {[
              { id: 'all', label: 'All Activity' },
              { id: 'posts', label: 'Posts' },
              { id: 'members', label: 'Members' },
              { id: 'announcements', label: 'Announcements' },
              { id: 'moderation', label: 'Moderation' },
              { id: 'settings', label: 'Settings' },
            ].map((tab) => (
              <button
                key={tab.id}
                type="button"
                role="tab"
                aria-selected={filter === tab.id}
                className={`community-activity-tab ${filter === tab.id ? 'is-active' : ''}`}
                onClick={() => {
                  setFilter(tab.id);
                  setPage(1);
                }}
              >
                <span>{tab.label}</span>
              </button>
            ))}
          </div>

          <button
            type="button"
            className="community-activity-refresh-btn"
            onClick={handleRefresh}
            disabled={isRefreshing}
            title="Refresh Timeline"
            aria-label="Refresh Timeline"
          >
            <RotateCw size={14} className={isRefreshing ? 'spin-icon' : ''} aria-hidden="true" />
          </button>
        </div>

        {/* Timeline Content */}
        <div className="community-activity-body">
          {isLoading ? (
            <div className="community-activity-loading">
              <RotateCw size={24} className="spin-icon" style={{ marginBottom: '12px', color: 'var(--color-primary, #4f7df3)' }} />
              <span>Loading activity timeline...</span>
            </div>
          ) : error ? (
            <div className="community-activity-error">
              {error}
            </div>
          ) : activitiesList.length === 0 ? (
            <div className="community-activity-empty">
              <div className="community-activity-empty__icon">
                <Activity size={26} aria-hidden="true" />
              </div>
              <h3>No Activity Recorded</h3>
              <p>No activity timeline entries match this filter.</p>
            </div>
          ) : (
            <div className="community-activity-timeline">
              <div className="community-activity-timeline__line" aria-hidden="true" />

              {activitiesList.map((act) => {
                const actor = act.actor;
                return (
                  <div key={act.id} className="community-activity-item">
                    <div className="community-activity-item__dot" aria-hidden="true" />

                    <div className="community-activity-item__header">
                      <div className="community-activity-item__actor">
                        <MemberAvatar member={actor} size={34} />
                        <div className="community-activity-item__actor-info">
                          <strong className="community-activity-item__name">
                            {actor?.name || 'System'}
                          </strong>
                          <span className="community-activity-item__badge">
                            {act.action}
                          </span>
                        </div>
                      </div>

                      <span className="community-activity-item__time">
                        {formatRelativeTime(act.created_at)}
                      </span>
                    </div>

                    {act.metadata && (
                      <div className="community-activity-item__metadata">
                        {act.metadata.reason && <div>Reason: {act.metadata.reason}</div>}
                        {act.metadata.duration && <div>Duration: {act.metadata.duration}</div>}
                      </div>
                    )}
                  </div>
                );
              })}
            </div>
          )}

          {/* Pagination */}
          {pagination.last_page > 1 && (
            <div className="community-activity-pagination">
              <button
                type="button"
                className="member-button member-button--secondary"
                disabled={page <= 1}
                onClick={() => setPage((p) => Math.max(1, p - 1))}
              >
                Previous
              </button>
              <span className="community-activity-pagination__info">
                Page {pagination.current_page || page} of {pagination.last_page}
              </span>
              <button
                type="button"
                className="member-button member-button--secondary"
                disabled={page >= pagination.last_page}
                onClick={() => setPage((p) => p + 1)}
              >
                Next
              </button>
            </div>
          )}
        </div>
      </section>
    </div>
  );
}

export default CommunityActivityTimelinePage;
