import { Routes, Route, Navigate, useParams } from 'react-router-dom';

// Layouts
import MemberLayout from '../layouts/MemberLayout';
import AuthLayout from '../layouts/AuthLayout';

// Guards
import ProtectedMemberRoute from '../components/common/ProtectedMemberRoute';
import PublicMemberRoute from '../components/common/PublicMemberRoute';

// Auth Pages
import LoginPage from '../pages/auth/LoginPage';
import RegisterPage from '../pages/auth/RegisterPage';
import GoogleIntroducerPage from '../pages/auth/GoogleIntroducerPage';
import VerifyEmailPage from '../pages/auth/VerifyEmailPage';
import ForgotPasswordPage from '../pages/auth/ForgotPasswordPage';
import ResetPasswordPage from '../pages/auth/ResetPasswordPage';

// Member Pages
import DashboardPage from '../pages/dashboard/DashboardPage';
import SocialsFeedPage from '../pages/socials/SocialsFeedPage';
import SavedPostsPage from '../pages/socials/SavedPostsPage';
import PostDetailPage from '../pages/socials/PostDetailPage';
import StoriesPage from '../pages/stories/StoriesPage';
import WatchPage from '../pages/watch/WatchPage';
import CreateVideoPage from '../pages/watch/CreateVideoPage';
import MyProfilePage from '../pages/profile/MyProfilePage';
import EditProfilePage from '../pages/profile/EditProfilePage';
import ProfileVisitorsPage from '../pages/profile/ProfileVisitorsPage';
import MemberProfilePage from '../pages/profile/MemberProfilePage';
import FriendsPage from '../pages/friends/FriendsPage';
import FriendRequestsPage from '../pages/friends/FriendRequestsPage';
import SuggestedConnectionsPage from '../pages/friends/SuggestedConnectionsPage';
import BlockedUsersPage from '../pages/friends/BlockedUsersPage';
import NotificationsPage from '../pages/notifications/NotificationsPage';
import SearchPage from '../pages/search/SearchPage';
// Marketplace Pages - Temporarily Disabled (Preserved for future recovery)
// import MarketplacePage from '../pages/marketplace/MarketplacePage';
// import ProductDetailsPage from '../pages/marketplace/ProductDetailsPage';
// import CreateProductPage from '../pages/marketplace/CreateProductPage';
// import EditProductPage from '../pages/marketplace/EditProductPage';
// import MyProductsPage from '../pages/marketplace/MyProductsPage';
// import SavedProductsPage from '../pages/marketplace/SavedProductsPage';
import CommunitiesPage from '../pages/community/CommunitiesPage';
import CommunityDiscoveryPage from '../pages/community/CommunityDiscoveryPage';
import CommunityDetailPage from '../pages/community/CommunityDetailPage';
import CreateCommunityPage from '../pages/community/CreateCommunityPage';
import EditCommunityPage from '../pages/community/EditCommunityPage';
import CommunityAdminPanelPage from '../pages/community/CommunityAdminPanelPage';
import CommunityNotificationsPage from '../pages/community/CommunityNotificationsPage';
import CommunityActivityTimelinePage from '../pages/community/CommunityActivityTimelinePage';
import CommunityAnalyticsPage from '../pages/community/CommunityAnalyticsPage';
// Event Module Pages - Temporarily Disabled
// import EventsPage from '../pages/events/EventsPage';
// import CreateEventPage from '../pages/events/CreateEventPage';
// import EventDetailPage from '../pages/events/EventDetailPage';
// import EditEventPage from '../pages/events/EditEventPage';
// import EventOutreachPage from '../pages/events/EventOutreachPage';
import BusinessPagesPage from '../pages/business/BusinessPagesPage';
import CreateBusinessPage from '../pages/business/CreateBusinessPage';
import EditBusinessPage from '../pages/business/EditBusinessPage';
import BusinessDetailPage from '../pages/business/BusinessDetailPage';
import BusinessTeamPage from '../pages/business/BusinessTeamPage';
import BusinessInboxPage from '../pages/business/BusinessInboxPage';
import BusinessVerificationPage from '../pages/business/BusinessVerificationPage';
import BusinessNotificationsPage from '../pages/business/BusinessNotificationsPage';
import BusinessAnalyticsPage from '../pages/business/BusinessAnalyticsPage';
import BusinessDirectoryPage from '../pages/business/BusinessDirectoryPage';
import BusinessCategoryPage from '../pages/business/BusinessCategoryPage';
import AdCampaignPeopleEngagedPage from '../pages/business/AdCampaignPeopleEngagedPage';
import DirectMessagesPage from '../pages/messages/DirectMessagesPage';
import AccountSettingsPage from '../pages/account/AccountSettingsPage';
import SecuritySettingsPage from '../pages/account/SecuritySettingsPage';
import AccountVerificationPage from '../pages/account/AccountVerificationPage';
import Web3WalletPage from '../pages/wallet/Web3WalletPage';
import DepositPage from '../pages/deposit/DepositPage';
import FeedbackSuggestionsPage from '../pages/feedback/FeedbackSuggestionsPage';
import WithdrawalPage from '../pages/wallet/WithdrawalPage';

