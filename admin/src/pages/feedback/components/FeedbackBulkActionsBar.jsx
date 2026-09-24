import { useState } from 'react';
import { Dropdown } from 'primereact/dropdown';
import { Button } from 'primereact/button';
import { confirmHelper } from '../../../utils/confirmHelper';

export function FeedbackBulkActionsBar({
  selectedCount = 0,
  onExecuteBulkAction,
  loading = false,
}) {
  const [action, setAction] = useState(null);

  const bulkOptions = [
    { label: 'Mark Resolved', value: 'resolve' },
    { label: 'Mark In Review', value: 'in_review' },
    { label: 'Reset to New', value: 'mark_new' },
    { label: 'Mark Closed', value: 'close' },
    { label: 'Delete Selected', value: 'delete' },
  ];

  const handleApply = () => {
    if (!action) return;

    const isDelete = action === 'delete';

    confirmHelper.confirm({
      header: isDelete ? 'Bulk Delete Submissions' : 'Bulk Update Status',
      message: isDelete
        ? `Are you sure you want to permanently delete ${selectedCount} selected submission(s)? This action cannot be undone.`
        : `Are you sure you want to update the status of ${selectedCount} selected submission(s)?`,
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
          {selectedCount} {selectedCount === 1 ? 'Item' : 'Items'} Selected
        </span>
      </div>

      <div className="flex flex-wrap items-center gap-2">
        <Dropdown
          value={action}
          options={bulkOptions}
          onChange={(e) => setAction(e.value)}
          placeholder="Choose Bulk Action..."
          className="text-xs w-48"
          disabled={loading}
        />
        <Button
          label="Apply"
          size="small"
          onClick={handleApply}
          disabled={!action || loading}
          loading={loading}
          className="p-button-primary text-xs font-semibold"
        />
      </div>
    </div>
  );
}

export default FeedbackBulkActionsBar;
