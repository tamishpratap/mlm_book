import { useAuth } from '../../hooks/useAuth';

/**
 * Permission-Aware Wrapper Component (UX Layer Only)
 * Renders children if current authenticated user holds the required permission or role.
 * Note: Real authorization remains strictly on the Laravel backend.
 */
export function Can({ permission, role, fallback = null, children }) {
  const { hasPermission, hasRole } = useAuth();

  if (permission && !hasPermission(permission)) {
    return fallback;
  }

  if (role && !hasRole(role)) {
    return fallback;
  }

  return children;
}

export default Can;
