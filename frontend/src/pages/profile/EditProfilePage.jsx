import { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { ArrowLeft, Save, Mail } from 'lucide-react';
import profileApi from '../../api/profileApi';
import useAuth from '../../hooks/useAuth';
import { getAvatarUrl, getCoverUrl } from '../../utils/assetHelper';

function getInitials(name) {
  if (!name) return 'U';
  const parts = name.trim().split(/\s+/);
  return parts.slice(0, 2).map((p) => p[0].toUpperCase()).join('') || 'U';
}

export function EditProfilePage() {
  const { user, setUser } = useAuth();
  const navigate = useNavigate();

  const [formData, setFormData] = useState({
    name: '',
    bio: '',
    date_of_birth: '',
    gender: '',
    city: '',
    country: '',
    website: '',
  });

  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState(null);
  const [fieldErrors, setFieldErrors] = useState({});
  const [successMessage, setSuccessMessage] = useState(null);
  const [avatarImgError, setAvatarImgError] = useState(false);

  useEffect(() => {
    setAvatarImgError(false);
  }, [user?.profile_photo]);

  useEffect(() => {
    let isMounted = true;

    profileApi
      .getEditProfile()
      .then((res) => {
        if (isMounted && res && res.success && res.member) {
          const m = res.member;
          setFormData({
            name: m.name || '',
            bio: m.bio || '',
            date_of_birth: m.date_of_birth ? m.date_of_birth.substring(0, 10) : '',
            gender: m.gender || '',
            city: m.city || '',
            country: m.country || '',
            website: m.website || '',
          });
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load profile details.');
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, []);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    if (fieldErrors[name]) {
      setFieldErrors((prev) => ({ ...prev, [name]: null }));
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (isSaving) return;

    setIsSaving(true);
    setError(null);
    setFieldErrors({});
    setSuccessMessage(null);

    try {
      const res = await profileApi.updateProfile(formData);
      if (res && res.success) {
        setSuccessMessage('Your profile has been updated successfully.');
        if (res.member) {
          setUser(res.member);
        }
        setTimeout(() => {
          navigate('/member/profile');
        }, 1200);
      }
    } catch (err) {
      if (err.response?.status === 422 && err.response?.data?.errors) {
        setFieldErrors(err.response.data.errors);
      } else {
        setError(err.response?.data?.message || 'Failed to update profile.');
      }
    } finally {
      setIsSaving(false);
    }
  };

  if (isLoading) {
    return (
      <div style={{ textAlign: 'center', padding: '60px', color: 'var(--color-text-secondary)' }}>
        Loading profile details...
      </div>
    );
  }

  const member = user || {};
  const initials = getInitials(formData.name || member.name);
  const profilePhotoUrl = getAvatarUrl(member.profile_photo);
  const coverPhotoUrl = getCoverUrl(member.cover_photo);

  return (
    <div className="edit-profile-page" style={{ width: '100%', margin: '0 auto' }}>
      <header className="member-page-heading" style={{ marginBottom: '24px', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
        <div>
          <h1 style={{ fontSize: '24px', fontWeight: 700, margin: '0 0 4px 0' }}>Edit Profile</h1>
          <p style={{ margin: 0, color: 'var(--color-text-secondary)', fontSize: '14px' }}>
            Keep your personal details accurate and up to date.
          </p>
        </div>
        <Link className="member-button member-button--secondary" to="/member/profile">
          <ArrowLeft size={16} aria-hidden="true" />
          <span>Back to profile</span>
        </Link>
      </header>

      {successMessage && (
        <div style={{ padding: '12px 18px', background: '#dcfce7', color: '#15803d', borderRadius: '12px', marginBottom: '20px', fontWeight: 600, fontSize: '14px' }}>
          {successMessage}
        </div>
      )}

      {error && (
        <div style={{ padding: '12px 18px', background: '#fee2e2', color: '#b91c1c', borderRadius: '12px', marginBottom: '20px', fontSize: '14px' }}>
          {error}
        </div>
      )}

      <div className="edit-profile-grid">
        {/* Profile Preview Sidebar Card */}
        <aside className="member-card profile-preview" style={{ background: '#fff', borderRadius: '16px', border: '1px solid #e5e7eb', overflow: 'hidden' }}>
          <div
            style={{
              height: '110px',
              background: coverPhotoUrl ? `url(${coverPhotoUrl}) center/cover no-repeat` : 'linear-gradient(135deg, #4f46e5, #7c3aed)',
            }}
          />
          <div style={{ padding: '0 20px 24px 20px', textAlign: 'center', position: 'relative' }}>
            <div
              style={{
                width: '76px',
                height: '76px',
                borderRadius: '50%',
                background: '#6366f1',
                color: '#fff',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                fontWeight: 700,
                fontSize: '24px',
                margin: '-38px auto 12px auto',
                border: '4px solid #fff',
                boxShadow: '0 4px 12px rgba(0,0,0,0.1)',
                overflow: 'hidden',
              }}
            >
              {member.profile_photo && !avatarImgError ? (
                <img
                  src={profilePhotoUrl}
                  alt={member.name}
                  style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                  onError={() => setAvatarImgError(true)}
                />
              ) : (
                <span>{initials}</span>
              )}
            </div>

            <h2 style={{ fontSize: '18px', fontWeight: 700, color: '#111827', margin: '0 0 2px 0' }}>
              {formData.name || member.name}
            </h2>
            {member.user_id && (
              <span style={{ fontSize: '13px', color: '#6b7280', display: 'block', marginBottom: '12px' }}>
                @{member.user_id}
              </span>
            )}
            <p style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '6px', fontSize: '13px', color: '#4b5563', margin: 0 }}>
              <Mail size={14} color="#9ca3af" />
              <span>{member.email}</span>
            </p>
          </div>
        </aside>

        {/* Edit Form Section */}
        <section className="member-card" style={{ background: '#fff', borderRadius: '16px', border: '1px solid #e5e7eb', padding: '24px' }}>
          <header style={{ marginBottom: '20px', paddingBottom: '14px', borderBottom: '1px solid #f0f0f0' }}>
            <h2 style={{ fontSize: '18px', fontWeight: 700, color: '#111827', margin: '0 0 4px 0' }}>Profile details</h2>
            <p style={{ margin: 0, color: '#6b7280', fontSize: '13px' }}>These details appear on your Member profile.</p>
          </header>

          <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: '18px' }}>
            <div className="edit-profile-form-grid">
              {/* Full Name */}
              <div style={{ gridColumn: 'span 2' }}>
                <label style={{ display: 'block', fontSize: '13px', fontWeight: 600, color: '#374151', marginBottom: '6px' }}>
                  Full name <span style={{ color: '#ef4444' }}>*</span>
                </label>
                <input
                  type="text"
                  name="name"
                  value={formData.name}
                  onChange={handleChange}
                  maxLength={255}
                  required
                  className="form-control"
                  style={{ width: '100%', padding: '10px 14px', borderRadius: '10px', border: '1px solid #d1d5db', fontSize: '14px' }}
                />
                {fieldErrors.name && (
                  <p style={{ color: '#dc2626', fontSize: '12px', marginTop: '4px', margin: '4px 0 0 0' }}>{fieldErrors.name[0]}</p>
                )}
              </div>

              {/* Date of Birth */}
              <div>
                <label style={{ display: 'block', fontSize: '13px', fontWeight: 600, color: '#374151', marginBottom: '6px' }}>
                  Date of birth
                </label>
                <input
                  type="date"
                  name="date_of_birth"
                  value={formData.date_of_birth}
                  onChange={handleChange}
                  max={new Date().toISOString().split('T')[0]}
                  className="form-control"
                  style={{ width: '100%', padding: '10px 14px', borderRadius: '10px', border: '1px solid #d1d5db', fontSize: '14px' }}
                />
                {fieldErrors.date_of_birth && (
                  <p style={{ color: '#dc2626', fontSize: '12px', marginTop: '4px', margin: '4px 0 0 0' }}>{fieldErrors.date_of_birth[0]}</p>
                )}
              </div>

              {/* Gender */}
              <div>
                <label style={{ display: 'block', fontSize: '13px', fontWeight: 600, color: '#374151', marginBottom: '6px' }}>
                  Gender
                </label>
                <select
                  name="gender"
                  value={formData.gender}
                  onChange={handleChange}
                  className="form-control"
                  style={{ width: '100%', padding: '10px 14px', borderRadius: '10px', border: '1px solid #d1d5db', fontSize: '14px' }}
                >
                  <option value="">Select an option</option>
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                  <option value="other">Other</option>
                  <option value="prefer_not_to_say">Prefer not to say</option>
                </select>
                {fieldErrors.gender && (
                  <p style={{ color: '#dc2626', fontSize: '12px', marginTop: '4px', margin: '4px 0 0 0' }}>{fieldErrors.gender[0]}</p>
                )}
              </div>

              {/* City */}
              <div>
                <label style={{ display: 'block', fontSize: '13px', fontWeight: 600, color: '#374151', marginBottom: '6px' }}>
                  City
                </label>
                <input
                  type="text"
                  name="city"
                  value={formData.city}
                  onChange={handleChange}
                  maxLength={100}
                  placeholder="Your city"
                  className="form-control"
                  style={{ width: '100%', padding: '10px 14px', borderRadius: '10px', border: '1px solid #d1d5db', fontSize: '14px' }}
                />
                {fieldErrors.city && (
                  <p style={{ color: '#dc2626', fontSize: '12px', marginTop: '4px', margin: '4px 0 0 0' }}>{fieldErrors.city[0]}</p>
                )}
              </div>

              {/* Country */}
              <div>
                <label style={{ display: 'block', fontSize: '13px', fontWeight: 600, color: '#374151', marginBottom: '6px' }}>
                  Country
                </label>
                <input
                  type="text"
                  name="country"
                  value={formData.country}
                  onChange={handleChange}
                  maxLength={100}
                  placeholder="Your country"
                  className="form-control"
                  style={{ width: '100%', padding: '10px 14px', borderRadius: '10px', border: '1px solid #d1d5db', fontSize: '14px' }}
                />
                {fieldErrors.country && (
                  <p style={{ color: '#dc2626', fontSize: '12px', marginTop: '4px', margin: '4px 0 0 0' }}>{fieldErrors.country[0]}</p>
                )}
              </div>

              {/* Website */}
              <div style={{ gridColumn: 'span 2' }}>
                <label style={{ display: 'block', fontSize: '13px', fontWeight: 600, color: '#374151', marginBottom: '6px' }}>
                  Website
                </label>
                <input
                  type="url"
                  name="website"
                  value={formData.website}
                  onChange={handleChange}
                  maxLength={255}
                  placeholder="https://example.com"
                  className="form-control"
                  style={{ width: '100%', padding: '10px 14px', borderRadius: '10px', border: '1px solid #d1d5db', fontSize: '14px' }}
                />
                {fieldErrors.website && (
                  <p style={{ color: '#dc2626', fontSize: '12px', marginTop: '4px', margin: '4px 0 0 0' }}>{fieldErrors.website[0]}</p>
                )}
              </div>

              {/* Bio */}
              <div style={{ gridColumn: 'span 2' }}>
                <label style={{ display: 'block', fontSize: '13px', fontWeight: 600, color: '#374151', marginBottom: '6px' }}>
                  Bio
                </label>
                <textarea
                  name="bio"
                  value={formData.bio}
                  onChange={handleChange}
                  maxLength={500}
                  rows={4}
                  placeholder="Share a little about yourself..."
                  className="form-control"
                  style={{ width: '100%', padding: '10px 14px', borderRadius: '10px', border: '1px solid #d1d5db', fontSize: '14px' }}
                />
                <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: '4px' }}>
                  <span style={{ fontSize: '12px', color: '#9ca3af' }}>Maximum 500 characters.</span>
                  <span style={{ fontSize: '12px', color: formData.bio.length > 450 ? '#f59e0b' : '#9ca3af' }}>
                    {formData.bio.length}/500
                  </span>
                </div>
                {fieldErrors.bio && (
                  <p style={{ color: '#dc2626', fontSize: '12px', marginTop: '4px', margin: '4px 0 0 0' }}>{fieldErrors.bio[0]}</p>
                )}
              </div>
            </div>

            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '12px', marginTop: '12px', paddingTop: '16px', borderTop: '1px solid #f0f0f0', flexWrap: 'wrap' }}>
              <Link to="/member/profile" className="member-button member-button--secondary">
                Cancel
              </Link>
              <button type="submit" className="member-button member-button--primary" disabled={isSaving}>
                <Save size={15} aria-hidden="true" />
                <span>{isSaving ? 'Saving...' : 'Save Changes'}</span>
              </button>
            </div>
          </form>
        </section>
      </div>
    </div>
  );
}

export default EditProfilePage;
