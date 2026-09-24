import { Skeleton } from 'primereact/skeleton';

export function ProductDetailsSkeleton() {
  return (
    <div className="space-y-6 animate-pulse">
      {/* Header Skeleton */}
      <div className="flex items-center justify-between">
        <div className="space-y-2">
          <Skeleton width="180px" height="14px" />
          <Skeleton width="280px" height="28px" />
        </div>
        <div className="flex space-x-2">
          <Skeleton width="100px" height="36px" borderRadius="8px" />
          <Skeleton width="100px" height="36px" borderRadius="8px" />
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left 2 Cols: Main Info + Gallery */}
        <div className="lg:col-span-2 space-y-6">
          <div className="p-6 bg-white rounded-2xl border border-slate-200 shadow-2xs space-y-4">
            <Skeleton width="100%" height="80px" borderRadius="12px" />
            <Skeleton width="100%" height="180px" borderRadius="12px" />
            <Skeleton width="100%" height="220px" borderRadius="12px" />
          </div>
        </div>

        {/* Right Col: Analytics + Actions */}
        <div className="space-y-6">
          <div className="p-6 bg-white rounded-2xl border border-slate-200 shadow-2xs space-y-3">
            <Skeleton width="140px" height="20px" />
            <Skeleton width="100%" height="40px" />
            <Skeleton width="100%" height="40px" />
            <Skeleton width="100%" height="40px" />
          </div>
        </div>
      </div>
    </div>
  );
}

export default ProductDetailsSkeleton;
