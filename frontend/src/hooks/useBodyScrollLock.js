import { useEffect } from 'react';

// Module-level reference counter for active open modals
let openModalsCount = 0;
let originalOverflow = '';
let originalPaddingRight = '';

/**
 * Reusable body scroll lock hook.
 * Locks document.body scrolling when `isLocked` is true.
 * Safely handles multiple stacked modals without premature unlocking.
 * Compensates for scrollbar width to prevent desktop layout shifts.
 */
export function useBodyScrollLock(isLocked = true) {
  useEffect(() => {
    if (!isLocked || typeof document === 'undefined') return;

    if (openModalsCount === 0) {
      originalOverflow = document.body.style.overflow || '';
      originalPaddingRight = document.body.style.paddingRight || '';

      const scrollBarWidth = window.innerWidth - document.documentElement.clientWidth;
      if (scrollBarWidth > 0) {
        document.body.style.paddingRight = `${scrollBarWidth}px`;
      }
      document.body.style.overflow = 'hidden';
    }
    openModalsCount++;

    return () => {
      openModalsCount--;
      if (openModalsCount <= 0) {
        openModalsCount = 0;
        document.body.style.overflow = originalOverflow;
        document.body.style.paddingRight = originalPaddingRight;
      }
    };
  }, [isLocked]);
}

export default useBodyScrollLock;
