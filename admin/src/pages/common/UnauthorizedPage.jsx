import { Link } from 'react-router-dom';
import { ShieldX } from 'lucide-react';
import { Button } from 'primereact/button';

export function UnauthorizedPage() {
  return (
    <div className="flex flex-col items-center justify-center min-h-[60vh] text-center p-6">
      <div className="w-16 h-16 rounded-2xl bg-red-100 flex items-center justify-center text-red-600 mb-4 shadow-xs">
        <ShieldX className="w-8 h-8 stroke-[1.5]" />
      </div>
      <h1 className="text-4xl font-extrabold text-red-900 tracking-tight mb-2">403</h1>
      <h2 className="text-lg font-bold text-slate-800 mb-1">Access Restricted</h2>
      <p className="text-xs text-slate-500 max-w-sm mb-6">
        Your administrator account does not possess the RBAC permission required to view or manage this section.
      </p>
      <Link to="/admin/dashboard">
        <Button
          label="Return to Overview"
          icon="pi pi-arrow-left"
          size="small"
          className="p-button-outlined p-button-secondary text-xs"
        />
      </Link>
    </div>
  );
}

export default UnauthorizedPage;
