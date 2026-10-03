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
import { OFFICIAL_SOCIAL_LINKS } from '../../constants/socialLinks';

const OFFICIAL_FACEBOOK_URL = 'https://www.facebook.com/profile.php?id=61593794263511';
const OFFICIAL_YOUTUBE_URL = 'https://www.youtube.com/@MLMBookAIOfficail';
const OFFICIAL_INSTAGRAM_URL = 'https://www.instagram.com/mlmbookai/';
const OFFICIAL_SUPPORT_EMAIL = 'support@mlmbookai.com';
const OFFICIAL_HELP_DESK = '+44 7472962940';
const OFFICIAL_HELP_DESK_TEL = '+447472962940';
const OFFICIAL_TELEGRAM_LINK = 'https://t.me/+447473962940';

export function PublicFooter() {
  const { logoUrl, siteName, siteDescription } = useContext(BrandingContext) || {};
  const [contactData, setContactData] = useState({
    company_name: 'MLM Book AI',
    support_email: OFFICIAL_SUPPORT_EMAIL,
    phone: OFFICIAL_HELP_DESK,
    social_telegram: OFFICIAL_SOCIAL_LINKS.telegram || 'https://t.me/mlmbook',
    social_facebook: OFFICIAL_FACEBOOK_URL,
    social_youtube: OFFICIAL_YOUTUBE_URL,
    social_instagram: OFFICIAL_INSTAGRAM_URL,
  });

  useEffect(() => {
    let mounted = true;
    publicApi.getContactInfo().then((res) => {
      if (mounted && res?.contact) {
        setContactData((prev) => ({
          ...prev,
          ...res.contact,
          company_name: 'MLM Book AI',
          support_email: OFFICIAL_SUPPORT_EMAIL,
          phone: OFFICIAL_HELP_DESK,
          social_facebook: OFFICIAL_FACEBOOK_URL,
          social_youtube: OFFICIAL_YOUTUBE_URL,
          social_instagram: OFFICIAL_INSTAGRAM_URL,
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
              <a href={OFFICIAL_TELEGRAM_LINK} target="_blank" rel="noopener noreferrer" className="pub-social-btn" aria-label="Telegram" title="Telegram">
                <TelegramIcon className="w-4 h-4" />
              </a>
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
                <Link to="/" className="pub-footer-link" onClick={() => window.scrollTo(0, 0)}>Home Overview</Link>
              </li>
              <li>
                <Link to="/ecosystem" className="pub-footer-link" onClick={() => window.scrollTo(0, 0)}>Ecosystem Architecture</Link>
              </li>
              <li>
                <Link to="/rewards" className="pub-footer-link" onClick={() => window.scrollTo(0, 0)}>Reward Mechanics</Link>
              </li>
              <li>
                <Link to="/contact" className="pub-footer-link" onClick={() => window.scrollTo(0, 0)}>Contact Support</Link>
              </li>
            </ul>
          </div>

          {/* Column 3: Member Hub */}
          <div>
            <h4 className="pub-footer-title">Members</h4>
            <ul className="pub-footer-links">
              <li>
                <Link to="/member/login" className="pub-footer-link" onClick={() => window.scrollTo(0, 0)}>Member Sign In</Link>
              </li>
              <li>
                <Link to="/member/register" className="pub-footer-link" onClick={() => window.scrollTo(0, 0)}>Create Account</Link>
              </li>
              <li>
                <Link to="/member/forgot-password" className="pub-footer-link" onClick={() => window.scrollTo(0, 0)}>Forgot Password</Link>
              </li>
              <li>
                <Link 
                  to="/rewards#calculator" 
                  className="pub-footer-link"
                  onClick={() => {
                    if (window.location.pathname === '/rewards') {
                      const el = document.getElementById('calculator');
                      if (el) el.scrollIntoView({ behavior: 'smooth' });
                    }
                  }}
                >
                  Reward Calculator
                </Link>
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
                <a href={`tel:${OFFICIAL_HELP_DESK_TEL}`} className="pub-footer-contact-card">
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

        {/* Footer Disclaimer Notice */}
        <div className="pub-footer-disclaimer">
          <p className="pub-footer-disclaimer-text">
            <strong>Disclaimer:</strong> MLMBook AI is a networking and business connectivity platform only. We do not provide investment, financial, legal, or business advice, nor do we accept or manage investments. Any interaction, business opportunity, investment, or transaction undertaken through the platform is solely at the user&apos;s own discretion and risk. MLMBook AI shall not be responsible for any loss, damage, or dispute arising from such activities.
          </p>
        </div>

        {/* Footer Bottom Strip */}
        <div className="pub-footer-bottom">
          <div className="pub-footer-bottom-copy">
            © {new Date().getFullYear()} MLM Book AI
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
