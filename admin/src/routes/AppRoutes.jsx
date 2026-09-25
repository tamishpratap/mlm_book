import { Routes, Route, Navigate } from 'react-router-dom';
import { ProtectedRoute } from './ProtectedRoute';
import { AdminLayout } from '../layouts/AdminLayout';
import { AuthLayout } from '../layouts/AuthLayout';

// Core Pages
import { LoginPage } from '../pages/auth/LoginPage';
import { DashboardPage } from '../pages/dashboard/DashboardPage';
import { NotFoundPage } from '../pages/common/NotFoundPage';
import { UnauthorizedPage } from '../pages/common/UnauthorizedPage';

// Members Module Pages
import { MembersListPage } from '../pages/members/MembersListPage';
import { MemberDetailsPage } from '../pages/members/MemberDetailsPage';
import { MemberEditPage } from '../pages/members/MemberEditPage';

// Posts & Timeline Moderation Module Pages
import { PostsListPage } from '../pages/posts/PostsListPage';
import { PostDetailsPage } from '../pages/posts/PostDetailsPage';

// Stories Management Module Pages
import { AllStoriesPage, LiveStoriesPage, ExpiredStoriesPage } from '../pages/stories';

// Business Pages & Categories Module Pages
import { BusinessPagesListPage } from '../pages/businessPages/BusinessPagesListPage';
import { BusinessPageDetailsPage } from '../pages/businessPages/BusinessPageDetailsPage';
import { BusinessCategoriesPage } from '../pages/businessPages/BusinessCategoriesPage';
import { BusinessCategoryCreatePage } from '../pages/businessPages/BusinessCategoryCreatePage';

// Advertising Campaigns Management Module Pages
import { AdCampaignsListPage } from '../pages/ads/AdCampaignsListPage';
import { AdCampaignAnalyticsPage } from '../pages/ads/AdCampaignAnalyticsPage';
import { AdminCampaignSettingsPage } from '../pages/ads/AdminCampaignSettingsPage';
import { AdminRewardHistoryPage } from '../pages/ads/AdminRewardHistoryPage';
import { AdRewardRulesPage } from '../pages/ads/AdRewardRulesPage';

// Centralized Reward Management Pages
import { RewardRulesPage, RewardHistoryPage } from '../pages/rewards';

// Funds & Deposit Management Pages
import { DepositSettingsPage } from '../pages/funds/DepositSettingsPage';
import { DepositsListPage } from '../pages/funds/DepositsListPage';
import { WithdrawalsListPage } from '../pages/funds/WithdrawalsListPage';

// Communities Module Pages
import { CommunitiesListPage } from '../pages/communities/CommunitiesListPage';
import { CommunityDetailsPage } from '../pages/communities/CommunityDetailsPage';

// Marketplace & Financial Commerce Pages
import { MarketplaceListPage } from '../pages/marketplace/MarketplaceListPage';
import { ProductDetailsPage } from '../pages/marketplace/ProductDetailsPage';

// Events Module Pages - Temporarily Disabled
// import { EventsListPage } from '../pages/events/EventsListPage';
// import { EventDetailsPage } from '../pages/events/EventDetailsPage';
// import { EventRewardRulesPage } from '../pages/events/EventRewardRulesPage';
// import { EventCampaignsControlCenterPage } from '../pages/events/EventCampaignsControlCenterPage';

// Central Moderation Reports Pages
import { ReportsListPage } from '../pages/reports/ReportsListPage';
import { ReportDetailsPage } from '../pages/reports/ReportDetailsPage';
import { FeedbackSuggestionsListPage } from '../pages/feedback/FeedbackSuggestionsListPage';

// Roles & Permissions (RBAC) Module Pages
import { RolesListPage } from '../pages/roles/RolesListPage';
import { RoleCreatePage } from '../pages/roles/RoleCreatePage';
import { RoleEditPage } from '../pages/roles/RoleEditPage';
import { PermissionMatrixPage } from '../pages/roles/PermissionMatrixPage';

// Notifications & Communications Pages
import { NotificationsListPage } from '../pages/notifications/NotificationsListPage';
import { BroadcastComposerPage } from '../pages/notifications/BroadcastComposerPage';
import { NotificationDetailsPage } from '../pages/notifications/NotificationDetailsPage';

// Analytics & BI Module Pages
import { AnalyticsPage } from '../pages/analytics/AnalyticsPage';

// Settings & Configuration Pages
import { SettingsPage } from '../pages/settings/SettingsPage';

// System Tools & Diagnostics Pages
import { SystemToolsPage } from '../pages/system/SystemToolsPage';
import { SystemLogsPage } from '../pages/system/SystemLogsPage';

