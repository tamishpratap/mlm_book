import { useState, useEffect, useCallback } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Bookmark, Store, ChevronLeft, ChevronRight, RotateCw } from 'lucide-react';
import marketplaceApi from '../../api/marketplaceApi';
import ProductCard from '../../components/marketplace/ProductCard';

export function SavedProductsPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const currentPage = parseInt(searchParams.get('page') || '1', 10);

  const [products, setProducts] = useState([]);
  const [lastPage, setLastPage] = useState(1);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    let isMounted = true;
    marketplaceApi
      .getSavedProducts(currentPage)
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
          setError(err.response?.data?.message || 'Failed to load saved products.');
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [currentPage]);

  const handleRefresh = useCallback(() => {
    setIsRefreshing(true);
    marketplaceApi
      .getSavedProducts(currentPage)
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
  }, [currentPage]);

  const handlePageChange = (newPage) => {
    if (newPage < 1 || newPage > lastPage) return;
    const newParams = new URLSearchParams(searchParams);
    newParams.set('page', newPage.toString());
    setSearchParams(newParams);
  };

  const handleSaveChange = (productId, isSaved) => {
    if (!isSaved) {
      setProducts((prev) => prev.filter((p) => p.id !== productId));
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
                <Bookmark size={24} aria-hidden="true" />
              </div>
              <div>
                <h1>Saved Marketplace Items</h1>
                <p>Products you have bookmarked to view or purchase later.</p>
              </div>
            </div>
          </div>
          <div className="col-12 col-lg-7 col-xl-6 p-0 d-flex justify-content-lg-end">
            <div className="marketplace-header__actions">
              <button
                className="member-button member-button--secondary"
                type="button"
                aria-label="Refresh Saved Items"
                onClick={handleRefresh}
                disabled={isRefreshing}
                style={{ padding: '8px 12px' }}
              >
                <RotateCw size={14} className={isRefreshing ? 'fa-spin' : ''} aria-hidden="true" />
              </button>
              <Link to="/member/marketplace" className="member-button member-button--secondary">
                <Store size={15} aria-hidden="true" />
                <span>Marketplace Home</span>
              </Link>
            </div>
          </div>
        </div>
      </header>

      {/* Product Grid */}
      {isLoading ? (
        <div className="marketplace-grid" style={{ marginTop: '16px' }}>
          {[1, 2, 3, 4].map((i) => (
            <div key={i} className="product-card card" style={{ minHeight: '260px', opacity: 0.6 }} />
          ))}
        </div>
      ) : error ? (
        <div className="notification-empty" role="alert" style={{ marginTop: '24px' }}>
          <h2>Error Loading Saved Products</h2>
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
            <Bookmark size={40} aria-hidden="true" />
          </div>
          <h2>No Saved Products</h2>
          <p>You haven&apos;t saved any marketplace listings yet.</p>
          <Link
            to="/member/marketplace"
            className="member-button member-button--primary"
            style={{ marginTop: '12px' }}
          >
            Browse Marketplace
          </Link>
        </div>
      ) : (
        <>
          <div className="marketplace-grid" style={{ marginTop: '16px' }}>
            {products.map((product) => (
              <ProductCard
                key={product.id}
                product={product}
                onSaveChange={handleSaveChange}
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
    </div>
  );
}

export default SavedProductsPage;
