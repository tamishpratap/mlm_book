<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Mail\MemberRegistrationOtpMail;
use App\Models\Admin;
use App\Models\Member;
use App\Models\MobileAccessToken;
use App\Models\PendingMemberRegistration;
use App\Services\MemberUserIdService;
use App\Services\ReferralRelationshipValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MobileAuthController extends Controller
{
    public function __construct(
        private readonly MemberUserIdService $userIds,
        private readonly ReferralRelationshipValidator $referralValidator,
    ) {}

    public function memberLogin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $loginInput = trim($validated['email']);
        $member = Member::withoutGlobalScopes()
            ->where(function ($query) use ($loginInput) {
                $query->where('email', $loginInput)
                      ->orWhere('user_id', $loginInput);
            })
            ->first();

        if (! $member || ! Hash::check($validated['password'], $member->password)) {
            return response()->json(['message' => 'The provided email or password is incorrect.'], 422);
        }
        if ($member->isBlocked()) {
            return response()->json(['message' => 'Your account has been blocked by an administrator.'], 403);
        }

        $deviceName = $validated['device_name'] ?? 'Mobile App';

        return response()->json([
            'success' => true,
            'token' => $this->issueToken('member', $member->id, $deviceName, $request),
            'member' => $this->memberPayload($member),
        ]);
    }

    public function adminLogin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $admin = Admin::query()->where('email', trim($validated['email']))->first();
        if (! $admin || ! Hash::check($validated['password'], $admin->password)) {
            return response()->json(['message' => 'Invalid email or password.'], 422);
        }

        $deviceName = $validated['device_name'] ?? 'Mobile Admin App';

        return response()->json([
            'success' => true,
            'token' => $this->issueToken('admin', $admin->id, $deviceName, $request),
            'admin' => $this->adminPayload($admin),
        ]);
    }

    /** Start the same email-OTP onboarding used by the web app, without a cookie session. */
    public function memberRegister(Request $request): JsonResponse
    {
        $rawUserId = (string) $request->input('user_id', '');
        $name = (string) $request->input('name', '');
        $userId = filled($rawUserId) ? $this->userIds->normalize($rawUserId) : $this->userIds->generateFromName($name);
        $introducerId = filled($request->input('introducer_id'))
            ? $this->userIds->normalize((string) $request->input('introducer_id'))
            : null;
        $request->merge(['user_id' => $userId, 'introducer_id' => $introducerId]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'user_id' => ['required', 'string', 'size:10', 'regex:'.MemberUserIdService::FORMAT_PATTERN, 'unique:members,user_id'],
            'introducer_id' => ['nullable', 'string', 'min:3', 'max:30', function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                if (blank($value)) {
                    return;
                }
                $result = $this->referralValidator->validate((string) $request->input('user_id'), (string) $value, (string) $request->input('email'));
                if (! $result['valid']) {
                    $fail($result['message']);
                }
            }],
            'email' => ['required', 'email', 'max:255', 'unique:members,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $otp = (string) random_int(100000, 999999);
        $registrationToken = Str::random(64);
        PendingMemberRegistration::where('email', $validated['email'])->delete();
        PendingMemberRegistration::create([
            'token' => $registrationToken,
            'name' => $validated['name'],
            'user_id' => $validated['user_id'],
            'introducer_id' => $validated['introducer_id'],
            'email' => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(PendingMemberRegistration::EXPIRY_MINUTES),
            'attempts' => 0,
            'last_resend_at' => now(),
        ]);

        Mail::to($validated['email'])->send(new MemberRegistrationOtpMail($otp, $validated['name'], $validated['email']));

        return response()->json([
            'success' => true,
            'message' => 'A verification code has been sent to your email address.',
            'registration_token' => $registrationToken,
            'expires_in_minutes' => PendingMemberRegistration::EXPIRY_MINUTES,
        ], 201);
    }

    public function verifyMemberRegistration(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'registration_token' => ['required', 'string', 'size:64'],
            'otp' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
            'device_name' => ['required', 'string', 'max:120'],
        ]);
        $pending = PendingMemberRegistration::where('token', $validated['registration_token'])->first();
        if (! $pending || $pending->isExpired()) {
            $pending?->delete();
            return response()->json(['message' => 'Registration code has expired. Please register again.'], 422);
        }
        if ($pending->hasExceededAttempts()) {
            return response()->json(['message' => 'Too many incorrect attempts. Please request a new code.'], 422);
        }
        if (! Hash::check($validated['otp'], $pending->otp_hash)) {
            $pending->increment('attempts');
            return response()->json(['message' => 'The verification code is incorrect.'], 422);
        }
        if (Member::where('email', $pending->email)->exists() || Member::whereRaw('UPPER(user_id) = ?', [strtoupper($pending->user_id)])->exists()) {
            $pending->delete();
            return response()->json(['message' => 'This email or User ID is already registered.'], 422);
        }

        $member = DB::transaction(function () use ($pending) {
            $introducerId = $pending->introducer_id;
            if ($introducerId && ! $this->referralValidator->validate($pending->user_id, $introducerId, $pending->email)['valid']) {
                $introducerId = null;
            }
            $member = Member::create([
                'name' => $pending->name,
                'user_id' => $pending->user_id,
                'introducer_id' => $introducerId,
                'direct_referral_count' => 0,
                'email' => $pending->email,
                'password' => $pending->password_hash,
            ]);
            $pending->delete();
            return $member;
        });

        return response()->json([
            'success' => true,
            'token' => $this->issueToken('member', $member->id, $validated['device_name'], $request),
            'member' => $this->memberPayload($member),
            'message' => 'Your account has been created. Verify your mobile number to unlock all member actions.',
        ], 201);
    }

    public function me(Request $request): JsonResponse
    {
        $actor = $request->attributes->get('mobile_actor');
        $audience = $request->attributes->get('mobile_token')->audience;

        return response()->json([
            'success' => true,
            'audience' => $audience,
            $audience => $audience === 'member' ? $this->memberPayload($actor) : $this->adminPayload($actor),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('mobile_token')?->delete();
        return response()->json(['success' => true, 'message' => 'Mobile session closed.']);
    }

    private function issueToken(string $audience, int $actorId, string $deviceName, Request $request): string
    {
        $plainToken = bin2hex(random_bytes(40));
        MobileAccessToken::create([
            'audience' => $audience,
            'actor_id' => $actorId,
            'token_hash' => hash('sha256', $plainToken),
            'device_name' => trim($deviceName),
            'last_ip' => $request->ip(),
            'last_used_at' => now(),
            'expires_at' => now()->addDays(90),
        ]);
        return $plainToken;
    }

    private function memberPayload(Member $member): array
    {
        return [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'user_id' => $member->user_id,
            'phone' => $member->phone,
            'avatar_url' => $member->avatar_url,
            'is_verified' => $member->isMobileVerified(),
            'direct_referral_count' => (int) $member->direct_referral_count,
            'wallet' => (float) ($member->wallet ?? 0.00),
            'p2p_wallet' => (float) ($member->p2p_wallet ?? 0.00),
            'ad_balance' => (float) ($member->p2p_wallet ?? 0.00),
            'reward_balance' => (float) ($member->wallet ?? 0.00),
        ];
    }

    private function adminPayload(Admin $admin): array
    {
        $isSuperAdmin = $admin->hasRole('super-admin');
        return [
            'id' => $admin->id,
            'name' => $admin->name,
            'email' => $admin->email,
            'profile_photo' => $admin->profile_photo,
            'roles' => $admin->roles()->pluck('slug')->values()->all(),
            'permissions' => $isSuperAdmin ? ['*'] : $admin->roles()->with('permissions')->get()->flatMap(fn ($role) => $role->permissions->pluck('slug'))->unique()->values()->all(),
            'is_super_admin' => $isSuperAdmin,
        ];
    }
}
