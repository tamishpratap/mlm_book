<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CommentReaction;
use App\Models\Member;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostLike;
use App\Models\PostReaction;
use App\Models\PostShare;
use App\Models\Story;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MobileFeedController extends Controller
{
    /**
     * Get paginated social feed posts for mobile app.
     */
    public function feed(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $query = Post::query()
            ->with(['member:id,name,user_id,profile_photo,mobile_verified_at', 'comments.member:id,name,user_id,profile_photo'])
            ->withCount(['likes', 'comments'])
            ->latest();

        $posts = $query->paginate(15);

        $data = $posts->getCollection()->map(function (Post $post) use ($member) {
            $isLiked = $member ? PostLike::where('post_id', $post->id)->where('member_id', $member->id)->exists() : false;
            $userReaction = $member ? PostReaction::where('post_id', $post->id)->where('member_id', $member->id)->value('reaction') : null;
            $isSaved = $member ? DB::table('saved_posts')->where('post_id', $post->id)->where('member_id', $member->id)->exists() : false;

            $sharesCount = DB::table('post_shares')->where('original_post_id', $post->id)->count();
            $savesCount = DB::table('saved_posts')->where('post_id', $post->id)->count();

            return [
                'id' => $post->id,
                'body' => $post->body,
                'media_type' => $post->media_type,
                'media_url' => $post->media_url,
                'created_at' => $post->created_at?->diffForHumans() ?? 'Just now',
                'likes_count' => (int) $post->likes_count,
                'comments_count' => (int) $post->comments_count,
                'shares_count' => $sharesCount,
                'saves_count' => $savesCount,
                'is_liked' => $isLiked,
                'is_saved' => $isSaved,
                'user_reaction' => $userReaction,
                'comments' => $post->comments->take(5)->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'comment' => $c->comment,
                        'created_at' => $c->created_at?->diffForHumans() ?? 'Just now',
                        'author' => [
                            'id' => $c->member?->id ?? 0,
                            'name' => $c->member?->name ?? 'User',
                            'avatar_url' => $c->member?->avatar_url,
                        ],
                    ];
                })->values()->all(),
                'author' => [
                    'id' => $post->member?->id,
                    'name' => $post->member?->name ?? 'Unknown',
                    'user_id' => $post->member?->user_id ?? '',
                    'avatar_url' => $post->member?->avatar_url,
                    'is_verified' => $post->member?->isMobileVerified() ?? false,
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'posts' => $data,
            'has_more' => $posts->hasMorePages(),
            'current_page' => $posts->currentPage(),
        ]);
    }

    /**
     * Create a new post from mobile app.
     */
    public function storePost(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'media' => ['nullable', 'file', 'max:61440'], // 60MB max
            'media_url' => ['nullable', 'string'],
            'media_type' => ['nullable', 'string', 'in:image,video'],
        ]);

        $mediaPath = null;
        $mediaType = null;

        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $mime = $file->getMimeType();

            if (str_starts_with($mime, 'image/')) {
                $mediaType = 'image';
                $dest = public_path('uploads/posts/images');
            } elseif (str_starts_with($mime, 'video/')) {
                $mediaType = 'video';
                $dest = public_path('uploads/posts/videos');
            } else {
                return response()->json(['message' => 'Invalid media format. Only images and videos are supported.'], 422);
            }

            if (!File::isDirectory($dest)) {
                File::makeDirectory($dest, 0755, true, true);
            }

            $filename = 'post_' . $member->id . '_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $file->move($dest, $filename);
            $mediaPath = ($mediaType === 'image' ? 'uploads/posts/images/' : 'uploads/posts/videos/') . $filename;
        } elseif (!empty($validated['media_url'])) {
            $mediaPath = trim($validated['media_url']);
            $mediaType = $validated['media_type'] ?? 'image';
        }

        $post = Post::create([
            'member_id' => $member->id,
            'body' => $validated['body'] ?? null,
            'media_type' => $mediaType,
            'media_path' => $mediaPath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Post published successfully.',
            'post' => [
                'id' => $post->id,
                'body' => $post->body,
                'media_type' => $post->media_type,
                'media_url' => $post->media_url,
                'created_at' => 'Just now',
                'likes_count' => 0,
                'comments_count' => 0,
                'shares_count' => 0,
                'saves_count' => 0,
                'is_liked' => false,
                'is_saved' => false,
                'user_reaction' => null,
                'author' => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'user_id' => $member->user_id,
                    'avatar_url' => $member->avatar_url,
                    'is_verified' => $member->isMobileVerified(),
                ],
            ],
        ], 201);
    }

    /**
     * React to / like a post.
     */
    public function reactPost(Request $request, int $postId): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();
        $type = $request->input('reaction', $request->input('type', 'like'));
        if (!in_array($type, ['like', 'love', 'haha', 'wow', 'sad', 'angry'])) {
            $type = 'like';
        }

        $post = Post::findOrFail($postId);

        $existing = PostReaction::where('post_id', $post->id)->where('member_id', $member->id)->first();
        if ($existing) {
            if ($existing->reaction === $type) {
                $existing->delete();
                PostLike::where('post_id', $post->id)->where('member_id', $member->id)->delete();
                $reacted = false;
                $userReaction = null;
            } else {
                $existing->update(['reaction' => $type]);
                $reacted = true;
                $userReaction = $type;
            }
        } else {
            PostReaction::create([
                'post_id' => $post->id,
                'member_id' => $member->id,
                'reaction' => $type,
            ]);
            PostLike::firstOrCreate(['post_id' => $post->id, 'member_id' => $member->id]);
            $reacted = true;
            $userReaction = $type;
        }

        $likesCount = PostLike::where('post_id', $post->id)->count();

        return response()->json([
            'success' => true,
            'reacted' => $reacted,
            'liked' => $reacted,
            'user_reaction' => $userReaction,
            'likes_count' => $likesCount,
        ]);
    }

    /**
     * Get reactors list for a post.
     */
    public function getReactors(Request $request, int $postId): JsonResponse
    {
        $post = Post::findOrFail($postId);
        $reactions = PostReaction::query()
            ->where('post_id', $post->id)
            ->with(['member:id,name,user_id,profile_photo'])
            ->latest()
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'reaction' => $r->reaction,
                    'emoji' => PostReaction::EMOJI_MAP[$r->reaction] ?? '👍',
                    'label' => PostReaction::LABEL_MAP[$r->reaction] ?? 'Like',
                    'member' => [
                        'id' => $r->member?->id,
                        'name' => $r->member?->name ?? 'Member',
                        'avatar_url' => $r->member?->avatar_url,
                    ],
                ];
            });

        return response()->json([
            'success' => true,
            'reactors' => $reactions,
            'count' => $reactions->count(),
        ]);
    }

    /**
     * Share a post to member feed.
     */
    public function sharePost(Request $request, int $postId): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $post = Post::findOrFail($postId);
        $originalPostId = $post->original_post_id ?: $post->id;
        $originalPost = Post::findOrFail($originalPostId);

        $shareMessage = trim($request->input('message', ''));

        $sharedPost = Post::create([
            'member_id' => $member->id,
            'original_post_id' => $originalPost->id,
            'body' => $shareMessage ?: null,
            'media_type' => $originalPost->media_type,
            'media_path' => $originalPost->media_path,
        ]);

        PostShare::create([
            'original_post_id' => $originalPost->id,
            'shared_post_id' => $sharedPost->id,
            'shared_by' => $member->id,
            'share_message' => $shareMessage ?: null,
        ]);

        $sharesCount = PostShare::where('original_post_id', $originalPost->id)->count();

        return response()->json([
            'success' => true,
            'message' => 'Post shared successfully to your feed.',
            'shares_count' => $sharesCount,
            'shared_post_id' => $sharedPost->id,
        ]);
    }

    /**
     * Get comments for a post.
     */
    public function getComments(Request $request, int $postId): JsonResponse
    {
        $post = Post::findOrFail($postId);

        $comments = PostComment::query()
            ->where('post_id', $post->id)
            ->whereNull('parent_id')
            ->with(['member:id,name,user_id,profile_photo', 'replies.member:id,name,user_id,profile_photo'])
            ->latest()
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'comment' => $c->comment,
                    'created_at' => $c->created_at?->diffForHumans(),
                    'author' => [
                        'id' => $c->member?->id,
                        'name' => $c->member?->name ?? 'User',
                        'avatar_url' => $c->member?->avatar_url,
                    ],
                    'replies_count' => $c->replies->count(),
                ];
            });

        return response()->json([
            'success' => true,
            'comments' => $comments,
        ]);
    }

    /**
     * Add a comment to a post.
     */
    public function addComment(Request $request, int $postId): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'integer', 'exists:post_comments,id'],
        ]);

        $post = Post::findOrFail($postId);

        $comment = PostComment::create([
            'post_id' => $post->id,
            'member_id' => $member->id,
            'comment' => trim($validated['comment']),
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'comment' => [
                'id' => $comment->id,
                'comment' => $comment->comment,
                'created_at' => 'Just now',
                'author' => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'avatar_url' => $member->avatar_url,
                ],
            ],
        ], 201);
    }

    /**
     * Active 24-hour stories grouped by user.
     */
    public function stories(Request $request): JsonResponse
    {
        $cutoff = now()->subHours(24);

        $stories = Story::query()
            ->where('created_at', '>=', $cutoff)
            ->with(['member:id,name,user_id,profile_photo'])
            ->latest()
            ->get();

        $grouped = $stories->groupBy('member_id')->map(function ($userStories, $memberId) use ($request) {
            $first = $userStories->first();
            $host = $request->getSchemeAndHttpHost();
            return [
                'member_id' => $memberId,
                'author_name' => $first->member?->name ?? 'Member',
                'author_avatar' => $first->member?->avatar_url,
                'stories_count' => $userStories->count(),
                'stories' => $userStories->map(function ($s) use ($host) {
                    $mediaUrl = $s->media_path ? $host . '/' . ltrim($s->media_path, '/') : ($s->media_url ?? asset($s->media_path));
                    return [
                        'id' => $s->id,
                        'media_url' => $mediaUrl,
                        'media_path' => $s->media_path,
                        'media_type' => $s->media_type ?? 'image',
                        'caption' => $s->caption,
                        'created_at' => $s->created_at?->diffForHumans(),
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'stories_groups' => $grouped,
        ]);
    }

    /**
     * Create a story from mobile app.
     */
    public function createStory(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $request->validate([
            'media' => ['required', 'file', 'max:65536'],
            'caption' => ['nullable', 'string', 'max:500'],
        ]);

        $file = $request->file('media');
        $mime = $file->getMimeType();
        $mediaType = str_starts_with($mime, 'video/') ? 'video' : 'image';

        $dest = public_path('uploads/stories/' . ($mediaType === 'video' ? 'videos' : 'images'));
        if (!File::isDirectory($dest)) {
            File::makeDirectory($dest, 0755, true, true);
        }

        $filename = 'story_' . $member->id . '_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $file->move($dest, $filename);
        $mediaPath = 'uploads/stories/' . ($mediaType === 'video' ? 'videos/' : 'images/') . $filename;

        $story = Story::create([
            'member_id' => $member->id,
            'caption' => trim($request->input('caption', '')) ?: null,
            'media_type' => $mediaType,
            'media_path' => $mediaPath,
            'expires_at' => now()->addHours(24),
        ]);

        $host = $request->getSchemeAndHttpHost();
        $mediaUrl = $host . '/' . ltrim($mediaPath, '/');

        return response()->json([
            'success' => true,
            'message' => 'Story shared successfully.',
            'story' => [
                'id' => $story->id,
                'media_type' => $story->media_type,
                'media_url' => $mediaUrl,
                'caption' => $story->caption,
            ],
        ], 201);
    }

    /**
     * Delete story (Owner only).
     */
    public function deleteStory(Request $request, int $id): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $story = Story::findOrFail($id);

        if ((int)$story->member_id !== (int)$member->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this story.',
            ], 403);
        }

        DB::transaction(function () use ($story) {
            $story->views()->delete();
            $story->likes()->delete();
            $story->reactions()->delete();
            $story->replies()->delete();

            if ($story->media_path && File::exists(public_path($story->media_path))) {
                File::delete(public_path($story->media_path));
            }

            $story->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Story deleted successfully.',
            'story_id' => $id,
        ]);
    }

    /**
     * Watch video feed for mobile Watch tab.
     */
    public function watchVideos(Request $request): JsonResponse
    {
        $currentMember = auth('member')->user();
        $filter = $request->query('filter', 'all');

        $eligibleMemberIds = ($currentMember && method_exists($currentMember, 'watchEligibleMemberIds'))
            ? $currentMember->watchEligibleMemberIds()
            : [];

        $query = Post::query()
            ->where('media_type', 'video')
            ->whereNotNull('media_path')
            ->where('media_path', '!=', '')
            ->with([
                'member:id,name,user_id,profile_photo',
                'comments' => fn ($cq) => $cq->whereNull('parent_id')->with('member:id,name,user_id,profile_photo')->latest()->take(3),
            ])
            ->withCount(['likes', 'comments', 'shares', 'savedPosts', 'reactions']);

        if ($filter === 'my_videos') {
            if ($currentMember) {
                $query->where('member_id', $currentMember->id);
            }
            $query->latest();
        } elseif ($filter === 'saved') {
            if ($currentMember) {
                $savedPostIds = DB::table('saved_posts')->where('member_id', $currentMember->id)->pluck('post_id');
                $query->whereIn('id', $savedPostIds);
            }
            $query->latest();
        } elseif ($filter === 'trending') {
            $query->orderByRaw('(likes_count + (reactions_count * 1.5) + (comments_count * 2) + (shares_count * 2)) DESC')
                  ->latest();
        } else {
            // 'all': connection-based if available and has items, else latest
            if ($currentMember && !empty($eligibleMemberIds)) {
                $query->whereIn('member_id', $eligibleMemberIds);
            }
            $query->latest();
        }

        $posts = $query->paginate(10);

        // Fallback: If 'all' connection filter returned empty but global video posts exist, fallback to general latest videos
        if ($filter === 'all' && $posts->isEmpty()) {
            $posts = Post::query()
                ->where('media_type', 'video')
                ->whereNotNull('media_path')
                ->where('media_path', '!=', '')
                ->with([
                    'member:id,name,user_id,profile_photo',
                    'comments' => fn ($cq) => $cq->whereNull('parent_id')->with('member:id,name,user_id,profile_photo')->latest()->take(3),
                ])
                ->withCount(['likes', 'comments', 'shares', 'savedPosts', 'reactions'])
                ->latest()
                ->paginate(10);
        }

        $data = $posts->getCollection()->map(function (Post $p) use ($currentMember) {
            $isLiked = $currentMember ? PostLike::where('post_id', $p->id)->where('member_id', $currentMember->id)->exists() : false;
            $userReaction = $currentMember ? PostReaction::where('post_id', $p->id)->where('member_id', $currentMember->id)->value('reaction') : null;
            $isSaved = $currentMember ? DB::table('saved_posts')->where('post_id', $p->id)->where('member_id', $currentMember->id)->exists() : false;

            $recentComments = $p->comments->map(function ($c) {
                return [
                    'id' => $c->id,
                    'body' => $c->body ?? $c->comment,
                    'comment' => $c->body ?? $c->comment,
                    'created_at' => $c->created_at?->diffForHumans() ?? 'just now',
                    'member' => [
                        'id' => $c->member?->id,
                        'name' => $c->member?->name ?? 'Member',
                        'avatar_url' => $c->member?->avatar_url,
                    ],
                ];
            })->values()->all();

            return [
                'id' => $p->id,
                'title' => $p->body ?? 'Video',
                'body' => $p->body,
                'media_type' => 'video',
                'media_url' => $p->media_url,
                'video_url' => $p->media_url,
                'author' => [
                    'id' => $p->member?->id,
                    'name' => $p->member?->name ?? 'Creator',
                    'user_id' => $p->member?->user_id,
                    'avatar_url' => $p->member?->avatar_url,
                    'is_verified' => (bool) ($p->member?->is_verified ?? false),
                ],
                'likes_count' => (int) $p->likes_count,
                'reactions_count' => (int) ($p->reactions_count ?? $p->likes_count),
                'comments_count' => (int) $p->comments_count,
                'shares_count' => (int) ($p->shares_count ?? 0),
                'saves_count' => (int) ($p->saved_posts_count ?? 0),
                'is_liked' => (bool) $isLiked,
                'is_saved' => (bool) $isSaved,
                'user_reaction' => $userReaction,
                'created_at' => $p->created_at?->diffForHumans() ?? 'just now',
                'recent_comments' => $recentComments,
                'comments' => $recentComments,
            ];
        });

        // Sidebar widgets
        $trendingVideos = Post::query()
            ->where('media_type', 'video')
            ->whereNotNull('media_path')
            ->with(['member:id,name,user_id,profile_photo'])
            ->withCount(['likes', 'comments', 'reactions'])
            ->orderByRaw('(likes_count + reactions_count + comments_count) DESC')
            ->latest()
            ->take(3)
            ->get()
            ->map(fn ($tv) => [
                'id' => $tv->id,
                'body' => $tv->body,
                'media_url' => $tv->media_url,
                'reactions_count' => (int) ($tv->reactions_count ?? $tv->likes_count),
                'likes_count' => (int) $tv->likes_count,
                'created_at' => $tv->created_at?->diffForHumans(),
                'member' => [
                    'id' => $tv->member?->id,
                    'name' => $tv->member?->name ?? 'Creator',
                    'avatar_url' => $tv->member?->avatar_url,
                ],
            ]);

        $recentVideos = Post::query()
            ->where('media_type', 'video')
            ->whereNotNull('media_path')
            ->with(['member:id,name,user_id,profile_photo'])
            ->latest()
            ->take(3)
            ->get()
            ->map(fn ($rv) => [
                'id' => $rv->id,
                'body' => $rv->body,
                'media_url' => $rv->media_url,
                'created_at' => $rv->created_at?->diffForHumans(),
                'member' => [
                    'id' => $rv->member?->id,
                    'name' => $rv->member?->name ?? 'Creator',
                    'avatar_url' => $rv->member?->avatar_url,
                ],
            ]);

        $myVideosCount = $currentMember
            ? Post::where('member_id', $currentMember->id)->where('media_type', 'video')->whereNotNull('media_path')->count()
            : 0;

        $savedVideosCount = $currentMember
            ? DB::table('saved_posts')->where('member_id', $currentMember->id)->count()
            : 0;

        return response()->json([
            'success' => true,
            'videos' => $data,
            'posts' => $data,
            'has_more' => $posts->hasMorePages(),
            'trending_videos' => $trendingVideos,
            'recent_videos' => $recentVideos,
            'suggested_creators' => [],
            'my_videos_count' => $myVideosCount,
            'saved_videos_count' => $savedVideosCount,
            'filter' => $filter,
        ]);
    }

    /**
     * Get member's saved / bookmarked posts.
     */
    public function savedPosts(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $savedPostIds = DB::table('saved_posts')
            ->where('member_id', $member->id)
            ->latest('created_at')
            ->pluck('post_id');

        $posts = Post::query()
            ->whereIn('id', $savedPostIds)
            ->with(['member:id,name,user_id,profile_photo'])
            ->withCount(['likes', 'comments'])
            ->paginate(15);

        $data = $posts->getCollection()->map(function (Post $post) use ($member) {
            $isLiked = $member ? PostLike::where('post_id', $post->id)->where('member_id', $member->id)->exists() : false;

            return [
                'id' => $post->id,
                'body' => $post->body,
                'media_type' => $post->media_type,
                'media_url' => $post->media_url,
                'created_at' => $post->created_at?->diffForHumans() ?? '',
                'likes_count' => (int) $post->likes_count,
                'comments_count' => (int) $post->comments_count,
                'is_liked' => $isLiked,
                'is_saved' => true,
                'author' => [
                    'id' => $post->member?->id ?? 0,
                    'name' => $post->member?->name ?? 'Member',
                    'user_id' => $post->member?->user_id ?? '',
                    'avatar_url' => $post->member?->avatar_url,
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'posts' => $data,
            'has_more' => $posts->hasMorePages(),
        ]);
    }

    /**
     * Toggle bookmark/save on a post.
     */
    public function toggleSavePost(Request $request, int $postId): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $post = Post::findOrFail($postId);

        $existing = DB::table('saved_posts')
            ->where('member_id', $member->id)
            ->where('post_id', $post->id)
            ->first();

        if ($existing) {
            DB::table('saved_posts')
                ->where('member_id', $member->id)
                ->where('post_id', $post->id)
                ->delete();

            return response()->json([
                'success' => true,
                'saved' => false,
                'message' => 'Post removed from saved bookmarks.',
            ]);
        }

        DB::table('saved_posts')->insert([
            'member_id' => $member->id,
            'post_id' => $post->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'saved' => true,
            'message' => 'Post saved to bookmarks.',
        ]);
    }
}
