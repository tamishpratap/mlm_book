import { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import {
  Users,
  Lock,
  Trash2,
  CheckCircle,
  XCircle,
  ArrowLeft,
  Shield,
  Clock,
  FileText,
  Check,
  X,
  Eye,
} from 'lucide-react';
import { Button } from 'primereact/button';
import { TabView, TabPanel } from 'primereact/tabview';
import { PageHeader } from '../../components/common/PageHeader';
import { StatusBadge } from '../../components/common/StatusBadge';
import { ErrorState } from '../../components/common/ErrorState';
import { LoadingSpinner } from '../../components/common/LoadingSpinner';
import { EmptyState } from '../../components/common/EmptyState';
import { useToast } from '../../hooks/useToast';
import { confirmHelper } from '../../utils/confirmHelper';
import { communitiesApi } from '../../api';

export function CommunityDetailsPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { showSuccess, showError } = useToast();

  const [community, setCommunity] = useState(null);
  const [moderators, setModerators] = useState([]);
  const [posts, setPosts] = useState([]);
  const [postsCount, setPostsCount] = useState(0);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [error, setError] = useState(null);
  const [activeTab, setActiveTab] = useState(0);

  const fetchCommunityDetails = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await communitiesApi.getCommunity(id);
      const data = res?.community || res?.data || res;
      setCommunity(data);
      setModerators(res?.moderators || data?.moderators || []);
      setPosts(res?.posts || data?.posts || []);
      setPostsCount(res?.postsCount ?? (res?.posts ? res.posts.length : 0));
    } catch (err) {
      setError(err.message || 'Failed to load community inspection details.');
    } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => {
    let isMounted = true;
    communitiesApi
      .getCommunity(id)
      .then((res) => {
        if (!isMounted) return;
        const data = res?.community || res?.data || res;
        setCommunity(data);
        setModerators(res?.moderators || data?.moderators || []);
        setPosts(res?.posts || data?.posts || []);
        setPostsCount(res?.postsCount ?? (res?.posts ? res.posts.length : 0));
      })
      .catch((err) => {
        if (!isMounted) return;
        setError(err.message || 'The requested community could not be found.');
      })
      .finally(() => {
        if (isMounted) setLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [id]);

  const handleToggleStatus = async () => {
    if (!community || actionLoading) return;
    const isCurrentlyActive = community.status === 'active';
    const newStatus = isCurrentlyActive ? 'suspended' : 'active';

    setActionLoading(true);
    try {
      const res = await communitiesApi.updateStatus(community.id, newStatus);
      showSuccess(res?.message || `Community marked as ${newStatus}.`);
      setCommunity((prev) => (prev ? { ...prev, status: newStatus } : prev));
    } catch (err) {
      showError(err.message || 'Failed to update community status.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleDelete = () => {
    if (!community || actionLoading) return;
    confirmHelper.confirmDelete({
      header: 'Delete Community',
      message: `Are you sure you want to permanently delete community "${community.name}"? All member associations, discussions, and content will be removed.`,
      onAccept: async () => {
        setActionLoading(true);
        try {
          await communitiesApi.deleteCommunity(community.id);
          showSuccess(`Community "${community.name}" deleted.`);
          navigate('/admin/communities');
        } catch (err) {
          showError(err.message || 'Failed to delete community.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  const handleJoinRequestAction = async (membershipId, action) => {
    if (!community || actionLoading) return;
    setActionLoading(true);
    try {
      await communitiesApi.handleJoinRequest(community.id, membershipId, action);
      showSuccess(`Membership request ${action === 'approve' ? 'approved' : 'rejected'}.`);
      setCommunity((prev) => {
        if (!prev) return prev;
        const updatedPending = (prev.pendingMembers || []).filter((m) => m.id !== membershipId);
        return {
          ...prev,
          pendingMembers: updatedPending,
          pending_members_count: Math.max(0, (prev.pending_members_count || 1) - 1),
          accepted_members_count: action === 'approve' ? (prev.accepted_members_count || 0) + 1 : prev.accepted_members_count,
        };
      });
    } catch (err) {
      showError(err.message || `Failed to ${action} membership request.`);
    } finally {
      setActionLoading(false);
    }
  };

  const handleResolveReport = async (reportId) => {
    if (!community || actionLoading) return;
    setActionLoading(true);
    try {
      await communitiesApi.resolveReport(reportId, 'resolved', 'Resolved by Admin inspection');
      showSuccess('Report resolved successfully.');
      setCommunity((prev) => {
        if (!prev) return prev;
        const updatedReports = (prev.reports || []).map((r) =>
          r.id === reportId ? { ...r, status: 'resolved' } : r
        );
        return {
          ...prev,
          reports: updatedReports,
        };
      });
    } catch (err) {
      showError(err.message || 'Failed to resolve report.');
    } finally {
      setActionLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="py-16">
        <LoadingSpinner message="Loading community details..." />
      </div>
    );
  }

  if (error || !community) {
    return (
      <div className="space-y-6">
        <PageHeader
          title="Community Inspection"
          breadcrumbs={[{ label: 'Communities', to: '/admin/communities' }, { label: 'Inspection' }]}
        />
        <ErrorState
          title="Community Record Not Found"
          message={error || 'The requested community could not be located in the system.'}
          onRetry={fetchCommunityDetails}
        />
        <div className="flex justify-center pt-2">
          <Link to="/admin/communities">
            <Button
              label="Return to Communities Directory"
              icon="pi pi-arrow-left"
              size="small"
              className="p-button-outlined p-button-secondary text-xs"
            />
          </Link>
        </div>
      </div>
    );
  }

  const logoUrl = community.avatar_url || community.logo || community.cover_url || community.cover_photo;
  const coverUrl = community.cover_url || community.cover_photo;
  const acceptedMembers = community.acceptedMembers || community.members || [];
  const pendingMembers = community.pendingMembers || [];
  const reportsList = community.reports || [];
  const totalMembers = community.accepted_members_count ?? acceptedMembers.length;
  const pendingCount = community.pending_members_count ?? pendingMembers.length;
  const reportsCount = community.reports_count ?? reportsList.length;

  return (
    <div className="space-y-6">
      {/* Top Page Header */}
      <PageHeader
        title={`Community: ${community.name}`}
        subtitle="Review community information, member rosters, join requests, posts, and manage status."
        breadcrumbs={[
          { label: 'Communities', to: '/admin/communities' },
          { label: community.name },
        ]}
        actions={
          <div className="flex flex-wrap items-center gap-2">
            <Button
              label={community.status === 'active' ? 'Suspend Community' : 'Restore Community'}
              icon={community.status === 'active' ? <XCircle className="w-3.5 h-3.5 mr-1.5 text-amber-500" /> : <CheckCircle className="w-3.5 h-3.5 mr-1.5 text-emerald-600" />}
              size="small"
              onClick={handleToggleStatus}
              loading={actionLoading}
              className={community.status === 'active' ? 'p-button-outlined p-button-warning text-xs' : 'p-button-success text-xs'}
            />

            <Button
              label="Delete"
              icon={<Trash2 className="w-3.5 h-3.5 mr-1.5 text-red-500" />}
              size="small"
              onClick={handleDelete}
              loading={actionLoading}
              className="p-button-outlined p-button-danger text-xs"
            />

            <Link to="/admin/communities" className="inline-flex">
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

      {/* Community Banner & Identity Header */}
      <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        {/* Cover Graphic Area */}
        <div
          className="h-36 sm:h-44 w-full relative bg-cover bg-center"
          style={{
            background: coverUrl
              ? `url("${coverUrl}") center/cover no-repeat`
              : 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 50%, #0f172a 100%)',
          }}
        >
          <div className="absolute inset-0 bg-slate-900/30" />
        </div>

        {/* Identity & Metrics Row */}
        <div className="px-6 pb-6 pt-0 relative">
          <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-4 -mt-12 sm:-mt-14 mb-6">
            <div className="flex flex-col sm:flex-row items-center sm:items-end gap-4 text-center sm:text-left">
              {/* Logo / Avatar */}
              <div className="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl bg-white p-1 shadow-md border-2 border-white overflow-hidden shrink-0">
                {logoUrl ? (
                  <img
                    src={logoUrl}
                    alt={community.name}
                    className="w-full h-full object-cover rounded-xl"
                    onError={(e) => {
                      e.target.style.display = 'none';
                    }}
                  />
                ) : (
                  <div className="w-full h-full bg-blue-50 text-blue-600 flex items-center justify-center font-extrabold text-2xl rounded-xl">
                    {(community.name || 'C').charAt(0).toUpperCase()}
                  </div>
                )}
              </div>

              {/* Title & Badges */}
              <div className="space-y-1.5">
                <div className="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                  <h2 className="text-xl sm:text-2xl font-bold text-slate-900">{community.name}</h2>
                  <StatusBadge status={community.status || 'active'} />
                  {community.visibility === 'private' ? (
                    <span className="inline-flex items-center text-[10px] font-semibold bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full border border-amber-200">
                      <Lock className="w-2.5 h-2.5 mr-1" /> Private
                    </span>
                  ) : (
                    <span className="inline-flex items-center text-[10px] font-semibold bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full border border-blue-200">
                      <Eye className="w-2.5 h-2.5 mr-1" /> Public
                    </span>
                  )}
                </div>

                <div className="flex flex-wrap items-center justify-center sm:justify-start gap-3 text-xs text-slate-500">
                  <span className="font-mono text-slate-600 bg-slate-100 px-2 py-0.5 rounded">
                    c/{community.slug || community.id}
                  </span>
                  <span>Category: <strong className="text-slate-700">{community.category || 'General'}</strong></span>
                  <span>•</span>
                  <span>Owner: <strong className="text-slate-700">{community.owner?.name || 'Admin'}</strong></span>
                </div>
              </div>
            </div>
          </div>

          {/* Statistics Bar */}
          <div className="grid grid-cols-2 sm:grid-cols-5 gap-3 pt-4 border-t border-slate-100 text-center">
            <div className="p-3 bg-slate-50 rounded-xl">
              <span className="text-xs text-slate-500 font-medium block">Total Members</span>
              <span className="text-lg font-extrabold text-blue-600">{totalMembers}</span>
            </div>
            <div className="p-3 bg-slate-50 rounded-xl">
              <span className="text-xs text-slate-500 font-medium block">Pending Requests</span>
              <span className="text-lg font-extrabold text-amber-600">{pendingCount}</span>
            </div>
            <div className="p-3 bg-slate-50 rounded-xl">
              <span className="text-xs text-slate-500 font-medium block">Community Posts</span>
              <span className="text-lg font-extrabold text-indigo-600">{postsCount}</span>
            </div>
            <div className="p-3 bg-slate-50 rounded-xl">
              <span className="text-xs text-slate-500 font-medium block">Moderators</span>
              <span className="text-lg font-extrabold text-purple-600">{moderators.length}</span>
            </div>
            <div className="p-3 bg-slate-50 rounded-xl col-span-2 sm:col-span-1">
              <span className="text-xs text-slate-500 font-medium block">Reports Flagged</span>
              <span className="text-lg font-extrabold text-red-600">{reportsCount}</span>
            </div>
          </div>
        </div>
      </div>

      {/* Tabbed Inspection Sections */}
      <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 sm:p-6">
        <TabView activeIndex={activeTab} onTabChange={(e) => setActiveTab(e.index)}>
          {/* Tab 1: Overview & Info */}
          <TabPanel header="Overview & Info">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
              <div className="space-y-4">
                <h4 className="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2">Community Metadata</h4>
                <div className="grid grid-cols-3 gap-2 text-xs py-1">
                  <span className="text-slate-400 font-medium">Community Name:</span>
                  <span className="col-span-2 text-slate-800 font-semibold">{community.name}</span>
                </div>
                <div className="grid grid-cols-3 gap-2 text-xs py-1">
                  <span className="text-slate-400 font-medium">Slug / Handle:</span>
                  <span className="col-span-2 font-mono text-blue-600 font-medium">c/{community.slug || community.id}</span>
                </div>
                <div className="grid grid-cols-3 gap-2 text-xs py-1">
                  <span className="text-slate-400 font-medium">Category:</span>
                  <span className="col-span-2 text-slate-800">{community.category || 'General'}</span>
                </div>
                <div className="grid grid-cols-3 gap-2 text-xs py-1">
                  <span className="text-slate-400 font-medium">Visibility:</span>
                  <span className="col-span-2 text-slate-800 capitalize">{community.visibility || 'Public'}</span>
                </div>
                <div className="grid grid-cols-3 gap-2 text-xs py-1">
                  <span className="text-slate-400 font-medium">Status:</span>
                  <span className="col-span-2"><StatusBadge status={community.status || 'active'} /></span>
                </div>
                <div className="grid grid-cols-3 gap-2 text-xs py-1">
                  <span className="text-slate-400 font-medium">Join Approval:</span>
                  <span className="col-span-2 text-slate-800 capitalize">{community.join_approval_mode || 'Auto'}</span>
                </div>
                <div className="grid grid-cols-3 gap-2 text-xs py-1">
                  <span className="text-slate-400 font-medium">Posting Access:</span>
                  <span className="col-span-2 text-slate-800 capitalize">
                    {(community.posting_permissions || 'everyone').replace(/_/g, ' ')}
                  </span>
                </div>
                <div className="grid grid-cols-3 gap-2 text-xs py-1">
                  <span className="text-slate-400 font-medium">Invite Code:</span>
                  <span className="col-span-2 font-mono text-slate-600">{community.invite_code || 'None'}</span>
                </div>
              </div>

              <div className="space-y-4">
                <h4 className="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2">Description & Rules</h4>
                <div>
                  <span className="text-xs text-slate-400 font-medium block mb-1">Description</span>
                  <div className="bg-slate-50 border border-slate-100 rounded-xl p-3 text-xs text-slate-700 whitespace-pre-wrap min-h-[70px]">
                    {community.description || 'No description provided.'}
                  </div>
                </div>
                <div>
                  <span className="text-xs text-slate-400 font-medium block mb-1">Community Rules</span>
                  <div className="bg-slate-50 border border-slate-100 rounded-xl p-3 text-xs text-slate-700 whitespace-pre-wrap min-h-[70px]">
                    {community.rules || 'No explicit rules specified for this community.'}
                  </div>
                </div>

                <div className="pt-2 border-t border-slate-100">
                  <span className="text-xs text-slate-400 font-medium block mb-1">Created By</span>
                  <div className="flex items-center gap-3 bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                    <div className="w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs">
                      {(community.owner?.name || 'A').charAt(0).toUpperCase()}
                    </div>
                    <div>
                      <p className="text-xs font-semibold text-slate-800">{community.owner?.name || 'System / Admin'}</p>
                      <p className="text-[11px] text-slate-400">{community.owner?.email || (community.owner?.user_id ? `ID: ${community.owner.user_id}` : 'Platform Root')}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </TabPanel>

          {/* Tab 2: Members & Moderators */}
          <TabPanel header={`Members (${totalMembers})`}>
            <div className="pt-4 space-y-4">
              {acceptedMembers.length === 0 ? (
                <EmptyState
                  icon={Users}
                  title="No Accepted Members"
                  description="This community currently does not have any active accepted members."
                />
              ) : (
                <div className="overflow-x-auto">
                  <table className="w-full text-left text-xs">
                    <thead>
                      <tr className="border-b border-slate-200 text-slate-500">
                        <th className="py-2.5 px-3">Member</th>
                        <th className="py-2.5 px-3">Role</th>
                        <th className="py-2.5 px-3">Status</th>
                        <th className="py-2.5 px-3">Joined Date</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {acceptedMembers.map((cm, idx) => {
                        const mUser = cm.member || cm;
                        return (
                          <tr key={cm.id || idx} className="hover:bg-slate-50">
                            <td className="py-2.5 px-3">
                              <div className="flex items-center gap-2">
                                <div className="w-7 h-7 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center font-semibold text-xs border border-slate-200">
                                  {(mUser.name || 'M').charAt(0).toUpperCase()}
                                </div>
                                <div>
                                  <p className="font-semibold text-slate-800">{mUser.name || 'Unknown'}</p>
                                  <p className="text-[10px] text-slate-400">{mUser.user_id || mUser.email || ''}</p>
                                </div>
                              </div>
                            </td>
                            <td className="py-2.5 px-3">
                              <span className="capitalize text-[10px] font-bold px-2 py-0.5 rounded bg-blue-50 text-blue-700">
                                {cm.role || 'Member'}
                              </span>
                            </td>
                            <td className="py-2.5 px-3">
                              <StatusBadge status={cm.status || 'accepted'} />
                            </td>
                            <td className="py-2.5 px-3 text-slate-500">
                              {cm.created_at ? new Date(cm.created_at).toLocaleDateString() : 'Recently'}
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          </TabPanel>

          {/* Tab 3: Join Requests */}
          <TabPanel header={`Join Requests (${pendingCount})`}>
            <div className="pt-4 space-y-4">
              {pendingMembers.length === 0 ? (
                <EmptyState
                  icon={Clock}
                  title="No Pending Requests"
                  description="There are no pending join requests awaiting approval for this community."
                />
              ) : (
                <div className="overflow-x-auto">
                  <table className="w-full text-left text-xs">
                    <thead>
                      <tr className="border-b border-slate-200 text-slate-500">
                        <th className="py-2.5 px-3">Applicant</th>
                        <th className="py-2.5 px-3">Requested Date</th>
                        <th className="py-2.5 px-3 text-right">Actions</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {pendingMembers.map((pm) => {
                        const mUser = pm.member || pm;
                        return (
                          <tr key={pm.id} className="hover:bg-slate-50">
                            <td className="py-2.5 px-3">
                              <div className="flex items-center gap-2">
                                <div className="w-7 h-7 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-semibold text-xs border border-amber-200">
                                  {(mUser.name || 'P').charAt(0).toUpperCase()}
                                </div>
                                <div>
                                  <p className="font-semibold text-slate-800">{mUser.name || 'Applicant'}</p>
                                  <p className="text-[10px] text-slate-400">{mUser.user_id || mUser.email || ''}</p>
                                </div>
                              </div>
                            </td>
                            <td className="py-2.5 px-3 text-slate-500">
                              {pm.created_at ? new Date(pm.created_at).toLocaleDateString() : 'Pending'}
                            </td>
                            <td className="py-2.5 px-3 text-right">
                              <div className="inline-flex items-center gap-2">
                                <Button
                                  label="Approve"
                                  icon={<Check className="w-3 h-3 mr-1" />}
                                  size="small"
                                  onClick={() => handleJoinRequestAction(pm.id, 'approve')}
                                  disabled={actionLoading}
                                  className="p-button-success p-button-sm text-xs py-1 px-2.5"
                                />
                                <Button
                                  label="Reject"
                                  icon={<X className="w-3 h-3 mr-1" />}
                                  size="small"
                                  onClick={() => handleJoinRequestAction(pm.id, 'reject')}
                                  disabled={actionLoading}
                                  className="p-button-outlined p-button-danger p-button-sm text-xs py-1 px-2.5"
                                />
                              </div>
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          </TabPanel>

          {/* Tab 4: Recent Posts */}
          <TabPanel header={`Posts (${postsCount})`}>
            <div className="pt-4 space-y-4">
              {posts.length === 0 ? (
                <EmptyState
                  icon={FileText}
                  title="No Community Posts"
                  description="No posts have been published in this community yet."
                />
              ) : (
                <div className="space-y-3">
                  {posts.map((post) => (
                    <div key={post.id} className="p-4 bg-slate-50 rounded-xl border border-slate-200/80 text-xs space-y-2">
                      <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                          <div className="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-[10px]">
                            {(post.member?.name || 'A').charAt(0).toUpperCase()}
                          </div>
                          <span className="font-semibold text-slate-800">{post.member?.name || 'Community Member'}</span>
                          <span className="text-[10px] text-slate-400">
                            {post.created_at ? new Date(post.created_at).toLocaleString() : ''}
                          </span>
                        </div>
                        <StatusBadge status={post.status || 'published'} />
                      </div>
                      <p className="text-slate-700 whitespace-pre-wrap">
                        {post.content || post.body || 'Post without text content.'}
                      </p>
                      <div className="flex items-center gap-4 text-[11px] text-slate-400 pt-1 border-t border-slate-200/60">
                        <span>Likes: {post.likes_count ?? 0}</span>
                        <span>Comments: {post.comments_count ?? 0}</span>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </TabPanel>

          {/* Tab 5: Reports */}
          <TabPanel header={`Reports (${reportsCount})`}>
            <div className="pt-4 space-y-4">
              {reportsList.length === 0 ? (
                <EmptyState
                  icon={Shield}
                  title="Clean Moderation Record"
                  description="There are currently no active moderation reports flagged against this community."
                />
              ) : (
                <div className="overflow-x-auto">
                  <table className="w-full text-left text-xs">
                    <thead>
                      <tr className="border-b border-slate-200 text-slate-500">
                        <th className="py-2.5 px-3">Reporter</th>
                        <th className="py-2.5 px-3">Reason</th>
                        <th className="py-2.5 px-3">Status</th>
                        <th className="py-2.5 px-3">Date</th>
                        <th className="py-2.5 px-3 text-right">Actions</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {reportsList.map((rep) => (
                        <tr key={rep.id} className="hover:bg-slate-50">
                          <td className="py-2.5 px-3 font-medium text-slate-800">
                            {rep.reporter?.name || 'Anonymous User'}
                          </td>
                          <td className="py-2.5 px-3 text-slate-600">
                            {rep.reason || rep.notes || 'Inappropriate content'}
                          </td>
                          <td className="py-2.5 px-3">
                            <StatusBadge status={rep.status || 'pending'} />
                          </td>
                          <td className="py-2.5 px-3 text-slate-400">
                            {rep.created_at ? new Date(rep.created_at).toLocaleDateString() : 'Recently'}
                          </td>
                          <td className="py-2.5 px-3 text-right">
                            {rep.status !== 'resolved' ? (
                              <Button
                                label="Resolve"
                                icon={<Check className="w-3 h-3 mr-1" />}
                                size="small"
                                onClick={() => handleResolveReport(rep.id)}
                                disabled={actionLoading}
                                className="p-button-outlined p-button-success p-button-sm text-xs py-1 px-2"
                              />
                            ) : (
                              <span className="text-[11px] text-emerald-600 font-semibold">Resolved</span>
                            )}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          </TabPanel>
        </TabView>
      </div>
    </div>
  );
}

export default CommunityDetailsPage;
