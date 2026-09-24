import { Link } from 'react-router-dom';
import { Activity, ArrowRight } from 'lucide-react';

export function SystemStatusCard({
  totalNotifications = 0,
  phpVersion = '8.2',
  laravelVersion = '12.x',
}) {
  return (
    <div className="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
      {/* Header */}
      <div className="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center">
          <Activity className="w-4 h-4 text-emerald-600 mr-2" /> System Status & Diagnostics
        </h4>
        <span className="bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded-full border border-emerald-200">
          Live Operational
        </span>
      </div>

      {/* Body */}
      <div className="p-4 divide-y divide-slate-100 text-xs">
        <div className="py-2 flex items-center justify-between">
          <span className="text-slate-500">PHP Runtime Environment</span>
          <strong className="text-slate-900 font-mono text-[11px] bg-slate-100 px-2 py-0.5 rounded-sm">
            PHP {phpVersion}
          </strong>
        </div>
        <div className="py-2 flex items-center justify-between">
          <span className="text-slate-500">Laravel Core Framework</span>
          <strong className="text-slate-900 font-mono text-[11px] bg-slate-100 px-2 py-0.5 rounded-sm">
            Laravel {laravelVersion}
          </strong>
        </div>
        <div className="py-2 flex items-center justify-between">
          <span className="text-slate-500">System Notifications Stream</span>
          <strong className="text-blue-600 font-bold">
            {totalNotifications.toLocaleString()} Delivered
          </strong>
        </div>
        <div className="pt-2 flex items-center justify-end">
          <Link
            to="/admin/system"
            className="text-xs font-semibold text-blue-600 hover:text-blue-700 inline-flex items-center"
          >
            System Health & Logs <ArrowRight className="w-3 h-3 ml-1" />
          </Link>
        </div>
      </div>
    </div>
  );
}

export default SystemStatusCard;
