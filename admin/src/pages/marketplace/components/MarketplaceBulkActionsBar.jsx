import { useState } from 'react';
import { Dropdown } from 'primereact/dropdown';
import { Button } from 'primereact/button';
import { confirmHelper } from '../../../utils/confirmHelper';

export function MarketplaceBulkActionsBar({
  selectedCount = 0,
  onExecuteBulkAction,
  loading = false,
}) {
  const [action, setAction] = useState(null);

  const bulkOptions = [
    { label: 'Feature Selected', value: 'feature' },
    { label: 'Unfeature Selected', value: 'unfeature' },
    { label: 'Hide Selected', value: 'hide' },
    { label: 'Mark Available', value: 'available' },
    { label: 'Delete Selected', value: 'delete' },
  ];

  const handleApply = () => {
    if (!action) return;

    if (action === 'delete') {
      confirmHelper.confirmDelete({
        header: 'Bulk Delete Products',
        message: `Are you sure you want to delete ${selectedCount} selected product listings? This action cannot be undone.`,
        onAccept: () => onExecuteBulkAction(action),
      });
    } else {
      confirmHelper.confirm({
        header: 'Apply Bulk Action',
        message: `Are you sure you want to apply "${action}" to ${selectedCount} selected products?`,
        onAccept: () => onExecuteBulkAction(action),
      });
    }
  };

  if (selectedCount === 0) return null;

  return (
    <div className="bg-slate-900 text-white px-4 py-2.5 rounded-xl mb-4 flex items-center justify-between flex-wrap gap-2 shadow-lg animate-fade-in">
      <div className="flex items-center space-x-2">
        <span className="w-2 h-2 rounded-full bg-emerald-400 animate-ping" />
        <span className="text-xs font-bold">
          {selectedCount} {selectedCount === 1 ? 'Product' : 'Products'} Selected
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

export default MarketplaceBulkActionsBar;
