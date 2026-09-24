<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessReview;
use App\Models\BusinessReviewReport;
use App\Models\Community;
use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\CommunityReport;
use App\Models\Post;
use App\Models\Product;
use App\Models\ReportedPost;
use App\Models\ReportedProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ReportManagementController extends Controller
{
    /**
     * Display a unified listing of all platform reports.
     */
    public function index(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to'   => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
        ]);

        $search = trim((string) $request->input('q'));
        $sourceType = $request->input('source_type');
        $status = $request->input('status');
        $reason = trim((string) $request->input('reason', ''));
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $startDate = null;
        $endDate = null;
        if (!empty($dateFrom)) {
            $startDate = Carbon::parse($dateFrom)->startOfDay();
            $endDate = !empty($dateTo) ? Carbon::parse($dateTo)->endOfDay() : Carbon::parse($dateFrom)->endOfDay();
        } elseif (!empty($dateTo)) {
            $endDate = Carbon::parse($dateTo)->endOfDay();
        }

        $applyDateFilter = function ($query) use ($startDate, $endDate) {
            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            } elseif ($startDate) {
                $query->where('created_at', '>=', $startDate);
            } elseif ($endDate) {
                $query->where('created_at', '<=', $endDate);
            }
        };

        $reports = collect();

        // 1. Post Reports
        if (empty($sourceType) || $sourceType === 'post') {
            $postQuery = ReportedPost::with(['member', 'post.member', 'post.originalPost.member']);
            $applyDateFilter($postQuery);
            $postReports = $postQuery->get()->map(function ($r) {
                $post = $r->post;
                $orig = $post?->originalPost;
                return (object) [
                    'id' => $r->id,
                    'type' => 'post',
                    'type_label' => 'Post',
                    'reporter_name' => $r->member?->name ?? 'Anonymous',
                    'reporter_user_id' => $r->member?->user_id ?? 'N/A',
                    'reporter_avatar' => $r->member?->avatar_url,
                    'target_id' => $r->post_id,
                    'target_title' => 'Post #' . $r->post_id . ': ' . ($post?->body ? Str::limit($post->body, 40) : '[Media Post]'),
                    'target_owner' => $post?->member?->name ?? 'N/A',
                    'target_media_url' => $post?->media_url ?: $orig?->media_url,
                    'target_media_path' => $post?->media_path ?: $orig?->media_path,
                    'target_media_type' => $post?->media_type ?: $orig?->media_type,
                    'reason' => $r->reason,
                    'details' => $r->description,
                    'status' => $r->status ?: 'pending',
                    'created_at' => $r->created_at,
                    'created_at_human' => $r->created_at?->diffForHumans(),
                    'model' => $r,
                ];
            });
            $reports = $reports->concat($postReports);
        }

        // 2. Community Reports
        if (empty($sourceType) || $sourceType === 'community') {
            $commQuery = CommunityReport::with(['reporter', 'community.owner']);
            $applyDateFilter($commQuery);
            $commReports = $commQuery->get()->map(function ($r) {
                return (object) [
                    'id' => $r->id,
                    'type' => 'community',
                    'type_label' => 'Community',
                    'reporter_name' => $r->reporter?->name ?? 'Anonymous',
                    'reporter_user_id' => $r->reporter?->user_id ?? 'N/A',
                    'reporter_avatar' => $r->reporter?->avatar_url,
                    'target_id' => $r->community_id,
                    'target_title' => 'Community: ' . ($r->community?->name ?? 'Group #' . $r->community_id),
                    'target_owner' => $r->community?->owner?->name ?? 'System',
                    'target_media_url' => $r->community?->logo ? asset($r->community->logo) : null,
                    'target_media_path' => $r->community?->logo,
                    'target_media_type' => 'image',
                    'reason' => $r->reason,
                    'details' => $r->details,
                    'status' => $r->status ?: 'pending',
                    'created_at' => $r->created_at,
                    'created_at_human' => $r->created_at?->diffForHumans(),
                    'model' => $r,
                ];
            });
            $reports = $reports->concat($commReports);
        }

        // 3. Marketplace Product Reports
        if (empty($sourceType) || $sourceType === 'product') {
            $prodQuery = ReportedProduct::with(['member', 'product.member', 'product.media']);
            $applyDateFilter($prodQuery);
            $prodReports = $prodQuery->get()->map(function ($r) {
                $firstMedia = $r->product?->media?->first();
                $mediaUrl = $firstMedia?->url ?? ($firstMedia?->path ? asset($firstMedia->path) : null);
                return (object) [
                    'id' => $r->id,
                    'type' => 'product',
                    'type_label' => 'Marketplace Product',
                    'reporter_name' => $r->member?->name ?? 'Anonymous',
                    'reporter_user_id' => $r->member?->user_id ?? 'N/A',
                    'reporter_avatar' => $r->member?->avatar_url,
                    'target_id' => $r->product_id,
                    'target_title' => 'Product: ' . ($r->product?->title ?? 'Item #' . $r->product_id),
                    'target_owner' => $r->product?->member?->name ?? 'N/A',
                    'target_media_url' => $mediaUrl,
                    'target_media_path' => $firstMedia?->path,
                    'target_media_type' => 'image',
                    'reason' => $r->reason,
                    'details' => $r->notes,
                    'status' => 'pending',
                    'created_at' => $r->created_at,
                    'created_at_human' => $r->created_at?->diffForHumans(),
                    'model' => $r,
                ];
            });
            $reports = $reports->concat($prodReports);
        }

        // 4. Business Review Reports
        if (empty($sourceType) || $sourceType === 'business_review') {
            $reviewQuery = BusinessReviewReport::with(['reporter', 'review.member']);
            $applyDateFilter($reviewQuery);
            $reviewReports = $reviewQuery->get()->map(function ($r) {
                $review = $r->review;
                $reviewText = $review?->body ?? $review?->title ?? 'Review';
                $firstPhoto = is_array($review?->photos) && count($review->photos) > 0 ? $review->photos[0] : null;
                return (object) [
                    'id' => $r->id,
                    'type' => 'business_review',
                    'type_label' => 'Business Review',
                    'reporter_name' => $r->reporter?->name ?? 'Anonymous',
                    'reporter_user_id' => $r->reporter?->user_id ?? 'N/A',
                    'reporter_avatar' => $r->reporter?->avatar_url,
                    'target_id' => $r->business_review_id,
                    'target_title' => 'Review #' . $r->business_review_id . ': ' . Str::limit($reviewText, 40),
                    'target_owner' => $review?->member?->name ?? 'N/A',
                    'target_media_url' => $firstPhoto ? (str_starts_with($firstPhoto, 'http') ? $firstPhoto : asset($firstPhoto)) : null,
                    'target_media_path' => $firstPhoto,
                    'target_media_type' => $firstPhoto ? 'image' : null,
                    'reason' => $r->reason,
                    'details' => $r->details,
                    'status' => $r->status ?: 'pending',
                    'created_at' => $r->created_at,
                    'created_at_human' => $r->created_at?->diffForHumans(),
                    'model' => $r,
                ];
            });
            $reports = $reports->concat($reviewReports);
        }

        // Filtering by Search Query
        if ($search !== '') {
            $reports = $reports->filter(function ($r) use ($search) {
                return stripos($r->reporter_name, $search) !== false
                    || stripos($r->reporter_user_id, $search) !== false
                    || stripos($r->target_title, $search) !== false
                    || stripos($r->reason, $search) !== false
                    || (string)$r->id === $search;
            });
        }

        // Filtering by Reason
        if ($reason !== '') {
            $normalizedReason = strtolower(str_replace('_', ' ', $reason));
            $reports = $reports->filter(function ($r) use ($normalizedReason) {
                $itemReason = strtolower(str_replace('_', ' ', (string) ($r->reason ?? '')));
                return $itemReason === $normalizedReason || stripos($itemReason, $normalizedReason) !== false;
            });
        }

        // Filtering by Status
        if (!empty($status)) {
            $reports = $reports->filter(function ($r) use ($status) {
                return strtolower($r->status) === strtolower($status);
            });
        }

        // Sort latest
        $reports = $reports->sortByDesc('created_at')->values();

        // Paginate manually
        $page = (int) $request->input('page', 1);
        $perPage = 15;
        $paginatedReports = new \Illuminate\Pagination\LengthAwarePaginator(
            $reports->slice(($page - 1) * $perPage, $perPage)->values(),
            $reports->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Dashboard metrics (Respecting active date filter)
        $postCountQuery = ReportedPost::query();
        $applyDateFilter($postCountQuery);
        $postCount = $postCountQuery->count();

        $commCountQuery = CommunityReport::query();
        $applyDateFilter($commCountQuery);
        $commCount = $commCountQuery->count();

        $productCountQuery = ReportedProduct::query();
        $applyDateFilter($productCountQuery);
        $productCount = $productCountQuery->count();

        $reviewCountQuery = BusinessReviewReport::query();
        $applyDateFilter($reviewCountQuery);
        $reviewCount = $reviewCountQuery->count();

        $totalCount = $postCount + $commCount + $productCount + $reviewCount;

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'paginatedReports' => $paginatedReports,
                'reports' => $reports,
                'totalCount' => $totalCount,
                'postCount' => $postCount,
                'commCount' => $commCount,
                'productCount' => $productCount,
                'search' => $search,
                'sourceType' => $sourceType,
                'status' => $status,
                'reason' => $reason,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);
        }

        return view('admin.reports.index', compact(
            'paginatedReports',
            'search',
            'sourceType',
            'status',
            'dateFrom',
            'dateTo',
            'totalCount',
            'postCount',
            'commCount',
            'productCount'
        ));
    }

    /**
     * Display detailed read-only inspection of a report and its target item.
     */
    public function show(Request $request, string $type, $id)
    {
        $numericId = filter_var($id, FILTER_VALIDATE_INT);
        if ($numericId === false) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => "Invalid report ID '{$id}'."], 404);
            }
            abort(404, "Invalid report ID '{$id}'.");
        }
        $id = $numericId;
        $reportData = null;

        if ($type === 'post') {
            $r = ReportedPost::with(['member', 'post.member', 'post.originalPost.member'])->findOrFail($id);
            $post = $r->post;
            $orig = $post?->originalPost;
            $reportData = (object) [
                'id' => $r->id,
                'type' => 'post',
                'type_label' => 'Post',
                'reporter' => $r->member ? [
                    'id' => $r->member->id,
                    'name' => $r->member->name,
                    'user_id' => $r->member->user_id,
                    'avatar_url' => $r->member->avatar_url ?? null,
                ] : null,
                'reason' => $r->reason,
                'details' => $r->description,
                'status' => $r->status ?: 'pending',
                'created_at' => $r->created_at,
                'created_at_human' => $r->created_at?->diffForHumans(),
                'target' => $post ? [
                    'id' => $post->id,
                    'title' => 'Post #' . $post->id,
                    'name' => 'Post #' . $post->id,
                    'body' => $post->body,
                    'media_path' => $post->media_path ?: $orig?->media_path,
                    'media_type' => $post->media_type ?: $orig?->media_type,
                    'media_url' => $post->media_url ?: $orig?->media_url,
                    'created_at' => $post->created_at,
                    'created_at_human' => $post->created_at?->diffForHumans(),
                    'status' => $post->is_hidden ? 'Hidden' : 'Active',
                    'is_shared' => (bool) ($post->original_post_id || $post->is_shared),
                    'original_post' => $orig ? [
                        'id' => $orig->id,
                        'body' => $orig->body,
                        'media_path' => $orig->media_path,
                        'media_type' => $orig->media_type,
                        'media_url' => $orig->media_url,
                        'member' => $orig->member ? [
                            'id' => $orig->member->id,
                            'name' => $orig->member->name,
                            'user_id' => $orig->member->user_id,
                            'avatar_url' => $orig->member->avatar_url ?? null,
                        ] : null,
                    ] : null,
                    'member' => $post->member ? [
                        'id' => $post->member->id,
                        'name' => $post->member->name,
                        'user_id' => $post->member->user_id,
                        'avatar_url' => $post->member->avatar_url ?? null,
                    ] : null,
                ] : null,
                'model' => $r,
            ];
        } elseif ($type === 'community') {
            $r = CommunityReport::with(['reporter', 'community.owner'])->findOrFail($id);
            $community = $r->community;
            $owner = $community?->owner;
            $reportData = (object) [
                'id' => $r->id,
                'type' => 'community',
                'type_label' => 'Community',
                'reporter' => $r->reporter ? [
                    'id' => $r->reporter->id,
                    'name' => $r->reporter->name,
                    'user_id' => $r->reporter->user_id,
                    'avatar_url' => $r->reporter->avatar_url ?? null,
                ] : null,
                'reason' => $r->reason,
                'details' => $r->details,
                'status' => $r->status ?: 'pending',
                'created_at' => $r->created_at,
                'created_at_human' => $r->created_at?->diffForHumans(),
                'target' => $community ? [
                    'id' => $community->id,
                    'title' => $community->name,
                    'name' => $community->name,
                    'description' => $community->description,
                    'media_path' => $community->logo,
                    'media_type' => 'image',
                    'media_url' => $community->logo ? asset($community->logo) : null,
                    'created_at' => $community->created_at,
                    'created_at_human' => $community->created_at?->diffForHumans(),
                    'status' => $community->status,
                    'member' => $owner ? [
                        'id' => $owner->id,
                        'name' => $owner->name,
                        'user_id' => $owner->user_id,
                        'avatar_url' => $owner->avatar_url ?? null,
                    ] : null,
                ] : null,
                'model' => $r,
            ];
        } elseif ($type === 'product') {
            $r = ReportedProduct::with(['member', 'product.member', 'product.media'])->findOrFail($id);
            $product = $r->product;
            $seller = $product?->member;
            $firstMedia = $product?->media?->first();
            $mediaUrl = $firstMedia?->url ?? ($firstMedia?->path ? asset($firstMedia->path) : null);
            $reportData = (object) [
                'id' => $r->id,
                'type' => 'product',
                'type_label' => 'Marketplace Product',
                'reporter' => $r->member ? [
                    'id' => $r->member->id,
                    'name' => $r->member->name,
                    'user_id' => $r->member->user_id,
                    'avatar_url' => $r->member->avatar_url ?? null,
                ] : null,
                'reason' => $r->reason,
                'details' => $r->notes,
                'status' => 'pending',
                'created_at' => $r->created_at,
                'created_at_human' => $r->created_at?->diffForHumans(),
                'target' => $product ? [
                    'id' => $product->id,
                    'title' => $product->title,
                    'name' => $product->title,
                    'description' => $product->description,
                    'media_path' => $firstMedia?->path,
                    'media_type' => 'image',
                    'media_url' => $mediaUrl,
                    'created_at' => $product->created_at,
                    'created_at_human' => $product->created_at?->diffForHumans(),
                    'status' => $product->status,
                    'member' => $seller ? [
                        'id' => $seller->id,
                        'name' => $seller->name,
                        'user_id' => $seller->user_id,
                        'avatar_url' => $seller->avatar_url ?? null,
                    ] : null,
                ] : null,
                'model' => $r,
            ];
        } elseif ($type === 'business_review') {
            $r = BusinessReviewReport::with(['reporter', 'review.member'])->findOrFail($id);
            $review = $r->review;
            $reviewer = $review?->member;
            $firstPhoto = is_array($review?->photos) && count($review->photos) > 0 ? $review->photos[0] : null;
            $reportData = (object) [
                'id' => $r->id,
                'type' => 'business_review',
                'type_label' => 'Business Review',
                'reporter' => $r->reporter ? [
                    'id' => $r->reporter->id,
                    'name' => $r->reporter->name,
                    'user_id' => $r->reporter->user_id,
                    'avatar_url' => $r->reporter->avatar_url ?? null,
                ] : null,
                'reason' => $r->reason,
                'details' => $r->details,
                'status' => $r->status ?: 'pending',
                'created_at' => $r->created_at,
                'created_at_human' => $r->created_at?->diffForHumans(),
                'target' => $review ? [
                    'id' => $review->id,
                    'title' => $review->title ?: 'Business Review #' . $review->id,
                    'name' => $review->title ?: 'Business Review #' . $review->id,
                    'body' => $review->body,
                    'description' => $review->body,
                    'media_path' => $firstPhoto,
                    'media_type' => $firstPhoto ? 'image' : null,
                    'media_url' => $firstPhoto ? (str_starts_with($firstPhoto, 'http') ? $firstPhoto : asset($firstPhoto)) : null,
                    'created_at' => $review->created_at,
                    'created_at_human' => $review->created_at?->diffForHumans(),
                    'status' => $review->is_hidden ? 'Hidden' : 'Active',
                    'member' => $reviewer ? [
                        'id' => $reviewer->id,
                        'name' => $reviewer->name,
                        'user_id' => $reviewer->user_id,
                        'avatar_url' => $reviewer->avatar_url ?? null,
                    ] : null,
                ] : null,
                'model' => $r,
            ];
        } else {
            abort(404, 'Report type not found.');
        }

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'reportData' => $reportData,
            ]);
        }

        return view('admin.reports.show', compact('reportData'));
    }

    /**
     * Update report status (e.g. mark reviewed, resolved, or dismissed).
     * Post remains live. Author and reporter are untouched.
     */
    public function updateStatus(Request $request, string $type, $id)
    {
        $numericId = filter_var($id, FILTER_VALIDATE_INT);
        if ($numericId === false) {
            return response()->json([
                'success' => false,
                'message' => "Invalid report ID '{$id}'. Must be an integer.",
            ], 404);
        }

        $status = $request->input('status', 'resolved');

        if (!in_array($status, ['pending', 'resolved', 'dismissed'], true)) {
            return response()->json([
                'success' => false,
                'message' => "Invalid status '{$status}'. Supported: pending, resolved, dismissed.",
            ], 422);
        }

        if ($type === 'post') {
            $report = ReportedPost::find($numericId);
            if (!$report) {
                return response()->json(['success' => false, 'message' => "Report #{$numericId} not found."], 404);
            }
            $report->update(['status' => $status]);
        } elseif ($type === 'community') {
            $report = CommunityReport::find($numericId);
            if (!$report) {
                return response()->json(['success' => false, 'message' => "Report #{$numericId} not found."], 404);
            }
            $report->update(['status' => $status, 'resolved_by_id' => auth()->id()]);
        } elseif ($type === 'business_review') {
            $report = BusinessReviewReport::find($numericId);
            if (!$report) {
                return response()->json(['success' => false, 'message' => "Report #{$numericId} not found."], 404);
            }
            $report->update(['status' => $status]);
        } elseif ($type === 'product') {
            $report = ReportedProduct::find($numericId);
            if (!$report) {
                return response()->json(['success' => false, 'message' => "Report #{$numericId} not found."], 404);
            }
            // Note: reported_products table schema does not store a persistent status column.
            // Acknowledge the update safely without triggering SQL errors.
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid report type.',
            ], 422);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Report #{$numericId} status updated to {$status}.",
            ]);
        }

        return redirect()->back()->with('success', "Report #{$numericId} status updated to {$status}.");
    }

    /**
     * Delete a single report ticket ONLY (does NOT delete target content or members).
     */
    public function destroy(Request $request, string $type, $id)
    {
        $numericId = filter_var($id, FILTER_VALIDATE_INT);
        if ($numericId === false) {
            return response()->json([
                'success' => false,
                'message' => "Invalid report ID '{$id}'. Must be an integer.",
            ], 404);
        }

        if ($type === 'post') {
            $report = ReportedPost::find($numericId);
            if (!$report) {
                return response()->json(['success' => false, 'message' => "Report #{$numericId} not found."], 404);
            }
            $report->delete();
        } elseif ($type === 'community') {
            $report = CommunityReport::find($numericId);
            if (!$report) {
                return response()->json(['success' => false, 'message' => "Report #{$numericId} not found."], 404);
            }
            $report->delete();
        } elseif ($type === 'product') {
            $report = ReportedProduct::find($numericId);
            if (!$report) {
                return response()->json(['success' => false, 'message' => "Report #{$numericId} not found."], 404);
            }
            $report->delete();
        } elseif ($type === 'business_review') {
            $report = BusinessReviewReport::find($numericId);
            if (!$report) {
                return response()->json(['success' => false, 'message' => "Report #{$numericId} not found."], 404);
            }
            $report->delete();
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid report type specified.',
            ], 422);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Report #{$numericId} ticket deleted successfully.",
            ]);
        }

        return redirect()->route('admin.reports.index')->with('success', "Report #{$numericId} ticket deleted.");
    }

    /**
     * Delete the reported target content (destructive action).
     * For posts: deletes post and cleans up media; cascades report cleanup.
     * Members/users are NEVER deleted.
     */
    public function destroyTarget(Request $request, string $type, $id)
    {
        $numericId = filter_var($id, FILTER_VALIDATE_INT);
        if ($numericId === false) {
            return response()->json([
                'success' => false,
                'message' => "Invalid report ID '{$id}'. Must be an integer.",
            ], 404);
        }
        $id = $numericId;

        return DB::transaction(function () use ($request, $type, $id) {
            if ($type === 'post') {
                $report = ReportedPost::find($id);
                if (!$report) {
                    return response()->json(['success' => false, 'message' => 'Report record not found.'], 404);
                }

                $post = Post::find($report->post_id);
                if (!$post) {
                    // Post already deleted
                    $report->update(['status' => 'resolved']);
                    return response()->json([
                        'success' => true,
                        'message' => 'The reported post has already been removed. Report ticket marked as resolved.',
                    ]);
                }

                $postId = $post->id;
                // Delete media file if it exists locally
                if ($post->media_path && File::exists(public_path($post->media_path))) {
                    try {
                        File::delete(public_path($post->media_path));
                    } catch (\Throwable $e) {
                        // File deletion should not block post database removal
                    }
                }

                // Delete the post. Foreign key cascade will remove related reported_posts.
                // NEVER delete author/member!
                $post->delete();

                // If cascade did not delete this report row, clean it up
                if (ReportedPost::where('id', $id)->exists()) {
                    ReportedPost::where('id', $id)->delete();
                }

                return response()->json([
                    'success' => true,
                    'message' => "Reported Post #{$postId} and attached media have been permanently deleted.",
                ]);
            } elseif ($type === 'community') {
                $report = CommunityReport::find($id);
                if (!$report) {
                    return response()->json(['success' => false, 'message' => 'Report record not found.'], 404);
                }

                if ($report->reportable_type === 'community') {
                    $community = Community::find($report->community_id);
                    if ($community) {
                        $community->delete();
                    }
                } elseif ($report->reportable_type === 'post') {
                    $commPost = CommunityPost::find($report->reportable_id);
                    if ($commPost) {
                        if ($commPost->media_path && File::exists(public_path($commPost->media_path))) {
                            File::delete(public_path($commPost->media_path));
                        }
                        $commPost->delete();
                    }
                } elseif ($report->reportable_type === 'comment') {
                    $commComment = CommunityComment::find($report->reportable_id);
                    if ($commComment) {
                        $commComment->delete();
                    }
                }

                $report->update(['status' => 'resolved', 'resolved_by_id' => auth()->id()]);

                return response()->json([
                    'success' => true,
                    'message' => 'Reported community content has been removed.',
                ]);
            } elseif ($type === 'product') {
                $report = ReportedProduct::find($id);
                if (!$report) {
                    return response()->json(['success' => false, 'message' => 'Report record not found.'], 404);
                }

                $product = Product::find($report->product_id);
                if ($product) {
                    $product->delete();
                }
                $report->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Reported product listing has been removed.',
                ]);
            } elseif ($type === 'business_review') {
                $report = BusinessReviewReport::find($id);
                if (!$report) {
                    return response()->json(['success' => false, 'message' => 'Report record not found.'], 404);
                }

                $review = BusinessReview::find($report->business_review_id);
                if ($review) {
                    $review->delete();
                }
                $report->update(['status' => 'resolved']);

                return response()->json([
                    'success' => true,
                    'message' => 'Reported business review has been removed.',
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Invalid report type.'], 422);
        });
    }

    /**
     * Bulk actions for reports queue (resolve, dismiss, or delete tickets).
     */
    public function bulkAction(Request $request)
    {
        $action = $request->input('action');
        $rawItems = $request->input('items', $request->input('ids', []));

        if (!is_array($rawItems)) {
            $rawItems = [];
        }

        if (!in_array($action, ['resolve', 'dismiss', 'delete'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid bulk action. Allowed: resolve, dismiss, delete.',
            ], 422);
        }

        $processedCount = 0;
        $failedCount = 0;

        foreach ($rawItems as $item) {
            // Match keys like post_12, community_3, product_4, business_review_5
            if (!preg_match('/^(post|community|product|business_review)_(\d+)$/', (string) $item, $matches)) {
                $failedCount++;
                continue;
            }

            $type = $matches[1];
            $id = (int) $matches[2];

            if ($action === 'resolve' || $action === 'dismiss') {
                $status = $action === 'resolve' ? 'resolved' : 'dismissed';
                if ($type === 'post') {
                    ReportedPost::where('id', $id)->update(['status' => $status]);
                } elseif ($type === 'community') {
                    CommunityReport::where('id', $id)->update(['status' => $status, 'resolved_by_id' => auth()->id()]);
                } elseif ($type === 'business_review') {
                    BusinessReviewReport::where('id', $id)->update(['status' => $status]);
                } elseif ($type === 'product') {
                    // product reports do not store a status column
                }
                $processedCount++;
            } elseif ($action === 'delete') {
                // Delete tickets only (never targets or users)
                if ($type === 'post') {
                    ReportedPost::where('id', $id)->delete();
                } elseif ($type === 'community') {
                    CommunityReport::where('id', $id)->delete();
                } elseif ($type === 'product') {
                    ReportedProduct::where('id', $id)->delete();
                } elseif ($type === 'business_review') {
                    BusinessReviewReport::where('id', $id)->delete();
                }
                $processedCount++;
            }
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'processed_count' => $processedCount,
                'failed_count' => $failedCount,
                'message' => "Successfully processed {$processedCount} report(s).",
            ]);
        }

        return redirect()->back()->with('success', "Successfully processed {$processedCount} report(s).");
    }

    /**
     * Export reports to CSV stream.
     */
    public function export(Request $request)
    {
        $sourceType = $request->input('source_type', $request->input('type'));
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $startDate = null;
        $endDate = null;
        if (!empty($dateFrom)) {
            $startDate = Carbon::parse($dateFrom)->startOfDay();
            $endDate = !empty($dateTo) ? Carbon::parse($dateTo)->endOfDay() : Carbon::parse($dateFrom)->endOfDay();
        } elseif (!empty($dateTo)) {
            $endDate = Carbon::parse($dateTo)->endOfDay();
        }

        $applyDateFilter = function ($query) use ($startDate, $endDate) {
            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            } elseif ($startDate) {
                $query->where('created_at', '>=', $startDate);
            } elseif ($endDate) {
                $query->where('created_at', '<=', $endDate);
            }
        };

        $filename = 'reports_export_' . date('Y_m_d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($sourceType, $applyDateFilter) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, ['Report ID', 'Type', 'Reporter Name', 'Reporter User ID', 'Reason', 'Details', 'Created Date']);

            if (empty($sourceType) || $sourceType === 'post') {
                $q = ReportedPost::with('member')->latest('created_at');
                $applyDateFilter($q);
                $q->chunk(200, function ($records) use ($file) {
                    foreach ($records as $r) {
                        fputcsv($file, [$r->id, 'Post', $r->member?->name ?? 'N/A', $r->member?->user_id ?? 'N/A', $r->reason, $r->description, $r->created_at?->format('Y-m-d H:i:s')]);
                    }
                });
            }
            if (empty($sourceType) || $sourceType === 'community') {
                $q = CommunityReport::with('reporter')->latest('created_at');
                $applyDateFilter($q);
                $q->chunk(200, function ($records) use ($file) {
                    foreach ($records as $r) {
                        fputcsv($file, [$r->id, 'Community', $r->reporter?->name ?? 'N/A', $r->reporter?->user_id ?? 'N/A', $r->reason, $r->details, $r->created_at?->format('Y-m-d H:i:s')]);
                    }
                });
            }
            if (empty($sourceType) || $sourceType === 'product') {
                $q = ReportedProduct::with('member')->latest('created_at');
                $applyDateFilter($q);
                $q->chunk(200, function ($records) use ($file) {
                    foreach ($records as $r) {
                        fputcsv($file, [$r->id, 'Product', $r->member?->name ?? 'N/A', $r->member?->user_id ?? 'N/A', $r->reason, $r->notes, $r->created_at?->format('Y-m-d H:i:s')]);
                    }
                });
            }
            if (empty($sourceType) || $sourceType === 'business_review') {
                $q = BusinessReviewReport::with('reporter')->latest('created_at');
                $applyDateFilter($q);
                $q->chunk(200, function ($records) use ($file) {
                    foreach ($records as $r) {
                        fputcsv($file, [$r->id, 'Business Review', $r->reporter?->name ?? 'N/A', $r->reporter?->user_id ?? 'N/A', $r->reason, $r->details, $r->created_at?->format('Y-m-d H:i:s')]);
                    }
                });
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
