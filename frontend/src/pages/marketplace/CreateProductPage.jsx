import { useState, useEffect } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { ArrowLeft, Upload, X } from 'lucide-react';
import marketplaceApi from '../../api/marketplaceApi';
import { useImageModeration } from '../../hooks/useImageModeration';
import { MODERATION_CONTEXTS } from '../../config/imageModerationPolicy';
import { ImageModerationScanModal } from '../../components/moderation/ImageModerationScanModal';

export function CreateProductPage() {
  const navigate = useNavigate();

  const [categories, setCategories] = useState([]);
  const [title, setTitle] = useState('');
  const [categoryId, setCategoryId] = useState('');
  const [price, setPrice] = useState('');
  const [condition, setCondition] = useState('like_new');
  const [location, setLocation] = useState('');
  const [description, setDescription] = useState('');
  const [selectedImages, setSelectedImages] = useState([]);

  const [isLoading, setIsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState(null);

  const {
    scanImages,
    progress: modProgress,
    decision: modDecision,
    error: modError,
    cancel: cancelModeration,
    reset: resetModeration,
  } = useImageModeration({ context: MODERATION_CONTEXTS.MARKETPLACE_IMAGE });

  const [isScanModalOpen, setIsScanModalOpen] = useState(false);
  const [scanStatus, setScanStatus] = useState('IDLE');
  const [blockedItems, setBlockedItems] = useState([]);

  // Object URL cleanup on unmount
  useEffect(() => {
    return () => {
      selectedImages.forEach((imgObj) => {
        if (imgObj?.previewUrl) URL.revokeObjectURL(imgObj.previewUrl);
      });
    };
  }, [selectedImages]);

  useEffect(() => {
    let isMounted = true;
    marketplaceApi
      .getCreateData()
      .then((data) => {
        if (isMounted && data && Array.isArray(data.categories)) {
          setCategories(data.categories);
        }
      })
      .catch(() => {})
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, []);

  const handleImageChange = (e) => {
    const files = Array.from(e.target.files || []);
    if (selectedImages.length + files.length > 10) {
      setError('You can upload a maximum of 10 photos.');
      return;
    }
    const newPreviews = files.map((file) => ({
      file,
      previewUrl: URL.createObjectURL(file),
    }));
    setSelectedImages((prev) => [...prev, ...newPreviews]);
  };

  const handleRemoveImage = (index) => {
    setSelectedImages((prev) => {
      const removed = prev[index];
      if (removed?.previewUrl) URL.revokeObjectURL(removed.previewUrl);
      return prev.filter((_, idx) => idx !== index);
    });
  };

  const handleRemoveBlockedFiles = () => {
    if (blockedItems.length > 0) {
      const blockedFilesSet = new Set(blockedItems.map((b) => b.file));
      setSelectedImages((prev) => {
        const remaining = [];
        prev.forEach((item) => {
          if (blockedFilesSet.has(item.file)) {
            if (item.previewUrl) URL.revokeObjectURL(item.previewUrl);
          } else {
            remaining.push(item);
          }
        });
        return remaining;
      });
    }
    setBlockedItems([]);
    setIsScanModalOpen(false);
    setScanStatus('IDLE');
    resetModeration();
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (isSubmitting) return;
    setIsSubmitting(true);
    setError(null);

    // Pre-flight Client-Side Image Moderation for all selected photos
    if (selectedImages.length > 0) {
      setIsScanModalOpen(true);
      setScanStatus('SCANNING');
      setBlockedItems([]);

      try {
        const batchSummary = await scanImages(selectedImages.map((img) => img.file));

        if (!batchSummary.allAllowed) {
          setScanStatus('BLOCKED');
          setBlockedItems(batchSummary.blockedItems);
          const blockedNames = batchSummary.blockedItems.map((b) => b.fileName).join(', ');
          setError(`Restricted content detected in photo(s): ${blockedNames}. Please remove or replace the flagged photos.`);
          setIsSubmitting(false);
          return;
        }

        if (batchSummary.error) {
          setScanStatus('ERROR');
          setError('Failed to verify photos against safety standards. Please try again.');
          setIsSubmitting(false);
          return;
        }

        // All photos passed moderation
        setIsScanModalOpen(false);
        setScanStatus('IDLE');
      } catch {
        setScanStatus('ERROR');
        setError('Verification encountered an unexpected error. Please try again.');
        setIsSubmitting(false);
        return;
      }
    }

    const formData = new FormData();
    formData.append('title', title.trim());
    formData.append('category_id', categoryId);
    formData.append('price', price);
    formData.append('condition', condition);
    if (location.trim()) formData.append('location', location.trim());
    formData.append('description', description.trim());

    selectedImages.forEach((imgObj) => {
      formData.append('images[]', imgObj.file);
    });

    try {
      const res = await marketplaceApi.createProduct(formData);
      if (res && res.product) {
        navigate(`/member/marketplace/${res.product.id}`);
      } else {
        navigate('/member/marketplace');
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to create product listing.');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div style={{ width: '100%', margin: '0 auto' }}>
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

      <div className="product-create-page card" style={{ padding: '32px', borderRadius: '18px' }}>
        <header className="member-card__header" style={{ marginBottom: '24px' }}>
          <div>
            <h1 style={{ fontSize: '1.4rem', fontWeight: 800, margin: '0 0 6px 0' }}>Create New Product Listing</h1>
            <p style={{ color: 'var(--color-text-secondary)', margin: 0 }}>
              Fill out the product details to sell to MLM Book members.
            </p>
          </div>
        </header>

        {error && (
          <div style={{ padding: '12px 16px', background: '#fee2e2', color: '#b91c1c', borderRadius: '10px', marginBottom: '20px', fontSize: '0.875rem' }}>
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} className="member-form">
          <div className="form-group" style={{ marginBottom: '18px' }}>
            <label htmlFor="title" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
              Title *
            </label>
            <input
              type="text"
              id="title"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              required
              placeholder="e.g. iPhone 15 Pro Max 256GB"
              style={{ width: '100%', padding: '12px', borderRadius: '10px', border: '1px solid var(--color-border-soft)' }}
            />
          </div>

          <div className="row g-3" style={{ marginBottom: '18px' }}>
            <div className="col-12 col-md-6">
              <div className="form-group">
                <label htmlFor="category_id" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
                  Category *
                </label>
                <select
                  id="category_id"
                  value={categoryId}
                  onChange={(e) => setCategoryId(e.target.value)}
                  required
                  disabled={isLoading}
                  style={{ width: '100%', padding: '12px', borderRadius: '10px', border: '1px solid var(--color-border-soft)' }}
                >
                  <option value="">Select Category</option>
                  {categories.map((cat) => (
                    <option key={cat.id} value={cat.id}>
                      {cat.name}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            <div className="col-12 col-md-6">
              <div className="form-group">
                <label htmlFor="price" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
                  Price ($) *
                </label>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  id="price"
                  value={price}
                  onChange={(e) => setPrice(e.target.value)}
                  required
                  placeholder="0.00"
                  style={{ width: '100%', padding: '12px', borderRadius: '10px', border: '1px solid var(--color-border-soft)' }}
                />
              </div>
            </div>
          </div>

          <div className="row g-3" style={{ marginBottom: '18px' }}>
            <div className="col-12 col-md-6">
              <div className="form-group">
                <label htmlFor="condition" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
                  Condition *
                </label>
                <select
                  id="condition"
                  value={condition}
                  onChange={(e) => setCondition(e.target.value)}
                  required
                  style={{ width: '100%', padding: '12px', borderRadius: '10px', border: '1px solid var(--color-border-soft)' }}
                >
                  <option value="new">Brand New</option>
                  <option value="like_new">Like New</option>
                  <option value="good">Good Condition</option>
                  <option value="fair">Fair Condition</option>
                </select>
              </div>
            </div>

            <div className="col-12 col-md-6">
              <div className="form-group">
                <label htmlFor="location" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
                  Location
                </label>
                <input
                  type="text"
                  id="location"
                  value={location}
                  onChange={(e) => setLocation(e.target.value)}
                  placeholder="e.g. New York, NY"
                  style={{ width: '100%', padding: '12px', borderRadius: '10px', border: '1px solid var(--color-border-soft)' }}
                />
              </div>
            </div>
          </div>

          <div className="form-group" style={{ marginBottom: '18px' }}>
            <label htmlFor="description" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
              Description *
            </label>
            <textarea
              id="description"
              rows={5}
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              required
              placeholder="Describe what you're selling, item specs, reason for selling..."
              style={{ width: '100%', padding: '12px', borderRadius: '10px', border: '1px solid var(--color-border-soft)', resize: 'vertical' }}
            />
          </div>

          {/* Images Upload */}
          <div className="form-group" style={{ marginBottom: '18px' }}>
            <label style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
              Product Images (Up to 10 photos)
            </label>
            <label
              htmlFor="product-images-input"
              style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '8px',
                padding: '24px',
                borderRadius: '12px',
                border: '2px dashed var(--color-border-soft)',
                background: 'var(--color-surface-soft, #f8fafc)',
                cursor: 'pointer',
              }}
            >
              <Upload size={18} />
              <span>Choose Photos</span>
              <input
                id="product-images-input"
                type="file"
                multiple
                accept=".jpg,.jpeg,.png,.webp"
                onChange={handleImageChange}
                style={{ display: 'none' }}
              />
            </label>

            {selectedImages.length > 0 && (
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(100px, 1fr))', gap: '10px', marginTop: '12px' }}>
                {selectedImages.map((img, idx) => (
                  <div key={idx} style={{ position: 'relative', height: '100px', borderRadius: '8px', overflow: 'hidden', border: '1px solid #e2e8f0' }}>
                    <img src={img.previewUrl} alt="" style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                    <button
                      type="button"
                      onClick={() => handleRemoveImage(idx)}
                      style={{
                        position: 'absolute',
                        top: '4px',
                        right: '4px',
                        width: '24px',
                        height: '24px',
                        borderRadius: '50%',
                        background: 'rgba(0,0,0,0.6)',
                        color: '#fff',
                        border: 'none',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        cursor: 'pointer',
                      }}
                      aria-label="Remove image"
                    >
                      <X size={14} />
                    </button>
                  </div>
                ))}
              </div>
            )}
          </div>

          <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '12px' }}>
            <Link to="/member/marketplace" className="member-button member-button--secondary">
              Cancel
            </Link>
            <button
              type="submit"
              className="member-button member-button--primary"
              disabled={isSubmitting}
            >
              {isSubmitting ? 'Publishing...' : 'Publish Listing'}
            </button>
          </div>
        </form>
      </div>

      {/* Pre-Flight Client-Side Image Moderation Modal */}
      <ImageModerationScanModal
        isOpen={isScanModalOpen}
        status={scanStatus}
        progress={modProgress}
        decision={modDecision}
        error={modError}
        blockedItems={blockedItems}
        onCancel={() => {
          cancelModeration();
          setIsScanModalOpen(false);
          setScanStatus('IDLE');
          setIsSubmitting(false);
        }}
        onRetry={() => {
          handleSubmit(new Event('submit'));
        }}
        onAcknowledge={handleRemoveBlockedFiles}
      />
    </div>
  );
}

export default CreateProductPage;
