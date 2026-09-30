import { useState, useEffect, useContext } from 'react';
import { BrandingContext } from '../../context/brandingContextDef';
import publicApi from '../../api/publicApi';
import {
  Mail,
  Phone,
  MapPin,
  Clock,
  Send,
  CheckCircle2,
  AlertCircle,
  ChevronDown,
  ChevronUp,
  MessageSquare,
  HelpCircle
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

export function ContactPage() {
  const { siteName } = useContext(BrandingContext) || {};
  const platformName = siteName || 'MLM Book';

  // Dynamic Contact State (Fetched from Backend Settings configured by Admin)
  const [contactData, setContactData] = useState({
    company_name: 'MLM Book Enterprise',
    support_email: 'support@mlmbook.com',
    phone: '+1 (800) 123-4567',
    website: 'https://mlmbook.com',
    address: '123 Enterprise Way, Suite 500, Tech City',
    business_hours: 'Monday - Friday: 9:00 AM - 6:00 PM (UTC)',
    social_telegram: OFFICIAL_SOCIAL_LINKS.telegram,
    social_twitter: OFFICIAL_SOCIAL_LINKS.twitter,
    social_facebook: OFFICIAL_SOCIAL_LINKS.facebook,
    social_linkedin: OFFICIAL_SOCIAL_LINKS.linkedin,
    social_youtube: OFFICIAL_SOCIAL_LINKS.youtube,
    social_instagram: OFFICIAL_SOCIAL_LINKS.instagram,
  });

  const [loadingContact, setLoadingContact] = useState(true);

  // Form State
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    category: 'general',
    subject: '',
    message: '',
  });

  const [submitting, setSubmitting] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');
  const [errorMessage, setErrorMessage] = useState('');

  // FAQ Accordion State
  const [openFaq, setOpenFaq] = useState(null);

  const toggleFaq = (index) => {
    setOpenFaq((prev) => (prev === index ? null : index));
  };

  // Fetch dynamic contact info on mount
  useEffect(() => {
    let isMounted = true;
    publicApi.getContactInfo()
      .then((res) => {
        if (isMounted && res?.contact) {
          setContactData((prev) => ({
            ...prev,
            ...res.contact,
          }));
        }
      })
      .catch(() => {})
      .finally(() => {
        if (isMounted) setLoadingContact(false);
      });

    return () => {
      isMounted = false;
    };
  }, []);

  const handleChange = (field, value) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
    if (errorMessage) setErrorMessage('');
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSuccessMessage('');
    setErrorMessage('');

    if (!formData.name.trim() || !formData.email.trim() || !formData.subject.trim() || !formData.message.trim()) {
      setErrorMessage('Please fill out all required fields (Name, Email, Subject, and Message).');
      return;
    }

    setSubmitting(true);
    try {
      const res = await publicApi.submitContactForm({
        name: formData.name.trim(),
        email: formData.email.trim(),
        phone: formData.phone.trim(),
        subject: `[${formData.category.toUpperCase()}] ${formData.subject.trim()}`,
        message: formData.message.trim(),
      });

      setSuccessMessage(res?.message || 'Thank you! Your message has been sent successfully. Our team will contact you shortly.');
      setFormData({
        name: '',
        email: '',
        phone: '',
        category: 'general',
        subject: '',
        message: '',
      });
    } catch (err) {
      const msg = err?.response?.data?.message || 'Unable to submit your message at this time. Please try again or email us directly.';
      setErrorMessage(msg);
    } finally {
      setSubmitting(false);
    }
  };

  const telegramUrl = contactData.social_telegram || OFFICIAL_SOCIAL_LINKS.telegram;
  const twitterUrl = contactData.social_twitter || OFFICIAL_SOCIAL_LINKS.twitter;
  const facebookUrl = OFFICIAL_SOCIAL_LINKS.facebook;
  const linkedinUrl = contactData.social_linkedin || OFFICIAL_SOCIAL_LINKS.linkedin;
  const youtubeUrl = contactData.social_youtube || OFFICIAL_SOCIAL_LINKS.youtube;
  const instagramUrl = OFFICIAL_SOCIAL_LINKS.instagram;

  const faqs = [
    {
      q: 'How do I register an account on the platform?',
      a: 'Registration is free and straightforward. Click the "Register" button in the top navigation, enter your name, email, and phone number, and specify an Introducer ID if you were sponsored by an existing member.'
    },
    {
      q: 'What is an Introducer ID and is it required?',
      a: 'An Introducer ID is the member username or referral code of the person who invited you. It places you in their genealogy network tree. If you do not have an introducer, you can join directly under the corporate team.'
    },
    {
      q: 'How does the Reward Wallet work and when can I withdraw?',
      a: 'All earnings from ad views, clicks, downline commissions, and rank bonuses accumulate in your Reward Wallet. You can bind your BEP-20 or TRC-20 USDT crypto address in Account Security and request instant on-chain payouts.'
    },
    {
      q: 'How do business owners launch targeted ad campaigns?',
      a: 'Business pages can fund their Fund Wallet via crypto deposits or direct gateways, create targeted ad campaigns specifying daily budgets, PPC rates, and target demographics, and monitor verified real-time audience engagement.'
    },
    {
      q: 'How do I obtain a verified blue check badge for my business page?',
      a: 'Once your business page is created, go to the page settings and submit your official business verification documents. Our compliance team reviews submissions within 24-48 hours.'
    }
  ];

  return (
    <div style={{ padding: '60px 0 80px' }}>
      <div className="pub-container">
        {/* ====================================================================
            1. HEADER
            ==================================================================== */}
        <div className="pub-section-header" style={{ marginBottom: '56px' }}>
          <div className="pub-badge pub-badge-indigo">
            <Mail className="w-4 h-4" />
            <span>24/7 Global Member Support</span>
          </div>
          <h1 className="pub-title-hero" style={{ fontSize: 'clamp(2.25rem, 4.5vw, 3.5rem)' }}>
            We're Here to <span className="pub-title-gradient">Support Your Growth</span>
          </h1>
          <p className="pub-desc-hero" style={{ margin: '0 auto' }}>
            Have questions about business pages, ad campaigns, referral rewards, or account verification? Our dedicated team and community managers are available around the clock.
          </p>
        </div>

        {/* ====================================================================
            2. CONTACT DETAILS & FORM GRID
            ==================================================================== */}
        <div className="pub-contact-grid" style={{ marginBottom: '80px' }}>
          {/* Left Column: Interactive Contact Form */}
          <div className="pub-card" style={{ padding: '36px' }}>
            <div className="pub-card-glow-line"></div>
            
            <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '8px' }}>
              <MessageSquare className="w-5 h-5 text-indigo-600" />
              <h2 style={{ fontSize: '1.4rem', fontWeight: '800', color: '#0f172a', margin: 0 }}>
                Send Us a Message
              </h2>
            </div>
            <p style={{ color: '#64748b', fontSize: '0.9rem', marginBottom: '24px' }}>
              Fill out the form below and an official representative will respond to your email.
            </p>

            {successMessage && (
              <div style={{ background: '#ecfdf5', border: '1px solid #a7f3d0', borderRadius: '12px', padding: '16px', display: 'flex', alignItems: 'flex-start', gap: '12px', marginBottom: '20px' }}>
                <CheckCircle2 className="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" />
                <span style={{ color: '#065f46', fontSize: '0.9rem', lineHeight: '1.5', fontWeight: '500' }}>
                  {successMessage}
                </span>
              </div>
            )}

            {errorMessage && (
              <div style={{ background: '#fef2f2', border: '1px solid #fecaca', borderRadius: '12px', padding: '16px', display: 'flex', alignItems: 'flex-start', gap: '12px', marginBottom: '20px' }}>
                <AlertCircle className="w-5 h-5 text-rose-600 flex-shrink-0 mt-0.5" />
                <span style={{ color: '#991b1b', fontSize: '0.9rem', lineHeight: '1.5', fontWeight: '500' }}>
                  {errorMessage}
                </span>
              </div>
            )}

            <form onSubmit={handleSubmit}>
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: '16px' }}>
                {/* Full Name */}
                <div className="pub-form-group">
                  <label className="pub-label">Your Name *</label>
                  <input
                    type="text"
                    required
                    placeholder="Enter full name"
                    value={formData.name}
                    onChange={(e) => handleChange('name', e.target.value)}
                    className="pub-input"
                    disabled={submitting}
                  />
                </div>

                {/* Email Address */}
                <div className="pub-form-group">
                  <label className="pub-label">Email Address *</label>
                  <input
                    type="email"
                    required
                    placeholder="name@domain.com"
                    value={formData.email}
                    onChange={(e) => handleChange('email', e.target.value)}
                    className="pub-input"
                    disabled={submitting}
                  />
                </div>
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: '16px' }}>
                {/* Phone (Optional) */}
                <div className="pub-form-group">
                  <label className="pub-label">Phone Number (Optional)</label>
                  <input
                    type="text"
                    placeholder="+1 (555) 000-0000"
                    value={formData.phone}
                    onChange={(e) => handleChange('phone', e.target.value)}
                    className="pub-input"
                    disabled={submitting}
                  />
                </div>

                {/* Inquiry Category */}
                <div className="pub-form-group">
                  <label className="pub-label">Inquiry Category *</label>
                  <select
                    value={formData.category}
                    onChange={(e) => handleChange('category', e.target.value)}
                    className="pub-select"
                    disabled={submitting}
                  >
                    <option value="general">General Platform Inquiry</option>
                    <option value="business">Business Page & Verification</option>
                    <option value="advertising">Targeted Ad Campaigns</option>
                    <option value="rewards">Reward Wallet & Withdrawals</option>
                    <option value="technical">Technical Support</option>
                    <option value="partnership">Enterprise Partnership</option>
                  </select>
                </div>
              </div>

              {/* Subject */}
              <div className="pub-form-group">
                <label className="pub-label">Subject *</label>
                <input
                  type="text"
                  required
                  placeholder="Summary of your inquiry"
                  value={formData.subject}
                  onChange={(e) => handleChange('subject', e.target.value)}
                  className="pub-input"
                  disabled={submitting}
                />
              </div>

              {/* Message */}
              <div className="pub-form-group">
                <label className="pub-label">Message Details *</label>
                <textarea
                  required
                  rows={4}
                  placeholder="Please provide details about your inquiry or question..."
                  value={formData.message}
                  onChange={(e) => handleChange('message', e.target.value)}
                  className="pub-textarea"
                  disabled={submitting}
                />
              </div>

              <button
                type="submit"
                disabled={submitting}
                className="pub-btn pub-btn-primary pub-btn-lg"
                style={{ width: '100%', marginTop: '8px' }}
              >
                {submitting ? (
                  <>
                    <span className="spin-icon">⏳</span>
                    <span>Submitting Inquiry...</span>
                  </>
                ) : (
                  <>
                    <Send className="w-5 h-5" />
                    <span>Send Message to Support</span>
                  </>
                )}
              </button>
            </form>
          </div>

          {/* Right Column: Dynamic Contact Cards (from Admin Settings) */}
          <div style={{ display: 'flex', flexDirection: 'column', gap: '20px' }}>
            {/* Dynamic Support Email */}
            <a href={`mailto:${contactData.support_email}`} className="pub-contact-card">
              <div className="pub-icon-wrapper pub-icon-indigo" style={{ width: '48px', height: '48px', marginBottom: 0, flexShrink: 0 }}>
                <Mail className="w-5 h-5" />
              </div>
              <div>
                <div style={{ fontSize: '0.75rem', fontWeight: '700', color: '#4f46e5', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                  Support Email
                </div>
                <div style={{ fontSize: '1.05rem', fontWeight: '800', color: '#0f172a', marginTop: '2px' }}>
                  {contactData.support_email}
                </div>
                <div style={{ fontSize: '0.8rem', color: '#64748b', marginTop: '4px' }}>
                  Official desk replies within 12-24 hours
                </div>
              </div>
            </a>

            {/* Dynamic Phone */}
            {contactData.phone && (
              <a href={`tel:${contactData.phone}`} className="pub-contact-card">
                <div className="pub-icon-wrapper pub-icon-emerald" style={{ width: '48px', height: '48px', marginBottom: 0, flexShrink: 0 }}>
                  <Phone className="w-5 h-5" />
                </div>
                <div>
                  <div style={{ fontSize: '0.75rem', fontWeight: '700', color: '#059669', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                    Phone Hotline
                  </div>
                  <div style={{ fontSize: '1.05rem', fontWeight: '800', color: '#0f172a', marginTop: '2px' }}>
                    {contactData.phone}
                  </div>
                  <div style={{ fontSize: '0.8rem', color: '#64748b', marginTop: '4px' }}>
                    Direct line for member & business escalations
                  </div>
                </div>
              </a>
            )}

            {/* Dynamic Physical Address */}
            {contactData.address && (
              <div className="pub-contact-card">
                <div className="pub-icon-wrapper pub-icon-cyan" style={{ width: '48px', height: '48px', marginBottom: 0, flexShrink: 0 }}>
                  <MapPin className="w-5 h-5" />
                </div>
                <div>
                  <div style={{ fontSize: '0.75rem', fontWeight: '700', color: '#0891b2', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                    Corporate Address
                  </div>
                  <div style={{ fontSize: '0.98rem', fontWeight: '800', color: '#0f172a', marginTop: '2px', lineHeight: '1.4' }}>
                    {contactData.address}
                  </div>
                  <div style={{ fontSize: '0.8rem', color: '#64748b', marginTop: '4px' }}>
                    {contactData.company_name}
                  </div>
                </div>
              </div>
            )}

            {/* Dynamic Business Hours */}
            <div className="pub-contact-card">
              <div className="pub-icon-wrapper pub-icon-amber" style={{ width: '48px', height: '48px', marginBottom: 0, flexShrink: 0 }}>
                <Clock className="w-5 h-5" />
              </div>
              <div>
                <div style={{ fontSize: '0.75rem', fontWeight: '700', color: '#d97706', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                  Operational Hours
                </div>
                <div style={{ fontSize: '0.95rem', fontWeight: '800', color: '#0f172a', marginTop: '2px' }}>
                  {contactData.business_hours}
                </div>
                <div style={{ fontSize: '0.8rem', color: '#64748b', marginTop: '4px' }}>
                  Automated Web3 withdrawals process 24/7/365
                </div>
              </div>
            </div>

            {/* Social Channels Section */}
            <div className="pub-card" style={{ padding: '24px' }}>
              <div style={{ fontSize: '0.95rem', fontWeight: '700', color: '#0f172a', marginBottom: '14px' }}>
                Official Social Channels
              </div>
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
          </div>
        </div>

        {/* ====================================================================
            3. FREQUENTLY ASKED QUESTIONS (FAQ)
            ==================================================================== */}
        <section style={{ maxWidth: '840px', margin: '0 auto' }}>
          <div className="pub-section-header" style={{ marginBottom: '36px' }}>
            <div className="pub-badge pub-badge-cyan">
              <HelpCircle className="w-4 h-4" />
              <span>Instant Answers</span>
            </div>
            <h2 className="pub-section-title">Frequently Asked Questions</h2>
            <p className="pub-section-desc">
              Quick answers to common questions regarding accounts, verification, and reward distributions.
            </p>
          </div>

          <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
            {faqs.map((faq, fIdx) => (
              <div key={fIdx} className="pub-faq-item">
                <button
                  type="button"
                  onClick={() => toggleFaq(fIdx)}
                  className="pub-faq-trigger"
                >
                  <span style={{ color: '#0f172a' }}>{faq.q}</span>
                  {openFaq === fIdx ? (
                    <ChevronUp className="w-5 h-5 text-indigo-600 flex-shrink-0" />
                  ) : (
                    <ChevronDown className="w-5 h-5 text-slate-400 flex-shrink-0" />
                  )}
                </button>

                {openFaq === fIdx && (
                  <div className="pub-faq-body">
                    {faq.a}
                  </div>
                )}
              </div>
            ))}
          </div>
        </section>
      </div>
    </div>
  );
}

export default ContactPage;
