import { Link } from 'react-router-dom';
import { Briefcase } from 'lucide-react';
import { EmptyState } from '../../../components/common/EmptyState';

export function BusinessCategoriesCard({
  categories = [],
  totalBusinessPages = 0,
}) {
  const hasCategories = categories && categories.length > 0;

  return (
    <div className="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden flex flex-col justify-between h-full">
      {/* Header */}
      <div className="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <div>
          <h4 className="text-sm font-bold text-slate-800">MLM Business Categories</h4>
          <p className="text-xs text-slate-400 mt-0.5">Directory distribution by industry</p>
        </div>
        <Link
          to="/admin/business-pages/categories"
          className="text-xs font-semibold text-blue-600 hover:text-blue-700"
        >
          All Categories
        </Link>
      </div>

      {/* Body */}
      <div className="p-5 flex-1 flex flex-col justify-center">
        {!hasCategories ? (
          <EmptyState
            icon={Briefcase}
            title="No Categories Yet"
            description="Category distribution metrics will display here as business pages are published."
          />
        ) : (
          <div className="space-y-4">
            {categories.map((cat, idx) => {
              const count = cat.count || cat.total || 0;
              const pct = totalBusinessPages > 0 ? Math.round((count / totalBusinessPages) * 100) : 0;

              return (
                <div key={idx} className="space-y-1.5">
                  <div className="flex items-center justify-between text-xs">
                    <span className="font-semibold text-slate-800">
                      {cat.category || 'General Business'}
                    </span>
                    <span className="text-slate-500 font-medium">
                      {count} pages ({pct}%)
                    </span>
                  </div>
                  <div className="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                    <div
                      className="bg-blue-600 h-full rounded-full transition-all duration-500"
                      style={{ width: `${Math.min(pct, 100)}%` }}
                    />
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}

export default BusinessCategoriesCard;
