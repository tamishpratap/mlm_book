import { useState, useEffect, useCallback, useRef } from 'react';
import { useSearchParams } from 'react-router-dom';
import {
  CheckCheck,
  Trash2,
  BellOff,
  ChevronLeft,
  ChevronRight,
  RotateCw,
} from 'lucide-react';
import useAuth from '../../hooks/useAuth';
import useNotificationAutoRead from '../../hooks/useNotificationAutoRead';
import notificationApi from '../../api/notificationApi';
import NotificationItem from '../../components/notifications/NotificationItem';
import AnnouncementModal from '../../components/notifications/AnnouncementModal';
import DeleteConfirmModal from '../../components/posts/modals/DeleteConfirmModal';
import FeedRightSidebar from '../../components/posts/FeedRightSidebar';

export function NotificationsPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const activeFilter = searchParams.get('filter') || 'all';
  const currentPage = parseInt(searchParams.get('page') || '1', 10);

  const { setUnreadCount } = useAuth();

  const [notifications, setNotifications] = useState([]);
  const [lastPage, setLastPage] = useState(1);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [isMarkingAll, setIsMarkingAll] = useState(false);
  const [isClearingAll, setIsClearingAll] = useState(false);
  const [showClearConfirm, setShowClearConfirm] = useState(false);
  const [selectedAnnouncement, setSelectedAnnouncement] = useState(null);
  const [isAnnouncementModalOpen, setIsAnnouncementModalOpen] = useState(false);
  const [error, setError] = useState(null);
  const listCardRef = useRef(null);

  useNotificationAutoRead({
    enabled: !isLoading && notifications.length > 0,
    containerRef: listCardRef,
    rootRef: null,
    notifications,
    setNotifications,
    setUnreadCount,
    threshold: 0.6,
  });

  const filterTabs = [
    { key: 'all', label: 'All' },
    { key: 'unread', label: 'Unread' },
    { key: 'friends', label: 'Friends' },
    { key: 'stories', label: 'Stories' },
    { key: 'posts', label: 'Posts' },
    { key: 'comments', label: 'Comments' },
    { key: 'system', label: 'System' },
  ];

  useEffect(() => {
    let isMounted = true;
    const params = {
      filter: activeFilter !== 'all' ? activeFilter : undefined,
      page: currentPage,
    };

    notificationApi
      .getNotifications(params)
      .then((data) => {
        if (isMounted) {
          if (data && data.notifications) {
            const list = data.notifications.data || [];
            setNotifications(list);
            setLastPage(data.notifications.last_page || 1);
            if (data.unread_count !== undefined) {
              setUnreadCount(data.unread_count);
            }
          } else {
            setNotifications([]);
          }
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load notifications.');
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
  }, [activeFilter, currentPage, setUnreadCount]);

  const handleRefresh = useCallback(() => {
    setIsRefreshing(true);
    const params = {
      filter: activeFilter !== 'all' ? activeFilter : undefined,
      page: currentPage,
    };

    notificationApi
      .getNotifications(params)
      .then((data) => {
        if (data && data.notifications) {
          const list = data.notifications.data || [];
          setNotifications(list);
          setLastPage(data.notifications.last_page || 1);
          if (data.unread_count !== undefined) {
            setUnreadCount(data.unread_count);
          }
        }
      })
      .catch(() => {})
      .finally(() => {
        setIsRefreshing(false);
      });
  }, [activeFilter, currentPage, setUnreadCount]);

  const handleFilterChange = (filterKey) => {
    const newParams = new URLSearchParams(searchParams);
    if (filterKey !== 'all') {
      newParams.set('filter', filterKey);
    } else {
      newParams.delete('filter');
    }
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handlePageChange = (newPage) => {
    if (newPage < 1 || newPage > lastPage) return;
    const newParams = new URLSearchParams(searchParams);
    newParams.set('page', newPage.toString());
    setSearchParams(newParams);
  };

  const handleMarkAllRead = async () => {
    if (isMarkingAll) return;
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

  const handleClearAllConfirm = async () => {
    setIsClearingAll(true);
    try {
      const res = await notificationApi.clearAll();
      if (res && res.success) {
        setUnreadCount(0);
        setNotifications([]);
        setShowClearConfirm(false);
      }
    } catch {
      // Handle error
    } finally {
      setIsClearingAll(false);
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
  };

  const hasUnread = notifications.some((n) => !n.read_at);

  return (
    <>
      <main className="member-main feed" id="notifications-page-main">
        <div className="notifications-page">
          <header className="notifications-page__header">
            <div>
              <span>Stay up to date</span>
              <h1>Notifications</h1>
              <p>Activity on your posts, stories, comments, and friend requests.</p>
            </div>

            <div className="notifications-page__actions" style={{ display: 'flex', gap: '10px', alignItems: 'center' }}>
              <button
                className="member-button member-button--secondary"
                type="button"
                aria-label="Refresh Notifications"
                onClick={handleRefresh}
                disabled={isRefreshing}
                style={{ padding: '8px 12px' }}
              >
                <RotateCw size={14} className={isRefreshing ? 'fa-spin' : ''} aria-hidden="true" />
              </button>

              <button
                className="member-button member-button--secondary"
                type="button"
                onClick={handleMarkAllRead}
                disabled={!hasUnread || isMarkingAll}
              >
                <CheckCheck size={16} aria-hidden="true" />
                <span>{isMarkingAll ? 'Marking...' : 'Mark all as read'}</span>
              </button>

              <button
                className="member-button member-button--secondary member-button--danger"
                type="button"
                onClick={() => setShowClearConfirm(true)}
                disabled={notifications.length === 0}
              >
                <Trash2 size={16} aria-hidden="true" />
                <span>Clear all</span>
              </button>
            </div>
          </header>

          {/* Filter Tabs */}
          <nav className="notification-filter-tabs" aria-label="Notification Categories">
            {filterTabs.map((tab) => (
              <button
                key={tab.key}
                type="button"
                onClick={() => handleFilterChange(tab.key)}
                className={`notification-filter-tab ${activeFilter === tab.key ? 'is-active' : ''}`}
              >
                {tab.label}
              </button>
            ))}
          </nav>

          {/* Notifications List Card */}
          <section className="member-card notifications-page__card" aria-label="Notification history" ref={listCardRef}>
            {isLoading ? (
              <div style={{ padding: '32px', textAlign: 'center', color: 'var(--color-text-secondary)' }}>
                Loading notifications...
              </div>
            ) : error ? (
              <div className="notification-empty" role="alert">
                <h2>Error Loading Notifications</h2>
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
            ) : notifications.length === 0 ? (
              <div className="notification-empty">
                <div className="notification-empty__icon">
                  <BellOff size={40} aria-hidden="true" />
                </div>
                <h2>
                  No {activeFilter !== 'all' ? activeFilter.charAt(0).toUpperCase() + activeFilter.slice(1) : ''} Notifications
                </h2>
                <p>When you get new notifications, they will show up here.</p>
              </div>
            ) : (
              notifications.map((notification) => (
                <NotificationItem
                  key={notification.id}
                  notification={notification}
                  onRead={handleItemRead}
                  onDelete={handleItemDelete}
                  onOpenAnnouncement={handleOpenAnnouncement}
                />
              ))
            )}
          </section>

          {/* Pagination */}
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

        {/* Clear All Confirmation Modal */}
        {showClearConfirm && (
          <DeleteConfirmModal
            isOpen={showClearConfirm}
            onClose={() => setShowClearConfirm(false)}
            onConfirm={handleClearAllConfirm}
            title="Clear all notifications?"
            message="Are you sure you want to clear all your notifications? This cannot be undone."
            isDeleting={isClearingAll}
          />
        )}

        {/* Admin Announcement Full Content Modal */}
        <AnnouncementModal
          isOpen={isAnnouncementModalOpen}
          notification={selectedAnnouncement}
          onClose={() => {
            setIsAnnouncementModalOpen(false);
            setSelectedAnnouncement(null);
          }}
        />
      </main>

      <FeedRightSidebar />
    </>
  );
}

export default NotificationsPage;
