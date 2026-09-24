<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberVerificationOtp;
use App\Services\MemberOtpService;
use App\Services\MemberUserIdService;
use App\Services\ReferralRelationshipValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class AccountController extends Controller
{
    public function settings(Request $request)
    {
        /** @var Member $member */
        $member = auth('member')->user();
        $activeOtps = MemberVerificationOtp::query()
            ->where('member_id', $member->getKey())
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->get()
            ->keyBy('purpose');

        $mobileCooldown = RateLimiter::availableIn(MemberOtpService::cooldownRateKey(MemberOtpService::PURPOSE_MOBILE_CHANGE, $member->getKey()));
        $emailCooldown = RateLimiter::availableIn(MemberOtpService::cooldownRateKey(MemberOtpService::PURPOSE_EMAIL_CHANGE, $member->getKey()));

        $introducer = null;
        if (! empty($member->introducer_id)) {
            $introducer = Member::where('user_id', $member->introducer_id)
                ->select(['id', 'name', 'user_id', 'profile_photo', 'mobile_verified_at', 'city', 'country'])
                ->first();
        }

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'member' => $member,
                'introducer' => $introducer,
                'mobile_otp_pending' => $activeOtps->get(MemberOtpService::PURPOSE_MOBILE_CHANGE),
                'email_otp_pending' => $activeOtps->get(MemberOtpService::PURPOSE_EMAIL_CHANGE),
                'mobile_cooldown' => $mobileCooldown,
                'email_cooldown' => $emailCooldown,
            ]);
        }

        return view('member.account.settings', [
            'member' => $member,
            'introducer' => $introducer,
            'mobileOtpPending' => $activeOtps->get(MemberOtpService::PURPOSE_MOBILE_CHANGE),
            'emailOtpPending' => $activeOtps->get(MemberOtpService::PURPOSE_EMAIL_CHANGE),
            'mobileCooldown' => $mobileCooldown,
            'emailCooldown' => $emailCooldown,
        ]);
    }

    /**
     * Preview / check an Introducer ID for the authenticated member.
     */
    public function checkIntroducer(
        Request $request,
        MemberUserIdService $userIdService,
        ReferralRelationshipValidator $validator
    ) {
        /** @var Member $member */
        $member = auth('member')->user();

        if (! empty($member->introducer_id)) {
            return response()->json([
                'valid' => false,
                'exists' => false,
                'is_eligible' => false,
                'message' => 'Your introducer is already assigned.',
            ]);
        }

        $raw = $request->input('introducer_id', $request->input('ref', $request->query('introducer_id', $request->query('ref', ''))));
        $clean = $userIdService->normalize((string) $raw);

        if (blank($clean)) {
            return response()->json([
                'valid' => false,
                'exists' => false,
                'is_eligible' => false,
                'message' => 'Please enter an Introducer ID.',
            ]);
        }

        $validation = $validator->validate($member, $clean);
        if (! $validation['valid']) {
            return response()->json([
                'valid' => false,
                'exists' => $validation['introducer'] !== null,
                'is_self' => $validation['code'] === 'SELF_REFERRAL',
                'is_eligible' => false,
                'user_id' => $validation['introducer']?->user_id ?? $clean,
                'name' => $validation['introducer']?->name ?? '',
                'code' => $validation['code'],
                'message' => $validation['message'],
            ]);
        }

        $introducer = $validation['introducer'];

        return response()->json([
            'valid' => true,
            'exists' => true,
            'is_eligible' => true,
            'user_id' => $introducer->user_id,
            'name' => $introducer->name,
            'profile_photo' => $introducer->profile_photo,
            'message' => "Introduced by {$introducer->name}",
        ]);
    }

    /**
     * Post-registration introducer claim / assignment.
     * Strictly ADD-ONLY when current introducer_id is NULL.
     */
    public function claimIntroducer(
        Request $request,
        MemberUserIdService $userIdService,
        ReferralRelationshipValidator $validator
    ) {
        /** @var Member $member */
        $member = auth('member')->user();

        if (! empty($member->introducer_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Your introducer is already assigned and cannot be changed.',
                'errors' => ['introducer_id' => ['Your introducer is already assigned and cannot be changed.']],
            ], 422);
        }

        $validated = $request->validate([
            'introducer_id' => ['required', 'string', 'max:50'],
        ]);

        $clean = $userIdService->normalize((string) $validated['introducer_id']);

        if (blank($clean)) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a valid Introducer ID.',
                'errors' => ['introducer_id' => ['Please provide a valid Introducer ID.']],
            ], 422);
        }

        // Concurrency-safe atomic assignment within transaction
        $result = DB::transaction(function () use ($member, $clean, $validator) {
            /** @var Member|null $lockedMember */
            $lockedMember = Member::where('id', $member->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedMember || ! empty($lockedMember->introducer_id)) {
                return [
                    'success' => false,
                    'code' => 422,
                    'message' => 'Your introducer is already assigned and cannot be changed.',
                ];
            }

            // Centralized validation strictly within lock
            $validation = $validator->validate($lockedMember, $clean);
            if (! $validation['valid']) {
                return [
                    'success' => false,
                    'code' => 422,
                    'message' => $validation['message'],
                ];
            }

            $introducer = $validation['introducer'];

            $lockedMember->introducer_id = $introducer->user_id;
            $lockedMember->save();

            // If current member is already mobile verified, qualify referral immediately
            if ($lockedMember->isMobileVerified()) {
                $lockedMember->qualifyReferral();
            }

            return [
                'success' => true,
                'code' => 200,
                'message' => 'Introducer added successfully.',
                'member' => $lockedMember->fresh(),
                'introducer' => [
                    'id' => $introducer->id,
                    'name' => $introducer->name,
                    'user_id' => $introducer->user_id,
                    'profile_photo' => $introducer->profile_photo,
                    'mobile_verified' => $introducer->isMobileVerified(),
                ],
            ];
        });

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'errors' => ['introducer_id' => [$result['message']]],
            ], $result['code']);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'member' => $result['member'],
            'introducer' => $result['introducer'],
        ]);
    }

    public function updateSettings(Request $request)
    {
        $member = auth('member')->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $member->update($validated);

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Your account settings have been updated.',
                'member' => $member,
            ]);
        }

        return back()->with('success', 'Your account settings have been updated.');
    }

    public function security(Request $request)
    {
        $member = auth('member')->user();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'member' => $member,
            ]);
        }

        return view('member.account.security', [
            'member' => $member,
        ]);
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password:member'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        auth('member')->user()->update([
            'password' => $validated['password'],
        ]);

        $request->session()->regenerate();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Your password has been changed successfully.',
            ]);
        }

        return back()->with('success', 'Your password has been changed successfully.');
    }
}
