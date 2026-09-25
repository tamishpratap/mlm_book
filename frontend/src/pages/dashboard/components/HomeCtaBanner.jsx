import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Sparkles, ArrowRight, Rss, Building2, CalendarDays, UserPlus } from 'lucide-react';
import useAuth from '../../../hooks/useAuth';
import AccountVerificationModal from '../../../components/verification/AccountVerificationModal';
import { isMemberMobileVerified } from '../../../utils/whatsappVerification';

export function HomeCtaBanner() {
  const navigate = useNavigate();
  const { user: currentUser } = useAuth();
  const isVerified = isMemberMobileVerified(currentUser);
  const [showVerifyModal, setShowVerifyModal] = useState(false);

  const handleCreateBusinessClick = (e) => {
    if (!isVerified) {
      e.preventDefault();
      setShowVerifyModal(true);
    }
  };

  const joinLink = currentUser ? '/member/socials' : '/member/register';

  return (
    <section className="card home-cta-banner-v2" aria-label="Join MLM Book Ecosystem">
      <div className="home-cta-banner-v2__glow" aria-hidden="true" />

      <div className="home-cta-banner-v2__content">
        <div className="home-cta-banner-v2__badge">
          <Sparkles size={14} aria-hidden="true" />
          <span>Start Your Digital Journey Today</span>
        </div>

        <h2 className="home-cta-banner-v2__title">
          Ready to Build Your Network and Grow Your Digital Presence?
        </h2>

        <p className="home-cta-banner-v2__desc">
          Join MLM Book, connect with your community, build your brand and explore the platform.
        </p>

        <div className="home-cta-banner-v2__actions">
          <Link className="member-button cta-primary-btn" to={joinLink}>
            <UserPlus size={16} aria-hidden="true" />
            <span>Join MLM Book</span>
            <ArrowRight size={15} aria-hidden="true" />
          </Link>

          <Link className="member-button cta-secondary-btn" to="/member/socials">
            <Rss size={16} aria-hidden="true" />
            <span>Explore Socials</span>
          </Link>

          <Link
            className="member-button cta-secondary-btn"
            to="/member/business-pages/create"
            onClick={handleCreateBusinessClick}
          >
            <Building2 size={16} aria-hidden="true" />
            <span>Create Business Page</span>
          </Link>

          {/* Discover Events - Temporarily Disabled */}
          {/*
          <Link className="member-button cta-secondary-btn" to="/member/events">
            <CalendarDays size={16} aria-hidden="true" />
            <span>Discover Events</span>
          </Link>
          */}
        </div>
      </div>

      {showVerifyModal && (
        <AccountVerificationModal
          isOpen={showVerifyModal}
          promptMessage="Please verify your mobile number through WhatsApp before creating a business page."
          onClose={() => setShowVerifyModal(false)}
          onVerified={() => {
            setShowVerifyModal(false);
            navigate('/member/business-pages/create');
          }}
        />
      )}
    </section>
  );
}

export default HomeCtaBanner;
