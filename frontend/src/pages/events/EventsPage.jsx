import { useState, useEffect, useCallback } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import {
  Calendar,
  Plus,
  Search,
  CalendarCheck,
  Compass,
  RotateCw,
  Globe,
  Tag,
  X,
  Wallet,
  CheckCircle2,
  BarChart3,
} from 'lucide-react';
import eventApi from '../../api/eventApi';
import useAuth from '../../hooks/useAuth';
import EventCard from '../../components/events/EventCard';
import AccountVerificationModal from '../../components/verification/AccountVerificationModal';
import { AddFundsToEventCampaignModal } from '../../components/events/AddFundsToEventCampaignModal';
import { AddFundModal } from '../../components/business/ads/AddFundModal';

export function EventsPage({ defaultTab }) {
  const [searchParams, setSearchParams] = useSearchParams();

  const rawTab = searchParams.get('tab') || defaultTab || 'all';
  const currentTab = (rawTab === 'add-fund' || rawTab === 'add_fund' || rawTab === 'funding')
    ? 'add-fund'
    : ((rawTab === 'my' || rawTab === 'my_events' || rawTab === 'my-events') ? 'my' : 'all');

  const searchQuery = searchParams.get('search') || '';
  const timeframeQuery = searchParams.get('timeframe') || '';
  const typeQuery = searchParams.get('type') || '';
  const categoryQuery = searchParams.get('category') || '';
  const currentPage = parseInt(searchParams.get('page') || '1', 10);

  const { user: currentUser } = useAuth();
  const [searchInput, setSearchInput] = useState(searchQuery);
  const [events, setEvents] = useState([]);
  const [myEventsCount, setMyEventsCount] = useState(0);
  const [fundableEventsCount, setFundableEventsCount] = useState(0);
  const [availableAdFunds, setAvailableAdFunds] = useState(parseFloat(currentUser?.p2p_wallet ?? currentUser?.fund_wallet ?? currentUser?.ad_balance ?? 0));
  const [platformFeePercent, setPlatformFeePercent] = useState(2.5);
  const [totalEventsCount, setTotalEventsCount] = useState(0);
  const [categories, setCategories] = useState([]);
  const [lastPage, setLastPage] = useState(1);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState(null);
  const [showVerifyModal, setShowVerifyModal] = useState(false);
  const [showAddFundsModal, setShowAddFundsModal] = useState(false);
  const [selectedEventForFunding, setSelectedEventForFunding] = useState(null);
  const [showDepositModal, setShowDepositModal] = useState(false);
  const [fundingSuccessBanner, setFundingSuccessBanner] = useState(null);

  const fetchEvents = useCallback(() => {
    const params = {
      tab: currentTab,
      search: searchQuery || undefined,
      timeframe: timeframeQuery || undefined,
      type: typeQuery || undefined,
      category: categoryQuery || undefined,
      page: currentPage,
    };

    return eventApi.getEvents(params);
  }, [currentTab, searchQuery, timeframeQuery, typeQuery, categoryQuery, currentPage]);

  useEffect(() => {
    let isMounted = true;

    fetchEvents()
      .then((data) => {
        if (isMounted) {
          if (data) {
            const eventList = data.events?.data || (Array.isArray(data.events) ? data.events : []);
            setEvents(eventList);
            setLastPage(data.events?.last_page || 1);
            setTotalEventsCount(data.events?.total !== undefined ? data.events.total : eventList.length);
            if (data.my_events_count !== undefined) {
              setMyEventsCount(data.my_events_count);
            } else if (Array.isArray(data.my_events)) {
              setMyEventsCount(data.my_events.length);
            }
            if (data.fundable_events_count !== undefined) {
              setFundableEventsCount(data.fundable_events_count);
            }
            if (data.available_ad_funds !== undefined) {
              setAvailableAdFunds(Number(data.available_ad_funds));
            }
            if (data.platform_fee_percent !== undefined) {
              setPlatformFeePercent(Number(data.platform_fee_percent));
            }
            if (Array.isArray(data.categories)) {
              setCategories(data.categories);
            }
          }
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load events.');
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [fetchEvents]);

  const handleRefresh = () => {
    setIsRefreshing(true);
    fetchEvents()
      .then((data) => {
        if (data) {
          const eventList = data.events?.data || (Array.isArray(data.events) ? data.events : []);
          setEvents(eventList);
          setLastPage(data.events?.last_page || 1);
          setTotalEventsCount(data.events?.total !== undefined ? data.events.total : eventList.length);
          if (data.my_events_count !== undefined) {
            setMyEventsCount(data.my_events_count);
          } else if (Array.isArray(data.my_events)) {
            setMyEventsCount(data.my_events.length);
          }
          if (data.fundable_events_count !== undefined) {
            setFundableEventsCount(data.fundable_events_count);
          }
          if (data.available_ad_funds !== undefined) {
            setAvailableAdFunds(Number(data.available_ad_funds));
          }
          if (data.platform_fee_percent !== undefined) {
            setPlatformFeePercent(Number(data.platform_fee_percent));
          }
        }
      })
      .catch(() => {})
      .finally(() => setIsRefreshing(false));
  };

  const handleTabChange = (tabKey) => {
    const newParams = new URLSearchParams(searchParams);
    if (tabKey === 'all') {
      newParams.delete('tab');
    } else {
      newParams.set('tab', tabKey);
    }
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    const newParams = new URLSearchParams(searchParams);
    if (searchInput.trim()) {
      newParams.set('search', searchInput.trim());
    } else {
      newParams.delete('search');
    }
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handleFilterChange = (key, value) => {
    const newParams = new URLSearchParams(searchParams);
    if (value) {
      newParams.set(key, value);
    } else {
      newParams.delete(key);
    }
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handlePageChange = (newPage) => {
    const newParams = new URLSearchParams(searchParams);
    newParams.set('page', newPage.toString());
    setSearchParams(newParams);
  };

  return (
    <div className="events-page">
      {/* Header */}
      <header className="events-header">
        <div className="events-header__info">
          <h1>
            <Calendar size={24} aria-hidden="true" />
            <span>Events Hub</span>
          </h1>
          <p>Discover, create, and attend community events, workshops, and meetups.</p>
        </div>
        <div className="events-header__actions">
          <button
            className="member-button member-button--secondary events-header__btn-refresh"
            type="button"
            aria-label="Refresh Events"
            onClick={handleRefresh}
            disabled={isRefreshing}
            style={{ padding: '9px 13px' }}
          >
            <RotateCw size={15} className={isRefreshing ? 'fa-spin' : ''} aria-hidden="true" />
          </button>
          <button
            type="button"
            className="member-button member-button--primary events-header__btn-create"
            onClick={() => {
              const isVerified = currentUser?.is_verified || currentUser?.mobile_verified_at;
              if (!isVerified) {
                setShowVerifyModal(true);
              } else {
                window.location.href = '/member/events/create';
              }
            }}
          >
            <Plus size={16} aria-hidden="true" />
            <span>Create New Event</span>
          </button>
        </div>
      </header>

      {/* Navigation Tabs */}
      <nav className="events-nav-tabs" aria-label="Event tabs">
        <button
          type="button"
          className={`events-nav-tab ${currentTab === 'all' ? 'is-active' : ''}`}
          onClick={() => handleTabChange('all')}
          aria-current={currentTab === 'all' ? 'page' : undefined}
        >
          <Compass size={16} aria-hidden="true" />
          <span>All Events</span>
        </button>

        <button
          type="button"
          className={`events-nav-tab ${currentTab === 'my' ? 'is-active' : ''}`}
          onClick={() => handleTabChange('my')}
          aria-current={currentTab === 'my' ? 'page' : undefined}
        >
          <CalendarCheck size={16} aria-hidden="true" />
          <span>My Events</span>
          {myEventsCount > 0 && (
            <span className="events-nav-tab__count">{myEventsCount}</span>
          )}
        </button>

        <button
          type="button"
          className={`events-nav-tab ${currentTab === 'add-fund' ? 'is-active' : ''}`}
          onClick={() => handleTabChange('add-fund')}
          aria-current={currentTab === 'add-fund' ? 'page' : undefined}
        >
          <Wallet size={16} aria-hidden="true" />
          <span>Add Fund</span>
          {fundableEventsCount > 0 && (
            <span className="events-nav-tab__count">{fundableEventsCount}</span>
          )}
        </button>
      </nav>

      {/* Toolbar */}
      <div className="events-toolbar">
        <form onSubmit={handleSearchSubmit} className="events-search-form">
          <div className="events-search-box">
            <Search size={18} className="events-search-box__icon" aria-hidden="true" />
            <input
              type="search"
              name="search"
              value={searchInput}
              onChange={(e) => setSearchInput(e.target.value)}
              placeholder="Search events by title, city, category, keywords..."
              aria-label="Search events"
            />
            {searchInput && (
              <button
                type="button"
                className="events-search-box__clear"
                onClick={() => {
                  setSearchInput('');
                  handleFilterChange('search', '');
                }}
                aria-label="Clear search"
              >
                <X size={15} />
              </button>
            )}
          </div>

          {currentTab !== 'add-fund' && (
            <div className="events-filters-row">
              <div className="events-select-wrapper">
                <Calendar size={16} className="events-select-icon" aria-hidden="true" />
                <select
                  value={timeframeQuery}
                  onChange={(e) => handleFilterChange('timeframe', e.target.value)}
                  aria-label="Filter by Date"
                >
                  <option value="">All Dates</option>
                  <option value="today">Today</option>
                  <option value="tomorrow">Tomorrow</option>
                  <option value="this_week">This Week</option>
                  <option value="this_month">This Month</option>
                </select>
              </div>

              <div className="events-select-wrapper">
                <Globe size={16} className="events-select-icon" aria-hidden="true" />
                <select
                  value={typeQuery}
                  onChange={(e) => handleFilterChange('type', e.target.value)}
                  aria-label="Filter by Type"
                >
                  <option value="">All Types</option>
                  <option value="online">Online Events</option>
                  <option value="offline">In-Person Events</option>
                </select>
              </div>

              <div className="events-select-wrapper">
                <Tag size={16} className="events-select-icon" aria-hidden="true" />
                <select
                  value={categoryQuery}
                  onChange={(e) => handleFilterChange('category', e.target.value)}
                  aria-label="Filter by Category"
                >
                  <option value="">All Categories</option>
                  {categories.map((cat) => (
                    <option key={cat} value={cat}>
                      {cat}
                    </option>
                  ))}
                </select>
              </div>
            </div>
          )}
        </form>
      </div>

      {error && (
        <div className="events-error-banner" role="alert">
          {error}
        </div>
      )}

      {isLoading ? (
        <div className="events-loading-state">
          <RotateCw size={28} className="fa-spin" style={{ margin: '0 auto 12px', opacity: 0.7 }} />
          <p>Loading events...</p>
        </div>
      ) : currentTab === 'add-fund' ? (
        /* Event Campaign Add Fund Section */
        <section className="events-section">
          {/* Success Banner */}
          {fundingSuccessBanner && (
            <div
              style={{
                padding: '12px 18px',
                borderRadius: '10px',
                background: '#ecfdf5',
                border: '1px solid #a7f3d0',
                color: '#065f46',
                marginBottom: '20px',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                fontSize: '13.5px',
                fontWeight: 600,
              }}
            >
              <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                <CheckCircle2 size={18} color="#059669" />
                <span>{fundingSuccessBanner}</span>
              </div>
              <button
                type="button"
                onClick={() => setFundingSuccessBanner(null)}
                style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#065f46' }}
              >
                <X size={16} />
              </button>
            </div>
          )}

          {/* Intro Banner & Balance Overview */}
          <div
            style={{
              background: 'linear-gradient(135deg, #4338ca 0%, #312e81 100%)',
              color: '#ffffff',
              borderRadius: '16px',
              padding: '22px 24px',
              marginBottom: '24px',
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center',
              flexWrap: 'wrap',
              gap: '16px',
              boxShadow: '0 8px 24px rgba(67, 56, 202, 0.15)',
            }}
          >
            <div>
              <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '6px' }}>
                <Wallet size={20} />
                <h2 style={{ margin: 0, fontSize: '18px', fontWeight: 800, color: '#ffffff' }}>
                  Event Campaign Funding &amp; Budget Management
                </h2>
              </div>
              <p style={{ margin: 0, fontSize: '13px', opacity: 0.9, maxWidth: '560px' }}>
                Manage advertising budgets for your event campaigns. Top up low budgets or reactivate exhausted events to continue rewarding interested members.
              </p>
            </div>

            <div
              style={{
                background: 'rgba(255, 255, 255, 0.12)',
                backdropFilter: 'blur(8px)',
                border: '1px solid rgba(255, 255, 255, 0.2)',
                borderRadius: '12px',
                padding: '12px 18px',
                display: 'flex',
                alignItems: 'center',
                flexWrap: 'wrap',
                gap: '14px',
              }}
            >
              <div>
                <span style={{ fontSize: '11px', color: '#c7d2fe', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                  Fund Wallet Balance
                </span>
                <strong style={{ fontSize: '20px', fontWeight: 800, color: '#ffffff' }}>
                  ${Number(availableAdFunds).toFixed(2)} USD
                </strong>
              </div>
              <button
                type="button"
                onClick={() => setShowDepositModal(true)}
                className="member-button"
                style={{
                  background: '#ffffff',
                  color: '#4338ca',
                  fontWeight: 700,
                  fontSize: '13px',
                  padding: '8px 14px',
                  borderRadius: '8px',
                  border: 'none',
                  cursor: 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '6px',
                }}
              >
                <Plus size={14} />
                <span>Deposit Funds</span>
              </button>
            </div>
          </div>

          <div className="events-section__header">
            <h2>
              <Wallet size={20} aria-hidden="true" />
              <span>Fundable Event Campaigns ({totalEventsCount})</span>
            </h2>
          </div>

          {events.length === 0 ? (
            <div className="events-empty-state">
              <div className="events-empty-state__icon">
                <Wallet size={38} aria-hidden="true" />
              </div>
              <h2>No Event Campaigns Found</h2>
              <p>You haven't created any event campaigns yet. Create an event to start promoting and funding member rewards!</p>
              <Link to="/member/events/create" className="member-button member-button--primary" style={{ marginTop: '16px' }}>
                <Plus size={15} aria-hidden="true" />
                <span>Create New Event</span>
              </Link>
            </div>
          ) : (
            <div
              style={{
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fill, minmax(340px, 1fr))',
                gap: '20px',
              }}
            >
              {events.map((event) => {
                const camp = event.campaign_details || event.campaign || {};
                const initialBudget = parseFloat(camp.budget || 0);
                const additionalFunding = parseFloat(camp.additional_funding || 0);
                const spent = parseFloat(camp.spent_amount || 0);
                const remaining = parseFloat(camp.remaining_amount || 0);
                const isExhausted = Boolean(
                  camp.is_exhausted || remaining <= 0 || camp.status === 'exhausted' || camp.status === 'stopped'
                );
                const isLowBudget = Boolean(camp.is_low_budget || (!isExhausted && remaining <= 1.00));

                let badgeStyle = {
                  background: '#dcfce7',
                  color: '#15803d',
                  border: '1px solid #bbf7d0',
                  label: 'Active',
                };
                if (!camp.id || camp.status === 'draft') {
                  badgeStyle = {
                    background: '#f1f5f9',
                    color: '#475569',
                    border: '1px solid #cbd5e1',
                    label: camp.id ? 'Draft' : 'Unfunded',
                  };
                } else if (isExhausted) {
                  badgeStyle = {
                    background: '#fee2e2',
                    color: '#b91c1c',
                    border: '1px solid #fecaca',
                    label: 'Exhausted',
                  };
                } else if (isLowBudget) {
                  badgeStyle = {
                    background: '#fef3c7',
                    color: '#b45309',
                    border: '1px solid #fde68a',
                    label: 'Low Budget',
                  };
                } else if (camp.status === 'paused') {
                  badgeStyle = {
                    background: '#f1f5f9',
                    color: '#475569',
                    border: '1px solid #cbd5e1',
                    label: 'Paused',
                  };
                }

                let remainingColor = '#16a34a';
                if (!camp.id || camp.status === 'draft') {
                  remainingColor = '#64748b';
                } else if (remaining <= 0 || isExhausted) {
                  remainingColor = '#dc2626';
                } else if (isLowBudget) {
                  remainingColor = '#d97706';
                }

                return (
                  <div
                    key={`fund-${event.id}`}
                    style={{
                      background: '#ffffff',
                      borderRadius: '16px',
                      border: '1px solid #e2e8f0',
                      padding: '20px',
                      display: 'flex',
                      flexDirection: 'column',
                      justifyContent: 'space-between',
                      boxShadow: '0 4px 16px rgba(15, 23, 42, 0.04)',
                    }}
                  >
                    <div>
                      {/* Top Header with Title & Badge */}
                      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: '12px', marginBottom: '10px' }}>
                        <div style={{ flex: 1, minWidth: 0 }}>
                          <Link
                            to={`/member/events/${event.id}`}
                            style={{
                              fontSize: '16px',
                              fontWeight: 700,
                              color: '#0f172a',
                              textDecoration: 'none',
                              display: 'block',
                              overflow: 'hidden',
                              textOverflow: 'ellipsis',
                              whiteSpace: 'nowrap',
                            }}
                            title={event.title}
                          >
                            {event.title}
                          </Link>
                          <span style={{ fontSize: '12px', color: '#64748b' }}>
                            {event.category} &bull; {event.event_type === 'online' ? 'Online' : (event.location_city || 'In-Person')}
                          </span>
                        </div>
                        <span
                          style={{
                            padding: '4px 10px',
                            borderRadius: '999px',
                            fontSize: '11.5px',
                            fontWeight: 700,
                            background: badgeStyle.background,
                            color: badgeStyle.color,
                            border: badgeStyle.border,
                            flexShrink: 0,
                          }}
                        >
                          {badgeStyle.label}
                        </span>
                      </div>

                      {/* Date & RSVPs */}
                      <div style={{ fontSize: '12px', color: '#64748b', marginBottom: '14px', display: 'flex', gap: '12px', flexWrap: 'wrap' }}>
                        <span>
                          <strong>Date:</strong> {event.start_date || 'TBD'}
                        </span>
                        <span>
                          <strong>RSVPs:</strong> {event.guests_count || 0}
                        </span>
                      </div>

                      {/* Budget Breakdown Grid */}
                      <div
                        style={{
                          background: '#f8fafc',
                          border: '1px solid #f1f5f9',
                          borderRadius: '10px',
                          padding: '12px 14px',
                          display: 'grid',
                          gridTemplateColumns: '1fr 1fr',
                          gap: '10px',
                          marginBottom: '16px',
                          fontSize: '12.5px',
                        }}
                      >
                        <div>
                          <span style={{ color: '#64748b', fontSize: '11px', display: 'block' }}>Initial Budget</span>
                          <strong style={{ color: '#1e293b' }}>${initialBudget.toFixed(2)} USD</strong>
                        </div>
                        <div>
                          <span style={{ color: '#64748b', fontSize: '11px', display: 'block' }}>Additional Funding</span>
                          <strong style={{ color: '#1e293b' }}>${additionalFunding.toFixed(2)} USD</strong>
                        </div>
                        <div>
                          <span style={{ color: '#64748b', fontSize: '11px', display: 'block' }}>Spent</span>
                          <strong style={{ color: '#64748b' }}>${spent.toFixed(2)} USD</strong>
                        </div>
                        <div>
                          <span style={{ color: '#64748b', fontSize: '11px', display: 'block' }}>Remaining Budget</span>
                          <strong style={{ color: remainingColor, fontWeight: 800 }}>${remaining.toFixed(2)} USD</strong>
                        </div>
                      </div>
                    </div>

                    {/* Actions */}
                    <div style={{ display: 'flex', gap: '8px', alignItems: 'center', marginTop: 'auto', paddingTop: '12px', borderTop: '1px solid #f1f5f9', flexWrap: 'wrap' }}>
                      <button
                        type="button"
                        className="member-button member-button--primary"
                        onClick={() => {
                          setSelectedEventForFunding(event);
                          setShowAddFundsModal(true);
                        }}
                        style={{ flex: 1, justifyContent: 'center', gap: '6px', padding: '8px 12px', fontSize: '13px' }}
                      >
                        <Wallet size={14} />
                        <span>Add Fund</span>
                      </button>

                      <Link
                        to={`/member/events/${event.id}`}
                        className="member-button member-button--secondary"
                        style={{ padding: '8px 12px', fontSize: '13px' }}
                      >
                        View
                      </Link>

                      <Link
                        to={`/member/events/${event.id}/outreach`}
                        className="member-button member-button--secondary"
                        style={{ padding: '8px 10px', fontSize: '13px' }}
                        title="Host Outreach &amp; Analytics"
                      >
                        <BarChart3 size={15} />
                      </Link>
                    </div>
                  </div>
                );
              })}
            </div>
          )}

          {/* Pagination */}
          {lastPage > 1 && (
            <div style={{ display: 'flex', justifyContent: 'center', gap: '8px', marginTop: '24px', flexWrap: 'wrap' }}>
              <button
                type="button"
                className="member-button member-button--secondary"
                disabled={currentPage <= 1}
                onClick={() => handlePageChange(currentPage - 1)}
              >
                Previous
              </button>
              <span style={{ display: 'flex', alignItems: 'center', fontSize: '13px', color: '#64748b' }}>
                Page {currentPage} of {lastPage}
              </span>
              <button
                type="button"
                className="member-button member-button--secondary"
                disabled={currentPage >= lastPage}
                onClick={() => handlePageChange(currentPage + 1)}
              >
                Next
              </button>
            </div>
          )}
        </section>
      ) : currentTab === 'my' ? (
        /* My Events & RSVPs Section */
        <section className="events-section">
          <div className="events-section__header">
            <h2>
              <CalendarCheck size={20} aria-hidden="true" />
              <span>My Events &amp; RSVPs ({totalEventsCount})</span>
            </h2>
          </div>

          {events.length === 0 ? (
            <div className="events-empty-state">
              <div className="events-empty-state__icon">
                <CalendarCheck size={38} aria-hidden="true" />
              </div>
              <h2>No Events Yet</h2>
              <p>You haven't created or joined any events yet. Discover upcoming events or create your own!</p>
              <div style={{ display: 'flex', gap: '8px', justifyContent: 'center', marginTop: '16px', flexWrap: 'wrap' }}>
                <button
                  type="button"
                  onClick={() => handleTabChange('all')}
                  className="member-button member-button--secondary"
                >
                  <Compass size={15} aria-hidden="true" />
                  <span>Discover Events</span>
                </button>
                <Link to="/member/events/create" className="member-button member-button--primary">
                  <Plus size={15} aria-hidden="true" />
                  <span>Create New Event</span>
                </Link>
              </div>
            </div>
          ) : (
            <div className="events-grid">
              {events.map((event) => (
                <EventCard
                  key={`my-${event.id}`}
                  event={event}
                  onResponseChange={handleRefresh}
                />
              ))}
            </div>
          )}

          {/* Pagination */}
          {lastPage > 1 && (
            <div style={{ display: 'flex', justifyContent: 'center', gap: '8px', marginTop: '24px', flexWrap: 'wrap' }}>
              <button
                type="button"
                className="member-button member-button--secondary"
                disabled={currentPage <= 1}
                onClick={() => handlePageChange(currentPage - 1)}
              >
                Previous
              </button>
              <span style={{ display: 'flex', alignItems: 'center', fontSize: '13px', color: '#64748b' }}>
                Page {currentPage} of {lastPage}
              </span>
              <button
                type="button"
                className="member-button member-button--secondary"
                disabled={currentPage >= lastPage}
                onClick={() => handlePageChange(currentPage + 1)}
              >
                Next
              </button>
            </div>
          )}
        </section>
      ) : (
        /* Discover Upcoming Events Section */
        <section className="events-section">
          <div className="events-section__header">
            <h2>
              <Compass size={20} aria-hidden="true" />
              <span>Discover Upcoming Events ({totalEventsCount})</span>
            </h2>
          </div>

          {events.length === 0 ? (
            <div className="events-empty-state">
              <div className="events-empty-state__icon">
                <Calendar size={38} aria-hidden="true" />
              </div>
              <h2>No Events Found</h2>
              <p>No events match your current filter criteria. Try clearing filters or create a new event!</p>
              <Link to="/member/events/create" className="member-button member-button--primary" style={{ marginTop: '16px' }}>
                <Plus size={15} aria-hidden="true" />
                <span>Create New Event</span>
              </Link>
            </div>
          ) : (
            <div className="events-grid">
              {events.map((event) => (
                <EventCard
                  key={event.id}
                  event={event}
                  onResponseChange={handleRefresh}
                />
              ))}
            </div>
          )}

          {/* Pagination */}
          {lastPage > 1 && (
            <div style={{ display: 'flex', justifyContent: 'center', gap: '8px', marginTop: '24px', flexWrap: 'wrap' }}>
              <button
                type="button"
                className="member-button member-button--secondary"
                disabled={currentPage <= 1}
                onClick={() => handlePageChange(currentPage - 1)}
              >
                Previous
              </button>
              <span style={{ display: 'flex', alignItems: 'center', fontSize: '13px', color: '#64748b' }}>
                Page {currentPage} of {lastPage}
              </span>
              <button
                type="button"
                className="member-button member-button--secondary"
                disabled={currentPage >= lastPage}
                onClick={() => handlePageChange(currentPage + 1)}
              >
                Next
              </button>
            </div>
          )}
        </section>
      )}

      {/* Add Funds to Event Campaign Modal (Phase 4 reuse) */}
      {showAddFundsModal && selectedEventForFunding && (
        <AddFundsToEventCampaignModal
          event={selectedEventForFunding}
          campaign={selectedEventForFunding.campaign_details || selectedEventForFunding.campaign}
          availableAdFunds={availableAdFunds}
          platformFeePercent={platformFeePercent}
          onClose={() => {
            setShowAddFundsModal(false);
            setSelectedEventForFunding(null);
          }}
          onCampaignUpdated={(updatedCampaign, updatedAdFunds) => {
            setEvents((prev) =>
              prev.map((ev) => {
                if (ev.id === selectedEventForFunding.id) {
                  const merged = { ...(ev.campaign_details || {}), ...updatedCampaign };
                  return {
                    ...ev,
                    campaign: { ...(ev.campaign || {}), ...updatedCampaign },
                    campaign_details: merged,
                  };
                }
                return ev;
              })
            );
            if (updatedAdFunds !== undefined) {
              setAvailableAdFunds(Number(updatedAdFunds));
            }
            setFundingSuccessBanner(`Successfully added funds to "${selectedEventForFunding.title}"!`);
            setTimeout(() => setFundingSuccessBanner(null), 6000);
          }}
          onOpenDepositModal={() => {
            setShowAddFundsModal(false);
            setShowDepositModal(true);
          }}
        />
      )}

      {/* Universal USDT (BEP-20) Deposit Modal */}
      {showDepositModal && (
        <AddFundModal
          page={null}
          isOpen={showDepositModal}
          onClose={() => setShowDepositModal(false)}
          onDepositSubmitted={() => {
            setShowDepositModal(false);
            handleRefresh();
          }}
          onSuccess={() => {
            setShowDepositModal(false);
            handleRefresh();
          }}
        />
      )}

      {/* Account Verification Modal for Unverified Members */}
      <AccountVerificationModal
        isOpen={showVerifyModal}
        onClose={() => setShowVerifyModal(false)}
        onVerified={() => {
          setShowVerifyModal(false);
          window.location.href = '/member/events/create';
        }}
      />
    </div>
  );
}

export default EventsPage;
