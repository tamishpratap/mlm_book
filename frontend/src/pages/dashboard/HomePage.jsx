import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import useAuth from '../../hooks/useAuth';
import dashboardApi from '../../api/dashboardApi';

// Essential Subcomponents for Streamlined Home Experience
import HomeHero from './components/HomeHero';
import HomeVideoHub from './components/HomeVideoHub';
import HomeCapabilities from './components/HomeCapabilities';
import HomeCtaBanner from './components/HomeCtaBanner';
import HomeSkeleton from './components/HomeSkeleton';
import MissedIntroducerReminderModal from './components/MissedIntroducerReminderModal';

export function HomePage() {
  const navigate = useNavigate();
  const { user } = useAuth();
  const [dashboardData, setDashboardData] = useState(null);
  const [isLoading, setIsLoading] = useState(!user);
  const [error, setError] = useState(null);
  const [isReminderOpen, setIsReminderOpen] = useState(false);

  useEffect(() => {
    document.title = 'MLM Book - The Next-Generation Digital Social & Business Ecosystem';
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
            setError(data?.message || 'Unable to load home details.');
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
        setError(err.response?.data?.message || 'Unable to load home details.');
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
          <HomeSkeleton />
        ) : error && !currentMember ? (
          <div className="home-container">
            <div className="card home-section" role="alert" style={{ textAlign: 'center', padding: '40px 20px' }}>
              <h2 style={{ fontSize: '1.25rem', color: 'var(--color-danger, #ef4444)', marginBottom: '8px' }}>
                Home Unavailable
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
            {/* 1. Hero Introduction */}
            <HomeHero memberName={currentMember?.name} />

            {/* 2. Interactive Video Learning Center & Official Message */}
            <HomeVideoHub />

            {/* 3. Core Platform Capabilities & Modules */}
            <HomeCapabilities />

            {/* 4. Action & Community Next Steps */}
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

export default HomePage;
