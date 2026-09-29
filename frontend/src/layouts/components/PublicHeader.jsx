import { useState, useContext } from 'react';
import { Link, NavLink } from 'react-router-dom';
import { BrandingContext } from '../../context/brandingContextDef';
import { AuthContext } from '../../context/AuthContext';
import { 
  LogIn, 
  UserPlus, 
  LayoutDashboard, 
  Menu, 
  X, 
  Globe, 
  Layers, 
  Gift, 
  Mail,
  ChevronRight
} from 'lucide-react';

export function PublicHeader() {
  const { logoUrl, siteName } = useContext(BrandingContext) || {};
  const { user, isAuthenticated } = useContext(AuthContext) || {};
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  const closeMobile = () => setMobileMenuOpen(false);

  return (
    <header className="pub-header">
      <div className="pub-container">
        <div className="pub-header-inner">
          {/* Brand Logo */}
          <Link to="/" className="pub-logo-link" onClick={closeMobile}>
            {logoUrl ? (
              <img 
                src={logoUrl} 
                alt={siteName || 'MLM Book'} 
                className="pub-logo-img"
                onError={(e) => {
                  e.currentTarget.style.display = 'none';
                }}
              />
            ) : null}
            <span className="pub-logo-text">{siteName || 'MLM Book'}</span>
          </Link>

          {/* Desktop Navigation Links */}
          <nav className="pub-nav-links" aria-label="Main Navigation">
            <NavLink 
              to="/" 
              end
              className={({ isActive }) => `pub-nav-link ${isActive ? 'active' : ''}`}
            >
              <Globe className="w-4 h-4" />
              <span>Home</span>
            </NavLink>

            <NavLink 
              to="/ecosystem" 
              className={({ isActive }) => `pub-nav-link ${isActive ? 'active' : ''}`}
            >
              <Layers className="w-4 h-4" />
              <span>Ecosystem</span>
            </NavLink>

            <NavLink 
              to="/rewards" 
              className={({ isActive }) => `pub-nav-link ${isActive ? 'active' : ''}`}
            >
              <Gift className="w-4 h-4" />
              <span>Rewards</span>
            </NavLink>

            <NavLink 
              to="/contact" 
              className={({ isActive }) => `pub-nav-link ${isActive ? 'active' : ''}`}
            >
              <Mail className="w-4 h-4" />
              <span>Contact Us</span>
            </NavLink>
          </nav>

          {/* Desktop Auth Actions */}
          <div className="pub-nav-actions">
            {isAuthenticated ? (
              <Link to="/member/home" className="pub-btn pub-btn-primary">
                <LayoutDashboard className="w-4 h-4" />
                <span>Dashboard</span>
                <ChevronRight className="w-4 h-4" />
              </Link>
            ) : (
              <>
                <Link to="/member/login" className="pub-btn pub-btn-ghost">
                  <LogIn className="w-4 h-4" />
                  <span>Member Login</span>
                </Link>
                <Link to="/member/register" className="pub-btn pub-btn-primary">
                  <UserPlus className="w-4 h-4" />
                  <span>Register</span>
                </Link>
              </>
            )}
          </div>

          {/* Mobile Hamburger Toggle */}
          <button 
            type="button" 
            className="pub-mobile-toggle"
            onClick={() => setMobileMenuOpen((prev) => !prev)}
            aria-label="Toggle navigation menu"
          >
            {mobileMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6 text-white" />}
          </button>
        </div>
      </div>

      {/* Mobile Drawer */}
      <div className={`pub-mobile-drawer ${mobileMenuOpen ? 'open' : ''}`}>
        <NavLink 
          to="/" 
          end
          onClick={closeMobile}
          className={({ isActive }) => `pub-nav-link ${isActive ? 'active' : ''}`}
        >
          <Globe className="w-4 h-4" />
          <span>Home</span>
        </NavLink>

        <NavLink 
          to="/ecosystem" 
          onClick={closeMobile}
          className={({ isActive }) => `pub-nav-link ${isActive ? 'active' : ''}`}
        >
          <Layers className="w-4 h-4" />
          <span>Ecosystem</span>
        </NavLink>

        <NavLink 
          to="/rewards" 
          onClick={closeMobile}
          className={({ isActive }) => `pub-nav-link ${isActive ? 'active' : ''}`}
        >
          <Gift className="w-4 h-4" />
          <span>Rewards</span>
        </NavLink>

        <NavLink 
          to="/contact" 
          onClick={closeMobile}
          className={({ isActive }) => `pub-nav-link ${isActive ? 'active' : ''}`}
        >
          <Mail className="w-4 h-4" />
          <span>Contact Us</span>
        </NavLink>

        <div className="pub-mobile-drawer-actions">
          {isAuthenticated ? (
            <Link to="/member/home" onClick={closeMobile} className="pub-btn pub-btn-primary">
              <LayoutDashboard className="w-4 h-4" />
              <span>Go to Member Dashboard</span>
            </Link>
          ) : (
            <>
              <Link to="/member/login" onClick={closeMobile} className="pub-btn pub-btn-outline">
                <LogIn className="w-4 h-4" />
                <span>Member Login</span>
              </Link>
              <Link to="/member/register" onClick={closeMobile} className="pub-btn pub-btn-primary">
                <UserPlus className="w-4 h-4" />
                <span>Join & Register</span>
              </Link>
            </>
          )}
        </div>
      </div>
    </header>
  );
}

export default PublicHeader;
