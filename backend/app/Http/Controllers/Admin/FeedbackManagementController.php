<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeedbackSuggestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FeedbackManagementController extends Controller
{
    /**
     * Display a paginated listing of member feedback and suggestions.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'nullable|string|max:200',
            'status' => 'nullable|string|in:new,in_review,resolved,closed',
            'type' => 'nullable|string|max:50',
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
            'per_page' => 'nullable|integer|min:5|max:100',
        ]);

        $search = $request->input('q');
        $filters = [
            'status' => $request->input('status'),
            'type' => $request->input('type'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $perPage = (int) $request->input('per_page', 15);

        $query = FeedbackSuggestion::with([
            'member:id,name,user_id,email,profile_photo',
            'admin:id,name,email',
        ])
            ->search($search)
            ->filterBy($filters)
            ->latest('created_at');

        $feedbacks = $query->paginate($perPage);

        // Aggregated metrics for stats cards
        $stats = [
            'totalCount' => FeedbackSuggestion::count(),
            'newCount' => FeedbackSuggestion::where('status', 'new')->count(),
            'inReviewCount' => FeedbackSuggestion::where('status', 'in_review')->count(),
            'resolvedCount' => FeedbackSuggestion::where('status', 'resolved')->count(),
        ];

        return response()->json([
            'success' => true,
            'feedbacks' => $feedbacks,
            'stats' => $stats,
        ]);
    }

    /**
     * Display details for a single feedback or suggestion submission.
     */
    public function show(FeedbackSuggestion $feedback): JsonResponse
    {
        $feedback->load([
            'member:id,name,user_id,email,profile_photo,phone,created_at',
            'admin:id,name,email',
        ]);

        return response()->json([
            'success' => true,
            'feedback' => $feedback,
        ]);
    }

    /**
     * Update the status of a submission.
     */
    public function updateStatus(Request $request, FeedbackSuggestion $feedback): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:new,in_review,resolved,closed',
        ]);

        $feedback->status = $request->input('status');
        $feedback->save();

        $feedback->load([
            'member:id,name,user_id,email,profile_photo',
            'admin:id,name,email',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Submission status updated to '{$feedback->status_label}'.",
            'feedback' => $feedback,
        ]);
    }

    /**
     * Save an admin response/resolution note for a submission.
     */
    public function updateResponse(Request $request, FeedbackSuggestion $feedback): JsonResponse
    {
        $request->validate([
            'admin_response' => 'required|string|max:10000',
            'status' => 'nullable|string|in:new,in_review,resolved,closed',
        ]);

        $feedback->admin_response = $request->input('admin_response');
        $feedback->admin_id = auth('admin')->id();
        $feedback->responded_at = now();

        if ($request->filled('status')) {
            $feedback->status = $request->input('status');
        } elseif ($feedback->status === 'new') {
            $feedback->status = 'in_review';
        }

        $feedback->save();

        $feedback->load([
            'member:id,name,user_id,email,profile_photo',
            'admin:id,name,email',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Admin response recorded successfully.',
            'feedback' => $feedback,
        ]);
    }

    /**
     * Perform bulk moderation actions on submissions.
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|string|in:resolve,in_review,mark_new,close,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $action = $request->input('action');
        $ids = $request->input('ids');

        $count = 0;
        switch ($action) {
            case 'resolve':
                $count = FeedbackSuggestion::whereIn('id', $ids)->update(['status' => 'resolved']);
                $message = "{$count} submissions marked as Resolved.";
                break;

            case 'in_review':
                $count = FeedbackSuggestion::whereIn('id', $ids)->update(['status' => 'in_review']);
                $message = "{$count} submissions marked as In Review.";
                break;

            case 'mark_new':
                $count = FeedbackSuggestion::whereIn('id', $ids)->update(['status' => 'new']);
                $message = "{$count} submissions reset to New.";
                break;

            case 'close':
                $count = FeedbackSuggestion::whereIn('id', $ids)->update(['status' => 'closed']);
                $message = "{$count} submissions marked as Closed.";
                break;

            case 'delete':
                $count = FeedbackSuggestion::whereIn('id', $ids)->delete();
                $message = "{$count} submissions permanently deleted.";
                break;

            default:
                return response()->json(['success' => false, 'message' => 'Invalid bulk action.'], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'affected_count' => $count,
        ]);
    }

    /**
     * Permanently delete a feedback/suggestion record.
     */
    public function destroy(FeedbackSuggestion $feedback): JsonResponse
    {
        $feedback->delete();

        return response()->json([
            'success' => true,
            'message' => 'Submission deleted successfully.',
        ]);
    }

    /**
     * Export feedback and suggestions to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $search = $request->input('q');
        $filters = [
            'status' => $request->input('status'),
            'type' => $request->input('type'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $filename = 'feedback_suggestions_' . date('Y_m_d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($search, $filters) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            fputcsv($file, [
                'ID',
                'Type',
                'Subject',
                'Message',
                'Status',
                'Member Name',
                'Member User ID',
                'Member Email',
                'Admin Response',
                'Responded By',
                'Responded Date',
                'Submitted Date',
            ]);

            FeedbackSuggestion::with(['member', 'admin'])
                ->search($search)
                ->filterBy($filters)
                ->latest('created_at')
                ->chunk(200, function ($records) use ($file) {
                    foreach ($records as $item) {
                        fputcsv($file, [
                            $item->id,
                            $item->type_label,
                            $item->subject,
                            $item->message,
                            $item->status_label,
                            $item->member?->name ?? 'N/A',
                            $item->member?->user_id ?? 'N/A',
                            $item->member?->email ?? 'N/A',
                            $item->admin_response ?? '',
                            $item->admin?->name ?? '',
                            $item->responded_at ? $item->responded_at->format('Y-m-d H:i:s') : '',
                            $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : '',
                        ]);
                    }
                });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
