import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Eye, Trash2 } from 'lucide-react';
import { StatusBadge } from '../../../../components/common/StatusBadge';
import { EmptyState } from '../../../../components/common/EmptyState';
import { confirmHelper } from '../../../../utils/confirmHelper';
import { marketplaceApi } from '../../../../api';
import { useToast } from '../../../../hooks/useToast';

export function MemberMarketplaceTab({
  products = [],
  onRefresh,
}) {
  const { showSuccess, showError } = useToast();
  const [actionLoading, setActionLoading] = useState(false);

  const handleDeleteProduct = (product) => {
    confirmHelper.confirmDelete({
      header: 'Delete Marketplace Product',
      message: `Are you sure you want to delete product "${product.title || product.name}"?`,
      onAccept: async () => {
        setActionLoading(true);
        try {
          await marketplaceApi.deleteProduct(product.id);
          showSuccess(`Product removed from marketplace.`);
          onRefresh();
        } catch (err) {
          showError(err.message || 'Failed to delete product.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  if (products.length === 0) {
    return (
      <EmptyState
        title="No Products Listed"
        description="This member has not listed any marketplace products."
      />
    );
  }

  return (
    <div className="pt-2">
      <div className="overflow-x-auto border border-slate-200 rounded-xl bg-white">
        <table className="w-full text-left text-xs border-collapse">
          <thead>
            <tr className="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
              <th className="py-3 px-4">Product Name</th>
              <th className="py-3 px-4">Price</th>
              <th className="py-3 px-4">Condition</th>
              <th className="py-3 px-4">Status</th>
              <th className="py-3 px-4">Listed On</th>
              <th className="py-3 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {products.map((product, idx) => (
              <tr key={product.id || idx} className="hover:bg-slate-50/80 transition-colors">
                <td className="py-3 px-4 font-bold text-slate-900 whitespace-nowrap">
                  {product.title || product.name}
                </td>
                <td className="py-3 px-4 font-bold text-emerald-600 whitespace-nowrap">
                  ${Number(product.price || 0).toFixed(2)}
                </td>
                <td className="py-3 px-4 whitespace-nowrap">
                  <span className="bg-slate-100 text-slate-700 font-semibold px-2 py-0.5 rounded-md text-[10px] uppercase">
                    {(product.condition || 'N/A').replace('_', ' ')}
                  </span>
                </td>
                <td className="py-3 px-4 whitespace-nowrap">
                  <StatusBadge status={product.status || 'active'} />
                </td>
                <td className="py-3 px-4 text-slate-500 whitespace-nowrap">
                  {product.created_at_human || 'Recently'}
                </td>
                <td className="py-3 px-4 text-right whitespace-nowrap">
                  <div className="inline-flex items-center justify-end gap-1.5 sm:gap-2">
                    <Link
                      to={`/admin/marketplace/${product.id}`}
                      className="p-1.5 rounded-md text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition-colors inline-flex"
                      title="View Product Inspection"
                    >
                      <Eye className="w-3.5 h-3.5" />
                    </Link>

                    <button
                      type="button"
                      onClick={() => handleDeleteProduct(product)}
                      disabled={actionLoading}
                      className="p-1.5 rounded-md text-slate-500 hover:text-red-600 hover:bg-red-50 transition-colors"
                      title="Delete Product"
                    >
                      <Trash2 className="w-3.5 h-3.5" />
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}

export default MemberMarketplaceTab;
