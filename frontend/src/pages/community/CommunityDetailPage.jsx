import { useState, useEffect, useCallback, useRef } from 'react';
import { useParams, Link } from 'react-router-dom';
import {
  Users,
  UserCheck,
  Calendar,
  Lock,
  MailCheck,
  EyeOff,
  Globe,
  Camera,
  Share2,
  Info,
  UsersRound,
  MessageSquare,
  ArrowLeft,
  Pencil,
  RotateCw,
  Check,
  ShieldCheck,
  Activity,
  Bell,
  BarChart3,
} from 'lucide-react';
import communityApi from '../../api/communityApi';
import useAuth from '../../hooks/useAuth';
import JoinCommunityButton from '../../components/community/JoinCommunityButton';
import PostCard from '../../components/posts/PostCard';
import PostComposer from '../../components/posts/PostComposer';
import VerifiedBadge from '../../components/common/VerifiedBadge';
import MemberAvatar from '../../components/common/MemberAvatar';
import { ModalPortal } from '../../components/common/ModalPortal';
import { getCoverUrl, getAvatarUrl } from '../../utils/assetHelper';


function getInitials(name) {
  if (!name) return 'C';
  const parts = name.trim().split(/\s+/);
  return parts
    .slice(0, 2)
    .map((p) => p.charAt(0).toUpperCase())
    .join('') || 'C';
}

