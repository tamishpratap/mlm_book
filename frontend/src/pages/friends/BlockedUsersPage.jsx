import { useState, useEffect, useCallback } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { ShieldCheck, ChevronLeft, ChevronRight, RotateCw, UserPlus, UserMinus, Check, List, LayoutGrid } from 'lucide-react';
import friendApi from '../../api/friendApi';
import FeedRightSidebar from '../../components/posts/FeedRightSidebar';
import VerifiedBadge from '../../components/common/VerifiedBadge';
import MemberAvatar from '../../components/common/MemberAvatar';

function BlockedMemberItem({ member, onConnectSuccess, viewMode = 'list' }) {
  const [friendshipState, setFriendshipState] = useState(member?.friendship_state || 'none');
  const [friendshipId, setFriendshipId] = useState(member?.friendship_id || null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);

  const username = member?.user_id ? `@${member.user_id}` : '@member';

  if (!member) return null;

  const handleConnect = async (e) => {
    e?.preventDefault();
    e?.stopPropagation();
    if (isLoading) return;
    setIsLoading(true);
    setError(null);
    try {
      const response = await friendApi.sendFriendRequest(member.id);
      const newState = response.state || 'pending_sent';
      setFriendshipState(newState);
      if (response.friendship?.id) {
        setFriendshipId(response.friendship.id);
      }
      if (onConnectSuccess) {
        onConnectSuccess(member.id, newState, response.friendship);
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to send connection request.');
    } finally {
      setIsLoading(false);
    }
  };

  const handleCancel = async (e) => {
    e?.preventDefault();
    e?.stopPropagation();
    if (isLoading) return;
    setIsLoading(true);
    setError(null);
    try {
      const targetId = friendshipId || member?.friendship_id;
      if (targetId) {
        await friendApi.cancelFriendRequest(targetId);
      }
      setFriendshipState('none');
      setFriendshipId(null);
      if (onConnectSuccess) {
        onConnectSuccess(member.id, 'none', null);
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to cancel request.');
    } finally {
      setIsLoading(false);
    }
  };

  const handleAccept = async (e) => {
    e?.preventDefault();
    e?.stopPropagation();
    if (isLoading) return;
    setIsLoading(true);
    setError(null);
    try {
      const targetId = friendshipId || member?.friendship_id;
      if (targetId) {
        await friendApi.acceptFriendRequest(targetId);
      }
      setFriendshipState('friends');
      if (onConnectSuccess) {
        onConnectSuccess(member.id, 'friends', null);
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to accept request.');
    } finally {
      setIsLoading(false);
    }
  };

  if (viewMode === 'grid') {
    return (
      <div className="blocked-card card" data-blocked-card={member.id}>
        <div className="blocked-card__header">
          <Link
            to={`/member/people/${member.id}`}
            className="blocked-card__avatar"
            style={{ textDecoration: 'none' }}
            aria-label={`View ${member.name}'s profile`}
          >
            <MemberAvatar member={member} size={44} />
          </Link>

          <div className="blocked-card__info">
            <strong title={member.name}>
              <Link
                to={`/member/people/${member.id}`}
                className="blocked-card__name-link"
              >
                <span className="blocked-card__name-text">{member.name}</span>
                <VerifiedBadge member={member} size={14} />
              </Link>
            </strong>
            <small title={username}>
              <Link
                to={`/member/people/${member.id}`}
                className="blocked-card__username-link"
              >
                {username}
              </Link>
            </small>
            <div style={{ marginTop: '2px' }}>
              <span className="connection-request-row__no-mutual-text" style={{ color: '#e11d48', fontSize: '11.5px', fontWeight: 500 }}>
                Disconnected
              </span>
            </div>
          </div>
        </div>

        {error && (
          <div style={{ fontSize: '0.75rem', color: '#e11d48', marginTop: '6px', textAlign: 'center' }} role="alert">
            {error}
          </div>
        )}

        <div className="blocked-card__action">
          {friendshipState === 'none' && (
            <button
              className="member-button member-button--primary connection-request-btn connection-request-btn--connect blocked-card__unblock-btn"
              type="button"
              onClick={handleConnect}
              disabled={isLoading}
              aria-label={`Connect with ${member.name}`}
              style={{ background: 'var(--color-primary, #4f7df3)', color: '#ffffff', borderColor: 'var(--color-primary, #4f7df3)' }}
            >
              <UserPlus size={14} aria-hidden="true" />
              <span>{isLoading ? 'Connecting...' : 'Connect'}</span>
            </button>
          )}

          {friendshipState === 'pending_sent' && (
            <button
              className="member-button member-button--secondary connection-request-btn connection-request-btn--cancel blocked-card__unblock-btn"
              type="button"
              onClick={handleCancel}
              disabled={isLoading}
              aria-label={`Cancel request sent to ${member.name}`}
              title="Click to cancel connection request"
            >
              <UserMinus size={14} aria-hidden="true" />
              <span>{isLoading ? 'Cancelling...' : 'Request Sent'}</span>
            </button>
          )}

          {friendshipState === 'pending_received' && (
            <button
              className="member-button member-button--primary connection-request-btn connection-request-btn--accept blocked-card__unblock-btn"
              type="button"
              onClick={handleAccept}
              disabled={isLoading}
              aria-label={`Accept request from ${member.name}`}
            >
              <Check size={14} aria-hidden="true" />
              <span>{isLoading ? 'Accepting...' : 'Accept'}</span>
            </button>
          )}

          {friendshipState === 'friends' && (
            <button
              className="member-button member-button--secondary connection-request-btn blocked-card__unblock-btn"
              type="button"
              disabled
              aria-label={`Connected with ${member.name}`}
            >
              <Check size={14} aria-hidden="true" />
              <span>Connected</span>
            </button>
          )}
        </div>
      </div>
    );
  }

  // List mode: matches Phase 1 & 2 compact social list rows
  return (
    <div className="connection-request-row compact-member-row" data-blocked-row={member.id}>
      <div className="connection-request-row__main">
        <Link
          to={`/member/people/${member.id}`}
          className="connection-request-row__avatar-link"
          aria-label={`View ${member.name}'s profile`}
        >
          <MemberAvatar member={member} size={44} />
        </Link>

        <div className="connection-request-row__details">
          <div className="connection-request-row__name-wrap">
            <Link
              to={`/member/people/${member.id}`}
              className="connection-request-row__name-link"
              title={member.name}
            >
              <span className="connection-request-row__name">{member.name}</span>
              <VerifiedBadge member={member} size={14} />
            </Link>
          </div>

          <div className="connection-request-row__username-wrap">
            <Link
              to={`/member/people/${member.id}`}
              className="connection-request-row__username-link"
              title={username}
            >
              <span className="connection-request-row__username">{username}</span>
            </Link>
          </div>

          <div className="connection-request-row__meta">
            <span className="connection-request-row__no-mutual-text" style={{ color: '#e11d48' }}>
              Disconnected
            </span>
          </div>

          {error && (
            <div className="connection-request-row__error" role="alert" style={{ fontSize: '0.75rem', color: '#e11d48', marginTop: '4px' }}>
              {error}
            </div>
          )}
        </div>
      </div>

      <div className="connection-request-row__actions">
        {friendshipState === 'none' && (
          <button
            className="member-button member-button--primary connection-request-btn connection-request-btn--connect"
            type="button"
            onClick={handleConnect}
            disabled={isLoading}
            aria-label={`Connect with ${member.name}`}
          >
            <UserPlus size={14} aria-hidden="true" />
            <span>{isLoading ? 'Connecting...' : 'Connect'}</span>
          </button>
        )}

        {friendshipState === 'pending_sent' && (
          <button
            className="member-button member-button--secondary connection-request-btn connection-request-btn--cancel"
            type="button"
            onClick={handleCancel}
            disabled={isLoading}
            aria-label={`Cancel request sent to ${member.name}`}
            title="Click to cancel connection request"
          >
            <UserMinus size={14} aria-hidden="true" />
            <span>{isLoading ? 'Cancelling...' : 'Request Sent'}</span>
          </button>
        )}

        {friendshipState === 'pending_received' && (
          <button
            className="member-button member-button--primary connection-request-btn connection-request-btn--accept"
            type="button"
            onClick={handleAccept}
            disabled={isLoading}
            aria-label={`Accept request from ${member.name}`}
          >
            <Check size={14} aria-hidden="true" />
            <span>{isLoading ? 'Accepting...' : 'Accept'}</span>
          </button>
        )}

        {friendshipState === 'friends' && (
          <button
            className="member-button member-button--secondary connection-request-btn"
            type="button"
            disabled
            aria-label={`Connected with ${member.name}`}
          >
            <Check size={14} aria-hidden="true" />
            <span>Connected</span>
          </button>
        )}
      </div>
    </div>
  );
}

export function BlockedUsersPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const currentPage = parseInt(searchParams.get('page') || '1', 10);

  const [blockedMembers, setBlockedMembers] = useState([]);
  const [total, setTotal] = useState(0);
  const [lastPage, setLastPage] = useState(1);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState(null);
  const [viewMode, setViewMode] = useState('list'); // 'list' | 'grid'

  useEffect(() => {
    let isMounted = true;
    friendApi
      .getBlockedUsers(currentPage)
      .then((data) => {
        if (isMounted) {
          if (data && (data.disconnected_members || data.blocked_members)) {
            const listData = data.disconnected_members || data.blocked_members;
            const list = listData.data || (Array.isArray(listData) ? listData : []);
            setBlockedMembers(list);
            setTotal(data.total !== undefined ? data.total : (listData.total ?? list.length));
            setLastPage(listData.last_page || 1);
          } else {
            setBlockedMembers([]);
            setTotal(0);
          }
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load disconnected members.');
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
      .getBlockedUsers(currentPage)
      .then((data) => {
        if (data && (data.disconnected_members || data.blocked_members)) {
          const listData = data.disconnected_members || data.blocked_members;
          const list = listData.data || (Array.isArray(listData) ? listData : []);
          setBlockedMembers(list);
          setTotal(data.total !== undefined ? data.total : (listData.total ?? list.length));
          setLastPage(listData.last_page || 1);
        }
      })
      .catch(() => {})
      .finally(() => {
        setIsRefreshing(false);
      });
  }, [currentPage]);

  const handleConnectSuccess = (memberId, newState, friendship) => {
    setBlockedMembers((prev) =>
      prev.map((m) =>
        m.id === memberId
          ? {
              ...m,
              friendship_state: newState,
              friendship_id: friendship?.id || m.friendship_id,
            }
          : m
      )
    );
  };

  const handlePageChange = (newPage) => {
    if (newPage < 1 || newPage > lastPage) return;
    setSearchParams({ page: newPage.toString() });
  };

  return (
    <>
      <main className="member-main feed" id="blocked-users-page-main">
        <div className="blocked-users-page">
          <section className="card member-card blocked-users-card">
            <header className="blocked-users-header">
              <div className="blocked-users-header__info">
                <h1 className="blocked-users-header__title">Disconnections</h1>
                <p className="blocked-users-header__desc">
                  {total} {total === 1 ? 'member' : 'members'} disconnected from interacting with your profile and content.
                </p>
              </div>
              <div className="blocked-users-header__action" style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
                <div className="connection-quick-filter-pills" role="tablist" style={{ margin: 0, padding: 0 }}>
                  <button
                    type="button"
                    className={`connection-quick-btn ${viewMode === 'list' ? 'is-active' : ''}`}
                    onClick={() => setViewMode('list')}
                    aria-label="List View"
                    title="Compact List View"
                    style={{ padding: '6px 10px' }}
                  >
                    <List size={15} aria-hidden="true" />
                  </button>
                  <button
                    type="button"
                    className={`connection-quick-btn ${viewMode === 'grid' ? 'is-active' : ''}`}
                    onClick={() => setViewMode('grid')}
                    aria-label="4-Column Grid View"
                    title="4-Column Card Grid View"
                    style={{ padding: '6px 10px' }}
                  >
                    <LayoutGrid size={15} aria-hidden="true" />
                  </button>
                </div>
                <button
                  className="member-button member-button--secondary blocked-users-refresh-btn"
                  type="button"
                  aria-label="Refresh Disconnections"
                  title="Refresh Disconnections"
                  onClick={handleRefresh}
                  disabled={isRefreshing}
                >
                  <RotateCw size={14} className={isRefreshing ? 'blocked-spin' : ''} aria-hidden="true" />
                  <span>Refresh</span>
                </button>
              </div>
            </header>

            {isLoading ? (
              <div style={{ padding: '32px', textAlign: 'center', color: 'var(--color-text-secondary)' }}>
                Loading disconnections...
              </div>
            ) : error ? (
              <div className="notification-empty" role="alert">
                <h2>Error Loading Disconnections</h2>
                <p>{error}</p>
                <button
                  className="member-button member-button--primary"
                  type="button"
                  onClick={handleRefresh}
                  style={{ marginTop: '12px' }}
                >
                  Try Again
                </button>
              </div>
            ) : blockedMembers.length === 0 ? (
              <div className="notification-empty">
                <div className="notification-empty__icon">
                  <ShieldCheck size={40} aria-hidden="true" />
                </div>
                <h2>No Disconnections</h2>
                <p>You haven't disconnected any members.</p>
              </div>
            ) : viewMode === 'grid' ? (
              <div className="blocked-users-grid">
                {blockedMembers.map((member) => (
                  <BlockedMemberItem
                    key={member.id}
                    member={member}
                    onConnectSuccess={handleConnectSuccess}
                    viewMode="grid"
                  />
                ))}
              </div>
            ) : (
              <div className="connection-requests-list" style={{ marginTop: '16px', borderTop: '1px solid var(--color-border-soft, #edf1f7)' }}>
                {blockedMembers.map((member) => (
                  <BlockedMemberItem
                    key={member.id}
                    member={member}
                    onConnectSuccess={handleConnectSuccess}
                    viewMode="list"
                  />
                ))}
              </div>
            )}
          </section>

          {lastPage > 1 && (
            <nav
              className="pagination"
              role="navigation"
              aria-label="Pagination"
              style={{ marginTop: '24px', display: 'flex', justifyContent: 'center', gap: '8px' }}
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
        </div>
      </main>

      <FeedRightSidebar />
    </>
  );
}

export default BlockedUsersPage;
