import { useState } from 'react';
import { Dropdown } from 'primereact/dropdown';
import { Button } from 'primereact/button';
import { confirmHelper } from '../../../utils/confirmHelper';

export function ReportBulkActionsBar({
  selectedCount = 0,
  onExecuteBulkAction,
  loading = false,
}) {
  const [action, setAction] = useState(null);

  const bulkOptions = [
    { label: 'Mark Resolved', value: 'resolve' },
    { label: 'Mark Dismissed', value: 'dismiss' },
    { label: 'Delete Selected Tickets', value: 'delete' },
  ];

  const handleApply = () => {
    if (!action) return;

    const isDelete = action === 'delete';
    const isDismiss = action === 'dismiss';

    confirmHelper.confirm({
      header: isDelete ? 'Bulk Delete Report Tickets' : isDismiss ? 'Bulk Dismiss Reports' : 'Bulk Resolve Reports',
      message: isDelete
        ? `Are you sure you want to permanently delete ${selectedCount} selected report tickets? The reported content and users will remain unchanged.`
        : isDismiss
        ? `Are you sure you want to mark ${selectedCount} selected reports as dismissed?`
        : `Are you sure you want to mark ${selectedCount} selected reports as resolved?`,
      acceptClassName: isDelete ? 'p-button-danger text-xs' : 'p-button-primary text-xs',
      onAccept: () => onExecuteBulkAction(action),
    });
  };

  if (selectedCount === 0) return null;

  return (
    <div className="bg-slate-900 text-white px-4 py-2.5 rounded-xl mb-4 flex items-center justify-between flex-wrap gap-2 shadow-lg animate-fade-in">
      <div className="flex items-center space-x-2">
        <span className="w-2 h-2 rounded-full bg-emerald-400 animate-ping" />
        <span className="text-xs font-bold">
          {selectedCount} {selectedCount === 1 ? 'Report' : 'Reports'} Selected
        </span>
      </div>

      <div className="flex flex-wrap items-center gap-2">
        <Dropdown
          value={action}
          options={bulkOptions}
          onChange={(e) => setAction(e.value)}
          placeholder="Choose Action..."
          className="text-xs w-44"
          disabled={loading}
        />
        <Button
          label="Apply"
          size="small"
          onClick={handleApply}
          disabled={!action || loading}
          loading={loading}
          className="p-button-primary text-xs"
        />
      </div>
    </div>
  );
}

export default ReportBulkActionsBar;
