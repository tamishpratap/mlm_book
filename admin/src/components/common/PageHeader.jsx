import { Link } from 'react-router-dom';
import { ChevronRight, Home } from 'lucide-react';

export function PageHeader({
  title,
  subtitle,
  breadcrumbs = [],
  actions,
  statusBadge,
  className = '',
}) {
  return (
    <div className={`mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-3.5 sm:gap-4 ${className}`}>
      <div className="min-w-0">
        {breadcrumbs.length > 0 && (
          <nav className="flex flex-wrap items-center gap-1.5 text-xs text-slate-600 mb-1.5" aria-label="Breadcrumb">
            <Link to="/admin/dashboard" className="flex items-center text-slate-600 hover:text-blue-600 font-medium transition-colors">
              <Home className="w-3.5 h-3.5 mr-1" />
              <span>Admin</span>
            </Link>
            {breadcrumbs.map((crumb, index) => {
              const isLast = index === breadcrumbs.length - 1;
              return (
                <span key={index} className="inline-flex items-center gap-1.5">
                  <ChevronRight className="w-3 h-3 text-slate-400 shrink-0" />
                  {crumb.to && !isLast ? (
                    <Link to={crumb.to} className="text-slate-600 hover:text-blue-600 font-medium transition-colors truncate max-w-[140px] sm:max-w-none">
                      {crumb.label}
                    </Link>
                  ) : (
                    <span className="font-bold text-slate-850 truncate max-w-[160px] sm:max-w-none">{crumb.label}</span>
                  )}
                </span>
              );
            })}
          </nav>
        )}
        <div className="flex flex-wrap items-center gap-2.5 sm:gap-3">
          <h1 className="text-2xl font-bold tracking-tight text-slate-900">{title}</h1>
          {statusBadge && <div className="shrink-0">{statusBadge}</div>}
        </div>
        {subtitle && <p className="text-xs text-slate-500 mt-1 leading-relaxed">{subtitle}</p>}
      </div>

      {actions && (
        <div className="flex flex-wrap items-center gap-2 sm:gap-2.5 shrink-0 w-full sm:w-auto justify-start sm:justify-end">
          {actions}
        </div>
      )}
    </div>
  );
}

export default PageHeader;
