import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Calendar,
  Search,
  Download,
  Trash2,
  Users,
  MapPin,
  Video,
  CheckCircle,
  XCircle,
  Clock,
  Eye,
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
import { eventsApi, downloadBlobFromResponse } from '../../api';

/**
 * Normalized check for active/published event state.
 */
export const isEventActive = (status) =>
  ['active', 'published'].includes((status || '').toLowerCase().trim());

export function EventsListPage() {
  const navigate = useNavigate();
  const { showSuccess, showError, showInfo, showWarning } = useToast();

  const handleViewEvent = (eventItem) => {
    const eventId = eventItem?.id || eventItem?.event_id;
    if (!eventId) {
      showError('Unable to open event details: Missing event ID.');
      return;
    }
    navigate(`/admin/events/${eventId}`);
  };

  const [events, setEvents] = useState([]);
  const [stats, setStats] = useState({
    totalCount: 0,
    upcomingCount: 0,
    pastCount: 0,
    thisMonthCount: 0,
  });

  const [search, setSearch] = useState('');
  const [timeframe, setTimeframe] = useState('');
  const [eventType, setEventType] = useState('');
  const [status, setStatus] = useState('');
  const [pagination, setPagination] = useState({ page: 1, perPage: 15, total: 0 });
  const [selectedIds, setSelectedIds] = useState([]);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [exporting, setExporting] = useState(false);
  const [error, setError] = useState(null);

  const fetchEvents = (page = 1) => {
    setLoading(true);
    setError(null);
    const params = {
      page,
      q: search || undefined,
      timeframe: timeframe || undefined,
      event_type: eventType || undefined,
      status: status || undefined,
    };
    eventsApi
      .getEvents(params)
      .then((res) => {
        const items = res?.events?.data || res?.data || (Array.isArray(res) ? res : []);
        const paginator = res?.events || {};

        setEvents(items);
        setStats({
          totalCount: res?.totalCount ?? paginator?.total ?? items.length,
          upcomingCount: res?.upcomingCount ?? 0,
          pastCount: res?.pastCount ?? 0,
          thisMonthCount: res?.thisMonthCount ?? 0,
        });
        setPagination({
          page: paginator?.current_page || page,
          perPage: paginator?.per_page || 15,
          total: paginator?.total || items.length,
        });
        setSelectedIds([]);
      })
      .catch((err) => {
        setError(err.message || 'Failed to load events.');
      })
      .finally(() => {
        setLoading(false);
      });
  };

  useEffect(() => {
    let isMounted = true;
    eventsApi
      .getEvents({ page: 1 })
      .then((res) => {
        if (!isMounted) return;
        const items = res?.events?.data || res?.data || (Array.isArray(res) ? res : []);
        const paginator = res?.events || {};

        setEvents(items);
        setStats({
          totalCount: res?.totalCount ?? paginator?.total ?? items.length,
          upcomingCount: res?.upcomingCount ?? 0,
          pastCount: res?.pastCount ?? 0,
          thisMonthCount: res?.thisMonthCount ?? 0,
        });
        setPagination({
          page: paginator?.current_page || 1,
          perPage: paginator?.per_page || 15,
          total: paginator?.total || items.length,
        });
      })
      .catch((err) => {
        if (!isMounted) return;
        setError(err.message || 'Failed to load events.');
      })
      .finally(() => {
        if (isMounted) setLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, []);

  const handleStatusChange = async (eventItem, newStatus) => {
    const isTargetActive = ['active', 'published'].includes((newStatus || '').toLowerCase());
    const currentlyActive = isEventActive(eventItem.status);

    // RULE 1 & 2: Pre-request state check
    if (isTargetActive && currentlyActive) {
      showInfo('This event is already active.');
      return;
    }

    if (!isTargetActive && !currentlyActive && newStatus === 'cancelled') {
      showInfo('This event is already cancelled.');
      return;
    }

    setActionLoading(true);
    try {
      const res = await eventsApi.updateStatus(eventItem.id, newStatus);
      if (isTargetActive) {
        showSuccess('Event activated successfully.');
      } else {
        showSuccess(res?.message || `Event status changed to ${newStatus}.`);
      }
      fetchEvents(pagination.page);
    } catch (err) {
      if (err.status === 409 || err.code === 'EVENT_ALREADY_ACTIVE' || err.message?.toLowerCase().includes('already active')) {
        showInfo(err.message || 'This event is already active.');
      } else if (err.status === 409 || err.code === 'EVENT_ALREADY_CANCELLED') {
        showInfo(err.message || 'This event is already cancelled.');
      } else {
        showError(err.message || 'Failed to update status.');
      }
    } finally {
      setActionLoading(false);
    }
  };

  const handleDelete = (eventItem) => {
    confirmHelper.confirm({
      header: 'Delete Event',
      message: `Are you sure you want to permanently delete event "${eventItem.title}"?`,
      icon: 'pi pi-trash text-red-500',
      acceptClassName: 'p-button-danger text-xs',
      onAccept: async () => {
        setActionLoading(true);
        try {
          await eventsApi.deleteEvent(eventItem.id);
          showSuccess('Event deleted successfully.');
          fetchEvents(pagination.page);
        } catch (err) {
          showError(err.message || 'Failed to delete event.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  const handleBulkAction = async (action) => {
    if (!selectedIds.length) return;

    if (action === 'activate') {
      const selectedEvents = events.filter((e) => selectedIds.includes(e.id));
      const alreadyActive = selectedEvents.filter((e) => isEventActive(e.status));
      const inactive = selectedEvents.filter((e) => !isEventActive(e.status));

      // RULE 5 & 12: If ALL selected events are already active, do not call backend and inform user
      if (alreadyActive.length === selectedEvents.length && selectedEvents.length > 0) {
        showInfo('All selected events are already active.');
        return;
      }

      // If SOME are already active, clarify in confirmation
      const confirmMessage = alreadyActive.length > 0
        ? `${alreadyActive.length} of ${selectedEvents.length} selected events are already active. Activate the remaining ${inactive.length} event(s)?`
        : `Are you sure you want to activate ${selectedIds.length} event(s)?`;

      confirmHelper.confirm({
        header: 'Activate Selected Events',
        message: confirmMessage,
        icon: 'pi pi-check-circle text-emerald-500',
        acceptLabel: 'Activate',
        acceptClassName: 'p-button-success text-xs',
        onAccept: async () => {
          setActionLoading(true);
          try {
            const res = await eventsApi.bulkAction('activate', selectedIds);
            showSuccess(res?.message || 'Event(s) activated successfully.');
            fetchEvents(pagination.page);
          } catch (err) {
            if (err.status === 409 || err.code === 'ALL_EVENTS_ALREADY_ACTIVE' || err.message?.toLowerCase().includes('already active')) {
              showInfo(err.message || 'All selected events are already active.');
            } else {
              showError(err.message || 'Failed to activate events.');
            }
          } finally {
            setActionLoading(false);
          }
        },
      });
      return;
    }

    confirmHelper.confirm({
      header: 'Bulk Action',
      message: `Are you sure you want to apply "${action}" to ${selectedIds.length} event(s)?`,
      icon: 'pi pi-exclamation-triangle text-amber-500',
      acceptClassName: action === 'delete' ? 'p-button-danger text-xs' : 'p-button-primary text-xs',
      onAccept: async () => {
        setActionLoading(true);
        try {
          const res = await eventsApi.bulkAction(action, selectedIds);
          showSuccess(res?.message || 'Bulk action applied.');
          fetchEvents(pagination.page);
        } catch (err) {
          if (err.status === 409) {
            showInfo(err.message || 'Selected events already match this status.');
          } else {
            showError(err.message || `Failed to execute bulk action.`);
          }
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
      const filterParams = {
        q: search || undefined,
        timeframe: timeframe || undefined,
        event_type: eventType || undefined,
        status: status || undefined,
      };
      const blob = await eventsApi.exportCsv(filterParams);
      await downloadBlobFromResponse(blob, 'events_export.csv');
      showSuccess('Events CSV downloaded.');
    } catch (err) {
      showError(err.message || 'Failed to export CSV.');
    } finally {
      setExporting(false);
    }
  };

  const toggleSelectAll = (checked) => {
    if (checked) {
      setSelectedIds(events.map((e) => e.id));
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
      {/* Page Header */}
      <PageHeader
        title="Events Directory"
        subtitle="Manage conferences, online webinars, meetups, and attendee RSVP engagement."
        breadcrumbs={[{ label: 'Events' }]}
        actions={
          <Button
            label={exporting ? 'Exporting...' : 'Export CSV'}
            icon={exporting ? 'pi pi-spin pi-spinner' : <Download className="w-3.5 h-3.5 mr-1.5" />}
            onClick={handleExport}
            disabled={exporting}
            loading={exporting}
            className="p-button-outlined p-button-secondary text-xs"
          />
        }
      />

      {/* Stats Cards */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500">Total Events</span>
            <div className="p-2 rounded-lg bg-blue-50 text-blue-600">
              <Calendar className="w-4 h-4" />
            </div>
          </div>
          <p className="text-2xl font-bold text-slate-800 mt-2">{stats.totalCount}</p>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500">Upcoming Events</span>
            <div className="p-2 rounded-lg bg-emerald-50 text-emerald-600">
              <Clock className="w-4 h-4" />
            </div>
          </div>
          <p className="text-2xl font-bold text-emerald-600 mt-2">{stats.upcomingCount}</p>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500">This Month</span>
            <div className="p-2 rounded-lg bg-indigo-50 text-indigo-600">
              <Calendar className="w-4 h-4" />
            </div>
          </div>
          <p className="text-2xl font-bold text-indigo-600 mt-2">{stats.thisMonthCount}</p>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500">Past Events</span>
            <div className="p-2 rounded-lg bg-slate-100 text-slate-600">
              <Clock className="w-4 h-4" />
            </div>
          </div>
          <p className="text-2xl font-bold text-slate-600 mt-2">{stats.pastCount}</p>
        </div>
      </div>

      {/* Filter Bar */}
      <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs flex flex-wrap items-center justify-between gap-3">
        <div className="flex items-center gap-2 flex-1 max-w-md">
          <AdminSearchInput
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            onClear={() => setSearch('')}
            placeholder="Search event title, organizer, city..."
          />
        </div>

        <div className="flex flex-wrap items-center gap-2">
          <Dropdown
            value={timeframe}
            options={[
              { label: 'All Timeframes', value: '' },
              { label: 'Upcoming Only', value: 'upcoming' },
              { label: 'Past Only', value: 'past' },
            ]}
            onChange={(e) => setTimeframe(e.value)}
            className="text-xs w-36"
            placeholder="Timeframe"
          />

          <Dropdown
            value={eventType}
            options={[
              { label: 'All Formats', value: '' },
              { label: 'In-Person (Venue)', value: 'in_person' },
              { label: 'Online Webinar', value: 'online' },
            ]}
            onChange={(e) => setEventType(e.value)}
            className="text-xs w-36"
            placeholder="Event Format"
          />

          <Dropdown
            value={status}
            options={[
              { label: 'All Statuses', value: '' },
              { label: 'Active', value: 'active' },
              { label: 'Cancelled', value: 'cancelled' },
            ]}
            onChange={(e) => setStatus(e.value)}
            className="text-xs w-32"
            placeholder="Status"
          />

          <Button
            icon="pi pi-refresh"
            onClick={() => fetchEvents(1)}
            className="p-button-outlined p-button-secondary text-xs"
            tooltip="Refresh"
          />
        </div>
      </div>

      {/* Bulk Action Bar */}
      {selectedIds.length > 0 && (
        <div className="p-3 bg-blue-50 border border-blue-200 rounded-xl flex items-center justify-between flex-wrap gap-2">
          <span className="text-xs font-semibold text-blue-900">
            {selectedIds.length} event(s) selected
          </span>
          <div className="flex flex-wrap items-center gap-2">
            <Button
              label="Activate"
              size="small"
              onClick={() => handleBulkAction('activate')}
              className="p-button-success text-xs py-1"
              disabled={actionLoading}
            />
            <Button
              label="Cancel Events"
              size="small"
              onClick={() => handleBulkAction('cancel')}
              className="p-button-warning text-xs py-1"
              disabled={actionLoading}
            />
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

      {/* Table */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        {loading ? (
          <div className="p-12 flex justify-center">
            <LoadingSpinner message="Loading events..." />
          </div>
        ) : error ? (
          <div className="p-8">
            <ErrorState title="Failed to Load Events" message={error} onRetry={() => fetchEvents(1)} />
          </div>
        ) : events.length === 0 ? (
          <div className="p-8">
            <EmptyState
              title="No Events Found"
              message="No events match your current filter parameters."
              icon={Calendar}
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
                        checked={events.length > 0 && selectedIds.length === events.length}
                        onChange={(e) => toggleSelectAll(e.checked)}
                      />
                    </th>
                    <th className="p-3.5">Event Details</th>
                    <th className="p-3.5">Organizer</th>
                    <th className="p-3.5">Date & Time</th>
                    <th className="p-3.5">Location / Format</th>
                    <th className="p-3.5 text-center">RSVPs</th>
                    <th className="p-3.5">Status</th>
                    <th className="p-3.5 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {events.map((eventItem) => (
                    <tr key={eventItem.id} className="hover:bg-slate-50/80 transition-colors">
                      <td className="p-3.5 text-center">
                        <Checkbox
                          checked={selectedIds.includes(eventItem.id)}
                          onChange={() => toggleSelectOne(eventItem.id)}
                        />
                      </td>
                      <td className="p-3.5">
                        <button
                          type="button"
                          onClick={() => handleViewEvent(eventItem)}
                          className="text-left font-bold text-slate-900 hover:text-blue-600 hover:underline transition-colors block"
                          title="View Event Details"
                        >
                          {eventItem.title}
                        </button>
                        <div className="text-[10px] text-slate-400">
                          {eventItem.category || 'General Event'}
                        </div>
                      </td>
                      <td className="p-3.5">
                        <div className="font-semibold text-slate-800">
                          {eventItem.organizer?.name || 'Organizer'}
                        </div>
                        <div className="text-[10px] text-slate-400">{eventItem.organizer?.email || 'N/A'}</div>
                      </td>
                      <td className="p-3.5 text-[11px] text-slate-600">
                        <div className="font-medium text-slate-800">
                          {new Date(eventItem.start_date || eventItem.starts_at).toLocaleDateString()}
                        </div>
                        <div className="text-slate-400">
                          {eventItem.start_time || new Date(eventItem.start_date || eventItem.starts_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                        </div>
                      </td>
                      <td className="p-3.5">
                        {eventItem.event_type === 'online' || eventItem.is_online ? (
                          <span className="inline-flex items-center text-[10px] font-semibold bg-purple-50 text-purple-700 px-2 py-0.5 rounded-full border border-purple-200">
                            <Video className="w-2.5 h-2.5 mr-1" /> Online
                          </span>
                        ) : (
                          <span className="inline-flex items-center text-[10px] font-semibold bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full border border-blue-200">
                            <MapPin className="w-2.5 h-2.5 mr-1" /> {eventItem.location_city || 'In-Person'}
                          </span>
                        )}
                      </td>
                      <td className="p-3.5 text-center font-bold text-slate-800">
                        <span className="inline-flex items-center space-x-1 text-slate-700">
                          <Users className="w-3 h-3 text-blue-500 mr-1" />
                          {eventItem.responses_count ?? 0}
                        </span>
                      </td>
                      <td className="p-3.5">
                        <StatusBadge status={eventItem.status || 'active'} />
                      </td>
                      <td className="p-3.5 text-right whitespace-nowrap">
                        <div className="inline-flex items-center justify-end gap-1.5 sm:gap-2">
                          <Button
                            icon={<Eye className="w-3.5 h-3.5 text-blue-600" />}
                            onClick={() => handleViewEvent(eventItem)}
                            className="p-button-text p-button-secondary p-button-sm p-0 w-7 h-7"
                            tooltip="View event details"
                            aria-label="View event details"
                          />
                          <Button
                            icon={isEventActive(eventItem.status) ? <XCircle className="w-3.5 h-3.5 text-amber-500" /> : <CheckCircle className="w-3.5 h-3.5 text-emerald-600" />}
                            onClick={() => handleStatusChange(eventItem, isEventActive(eventItem.status) ? 'cancelled' : 'active')}
                            className="p-button-text p-button-secondary p-button-sm p-0 w-7 h-7"
                            tooltip={isEventActive(eventItem.status) ? 'Cancel event' : 'Activate event'}
                            aria-label={isEventActive(eventItem.status) ? 'Cancel event' : 'Activate event'}
                          />
                          <Button
                            icon={<Trash2 className="w-3.5 h-3.5 text-red-500" />}
                            onClick={() => handleDelete(eventItem)}
                            className="p-button-text p-button-danger p-button-sm p-0 w-7 h-7"
                            tooltip="Delete event"
                          />
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {/* Paginator */}
            <div className="p-3 border-t border-slate-200 flex justify-between items-center text-xs text-slate-500">
              <span>Showing {events.length} of {pagination.total} entries</span>
              <Paginator
                first={(pagination.page - 1) * pagination.perPage}
                rows={pagination.perPage}
                totalRecords={pagination.total}
                onPageChange={(e) => fetchEvents(e.page + 1)}
                className="p-paginator-sm"
              />
            </div>
          </>
        )}
      </div>
    </div>
  );
}

export default EventsListPage;
