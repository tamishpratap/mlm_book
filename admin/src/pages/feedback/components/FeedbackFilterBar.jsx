import { useState, useEffect } from 'react';
import { Filter, RotateCcw } from 'lucide-react';
import { Dropdown } from 'primereact/dropdown';
import { Button } from 'primereact/button';
import { AdminSearchInput } from '../../../components/common/AdminSearchInput';
import { AdminDateFilter } from '../../../components/admin/AdminDateFilter';

export function FeedbackFilterBar({
  filters = {},
  onFilter,
  loading = false,
}) {
  const [search, setSearch] = useState(filters.q || '');
  const [type, setType] = useState(filters.type || null);
  const [status, setStatus] = useState(filters.status || null);
  const [dateFrom, setDateFrom] = useState(filters.date_from || '');
  const [dateTo, setDateTo] = useState(filters.date_to || '');

  useEffect(() => {
    setSearch(filters.q || '');
    setType(filters.type || null);
    setStatus(filters.status || null);
    setDateFrom(filters.date_from || '');
    setDateTo(filters.date_to || '');
  }, [filters]);

  const typeOptions = [
    { label: 'All Submission Types', value: '' },
    { label: 'General Feedback', value: 'feedback' },
    { label: 'Suggestion', value: 'suggestion' },
    { label: 'Idea / Proposal', value: 'idea' },
    { label: 'Complaint', value: 'complaint' },
    { label: 'Bug Report', value: 'bug_report' },
    { label: 'Other Inquiry', value: 'other' },
  ];

  const statusOptions = [
    { label: 'All Statuses', value: '' },
    { label: 'New', value: 'new' },
    { label: 'In Review', value: 'in_review' },
    { label: 'Resolved', value: 'resolved' },
    { label: 'Closed', value: 'closed' },
  ];

  const handleSubmit = (e) => {
    e.preventDefault();
    onFilter({
      q: search.trim(),
      type: type || '',
      status: status || '',
      date_from: dateFrom,
      date_to: dateTo,
    });
  };

  const handleReset = () => {
    setSearch('');
    setType(null);
    setStatus(null);
    setDateFrom('');
    setDateTo('');
    onFilter({
      q: '',
      type: '',
      status: '',
      date_from: '',
      date_to: '',
    });
  };

  const hasActiveFilters = Boolean(search || type || status || dateFrom || dateTo);

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
            placeholder="Search Member, User ID, Subject, Message..."
            disabled={loading}
          />
        </div>

        {/* Feedback Type Dropdown */}
        <div className="w-full md:w-56 shrink-0">
          <Dropdown
            value={type}
            options={typeOptions}
            onChange={(e) => setType(e.value)}
            placeholder="Filter by Type"
            className="w-full text-xs"
            disabled={loading}
          />
        </div>

        {/* Status Dropdown */}
        <div className="w-full md:w-48 shrink-0">
          <Dropdown
            value={status}
            options={statusOptions}
            onChange={(e) => setStatus(e.value)}
            placeholder="Filter by Status"
            className="w-full text-xs"
            disabled={loading}
          />
        </div>
      </div>

      {/* ROW 2: Date Filters & Action Buttons */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 border-t border-slate-200/60">
        {/* Date Filter */}
        <div className="flex-1 max-w-xl">
          <AdminDateFilter
            dateFrom={dateFrom}
            dateTo={dateTo}
            onChange={({ dateFrom: df, dateTo: dt }) => {
              setDateFrom(df);
              setDateTo(dt);
            }}
            disabled={loading}
          />
        </div>

        {/* Filter / Reset Action Buttons */}
        <div className="flex items-center space-x-2 shrink-0 self-end sm:self-auto">
          {hasActiveFilters && (
            <Button
              type="button"
              label="Reset"
              icon={<RotateCcw className="w-3 h-3 mr-1" />}
              size="small"
              onClick={handleReset}
              disabled={loading}
              className="p-button-outlined p-button-secondary text-xs h-9"
            />
          )}

          <Button
            type="submit"
            label="Apply Filters"
            icon={<Filter className="w-3 h-3 mr-1" />}
            size="small"
            disabled={loading}
            className="p-button-primary text-xs h-9 font-semibold"
          />
        </div>
      </div>
    </form>
  );
}

export default FeedbackFilterBar;
