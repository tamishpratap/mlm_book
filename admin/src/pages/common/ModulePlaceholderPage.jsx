import { Layers, Shield, Code, CheckCircle } from 'lucide-react';
import { PageHeader } from '../../components/common/PageHeader';

export function ModulePlaceholderPage({
  title,
  subtitle,
  moduleKey,
  controller,
  phase = 'Phase 5+',
  features = [],
}) {
  return (
    <div className="space-y-6">
      <PageHeader
        title={title}
        subtitle={subtitle}
        breadcrumbs={[{ label: title }]}
      />

      <div className="bg-white rounded-xl border border-slate-200 p-6 sm:p-8 shadow-2xs">
        <div className="flex items-start space-x-4 mb-6">
          <div className="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
            <Layers className="w-6 h-6 stroke-[1.5]" />
          </div>
          <div>
            <div className="flex items-center space-x-2 mb-1">
              <h3 className="text-base font-bold text-slate-800">{title}</h3>
              <span className="bg-blue-100 text-blue-800 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase">
                {phase} Target
              </span>
            </div>
            <p className="text-xs text-slate-500 leading-relaxed max-w-2xl">
              This module route and navigation shell are registered and active. The full data tables, forms, filters, and bulk actions will be migrated during {phase} according to the migration architecture baseline.
            </p>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-slate-100">
          <div className="bg-slate-50 p-4 rounded-lg border border-slate-100">
            <span className="text-[11px] font-bold text-slate-700 uppercase tracking-wider block mb-2 flex items-center">
              <Code className="w-3.5 h-3.5 mr-1 text-blue-600" /> Backend Controller Source
            </span>
            <code className="text-xs font-mono text-blue-700 bg-blue-50/80 px-2 py-1 rounded-sm border border-blue-200/50 block truncate">
              {controller || 'App\\Http\\Controllers\\Admin\\' + (moduleKey || 'AdminController')}
            </code>
          </div>

          <div className="bg-slate-50 p-4 rounded-lg border border-slate-100">
            <span className="text-[11px] font-bold text-slate-700 uppercase tracking-wider block mb-2 flex items-center">
              <Shield className="w-3.5 h-3.5 mr-1 text-emerald-600" /> API Foundation Status
            </span>
            <div className="flex items-center space-x-2 text-xs text-emerald-700 font-semibold">
              <CheckCircle className="w-4 h-4 text-emerald-500" />
              <span>Phase 3 Service & Normalizer Ready</span>
            </div>
          </div>
        </div>

        {features.length > 0 && (
          <div className="mt-6 pt-4 border-t border-slate-100">
            <h4 className="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">
              Planned UI Capabilities:
            </h4>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-slate-600">
              {features.map((feat, idx) => (
                <div key={idx} className="flex items-center space-x-2">
                  <div className="w-1.5 h-1.5 rounded-full bg-blue-500" />
                  <span>{feat}</span>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

export default ModulePlaceholderPage;
