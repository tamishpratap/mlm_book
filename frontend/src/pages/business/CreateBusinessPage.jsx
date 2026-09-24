import { useState, useEffect, useRef } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { ArrowLeft, PlusCircle, Crop, Trash2, ShieldCheck, Phone } from 'lucide-react';
import businessApi from '../../api/businessApi';
import useAuth from '../../hooks/useAuth';
import { ImageAdjustmentModal } from '../../components/posts/modals/ImageAdjustmentModal';
import AccountVerificationModal from '../../components/verification/AccountVerificationModal';

import { isMemberMobileVerified } from '../../utils/whatsappVerification';

export function CreateBusinessPage() {
  const navigate = useNavigate();
  const { user: currentUser } = useAuth();
  const isVerified = isMemberMobileVerified(currentUser);
  const [showVerifyModal, setShowVerifyModal] = useState(false);

  const [categories, setCategories] = useState([]);

  const [visibilities, setVisibilities] = useState({
    public: 'Public',
    private: 'Private',
    draft: 'Draft',
  });

  const [countries, setCountries] = useState([
    'United States', 'India', 'United Kingdom', 'Canada', 'Australia',
    'Germany', 'France', 'United Arab Emirates', 'Singapore', 'South Africa',
  ]);

  const [dialingCodes, setDialingCodes] = useState([
    { code: '+91', country: 'India', flag: '🇮🇳' },
    { code: '+1', country: 'United States / Canada', flag: '🇺🇸' },
    { code: '+44', country: 'United Kingdom', flag: '🇬🇧' },
    { code: '+971', country: 'United Arab Emirates', flag: '🇦🇪' },
    { code: '+61', country: 'Australia', flag: '🇦🇺' },
    { code: '+49', country: 'Germany', flag: '🇩🇪' },
  ]);

  const [formData, setFormData] = useState({
    page_name: '',
    page_username: '',
    category: '',
    visibility: 'public',
    description: '',
    website: '',
    email: '',
    phone_country_code: '+91',
    phone_number: '',
    address: '',
    city: '',
    state: '',
    country: 'India',
  });

  // Media input refs
  const logoInputRef = useRef(null);
  const coverInputRef = useRef(null);

  // Logo file, preview, and framing adjustment states
  const [logoFile, setLogoFile] = useState(null);
  const [logoPreview, setLogoPreview] = useState(null);
  const [pendingLogoFile, setPendingLogoFile] = useState(null);
  const [logoAdjustmentState, setLogoAdjustmentState] = useState(null);
  const [isLogoAdjustModalOpen, setIsLogoAdjustModalOpen] = useState(false);

  // Cover photo file, preview, and framing adjustment states
  const [coverFile, setCoverFile] = useState(null);
  const [coverPreview, setCoverPreview] = useState(null);
  const [pendingCoverFile, setPendingCoverFile] = useState(null);
  const [coverAdjustmentState, setCoverAdjustmentState] = useState(null);
  const [isCoverAdjustModalOpen, setIsCoverAdjustModalOpen] = useState(false);

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
    businessApi
      .getCreateBusinessPageData()
      .then((data) => {
        if (data) {
          if (Array.isArray(data.categories) && data.categories.length > 0) {
            const catList = data.categories.map((c) => (typeof c === 'object' ? c.name : c)).filter(Boolean);
            setCategories(catList);
            setFormData((prev) => {
              if (!prev.category || !catList.includes(prev.category)) {
                return { ...prev, category: catList[0] };
              }
              return prev;
            });
          }
          if (data.visibilities && typeof data.visibilities === 'object') setVisibilities(data.visibilities);
          if (Array.isArray(data.countries)) setCountries(data.countries);
          if (Array.isArray(data.dialing_codes)) setDialingCodes(data.dialing_codes);
        }
      })
      .catch(() => {});
  }, []);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleLogoChange = (e) => {
    const file = e.target.files?.[0];
    if (file) {
      if (file.size > 2 * 1024 * 1024) {
        setError('Business logo must not exceed 2 MB.');
        if (logoInputRef.current) logoInputRef.current.value = '';
        return;
      }
      const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
      if (!validTypes.includes(file.type)) {
        setError('Business logo must be a JPG, PNG, or WEBP image.');
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
    setLogoFile(adjustedFile);
    setLogoPreview(previewUrl);
    setLogoAdjustmentState(state);
    setIsLogoAdjustModalOpen(false);
  };

  const handleCancelLogoAdjustment = () => {
    setIsLogoAdjustModalOpen(false);
    if (!logoFile) {
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
    setLogoFile(null);
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
        setError('Cover banner must not exceed 5 MB.');
        if (coverInputRef.current) coverInputRef.current.value = '';
        return;
      }
      const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
      if (!validTypes.includes(file.type)) {
        setError('Cover banner must be a JPG, PNG, or WEBP image.');
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
    setCoverFile(adjustedFile);
    setCoverPreview(previewUrl);
    setCoverAdjustmentState(state);
    setIsCoverAdjustModalOpen(false);
  };

  const handleCancelCoverAdjustment = () => {
    setIsCoverAdjustModalOpen(false);
    if (!coverFile) {
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
    setCoverFile(null);
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

    const payload = new FormData();
    Object.keys(formData).forEach((key) => {
      if (formData[key] !== null && formData[key] !== undefined && formData[key] !== '') {
        payload.append(key, formData[key]);
      }
    });

    if (logoFile) {
      payload.append('logo', logoFile);
    }
    if (coverFile) {
      payload.append('cover_photo', coverFile);
    }

    try {
      const res = await businessApi.createBusinessPage(payload);
      if (res && res.business_page) {
        navigate(`/member/business-pages/${res.business_page.slug}`);
      } else {
        navigate('/member/business-pages');
      }
    } catch (err) {
      setError(
        err.response?.data?.message ||
          'Failed to create Business Page. Please verify all required fields.'
      );
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="biz-page" style={{ width: '100%', margin: '0 auto', padding: '24px 0' }}>
      <header className="biz-header" style={{ marginBottom: '24px' }}>
        <div className="biz-header__info">
          <h1>
            <PlusCircle size={24} aria-hidden="true" />
            <span>Create Business Page</span>
          </h1>
          <p>Establish your official enterprise or personal brand page on MLM Book.</p>
        </div>
        <div className="biz-header__actions">
          <Link to="/member/business-pages" className="member-button member-button--secondary">
            <ArrowLeft size={15} aria-hidden="true" />
            <span>Back to Business Pages</span>
          </Link>
        </div>
      </header>

      {error && (
        <div style={{ padding: '12px 16px', background: '#fee2e2', color: '#b91c1c', borderRadius: '12px', marginBottom: '20px' }}>
          {error}
        </div>
      )}

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
            <div
              style={{
                width: '40px',
                height: '40px',
                borderRadius: '10px',
                background: '#d97706',
                color: '#ffffff',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                flexShrink: 0,
              }}
            >
              <ShieldCheck size={22} />
            </div>
            <div>
              <strong style={{ fontSize: '14.5px', color: '#92400e', display: 'block', marginBottom: '2px' }}>
                Mobile WhatsApp Verification Required
              </strong>
              <p style={{ margin: 0, fontSize: '13px', color: '#78350f' }}>
                Only verified members can create a business page on MLM Book. Please complete mobile verification through WhatsApp to continue.
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

      <form
        onSubmit={handleSubmit}
        style={{
          background: '#ffffff',
          border: '1px solid #e7ecf4',
          borderRadius: '18px',
          padding: '28px',
          display: 'flex',
          flexDirection: 'column',
          gap: '20px',
          boxShadow: '0 2px 8px rgba(34, 49, 78, 0.035)',
        }}
      >
        <div style={{ borderBottom: '1px solid #e7ecf4', paddingBottom: '12px' }}>
          <h2 style={{ fontSize: '17px', fontWeight: 700, color: '#1d2738', margin: '0 0 4px 0' }}>
            Page Information
          </h2>
          <p style={{ fontSize: '13px', color: '#687386', margin: 0 }}>
            Fill in your core business details.
          </p>
        </div>

        {/* Name and Username */}
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(240px, 1fr))', gap: '16px' }}>
          <div className="form-group">
            <label htmlFor="page_name" style={{ fontSize: '13.5px', fontWeight: 700, marginBottom: '6px', display: 'block' }}>
              Business Name <span style={{ color: 'red' }}>*</span>
            </label>
            <input
              type="text"
              id="page_name"
              name="page_name"
              value={formData.page_name}
              onChange={handleChange}
              className="biz-search-input"
              placeholder="e.g. Apex Global Solutions"
              required
              minLength={3}
              maxLength={255}
            />
          </div>

          <div className="form-group">
            <label htmlFor="page_username" style={{ fontSize: '13.5px', fontWeight: 700, marginBottom: '6px', display: 'block' }}>
              Username <span style={{ fontWeight: 400, color: '#98a2b3' }}>(Optional - Auto Generated)</span>
            </label>
            <input
              type="text"
              id="page_username"
              name="page_username"
              value={formData.page_username}
              onChange={handleChange}
              className="biz-search-input"
              placeholder="e.g. apex_global"
              maxLength={100}
            />
          </div>
        </div>

        {/* Category and Visibility */}
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: '16px' }}>
          <div className="form-group">
            <label htmlFor="category" style={{ fontSize: '13.5px', fontWeight: 700, marginBottom: '6px', display: 'block' }}>
              Category <span style={{ color: 'red' }}>*</span>
            </label>
            <select
              id="category"
              name="category"
              value={formData.category}
              onChange={handleChange}
              className="biz-filter-select"
              style={{ width: '100%' }}
              required
            >
              {categories.length === 0 ? (
                <option value="" disabled>
                  Loading categories...
                </option>
              ) : (
                <>
                  {!formData.category && (
                    <option value="" disabled>
                      Select a business category
                    </option>
                  )}
                  {formData.category && !categories.includes(formData.category) && (
                    <option key={formData.category} value={formData.category}>
                      {formData.category}
                    </option>
                  )}
                  {categories.map((cat) => {
                    const catValue = typeof cat === 'object' ? cat.name : cat;
                    return (
                      <option key={catValue} value={catValue}>
                        {catValue}
                      </option>
                    );
                  })}
                </>
              )}
            </select>
          </div>

          <div className="form-group">
            <label htmlFor="visibility" style={{ fontSize: '13.5px', fontWeight: 700, marginBottom: '6px', display: 'block' }}>
              Visibility <span style={{ color: 'red' }}>*</span>
            </label>
            <select
              id="visibility"
              name="visibility"
              value={formData.visibility}
              onChange={handleChange}
              className="biz-filter-select"
              style={{ width: '100%' }}
              required
            >
              {Object.entries(visibilities).map(([key, label]) => (
                <option key={key} value={key}>
                  {label}
                </option>
              ))}
            </select>
          </div>
        </div>

        {/* Description */}
        <div className="form-group">
          <label htmlFor="description" style={{ fontSize: '13.5px', fontWeight: 700, marginBottom: '6px', display: 'block' }}>
            Description <span style={{ color: 'red' }}>*</span> <span style={{ fontSize: '12px', fontWeight: 400, color: '#64748b' }}>(Minimum 20 characters)</span>
          </label>
          <textarea
            id="description"
            name="description"
            rows={3}
            value={formData.description}
            onChange={handleChange}
            className="biz-search-input"
            style={{ height: 'auto', padding: '12px 16px', resize: 'vertical' }}
            placeholder="Describe your products, services, or organization (min 20 characters)..."
            required
            minLength={20}
            maxLength={2000}
          />
        </div>

        {/* Contact & Location Header */}
        <div style={{ borderBottom: '1px solid #e7ecf4', paddingBottom: '8px', marginTop: '8px' }}>
          <h3 style={{ fontSize: '15px', fontWeight: 700, color: '#1d2738', margin: 0 }}>
            Contact & Location Details
          </h3>
        </div>

        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: '16px' }}>
          <div className="form-group">
            <label htmlFor="website" style={{ fontSize: '13px', fontWeight: 600, marginBottom: '4px', display: 'block' }}>
              Website URL <span style={{ fontWeight: 400, color: '#98a2b3' }}>(Optional)</span>
            </label>
            <input
              type="text"
              id="website"
              name="website"
              value={formData.website}
              onChange={handleChange}
              className="biz-search-input"
              placeholder="https://example.com"
            />
          </div>

          <div className="form-group">
            <label htmlFor="email" style={{ fontSize: '13px', fontWeight: 600, marginBottom: '4px', display: 'block' }}>
              Business Email <span style={{ color: 'red' }}>*</span>
            </label>
            <input
              type="email"
              id="email"
              name="email"
              value={formData.email}
              onChange={handleChange}
              className="biz-search-input"
              placeholder="contact@example.com"
              required
            />
          </div>

          <div className="form-group">
            <label htmlFor="phone_number" style={{ fontSize: '13px', fontWeight: 600, marginBottom: '4px', display: 'block' }}>
              Phone Number <span style={{ color: 'red' }}>*</span>
            </label>
            <div style={{ display: 'flex', gap: '8px' }}>
              <select
                name="phone_country_code"
                id="phone_country_code"
                value={formData.phone_country_code}
                onChange={handleChange}
                className="biz-filter-select"
                style={{ flex: '0 0 110px', padding: '6px 8px', fontSize: '13px' }}
              >
                {dialingCodes.map((dCode) => (
                  <option key={dCode.code} value={dCode.code}>
                    {dCode.flag} {dCode.code}
                  </option>
                ))}
              </select>
              <input
                type="text"
                id="phone_number"
                name="phone_number"
                value={formData.phone_number}
                onChange={(e) =>
                  setFormData((prev) => ({
                    ...prev,
                    phone_number: e.target.value.replace(/[^0-9]/g, ''),
                  }))
                }
                className="biz-search-input"
                placeholder="9876543210"
                required
                style={{ flex: 1 }}
              />
            </div>
          </div>
        </div>

        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: '16px' }}>
          <div className="form-group">
            <label htmlFor="address" style={{ fontSize: '13px', fontWeight: 600, marginBottom: '4px', display: 'block' }}>
              Street Address <span style={{ fontWeight: 400, color: '#98a2b3' }}>(Optional)</span>
            </label>
            <input
              type="text"
              id="address"
              name="address"
              value={formData.address}
              onChange={handleChange}
              className="biz-search-input"
              placeholder="123 Business Way"
            />
          </div>

          <div className="form-group">
            <label htmlFor="city" style={{ fontSize: '13px', fontWeight: 600, marginBottom: '4px', display: 'block' }}>
              City <span style={{ fontWeight: 400, color: '#98a2b3' }}>(Optional)</span>
            </label>
            <input
              type="text"
              id="city"
              name="city"
              value={formData.city}
              onChange={handleChange}
              className="biz-search-input"
              placeholder="New York"
            />
          </div>

          <div className="form-group">
            <label htmlFor="state" style={{ fontSize: '13px', fontWeight: 600, marginBottom: '4px', display: 'block' }}>
              State / Region <span style={{ fontWeight: 400, color: '#98a2b3' }}>(Optional)</span>
            </label>
            <input
              type="text"
              id="state"
              name="state"
              value={formData.state}
              onChange={handleChange}
              className="biz-search-input"
              placeholder="NY"
            />
          </div>

          <div className="form-group">
            <label htmlFor="country" style={{ fontSize: '13px', fontWeight: 600, marginBottom: '4px', display: 'block' }}>
              Country <span style={{ color: 'red' }}>*</span>
            </label>
            <select
              id="country"
              name="country"
              value={formData.country}
              onChange={handleChange}
              className="biz-filter-select"
              style={{ width: '100%' }}
              required
            >
              {countries.map((c) => (
                <option key={c} value={c}>
                  {c}
                </option>
              ))}
            </select>
          </div>
        </div>

        {/* Branding & Media */}
        <div style={{ borderBottom: '1px solid #e7ecf4', paddingBottom: '8px', marginTop: '8px' }}>
          <h3 style={{ fontSize: '15px', fontWeight: 700, color: '#1d2738', margin: 0 }}>
            Branding & Media
          </h3>
        </div>

        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: '16px' }}>
          <div className="form-group">
            <label htmlFor="logo" style={{ fontSize: '13.5px', fontWeight: 700, marginBottom: '6px', display: 'block' }}>
              Business Logo <span style={{ fontWeight: 400, color: '#64748b' }}>(JPG, PNG, WEBP - Max 2MB)</span>
            </label>
            <input
              ref={logoInputRef}
              type="file"
              id="logo"
              name="logo"
              accept="image/jpeg,image/png,image/webp"
              onChange={handleLogoChange}
              className="biz-search-input"
              style={{ padding: '8px 12px', fontSize: '12px' }}
            />
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
                      if (pendingLogoFile || logoFile) {
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

          <div className="form-group">
            <label htmlFor="cover_photo" style={{ fontSize: '13.5px', fontWeight: 700, marginBottom: '6px', display: 'block' }}>
              Cover Banner <span style={{ fontWeight: 400, color: '#64748b' }}>(JPG, PNG, WEBP - Max 5MB)</span>
            </label>
            <input
              ref={coverInputRef}
              type="file"
              id="cover_photo"
              name="cover_photo"
              accept="image/jpeg,image/png,image/webp"
              onChange={handleCoverChange}
              className="biz-search-input"
              style={{ padding: '8px 12px', fontSize: '12px' }}
            />
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
                      if (pendingCoverFile || coverFile) {
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

        <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '12px', marginTop: '16px' }}>
          <Link to="/member/business-pages" className="member-button member-button--secondary">
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
            {isSubmitting ? 'Creating Page...' : 'Create Business Page'}
          </button>
        </div>
      </form>

      {/* Business Logo Adjust & Frame Modal */}
      {isLogoAdjustModalOpen && (pendingLogoFile || logoFile) && (
        <ImageAdjustmentModal
          isOpen={isLogoAdjustModalOpen}
          file={pendingLogoFile || logoFile}
          initialAdjustment={logoAdjustmentState}
          onApply={handleApplyLogoAdjustment}
          onCancel={handleCancelLogoAdjustment}
          currentUser={currentUser}
          previewMode="business_logo"
          defaultAspectRatioId="1:1"
          businessData={{
            page_name: formData.page_name,
            page_username: formData.page_username,
            category: formData.category,
          }}
        />
      )}

      {/* Business Cover Banner Adjust & Frame Modal */}
      {isCoverAdjustModalOpen && (pendingCoverFile || coverFile) && (
        <ImageAdjustmentModal
          isOpen={isCoverAdjustModalOpen}
          file={pendingCoverFile || coverFile}
          initialAdjustment={coverAdjustmentState}
          onApply={handleApplyCoverAdjustment}
          onCancel={handleCancelCoverAdjustment}
          currentUser={currentUser}
          previewMode="business_cover"
          defaultAspectRatioId="16:9"
          businessData={{
            page_name: formData.page_name,
            page_username: formData.page_username,
            category: formData.category,
          }}
        />
      )}

      {showVerifyModal && (
        <AccountVerificationModal
          isOpen={showVerifyModal}
          promptMessage="Please verify your mobile number through WhatsApp before creating a business page."
          onClose={() => setShowVerifyModal(false)}
          onVerified={() => {
            setShowVerifyModal(false);
          }}
        />
      )}

    </div>
  );
}

export default CreateBusinessPage;
