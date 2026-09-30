import { useEffect } from 'react';
import '../../styles/dashboard.css';

// 16 Standard Home Section Components from dashboard/components
import HomeHero from '../dashboard/components/HomeHero';
import HomeWhatIs from '../dashboard/components/HomeWhatIs';
import HomeCapabilities from '../dashboard/components/HomeCapabilities';
import HomeWorkflowSteps from '../dashboard/components/HomeWorkflowSteps';
import HomeAdvertisingProcess from '../dashboard/components/HomeAdvertisingProcess';
import HomeMemberRewards from '../dashboard/components/HomeMemberRewards';
import HomeRewardDeterminants from '../dashboard/components/HomeRewardDeterminants';
import HomeBusinessBenefits from '../dashboard/components/HomeBusinessBenefits';
import HomeMemberBenefits from '../dashboard/components/HomeMemberBenefits';
import HomeCampaignTypes from '../dashboard/components/HomeCampaignTypes';
import HomeRewardWallet from '../dashboard/components/HomeRewardWallet';
import HomeTrustVerification from '../dashboard/components/HomeTrustVerification';
import HomeEndToEndWorkflow from '../dashboard/components/HomeEndToEndWorkflow';
import HomeWhyChoose from '../dashboard/components/HomeWhyChoose';
import HomeFaqAccordion from '../dashboard/components/HomeFaqAccordion';
import HomeCtaBanner from '../dashboard/components/HomeCtaBanner';

export function LandingPage() {
  useEffect(() => {
    document.title = 'MLM Book - The Next-Generation Digital Social & Business Ecosystem';
  }, []);

  return (
    <div
      className="pub-home-landing-wrapper"
      style={{
        padding: '24px 16px 80px',
        maxWidth: '1240px',
        margin: '0 auto',
        width: '100%',
        boxSizing: 'border-box',
      }}
    >
      <main className="home-container" id="home-landing">
        {/* Section 1 — Hero */}
        <HomeHero />

        {/* Section 2 — What is MLM Book? (Ecosystem Overview) */}
        <HomeWhatIs />

        {/* Section 3 — What Can You Do on MLM Book? */}
        <HomeCapabilities />

        {/* Section 4 — How MLM Book Works */}
        <HomeWorkflowSteps />

        {/* Section 5 — How Advertising Works */}
        <HomeAdvertisingProcess />

        {/* Section 6 — How Member Rewards Work */}
        <HomeMemberRewards />

        {/* Section 7 — What Determines Your Reward? */}
        <HomeRewardDeterminants />

        {/* Section 8 — Business Owner Benefits */}
        <HomeBusinessBenefits />

        {/* Section 9 — Member Benefits */}
        <HomeMemberBenefits />

        {/* Section 10 — Business Campaigns & Event Campaigns */}
        <HomeCampaignTypes />

        {/* Section 11 — Reward Wallet */}
        <HomeRewardWallet />

        {/* Section 12 — Trust & Verification */}
        <HomeTrustVerification />

        {/* Section 13 — End-to-End Workflow */}
        <HomeEndToEndWorkflow />

        {/* Section 14 — Why MLM Book? */}
        <HomeWhyChoose />

        {/* Section 15 — FAQ */}
        <HomeFaqAccordion />

        {/* Section 16 — Final CTA */}
        <HomeCtaBanner />
      </main>
    </div>
  );
}

export default LandingPage;
