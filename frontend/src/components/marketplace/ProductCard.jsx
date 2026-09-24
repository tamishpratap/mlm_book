import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Bookmark, MapPin, Clock, MoreVertical, Pencil, Trash2, CheckCircle2, RotateCcw } from 'lucide-react';
import marketplaceApi from '../../api/marketplaceApi';

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
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

export function ProductCard({
  product,
  isOwner = false,
  onStatusChange,
  onDelete,
  onSaveChange,
}) {
  const [isSaved, setIsSaved] = useState(Boolean(product?.is_saved));
  const [isSaving, setIsSaving] = useState(false);
  const [showMenu, setShowMenu] = useState(false);

  if (!product) return null;

  const mediaList = product.media || [];
  const featuredMedia = mediaList.find((m) => m.is_featured) || mediaList[0];
  const mediaPath = featuredMedia ? `/${featuredMedia.media_path}` : null;
  const isVideo = featuredMedia?.media_type === 'video';

  const handleSaveToggle = async (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (isSaving) return;
    setIsSaving(true);
    try {
      const res = await marketplaceApi.toggleSave(product.id);
      if (res && res.success) {
        setIsSaved(res.is_saved);
        if (onSaveChange) onSaveChange(product.id, res.is_saved);
      }
    } catch {
      // Handle error
    } finally {
      setIsSaving(false);
    }
  };

  const handleStatusToggle = async (e, targetStatus) => {
    e.preventDefault();
    e.stopPropagation();
    setShowMenu(false);
    try {
      const res = await marketplaceApi.toggleStatus(product.id, targetStatus);
      if (res && res.success && onStatusChange) {
        onStatusChange(product.id, targetStatus);
      }
    } catch {
      // Handle error
    }
  };

  const handleDelete = (e) => {
    e.preventDefault();
    e.stopPropagation();
    setShowMenu(false);
    if (onDelete) onDelete(product.id);
  };

  return (
    <div className="product-card card" data-product-card={product.id}>
      <div className="product-card__image">
        <Link to={`/member/marketplace/${product.id}`}>
          {mediaPath ? (
            isVideo ? (
              <video src={mediaPath} muted preload="metadata" />
            ) : (
              <img src={mediaPath} alt={product.title} loading="lazy" />
            )
          ) : (
            <div
              style={{
                width: '100%',
                height: '100%',
                background: 'linear-gradient(135deg, #e0e7ff 0%, #f1f5f9 100%)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                color: '#94a3b8',
                fontSize: '0.85rem',
              }}
            >
              No Image
            </div>
          )}
        </Link>

        {product.status === 'sold' && (
          <span className="product-badge product-badge--sold">SOLD</span>
        )}
        {product.status === 'reserved' && (
          <span className="product-badge product-badge--reserved">RESERVED</span>
        )}
        {product.status === 'hidden' && (
          <span className="product-badge" style={{ background: '#64748b', color: '#fff' }}>HIDDEN</span>
        )}

        <button
          className={`product-card__save-btn ${isSaved ? 'is-saved' : ''}`}
          type="button"
          data-product-save-btn={product.id}
          onClick={handleSaveToggle}
          disabled={isSaving}
          title={isSaved ? 'Unsave product' : 'Save product'}
          aria-label={isSaved ? 'Unsave product' : 'Save product'}
        >
          <Bookmark size={15} aria-hidden="true" />
        </button>

        {isOwner && (
          <div style={{ position: 'absolute', top: '10px', left: '10px', zIndex: 10 }}>
            <button
              type="button"
              className="icon-button"
              onClick={(e) => {
                e.preventDefault();
                e.stopPropagation();
                setShowMenu(!showMenu);
              }}
              style={{
                width: '32px',
                height: '32px',
                background: 'rgba(255, 255, 255, 0.9)',
                borderRadius: '50%',
                boxShadow: '0 2px 6px rgba(0,0,0,0.15)',
              }}
              aria-label="Owner actions"
            >
              <MoreVertical size={16} />
            </button>

            {showMenu && (
              <div
                style={{
                  position: 'absolute',
                  top: '38px',
                  left: 0,
                  background: '#ffffff',
                  borderRadius: '12px',
                  boxShadow: '0 8px 24px rgba(0,0,0,0.15)',
                  padding: '6px',
                  minWidth: '140px',
                  display: 'flex',
                  flexDirection: 'column',
                  gap: '4px',
                  zIndex: 20,
                }}
              >
                <Link
                  to={`/member/marketplace/${product.id}/edit`}
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '8px',
                    padding: '6px 10px',
                    fontSize: '0.8rem',
                    color: '#334155',
                    textDecoration: 'none',
                    borderRadius: '6px',
                  }}
                >
                  <Pencil size={13} /> Edit
                </Link>
                {product.status === 'sold' ? (
                  <button
                    type="button"
                    onClick={(e) => handleStatusToggle(e, 'available')}
                    style={{
                      display: 'flex',
                      alignItems: 'center',
                      gap: '8px',
                      padding: '6px 10px',
                      fontSize: '0.8rem',
                      color: '#334155',
                      border: 'none',
                      background: 'none',
                      textAlign: 'left',
                      cursor: 'pointer',
                      borderRadius: '6px',
                    }}
                  >
                    <RotateCcw size={13} /> Make Available
                  </button>
                ) : (
                  <button
                    type="button"
                    onClick={(e) => handleStatusToggle(e, 'sold')}
                    style={{
                      display: 'flex',
                      alignItems: 'center',
                      gap: '8px',
                      padding: '6px 10px',
                      fontSize: '0.8rem',
                      color: '#334155',
                      border: 'none',
                      background: 'none',
                      textAlign: 'left',
                      cursor: 'pointer',
                      borderRadius: '6px',
                    }}
                  >
                    <CheckCircle2 size={13} /> Mark Sold
                  </button>
                )}
                <button
                  type="button"
                  onClick={handleDelete}
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '8px',
                    padding: '6px 10px',
                    fontSize: '0.8rem',
                    color: '#ef4444',
                    border: 'none',
                    background: 'none',
                    textAlign: 'left',
                    cursor: 'pointer',
                    borderRadius: '6px',
                  }}
                >
                  <Trash2 size={13} /> Delete
                </button>
              </div>
            )}
          </div>
        )}
      </div>

      <div className="product-card__content">
        <div className="product-card__price">
          ${parseFloat(product.price || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
        </div>
        <h3 className="product-card__title">
          <Link to={`/member/marketplace/${product.id}`}>{product.title}</Link>
        </h3>
        <div className="product-card__meta">
          {product.location && (
            <span>
              <MapPin size={13} aria-hidden="true" />
              {product.location}
            </span>
          )}
          <span>
            <Clock size={13} aria-hidden="true" />
            {formatRelativeTime(product.created_at)}
          </span>
        </div>
      </div>
    </div>
  );
}

export default ProductCard;