export function AppRoutes() {
  return (
    <Routes>
      {/* Root redirect */}
      <Route path="/" element={<Navigate to="/admin/dashboard" replace />} />

      {/* Guest Authentication Routes */}
      <Route element={<AuthLayout />}>
        <Route path="/login" element={<Navigate to="/admin/login" replace />} />
        <Route path="/admin/login" element={<LoginPage />} />
      </Route>

      {/* Protected Admin Routes */}
      <Route
        path="/admin"
        element={
          <ProtectedRoute>
            <AdminLayout />
          </ProtectedRoute>
        }
      >
        <Route index element={<Navigate to="/admin/dashboard" replace />} />
        <Route path="dashboard" element={<DashboardPage />} />

        {/* Members Management Module */}
        <Route path="members" element={<MembersListPage defaultMode="active" />} />
        <Route path="members/active" element={<Navigate to="/admin/members" replace />} />
        <Route path="members/pending" element={<MembersListPage defaultMode="pending" />} />
        <Route path="members/blocked" element={<MembersListPage defaultMode="blocked" />} />
        <Route path="members/:id" element={<MemberDetailsPage />} />
        <Route path="members/:id/edit" element={<MemberEditPage />} />

        {/* Posts & Moderation Module */}
        <Route path="posts" element={<PostsListPage />} />
        <Route path="posts/:id" element={<PostDetailsPage />} />

        {/* Stories Management Module */}
        <Route path="stories" element={<AllStoriesPage />} />
        <Route path="stories/live" element={<LiveStoriesPage />} />
        <Route path="stories/expired" element={<ExpiredStoriesPage />} />

        {/* Business Pages & Categories Module */}
        <Route path="business-pages" element={<BusinessPagesListPage />} />
        <Route path="business-pages/categories" element={<BusinessCategoriesPage />} />
        <Route path="business-pages/categories/create" element={<BusinessCategoryCreatePage />} />
        <Route path="business-pages/category/create" element={<Navigate to="/admin/business-pages/categories/create" replace />} />
        <Route path="business-pages/:id" element={<BusinessPageDetailsPage />} />

        {/* Centralized Reward Management Module */}
        <Route path="rewards" element={<Navigate to="/admin/rewards/rules" replace />} />
        <Route path="rewards/rules" element={<RewardRulesPage />} />
        <Route path="rewards/history" element={<RewardHistoryPage />} />

        {/* Advertising Campaigns Management Module */}
        <Route path="ad-campaigns" element={<AdCampaignsListPage />} />
        <Route path="ad-campaigns/analytics" element={<AdCampaignAnalyticsPage />} />
        <Route path="ad-campaigns/rewards" element={<Navigate to="/admin/rewards/history?tab=ads" replace />} />
        <Route path="ad-campaigns/reward-rules" element={<Navigate to="/admin/rewards/rules" replace />} />
        <Route path="ad-campaigns/rules" element={<Navigate to="/admin/rewards/rules" replace />} />
        <Route path="ad-campaigns/settings" element={<AdminCampaignSettingsPage />} />

        {/* Funds & Deposit Management Module */}
        <Route path="funds/deposit-settings" element={<DepositSettingsPage />} />
        <Route path="funds/deposits" element={<DepositsListPage />} />
        <Route path="funds/withdrawals" element={<WithdrawalsListPage />} />
        <Route path="funds" element={<Navigate to="/admin/funds/withdrawals" replace />} />
        <Route path="withdrawals" element={<Navigate to="/admin/funds/withdrawals" replace />} />
        <Route path="funds" element={<Navigate to="/admin/funds/deposits" replace />} />

        {/* Communities Module */}
        <Route path="communities" element={<CommunitiesListPage />} />
        <Route path="communities/:id" element={<CommunityDetailsPage />} />

        {/* Marketplace & Financial Commerce Module */}
        <Route path="marketplace" element={<MarketplaceListPage />} />
        <Route path="marketplace/:id" element={<ProductDetailsPage />} />

        {/* Events Module - Temporarily Disabled (Redirect to Dashboard) */}
        {/*
        <Route path="events" element={<EventsListPage />} />
        <Route path="events/campaigns" element={<EventCampaignsControlCenterPage />} />
        <Route path="events/reward-rules" element={<Navigate to="/admin/rewards/rules" replace />} />
        <Route path="events/:id" element={<EventDetailsPage />} />
        */}
        <Route path="events" element={<Navigate to="/admin/dashboard" replace />} />
        <Route path="events/*" element={<Navigate to="/admin/dashboard" replace />} />

        {/* Central Moderation Reports Queue */}
        <Route path="reports" element={<ReportsListPage />} />
        <Route path="reports/:type/:id" element={<ReportDetailsPage />} />

        {/* Feedback & Suggestions Module */}
        <Route path="feedback-suggestions" element={<FeedbackSuggestionsListPage />} />

        {/* Platform Settings Module */}
        <Route path="settings" element={<SettingsPage />} />

        {/* Roles & RBAC Module */}
        <Route path="roles" element={<RolesListPage />} />
        <Route path="roles/create" element={<RoleCreatePage />} />
        <Route path="roles/matrix" element={<PermissionMatrixPage />} />
        <Route path="roles/:id/edit" element={<RoleEditPage />} />

        {/* Notifications & Communication Center */}
        <Route path="notifications" element={<NotificationsListPage />} />
        <Route path="notifications/broadcast" element={<BroadcastComposerPage />} />
        <Route path="notifications/:id" element={<NotificationDetailsPage />} />

        {/* Analytics & BI Module */}
        <Route path="analytics" element={<AnalyticsPage />} />

        {/* System Diagnostics & Logs Module */}
        <Route path="system" element={<SystemToolsPage />} />
        <Route path="system/logs" element={<SystemLogsPage />} />

        {/* Catch-all within Admin */}
        <Route path="*" element={<NotFoundPage />} />
      </Route>

      {/* Unauthorized access page */}
      <Route path="/unauthorized" element={<UnauthorizedPage />} />

      {/* Global 404 catch-all */}
      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  );
}

export default AppRoutes;
