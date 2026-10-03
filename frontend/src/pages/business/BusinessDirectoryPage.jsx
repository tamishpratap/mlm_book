import { useState, useEffect, useCallback } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import {
  Users,
  Search,
  MessageSquare,
  MapPin,
  Mail,
  RotateCw,
  User,
  Sparkles,
  List,
  LayoutGrid,
  Calendar,
} from 'lucide-react';
import businessApi from '../../api/businessApi';
import VerifiedBadge from '../../components/common/VerifiedBadge';
import { getAvatarUrl, getInitials } from '../../utils/assetHelper';

export function BusinessDirectoryPage() {
  const [searchParams, setSearchParams] = useSearchParams();

  const qQuery = searchParams.get('q') || '';
  const countryQuery = searchParams.get('country') || '';
  const sortQuery = searchParams.get('sort') || 'newest';
  const currentPage = parseInt(searchParams.get('page') || '1', 10);

  const [qInput, setQInput] = useState(qQuery);
  const [countryInput, setCountryInput] = useState(countryQuery);
  const [sort, setSort] = useState(sortQuery);

  // View Mode: 'strip' (default) | 'card'
  const [viewMode, setViewMode] = useState(() => {
    try {
      return localStorage.getItem('business_directory_view_mode') || 'strip';
    } catch {
      return 'strip';
    }
  });

  const handleViewModeChange = (mode) => {
    setViewMode(mode);
    try {
      localStorage.setItem('business_directory_view_mode', mode);
    } catch {
      // ignore
    }
  };

  const [data, setData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState(null);

  const fetchDirectory = useCallback(() => {
    const params = {
      q: qQuery || undefined,
      country: countryQuery || undefined,
      sort: sortQuery || undefined,
      page: currentPage,
    };
    return businessApi.getDirectory(params);
  }, [qQuery, countryQuery, sortQuery, currentPage]);

  useEffect(() => {
    let isMounted = true;

    fetchDirectory()
      .then((res) => {
        if (isMounted && res) {
          setData(res);
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load business directory.');
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [fetchDirectory]);

  const handleRefresh = () => {
    setIsRefreshing(true);
    fetchDirectory()
      .then((res) => {
        if (res) setData(res);
      })
      .catch(() => {})
      .finally(() => setIsRefreshing(false));
  };

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    const newParams = new URLSearchParams();
    if (qInput.trim()) newParams.set('q', qInput.trim());
    if (countryInput) newParams.set('country', countryInput);
    if (sort) newParams.set('sort', sort);
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handleCountryChange = (country) => {
    setCountryInput(country);
    const newParams = new URLSearchParams(searchParams);
    if (country && country !== 'All') {
      newParams.set('country', country);
    } else {
      newParams.delete('country');
    }
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handleSortChange = (newSort) => {
    setSort(newSort);
    const newParams = new URLSearchParams(searchParams);
    if (newSort) newParams.set('sort', newSort);
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handlePageChange = (newPage) => {
    const newParams = new URLSearchParams(searchParams);
    newParams.set('page', newPage.toString());
    setSearchParams(newParams);
  };

  const members = data?.members?.data || [];
  const lastPage = data?.members?.last_page || 1;
  const availableCountries = data?.available_countries || [];
  const featuredMembers = data?.featured_members || [];

  return (
    <div className="biz-page biz-directory-page" style={{ maxWidth: '1200px', margin: '0 auto', padding: '24px 16px' }}>
      {/* Hero Directory Search Header */}
      <div
        className="biz-hero biz-directory-hero"
        style={{
          background: 'linear-gradient(135deg, #1e40af 0%, #3b82f6 100%)',
          borderRadius: '20px',
          padding: '36px 28px',
          color: '#ffffff',
          marginBottom: '28px',
          boxShadow: '0 10px 25px -5px rgba(37, 99, 235, 0.25)',
        }}
      >
        <div className="biz-directory-hero__inner" style={{ maxWidth: '800px' }}>
          <span
            style={{
              display: 'inline-flex',
              alignItems: 'center',
              gap: '6px',
              padding: '6px 14px',
              borderRadius: '20px',
              background: 'rgba(255, 255, 255, 0.18)',
              backdropFilter: 'blur(8px)',
              fontSize: '12.5px',
              fontWeight: 700,
              marginBottom: '14px',
            }}
          >
            <MessageSquare size={15} />
            <span>Direct Message & Member Directory</span>
          </span>

          <h1 style={{ fontSize: '28px', fontWeight: 800, margin: '0 0 10px 0', letterSpacing: '-0.02em', color: '#ffffff' }}>
            Discover Members & Send Direct Messages
          </h1>
          <p style={{ fontSize: '15px', color: 'rgba(255, 255, 255, 0.88)', margin: '0 0 24px 0', lineHeight: 1.5 }}>
            Browse verified member profiles, search by country or ID, and connect directly with anyone across the global network.
          </p>

          {/* Search Panel */}
          <form
            onSubmit={handleSearchSubmit}
            style={{
              display: 'flex',
              flexWrap: 'wrap',
              gap: '12px',
              background: '#ffffff',
              padding: '12px',
              borderRadius: '16px',
              boxShadow: '0 4px 20px rgba(0, 0, 0, 0.08)',
            }}
          >
            <div style={{ flex: '1 1 240px', position: 'relative' }}>
              <input
                type="text"
                value={qInput}
                onChange={(e) => setQInput(e.target.value)}
                placeholder="Search name, user ID, email, country..."
                style={{
                  width: '100%',
                  padding: '12px 14px',
                  borderRadius: '10px',
                  border: '1px solid #e2e8f0',
                  fontSize: '14px',
                  color: '#0f172a',
                  outline: 'none',
                }}
              />
            </div>

            <div style={{ flex: '0 1 180px' }}>
              <select
                value={countryInput}
                onChange={(e) => handleCountryChange(e.target.value)}
                style={{
                  width: '100%',
                  padding: '12px 14px',
                  borderRadius: '10px',
                  border: '1px solid #e2e8f0',
                  fontSize: '14px',
                  color: '#0f172a',
                  backgroundColor: '#f8fafc',
                  outline: 'none',
                }}
              >
                <option value="">All Countries</option>
                {availableCountries.map((c) => (
                  <option key={c} value={c}>
                    {c}
                  </option>
                ))}
              </select>
            </div>

            <div style={{ flex: '0 1 160px' }}>
              <select
                value={sort}
                onChange={(e) => handleSortChange(e.target.value)}
                style={{
                  width: '100%',
                  padding: '12px 14px',
                  borderRadius: '10px',
                  border: '1px solid #e2e8f0',
                  fontSize: '14px',
                  color: '#0f172a',
                  backgroundColor: '#f8fafc',
                  outline: 'none',
                }}
              >
                <option value="newest">Newest First</option>
                <option value="name">Alphabetical</option>
                <option value="oldest">Oldest First</option>
              </select>
            </div>

            <button
              type="submit"
              style={{
                display: 'inline-flex',
                alignItems: 'center',
                gap: '8px',
                padding: '12px 20px',
                borderRadius: '10px',
                border: 'none',
                backgroundColor: '#2563eb',
                color: '#ffffff',
                fontWeight: 700,
                fontSize: '14px',
                cursor: 'pointer',
              }}
            >
              <Search size={16} />
              <span>Search</span>
            </button>
          </form>
        </div>
      </div>

      {error && (
        <div style={{ padding: '16px', backgroundColor: '#fef2f2', color: '#dc2626', borderRadius: '12px', marginBottom: '20px', border: '1px solid #fecaca' }}>
          {error}
        </div>
      )}

      {isLoading ? (
        <div style={{ textAlign: 'center', padding: '60px', color: '#64748b' }}>
          Loading directory profiles...
        </div>
      ) : (
        <>
          {/* Header & Controls */}
          <div
            style={{
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              flexWrap: 'wrap',
              gap: '12px',
              marginBottom: '20px',
            }}
          >
            <h2 style={{ fontSize: '20px', fontWeight: 800, color: '#0f172a', margin: 0, display: 'flex', alignItems: 'center', gap: '8px' }}>
              <Users size={20} color="#2563eb" />
              <span>Member Profiles ({data?.members?.total || members.length})</span>
            </h2>

            <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
              {/* View Toggle (Strip vs Card) */}
              <div
                style={{
                  display: 'inline-flex',
                  alignItems: 'center',
                  backgroundColor: '#f1f5f9',
                  padding: '3px',
                  borderRadius: '10px',
                  border: '1px solid #e2e8f0',
                }}
              >
                <button
                  type="button"
                  onClick={() => handleViewModeChange('strip')}
                  style={{
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: '6px',
                    padding: '6px 14px',
                    borderRadius: '7px',
                    border: 'none',
                    backgroundColor: viewMode === 'strip' ? '#ffffff' : 'transparent',
                    color: viewMode === 'strip' ? '#2563eb' : '#64748b',
                    boxShadow: viewMode === 'strip' ? '0 1px 3px rgba(0, 0, 0, 0.08)' : 'none',
                    fontWeight: viewMode === 'strip' ? 700 : 500,
                    fontSize: '13px',
                    cursor: 'pointer',
                    transition: 'all 0.15s ease',
                  }}
                  title="Strip View"
                >
                  <List size={15} />
                  <span>Strip View</span>
                </button>

                <button
                  type="button"
                  onClick={() => handleViewModeChange('card')}
                  style={{
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: '6px',
                    padding: '6px 14px',
                    borderRadius: '7px',
                    border: 'none',
                    backgroundColor: viewMode === 'card' ? '#ffffff' : 'transparent',
                    color: viewMode === 'card' ? '#2563eb' : '#64748b',
                    boxShadow: viewMode === 'card' ? '0 1px 3px rgba(0, 0, 0, 0.08)' : 'none',
                    fontWeight: viewMode === 'card' ? 700 : 500,
                    fontSize: '13px',
                    cursor: 'pointer',
                    transition: 'all 0.15s ease',
                  }}
                  title="Card View"
                >
                  <LayoutGrid size={15} />
                  <span>Card View</span>
                </button>
              </div>

              {/* Refresh Button */}
              <button
                type="button"
                onClick={handleRefresh}
                disabled={isRefreshing}
                style={{
                  display: 'inline-flex',
                  alignItems: 'center',
                  gap: '6px',
                  padding: '7px 14px',
                  borderRadius: '10px',
                  border: '1px solid #e2e8f0',
                  backgroundColor: '#ffffff',
                  color: '#475569',
                  fontSize: '13px',
                  fontWeight: 600,
                  cursor: isRefreshing ? 'not-allowed' : 'pointer',
                  boxShadow: '0 1px 2px rgba(0, 0, 0, 0.03)',
                  transition: 'all 0.15s ease',
                }}
              >
                <RotateCw size={14} className={isRefreshing ? 'spin-icon' : ''} />
                <span>Refresh</span>
              </button>
            </div>
          </div>

          {/* Scoped CSS for hover & responsive layouts */}
          <style>{`
            .directory-strip-card {
              transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            }
            .directory-strip-card:hover {
              border-color: #cbd5e1 !important;
              box-shadow: 0 4px 14px rgba(15, 23, 42, 0.07) !important;
              transform: translateY(-1px);
            }
            .directory-card-item {
              transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            }
            .directory-card-item:hover {
              border-color: #cbd5e1 !important;
              box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08) !important;
              transform: translateY(-2px);
            }
            @media (max-width: 680px) {
              .directory-strip-card {
                flex-direction: column !important;
                align-items: stretch !important;
                padding: 14px !important;
              }
              .directory-strip-card .strip-actions-wrap {
                width: 100% !important;
                margin-top: 4px !important;
              }
              .directory-strip-card .strip-actions-wrap > * {
                flex: 1 !important;
                text-align: center !important;
              }
            }
          `}</style>

          {/* Members Listing */}
          {members.length === 0 ? (
            <div style={{ padding: '48px 20px', textAlign: 'center', borderRadius: '16px', background: '#ffffff', border: '1px solid #e2e8f0' }}>
              <User size={44} color="#94a3b8" style={{ margin: '0 auto 12px' }} />
              <h3 style={{ fontSize: '17px', fontWeight: 700, color: '#0f172a', margin: '0 0 6px 0' }}>No Members Found</h3>
              <p style={{ color: '#64748b', margin: 0, fontSize: '14px' }}>
                Try adjusting your search query or selecting a different country filter.
              </p>
            </div>
          ) : viewMode === 'strip' ? (
            /* ============================================================== */
            /* STRIP VIEW (Default)                                           */
            /* ============================================================== */
            <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
              {members.map((m) => {
                const avatar = m.profile_photo ? getAvatarUrl(m.profile_photo) : null;
                const initials = getInitials(m.name);

                return (
                  <div
                    key={m.id}
                    className="directory-strip-card"
                    style={{
                      backgroundColor: '#ffffff',
                      borderRadius: '14px',
                      border: '1px solid #e2e8f0',
                      padding: '16px 20px',
                      boxShadow: '0 1px 3px rgba(15, 23, 42, 0.04)',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'space-between',
                      gap: '16px',
                    }}
                  >
                    {/* Left & Center: Avatar + Info */}
                    <div style={{ display: 'flex', alignItems: 'center', gap: '16px', minWidth: 0, flex: 1 }}>
                      {/* Avatar */}
                      <Link to={`/member/people/${m.id || m.user_id}`} state={{ member: m }} style={{ textDecoration: 'none', flexShrink: 0 }}>
                        <div
                          style={{
                            width: '54px',
                            height: '54px',
                            borderRadius: '50%',
                            backgroundColor: '#2563eb',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            color: '#ffffff',
                            fontWeight: 'bold',
                            fontSize: '18px',
                            overflow: 'hidden',
                            border: '2px solid #e2e8f0',
                            boxShadow: '0 2px 6px rgba(37, 99, 235, 0.15)',
                          }}
                        >
                          {avatar ? (
                            <img src={avatar} alt={m.name} style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                          ) : (
                            initials
                          )}
                        </div>
                      </Link>

                      {/* Info details */}
                      <div style={{ minWidth: 0, flex: 1 }}>
                        {/* Name + Verified + Username */}
                        <div style={{ display: 'flex', alignItems: 'center', flexWrap: 'wrap', gap: '8px', marginBottom: '4px' }}>
                          <Link
                            to={`/member/people/${m.id || m.user_id}`}
                            state={{ member: m }}
                            style={{
                              textDecoration: 'none',
                              color: '#0f172a',
                              fontWeight: 700,
                              fontSize: '16px',
                              letterSpacing: '-0.01em',
                            }}
                          >
                            {m.name}
                          </Link>
                          {m.is_verified && <VerifiedBadge member={m} size={15} />}
                          <span
                            style={{
                              fontSize: '12px',
                              color: '#2563eb',
                              fontWeight: 600,
                              backgroundColor: '#eff6ff',
                              border: '1px solid #dbeafe',
                              padding: '1px 8px',
                              borderRadius: '6px',
                            }}
                          >
                            @{m.user_id}
                          </span>
                        </div>

                        {/* Location, Email, Joined */}
                        <div
                          style={{
                            display: 'flex',
                            alignItems: 'center',
                            flexWrap: 'wrap',
                            gap: '14px',
                            fontSize: '13px',
                            color: '#475569',
                          }}
                        >
                          {/* Location */}
                          <div style={{ display: 'inline-flex', alignItems: 'center', gap: '4px', fontWeight: 500, color: '#334155' }}>
                            <MapPin size={14} color="#64748b" />
                            <span>{m.country || 'Global'}{m.city ? `, ${m.city}` : ''}</span>
                          </div>

                          {/* Email */}
                          {m.email && (
                            <div
                              style={{
                                display: 'inline-flex',
                                alignItems: 'center',
                                gap: '4px',
                                color: '#64748b',
                                maxWidth: '240px',
                                overflow: 'hidden',
                                textOverflow: 'ellipsis',
                                whiteSpace: 'nowrap',
                              }}
                              title={m.email}
                            >
                              <Mail size={14} color="#64748b" />
                              <span>{m.email}</span>
                            </div>
                          )}

                          {/* Joined */}
                          {m.created_at && (
                            <div style={{ display: 'inline-flex', alignItems: 'center', gap: '4px', color: '#94a3b8', fontSize: '12px' }}>
                              <Calendar size={13} color="#94a3b8" />
                              <span>Joined {m.created_at}</span>
                            </div>
                          )}
                        </div>

                        {/* Bio preview if available */}
                        {m.bio && (
                          <p
                            style={{
                              margin: '5px 0 0 0',
                              fontSize: '12.5px',
                              color: '#64748b',
                              lineHeight: 1.4,
                              overflow: 'hidden',
                              textOverflow: 'ellipsis',
                              whiteSpace: 'nowrap',
                              maxWidth: '650px',
                            }}
                          >
                            {m.bio}
                          </p>
                        )}
                      </div>
                    </div>

                    {/* Right: Action Buttons */}
                    <div
                      className="strip-actions-wrap"
                      style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: '8px',
                        flexShrink: 0,
                      }}
                    >
                      <Link
                        to={`/member/messages/${m.id}`}
                        style={{
                          display: 'inline-flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                          gap: '6px',
                          padding: '9px 18px',
                          borderRadius: '10px',
                          backgroundColor: '#2563eb',
                          color: '#ffffff',
                          fontWeight: 600,
                          fontSize: '13px',
                          textDecoration: 'none',
                          boxShadow: '0 2px 4px rgba(37, 99, 235, 0.2)',
                          transition: 'all 0.15s ease',
                          whiteSpace: 'nowrap',
                          position: 'relative',
                        }}
                      >
                        <MessageSquare size={15} />
                        <span>Send Message</span>
                        {m.unread_messages_count > 0 && (
                          <span className="notification-badge" style={{ position: 'absolute', top: '-6px', right: '-6px' }}>
                            {m.unread_messages_count > 99 ? '99+' : m.unread_messages_count}
                          </span>
                        )}
                      </Link>

                      <Link
                        to={`/member/people/${m.id || m.user_id}`}
                        state={{ member: m }}
                        style={{
                          display: 'inline-flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                          gap: '6px',
                          padding: '9px 14px',
                          borderRadius: '10px',
                          backgroundColor: '#f8fafc',
                          color: '#334155',
                          fontWeight: 600,
                          fontSize: '13px',
                          textDecoration: 'none',
                          border: '1px solid #e2e8f0',
                          transition: 'all 0.15s ease',
                          whiteSpace: 'nowrap',
                        }}
                        title="View Full Profile"
                      >
                        <User size={15} />
                        <span>Profile</span>
                      </Link>
                    </div>
                  </div>
                );
              })}
            </div>
          ) : (
            /* ============================================================== */
            /* CARD VIEW (Grid)                                               */
            /* ============================================================== */
            <div
              style={{
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fill, minmax(285px, 1fr))',
                gap: '20px',
              }}
            >
              {members.map((m) => {
                const avatar = m.profile_photo ? getAvatarUrl(m.profile_photo) : null;
                const initials = getInitials(m.name);

                return (
                  <div
                    key={m.id}
                    className="directory-card-item"
                    style={{
                      backgroundColor: '#ffffff',
                      borderRadius: '16px',
                      border: '1px solid #e2e8f0',
                      padding: '20px',
                      boxShadow: '0 2px 8px rgba(15, 23, 42, 0.04)',
                      display: 'flex',
                      flexDirection: 'column',
                      justifyContent: 'space-between',
                    }}
                  >
                    <div>
                      {/* Top Header: Avatar & Info */}
                      <div style={{ display: 'flex', alignItems: 'center', gap: '14px', marginBottom: '16px' }}>
                        <Link to={`/member/people/${m.id || m.user_id}`} state={{ member: m }} style={{ textDecoration: 'none', flexShrink: 0 }}>
                          <div
                            style={{
                              width: '56px',
                              height: '56px',
                              borderRadius: '50%',
                              backgroundColor: '#2563eb',
                              display: 'flex',
                              alignItems: 'center',
                              justifyContent: 'center',
                              color: '#ffffff',
                              fontWeight: 'bold',
                              fontSize: '18px',
                              overflow: 'hidden',
                              border: '2px solid #e2e8f0',
                              boxShadow: '0 2px 6px rgba(37, 99, 235, 0.12)',
                            }}
                          >
                            {avatar ? (
                              <img src={avatar} alt={m.name} style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                            ) : (
                              initials
                            )}
                          </div>
                        </Link>

                        <div style={{ flex: 1, minWidth: 0 }}>
                          <div style={{ display: 'flex', alignItems: 'center', gap: '5px' }}>
                            <Link
                              to={`/member/people/${m.id || m.user_id}`}
                              state={{ member: m }}
                              style={{
                                textDecoration: 'none',
                                color: '#0f172a',
                                fontWeight: 700,
                                fontSize: '15px',
                                overflow: 'hidden',
                                textOverflow: 'ellipsis',
                                whiteSpace: 'nowrap',
                              }}
                            >
                              {m.name}
                            </Link>
                            {m.is_verified && <VerifiedBadge member={m} size={15} />}
                          </div>
                          <span style={{ display: 'block', fontSize: '12.5px', color: '#2563eb', fontWeight: 600 }}>
                            @{m.user_id}
                          </span>
                        </div>
                      </div>

                      {/* Details Strip */}
                      <div
                        style={{
                          backgroundColor: '#f8fafc',
                          borderRadius: '10px',
                          padding: '9px 12px',
                          marginBottom: '14px',
                          display: 'flex',
                          alignItems: 'center',
                          gap: '12px',
                          fontSize: '12px',
                        }}
                      >
                        {/* Country */}
                        <div style={{ display: 'flex', alignItems: 'center', gap: '4px', color: '#334155', fontWeight: 600 }}>
                          <MapPin size={13} color="#64748b" />
                          <span style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', maxWidth: '110px' }}>
                            {m.country || 'Global'}
                          </span>
                        </div>

                        {/* Email */}
                        {m.email && (
                          <div
                            style={{
                              display: 'flex',
                              alignItems: 'center',
                              gap: '4px',
                              color: '#64748b',
                              minWidth: 0,
                              borderLeft: '1px solid #cbd5e1',
                              paddingLeft: '10px',
                            }}
                          >
                            <Mail size={13} color="#64748b" />
                            <span style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }} title={m.email}>
                              {m.email}
                            </span>
                          </div>
                        )}
                      </div>

                      {/* Bio snippet if present */}
                      {m.bio && (
                        <p
                          style={{
                            margin: '0 0 16px 0',
                            fontSize: '12.5px',
                            color: '#64748b',
                            lineHeight: 1.4,
                            display: '-webkit-box',
                            WebkitLineClamp: 2,
                            WebkitBoxOrient: 'vertical',
                            overflow: 'hidden',
                          }}
                        >
                          {m.bio}
                        </p>
                      )}
                    </div>

                    {/* Action Button: Send Direct Message + Profile */}
                    <div style={{ display: 'flex', gap: '8px', marginTop: m.bio ? '0' : '8px' }}>
                      <Link
                        to={`/member/messages/${m.id}`}
                        style={{
                          flex: 1,
                          display: 'inline-flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                          gap: '6px',
                          padding: '10px 14px',
                          borderRadius: '10px',
                          backgroundColor: '#2563eb',
                          color: '#ffffff',
                          fontWeight: 700,
                          fontSize: '13px',
                          textDecoration: 'none',
                          boxShadow: '0 2px 4px rgba(37, 99, 235, 0.2)',
                          transition: 'background-color 0.15s ease',
                          position: 'relative',
                        }}
                      >
                        <MessageSquare size={15} />
                        <span>Send Message</span>
                        {m.unread_messages_count > 0 && (
                          <span className="notification-badge" style={{ position: 'absolute', top: '-6px', right: '-6px' }}>
                            {m.unread_messages_count > 99 ? '99+' : m.unread_messages_count}
                          </span>
                        )}
                      </Link>

                      <Link
                        to={`/member/people/${m.id || m.user_id}`}
                        state={{ member: m }}
                        style={{
                          display: 'inline-flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                          padding: '10px 14px',
                          borderRadius: '10px',
                          backgroundColor: '#f1f5f9',
                          color: '#475569',
                          fontWeight: 600,
                          fontSize: '13px',
                          textDecoration: 'none',
                          border: '1px solid #e2e8f0',
                          transition: 'all 0.15s ease',
                        }}
                        title="View Profile"
                      >
                        <User size={15} />
                      </Link>
                    </div>
                  </div>
                );
              })}
            </div>
          )}

          {/* Pagination */}
          {lastPage > 1 && (
            <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', gap: '12px', marginTop: '32px' }}>
              <button
                type="button"
                disabled={currentPage <= 1}
                onClick={() => handlePageChange(currentPage - 1)}
                style={{
                  padding: '8px 16px',
                  borderRadius: '8px',
                  border: '1px solid #e2e8f0',
                  backgroundColor: currentPage <= 1 ? '#f8fafc' : '#ffffff',
                  color: currentPage <= 1 ? '#94a3b8' : '#0f172a',
                  cursor: currentPage <= 1 ? 'not-allowed' : 'pointer',
                  fontWeight: 600,
                  fontSize: '13px',
                }}
              >
                Previous
              </button>
              <span style={{ fontSize: '13.5px', color: '#64748b', fontWeight: 500 }}>
                Page {currentPage} of {lastPage}
              </span>
              <button
                type="button"
                disabled={currentPage >= lastPage}
                onClick={() => handlePageChange(currentPage + 1)}
                style={{
                  padding: '8px 16px',
                  borderRadius: '8px',
                  border: '1px solid #e2e8f0',
                  backgroundColor: currentPage >= lastPage ? '#f8fafc' : '#ffffff',
                  color: currentPage >= lastPage ? '#94a3b8' : '#0f172a',
                  cursor: currentPage >= lastPage ? 'not-allowed' : 'pointer',
                  fontWeight: 600,
                  fontSize: '13px',
                }}
              >
                Next
              </button>
            </div>
          )}
        </>
      )}
    </div>
  );
}

export default BusinessDirectoryPage;
