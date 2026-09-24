import { useState } from 'react';
import { Dialog } from 'primereact/dialog';
import { Button } from 'primereact/button';
import { Clock, ExternalLink, AlertCircle, Loader2, Image as ImageIcon, Video as VideoIcon } from 'lucide-react';
import { getStoryMediaUrl, isVideoStory } from '../../../../utils/mediaHelper';

export function StoryViewModal({
  visible,
  onHide,
  story,
}) {
  const mediaUrl = story ? getStoryMediaUrl(story) : null;
  const isVideo = story ? isVideoStory(story, mediaUrl) : false;

  const [prevStoryId, setPrevStoryId] = useState(story?.id);
  const [mediaLoading, setMediaLoading] = useState(Boolean(mediaUrl));
  const [mediaError, setMediaError] = useState(false);

  // Reset loading and error state whenever the displayed story changes
  if (story?.id !== prevStoryId) {
    setPrevStoryId(story?.id);
    setMediaLoading(Boolean(mediaUrl));
    setMediaError(false);
  }

  if (!story) return null;

  const formattedPostedOn = story.created_at_human ||
    (story.created_at ? new Date(story.created_at).toLocaleString() : 'Recently');

  const formattedExpiresAt = story.expires_at
    ? new Date(story.expires_at).toLocaleString()
    : 'In 24 hours';

  return (
    <Dialog
      visible={visible}
      onHide={onHide}
      header={`Story #${story.id} Inspection`}
      className="w-full max-w-md"
      modal
      footer={
        <div className="flex items-center justify-between w-full">
          {mediaUrl && !mediaError ? (
            <a
              href={mediaUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="text-xs text-blue-600 hover:text-blue-700 font-medium inline-flex items-center gap-1 hover:underline"
            >
              <span>Open raw media</span>
              <ExternalLink className="w-3 h-3" />
            </a>
          ) : <span />}
          <Button
            label="Close"
            size="small"
            onClick={onHide}
            className="p-button-outlined p-button-secondary text-xs"
          />
        </div>
      }
    >
      <div className="space-y-4 pt-1">
        {/* Media Type & ID Banner */}
        <div className="flex items-center justify-between text-xs px-1">
          <span className="text-slate-500 font-medium">Media Type:</span>
          <span className={`inline-flex items-center text-[10px] font-bold px-2 py-0.5 rounded-md uppercase tracking-wider ${
            isVideo
              ? 'bg-purple-50 text-purple-700 border border-purple-200'
              : 'bg-blue-50 text-blue-700 border border-blue-200'
          }`}>
            {isVideo ? (
              <><VideoIcon className="w-3 h-3 mr-1" /> VIDEO</>
            ) : (
              <><ImageIcon className="w-3 h-3 mr-1" /> IMAGE</>
            )}
          </span>
        </div>

        {/* Media Preview Container */}
        <div className="bg-slate-950 rounded-xl overflow-hidden p-2 text-center relative min-h-[220px] max-h-[420px] flex items-center justify-center">
          {!mediaUrl ? (
            <div className="py-12 px-4 text-center text-slate-400">
              <Clock className="w-8 h-8 mx-auto mb-2 opacity-40 text-slate-500" />
              <p className="text-xs font-semibold text-slate-300">No story media available.</p>
              <p className="text-[11px] text-slate-500 mt-1">This story does not have an attached media file.</p>
            </div>
          ) : mediaError ? (
            <div className="py-10 px-4 text-center text-rose-400">
              <AlertCircle className="w-8 h-8 mx-auto mb-2 opacity-80" />
              <p className="text-xs font-bold text-rose-300">Unable to load story media.</p>
              <p className="text-[11px] text-slate-400 mt-1">The media asset could not be loaded from the server.</p>
              <a
                href={mediaUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="mt-3 inline-flex items-center text-[11px] text-blue-400 hover:text-blue-300 underline"
              >
                <span>Retry opening media directly</span>
                <ExternalLink className="w-2.5 h-2.5 ml-1" />
              </a>
            </div>
          ) : (
            <>
              {/* Clean loading state */}
              {mediaLoading && (
                <div className="absolute inset-0 flex flex-col items-center justify-center bg-slate-950/80 text-slate-300 z-10">
                  <Loader2 className="w-6 h-6 animate-spin text-blue-500 mb-2" />
                  <span className="text-xs font-medium">Loading story media...</span>
                </div>
              )}

              {/* Media Renderer */}
              {isVideo ? (
                <video
                  key={mediaUrl}
                  src={mediaUrl}
                  controls
                  playsInline
                  className="max-h-72 sm:max-h-80 w-auto mx-auto rounded-lg object-contain"
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
                  alt={`Story #${story.id} Media`}
                  className={`max-h-72 sm:max-h-80 w-auto mx-auto object-contain rounded-lg transition-opacity duration-200 ${
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

        {/* Caption */}
        <div className="w-full max-w-full min-w-0 overflow-hidden">
          <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Caption:</span>
          <div
            tabIndex={story.caption?.trim() ? 0 : undefined}
            className="text-xs text-slate-800 bg-slate-50 p-2.5 rounded-lg border border-slate-200/60 whitespace-pre-wrap leading-relaxed break-words [overflow-wrap:anywhere] [word-break:break-word] max-h-36 sm:max-h-48 overflow-y-auto overflow-x-hidden focus:outline-hidden"
          >
            {story.caption?.trim() ? (
              story.caption
            ) : (
              <span className="text-slate-400 italic">No caption provided</span>
            )}
          </div>
        </div>

        {/* Expiration and Timestamp */}
        <div className="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 text-center text-xs">
          <div className="p-2.5 bg-slate-50 rounded-lg">
            <span className="text-slate-400 block text-[10px] uppercase font-semibold">Posted On</span>
            <strong className="text-slate-800 text-xs mt-0.5 block">{formattedPostedOn}</strong>
          </div>
          <div className="p-2.5 bg-slate-50 rounded-lg">
            <span className="text-slate-400 block text-[10px] uppercase font-semibold flex items-center justify-center">
              <Clock className="w-2.5 h-2.5 mr-1" /> Expires At
            </span>
            <strong className="text-slate-800 text-xs mt-0.5 block">{formattedExpiresAt}</strong>
          </div>
        </div>
      </div>
    </Dialog>
  );
}

export default StoryViewModal;
