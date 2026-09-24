import { useState } from 'react';
import { Button } from 'primereact/button';
import { confirmHelper } from '../../../utils/confirmHelper';

export function MemberBulkActionsBar({
  selectedIds = [],
  onApplyBulkAction,
  loading = false,
  currentMode = 'active',
}) {
  const [selectedAction, setSelectedAction] = useState('');

  const count = selectedIds.length;
  if (count === 0) return null;

  const handleApply = () => {
    if (!selectedAction) return;

    if (selectedAction === 'delete') {
      confirmHelper.confirmDelete({
        header: 'Confirm Bulk Deletion',
        message: `Are you sure you want to permanently remove the ${count} selected members?`,
        onAccept: () => onApplyBulkAction(selectedAction, selectedIds),
      });
      return;
    }

    if (selectedAction === 'block') {
      confirmHelper.confirmBlock({
        header: 'Confirm Bulk Account Block',
        message: `Are you sure you want to suspend/block the ${count} selected member accounts?`,
        onAccept: () => onApplyBulkAction(selectedAction, selectedIds),
      });
      return;
    }

    confirmHelper.confirm({
      header: 'Confirm Bulk Action',
      message: `Apply "${selectedAction}" to ${count} selected member(s)?`,
      onAccept: () => onApplyBulkAction(selectedAction, selectedIds),
    });
  };

  return (
    <div className="bg-blue-50/70 border border-blue-200/80 p-3 rounded-xl mb-4 flex items-center justify-between flex-wrap gap-2 animate-fadeIn">
      <div className="flex items-center space-x-2">
        <span className="w-2.5 h-2.5 rounded-full bg-blue-600 animate-pulse" />
        <span className="text-xs font-bold text-blue-900">
          {count} member{count > 1 ? 's' : ''} selected
        </span>
      </div>

      <div className="flex flex-wrap items-center gap-2">
        <select
          value={selectedAction}
          onChange={(e) => setSelectedAction(e.target.value)}
          className="px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-lg text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-blue-500/20"
        >
          <option value="">Choose Bulk Action</option>
          {currentMode === 'pending' ? (
            <>
              <option value="approve">Approve Verification</option>
              <option value="reject">Reject Request</option>
            </>
          ) : (
            <>
              <option value="block">Block / Revoke Verification</option>
              <option value="unverify">Mark as Unverified</option>
              <option value="verify">Mark as Verified</option>
            </>
          )}
          <option value="delete">Delete Permanently</option>
        </select>

        <Button
          label="Apply"
          size="small"
          onClick={handleApply}
          disabled={!selectedAction || loading}
          loading={loading}
          className="p-button-secondary text-xs"
        />
      </div>
    </div>
  );
}

export default MemberBulkActionsBar;
