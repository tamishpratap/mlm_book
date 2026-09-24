<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\BusinessNotification;
use App\Models\BusinessPage;
use App\Models\BusinessVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BusinessVerificationController extends Controller
{
    public function index(Request $request, BusinessPage $businessPage)
    {
        $member = auth('member')->user();

        if (! $businessPage->isOwner($member->id)) {
            abort(403, 'Unauthorized action. Only page owners can access the Business Verification Portal.');
        }

        $verifications = $businessPage->verifications()->latest()->get();
        $latestVerification = $businessPage->latestVerification;
        $status = $businessPage->verificationStatus();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'business_page' => $businessPage,
                'verifications' => $verifications,
                'latest_verification' => $latestVerification,
                'status' => $status,
            ]);
        }

        return view('member.business-pages.verification.index', compact(
            'businessPage',
            'verifications',
            'latestVerification',
            'status'
        ));
    }

    public function store(Request $request, BusinessPage $businessPage)
    {
        $member = auth('member')->user();

        if (! $businessPage->isOwner($member->id)) {
            return response()->json(['success' => false, 'message' => 'Only page owners can submit verification requests.'], 403);
        }

        if ($businessPage->is_verified) {
            return response()->json(['success' => false, 'message' => 'This business page is already officially verified.'], 422);
        }

        if ($businessPage->isVerificationPending()) {
            return response()->json(['success' => false, 'message' => 'A verification request is already pending review.'], 422);
        }

        $validated = $request->validate([
            'document_type' => ['required', 'string', 'in:business_registration,gst,license,govt_id,other'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'document_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $file = $request->file('document_file');
        $directory = 'uploads/business_pages/verifications';
        $ext = strtolower($file->getClientOriginalExtension() ?: 'pdf');
        $filename = sprintf('verify_%d_%d_%s.%s', $member->id, time(), Str::random(6), $ext);

        $documentPath = app(\App\Http\Controllers\ImageCompressionController::class)->compressAndStore(
            $file,
            $directory,
            'verification',
            $filename,
            'public_uploads'
        );

        $verification = BusinessVerification::create([
            'business_page_id' => $businessPage->id,
            'member_id' => $member->id,
            'status' => 'pending',
            'document_type' => $validated['document_type'],
            'document_number' => filled($validated['document_number'] ?? null) ? trim($validated['document_number']) : null,
            'document_path' => $documentPath,
            'admin_notes' => null,
            'reviewed_at' => null,
        ]);

        BusinessNotification::create([
            'business_page_id' => $businessPage->id,
            'member_id' => $member->id,
            'type' => 'new_verification_request',
            'title' => 'Verification document submitted for review',
            'data' => [
                'document_type' => $validated['document_type'],
                'verification_id' => $verification->id,
            ],
            'is_read' => false,
        ]);

        \App\Services\AdminNotificationService::notify(
            title: 'Business Verification Request',
            message: sprintf('"%s" submitted %s verification documents for review.', $businessPage->page_name, ucwords(str_replace('_', ' ', $validated['document_type']))),
            icon: 'shield',
            sourceType: 'business_verification',
            sourceId: (string) $verification->id,
            actionUrl: '/admin/business-pages',
            metadata: ['business_page_id' => $businessPage->id, 'verification_id' => $verification->id]
        );

        return response()->json([
            'success' => true,
            'message' => 'Your official verification document has been submitted for review!',
            'verification' => $verification,
        ]);
    }
}
