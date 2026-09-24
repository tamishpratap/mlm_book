import { Gift, Smartphone, CheckCircle, Target, Scale, Wallet } from 'lucide-react';

const REWARD_CONDITIONS = [
  {
    title: 'Mobile Verified',
    description: 'Required mobile/WhatsApp verification must be completed for supported reward qualification.',
    icon: Smartphone,
    color: 'blue',
  },
  {
    title: 'Active Campaign',
    description: 'The campaign must be active and eligible.',
    icon: CheckCircle,
    color: 'green',
  },
  {
    title: 'Qualifying Action',
    description: 'The member must complete the supported qualifying action.',
    icon: Target,
    color: 'indigo',
  },
  {
    title: 'Reward Rule',
    description: 'Reward amounts depend on the active campaign reward rules.',
    icon: Scale,
    color: 'amber',
  },
  {
    title: 'Reward Credit',
    description: 'Successfully issued rewards are added to the member’s Reward Wallet.',
    icon: Wallet,
    color: 'purple',
  },
];

export function HomeMemberRewards() {
  return (
    <section className="card home-section home-rewards-section" aria-labelledby="member-rewards-heading">
      <div className="home-section__header" style={{ marginBottom: '24px' }}>
        <div className="home-hub-badge home-hub-badge--green">
          <Gift size={13} aria-hidden="true" />
          <span>Reward Mechanics</span>
        </div>
        <h2 className="home-section__title" id="member-rewards-heading" style={{ fontSize: '1.45rem' }}>
          <Gift size={22} aria-hidden="true" />
          <span>How Member Rewards Work</span>
        </h2>
        <p className="home-section__desc" style={{ fontSize: '14px', lineHeight: '1.65' }}>
          MLM Book rewards are designed around qualifying campaign engagement. Eligible, mobile-verified members may receive rewards when they complete the conditions defined by an active campaign reward rule.
        </p>
      </div>

      {/* 5 Compact Cards */}
      <div className="home-grid home-grid--5">
        {REWARD_CONDITIONS.map((item) => {
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

export default HomeMemberRewards;
