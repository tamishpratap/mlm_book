import { useState } from 'react';
import {
  Star,
  ThumbsUp,
  ThumbsDown,
  Building2,
  Flag,
  Trash2,
  Edit3,
  MessageSquare,
  Eye,
  EyeOff,
} from 'lucide-react';
import { Link } from 'react-router-dom';
import MemberAvatar from '../common/MemberAvatar';

function getInitials(name) {
  if (!name) return 'U';
  const parts = name.trim().split(/\s+/);
  return parts.slice(0, 2).map((p) => p[0].toUpperCase()).join('') || 'U';
}

function formatDate(dateString) {
  if (!dateString) return '';
  const d = new Date(dateString);
  return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
}

export function BusinessReviewCard({
  review,
  currentMemberId,
  pageName,
  isAdmin,
  onVote,
  onEdit,
  onDelete,
  onReply,
  onReport,
  onToggleHide,
}) {
  const [isVoting, setIsVoting] = useState(false);

  const member = review.member || {};
  const isAuthor = Number(review.member_id) === Number(currentMemberId);
  const isRecommended = review.recommendation === 'recommend';
  const photos = Array.isArray(review.photos) ? review.photos : [];
  const officialReply = review.official_reply || review.officialReply;
  const userVote = review.user_vote;

  const handleVoteClick = async (type) => {
    if (isVoting || !onVote) return;
    setIsVoting(true);
    try {
      await onVote(review.id, type);
    } finally {
      setIsVoting(false);
    }
  };

  return (
    <div
      className="card"
      style={{
        padding: '20px',
        borderRadius: '16px',
        background: '#fff',
        border: '1px solid #e7ecf4',
        opacity: review.is_hidden ? 0.6 : 1,
        borderStyle: review.is_hidden ? 'dashed' : 'solid',
        display: 'flex',
        flexDirection: 'column',
        gap: '14px',
      }}
    >
      {/* Header */}
      <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: '14px', flexWrap: 'wrap' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
          <MemberAvatar member={member} size={42} />
          <div>
            <Link
              to={`/member/people/${member.id}`}
              style={{ fontWeight: 700, fontSize: '14.5px', color: '#1d2738', textDecoration: 'none', display: 'block' }}
            >
              {member.name}
            </Link>
            <span style={{ fontSize: '12px', color: '#98a2b3' }}>
              {formatDate(review.created_at)}
            </span>
          </div>
        </div>

        {/* Rating Stars & Recommendation */}
        <div style={{ textAlign: 'right' }}>
          <div style={{ display: 'flex', gap: '2px', justifyContent: 'flex-end' }}>
            {[1, 2, 3, 4, 5].map((star) => (
              <Star
                key={star}
                size={15}
                color="#f7b940"
                fill={star <= review.rating ? '#f7b940' : 'none'}
              />
            ))}
          </div>
          <span
            className={`biz-badge ${isRecommended ? 'biz-badge--verified' : 'biz-badge--unverified'}`}
            style={{
              fontSize: '11px',
              padding: '2px 8px',
              marginTop: '4px',
              display: 'inline-flex',
              alignItems: 'center',
              gap: '4px',
              background: isRecommended ? 'rgba(32, 200, 117, 0.1)' : 'rgba(239, 68, 68, 0.1)',
              color: isRecommended ? '#20c875' : '#ef4444',
              borderRadius: '8px',
              fontWeight: 600,
            }}
          >
            {isRecommended ? (
              <>
                <ThumbsUp size={11} />
                <span>Recommends</span>
              </>
            ) : (
              <>
                <ThumbsDown size={11} />
                <span>Doesn&apos;t Recommend</span>
              </>
            )}
          </span>
        </div>
      </div>

      {/* Title & Body */}
      {review.title && (
        <h4 style={{ fontSize: '15px', fontWeight: 700, color: '#1d2738', margin: 0 }}>
          {review.title}
        </h4>
      )}
      <p style={{ fontSize: '14px', color: '#334155', lineHeight: 1.6, margin: 0, whiteSpace: 'pre-line' }}>
        {review.body}
      </p>

      {/* Review Photos Gallery */}
      {photos.length > 0 && (
        <div style={{ display: 'flex', gap: '8px', flexWrap: 'wrap' }}>
          {photos.map((ph, idx) => (
            <a
              key={idx}
              href={ph.startsWith('http') ? ph : `/${ph}`}
              target="_blank"
              rel="noopener noreferrer"
              style={{ width: '70px', height: '70px', borderRadius: '10px', overflow: 'hidden', border: '1px solid #e7ecf4', display: 'block' }}
            >
              <img
                src={ph.startsWith('http') ? ph : `/${ph}`}
                alt={`Review photo ${idx + 1}`}
                style={{ width: '100%', height: '100%', objectFit: 'cover' }}
              />
            </a>
          ))}
        </div>
      )}

      {/* Official Team/Owner Reply */}
      {officialReply && (
        <div
          style={{
            padding: '14px',
            borderRadius: '12px',
            background: 'rgba(79, 125, 243, 0.05)',
            borderLeft: '3px solid #4f7df3',
            display: 'flex',
            flexDirection: 'column',
            gap: '6px',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '6px' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
              <Building2 size={16} color="#4f7df3" />
              <strong style={{ fontSize: '13px', color: '#4f7df3' }}>
                Official Reply from {pageName}
              </strong>
            </div>
            <span style={{ fontSize: '11px', color: '#98a2b3' }}>
              {formatDate(officialReply.created_at)}
            </span>
          </div>
          <p style={{ fontSize: '13.5px', color: '#1e293b', margin: 0, whiteSpace: 'pre-line' }}>
            {officialReply.reply}
          </p>
        </div>
      )}

      {/* Footer Controls */}
      <div
        style={{
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
          paddingTop: '10px',
          borderTop: '1px solid #f1f5f9',
          flexWrap: 'wrap',
          gap: '10px',
        }}
      >
        {/* Helpful & Unhelpful Vote Buttons */}
        <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
          <button
            type="button"
            className="mini-button"
            style={{
              background: 'transparent',
              border: 'none',
              cursor: 'pointer',
              display: 'inline-flex',
              alignItems: 'center',
              gap: '6px',
              color: userVote === 'helpful' ? '#4f7df3' : '#64748b',
              fontWeight: 600,
              fontSize: '12.5px',
            }}
            disabled={isVoting}
            onClick={() => handleVoteClick('helpful')}
          >
            <ThumbsUp size={14} />
            <span>Helpful ({review.helpful_count ?? 0})</span>
          </button>

          <button
            type="button"
            className="mini-button"
            style={{
              background: 'transparent',
              border: 'none',
              cursor: 'pointer',
              display: 'inline-flex',
              alignItems: 'center',
              gap: '6px',
              color: userVote === 'unhelpful' ? '#e53e3e' : '#64748b',
              fontWeight: 600,
              fontSize: '12.5px',
            }}
            disabled={isVoting}
            onClick={() => handleVoteClick('unhelpful')}
          >
            <ThumbsDown size={14} />
            <span>Unhelpful ({review.unhelpful_count ?? 0})</span>
          </button>
        </div>

        {/* Options / Moderation Actions */}
        <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
          {isAdmin && (
            <>
              <button
                type="button"
                className="mini-button"
                style={{
                  background: 'transparent',
                  border: 'none',
                  cursor: 'pointer',
                  display: 'inline-flex',
                  alignItems: 'center',
                  gap: '4px',
                  color: '#4f7df3',
                  fontSize: '12px',
                }}
                onClick={() => onReply(review)}
              >
                <MessageSquare size={13} />
                <span>{officialReply ? 'Edit Reply' : 'Official Reply'}</span>
              </button>

              <button
                type="button"
                className="mini-button"
                style={{
                  background: 'transparent',
                  border: 'none',
                  cursor: 'pointer',
                  display: 'inline-flex',
                  alignItems: 'center',
                  gap: '4px',
                  color: '#64748b',
                  fontSize: '12px',
                }}
                onClick={() => onToggleHide(review.id)}
              >
                {review.is_hidden ? <Eye size={13} /> : <EyeOff size={13} />}
                <span>{review.is_hidden ? 'Unhide' : 'Hide'}</span>
              </button>
            </>
          )}

          {isAuthor && (
            <button
              type="button"
              className="mini-button"
              style={{
                background: 'transparent',
                border: 'none',
                cursor: 'pointer',
                display: 'inline-flex',
                alignItems: 'center',
                gap: '4px',
                color: '#4f7df3',
                fontSize: '12px',
              }}
              onClick={() => onEdit(review)}
            >
              <Edit3 size={13} />
              <span>Edit</span>
            </button>
          )}

          <button
            type="button"
            className="mini-button"
            style={{
              background: 'transparent',
              border: 'none',
              cursor: 'pointer',
              display: 'inline-flex',
              alignItems: 'center',
              gap: '4px',
              color: '#94a3b8',
              fontSize: '12px',
            }}
            onClick={() => onReport(review.id)}
          >
            <Flag size={13} />
            <span>Report</span>
          </button>

          {(isAuthor || isAdmin) && (
            <button
              type="button"
              className="mini-button"
              style={{
                background: 'transparent',
                border: 'none',
                cursor: 'pointer',
                display: 'inline-flex',
                alignItems: 'center',
                gap: '4px',
                color: '#e53e3e',
                fontSize: '12px',
              }}
              onClick={() => onDelete(review.id)}
            >
              <Trash2 size={13} />
              <span>Delete</span>
            </button>
          )}
        </div>
      </div>
    </div>
  );
}

export default BusinessReviewCard;
