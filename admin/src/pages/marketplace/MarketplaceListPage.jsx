import { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { Download, Eye, Star, Trash2 } from 'lucide-react';
import { Button } from 'primereact/button';
import { Checkbox } from 'primereact/checkbox';
import { Paginator } from 'primereact/paginator';
import { PageHeader } from '../../components/common/PageHeader';
import { StatusBadge } from '../../components/common/StatusBadge';
import { EmptyState } from '../../components/common/EmptyState';
import { ErrorState } from '../../components/common/ErrorState';
import { confirmHelper } from '../../utils/confirmHelper';
import { useToast } from '../../hooks/useToast';
import { marketplaceApi, downloadBlobFromResponse } from '../../api';

// Subcomponents
import { MarketplaceStatsCards } from './components/MarketplaceStatsCards';
import { MarketplaceFilterBar } from './components/MarketplaceFilterBar';
import { MarketplaceBulkActionsBar } from './components/MarketplaceBulkActionsBar';

export function MarketplaceListPage() {
  const { showSuccess, showError } = useToast();

  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [stats, setStats] = useState({
    totalCount: 0,
    activeCount: 0,
    featuredCount: 0,
    totalSellersCount: 0,
  });

  const [filters, setFilters] = useState({});
  const [pagination, setPagination] = useState({
    page: 1,
    perPage: 15,
    total: 0,
  });

  const [selectedIds, setSelectedIds] = useState([]);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [exporting, setExporting] = useState(false);
  const [error, setError] = useState(null);

  const fetchProducts = useCallback(async (page = 1, currentFilters = filters) => {
    setLoading(true);
    setError(null);
    try {
      const params = {
        page,
        ...currentFilters,
      };
      const res = await marketplaceApi.getProducts(params);
      const productData = res?.products?.data || res?.data?.products?.data || res?.data || (Array.isArray(res) ? res : []);
      const paginatorData = res?.products || res?.data?.products || {};

      setProducts(productData);
      setCategories(res?.categories || res?.data?.categories || []);
      setStats({
        totalCount: res?.totalCount ?? paginatorData?.total ?? productData.length,
        activeCount: res?.activeCount ?? productData.filter((p) => p.status === 'available').length,
        featuredCount: res?.featuredCount ?? productData.filter((p) => p.is_featured).length,
        totalSellersCount: res?.totalSellersCount ?? 0,
      });

      setPagination({
        page: paginatorData?.current_page || page,
        perPage: paginatorData?.per_page || 15,
        total: paginatorData?.total || productData.length,
      });

      setSelectedIds([]);
    } catch (err) {
      setError(err.message || 'Failed to load marketplace products.');
    } finally {
      setLoading(false);
    }
  }, [filters]);

  useEffect(() => {
    let isMounted = true;
    marketplaceApi.getProducts({ page: 1 })
      .then((res) => {
        if (!isMounted) return;
        const productData = res?.products?.data || res?.data?.products?.data || res?.data || (Array.isArray(res) ? res : []);
        const paginatorData = res?.products || res?.data?.products || {};

        setProducts(productData);
        setCategories(res?.categories || res?.data?.categories || []);
        setStats({
          totalCount: res?.totalCount ?? paginatorData?.total ?? productData.length,
          activeCount: res?.activeCount ?? productData.filter((p) => p.status === 'available').length,
          featuredCount: res?.featuredCount ?? productData.filter((p) => p.is_featured).length,
          totalSellersCount: res?.totalSellersCount ?? 0,
        });

        setPagination({
          page: paginatorData?.current_page || 1,
          perPage: paginatorData?.per_page || 15,
          total: paginatorData?.total || productData.length,
        });
      })
      .catch((err) => {
        if (!isMounted) return;
        setError(err.message || 'Failed to load marketplace products.');
      })
      .finally(() => {
        if (isMounted) setLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, []);

  const handleFilter = (newFilters) => {
    setFilters(newFilters);
    fetchProducts(1, newFilters);
  };

  const handlePageChange = (e) => {
    const newPage = e.page + 1;
    fetchProducts(newPage, filters);
  };

  const handleToggleFeatured = async (product) => {
    setActionLoading(true);
    try {
      await marketplaceApi.toggleFeatured(product.id);
      showSuccess(`Product "${product.title}" featured status updated.`);
      fetchProducts(pagination.page, filters);
    } catch (err) {
      showError(err.message || 'Failed to update featured status.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleDeleteProduct = (product) => {
    confirmHelper.confirmDelete({
      header: 'Delete Product Listing',
      message: `Are you sure you want to permanently delete product "${product.title}"?`,
      onAccept: async () => {
        setActionLoading(true);
        try {
          await marketplaceApi.deleteProduct(product.id);
          showSuccess(`Product "${product.title}" deleted.`);
          fetchProducts(pagination.page, filters);
        } catch (err) {
          showError(err.message || 'Failed to delete product.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  const handleBulkAction = async (action) => {
    setActionLoading(true);
    try {
      await marketplaceApi.bulkAction(action, selectedIds);
      showSuccess(`Applied "${action}" to ${selectedIds.length} products.`);
      fetchProducts(pagination.page, filters);
    } catch (err) {
      showError(err.message || 'Failed to execute bulk action.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleExportCsv = async () => {
    if (exporting) return;
    setExporting(true);
    try {
      const blob = await marketplaceApi.exportCsv(filters);
      await downloadBlobFromResponse(blob, 'marketplace_products_export.csv');
      showSuccess('Marketplace products CSV downloaded.');
    } catch (err) {
      showError(err.message || 'Failed to export marketplace products.');
    } finally {
      setExporting(false);
    }
  };

  const handleSelectAll = () => {
    if (selectedIds.length === products.length) {
      setSelectedIds([]);
    } else {
      setSelectedIds(products.map((p) => p.id));
    }
  };

  const handleSelectRow = (id) => {
    setSelectedIds((prev) =>
      prev.includes(id) ? prev.filter((item) => item !== id) : [...prev, id]
    );
  };

  const allSelected = products.length > 0 && selectedIds.length === products.length;

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <PageHeader
        title="Marketplace Management"
        subtitle="Monitor, filter, and moderate platform marketplace products."
        breadcrumbs={[{ label: 'Commerce', to: '/admin/marketplace' }, { label: 'Marketplace Directory' }]}
        actions={
          <Button
            label={exporting ? 'Exporting...' : 'Export CSV'}
            icon={exporting ? 'pi pi-spin pi-spinner' : <Download className="w-3.5 h-3.5 mr-1.5" />}
            size="small"
            onClick={handleExportCsv}
            disabled={exporting}
            loading={exporting}
            className="p-button-outlined p-button-secondary text-xs"
          />
        }
      />

      {error ? (
        <ErrorState
          title="Failed to Load Products"
          message={error}
          onRetry={() => fetchProducts(pagination.page, filters)}
        />
      ) : (
        <>
          {/* Summary Metric Cards */}
          <MarketplaceStatsCards stats={stats} loading={loading} />

          {/* Directory Card */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 sm:p-6">
            {/* Filter Bar */}
            <MarketplaceFilterBar
              categories={categories}
              onFilter={handleFilter}
              loading={loading}
            />

            {/* Bulk Action Bar */}
            <MarketplaceBulkActionsBar
              selectedCount={selectedIds.length}
              onExecuteBulkAction={handleBulkAction}
              loading={actionLoading}
            />

            {/* Table */}
            {products.length === 0 && !loading ? (
              <EmptyState
                title="No Products Found"
                description="No marketplace product listings match your search or filter criteria."
              />
            ) : (
              <div className="overflow-x-auto border border-slate-200 rounded-2xl bg-white">
                <table className="w-full text-left text-xs border-collapse">
                  <thead>
                    <tr className="bg-slate-50/90 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                      <th className="py-3 px-4 w-10 text-center">
                        <Checkbox
                          checked={allSelected}
                          onChange={handleSelectAll}
                          disabled={products.length === 0 || loading}
                        />
                      </th>
                      <th className="py-3 px-3 w-14">Thumb</th>
                      <th className="py-3 px-4 min-w-[200px]">Product Title & Category</th>
                      <th className="py-3 px-4">Seller</th>
                      <th className="py-3 px-4">Price</th>
                      <th className="py-3 px-4">Condition</th>
                      <th className="py-3 px-4 text-center">Featured</th>
                      <th className="py-3 px-4 text-center">Reports</th>
                      <th className="py-3 px-4">Status</th>
                      <th className="py-3 px-4">Created Date</th>
                      <th className="py-3 px-4 text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {products.map((product) => {
                      const isSelected = selectedIds.includes(product.id);
                      const thumb = product.media?.[0]?.media_path || product.thumbnail_url;
                      const conditionLabel = (product.condition || 'N/A').replace(/_/g, ' ');

                      return (
                        <tr
                          key={product.id}
                          className={`hover:bg-slate-50/80 transition-colors ${isSelected ? 'bg-blue-50/40' : ''}`}
                        >
                          <td className="py-3 px-4 text-center">
                            <Checkbox
                              checked={isSelected}
                              onChange={() => handleSelectRow(product.id)}
                            />
                          </td>

                          {/* Thumb */}
                          <td className="py-3 px-3">
                            <div className="w-10 h-10 rounded-lg overflow-hidden bg-slate-100 border border-slate-200 shrink-0">
                              {thumb ? (
                                <img
                                  src={thumb}
                                  alt={product.title}
                                  className="w-full h-full object-cover"
                                  onError={(e) => {
                                    e.target.style.display = 'none';
                                  }}
                                />
                              ) : (
                                <div className="w-full h-full flex items-center justify-center text-slate-400 text-[10px] font-bold">
                                  N/A
                                </div>
                              )}
                            </div>
                          </td>

                          {/* Title & Category */}
                          <td className="py-3 px-4 max-w-xs">
                            <Link
                              to={`/admin/marketplace/${product.id}`}
                              className="font-bold text-slate-900 hover:text-blue-600 truncate block text-xs"
                            >
                              {product.title}
                            </Link>
                            <span className="inline-block text-[10px] font-semibold text-blue-700 bg-blue-50 px-1.5 py-0.2 rounded mt-0.5 border border-blue-100">
                              {product.category?.name || 'Uncategorized'}
                            </span>
                          </td>

                          {/* Seller */}
                          <td className="py-3 px-4 whitespace-nowrap">
                            <div className="flex items-center space-x-2">
                              <div className="w-7 h-7 rounded-full bg-slate-200 overflow-hidden shrink-0">
                                {product.member?.avatar_url ? (
                                  <img
                                    src={product.member.avatar_url}
                                    alt={product.member.name}
                                    className="w-full h-full object-cover"
                                  />
                                ) : (
                                  <div className="w-full h-full flex items-center justify-center text-[10px] font-bold text-slate-600">
                                    {(product.member?.name || 'M').charAt(0).toUpperCase()}
                                  </div>
                                )}
                              </div>
                              <div>
                                <strong className="text-slate-900 block text-xs">
                                  {product.member?.name || 'N/A'}
                                </strong>
                                <code className="text-[10px] font-mono text-slate-400">
                                  {product.member?.user_id}
                                </code>
                              </div>
                            </div>
                          </td>

                          {/* Price */}
                          <td className="py-3 px-4 font-extrabold text-emerald-600 whitespace-nowrap">
                            ${Number(product.price || 0).toFixed(2)}
                          </td>

                          {/* Condition */}
                          <td className="py-3 px-4 whitespace-nowrap capitalize text-slate-600 font-medium">
                            {conditionLabel}
                          </td>

                          {/* Featured */}
                          <td className="py-3 px-4 text-center whitespace-nowrap">
                            {product.is_featured ? (
                              <span className="inline-flex items-center text-[10px] font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                                <Star className="w-2.5 h-2.5 mr-1 text-amber-500 fill-amber-500" />
                                Featured
                              </span>
                            ) : (
                              <span className="text-[11px] text-slate-400">Standard</span>
                            )}
                          </td>

                          {/* Reports */}
                          <td className="py-3 px-4 text-center whitespace-nowrap">
                            {product.reports_count > 0 ? (
                              <span className="inline-block text-[10px] font-bold text-red-700 bg-red-50 px-2 py-0.5 rounded-full border border-red-200">
                                {product.reports_count} Reports
                              </span>
                            ) : (
                              <span className="text-[11px] text-slate-400">0</span>
                            )}
                          </td>

                          {/* Status */}
                          <td className="py-3 px-4 whitespace-nowrap">
                            <StatusBadge status={product.status || 'available'} />
                          </td>

                          {/* Created Date */}
                          <td className="py-3 px-4 text-slate-500 whitespace-nowrap">
                            {product.created_at_human || 'Recently'}
                          </td>

                          {/* Actions */}
                          <td className="py-3 px-4 text-right whitespace-nowrap">
                            <div className="inline-flex items-center justify-end gap-1.5 sm:gap-2">
                              <Link
                                to={`/admin/marketplace/${product.id}`}
                                className="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition-colors inline-flex"
                                title="View Product Details"
                              >
                                <Eye className="w-3.5 h-3.5" />
                              </Link>

                              <button
                                type="button"
                                onClick={() => handleToggleFeatured(product)}
                                disabled={actionLoading}
                                className={`p-1.5 rounded-lg transition-colors ${product.is_featured ? 'text-amber-500 hover:bg-amber-50' : 'text-slate-400 hover:text-amber-500 hover:bg-amber-50'}`}
                                title={product.is_featured ? 'Unfeature' : 'Feature'}
                              >
                                <Star className={`w-3.5 h-3.5 ${product.is_featured ? 'fill-amber-500' : ''}`} />
                              </button>

                              <button
                                type="button"
                                onClick={() => handleDeleteProduct(product)}
                                disabled={actionLoading}
                                className="p-1.5 rounded-lg text-slate-500 hover:text-red-600 hover:bg-red-50 transition-colors"
                                title="Delete Product"
                              >
                                <Trash2 className="w-3.5 h-3.5" />
                              </button>
                            </div>
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            )}

            {/* Pagination */}
            {pagination.total > pagination.perPage && (
              <div className="pt-4 border-t border-slate-100 flex justify-end">
                <Paginator
                  first={(pagination.page - 1) * pagination.perPage}
                  rows={pagination.perPage}
                  totalRecords={pagination.total}
                  onPageChange={handlePageChange}
                  className="text-xs"
                />
              </div>
            )}
          </div>
        </>
      )}
    </div>
  );
}

export default MarketplaceListPage;
