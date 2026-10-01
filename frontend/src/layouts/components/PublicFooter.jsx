import { useState, useEffect, useContext } from 'react';
import { Link } from 'react-router-dom';
import { BrandingContext } from '../../context/brandingContextDef';
import { BRAND_LOGO } from '../../utils/assetHelper';
import publicApi from '../../api/publicApi';
import { 
  Mail, 
  Phone, 
  ShieldCheck,
  MessageSquare
} from 'lucide-react';
import {
  TelegramIcon,
  FacebookIcon,
  YoutubeIcon,
  InstagramIcon
} from '../../components/common/SocialIcons';

export function PublicFooter() {
  const { logoUrl, siteName, siteDescription } = useContext(BrandingContext) || {};
  const [contactData, setContactData] = useState({
    company_name: 'MLM Book AI',
    support_email: 'support@mlmbook.com',
    phone: '+1 (800) 123-4567',
    social_telegram: 'https://t.me/mlmbook',
    social_facebook: 'https://facebook.com/mlmbook',
    social_youtube: 'https://youtube.com/mlmbook',
    social_instagram: 'https://instagram.com/mlmbook',
  });

  useEffect(() => {
    let mounted = true;
    publicApi.getContactInfo().then((res) => {
      if (mounted && res?.contact) {
        setContactData((prev) => ({
          ...prev,
          ...res.contact,
        }));
      }
    }).catch(() => {});

    return () => {
      mounted = false;
    };
  }, []);

  return (
    <footer className="pub-footer">
      <div className="pub-container">
        <div className="pub-footer-grid">
          {/* Column 1: Brand Info */}
          <div className="pub-footer-brand-col">
            <Link to="/" className="pub-footer-brand" aria-label={siteName || 'MLM Book AI'}>
              <img 
                src={logoUrl || BRAND_LOGO} 
                alt={siteName || 'MLM Book AI'} 
                className="pub-footer-logo-img"
                onError={(e) => {
                  if (e.currentTarget.src !== BRAND_LOGO) {
                    e.currentTarget.src = BRAND_LOGO;
                  }
                }}
              />
            </Link>
            
            <p className="pub-footer-desc">
              {siteDescription || 'Next-generation digital ecosystem connecting creators, advertisers, and community builders through transparent reward mechanics.'}
            </p>

            <div className="pub-social-row">
              {contactData.social_telegram && (
                <a href={contactData.social_telegram} target="_blank" rel="noopener noreferrer" className="pub-social-btn" aria-label="Telegram" title="Telegram">
                  <TelegramIcon className="w-4 h-4" />
                </a>
              )}
              {contactData.social_facebook && (
                <a href={contactData.social_facebook} target="_blank" rel="noopener noreferrer" className="pub-social-btn" aria-label="Facebook" title="Facebook">
                  <FacebookIcon className="w-4 h-4" />
                </a>
              )}
              {contactData.social_youtube && (
                <a href={contactData.social_youtube} target="_blank" rel="noopener noreferrer" className="pub-social-btn" aria-label="YouTube" title="YouTube">
                  <YoutubeIcon className="w-4 h-4" />
                </a>
              )}
              {contactData.social_instagram && (
                <a href={contactData.social_instagram} target="_blank" rel="noopener noreferrer" className="pub-social-btn" aria-label="Instagram" title="Instagram">
                  <InstagramIcon className="w-4 h-4" />
                </a>
              )}
            </div>
          </div>

          {/* Column 2: Platform Navigation */}
          <div>
            <h4 className="pub-footer-title">Platform</h4>
            <ul className="pub-footer-links">
              <li>
                <Link to="/" className="pub-footer-link">Home Overview</Link>
              </li>
              <li>
                <Link to="/ecosystem" className="pub-footer-link">Ecosystem Architecture</Link>
              </li>
              <li>
                <Link to="/rewards" className="pub-footer-link">Reward Mechanics</Link>
              </li>
              <li>
                <Link to="/contact" className="pub-footer-link">Contact Support</Link>
              </li>
            </ul>
          </div>

          {/* Column 3: Member Hub */}
          <div>
            <h4 className="pub-footer-title">Members</h4>
            <ul className="pub-footer-links">
              <li>
                <Link to="/member/login" className="pub-footer-link">Member Sign In</Link>
              </li>
              <li>
                <Link to="/member/register" className="pub-footer-link">Create Account</Link>
              </li>
              <li>
                <Link to="/member/forgot-password" className="pub-footer-link">Forgot Password</Link>
              </li>
              <li>
                <Link to="/rewards" className="pub-footer-link">Reward Calculator</Link>
              </li>
            </ul>
          </div>

          {/* Column 4: Support & Community */}
          <div>
            <h4 className="pub-footer-title">Support & Connect</h4>
            <div className="pub-footer-contact-list">
              {contactData.support_email && (
                <a href={`mailto:${contactData.support_email}`} className="pub-footer-contact-card">
                  <div className="pub-footer-contact-icon pub-footer-contact-icon--mail">
                    <Mail className="w-4 h-4" />
                  </div>
                  <div className="pub-footer-contact-text">
                    <span className="pub-footer-contact-label">Email Support</span>
                    <span className="pub-footer-contact-val">{contactData.support_email}</span>
                  </div>
                </a>
              )}
              {contactData.phone && (
                <a href={`tel:${contactData.phone}`} className="pub-footer-contact-card">
                  <div className="pub-footer-contact-icon pub-footer-contact-icon--phone">
                    <Phone className="w-4 h-4" />
                  </div>
                  <div className="pub-footer-contact-text">
                    <span className="pub-footer-contact-label">Help Desk</span>
                    <span className="pub-footer-contact-val">{contactData.phone}</span>
                  </div>
                </a>
              )}
              {contactData.social_telegram && (
                <a href={contactData.social_telegram} target="_blank" rel="noopener noreferrer" className="pub-footer-contact-card">
                  <div className="pub-footer-contact-icon pub-footer-contact-icon--tg">
                    <MessageSquare className="w-4 h-4" />
                  </div>
                  <div className="pub-footer-contact-text">
                    <span className="pub-footer-contact-label">Community Channel</span>
                    <span className="pub-footer-contact-val">Join Official Group</span>
                  </div>
                </a>
              )}
            </div>
          </div>
        </div>

        {/* Footer Bottom Strip */}
        <div className="pub-footer-bottom">
          <div className="pub-footer-bottom-copy">
            © {new Date().getFullYear()} {contactData.company_name || siteName || 'MLM Book AI'}. All rights reserved.
          </div>
          
          <div className="pub-footer-bottom-legal">
            <Link to="/privacy-policy" className="pub-footer-legal-link">Privacy Policy</Link>
            <span className="pub-footer-legal-dot">•</span>
            <Link to="/terms-and-conditions" className="pub-footer-legal-link">Terms & Conditions</Link>
          </div>

          <div className="pub-footer-bottom-security">
            <span className="pub-footer-ssl-badge">
              <ShieldCheck className="w-4 h-4 text-emerald-500" />
              <span>SSL 256-Bit Encrypted</span>
            </span>
          </div>
        </div>
      </div>
    </footer>
  );
}

export default PublicFooter;
