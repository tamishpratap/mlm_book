import { useState, useEffect, useCallback } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { AdminDateFilter } from '../../components/admin/AdminDateFilter';
import {
  FileText,
  Search,
  Download,
  Trash2,
  Eye,
  EyeOff,
  Edit2,
  AlertCircle,
  Image as ImageIcon,
  Video,
  Heart,
  MessageSquare,
  Share2,
  ExternalLink,
  User,
  Users,
  Briefcase,
} from 'lucide-react';
import { Button } from 'primereact/button';
import { InputText } from 'primereact/inputtext';
import { Dropdown } from 'primereact/dropdown';
import { Checkbox } from 'primereact/checkbox';
import { AdminSearchInput } from '../../components/common/AdminSearchInput';
import { Paginator } from 'primereact/paginator';
import { PageHeader } from '../../components/common/PageHeader';
import { StatusBadge } from '../../components/common/StatusBadge';
import { EmptyState } from '../../components/common/EmptyState';
import { ErrorState } from '../../components/common/ErrorState';
import { LoadingSpinner } from '../../components/common/LoadingSpinner';
import { useToast } from '../../hooks/useToast';
import { confirmHelper } from '../../utils/confirmHelper';
import { postsApi, downloadBlobFromResponse } from '../../api';
import { PostViewModal } from './components/PostViewModal';
import { PostEditModal } from './components/PostEditModal';

