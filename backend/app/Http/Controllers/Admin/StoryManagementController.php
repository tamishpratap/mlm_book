<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class StoryManagementController extends Controller
{
    /**
     * Display a listing of stories with search, status filtering, and pagination.
     */
    public function index(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
        ]);

        $search = trim((string) $request->input('q'));
        $status = $request->input('status');
        $mediaType = $request->input('media_type');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = Story::query()
            ->with(['member'])
            ->withCount(['views', 'likes', 'reactions', 'replies']);

        // Search Filter (Story ID, Author Name, User ID, Caption)
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                  ->orWhere('caption', 'like', "%{$search}%")
                  ->orWhereHas('member', function ($mq) use ($search) {
                      $mq->where('name', 'like', "%{$search}%")
                         ->orWhere('user_id', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Status Filter (Active vs Expired)
        if ($status === 'active') {
            $query->where('expires_at', '>', now());
        } elseif ($status === 'expired') {
            $query->where('expires_at', '<=', now());
        }

        // Media Type Filter
        if ($mediaType === 'image') {
            $query->where('media_type', 'image');
        } elseif ($mediaType === 'video') {
            $query->where('media_type', 'video');
        }

        // Date Range Filter
        $startDate = null;
        $endDate = null;

        if (!empty($dateFrom) || !empty($dateTo)) {
            $startDate = !empty($dateFrom) ? Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay() : null;
            $endDate = !empty($dateTo) ? Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay() : null;
        }

        $stories = $this->buildFilteredQuery($request)
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        // Metrics Summary
        $metricsQuery = Story::query();
        if ($startDate && $endDate) {
            $metricsQuery->whereBetween('created_at', [$startDate, $endDate]);
        } elseif ($startDate) {
            $metricsQuery->where('created_at', '>=', $startDate);
        } elseif ($endDate) {
            $metricsQuery->where('created_at', '<=', $endDate);
        }

        $totalCount = (clone $metricsQuery)->count();
        $activeCount = (clone $metricsQuery)->where('expires_at', '>', now())->count();
        $expiredCount = (clone $metricsQuery)->where('expires_at', '<=', now())->count();
        $imageCount = (clone $metricsQuery)->where('media_type', 'image')->count();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'stories' => $stories,
                'totalCount' => $totalCount,
                'activeCount' => $activeCount,
                'expiredCount' => $expiredCount,
                'imageCount' => $imageCount,
            ]);
        }

        return view('admin.stories.index', compact(
            'stories',
            'search',
            'status',
            'mediaType',
            'dateFrom',
            'dateTo',
            'totalCount',
            'activeCount',
            'expiredCount',
            'imageCount'
        ));
    }

    /**
     * Build the filtered query for stories.
     */
    protected function buildFilteredQuery(Request $request)
    {
        $search = trim((string) $request->input('q'));
        $status = $request->input('status');
        $mediaType = $request->input('media_type');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = Story::query()
            ->with(['member'])
            ->withCount(['views', 'likes', 'reactions', 'replies']);

        // Search Filter (Story ID, Author Name, User ID, Caption)
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                  ->orWhere('caption', 'like', "%{$search}%")
                  ->orWhereHas('member', function ($mq) use ($search) {
                      $mq->where('name', 'like', "%{$search}%")
                         ->orWhere('user_id', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Status Filter (Active vs Expired)
        if ($status === 'active') {
            $query->where('expires_at', '>', now());
        } elseif ($status === 'expired') {
            $query->where('expires_at', '<=', now());
        }

        // Media Type Filter
        if ($mediaType === 'image') {
            $query->where('media_type', 'image');
        } elseif ($mediaType === 'video') {
            $query->where('media_type', 'video');
        }

        // Date Range Filter
        if (!empty($dateFrom) || !empty($dateTo)) {
            try {
                $startDate = !empty($dateFrom) ? Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay() : null;
                $endDate = !empty($dateTo) ? Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay() : null;

                if ($startDate && $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                } elseif ($startDate) {
                    $query->where('created_at', '>=', $startDate);
                } elseif ($endDate) {
                    $query->where('created_at', '<=', $endDate);
                }
            } catch (\Throwable $e) {
                // Ignore malformed date and do not crash
            }
        }

        return $query;
    }

    /**
     * Display detailed story inspection with viewers, reactions, and replies.
     */
    public function show(Story $story)
    {
        $story->load([
            'member',
            'views.member',
            'reactions.member',
            'replies.member'
        ])->loadCount(['views', 'likes', 'reactions', 'replies']);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'story' => $story,
            ]);
        }

        return view('admin.stories.show', compact('story'));
    }

    /**
     * Delete a single story.
     */
    public function destroy(Story $story)
    {
        // Delete attachment if local
        if ($story->media_path && Storage::disk('public')->exists($story->media_path)) {
            Storage::disk('public')->delete($story->media_path);
        }

        $story->delete();

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Story deleted successfully.'
            ]);
        }

        return redirect()->route('admin.stories.index')->with('success', 'Story deleted successfully.');
    }

    /**
     * Apply bulk action (e.g., delete) on selected stories.
     */
    public function bulkAction(Request $request)
    {
        $action = $request->input('action');
        $ids = $request->input('ids', []);

        if (empty($ids) || !is_array($ids)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'No stories selected.'], 422);
            }
            return redirect()->back()->with('error', 'No stories selected.');
        }

        if ($action === 'delete') {
            $stories = Story::whereIn('id', $ids)->get();
            foreach ($stories as $story) {
                if ($story->media_path && Storage::disk('public')->exists($story->media_path)) {
                    Storage::disk('public')->delete($story->media_path);
                }
                $story->delete();
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => count($ids) . ' stories deleted successfully.'
                ]);
            }

            return redirect()->route('admin.stories.index')->with('success', count($ids) . ' stories deleted successfully.');
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => false, 'message' => 'Invalid bulk action.'], 422);
        }

        return redirect()->back()->with('error', 'Invalid bulk action.');
    }

    /**
     * Export stories list to CSV stream.
     */
    public function export(Request $request)
    {
        $query = $this->buildFilteredQuery($request)->latest('created_at');

        $filename = 'stories_export_' . date('Y_m_d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM for Excel / UTF-8 compatibility
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'Story ID',
                'Author Name',
                'Author User ID',
                'Caption',
                'Media Type',
                'Status',
                'Views',
                'Reactions',
                'Replies',
                'Created Date',
                'Expires Date',
            ]);

            $query->chunk(200, function ($stories) use ($file) {
                foreach ($stories as $s) {
                    $status = ($s->expires_at && $s->expires_at > now()) ? 'Active' : 'Expired';

                    fputcsv($file, [
                        $s->id,
                        $s->member?->name ?? 'N/A',
                        $s->member?->user_id ?? 'N/A',
                        $s->caption ?? '',
                        $s->media_type,
                        $status,
                        $s->views_count,
                        $s->reactions_count,
                        $s->replies_count,
                        $s->created_at?->format('Y-m-d H:i:s') ?? '',
                        $s->expires_at?->format('Y-m-d H:i:s') ?? '',
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
