import { useState } from 'react';
import { Eye, Edit2, EyeOff, Trash2, Share2, ThumbsUp, MessageSquare } from 'lucide-react';
import { StatusBadge } from '../../../../components/common/StatusBadge';
import { EmptyState } from '../../../../components/common/EmptyState';
import { confirmHelper } from '../../../../utils/confirmHelper';
import { postsApi } from '../../../../api';
import { useToast } from '../../../../hooks/useToast';

// Modals
import { PostViewModal } from '../../../posts/components/PostViewModal';
import { PostEditModal } from '../../../posts/components/PostEditModal';

export function MemberPostsTab({
  posts = [],
  member,
  onRefresh,
}) {
  const { showSuccess, showError } = useToast();
  const [selectedViewPost, setSelectedViewPost] = useState(null);
  const [selectedEditPost, setSelectedEditPost] = useState(null);
  const [actionLoading, setActionLoading] = useState(false);

  const handleToggleHide = (post) => {
    const isHidden = Boolean(post.is_hidden || post.status === 'hidden');
    confirmHelper.confirm({
      header: isHidden ? 'Unhide Post' : 'Hide / Block Post',
      message: isHidden
        ? `Are you sure you want to unhide post #${post.id} and restore it to feeds?`
        : `Are you sure you want to hide post #${post.id} from platform feeds?`,
      onAccept: async () => {
        setActionLoading(true);
        try {
          await postsApi.toggleHide(post.id);
          showSuccess(`Post #${post.id} visibility updated.`);
          onRefresh();
        } catch (err) {
          showError(err.message || 'Failed to update post visibility.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  const handleDeletePost = (post) => {
    confirmHelper.confirmDelete({
      header: 'Delete Post',
      message: `Are you sure you want to permanently delete post #${post.id}?`,
      onAccept: async () => {
        setActionLoading(true);
        try {
          await postsApi.deletePost(post.id);
          showSuccess(`Post #${post.id} deleted successfully.`);
          onRefresh();
        } catch (err) {
          showError(err.message || 'Failed to delete post.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  if (posts.length === 0) {
    return (
      <EmptyState
        title="No Posts Authored"
        description="This member has not created any timeline posts."
      />
    );
  }

  return (
    <div className="pt-2">
      {/* Inspection Modal */}
      <PostViewModal
        visible={Boolean(selectedViewPost)}
        onHide={() => setSelectedViewPost(null)}
        post={selectedViewPost}
        member={member}
      />

      {/* Edit Modal */}
      <PostEditModal
        visible={Boolean(selectedEditPost)}
        onHide={() => setSelectedEditPost(null)}
        post={selectedEditPost}
        onPostUpdated={onRefresh}
      />

      {/* Responsive Table */}
      <div className="overflow-x-auto border border-slate-200 rounded-xl bg-white">
        <table className="w-full text-left text-xs border-collapse">
          <thead>
            <tr className="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
              <th className="py-3 px-4">ID</th>
              <th className="py-3 px-4">Type</th>
              <th className="py-3 px-4">Content / Media</th>
              <th className="py-3 px-4 text-center">Likes</th>
              <th className="py-3 px-4 text-center">Comments</th>
              <th className="py-3 px-4">Status</th>
              <th className="py-3 px-4">Created At</th>
              <th className="py-3 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {posts.map((post) => {
              const isShared = Boolean(post.is_shared || post.original_post_id);
              const origPost = post.original_post || post.originalPost;
              const isHidden = Boolean(post.is_hidden || post.status === 'hidden');
              const mediaType = post.media_type;

              return (
                <tr key={post.id} className="hover:bg-slate-50/80 transition-colors">
                  {/* ID */}
                  <td className="py-3 px-4 font-mono font-bold text-slate-800 whitespace-nowrap">
                    #{post.id}
                    {isShared && (
                      <span className="block text-[9px] font-bold text-blue-600 bg-blue-50 px-1 rounded-sm w-fit mt-0.5">
                        Reshare
                      </span>
                    )}
                  </td>

                  {/* Type */}
                  <td className="py-3 px-4 whitespace-nowrap">
                    <span className="bg-slate-100 text-slate-700 font-semibold px-2 py-0.5 rounded-md text-[10px] uppercase">
                      {mediaType ? `${mediaType} ${isShared ? '(Shared)' : ''}` : 'Text'}
                    </span>
                  </td>

                  {/* Content Preview */}
                  <td className="py-3 px-4 max-w-xs">
                    <p className="text-slate-800 truncate font-medium">
                      {post.body || (isShared && origPost?.body ? origPost.body : (post.media_path ? '[Media Attachment]' : 'No text content'))}
                    </p>
                    {isShared && origPost && (
                      <span className="text-[10px] text-slate-400 block truncate mt-0.5">
                        <Share2 className="w-2.5 h-2.5 inline mr-1 text-blue-500" />
                        Shared from {origPost.member?.name || 'Original Author'}
                      </span>
                    )}
                  </td>

                  {/* Likes */}
                  <td className="py-3 px-4 text-center whitespace-nowrap">
                    <span className="inline-flex items-center text-[11px] font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded-full">
                      <ThumbsUp className="w-3 h-3 text-blue-600 mr-1" />
                      {post.likes_count || 0}
                    </span>
                  </td>

                  {/* Comments */}
                  <td className="py-3 px-4 text-center whitespace-nowrap">
                    <span className="inline-flex items-center text-[11px] font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded-full">
                      <MessageSquare className="w-3 h-3 text-slate-500 mr-1" />
                      {post.comments_count || 0}
                    </span>
                  </td>

                  {/* Status */}
                  <td className="py-3 px-4 whitespace-nowrap">
                    <StatusBadge
                      status={isHidden ? 'hidden' : 'published'}
                      label={isHidden ? 'Hidden' : 'Visible'}
                    />
                  </td>

                  {/* Created At */}
                  <td className="py-3 px-4 text-slate-500 whitespace-nowrap">
                    {post.created_at_human || 'Recently'}
                  </td>

                  {/* Actions */}
                  <td className="py-3 px-4 text-right whitespace-nowrap">
                    <div className="inline-flex items-center justify-end gap-1.5 sm:gap-2">
                      <button
                        type="button"
                        onClick={() => setSelectedViewPost(post)}
                        className="p-1.5 rounded-md text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                        title="View Post Details"
                      >
                        <Eye className="w-3.5 h-3.5" />
                      </button>

                      <button
                        type="button"
                        onClick={() => setSelectedEditPost(post)}
                        className="p-1.5 rounded-md text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors"
                        title="Edit Post"
                      >
                        <Edit2 className="w-3.5 h-3.5" />
                      </button>

                      <button
                        type="button"
                        onClick={() => handleToggleHide(post)}
                        disabled={actionLoading}
                        className={`p-1.5 rounded-md transition-colors ${isHidden ? 'text-emerald-600 hover:bg-emerald-50' : 'text-amber-600 hover:bg-amber-50'}`}
                        title={isHidden ? 'Unhide Post' : 'Hide Post'}
                      >
                        {isHidden ? <Eye className="w-3.5 h-3.5" /> : <EyeOff className="w-3.5 h-3.5" />}
                      </button>

                      <button
                        type="button"
                        onClick={() => handleDeletePost(post)}
                        disabled={actionLoading}
                        className="p-1.5 rounded-md text-slate-500 hover:text-red-600 hover:bg-red-50 transition-colors"
                        title="Delete Post"
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    </div>
  );
}

export default MemberPostsTab;
