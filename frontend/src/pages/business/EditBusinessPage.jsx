import { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { ArrowLeft, Edit3 } from 'lucide-react';
import businessApi from '../../api/businessApi';
import { getAvatarUrl, getCoverUrl } from '../../utils/assetHelper';


export function EditBusinessPage() {
  const { slug } = useParams();
  const navigate = useNavigate();

  const [categories, setCategories] = useState([]);
  const [visibilities, setVisibilities] = useState({
    public: 'Public',
    private: 'Private',
    draft: 'Draft',
  });
  const [countries, setCountries] = useState([]);
  const [dialingCodes, setDialingCodes] = useState([]);

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
    country: '',
    seo_title: '',
    meta_description: '',
    facebook: '',
    twitter: '',
    instagram: '',
    linkedin: '',
    youtube: '',
  });

  const [logoFile, setLogoFile] = useState(null);
  const [logoPreview, setLogoPreview] = useState(null);
  const [coverFile, setCoverFile] = useState(null);
  const [coverPreview, setCoverPreview] = useState(null);

  const [isLoading, setIsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState(null);



  useEffect(() => {
    let isMounted = true;

    businessApi
      .getEditBusinessPageData(slug)
      .then((data) => {
        if (isMounted && data) {
          if (Array.isArray(data.categories)) {
            const catList = data.categories.map((c) => (typeof c === 'object' ? c.name : c)).filter(Boolean);
            setCategories(catList);
          }
          if (data.visibilities && typeof data.visibilities === 'object') setVisibilities(data.visibilities);
          if (Array.isArray(data.countries)) setCountries(data.countries);
          if (Array.isArray(data.dialing_codes)) setDialingCodes(data.dialing_codes);

          if (data.business_page) {
            const page = data.business_page;
            const social = page.social_links || {};

            let code = '+91';
            let num = page.phone || '';
            if (page.phone && page.phone.startsWith('+')) {
              const matchedCode = (data.dialing_codes || []).find((dc) => page.phone.startsWith(dc.code));
              if (matchedCode) {
                code = matchedCode.code;
                num = page.phone.slice(matchedCode.code.length);
              }
            }

            setFormData({
              page_name: page.page_name || '',
              page_username: page.page_username || '',
              category: page.category || '',
              visibility: page.visibility || 'public',
              description: page.description || '',
              website: page.website || '',
              email: page.email || '',
              phone_country_code: code,
              phone_number: num,
              address: page.address || '',
              city: page.city || '',
              state: page.state || '',
              country: page.country || '',
              seo_title: page.seo_title || '',
              meta_description: page.meta_description || '',
              facebook: social.facebook || '',
              twitter: social.twitter || '',
              instagram: social.instagram || '',
              linkedin: social.linkedin || '',
              youtube: social.youtube || '',
            });

            if (page.logo) {
              setLogoPreview(getAvatarUrl(page.logo));
            }
            if (page.cover_photo) {
              setCoverPreview(getCoverUrl(page.cover_photo));
            }
          }
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load page for editing.');
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [slug]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleLogoChange = (e) => {
    const file = e.target.files?.[0];
    if (file) {
      setLogoFile(file);
      setLogoPreview(URL.createObjectURL(file));
    }
  };

  const handleCoverChange = (e) => {
    const file = e.target.files?.[0];
    if (file) {
      setCoverFile(file);
      setCoverPreview(URL.createObjectURL(file));
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
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
      const res = await businessApi.updateBusinessPage(slug, payload);
      if (res && res.business_page) {
        navigate(`/member/business-pages/${res.business_page.slug}`);
      } else {
        navigate(`/member/business-pages/${slug}`);
      }
    } catch (err) {
      setError(
        err.response?.data?.message ||
          'Failed to update Business Page. Please verify required fields.'
      );
    } finally {
      setIsSubmitting(false);
    }
  };

  if (isLoading) {
    return (
      <div style={{ textAlign: 'center', padding: '60px', color: 'var(--color-text-secondary)' }}>
        Loading business page details...
      </div>
    );
  }

  return (
    <div className="biz-page" style={{ width: '100%', margin: '0 auto', padding: '24px 0' }}>
      <header className="biz-header" style={{ marginBottom: '24px' }}>
        <div className="biz-header__info">
          <h1>
            <Edit3 size={24} aria-hidden="true" />
            <span>Edit {formData.page_name}</span>
          </h1>
          <p>Update your business information, contact details, and branding.</p>
        </div>
        <div className="biz-header__actions">
          <Link to={`/member/business-pages/${slug}`} className="member-button member-button--secondary">
            <ArrowLeft size={15} aria-hidden="true" />
            <span>Back to Profile</span>
          </Link>
        </div>
      </header>

      {error && (
        <div style={{ padding: '12px 16px', background: '#fee2e2', color: '#b91c1c', borderRadius: '12px', marginBottom: '20px' }}>
          {error}
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
            Core Page Information
          </h2>
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
              required
              minLength={3}
              maxLength={255}
            />
          </div>

          <div className="form-group">
            <label htmlFor="page_username" style={{ fontSize: '13.5px', fontWeight: 700, marginBottom: '6px', display: 'block' }}>
              Username <span style={{ color: 'red' }}>*</span>
            </label>
            <input
              type="text"
              id="page_username"
              name="page_username"
              value={formData.page_username}
              onChange={handleChange}
              className="biz-search-input"
              required
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
              Website URL
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
                required
                style={{ flex: 1 }}
              />
            </div>
          </div>
        </div>

        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: '16px' }}>
          <div className="form-group">
            <label htmlFor="address" style={{ fontSize: '13px', fontWeight: 600, marginBottom: '4px', display: 'block' }}>
              Street Address <span style={{ color: 'red' }}>*</span>
            </label>
            <input
              type="text"
              id="address"
              name="address"
              value={formData.address}
              onChange={handleChange}
              className="biz-search-input"
              required
              minLength={3}
            />
          </div>

          <div className="form-group">
            <label htmlFor="city" style={{ fontSize: '13px', fontWeight: 600, marginBottom: '4px', display: 'block' }}>
              City <span style={{ color: 'red' }}>*</span>
            </label>
            <input
              type="text"
              id="city"
              name="city"
              value={formData.city}
              onChange={handleChange}
              className="biz-search-input"
              required
              minLength={2}
            />
          </div>

          <div className="form-group">
            <label htmlFor="state" style={{ fontSize: '13px', fontWeight: 600, marginBottom: '4px', display: 'block' }}>
              State / Region <span style={{ color: 'red' }}>*</span>
            </label>
            <input
              type="text"
              id="state"
              name="state"
              value={formData.state}
              onChange={handleChange}
              className="biz-search-input"
              required
              minLength={2}
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

        {/* SEO & Social Header */}
        <div style={{ borderBottom: '1px solid #e7ecf4', paddingBottom: '8px', marginTop: '8px' }}>
          <h3 style={{ fontSize: '15px', fontWeight: 700, color: '#1d2738', margin: 0 }}>
            SEO & Social Profiles
          </h3>
        </div>

        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: '16px' }}>
          <div className="form-group">
            <label htmlFor="seo_title" style={{ fontSize: '13px', fontWeight: 600, marginBottom: '4px', display: 'block' }}>
              SEO Title
            </label>
            <input
              type="text"
              id="seo_title"
              name="seo_title"
              value={formData.seo_title}
              onChange={handleChange}
              className="biz-search-input"
              placeholder="Apex Global - Leading MLM Solutions"
            />
          </div>

          <div className="form-group">
            <label htmlFor="facebook" style={{ fontSize: '13px', fontWeight: 600, marginBottom: '4px', display: 'block' }}>
              Facebook URL
            </label>
            <input
              type="url"
              id="facebook"
              name="facebook"
              value={formData.facebook}
              onChange={handleChange}
              className="biz-search-input"
              placeholder="https://facebook.com/apexglobal"
            />
          </div>

          <div className="form-group">
            <label htmlFor="twitter" style={{ fontSize: '13px', fontWeight: 600, marginBottom: '4px', display: 'block' }}>
              Twitter / X URL
            </label>
            <input
              type="url"
              id="twitter"
              name="twitter"
              value={formData.twitter}
              onChange={handleChange}
              className="biz-search-input"
              placeholder="https://twitter.com/apexglobal"
            />
          </div>

          <div className="form-group">
            <label htmlFor="instagram" style={{ fontSize: '13px', fontWeight: 600, marginBottom: '4px', display: 'block' }}>
              Instagram URL
            </label>
            <input
              type="url"
              id="instagram"
              name="instagram"
              value={formData.instagram}
              onChange={handleChange}
              className="biz-search-input"
              placeholder="https://instagram.com/apexglobal"
            />
          </div>

          <div className="form-group">
            <label htmlFor="linkedin" style={{ fontSize: '13px', fontWeight: 600, marginBottom: '4px', display: 'block' }}>
              LinkedIn URL
            </label>
            <input
              type="url"
              id="linkedin"
              name="linkedin"
              value={formData.linkedin}
              onChange={handleChange}
              className="biz-search-input"
              placeholder="https://linkedin.com/company/apexglobal"
            />
          </div>

          <div className="form-group">
            <label htmlFor="youtube" style={{ fontSize: '13px', fontWeight: 600, marginBottom: '4px', display: 'block' }}>
              YouTube URL
            </label>
            <input
              type="url"
              id="youtube"
              name="youtube"
              value={formData.youtube}
              onChange={handleChange}
              className="biz-search-input"
              placeholder="https://youtube.com/@apexglobal"
            />
          </div>
        </div>

        {/* Branding & Media */}
        <div style={{ borderBottom: '1px solid #e7ecf4', paddingBottom: '8px', marginTop: '8px' }}>
          <h3 style={{ fontSize: '15px', fontWeight: 700, color: '#1d2738', margin: 0 }}>
            Change Branding & Media
          </h3>
        </div>

        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: '16px' }}>
          <div className="form-group">
            <label htmlFor="logo" style={{ fontSize: '13.5px', fontWeight: 700, marginBottom: '6px', display: 'block' }}>
              Change Logo <span style={{ fontWeight: 400, color: '#64748b' }}>(Max 2MB)</span>
            </label>
            <input
              type="file"
              id="logo"
              name="logo"
              accept="image/jpeg,image/png,image/webp"
              onChange={handleLogoChange}
              className="biz-search-input"
              style={{ padding: '8px 12px', fontSize: '12px' }}
            />
            {logoPreview && (
              <div style={{ marginTop: '10px' }}>
                <img
                  src={logoPreview}
                  alt="Logo preview"
                  style={{ width: '60px', height: '60px', borderRadius: '50%', objectFit: 'cover' }}
                />
              </div>
            )}
          </div>

          <div className="form-group">
            <label htmlFor="cover_photo" style={{ fontSize: '13.5px', fontWeight: 700, marginBottom: '6px', display: 'block' }}>
              Change Cover Banner <span style={{ fontWeight: 400, color: '#64748b' }}>(Max 5MB)</span>
            </label>
            <input
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
                <img
                  src={coverPreview}
                  alt="Cover preview"
                  style={{ width: '100%', maxHeight: '120px', borderRadius: '8px', objectFit: 'cover' }}
                />
              </div>
            )}
          </div>
        </div>

        <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '12px', marginTop: '16px' }}>
          <Link to={`/member/business-pages/${slug}`} className="member-button member-button--secondary">
            Cancel
          </Link>
          <button
            type="submit"
            className="member-button member-button--primary"
            disabled={isSubmitting}
          >
            {isSubmitting ? 'Saving Changes...' : 'Save Changes'}
          </button>
        </div>
      </form>

    </div>
  );
}

export default EditBusinessPage;
