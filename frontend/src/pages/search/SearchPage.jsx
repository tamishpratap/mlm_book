import { useState, useEffect, useCallback, useRef } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import {
  Search,
  Users,
  Flag,
  UsersRound,
  FileText,
  CalendarDays,
  X,
  MapPin,
  Calendar,
  AlertCircle,
  UserPlus,
  Check,
  RotateCw,
} from 'lucide-react';
import useAuth from '../../hooks/useAuth';
import searchApi from '../../api/searchApi';
import friendApi from '../../api/friendApi';
import CommunityCard from '../../components/community/CommunityCard';
import BusinessCard from '../../components/business/BusinessCard';
import EventCard from '../../components/events/EventCard';
import PostCard from '../../components/posts/PostCard';
import FeedRightSidebar from '../../components/posts/FeedRightSidebar';
import VerifiedBadge from '../../components/common/VerifiedBadge';
import MemberAvatar from '../../components/common/MemberAvatar';

const TABS = [
  { type: 'all', label: 'All', icon: Search },
  { type: 'members', label: 'People', icon: Users },
  { type: 'pages', label: 'Business Pages', icon: Flag },
  { type: 'groups', label: 'Communities', icon: UsersRound },
  { type: 'posts', label: 'Posts', icon: FileText },
  // { type: 'events', label: 'Events', icon: CalendarDays }, // Temporarily disabled
];

function getInitials(name) {
  if (!name) return 'M';
  const parts = name.trim().split(/\s+/);
  return parts.slice(0, 2).map((p) => p[0].toUpperCase()).join('') || 'M';
}

function formatMonthYear(dateString) {
  if (!dateString) return '';
  const d = new Date(dateString);
  return d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
}

