import { useRef } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { Settings, Cpu, LogOut, ChevronDown, ShieldCheck } from 'lucide-react';
import { OverlayPanel } from 'primereact/overlaypanel';
import { useAuth } from '../../hooks/useAuth';
import { confirmHelper } from '../../utils/confirmHelper';

export function ProfileDropdown() {
  const op = useRef(null);
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = () => {
    op.current?.hide();
    confirmHelper.confirm({
      header: 'Confirm Sign Out',
      message: 'Are you sure you want to sign out of the administrator portal?',
      icon: 'pi pi-exclamation-triangle text-amber-500',
      acceptLabel: 'Sign Out',
      rejectLabel: 'Cancel',
      acceptClassName: 'p-button-danger text-xs',
      onAccept: async () => {
        await logout();
        navigate('/admin/login');
      },
    });
  };

  const displayName = user?.name || 'Administrator';
  const displayEmail = user?.email || 'admin@mlmbook.com';
  const isSuperAdmin = Boolean(user?.is_super_admin || user?.role === 'super-admin');
  const roleName = isSuperAdmin ? 'Super Admin' : (user?.role || 'Admin');
  const initial = displayName.charAt(0).toUpperCase();

  return (
    <div>
      <button
        type="button"
        onClick={(e) => op.current.toggle(e)}
        className="flex items-center space-x-2.5 p-1.5 rounded-lg hover:bg-slate-100 transition-colors focus:outline-hidden cursor-pointer"
        aria-label="User Profile"
      >
        <div className="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0">
          {initial}
        </div>
        <div className="hidden sm:flex flex-col text-left">
          <span className="text-xs font-bold text-slate-800 leading-tight truncate max-w-[120px]">{displayName}</span>
          <span className="text-[10px] text-blue-600 font-semibold flex items-center">
            {roleName} <ChevronDown className="w-3 h-3 ml-0.5" />
          </span>
        </div>
      </button>

      <OverlayPanel ref={op} className="w-64 p-0 shadow-2xl rounded-xl border border-slate-200 overflow-hidden">
        {/* User Card */}
        <div className="p-4 bg-slate-900 text-white">
          <div className="flex items-center space-x-3">
            <div className="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm shrink-0">
              {initial}
            </div>
            <div className="overflow-hidden">
              <h4 className="text-sm font-bold truncate">{displayName}</h4>
              <p className="text-[11px] text-slate-400 truncate">{displayEmail}</p>
              <span className="inline-flex items-center mt-1 px-1.5 py-0.2 text-[9px] font-semibold bg-blue-500/20 text-blue-300 rounded-sm border border-blue-500/30">
                <ShieldCheck className="w-2.5 h-2.5 mr-0.5" />
                {roleName}
              </span>
            </div>
          </div>
        </div>

        {/* Links */}
        <div className="p-1.5 divide-y divide-slate-100 text-xs">
          <div className="py-1">
            <Link
              to="/admin/settings"
              onClick={() => op.current.hide()}
              className="flex items-center space-x-2.5 px-3 py-2 text-slate-700 hover:bg-slate-50 rounded-md transition-colors"
            >
              <Settings className="w-4 h-4 text-slate-400" />
              <span>Platform Settings</span>
            </Link>
            <Link
              to="/admin/system"
              onClick={() => op.current.hide()}
              className="flex items-center space-x-2.5 px-3 py-2 text-slate-700 hover:bg-slate-50 rounded-md transition-colors"
            >
              <Cpu className="w-4 h-4 text-slate-400" />
              <span>System Tools & Logs</span>
            </Link>
          </div>

          <div className="pt-1">
            <button
              onClick={handleLogout}
              className="w-full flex items-center space-x-2.5 px-3 py-2 text-red-600 hover:bg-red-50 rounded-md transition-colors font-medium text-left cursor-pointer"
            >
              <LogOut className="w-4 h-4" />
              <span>Sign Out</span>
            </button>
          </div>
        </div>
      </OverlayPanel>
    </div>
  );
}

export default ProfileDropdown;
