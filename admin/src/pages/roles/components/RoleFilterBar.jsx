import { useState } from 'react';
import { Filter } from 'lucide-react';
import { Button } from 'primereact/button';
import { AdminSearchInput } from '../../../components/common/AdminSearchInput';

export function RoleFilterBar({ initialSearch = '', onFilter, loading = false }) {
  const [search, setSearch] = useState(initialSearch);

  const handleSubmit = (e) => {
    if (e && e.preventDefault) e.preventDefault();
    onFilter(search.trim());
  };

  const handleClear = () => {
    setSearch('');
    onFilter('');
  };

  return (
    <form onSubmit={handleSubmit} className="p-4 bg-slate-50/80 rounded-2xl border border-slate-200/80 mb-6">
      <div className="flex flex-col sm:flex-row items-center gap-3">
        <div className="flex-1 w-full">
          <AdminSearchInput
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            onClear={handleClear}
            onSubmit={handleSubmit}
            placeholder="Search Role Name, Slug, Description..."
            disabled={loading}
          />
        </div>

        <div className="flex items-center space-x-2 w-full sm:w-auto">
          <Button
            type="submit"
            label="Filter"
            icon={<Filter className="w-3.5 h-3.5 mr-1" />}
            size="small"
            loading={loading}
            className="p-button-primary text-xs w-full sm:w-auto"
          />
          {search && (
            <Button
              type="button"
              label="Reset"
              size="small"
              onClick={handleClear}
              disabled={loading}
              className="p-button-outlined p-button-secondary text-xs"
            />
          )}
        </div>
      </div>
    </form>
  );
}

export default RoleFilterBar;
