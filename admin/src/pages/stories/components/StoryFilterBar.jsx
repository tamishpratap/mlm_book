import { Dropdown } from 'primereact/dropdown';
import { Button } from 'primereact/button';
import { AdminSearchInput } from '../../../components/common/AdminSearchInput';
import { AdminDateFilter } from '../../../components/admin/AdminDateFilter';

export function StoryFilterBar({
  search = '',
  onSearchChange,
  mediaType = '',
  onMediaTypeChange,
  status = '',
  onStatusChange,
  showStatusFilter = false,
  dateFrom = '',
  dateTo = '',
  datePreset = '',
  onDateChange,
  onDateReset,
  onRefresh,
  loading = false,
}) {
  return (
    <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs flex flex-wrap items-center justify-between gap-3">
      {/* Search Input */}
      <div className="flex items-center gap-2 flex-1 max-w-md">
        <AdminSearchInput
          value={search}
          onChange={(e) => onSearchChange(e.target.value)}
          onClear={() => onSearchChange('')}
          placeholder="Search by caption, user ID or author name..."
        />
      </div>

      {/* Filter Controls */}
      <div className="flex flex-wrap items-center gap-2">
        {showStatusFilter && (
          <Dropdown
            value={status}
            options={[
              { label: 'All Statuses', value: '' },
              { label: 'Active (Live)', value: 'active' },
              { label: 'Expired', value: 'expired' },
            ]}
            onChange={(e) => onStatusChange(e.value)}
            className="text-xs w-36"
            placeholder="Status Filter"
          />
        )}

        <Dropdown
          value={mediaType}
          options={[
            { label: 'All Media', value: '' },
            { label: 'Images', value: 'image' },
            { label: 'Videos', value: 'video' },
          ]}
          onChange={(e) => onMediaTypeChange(e.value)}
          className="text-xs w-32"
          placeholder="Media Filter"
        />

        <AdminDateFilter
          startDate={dateFrom}
          endDate={dateTo}
          preset={datePreset}
          onChange={onDateChange}
          onReset={onDateReset}
        />

        <Button
          icon={loading ? 'pi pi-spin pi-spinner' : 'pi pi-refresh'}
          size="small"
          onClick={onRefresh}
          disabled={loading}
          className="p-button-outlined p-button-secondary text-xs"
          tooltip="Refresh stories"
        />
      </div>
    </div>
  );
}

export default StoryFilterBar;
