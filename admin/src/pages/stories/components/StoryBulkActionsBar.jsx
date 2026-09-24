import { Button } from 'primereact/button';

export function StoryBulkActionsBar({
  selectedCount = 0,
  onBulkDelete,
  loading = false,
}) {
  if (selectedCount === 0) return null;

  return (
    <div className="p-3 bg-red-50 border border-red-200 rounded-xl flex items-center justify-between">
      <span className="text-xs font-semibold text-red-900">
        {selectedCount} {selectedCount === 1 ? 'story' : 'stories'} selected
      </span>
      <Button
        label="Delete Selected"
        size="small"
        onClick={onBulkDelete}
        className="p-button-danger text-xs py-1"
        disabled={loading}
        icon={loading ? 'pi pi-spin pi-spinner mr-1' : undefined}
      />
    </div>
  );
}

export default StoryBulkActionsBar;
