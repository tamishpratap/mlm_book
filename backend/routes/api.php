<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MemberManagementController;
use App\Http\Controllers\Admin\PostManagementController;
use App\Http\Controllers\Admin\StoryManagementController;
use App\Http\Controllers\Admin\BusinessPageManagementController;
use App\Http\Controllers\Admin\BusinessPageCategoryManagementController;
use App\Http\Controllers\Admin\CommunityManagementController;
use App\Http\Controllers\Admin\MarketplaceManagementController;
use App\Http\Controllers\Admin\EventManagementController;
use App\Http\Controllers\Admin\ReportManagementController;
use App\Http\Controllers\Admin\FeedbackManagementController;
use App\Http\Controllers\Admin\SettingManagementController;
use App\Http\Controllers\Admin\RoleManagementController;
use App\Http\Controllers\Admin\NotificationManagementController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\SystemToolsController;
use App\Http\Controllers\Admin\AdCampaignManagementController;
use App\Http\Controllers\Admin\AdDepositSettingsController;
use App\Http\Controllers\Admin\AdRewardRuleController;
use App\Http\Controllers\Admin\EventRewardRuleController;
use App\Http\Controllers\Admin\EventCampaignManagementController;
use App\Http\Controllers\Admin\RewardManagementController;
use App\Http\Controllers\Admin\WithdrawalManagementController;

// Member Controllers
use App\Http\Controllers\Member\MemberAuthController;
use App\Http\Controllers\Member\MemberPasswordResetController;
use App\Http\Controllers\Member\DashboardController as MemberDashboardController;
use App\Http\Controllers\Member\SocialsController;
use App\Http\Controllers\Member\WatchController;
use App\Http\Controllers\Member\PostController;
use App\Http\Controllers\Member\StoryController;
use App\Http\Controllers\Member\NotificationController as MemberNotificationController;
use App\Http\Controllers\Member\MemberSearchController;
use App\Http\Controllers\Member\FriendshipController;
use App\Http\Controllers\Member\FollowController;
use App\Http\Controllers\Member\MemberDirectoryProfileController;
use App\Http\Controllers\Member\ProfileController;
use App\Http\Controllers\Member\AccountController;
use App\Http\Controllers\Member\AccountVerificationController;
use App\Http\Controllers\Member\BlockController;
use App\Http\Controllers\Member\SocialFeaturesController;
use App\Http\Controllers\Member\MarketplaceController;
use App\Http\Controllers\Member\CommunityController;
use App\Http\Controllers\Member\CommunityDiscoveryController;
use App\Http\Controllers\Member\CommunityInviteController;
use App\Http\Controllers\Member\CommunityMembershipController;
use App\Http\Controllers\Member\CommunityPostController;
use App\Http\Controllers\Member\CommunityModerationController;
use App\Http\Controllers\Member\CommunityNotificationController;
use App\Http\Controllers\Member\CommunityAnalyticsController;
use App\Http\Controllers\Member\EventController;
use App\Http\Controllers\Member\EventCampaignController;
use App\Http\Controllers\Member\BusinessPageController;
use App\Http\Controllers\Member\BusinessTeamController;
use App\Http\Controllers\Member\BusinessFollowerController;
use App\Http\Controllers\Member\BusinessReviewController;
use App\Http\Controllers\Member\BusinessInboxController;
use App\Http\Controllers\Member\BusinessDirectoryController;
use App\Http\Controllers\Member\BusinessAnalyticsController;
use App\Http\Controllers\Member\BusinessVerificationController;
use App\Http\Controllers\Member\BusinessAdCampaignController;
use App\Http\Controllers\Member\AdDepositController;
use App\Http\Controllers\Member\DepositVerificationController;
use App\Http\Controllers\Member\RewardWalletController;
use App\Http\Controllers\Member\MemberAdRewardEligibilityController;
use App\Http\Controllers\Member\DirectMessageController;
use App\Http\Controllers\Member\WithdrawalController;
use App\Http\Controllers\Member\FeedbackSuggestionController;
use App\Http\Middleware\UpdateLastSeenMiddleware;
use App\Http\Middleware\EnsureMemberMobileVerified;

/*
|--------------------------------------------------------------------------
| Admin REST API Routes
|--------------------------------------------------------------------------
|
| Base URI: /api/admin/...
| Headless API endpoints for React Admin SPA consumption.
|
*/

// Global Public Branding Endpoint
Route::get('/branding', [SettingManagementController::class, 'getBranding']);

