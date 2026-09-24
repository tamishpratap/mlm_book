import { Megaphone, FileText, Settings, DollarSign, ShieldCheck, Radio, Users, Award, Wallet } from 'lucide-react';

const ADVERTISING_STEPS = [
  {
    step: '01',
    title: 'Create Content',
    description: 'Publish qualifying content on your Business Page.',
    icon: FileText,
    color: 'blue',
  },
  {
    step: '02',
    title: 'Create Campaign',
    description: 'Use the campaign system to promote eligible content.',
    icon: Settings,
    color: 'indigo',
  },
  {
    step: '03',
    title: 'Set Budget',
    description: 'Allocate your campaign budget.',
    icon: DollarSign,
    color: 'purple',
  },
  {
    step: '04',
    title: 'Review & Approval',
    description: 'Campaigns follow the platform’s existing review and approval process.',
    icon: ShieldCheck,
    color: 'green',
  },
  {
    step: '05',
    title: 'Campaign Goes Live',
    description: 'Approved campaigns can reach eligible members.',
    icon: Radio,
    color: 'amber',
  },
  {
    step: '06',
    title: 'Member Engagement',
    description: 'Members can interact according to the campaign’s supported actions.',
    icon: Users,
    color: 'blue',
  },
  {
    step: '07',
    title: 'Reward Qualification',
    description: 'Qualifying conditions determine whether a member becomes eligible for a reward.',
    icon: Award,
    color: 'purple',
  },
  {
    step: '08',
    title: 'Reward Credit',
    description: 'Successfully issued rewards are added to the member’s Reward Wallet.',
    icon: Wallet,
    color: 'green',
  },
];

export function HomeAdvertisingProcess() {
  return (
    <section className="card home-section home-advertising-section" aria-labelledby="ads-heading">
      <div className="home-section__header" style={{ marginBottom: '24px' }}>
        <div className="home-hub-badge home-hub-badge--purple">
          <Megaphone size={13} aria-hidden="true" />
          <span>Advertising System</span>
        </div>
        <h2 className="home-section__title" id="ads-heading" style={{ fontSize: '1.45rem' }}>
          <Megaphone size={22} aria-hidden="true" />
          <span>How MLM Book Advertising Works</span>
        </h2>
        <p className="home-section__desc">
          Businesses can promote high-quality Business Page posts through transparent, rule-based campaigns that reach verified and engaged community members.
        </p>
      </div>

      {/* 8-Step Advertising Visual Process Grid */}
      <div className="home-grid home-grid--4">
        {ADVERTISING_STEPS.map((item) => {
          const IconComponent = item.icon;
          return (
            <div key={item.step} className="home-process-card">
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '10px' }}>
                <span className="home-process-card__step">{item.step}</span>
                <div
                  style={{
                    width: '32px',
                    height: '32px',
                    borderRadius: '8px',
                    display: 'grid',
                    placeItems: 'center',
                    background: 'rgba(79, 125, 243, 0.08)',
                    color: 'var(--color-primary, #4f7df3)',
                  }}
                >
                  <IconComponent size={16} aria-hidden="true" />
                </div>
              </div>

              <h3 className="home-process-card__title">{item.title}</h3>
              <p className="home-process-card__desc">{item.description}</p>
            </div>
          );
        })}
      </div>
    </section>
  );
}

export default HomeAdvertisingProcess;
