export function DashboardSkeleton() {
  return (
    <div className="home-container" aria-busy="true" aria-label="Loading dashboard...">
      <section className="card home-hero" style={{ minHeight: '220px', display: 'flex', flexDirection: 'column', justifyContent: 'center' }}>
        <div style={{ width: '180px', height: '26px', background: 'rgba(79, 125, 243, 0.1)', borderRadius: '20px', marginBottom: '14px' }} />
        <div style={{ width: '60%', height: '32px', background: 'var(--color-border)', borderRadius: '8px', marginBottom: '12px' }} />
        <div style={{ width: '90%', height: '18px', background: 'var(--color-border-soft)', borderRadius: '6px', marginBottom: '8px' }} />
        <div style={{ width: '75%', height: '18px', background: 'var(--color-border-soft)', borderRadius: '6px', marginBottom: '20px' }} />
        <div style={{ display: 'flex', gap: '10px' }}>
          <div style={{ width: '130px', height: '40px', background: 'rgba(79, 125, 243, 0.15)', borderRadius: '10px' }} />
          <div style={{ width: '150px', height: '40px', background: 'var(--color-border)', borderRadius: '10px' }} />
        </div>
      </section>

      <section className="card home-section" style={{ minHeight: '260px' }}>
        <div style={{ width: '260px', height: '24px', background: 'var(--color-border)', borderRadius: '6px', marginBottom: '20px' }} />
        <div className="home-video-layout">
          <div style={{ height: '220px', background: 'var(--color-surface-alt)', borderRadius: '14px' }} />
          <div style={{ height: '220px', background: 'var(--color-surface-alt)', borderRadius: '14px' }} />
        </div>
      </section>

      <section className="card home-section" style={{ minHeight: '200px' }}>
        <div style={{ width: '220px', height: '24px', background: 'var(--color-border)', borderRadius: '6px', marginBottom: '20px' }} />
        <div className="home-grid home-grid--2">
          <div style={{ height: '120px', background: 'var(--color-surface-alt)', borderRadius: '12px' }} />
          <div style={{ height: '120px', background: 'var(--color-surface-alt)', borderRadius: '12px' }} />
        </div>
      </section>

      <section className="card home-section" style={{ minHeight: '300px' }}>
        <div style={{ width: '240px', height: '24px', background: 'var(--color-border)', borderRadius: '6px', marginBottom: '20px' }} />
        <div className="home-grid home-grid--3">
          {[1, 2, 3, 4, 5, 6].map((i) => (
            <div key={i} style={{ height: '150px', background: 'var(--color-surface-alt)', borderRadius: '12px' }} />
          ))}
        </div>
      </section>
    </div>
  );
}

export default DashboardSkeleton;
