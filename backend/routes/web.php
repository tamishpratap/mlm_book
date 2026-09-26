<?php

use App\Http\Controllers\Member\AccountController;
use App\Http\Controllers\Member\AccountVerificationController;
use App\Http\Controllers\Member\BlockController;
use App\Http\Controllers\Member\BusinessAnalyticsController;
use App\Http\Controllers\Member\BusinessDirectoryController;
use App\Http\Controllers\Member\BusinessFollowerController;
use App\Http\Controllers\Member\BusinessInboxController;
use App\Http\Controllers\Member\BusinessPageController;
use App\Http\Controllers\Member\BusinessReviewController;
use App\Http\Controllers\Member\BusinessTeamController;
use App\Http\Controllers\Member\BusinessVerificationController;
use App\Http\Controllers\Member\CommunityAnalyticsController;
use App\Http\Controllers\Member\CommunityController;
use App\Http\Controllers\Member\CommunityDiscoveryController;
use App\Http\Controllers\Member\CommunityInviteController;
use App\Http\Controllers\Member\CommunityMembershipController;
use App\Http\Controllers\Member\CommunityModerationController;
use App\Http\Controllers\Member\CommunityNotificationController;
use App\Http\Controllers\Member\CommunityPostController;
use App\Http\Controllers\Member\DashboardController;
use App\Http\Controllers\Member\EventController;
use App\Http\Controllers\Member\EventCampaignController;
use App\Http\Controllers\Member\FollowController;
use App\Http\Controllers\Member\FriendshipController;
use App\Http\Controllers\Member\GroupController;
use App\Http\Controllers\Member\MarketplaceController;
use App\Http\Controllers\Member\MemberAuthController;
use App\Http\Controllers\Member\MemberDirectoryProfileController;
use App\Http\Controllers\Member\MemberPasswordResetController;
use App\Http\Controllers\Member\MemberSearchController;
use App\Http\Controllers\Member\NotificationController;
use App\Http\Controllers\Member\PostController;
use App\Http\Controllers\Member\ProfileController;
use App\Http\Controllers\Member\SocialFeaturesController;
use App\Http\Controllers\Member\SocialsController;
use App\Http\Controllers\Member\StoryController;
use App\Http\Controllers\Member\WatchController;
use App\Http\Middleware\EnsureMemberMobileVerified;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\UpdateLastSeenMiddleware;
use Illuminate\Support\Facades\Route;


// Route::get('/', function () {
//     return redirect()->route('member.login');
// });

Route::get('/auth/google', [MemberAuthController::class, 'redirectToGoogle'])
    ->name('member.google.redirect');

Route::get('/auth/google/callback', [MemberAuthController::class, 'handleGoogleCallback'])
    ->name('member.google.callback');

Route::get('/auth/google-callback', [MemberAuthController::class, 'handleGoogleCallback']);

Route::get('/auth/google/pending', [MemberAuthController::class, 'getPendingGoogleSignup'])
    ->name('member.google.pending');

Route::post('/auth/google/complete', [MemberAuthController::class, 'completeGoogleSignup'])
    ->name('member.google.complete');

Route::get('/auth/google/introducer', function (Request $request) {
    return redirect('/member/google-introducer?' . http_build_query($request->all()));
})->name('member.google.introducer');

// Public Community Invite URL
Route::get('/community/invite/{code}', [CommunityInviteController::class, 'show'])
    ->name('member.community.invite.show');

