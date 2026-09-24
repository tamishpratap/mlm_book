import { Bell, Send, CheckCircle, Layers } from 'lucide-react';

export function NotificationStatsCards({ stats = {}, loading = false }) {
  const cards = [
    {
      title: 'Total Notifications',
      value: stats.totalCount ?? 0,
      subtext: 'Platform Notifications',
      icon: Bell,
      iconColor: 'text-blue-600',
      bgGradient: 'from-blue-500/10 to-indigo-500/5',
      borderColor: 'border-blue-200/60',
    },
    {
      title: 'Sent Today',
      value: stats.sentTodayCount ?? 0,
      subtext: 'Dispatched Today',
      icon: Send,
      iconColor: 'text-emerald-600',
      bgGradient: 'from-emerald-500/10 to-teal-500/5',
      borderColor: 'border-emerald-200/60',
    },
    {
      title: 'Read Notifications',
      value: stats.readCount ?? 0,
      subtext: 'Opened by Members',
      icon: CheckCircle,
      iconColor: 'text-sky-600',
      bgGradient: 'from-sky-500/10 to-cyan-500/5',
      borderColor: 'border-sky-200/60',
    },
    {
      title: 'Queued Jobs',
      value: stats.queuedJobsCount ?? 0,
      subtext: 'Queue Worker Jobs',
      icon: Layers,
      iconColor: 'text-amber-600',
      bgGradient: 'from-amber-500/10 to-yellow-500/5',
      borderColor: 'border-amber-200/60',
    },
  ];

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      {cards.map((card, idx) => {
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
  );
}

export default NotificationStatsCards;
