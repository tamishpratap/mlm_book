<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Http\Middleware\EnsureMemberMobileVerified;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CommunityPostController extends Controller
{
    public function store(Request $request, Community $community)
    {
        $member = auth('member')->user();

        if (! $member || ! $member->isMobileVerified()) {
            return response()->json([
                'success' => false,
                'message' => EnsureMemberMobileVerified::UNVERIFIED_MESSAGE,
            ], 403);
        }

        if (! $community->isMember($member->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Only active members can publish posts in this community.',
            ], 403);
        }

        if ($community->isBanned($member->id)) {
            return response()->json(['success' => false, 'message' => 'You are banned from posting in this community.'], 403);
        }

        if ($community->isMuted($member->id)) {
            return response()->json(['success' => false, 'message' => 'You are currently muted in this community.'], 403);
        }

        if (! $community->canPost($member->id)) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to publish posts in this community.'], 403);
        }

        if ($request->hasFile('media')) {
            $uploadedMedia = $request->file('media');
            $mime = (string) $uploadedMedia->getMimeType();
            $ext = strtolower($uploadedMedia->getClientOriginalExtension() ?: '');
            if (str_starts_with($mime, 'video/') || in_array($ext, ['mp4', 'webm', 'mov', 'm4v', 'avi', 'mkv', 'flv', 'wmv'], true) || in_array($mime, ['video/mp4', 'video/webm', 'video/quicktime'], true)) {
                throw ValidationException::withMessages([
                    'media' => 'Videos can only be posted from a Business Page.',
                ]);
            }
        }

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:5000', 'required_without:media'],
            'media' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'required_without:body'],
            'is_announcement' => ['nullable', 'boolean'],
        ]);

        $media = $request->file('media');
        $body = filled($validated['body'] ?? null) ? trim($validated['body']) : null;
        $mediaType = null;
        $mediaPath = null;

        if ($media) {
            $mime = $media->getMimeType();
            if (str_starts_with($mime, 'image/')) {
                $mediaType = 'image';
                $directory = 'uploads/posts/images';
            } else {
                return response()->json(['success' => false, 'message' => 'Invalid media file type.'], 422);
            }

            $extension = strtolower($media->getClientOriginalExtension() ?: 'jpg');
            $filename = sprintf('post_%d_%d_%s.%s', $member->id, time(), Str::random(8), $extension);

            $mediaPath = app(\App\Http\Controllers\ImageCompressionController::class)->compressAndStore(
                $media,
                $directory,
                'post',
                $filename,
                'public_uploads'
            );
        }

        $isAnnouncement = ! empty($validated['is_announcement']) && $community->isAdmin($member->id);

        $post = Post::create([
            'member_id' => $member->id,
            'community_id' => $community->id,
            'body' => $body,
            'media_type' => $mediaType,
            'media_path' => $mediaPath,
            'is_announcement' => $isAnnouncement,
            'is_pinned' => false,
        ]);

        $community->increment('post_count');
        $post->load(['member', 'comments.member', 'likes', 'reactions']);

        $html = view('member.posts.partials.card', compact('post'))->render();

        return response()->json([
            'success' => true,
            'message' => 'Post published to ' . $community->name . '!',
            'post' => $post,
            'post_id' => $post->id,
            'html' => $html,
            'post_count' => $community->fresh()->post_count,
        ]);
    }

    public function togglePin(Request $request, Community $community, Post $post)
    {
        $member = auth('member')->user();

        if (! $community->isAdmin($member->id)) {
            return response()->json(['success' => false, 'message' => 'Only community admins can pin posts.'], 403);
        }

        if ((int) $post->community_id !== (int) $community->id) {
            return response()->json(['success' => false, 'message' => 'Post does not belong to this community.'], 400);
        }

        $newPinnedState = ! $post->is_pinned;

        if ($newPinnedState) {
            // Unpin any existing pinned post in this community
            Post::where('community_id', $community->id)
                ->where('is_pinned', true)
                ->update(['is_pinned' => false]);
        }

        $post->update(['is_pinned' => $newPinnedState]);

        return response()->json([
            'success' => true,
            'message' => $newPinnedState ? 'Post pinned to community top!' : 'Post unpinned.',
            'is_pinned' => $newPinnedState,
        ]);
    }

    public function toggleAnnouncement(Request $request, Community $community, Post $post)
    {
        $member = auth('member')->user();

        if (! $community->isAdmin($member->id)) {
            return response()->json(['success' => false, 'message' => 'Only community admins can toggle announcements.'], 403);
        }

        if ((int) $post->community_id !== (int) $community->id) {
            return response()->json(['success' => false, 'message' => 'Post does not belong to this community.'], 400);
        }

        $newAnnouncementState = ! $post->is_announcement;
        $post->update(['is_announcement' => $newAnnouncementState]);

        return response()->json([
            'success' => true,
            'message' => $newAnnouncementState ? 'Post marked as Community Announcement!' : 'Announcement badge removed.',
            'is_announcement' => $newAnnouncementState,
        ]);
    }

    public function destroy(Request $request, Community $community, Post $post)
    {
        $member = auth('member')->user();

        if ((int) $post->community_id !== (int) $community->id) {
            return response()->json(['success' => false, 'message' => 'Post does not belong to this community.'], 400);
        }

        $canDelete = (int) $post->member_id === (int) $member->id || $community->isModerator($member->id);

        if (! $canDelete) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to delete this post.'], 403);
        }

        if ($post->media_path && File::exists(public_path($post->media_path))) {
            File::delete(public_path($post->media_path));
        }

        $post->delete();
        $community->decrement('post_count');

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully.',
            'post_count' => max(0, $community->fresh()->post_count),
        ]);
    }
}
