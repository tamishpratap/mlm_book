import { useState, useEffect, useCallback, useRef } from 'react';
import { useSearchParams, Link } from 'react-router-dom';
import {
  Shield,
  Key,
  Lock,
  Search,
  CheckCircle2,
  AlertCircle,
  Eye,
  EyeOff,
  User,
  ArrowRight,
} from 'lucide-react';
import { Button } from 'primereact/button';
import { InputText } from 'primereact/inputtext';
import { PageHeader } from '../../components/common/PageHeader';
import { FormSection } from '../../components/common/FormSection';
import { FormField } from '../../components/common/FormField';
import { StatusBadge } from '../../components/common/StatusBadge';
import { LoadingSpinner } from '../../components/common/LoadingSpinner';
import { useToast } from '../../hooks/useToast';
import { membersApi } from '../../api';
import { getMemberAvatarUrl } from '../../utils/mediaHelper';

export function MemberSecurityPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const { showSuccess, showError } = useToast();

  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState([]);
  const [searching, setSearching] = useState(false);
  const [selectedMember, setSelectedMember] = useState(null);
  const [loadingInitial, setLoadingInitial] = useState(false);

  // Form State
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [fieldErrors, setFieldErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);
  const [successBanner, setSuccessBanner] = useState(null);

  const searchDebounceRef = useRef(null);

  // Load initial member if member_id is in query params
  const loadInitialMember = useCallback(async (memberId) => {
    setLoadingInitial(true);
    try {
      const res = await membersApi.getMember(memberId);
      const m = res?.member || res?.data || res;
      if (m && m.id) {
        setSelectedMember(m);
      }
    } catch (err) {
      showError(err.message || 'Failed to load member profile.');
    } finally {
      setLoadingInitial(false);
    }
  }, [showError]);

  useEffect(() => {
    const memberIdParam = searchParams.get('member_id') || searchParams.get('id');
    if (memberIdParam) {
      loadInitialMember(memberIdParam);
    } else {
      // Load recent members for instant selection
      handleSearch('');
    }
  }, [searchParams, loadInitialMember]);

  // Execute member search
  const handleSearch = async (queryText) => {
    setSearching(true);
    try {
      const res = await membersApi.searchMembers({ q: queryText });
      const list = res?.members || res?.data || [];
      setSearchResults(list);
    } catch (err) {
      setSearchResults([]);
    } finally {
      setSearching(false);
    }
  };

  const handleQueryChange = (val) => {
    setSearchQuery(val);
    if (searchDebounceRef.current) {
      clearTimeout(searchDebounceRef.current);
    }
    searchDebounceRef.current = setTimeout(() => {
      handleSearch(val);
    }, 300);
  };

  const handleSelectMember = (member) => {
    setSelectedMember(member);
    setSuccessBanner(null);
    setFieldErrors({});
    setNewPassword('');
    setConfirmPassword('');
    setSearchParams({ member_id: member.id });
  };

  const handleClearSelection = () => {
    setSelectedMember(null);
    setSuccessBanner(null);
    setFieldErrors({});
    setNewPassword('');
    setConfirmPassword('');
    setSearchParams({});
    handleSearch('');
  };

  // Submit Password Change
  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!selectedMember) return;

    const errors = {};
    if (!newPassword) {
      errors.password = 'Please enter a new password.';
    } else if (newPassword.length < 8) {
      errors.password = 'The password must be at least 8 characters.';
    }

    if (!confirmPassword) {
      errors.password_confirmation = 'Please confirm the new password.';
    } else if (newPassword !== confirmPassword) {
      errors.password_confirmation = 'The password confirmation does not match.';
    }

    if (Object.keys(errors).length > 0) {
      setFieldErrors(errors);
      return;
    }

    setFieldErrors({});
    setSubmitting(true);
    setSuccessBanner(null);

    try {
      const res = await membersApi.updatePassword(selectedMember.id, {
        password: newPassword,
        password_confirmation: confirmPassword,
      });

      if (res && (res.success || res.message)) {
        const msg = res.message || `Password for ${selectedMember.name} has been updated successfully.`;
        showSuccess(msg);
        setSuccessBanner(msg);
        setNewPassword('');
        setConfirmPassword('');
      }
    } catch (err) {
      const respErrors = err?.response?.data?.errors || err?.errors;
      if (respErrors) {
        const formatted = {};
        Object.keys(respErrors).forEach((key) => {
          formatted[key] = Array.isArray(respErrors[key]) ? respErrors[key][0] : respErrors[key];
        });
        setFieldErrors(formatted);
      } else {
        const msg = err?.response?.data?.message || err?.message || 'Failed to update member password.';
        showError(msg);
        setFieldErrors({ general: msg });
      }
    } finally {
      setSubmitting(false);
    }
  };

  const avatarUrl = selectedMember ? getMemberAvatarUrl(selectedMember) : null;
  const isVerified = selectedMember ? Boolean(selectedMember.mobile_verified_at || selectedMember.is_verified) : false;
  const isBlocked = selectedMember ? Boolean(selectedMember.blocked_at || selectedMember.status === 'blocked') : false;

  return (
    <div className="space-y-6">
      <PageHeader
        title="Member Security"
        subtitle="Manage and change the login password for any registered platform member."
        breadcrumbs={[
          { label: 'Member Management', to: '/admin/members' },
          { label: 'Security' },
        ]}
      />

      {loadingInitial ? (
        <div className="bg-white rounded-xl border border-slate-200 p-12 text-center">
          <LoadingSpinner text="Loading member details..." />
        </div>
      ) : (
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
          {/* Left Column: Member Search & Selector (5 Cols on LG) */}
          <div className="lg:col-span-5 space-y-4">
            <div className="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
              <div className="p-4 border-b border-slate-100 flex items-center justify-between">
                <div className="flex items-center space-x-2">
                  <div className="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <User className="w-4 h-4" />
                  </div>
                  <div>
                    <h3 className="text-sm font-bold text-slate-900">Find Member</h3>
                    <p className="text-xs text-slate-500">Search by Name, User ID, Email, Phone</p>
                  </div>
                </div>
                {selectedMember && (
                  <button
                    type="button"
                    onClick={handleClearSelection}
                    className="text-xs font-semibold text-blue-600 hover:text-blue-800 transition-colors"
                  >
                    Change
                  </button>
                )}
              </div>

              <div className="p-4">
                {/* Search Bar */}
                <div className="relative mb-3">
                  <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                  <input
                    type="text"
                    value={searchQuery}
                    onChange={(e) => handleQueryChange(e.target.value)}
                    placeholder="Search name, @username, email, ID..."
                    className="w-full pl-9 pr-3 py-2 text-xs bg-slate-50 border border-slate-300 rounded-lg text-slate-800 placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                  />
                  {searching && (
                    <div className="absolute right-3 top-1/2 -translate-y-1/2">
                      <div className="w-3.5 h-3.5 border-2 border-blue-500 border-t-transparent rounded-full animate-spin" />
                    </div>
                  )}
                </div>

                {/* Search Results List */}
                <div className="divide-y divide-slate-100 max-h-96 overflow-y-auto pr-1">
                  {searchResults.length === 0 ? (
                    <div className="text-center py-6 text-xs text-slate-400">
                      {searchQuery ? 'No members found matching your search.' : 'Type to search members...'}
                    </div>
                  ) : (
                    searchResults.map((m) => {
                      const isCurrent = selectedMember?.id === m.id;
                      const mAvatar = getMemberAvatarUrl(m);
                      const mInitial = (m.name || 'M').charAt(0).toUpperCase();

                      return (
                        <div
                          key={m.id}
                          onClick={() => handleSelectMember(m)}
                          className={`p-2.5 rounded-lg cursor-pointer transition-colors flex items-center justify-between ${
                            isCurrent
                              ? 'bg-blue-50 border border-blue-200'
                              : 'hover:bg-slate-50'
                          }`}
                        >
                          <div className="flex items-center space-x-3 min-w-0">
                            {mAvatar ? (
                              <img
                                src={mAvatar}
                                alt={m.name}
                                className="w-9 h-9 rounded-full object-cover shrink-0 ring-1 ring-slate-200"
                              />
                            ) : (
                              <div className="w-9 h-9 rounded-full bg-blue-600 text-white font-bold text-xs flex items-center justify-center shrink-0">
                                {mInitial}
                              </div>
                            )}
                            <div className="min-w-0">
                              <div className="text-xs font-bold text-slate-900 truncate">
                                {m.name}
                              </div>
                              <div className="flex items-center space-x-1.5 text-[11px] text-slate-500 truncate">
                                <code className="text-blue-600 font-mono">@{m.user_id}</code>
                                <span>•</span>
                                <span className="truncate">{m.email}</span>
                              </div>
                            </div>
                          </div>

                          <div className="shrink-0 ml-2">
                            {isCurrent ? (
                              <CheckCircle2 className="w-4 h-4 text-blue-600" />
                            ) : (
                              <ArrowRight className="w-3.5 h-3.5 text-slate-400" />
                            )}
                          </div>
                        </div>
                      );
                    })
                  )}
                </div>
              </div>
            </div>
          </div>

          {/* Right Column: Selected Member Identity & Password Form (7 Cols on LG) */}
          <div className="lg:col-span-7 space-y-6">
            {!selectedMember ? (
              <div className="bg-white rounded-xl border border-slate-200 p-12 text-center shadow-2xs">
                <div className="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                  <Shield className="w-6 h-6" />
                </div>
                <h3 className="text-base font-bold text-slate-800 mb-1">No Member Selected</h3>
                <p className="text-xs text-slate-500 max-w-sm mx-auto">
                  Please search and select a member from the directory on the left to change their account password.
                </p>
              </div>
            ) : (
              <>
                {/* 1. Selected Member Identity Information Card */}
                <div className="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                  <div className="p-4 bg-slate-50/70 border-b border-slate-100 flex items-center justify-between">
                    <span className="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center">
                      <User className="w-3.5 h-3.5 mr-1.5 text-blue-600" /> Target Member Identity
                    </span>
                    <Link
                      to={`/admin/members/${selectedMember.id}`}
                      className="text-xs font-semibold text-blue-600 hover:text-blue-800 transition-colors"
                    >
                      View Profile →
                    </Link>
                  </div>

                  <div className="p-5">
                    <div className="flex items-center space-x-4">
                      {avatarUrl ? (
                        <img
                          src={avatarUrl}
                          alt={selectedMember.name}
                          className="w-16 h-16 rounded-xl object-cover ring-2 ring-blue-100 shadow-xs shrink-0"
                        />
                      ) : (
                        <div className="w-16 h-16 rounded-xl bg-blue-600 text-white font-extrabold text-2xl flex items-center justify-center shrink-0 shadow-xs">
                          {(selectedMember.name || 'M').charAt(0).toUpperCase()}
                        </div>
                      )}

                      <div className="min-w-0 flex-1">
                        <div className="flex items-center space-x-2 flex-wrap gap-y-1 mb-1">
                          <h2 className="text-lg font-bold text-slate-900 truncate">
                            {selectedMember.name}
                          </h2>
                          <code className="text-xs font-mono font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-100">
                            @{selectedMember.user_id}
                          </code>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-xs text-slate-600 mt-2">
                          <div>
                            <span className="text-slate-400">Email:</span>{' '}
                            <span className="font-medium text-slate-800">{selectedMember.email}</span>
                          </div>
                          <div>
                            <span className="text-slate-400">Member ID:</span>{' '}
                            <span className="font-mono font-bold text-slate-800">#{selectedMember.id}</span>
                          </div>
                          {selectedMember.phone && (
                            <div>
                              <span className="text-slate-400">Phone:</span>{' '}
                              <span className="font-medium text-slate-800">{selectedMember.phone}</span>
                            </div>
                          )}
                          <div className="flex items-center space-x-1.5">
                            <span className="text-slate-400">Status:</span>
                            <StatusBadge
                              status={isBlocked ? 'blocked' : isVerified ? 'verified' : 'unverified'}
                              label={isBlocked ? 'BLOCKED' : isVerified ? 'VERIFIED' : 'UNVERIFIED'}
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                {/* Success Banner */}
                {successBanner && (
                  <div className="p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-start space-x-3 text-emerald-800 text-xs">
                    <CheckCircle2 className="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" />
                    <div>
                      <strong className="block font-bold text-emerald-900 mb-0.5">Success!</strong>
                      {successBanner}
                    </div>
                  </div>
                )}

                {/* Error Banner */}
                {fieldErrors.general && (
                  <div className="p-4 rounded-xl bg-red-50 border border-red-200 flex items-start space-x-3 text-red-800 text-xs">
                    <AlertCircle className="w-5 h-5 text-red-600 shrink-0 mt-0.5" />
                    <div>
                      <strong className="block font-bold text-red-900 mb-0.5">Error</strong>
                      {fieldErrors.general}
                    </div>
                  </div>
                )}

                {/* 2. Password Change Form */}
                <form onSubmit={handleSubmit}>
                  <FormSection
                    title="Change Member Password"
                    description="Set a new login password for this member account. Passwords are encrypted using Laravel's secure hashing mechanism."
                    icon={Lock}
                  >
                    {/* Notice Box */}
                    <div className="p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-600 space-y-1">
                      <div className="flex items-center space-x-1.5 font-semibold text-slate-700">
                        <Shield className="w-3.5 h-3.5 text-blue-600" />
                        <span>Security & Privacy Protocol</span>
                      </div>
                      <p className="text-[11.5px] text-slate-500">
                        Existing passwords and password hashes are never shown or exported. Changing this password will take effect immediately on next login.
                      </p>
                    </div>

                    {/* New Password */}
                    <FormField
                      label="New Password"
                      id="newPassword"
                      required
                      error={fieldErrors.password}
                      helpText="Must be at least 8 characters long."
                    >
                      <div className="relative w-full">
                        <InputText
                          id="newPassword"
                          type={showPassword ? 'text' : 'password'}
                          value={newPassword}
                          onChange={(e) => {
                            setNewPassword(e.target.value);
                            if (fieldErrors.password) {
                              setFieldErrors((prev) => ({ ...prev, password: null }));
                            }
                          }}
                          placeholder="Enter new member password"
                          className="w-full pr-10 text-xs"
                          disabled={submitting}
                          autoComplete="new-password"
                        />
                        <button
                          type="button"
                          onClick={() => setShowPassword((p) => !p)}
                          className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-hidden"
                          tabIndex={-1}
                        >
                          {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                        </button>
                      </div>
                    </FormField>

                    {/* Confirm Password */}
                    <FormField
                      label="Confirm Password"
                      id="confirmPassword"
                      required
                      error={fieldErrors.password_confirmation}
                      helpText="Must match the new password entered above."
                    >
                      <div className="relative w-full">
                        <InputText
                          id="confirmPassword"
                          type={showConfirmPassword ? 'text' : 'password'}
                          value={confirmPassword}
                          onChange={(e) => {
                            setConfirmPassword(e.target.value);
                            if (fieldErrors.password_confirmation) {
                              setFieldErrors((prev) => ({ ...prev, password_confirmation: null }));
                            }
                          }}
                          placeholder="Re-enter new password to confirm"
                          className="w-full pr-10 text-xs"
                          disabled={submitting}
                          autoComplete="new-password"
                        />
                        <button
                          type="button"
                          onClick={() => setShowConfirmPassword((p) => !p)}
                          className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-hidden"
                          tabIndex={-1}
                        >
                          {showConfirmPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                        </button>
                      </div>
                    </FormField>

                    {/* Submit Actions */}
                    <div className="pt-2 flex items-center justify-end space-x-3">
                      <Button
                        type="button"
                        label="Reset Form"
                        icon="pi pi-refresh"
                        onClick={() => {
                          setNewPassword('');
                          setConfirmPassword('');
                          setFieldErrors({});
                          setSuccessBanner(null);
                        }}
                        className="p-button-outlined p-button-secondary text-xs"
                        disabled={submitting || (!newPassword && !confirmPassword)}
                      />
                      <Button
                        type="submit"
                        label={submitting ? 'Updating...' : 'Update Password'}
                        icon="pi pi-check"
                        loading={submitting}
                        className="p-button-primary text-xs"
                        disabled={submitting || !newPassword || !confirmPassword}
                      />
                    </div>
                  </FormSection>
                </form>
              </>
            )}
          </div>
        </div>
      )}
    </div>
  );
}

export default MemberSecurityPage;
