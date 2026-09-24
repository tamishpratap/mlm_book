import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Building2,
  Share2,
  Megaphone,
  Users,
  BarChart3,
  Wallet,
  ArrowRight,
} from 'lucide-react';
import useAuth from '../../../hooks/useAuth';
import AccountVerificationModal from '../../../components/verification/AccountVerificationModal';
import { isMemberMobileVerified } from '../../../utils/whatsappVerification';

const BUSINESS_BENEFITS = [
  {
    title: 'Build Your Business Presence',
    description: 'Create a dedicated Business Page for your brand.',
    icon: Building2,
    badgeColor: 'blue',
  },
  {
    title: 'Publish Content',
    description: 'Share qualifying posts and media with your audience.',
    icon: Share2,
    badgeColor: 'indigo',
  },
  {
    title: 'Promote Campaigns',
    description: 'Use supported advertising campaigns to promote your content.',
    icon: Megaphone,
    badgeColor: 'purple',
  },
  {
    title: 'Reach Members',
    description: 'Connect with eligible members and grow visibility.',
    icon: Users,
    badgeColor: 'green',
  },
  {
    title: 'Track Campaign Activity',
    description: 'Use available campaign information, engagement and analytics.',
    icon: BarChart3,
    badgeColor: 'amber',
  },
  {
    title: 'Manage Campaign Funds',
    description: 'Manage campaign budget and supported funding actions.',
    icon: Wallet,
    badgeColor: 'cyan',
  },
];

export function HomeBusinessBenefits() {
  const navigate = useNavigate();
  const { user: currentUser } = useAuth();
  const isVerified = isMemberMobileVerified(currentUser);
  const [showVerifyModal, setShowVerifyModal] = useState(false);

  const handleCreateBusinessClick = (e) => {
    if (!isVerified) {
      e.preventDefault();
      setShowVerifyModal(true);
    } else {
      navigate('/member/business-pages/create');
    }
  };

  return (
    <section className="card home-section home-business-benefits-section" aria-labelledby="business-benefits-heading">
      <div className="home-section__header" style={{ marginBottom: '24px' }}>
        <div className="home-hub-badge home-hub-badge--purple">
          <Building2 size={13} aria-hidden="true" />
          <span>Business & Enterprise</span>
        </div>
        <h2 className="home-section__title" id="business-benefits-heading" style={{ fontSize: '1.45rem' }}>
          <Building2 size={22} aria-hidden="true" />
          <span>Why Businesses Use MLM Book</span>
        </h2>
        <p className="home-section__desc">
          Powerful tools designed for brands, enterprises, and creators to establish credibility, promote content, and reach targeted audiences.
        </p>
      </div>

      {/* 6 Benefit Cards in 3-Column Grid */}
      <div className="home-grid home-grid--3" style={{ marginBottom: '28px' }}>
        {BUSINESS_BENEFITS.map((item) => {
          const IconComponent = item.icon;
          return (
            <div key={item.title} className="home-feature-card-v2">
              <div className="home-feature-card-v2__top">
                <div className={`home-feature-card-v2__icon home-feature-card-v2__icon--${item.badgeColor}`}>
                  <IconComponent size={22} aria-hidden="true" />
                </div>
              </div>

              <h3 className="home-feature-card-v2__title">{item.title}</h3>
              <p className="home-feature-card-v2__desc">{item.description}</p>
            </div>
          );
        })}
      </div>

      {/* Existing-Style CTA Button */}
      <div style={{ display: 'flex', justifyContent: 'center' }}>
        <button
          type="button"
          className="member-button member-button--primary hero-btn"
          onClick={handleCreateBusinessClick}
          style={{ minHeight: '44px', padding: '10px 24px' }}
        >
          <Building2 size={17} aria-hidden="true" />
          <span>Create Your Business Page</span>
          <ArrowRight size={15} aria-hidden="true" />
        </button>
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

export default HomeBusinessBenefits;
