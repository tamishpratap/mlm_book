import { useState, useEffect, useCallback } from 'react';
import { useParams, Link } from 'react-router-dom';
import {
  Bell,
  ArrowLeft,
  CheckCheck,
  Trash2,
  MessageSquare,
  BadgeCheck,
  Shield,
  Star,
  Users,
  Check,
  AlertCircle,
} from 'lucide-react';
import businessApi from '../../api/businessApi';

function formatRelativeTime(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  const now = new Date();
  const diffInSec = Math.floor((now - date) / 1000);

  if (diffInSec < 60) return 'Just now';
  if (diffInSec < 3600) return `${Math.floor(diffInSec / 60)}m ago`;
  if (diffInSec < 86400) return `${Math.floor(diffInSec / 3600)}h ago`;
  if (diffInSec < 604800) return `${Math.floor(diffInSec / 86400)}d ago`;
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function getNotificationIcon(type) {
  switch (type) {
    case 'new_message':
      return <MessageSquare size={18} color="#4f7df3" />;
    case 'new_verification_request':
      return <BadgeCheck size={18} color="#20c875" />;
    case 'new_review':
      return <Star size={18} color="#f7b940" />;
    case 'new_follower':
    case 'team_invite':
      return <Users size={18} color="#8a2be2" />;
    default:
      return <Shield size={18} color="#4f7df3" />;
  }
}

function getNotificationLink(slug, notification) {
  const type = notification.type;

  if (type === 'new_message') {
    return `/member/business-pages/${slug}/inbox`;
  }
  if (type === 'new_verification_request') {
    return `/member/business-pages/${slug}/verification`;
  }
  if (type === 'new_review') {
    return `/member/business-pages/${slug}?tab=reviews`;
  }
  if (type === 'team_invite') {
    return `/member/business-pages/${slug}/team`;
  }
  if (type === 'new_follower') {
    return `/member/business-pages/${slug}?tab=followers`;
  }

  return `/member/business-pages/${slug}`;
}

export function BusinessNotificationsPage() {
  const { slug } = useParams();

  const [notifications, setNotifications] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [filter, setFilter] = useState('all');
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [isProcessing, setIsProcessing] = useState(false);

  const fetchNotifications = useCallback(() => {
    if (!slug) return Promise.resolve(null);
    return businessApi.getInboxNotifications(slug, { filter });
  }, [slug, filter]);

  useEffect(() => {
    let isMounted = true;

    fetchNotifications()
      .then((res) => {
        if (isMounted && res && res.success) {
          setNotifications(res.notifications || []);
          setUnreadCount(res.unread_count ?? 0);
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load business notifications.');
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [fetchNotifications]);

  // Polling every 30 seconds
  useEffect(() => {
    if (!slug) return;

    const interval = setInterval(() => {
      fetchNotifications().then((res) => {
        if (res && res.success) {
          setNotifications(res.notifications || []);
          setUnreadCount(res.unread_count ?? 0);
        }
      });
    }, 30000);

    return () => clearInterval(interval);
  }, [slug, fetchNotifications]);

  const handleMarkAllRead = async () => {
    if (isProcessing) return;
    setIsProcessing(true);
    try {
      await businessApi.markInboxNotificationsRead(slug);
      setNotifications((prev) => prev.map((n) => ({ ...n, is_read: true })));
      setUnreadCount(0);
    } catch {
      // Ignore
    } finally {
      setIsProcessing(false);
    }
  };

  const handleMarkOneRead = async (notificationId) => {
    try {
      await businessApi.markSingleNotificationRead(slug, notificationId);
      setNotifications((prev) =>
        prev.map((n) => (n.id === notificationId ? { ...n, is_read: true } : n))
      );
      setUnreadCount((prev) => Math.max(0, prev - 1));
    } catch {
      // Ignore
    }
  };

  const handleDeleteOne = async (notificationId) => {
    try {
      await businessApi.deleteNotification(slug, notificationId);
      setNotifications((prev) => prev.filter((n) => n.id !== notificationId));
      fetchNotifications().then((res) => {
        if (res && res.success) {
          setUnreadCount(res.unread_count ?? 0);
        }
      });
    } catch {
      // Ignore
    }
  };

  const handleClearAll = async () => {
    if (!window.confirm('Are you sure you want to clear all business notifications?')) return;
    setIsProcessing(true);
    try {
      await businessApi.clearAllNotifications(slug);
      setNotifications([]);
      setUnreadCount(0);
    } catch {
      // Ignore
    } finally {
      setIsProcessing(false);
    }
  };

  if (isLoading) {
    return (
      <div style={{ textAlign: 'center', padding: '80px', color: 'var(--color-text-secondary)' }}>
        Loading business notifications...
      </div>
    );
  }

  if (error) {
    return (
      <div className="card" style={{ maxWidth: '640px', margin: '40px auto', padding: '32px', textAlign: 'center' }}>
        <AlertCircle size={40} color="#dc2626" style={{ margin: '0 auto 12px auto' }} />
        <h2 style={{ fontSize: '18px', color: '#111827', marginBottom: '8px' }}>Access Restricted</h2>
        <p style={{ color: '#687386', marginBottom: '20px', fontSize: '14px' }}>{error}</p>
        <Link to={`/member/business-pages/${slug}`} className="member-button member-button--secondary">
          <ArrowLeft size={16} />
          <span>Back to Business Page</span>
        </Link>
      </div>
    );
  }

  return (
    <div className="biz-page" style={{ maxWidth: '860px', margin: '16px auto', padding: '0 16px' }}>
      {/* Header */}
      <header className="biz-header" style={{ marginBottom: '20px', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
        <div className="biz-header__info">
          <h1 style={{ display: 'flex', alignItems: 'center', gap: '8px', fontSize: '22px', fontWeight: 800, margin: '0 0 4px 0' }}>
            <Bell size={24} color="#4f7df3" />
            <span>Business Notification Center</span>
            {unreadCount > 0 && (
              <span
                className="biz-badge biz-badge--category"
                style={{ background: '#e53e3e', color: '#fff', verticalAlign: 'middle', marginLeft: '6px' }}
              >
                {unreadCount} Unread
              </span>
            )}
          </h1>
          <p style={{ margin: 0, color: '#687386', fontSize: '13.5px' }}>
            Activity, customer inquiries, and verification notifications for your business.
          </p>
        </div>

        <div className="biz-header__actions">
          {notifications.length > 0 && (
            <>
              {unreadCount > 0 && (
                <button
                  type="button"
                  className="member-button member-button--secondary"
                  onClick={handleMarkAllRead}
                  disabled={isProcessing}
                >
                  <CheckCheck size={15} />
                  <span>Mark All Read</span>
                </button>
              )}
              <button
                type="button"
                className="member-button member-button--secondary"
                onClick={handleClearAll}
                disabled={isProcessing}
              >
                <Trash2 size={15} />
                <span>Clear All</span>
              </button>
            </>
          )}

          <Link to={`/member/business-pages/${slug}`} className="member-button member-button--primary">
            <ArrowLeft size={15} />
            <span>Back to Profile</span>
          </Link>
        </div>
      </header>

      {/* Filter Tabs */}
      <div
        className="biz-info-card"
        style={{
          padding: '12px 18px',
          marginBottom: '16px',
          display: 'flex',
          gap: '8px',
          alignItems: 'center',
          background: '#fff',
          borderRadius: '12px',
          border: '1px solid #e5e7eb',
        }}
      >
        <button
          type="button"
          onClick={() => setFilter('all')}
          style={{
            padding: '6px 14px',
            borderRadius: '8px',
            border: 'none',
            background: filter === 'all' ? '#edf3ff' : 'transparent',
            color: filter === 'all' ? '#4f7df3' : '#687386',
            fontWeight: filter === 'all' ? 700 : 500,
            fontSize: '13px',
            cursor: 'pointer',
          }}
        >
          All Activity
        </button>
        <button
          type="button"
          onClick={() => setFilter('unread')}
          style={{
            padding: '6px 14px',
            borderRadius: '8px',
            border: 'none',
            background: filter === 'unread' ? '#edf3ff' : 'transparent',
            color: filter === 'unread' ? '#4f7df3' : '#687386',
            fontWeight: filter === 'unread' ? 700 : 500,
            fontSize: '13px',
            cursor: 'pointer',
          }}
        >
          Unread ({unreadCount})
        </button>
      </div>

      {/* Notifications List Card */}
      <div className="biz-info-card" style={{ padding: '20px', background: '#fff', borderRadius: '16px', border: '1px solid #e5e7eb' }}>
        {notifications.length === 0 ? (
          <div style={{ textAlign: 'center', padding: '48px 0', color: '#98a2b3' }}>
            <Bell size={40} style={{ margin: '0 auto 12px auto', opacity: 0.4 }} />
            <h3 style={{ fontSize: '16px', color: '#1d2738', margin: '0 0 6px 0' }}>No Notifications Found</h3>
            <p style={{ fontSize: '13.5px', margin: 0 }}>
              {filter === 'unread'
                ? 'All business notifications are currently marked as read.'
                : 'Your business page has no recorded notification activity yet.'}
            </p>
          </div>
        ) : (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
            {notifications.map((n) => {
              const targetLink = getNotificationLink(slug, n);
              return (
                <div
                  key={n.id}
                  style={{
                    padding: '14px 18px',
                    borderRadius: '14px',
                    background: n.is_read ? '#fff' : '#edf3ff',
                    border: '1px solid #e7ecf4',
                    display: 'flex',
                    alignItems: 'flex-start',
                    justifyContent: 'space-between',
                    gap: '14px',
                  }}
                >
                  <div style={{ display: 'flex', gap: '12px', minWidth: 0, flex: 1 }}>
                    <div
                      style={{
                        width: '38px',
                        height: '38px',
                        borderRadius: '50%',
                        background: '#fff',
                        border: '1px solid #e7ecf4',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        flexShrink: 0,
                      }}
                    >
                      {getNotificationIcon(n.type)}
                    </div>

                    <div style={{ minWidth: 0, flex: 1 }}>
                      <Link
                        to={targetLink}
                        onClick={() => {
                          if (!n.is_read) handleMarkOneRead(n.id);
                        }}
                        style={{ textDecoration: 'none', color: 'inherit' }}
                      >
                        <strong
                          style={{
                            fontSize: '14px',
                            color: '#1d2738',
                            display: 'block',
                            lineHeight: 1.3,
                          }}
                        >
                          {n.title}
                        </strong>
                        {n.data?.message_snippet && (
                          <p
                            style={{
                              fontSize: '13px',
                              color: '#687386',
                              margin: '4px 0 0 0',
                            }}
                          >
                            {n.data.message_snippet}
                          </p>
                        )}
                      </Link>
                      <span style={{ fontSize: '11.5px', color: '#98a2b3', display: 'block', marginTop: '6px' }}>
                        {formatRelativeTime(n.created_at)}
                      </span>
                    </div>
                  </div>

                  <div style={{ display: 'flex', alignItems: 'center', gap: '6px', flexShrink: 0 }}>
                    {!n.is_read && (
                      <button
                        type="button"
                        onClick={() => handleMarkOneRead(n.id)}
                        className="mini-button"
                        style={{ color: '#4f7df3', fontSize: '12px', padding: '4px 8px' }}
                        title="Mark as read"
                      >
                        <Check size={14} />
                        <span>Read</span>
                      </button>
                    )}
                    <button
                      type="button"
                      onClick={() => handleDeleteOne(n.id)}
                      className="mini-button"
                      style={{ color: '#98a2b3', fontSize: '12px', padding: '4px 8px' }}
                      title="Delete notification"
                    >
                      <Trash2 size={13} />
                    </button>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}

export default BusinessNotificationsPage;
