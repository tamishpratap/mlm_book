import { Inbox } from 'lucide-react';
import { Button } from 'primereact/button';

export function EmptyState({
  title = 'No records found',
  description = 'There are no items matching your search or filter criteria.',
  icon: Icon = Inbox,
  actionLabel,
  onAction,
  className = '',
}) {
  return (
    <div className={`flex flex-col items-center justify-center p-8 sm:p-12 text-center bg-white rounded-xl border border-slate-200 shadow-2xs my-4 ${className}`}>
      <div className="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 mb-4 shadow-2xs">
        <Icon className="w-7 h-7 stroke-[1.5]" />
      </div>
      <h3 className="text-sm font-bold text-slate-900 mb-1">{title}</h3>
      <p className="text-xs text-slate-600 max-w-sm mb-6 leading-relaxed">{description}</p>
      {actionLabel && onAction && (
        <Button
          label={actionLabel}
          icon="pi pi-plus"
          size="small"
          onClick={onAction}
          className="p-button-outlined p-button-primary text-xs"
        />
      )}
    </div>
  );
}

export default EmptyState;
