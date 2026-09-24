import { Skeleton } from 'primereact/skeleton';

export function SettingsSkeleton() {
  return (
    <div className="space-y-6 animate-pulse">
      {/* Header Skeleton */}
      <div className="space-y-2">
        <Skeleton width="180px" height="14px" />
        <Skeleton width="320px" height="28px" />
      </div>

      {/* Card Skeleton */}
      <div className="p-6 bg-white rounded-2xl border border-slate-200 shadow-2xs space-y-6">
        <Skeleton width="100%" height="40px" borderRadius="8px" />
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <Skeleton width="100%" height="60px" borderRadius="8px" />
          <Skeleton width="100%" height="60px" borderRadius="8px" />
          <Skeleton width="100%" height="90px" borderRadius="8px" className="sm:col-span-2" />
        </div>
      </div>
    </div>
  );
}

export default SettingsSkeleton;
