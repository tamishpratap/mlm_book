import { Compass, Target, UsersRound, Globe, Award, ShieldCheck } from 'lucide-react';

export function HomePurposeVision() {
  return (
    <section className="card home-section home-purpose-section" aria-labelledby="purpose-heading">
      <div className="home-section__header" style={{ marginBottom: '24px' }}>
        <div className="home-hub-badge home-hub-badge--indigo">
          <Compass size={13} aria-hidden="true" />
          <span>Our Foundation & Mission</span>
        </div>
        <h2 className="home-section__title" id="purpose-heading" style={{ fontSize: '1.35rem' }}>
          <Target size={22} aria-hidden="true" />
          <span>About MLM Book: Mission & Global Community</span>
        </h2>
        <p className="home-section__desc">
          Empowering individuals, creators, and business leaders with a dedicated digital ecosystem built on trust, transparency, and collaboration.
        </p>
      </div>

      <div className="home-grid home-grid--2">
        {/* Purpose Card */}
        <div className="home-info-card-v2 home-info-card-v2--primary">
          <div className="home-info-card-v2__header">
            <div className="home-info-card-v2__icon home-info-card-v2__icon--primary">
              <Target size={22} aria-hidden="true" />
            </div>
            <span className="home-info-card-v2__tag">Core Mission</span>
          </div>
          <h3 className="home-info-card-v2__title">Our Purpose & Values</h3>
          <p className="home-info-card-v2__desc">
            MLM Book was founded to bridge the divide between authentic social interactions and sustainable professional collaboration. We believe in providing digital creators, enterprise brands, and individuals with the tools to build lasting relationships and grow without algorithmic manipulation or privacy compromises.
          </p>
          <div className="home-info-card-v2__features">
            <div className="home-info-card-v2__item">
              <Award size={16} color="#4f7df3" aria-hidden="true" />
              <span>Built for genuine engagement and constructive networking.</span>
            </div>
            <div className="home-info-card-v2__item">
              <ShieldCheck size={16} color="#4f7df3" aria-hidden="true" />
              <span>Zero compromise on member privacy and verified authenticity.</span>
            </div>
          </div>
        </div>

        {/* Community Card */}
        <div className="home-info-card-v2 home-info-card-v2--success">
          <div className="home-info-card-v2__header">
            <div className="home-info-card-v2__icon home-info-card-v2__icon--success">
              <UsersRound size={22} aria-hidden="true" />
            </div>
            <span className="home-info-card-v2__tag home-info-card-v2__tag--success">Global Ecosystem</span>
          </div>
          <h3 className="home-info-card-v2__title">Our Global Community</h3>
          <p className="home-info-card-v2__desc">
            Our platform connects hundreds of thousands of entrepreneurs, creators, leaders, and consumers worldwide. Whether discovering high-value masterclasses in the Watch Hub, creating an enterprise page, or leading niche groups, our community provides the foundation for mutual success.
          </p>
          <div className="home-info-card-v2__features">
            <div className="home-info-card-v2__item">
              <Globe size={16} color="#20c875" aria-hidden="true" />
              <span>Seamless cross-border communication and localized discovery.</span>
            </div>
            <div className="home-info-card-v2__item">
              <UsersRound size={16} color="#20c875" aria-hidden="true" />
              <span>Vibrant peer-to-peer mentoring and collaborative groups.</span>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

export default HomePurposeVision;
