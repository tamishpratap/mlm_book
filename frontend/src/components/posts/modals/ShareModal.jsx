import { useState, useEffect } from 'react';
import {
  X,
  Share2,
  Rss,
  Send,
  Link as LinkIcon,
  Check,
  Users,
  Search,
  MessageCircle,
  Mail,
  Copy,
  ExternalLink,
} from 'lucide-react';
import postApi from '../../../api/postApi';
import { getAvatarUrl, getMediaUrl, getInitials } from '../../../utils/assetHelper';
import { ModalPortal } from '../../common/ModalPortal';
import { renderContentWithLinks } from '../../../utils/linkHelper';

function formatRelativeTime(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  const now = new Date();
  const diffSec = Math.floor((now - date) / 1000);
  if (diffSec < 60) return 'just now';
  if (diffSec < 3600) return `${Math.floor(diffSec / 60)}m ago`;
  if (diffSec < 86400) return `${Math.floor(diffSec / 3600)}h ago`;
  return date.toLocaleDateString();
}

function InstagramIcon({ size = 20, color = 'currentColor' }) {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke={color}
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    >
      <rect width="20" height="20" x="2" y="2" rx="5" ry="5" />
      <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
      <line x1="17.5" x2="17.51" y1="6.5" y2="6.5" />
    </svg>
  );
}

