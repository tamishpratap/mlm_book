import React from 'react';
import { Download } from 'lucide-react';
import { Button } from 'primereact/button';
import { AdminDateFilter } from '../../../components/admin/AdminDateFilter';

export function AnalyticsFilterBar({
  dateFrom = '',
  dateTo = '',
  onFilter,
  onExport,
  loading = false,
  exporting = false,
}) {
  const handleDateChange = ({ date_from, date_to, preset }) => {
    onFilter({
      period: preset === 'custom' ? undefined : (preset || '30days'),
      date_from: date_from || undefined,
      date_to: date_to || undefined,
    });
  };

  const handleReset = () => {
    onFilter({
      period: '30days',
      date_from: undefined,
      date_to: undefined,
    });
  };

  return (
    <div className="p-4 bg-white rounded-2xl border border-slate-200 shadow-2xs mb-6 flex flex-wrap items-center justify-between gap-3">
      {/* Unified Date Range & Preset Filter */}
      <div className="flex items-center gap-3">
        <AdminDateFilter
          dateFrom={dateFrom}
          dateTo={dateTo}
          onChange={handleDateChange}
          onReset={handleReset}
          disabled={loading}
        />
      </div>

      {/* Export Button */}
      <div className="flex items-center gap-2">
        <Button
          type="button"
          label={exporting ? 'Exporting...' : 'Export CSV'}
          icon={exporting ? 'pi pi-spin pi-spinner' : <Download className="w-3.5 h-3.5 mr-1" />}
          size="small"
          onClick={onExport}
          disabled={exporting || loading}
          loading={exporting}
          className="p-button-outlined p-button-secondary text-xs"
        />
      </div>
    </div>
  );
}

export default AnalyticsFilterBar;
