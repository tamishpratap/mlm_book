import { Megaphone, CalendarDays, ArrowRight, Info } from 'lucide-react';
import { Link } from 'react-router-dom';

export function HomeCampaignTypes() {
  return (
    <section className="card home-section home-campaign-types-section" aria-labelledby="campaign-types-heading">
      <div className="home-section__header" style={{ marginBottom: '24px' }}>
        <div className="home-hub-badge home-hub-badge--blue">
          <Megaphone size={13} aria-hidden="true" />
          <span>Campaign System Options</span>
        </div>
        <h2 className="home-section__title" id="campaign-types-heading" style={{ fontSize: '1.45rem' }}>
          <Megaphone size={22} aria-hidden="true" />
          <span>Two Ways to Promote and Engage</span>
        </h2>
        <p className="home-section__desc">
          Choose the campaign structure that best matches your promotional objectives and community engagement goals.
        </p>
      </div>

      <div className="home-grid home-grid--2">
        {/* Card 1: Business Campaigns */}
        <div className="home-info-card-v2">
          <div className="home-info-card-v2__header">
            <div className="home-info-card-v2__icon home-info-card-v2__icon--primary">
              <Megaphone size={22} aria-hidden="true" />
            </div>
            <span className="home-info-card-v2__tag">Advertising Promotion</span>
          </div>
          <h3 className="home-info-card-v2__title">Business Campaigns</h3>
          <p className="home-info-card-v2__desc">
            Businesses can promote qualifying Business Page content through supported advertising campaigns.
          </p>
          <div style={{ marginTop: 'auto', paddingTop: '16px' }}>
            <Link to="/member/business-pages" className="workflow-card__link">
              <span>Explore Business Campaigns</span>
              <ArrowRight size={13} aria-hidden="true" />
            </Link>
          </div>
        </div>

        {/* Card 2: Event Campaigns - Temporarily Disabled */}
        {/*
        <div className="home-info-card-v2">
          <div className="home-info-card-v2__header">
            <div className="home-info-card-v2__icon" style={{ background: 'rgba(124, 58, 237, 0.12)', color: '#7c3aed' }}>
              <CalendarDays size={22} aria-hidden="true" />
            </div>
            <span className="home-info-card-v2__tag" style={{ background: 'rgba(124, 58, 237, 0.1)', color: '#7c3aed' }}>
              Events & Summits
            </span>
          </div>
          <h3 className="home-info-card-v2__title">Event Campaigns</h3>
          <p className="home-info-card-v2__desc">
            Event organizers can use supported Event campaign and reward flows to promote events and engage eligible members.
          </p>
          <div style={{ marginTop: 'auto', paddingTop: '16px' }}>
            <Link to="/member/events" className="workflow-card__link">
              <span>Explore Event Campaigns</span>
              <ArrowRight size={13} aria-hidden="true" />
            </Link>
          </div>
        </div>
        */}
      </div>

      {/* Small Informational Note */}
      <div className="home-note-card">
        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
          <Info size={16} color="var(--color-primary, #4f7df3)" style={{ flexShrink: 0 }} />
          <span>
            Campaign availability, approval, eligibility and reward rules depend on the campaign type and current platform configuration.
          </span>
        </div>
      </div>
    </section>
  );
}

export default HomeCampaignTypes;
