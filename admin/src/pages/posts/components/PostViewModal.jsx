import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Dialog } from 'primereact/dialog';
import { Button } from 'primereact/button';
import {
  ThumbsUp,
  MessageSquare,
  Share2,
  AlertTriangle,
  ExternalLink,
  Edit2,
  Eye,
  EyeOff,
  User,
  Users,
  Briefcase,
  Play,
  Image as ImageIcon,
  RefreshCw,
  Video,
} from 'lucide-react';
import { postsApi } from '../../../api';

/**
 * Robust Media Viewer with dark skeleton placeholders, fade-in transitions,
 * and resilient error fallbacks to eliminate white flashes and layout jumps.
 */
function PostMediaViewer({ mediaType, mediaUrl, mediaPath, label = 'Attached Media' }) {
  const [imageStatus, setImageStatus] = useState('loading'); // 'loading' | 'loaded' | 'error'

  useEffect(() => {
    setImageStatus('loading');
  }, [mediaUrl]);

  if (!mediaUrl && !mediaPath) {
    return null;
  }

  const isVideo = mediaType === 'video';

  return (
    <div className="space-y-1.5">
      <div className="flex items-center justify-between">
        <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center">
          {isVideo ? (
            <Video className="w-3 h-3 mr-1 text-purple-500" />
          ) : (
            <ImageIcon className="w-3 h-3 mr-1 text-blue-500" />
          )}
          {label} ({isVideo ? 'Video' : 'Image'})
        </span>
        {mediaPath && (
          <span className="text-[10px] text-slate-400 font-mono truncate max-w-[200px]" title={mediaPath}>
            {mediaPath}
          </span>
        )}
      </div>

      <div className="bg-slate-950 rounded-xl overflow-hidden border border-slate-800/80 text-center relative group min-h-[220px] max-h-[420px] flex items-center justify-center">
        {isVideo ? (
          <video
            src={mediaUrl}
            controls
            preload="metadata"
            className="max-h-[400px] mx-auto rounded-lg w-full bg-black"
          >
            Your browser does not support the video tag.
          </video>
        ) : (
          <>
            {/* Dark Skeleton Shimmer (Active while image is fetching/decoding) */}
            {imageStatus === 'loading' && (
              <div className="absolute inset-0 flex flex-col items-center justify-center bg-slate-950 z-10 space-y-2 p-6">
                <div className="w-11 h-11 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center animate-pulse">
                  <ImageIcon className="w-5 h-5 text-slate-500" />
                </div>
                <div className="flex items-center space-x-1.5 text-slate-400 text-xs">
                  <RefreshCw className="w-3 h-3 animate-spin text-blue-400" />
                  <span className="font-medium">Loading image preview...</span>
                </div>
              </div>
            )}

            {/* Error Fallback Card */}
            {imageStatus === 'error' && (
              <div className="py-10 px-4 text-center text-slate-400 text-xs space-y-2 z-10">
                <div className="w-10 h-10 rounded-full bg-slate-900 border border-slate-800 flex items-center justify-center mx-auto text-amber-500">
                  <AlertTriangle className="w-5 h-5" />
                </div>
                <p className="text-slate-300 font-semibold">Media file preview unavailable</p>
                <p className="text-[10px] text-slate-500 font-mono max-w-sm mx-auto truncate">
                  {mediaPath || mediaUrl}
                </p>
                {mediaUrl && (
                  <a
                    href={mediaUrl}
                    target="_blank"
                    rel="noreferrer"
                    className="inline-flex items-center text-blue-400 hover:text-blue-300 text-xs font-semibold mt-1"
                  >
                    Open direct URL <ExternalLink className="w-3 h-3 ml-1" />
                  </a>
                )}
              </div>
            )}

            {/* Image Preview with Smooth Fade-In */}
            <a
              href={mediaUrl}
              target="_blank"
              rel="noreferrer"
              title="Click to view full image in a new tab"
              className="block w-full h-full flex items-center justify-center"
            >
              <img
                src={mediaUrl}
                alt="Post Media"
                loading="eager"
                decoding="async"
                onLoad={() => setImageStatus('loaded')}
                onError={() => setImageStatus('error')}
                className={`max-h-[400px] max-w-full object-contain mx-auto rounded-lg transition-all duration-300 ${
                  imageStatus === 'loaded'
                    ? 'opacity-100 scale-100'
                    : 'opacity-0 scale-98 pointer-events-none'
                }`}
              />
            </a>

            {/* Hover External Link Button */}
            {imageStatus === 'loaded' && (
              <a
                href={mediaUrl}
                target="_blank"
                rel="noreferrer"
                className="absolute top-2.5 right-2.5 p-1.5 rounded-lg bg-slate-900/80 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-700/60 shadow-md opacity-0 group-hover:opacity-100 transition-opacity text-xs inline-flex items-center gap-1"
                title="Open full resolution"
              >
                <ExternalLink className="w-3.5 h-3.5" />
              </a>
            )}
          </>
        )}
      </div>
    </div>
  );
}

