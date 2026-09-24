import { useState, useEffect, useRef } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { ArrowLeft, Upload, ShieldCheck, Phone, Crop, Trash2 } from 'lucide-react';
import communityApi from '../../api/communityApi';
import useAuth from '../../hooks/useAuth';
import { ImageAdjustmentModal } from '../../components/posts/modals/ImageAdjustmentModal';
import AccountVerificationModal from '../../components/verification/AccountVerificationModal';

import { isMemberMobileVerified } from '../../utils/whatsappVerification';

export function CreateCommunityPage() {
  const navigate = useNavigate();
  const { user: currentUser } = useAuth();
  const isVerified = isMemberMobileVerified(currentUser);
  const [showVerifyModal, setShowVerifyModal] = useState(false);

  const [categories, setCategories] = useState([]);
  const [visibilities, setVisibilities] = useState({});
  const [name, setName] = useState('');
  const [category, setCategory] = useState('Technology');
  const [visibility, setVisibility] = useState('public');
  const [description, setDescription] = useState('');
  const [rules, setRules] = useState('');
  const [tags, setTags] = useState('');

  // Media input refs
  const logoInputRef = useRef(null);
  const coverInputRef = useRef(null);

  // Logo file, preview, and framing adjustment states
  const [logo, setLogo] = useState(null);
  const [logoPreview, setLogoPreview] = useState(null);
  const [pendingLogoFile, setPendingLogoFile] = useState(null);
  const [logoAdjustmentState, setLogoAdjustmentState] = useState(null);
  const [isLogoAdjustModalOpen, setIsLogoAdjustModalOpen] = useState(false);

  // Cover photo file, preview, and framing adjustment states
  const [coverPhoto, setCoverPhoto] = useState(null);
  const [coverPreview, setCoverPreview] = useState(null);
  const [pendingCoverFile, setPendingCoverFile] = useState(null);
  const [coverAdjustmentState, setCoverAdjustmentState] = useState(null);
  const [isCoverAdjustModalOpen, setIsCoverAdjustModalOpen] = useState(false);

  const [isLoading, setIsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState(null);



  // Clean up blob preview URLs to avoid memory leaks
  useEffect(() => {
    return () => {
      if (logoPreview && logoPreview.startsWith('blob:')) {
        URL.revokeObjectURL(logoPreview);
      }
      if (coverPreview && coverPreview.startsWith('blob:')) {
        URL.revokeObjectURL(coverPreview);
      }
    };
  }, [logoPreview, coverPreview]);

  useEffect(() => {
    let isMounted = true;
    communityApi
      .getCreateData()
      .then((data) => {
        if (isMounted && data) {
          if (Array.isArray(data.categories)) {
            setCategories(data.categories);
            if (data.categories[0]) setCategory(data.categories[0]);
          }
          if (data.visibilities) {
            setVisibilities(data.visibilities);
          }
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

  const handleLogoChange = (e) => {
    const file = e.target.files?.[0];
    if (file) {
      if (file.size > 2 * 1024 * 1024) {
        setError('Community logo must not exceed 2 MB.');
        if (logoInputRef.current) logoInputRef.current.value = '';
        return;
      }
      const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
      if (!validTypes.includes(file.type)) {
        setError('Community logo must be a JPG, PNG, or WEBP image.');
        if (logoInputRef.current) logoInputRef.current.value = '';
        return;
      }
      setError(null);
      setPendingLogoFile(file);
      setIsLogoAdjustModalOpen(true);
    }
  };

  const handleApplyLogoAdjustment = ({ file: adjustedFile, previewUrl, adjustmentState: state }) => {
    if (logoPreview && logoPreview.startsWith('blob:')) {
      URL.revokeObjectURL(logoPreview);
    }
    setLogo(adjustedFile);
    setLogoPreview(previewUrl);
    setLogoAdjustmentState(state);
    setIsLogoAdjustModalOpen(false);
  };

  const handleCancelLogoAdjustment = () => {
    setIsLogoAdjustModalOpen(false);
    if (!logo) {
      setPendingLogoFile(null);
      if (logoInputRef.current) {
        logoInputRef.current.value = '';
      }
    }
  };

  const handleRemoveLogo = () => {
    if (logoPreview && logoPreview.startsWith('blob:')) {
      URL.revokeObjectURL(logoPreview);
    }
    setLogo(null);
    setLogoPreview(null);
    setPendingLogoFile(null);
    setLogoAdjustmentState(null);
    if (logoInputRef.current) {
      logoInputRef.current.value = '';
    }
  };

  const handleCoverChange = (e) => {
    const file = e.target.files?.[0];
    if (file) {
      if (file.size > 5 * 1024 * 1024) {
        setError('Cover photo must not exceed 5 MB.');
        if (coverInputRef.current) coverInputRef.current.value = '';
        return;
      }
      const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
      if (!validTypes.includes(file.type)) {
        setError('Cover photo must be a JPG, PNG, or WEBP image.');
        if (coverInputRef.current) coverInputRef.current.value = '';
        return;
      }
      setError(null);
      setPendingCoverFile(file);
      setIsCoverAdjustModalOpen(true);
    }
  };

  const handleApplyCoverAdjustment = ({ file: adjustedFile, previewUrl, adjustmentState: state }) => {
    if (coverPreview && coverPreview.startsWith('blob:')) {
      URL.revokeObjectURL(coverPreview);
    }
    setCoverPhoto(adjustedFile);
    setCoverPreview(previewUrl);
    setCoverAdjustmentState(state);
    setIsCoverAdjustModalOpen(false);
  };

  const handleCancelCoverAdjustment = () => {
    setIsCoverAdjustModalOpen(false);
    if (!coverPhoto) {
      setPendingCoverFile(null);
      if (coverInputRef.current) {
        coverInputRef.current.value = '';
      }
    }
  };

  const handleRemoveCover = () => {
    if (coverPreview && coverPreview.startsWith('blob:')) {
      URL.revokeObjectURL(coverPreview);
    }
    setCoverPhoto(null);
    setCoverPreview(null);
    setPendingCoverFile(null);
    setCoverAdjustmentState(null);
    if (coverInputRef.current) {
      coverInputRef.current.value = '';
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!isVerified) {
      setShowVerifyModal(true);
      return;
    }
    if (isSubmitting) return;
    setIsSubmitting(true);
    setError(null);

    const formData = new FormData();
    formData.append('name', name.trim());
    formData.append('category', category);
    formData.append('visibility', visibility);
    if (description.trim()) formData.append('description', description.trim());
    if (rules.trim()) formData.append('rules', rules.trim());
    if (tags.trim()) formData.append('tags', tags.trim());
    if (coverPhoto) formData.append('cover_photo', coverPhoto);
    if (logo) formData.append('logo', logo);

    try {
      const res = await communityApi.createCommunity(formData);
      if (res && res.community) {
        navigate(`/member/community/${res.community.slug}`);
      } else {
        navigate('/member/community');
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to create community.');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div style={{ width: '100%', margin: '0 auto' }}>
      <div style={{ marginBottom: '16px' }}>
        <Link
          to="/member/community"
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
          <span>Back to Community Hub</span>
        </Link>
      </div>

      <div className="card" style={{ padding: '32px', borderRadius: '18px' }}>
        <header style={{ marginBottom: '24px' }}>
          <h1 style={{ fontSize: '1.4rem', fontWeight: 800, margin: '0 0 6px 0' }}>Create New Community</h1>
          <p style={{ color: 'var(--color-text-secondary)', margin: 0 }}>
            Establish a group for members to collaborate, share posts, and grow.
          </p>
        </header>

        {!isVerified && (
          <div
            style={{
              background: 'linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%)',
              border: '1px solid #fcd34d',
              borderRadius: '14px',
              padding: '16px 20px',
              marginBottom: '24px',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              flexWrap: 'wrap',
              gap: '14px',
            }}
          >
            <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
              <div style={{ width: '40px', height: '40px', borderRadius: '10px', background: '#d97706', color: '#ffffff', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                <ShieldCheck size={22} />
              </div>
              <div>
                <strong style={{ fontSize: '14.5px', color: '#92400e', display: 'block', marginBottom: '2px' }}>
                  Mobile WhatsApp Verification Required
                </strong>
                <p style={{ margin: 0, fontSize: '13px', color: '#78350f' }}>
                  Only verified members can create a community on MLM Book. Please complete mobile verification through WhatsApp to continue.
                </p>
              </div>
            </div>

            <button
              type="button"
              className="member-button"
              style={{
                background: '#059669',
                color: '#ffffff',
                border: 'none',
                fontWeight: 700,
                fontSize: '13px',
                padding: '9px 16px',
                borderRadius: '10px',
              }}
              onClick={() => setShowVerifyModal(true)}
            >
              <Phone size={14} />
              <span>Verify Mobile on WhatsApp</span>
            </button>
          </div>
        )}

        {error && (
          <div style={{ padding: '12px 16px', background: '#fee2e2', color: '#b91c1c', borderRadius: '10px', marginBottom: '20px', fontSize: '0.875rem' }}>
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} className="member-form">
          <div className="form-group" style={{ marginBottom: '18px' }}>
            <label htmlFor="community-name" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
              Community Name *
            </label>
            <input
              type="text"
              id="community-name"
              value={name}
              onChange={(e) => setName(e.target.value)}
              required
              placeholder="e.g. Crypto Leaders & Forex Traders"
              style={{ width: '100%', padding: '12px', borderRadius: '10px', border: '1px solid var(--color-border-soft)' }}
            />
          </div>

          <div className="row g-3" style={{ marginBottom: '18px' }}>
            <div className="col-12 col-md-6">
              <div className="form-group">
                <label htmlFor="community-category" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
                  Category *
                </label>
                <select
                  id="community-category"
                  value={category}
                  onChange={(e) => setCategory(e.target.value)}
                  required
                  disabled={isLoading}
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
                <label htmlFor="community-visibility" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
                  Privacy & Visibility *
                </label>
                <select
                  id="community-visibility"
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
            <label htmlFor="community-description" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
              Description
            </label>
            <textarea
              id="community-description"
              rows={4}
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              placeholder="What is this community about? Who should join?"
              style={{ width: '100%', padding: '12px', borderRadius: '10px', border: '1px solid var(--color-border-soft)', resize: 'vertical' }}
            />
          </div>

          <div className="form-group" style={{ marginBottom: '18px' }}>
            <label htmlFor="community-rules" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
              Community Rules (optional)
            </label>
            <textarea
              id="community-rules"
              rows={3}
              value={rules}
              onChange={(e) => setRules(e.target.value)}
              placeholder="Set community guidelines, posting policies, or etiquette..."
              style={{ width: '100%', padding: '12px', borderRadius: '10px', border: '1px solid var(--color-border-soft)', resize: 'vertical' }}
            />
          </div>

          <div className="form-group" style={{ marginBottom: '18px' }}>
            <label htmlFor="community-tags" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
              Tags (comma separated)
            </label>
            <input
              type="text"
              id="community-tags"
              value={tags}
              onChange={(e) => setTags(e.target.value)}
              placeholder="e.g. trading, networking, passive-income"
              style={{ width: '100%', padding: '12px', borderRadius: '10px', border: '1px solid var(--color-border-soft)' }}
            />
          </div>

          {/* Photos Upload Row */}
          <div className="row g-3" style={{ marginBottom: '24px' }}>
            <div className="col-12 col-md-6">
              <div className="form-group">
                <label htmlFor="cover-input" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
                  Cover Photo <span style={{ fontWeight: 400, color: '#64748b' }}>(JPG, PNG, WEBP - Max 5MB)</span>
                </label>
                <label
                  htmlFor="cover-input"
                  style={{
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    justifyContent: 'center',
                    gap: '6px',
                    padding: '20px',
                    borderRadius: '12px',
                    border: '1px dashed var(--color-border-soft)',
                    background: '#f8fafc',
                    cursor: 'pointer',
                  }}
                >
                  <Upload size={18} />
                  <span style={{ fontSize: '13px' }}>{coverPhoto ? coverPhoto.name : 'Upload Cover Photo'}</span>
                  <input
                    id="cover-input"
                    ref={coverInputRef}
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    onChange={handleCoverChange}
                    style={{ display: 'none' }}
                  />
                </label>
                {coverPreview && (
                  <div style={{ marginTop: '10px' }}>
                    <div style={{ position: 'relative', width: '100%', maxHeight: '140px', borderRadius: '8px', overflow: 'hidden', border: '1px solid #e2e8f0', background: '#0f172a' }}>
                      <img
                        src={coverPreview}
                        alt="Cover preview"
                        style={{ width: '100%', maxHeight: '140px', objectFit: 'cover', display: 'block' }}
                      />
                    </div>
                    <div style={{ display: 'flex', gap: '8px', marginTop: '8px' }}>
                      <button
                        type="button"
                        onClick={() => {
                          if (pendingCoverFile || coverPhoto) {
                            setIsCoverAdjustModalOpen(true);
                          }
                        }}
                        className="member-button member-button--secondary"
                        style={{ padding: '6px 12px', fontSize: '12px', display: 'inline-flex', alignItems: 'center', gap: '5px' }}
                        title="Adjust Framing"
                      >
                        <Crop size={14} aria-hidden="true" />
                        <span>Adjust Framing</span>
                      </button>
                      <button
                        type="button"
                        onClick={handleRemoveCover}
                        className="member-button member-button--secondary"
                        style={{ padding: '6px 10px', fontSize: '12px', color: '#ef4444' }}
                        title="Remove Cover"
                      >
                        <Trash2 size={14} aria-hidden="true" />
                      </button>
                    </div>
                  </div>
                )}
              </div>
            </div>

            <div className="col-12 col-md-6">
              <div className="form-group">
                <label htmlFor="logo-input" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
                  Community Logo / Avatar <span style={{ fontWeight: 400, color: '#64748b' }}>(JPG, PNG, WEBP - Max 2MB)</span>
                </label>
                <label
                  htmlFor="logo-input"
                  style={{
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    justifyContent: 'center',
                    gap: '6px',
                    padding: '20px',
                    borderRadius: '12px',
                    border: '1px dashed var(--color-border-soft)',
                    background: '#f8fafc',
                    cursor: 'pointer',
                  }}
                >
                  <Upload size={18} />
                  <span style={{ fontSize: '13px' }}>{logo ? logo.name : 'Upload Logo'}</span>
                  <input
                    id="logo-input"
                    ref={logoInputRef}
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    onChange={handleLogoChange}
                    style={{ display: 'none' }}
                  />
                </label>
                {logoPreview && (
                  <div style={{ marginTop: '10px', display: 'flex', alignItems: 'center', gap: '12px' }}>
                    <img
                      src={logoPreview}
                      alt="Logo preview"
                      style={{ width: '60px', height: '60px', borderRadius: '50%', objectFit: 'cover', border: '2px solid #e2e8f0', background: '#f8fafc' }}
                    />
                    <div style={{ display: 'flex', gap: '8px' }}>
                      <button
                        type="button"
                        onClick={() => {
                          if (pendingLogoFile || logo) {
                            setIsLogoAdjustModalOpen(true);
                          }
                        }}
                        className="member-button member-button--secondary"
                        style={{ padding: '6px 12px', fontSize: '12px', display: 'inline-flex', alignItems: 'center', gap: '5px' }}
                        title="Adjust Framing"
                      >
                        <Crop size={14} aria-hidden="true" />
                        <span>Adjust Framing</span>
                      </button>
                      <button
                        type="button"
                        onClick={handleRemoveLogo}
                        className="member-button member-button--secondary"
                        style={{ padding: '6px 10px', fontSize: '12px', color: '#ef4444' }}
                        title="Remove Logo"
                      >
                        <Trash2 size={14} aria-hidden="true" />
                      </button>
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>

          <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '12px' }}>
            <Link to="/member/community" className="member-button member-button--secondary">
              Cancel
            </Link>
            <button
              type="submit"
              className="member-button member-button--primary"
              disabled={isSubmitting}
              onClick={(e) => {
                if (!isVerified) {
                  e.preventDefault();
                  setShowVerifyModal(true);
                }
              }}
            >
              {isSubmitting ? 'Creating...' : 'Create Community'}
            </button>
          </div>
        </form>
      </div>

      {/* Community Logo Adjust & Frame Modal */}
      {isLogoAdjustModalOpen && (pendingLogoFile || logo) && (
        <ImageAdjustmentModal
          isOpen={isLogoAdjustModalOpen}
          file={pendingLogoFile || logo}
          initialAdjustment={logoAdjustmentState}
          onApply={handleApplyLogoAdjustment}
          onCancel={handleCancelLogoAdjustment}
          currentUser={currentUser}
          previewMode="community_logo"
          defaultAspectRatioId="1:1"
          communityData={{
            name,
            category,
          }}
        />
      )}

      {/* Community Cover Banner Adjust & Frame Modal */}
      {isCoverAdjustModalOpen && (pendingCoverFile || coverPhoto) && (
        <ImageAdjustmentModal
          isOpen={isCoverAdjustModalOpen}
          file={pendingCoverFile || coverPhoto}
          initialAdjustment={coverAdjustmentState}
          onApply={handleApplyCoverAdjustment}
          onCancel={handleCancelCoverAdjustment}
          currentUser={currentUser}
          previewMode="community_cover"
          defaultAspectRatioId="16:9"
          communityData={{
            name,
            category,
          }}
        />
      )}

      {showVerifyModal && (
        <AccountVerificationModal
          isOpen={showVerifyModal}
          promptMessage="Please verify your mobile number through WhatsApp before creating a community."
          onClose={() => setShowVerifyModal(false)}
          onVerified={() => {
            setShowVerifyModal(false);
          }}
        />
      )}

    </div>
  );
}

export default CreateCommunityPage;
