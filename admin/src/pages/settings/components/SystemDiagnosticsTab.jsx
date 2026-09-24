import { Cpu } from 'lucide-react';

export function SystemDiagnosticsTab({ systemInfo = {} }) {
  return (
    <div className="space-y-4 pt-2">
      <div className="flex items-center space-x-2 border-b border-slate-100 pb-2">
        <Cpu className="w-4 h-4 text-blue-600" />
        <h6 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
          System Diagnostics & Environment
        </h6>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        {Object.entries(systemInfo).map(([key, val]) => (
          <div key={key} className="p-3.5 bg-slate-50 rounded-xl border border-slate-200/70">
            <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">
              {key.replace(/_/g, ' ')}
            </span>
            <strong className="text-xs text-slate-900 font-mono block truncate">
              {String(val || 'N/A')}
            </strong>
          </div>
        ))}
      </div>
    </div>
  );
}

export default SystemDiagnosticsTab;
