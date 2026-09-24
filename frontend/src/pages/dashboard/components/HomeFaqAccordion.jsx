import { useState } from 'react';
import { ChevronDown, HelpCircle, MessageCircleQuestion } from 'lucide-react';

const FAQ_DATA = [
  {
    id: 'faq-1',
    question: 'What is MLM Book?',
    answer:
      'MLM Book is a connected social and digital business ecosystem combining networking, content, communities, events, businesses, advertising and supported reward programs.',
  },
  {
    id: 'faq-2',
    question: 'What can I do on MLM Book?',
    answer:
      'You can connect with people, create content, join communities, discover events, use business features, explore videos and participate in supported campaign experiences.',
  },
  {
    id: 'faq-3',
    question: 'How does advertising work?',
    answer:
      'Businesses can create supported campaigns, set budgets and follow the existing review, approval and activation process.',
  },
  {
    id: 'faq-4',
    question: 'How can members earn rewards?',
    answer:
      'Eligible verified members may receive rewards when they complete qualifying conditions defined by an active supported campaign.',
  },
  {
    id: 'faq-5',
    question: 'Is mobile/WhatsApp verification required?',
    answer:
      'Supported reward participation requires the applicable mobile/WhatsApp verification.',
  },
  {
    id: 'faq-6',
    question: 'How is the reward amount determined?',
    answer:
      'Reward amounts depend on the active campaign reward rules and the member’s eligibility.',
  },
  {
    id: 'faq-7',
    question: 'Can a campaign owner earn from their own campaign?',
    answer:
      'No. The campaign owner is not eligible to receive rewards from their own campaign.',
  },
  {
    id: 'faq-8',
    question: 'Can the same campaign reward be claimed twice?',
    answer:
      'No. The existing reward system protects against duplicate reward claims.',
  },
  {
    id: 'faq-9',
    question: 'What happens when a campaign budget is exhausted?',
    answer:
      'Reward availability follows the campaign’s configured budget and existing reward rules.',
  },
];

export function HomeFaqAccordion() {
  const [openId, setOpenId] = useState('faq-1');

  const toggleAccordion = (id) => {
    setOpenId((prev) => (prev === id ? null : id));
  };

  return (
    <section className="card home-section home-faq-section" aria-labelledby="faq-heading">
      <div className="home-section__header" style={{ marginBottom: '24px' }}>
        <div className="home-hub-badge home-hub-badge--purple">
          <HelpCircle size={13} aria-hidden="true" />
          <span>Knowledge & Help Center</span>
        </div>
        <h2 className="home-section__title" id="faq-heading" style={{ fontSize: '1.45rem' }}>
          <MessageCircleQuestion size={22} aria-hidden="true" />
          <span>Frequently Asked Questions</span>
        </h2>
        <p className="home-section__desc">
          Find clear answers to common questions about MLM Book, accounts, advertising, and campaign rewards.
        </p>
      </div>

      <div className="faq-list">
        {FAQ_DATA.map((faq) => {
          const isOpen = openId === faq.id;
          return (
            <div key={faq.id} className={`faq-item ${isOpen ? 'is-open' : ''}`}>
              <button
                type="button"
                className="faq-question-btn"
                onClick={() => toggleAccordion(faq.id)}
                aria-expanded={isOpen}
                aria-controls={`faq-answer-${faq.id}`}
              >
                <span className="faq-question-text">{faq.question}</span>
                <span className="faq-icon-wrap">
                  <ChevronDown size={18} className="faq-chevron" aria-hidden="true" />
                </span>
              </button>

              {isOpen && (
                <div id={`faq-answer-${faq.id}`} className="faq-answer-body" role="region">
                  <p>{faq.answer}</p>
                </div>
              )}
            </div>
          );
        })}
      </div>
    </section>
  );
}

export default HomeFaqAccordion;
