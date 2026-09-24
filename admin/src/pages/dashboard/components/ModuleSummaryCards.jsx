import { Link } from 'react-router-dom';
import { FileText, Briefcase, Shield } from 'lucide-react';

export function ModuleSummaryCards({ data = {} }) {
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      {/* SECTION 2: Content Overview */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden flex flex-col justify-between">
        <div className="px-4 py-3.5 border-b border-slate-100 flex items-center justify-between">
          <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center">
            <FileText className="w-4 h-4 text-blue-600 mr-2" /> Content Overview
          </h4>
          <Link to="/admin/posts" className="text-xs font-semibold text-blue-600 hover:text-blue-700">
            View All
          </Link>
        </div>
        <div className="p-4 divide-y divide-slate-100 text-xs">
          <div className="py-2 flex items-center justify-between">
            <span className="text-slate-500">Total Posts</span>
            <strong className="text-slate-900 font-bold">{(data.totalPosts || 0).toLocaleString()}</strong>
          </div>
          <div className="py-2 flex items-center justify-between">
            <span className="text-slate-500">Posts Created Today</span>
            <span className="bg-blue-50 text-blue-700 font-bold px-2 py-0.5 rounded-full border border-blue-200/60 text-[11px]">
              {(data.postsToday || 0).toLocaleString()}
            </span>
          </div>
          <div className="py-2 flex items-center justify-between">
            <span className="text-slate-500">Posts This Week</span>
            <span className="bg-sky-50 text-sky-700 font-bold px-2 py-0.5 rounded-full border border-sky-200/60 text-[11px]">
              {(data.postsThisWeek || 0).toLocaleString()}
            </span>
          </div>
          <div className="py-2 flex items-center justify-between">
            <span className="text-slate-500">Active Live Stories</span>
            <span className="bg-emerald-50 text-emerald-700 font-bold px-2 py-0.5 rounded-full border border-emerald-200/60 text-[11px]">
              {(data.activeStories || 0).toLocaleString()}
            </span>
          </div>
          <div className="py-2 flex items-center justify-between">
            <span className="text-slate-500">Stories Posted Today</span>
            <span className="text-slate-900 font-semibold">{(data.storiesToday || 0).toLocaleString()}</span>
          </div>
          <div className="pt-2 flex items-center justify-between">
            <span className="text-slate-500">Media & Image Posts</span>
            <strong className="text-slate-900 font-bold">{(data.mediaPosts || 0).toLocaleString()}</strong>
          </div>
        </div>
      </div>

      {/* SECTION 3 & 4: Business Pages & Communities */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden flex flex-col justify-between">
        <div className="px-4 py-3.5 border-b border-slate-100 flex items-center justify-between">
          <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center">
            <Briefcase className="w-4 h-4 text-indigo-600 mr-2" /> Business & Communities
          </h4>
          <Link to="/admin/business-pages" className="text-xs font-semibold text-blue-600 hover:text-blue-700">
            Manage
          </Link>
        </div>
        <div className="p-4 divide-y divide-slate-100 text-xs">
          <div className="py-2 flex items-center justify-between">
            <span className="text-slate-500">Total Business Pages</span>
            <strong className="text-slate-900 font-bold">{(data.totalBusinessPages || 0).toLocaleString()}</strong>
          </div>
          <div className="py-2 flex items-center justify-between">
            <span className="text-slate-500">Verified Business Pages</span>
            <span className="bg-emerald-50 text-emerald-700 font-bold px-2 py-0.5 rounded-full border border-emerald-200/60 text-[11px]">
              {(data.verifiedBusinessPages || 0).toLocaleString()}
            </span>
          </div>
          <div className="py-2 flex items-center justify-between">
            <span className="text-slate-500">Pending Verification</span>
            <span className="bg-amber-50 text-amber-700 font-bold px-2 py-0.5 rounded-full border border-amber-200/60 text-[11px]">
              {(data.pendingBusinessPages || 0).toLocaleString()}
            </span>
          </div>
          <div className="py-2 flex items-center justify-between">
            <span className="text-slate-500">Total Communities</span>
            <strong className="text-slate-900 font-bold">{(data.totalCommunities || 0).toLocaleString()}</strong>
          </div>
          <div className="py-2 flex items-center justify-between">
            <span className="text-slate-500">Community Memberships</span>
            <span className="bg-blue-50 text-blue-700 font-bold px-2 py-0.5 rounded-full border border-blue-200/60 text-[11px]">
              {(data.totalCommunityMembers || 0).toLocaleString()}
            </span>
          </div>
          <div className="pt-2 flex items-center justify-between">
            <span className="text-slate-500">Pending Join Requests</span>
            <span className="bg-slate-100 text-slate-700 font-bold px-2 py-0.5 rounded-full border border-slate-200 text-[11px]">
              {(data.pendingCommunityRequests || 0).toLocaleString()}
            </span>
          </div>
        </div>
      </div>

      {/* SECTION 5, 6, 7: Marketplace, Events & Moderation */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden flex flex-col justify-between">
        <div className="px-4 py-3.5 border-b border-slate-100 flex items-center justify-between">
          <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center">
            <Shield className="w-4 h-4 text-amber-600 mr-2" /> Marketplace & Trust
          </h4>
          <Link to="/admin/reports" className="text-xs font-semibold text-blue-600 hover:text-blue-700">
            Reports
          </Link>
        </div>
        <div className="p-4 divide-y divide-slate-100 text-xs">
          <div className="py-2 flex items-center justify-between">
            <span className="text-slate-500">Marketplace Listings</span>
            <strong className="text-slate-900 font-bold">{(data.totalProducts || 0).toLocaleString()}</strong>
          </div>
          <div className="py-2 flex items-center justify-between">
            <span className="text-slate-500">Active Products</span>
            <span className="bg-emerald-50 text-emerald-700 font-bold px-2 py-0.5 rounded-full border border-emerald-200/60 text-[11px]">
              {(data.activeProducts || 0).toLocaleString()}
            </span>
          </div>
          <div className="py-2 flex items-center justify-between">
            <span className="text-slate-500">Upcoming Events</span>
            <span className="bg-sky-50 text-sky-700 font-bold px-2 py-0.5 rounded-full border border-sky-200/60 text-[11px]">
              {(data.upcomingEventsCount || 0).toLocaleString()}
            </span>
          </div>
          <div className="py-2 flex items-center justify-between">
            <span className="text-slate-500">Events This Month</span>
            <span className="bg-blue-50 text-blue-700 font-bold px-2 py-0.5 rounded-full border border-blue-200/60 text-[11px]">
              {(data.eventsThisMonthCount || 0).toLocaleString()}
            </span>
          </div>
          <div className="py-2 flex items-center justify-between">
            <span className="text-slate-500">Pending Content Reports</span>
            {(data.pendingReports || 0) > 0 ? (
              <span className="bg-red-500 text-white font-bold px-2 py-0.5 rounded-full text-[10px]">
                {data.pendingReports} Action Required
              </span>
            ) : (
              <span className="bg-emerald-50 text-emerald-700 font-semibold px-2 py-0.5 rounded-full border border-emerald-200 text-[10px]">
                0 Clean
              </span>
            )}
          </div>
          <div className="pt-2 flex items-center justify-between">
            <span className="text-slate-500">Blocked Members</span>
            <span className="text-slate-600 font-medium">{(data.blockedMembers || 0).toLocaleString()}</span>
          </div>
        </div>
      </div>
    </div>
  );
}

export default ModuleSummaryCards;
