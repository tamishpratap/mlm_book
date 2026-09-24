import { useState, useEffect, useCallback } from 'react';
import {
  MessageSquareText,
  Send,
  CheckCircle2,
  AlertCircle,
  Loader2,
  Clock,
  Sparkles,
  Inbox,
  RotateCcw,
} from 'lucide-react';
import feedbackApi from '../../api/feedbackApi';

const FEEDBACK_TYPES = [
  { value: 'feedback', label: 'General Feedback', description: 'Share your overall thoughts about MLM Book' },
  { value: 'suggestion', label: 'Suggestion', description: 'Suggest a tweak, optimization, or process change' },
  { value: 'idea', label: 'Idea / Feature Proposal', description: 'Propose a new feature, tool, or capability' },
  { value: 'other', label: 'Other Inquiry', description: 'General platform question or other input' },
];

const STATUS_CONFIG = {
  new: { label: 'New', bg: '#eff6ff', color: '#1d4ed8', border: '#bfdbfe' },
  in_review: { label: 'In Review', bg: '#fffbeb', color: '#b45309', border: '#fde68a' },
  resolved: { label: 'Resolved', bg: '#ecfdf5', color: '#15803d', border: '#a7f3d0' },
  closed: { label: 'Closed', bg: '#f3f4f6', color: '#4b5563', border: '#e5e7eb' },
};

