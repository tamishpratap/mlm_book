import { Clock, Zap, Slash } from 'lucide-react';
import { Link } from 'react-router-dom';

export function StoryStatsCards({
  totalCount = 0,
  activeCount = 0,
  expiredCount = 0,
  currentMode = 'all',
}) {
  const cards = [
    {
      key: 'all',
      title: 'Total Stories',
      value: totalCount,
      to: '/admin/stories',
      icon: Clock,
      color: 'text-blue-600',
      bgColor: 'bg-blue-50',
      activeBorder: currentMode === 'all' ? 'ring-2 ring-blue-500/30 border-blue-200' : 'border-slate-200',
      subtext: 'Platform Stories',
    },
    {
      key: 'live',
      title: 'Live (Active) Stories',
      value: activeCount,
      to: '/admin/stories/live',
      icon: Zap,
      color: 'text-emerald-600',
      bgColor: 'bg-emerald-50',
      activeBorder: currentMode === 'live' ? 'ring-2 ring-emerald-500/30 border-emerald-200' : 'border-slate-200',
      subtext: 'Currently Live',
    },
    {
      key: 'expired',
      title: 'Expired Stories',
      value: expiredCount,
      to: '/admin/stories/expired',
      icon: Slash,
      color: 'text-amber-600',
      bgColor: 'bg-amber-50',
      activeBorder: currentMode === 'expired' ? 'ring-2 ring-amber-500/30 border-amber-200' : 'border-slate-200',
      subtext: 'Past 24h Window',
    },
  ];

  return (
    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
      {cards.map((card) => {
        const Icon = card.icon;
        return (
          <Link
            key={card.key}
            to={card.to}
            className={`bg-white p-4 rounded-xl border ${card.activeBorder} shadow-2xs hover:shadow-xs transition-all block`}
          >
            <div className="flex items-center justify-between">
              <span className="text-xs font-semibold text-slate-500">{card.title}</span>
              <div className={`p-2 rounded-lg ${card.bgColor} ${card.color}`}>
                <Icon className="w-4 h-4" />
              </div>
            </div>
            <div className="mt-2 flex items-baseline justify-between">
              <p className={`text-2xl font-bold ${card.key === 'live' ? 'text-emerald-600' : card.key === 'expired' ? 'text-slate-700' : 'text-slate-800'}`}>
                {card.value}
              </p>
              <span className="text-[11px] text-slate-400 font-medium">{card.subtext}</span>
            </div>
          </Link>
        );
      })}
    </div>
  );
}

export default StoryStatsCards;
