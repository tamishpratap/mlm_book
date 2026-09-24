import { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { ArrowLeft, Upload, Trash2 } from 'lucide-react';
import communityApi from '../../api/communityApi';
import DeleteConfirmModal from '../../components/posts/modals/DeleteConfirmModal';
import { useImageModeration } from '../../hooks/useImageModeration';
import { MODERATION_CONTEXTS } from '../../config/imageModerationPolicy';
import { ImageModerationScanModal } from '../../components/moderation/ImageModerationScanModal';

export function EditCommunityPage() {
  const { slug } = useParams();
  const navigate = useNavigate();

  const [categories, setCategories] = useState([]);
  const [visibilities, setVisibilities] = useState({});
  const [name, setName] = useState('');
  const [category, setCategory] = useState('');
  const [visibility, setVisibility] = useState('public');
  const [description, setDescription] = useState('');
  const [rules, setRules] = useState('');
  const [tags, setTags] = useState('');
  const [existingCover, setExistingCover] = useState(null);
  const [existingLogo, setExistingLogo] = useState(null);
  const [newCover, setNewCover] = useState(null);
  const [newLogo, setNewLogo] = useState(null);

  const [isLoading, setIsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);
  const [error, setError] = useState(null);

  const {
    scanImages,
    progress: modProgress,
    decision: modDecision,
    error: modError,
    cancel: cancelModeration,
    reset: resetModeration,
  } = useImageModeration({ context: MODERATION_CONTEXTS.COMMUNITY_IMAGE });

  const [isScanModalOpen, setIsScanModalOpen] = useState(false);
  const [scanStatus, setScanStatus] = useState('IDLE');
  const [blockedItems, setBlockedItems] = useState([]);

  useEffect(() => {
    let isMounted = true;
    if (slug) {
      communityApi
        .getEditData(slug)
        .then((data) => {
          if (isMounted && data && data.community) {
            const com = data.community;
            setName(com.name || '');
            setCategory(com.category || '');
            setVisibility(com.visibility || 'public');
            setDescription(com.description || '');
            setRules(com.rules || '');
            setTags(com.tags || '');
            setExistingCover(com.cover_photo);
            setExistingLogo(com.logo);
            if (Array.isArray(data.categories)) setCategories(data.categories);
            if (data.visibilities) setVisibilities(data.visibilities);
          }
        })
        .catch((err) => {
          if (isMounted) {
            setError(err.response?.data?.message || 'Failed to load community for editing.');
          }
        })
        .finally(() => {
          if (isMounted) setIsLoading(false);
        });
    }

    return () => {
      isMounted = false;
    };
  }, [slug]);

  const handleRemoveBlockedFiles = () => {
    if (blockedItems.length > 0) {
      const blockedFilesSet = new Set(blockedItems.map((b) => b.file));
      if (newCover && blockedFilesSet.has(newCover)) {
        setNewCover(null);
      }
      if (newLogo && blockedFilesSet.has(newLogo)) {
        setNewLogo(null);
      }
    }
    setBlockedItems([]);
    setIsScanModalOpen(false);
    setScanStatus('IDLE');
    resetModeration();
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (isSubmitting || !slug) return;
    setIsSubmitting(true);
    setError(null);

    // Pre-flight Client-Side Image Moderation
    const imagesToScan = [
      newCover ? { file: newCover, type: 'cover' } : null,
      newLogo ? { file: newLogo, type: 'logo' } : null,
    ].filter(Boolean);

    if (imagesToScan.length > 0) {
      setIsScanModalOpen(true);
      setScanStatus('SCANNING');
      setBlockedItems([]);

      try {
        const batchSummary = await scanImages(imagesToScan.map((i) => i.file));

        if (!batchSummary.allAllowed) {
          setScanStatus('BLOCKED');
          setBlockedItems(batchSummary.blockedItems);
          const blockedNames = batchSummary.blockedItems.map((b) => b.fileName).join(', ');
          setError(`Restricted content detected in image(s): ${blockedNames}. Please remove or replace the flagged photo(s).`);
          setIsSubmitting(false);
          return;
        }

        if (batchSummary.error) {
          setScanStatus('ERROR');
          setError('Failed to verify photos against safety standards. Please try again.');
          setIsSubmitting(false);
          return;
        }

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
    formData.append('name', name.trim());
    formData.append('category', category);
    formData.append('visibility', visibility);
    if (description.trim()) formData.append('description', description.trim());
    if (rules.trim()) formData.append('rules', rules.trim());
    if (tags.trim()) formData.append('tags', tags.trim());
    if (newCover) formData.append('cover_photo', newCover);
    if (newLogo) formData.append('logo', newLogo);

    try {
      const res = await communityApi.updateCommunity(slug, formData);
      if (res && res.community) {
        navigate(`/member/community/${res.community.slug}`);
      } else {
        navigate(`/member/community/${slug}`);
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to update community.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleDeleteConfirm = async () => {
    if (!slug) return;
    setIsDeleting(true);
    try {
      await communityApi.deleteCommunity(slug);
      setShowDeleteModal(false);
      navigate('/member/community');
    } catch {
      // Handle error
    } finally {
      setIsDeleting(false);
    }
  };

  if (isLoading) {
    return (
      <div style={{ padding: '40px', textAlign: 'center', color: 'var(--color-text-secondary)' }}>
        Loading community settings...
      </div>
    );
  }

  return (
    <div style={{ width: '100%', margin: '0 auto' }}>
      <div style={{ marginBottom: '16px' }}>
        <Link
          to={`/member/community/${slug}`}
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
          <span>Back to Community</span>
        </Link>
      </div>

      <div className="card" style={{ padding: '32px', borderRadius: '18px' }}>
        <header style={{ marginBottom: '24px' }}>
          <h1 style={{ fontSize: '1.4rem', fontWeight: 800, margin: '0 0 6px 0' }}>Edit Community Settings</h1>
          <p style={{ color: 'var(--color-text-secondary)', margin: 0 }}>
            Update your community information, visibility, and guidelines.
          </p>
        </header>

        {error && (
          <div style={{ padding: '12px 16px', background: '#fee2e2', color: '#b91c1c', borderRadius: '10px', marginBottom: '20px', fontSize: '0.875rem' }}>
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} className="member-form">
          <div className="form-group" style={{ marginBottom: '18px' }}>
            <label htmlFor="edit-name" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
              Community Name *
            </label>
            <input
              type="text"
              id="edit-name"
              value={name}
              onChange={(e) => setName(e.target.value)}
              required
              style={{ width: '100%', padding: '12px', borderRadius: '10px', border: '1px solid var(--color-border-soft)' }}
            />
          </div>

          <div className="row g-3" style={{ marginBottom: '18px' }}>
            <div className="col-12 col-md-6">
              <div className="form-group">
                <label htmlFor="edit-category" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
                  Category *
                </label>
                <select
                  id="edit-category"
                  value={category}
                  onChange={(e) => setCategory(e.target.value)}
                  required
                  style={{ width: '100%', padding: '12px', borderRadius: '10px', border: '1px solid var(--color-border-soft)' }}
                >
                  {categories.map((cat) => (
                    <option key={cat} value={cat}>
                      {cat}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            <div className="col-12 col-md-6">
              <div className="form-group">
                <label htmlFor="edit-visibility" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
                  Privacy & Visibility *
                </label>
                <select
                  id="edit-visibility"
                  value={visibility}
                  onChange={(e) => setVisibility(e.target.value)}
                  required
                  style={{ width: '100%', padding: '12px', borderRadius: '10px', border: '1px solid var(--color-border-soft)' }}
                >
                  {Object.entries(visibilities).length > 0 ? (
                    Object.entries(visibilities).map(([val, label]) => (
                      <option key={val} value={val}>
                        {label}
                      </option>
                    ))
                  ) : (
                    <>
                      <option value="public">Public</option>
                      <option value="private">Private</option>
                      <option value="invite_only">Invite Only</option>
                      <option value="secret">Secret</option>
                    </>
                  )}
                </select>
              </div>
            </div>
          </div>

          <div className="form-group" style={{ marginBottom: '18px' }}>
            <label htmlFor="edit-description" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
              Description
            </label>
            <textarea
              id="edit-description"
              rows={4}
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              style={{ width: '100%', padding: '12px', borderRadius: '10px', border: '1px solid var(--color-border-soft)', resize: 'vertical' }}
            />
          </div>

          <div className="form-group" style={{ marginBottom: '18px' }}>
            <label htmlFor="edit-rules" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
              Community Rules
            </label>
            <textarea
              id="edit-rules"
              rows={3}
              value={rules}
              onChange={(e) => setRules(e.target.value)}
              style={{ width: '100%', padding: '12px', borderRadius: '10px', border: '1px solid var(--color-border-soft)', resize: 'vertical' }}
            />
          </div>

          <div className="form-group" style={{ marginBottom: '18px' }}>
            <label htmlFor="edit-tags" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
              Tags (comma separated)
            </label>
            <input
              type="text"
              id="edit-tags"
              value={tags}
              onChange={(e) => setTags(e.target.value)}
              style={{ width: '100%', padding: '12px', borderRadius: '10px', border: '1px solid var(--color-border-soft)' }}
            />
          </div>

          {/* Photo uploads */}
          <div className="row g-3" style={{ marginBottom: '24px' }}>
            <div className="col-12 col-md-6">
              <div className="form-group">
                <label style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
                  Update Cover Photo
                </label>
                <label
                  htmlFor="edit-cover-input"
                  style={{
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    justifyContent: 'center',
                    gap: '6px',
                    padding: '16px',
                    borderRadius: '12px',
                    border: '1px dashed var(--color-border-soft)',
                    background: '#f8fafc',
                    cursor: 'pointer',
                  }}
                >
                  <Upload size={16} />
                  <span style={{ fontSize: '13px' }}>{newCover ? newCover.name : 'Choose New Cover'}</span>
                  <input id="edit-cover-input" type="file" accept=".jpg,.jpeg,.png,.webp" onChange={(e) => setNewCover(e.target.files?.[0] || null)} style={{ display: 'none' }} />
                </label>
                {existingCover && !newCover && (
                  <div style={{ marginTop: '8px', height: '60px', borderRadius: '8px', overflow: 'hidden' }}>
                    <img src={`/${existingCover}`} alt="Current Cover" style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                  </div>
                )}
              </div>
            </div>

            <div className="col-12 col-md-6">
              <div className="form-group">
                <label style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
                  Update Community Logo
                </label>
                <label
                  htmlFor="edit-logo-input"
                  style={{
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    justifyContent: 'center',
                    gap: '6px',
                    padding: '16px',
                    borderRadius: '12px',
                    border: '1px dashed var(--color-border-soft)',
                    background: '#f8fafc',
                    cursor: 'pointer',
                  }}
                >
                  <Upload size={16} />
                  <span style={{ fontSize: '13px' }}>{newLogo ? newLogo.name : 'Choose New Logo'}</span>
                  <input id="edit-logo-input" type="file" accept=".jpg,.jpeg,.png,.webp" onChange={(e) => setNewLogo(e.target.files?.[0] || null)} style={{ display: 'none' }} />
                </label>
                {existingLogo && !newLogo && (
                  <div style={{ marginTop: '8px', width: '44px', height: '44px', borderRadius: '50%', overflow: 'hidden' }}>
                    <img src={`/${existingLogo}`} alt="Current Logo" style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                  </div>
                )}
              </div>
            </div>
          </div>

          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <button
              type="button"
              className="member-button member-button--danger"
              onClick={() => setShowDeleteModal(true)}
            >
              <Trash2 size={15} />
              <span>Delete Community</span>
            </button>

            <div style={{ display: 'flex', gap: '12px' }}>
              <Link to={`/member/community/${slug}`} className="member-button member-button--secondary">
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
          </div>
        </form>
      </div>

      {/* Delete Modal */}
      {showDeleteModal && (
        <DeleteConfirmModal
          isOpen={showDeleteModal}
          onClose={() => setShowDeleteModal(false)}
          onConfirm={handleDeleteConfirm}
          title="Delete Community?"
          message="Are you sure you want to permanently delete this community? All member associations, discussions, and settings will be permanently removed. This action cannot be undone."
          isDeleting={isDeleting}
        />
      )}

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

export default EditCommunityPage;
