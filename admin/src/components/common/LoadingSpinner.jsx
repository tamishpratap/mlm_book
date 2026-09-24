export function LoadingSpinner({
  message = 'Loading...',
  fullScreen = false,
  size = 'md',
}) {
  const sizeClasses = {
    sm: 'w-5 h-5 border-2',
    md: 'w-8 h-8 border-3',
    lg: 'w-12 h-12 border-4',
  };

  const spinner = (
    <div className="flex flex-col items-center justify-center p-6 space-y-3">
      <div
        className={`animate-spin rounded-full border-blue-600 border-t-transparent ${sizeClasses[size] || sizeClasses.md}`}
        role="status"
        aria-label="loading"
      />
      {message && (
        <span className="text-xs font-semibold text-slate-500">{message}</span>
      )}
    </div>
  );

  if (fullScreen) {
    return (
      <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs">
        <div className="bg-white p-6 rounded-2xl shadow-2xl flex flex-col items-center space-y-3 border border-slate-100">
          <div className="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin" />
          <span className="text-xs font-bold text-slate-700">{message}</span>
        </div>
      </div>
    );
  }

  return spinner;
}

export default LoadingSpinner;
