import { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import {
  Calendar,
  Clock,
  MapPin,
  Video,
  Users,
  CheckCircle,
  XCircle,
  Trash2,
  ArrowLeft,
  ExternalLink,
  User,
  MessageSquare,
  Globe,
  Lock,
  CalendarCheck,
  Heart,
  Tag,
} from 'lucide-react';
import { Button } from 'primereact/button';
import { TabView, TabPanel } from 'primereact/tabview';
import { PageHeader } from '../../components/common/PageHeader';
import { StatusBadge } from '../../components/common/StatusBadge';
import { EmptyState } from '../../components/common/EmptyState';
import { ErrorState } from '../../components/common/ErrorState';
import { LoadingSpinner } from '../../components/common/LoadingSpinner';
import { confirmHelper } from '../../utils/confirmHelper';
import { useToast } from '../../hooks/useToast';
import { eventsApi } from '../../api';

/**
 * Normalized check for active/published event state.
 */
export const isEventActive = (status) =>
  ['active', 'published'].includes((status || '').toLowerCase().trim());

export function EventDetailsPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { showSuccess, showError, showInfo, showWarning } = useToast();

  const [event, setEvent] = useState(null);
  const [goingCount, setGoingCount] = useState(0);
  const [interestedCount, setInterestedCount] = useState(0);
  const [posts, setPosts] = useState([]);
  const [postsCount, setPostsCount] = useState(0);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [error, setError] = useState(null);
  const [activeTab, setActiveTab] = useState(0);

  const fetchEventDetails = useCallback(async () => {
    if (!id) {
      setError('Event ID is missing.');
      setLoading(false);
      return;
    }

    setLoading(true);
    setError(null);
    try {
      const res = await eventsApi.getEvent(id);
      const data = res?.event || res?.data || res;
      setEvent(data);
      setGoingCount(res?.goingCount ?? data?.goingCount ?? 0);
      setInterestedCount(res?.interestedCount ?? data?.interestedCount ?? 0);
      setPosts(res?.posts || data?.posts || []);
      setPostsCount(res?.postsCount ?? (res?.posts ? res.posts.length : 0));
    } catch (err) {
      setError(err.message || 'Failed to load event inspection details.');
    } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => {
    let isMounted = true;
    if (!id) {
      setError('Event ID is missing.');
      setLoading(false);
      return;
    }

    eventsApi
      .getEvent(id)
      .then((res) => {
        if (!isMounted) return;
        const data = res?.event || res?.data || res;
        setEvent(data);
        setGoingCount(res?.goingCount ?? data?.goingCount ?? 0);
        setInterestedCount(res?.interestedCount ?? data?.interestedCount ?? 0);
        setPosts(res?.posts || data?.posts || []);
        setPostsCount(res?.postsCount ?? (res?.posts ? res.posts.length : 0));
      })
      .catch((err) => {
        if (!isMounted) return;
        setError(err.message || 'The requested event could not be found.');
      })
      .finally(() => {
        if (isMounted) setLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [id]);

  const handleToggleStatus = async () => {
    if (!event) return;
    const currentlyActive = isEventActive(event.status);
    const newStatus = currentlyActive ? 'cancelled' : 'active';

    setActionLoading(true);
    try {
      const res = await eventsApi.updateStatus(event.id, newStatus);
      if (!currentlyActive) {
        showSuccess('Event activated successfully.');
      } else {
        showSuccess(res?.message || 'Event marked as cancelled.');
      }
      setEvent((prev) => (prev ? { ...prev, status: newStatus === 'active' ? 'published' : 'cancelled' } : prev));
    } catch (err) {
      if (err.status === 409 || err.code === 'EVENT_ALREADY_ACTIVE' || err.message?.toLowerCase().includes('already active')) {
        showInfo(err.message || 'This event is already active.');
        setEvent((prev) => (prev ? { ...prev, status: 'published' } : prev));
      } else if (err.status === 409 || err.code === 'EVENT_ALREADY_CANCELLED') {
        showInfo(err.message || 'This event is already cancelled.');
        setEvent((prev) => (prev ? { ...prev, status: 'cancelled' } : prev));
      } else {
        showError(err.message || 'Failed to update event status.');
      }
    } finally {
      setActionLoading(false);
    }
  };

  const handleDelete = () => {
    if (!event) return;
    confirmHelper.confirmDelete({
      header: 'Delete Event',
      message: `Are you sure you want to permanently delete event "${event.title}"? All responses and discussion posts will be removed.`,
      onAccept: async () => {
        setActionLoading(true);
        try {
          await eventsApi.deleteEvent(event.id);
          showSuccess(`Event "${event.title}" deleted.`);
          navigate('/admin/events');
        } catch (err) {
          showError(err.message || 'Failed to delete event.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  if (loading) {
    return (
      <div className="py-16">
        <LoadingSpinner message="Loading event details..." />
      </div>
    );
  }

  if (error || !event) {
    return (
      <div className="space-y-6">
        <PageHeader
          title="Event Inspection"
          breadcrumbs={[{ label: 'Events', to: '/admin/events' }, { label: 'Event Inspection' }]}
        />
        <ErrorState
          title="Event Record Not Found"
          message={error || 'The requested event could not be located in the system.'}
          onRetry={fetchEventDetails}
        />
        <div className="flex justify-center pt-2">
          <Link to="/admin/events">
            <Button
              label="Return to Events Directory"
              icon="pi pi-arrow-left"
              size="small"
              className="p-button-outlined p-button-secondary text-xs"
            />
          </Link>
        </div>
      </div>
    );
  }

  const isOnline = event.event_type === 'online' || event.is_online;
  const locationString = [event.location_city, event.location_state, event.location_country]
    .filter(Boolean)
    .join(', ') || event.location_address || (isOnline ? 'Online Webinar' : 'Venue TBA');

  const startDateFormatted = event.start_date
    ? new Date(event.start_date).toLocaleDateString(undefined, {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
      })
    : 'Date TBA';

  const responses = event.responses || [];

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <PageHeader
        title={`Event: ${event.title}`}
        subtitle="Review full event details, attendees, discussions, and manage event publication status."
        breadcrumbs={[
          { label: 'Events', to: '/admin/events' },
          { label: event.title },
        ]}
        actions={
          <div className="flex flex-wrap items-center gap-2">
            <Button
              label={isEventActive(event.status) ? 'Cancel Event' : 'Activate Event'}
              icon={isEventActive(event.status) ? <XCircle className="w-3.5 h-3.5 mr-1.5 text-amber-500" /> : <CheckCircle className="w-3.5 h-3.5 mr-1.5 text-emerald-600" />}
              size="small"
              onClick={handleToggleStatus}
              loading={actionLoading}
              className={isEventActive(event.status) ? 'p-button-outlined p-button-warning text-xs' : 'p-button-success text-xs'}
            />

            <Button
              label="Delete"
              icon={<Trash2 className="w-3.5 h-3.5 mr-1.5 text-red-500" />}
              size="small"
              onClick={handleDelete}
              loading={actionLoading}
              className="p-button-outlined p-button-danger text-xs"
            />

            <Link to="/admin/events" className="inline-flex">
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

      {/* Main Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left 2 Columns: Main Details, Description & Tabs */}
        <div className="lg:col-span-2 space-y-6">
          {/* Main Card */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-6 space-y-5">
            {/* Header info */}
            <div className="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-4">
              <div>
                <div className="flex items-center gap-2 flex-wrap">
                  <h2 className="text-xl font-bold text-slate-900">{event.title}</h2>
                  <span className="text-xs font-semibold text-indigo-700 bg-indigo-50 px-2.5 py-0.5 rounded-full border border-indigo-100 uppercase">
                    {event.category || 'General'}
                  </span>
                  <StatusBadge status={event.status || 'active'} />
                </div>

                {event.organizer && (
                  <p className="text-xs text-slate-500 mt-1.5 flex items-center gap-1.5">
                    <User className="w-3.5 h-3.5 text-slate-400" />
                    <span>Organized by</span>
                    <Link
                      to={`/admin/members/${event.organizer.id}`}
                      className="font-semibold text-blue-600 hover:underline inline-flex items-center gap-1"
                    >
                      {event.organizer.name} ({event.organizer.user_id})
                    </Link>
                  </p>
                )}
              </div>

              {/* Event format badge */}
              <div>
                {isOnline ? (
                  <span className="inline-flex items-center text-xs font-bold text-purple-700 bg-purple-50 px-3 py-1 rounded-lg border border-purple-200">
                    <Video className="w-3.5 h-3.5 mr-1.5" /> Online Webinar
                  </span>
                ) : (
                  <span className="inline-flex items-center text-xs font-bold text-blue-700 bg-blue-50 px-3 py-1 rounded-lg border border-blue-200">
                    <MapPin className="w-3.5 h-3.5 mr-1.5" /> In-Person Gathering
                  </span>
                )}
              </div>
            </div>

            {/* Quick Metrics Strip */}
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <div className="p-3 bg-slate-50 rounded-xl border border-slate-100">
                <span className="text-[11px] font-semibold text-slate-400 block uppercase">Date</span>
                <span className="text-xs font-bold text-slate-800 mt-0.5 block">{startDateFormatted}</span>
                <span className="text-[10px] text-slate-500 block">{event.start_time || 'All Day'}</span>
              </div>

              <div className="p-3 bg-emerald-50/70 rounded-xl border border-emerald-100">
                <span className="text-[11px] font-semibold text-emerald-700 block uppercase">Going</span>
                <span className="text-base font-bold text-emerald-800 mt-0.5 block">{goingCount}</span>
                <span className="text-[10px] text-emerald-600 block">Confirmed RSVPs</span>
              </div>

              <div className="p-3 bg-indigo-50/70 rounded-xl border border-indigo-100">
                <span className="text-[11px] font-semibold text-indigo-700 block uppercase">Interested</span>
                <span className="text-base font-bold text-indigo-800 mt-0.5 block">{interestedCount}</span>
                <span className="text-[10px] text-indigo-600 block">Saved / Interested</span>
              </div>

              <div className="p-3 bg-purple-50/70 rounded-xl border border-purple-100">
                <span className="text-[11px] font-semibold text-purple-700 block uppercase">Discussions</span>
                <span className="text-base font-bold text-purple-800 mt-0.5 block">{postsCount}</span>
                <span className="text-[10px] text-purple-600 block">Community Posts</span>
              </div>
            </div>

            {/* Cover photo or Banner if available */}
            {(event.cover_photo_url || event.banner || event.cover_photo) && (
              <div className="rounded-xl overflow-hidden border border-slate-200 bg-slate-50">
                <img
                  src={event.cover_photo_url || (event.cover_photo?.startsWith('http') ? event.cover_photo : `/${event.cover_photo}`)}
                  alt={event.title}
                  className="w-full max-h-64 object-cover"
                  onError={(e) => {
                    e.currentTarget.style.display = 'none';
                  }}
                />
              </div>
            )}

            {/* Event Description */}
            <div className="space-y-2">
              <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider">About This Event</h4>
              {event.short_description && (
                <p className="text-xs font-semibold text-slate-600 italic">
                  {event.short_description}
                </p>
              )}
              {event.description ? (
                <div className="text-xs text-slate-700 whitespace-pre-line leading-relaxed bg-slate-50/60 p-4 rounded-xl border border-slate-100">
                  {event.description}
                </div>
              ) : (
                <p className="text-xs text-slate-400 italic">No full description provided for this event.</p>
              )}
            </div>
          </div>

          {/* Tabbed details: Attendees / RSVPs and Discussion Posts */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5">
            <TabView activeIndex={activeTab} onTabChange={(e) => setActiveTab(e.index)}>
              <TabPanel header={`RSVPs & Attendees (${responses.length})`}>
                {responses.length === 0 ? (
                  <div className="py-8">
                    <EmptyState
                      icon={Users}
                      title="No RSVPs Yet"
                      description="No community members have confirmed attendance or expressed interest in this event yet."
                    />
                  </div>
                ) : (
                  <div className="overflow-x-auto">
                    <table className="w-full text-left text-xs text-slate-700">
                      <thead className="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase border-b border-slate-200">
                        <tr>
                          <th className="py-2.5 px-3">Member</th>
                          <th className="py-2.5 px-3">Response</th>
                          <th className="py-2.5 px-3">Contact</th>
                          <th className="py-2.5 px-3 text-right">Action</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100">
                        {responses.map((resp) => {
                          const m = resp.member;
                          return (
                            <tr key={resp.id} className="hover:bg-slate-50/60">
                              <td className="py-2.5 px-3">
                                {m ? (
                                  <div className="flex items-center space-x-2">
                                    <div className="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center font-bold text-[11px] text-slate-600">
                                      {m.name ? m.name.charAt(0).toUpperCase() : 'M'}
                                    </div>
                                    <div>
                                      <span className="font-semibold text-slate-900 block">{m.name}</span>
                                      <code className="text-[10px] font-mono text-slate-400">{m.user_id}</code>
                                    </div>
                                  </div>
                                ) : (
                                  <span className="text-slate-400 italic">Member #{resp.member_id}</span>
                                )}
                              </td>
                              <td className="py-2.5 px-3">
                                <span
                                  className={`text-[10px] font-bold px-2 py-0.5 rounded-full uppercase ${
                                    resp.response === 'going'
                                      ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                      : 'bg-indigo-50 text-indigo-700 border border-indigo-200'
                                  }`}
                                >
                                  {resp.response || 'Interested'}
                                </span>
                              </td>
                              <td className="py-2.5 px-3 text-slate-500">
                                {m?.email || 'N/A'}
                              </td>
                              <td className="py-2.5 px-3 text-right">
                                {m?.id && (
                                  <Link
                                    to={`/admin/members/${m.id}`}
                                    className="text-xs text-blue-600 hover:underline font-medium"
                                  >
                                    View Member
                                  </Link>
                                )}
                              </td>
                            </tr>
                          );
                        })}
                      </tbody>
                    </table>
                  </div>
                )}
              </TabPanel>

              <TabPanel header={`Discussion Posts (${posts.length})`}>
                {posts.length === 0 ? (
                  <div className="py-8">
                    <EmptyState
                      icon={MessageSquare}
                      title="No Discussion Posts"
                      description="No posts have been shared on this event wall."
                    />
                  </div>
                ) : (
                  <div className="space-y-3">
                    {posts.map((p) => (
                      <div key={p.id} className="p-3.5 bg-slate-50/70 border border-slate-100 rounded-xl space-y-2">
                        <div className="flex items-center justify-between text-xs">
                          <div className="flex items-center space-x-2">
                            <span className="font-semibold text-slate-900">{p.member?.name || 'Member'}</span>
                            <span className="text-slate-400 text-[11px]">• {new Date(p.created_at).toLocaleDateString()}</span>
                          </div>
                          <Link
                            to={`/admin/posts/${p.id}`}
                            className="text-xs text-blue-600 hover:underline font-medium inline-flex items-center gap-1"
                          >
                            <span>Inspect Post #{p.id}</span>
                            <ExternalLink className="w-3 h-3" />
                          </Link>
                        </div>
                        {p.body && <p className="text-xs text-slate-700">{p.body}</p>}
                        <div className="flex items-center gap-3 text-[11px] text-slate-500 pt-1">
                          <span>{p.likes_count ?? 0} likes</span>
                          <span>{p.comments_count ?? 0} comments</span>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </TabPanel>
            </TabView>
          </div>
        </div>

        {/* Right Sidebar Column: Metadata & Links */}
        <div className="space-y-6">
          {/* Status & Schedule Card */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 space-y-4">
            <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
              <Calendar className="w-4 h-4 text-blue-600" />
              <span>Schedule & Status</span>
            </h4>

            <div className="space-y-3 text-xs divide-y divide-slate-100">
              <div className="pt-2 flex justify-between items-center">
                <span className="text-slate-500 font-medium">Status</span>
                <StatusBadge status={event.status || 'active'} />
              </div>

              <div className="pt-2 flex justify-between items-center">
                <span className="text-slate-500 font-medium">Privacy</span>
                <span className="font-semibold text-slate-800 capitalize flex items-center gap-1">
                  {event.privacy === 'private' ? <Lock className="w-3 h-3 text-amber-500" /> : <Globe className="w-3 h-3 text-slate-400" />}
                  <span>{event.privacy || 'Public'}</span>
                </span>
              </div>

              <div className="pt-2 flex justify-between items-center">
                <span className="text-slate-500 font-medium">Start Date</span>
                <span className="font-semibold text-slate-800">{event.start_date || 'N/A'}</span>
              </div>

              {event.end_date && (
                <div className="pt-2 flex justify-between items-center">
                  <span className="text-slate-500 font-medium">End Date</span>
                  <span className="font-semibold text-slate-800">{event.end_date}</span>
                </div>
              )}

              <div className="pt-2 flex justify-between items-center">
                <span className="text-slate-500 font-medium">Time</span>
                <span className="font-semibold text-slate-800">
                  {event.start_time || 'TBA'} {event.end_time ? `- ${event.end_time}` : ''}
                </span>
              </div>

              {event.timezone && (
                <div className="pt-2 flex justify-between items-center">
                  <span className="text-slate-500 font-medium">Timezone</span>
                  <span className="font-semibold text-slate-800 font-mono text-[11px]">{event.timezone}</span>
                </div>
              )}

              {event.max_guests && (
                <div className="pt-2 flex justify-between items-center">
                  <span className="text-slate-500 font-medium">Capacity</span>
                  <span className="font-semibold text-slate-800">{event.max_guests} attendees max</span>
                </div>
              )}
            </div>
          </div>

          {/* Location & Connection Card */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 space-y-4">
            <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
              {isOnline ? <Video className="w-4 h-4 text-purple-600" /> : <MapPin className="w-4 h-4 text-blue-600" />}
              <span>{isOnline ? 'Online Connection' : 'Venue & Location'}</span>
            </h4>

            {isOnline ? (
              <div className="space-y-2.5 text-xs">
                {event.meeting_link ? (
                  <div>
                    <span className="text-slate-500 block font-medium">Meeting URL:</span>
                    <a
                      href={event.meeting_link}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-blue-600 hover:underline break-all inline-flex items-center gap-1 mt-0.5 font-semibold"
                    >
                      <span>{event.meeting_link}</span>
                      <ExternalLink className="w-3 h-3 shrink-0" />
                    </a>
                  </div>
                ) : (
                  <p className="text-slate-400 italic">No webinar or meeting URL specified.</p>
                )}

                {event.meeting_password && (
                  <div className="pt-1">
                    <span className="text-slate-500 block font-medium">Passcode:</span>
                    <code className="bg-slate-100 text-slate-800 px-2 py-0.5 rounded font-mono text-[11px] inline-block mt-0.5">
                      {event.meeting_password}
                    </code>
                  </div>
                )}
              </div>
            ) : (
              <div className="space-y-2 text-xs">
                <div>
                  <span className="text-slate-500 block font-medium">Address:</span>
                  <p className="font-semibold text-slate-800 mt-0.5">{locationString}</p>
                </div>

                {event.google_maps_link && (
                  <div className="pt-1">
                    <a
                      href={event.google_maps_link}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-blue-600 hover:underline inline-flex items-center gap-1 text-xs font-medium"
                    >
                      <MapPin className="w-3.5 h-3.5" />
                      <span>Open in Google Maps</span>
                      <ExternalLink className="w-3 h-3" />
                    </a>
                  </div>
                )}
              </div>
            )}
          </div>

          {/* Organizer Card */}
          {event.organizer && (
            <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 space-y-3">
              <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                <User className="w-4 h-4 text-slate-600" />
                <span>Event Organizer</span>
              </h4>

              <div className="p-3 bg-slate-50 rounded-xl border border-slate-100 flex items-center space-x-3">
                <div className="w-10 h-10 rounded-full bg-blue-100 text-blue-700 font-bold flex items-center justify-center text-sm">
                  {event.organizer.name ? event.organizer.name.charAt(0).toUpperCase() : 'U'}
                </div>
                <div className="min-w-0 flex-1">
                  <span className="font-bold text-slate-900 text-xs block truncate">{event.organizer.name}</span>
                  <span className="text-[11px] text-slate-500 block truncate">{event.organizer.email}</span>
                  <code className="text-[10px] text-blue-600 font-mono block mt-0.5">{event.organizer.user_id}</code>
                </div>
              </div>

              <Link to={`/admin/members/${event.organizer.id}`} className="block">
                <Button
                  label="View Organizer Profile"
                  icon="pi pi-user"
                  size="small"
                  className="p-button-outlined p-button-primary text-xs w-full"
                />
              </Link>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

export default EventDetailsPage;
