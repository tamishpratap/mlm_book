import { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import {
  FileText,
  ArrowLeft,
  Edit2,
  Trash2,
  Eye,
  EyeOff,
  ThumbsUp,
  MessageSquare,
  Share2,
  AlertTriangle,
  ExternalLink,
  Users,
  Briefcase,
  CheckCircle2,
  Clock,
  User,
  Heart,
  Video,
  Image as ImageIcon,
} from 'lucide-react';
import { Button } from 'primereact/button';
import { PageHeader } from '../../components/common/PageHeader';
import { StatusBadge } from '../../components/common/StatusBadge';
import { EmptyState } from '../../components/common/EmptyState';
import { ErrorState } from '../../components/common/ErrorState';
import { LoadingSpinner } from '../../components/common/LoadingSpinner';
import { useToast } from '../../hooks/useToast';
import { confirmHelper } from '../../utils/confirmHelper';
import { postsApi } from '../../api';
import { PostEditModal } from './components/PostEditModal';

export function PostDetailsPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { showSuccess, showError } = useToast();

  const [post, setPost] = useState(null);
  const [topReactions, setTopReactions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [error, setError] = useState(null);
  const [editModalVisible, setEditModalVisible] = useState(false);

  const fetchPostDetails = (signal) => {
    setLoading(true);
    setError(null);
    postsApi
      .getPost(id, signal)
      .then((res) => {
        const postData = res?.post || res?.data || res;
        setPost(postData);
        setTopReactions(res?.topReactions || []);
      })
      .catch((err) => {
        if (err.name === 'CanceledError') return;
        setError(err.message || 'Failed to load post details.');
      })
      .finally(() => {
        setLoading(false);
      });
  };

  useEffect(() => {
    const controller = new AbortController();
    fetchPostDetails(controller.signal);
    return () => controller.abort();
  }, [id]);

  const handleToggleHide = async () => {
    if (!post) return;
    setActionLoading(true);
    try {
      const res = await postsApi.toggleHide(post.id);
      showSuccess(res?.message || 'Post visibility updated.');
      fetchPostDetails();
    } catch (err) {
      showError(err.message || 'Failed to update visibility.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleDelete = () => {
    if (!post) return;
    confirmHelper.confirm({
      header: 'Delete Post',
      message: `Are you sure you want to permanently delete Post #${post.id}? All attachments, comments, and interactions will be removed.`,
      icon: 'pi pi-trash text-red-500',
      acceptClassName: 'p-button-danger text-xs',
      onAccept: async () => {
        setActionLoading(true);
        try {
          await postsApi.deletePost(post.id);
          showSuccess(`Post #${post.id} deleted successfully.`);
          navigate('/admin/posts');
        } catch (err) {
          showError(err.message || 'Failed to delete post.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  const handleReportStatus = async (reportId, status) => {
    try {
      await postsApi.updateReportStatus(reportId, status);
      showSuccess(`Report marked as ${status}.`);
      fetchPostDetails();
    } catch (err) {
      showError(err.message || 'Failed to update report status.');
    }
  };

  if (loading) {
    return (
      <div className="space-y-6">
        <PageHeader
          title={`Post Moderation #${id}`}
          breadcrumbs={[{ label: 'Posts & Moderation', to: '/admin/posts' }, { label: `Post #${id}` }]}
        />
        <div className="p-16 flex justify-center bg-white rounded-2xl border border-slate-200 shadow-2xs">
          <LoadingSpinner message="Loading post inspection details..." />
        </div>
      </div>
    );
  }

  if (error || !post) {
    return (
      <div className="space-y-6">
        <PageHeader
          title="Post Not Found"
          breadcrumbs={[{ label: 'Posts & Moderation', to: '/admin/posts' }, { label: 'Post' }]}
        />
        <div className="p-8 bg-white rounded-2xl border border-slate-200 shadow-2xs">
          <ErrorState
            title="Post Record Unavailable"
            message={error || 'The requested post could not be found or has been deleted.'}
            onRetry={() => fetchPostDetails()}
          />
        </div>
      </div>
    );
  }

  const isShared = Boolean(post.is_shared || post.original_post_id);
  const origPost = post.original_post || post.originalPost;
  const isHidden = Boolean(
    post.is_hidden ||
    post.hidden_posts?.length > 0 ||
    post.status === 'hidden'
  );

  const directMediaUrl = post.media_url || post.media_path;
  const origMediaUrl = origPost?.media_url || origPost?.media_path;

  return (
    <div className="space-y-6">
      {/* Edit Modal */}
      <PostEditModal
        visible={editModalVisible}
        onHide={() => setEditModalVisible(false)}
        post={post}
        onPostUpdated={fetchPostDetails}
      />

      {/* Page Header */}
      <PageHeader
        title={`Post Moderation #${post.id}`}
        subtitle="Read-only post inspection, media preview, comments tree & report queue."
        breadcrumbs={[
          { label: 'Posts & Moderation', to: '/admin/posts' },
          { label: `Post #${post.id}` },
        ]}
        actions={
          <div className="flex flex-wrap items-center gap-2.5 sm:gap-3">
            <Button
              label="Edit Post"
              icon={<Edit2 className="w-3.5 h-3.5 mr-1.5" />}
              size="small"
              onClick={() => setEditModalVisible(true)}
              className="p-button-outlined p-button-primary text-xs"
            />
            <Button
              label={isHidden ? 'Unhide Post' : 'Hide from Feed'}
              icon={isHidden ? <Eye className="w-3.5 h-3.5 mr-1.5" /> : <EyeOff className="w-3.5 h-3.5 mr-1.5" />}
              size="small"
              onClick={handleToggleHide}
              loading={actionLoading}
              className={isHidden ? 'p-button-outlined p-button-success text-xs' : 'p-button-outlined p-button-warning text-xs'}
            />
            <Button
              label="Delete Post"
              icon={<Trash2 className="w-3.5 h-3.5 mr-1.5" />}
              size="small"
              severity="danger"
              onClick={handleDelete}
              loading={actionLoading}
              className="p-button-danger text-xs"
            />
            <Link to="/admin/posts" className="inline-flex">
              <Button
                label="Back"
                icon={<ArrowLeft className="w-3.5 h-3.5 mr-1.5" />}
                size="small"
                className="p-button-outlined p-button-secondary text-xs"
              />
            </Link>
          </div>
        }
      />

      {/* 2-Column Main Layout */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left Column: Author, Post Body, Media Preview, Comments */}
        <div className="lg:col-span-2 space-y-6">
          {/* Post Header Card */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 sm:p-6 space-y-4">
            {/* Author Header */}
            <div className="flex items-center justify-between border-b border-slate-100 pb-4">
              <div className="flex items-center space-x-3.5">
                <div className="w-12 h-12 rounded-full bg-blue-100 text-blue-700 font-bold flex items-center justify-center text-sm overflow-hidden shrink-0 border border-blue-200">
                  {post.member?.avatar_url || post.member?.profile_photo ? (
                    <img
                      src={post.member.avatar_url || post.member.profile_photo}
                      alt={post.member.name}
                      className="w-full h-full object-cover"
                    />
                  ) : (
                    <User className="w-6 h-6 text-blue-600" />
                  )}
                </div>
                <div>
                  <h4 className="text-sm font-bold text-slate-900 flex items-center">
                    {post.member ? (
                      <Link
                        to={`/admin/members/${post.member.id}`}
                        className="text-slate-900 hover:text-blue-600 transition-colors"
                      >
                        {post.member.name}
                      </Link>
                    ) : (
                      'Unknown Author'
                    )}
                  </h4>
                  <span className="text-xs text-slate-400 font-mono">
                    {post.member?.user_id || 'ID: ' + post.member_id} •{' '}
                    {new Date(post.created_at).toLocaleDateString()} at{' '}
                    {new Date(post.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                  </span>
                </div>
              </div>

              <div className="flex items-center space-x-2">
                {isShared && (
                  <span className="inline-flex items-center text-xs font-bold text-blue-700 bg-blue-50 border border-blue-200 px-2.5 py-0.5 rounded-full">
                    <Share2 className="w-3 h-3 mr-1" /> Reshared
                  </span>
                )}
                {post.community && (
                  <span className="inline-flex items-center text-xs font-semibold bg-emerald-50 text-emerald-700 px-2.5 py-0.5 rounded-full border border-emerald-200">
                    <Users className="w-3 h-3 mr-1" /> {post.community.name}
                  </span>
                )}
                {post.businessPage && (
                  <span className="inline-flex items-center text-xs font-semibold bg-sky-50 text-sky-700 px-2.5 py-0.5 rounded-full border border-sky-200">
                    <Briefcase className="w-3 h-3 mr-1" /> {post.businessPage.name}
                  </span>
                )}
                <StatusBadge
                  status={isHidden ? 'blocked' : 'active'}
                  label={isHidden ? 'Hidden' : 'Published'}
                />
              </div>
            </div>

            {/* Post Content / Caption */}
            <div className="space-y-1.5">
              {isShared && (
                <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">
                  Resharer Commentary
                </span>
              )}
              <div className="p-4 bg-slate-50 rounded-xl border border-slate-200/70 text-slate-800 text-sm leading-relaxed whitespace-pre-line font-normal">
                {post.body || (
                  <span className="text-slate-400 italic">
                    {isShared ? 'No additional commentary added.' : 'No text content provided.'}
                  </span>
                )}
              </div>
            </div>

            {/* Embedded Reshared Original Post */}
            {isShared && origPost && (
              <div className="border border-blue-200 rounded-xl p-4 bg-blue-50/40 space-y-3 border-l-4 border-l-blue-600">
                <div className="flex items-center justify-between border-b border-blue-100 pb-2">
                  <div className="flex items-center space-x-2.5">
                    <div className="w-8 h-8 rounded-full bg-blue-100 text-blue-700 font-bold flex items-center justify-center text-xs overflow-hidden shrink-0">
                      {origPost.member?.avatar_url || origPost.member?.profile_photo ? (
                        <img
                          src={origPost.member.avatar_url || origPost.member.profile_photo}
                          alt={origPost.member.name}
                          className="w-full h-full object-cover"
                        />
                      ) : (
                        <User className="w-4 h-4 text-blue-600" />
                      )}
                    </div>
                    <div>
                      <strong className="text-xs text-slate-800 font-bold">
                        {origPost.member?.name || 'Original Author'}
                      </strong>
                      <span className="text-[10px] text-slate-400 block font-mono">
                        {origPost.member?.user_id} • {new Date(origPost.created_at).toLocaleDateString()}
                      </span>
                    </div>
                  </div>

                  <Link
                    to={`/admin/posts/${origPost.id}`}
                    className="text-xs text-blue-600 hover:text-blue-700 font-semibold inline-flex items-center"
                  >
                    Original Post #{origPost.id} <ExternalLink className="w-3 h-3 ml-1" />
                  </Link>
                </div>

                {origPost.body && (
                  <p className="text-xs text-slate-700 leading-relaxed whitespace-pre-line">
                    {origPost.body}
                  </p>
                )}

                {origMediaUrl && (
                  <div className="bg-slate-950 rounded-xl overflow-hidden p-2 text-center">
                    {origPost.media_type === 'video' ? (
                      <video
                        src={origMediaUrl}
                        controls
                        className="max-h-96 mx-auto rounded-lg w-full"
                      />
                    ) : (
                      <>
                        <img
                          src={origMediaUrl}
                          alt="Original Post Media"
                          onError={(e) => {
                            e.target.style.display = 'none';
                            e.target.nextSibling?.classList.remove('hidden');
                          }}
                          className="max-h-96 mx-auto object-contain rounded-lg"
                        />
                        <div className="hidden py-8 text-center text-slate-400 text-xs">
                          <ImageIcon className="w-8 h-8 text-slate-500 mx-auto mb-1 opacity-60" />
                          <span>Media attachment file unavailable on storage</span>
                        </div>
                      </>
                    )}
                  </div>
                )}
              </div>
            )}

            {/* Direct Post Media Preview */}
            {!isShared && directMediaUrl && (
              <div className="space-y-1.5">
                <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">
                  Attached {post.media_type === 'video' ? 'Video' : 'Image'} Attachment
                </span>
                <div className="bg-slate-950 rounded-xl overflow-hidden p-2 text-center">
                  {post.media_type === 'video' ? (
                    <video
                      src={directMediaUrl}
                      controls
                      className="max-h-96 mx-auto rounded-lg w-full"
                    />
                  ) : (
                    <>
                      <a href={directMediaUrl} target="_blank" rel="noreferrer">
                        <img
                          src={directMediaUrl}
                          alt="Post Media"
                          onError={(e) => {
                            e.target.style.display = 'none';
                            e.target.parentElement?.nextElementSibling?.classList.remove('hidden');
                          }}
                          className="max-h-96 mx-auto object-contain rounded-lg hover:opacity-95 transition-opacity"
                        />
                      </a>
                      <div className="hidden py-8 text-center text-slate-400 text-xs">
                        <ImageIcon className="w-8 h-8 text-slate-500 mx-auto mb-1 opacity-60" />
                        <span>Media attachment file unavailable on storage ({post.media_path})</span>
                      </div>
                    </>
                  )}
                </div>
              </div>
            )}

            {/* Metric Footer */}
            <div className="flex items-center justify-between pt-3 border-t border-slate-100 text-xs text-slate-500">
              <div className="flex items-center space-x-4">
                <span className="flex items-center font-semibold text-slate-700">
                  <Heart className="w-3.5 h-3.5 text-red-500 mr-1" /> {post.likes_count ?? 0} Likes
                </span>
                <span className="flex items-center font-semibold text-slate-700">
                  <MessageSquare className="w-3.5 h-3.5 text-blue-500 mr-1" /> {post.comments_count ?? 0} Comments
                </span>
                <span className="flex items-center font-semibold text-slate-700">
                  <Share2 className="w-3.5 h-3.5 text-emerald-500 mr-1" /> {post.shares_count ?? 0} Shares
                </span>
              </div>
              <div>
                <span className={`inline-flex items-center text-xs font-bold px-2.5 py-0.5 rounded-full ${post.reports_count > 0 ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-slate-100 text-slate-600'}`}>
                  <AlertTriangle className="w-3 h-3 mr-1" /> {post.reports_count ?? 0} Reports
                </span>
              </div>
            </div>
          </div>

          {/* Comments & Discussion Log */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 sm:p-6 space-y-4">
            <div className="flex items-center justify-between border-b border-slate-100 pb-3">
              <h3 className="text-sm font-bold text-slate-800 flex items-center">
                <MessageSquare className="w-4 h-4 mr-2 text-blue-600" />
                Comments & Discussion ({post.comments?.length ?? 0})
              </h3>
            </div>

            {!post.comments || post.comments.length === 0 ? (
              <div className="text-center py-8 text-slate-400 text-xs">
                <MessageSquare className="w-8 h-8 text-slate-300 mx-auto mb-2" />
                No comments posted on this publication yet.
              </div>
            ) : (
              <div className="space-y-3">
                {post.comments.map((comment) => (
                  <div key={comment.id} className="p-3.5 bg-slate-50 rounded-xl border border-slate-100 text-xs space-y-2">
                    <div className="flex items-center justify-between">
                      <div className="flex items-center space-x-2">
                        <div className="w-7 h-7 rounded-full bg-blue-100 text-blue-700 font-bold flex items-center justify-center text-[10px] overflow-hidden">
                          {comment.member?.avatar_url || comment.member?.profile_photo ? (
                            <img
                              src={comment.member.avatar_url || comment.member.profile_photo}
                              alt=""
                              className="w-full h-full object-cover"
                            />
                          ) : (
                            <User className="w-3.5 h-3.5 text-blue-600" />
                          )}
                        </div>
                        <div>
                          <span className="font-bold text-slate-900">{comment.member?.name || 'Member'}</span>
                          <span className="text-[10px] text-slate-400 font-mono ml-1.5">
                            {comment.created_at ? new Date(comment.created_at).toLocaleDateString() : ''}
                          </span>
                        </div>
                      </div>
                    </div>

                    <p className="text-slate-800 leading-relaxed font-medium pl-9">
                      {comment.comment}
                    </p>

                    {/* Sub-replies */}
                    {comment.replies && comment.replies.length > 0 && (
                      <div className="ml-9 mt-2 pl-3 border-l-2 border-slate-200 space-y-2">
                        {comment.replies.map((reply) => (
                          <div key={reply.id} className="p-2 bg-white rounded-lg border border-slate-200/60 text-xs">
                            <div className="flex items-center justify-between mb-1">
                              <span className="font-bold text-slate-800 text-[11px]">
                                {reply.member?.name || 'Replier'}
                              </span>
                              <span className="text-[10px] text-slate-400">
                                {reply.created_at ? new Date(reply.created_at).toLocaleDateString() : ''}
                              </span>
                            </div>
                            <p className="text-slate-700 text-[11px]">{reply.comment}</p>
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>

        {/* Right Column: Reaction Breakdown & Moderation Reports Log */}
        <div className="space-y-6">
          {/* Top Reactions Card */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 space-y-3">
            <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-2 flex items-center">
              <ThumbsUp className="w-3.5 h-3.5 mr-1.5 text-blue-600" />
              Reactions Breakdown
            </h4>

            {topReactions.length === 0 ? (
              <p className="text-slate-400 text-xs py-2">No reactions recorded on this post.</p>
            ) : (
              <ul className="space-y-2 text-xs">
                {topReactions.map((r, idx) => (
                  <li key={idx} className="flex items-center justify-between py-1.5 border-b border-slate-100 last:border-0">
                    <span className="capitalize font-semibold text-slate-700 flex items-center">
                      <Heart className="w-3 h-3 mr-1.5 text-red-500" /> {r.reaction}
                    </span>
                    <span className="font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full text-xs">
                      {r.total}
                    </span>
                  </li>
                ))}
              </ul>
            )}
          </div>

          {/* Reports Moderation Queue Log */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 space-y-3">
            <div className="flex items-center justify-between border-b border-slate-100 pb-2">
              <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center">
                <AlertTriangle className="w-3.5 h-3.5 mr-1.5 text-red-600" />
                Moderation Reports ({post.reports?.length ?? 0})
              </h4>
            </div>

            {!post.reports || post.reports.length === 0 ? (
              <div className="text-center py-6 text-slate-400 text-xs">
                <CheckCircle2 className="w-6 h-6 text-emerald-500 mx-auto mb-1.5" />
                No moderation reports filed against this post.
              </div>
            ) : (
              <div className="space-y-3 text-xs">
                {post.reports.map((report) => (
                  <div key={report.id} className="p-3 bg-red-50/40 rounded-xl border border-red-200 space-y-2">
                    <div className="flex items-center justify-between">
                      <span className="font-bold text-red-800 uppercase text-[10px] bg-red-100 px-2 py-0.5 rounded">
                        {report.reason}
                      </span>
                      <span className="text-[10px] text-slate-400 font-mono">
                        {report.created_at ? new Date(report.created_at).toLocaleDateString() : ''}
                      </span>
                    </div>

                    <p className="text-slate-800 text-xs leading-relaxed">
                      {report.description || 'No detailed explanation entered by reporter.'}
                    </p>

                    <div className="flex items-center justify-between pt-2 border-t border-red-100">
                      <span className="text-[11px] text-slate-500">
                        Reporter: <strong>{report.member?.name || 'Member'}</strong>
                      </span>
                      {report.status === 'reviewed' ? (
                        <span className="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                          Reviewed
                        </span>
                      ) : (
                        <Button
                          type="button"
                          label="Mark Reviewed"
                          size="small"
                          outlined
                          severity="success"
                          onClick={() => handleReportStatus(report.id, 'reviewed')}
                          className="text-[10px] py-0.5 px-2"
                        />
                      )}
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

export default PostDetailsPage;
