import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Eye, Trash2 } from 'lucide-react';
import { StatusBadge } from '../../../../components/common/StatusBadge';
import { EmptyState } from '../../../../components/common/EmptyState';
import { confirmHelper } from '../../../../utils/confirmHelper';
import { eventsApi } from '../../../../api';
import { useToast } from '../../../../hooks/useToast';

export function MemberEventsTab({
  events = [],
  onRefresh,
}) {
  const navigate = useNavigate();
  const { showSuccess, showError } = useToast();
  const [actionLoading, setActionLoading] = useState(false);

  const handleViewEvent = (event) => {
    const eventId = event?.id || event?.event_id;
    if (!eventId) {
      showError('Unable to open event details: Event ID is missing.');
      return;
    }
    navigate(`/admin/events/${eventId}`);
  };

  const handleDeleteEvent = (event) => {
    confirmHelper.confirmDelete({
      header: 'Delete Event',
      message: `Are you sure you want to delete event "${event.title}"?`,
      onAccept: async () => {
        setActionLoading(true);
        try {
          await eventsApi.deleteEvent(event.id);
          showSuccess(`Event removed.`);
          onRefresh();
        } catch (err) {
          showError(err.message || 'Failed to delete event.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  if (events.length === 0) {
    return (
      <EmptyState
        title="No Events Scheduled"
        description="This member has not organized or RSVP'd to platform events."
      />
    );
  }

  return (
    <div className="pt-2">
      <div className="overflow-x-auto border border-slate-200 rounded-xl bg-white">
        <table className="w-full text-left text-xs border-collapse">
          <thead>
            <tr className="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
              <th className="py-3 px-4">Event Title</th>
              <th className="py-3 px-4">Category</th>
              <th className="py-3 px-4">Location</th>
              <th className="py-3 px-4">Event Date & Time</th>
              <th className="py-3 px-4">Status</th>
              <th className="py-3 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {events.map((event, idx) => {
              const locationStr = [event.location_city, event.location_country].filter(Boolean).join(', ') || event.location_address || 'Online';

              return (
                <tr key={event.id || idx} className="hover:bg-slate-50/80 transition-colors">
                  <td className="py-3 px-4 font-bold text-slate-900 whitespace-nowrap">
                    <button
                      type="button"
                      onClick={() => handleViewEvent(event)}
                      className="text-left font-bold text-slate-900 hover:text-blue-600 hover:underline transition-colors"
                      title="View Event Details"
                    >
                      {event.title}
                    </button>
                  </td>
                  <td className="py-3 px-4 whitespace-nowrap">
                    <span className="bg-indigo-50 text-indigo-700 font-semibold px-2 py-0.5 rounded-md text-[10px] uppercase">
                      {event.category || 'General'}
                    </span>
                  </td>
                  <td className="py-3 px-4 text-slate-600 whitespace-nowrap">
                    {locationStr}
                  </td>
                  <td className="py-3 px-4 font-semibold text-blue-600 whitespace-nowrap">
                    {event.start_date ? `${event.start_date} ${event.start_time || ''}` : 'Scheduled'}
                  </td>
                  <td className="py-3 px-4 whitespace-nowrap">
                    <StatusBadge status={event.status || 'published'} />
                  </td>
                  <td className="py-3 px-4 text-right whitespace-nowrap">
                    <div className="inline-flex items-center justify-end gap-1.5 sm:gap-2">
                      <button
                        type="button"
                        onClick={() => handleViewEvent(event)}
                        className="p-1.5 rounded-md text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition-colors inline-flex"
                        title="View Event Details"
                        aria-label="View Event Details"
                      >
                        <Eye className="w-3.5 h-3.5" />
                      </button>

                      <button
                        type="button"
                        onClick={() => handleDeleteEvent(event)}
                        disabled={actionLoading}
                        className="p-1.5 rounded-md text-slate-500 hover:text-red-600 hover:bg-red-50 transition-colors"
                        title="Delete Event"
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

export default MemberEventsTab;
