import { Skeleton } from 'primereact/skeleton';

export function NotificationDetailsSkeleton() {
  return (
    <div className="space-y-6 animate-pulse">
      {/* Header Skeleton */}
      <div className="flex items-center justify-between">
        <div className="space-y-2">
          <Skeleton width="180px" height="14px" />
          <Skeleton width="260px" height="28px" />
        </div>
        <div className="flex space-x-2">
          <Skeleton width="90px" height="36px" borderRadius="8px" />
          <Skeleton width="90px" height="36px" borderRadius="8px" />
        </div>
      </div>

      {/* Grid Skeleton */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="p-6 bg-white rounded-2xl border border-slate-200 shadow-2xs space-y-4">
          <Skeleton width="100%" height="80px" borderRadius="12px" />
          <Skeleton width="100%" height="100px" borderRadius="12px" />
        </div>
        <div className="p-6 bg-white rounded-2xl border border-slate-200 shadow-2xs space-y-3">
          <Skeleton width="140px" height="20px" />
          <Skeleton width="100%" height="180px" borderRadius="12px" />
        </div>
      </div>
    </div>
  );
}

export default NotificationDetailsSkeleton;
