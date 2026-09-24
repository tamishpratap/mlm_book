export function AdminFooter() {
  const currentYear = new Date().getFullYear();

  return (
    <footer className="h-12 bg-white border-t border-slate-200 px-4 lg:px-6 flex items-center justify-between text-xs text-slate-500 shrink-0">
      <div>
        <span>&copy; {currentYear} </span>
        <strong className="text-slate-700 font-semibold">MLM_Book Platform</strong>
        <span>. All rights reserved.</span>
      </div>

      <div className="flex items-center space-x-4">
        <span className="hidden sm:inline text-slate-500 font-medium">Laravel 12 + React 19 Admin Portal</span>
        <span className="bg-emerald-50 text-emerald-800 text-[11px] font-bold px-2.5 py-0.5 rounded-full border border-emerald-300">
          Operational
        </span>
      </div>
    </footer>
  );
}

export default AdminFooter;
