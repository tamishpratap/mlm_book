import { Link } from 'react-router-dom';
import { PlusCircle, Cake, Search } from 'lucide-react';
import VerifiedBadge from '../common/VerifiedBadge';
import MemberAvatar from '../common/MemberAvatar';

export function FeedRightSidebar({ contacts = [], ownedPage = null, showMessaging = false }) {
  return (
    <aside className="right-sidebar">
      {/* 1. Pages & Profiles Widget */}
      <section className="card widget pages-widget">
        <div className="section-heading d-flex align-items-center justify-content-between gap-2">
          <h2 className="m-0">Your Pages & Profiles</h2>
          <Link
            to="/member/business-pages"
            style={{
              color: 'var(--color-primary)',
              fontSize: '11px',
              fontWeight: 600,
              textDecoration: 'none',
              whiteSpace: 'nowrap',
            }}
          >
            See all
          </Link>
        </div>

        {ownedPage ? (
          <div className="page-profile">
            {ownedPage.logo_url ? (
              <img src={ownedPage.logo_url} alt={ownedPage.page_name} />
            ) : (
              <span className="avatar post-avatar-initials" style={{ width: '38px', height: '38px' }}>
                BP
              </span>
            )}
            <div>
              <strong>
                <Link
                  to={`/member/business-pages/${ownedPage.slug || ownedPage.id}`}
                  style={{ color: 'inherit', textDecoration: 'none' }}
                >
                  {ownedPage.page_name}
                </Link>
              </strong>
              <span>Business Page</span>
            </div>
          </div>
        ) : (
          <div style={{ padding: '6px 0', marginBottom: '8px' }}>
            <p style={{ fontSize: '0.8rem', color: 'var(--color-text-muted)', margin: '0 0 8px 0' }}>
              Create a Business Page to reach clients & promote your brand.
            </p>
            <Link
              className="member-button member-button--primary member-button--sm w-100"
              to="/member/business-pages"
              style={{ textDecoration: 'none', justifyContent: 'center' }}
            >
              <PlusCircle size={14} /> <span>Create Page</span>
            </Link>
          </div>
        )}
      </section>

      {/* 2. Birthdays Widget */}
      <section className="card widget birthday-widget">
        <h2>Birthdays</h2>
        <div className="birthday-widget__body">
          <p>Stay connected with your network on their special days!</p>
          <div className="gift-visual">
            <span>✦</span>
            <Cake size={24} style={{ color: 'var(--color-primary)' }} />
            <i>✦</i>
          </div>
        </div>
        <div className="birthday-widget__action">
          <Link
            className="soft-cta"
            to="/member/friends"
          >
            View Friends
          </Link>
        </div>
      </section>

      {/* 3. Contacts Widget */}
      <section className="card widget contacts-widget">
        <div className="contacts-widget__header">
          <h2>Contacts</h2>
          <div>
            <Link className="mini-button" to="/member/friends" aria-label="Search friends">
              <Search size={14} />
            </Link>
          </div>
        </div>
        <div className="contact-list">
          {contacts.length > 0 ? (
            contacts.map((contact) => {
              return (
                <Link
                  className="contact"
                  to={`/member/people/${contact.id}`}
                  key={contact.id}
                >
                  <span style={{ position: 'relative', display: 'inline-flex' }}>
                    <MemberAvatar member={contact} size={28} />
                    <i />
                  </span>
                  <strong style={{ display: 'inline-flex', alignItems: 'center' }}>
                    <span>{contact.name}</span>
                    <VerifiedBadge member={contact} size={12} />
                  </strong>
                </Link>
              );
            })
          ) : (
            <div style={{ padding: '10px 0', textAlign: 'center' }}>
              <small style={{ color: 'var(--color-text-muted)', fontSize: '0.8rem' }}>
                No active contacts yet.
              </small>
              <Link
                to="/member/people/suggestions"
                style={{
                  display: 'block',
                  marginTop: '4px',
                  fontSize: '0.8rem',
                  color: 'var(--color-primary)',
                  fontWeight: 600,
                  textDecoration: 'none',
                }}
              >
                Find Connections
              </Link>
            </div>
          )}
        </div>
      </section>

      {/* 4. Messaging Widget (Explicitly disabled/hidden on Connections page) */}
      {showMessaging && (
        <section className="card widget messaging-widget">
          <div className="section-heading d-flex align-items-center justify-content-between gap-2">
            <h2 className="m-0">Messaging</h2>
            <Link
              to="/member/messages"
              style={{
                color: 'var(--color-primary)',
                fontSize: '11px',
                fontWeight: 600,
                textDecoration: 'none',
                whiteSpace: 'nowrap',
              }}
            >
              Open
            </Link>
          </div>
          <div style={{ padding: '8px 0', fontSize: '0.825rem', color: 'var(--color-text-secondary)' }}>
            <p style={{ margin: '0 0 8px 0' }}>Chat and stay connected with your network.</p>
            <Link
              className="member-button member-button--secondary member-button--sm w-100"
              to="/member/messages"
              style={{ textDecoration: 'none', justifyContent: 'center' }}
            >
              <span>View Messages</span>
            </Link>
          </div>
        </section>
      )}
    </aside>
  );
}

export default FeedRightSidebar;
