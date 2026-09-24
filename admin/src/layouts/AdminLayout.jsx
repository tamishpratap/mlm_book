import { useState } from 'react';
import { Outlet } from 'react-router-dom';
import { ConfirmDialog } from 'primereact/confirmdialog';
import { AdminHeader } from '../components/layout/AdminHeader';
import { AdminSidebar } from '../components/layout/AdminSidebar';
import { AdminFooter } from '../components/layout/AdminFooter';
import { ErrorBoundary } from '../components/common/ErrorBoundary';

export function AdminLayout() {
  const [mobileSidebarOpen, setMobileSidebarOpen] = useState(false);
  const [desktopSidebarCollapsed, setDesktopSidebarCollapsed] = useState(false);

  return (
    <div className="flex h-screen w-screen overflow-hidden bg-slate-50 text-slate-800">
      {/* Global ConfirmDialog for delete, status change, and logout confirmations */}
      <ConfirmDialog />

      {/* Responsive Sidebar */}
      <AdminSidebar
        mobileOpen={mobileSidebarOpen}
        onCloseMobile={() => setMobileSidebarOpen(false)}
        collapsed={desktopSidebarCollapsed}
      />

      {/* Main Column */}
      <div className="flex flex-col flex-1 min-w-0 h-full overflow-hidden">
        {/* Top Header */}
        <AdminHeader
          onToggleMobileSidebar={() => setMobileSidebarOpen((prev) => !prev)}
          onToggleDesktopSidebar={() => setDesktopSidebarCollapsed((prev) => !prev)}
          sidebarCollapsed={desktopSidebarCollapsed}
        />

        {/* Scrollable Page Body */}
        <main className="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
          <div className="max-w-7xl 2xl:max-w-[1600px] mx-auto w-full">
            <ErrorBoundary>
              <Outlet />
            </ErrorBoundary>
          </div>
        </main>

        {/* Footer */}
        <AdminFooter />
      </div>
    </div>
  );
}

export default AdminLayout;
