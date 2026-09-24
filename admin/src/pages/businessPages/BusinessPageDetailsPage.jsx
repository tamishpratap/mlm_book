import { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import {
  Users,
  ShieldCheck,
  ShieldAlert,
  Trash2,
  CheckCircle,
  XCircle,
  ArrowLeft,
  Star,
  FileText,
  MessageSquare,
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
import { businessPagesApi } from '../../api';

export function BusinessPageDetailsPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { showSuccess, showError } = useToast();

  const [businessPage, setBusinessPage] = useState(null);
  const [posts, setPosts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [error, setError] = useState(null);
  const [activeTab, setActiveTab] = useState(0);

  const fetchPageDetails = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await businessPagesApi.getPage(id);
      const data = res?.businessPage || res?.data || res;
      setBusinessPage(data);
      setPosts(res?.posts || data?.posts || []);
    } catch (err) {
      setError(err.message || 'Failed to load business page details.');
    } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => {
    let isMounted = true;
    businessPagesApi
      .getPage(id)
      .then((res) => {
        if (!isMounted) return;
        const data = res?.businessPage || res?.data || res;
        setBusinessPage(data);
        setPosts(res?.posts || data?.posts || []);
      })
      .catch((err) => {
        if (!isMounted) return;
        setError(err.message || 'The requested business page could not be found.');
      })
      .finally(() => {
        if (isMounted) setLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [id]);

  const handleToggleStatus = async () => {
    if (!businessPage || actionLoading) return;
    const isCurrentlyActive = businessPage.status === 'active';
    const newStatus = isCurrentlyActive ? 'inactive' : 'active';

    setActionLoading(true);
    try {
      const res = await businessPagesApi.updateStatus(businessPage.id, newStatus);
      showSuccess(res?.message || `Business page status set to ${newStatus}.`);
      setBusinessPage((prev) => (prev ? { ...prev, status: newStatus } : prev));
    } catch (err) {
      showError(err.message || 'Failed to update business page status.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleToggleVerification = async () => {
    if (!businessPage || actionLoading) return;
    const isVerified = Boolean(businessPage.is_verified);
    const action = isVerified ? 'reject' : 'approve';

    setActionLoading(true);
    try {
      await businessPagesApi.handleVerification(businessPage.id, { action });
      showSuccess(
        isVerified ? 'Verification status revoked.' : 'Business page successfully verified!'
      );
      setBusinessPage((prev) =>
        prev
          ? {
              ...prev,
              is_verified: !isVerified,
              verification_status: isVerified ? 'rejected' : 'approved',
            }
          : prev
      );
    } catch (err) {
      showError(err.message || 'Failed to update verification status.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleDelete = () => {
    if (!businessPage || actionLoading) return;
    confirmHelper.confirmDelete({
      header: 'Delete Business Page',
      message: `Are you sure you want to permanently delete "${businessPage.page_name}"? All products, followers, and reviews will be removed.`,
      onAccept: async () => {
        setActionLoading(true);
        try {
          await businessPagesApi.deletePage(businessPage.id);
          showSuccess(`Business page "${businessPage.page_name}" deleted.`);
          navigate('/admin/business-pages');
        } catch (err) {
          showError(err.message || 'Failed to delete business page.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  if (loading) {
    return (
      <div className="py-16">
        <LoadingSpinner message="Loading business page details..." />
      </div>
    );
  }

  if (error || !businessPage) {
    return (
      <div className="space-y-6">
        <PageHeader
          title="Business Page Inspection"
          breadcrumbs={[{ label: 'Business Pages', to: '/admin/business-pages' }, { label: 'Inspection' }]}
        />
        <ErrorState
          title="Business Page Not Found"
          message={error || 'The requested business page could not be located in the system.'}
          onRetry={fetchPageDetails}
        />
        <div className="flex justify-center pt-2">
          <Link to="/admin/business-pages">
            <Button
              label="Return to Business Directory"
              icon="pi pi-arrow-left"
              size="small"
              className="p-button-outlined p-button-secondary text-xs"
            />
          </Link>
        </div>
      </div>
    );
  }

  const logoUrl = businessPage.logo_url || businessPage.logo || businessPage.avatar_url;
  const coverUrl = businessPage.cover_url || businessPage.cover_photo;
  const followersList = businessPage.acceptedFollowers || businessPage.followers || [];
  const reviewsList = businessPage.reviews || [];
  const teamList = businessPage.teamMembers || [];
  const verificationsList = businessPage.verifications || [];
  const followersCount = businessPage.accepted_followers_count ?? followersList.length;
  const postsCount = businessPage.posts_count ?? posts.length;
  const reviewsCount = businessPage.reviews_count ?? reviewsList.length;

  return (
    <div className="space-y-6">
      {/* Top Page Header */}
      <PageHeader
        title={`Business Page: ${businessPage.page_name}`}
        subtitle="Review business verification, followers, customer reviews, posts, and manage status."
        breadcrumbs={[
          { label: 'Business Pages', to: '/admin/business-pages' },
          { label: businessPage.page_name },
        ]}
        actions={
          <div className="flex flex-wrap items-center gap-2">
            <Button
              label={businessPage.is_verified ? 'Revoke Verification' : 'Approve Verification'}
              icon={
                businessPage.is_verified ? (
                  <ShieldAlert className="w-3.5 h-3.5 mr-1.5 text-amber-500" />
                ) : (
                  <ShieldCheck className="w-3.5 h-3.5 mr-1.5 text-blue-600" />
                )
              }
              size="small"
              onClick={handleToggleVerification}
              loading={actionLoading}
              className={
                businessPage.is_verified
                  ? 'p-button-outlined p-button-warning text-xs'
                  : 'p-button-outlined p-button-info text-xs'
              }
            />

            <Button
              label={businessPage.status === 'active' ? 'Suspend Page' : 'Activate Page'}
              icon={
                businessPage.status === 'active' ? (
                  <XCircle className="w-3.5 h-3.5 mr-1.5 text-amber-500" />
                ) : (
                  <CheckCircle className="w-3.5 h-3.5 mr-1.5 text-emerald-600" />
                )
              }
              size="small"
              onClick={handleToggleStatus}
              loading={actionLoading}
              className={
                businessPage.status === 'active'
                  ? 'p-button-outlined p-button-warning text-xs'
                  : 'p-button-success text-xs'
              }
            />

            <Button
              label="Delete"
              icon={<Trash2 className="w-3.5 h-3.5 mr-1.5 text-red-500" />}
              size="small"
              onClick={handleDelete}
              loading={actionLoading}
              className="p-button-outlined p-button-danger text-xs"
            />

            <Link to="/admin/business-pages" className="inline-flex">
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

      {/* Identity Banner & Metrics Header */}
      <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        {/* Cover Graphic */}
        <div
          className="h-36 sm:h-44 w-full relative bg-cover bg-center"
          style={{
            background: coverUrl
              ? `url("${coverUrl}") center/cover no-repeat`
              : 'linear-gradient(135deg, #0284c7 0%, #0369a1 50%, #0f172a 100%)',
          }}
        >
          <div className="absolute inset-0 bg-slate-900/30" />
        </div>

        {/* Identity Row */}
        <div className="px-6 pb-6 pt-0 relative">
          <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-4 -mt-12 sm:-mt-14 mb-6">
            <div className="flex flex-col sm:flex-row items-center sm:items-end gap-4 text-center sm:text-left">
              {/* Logo */}
              <div className="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl bg-white p-1 shadow-md border-2 border-white overflow-hidden shrink-0">
                {logoUrl ? (
                  <img
                    src={logoUrl}
                    alt={businessPage.page_name}
                    className="w-full h-full object-cover rounded-xl"
                    onError={(e) => {
                      e.target.style.display = 'none';
                    }}
                  />
                ) : (
                  <div className="w-full h-full bg-sky-50 text-sky-600 flex items-center justify-center font-extrabold text-2xl rounded-xl">
                    {(businessPage.page_name || 'B').charAt(0).toUpperCase()}
                  </div>
                )}
              </div>

              {/* Title & Badges */}
              <div className="space-y-1.5">
                <div className="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                  <h2 className="text-xl sm:text-2xl font-bold text-slate-900">{businessPage.page_name}</h2>
                  <StatusBadge status={businessPage.status || 'active'} />
                  {businessPage.is_verified ? (
                    <span className="inline-flex items-center text-[10px] font-semibold bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full border border-emerald-200">
                      <ShieldCheck className="w-2.5 h-2.5 mr-1 text-emerald-600" /> Verified Business
                    </span>
                  ) : (
                    <span className="inline-flex items-center text-[10px] font-semibold bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full border border-slate-200">
                      Unverified
                    </span>
                  )}
                </div>

                <div className="flex flex-wrap items-center justify-center sm:justify-start gap-3 text-xs text-slate-500">
                  {businessPage.page_username && (
                    <span className="font-mono text-slate-600 bg-slate-100 px-2 py-0.5 rounded">
                      @{businessPage.page_username}
                    </span>
                  )}
                  <span>Category: <strong className="text-slate-700">{businessPage.category || 'General'}</strong></span>
                  <span>•</span>
                  <span>Owner: <strong className="text-slate-700">{businessPage.owner?.name || 'Unknown'}</strong></span>
                </div>
              </div>
            </div>
          </div>

          {/* Statistics Bar */}
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-4 border-t border-slate-100 text-center">
            <div className="p-3 bg-slate-50 rounded-xl">
              <span className="text-xs text-slate-500 font-medium block">Total Followers</span>
              <span className="text-lg font-extrabold text-blue-600">{followersCount}</span>
            </div>
            <div className="p-3 bg-slate-50 rounded-xl">
              <span className="text-xs text-slate-500 font-medium block">Page Posts</span>
              <span className="text-lg font-extrabold text-indigo-600">{postsCount}</span>
            </div>
            <div className="p-3 bg-slate-50 rounded-xl">
              <span className="text-xs text-slate-500 font-medium block">Reviews</span>
              <span className="text-lg font-extrabold text-amber-600">{reviewsCount}</span>
            </div>
            <div className="p-3 bg-slate-50 rounded-xl">
              <span className="text-xs text-slate-500 font-medium block">Team Members</span>
              <span className="text-lg font-extrabold text-purple-600">{teamList.length}</span>
            </div>
          </div>
        </div>
      </div>

      {/* Tabbed Inspection Sections */}
      <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 sm:p-6">
        <TabView activeIndex={activeTab} onTabChange={(e) => setActiveTab(e.index)}>
          {/* Tab 1: Overview & Contact */}
          <TabPanel header="Overview & Contact">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
              <div className="space-y-4">
                <h4 className="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2">Business Profile</h4>
                <div className="grid grid-cols-3 gap-2 text-xs py-1">
                  <span className="text-slate-400 font-medium">Business Name:</span>
                  <span className="col-span-2 text-slate-800 font-semibold">{businessPage.page_name}</span>
                </div>
                <div className="grid grid-cols-3 gap-2 text-xs py-1">
                  <span className="text-slate-400 font-medium">Page ID / Handle:</span>
                  <span className="col-span-2 font-mono text-blue-600 font-medium">
                    {businessPage.page_username ? `@${businessPage.page_username}` : businessPage.page_id || businessPage.id}
                  </span>
                </div>
                <div className="grid grid-cols-3 gap-2 text-xs py-1">
                  <span className="text-slate-400 font-medium">Category:</span>
                  <span className="col-span-2 text-slate-800">{businessPage.category || 'General'}</span>
                </div>
                <div className="grid grid-cols-3 gap-2 text-xs py-1">
                  <span className="text-slate-400 font-medium">Status:</span>
                  <span className="col-span-2"><StatusBadge status={businessPage.status || 'active'} /></span>
                </div>
                <div className="grid grid-cols-3 gap-2 text-xs py-1">
                  <span className="text-slate-400 font-medium">Verification:</span>
                  <span className="col-span-2">
                    <StatusBadge status={businessPage.is_verified ? 'verified' : (businessPage.verification_status || 'unverified')} />
                  </span>
                </div>
                <div className="grid grid-cols-3 gap-2 text-xs py-1">
                  <span className="text-slate-400 font-medium">Created Date:</span>
                  <span className="col-span-2 text-slate-800">
                    {businessPage.created_at ? new Date(businessPage.created_at).toLocaleDateString() : 'N/A'}
                  </span>
                </div>

                <div className="pt-2 border-t border-slate-100">
                  <span className="text-xs text-slate-400 font-medium block mb-1">Page Owner</span>
                  <div className="flex items-center gap-3 bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                    <div className="w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs">
                      {(businessPage.owner?.name || 'O').charAt(0).toUpperCase()}
                    </div>
                    <div>
                      <p className="text-xs font-semibold text-slate-800">{businessPage.owner?.name || 'Unknown Owner'}</p>
                      <p className="text-[11px] text-slate-400">{businessPage.owner?.email || (businessPage.owner?.user_id ? `ID: ${businessPage.owner.user_id}` : 'N/A')}</p>
                    </div>
                  </div>
                </div>
              </div>

              <div className="space-y-4">
                <h4 className="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2">Contact & Location</h4>
                <div className="grid grid-cols-3 gap-2 text-xs py-1">
                  <span className="text-slate-400 font-medium">Email:</span>
                  <span className="col-span-2 text-slate-800">{businessPage.email || 'N/A'}</span>
                </div>
                <div className="grid grid-cols-3 gap-2 text-xs py-1">
                  <span className="text-slate-400 font-medium">Phone:</span>
                  <span className="col-span-2 text-slate-800">{businessPage.phone || 'N/A'}</span>
                </div>
                <div className="grid grid-cols-3 gap-2 text-xs py-1">
                  <span className="text-slate-400 font-medium">Website:</span>
                  <span className="col-span-2 text-blue-600">
                    {businessPage.website ? (
                      <a href={businessPage.website.startsWith('http') ? businessPage.website : `https://${businessPage.website}`} target="_blank" rel="noreferrer" className="underline">
                        {businessPage.website}
                      </a>
                    ) : (
                      'N/A'
                    )}
                  </span>
                </div>
                <div className="grid grid-cols-3 gap-2 text-xs py-1">
                  <span className="text-slate-400 font-medium">Country / Region:</span>
                  <span className="col-span-2 text-slate-800">{businessPage.country || businessPage.location || 'N/A'}</span>
                </div>
                <div className="grid grid-cols-3 gap-2 text-xs py-1">
                  <span className="text-slate-400 font-medium">Address:</span>
                  <span className="col-span-2 text-slate-800">{businessPage.address || 'N/A'}</span>
                </div>

                <div className="pt-2 border-t border-slate-100">
                  <span className="text-xs text-slate-400 font-medium block mb-1">About / Bio</span>
                  <div className="bg-slate-50 border border-slate-100 rounded-xl p-3 text-xs text-slate-700 whitespace-pre-wrap min-h-[70px]">
                    {businessPage.bio || businessPage.about || businessPage.description || 'No description provided.'}
                  </div>
                </div>
              </div>
            </div>
          </TabPanel>

          {/* Tab 2: Verification Queue */}
          <TabPanel header={`Verification (${verificationsList.length})`}>
            <div className="pt-4 space-y-4">
              {verificationsList.length === 0 ? (
                <EmptyState
                  icon={ShieldCheck}
                  title="No Verification Documents"
                  description="No verification requests or uploaded documents have been filed for this business page."
                />
              ) : (
                <div className="overflow-x-auto">
                  <table className="w-full text-left text-xs">
                    <thead>
                      <tr className="border-b border-slate-200 text-slate-500">
                        <th className="py-2.5 px-3">Applicant</th>
                        <th className="py-2.5 px-3">Document Type</th>
                        <th className="py-2.5 px-3">Status</th>
                        <th className="py-2.5 px-3">Submitted Date</th>
                        <th className="py-2.5 px-3">Notes</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {verificationsList.map((ver) => (
                        <tr key={ver.id} className="hover:bg-slate-50">
                          <td className="py-2.5 px-3 font-semibold text-slate-800">
                            {ver.member?.name || 'Owner'}
                          </td>
                          <td className="py-2.5 px-3 text-slate-600 capitalize">
                            {ver.document_type || 'Business License'}
                          </td>
                          <td className="py-2.5 px-3">
                            <StatusBadge status={ver.status || 'pending'} />
                          </td>
                          <td className="py-2.5 px-3 text-slate-500">
                            {ver.created_at ? new Date(ver.created_at).toLocaleDateString() : 'Recently'}
                          </td>
                          <td className="py-2.5 px-3 text-slate-500">
                            {ver.notes || 'No review notes.'}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          </TabPanel>

          {/* Tab 3: Followers */}
          <TabPanel header={`Followers (${followersCount})`}>
            <div className="pt-4 space-y-4">
              {followersList.length === 0 ? (
                <EmptyState
                  icon={Users}
                  title="No Followers Yet"
                  description="This business page currently does not have any active followers."
                />
              ) : (
                <div className="overflow-x-auto">
                  <table className="w-full text-left text-xs">
                    <thead>
                      <tr className="border-b border-slate-200 text-slate-500">
                        <th className="py-2.5 px-3">Member</th>
                        <th className="py-2.5 px-3">User ID</th>
                        <th className="py-2.5 px-3">Followed Date</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {followersList.map((f, idx) => {
                        const mUser = f.member || f;
                        return (
                          <tr key={f.id || idx} className="hover:bg-slate-50">
                            <td className="py-2.5 px-3">
                              <div className="flex items-center gap-2">
                                <div className="w-7 h-7 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center font-semibold text-xs border border-slate-200">
                                  {(mUser.name || 'U').charAt(0).toUpperCase()}
                                </div>
                                <div>
                                  <p className="font-semibold text-slate-800">{mUser.name || 'Follower'}</p>
                                  <p className="text-[10px] text-slate-400">{mUser.email || ''}</p>
                                </div>
                              </div>
                            </td>
                            <td className="py-2.5 px-3 text-slate-500 font-mono">
                              {mUser.user_id || `ID: ${mUser.id || 'N/A'}`}
                            </td>
                            <td className="py-2.5 px-3 text-slate-500">
                              {f.created_at ? new Date(f.created_at).toLocaleDateString() : 'Recently'}
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

          {/* Tab 4: Customer Reviews */}
          <TabPanel header={`Reviews (${reviewsCount})`}>
            <div className="pt-4 space-y-4">
              {reviewsList.length === 0 ? (
                <EmptyState
                  icon={MessageSquare}
                  title="No Customer Reviews"
                  description="This business page has not received any customer reviews yet."
                />
              ) : (
                <div className="space-y-3">
                  {reviewsList.map((rev) => (
                    <div key={rev.id} className="p-4 bg-slate-50 rounded-xl border border-slate-200/80 text-xs space-y-2">
                      <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                          <div className="w-6 h-6 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-[10px]">
                            {(rev.member?.name || 'R').charAt(0).toUpperCase()}
                          </div>
                          <span className="font-semibold text-slate-800">{rev.member?.name || 'Reviewer'}</span>
                          <span className="text-[10px] text-slate-400">
                            {rev.created_at ? new Date(rev.created_at).toLocaleDateString() : ''}
                          </span>
                        </div>
                        <div className="flex items-center text-amber-500">
                          {Array.from({ length: 5 }).map((_, i) => (
                            <Star
                              key={i}
                              className={`w-3 h-3 ${i < (rev.rating || 5) ? 'fill-amber-400 text-amber-400' : 'text-slate-300'}`}
                            />
                          ))}
                        </div>
                      </div>
                      <p className="text-slate-700 whitespace-pre-wrap">
                        {rev.review || rev.comment || 'No written comment.'}
                      </p>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </TabPanel>

          {/* Tab 5: Recent Posts */}
          <TabPanel header={`Posts (${postsCount})`}>
            <div className="pt-4 space-y-4">
              {posts.length === 0 ? (
                <EmptyState
                  icon={FileText}
                  title="No Page Posts"
                  description="No posts have been published on this business page yet."
                />
              ) : (
                <div className="space-y-3">
                  {posts.map((post) => (
                    <div key={post.id} className="p-4 bg-slate-50 rounded-xl border border-slate-200/80 text-xs space-y-2">
                      <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                          <div className="w-6 h-6 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center font-bold text-[10px]">
                            {(post.member?.name || 'B').charAt(0).toUpperCase()}
                          </div>
                          <span className="font-semibold text-slate-800">{post.member?.name || 'Page Admin'}</span>
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
        </TabView>
      </div>
    </div>
  );
}

export default BusinessPageDetailsPage;
