import { useEffect, useRef, useCallback } from 'react';
import notificationApi from '../api/notificationApi';

/**
 * Custom hook to automatically mark notifications as read when they enter
 * the visible viewport of a scroll container (or window).
 *
 * @param {Object} options
 * @param {boolean} [options.enabled=true] - Whether visibility observation is active
 * @param {React.RefObject} options.containerRef - Ref to the element containing notification items
 * @param {React.RefObject|null} [options.rootRef=null] - Ref to scroll container (null for viewport)
 * @param {Array} [options.notifications=[]] - Current array of notification objects
 * @param {Function} options.setNotifications - State setter for notifications
 * @param {Function} [options.setUnreadCount] - Context setter for unread count
 * @param {number} [options.threshold=0.6] - Visibility ratio required (0.0 - 1.0)
 */
export function useNotificationAutoRead({
  enabled = true,
  containerRef,
  rootRef = null,
  notifications = [],
  setNotifications,
  setUnreadCount,
  threshold = 0.6,
}) {
  const processedIdsRef = useRef(new Set());
  const queueRef = useRef([]);
  const isProcessingRef = useRef(false);
  const failedCountRef = useRef(new Map());
  const isMountedRef = useRef(true);

  useEffect(() => {
    isMountedRef.current = true;
    return () => {
      isMountedRef.current = false;
    };
  }, []);

  // Process the queue of visible notification IDs sequentially
  const processQueue = useCallback(async () => {
    if (isProcessingRef.current) return;
    isProcessingRef.current = true;

    while (queueRef.current.length > 0) {
      const nextId = queueRef.current.shift();
      if (!nextId) continue;

      try {
        const res = await notificationApi.markAsRead(nextId);
        if (
          res &&
          res.unread_count !== undefined &&
          isMountedRef.current &&
          setUnreadCount
        ) {
          setUnreadCount(res.unread_count);
        }
      } catch (err) {
        // Failed request: revert optimistic update so backend remains authoritative
        const failCount = (failedCountRef.current.get(nextId) || 0) + 1;
        failedCountRef.current.set(nextId, failCount);

        if (isMountedRef.current && setNotifications) {
          setNotifications((prev) =>
            prev.map((n) =>
              String(n.id) === String(nextId) ? { ...n, read_at: null } : n
            )
          );
        }

        // Allow retry later (up to 3 attempts) after a delay
        if (failCount < 3) {
          setTimeout(() => {
            processedIdsRef.current.delete(nextId);
          }, 3000);
        }
      }
    }

    isProcessingRef.current = false;
  }, [setNotifications, setUnreadCount]);

  // Queue a notification ID and trigger processing
  const enqueueMarkRead = useCallback(
    (id) => {
      const stringId = String(id);
      if (processedIdsRef.current.has(stringId)) return;
      processedIdsRef.current.add(stringId);

      // 1. Optimistic update in notifications list
      if (setNotifications) {
        setNotifications((prev) =>
          prev.map((n) =>
            String(n.id) === stringId && !n.read_at
              ? { ...n, read_at: new Date().toISOString() }
              : n
          )
        );
      }

      // 2. Optimistic decrement of unread count
      if (setUnreadCount) {
        setUnreadCount((prev) => Math.max(0, prev - 1));
      }

      // 3. Queue for backend single-read request
      queueRef.current.push(stringId);
      processQueue();
    },
    [setNotifications, setUnreadCount, processQueue]
  );

  useEffect(() => {
    if (!enabled || !containerRef?.current) return;
    if (typeof window === 'undefined' || !('IntersectionObserver' in window)) return;

    const rootElement = rootRef && rootRef.current ? rootRef.current : null;

    // Set of unread notification IDs from props
    const unreadIds = new Set(
      notifications
        .filter((n) => !n.read_at)
        .map((n) => String(n.id))
    );

    if (unreadIds.size === 0) return;

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const id = entry.target.getAttribute('data-notification-item');
            if (!id) return;

            // Immediately unobserve so it fires only once per item
            observer.unobserve(entry.target);

            // Double check unread and processed state
            if (processedIdsRef.current.has(String(id))) return;
            enqueueMarkRead(id);
          }
        });
      },
      {
        root: rootElement,
        threshold,
      }
    );

    let rafId = requestAnimationFrame(() => {
      if (!containerRef.current) return;
      const elements = containerRef.current.querySelectorAll(
        '[data-notification-item]'
      );

      elements.forEach((el) => {
        const id = el.getAttribute('data-notification-item');
        if (
          id &&
          unreadIds.has(String(id)) &&
          !processedIdsRef.current.has(String(id))
        ) {
          observer.observe(el);
        }
      });
    });

    return () => {
      if (rafId) cancelAnimationFrame(rafId);
      observer.disconnect();
    };
  }, [
    enabled,
    notifications,
    threshold,
    containerRef,
    rootRef,
    enqueueMarkRead,
  ]);
}

export default useNotificationAutoRead;
