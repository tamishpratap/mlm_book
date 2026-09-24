import { useEffect, useRef } from 'react';
import { createPortal } from 'react-dom';
import { useBodyScrollLock } from '../../hooks/useBodyScrollLock';

function getModalRoot() {
  if (typeof document === 'undefined') return null;
  let root = document.getElementById('modal-root');
  if (!root) {
    root = document.createElement('div');
    root.id = 'modal-root';
    document.body.appendChild(root);
  }
  return root;
}

/**
 * Standardized Global Modal Portal
 * Provides:
 * - Document-level portal rendering
 * - Full-viewport overlay covering navigation and sidebars
 * - Dark translucent backdrop with backdrop blur (blur(8px))
 * - True flex centering
 * - Body scroll locking with scrollbar width compensation
 * - Stacking context / depth support for nested modals
 * - Escape key & click-outside handling
 */
export function ModalPortal({
  isOpen,
  onClose,
  children,
  closeOnBackdropClick = true,
  closeOnEsc = true,
  depth = 0,
  baseZIndex = 10000,
  backdropBlur = true,
  backdropDim = true,
  overlayClassName = '',
  containerStyle = {},
  backdropStyle = {},
}) {
  useBodyScrollLock(Boolean(isOpen));
  const containerRef = useRef(null);

  // Close on Escape key (handled by topmost modal)
  useEffect(() => {
    if (!isOpen || !closeOnEsc || !onClose) return;

    const handleKeyDown = (e) => {
      if (e.key === 'Escape') {
        e.stopPropagation();
        onClose();
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, closeOnEsc, onClose]);

  if (!isOpen || typeof document === 'undefined') return null;

  const currentZIndex = baseZIndex + depth * 10;
  const modalRoot = getModalRoot() || document.body;

  const handleBackdropClick = (e) => {
    e.stopPropagation();
    if (closeOnBackdropClick && onClose && e.target === e.currentTarget) {
      onClose();
    }
  };

  return createPortal(
    <div
      ref={containerRef}
      className={`global-modal-overlay is-open ${overlayClassName}`}
      role="dialog"
      aria-modal="true"
      style={{
        position: 'fixed',
        inset: 0,
        width: '100vw',
        height: '100vh',
        zIndex: currentZIndex,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        boxSizing: 'border-box',
        overflowX: 'hidden',
        overflowY: 'auto',
        ...containerStyle,
      }}
      onClick={handleBackdropClick}
    >
      {/* Full Viewport Backdrop with Dim & Blur */}
      <div
        className="global-modal-backdrop"
        aria-hidden="true"
        style={{
          position: 'absolute',
          inset: 0,
          width: '100%',
          height: '100%',
          backgroundColor: backdropDim ? 'rgba(15, 23, 42, 0.72)' : 'transparent',
          backdropFilter: backdropBlur ? 'blur(8px)' : 'none',
          WebkitBackdropFilter: backdropBlur ? 'blur(8px)' : 'none',
          cursor: closeOnBackdropClick ? 'pointer' : 'default',
          transition: 'opacity 0.2s ease',
          zIndex: 0,
          pointerEvents: 'auto',
          ...backdropStyle,
        }}
        onClick={(e) => {
          if (closeOnBackdropClick && onClose) {
            e.stopPropagation();
            onClose();
          }
        }}
      />

      {/* Centered Modal Content Container (Stops Propagation & Placed Strictly Above Backdrop) */}
      <div
        className="global-modal-content-wrapper"
        style={{
          position: 'relative',
          zIndex: 10,
          display: 'flex',
          justifyContent: 'center',
          alignItems: 'center',
          width: '100%',
          maxHeight: '100%',
          pointerEvents: 'auto',
        }}
        onClick={(e) => {
          // If clicked directly on the wrapper padding/empty space outside the modal dialog, close if enabled
          if (e.target === e.currentTarget && closeOnBackdropClick && onClose) {
            onClose();
          }
        }}
      >
        {children}
      </div>
    </div>,
    modalRoot
  );
}

export default ModalPortal;
