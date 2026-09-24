import { useState, useRef, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import {
  Bell,
  BellOff,
  CheckCircle,
  User,
  Shield,
  AlertCircle,
  Clock,
  ArrowRight,
  Flag,
  Building2,
  Calendar,
  Megaphone,
} from 'lucide-react';
import { OverlayPanel } from 'primereact/overlaypanel';
import { notificationsApi } from '../../api';
import { useToast } from '../../hooks/useToast';
import { useAuth } from '../../hooks/useAuth';
import { processNewNotifications, markNotificationsAsKnown } from '../../utils/notificationSound';

export function NotificationDropdown() {
  const { admin } = useAuth();
  const navigate = useNavigate();
  const op = useRef(null);
  const isMarkingReadRef = useRef(false);
  const [unreadCount, setUnreadCount] = useState(0);
  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(true);
  const { showSuccess } = useToast();

  const adminIdentifier = admin?.id || admin?.email || 'admin';

  useEffect(() => {
    let isMounted = true;

    const loadData = async (isBackgroundPoll = false) => {
      try {
        if (typeof notificationsApi.getDropdownData === 'function') {
          const res = await notificationsApi.getDropdownData();
          if (res?.success && isMounted) {
            const count = typeof res.unread_count === 'number'
              ? res.unread_count
              : typeof res.count === 'number'
              ? res.count
              : 0;
            setUnreadCount(count);
            const incoming = Array.isArray(res.notifications) ? res.notifications : [];
            setNotifications(incoming);
            // Detect genuinely new notifications and trigger sound
            processNewNotifications(incoming, adminIdentifier);
            return;
          }
        }

        if (typeof notificationsApi.getUnreadCount === 'function') {
          const res = await notificationsApi.getUnreadCount();
          if (isMounted) {
            if (typeof res?.unread_count === 'number') {
              setUnreadCount(res.unread_count);
            } else if (typeof res?.count === 'number') {
              setUnreadCount(res.count);
            }
          }
        }
      } catch (err) {
        console.error('Failed to fetch admin notifications:', err);
      } finally {
        if (!isBackgroundPoll && isMounted) {
          setLoading(false);
        }
      }
    };

    // 1. Initial snapshot on mount establishes baseline (no sound)
    loadData(false);

    // 2. Background polling every 30 seconds to detect new arrivals
    const timer = setInterval(() => {
      if (document.visibilityState !== 'hidden') {
        loadData(true);
      }
    }, 30000);

    const handleVisibilityChange = () => {
      if (document.visibilityState === 'visible') {
        loadData(true);
      }
    };
    document.addEventListener('visibilitychange', handleVisibilityChange);

    return () => {
      isMounted = false;
      clearInterval(timer);
      document.removeEventListener('visibilitychange', handleVisibilityChange);
    };
  }, [adminIdentifier]);

  // Automatically mark all notifications as read as soon as dropdown opens
  const handlePanelShow = async () => {
    markNotificationsAsKnown(notifications);

    // 1. Guard: If already marked as read or 0 unread, no backend call needed (idempotent)
    const hasUnread = unreadCount > 0 || notifications.some((n) => !n.read);
    if (!hasUnread) {
      return;
    }

    // 2. Guard: Prevent duplicate in-flight requests
    if (isMarkingReadRef.current) {
      return;
    }

    isMarkingReadRef.current = true;

    try {
      const res = await notificationsApi.markAllRead();
      if (res?.success) {
        // Backend confirmed read status
        setUnreadCount(0);
        setNotifications((prev) => prev.map((n) => ({ ...n, read: true, is_read: true })));
      }
    } catch (err) {
      console.error('Failed to mark notifications as read on dropdown open:', err);
    } finally {
      isMarkingReadRef.current = false;
    }
  };

  const handleMarkAllRead = async (e) => {
    if (e?.preventDefault) e.preventDefault();
    if (isMarkingReadRef.current) return;
    isMarkingReadRef.current = true;

    try {
      const res = await notificationsApi.markAllRead();
      if (res?.success) {
        markNotificationsAsKnown(notifications);
        setNotifications((prev) => prev.map((n) => ({ ...n, read: true, is_read: true })));
        setUnreadCount(0);
        showSuccess('All notifications marked as read.');
      }
    } catch (err) {
      console.error('Failed to mark all as read:', err);
    } finally {
      isMarkingReadRef.current = false;
    }
  };

  const handleNotificationClick = (notif) => {
    if (notif?.action_url) {
      op.current?.hide();
      navigate(notif.action_url);
    }
  };

  const getNotificationIcon = (iconName) => {
    switch (iconName) {
      case 'user':
        return <User className="w-3.5 h-3.5" />;
      case 'shield':
        return <Shield className="w-3.5 h-3.5" />;
      case 'alert-circle':
        return <AlertCircle className="w-3.5 h-3.5" />;
      case 'flag':
        return <Flag className="w-3.5 h-3.5" />;
      case 'building':
      case 'building-2':
        return <Building2 className="w-3.5 h-3.5" />;
      case 'calendar':
        return <Calendar className="w-3.5 h-3.5" />;
      case 'megaphone':
        return <Megaphone className="w-3.5 h-3.5" />;
      default:
        return <Bell className="w-3.5 h-3.5" />;
    }
  };

  const displayBadge = unreadCount > 99 ? '99+' : unreadCount;

  return (
    <div>
      <button
        type="button"
        onClick={(e) => op.current?.toggle(e)}
        className="relative p-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors focus:outline-hidden cursor-pointer"
        aria-label="Notifications"
        title="Notifications"
      >
        <Bell className="w-5 h-5" />
        {unreadCount > 0 && (
          <span className="absolute top-1.5 right-1.5 min-w-[16px] h-4 bg-red-500 text-white text-[10px] font-bold rounded-full px-1 flex items-center justify-center animate-pulse">
            {displayBadge}
          </span>
        )}
      </button>

      <OverlayPanel
        ref={op}
        onShow={handlePanelShow}
        className="w-80 sm:w-96 p-0 shadow-2xl rounded-xl border border-slate-200 overflow-hidden"
      >
        {/* Header */}
        <div className="p-3.5 bg-slate-900 text-white flex items-center justify-between">
          <div className="flex items-center space-x-2">
            <Bell className="w-4 h-4 text-blue-400" />
            <span className="text-sm font-bold">Notifications</span>
          </div>
          {unreadCount > 0 ? (
            <span className="bg-blue-600 text-white text-[11px] px-2 py-0.5 rounded-full font-semibold">
              {unreadCount} New
            </span>
          ) : (
            <span className="bg-slate-800 text-slate-400 text-[11px] px-2 py-0.5 rounded-full font-medium">
              0 New
            </span>
          )}
        </div>

        {/* List */}
        <div className="divide-y divide-slate-100 max-h-80 overflow-y-auto">
          {loading && notifications.length === 0 ? (
            <div className="p-4 space-y-3">
              {[1, 2, 3].map((i) => (
                <div key={i} className="flex items-start space-x-3 animate-pulse">
                  <div className="w-8 h-8 rounded-full bg-slate-200 shrink-0" />
                  <div className="flex-1 space-y-1.5">
                    <div className="h-3 bg-slate-200 rounded w-2/3" />
                    <div className="h-2.5 bg-slate-200 rounded w-5/6" />
                  </div>
                </div>
              ))}
            </div>
          ) : notifications.length === 0 ? (
            <div className="p-8 text-center text-slate-400">
              <div className="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-2 text-slate-400">
                <BellOff className="w-5 h-5 text-slate-400" />
              </div>
              <p className="text-xs font-semibold text-slate-700">No notifications yet</p>
              <p className="text-[11px] text-slate-400 mt-0.5">You're all caught up. Platform activity will appear here.</p>
            </div>
          ) : (
            notifications.map((notif) => {
              const isClickable = Boolean(notif.action_url);

              return (
                <div
                  key={notif.id}
                  onClick={() => handleNotificationClick(notif)}
                  className={`p-3.5 flex items-start space-x-3 transition-colors ${
                    isClickable ? 'cursor-pointer' : ''
                  } ${
                    notif.read
                      ? 'bg-white opacity-85 hover:bg-slate-50'
                      : 'bg-slate-50/90 hover:bg-slate-100/80'
                  }`}
                  role={isClickable ? 'button' : undefined}
                  tabIndex={isClickable ? 0 : undefined}
                >
                  <div
                    className={`w-8 h-8 rounded-full flex items-center justify-center shrink-0 mt-0.5 ${
                      notif.read ? 'bg-slate-100 text-slate-500' : 'bg-blue-100 text-blue-600'
                    }`}
                  >
                    {getNotificationIcon(notif.icon)}
                  </div>

                  <div className="flex-1 min-w-0">
                    <div className="flex items-center justify-between gap-1">
                      <div className="flex items-center gap-1.5 min-w-0">
                        {!notif.read && (
                          <span className="w-2 h-2 rounded-full bg-emerald-500 shrink-0" title="Unread" />
                        )}
                        <h5
                          className={`text-xs truncate ${
                            notif.read ? 'font-medium text-slate-700' : 'font-bold text-slate-900'
                          }`}
                        >
                          {notif.title}
                        </h5>
                      </div>
                      {!notif.read && (
                        <span className="bg-blue-600 text-white text-[8px] font-extrabold px-1.5 py-0.5 rounded-xs uppercase tracking-wider shrink-0">
                          NEW
                        </span>
                      )}
                    </div>
                    <p className="text-[11px] text-slate-500 line-clamp-2 mt-0.5">{notif.message}</p>
                    <span className="text-[10px] text-slate-400 flex items-center mt-1">
                      <Clock className="w-2.5 h-2.5 mr-1" />
                      {notif.time}
                    </span>
                  </div>
                </div>
              );
            })
          )}
        </div>

        {/* Footer */}
        <div className="p-2.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs">
          {unreadCount > 0 ? (
            <button
              onClick={handleMarkAllRead}
              className="text-blue-600 hover:text-blue-700 font-semibold flex items-center space-x-1 cursor-pointer"
            >
              <CheckCircle className="w-3.5 h-3.5" />
              <span>Mark all read</span>
            </button>
          ) : (
            <span className="text-slate-400 text-[11px] flex items-center gap-1">
              <CheckCircle className="w-3.5 h-3.5 text-emerald-500" />
              <span>All caught up</span>
            </span>
          )}

          <Link
            to="/admin/notifications"
            onClick={() => op.current?.hide()}
            className="text-slate-600 hover:text-slate-900 font-medium inline-flex items-center space-x-1"
          >
            <span>View all</span>
            <ArrowRight className="w-3 h-3" />
          </Link>
        </div>
      </OverlayPanel>
    </div>
  );
}

export default NotificationDropdown;
