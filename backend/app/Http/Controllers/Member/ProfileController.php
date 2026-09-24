<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\Member;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostReaction;
use App\Models\PostShare;
use App\Models\SavedPost;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $member = auth('member')->user();
        $this->migrateLegacyPhotos($member);

        $activeTab = $request->query('tab', 'timeline');

        // Complete Statistics
        $postsCount = $member->posts()->count();
        $storiesCount = $member->stories()->count();

        $friendshipsQuery = Friendship::query()
            ->forMember($member->id)
            ->accepted()
            ->with(['memberOne', 'memberTwo'])
            ->orderByDesc('accepted_at');
        $friendsCount = (clone $friendshipsQuery)->count();
        $friendsList = $friendshipsQuery->get()->map(fn (Friendship $f) => $f->otherMember($member->id));

        $photosCount = $member->posts()->where('media_type', 'image')->whereNotNull('media_path')->count();
        $videosCount = $member->posts()->where('media_type', 'video')->whereNotNull('media_path')->count();
        $sharesCount = PostShare::query()->where('shared_by', $member->id)->count();

        $postIds = $member->posts()->pluck('id');
        $reactionsReceivedCount = PostReaction::query()->whereIn('post_id', $postIds)->count();
        $commentsReceivedCount = PostComment::query()->whereIn('post_id', $postIds)->count();

        // Timeline Feed: Pinned post first, then latest created
        $posts = Post::query()
            ->with([
                'member',
                'originalPost' => fn ($q) => $q->with(['member', 'businessPage'])->withCount([
                    'likes',
                    'reactions',
                    'shares',
                    'savedPosts',
                    'comments' => fn ($cq) => $cq->whereNull('parent_id'),
                ]),
            ])
            ->withCount(['likes', 'reactions', 'shares', 'savedPosts', 'comments' => fn ($q) => $q->whereNull('parent_id')])
            ->where('member_id', $member->id)
            ->orderByDesc('is_pinned')
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        // Photos gallery items
        $photos = Post::query()
            ->where('member_id', $member->id)
            ->where('media_type', 'image')
            ->whereNotNull('media_path')
            ->latest('created_at')
            ->get();

        // Videos gallery items
        $videos = Post::query()
            ->where('member_id', $member->id)
            ->where('media_type', 'video')
            ->whereNotNull('media_path')
            ->latest('created_at')
            ->get();

        // Stories
        $stories = $member->stories()
            ->withCount(['views', 'likes', 'reactions', 'replies'])
            ->latest('created_at')
            ->get();

        // Saved Posts
        $savedPostIds = SavedPost::query()
            ->where('member_id', $member->id)
            ->pluck('post_id');
        $savedPosts = Post::query()
            ->with([
                'member',
                'originalPost' => fn ($q) => $q->with(['member', 'businessPage'])->withCount([
                    'likes',
                    'reactions',
                    'shares',
                    'savedPosts',
                    'comments' => fn ($cq) => $cq->whereNull('parent_id'),
                ]),
            ])
            ->withCount(['likes', 'reactions', 'shares', 'savedPosts', 'comments' => fn ($q) => $q->whereNull('parent_id')])
            ->whereIn('id', $savedPostIds)
            ->paginate(10);

        // Referral Data
        $introducer = $member->introducer_id
            ? Member::where('user_id', $member->introducer_id)
                ->select(['id', 'name', 'user_id', 'profile_photo', 'mobile_verified_at', 'city', 'country', 'created_at'])
                ->first()
            : null;

        $directReferrals = $member->user_id
            ? Member::where('introducer_id', $member->user_id)
                ->select(['id', 'name', 'user_id', 'profile_photo', 'mobile_verified_at', 'city', 'country', 'created_at'])
                ->latest('created_at')
                ->get()
            : collect();

        $directReferralCount = $member->direct_referral_count ?? $directReferrals->count();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'member' => $member,
                'active_tab' => $activeTab,
                'stats' => [
                    'posts_count' => $postsCount,
                    'stories_count' => $storiesCount,
                    'friends_count' => $friendsCount,
                    'photos_count' => $photosCount,
                    'videos_count' => $videosCount,
                    'shares_count' => $sharesCount,
                    'reactions_received_count' => $reactionsReceivedCount,
                    'comments_received_count' => $commentsReceivedCount,
                    'direct_referrals_count' => $directReferralCount,
                ],
                'posts' => $posts,
                'photos' => $photos,
                'videos' => $videos,
                'friends' => $friendsList,
                'stories' => $stories,
                'saved_posts' => $savedPosts,
                'introducer' => $introducer,
                'direct_referrals' => $directReferrals,
                'direct_referral_count' => $directReferralCount,
            ]);
        }

        return view('member.profile.show', compact(
            'member',
            'activeTab',
            'postsCount',
            'storiesCount',
            'friendsCount',
            'photosCount',
            'videosCount',
            'sharesCount',
            'reactionsReceivedCount',
            'commentsReceivedCount',
            'posts',
            'photos',
            'videos',
            'friendsList',
            'stories',
            'savedPosts'
        ));
    }

    public function edit(Request $request)
    {
        $member = auth('member')->user();
        $this->migrateLegacyPhotos($member);

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'member' => $member,
            ]);
        }

        return view('member.profile.edit', compact('member'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:500'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', 'in:male,female,other,prefer_not_to_say'],
            'phone' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
        ]);

        $member = auth('member')->user();
        $member->update($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Your profile has been updated successfully.',
                'member' => $member,
            ]);
        }

        return redirect()
            ->route('member.profile.show')
            ->with('success', 'Your profile has been updated successfully.');
    }

    public function updateProfilePhoto(Request $request)
    {
        $input = $request->allFiles() + $request->all();
        $file = $request->file('profile_photo') ?? $request->file('photo');

        $validator = \Illuminate\Support\Facades\Validator::make($input, [
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'profile_photo.image' => 'The selected file must be a valid image.',
            'profile_photo.mimes' => 'Only JPG, JPEG, PNG, and WEBP images are allowed.',
            'profile_photo.max' => 'The image size must not exceed 5MB.',
            'photo.image' => 'The selected file must be a valid image.',
            'photo.mimes' => 'Only JPG, JPEG, PNG, and WEBP images are allowed.',
            'photo.max' => 'The image size must not exceed 5MB.',
        ]);

        if (! $file) {
            $validator->errors()->add('profile_photo', 'Please select an image file to upload.');
        }

        if ($validator->fails() || ! $file) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first('profile_photo') ?: ($validator->errors()->first('photo') ?: 'Please select an image file to upload.'),
                    'errors' => $validator->errors(),
                ], 422);
            }
            return back()->withErrors($validator);
        }

        $member = auth('member')->user();
        $path = $this->storePublicPhoto(
            $file,
            'uploads/profile',
            'profile',
            $member->id,
        );

        if (! $path) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Failed to save the image. Please try again.'], 422);
            }
            return back()->with('error', 'We could not upload the image. Please try again.');
        }

        $this->deletePublicPhoto($member->profile_photo, 'uploads/profile');
        $member->update(['profile_photo' => $path]);

        $photoUrl = asset($path).'?v='.now()->timestamp;

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Your profile photo has been updated.',
                'photo_url' => $photoUrl,
                'profile_photo' => $path,
                'member' => $member->fresh(),
            ]);
        }

        return back()->with('success', 'Your profile photo has been updated.');
    }

    public function updateCoverPhoto(Request $request)
    {
        $input = $request->allFiles() + $request->all();
        $file = $request->file('cover_photo') ?? $request->file('cover');

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

        $member = auth('member')->user();
        $path = $this->storePublicPhoto(
            $file,
            'uploads/cover',
            'cover',
            $member->id,
        );

        if (! $path) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Failed to save the cover image. Please try again.'], 422);
            }
            return back()->with('error', 'We could not upload the image. Please try again.');
        }

        $this->deletePublicPhoto($member->cover_photo, 'uploads/cover');
        $member->update(['cover_photo' => $path]);

        $photoUrl = asset($path).'?v='.now()->timestamp;

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Your cover photo has been updated.',
                'photo_url' => $photoUrl,
                'cover_photo' => $path,
                'member' => $member->fresh(),
            ]);
        }

        return back()->with('success', 'Your cover photo has been updated.');
    }

    public function removeProfilePhoto(Request $request)
    {
        $member = auth('member')->user();

        $this->deletePublicPhoto($member->profile_photo, 'uploads/profile');
        $member->update(['profile_photo' => null]);

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Your profile photo has been removed.',
                'member' => $member->fresh(),
            ]);
        }

        return back()->with('success', 'Your profile photo has been removed.');
    }

    public function removeCoverPhoto(Request $request)
    {
        $member = auth('member')->user();

        $this->deletePublicPhoto($member->cover_photo, 'uploads/cover');
        $member->update(['cover_photo' => null]);

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Your cover photo has been removed.',
                'member' => $member->fresh(),
            ]);
        }

        return back()->with('success', 'Your cover photo has been removed.');
    }

    private function storePublicPhoto(
        UploadedFile $photo,
        string $directory,
        string $prefix,
        int $memberId,
    ): ?string {
        try {
            if (! $photo->isValid()) {
                return null;
            }

            $fullDirectory = public_path($directory);
            File::ensureDirectoryExists($fullDirectory);

            $ext = Str::lower($photo->getClientOriginalExtension() ?: $photo->guessExtension() ?: 'jpg');

            do {
                $filename = sprintf(
                    '%s_%d_%d_%s.%s',
                    $prefix,
                    $memberId,
                    time(),
                    Str::lower(Str::random(8)),
                    $ext,
                );
            } while (File::exists($fullDirectory.DIRECTORY_SEPARATOR.$filename));

            $type = $prefix === 'cover' ? 'cover' : 'avatar';
            $context = $prefix === 'cover' ? 'PROFILE_COVER' : 'PROFILE_PHOTO';

            return app(\App\Http\Controllers\ImageCompressionController::class)->compressAndStore(
                $photo,
                $directory,
                $type,
                $filename,
                'public_uploads',
                $context
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('storePublicPhoto failed', [
                'error' => $e->getMessage(),
                'directory' => $directory,
                'member_id' => $memberId,
            ]);
            return null;
        }
    }

    private function deletePublicPhoto(?string $path, string $directory): void
    {
        $path = $path ? ltrim(str_replace('\\', '/', $path), '/') : null;

        if (
            $path
            && str_starts_with($path, $directory.'/')
            && ! str_contains($path, '..')
            && $path === $directory.'/'.basename($path)
            && File::isFile(public_path($path))
        ) {
            File::delete(public_path($path));
        }
    }

    private function migrateLegacyPhotos(Member $member): void
    {
        $updates = array_filter([
            'profile_photo' => $this->copyLegacyPhoto(
                $member->profile_photo,
                'member/profile-photos',
                'uploads/profile',
                'profile',
                $member->id,
            ),
            'cover_photo' => $this->copyLegacyPhoto(
                $member->cover_photo,
                'member/cover-photos',
                'uploads/cover',
                'cover',
                $member->id,
            ),
        ]);

        if ($updates) {
            $member->update($updates);
        }
    }

    private function copyLegacyPhoto(
        ?string $path,
        string $legacyDirectory,
        string $publicDirectory,
        string $prefix,
        int $memberId,
    ): ?string {
        if (! $path) {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');
        $legacyPath = str_starts_with($path, 'storage/')
            ? Str::after($path, 'storage/')
            : $path;

        if (
            ! str_starts_with($legacyPath, $legacyDirectory.'/')
            || str_contains($legacyPath, '..')
            || $legacyPath !== $legacyDirectory.'/'.basename($legacyPath)
        ) {
            return null;
        }

        $extension = Str::lower(pathinfo($legacyPath, PATHINFO_EXTENSION));

        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }

        $source = storage_path('app/public/'.$legacyPath);

        if (! File::isFile($source)) {
            return null;
        }

        try {
            $fullDirectory = public_path($publicDirectory);
            File::ensureDirectoryExists($fullDirectory);

            do {
                $filename = sprintf(
                    '%s_%d_%d_%s.%s',
                    $prefix,
                    $memberId,
                    time(),
                    Str::lower(Str::random(8)),
                    $extension,
                );
                $destination = $fullDirectory.DIRECTORY_SEPARATOR.$filename;
            } while (File::exists($destination));

            if (! File::copy($source, $destination) || ! File::isFile($destination)) {
                return null;
            }

            return $publicDirectory.'/'.$filename;
        } catch (\Throwable) {
            return null;
        }
    }
}
