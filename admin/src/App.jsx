import { BrowserRouter } from 'react-router-dom';
import { PrimeReactProvider } from 'primereact/api';
import { ToastProvider } from './context/ToastContext';
import { AuthProvider } from './context/AuthContext';
import { BrandingProvider } from './context/BrandingContext';
import { AppRoutes } from './routes/AppRoutes';
import { ErrorBoundary } from './components/common/ErrorBoundary';

export function App() {
  return (
    <PrimeReactProvider value={{ ripple: true }}>
      <ToastProvider>
        <AuthProvider>
          <BrandingProvider>
            <BrowserRouter>
              <ErrorBoundary>
                <AppRoutes />
              </ErrorBoundary>
            </BrowserRouter>
          </BrandingProvider>
        </AuthProvider>
      </ToastProvider>
    </PrimeReactProvider>
  );
}

export default App;
