import { useState, useRef, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { Bell, LoaderCircle, CheckCheck, ArrowRight, BellRing } from 'lucide-react';
import useAuth from '../../hooks/useAuth';
import useNotificationAutoRead from '../../hooks/useNotificationAutoRead';
import notificationApi from '../../api/notificationApi';
import NotificationItem from '../../components/notifications/NotificationItem';
import AnnouncementModal from '../../components/notifications/AnnouncementModal';
import { processNewNotifications, markNotificationsAsKnown } from '../../utils/notificationSound';

export function NotificationDropdown({ isOpen: propIsOpen, onToggle, onClose }) {
  const { user, unreadCount, setUnreadCount } = useAuth();
  const [internalIsOpen, setInternalIsOpen] = useState(false);

  const isControlled = propIsOpen !== undefined;
  const isOpen = isControlled ? propIsOpen : internalIsOpen;

  const [isLoading, setIsLoading] = useState(false);
  const [isMarkingAll, setIsMarkingAll] = useState(false);
  const [notifications, setNotifications] = useState([]);
  const [selectedAnnouncement, setSelectedAnnouncement] = useState(null);
  const [isAnnouncementModalOpen, setIsAnnouncementModalOpen] = useState(false);
  const dropdownRef = useRef(null);
  const bodyRef = useRef(null);

  useNotificationAutoRead({
    enabled: isOpen && !isLoading,
    containerRef: bodyRef,
    rootRef: bodyRef,
    notifications,
    setNotifications,
    setUnreadCount,
    threshold: 0.6,
  });

  const unreadCountRef = useRef(unreadCount);
  useEffect(() => {
    unreadCountRef.current = unreadCount;
  }, [unreadCount]);

  const userIdRef = useRef(user?.id);
  useEffect(() => {
    userIdRef.current = user?.id;
  }, [user?.id]);

  // Poll for new notifications periodically (every 30s when visible)
  const pollNotifications = useCallback(async () => {
    if (document.visibilityState === 'hidden') return;
    try {
      const data = await notificationApi.poll(unreadCountRef.current);
      if (data && data.success) {
        if (data.unread_count !== undefined) {
          setUnreadCount(data.unread_count);
        }
        if (Array.isArray(data.notifications)) {
          setNotifications(data.notifications);
          processNewNotifications(data.notifications, userIdRef.current);
        }
      }
    } catch {
      // Silent background poll error handling
    }
  }, [setUnreadCount]);

  useEffect(() => {
    // Initial fetch of notifications snapshot on mount
    pollNotifications();

    const timer = setInterval(pollNotifications, 30000);
    const handleVisibilityChange = () => {
      if (document.visibilityState === 'visible') {
        pollNotifications();
      }
    };
    document.addEventListener('visibilitychange', handleVisibilityChange);

    return () => {
      clearInterval(timer);
      document.removeEventListener('visibilitychange', handleVisibilityChange);
    };
  }, [pollNotifications]);

  // Fetch dropdown notifications when opened
  const fetchDropdownData = useCallback(async () => {
    setIsLoading(true);
    try {
      const data = await notificationApi.getDropdownNotifications();
      if (data && data.success) {
        if (data.unread_count !== undefined) {
          setUnreadCount(data.unread_count);
        }
        if (Array.isArray(data.notifications)) {
          setNotifications(data.notifications);
          markNotificationsAsKnown(data.notifications);
        }
      }
    } catch {
      // Handle error
    } finally {
      setIsLoading(false);
    }
  }, [setUnreadCount]);

  const handleToggle = () => {
    if (onToggle) {
      onToggle();
    } else {
      setInternalIsOpen((prev) => !prev);
    }
  };

  const handleClose = useCallback(() => {
    if (onClose) {
      onClose();
    } else {
      setInternalIsOpen(false);
    }
  }, [onClose]);

  // Trigger fetch whenever isOpen transitions to true
  useEffect(() => {
    if (isOpen) {
      fetchDropdownData();
    }
  }, [isOpen, fetchDropdownData]);

  const handleMarkAllRead = async (e) => {
    e.preventDefault();
    if (isMarkingAll || unreadCount === 0) return;
    setIsMarkingAll(true);
    try {
      const res = await notificationApi.markAllAsRead();
      if (res && res.success) {
        setUnreadCount(0);
        setNotifications((prev) =>
          prev.map((n) => ({ ...n, read_at: n.read_at || new Date().toISOString() }))
        );
      }
    } catch {
      // Handle error
    } finally {
      setIsMarkingAll(false);
    }
  };

  const handleItemRead = (notificationId) => {
    setNotifications((prev) =>
      prev.map((n) =>
        n.id === notificationId ? { ...n, read_at: n.read_at || new Date().toISOString() } : n
      )
    );
  };

  const handleItemDelete = async (notificationId) => {
    try {
      const res = await notificationApi.deleteNotification(notificationId);
      if (res && res.unread_count !== undefined) {
        setUnreadCount(res.unread_count);
      }
      setNotifications((prev) => prev.filter((n) => n.id !== notificationId));
    } catch {
      // Handle error
    }
  };

  const handleOpenAnnouncement = (notification) => {
    setSelectedAnnouncement(notification);
    setIsAnnouncementModalOpen(true);
    handleClose();
  };

  // Close dropdown on click outside or Escape key
  useEffect(() => {
    function handleClickOutside(event) {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        handleClose();
      }
    }

    function handleKeyDown(event) {
      if (event.key === 'Escape' && isOpen) {
        handleClose();
      }
    }

    if (isOpen) {
      document.addEventListener('mousedown', handleClickOutside);
      document.addEventListener('keydown', handleKeyDown);
    }

    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
      document.removeEventListener('keydown', handleKeyDown);
    };
  }, [isOpen, handleClose]);

  const hasUnread = unreadCount > 0 || notifications.some((n) => !n.read_at);

  return (
    <div className="notification-menu" ref={dropdownRef} data-notification-menu>
      <button
        className="icon-button has-badge"
        type="button"
        aria-label="Notifications"
        aria-expanded={isOpen}
        aria-controls="member-notification-dropdown"
        data-notification-trigger
        onClick={handleToggle}
      >
        <Bell size={20} aria-hidden="true" />
        {unreadCount > 0 && (
          <span className="notification-badge" aria-live="polite" data-notification-badge>
            {unreadCount > 99 ? '99+' : unreadCount}
          </span>
        )}
      </button>

      <div
        className={`notification-dropdown ${isOpen ? 'is-open' : ''}`}
        id="member-notification-dropdown"
        role="dialog"
        aria-label="Notifications"
        data-notification-dropdown
        style={{ display: isOpen ? 'block' : 'none' }}
      >
        <div className="notification-dropdown__header">
          <div>
            <span>Latest updates</span>
            <h2>Notifications</h2>
          </div>
          <button
            type="button"
            className="notification-dropdown__mark-all"
            onClick={handleMarkAllRead}
            disabled={!hasUnread || isMarkingAll}
          >
            <CheckCheck size={14} aria-hidden="true" />
            <span>{isMarkingAll ? 'Marking...' : 'Mark all as read'}</span>
          </button>
        </div>

        <div className="notification-dropdown__body" ref={bodyRef}>
          {isLoading ? (
            <div className="notification-dropdown__loading" aria-live="polite">
              <LoaderCircle size={20} aria-hidden="true" />
              <span>Loading notifications…</span>
            </div>
          ) : notifications.length === 0 ? (
            <div className="notification-empty notification-empty--compact">
              <BellRing size={24} aria-hidden="true" />
              <strong>No notifications yet</strong>
              <span>Friend requests and new posts will appear here.</span>
            </div>
          ) : (
            notifications.map((notification) => (
              <NotificationItem
                key={notification.id}
                notification={notification}
                onRead={handleItemRead}
                onDelete={handleItemDelete}
                onClickItem={handleClose}
                onOpenAnnouncement={handleOpenAnnouncement}
              />
            ))
          )}
        </div>

        <footer className="notification-dropdown__footer">
          <Link to="/member/notifications" onClick={handleClose}>
            <span>See all notifications</span>
            <ArrowRight size={14} aria-hidden="true" />
          </Link>
        </footer>
      </div>

      <AnnouncementModal
        isOpen={isAnnouncementModalOpen}
        notification={selectedAnnouncement}
        onClose={() => {
          setIsAnnouncementModalOpen(false);
          setSelectedAnnouncement(null);
        }}
      />
    </div>
  );
}

export default NotificationDropdown;
