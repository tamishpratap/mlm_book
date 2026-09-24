import { Folder, CheckSquare, Square } from 'lucide-react';
import { Checkbox } from 'primereact/checkbox';

export function PermissionGroupSelector({
  groupedPermissions = {},
  selectedPermissionIds = [],
  onChange,
  disabled = false,
}) {
  const handleToggleSingle = (permId) => {
    if (disabled) return;
    const exists = selectedPermissionIds.includes(permId);
    const updated = exists
      ? selectedPermissionIds.filter((id) => id !== permId)
      : [...selectedPermissionIds, permId];
    onChange(updated);
  };

  const handleToggleGroup = (groupPerms) => {
    if (disabled) return;
    const groupPermIds = groupPerms.map((p) => p.id);
    const allSelected = groupPermIds.every((id) => selectedPermissionIds.includes(id));

    let updated;
    if (allSelected) {
      // Deselect group
      updated = selectedPermissionIds.filter((id) => !groupPermIds.includes(id));
    } else {
      // Select all in group
      const toAdd = groupPermIds.filter((id) => !selectedPermissionIds.includes(id));
      updated = [...selectedPermissionIds, ...toAdd];
    }
    onChange(updated);
  };

  const groups = Object.keys(groupedPermissions);

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
      {groups.map((groupKey) => {
        const perms = groupedPermissions[groupKey] || [];
        const groupPermIds = perms.map((p) => p.id);
        const selectedCount = groupPermIds.filter((id) => selectedPermissionIds.includes(id)).length;
        const allSelected = selectedCount === perms.length && perms.length > 0;
        const groupTitle = groupKey.replace(/_/g, ' ');

        return (
          <div
            key={groupKey}
            className="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 transition-colors hover:border-slate-300"
          >
            {/* Group Header with Select All toggle */}
            <div className="flex items-center justify-between pb-3 mb-3 border-b border-slate-200/80">
              <div className="flex items-center space-x-2">
                <Folder className="w-4 h-4 text-blue-600" />
                <h4 className="text-xs font-bold text-slate-900 capitalize">
                  {groupTitle}
                </h4>
                <span className="text-[10px] font-semibold text-slate-500 bg-white px-2 py-0.5 rounded-full border border-slate-200">
                  {selectedCount}/{perms.length}
                </span>
              </div>

              {!disabled && (
                <button
                  type="button"
                  onClick={() => handleToggleGroup(perms)}
                  className="text-[11px] font-semibold text-blue-600 hover:text-blue-700 flex items-center space-x-1"
                >
                  {allSelected ? (
                    <>
                      <CheckSquare className="w-3.5 h-3.5" />
                      <span>Deselect All</span>
                    </>
                  ) : (
                    <>
                      <Square className="w-3.5 h-3.5" />
                      <span>Select All</span>
                    </>
                  )}
                </button>
              )}
            </div>

            {/* Individual Checkbox List */}
            <div className="space-y-2.5">
              {perms.map((perm) => {
                const isChecked = selectedPermissionIds.includes(perm.id);

                return (
                  <div
                    key={perm.id}
                    className="flex items-start space-x-2.5 cursor-pointer select-none"
                    onClick={() => handleToggleSingle(perm.id)}
                  >
                    <Checkbox
                      inputId={`perm_${perm.id}`}
                      checked={isChecked}
                      onChange={() => handleToggleSingle(perm.id)}
                      disabled={disabled}
                      className="mt-0.5"
                    />
                    <label
                      htmlFor={`perm_${perm.id}`}
                      className="text-xs font-medium text-slate-800 cursor-pointer"
                    >
                      <span className="font-semibold text-slate-900">{perm.name}</span>
                      <code className="text-[10px] font-mono text-slate-500 ml-1.5 bg-white px-1 py-0.2 rounded border border-slate-200">
                        {perm.slug}
                      </code>
                      {perm.description && (
                        <span className="block text-[11px] text-slate-500 font-normal mt-0.5">
                          {perm.description}
                        </span>
                      )}
                    </label>
                  </div>
                );
              })}
            </div>
          </div>
        );
      })}
    </div>
  );
}

export default PermissionGroupSelector;