export function SearchPage() {
  const { user: currentUser } = useAuth();
  const [searchParams, setSearchParams] = useSearchParams();

  const queryParam = searchParams.get('q') || '';
  const typeParam = searchParams.get('type') || 'all';
  const pageParam = parseInt(searchParams.get('page') || '1', 10);

  const [queryInput, setQueryInput] = useState(queryParam);
  const [activeType, setActiveType] = useState(typeParam);

  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);

  const [counts, setCounts] = useState({
    all: 0,
    members: 0,
    pages: 0,
    groups: 0,
    posts: 0,
    events: 0,
  });

  const [state, setState] = useState(queryParam.length >= 2 ? 'results' : queryParam.length === 0 ? 'initial' : 'short');
  const [members, setMembers] = useState(null);
  const [communities, setCommunities] = useState(null);
  const [pages, setPages] = useState(null);
  const [events, setEvents] = useState(null);
  const [posts, setPosts] = useState(null);

  const [connectingIds, setConnectingIds] = useState(new Set());
  const [connectedIds, setConnectedIds] = useState(new Set());

  const debounceTimerRef = useRef(null);

  const performSearch = useCallback((q, type, page = 1) => {
    const trimmed = q.trim();
    if (trimmed.length < 2) {
      setState(trimmed.length === 0 ? 'initial' : 'short');
      setIsLoading(false);
      setMembers(null);
      setCommunities(null);
      setPages(null);
      setEvents(null);
      setPosts(null);
      setCounts({ all: 0, members: 0, pages: 0, groups: 0, posts: 0, events: 0 });
      return;
    }

    setIsLoading(true);
    setError(null);

    searchApi
      .search(trimmed, type, page)
      .then((data) => {
        if (data) {
          if (data.counts) setCounts(data.counts);
          setState(data.state || 'results');

          // Members
          if (data.members) {
            const rawMembers = data.members.data ? data.members.data : Array.isArray(data.members) ? data.members : [];
            setMembers(rawMembers.filter((m) => Boolean(m.is_verified || m.mobile_verified_at)));
          } else {
            setMembers(null);
          }

          // Communities
          if (data.communities) {
            setCommunities(data.communities.data ? data.communities.data : Array.isArray(data.communities) ? data.communities : []);
          } else {
            setCommunities(null);
          }

          // Pages
          if (data.pages) {
            setPages(data.pages.data ? data.pages.data : Array.isArray(data.pages) ? data.pages : []);
          } else {
            setPages(null);
          }

          // Events
          if (data.events) {
            setEvents(data.events.data ? data.events.data : Array.isArray(data.events) ? data.events : []);
          } else {
            setEvents(null);
          }

          // Posts
          if (data.posts) {
            setPosts(data.posts.data ? data.posts.data : Array.isArray(data.posts) ? data.posts : []);
          } else {
            setPosts(null);
          }
        }
      })
      .catch((err) => {
        setError(err.response?.data?.message || 'Search request failed.');
      })
      .finally(() => {
        setIsLoading(false);
      });
  }, []);

  // Sync with searchParams
  useEffect(() => {
    setQueryInput(queryParam);
    setActiveType(typeParam);
    performSearch(queryParam, typeParam, pageParam);
  }, [queryParam, typeParam, pageParam, performSearch]);

  const handleInputChange = (e) => {
    const val = e.target.value;
    setQueryInput(val);

    if (debounceTimerRef.current) {
      clearTimeout(debounceTimerRef.current);
    }

    debounceTimerRef.current = setTimeout(() => {
      const newParams = {};
      if (val.trim()) newParams.q = val.trim();
      if (activeType !== 'all') newParams.type = activeType;
      setSearchParams(newParams);
    }, 350);
  };

  const handleClear = () => {
    setQueryInput('');
    setSearchParams(activeType !== 'all' ? { type: activeType } : {});
  };

  const handleTabClick = (type) => {
    setActiveType(type);
    const newParams = {};
    if (queryInput.trim()) newParams.q = queryInput.trim();
    if (type !== 'all') newParams.type = type;
    setSearchParams(newParams);
  };

  const handleConnect = async (memberId) => {
    if (connectingIds.has(memberId) || connectedIds.has(memberId)) return;
    setConnectingIds((prev) => new Set(prev).add(memberId));
    try {
      await friendApi.sendFriendRequest(memberId);
      setConnectedIds((prev) => new Set(prev).add(memberId));
    } catch {
      // Revert if error
    } finally {
      setConnectingIds((prev) => {
        const next = new Set(prev);
        next.delete(memberId);
        return next;
      });
    }
  };

  const renderMemberCard = (member) => {
    const isConnecting = connectingIds.has(member.id);
    const isConnected = connectedIds.has(member.id);
    const location = [member.city, member.country].filter(Boolean).join(', ');

    return (
      <article className="member-result-card" key={member.id}>
        <MemberAvatar member={member} size={56} className="member-result-card__avatar" />

        <div className="member-result-card__content">
          <div className="member-result-card__name-row">
            <h3 style={{ display: 'inline-flex', alignItems: 'center' }}>
              <Link to={`/member/people/${member.id}`} style={{ display: 'inline-flex', alignItems: 'center' }}>
                <span>{member.name}</span>
                <VerifiedBadge member={member} size={14} />
              </Link>
            </h3>
            <span>Member</span>
          </div>

          {member.user_id && (
            <div className="member-result-card__meta">
              <span>@{member.user_id}</span>
            </div>
          )}

          <p className="member-result-card__bio">
            {member.bio ? (member.bio.length > 140 ? `${member.bio.slice(0, 140)}...` : member.bio) : 'MLM Book Member'}
          </p>

          <div className="member-result-card__meta">
            {location && (
              <span>
                <MapPin size={13} aria-hidden="true" />
                {location}
              </span>
            )}
            <span>
              <Calendar size={13} aria-hidden="true" />
              Joined {formatMonthYear(member.created_at)}
            </span>
          </div>
        </div>

        <div className="member-result-card__actions">
          <button
            className={`member-button ${isConnected ? 'member-button--primary' : 'member-button--secondary'} member-button--sm`}
            type="button"
            onClick={() => handleConnect(member.id)}
            disabled={isConnecting || isConnected}
          >
            {isConnected ? (
              <>
                <Check size={14} /> Sent
              </>
            ) : (
              <>
                <UserPlus size={14} /> {isConnecting ? 'Connecting...' : 'Connect'}
              </>
            )}
          </button>
          <Link className="friend-link" to={`/member/people/${member.id}`}>
            View Profile
          </Link>
        </div>
      </article>
    );
  };

  return (
    <>
      <main className="member-main feed" id="search-page-main">
        <div className="member-search-page">
          {/* Search Header Panel */}
          <section className="member-card member-search-panel" aria-labelledby="member-search-heading">
            <header className="member-search-panel__header">
              <div>
                <span className="member-search-panel__eyebrow">Discover</span>
                <h1 id="member-search-heading">Search</h1>
                <p>Find people and explore everything available on MLM Book.</p>
              </div>
            </header>

            {/* Search Input Bar */}
            <form
              className="member-search-form"
              onSubmit={(e) => {
                e.preventDefault();
                performSearch(queryInput, activeType, 1);
              }}
              role="search"
            >
              <button className="member-search-form__submit" type="submit" aria-label="Search MLM Book">
                <Search size={18} aria-hidden="true" />
              </button>

              <input
                id="member-search-query"
                type="search"
                name="q"
                value={queryInput}
                onChange={handleInputChange}
                placeholder="Search MLM Book..."
                aria-label="Search MLM Book"
                maxLength={100}
                autoComplete="off"
                autoFocus
              />

              {queryInput && (
                <button
                  className="member-search-form__clear"
                  type="button"
                  aria-label="Clear search"
                  onClick={handleClear}
                >
                  <X size={16} aria-hidden="true" />
                </button>
              )}
            </form>

            {/* Category Filter Tabs */}
            <div className="member-search-tabs" role="tablist" aria-label="Search categories">
              {TABS.map((tab) => {
                const TabIcon = tab.icon;
                const count = counts[tab.type] || 0;
                const isActive = activeType === tab.type;

                return (
                  <button
                    key={tab.type}
                    type="button"
                    className={`member-search-tab ${isActive ? 'is-active' : ''}`}
                    role="tab"
                    aria-selected={isActive}
                    onClick={() => handleTabClick(tab.type)}
                  >
                    <TabIcon size={16} aria-hidden="true" />
                    <span>{tab.label}</span>
                    <small>{count}</small>
                  </button>
                );
              })}
            </div>
          </section>

          {/* Results Container */}
          <div className="member-search-results-container">
            {isLoading ? (
              <div style={{ padding: '40px 20px', textAlign: 'center', color: 'var(--color-text-muted)' }}>
                <RotateCw size={24} className="spin-icon" style={{ margin: '0 auto 12px auto', display: 'block' }} />
                <span>Searching MLM Book...</span>
              </div>
            ) : error ? (
              <section className="member-search-state" role="alert">
                <span className="member-search-state__icon">
                  <AlertCircle size={32} />
                </span>
                <h2>Search unavailable</h2>
                <p>{error}</p>
                <button
                  className="member-button member-button--primary"
                  type="button"
                  onClick={() => performSearch(queryInput, activeType, 1)}
                  style={{ marginTop: '12px' }}
                >
                  Retry
                </button>
              </section>
            ) : state === 'initial' ? (
              <div className="member-search-empty-state">
                <Search size={40} color="var(--color-primary)" style={{ opacity: 0.7, marginBottom: '12px' }} />
                <h3>Search MLM Book</h3>
                <p>Type at least 2 characters to search across People, Business Pages, Communities, Events, and Posts.</p>
              </div>
            ) : state === 'short' ? (
              <div className="member-search-empty-state">
                <Search size={40} color="var(--color-primary)" style={{ opacity: 0.7, marginBottom: '12px' }} />
                <h3>Keep typing...</h3>
                <p>Please enter at least 2 characters to search.</p>
              </div>
            ) : state === 'empty' ? (
              <div className="member-search-empty-state">
                <Search size={40} color="var(--color-text-muted)" style={{ opacity: 0.5, marginBottom: '12px' }} />
                <h3>No results found</h3>
                <p>We couldn't find any results matching "{queryInput}". Try different keywords or check spelling.</p>
              </div>
            ) : activeType === 'all' ? (
              <section className="member-search-results" aria-labelledby="member-search-summary">
                <header className="member-search-results__header">
                  <div>
                    <h2 id="member-search-summary">Results for "{queryInput}"</h2>
                    <p>{counts.all} {counts.all === 1 ? 'result' : 'results'} found</p>
                  </div>
                  <span className="member-search-results__badge">All</span>
                </header>

                {/* People section */}
                {members && members.length > 0 && (
                  <section className="member-search-result-group" aria-labelledby="people-results-heading">
                    <header className="member-search-result-group__header">
                      <div>
                        <h3 id="people-results-heading">People</h3>
                        <span>{counts.members}</span>
                      </div>
                      <button
                        type="button"
                        className="member-search-see-all-btn"
                        onClick={() => handleTabClick('members')}
                        style={{ border: 'none', background: 'transparent', color: 'var(--color-primary)', fontWeight: 600, cursor: 'pointer' }}
                      >
                        See all
                      </button>
                    </header>
                    <div className="member-search-list">
                      {members.slice(0, 5).map(renderMemberCard)}
                    </div>
                  </section>
                )}

                {/* Communities section */}
                {communities && communities.length > 0 && (
                  <section className="member-search-result-group" style={{ marginTop: '24px' }}>
                    <header className="member-search-result-group__header">
                      <div>
                        <h3>Communities</h3>
                        <span>{counts.groups}</span>
                      </div>
                      <button
                        type="button"
                        className="member-search-see-all-btn"
                        onClick={() => handleTabClick('groups')}
                        style={{ border: 'none', background: 'transparent', color: 'var(--color-primary)', fontWeight: 600, cursor: 'pointer' }}
                      >
                        See all
                      </button>
                    </header>
                    <div className="community-grid">
                      {communities.slice(0, 6).map((c) => (
                        <CommunityCard key={c.id} community={c} />
                      ))}
                    </div>
                  </section>
                )}

                {/* Business Pages section */}
                {pages && pages.length > 0 && (
                  <section className="member-search-result-group" style={{ marginTop: '24px' }}>
                    <header className="member-search-result-group__header">
                      <div>
                        <h3>Business Pages</h3>
                        <span>{counts.pages}</span>
                      </div>
                      <button
                        type="button"
                        className="member-search-see-all-btn"
                        onClick={() => handleTabClick('pages')}
                        style={{ border: 'none', background: 'transparent', color: 'var(--color-primary)', fontWeight: 600, cursor: 'pointer' }}
                      >
                        See all
                      </button>
                    </header>
                    <div className="community-grid">
                      {pages.slice(0, 6).map((p) => (
                        <BusinessCard key={p.id} businessPage={p} />
                      ))}
                    </div>
                  </section>
                )}

                {/* Events section - Temporarily Disabled */}
                {/*
                {events && events.length > 0 && (
                  <section className="member-search-result-group" style={{ marginTop: '24px' }}>
                    <header className="member-search-result-group__header">
                      <div>
                        <h3>Events</h3>
                        <span>{counts.events}</span>
                      </div>
                      <button
                        type="button"
                        className="member-search-see-all-btn"
                        onClick={() => handleTabClick('events')}
                        style={{ border: 'none', background: 'transparent', color: 'var(--color-primary)', fontWeight: 600, cursor: 'pointer' }}
                      >
                        See all
                      </button>
                    </header>
                    <div className="groups-grid">
                      {events.slice(0, 6).map((e) => (
                        <EventCard key={e.id} event={e} />
                      ))}
                    </div>
                  </section>
                )}
                */}

                {/* Posts section */}
                {posts && posts.length > 0 && (
                  <section className="member-search-result-group" style={{ marginTop: '24px' }}>
                    <header className="member-search-result-group__header">
                      <div>
                        <h3>Posts</h3>
                        <span>{counts.posts}</span>
                      </div>
                      <button
                        type="button"
                        className="member-search-see-all-btn"
                        onClick={() => handleTabClick('posts')}
                        style={{ border: 'none', background: 'transparent', color: 'var(--color-primary)', fontWeight: 600, cursor: 'pointer' }}
                      >
                        See all
                      </button>
                    </header>
                    <div className="posts-feed" style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
                      {posts.slice(0, 5).map((post) => (
                        <PostCard key={post.id} post={post} currentUser={currentUser} />
                      ))}
                    </div>
                  </section>
                )}
              </section>
            ) : activeType === 'members' ? (
              <section className="member-search-results">
                <header className="member-search-results__header">
                  <div>
                    <h2>People matching "{queryInput}"</h2>
                    <p>{counts.members} {counts.members === 1 ? 'person' : 'people'} found</p>
                  </div>
                  <span className="member-search-results__badge">People</span>
                </header>
                <div className="member-search-list">
                  {members && members.map(renderMemberCard)}
                </div>
              </section>
            ) : activeType === 'groups' ? (
              <section className="member-search-results">
                <header className="member-search-results__header">
                  <div>
                    <h2>Communities matching "{queryInput}"</h2>
                    <p>{counts.groups} {counts.groups === 1 ? 'community' : 'communities'} found</p>
                  </div>
                  <span className="member-search-results__badge">Communities</span>
                </header>
                <div className="community-grid">
                  {communities && communities.map((c) => (
                    <CommunityCard key={c.id} community={c} />
                  ))}
                </div>
              </section>
            ) : activeType === 'pages' ? (
              <section className="member-search-results">
                <header className="member-search-results__header">
                  <div>
                    <h2>Business Pages matching "{queryInput}"</h2>
                    <p>{counts.pages} {counts.pages === 1 ? 'page' : 'pages'} found</p>
                  </div>
                  <span className="member-search-results__badge">Business Pages</span>
                </header>
                <div className="community-grid">
                  {pages && pages.map((p) => (
                    <BusinessCard key={p.id} businessPage={p} />
                  ))}
                </div>
              </section>
            ) /* : activeType === 'events' ? (
              <section className="member-search-results">
                <header className="member-search-results__header">
                  <div>
                    <h2>Events matching "{queryInput}"</h2>
                    <p>{counts.events} {counts.events === 1 ? 'event' : 'events'} found</p>
                  </div>
                  <span className="member-search-results__badge">Events</span>
                </header>
                <div className="groups-grid">
                  {events && events.map((e) => (
                    <EventCard key={e.id} event={e} />
                  ))}
                </div>
              </section>
            ) */ : activeType === 'posts' ? (
              <section className="member-search-results">
                <header className="member-search-results__header">
                  <div>
                    <h2>Posts matching "{queryInput}"</h2>
                    <p>{counts.posts} {counts.posts === 1 ? 'post' : 'posts'} found</p>
                  </div>
                  <span className="member-search-results__badge">Posts</span>
                </header>
                <div className="posts-feed" style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
                  {posts && posts.map((post) => (
                    <PostCard key={post.id} post={post} currentUser={currentUser} />
                  ))}
                </div>
              </section>
            ) : null}
          </div>
        </div>
      </main>

      <FeedRightSidebar currentUser={currentUser} />
    </>
  );
}

export default SearchPage;
