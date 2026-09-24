import { Skeleton } from 'primereact/skeleton';

export function RoleSkeleton() {
  return (
    <div className="space-y-6 animate-pulse">
      {/* Header Skeleton */}
      <div className="flex items-center justify-between">
        <div className="space-y-2">
          <Skeleton width="180px" height="14px" />
          <Skeleton width="280px" height="28px" />
        </div>
        <div className="flex space-x-2">
          <Skeleton width="120px" height="36px" borderRadius="8px" />
          <Skeleton width="120px" height="36px" borderRadius="8px" />
        </div>
      </div>

      {/* 4 Stats Cards Skeleton */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {Array.from({ length: 4 }).map((_, i) => (
          <div key={i} className="p-5 bg-white rounded-2xl border border-slate-200 shadow-2xs space-y-3">
            <div className="flex justify-between items-center">
              <Skeleton width="80px" height="14px" />
              <Skeleton width="36px" height="36px" borderRadius="10px" />
            </div>
            <Skeleton width="60px" height="24px" />
            <Skeleton width="110px" height="12px" />
          </div>
        ))}
      </div>

      {/* Main Table Container Skeleton */}
      <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-6 space-y-4">
        <Skeleton width="100%" height="42px" borderRadius="10px" />
        <div className="space-y-3 pt-2">
          {Array.from({ length: 4 }).map((_, i) => (
            <Skeleton key={i} width="100%" height="52px" borderRadius="8px" />
          ))}
        </div>
      </div>
    </div>
  );
}

export default RoleSkeleton;
