import { Outlet } from 'react-router-dom';
import PublicHeader from './components/PublicHeader';
import PublicFooter from './components/PublicFooter';
import '../styles/public-pages.css';

export function PublicLayout() {
  return (
    <div className="pub-shell">
      <PublicHeader />
      <main style={{ flex: 1, position: 'relative', zIndex: 1 }}>
        <Outlet />
      </main>
      <PublicFooter />
    </div>
  );
}

export default PublicLayout;
