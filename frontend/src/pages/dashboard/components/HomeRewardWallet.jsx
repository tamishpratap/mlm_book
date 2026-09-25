import { Wallet, History, ShieldCheck, ArrowRight } from 'lucide-react';
import { Link } from 'react-router-dom';

export function HomeRewardWallet() {
  return (
    <section className="card home-section home-reward-wallet-section" aria-labelledby="wallet-heading">
      <div className="home-section__header" style={{ marginBottom: '24px' }}>
        <div className="home-hub-badge home-hub-badge--green">
          <Wallet size={13} aria-hidden="true" />
          <span>Reward Accounting</span>
        </div>
        <h2 className="home-section__title" id="wallet-heading" style={{ fontSize: '1.45rem' }}>
          <Wallet size={22} aria-hidden="true" />
          <span>Your Wallet</span>
        </h2>
        <p className="home-section__desc" style={{ fontSize: '14.5px', lineHeight: '1.65' }}>
          Successfully credited campaign rewards are added to your Wallet, where you can view and manage your available wallet balance and reward history.
        </p>
      </div>

      <div className="home-grid home-grid--2">
        <div className="home-info-card-v2">
          <div className="home-info-card-v2__header">
            <div className="home-info-card-v2__icon home-info-card-v2__icon--success">
              <Wallet size={22} aria-hidden="true" />
            </div>
            <span className="home-info-card-v2__tag home-info-card-v2__tag--success">Balance Overview</span>
          </div>
          <h3 className="home-info-card-v2__title">Available Balance Tracking</h3>
          <p className="home-info-card-v2__desc">
            Monitor credited campaign rewards in one centralized ledger. Check your current balances and maintain transparent oversight over all qualifying campaign earnings.
          </p>
          <div style={{ marginTop: 'auto', paddingTop: '16px' }}>
            <Link to="/member/web3-wallet" className="workflow-card__link">
              <span>View Wallet</span>
              <ArrowRight size={13} aria-hidden="true" />
            </Link>
          </div>
        </div>

        <div className="home-info-card-v2">
          <div className="home-info-card-v2__header">
            <div className="home-info-card-v2__icon home-info-card-v2__icon--primary">
              <History size={22} aria-hidden="true" />
            </div>
            <span className="home-info-card-v2__tag">Activity Logs</span>
          </div>
          <h3 className="home-info-card-v2__title">Transparent Reward History</h3>
          <p className="home-info-card-v2__desc">
            Review detailed historical logs for every reward credit, associated campaign ID, completion timestamp, and verification status with complete transparency.
          </p>
          <div style={{ marginTop: 'auto', paddingTop: '16px' }}>
            <div className="home-info-card-v2__item" style={{ fontSize: '13px', color: 'var(--color-text-secondary)' }}>
              <ShieldCheck size={16} color="#4f7df3" aria-hidden="true" />
              <span>Protected by automated duplicate claim prevention rules.</span>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

export default HomeRewardWallet;
