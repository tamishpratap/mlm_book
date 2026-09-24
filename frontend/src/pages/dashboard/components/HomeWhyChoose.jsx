import { Sparkles, Layers, Users, Building2, Gift, ShieldCheck } from 'lucide-react';

const WHY_CHOOSE_ITEMS = [
  {
    title: 'One Connected Ecosystem',
    description: 'Social, business, community, events and content in one platform.',
    icon: Layers,
    badgeColor: 'blue',
  },
  {
    title: 'Built for Real Connections',
    description: 'Connect with people, creators, professionals and brands.',
    icon: Users,
    badgeColor: 'green',
  },
  {
    title: 'Built for Businesses',
    description: 'Create a business presence and promote qualifying content.',
    icon: Building2,
    badgeColor: 'purple',
  },
  {
    title: 'Reward-Enabled Engagement',
    description: 'Eligible members can earn through supported qualifying campaign interactions.',
    icon: Gift,
    badgeColor: 'amber',
  },
  {
    title: 'Trust & Control',
    description: 'Verification, permissions, moderation and reward protections are built into the ecosystem.',
    icon: ShieldCheck,
    badgeColor: 'indigo',
  },
];

export function HomeWhyChoose() {
  return (
    <section className="card home-section home-why-choose-section" aria-labelledby="why-choose-heading">
      <div className="home-section__header" style={{ marginBottom: '24px' }}>
        <div className="home-hub-badge home-hub-badge--purple">
          <Sparkles size={13} aria-hidden="true" />
          <span>Core Value Proposition</span>
        </div>
        <h2 className="home-section__title" id="why-choose-heading" style={{ fontSize: '1.45rem' }}>
          <Sparkles size={22} aria-hidden="true" />
          <span>Why Choose MLM Book?</span>
        </h2>
        <p className="home-section__desc">
          Discover the distinct advantages of a platform uniting social networking, enterprise brand tools, and verifiable rewards.
        </p>
      </div>

      {/* 5 Value Cards */}
      <div className="home-grid home-grid--5">
        {WHY_CHOOSE_ITEMS.map((item) => {
          const IconComponent = item.icon;
          return (
            <div key={item.title} className="home-process-card">
              <div
                style={{
                  width: '36px',
                  height: '36px',
                  borderRadius: '10px',
                  display: 'grid',
                  placeItems: 'center',
                  background: 'rgba(79, 125, 243, 0.1)',
                  color: 'var(--color-primary, #4f7df3)',
                  marginBottom: '12px',
                }}
              >
                <IconComponent size={18} aria-hidden="true" />
              </div>

              <h3 className="home-process-card__title" style={{ fontSize: '0.95rem' }}>
                {item.title}
              </h3>
              <p className="home-process-card__desc" style={{ fontSize: '12.5px' }}>
                {item.description}
              </p>
            </div>
          );
        })}
      </div>
    </section>
  );
}

export default HomeWhyChoose;
