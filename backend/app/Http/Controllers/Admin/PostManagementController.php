<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\ReportedPost;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PostManagementController extends Controller
{
    /**
     * Display a listing of posts with search, media filtering, report filtering, and pagination.
     */
    /**
     * Build filtered query for posts.
     */
    protected function buildFilteredQuery(Request $request)
    {
        $search = trim((string) $request->input('q'));
        $mediaType = $request->input('media_type');
        $hasReports = $request->input('has_reports');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = Post::query();

        // Search Filter (Post ID, Author Name, User ID, Caption/Body, Community/Business Name, Original Author)
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                  ->orWhere('body', 'like', "%{$search}%")
                  ->orWhereHas('member', function ($mq) use ($search) {
                      $mq->where('name', 'like', "%{$search}%")
                         ->orWhere('user_id', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('originalPost', function ($oq) use ($search) {
                      $oq->where('body', 'like', "%{$search}%")
                         ->orWhereHas('member', function ($omq) use ($search) {
                              $omq->where('name', 'like', "%{$search}%")
                                 ->orWhere('user_id', 'like', "%{$search}%");
                         });
                  })
                  ->orWhereHas('community', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('businessPage', function ($bq) use ($search) {
                      $bq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Media Type Filter (Supports both direct post media and reshared original media)
        if ($mediaType === 'image') {
            $query->where(function ($q) {
                $q->where('media_type', 'image')
                  ->orWhere(function ($sq) {
                      $sq->whereNotNull('original_post_id')
                         ->whereHas('originalPost', function ($oq) {
                             $oq->where('media_type', 'image');
                         });
                  });
            });
        } elseif ($mediaType === 'video') {
            $query->where(function ($q) {
                $q->where('media_type', 'video')
                  ->orWhere(function ($sq) {
                      $sq->whereNotNull('original_post_id')
                         ->whereHas('originalPost', function ($oq) {
                             $oq->where('media_type', 'video');
                         });
                  });
            });
        } elseif ($mediaType === 'text') {
            $query->where(function ($q) {
                $q->where(function ($tq) {
                    $tq->whereNull('media_type')->orWhere('media_type', 'none')->orWhere('media_type', '');
                })->where(function ($tq) {
                    $tq->whereNull('original_post_id')
                       ->orWhereHas('originalPost', function ($oq) {
                           $oq->whereNull('media_type')->orWhere('media_type', 'none')->orWhere('media_type', '');
                       });
                });
            });
        }

        // Reported Posts Filter
        if ($hasReports === 'yes') {
            $query->has('reports');
        } elseif ($hasReports === 'no') {
            $query->doesntHave('reports');
        }

        // Date Range Filter
        if (!empty($dateFrom)) {
            $startDate = Carbon::parse($dateFrom)->startOfDay();
            $endDate = !empty($dateTo) ? Carbon::parse($dateTo)->endOfDay() : Carbon::parse($dateFrom)->endOfDay();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } elseif (!empty($dateTo)) {
            $query->where('created_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        return $query;
    }

    public function index(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to'   => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
        ]);

        $query = $this->buildFilteredQuery($request)
            ->with(['member', 'community', 'businessPage', 'originalPost.member', 'hiddenPosts'])
            ->withCount(['likes', 'comments', 'shares', 'reports']);

        $posts = $query->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        // Metrics Summary (Direct + Reshared)
        $totalCount = Post::count();
        $imageCount = Post::where(function ($q) {
            $q->where('media_type', 'image')
              ->orWhere(function ($sq) {
                  $sq->whereNotNull('original_post_id')
                     ->whereHas('originalPost', fn($oq) => $oq->where('media_type', 'image'));
              });
        })->count();
        $videoCount = Post::where(function ($q) {
            $q->where('media_type', 'video')
              ->orWhere(function ($sq) {
                  $sq->whereNotNull('original_post_id')
                     ->whereHas('originalPost', fn($oq) => $oq->where('media_type', 'video'));
              });
        })->count();
        $reportedCount = Post::has('reports')->count();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'posts' => $posts,
                'totalCount' => $totalCount,
                'imageCount' => $imageCount,
                'videoCount' => $videoCount,
                'reportedCount' => $reportedCount,
            ]);
        }

        return view('admin.posts.index', compact(
            'posts',
            'search',
            'mediaType',
            'hasReports',
            'dateFrom',
            'dateTo',
            'totalCount',
            'imageCount',
            'videoCount',
            'reportedCount'
        ));
    }

    /**
     * Display detailed read-only inspection of a post, including comments, reactions, and reports.
     */
    public function show(Post $post)
    {
        $post->load([
            'member',
            'community',
            'businessPage',
            'originalPost.member',
            'hiddenPosts',
            'reports.member',
            'comments' => function ($q) {
                $q->whereNull('parent_id')->with(['member', 'replies.member'])->latest();
            }
        ])->loadCount(['likes', 'comments', 'shares', 'reports']);

        $topReactions = $post->topReactions();

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'post' => $post,
                'topReactions' => $topReactions,
            ]);
        }

        return view('admin.posts.show', compact('post', 'topReactions'));
    }

    /**
     * Update report status (e.g. mark reviewed or dismissed).
     */
    public function updateReportStatus(Request $request, ReportedPost $report)
    {
        $status = $request->input('status', 'reviewed');

        $report->update(['status' => $status]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Report #{$report->id} has been marked as {$status}.",
            ]);
        }

        return redirect()->back()->with('success', "Report #{$report->id} has been marked as {$status}.");
    }

    /**
     * Update a post content / body and attached image/media.
     */
    public function update(Request $request, Post $post)
    {
        $validated = $request->validate([
            'body' => 'nullable|string',
            'media' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,mp4,mov,avi|max:61440',
            'remove_media' => 'nullable|boolean',
            'remove_attachment' => 'nullable|boolean',
        ]);

        $updateData = [
            'body' => $validated['body'] ?? null,
        ];

        $oldMediaPath = $post->media_path;
        $shouldDeleteOldMedia = false;

        // Handle Media Upload / Replacement (Replacement takes precedence over removal)
        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $mime = $file->getMimeType();
            $isVideo = str_starts_with($mime, 'video/');
            $mediaType = $isVideo ? 'video' : 'image';
            $directory = $isVideo ? 'uploads/posts/videos' : 'uploads/posts/images';

            $extension = $file->getClientOriginalExtension() ?: ($isVideo ? 'mp4' : 'jpg');
            $filename = sprintf(
                'post_%d_%d_%s.%s',
                $post->member_id,
                time(),
                \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8)),
                $extension
            );

            if ($isVideo) {
                $newMediaPath = app(\App\Http\Controllers\VideoCompressionController::class)->stageAndStore(
                    $file,
                    $directory,
                    $filename
                );
            } else {
                try {
                    $newMediaPath = app(\App\Http\Controllers\ImageCompressionController::class)->compressAndStore(
                        $file,
                        $directory,
                        'post',
                        $filename,
                        'public_uploads'
                    );
                } catch (\Illuminate\Validation\ValidationException $e) {
                    throw $e;
                } catch (\Throwable $e) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'media' => ['Failed to process or store the post image.'],
                    ]);
                }

                if (!$newMediaPath) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'media' => ['Failed to process or store the post image.'],
                    ]);
                }
            }

            $updateData['media_path'] = $newMediaPath;
            $updateData['media_type'] = $mediaType;
            $shouldDeleteOldMedia = true;
        } elseif ($request->boolean('remove_media') || $request->boolean('remove_attachment')) {
            // Remove media attachment
            $updateData['media_path'] = null;
            $updateData['media_type'] = null;
            $shouldDeleteOldMedia = true;
        }

        // Perform Database Update First
        $post->update($updateData);

        if ($request->hasFile('media') && ! empty($isVideo) && ! empty($newMediaPath)) {
            app(\App\Http\Controllers\VideoCompressionController::class)->dispatchCompression(
                $post,
                null,
                $newMediaPath,
                $directory,
                $filename
            );
        }

        // ONLY delete old file from disk AFTER database update succeeds
        if ($shouldDeleteOldMedia && $oldMediaPath && \Illuminate\Support\Facades\File::exists(public_path($oldMediaPath))) {
            \Illuminate\Support\Facades\File::delete(public_path($oldMediaPath));
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Post #{$post->id} has been updated successfully.",
                'post' => $post->fresh(['member', 'community', 'businessPage', 'originalPost.member', 'hiddenPosts']),
            ]);
        }

        return redirect()->back()->with('success', "Post #{$post->id} has been updated successfully.");
    }

    /**
     * Remove the media attachment from a post without modifying body or deleting the post.
     */
    public function removeMedia(Request $request, Post $post)
    {
        $oldMediaPath = $post->media_path;

        $post->update([
            'media_path' => null,
            'media_type' => null,
        ]);

        if ($oldMediaPath && \Illuminate\Support\Facades\File::exists(public_path($oldMediaPath))) {
            \Illuminate\Support\Facades\File::delete(public_path($oldMediaPath));
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Media attachment removed from Post #{$post->id}.",
                'post' => $post->fresh(['member', 'community', 'businessPage', 'originalPost.member', 'hiddenPosts']),
            ]);
        }

        return redirect()->back()->with('success', "Media attachment removed from Post #{$post->id}.");
    }

    /**
     * Toggle hide/block state of a post.
     */
    public function toggleHide(Request $request, Post $post)
    {
        $hiddenRecord = \App\Models\HiddenPost::where('post_id', $post->id)->where('member_id', $post->member_id)->first();

        if ($hiddenRecord) {
            $hiddenRecord->delete();
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => "Post #{$post->id} is now unhidden and visible.", 'hidden' => false]);
            }
            return redirect()->back()->with('success', "Post #{$post->id} is now unhidden and visible.");
        }

        \App\Models\HiddenPost::firstOrCreate([
            'member_id' => $post->member_id,
            'post_id' => $post->id,
        ]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => true, 'message' => "Post #{$post->id} has been blocked and hidden from public feeds.", 'hidden' => true]);
        }

        return redirect()->back()->with('warning', "Post #{$post->id} has been blocked and hidden from public feeds.");
    }

    /**
     * Delete a post.
     */
    public function destroy(Post $post)
    {
        $postId = $post->id;
        $authorName = $post->member?->name ?? 'Unknown Member';

        $post->delete();

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Post #{$postId} by {$authorName} has been deleted.",
            ]);
        }

        return redirect()->back()->with('success', "Post #{$postId} by {$authorName} has been deleted.");
    }

    /**
     * Process bulk actions on selected posts.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:delete',
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:posts,id'
        ]);

        $action = $request->input('action');
        $ids = $request->input('ids');

        if ($action === 'delete') {
            Post::whereIn('id', $ids)->delete();
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => count($ids) . ' posts have been deleted.']);
            }
            return redirect()->back()->with('success', count($ids) . ' posts have been deleted.');
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => false, 'message' => 'Invalid bulk action.'], 422);
        }

        return redirect()->back()->with('error', 'Invalid bulk action.');
    }

    /**
     * Export posts list to CSV stream.
     */
    public function export(Request $request)
    {
        $query = $this->buildFilteredQuery($request)
            ->with(['member', 'community', 'businessPage'])
            ->withCount(['likes', 'comments', 'shares', 'reports'])
            ->latest('created_at');

        $filename = 'posts_export_' . date('Y_m_d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, ['Post ID', 'Author Name', 'Author User ID', 'Content', 'Media Type', 'Context', 'Likes', 'Comments', 'Shares', 'Reports', 'Created Date']);

            $query->chunk(200, function ($posts) use ($file) {
                foreach ($posts as $p) {
                    $context = $p->community ? 'Community: ' . $p->community->name : ($p->businessPage ? 'Page: ' . $p->businessPage->name : 'Personal Feed');

                    fputcsv($file, [
                        $p->id,
                        $p->member?->name ?? 'N/A',
                        $p->member?->user_id ?? 'N/A',
                        $p->body ?? '',
                        $p->media_type ?: 'Text',
                        $context,
                        $p->likes_count,
                        $p->comments_count,
                        $p->shares_count,
                        $p->reports_count,
                        $p->created_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
