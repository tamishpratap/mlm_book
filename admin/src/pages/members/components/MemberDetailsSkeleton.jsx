import { Skeleton } from 'primereact/skeleton';

export function MemberDetailsSkeleton() {
  return (
    <div className="space-y-6 animate-pulse">
      {/* Header Skeleton */}
      <div className="flex items-center justify-between">
        <div className="space-y-2">
          <Skeleton width="180px" height="14px" />
          <Skeleton width="300px" height="28px" />
        </div>
        <div className="flex space-x-2">
          <Skeleton width="100px" height="34px" borderRadius="8px" />
          <Skeleton width="100px" height="34px" borderRadius="8px" />
        </div>
      </div>

      {/* Profile Banner Skeleton */}
      <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <Skeleton width="100%" height="180px" />
        <div className="px-6 pb-6 pt-0 bg-white">
          <div className="flex flex-col sm:flex-row items-center sm:items-end justify-between -mt-12 mb-4 gap-4">
            <div className="flex items-center space-x-4">
              <Skeleton width="100px" height="100px" borderRadius="16px" />
              <div className="space-y-2 pb-1">
                <Skeleton width="200px" height="24px" />
                <Skeleton width="150px" height="14px" />
              </div>
            </div>
          </div>

          <div className="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2 pt-4 border-t border-slate-100">
            {Array.from({ length: 7 }).map((_, i) => (
              <div key={i} className="p-3 bg-slate-50 rounded-xl space-y-1">
                <Skeleton width="40px" height="20px" className="mx-auto" />
                <Skeleton width="60px" height="10px" className="mx-auto" />
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Tabs Skeleton */}
      <div className="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
        <div className="flex space-x-4 border-b border-slate-100 pb-3">
          <Skeleton width="120px" height="32px" />
          <Skeleton width="100px" height="32px" />
          <Skeleton width="100px" height="32px" />
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
          <Skeleton width="100%" height="200px" borderRadius="12px" />
          <Skeleton width="100%" height="200px" borderRadius="12px" />
        </div>
      </div>
    </div>
  );
}

export default MemberDetailsSkeleton;
