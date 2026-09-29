export function HomeSkeleton() {
  return (
    <div className="home-container" aria-busy="true" aria-label="Loading Home...">
      <section
        className="card home-hero"
        style={{
          minHeight: '220px',
          display: 'flex',
          flexDirection: 'column',
          justifyContent: 'center',
          gap: '12px',
          padding: '32px 28px',
        }}
      >
        <div style={{ width: '200px', height: '24px', background: 'rgba(59, 130, 246, 0.12)', borderRadius: '20px' }} />
        <div style={{ width: '55%', height: '32px', background: 'var(--color-border, #e2e8f0)', borderRadius: '8px' }} />
        <div style={{ width: '85%', height: '16px', background: 'var(--color-border-soft, #f1f5f9)', borderRadius: '6px' }} />
        <div style={{ width: '70%', height: '16px', background: 'var(--color-border-soft, #f1f5f9)', borderRadius: '6px' }} />
        <div style={{ display: 'flex', gap: '12px', marginTop: '8px' }}>
          <div style={{ width: '140px', height: '40px', background: 'rgba(59, 130, 246, 0.2)', borderRadius: '10px' }} />
          <div style={{ width: '120px', height: '40px', background: 'var(--color-border, #e2e8f0)', borderRadius: '10px' }} />
        </div>
      </section>

      <section className="card home-section" style={{ minHeight: '240px', padding: '28px' }}>
        <div style={{ width: '240px', height: '22px', background: 'var(--color-border, #e2e8f0)', borderRadius: '6px', marginBottom: '20px' }} />
        <div className="home-video-layout" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '20px' }}>
          <div style={{ height: '190px', background: 'var(--color-surface-alt, #f8fafc)', borderRadius: '14px', border: '1px solid #f1f5f9' }} />
          <div style={{ height: '190px', background: 'var(--color-surface-alt, #f8fafc)', borderRadius: '14px', border: '1px solid #f1f5f9' }} />
        </div>
      </section>

      <section className="card home-section" style={{ minHeight: '200px', padding: '28px' }}>
        <div style={{ width: '220px', height: '22px', background: 'var(--color-border, #e2e8f0)', borderRadius: '6px', marginBottom: '20px' }} />
        <div className="home-grid home-grid--2" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(260px, 1fr))', gap: '18px' }}>
          <div style={{ height: '120px', background: 'var(--color-surface-alt, #f8fafc)', borderRadius: '12px', border: '1px solid #f1f5f9' }} />
          <div style={{ height: '120px', background: 'var(--color-surface-alt, #f8fafc)', borderRadius: '12px', border: '1px solid #f1f5f9' }} />
        </div>
      </section>

      <section className="card home-section" style={{ minHeight: '280px', padding: '28px' }}>
        <div style={{ width: '240px', height: '22px', background: 'var(--color-border, #e2e8f0)', borderRadius: '6px', marginBottom: '20px' }} />
        <div className="home-grid home-grid--3" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '16px' }}>
          {[1, 2, 3, 4, 5, 6].map((i) => (
            <div key={i} style={{ height: '130px', background: 'var(--color-surface-alt, #f8fafc)', borderRadius: '12px', border: '1px solid #f1f5f9' }} />
          ))}
        </div>
      </section>
    </div>
  );
}

export default HomeSkeleton;
