import { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import {
  Bell,
  CheckCheck,
  Check,
  BellOff,
  RotateCw,
  Megaphone,
  ShieldAlert,
  Users,
  MessageSquare,
  ArrowLeft,
} from 'lucide-react';
import communityApi from '../../api/communityApi';

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

function getIconForAction(action) {
  switch (action) {
    case 'announcement':
      return <Megaphone size={11} aria-hidden="true" />;
    case 'moderation_action':
      return <ShieldAlert size={11} aria-hidden="true" />;
    case 'member_joined':
    case 'invitation':
      return <Users size={11} aria-hidden="true" />;
    case 'comment':
    case 'reply':
      return <MessageSquare size={11} aria-hidden="true" />;
    default:
      return <Bell size={11} aria-hidden="true" />;
  }
}

export function CommunityNotificationsPage() {
  const [category, setCategory] = useState('all');
  const [page, setPage] = useState(1);
  const [data, setData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState(null);
  const [feedback, setFeedback] = useState({ type: '', message: '' });

  const fetchNotifications = useCallback(
    (cat, p) => {
      return communityApi.getCommunityNotifications(cat, p);
    },
    []
  );

  useEffect(() => {
    let isMounted = true;

    fetchNotifications(category, page)
      .then((res) => {
        if (isMounted && res) {
          setData(res);
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load community notifications.');
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [fetchNotifications, category, page]);

  const handleRefresh = () => {
    setIsRefreshing(true);
    fetchNotifications(category, page)
      .then((res) => {
        if (res) setData(res);
      })
      .catch(() => {})
      .finally(() => setIsRefreshing(false));
  };

  const handleMarkOneRead = async (notificationId) => {
    try {
      const res = await communityApi.markCommunityNotificationRead(notificationId);
      setData((prev) => {
        if (!prev) return prev;
        const updatedList = (prev.notifications?.data || []).map((n) =>
          n.id === notificationId ? { ...n, read_at: new Date().toISOString() } : n
        );
        return {
          ...prev,
          notifications: {
            ...prev.notifications,
            data: updatedList,
          },
          unread_count: res.unread_count ?? Math.max(0, (prev.unread_count || 1) - 1),
        };
      });
      setFeedback({ type: 'success', message: 'Notification marked as read.' });
      setTimeout(() => setFeedback({ type: '', message: '' }), 3000);
    } catch {
      setFeedback({ type: 'error', message: 'Failed to mark notification as read.' });
    }
  };

  const handleMarkAllRead = async () => {
    try {
      await communityApi.markAllCommunityNotificationsRead();
      setData((prev) => {
        if (!prev) return prev;
        const updatedList = (prev.notifications?.data || []).map((n) => ({
          ...n,
          read_at: new Date().toISOString(),
        }));
        return {
          ...prev,
          notifications: {
            ...prev.notifications,
            data: updatedList,
          },
          unread_count: 0,
        };
      });
      setFeedback({ type: 'success', message: 'All notifications marked as read.' });
      setTimeout(() => setFeedback({ type: '', message: '' }), 3000);
    } catch {
      setFeedback({ type: 'error', message: 'Failed to mark all notifications as read.' });
    }
  };

  const notificationsList = data?.notifications?.data || [];
  const unreadCount = data?.unread_count || 0;
  const pagination = data?.notifications || {};

  return (
    <div className="community-notifications-page">
      {/* Back button */}
      <div className="community-back-nav">
        <Link to="/member/community" className="community-back-link">
          <ArrowLeft size={16} aria-hidden="true" />
          <span>Back to Communities</span>
        </Link>
      </div>

      {feedback.message && (
        <div
          style={{
            padding: '12px 16px',
            borderRadius: '12px',
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

      {/* Main Notification Container */}
      <div className="community-notifications-card">
        {/* Header */}
        <div className="community-notifications-header">
          <div className="community-notifications-header__info">
            <h1>
              <Bell size={22} className="community-notifications-header__icon" aria-hidden="true" />
              <span>Community Notification Center</span>
            </h1>
            <p>
              Stay updated with community events, announcements, and moderation alerts.
            </p>
          </div>

          <div className="community-notifications-header__actions">
            {unreadCount > 0 && (
              <button
                type="button"
                className="member-button member-button--secondary community-notifications-mark-read-btn"
                onClick={handleMarkAllRead}
              >
                <CheckCheck size={14} aria-hidden="true" />
                <span>Mark All as Read</span>
              </button>
            )}

            <button
              type="button"
              className="member-button member-button--secondary community-notifications-refresh-btn"
              onClick={handleRefresh}
              disabled={isRefreshing}
              title="Refresh notifications"
              aria-label="Refresh notifications"
            >
              <RotateCw size={14} className={isRefreshing ? 'fa-spin' : ''} aria-hidden="true" />
            </button>
          </div>
        </div>

        {/* Category Tabs */}
        <div className="community-notifications-tabs" role="tablist" aria-label="Notification Categories">
          {[
            { id: 'all', label: 'All' },
            { id: 'unread', label: 'Unread', badge: unreadCount > 0 ? unreadCount : null },
            { id: 'announcements', label: 'Announcements' },
            { id: 'moderation', label: 'Moderation' },
          ].map((tab) => (
            <button
              key={tab.id}
              type="button"
              role="tab"
              aria-selected={category === tab.id}
              className={`community-notifications-tab ${category === tab.id ? 'is-active' : ''}`}
              onClick={() => {
                setCategory(tab.id);
                setPage(1);
              }}
            >
              <span>{tab.label}</span>
              {tab.badge && (
                <span className="community-notifications-tab__badge">
                  {tab.badge}
                </span>
              )}
            </button>
          ))}
        </div>

        {/* Notifications Body Content */}
        <div className="community-notifications-body">
          {isLoading ? (
            <div className="community-notifications-loading">
              <RotateCw size={24} className="fa-spin" style={{ opacity: 0.7, marginBottom: '10px' }} aria-hidden="true" />
              <p>Loading community notifications...</p>
            </div>
          ) : error ? (
            <div className="community-notifications-error" role="alert">
              {error}
            </div>
          ) : notificationsList.length === 0 ? (
            <div className="community-notifications-empty">
              <div className="community-notifications-empty__icon">
                <BellOff size={24} aria-hidden="true" />
              </div>
              <h3>No Community Notifications</h3>
              <p>You are all caught up with your communities.</p>
            </div>
          ) : (
            <div className="community-notifications-list">
              {notificationsList.map((notification) => {
                const nData = notification.data || {};
                const isUnread = !notification.read_at;
                const targetUrl = nData.url
                  ? nData.url.replace(/^.*\/member\//, '/member/')
                  : nData.community_slug
                  ? `/member/community/${nData.community_slug}`
                  : '/member/community';

                return (
                  <div
                    key={notification.id}
                    className={`community-notification-item ${isUnread ? 'community-notification-item--unread' : ''}`}
                  >
                    <div style={{ position: 'relative', flexShrink: 0 }}>
                      {nData.actor_photo ? (
                        <img
                          src={nData.actor_photo.startsWith('http') ? nData.actor_photo : `/${nData.actor_photo}`}
                          alt={nData.actor_name || 'Member'}
                          style={{ width: '42px', height: '42px', borderRadius: '50%', objectFit: 'cover' }}
                        />
                      ) : (
                        <span
                          className="avatar post-avatar-initials"
                          style={{
                            width: '42px',
                            height: '42px',
                            fontSize: '14px',
                            display: 'inline-flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            borderRadius: '50%',
                            background: '#e0e7ff',
                            color: '#3730a3',
                            fontWeight: 700,
                          }}
                        >
                          {getInitials(nData.actor_name)}
                        </span>
                      )}

                      <div
                        style={{
                          position: 'absolute',
                          bottom: '-2px',
                          right: '-2px',
                          width: '20px',
                          height: '20px',
                          borderRadius: '50%',
                          background: 'var(--color-primary, #4f7df3)',
                          color: '#ffffff',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                        }}
                      >
                        {getIconForAction(nData.action)}
                      </div>
                    </div>

                    <div style={{ flex: 1, minWidth: 0 }}>
                      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '6px' }}>
                        <strong style={{ fontSize: '14px', color: 'var(--color-text-main, #0f172a)' }}>
                          <Link to={targetUrl} style={{ color: 'inherit', textDecoration: 'none' }}>
                            {nData.title || 'Community Notification'}
                          </Link>
                        </strong>
                        <span style={{ fontSize: '11.5px', color: 'var(--color-text-secondary, #64748b)' }}>
                          {formatRelativeTime(notification.created_at)}
                        </span>
                      </div>

                      <p style={{ fontSize: '13px', color: 'var(--color-text-secondary, #687386)', margin: '4px 0 0 0', lineHeight: 1.5 }}>
                        {nData.message || ''}
                      </p>

                      {nData.community_name && (
                        <span className="community-badge" style={{ fontSize: '10.5px', marginTop: '6px', display: 'inline-block' }}>
                          {nData.community_name}
                        </span>
                      )}
                    </div>

                    {isUnread && (
                      <button
                        type="button"
                        className="member-button member-button--secondary"
                        style={{ padding: '4px 8px', fontSize: '11px', flexShrink: 0 }}
                        title="Mark as read"
                        onClick={() => handleMarkOneRead(notification.id)}
                      >
                        <Check size={13} aria-hidden="true" />
                      </button>
                    )}
                  </div>
                );
              })}
            </div>
          )}

          {/* Pagination */}
          {pagination.last_page > 1 && (
            <div className="community-notifications-pagination">
              <button
                type="button"
                className="member-button member-button--secondary"
                disabled={page <= 1}
                onClick={() => setPage((p) => Math.max(1, p - 1))}
              >
                Previous
              </button>
              <span className="community-notifications-pagination__info">
                Page {pagination.current_page} of {pagination.last_page}
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
      </div>
    </div>
  );
}

export default CommunityNotificationsPage;
