import { useState, useEffect, useCallback, useRef } from 'react';
import { useSearchParams, Link } from 'react-router-dom';
import {
  Wallet,
  Search,
  CheckCircle2,
  AlertCircle,
  Copy,
  Check,
  User,
  ArrowRight,
  ExternalLink,
  ShieldAlert,
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

export function MemberWalletAddressPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const { showSuccess, showError } = useToast();

  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState([]);
  const [searching, setSearching] = useState(false);
  const [selectedMember, setSelectedMember] = useState(null);
  const [loadingInitial, setLoadingInitial] = useState(false);

  // Form State
  const [walletAddress, setWalletAddress] = useState('');
  const [fieldErrors, setFieldErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);
  const [copied, setCopied] = useState(false);
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
      showError(err.message || 'Failed to load member details.');
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
    setWalletAddress('');
    setSearchParams({ member_id: member.id });
  };

  const handleClearSelection = () => {
    setSelectedMember(null);
    setSuccessBanner(null);
    setFieldErrors({});
    setWalletAddress('');
    setSearchParams({});
    handleSearch('');
  };

  const handleCopyCurrent = () => {
    if (!selectedMember?.wallet_address) return;
    navigator.clipboard.writeText(selectedMember.wallet_address);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  // Submit Wallet Address Update
  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!selectedMember) return;

    const trimmed = walletAddress.trim();
    const errors = {};

    if (!trimmed) {
      errors.wallet_address = 'Please enter a wallet address.';
    } else if (!/^0x[a-fA-F0-9]{40}$/.test(trimmed)) {
      errors.wallet_address = 'Please enter a valid USDT (BEP-20) BNB Smart Chain wallet address starting with 0x (42 characters).';
    }

    if (Object.keys(errors).length > 0) {
      setFieldErrors(errors);
      return;
    }

    setFieldErrors({});
    setSubmitting(true);
    setSuccessBanner(null);

    try {
      const res = await membersApi.updateWalletAddress(selectedMember.id, {
        wallet_address: trimmed,
      });

      if (res && (res.success || res.message)) {
        const msg = res.message || `Wallet address for ${selectedMember.name} updated successfully.`;
        showSuccess(msg);
        setSuccessBanner(msg);

        // Update local selected member state with new wallet address
        setSelectedMember((prev) => ({
          ...prev,
          wallet_address: trimmed,
        }));

        setWalletAddress('');
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
        const msg = err?.response?.data?.message || err?.message || 'Failed to update member wallet address.';
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
        title="Member Wallet Address"
        subtitle="View and replace the Web3 USDT (BEP-20) payout wallet address for any registered member."
        breadcrumbs={[
          { label: 'Member Management', to: '/admin/members' },
          { label: 'Wallet Address' },
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

          {/* Right Column: Selected Member Identity & Wallet Form (7 Cols on LG) */}
          <div className="lg:col-span-7 space-y-6">
            {!selectedMember ? (
              <div className="bg-white rounded-xl border border-slate-200 p-12 text-center shadow-2xs">
                <div className="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                  <Wallet className="w-6 h-6" />
                </div>
                <h3 className="text-base font-bold text-slate-800 mb-1">No Member Selected</h3>
                <p className="text-xs text-slate-500 max-w-sm mx-auto">
                  Please search and select a member from the directory on the left to view or update their wallet address.
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

                {/* 2. Current Wallet Address Card */}
                <div className="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                  <div className="p-4 bg-slate-50/70 border-b border-slate-100 flex items-center justify-between">
                    <span className="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center">
                      <Wallet className="w-3.5 h-3.5 mr-1.5 text-emerald-600" /> Current Authoritative Wallet Address
                    </span>
                    <span className="text-xs font-semibold px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-100">
                      USDT (BEP-20)
                    </span>
                  </div>

                  <div className="p-5 space-y-3">
                    {selectedMember.wallet_address ? (
                      <div className="flex flex-col sm:flex-row sm:items-center justify-between p-3.5 bg-slate-50 border border-slate-200 rounded-xl gap-3">
                        <div className="min-w-0">
                          <span className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block mb-1">
                            Registered Address (BNB Smart Chain)
                          </span>
                          <code className="text-xs font-mono font-bold text-slate-800 break-all select-all">
                            {selectedMember.wallet_address}
                          </code>
                        </div>
                        <div className="flex items-center space-x-2 shrink-0">
                          <button
                            type="button"
                            onClick={handleCopyCurrent}
                            className="inline-flex items-center space-x-1 text-xs font-medium px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition-colors shadow-2xs"
                            title="Copy address"
                          >
                            {copied ? (
                              <>
                                <Check className="w-3.5 h-3.5 text-emerald-600" />
                                <span className="text-emerald-600 font-semibold">Copied</span>
                              </>
                            ) : (
                              <>
                                <Copy className="w-3.5 h-3.5 text-slate-500" />
                                <span>Copy</span>
                              </>
                            )}
                          </button>
                          <a
                            href={`https://bscscan.com/address/${selectedMember.wallet_address}`}
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex items-center space-x-1 text-xs font-medium px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-blue-600 hover:bg-blue-50 transition-colors shadow-2xs"
                            title="View on BscScan"
                          >
                            <ExternalLink className="w-3.5 h-3.5" />
                            <span>BscScan</span>
                          </a>
                        </div>
                      </div>
                    ) : (
                      <div className="p-4 bg-amber-50/60 border border-amber-200/80 rounded-xl flex items-center space-x-3 text-amber-800 text-xs">
                        <ShieldAlert className="w-5 h-5 text-amber-600 shrink-0" />
                        <div>
                          <strong className="block font-bold text-amber-900">No Wallet Address Configured</strong>
                          This member has not yet set up a Web3 USDT (BEP-20) wallet address. You can set one below.
                        </div>
                      </div>
                    )}
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

                {/* 3. Replace Wallet Address Form */}
                <form onSubmit={handleSubmit}>
                  <FormSection
                    title="Replace Wallet Address"
                    description="Enter the new USDT (BEP-20) address to overwrite and replace the member's current address."
                    icon={Wallet}
                  >
                    <div className="p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-600 space-y-1">
                      <div className="flex items-center space-x-1.5 font-semibold text-slate-700">
                        <CheckCircle2 className="w-3.5 h-3.5 text-blue-600" />
                        <span>Authoritative Wallet Storage Rule</span>
                      </div>
                      <p className="text-[11.5px] text-slate-500">
                        This update replaces <code>members.wallet_address</code> directly. No second wallet field or table is created. Member balances, Fund Wallet, P2P Wallet, and withdrawal records remain completely untouched.
                      </p>
                    </div>

                    {/* New Address Field */}
                    <FormField
                      label="New Wallet Address"
                      id="walletAddress"
                      required
                      error={fieldErrors.wallet_address}
                      helpText="Must be a valid 42-character hexadecimal BSC (BEP-20) wallet address starting with 0x."
                    >
                      <InputText
                        id="walletAddress"
                        type="text"
                        value={walletAddress}
                        onChange={(e) => {
                          setWalletAddress(e.target.value);
                          if (fieldErrors.wallet_address) {
                            setFieldErrors((prev) => ({ ...prev, wallet_address: null }));
                          }
                        }}
                        placeholder="0x71C66336071A72F37854499b794E747192663972"
                        className="w-full text-xs font-mono"
                        disabled={submitting}
                        autoComplete="off"
                        spellCheck="false"
                      />
                    </FormField>

                    {/* Submit Actions */}
                    <div className="pt-2 flex items-center justify-end space-x-3">
                      <Button
                        type="button"
                        label="Reset Form"
                        icon="pi pi-refresh"
                        onClick={() => {
                          setWalletAddress('');
                          setFieldErrors({});
                          setSuccessBanner(null);
                        }}
                        className="p-button-outlined p-button-secondary text-xs"
                        disabled={submitting || !walletAddress}
                      />
                      <Button
                        type="submit"
                        label={submitting ? 'Saving Address...' : 'Save Wallet Address'}
                        icon="pi pi-check"
                        loading={submitting}
                        className="p-button-primary text-xs"
                        disabled={submitting || !walletAddress.trim()}
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

export default MemberWalletAddressPage;
