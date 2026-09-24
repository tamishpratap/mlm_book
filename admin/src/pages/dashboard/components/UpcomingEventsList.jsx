import { Link } from 'react-router-dom';
import { Calendar, ArrowRight } from 'lucide-react';

export function UpcomingEventsList({ events = [] }) {
  const hasEvents = events && events.length > 0;

  return (
    <div className="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden flex flex-col justify-between">
      {/* Header */}
      <div className="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center">
          <Calendar className="w-4 h-4 text-purple-600 mr-2" /> Upcoming Platform Events
        </h4>
        <Link
          to="/admin/events"
          className="text-xs font-semibold text-blue-600 hover:text-blue-700 inline-flex items-center"
        >
          All Events <ArrowRight className="w-3.5 h-3.5 ml-1" />
        </Link>
      </div>

      {/* Body */}
      <div className="p-4 flex-1 flex flex-col justify-center">
        {!hasEvents ? (
          <div className="py-6 text-center text-xs text-slate-400">
            No upcoming events scheduled at this moment.
          </div>
        ) : (
          <div className="space-y-2.5">
            {events.map((evt, idx) => {
              const formattedDate = evt.formatted_date || evt.date || 'Upcoming';
              const eventId = evt.id || evt.event_id;

              return (
                <div
                  key={evt.id || idx}
                  className="p-3 bg-slate-50/80 rounded-lg border border-slate-100 flex items-center justify-between hover:bg-slate-100/60 transition-colors"
                >
                  <div className="min-w-0 pr-3">
                    {eventId ? (
                      <Link
                        to={`/admin/events/${eventId}`}
                        className="text-xs font-bold text-slate-800 block truncate hover:text-blue-600 transition-colors"
                        title="View Event Inspection"
                      >
                        {evt.title || 'Platform Webinar'}
                      </Link>
                    ) : (
                      <strong className="text-xs font-bold text-slate-800 block truncate">
                        {evt.title || 'Platform Webinar'}
                      </strong>
                    )}
                    <span className="text-[11px] text-slate-500 block truncate mt-0.5">
                      {evt.location_city || evt.location_address || 'Online Webinar'} • {evt.start_time || 'All Day'}
                    </span>
                  </div>

                  <span className="bg-blue-600 text-white text-[11px] font-bold px-2.5 py-1 rounded-md shrink-0 shadow-2xs">
                    {formattedDate}
                  </span>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}

export default UpcomingEventsList;
