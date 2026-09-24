import { Users, FileText, Briefcase, ShoppingBag, MessageSquare, Calendar, Flag, Bell } from 'lucide-react';

export function AnalyticsStatsCards({ data = {}, loading = false }) {
  const primaryCards = [
    {
      title: 'Total Members',
      value: data.totalMembers ?? 0,
      subtext: `${data.newMembersCount ?? 0} new in period`,
      icon: Users,
      iconColor: 'text-blue-600',
      bgGradient: 'from-blue-500/10 to-indigo-500/5',
      borderColor: 'border-blue-200/60',
    },
    {
      title: 'Posts Published',
      value: data.totalPosts ?? 0,
      subtext: `${data.periodPostsCount ?? 0} posts in period`,
      icon: FileText,
      iconColor: 'text-emerald-600',
      bgGradient: 'from-emerald-500/10 to-teal-500/5',
      borderColor: 'border-emerald-200/60',
    },
    {
      title: 'Business Pages',
      value: data.totalBusinessPages ?? 0,
      subtext: `${data.verifiedBusinessPagesCount ?? 0} verified pages`,
      icon: Briefcase,
      iconColor: 'text-sky-600',
      bgGradient: 'from-sky-500/10 to-cyan-500/5',
      borderColor: 'border-sky-200/60',
    },
    {
      title: 'Marketplace Listings',
      value: data.totalProducts ?? 0,
      subtext: `${data.activeProductsCount ?? 0} active listings`,
      icon: ShoppingBag,
      iconColor: 'text-amber-600',
      bgGradient: 'from-amber-500/10 to-yellow-500/5',
      borderColor: 'border-amber-200/60',
    },
  ];

  const secondaryCards = [
    {
      title: 'Communities',
      value: data.totalCommunities ?? 0,
      icon: MessageSquare,
      color: 'text-slate-900',
    },
    {
      title: 'Events Scheduled',
      value: data.totalEvents ?? 0,
      icon: Calendar,
      color: 'text-slate-900',
    },
    {
      title: 'Platform Reports',
      value: data.totalReports ?? 0,
      icon: Flag,
      color: 'text-red-600',
    },
    {
      title: 'Notifications Delivered',
      value: data.totalNotifications ?? 0,
      icon: Bell,
      color: 'text-blue-600',
    },
  ];

  return (
    <div className="space-y-4">
      {/* 4 Primary Metric Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {primaryCards.map((card, idx) => {
          const Icon = card.icon;
          return (
            <div
              key={idx}
              className={`relative overflow-hidden rounded-2xl bg-white p-5 border ${card.borderColor} shadow-2xs transition-all hover:shadow-md`}
            >
              <div className="flex items-center justify-between">
                <div className="space-y-1">
                  <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    {card.title}
                  </span>
                  <div className="text-2xl font-extrabold text-slate-900">
                    {loading ? (
                      <div className="h-7 w-16 bg-slate-200 animate-pulse rounded-md mt-1" />
                    ) : (
                      Number(card.value).toLocaleString()
                    )}
                  </div>
                  <span className="text-[11px] text-slate-400 font-medium block">
                    {card.subtext}
                  </span>
                </div>
                <div className={`p-3 rounded-xl bg-gradient-to-br ${card.bgGradient} shrink-0`}>
                  <Icon className={`w-6 h-6 ${card.iconColor}`} />
                </div>
              </div>
            </div>
          );
        })}
      </div>

      {/* 4 Secondary Metric Strips */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        {secondaryCards.map((card, idx) => (
          <div key={idx} className="p-4 bg-white border border-slate-200/80 rounded-2xl text-center shadow-2xs">
            <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">
              {card.title}
            </span>
            <span className={`text-lg font-extrabold ${card.color}`}>
              {loading ? '...' : Number(card.value).toLocaleString()}
            </span>
          </div>
        ))}
      </div>
    </div>
  );
}

export default AnalyticsStatsCards;