function formatDate(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

export function CommunityDetailPage() {
  const { slug } = useParams();
  const { user } = useAuth();

  const [detailData, setDetailData] = useState(null);
  const [activeTab, setActiveTab] = useState('feed');
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState(null);

  // Invite share toast
  const [copiedInvite, setCopiedInvite] = useState(false);

  // Notification Preferences State
  const [showNotifModal, setShowNotifModal] = useState(false);
  const [notifLevel, setNotifLevel] = useState('all');
  const [muteDuration, setMuteDuration] = useState('none');
  const [isSavingNotif, setIsSavingNotif] = useState(false);
  const [notifFeedback, setNotifFeedback] = useState('');

  // Media upload refs
  const coverInputRef = useRef(null);
  const logoInputRef = useRef(null);



  const handleSaveNotificationPreferences = async (e) => {
    e.preventDefault();
    if (!slug || isSavingNotif) return;
    setIsSavingNotif(true);
    try {
      const res = await communityApi.updateNotificationPreferences(slug, {
        notification_level: notifLevel,
        mute_duration: muteDuration,
      });
      setNotifFeedback(res.message || 'Preferences updated.');
      setTimeout(() => {
        setShowNotifModal(false);
        setNotifFeedback('');
      }, 1500);
    } catch {
      setNotifFeedback('Failed to update preferences.');
    } finally {
      setIsSavingNotif(false);
    }
  };

  const fetchCommunityDetail = useCallback((tabName) => {
    if (!slug) return Promise.resolve(null);
    return communityApi.getCommunity(slug, tabName);
  }, [slug]);

  useEffect(() => {
    let isMounted = true;

    fetchCommunityDetail(activeTab)
      .then((data) => {
        if (isMounted && data) {
          setDetailData(data);
          if (data.active_tab) {
            setActiveTab(data.active_tab);
          }
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load community details.');
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [fetchCommunityDetail, activeTab]);

  const handleRefresh = () => {
    setIsRefreshing(true);
    fetchCommunityDetail(activeTab)
      .then((data) => {
        if (data) setDetailData(data);
      })
      .catch(() => {})
      .finally(() => setIsRefreshing(false));
  };

  const handleTabChange = (tabName) => {
    setActiveTab(tabName);
  };

  const handleCoverUpload = async (e) => {
    const file = e.target.files?.[0];
    if (!file || !slug) return;

    const formData = new FormData();
    formData.append('cover_photo', file);

    try {
      const res = await communityApi.updateCover(slug, formData);
      if (res && res.success) {
        handleRefresh();
      }
    } catch {
      // Handle error
    } finally {
      if (coverInputRef.current) coverInputRef.current.value = '';
    }
  };

  const handleLogoUpload = async (e) => {
    const file = e.target.files?.[0];
    if (!file || !slug) return;

    const formData = new FormData();
    formData.append('logo', file);

    try {
      const res = await communityApi.updateLogo(slug, formData);
      if (res && res.success) {
        handleRefresh();
      }
    } catch {
      // Handle error
    } finally {
      if (logoInputRef.current) logoInputRef.current.value = '';
    }
  };

  const handleShareInvite = () => {
    const inviteUrl = `${window.location.origin}/member/community/${slug}`;
    navigator.clipboard.writeText(inviteUrl);
    setCopiedInvite(true);
    setTimeout(() => setCopiedInvite(false), 2000);
  };

  if (isLoading && !detailData) {
    return (
      <div style={{ padding: '40px', textAlign: 'center', color: 'var(--color-text-secondary)' }}>
        Loading community...
      </div>
    );
  }

  if (error || !detailData?.community) {
    return (
      <div>
        <div style={{ marginBottom: '16px' }}>
          <Link
            to="/member/community"
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
            <span>Back to Community Hub</span>
          </Link>
        </div>
        <div className="notification-empty" role="alert">
          <h2>Community Unavailable</h2>
          <p>{error || 'The requested community was not found.'}</p>
          <Link to="/member/community" className="member-button member-button--primary" style={{ marginTop: '12px' }}>
            Browse Communities
          </Link>
        </div>
      </div>
    );
  }

  const community = detailData.community;
  const isOwner = Boolean(detailData.is_owner);
  const isMember = Boolean(detailData.is_member);
  const isPending = Boolean(detailData.is_pending);
  const isAdmin = Boolean(detailData.is_admin);
  const role = detailData.member_role || 'member';

  const acceptedMembers = detailData.accepted_members?.data || [];
  const feedPosts = detailData.feed_posts?.data || [];
  const pinnedPost = detailData.pinned_post;
  const announcements = detailData.announcements || [];

  const initials = getInitials(community.name);
  const canViewFeed = community.visibility === 'public' || isMember;

  return (
    <div className="community-page">
      {/* Back button */}
      <div style={{ marginBottom: '12px' }}>
        <Link
          to="/member/community"
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
          <span>Back to Community Hub</span>
        </Link>
      </div>

      {/* Community Hero Header */}
      <div className="community-hero">
        <div className="community-hero__cover">
          {community.cover_photo ? (
            <img
              src={getCoverUrl(community.cover_photo)}
              alt={`${community.name} cover`}
              onError={(e) => { e.currentTarget.style.display = 'none'; }}
            />
          ) : (
            <div style={{ width: '100%', height: '100%', background: 'linear-gradient(135deg, #176bff 0%, #7146ed 100%)' }} />
          )}

          {(isOwner || isAdmin) && (
            <>
              <button
                type="button"
                className="media-upload-button"
                title="Change Cover Photo"
                onClick={() => coverInputRef.current?.click()}
                style={{ position: 'absolute', bottom: '16px', right: '16px', display: 'flex', alignItems: 'center', gap: '6px', background: 'rgba(0,0,0,0.6)', color: '#fff', border: 'none', padding: '8px 14px', borderRadius: '8px', cursor: 'pointer', fontSize: '13px' }}
              >
                <Camera size={15} />
                <span>Change Cover</span>
              </button>
              <input
                ref={coverInputRef}
                type="file"
                accept=".jpg,.jpeg,.png,.webp"
                onChange={handleCoverUpload}
                style={{ display: 'none' }}
              />
            </>
          )}
        </div>

        <div className="community-hero__body">
          <div className="community-hero__avatar">
            {community.logo ? (
              <img
                src={getAvatarUrl(community.logo)}
                alt={`${community.name} logo`}
                onError={(e) => { e.currentTarget.style.display = 'none'; }}
              />
            ) : (
              <div className="community-avatar-initials">{initials}</div>
            )}

            {(isOwner || isAdmin) && (
              <>
                <button
                  type="button"
                  onClick={() => logoInputRef.current?.click()}
                  title="Change Logo"
                  style={{
                    position: 'absolute',
                    bottom: '4px',
                    right: '4px',
                    width: '32px',
                    height: '32px',
                    borderRadius: '50%',
                    background: 'var(--color-primary, #4f7df3)',
                    color: '#fff',
                    border: '2px solid #fff',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    cursor: 'pointer',
                  }}
                >
                  <Camera size={14} />
                </button>
                <input
                  ref={logoInputRef}
                  type="file"
                  accept=".jpg,.jpeg,.png,.webp"
                  onChange={handleLogoUpload}
                  style={{ display: 'none' }}
                />
              </>
            )}
          </div>

          <div className="community-hero__content">
            <div className="community-hero__title-row">
              <h1>{community.name}</h1>
              <div className="community-hero__actions">
                <button
                  className="member-button member-button--secondary"
                  type="button"
                  aria-label="Refresh Community"
                  onClick={handleRefresh}
                  disabled={isRefreshing}
                  style={{ padding: '8px 12px' }}
                >
                  <RotateCw size={14} className={isRefreshing ? 'fa-spin' : ''} aria-hidden="true" />
                </button>

                {(isOwner || isAdmin || role === 'moderator') && (
                  <>
                    <Link to={`/member/community/${community.slug}/analytics`} className="member-button member-button--secondary" title="Community Analytics">
                      <BarChart3 size={14} aria-hidden="true" />
                      <span>Analytics</span>
                    </Link>
                    <Link to={`/member/community/${community.slug}/admin`} className="member-button member-button--secondary" title="Moderation & Admin Panel">
                      <ShieldCheck size={14} aria-hidden="true" />
                      <span>Moderation</span>
                    </Link>
                  </>
                )}

                {isOwner && (
                  <Link to={`/member/community/${community.slug}/edit`} className="member-button member-button--secondary">
                    <Pencil size={14} aria-hidden="true" />
                    <span>Edit Settings</span>
                  </Link>
                )}

                <JoinCommunityButton
                  community={community}
                  isOwner={isOwner}
                  isMember={isMember}
                  isPending={isPending}
                  role={role}
                  onStateChange={handleRefresh}
                />

                {(isMember || isOwner) && (
                  <button
                    type="button"
                    className="member-button member-button--secondary"
                    title="Notification Preferences"
                    onClick={() => setShowNotifModal(true)}
                  >
                    <Bell size={14} aria-hidden="true" />
                    <span>Alerts</span>
                  </button>
                )}

                <button
                  type="button"
                  className="member-button member-button--secondary"
                  onClick={handleShareInvite}
                  title="Copy Invite Link"
                >
                  {copiedInvite ? <Check size={14} color="#16a34a" /> : <Share2 size={14} />}
                  <span>{copiedInvite ? 'Link Copied!' : 'Invite & Share'}</span>
                </button>
              </div>
            </div>

            <div className="community-hero__badges">
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

            <div className="community-hero__meta">
              <span>
                <Users size={14} aria-hidden="true" />
                <span>{Number(community.member_count ?? 0).toLocaleString()} {community.member_count === 1 ? 'member' : 'members'}</span>
              </span>
              <span>
                <UserCheck size={14} aria-hidden="true" />
                <span>Created by <strong>{community.owner?.name || 'Member'}</strong></span>
              </span>
              <span>
                <Calendar size={14} aria-hidden="true" />
                <span>{formatDate(community.created_at)}</span>
              </span>
            </div>
          </div>
        </div>
      </div>

      {/* Sub-Tabs */}
      <nav className="community-nav-tabs" aria-label="Community detail tabs">
        {canViewFeed && (
          <button
            type="button"
            className={`community-nav-tab ${activeTab === 'feed' ? 'is-active' : ''}`}
            onClick={() => handleTabChange('feed')}
          >
            <MessageSquare size={15} aria-hidden="true" />
            <span>Discussion Feed</span>
          </button>
        )}

        <button
          type="button"
          className={`community-nav-tab ${activeTab === 'about' ? 'is-active' : ''}`}
          onClick={() => handleTabChange('about')}
        >
          <Info size={15} aria-hidden="true" />
          <span>About</span>
        </button>

        <button
          type="button"
          className={`community-nav-tab ${activeTab === 'members' ? 'is-active' : ''}`}
          onClick={() => handleTabChange('members')}
        >
          <UsersRound size={15} aria-hidden="true" />
          <span>Members ({community.member_count ?? 0})</span>
        </button>

        <Link
          to={`/member/community/${community.slug}/activity`}
          className="community-nav-tab"
        >
          <Activity size={15} aria-hidden="true" />
          <span>Activity Timeline</span>
        </Link>
      </nav>

      {/* Tab Content: FEED */}
      {activeTab === 'feed' && canViewFeed && (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
          {(isMember || isOwner) && (
            <PostComposer
              currentUser={user}
              communitySlug={community.slug}
              communityName={community.name}
              isCommunityAdmin={isAdmin || isOwner}
              onPostCreated={handleRefresh}
              allowVideo={false}
            />
          )}

          {announcements.length > 0 && (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
              {announcements.map((post) => (
                <PostCard
                  key={post.id}
                  post={post}
                  currentUser={user}
                  communitySlug={community.slug}
                  isCommunityAdmin={isAdmin || isOwner}
                  onPostDeleted={handleRefresh}
                  onPostUpdated={handleRefresh}
                />
              ))}
            </div>
          )}

          {pinnedPost && (
            <PostCard
              post={pinnedPost}
              currentUser={user}
              communitySlug={community.slug}
              isCommunityAdmin={isAdmin || isOwner}
              onPostDeleted={handleRefresh}
              onPostUpdated={handleRefresh}
            />
          )}

          {feedPosts.length > 0 ? (
            feedPosts.map((post) => (
              <PostCard
                key={post.id}
                post={post}
                currentUser={user}
                communitySlug={community.slug}
                isCommunityAdmin={isAdmin || isOwner}
                onPostDeleted={handleRefresh}
                onPostUpdated={handleRefresh}
              />
            ))
          ) : (
            <div className="fb-empty-state card" style={{ padding: '32px', textAlign: 'center' }}>
              <div className="fb-empty-state__icon">
                <MessageSquare size={36} aria-hidden="true" />
              </div>
              <h3>No Community Discussions Yet</h3>
              <p>Be the first member to start a discussion in {community.name}!</p>
            </div>
          )}
        </div>
      )}

      {/* Tab Content: ABOUT */}
      {activeTab === 'about' && (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
          <div className="card" style={{ padding: '24px', borderRadius: '16px' }}>
            <h2 style={{ fontSize: '18px', fontWeight: 800, margin: '0 0 12px 0' }}>About this Community</h2>
            <p style={{ whiteSpace: 'pre-wrap', lineHeight: 1.6, color: 'var(--color-text, #1d2738)' }}>
              {community.description || 'No description provided for this community.'}
            </p>
          </div>

          {community.rules && (
            <div className="card" style={{ padding: '24px', borderRadius: '16px' }}>
              <h2 style={{ fontSize: '18px', fontWeight: 800, margin: '0 0 12px 0' }}>Community Rules</h2>
              <p style={{ whiteSpace: 'pre-wrap', lineHeight: 1.6, color: 'var(--color-text, #1d2738)' }}>
                {community.rules}
              </p>
            </div>
          )}

          <div className="card" style={{ padding: '24px', borderRadius: '16px' }}>
            <h2 style={{ fontSize: '18px', fontWeight: 800, margin: '0 0 16px 0' }}>Community Information</h2>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '16px' }}>
              <div>
                <span style={{ fontSize: '12px', color: 'var(--color-text-secondary, #687386)', display: 'block' }}>Category</span>
                <strong style={{ fontSize: '14px' }}>{community.category}</strong>
              </div>
              <div>
                <span style={{ fontSize: '12px', color: 'var(--color-text-secondary, #687386)', display: 'block' }}>Privacy</span>
                <strong style={{ fontSize: '14px', textTransform: 'capitalize' }}>{community.visibility.replace('_', ' ')}</strong>
              </div>
              <div>
                <span style={{ fontSize: '12px', color: 'var(--color-text-secondary, #687386)', display: 'block' }}>Created</span>
                <strong style={{ fontSize: '14px' }}>{formatDate(community.created_at)}</strong>
              </div>
              {community.tags && (
                <div>
                  <span style={{ fontSize: '12px', color: 'var(--color-text-secondary, #687386)', display: 'block' }}>Tags</span>
                  <strong style={{ fontSize: '14px' }}>{community.tags}</strong>
                </div>
              )}
            </div>
          </div>
        </div>
      )}

      {/* Tab Content: MEMBERS */}
      {activeTab === 'members' && (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
          <div className="card" style={{ padding: '24px', borderRadius: '16px' }}>
            <h2 style={{ fontSize: '18px', fontWeight: 800, margin: '0 0 16px 0' }}>
              Community Members ({community.member_count ?? detailData.accepted_members?.total ?? acceptedMembers.length})
            </h2>

            {acceptedMembers.length === 0 ? (
              <p style={{ color: 'var(--color-text-secondary)' }}>No members found.</p>
            ) : (
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(240px, 1fr))', gap: '14px' }}>
                {acceptedMembers.map((cm) => {
                  const m = cm.member;
                  if (!m) return null;
                  return (
                    <div
                      key={cm.id}
                      style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: '12px',
                        padding: '12px',
                        background: '#f8fafc',
                        borderRadius: '12px',
                        border: '1px solid #e2e8f0',
                      }}
                    >
                      <MemberAvatar member={m} size={44} />
                      <div style={{ flex: 1, minWidth: 0 }}>
                        <Link to={`/member/people/${m.id}`} style={{ fontWeight: 700, fontSize: '14px', color: '#1e293b', textDecoration: 'none', display: 'inline-flex', alignItems: 'center', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                          <span>{m.name}</span>
                          <VerifiedBadge member={m} size={14} />
                        </Link>
                        <span style={{ fontSize: '12px', textTransform: 'capitalize', color: cm.role === 'owner' ? '#4f7df3' : cm.role === 'admin' ? '#16a34a' : '#64748b', fontWeight: 600, display: 'block' }}>
                          {cm.role || 'Member'}
                        </span>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        </div>
      )}

      {/* Notification Preferences Modal */}
      {showNotifModal && (
        <ModalPortal isOpen={showNotifModal} onClose={() => setShowNotifModal(false)}>
          <div
            className="card"
            role="dialog"
            aria-modal="true"
            aria-labelledby="notif-prefs-modal-title"
            style={{ maxWidth: '440px', width: '100%', padding: '24px', borderRadius: '16px', background: '#fff' }}
          >
            <h3 id="notif-prefs-modal-title" style={{ fontSize: '18px', fontWeight: 800, margin: '0 0 8px 0', display: 'flex', alignItems: 'center', gap: '8px' }}>
              <Bell size={18} color="#4f7df3" />
              <span>Notification Preferences</span>
            </h3>
            <p style={{ fontSize: '13px', color: '#64748b', margin: '0 0 16px 0' }}>
              Customize which notifications you receive from {community.name}.
            </p>

            {notifFeedback && (
              <div
                style={{
                  padding: '8px 12px',
                  borderRadius: '8px',
                  marginBottom: '14px',
                  fontSize: '12.5px',
                  background: '#dcfce7',
                  color: '#15803d',
                }}
              >
                {notifFeedback}
              </div>
            )}

            <form onSubmit={handleSaveNotificationPreferences}>
              <div style={{ marginBottom: '14px' }}>
                <label className="community-admin-label" style={{ fontSize: '12.5px', fontWeight: 600, display: 'block', marginBottom: '6px' }}>
                  Notification Level
                </label>
                <select
                  className="community-admin-select"
                  value={notifLevel}
                  onChange={(e) => setNotifLevel(e.target.value)}
                  style={{ width: '100%', padding: '8px 12px', borderRadius: '8px', border: '1px solid #cbd5e1' }}
                >
                  <option value="all">All Notifications</option>
                  <option value="important_only">Important Only</option>
                  <option value="announcements_only">Announcements Only</option>
                  <option value="posts_only">Posts Only</option>
                  <option value="muted">Mute All</option>
                </select>
              </div>

              <div style={{ marginBottom: '20px' }}>
                <label className="community-admin-label" style={{ fontSize: '12.5px', fontWeight: 600, display: 'block', marginBottom: '6px' }}>
                  Mute Duration
                </label>
                <select
                  className="community-admin-select"
                  value={muteDuration}
                  onChange={(e) => setMuteDuration(e.target.value)}
                  style={{ width: '100%', padding: '8px 12px', borderRadius: '8px', border: '1px solid #cbd5e1' }}
                >
                  <option value="none">Don't Mute (Normal)</option>
                  <option value="1h">Mute for 1 Hour</option>
                  <option value="8h">Mute for 8 Hours</option>
                  <option value="24h">Mute for 24 Hours</option>
                  <option value="7d">Mute for 7 Days</option>
                  <option value="forever">Mute Indefinitely</option>
                </select>
              </div>

              <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '10px' }}>
                <button
                  type="button"
                  className="member-button member-button--secondary"
                  onClick={() => setShowNotifModal(false)}
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="member-button member-button--primary"
                  disabled={isSavingNotif}
                >
                  {isSavingNotif ? 'Saving...' : 'Save Preferences'}
                </button>
              </div>
            </form>
          </div>
        </ModalPortal>
      )}

    </div>
  );
}

export default CommunityDetailPage;
