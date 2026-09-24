import { X } from 'lucide-react';
import { ModalPortal } from './ModalPortal';

/**
 * Standardized Common Modal Component
 * Wraps content in ModalPortal with consistent header, title, close button,
 * scrollable body, and optional footer actions.
 */
export function Modal({
  isOpen,
  onClose,
  title,
  subtitle,
  icon = null,
  headerAction = null,
  children,
  footer = null,
  maxWidth = '540px',
  maxHeight = 'min(90vh, 760px)',
  closeOnBackdropClick = true,
  closeOnEsc = true,
  depth = 0,
  panelClassName = '',
  panelStyle = {},
  bodyStyle = {},
  headerStyle = {},
  showCloseButton = true,
  'aria-labelledby': ariaLabelledBy,
}) {
  if (!isOpen) return null;

  const titleId = ariaLabelledBy || (title ? 'global-modal-title' : undefined);

  return (
    <ModalPortal
      isOpen={isOpen}
      onClose={onClose}
      closeOnBackdropClick={closeOnBackdropClick}
      closeOnEsc={closeOnEsc}
      depth={depth}
    >
      <div
        className={`global-modal-panel card ${panelClassName}`}
        style={{
          width: '100%',
          maxWidth,
          maxHeight,
          display: 'flex',
          flexDirection: 'column',
          borderRadius: '20px',
          overflow: 'hidden',
          backgroundColor: '#ffffff',
          boxShadow: '0 25px 50px -12px rgba(15, 23, 42, 0.35)',
          border: '1px solid rgba(226, 232, 240, 0.9)',
          boxSizing: 'border-box',
          position: 'relative',
          ...panelStyle,
        }}
      >
        {/* Modal Header */}
        {(title || showCloseButton || icon) && (
          <header
            className="global-modal-header"
            style={{
              borderBottom: '1px solid var(--color-border-soft, #f1f5f9)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              gap: '12px',
              flexShrink: 0,
              backgroundColor: '#ffffff',
              ...headerStyle,
            }}
          >
            <div style={{ display: 'flex', alignItems: 'center', gap: '12px', minWidth: 0 }}>
              {icon && (
                <div
                  style={{
                    width: '38px',
                    height: '38px',
                    borderRadius: '10px',
                    backgroundColor: 'rgba(79, 125, 243, 0.1)',
                    color: '#4f7df3',
                    display: 'grid',
                    placeItems: 'center',
                    flexShrink: 0,
                  }}
                >
                  {icon}
                </div>
              )}
              <div style={{ minWidth: 0 }}>
                {title && (
                  <h2
                    id={titleId}
                    style={{
                      margin: 0,
                      fontSize: '1.15rem',
                      fontWeight: 800,
                      color: 'var(--color-text-main, #0f172a)',
                      overflow: 'hidden',
                      textOverflow: 'ellipsis',
                      whiteSpace: 'nowrap',
                    }}
                  >
                    {title}
                  </h2>
                )}
                {subtitle && (
                  <p
                    style={{
                      margin: '2px 0 0',
                      fontSize: '0.825rem',
                      color: 'var(--color-text-secondary, #64748b)',
                    }}
                  >
                    {subtitle}
                  </p>
                )}
              </div>
            </div>

            <div style={{ display: 'flex', alignItems: 'center', gap: '8px', flexShrink: 0 }}>
              {headerAction}
              {showCloseButton && (
                <button
                  type="button"
                  aria-label="Close"
                  onClick={onClose}
                  style={{
                    width: '32px',
                    height: '32px',
                    borderRadius: '50%',
                    background: '#f1f5f9',
                    border: 'none',
                    display: 'grid',
                    placeItems: 'center',
                    cursor: 'pointer',
                    color: '#475569',
                    transition: 'all 0.15s ease',
                  }}
                >
                  <X size={18} aria-hidden="true" />
                </button>
              )}
            </div>
          </header>
        )}

        {/* Modal Body with internal scrolling */}
        <div
          className="global-modal-body"
          style={{
            flex: '1 1 auto',
            overflowY: 'auto',
            overflowX: 'hidden',
            WebkitOverflowScrolling: 'touch',
            ...bodyStyle,
          }}
        >
          {children}
        </div>

        {/* Modal Footer */}
        {footer && (
          <footer
            className="global-modal-footer"
            style={{
              borderTop: '1px solid var(--color-border-soft, #f1f5f9)',
              background: '#f8fafc',
              flexShrink: 0,
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'flex-end',
              gap: '12px',
            }}
          >
            {footer}
          </footer>
        )}
      </div>
    </ModalPortal>
  );
}

export default Modal;
