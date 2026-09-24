import { useState, useEffect, useCallback } from 'react';
import { useParams, Link, useSearchParams } from 'react-router-dom';
import { ArrowLeft, Tag, PlusCircle } from 'lucide-react';
import businessApi from '../../api/businessApi';
import BusinessCard from '../../components/business/BusinessCard';

export function BusinessCategoryPage() {
  const { category: categorySlug } = useParams();
  const [searchParams, setSearchParams] = useSearchParams();

  const qQuery = searchParams.get('q') || '';
  const sortQuery = searchParams.get('sort') || 'popular';
  const currentPage = parseInt(searchParams.get('page') || '1', 10);

  const [qInput, setQInput] = useState(qQuery);
  const [sort, setSort] = useState(sortQuery);

  const [data, setData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  const fetchCategoryPages = useCallback(() => {
    if (!categorySlug) return Promise.resolve(null);
    const params = {
      q: qQuery || undefined,
      sort: sortQuery || undefined,
      page: currentPage,
    };
    return businessApi.getDirectoryCategory(categorySlug, params);
  }, [categorySlug, qQuery, sortQuery, currentPage]);

  useEffect(() => {
    let isMounted = true;

    fetchCategoryPages()
      .then((res) => {
        if (isMounted && res) {
          setData(res);
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Category not found.');
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [fetchCategoryPages]);

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    const newParams = new URLSearchParams();
    if (qInput.trim()) newParams.set('q', qInput.trim());
    if (sort) newParams.set('sort', sort);
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handleSortChange = (newSort) => {
    setSort(newSort);
    const newParams = new URLSearchParams(searchParams);
    if (newSort) newParams.set('sort', newSort);
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handlePageChange = (newPage) => {
    const newParams = new URLSearchParams(searchParams);
    newParams.set('page', newPage.toString());
    setSearchParams(newParams);
  };

  if (isLoading) {
    return (
      <div style={{ textAlign: 'center', padding: '60px', color: 'var(--color-text-secondary)' }}>
        Loading category businesses...
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="card" style={{ maxWidth: '800px', margin: '40px auto', padding: '32px', textAlign: 'center' }}>
        <h2 style={{ fontSize: '18px', color: '#dc2626', marginBottom: '8px' }}>Category Not Found</h2>
        <p style={{ color: 'var(--color-text-secondary)', marginBottom: '20px' }}>
          {error || 'This business category does not exist.'}
        </p>
        <Link to="/member/business-directory" className="member-button member-button--primary">
          Back to Directory
        </Link>
      </div>
    );
  }

  const categoryName = data.category_name || categorySlug;
  const pagesList = data.pages?.data || [];
  const lastPage = data.pages?.last_page || 1;
  const total = data.pages?.total || 0;

  return (
    <div className="biz-page biz-category-page" style={{ padding: '24px 16px' }}>
      {/* Category Hero Banner */}
      <div
        className="biz-hero biz-category-hero"
        style={{
          padding: '28px',
          borderRadius: '18px',
          background: 'linear-gradient(135deg, #4f7df3 0%, #8a2be2 100%)',
          color: '#fff',
          marginBottom: '24px',
        }}
      >
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '16px' }}>
          <div>
            <Link
              to="/member/business-directory"
              style={{
                color: 'rgba(255,255,255,0.85)',
                fontSize: '13px',
                textDecoration: 'none',
                display: 'inline-flex',
                alignItems: 'center',
                gap: '4px',
                marginBottom: '8px',
              }}
            >
              <ArrowLeft size={14} />
              <span>Back to Directory</span>
            </Link>
            <h1 style={{ fontSize: '28px', fontWeight: 800, color: '#fff', margin: 0 }}>
              {categoryName} Businesses
            </h1>
            <p style={{ fontSize: '14px', color: 'rgba(255,255,255,0.85)', margin: '6px 0 0 0' }}>
              Explore top verified companies and services in {categoryName}.
            </p>
          </div>

          <span
            className="biz-badge"
            style={{
              background: 'rgba(255, 255, 255, 0.2)',
              color: '#fff',
              fontSize: '14px',
              padding: '8px 16px',
            }}
          >
            {total} {total === 1 ? 'Business' : 'Businesses'}
          </span>
        </div>
      </div>

      {/* Filter & Sort Bar */}
      <div className="biz-info-card biz-category-filter-card" style={{ padding: '16px', marginBottom: '20px' }}>
        <form
          onSubmit={handleSearchSubmit}
          style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '12px', flexWrap: 'wrap' }}
        >
          <div style={{ flex: 1, minWidth: '200px' }}>
            <input
              type="text"
              value={qInput}
              onChange={(e) => setQInput(e.target.value)}
              className="biz-search-input"
              placeholder={`Search within ${categoryName}...`}
            />
          </div>

          <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <select
              value={sort}
              onChange={(e) => handleSortChange(e.target.value)}
              className="biz-filter-select"
            >
              <option value="popular">Most Popular</option>
              <option value="newest">Newest First</option>
              <option value="alphabetical">Alphabetical (A-Z)</option>
            </select>
            <button type="submit" className="member-button member-button--primary" style={{ padding: '8px 16px' }}>
              Filter
            </button>
          </div>
        </form>
      </div>

      {/* Category Pages Grid */}
      <div className="biz-directory-grid biz-directory-grid--category" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(280px, 1fr))', gap: '20px' }}>
        {pagesList.length === 0 ? (
          <div className="card" style={{ gridColumn: '1 / -1', width: '100%', padding: '48px 20px', textAlign: 'center' }}>
            <Tag size={36} color="#94a3b8" style={{ margin: '0 auto 12px' }} />
            <h3 style={{ fontSize: '16px', fontWeight: 700, margin: '0 0 6px 0' }}>
              No Businesses Found in {categoryName}
            </h3>
            <p style={{ color: 'var(--color-text-secondary)', marginBottom: '16px', fontSize: '13.5px' }}>
              Be the first member to create a business page in this category!
            </p>
            <Link
              to="/member/business-pages/create"
              className="member-button member-button--primary"
              style={{ display: 'inline-flex', alignItems: 'center', gap: '6px' }}
            >
              <PlusCircle size={15} />
              <span>Create Business Page</span>
            </Link>
          </div>
        ) : (
          pagesList.map((bp) => <BusinessCard key={bp.id} businessPage={bp} />)
        )}
      </div>

      {/* Pagination */}
      {lastPage > 1 && (
        <div style={{ display: 'flex', justifyContent: 'center', gap: '8px', marginTop: '24px' }}>
          <button
            type="button"
            className="member-button member-button--secondary"
            disabled={currentPage <= 1}
            onClick={() => handlePageChange(currentPage - 1)}
          >
            Previous
          </button>
          <span style={{ display: 'flex', alignItems: 'center', fontSize: '13px', color: '#64748b' }}>
            Page {currentPage} of {lastPage}
          </span>
          <button
            type="button"
            className="member-button member-button--secondary"
            disabled={currentPage >= lastPage}
            onClick={() => handlePageChange(currentPage + 1)}
          >
            Next
          </button>
        </div>
      )}
    </div>
  );
}

export default BusinessCategoryPage;
