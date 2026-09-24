import {
  GitFork,
  UserPlus,
  ShieldCheck,
  Users,
  Share2,
  Building2,
  Megaphone,
  Target,
  CheckCircle2,
  Gift,
  Wallet,
  ArrowDown,
} from 'lucide-react';

const PIPELINE_STEPS = [
  { num: '01', title: 'Join MLM Book', icon: UserPlus },
  { num: '02', title: 'Verify Your Account', icon: ShieldCheck },
  { num: '03', title: 'Build Your Network', icon: Users },
  { num: '04', title: 'Create & Share', icon: Share2 },
  { num: '05', title: 'Create Business / Event Presence', icon: Building2 },
  { num: '06', title: 'Promote Through Campaigns', icon: Megaphone },
  { num: '07', title: 'Reach Eligible Members', icon: Target },
  { num: '08', title: 'Complete Qualifying Actions', icon: CheckCircle2 },
  { num: '09', title: 'Receive Eligible Rewards', icon: Gift },
  { num: '10', title: 'View Rewards in Wallet', icon: Wallet },
];

export function HomeEndToEndWorkflow() {
  return (
    <section className="card home-section home-pipeline-section" aria-labelledby="pipeline-heading">
      <div className="home-section__header" style={{ marginBottom: '24px' }}>
        <div className="home-hub-badge home-hub-badge--blue">
          <GitFork size={13} aria-hidden="true" />
          <span>Full Ecosystem Journey</span>
        </div>
        <h2 className="home-section__title" id="pipeline-heading" style={{ fontSize: '1.45rem' }}>
          <GitFork size={22} aria-hidden="true" />
          <span>From Joining to Growth</span>
        </h2>
        <p className="home-section__desc">
          Follow the end-to-end pathway from your initial registration to business growth and credited wallet rewards.
        </p>
      </div>

      {/* 10-Step Pipeline Grid */}
      <div className="home-pipeline-grid">
        {PIPELINE_STEPS.map((item, index) => {
          const IconComponent = item.icon;
          return (
            <div key={item.num} className="home-pipeline-step">
              <div className="home-pipeline-step__num">{item.num}</div>
              <div
                style={{
                  width: '32px',
                  height: '32px',
                  borderRadius: '8px',
                  background: 'rgba(79, 125, 243, 0.08)',
                  color: 'var(--color-primary, #4f7df3)',
                  display: 'grid',
                  placeItems: 'center',
                }}
              >
                <IconComponent size={16} aria-hidden="true" />
              </div>
              <span className="home-pipeline-step__title">{item.title}</span>
              {index < PIPELINE_STEPS.length - 1 && (
                <div className="home-pipeline-arrow" aria-hidden="true">
                  <ArrowDown size={14} />
                </div>
              )}
            </div>
          );
        })}
      </div>
    </section>
  );
}

export default HomeEndToEndWorkflow;
