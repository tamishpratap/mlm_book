import { Dialog } from 'primereact/dialog';
import { Button } from 'primereact/button';
import { ThumbsUp, MessageSquare, Share2 } from 'lucide-react';

export function PostViewModal({
  visible,
  onHide,
  post,
  member,
}) {
  if (!post) return null;

  const isShared = Boolean(post.is_shared || post.original_post_id);
  const origPost = post.original_post || post.originalPost;
  const resolvedPost = (isShared && origPost) ? origPost : post;
  const mediaType = resolvedPost.media_type;
  const mediaPath = resolvedPost.media_path;

  return (
    <Dialog
      visible={visible}
      onHide={onHide}
      header={`Post #${post.id} Inspection`}
      className="w-full max-w-lg"
      modal
      footer={
        <Button
          label="Close"
          size="small"
          onClick={onHide}
          className="p-button-outlined p-button-secondary text-xs"
        />
      }
    >
      <div className="space-y-4 pt-1">
        {/* Author Header */}
        <div className="flex items-center space-x-3 p-3 bg-slate-50 rounded-xl border border-slate-100">
          <div className="w-10 h-10 rounded-full bg-blue-100 text-blue-600 font-bold flex items-center justify-center text-xs overflow-hidden shrink-0">
            {member?.avatar_url || member?.profile_photo ? (
              <img src={member.avatar_url || member.profile_photo} alt={member.name} className="w-full h-full object-cover" />
            ) : (
              (member?.name || 'M').charAt(0).toUpperCase()
            )}
          </div>
          <div>
            <h5 className="text-xs font-bold text-slate-900">{member?.name}</h5>
            <span className="text-[11px] text-slate-400 font-mono">
              {member?.user_id} • {post.created_at_human || 'Recently'}
            </span>
            {isShared && (
              <span className="ml-2 inline-flex items-center text-[10px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded-sm">
                <Share2 className="w-2.5 h-2.5 mr-1" /> Reshare
              </span>
            )}
          </div>
        </div>

        {/* Post Text Content */}
        <div className="space-y-1">
          <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400">Content / Body</span>
          <p className="text-xs text-slate-800 bg-slate-50 p-3 rounded-xl border border-slate-200/60 leading-relaxed">
            {post.body || (isShared ? 'No additional commentary added.' : 'No text content provided.')}
          </p>
        </div>

        {/* Reshared Original Post Banner */}
        {isShared && origPost && (
          <div className="p-3 bg-slate-100 rounded-xl border border-slate-200 text-xs space-y-2">
            <span className="text-[10px] font-bold text-blue-600 flex items-center">
              <Share2 className="w-3 h-3 mr-1" /> Shared from {origPost.member?.name || 'Original Author'}
            </span>
            {origPost.body && <p className="text-slate-700">{origPost.body}</p>}
          </div>
        )}

        {/* Media Preview */}
        {mediaPath && (
          <div className="space-y-1">
            <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400">Attached Media</span>
            <div className="bg-slate-950 rounded-xl overflow-hidden p-2 text-center">
              {mediaType === 'video' ? (
                <video src={mediaPath} controls className="max-h-60 mx-auto rounded-lg" />
              ) : (
                <img src={mediaPath} alt="Post Media" className="max-h-60 mx-auto object-contain rounded-lg" />
              )}
            </div>
          </div>
        )}

        {/* Stats strip */}
        <div className="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 text-center text-xs">
          <div className="p-2 bg-slate-50 rounded-lg flex items-center justify-center space-x-1.5">
            <ThumbsUp className="w-3.5 h-3.5 text-blue-600" />
            <span className="font-bold text-slate-800">{post.likes_count || 0} Likes</span>
          </div>
          <div className="p-2 bg-slate-50 rounded-lg flex items-center justify-center space-x-1.5">
            <MessageSquare className="w-3.5 h-3.5 text-slate-600" />
            <span className="font-bold text-slate-800">{post.comments_count || 0} Comments</span>
          </div>
        </div>
      </div>
    </Dialog>
  );
}

export default PostViewModal;