/**
 * Structural Skeleton matching the card layout, used ONLY when post data is completely missing.
 */
function PostViewSkeleton() {
  return (
    <div className="space-y-4 pt-2 animate-pulse">
      <div className="flex items-center justify-between p-3.5 bg-slate-50 rounded-xl border border-slate-200/80">
        <div className="flex items-center space-x-3">
          <div className="w-11 h-11 rounded-full bg-slate-200 shrink-0" />
          <div className="space-y-2">
            <div className="h-3.5 w-32 bg-slate-200 rounded" />
            <div className="h-2.5 w-24 bg-slate-100 rounded" />
            <div className="h-2 w-40 bg-slate-100 rounded" />
          </div>
        </div>
        <div className="h-5 w-20 bg-slate-100 rounded-full" />
      </div>

      <div className="space-y-2">
        <div className="h-2.5 w-24 bg-slate-200 rounded" />
        <div className="p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-2">
          <div className="h-3 w-full bg-slate-200 rounded" />
          <div className="h-3 w-4/5 bg-slate-200 rounded" />
          <div className="h-3 w-2/3 bg-slate-200 rounded" />
        </div>
      </div>

      <div className="h-56 bg-slate-950 rounded-xl border border-slate-800 flex items-center justify-center">
        <div className="text-center space-y-2">
          <div className="w-10 h-10 rounded-full bg-slate-900 flex items-center justify-center mx-auto text-slate-500">
            <ImageIcon className="w-5 h-5" />
          </div>
          <div className="h-2.5 w-32 bg-slate-900 rounded mx-auto" />
        </div>
      </div>

      <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-2 border-t border-slate-100">
        {[1, 2, 3, 4].map((i) => (
          <div key={i} className="h-9 bg-slate-50 rounded-xl border border-slate-100" />
        ))}
      </div>
    </div>
  );
}

