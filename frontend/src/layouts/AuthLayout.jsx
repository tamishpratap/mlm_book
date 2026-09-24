import { Outlet } from 'react-router-dom';

// Load Auth specific stylesheets
import '../styles/member-login.css';
import '../styles/member-register.css';
import '../styles/member-verify.css';
import '../styles/member-reset.css';

export function AuthLayout() {
  return <Outlet />;
}

export default AuthLayout;
