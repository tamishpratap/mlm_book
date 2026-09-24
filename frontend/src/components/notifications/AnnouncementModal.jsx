import { useState, useEffect } from 'react';
import { X, Megaphone, Clock, LoaderCircle, AlertCircle } from 'lucide-react';
import { ModalPortal } from '../common/ModalPortal';
import notificationApi from '../../api/notificationApi';

function formatAnnouncementDateTime(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return String(dateString);

  return date.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    hour12: true,
  });
}

export function AnnouncementModal({
  isOpen,
  onClose,
  notification = null,
  notificationId = null,
}) {
  const [detailData, setDetailData] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);

  const activeId = notification?.id || notificationId;

  useEffect(() => {
    if (!isOpen) {
      setDetailData(null);
      setError(null);
      setIsLoading(false);
      return;
    }

    // Parse notification data if provided
    let rawPayload = notification?.data;
    if (typeof rawPayload === 'string') {
      try {
        rawPayload = JSON.parse(rawPayload);
      } catch {
        rawPayload = {};
      }
    }

    const hasEmbeddedContent =
      Boolean(rawPayload?.message || rawPayload?.body) &&
      Boolean(rawPayload?.title || notification?.title);

    if (hasEmbeddedContent) {
      setDetailData({
        title: rawPayload?.title || notification?.title || 'Admin Announcement',
        message: rawPayload?.message || rawPayload?.body || '',
        sentAt: rawPayload?.sent_at || notification?.created_at,
        source: rawPayload?.source || notification?.source || 'ADMIN',
      });
      setIsLoading(false);
      setError(null);
    } else if (activeId) {
      // Fetch authoritative notification detail from API if not embedded
      setIsLoading(true);
      setError(null);

      notificationApi
        .getNotification(activeId)
        .then((res) => {
          if (res && res.success && res.notification) {
            const notif = res.notification;
            let data = notif.data;
            if (typeof data === 'string') {
              try {
                data = JSON.parse(data);
              } catch {
                data = {};
              }
            }
            setDetailData({
              title: data?.title || notif.title || 'Admin Announcement',
              message: data?.message || data?.body || '',
              sentAt: data?.sent_at || notif.created_at,
              source: data?.source || notif.source || 'ADMIN',
            });
          } else {
            setError('Announcement not found.');
          }
        })
        .catch((err) => {
          const errMsg =
            err.response?.data?.message ||
            'Unable to load announcement details. Please try again later.';
          setError(errMsg);
        })
        .finally(() => {
          setIsLoading(false);
        });
    } else {
      setError('Announcement information is unavailable.');
    }
  }, [isOpen, activeId, notification]);

  if (!isOpen) return null;

  return (
    <ModalPortal isOpen={isOpen} onClose={onClose}>
      <div
        className="announcement-modal card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="announcement-modal-title"
        style={{
          background: '#ffffff',
          borderRadius: '20px',
          maxWidth: '560px',
          width: '100%',
          boxShadow: '0 25px 50px -12px rgba(15, 23, 42, 0.35)',
          overflow: 'hidden',
          maxHeight: 'min(85vh, 720px)',
          display: 'flex',
          flexDirection: 'column',
          boxSizing: 'border-box',
          position: 'relative',
        }}
      >
        {/* Modal Header */}
        <header
          className="announcement-modal__header"
          style={{
            padding: '16px 20px',
            borderBottom: '1px solid #f1f5f9',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            gap: '12px',
            flexShrink: 0,
            background: '#ffffff',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px', minWidth: 0 }}>
            <div
              className="announcement-modal__icon-badge"
              style={{
                width: '38px',
                height: '38px',
                borderRadius: '10px',
                background: 'linear-gradient(135deg, #e0e7ff 0%, #ede9fe 100%)',
                color: '#4338ca',
                display: 'grid',
                placeItems: 'center',
                flexShrink: 0,
              }}
              aria-hidden="true"
            >
              <Megaphone size={20} />
            </div>

            <div style={{ display: 'flex', alignItems: 'center', gap: '6px', flexWrap: 'wrap' }}>
              <span
                className="notification-item__announcement-badge"
                style={{
                  display: 'inline-flex',
                  alignItems: 'center',
                  padding: '3px 8px',
                  borderRadius: '6px',
                  fontSize: '11px',
                  fontWeight: 700,
                  letterSpacing: '0.06em',
                  textTransform: 'uppercase',
                  background: '#e0e7ff',
                  color: '#4338ca',
                  border: '1px solid #c7d2fe',
                  lineHeight: 1.3,
                }}
              >
                ANNOUNCEMENT
              </span>
              <span
                className="notification-item__source-tag"
                style={{
                  display: 'inline-flex',
                  alignItems: 'center',
                  padding: '3px 7px',
                  borderRadius: '6px',
                  fontSize: '11px',
                  fontWeight: 600,
                  color: '#475569',
                  background: '#f1f5f9',
                  lineHeight: 1.3,
                }}
              >
                From Admin
              </span>
            </div>
          </div>

          <button
            type="button"
            className="announcement-modal__close-btn"
            onClick={onClose}
            aria-label="Close announcement modal"
            title="Close"
            style={{
              width: '34px',
              height: '34px',
              borderRadius: '50%',
              background: '#f1f5f9',
              border: 'none',
              display: 'grid',
              placeItems: 'center',
              cursor: 'pointer',
              color: '#475569',
              flexShrink: 0,
              transition: 'all 0.15s ease',
            }}
          >
            <X size={18} aria-hidden="true" />
          </button>
        </header>

        {/* Modal Scrollable Body */}
        <div
          className="announcement-modal__body"
          style={{
            flex: '1 1 auto',
            overflowY: 'auto',
            overflowX: 'hidden',
            WebkitOverflowScrolling: 'touch',
            padding: '24px',
            display: 'flex',
            flexDirection: 'column',
            gap: '16px',
          }}
        >
          {isLoading ? (
            <div
              className="announcement-modal__loading"
              style={{
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                justifyContent: 'center',
                padding: '48px 0',
                gap: '12px',
                color: '#64748b',
              }}
              aria-live="polite"
            >
              <LoaderCircle size={32} className="animate-spin" style={{ color: '#4f46e5' }} />
              <span style={{ fontSize: '13.5px', fontWeight: 500 }}>
                Loading announcement details…
              </span>
            </div>
          ) : error ? (
            <div
              className="announcement-modal__error"
              style={{
                textAlign: 'center',
                padding: '32px 16px',
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                gap: '10px',
              }}
              role="alert"
            >
              <AlertCircle size={36} style={{ color: '#ef4444' }} />
              <h3 style={{ margin: 0, fontSize: '16px', fontWeight: 700, color: '#0f172a' }}>
                Announcement Unavailable
              </h3>
              <p style={{ margin: 0, fontSize: '13.5px', color: '#64748b', maxWidth: '380px' }}>
                {error}
              </p>
            </div>
          ) : detailData ? (
            <>
              <div>
                <h2
                  id="announcement-modal-title"
                  className="announcement-modal__title"
                  style={{
                    margin: 0,
                    fontSize: '1.25rem',
                    fontWeight: 800,
                    lineHeight: 1.35,
                    color: '#0f172a',
                    wordBreak: 'break-word',
                  }}
                >
                  {detailData.title}
                </h2>

                {detailData.sentAt && (
                  <div
                    className="announcement-modal__timestamp"
                    style={{
                      display: 'flex',
                      alignItems: 'center',
                      gap: '6px',
                      marginTop: '8px',
                      color: '#64748b',
                      fontSize: '12px',
                      fontWeight: 500,
                    }}
                  >
                    <Clock size={13} aria-hidden="true" />
                    <span>Sent: {formatAnnouncementDateTime(detailData.sentAt)}</span>
                  </div>
                )}
              </div>

              <div
                style={{
                  height: '1px',
                  background: '#f1f5f9',
                  width: '100%',
                }}
              />

              {/* Complete, unabridged Admin content */}
              <div
                className="announcement-modal__content"
                style={{
                  fontSize: '14px',
                  lineHeight: '1.7',
                  color: '#334155',
                  whiteSpace: 'pre-wrap',
                  wordBreak: 'break-word',
                  overflowWrap: 'break-word',
                }}
              >
                {detailData.message}
              </div>
            </>
          ) : null}
        </div>

        {/* Modal Footer */}
        <footer
          className="announcement-modal__footer"
          style={{
            padding: '14px 20px',
            borderTop: '1px solid #f1f5f9',
            background: '#f8fafc',
            flexShrink: 0,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'flex-end',
            gap: '12px',
          }}
        >
          <button
            type="button"
            className="member-button member-button--primary announcement-modal__done-btn"
            onClick={onClose}
            style={{
              minWidth: '96px',
              padding: '8px 16px',
              fontSize: '13.5px',
              fontWeight: 600,
              borderRadius: '10px',
            }}
          >
            Close
          </button>
        </footer>
      </div>
    </ModalPortal>
  );
}

export default AnnouncementModal;
