import { useState, useEffect, useContext } from 'react';
import { Link } from 'react-router-dom';
import { BrandingContext } from '../../context/brandingContextDef';
import publicApi from '../../api/publicApi';
import { 
  Mail, 
  Phone, 
  MapPin, 
  Send, 
  ShieldCheck,
  Zap,
  Globe
} from 'lucide-react';
import {
  TelegramIcon,
  TwitterXIcon,
  FacebookIcon,
  LinkedinIcon,
  YoutubeIcon,
  InstagramIcon,
} from '../../components/common/SocialIcons';
import { OFFICIAL_SOCIAL_LINKS } from '../../constants/socialLinks';

export function PublicFooter() {
  const { logoUrl, siteName, siteDescription } = useContext(BrandingContext) || {};
  const [contactData, setContactData] = useState({
    company_name: 'MLM Book Enterprise',
    support_email: 'support@mlmbook.com',
    phone: '+1 (800) 123-4567',
    address: '123 Enterprise Way, Suite 500, Tech City',
    social_telegram: OFFICIAL_SOCIAL_LINKS.telegram,
    social_twitter: OFFICIAL_SOCIAL_LINKS.twitter,
    social_facebook: OFFICIAL_SOCIAL_LINKS.facebook,
    social_linkedin: OFFICIAL_SOCIAL_LINKS.linkedin,
    social_youtube: OFFICIAL_SOCIAL_LINKS.youtube,
    social_instagram: OFFICIAL_SOCIAL_LINKS.instagram,
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

  const telegramUrl = contactData.social_telegram || OFFICIAL_SOCIAL_LINKS.telegram;
  const twitterUrl = contactData.social_twitter || OFFICIAL_SOCIAL_LINKS.twitter;
  const facebookUrl = OFFICIAL_SOCIAL_LINKS.facebook;
  const linkedinUrl = contactData.social_linkedin || OFFICIAL_SOCIAL_LINKS.linkedin;
  const youtubeUrl = contactData.social_youtube || OFFICIAL_SOCIAL_LINKS.youtube;
  const instagramUrl = OFFICIAL_SOCIAL_LINKS.instagram;

  return (
    <footer className="pub-footer">
      <div className="pub-container">
        <div className="pub-footer-grid">
          {/* Column 1: Brand Info */}
          <div>
            <Link to="/" className="pub-logo-link" style={{ marginBottom: '16px' }}>
              {logoUrl ? (
                <img 
                  src={logoUrl} 
                  alt={siteName || 'MLM Book'} 
                  className="pub-logo-img"
                  onError={(e) => { e.currentTarget.style.display = 'none'; }}
                />
              ) : null}
              <span className="pub-logo-text">{siteName || 'MLM Book'}</span>
            </Link>
            
            <p style={{ color: '#94a3b8', fontSize: '0.9rem', lineHeight: '1.6', maxWidth: '340px', margin: '14px 0 20px' }}>
              {siteDescription || 'Next-generation decentralized social network, business directories, and multi-tier reward ecosystem built for creators, businesses, and network builders.'}
            </p>

            <div className="pub-social-row">
              {telegramUrl && (
                <a href={telegramUrl} target="_blank" rel="noopener noreferrer" className="pub-social-btn" aria-label="Telegram">
                  <TelegramIcon className="w-4 h-4" />
                </a>
              )}
              {twitterUrl && (
                <a href={twitterUrl} target="_blank" rel="noopener noreferrer" className="pub-social-btn" aria-label="Twitter / X">
                  <TwitterXIcon className="w-4 h-4" />
                </a>
              )}
              <a href={facebookUrl} target="_blank" rel="noopener noreferrer" className="pub-social-btn" aria-label="Facebook">
                <FacebookIcon className="w-4 h-4" />
              </a>
              {linkedinUrl && (
                <a href={linkedinUrl} target="_blank" rel="noopener noreferrer" className="pub-social-btn" aria-label="LinkedIn">
                  <LinkedinIcon className="w-4 h-4" />
                </a>
              )}
              {youtubeUrl && (
                <a href={youtubeUrl} target="_blank" rel="noopener noreferrer" className="pub-social-btn" aria-label="YouTube">
                  <YoutubeIcon className="w-4 h-4" />
                </a>
              )}
              <a href={instagramUrl} target="_blank" rel="noopener noreferrer" className="pub-social-btn" aria-label="Instagram">
                <InstagramIcon className="w-4 h-4" />
              </a>
            </div>
          </div>

          {/* Column 2: Platform Navigation */}
          <div>
            <h4 className="pub-footer-title">Platform</h4>
            <ul className="pub-footer-links">
              <li><Link to="/" className="pub-footer-link">Home Overview</Link></li>
              <li><Link to="/ecosystem" className="pub-footer-link">Ecosystem Architecture</Link></li>
              <li><Link to="/rewards" className="pub-footer-link">Reward Mechanics</Link></li>
              <li><Link to="/contact" className="pub-footer-link">Contact Support</Link></li>
            </ul>
          </div>

          {/* Column 3: Member Hub */}
          <div>
            <h4 className="pub-footer-title">Members</h4>
            <ul className="pub-footer-links">
              <li><Link to="/member/login" className="pub-footer-link">Member Sign In</Link></li>
              <li><Link to="/member/register" className="pub-footer-link">Create Account</Link></li>
              <li><Link to="/member/forgot-password" className="pub-footer-link">Forgot Password</Link></li>
              <li><Link to="/rewards" className="pub-footer-link">Earning Calculator</Link></li>
            </ul>
          </div>

          {/* Column 4: Dynamic Contact Snippet */}
          <div>
            <h4 className="pub-footer-title">Get in Touch</h4>
            <ul className="pub-footer-links">
              <li style={{ display: 'flex', alignItems: 'flex-start', gap: '10px', color: '#94a3b8', fontSize: '0.88rem' }}>
                <Mail className="w-4 h-4 text-indigo-400 mt-1 flex-shrink-0" />
                <a href={`mailto:${contactData.support_email}`} style={{ color: 'inherit', textDecoration: 'none' }}>
                  {contactData.support_email}
                </a>
              </li>
              {contactData.phone && (
                <li style={{ display: 'flex', alignItems: 'flex-start', gap: '10px', color: '#94a3b8', fontSize: '0.88rem' }}>
                  <Phone className="w-4 h-4 text-emerald-400 mt-1 flex-shrink-0" />
                  <a href={`tel:${contactData.phone}`} style={{ color: 'inherit', textDecoration: 'none' }}>
                    {contactData.phone}
                  </a>
                </li>
              )}
              {contactData.address && (
                <li style={{ display: 'flex', alignItems: 'flex-start', gap: '10px', color: '#94a3b8', fontSize: '0.88rem' }}>
                  <MapPin className="w-4 h-4 text-cyan-400 mt-1 flex-shrink-0" />
                  <span>{contactData.address}</span>
                </li>
              )}
            </ul>
          </div>
        </div>

        {/* Footer Bottom Strip */}
        <div className="pub-footer-bottom">
          <div>
            © {new Date().getFullYear()} {contactData.company_name || siteName || 'MLM Book'}. All rights reserved.
          </div>
          <div style={{ display: 'flex', alignItems: 'center', gap: '20px' }}>
            <span style={{ display: 'inline-flex', alignItems: 'center', gap: '6px' }}>
              <ShieldCheck className="w-4 h-4 text-emerald-400" />
              <span>SSL 256-Bit Encrypted</span>
            </span>
            <span style={{ display: 'inline-flex', alignItems: 'center', gap: '6px' }}>
              <Zap className="w-4 h-4 text-amber-400" />
              <span>Web3 Payout Ready</span>
            </span>
          </div>
        </div>
      </div>
    </footer>
  );
}

export default PublicFooter;
