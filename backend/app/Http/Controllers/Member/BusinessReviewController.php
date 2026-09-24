<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureMemberMobileVerified;
use App\Models\BusinessPage;
use App\Models\BusinessReview;
use App\Models\BusinessReviewReply;
use App\Models\BusinessReviewReport;
use App\Models\BusinessReviewVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BusinessReviewController extends Controller
{
    public function store(Request $request, BusinessPage $businessPage)
    {
        $member = auth('member')->user();

        if (! $member || ! $member->isMobileVerified()) {
            return response()->json([
                'success' => false,
                'message' => EnsureMemberMobileVerified::UNVERIFIED_MESSAGE,
            ], 403);
        }

        if ($businessPage->isOwner($member->id)) {
            return response()->json(['success' => false, 'message' => 'Page owners cannot write reviews for their own business.'], 403);
        }

        if (! $businessPage->isFollowedBy($member->id)) {
            return response()->json(['success' => false, 'message' => 'Only followers of this business page can write a review.'], 403);
        }

        if ($businessPage->hasReviewedBy($member->id)) {
            return response()->json(['success' => false, 'message' => 'You have already submitted a review for this business page.'], 422);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'recommendation' => ['required', 'string', 'in:recommend,not_recommend'],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'photos.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $photoPaths = [];
        try {
            if ($request->hasFile('photos')) {
                $directory = 'uploads/business_pages/reviews';
                File::ensureDirectoryExists(public_path($directory));

                foreach ($request->file('photos') as $photo) {
                    $ext = strtolower($photo->getClientOriginalExtension() ?: 'jpg');
                    $filename = sprintf('review_%d_%d_%s.%s', $member->id, time(), Str::random(6), $ext);
                    $savedPath = app(\App\Http\Controllers\ImageCompressionController::class)->compressAndStore(
                        $photo,
                        $directory,
                        'review',
                        $filename,
                        'public_uploads'
                    );
                    if ($savedPath) {
                        $photoPaths[] = $savedPath;
                    }
                }
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach ($photoPaths as $p) {
                @unlink(public_path($p));
            }
            throw $e;
        }

        $review = BusinessReview::create([
            'business_page_id' => $businessPage->id,
            'member_id' => $member->id,
            'rating' => $validated['rating'],
            'recommendation' => $validated['recommendation'],
            'title' => filled($validated['title'] ?? null) ? trim($validated['title']) : null,
            'body' => trim($validated['body']),
            'photos' => $photoPaths,
            'is_hidden' => false,
            'helpful_count' => 0,
            'unhelpful_count' => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Your review for ' . $businessPage->page_name . ' has been published!',
            'review' => $review->load('member'),
        ]);
    }

    public function update(Request $request, BusinessPage $businessPage, BusinessReview $review)
    {
        $member = auth('member')->user();

        if ((int) $review->member_id !== (int) $member->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        if ((int) $review->business_page_id !== (int) $businessPage->id) {
            return response()->json(['success' => false, 'message' => 'Invalid review.'], 422);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'recommendation' => ['required', 'string', 'in:recommend,not_recommend'],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $review->update([
            'rating' => $validated['rating'],
            'recommendation' => $validated['recommendation'],
            'title' => filled($validated['title'] ?? null) ? trim($validated['title']) : null,
            'body' => trim($validated['body']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review updated successfully!',
            'review' => $review,
        ]);
    }

    public function destroy(Request $request, BusinessPage $businessPage, BusinessReview $review)
    {
        $member = auth('member')->user();

        if ((int) $review->member_id !== (int) $member->id && ! $businessPage->isTeamAdmin($member->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        if ((int) $review->business_page_id !== (int) $businessPage->id) {
            return response()->json(['success' => false, 'message' => 'Invalid review.'], 422);
        }

        if (is_array($review->photos)) {
            foreach ($review->photos as $photoPath) {
                if (File::exists(public_path($photoPath))) {
                    File::delete(public_path($photoPath));
                }
            }
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully.',
        ]);
    }

    public function storeReply(Request $request, BusinessPage $businessPage, BusinessReview $review)
    {
        $member = auth('member')->user();

        if (! $businessPage->isTeamAdmin($member->id)) {
            return response()->json(['success' => false, 'message' => 'Only page owners or admins can write official replies.'], 403);
        }

        if ((int) $review->business_page_id !== (int) $businessPage->id) {
            return response()->json(['success' => false, 'message' => 'Invalid review.'], 422);
        }

        $validated = $request->validate([
            'reply' => ['required', 'string', 'max:3000'],
        ]);

        $officialReply = BusinessReviewReply::updateOrCreate(
            ['business_review_id' => $review->id],
            [
                'member_id' => $member->id,
                'reply' => trim($validated['reply']),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Official reply published!',
            'reply' => $officialReply->load('member'),
        ]);
    }

    public function vote(Request $request, BusinessPage $businessPage, BusinessReview $review)
    {
        $member = auth('member')->user();

        if (! $member || ! $member->isMobileVerified()) {
            return response()->json([
                'success' => false,
                'message' => EnsureMemberMobileVerified::UNVERIFIED_MESSAGE,
            ], 403);
        }

        if ((int) $review->business_page_id !== (int) $businessPage->id) {
            return response()->json(['success' => false, 'message' => 'Invalid review.'], 422);
        }

        $type = $request->input('vote_type', 'helpful');
        if (! in_array($type, ['helpful', 'unhelpful'], true)) {
            $type = 'helpful';
        }

        $existingVote = BusinessReviewVote::where('business_review_id', $review->id)
            ->where('member_id', $member->id)
            ->first();

        if ($existingVote) {
            if ($existingVote->vote_type === $type) {
                // Cancel vote
                $existingVote->delete();
            } else {
                // Change vote
                $existingVote->update(['vote_type' => $type]);
            }
        } else {
            BusinessReviewVote::create([
                'business_review_id' => $review->id,
                'member_id' => $member->id,
                'vote_type' => $type,
            ]);
        }

        // Recalculate vote counts
        $helpfulCount = BusinessReviewVote::where('business_review_id', $review->id)->where('vote_type', 'helpful')->count();
        $unhelpfulCount = BusinessReviewVote::where('business_review_id', $review->id)->where('vote_type', 'unhelpful')->count();

        $review->update([
            'helpful_count' => $helpfulCount,
            'unhelpful_count' => $unhelpfulCount,
        ]);

        $currentVote = $review->userVote($member->id);

        return response()->json([
            'success' => true,
            'helpful_count' => $helpfulCount,
            'unhelpful_count' => $unhelpfulCount,
            'user_vote' => $currentVote,
        ]);
    }

    public function report(Request $request, BusinessPage $businessPage, BusinessReview $review)
    {
        $member = auth('member')->user();

        if ((int) $review->business_page_id !== (int) $businessPage->id) {
            return response()->json(['success' => false, 'message' => 'Invalid review.'], 422);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'in:spam,fake_review,harassment,offensive,other'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        BusinessReviewReport::create([
            'business_review_id' => $review->id,
            'reporter_id' => $member->id,
            'reason' => $validated['reason'],
            'details' => filled($validated['details'] ?? null) ? trim($validated['details']) : null,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you. Your report has been submitted to the moderation team.',
        ]);
    }

    public function toggleHide(Request $request, BusinessPage $businessPage, BusinessReview $review)
    {
        $member = auth('member')->user();

        if (! $businessPage->isTeamAdmin($member->id)) {
            return response()->json(['success' => false, 'message' => 'Only page owners or admins can moderate reviews.'], 403);
        }

        if ((int) $review->business_page_id !== (int) $businessPage->id) {
            return response()->json(['success' => false, 'message' => 'Invalid review.'], 422);
        }

        $newHiddenState = ! $review->is_hidden;
        $review->update(['is_hidden' => $newHiddenState]);

        return response()->json([
            'success' => true,
            'is_hidden' => $newHiddenState,
            'message' => $newHiddenState ? 'Review is now hidden from the public.' : 'Review is now visible.',
        ]);
    }
}
