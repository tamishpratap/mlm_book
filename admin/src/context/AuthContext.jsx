import { useState, useEffect, useCallback, useRef } from 'react';
import { authApi } from '../api';
import { useToast } from '../hooks/useToast';
import { AuthContext } from './authContextDef';
import { resetNotificationSoundState } from '../utils/notificationSound';

const ADMIN_STORAGE_KEY = 'mlm_admin_session_active';

export function AuthProvider({ children }) {
  const [admin, setAdmin] = useState(null);
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [loading, setLoading] = useState(true);
  const { showError, showInfo } = useToast();

  const isAuthenticatedRef = useRef(false);
  const hasShownExpiryToastRef = useRef(false);

  // Sync ref with authentication state
  useEffect(() => {
    isAuthenticatedRef.current = isAuthenticated;
  }, [isAuthenticated]);

  const checkSession = useCallback(async () => {
    try {
      const response = await authApi.checkSession({ silentAuth: true });
      const adminData = response?.admin || response?.user || {
        name: 'Administrator',
        email: 'admin@gmail.com',
        role: 'super-admin',
        is_super_admin: true,
        permissions: ['*'],
      };
      if (typeof window !== 'undefined') {
        localStorage.setItem(ADMIN_STORAGE_KEY, 'true');
      }
      setAdmin(adminData);
      setIsAuthenticated(true);
      isAuthenticatedRef.current = true;
      return adminData;
    } catch (error) {
      if (typeof window !== 'undefined') {
        localStorage.removeItem(ADMIN_STORAGE_KEY);
      }
      setAdmin(null);
      setIsAuthenticated(false);
      isAuthenticatedRef.current = false;
      throw error;
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    let mounted = true;

    authApi
      .checkSession({ silentAuth: true })
      .then((response) => {
        if (!mounted) return;
        const adminData = response?.admin || response?.user || {
          name: 'Administrator',
          email: 'admin@gmail.com',
          role: 'super-admin',
          is_super_admin: true,
          permissions: ['*'],
        };
        if (typeof window !== 'undefined') {
          localStorage.setItem(ADMIN_STORAGE_KEY, 'true');
        }
        setAdmin(adminData);
        setIsAuthenticated(true);
        isAuthenticatedRef.current = true;
      })
      .catch(() => {
        if (!mounted) return;

        const hadActiveSession =
          typeof window !== 'undefined' &&
          localStorage.getItem(ADMIN_STORAGE_KEY) === 'true';

        if (typeof window !== 'undefined') {
          localStorage.removeItem(ADMIN_STORAGE_KEY);
        }

        setAdmin(null);
        setIsAuthenticated(false);
        isAuthenticatedRef.current = false;

        const pathname = typeof window !== 'undefined' ? window.location.pathname : '';
        const isLoginPage = pathname.endsWith('/login') || pathname.includes('/admin/login');

        // Only notify if user had an active session AND tried accessing a protected route (not login page)
        if (hadActiveSession && !isLoginPage) {
          if (!hasShownExpiryToastRef.current) {
            hasShownExpiryToastRef.current = true;
            showError('Your session has expired. Please sign in again.');
            setTimeout(() => {
              hasShownExpiryToastRef.current = false;
            }, 3000);
          }
        }
      })
      .finally(() => {
        if (mounted) setLoading(false);
      });

    return () => {
      mounted = false;
    };
  }, [showError]);

  useEffect(() => {
    const handleAuthExpired = (event) => {
      const wasAuthenticated =
        isAuthenticatedRef.current ||
        (typeof window !== 'undefined' &&
          localStorage.getItem(ADMIN_STORAGE_KEY) === 'true');

      if (typeof window !== 'undefined') {
        localStorage.removeItem(ADMIN_STORAGE_KEY);
      }

      setAdmin(null);
      setIsAuthenticated(false);
      isAuthenticatedRef.current = false;
      resetNotificationSoundState(null);

      const pathname = typeof window !== 'undefined' ? window.location.pathname : '';
      const isLoginPage = pathname.endsWith('/login') || pathname.includes('/admin/login');

      // Do not display expired toast if user is already on the login page or was never logged in
      if (!isLoginPage && wasAuthenticated) {
        if (!hasShownExpiryToastRef.current) {
          hasShownExpiryToastRef.current = true;
          showError(event?.detail?.message || 'Your session has expired. Please sign in again.');
          setTimeout(() => {
            hasShownExpiryToastRef.current = false;
          }, 3000);
        }
      }
    };

    window.addEventListener('auth:expired', handleAuthExpired);
    return () => window.removeEventListener('auth:expired', handleAuthExpired);
  }, [showError]);

  const login = async (credentials) => {
    setLoading(true);
    try {
      const response = await authApi.login(credentials);
      const adminUser = response?.admin || response?.user || {
        name: 'Administrator',
        email: credentials.email,
        role: 'super-admin',
        is_super_admin: true,
        permissions: ['*'],
      };
      if (typeof window !== 'undefined') {
        localStorage.setItem(ADMIN_STORAGE_KEY, 'true');
      }
      setAdmin(adminUser);
      setIsAuthenticated(true);
      isAuthenticatedRef.current = true;
      return { success: true, admin: adminUser };
    } catch (error) {
      if (typeof window !== 'undefined') {
        localStorage.removeItem(ADMIN_STORAGE_KEY);
      }
      setAdmin(null);
      setIsAuthenticated(false);
      isAuthenticatedRef.current = false;
      throw error;
    } finally {
      setLoading(false);
    }
  };

  const logout = async () => {
    setLoading(true);
    try {
      if (typeof window !== 'undefined') {
        localStorage.removeItem(ADMIN_STORAGE_KEY);
      }
      await authApi.logout();
      showInfo('You have been signed out successfully.');
    } catch {
      // Clean up frontend state
    } finally {
      if (typeof window !== 'undefined') {
        localStorage.removeItem(ADMIN_STORAGE_KEY);
      }
      setAdmin(null);
      setIsAuthenticated(false);
      isAuthenticatedRef.current = false;
      resetNotificationSoundState(null);
      setLoading(false);
    }
  };

  const hasPermission = (permissionSlug) => {
    if (!admin) return false;
    if (
      admin.is_super_admin ||
      admin.role === 'super-admin' ||
      admin.role_id === 1 ||
      !admin.permissions ||
      admin.permissions.length === 0 ||
      admin.permissions.includes('*')
    ) {
      return true;
    }
    return Boolean(admin.permissions?.includes(permissionSlug));
  };

  const hasRole = (roleSlug) => {
    if (!admin) return false;
    if (admin.role === roleSlug || admin.roles?.includes(roleSlug)) {
      return true;
    }
    return false;
  };

  return (
    <AuthContext.Provider
      value={{
        admin,
        user: admin,
        isAuthenticated,
        loading,
        login,
        logout,
        checkSession,
        hasPermission,
        hasRole,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export default AuthProvider;
