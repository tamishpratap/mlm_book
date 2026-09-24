import { useState, useEffect } from 'react';
import { Filter, RotateCcw } from 'lucide-react';
import { Dropdown } from 'primereact/dropdown';
import { Button } from 'primereact/button';
import { AdminSearchInput } from '../../../components/common/AdminSearchInput';
import { AdminDateFilter } from '../../../components/admin/AdminDateFilter';

export function ReportFilterBar({
  filters = {},
  onFilter,
  loading = false,
}) {
  const [search, setSearch] = useState(filters.q || '');
  const [sourceType, setSourceType] = useState(filters.source_type || null);
  const [reason, setReason] = useState(filters.reason || null);
  const [status, setStatus] = useState(filters.status || null);
  const [dateFrom, setDateFrom] = useState(filters.date_from || '');
  const [dateTo, setDateTo] = useState(filters.date_to || '');

  useEffect(() => {
    setSearch(filters.q || '');
    setSourceType(filters.source_type || null);
    setReason(filters.reason || null);
    setStatus(filters.status || null);
    setDateFrom(filters.date_from || '');
    setDateTo(filters.date_to || '');
  }, [filters]);

  const sourceTypeOptions = [
    { label: 'All Sources', value: '' },
    { label: 'Feed Posts', value: 'post' },
    { label: 'Communities', value: 'community' },
    { label: 'Marketplace Products', value: 'product' },
    { label: 'Business Reviews', value: 'business_review' },
  ];

  const reasonOptions = [
    { label: 'All Reasons', value: '' },
    { label: 'Spam', value: 'Spam' },
    { label: 'Fake News', value: 'Fake News' },
    { label: 'Harassment', value: 'Harassment' },
    { label: 'Violence', value: 'Violence' },
    { label: 'Adult Content', value: 'Adult Content' },
    { label: 'Hate Speech', value: 'Hate Speech' },
    { label: 'Other', value: 'Other' },
  ];

  const statusOptions = [
    { label: 'All Statuses', value: '' },
    { label: 'Pending Review', value: 'pending' },
    { label: 'Resolved', value: 'resolved' },
    { label: 'Dismissed', value: 'dismissed' },
  ];

  const handleSubmit = (e) => {
    e.preventDefault();
    onFilter({
      q: search.trim(),
      source_type: sourceType || '',
      reason: reason || '',
      status: status || '',
      date_from: dateFrom,
      date_to: dateTo,
    });
  };

  const handleReset = () => {
    setSearch('');
    setSourceType(null);
    setReason(null);
    setStatus(null);
    setDateFrom('');
    setDateTo('');
    onFilter({
      q: '',
      source_type: '',
      reason: '',
      status: '',
      date_from: '',
      date_to: '',
    });
  };

  const hasActiveFilters = Boolean(search || sourceType || reason || status || dateFrom || dateTo);

  return (
    <form
      onSubmit={handleSubmit}
      className="bg-slate-50/80 p-4 sm:p-5 rounded-2xl border border-slate-200/80 mb-6 space-y-3.5 min-w-0"
    >
      {/* ROW 1: Search and Categorical Dropdowns */}
      <div className="flex flex-col md:flex-row md:items-center gap-3 min-w-0">
        {/* Search */}
        <div className="flex-1 min-w-[200px]">
          <AdminSearchInput
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            onClear={() => setSearch('')}
            placeholder="Search Reporter, Target, Reason..."
            disabled={loading}
          />
        </div>

        {/* Dropdowns Group */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-2.5 sm:gap-3 w-full md:w-auto shrink-0">
          {/* Source Type */}
          <div className="w-full md:w-44">
            <Dropdown
              value={sourceType}
              options={sourceTypeOptions}
              onChange={(e) => setSourceType(e.value)}
              placeholder="All Sources"
              className="w-full text-xs"
              disabled={loading}
            />
          </div>

          {/* Reason Filter */}
          <div className="w-full md:w-36">
            <Dropdown
              value={reason}
              options={reasonOptions}
              onChange={(e) => setReason(e.value)}
              placeholder="All Reasons"
              className="w-full text-xs"
              disabled={loading}
            />
          </div>

          {/* Status */}
          <div className="w-full md:w-36">
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
      </div>

      {/* ROW 2: Date Filters & Action Buttons */}
      <div className="flex flex-wrap items-center gap-3 pt-3 border-t border-slate-200/60 min-w-0">
        {/* Date Filter (Presets + Range) */}
        <div className="min-w-0">
          <AdminDateFilter
            dateFrom={dateFrom}
            dateTo={dateTo}
            onChange={({ date_from, date_to }) => {
              setDateFrom(date_from || '');
              setDateTo(date_to || '');
            }}
            onReset={() => {
              setDateFrom('');
              setDateTo('');
            }}
            disabled={loading}
          />
        </div>

        {/* Action Buttons: Filter & Reset */}
        <div className="flex items-center gap-2 w-full sm:w-auto">
          <Button
            type="submit"
            label="Filter"
            icon={<Filter className="w-3.5 h-3.5 mr-1.5" />}
            size="small"
            loading={loading}
            className="p-button-primary text-xs !h-9 !rounded-lg px-4 font-semibold shadow-2xs flex-1 sm:flex-initial"
          />
          {hasActiveFilters && (
            <Button
              type="button"
              label="Reset"
              icon={<RotateCcw className="w-3.5 h-3.5 mr-1.5" />}
              size="small"
              onClick={handleReset}
              disabled={loading}
              className="p-button-outlined p-button-secondary text-xs !h-9 !rounded-lg px-3 flex-1 sm:flex-initial"
            />
          )}
        </div>
      </div>
    </form>
  );
}

export default ReportFilterBar;
