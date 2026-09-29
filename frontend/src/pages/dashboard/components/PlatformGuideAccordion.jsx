import { useState } from 'react';
import { ChevronDown, ChevronUp, BookOpen } from 'lucide-react';

import HomeWhatIs from './HomeWhatIs';
import HomeCapabilities from './HomeCapabilities';
import HomeWorkflowSteps from './HomeWorkflowSteps';
import HomeAdvertisingProcess from './HomeAdvertisingProcess';
import HomeMemberRewards from './HomeMemberRewards';
import HomeRewardDeterminants from './HomeRewardDeterminants';
import HomeBusinessBenefits from './HomeBusinessBenefits';
import HomeMemberBenefits from './HomeMemberBenefits';
import HomeCampaignTypes from './HomeCampaignTypes';
import HomeRewardWallet from './HomeRewardWallet';
import HomeTrustVerification from './HomeTrustVerification';
import HomeEndToEndWorkflow from './HomeEndToEndWorkflow';
import HomeWhyChoose from './HomeWhyChoose';
import HomeFaqAccordion from './HomeFaqAccordion';

export function PlatformGuideAccordion() {
  const [isOpen, setIsOpen] = useState(false);

  return (
    <div style={{ marginTop: '12px' }}>
      <button
        type="button"
        onClick={() => setIsOpen((prev) => !prev)}
        style={{
          width: '100%',
          padding: '16px 20px',
          backgroundColor: '#ffffff',
          border: '1px solid #e2e8f0',
          borderRadius: isOpen ? '16px 16px 0 0' : '16px',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
          cursor: 'pointer',
          boxShadow: '0 2px 6px rgba(15, 23, 42, 0.03)',
          transition: 'all 0.2s ease',
        }}
      >
        <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
          <div
            style={{
              width: '32px',
              height: '32px',
              borderRadius: '8px',
              backgroundColor: '#eff6ff',
              color: '#3b82f6',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
            }}
          >
            <BookOpen size={16} />
          </div>
          <div style={{ textAlign: 'left' }}>
            <span style={{ fontWeight: 800, fontSize: '0.95rem', color: '#1e293b', display: 'block' }}>
              Platform Knowledge Base & FAQs
            </span>
            <span style={{ fontSize: '0.775rem', color: '#64748b' }}>
              Learn how advertising, rewards, rank determinants, and trust verification work
            </span>
          </div>
        </div>

        <div style={{ display: 'flex', alignItems: 'center', gap: '6px', color: '#3b82f6', fontWeight: 700, fontSize: '0.85rem' }}>
          <span>{isOpen ? 'Collapse Guide' : 'Expand Guide'}</span>
          {isOpen ? <ChevronUp size={16} /> : <ChevronDown size={16} />}
        </div>
      </button>

      {isOpen && (
        <div
          style={{
            padding: '24px',
            backgroundColor: '#ffffff',
            border: '1px solid #e2e8f0',
            borderTop: 'none',
            borderRadius: '0 0 16px 16px',
            display: 'flex',
            flexDirection: 'column',
            gap: '24px',
          }}
        >
          <HomeWhatIs />
          <HomeCapabilities />
          <HomeWorkflowSteps />
          <HomeAdvertisingProcess />
          <HomeMemberRewards />
          <HomeRewardDeterminants />
          <HomeBusinessBenefits />
          <HomeMemberBenefits />
          <HomeCampaignTypes />
          <HomeRewardWallet />
          <HomeTrustVerification />
          <HomeEndToEndWorkflow />
          <HomeWhyChoose />
          <HomeFaqAccordion />
        </div>
      )}
    </div>
  );
}

export default PlatformGuideAccordion;
