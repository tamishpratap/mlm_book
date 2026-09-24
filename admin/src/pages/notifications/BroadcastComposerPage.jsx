import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { Send, ArrowLeft, CheckCircle2, Info } from 'lucide-react';
import { Button } from 'primereact/button';
import { InputText } from 'primereact/inputtext';
import { InputTextarea } from 'primereact/inputtextarea';
import { Dropdown } from 'primereact/dropdown';
import { PageHeader } from '../../components/common/PageHeader';
import { confirmHelper } from '../../utils/confirmHelper';
import { useToast } from '../../hooks/useToast';
import { notificationsApi } from '../../api';

export function BroadcastComposerPage() {
  const navigate = useNavigate();
  const { showSuccess, showError } = useToast();

  const [audience, setAudience] = useState('all');
  const [title, setTitle] = useState('');
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);

  const audienceOptions = [
    { label: 'All Platform Members', value: 'all' },
    { label: 'Active Members Only', value: 'active' },
    { label: 'Business Page Owners', value: 'business_owners' },
    { label: 'Marketplace Sellers', value: 'sellers' },
  ];

  const handleSubmit = (e) => {
    e.preventDefault();

    confirmHelper.confirm({
      header: 'Dispatch Broadcast Announcement',
      message: `Are you sure you want to dispatch this broadcast announcement to "${audienceOptions.find((o) => o.value === audience)?.label}"?`,
      onAccept: async () => {
        setLoading(true);
        try {
          await notificationsApi.sendBroadcast({
            audience,
            title: title.trim(),
            message: message.trim(),
          });
          showSuccess('Broadcast announcement dispatched successfully.');
          navigate('/admin/notifications');
        } catch (err) {
          showError(err.message || 'Failed to dispatch broadcast announcement.');
        } finally {
          setLoading(false);
        }
      },
    });
  };

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <PageHeader
        title="Send Broadcast Announcement"
        subtitle="Dispatch system notifications to target member segments."
        breadcrumbs={[
          { label: 'Notifications', to: '/admin/notifications' },
          { label: 'Broadcast Composer' },
        ]}
      />

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left 2 Cols: Broadcast Form */}
        <div className="lg:col-span-2 space-y-6">
          <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-6">
            <h4 className="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 mb-4">
              Broadcast Announcement Form
            </h4>

            <form onSubmit={handleSubmit} className="space-y-4">
              {/* Audience */}
              <div>
                <label className="text-xs font-bold text-slate-700 block mb-1.5">
                  Target Audience Segment <span className="text-red-500">*</span>
                </label>
                <Dropdown
                  value={audience}
                  options={audienceOptions}
                  onChange={(e) => setAudience(e.value)}
                  placeholder="Select Target Audience"
                  className="w-full text-xs"
                  disabled={loading}
                />
              </div>

              {/* Title */}
              <div>
                <label className="text-xs font-bold text-slate-700 block mb-1.5">
                  Announcement Title <span className="text-red-500">*</span>
                </label>
                <InputText
                  value={title}
                  onChange={(e) => setTitle(e.target.value)}
                  placeholder="e.g. Platform Maintenance Notice / New Feature Release"
                  className="w-full text-xs"
                  required
                  disabled={loading}
                />
              </div>

              {/* Message */}
              <div>
                <label className="text-xs font-bold text-slate-700 block mb-1.5">
                  Announcement Body Message <span className="text-red-500">*</span>
                </label>
                <InputTextarea
                  value={message}
                  onChange={(e) => setMessage(e.target.value)}
                  placeholder="Type the message content to be delivered to members..."
                  rows={5}
                  className="w-full text-xs"
                  required
                  disabled={loading}
                />
              </div>

              {/* Actions */}
              <div className="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-slate-100">
                <Link to="/admin/notifications" className="inline-flex">
                  <Button
                    type="button"
                    label="Cancel"
                    icon={<ArrowLeft className="w-3.5 h-3.5 mr-1.5" />}
                    size="small"
                    disabled={loading}
                    className="p-button-outlined p-button-secondary text-xs"
                  />
                </Link>

                <Button
                  type="submit"
                  label="Dispatch Broadcast Announcement"
                  icon={<Send className="w-3.5 h-3.5 mr-1.5" />}
                  size="small"
                  loading={loading}
                  className="p-button-primary text-xs"
                />
              </div>
            </form>
          </div>
        </div>

        {/* Right Col: Delivery Rules Card */}
        <div className="space-y-6">
          <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 space-y-3">
            <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-2">
              Broadcast Delivery Rules
            </h4>
            <ul className="space-y-2.5 text-xs text-slate-600">
              <li className="flex items-start">
                <CheckCircle2 className="w-4 h-4 text-emerald-600 mr-2 shrink-0 mt-0.5" />
                <span>Broadcast announcements are delivered as database notifications to members&apos; in-app notification inbox.</span>
              </li>
              <li className="flex items-start">
                <CheckCircle2 className="w-4 h-4 text-emerald-600 mr-2 shrink-0 mt-0.5" />
                <span>All broadcast dispatches are logged and trackable in the Notification Queue.</span>
              </li>
              <li className="flex items-start">
                <Info className="w-4 h-4 text-sky-600 mr-2 shrink-0 mt-0.5" />
                <span>Target segment filtering prevents unwanted mass spamming.</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  );
}

export default BroadcastComposerPage;
