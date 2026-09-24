import { useState } from 'react';
import { Dialog } from 'primereact/dialog';
import { Clock, Eye, Heart, Smile, AlertCircle, Loader2 } from 'lucide-react';
import { getStoryMediaUrl, isVideoStory } from '../../../utils/mediaHelper';

export function StoryViewerModal({
  story,
  visible,
  onHide,
}) {
  const mediaUrl = story ? getStoryMediaUrl(story) : null;
  const isVideo = story ? isVideoStory(story, mediaUrl) : false;

  const [prevStoryId, setPrevStoryId] = useState(story?.id);
  const [mediaLoading, setMediaLoading] = useState(Boolean(mediaUrl));
  const [mediaError, setMediaError] = useState(false);

  if (story?.id !== prevStoryId) {
    setPrevStoryId(story?.id);
    setMediaLoading(Boolean(mediaUrl));
    setMediaError(false);
  }

  if (!story) return null;

  const isExpired = Boolean(
    story.is_expired ??
    (story.expires_at ? new Date(story.expires_at) <= new Date() : false)
  );

  const hasCaption = Boolean(story.caption && story.caption.trim().length > 0);

  return (
    <Dialog
      visible={visible}
      onHide={onHide}
      header={
        <div className="flex items-center space-x-2">
          <Clock className="w-4 h-4 text-blue-600" />
          <span className="text-sm font-bold text-slate-800">Story Preview #{story.id}</span>
        </div>
      }
      className="w-full max-w-md mx-4"
      style={{ width: '100%', maxWidth: '28rem' }}
      breakpoints={{ '640px': '95vw' }}
      contentClassName="p-0 overflow-hidden rounded-b-xl"
      closable
    >
      <div className="bg-slate-900 text-white p-4 w-full max-w-full min-w-0 flex flex-col overflow-hidden">
        {/* Author Header */}
        <div className="flex items-center justify-between mb-3">
          <div className="flex items-center space-x-2.5">
            <div className="w-9 h-9 rounded-full bg-blue-600 flex items-center justify-center font-bold text-xs overflow-hidden">
              {story.member?.avatar_url || story.author?.avatar_url ? (
                <img
                  src={story.member?.avatar_url || story.author?.avatar_url}
                  alt={story.member?.name || story.author?.name}
                  className="w-full h-full object-cover"
                />
              ) : (
                (story.member?.name || story.author?.name || 'M').charAt(0).toUpperCase()
              )}
            </div>
            <div>
              <p className="text-xs font-bold text-white leading-tight">
                {story.member?.name || story.author?.name || 'Member'}
              </p>
              <p className="text-[10px] text-slate-400">
                {story.member?.user_id || story.author?.user_id || `ID: ${story.member_id}`}
              </p>
            </div>
          </div>
          <span className={`text-[10px] font-semibold px-2 py-0.5 rounded-full ${isExpired ? 'bg-slate-700 text-slate-300' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'}`}>
            {isExpired ? 'Expired' : 'Live'}
          </span>
        </div>

        {/* Media Container */}
        <div className="rounded-lg overflow-hidden bg-black flex items-center justify-center min-h-[260px] max-h-[440px] relative w-full">
          {!mediaUrl ? (
            <div className="p-8 text-center text-slate-400">
              <Clock className="w-8 h-8 mx-auto mb-2 opacity-50" />
              <p className="text-xs">No story media available</p>
            </div>
          ) : mediaError ? (
            <div className="p-8 text-center text-rose-400">
              <AlertCircle className="w-8 h-8 mx-auto mb-2 opacity-80" />
              <p className="text-xs font-semibold">Unable to load story media.</p>
              <p className="text-[10px] text-slate-400 mt-1">Resource could not be loaded.</p>
            </div>
          ) : (
            <>
              {mediaLoading && (
                <div className="absolute inset-0 flex flex-col items-center justify-center bg-black/70 text-slate-300 z-10">
                  <Loader2 className="w-6 h-6 animate-spin text-blue-500 mb-2" />
                  <span className="text-xs">Loading story media...</span>
                </div>
              )}

              {isVideo ? (
                <video
                  key={mediaUrl}
                  controls
                  playsInline
                  className="max-h-[440px] w-full object-contain"
                  onLoadedData={() => setMediaLoading(false)}
                  onError={() => {
                    setMediaLoading(false);
                    setMediaError(true);
                  }}
                >
                  <source src={mediaUrl} type="video/mp4" />
                  Your browser does not support video playback.
                </video>
              ) : (
                <img
                  key={mediaUrl}
                  src={mediaUrl}
                  alt="Story Content"
                  className={`max-h-[440px] w-auto object-contain transition-opacity duration-200 ${
                    mediaLoading ? 'opacity-0' : 'opacity-100'
                  }`}
                  onLoad={() => setMediaLoading(false)}
                  onError={() => {
                    setMediaLoading(false);
                    setMediaError(true);
                  }}
                />
              )}
            </>
          )}
        </div>

        {/* Story Caption Card */}
        <div className="mt-3 bg-slate-800/90 border border-slate-700/60 rounded-lg p-3 w-full max-w-full min-w-0 overflow-hidden flex flex-col shadow-inner">
          <div className="flex items-center justify-between pb-1.5 mb-1.5 border-b border-slate-700/60">
            <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400">
              Story Caption
            </span>
          </div>
          <div
            tabIndex={hasCaption ? 0 : undefined}
            aria-label="Story Caption Content"
            className="max-h-36 sm:max-h-48 overflow-y-auto overflow-x-hidden text-xs text-slate-200 whitespace-pre-wrap leading-relaxed break-words [overflow-wrap:anywhere] [word-break:break-word] pr-1 focus:outline-hidden dark-scrollbar"
          >
            {hasCaption ? (
              story.caption
            ) : (
              <span className="text-slate-400 italic">No caption provided</span>
            )}
          </div>
        </div>

        {/* Impressions & Timestamps */}
        <div className="mt-3 pt-3 border-t border-slate-800 flex items-center justify-between text-[11px] text-slate-400">
          <div className="flex items-center space-x-3">
            <span className="flex items-center" title="Views">
              <Eye className="w-3.5 h-3.5 text-blue-400 mr-1" /> {story.views_count ?? 0}
            </span>
            <span className="flex items-center" title="Likes">
              <Heart className="w-3.5 h-3.5 text-red-400 mr-1" /> {story.likes_count ?? 0}
            </span>
            <span className="flex items-center" title="Reactions">
              <Smile className="w-3.5 h-3.5 text-amber-400 mr-1" /> {story.reactions_count ?? 0}
            </span>
          </div>
          <div>
            Created: {story.created_at_human || (story.created_at ? new Date(story.created_at).toLocaleDateString() : 'Recently')}
          </div>
        </div>
      </div>
    </Dialog>
  );
}

export default StoryViewerModal;
