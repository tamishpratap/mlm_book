import React from 'react';
import { AlertTriangle, RefreshCw, RotateCcw } from 'lucide-react';
import { Button } from 'primereact/button';

/**
 * ErrorBoundary
 * Catches JavaScript runtime and render errors anywhere in child component tree,
 * logs the error, and displays a graceful fallback UI instead of crashing the app into a blank page.
 */
export class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      hasError: false,
      error: null,
      errorInfo: null,
    };
  }

  static getDerivedStateFromError(error) {
    return { hasError: true, error };
  }

  componentDidCatch(error, errorInfo) {
    this.setState({ errorInfo });
    console.error('[ErrorBoundary caught error]:', error, errorInfo);
  }

  handleReset = () => {
    this.setState({ hasError: false, error: null, errorInfo: null });
    if (this.props.onReset) {
      this.props.onReset();
    }
  };

  handleReload = () => {
    window.location.reload();
  };

  render() {
    if (this.state.hasError) {
      if (this.props.fallback) {
        return this.props.fallback;
      }

      const isDev = Boolean(import.meta.env?.DEV);
      const errorMessage =
        this.state.error?.message ||
        (typeof this.state.error === 'string' ? this.state.error : 'An unexpected error occurred.');

      return (
        <div className="flex flex-col items-center justify-center p-6 sm:p-10 text-center bg-white rounded-2xl border border-red-200/90 shadow-sm my-6 max-w-2xl mx-auto">
          <div className="w-14 h-14 rounded-2xl bg-red-50 flex items-center justify-center text-red-500 mb-4 shadow-2xs">
            <AlertTriangle className="w-7 h-7 stroke-[2]" />
          </div>

          <h2 className="text-base font-bold text-slate-900 mb-1">
            {this.props.title || 'Something went wrong rendering this section'}
          </h2>
          <p className="text-xs text-slate-500 max-w-md mb-6 leading-relaxed">
            {this.props.description ||
              'A component rendering exception occurred. You can attempt to refresh this view or reload the page.'}
          </p>

          <div className="flex flex-wrap items-center justify-center gap-3 mb-6">
            <Button
              label="Try Again"
              icon={<RotateCcw className="w-3.5 h-3.5 mr-1.5" />}
              size="small"
              onClick={this.handleReset}
              className="p-button-outlined p-button-secondary text-xs"
            />
            <Button
              label="Reload Page"
              icon={<RefreshCw className="w-3.5 h-3.5 mr-1.5" />}
              size="small"
              onClick={this.handleReload}
              className="p-button-primary text-xs"
            />
          </div>

          {isDev && this.state.error && (
            <div className="w-full text-left bg-slate-900 text-slate-100 p-4 rounded-xl text-[11px] font-mono overflow-auto max-h-56 mt-2 border border-slate-800">
              <div className="font-bold text-red-400 mb-1.5">Runtime Error: {errorMessage}</div>
              {this.state.errorInfo?.componentStack && (
                <pre className="text-slate-400 whitespace-pre-wrap leading-snug">
                  {this.state.errorInfo.componentStack}
                </pre>
              )}
            </div>
          )}
        </div>
      );
    }

    return this.props.children;
  }
}

export default ErrorBoundary;
