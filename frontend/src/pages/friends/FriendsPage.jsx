import { useState, useEffect, useCallback } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { UserRoundCheck, UsersRound, ChevronLeft, ChevronRight, RotateCw } from 'lucide-react';
import friendApi from '../../api/friendApi';
import CompactMemberRow from '../../components/friends/CompactMemberRow';
import FeedRightSidebar from '../../components/posts/FeedRightSidebar';

export function FriendsPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const currentPage = parseInt(searchParams.get('page') || '1', 10);

  const [friends, setFriends] = useState([]);
  const [total, setTotal] = useState(0);
  const [lastPage, setLastPage] = useState(1);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    let isMounted = true;
    friendApi
      .getFriends(currentPage)
      .then((data) => {
        if (isMounted) {
          if (data && data.friends) {
            const list = data.friends.data || [];
            setFriends(list);
            setTotal(data.total !== undefined ? data.total : data.friends.total || list.length);
            setLastPage(data.friends.last_page || 1);
          } else {
            setFriends([]);
            setTotal(0);
          }
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load connections.');
        }
      })
      .finally(() => {
        if (isMounted) {
          setIsLoading(false);
        }
      });

    return () => {
      isMounted = false;
    };
  }, [currentPage]);

  const handleRefresh = useCallback(() => {
    setIsRefreshing(true);
    friendApi
      .getFriends(currentPage)
      .then((data) => {
        if (data && data.friends) {
          const list = data.friends.data || [];
          setFriends(list);
          setTotal(data.total !== undefined ? data.total : data.friends.total || list.length);
          setLastPage(data.friends.last_page || 1);
        }
      })
      .catch(() => {})
      .finally(() => {
        setIsRefreshing(false);
      });
  }, [currentPage]);

  const handlePageChange = (newPage) => {
    if (newPage < 1 || newPage > lastPage) return;
    setSearchParams({ page: newPage.toString() });
  };

  return (
    <>
      <main className="member-main feed" id="friends-page-main">
        <div className="friends-page">
          <header className="member-page-heading friend-page-heading">
            <div>
              <span className="friend-page-eyebrow">Your network</span>
              <h1>My Connections</h1>
              <p>
                {total} accepted connection{total !== 1 ? 's' : ''}
              </p>
            </div>
            <div className="friend-page-heading__actions">
              <button
                className="member-button member-button--secondary"
                type="button"
                aria-label="Refresh Connections"
                onClick={handleRefresh}
                disabled={isRefreshing}
                style={{ padding: '8px 12px' }}
              >
                <RotateCw size={14} className={isRefreshing ? 'fa-spin' : ''} aria-hidden="true" />
              </button>
              <Link
                className="member-button member-button--secondary"
                to="/member/friend-requests"
              >
                <UserRoundCheck size={16} aria-hidden="true" />
                <span>Connection Requests</span>
              </Link>
            </div>
          </header>

          {isLoading ? (
            <div style={{ padding: '32px', textAlign: 'center', color: 'var(--color-text-secondary)' }}>
              Loading connections...
            </div>
          ) : error ? (
            <section className="member-card friend-empty" role="alert">
              <h2>Error Loading Connections</h2>
              <p>{error}</p>
              <button
                className="member-button member-button--primary"
                type="button"
                onClick={handleRefresh}
              >
                Try Again
              </button>
            </section>
          ) : friends.length === 0 ? (
            <section className="member-card friend-empty">
              <span>
                <UsersRound size={48} aria-hidden="true" />
              </span>
              <h2>No connections yet</h2>
              <p>Search for Members and send a connection request to start building your network.</p>
              <Link
                className="member-button member-button--primary"
                to="/member/people/suggestions"
              >
                Find People
              </Link>
            </section>
          ) : (
            <>
              <section className="card connection-requests-card" style={{ padding: '0', overflow: 'hidden' }}>
                <div className="connection-requests-list">
                  {friends.map((friend) => (
                    <CompactMemberRow
                      key={friend.id}
                      member={friend}
                      mode="connection"
                      initialFriendshipState="friends"
                    />
                  ))}
                </div>
              </section>

              {lastPage > 1 && (
                <nav
                  className="pagination"
                  role="navigation"
                  aria-label="Pagination"
                  style={{ marginTop: '24px', display: 'flex', justifyContent: 'center', gap: '8px', flexWrap: 'wrap' }}
                >
                  <button
                    className="member-button member-button--secondary"
                    type="button"
                    onClick={() => handlePageChange(currentPage - 1)}
                    disabled={currentPage <= 1}
                    aria-label="Previous Page"
                  >
                    <ChevronLeft size={16} />
                  </button>

                  {Array.from({ length: lastPage }, (_, i) => i + 1).map((pageNum) => (
                    <button
                      key={pageNum}
                      className={`member-button ${pageNum === currentPage ? 'member-button--primary' : 'member-button--secondary'}`}
                      type="button"
                      onClick={() => handlePageChange(pageNum)}
                      style={{ minWidth: '36px' }}
                    >
                      {pageNum}
                    </button>
                  ))}

                  <button
                    className="member-button member-button--secondary"
                    type="button"
                    onClick={() => handlePageChange(currentPage + 1)}
                    disabled={currentPage >= lastPage}
                    aria-label="Next Page"
                  >
                    <ChevronRight size={16} />
                  </button>
                </nav>
              )}
            </>
          )}
        </div>
      </main>

      <FeedRightSidebar showMessaging={false} />
    </>
  );
}

export default FriendsPage;