// Notification & UI Audio Assets
Route::get('/sounds/{file}', function ($file) {
    $path = public_path('sounds/' . $file);
    if (file_exists($path)) {
        return response()->file($path, [
            'Content-Type' => 'audio/mpeg',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
    abort(404);
})->where('file', '[a-zA-Z0-9_\-\.]+');

// Member Password Reset & Auth Navigation Routes
Route::prefix('member')->name('member.')->group(function () {
    Route::get('/forgot-password', [MemberPasswordResetController::class, 'showForgotPassword'])
        ->name('forgot-password');
    Route::post('/forgot-password', [MemberPasswordResetController::class, 'sendResetLink'])
        ->name('forgot-password.send');
    Route::get('/reset-password/{token}', [MemberPasswordResetController::class, 'showResetPassword'])
        ->name('password.reset');
    Route::post('/reset-password', [MemberPasswordResetController::class, 'resetPassword'])
        ->name('password.update');
    Route::get('/login', function () {
        $frontendUrl = rtrim((string) (config('app.frontend_url') ?: config('app.url', 'https://mlmbookai.com')), '/');
        return redirect()->away($frontendUrl . '/member/login');
    })->name('login');
});

Route::prefix('member')
    ->name('member.')
    ->group(function () {
        Route::get('/login', [MemberAuthController::class, 'showLogin'])
            ->name('login');

        Route::post('/login', [MemberAuthController::class, 'login'])
            ->name('login.submit');

        Route::get('/register', [MemberAuthController::class, 'showRegister'])
            ->name('register');

        Route::get('/register/check-phone', [MemberAuthController::class, 'checkPhone'])
            ->name('register.check-phone');

        Route::get('/register/check-user-id', [MemberAuthController::class, 'checkUserId'])
            ->name('register.check-user-id');

        Route::get('/register/check-introducer', [MemberAuthController::class, 'checkIntroducer'])
            ->name('register.check-introducer');

        Route::post('/register', [MemberAuthController::class, 'register'])
            ->name('register.submit');

        Route::get('/register/verify', [MemberAuthController::class, 'showVerifyEmail'])
            ->name('register.verify');

        Route::post('/register/verify', [MemberAuthController::class, 'verifyEmailOtp'])
            ->name('register.verify.submit');

        Route::post('/register/resend-otp', [MemberAuthController::class, 'resendRegistrationOtp'])
            ->name('register.resend-otp');

        Route::post('/register/cancel', [MemberAuthController::class, 'cancelRegistration'])
            ->name('register.cancel');

        Route::get('/forgot-password', [MemberPasswordResetController::class, 'showForgotPassword'])
            ->name('forgot-password');

        Route::post('/forgot-password', [MemberPasswordResetController::class, 'sendResetLink'])
            ->name('forgot-password.send');

        Route::get('/reset-password/{token}', [MemberPasswordResetController::class, 'showResetPassword'])
            ->name('password.reset');

        Route::post('/reset-password', [MemberPasswordResetController::class, 'resetPassword'])
            ->name('password.update');

        Route::middleware(['auth:member', UpdateLastSeenMiddleware::class, SecurityHeadersMiddleware::class])->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index'])
                ->name('dashboard');

            Route::get('/socials', [SocialsController::class, 'index'])
                ->name('socials');

            Route::get('/watch', [WatchController::class, 'index'])
                ->name('watch.index');

            Route::get('/create-video', function () {
                return redirect()->route('member.watch.index');
            })->name('create-video');

            Route::get('/videos/create', function () {
                return redirect()->route('member.watch.index');
            });

            Route::post('/posts', [PostController::class, 'store'])
                ->middleware(EnsureMemberMobileVerified::class)
                ->name('posts.store');

            Route::get('/posts/{post}', [PostController::class, 'show'])
                ->whereNumber('post')
                ->name('posts.show');

            Route::post('/posts/{post}/like', [PostController::class, 'toggleLike'])
                ->middleware(EnsureMemberMobileVerified::class)
                ->whereNumber('post')
                ->name('posts.like');

            Route::get('/posts/{post}/likers', [PostController::class, 'likers'])
                ->whereNumber('post')
                ->name('posts.likers');

            Route::post('/posts/{post}/react', [PostController::class, 'react'])
                ->middleware(EnsureMemberMobileVerified::class)
                ->whereNumber('post')
                ->name('posts.react');

            Route::get('/posts/{post}/reactors', [PostController::class, 'reactors'])
                ->whereNumber('post')
                ->name('posts.reactors');

            Route::get('/posts/{post}/comments', [PostController::class, 'comments'])
                ->whereNumber('post')
                ->name('posts.comments.index');

            Route::post('/posts/{post}/comments', [PostController::class, 'storeComment'])
                ->middleware(EnsureMemberMobileVerified::class)
                ->whereNumber('post')
                ->name('posts.comments.store');

            Route::delete('/comments/{comment}', [PostController::class, 'destroyComment'])
                ->whereNumber('comment')
                ->name('posts.comments.destroy');

            Route::put('/comments/{comment}', [PostController::class, 'updateComment'])
                ->whereNumber('comment')
                ->name('posts.comments.update');

            Route::get('/comments/{comment}/replies', [PostController::class, 'replies'])
                ->whereNumber('comment')
                ->name('comments.replies.index');

            Route::post('/comments/{comment}/replies', [PostController::class, 'storeReply'])
                ->middleware(EnsureMemberMobileVerified::class)
                ->whereNumber('comment')
                ->name('comments.replies.store');

            Route::post('/comments/{comment}/react', [PostController::class, 'reactComment'])
                ->whereNumber('comment')
                ->name('comments.react');

            Route::get('/comments/{comment}/reactors', [PostController::class, 'commentReactors'])
                ->whereNumber('comment')
                ->name('comments.reactors');

            Route::post('/posts/{post}/share', [PostController::class, 'sharePost'])
                ->whereNumber('post')
                ->name('posts.share');

            Route::post('/posts/{post}/send-to-friends', [PostController::class, 'sendToFriends'])
                ->whereNumber('post')
                ->name('posts.send-to-friends');

            Route::get('/posts/{post}/sharers', [PostController::class, 'postSharers'])
                ->whereNumber('post')
                ->name('posts.sharers');

            Route::post('/posts/{post}/save', [PostController::class, 'toggleSave'])
                ->whereNumber('post')
                ->name('posts.save');

            Route::post('/posts/{post}/hide', [PostController::class, 'hidePost'])
                ->whereNumber('post')
                ->name('posts.hide');

            Route::post('/posts/{post}/report', [PostController::class, 'reportPost'])
                ->name('posts.report');

            Route::post('/posts/{post}/pin', [PostController::class, 'togglePin'])
                ->whereNumber('post')
                ->name('posts.pin');

            Route::get('/saved-posts', [PostController::class, 'savedPosts'])
                ->name('posts.saved');

            Route::get('/feed/check-new', [PostController::class, 'checkNewPosts'])
                ->name('posts.check-new');

            Route::post('/stories', [StoryController::class, 'store'])
                ->middleware(EnsureMemberMobileVerified::class)
                ->name('stories.store');

            Route::get('/stories/{story}', [StoryController::class, 'show'])
                ->whereNumber('story')
                ->name('stories.show');

            Route::get('/stories/{story}/viewers', [StoryController::class, 'viewers'])
                ->whereNumber('story')
                ->name('stories.viewers');

            Route::post('/stories/{story}/like', [StoryController::class, 'toggleLike'])
                ->whereNumber('story')
                ->name('stories.like');

            Route::post('/stories/{story}/react', [StoryController::class, 'react'])
                ->whereNumber('story')
                ->name('stories.react');

            Route::get('/stories/{story}/reactors', [StoryController::class, 'reactors'])
                ->whereNumber('story')
                ->name('stories.reactors');

            Route::post('/stories/{story}/reply', [StoryController::class, 'reply'])
                ->whereNumber('story')
                ->name('stories.reply');

            Route::get('/stories/{story}/replies', [StoryController::class, 'replies'])
                ->whereNumber('story')
                ->name('stories.replies');

            Route::delete('/stories/{story}', [StoryController::class, 'destroy'])
                ->whereNumber('story')
                ->name('stories.destroy');

            Route::delete('/story-replies/{reply}', [StoryController::class, 'destroyReply'])
                ->whereNumber('reply')
                ->name('stories.reply.destroy');

            Route::get('/notifications', [NotificationController::class, 'index'])
                ->name('notifications.index');

            Route::get('/notifications/dropdown', [NotificationController::class, 'dropdown'])
                ->name('notifications.dropdown');

            Route::get('/notifications/poll', [NotificationController::class, 'poll'])
                ->name('notifications.poll');

            Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
                ->name('notifications.read-all');

            Route::delete('/notifications/clear-all', [NotificationController::class, 'clearAll'])
                ->name('notifications.clear-all');

            Route::get('/notifications/{notificationId}', [NotificationController::class, 'show'])
                ->name('notifications.show');

            Route::post('/notifications/{notificationId}/read', [NotificationController::class, 'markAsRead'])
                ->name('notifications.read');

            Route::delete('/notifications/{notificationId}', [NotificationController::class, 'destroy'])
                ->name('notifications.destroy');

            Route::get('/search', [MemberSearchController::class, 'index'])
                ->name('search');

            Route::get('/search/results', [MemberSearchController::class, 'results'])
                ->name('search.results');

            Route::get('/friends', [FriendshipController::class, 'index'])
                ->name('friends.index');

            Route::get('/friend-requests', [FriendshipController::class, 'requests'])
                ->name('friend-requests.index');

            Route::post('/friends/request/{member}', [FriendshipController::class, 'send'])
                ->middleware(EnsureMemberMobileVerified::class)
                ->whereNumber('member')
                ->name('friends.request');

            Route::post('/friend-requests/{friendship}/accept', [FriendshipController::class, 'accept'])
                ->whereNumber('friendship')
                ->name('friend-requests.accept');

            Route::post('/friend-requests/{friendship}/reject', [FriendshipController::class, 'reject'])
                ->whereNumber('friendship')
                ->name('friend-requests.reject');

            Route::delete('/friend-requests/{friendship}/cancel', [FriendshipController::class, 'cancel'])
                ->whereNumber('friendship')
                ->name('friend-requests.cancel');

            Route::post('/people/{member}/follow', [FollowController::class, 'toggleFollow'])
                ->middleware(EnsureMemberMobileVerified::class)
                ->whereNumber('member')
                ->name('people.follow');

            Route::get('/people/{member}/followers', [FollowController::class, 'followers'])
                ->whereNumber('member')
                ->name('people.followers');

            Route::get('/people/{member}/following', [FollowController::class, 'following'])
                ->whereNumber('member')
                ->name('people.following');

            Route::get('/people/{member}', [MemberDirectoryProfileController::class, 'show'])
                ->whereNumber('member')
                ->name('people.show');

            Route::get('/people/{member}/friends', [FriendshipController::class, 'memberFriends'])
                ->whereNumber('member')
                ->name('people.friends');

            Route::get('/profile', [ProfileController::class, 'show'])
                ->name('profile.show');

            Route::get('/profile/edit', [ProfileController::class, 'edit'])
                ->name('profile.edit');

            Route::put('/profile', [ProfileController::class, 'update'])
                ->name('profile.update');

            Route::post('/profile/photo', [ProfileController::class, 'updateProfilePhoto'])
                ->name('profile.photo.update');

            Route::delete('/profile/photo', [ProfileController::class, 'removeProfilePhoto'])
                ->name('profile.photo.remove');

            Route::post('/profile/cover', [ProfileController::class, 'updateCoverPhoto'])
                ->name('profile.cover.update');

            Route::delete('/profile/cover', [ProfileController::class, 'removeCoverPhoto'])
                ->name('profile.cover.remove');

            Route::get('/account/settings', [AccountController::class, 'settings'])
                ->name('account.settings');

            Route::put('/account/settings', [AccountController::class, 'updateSettings'])
                ->name('account.settings.update');

            Route::get('/account/check-introducer', [AccountController::class, 'checkIntroducer'])
                ->name('account.check-introducer');

            Route::post('/account/introducer', [AccountController::class, 'claimIntroducer'])
                ->name('account.introducer.claim');

            Route::get('/account/verification-status', [AccountVerificationController::class, 'getVerificationStatus'])
                ->name('account.verification.status');

            Route::post('/account/verification/initiate', [AccountVerificationController::class, 'initiateWhatsAppVerification'])
                ->name('account.verification.initiate');

            Route::post('/account/verification/submit-hi', [AccountVerificationController::class, 'submitWhatsAppVerificationRequest'])
                ->name('account.verification.submit-hi');

            Route::post('/account/mobile/send-otp', [AccountVerificationController::class, 'sendMobileOtp'])
                ->name('account.mobile.send-otp');

            Route::post('/account/mobile/verify-otp', [AccountVerificationController::class, 'verifyMobileOtp'])
                ->name('account.mobile.verify-otp');

            Route::post('/account/email/send-otp', [AccountVerificationController::class, 'sendEmailOtp'])
                ->name('account.email.send-otp');

            Route::post('/account/email/verify-otp', [AccountVerificationController::class, 'verifyEmailOtp'])
                ->name('account.email.verify-otp');

            Route::get('/account/security', [AccountController::class, 'security'])
                ->name('account.security');

            Route::put('/account/password', [AccountController::class, 'updatePassword'])
                ->name('account.password.update');

            Route::post('/people/{member}/block', [BlockController::class, 'block'])
                ->whereNumber('member')
                ->name('people.block');

            Route::delete('/people/{member}/unblock', [BlockController::class, 'unblock'])
                ->whereNumber('member')
                ->name('people.unblock');

            Route::get('/blocked-users', [BlockController::class, 'index'])
                ->name('blocked-users.index');

            Route::get('/people/suggestions', [SocialFeaturesController::class, 'suggestions'])
                ->name('people.suggestions');

            Route::get('/profile/visitors', [SocialFeaturesController::class, 'visitors'])
                ->name('profile.visitors');

            Route::get('/marketplace', [MarketplaceController::class, 'index'])
                ->name('marketplace.index');

            Route::get('/marketplace/create', [MarketplaceController::class, 'create'])
                ->name('marketplace.create');

            Route::post('/marketplace', [MarketplaceController::class, 'store'])
                ->name('marketplace.store');

            Route::get('/marketplace/my-products', [MarketplaceController::class, 'myProducts'])
                ->name('marketplace.my-products');

            Route::get('/marketplace/saved', [MarketplaceController::class, 'saved'])
                ->name('marketplace.saved');

            Route::get('/marketplace/{product}', [MarketplaceController::class, 'show'])
                ->whereNumber('product')
                ->name('marketplace.show');

            Route::get('/marketplace/{product}/edit', [MarketplaceController::class, 'edit'])
                ->whereNumber('product')
                ->name('marketplace.edit');

            Route::put('/marketplace/{product}', [MarketplaceController::class, 'update'])
                ->whereNumber('product')
                ->name('marketplace.update');

            Route::delete('/marketplace/{product}', [MarketplaceController::class, 'destroy'])
                ->whereNumber('product')
                ->name('marketplace.destroy');

            Route::post('/marketplace/{product}/status', [MarketplaceController::class, 'toggleStatus'])
                ->whereNumber('product')
                ->name('marketplace.status');

            Route::post('/marketplace/{product}/save', [MarketplaceController::class, 'toggleSave'])
                ->whereNumber('product')
                ->name('marketplace.save');

            Route::post('/marketplace/{product}/report', [MarketplaceController::class, 'report'])
                ->whereNumber('product')
                ->name('marketplace.report');

            // Community Base & Static Routes
            Route::get('/community', [CommunityController::class, 'index'])
                ->name('community.index');

            Route::get('/community/create', [CommunityController::class, 'create'])
                ->name('community.create');

            Route::post('/community', [CommunityController::class, 'store'])
                ->name('community.store');

            // Community Discovery Platform Routes (Phase 8)
            Route::get('/community/discover', [CommunityDiscoveryController::class, 'discover'])
                ->name('community.discover');

            Route::get('/community/search-ajax', [CommunityDiscoveryController::class, 'searchAjax'])
                ->name('community.search-ajax');

            // Community Notifications Center Routes (Phase 7)
            Route::get('/community/notifications', [CommunityNotificationController::class, 'index'])
                ->name('community.notifications.index');

            Route::post('/community/notifications/{notification}/read', [CommunityNotificationController::class, 'markAsRead'])
                ->name('community.notifications.read');

            Route::post('/community/notifications/mark-all-read', [CommunityNotificationController::class, 'markAllAsRead'])
                ->name('community.notifications.mark-all-read');

            Route::get('/community/notifications/unread-count', [CommunityNotificationController::class, 'unreadCount'])
                ->name('community.notifications.unread-count');

            // Community Invite Process Route (Phase 4)
            Route::post('/community/invite/{code}/join', [CommunityInviteController::class, 'processJoin'])
                ->name('community.invite.join');

            // Community Wildcard & Slug Parameter Routes
            Route::get('/community/{community:slug}', [CommunityController::class, 'show'])
                ->name('community.show');

            Route::get('/community/{community:slug}/edit', [CommunityController::class, 'edit'])
                ->name('community.edit');

            Route::put('/community/{community:slug}', [CommunityController::class, 'update'])
                ->name('community.update');

            Route::delete('/community/{community:slug}', [CommunityController::class, 'destroy'])
                ->name('community.destroy');

            Route::post('/community/{community:slug}/cover', [CommunityController::class, 'updateCover'])
                ->name('community.cover.update');

            Route::post('/community/{community:slug}/cover/remove', [CommunityController::class, 'removeCover'])
                ->name('community.cover.remove');

            Route::post('/community/{community:slug}/logo', [CommunityController::class, 'updateLogo'])
                ->name('community.logo.update');

            Route::post('/community/{community:slug}/logo/remove', [CommunityController::class, 'removeLogo'])
                ->name('community.logo.remove');

            // Community Membership Routes (Phase 2)
            Route::post('/community/{community:slug}/join', [CommunityMembershipController::class, 'join'])
                ->name('community.join');

            Route::post('/community/{community:slug}/leave', [CommunityMembershipController::class, 'leave'])
                ->name('community.leave');

            Route::post('/community/{community:slug}/requests/{membership}/handle', [CommunityMembershipController::class, 'handleRequest'])
                ->name('community.requests.handle');

            Route::post('/community/{community:slug}/members/{membership}/role', [CommunityMembershipController::class, 'updateRole'])
                ->name('community.members.role');

            Route::delete('/community/{community:slug}/members/{membership}', [CommunityMembershipController::class, 'removeMember'])
                ->name('community.members.remove');

            // Community Feed & Post Routes (Phase 3)
            Route::post('/community/{community:slug}/posts', [CommunityPostController::class, 'store'])
                ->middleware(EnsureMemberMobileVerified::class)
                ->name('community.posts.store');

            Route::post('/community/{community:slug}/posts/{post}/pin', [CommunityPostController::class, 'togglePin'])
                ->name('community.posts.pin');

            Route::post('/community/{community:slug}/posts/{post}/announcement', [CommunityPostController::class, 'toggleAnnouncement'])
                ->name('community.posts.announcement');

            Route::delete('/community/{community:slug}/posts/{post}', [CommunityPostController::class, 'destroy'])
                ->name('community.posts.destroy');

            // Community Invite Management Routes (Phase 4)
            Route::get('/community/{community:slug}/invites/friends', [CommunityInviteController::class, 'getFriends'])
                ->name('community.invites.friends');

            Route::post('/community/{community:slug}/invites/send', [CommunityInviteController::class, 'sendInvites'])
                ->name('community.invites.send');

            Route::post('/community/{community:slug}/invites/generate', [CommunityInviteController::class, 'generate'])
                ->name('community.invites.generate');

            Route::post('/community/{community:slug}/invites/revoke', [CommunityInviteController::class, 'revoke'])
                ->name('community.invites.revoke');

            // Community Moderation & Admin Panel Routes (Phase 5)
            Route::get('/community/{community:slug}/admin', [CommunityModerationController::class, 'adminPanel'])
                ->name('community.admin');

            Route::post('/community/{community:slug}/moderation/ban', [CommunityModerationController::class, 'banMember'])
                ->name('community.moderation.ban');

            Route::post('/community/{community:slug}/moderation/unban', [CommunityModerationController::class, 'unbanMember'])
                ->name('community.moderation.unban');

            Route::post('/community/{community:slug}/moderation/mute', [CommunityModerationController::class, 'muteMember'])
                ->name('community.moderation.mute');

            Route::post('/community/{community:slug}/moderation/unmute', [CommunityModerationController::class, 'unmuteMember'])
                ->name('community.moderation.unmute');

            Route::post('/community/{community:slug}/moderation/warn', [CommunityModerationController::class, 'warnMember'])
                ->name('community.moderation.warn');

            Route::post('/community/{community:slug}/reports', [CommunityModerationController::class, 'storeReport'])
                ->name('community.reports.store');

            Route::post('/community/{community:slug}/reports/{report}/handle', [CommunityModerationController::class, 'handleReport'])
                ->name('community.reports.handle');

            Route::put('/community/{community:slug}/settings', [CommunityModerationController::class, 'updateSettings'])
                ->name('community.settings.update');

            Route::post('/community/{community:slug}/transfer-ownership', [CommunityModerationController::class, 'transferOwnership'])
                ->name('community.transfer-ownership');

            Route::post('/community/{community:slug}/preferences', [CommunityNotificationController::class, 'updatePreferences'])
                ->name('community.preferences.update');

            Route::get('/community/{community:slug}/activity', [CommunityNotificationController::class, 'activityTimeline'])
                ->name('community.activity');

            // Community Analytics Platform Routes (Phase 9)
            Route::get('/community/{community:slug}/analytics', [CommunityAnalyticsController::class, 'index'])
                ->name('community.analytics');

            Route::get('/community/{community:slug}/analytics/export', [CommunityAnalyticsController::class, 'exportCsv'])
                ->name('community.analytics.export');

            // Legacy Group Redirects
            Route::get('/groups', function () {
                return redirect()->route('member.community.index');
            })->name('groups.index');

            Route::get('/groups/create', function () {
                return redirect()->route('member.community.create');
            })->name('groups.create');

            Route::get('/groups/{group}', function (Group $group) {
                return redirect()->route('member.community.index');
            })->name('groups.show');

            // Event Routes
            Route::get('/events', [EventController::class, 'index'])
                ->name('events.index');

            Route::get('/events/create', [EventController::class, 'create'])
                ->name('events.create');

            Route::post('/events', [EventController::class, 'store'])
                ->name('events.store');

            Route::get('/events/{event}', [EventController::class, 'show'])
                ->whereNumber('event')
                ->name('events.show');

            Route::get('/events/{event}/edit', [EventController::class, 'edit'])
                ->whereNumber('event')
                ->name('events.edit');

            Route::put('/events/{event}', [EventController::class, 'update'])
                ->whereNumber('event')
                ->name('events.update');

            Route::delete('/events/{event}', [EventController::class, 'destroy'])
                ->whereNumber('event')
                ->name('events.destroy');

            Route::post('/events/{event}/respond', [EventController::class, 'respond'])
                ->whereNumber('event')
                ->name('events.respond');

            Route::post('/events/{event}/invite', [EventController::class, 'invite'])
                ->whereNumber('event')
                ->name('events.invite');

            Route::get('/events/{event}/outreach', [EventController::class, 'outreach'])
                ->whereNumber('event')
                ->name('events.outreach');

            Route::post('/events/{event}/posts', [EventController::class, 'storePost'])
                ->whereNumber('event')
                ->name('events.posts.store');

            Route::get('/events/{event}/campaign', [EventCampaignController::class, 'show'])
                ->whereNumber('event')
                ->name('events.campaign.show');

            Route::post('/events/{event}/campaign', [EventCampaignController::class, 'store'])
                ->whereNumber('event')
                ->name('events.campaign.store');

            Route::post('/events/{event}/campaign/allocate-budget', [EventCampaignController::class, 'allocateBudget'])
                ->whereNumber('event')
                ->name('events.campaign.allocate-budget');

            Route::post('/events/{event}/campaign/add-funds', [EventCampaignController::class, 'addFunds'])
                ->whereNumber('event')
                ->name('events.campaign.add-funds');

            Route::post('/events/{event}/add-funds', [EventCampaignController::class, 'addFunds'])
                ->whereNumber('event')
                ->name('events.add-funds');

            Route::post('/events/{event}/campaign/activate', [EventCampaignController::class, 'activate'])
                ->whereNumber('event')
                ->name('events.campaign.activate');

            Route::post('/events/{event}/campaign/pause', [EventCampaignController::class, 'pause'])
                ->whereNumber('event')
                ->name('events.campaign.pause');

            Route::post('/events/{event}/campaign/resume', [EventCampaignController::class, 'resume'])
                ->whereNumber('event')
                ->name('events.campaign.resume');

            Route::post('/events/{event}/campaign/reactivate', [EventCampaignController::class, 'reactivate'])
                ->whereNumber('event')
                ->name('events.campaign.reactivate');

            Route::get('/events/{event}/campaign/analytics', [EventCampaignController::class, 'analytics'])
                ->whereNumber('event')
                ->name('events.campaign.analytics');

            Route::get('/events/{event}/campaign/participants', [EventCampaignController::class, 'participants'])
                ->whereNumber('event')
                ->name('events.campaign.participants');

            Route::get('/events/sponsored-feed', [EventCampaignController::class, 'sponsoredFeed'])
                ->name('events.sponsored-feed');

            Route::get('/events/{event}/reward-preview', [EventCampaignController::class, 'rewardPreview'])
                ->whereNumber('event')
                ->name('events.reward-preview');

            Route::post('/events/{event}/campaign/qualify-interest', [EventCampaignController::class, 'qualifyInterest'])
                ->whereNumber('event')
                ->name('events.campaign.qualify-interest');

            Route::post('/events/{event}/campaign/claim', [EventCampaignController::class, 'qualifyInterest'])
                ->whereNumber('event')
                ->name('events.campaign.claim');

            // Business Pages Routes (Phase 1 Foundation)
            Route::get('/business-pages', [BusinessPageController::class, 'index'])
                ->name('business-pages.index');

            Route::get('/business-pages/create', [BusinessPageController::class, 'create'])
                ->name('business-pages.create');

            Route::post('/business-pages', [BusinessPageController::class, 'store'])
                ->middleware(EnsureMemberMobileVerified::class)
                ->name('business-pages.store');

            Route::get('/business-pages/{businessPage:slug}', [BusinessPageController::class, 'show'])
                ->name('business-pages.show');

            Route::get('/business-pages/{businessPage:slug}/edit', [BusinessPageController::class, 'edit'])
                ->name('business-pages.edit');

            Route::put('/business-pages/{businessPage:slug}', [BusinessPageController::class, 'update'])
                ->name('business-pages.update');

            Route::delete('/business-pages/{businessPage:slug}', [BusinessPageController::class, 'destroy'])
                ->name('business-pages.destroy');

            // Business Pages Timeline Posts Routes (Phase 3)
            Route::post('/business-pages/{businessPage:slug}/posts', [BusinessPageController::class, 'storePost'])
                ->middleware(EnsureMemberMobileVerified::class)
                ->name('business-pages.posts.store');

            Route::post('/business-pages/{businessPage:slug}/posts/{post}/pin', [BusinessPageController::class, 'togglePinPost'])
                ->name('business-pages.posts.pin');

            Route::post('/business-pages/{businessPage:slug}/posts/{post}/feature', [BusinessPageController::class, 'toggleFeaturePost'])
                ->name('business-pages.posts.feature');

            Route::delete('/business-pages/{businessPage:slug}/posts/{post}', [BusinessPageController::class, 'destroyPost'])
                ->name('business-pages.posts.destroy');

            // Business Pages Team Management Routes (Phase 4)
            Route::get('/business-pages/{businessPage:slug}/team', [BusinessTeamController::class, 'index'])
                ->name('business-pages.team.index');

            Route::post('/business-pages/{businessPage:slug}/team/invite', [BusinessTeamController::class, 'invite'])
                ->name('business-pages.team.invite');

            Route::delete('/business-pages/{businessPage:slug}/team/invitations/{invitation}', [BusinessTeamController::class, 'cancelInvite'])
                ->name('business-pages.team.invitations.cancel');

            Route::put('/business-pages/{businessPage:slug}/team/members/{teamMember}/role', [BusinessTeamController::class, 'changeRole'])
                ->name('business-pages.team.members.role');

            Route::delete('/business-pages/{businessPage:slug}/team/members/{teamMember}', [BusinessTeamController::class, 'removeMember'])
                ->name('business-pages.team.members.remove');

            Route::post('/business-invitations/{invitation}/accept', [BusinessTeamController::class, 'acceptInvite'])
                ->middleware(EnsureMemberMobileVerified::class)
                ->name('business-invitations.accept');

            Route::post('/business-invitations/{invitation}/reject', [BusinessTeamController::class, 'rejectInvite'])
                ->name('business-invitations.reject');

            // Business Pages Followers Routes (Phase 5)
            Route::post('/business-pages/{businessPage:slug}/follow', [BusinessFollowerController::class, 'toggleFollow'])
                ->middleware(EnsureMemberMobileVerified::class)
                ->name('business-pages.follow.toggle');

            Route::post('/business-pages/{businessPage:slug}/follow-requests/{follower}', [BusinessFollowerController::class, 'handleRequest'])
                ->name('business-pages.followers.handle-request');

            Route::delete('/business-pages/{businessPage:slug}/followers/{follower}', [BusinessFollowerController::class, 'removeFollower'])
                ->name('business-pages.followers.remove');

            Route::post('/business-pages/{businessPage:slug}/invite-to-follow', [BusinessFollowerController::class, 'inviteToFollow'])
                ->name('business-pages.followers.invite');

            // Business Pages Reviews Routes (Phase 6)
            Route::post('/business-pages/{businessPage:slug}/reviews', [BusinessReviewController::class, 'store'])
                ->name('business-pages.reviews.store');

            Route::put('/business-pages/{businessPage:slug}/reviews/{review}', [BusinessReviewController::class, 'update'])
                ->name('business-pages.reviews.update');

            Route::delete('/business-pages/{businessPage:slug}/reviews/{review}', [BusinessReviewController::class, 'destroy'])
                ->name('business-pages.reviews.destroy');

            Route::post('/business-pages/{businessPage:slug}/reviews/{review}/reply', [BusinessReviewController::class, 'storeReply'])
                ->name('business-pages.reviews.reply');

            Route::post('/business-pages/{businessPage:slug}/reviews/{review}/vote', [BusinessReviewController::class, 'vote'])
                ->name('business-pages.reviews.vote');

            Route::post('/business-pages/{businessPage:slug}/reviews/{review}/report', [BusinessReviewController::class, 'report'])
                ->name('business-pages.reviews.report');

            Route::post('/business-pages/{businessPage:slug}/reviews/{review}/hide', [BusinessReviewController::class, 'toggleHide'])
                ->name('business-pages.reviews.hide');

            // Business Pages Inbox & Customer Messaging Routes (Phase 7)
            Route::get('/business-pages/{businessPage:slug}/inbox', [BusinessInboxController::class, 'index'])
                ->name('business-pages.inbox.index');

            Route::post('/business-pages/{businessPage:slug}/inbox/chat', [BusinessInboxController::class, 'startConversation'])
                ->name('business-pages.inbox.start');

            Route::get('/business-pages/{businessPage:slug}/inbox/conversations/{conversation}', [BusinessInboxController::class, 'showConversation'])
                ->name('business-pages.inbox.show');

            Route::post('/business-pages/{businessPage:slug}/inbox/conversations/{conversation}/messages', [BusinessInboxController::class, 'sendMessage'])
                ->name('business-pages.inbox.send');

            Route::post('/business-pages/{businessPage:slug}/inbox/conversations/{conversation}/request', [BusinessInboxController::class, 'handleRequest'])
                ->name('business-pages.inbox.request');

            Route::post('/business-pages/{businessPage:slug}/inbox/conversations/{conversation}/star', [BusinessInboxController::class, 'toggleStar'])
                ->name('business-pages.inbox.star');

            Route::post('/business-pages/{businessPage:slug}/inbox/conversations/{conversation}/pin', [BusinessInboxController::class, 'togglePin'])
                ->name('business-pages.inbox.pin');

            Route::put('/business-pages/{businessPage:slug}/inbox/conversations/{conversation}/status', [BusinessInboxController::class, 'updateStatus'])
                ->name('business-pages.inbox.status');

            Route::post('/business-pages/{businessPage:slug}/inbox/quick-replies', [BusinessInboxController::class, 'storeQuickReply'])
                ->name('business-pages.inbox.quick-replies.store');

            Route::delete('/business-pages/{businessPage:slug}/inbox/quick-replies/{quickReply}', [BusinessInboxController::class, 'destroyQuickReply'])
                ->name('business-pages.inbox.quick-replies.destroy');

            Route::get('/business-pages/{businessPage:slug}/notifications', [BusinessInboxController::class, 'notifications'])
                ->name('business-pages.notifications.index');

            Route::post('/business-pages/{businessPage:slug}/notifications/read', [BusinessInboxController::class, 'markNotificationsRead'])
                ->name('business-pages.notifications.read');

            // Business Pages Directory & Discovery Routes (Phase 8)
            Route::get('/business-directory', [BusinessDirectoryController::class, 'directory'])
                ->name('business-pages.directory.index');

            Route::get('/business-directory/categories/{category}', [BusinessDirectoryController::class, 'category'])
                ->name('business-pages.directory.category');

            Route::get('/business-directory/search', [BusinessDirectoryController::class, 'search'])
                ->name('business-pages.directory.search');

            // Business Pages Analytics & Insights Routes (Phase 9)
            Route::get('/business-pages/{businessPage:slug}/analytics', [BusinessAnalyticsController::class, 'index'])
                ->name('business-pages.analytics.index');

            Route::get('/business-pages/{businessPage:slug}/analytics/data', [BusinessAnalyticsController::class, 'data'])
                ->name('business-pages.analytics.data');

            // Business Pages Verification Routes (Phase 10)
            Route::get('/business-pages/{businessPage:slug}/verification', [BusinessVerificationController::class, 'index'])
                ->name('business-pages.verification.index');

            Route::post('/business-pages/{businessPage:slug}/verification', [BusinessVerificationController::class, 'store'])
                ->name('business-pages.verification.store');

            Route::post('/logout', [MemberAuthController::class, 'logout'])
                ->name('logout');

            /*
             | Future authenticated Member Panel routes must be added here.
             */
        });
    });

/*
 |--------------------------------------------------------------------------
 | Admin Panel Routes
 |--------------------------------------------------------------------------
 */
Route::prefix('admin')->name('admin.')->group(function () {
    // Admin Auth Routes (Guest accessible)
    Route::get('/', function () {
        return redirect()->route('admin.login');
    });
    Route::get('/login', [\App\Http\Controllers\Admin\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Admin\Auth\LoginController::class, 'login'])->name('login.submit');
    Route::post('/logout', [\App\Http\Controllers\Admin\Auth\LoginController::class, 'logout'])->name('logout');

    // Authenticated Admin Routes
    Route::middleware('auth:admin')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

        // Manual & DApp Deposit Requests Management Routes
        Route::prefix('deposits')->name('deposits.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\DepositManagementWebController::class, 'index'])->name('index');
            Route::post('/{id}/verify-onchain', [\App\Http\Controllers\Admin\DepositManagementWebController::class, 'verifyOnChain'])->name('verify-onchain');
            Route::post('/{id}/approve', [\App\Http\Controllers\Admin\DepositManagementWebController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [\App\Http\Controllers\Admin\DepositManagementWebController::class, 'reject'])->name('reject');
            Route::get('/settings', [\App\Http\Controllers\Admin\DepositManagementWebController::class, 'settings'])->name('settings');
            Route::post('/settings', [\App\Http\Controllers\Admin\DepositManagementWebController::class, 'updateSettings'])->name('settings.update');
        });

        // Members Management Routes
        Route::prefix('members')->name('members.')->group(function () {
            Route::get('/security', [\App\Http\Controllers\Admin\MemberManagementController::class, 'securityView'])->name('security');
            Route::get('/wallet-address', [\App\Http\Controllers\Admin\MemberManagementController::class, 'walletAddressView'])->name('wallet-address');
            Route::get('/search', [\App\Http\Controllers\Admin\MemberManagementController::class, 'search'])->name('search');
            Route::get('/', [\App\Http\Controllers\Admin\MemberManagementController::class, 'active'])->name('index');
            Route::get('/active', [\App\Http\Controllers\Admin\MemberManagementController::class, 'active'])->name('active');
            Route::get('/pending', [\App\Http\Controllers\Admin\MemberManagementController::class, 'pending'])->name('pending');
            Route::get('/blocked', [\App\Http\Controllers\Admin\MemberManagementController::class, 'blocked'])->name('blocked');
            Route::get('/export/{format?}', [\App\Http\Controllers\Admin\MemberManagementController::class, 'export'])->name('export');
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\MemberManagementController::class, 'bulkAction'])->name('bulk-action');
            Route::get('/{member}', [\App\Http\Controllers\Admin\MemberManagementController::class, 'show'])->name('show');
            Route::get('/{member}/edit', [\App\Http\Controllers\Admin\MemberManagementController::class, 'edit'])->name('edit');
            Route::put('/{member}', [\App\Http\Controllers\Admin\MemberManagementController::class, 'update'])->name('update');
            Route::post('/{member}/password', [\App\Http\Controllers\Admin\MemberManagementController::class, 'updatePassword'])->name('password.update');
            Route::match(['put', 'post'], '/{member}/wallet-address', [\App\Http\Controllers\Admin\MemberManagementController::class, 'updateWalletAddress'])->name('wallet-address.update');
            Route::post('/{member}/status', [\App\Http\Controllers\Admin\MemberManagementController::class, 'updateStatus'])->name('status');
            Route::post('/{member}/block', [\App\Http\Controllers\Admin\MemberManagementController::class, 'block'])->name('block');
            Route::post('/{member}/unblock', [\App\Http\Controllers\Admin\MemberManagementController::class, 'unblock'])->name('unblock');
            Route::post('/{member}/approve', [\App\Http\Controllers\Admin\MemberManagementController::class, 'approve'])->name('approve');
            Route::post('/{member}/approve-mobile-verification', [\App\Http\Controllers\Admin\MemberManagementController::class, 'approve'])->name('approve-mobile-verification');
            Route::post('/{member}/reject', [\App\Http\Controllers\Admin\MemberManagementController::class, 'reject'])->name('reject');
            Route::post('/{member}/reject-mobile-verification', [\App\Http\Controllers\Admin\MemberManagementController::class, 'reject'])->name('reject-mobile-verification');
            Route::post('/{member}/communities/{community}/remove', [\App\Http\Controllers\Admin\MemberManagementController::class, 'removeCommunity'])->name('remove-community');
            Route::delete('/{member}', [\App\Http\Controllers\Admin\MemberManagementController::class, 'destroy'])->name('destroy');
        });

        // Posts & Moderation Routes
        Route::prefix('posts')->name('posts.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PostManagementController::class, 'index'])->name('index');
            Route::get('/export', [\App\Http\Controllers\Admin\PostManagementController::class, 'export'])->name('export');
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\PostManagementController::class, 'bulkAction'])->name('bulk-action');
            Route::get('/{post}', [\App\Http\Controllers\Admin\PostManagementController::class, 'show'])->name('show');
            Route::put('/{post}', [\App\Http\Controllers\Admin\PostManagementController::class, 'update'])->name('update');
            Route::delete('/{post}/media', [\App\Http\Controllers\Admin\PostManagementController::class, 'removeMedia'])->name('media.destroy');
            Route::post('/{post}/toggle-hide', [\App\Http\Controllers\Admin\PostManagementController::class, 'toggleHide'])->name('toggle-hide');
            Route::post('/reports/{report}/status', [\App\Http\Controllers\Admin\PostManagementController::class, 'updateReportStatus'])->name('reports.status');
            Route::delete('/{post}', [\App\Http\Controllers\Admin\PostManagementController::class, 'destroy'])->name('destroy');
        });

        // Stories Management Routes
        Route::prefix('stories')->name('stories.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\StoryManagementController::class, 'index'])->name('index');
            Route::get('/export', [\App\Http\Controllers\Admin\StoryManagementController::class, 'export'])->name('export');
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\StoryManagementController::class, 'bulkAction'])->name('bulk-action');
            Route::get('/{story}', [\App\Http\Controllers\Admin\StoryManagementController::class, 'show'])->name('show');
            Route::delete('/{story}', [\App\Http\Controllers\Admin\StoryManagementController::class, 'destroy'])->name('destroy');
        });

        // Communities Management Routes
        Route::prefix('communities')->name('communities.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\CommunityManagementController::class, 'index'])->name('index');
            Route::get('/export', [\App\Http\Controllers\Admin\CommunityManagementController::class, 'export'])->name('export');
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\CommunityManagementController::class, 'bulkAction'])->name('bulk-action');
            Route::get('/{community:id}', [\App\Http\Controllers\Admin\CommunityManagementController::class, 'show'])->name('show');
            Route::get('/{community:id}/edit', [\App\Http\Controllers\Admin\CommunityManagementController::class, 'edit'])->name('edit');
            Route::put('/{community:id}', [\App\Http\Controllers\Admin\CommunityManagementController::class, 'update'])->name('update');
            Route::post('/{community:id}/status', [\App\Http\Controllers\Admin\CommunityManagementController::class, 'updateStatus'])->name('status');
            Route::post('/{community:id}/join-requests/{membership}', [\App\Http\Controllers\Admin\CommunityManagementController::class, 'handleJoinRequest'])->name('join-requests');
            Route::post('/reports/{report}/resolve', [\App\Http\Controllers\Admin\CommunityManagementController::class, 'resolveReport'])->name('reports.resolve');
            Route::delete('/{community:id}', [\App\Http\Controllers\Admin\CommunityManagementController::class, 'destroy'])->name('destroy');
        });

        // Business Pages Management Routes
        Route::prefix('business-pages')->name('business-pages.')->group(function () {
            Route::get('/categories', [\App\Http\Controllers\Admin\BusinessPageCategoryManagementController::class, 'index'])->name('categories.index');
            Route::post('/categories', [\App\Http\Controllers\Admin\BusinessPageCategoryManagementController::class, 'store'])->name('categories.store');
            Route::put('/categories/{category}', [\App\Http\Controllers\Admin\BusinessPageCategoryManagementController::class, 'update'])->name('categories.update');
            Route::post('/categories/{category}/toggle-status', [\App\Http\Controllers\Admin\BusinessPageCategoryManagementController::class, 'toggleStatus'])->name('categories.toggle-status');
            Route::post('/categories/{category}/reassign', [\App\Http\Controllers\Admin\BusinessPageCategoryManagementController::class, 'reassign'])->name('categories.reassign');
            Route::delete('/categories/{category}', [\App\Http\Controllers\Admin\BusinessPageCategoryManagementController::class, 'destroy'])->name('categories.destroy');

            Route::get('/', [\App\Http\Controllers\Admin\BusinessPageManagementController::class, 'index'])->name('index');
            Route::get('/export', [\App\Http\Controllers\Admin\BusinessPageManagementController::class, 'export'])->name('export');
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\BusinessPageManagementController::class, 'bulkAction'])->name('bulk-action');
            Route::get('/{businessPage:id}', [\App\Http\Controllers\Admin\BusinessPageManagementController::class, 'show'])->name('show');
            Route::get('/{businessPage:id}/edit', [\App\Http\Controllers\Admin\BusinessPageManagementController::class, 'edit'])->name('edit');
            Route::put('/{businessPage:id}', [\App\Http\Controllers\Admin\BusinessPageManagementController::class, 'update'])->name('update');
            Route::post('/{businessPage:id}/status', [\App\Http\Controllers\Admin\BusinessPageManagementController::class, 'updateStatus'])->name('status');
            Route::post('/{businessPage:id}/verification', [\App\Http\Controllers\Admin\BusinessPageManagementController::class, 'handleVerification'])->name('verification');
            Route::delete('/{businessPage:id}', [\App\Http\Controllers\Admin\BusinessPageManagementController::class, 'destroy'])->name('destroy');
        });

        // Marketplace Management Routes
        Route::prefix('marketplace')->name('marketplace.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\MarketplaceManagementController::class, 'index'])->name('index');
            Route::get('/export', [\App\Http\Controllers\Admin\MarketplaceManagementController::class, 'export'])->name('export');
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\MarketplaceManagementController::class, 'bulkAction'])->name('bulk-action');
            Route::get('/{product}', [\App\Http\Controllers\Admin\MarketplaceManagementController::class, 'show'])->name('show');
            Route::post('/{product}/status', [\App\Http\Controllers\Admin\MarketplaceManagementController::class, 'updateStatus'])->name('status');
            Route::post('/{product}/toggle-featured', [\App\Http\Controllers\Admin\MarketplaceManagementController::class, 'toggleFeatured'])->name('toggle-featured');
            Route::delete('/{product}', [\App\Http\Controllers\Admin\MarketplaceManagementController::class, 'destroy'])->name('destroy');
        });

        // Events Management Routes
        Route::prefix('events')->name('events.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\EventManagementController::class, 'index'])->name('index');
            Route::get('/export', [\App\Http\Controllers\Admin\EventManagementController::class, 'export'])->name('export');
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\EventManagementController::class, 'bulkAction'])->name('bulk-action');
            Route::get('/{event:id}', [\App\Http\Controllers\Admin\EventManagementController::class, 'show'])->name('show');
            Route::get('/{event:id}/edit', [\App\Http\Controllers\Admin\EventManagementController::class, 'edit'])->name('edit');
            Route::put('/{event:id}', [\App\Http\Controllers\Admin\EventManagementController::class, 'update'])->name('update');
            Route::post('/{event:id}/status', [\App\Http\Controllers\Admin\EventManagementController::class, 'updateStatus'])->name('status');
            Route::delete('/{event:id}', [\App\Http\Controllers\Admin\EventManagementController::class, 'destroy'])->name('destroy');
        });

        // Event Campaigns Management (Admin Event Control Center)
        Route::prefix('event-campaigns')->name('event-campaigns.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\EventCampaignManagementController::class, 'index'])->name('index');
            Route::get('/{adCampaign}', [\App\Http\Controllers\Admin\EventCampaignManagementController::class, 'show'])->name('show');
            Route::get('/{adCampaign}/participants', [\App\Http\Controllers\Admin\EventCampaignManagementController::class, 'participants'])->name('participants');
            Route::post('/{adCampaign}/pause', [\App\Http\Controllers\Admin\EventCampaignManagementController::class, 'pause'])->name('pause');
            Route::post('/{adCampaign}/resume', [\App\Http\Controllers\Admin\EventCampaignManagementController::class, 'resume'])->name('resume');
            Route::post('/{adCampaign}/stop', [\App\Http\Controllers\Admin\EventCampaignManagementController::class, 'stop'])->name('stop');
        });

        // Reports & Moderation Center Routes
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\ReportManagementController::class, 'index'])->name('index');
            Route::get('/export', [\App\Http\Controllers\Admin\ReportManagementController::class, 'export'])->name('export');
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\ReportManagementController::class, 'bulkAction'])->name('bulk-action');
            Route::get('/{type}/{id}', [\App\Http\Controllers\Admin\ReportManagementController::class, 'show'])->name('show');
            Route::post('/{type}/{id}/status', [\App\Http\Controllers\Admin\ReportManagementController::class, 'updateStatus'])->name('status');
        });

        // Platform Settings & Configuration Center Routes
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\SettingManagementController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\SettingManagementController::class, 'update'])->name('update');
            Route::post('/clear-cache', [\App\Http\Controllers\Admin\SettingManagementController::class, 'clearCache'])->name('clear-cache');
            Route::post('/maintenance', [\App\Http\Controllers\Admin\SettingManagementController::class, 'toggleMaintenance'])->name('maintenance');
        });

        // Roles & Permissions (RBAC) Routes
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\RoleManagementController::class, 'index'])->name('index');
            Route::get('/matrix', [\App\Http\Controllers\Admin\RoleManagementController::class, 'matrix'])->name('matrix');
            Route::post('/matrix', [\App\Http\Controllers\Admin\RoleManagementController::class, 'updateMatrix'])->name('matrix.update');
            Route::get('/create', [\App\Http\Controllers\Admin\RoleManagementController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\RoleManagementController::class, 'store'])->name('store');
            Route::get('/{role}/edit', [\App\Http\Controllers\Admin\RoleManagementController::class, 'edit'])->name('edit');
            Route::put('/{role}', [\App\Http\Controllers\Admin\RoleManagementController::class, 'update'])->name('update');
            Route::delete('/{role}', [\App\Http\Controllers\Admin\RoleManagementController::class, 'destroy'])->name('destroy');
            Route::post('/assign-user', [\App\Http\Controllers\Admin\RoleManagementController::class, 'assignUserRole'])->name('assign-user');
        });

        // Notifications & Communication Center Routes
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/unread-count', [\App\Http\Controllers\Admin\NotificationManagementController::class, 'unreadCount'])->name('unread-count');
            Route::get('/dropdown', [\App\Http\Controllers\Admin\NotificationManagementController::class, 'dropdown'])->name('dropdown');
            Route::get('/', [\App\Http\Controllers\Admin\NotificationManagementController::class, 'index'])->name('index');
            Route::get('/broadcast', [\App\Http\Controllers\Admin\NotificationManagementController::class, 'broadcastForm'])->name('broadcast');
            Route::post('/broadcast', [\App\Http\Controllers\Admin\NotificationManagementController::class, 'sendBroadcast'])->name('broadcast.send');
            Route::get('/export', [\App\Http\Controllers\Admin\NotificationManagementController::class, 'export'])->name('export');
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\NotificationManagementController::class, 'bulkAction'])->name('bulk-action');
            Route::post('/mark-all-read', [\App\Http\Controllers\Admin\NotificationManagementController::class, 'markAllRead'])->name('mark-all-read');
            Route::get('/{id}', [\App\Http\Controllers\Admin\NotificationManagementController::class, 'show'])->name('show');
            Route::post('/{id}/read', [\App\Http\Controllers\Admin\NotificationManagementController::class, 'markRead'])->name('mark-read');
        });

        // Enterprise Analytics & Business Intelligence Routes
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('index');
            Route::get('/export', [\App\Http\Controllers\Admin\AnalyticsController::class, 'export'])->name('export');
        });

        // System Tools, Maintenance & Monitoring Center Routes
        Route::prefix('system')->name('system.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\SystemToolsController::class, 'index'])->name('index');
            Route::get('/logs', [\App\Http\Controllers\Admin\SystemToolsController::class, 'logs'])->name('logs');
            Route::get('/logs/download', [\App\Http\Controllers\Admin\SystemToolsController::class, 'downloadLog'])->name('logs.download');
            Route::post('/logs/clear', [\App\Http\Controllers\Admin\SystemToolsController::class, 'clearLog'])->name('logs.clear');
            Route::post('/failed-jobs/{id}/retry', [\App\Http\Controllers\Admin\SystemToolsController::class, 'retryFailedJob'])->name('failed-jobs.retry');
            Route::delete('/failed-jobs/{id}', [\App\Http\Controllers\Admin\SystemToolsController::class, 'deleteFailedJob'])->name('failed-jobs.delete');
            Route::post('/artisan', [\App\Http\Controllers\Admin\SystemToolsController::class, 'runArtisan'])->name('artisan');
        });
    });
});

// Storage Asset Fallback Route (guarantees public storage images serve properly in all environments)
Route::get('/storage/{path}', function (string $path) {
    $fullPath = storage_path('app/public/' . $path);
    if (!file_exists($fullPath) || is_dir($fullPath)) {
        return response('File not found', 404);
    }
    return response()->file($fullPath, [
        'Content-Type' => mime_content_type($fullPath) ?: 'application/octet-stream',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('path', '.*');

Route::fallback(function () {
    echo "Sorry Not Found";
});
