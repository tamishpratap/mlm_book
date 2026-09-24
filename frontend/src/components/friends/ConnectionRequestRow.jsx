import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Check, X, UserMinus } from 'lucide-react';
import friendApi from '../../api/friendApi';
import VerifiedBadge from '../common/VerifiedBadge';
import MemberAvatar from '../common/MemberAvatar';

export function ConnectionRequestRow({
  request,
  type = 'incoming', // 'incoming' | 'sent'
  onAction,
}) {
  const [isProcessing, setIsProcessing] = useState(false);
  const [actionType, setActionType] = useState(null); // 'accept' | 'reject' | 'cancel'
  const [error, setError] = useState(null);

  if (!request || !request.friend) return null;

  const { friend } = request;
  const friendshipId = request.friendship?.id || request.friendship_id || request.id;
  const username = friend.user_id ? `@${friend.user_id}` : '@member';
  const mutualCount = request.mutual_count ?? (Array.isArray(request.mutual_friends) ? request.mutual_friends.length : 0);
  const mutualFriends = request.mutual_friends || [];

  const handleAccept = async (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (isProcessing) return;
    setIsProcessing(true);
    setActionType('accept');
    setError(null);
    try {
      await friendApi.acceptFriendRequest(friendshipId);
      if (onAction) onAction(request.id, 'friends');
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to accept request.');
      setIsProcessing(false);
      setActionType(null);
    }
  };

  const handleReject = async (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (isProcessing) return;
    setIsProcessing(true);
    setActionType('reject');
    setError(null);
    try {
      await friendApi.rejectFriendRequest(friendshipId);
      if (onAction) onAction(request.id, 'none');
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to reject request.');
      setIsProcessing(false);
      setActionType(null);
    }
  };

  const handleCancel = async (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (isProcessing) return;
    setIsProcessing(true);
    setActionType('cancel');
    setError(null);
    try {
      await friendApi.cancelFriendRequest(friendshipId);
      if (onAction) onAction(request.id, 'none');
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to cancel request.');
      setIsProcessing(false);
      setActionType(null);
    }
  };

  return (
    <div
      className={`connection-request-row ${type === 'incoming' ? 'connection-request-row--incoming' : 'connection-request-row--sent'}`}
      data-request-id={request.id}
    >
      <div className="connection-request-row__main">
        {/* Avatar */}
        <Link
          to={`/member/people/${friend.id}`}
          className="connection-request-row__avatar-link"
          aria-label={`View ${friend.name}'s profile`}
        >
          <MemberAvatar
            member={friend}
            size={48}
            className="connection-request-row__avatar-img"
          />
        </Link>

        {/* Member Details */}
        <div className="connection-request-row__details">
          <div className="connection-request-row__name-wrap">
            <Link
              to={`/member/people/${friend.id}`}
              className="connection-request-row__name-link"
              title={friend.name}
            >
              <span className="connection-request-row__name">{friend.name}</span>
              <VerifiedBadge member={friend} size={14} />
            </Link>
          </div>

          <div className="connection-request-row__username-wrap">
            <Link
              to={`/member/people/${friend.id}`}
              className="connection-request-row__username-link"
              title={username}
            >
              <span className="connection-request-row__username">{username}</span>
            </Link>
          </div>

          <div className="connection-request-row__meta">
            {mutualCount > 0 ? (
              <div className="connection-request-row__mutuals">
                {mutualFriends.length > 0 && (
                  <div className="connection-request-row__mini-avatars" aria-hidden="true">
                    {mutualFriends.slice(0, 3).map((mf, i) => (
                      <span
                        key={mf.id || i}
                        className="connection-request-row__mini-avatar-wrap"
                        style={{ zIndex: 3 - i }}
                      >
                        <MemberAvatar
                          member={mf}
                          size={18}
                          className="connection-request-row__mini-avatar"
                        />
                      </span>
                    ))}
                  </div>
                )}
                <span className="connection-request-row__mutual-text">
                  {mutualCount} mutual connection{mutualCount !== 1 ? 's' : ''}
                </span>
              </div>
            ) : (
              <span className="connection-request-row__no-mutual-text">
                No mutual connections
              </span>
            )}
          </div>

          {error && (
            <div className="connection-request-row__error" role="alert">
              {error}
            </div>
          )}
        </div>
      </div>

      {/* Actions */}
      <div className="connection-request-row__actions">
        {type === 'incoming' ? (
          <>
            <button
              className="member-button member-button--primary connection-request-btn connection-request-btn--accept"
              type="button"
              onClick={handleAccept}
              disabled={isProcessing}
              aria-label={`Accept connection request from ${friend.name}`}
            >
              <Check size={14} aria-hidden="true" />
              <span>{isProcessing && actionType === 'accept' ? 'Accepting...' : 'Accept'}</span>
            </button>

            <button
              className="member-button member-button--secondary connection-request-btn connection-request-btn--reject"
              type="button"
              onClick={handleReject}
              disabled={isProcessing}
              aria-label={`Reject connection request from ${friend.name}`}
            >
              <span>{isProcessing && actionType === 'reject' ? 'Deleting...' : 'Delete'}</span>
            </button>

            <button
              className="connection-request-btn--dismiss"
              type="button"
              onClick={handleReject}
              disabled={isProcessing}
              aria-label={`Dismiss request from ${friend.name}`}
              title="Dismiss"
            >
              <X size={15} aria-hidden="true" />
            </button>
          </>
        ) : (
          <button
            className="member-button member-button--secondary connection-request-btn connection-request-btn--cancel"
            type="button"
            onClick={handleCancel}
            disabled={isProcessing}
            aria-label={`Cancel connection request to ${friend.name}`}
          >
            <UserMinus size={14} aria-hidden="true" />
            <span>{isProcessing && actionType === 'cancel' ? 'Cancelling...' : 'Cancel Request'}</span>
          </button>
        )}
      </div>
    </div>
  );
}

export default ConnectionRequestRow;
