import { useState, useEffect, useCallback, useMemo } from 'react';
import { AuthContext } from './AuthContext';
import authApi from '../api/authApi';
import { resetNotificationSoundState } from '../utils/notificationSound';

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [unreadCount, setUnreadCount] = useState(0);
  const [isLoading, setIsLoading] = useState(true);

  // Refresh user state from API (/api/member/me)
  const refreshUser = useCallback(async () => {
    try {
      const data = await authApi.getMe();
      if (data && data.member) {
        setUser(data.member);
        setUnreadCount(data.unread_notifications_count || 0);
      } else {
        setUser(null);
        setUnreadCount(0);
      }
    } catch {
      setUser(null);
      setUnreadCount(0);
    }
  }, []);

  // Initial session check on mount
  useEffect(() => {
    let isMounted = true;
    authApi.getMe()
      .then((data) => {
        if (isMounted && data && data.member) {
          setUser(data.member);
          setUnreadCount(data.unread_notifications_count || 0);
        } else if (isMounted) {
          setUser(null);
          setUnreadCount(0);
        }
      })
      .catch(() => {
        if (isMounted) {
          setUser(null);
          setUnreadCount(0);
        }
      })
      .finally(() => {
        if (isMounted) {
          setIsLoading(false);
        }
      });

    // Listen to global unauthenticated events from apiClient interceptor
    const handleUnauthenticated = () => {
      setUser(null);
      setUnreadCount(0);
      resetNotificationSoundState(null);
    };

    window.addEventListener('member:unauthenticated', handleUnauthenticated);
    return () => {
      isMounted = false;
      window.removeEventListener('member:unauthenticated', handleUnauthenticated);
    };
  }, []);

  // Log in action
  const login = useCallback(async (credentials) => {
    const response = await authApi.login(credentials);
    if (response && response.member) {
      setUser(response.member);
    }
    await refreshUser();
    return response;
  }, [refreshUser]);

  // Log out action
  const logout = useCallback(async () => {
    try {
      await authApi.logout();
    } catch (err) {
      console.warn('Logout API warning:', err);
    } finally {
      setUser(null);
      setUnreadCount(0);
      resetNotificationSoundState(null);
    }
  }, []);

  const value = useMemo(() => ({
    user,
    setUser,
    isAuthenticated: !!user,
    isLoading,
    unreadCount,
    setUnreadCount,
    login,
    logout,
    refreshUser,
  }), [user, isLoading, unreadCount, login, logout, refreshUser]);

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
}

export default AuthProvider;
