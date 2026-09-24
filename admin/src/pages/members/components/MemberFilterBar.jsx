import { RotateCcw } from 'lucide-react';
import { Button } from 'primereact/button';
import { AdminSearchInput } from '../../../components/common/AdminSearchInput';
import { AdminDateFilter } from '../../../components/admin/AdminDateFilter';

export function MemberFilterBar({
  filters = {},
  countries = [],
  onFilterChange,
  onApplyFilters,
  onResetFilters,
  loading = false,
  showStatusFilter = true,
}) {
  const statusOptions = [
    { label: 'All Members', value: 'all' },
    { label: 'Verified Members', value: 'verified' },
    { label: 'Unverified Members', value: 'unverified' },
    { label: 'Active (Last 7 Days)', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
  ];

  const hasActiveFilters = Boolean(
    filters.q || filters.status || filters.country || filters.date_from || filters.date_to
  );

  const handleSubmit = (e) => {
    e.preventDefault();
    onApplyFilters();
  };

  return (
    <form onSubmit={handleSubmit} className="bg-slate-50/80 p-4 rounded-xl border border-slate-200/80 mb-4 w-full">
      <div className="flex flex-wrap items-end gap-3 w-full">
        {/* Search */}
        <div className="flex-1 min-w-[200px] sm:min-w-[240px] w-full sm:w-auto">
          <label className="block text-xs font-semibold text-slate-700 mb-1" htmlFor="q">
            Search Members
          </label>
          <AdminSearchInput
            id="q"
            value={filters.q || ''}
            onChange={(e) => onFilterChange('q', e.target.value)}
            onClear={() => onFilterChange('q', '')}
            placeholder="Search by ID, Name, Email, Phone, City..."
          />
        </div>

        {/* Verification / Activity Status */}
        {showStatusFilter && (
          <div className="w-full sm:w-auto sm:min-w-[150px]">
            <label className="block text-xs font-semibold text-slate-700 mb-1" htmlFor="status">
              Verification & Activity
            </label>
            <select
              id="status"
              value={filters.status || ''}
              onChange={(e) => onFilterChange('status', e.target.value)}
              className="w-full px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-lg text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
            >
              {statusOptions.map((opt, idx) => (
                <option key={idx} value={opt.value}>
                  {opt.label}
                </option>
              ))}
            </select>
          </div>
        )}

        {/* Country */}
        <div className="w-full sm:w-auto sm:min-w-[130px] lg:w-36">
          <label className="block text-xs font-semibold text-slate-700 mb-1" htmlFor="country">
            Country
          </label>
          <select
            id="country"
            value={filters.country || ''}
            onChange={(e) => onFilterChange('country', e.target.value)}
            className="w-full px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-lg text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 truncate"
          >
            <option value="">All Countries</option>
            {countries.map((c, idx) => (
              <option key={idx} value={c}>
                {c}
              </option>
            ))}
          </select>
        </div>

        {/* Joined Date Filter (Presets + Range) */}
        <div className="w-full sm:w-auto">
          <label className="block text-xs font-semibold text-slate-700 mb-1">
            Joined Date
          </label>
          <AdminDateFilter
            dateFrom={filters.date_from || ''}
            dateTo={filters.date_to || ''}
            onChange={({ date_from, date_to }) => {
              onFilterChange('date_from', date_from);
              onFilterChange('date_to', date_to);
            }}
            onReset={() => {
              onFilterChange('date_from', '');
              onFilterChange('date_to', '');
            }}
          />
        </div>

        {/* Action Buttons */}
        <div className="flex items-center gap-2 w-full sm:w-auto shrink-0 pt-1 sm:pt-0">
          <Button
            type="submit"
            label="Filter"
            icon="pi pi-filter"
            size="small"
            loading={loading}
            className="p-button-primary text-xs flex-1 sm:flex-initial px-4"
          />
          {hasActiveFilters && (
            <button
              type="button"
              onClick={onResetFilters}
              title="Reset all filters"
              className="p-2 bg-white border border-slate-300 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors focus:outline-hidden cursor-pointer shrink-0"
            >
              <RotateCcw className="w-4 h-4" />
            </button>
          )}
        </div>
      </div>
    </form>
  );
}

export default MemberFilterBar;