Route::prefix('admin')->group(function () {
    // Guest Admin Auth Endpoints
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/branding', [SettingManagementController::class, 'getBranding']);

    // Protected Admin Endpoints
    Route::middleware('auth:admin')->group(function () {
        Route::get('/me', function (Request $request) {
            $admin = $request->user('admin');
            $isSuperAdmin = $admin->hasRole('super-admin');
            $roles = $admin->roles()->pluck('slug');
            $permissions = $isSuperAdmin
                ? ['*']
                : $admin->roles()->with('permissions')->get()->flatMap(fn($role) => $role->permissions->pluck('slug'))->unique()->values()->toArray();

            return response()->json([
                'admin' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'profile_photo' => $admin->profile_photo,
                    'status' => $admin->status,
                    'roles' => $roles,
                    'is_super_admin' => $isSuperAdmin,
                    'permissions' => $permissions,
                ],
            ]);
        });

        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Members Management
        Route::prefix('members')->group(function () {
            Route::get('/', [MemberManagementController::class, 'active']);
            Route::get('/active', [MemberManagementController::class, 'active']);
            Route::get('/pending', [MemberManagementController::class, 'pending']);
            Route::get('/blocked', [MemberManagementController::class, 'blocked']);
            Route::get('/export/{format?}', [MemberManagementController::class, 'export']);
            Route::post('/bulk-action', [MemberManagementController::class, 'bulkAction']);
            Route::get('/{member}', [MemberManagementController::class, 'show']);
            Route::get('/{member}/edit', [MemberManagementController::class, 'edit']);
            Route::put('/{member}', [MemberManagementController::class, 'update']);
            Route::post('/{member}/status', [MemberManagementController::class, 'updateStatus']);
            Route::post('/{member}/block', [MemberManagementController::class, 'block']);
            Route::post('/{member}/unblock', [MemberManagementController::class, 'unblock']);
            Route::post('/{member}/approve', [MemberManagementController::class, 'approve']);
            Route::post('/{member}/approve-mobile-verification', [MemberManagementController::class, 'approve']);
            Route::post('/{member}/reject', [MemberManagementController::class, 'reject']);
            Route::post('/{member}/reject-mobile-verification', [MemberManagementController::class, 'reject']);
            Route::post('/{member}/communities/{community}/remove', [MemberManagementController::class, 'removeCommunity']);
            Route::delete('/{member}', [MemberManagementController::class, 'destroy']);
        });

        // Posts & Moderation
        Route::prefix('posts')->group(function () {
            Route::get('/', [PostManagementController::class, 'index']);
            Route::get('/export', [PostManagementController::class, 'export']);
            Route::post('/bulk-action', [PostManagementController::class, 'bulkAction']);
            Route::get('/{post}', [PostManagementController::class, 'show']);
            Route::put('/{post}', [PostManagementController::class, 'update']);
            Route::delete('/{post}/media', [PostManagementController::class, 'removeMedia']);
            Route::post('/{post}/toggle-hide', [PostManagementController::class, 'toggleHide']);
            Route::post('/reports/{report}/status', [PostManagementController::class, 'updateReportStatus']);
            Route::delete('/{post}', [PostManagementController::class, 'destroy']);
        });

        // Stories Management
        Route::prefix('stories')->group(function () {
            Route::get('/', [StoryManagementController::class, 'index']);
            Route::get('/export', [StoryManagementController::class, 'export']);
            Route::post('/bulk-action', [StoryManagementController::class, 'bulkAction']);
            Route::get('/{story}', [StoryManagementController::class, 'show']);
            Route::delete('/{story}', [StoryManagementController::class, 'destroy']);
        });

        // Business Pages Management
        Route::prefix('business-pages')->group(function () {
            Route::get('/categories', [BusinessPageCategoryManagementController::class, 'index']);
            Route::post('/categories', [BusinessPageCategoryManagementController::class, 'store']);
            Route::put('/categories/{category}', [BusinessPageCategoryManagementController::class, 'update']);
            Route::post('/categories/{category}/toggle-status', [BusinessPageCategoryManagementController::class, 'toggleStatus']);
            Route::post('/categories/{category}/reassign', [BusinessPageCategoryManagementController::class, 'reassign']);
            Route::delete('/categories/{category}', [BusinessPageCategoryManagementController::class, 'destroy']);

            Route::get('/', [BusinessPageManagementController::class, 'index']);
            Route::get('/export', [BusinessPageManagementController::class, 'export']);
            Route::post('/bulk-action', [BusinessPageManagementController::class, 'bulkAction']);
            Route::get('/{businessPage:id}', [BusinessPageManagementController::class, 'show']);
            Route::get('/{businessPage:id}/edit', [BusinessPageManagementController::class, 'edit']);
            Route::put('/{businessPage:id}', [BusinessPageManagementController::class, 'update']);
            Route::post('/{businessPage:id}/status', [BusinessPageManagementController::class, 'updateStatus']);
            Route::post('/{businessPage:id}/verification', [BusinessPageManagementController::class, 'handleVerification']);
            Route::delete('/{businessPage:id}', [BusinessPageManagementController::class, 'destroy']);
        });

        // Ad Campaigns Management
        Route::prefix('ad-campaigns')->group(function () {
            Route::get('/settings', [AdCampaignManagementController::class, 'getSettings']);
            Route::post('/settings', [AdCampaignManagementController::class, 'updateSettings']);
            Route::get('/', [AdCampaignManagementController::class, 'index']);
            Route::get('/analytics', [AdCampaignManagementController::class, 'analytics']);
            Route::get('/rewards', [AdCampaignManagementController::class, 'rewards']);
            Route::get('/export', [AdCampaignManagementController::class, 'export']);
            Route::get('/{adCampaign}', [AdCampaignManagementController::class, 'show']);
            Route::get('/{adCampaign}/rewards', [AdCampaignManagementController::class, 'campaignRewards']);
            Route::post('/{adCampaign}/approve', [AdCampaignManagementController::class, 'approve']);
            Route::post('/{adCampaign}/reject', [AdCampaignManagementController::class, 'reject']);
            Route::post('/{adCampaign}/pause', [AdCampaignManagementController::class, 'pause']);
            Route::post('/{adCampaign}/resume', [AdCampaignManagementController::class, 'resume']);
            Route::post('/{adCampaign}/stop', [AdCampaignManagementController::class, 'stop']);
            Route::post('/{adCampaign}/restart', [AdCampaignManagementController::class, 'restart']);
        });

        // Centralized Reward Management
        Route::prefix('reward-rules')->group(function () {
            Route::get('/', [RewardManagementController::class, 'indexRules']);
            Route::get('/validate-active-set', [RewardManagementController::class, 'validateActiveSet']);
            Route::post('/', [RewardManagementController::class, 'storeRule']);
            Route::post('/preview', [RewardManagementController::class, 'previewRule']);
            Route::put('/{rewardRankRule}', [RewardManagementController::class, 'updateRule']);
            Route::post('/{rewardRankRule}/toggle-status', [RewardManagementController::class, 'toggleRuleStatus']);
        });

        Route::prefix('reward-history')->group(function () {
            Route::get('/events', [RewardManagementController::class, 'eventRewardsHistory']);
            Route::get('/ads', [RewardManagementController::class, 'adRewardsHistory']);
            Route::get('/metrics', [RewardManagementController::class, 'historyMetrics']);
        });

        // Ad Reward Rules Management (Legacy compatibility)
        Route::prefix('ad-reward-rules')->group(function () {
            Route::get('/', [AdRewardRuleController::class, 'index']);
            Route::post('/', [AdRewardRuleController::class, 'store']);
            Route::post('/preview', [AdRewardRuleController::class, 'preview']);
            Route::put('/{adRewardRule}', [AdRewardRuleController::class, 'update']);
            Route::post('/{adRewardRule}/toggle-status', [AdRewardRuleController::class, 'toggleStatus']);
        });

        // Event Reward Rules Management
        Route::prefix('event-reward-rules')->group(function () {
            Route::get('/', [EventRewardRuleController::class, 'index']);
            Route::get('/validate-active-set', [EventRewardRuleController::class, 'validateActiveSet']);
            Route::post('/', [EventRewardRuleController::class, 'store']);
            Route::post('/preview', [EventRewardRuleController::class, 'preview']);
            Route::put('/{adRewardRule}', [EventRewardRuleController::class, 'update']);
            Route::post('/{adRewardRule}/toggle-status', [EventRewardRuleController::class, 'toggleStatus']);
        });

        // Event Campaigns Management (Admin Event Control Center)
        Route::prefix('event-campaigns')->group(function () {
            Route::get('/', [EventCampaignManagementController::class, 'index']);
            Route::get('/{adCampaign}', [EventCampaignManagementController::class, 'show']);
            Route::get('/{adCampaign}/participants', [EventCampaignManagementController::class, 'participants']);
            Route::post('/{adCampaign}/pause', [EventCampaignManagementController::class, 'pause']);
            Route::post('/{adCampaign}/resume', [EventCampaignManagementController::class, 'resume']);
            Route::post('/{adCampaign}/stop', [EventCampaignManagementController::class, 'stop']);
        });

        // Communities Management
        Route::prefix('communities')->group(function () {
            Route::get('/', [CommunityManagementController::class, 'index']);
            Route::get('/export', [CommunityManagementController::class, 'export']);
            Route::post('/bulk-action', [CommunityManagementController::class, 'bulkAction']);
            Route::get('/{community:id}', [CommunityManagementController::class, 'show']);
            Route::get('/{community:id}/edit', [CommunityManagementController::class, 'edit']);
            Route::put('/{community:id}', [CommunityManagementController::class, 'update']);
            Route::post('/{community:id}/status', [CommunityManagementController::class, 'updateStatus']);
            Route::post('/{community:id}/join-requests/{membership}', [CommunityManagementController::class, 'handleJoinRequest']);
            Route::post('/reports/{report}/resolve', [CommunityManagementController::class, 'resolveReport']);
            Route::delete('/{community:id}', [CommunityManagementController::class, 'destroy']);
        });

        // Marketplace Management
        Route::prefix('marketplace')->group(function () {
            Route::get('/', [MarketplaceManagementController::class, 'index']);
            Route::get('/export', [MarketplaceManagementController::class, 'export']);
            Route::post('/bulk-action', [MarketplaceManagementController::class, 'bulkAction']);
            Route::get('/{product}', [MarketplaceManagementController::class, 'show']);
            Route::post('/{product}/status', [MarketplaceManagementController::class, 'updateStatus']);
            Route::post('/{product}/toggle-featured', [MarketplaceManagementController::class, 'toggleFeatured']);
            Route::delete('/{product}', [MarketplaceManagementController::class, 'destroy']);
        });

        // Events Management
        Route::prefix('events')->group(function () {
            Route::get('/', [EventManagementController::class, 'index']);
            Route::get('/export', [EventManagementController::class, 'export']);
            Route::post('/bulk-action', [EventManagementController::class, 'bulkAction']);
            Route::get('/{event:id}', [EventManagementController::class, 'show']);
            Route::get('/{event:id}/edit', [EventManagementController::class, 'edit']);
            Route::put('/{event:id}', [EventManagementController::class, 'update']);
            Route::post('/{event:id}/status', [EventManagementController::class, 'updateStatus']);
            Route::delete('/{event:id}', [EventManagementController::class, 'destroy']);
        });

        // Reports & Moderation
        Route::prefix('reports')->group(function () {
            Route::get('/', [ReportManagementController::class, 'index']);
            Route::get('/export', [ReportManagementController::class, 'export']);
            Route::post('/bulk-action', [ReportManagementController::class, 'bulkAction']);
            Route::get('/{type}/{id}', [ReportManagementController::class, 'show']);
            Route::post('/{type}/{id}/status', [ReportManagementController::class, 'updateStatus']);
            Route::delete('/{type}/{id}/target', [ReportManagementController::class, 'destroyTarget']);
            Route::delete('/{type}/{id}', [ReportManagementController::class, 'destroy']);
        });

        // Feedback & Suggestions Management
        Route::prefix('feedback-suggestions')->group(function () {
            Route::get('/', [FeedbackManagementController::class, 'index']);
            Route::get('/export', [FeedbackManagementController::class, 'export']);
            Route::post('/bulk-action', [FeedbackManagementController::class, 'bulkAction']);
            Route::get('/{feedback}', [FeedbackManagementController::class, 'show']);
            Route::match(['put', 'post'], '/{feedback}/status', [FeedbackManagementController::class, 'updateStatus']);
            Route::match(['put', 'post'], '/{feedback}/response', [FeedbackManagementController::class, 'updateResponse']);
            Route::delete('/{feedback}', [FeedbackManagementController::class, 'destroy']);
        });

        // Settings & Configuration
        Route::prefix('settings')->group(function () {
            Route::get('/', [SettingManagementController::class, 'index']);
            Route::get('/branding', [SettingManagementController::class, 'getBranding']);
            Route::post('/', [SettingManagementController::class, 'update']);
            Route::post('/clear-cache', [SettingManagementController::class, 'clearCache']);
            Route::post('/maintenance', [SettingManagementController::class, 'toggleMaintenance']);
        });

        // Roles & Permissions (RBAC)
        Route::prefix('roles')->group(function () {
            Route::get('/', [RoleManagementController::class, 'index']);
            Route::get('/matrix', [RoleManagementController::class, 'matrix']);
            Route::post('/matrix', [RoleManagementController::class, 'updateMatrix']);
            Route::get('/create', [RoleManagementController::class, 'create']);
            Route::post('/', [RoleManagementController::class, 'store']);
            Route::get('/{role}/edit', [RoleManagementController::class, 'edit']);
            Route::put('/{role}', [RoleManagementController::class, 'update']);
            Route::delete('/{role}', [RoleManagementController::class, 'destroy']);
            Route::post('/assign-user', [RoleManagementController::class, 'assignUserRole']);
        });

        // Notifications & Broadcast
        Route::prefix('notifications')->group(function () {
            Route::get('/unread-count', [NotificationManagementController::class, 'unreadCount']);
            Route::get('/dropdown', [NotificationManagementController::class, 'dropdown']);
            Route::get('/', [NotificationManagementController::class, 'index']);
            Route::get('/broadcast', [NotificationManagementController::class, 'broadcastForm']);
            Route::post('/broadcast', [NotificationManagementController::class, 'sendBroadcast']);
            Route::get('/export', [NotificationManagementController::class, 'export']);
            Route::post('/bulk-action', [NotificationManagementController::class, 'bulkAction']);
            Route::post('/mark-all-read', [NotificationManagementController::class, 'markAllRead']);
            Route::get('/{id}', [NotificationManagementController::class, 'show']);
            Route::post('/{id}/read', [NotificationManagementController::class, 'markRead']);
        });

        // Analytics & BI
        Route::prefix('analytics')->group(function () {
            Route::get('/', [AnalyticsController::class, 'index']);
            Route::get('/export', [AnalyticsController::class, 'export']);
        });

        // System Tools & Diagnostics
        Route::prefix('system')->group(function () {
            Route::get('/', [SystemToolsController::class, 'index']);
            Route::get('/logs', [SystemToolsController::class, 'logs']);
            Route::get('/logs/download', [SystemToolsController::class, 'downloadLog']);
            Route::post('/logs/clear', [SystemToolsController::class, 'clearLog']);
            Route::post('/failed-jobs/{id}/retry', [SystemToolsController::class, 'retryFailedJob']);
            Route::delete('/failed-jobs/{id}', [SystemToolsController::class, 'deleteFailedJob']);
            Route::post('/artisan', [SystemToolsController::class, 'runArtisan']);
        });

        // Funds & Deposit Management
        Route::prefix('funds')->group(function () {
            Route::get('/deposit-settings', [AdDepositSettingsController::class, 'getSettings']);
            Route::post('/deposit-settings', [AdDepositSettingsController::class, 'updateSettings']);
            Route::get('/deposits', [AdDepositSettingsController::class, 'getDeposits']);
            Route::post('/deposits/{id}/reverify', [AdDepositSettingsController::class, 'reverifyDeposit']);
            Route::post('/deposits/{id}/approve', [AdDepositSettingsController::class, 'approveDeposit']);
            Route::post('/deposits/{id}/reject', [AdDepositSettingsController::class, 'rejectDeposit']);

            // Withdrawal Requests Management
            Route::prefix('withdrawals')->group(function () {
                Route::get('/', [WithdrawalManagementController::class, 'index']);
                Route::get('/metrics', [WithdrawalManagementController::class, 'metrics']);
                Route::get('/{id}', [WithdrawalManagementController::class, 'show']);
                Route::post('/{id}/accept', [WithdrawalManagementController::class, 'accept']);
                Route::post('/{id}/verify', [WithdrawalManagementController::class, 'verify']);
                Route::post('/{id}/reject', [WithdrawalManagementController::class, 'reject']);
            });
        });

        // Direct alias for withdrawals
        Route::prefix('withdrawals')->group(function () {
            Route::get('/', [WithdrawalManagementController::class, 'index']);
            Route::get('/metrics', [WithdrawalManagementController::class, 'metrics']);
            Route::get('/{id}', [WithdrawalManagementController::class, 'show']);
            Route::post('/{id}/accept', [WithdrawalManagementController::class, 'accept']);
            Route::post('/{id}/verify', [WithdrawalManagementController::class, 'verify']);
            Route::post('/{id}/reject', [WithdrawalManagementController::class, 'reject']);
        });
    });
});

