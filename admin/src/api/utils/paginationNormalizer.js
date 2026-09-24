/**
 * Pagination Normalizer Utility
 * Normalizes Laravel LengthAwarePaginator responses into a standard
 * pagination format suitable for PrimeReact DataTable and UI paginators.
 */

export function normalizePagination(paginatorResponse) {
  if (!paginatorResponse) {
    return {
      items: [],
      currentPage: 1,
      lastPage: 1,
      perPage: 15,
      total: 0,
      from: 0,
      to: 0,
      hasNext: false,
      hasPrev: false,
    };
  }

  // Handle direct array vs paginated object
  if (Array.isArray(paginatorResponse)) {
    return {
      items: paginatorResponse,
      currentPage: 1,
      lastPage: 1,
      perPage: paginatorResponse.length,
      total: paginatorResponse.length,
      from: paginatorResponse.length > 0 ? 1 : 0,
      to: paginatorResponse.length,
      hasNext: false,
      hasPrev: false,
    };
  }

  // Standard Laravel LengthAwarePaginator structure
  const items = Array.isArray(paginatorResponse.data) ? paginatorResponse.data : (paginatorResponse.items || []);
  const currentPage = Number(paginatorResponse.current_page || paginatorResponse.currentPage || 1);
  const lastPage = Number(paginatorResponse.last_page || paginatorResponse.lastPage || 1);
  const perPage = Number(paginatorResponse.per_page || paginatorResponse.perPage || 15);
  const total = Number(paginatorResponse.total || items.length);
  const from = Number(paginatorResponse.from || (items.length > 0 ? (currentPage - 1) * perPage + 1 : 0));
  const to = Number(paginatorResponse.to || from + items.length - 1);

  return {
    items,
    currentPage,
    lastPage,
    perPage,
    total,
    from,
    to,
    hasNext: currentPage < lastPage,
    hasPrev: currentPage > 1,
    // PrimeReact helper: first index (zero-based)
    first: Math.max(0, (currentPage - 1) * perPage),
  };
}
