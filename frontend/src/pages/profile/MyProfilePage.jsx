import { useState, useEffect, useRef, useCallback } from 'react';
import { useSearchParams, Link, useNavigate } from 'react-router-dom';
import {
  Camera,
  MapPin,
  Mail,
  Pencil,
  Settings,
  Eye,
  Newspaper,
  User,
  Image as ImageIcon,
  Video as VideoIcon,
  UsersRound,
  CirclePlay,
  Bookmark,
  Heart,
  MessageCircle,
  PlusCircle,
  Compass,
  MessageSquare,
  KeyRound,
  Cake,
  ImageOff,
  VideoOff,
  UserX,
  UserPlus,
  CircleOff,
  BookmarkCheck,
  CalendarDays,
  ShieldCheck,
  UserCheck,
} from 'lucide-react';
import useAuth from '../../hooks/useAuth';
import profileApi from '../../api/profileApi';
import { getAvatarUrl, getCoverUrl, getMediaUrl, getInitials } from '../../utils/assetHelper';
import PostComposer from '../../components/posts/PostComposer';
import PostCard from '../../components/posts/PostCard';
import VerifiedBadge from '../../components/common/VerifiedBadge';
import CompactMemberRow from '../../components/friends/CompactMemberRow';
import AccountVerificationModal from '../../components/verification/AccountVerificationModal';
import ProfileImageAdjustModal from '../../components/profile/ProfileImageAdjustModal';
import ProfileMediaViewerModal from '../../components/profile/ProfileMediaViewerModal';
import MemberAvatar from '../../components/common/MemberAvatar';
import StoryViewer from '../../components/stories/StoryViewer';

function formatDate(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
}

function formatFullDate(dateString) {
  if (!dateString) return 'Not added yet';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
}

