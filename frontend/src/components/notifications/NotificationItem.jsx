import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  UserPlus,
  UserCheck,
  UserX,
  FileText,
  ThumbsUp,
  Heart,
  MessageCircle,
  Share2,
  Eye,
  Bell,
  Users,
  Building2,
  Sparkles,
  Flame,
  Smile,
  X,
  Megaphone,
} from 'lucide-react';
import useAuth from '../../hooks/useAuth';
import notificationApi from '../../api/notificationApi';
import { getAvatarUrl, getInitials } from '../../utils/assetHelper';
import { handleNotificationNavigation } from '../../utils/notificationNavigation';

function formatRelativeTime(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return '';
  const now = new Date();
  const diffInSeconds = Math.floor((now - date) / 1000);

  if (diffInSeconds < 60) return 'Just now';
  if (diffInSeconds < 3600) {
    const mins = Math.floor(diffInSeconds / 60);
    return `${mins}m ago`;
  }
  if (diffInSeconds < 86400) {
    const hours = Math.floor(diffInSeconds / 3600);
    return `${hours}h ago`;
  }
  if (diffInSeconds < 604800) {
    const days = Math.floor(diffInSeconds / 86400);
    return `${days}d ago`;
  }
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

export function NotificationItem({
  notification,
  onRead,
  onDelete,
  onClickItem,
  onOpenAnnouncement,
}) {
  const navigate = useNavigate();
  const { setUnreadCount } = useAuth();
  const [imgError, setImgError] = useState(false);

  if (!notification) return null;

  let data = notification.data || {};
  if (typeof data === 'string') {
    try {
      data = JSON.parse(data);
    } catch {
      data = {};
    }
  }

  // Authoritative Admin Announcement detection using backend metadata only
  const notifType = (notification.notification_type || '').toUpperCase();
  const notifSource = (notification.source || notification.source_type || '').toUpperCase();
  const dataType = (data.notification_type || data.type || '').toUpperCase();
  const dataSource = (data.source || data.source_type || '').toUpperCase();
  const rawType = notification.type || '';

  const isAdminAnnouncement =
    notifType === 'ADMIN_ANNOUNCEMENT' ||
    dataType === 'ADMIN_ANNOUNCEMENT' ||
    (notifSource === 'ADMIN' && (notifType === 'ANNOUNCEMENT' || dataType === 'ANNOUNCEMENT')) ||
    (dataSource === 'ADMIN' && (notifType === 'ANNOUNCEMENT' || dataType === 'ANNOUNCEMENT')) ||
    rawType === 'App\\Notifications\\AdminBroadcastNotification' ||
    (typeof rawType === 'string' && rawType.endsWith('AdminBroadcastNotification')) ||
    data.reference_type === 'admin_announcement';

  const isUnread = !notification.read_at;
  const actorName = isAdminAnnouncement
    ? (data.actor_name || 'Admin Announcement')
    : (data.actor_name || data.actor?.name || 'MLM Book Member');
  const rawPhoto = isAdminAnnouncement ? null : (data.actor_photo || data.actor?.profile_photo);
  const actorPhoto = rawPhoto ? getAvatarUrl(rawPhoto) : null;
  const title = data.title || (isAdminAnnouncement ? 'Admin Announcement' : 'Notification');
  const message = data.message || data.body || 'You have a new notification.';
  const rawIcon = data.icon || (isAdminAnnouncement ? 'megaphone' : 'bell');

  const renderIcon = (iconName) => {
    switch (iconName) {
      case 'megaphone':
      case 'announcement':
        return <Megaphone size={11} aria-hidden="true" />;
      case 'user-plus':
        return <UserPlus size={11} aria-hidden="true" />;
      case 'user-check':
        return <UserCheck size={11} aria-hidden="true" />;
      case 'user-x':
        return <UserX size={11} aria-hidden="true" />;
      case 'file-text':
        return <FileText size={11} aria-hidden="true" />;
      case 'thumbs-up':
        return <ThumbsUp size={11} aria-hidden="true" />;
      case 'heart':
        return <Heart size={11} aria-hidden="true" />;
      case 'message-circle':
        return <MessageCircle size={11} aria-hidden="true" />;
      case 'share-2':
        return <Share2 size={11} aria-hidden="true" />;
      case 'eye':
        return <Eye size={11} aria-hidden="true" />;
      case 'users':
      case 'users-round':
      case 'community':
        return <Users size={11} aria-hidden="true" />;
      case 'building-2':
      case 'building':
        return <Building2 size={11} aria-hidden="true" />;
      case 'sparkles':
        return <Sparkles size={11} aria-hidden="true" />;
      case 'flame':
        return <Flame size={11} aria-hidden="true" />;
      case 'smile':
        return <Smile size={11} aria-hidden="true" />;
      default:
        return <Bell size={11} aria-hidden="true" />;
    }
  };

  const handleClick = (e) => {
    e.preventDefault();

    if (isUnread) {
      setUnreadCount((prev) => Math.max(0, prev - 1));
      if (onRead) onRead(notification.id);

      notificationApi
        .markAsRead(notification.id)
        .then((res) => {
          if (res && res.unread_count !== undefined) {
            setUnreadCount(res.unread_count);
          }
        })
        .catch(() => {
          // Silent catch: network latency or failure will never block navigation
        });
    }

    if (isAdminAnnouncement) {
      if (onOpenAnnouncement) {
        onOpenAnnouncement(notification);
      } else if (typeof window !== 'undefined') {
        window.dispatchEvent(
          new CustomEvent('open-announcement-modal', { detail: notification })
        );
      }

      if (onClickItem) {
        onClickItem();
      }
      return;
    }

    handleNotificationNavigation(notification, navigate);

    if (onClickItem) {
      onClickItem();
    }
  };

  const handleDelete = (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (onDelete) onDelete(notification.id);
  };

  return (
    <div
      className={`notification-item ${isAdminAnnouncement ? 'notification-item--announcement' : ''} ${isUnread ? 'is-unread' : 'is-read'}`}
      data-notification-item={notification.id}
    >
      <div className="notification-item__main">
        <button
          type="button"
          onClick={handleClick}
          aria-label={`${isUnread ? 'Unread notification: ' : 'Notification: '}${isAdminAnnouncement ? 'Admin Announcement - ' : ''}${title}`}
        >
          <span className="notification-item__avatar">
            {actorPhoto && !imgError ? (
              <img
                src={actorPhoto}
                alt={actorName}
                loading="lazy"
                onError={() => setImgError(true)}
              />
            ) : (
              <span aria-hidden="true">{getInitials(actorName)}</span>
            )}
            {renderIcon(rawIcon)}
          </span>

          <span className="notification-item__copy">
            {isAdminAnnouncement && (
              <span className="notification-item__header-meta">
                <span className="notification-item__announcement-badge" aria-label="Type: Announcement">
                  ANNOUNCEMENT
                </span>
                <span className="notification-item__source-tag" aria-label="Source: Administrator">
                  From Admin
                </span>
              </span>
            )}
            <strong>{title}</strong>
            <span>{message}</span>
            <time dateTime={notification.created_at}>
              {formatRelativeTime(notification.created_at)}
            </time>
          </span>

          {isUnread ? (
            <span className="notification-item__unread" aria-label="Unread" />
          ) : (
            <span className="notification-item__unread-placeholder" aria-hidden="true" />
          )}
        </button>
      </div>

      {onDelete && (
        <button
          className="notification-item__delete-btn"
          type="button"
          aria-label="Delete notification"
          title="Delete notification"
          onClick={handleDelete}
        >
          <X size={14} aria-hidden="true" />
        </button>
      )}
    </div>
  );
}

export default NotificationItem;
