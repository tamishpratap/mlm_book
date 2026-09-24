import { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import {
  Bell,
  X,
  CheckCheck,
  Trash2,
  MessageSquare,
  BadgeCheck,
  Shield,
  Star,
  Users,
  Check,
} from 'lucide-react';
import businessApi from '../../api/businessApi';
import { ModalPortal } from '../common/ModalPortal';

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
      return <MessageSquare size={16} color="#4f7df3" />;
    case 'new_verification_request':
      return <BadgeCheck size={16} color="#20c875" />;
    case 'new_review':
      return <Star size={16} color="#f7b940" />;
    case 'new_follower':
    case 'team_invite':
      return <Users size={16} color="#8a2be2" />;
    default:
      return <Shield size={16} color="#4f7df3" />;
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

export function BusinessNotificationModal({ slug, isOpen, onClose, onNotificationsUpdated }) {
  const [notifications, setNotifications] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [filter, setFilter] = useState('all');
  const [isLoading, setIsLoading] = useState(false);
  const [isProcessing, setIsProcessing] = useState(false);

  const fetchNotifications = useCallback(() => {
    if (!slug || !isOpen) return Promise.resolve(null);
    return businessApi.getInboxNotifications(slug, { filter });
  }, [slug, isOpen, filter]);

  useEffect(() => {
    let isMounted = true;
    if (!isOpen) return;

    fetchNotifications()
      .then((res) => {
        if (isMounted && res && res.success) {
          setNotifications(res.notifications || []);
          setUnreadCount(res.unread_count ?? 0);
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [isOpen, fetchNotifications]);

  // Polling every 25 seconds when modal is open
  useEffect(() => {
    if (!isOpen || !slug) return;

    const interval = setInterval(() => {
      fetchNotifications().then((res) => {
        if (res && res.success) {
          setNotifications(res.notifications || []);
          setUnreadCount(res.unread_count ?? 0);
          if (onNotificationsUpdated) onNotificationsUpdated(res.unread_count ?? 0);
        }
      });
    }, 25000);

    return () => clearInterval(interval);
  }, [isOpen, slug, fetchNotifications, onNotificationsUpdated]);

  const handleMarkAllRead = async () => {
    if (isProcessing) return;
    setIsProcessing(true);
    try {
      await businessApi.markInboxNotificationsRead(slug);
      setNotifications((prev) => prev.map((n) => ({ ...n, is_read: true })));
      setUnreadCount(0);
      if (onNotificationsUpdated) onNotificationsUpdated(0);
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
      if (onNotificationsUpdated) onNotificationsUpdated(Math.max(0, unreadCount - 1));
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
          if (onNotificationsUpdated) onNotificationsUpdated(res.unread_count ?? 0);
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
      if (onNotificationsUpdated) onNotificationsUpdated(0);
    } catch {
      // Ignore
    } finally {
      setIsProcessing(false);
    }
  };

  if (!isOpen) return null;

  return (
    <ModalPortal isOpen={isOpen} onClose={onClose}>
      <div
        className="card biz-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="biz-notifications-title"
        style={{
          background: '#fff',
          borderRadius: '20px',
          maxWidth: '520px',
          width: '100%',
          boxShadow: '0 25px 50px -12px rgba(15, 23, 42, 0.35)',
          overflow: 'hidden',
          maxHeight: 'min(85vh, 720px)',
          display: 'flex',
          flexDirection: 'column',
        }}
      >
        {/* Modal Header */}
        <div
          style={{
            padding: '18px 24px',
            borderBottom: '1px solid #e7ecf4',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
            <Bell size={20} color="#4f7df3" />
            <h3 style={{ fontSize: '17px', fontWeight: 700, margin: 0, color: '#1d2738' }}>
              Business Notifications
            </h3>
            {unreadCount > 0 && (
              <span
                style={{
                  background: '#e53e3e',
                  color: '#fff',
                  borderRadius: '12px',
                  padding: '2px 8px',
                  fontSize: '11px',
                  fontWeight: 700,
                }}
              >
                {unreadCount} New
              </span>
            )}
          </div>

          <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
            {notifications.length > 0 && (
              <>
                {unreadCount > 0 && (
                  <button
                    type="button"
                    onClick={handleMarkAllRead}
                    disabled={isProcessing}
                    style={{
                      background: 'transparent',
                      border: 'none',
                      color: '#4f7df3',
                      fontSize: '12.5px',
                      fontWeight: 600,
                      cursor: 'pointer',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '4px',
                    }}
                    title="Mark all as read"
                  >
                    <CheckCheck size={14} />
                    <span>Mark all read</span>
                  </button>
                )}
                <button
                  type="button"
                  onClick={handleClearAll}
                  disabled={isProcessing}
                  style={{
                    background: 'transparent',
                    border: 'none',
                    color: '#98a2b3',
                    fontSize: '12.5px',
                    cursor: 'pointer',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '4px',
                  }}
                  title="Clear all notifications"
                >
                  <Trash2 size={13} />
                  <span>Clear</span>
                </button>
              </>
            )}
            <button
              type="button"
              className="icon-button"
              onClick={onClose}
              style={{ background: 'transparent', border: 'none', cursor: 'pointer', marginLeft: '4px' }}
            >
              <X size={18} />
            </button>
          </div>
        </div>

        {/* Filter Bar */}
        <div
          style={{
            display: 'flex',
            gap: '8px',
            padding: '10px 24px',
            background: '#fafbfc',
            borderBottom: '1px solid #e7ecf4',
          }}
        >
          <button
            type="button"
            onClick={() => setFilter('all')}
            style={{
              padding: '4px 12px',
              borderRadius: '8px',
              border: 'none',
              background: filter === 'all' ? '#edf3ff' : 'transparent',
              color: filter === 'all' ? '#4f7df3' : '#687386',
              fontWeight: filter === 'all' ? 700 : 500,
              fontSize: '12.5px',
              cursor: 'pointer',
            }}
          >
            All
          </button>
          <button
            type="button"
            onClick={() => setFilter('unread')}
            style={{
              padding: '4px 12px',
              borderRadius: '8px',
              border: 'none',
              background: filter === 'unread' ? '#edf3ff' : 'transparent',
              color: filter === 'unread' ? '#4f7df3' : '#687386',
              fontWeight: filter === 'unread' ? 700 : 500,
              fontSize: '12.5px',
              cursor: 'pointer',
            }}
          >
            Unread ({unreadCount})
          </button>
        </div>

        {/* Modal Body / Notification List */}
        <div style={{ padding: '16px 20px', overflowY: 'auto', flex: 1, maxHeight: '500px' }}>
          {isLoading ? (
            <div style={{ textAlign: 'center', padding: '40px 0', color: '#98a2b3', fontSize: '13px' }}>
              Loading notifications...
            </div>
          ) : notifications.length === 0 ? (
            <div style={{ textAlign: 'center', padding: '40px 0', color: '#98a2b3' }}>
              <Bell size={32} style={{ margin: '0 auto 8px auto', opacity: 0.4 }} />
              <p style={{ fontSize: '14px', margin: 0, fontWeight: 600, color: '#1d2738' }}>No notifications found</p>
              <p style={{ fontSize: '12.5px', margin: '4px 0 0 0' }}>
                {filter === 'unread'
                  ? 'All business notifications have been marked as read.'
                  : 'You have no business notifications yet for this page.'}
              </p>
            </div>
          ) : (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
              {notifications.map((n) => {
                const targetLink = getNotificationLink(slug, n);
                return (
                  <div
                    key={n.id}
                    style={{
                      padding: '12px 14px',
                      borderRadius: '12px',
                      background: n.is_read ? '#fff' : '#edf3ff',
                      border: '1px solid #e7ecf4',
                      display: 'flex',
                      alignItems: 'flex-start',
                      justifyContent: 'space-between',
                      gap: '12px',
                      transition: 'background 0.2s',
                    }}
                  >
                    <div style={{ display: 'flex', gap: '10px', minWidth: 0, flex: 1 }}>
                      <div
                        style={{
                          width: '32px',
                          height: '32px',
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
                            onClose();
                          }}
                          style={{ textDecoration: 'none', color: 'inherit' }}
                        >
                          <strong
                            style={{
                              fontSize: '13.5px',
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
                                fontSize: '12.5px',
                                color: '#687386',
                                margin: '4px 0 0 0',
                                whiteSpace: 'nowrap',
                                overflow: 'hidden',
                                textOverflow: 'ellipsis',
                              }}
                            >
                              {n.data.message_snippet}
                            </p>
                          )}
                        </Link>
                        <span style={{ fontSize: '11px', color: '#98a2b3', display: 'block', marginTop: '4px' }}>
                          {formatRelativeTime(n.created_at)}
                        </span>
                      </div>
                    </div>

                    <div style={{ display: 'flex', alignItems: 'center', gap: '4px', flexShrink: 0 }}>
                      {!n.is_read && (
                        <button
                          type="button"
                          onClick={() => handleMarkOneRead(n.id)}
                          style={{
                            background: 'transparent',
                            border: 'none',
                            cursor: 'pointer',
                            padding: '4px',
                            color: '#4f7df3',
                          }}
                          title="Mark as read"
                        >
                          <Check size={14} />
                        </button>
                      )}
                      <button
                        type="button"
                        onClick={() => handleDeleteOne(n.id)}
                        style={{
                          background: 'transparent',
                          border: 'none',
                          cursor: 'pointer',
                          padding: '4px',
                          color: '#98a2b3',
                        }}
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
    </ModalPortal>
  );
}

export default BusinessNotificationModal;
