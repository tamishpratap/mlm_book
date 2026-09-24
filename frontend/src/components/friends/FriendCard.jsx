import { useState } from 'react';
import { Link } from 'react-router-dom';
import { User, MapPin, Users } from 'lucide-react';
import FriendActions from './FriendActions';
import VerifiedBadge from '../common/VerifiedBadge';
import MemberAvatar from '../common/MemberAvatar';
import { getCoverUrl } from '../../utils/assetHelper';

export function FriendCard({
  friend,
  friendship = null,
  friendshipState = 'none',
  showActions = true,
  compact = false,
  mutualCount = 0,
  onStateChange,
}) {
  const [coverError, setCoverError] = useState(false);

  if (!friend) return null;

  const coverUrl = friend.cover_photo ? getCoverUrl(friend.cover_photo) : null;
  const location = [friend.city, friend.country].filter(Boolean).join(', ');
  const username = friend.user_id ? `@${friend.user_id}` : `@member`;

  return (
    <article className={`friend-card ${compact ? 'friend-card--compact' : ''}`}>
      {/* 1. Cover Image / Placeholder */}
      <div className="friend-card__cover">
        {coverUrl && !coverError ? (
          <img
            src={coverUrl}
            alt=""
            loading="lazy"
            onError={() => setCoverError(true)}
          />
        ) : (
          <div className="friend-card__cover-placeholder" aria-hidden="true" />
        )}
      </div>

      {/* 2. Content */}
      <div className="friend-card__content">
        {/* Avatar */}
        <Link
          className="friend-card__avatar-wrap"
          to={`/member/people/${friend.id}`}
          aria-label={`View ${friend.name}'s profile`}
        >
          <MemberAvatar member={friend} size={56} className="friend-card__avatar-img" />
        </Link>

        {/* Details */}
        <div className="friend-card__details">
          <h3 className="friend-card__name">
            <Link to={`/member/people/${friend.id}`} style={{ display: 'inline-flex', alignItems: 'center' }}>
              <span>{friend.name}</span>
              <VerifiedBadge member={friend} size={14} />
            </Link>
          </h3>

          <span className="friend-card__username">{username}</span>

          <p className="friend-card__bio">
            {friend.bio ? (friend.bio.length > 80 ? `${friend.bio.slice(0, 80)}...` : friend.bio) : 'MLM Book Member'}
          </p>

          {mutualCount > 0 ? (
            <div className="friend-card__location">
              <Users size={14} aria-hidden="true" />
              <span>{mutualCount} mutual connection{mutualCount !== 1 ? 's' : ''}</span>
            </div>
          ) : (
            <div className={`friend-card__location ${!location ? 'is-empty' : ''}`}>
              <MapPin size={14} aria-hidden="true" />
              <span>{location || 'Location not set'}</span>
            </div>
          )}
        </div>

        {/* Actions */}
        <div className="friend-card__actions">
          {showActions && (
            <FriendActions
              targetMember={friend}
              initialFriendshipState={friendshipState}
              initialFriendship={friendship}
              onStateChange={onStateChange}
            />
          )}

          <Link
            className={`member-button ${showActions ? 'member-button--secondary' : 'member-button--primary'} friend-card__btn-view`}
            to={`/member/people/${friend.id}`}
          >
            <User size={14} aria-hidden="true" />
            <span>View Profile</span>
          </Link>
        </div>
      </div>
    </article>
  );
}

export default FriendCard;
