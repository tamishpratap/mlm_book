import { Mail, HardDrive, CheckCircle2 } from 'lucide-react';

export function MailStorageTab({ systemInfo = {} }) {
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
      {/* Mail Server Card */}
      <div className="bg-slate-50 p-5 rounded-2xl border border-slate-200/80 space-y-4">
        <div className="flex items-center space-x-2 border-b border-slate-200/60 pb-3">
          <Mail className="w-4 h-4 text-blue-600" />
          <h6 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
            Mail Server Configuration
          </h6>
        </div>

        <div className="space-y-2 text-xs">
          <div className="flex justify-between py-1.5 border-b border-slate-200/40">
            <span className="text-slate-500 font-medium">Mail Driver:</span>
            <code className="bg-white px-2 py-0.5 rounded border border-slate-200 text-blue-600 font-mono">
              {systemInfo.mail_driver || 'smtp'}
            </code>
          </div>
          <div className="flex justify-between py-1.5 border-b border-slate-200/40">
            <span className="text-slate-500 font-medium">SMTP Host:</span>
            <span className="text-slate-800 font-bold">smtp.mailtrap.io</span>
          </div>
          <div className="flex justify-between py-1.5 border-b border-slate-200/40">
            <span className="text-slate-500 font-medium">SMTP Port:</span>
            <span className="text-slate-800 font-bold">2525</span>
          </div>
          <div className="flex justify-between py-1.5">
            <span className="text-slate-500 font-medium">From Address:</span>
            <span className="text-slate-800 font-semibold">noreply@mlmbook.com</span>
          </div>
        </div>
      </div>

      {/* Storage Driver Card */}
      <div className="bg-slate-50 p-5 rounded-2xl border border-slate-200/80 space-y-4">
        <div className="flex items-center space-x-2 border-b border-slate-200/60 pb-3">
          <HardDrive className="w-4 h-4 text-emerald-600" />
          <h6 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
            Storage Driver Status
          </h6>
        </div>

        <div className="space-y-2 text-xs">
          <div className="flex justify-between py-1.5 border-b border-slate-200/40">
            <span className="text-slate-500 font-medium">Default Disk:</span>
            <span className="inline-flex items-center text-xs font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
              local
            </span>
          </div>
          <div className="flex justify-between py-1.5 border-b border-slate-200/40">
            <span className="text-slate-500 font-medium">Public Storage:</span>
            <code className="bg-white px-2 py-0.5 rounded border border-slate-200 text-slate-700 font-mono">
              storage/app/public
            </code>
          </div>
          <div className="flex justify-between py-1.5">
            <span className="text-slate-500 font-medium">Symlink Status:</span>
            <span className="inline-flex items-center text-xs font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
              <CheckCircle2 className="w-3 h-3 mr-1 text-emerald-600" /> Linked
            </span>
          </div>
        </div>
      </div>
    </div>
  );
}

export default MailStorageTab;
