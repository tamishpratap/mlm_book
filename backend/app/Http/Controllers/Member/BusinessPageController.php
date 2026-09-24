<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\BusinessFollower;
use App\Models\BusinessPage;
use App\Models\BusinessPageCategory;
use App\Models\BusinessReview;
use App\Models\Member;
use App\Models\Post;
use App\Http\Middleware\EnsureMemberMobileVerified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BusinessPageController extends Controller
{
    public function index(Request $request)
    {
        $member = auth('member')->user();
        $tab = $request->query('tab', 'all');

        // My Business Pages (Owned)
        $myPages = BusinessPage::query()
            ->with('owner')
            ->where('member_id', $member->id)
            ->latest()
            ->get();

        // Main Query
        $query = BusinessPage::query()
            ->with('owner');

        if ($tab === 'my') {
            $query->where('member_id', $member->id);
        } else {
            // All pages tab - show public pages OR owned private/draft pages
            $query->where(function ($q) use ($member) {
                $q->where('visibility', 'public')
                  ->orWhere('member_id', $member->id);
            });
        }

        if ($request->filled('search')) {
            $search = '%'.$request->query('search').'%';
            $query->where(function ($q) use ($search) {
                $q->where('page_name', 'like', $search)
                    ->orWhere('page_username', 'like', $search)
                    ->orWhere('description', 'like', $search)
                    ->orWhere('category', 'like', $search)
                    ->orWhere('city', 'like', $search)
                    ->orWhere('country', 'like', $search);
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        $pages = $query->latest('created_at')->paginate(12)->withQueryString();
        $categories = BusinessPage::categories();
        $visibilities = BusinessPage::VISIBILITIES;

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'pages' => $pages,
                'my_pages' => $myPages,
                'categories' => $categories,
                'visibilities' => $visibilities,
                'tab' => $tab,
            ]);
        }

        return view('member.business-pages.index', compact(
            'pages',
            'myPages',
            'categories',
            'visibilities',
            'tab'
        ));
    }

    public function create(Request $request)
    {
        $categories = BusinessPage::categories();
        $visibilities = BusinessPage::VISIBILITIES;
        $countries = BusinessPage::COUNTRIES;
        $dialingCodes = BusinessPage::DIALING_CODES;

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'categories' => $categories,
                'visibilities' => $visibilities,
                'countries' => $countries,
                'dialing_codes' => $dialingCodes,
            ]);
        }

        return view('member.business-pages.create', compact('categories', 'visibilities', 'countries', 'dialingCodes'));
    }

    public function store(Request $request)
    {
        $member = auth('member')->user();

        if (! $member || ! $member->isMobileVerified()) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => EnsureMemberMobileVerified::UNVERIFIED_MESSAGE,
                    'requires_mobile_verification' => true,
                ], 403);
            }

            return back()->withInput()->with('error', EnsureMemberMobileVerified::UNVERIFIED_MESSAGE);
        }

        // Sanitize string inputs
        $pageName = trim((string) $request->input('page_name'));
        $description = trim((string) $request->input('description'));
        $website = trim((string) $request->input('website'));
        $email = trim((string) $request->input('email'));
        $address = trim((string) $request->input('address'));
        $city = trim((string) $request->input('city'));
        $state = trim((string) $request->input('state'));
        $country = trim((string) $request->input('country'));
        $code = trim((string) $request->input('phone_country_code', '+91'));
        $digits = preg_replace('/[^0-9]/', '', (string) $request->input('phone_number', ''));

        $phoneCombined = filled($digits) ? ($code . $digits) : (trim((string) $request->input('phone')));

        if (filled($website)) {
            if (str_starts_with(strtolower($website), 'www.')) {
                $website = 'https://' . $website;
            } elseif (! str_starts_with(strtolower($website), 'http://') && ! str_starts_with(strtolower($website), 'https://')) {
                $website = 'https://' . $website;
            }
        }

        $categoryRaw = $request->input('category') ?? $request->input('category_id');
        $resolvedCategory = BusinessPageCategory::resolveCanonicalName($categoryRaw);

        $request->merge([
            'page_name' => $pageName,
            'category' => $resolvedCategory ?? (is_string($categoryRaw) ? trim($categoryRaw) : $categoryRaw),
            'description' => $description,
            'website' => filled($website) ? $website : null,
            'email' => $email,
            'phone' => $phoneCombined,
            'address' => filled($address) ? $address : null,
            'city' => filled($city) ? $city : null,
            'state' => filled($state) ? $state : null,
            'country' => $country,
        ]);

        $validated = $request->validate([
            'page_name' => ['required', 'string', 'min:3', 'max:255'],
            'page_username' => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z0-9._-]+$/', 'unique:business_pages,page_username'],
            'category' => ['required', 'string', Rule::in(BusinessPage::categories())],
            'description' => ['required', 'string', 'min:20', 'max:2000'],
            'website' => ['nullable', 'url', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\+[1-9]\d{6,14}$/'],
            'country' => ['required', 'string', Rule::in(BusinessPage::COUNTRIES)],
            'state' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'visibility' => ['required', 'string', 'in:public,private,draft'],
            'cover_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'page_name.required' => 'Business name is required.',
            'page_name.min' => 'Business name must be at least 3 characters.',
            'page_name.max' => 'Business name cannot exceed 255 characters.',
            'category.required' => 'Please select a business category.',
            'category.in' => 'The selected category is invalid.',
            'description.required' => 'Description is required.',
            'description.min' => 'Description must be at least 20 characters.',
            'email.required' => 'Business email is required.',
            'email.email' => 'Please enter a valid email address.',
            'phone.required' => 'Phone number is required.',
            'phone.regex' => 'Phone number must contain only numeric digits after country code.',
            'website.regex' => 'Please enter a valid website URL (e.g. https://example.com or www.example.com).',
            'country.required' => 'Please select a country.',
            'country.in' => 'Please select a valid country from the list.',
            'logo.mimes' => 'Logo must be a JPG, JPEG, PNG, or WEBP image.',
            'logo.max' => 'Logo file size cannot exceed 2MB.',
            'cover_photo.mimes' => 'Cover banner must be a JPG, JPEG, PNG, or WEBP image.',
            'cover_photo.max' => 'Cover banner file size cannot exceed 5MB.',
        ]);

        $member = auth('member')->user();

        // Generate unique slug
        $baseSlug = Str::slug($validated['page_name']);
        $slug = $baseSlug ?: 'page';
        if (BusinessPage::where('slug', $slug)->exists()) {
            $slug = $slug.'-'.Str::random(5);
        }

        // Auto-generate username if empty
        $pageUsername = $validated['page_username'] ?? null;
        if (empty($pageUsername)) {
            $baseUsername = Str::slug($validated['page_name'], '_');
            $baseUsername = preg_replace('/[^a-zA-Z0-9_]/', '', $baseUsername) ?: 'page';
            $pageUsername = $baseUsername;
            if (BusinessPage::where('page_username', $pageUsername)->exists()) {
                $pageUsername = $pageUsername.'_'.Str::random(4);
            }
        }

        // Unique page_id
        $pageId = 'biz_'.Str::random(10);

        // Store cover photo and logo if provided
        $coverPath = null;
        if ($request->hasFile('cover_photo')) {
            $coverPath = $this->storeFile($request->file('cover_photo'), 'uploads/business_pages/covers');
        }

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $this->storeFile($request->file('logo'), 'uploads/business_pages/logos');
        }

        $page = BusinessPage::create([
            'page_id' => $pageId,
            'member_id' => $member->id,
            'page_name' => $validated['page_name'],
            'page_username' => strtolower($pageUsername),
            'slug' => $slug,
            'category' => $validated['category'],
            'description' => $validated['description'] ?? null,
            'website' => $validated['website'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'country' => $validated['country'] ?? null,
            'state' => $validated['state'] ?? null,
            'city' => $validated['city'] ?? null,
            'address' => $validated['address'] ?? null,
            'visibility' => $validated['visibility'],
            'status' => 'active',
            'is_verified' => false,
            'cover_photo' => $coverPath,
            'logo' => $logoPath,
        ]);

        \App\Services\AdminNotificationService::notify(
            title: 'New Business Page Registered',
            message: sprintf('"%s" was registered by %s.', $page->page_name, $member->name),
            icon: 'building',
            sourceType: 'business_page',
            sourceId: (string) $page->id,
            actionUrl: '/admin/business-pages',
            metadata: ['page_id' => $page->id, 'page_name' => $page->page_name]
        );

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Business Page created successfully!',
                'business_page' => $page->load('owner'),
            ], 201);
        }

        return redirect()->route('member.business-pages.show', $page)
            ->with('success', 'Business Page created successfully!');
    }

    public function show(Request $request, BusinessPage $businessPage)
    {
        $currentMember = auth('member')->user();
        $businessPage->load('owner');

        $isOwner = $businessPage->isOwner($currentMember->id);
        $isAdmin = $businessPage->isTeamAdmin($currentMember->id);
        $isTeamMember = $businessPage->isTeamMember($currentMember->id);
        $canManage = $isTeamMember;
        $teamRole = $businessPage->getTeamRole($currentMember->id);

        if (! $businessPage->isPublic() && ! $canManage) {
            abort(403, 'This Business Page is private or draft.');
        }

        // Record Analytics View
        $businessPage->recordView($currentMember->id);

        $activeTab = $request->query('tab', 'home');
        $validTabs = ['home', 'about', 'photos', 'videos', 'events', 'reviews', 'followers', 'settings'];
        if (! in_array($activeTab, $validTabs)) {
            $activeTab = 'home';
        }

        if ($activeTab === 'settings' && ! $isOwner) {
            $activeTab = 'home';
        }

        // Timeline Feed Posts
        $pinnedPost = null;
        $timelinePosts = collect();

        if ($activeTab === 'home') {
            $pinnedPost = Post::query()
                ->with(['member', 'businessPage', 'likes', 'reactions', 'comments.member'])
                ->where('business_page_id', $businessPage->id)
                ->where('is_pinned', true)
                ->latest()
                ->first();

            $query = Post::query()
                ->with(['member', 'businessPage', 'likes', 'reactions', 'comments.member'])
                ->where('business_page_id', $businessPage->id);

            if ($pinnedPost) {
                $query->where('id', '!=', $pinnedPost->id);
            }

            $timelinePosts = $query->latest()->paginate(10)->withQueryString();
        }

        // Photos Gallery Query
        $photos = collect();
        if ($activeTab === 'photos') {
            $photos = Post::query()
                ->where('business_page_id', $businessPage->id)
                ->where('media_type', 'image')
                ->whereNotNull('media_path')
                ->with(['member', 'businessPage'])
                ->latest()
                ->paginate(18)
                ->withQueryString();
        }

        // Videos Gallery Query
        $videos = collect();
        if ($activeTab === 'videos') {
            $videos = Post::query()
                ->where('business_page_id', $businessPage->id)
                ->where('media_type', 'video')
                ->whereNotNull('media_path')
                ->with(['member', 'businessPage'])
                ->latest()
                ->paginate(12)
                ->withQueryString();
        }

        // Followers Tab Query & Filters
        $followers = collect();
        $pendingFollowRequests = collect();
        if ($activeTab === 'followers') {
            $q = trim($request->query('q', ''));
            $sort = $request->query('sort', 'newest');

            $followersQuery = BusinessFollower::query()
                ->where('business_page_id', $businessPage->id)
                ->where('status', 'accepted')
                ->with('member');

            if (filled($q)) {
                $followersQuery->whereHas('member', function ($mq) use ($q) {
                    $mq->where('name', 'like', "%{$q}%")
                        ->orWhere('user_id', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            }

            if ($sort === 'oldest') {
                $followersQuery->oldest('followed_at');
            } elseif ($sort === 'alphabetical') {
                $followersQuery->join('members', 'business_followers.member_id', '=', 'members.id')
                    ->orderBy('members.name', 'asc')
                    ->select('business_followers.*');
            } else {
                $followersQuery->latest('followed_at');
            }

            $followers = $followersQuery->paginate(18)->withQueryString();

            if ($businessPage->isTeamAdmin($currentMember->id)) {
                $pendingFollowRequests = BusinessFollower::query()
                    ->where('business_page_id', $businessPage->id)
                    ->where('status', 'pending')
                    ->with('member')
                    ->latest()
                    ->get();
            }
        }

        // Reviews Tab Query & Filters
        $reviews = collect();
        $ratingDistribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        $recommendationBadge = 'No Reviews Yet';
        $userReview = null;

        if ($activeTab === 'reviews') {
            $q = trim($request->query('q', ''));
            $sort = $request->query('sort', 'newest');

            $reviewsQuery = BusinessReview::query()
                ->where('business_page_id', $businessPage->id)
                ->with(['member', 'officialReply.member']);

            // Page Admins can see hidden reviews; regular users see only visible reviews
            if (! $businessPage->isTeamAdmin($currentMember->id)) {
                $reviewsQuery->where('is_hidden', false);
            }

            if (filled($q)) {
                $reviewsQuery->where(function ($rq) use ($q) {
                    $rq->where('title', 'like', "%{$q}%")
                        ->orWhere('body', 'like', "%{$q}%")
                        ->orWhereHas('member', function ($mq) use ($q) {
                            $mq->where('name', 'like', "%{$q}%");
                        });
                });
            }

            if ($sort === 'oldest') {
                $reviewsQuery->oldest();
            } elseif ($sort === 'highest') {
                $reviewsQuery->orderBy('rating', 'desc')->latest();
            } elseif ($sort === 'lowest') {
                $reviewsQuery->orderBy('rating', 'asc')->latest();
            } elseif ($sort === 'photos') {
                $reviewsQuery->whereNotNull('photos')->latest();
            } elseif ($sort === 'recommended') {
                $reviewsQuery->where('recommendation', 'recommend')->latest();
            } else {
                $reviewsQuery->latest();
            }

            $reviews = $reviewsQuery->paginate(10)->withQueryString();
            $ratingDistribution = $businessPage->ratingDistribution();
            $recommendationBadge = $businessPage->recommendationBadge();
            $userReview = $businessPage->userReview($currentMember->id);
        }

        // Friends list for follow invitation modal
        $friends = collect();
        if ($businessPage->isTeamAdmin($currentMember->id)) {
            $friendIds = $currentMember->acceptedFriendIds();
            $friends = Member::whereIn('id', $friendIds)
                ->where('id', '!=', $businessPage->member_id)
                ->whereNotIn('id', $businessPage->acceptedFollowers()->pluck('member_id')->toArray())
                ->get();
        }

        // Audience Counters Summary
        $audienceCounters = [
            'followers' => $businessPage->followersCount(),
            'posts' => Post::where('business_page_id', $businessPage->id)->count(),
            'photos' => Post::where('business_page_id', $businessPage->id)->where('media_type', 'image')->count(),
            'videos' => Post::where('business_page_id', $businessPage->id)->where('media_type', 'video')->count(),
            'team' => $businessPage->activeTeamMembers()->count() + 1,
        ];

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'business_page' => $businessPage,
                'is_owner' => $isOwner,
                'is_admin' => $isAdmin,
                'is_team_member' => $isTeamMember,
                'can_manage' => $canManage,
                'team_role' => $teamRole,
                'follow_status' => $businessPage->getFollowStatus($currentMember->id),
                'is_following' => $businessPage->isFollowedBy($currentMember->id),
                'followers_count' => $businessPage->followersCount(),
                'active_tab' => $activeTab,
                'pinned_post' => $pinnedPost,
                'timeline_posts' => $timelinePosts,
                'photos' => $photos,
                'videos' => $videos,
                'followers' => $followers,
                'pending_follow_requests' => $pendingFollowRequests,
                'friends' => $friends,
                'audience_counters' => $audienceCounters,
                'reviews' => $reviews,
                'rating_distribution' => $ratingDistribution,
                'recommendation_badge' => $recommendationBadge,
                'average_rating' => $businessPage->averageRating(),
                'reviews_count' => $businessPage->reviewsCount(),
                'user_review' => $userReview,
            ]);
        }

        return view('member.business-pages.show', compact(
            'businessPage',
            'isOwner',
            'isAdmin',
            'isTeamMember',
            'canManage',
            'teamRole',
            'activeTab',
            'pinnedPost',
            'timelinePosts',
            'photos',
            'videos',
            'followers',
            'pendingFollowRequests',
            'friends',
            'audienceCounters',
            'reviews',
            'ratingDistribution',
            'recommendationBadge',
            'userReview'
        ));
    }

    public function storePost(Request $request, BusinessPage $businessPage)
    {
        $member = auth('member')->user();

        if (! $member || ! $member->isMobileVerified()) {
            return response()->json([
                'success' => false,
                'message' => EnsureMemberMobileVerified::UNVERIFIED_MESSAGE,
                'requires_mobile_verification' => true,
            ], 403);
        }

        if (! $businessPage->isOwner($member->id) && ! $businessPage->hasTeamPermission($member->id, 'publish_posts')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You do not have permission to publish posts for this Business Page.',
            ], 403);
        }

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:5000', 'required_without:media'],
            'media' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,mp4,webm,mov', 'max:61440', 'required_without:body'],
            'is_announcement' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ], [
            'body.required_without' => 'Please enter some text or attach an image/video.',
            'media.required_without' => 'Please enter some text or attach an image/video.',
            'media.mimes' => 'Only JPG, PNG, WEBP images and MP4, WEBM, MOV videos are supported.',
            'media.max' => 'Media file size cannot exceed 60MB.',
        ]);

        $media = $request->file('media');
        $body = filled($validated['body'] ?? null) ? trim($validated['body']) : null;
        $mediaType = null;
        $mediaPath = null;

        if ($media) {
            $mime = $media->getMimeType();
            if (str_starts_with($mime, 'image/')) {
                if ($media->getSize() > 5 * 1024 * 1024) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Images may not be larger than 5 MB.',
                    ], 422);
                }
                $mediaType = 'image';
                $directory = 'uploads/posts/images';
            } elseif (str_starts_with($mime, 'video/')) {
                if ($media->getSize() > 60 * 1024 * 1024) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Videos may not be larger than 60 MB.',
                    ], 422);
                }
                $mediaType = 'video';
                $directory = 'uploads/posts/videos';
            } else {
                return response()->json(['success' => false, 'message' => 'Invalid media file type.'], 422);
            }

            $extension = strtolower($media->getClientOriginalExtension() ?: ($mediaType === 'image' ? 'jpg' : 'mp4'));
            $filename = sprintf('biz_post_%d_%d_%s.%s', $member->id, time(), Str::random(8), $extension);

            if ($mediaType === 'image') {
                $mediaPath = app(\App\Http\Controllers\ImageCompressionController::class)->compressAndStore(
                    $media,
                    $directory,
                    'post',
                    $filename,
                    'public_uploads',
                    'BUSINESS_IMAGE'
                );
            } else {
                $mediaPath = app(\App\Http\Controllers\VideoCompressionController::class)->stageAndStore(
                    $media,
                    $directory,
                    $filename,
                    \App\Services\ContentModeration\ContentModerationService::CONTEXT_BUSINESS_VIDEO
                );
            }
        }

        $post = Post::create([
            'member_id' => $member->id,
            'business_page_id' => $businessPage->id,
            'body' => $body,
            'media_type' => $mediaType,
            'media_path' => $mediaPath,
            'is_announcement' => ! empty($validated['is_announcement']),
            'is_featured' => ! empty($validated['is_featured']),
            'is_pinned' => false,
        ]);

        if ($mediaType === 'video' && $mediaPath) {
            app(\App\Http\Controllers\VideoCompressionController::class)->dispatchCompression(
                $post,
                null,
                $mediaPath,
                $directory,
                $filename
            );
        }

        $post->load(['member', 'businessPage']);

        $html = view('member.posts.partials.card', compact('post'))->render();

        return response()->json([
            'success' => true,
            'message' => 'Post published to ' . $businessPage->page_name . ' timeline!',
            'post' => $post,
            'post_id' => $post->id,
            'html' => $html,
        ]);
    }

    public function togglePinPost(Request $request, BusinessPage $businessPage, Post $post)
    {
        $member = auth('member')->user();

        if (! $businessPage->isTeamAdmin($member->id)) {
            return response()->json(['success' => false, 'message' => 'Only page owners and admins can pin posts.'], 403);
        }

        if ((int) $post->business_page_id !== (int) $businessPage->id) {
            return response()->json(['success' => false, 'message' => 'Post does not belong to this business page.'], 422);
        }

        $newPinnedState = ! $post->is_pinned;

        if ($newPinnedState) {
            // Unpin any existing pinned post for this page
            Post::where('business_page_id', $businessPage->id)
                ->where('is_pinned', true)
                ->update(['is_pinned' => false]);
        }

        $post->update(['is_pinned' => $newPinnedState]);

        return response()->json([
            'success' => true,
            'is_pinned' => $newPinnedState,
            'message' => $newPinnedState ? 'Post pinned to top of timeline!' : 'Post unpinned.',
        ]);
    }

    public function toggleFeaturePost(Request $request, BusinessPage $businessPage, Post $post)
    {
        $member = auth('member')->user();

        if (! $businessPage->isTeamAdmin($member->id)) {
            return response()->json(['success' => false, 'message' => 'Only page owners and admins can feature posts.'], 403);
        }

        if ((int) $post->business_page_id !== (int) $businessPage->id) {
            return response()->json(['success' => false, 'message' => 'Post does not belong to this business page.'], 422);
        }

        $newFeaturedState = ! $post->is_featured;
        $post->update(['is_featured' => $newFeaturedState]);

        return response()->json([
            'success' => true,
            'is_featured' => $newFeaturedState,
            'message' => $newFeaturedState ? 'Post marked as Featured!' : 'Featured tag removed.',
        ]);
    }

    public function destroyPost(Request $request, BusinessPage $businessPage, Post $post)
    {
        $member = auth('member')->user();

        if (! $businessPage->isTeamAdmin($member->id)) {
            return response()->json(['success' => false, 'message' => 'Only page owners and admins can delete posts.'], 403);
        }

        if ((int) $post->business_page_id !== (int) $businessPage->id) {
            return response()->json(['success' => false, 'message' => 'Post does not belong to this business page.'], 422);
        }

        if ($post->media_path && File::exists(public_path($post->media_path))) {
            File::delete(public_path($post->media_path));
        }

        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Business post deleted successfully.',
        ]);
    }

    public function edit(Request $request, BusinessPage $businessPage)
    {
        $member = auth('member')->user();
        if (! $businessPage->isOwner($member->id)) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }
            abort(403, 'Unauthorized action.');
        }

        $categories = BusinessPage::categories();
        if (filled($businessPage->category) && ! in_array($businessPage->category, $categories)) {
            $categories[] = $businessPage->category;
        }
        $visibilities = BusinessPage::VISIBILITIES;
        $countries = BusinessPage::COUNTRIES;
        $dialingCodes = BusinessPage::DIALING_CODES;

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'business_page' => $businessPage,
                'categories' => $categories,
                'visibilities' => $visibilities,
                'countries' => $countries,
                'dialing_codes' => $dialingCodes,
            ]);
        }

        return view('member.business-pages.edit', compact('businessPage', 'categories', 'visibilities', 'countries', 'dialingCodes'));
    }

    public function update(Request $request, BusinessPage $businessPage)
    {
        $member = auth('member')->user();
        if (! $businessPage->isOwner($member->id)) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }
            abort(403, 'Unauthorized action.');
        }

        // Sanitize string inputs
        $pageName = trim((string) $request->input('page_name'));
        $description = trim((string) $request->input('description'));
        $website = trim((string) $request->input('website'));
        $email = trim((string) $request->input('email'));
        $address = trim((string) $request->input('address'));
        $city = trim((string) $request->input('city'));
        $state = trim((string) $request->input('state'));
        $country = trim((string) $request->input('country'));
        $code = trim((string) $request->input('phone_country_code', '+91'));
        $digits = preg_replace('/[^0-9]/', '', (string) $request->input('phone_number', ''));

        $phoneCombined = filled($digits) ? ($code . $digits) : (trim((string) $request->input('phone')));

        if (filled($website)) {
            if (str_starts_with(strtolower($website), 'www.')) {
                $website = 'https://' . $website;
            } elseif (! str_starts_with(strtolower($website), 'http://') && ! str_starts_with(strtolower($website), 'https://')) {
                $website = 'https://' . $website;
            }
        }

        $categoryRaw = $request->input('category') ?? $request->input('category_id');
        $resolvedCategory = BusinessPageCategory::resolveCanonicalName($categoryRaw);

        $request->merge([
            'page_name' => $pageName,
            'category' => $resolvedCategory ?? (is_string($categoryRaw) ? trim($categoryRaw) : $categoryRaw),
            'description' => $description,
            'website' => filled($website) ? $website : null,
            'email' => $email,
            'phone' => $phoneCombined,
            'address' => filled($address) ? $address : null,
            'city' => filled($city) ? $city : null,
            'state' => filled($state) ? $state : null,
            'country' => $country,
        ]);

        $allowedCategories = array_values(array_unique(array_merge(BusinessPage::categories(), filled($businessPage->category) ? [$businessPage->category] : [])));

        $validated = $request->validate([
            'page_name' => ['required', 'string', 'min:3', 'max:255'],
            'page_username' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9._-]+$/',
                Rule::unique('business_pages', 'page_username')->ignore($businessPage->id),
            ],
            'category' => ['required', 'string', Rule::in($allowedCategories)],
            'description' => ['required', 'string', 'min:20', 'max:2000'],
            'website' => ['nullable', 'url', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\+[1-9]\d{6,14}$/'],
            'country' => ['required', 'string', Rule::in(BusinessPage::COUNTRIES)],
            'state' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'visibility' => ['required', 'string', 'in:public,private,draft'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'facebook' => ['nullable', 'url', 'max:255'],
            'twitter' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'url', 'max:255'],
            'linkedin' => ['nullable', 'url', 'max:255'],
            'youtube' => ['nullable', 'url', 'max:255'],
            'cover_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'page_name.required' => 'Business name is required.',
            'page_name.min' => 'Business name must be at least 3 characters.',
            'page_name.max' => 'Business name cannot exceed 255 characters.',
            'category.required' => 'Please select a business category.',
            'category.in' => 'The selected category is invalid.',
            'description.required' => 'Description is required.',
            'description.min' => 'Description must be at least 20 characters.',
            'email.required' => 'Business email is required.',
            'email.email' => 'Please enter a valid email address.',
            'phone.required' => 'Phone number is required.',
            'phone.regex' => 'Phone number must contain only numeric digits after country code.',
            'website.regex' => 'Please enter a valid website URL (e.g. https://example.com or www.example.com).',
            'country.required' => 'Please select a country.',
            'country.in' => 'Please select a valid country from the list.',
            'logo.mimes' => 'Logo must be a JPG, JPEG, PNG, or WEBP image.',
            'logo.max' => 'Logo file size cannot exceed 2MB.',
            'cover_photo.mimes' => 'Cover banner must be a JPG, JPEG, PNG, or WEBP image.',
            'cover_photo.max' => 'Cover banner file size cannot exceed 5MB.',
        ]);

        $socialLinks = array_filter([
            'facebook' => $request->input('facebook'),
            'twitter' => $request->input('twitter'),
            'instagram' => $request->input('instagram'),
            'linkedin' => $request->input('linkedin'),
            'youtube' => $request->input('youtube'),
        ]);

        $validated['social_links'] = $socialLinks;

        $oldCover = $businessPage->cover_photo;
        $oldLogo = $businessPage->logo;

        if ($request->hasFile('cover_photo')) {
            $validated['cover_photo'] = $this->storeFile($request->file('cover_photo'), 'uploads/business_pages/covers');
        }

        if ($request->hasFile('logo')) {
            $validated['logo'] = $this->storeFile($request->file('logo'), 'uploads/business_pages/logos');
        }

        $validated['page_username'] = strtolower($validated['page_username']);

        $businessPage->update($validated);

        // Only delete old media after new upload is moderated and DB update succeeds
        if ($request->hasFile('cover_photo') && $oldCover && File::exists(public_path($oldCover))) {
            File::delete(public_path($oldCover));
        }
        if ($request->hasFile('logo') && $oldLogo && File::exists(public_path($oldLogo))) {
            File::delete(public_path($oldLogo));
        }

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Business Page updated successfully!',
                'business_page' => $businessPage->fresh(['owner']),
            ]);
        }

        return redirect()->route('member.business-pages.show', $businessPage)
            ->with('success', 'Business Page updated successfully!');
    }

    public function destroy(Request $request, BusinessPage $businessPage)
    {
        $member = auth('member')->user();
        if (! $businessPage->isOwner($member->id)) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }
            abort(403, 'Unauthorized action.');
        }

        $businessPage->delete();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Business Page deleted successfully.',
            ]);
        }

        return redirect()->route('member.business-pages.index')
            ->with('success', 'Business Page deleted successfully.');
    }

    public function updateProfilePhoto(Request $request, BusinessPage $businessPage)
    {
        $member = auth('member')->user();
        if (! ($businessPage->isOwner($member->id) || $businessPage->isTeamAdmin($member->id))) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Unauthorized. Only the page owner or administrators can update the profile photo.'], 403);
            }
            abort(403, 'Unauthorized action.');
        }

        $input = $request->allFiles() + $request->all();
        $file = $request->file('profile_photo') ?? ($request->file('logo') ?? $request->file('photo'));

        $validator = \Illuminate\Support\Facades\Validator::make($input, [
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'profile_photo.image' => 'The selected file must be a valid image.',
            'profile_photo.mimes' => 'Only JPG, JPEG, PNG, and WEBP images are allowed.',
            'profile_photo.max' => 'The profile photo size must not exceed 5MB.',
            'logo.image' => 'The selected file must be a valid image.',
            'logo.mimes' => 'Only JPG, JPEG, PNG, and WEBP images are allowed.',
            'logo.max' => 'The logo size must not exceed 5MB.',
        ]);

        if (! $file) {
            $validator->errors()->add('profile_photo', 'Please select an image file to upload.');
        }

        if ($validator->fails() || ! $file) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first('profile_photo') ?: ($validator->errors()->first('logo') ?: 'Please select an image file to upload.'),
                    'errors' => $validator->errors(),
                ], 422);
            }
            return back()->withErrors($validator);
        }

        $oldLogo = $businessPage->logo;
        $path = $this->storeFile($file, 'uploads/business_pages/logos');
        $businessPage->update(['logo' => $path]);

        if ($oldLogo && File::exists(public_path($oldLogo))) {
            File::delete(public_path($oldLogo));
        }

        $photoUrl = asset($path).'?v='.now()->timestamp;

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Business Page profile photo updated successfully!',
                'photo_url' => $photoUrl,
                'logo' => $path,
                'logo_url' => $photoUrl,
                'business_page' => $businessPage->fresh(['owner']),
            ]);
        }

        return back()->with('success', 'Business Page profile photo updated successfully!');
    }

    public function removeProfilePhoto(Request $request, BusinessPage $businessPage)
    {
        $member = auth('member')->user();
        if (! ($businessPage->isOwner($member->id) || $businessPage->isTeamAdmin($member->id))) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }
            abort(403, 'Unauthorized action.');
        }

        if ($businessPage->logo && File::exists(public_path($businessPage->logo))) {
            File::delete(public_path($businessPage->logo));
        }

        $businessPage->update(['logo' => null]);

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Business Page profile photo removed.',
                'business_page' => $businessPage->fresh(['owner']),
            ]);
        }

        return back()->with('success', 'Business Page profile photo removed.');
    }

    public function updateCoverPhoto(Request $request, BusinessPage $businessPage)
    {
        $member = auth('member')->user();
        if (! ($businessPage->isOwner($member->id) || $businessPage->isTeamAdmin($member->id))) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Unauthorized. Only the page owner or administrators can update the cover photo.'], 403);
            }
            abort(403, 'Unauthorized action.');
        }

        $input = $request->allFiles() + $request->all();
        $file = $request->file('cover_photo') ?? ($request->file('cover') ?? $request->file('cover_image'));

        $validator = \Illuminate\Support\Facades\Validator::make($input, [
            'cover_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'cover_photo.image' => 'The selected file must be a valid image.',
            'cover_photo.mimes' => 'Only JPG, JPEG, PNG, and WEBP images are allowed.',
            'cover_photo.max' => 'The cover image size must not exceed 10MB.',
            'cover.image' => 'The selected file must be a valid image.',
            'cover.mimes' => 'Only JPG, JPEG, PNG, and WEBP images are allowed.',
            'cover.max' => 'The cover image size must not exceed 10MB.',
        ]);

        if (! $file) {
            $validator->errors()->add('cover_photo', 'Please select a cover image file to upload.');
        }

        if ($validator->fails() || ! $file) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first('cover_photo') ?: ($validator->errors()->first('cover') ?: 'Please select a cover image file to upload.'),
                    'errors' => $validator->errors(),
                ], 422);
            }
            return back()->withErrors($validator);
        }

        $oldCover = $businessPage->cover_photo;
        $path = $this->storeFile($file, 'uploads/business_pages/covers');
        $businessPage->update(['cover_photo' => $path]);

        if ($oldCover && File::exists(public_path($oldCover))) {
            File::delete(public_path($oldCover));
        }

        $photoUrl = asset($path).'?v='.now()->timestamp;

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Business Page cover photo updated successfully!',
                'photo_url' => $photoUrl,
                'cover_photo' => $path,
                'cover_url' => $photoUrl,
                'business_page' => $businessPage->fresh(['owner']),
            ]);
        }

        return back()->with('success', 'Business Page cover photo updated successfully!');
    }

    public function removeCoverPhoto(Request $request, BusinessPage $businessPage)
    {
        $member = auth('member')->user();
        if (! ($businessPage->isOwner($member->id) || $businessPage->isTeamAdmin($member->id))) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }
            abort(403, 'Unauthorized action.');
        }

        if ($businessPage->cover_photo && File::exists(public_path($businessPage->cover_photo))) {
            File::delete(public_path($businessPage->cover_photo));
        }

        $businessPage->update(['cover_photo' => null]);

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Business Page cover photo removed.',
                'business_page' => $businessPage->fresh(['owner']),
            ]);
        }

        return back()->with('success', 'Business Page cover photo removed.');
    }

    private function storeFile($file, string $directory): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $fileName = Str::uuid().'.'.$extension;
        $type = str_contains($directory, 'logo') ? 'logo' : 'cover';

        $storedPath = app(\App\Http\Controllers\ImageCompressionController::class)->compressAndStore(
            $file,
            $directory,
            $type,
            $fileName,
            'public_uploads',
            'BUSINESS_IMAGE'
        );

        return $storedPath ?: ($directory.'/'.$fileName);
    }
}