function LegacyGroupRedirect() {
  const { slug } = useParams();
  return <Navigate to={slug ? `/member/community/${slug}` : '/member/community'} replace />;
}

export function AppRoutes() {
  return (
    <Routes>
      {/* Root redirect */}
      <Route path="/" element={<Navigate to="/member/dashboard" replace />} />

      {/* Public Auth Routes */}
      <Route element={<PublicMemberRoute />}>
        <Route element={<AuthLayout />}>
          <Route path="/member/login" element={<LoginPage />} />
          <Route path="/member/register" element={<RegisterPage />} />
          <Route path="/member/google-introducer" element={<GoogleIntroducerPage />} />
          <Route path="/member/register/verify" element={<VerifyEmailPage />} />
          <Route path="/member/forgot-password" element={<ForgotPasswordPage />} />
          <Route path="/member/reset-password/:token" element={<ResetPasswordPage />} />
        </Route>
      </Route>

      {/* Protected Member Routes */}
      <Route element={<ProtectedMemberRoute />}>
        <Route element={<MemberLayout />}>
          <Route path="/member/dashboard" element={<DashboardPage />} />
          <Route path="/member/socials" element={<SocialsFeedPage />} />
          <Route path="/member/stories" element={<StoriesPage />} />
          <Route path="/member/stories/:id" element={<StoriesPage />} />
          <Route path="/member/posts" element={<SocialsFeedPage />} />
          <Route path="/member/posts/:id" element={<PostDetailPage />} />
          <Route path="/member/saved-posts" element={<SavedPostsPage />} />
          <Route path="/member/watch" element={<WatchPage />} />
          <Route path="/member/create-video" element={<Navigate to="/member/watch" replace />} />
          <Route path="/member/videos/create" element={<Navigate to="/member/watch" replace />} />
          <Route path="/member/watch/create" element={<Navigate to="/member/watch" replace />} />
          <Route path="/member/profile" element={<MyProfilePage />} />
          <Route path="/member/profile/edit" element={<EditProfilePage />} />
          <Route path="/member/profile/visitors" element={<ProfileVisitorsPage />} />
          <Route path="/member/people/suggestions" element={<SuggestedConnectionsPage />} />
          <Route path="/member/people/:id" element={<MemberProfilePage />} />
          <Route path="/member/profile/:id" element={<MemberProfilePage />} />
          <Route path="/member/connections" element={<FriendsPage />} />
          <Route path="/member/friends" element={<FriendsPage />} />
          <Route path="/member/friend-requests" element={<FriendRequestsPage />} />
          <Route path="/member/blocked-users" element={<BlockedUsersPage />} />
          <Route path="/member/disconnections" element={<BlockedUsersPage />} />
          <Route path="/member/notifications" element={<NotificationsPage />} />
          <Route path="/member/search" element={<SearchPage />} />
          {/* Marketplace Routes - Temporarily Disabled (Redirect to Dashboard) */}
          {/*
          <Route path="/member/marketplace" element={<MarketplacePage />} />
          <Route path="/member/marketplace/create" element={<CreateProductPage />} />
          <Route path="/member/marketplace/my-products" element={<MyProductsPage />} />
          <Route path="/member/marketplace/saved" element={<SavedProductsPage />} />
          <Route path="/member/marketplace/:id" element={<ProductDetailsPage />} />
          <Route path="/member/marketplace/:id/edit" element={<EditProductPage />} />
          */}
          <Route path="/member/marketplace" element={<Navigate to="/member/dashboard" replace />} />
          <Route path="/member/marketplace/*" element={<Navigate to="/member/dashboard" replace />} />
          <Route path="/member/community" element={<CommunitiesPage />} />
          <Route path="/member/community/discover" element={<CommunityDiscoveryPage />} />
          <Route path="/member/community/create" element={<CreateCommunityPage />} />
          <Route path="/member/community/notifications" element={<CommunityNotificationsPage />} />
          <Route path="/member/community/:slug" element={<CommunityDetailPage />} />
          <Route path="/member/community/:slug/edit" element={<EditCommunityPage />} />
          <Route path="/member/community/:slug/admin" element={<CommunityAdminPanelPage />} />
          <Route path="/member/community/:slug/activity" element={<CommunityActivityTimelinePage />} />
          <Route path="/member/community/:slug/analytics" element={<CommunityAnalyticsPage />} />
          {/* Legacy Group Route Aliases / Redirects to Community */}
          <Route path="/member/groups" element={<Navigate to="/member/community" replace />} />
          <Route path="/member/groups/create" element={<Navigate to="/member/community/create" replace />} />
          <Route path="/member/groups/:slug" element={<LegacyGroupRedirect />} />
          {/* Event Routes - Temporarily Disabled (Redirect to Dashboard) */}
          {/*
          <Route path="/member/events" element={<EventsPage />} />
          <Route path="/member/events/add-fund" element={<EventsPage defaultTab="add-fund" />} />
          <Route path="/member/events/create" element={<CreateEventPage />} />
          <Route path="/member/events/:id" element={<EventDetailPage />} />
          <Route path="/member/events/:id/edit" element={<EditEventPage />} />
          <Route path="/member/events/:id/outreach" element={<EventOutreachPage />} />
          <Route path="/member/events/:id/attendees" element={<EventOutreachPage />} />
          */}
          <Route path="/member/events" element={<Navigate to="/member/dashboard" replace />} />
          <Route path="/member/events/*" element={<Navigate to="/member/dashboard" replace />} />
          <Route path="/member/business-pages" element={<BusinessPagesPage />} />
          <Route path="/member/business-pages/create" element={<CreateBusinessPage />} />
          <Route path="/member/business-pages/:slug" element={<BusinessDetailPage />} />
          <Route path="/member/business-pages/:slug/edit" element={<EditBusinessPage />} />
          <Route path="/member/business-pages/:slug/team" element={<BusinessTeamPage />} />
          <Route path="/member/business-pages/:slug/inbox" element={<BusinessInboxPage />} />
          <Route path="/member/business-pages/:slug/notifications" element={<BusinessNotificationsPage />} />
          <Route path="/member/business-pages/:slug/analytics" element={<BusinessAnalyticsPage />} />
          <Route path="/member/business-pages/:slug/verification" element={<BusinessVerificationPage />} />
          <Route path="/member/business-pages/:slug/ads/:campaignId/people-engaged" element={<AdCampaignPeopleEngagedPage />} />
          <Route path="/member/business-pages/:slug/ads/:campaignId/engagements" element={<AdCampaignPeopleEngagedPage />} />
          <Route path="/member/business-directory" element={<BusinessDirectoryPage />} />
          <Route path="/member/business-directory/categories/:category" element={<BusinessCategoryPage />} />
          {/* Member Direct Messages Routes */}
          <Route path="/member/messages" element={<DirectMessagesPage />} />
          <Route path="/member/messages/:memberId" element={<DirectMessagesPage />} />
          <Route path="/member/account/settings" element={<AccountSettingsPage />} />
          <Route path="/member/account/security" element={<SecuritySettingsPage />} />
          <Route path="/member/account/password" element={<SecuritySettingsPage />} />
          <Route path="/member/account/verify" element={<AccountVerificationPage />} />
          <Route path="/member/web3-wallet" element={<Web3WalletPage />} />
          <Route path="/member/account/web3-wallet" element={<Web3WalletPage />} />
          <Route path="/member/deposit" element={<DepositPage />} />
          <Route path="/member/feedback-suggestions" element={<FeedbackSuggestionsPage />} />
          <Route path="/member/withdrawal" element={<WithdrawalPage />} />
          <Route path="/member/wallet/withdrawal" element={<WithdrawalPage />} />
          <Route path="/member/blocked-users" element={<BlockedUsersPage />} />
        </Route>
      </Route>

      {/* Catch-all fallback */}
      <Route path="*" element={<Navigate to="/member/dashboard" replace />} />
    </Routes>
  );
}

export default AppRoutes;