/*
|--------------------------------------------------------------------------
| Member REST API Routes
|--------------------------------------------------------------------------
|
| Base URI: /api/member/...
| Headless API endpoints for React Member SPA consumption.
|
*/
Route::prefix('member')->name('api.member.')->group(function () {

    // ---------------------------------------------------------------------
    // 0. Public Platform Branding & Meta
    // ---------------------------------------------------------------------
    Route::get('/branding', [SettingManagementController::class, 'getBranding'])->name('branding');

    // ---------------------------------------------------------------------
    // 1. Guest Authentication & Onboarding
    // ---------------------------------------------------------------------
    Route::post('/login', [MemberAuthController::class, 'login']);
    Route::post('/register', [MemberAuthController::class, 'register']);
    Route::get('/register/check-phone', [MemberAuthController::class, 'checkPhone'])->middleware('throttle:30,1');
    Route::get('/register/check-user-id', [MemberAuthController::class, 'checkUserId']);
    Route::get('/register/check-introducer', [MemberAuthController::class, 'checkIntroducer']);
    Route::get('/register/verify', [MemberAuthController::class, 'showVerifyEmail']);
    Route::post('/register/verify', [MemberAuthController::class, 'verifyEmailOtp']);
    Route::post('/register/resend-otp', [MemberAuthController::class, 'resendRegistrationOtp']);
    Route::post('/register/cancel', [MemberAuthController::class, 'cancelRegistration']);
    Route::post('/forgot-password', [MemberPasswordResetController::class, 'sendResetLink']);
    Route::post('/reset-password', [MemberPasswordResetController::class, 'resetPassword']);
    Route::get('/auth/google/redirect', [MemberAuthController::class, 'redirectToGoogle']);
    Route::get('/auth/google/callback', [MemberAuthController::class, 'handleGoogleCallback']);
    Route::get('/auth/google/pending', [MemberAuthController::class, 'getPendingGoogleSignup']);
    Route::post('/auth/google/complete', [MemberAuthController::class, 'completeGoogleSignup']);
    Route::get('/community/invite/{code}', [CommunityInviteController::class, 'show']);

    // ---------------------------------------------------------------------
    // 2. Protected Member Endpoints
    // ---------------------------------------------------------------------
    Route::middleware(['auth:member', UpdateLastSeenMiddleware::class])->group(function () {

        // Session & Auth
        Route::get('/me', function (Request $request) {
            $member = $request->user('member')?->fresh();
            return response()->json([
                'member' => $member,
                'unread_notifications_count' => $member ? $member->unreadNotifications()->count() : 0,
            ]);
        });
        Route::post('/logout', [MemberAuthController::class, 'logout']);

        // Dashboard Hub
        Route::get('/dashboard', [MemberDashboardController::class, 'index']);

        // Feed & Socials
        Route::get('/socials', [SocialsController::class, 'index']);
        Route::get('/watch', [WatchController::class, 'index']);
        Route::get('/feed/check-new', [PostController::class, 'checkNewPosts']);
        Route::get('/saved-posts', [PostController::class, 'savedPosts']);

        // Posts Core & Interactions
        Route::prefix('posts')->group(function () {
            Route::post('/', [PostController::class, 'store'])->middleware(EnsureMemberMobileVerified::class);
            Route::get('/{post}', [PostController::class, 'show']);
            Route::post('/{post}/like', [PostController::class, 'toggleLike'])->middleware(EnsureMemberMobileVerified::class);
            Route::get('/{post}/likers', [PostController::class, 'likers']);
            Route::post('/{post}/react', [PostController::class, 'react'])->middleware(EnsureMemberMobileVerified::class);
            Route::get('/{post}/reactors', [PostController::class, 'reactors']);
            Route::get('/{post}/comments', [PostController::class, 'comments']);
            Route::post('/{post}/comments', [PostController::class, 'storeComment'])->middleware(EnsureMemberMobileVerified::class);
            Route::post('/{post}/share', [PostController::class, 'sharePost']);
            Route::post('/{post}/send-to-friends', [PostController::class, 'sendToFriends']);
            Route::get('/{post}/sharers', [PostController::class, 'postSharers']);
            Route::post('/{post}/save', [PostController::class, 'toggleSave']);
            Route::post('/{post}/hide', [PostController::class, 'hidePost']);
            Route::post('/{post}/report', [PostController::class, 'reportPost']);
            Route::post('/{post}/pin', [PostController::class, 'togglePin']);
            Route::delete('/{post}', [PostController::class, 'destroy']);
        });

        // Comments & Replies
        Route::prefix('comments')->group(function () {
            Route::put('/{comment}', [PostController::class, 'updateComment']);
            Route::delete('/{comment}', [PostController::class, 'destroyComment']);
            Route::get('/{comment}/replies', [PostController::class, 'replies']);
            Route::post('/{comment}/replies', [PostController::class, 'storeReply'])->middleware(EnsureMemberMobileVerified::class);
            Route::post('/{comment}/react', [PostController::class, 'reactComment']);
            Route::get('/{comment}/reactors', [PostController::class, 'commentReactors']);
        });

        // Stories Core & Interactions
        Route::prefix('stories')->group(function () {
            Route::get('/', [StoryController::class, 'index']);
            Route::post('/', [StoryController::class, 'store'])->middleware(EnsureMemberMobileVerified::class);
            Route::get('/{story}', [StoryController::class, 'show']);
            Route::get('/{story}/viewers', [StoryController::class, 'viewers']);
            Route::post('/{story}/like', [StoryController::class, 'toggleLike']);
            Route::post('/{story}/react', [StoryController::class, 'react']);
            Route::get('/{story}/reactors', [StoryController::class, 'reactors']);
            Route::get('/{story}/replies', [StoryController::class, 'replies']);
            Route::post('/{story}/reply', [StoryController::class, 'reply']);
            Route::delete('/{story}', [StoryController::class, 'destroy']);
        });
        Route::delete('/story-replies/{reply}', [StoryController::class, 'destroyReply']);

        // Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [MemberNotificationController::class, 'index']);
            Route::get('/dropdown', [MemberNotificationController::class, 'dropdown']);
            Route::get('/poll', [MemberNotificationController::class, 'poll']);
            Route::post('/read-all', [MemberNotificationController::class, 'markAllAsRead']);
            Route::delete('/clear-all', [MemberNotificationController::class, 'clearAll']);
            Route::get('/{notificationId}', [MemberNotificationController::class, 'show']);
            Route::post('/{notificationId}/read', [MemberNotificationController::class, 'markAsRead']);
            Route::delete('/{notificationId}', [MemberNotificationController::class, 'destroy']);
        });

        // Global Search
        Route::prefix('search')->group(function () {
            Route::get('/', [MemberSearchController::class, 'index']);
            Route::get('/results', [MemberSearchController::class, 'results']);
        });

        // Friends, Connections & Requests
        Route::prefix('friends')->group(function () {
            Route::get('/', [FriendshipController::class, 'index']);
            Route::get('/requests', [FriendshipController::class, 'requests']);
            Route::post('/request/{member}', [FriendshipController::class, 'send'])->middleware(EnsureMemberMobileVerified::class);
            Route::delete('/{member}/remove', [FriendshipController::class, 'remove']);
        });
        Route::prefix('friend-requests')->group(function () {
            Route::get('/', [FriendshipController::class, 'requests']);
            Route::post('/{friendship}/accept', [FriendshipController::class, 'accept']);
            Route::post('/{friendship}/reject', [FriendshipController::class, 'reject']);
            Route::delete('/{friendship}/cancel', [FriendshipController::class, 'cancel']);
        });

        // People & Public Profiles
        Route::prefix('people')->group(function () {
            Route::get('/suggestions', [SocialFeaturesController::class, 'suggestions']);
            Route::get('/{member}', [MemberDirectoryProfileController::class, 'show']);
            Route::get('/{member}/friends', [FriendshipController::class, 'memberFriends']);
            Route::post('/{member}/follow', [FollowController::class, 'toggleFollow'])->middleware(EnsureMemberMobileVerified::class);
            Route::get('/{member}/followers', [FollowController::class, 'followers']);
            Route::get('/{member}/following', [FollowController::class, 'following']);
            Route::post('/{member}/block', [BlockController::class, 'block']);
            Route::delete('/{member}/unblock', [BlockController::class, 'unblock']);
        });

        // Own Profile
        Route::prefix('profile')->group(function () {
            Route::get('/', [ProfileController::class, 'show']);
            Route::get('/edit', [ProfileController::class, 'edit']);
            Route::put('/', [ProfileController::class, 'update']);
            Route::post('/photo', [ProfileController::class, 'updateProfilePhoto']);
            Route::delete('/photo', [ProfileController::class, 'removeProfilePhoto']);
            Route::post('/cover', [ProfileController::class, 'updateCoverPhoto']);
            Route::delete('/cover', [ProfileController::class, 'removeCoverPhoto']);
            Route::get('/visitors', [SocialFeaturesController::class, 'visitors']);
        });

        // Account & Security
        Route::prefix('account')->group(function () {
            Route::get('/settings', [AccountController::class, 'settings']);
            Route::put('/settings', [AccountController::class, 'updateSettings']);
            Route::get('/check-introducer', [AccountController::class, 'checkIntroducer']);
            Route::post('/introducer', [AccountController::class, 'claimIntroducer']);
            Route::get('/security', [AccountController::class, 'security']);
            Route::get('/verification-status', [AccountVerificationController::class, 'getVerificationStatus']);
            Route::post('/verification/initiate', [AccountVerificationController::class, 'initiateWhatsAppVerification']);
            Route::post('/verification/submit-hi', [AccountVerificationController::class, 'submitWhatsAppVerificationRequest']);
            Route::put('/password', [AccountController::class, 'updatePassword']);
            Route::post('/mobile/send-otp', [AccountVerificationController::class, 'sendMobileOtp']);
            Route::post('/mobile/verify-otp', [AccountVerificationController::class, 'verifyMobileOtp']);
            Route::post('/email/send-otp', [AccountVerificationController::class, 'sendEmailOtp']);
            Route::post('/email/verify-otp', [AccountVerificationController::class, 'verifyEmailOtp']);
        });
        Route::get('/blocked-users', [BlockController::class, 'index']);
        Route::get('/disconnections', [BlockController::class, 'index']);
        Route::delete('/disconnections/{member}', [BlockController::class, 'unblock']);

        // Marketplace
        Route::prefix('marketplace')->group(function () {
            Route::get('/', [MarketplaceController::class, 'index']);
            Route::get('/create', [MarketplaceController::class, 'create']);
            Route::post('/', [MarketplaceController::class, 'store']);
            Route::get('/my-products', [MarketplaceController::class, 'myProducts']);
            Route::get('/saved', [MarketplaceController::class, 'saved']);
            Route::get('/{product}', [MarketplaceController::class, 'show']);
            Route::get('/{product}/edit', [MarketplaceController::class, 'edit']);
            Route::put('/{product}', [MarketplaceController::class, 'update']);
            Route::delete('/{product}', [MarketplaceController::class, 'destroy']);
            Route::delete('/{product}/media/{media}', [MarketplaceController::class, 'destroyMedia']);
            Route::post('/{product}/status', [MarketplaceController::class, 'toggleStatus']);
            Route::post('/{product}/save', [MarketplaceController::class, 'toggleSave']);
            Route::post('/{product}/report', [MarketplaceController::class, 'report']);
        });

        // Communities
        Route::prefix('community')->group(function () {
            Route::get('/', [CommunityController::class, 'index']);
            Route::get('/create', [CommunityController::class, 'create']);
            Route::post('/', [CommunityController::class, 'store'])->middleware(EnsureMemberMobileVerified::class);
            Route::get('/discover', [CommunityDiscoveryController::class, 'discover']);
            Route::get('/search-ajax', [CommunityDiscoveryController::class, 'searchAjax']);
            Route::get('/notifications', [CommunityNotificationController::class, 'index']);
            Route::post('/notifications/{notification}/read', [CommunityNotificationController::class, 'markAsRead']);
            Route::post('/notifications/mark-all-read', [CommunityNotificationController::class, 'markAllAsRead']);
            Route::get('/notifications/unread-count', [CommunityNotificationController::class, 'unreadCount']);
            Route::post('/invite/{code}/join', [CommunityInviteController::class, 'processJoin'])->middleware(EnsureMemberMobileVerified::class);
            Route::get('/{community:slug}', [CommunityController::class, 'show']);
            Route::get('/{community:slug}/edit', [CommunityController::class, 'edit']);
            Route::put('/{community:slug}', [CommunityController::class, 'update']);
            Route::delete('/{community:slug}', [CommunityController::class, 'destroy']);
            Route::post('/{community:slug}/cover', [CommunityController::class, 'updateCover']);
            Route::post('/{community:slug}/cover/remove', [CommunityController::class, 'removeCover']);
            Route::post('/{community:slug}/logo', [CommunityController::class, 'updateLogo']);
            Route::post('/{community:slug}/logo/remove', [CommunityController::class, 'removeLogo']);
            Route::post('/{community:slug}/join', [CommunityMembershipController::class, 'join'])->middleware(EnsureMemberMobileVerified::class);
            Route::post('/{community:slug}/leave', [CommunityMembershipController::class, 'leave']);
            Route::post('/{community:slug}/requests/{membership}/handle', [CommunityMembershipController::class, 'handleRequest']);
            Route::post('/{community:slug}/members/{membership}/role', [CommunityMembershipController::class, 'updateRole']);
            Route::delete('/{community:slug}/members/{membership}', [CommunityMembershipController::class, 'removeMember']);
            Route::post('/{community:slug}/posts', [CommunityPostController::class, 'store'])->middleware(EnsureMemberMobileVerified::class);
            Route::post('/{community:slug}/posts/{post}/pin', [CommunityPostController::class, 'togglePin']);
            Route::post('/{community:slug}/posts/{post}/announcement', [CommunityPostController::class, 'toggleAnnouncement']);
            Route::delete('/{community:slug}/posts/{post}', [CommunityPostController::class, 'destroy']);
            Route::get('/{community:slug}/invites/friends', [CommunityInviteController::class, 'getFriends']);
            Route::post('/{community:slug}/invites/send', [CommunityInviteController::class, 'sendInvites']);
            Route::post('/{community:slug}/invites/generate', [CommunityInviteController::class, 'generate']);
            Route::post('/{community:slug}/invites/revoke', [CommunityInviteController::class, 'revoke']);
            Route::get('/{community:slug}/admin', [CommunityModerationController::class, 'adminPanel']);
            Route::post('/{community:slug}/moderation/ban', [CommunityModerationController::class, 'banMember']);
            Route::post('/{community:slug}/moderation/unban', [CommunityModerationController::class, 'unbanMember']);
            Route::post('/{community:slug}/moderation/mute', [CommunityModerationController::class, 'muteMember']);
            Route::post('/{community:slug}/moderation/unmute', [CommunityModerationController::class, 'unmuteMember']);
            Route::post('/{community:slug}/moderation/warn', [CommunityModerationController::class, 'warnMember']);
            Route::post('/{community:slug}/reports', [CommunityModerationController::class, 'storeReport']);
            Route::post('/{community:slug}/reports/{report}/handle', [CommunityModerationController::class, 'handleReport']);
            Route::put('/{community:slug}/settings', [CommunityModerationController::class, 'updateSettings']);
            Route::post('/{community:slug}/transfer-ownership', [CommunityModerationController::class, 'transferOwnership']);
            Route::post('/{community:slug}/preferences', [CommunityNotificationController::class, 'updatePreferences']);
            Route::get('/{community:slug}/activity', [CommunityNotificationController::class, 'activityTimeline']);
            Route::get('/{community:slug}/analytics', [CommunityAnalyticsController::class, 'index']);
            Route::get('/{community:slug}/analytics/export', [CommunityAnalyticsController::class, 'exportCsv']);
        });

        // Events
        Route::prefix('events')->group(function () {
            Route::get('/', [EventController::class, 'index']);
            Route::get('/create', [EventController::class, 'create']);
            Route::get('/sponsored-feed', [EventCampaignController::class, 'sponsoredFeed']);
            Route::post('/', [EventController::class, 'store']);
            Route::get('/{event}', [EventController::class, 'show']);
            Route::get('/{event}/edit', [EventController::class, 'edit']);
            Route::put('/{event}', [EventController::class, 'update']);
            Route::delete('/{event}', [EventController::class, 'destroy']);
            Route::post('/{event}/respond', [EventController::class, 'respond']);
            Route::post('/{event}/invite', [EventController::class, 'invite']);
            Route::get('/{event}/outreach', [EventController::class, 'outreach']);
            Route::post('/{event}/posts', [EventController::class, 'storePost']);
            Route::get('/{event}/campaign', [EventCampaignController::class, 'show']);
            Route::post('/{event}/campaign', [EventCampaignController::class, 'store']);
            Route::post('/{event}/campaign/add-funds', [EventCampaignController::class, 'addFunds']);
            Route::post('/{event}/campaign/top-up', [EventCampaignController::class, 'addFunds']);
            Route::post('/{event}/add-funds', [EventCampaignController::class, 'addFunds']);
            Route::post('/{event}/campaign/allocate-budget', [EventCampaignController::class, 'allocateBudget']);
            Route::post('/{event}/campaign/activate', [EventCampaignController::class, 'activate']);
            Route::post('/{event}/campaign/pause', [EventCampaignController::class, 'pause']);
            Route::post('/{event}/campaign/resume', [EventCampaignController::class, 'resume']);
            Route::post('/{event}/campaign/reactivate', [EventCampaignController::class, 'reactivate']);
            Route::get('/{event}/campaign/analytics', [EventCampaignController::class, 'analytics']);
            Route::get('/{event}/campaign/participants', [EventCampaignController::class, 'participants']);
            Route::get('/{event}/reward-preview', [EventCampaignController::class, 'rewardPreview']);
            Route::post('/{event}/campaign/qualify-interest', [EventCampaignController::class, 'qualifyInterest']);
            Route::post('/{event}/campaign/claim', [EventCampaignController::class, 'qualifyInterest']);
        });

        // Business Pages
        Route::prefix('business-pages')->group(function () {
            Route::get('/', [BusinessPageController::class, 'index']);
            Route::get('/create', [BusinessPageController::class, 'create']);
            Route::post('/', [BusinessPageController::class, 'store'])->middleware(EnsureMemberMobileVerified::class);
            Route::get('/{businessPage:slug}', [BusinessPageController::class, 'show']);
            Route::get('/{businessPage:slug}/edit', [BusinessPageController::class, 'edit']);
            Route::put('/{businessPage:slug}', [BusinessPageController::class, 'update']);
            Route::delete('/{businessPage:slug}', [BusinessPageController::class, 'destroy']);
            Route::post('/{businessPage:slug}/photo', [BusinessPageController::class, 'updateProfilePhoto']);
            Route::delete('/{businessPage:slug}/photo', [BusinessPageController::class, 'removeProfilePhoto']);
            Route::post('/{businessPage:slug}/cover', [BusinessPageController::class, 'updateCoverPhoto']);
            Route::delete('/{businessPage:slug}/cover', [BusinessPageController::class, 'removeCoverPhoto']);
            Route::post('/{businessPage:slug}/posts', [BusinessPageController::class, 'storePost'])->middleware(EnsureMemberMobileVerified::class);
            Route::post('/{businessPage:slug}/posts/{post}/pin', [BusinessPageController::class, 'togglePinPost']);
            Route::post('/{businessPage:slug}/posts/{post}/feature', [BusinessPageController::class, 'toggleFeaturePost']);
            Route::delete('/{businessPage:slug}/posts/{post}', [BusinessPageController::class, 'destroyPost']);
            Route::get('/{businessPage:slug}/team', [BusinessTeamController::class, 'index']);
            Route::post('/{businessPage:slug}/team/invite', [BusinessTeamController::class, 'invite']);
            Route::delete('/{businessPage:slug}/team/invitations/{invitation}', [BusinessTeamController::class, 'cancelInvite']);
            Route::put('/{businessPage:slug}/team/members/{teamMember}/role', [BusinessTeamController::class, 'changeRole']);
            Route::delete('/{businessPage:slug}/team/members/{teamMember}', [BusinessTeamController::class, 'removeMember']);
            Route::post('/{businessPage:slug}/follow', [BusinessFollowerController::class, 'toggleFollow'])->middleware(EnsureMemberMobileVerified::class);
            Route::post('/{businessPage:slug}/follow-requests/{follower}', [BusinessFollowerController::class, 'handleRequest']);
            Route::delete('/{businessPage:slug}/followers/{follower}', [BusinessFollowerController::class, 'removeFollower']);
            Route::post('/{businessPage:slug}/invite-to-follow', [BusinessFollowerController::class, 'inviteToFollow']);
            Route::post('/{businessPage:slug}/reviews', [BusinessReviewController::class, 'store'])->middleware(EnsureMemberMobileVerified::class);
            Route::put('/{businessPage:slug}/reviews/{review}', [BusinessReviewController::class, 'update']);
            Route::delete('/{businessPage:slug}/reviews/{review}', [BusinessReviewController::class, 'destroy']);
            Route::post('/{businessPage:slug}/reviews/{review}/reply', [BusinessReviewController::class, 'storeReply']);
            Route::post('/{businessPage:slug}/reviews/{review}/vote', [BusinessReviewController::class, 'vote'])->middleware(EnsureMemberMobileVerified::class);
            Route::post('/{businessPage:slug}/reviews/{review}/report', [BusinessReviewController::class, 'report']);
            Route::post('/{businessPage:slug}/reviews/{review}/hide', [BusinessReviewController::class, 'toggleHide']);
            Route::get('/{businessPage:slug}/inbox', [BusinessInboxController::class, 'index']);
            Route::post('/{businessPage:slug}/inbox/chat', [BusinessInboxController::class, 'startConversation']);
            Route::get('/{businessPage:slug}/inbox/conversations/{conversation}', [BusinessInboxController::class, 'showConversation']);
            Route::post('/{businessPage:slug}/inbox/conversations/{conversation}/messages', [BusinessInboxController::class, 'sendMessage']);
            Route::post('/{businessPage:slug}/inbox/conversations/{conversation}/request', [BusinessInboxController::class, 'handleRequest']);
            Route::post('/{businessPage:slug}/inbox/conversations/{conversation}/star', [BusinessInboxController::class, 'toggleStar']);
            Route::post('/{businessPage:slug}/inbox/conversations/{conversation}/pin', [BusinessInboxController::class, 'togglePin']);
            Route::put('/{businessPage:slug}/inbox/conversations/{conversation}/status', [BusinessInboxController::class, 'updateStatus']);
            Route::post('/{businessPage:slug}/inbox/quick-replies', [BusinessInboxController::class, 'storeQuickReply']);
            Route::delete('/{businessPage:slug}/inbox/quick-replies/{quickReply}', [BusinessInboxController::class, 'destroyQuickReply']);
            Route::get('/{businessPage:slug}/notifications', [BusinessInboxController::class, 'notifications']);
            Route::post('/{businessPage:slug}/notifications/read', [BusinessInboxController::class, 'markNotificationsRead']);
            Route::post('/{businessPage:slug}/notifications/{notification}/read', [BusinessInboxController::class, 'markSingleNotificationRead']);
            Route::delete('/{businessPage:slug}/notifications/{notification}', [BusinessInboxController::class, 'destroyNotification']);
            Route::delete('/{businessPage:slug}/notifications', [BusinessInboxController::class, 'clearAllNotifications']);
            Route::get('/{businessPage:slug}/analytics', [BusinessAnalyticsController::class, 'index']);
            Route::get('/{businessPage:slug}/analytics/data', [BusinessAnalyticsController::class, 'data']);
            Route::get('/{businessPage:slug}/verification', [BusinessVerificationController::class, 'index']);
            Route::post('/{businessPage:slug}/verification', [BusinessVerificationController::class, 'store']);

            // Advertising Campaigns
            Route::get('/{businessPage:slug}/ad-campaigns', [BusinessAdCampaignController::class, 'index']);
            Route::post('/{businessPage:slug}/ad-campaigns', [BusinessAdCampaignController::class, 'store']);
            Route::get('/{businessPage:slug}/ad-campaigns/analytics', [BusinessAdCampaignController::class, 'analytics']);
            Route::get('/{businessPage:slug}/ad-campaigns/{campaignIdentifier}/analytics', [BusinessAdCampaignController::class, 'campaignAnalytics']);
            Route::get('/{businessPage:slug}/ad-campaigns/{campaignIdentifier}/engagements', [BusinessAdCampaignController::class, 'engagements']);
            Route::get('/{businessPage:slug}/ad-campaigns/{campaignIdentifier}/export-engagements', [BusinessAdCampaignController::class, 'exportEngagements']);
            Route::post('/{businessPage:slug}/ad-campaigns/{campaignIdentifier}/contact-audience', [BusinessAdCampaignController::class, 'contactAudience']);
            Route::get('/{businessPage:slug}/ad-campaigns/{campaignIdentifier}/engagements/{member}', [BusinessAdCampaignController::class, 'memberEngagementDetail']);
            Route::get('/{businessPage:slug}/ad-campaigns/{campaignIdentifier}', [BusinessAdCampaignController::class, 'show']);
            Route::put('/{businessPage:slug}/ad-campaigns/{campaignIdentifier}', [BusinessAdCampaignController::class, 'update']);
            Route::post('/{businessPage:slug}/ad-campaigns/{campaignIdentifier}/submit', [BusinessAdCampaignController::class, 'submit']);
            Route::post('/{businessPage:slug}/ad-campaigns/{campaignIdentifier}/pause', [BusinessAdCampaignController::class, 'pause']);
            Route::post('/{businessPage:slug}/ad-campaigns/{campaignIdentifier}/resume', [BusinessAdCampaignController::class, 'resume']);
            Route::post('/{businessPage:slug}/ad-campaigns/{campaignIdentifier}/stop', [BusinessAdCampaignController::class, 'stop']);
            Route::post('/{businessPage:slug}/ad-campaigns/{campaignIdentifier}/restart', [BusinessAdCampaignController::class, 'restart']);
            Route::post('/{businessPage:slug}/ad-campaigns/{campaignIdentifier}/close', [BusinessAdCampaignController::class, 'close']);
            Route::post('/{businessPage:slug}/ad-campaigns/{campaignIdentifier}/add-funds', [BusinessAdCampaignController::class, 'addFunds']);
            Route::post('/{businessPage:slug}/ad-campaigns/{campaignIdentifier}/top-up', [BusinessAdCampaignController::class, 'addFunds']);
            Route::get('/{businessPage:slug}/posts/{post}/ad-campaign', [BusinessAdCampaignController::class, 'checkPostCampaign']);
        });

        // Ad Delivery Feed, Impression, Click, Interest, Follow-to-Earn & Landing Page Visit Reward Tracking
        Route::get('/ad-campaigns/feed', [BusinessAdCampaignController::class, 'feed']);
        Route::post('/ad-campaigns/{adCampaign}/impression', [BusinessAdCampaignController::class, 'recordImpression']);
        Route::post('/ad-campaigns/{adCampaign}/click', [BusinessAdCampaignController::class, 'recordClick']);
        Route::post('/ad-campaigns/{adCampaign}/interest', [BusinessAdCampaignController::class, 'interest']);
        Route::get('/ad-campaigns/{adCampaign}/follow-reward-status', [BusinessAdCampaignController::class, 'followRewardStatus']);
        Route::get('/ad-campaigns/{adCampaign}/landing-reward-status', [BusinessAdCampaignController::class, 'landingRewardStatus']);
        Route::get('/ad-campaigns/{adCampaign}/reward-preview', [BusinessAdCampaignController::class, 'landingRewardStatus']);
        Route::post('/ad-campaigns/{adCampaign}/follow-to-earn', [BusinessAdCampaignController::class, 'followToEarn']);
        Route::post('/ad-campaigns/{adCampaign}/qualify-visit', [BusinessAdCampaignController::class, 'qualifyVisit']);

        Route::post('/business-invitations/{invitation}/accept', [BusinessTeamController::class, 'acceptInvite'])->middleware(EnsureMemberMobileVerified::class);
        Route::post('/business-invitations/{invitation}/reject', [BusinessTeamController::class, 'rejectInvite']);

        // Business Directory
        Route::prefix('business-directory')->group(function () {
            Route::get('/', [BusinessDirectoryController::class, 'directory']);
            Route::get('/categories/{category}', [BusinessDirectoryController::class, 'category']);
            Route::get('/search', [BusinessDirectoryController::class, 'search']);
        });

        // Direct Messaging (1-on-1 Member Chat)
        Route::prefix('messages')->group(function () {
            Route::get('/', [DirectMessageController::class, 'index']);
            Route::get('/chat/{member}', [DirectMessageController::class, 'chat']);
            Route::get('/{conversation}/fetch', [DirectMessageController::class, 'fetchMessages']);
            Route::post('/{member}', [DirectMessageController::class, 'sendMessage']);
        });

        // Advertising Funds & Deposits
        Route::prefix('funds')->group(function () {
            Route::get('/deposit-settings', [AdDepositController::class, 'getDepositConfig']);
            Route::post('/deposits', [AdDepositController::class, 'store']);
            Route::get('/deposits', [AdDepositController::class, 'index']);
        });

        // DApp & Manual Deposit Verification System
        Route::prefix('deposit')->group(function () {
            Route::get('/config', [DepositVerificationController::class, 'getDepositConfig']);
            Route::post('/verify', [DepositVerificationController::class, 'verify']);
            Route::post('/manual-request', [DepositVerificationController::class, 'submitManualRequest']);
            Route::post('/dapp', [DepositVerificationController::class, 'submitDappDeposit']);
            Route::get('/history', [DepositVerificationController::class, 'history']);
        });

        // Reward Wallet & Dynamic Ad Reward Eligibility
        Route::prefix('rewards')->group(function () {
            Route::get('/wallet', [RewardWalletController::class, 'wallet']);
            Route::post('/wallet/send-otp', [RewardWalletController::class, 'sendWalletOtp']);
            Route::post('/wallet/verify-otp', [RewardWalletController::class, 'verifyWalletOtp']);
            Route::get('/history', [RewardWalletController::class, 'history']);
            Route::get('/ad-eligibility', [MemberAdRewardEligibilityController::class, 'checkEligibility']);
            Route::get('/rank', [MemberAdRewardEligibilityController::class, 'currentRank']);
            Route::get('/current-rank', [MemberAdRewardEligibilityController::class, 'currentRank']);
        });

        Route::get('/ad-rewards/eligibility', [MemberAdRewardEligibilityController::class, 'checkEligibility']);

        // Feedback & Suggestions
        Route::prefix('feedback-suggestions')->group(function () {
            Route::get('/', [FeedbackSuggestionController::class, 'index']);
            Route::post('/', [FeedbackSuggestionController::class, 'store']);
        });
        
        // Member Withdrawal Requests
        Route::prefix('withdrawals')->group(function () {
            Route::get('/', [WithdrawalController::class, 'index']);
            Route::post('/', [WithdrawalController::class, 'store']);
        });
    });
});

/*
|--------------------------------------------------------------------------
| Mobile Flutter Dedicated REST API Routes
|--------------------------------------------------------------------------
| Base URI: /api/v1/mobile/...
*/
Route::prefix('v1/mobile')->group(base_path('routes/mobile.php'));