import { Link } from 'react-router-dom';
import {
  UserCheck,
  Users,
  Share2,
  Building2,
  Megaphone,
  CheckCircle2,
  Gift,
  Wallet,
  ArrowRight,
  Zap,
} from 'lucide-react';

const WORKFLOW_STEPS = [
  {
    step: '01',
    title: 'Create Your Account',
    subtitle: 'Account & Verification',
    icon: UserCheck,
    color: 'blue',
    description: 'Create your member account and complete the required verification.',
    link: '/member/account/verify',
    linkText: 'Account Verification',
  },
  {
    step: '02',
    title: 'Build Your Network',
    subtitle: 'Community & Connections',
    icon: Users,
    color: 'green',
    description: 'Connect with people, discover communities and interact with content.',
    link: '/member/connections',
    linkText: 'Explore Network',
  },
  {
    step: '03',
    title: 'Create & Share',
    subtitle: 'Content Publishing',
    icon: Share2,
    color: 'purple',
    description: 'Publish posts, photos, videos, stories and other supported content.',
    link: '/member/socials',
    linkText: 'Share Content',
  },
  {
    step: '04',
    title: 'Build Your Brand',
    subtitle: 'Business Pages',
    icon: Building2,
    color: 'amber',
    description: 'Create a Business Page and establish your business presence.',
    link: '/member/business-pages/create',
    linkText: 'Create Business Page',
  },
  {
    step: '05',
    title: 'Promote Your Content',
    subtitle: 'Advertising Campaigns',
    icon: Megaphone,
    color: 'blue',
    description: 'Create supported advertising campaigns to promote your content.',
    link: '/member/business-pages',
    linkText: 'Promote Content',
  },
  {
    step: '06',
    title: 'Reach Eligible Members',
    subtitle: 'Targeted Discovery',
    icon: CheckCircle2,
    color: 'green',
    description: 'Campaigns reach members according to the platform’s visibility, approval and eligibility rules.',
    link: '/member/business-pages',
    linkText: 'View Campaigns',
  },
  {
    step: '07',
    title: 'Earn Eligible Rewards',
    subtitle: 'Qualifying Engagement',
    icon: Gift,
    color: 'purple',
    description: 'Eligible verified members may receive rewards after completing qualifying campaign conditions.',
    link: '/member/socials',
    linkText: 'Explore Feed',
  },
  {
    step: '08',
    title: 'Wallet',
    subtitle: 'Credited Balance',
    icon: Wallet,
    color: 'amber',
    description: 'Successfully issued rewards are credited to the member’s Wallet.',
    link: '/member/web3-wallet',
    linkText: 'View Wallet',
  },
];

export function HomeWorkflowSteps() {
  return (
    <section className="card home-section home-workflow-section" aria-labelledby="workflow-heading">
      <div className="home-section__header" style={{ marginBottom: '24px' }}>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '12px' }}>
          <div>
            <div className="home-hub-badge home-hub-badge--green">
              <Zap size={13} aria-hidden="true" />
              <span>Step-by-Step Flow</span>
            </div>
            <h2 className="home-section__title" id="workflow-heading" style={{ fontSize: '1.45rem' }}>
              <Zap size={22} aria-hidden="true" />
              <span>How MLM Book Works</span>
            </h2>
            <p className="home-section__desc">
              From account setup and community networking to brand promotion and earning eligible rewards, follow the complete platform journey.
            </p>
          </div>
        </div>
      </div>

      {/* 8 Step Workflow Grid */}
      <div className="workflow-grid">
        {WORKFLOW_STEPS.map((item) => {
          const IconComponent = item.icon;
          return (
            <div key={item.step} className={`workflow-card workflow-card--${item.color}`}>
              <div className="workflow-card__top">
                <span className="workflow-card__step-num">{item.step}</span>
                <div className={`workflow-card__icon-wrap workflow-card__icon-wrap--${item.color}`}>
                  <IconComponent size={20} aria-hidden="true" />
                </div>
              </div>

              <div className="workflow-card__subtitle">{item.subtitle}</div>
              <h3 className="workflow-card__title">{item.title}</h3>
              <p className="workflow-card__desc">{item.description}</p>

              <Link to={item.link} className="workflow-card__link">
                <span>{item.linkText}</span>
                <ArrowRight size={13} aria-hidden="true" />
              </Link>
            </div>
          );
        })}
      </div>
    </section>
  );
}

export default HomeWorkflowSteps;