export function MyProfilePage() {
  const { user, setUser, refreshUser } = useAuth();
  const [searchParams, setSearchParams] = useSearchParams();
  const navigate = useNavigate();
  const activeTab = searchParams.get('tab') || 'timeline';

  const [profileData, setProfileData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [selectedStoryId, setSelectedStoryId] = useState(null);

  // Photo Upload & Adjustment Modals
  const [photoModalType, setPhotoModalType] = useState(null); // 'avatar' | 'cover' | null
  const [selectedFile, setSelectedFile] = useState(null);
  const [isUploadingPhoto, setIsUploadingPhoto] = useState(false);
  const [photoError, setPhotoError] = useState(null);
  const [showVerificationModal, setShowVerificationModal] = useState(false);
  const [viewMedia, setViewMedia] = useState(null); // { type: 'avatar' | 'cover', url: string } | null
  const [avatarImgError, setAvatarImgError] = useState(false);
  const avatarFileInputRef = useRef(null);
  const coverFileInputRef = useRef(null);
  const tabsNavRef = useRef(null);

  const handleOpenViewMedia = (type) => {
    if (type === 'avatar' && profilePhotoUrl) {
      setViewMedia({ type: 'avatar', url: profilePhotoUrl });
    } else if (type === 'cover' && coverPhotoUrl) {
      setViewMedia({ type: 'cover', url: coverPhotoUrl });
    }
  };

  const handleCloseViewMedia = () => {
    setViewMedia(null);
  };

  const fetchProfileData = useCallback(async (tab = activeTab) => {
    setIsLoading(true);
    setError(null);
    try {
      const data = await profileApi.getProfile({ tab });
      setProfileData(data);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to load profile.');
    } finally {
      setIsLoading(false);
    }
  }, [activeTab]);
 
  const handleStoryDeleted = useCallback(() => {
    setSelectedStoryId(null);
    fetchProfileData(activeTab);
  }, [fetchProfileData, activeTab]);

  useEffect(() => {
    fetchProfileData(activeTab);
  }, [fetchProfileData, activeTab]);

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

  const handleClosePhotoModal = useCallback(() => {
    setPhotoModalType(null);
    setSelectedFile(null);
    setPhotoError(null);
    if (avatarFileInputRef.current) {
      avatarFileInputRef.current.value = '';
    }
    if (coverFileInputRef.current) {
      coverFileInputRef.current.value = '';
    }
  }, []);

  const handleTabChange = (tabKey) => {
    setSearchParams({ tab: tabKey });
  };

  const handleFileChange = (e, type) => {
    const file = e.target.files?.[0];
    if (!file) return;

    if (type === 'avatar' && file.size > 5 * 1024 * 1024) {
      alert('Profile photo size must not exceed 5MB.');
      e.target.value = '';
      return;
    }
    if (type === 'cover' && file.size > 10 * 1024 * 1024) {
      alert('Cover photo size must not exceed 10MB.');
      e.target.value = '';
      return;
    }

    setPhotoModalType(type);
    setSelectedFile(file);
    setPhotoError(null);
  };

  const handleSaveAdjustedPhoto = async (adjustedFile) => {
    if (!adjustedFile || !photoModalType) return;

    setIsUploadingPhoto(true);
    setPhotoError(null);

    const formData = new FormData();
    if (photoModalType === 'avatar') {
      formData.append('profile_photo', adjustedFile);
    } else {
      formData.append('cover_photo', adjustedFile);
    }

    try {
      if (photoModalType === 'avatar') {
        const res = await profileApi.updateProfilePhoto(formData);
        if (res && res.success) {
          const updatedMember = res.member || {
            ...member,
            profile_photo: res.profile_photo || res.photo_url,
          };
          if (typeof setUser === 'function') {
            setUser(updatedMember);
          }
          setProfileData((prev) => ({
            ...prev,
            member: updatedMember,
          }));
          if (typeof refreshUser === 'function') {
            refreshUser();
          }
          handleClosePhotoModal();
          fetchProfileData();
        }
      } else {
        const res = await profileApi.updateCoverPhoto(formData);
        if (res && res.success) {
          const updatedMember = res.member || {
            ...member,
            cover_photo: res.cover_photo || res.photo_url,
          };
          if (typeof setUser === 'function') {
            setUser(updatedMember);
          }
          setProfileData((prev) => ({
            ...prev,
            member: updatedMember,
          }));
          if (typeof refreshUser === 'function') {
            refreshUser();
          }
          handleClosePhotoModal();
          fetchProfileData();
        }
      }
    } catch (err) {
      setPhotoError(err.response?.data?.message || err.message || 'Failed to upload image.');
    } finally {
      setIsUploadingPhoto(false);
    }
  };

  const handleRemovePhoto = async (type) => {
    if (!window.confirm(`Are you sure you want to remove your ${type === 'avatar' ? 'profile' : 'cover'} photo?`)) {
      return;
    }

    setIsUploadingPhoto(true);
    try {
      if (type === 'avatar') {
        const res = await profileApi.removeProfilePhoto();
        const updatedMember = res?.member || { ...member, profile_photo: null };
        if (typeof setUser === 'function') {
          setUser(updatedMember);
        }
        setProfileData((prev) => ({
          ...prev,
          member: updatedMember,
        }));
      } else {
        const res = await profileApi.removeCoverPhoto();
        const updatedMember = res?.member || { ...member, cover_photo: null };
        if (typeof setUser === 'function') {
          setUser(updatedMember);
        }
        setProfileData((prev) => ({
          ...prev,
          member: updatedMember,
        }));
      }
      if (typeof refreshUser === 'function') {
        refreshUser();
      }
      handleClosePhotoModal();
      fetchProfileData();
    } catch (err) {
      alert(err.response?.data?.message || 'Failed to remove photo.');
    } finally {
      setIsUploadingPhoto(false);
    }
  };

  if (isLoading && !profileData) {
    return (
      <main className="member-main" id="member-content">
        <div style={{ textAlign: 'center', padding: '60px', color: 'var(--color-text-secondary)' }}>
          Loading profile...
        </div>
      </main>
    );
  }

  if (error && !profileData) {
    return (
      <main className="member-main" id="member-content">
        <div className="card" style={{ maxWidth: '600px', margin: '40px auto', padding: '32px', textAlign: 'center' }}>
          <h2 style={{ fontSize: '18px', color: '#dc2626', marginBottom: '8px' }}>Unable to Load Profile</h2>
          <p style={{ color: 'var(--color-text-secondary)', marginBottom: '20px' }}>{error}</p>
          <button
            type="button"
            className="member-button member-button--primary"
            onClick={() => fetchProfileData()}
          >
            Try Again
          </button>
        </div>
      </main>
    );
  }

  const member = profileData?.member || user || {};
  const stats = profileData?.stats || {};
  const posts = profileData?.posts?.data || [];
  const photos = profileData?.photos || [];
  const videos = profileData?.videos || [];
  const friendsList = profileData?.friends || [];
  const friendsCount = stats.friends_count ?? (Array.isArray(friendsList) ? friendsList.length : 0);
  const stories = profileData?.stories || [];
  const savedPosts = profileData?.saved_posts?.data || [];

  const directReferrals = profileData?.direct_referrals || [];
  const directReferralsCount = stats.direct_referrals_count ?? profileData?.direct_referral_count ?? directReferrals.length;
  const introducer = profileData?.introducer || null;

  const rawProfilePhoto = member?.profile_photo_url || member?.profile_photo;
  const profilePhotoUrl = rawProfilePhoto ? getAvatarUrl(rawProfilePhoto) : null;
  const coverPhotoUrl = getCoverUrl(member.cover_photo);
  const initials = getInitials(member.name);
  const locationText = [member.city, member.country].filter(Boolean).join(', ');

  const tabsMeta = [
    { key: 'timeline', label: 'Timeline', icon: Newspaper },
    { key: 'about', label: 'About', icon: User },
    { key: 'photos', label: `Photos (${stats.photos_count ?? photos.length})`, icon: ImageIcon },
    { key: 'videos', label: `Videos (${stats.videos_count ?? videos.length})`, icon: VideoIcon },
    { key: 'friends', label: `Connections (${friendsCount})`, icon: UsersRound },
    { key: 'referrals', label: `My Referrals (${directReferralsCount})`, icon: UsersRound },
    { key: 'stories', label: `Stories (${stats.stories_count ?? stories.length})`, icon: CirclePlay },
    { key: 'saved', label: 'Saved Posts', icon: Bookmark },
  ];

  return (
    <main className="member-main" id="member-content">
      <div className="profile-page">
      {/* Profile Header & Hero */}
      <section className="profile-hero" aria-labelledby="profile-name">
        {/* Cover Photo */}
        <div className={`profile-cover ${coverPhotoUrl ? 'has-cover' : ''}`}>
          {coverPhotoUrl ? (
            <img
              className="profile-cover__image"
              id="cover-photo-preview"
              src={coverPhotoUrl}
              alt="Cover Photo"
              onClick={() => handleOpenViewMedia('cover')}
              title="View Cover Photo"
              style={{ cursor: 'pointer' }}
            />
          ) : (
            <div
              className="profile-cover__preview"
              id="cover-photo-preview"
              onClick={() => coverFileInputRef.current?.click()}
              title="Add Cover Photo"
              style={{ cursor: 'pointer' }}
            />
          )}
          <div className="profile-cover__overlay" aria-hidden="true" />

          <div className="profile-cover__actions">
            {coverPhotoUrl && (
              <button
                type="button"
                className="media-upload-button"
                onClick={() => handleOpenViewMedia('cover')}
                aria-label="View Cover Photo"
                title="View Cover Photo"
              >
                <Eye size={18} aria-hidden="true" />
                <span>View Cover</span>
              </button>
            )}

            <button
              type="button"
              className="media-upload-button"
              onClick={() => coverFileInputRef.current?.click()}
              aria-label="Change Cover Photo"
              title="Change Cover Photo"
            >
              <Camera size={18} aria-hidden="true" />
              <span>Change Cover</span>
            </button>
          </div>
        </div>

        {/* Profile Identity Bar */}
        <div className="profile-identity">
          {/* Avatar Container */}
          <div className="profile-avatar-container">
            <div
              className="profile-avatar-clickable"
              onClick={() => {
                if (profilePhotoUrl) {
                  handleOpenViewMedia('avatar');
                } else {
                  avatarFileInputRef.current?.click();
                }
              }}
              title={profilePhotoUrl ? "View Profile Photo" : "Change Profile Photo"}
            >
              {profilePhotoUrl && !avatarImgError ? (
                <img
                  className="profile-avatar"
                  id="profile-photo-preview"
                  src={profilePhotoUrl}
                  alt={member.name}
                  onError={() => setAvatarImgError(true)}
                />
              ) : (
                <div
                  className="profile-avatar profile-avatar--initials"
                  id="profile-photo-preview"
                  role="img"
                  aria-label={member.name}
                >
                  <span>{initials}</span>
                </div>
              )}
              <div
                className="profile-avatar__camera"
                aria-label="Change profile photo"
                title="Change profile photo"
                onClick={(e) => {
                  e.stopPropagation();
                  avatarFileInputRef.current?.click();
                }}
              >
                <Camera size={18} aria-hidden="true" />
              </div>
            </div>
          </div>

          {/* Profile Identity Information */}
          <div className="profile-identity__copy">
            <h1 id="profile-name" style={{ display: 'inline-flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' }}>
              <span>{member.name}</span>
              <VerifiedBadge member={member} size={22} showText={true} />
            </h1>
            {member.user_id && (
              <p className="profile-identity__username">
                <span>@{member.user_id}</span>
              </p>
            )}
            <p className="profile-identity__email">
              <Mail size={16} aria-hidden="true" />
              <span>{member.email}</span>
            </p>
            <p className="profile-identity__bio">
              {member.bio || 'Add a short bio to tell the community about yourself.'}
            </p>
            <div className="profile-identity__meta">
              {locationText && (
                <span>
                  <MapPin size={16} aria-hidden="true" />
                  {locationText}
                </span>
              )}
              <span>
                <CalendarDays size={16} aria-hidden="true" />
                Joined {formatDate(member.created_at)}
              </span>
            </div>
          </div>

          {/* Profile Actions */}
          <div className="profile-actions">
            {member.is_verified || member.mobile_verified_at ? (
              <button
                type="button"
                className="member-button"
                style={{
                  background: '#f0fdf4',
                  border: '1px solid #bbf7d0',
                  color: '#15803d',
                  fontWeight: 700,
                }}
                onClick={() => setShowVerificationModal(true)}
                title="Account Verified on WhatsApp"
              >
                <ShieldCheck size={18} color="#10b981" aria-hidden="true" />
                <span>Verified</span>
              </button>
            ) : (
              <button
                type="button"
                className="member-button member-button--primary"
                style={{
                  background: 'linear-gradient(135deg, #059669 0%, #10b981 100%)',
                  color: '#ffffff',
                  border: 'none',
                  fontWeight: 700,
                }}
                onClick={() => setShowVerificationModal(true)}
                title="Get Verified with Green Tick via WhatsApp"
              >
                <ShieldCheck size={18} aria-hidden="true" />
                <span>Verify Account</span>
              </button>
            )}
            <Link className="member-button member-button--secondary" to="/member/profile/visitors">
              <Eye size={18} aria-hidden="true" />
              <span>Visitors</span>
            </Link>
            <Link className="member-button member-button--primary" to="/member/profile/edit">
              <Pencil size={18} aria-hidden="true" />
              <span>Edit Profile</span>
            </Link>
            <Link className="member-button member-button--secondary" to="/member/account/settings">
              <Settings size={18} aria-hidden="true" />
              <span>Account Settings</span>
            </Link>
          </div>
        </div>

        {/* Complete Profile Statistics Bar (5-Box Grid) */}
        <div className="profile-metrics-bar">
          <div className="profile-stat-box">
            <strong>{stats.posts_count ?? posts.length}</strong>
            <small>Posts</small>
          </div>
          <div className="profile-stat-box">
            <strong>{stats.stories_count ?? stories.length}</strong>
            <small>Stories</small>
          </div>
          <div className="profile-stat-box">
            <Link to="/member/friends" style={{ textDecoration: 'none', color: 'inherit', display: 'flex', flexDirection: 'column', alignItems: 'center', width: '100%' }}>
              <strong>{friendsCount}</strong>
              <small>Connections</small>
            </Link>
          </div>
          <div className="profile-stat-box">
            <strong>{stats.photos_count ?? photos.length}</strong>
            <small>Photos</small>
          </div>
          <div className="profile-stat-box">
            <strong>{stats.videos_count ?? videos.length}</strong>
            <small>Videos</small>
          </div>
        </div>

        {/* Profile Navigation Tabs Bar */}
        <nav className="profile-nav-tabs" aria-label="Profile Sections" data-profile-tabs ref={tabsNavRef}>
          {tabsMeta.map((t) => {
            const IconComponent = t.icon;
            return (
              <button
                key={t.key}
                type="button"
                className={`profile-nav-tab ${activeTab === t.key ? 'is-active' : ''}`}
                onClick={() => handleTabChange(t.key)}
                data-profile-tab={t.key}
              >
                <IconComponent size={18} aria-hidden="true" />
                <span>{t.label}</span>
              </button>
            );
          })}
        </nav>
      </section>

      {/* Dynamic Content Container */}
      <div className="profile-tab-content" data-profile-tab-content>
        {/* 1. TIMELINE TAB */}
        {activeTab === 'timeline' && (
          <div className="post-feed" data-post-feed>
            <PostComposer currentUser={member} onPostCreated={() => fetchProfileData()} allowVideo={false} />
            {posts.length === 0 ? (
              <section className="card post-empty-state" data-post-empty>
                <div className="post-empty-state__icon">
                  <Newspaper size={36} aria-hidden="true" />
                </div>
                <h2>No Posts on Timeline</h2>
                <p>Posts created or shared by {member.name} will appear here.</p>
              </section>
            ) : (
              posts.map((post) => (
                <PostCard
                  key={post.id}
                  post={post}
                  onPostDeleted={() => fetchProfileData()}
                  onPostUpdated={() => fetchProfileData()}
                />
              ))
            )}
          </div>
        )}

        {/* 2. ABOUT TAB */}
        {activeTab === 'about' && (
          <div className="profile-about-layout" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(320px, 100%), 1fr))', gap: '18px' }}>
            <section className="member-card profile-about-card">
              <header className="member-card__header">
                <div>
                  <h2>About</h2>
                  <p>Your personal and contact information.</p>
                </div>
                <button
                  className="member-button member-button--secondary"
                  type="button"
                  onClick={() => navigate('/member/profile/edit')}
                >
                  <Pencil size={16} aria-hidden="true" />
                  <span>Edit</span>
                </button>
              </header>

              <div className="about-grid">
                <div className="about-item">
                  <span><MessageSquare size={18} aria-hidden="true" /></span>
                  <div>
                    <small>Bio</small>
                    <strong>{member.bio || 'Not added yet'}</strong>
                  </div>
                </div>
                <div className="about-item">
                  <span><Mail size={18} aria-hidden="true" /></span>
                  <div>
                    <small>Phone</small>
                    <strong>{member.phone || 'Not added yet'}</strong>
                  </div>
                </div>
                <div className="about-item">
                  <span><Cake size={18} aria-hidden="true" /></span>
                  <div>
                    <small>Date of Birth</small>
                    <strong>{member.date_of_birth ? formatFullDate(member.date_of_birth) : 'Not added yet'}</strong>
                  </div>
                </div>
                <div className="about-item">
                  <span><User size={18} aria-hidden="true" /></span>
                  <div>
                    <small>Gender</small>
                    <strong style={{ textTransform: 'capitalize' }}>
                      {member.gender ? member.gender.replace(/_/g, ' ') : 'Not added yet'}
                    </strong>
                  </div>
                </div>
                <div className="about-item">
                  <span><MapPin size={18} aria-hidden="true" /></span>
                  <div>
                    <small>Location</small>
                    <strong>{locationText || 'Not added yet'}</strong>
                  </div>
                </div>
                <div className="about-item">
                  <span><Compass size={18} aria-hidden="true" /></span>
                  <div>
                    <small>Website</small>
                    {member.website ? (
                      <a href={member.website} target="_blank" rel="noopener noreferrer">
                        {member.website}
                      </a>
                    ) : (
                      <strong>Not added yet</strong>
                    )}
                  </div>
                </div>
              </div>
            </section>

            <section className="member-card profile-account-summary-card">
              <header className="member-card__header">
                <div>
                  <h2>Account summary</h2>
                  <p>Your secure account overview.</p>
                </div>
              </header>

              <div className="about-grid" style={{ gridTemplateColumns: '1fr' }}>
                <div className="about-item">
                  <span><Mail size={18} aria-hidden="true" /></span>
                  <div>
                    <small>Account Email</small>
                    <strong>{member.email}</strong>
                  </div>
                </div>
                <div className="about-item">
                  <span><KeyRound size={18} aria-hidden="true" /></span>
                  <div>
                    <small>Login Method</small>
                    <strong>Email and password</strong>
                  </div>
                </div>
                <div className="about-item">
                  <span><CalendarDays size={18} aria-hidden="true" /></span>
                  <div>
                    <small>Member Since</small>
                    <strong>{formatFullDate(member.created_at)}</strong>
                  </div>
                </div>
              </div>
            </section>
          </div>
        )}

        {/* 3. PHOTOS TAB */}
        {activeTab === 'photos' && (
          <section className="member-card profile-section-card">
            <header className="member-card__header">
              <div>
                <h2><ImageIcon size={20} aria-hidden="true" style={{ verticalAlign: 'middle', marginRight: '6px' }} /> Photos</h2>
                <p>{photos.length} {photos.length === 1 ? 'photo' : 'photos'} shared by {member.name}</p>
              </div>
            </header>

            {photos.length === 0 ? (
              <div className="notification-empty" style={{ padding: '40px 20px' }}>
                <div className="notification-empty__icon">
                  <ImageOff size={36} aria-hidden="true" />
                </div>
                <h2>No Photos Uploaded</h2>
                <p>Photos shared in timeline posts will appear here.</p>
              </div>
            ) : (
              <div className="profile-photos-grid">
                {photos.map((post) => {
                  const photoSrc = post.media_url || getMediaUrl(post.media_path);
                  return (
                    <Link key={post.id} className="profile-photo-item" to={`/member/posts/${post.id}`}>
                      <img
                        src={photoSrc}
                        alt={`Photo shared by ${member.name}`}
                        loading="lazy"
                      />
                      <div className="profile-photo-overlay">
                        <span><Heart size={14} aria-hidden="true" /> {post.reactions_count ?? post.likes_count ?? 0}</span>
                        <span><MessageCircle size={14} aria-hidden="true" /> {post.comments_count ?? 0}</span>
                      </div>
                    </Link>
                  );
                })}
              </div>
            )}
          </section>
        )}

        {/* 4. VIDEOS TAB */}
        {activeTab === 'videos' && (
          <section className="member-card profile-section-card">
            <header className="member-card__header">
              <div>
                <h2><VideoIcon size={20} aria-hidden="true" style={{ verticalAlign: 'middle', marginRight: '6px' }} /> Videos</h2>
                <p>{videos.length} {videos.length === 1 ? 'video' : 'videos'} shared by {member.name}</p>
              </div>
            </header>

            {videos.length === 0 ? (
              <div className="notification-empty" style={{ padding: '40px 20px' }}>
                <div className="notification-empty__icon">
                  <VideoOff size={36} aria-hidden="true" />
                </div>
                <h2>No Videos Uploaded</h2>
                <p>Videos uploaded in timeline posts will appear here.</p>
              </div>
            ) : (
              <div className="profile-videos-grid">
                {videos.map((post) => {
                  const videoSrc = post.media_url || getMediaUrl(post.media_path);
                  return (
                    <div key={post.id} className="profile-video-item">
                      <video controls muted playsInline preload="metadata">
                        <source src={videoSrc} />
                      </video>
                      <div className="profile-video-item__meta">
                        <Link to={`/member/posts/${post.id}`} className="profile-video-item__title">
                          {post.body ? (post.body.length > 60 ? `${post.body.slice(0, 60)}...` : post.body) : 'Video post'}
                        </Link>
                        <time>{formatDate(post.created_at)}</time>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </section>
        )}

        {/* 5. CONNECTIONS TAB */}
        {activeTab === 'friends' && (
          <section className="member-card profile-section-card" style={{ padding: '0', overflow: 'hidden' }}>
            <header className="member-card__header" style={{ padding: '20px 24px 16px', borderBottom: '1px solid var(--color-border-subtle, #f1f5f9)', margin: '0' }}>
              <div>
                <h2><UsersRound size={20} aria-hidden="true" style={{ verticalAlign: 'middle', marginRight: '6px' }} /> Connections</h2>
                <p>{friendsCount} {friendsCount === 1 ? 'connection' : 'connections'}</p>
              </div>
            </header>

            {friendsList.length === 0 ? (
              <div className="notification-empty" style={{ padding: '40px 20px' }}>
                <div className="notification-empty__icon">
                  <UserX size={36} aria-hidden="true" />
                </div>
                <h2>No Connections Yet</h2>
                <p>Accepted connections will appear here.</p>
                <Link to="/member/people/suggestions" className="member-button member-button--primary" style={{ marginTop: '16px' }}>
                  <UserPlus size={16} aria-hidden="true" />
                  <span>Find New Connections</span>
                </Link>
              </div>
            ) : (
              <div className="connection-requests-list" style={{ border: 'none', borderRadius: '0' }}>
                {friendsList.map((friend) => (
                  <CompactMemberRow
                    key={friend.id}
                    member={friend}
                    mode="connection"
                    initialFriendshipState="friends"
                  />
                ))}
              </div>
            )}
          </section>
        )}

        {/* REFERRALS TAB */}
        {activeTab === 'referrals' && (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '20px' }}>
            {/* 1. INTRODUCED BY */}
            <section className="member-card profile-section-card">
              <header className="member-card__header">
                <div>
                  <h2><UserCheck size={20} aria-hidden="true" style={{ verticalAlign: 'middle', marginRight: '6px' }} /> Introduced By</h2>
                  <p>The member who introduced you to MLM Book</p>
                </div>
              </header>

              {!introducer ? (
                <div className="notification-empty" style={{ padding: '30px 20px' }}>
                  <div className="notification-empty__icon">
                    <UserX size={32} aria-hidden="true" />
                  </div>
                  <h2>No Introducer</h2>
                  <p>You registered directly without an introducer.</p>
                </div>
              ) : (
                <div className="profile-friends-grid">
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
            </section>

            {/* 2. MY REFERRALS */}
            <section className="member-card profile-section-card" style={{ padding: '0', overflow: 'hidden' }}>
              <header className="member-card__header" style={{ padding: '20px 24px 16px', borderBottom: '1px solid var(--color-border-subtle, #f1f5f9)', margin: '0' }}>
                <div>
                  <h2><UsersRound size={20} aria-hidden="true" style={{ verticalAlign: 'middle', marginRight: '6px' }} /> My Referrals</h2>
                  <p>Direct Referrals: {directReferralsCount}</p>
                </div>
              </header>

              {directReferrals.length === 0 ? (
                <div className="notification-empty" style={{ padding: '40px 20px' }}>
                  <div className="notification-empty__icon">
                    <UserPlus size={36} aria-hidden="true" />
                  </div>
                  <h2>No Direct Referrals Yet</h2>
                  <p>Members who join using your referral link will appear here.</p>
                </div>
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

        {/* 6. STORIES TAB */}
        {activeTab === 'stories' && (
          <section className="member-card profile-section-card">
            <header className="member-card__header">
              <div>
                <h2><CirclePlay size={20} aria-hidden="true" style={{ verticalAlign: 'middle', marginRight: '6px' }} /> Stories</h2>
                <p>{stories.length} {stories.length === 1 ? 'story' : 'stories'} shared by {member.name}</p>
              </div>
            </header>

            {stories.length === 0 ? (
              <div className="notification-empty" style={{ padding: '40px 20px' }}>
                <div className="notification-empty__icon">
                  <CircleOff size={36} aria-hidden="true" />
                </div>
                <h2>No Stories Shared</h2>
                <p>Uploaded stories will appear here.</p>
                <Link to="/member/stories" className="member-button member-button--primary" style={{ marginTop: '16px' }}>
                  <PlusCircle size={16} aria-hidden="true" />
                  <span>Create a Story</span>
                </Link>
              </div>
            ) : (
              <div className="profile-stories-grid">
                {stories.map((story) => {
                  const isExpired = Boolean(
                    story.is_expired ||
                    story.status === 'expired' ||
                    (story.expires_at && new Date(story.expires_at) <= new Date())
                  );
                  const storyMediaSrc = story.media_url || getMediaUrl(story.media_path, 'stories');
                  return (
                    <button
                      key={story.id}
                      type="button"
                      className={`profile-story-card ${isExpired ? 'is-expired' : ''}`}
                      onClick={() => setSelectedStoryId(story.id)}
                      aria-label={`View story shared on ${formatFullDate(story.created_at)} (${isExpired ? 'Expired' : 'Active'})`}
                    >
                      {story.media_type === 'video' ? (
                        <video preload="metadata">
                          <source src={storyMediaSrc} />
                        </video>
                      ) : (
                        <img
                          src={storyMediaSrc}
                          alt="Story thumbnail"
                          loading="lazy"
                        />
                      )}
                      <div className="profile-story-card__overlay">
                        <span className={`profile-story-card__status profile-story-card__status--${isExpired ? 'expired' : 'active'}`}>
                          {isExpired ? 'Expired' : 'Active'}
                        </span>
                        <div className="story-card-item__stats profile-story-card__stats">
                          <span><Eye size={13} aria-hidden="true" /> {story.views_count ?? 0}</span>
                          <span><Heart size={13} aria-hidden="true" /> {story.likes_count ?? 0}</span>
                          <span><MessageCircle size={13} aria-hidden="true" /> {story.replies_count ?? 0}</span>
                        </div>
                      </div>
                    </button>
                  );
                })}
              </div>
            )}
          </section>
        )}

        {/* 7. SAVED POSTS TAB */}
        {activeTab === 'saved' && (
          <>
            <section className="member-card profile-section-card" style={{ marginBottom: '18px' }}>
              <header className="member-card__header" style={{ marginBottom: 0 }}>
                <div>
                  <h2><Bookmark size={20} aria-hidden="true" style={{ verticalAlign: 'middle', marginRight: '6px' }} /> Saved Posts</h2>
                  <p>Your bookmarked posts and articles saved for later viewing.</p>
                </div>
              </header>
            </section>

            <div className="post-feed" data-post-feed>
              {savedPosts.length === 0 ? (
                <div className="notification-empty" style={{ padding: '40px 20px' }}>
                  <div className="notification-empty__icon">
                    <BookmarkCheck size={36} aria-hidden="true" />
                  </div>
                  <h2>No Saved Posts Yet</h2>
                  <p>Bookmark posts from your feed to view them here anytime.</p>
                  <Link to="/member/dashboard" className="member-button member-button--primary" style={{ marginTop: '16px' }}>
                    <Compass size={16} aria-hidden="true" />
                    <span>Explore Feed</span>
                  </Link>
                </div>
              ) : (
                savedPosts.map((sp) => (
                  <PostCard key={sp.id} post={sp} onPostUpdated={() => fetchProfileData()} />
                ))
              )}
            </div>
          </>
        )}
      </div>

      {/* PHOTO UPLOAD / MANAGEMENT MODAL */}
        {/* Hidden File Inputs for Avatar and Cover */}
        <input
          type="file"
          ref={avatarFileInputRef}
          accept=".jpg,.jpeg,.png,.webp"
          onChange={(e) => handleFileChange(e, 'avatar')}
          style={{ display: 'none' }}
        />
        <input
          type="file"
          ref={coverFileInputRef}
          accept=".jpg,.jpeg,.png,.webp"
          onChange={(e) => handleFileChange(e, 'cover')}
          style={{ display: 'none' }}
        />

        {/* Adjust Profile Photo / Cover Photo Crop & Preview Modal */}
        <ProfileImageAdjustModal
          isOpen={Boolean(photoModalType && selectedFile)}
          type={photoModalType || 'avatar'}
          file={selectedFile}
          onSave={handleSaveAdjustedPhoto}
          onCancel={handleClosePhotoModal}
          onRemove={() => handleRemovePhoto(photoModalType)}
          hasExistingPhoto={photoModalType === 'avatar' ? Boolean(member.profile_photo) : Boolean(member.cover_photo)}
          isUploading={isUploadingPhoto}
          errorMessage={photoError}
        />

        {/* View Profile Photo / Cover Photo Lightbox Modal */}
        <ProfileMediaViewerModal
          isOpen={Boolean(viewMedia)}
          type={viewMedia?.type || 'avatar'}
          imageUrl={viewMedia?.url}
          memberName={member?.name || 'Member'}
          onClose={handleCloseViewMedia}
          onChangePhoto={() => {
            if (viewMedia?.type === 'cover') {
              coverFileInputRef.current?.click();
            } else {
              avatarFileInputRef.current?.click();
            }
          }}
        />

        {/* Account Verification Modal */}
        <AccountVerificationModal
          isOpen={showVerificationModal}
          onClose={() => setShowVerificationModal(false)}
          onVerified={() => {
            fetchProfileData();
            if (typeof refreshUser === 'function') {
              refreshUser();
            }
          }}
        />

        {/* Story Viewer Modal */}
        {selectedStoryId && (
          <StoryViewer
            storyId={selectedStoryId}
            currentMemberId={member?.id || user?.id}
            onClose={() => setSelectedStoryId(null)}
            onStoryDeleted={handleStoryDeleted}
            onNavigateToStory={(id) => setSelectedStoryId(id)}
          />
        )}
      </div>
    </main>
  );
}

export default MyProfilePage;

