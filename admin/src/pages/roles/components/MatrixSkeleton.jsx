import { Skeleton } from 'primereact/skeleton';

export function MatrixSkeleton() {
  return (
    <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-6 space-y-4 animate-pulse">
      <div className="flex justify-between items-center pb-3 border-b border-slate-100">
        <Skeleton width="180px" height="20px" />
        <Skeleton width="140px" height="36px" borderRadius="8px" />
      </div>

      <div className="space-y-3">
        <Skeleton width="100%" height="45px" borderRadius="8px" />
        {Array.from({ length: 8 }).map((_, i) => (
          <div key={i} className="flex space-x-4 items-center">
            <Skeleton width="40%" height="32px" borderRadius="6px" />
            <Skeleton width="20%" height="32px" borderRadius="6px" />
            <Skeleton width="20%" height="32px" borderRadius="6px" />
            <Skeleton width="20%" height="32px" borderRadius="6px" />
          </div>
        ))}
      </div>
    </div>
  );
}

export default MatrixSkeleton;