export function ShareModal({
  isOpen,
  onClose,
  post,
  businessPage,
  entityType = 'post',
  onShareSuccess,
}) {
  const [activeTab, setActiveTab] = useState('social'); // 'social' | 'feed' | 'friend'
  const [shareMessage, setShareMessage] = useState('');
  const [friendNote, setFriendNote] = useState('');
  const [friends, setFriends] = useState([]);
  const [friendsSearchQuery, setFriendsSearchQuery] = useState('');
  const [selectedFriendIds, setSelectedFriendIds] = useState([]);
  const [isLoadingFriends, setIsLoadingFriends] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [copied, setCopied] = useState(false);
  const [error, setError] = useState(null);
  const [successMessage, setSuccessMessage] = useState(null);



  // Fetch accepted friends when switching to "friend" tab
  useEffect(() => {
    if (isOpen && activeTab === 'friend' && friends.length === 0) {
      setIsLoadingFriends(true);
      postApi
        .getFriends()
        .then((data) => {
          if (data && Array.isArray(data.friends)) {
            setFriends(data.friends);
          } else if (data && data.friends && Array.isArray(data.friends.data)) {
            setFriends(data.friends.data);
          } else if (Array.isArray(data)) {
            setFriends(data);
          }
        })
        .catch(() => {})
        .finally(() => {
          setIsLoadingFriends(false);
        });
    }
  }, [isOpen, activeTab, friends.length]);

  if (!isOpen || (!post && !businessPage)) return null;

  const isBizPage = Boolean(businessPage || entityType === 'business_page');
  const bizPageData = businessPage || (post?.business_page);

  let canonicalUrl = '';
  let entityTitle = '';
  let entityCategory = '';
  let entityDescription = '';
  let photoUrl = null;
  let initials = 'BP';
  let shareTitle = '';
  let mediaPath = null;
  let targetPost = null;
  let authorName = '';

  if (isBizPage && bizPageData) {
    canonicalUrl = `${window.location.origin}/member/business-pages/${bizPageData.slug}`;
    entityTitle = bizPageData.page_name || 'Business Page';
    entityCategory = bizPageData.category || 'Business';
    entityDescription = bizPageData.description || '';
    photoUrl = bizPageData.logo
      ? (bizPageData.logo.startsWith('http') ? bizPageData.logo : `/${bizPageData.logo}`)
      : null;
    initials = getInitials(entityTitle);
    shareTitle = `Check out ${entityTitle} on MLM Book! ${entityDescription ? (entityDescription.length > 90 ? entityDescription.substring(0, 90) + '...' : entityDescription) : ''}`;
  } else if (post) {
    targetPost = post.original_post || post.originalPost || post;
    const bizPage = targetPost.business_page;
    const author = bizPage || targetPost.member;
    authorName = bizPage ? bizPage.page_name : author?.name || 'Member';
    photoUrl = bizPage
      ? (bizPage.logo ? getAvatarUrl(bizPage.logo) : null)
      : (author?.profile_photo ? getAvatarUrl(author.profile_photo) : null);
    initials = getInitials(authorName);
    mediaPath = targetPost.media_path ? getMediaUrl(targetPost.media_path) : null;
    canonicalUrl = `${window.location.origin}/member/posts/${targetPost.id}`;
    shareTitle = targetPost.body
      ? (targetPost.body.length > 90 ? targetPost.body.substring(0, 90) + '...' : targetPost.body)
      : `Check out this post by ${authorName} on MLM Book!`;
  }

  const encodedUrl = encodeURIComponent(canonicalUrl);
  const encodedTitle = encodeURIComponent(shareTitle);

  // Filtered friends list for Send to Friends tab
  const filteredFriends = friends.filter((f) => {
    if (!friendsSearchQuery.trim()) return true;
    const query = friendsSearchQuery.toLowerCase();
    const nameMatch = f.name && f.name.toLowerCase().includes(query);
    const userMatch = f.user_id && f.user_id.toLowerCase().includes(query);
    return nameMatch || userMatch;
  });

  const handleCopyLink = () => {
    navigator.clipboard.writeText(canonicalUrl).then(() => {
      setCopied(true);
      setSuccessMessage(isBizPage ? 'Business Page link copied to clipboard!' : 'Post link copied to clipboard!');
      setTimeout(() => {
        setCopied(false);
        setSuccessMessage(null);
      }, 2500);
    });
  };

  const handleInstagramShare = () => {
    navigator.clipboard.writeText(canonicalUrl).then(() => {
      setCopied(true);
      setSuccessMessage(isBizPage ? 'Business Page link copied! Open Instagram to paste and share.' : 'Post link copied! Open Instagram to paste and share.');
      window.open('https://www.instagram.com/', '_blank', 'noopener,noreferrer');
      setTimeout(() => {
        setCopied(false);
        setSuccessMessage(null);
      }, 3500);
    });
  };

  const handleNativeShare = async () => {
    if (typeof navigator !== 'undefined' && navigator.share) {
      try {
        await navigator.share({
          title: shareTitle,
          text: shareTitle,
          url: canonicalUrl,
        });
      } catch {
        // Dismissed or unsupported
      }
    } else {
      handleCopyLink();
    }
  };

  const handleShareToFeed = async (e) => {
    e.preventDefault();
    setIsSubmitting(true);
    setError(null);
    try {
      let response;
      if (isBizPage) {
        const formData = new FormData();
        const bodyContent = shareMessage.trim()
          ? `${shareMessage.trim()}\n\n${canonicalUrl}`
          : `Check out ${entityTitle} on MLM Book!\n${canonicalUrl}`;
        formData.append('body', bodyContent);
        response = await postApi.createPost(formData);
        setSuccessMessage('Business Page shared to your feed successfully!');
      } else {
        response = await postApi.sharePost(targetPost.id, shareMessage.trim());
        setSuccessMessage('Post shared to your feed successfully!');
      }
      if (onShareSuccess) onShareSuccess(response);
      setTimeout(() => {
        onClose();
        setSuccessMessage(null);
        setShareMessage('');
      }, 1200);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to share. Please try again.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleSendToFriends = async (e) => {
    e.preventDefault();
    if (selectedFriendIds.length === 0) {
      setError('Please select at least one connection.');
      return;
    }
    setIsSubmitting(true);
    setError(null);
    try {
      if (isBizPage) {
        const messageContent = friendNote.trim()
          ? `${friendNote.trim()}\n\n${canonicalUrl}`
          : `Check out ${entityTitle} on MLM Book: ${canonicalUrl}`;
        const formData = new FormData();
        formData.append('body', messageContent);
        await postApi.createPost(formData);
        setSuccessMessage('Business Page shared with your connections successfully!');
      } else {
        await postApi.sendToFriends(targetPost.id, selectedFriendIds, friendNote.trim());
        setSuccessMessage('Post sent to your connections successfully!');
      }
      setTimeout(() => {
        onClose();
        setSuccessMessage(null);
        setSelectedFriendIds([]);
        setFriendNote('');
      }, 1200);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to send share.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const toggleFriendSelection = (id) => {
    setSelectedFriendIds((prev) =>
      prev.includes(id) ? prev.filter((item) => item !== id) : [...prev, id]
    );
  };

  const toggleSelectAll = () => {
    if (selectedFriendIds.length === filteredFriends.length && filteredFriends.length > 0) {
      setSelectedFriendIds([]);
    } else {
      setSelectedFriendIds(filteredFriends.map((f) => f.id));
    }
  };

  if (!isOpen) return null;

  return (
    <ModalPortal isOpen={isOpen} onClose={onClose}>
      <div
        className="story-modal__panel post-share-modal__panel card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="post-share-modal-title"
        style={{
          maxWidth: '560px',
          width: '100%',
          maxHeight: 'min(90vh, 720px)',
          display: 'flex',
          flexDirection: 'column',
          borderRadius: '18px',
          overflow: 'hidden',
          backgroundColor: '#ffffff',
          boxShadow: '0 25px 50px -12px rgba(15, 23, 42, 0.35)',
        }}
      >
        {/* Header */}
        <header
          className="story-modal__header"
          style={{
            padding: '16px 20px',
            borderBottom: '1px solid var(--color-border-soft, #f1f5f9)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            flexShrink: 0,
            background: '#ffffff',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <span
              style={{
                width: '36px',
                height: '36px',
                borderRadius: '10px',
                background: 'rgba(79, 125, 243, 0.1)',
                color: '#4f7df3',
                display: 'grid',
                placeItems: 'center',
                flexShrink: 0,
              }}
            >
              <Share2 size={18} />
            </span>
            <div>
              <span style={{ fontSize: '0.75rem', fontWeight: 600, color: 'var(--color-text-secondary, #64748b)', textTransform: 'uppercase', letterSpacing: '0.04em' }}>
                {isBizPage ? 'Share Business Page' : 'Share Post'}
              </span>
              <h2 id="post-share-modal-title" style={{ margin: 0, fontSize: '1.15rem', fontWeight: 800, color: 'var(--color-text-main, #0f172a)' }}>
                Share Options
              </h2>
            </div>
          </div>
          <button
            className="story-modal__close"
            type="button"
            aria-label="Close Modal"
            onClick={onClose}
            style={{ width: '32px', height: '32px', borderRadius: '50%', background: '#f1f5f9', border: 'none', display: 'grid', placeItems: 'center', cursor: 'pointer', color: '#475569' }}
          >
            <X size={18} aria-hidden="true" />
          </button>
        </header>

        {/* Share Options Navigation Tabs */}
        <div
          className="share-options-nav"
          style={{
            display: 'flex',
            gap: '6px',
            padding: '10px 16px',
            background: 'var(--color-surface-alt, #f8fafc)',
            borderBottom: '1px solid var(--color-border-soft, #e2e8f0)',
            flexShrink: 0,
          }}
        >
          <button
            type="button"
            className={`share-option-tab ${activeTab === 'social' ? 'is-active' : ''}`}
            onClick={() => {
              setActiveTab('social');
              setError(null);
            }}
            style={{
              flex: 1,
              padding: '8px 12px',
              borderRadius: '8px',
              border: activeTab === 'social' ? '1px solid #4f7df3' : '1px solid transparent',
              background: activeTab === 'social' ? '#ffffff' : 'transparent',
              color: activeTab === 'social' ? '#4f7df3' : 'var(--color-text-secondary, #64748b)',
              fontWeight: activeTab === 'social' ? 700 : 500,
              fontSize: '0.825rem',
              display: 'inline-flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '6px',
              cursor: 'pointer',
              boxShadow: activeTab === 'social' ? '0 2px 4px rgba(0,0,0,0.05)' : 'none',
              transition: 'all 0.15s ease',
            }}
          >
            <Share2 size={15} aria-hidden="true" />
            <span>Social Share</span>
          </button>

          <button
            type="button"
            className={`share-option-tab ${activeTab === 'feed' ? 'is-active' : ''}`}
            onClick={() => {
              setActiveTab('feed');
              setError(null);
            }}
            style={{
              flex: 1,
              padding: '8px 12px',
              borderRadius: '8px',
              border: activeTab === 'feed' ? '1px solid #4f7df3' : '1px solid transparent',
              background: activeTab === 'feed' ? '#ffffff' : 'transparent',
              color: activeTab === 'feed' ? '#4f7df3' : 'var(--color-text-secondary, #64748b)',
              fontWeight: activeTab === 'feed' ? 700 : 500,
              fontSize: '0.825rem',
              display: 'inline-flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '6px',
              cursor: 'pointer',
              boxShadow: activeTab === 'feed' ? '0 2px 4px rgba(0,0,0,0.05)' : 'none',
              transition: 'all 0.15s ease',
            }}
          >
            <Rss size={15} aria-hidden="true" />
            <span>Share to Feed</span>
          </button>

          <button
            type="button"
            className={`share-option-tab ${activeTab === 'friend' ? 'is-active' : ''}`}
            onClick={() => {
              setActiveTab('friend');
              setError(null);
            }}
            style={{
              flex: 1,
              padding: '8px 12px',
              borderRadius: '8px',
              border: activeTab === 'friend' ? '1px solid #4f7df3' : '1px solid transparent',
              background: activeTab === 'friend' ? '#ffffff' : 'transparent',
              color: activeTab === 'friend' ? '#4f7df3' : 'var(--color-text-secondary, #64748b)',
              fontWeight: activeTab === 'friend' ? 700 : 500,
              fontSize: '0.825rem',
              display: 'inline-flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '6px',
              cursor: 'pointer',
              boxShadow: activeTab === 'friend' ? '0 2px 4px rgba(0,0,0,0.05)' : 'none',
              transition: 'all 0.15s ease',
            }}
          >
            <Send size={15} aria-hidden="true" />
            <span>Send to Friend</span>
          </button>
        </div>

        {/* Global Feedback Banners */}
        {error && (
          <div style={{ padding: '10px 18px', background: 'rgba(239, 68, 68, 0.1)', color: '#dc2626', fontSize: '0.85rem', fontWeight: 600, flexShrink: 0 }}>
            {error}
          </div>
        )}
        {successMessage && (
          <div style={{ padding: '10px 18px', background: 'rgba(32, 200, 117, 0.12)', color: '#16a34a', fontSize: '0.85rem', fontWeight: 600, display: 'flex', alignItems: 'center', gap: '6px', flexShrink: 0 }}>
            <Check size={16} />
            <span>{successMessage}</span>
          </div>
        )}

        {/* TAB 1: SOCIAL SHARE (WhatsApp, Facebook, Instagram, Telegram, X, LinkedIn, Email, Native, Copy Link) */}
        {activeTab === 'social' && (
          <div
            className="share-tab-panel"
            style={{
              display: 'flex',
              flexDirection: 'column',
              flex: 1,
              minHeight: 0,
              overflow: 'hidden',
            }}
          >
            <div
              className="post-share-modal__body"
              style={{
                flex: 1,
                minHeight: 0,
                overflowY: 'auto',
                overscrollBehavior: 'contain',
                WebkitOverflowScrolling: 'touch',
                padding: '20px 22px',
              }}
            >
              {/* Direct URL Input & Copy Bar */}
              <div style={{ marginBottom: '18px' }}>
                <label style={{ fontSize: '0.8rem', fontWeight: 700, color: 'var(--color-text-secondary, #64748b)', display: 'block', marginBottom: '6px' }}>
                  {isBizPage ? 'Direct Business Page Link' : 'Direct Post Link'}
                </label>
                <div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
                  <input
                    type="text"
                    readOnly
                    value={canonicalUrl}
                    style={{
                      flex: 1,
                      padding: '9px 12px',
                      border: '1px solid #cbd5e1',
                      borderRadius: '10px',
                      fontSize: '0.825rem',
                      background: '#f8fafc',
                      color: '#0f172a',
                      fontFamily: 'monospace',
                      boxSizing: 'border-box',
                      outline: 'none',
                    }}
                    onClick={(e) => e.target.select()}
                  />
                  <button
                    type="button"
                    onClick={handleCopyLink}
                    className="member-button member-button--primary"
                    style={{
                      padding: '9px 16px',
                      fontSize: '0.825rem',
                      fontWeight: 700,
                      borderRadius: '10px',
                      flexShrink: 0,
                      display: 'inline-flex',
                      alignItems: 'center',
                      gap: '6px',
                      cursor: 'pointer',
                      background: copied ? '#20c875' : '#4f7df3',
                    }}
                  >
                    {copied ? <Check size={15} /> : <Copy size={15} />}
                    <span>{copied ? 'Copied!' : 'Copy Link'}</span>
                  </button>
                </div>
              </div>

              {/* Social Share Grid */}
              <div>
                <p style={{ margin: '0 0 10px 0', fontSize: '0.8rem', fontWeight: 700, color: 'var(--color-text-secondary, #64748b)' }}>
                  Share to external networks:
                </p>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(115px, 1fr))', gap: '10px' }}>
                  {/* WhatsApp */}
                  <a
                    href={`https://api.whatsapp.com/send?text=${encodedTitle}%20${encodedUrl}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    style={{
                      display: 'flex',
                      flexDirection: 'column',
                      alignItems: 'center',
                      justifyContent: 'center',
                      gap: '6px',
                      padding: '12px 8px',
                      borderRadius: '12px',
                      border: '1px solid rgba(37, 211, 102, 0.25)',
                      background: 'rgba(37, 211, 102, 0.06)',
                      color: '#15803d',
                      textDecoration: 'none',
                      fontSize: '0.8rem',
                      fontWeight: 700,
                      transition: 'all 0.15s ease',
                    }}
                  >
                    <MessageCircle size={20} color="#25d366" />
                    <span>WhatsApp</span>
                  </a>

                  {/* Facebook */}
                  <a
                    href={`https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    style={{
                      display: 'flex',
                      flexDirection: 'column',
                      alignItems: 'center',
                      justifyContent: 'center',
                      gap: '6px',
                      padding: '12px 8px',
                      borderRadius: '12px',
                      border: '1px solid rgba(24, 119, 242, 0.25)',
                      background: 'rgba(24, 119, 242, 0.06)',
                      color: '#1877f2',
                      textDecoration: 'none',
                      fontSize: '0.8rem',
                      fontWeight: 700,
                      transition: 'all 0.15s ease',
                    }}
                  >
                    <ExternalLink size={20} color="#1877f2" />
                    <span>Facebook</span>
                  </a>

                  {/* Instagram */}
                  <button
                    type="button"
                    onClick={handleInstagramShare}
                    style={{
                      display: 'flex',
                      flexDirection: 'column',
                      alignItems: 'center',
                      justifyContent: 'center',
                      gap: '6px',
                      padding: '12px 8px',
                      borderRadius: '12px',
                      border: '1px solid rgba(225, 48, 108, 0.25)',
                      background: 'rgba(225, 48, 108, 0.06)',
                      color: '#c13584',
                      cursor: 'pointer',
                      fontSize: '0.8rem',
                      fontWeight: 700,
                      transition: 'all 0.15s ease',
                    }}
                  >
                    <InstagramIcon size={20} color="#e1306c" />
                    <span>Instagram</span>
                  </button>

                  {/* Telegram */}
                  <a
                    href={`https://t.me/share/url?url=${encodedUrl}&text=${encodedTitle}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    style={{
                      display: 'flex',
                      flexDirection: 'column',
                      alignItems: 'center',
                      justifyContent: 'center',
                      gap: '6px',
                      padding: '12px 8px',
                      borderRadius: '12px',
                      border: '1px solid rgba(34, 158, 217, 0.25)',
                      background: 'rgba(34, 158, 217, 0.06)',
                      color: '#0284c7',
                      textDecoration: 'none',
                      fontSize: '0.8rem',
                      fontWeight: 700,
                      transition: 'all 0.15s ease',
                    }}
                  >
                    <Send size={20} color="#229ed9" />
                    <span>Telegram</span>
                  </a>

                  {/* X (Twitter) */}
                  <a
                    href={`https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedTitle}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    style={{
                      display: 'flex',
                      flexDirection: 'column',
                      alignItems: 'center',
                      justifyContent: 'center',
                      gap: '6px',
                      padding: '12px 8px',
                      borderRadius: '12px',
                      border: '1px solid rgba(15, 20, 25, 0.2)',
                      background: 'rgba(15, 20, 25, 0.04)',
                      color: '#0f172a',
                      textDecoration: 'none',
                      fontSize: '0.8rem',
                      fontWeight: 700,
                      transition: 'all 0.15s ease',
                    }}
                  >
                    <Share2 size={20} color="#0f172a" />
                    <span>X / Twitter</span>
                  </a>

                  {/* LinkedIn */}
                  <a
                    href={`https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    style={{
                      display: 'flex',
                      flexDirection: 'column',
                      alignItems: 'center',
                      justifyContent: 'center',
                      gap: '6px',
                      padding: '12px 8px',
                      borderRadius: '12px',
                      border: '1px solid rgba(10, 102, 194, 0.25)',
                      background: 'rgba(10, 102, 194, 0.06)',
                      color: '#0a66c2',
                      textDecoration: 'none',
                      fontSize: '0.8rem',
                      fontWeight: 700,
                      transition: 'all 0.15s ease',
                    }}
                  >
                    <LinkIcon size={20} color="#0a66c2" />
                    <span>LinkedIn</span>
                  </a>

                  {/* Email */}
                  <a
                    href={`mailto:?subject=${encodedTitle}&body=${encodeURIComponent('Check out ' + (isBizPage ? entityTitle : authorName) + ' on MLM Book: ' + canonicalUrl)}`}
                    style={{
                      display: 'flex',
                      flexDirection: 'column',
                      alignItems: 'center',
                      justifyContent: 'center',
                      gap: '6px',
                      padding: '12px 8px',
                      borderRadius: '12px',
                      border: '1px solid rgba(100, 116, 139, 0.25)',
                      background: 'rgba(100, 116, 139, 0.06)',
                      color: '#475569',
                      textDecoration: 'none',
                      fontSize: '0.8rem',
                      fontWeight: 700,
                      transition: 'all 0.15s ease',
                    }}
                  >
                    <Mail size={20} color="#475569" />
                    <span>Email</span>
                  </a>

                  {/* Native Share */}
                  {typeof navigator !== 'undefined' && typeof navigator.share === 'function' && (
                    <button
                      type="button"
                      onClick={handleNativeShare}
                      style={{
                        display: 'flex',
                        flexDirection: 'column',
                        alignItems: 'center',
                        justifyContent: 'center',
                        gap: '6px',
                        padding: '12px 8px',
                        borderRadius: '12px',
                        border: '1px solid rgba(79, 125, 243, 0.25)',
                        background: 'rgba(79, 125, 243, 0.06)',
                        color: '#4f7df3',
                        cursor: 'pointer',
                        fontSize: '0.8rem',
                        fontWeight: 700,
                        transition: 'all 0.15s ease',
                      }}
                    >
                      <Share2 size={20} color="#4f7df3" />
                      <span>Native Share</span>
                    </button>
                  )}
                </div>
              </div>
            </div>

            <footer
              style={{
                display: 'flex',
                justifyContent: 'flex-end',
                padding: '12px 20px',
                borderTop: '1px solid var(--color-border, #e2e8f0)',
                background: '#f8fafc',
                flexShrink: 0,
              }}
            >
              <button
                type="button"
                className="member-button member-button--secondary"
                onClick={onClose}
                style={{ padding: '8px 20px', borderRadius: '10px', minWidth: '90px' }}
              >
                Close
              </button>
            </footer>
          </div>
        )}

        {/* TAB 2: SHARE TO FEED */}
        {activeTab === 'feed' && (
          <form
            className="post-share-modal__form share-tab-panel"
            onSubmit={handleShareToFeed}
            style={{
              display: 'flex',
              flexDirection: 'column',
              flex: 1,
              minHeight: 0,
              overflow: 'hidden',
              margin: 0,
            }}
          >
            <div
              className="post-share-modal__body"
              style={{
                flex: 1,
                minHeight: 0,
                overflowY: 'auto',
                overscrollBehavior: 'contain',
                WebkitOverflowScrolling: 'touch',
                padding: '18px 22px',
                display: 'flex',
                flexDirection: 'column',
                gap: '14px',
              }}
            >
              <div className="post-share-modal__input-wrap">
                <textarea
                  className="post-share-modal__textarea"
                  name="share_message"
                  placeholder="Say something about this..."
                  aria-label="Say something about this"
                  maxLength={1000}
                  rows={3}
                  value={shareMessage}
                  onChange={(e) => setShareMessage(e.target.value)}
                  autoFocus
                  style={{
                    width: '100%',
                    minHeight: '70px',
                    maxHeight: '140px',
                    padding: '10px 12px',
                    borderRadius: '10px',
                    border: '1px solid #cbd5e1',
                    fontSize: '0.875rem',
                    resize: 'vertical',
                    boxSizing: 'border-box',
                    outline: 'none',
                  }}
                />
              </div>

              {/* Preview Box */}
              {isBizPage ? (
                <div
                  className="shared-post-preview"
                  style={{
                    border: '1px solid #e2e8f0',
                    borderRadius: '12px',
                    padding: '12px',
                    background: '#f8fafc',
                  }}
                >
                  <div className="shared-post-preview__header" style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '8px' }}>
                    <div className="shared-post-preview__avatar-wrap">
                      {photoUrl ? (
                        <img
                          className="shared-post-preview__avatar"
                          src={photoUrl}
                          alt={entityTitle}
                          style={{ width: '36px', height: '36px', borderRadius: '10px', objectFit: 'cover' }}
                        />
                      ) : (
                        <span
                          className="shared-post-preview__avatar shared-post-preview__avatar--initials"
                          aria-hidden="true"
                          style={{
                            width: '36px',
                            height: '36px',
                            borderRadius: '10px',
                            background: '#4f7df3',
                            color: '#fff',
                            display: 'grid',
                            placeItems: 'center',
                            fontWeight: 700,
                            fontSize: '0.8rem',
                          }}
                        >
                          {initials}
                        </span>
                      )}
                    </div>
                    <div className="shared-post-preview__info">
                      <strong className="shared-post-preview__name" style={{ fontSize: '0.875rem', color: '#0f172a' }}>{entityTitle}</strong>
                      <span className="shared-post-preview__time" style={{ display: 'block', fontSize: '0.75rem', color: '#64748b' }}>
                        {entityCategory}
                      </span>
                    </div>
                  </div>

                  {entityDescription && (
                    <p className="shared-post-preview__text" style={{ margin: '0', fontSize: '0.85rem', color: '#334155', lineHeight: 1.4, wordBreak: 'break-word' }}>
                      {entityDescription.length > 250
                        ? `${entityDescription.substring(0, 250)}...`
                        : entityDescription}
                    </p>
                  )}
                </div>
              ) : targetPost ? (
                <div
                  className="shared-post-preview"
                  style={{
                    border: '1px solid #e2e8f0',
                    borderRadius: '12px',
                    padding: '12px',
                    background: '#f8fafc',
                  }}
                >
                  <div className="shared-post-preview__header" style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '8px' }}>
                    <div className="shared-post-preview__avatar-wrap">
                      {photoUrl ? (
                        <img
                          className="shared-post-preview__avatar"
                          src={photoUrl}
                          alt={authorName}
                          style={{ width: '36px', height: '36px', borderRadius: '50%', objectFit: 'cover' }}
                        />
                      ) : (
                        <span
                          className="shared-post-preview__avatar shared-post-preview__avatar--initials"
                          aria-hidden="true"
                          style={{
                            width: '36px',
                            height: '36px',
                            borderRadius: '50%',
                            background: '#4f7df3',
                            color: '#fff',
                            display: 'grid',
                            placeItems: 'center',
                            fontWeight: 700,
                            fontSize: '0.8rem',
                          }}
                        >
                          {getInitials(authorName)}
                        </span>
                      )}
                    </div>
                    <div className="shared-post-preview__info">
                      <strong className="shared-post-preview__name" style={{ fontSize: '0.875rem', color: '#0f172a' }}>{authorName}</strong>
                      <span className="shared-post-preview__time" style={{ display: 'block', fontSize: '0.75rem', color: '#64748b' }}>
                        {formatRelativeTime(targetPost.created_at)}
                      </span>
                    </div>
                  </div>

                  {targetPost.body && (
                    <p className="shared-post-preview__text" style={{ margin: '0 0 8px 0', fontSize: '0.85rem', color: '#334155', lineHeight: 1.4, wordBreak: 'break-word' }}>
                      {renderContentWithLinks(targetPost.body.length > 250
                        ? `${targetPost.body.substring(0, 250)}...`
                        : targetPost.body)}
                    </p>
                  )}

                  {mediaPath && (
                    <div style={{ maxHeight: '180px', overflow: 'hidden', borderRadius: '8px', background: '#0f172a', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                      {targetPost.media_type === 'video' ? (
                        <video
                          src={mediaPath}
                          controls
                          style={{ width: '100%', maxHeight: '180px', objectFit: 'contain' }}
                        />
                      ) : (
                        <img
                          src={mediaPath}
                          alt="Original Post Media"
                          style={{ width: '100%', maxHeight: '180px', objectFit: 'contain' }}
                        />
                      )}
                    </div>
                  )}
                </div>
              ) : null}
            </div>

            <footer
              className="post-share-modal__footer"
              style={{
                display: 'flex',
                gap: '10px',
                justifyContent: 'flex-end',
                padding: '12px 20px',
                borderTop: '1px solid var(--color-border, #e2e8f0)',
                background: '#f8fafc',
                flexShrink: 0,
              }}
            >
              <button
                type="button"
                className="member-button member-button--secondary"
                onClick={onClose}
                disabled={isSubmitting}
                style={{ padding: '8px 18px', borderRadius: '10px', minWidth: '90px' }}
              >
                Cancel
              </button>
              <button
                type="submit"
                className="member-button member-button--primary"
                disabled={isSubmitting}
                style={{ padding: '8px 20px', borderRadius: '10px', fontWeight: 700, minWidth: '120px' }}
              >
                {isSubmitting ? 'Sharing...' : 'Share Now'}
              </button>
            </footer>
          </form>
        )}

        {/* TAB 3: SEND TO FRIEND */}
        {activeTab === 'friend' && (
          <form
            className="post-share-modal__form share-tab-panel"
            onSubmit={handleSendToFriends}
            style={{
              display: 'flex',
              flexDirection: 'column',
              flex: 1,
              minHeight: 0,
              overflow: 'hidden',
              margin: 0,
            }}
          >
            <div
              className="post-share-modal__body"
              style={{
                flex: 1,
                minHeight: 0,
                overflowY: 'auto',
                overscrollBehavior: 'contain',
                WebkitOverflowScrolling: 'touch',
                padding: '18px 22px',
                display: 'flex',
                flexDirection: 'column',
                gap: '12px',
              }}
            >
              {/* Friends Search Input */}
              <div style={{ position: 'relative' }}>
                <Search size={16} style={{ position: 'absolute', left: '10px', top: '50%', transform: 'translateY(-50%)', color: '#94a3b8' }} />
                <input
                  type="text"
                  placeholder="Search connections..."
                  value={friendsSearchQuery}
                  onChange={(e) => setFriendsSearchQuery(e.target.value)}
                  style={{
                    width: '100%',
                    padding: '8px 12px 8px 34px',
                    borderRadius: '10px',
                    border: '1px solid #cbd5e1',
                    fontSize: '0.825rem',
                    boxSizing: 'border-box',
                    outline: 'none',
                  }}
                />
              </div>

              {/* Select All Checkbox */}
              {filteredFriends.length > 0 && (
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '0 4px' }}>
                  <label style={{ display: 'inline-flex', alignItems: 'center', gap: '6px', fontSize: '0.78rem', color: '#475569', cursor: 'pointer' }}>
                    <input
                      type="checkbox"
                      checked={selectedFriendIds.length === filteredFriends.length && filteredFriends.length > 0}
                      onChange={toggleSelectAll}
                      style={{ accentColor: '#4f7df3', cursor: 'pointer' }}
                    />
                    <span>Select All Available</span>
                  </label>
                  <span style={{ fontSize: '0.75rem', color: '#64748b' }}>
                    {selectedFriendIds.length} of {filteredFriends.length} selected
                  </span>
                </div>
              )}

              {/* Friends List */}
              <div
                style={{
                  maxHeight: '180px',
                  minHeight: '80px',
                  overflowY: 'auto',
                  border: '1px solid #e2e8f0',
                  borderRadius: '10px',
                  padding: '6px',
                  background: '#ffffff',
                }}
              >
                {isLoadingFriends ? (
                  <div style={{ padding: '24px 0', textAlign: 'center', color: '#64748b', fontSize: '0.825rem' }}>
                    Loading connections...
                  </div>
                ) : filteredFriends.length > 0 ? (
                  filteredFriends.map((friend) => {
                    const friendPhoto = friend.profile_photo ? getAvatarUrl(friend.profile_photo) : null;
                    const isSelected = selectedFriendIds.includes(friend.id);

                    return (
                      <label
                        key={friend.id}
                        style={{
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'space-between',
                          padding: '6px 10px',
                          borderRadius: '8px',
                          cursor: 'pointer',
                          background: isSelected ? '#eff6ff' : 'transparent',
                          transition: 'background 0.15s ease',
                        }}
                      >
                        <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                          {friendPhoto ? (
                            <img
                              src={friendPhoto}
                              alt={friend.name}
                              style={{ width: '32px', height: '32px', borderRadius: '50%', objectFit: 'cover' }}
                            />
                          ) : (
                            <span
                              style={{
                                width: '32px',
                                height: '32px',
                                borderRadius: '50%',
                                background: '#4f7df3',
                                color: '#fff',
                                display: 'grid',
                                placeItems: 'center',
                                fontWeight: 700,
                                fontSize: '0.75rem',
                              }}
                            >
                              {getInitials(friend.name)}
                            </span>
                          )}
                          <div>
                            <strong style={{ display: 'block', fontSize: '0.825rem', color: '#0f172a' }}>{friend.name}</strong>
                            <span style={{ fontSize: '0.725rem', color: '#64748b' }}>
                              {friend.user_id ? `@${friend.user_id}` : `ID: #${friend.id}`}
                            </span>
                          </div>
                        </div>
                        <input
                          type="checkbox"
                          checked={isSelected}
                          onChange={() => toggleFriendSelection(friend.id)}
                          style={{ accentColor: '#4f7df3', cursor: 'pointer' }}
                        />
                      </label>
                    );
                  })
                ) : (
                  <div style={{ padding: '20px 0', textAlign: 'center', color: 'var(--color-text-muted, #94a3b8)' }}>
                    <Users size={22} style={{ marginBottom: '4px', opacity: 0.6 }} />
                    <p style={{ margin: 0, fontSize: '0.8rem' }}>No connections found.</p>
                  </div>
                )}
              </div>

              {/* Optional message input */}
              <div>
                <input
                  type="text"
                  placeholder="Add an optional message..."
                  maxLength={500}
                  value={friendNote}
                  onChange={(e) => setFriendNote(e.target.value)}
                  style={{
                    width: '100%',
                    padding: '8px 12px',
                    borderRadius: '10px',
                    border: '1px solid #cbd5e1',
                    fontSize: '0.825rem',
                    boxSizing: 'border-box',
                    outline: 'none',
                  }}
                />
              </div>
            </div>

            <footer
              className="post-share-modal__footer"
              style={{
                display: 'flex',
                gap: '10px',
                justifyContent: 'flex-end',
                padding: '12px 20px',
                borderTop: '1px solid var(--color-border, #e2e8f0)',
                background: '#f8fafc',
                flexShrink: 0,
              }}
            >
              <button
                type="button"
                className="member-button member-button--secondary"
                onClick={onClose}
                disabled={isSubmitting}
                style={{ padding: '8px 18px', borderRadius: '10px', minWidth: '90px' }}
              >
                Cancel
              </button>
              <button
                type="submit"
                className="member-button member-button--primary"
                disabled={isSubmitting || selectedFriendIds.length === 0}
                style={{ padding: '8px 20px', borderRadius: '10px', fontWeight: 700, minWidth: '120px' }}
              >
                {isSubmitting ? 'Sending...' : `Send (${selectedFriendIds.length})`}
              </button>
            </footer>
          </form>
        )}
      </div>
    </ModalPortal>
  );
}

export default ShareModal;