export function PostsListPage() {
  const { showSuccess, showError } = useToast();

  const [posts, setPosts] = useState([]);
  const [exporting, setExporting] = useState(false);
  const [stats, setStats] = useState({
    totalCount: 0,
    imageCount: 0,
    videoCount: 0,
    reportedCount: 0,
  });

  const [searchParams, setSearchParams] = useSearchParams();

  // Local draft state for filters currently entered/selected by the user
  const [filterDraft, setFilterDraft] = useState({
    q: searchParams.get('q') || '',
    media_type: searchParams.get('media_type') || '',
    has_reports: searchParams.get('has_reports') || '',
    date_from: searchParams.get('date_from') || '',
    date_to: searchParams.get('date_to') || '',
  });

  // Authoritative applied filter state currently fetched and reflected in the table
  const [appliedFilters, setAppliedFilters] = useState({
    q: searchParams.get('q') || '',
    media_type: searchParams.get('media_type') || '',
    has_reports: searchParams.get('has_reports') || '',
    date_from: searchParams.get('date_from') || '',
    date_to: searchParams.get('date_to') || '',
  });

  const [pagination, setPagination] = useState({ page: 1, perPage: 15, total: 0 });
  const [selectedIds, setSelectedIds] = useState([]);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [error, setError] = useState(null);

  // Modals state
  const [selectedViewPost, setSelectedViewPost] = useState(null);
  const [selectedEditPost, setSelectedEditPost] = useState(null);

  const fetchPosts = useCallback((page = 1, currentFilters = null) => {
    setLoading(true);
    setError(null);
    const activeFilters = currentFilters || appliedFilters;
    const params = {
      page,
      q: activeFilters.q || undefined,
      media_type: activeFilters.media_type || undefined,
      has_reports: activeFilters.has_reports || undefined,
      date_from: activeFilters.date_from || undefined,
      date_to: activeFilters.date_to || undefined,
    };
    postsApi
      .getPosts(params)
      .then((res) => {
        const items = res?.posts?.data || res?.data || (Array.isArray(res) ? res : []);
        const paginator = res?.posts || {};

        setPosts(items);
        setStats({
          totalCount: res?.totalCount ?? paginator?.total ?? items.length,
          imageCount: res?.imageCount ?? 0,
          videoCount: res?.videoCount ?? 0,
          reportedCount: res?.reportedCount ?? 0,
        });
        setPagination({
          page: paginator?.current_page || page,
          perPage: paginator?.per_page || 15,
          total: paginator?.total || items.length,
        });
        setSelectedIds([]);
      })
      .catch((err) => {
        setError(err.message || 'Failed to load posts.');
      })
      .finally(() => {
        setLoading(false);
      });
  }, [appliedFilters]);

  // Initial load on mount
  useEffect(() => {
    fetchPosts(1, appliedFilters);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // Explicitly apply all selected filters together
  const handleApplyFilters = (e) => {
    if (e && e.preventDefault) e.preventDefault();
    setAppliedFilters(filterDraft);

    // Sync URL search params
    const nextParams = new URLSearchParams();
    if (filterDraft.q) nextParams.set('q', filterDraft.q);
    if (filterDraft.media_type) nextParams.set('media_type', filterDraft.media_type);
    if (filterDraft.has_reports) nextParams.set('has_reports', filterDraft.has_reports);
    if (filterDraft.date_from) nextParams.set('date_from', filterDraft.date_from);
    if (filterDraft.date_to) nextParams.set('date_to', filterDraft.date_to);
    setSearchParams(nextParams, { replace: true });

    fetchPosts(1, filterDraft);
  };

  // Reload current posts data independently with currently applied filters
  const handleReload = () => {
    fetchPosts(pagination.page, appliedFilters);
  };

  // Clear all filters and reload unfiltered data
  const handleResetFilters = () => {
    const emptyFilters = {
      q: '',
      media_type: '',
      has_reports: '',
      date_from: '',
      date_to: '',
    };
    setFilterDraft(emptyFilters);
    setAppliedFilters(emptyFilters);
    setSearchParams(new URLSearchParams(), { replace: true });
    fetchPosts(1, emptyFilters);
  };

  const hasActiveFilters = Boolean(
    appliedFilters.q ||
    appliedFilters.media_type ||
    appliedFilters.has_reports ||
    appliedFilters.date_from ||
    appliedFilters.date_to ||
    filterDraft.q ||
    filterDraft.media_type ||
    filterDraft.has_reports ||
    filterDraft.date_from ||
    filterDraft.date_to
  );

  const handleToggleHide = async (post) => {
    setActionLoading(true);
    try {
      const res = await postsApi.toggleHide(post.id);
      showSuccess(res?.message || 'Post visibility updated successfully.');
      fetchPosts(pagination.page);
    } catch (err) {
      showError(err.message || 'Failed to toggle visibility.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleDelete = (post) => {
    confirmHelper.confirm({
      header: 'Delete Post',
      message: `Are you sure you want to permanently delete Post #${post.id} and its attachments?`,
      icon: 'pi pi-trash text-red-500',
      acceptClassName: 'p-button-danger text-xs',
      onAccept: async () => {
        setActionLoading(true);
        try {
          await postsApi.deletePost(post.id);
          showSuccess(`Post #${post.id} deleted successfully.`);
          fetchPosts(pagination.page);
        } catch (err) {
          showError(err.message || 'Failed to delete post.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  const handleBulkAction = async (action) => {
    if (!selectedIds.length) return;
    confirmHelper.confirm({
      header: 'Bulk Post Action',
      message: `Are you sure you want to apply "${action}" to ${selectedIds.length} selected post(s)?`,
      icon: 'pi pi-exclamation-triangle text-amber-500',
      acceptClassName: action === 'delete' ? 'p-button-danger text-xs' : 'p-button-primary text-xs',
      onAccept: async () => {
        setActionLoading(true);
        try {
          await postsApi.bulkAction(action, selectedIds);
          showSuccess(`Bulk ${action} executed successfully.`);
          fetchPosts(pagination.page);
        } catch (err) {
          showError(err.message || `Failed to execute bulk ${action}.`);
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  const handleExport = async () => {
    if (exporting) return;
    setExporting(true);
    try {
      const exportParams = {
        q: appliedFilters.q || undefined,
        media_type: appliedFilters.media_type || undefined,
        has_reports: appliedFilters.has_reports || undefined,
        date_from: appliedFilters.date_from || undefined,
        date_to: appliedFilters.date_to || undefined,
      };
      const blob = await postsApi.exportCsv(exportParams);
      await downloadBlobFromResponse(blob, 'posts_export.csv');
      showSuccess('Posts CSV export downloaded successfully.');
    } catch (err) {
      showError(err.message || 'Failed to export CSV.');
    } finally {
      setExporting(false);
    }
  };

  const toggleSelectAll = (checked) => {
    if (checked) {
      setSelectedIds(posts.map((p) => p.id));
    } else {
      setSelectedIds([]);
    }
  };

  const toggleSelectOne = (id) => {
    setSelectedIds((prev) =>
      prev.includes(id) ? prev.filter((i) => i !== id) : [...prev, id]
    );
  };

  return (
    <div className="space-y-6">
      {/* Post View Inspection Modal */}
      <PostViewModal
        visible={Boolean(selectedViewPost)}
        onHide={() => setSelectedViewPost(null)}
        post={selectedViewPost}
        onEdit={(p) => setSelectedEditPost(p)}
        onToggleHide={handleToggleHide}
      />

      {/* Post Edit Modal */}
      <PostEditModal
        visible={Boolean(selectedEditPost)}
        onHide={() => setSelectedEditPost(null)}
        post={selectedEditPost}
        onPostUpdated={() => fetchPosts(pagination.page)}
      />

      {/* Page Header */}
      <PageHeader
        title="Posts & Moderation"
        subtitle="Monitor timeline posts, media attachments, and moderation flags across the platform."
        breadcrumbs={[{ label: 'Posts & Moderation' }]}
        actions={
          <Button
            label={exporting ? 'Exporting...' : 'Export CSV'}
            icon={exporting ? 'pi pi-spin pi-spinner mr-1.5' : <Download className="w-3.5 h-3.5 mr-1.5" />}
            onClick={handleExport}
            disabled={exporting}
            loading={exporting}
            className="p-button-outlined p-button-secondary text-xs"
          />
        }
      />

      {/* KPI Stats Cards */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500">Total Posts</span>
            <div className="p-2 rounded-lg bg-blue-50 text-blue-600">
              <FileText className="w-4 h-4" />
            </div>
          </div>
          <p className="text-2xl font-bold text-slate-800 mt-2">{stats.totalCount}</p>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500">Image Posts</span>
            <div className="p-2 rounded-lg bg-emerald-50 text-emerald-600">
              <ImageIcon className="w-4 h-4" />
            </div>
          </div>
          <p className="text-2xl font-bold text-slate-800 mt-2">{stats.imageCount}</p>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500">Video Posts</span>
            <div className="p-2 rounded-lg bg-purple-50 text-purple-600">
              <Video className="w-4 h-4" />
            </div>
          </div>
          <p className="text-2xl font-bold text-slate-800 mt-2">{stats.videoCount}</p>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500">Reported</span>
            <div className="p-2 rounded-lg bg-red-50 text-red-600">
              <AlertCircle className="w-4 h-4" />
            </div>
          </div>
          <p className="text-2xl font-bold text-slate-800 mt-2">{stats.reportedCount}</p>
        </div>
      </div>

      {/* Filter Bar */}
      <form
        onSubmit={handleApplyFilters}
        className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs"
      >
        <div className="flex flex-col xl:flex-row xl:items-center justify-between gap-3">
          {/* Search Input */}
          <div className="flex-1 min-w-[220px]">
            <AdminSearchInput
              value={filterDraft.q}
              onChange={(e) => setFilterDraft((prev) => ({ ...prev, q: e.target.value }))}
              onClear={() => setFilterDraft((prev) => ({ ...prev, q: '' }))}
              onSubmit={() => handleApplyFilters()}
              placeholder="Search post body, author, community..."
            />
          </div>

          {/* Filters & Actions Controls */}
          <div className="flex flex-wrap items-center gap-2.5">
            {/* Media Type Dropdown */}
            <Dropdown
              value={filterDraft.media_type}
              options={[
                { label: 'All Media', value: '' },
                { label: 'Images Only', value: 'image' },
                { label: 'Videos Only', value: 'video' },
                { label: 'Text Only', value: 'text' },
              ]}
              onChange={(e) => setFilterDraft((prev) => ({ ...prev, media_type: e.value }))}
              className="text-xs w-full sm:w-36"
              placeholder="All Media"
            />

            {/* Post Status / Reports Dropdown */}
            <Dropdown
              value={filterDraft.has_reports}
              options={[
                { label: 'All Posts', value: '' },
                { label: 'Reported Only', value: 'yes' },
                { label: 'No Reports', value: 'no' },
              ]}
              onChange={(e) => setFilterDraft((prev) => ({ ...prev, has_reports: e.value }))}
              className="text-xs w-full sm:w-36"
              placeholder="All Posts"
            />

            {/* Admin Date Filter */}
            <AdminDateFilter
              dateFrom={filterDraft.date_from}
              dateTo={filterDraft.date_to}
              onChange={({ date_from, date_to }) => {
                setFilterDraft((prev) => ({
                  ...prev,
                  date_from: date_from || '',
                  date_to: date_to || '',
                }));
              }}
              onReset={() => {
                setFilterDraft((prev) => ({
                  ...prev,
                  date_from: '',
                  date_to: '',
                }));
              }}
              className="w-full sm:w-auto"
            />

            {/* Action Buttons */}
            <div className="flex items-center gap-2 shrink-0">
              {/* Apply Filters Button */}
              <Button
                type="submit"
                label="Apply Filters"
                icon="pi pi-filter"
                size="small"
                loading={loading}
                className="p-button-primary text-xs shrink-0"
              />

              {/* Clear Filters Button */}
              {hasActiveFilters && (
                <Button
                  type="button"
                  label="Clear Filters"
                  icon="pi pi-times"
                  size="small"
                  onClick={handleResetFilters}
                  className="p-button-outlined p-button-secondary text-xs shrink-0"
                />
              )}

              {/* Separate Reload Button */}
              <Button
                type="button"
                icon="pi pi-refresh"
                size="small"
                onClick={handleReload}
                loading={loading}
                className="p-button-outlined p-button-secondary text-xs shrink-0"
                tooltip="Reload post data"
                tooltipOptions={{ position: 'top' }}
                aria-label="Reload post data"
              />
            </div>
          </div>
        </div>
      </form>

      {/* Bulk Actions Bar */}
      {selectedIds.length > 0 && (
        <div className="p-3 bg-blue-50 border border-blue-200 rounded-xl flex items-center justify-between flex-wrap gap-2">
          <span className="text-xs font-semibold text-blue-900">
            {selectedIds.length} post(s) selected
          </span>
          <div className="flex flex-wrap items-center gap-2">
            <Button
              label="Delete Selected"
              size="small"
              onClick={() => handleBulkAction('delete')}
              className="p-button-danger text-xs py-1"
              disabled={actionLoading}
            />
          </div>
        </div>
      )}

      {/* Table & Content */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        {loading ? (
          <div className="p-12 flex justify-center">
            <LoadingSpinner message="Loading posts..." />
          </div>
        ) : error ? (
          <div className="p-8">
            <ErrorState title="Failed to Load Posts" message={error} onRetry={handleReload} />
          </div>
        ) : posts.length === 0 ? (
          <div className="p-8">
            <EmptyState
              title="No Posts Found"
              message="No posts match your current search or filter criteria."
              icon={FileText}
            />
          </div>
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs text-slate-700">
                <thead className="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase border-b border-slate-200">
                  <tr>
                    <th className="p-3.5 w-10 text-center">
                      <Checkbox
                        checked={posts.length > 0 && selectedIds.length === posts.length}
                        onChange={(e) => toggleSelectAll(e.checked)}
                      />
                    </th>
                    <th className="p-3.5">ID</th>
                    <th className="p-3.5">Author</th>
                    <th className="p-3.5">Post Content</th>
                    <th className="p-3.5">Media</th>
                    <th className="p-3.5 text-center">Engagement</th>
                    <th className="p-3.5 text-center">Reports</th>
                    <th className="p-3.5">Status</th>
                    <th className="p-3.5 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {posts.map((post) => {
                    const isShared = Boolean(post.is_shared || post.original_post_id);
                    const origPost = post.original_post || post.originalPost;
                    const resolvedPost = (isShared && origPost) ? origPost : post;
                    const effectiveMediaType = resolvedPost.media_type;
                    const effectiveMediaUrl = resolvedPost.media_url || resolvedPost.media_path;
                    const isHidden = Boolean(
                      post.is_hidden ||
                      post.hidden_posts?.length > 0 ||
                      post.status === 'hidden'
                    );

                    return (
                      <tr key={post.id} className="hover:bg-slate-50/80 transition-colors">
                        <td className="p-3.5 text-center">
                          <Checkbox
                            checked={selectedIds.includes(post.id)}
                            onChange={() => toggleSelectOne(post.id)}
                          />
                        </td>
                        <td className="p-3.5 font-mono font-bold text-slate-900 whitespace-nowrap">
                          <Link
                            to={`/admin/posts/${post.id}`}
                            className="text-blue-600 hover:text-blue-800 hover:underline"
                            title="Inspect Post Details"
                          >
                            #{post.id}
                          </Link>
                          {isShared && (
                            <span className="block text-[9px] font-bold text-blue-600 bg-blue-50 px-1 rounded-sm w-fit mt-0.5">
                              Reshare
                            </span>
                          )}
                        </td>
                        <td className="p-3.5 whitespace-nowrap">
                          <div className="flex items-center space-x-2.5">
                            <div className="w-8 h-8 rounded-full bg-blue-100 text-blue-700 font-bold flex items-center justify-center text-xs overflow-hidden shrink-0">
                              {post.member?.avatar_url || post.member?.profile_photo ? (
                                <img
                                  src={post.member.avatar_url || post.member.profile_photo}
                                  alt={post.member.name}
                                  onError={(e) => {
                                    e.target.style.display = 'none';
                                    e.target.nextElementSibling?.classList.remove('hidden');
                                  }}
                                  className="w-full h-full object-cover"
                                />
                              ) : null}
                              <User className={`w-4 h-4 text-blue-600 ${post.member?.avatar_url || post.member?.profile_photo ? 'hidden' : ''}`} />
                            </div>
                            <div>
                              <div className="font-semibold text-slate-900">
                                {post.member ? (
                                  <Link
                                    to={`/admin/members/${post.member.id}`}
                                    className="hover:text-blue-600 transition-colors"
                                  >
                                    {post.member.name}
                                  </Link>
                                ) : (
                                  'Unknown Author'
                                )}
                              </div>
                              <div className="text-[10px] text-slate-400 font-mono">
                                {post.member?.user_id || 'ID: ' + post.member_id}
                              </div>
                              {post.community && (
                                <span className="inline-block mt-0.5 text-[9px] font-semibold bg-emerald-50 text-emerald-700 px-1.5 py-0.2 rounded-sm border border-emerald-200">
                                  {post.community.name}
                                </span>
                              )}
                              {post.businessPage && (
                                <span className="inline-block mt-0.5 text-[9px] font-semibold bg-sky-50 text-sky-700 px-1.5 py-0.2 rounded-sm border border-sky-200">
                                  {post.businessPage.name}
                                </span>
                              )}
                            </div>
                          </div>
                        </td>
                        <td className="p-3.5 max-w-xs">
                          <p className="line-clamp-2 text-slate-800 text-xs font-normal">
                            {post.body || (isShared && origPost?.body ? origPost.body : <span className="text-slate-400 italic">No text content</span>)}
                          </p>
                          {isShared && origPost && (
                            <span className="text-[10px] text-slate-400 block truncate mt-0.5 font-medium">
                              <Share2 className="w-2.5 h-2.5 inline mr-1 text-blue-500" />
                              From {origPost.member?.name || 'Original Author'}
                            </span>
                          )}
                          <span className="text-[10px] text-slate-400 block mt-0.5">
                            {new Date(post.created_at).toLocaleDateString()} at{' '}
                            {new Date(post.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                          </span>
                        </td>
                        <td className="p-3.5 whitespace-nowrap">
                          {effectiveMediaType === 'image' ? (
                            <div className="flex items-center space-x-2">
                              {effectiveMediaUrl ? (
                                <button
                                  type="button"
                                  onClick={() => setSelectedViewPost(post)}
                                  className="w-9 h-9 rounded-lg overflow-hidden bg-slate-900 border border-slate-200 hover:opacity-80 transition-opacity shrink-0 flex items-center justify-center"
                                  title="Click to view image"
                                >
                                  <img
                                    src={effectiveMediaUrl}
                                    alt="Media Thumbnail"
                                    onError={(e) => {
                                      e.target.style.display = 'none';
                                      e.target.nextElementSibling?.classList.remove('hidden');
                                    }}
                                    className="w-full h-full object-cover"
                                  />
                                  <ImageIcon className="w-4 h-4 text-slate-400 hidden" />
                                </button>
                              ) : null}
                              <div>
                                <span className="inline-flex items-center text-[10px] font-semibold bg-blue-50 text-blue-700 px-2 py-0.5 rounded-sm border border-blue-200">
                                  <ImageIcon className="w-3 h-3 mr-1" /> Image
                                </span>
                                {isShared && (
                                  <span className="block text-[9px] text-slate-400">(Shared)</span>
                                )}
                              </div>
                            </div>
                          ) : effectiveMediaType === 'video' ? (
                            <div className="flex items-center space-x-2">
                              <button
                                type="button"
                                onClick={() => setSelectedViewPost(post)}
                                className="w-9 h-9 rounded-lg bg-slate-900 border border-slate-200 flex items-center justify-center text-white hover:bg-slate-800 transition-colors shrink-0"
                                title="Click to preview video"
                              >
                                <Video className="w-4 h-4 text-purple-400" />
                              </button>
                              <div>
                                <span className="inline-flex items-center text-[10px] font-semibold bg-purple-50 text-purple-700 px-2 py-0.5 rounded-sm border border-purple-200">
                                  <Video className="w-3 h-3 mr-1" /> Video
                                </span>
                                {isShared && (
                                  <span className="block text-[9px] text-slate-400">(Shared)</span>
                                )}
                              </div>
                            </div>
                          ) : (
                            <span className="text-slate-400 text-[11px]">Text Only</span>
                          )}
                        </td>
                        <td className="p-3.5 text-center whitespace-nowrap">
                          <div className="flex items-center justify-center space-x-2.5 text-[11px] text-slate-500">
                            <span className="flex items-center" title="Likes">
                              <Heart className="w-3 h-3 text-red-500 mr-0.5" /> {post.likes_count ?? 0}
                            </span>
                            <span className="flex items-center" title="Comments">
                              <MessageSquare className="w-3 h-3 text-blue-500 mr-0.5" /> {post.comments_count ?? 0}
                            </span>
                            <span className="flex items-center" title="Shares">
                              <Share2 className="w-3 h-3 text-emerald-500 mr-0.5" /> {post.shares_count ?? 0}
                            </span>
                          </div>
                        </td>
                        <td className="p-3.5 text-center whitespace-nowrap">
                          {post.reports_count > 0 ? (
                            <span className="inline-flex items-center text-[10px] font-bold bg-red-100 text-red-800 px-2 py-0.5 rounded-full">
                              <AlertCircle className="w-2.5 h-2.5 mr-1" /> {post.reports_count}
                            </span>
                          ) : (
                            <span className="text-slate-400 text-[11px]">0</span>
                          )}
                        </td>
                        <td className="p-3.5 whitespace-nowrap">
                          <StatusBadge
                            status={isHidden ? 'blocked' : 'active'}
                            label={isHidden ? 'Hidden' : 'Published'}
                          />
                        </td>
                        <td className="p-3.5 text-right whitespace-nowrap">
                          <div className="inline-flex items-center justify-end gap-1.5 sm:gap-2">
                            <button
                              type="button"
                              onClick={() => setSelectedViewPost(post)}
                              className="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                              title="View Post Details"
                            >
                              <Eye className="w-3.5 h-3.5" />
                            </button>

                            <button
                              type="button"
                              onClick={() => setSelectedEditPost(post)}
                              className="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors"
                              title="Edit Post Content & Media"
                            >
                              <Edit2 className="w-3.5 h-3.5" />
                            </button>

                            <button
                              type="button"
                              onClick={() => handleToggleHide(post)}
                              disabled={actionLoading}
                              className={`p-1.5 rounded-lg transition-colors ${isHidden ? 'text-emerald-600 hover:bg-emerald-50' : 'text-amber-600 hover:bg-amber-50'}`}
                              title={isHidden ? 'Unhide post' : 'Hide from feed'}
                            >
                              {isHidden ? <Eye className="w-3.5 h-3.5" /> : <EyeOff className="w-3.5 h-3.5" />}
                            </button>

                            <button
                              type="button"
                              onClick={() => handleDelete(post)}
                              disabled={actionLoading}
                              className="p-1.5 rounded-lg text-slate-500 hover:text-red-600 hover:bg-red-50 transition-colors"
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

            {/* Paginator */}
            <div className="p-3 border-t border-slate-200 flex justify-between items-center text-xs text-slate-500">
              <span>Showing {posts.length} of {pagination.total} entries</span>
              <Paginator
                first={(pagination.page - 1) * pagination.perPage}
                rows={pagination.perPage}
                totalRecords={pagination.total}
                onPageChange={(e) => fetchPosts(e.page + 1)}
                className="p-paginator-sm"
              />
            </div>
          </>
        )}
      </div>
    </div>
  );
}

export default PostsListPage;