export function PostViewModal({
  visible,
  onHide,
  post,
  onEdit,
  onToggleHide,
  member: passedMember,
}) {
  const [detailedPost, setDetailedPost] = useState(null);
  const [isSyncing, setIsSyncing] = useState(false);
  const [syncError, setSyncError] = useState(null);

  // When a new post is opened or visibility changes
  useEffect(() => {
    if (!visible || !post?.id) {
      setDetailedPost(null);
      setIsSyncing(false);
      setSyncError(null);
      return;
    }

    // Always keep current post in state; fetch latest details seamlessly in background
    let isSubscribed = true;
    setIsSyncing(true);
    setSyncError(null);

    postsApi
      .getPost(post.id)
      .then((res) => {
        if (!isSubscribed) return;
        const freshData = res?.post || res?.data;
        if (freshData) {
          setDetailedPost(freshData);
        }
      })
      .catch((err) => {
        if (!isSubscribed) return;
        setSyncError(err?.message || 'Could not refresh latest details');
      })
      .finally(() => {
        if (isSubscribed) {
          setIsSyncing(false);
        }
      });

    return () => {
      isSubscribed = false;
    };
  }, [visible, post?.id]);

  if (!post) return null;

  // Progressive data resolution: use detailed data if available, fallback to initial post data
  const currentPost = detailedPost || post;
  const isShared = Boolean(currentPost.is_shared || currentPost.original_post_id);
  const origPost = currentPost.original_post || currentPost.originalPost;
  const author = currentPost.member || passedMember;
  const isHidden = Boolean(
    currentPost.is_hidden ||
    currentPost.hidden_posts?.length > 0 ||
    currentPost.status === 'hidden'
  );

  const directMediaUrl = currentPost.media_url || currentPost.media_path;
  const origMediaUrl = origPost?.media_url || origPost?.media_path;

  // Determine if post has sufficient initial content to display immediately
  const hasInitialContent = Boolean(
    author ||
    currentPost.body ||
    currentPost.created_at ||
    currentPost.media_type ||
    directMediaUrl
  );

  return (
    <Dialog
      visible={visible}
      onHide={onHide}
      header={
        <div className="flex items-center justify-between w-full pr-6">
          <div className="flex items-center space-x-2">
            <span className="font-bold text-slate-800 text-sm">
              Post Inspection #{post.id}
            </span>
            {isSyncing && (
              <span
                className="inline-flex items-center text-[10px] font-semibold text-blue-600 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded-full"
                title="Synchronizing latest interactions in the background"
              >
                <RefreshCw className="w-2.5 h-2.5 mr-1 animate-spin" /> Syncing
              </span>
            )}
            {isShared && (
              <span className="inline-flex items-center text-[10px] font-bold text-blue-700 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded-full">
                <Share2 className="w-2.5 h-2.5 mr-1" /> Reshared
              </span>
            )}
            {isHidden && (
              <span className="inline-flex items-center text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full">
                <EyeOff className="w-2.5 h-2.5 mr-1" /> Hidden from Feed
              </span>
            )}
          </div>
          <Link
            to={`/admin/posts/${post.id}`}
            className="text-xs text-blue-600 hover:text-blue-700 font-semibold inline-flex items-center"
            title="Open Full Inspection Page"
          >
            Full Details <ExternalLink className="w-3 h-3 ml-1" />
          </Link>
        </div>
      }
      className="post-view-dialog w-full max-w-2xl"
      modal
      transitionOptions={{ timeout: 150 }}
      footer={
        <div className="flex flex-wrap items-center justify-between w-full gap-2 pt-2 border-t border-slate-100">
          <div className="flex flex-wrap items-center gap-2">
            {onToggleHide && (
              <Button
                type="button"
                label={isHidden ? 'Unhide Post' : 'Hide from Feed'}
                icon={isHidden ? <Eye className="w-3.5 h-3.5 mr-1" /> : <EyeOff className="w-3.5 h-3.5 mr-1" />}
                size="small"
                onClick={() => onToggleHide(currentPost)}
                className={isHidden ? 'p-button-outlined p-button-success text-xs' : 'p-button-outlined p-button-warning text-xs'}
              />
            )}
            {onEdit && (
              <Button
                type="button"
                label="Edit Post"
                icon={<Edit2 className="w-3.5 h-3.5 mr-1" />}
                size="small"
                onClick={() => {
                  onHide();
                  onEdit(currentPost);
                }}
                className="p-button-outlined p-button-primary text-xs"
              />
            )}
          </div>
          <Button
            type="button"
            label="Close"
            size="small"
            onClick={onHide}
            className="p-button-secondary text-xs"
          />
        </div>
      }
    >
      {!hasInitialContent && isSyncing ? (
        <PostViewSkeleton />
      ) : (
        <div className="space-y-4 pt-1 text-xs">
          {/* Author Header Card */}
          <div className="flex items-center justify-between p-3.5 bg-slate-50 rounded-xl border border-slate-200/80">
            <div className="flex items-center space-x-3">
              <div className="w-11 h-11 rounded-full bg-blue-100 text-blue-700 font-bold flex items-center justify-center text-xs overflow-hidden shrink-0 border border-blue-200 relative">
                {author?.avatar_url || author?.profile_photo ? (
                  <img
                    src={author.avatar_url || author.profile_photo}
                    alt={author.name || 'Author'}
                    onError={(e) => {
                      e.currentTarget.style.display = 'none';
                      e.currentTarget.nextElementSibling?.classList.remove('hidden');
                    }}
                    className="w-full h-full object-cover"
                  />
                ) : null}
                <User
                  className={`w-5 h-5 text-blue-600 ${
                    author?.avatar_url || author?.profile_photo ? 'hidden' : ''
                  }`}
                />
              </div>
              <div>
                <h5 className="text-xs font-bold text-slate-900">
                  {author ? (
                    <Link
                      to={`/admin/members/${author.id}`}
                      className="hover:text-blue-600 transition-colors"
                    >
                      {author.name}
                    </Link>
                  ) : (
                    'Unknown Author'
                  )}
                </h5>
                <span className="text-[11px] text-slate-500 font-mono">
                  {author?.user_id || 'ID: ' + (currentPost.member_id || author?.id || 'N/A')}
                </span>
                {currentPost.created_at && (
                  <div className="text-[10px] text-slate-400 mt-0.5">
                    Published {new Date(currentPost.created_at).toLocaleDateString()} at{' '}
                    {new Date(currentPost.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                  </div>
                )}
              </div>
            </div>

            <div className="text-right space-y-1">
              {currentPost.community && (
                <span className="inline-flex items-center text-[10px] font-semibold bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full border border-emerald-200">
                  <Users className="w-2.5 h-2.5 mr-1" /> {currentPost.community.name}
                </span>
              )}
              {currentPost.businessPage && (
                <span className="inline-flex items-center text-[10px] font-semibold bg-sky-50 text-sky-700 px-2 py-0.5 rounded-full border border-sky-200">
                  <Briefcase className="w-2.5 h-2.5 mr-1" /> {currentPost.businessPage.name}
                </span>
              )}
            </div>
          </div>

          {/* Post Content / Body */}
          <div className="space-y-1">
            <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400">
              {isShared ? 'Resharer Commentary' : 'Post Body / Caption'}
            </span>
            <div className="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80 text-slate-800 leading-relaxed whitespace-pre-line font-medium">
              {currentPost.body || (
                <span className="text-slate-400 italic font-normal">
                  {isShared ? 'No additional commentary added.' : 'No text content provided.'}
                </span>
              )}
            </div>
          </div>

          {/* Reshared Original Post Banner */}
          {isShared && origPost && (
            <div className="p-3.5 bg-blue-50/50 rounded-xl border border-blue-200/70 space-y-3">
              <div className="flex items-center justify-between border-b border-blue-100 pb-2">
                <span className="text-[11px] font-bold text-blue-900 flex items-center">
                  <Share2 className="w-3.5 h-3.5 mr-1.5 text-blue-600" />
                  Original Post from {origPost.member?.name || 'Original Author'}
                </span>
                <span className="text-[10px] text-slate-400">
                  {origPost.created_at ? new Date(origPost.created_at).toLocaleDateString() : ''}
                </span>
              </div>

              {origPost.body && (
                <p className="text-slate-700 text-xs leading-relaxed whitespace-pre-line">
                  {origPost.body}
                </p>
              )}

              {/* Original Post Media */}
              {origMediaUrl && (
                <PostMediaViewer
                  mediaType={origPost.media_type}
                  mediaUrl={origMediaUrl}
                  mediaPath={origPost.media_path}
                  label="Original Post Attachment"
                />
              )}
            </div>
          )}

          {/* Direct Post Media (Rendered ONLY if post actually has media) */}
          {!isShared && directMediaUrl && (
            <PostMediaViewer
              mediaType={currentPost.media_type}
              mediaUrl={directMediaUrl}
              mediaPath={currentPost.media_path}
              label="Attached Media"
            />
          )}

          {/* Metrics & Moderation Flag Strip */}
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-2 border-t border-slate-100 text-center">
            <div className="p-2.5 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-center space-x-1.5">
              <ThumbsUp className="w-3.5 h-3.5 text-red-500" />
              <span className="font-bold text-slate-800">{currentPost.likes_count || 0} Likes</span>
            </div>
            <div className="p-2.5 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-center space-x-1.5">
              <MessageSquare className="w-3.5 h-3.5 text-blue-500" />
              <span className="font-bold text-slate-800">{currentPost.comments_count || 0} Comments</span>
            </div>
            <div className="p-2.5 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-center space-x-1.5">
              <Share2 className="w-3.5 h-3.5 text-emerald-500" />
              <span className="font-bold text-slate-800">{currentPost.shares_count || 0} Shares</span>
            </div>
            <div className="p-2.5 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-center space-x-1.5">
              <AlertTriangle className={`w-3.5 h-3.5 ${currentPost.reports_count > 0 ? 'text-red-600' : 'text-slate-400'}`} />
              <span className={`font-bold ${currentPost.reports_count > 0 ? 'text-red-700' : 'text-slate-700'}`}>
                {currentPost.reports_count || 0} Reports
              </span>
            </div>
          </div>
        </div>
      )}
    </Dialog>
  );
}

export default PostViewModal;
