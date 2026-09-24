import { useState, useEffect, useCallback } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import {
  Store,
  Plus,
  Package,
  Bookmark,
  Search,
  X,
  Grid,
  Sparkles,
  ArrowUpDown,
  Layers,
  ChevronLeft,
  ChevronRight,
  RotateCw,
  Box,
} from 'lucide-react';
import marketplaceApi from '../../api/marketplaceApi';
import ProductCard from '../../components/marketplace/ProductCard';

export function MarketplacePage() {
  const [searchParams, setSearchParams] = useSearchParams();

  const searchQuery = searchParams.get('search') || '';
  const categoryQuery = searchParams.get('category') || '';
  const conditionQuery = searchParams.get('condition') || '';
  const sortQuery = searchParams.get('sort') || 'newest';
  const pageQuery = parseInt(searchParams.get('page') || '1', 10);

  const [searchInput, setSearchInput] = useState(searchQuery);
  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [lastPage, setLastPage] = useState(1);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    let isMounted = true;
    const params = {
      search: searchQuery || undefined,
      category: categoryQuery || undefined,
      condition: conditionQuery || undefined,
      sort: sortQuery !== 'newest' ? sortQuery : undefined,
      page: pageQuery,
    };

    marketplaceApi
      .getProducts(params)
      .then((data) => {
        if (isMounted) {
          if (data && data.products) {
            const list = data.products.data || [];
            setProducts(list);
            setLastPage(data.products.last_page || 1);
            if (Array.isArray(data.categories)) {
              setCategories(data.categories);
            }
          } else {
            setProducts([]);
          }
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load marketplace listings.');
        }
      })
      .finally(() => {
        if (isMounted) {
          setIsLoading(false);
        }
      });

    return () => {
      isMounted = false;
    };
  }, [searchQuery, categoryQuery, conditionQuery, sortQuery, pageQuery]);

  const handleRefresh = useCallback(() => {
    setIsRefreshing(true);
    const params = {
      search: searchQuery || undefined,
      category: categoryQuery || undefined,
      condition: conditionQuery || undefined,
      sort: sortQuery !== 'newest' ? sortQuery : undefined,
      page: pageQuery,
    };

    marketplaceApi
      .getProducts(params)
      .then((data) => {
        if (data && data.products) {
          const list = data.products.data || [];
          setProducts(list);
          setLastPage(data.products.last_page || 1);
          if (Array.isArray(data.categories)) {
            setCategories(data.categories);
          }
        }
      })
      .catch(() => {})
      .finally(() => {
        setIsRefreshing(false);
      });
  }, [searchQuery, categoryQuery, conditionQuery, sortQuery, pageQuery]);

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    const newParams = new URLSearchParams(searchParams);
    if (searchInput.trim()) {
      newParams.set('search', searchInput.trim());
    } else {
      newParams.delete('search');
    }
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handleClearSearch = () => {
    setSearchInput('');
    const newParams = new URLSearchParams(searchParams);
    newParams.delete('search');
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handleCategoryChange = (catSlug) => {
    const newParams = new URLSearchParams(searchParams);
    if (catSlug) {
      newParams.set('category', catSlug);
    } else {
      newParams.delete('category');
    }
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handleConditionChange = (e) => {
    const val = e.target.value;
    const newParams = new URLSearchParams(searchParams);
    if (val) {
      newParams.set('condition', val);
    } else {
      newParams.delete('condition');
    }
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handleSortChange = (e) => {
    const val = e.target.value;
    const newParams = new URLSearchParams(searchParams);
    if (val && val !== 'newest') {
      newParams.set('sort', val);
    } else {
      newParams.delete('sort');
    }
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handlePageChange = (newPage) => {
    if (newPage < 1 || newPage > lastPage) return;
    const newParams = new URLSearchParams(searchParams);
    newParams.set('page', newPage.toString());
    setSearchParams(newParams);
  };

  return (
    <div className="marketplace-page">
      {/* Header & Banner */}
      <header className="marketplace-header card">
        <div className="row align-items-center g-3 w-100 m-0">
          <div className="col-12 col-lg-5 col-xl-6 p-0">
            <div className="marketplace-header__info">
              <div className="marketplace-header__icon-badge">
                <Store size={24} aria-hidden="true" />
              </div>
              <div>
                <h1>Marketplace</h1>
                <p>Buy & sell items locally with MLM Book community members.</p>
              </div>
            </div>
          </div>
          <div className="col-12 col-lg-7 col-xl-6 p-0 d-flex justify-content-lg-end">
            <div className="marketplace-header__actions">
              <button
                className="member-button member-button--secondary"
                type="button"
                aria-label="Refresh Listings"
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
              <Link to="/member/marketplace/my-products" className="member-button member-button--secondary">
                <Package size={15} aria-hidden="true" />
                <span>My Products</span>
              </Link>
              <Link to="/member/marketplace/saved" className="member-button member-button--secondary">
                <Bookmark size={15} aria-hidden="true" />
                <span>Saved Items</span>
              </Link>
            </div>
          </div>
        </div>
      </header>

      {/* Toolbar & Filters */}
      <div className="marketplace-toolbar card">
        <form className="marketplace-search-form" onSubmit={handleSearchSubmit}>
          <div className="search-box">
            <Search size={18} aria-hidden="true" />
            <input
              type="search"
              name="search"
              value={searchInput}
              onChange={(e) => setSearchInput(e.target.value)}
              placeholder="Search Marketplace items, electronics, vehicles..."
              aria-label="Search Marketplace"
            />
            {searchInput && (
              <button
                className="search-box__clear"
                type="button"
                aria-label="Clear search"
                onClick={handleClearSearch}
                style={{ position: 'absolute', right: '16px', background: 'none', border: 'none', cursor: 'pointer' }}
              >
                <X size={16} />
              </button>
            )}
          </div>

          <div className="row g-3 marketplace-filters-row">
            <div className="col-12 col-md-4">
              <div className="select-wrapper">
                <Grid size={16} aria-hidden="true" className="select-icon" />
                <select
                  name="category"
                  value={categoryQuery}
                  onChange={(e) => handleCategoryChange(e.target.value)}
                  aria-label="Select Category"
                >
                  <option value="">All Categories</option>
                  {categories.map((cat) => (
                    <option key={cat.id} value={cat.slug}>
                      {cat.name}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            <div className="col-12 col-md-4">
              <div className="select-wrapper">
                <Sparkles size={16} aria-hidden="true" className="select-icon" />
                <select
                  name="condition"
                  value={conditionQuery}
                  onChange={handleConditionChange}
                  aria-label="Select Condition"
                >
                  <option value="">Any Condition</option>
                  <option value="new">Brand New</option>
                  <option value="like_new">Like New</option>
                  <option value="good">Good Condition</option>
                  <option value="fair">Fair Condition</option>
                </select>
              </div>
            </div>

            <div className="col-12 col-md-4">
              <div className="select-wrapper">
                <ArrowUpDown size={16} aria-hidden="true" className="select-icon" />
                <select
                  name="sort"
                  value={sortQuery}
                  onChange={handleSortChange}
                  aria-label="Sort Results"
                >
                  <option value="newest">Newest First</option>
                  <option value="price_low">Price: Low to High</option>
                  <option value="price_high">Price: High to Low</option>
                  <option value="most_viewed">Most Popular</option>
                </select>
              </div>
            </div>
          </div>
        </form>
      </div>

      {/* Category Pills Bar */}
      <nav className="category-nav-bar" aria-label="Product Categories">
        <button
          type="button"
          onClick={() => handleCategoryChange('')}
          className={`category-pill ${!categoryQuery ? 'is-active' : ''}`}
          style={{ background: 'none', border: 'none', cursor: 'pointer' }}
        >
          <Layers size={15} aria-hidden="true" />
          <span>All Items</span>
        </button>
        {categories.map((cat) => (
          <button
            key={cat.id}
            type="button"
            onClick={() => handleCategoryChange(cat.slug)}
            className={`category-pill ${categoryQuery === cat.slug ? 'is-active' : ''}`}
            style={{ background: 'none', border: 'none', cursor: 'pointer' }}
          >
            <Box size={15} aria-hidden="true" />
            <span>{cat.name}</span>
          </button>
        ))}
      </nav>

      {/* Product Grid */}
      {isLoading ? (
        <div className="marketplace-grid" style={{ marginTop: '16px' }}>
          {[1, 2, 3, 4, 5, 6, 7, 8].map((i) => (
            <div key={i} className="product-card card" style={{ minHeight: '260px', opacity: 0.6 }} />
          ))}
        </div>
      ) : error ? (
        <div className="notification-empty" role="alert" style={{ marginTop: '24px' }}>
          <h2>Error Loading Marketplace</h2>
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
            <Store size={40} aria-hidden="true" />
          </div>
          <h2>No Products Found</h2>
          <p>Try adjusting your search filters or be the first to list an item in this category.</p>
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
              <ProductCard key={product.id} product={product} />
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
                onClick={() => handlePageChange(pageQuery - 1)}
                disabled={pageQuery <= 1}
                aria-label="Previous Page"
              >
                <ChevronLeft size={16} />
              </button>

              {Array.from({ length: lastPage }, (_, i) => i + 1).map((pageNum) => (
                <button
                  key={pageNum}
                  className={`member-button ${pageNum === pageQuery ? 'member-button--primary' : 'member-button--secondary'}`}
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
                onClick={() => handlePageChange(pageQuery + 1)}
                disabled={pageQuery >= lastPage}
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

export default MarketplacePage;
