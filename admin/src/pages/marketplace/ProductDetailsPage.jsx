import { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { ArrowLeft, Star, Trash2, Eye, Bookmark, Flag, User, Check, EyeOff } from 'lucide-react';
import { Button } from 'primereact/button';
import { TabView, TabPanel } from 'primereact/tabview';
import { PageHeader } from '../../components/common/PageHeader';
import { StatusBadge } from '../../components/common/StatusBadge';
import { EmptyState } from '../../components/common/EmptyState';
import { ErrorState } from '../../components/common/ErrorState';
import { ProductDetailsSkeleton } from './components/ProductDetailsSkeleton';
import { confirmHelper } from '../../utils/confirmHelper';
import { useToast } from '../../hooks/useToast';
import { marketplaceApi } from '../../api';

export function ProductDetailsPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { showSuccess, showError } = useToast();

  const [product, setProduct] = useState(null);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [error, setError] = useState(null);
  const [activeTab, setActiveTab] = useState(0);

  const fetchProductDetails = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await marketplaceApi.getProduct(id);
      const data = res?.product || res?.data || res;
      setProduct(data);
    } catch (err) {
      setError(err.message || 'Failed to load product inspection details.');
    } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => {
    let isMounted = true;
    marketplaceApi.getProduct(id)
      .then((res) => {
        if (!isMounted) return;
        const data = res?.product || res?.data || res;
        setProduct(data);
      })
      .catch((err) => {
        if (!isMounted) return;
        setError(err.message || 'Failed to load product inspection details.');
      })
      .finally(() => {
        if (isMounted) setLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [id]);

  const handleToggleFeatured = async () => {
    if (!product) return;
    setActionLoading(true);
    try {
      await marketplaceApi.toggleFeatured(product.id);
      showSuccess(`Product featured status updated.`);
      fetchProductDetails();
    } catch (err) {
      showError(err.message || 'Failed to update featured status.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleToggleStatus = async () => {
    if (!product) return;
    const newStatus = product.status === 'available' ? 'hidden' : 'available';
    setActionLoading(true);
    try {
      await marketplaceApi.updateStatus(product.id, newStatus);
      showSuccess(`Product status updated to ${newStatus}.`);
      fetchProductDetails();
    } catch (err) {
      showError(err.message || 'Failed to update status.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleDelete = () => {
    if (!product) return;
    confirmHelper.confirmDelete({
      header: 'Delete Product Listing',
      message: `Are you sure you want to permanently delete product "${product.title}"?`,
      onAccept: async () => {
        setActionLoading(true);
        try {
          await marketplaceApi.deleteProduct(product.id);
          showSuccess(`Product "${product.title}" deleted.`);
          navigate('/admin/marketplace');
        } catch (err) {
          showError(err.message || 'Failed to delete product.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  if (loading) {
    return <ProductDetailsSkeleton />;
  }

  if (error || !product) {
    return (
      <div className="space-y-6">
        <PageHeader
          title="Product Inspection"
          breadcrumbs={[{ label: 'Marketplace', to: '/admin/marketplace' }, { label: 'Inspection' }]}
        />
        <ErrorState
          title="Product Record Not Found"
          message={error || 'The requested product listing could not be located.'}
          onRetry={fetchProductDetails}
        />
      </div>
    );
  }

  const reports = product.reports || [];
  const mediaList = product.media || [];
  const isAvailable = product.status === 'available';

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <PageHeader
        title={`Product Inspection: ${product.title}`}
        subtitle="Read-only marketplace product inspection, media gallery, seller info & report log."
        breadcrumbs={[
          { label: 'Marketplace', to: '/admin/marketplace' },
          { label: product.title },
        ]}
        actions={
          <div className="flex flex-wrap items-center gap-2.5 sm:gap-3">
            <Button
              label={product.is_featured ? 'Unfeature' : 'Feature'}
              icon={<Star className={`w-3.5 h-3.5 mr-1.5 ${product.is_featured ? 'fill-amber-500 text-amber-500' : ''}`} />}
              size="small"
              onClick={handleToggleFeatured}
              loading={actionLoading}
              className={product.is_featured ? 'p-button-warning text-xs' : 'p-button-outlined p-button-warning text-xs'}
            />

            <Button
              label={isAvailable ? 'Hide Product' : 'Mark Available'}
              icon={isAvailable ? <EyeOff className="w-3.5 h-3.5 mr-1.5" /> : <Check className="w-3.5 h-3.5 mr-1.5" />}
              size="small"
              onClick={handleToggleStatus}
              loading={actionLoading}
              className={isAvailable ? 'p-button-outlined p-button-secondary text-xs' : 'p-button-success text-xs'}
            />

            <Link to="/admin/marketplace" className="inline-flex">
              <Button
                label="Back"
                icon={<ArrowLeft className="w-3.5 h-3.5 mr-1.5" />}
                size="small"
                className="p-button-outlined p-button-secondary text-xs"
              />
            </Link>
          </div>
        }
      />

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left 2 Cols: Main Info, Key Strip, Gallery, Tabs */}
        <div className="lg:col-span-2 space-y-6">
          <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-6 space-y-6">
            {/* Header info */}
            <div className="flex items-start justify-between border-b border-slate-100 pb-4">
              <div>
                <div className="flex items-center space-x-2 flex-wrap gap-y-1">
                  <h3 className="text-lg font-bold text-slate-900">{product.title}</h3>
                  <span className="text-xs font-semibold text-blue-700 bg-blue-50 px-2.5 py-0.5 rounded-full border border-blue-100">
                    {product.category?.name || 'Uncategorized'}
                  </span>
                  {product.is_featured && (
                    <span className="inline-flex items-center text-xs font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                      <Star className="w-3 h-3 mr-1 text-amber-500 fill-amber-500" /> Featured
                    </span>
                  )}
                </div>
                <p className="text-xs text-slate-500 mt-1">
                  Listed by <strong className="text-slate-800">{product.member?.name}</strong> ({product.member?.user_id}) • {product.created_at_human || 'Recently'}
                </p>
              </div>
            </div>

            {/* Price & Commercial Stats Strip */}
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 p-4 bg-slate-50 rounded-xl border border-slate-100 text-center">
              <div className="border-r border-slate-200/80 pr-2">
                <span className="text-[10px] font-bold uppercase text-slate-400 block">Listing Price</span>
                <span className="text-xl font-extrabold text-emerald-600">
                  ${Number(product.price || 0).toFixed(2)}
                </span>
                {product.is_negotiable && (
                  <span className="block text-[10px] text-sky-600 font-bold mt-0.5">Negotiable</span>
                )}
              </div>

              <div className="border-r border-slate-200/80 pr-2">
                <span className="text-[10px] font-bold uppercase text-slate-400 block">Condition</span>
                <span className="text-sm font-bold text-slate-800 capitalize">
                  {(product.condition || 'N/A').replace(/_/g, ' ')}
                </span>
              </div>

              <div className="border-r border-slate-200/80 pr-2">
                <span className="text-[10px] font-bold uppercase text-slate-400 block">Stock Quantity</span>
                <span className="text-sm font-bold text-slate-800">
                  {product.quantity ?? 1}
                </span>
              </div>

              <div>
                <span className="text-[10px] font-bold uppercase text-slate-400 block mb-1">Status</span>
                <StatusBadge status={product.status || 'available'} />
              </div>
            </div>

            {/* Product Media Gallery */}
            <div>
              <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider mb-3">
                Product Media Gallery ({mediaList.length})
              </h4>
              {mediaList.length === 0 ? (
                <div className="p-8 text-center bg-slate-50 rounded-xl text-xs text-slate-400 border border-slate-100">
                  No product photos uploaded.
                </div>
              ) : (
                <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                  {mediaList.map((m, idx) => (
                    <div key={m.id || idx} className="relative h-36 rounded-xl overflow-hidden bg-slate-900 border border-slate-200">
                      <img
                        src={m.media_path}
                        alt={`Media ${idx + 1}`}
                        className="w-full h-full object-cover"
                      />
                      {m.is_featured && (
                        <span className="absolute top-2 left-2 bg-amber-500 text-white font-bold text-[9px] px-1.5 py-0.5 rounded shadow">
                          Main Image
                        </span>
                      )}
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* Tabbed Specifications, Seller & Reports */}
            <div className="border-t border-slate-100 pt-4">
              <TabView activeIndex={activeTab} onTabChange={(e) => setActiveTab(e.index)}>
                {/* Tab 1: Description & Specs */}
                <TabPanel header="Description & Specs">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                    <div className="space-y-2 text-xs bg-slate-50 p-4 rounded-xl border border-slate-100">
                      <h5 className="font-bold text-slate-800 uppercase text-[11px] mb-2">Specifications</h5>
                      <div className="flex justify-between py-1 border-b border-slate-200/50">
                        <span className="text-slate-500">Brand:</span>
                        <strong className="text-slate-800">{product.brand || 'Unspecified'}</strong>
                      </div>
                      <div className="flex justify-between py-1 border-b border-slate-200/50">
                        <span className="text-slate-500">Category:</span>
                        <span className="text-slate-800">{product.category?.name || 'N/A'}</span>
                      </div>
                      <div className="flex justify-between py-1 border-b border-slate-200/50">
                        <span className="text-slate-500">Sub Category:</span>
                        <span className="text-slate-800">{product.subCategory?.name || 'N/A'}</span>
                      </div>
                      <div className="flex justify-between py-1 border-b border-slate-200/50">
                        <span className="text-slate-500">Location:</span>
                        <span className="text-slate-800">{product.location || 'Not specified'}</span>
                      </div>
                      <div className="flex justify-between pt-1">
                        <span className="text-slate-500">Tags:</span>
                        <span className="text-slate-800">{product.tags || 'None'}</span>
                      </div>
                    </div>

                    <div>
                      <h5 className="font-bold text-slate-800 uppercase text-[11px] mb-2">Description</h5>
                      <p className="text-xs text-slate-700 bg-slate-50 p-4 rounded-xl border border-slate-100 leading-relaxed whitespace-pre-line">
                        {product.description || 'No description provided.'}
                      </p>
                    </div>
                  </div>
                </TabPanel>

                {/* Tab 2: Seller Info */}
                <TabPanel header="Seller Info">
                  <div className="p-4 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-between flex-wrap gap-4 pt-2">
                    <div className="flex items-center space-x-3">
                      <div className="w-12 h-12 rounded-full bg-blue-100 text-blue-600 font-bold flex items-center justify-center overflow-hidden shrink-0">
                        {product.member?.avatar_url ? (
                          <img src={product.member.avatar_url} alt={product.member.name} className="w-full h-full object-cover" />
                        ) : (
                          (product.member?.name || 'M').charAt(0).toUpperCase()
                        )}
                      </div>
                      <div>
                        <h5 className="text-sm font-bold text-slate-900">{product.member?.name || 'Deleted Member'}</h5>
                        <code className="text-xs text-blue-600 font-mono">{product.member?.user_id}</code>
                        <span className="text-xs text-slate-500 block mt-0.5">
                          {product.member?.email} • {product.member?.phone || 'Phone not provided'}
                        </span>
                      </div>
                    </div>

                    {product.member && (
                      <Link to={`/admin/members/${product.member.id}`}>
                        <Button
                          label="View Member Profile"
                          icon={<User className="w-3.5 h-3.5 mr-1" />}
                          size="small"
                          className="p-button-outlined p-button-primary text-xs"
                        />
                      </Link>
                    )}
                  </div>
                </TabPanel>

                {/* Tab 3: Reports Queue */}
                <TabPanel header={`Reports Queue (${reports.length})`}>
                  {reports.length === 0 ? (
                    <EmptyState
                      title="No Reports Filed"
                      description="This product listing has a clean moderation record."
                    />
                  ) : (
                    <div className="overflow-x-auto border border-slate-200 rounded-xl bg-white mt-2">
                      <table className="w-full text-left text-xs border-collapse">
                        <thead>
                          <tr className="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th className="py-2.5 px-4">Report ID</th>
                            <th className="py-2.5 px-4">Reporter</th>
                            <th className="py-2.5 px-4">Reason</th>
                            <th className="py-2.5 px-4">Notes</th>
                            <th className="py-2.5 px-4">Date</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                          {reports.map((report) => (
                            <tr key={report.id} className="hover:bg-slate-50/80">
                              <td className="py-2.5 px-4 font-mono font-bold text-slate-800">#{report.id}</td>
                              <td className="py-2.5 px-4 font-semibold text-slate-900">{report.member?.name || 'Anonymous'}</td>
                              <td className="py-2.5 px-4 text-red-600 font-bold">{report.reason}</td>
                              <td className="py-2.5 px-4 text-slate-600 max-w-xs truncate">{report.notes || 'N/A'}</td>
                              <td className="py-2.5 px-4 text-slate-500">{report.created_at_human || 'Recently'}</td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  )}
                </TabPanel>
              </TabView>
            </div>
          </div>
        </div>

        {/* Right Col: Product Analytics & Moderation Actions */}
        <div className="space-y-6">
          {/* Analytics Card */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 space-y-4">
            <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-2">
              Product Analytics
            </h4>
            <div className="space-y-3 text-xs">
              <div className="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl">
                <span className="text-slate-600 flex items-center">
                  <Eye className="w-3.5 h-3.5 mr-2 text-blue-600" /> Page Views
                </span>
                <strong className="text-slate-900">{Number(product.views_count || 0).toLocaleString()}</strong>
              </div>

              <div className="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl">
                <span className="text-slate-600 flex items-center">
                  <Bookmark className="w-3.5 h-3.5 mr-2 text-sky-600" /> Saved / Wishlist
                </span>
                <strong className="text-slate-900">{Number(product.saved_products_count || 0).toLocaleString()}</strong>
              </div>

              <div className="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl">
                <span className="text-slate-600 flex items-center">
                  <Flag className="w-3.5 h-3.5 mr-2 text-red-600" /> Reports Count
                </span>
                <strong className="text-red-600">{reports.length}</strong>
              </div>
            </div>
          </div>

          {/* Moderation Actions Card */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 space-y-3">
            <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-2">
              Moderation Actions
            </h4>
            <Button
              label="Delete Listing"
              icon={<Trash2 className="w-3.5 h-3.5 mr-1.5" />}
              severity="danger"
              size="small"
              onClick={handleDelete}
              loading={actionLoading}
              className="w-full text-xs"
            />
          </div>
        </div>
      </div>
    </div>
  );
}

export default ProductDetailsPage;
