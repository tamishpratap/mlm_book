import { Link } from 'react-router-dom';
import { Users, CheckCircle2, Clock, UserX } from 'lucide-react';

export function MemberStatsCards({
  totalCount = 0,
  activeCount = 0,
  pendingCount = 0,
  blockedCount = 0,
  currentMode = 'active',
}) {
  const cards = [
    {
      title: 'Total Members',
      value: totalCount.toLocaleString(),
      icon: Users,
      color: 'text-blue-600',
      bg: 'bg-blue-50',
      subtext: 'All System Records',
      to: '/admin/members',
      mode: 'active',
    },
    {
      title: 'Verified Members',
      value: activeCount.toLocaleString(),
      icon: CheckCircle2,
      color: 'text-emerald-600',
      bg: 'bg-emerald-50',
      subtext: 'Mobile Number Verified',
      to: '/admin/members',
      mode: 'active',
    },
    {
      title: 'Unverified Members',
      value: pendingCount.toLocaleString(),
      icon: Clock,
      color: 'text-amber-600',
      bg: 'bg-amber-50',
      subtext: 'Pending Mobile Verification',
      to: '/admin/members/pending',
      mode: 'pending',
    },
    {
      title: 'Blocked Members',
      value: blockedCount.toLocaleString(),
      icon: UserX,
      color: 'text-slate-600',
      bg: 'bg-slate-100',
      subtext: 'Suspended Accounts',
      to: '/admin/members/blocked',
      mode: 'blocked',
    },
  ];

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      {cards.map((card, idx) => {
        const Icon = card.icon;
        const isCurrent = currentMode === card.mode;

        return (
          <Link
            key={idx}
            to={card.to}
            className={`block bg-white p-4 rounded-xl border transition-all ${
              isCurrent
                ? 'border-blue-500/50 shadow-xs ring-1 ring-blue-500/20'
                : 'border-slate-200 shadow-2xs hover:shadow-xs'
            }`}
          >
            <div className="flex items-start justify-between">
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
          </Link>
        );
      })}
    </div>
  );
}

export default MemberStatsCards;
