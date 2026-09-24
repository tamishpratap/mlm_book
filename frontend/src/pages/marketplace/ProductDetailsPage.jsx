import { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import {
  Tag,
  ShieldCheck,
  MapPin,
  Eye,
  Clock,
  Bookmark,
  Pencil,
  RotateCcw,
  CheckCircle2,
  Trash2,
  Flag,
  ArrowLeft,
  Store,
} from 'lucide-react';
import marketplaceApi from '../../api/marketplaceApi';
import ProductCard from '../../components/marketplace/ProductCard';
import ProductReportModal from '../../components/marketplace/ProductReportModal';
import DeleteConfirmModal from '../../components/posts/modals/DeleteConfirmModal';
import MemberAvatar from '../../components/common/MemberAvatar';

function formatRelativeTime(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  const now = new Date();
  const diffInSeconds = Math.floor((now - date) / 1000);

  if (diffInSeconds < 60) return 'Just now';
  if (diffInSeconds < 3600) {
    const mins = Math.floor(diffInSeconds / 60);
    return `${mins}m ago`;
  }
  if (diffInSeconds < 86400) {
    const hours = Math.floor(diffInSeconds / 3600);
    return `${hours}h ago`;
  }
  if (diffInSeconds < 604800) {
    const days = Math.floor(diffInSeconds / 86400);
    return `${days}d ago`;
  }
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function formatMemberSince(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
}

function formatHeadline(str) {
  if (!str) return '';
  return str.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

export function ProductDetailsPage() {
  const { id } = useParams();
  const navigate = useNavigate();

  const [productData, setProductData] = useState(null);
  const [selectedMediaIndex, setSelectedMediaIndex] = useState(0);
  const [isSaved, setIsSaved] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  // Modals
  const [showReportModal, setShowReportModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);

  useEffect(() => {
    let isMounted = true;
    if (id) {
      marketplaceApi
        .getProduct(id)
        .then((data) => {
          if (isMounted) {
            if (data && data.product) {
              setProductData(data);
              setIsSaved(Boolean(data.is_saved));
              setSelectedMediaIndex(0);
              setError(null);
            } else {
              setError('Product listing not found.');
            }
          }
        })
        .catch((err) => {
          if (isMounted) {
            setError(err.response?.data?.message || 'Failed to load product details.');
          }
        })
        .finally(() => {
          if (isMounted) {
            setIsLoading(false);
          }
        });
    }

    return () => {
      isMounted = false;
    };
  }, [id]);

  const handleToggleSave = async () => {
    if (isSaving || !id) return;
    setIsSaving(true);
    try {
      const res = await marketplaceApi.toggleSave(id);
      if (res && res.success) {
        setIsSaved(res.is_saved);
      }
    } catch {
      // Handle error
    } finally {
      setIsSaving(false);
    }
  };

  const handleToggleStatus = async (newStatus) => {
    if (!id) return;
    try {
      const res = await marketplaceApi.toggleStatus(id, newStatus);
      if (res && res.success) {
        setProductData((prev) => ({
          ...prev,
          product: {
            ...prev.product,
            status: newStatus,
          },
        }));
      }
    } catch {
      // Handle error
    }
  };

  const handleDeleteConfirm = async () => {
    if (!id) return;
    setIsDeleting(true);
    try {
      await marketplaceApi.deleteProduct(id);
      setShowDeleteModal(false);
      navigate('/member/marketplace/my-products');
    } catch {
      // Handle error
    } finally {
      setIsDeleting(false);
    }
  };

  if (isLoading && !productData) {
    return (
      <div style={{ padding: '40px', textAlign: 'center', color: 'var(--color-text-secondary)' }}>
        Loading product details...
      </div>
    );
  }

  if (error || !productData?.product) {
    return (
      <div>
        <div style={{ marginBottom: '16px' }}>
          <Link
            to="/member/marketplace"
            style={{
              display: 'inline-flex',
              alignItems: 'center',
              gap: '6px',
              color: 'var(--color-text-secondary)',
              fontSize: '0.875rem',
              textDecoration: 'none',
            }}
          >
            <ArrowLeft size={16} />
            <span>Back to Marketplace</span>
          </Link>
        </div>
        <div className="notification-empty" role="alert">
          <div className="notification-empty__icon">
            <Store size={40} aria-hidden="true" />
          </div>
          <h2>Listing Unavailable</h2>
          <p>{error || 'Product listing not found.'}</p>
          <Link to="/member/marketplace" className="member-button member-button--primary" style={{ marginTop: '12px' }}>
            Marketplace Home
          </Link>
        </div>
      </div>
    );
  }

  const product = productData.product;
  const relatedProducts = productData.related_products || [];
  const isOwner = Boolean(productData.is_owner);
  const mediaList = product.media || [];
  const activeMedia = mediaList[selectedMediaIndex] || mediaList[0];
  const seller = product.member;

  return (
    <div className="product-details-page">
      {/* Back button */}
      <div style={{ marginBottom: '16px' }}>
        <Link
          to="/member/marketplace"
          style={{
            display: 'inline-flex',
            alignItems: 'center',
            gap: '6px',
            color: 'var(--color-text-secondary)',
            fontSize: '0.875rem',
            fontWeight: 500,
            textDecoration: 'none',
          }}
        >
          <ArrowLeft size={16} />
          <span>Back to Marketplace</span>
        </Link>
      </div>

      <div className="product-details-grid">
        {/* Gallery */}
        <div className="product-details-gallery card">
          {mediaList.length === 0 ? (
            <div className="gallery-main">
              <div
                style={{
                  width: '100%',
                  height: '360px',
                  background: 'linear-gradient(135deg, #e0e7ff 0%, #f1f5f9 100%)',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  color: '#94a3b8',
                }}
              >
                No Image Available
              </div>
            </div>
          ) : (
            <>
              <div className="gallery-main" id="gallery-main-viewport">
                {activeMedia?.media_type === 'video' ? (
                  <video src={`/${activeMedia.media_path}`} controls autoPlay playsInline style={{ width: '100%', height: '100%', objectFit: 'contain' }} />
                ) : (
                  <img
                    id="gallery-main-image"
                    src={`/${activeMedia.media_path}`}
                    alt={product.title}
                    style={{ width: '100%', height: '100%', objectFit: 'contain' }}
                  />
                )}
              </div>

              {mediaList.length > 1 && (
                <div className="gallery-thumbnails">
                  {mediaList.map((media, idx) => (
                    <button
                      key={media.id}
                      type="button"
                      className={`gallery-thumb-btn ${idx === selectedMediaIndex ? 'is-active' : ''}`}
                      onClick={() => setSelectedMediaIndex(idx)}
                      style={{
                        outline: idx === selectedMediaIndex ? '2px solid var(--color-primary, #4f7df3)' : 'none',
                        borderRadius: '8px',
                        overflow: 'hidden',
                      }}
                    >
                      {media.media_type === 'video' ? (
                        <video src={`/${media.media_path}`} muted style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                      ) : (
                        <img src={`/${media.media_path}`} alt={`Thumb ${idx + 1}`} style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                      )}
                    </button>
                  ))}
                </div>
              )}
            </>
          )}
        </div>

        {/* Product Details Info */}
        <div className="product-details-info card">
          <div className="product-details-header">
            {product.category && (
              <span className="category-tag">
                <Tag size={13} aria-hidden="true" /> {product.category.name}
              </span>
            )}
            <h1>{product.title}</h1>
            <div className="product-details-price">
              ${parseFloat(product.price || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
            </div>
            <div className={`product-status-tag status-${product.status}`}>
              {formatHeadline(product.status)}
            </div>
          </div>

          <div className="product-meta-list">
            <div className="product-meta-item">
              <span>
                <ShieldCheck size={14} aria-hidden="true" /> Condition
              </span>
              <strong>{formatHeadline(product.condition)}</strong>
            </div>
            {product.location && (
              <div className="product-meta-item">
                <span>
                  <MapPin size={14} aria-hidden="true" /> Location
                </span>
                <strong>{product.location}</strong>
              </div>
            )}
            <div className="product-meta-item">
              <span>
                <Eye size={14} aria-hidden="true" /> Views
              </span>
              <strong>{product.views_count || 0}</strong>
            </div>
            <div className="product-meta-item">
              <span>
                <Clock size={14} aria-hidden="true" /> Posted
              </span>
              <strong>{formatRelativeTime(product.created_at)}</strong>
            </div>
          </div>

          {/* Action Buttons */}
          <div className="product-actions" style={{ display: 'flex', gap: '10px', flexWrap: 'wrap' }}>
            <button
              className={`member-button ${isSaved ? 'member-button--secondary' : 'member-button--primary'}`}
              type="button"
              onClick={handleToggleSave}
              disabled={isSaving}
            >
              <Bookmark size={15} aria-hidden="true" />
              <span>{isSaved ? 'Saved Item' : 'Save Product'}</span>
            </button>

            {isOwner ? (
              <>
                <Link
                  className="member-button member-button--secondary"
                  to={`/member/marketplace/${product.id}/edit`}
                >
                  <Pencil size={15} aria-hidden="true" />
                  <span>Edit Listing</span>
                </Link>

                <button
                  className="member-button member-button--secondary"
                  type="button"
                  onClick={() => handleToggleStatus(product.status === 'sold' ? 'available' : 'sold')}
                >
                  {product.status === 'sold' ? (
                    <>
                      <RotateCcw size={15} aria-hidden="true" />
                      <span>Mark Available</span>
                    </>
                  ) : (
                    <>
                      <CheckCircle2 size={15} aria-hidden="true" />
                      <span>Mark as Sold</span>
                    </>
                  )}
                </button>

                <button
                  className="member-button member-button--danger"
                  type="button"
                  onClick={() => setShowDeleteModal(true)}
                >
                  <Trash2 size={15} aria-hidden="true" />
                  <span>Delete</span>
                </button>
              </>
            ) : (
              <button
                className="member-button member-button--secondary"
                type="button"
                onClick={() => setShowReportModal(true)}
              >
                <Flag size={15} aria-hidden="true" />
                <span>Report</span>
              </button>
            )}
          </div>

          {/* Seller Information */}
          {seller && (
            <div className="seller-box card">
              <h3>Seller Information</h3>
              <div className="seller-box__profile">
                <MemberAvatar member={seller} size={44} />
                <div>
                  <strong>{seller.name}</strong>
                  <small>Member since {formatMemberSince(seller.created_at)}</small>
                </div>
                <Link className="soft-cta" to={`/member/people/${seller.id}`}>
                  Profile
                </Link>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Description Box */}
      <div className="product-description-box card">
        <h2>Description</h2>
        <p style={{ whiteSpace: 'pre-wrap', lineHeight: 1.6 }}>{product.description}</p>
      </div>

      {/* Related / Similar Products */}
      {relatedProducts.length > 0 && (
        <div className="related-products-section">
          <h2>Similar Listings</h2>
          <div className="marketplace-grid">
            {relatedProducts.map((related) => (
              <ProductCard key={related.id} product={related} />
            ))}
          </div>
        </div>
      )}

      {/* Report Modal */}
      {showReportModal && (
        <ProductReportModal
          isOpen={showReportModal}
          onClose={() => setShowReportModal(false)}
          productId={product.id}
          productTitle={product.title}
        />
      )}

      {/* Delete Confirmation Modal */}
      {showDeleteModal && (
        <DeleteConfirmModal
          isOpen={showDeleteModal}
          onClose={() => setShowDeleteModal(false)}
          onConfirm={handleDeleteConfirm}
          title={`Delete "${product.title}"?`}
          message="Are you sure you want to permanently remove this product listing from Marketplace? This action cannot be undone."
          isDeleting={isDeleting}
        />
      )}
    </div>
  );
}

export default ProductDetailsPage;
