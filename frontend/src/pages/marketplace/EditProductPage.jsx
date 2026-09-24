import { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { ArrowLeft, Upload, X, Trash2 } from 'lucide-react';
import marketplaceApi from '../../api/marketplaceApi';

export function EditProductPage() {
  const { id } = useParams();
  const navigate = useNavigate();

  const [categories, setCategories] = useState([]);
  const [title, setTitle] = useState('');
  const [categoryId, setCategoryId] = useState('');
  const [price, setPrice] = useState('');
  const [condition, setCondition] = useState('like_new');
  const [status, setStatus] = useState('available');
  const [location, setLocation] = useState('');
  const [description, setDescription] = useState('');
  const [existingMedia, setExistingMedia] = useState([]);
  const [newImages, setNewImages] = useState([]);

  const [isLoading, setIsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    let isMounted = true;
    if (id) {
      marketplaceApi
        .getEditData(id)
        .then((data) => {
          if (isMounted && data && data.product) {
            const p = data.product;
            setTitle(p.title || '');
            setCategoryId(p.category_id || '');
            setPrice(p.price || '');
            setCondition(p.condition || 'like_new');
            setStatus(p.status || 'available');
            setLocation(p.location || '');
            setDescription(p.description || '');
            setExistingMedia(p.media || []);
            if (Array.isArray(data.categories)) {
              setCategories(data.categories);
            }
          }
        })
        .catch((err) => {
          if (isMounted) {
            setError(err.response?.data?.message || 'Failed to load product for editing.');
          }
        })
        .finally(() => {
          if (isMounted) setIsLoading(false);
        });
    }

    return () => {
      isMounted = false;
    };
  }, [id]);

  const handleDeleteExistingMedia = async (mediaId) => {
    if (!id) return;
    try {
      await marketplaceApi.deleteMedia(id, mediaId);
      setExistingMedia((prev) => prev.filter((m) => m.id !== mediaId));
    } catch {
      // Handle error
    }
  };

  const handleNewImagesChange = (e) => {
    const files = Array.from(e.target.files || []);
    const newPreviews = files.map((file) => ({
      file,
      previewUrl: URL.createObjectURL(file),
    }));
    setNewImages((prev) => [...prev, ...newPreviews]);
  };

  const handleRemoveNewImage = (index) => {
    setNewImages((prev) => {
      const removed = prev[index];
      if (removed?.previewUrl) URL.revokeObjectURL(removed.previewUrl);
      return prev.filter((_, idx) => idx !== index);
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (isSubmitting || !id) return;
    setIsSubmitting(true);
    setError(null);

    const formData = new FormData();
    formData.append('title', title.trim());
    formData.append('category_id', categoryId);
    formData.append('price', price);
    formData.append('condition', condition);
    formData.append('status', status);
    if (location.trim()) formData.append('location', location.trim());
    formData.append('description', description.trim());

    newImages.forEach((imgObj) => {
      formData.append('images[]', imgObj.file);
    });

    try {
      await marketplaceApi.updateProduct(id, formData);
      navigate(`/member/marketplace/${id}`);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to update product listing.');
    } finally {
      setIsSubmitting(false);
    }
  };

  if (isLoading) {
    return (
      <div style={{ padding: '40px', textAlign: 'center', color: 'var(--color-text-secondary)' }}>
        Loading listing details...
      </div>
    );
  }

  return (
    <div style={{ width: '100%', margin: '0 auto' }}>
      <div style={{ marginBottom: '16px' }}>
        <Link
          to={`/member/marketplace/${id}`}
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
          <span>Back to Product</span>
        </Link>
      </div>

      <div className="product-create-page card" style={{ padding: '32px', borderRadius: '18px' }}>
        <header className="member-card__header" style={{ marginBottom: '24px' }}>
          <div>
            <h1 style={{ fontSize: '1.4rem', fontWeight: 800, margin: '0 0 6px 0' }}>Edit Listing</h1>
            <p style={{ color: 'var(--color-text-secondary)', margin: 0 }}>
              Update your product listing details.
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
                  style={{ width: '100%', padding: '12px', borderRadius: '10px', border: '1px solid var(--color-border-soft)' }}
                >
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
                <label htmlFor="status" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
                  Status *
                </label>
                <select
                  id="status"
                  value={status}
                  onChange={(e) => setStatus(e.target.value)}
                  required
                  style={{ width: '100%', padding: '12px', borderRadius: '10px', border: '1px solid var(--color-border-soft)' }}
                >
                  <option value="available">Available</option>
                  <option value="sold">Sold</option>
                  <option value="reserved">Reserved</option>
                  <option value="hidden">Hidden</option>
                </select>
              </div>
            </div>
          </div>

          <div className="form-group" style={{ marginBottom: '18px' }}>
            <label htmlFor="location" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
              Location
            </label>
            <input
              type="text"
              id="location"
              value={location}
              onChange={(e) => setLocation(e.target.value)}
              style={{ width: '100%', padding: '12px', borderRadius: '10px', border: '1px solid var(--color-border-soft)' }}
            />
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
              style={{ width: '100%', padding: '12px', borderRadius: '10px', border: '1px solid var(--color-border-soft)', resize: 'vertical' }}
            />
          </div>

          {/* Existing Media Section */}
          {existingMedia.length > 0 && (
            <div className="form-group" style={{ marginBottom: '18px' }}>
              <label style={{ fontWeight: 600, display: 'block', marginBottom: '8px' }}>
                Current Media
              </label>
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(100px, 1fr))', gap: '10px' }}>
                {existingMedia.map((m) => (
                  <div key={m.id} style={{ position: 'relative', height: '100px', borderRadius: '8px', overflow: 'hidden', border: '1px solid #e2e8f0' }}>
                    {m.media_type === 'video' ? (
                      <video src={`/${m.media_path}`} muted style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                    ) : (
                      <img src={`/${m.media_path}`} alt="" style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                    )}
                    <button
                      type="button"
                      onClick={() => handleDeleteExistingMedia(m.id)}
                      style={{
                        position: 'absolute',
                        top: '4px',
                        right: '4px',
                        width: '24px',
                        height: '24px',
                        borderRadius: '50%',
                        background: 'rgba(239, 68, 68, 0.9)',
                        color: '#fff',
                        border: 'none',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        cursor: 'pointer',
                      }}
                      title="Remove media"
                    >
                      <Trash2 size={13} />
                    </button>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Add More Media */}
          <div className="form-group" style={{ marginBottom: '24px' }}>
            <label style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
              Add More Photos
            </label>
            <label
              htmlFor="add-images-input"
              style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '8px',
                padding: '16px',
                borderRadius: '12px',
                border: '1px dashed var(--color-border-soft)',
                background: 'var(--color-surface-soft, #f8fafc)',
                cursor: 'pointer',
              }}
            >
              <Upload size={16} />
              <span>Choose Additional Photos</span>
              <input
                id="add-images-input"
                type="file"
                multiple
                accept=".jpg,.jpeg,.png,.webp"
                onChange={handleNewImagesChange}
                style={{ display: 'none' }}
              />
            </label>

            {newImages.length > 0 && (
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(100px, 1fr))', gap: '10px', marginTop: '12px' }}>
                {newImages.map((img, idx) => (
                  <div key={idx} style={{ position: 'relative', height: '100px', borderRadius: '8px', overflow: 'hidden', border: '1px solid #e2e8f0' }}>
                    <img src={img.previewUrl} alt="" style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                    <button
                      type="button"
                      onClick={() => handleRemoveNewImage(idx)}
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
                    >
                      <X size={14} />
                    </button>
                  </div>
                ))}
              </div>
            )}
          </div>

          <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '12px' }}>
            <Link to={`/member/marketplace/${id}`} className="member-button member-button--secondary">
              Cancel
            </Link>
            <button
              type="submit"
              className="member-button member-button--primary"
              disabled={isSubmitting}
            >
              {isSubmitting ? 'Saving...' : 'Save Changes'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

export default EditProductPage;
