import { useState, useEffect, useRef } from 'react';
import { useParams, Link } from 'react-router-dom';
import {
  MapPin,
  CalendarDays,
  UserMinus,
  Newspaper,
  User,
  Image,
  Video,
  UsersRound,
  PlayCircle,
  Activity,
  ArrowLeft,
} from 'lucide-react';
import { getAvatarUrl, getCoverUrl, getMediaUrl, getInitials } from '../../utils/assetHelper';
import useAuth from '../../hooks/useAuth';
import friendApi from '../../api/friendApi';
import FriendActions from '../../components/friends/FriendActions';
import CompactMemberRow from '../../components/friends/CompactMemberRow';
import PostCard from '../../components/posts/PostCard';
import StoryCard from '../../components/stories/StoryCard';
import DeleteConfirmModal from '../../components/posts/modals/DeleteConfirmModal';
import FeedRightSidebar from '../../components/posts/FeedRightSidebar';
import VerifiedBadge from '../../components/common/VerifiedBadge';
import MemberAvatar from '../../components/common/MemberAvatar';

function formatJoinDate(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
}

function getSanitizedErrorMessage(err) {
  if (!err) return 'Unable to load member profile.';
  const status = err.response?.status;
  if (status === 404) return 'Member profile not found.';
  if (status === 403) return 'You do not have permission to view this profile.';
  const msg = err.response?.data?.message || err.message;
  if (
    !msg ||
    typeof msg !== 'string' ||
    status >= 500 ||
    /undefined method|sqlstate|exception|fatal error|eval\(|\.php|syntax error|internal server error/i.test(msg)
  ) {
    return 'Unable to load member profile at this time. Please try again.';
  }
  return msg;
}

export function MemberProfilePage() {
  const { id } = useParams();
  const { user: currentUser } = useAuth();

  const [profileData, setProfileData] = useState(null);
  const [activeTab, setActiveTab] = useState('timeline');
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [retryKey, setRetryKey] = useState(0);

  // Disconnect modal state
  const [showDisconnectModal, setShowDisconnectModal] = useState(false);
  const [isDisconnecting, setIsDisconnecting] = useState(false);
  const [disconnectError, setDisconnectError] = useState(null);

  // Image load fallback states
  const [coverLoadError, setCoverLoadError] = useState(false);
  const [avatarLoadError, setAvatarLoadError] = useState(false);
  const tabsNavRef = useRef(null);

  useEffect(() => {
    setCoverLoadError(false);
    setAvatarLoadError(false);
    setProfileData(null);
    setActiveTab((currentTab) => (currentTab === 'stories' || currentTab === 'referrals' ? 'timeline' : currentTab));
  }, [id]);

  useEffect(() => {
    let isMounted = true;
    if (id) {
      setIsLoading(true);
      friendApi
        .getMemberProfile(id, activeTab)
        .then((data) => {
          if (isMounted) {
            if (data && data.member) {
              setProfileData(data);
              const dataCurrentMemberId = currentUser?.id != null ? Number(currentUser.id) : null;
              const dataProfileMemberId = data.member?.id != null
                ? Number(data.member.id)
                : (id != null && !isNaN(Number(id)) ? Number(id) : null);
              const dataIsSelf = Boolean(
                (dataCurrentMemberId != null && dataProfileMemberId != null && dataCurrentMemberId === dataProfileMemberId) ||
                data.friendship_state === 'self' ||
                data.is_self
              );
              if (!dataIsSelf && (activeTab === 'referrals' || activeTab === 'stories')) {
                setActiveTab('timeline');
              }
              setError(null);
            } else {
              setError('Member profile not found.');
            }
          }
        })
        .catch((err) => {
          if (isMounted) {
            setError(getSanitizedErrorMessage(err));
          }
        })
        .finally(() => {
          if (isMounted) {
            setIsLoading(false);
          }
        });
    }

    return () => {
      isMounted = false;
    };
  }, [id, activeTab, retryKey, currentUser?.id]);

  // Ensure active tab is scrolled into view within the horizontal tabs container
  useEffect(() => {
    if (!tabsNavRef.current) return;
    const activeBtn = tabsNavRef.current.querySelector('.profile-nav-tab.is-active');
    if (activeBtn) {
      const container = tabsNavRef.current;
      const btnLeft = activeBtn.offsetLeft;
      const btnRight = btnLeft + activeBtn.offsetWidth;
      const scrollLeft = container.scrollLeft;
      const containerWidth = container.clientWidth;

      if (btnLeft < scrollLeft) {
        container.scrollTo({ left: Math.max(0, btnLeft - 16), behavior: 'smooth' });
      } else if (btnRight > scrollLeft + containerWidth) {
        container.scrollTo({ left: btnRight - containerWidth + 16, behavior: 'smooth' });
      }
    }
  }, [activeTab]);

  // Keyboard focus navigation for horizontally scrollable tabs
  useEffect(() => {
    const container = tabsNavRef.current;
    if (!container) return;

    const handleFocusIn = (e) => {
      const targetTab = e.target.closest('.profile-nav-tab');
      if (targetTab && container.contains(targetTab)) {
        const btnLeft = targetTab.offsetLeft;
        const btnRight = btnLeft + targetTab.offsetWidth;
        const scrollLeft = container.scrollLeft;
        const containerWidth = container.clientWidth;

        if (btnLeft < scrollLeft) {
          container.scrollTo({ left: Math.max(0, btnLeft - 16), behavior: 'smooth' });
        } else if (btnRight > scrollLeft + containerWidth) {
          container.scrollTo({ left: btnRight - containerWidth + 16, behavior: 'smooth' });
        }
      }
    };

    container.addEventListener('focusin', handleFocusIn);
    return () => container.removeEventListener('focusin', handleFocusIn);
  }, []);

  const handleDisconnectConfirm = async () => {
    if (!id || isDisconnecting) return;
    setIsDisconnecting(true);
    setDisconnectError(null);
    try {
      await friendApi.removeFriend(id);
      setShowDisconnectModal(false);
      setProfileData((prev) => ({
        ...prev,
        friendship_state: 'none',
        friendship: null,
        counts: {
          ...prev?.counts,
          friends: Math.max(0, (prev?.counts?.friends || 1) - 1),
        },
      }));
    } catch (err) {
      setDisconnectError(getSanitizedErrorMessage(err) || 'Failed to disconnect. Please try again.');
    } finally {
      setIsDisconnecting(false);
    }
  };

  const member = profileData?.member;
  const friendship = profileData?.friendship;
  const friendshipState = profileData?.friendship_state || 'none';
  const currentMemberId = currentUser?.id != null ? Number(currentUser.id) : null;
  const profileMemberId = member?.id != null
    ? Number(member.id)
    : (id != null && !isNaN(Number(id)) ? Number(id) : null);
  const isSelf = Boolean(
    (currentMemberId != null && profileMemberId != null && currentMemberId === profileMemberId) ||
    friendshipState === 'self' ||
    profileData?.is_self
  );
  const counts = profileData?.counts || {};
  const canViewTimeline = isSelf || friendshipState === 'friends';

  useEffect(() => {
    if (profileData && !isSelf && (activeTab === 'stories' || activeTab === 'referrals')) {
      setActiveTab('timeline');
    }
  }, [isSelf, activeTab, profileData]);

  const directReferrals = profileData?.direct_referrals || [];
  const directReferralsCount = profileData?.direct_referral_count ?? counts.direct_referrals ?? directReferrals.length;
  const introducer = profileData?.introducer || null;

  const rawPhoto = member?.profile_photo_url || member?.profile_photo;
  const photoUrl = rawPhoto ? getAvatarUrl(rawPhoto) : null;
  const coverUrl = member?.cover_photo ? getCoverUrl(member.cover_photo) : null;
  const location = [member?.city, member?.country].filter(Boolean).join(', ');

  const tabs = [
    { key: 'timeline', label: 'Timeline', icon: Newspaper },
    { key: 'about', label: 'About', icon: User },
    { key: 'photos', label: 'Photos', icon: Image },
    { key: 'videos', label: 'Videos', icon: Video },
    { key: 'friends', label: 'Connections', icon: UsersRound },
    ...(isSelf ? [{ key: 'referrals', label: `My Referrals (${directReferralsCount})`, icon: UsersRound }] : []),
    ...(isSelf ? [{ key: 'stories', label: 'Stories', icon: PlayCircle }] : []),
    { key: 'activity', label: 'Activity', icon: Activity },
  ];

  if (isLoading && !profileData) {
    return (
      <>
        <main className="member-main feed">
          <div style={{ padding: '40px', textAlign: 'center', color: 'var(--color-text-secondary)' }}>
            Loading member profile...
          </div>
        </main>
        <FeedRightSidebar />
      </>
    );
  }

  if (error || !member) {
    return (
      <>
        <main className="member-main feed">
          <div style={{ marginBottom: '16px' }}>
            <Link
              to="/member/friends"
              style={{
                display: 'inline-flex',
                alignItems: 'center',
                gap: '6px',
                color: 'var(--color-text-secondary)',
                fontSize: '0.875rem',
                textDecoration: 'none',
              }}
            >
              <ArrowLeft size={16} />
              <span>Back to Connections</span>
            </Link>
          </div>
          <section className="member-card friend-empty" role="alert">
            <h2>Profile Unavailable</h2>
            <p>{error || 'Member not found.'}</p>
            <div style={{ display: 'flex', gap: '10px', justifyContent: 'center', marginTop: '16px' }}>
              <button
                type="button"
                className="member-button member-button--primary"
                onClick={() => {
                  setError(null);
                  setIsLoading(true);
                  setRetryKey((k) => k + 1);
                }}
              >
                Try Again
              </button>
              <Link className="member-button member-button--secondary" to="/member/friends">
                My Connections
              </Link>
            </div>
          </section>
        </main>
        <FeedRightSidebar />
      </>
    );
  }

  return (
    <>
      <main className="member-main feed" id="member-profile-main">
        <div className="profile-page public-member-profile">
          {/* Back button */}
          <div style={{ marginBottom: '12px' }}>
            <Link
              to="/member/friends"
              style={{
                display: 'inline-flex',
                alignItems: 'center',
                gap: '6px',
                color: 'var(--color-text-secondary)',
                fontSize: '0.875rem',
                fontWeight: 500,
                textDecoration: 'none',
              }}
            >
              <ArrowLeft size={16} />
              <span>Back to Connections</span>
            </Link>
          </div>

          {/* Profile Hero */}
          <section className="profile-hero" aria-labelledby="profile-name">
            <div className={`profile-cover ${coverUrl && !coverLoadError ? 'has-cover' : ''}`}>
              {coverUrl && !coverLoadError ? (
                <img
                  className="profile-cover__image"
                  src={coverUrl}
                  alt={`${member.name}'s cover`}
                  onError={() => setCoverLoadError(true)}
                />
              ) : (
                <div className="profile-cover__preview" aria-hidden="true" />
              )}
              <div className="profile-cover__overlay" aria-hidden="true" />
            </div>

            <div className="profile-identity">
              <div className="profile-avatar-container">
                <div className="profile-avatar-clickable">
                  {photoUrl && !avatarLoadError ? (
                    <img
                      className="profile-avatar"
                      src={photoUrl}
                      alt={member.name}
                      onError={() => setAvatarLoadError(true)}
                    />
                  ) : (
                    <div
                      className="profile-avatar profile-avatar--initials"
                      role="img"
                      aria-label={member.name}
                    >
                      <span>{getInitials(member.name)}</span>
                    </div>
                  )}
                </div>
              </div>

              <div className="profile-identity__copy">
                <h1
                  id="profile-name"
                  style={{
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: '8px',
                    flexWrap: 'wrap',
                    maxWidth: '100%',
                    wordBreak: 'break-word',
                    overflowWrap: 'break-word',
                  }}
                >
                  <span style={{ wordBreak: 'break-word', overflowWrap: 'break-word', maxWidth: '100%' }}>{member.name}</span>
                  <VerifiedBadge member={member} size={22} showText={true} />
                </h1>
                {member.user_id && (
                  <p className="profile-identity__username">
                    <span>@{member.user_id}</span>
                  </p>
                )}
                <p className="profile-identity__bio">{member.bio || 'MLM Book Member'}</p>
                <div className="profile-identity__meta">
                  {location && (
                    <span>
                      <MapPin size={14} aria-hidden="true" />
                      {location}
                    </span>
                  )}
                  <span>
                    <CalendarDays size={14} aria-hidden="true" />
                    Joined {formatJoinDate(member.created_at)}
                  </span>
                </div>
              </div>

              {/* Action Buttons */}
              <div className="profile-actions" style={{ display: 'flex', gap: '8px', flexWrap: 'wrap' }}>
                {friendshipState === 'self' || profileData?.is_self ? (
                  <Link
                    to="/member/profile"
                    className="member-button member-button--primary"
                    style={{ textDecoration: 'none', display: 'inline-flex', alignItems: 'center', gap: '6px' }}
                  >
                    <User size={15} aria-hidden="true" />
                    <span>Edit Profile</span>
                  </Link>
                ) : (
                  <>
                    <FriendActions
                      key={friendshipState}
                      targetMember={member}
                      initialFriendshipState={friendshipState}
                      initialFriendship={friendship}
                      onStateChange={(newState) => {
                        setProfileData((prev) => ({
                          ...prev,
                          friendship_state: newState,
                        }));
                      }}
                    />

                    {friendshipState === 'friends' && (
                      <button
                        className="member-button member-button--secondary"
                        type="button"
                        title="Disconnect"
                        onClick={() => {
                          setDisconnectError(null);
                          setShowDisconnectModal(true);
                        }}
                        style={{ display: 'inline-flex', alignItems: 'center', gap: '6px' }}
                      >
                        <UserMinus size={15} aria-hidden="true" />
                        <span>Disconnect</span>
                      </button>
                    )}
                  </>
                )}
              </div>
            </div>

            {/* Profile Statistics Bar */}
            <div className="profile-metrics-bar">
              <div className="profile-stat-box">
                {canViewTimeline ? (
                  <strong>{counts.posts || 0}</strong>
                ) : (
                  <strong aria-label="Private">&mdash;</strong>
                )}
                <small>Posts</small>
              </div>

              <div className="profile-stat-box">
                <strong>{counts.stories || 0}</strong>
                <small>Stories</small>
              </div>

              <div
                className="profile-stat-box"
                style={{ cursor: 'pointer' }}
                onClick={() => setActiveTab('friends')}
              >
                <strong>{counts.friends || 0}</strong>
                <small>Connections</small>
              </div>

              <div
                className="profile-stat-box"
                style={{ cursor: 'pointer' }}
                onClick={() => setActiveTab('photos')}
              >
                <strong>{counts.photos || 0}</strong>
                <small>Photos</small>
              </div>

              <div
                className="profile-stat-box"
                style={{ cursor: 'pointer' }}
                onClick={() => setActiveTab('videos')}
              >
                <strong>{counts.videos || 0}</strong>
                <small>Videos</small>
              </div>
            </div>

            {/* Navigation Tabs Bar */}
            <nav className="profile-nav-tabs" aria-label="Profile Sections" ref={tabsNavRef}>
              {tabs.map((tab) => {
                const Icon = tab.icon;
                return (
                  <button
                    key={tab.key}
                    type="button"
                    className={`profile-nav-tab ${activeTab === tab.key ? 'is-active' : ''}`}
                    onClick={() => setActiveTab(tab.key)}
                  >
                    <Icon size={16} aria-hidden="true" />
                    <span>{tab.label}</span>
                  </button>
                );
              })}
            </nav>
          </section>

          {/* Dynamic Tab Content */}
          <div className="profile-tab-content" style={{ marginTop: '20px' }}>
            {activeTab === 'timeline' && (
              <div className="profile-timeline">
                {!canViewTimeline ? (
                  <section className="member-card friend-empty">
                    <h2>Private Profile</h2>
                    <p>Connect with {member.name} to view their full timeline posts and activity.</p>
                  </section>
                ) : profileData.posts?.data && profileData.posts.data.length > 0 ? (
                  <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
                    {profileData.posts.data.map((post) => (
                      <PostCard key={post.id} post={post} />
                    ))}
                  </div>
                ) : (
                  <section className="member-card friend-empty">
                    <p>No posts published yet.</p>
                  </section>
                )}
              </div>
            )}

            {activeTab === 'about' && (
              <div className="card member-card" style={{ padding: '24px', borderRadius: '16px' }}>
                <h3 style={{ fontSize: '1.15rem', fontWeight: 700, marginBottom: '20px' }}>About {member.name}</h3>
                <div className="profile-about-grid">
                  <div className="profile-about-field">
                    <span className="profile-about-field__label">Handle</span>
                    <strong
                      className="profile-about-field__value"
                      title={member.user_id ? `@${member.user_id}` : 'Not set'}
                    >
                      {member.user_id ? `@${member.user_id}` : 'Not set'}
                    </strong>
                  </div>
                  <div className="profile-about-field">
                    <span className="profile-about-field__label">Location</span>
                    <strong
                      className="profile-about-field__value"
                      title={location || 'Not provided'}
                    >
                      {location || 'Not provided'}
                    </strong>
                  </div>
                  <div className="profile-about-field">
                    <span className="profile-about-field__label">Joined</span>
                    <strong
                      className="profile-about-field__value"
                      title={formatJoinDate(member.created_at)}
                    >
                      {formatJoinDate(member.created_at)}
                    </strong>
                  </div>
                  <div className="profile-about-field">
                    <span className="profile-about-field__label">Network</span>
                    <strong
                      className="profile-about-field__value"
                      title={`${counts.friends || 0} Connections`}
                    >
                      {counts.friends || 0} Connections
                    </strong>
                  </div>
                </div>
                {member.bio && (
                  <div style={{ marginTop: '24px', paddingTop: '18px', borderTop: '1px solid var(--color-border-soft)' }}>
                    <span className="profile-about-field__label" style={{ display: 'block', marginBottom: '6px' }}>Bio</span>
                    <p style={{ margin: 0, lineHeight: 1.6, color: 'var(--color-text-main, #0f172a)', wordBreak: 'break-word', overflowWrap: 'break-word' }}>{member.bio}</p>
                  </div>
                )}
              </div>
            )}

            {activeTab === 'photos' && (
              <div>
                {profileData.photos && profileData.photos.length > 0 ? (
                  <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(min(100%, 140px), 1fr))', gap: '12px' }}>
                    {profileData.photos.map((p) => {
                      const mUrl = getMediaUrl(p.media_path, 'posts/images') || `/${p.media_path}`;
                      return (
                        <div key={p.id} style={{ borderRadius: '12px', overflow: 'hidden', height: '180px', background: '#f1f5f9' }}>
                          <img
                            src={mUrl}
                            alt=""
                            style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                            onError={(e) => {
                              e.target.style.display = 'none';
                            }}
                          />
                        </div>
                      );
                    })}
                  </div>
                ) : (
                  <section className="member-card friend-empty">
                    <p>No photos uploaded yet.</p>
                  </section>
                )}
              </div>
            )}

            {activeTab === 'videos' && (
              <div>
                {profileData.videos && profileData.videos.length > 0 ? (
                  <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(min(100%, 200px), 1fr))', gap: '12px' }}>
                    {profileData.videos.map((v) => {
                      const vUrl = getMediaUrl(v.media_path, 'posts/videos') || `/${v.media_path}`;
                      return (
                        <div key={v.id} style={{ borderRadius: '12px', overflow: 'hidden', height: '180px', background: '#0f172a' }}>
                          <video
                            src={vUrl}
                            controls
                            playsInline
                            style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                          />
                        </div>
                      );
                    })}
                  </div>
                ) : (
                  <section className="member-card friend-empty">
                    <p>No videos uploaded yet.</p>
                  </section>
                )}
              </div>
            )}

            {activeTab === 'friends' && (
              <div>
                {profileData.friends_list && profileData.friends_list.length > 0 ? (
                  <section className="card connection-requests-card" style={{ padding: '0', overflow: 'hidden' }}>
                    <div className="connection-requests-list">
                      {profileData.friends_list.map((f) => (
                        <CompactMemberRow
                          key={f.id}
                          member={f}
                          mode="connection"
                          initialFriendshipState="friends"
                        />
                      ))}
                    </div>
                  </section>
                ) : (
                  <section className="member-card friend-empty">
                    <p>No public connections to display.</p>
                  </section>
                )}
              </div>
            )}

            {isSelf && activeTab === 'referrals' && (
              <div style={{ display: 'flex', flexDirection: 'column', gap: '20px' }}>
                {/* 1. INTRODUCED BY */}
                <div className="card member-card" style={{ padding: '24px', borderRadius: '16px' }}>
                  <h3 style={{ fontSize: '1.15rem', fontWeight: 700, marginBottom: '16px' }}>Introduced By</h3>
                  {!introducer ? (
                    <section className="member-card friend-empty" style={{ margin: 0, padding: '24px 16px' }}>
                      <p>No introducer</p>
                    </section>
                  ) : (
                    <div className="friend-grid">
                      <div className="profile-friend-card">
                        <div className="profile-friend-card__avatar">
                          <MemberAvatar member={introducer} size={44} />
                        </div>
                        <div className="profile-friend-card__info">
                          <div style={{ display: 'flex', alignItems: 'center', gap: '6px', flexWrap: 'wrap' }}>
                            <Link to={`/member/people/${introducer.id}`} className="profile-friend-card__name" title={introducer.name}>
                              {introducer.name}
                            </Link>
                            {Boolean(introducer.mobile_verified_at) && (
                              <VerifiedBadge isVerified={true} size="sm" />
                            )}
                          </div>
                          {introducer.user_id && (
                            <span className="profile-friend-card__username">@{introducer.user_id}</span>
                          )}
                          {(introducer.city || introducer.country) && (
                            <span style={{ fontSize: '0.8rem', color: 'var(--color-text-secondary)', display: 'block', marginTop: '2px' }}>
                              {[introducer.city, introducer.country].filter(Boolean).join(', ')}
                            </span>
                          )}
                        </div>
                        <div className="profile-friend-card__actions">
                          <Link to={`/member/people/${introducer.id}`} className="member-button member-button--secondary">
                            <User size={16} aria-hidden="true" />
                            <span>View Profile</span>
                          </Link>
                        </div>
                      </div>
                    </div>
                  )}
                </div>

                {/* 2. DIRECT REFERRALS */}
                <section className="card member-card" style={{ padding: '0', overflow: 'hidden' }}>
                  <header style={{ padding: '20px 24px 16px', borderBottom: '1px solid var(--color-border-subtle, #f1f5f9)', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <h3 style={{ fontSize: '1.15rem', fontWeight: 700, margin: 0 }}>My Referrals</h3>
                    <span style={{ fontSize: '0.9rem', color: 'var(--color-text-secondary)', fontWeight: 600 }}>
                      Direct Referrals: {directReferralsCount}
                    </span>
                  </header>

                  {directReferrals.length === 0 ? (
                    <section className="member-card friend-empty" style={{ margin: 0, padding: '24px 16px' }}>
                      <p>No direct referrals to display.</p>
                    </section>
                  ) : (
                    <div className="connection-requests-list" style={{ border: 'none', borderRadius: '0' }}>
                      {directReferrals.map((refMember) => (
                        <CompactMemberRow
                          key={refMember.id}
                          member={refMember}
                          mode="referral"
                        />
                      ))}
                    </div>
                  )}
                </section>
              </div>
            )}

            {isSelf && activeTab === 'stories' && (
              <div>
                {profileData.stories && profileData.stories.length > 0 ? (
                  <div style={{ display: 'flex', gap: '12px', flexWrap: 'wrap' }}>
                    {profileData.stories.map((s) => (
                      <div key={s.id} style={{ width: '120px', height: '180px' }}>
                        <StoryCard story={s} />
                      </div>
                    ))}
                  </div>
                ) : (
                  <section className="member-card friend-empty">
                    <p>No active stories.</p>
                  </section>
                )}
              </div>
            )}

            {activeTab === 'activity' && (
              <div className="card member-card" style={{ padding: '24px', borderRadius: '16px' }}>
                <h3 style={{ fontSize: '1.15rem', fontWeight: 700, marginBottom: '16px' }}>Recent Activity</h3>
                <p style={{ color: 'var(--color-text-secondary)' }}>
                  {member.name} joined MLM Book in {formatJoinDate(member.created_at)} and has accumulated {counts.friends || 0} connections and {counts.posts || 0} posts.
                </p>
              </div>
            )}
          </div>
        </div>

        {/* Disconnect Confirmation Modal */}
        {showDisconnectModal && (
          <DeleteConfirmModal
            isOpen={showDisconnectModal}
            onClose={() => {
              if (!isDisconnecting) {
                setShowDisconnectModal(false);
                setDisconnectError(null);
              }
            }}
            onConfirm={handleDisconnectConfirm}
            title={`Disconnect from ${member?.name || 'this member'}?`}
            message="Are you sure you want to disconnect from this member?"
            confirmLabel="Disconnect"
            loadingLabel="Disconnecting..."
            icon={<UserMinus size={18} />}
            errorMessage={disconnectError}
            isDeleting={isDisconnecting}
          />
        )}
      </main>

      <FeedRightSidebar />
    </>
  );
}

export default MemberProfilePage;
