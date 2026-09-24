<?php

use App\Http\Controllers\Mobile\MobileAdminController;
use App\Http\Controllers\Mobile\MobileAuthController;
use App\Http\Controllers\Mobile\MobileBusinessAndCampaignController;
use App\Http\Controllers\Mobile\MobileFeedController;
use App\Http\Controllers\Mobile\MobileSocialController;
use App\Http\Controllers\Mobile\MobileUtilityController;
use App\Http\Controllers\Mobile\MobileWalletController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile REST API Routes (Flutter App)
|--------------------------------------------------------------------------
| Base Prefix: /api/v1/mobile
| Isolated Bearer Token auth for Flutter Mobile App.
*/

// Public Authentication & Onboarding
Route::prefix('auth')->group(function () {
    Route::post('/member/login', [MobileAuthController::class, 'memberLogin']);
    Route::post('/admin/login', [MobileAuthController::class, 'adminLogin']);
    Route::post('/member/register', [MobileAuthController::class, 'memberRegister']);
    Route::post('/member/register/verify', [MobileAuthController::class, 'verifyMemberRegistration']);
});

// Member Protected Mobile Endpoints
Route::middleware('mobile.auth:member')->group(function () {
    // Auth & Session
    Route::get('/auth/me', [MobileAuthController::class, 'me']);
    Route::post('/auth/logout', [MobileAuthController::class, 'logout']);

    // Social Feed, Posts, Stories & Saved Posts
    Route::get('/feed', [MobileFeedController::class, 'feed']);
    Route::post('/posts', [MobileFeedController::class, 'storePost']);
    Route::post('/posts/{id}/react', [MobileFeedController::class, 'reactPost']);
    Route::get('/posts/{id}/reactors', [MobileFeedController::class, 'getReactors']);
    Route::get('/posts/{id}/comments', [MobileFeedController::class, 'getComments']);
    Route::post('/posts/{id}/comments', [MobileFeedController::class, 'addComment']);
    Route::post('/posts/{id}/share', [MobileFeedController::class, 'sharePost']);
    Route::get('/saved-posts', [MobileFeedController::class, 'savedPosts']);
    Route::post('/posts/{id}/save', [MobileFeedController::class, 'toggleSavePost']);
    Route::get('/stories', [MobileFeedController::class, 'stories']);
    Route::post('/stories', [MobileFeedController::class, 'createStory']);
    Route::delete('/stories/{id}', [MobileFeedController::class, 'deleteStory']);
    Route::get('/watch', [MobileFeedController::class, 'watchVideos']);

    // Profile & Social Connections
    Route::get('/profile/me', [MobileSocialController::class, 'myProfile']);
    Route::get('/profile/{id}', [MobileSocialController::class, 'memberProfile']);
    Route::post('/profile/photo', [MobileSocialController::class, 'updateProfilePhoto']);
    Route::delete('/profile/photo', [MobileSocialController::class, 'removeProfilePhoto']);
    Route::post('/profile/cover', [MobileSocialController::class, 'updateCoverPhoto']);
    Route::delete('/profile/cover', [MobileSocialController::class, 'removeCoverPhoto']);
    Route::get('/friends', [MobileSocialController::class, 'friends']);
    Route::get('/friends/requests', [MobileSocialController::class, 'friendRequests']);
    Route::post('/friends/requests/{id}/respond', [MobileSocialController::class, 'respondFriendRequest']);
    Route::post('/friends/requests/{id}/cancel', [MobileSocialController::class, 'cancelFriendRequest']);
    Route::get('/friends/suggestions', [MobileSocialController::class, 'suggestedFriends']);
    Route::get('/people/suggestions', [MobileSocialController::class, 'suggestedFriends']);
    Route::post('/friends/request/{id}', [MobileSocialController::class, 'sendFriendRequest']);
    Route::delete('/friends/{id}', [MobileSocialController::class, 'removeFriend']);
    Route::post('/friends/follow/{id}', [MobileSocialController::class, 'toggleFollow']);

    // Notifications Center
    Route::get('/notifications', [MobileSocialController::class, 'notifications']);
    Route::post('/notifications/{id}/read', [MobileSocialController::class, 'markNotificationRead']);
    Route::post('/notifications/read-all', [MobileSocialController::class, 'markAllNotificationsRead']);

    // 1-on-1 Direct Messaging
    Route::get('/messages/conversations', [MobileSocialController::class, 'conversations']);
    Route::get('/messages/{partnerId}', [MobileSocialController::class, 'chatMessages']);
    Route::post('/messages/{partnerId}', [MobileSocialController::class, 'sendMessage']);

    // Communities (Groups)
    Route::get('/communities', [MobileBusinessAndCampaignController::class, 'communities']);
    Route::get('/communities/{slug}', [MobileBusinessAndCampaignController::class, 'communityDetail']);
    Route::post('/communities', [MobileBusinessAndCampaignController::class, 'storeCommunity']);
    Route::post('/communities/{slug}/join', [MobileBusinessAndCampaignController::class, 'toggleCommunityJoin']);

    // Business Pages & Ad Campaigns
    Route::get('/business-pages', [MobileBusinessAndCampaignController::class, 'businessPages']);
    Route::post('/business-pages', [MobileBusinessAndCampaignController::class, 'storeBusinessPage']);
    Route::get('/business-pages/{slug}/campaigns', [MobileBusinessAndCampaignController::class, 'pageCampaigns']);
    Route::post('/business-pages/{slug}/campaigns', [MobileBusinessAndCampaignController::class, 'storeAdCampaign']);
    Route::get('/sponsored-feed', [MobileBusinessAndCampaignController::class, 'sponsoredFeed']);
    Route::post('/sponsored/{id}/claim', [MobileBusinessAndCampaignController::class, 'claimReward']);

    // Events Hub
    Route::get('/events', [MobileBusinessAndCampaignController::class, 'events']);
    Route::post('/events', [MobileBusinessAndCampaignController::class, 'storeEvent']);
    Route::post('/events/{id}/respond', [MobileBusinessAndCampaignController::class, 'respondEvent']);

    // Feedback & Suggestions
    Route::get('/feedback', [MobileUtilityController::class, 'feedbackList']);
    Route::post('/feedback', [MobileUtilityController::class, 'storeFeedback']);

    // Global Search
    Route::get('/search', [MobileUtilityController::class, 'search']);

    // Account & Security Settings
    Route::get('/account/settings', [MobileUtilityController::class, 'accountSettings']);
    Route::post('/account/profile', [MobileUtilityController::class, 'updateProfile']);
    Route::post('/account/password', [MobileUtilityController::class, 'changePassword']);

    // Web3 Reward Wallet & BSC Deposits
    Route::get('/wallet', [MobileWalletController::class, 'summary']);
    Route::post('/wallet/link-address/send-otp', [MobileWalletController::class, 'sendWalletOtp']);
    Route::post('/wallet/link-address/verify', [MobileWalletController::class, 'verifyWalletOtp']);
    Route::get('/deposits/config', [MobileWalletController::class, 'depositConfig']);
    Route::post('/deposits/submit', [MobileWalletController::class, 'submitDeposit']);
    Route::get('/deposits/history', [MobileWalletController::class, 'depositHistory']);
});

// Admin Protected Mobile Endpoints
Route::prefix('admin')->middleware('mobile.auth:admin')->group(function () {
    Route::get('/dashboard', [MobileAdminController::class, 'dashboard']);
    Route::get('/members', [MobileAdminController::class, 'members']);
    Route::post('/members/{id}/toggle-block', [MobileAdminController::class, 'toggleMemberBlock']);
    Route::get('/campaigns', [MobileAdminController::class, 'campaigns']);
    Route::post('/campaigns/{id}/review', [MobileAdminController::class, 'reviewCampaign']);
    Route::get('/deposits', [MobileAdminController::class, 'deposits']);
    Route::post('/deposits/{id}/approve', [MobileAdminController::class, 'approveDeposit']);
    Route::post('/deposits/{id}/reverify', [MobileAdminController::class, 'reverifyDeposit']);
    Route::get('/reports', [MobileAdminController::class, 'reports']);
});
