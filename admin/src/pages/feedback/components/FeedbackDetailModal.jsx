import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Dialog } from 'primereact/dialog';
import { Button } from 'primereact/button';
import { Dropdown } from 'primereact/dropdown';
import {
  User,
  Mail,
  Calendar,
  Clock,
  CheckCircle2,
  AlertTriangle,
  MessageSquare,
  ShieldCheck,
  Send,
  ExternalLink,
} from 'lucide-react';
import { StatusBadge } from '../../../components/common/StatusBadge';
import { useToast } from '../../../hooks/useToast';
import { feedbackSuggestionsApi } from '../../../api';

export function FeedbackDetailModal({
  visible,
  onHide,
  feedback,
  onUpdateSuccess,
}) {
  const { showSuccess, showError } = useToast();

  const [currentFeedback, setCurrentFeedback] = useState(feedback);
  const [selectedStatus, setSelectedStatus] = useState(feedback?.status || 'new');
  const [adminResponse, setAdminResponse] = useState(feedback?.admin_response || '');
  const [statusLoading, setStatusLoading] = useState(false);
  const [responseLoading, setResponseLoading] = useState(false);
  const [syncLoading, setSyncLoading] = useState(false);

  useEffect(() => {
    if (visible && feedback?.id) {
      setCurrentFeedback(feedback);
      setSelectedStatus(feedback.status || 'new');
      setAdminResponse(feedback.admin_response || '');

      // Fetch fresh details seamlessly
      let isMounted = true;
      setSyncLoading(true);
      feedbackSuggestionsApi
        .getFeedback(feedback.id)
        .then((res) => {
          if (!isMounted) return;
          const fresh = res?.feedback || res?.data;
          if (fresh) {
            setCurrentFeedback(fresh);
            setSelectedStatus(fresh.status || 'new');
            setAdminResponse(fresh.admin_response || '');
          }
        })
        .catch(() => {})
        .finally(() => {
          if (isMounted) setSyncLoading(false);
        });

      return () => {
        isMounted = false;
      };
    }
  }, [visible, feedback?.id]);

  if (!feedback) return null;

  const item = currentFeedback || feedback;
  const member = item.member;
  const admin = item.admin;

  const statusOptions = [
    { label: 'New', value: 'new' },
    { label: 'In Review', value: 'in_review' },
    { label: 'Resolved', value: 'resolved' },
    { label: 'Closed', value: 'closed' },
  ];

  const handleStatusChange = async (newStatus) => {
    if (!newStatus || newStatus === item.status) return;
    setStatusLoading(true);
    try {
      const res = await feedbackSuggestionsApi.updateStatus(item.id, newStatus);
      const updated = res?.feedback || { ...item, status: newStatus };
      setCurrentFeedback(updated);
      setSelectedStatus(newStatus);
      showSuccess(res?.message || `Status updated to ${newStatus}.`);
      if (onUpdateSuccess) onUpdateSuccess(updated);
    } catch (err) {
      showError(err.message || 'Failed to update status.');
      setSelectedStatus(item.status);
    } finally {
      setStatusLoading(false);
    }
  };

  const handleSaveResponse = async () => {
    if (!adminResponse.trim()) {
      showError('Please enter a response before submitting.');
      return;
    }
    setResponseLoading(true);
    try {
      const res = await feedbackSuggestionsApi.updateResponse(item.id, adminResponse.trim(), selectedStatus);
      const updated = res?.feedback || {
        ...item,
        admin_response: adminResponse.trim(),
        status: selectedStatus,
      };
      setCurrentFeedback(updated);
      showSuccess(res?.message || 'Admin response saved successfully.');
      if (onUpdateSuccess) onUpdateSuccess(updated);
    } catch (err) {
      showError(err.message || 'Failed to save admin response.');
    } finally {
      setResponseLoading(false);
    }
  };

  const getTypeBadgeClass = (type) => {
    switch (type) {
      case 'suggestion':
        return 'bg-purple-50 text-purple-700 border-purple-200';
      case 'idea':
        return 'bg-amber-50 text-amber-700 border-amber-200';
      case 'complaint':
        return 'bg-rose-50 text-rose-700 border-rose-200';
      case 'bug_report':
        return 'bg-red-50 text-red-700 border-red-200';
      case 'other':
        return 'bg-slate-50 text-slate-700 border-slate-200';
      default:
        return 'bg-blue-50 text-blue-700 border-blue-200';
    }
  };

  return (
    <Dialog
      visible={visible}
      onHide={onHide}
      header={
        <div className="flex items-center justify-between w-full pr-6">
          <div className="flex items-center space-x-2.5">
            <span className="font-mono text-sm font-bold text-slate-700">
              #{item.id}
            </span>
            <span className={`inline-block text-[10px] font-bold px-2 py-0.5 rounded-full border ${getTypeBadgeClass(item.type)}`}>
              {item.type_label || item.type}
            </span>
            <StatusBadge status={item.status} />
          </div>
          {syncLoading && (
            <span className="text-[11px] text-slate-400 animate-pulse">Syncing...</span>
          )}
        </div>
      }
      className="w-full max-w-2xl"
      modal
      footer={
        <div className="flex items-center justify-end w-full pt-3 border-t border-slate-100">
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
      <div className="space-y-4 pt-2 text-xs">
        {/* 1. Member Information Card */}
        <div className="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80 flex items-center justify-between flex-wrap gap-3">
          <div className="flex items-center space-x-3">
            <div className="w-11 h-11 rounded-full bg-blue-100 text-blue-700 font-bold flex items-center justify-center text-xs overflow-hidden shrink-0 border border-blue-200">
              {member?.avatar_url || member?.profile_photo ? (
                <img
                  src={member.avatar_url || member.profile_photo}
                  alt={member.name || 'Member'}
                  className="w-full h-full object-cover"
                />
              ) : (
                <User className="w-5 h-5 text-blue-600" />
              )}
            </div>
            <div>
              <div className="flex items-center space-x-1.5">
                <span className="font-bold text-slate-900 text-xs">
                  {member?.name || 'Anonymous Member'}
                </span>
                {member?.id && (
                  <Link
                    to={`/admin/members/${member.id}`}
                    className="text-blue-600 hover:text-blue-700"
                    title="View Member Profile"
                  >
                    <ExternalLink className="w-3 h-3" />
                  </Link>
                )}
              </div>
              <div className="text-[11px] text-slate-500 font-mono">
                {member?.user_id ? `@${member.user_id}` : `ID: ${item.member_id}`}
              </div>
              {member?.email && (
                <div className="text-[11px] text-slate-400 flex items-center mt-0.5">
                  <Mail className="w-3 h-3 mr-1 text-slate-400" />
                  {member.email}
                </div>
              )}
            </div>
          </div>

          <div className="text-right text-[11px] text-slate-400 space-y-1">
            <div className="flex items-center justify-end">
              <Calendar className="w-3 h-3 mr-1 text-slate-400" />
              <span>{item.created_at ? new Date(item.created_at).toLocaleDateString() : 'N/A'}</span>
            </div>
            <div className="flex items-center justify-end">
              <Clock className="w-3 h-3 mr-1 text-slate-400" />
              <span>{item.created_at_human || 'Recently'}</span>
            </div>
          </div>
        </div>

        {/* 2. Submission Subject & Message */}
        <div className="space-y-1.5">
          <label className="text-[10px] font-bold uppercase tracking-wider text-slate-400">
            Subject
          </label>
          <div className="p-3 bg-white rounded-xl border border-slate-200 font-bold text-slate-900 text-sm">
            {item.subject}
          </div>
        </div>

        <div className="space-y-1.5">
          <label className="text-[10px] font-bold uppercase tracking-wider text-slate-400">
            Submitted Message
          </label>
          <div className="p-3.5 bg-slate-50/70 rounded-xl border border-slate-200 text-slate-800 leading-relaxed whitespace-pre-line font-medium min-h-[90px]">
            {item.message}
          </div>
        </div>

        {/* 3. Status Management Strip */}
        <div className="p-3 bg-blue-50/60 rounded-xl border border-blue-100 flex items-center justify-between flex-wrap gap-2">
          <div>
            <span className="text-xs font-bold text-blue-900 block">Change Status</span>
            <span className="text-[11px] text-blue-700/80">Update the lifecycle phase of this submission.</span>
          </div>
          <div className="w-40 shrink-0">
            <Dropdown
              value={selectedStatus}
              options={statusOptions}
              onChange={(e) => {
                setSelectedStatus(e.value);
                handleStatusChange(e.value);
              }}
              disabled={statusLoading}
              className="w-full text-xs"
            />
          </div>
        </div>

        {/* 4. Admin Response / Resolution Notes */}
        <div className="space-y-2 pt-2 border-t border-slate-100">
          <div className="flex items-center justify-between">
            <label className="text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center">
              <ShieldCheck className="w-3.5 h-3.5 mr-1 text-emerald-600" />
              Admin Response / Resolution Notes
            </label>
            {item.responded_at && (
              <span className="text-[10px] text-slate-400">
                Responded {item.responded_at_human} {admin?.name ? `by ${admin.name}` : ''}
              </span>
            )}
          </div>

          <textarea
            rows={4}
            value={adminResponse}
            onChange={(e) => setAdminResponse(e.target.value)}
            placeholder="Type administrative response or internal resolution notes here..."
            className="w-full p-3 text-xs bg-white rounded-xl border border-slate-200 text-slate-800 focus:outline-hidden focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
            disabled={responseLoading}
          />

          <div className="flex justify-end">
            <Button
              type="button"
              label={responseLoading ? 'Saving...' : 'Save Admin Response'}
              icon={<Send className="w-3 h-3 mr-1" />}
              size="small"
              onClick={handleSaveResponse}
              disabled={responseLoading || !adminResponse.trim()}
              loading={responseLoading}
              className="p-button-primary text-xs font-semibold"
            />
          </div>
        </div>
      </div>
    </Dialog>
  );
}

export default FeedbackDetailModal;
