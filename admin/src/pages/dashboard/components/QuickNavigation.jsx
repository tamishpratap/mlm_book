import { Link } from 'react-router-dom';
import {
  Zap,
  Users,
  FileText,
  Clock,
  Briefcase,
  Globe,
  ShoppingBag,
  Calendar,
  AlertCircle,
  BarChart2,
} from 'lucide-react';

export function QuickNavigation({ pendingReports = 0 }) {
  const quickLinks = [
    { label: 'Members', to: '/admin/members', icon: Users },
    { label: 'Posts', to: '/admin/posts', icon: FileText },
    { label: 'Stories', to: '/admin/stories', icon: Clock },
    { label: 'Business Pages', to: '/admin/business-pages', icon: Briefcase },
    { label: 'Communities', to: '/admin/communities', icon: Globe },
    { label: 'Marketplace', to: '/admin/marketplace', icon: ShoppingBag },
    { label: 'Events', to: '/admin/events', icon: Calendar },
    {
      label: 'Reports',
      to: '/admin/reports',
      icon: AlertCircle,
      badge: pendingReports > 0 ? pendingReports : null,
      badgeColor: 'bg-red-500 text-white',
    },
    {
      label: 'Full Analytics BI',
      to: '/admin/analytics',
      icon: BarChart2,
      isPrimary: true,
    },
  ];

  return (
    <div className="bg-white border border-slate-200 rounded-xl p-3.5 shadow-2xs">
      <div className="flex items-center justify-between flex-wrap gap-2">
        <span className="text-xs font-bold text-slate-800 flex items-center uppercase tracking-wider">
          <Zap className="w-4 h-4 text-blue-600 mr-1.5" /> Quick Navigation:
        </span>
        <div className="flex flex-wrap gap-1.5">
          {quickLinks.map((link, idx) => {
            const Icon = link.icon;
            if (link.isPrimary) {
              return (
                <Link
                  key={idx}
                  to={link.to}
                  className="px-2.5 py-1 text-xs bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-md font-semibold transition-colors flex items-center space-x-1 border border-blue-200/60"
                >
                  <Icon className="w-3.5 h-3.5" />
                  <span>{link.label}</span>
                </Link>
              );
            }
            return (
              <Link
                key={idx}
                to={link.to}
                className="px-2.5 py-1 text-xs bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-md font-medium transition-colors flex items-center space-x-1 border border-slate-200/60"
              >
                <Icon className="w-3.5 h-3.5 text-slate-500" />
                <span>{link.label}</span>
                {link.badge && (
                  <span className={`text-[10px] font-bold px-1.5 py-0.2 rounded-full ${link.badgeColor}`}>
                    {link.badge}
                  </span>
                )}
              </Link>
            );
          })}
        </div>
      </div>
    </div>
  );
}

export default QuickNavigation;
