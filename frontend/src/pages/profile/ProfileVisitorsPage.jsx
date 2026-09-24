import { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { Eye, ArrowLeft, ExternalLink } from 'lucide-react';
import profileApi from '../../api/profileApi';
import VerifiedBadge from '../../components/common/VerifiedBadge';
import MemberAvatar from '../../components/common/MemberAvatar';

function getInitials(name) {
  if (!name) return 'U';
  const parts = name.trim().split(/\s+/);
  return parts.slice(0, 2).map((p) => p[0].toUpperCase()).join('') || 'U';
}

function formatRelativeTime(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  const now = new Date();
  const diffInSec = Math.floor((now - date) / 1000);

  if (diffInSec < 60) return 'Just now';
  if (diffInSec < 3600) return `${Math.floor(diffInSec / 60)}m ago`;
  if (diffInSec < 86400) return `${Math.floor(diffInSec / 3600)}h ago`;
  if (diffInSec < 604800) return `${Math.floor(diffInSec / 86400)}d ago`;
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

export function ProfileVisitorsPage() {
  const [visitorsData, setVisitorsData] = useState(null);
  const [page, setPage] = useState(1);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  const fetchVisitors = useCallback(() => {
    return profileApi.getVisitors(page);
  }, [page]);

  useEffect(() => {
    let isMounted = true;

    fetchVisitors()
      .then((res) => {
        if (isMounted && res && res.success) {
          setVisitorsData(res.visitors || null);
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load profile visitors.');
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [fetchVisitors]);

  const visitorsList = visitorsData?.data || [];
  const totalVisitors = visitorsData?.total ?? 0;

  if (isLoading) {
    return (
      <div style={{ textAlign: 'center', padding: '60px', color: 'var(--color-text-secondary)' }}>
        Loading profile visitors...
      </div>
    );
  }

  return (
    <div className="profile-visitors-page" style={{ width: '100%', margin: '0 auto' }}>
      <header className="member-page-heading" style={{ marginBottom: '20px', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
        <div>
          <h1 style={{ fontSize: '24px', fontWeight: 700, margin: '0 0 4px 0' }}>Profile Visitors</h1>
          <p style={{ margin: 0, color: 'var(--color-text-secondary)', fontSize: '14px' }}>
            See members who recently viewed your profile timeline ({totalVisitors} total visits).
          </p>
        </div>
        <Link className="member-button member-button--secondary" to="/member/profile">
          <ArrowLeft size={16} aria-hidden="true" />
          <span>Back to Profile</span>
        </Link>
      </header>

      {error && (
        <div style={{ padding: '12px 16px', background: '#fee2e2', color: '#b91c1c', borderRadius: '10px', marginBottom: '16px' }}>
          {error}
        </div>
      )}

      <section className="card member-card" style={{ padding: '24px', borderRadius: '16px', background: '#fff', border: '1px solid #e5e7eb' }}>
        {visitorsList.length === 0 ? (
          <div style={{ textAlign: 'center', padding: '60px 20px', color: '#9ca3af' }}>
            <div style={{ width: '60px', height: '60px', borderRadius: '50%', background: '#f3f4f6', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 16px auto' }}>
              <Eye size={30} color="#9ca3af" />
            </div>
            <h2 style={{ fontSize: '18px', fontWeight: 600, color: '#111827', margin: '0 0 6px 0' }}>No Visitors Yet</h2>
            <p style={{ margin: 0, fontSize: '14px' }}>
              Members who view your profile will be listed here.
            </p>
          </div>
        ) : (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
            {visitorsList.map((visit) => {
              const visitor = visit.visitor;
              if (!visitor) return null;

              return (
                <div
                  key={visit.id}
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    padding: '12px 16px',
                    borderRadius: '12px',
                    border: '1px solid #f0f0f0',
                    background: '#f9fafb',
                    gap: '12px',
                  }}
                >
                  <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                    <MemberAvatar member={visitor} size={46} />

                    <div>
                      <Link
                        to={`/member/people/${visitor.id}`}
                        style={{ fontWeight: 600, fontSize: '15px', color: '#111827', textDecoration: 'none', display: 'inline-flex', alignItems: 'center' }}
                      >
                        <span>{visitor.name}</span>
                        <VerifiedBadge member={visitor} size={14} />
                      </Link>
                      <span style={{ fontSize: '12px', color: '#6b7280', display: 'block' }}>
                        Viewed your profile • {formatRelativeTime(visit.visited_at || visit.created_at)}
                      </span>
                    </div>
                  </div>

                  <Link
                    to={`/member/people/${visitor.id}`}
                    className="member-button member-button--secondary"
                    style={{ fontSize: '12.5px', padding: '6px 14px' }}
                  >
                    <ExternalLink size={13} />
                    <span>View Profile</span>
                  </Link>
                </div>
              );
            })}
          </div>
        )}

        {/* Pagination */}
        {visitorsData && visitorsData.last_page > 1 && (
          <div style={{ display: 'flex', justifyContent: 'center', gap: '8px', marginTop: '24px' }}>
            <button
              type="button"
              className="member-button member-button--secondary"
              disabled={page <= 1}
              onClick={() => setPage((p) => Math.max(1, p - 1))}
            >
              Previous
            </button>
            <span style={{ display: 'flex', alignItems: 'center', padding: '0 12px', fontSize: '13px', color: '#6b7280' }}>
              Page {page} of {visitorsData.last_page}
            </span>
            <button
              type="button"
              className="member-button member-button--secondary"
              disabled={page >= visitorsData.last_page}
              onClick={() => setPage((p) => p + 1)}
            >
              Next
            </button>
          </div>
        )}
      </section>
    </div>
  );
}

export default ProfileVisitorsPage;
