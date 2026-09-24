import { Sliders, ShieldCheck, UsersRound, FileCheck, Info } from 'lucide-react';

export function HomeRewardDeterminants() {
  return (
    <section className="card home-section home-determinants-section" aria-labelledby="determinants-heading">
      <div className="home-section__header" style={{ marginBottom: '24px' }}>
        <div className="home-hub-badge home-hub-badge--indigo">
          <Sliders size={13} aria-hidden="true" />
          <span>Reward Calculation & Rules</span>
        </div>
        <h2 className="home-section__title" id="determinants-heading" style={{ fontSize: '1.45rem' }}>
          <Sliders size={22} aria-hidden="true" />
          <span>What Determines Your Reward?</span>
        </h2>
        <p className="home-section__desc" style={{ fontSize: '14.5px', lineHeight: '1.65' }}>
          Reward qualification and amount depend on the active campaign rules and the member’s eligibility.
        </p>
      </div>

      <div className="home-grid home-grid--2">
        <div className="home-info-card-v2">
          <div className="home-info-card-v2__header">
            <div className="home-info-card-v2__icon home-info-card-v2__icon--primary">
              <FileCheck size={22} aria-hidden="true" />
            </div>
            <span className="home-info-card-v2__tag">Active Campaign Rules</span>
          </div>
          <h3 className="home-info-card-v2__title">Campaign-Specific Configuration</h3>
          <p className="home-info-card-v2__desc">
            Each advertising or event campaign defines its own qualifying actions, allocated budget, and distribution rules. Rewards are only issued when conditions established by the active campaign are fully satisfied.
          </p>
          <div className="home-info-card-v2__features">
            <div className="home-info-card-v2__item">
              <ShieldCheck size={16} color="#4f7df3" aria-hidden="true" />
              <span>Budget thresholds and validation criteria govern every reward distribution.</span>
            </div>
          </div>
        </div>

        <div className="home-info-card-v2">
          <div className="home-info-card-v2__header">
            <div className="home-info-card-v2__icon" style={{ background: 'rgba(16, 185, 129, 0.12)', color: '#10b981' }}>
              <UsersRound size={22} aria-hidden="true" />
            </div>
            <span className="home-info-card-v2__tag" style={{ background: 'rgba(16, 185, 129, 0.12)', color: '#059669' }}>
              Eligibility Criteria
            </span>
          </div>
          <h3 className="home-info-card-v2__title">Member Eligibility & Direct Levels</h3>
          <p className="home-info-card-v2__desc">
            Certain reward flows can use verified direct-member/referral levels as part of qualification. All qualification is subject to completed mobile/WhatsApp verification, fraud protection filters, and platform eligibility guidelines.
          </p>
          <div className="home-info-card-v2__features">
            <div className="home-info-card-v2__item">
              <Info size={16} color="#10b981" aria-hidden="true" />
              <span>Rewards are not guaranteed; they are conditionally issued based on active rules.</span>
            </div>
          </div>
        </div>
      </div>

      <div className="home-note-card">
        <strong>Important Notice:</strong> MLM Book does not offer fixed daily earnings, guaranteed income, or passive investment returns. All rewards are strictly based on legitimate campaign interaction rules and verified participation.
      </div>
    </section>
  );
}

export default HomeRewardDeterminants;
