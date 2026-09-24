import apiClient from './apiClient';

/**
 * Member Marketplace API Service
 */
export const marketplaceApi = {
  /**
   * Get paginated marketplace listings with search, filter, and sorting
   * @param {Object} params - { category, search, condition, min_price, max_price, sort, page }
   */
  async getProducts(params = {}) {
    const response = await apiClient.get('/marketplace', {
      params,
    });
    return response.data;
  },

  /**
   * Get single product detail with seller info, media, and related products
   * @param {number|string} productId
   */
  async getProduct(productId) {
    const response = await apiClient.get(`/marketplace/${productId}`);
    return response.data;
  },

  /**
   * Get category options for product creation
   */
  async getCreateData() {
    const response = await apiClient.get('/marketplace/create');
    return response.data;
  },

  /**
   * Create a new product listing (multipart/form-data for image/video upload)
   * @param {FormData} formData
   */
  async createProduct(formData) {
    const response = await apiClient.post('/marketplace', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },

  /**
   * Get edit form data (product details and categories)
   * @param {number|string} productId
   */
  async getEditData(productId) {
    const response = await apiClient.get(`/marketplace/${productId}/edit`);
    return response.data;
  },

  /**
   * Update an existing product listing
   * @param {number|string} productId
   * @param {FormData|Object} data
   */
  async updateProduct(productId, data) {
    // If formData is passed, send via POST with _method=PUT for PHP file handling
    if (data instanceof FormData) {
      data.append('_method', 'PUT');
      const response = await apiClient.post(`/marketplace/${productId}`, data, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
      return response.data;
    }
    const response = await apiClient.put(`/marketplace/${productId}`, data);
    return response.data;
  },

  /**
   * Delete a product listing
   * @param {number|string} productId
   */
  async deleteProduct(productId) {
    const response = await apiClient.delete(`/marketplace/${productId}`);
    return response.data;
  },

  /**
   * Get current member's product listings
   * @param {Object} params - { status: 'all' | 'available' | 'sold' | 'reserved' | 'hidden', page }
   */
  async getMyProducts(params = {}) {
    const response = await apiClient.get('/marketplace/my-products', {
      params,
    });
    return response.data;
  },

  /**
   * Get member's bookmarked / saved products
   * @param {number} page
   */
  async getSavedProducts(page = 1) {
    const response = await apiClient.get('/marketplace/saved', {
      params: { page },
    });
    return response.data;
  },

  /**
   * Toggle product status (e.g. available <-> sold, reserved, hidden)
   * @param {number|string} productId
   * @param {string} status - 'available' | 'sold' | 'reserved' | 'hidden'
   */
  async toggleStatus(productId, status) {
    const response = await apiClient.post(`/marketplace/${productId}/status`, {
      status,
    });
    return response.data;
  },

  /**
   * Toggle bookmark/save state for a product
   * @param {number|string} productId
   */
  async toggleSave(productId) {
    const response = await apiClient.post(`/marketplace/${productId}/save`);
    return response.data;
  },

  /**
   * Report a product listing to moderators
   * @param {number|string} productId
   * @param {Object} data - { reason, notes }
   */
  async reportProduct(productId, data) {
    const response = await apiClient.post(`/marketplace/${productId}/report`, data);
    return response.data;
  },

  /**
   * Delete individual media file from a product
   * @param {number|string} productId
   * @param {number|string} mediaId
   */
  async deleteMedia(productId, mediaId) {
    const response = await apiClient.delete(`/marketplace/${productId}/media/${mediaId}`);
    return response.data;
  },
};

export default marketplaceApi;
