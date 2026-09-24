import { useState, useEffect, useCallback } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Package, Plus, Store, ChevronLeft, ChevronRight, RotateCw } from 'lucide-react';
import marketplaceApi from '../../api/marketplaceApi';
import ProductCard from '../../components/marketplace/ProductCard';
import DeleteConfirmModal from '../../components/posts/modals/DeleteConfirmModal';

export function MyProductsPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const statusFilter = searchParams.get('status') || 'available';
  const currentPage = parseInt(searchParams.get('page') || '1', 10);

  const [products, setProducts] = useState([]);
  const [lastPage, setLastPage] = useState(1);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState(null);

  // Deletion modal
  const [deleteProductId, setDeleteProductId] = useState(null);
  const [isDeleting, setIsDeleting] = useState(false);

  const statusTabs = [
    { key: 'all', label: 'All' },
    { key: 'available', label: 'Available' },
    { key: 'sold', label: 'Sold' },
    { key: 'reserved', label: 'Reserved' },
  ];

  useEffect(() => {
    let isMounted = true;
    const params = {
      status: statusFilter,
      page: currentPage,
    };

    marketplaceApi
      .getMyProducts(params)
      .then((data) => {
        if (isMounted) {
          if (data && data.products) {
            setProducts(data.products.data || []);
            setLastPage(data.products.last_page || 1);
          } else {
            setProducts([]);
          }
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load your products.');
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [statusFilter, currentPage]);

  const handleRefresh = useCallback(() => {
    setIsRefreshing(true);
    marketplaceApi
      .getMyProducts({ status: statusFilter, page: currentPage })
      .then((data) => {
        if (data && data.products) {
          setProducts(data.products.data || []);
          setLastPage(data.products.last_page || 1);
        }
      })
      .catch(() => {})
      .finally(() => {
        setIsRefreshing(false);
      });
  }, [statusFilter, currentPage]);

  const handleStatusTabChange = (statusKey) => {
    const newParams = new URLSearchParams(searchParams);
    newParams.set('status', statusKey);
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handlePageChange = (newPage) => {
    if (newPage < 1 || newPage > lastPage) return;
    const newParams = new URLSearchParams(searchParams);
    newParams.set('page', newPage.toString());
    setSearchParams(newParams);
  };

  const handleStatusChange = (productId, newStatus) => {
    setProducts((prev) =>
      prev.map((p) => (p.id === productId ? { ...p, status: newStatus } : p))
    );
  };

  const handleDeleteConfirm = async () => {
    if (!deleteProductId) return;
    setIsDeleting(true);
    try {
      await marketplaceApi.deleteProduct(deleteProductId);
      setProducts((prev) => prev.filter((p) => p.id !== deleteProductId));
      setDeleteProductId(null);
    } catch {
      // Handle error
    } finally {
      setIsDeleting(false);
    }
  };

  return (
    <div className="marketplace-page">
      {/* Header */}
      <header className="marketplace-header card">
        <div className="row align-items-center g-3 w-100 m-0">
          <div className="col-12 col-lg-5 col-xl-6 p-0">
            <div className="marketplace-header__info">
              <div className="marketplace-header__icon-badge">
                <Package size={24} aria-hidden="true" />
              </div>
              <div>
                <h1>My Products</h1>
                <p>Manage your Marketplace product listings and status.</p>
              </div>
            </div>
          </div>
          <div className="col-12 col-lg-7 col-xl-6 p-0 d-flex justify-content-lg-end">
            <div className="marketplace-header__actions">
              <button
                className="member-button member-button--secondary"
                type="button"
                aria-label="Refresh My Products"
                onClick={handleRefresh}
                disabled={isRefreshing}
                style={{ padding: '8px 12px' }}
              >
                <RotateCw size={14} className={isRefreshing ? 'fa-spin' : ''} aria-hidden="true" />
              </button>
              <Link to="/member/marketplace/create" className="member-button member-button--primary">
                <Plus size={15} aria-hidden="true" />
                <span>Create New Listing</span>
              </Link>
              <Link to="/member/marketplace" className="member-button member-button--secondary">
                <Store size={15} aria-hidden="true" />
                <span>Marketplace Home</span>
              </Link>
            </div>
          </div>
        </div>
      </header>

      {/* Status Pills */}
      <nav className="category-nav-bar" aria-label="Filter by Status">
        {statusTabs.map((tab) => (
          <button
            key={tab.key}
            type="button"
            onClick={() => handleStatusTabChange(tab.key)}
            className={`category-pill ${statusFilter === tab.key ? 'is-active' : ''}`}
            style={{ background: 'none', border: 'none', cursor: 'pointer' }}
          >
            <span>{tab.label}</span>
          </button>
        ))}
      </nav>

      {/* Product Grid */}
      {isLoading ? (
        <div className="marketplace-grid" style={{ marginTop: '16px' }}>
          {[1, 2, 3, 4].map((i) => (
            <div key={i} className="product-card card" style={{ minHeight: '260px', opacity: 0.6 }} />
          ))}
        </div>
      ) : error ? (
        <div className="notification-empty" role="alert" style={{ marginTop: '24px' }}>
          <h2>Error Loading Products</h2>
          <p>{error}</p>
          <button
            className="member-button member-button--primary"
            type="button"
            onClick={handleRefresh}
            style={{ marginTop: '12px' }}
          >
            Try Again
          </button>
        </div>
      ) : products.length === 0 ? (
        <div className="notification-empty" style={{ marginTop: '24px' }}>
          <div className="notification-empty__icon">
            <Package size={40} aria-hidden="true" />
          </div>
          <h2>No Products in this View</h2>
          <p>You have no {statusFilter !== 'all' ? statusFilter : ''} listings currently.</p>
          <Link
            to="/member/marketplace/create"
            className="member-button member-button--primary"
            style={{ marginTop: '12px' }}
          >
            Create First Listing
          </Link>
        </div>
      ) : (
        <>
          <div className="marketplace-grid" style={{ marginTop: '16px' }}>
            {products.map((product) => (
              <ProductCard
                key={product.id}
                product={product}
                isOwner={true}
                onStatusChange={handleStatusChange}
                onDelete={(prodId) => setDeleteProductId(prodId)}
              />
            ))}
          </div>

          {lastPage > 1 && (
            <nav
              className="pagination"
              role="navigation"
              aria-label="Pagination"
              style={{ marginTop: '24px', display: 'flex', justifyContent: 'center', gap: '8px' }}
            >
              <button
                className="member-button member-button--secondary"
                type="button"
                onClick={() => handlePageChange(currentPage - 1)}
                disabled={currentPage <= 1}
                aria-label="Previous Page"
              >
                <ChevronLeft size={16} />
              </button>

              {Array.from({ length: lastPage }, (_, i) => i + 1).map((pageNum) => (
                <button
                  key={pageNum}
                  className={`member-button ${pageNum === currentPage ? 'member-button--primary' : 'member-button--secondary'}`}
                  type="button"
                  onClick={() => handlePageChange(pageNum)}
                  style={{ minWidth: '36px' }}
                >
                  {pageNum}
                </button>
              ))}

              <button
                className="member-button member-button--secondary"
                type="button"
                onClick={() => handlePageChange(currentPage + 1)}
                disabled={currentPage >= lastPage}
                aria-label="Next Page"
              >
                <ChevronRight size={16} />
              </button>
            </nav>
          )}
        </>
      )}

      {/* Delete Modal */}
      {deleteProductId && (
        <DeleteConfirmModal
          isOpen={Boolean(deleteProductId)}
          onClose={() => setDeleteProductId(null)}
          onConfirm={handleDeleteConfirm}
          title="Delete Product Listing?"
          message="Are you sure you want to permanently delete this product? This action cannot be undone."
          isDeleting={isDeleting}
        />
      )}
    </div>
  );
}

export default MyProductsPage;
