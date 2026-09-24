import { useRef } from 'react';
import { Toast } from 'primereact/toast';
import { ToastContext } from './toastContextDef';

export function ToastProvider({ children }) {
  const toastRef = useRef(null);
  const recentToastsRef = useRef(new Map());

  const showToast = ({ severity = 'info', summary = '', detail = '', life = 4000 }) => {
    const key = `${severity}:${summary}:${detail}`;
    const now = Date.now();
    const lastShown = recentToastsRef.current.get(key) || 0;
    if (now - lastShown < 1500) {
      // Deduplicate identical toast notifications within 1.5s window
      return;
    }
    recentToastsRef.current.set(key, now);

    // Prune stale cache entries
    if (recentToastsRef.current.size > 50) {
      for (const [k, time] of recentToastsRef.current.entries()) {
        if (now - time > 5000) recentToastsRef.current.delete(k);
      }
    }

    if (toastRef.current) {
      toastRef.current.show({ severity, summary, detail, life });
    }
  };

  const showSuccess = (detail, summary = 'Success') => {
    showToast({ severity: 'success', summary, detail });
  };

  const showError = (detail, summary = 'Error') => {
    showToast({ severity: 'error', summary, detail: detail || 'An unexpected error occurred.' });
  };

  const showWarning = (detail, summary = 'Warning') => {
    showToast({ severity: 'warn', summary, detail });
  };

  const showInfo = (detail, summary = 'Information') => {
    showToast({ severity: 'info', summary, detail });
  };

  return (
    <ToastContext.Provider value={{ showToast, showSuccess, showError, showWarning, showInfo }}>
      <Toast ref={toastRef} position="top-right" />
      {children}
    </ToastContext.Provider>
  );
}

export default ToastProvider;
