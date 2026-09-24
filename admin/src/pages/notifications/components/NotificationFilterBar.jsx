import { useState } from 'react';
import { Filter, X } from 'lucide-react';
import { Dropdown } from 'primereact/dropdown';
import { Button } from 'primereact/button';
import { AdminSearchInput } from '../../../components/common/AdminSearchInput';

export function NotificationFilterBar({
  onFilter,
  loading = false,
}) {
  const [search, setSearch] = useState('');
  const [channel, setChannel] = useState(null);
  const [status, setStatus] = useState(null);

  const channelOptions = [
    { label: 'All Channels', value: '' },
    { label: 'In-App Database', value: 'in_app' },
    { label: 'Business Page Alerts', value: 'business' },
  ];

  const statusOptions = [
    { label: 'All Statuses', value: '' },
    { label: 'Read', value: 'read' },
    { label: 'Unread', value: 'unread' },
  ];

  const handleSubmit = (e) => {
    e.preventDefault();
    onFilter({
      q: search.trim(),
      channel: channel || '',
      status: status || '',
    });
  };

  const handleReset = () => {
    setSearch('');
    setChannel(null);
    setStatus(null);
    onFilter({
      q: '',
      channel: '',
      status: '',
    });
  };

  const hasActiveFilters = Boolean(search || channel || status);

  return (
    <form onSubmit={handleSubmit} className="p-4 bg-slate-50/80 rounded-2xl border border-slate-200/80 mb-6 space-y-3">
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
        {/* Search */}
        <div>
          <AdminSearchInput
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            onClear={() => setSearch('')}
            placeholder="Search ID, Recipient, Message..."
            disabled={loading}
          />
        </div>

        {/* Channel */}
        <div>
          <Dropdown
            value={channel}
            options={channelOptions}
            onChange={(e) => setChannel(e.value)}
            placeholder="All Channels"
            className="w-full text-xs"
            disabled={loading}
          />
        </div>

        {/* Status */}
        <div>
          <Dropdown
            value={status}
            options={statusOptions}
            onChange={(e) => setStatus(e.value)}
            placeholder="All Statuses"
            className="w-full text-xs"
            disabled={loading}
          />
        </div>
      </div>

      <div className="flex flex-wrap items-center justify-end gap-2 sm:gap-2.5 pt-2 border-t border-slate-200/60">
        {hasActiveFilters && (
          <Button
            type="button"
            label="Reset Filters"
            icon={<X className="w-3.5 h-3.5 mr-1" />}
            size="small"
            onClick={handleReset}
            disabled={loading}
            className="p-button-outlined p-button-secondary text-xs"
          />
        )}
        <Button
          type="submit"
          label="Filter"
          icon={<Filter className="w-3.5 h-3.5 mr-1" />}
          size="small"
          loading={loading}
          className="p-button-primary text-xs"
        />
      </div>
    </form>
  );
}

export default NotificationFilterBar;
