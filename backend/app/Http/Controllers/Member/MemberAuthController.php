<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Mail\MemberRegistrationOtpMail;
use App\Models\Member;
use App\Models\PendingMemberRegistration;
use App\Services\MemberPhoneNumberService;
use App\Services\MemberUserIdService;
use App\Services\ReferralRelationshipValidator;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class MemberAuthController extends Controller
{
    public function __construct(
        private MemberUserIdService $userIds,
        private ReferralRelationshipValidator $referralValidator,
        private MemberPhoneNumberService $phoneNumbers
    ) {}

    public function showLogin()
    {
        if (Auth::guard('member')->check()) {
            $member = Auth::guard('member')->user();
            if ($member && $member->isBlocked()) {
                Auth::guard('member')->logout();
                if (request()->hasSession()) {
                    request()->session()->invalidate();
                    request()->session()->regenerateToken();
                }

                return redirect()->route('member.login')->with('error', 'Your account has been blocked by the admin. You cannot log in.');
            }

            return redirect()->route('member.dashboard');
        }

        return view('member.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $member = Member::withoutGlobalScopes()->where('email', $credentials['email'])->first();

        // 1. Password verification first: prevent account enumeration / status leakage on bad password
        if (! $member || ! Hash::check($credentials['password'], $member->password)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'The provided email or password is incorrect.',
                    'errors' => [
                        'email' => ['The provided email or password is incorrect.'],
                    ],
                ], 422);
            }

            return back()
                ->withErrors([
                    'email' => 'The provided email or password is incorrect.',
                ])
                ->withInput($request->only('email'));
        }

        // 2. Block status check for confirmed credentials
        if ($member->isBlocked()) {
            Auth::guard('member')->logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            $blockedMsg = 'Your account has been blocked by the admin. You cannot log in.';
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'code' => 'ACCOUNT_BLOCKED',
                    'message' => $blockedMsg,
                    'errors' => [
                        'email' => [$blockedMsg],
                    ],
                ], 403);
            }

            return back()
                ->with('error', $blockedMsg)
                ->withErrors([
                    'email' => $blockedMsg,
                ])
                ->withInput($request->only('email'));
        }

        // 3. Credentials are valid and account is active
        Auth::guard('member')->login($member);

        $authenticatedMember = Auth::guard('member')->user();
        if ($authenticatedMember && $authenticatedMember->isBlocked()) {
            Auth::guard('member')->logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            $blockedMsg = 'Your account has been blocked by the admin. You cannot log in.';
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'code' => 'ACCOUNT_BLOCKED',
                    'message' => $blockedMsg,
                    'errors' => [
                        'email' => [$blockedMsg],
                    ],
                ], 403);
            }

            return back()
                ->with('error', $blockedMsg)
                ->withErrors([
                    'email' => $blockedMsg,
                ])
                ->withInput($request->only('email'));
        }

        $request->session()->regenerate();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Logged in successfully.',
                'member' => Auth::guard('member')->user(),
            ]);
        }

        return redirect()->intended(route('member.dashboard'));
    }

    public function showRegister()
    {
        if (Auth::guard('member')->check()) {
            return redirect()->route('member.dashboard');
        }

        return view('member.auth.register');
    }

    public function register(Request $request)
    {
        if (Auth::guard('member')->check()) {
            return redirect()->route('member.dashboard');
        }

        $rateKey = 'member-register-request:'.$request->ip();
        if (RateLimiter::tooManyAttempts($rateKey, 10)) {
            $seconds = RateLimiter::availableIn($rateKey);
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => "Too many registration attempts. Please try again in {$seconds} seconds.",
                    'errors' => ['email' => ["Too many registration attempts. Please try again in {$seconds} seconds."]],
                ], 429);
            }

            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['email' => "Too many registration attempts. Please try again in {$seconds} seconds."]);
        }
        RateLimiter::hit($rateKey, 60);

        $rawIntroducerId = $request->input('introducer_id');
        $cleanIntroducerId = filled($rawIntroducerId) ? $this->userIds->normalize((string) $rawIntroducerId) : null;

        $rawUserId = $request->input('user_id');
        $name = (string) $request->input('name', '');
        $cleanUserId = filled($rawUserId)
            ? $this->userIds->normalize((string) $rawUserId)
            : (filled($name) ? $this->userIds->generateFromName($name) : null);

        $rawPhone = $request->input('phone') ?? $request->input('mobile_number');
        $countryCode = (string) ($request->input('country_code') ?? '+91');
        $cleanPhone = filled($rawPhone) ? $this->phoneNumbers->normalize((string) $rawPhone, $countryCode) : null;
        $phoneToValidate = $cleanPhone ?? (filled($rawPhone) ? (string) $rawPhone : null);

        $request->merge([
            'user_id' => $cleanUserId,
            'introducer_id' => $cleanIntroducerId,
            'phone' => $phoneToValidate,
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'user_id' => [
                'required',
                'string',
                'size:10',
                'regex:'.MemberUserIdService::FORMAT_PATTERN,
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->userIds->isReserved((string) $value)) {
                        $fail('This User ID is reserved. Please choose another one.');
                    }
                },
                'unique:members,user_id',
            ],
            'introducer_id' => [
                'nullable',
                'string',
                'min:3',
                'max:30',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    if (blank($value)) {
                        return;
                    }
                    $newUserId = (string) $request->input('user_id');
                    $email = (string) $request->input('email');
                    $validation = $this->referralValidator->validate($newUserId, (string) $value, $email);
                    if (! $validation['valid']) {
                        $fail($validation['message']);
                    }
                },
            ],
            'email' => ['required', 'email', 'max:255', 'unique:members,email'],
            'phone' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) use ($cleanPhone): void {
                    if (! $cleanPhone || ! $this->phoneNumbers->isValid($cleanPhone)) {
                        $fail('Please enter a valid WhatsApp/mobile number with country code.');
                        return;
                    }

                    if (Member::where('phone', $cleanPhone)->exists()) {
                        $fail('This number already exists. Please use the existing login option.');
                    }
                },
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:8', 'same:password'],
        ], [
            'user_id.required' => 'A valid 10-character User ID is required.',
            'user_id.size' => 'User ID must be exactly 10 characters.',
            'user_id.regex' => 'User ID must be 4 letters followed by 6 digits (e.g. abcd123456).',
            'user_id.unique' => 'This User ID already exists. Please choose another one.',
            'introducer_id.min' => 'Introducer ID must be at least 3 characters.',
            'introducer_id.max' => 'Introducer ID must not exceed 30 characters.',
            'phone.required' => 'Please enter a WhatsApp/mobile number.',
        ]);

        $otpCode = (string) random_int(100000, 999999);
        $token = Str::random(64);

        // Delete any old pending registrations with this email, phone, or old session token
        $previousToken = $request->session()->get('pending_registration_token');
        if ($previousToken) {
            PendingMemberRegistration::where('token', $previousToken)->delete();
        }
        PendingMemberRegistration::where('email', $validated['email'])->delete();
        PendingMemberRegistration::where('phone', $cleanPhone)->delete();

        PendingMemberRegistration::create([
            'token' => $token,
            'name' => $validated['name'],
            'user_id' => $validated['user_id'],
            'introducer_id' => $validated['introducer_id'] ?? null,
            'email' => $validated['email'],
            'phone' => $cleanPhone,
            'password_hash' => Hash::make($validated['password']),
            'otp_hash' => Hash::make($otpCode),
            'expires_at' => now()->addMinutes(PendingMemberRegistration::EXPIRY_MINUTES),
            'attempts' => 0,
            'last_resend_at' => now(),
        ]);

        $request->session()->put('pending_registration_token', $token);

        Log::info('Member registration OTP generated', [
            'email' => $validated['email'],
            'user_id' => $validated['user_id'],
            'phone' => $this->phoneNumbers->mask($cleanPhone),
        ]);

        try {
            Mail::to($validated['email'])->send(
                new MemberRegistrationOtpMail($otpCode, $validated['name'], $validated['email'])
            );
        } catch (Throwable $e) {
            Log::error('Failed to send registration OTP email', [
                'email' => $validated['email'],
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            PendingMemberRegistration::where('token', $token)->delete();
            $request->session()->forget('pending_registration_token');

            $errorMessage = 'We were unable to send the verification code to your email. Please verify your email address or try again.';

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'errors' => ['email' => [$errorMessage]],
                ], 500);
            }

            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['email' => $errorMessage]);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'A 6-digit verification code has been sent to your email address.',
                'email' => $validated['email'],
                'name' => $validated['name'],
                'resend_cooldown' => 60,
            ]);
        }

        return redirect()->route('member.register.verify')
            ->with('status', 'A 6-digit verification code has been sent to your email address.');
    }

    public function showVerifyEmail(Request $request)
    {
        if (Auth::guard('member')->check()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'authenticated' => true,
                    'member' => Auth::guard('member')->user(),
                ]);
            }
            return redirect()->route('member.dashboard');
        }

        $token = $request->session()->get('pending_registration_token');

        if (! $token) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Please fill in your registration details first.',
                ], 422);
            }
            return redirect()->route('member.register')
                ->withErrors(['email' => 'Please fill in your registration details first.']);
        }

        $pending = PendingMemberRegistration::where('token', $token)->first();

        if (! $pending) {
            $request->session()->forget('pending_registration_token');

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Your registration session has expired. Please register again.',
                ], 422);
            }

            return redirect()->route('member.register')
                ->withErrors(['email' => 'Your registration session has expired. Please register again.']);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'valid' => true,
                'email' => $pending->email,
                'name' => $pending->name,
                'resend_cooldown' => $pending->resendCooldownRemaining(),
                'is_expired' => $pending->isExpired(),
                'has_exceeded_attempts' => $pending->hasExceededAttempts(),
            ]);
        }

        return view('member.auth.verify-email', [
            'email' => $pending->email,
            'name' => $pending->name,
            'resendCooldown' => $pending->resendCooldownRemaining(),
            'isExpired' => $pending->isExpired(),
            'hasExceededAttempts' => $pending->hasExceededAttempts(),
        ]);
    }

    public function verifyEmailOtp(Request $request)
    {
        if (Auth::guard('member')->check()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Already authenticated.',
                    'member' => Auth::guard('member')->user(),
                ]);
            }
            return redirect()->route('member.dashboard');
        }

        $token = $request->session()->get('pending_registration_token');

        if (! $token) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Registration session expired. Please register again.',
                    'errors' => ['email' => ['Registration session expired. Please register again.']],
                ], 422);
            }
            return redirect()->route('member.register')
                ->withErrors(['email' => 'Registration session expired. Please register again.']);
        }

        $pending = PendingMemberRegistration::where('token', $token)->first();

        if (! $pending) {
            $request->session()->forget('pending_registration_token');

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Registration session expired. Please register again.',
                    'errors' => ['email' => ['Registration session expired. Please register again.']],
                ], 422);
            }

            return redirect()->route('member.register')
                ->withErrors(['email' => 'Registration session expired. Please register again.']);
        }

        $rateKey = 'member-register-verify:'.$token;
        if (RateLimiter::tooManyAttempts($rateKey, 10)) {
            $seconds = RateLimiter::availableIn($rateKey);
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => "Too many attempts. Please try again in {$seconds} seconds.",
                    'errors' => ['otp' => ["Too many attempts. Please try again in {$seconds} seconds."]],
                ], 429);
            }
            return back()->withErrors(['otp' => "Too many attempts. Please try again in {$seconds} seconds."]);
        }
        RateLimiter::hit($rateKey, 60);

        $validated = $request->validate([
            'otp' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ], [
            'otp.required' => 'Please enter the 6-digit verification code.',
            'otp.size' => 'The verification code must be exactly 6 digits.',
            'otp.regex' => 'The verification code must contain numbers only.',
        ]);

        if ($pending->isExpired()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'This verification code has expired. Please request a new code.',
                    'errors' => ['otp' => ['This verification code has expired. Please request a new code.']],
                ], 422);
            }
            return back()->withErrors([
                'otp' => 'This verification code has expired. Please request a new code.',
            ]);
        }

        if ($pending->hasExceededAttempts()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Too many incorrect attempts. Please request a new code.',
                    'errors' => ['otp' => ['Too many incorrect attempts. Please request a new code.']],
                ], 422);
            }
            return back()->withErrors([
                'otp' => 'Too many incorrect attempts. Please request a new code.',
            ]);
        }

        if (! Hash::check($validated['otp'], $pending->otp_hash)) {
            $pending->increment('attempts');

            if ($pending->attempts >= PendingMemberRegistration::MAX_ATTEMPTS) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'message' => 'Too many incorrect attempts. Please request a new code.',
                        'errors' => ['otp' => ['Too many incorrect attempts. Please request a new code.']],
                    ], 422);
                }
                return back()->withErrors([
                    'otp' => 'Too many incorrect attempts. Please request a new code.',
                ]);
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'The verification code is incorrect. Please check and try again.',
                    'errors' => ['otp' => ['The verification code is incorrect. Please check and try again.']],
                ], 422);
            }
            return back()->withErrors([
                'otp' => 'The verification code is incorrect. Please check and try again.',
            ]);
        }

        // Check if email, phone, or user_id was registered concurrently
        if (Member::where('email', $pending->email)->exists()) {
            $pending->delete();
            $request->session()->forget('pending_registration_token');

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'This email address is already registered. Please login.',
                    'errors' => ['email' => ['This email address is already registered. Please login.']],
                ], 422);
            }

            return redirect()->route('member.register')->withErrors([
                'email' => 'This email address is already registered. Please login.',
            ]);
        }

        if (filled($pending->phone) && Member::where('phone', $pending->phone)->exists()) {
            $pending->delete();
            $request->session()->forget('pending_registration_token');

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'This number already exists. Please use the existing login option.',
                    'errors' => ['phone' => ['This number already exists. Please use the existing login option.']],
                ], 422);
            }

            return redirect()->route('member.register')->withErrors([
                'phone' => 'This number already exists. Please use the existing login option.',
            ]);
        }

        if (Member::where('user_id', $pending->user_id)->orWhereRaw('UPPER(user_id) = ?', [strtoupper($pending->user_id)])->exists()) {
            $pending->user_id = $this->userIds->generateFromName($pending->name);
            $pending->save();
        }

        try {
            $member = DB::transaction(function () use ($pending) {
                $introducerId = $pending->introducer_id;
                // Revalidate introducer eligibility before final creation
                if ($introducerId) {
                    $validation = $this->referralValidator->validate($pending->user_id, $introducerId, $pending->email);
                    if (! $validation['valid']) {
                        $introducerId = null;
                    }
                }

                $member = Member::create([
                    'name' => $pending->name,
                    'user_id' => $pending->user_id,
                    'introducer_id' => $introducerId,
                    'direct_referral_count' => 0,
                    'referral_counted_at' => null,
                    'email' => $pending->email,
                    'phone' => $pending->phone,
                    'mobile_verified_at' => null,
                    'password' => $pending->password_hash,
                ]);

                // NOTE: Referral qualification & count increment happen upon mobile verification.

                $pending->delete();

                return $member;
            });
        } catch (QueryException $exception) {
            if ($this->isPhoneUniqueViolation($exception)) {
                $pending->delete();
                $request->session()->forget('pending_registration_token');

                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'message' => 'This number already exists. Please use the existing login option.',
                        'errors' => ['phone' => ['This number already exists. Please use the existing login option.']],
                    ], 422);
                }

                return redirect()->route('member.register')->withErrors([
                    'phone' => 'This number already exists. Please use the existing login option.',
                ]);
            }

            if ($this->isUserIdUniqueViolation($exception)) {
                try {
                    $member = DB::transaction(function () use ($pending) {
                        $freshUserId = $this->userIds->generateFromName($pending->name);
                        $introducerId = $pending->introducer_id;
                        if ($introducerId) {
                            $validation = $this->referralValidator->validate($freshUserId, $introducerId, $pending->email);
                            if (! $validation['valid']) {
                                $introducerId = null;
                            }
                        }

                        $member = Member::create([
                            'name' => $pending->name,
                            'user_id' => $freshUserId,
                            'introducer_id' => $introducerId,
                            'direct_referral_count' => 0,
                            'referral_counted_at' => null,
                            'email' => $pending->email,
                            'phone' => $pending->phone,
                            'mobile_verified_at' => null,
                            'password' => $pending->password_hash,
                        ]);

                        $pending->delete();

                        return $member;
                    });
                } catch (QueryException $retryException) {
                    $pending->delete();
                    $request->session()->forget('pending_registration_token');

                    if ($this->isPhoneUniqueViolation($retryException)) {
                        if ($request->expectsJson() || $request->is('api/*')) {
                            return response()->json([
                                'message' => 'This number already exists. Please use the existing login option.',
                                'errors' => ['phone' => ['This number already exists. Please use the existing login option.']],
                            ], 422);
                        }

                        return redirect()->route('member.register')->withErrors([
                            'phone' => 'This number already exists. Please use the existing login option.',
                        ]);
                    }

                    if ($request->expectsJson() || $request->is('api/*')) {
                        return response()->json([
                            'message' => 'A unique User ID could not be assigned. Please try registering again.',
                            'errors' => ['user_id' => ['A unique User ID could not be assigned. Please try registering again.']],
                        ], 422);
                    }

                    return redirect()->route('member.register')->withErrors([
                        'user_id' => 'A unique User ID could not be assigned. Please try registering again.',
                    ]);
                }
            } else {
                throw $exception;
            }
        }

        $request->session()->forget('pending_registration_token');
        RateLimiter::clear($rateKey);

        Auth::guard('member')->login($member);
        $request->session()->regenerate();

        \App\Services\AdminNotificationService::notify(
            title: 'New Member Registered',
            message: sprintf('%s (%s) has registered on MLM Book.', $member->name, $member->user_id),
            icon: 'user',
            sourceType: 'member',
            sourceId: (string) $member->id,
            actionUrl: '/admin/members/' . $member->id,
            metadata: ['member_id' => $member->id, 'user_id' => $member->user_id]
        );

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Account verified successfully.',
                'member' => $member,
            ]);
        }

        return redirect()->route('member.dashboard');
    }

    public function resendRegistrationOtp(Request $request)
    {
        if (Auth::guard('member')->check()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Already authenticated.',
                ]);
            }
            return redirect()->route('member.dashboard');
        }

        $token = $request->session()->get('pending_registration_token');

        if (! $token) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Registration session expired. Please register again.',
                    'errors' => ['email' => ['Registration session expired. Please register again.']],
                ], 422);
            }
            return redirect()->route('member.register')
                ->withErrors(['email' => 'Registration session expired. Please register again.']);
        }

        $pending = PendingMemberRegistration::where('token', $token)->first();

        if (! $pending) {
            $request->session()->forget('pending_registration_token');

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Registration session expired. Please register again.',
                    'errors' => ['email' => ['Registration session expired. Please register again.']],
                ], 422);
            }

            return redirect()->route('member.register')
                ->withErrors(['email' => 'Registration session expired. Please register again.']);
        }

        $rateKey = 'member-resend-otp:'.$token;
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $seconds = RateLimiter::availableIn($rateKey);
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => "Please wait {$seconds} seconds before requesting a new code.",
                    'errors' => ['otp' => ["Please wait {$seconds} seconds before requesting a new code."]],
                ], 429);
            }
            return back()->withErrors(['otp' => "Please wait {$seconds} seconds before requesting a new code."]);
        }
        RateLimiter::hit($rateKey, 60);

        if (! $pending->canResend()) {
            $remaining = $pending->resendCooldownRemaining();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => "Please wait {$remaining} seconds before requesting a new code.",
                    'errors' => ['otp' => ["Please wait {$remaining} seconds before requesting a new code."]],
                ], 422);
            }

            return back()->withErrors([
                'otp' => "Please wait {$remaining} seconds before requesting a new code.",
            ]);
        }

        $otpCode = (string) random_int(100000, 999999);

        $pending->update([
            'otp_hash' => Hash::make($otpCode),
            'expires_at' => now()->addMinutes(PendingMemberRegistration::EXPIRY_MINUTES),
            'attempts' => 0,
            'last_resend_at' => now(),
        ]);

        Log::info('Member registration OTP resent', [
            'email' => $pending->email,
            'user_id' => $pending->user_id,
        ]);

        try {
            Mail::to($pending->email)->send(
                new MemberRegistrationOtpMail($otpCode, $pending->name, $pending->email)
            );
        } catch (Throwable $e) {
            Log::error('Failed to resend registration OTP email', [
                'email' => $pending->email,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            $errorMessage = 'Unable to deliver the verification code to your email at this time. Please try again.';

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'errors' => ['otp' => [$errorMessage]],
                ], 500);
            }

            return back()->withErrors(['otp' => $errorMessage]);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'A new 6-digit verification code has been sent to your email.',
                'resend_cooldown' => 60,
            ]);
        }

        return back()->with('status', 'A new 6-digit verification code has been sent to your email.');
    }

    public function cancelRegistration(Request $request)
    {
        $token = $request->session()->get('pending_registration_token');

        if ($token) {
            PendingMemberRegistration::where('token', $token)->delete();
            $request->session()->forget('pending_registration_token');
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Registration cancelled.',
            ]);
        }

        return redirect()->route('member.register');
    }

    public function checkPhone(Request $request)
    {
        $rawPhone = (string) ($request->query('phone') ?? $request->query('mobile_number') ?? $request->input('phone') ?? $request->input('mobile_number') ?? '');
        $countryCode = (string) ($request->query('country_code') ?? $request->input('country_code') ?? '+91');

        if (blank($rawPhone)) {
            return response()->json([
                'available' => false,
                'message' => 'Please provide a WhatsApp/mobile number.',
            ], 422);
        }

        $cleanPhone = $this->phoneNumbers->normalize($rawPhone, $countryCode);

        if (! $cleanPhone || ! $this->phoneNumbers->isValid($cleanPhone)) {
            return response()->json([
                'available' => false,
                'message' => 'Please enter a valid WhatsApp/mobile number with country code.',
            ], 422);
        }

        $exists = Member::where('phone', $cleanPhone)->exists();

        if ($exists) {
            return response()->json([
                'available' => false,
                'normalized_phone' => $cleanPhone,
                'message' => 'This number already exists. Please use the existing login option.',
            ]);
        }

        return response()->json([
            'available' => true,
            'normalized_phone' => $cleanPhone,
            'message' => 'Phone number is available.',
        ]);
    }

    public function checkUserId(Request $request)
    {
        $userId = $this->userIds->normalize((string) $request->query('user_id'));

        if (! $this->userIds->isValid($userId)) {
            return response()->json([
                'available' => false,
                'normalized_user_id' => $userId,
                'message' => 'Use a 10-character User ID: 4 uppercase letters and 6 digits.',
            ]);
        }

        if ($this->userIds->isReserved($userId)) {
            return response()->json([
                'available' => false,
                'normalized_user_id' => $userId,
                'message' => 'This User ID is reserved.',
            ]);
        }

        try {
            if (! $this->userIds->isAvailable($userId)) {
                return response()->json([
                    'available' => false,
                    'normalized_user_id' => $userId,
                    'message' => 'This User ID already exists.',
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Member checkUserId failed: '.$e->getMessage());

            return response()->json([
                'available' => false,
                'normalized_user_id' => $userId,
                'message' => 'Could not verify User ID. Please try again.',
            ], 500);
        }

        return response()->json([
            'available' => true,
            'normalized_user_id' => $userId,
            'message' => 'User ID is available.',
        ]);
    }

    public function checkIntroducer(Request $request)
    {
        $raw = $request->query('introducer_id', $request->query('ref', $request->query('user_id', '')));
        $clean = $this->userIds->normalize((string) $raw);

        if (blank($clean)) {
            return response()->json([
                'valid' => false,
                'exists' => false,
                'is_eligible' => false,
                'message' => 'No Introducer ID provided.',
            ]);
        }

        $currentMember = auth('member')->user();
        $validation = $this->referralValidator->validate($currentMember, $clean);

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
            'message' => "Introduced by {$introducer->name}",
        ]);
    }

    public function redirectToGoogle(Request $request)
    {
        $ref = $request->query('ref') ?: $request->query('introducer');
        $mode = $request->query('mode') ?: $request->query('intent') ?: 'login';

        $cleanRef = null;
        if ($ref) {
            $cleanRef = strtolower(preg_replace('/[^a-z0-9_]/', '', (string) $ref));
            $request->session()->put('google_oauth_ref', $cleanRef);
        } else {
            $request->session()->forget('google_oauth_ref');
        }

        $request->session()->put('google_oauth_mode', $mode);

        $stateData = [
            'mode' => $mode,
            'ref' => $cleanRef,
        ];

        return Socialite::driver('google')
            ->stateless()
            ->with(['state' => base64_encode(json_encode(array_filter($stateData)))])
            ->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        $frontendUrl = (config('app.frontend_url') ?: env('FRONTEND_URL'));

        if ($request->has('error')) {
            $msg = 'Google authentication was cancelled. Please try again.';
            if ($frontendUrl) {
                return redirect(rtrim($frontendUrl, '/').'/member/login?'.http_build_query(['error' => $msg]));
            }

            return redirect()->route('member.login')->with('error', $msg);
        }

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            $email = $googleUser->getEmail();
            $googleId = $googleUser->getId();

            if (! $email) {
                $msg = 'Google could not provide an email address for this account.';
                if ($frontendUrl) {
                    return redirect(rtrim($frontendUrl, '/').'/member/login?'.http_build_query(['error' => $msg]));
                }

                return redirect()->route('member.login')->with('error', $msg);
            }

            $googleData = $googleUser->user;
            $verifiedEmail = $googleData['email_verified']
                ?? $googleData['verified_email']
                ?? null;

            if ($verifiedEmail !== null && ! filter_var($verifiedEmail, FILTER_VALIDATE_BOOLEAN)) {
                $msg = 'Please use a verified Google email address.';
                if ($frontendUrl) {
                    return redirect(rtrim($frontendUrl, '/').'/member/login?'.http_build_query(['error' => $msg]));
                }

                return redirect()->route('member.login')->with('error', $msg);
            }

            if (! $googleId) {
                $msg = 'Google authentication could not be completed. Please try again.';
                if ($frontendUrl) {
                    return redirect(rtrim($frontendUrl, '/').'/member/login?'.http_build_query(['error' => $msg]));
                }

                return redirect()->route('member.login')->with('error', $msg);
            }

            // Extract mode and ref from session or state
            $mode = $request->session()->pull('google_oauth_mode') ?: 'login';
            $ref = $request->session()->pull('google_oauth_ref');

            if ($request->has('state')) {
                try {
                    $decoded = json_decode(base64_decode((string) $request->input('state')), true);
                    if (is_array($decoded)) {
                        if (! empty($decoded['mode'])) {
                            $mode = $decoded['mode'];
                        }
                        if (! empty($decoded['ref']) && ! $ref) {
                            $ref = strtolower(preg_replace('/[^a-z0-9_]/', '', (string) $decoded['ref']));
                        }
                    }
                } catch (Throwable $e) {
                    // Ignore state decode errors
                }
            }

            $member = Member::where('google_id', $googleId)->first();

            if ($member) {
                if (strtolower(trim((string) $member->email)) !== strtolower(trim((string) $email))) {
                    Log::warning('Google Member email mismatch on google_id lookup', [
                        'member_id' => $member->getKey(),
                        'member_email' => $member->email,
                        'google_email' => $email,
                        'google_id' => $googleId,
                    ]);

                    $msg = 'Google authentication could not be completed. Please try again.';
                    if ($frontendUrl) {
                        return redirect(rtrim($frontendUrl, '/').'/member/login?'.http_build_query(['error' => $msg]));
                    }

                    return redirect()->route('member.login')->with('error', $msg);
                }
            }

            // 1. Existing Member Found by Google ID or Email
            if (! $member) {
                $member = Member::where('email', $email)->first();

                if ($member) {
                    if ($member->google_id && $member->google_id !== $googleId) {
                        Log::warning('Google Member account linking conflict', [
                            'member_id' => $member->getKey(),
                            'request_host' => $request->getHost(),
                            'environment' => app()->environment(),
                        ]);

                        $msg = 'Google authentication could not be completed. Please try again.';
                        if ($frontendUrl) {
                            return redirect(rtrim($frontendUrl, '/').'/member/login?'.http_build_query(['error' => $msg]));
                        }

                        return redirect()->route('member.login')->with('error', $msg);
                    }
                }
            }

            if ($member) {
                // If user was on /member/register attempting to sign up with existing account:
                if ($mode === 'register') {
                    $params = ['error' => 'account_exists'];
                    if ($email) {
                        $params['email'] = $email;
                    }

                    if ($frontendUrl) {
                        return redirect(rtrim($frontendUrl, '/').'/member/register?'.http_build_query($params));
                    }

                    return redirect()->route('member.register', $params)->with('error', 'account_exists');
                }

                // If existing member account is blocked, deny login immediately without mutating state or starting session
                if ($member->isBlocked()) {
                    Auth::guard('member')->logout();
                    if ($request->hasSession()) {
                        $request->session()->invalidate();
                        $request->session()->regenerateToken();
                    }

                    $blockedMsg = 'Your account has been blocked by the admin. You cannot log in.';
                    if ($frontendUrl) {
                        return redirect(rtrim($frontendUrl, '/').'/member/login?'.http_build_query(['error' => $blockedMsg]));
                    }

                    return redirect()->route('member.login')
                        ->with('error', $blockedMsg)
                        ->withErrors([
                            'email' => $blockedMsg,
                        ]);
                }

                // Active account: link google_id if matched by email
                if (! $member->google_id) {
                    $member->google_id = $googleId;
                    $member->save();
                }

                // If user was logging in from /member/login:
                $this->ensureMemberHasUserId($member);

                if (empty($member->profile_photo) && filled($googleUser->getAvatar())) {
                    $member->profile_photo = $googleUser->getAvatar();
                    $member->save();
                }

                Auth::guard('member')->login($member);
                $request->session()->regenerate();

                if ($frontendUrl) {
                    return redirect(rtrim($frontendUrl, '/').'/member/dashboard');
                }

                return redirect()->route('member.dashboard');
            }

            // 2. New / Unregistered Google Identity
            // If user clicked "Login with Google" on /member/login, enforce Signup-First rule:
            if ($mode === 'login') {
                $params = ['error' => 'signup_required'];
                if ($ref) {
                    $params['ref'] = $ref;
                }
                if ($email) {
                    $params['email'] = $email;
                }

                if ($frontendUrl) {
                    return redirect(rtrim($frontendUrl, '/').'/member/login?'.http_build_query($params));
                }

                return redirect()->route('member.login', $params)->with([
                    'error' => 'signup_required',
                    'email' => $email,
                    'ref' => $ref,
                ]);
            }

            // If user clicked "Sign up with Google" on /member/register:
            // Proceed with secure pending signup state and redirect to the Introducer step!
            $token = Str::random(64);
            $name = trim((string) $googleUser->getName()) ?: Str::before($email, '@');

            PendingMemberRegistration::where('email', $email)->delete();
            PendingMemberRegistration::create([
                'token' => $token,
                'name' => $name,
                'user_id' => $this->userIds->generateFromName($name),
                'introducer_id' => $ref ?: null,
                'email' => $email,
                'password_hash' => Hash::make(Str::random(40)),
                'otp_hash' => $googleId,
                'expires_at' => now()->addMinutes(30),
            ]);

            $pendingData = [
                'name' => $name,
                'email' => $email,
                'google_id' => $googleId,
                'avatar' => $googleUser->getAvatar(),
                'ref' => $ref ?: null,
                'created_at' => now()->timestamp,
            ];

            Cache::put('pending_google_signup:'.$token, $pendingData, now()->addMinutes(30));
            $request->session()->put('pending_google_signup_token', $token);

            $params = ['token' => $token];
            if ($ref) {
                $params['ref'] = $ref;
            }

            if ($frontendUrl) {
                return redirect(rtrim($frontendUrl, '/').'/member/google-introducer?'.http_build_query($params));
            }

            return redirect()->route('member.google.introducer', $params);
        } catch (Throwable $e) {
            Log::error('Google Member authentication failed', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'request_url' => $request->fullUrlWithoutQuery([
                    'code',
                    'state',
                ]),
                'request_host' => $request->getHost(),
                'app_url' => config('app.url'),
                'redirect_uri' => config('services.google.redirect'),
                'session_started' => $request->hasSession(),
                'session_has_id' => $request->hasSession()
                    && filled($request->session()->getId()),
                'environment' => app()->environment(),
            ]);

            $msg = 'Google authentication could not be completed. Please try again.';
            if ($frontendUrl) {
                return redirect(rtrim($frontendUrl, '/').'/member/login?'.http_build_query(['error' => $msg]));
            }

            return redirect()->route('member.login')->with(
                'error',
                $msg
            );
        }
    }

    /**
     * Get pending Google signup metadata.
     */
    public function getPendingGoogleSignup(Request $request)
    {
        $token = $request->query('token') ?: $request->session()->get('pending_google_signup_token');

        if (! $token) {
            return response()->json([
                'message' => 'Signup session token is required.',
            ], 422);
        }

        $pending = PendingMemberRegistration::where('token', $token)->first();
        $cachedData = Cache::get('pending_google_signup:'.$token);

        if (! $pending && ! $cachedData) {
            return response()->json([
                'message' => 'Your Google signup session has expired or is invalid. Please sign in with Google again.',
            ], 404);
        }

        if ($pending && $pending->isExpired()) {
            $pending->delete();
            Cache::forget('pending_google_signup:'.$token);

            return response()->json([
                'message' => 'Your Google signup session has expired. Please sign in with Google again.',
            ], 404);
        }

        $name = $pending ? $pending->name : $cachedData['name'];
        $email = $pending ? $pending->email : $cachedData['email'];
        $ref = $pending ? $pending->introducer_id : ($cachedData['ref'] ?? null);
        $avatar = $cachedData['avatar'] ?? null;
        $phone = $pending ? $pending->phone : ($cachedData['phone'] ?? null);

        $introducerInfo = null;
        if (! empty($ref)) {
            $introducer = Member::where('user_id', $ref)->first();
            if ($introducer) {
                $introducerInfo = [
                    'exists' => true,
                    'valid' => $introducer->isMobileVerified() && ! $introducer->isBlocked(),
                    'user_id' => $introducer->user_id,
                    'name' => $introducer->name,
                    'is_eligible' => $introducer->isMobileVerified(),
                    'message' => $introducer->isMobileVerified()
                        ? "Introduced by {$introducer->name}"
                        : 'The selected Introducer is not eligible to refer members until their mobile number is verified.',
                ];
            } else {
                $introducerInfo = [
                    'exists' => false,
                    'valid' => false,
                    'user_id' => $ref,
                    'name' => null,
                    'is_eligible' => false,
                    'message' => 'The selected Introducer ID does not exist.',
                ];
            }
        }

        return response()->json([
            'success' => true,
            'name' => $name,
            'email' => $email,
            'avatar' => $avatar,
            'ref' => $ref,
            'phone' => $phone,
            'introducer' => $introducerInfo,
        ]);
    }

    /**
     * Complete Google signup with required Phone/WhatsApp and optional Introducer ID.
     */
    public function completeGoogleSignup(Request $request)
    {
        $rawPhone = $request->input('phone') ?? $request->input('mobile_number');
        $countryCode = (string) ($request->input('country_code') ?? '+91');
        $cleanPhone = filled($rawPhone) ? $this->phoneNumbers->normalize((string) $rawPhone, $countryCode) : null;
        $phoneToValidate = $cleanPhone ?? (filled($rawPhone) ? (string) $rawPhone : null);

        $request->merge([
            'phone' => $phoneToValidate,
        ]);

        $request->validate([
            'token' => 'required|string',
            'phone' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) use ($cleanPhone): void {
                    if (! $cleanPhone || ! $this->phoneNumbers->isValid($cleanPhone)) {
                        $fail('Please enter a valid WhatsApp/mobile number with country code.');
                        return;
                    }

                    if (Member::where('phone', $cleanPhone)->exists()) {
                        $fail('This number already exists. Please use the existing login option.');
                    }
                },
            ],
            'introducer_id' => 'nullable|string|max:30',
        ], [
            'phone.required' => 'Please enter a WhatsApp/mobile number.',
        ]);

        $token = $request->input('token');
        $pending = PendingMemberRegistration::where('token', $token)->first();
        $cachedData = Cache::get('pending_google_signup:'.$token);

        if (! $pending && ! $cachedData) {
            return response()->json([
                'message' => 'Your Google signup session has expired. Please sign in with Google again.',
            ], 422);
        }

        if ($pending && $pending->isExpired()) {
            $pending->delete();
            Cache::forget('pending_google_signup:'.$token);

            return response()->json([
                'message' => 'Your Google signup session has expired. Please sign in with Google again.',
            ], 422);
        }

        $email = $pending ? $pending->email : $cachedData['email'];
        $name = $pending ? $pending->name : $cachedData['name'];
        $googleId = $cachedData['google_id'] ?? ($pending ? $pending->otp_hash : null);

        // Replay / existing member check
        $existingMember = Member::where('google_id', $googleId)->orWhere('email', $email)->first();
        if ($existingMember) {
            if ($existingMember->isBlocked()) {
                Auth::guard('member')->logout();
                if ($request->hasSession()) {
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }

                $blockedMsg = 'Your account has been blocked by the admin. You cannot log in.';
                return response()->json([
                    'success' => false,
                    'code' => 'ACCOUNT_BLOCKED',
                    'message' => $blockedMsg,
                    'errors' => [
                        'email' => [$blockedMsg],
                    ],
                ], 403);
            }

            if ($pending) {
                $pending->delete();
            }
            Cache::forget('pending_google_signup:'.$token);
            $request->session()->forget('pending_google_signup_token');
            Auth::guard('member')->login($existingMember);
            $request->session()->regenerate();

            return response()->json([
                'success' => true,
                'message' => 'Logged in successfully.',
                'member' => $existingMember,
                'redirect' => '/member/dashboard',
            ]);
        }

        $finalIntroducerId = null;
        $rawIntroducerId = trim((string) $request->input('introducer_id', ''));

        if (filled($rawIntroducerId)) {
            $cleanIntroducerId = $this->userIds->normalize($rawIntroducerId);
            $pendingUserId = $pending ? $pending->user_id : null;
            $validation = $this->referralValidator->validate($pendingUserId, $cleanIntroducerId, $email);

            if (! $validation['valid']) {
                return response()->json([
                    'message' => $validation['message'],
                    'errors' => ['introducer_id' => [$validation['message']]],
                ], 422);
            }

            $finalIntroducerId = $validation['introducer']->user_id;
        }

        $avatar = $cachedData['avatar'] ?? null;

        // Atomic Member creation
        try {
            $member = $this->createGoogleMember($name, $email, $googleId, $cleanPhone, $finalIntroducerId, $avatar);
        } catch (QueryException $exception) {
            if ($this->isPhoneUniqueViolation($exception)) {
                return response()->json([
                    'message' => 'This number already exists. Please use the existing login option.',
                    'errors' => ['phone' => ['This number already exists. Please use the existing login option.']],
                ], 422);
            }
            throw $exception;
        }

        \App\Services\AdminNotificationService::notify(
            title: 'New Member Registered',
            message: sprintf('%s (%s) has registered via Google.', $member->name, $member->user_id),
            icon: 'user',
            sourceType: 'member',
            sourceId: (string) $member->id,
            actionUrl: '/admin/members/' . $member->id,
            metadata: ['member_id' => $member->id, 'user_id' => $member->user_id, 'phone' => $member->phone]
        );

        // Clear pending token so it cannot be reused
        if ($pending) {
            $pending->delete();
        }
        Cache::forget('pending_google_signup:'.$token);
        $request->session()->forget('pending_google_signup_token');

        return response()->json([
            'success' => true,
            'message' => 'Your account has been created successfully. You can now log in.',
            'member' => [
                'name' => $member->name,
                'email' => $member->email,
                'user_id' => $member->user_id,
                'phone' => $member->phone,
            ],
            'redirect' => '/member/login?success=account_created',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('member')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully.',
            ]);
        }

        return redirect()->route('member.login');
    }

    private function createGoogleMember(string $name, string $email, string $googleId, string $phone, ?string $introducerId = null, ?string $avatar = null): Member
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                return Member::create([
                    'name' => $name,
                    'user_id' => $this->userIds->generateFromName($name),
                    'email' => $email,
                    'google_id' => $googleId,
                    'phone' => $phone,
                    'profile_photo' => $avatar,
                    'password' => Str::random(40),
                    'introducer_id' => $introducerId,
                    'direct_referral_count' => 0,
                    'referral_counted_at' => null,
                ]);
            } catch (QueryException $exception) {
                if ($this->isPhoneUniqueViolation($exception)) {
                    throw $exception;
                }
                if (! $this->isUserIdUniqueViolation($exception)) {
                    throw $exception;
                }
            }
        }

        throw new \RuntimeException('A unique User ID could not be assigned to the Google Member.');
    }

    private function ensureMemberHasUserId(Member $member): void
    {
        if (filled($member->user_id)) {
            return;
        }

        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                $member->user_id = $this->userIds->generateFromName($member->name);
                $member->save();

                return;
            } catch (QueryException $exception) {
                if (! $this->isUserIdUniqueViolation($exception)) {
                    throw $exception;
                }
            }
        }

        throw new \RuntimeException('A unique User ID could not be assigned to the existing Member.');
    }

    private function isUserIdUniqueViolation(QueryException $exception): bool
    {
        $details = Str::lower($exception->getMessage().' '.implode(' ', $exception->errorInfo ?? []));

        return in_array((string) $exception->getCode(), ['23000', '23505'], true)
            && (
                str_contains($details, 'members_user_id_unique')
                || str_contains($details, 'members.user_id')
                || (str_contains($details, 'unique') && str_contains($details, 'user_id'))
            );
    }

    private function isPhoneUniqueViolation(QueryException $exception): bool
    {
        $details = Str::lower($exception->getMessage().' '.implode(' ', $exception->errorInfo ?? []));

        return in_array((string) $exception->getCode(), ['23000', '23505'], true)
            && (
                str_contains($details, 'members_phone_unique')
                || str_contains($details, 'members.phone')
                || (str_contains($details, 'unique') && str_contains($details, 'phone'))
            );
    }
}
