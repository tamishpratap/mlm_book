import { useState } from 'react';
import { Filter, X } from 'lucide-react';
import { Dropdown } from 'primereact/dropdown';
import { Button } from 'primereact/button';
import { AdminSearchInput } from '../../../components/common/AdminSearchInput';

export function MarketplaceFilterBar({
  categories = [],
  onFilter,
  loading = false,
}) {
  const [search, setSearch] = useState('');
  const [category, setCategory] = useState(null);
  const [status, setStatus] = useState(null);
  const [condition, setCondition] = useState(null);

  const statusOptions = [
    { label: 'All Statuses', value: '' },
    { label: 'Available', value: 'available' },
    { label: 'Sold', value: 'sold' },
    { label: 'Hidden', value: 'hidden' },
  ];

  const conditionOptions = [
    { label: 'All Conditions', value: '' },
    { label: 'Brand New', value: 'new' },
    { label: 'Like New', value: 'like_new' },
    { label: 'Good', value: 'good' },
    { label: 'Fair', value: 'fair' },
  ];

  const categoryOptions = [
    { label: 'All Categories', value: '' },
    ...categories.map((c) => ({ label: c.name, value: c.id })),
  ];

  const handleSubmit = (e) => {
    e.preventDefault();
    onFilter({
      q: search.trim(),
      category_id: category || '',
      status: status || '',
      condition: condition || '',
    });
  };

  const handleReset = () => {
    setSearch('');
    setCategory(null);
    setStatus(null);
    setCondition(null);
    onFilter({
      q: '',
      category_id: '',
      status: '',
      condition: '',
    });
  };

  const hasActiveFilters = Boolean(search || category || status || condition);

  return (
    <form onSubmit={handleSubmit} className="p-4 bg-slate-50/80 rounded-2xl border border-slate-200/80 mb-6 space-y-3">
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        {/* Search */}
        <div>
          <AdminSearchInput
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            onClear={() => setSearch('')}
            placeholder="Search ID, Title, Seller, Brand..."
            disabled={loading}
          />
        </div>

        {/* Category */}
        <div>
          <Dropdown
            value={category}
            options={categoryOptions}
            onChange={(e) => setCategory(e.value)}
            placeholder="All Categories"
            className="w-full text-xs"
            disabled={loading}
          />
        </div>

        {/* Status */}
        <div>
          <Dropdown
            value={status}
            options={statusOptions}
            onChange={(e) => setStatus(e.value)}
            placeholder="All Statuses"
            className="w-full text-xs"
            disabled={loading}
          />
        </div>

        {/* Condition */}
        <div>
          <Dropdown
            value={condition}
            options={conditionOptions}
            onChange={(e) => setCondition(e.value)}
            placeholder="All Conditions"
            className="w-full text-xs"
            disabled={loading}
          />
        </div>
      </div>

      <div className="flex flex-wrap items-center justify-end gap-2 sm:gap-2.5 pt-2 border-t border-slate-200/60">
        {hasActiveFilters && (
          <Button
            type="button"
            label="Reset Filters"
            icon={<X className="w-3.5 h-3.5 mr-1" />}
            size="small"
            onClick={handleReset}
            disabled={loading}
            className="p-button-outlined p-button-secondary text-xs"
          />
        )}
        <Button
          type="submit"
          label="Filter"
          icon={<Filter className="w-3.5 h-3.5 mr-1" />}
          size="small"
          loading={loading}
          className="p-button-primary text-xs"
        />
      </div>
    </form>
  );
}

export default MarketplaceFilterBar;
