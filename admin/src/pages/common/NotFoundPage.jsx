import { Link } from 'react-router-dom';
import { Compass } from 'lucide-react';
import { Button } from 'primereact/button';

export function NotFoundPage() {
  return (
    <div className="flex flex-col items-center justify-center min-h-[60vh] text-center p-6">
      <div className="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 mb-4 shadow-xs">
        <Compass className="w-8 h-8 stroke-[1.5]" />
      </div>
      <h1 className="text-4xl font-extrabold text-slate-800 tracking-tight mb-2">404</h1>
      <h2 className="text-lg font-bold text-slate-700 mb-1">Page Not Found</h2>
      <p className="text-xs text-slate-500 max-w-sm mb-6">
        The requested administrative resource or page route could not be found in the system.
      </p>
      <Link to="/admin/dashboard">
        <Button
          label="Back to Dashboard"
          icon="pi pi-arrow-left"
          size="small"
          className="p-button-primary text-xs"
        />
      </Link>
    </div>
  );
}

export default NotFoundPage;
