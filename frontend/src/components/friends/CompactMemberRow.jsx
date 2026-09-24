import { useState } from 'react';
import { Link } from 'react-router-dom';
import { User, UserPlus, UserMinus, Check, UsersRound } from 'lucide-react';
import friendApi from '../../api/friendApi';
import VerifiedBadge from '../common/VerifiedBadge';
import MemberAvatar from '../common/MemberAvatar';

export function CompactMemberRow({
  member,
  mode = 'suggestion', // 'connection' | 'suggestion' | 'referral'
  type, // optional alias for mode: 'connection' | 'suggestion' | 'referral'
  initialFriendshipState = 'none', // 'none' | 'pending_sent' | 'pending_received' | 'friends'
  onStateChange,
  secondaryInfo,
}) {
  const currentMode = type || mode;
  const [friendshipState, setFriendshipState] = useState(
    member?.friendship_state || (currentMode === 'connection' ? 'friends' : initialFriendshipState)
  );
  const [friendshipId, setFriendshipId] = useState(member?.friendship_id || member?.friendship?.id || null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);

  if (!member) return null;

  const username = member.user_id ? `@${member.user_id}` : '@member';
  const location = [member.city, member.country].filter(Boolean).join(', ');
  const mutualCount = member.mutual_count ?? (Array.isArray(member.mutual_friends) ? member.mutual_friends.length : 0);
  const mutualFriends = member.mutual_friends || [];

  const handleConnect = async (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (isLoading) return;
    setIsLoading(true);
    setError(null);
    try {
      const response = await friendApi.sendFriendRequest(member.id);
      const newState = response.state || 'pending_sent';
      setFriendshipState(newState);
      if (response.friendship?.id) {
        setFriendshipId(response.friendship.id);
      }
      if (onStateChange) onStateChange(member.id, newState, response.friendship);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to send request.');
    } finally {
      setIsLoading(false);
    }
  };

  const handleCancel = async (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (isLoading) return;
    setIsLoading(true);
    setError(null);
    try {
      const targetFriendshipId = friendshipId || member.friendship_id;
      if (targetFriendshipId) {
        await friendApi.cancelFriendRequest(targetFriendshipId);
      }
      setFriendshipState('none');
      setFriendshipId(null);
      if (onStateChange) onStateChange(member.id, 'none', null);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to cancel request.');
    } finally {
      setIsLoading(false);
    }
  };

  const handleAccept = async (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (isLoading) return;
    setIsLoading(true);
    setError(null);
    try {
      const targetFriendshipId = friendshipId || member.friendship_id;
      if (targetFriendshipId) {
        await friendApi.acceptFriendRequest(targetFriendshipId);
      }
      setFriendshipState('friends');
      if (onStateChange) onStateChange(member.id, 'friends', null);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to accept request.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div
      className={`connection-request-row compact-member-row ${
        currentMode === 'connection'
          ? 'compact-member-row--connection'
          : currentMode === 'referral'
            ? 'compact-member-row--referral'
            : 'compact-member-row--suggestion'
      }`}
      data-member-id={member.id}
    >
      <div className="connection-request-row__main">
        {/* Avatar */}
        <Link
          to={`/member/people/${member.id}`}
          className="connection-request-row__avatar-link"
          aria-label={`View ${member.name}'s profile`}
        >
          <MemberAvatar
            member={member}
            size={48}
            className="connection-request-row__avatar-img"
          />
        </Link>

        {/* Member Details */}
        <div className="connection-request-row__details">
          <div className="connection-request-row__name-wrap">
            <Link
              to={`/member/people/${member.id}`}
              className="connection-request-row__name-link"
              title={member.name}
            >
              <span className="connection-request-row__name">{member.name}</span>
              <VerifiedBadge member={member} size={14} />
            </Link>
          </div>

          <div className="connection-request-row__username-wrap">
            <Link
              to={`/member/people/${member.id}`}
              className="connection-request-row__username-link"
              title={username}
            >
              <span className="connection-request-row__username">{username}</span>
            </Link>
          </div>

          {currentMode === 'referral' ? (
            (secondaryInfo || location) ? (
              <div className="connection-request-row__meta">
                <span className="connection-request-row__no-mutual-text" title={secondaryInfo || location}>
                  {secondaryInfo || location}
                </span>
              </div>
            ) : null
          ) : (
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
              ) : location ? (
                <span className="connection-request-row__no-mutual-text" title={location}>
                  {location}
                </span>
              ) : (
                <span className="connection-request-row__no-mutual-text">
                  No mutual connections
                </span>
              )}
            </div>
          )}

          {error && (
            <div className="connection-request-row__error" role="alert">
              {error}
            </div>
          )}
        </div>
      </div>

      {/* Actions */}
      <div className="connection-request-row__actions compact-member-row__actions">
        {currentMode === 'connection' || currentMode === 'referral' ? (
          <Link
            to={`/member/people/${member.id}`}
            className="member-button member-button--secondary connection-request-btn"
            style={{ textDecoration: 'none' }}
          >
            <User size={14} aria-hidden="true" />
            <span>View Profile</span>
          </Link>
        ) : (
          <>
            {friendshipState === 'none' && (
              <button
                className="member-button member-button--primary connection-request-btn connection-request-btn--connect"
                type="button"
                onClick={handleConnect}
                disabled={isLoading}
                aria-label={`Connect with ${member.name}`}
              >
                <UserPlus size={14} aria-hidden="true" />
                <span>{isLoading ? 'Connecting...' : 'Connect'}</span>
              </button>
            )}

            {friendshipState === 'pending_sent' && (
              <button
                className="member-button member-button--secondary connection-request-btn connection-request-btn--cancel"
                type="button"
                onClick={handleCancel}
                disabled={isLoading}
                aria-label={`Cancel request sent to ${member.name}`}
              >
                <UserMinus size={14} aria-hidden="true" />
                <span>{isLoading ? 'Cancelling...' : 'Requested'}</span>
              </button>
            )}

            {friendshipState === 'pending_received' && (
              <button
                className="member-button member-button--primary connection-request-btn connection-request-btn--accept"
                type="button"
                onClick={handleAccept}
                disabled={isLoading}
                aria-label={`Accept request from ${member.name}`}
              >
                <Check size={14} aria-hidden="true" />
                <span>{isLoading ? 'Accepting...' : 'Accept'}</span>
              </button>
            )}

            {friendshipState === 'friends' && (
              <Link
                to={`/member/people/${member.id}`}
                className="member-button member-button--secondary connection-request-btn"
                style={{ textDecoration: 'none' }}
              >
                <UsersRound size={14} aria-hidden="true" />
                <span>Connected</span>
              </Link>
            )}
          </>
        )}
      </div>
    </div>
  );
}

export default CompactMemberRow;