export function FeedbackSuggestionsPage() {
  const [formData, setFormData] = useState({
    type: 'feedback',
    subject: '',
    message: '',
  });

  const [fieldErrors, setFieldErrors] = useState({});
  const [generalError, setGeneralError] = useState(null);
  const [successMessage, setSuccessMessage] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Member history / past submissions state
  const [myFeedbacks, setMyFeedbacks] = useState([]);
  const [isLoadingHistory, setIsLoadingHistory] = useState(true);
  const [historyError, setHistoryError] = useState(null);

  const fetchMyFeedbacks = useCallback(async () => {
    try {
      setIsLoadingHistory(true);
      const res = await feedbackApi.getMyFeedbacks();
      if (res && res.success && Array.isArray(res.feedbacks)) {
        setMyFeedbacks(res.feedbacks);
        setHistoryError(null);
      }
    } catch (err) {
      setHistoryError(err.response?.data?.message || 'Could not load past feedback history.');
    } finally {
      setIsLoadingHistory(false);
    }
  }, []);

  useEffect(() => {
    fetchMyFeedbacks();
  }, [fetchMyFeedbacks]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    if (fieldErrors[name]) {
      setFieldErrors((prev) => {
        const updated = { ...prev };
        delete updated[name];
        return updated;
      });
    }
    if (generalError) {
      setGeneralError(null);
    }
  };

  const handleReset = () => {
    setFormData({
      type: 'feedback',
      subject: '',
      message: '',
    });
    setFieldErrors({});
    setGeneralError(null);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setGeneralError(null);
    setSuccessMessage(null);
    setFieldErrors({});

    // Client-side baseline validation
    const errors = {};
    if (!formData.type) {
      errors.type = ['Please select a feedback type.'];
    }
    if (!formData.subject.trim()) {
      errors.subject = ['The subject field is required.'];
    } else if (formData.subject.trim().length > 191) {
      errors.subject = ['Subject must not exceed 191 characters.'];
    }
    if (!formData.message.trim()) {
      errors.message = ['The message details field is required.'];
    } else if (formData.message.trim().length < 5) {
      errors.message = ['Message must be at least 5 characters long.'];
    } else if (formData.message.trim().length > 5000) {
      errors.message = ['Message must not exceed 5000 characters.'];
    }

    if (Object.keys(errors).length > 0) {
      setFieldErrors(errors);
      return;
    }

    setIsSubmitting(true);

    try {
      const payload = {
        type: formData.type,
        subject: formData.subject.trim(),
        message: formData.message.trim(),
      };

      const res = await feedbackApi.submitFeedback(payload);

      if (res && res.success) {
        setSuccessMessage(res.message || 'Thank you! Your feedback has been submitted successfully.');
        handleReset();
        // Refresh history to show newly submitted item immediately
        fetchMyFeedbacks();
      } else {
        setGeneralError(res?.message || 'Failed to submit feedback. Please try again.');
      }
    } catch (err) {
      if (err.response?.status === 422 && err.response?.data?.errors) {
        setFieldErrors(err.response.data.errors);
        setGeneralError('Please correct the errors in the form.');
      } else {
        setGeneralError(
          err.response?.data?.message || 'Failed to submit your feedback. Please check your network and try again.'
        );
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  const remainingChars = 5000 - formData.message.length;

  return (
    <div
      className="feedback-page-wrapper"
      style={{
        maxWidth: '860px',
        margin: '20px auto',
        padding: '0 16px',
        boxSizing: 'border-box',
        width: '100%',
      }}
    >
      {/* PAGE HEADER */}
      <header
        className="member-page-heading"
        style={{
          marginBottom: '24px',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
          flexWrap: 'wrap',
          gap: '12px',
        }}
      >
        <div className="member-page-heading__content">
          <h1
            style={{
              fontSize: '24px',
              fontWeight: 700,
              margin: '0 0 6px 0',
              color: 'var(--color-text-main, #1e293b)',
              display: 'flex',
              alignItems: 'center',
              gap: '10px',
            }}
          >
            <MessageSquareText size={26} color="#176bff" />
            <span>Feedback & Suggestions</span>
          </h1>
          <p
            style={{
              margin: 0,
              color: 'var(--color-text-secondary, #64748b)',
              fontSize: '14px',
            }}
          >
            Share your thoughts, suggestions, or feature proposals to help us improve MLM Book.
          </p>
        </div>
      </header>

      {/* TOP NOTIFICATIONS */}
      {successMessage && (
        <div
          role="alert"
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: '10px',
            padding: '14px 18px',
            background: '#ecfdf5',
            color: '#15803d',
            borderRadius: '12px',
            border: '1px solid #a7f3d0',
            marginBottom: '20px',
            fontSize: '14px',
            fontWeight: 500,
          }}
        >
          <CheckCircle2 size={20} flexShrink={0} />
          <div style={{ flex: 1 }}>{successMessage}</div>
        </div>
      )}

      {generalError && (
        <div
          role="alert"
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: '10px',
            padding: '14px 18px',
            background: '#fef2f2',
            color: '#b91c1c',
            borderRadius: '12px',
            border: '1px solid #fecaca',
            marginBottom: '20px',
            fontSize: '14px',
            fontWeight: 500,
          }}
        >
          <AlertCircle size={20} flexShrink={0} />
          <div style={{ flex: 1 }}>{generalError}</div>
        </div>
      )}

      {/* FORM CARD */}
      <section
        className="member-card feedback-form-card"
        style={{
          background: '#ffffff',
          borderRadius: '16px',
          border: '1px solid #e5e7eb',
          padding: '24px',
          marginBottom: '28px',
          boxShadow: '0 1px 3px rgba(0, 0, 0, 0.04)',
          boxSizing: 'border-box',
          width: '100%',
        }}
      >
        <header
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: '12px',
            marginBottom: '20px',
            paddingBottom: '14px',
            borderBottom: '1px solid #f1f5f9',
          }}
        >
          <div
            style={{
              width: '40px',
              height: '40px',
              borderRadius: '10px',
              background: 'linear-gradient(135deg, rgba(23, 107, 255, 0.1), rgba(113, 70, 237, 0.1))',
              color: '#176bff',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              flexShrink: 0,
            }}
          >
            <Sparkles size={20} />
          </div>
          <div>
            <h2 style={{ fontSize: '17px', fontWeight: 700, margin: 0, color: '#111827' }}>
              Send Platform Input
            </h2>
            <p style={{ margin: '2px 0 0 0', fontSize: '13px', color: '#6b7280' }}>
              All submissions are reviewed directly by the MLM Book administration team.
            </p>
          </div>
        </header>

        <form onSubmit={handleSubmit} noValidate style={{ display: 'flex', flexDirection: 'column', gap: '20px' }}>
          {/* 1. FEEDBACK TYPE */}
          <div>
            <label
              htmlFor="feedback-type"
              style={{
                display: 'block',
                fontSize: '13px',
                fontWeight: 600,
                color: '#374151',
                marginBottom: '6px',
              }}
            >
              Type <span style={{ color: '#ef4444' }}>*</span>
            </label>
            <select
              id="feedback-type"
              name="type"
              value={formData.type}
              onChange={handleChange}
              disabled={isSubmitting}
              className="form-control"
              style={{
                width: '100%',
                padding: '11px 14px',
                borderRadius: '10px',
                border: fieldErrors.type ? '1px solid #ef4444' : '1px solid #d1d5db',
                fontSize: '14px',
                background: '#ffffff',
                color: '#1f2937',
                outline: 'none',
                boxSizing: 'border-box',
              }}
            >
              {FEEDBACK_TYPES.map((t) => (
                <option key={t.value} value={t.value}>
                  {t.label}
                </option>
              ))}
            </select>
            {fieldErrors.type && (
              <p style={{ color: '#dc2626', fontSize: '12px', marginTop: '5px', margin: '5px 0 0 0' }}>
                {fieldErrors.type[0]}
              </p>
            )}
          </div>

          {/* 2. SUBJECT */}
          <div>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '6px' }}>
              <label
                htmlFor="feedback-subject"
                style={{
                  fontSize: '13px',
                  fontWeight: 600,
                  color: '#374151',
                }}
              >
                Subject <span style={{ color: '#ef4444' }}>*</span>
              </label>
              <span style={{ fontSize: '12px', color: '#9ca3af' }}>{formData.subject.length} / 191</span>
            </div>
            <input
              id="feedback-subject"
              type="text"
              name="subject"
              value={formData.subject}
              onChange={handleChange}
              maxLength={191}
              placeholder="e.g., Idea for community member discovery"
              disabled={isSubmitting}
              className="form-control"
              style={{
                width: '100%',
                padding: '11px 14px',
                borderRadius: '10px',
                border: fieldErrors.subject ? '1px solid #ef4444' : '1px solid #d1d5db',
                fontSize: '14px',
                color: '#1f2937',
                outline: 'none',
                boxSizing: 'border-box',
              }}
            />
            {fieldErrors.subject && (
              <p style={{ color: '#dc2626', fontSize: '12px', marginTop: '5px', margin: '5px 0 0 0' }}>
                {fieldErrors.subject[0]}
              </p>
            )}
          </div>

          {/* 3. MESSAGE / DETAILS */}
          <div>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '6px' }}>
              <label
                htmlFor="feedback-message"
                style={{
                  fontSize: '13px',
                  fontWeight: 600,
                  color: '#374151',
                }}
              >
                Message / Details <span style={{ color: '#ef4444' }}>*</span>
              </label>
              <span
                style={{
                  fontSize: '12px',
                  color: remainingChars < 100 ? '#ef4444' : '#9ca3af',
                }}
              >
                {formData.message.length} / 5000
              </span>
            </div>
            <textarea
              id="feedback-message"
              name="message"
              value={formData.message}
              onChange={handleChange}
              rows={6}
              maxLength={5000}
              placeholder="Provide a clear description of your feedback, suggestion, or idea..."
              disabled={isSubmitting}
              className="form-control"
              style={{
                width: '100%',
                padding: '12px 14px',
                borderRadius: '10px',
                border: fieldErrors.message ? '1px solid #ef4444' : '1px solid #d1d5db',
                fontSize: '14px',
                color: '#1f2937',
                lineHeight: 1.5,
                outline: 'none',
                resize: 'vertical',
                boxSizing: 'border-box',
                minHeight: '120px',
              }}
            />
            {fieldErrors.message && (
              <p style={{ color: '#dc2626', fontSize: '12px', marginTop: '5px', margin: '5px 0 0 0' }}>
                {fieldErrors.message[0]}
              </p>
            )}
          </div>

          {/* FORM ACTIONS */}
          <div
            style={{
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'flex-end',
              gap: '12px',
              paddingTop: '8px',
              flexWrap: 'wrap',
            }}
          >
            {(formData.subject || formData.message) && (
              <button
                type="button"
                onClick={handleReset}
                disabled={isSubmitting}
                className="member-button member-button--secondary"
                style={{
                  minHeight: '42px',
                  padding: '0 16px',
                  fontSize: '13px',
                  cursor: 'pointer',
                }}
              >
                <RotateCcw size={15} />
                <span>Reset</span>
              </button>
            )}

            <button
              type="submit"
              disabled={isSubmitting}
              className="member-button member-button--primary"
              style={{
                minHeight: '42px',
                padding: '0 22px',
                fontSize: '14px',
                display: 'inline-flex',
                alignItems: 'center',
                gap: '8px',
                cursor: isSubmitting ? 'not-allowed' : 'pointer',
                opacity: isSubmitting ? 0.75 : 1,
              }}
            >
              {isSubmitting ? (
                <>
                  <Loader2 size={16} className="spin-icon" />
                  <span>Submitting...</span>
                </>
              ) : (
                <>
                  <Send size={16} />
                  <span>Submit Feedback</span>
                </>
              )}
            </button>
          </div>
        </form>
      </section>

      {/* MY PAST SUBMISSIONS SECTION */}
      <section
        className="member-card feedback-history-card"
        style={{
          background: '#ffffff',
          borderRadius: '16px',
          border: '1px solid #e5e7eb',
          padding: '24px',
          boxShadow: '0 1px 3px rgba(0, 0, 0, 0.04)',
          boxSizing: 'border-box',
          width: '100%',
        }}
      >
        <header
          style={{
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            marginBottom: '16px',
            paddingBottom: '12px',
            borderBottom: '1px solid #f1f5f9',
            flexWrap: 'wrap',
            gap: '8px',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
            <Clock size={18} color="#64748b" />
            <h2 style={{ fontSize: '16px', fontWeight: 700, margin: 0, color: '#111827' }}>
              My Submissions
            </h2>
            {myFeedbacks.length > 0 && (
              <span
                style={{
                  background: '#f1f5f9',
                  color: '#475569',
                  borderRadius: '20px',
                  padding: '2px 8px',
                  fontSize: '12px',
                  fontWeight: 600,
                }}
              >
                {myFeedbacks.length}
              </span>
            )}
          </div>
        </header>

        {isLoadingHistory ? (
          <div
            style={{
              padding: '36px 0',
              textAlign: 'center',
              color: '#94a3b8',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '8px',
            }}
          >
            <Loader2 size={18} className="spin-icon" />
            <span style={{ fontSize: '13px' }}>Loading submissions...</span>
          </div>
        ) : historyError ? (
          <div
            style={{
              padding: '12px 16px',
              background: '#fef2f2',
              color: '#b91c1c',
              borderRadius: '10px',
              fontSize: '13px',
            }}
          >
            {historyError}
          </div>
        ) : myFeedbacks.length === 0 ? (
          <div
            style={{
              padding: '36px 20px',
              textAlign: 'center',
              color: '#94a3b8',
              display: 'flex',
              flexDirection: 'column',
              alignItems: 'center',
              gap: '10px',
            }}
          >
            <Inbox size={36} strokeWidth={1.5} color="#cbd5e1" />
            <p style={{ margin: 0, fontSize: '14px', color: '#64748b', fontWeight: 500 }}>
              No submissions yet
            </p>
            <p style={{ margin: 0, fontSize: '12px', color: '#94a3b8' }}>
              When you submit feedback or ideas, you can track review progress and admin replies here.
            </p>
          </div>
        ) : (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '14px' }}>
            {myFeedbacks.map((item) => {
              const status = STATUS_CONFIG[item.status] || STATUS_CONFIG.new;

              return (
                <article
                  key={item.id}
                  style={{
                    background: '#f8fafc',
                    borderRadius: '12px',
                    border: '1px solid #e2e8f0',
                    padding: '16px',
                    boxSizing: 'border-box',
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '10px',
                  }}
                >
                  {/* Item top metadata bar */}
                  <div
                    style={{
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'space-between',
                      flexWrap: 'wrap',
                      gap: '8px',
                    }}
                  >
                    <div style={{ display: 'flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' }}>
                      <span
                        style={{
                          background: '#e0e7ff',
                          color: '#4338ca',
                          fontSize: '11px',
                          fontWeight: 700,
                          padding: '3px 9px',
                          borderRadius: '6px',
                          textTransform: 'uppercase',
                          letterSpacing: '0.4px',
                        }}
                      >
                        {item.type_label || item.type}
                      </span>
                      <span
                        style={{
                          background: status.bg,
                          color: status.color,
                          border: `1px solid ${status.border}`,
                          fontSize: '11px',
                          fontWeight: 600,
                          padding: '2px 8px',
                          borderRadius: '6px',
                        }}
                      >
                        {status.label}
                      </span>
                    </div>

                    <span style={{ fontSize: '12px', color: '#94a3b8' }}>
                      {item.created_at_human || (item.created_at ? new Date(item.created_at).toLocaleDateString() : '')}
                    </span>
                  </div>

                  {/* Subject & Message */}
                  <div>
                    <h3
                      style={{
                        margin: '0 0 6px 0',
                        fontSize: '15px',
                        fontWeight: 700,
                        color: '#0f172a',
                        wordBreak: 'break-word',
                      }}
                    >
                      {item.subject}
                    </h3>
                    <p
                      style={{
                        margin: 0,
                        fontSize: '13px',
                        color: '#334155',
                        whiteSpace: 'pre-wrap',
                        lineHeight: 1.5,
                        wordBreak: 'break-word',
                      }}
                    >
                      {item.message}
                    </p>
                  </div>

                  {/* Admin Response Box (if responded) */}
                  {item.admin_response && (
                    <div
                      style={{
                        marginTop: '6px',
                        background: '#ffffff',
                        borderRadius: '10px',
                        border: '1px solid #cbd5e1',
                        borderLeft: '4px solid #10b981',
                        padding: '12px 14px',
                      }}
                    >
                      <div
                        style={{
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'space-between',
                          marginBottom: '4px',
                        }}
                      >
                        <strong style={{ fontSize: '12px', color: '#047857', display: 'flex', alignItems: 'center', gap: '5px' }}>
                          <CheckCircle2 size={13} />
                          <span>Admin Response</span>
                        </strong>
                        {item.responded_at_human && (
                          <span style={{ fontSize: '11px', color: '#94a3b8' }}>{item.responded_at_human}</span>
                        )}
                      </div>
                      <p
                        style={{
                          margin: 0,
                          fontSize: '13px',
                          color: '#1e293b',
                          whiteSpace: 'pre-wrap',
                          lineHeight: 1.45,
                          wordBreak: 'break-word',
                        }}
                      >
                        {item.admin_response}
                      </p>
                    </div>
                  )}
                </article>
              );
            })}
          </div>
        )}
      </section>
    </div>
  );
}

export default FeedbackSuggestionsPage;
