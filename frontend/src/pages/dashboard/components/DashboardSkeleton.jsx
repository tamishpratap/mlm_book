import { Megaphone, Wallet, Sparkles } from 'lucide-react';

export function DashboardSkeleton() {
  return (
    <div className="member-dashboard-hub" aria-busy="true" aria-label="Loading Member Dashboard...">
      {/* High-Tech Central Executive Loader Card */}
      <div className="dash-loader-wrapper">
        <div className="dash-loader-card">
          <div className="dash-spinner-orbit">
            <div className="dash-spinner-ring-outer" />
            <div className="dash-spinner-ring-inner" />
            <div className="dash-spinner-core-icon">
              <Megaphone size={20} color="#38bdf8" />
            </div>
          </div>

          <h3 className="dash-loader-title">
            Loading Dashboard...
          </h3>

          <p className="dash-loader-subtitle">
            Synchronizing fund balances, active ad campaigns, and business page analytics.
          </p>

          <div className="dash-loader-pill">
            <span className="dash-loader-dot-pulse" />
            <span>Fetching Live Account Data</span>
          </div>
        </div>
      </div>

      {/* Synchronized Shimmer Skeleton Layout Below */}
      <div style={{ opacity: 0.65, display: 'flex', flexDirection: 'column', gap: '20px', pointerEvents: 'none' }}>
        {/* Banner Skeleton */}
        <div className="dash-skeleton-box" style={{ height: '140px', borderRadius: '20px' }} />

        {/* 4 Wallet Cards Skeleton Grid */}
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(240px, 1fr))', gap: '16px' }}>
          {[1, 2, 3, 4].map((i) => (
            <div key={i} className="dash-skeleton-box" style={{ height: '150px', borderRadius: '16px' }} />
          ))}
        </div>

        {/* Business Pages Skeleton Grid */}
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(280px, 1fr))', gap: '18px' }}>
          {[1, 2, 3].map((i) => (
            <div key={i} className="dash-skeleton-box" style={{ height: '220px', borderRadius: '16px' }} />
          ))}
        </div>
      </div>
    </div>
  );
}

export default DashboardSkeleton;
