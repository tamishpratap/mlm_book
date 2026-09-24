import { Link } from 'react-router-dom';
import { Briefcase, Eye, ArrowRight } from 'lucide-react';
import { StatusBadge } from '../../../components/common/StatusBadge';

export function RecentBusinessPagesTable({ pages = [] }) {
  const hasPages = pages && pages.length > 0;

  return (
    <div className="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden flex flex-col justify-between h-full">
      {/* Header */}
      <div className="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center">
          <Briefcase className="w-4 h-4 text-indigo-600 mr-2" /> Recent Business Pages
        </h4>
        <Link
          to="/admin/business-pages"
          className="text-xs font-semibold text-blue-600 hover:text-blue-700 inline-flex items-center"
        >
          View All <ArrowRight className="w-3.5 h-3.5 ml-1" />
        </Link>
      </div>

      {/* Body Table */}
      <div className="overflow-x-auto flex-1">
        {!hasPages ? (
          <div className="p-8 text-center text-xs text-slate-400">
            No business pages created yet.
          </div>
        ) : (
          <table className="w-full text-left border-collapse text-xs">
            <thead>
              <tr className="bg-slate-50/80 border-b border-slate-100 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                <th className="py-2.5 px-4">Page Name</th>
                <th className="py-2.5 px-4">Category</th>
                <th className="py-2.5 px-4">Verification</th>
                <th className="py-2.5 px-4">Created</th>
                <th className="py-2.5 px-4 text-right">Action</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {pages.map((page, idx) => {
                const isVerified = Boolean(page.is_verified);
                const pageName = page.page_name || page.name || 'Untitled Business';

                return (
                  <tr key={page.id || idx} className="hover:bg-slate-50/80 transition-colors">
                    <td className="py-2.5 px-4">
                      <span className="font-semibold text-slate-800 block truncate max-w-[140px] sm:max-w-[180px]">
                        {pageName}
                      </span>
                    </td>

                    <td className="py-2.5 px-4">
                      <span className="bg-blue-50 text-blue-700 font-medium px-2 py-0.5 rounded-sm text-[11px] border border-blue-200/50">
                        {page.category || 'General'}
                      </span>
                    </td>

                    <td className="py-2.5 px-4">
                      <StatusBadge
                        status={isVerified ? 'verified' : 'inactive'}
                        label={isVerified ? 'Verified' : 'Unverified'}
                      />
                    </td>

                    <td className="py-2.5 px-4 text-slate-500 whitespace-nowrap">
                      {page.created_at_human || page.created || 'Recently'}
                    </td>

                    <td className="py-2.5 px-4 text-right">
                      <Link
                        to={`/admin/business-pages/${page.id}`}
                        className="inline-flex p-1.5 rounded-md text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                        title="View Details"
                      >
                        <Eye className="w-4 h-4" />
                      </Link>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        )}
      </div>
    </div>
  );
}

export default RecentBusinessPagesTable;
