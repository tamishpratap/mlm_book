<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\FeedbackSuggestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackSuggestionController extends Controller
{
    /**
     * Display a listing of the authenticated member's feedback and suggestions.
     */
    public function index(Request $request): JsonResponse
    {
        $member = auth('member')->user() ?? $request->user('member');

        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $feedbacks = FeedbackSuggestion::where('member_id', $member->id)
            ->latest('created_at')
            ->take(50)
            ->get();

        return response()->json([
            'success' => true,
            'feedbacks' => $feedbacks,
        ]);
    }

    /**
     * Store a newly created feedback or suggestion from the authenticated member.
     */
    public function store(Request $request): JsonResponse
    {
        $member = auth('member')->user() ?? $request->user('member');

        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:feedback,suggestion,idea,other,bug_report,complaint'],
            'subject' => ['required', 'string', 'max:191'],
            'message' => ['required', 'string', 'min:5', 'max:5000'],
        ]);

        // Duplicate submission guard: rapid resubmit within 5 seconds with same subject and message
        $recentDuplicate = FeedbackSuggestion::where('member_id', $member->id)
            ->where('subject', trim($validated['subject']))
            ->where('message', trim($validated['message']))
            ->where('created_at', '>=', now()->subSeconds(5))
            ->latest()
            ->first();

        if ($recentDuplicate) {
            return response()->json([
                'success' => true,
                'message' => 'Your feedback has already been received.',
                'feedback' => $recentDuplicate,
                'is_duplicate' => true,
            ], 200);
        }

        $feedback = FeedbackSuggestion::create([
            'member_id' => $member->id,
            'type' => strtolower(trim($validated['type'])),
            'subject' => trim($validated['subject']),
            'message' => trim($validated['message']),
            'status' => 'new',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you! Your feedback has been submitted successfully.',
            'feedback' => $feedback,
        ], 201);
    }
}
