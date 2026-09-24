/**
 * MLM BOOK AI - Reusable Image Moderation Scan & Policy Feedback Modal
 * Phase 4: Accessible Client-Side Moderation User Experience
 *
 * Capabilities:
 * - Built on ModalPortal for full-viewport backdrop & body scroll locking
 * - Accessible: ARIA dialog role, aria-live polite status announcements, keyboard focus management
 * - High-Contrast Non-Color-Only Indicators: Icons, clear typography, explicit action labels
 * - States: SCANNING (with progress bar for batches), BLOCKED (respectful rejection), ERROR (retryable)
 * - Zero Technical Leaks: Never exposes raw probabilities, TensorFlow internals, or stack traces
 */

import { useEffect } from 'react';
import { ShieldAlert, Loader2, AlertCircle, X, RefreshCw } from 'lucide-react';
import { ModalPortal } from '../common/ModalPortal';

export function ImageModerationScanModal({
  isOpen,
  status = 'IDLE', // 'SCANNING' | 'BLOCKED' | 'ERROR' | 'IDLE'
  progress = { completed: 0, total: 1, percentage: 0, currentFileName: '' },
  decision = null,
  error = null,
  blockedItems = [],
  onCancel = null,
  onRetry = null,
  onAcknowledge = null,
}) {
  // Handle Escape key to cancel/dismiss
  useEffect(() => {
    if (!isOpen) return;
    const handleKeyDown = (e) => {
      if (e.key === 'Escape') {
        if (status === 'SCANNING' && onCancel) {
          onCancel();
        } else if (onAcknowledge) {
          onAcknowledge();
        }
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, status, onCancel, onAcknowledge]);

  if (!isOpen || status === 'IDLE') return null;

  const isScanning = status === 'SCANNING';
  const isBlocked = status === 'BLOCKED';
  const isError = status === 'ERROR';
  const isMulti = progress.total > 1;

  return (
    <ModalPortal isOpen={isOpen} onClose={isScanning ? onCancel : onAcknowledge} depth={10}>
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="moderation-scan-title"
        aria-describedby="moderation-scan-desc"
        style={{
          background: '#ffffff',
          borderRadius: '16px',
          width: '100%',
          maxWidth: '440px',
          padding: '28px 24px',
          boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          textAlign: 'center',
          position: 'relative',
          fontFamily: 'inherit',
          color: '#1e293b',
          margin: '16px',
        }}
      >
        {/* Close Button (when not scanning) */}
        {!isScanning && (
          <button
            type="button"
            onClick={onAcknowledge}
            aria-label="Close dialog"
            style={{
              position: 'absolute',
              top: '16px',
              right: '16px',
              background: 'transparent',
              border: 'none',
              cursor: 'pointer',
              color: '#94a3b8',
              padding: '4px',
              borderRadius: '8px',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
            }}
          >
            <X size={18} />
          </button>
        )}

        {/* State Icon */}
        <div style={{ marginBottom: '16px' }}>
          {isScanning && (
            <div
              style={{
                width: '56px',
                height: '56px',
                borderRadius: '50%',
                background: 'rgba(79, 70, 229, 0.1)',
                color: '#4f46e5',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
              }}
            >
              <Loader2 size={28} className="animate-spin" style={{ animation: 'spin 1s linear infinite' }} />
            </div>
          )}

          {isBlocked && (
            <div
              style={{
                width: '56px',
                height: '56px',
                borderRadius: '50%',
                background: 'rgba(239, 68, 68, 0.1)',
                color: '#ef4444',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
              }}
            >
              <ShieldAlert size={30} />
            </div>
          )}

          {isError && (
            <div
              style={{
                width: '56px',
                height: '56px',
                borderRadius: '50%',
                background: 'rgba(245, 158, 11, 0.1)',
                color: '#f59e0b',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
              }}
            >
              <AlertCircle size={30} />
            </div>
          )}
        </div>

        {/* Title */}
        <h2
          id="moderation-scan-title"
          style={{
            fontSize: '1.2rem',
            fontWeight: 700,
            margin: '0 0 8px 0',
            color: isBlocked ? '#991b1b' : '#0f172a',
          }}
        >
          {isScanning
            ? isMulti
              ? `Verifying Photos (${progress.completed}/${progress.total})`
              : 'Checking Image Safety'
            : isBlocked
              ? 'Upload Blocked'
              : 'Verification Interrupted'}
        </h2>

        {/* Description / Feedback */}
        <p
          id="moderation-scan-desc"
          style={{
            fontSize: '0.9rem',
            lineHeight: 1.5,
            color: '#64748b',
            margin: '0 0 20px 0',
          }}
          aria-live="polite"
        >
          {isScanning ? (
            isMulti ? (
              <>
                Scanning{' '}
                <span style={{ fontWeight: 600, color: '#334155' }}>
                  {progress.currentFileName || `image ${progress.completed + 1}`}
                </span>{' '}
                against community standards...
              </>
            ) : (
              'Scanning your image locally before upload to ensure it complies with community guidelines...'
            )
          ) : isBlocked ? (
            decision?.userMessage ||
            'This image cannot be uploaded because it appears to violate our community safety guidelines.'
          ) : (
            error || 'We were unable to verify your image. Please check your network connection and try again.'
          )}
        </p>

        {/* Multi-Item Blocked List */}
        {isBlocked && blockedItems && blockedItems.length > 0 && (
          <div
            style={{
              width: '100%',
              background: '#fef2f2',
              border: '1px solid #fecaca',
              borderRadius: '8px',
              padding: '12px 14px',
              marginBottom: '20px',
              textAlign: 'left',
              fontSize: '0.85rem',
            }}
          >
            <div style={{ fontWeight: 600, color: '#991b1b', marginBottom: '6px' }}>
              Restricted file{blockedItems.length > 1 ? 's' : ''}:
            </div>
            <ul style={{ margin: 0, paddingLeft: '18px', color: '#b91c1c' }}>
              {blockedItems.map((item, idx) => (
                <li key={idx} style={{ marginBottom: '2px' }}>
                  {item.fileName}
                </li>
              ))}
            </ul>
          </div>
        )}

        {/* Progress Bar (during scanning) */}
        {isScanning && (
          <div
            style={{
              width: '100%',
              marginBottom: '20px',
            }}
          >
            <div
              role="progressbar"
              aria-valuenow={progress.percentage}
              aria-valuemin="0"
              aria-valuemax="100"
              style={{
                width: '100%',
                height: '6px',
                background: '#e2e8f0',
                borderRadius: '999px',
                overflow: 'hidden',
              }}
            >
              <div
                style={{
                  width: `${Math.max(5, progress.percentage)}%`,
                  height: '100%',
                  background: '#4f46e5',
                  transition: 'width 0.3s ease-in-out',
                }}
              />
            </div>
            {isMulti && (
              <div
                style={{
                  display: 'flex',
                  justifyContent: 'space-between',
                  fontSize: '0.75rem',
                  color: '#94a3b8',
                  marginTop: '6px',
                }}
              >
                <span>{progress.completed} of {progress.total} completed</span>
                <span>{progress.percentage}%</span>
              </div>
            )}
          </div>
        )}

        {/* Action Buttons */}
        <div
          style={{
            display: 'flex',
            gap: '10px',
            width: '100%',
            justifyContent: 'center',
          }}
        >
          {isScanning && onCancel && (
            <button
              type="button"
              onClick={onCancel}
              className="member-button member-button--secondary"
              style={{
                flex: 1,
                padding: '10px 16px',
                fontSize: '0.875rem',
                fontWeight: 600,
                borderRadius: '8px',
                border: '1px solid #cbd5e1',
                background: '#ffffff',
                color: '#475569',
                cursor: 'pointer',
              }}
            >
              Cancel Scan
            </button>
          )}

          {isBlocked && (
            <button
              type="button"
              onClick={onAcknowledge}
              className="member-button member-button--primary"
              style={{
                width: '100%',
                padding: '10px 16px',
                fontSize: '0.875rem',
                fontWeight: 600,
                borderRadius: '8px',
                background: '#ef4444',
                color: '#ffffff',
                border: 'none',
                cursor: 'pointer',
              }}
            >
              Remove Blocked File
            </button>
          )}

          {isError && (
            <>
              {onCancel && (
                <button
                  type="button"
                  onClick={onCancel}
                  style={{
                    flex: 1,
                    padding: '10px 16px',
                    fontSize: '0.875rem',
                    fontWeight: 600,
                    borderRadius: '8px',
                    border: '1px solid #cbd5e1',
                    background: '#ffffff',
                    color: '#475569',
                    cursor: 'pointer',
                  }}
                >
                  Cancel
                </button>
              )}
              {onRetry && (
                <button
                  type="button"
                  onClick={onRetry}
                  style={{
                    flex: 1,
                    padding: '10px 16px',
                    fontSize: '0.875rem',
                    fontWeight: 600,
                    borderRadius: '8px',
                    background: '#4f46e5',
                    color: '#ffffff',
                    border: 'none',
                    cursor: 'pointer',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    gap: '6px',
                  }}
                >
                  <RefreshCw size={14} />
                  <span>Retry</span>
                </button>
              )}
            </>
          )}
        </div>
      </div>
    </ModalPortal>
  );
}

export default ImageModerationScanModal;
