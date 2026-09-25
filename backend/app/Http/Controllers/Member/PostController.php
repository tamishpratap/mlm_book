<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\BlockedUser;
use App\Models\Friendship;
use App\Models\Member;
use App\Models\Post;
use App\Models\CommentReaction;
use App\Models\PostComment;
use App\Models\PostLike;
use App\Models\PostReaction;
use App\Models\PostShare;
use App\Models\SavedPost;
use App\Models\HiddenPost;
use App\Models\ReportedPost;
use App\Notifications\CommentReactionNotification;
use App\Notifications\CommentReplyNotification;
use App\Notifications\FriendCreatedPostNotification;
use App\Notifications\PostCommentNotification;
use App\Notifications\PostLikeNotification;
use App\Notifications\PostReactionNotification;
use App\Notifications\PostShareNotification;
use App\Services\NotificationHelper;
use App\Http\Middleware\EnsureMemberMobileVerified;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PostController extends Controller
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

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:5000', 'required_without:media'],
            'media' => ['nullable', 'file', 'required_without:body'],
        ]);

        $media = $request->file('media');
        $body = filled($validated['body'] ?? null) ? trim($validated['body']) : null;
        $mediaType = null;
        $mediaPath = null;

        try {
            if ($media) {
                [$mediaType, $extension, $maxBytes] = $this->validatedMediaDetails($media);

                if ($media->getSize() > $maxBytes) {
                    throw ValidationException::withMessages([
                        'media' => $mediaType === 'image'
                            ? 'Images may not be larger than 5 MB.'
                            : 'Videos may not be larger than 60 MB.',
                    ]);
                }

                $directory = 'uploads/posts/images';
                $filename = sprintf(
                    'post_%d_%d_%s.%s',
                    $member->id,
                    time(),
                    Str::lower(Str::random(8)),
                    $extension,
                );

                $mediaPath = app(\App\Http\Controllers\ImageCompressionController::class)->compressAndStore(
                    $media,
                    $directory,
                    'post',
                    $filename,
                    'public_uploads'
                );
            }

            $post = Post::create([
                'member_id' => $member->id,
                'body' => $body,
                'media_type' => $mediaType,
                'media_path' => $mediaPath,
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            if ($mediaPath) {
                File::delete(public_path($mediaPath));
            }

            report($exception);

            return $this->publishErrorResponse($request);
        }

        $this->notifyFriends($member, $post);
        $post->load('member');

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Your post has been published.',
                'post_id' => $post->id,
                'post' => $post,
                'html' => view('member.posts.partials.card', compact('post'))->render(),
            ]);
        }

        return back()->with('success', 'Your post has been published.');
    }

    private function canMemberAccessPost(int $memberId, Post $post): bool
    {
        $targetPost = $post->originalPost ?? $post;

        if ($targetPost->member_id === $memberId || $post->member_id === $memberId) {
            return true;
        }

        // If either member has blocked the other, access is denied
        if (BlockedUser::query()
            ->where(function ($q) use ($memberId, $targetPost) {
                $q->where('member_id', $memberId)
                  ->where('blocked_member_id', $targetPost->member_id);
            })
            ->orWhere(function ($q) use ($memberId, $targetPost) {
                $q->where('member_id', $targetPost->member_id)
                  ->where('blocked_member_id', $memberId);
            })
            ->exists()) {
            return false;
        }

        // Target author must be socially eligible (verified and active)
        if ($targetPost->member && ! $targetPost->member->isSociallyEligible()) {
            return false;
        }

        if ($targetPost->business_page_id) {
            return true;
        }

        if ($targetPost->group_id) {
            return \App\Models\GroupMember::where('group_id', $targetPost->group_id)
                ->where('member_id', $memberId)
                ->exists();
        }

        if ($targetPost->community_id) {
            return \App\Models\CommunityMember::where('community_id', $targetPost->community_id)
                ->where('member_id', $memberId)
                ->exists();
        }

        if (Friendship::query()->between($memberId, $targetPost->member_id)->accepted()->exists()) {
            return true;
        }

        return $targetPost->community_id === null && $targetPost->group_id === null;
    }

    public function show(Request $request, Post $post)
    {
        $currentMemberId = auth('member')->id();
        abort_unless($this->canMemberAccessPost($currentMemberId, $post), 403);

        $post->load([
            'member',
            'businessPage',
            'community',
            'originalPost.member',
            'originalPost.businessPage',
            'originalPost.community',
        ])->loadCount([
            'likes',
            'reactions',
            'shares',
            'savedPosts',
            'comments' => fn ($query) => $query->whereNull('parent_id'),
        ]);

        $latestComments = $post->comments()
            ->whereNull('parent_id')
            ->with([
                'member',
                'reactions',
                'replies' => fn ($rq) => $rq->with(['member', 'reactions'])->orderBy('created_at', 'asc'),
            ])
            ->withCount('replies')
            ->orderBy('created_at', 'desc')
            ->take(4)
            ->get()
            ->reverse()
            ->values();

        $post->setRelation('comments', $latestComments);

        if ($post->originalPost) {
            $post->originalPost->loadCount([
                'likes',
                'reactions',
                'shares',
                'savedPosts',
                'comments' => fn ($query) => $query->whereNull('parent_id'),
            ]);
        }

        if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
            return response()->json([
                'success' => true,
                'post' => $post,
            ]);
        }

        return view('member.posts.show', compact('post'));
    }

    private function validatedMediaDetails(UploadedFile $media): array
    {
        $mimeType = (string) $media->getMimeType();

        if (isset(self::VIDEO_MIME_TYPES[$mimeType]) || str_starts_with($mimeType, 'video/')) {
            throw ValidationException::withMessages([
                'media' => 'Videos can only be posted from a Business Page.',
            ]);
        }

        if (isset(self::IMAGE_MIME_TYPES[$mimeType])) {
            return ['image', self::IMAGE_MIME_TYPES[$mimeType], 5 * 1024 * 1024];
        }

        throw ValidationException::withMessages([
            'media' => 'Please choose a JPG, PNG, or WEBP image.',
        ]);
    }

    private function notifyFriends(Member $member, Post $post): void
    {
        $friendIds = Friendship::query()
            ->forMember($member->id)
            ->accepted()
            ->get(['member_one_id', 'member_two_id'])
            ->toBase()
            ->map(fn (Friendship $friendship) => $friendship->member_one_id === $member->id
                ? $friendship->member_two_id
                : $friendship->member_one_id)
            ->unique();

        if ($friendIds->isEmpty()) {
            return;
        }

        $friends = Member::query()->whereKey($friendIds->all())->get();
        try {
            Notification::send(
                $friends,
                new FriendCreatedPostNotification($member, $post),
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function publishErrorResponse(Request $request)
    {
        $message = 'We could not publish your post. Please try again.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 500);
        }

        return back()->withInput()->with('error', $message);
    }

    public function toggleLike(Request $request, Post $post)
    {
        return $this->react($request->merge(['reaction' => 'like']), $post);
    }

    public function likers(Request $request, Post $post)
    {
        $currentMember = auth('member')->user();
        if (! $currentMember || ($post->member_id !== $currentMember->id && ! ($post->businessPage && $post->businessPage->isTeamAdmin($currentMember->id)))) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only the post author can view the list of members who liked this post.',
                ], 403);
            }
            return back()->with('error', 'Unauthorized.');
        }

        $likes = $post->likes()
            ->with(['member' => function ($q) {
                $q->select(['id', 'name', 'user_id', 'profile_photo']);
            }])
            ->latest('created_at')
            ->get();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'post_id' => $post->id,
                'likes_count' => $likes->count(),
                'likers' => $likes,
                'html' => view('member.posts.partials.likers-modal', [
                    'post' => $post,
                    'likes' => $likes,
                ])->render(),
            ]);
        }

        return back();
    }

    public function react(Request $request, Post $post)
    {
        $currentMember = auth('member')->user();
        if (! $currentMember || ! $currentMember->isMobileVerified()) {
            $message = EnsureMemberMobileVerified::UNVERIFIED_MESSAGE;
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 403);
            }

            return back()->with('error', $message);
        }

        try {
            $validated = $request->validate([
                'reaction' => ['required', 'string', 'in:like,love,haha,wow,sad,angry'],
            ]);

            $currentMemberId = auth('member')->id();
            $selectedReaction = $validated['reaction'];

            $existingReaction = PostReaction::query()
                ->where('post_id', $post->id)
                ->where('member_id', $currentMemberId)
                ->first();

            if ($existingReaction && $existingReaction->reaction === $selectedReaction) {
                $existingReaction->delete();
                PostLike::query()
                    ->where('post_id', $post->id)
                    ->where('member_id', $currentMemberId)
                    ->delete();
                $userReaction = null;
                $hasLiked = false;
            } else {
                PostReaction::updateOrCreate(
                    [
                        'post_id' => $post->id,
                        'member_id' => $currentMemberId,
                    ],
                    [
                        'reaction' => $selectedReaction,
                    ]
                );
                PostLike::firstOrCreate([
                    'post_id' => $post->id,
                    'member_id' => $currentMemberId,
                ]);
                $userReaction = $selectedReaction;
                $hasLiked = true;
                NotificationHelper::send($post->member, new PostReactionNotification(auth('member')->user(), $post, $userReaction), 'post', $post->id, auth('member')->user());
            }

            $reactionsCount = PostReaction::query()->where('post_id', $post->id)->count();
            $likesCount = PostLike::query()->where('post_id', $post->id)->count();

            $topReactions = PostReaction::query()
                ->where('post_id', $post->id)
                ->selectRaw('reaction, COUNT(*) as total')
                ->groupBy('reaction')
                ->orderByDesc('total')
                ->take(3)
                ->pluck('reaction')
                ->map(fn ($r) => [
                    'type' => $r,
                    'emoji' => PostReaction::EMOJI_MAP[$r] ?? '👍',
                ])
                ->values();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'post_id' => $post->id,
                    'liked' => $hasLiked,
                    'has_liked' => $hasLiked,
                    'user_reaction' => $userReaction,
                    'user_reaction_emoji' => $userReaction ? (PostReaction::EMOJI_MAP[$userReaction] ?? '👍') : null,
                    'user_reaction_label' => $userReaction ? (PostReaction::LABEL_MAP[$userReaction] ?? 'Like') : 'Like',
                    'user_reaction_color' => $userReaction ? (PostReaction::COLOR_MAP[$userReaction] ?? '#2563eb') : '#475569',
                    'reactions_count' => $reactionsCount,
                    'likes_count' => $likesCount,
                    'top_reactions' => $topReactions,
                    'message' => $hasLiked ? 'Post liked successfully' : 'Post unliked',
                ]);
            }

            return back();
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process like: '.$e->getMessage(),
                ], 500);
            }
            throw $e;
        }
    }

    public function reactors(Request $request, Post $post)
    {
        $currentMember = auth('member')->user();
        if (! $currentMember || ($post->member_id !== $currentMember->id && ! ($post->businessPage && $post->businessPage->isTeamAdmin($currentMember->id)))) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only the post author can view the list of members who reacted to this post.',
                ], 403);
            }
            return back()->with('error', 'Unauthorized.');
        }

        $reactions = $post->reactions()
            ->with(['member' => function ($q) {
                $q->select(['id', 'name', 'user_id', 'profile_photo']);
            }])
            ->latest('created_at')
            ->get();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'post_id' => $post->id,
                'reactions_count' => $reactions->count(),
                'reactors' => $reactions,
                'html' => view('member.posts.partials.reactors-modal', [
                    'post' => $post,
                    'reactions' => $reactions,
                ])->render(),
            ]);
        }

        return back();
    }

    public function storeComment(Request $request, Post $post)
    {
        $currentMember = auth('member')->user();
        if (! $currentMember || ! $currentMember->isMobileVerified()) {
            $message = EnsureMemberMobileVerified::UNVERIFIED_MESSAGE;
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 403);
            }

            return back()->with('error', $message);
        }

        $validated = $request->validate([
            'comment' => ['required', 'string', 'min:1', 'max:1000'],
        ]);

        $currentMemberId = auth('member')->id();
        abort_unless($this->canMemberAccessPost($currentMemberId, $post), 403);

        $commentText = trim($validated['comment']);

        $comment = PostComment::create([
            'post_id' => $post->id,
            'member_id' => $currentMemberId,
            'parent_id' => null,
            'comment' => $commentText,
        ]);

        NotificationHelper::send($post->member, new PostCommentNotification(auth('member')->user(), $post, $commentText), 'post', $post->id, auth('member')->user());

        $comment->load('member');
        $commentsCount = PostComment::query()->where('post_id', $post->id)->whereNull('parent_id')->count();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Comment posted successfully.',
                'comment_id' => $comment->id,
                'comment' => $comment,
                'comments_count' => $commentsCount,
                'html' => view('member.posts.partials.comment-item', [
                    'post' => $post,
                    'comment' => $comment,
                ])->render(),
            ]);
        }

        return back();
    }

    public function comments(Request $request, Post $post)
    {
        $currentMemberId = auth('member')->id();
        abort_unless($this->canMemberAccessPost($currentMemberId, $post), 403);

        $offset = (int) $request->input('offset', 0);
        $limit = (int) $request->input('limit', (int) $request->input('per_page', 4));
        if ($limit <= 0 || $limit > 50) {
            $limit = 4;
        }

        $commentsQuery = $post->comments()
            ->whereNull('parent_id')
            ->with([
                'member',
                'reactions',
                'replies' => fn ($rq) => $rq->with(['member', 'reactions'])->orderBy('created_at', 'asc'),
            ])
            ->withCount('replies')
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit + 1)
            ->get();

        $hasMore = $commentsQuery->count() > $limit;
        $comments = $commentsQuery->take($limit)->reverse()->values();
        $commentsCount = PostComment::query()->where('post_id', $post->id)->whereNull('parent_id')->count();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            $html = '';
            foreach ($comments as $comment) {
                $html .= view('member.posts.partials.comment-item', [
                    'post' => $post,
                    'comment' => $comment,
                ])->render();
            }

            return response()->json([
                'success' => true,
                'post_id' => $post->id,
                'comments_count' => $commentsCount,
                'has_more' => $hasMore,
                'comments' => $comments,
                'html' => $html,
            ]);
        }

        return back();
    }

    public function storeReply(Request $request, PostComment $comment)
    {
        $currentMember = auth('member')->user();
        if (! $currentMember || ! $currentMember->isMobileVerified()) {
            $message = EnsureMemberMobileVerified::UNVERIFIED_MESSAGE;
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 403);
            }

            return back()->with('error', $message);
        }

        $validated = $request->validate([
            'comment' => ['required', 'string', 'min:1', 'max:1000'],
        ]);

        $currentMemberId = auth('member')->id();
        $post = $comment->post;
        abort_unless($this->canMemberAccessPost($currentMemberId, $post), 403);

        $rootParentId = $comment->parent_id ?? $comment->id;

        $reply = PostComment::create([
            'post_id' => $post->id,
            'member_id' => $currentMemberId,
            'parent_id' => $rootParentId,
            'comment' => trim($validated['comment']),
        ]);

        $parentCommentModel = PostComment::find($rootParentId);
        if ($parentCommentModel && $parentCommentModel->member) {
            NotificationHelper::send($parentCommentModel->member, new CommentReplyNotification(auth('member')->user(), $post, $parentCommentModel, trim($validated['comment'])), 'comment', $parentCommentModel->id, auth('member')->user());
        }

        $reply->load(['member', 'reactions']);
        $repliesCount = PostComment::query()->where('parent_id', $rootParentId)->count();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'reply_id' => $reply->id,
                'reply' => $reply,
                'parent_id' => $rootParentId,
                'replies_count' => $repliesCount,
                'html' => view('member.posts.partials.reply-item', [
                    'post' => $post,
                    'parentComment' => PostComment::find($rootParentId),
                    'reply' => $reply,
                ])->render(),
            ]);
        }

        return back();
    }

    public function replies(Request $request, PostComment $comment)
    {
        $currentMemberId = auth('member')->id();
        $post = $comment->post;
        abort_unless($this->canMemberAccessPost($currentMemberId, $post), 403);

        $offset = (int) $request->input('offset', 0);
        $limit = 10;

        $repliesQuery = $comment->replies()
            ->with(['member', 'reactions'])
            ->orderBy('created_at', 'asc')
            ->skip($offset)
            ->take($limit + 1)
            ->get();

        $hasMore = $repliesQuery->count() > $limit;
        $replies = $repliesQuery->take($limit)->values();
        $repliesCount = PostComment::query()->where('parent_id', $comment->id)->count();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            $html = '';
            foreach ($replies as $reply) {
                $html .= view('member.posts.partials.reply-item', [
                    'post' => $post,
                    'parentComment' => $comment,
                    'reply' => $reply,
                ])->render();
            }

            return response()->json([
                'success' => true,
                'parent_id' => $comment->id,
                'replies_count' => $repliesCount,
                'has_more' => $hasMore,
                'replies' => $replies,
                'html' => $html,
            ]);
        }

        return back();
    }

    public function destroyComment(Request $request, PostComment $comment)
    {
        $currentMemberId = auth('member')->id();
        $post = $comment->post;
        $isReply = ! is_null($comment->parent_id);
        $parentId = $comment->parent_id;

        if ($isReply) {
            $parentCommentAuthorId = $comment->parent?->member_id;
            $canDelete = $comment->member_id === $currentMemberId
                || $post->member_id === $currentMemberId
                || ($parentCommentAuthorId && $parentCommentAuthorId === $currentMemberId);
        } else {
            $canDelete = $comment->member_id === $currentMemberId || $post->member_id === $currentMemberId;
        }

        abort_unless($canDelete, 403);

        $comment->delete();

        $commentsCount = PostComment::query()->where('post_id', $post->id)->whereNull('parent_id')->count();
        $repliesCount = $isReply ? PostComment::query()->where('parent_id', $parentId)->count() : 0;

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'comment_id' => $comment->id,
                'post_id' => $post->id,
                'is_reply' => $isReply,
                'parent_id' => $parentId,
                'comments_count' => $commentsCount,
                'replies_count' => $repliesCount,
            ]);
        }

        return back();
    }

    public function reactComment(Request $request, PostComment $comment)
    {
        $validated = $request->validate([
            'reaction' => ['required', 'string', 'in:like,love,haha,wow,sad,angry'],
        ]);

        $currentMemberId = auth('member')->id();
        $post = $comment->post;
        abort_unless($this->canMemberAccessPost($currentMemberId, $post), 403);

        $selectedReaction = $validated['reaction'];

        $existingReaction = CommentReaction::query()
            ->where('comment_id', $comment->id)
            ->where('member_id', $currentMemberId)
            ->first();

        if ($existingReaction && $existingReaction->reaction === $selectedReaction) {
            $existingReaction->delete();
            $userReaction = null;
        } else {
            CommentReaction::updateOrCreate(
                [
                    'comment_id' => $comment->id,
                    'member_id' => $currentMemberId,
                ],
                [
                    'reaction' => $selectedReaction,
                ]
            );
            $userReaction = $selectedReaction;
            NotificationHelper::send($comment->member, new CommentReactionNotification(auth('member')->user(), $post, $comment, $userReaction), 'comment', $comment->id, auth('member')->user());
        }

        $reactionsCount = CommentReaction::query()->where('comment_id', $comment->id)->count();

        $topReactions = CommentReaction::query()
            ->where('comment_id', $comment->id)
            ->selectRaw('reaction, COUNT(*) as total')
            ->groupBy('reaction')
            ->orderByDesc('total')
            ->take(3)
            ->pluck('reaction')
            ->map(fn ($r) => [
                'type' => $r,
                'emoji' => CommentReaction::EMOJI_MAP[$r] ?? '👍',
            ])
            ->values();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'comment_id' => $comment->id,
                'user_reaction' => $userReaction,
                'user_reaction_emoji' => $userReaction ? (CommentReaction::EMOJI_MAP[$userReaction] ?? '👍') : null,
                'user_reaction_label' => $userReaction ? (CommentReaction::LABEL_MAP[$userReaction] ?? 'Like') : 'Like',
                'user_reaction_color' => $userReaction ? (CommentReaction::COLOR_MAP[$userReaction] ?? '#2563eb') : '#64748b',
                'reactions_count' => $reactionsCount,
                'top_reactions' => $topReactions,
            ]);
        }

        return back();
    }

    public function commentReactors(Request $request, PostComment $comment)
    {
        $currentMemberId = auth('member')->id();
        $post = $comment->post;
        abort_unless($this->canMemberAccessPost($currentMemberId, $post), 403);

        $reactions = $comment->reactions()
            ->with('member')
            ->latest('created_at')
            ->get();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'comment_id' => $comment->id,
                'reactions_count' => $reactions->count(),
                'reactors' => $reactions,
                'html' => view('member.posts.partials.comment-reactors-modal', [
                    'comment' => $comment,
                    'reactions' => $reactions,
                ])->render(),
            ]);
        }

        return back();
    }

    public function updateComment(Request $request, PostComment $comment)
    {
        $validated = $request->validate([
            'comment' => ['required', 'string', 'min:1', 'max:1000'],
        ]);

        $currentMemberId = auth('member')->id();

        abort_unless($comment->member_id === $currentMemberId, 403);

        $commentText = trim($validated['comment']);

        $comment->update([
            'comment' => $commentText,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'comment_id' => $comment->id,
                'comment_text' => $comment->comment,
                'is_edited' => true,
                'formatted_time' => $comment->created_at->diffForHumans(),
            ]);
        }

        return back();
    }

    public function sharePost(Request $request, Post $post)
    {
        $validated = $request->validate([
            'share_message' => ['nullable', 'string', 'max:1000'],
        ]);

        $currentMemberId = auth('member')->id();
        $originalPost = $post->original_post_id
            ? ($post->originalPost ?? Post::find($post->original_post_id) ?? $post)
            : $post;
        abort_unless($this->canMemberAccessPost($currentMemberId, $originalPost), 403);

        $shareMessage = filled($validated['share_message'] ?? null)
            ? trim($validated['share_message'])
            : null;

        $newPost = Post::create([
            'member_id' => $currentMemberId,
            'original_post_id' => $originalPost->id,
            'body' => $shareMessage,
        ]);

        PostShare::create([
            'original_post_id' => $originalPost->id,
            'shared_post_id' => $newPost->id,
            'shared_by' => $currentMemberId,
            'share_message' => $shareMessage,
        ]);

        try {
            if ($originalPost->member && $originalPost->member->id !== $currentMemberId) {
                NotificationHelper::send(
                    $originalPost->member,
                    new PostShareNotification(auth('member')->user(), $originalPost),
                    'post',
                    $originalPost->id,
                    auth('member')->user()
                );
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed sending PostShareNotification: ' . $e->getMessage());
        }

        $sharesCount = PostShare::query()->where('original_post_id', $originalPost->id)->count();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            $newPost->load([
                'member',
                'originalPost' => function ($q) {
                    $q->with(['member', 'businessPage'])->withCount([
                        'likes',
                        'reactions',
                        'shares',
                        'savedPosts',
                        'comments' => fn ($query) => $query->whereNull('parent_id'),
                    ]);
                },
                'likes',
                'reactions',
                'comments',
            ])->loadCount([
                'likes',
                'reactions',
                'shares',
                'savedPosts',
                'comments' => fn ($query) => $query->whereNull('parent_id'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Post shared successfully.',
                'post' => $newPost,
                'original_post_id' => $originalPost->id,
                'shared_post_id' => $newPost->id,
                'shares_count' => $sharesCount,
                'html' => view('member.posts.partials.card', ['post' => $newPost])->render(),
            ]);
        }

        return back();
    }

    public function sendToFriends(Request $request, Post $post)
    {
        $validated = $request->validate([
            'friend_ids' => ['required', 'array', 'min:1'],
            'friend_ids.*' => ['required', 'integer', 'exists:members,id'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $currentMember = auth('member')->user();
        $targetPost = $post->originalPost ?? $post;

        $isAuthor = $post->member_id === $currentMember->id || $targetPost->member_id === $currentMember->id;
        $isFriend = Friendship::query()->between($currentMember->id, $targetPost->member_id)->accepted()->exists();
        $canView = $isAuthor || $isFriend || ($targetPost->community_id === null && $targetPost->group_id === null);

        abort_unless($canView, 403);

        $validFriendIds = Friendship::query()
            ->forMember($currentMember->id)
            ->accepted()
            ->get()
            ->map(fn ($f) => $f->otherMember($currentMember->id)->id)
            ->toArray();

        $selectedFriends = array_intersect($validated['friend_ids'], $validFriendIds);

        if (empty($selectedFriends)) {
            return response()->json([
                'success' => false,
                'message' => 'Please select valid accepted connections.',
            ], 422);
        }

        $note = filled($validated['message'] ?? null) ? trim($validated['message']) : null;

        foreach ($selectedFriends as $friendId) {
            $friend = Member::find($friendId);
            if ($friend) {
                NotificationHelper::send(
                    $friend,
                    new \App\Notifications\SendPostToFriendNotification($currentMember, $targetPost, $note),
                    'post',
                    $targetPost->id,
                    $currentMember
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Post sent successfully.',
        ]);
    }

    public function postSharers(Request $request, Post $post)
    {
        $currentMemberId = auth('member')->id();
        $targetPost = $post->original_post_id
            ? ($post->originalPost ?? Post::find($post->original_post_id) ?? $post)
            : $post;
        abort_unless($this->canMemberAccessPost($currentMemberId, $targetPost), 403);

        $shares = PostShare::query()
            ->where('original_post_id', $targetPost->id)
            ->with(['member' => function ($q) {
                $q->select('id', 'name', 'user_id', 'profile_photo');
            }])
            ->latest('created_at')
            ->get();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'post_id' => $targetPost->id,
                'shares_count' => $shares->count(),
                'sharers' => $shares,
                'html' => view('member.posts.partials.post-sharers-modal', [
                    'post' => $targetPost,
                    'shares' => $shares,
                ])->render(),
            ]);
        }

        return back();
    }

    public function toggleSave(Request $request, Post $post)
    {
        $currentMemberId = auth('member')->id();

        $savedRecord = SavedPost::query()
            ->where('member_id', $currentMemberId)
            ->where('post_id', $post->id)
            ->first();

        if ($savedRecord) {
            $savedRecord->delete();
            $isSaved = false;
        } else {
            SavedPost::create([
                'member_id' => $currentMemberId,
                'post_id' => $post->id,
            ]);
            $isSaved = true;
        }

        if ($request->expectsJson() || $request->ajax()) {
            $savesCount = SavedPost::query()
                ->where('post_id', $post->id)
                ->count();

            return response()->json([
                'success' => true,
                'post_id' => $post->id,
                'is_saved' => $isSaved,
                'saves_count' => $savesCount,
            ]);
        }

        return back();
    }

    public function hidePost(Request $request, Post $post)
    {
        $currentMemberId = auth('member')->id();

        HiddenPost::firstOrCreate([
            'member_id' => $currentMemberId,
            'post_id' => $post->id,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'post_id' => $post->id,
            ]);
        }

        return back();
    }

    public function reportPost(Request $request, $post)
    {
        $targetPost = null;

        if ($post instanceof Post) {
            $targetPost = $post;
        } elseif (is_numeric($post)) {
            $targetPost = Post::find((int) $post);
        } elseif (is_string($post)) {
            $targetPost = $this->resolveUnderlyingPostFromSyntheticId($post);
        }

        if (! $targetPost) {
            $message = is_string($post) && str_starts_with($post, 'event_')
                ? 'This event content cannot be reported as a post.'
                : 'Post not found.';

            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], is_string($post) && ! is_numeric($post) ? 422 : 404);
            }

            abort(is_string($post) && ! is_numeric($post) ? 422 : 404, $message);
        }

        $post = $targetPost;

        $validated = $request->validate([
            'reason' => ['required', 'string', 'in:Spam,Fake News,Harassment,Violence,Adult Content,Hate Speech,Other'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $currentMemberId = auth('member')->id();

        $report = ReportedPost::updateOrCreate(
            [
                'member_id' => $currentMemberId,
                'post_id' => $post->id,
            ],
            [
                'reason' => $validated['reason'],
                'description' => filled($validated['description'] ?? null) ? trim($validated['description']) : null,
                'status' => 'pending',
            ]
        );

        $reporterName = auth('member')->user()?->name ?? 'A member';
        \App\Services\AdminNotificationService::notify(
            title: 'Post Flagged for Review',
            message: sprintf('Post #%d was reported for %s by %s.', $post->id, $validated['reason'], $reporterName),
            icon: 'alert-circle',
            sourceType: 'post_report',
            sourceId: (string) $report->id,
            actionUrl: '/admin/reports/post/' . $report->id,
            metadata: ['post_id' => $post->id, 'reason' => $validated['reason']]
        );

        // Dispatch WhatsApp notification asynchronously (additive side-effect, never blocks or fails report)
        try {
            \App\Jobs\SendReportToWhatsAppJob::dispatch($report->id);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error(sprintf(
                'Failed to dispatch WhatsApp report notification for Report #%d: %s',
                $report->id,
                $e->getMessage()
            ));
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'post_id' => $post->id,
                'message' => 'Thank you. We have received your report and will review it.',
            ]);
        }

        return back();
    }

    public function savedPosts(Request $request)
    {
        $currentMember = auth('member')->user();

        $savedPostIds = SavedPost::query()
            ->where('member_id', $currentMember->id)
            ->latest('created_at')
            ->pluck('post_id');

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
            ->withCount([
                'likes',
                'reactions',
                'shares',
                'savedPosts',
                'comments' => fn ($query) => $query->whereNull('parent_id'),
            ])
            ->whereIn('id', $savedPostIds)
            ->paginate(10)
            ->withQueryString();

        if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
            return response()->json([
                'success' => true,
                'posts' => $posts->items(),
                'has_more' => $posts->hasMorePages(),
                'current_page' => $posts->currentPage(),
                'next_page' => $posts->currentPage() + 1,
                'total' => $posts->total(),
            ]);
        }

        $hasProfilePhoto = $currentMember->profile_photo
            && str_starts_with($currentMember->profile_photo, 'uploads/profile/')
            && ! str_contains($currentMember->profile_photo, '..')
            && $currentMember->profile_photo === 'uploads/profile/'.basename($currentMember->profile_photo)
            && file_exists(public_path($currentMember->profile_photo));
        $composerAvatar = $hasProfilePhoto
            ? asset($currentMember->profile_photo).'?v='.($currentMember->updated_at?->timestamp ?? now()->timestamp)
            : asset('member_assets/images/dashboard/image/profile.png');

        return view('member.posts.saved', compact('currentMember', 'posts', 'composerAvatar'));
    }

    public function checkNewPosts(Request $request)
    {
        $latestId = (int) $request->query('latest_id', 0);
        if ($latestId <= 0) {
            return response()->json(['success' => true, 'has_new' => false, 'count' => 0]);
        }

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

        $hiddenPostIds = HiddenPost::query()
            ->where('member_id', $currentMember->id)
            ->pluck('post_id');

        $newCount = Post::query()
            ->whereIn('member_id', $feedMemberIds)
            ->whereNotIn('id', $hiddenPostIds)
            ->where('id', '>', $latestId)
            ->count();

        return response()->json([
            'success' => true,
            'has_new' => $newCount > 0,
            'count' => $newCount,
        ]);
    }

    public function togglePin(Request $request, Post $post)
    {
        $currentMemberId = auth('member')->id();
        abort_unless($post->member_id === $currentMemberId, 403);

        $isPinned = ! $post->is_pinned;

        if ($isPinned) {
            Post::query()
                ->where('member_id', $currentMemberId)
                ->where('is_pinned', true)
                ->update(['is_pinned' => false]);
        }

        $post->update(['is_pinned' => $isPinned]);

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'is_pinned' => $isPinned,
                'message' => $isPinned ? 'Post pinned to profile top.' : 'Post unpinned.',
            ]);
        }

        return back()->with('success', $isPinned ? 'Post pinned to profile top.' : 'Post unpinned.');
    }

    public function destroy(Request $request, Post $post)
    {
        $currentMemberId = auth('member')->id();
        abort_unless($post->member_id === $currentMemberId, 403);

        if ($post->media_path && file_exists(public_path($post->media_path))) {
            File::delete(public_path($post->media_path));
        }

        $post->delete();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Post deleted successfully.',
                'post_id' => $post->id,
            ]);
        }

        return back()->with('success', 'Post deleted successfully.');
    }

    /**
     * Resolve a real underlying Post model from a synthetic campaign or event identifier.
     */
    protected function resolveUnderlyingPostFromSyntheticId(string $syntheticId): ?Post
    {
        // 1. Pattern: event_campaign_{id}
        if (preg_match('/^event_campaign_(\d+)$/', $syntheticId, $matches)) {
            $campaignId = (int) $matches[1];
            $campaign = \App\Models\AdCampaign::find($campaignId);
            if ($campaign) {
                if ($campaign->post_id) {
                    return Post::find($campaign->post_id);
                }
                if ($campaign->event_id) {
                    return Post::where('event_id', $campaign->event_id)->first();
                }
            }
            return null;
        }

        // 2. Pattern: business_campaign_{id} or campaign_{id}
        if (preg_match('/^(?:business_)?campaign_(\d+)$/', $syntheticId, $matches)) {
            $campaignId = (int) $matches[1];
            $campaign = \App\Models\AdCampaign::find($campaignId);
            if ($campaign && $campaign->post_id) {
                return Post::find($campaign->post_id);
            }
            return null;
        }

        // 3. Pattern: event_{id}
        if (preg_match('/^event_(\d+)$/', $syntheticId, $matches)) {
            $eventId = (int) $matches[1];
            return Post::where('event_id', $eventId)->first();
        }

        return null;
    }
}

