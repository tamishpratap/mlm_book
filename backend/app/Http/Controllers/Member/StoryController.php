<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\Story;
use App\Models\StoryLike;
use App\Models\StoryReaction;
use App\Models\StoryReply;
use App\Models\StoryView;
use App\Notifications\StoryLikeNotification;
use App\Notifications\StoryReactionNotification;
use App\Notifications\StoryReplyNotification;
use App\Notifications\StoryViewNotification;
use App\Services\NotificationHelper;
use App\Http\Middleware\EnsureMemberMobileVerified;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class StoryController extends Controller
{
    private const IMAGE_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private const VIDEO_MIME_TYPES = [
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/quicktime' => 'mov',
    ];

    public function index(Request $request)
    {
        $currentMember = auth('member')->user();
        $friendIds = Friendship::query()
            ->forMember($currentMember->id)
            ->accepted()
            ->get(['member_one_id', 'member_two_id'])
            ->map(fn (Friendship $friendship) => $friendship->member_one_id === $currentMember->id
                ? $friendship->member_two_id
                : $friendship->member_one_id);

        $feedMemberIds = collect($friendIds->all())
            ->push($currentMember->id)
            ->unique()
            ->values();

        $allActiveStories = Story::query()
            ->with(['member', 'views'])
            ->withCount(['views', 'likes', 'reactions', 'replies'])
            ->active()
            ->whereIn('member_id', $feedMemberIds)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $groupedStories = $allActiveStories->groupBy('member_id');

        $unreadMemberStories = collect();
        $seenMemberStories = collect();
        $ownMemberStory = null;

        foreach ($groupedStories as $mId => $mStories) {
            $mIdInt = (int) $mId;

            // Find first unread story for current member
            $firstUnread = $mStories->first(function ($s) use ($currentMember) {
                return ! $s->views->contains('viewer_member_id', $currentMember->id);
            });

            // Target story: first unread story if exists, otherwise oldest story
            $targetStory = $firstUnread ?? $mStories->first();

            if ($mIdInt === (int) $currentMember->id) {
                $ownMemberStory = $targetStory;
            } elseif ($firstUnread) {
                $unreadMemberStories->push($targetStory);
            } else {
                $seenMemberStories->push($targetStory);
            }
        }

        $stories = collect();
        if ($ownMemberStory) {
            $stories->push($ownMemberStory);
        }
        foreach ($unreadMemberStories as $storyItem) {
            $stories->push($storyItem);
        }
        foreach ($seenMemberStories as $storyItem) {
            $stories->push($storyItem);
        }

        return response()->json([
            'success' => true,
            'stories' => $stories,
            'all_active_stories' => $allActiveStories,
        ]);
    }

    public function store(Request $request)
    {
        $member = auth('member')->user();

        if (! $member || ! $member->isMobileVerified()) {
            $message = EnsureMemberMobileVerified::UNVERIFIED_MESSAGE;
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 403);
            }

            return back()->withInput()->with('error', $message);
        }

        $uploadedMedia = $request->file('media');

        if ($uploadedMedia instanceof UploadedFile) {
            $mimeType = (string) $uploadedMedia->getMimeType();
            $clientExtension = Str::lower($uploadedMedia->getClientOriginalExtension());
            if (
                isset(self::VIDEO_MIME_TYPES[$mimeType])
                || str_starts_with($mimeType, 'video/')
                || in_array($clientExtension, ['mp4', 'mov', 'webm', 'm4v', 'avi', 'mkv'], true)
                || ($mimeType === 'application/octet-stream' && in_array($clientExtension, ['mp4', 'mov'], true) && $this->hasIsoBaseMediaSignature($uploadedMedia))
            ) {
                $exception = ValidationException::withMessages([
                    'media' => 'Videos can only be posted from a Business Page.',
                ]);
                $exception->errorBag = 'story';
                throw $exception;
            }
        }

        if ($uploadedMedia instanceof UploadedFile && ! $uploadedMedia->isValid()) {
            $exception = ValidationException::withMessages([
                'media' => $this->uploadErrorMessage($uploadedMedia->getError()),
            ]);
            $exception->errorBag = 'story';

            throw $exception;
        }

        $validated = $request->validateWithBag('story', [
            'caption' => ['nullable', 'string', 'max:500'],
            'media' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'media.required' => 'Please select an image.',
            'media.file' => 'Upload a JPG, PNG, or WebP file.',
            'media.max' => 'The uploaded file must not be larger than 5 MB.',
            'media.uploaded' => 'The image could not be received by the server. Check the PHP upload limit.',
        ]);

        $member = auth('member')->user();
        $media = $request->file('media');
        $caption = filled($validated['caption'] ?? null)
            ? trim($validated['caption'])
            : null;
        $mediaPath = null;
        $targetDirectory = null;
        $uploadedMimeType = $media->getMimeType();
        $uploadedExtension = Str::lower($media->getClientOriginalExtension());
        $uploadedSize = $media->getSize();

        try {
            [$mediaType, $extension, $maxBytes] = $this->validatedMediaDetails($media);

            if ($media->getSize() > $maxBytes) {
                throw ValidationException::withMessages([
                    'media' => 'The image must not be larger than 5 MB.',
                ]);
            }

            $directory = 'uploads/stories/images';
            $targetDirectory = public_path($directory);
            File::ensureDirectoryExists($targetDirectory);

            if (! is_writable($targetDirectory)) {
                throw new \RuntimeException('The Story upload directory is not writable.');
            }

            $filename = sprintf(
                'story_%d_%d_%s.%s',
                $member->id,
                time(),
                Str::lower(Str::random(8)),
                $extension,
            );

            $mediaPath = app(\App\Http\Controllers\ImageCompressionController::class)->compressAndStore(
                $media,
                $directory,
                'story',
                $filename,
                'public_uploads',
                'STORY_IMAGE'
            );

            if (! $mediaPath || ! File::exists(public_path($mediaPath)) || File::size(public_path($mediaPath)) === 0) {
                throw new \RuntimeException('The Story media file was not stored correctly.');
            }

            $story = Story::create([
                'member_id' => $member->id,
                'caption' => $caption,
                'media_type' => $mediaType,
                'media_path' => $mediaPath,
                'expires_at' => now()->addHours(24),
            ]);
        } catch (ValidationException $exception) {
            if ($mediaPath) {
                File::delete(public_path($mediaPath));
            }

            $exception->errorBag = 'story';

            throw $exception;
        } catch (Throwable $exception) {
            if ($mediaPath) {
                File::delete(public_path($mediaPath));
            }

            Log::error('Story media upload failed', [
                'member_id' => $member->id,
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'uploaded_mime_type' => $uploadedMimeType,
                'uploaded_extension' => $uploadedExtension,
                'uploaded_size_bytes' => $uploadedSize,
                'target_directory' => $targetDirectory,
                'php_upload_error_code' => $media->getError(),
            ]);

            return $this->shareErrorResponse($request);
        }

        $story->load('member');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Your Story has been shared.',
                'story_id' => $story->id,
                'member_id' => $member->id,
                'story' => $story,
                'html' => view('member.stories.partials.card', [
                    'story' => $story,
                    'currentMemberId' => $member->id,
                ])->render(),
            ]);
        }

        return back()->with('success', 'Your Story has been shared.');
    }

    public function show(Request $request, Story $story)
    {
        $currentMemberId = auth('member')->id();
        $isOwner = (int) $story->member_id === (int) $currentMemberId;
        $isExpired = $story->expires_at && $story->expires_at->lessThanOrEqualTo(now());

        if (! $isOwner) {
            abort_if($isExpired, 404);
        }

        $canView = $isOwner
            || Friendship::query()
                ->between($currentMemberId, $story->member_id)
                ->accepted()
                ->exists();

        abort_unless($canView, 403);

        if ($currentMemberId !== $story->member_id && ! $isExpired) {
            $isFirstView = false;
            DB::transaction(function () use ($story, $currentMemberId, &$isFirstView) {
                $exists = StoryView::query()
                    ->where('story_id', $story->id)
                    ->where('viewer_member_id', $currentMemberId)
                    ->lockForUpdate()
                    ->exists();

                if (! $exists) {
                    try {
                        StoryView::create([
                            'story_id' => $story->id,
                            'viewer_member_id' => $currentMemberId,
                        ]);
                        $isFirstView = true;
                    } catch (\Illuminate\Database\UniqueConstraintViolationException|\Illuminate\Database\QueryException $e) {
                        // Concurrent request already inserted the view record
                        $isFirstView = false;
                    }
                }
            });

            if ($isFirstView) {
                NotificationHelper::send(
                    $story->member,
                    new StoryViewNotification(auth('member')->user(), $story),
                    'story',
                    $story->id,
                    auth('member')->user()
                );
            }
        }

        $story->load('member');
        if ($isOwner) {
            $story->loadCount(['views', 'likes', 'reactions', 'replies']);
        }

        if ($isExpired) {
            $activeStoryIds = [(int) $story->id];
            $previousStoryId = null;
            $nextStoryId = null;
            $authorStoryCount = 1;
            $authorStoryIndex = 0;
        } else {
            $friendIds = Friendship::query()
                ->forMember($currentMemberId)
                ->accepted()
                ->get(['member_one_id', 'member_two_id'])
                ->map(fn (Friendship $friendship) => $friendship->member_one_id === $currentMemberId
                    ? $friendship->member_two_id
                    : $friendship->member_one_id);

            $targetMemberId = (int) $story->member_id;

            $feedMemberIds = collect($friendIds->all())
                ->push($currentMemberId)
                ->push($targetMemberId)
                ->unique()
                ->map(fn ($id) => (int) $id)
                ->values();

            // Fetch all active stories for feed members, ordered chronologically (oldest first)
            $allActiveStories = Story::query()
                ->active()
                ->whereIn('member_id', $feedMemberIds)
                ->with(['views', 'member'])
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            // Group active stories by member_id
            $storiesByMember = $allActiveStories->groupBy('member_id');

            // Feed members in chronological order
            $feedMemberOrder = $storiesByMember->keys()->map(fn ($k) => (int) $k)->values()->all();

            $targetMemberId = (int) $story->member_id;
            $targetPos = array_search($targetMemberId, $feedMemberOrder, true);
            if ($targetPos === false) {
                $targetPos = 0;
            }

            // Candidates starting from targetMemberId to end of feed
            $candidateMemberIds = array_slice($feedMemberOrder, $targetPos);

            // Check if any candidate member after targetMemberId has unread stories
            $otherUnreadMemberIds = [];
            foreach ($candidateMemberIds as $mId) {
                if ($mId === $targetMemberId) {
                    continue;
                }
                $mStories = $storiesByMember->get($mId) ?? $storiesByMember->get((string) $mId);
                $hasUnread = $mStories ? $mStories->contains(function ($s) use ($currentMemberId) {
                    return ! $s->views->contains('viewer_member_id', $currentMemberId);
                }) : false;

                if ($hasUnread) {
                    $otherUnreadMemberIds[] = $mId;
                }
            }

            // If there are unread candidates, play targetMemberId followed by unread candidates.
            // Otherwise, play all candidates from targetMemberId to end of feed.
            if (! empty($otherUnreadMemberIds)) {
                $orderedMemberIds = array_merge([$targetMemberId], $otherUnreadMemberIds);
            } else {
                $orderedMemberIds = $candidateMemberIds;
            }

            // Build master linear playback queue of story IDs in exact order
            $activeStoryIds = [];
            foreach ($orderedMemberIds as $mId) {
                $mIdInt = (int) $mId;
                $memberStories = $storiesByMember->get($mIdInt) ?? $storiesByMember->get((string) $mIdInt);
                if ($memberStories) {
                    foreach ($memberStories as $mStory) {
                        $activeStoryIds[] = (int) $mStory->id;
                    }
                }
            }

            // Fallback: If requested story is not in activeStoryIds, prepend it
            if (! in_array((int) $story->id, $activeStoryIds, true)) {
                array_unshift($activeStoryIds, (int) $story->id);
            }

            $currentIndex = array_search((int) $story->id, $activeStoryIds, true);
            $nextStoryId = ($currentIndex !== false && isset($activeStoryIds[$currentIndex + 1])) ? $activeStoryIds[$currentIndex + 1] : null;

            // Author active stories count & current story index for segmented progress bars
            $authorStories = $storiesByMember->get($targetMemberId) ?? $storiesByMember->get((string) $targetMemberId) ?? collect();
            $authorStoriesCollection = $authorStories->values();
            $authorStoryCount = $authorStoriesCollection->count() ?: 1;
            $authorStoryIndex = $authorStoriesCollection->search(fn ($s) => (int) $s->id === (int) $story->id);
            if ($authorStoryIndex === false) {
                $authorStoryIndex = 0;
            }

            // Previous Story ID calculation:
            // 1. If current member has an earlier story, return that previous story ID
            // 2. Otherwise, if a previous member exists in feedMemberOrder, return that previous member's latest available story ID
            // 3. Otherwise (first member, first story), return null (safe boundary)
            $previousStoryId = null;
            if ($authorStoryIndex > 0 && isset($authorStoriesCollection[$authorStoryIndex - 1])) {
                $previousStoryId = (int) $authorStoriesCollection[$authorStoryIndex - 1]->id;
            } elseif ($targetPos !== false && $targetPos > 0) {
                for ($prevPos = $targetPos - 1; $prevPos >= 0; $prevPos--) {
                    $prevMemberId = $feedMemberOrder[$prevPos];
                    $prevMemberStories = $storiesByMember->get($prevMemberId) ?? $storiesByMember->get((string) $prevMemberId) ?? collect();
                    if ($prevMemberStories->isNotEmpty()) {
                        $previousStoryId = (int) $prevMemberStories->last()->id;
                        break;
                    }
                }
            }
        }

        $currentMemberReaction = StoryReaction::query()
            ->where('story_id', $story->id)
            ->where('member_id', $currentMemberId)
            ->first();
        $userReaction = $currentMemberReaction?->reaction;
        $hasLiked = StoryLike::query()
            ->where('story_id', $story->id)
            ->where('member_id', $currentMemberId)
            ->exists();

        $reactionsCount = StoryReaction::query()->where('story_id', $story->id)->count();
        $likesCount = StoryLike::query()->where('story_id', $story->id)->count();
        $repliesCount = StoryReply::query()->where('story_id', $story->id)->count();
        $viewsCount = StoryView::query()->where('story_id', $story->id)->count();
        $unreadRepliesCount = $isOwner
            ? StoryReply::query()->where('story_id', $story->id)->where('receiver_id', $currentMemberId)->where('is_seen', false)->count()
            : 0;

        $topReactions = StoryReaction::query()
            ->where('story_id', $story->id)
            ->selectRaw('reaction, count(*) as total')
            ->groupBy('reaction')
            ->orderByDesc('total')
            ->limit(3)
            ->pluck('reaction')
            ->map(fn ($r) => StoryReaction::EMOJI_MAP[$r] ?? '👍')
            ->values()
            ->toArray();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            $payload = [
                'success' => true,
                'story_id' => $story->id,
                'story' => $story,
                'is_owner' => $isOwner,
                'previous_story_id' => $previousStoryId,
                'next_story_id' => $nextStoryId,
                'author_story_count' => $authorStoryCount,
                'author_story_index' => $authorStoryIndex,
                'active_story_ids' => $activeStoryIds,
                'user_reaction' => $userReaction,
                'has_liked' => $hasLiked,
                'reactions_count' => $reactionsCount,
                'likes_count' => $likesCount,
                'replies_count' => $repliesCount,
                'unread_replies_count' => $unreadRepliesCount,
                'top_reactions' => $topReactions,
                'html' => view('member.stories.partials.viewer', [
                    'story' => $story,
                    'standalone' => false,
                    'previousStoryId' => $previousStoryId,
                    'nextStoryId' => $nextStoryId,
                    'authorStoryCount' => $authorStoryCount,
                    'authorStoryIndex' => $authorStoryIndex,
                ])->render(),
            ];

            if ($isOwner) {
                $payload['views_count'] = $viewsCount;
            }

            return response()->json($payload);
        }

        return view('member.stories.show', [
            'story' => $story,
            'previousStoryId' => $previousStoryId,
            'nextStoryId' => $nextStoryId,
            'authorStoryCount' => $authorStoryCount,
            'authorStoryIndex' => $authorStoryIndex,
        ]);
    }

    public function viewers(Request $request, Story $story)
    {
        $currentMemberId = auth('member')->id();
        abort_unless($story->member_id === $currentMemberId, 403);

        $views = $story->views()
            ->with('viewer')
            ->latest('created_at')
            ->get();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'story_id' => $story->id,
                'views_count' => $views->count(),
                'viewers' => $views,
                'html' => view('member.stories.partials.viewers-modal', [
                    'story' => $story,
                    'views' => $views,
                ])->render(),
            ]);
        }

        return back();
    }

    public function toggleLike(Request $request, Story $story)
    {
        abort_if($story->expires_at->isPast(), 404);

        $currentMemberId = auth('member')->id();
        $canInteract = $story->member_id === $currentMemberId
            || Friendship::query()
                ->between($currentMemberId, $story->member_id)
                ->accepted()
                ->exists();

        abort_unless($canInteract, 403);

        $existingLike = StoryLike::query()
            ->where('story_id', $story->id)
            ->where('member_id', $currentMemberId)
            ->first();

        if ($existingLike) {
            $existingLike->delete();
            StoryReaction::query()
                ->where('story_id', $story->id)
                ->where('member_id', $currentMemberId)
                ->where('reaction', 'like')
                ->delete();
            $hasLiked = false;
            $userReaction = null;
        } else {
            StoryLike::create([
                'story_id' => $story->id,
                'member_id' => $currentMemberId,
            ]);
            StoryReaction::updateOrCreate(
                ['story_id' => $story->id, 'member_id' => $currentMemberId],
                ['reaction' => 'like']
            );
            $hasLiked = true;
            $userReaction = 'like';
            if ($story->member_id !== $currentMemberId) {
                NotificationHelper::send($story->member, new StoryLikeNotification(auth('member')->user(), $story), 'story', $story->id, auth('member')->user());
            }
        }

        return $this->buildReactionResponse($story, $currentMemberId, $hasLiked, $userReaction);
    }

    public function react(Request $request, Story $story)
    {
        abort_if($story->expires_at->isPast(), 404);

        $validated = $request->validate([
            'reaction' => ['required', 'string', 'in:like,love,haha,wow,sad,angry'],
        ]);

        $reactionType = $validated['reaction'];
        $currentMemberId = auth('member')->id();
        $canInteract = $story->member_id === $currentMemberId
            || Friendship::query()
                ->between($currentMemberId, $story->member_id)
                ->accepted()
                ->exists();

        abort_unless($canInteract, 403);

        $existingReaction = StoryReaction::query()
            ->where('story_id', $story->id)
            ->where('member_id', $currentMemberId)
            ->first();

        if ($existingReaction && $existingReaction->reaction === $reactionType) {
            $existingReaction->delete();
            StoryLike::query()
                ->where('story_id', $story->id)
                ->where('member_id', $currentMemberId)
                ->delete();
            $userReaction = null;
            $hasLiked = false;
        } else {
            StoryReaction::updateOrCreate(
                ['story_id' => $story->id, 'member_id' => $currentMemberId],
                ['reaction' => $reactionType]
            );

            if ($reactionType === 'like') {
                StoryLike::firstOrCreate([
                    'story_id' => $story->id,
                    'member_id' => $currentMemberId,
                ]);
                $hasLiked = true;
            } else {
                StoryLike::query()
                    ->where('story_id', $story->id)
                    ->where('member_id', $currentMemberId)
                    ->delete();
                $hasLiked = false;
            }
            $userReaction = $reactionType;
            if ($story->member_id !== $currentMemberId) {
                NotificationHelper::send($story->member, new StoryReactionNotification(auth('member')->user(), $story, $reactionType), 'story', $story->id, auth('member')->user());
            }
        }

        return $this->buildReactionResponse($story, $currentMemberId, $hasLiked, $userReaction);
    }

    public function reactors(Request $request, Story $story)
    {
        $currentMemberId = auth('member')->id();
        abort_unless($story->member_id === $currentMemberId, 403);

        $reactions = StoryReaction::query()
            ->with('member')
            ->where('story_id', $story->id)
            ->latest('updated_at')
            ->get();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'story_id' => $story->id,
                'reactions_count' => $reactions->count(),
                'reactors' => $reactions,
                'html' => view('member.stories.partials.reactions-modal', [
                    'story' => $story,
                    'reactions' => $reactions,
                ])->render(),
            ]);
        }

        return back();
    }

    private function buildReactionResponse(Story $story, int $currentMemberId, bool $hasLiked, ?string $userReaction)
    {
        $reactionsCount = StoryReaction::query()->where('story_id', $story->id)->count();
        $likesCount = StoryLike::query()->where('story_id', $story->id)->count();

        $topReactions = StoryReaction::query()
            ->where('story_id', $story->id)
            ->selectRaw('reaction, count(*) as total')
            ->groupBy('reaction')
            ->orderByDesc('total')
            ->limit(3)
            ->pluck('reaction')
            ->map(fn ($r) => StoryReaction::EMOJI_MAP[$r] ?? '👍')
            ->values()
            ->toArray();

        return response()->json([
            'success' => true,
            'story_id' => $story->id,
            'has_liked' => $hasLiked,
            'user_reaction' => $userReaction,
            'user_reaction_emoji' => $userReaction ? (StoryReaction::EMOJI_MAP[$userReaction] ?? '👍') : null,
            'user_reaction_label' => $userReaction ? (StoryReaction::LABEL_MAP[$userReaction] ?? 'Like') : 'Like',
            'reactions_count' => $reactionsCount,
            'likes_count' => $likesCount,
            'top_reactions' => $topReactions,
        ]);
    }

    public function reply(Request $request, Story $story)
    {
        abort_if($story->expires_at->isPast(), 404);

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:500'],
        ]);

        $message = trim($validated['message']);
        if (mb_strlen($message) === 0) {
            throw ValidationException::withMessages([
                'message' => 'Please enter a reply message.',
            ]);
        }

        $currentMemberId = auth('member')->id();
        $canInteract = $story->member_id === $currentMemberId
            || Friendship::query()
                ->between($currentMemberId, $story->member_id)
                ->accepted()
                ->exists();

        abort_unless($canInteract, 403);

        $reply = StoryReply::create([
            'story_id' => $story->id,
            'sender_id' => $currentMemberId,
            'receiver_id' => $story->member_id,
            'message' => $message,
            'is_seen' => false,
        ]);

        NotificationHelper::send($story->member, new StoryReplyNotification(auth('member')->user(), $story, $message), 'story', $story->id, auth('member')->user());

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Your reply has been sent.',
                'story_id' => $story->id,
                'reply_id' => $reply->id,
            ]);
        }

        return back()->with('success', 'Your reply has been sent.');
    }

    public function replies(Request $request, Story $story)
    {
        $currentMemberId = auth('member')->id();

        $isOwner = $story->member_id === $currentMemberId;
        $hasSentReply = StoryReply::query()
            ->where('story_id', $story->id)
            ->where('sender_id', $currentMemberId)
            ->exists();

        abort_unless($isOwner || $hasSentReply, 403);

        if ($isOwner) {
            StoryReply::query()
                ->where('story_id', $story->id)
                ->where('receiver_id', $currentMemberId)
                ->where('is_seen', false)
                ->update(['is_seen' => true]);
        }

        $query = $story->replies()->with(['sender', 'receiver']);
        if (! $isOwner) {
            $query->where(function ($q) use ($currentMemberId) {
                $q->where('sender_id', $currentMemberId)
                    ->orWhere('receiver_id', $currentMemberId);
            });
        }

        $replies = $query->orderBy('created_at', 'asc')->get();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'story_id' => $story->id,
                'replies_count' => $replies->count(),
                'replies' => $replies,
                'is_owner' => $isOwner,
                'html' => view('member.stories.partials.replies-modal', [
                    'story' => $story,
                    'replies' => $replies,
                    'isOwner' => $isOwner,
                    'currentMemberId' => $currentMemberId,
                ])->render(),
            ]);
        }

        return back();
    }

    public function destroy(Request $request, Story $story)
    {
        $currentMemberId = auth('member')->id();

        // Security Check: Only Story Owner can delete story
        abort_unless($story->member_id === $currentMemberId, 403);

        DB::transaction(function () use ($story) {
            // Delete related models atomically
            $story->views()->delete();
            $story->likes()->delete();
            $story->reactions()->delete();
            $story->replies()->delete();

            // Delete actual media file from storage if safe & present
            if ($story->media_path && File::exists(public_path($story->media_path))) {
                File::delete(public_path($story->media_path));
            }

            // Delete story record
            $story->delete();
        });

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Your story has been deleted.',
                'story_id' => $story->id,
            ]);
        }

        return redirect()->route('member.dashboard')->with('success', 'Your story has been deleted.');
    }

    public function destroyReply(Request $request, StoryReply $reply)
    {
        $currentMemberId = auth('member')->id();

        $canDelete = $reply->sender_id === $currentMemberId
            || $reply->receiver_id === $currentMemberId;

        abort_unless($canDelete, 403);

        $storyId = $reply->story_id;
        $reply->delete();

        $remainingCount = StoryReply::query()->where('story_id', $storyId)->count();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Reply deleted.',
                'reply_id' => $reply->id,
                'story_id' => $storyId,
                'replies_count' => $remainingCount,
            ]);
        }

        return back()->with('success', 'Reply deleted.');
    }

    private function validatedMediaDetails(UploadedFile $media): array
    {
        $mimeType = (string) $media->getMimeType();
        $clientExtension = Str::lower($media->getClientOriginalExtension());

        if (
            isset(self::VIDEO_MIME_TYPES[$mimeType])
            || str_starts_with($mimeType, 'video/')
            || in_array($clientExtension, ['mp4', 'mov', 'webm', 'm4v', 'avi', 'mkv'], true)
            || ($mimeType === 'application/octet-stream' && in_array($clientExtension, ['mp4', 'mov'], true) && $this->hasIsoBaseMediaSignature($media))
        ) {
            $exception = ValidationException::withMessages([
                'media' => 'Videos can only be posted from a Business Page.',
            ]);
            $exception->errorBag = 'story';
            throw $exception;
        }

        if (isset(self::IMAGE_MIME_TYPES[$mimeType])) {
            return ['image', self::IMAGE_MIME_TYPES[$mimeType], 5 * 1024 * 1024];
        }

        $exception = ValidationException::withMessages([
            'media' => 'Upload a JPG, PNG, or WebP file.',
        ]);
        $exception->errorBag = 'story';
        throw $exception;
    }

    private function hasIsoBaseMediaSignature(UploadedFile $media): bool
    {
        $handle = fopen($media->getPathname(), 'rb');

        if ($handle === false) {
            return false;
        }

        $header = fread($handle, 12);
        fclose($handle);

        return is_string($header)
            && strlen($header) === 12
            && substr($header, 4, 4) === 'ftyp';
    }

    private function uploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The video is larger than the server upload limit.',
            UPLOAD_ERR_PARTIAL => 'The video upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_FILE => 'Please select an image or video.',
            UPLOAD_ERR_NO_TMP_DIR => 'The server upload folder is unavailable.',
            UPLOAD_ERR_CANT_WRITE => 'The server could not save the uploaded video.',
            UPLOAD_ERR_EXTENSION => 'The server stopped the video upload.',
            default => 'The video could not be received by the server. Check the PHP upload limit.',
        };
    }

    private function shareErrorResponse(Request $request)
    {
        $message = 'We could not save your Story. Please try again.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 500);
        }

        return back()->withInput()->with('error', $message);
    }
}
