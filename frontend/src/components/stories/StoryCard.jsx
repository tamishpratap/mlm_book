import { Eye, ImageOff } from 'lucide-react';
import { getAvatarUrl, getMediaUrl, getInitials } from '../../utils/assetHelper';
import VerifiedBadge from '../common/VerifiedBadge';
import MemberAvatar from '../common/MemberAvatar';

export function StoryCard({ story, currentMemberId, onOpenViewer, onOpenViewersList }) {
  const author = story.member;
  const isOwner = story.member_id === currentMemberId;
  const viewsCount = isOwner
    ? (story.views_count !== undefined ? story.views_count : (story.views ? story.views.length : 0))
    : null;

  const isImage = story.media_type === 'image';
  const isVideo = story.media_type === 'video';
  const mediaFolder = isVideo ? 'stories/videos' : 'stories/images';
  const mediaUrl = story.media_path
    ? getMediaUrl(story.media_path, mediaFolder)
    : (story.media_url ? getMediaUrl(story.media_url, mediaFolder) : null);
  const authorPhoto = author?.profile_photo ? getAvatarUrl(author.profile_photo) : null;

  const handleCardClick = (e) => {
    e.preventDefault();
    if (onOpenViewer) {
      onOpenViewer(story.id);
    }
  };

  const handleViewersClick = (e) => {
    e.stopPropagation();
    e.preventDefault();
    if (onOpenViewersList) {
      onOpenViewersList(story.id);
    }
  };

  return (
    <div
      className="story story--published"
      role="button"
      tabIndex={0}
      onClick={handleCardClick}
      onKeyDown={(e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          handleCardClick(e);
        }
      }}
      aria-label={`View ${author?.name || 'Member'}'s Story`}
      style={{ cursor: 'pointer', position: 'relative' }}
    >
      {mediaUrl && isImage ? (
        <img
          className="story__cover"
          src={mediaUrl}
          alt={`${author?.name || 'Member'}'s Story`}
          loading="lazy"
          onError={(e) => {
            e.currentTarget.style.display = 'none';
          }}
        />
      ) : mediaUrl && isVideo ? (
        <video
          className="story__cover"
          src={mediaUrl}
          muted
          playsInline
          preload="metadata"
          aria-label={`${author?.name || 'Member'}'s Story video`}
        />
      ) : (
        <span className="story__media-unavailable">
          <ImageOff size={28} aria-hidden="true" />
        </span>
      )}

      <span className="story__overlay" aria-hidden="true" />

      <MemberAvatar
        member={author}
        size={36}
        className="story__avatar"
      />

      {isOwner && (
        <span className="story__owner" aria-label="Your Story">Your Story</span>
      )}

      {isOwner && (
        <button
          className="story__views-badge story__views-badge--owner"
          type="button"
          title="Viewers list"
          aria-label={`Seen by ${viewsCount}`}
          onClick={handleViewersClick}
        >
          <Eye size={12} aria-hidden="true" />
          <span>Seen by {viewsCount}</span>
        </button>
      )}

      {isOwner ? (
        <strong>Your Story</strong>
      ) : (
        <strong style={{ display: 'inline-flex', alignItems: 'center', gap: '3px' }}>
          <span>{author?.name || 'Member'}</span>
          <VerifiedBadge member={author} size={12} />
        </strong>
      )}
    </div>
  );
}

export default StoryCard;
