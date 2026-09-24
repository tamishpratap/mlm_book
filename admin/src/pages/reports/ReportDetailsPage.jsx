import { useState, useEffect, useCallback } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import {
  ArrowLeft,
  CheckCircle2,
  Flag,
  User,
  Clock,
  AlertTriangle,
  XCircle,
  Trash2,
  ShieldAlert,
  FileText,
} from 'lucide-react';
import { Button } from 'primereact/button';
import { PageHeader } from '../../components/common/PageHeader';
import { StatusBadge } from '../../components/common/StatusBadge';
import { ErrorState } from '../../components/common/ErrorState';
import { ReportDetailsSkeleton } from './components/ReportDetailsSkeleton';
import { ReportMediaViewer } from './components/ReportMediaViewer';
import { useToast } from '../../hooks/useToast';
import { confirmHelper } from '../../utils/confirmHelper';
import { reportsApi } from '../../api';

export function ReportDetailsPage() {
  const { type, id } = useParams();
  const navigate = useNavigate();
  const { showSuccess, showError } = useToast();

  const [reportData, setReportData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [error, setError] = useState(null);

  const fetchReportDetails = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await reportsApi.getReport(type, id);
      const data = res?.reportData || res?.data || res;
      setReportData(data);
    } catch (err) {
      setError(err.message || 'Failed to load report inspection details.');
    } finally {
      setLoading(false);
    }
  }, [type, id]);

  useEffect(() => {
    let isMounted = true;
    reportsApi
      .getReport(type, id)
      .then((res) => {
        if (!isMounted) return;
        setReportData(res?.reportData || res?.data || res);
      })
      .catch((err) => {
        if (!isMounted) return;
        setError(err.message || 'Failed to load report inspection details.');
      })
      .finally(() => {
        if (isMounted) setLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [type, id]);

  const handleUpdateStatus = async (newStatus) => {
    setActionLoading(true);
    try {
      await reportsApi.updateReportStatus(type, id, newStatus);
      showSuccess(`Report #${id} status updated to ${newStatus}.`);
      fetchReportDetails();
    } catch (err) {
      showError(err.message || 'Failed to update report status.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleDeleteReport = () => {
    confirmHelper.confirm({
      header: 'Delete Report Ticket?',
      message: `Are you sure you want to permanently delete Report #${id}? The reported content and user accounts will remain unchanged.`,
      acceptClassName: 'p-button-danger text-xs',
      onAccept: async () => {
        setActionLoading(true);
        try {
          await reportsApi.deleteReport(type, id);
          showSuccess(`Report #${id} ticket deleted successfully.`);
          navigate('/admin/reports');
        } catch (err) {
          showError(err.message || 'Failed to delete report ticket.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  const getTargetTypeLabel = () => {
    switch (type) {
      case 'post':
        return 'Post';
      case 'community':
        return 'Community Content';
      case 'product':
        return 'Product Listing';
      case 'business_review':
        return 'Business Review';
      default:
        return 'Content';
    }
  };

  const handleDeleteTarget = () => {
    const targetLabel = getTargetTypeLabel();

    confirmHelper.confirm({
      header: `Delete Reported ${targetLabel}?`,
      message: `Are you sure you want to permanently remove this reported ${targetLabel.toLowerCase()} and its attached media? The author and reporter accounts will NOT be deleted.`,
      acceptClassName: 'p-button-danger text-xs',
      onAccept: async () => {
        setActionLoading(true);
        try {
          const res = await reportsApi.deleteReportTarget(type, id);
          showSuccess(res?.message || `Reported ${targetLabel.toLowerCase()} deleted successfully.`);
          navigate('/admin/reports');
        } catch (err) {
          showError(err.message || `Failed to delete reported ${targetLabel.toLowerCase()}.`);
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  if (loading) {
    return <ReportDetailsSkeleton />;
  }

  if (error || !reportData) {
    return (
      <div className="space-y-6">
        <PageHeader
          title="Report Inspection"
          breadcrumbs={[{ label: 'Reports', to: '/admin/reports' }, { label: 'Inspection' }]}
        />
        <ErrorState
          title="Report Record Not Found"
          message={error || 'The requested moderation report could not be located.'}
          onRetry={fetchReportDetails}
        />
      </div>
    );
  }

  const isResolved = reportData.status === 'resolved';
  const isDismissed = reportData.status === 'dismissed';
  const targetAuthor = reportData.target?.member;

  return (
    <div className="space-y-6">
      {/* Top Page Header */}
      <PageHeader
        title={`Report Inspection #${reportData.id} (${reportData.type_label || reportData.type})`}
        subtitle="Review violation details, inspect reported content, and take moderation action."
        breadcrumbs={[
          { label: 'Reports Queue', to: '/admin/reports' },
          { label: `Report #${reportData.id}` },
        ]}
        actions={
          <div className="flex flex-wrap items-center gap-2 sm:gap-2.5">
            {!isResolved ? (
              <Button
                label="Resolve Report"
                icon={<CheckCircle2 className="w-3.5 h-3.5 mr-1" />}
                size="small"
                onClick={() => handleUpdateStatus('resolved')}
                loading={actionLoading}
                className="p-button-success text-xs !h-9 !rounded-lg"
              />
            ) : (
              <Button
                label="Reopen as Pending"
                icon={<AlertTriangle className="w-3.5 h-3.5 mr-1" />}
                size="small"
                onClick={() => handleUpdateStatus('pending')}
                loading={actionLoading}
                className="p-button-outlined p-button-warning text-xs !h-9 !rounded-lg"
              />
            )}

            {!isDismissed && !isResolved && (
              <Button
                label="Dismiss Report"
                icon={<XCircle className="w-3.5 h-3.5 mr-1" />}
                size="small"
                onClick={() => handleUpdateStatus('dismissed')}
                loading={actionLoading}
                className="p-button-outlined p-button-secondary text-xs !h-9 !rounded-lg"
              />
            )}

            <Button
              label="Delete Ticket"
              icon={<Trash2 className="w-3.5 h-3.5 mr-1" />}
              size="small"
              onClick={handleDeleteReport}
              loading={actionLoading}
              className="p-button-outlined p-button-danger text-xs !h-9 !rounded-lg"
            />

            <Link to="/admin/reports" className="inline-flex">
              <Button
                label="Back to Queue"
                icon={<ArrowLeft className="w-3.5 h-3.5 mr-1" />}
                size="small"
                className="p-button-outlined p-button-secondary text-xs !h-9 !rounded-lg"
              />
            </Link>
          </div>
        }
      />

      {/* Main Responsive Grid Layout */}
      <div className="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_320px] xl:grid-cols-[minmax(0,1fr)_340px] gap-6 items-start">
        {/* ========================================================================= */}
        {/* Left Column: Violation Info & Reported Target Content Area               */}
        {/* ========================================================================= */}
        <div className="space-y-6 min-w-0">
          {/* Card 1: Violation Details */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 sm:p-6 space-y-5">
            <div className="flex items-center justify-between border-b border-slate-100 pb-3.5 flex-wrap gap-2">
              <div className="flex items-center space-x-2">
                <Flag className="w-4 h-4 text-red-600" />
                <h4 className="text-sm font-bold text-slate-900">Violation Details</h4>
              </div>
              <StatusBadge status={reportData.status || 'pending'} />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
              <div className="p-4 bg-red-50/50 rounded-xl border border-red-100/80 space-y-1">
                <span className="text-[10px] font-bold uppercase tracking-wider text-red-700 block">
                  Reason for Report
                </span>
                <span className="text-sm font-bold text-red-900 block break-words">
                  {reportData.reason}
                </span>
              </div>

              <div className="p-4 bg-slate-50 rounded-xl border border-slate-100 space-y-1">
                <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">
                  Reported Timestamp
                </span>
                <span className="text-xs font-semibold text-slate-700 flex items-center">
                  <Clock className="w-3.5 h-3.5 mr-1.5 text-slate-400 shrink-0" />
                  {reportData.created_at_human || 'Recently'}
                </span>
              </div>
            </div>

            <div className="space-y-1.5">
              <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">
                Reporter Notes / Description
              </span>
              <p className="text-xs sm:text-sm text-slate-700 bg-slate-50 p-4 rounded-xl border border-slate-100 leading-relaxed whitespace-pre-line break-words">
                {reportData.details || 'No additional explanation provided by the reporter.'}
              </p>
            </div>
          </div>

          {/* Card 2: Reported Target Content Area */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 sm:p-6 space-y-5">
            <div className="flex items-center justify-between border-b border-slate-100 pb-3.5 flex-wrap gap-2">
              <div className="flex items-center space-x-2">
                <FileText className="w-4 h-4 text-slate-700" />
                <h4 className="text-sm font-bold text-slate-900">
                  Reported Target Content ({reportData.type_label || reportData.type})
                </h4>
              </div>
              {reportData.target?.id && (
                <span className="text-xs font-mono text-slate-400">
                  Target #{reportData.target.id}
                </span>
              )}
            </div>

            {reportData.target ? (
              <div className="p-4 sm:p-5 bg-slate-50/75 rounded-xl border border-slate-200/80 space-y-4">
                {/* Target Header: Title/ID + Status Badge */}
                <div className="flex items-center justify-between gap-4 flex-wrap pb-3 border-b border-slate-200/60">
                  <div className="min-w-0 flex-1">
                    <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">
                      Content Identification
                    </span>
                    <strong className="text-slate-900 text-sm sm:text-base font-bold truncate block">
                      {reportData.target.title || reportData.target.name || `${getTargetTypeLabel()} #${reportData.target.id}`}
                    </strong>
                  </div>
                  {reportData.target.status && (
                    <div className="shrink-0">
                      <span className="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-white border border-slate-200 text-slate-700 shadow-2xs">
                        Status: <span className="ml-1 text-slate-900 capitalize">{reportData.target.status}</span>
                      </span>
                    </div>
                  )}
                </div>

                {/* Target Body / Text Description */}
                {(reportData.target.body || reportData.target.description) && (
                  <div className="space-y-1.5">
                    <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">
                      {reportData.type === 'post' ? 'Post Text / Caption' : 'Content Description'}
                    </span>
                    <p className="text-xs sm:text-sm text-slate-800 bg-white p-4 rounded-xl border border-slate-200 leading-relaxed whitespace-pre-line break-words">
                      {reportData.target.body || reportData.target.description}
                    </p>
                  </div>
                )}

                {/* Attached Media Viewer (Images & Videos) */}
                {(reportData.target.media_url ||
                  reportData.target.media_path ||
                  reportData.target.original_post?.media_url ||
                  reportData.target.original_post?.media_path) && (
                  <div className="pt-2">
                    <ReportMediaViewer
                      mediaType={
                        reportData.target.media_type ||
                        reportData.target.original_post?.media_type
                      }
                      mediaUrl={
                        reportData.target.media_url ||
                        reportData.target.original_post?.media_url
                      }
                      mediaPath={
                        reportData.target.media_path ||
                        reportData.target.original_post?.media_path
                      }
                      label={
                        reportData.target.original_post && !reportData.target.media_path
                          ? 'Original Post Media'
                          : 'Attached Media'
                      }
                    />
                  </div>
                )}
              </div>
            ) : (
              <div className="p-8 text-center text-xs text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-200 space-y-2">
                <AlertTriangle className="w-7 h-7 mx-auto text-amber-500" />
                <p className="font-semibold text-slate-700 text-sm">Target content record has been deleted or is unavailable.</p>
                <p className="text-xs text-slate-400 max-w-sm mx-auto">The underlying item was removed from the database or feed by an admin or user.</p>
              </div>
            )}
          </div>
        </div>

        {/* ========================================================================= */}
        {/* Right Sidebar: Author Info, Reporter Identity & Moderation Actions        */}
        {/* ========================================================================= */}
        <div className="space-y-6 min-w-0">
          {/* Card 1: Content Author Card */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 sm:p-6 space-y-4">
            <div className="flex items-center justify-between border-b border-slate-100 pb-3">
              <div className="flex items-center space-x-2">
                <User className="w-4 h-4 text-slate-700" />
                <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
                  Content Author
                </h4>
              </div>
            </div>

            {targetAuthor ? (
              <div className="space-y-4">
                <div className="flex items-center space-x-3 min-w-0">
                  <div className="w-11 h-11 rounded-full bg-slate-100 text-slate-700 font-bold flex items-center justify-center shrink-0 border border-slate-200/80 overflow-hidden shadow-2xs">
                    {targetAuthor.avatar_url ? (
                      <img
                        src={targetAuthor.avatar_url}
                        alt={targetAuthor.name || 'Author'}
                        className="w-full h-full object-cover rounded-full"
                      />
                    ) : (
                      <span className="text-sm">{(targetAuthor.name || 'U').charAt(0).toUpperCase()}</span>
                    )}
                  </div>
                  <div className="min-w-0 flex-1">
                    <h5 className="text-sm font-bold text-slate-900 truncate leading-snug">
                      {targetAuthor.name}
                    </h5>
                    <span className="text-xs text-slate-500 font-mono block truncate mt-0.5">
                      {targetAuthor.user_id ? `@${targetAuthor.user_id}` : 'User ID Unavailable'}
                    </span>
                  </div>
                </div>

                {targetAuthor.id && (
                  <div className="pt-1">
                    <Link to={`/admin/members/${targetAuthor.id}`} className="block">
                      <Button
                        label="View Author Profile"
                        icon={<User className="w-4 h-4 mr-2" />}
                        className="p-button-outlined p-button-secondary text-xs w-full !h-10 !rounded-xl justify-center font-medium"
                      />
                    </Link>
                  </div>
                )}
              </div>
            ) : (
              <p className="text-xs text-slate-400 italic py-2">
                Author information unavailable or content deleted.
              </p>
            )}
          </div>

          {/* Card 2: Reporter Identity Card */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 sm:p-6 space-y-4">
            <div className="flex items-center justify-between border-b border-slate-100 pb-3">
              <div className="flex items-center space-x-2">
                <User className="w-4 h-4 text-blue-600" />
                <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
                  Reporter Identity
                </h4>
              </div>
            </div>

            <div className="flex items-center space-x-3 min-w-0">
              <div className="w-11 h-11 rounded-full bg-blue-100 text-blue-700 font-bold flex items-center justify-center shrink-0 border border-blue-200/80 overflow-hidden shadow-2xs">
                {reportData.reporter?.avatar_url ? (
                  <img
                    src={reportData.reporter.avatar_url}
                    alt={reportData.reporter?.name || 'Reporter'}
                    className="w-full h-full object-cover rounded-full"
                  />
                ) : (
                  <span className="text-sm">{(reportData.reporter?.name || 'A').charAt(0).toUpperCase()}</span>
                )}
              </div>
              <div className="min-w-0 flex-1">
                <h5 className="text-sm font-bold text-slate-900 truncate leading-snug">
                  {reportData.reporter?.name || 'Anonymous Member'}
                </h5>
                <span className="text-xs text-blue-600 font-mono block truncate mt-0.5">
                  {reportData.reporter?.user_id ? `@${reportData.reporter.user_id}` : 'User ID Unavailable'}
                </span>
              </div>
            </div>

            {reportData.reporter?.id && (
              <div className="pt-1">
                <Link to={`/admin/members/${reportData.reporter.id}`} className="block">
                  <Button
                    label="View Reporter Profile"
                    icon={<User className="w-4 h-4 mr-2" />}
                    className="p-button-outlined p-button-secondary text-xs w-full !h-10 !rounded-xl justify-center font-medium"
                  />
                </Link>
              </div>
            )}
          </div>

          {/* Card 3: Moderation Actions Card */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 sm:p-6 flex flex-col gap-6">
            <div className="flex items-center justify-between border-b border-slate-100 pb-3">
              <div className="flex items-center space-x-2">
                <ShieldAlert className="w-4 h-4 text-slate-700" />
                <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
                  Moderation Actions
                </h4>
              </div>
            </div>

            <div className="flex flex-col gap-6">
              {/* Status Actions Group */}
              <div className="flex flex-col gap-3">
                <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">
                  Report Ticket Status
                </span>

                <div className="flex flex-col gap-3 w-full moderation-action-stack">
                  {!isResolved ? (
                    <Button
                      label="Resolve Report"
                      icon={<CheckCircle2 className="w-4 h-4 shrink-0" />}
                      onClick={() => handleUpdateStatus('resolved')}
                      loading={actionLoading}
                      className="w-full !min-h-[44px] !h-11 !rounded-xl text-xs font-semibold p-button-success flex items-center justify-center gap-2 shadow-2xs cursor-pointer"
                    />
                  ) : (
                    <Button
                      label="Reopen as Pending"
                      icon={<AlertTriangle className="w-4 h-4 shrink-0" />}
                      onClick={() => handleUpdateStatus('pending')}
                      loading={actionLoading}
                      className="w-full !min-h-[44px] !h-11 !rounded-xl text-xs font-semibold p-button-outlined p-button-warning flex items-center justify-center gap-2 cursor-pointer"
                    />
                  )}

                  {!isDismissed && !isResolved && (
                    <Button
                      label="Dismiss Report"
                      icon={<XCircle className="w-4 h-4 shrink-0" />}
                      onClick={() => handleUpdateStatus('dismissed')}
                      loading={actionLoading}
                      className="w-full !min-h-[44px] !h-11 !rounded-xl text-xs font-semibold p-button-outlined p-button-secondary flex items-center justify-center gap-2 cursor-pointer"
                    />
                  )}
                </div>
              </div>

              {/* Destructive Moderation Actions Group */}
              <div className="border-t border-slate-200/80 pt-5 flex flex-col gap-3">
                <div className="flex items-center space-x-1.5 mb-0.5">
                  <Trash2 className="w-3.5 h-3.5 text-red-500" />
                  <span className="text-[10px] font-bold uppercase tracking-wider text-red-600 block">
                    Destructive Actions
                  </span>
                </div>

                <div className="flex flex-col gap-3 w-full moderation-action-stack">
                  {/* Primary Destructive: Delete Reported Content (Solid Danger) */}
                  {reportData.target && (
                    <Button
                      label={`Delete Reported ${getTargetTypeLabel()}`}
                      icon={<ShieldAlert className="w-4 h-4 shrink-0" />}
                      onClick={handleDeleteTarget}
                      loading={actionLoading}
                      className="w-full !min-h-[44px] !h-11 !rounded-xl text-xs font-semibold p-button-danger flex items-center justify-center gap-2 shadow-xs cursor-pointer"
                    />
                  )}

                  {/* Secondary Destructive: Delete Report Ticket (Outline Danger) */}
                  <Button
                    label="Delete Report Ticket"
                    icon={<Trash2 className="w-4 h-4 shrink-0" />}
                    onClick={handleDeleteReport}
                    loading={actionLoading}
                    className="w-full !min-h-[44px] !h-11 !rounded-xl text-xs font-semibold p-button-outlined p-button-danger flex items-center justify-center gap-2 cursor-pointer"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export default ReportDetailsPage;
