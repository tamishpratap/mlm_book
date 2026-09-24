import { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { User, MapPin, Shield, Image } from 'lucide-react';
import { Button } from 'primereact/button';
import { InputText } from 'primereact/inputtext';
import { InputTextarea } from 'primereact/inputtextarea';
import { InputSwitch } from 'primereact/inputswitch';
import { PageHeader } from '../../components/common/PageHeader';
import { FormSection } from '../../components/common/FormSection';
import { FormField } from '../../components/common/FormField';
import { LoadingSpinner } from '../../components/common/LoadingSpinner';
import { ErrorState } from '../../components/common/ErrorState';
import { useToast } from '../../hooks/useToast';
import { membersApi } from '../../api';
import { getMemberCoverUrl, getMemberAvatarUrl } from '../../utils/mediaHelper';

export function MemberEditPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { showSuccess, showError } = useToast();

  const [formData, setFormData] = useState({
    name: '',
    user_id: '',
    email: '',
    phone: '',
    gender: '',
    date_of_birth: '',
    city: '',
    country: '',
    website: '',
    bio: '',
    is_verified: false,
    remove_profile_photo: false,
    remove_cover_photo: false,
  });

  const [profilePhotoFile, setProfilePhotoFile] = useState(null);
  const [profilePhotoPreview, setProfilePhotoPreview] = useState(null);
  const [profilePhotoError, setProfilePhotoError] = useState(false);
  const [coverPhotoFile, setCoverPhotoFile] = useState(null);
  const [coverPhotoPreview, setCoverPhotoPreview] = useState(null);
  const [coverPhotoError, setCoverPhotoError] = useState(false);

  const [fieldErrors, setFieldErrors] = useState({});
  const [generalError, setGeneralError] = useState('');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  const loadMemberData = useCallback(async () => {
    setLoading(true);
    setGeneralError('');
    setCoverPhotoError(false);
    setProfilePhotoError(false);
    try {
      const res = await membersApi.getMember(id);
      const m = res?.member || res?.data || res;
      if (m) {
        setFormData({
          name: m.name || '',
          user_id: m.user_id ? m.user_id.replace(/^@/, '') : '',
          email: m.email || '',
          phone: m.phone || '',
          gender: m.gender || '',
          date_of_birth: m.date_of_birth ? m.date_of_birth.substring(0, 10) : '',
          city: m.city || '',
          country: m.country || '',
          website: m.website || '',
          bio: m.bio || '',
          is_verified: Boolean(m.mobile_verified_at || m.is_verified),
          remove_profile_photo: false,
          remove_cover_photo: false,
        });

        const avatar = getMemberAvatarUrl(m);
        if (avatar) {
          setProfilePhotoPreview(avatar);
        } else {
          setProfilePhotoPreview(null);
        }

        const cover = getMemberCoverUrl(m);
        if (cover) {
          setCoverPhotoPreview(cover);
        } else {
          setCoverPhotoPreview(null);
        }
      }
    } catch (err) {
      setGeneralError(err.message || 'Failed to load member edit details.');
    } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => {
    loadMemberData();
  }, [loadMemberData]);

  useEffect(() => {
    return () => {
      if (profilePhotoPreview && profilePhotoPreview.startsWith('blob:')) {
        URL.revokeObjectURL(profilePhotoPreview);
      }
      if (coverPhotoPreview && coverPhotoPreview.startsWith('blob:')) {
        URL.revokeObjectURL(coverPhotoPreview);
      }
    };
  }, [profilePhotoPreview, coverPhotoPreview]);

  const handleChange = (key, value) => {
    setFormData((prev) => ({ ...prev, [key]: value }));
    if (fieldErrors[key]) {
      setFieldErrors((prev) => ({ ...prev, [key]: undefined }));
    }
  };

  const handleProfilePhotoSelect = (e) => {
    const file = e.target.files?.[0];
    if (file) {
      setProfilePhotoFile(file);
      if (profilePhotoPreview && profilePhotoPreview.startsWith('blob:')) {
        URL.revokeObjectURL(profilePhotoPreview);
      }
      setProfilePhotoPreview(URL.createObjectURL(file));
      setProfilePhotoError(false);
      handleChange('remove_profile_photo', false);
    }
  };

  const handleCoverPhotoSelect = (e) => {
    const file = e.target.files?.[0];
    if (file) {
      setCoverPhotoFile(file);
      if (coverPhotoPreview && coverPhotoPreview.startsWith('blob:')) {
        URL.revokeObjectURL(coverPhotoPreview);
      }
      setCoverPhotoPreview(URL.createObjectURL(file));
      setCoverPhotoError(false);
      handleChange('remove_cover_photo', false);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setFieldErrors({});
    setGeneralError('');

    // Quick client check
    const errors = {};
    if (!formData.name.trim()) errors.name = 'Full name is required.';
    if (!formData.user_id.trim()) errors.user_id = 'Username/handle is required.';
    if (!formData.email.trim()) errors.email = 'Email address is required.';

    if (Object.keys(errors).length > 0) {
      setFieldErrors(errors);
      return;
    }

    setSaving(true);
    try {
      const files = {};
      if (profilePhotoFile) files.profile_photo = profilePhotoFile;
      if (coverPhotoFile) files.cover_photo = coverPhotoFile;

      const payload = {
        ...formData,
        is_verified: formData.is_verified ? 1 : 0,
        remove_profile_photo: formData.remove_profile_photo ? 1 : 0,
        remove_cover_photo: formData.remove_cover_photo ? 1 : 0,
      };

      await membersApi.updateMember(id, payload, files);
      showSuccess(`Member profile for ${formData.name} updated successfully.`);
      navigate(`/admin/members/${id}`);
    } catch (err) {
      if (err.errors && Object.keys(err.errors).length > 0) {
        setFieldErrors(err.errors);
      } else {
        setGeneralError(err.message || 'Failed to update member profile.');
        showError(err.message || 'Validation or server error occurred.');
      }
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return <LoadingSpinner fullScreen message="Loading member edit form..." />;
  }

  if (generalError && !formData.name) {
    return (
      <div className="space-y-6">
        <PageHeader
          title="Edit Member"
          subtitle="Update profile details, contact information, verification state, and media assets."
          breadcrumbs={[{ label: 'Members', to: '/admin/members' }, { label: 'Edit Member' }]}
        />
        <ErrorState
          title="Failed to Load Member"
          message={generalError}
          onRetry={loadMemberData}
        />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <PageHeader
        title={`Edit Member: ${formData.name}`}
        subtitle="Update profile details, contact information, verification state, and media assets."
        breadcrumbs={[
          { label: 'Members', to: '/admin/members' },
          { label: formData.name, to: `/admin/members/${id}` },
          { label: 'Edit' },
        ]}
        actions={
          <Button
            label="Back to Profile"
            icon="pi pi-arrow-left"
            size="small"
            onClick={() => navigate(`/admin/members/${id}`)}
            className="p-button-outlined p-button-secondary text-xs"
          />
        }
      />

      {generalError && (
        <div className="p-4 bg-red-50/80 border border-red-200 rounded-xl text-xs text-red-700">
          {generalError}
        </div>
      )}

      {/* Main Edit Form */}
      <form onSubmit={handleSubmit} className="space-y-6">
        {/* SECTION 1: Account & Personal Information */}
        <FormSection
          title="1. Account & Personal Information"
          description="Primary identity, contact details, and platform username handle."
          icon={User}
        >
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <FormField label="Full Name" id="name" required error={fieldErrors.name}>
              <InputText
                id="name"
                value={formData.name}
                onChange={(e) => handleChange('name', e.target.value)}
                placeholder="e.g. John Doe"
                className={`w-full text-xs ${fieldErrors.name ? 'p-invalid' : ''}`}
                disabled={saving}
              />
            </FormField>

            <FormField
              label="Username / Handle"
              id="user_id"
              required
              error={fieldErrors.user_id}
              startAddon="@"
            >
              <InputText
                id="user_id"
                value={formData.user_id}
                onChange={(e) => handleChange('user_id', e.target.value)}
                placeholder="username"
                className={`w-full text-xs has-start-addon ${fieldErrors.user_id ? 'p-invalid' : ''}`}
                disabled={saving}
              />
            </FormField>

            <FormField label="Email Address" id="email" required error={fieldErrors.email}>
              <InputText
                id="email"
                type="email"
                value={formData.email}
                onChange={(e) => handleChange('email', e.target.value)}
                placeholder="user@example.com"
                className={`w-full text-xs ${fieldErrors.email ? 'p-invalid' : ''}`}
                disabled={saving}
              />
            </FormField>

            <FormField label="Phone Number" id="phone" error={fieldErrors.phone}>
              <InputText
                id="phone"
                value={formData.phone}
                onChange={(e) => handleChange('phone', e.target.value)}
                placeholder="+1 555-0199"
                className={`w-full text-xs ${fieldErrors.phone ? 'p-invalid' : ''}`}
                disabled={saving}
              />
            </FormField>

            <FormField label="Gender" id="gender" error={fieldErrors.gender}>
              <select
                id="gender"
                value={formData.gender}
                onChange={(e) => handleChange('gender', e.target.value)}
                className="w-full h-[38px] px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg text-slate-800 transition-colors focus:outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                disabled={saving}
              >
                <option value="">-- Select Gender --</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
                <option value="prefer_not_to_say">Prefer not to say</option>
              </select>
            </FormField>

            <FormField label="Date of Birth" id="date_of_birth" error={fieldErrors.date_of_birth}>
              <input
                id="date_of_birth"
                type="date"
                value={formData.date_of_birth}
                onChange={(e) => handleChange('date_of_birth', e.target.value)}
                className="w-full h-[38px] px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg text-slate-800 transition-colors focus:outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                disabled={saving}
              />
            </FormField>
          </div>
        </FormSection>

        {/* SECTION 2: Location & Biography */}
        <FormSection
          title="2. Location & Biography"
          description="Geographical residence, portfolio link, and profile biography."
          icon={MapPin}
        >
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <FormField label="City" id="city" error={fieldErrors.city}>
              <InputText
                id="city"
                value={formData.city}
                onChange={(e) => handleChange('city', e.target.value)}
                placeholder="e.g. New York"
                className={`w-full text-xs ${fieldErrors.city ? 'p-invalid' : ''}`}
                disabled={saving}
              />
            </FormField>

            <FormField label="Country" id="country" error={fieldErrors.country}>
              <InputText
                id="country"
                value={formData.country}
                onChange={(e) => handleChange('country', e.target.value)}
                placeholder="e.g. United States"
                className={`w-full text-xs ${fieldErrors.country ? 'p-invalid' : ''}`}
                disabled={saving}
              />
            </FormField>

            <div className="md:col-span-2">
              <FormField label="Website / Portfolio URL" id="website" error={fieldErrors.website}>
                <InputText
                  id="website"
                  value={formData.website}
                  onChange={(e) => handleChange('website', e.target.value)}
                  placeholder="https://example.com"
                  className={`w-full text-xs ${fieldErrors.website ? 'p-invalid' : ''}`}
                  disabled={saving}
                />
              </FormField>
            </div>

            <div className="md:col-span-2">
              <FormField label="Bio / About Member" id="bio" error={fieldErrors.bio}>
                <InputTextarea
                  id="bio"
                  value={formData.bio}
                  onChange={(e) => handleChange('bio', e.target.value)}
                  rows={4}
                  placeholder="Short introduction or background of the member..."
                  className={`w-full text-xs ${fieldErrors.bio ? 'p-invalid' : ''}`}
                  disabled={saving}
                />
              </FormField>
            </div>
          </div>
        </FormSection>

        {/* SECTION 3: Verification State */}
        <FormSection
          title="3. Verification & Account State"
          description="Toggle whether the member account holds official verified status."
          icon={Shield}
        >
          <div className="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-200">
            <div>
              <span className="text-xs font-bold text-slate-800 block">Verified Member Account</span>
              <span className="text-[11px] text-slate-500">
                When enabled, this member account is considered fully verified and approved on the platform.
              </span>
            </div>
            <InputSwitch
              checked={formData.is_verified}
              onChange={(e) => handleChange('is_verified', e.value)}
              disabled={saving}
            />
          </div>
        </FormSection>

        {/* SECTION 4: Media & Branding Assets */}
        <FormSection
          title="4. Media & Branding Assets"
          description="Update member profile avatar and cover background banner."
          icon={Image}
        >
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {/* Profile Avatar */}
            <div className="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
              <div className="flex items-center justify-between">
                <label className="text-xs font-bold text-slate-800 block">Profile Avatar Photo</label>
                {(profilePhotoPreview || profilePhotoFile) && !formData.remove_profile_photo && (
                  <button
                    type="button"
                    onClick={() => {
                      setProfilePhotoFile(null);
                      setProfilePhotoPreview(null);
                      handleChange('remove_profile_photo', true);
                    }}
                    className="text-[11px] text-red-600 hover:text-red-700 font-medium cursor-pointer"
                  >
                    Remove Avatar
                  </button>
                )}
                {formData.remove_profile_photo && (
                  <button
                    type="button"
                    onClick={() => {
                      handleChange('remove_profile_photo', false);
                      loadMemberData();
                    }}
                    className="text-[11px] text-blue-600 hover:text-blue-700 font-medium cursor-pointer"
                  >
                    Undo Remove
                  </button>
                )}
              </div>
              <div className="flex items-center space-x-4">
                {profilePhotoPreview && !profilePhotoError && !formData.remove_profile_photo ? (
                  <img
                    src={profilePhotoPreview}
                    alt="Preview"
                    className="w-16 h-16 rounded-full object-cover ring-2 ring-slate-300 bg-white"
                    onError={() => setProfilePhotoError(true)}
                  />
                ) : (
                  <div className="w-16 h-16 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center font-bold text-sm">
                    {formData.remove_profile_photo ? 'None' : 'Photo'}
                  </div>
                )}
                <div className="flex-1">
                  <input
                    type="file"
                    accept="image/jpeg,image/png,image/jpg,image/webp"
                    onChange={handleProfilePhotoSelect}
                    className="text-xs text-slate-600 file:mr-2 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                    disabled={saving}
                  />
                  <span className="text-[10px] text-slate-400 block mt-1">
                    Recommended 400x400px (JPG, PNG, WebP up to 5MB)
                  </span>
                </div>
              </div>
            </div>

            {/* Cover Banner */}
            <div className="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
              <div className="flex items-center justify-between">
                <label className="text-xs font-bold text-slate-800 block">Cover Banner Photo</label>
                {(coverPhotoPreview || coverPhotoFile) && !formData.remove_cover_photo && (
                  <button
                    type="button"
                    onClick={() => {
                      setCoverPhotoFile(null);
                      setCoverPhotoPreview(null);
                      handleChange('remove_cover_photo', true);
                    }}
                    className="text-[11px] text-red-600 hover:text-red-700 font-medium cursor-pointer"
                  >
                    Remove Cover
                  </button>
                )}
                {formData.remove_cover_photo && (
                  <button
                    type="button"
                    onClick={() => {
                      handleChange('remove_cover_photo', false);
                      loadMemberData();
                    }}
                    className="text-[11px] text-blue-600 hover:text-blue-700 font-medium cursor-pointer"
                  >
                    Undo Remove
                  </button>
                )}
              </div>
              <div className="space-y-2">
                <div className="h-14 rounded-lg bg-slate-900 overflow-hidden relative">
                  {coverPhotoPreview && !coverPhotoError && !formData.remove_cover_photo ? (
                    <img
                      src={coverPhotoPreview}
                      alt="Cover Preview"
                      className="w-full h-full object-cover"
                      onError={() => setCoverPhotoError(true)}
                    />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center text-slate-400 text-xs bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-900">
                      {formData.remove_cover_photo ? 'Marked for removal' : 'No Cover Image'}
                    </div>
                  )}
                </div>
                <input
                  type="file"
                  accept="image/jpeg,image/png,image/jpg,image/webp"
                  onChange={handleCoverPhotoSelect}
                  className="text-xs text-slate-600 file:mr-2 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                  disabled={saving}
                />
                <span className="text-[10px] text-slate-400 block">
                  Recommended 1200x400px (JPG, PNG, WebP up to 10MB)
                </span>
              </div>
            </div>
          </div>
        </FormSection>

        {/* Form Action Buttons */}
        <div className="flex flex-wrap items-center justify-end gap-2.5 sm:gap-3 pt-4 border-t border-slate-200">
          <Button
            type="button"
            label="Cancel"
            icon="pi pi-times"
            size="small"
            onClick={() => navigate(`/admin/members/${id}`)}
            disabled={saving}
            className="p-button-outlined p-button-secondary text-xs"
          />
          <Button
            type="submit"
            label={saving ? 'Saving...' : 'Save Changes'}
            icon={saving ? 'pi pi-spin pi-spinner' : 'pi pi-check'}
            size="small"
            loading={saving}
            className="p-button-primary text-xs shadow-xs"
          />
        </div>
      </form>
    </div>
  );
}

export default MemberEditPage;
