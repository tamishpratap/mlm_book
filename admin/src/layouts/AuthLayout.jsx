import { Outlet } from 'react-router-dom';

export function AuthLayout() {
  return (
    <main className="min-h-screen w-full bg-[#0A0F1F] text-[#F8FAFC] relative overflow-x-hidden">
      <Outlet />
    </main>
  );
}

export default AuthLayout;
