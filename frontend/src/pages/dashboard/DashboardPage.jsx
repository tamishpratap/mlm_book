import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import useAuth from '../../hooks/useAuth';
import dashboardApi from '../../api/dashboardApi';
import '../../styles/member-dashboard-hub.css';

// Modern Dashboard Components
import MemberWelcomeBanner from './components/MemberWelcomeBanner';
import WalletIntelligenceCard from './components/WalletIntelligenceCard';
import BusinessPagesOverview from './components/BusinessPagesOverview';
import AdCampaignsOverview from './components/AdCampaignsOverview';
import ReferralNetworkCard from './components/ReferralNetworkCard';
import RecentTransactionsCard from './components/RecentTransactionsCard';
import PlatformShortcutsGrid from './components/PlatformShortcutsGrid';
import DashboardSkeleton from './components/DashboardSkeleton';
import MissedIntroducerReminderModal from './components/MissedIntroducerReminderModal';

export function DashboardPage({ embedded = false }) {
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

  const content = (
    <div className="member-dashboard-hub" style={embedded ? { padding: 0 } : undefined}>
      {/* 1. Executive Welcome & Quick Action Header */}
      <MemberWelcomeBanner
        member={currentMember}
        referralNetwork={dashboardData?.referral_network}
      />

      {/* 2. Wallet & Financial Intelligence Center */}
      <WalletIntelligenceCard
        wallets={dashboardData?.wallets}
      />

      {/* 3. Business Pages Command Center */}
      <BusinessPagesOverview
        businessPages={dashboardData?.business_pages}
      />

      {/* 4. Advertising Campaigns Performance */}
      <AdCampaignsOverview
        adCampaigns={dashboardData?.ad_campaigns}
      />

      {/* 5. Referral Network & Rank Status */}
      <ReferralNetworkCard
        referralNetwork={dashboardData?.referral_network}
      />

      {/* 6. Recent Financial Activity (Deposits & Withdrawals) */}
      <RecentTransactionsCard
        transactions={dashboardData?.recent_transactions}
      />

      {/* 7. Quick Navigation Hub */}
      <PlatformShortcutsGrid />
    </div>
  );

  if (embedded) {
    return isLoading ? (
      <DashboardSkeleton />
    ) : error ? (
      <div className="member-dashboard-hub">
        <div className="card" role="alert" style={{ textAlign: 'center', padding: '48px 24px', borderRadius: '16px' }}>
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
      content
    );
  }

  return (
    <>
      <main className="member-main" id="member-dashboard-main">
        {isLoading ? (
          <DashboardSkeleton />
        ) : error ? (
          <div className="member-dashboard-hub">
            <div className="card" role="alert" style={{ textAlign: 'center', padding: '48px 24px', borderRadius: '16px' }}>
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
          content
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
