import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Lock, MailCheck, EyeOff, Globe, Users } from 'lucide-react';
import useAuth from '../../hooks/useAuth';
import JoinCommunityButton from './JoinCommunityButton';

function getInitials(name) {
  if (!name) return 'C';
  const parts = name.trim().split(/\s+/);
  return parts
    .slice(0, 2)
    .map((p) => p.charAt(0).toUpperCase())
    .join('') || 'C';
}

export function CommunityCard({ community, onUpdate }) {
  const { user } = useAuth();
  const [memberCount, setMemberCount] = useState(community?.member_count ?? 0);

  useEffect(() => {
    if (community?.member_count !== undefined) {
      setMemberCount(community.member_count);
    }
  }, [community?.member_count]);

  if (!community) return null;

  const currentMemberId = user?.id;
  const isOwner = community.owner_id === currentMemberId;
  const initials = getInitials(community.name);

  const handleStateChange = (newState, newCount) => {
    if (newCount !== undefined) {
      setMemberCount(newCount);
    }
    if (onUpdate) onUpdate();
  };

  return (
    <article className="community-card">
      <div className="community-card__cover">
        {community.cover_photo ? (
          <img src={`/${community.cover_photo}`} alt={`${community.name} cover`} loading="lazy" />
        ) : (
          <div
            style={{
              width: '100%',
              height: '100%',
              background: 'linear-gradient(135deg, #176bff 0%, #7146ed 100%)',
            }}
          />
        )}
        <div className="community-card__badges">
          <span className="community-badge community-badge--category">{community.category}</span>
          <span className="community-badge community-badge--visibility">
            {community.visibility === 'private' && (
              <>
                <Lock size={12} aria-hidden="true" />
                <span>Private</span>
              </>
            )}
            {community.visibility === 'invite_only' && (
              <>
                <MailCheck size={12} aria-hidden="true" />
                <span>Invite Only</span>
              </>
            )}
            {community.visibility === 'secret' && (
              <>
                <EyeOff size={12} aria-hidden="true" />
                <span>Secret</span>
              </>
            )}
            {community.visibility === 'public' && (
              <>
                <Globe size={12} aria-hidden="true" />
                <span>Public</span>
              </>
            )}
          </span>
        </div>
      </div>

      <div className="community-card__body">
        <div className="community-card__avatar">
          {community.logo ? (
            <img src={`/${community.logo}`} alt={`${community.name} logo`} loading="lazy" />
          ) : (
            <div className="community-avatar-initials">{initials}</div>
          )}
        </div>

        <h3 className="community-card__title">
          <Link to={`/member/community/${community.slug}`}>{community.name}</Link>
        </h3>

        <div className="community-card__owner">
          <span>Created by </span>
          <strong>{community.owner?.name || 'Member'}</strong>
        </div>

        <p className="community-card__description">
          {community.description || 'No description provided for this community yet.'}
        </p>

        <div className="community-card__footer">
          <div className="community-card__meta">
            <span>
              <Users size={14} aria-hidden="true" />
              {Number(memberCount ?? 0).toLocaleString()} {memberCount === 1 ? 'member' : 'members'}
            </span>
          </div>

          <JoinCommunityButton
            community={community}
            isOwner={isOwner}
            isMember={Boolean(community.is_member)}
            isPending={Boolean(community.is_pending)}
            role={community.member_role || 'member'}
            onStateChange={handleStateChange}
          />
        </div>
      </div>
    </article>
  );
}

export default CommunityCard;
