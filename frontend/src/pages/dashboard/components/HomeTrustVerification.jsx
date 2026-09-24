import { ShieldCheck, Smartphone, UserCheck, CheckSquare, Shield, Lock } from 'lucide-react';

const TRUST_ITEMS = [
  {
    title: 'Mobile / WhatsApp Verification',
    description: 'Verification helps maintain trusted member eligibility.',
    icon: Smartphone,
    tag: 'Verification',
    badgeColor: 'blue',
  },
  {
    title: 'Verified Member Identity',
    description: 'Verification supports controlled access to eligible reward actions.',
    icon: UserCheck,
    tag: 'Identity',
    badgeColor: 'green',
  },
  {
    title: 'Campaign Approval',
    description: 'Supported campaigns follow an existing review and approval process.',
    icon: CheckSquare,
    tag: 'Moderation',
    badgeColor: 'indigo',
  },
  {
    title: 'Reward Protection',
    description: 'Duplicate and owner self-reward protections are applied through the existing reward system.',
    icon: Shield,
    tag: 'Protection',
    badgeColor: 'purple',
  },
  {
    title: 'Privacy & Control',
    description: 'Members and businesses use the platform according to existing permissions and controls.',
    icon: Lock,
    tag: 'Control',
    badgeColor: 'amber',
  },
];

export function HomeTrustVerification() {
  return (
    <section className="card home-section home-trust-section" aria-labelledby="trust-heading">
      <div className="home-section__header" style={{ marginBottom: '24px' }}>
        <div className="home-hub-badge home-hub-badge--green">
          <ShieldCheck size={13} aria-hidden="true" />
          <span>Trust & Safety</span>
        </div>
        <h2 className="home-section__title" id="trust-heading" style={{ fontSize: '1.45rem' }}>
          <ShieldCheck size={22} aria-hidden="true" />
          <span>Built Around Trust</span>
        </h2>
        <p className="home-section__desc">
          Platform integrity, verified identities, and automated guardrails ensure a secure and trusted environment for every member and business.
        </p>
      </div>

      {/* 5 Trust Cards */}
      <div className="home-grid home-grid--5">
        {TRUST_ITEMS.map((item) => {
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

export default HomeTrustVerification;
