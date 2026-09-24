import { Skeleton } from 'primereact/skeleton';

export function AnalyticsSkeleton() {
  return (
    <div className="space-y-6 animate-pulse">
      {/* Header Skeleton */}
      <div className="flex items-center justify-between">
        <div className="space-y-2">
          <Skeleton width="220px" height="14px" />
          <Skeleton width="320px" height="28px" />
        </div>
        <Skeleton width="120px" height="36px" borderRadius="8px" />
      </div>

      {/* Filter Skeleton */}
      <Skeleton width="100%" height="60px" borderRadius="16px" />

      {/* Cards Skeleton */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {[...Array(4)].map((_, i) => (
          <Skeleton key={i} width="100%" height="100px" borderRadius="16px" />
        ))}
      </div>

      {/* Chart Skeleton */}
      <Skeleton width="100%" height="240px" borderRadius="16px" />

      {/* Tabs Skeleton */}
      <Skeleton width="100%" height="300px" borderRadius="16px" />
    </div>
  );
}

export default AnalyticsSkeleton;
