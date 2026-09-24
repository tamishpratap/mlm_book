import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import useAuth from '../../hooks/useAuth';
import dashboardApi from '../../api/dashboardApi';

// Subcomponents (16 Sections in exact specified order)
import HomeHero from './components/HomeHero';
import HomeWhatIs from './components/HomeWhatIs';
import HomeCapabilities from './components/HomeCapabilities';
import HomeWorkflowSteps from './components/HomeWorkflowSteps';
import HomeAdvertisingProcess from './components/HomeAdvertisingProcess';
import HomeMemberRewards from './components/HomeMemberRewards';
import HomeRewardDeterminants from './components/HomeRewardDeterminants';
import HomeBusinessBenefits from './components/HomeBusinessBenefits';
import HomeMemberBenefits from './components/HomeMemberBenefits';
import HomeCampaignTypes from './components/HomeCampaignTypes';
import HomeRewardWallet from './components/HomeRewardWallet';
import HomeTrustVerification from './components/HomeTrustVerification';
import HomeEndToEndWorkflow from './components/HomeEndToEndWorkflow';
import HomeWhyChoose from './components/HomeWhyChoose';
import HomeFaqAccordion from './components/HomeFaqAccordion';
import HomeCtaBanner from './components/HomeCtaBanner';
import DashboardSkeleton from './components/DashboardSkeleton';
import MissedIntroducerReminderModal from './components/MissedIntroducerReminderModal';

export function DashboardPage() {
  const navigate = useNavigate();
  const { user } = useAuth();
  const [dashboardData, setDashboardData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [isReminderOpen, setIsReminderOpen] = useState(false);

  useEffect(() => {
    let isMounted = true;

    dashboardApi
      .getDashboard()
      .then((data) => {
        if (isMounted) {
          setDashboardData(data);
          setError(null);
        }
      })
      .catch((err) => {
        if (!isMounted) return;
        if (err.response) {
          const { status, data } = err.response;
          if (status === 401) {
            setError('Session expired. Please log in again.');
          } else if (status === 403) {
            setError(data?.message || 'Access denied.');
          } else if (status === 429) {
            setError(data?.message || 'Too many requests. Please try again shortly.');
          } else {
            setError(data?.message || 'Unable to load dashboard details.');
          }
        } else {
          setError('Network error. Please check your internet connection.');
        }
      })
      .finally(() => {
        if (isMounted) {
          setIsLoading(false);
        }
      });

    return () => {
      isMounted = false;
    };
  }, []);

  const handleRetry = () => {
    setIsLoading(true);
    setError(null);
    dashboardApi
      .getDashboard()
      .then((data) => {
        setDashboardData(data);
      })
      .catch((err) => {
        setError(err.response?.data?.message || 'Unable to load dashboard details.');
      })
      .finally(() => {
        setIsLoading(false);
      });
  };

  const currentMember = dashboardData?.member || user;

  // Evaluate whether to show the Missed Introducer Reminder Popup
  useEffect(() => {
    if (!isLoading && currentMember) {
      const sessionKey = `dismissed_introducer_reminder_${currentMember.user_id || currentMember.id}`;
      const alreadyDismissed = sessionStorage.getItem(sessionKey) === 'true';
      const hasNoIntroducer =
        currentMember.introducer_id === null ||
        currentMember.introducer_id === undefined ||
        currentMember.introducer_id === '';

      if (hasNoIntroducer && !alreadyDismissed) {
        setIsReminderOpen(true);
      } else {
        setIsReminderOpen(false);
      }
    } else if (!isLoading && !currentMember) {
      setIsReminderOpen(false);
    }
  }, [isLoading, currentMember]);

  const handleCloseReminder = () => {
    if (currentMember) {
      const sessionKey = `dismissed_introducer_reminder_${currentMember.user_id || currentMember.id}`;
      sessionStorage.setItem(sessionKey, 'true');
    }
    setIsReminderOpen(false);
  };

  const handleAddIntroducer = () => {
    if (currentMember) {
      const sessionKey = `dismissed_introducer_reminder_${currentMember.user_id || currentMember.id}`;
      sessionStorage.setItem(sessionKey, 'true');
    }
    setIsReminderOpen(false);
    navigate('/member/account/settings');
  };

  return (
    <>
      <main className="member-main home-landing" id="home-landing">
        {isLoading ? (
          <DashboardSkeleton />
        ) : error ? (
          <div className="home-container">
            <div className="card home-section" role="alert" style={{ textAlign: 'center', padding: '40px 20px' }}>
              <h2 style={{ fontSize: '1.25rem', color: 'var(--color-danger, #ef4444)', marginBottom: '8px' }}>
                Dashboard Unavailable
              </h2>
              <p style={{ color: 'var(--color-text-secondary)', marginBottom: '18px' }}>{error}</p>
              <button
                type="button"
                className="member-button member-button--primary"
                onClick={handleRetry}
                style={{ margin: '0 auto' }}
              >
                Retry
              </button>
            </div>
          </div>
        ) : (
          <div className="home-container">
            {/* Section 1 — Hero */}
            <HomeHero memberName={currentMember?.name} />

            {/* Section 2 — What is MLM Book? */}
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
          </div>
        )}
      </main>

      {/* Missed Introducer Reminder Modal */}
      <MissedIntroducerReminderModal
        isOpen={isReminderOpen}
        onClose={handleCloseReminder}
        onAddIntroducer={handleAddIntroducer}
      />
    </>
  );
}

export default DashboardPage;
