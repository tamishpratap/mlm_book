import { Skeleton } from 'primereact/skeleton';

export function ReportDetailsSkeleton() {
  return (
    <div className="space-y-6 animate-pulse">
      {/* Header Skeleton */}
      <div className="flex items-center justify-between flex-wrap gap-4">
        <div className="space-y-2">
          <Skeleton width="180px" height="14px" />
          <Skeleton width="280px" height="28px" />
        </div>
        <div className="flex items-center space-x-2">
          <Skeleton width="110px" height="36px" borderRadius="8px" />
          <Skeleton width="110px" height="36px" borderRadius="8px" />
          <Skeleton width="80px" height="36px" borderRadius="8px" />
        </div>
      </div>

      {/* Grid Skeleton */}
      <div className="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_320px] xl:grid-cols-[minmax(0,1fr)_340px] gap-6 items-start">
        {/* Left Column */}
        <div className="space-y-6">
          <div className="p-6 bg-white rounded-2xl border border-slate-200 shadow-2xs space-y-4">
            <Skeleton width="100%" height="24px" />
            <Skeleton width="100%" height="70px" borderRadius="12px" />
            <Skeleton width="100%" height="100px" borderRadius="12px" />
          </div>

          <div className="p-6 bg-white rounded-2xl border border-slate-200 shadow-2xs space-y-4">
            <Skeleton width="100%" height="24px" />
            <Skeleton width="100%" height="160px" borderRadius="12px" />
          </div>
        </div>

        {/* Right Sidebar */}
        <div className="space-y-6">
          <div className="p-6 bg-white rounded-2xl border border-slate-200 shadow-2xs space-y-4">
            <Skeleton width="120px" height="16px" />
            <div className="flex items-center space-x-3">
              <Skeleton shape="circle" size="44px" />
              <div className="space-y-2 flex-1">
                <Skeleton width="120px" height="16px" />
                <Skeleton width="80px" height="12px" />
              </div>
            </div>
            <Skeleton width="100%" height="40px" borderRadius="10px" />
          </div>

          <div className="p-6 bg-white rounded-2xl border border-slate-200 shadow-2xs space-y-4">
            <Skeleton width="120px" height="16px" />
            <div className="flex items-center space-x-3">
              <Skeleton shape="circle" size="44px" />
              <div className="space-y-2 flex-1">
                <Skeleton width="120px" height="16px" />
                <Skeleton width="80px" height="12px" />
              </div>
            </div>
            <Skeleton width="100%" height="40px" borderRadius="10px" />
          </div>

          <div className="p-6 bg-white rounded-2xl border border-slate-200 shadow-2xs space-y-5">
            <Skeleton width="140px" height="16px" />
            <div className="space-y-3">
              <Skeleton width="100%" height="44px" borderRadius="12px" />
              <Skeleton width="100%" height="44px" borderRadius="12px" />
            </div>
            <div className="pt-4 border-t border-slate-100 space-y-3">
              <Skeleton width="100%" height="44px" borderRadius="12px" />
              <Skeleton width="100%" height="44px" borderRadius="12px" />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export default ReportDetailsSkeleton;
