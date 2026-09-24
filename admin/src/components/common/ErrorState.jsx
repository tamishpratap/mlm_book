import { AlertCircle } from 'lucide-react';
import { Button } from 'primereact/button';

export function ErrorState({
  title = 'Failed to load content',
  message = 'An error occurred while communicating with the server. Please try again.',
  onRetry,
  className = '',
}) {
  return (
    <div className={`flex flex-col items-center justify-center p-6 sm:p-8 text-center bg-red-50/60 rounded-xl border border-red-200/80 my-4 ${className}`}>
      <div className="w-12 h-12 rounded-2xl bg-red-100 flex items-center justify-center text-red-500 mb-3 shadow-2xs">
        <AlertCircle className="w-6 h-6 stroke-[2]" />
      </div>
      <h3 className="text-sm font-bold text-red-950 mb-1">{title}</h3>
      <p className="text-xs text-red-700 font-medium max-w-md mb-5 leading-relaxed">{message}</p>
      {onRetry && (
        <Button
          label="Try Again"
          icon="pi pi-refresh"
          size="small"
          severity="danger"
          outlined
          onClick={onRetry}
          className="text-xs"
        />
      )}
    </div>
  );
}

export default ErrorState;
