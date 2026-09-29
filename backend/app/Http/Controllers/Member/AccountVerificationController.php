<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Mail\MemberEmailVerificationOtp;
use App\Models\Member;
use App\Models\MemberVerificationOtp;
use App\Models\PhoneNumberChangeRequest;
use App\Services\MemberOtpService;
use App\Services\WhatsAppService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class AccountVerificationController extends Controller
{
    public function __construct(
        private MemberOtpService $otpService,
        private WhatsAppService $whatsAppService
    ) {}

    public function getVerificationStatus(Request $request)
    {
        /** @var Member $member */
        $member = auth('member')->user();
        $fresh = $member->fresh();

        $destination = $this->getWhatsAppDestination();
        $whatsAppUrl = $this->getWhatsAppDeepLink($destination, $fresh);

        $phone = $fresh->phone;
        $maskedPhone = $phone ? app(\App\Services\MemberPhoneNumberService::class)->mask($phone) : null;

        $pendingChange = $fresh->latestPendingPhoneChangeRequest;
        $pendingChangePayload = null;
        if ($pendingChange) {
            $changeWhatsAppUrl = $this->getWhatsAppChangeRequestDeepLink(
                $destination,
                $fresh,
                $pendingChange->old_phone,
                $pendingChange->new_phone
            );
            $pendingChangePayload = [
                'id' => $pendingChange->id,
                'old_phone' => $pendingChange->old_phone,
                'masked_old_phone' => app(\App\Services\MemberPhoneNumberService::class)->mask($pendingChange->old_phone),
                'new_phone' => $pendingChange->new_phone,
                'masked_new_phone' => app(\App\Services\MemberPhoneNumberService::class)->mask($pendingChange->new_phone),
                'status' => $pendingChange->status,
                'whatsapp_verified_at' => $pendingChange->whatsapp_verified_at?->toIso8601String(),
                'is_whatsapp_verified' => $pendingChange->isWhatsAppVerified(),
                'whatsapp_url' => $changeWhatsAppUrl,
                'created_at' => $pendingChange->created_at?->toIso8601String(),
            ];
        }

        return response()->json([
            'success' => true,
            'is_verified' => $fresh->isMobileVerified(),
            'is_pending' => $fresh->isMobileVerificationPending(),
            'verification_status' => $fresh->verification_status,
            'phone' => $phone,
            'masked_phone' => $maskedPhone,
            'mobile_verified_at' => $fresh->mobile_verified_at?->toIso8601String(),
            'mobile_verification_requested_at' => $fresh->mobile_verification_requested_at?->toIso8601String(),
            'whatsapp_destination' => $destination,
            'whatsapp_url' => $whatsAppUrl,
            'has_pending_phone_change' => $pendingChange !== null,
            'pending_phone_change_request' => $pendingChangePayload,
            'member' => $fresh,
        ]);
    }

    public function initiateWhatsAppVerification(Request $request)
    {
        /** @var Member $member */
        $member = auth('member')->user();
        $fresh = $member->fresh();

        if ($fresh->isMobileVerified()) {
            return response()->json([
                'success' => true,
                'status' => 'verified',
                'is_verified' => true,
                'is_pending' => false,
                'message' => 'Your account is already verified.',
                'member' => $fresh,
            ]);
        }

        if (empty($fresh->phone)) {
            return response()->json([
                'success' => false,
                'status' => 'missing_phone',
                'message' => 'Please register a valid mobile number on your profile first.',
                'errors' => ['phone' => ['No registered phone number found on account.']],
            ], 422);
        }

        $destination = $this->getWhatsAppDestination();
        $whatsAppUrl = $this->getWhatsAppDeepLink($destination, $fresh);
        $maskedPhone = app(\App\Services\MemberPhoneNumberService::class)->mask($fresh->phone);

        return response()->json([
            'success' => true,
            'status' => $fresh->isMobileVerificationPending() ? 'pending' : 'ready',
            'is_verified' => false,
            'is_pending' => $fresh->isMobileVerificationPending(),
            'registered_phone' => $fresh->phone,
            'masked_phone' => $maskedPhone,
            'whatsapp_destination' => $destination,
            'whatsapp_url' => $whatsAppUrl,
            'mobile_verification_requested_at' => $fresh->mobile_verification_requested_at?->toIso8601String(),
        ]);
    }

    public function submitWhatsAppVerificationRequest(Request $request)
    {
        /** @var Member $member */
        $member = auth('member')->user();
        $fresh = $member->fresh();

        if ($fresh->isMobileVerified()) {
            return response()->json([
                'success' => true,
                'status' => 'verified',
                'is_verified' => true,
                'is_pending' => false,
                'message' => 'Your account is already verified.',
                'member' => $fresh,
            ]);
        }

        if (empty($fresh->phone)) {
            return response()->json([
                'success' => false,
                'status' => 'missing_phone',
                'message' => 'Please register a valid mobile number on your profile first.',
                'errors' => ['phone' => ['No registered phone number found on account.']],
            ], 422);
        }

        // Throttle submit attempts to prevent spam (max 5 requests per 60 seconds per member)
        $rateKey = 'member-verification-hi-request:' . $member->getKey();
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $seconds = RateLimiter::availableIn($rateKey);
            return response()->json([
                'success' => false,
                'message' => "Please wait {$seconds} seconds before submitting again.",
                'errors' => ['request' => ["Please wait {$seconds} seconds before submitting again."]],
            ], 429);
        }
        RateLimiter::hit($rateKey, 60);

        // Record verification request timestamp without marking as verified
        // CRITICAL INVARIANT: mobile_verified_at MUST remain NULL in Phase 3
        $now = now();
        $fresh->update([
            'mobile_verification_requested_at' => $now,
        ]);

        $fresh = $fresh->fresh();

        Log::info('Member submitted WhatsApp "Hi" verification request', [
            'member_id' => $fresh->id,
            'user_id' => $fresh->user_id,
            'phone' => app(\App\Services\MemberPhoneNumberService::class)->mask($fresh->phone),
            'requested_at' => $now->toIso8601String(),
        ]);

        // Dispatch admin notification
        \App\Services\AdminNotificationService::notify(
            title: 'WhatsApp Verification Requested',
            message: sprintf('%s (%s) sent "Hi" from %s and requested verification.', $fresh->name, $fresh->user_id, app(\App\Services\MemberPhoneNumberService::class)->mask($fresh->phone)),
            icon: 'shield',
            sourceType: 'member_verification',
            sourceId: (string) $fresh->id,
            actionUrl: '/admin/members/' . $fresh->id,
            metadata: [
                'member_id' => $fresh->id,
                'user_id' => $fresh->user_id,
                'phone' => $fresh->phone,
                'requested_at' => $now->toIso8601String(),
            ]
        );

        return response()->json([
            'success' => true,
            'status' => 'pending',
            'is_verified' => false,
            'is_pending' => true,
            'message' => 'Verification request submitted. Your account is pending manual verification.',
            'mobile_verification_requested_at' => $fresh->mobile_verification_requested_at?->toIso8601String(),
            'member' => $fresh,
        ]);
    }

    public function sendMobileOtp(Request $request)
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $validated = $request->validate([
            'mobile_number' => [
                'required',
                'string',
                'regex:/^\+?[0-9\s\-()]{7,20}$/',
            ],
        ], [
            'mobile_number.regex' => 'Please enter a valid mobile number with country code (e.g. +91 9876543210 or +1 2345678901).',
        ]);

        // Clean and format phone number to E.164-like clean string
        $rawPhone = preg_replace('/[^\d+]/', '', $validated['mobile_number']);
        if (! str_starts_with($rawPhone, '+')) {
            $rawPhone = '+' . $rawPhone;
        }

        $key = MemberOtpService::cooldownRateKey(MemberOtpService::PURPOSE_MOBILE_CHANGE, $member->getKey());
        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'message' => "Please wait {$seconds} seconds before requesting a new WhatsApp code.",
                    'errors' => ['mobile_number' => ["Please wait {$seconds} seconds before requesting a new WhatsApp code."]],
                ], 422);
            }
            return back()->withErrors(['mobile_number' => "Please wait {$seconds} seconds before requesting a new code."]);
        }

        RateLimiter::hit($key, 60);

        $result = $this->otpService->createOtp(
            $member,
            MemberOtpService::PURPOSE_MOBILE_CHANGE,
            $rawPhone,
            $rawPhone
        );

        // Send OTP via WhatsApp Service
        $whatsAppResult = $this->whatsAppService->sendOtp(
            $rawPhone,
            $result['code'],
            $member->name
        );

        Log::info('WhatsApp verification OTP generated & dispatched', [
            'member_id' => $member->getKey(),
            'mobile_number' => $rawPhone,
            'code' => $result['code'],
            'whatsapp_result' => $whatsAppResult,
        ]);

        $responsePayload = [
            'success' => true,
            'message' => "Verification code sent to WhatsApp on {$rawPhone}",
            'mobile_number' => $rawPhone,
            'expires_in_minutes' => MemberOtpService::EXPIRY_MINUTES,
            'channel' => 'whatsapp',
        ];

        // Include demo OTP in local/demo environment for effortless developer & user testing
        if ($whatsAppResult['mode'] === 'demo' || app()->environment('local', 'development')) {
            $responsePayload['demo_otp'] = $result['code'];
            $responsePayload['is_demo'] = true;
        }

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json($responsePayload);
        }

        return back()->with('success', "Verification code sent to WhatsApp on {$rawPhone}");
    }

    public function verifyMobileOtp(Request $request)
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $validated = $request->validate([
            'mobile_otp' => ['required', 'string', 'min:4', 'max:8'],
        ]);

        $otpCode = preg_replace('/\D/', '', $validated['mobile_otp']);

        try {
            $this->otpService->verifyOtp(
                $member,
                MemberOtpService::PURPOSE_MOBILE_CHANGE,
                $otpCode,
                'mobile_otp',
                function (MemberVerificationOtp $otp) use ($member): void {
                    // Normalize requested phone number consistently (E.164 +digits)
                    $requestedPhone = preg_replace('/[^\d+]/', '', (string) $otp->pending_value);
                    if (! str_starts_with($requestedPhone, '+')) {
                        $requestedPhone = '+' . $requestedPhone;
                    }

                    // Check phone uniqueness excluding current authenticated member
                    $duplicateExists = Member::where('phone', $requestedPhone)
                        ->where('id', '!=', $member->id)
                        ->exists();

                    if ($duplicateExists) {
                        throw ValidationException::withMessages([
                            'mobile_otp' => ['This WhatsApp number is already registered with another account.'],
                        ]);
                    }

                    try {
                        $member->update([
                            'phone' => $requestedPhone,
                            'mobile_verified_at' => now(),
                        ]);
                        $member->qualifyReferral();
                    } catch (QueryException $e) {
                        // Defensive catch for concurrency / race condition on members_phone_unique
                        if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'members_phone_unique') || str_contains($e->getMessage(), '1062')) {
                            Log::warning('WhatsApp phone unique constraint collision on update', [
                                'member_id' => $member->id,
                                'phone' => substr($requestedPhone, 0, 4) . '****' . substr($requestedPhone, -2),
                            ]);

                            throw ValidationException::withMessages([
                                'mobile_otp' => ['This WhatsApp number is already registered with another account.'],
                            ]);
                        }

                        Log::error('Database error during mobile verification phone update', [
                            'member_id' => $member->id,
                            'exception' => get_class($e),
                        ]);

                        throw ValidationException::withMessages([
                            'mobile_otp' => ['Unable to complete WhatsApp verification. Please try again.'],
                        ]);
                    }
                }
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (QueryException $e) {
            // Defensive safeguard against raw database/SQL exceptions bubbling to the frontend
            if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'members_phone_unique') || str_contains($e->getMessage(), '1062')) {
                Log::warning('WhatsApp phone duplicate key constraint collision during verifyMobileOtp', [
                    'member_id' => $member->id,
                    'code' => $e->getCode(),
                ]);

                throw ValidationException::withMessages([
                    'mobile_otp' => ['This WhatsApp number is already registered with another account.'],
                ]);
            }

            Log::error('Unexpected QueryException during verifyMobileOtp', [
                'member_id' => $member->id,
                'exception' => get_class($e),
            ]);

            throw ValidationException::withMessages([
                'mobile_otp' => ['Unable to complete WhatsApp verification. Please try again.'],
            ]);
        }

        $freshMember = $member->fresh();

        \App\Services\AdminNotificationService::notify(
            title: 'Member Account Verified',
            message: sprintf('%s (%s) has completed mobile verification.', $freshMember->name, $freshMember->user_id),
            icon: 'shield',
            sourceType: 'member_verification',
            sourceId: (string) $freshMember->id,
            actionUrl: '/admin/members/' . $freshMember->id,
            metadata: ['member_id' => $freshMember->id, 'user_id' => $freshMember->user_id]
        );

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Your mobile number has been verified! Green verified badge activated.',
                'member' => $freshMember,
                'is_verified' => true,
            ]);
        }

        return back()->with('success', 'Your mobile number has been verified! Green verified badge activated.');
    }

    public function sendEmailOtp(Request $request)
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $validated = $request->validate([
            'new_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('members', 'email')->ignore($member->id),
            ],
        ]);

        $key = MemberOtpService::cooldownRateKey(MemberOtpService::PURPOSE_EMAIL_CHANGE, $member->getKey());
        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'message' => "Please wait {$seconds} seconds before requesting a new code.",
                    'errors' => ['new_email' => ["Please wait {$seconds} seconds before requesting a new code."]],
                ], 422);
            }
            return back()->withErrors(['new_email' => "Please wait {$seconds} seconds before requesting a new code."]);
        }

        RateLimiter::hit($key, 60);

        $newEmail = $validated['new_email'];

        $result = $this->otpService->createOtp(
            $member,
            MemberOtpService::PURPOSE_EMAIL_CHANGE,
            $newEmail,
            $newEmail
        );

        Log::info('Email verification OTP generated & dispatch initiated', [
            'member_id' => $member->getKey(),
            'current_email' => $this->maskEmail($member->email),
            'target_email' => $this->maskEmail($newEmail),
        ]);

        try {
            Mail::to($newEmail)->send(
                new MemberEmailVerificationOtp($result['code'], $member->name)
            );

            Log::info('Email verification OTP dispatched successfully', [
                'member_id' => $member->getKey(),
                'target_email' => $this->maskEmail($newEmail),
            ]);
        } catch (Throwable $e) {
            $this->otpService->invalidate($member, MemberOtpService::PURPOSE_EMAIL_CHANGE);
            RateLimiter::clear($key);

            Log::error('Failed to dispatch email verification OTP', [
                'member_id' => $member->getKey(),
                'target_email' => $this->maskEmail($newEmail),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            $errorMessage = 'Unable to send verification code. Please try again.';

            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'errors' => ['new_email' => [$errorMessage]],
                ], 500);
            }

            return back()->withErrors(['new_email' => $errorMessage]);
        }

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Verification code sent to ' . $newEmail,
                'destination' => $newEmail,
                'expires_in_minutes' => MemberOtpService::EXPIRY_MINUTES,
            ]);
        }

        return back()->with('success', 'Verification code sent to ' . $newEmail);
    }

    public function verifyEmailOtp(Request $request)
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $validated = $request->validate([
            'email_otp' => ['required', 'string', 'size:6'],
        ]);

        $this->otpService->verifyOtp(
            $member,
            MemberOtpService::PURPOSE_EMAIL_CHANGE,
            $validated['email_otp'],
            'email_otp',
            function (MemberVerificationOtp $otp) use ($member): void {
                if (Member::where('email', $otp->pending_value)->where('id', '!=', $member->id)->exists()) {
                    throw ValidationException::withMessages([
                        'email_otp' => ['This email address is already registered to another account.'],
                    ]);
                }

                $member->update([
                    'email' => $otp->pending_value,
                    'google_id' => null,
                ]);
            }
        );

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Email address updated successfully.',
                'member' => $member->fresh(),
            ]);
        }

        return back()->with('success', 'Email address updated successfully.');
    }

    private function maskEmail(?string $email): string
    {
        if (empty($email) || ! str_contains($email, '@')) {
            return '***';
        }

        [$user, $domain] = explode('@', $email, 2);
        $maskedUser = mb_substr($user, 0, 1) . '***';

        return $maskedUser . '@' . $domain;
    }

    private function getWhatsAppDestination(): string
    {
        return config('whatsapp.verification_number')
            ?: config('whatsapp.to_number')
            ?: env('WHATSAPP_VERIFICATION_NUMBER')
            ?: env('WHATSAPP_TO_NUMBER')
            ?: config('services.whatsapp.sender_number')
            ?: '+919876543210';
    }

    private function getWhatsAppDeepLink(string $destinationNumber, ?Member $member = null): string
    {
        $cleanDigits = preg_replace('/\D/', '', $destinationNumber) ?: '919876543210';

        if ($member) {
            $name = trim((string) $member->name);
            $userId = trim((string) $member->user_id);
            $email = trim((string) $member->email);
            $phone = trim((string) $member->phone);

            $message = "Hello Support Team,\n\nI would like to verify my WhatsApp number for my account.\n\nUser ID: {$userId}\nName: {$name}\nEmail: {$email}\nMobile Number: {$phone}\n\nKindly verify and link this number to my account. Please let me know if any additional information is required.\n\nThank you.";
        } else {
            $message = "Hello Support Team,\n\nI would like to verify my WhatsApp number for my account.\n\nKindly verify and link this number to my account. Please let me know if any additional information is required.\n\nThank you.";
        }

        return 'https://wa.me/' . $cleanDigits . '?text=' . rawurlencode($message);
    }

    /**
     * Update phone number for an unverified member.
     */
    public function updateUnverifiedPhone(Request $request)
    {
        /** @var Member $member */
        $member = auth('member')->user();
        $fresh = $member->fresh();

        if ($fresh->isMobileVerified()) {
            return response()->json([
                'success' => false,
                'message' => 'Your phone number is already verified. To change a verified phone number, please submit a Phone Change Request.',
                'errors' => ['phone' => ['Your phone number is already verified.']],
            ], 422);
        }

        $validated = $request->validate([
            'phone' => [
                'required',
                'string',
                'regex:/^\+?[0-9\s\-()]{7,20}$/',
            ],
        ], [
            'phone.regex' => 'Please enter a valid mobile number with country code (e.g. +91 9876543210).',
        ]);

        $rawPhone = preg_replace('/[^\d+]/', '', $validated['phone']);
        if (! str_starts_with($rawPhone, '+')) {
            $rawPhone = '+' . $rawPhone;
        }

        $digitsOnly = preg_replace('/\D/', '', $rawPhone);
        if (strlen($digitsOnly) < 7 || strlen($digitsOnly) > 15) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid phone number with 7 to 15 digits.',
                'errors' => ['phone' => ['Please enter a valid phone number with 7 to 15 digits.']],
            ], 422);
        }

        // Check uniqueness in members table
        $duplicate = Member::where('phone', $rawPhone)->where('id', '!=', $fresh->id)->exists();
        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => 'This phone number is already registered to another account.',
                'errors' => ['phone' => ['This phone number is already registered to another account.']],
            ], 422);
        }

        // Update phone and reset verification request timestamp
        $fresh->update([
            'phone' => $rawPhone,
            'mobile_verification_requested_at' => null,
        ]);

        $updated = $fresh->fresh();
        $destination = $this->getWhatsAppDestination();
        $whatsAppUrl = $this->getWhatsAppDeepLink($destination, $updated);
        $maskedPhone = app(\App\Services\MemberPhoneNumberService::class)->mask($updated->phone);

        Log::info('Member updated unverified phone number', [
            'member_id' => $updated->id,
            'user_id' => $updated->user_id,
            'new_phone' => $maskedPhone,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Phone number updated successfully. You can now verify it via WhatsApp.',
            'phone' => $updated->phone,
            'masked_phone' => $maskedPhone,
            'whatsapp_url' => $whatsAppUrl,
            'whatsapp_destination' => $destination,
            'member' => $updated,
        ]);
    }

    /**
     * Submit a phone number change request for an already-verified member.
     * Note: The existing verified phone number remains active until admin approval.
     */
    public function requestPhoneChange(Request $request)
    {
        /** @var Member $member */
        $member = auth('member')->user();
        $fresh = $member->fresh();

        if (! $fresh->isMobileVerified()) {
            return response()->json([
                'success' => false,
                'message' => 'Your current phone number is not verified yet. Please update and verify it directly.',
                'errors' => ['new_phone' => ['Your current phone number is not verified yet.']],
            ], 422);
        }

        // Guard: prevent duplicate active change requests
        if ($fresh->hasPendingPhoneChangeRequest()) {
            $existing = $fresh->latestPendingPhoneChangeRequest;
            return response()->json([
                'success' => false,
                'message' => 'You already have an active phone number change request pending admin approval.',
                'errors' => ['new_phone' => ['You already have an active phone number change request pending admin approval.']],
                'pending_request' => $existing,
            ], 422);
        }

        $validated = $request->validate([
            'new_phone' => [
                'required',
                'string',
                'regex:/^\+?[0-9\s\-()]{7,20}$/',
            ],
        ], [
            'new_phone.regex' => 'Please enter a valid new mobile number with country code (e.g. +91 9876543210).',
        ]);

        $rawPhone = preg_replace('/[^\d+]/', '', $validated['new_phone']);
        if (! str_starts_with($rawPhone, '+')) {
            $rawPhone = '+' . $rawPhone;
        }

        $digitsOnly = preg_replace('/\D/', '', $rawPhone);
        if (strlen($digitsOnly) < 7 || strlen($digitsOnly) > 15) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid phone number with 7 to 15 digits.',
                'errors' => ['new_phone' => ['Please enter a valid phone number with 7 to 15 digits.']],
            ], 422);
        }

        // Check if new number equals current number
        if ($rawPhone === $fresh->phone) {
            return response()->json([
                'success' => false,
                'message' => 'The new phone number cannot be the same as your current verified number.',
                'errors' => ['new_phone' => ['The new phone number cannot be the same as your current verified number.']],
            ], 422);
        }

        // Check if taken by another member
        if (Member::where('phone', $rawPhone)->where('id', '!=', $fresh->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This phone number is already registered to another account.',
                'errors' => ['new_phone' => ['This phone number is already registered to another account.']],
            ], 422);
        }

        // Check if in another pending request
        if (PhoneNumberChangeRequest::where('new_phone', $rawPhone)->where('status', 'pending')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This phone number is already part of a pending change request.',
                'errors' => ['new_phone' => ['This phone number is already part of a pending change request.']],
            ], 422);
        }

        // Rate limit: 5 requests per 10 minutes
        $rateKey = 'phone-change-request:' . $fresh->id;
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $seconds = RateLimiter::availableIn($rateKey);
            return response()->json([
                'success' => false,
                'message' => "Please wait {$seconds} seconds before submitting another change request.",
            ], 429);
        }
        RateLimiter::hit($rateKey, 600);

        // Create change request. CRITICAL: members.phone remains UNTOUCHED!
        $changeRequest = PhoneNumberChangeRequest::create([
            'member_id' => $fresh->id,
            'old_phone' => $fresh->phone,
            'new_phone' => $rawPhone,
            'status' => 'pending',
            'whatsapp_verified_at' => null,
        ]);

        $destination = $this->getWhatsAppDestination();
        $changeWhatsAppUrl = $this->getWhatsAppChangeRequestDeepLink(
            $destination,
            $fresh,
            $changeRequest->old_phone,
            $changeRequest->new_phone
        );

        Log::info('Member created phone number change request', [
            'change_request_id' => $changeRequest->id,
            'member_id' => $fresh->id,
            'user_id' => $fresh->user_id,
            'old_phone' => $changeRequest->old_phone,
            'new_phone' => $changeRequest->new_phone,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Phone number change request created. Please send the verification message on WhatsApp.',
            'change_request' => [
                'id' => $changeRequest->id,
                'old_phone' => $changeRequest->old_phone,
                'masked_old_phone' => app(\App\Services\MemberPhoneNumberService::class)->mask($changeRequest->old_phone),
                'new_phone' => $changeRequest->new_phone,
                'masked_new_phone' => app(\App\Services\MemberPhoneNumberService::class)->mask($changeRequest->new_phone),
                'status' => $changeRequest->status,
                'whatsapp_verified_at' => null,
                'whatsapp_url' => $changeWhatsAppUrl,
                'created_at' => $changeRequest->created_at->toIso8601String(),
            ],
            'whatsapp_url' => $changeWhatsAppUrl,
            'whatsapp_destination' => $destination,
        ]);
    }

    /**
     * Member confirms that they sent the WhatsApp verification message for their change request.
     */
    public function confirmPhoneChangeWhatsApp(Request $request, $id)
    {
        /** @var Member $member */
        $member = auth('member')->user();
        $fresh = $member->fresh();

        $changeRequest = PhoneNumberChangeRequest::where('id', $id)
            ->where('member_id', $fresh->id)
            ->firstOrFail();

        if ($changeRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => "This change request has already been {$changeRequest->status}.",
            ], 422);
        }

        $now = now();
        $changeRequest->update([
            'whatsapp_verified_at' => $now,
        ]);

        Log::info('Member confirmed sending WhatsApp for phone change request', [
            'change_request_id' => $changeRequest->id,
            'member_id' => $fresh->id,
            'user_id' => $fresh->user_id,
            'new_phone' => $changeRequest->new_phone,
        ]);

        // Notify Admin of incoming change request
        \App\Services\AdminNotificationService::notify(
            title: 'Phone Number Change Requested',
            message: sprintf('%s (%s) requested to change number from %s to %s and sent WhatsApp confirmation.', $fresh->name, $fresh->user_id, app(\App\Services\MemberPhoneNumberService::class)->mask($changeRequest->old_phone), app(\App\Services\MemberPhoneNumberService::class)->mask($changeRequest->new_phone)),
            icon: 'shield',
            sourceType: 'phone_change_request',
            sourceId: (string) $changeRequest->id,
            actionUrl: '/admin/members/phone-change-requests',
            metadata: [
                'change_request_id' => $changeRequest->id,
                'member_id' => $fresh->id,
                'user_id' => $fresh->user_id,
                'old_phone' => $changeRequest->old_phone,
                'new_phone' => $changeRequest->new_phone,
                'requested_at' => $now->toIso8601String(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp verification confirmation recorded. Your phone change request is pending admin approval.',
            'change_request' => [
                'id' => $changeRequest->id,
                'old_phone' => $changeRequest->old_phone,
                'masked_old_phone' => app(\App\Services\MemberPhoneNumberService::class)->mask($changeRequest->old_phone),
                'new_phone' => $changeRequest->new_phone,
                'masked_new_phone' => app(\App\Services\MemberPhoneNumberService::class)->mask($changeRequest->new_phone),
                'status' => $changeRequest->status,
                'whatsapp_verified_at' => $now->toIso8601String(),
                'is_whatsapp_verified' => true,
            ],
        ]);
    }

    /**
     * Member cancels their own pending phone number change request.
     */
    public function cancelPhoneChangeRequest(Request $request, $id)
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $changeRequest = PhoneNumberChangeRequest::where('id', $id)
            ->where('member_id', $member->id)
            ->firstOrFail();

        if ($changeRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be cancelled.',
            ], 422);
        }

        $changeRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Phone number change request has been cancelled.',
        ]);
    }

    /**
     * Build distinct WhatsApp deep link for phone change requests.
     */
    public function getWhatsAppChangeRequestDeepLink(string $destinationNumber, Member $member, string $oldPhone, string $newPhone): string
    {
        $cleanDigits = preg_replace('/\D/', '', $destinationNumber) ?: '919876543210';
        $name = trim((string) $member->name);
        $userId = trim((string) $member->user_id);
        $email = trim((string) $member->email);

        $message = "Hello Support Team,\n\n"
            . "PHONE NUMBER CHANGE REQUEST\n\n"
            . "I would like to request a change of my registered WhatsApp number.\n\n"
            . "Member ID: {$userId}\n"
            . "Name: {$name}\n"
            . "Email: {$email}\n"
            . "Current Verified Number: {$oldPhone}\n"
            . "New Requested Number: {$newPhone}\n\n"
            . "Kindly review and approve my phone number change request.\n\n"
            . "Thank you.";

        return 'https://wa.me/' . $cleanDigits . '?text=' . rawurlencode($message);
    }
}

