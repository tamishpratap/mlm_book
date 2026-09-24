import { useState, useEffect } from 'react';
// import { Link } from 'react-router-dom';
import { UserPlus, UserMinus, UserCheck, UserX, UsersRound /*, MessageSquare */ } from 'lucide-react';
import friendApi from '../../api/friendApi';

export function FriendActions({
  targetMember,
  initialFriendshipState = 'none',
  initialFriendship = null,
  onStateChange,
}) {
  const [friendshipState, setFriendshipState] = useState(initialFriendshipState);
  const [friendship, setFriendship] = useState(initialFriendship);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    setFriendshipState(initialFriendshipState);
    setFriendship(initialFriendship);
  }, [initialFriendshipState, initialFriendship]);

  const handleSendRequest = async (e) => {
    e.preventDefault();
    if (isLoading) return;
    setIsLoading(true);
    setError(null);
    try {
      const response = await friendApi.sendFriendRequest(targetMember.id);
      setFriendshipState(response.state || 'pending_sent');
      if (response.friendship) setFriendship(response.friendship);
      if (onStateChange) onStateChange(response.state || 'pending_sent', response.friendship);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to send request.');
    } finally {
      setIsLoading(false);
    }
  };

  const handleCancelRequest = async (e) => {
    e.preventDefault();
    if (isLoading) return;
    setIsLoading(true);
    setError(null);
    try {
      const friendshipId = friendship?.id || targetMember.friendship_id;
      if (friendshipId) {
        await friendApi.cancelFriendRequest(friendshipId);
      }
      setFriendshipState('none');
      setFriendship(null);
      if (onStateChange) onStateChange('none', null);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to cancel request.');
    } finally {
      setIsLoading(false);
    }
  };

  const handleAcceptRequest = async (e) => {
    e.preventDefault();
    if (isLoading) return;
    setIsLoading(true);
    setError(null);
    try {
      const friendshipId = friendship?.id || targetMember.friendship_id;
      if (friendshipId) {
        await friendApi.acceptFriendRequest(friendshipId);
      }
      setFriendshipState('friends');
      if (onStateChange) onStateChange('friends', friendship);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to accept request.');
    } finally {
      setIsLoading(false);
    }
  };

  const handleRejectRequest = async (e) => {
    e.preventDefault();
    if (isLoading) return;
    setIsLoading(true);
    setError(null);
    try {
      const friendshipId = friendship?.id || targetMember.friendship_id;
      if (friendshipId) {
        await friendApi.rejectFriendRequest(friendshipId);
      }
      setFriendshipState('none');
      setFriendship(null);
      if (onStateChange) onStateChange('none', null);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to reject request.');
    } finally {
      setIsLoading(false);
    }
  };

  const stateClass = `friend-actions friend-actions--${friendshipState.replace('_', '-')}`;

  return (
    <div className={stateClass} data-friend-actions data-friend-member-id={targetMember?.id}>
      {friendshipState === 'none' && (
        <button
          className="member-button member-button--primary friend-action-button"
          type="button"
          onClick={handleSendRequest}
          disabled={isLoading}
        >
          <UserPlus size={15} aria-hidden="true" />
          <span>{isLoading ? 'Sending...' : 'Connect'}</span>
        </button>
      )}

      {friendshipState === 'pending_sent' && (
        <button
          className="member-button member-button--secondary friend-action-button"
          type="button"
          onClick={handleCancelRequest}
          disabled={isLoading}
        >
          <UserMinus size={15} aria-hidden="true" />
          <span>{isLoading ? 'Cancelling...' : 'Cancel Connection Request'}</span>
        </button>
      )}

      {friendshipState === 'pending_received' && (
        <div className="friend-actions__btn-group" style={{ display: 'flex', gap: '8px', flexWrap: 'wrap', width: '100%' }}>
          <button
            className="member-button member-button--primary friend-action-button"
            type="button"
            onClick={handleAcceptRequest}
            disabled={isLoading}
          >
            <UserCheck size={15} aria-hidden="true" />
            <span>{isLoading ? 'Accepting...' : 'Accept Connection'}</span>
          </button>
          <button
            className="member-button member-button--danger friend-action-button"
            type="button"
            onClick={handleRejectRequest}
            disabled={isLoading}
          >
            <UserX size={15} aria-hidden="true" />
            <span>{isLoading ? 'Rejecting...' : 'Reject Connection'}</span>
          </button>
        </div>
      )}

      {friendshipState === 'friends' && (
        <div style={{ display: 'flex', gap: '6px', flexWrap: 'wrap' }}>
          <button
            className="member-button friend-action-button friend-action-button--friends"
            type="button"
            disabled
          >
            <UsersRound size={15} aria-hidden="true" />
            <span>Connected</span>
          </button>
          {/* Member Direct Messages - Temporarily Disabled (Preserved for future activation) */}
          {/*
          {targetMember?.id && (
            <Link
              to={`/member/messages/${targetMember.id}`}
              className="member-button member-button--secondary friend-action-button"
              style={{ textDecoration: 'none' }}
            >
              <MessageSquare size={15} aria-hidden="true" />
              <span>Message</span>
            </Link>
          )}
          */}
        </div>
      )}

      {error && (
        <span style={{ fontSize: '0.75rem', color: 'var(--color-danger, #ff4168)', display: 'block', marginTop: '4px' }}>
          {error}
        </span>
      )}
    </div>
  );
}

export default FriendActions;
