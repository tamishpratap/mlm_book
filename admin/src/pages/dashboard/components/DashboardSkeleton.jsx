export function DashboardSkeleton() {
  return (
    <div className="space-y-6 animate-pulse">
      {/* Header skeleton */}
      <div className="flex justify-between items-center">
        <div className="space-y-2">
          <div className="h-6 w-64 bg-slate-200 rounded-md" />
          <div className="h-4 w-96 bg-slate-100 rounded-md" />
        </div>
        <div className="h-9 w-32 bg-slate-200 rounded-lg" />
      </div>

      {/* Quick Nav skeleton */}
      <div className="h-12 bg-white rounded-xl border border-slate-200 p-3" />

      {/* 8 Stat Cards skeleton */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {Array.from({ length: 8 }).map((_, i) => (
          <div key={i} className="bg-white p-4 rounded-xl border border-slate-200 h-24 flex justify-between">
            <div className="space-y-2">
              <div className="h-3 w-20 bg-slate-200 rounded-sm" />
              <div className="h-6 w-16 bg-slate-300 rounded-sm" />
              <div className="h-2.5 w-28 bg-slate-100 rounded-sm" />
            </div>
            <div className="w-10 h-10 rounded-xl bg-slate-100" />
          </div>
        ))}
      </div>

      {/* 3 Summary Cards skeleton */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        {Array.from({ length: 3 }).map((_, i) => (
          <div key={i} className="bg-white rounded-xl border border-slate-200 h-64 p-4 space-y-3">
            <div className="h-4 w-32 bg-slate-200 rounded-sm" />
            <div className="space-y-2 pt-2">
              {Array.from({ length: 5 }).map((_, j) => (
                <div key={j} className="h-4 bg-slate-100 rounded-sm" />
              ))}
            </div>
          </div>
        ))}
      </div>

      {/* Chart & Categories skeleton */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-4">
        <div className="lg:col-span-8 bg-white rounded-xl border border-slate-200 h-80 p-4" />
        <div className="lg:col-span-4 bg-white rounded-xl border border-slate-200 h-80 p-4" />
      </div>

      {/* Tables skeleton */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div className="bg-white rounded-xl border border-slate-200 h-72 p-4" />
        <div className="bg-white rounded-xl border border-slate-200 h-72 p-4" />
      </div>
    </div>
  );
}

export default DashboardSkeleton;
