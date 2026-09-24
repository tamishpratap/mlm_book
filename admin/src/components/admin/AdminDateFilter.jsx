import React, { useState, useEffect } from 'react';
import { Calendar as CalendarIcon, X, AlertCircle } from 'lucide-react';

/**
 * Format a Date object as YYYY-MM-DD string using local calendar dates
 */
export const formatDateString = (date) => {
  if (!date) return '';
  const d = new Date(date);
  if (isNaN(d.getTime())) return '';
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

/**
 * Compute predefined date boundaries for presets
 */
export const getDatePresetRange = (preset) => {
  const now = new Date();
  switch (preset) {
    case 'today': {
      const todayStr = formatDateString(now);
      return { date_from: todayStr, date_to: todayStr };
    }
    case 'yesterday': {
      const yesterday = new Date(now);
      yesterday.setDate(yesterday.getDate() - 1);
      const yesterdayStr = formatDateString(yesterday);
      return { date_from: yesterdayStr, date_to: yesterdayStr };
    }
    case '7days':
    case '7d': {
      const start = new Date(now);
      start.setDate(start.getDate() - 6);
      return { date_from: formatDateString(start), date_to: formatDateString(now) };
    }
    case '30days':
    case '30d': {
      const start = new Date(now);
      start.setDate(start.getDate() - 29);
      return { date_from: formatDateString(start), date_to: formatDateString(now) };
    }
    case 'month':
    case 'this_month': {
      const start = new Date(now.getFullYear(), now.getMonth(), 1);
      const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
      return { date_from: formatDateString(start), date_to: formatDateString(end) };
    }
    case 'all':
    default:
      return { date_from: '', date_to: '' };
  }
};

/**
 * Identify matching preset if date_from and date_to match standard boundaries
 */
export const detectPreset = (dateFrom, dateTo) => {
  if (!dateFrom && !dateTo) return 'all';
  const today = getDatePresetRange('today');
  if (dateFrom === today.date_from && dateTo === today.date_to) return 'today';

  const yesterday = getDatePresetRange('yesterday');
  if (dateFrom === yesterday.date_from && dateTo === yesterday.date_to) return 'yesterday';

  const seven = getDatePresetRange('7days');
  if (dateFrom === seven.date_from && dateTo === seven.date_to) return '7days';

  const thirty = getDatePresetRange('30days');
  if (dateFrom === thirty.date_from && dateTo === thirty.date_to) return '30days';

  const month = getDatePresetRange('month');
  if (dateFrom === month.date_from && dateTo === month.date_to) return 'month';

  return 'custom';
};

const PRESET_OPTIONS = [
  { label: 'All Dates', value: 'all' },
  { label: 'Today', value: 'today' },
  { label: 'Yesterday', value: 'yesterday' },
  { label: 'Last 7 Days', value: '7days' },
  { label: 'Last 30 Days', value: '30days' },
  { label: 'This Month', value: 'month' },
  { label: 'Custom Range', value: 'custom' },
];

/**
 * Reusable Admin Date Filter Component
 * Supports preset quick selection and explicit custom range inputs with auto-validation.
 */
export function AdminDateFilter({
  dateFrom = '',
  dateTo = '',
  onChange,
  onReset,
  className = '',
  showInputs = true,
  disabled = false,
  compact = false,
}) {
  const [selectedPreset, setSelectedPreset] = useState(() => detectPreset(dateFrom, dateTo));
  const [localFrom, setLocalFrom] = useState(dateFrom || '');
  const [localTo, setLocalTo] = useState(dateTo || '');
  const [error, setError] = useState(null);

  // Sync internal state when external props change
  useEffect(() => {
    setLocalFrom(dateFrom || '');
    setLocalTo(dateTo || '');
    setSelectedPreset(detectPreset(dateFrom, dateTo));
    setError(null);
  }, [dateFrom, dateTo]);

  const handlePresetSelect = (presetVal) => {
    setSelectedPreset(presetVal);
    setError(null);

    if (presetVal === 'all') {
      setLocalFrom('');
      setLocalTo('');
      onChange?.({ date_from: '', date_to: '', preset: 'all' });
      return;
    }

    if (presetVal === 'custom') {
      // Keep current inputs or leave open for user to enter
      return;
    }

    const range = getDatePresetRange(presetVal);
    setLocalFrom(range.date_from);
    setLocalTo(range.date_to);
    onChange?.({ ...range, preset: presetVal });
  };

  const handleDateChange = (fromVal, toVal) => {
    setLocalFrom(fromVal);
    setLocalTo(toVal);

    if (fromVal && toVal && fromVal > toVal) {
      setError('Start date cannot be after end date');
      return;
    }

    setError(null);
    const newPreset = detectPreset(fromVal, toVal);
    setSelectedPreset(newPreset);
    onChange?.({ date_from: fromVal, date_to: toVal, preset: newPreset });
  };

  const handleClear = () => {
    setSelectedPreset('all');
    setLocalFrom('');
    setLocalTo('');
    setError(null);
    if (onReset) {
      onReset();
    } else {
      onChange?.({ date_from: '', date_to: '', preset: 'all' });
    }
  };

  const hasValue = Boolean(localFrom || localTo || (selectedPreset && selectedPreset !== 'all'));

  return (
    <div className={`flex flex-col gap-1 ${className}`}>
      <div className="flex flex-wrap items-center gap-2">
        {/* Presets Dropdown */}
        <div className="relative w-full sm:w-auto">
          <select
            value={selectedPreset}
            onChange={(e) => handlePresetSelect(e.target.value)}
            disabled={disabled}
            className="w-full sm:w-36 px-2.5 py-1.5 text-xs bg-white border border-slate-300 rounded-lg text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:opacity-50 cursor-pointer"
          >
            {PRESET_OPTIONS.map((opt) => (
              <option key={opt.value} value={opt.value}>
                {opt.label}
              </option>
            ))}
          </select>
        </div>

        {/* Custom Date Inputs */}
        {(showInputs || selectedPreset === 'custom') && (
          <div className="flex flex-wrap sm:flex-nowrap items-center gap-1.5 max-w-full">
            <div className="relative flex items-center min-w-0">
              <input
                type="date"
                value={localFrom}
                onChange={(e) => handleDateChange(e.target.value, localTo)}
                disabled={disabled}
                placeholder="From"
                className={`w-[120px] sm:w-[125px] max-w-full min-w-0 px-2 py-1 text-xs bg-white border rounded-lg text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:opacity-50 ${
                  error ? 'border-red-400 focus:border-red-500' : 'border-slate-300'
                }`}
                title="Start Date (From)"
              />
            </div>
            <span className="text-xs text-slate-400 select-none shrink-0">to</span>
            <div className="relative flex items-center min-w-0">
              <input
                type="date"
                value={localTo}
                onChange={(e) => handleDateChange(localFrom, e.target.value)}
                disabled={disabled}
                placeholder="To"
                className={`w-[120px] sm:w-[125px] max-w-full min-w-0 px-2 py-1 text-xs bg-white border rounded-lg text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:opacity-50 ${
                  error ? 'border-red-400 focus:border-red-500' : 'border-slate-300'
                }`}
                title="End Date (To)"
              />
            </div>
          </div>
        )}

        {/* Quick Clear Button */}
        {hasValue && (
          <button
            type="button"
            onClick={handleClear}
            disabled={disabled}
            title="Clear date filter"
            className="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-md transition-colors cursor-pointer"
          >
            <X className="w-3.5 h-3.5" />
          </button>
        )}
      </div>

      {/* Validation Error Hint */}
      {error && (
        <div className="flex items-center gap-1 text-[11px] text-red-600 mt-0.5 animate-fadeIn">
          <AlertCircle className="w-3 h-3 shrink-0" />
          <span>{error}</span>
        </div>
      )}
    </div>
  );
}

export default AdminDateFilter;
