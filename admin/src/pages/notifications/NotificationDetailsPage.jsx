import { useState, useEffect, useCallback } from 'react';
import { useParams, Link } from 'react-router-dom';
import { ArrowLeft, CheckCircle2, User, Clock, Code2 } from 'lucide-react';
import { Button } from 'primereact/button';
import { PageHeader } from '../../components/common/PageHeader';
import { StatusBadge } from '../../components/common/StatusBadge';
import { ErrorState } from '../../components/common/ErrorState';
import { NotificationDetailsSkeleton } from './components/NotificationDetailsSkeleton';
import { useToast } from '../../hooks/useToast';
import { notificationsApi } from '../../api';

export function NotificationDetailsPage() {
  const { id } = useParams();
  const { showSuccess, showError } = useToast();

  const [notification, setNotification] = useState(null);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [error, setError] = useState(null);

  const fetchNotificationDetails = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await notificationsApi.getNotification(id);
      const data = res?.notification || res?.data || res;
      setNotification(data);
    } catch (err) {
      setError(err.message || 'Failed to load notification details.');
    } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => {
    let isMounted = true;
    notificationsApi.getNotification(id)
      .then((res) => {
        if (!isMounted) return;
        setNotification(res?.notification || res?.data || res);
      })
      .catch((err) => {
        if (!isMounted) return;
        setError(err.message || 'Failed to load notification details.');
      })
      .finally(() => {
        if (isMounted) setLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [id]);

  const handleMarkRead = async () => {
    setActionLoading(true);
    try {
      await notificationsApi.markRead(id);
      showSuccess('Notification marked as read.');
      fetchNotificationDetails();
    } catch (err) {
      showError(err.message || 'Failed to mark notification as read.');
    } finally {
      setActionLoading(false);
    }
  };

  if (loading) {
    return <NotificationDetailsSkeleton />;
  }

  if (error || !notification) {
    return (
      <div className="space-y-6">
        <PageHeader
          title="Notification Inspection"
          breadcrumbs={[{ label: 'Notifications', to: '/admin/notifications' }, { label: 'Inspection' }]}
        />
        <ErrorState
          title="Notification Record Not Found"
          message={error || 'The requested notification record could not be located.'}
          onRetry={fetchNotificationDetails}
        />
      </div>
    );
  }

  const shortId = typeof notification.id === 'string' && notification.id.length > 8 ? notification.id.substring(0, 8) : notification.id;

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <PageHeader
        title={`Notification Inspection: #${shortId}`}
        subtitle="Read-only notification details, recipient info, and raw payload data."
        breadcrumbs={[
          { label: 'Notifications Queue', to: '/admin/notifications' },
          { label: `Notification #${shortId}` },
        ]}
        actions={
          <div className="flex items-center space-x-2">
            {!notification.is_read && (
              <Button
                label="Mark Read"
                icon={<CheckCircle2 className="w-3.5 h-3.5 mr-1.5" />}
                size="small"
                onClick={handleMarkRead}
                loading={actionLoading}
                className="p-button-success text-xs"
              />
            )}
            <Link to="/admin/notifications">
              <Button
                label="Back to Queue"
                icon={<ArrowLeft className="w-3.5 h-3.5 mr-1.5" />}
                size="small"
                className="p-button-outlined p-button-secondary text-xs"
              />
            </Link>
          </div>
        }
      />

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Left Col: Notification Details & Recipient */}
        <div className="space-y-6">
          <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-6 space-y-4">
            <div className="flex items-center justify-between border-b border-slate-100 pb-3">
              <div className="flex items-center space-x-2">
                <span className="inline-block text-xs font-bold px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                  {notification.channel}
                </span>
                <span className="inline-block text-xs font-semibold px-2.5 py-0.5 rounded-full bg-sky-50 text-sky-700 border border-sky-200">
                  {notification.type}
                </span>
              </div>
              <StatusBadge status={notification.is_read ? 'read' : 'unread'} />
            </div>

            <div className="p-4 bg-slate-50 rounded-xl border border-slate-100 space-y-2">
              <h5 className="font-bold text-slate-900 text-sm">{notification.title}</h5>
              <p className="text-xs text-slate-600 leading-relaxed">{notification.message}</p>
              <div className="text-[11px] text-slate-400 pt-2 flex items-center space-x-2">
                <Clock className="w-3 h-3 text-slate-400" />
                <span>Sent: {notification.created_at_human || 'Recently'}</span>
                {notification.read_at && (
                  <>
                    <span>•</span>
                    <span>Read: {notification.read_at_human || 'Read'}</span>
                  </>
                )}
              </div>
            </div>

            {/* Recipient Information */}
            <div className="space-y-3 pt-2">
              <h6 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
                Recipient Member
              </h6>

              {notification.recipient ? (
                <div className="flex items-center justify-between p-3.5 border border-slate-200 rounded-xl bg-white">
                  <div className="flex items-center space-x-3">
                    <div className="w-10 h-10 rounded-full bg-slate-200 overflow-hidden shrink-0">
                      {notification.recipient.avatar_url ? (
                        <img src={notification.recipient.avatar_url} alt={notification.recipient.name} className="w-full h-full object-cover" />
                      ) : (
                        <div className="w-full h-full flex items-center justify-center font-bold text-slate-600 text-xs">
                          {(notification.recipient.name || 'M').charAt(0)}
                        </div>
                      )}
                    </div>
                    <div>
                      <strong className="text-xs text-slate-900 block">{notification.recipient.name}</strong>
                      <code className="text-[11px] font-mono text-blue-600">{notification.recipient.user_id}</code>
                      <span className="text-[11px] text-slate-400 block">{notification.recipient.email}</span>
                    </div>
                  </div>

                  <Link to={`/admin/members/${notification.recipient.id}`}>
                    <Button
                      label="View Profile"
                      icon={<User className="w-3.5 h-3.5 mr-1" />}
                      size="small"
                      className="p-button-outlined p-button-primary text-xs"
                    />
                  </Link>
                </div>
              ) : (
                <div className="p-4 text-center text-xs text-slate-400 bg-slate-50 rounded-xl">
                  Recipient profile not found or deleted.
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Right Col: Raw JSON Data Payload */}
        <div className="space-y-6">
          <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-6 space-y-3">
            <div className="flex items-center space-x-2 border-b border-slate-100 pb-2">
              <Code2 className="w-4 h-4 text-blue-600" />
              <h6 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
                JSON Data Payload
              </h6>
            </div>

            <pre className="bg-slate-900 text-emerald-400 p-4 rounded-xl text-xs font-mono overflow-auto max-h-96 leading-relaxed shadow-inner">
              <code>{JSON.stringify(notification.raw_data || {}, null, 2)}</code>
            </pre>
          </div>
        </div>
      </div>
    </div>
  );
}

export default NotificationDetailsPage;
