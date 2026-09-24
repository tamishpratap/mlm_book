import {
  Users,
  UserPlus,
  Calendar,
  TrendingUp,
  Activity,
  UserX,
  UserCheck,
  Share2,
} from 'lucide-react';

export function KpiStatCards({ data = {} }) {
  const cards = [
    {
      title: 'Total Members',
      value: (data.totalMembers || 0).toLocaleString(),
      icon: Users,
      color: 'text-blue-600',
      bg: 'bg-blue-50',
      subtext: 'Registered Platform Accounts',
    },
    {
      title: 'New Members Today',
      value: (data.newMembersToday || 0).toLocaleString(),
      icon: UserPlus,
      color: 'text-emerald-600',
      bg: 'bg-emerald-50',
      subtext: 'Joined within last 24h',
    },
    {
      title: 'New This Week',
      value: (data.newMembersThisWeek || 0).toLocaleString(),
      icon: Calendar,
      color: 'text-sky-600',
      bg: 'bg-sky-50',
      subtext: 'Current week registrations',
    },
    {
      title: 'New This Month',
      value: (data.newMembersThisMonth || 0).toLocaleString(),
      icon: TrendingUp,
      color: 'text-indigo-600',
      bg: 'bg-indigo-50',
      subtext: 'Current month onboarding',
    },
    {
      title: 'Active Members',
      value: (data.activeMembers || 0).toLocaleString(),
      icon: Activity,
      color: 'text-emerald-600',
      bg: 'bg-emerald-50',
      subtext: 'Active in past 7 days',
    },
    {
      title: 'Inactive Members',
      value: (data.inactiveMembers || 0).toLocaleString(),
      icon: UserX,
      color: 'text-slate-600',
      bg: 'bg-slate-100',
      subtext: 'No activity in 7+ days',
    },
    {
      title: 'Pending Requests',
      value: (data.pendingConnections || 0).toLocaleString(),
      icon: UserCheck,
      color: 'text-amber-600',
      bg: 'bg-amber-50',
      subtext: 'Awaiting user acceptance',
    },
    {
      title: 'Total Connections',
      value: (data.totalConnections || 0).toLocaleString(),
      icon: Share2,
      color: 'text-teal-600',
      bg: 'bg-teal-50',
      subtext: 'Established friend connections',
    },
  ];

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      {cards.map((card, idx) => {
        const Icon = card.icon;
        return (
          <div
            key={idx}
            className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs hover:shadow-xs transition-shadow flex items-start justify-between"
          >
            <div>
              <span className="text-xs font-semibold text-slate-500 block mb-1">
                {card.title}
              </span>
              <h3 className="text-2xl font-bold text-slate-900 tracking-tight">
                {card.value}
              </h3>
              <span className="text-[11px] text-slate-400 mt-1 block">
                {card.subtext}
              </span>
            </div>
            <div className={`w-11 h-11 rounded-xl ${card.bg} ${card.color} flex items-center justify-center shrink-0`}>
              <Icon className="w-5 h-5 stroke-[2]" />
            </div>
          </div>
        );
      })}
    </div>
  );
}

export default KpiStatCards;
