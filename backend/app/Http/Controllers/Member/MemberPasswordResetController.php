<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Mail\MemberResetPasswordMail;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Throwable;

class MemberPasswordResetController extends Controller
{
    public function showForgotPassword()
    {
        if (Auth::guard('member')->check()) {
            return redirect()->route('member.dashboard');
        }

        return view('member.auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        if (Auth::guard('member')->check()) {
            return redirect()->route('member.dashboard');
        }

        $key = 'forgot-password:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Too many reset attempts. Please try again in '.$seconds.' seconds.',
                    'errors' => ['email' => ['Too many reset attempts. Please try again in '.$seconds.' seconds.']],
                ], 429);
            }

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Too many reset attempts. Please try again in '.$seconds.' seconds.',
                ]);
        }

        RateLimiter::hit($key, 60);

        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
        ]);

        $member = Member::where('email', $request->email)->first();

        if (! $member) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'No account found with this email.',
                    'errors' => ['email' => ['No account found with this email.']],
                ], 422);
            }

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'No account found with this email.',
                ]);
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $member->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        try {
            Mail::to($member->email)->send(new MemberResetPasswordMail($member, $token));
        } catch (Throwable $e) {
            Log::error('Failed to send password reset email', [
                'email' => $member->email,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            DB::table('password_reset_tokens')->where('email', $member->email)->delete();

            $errorMessage = 'We were unable to send the password reset email. Please try again later.';

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'errors' => ['email' => [$errorMessage]],
                ], 500);
            }

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => $errorMessage]);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'A password reset link has been sent to your email address.',
            ]);
        }

        return back()->with('status', 'A password reset link has been sent to your email address.');
    }

    public function showResetPassword(Request $request, string $token)
    {
        if (Auth::guard('member')->check()) {
            return redirect()->route('member.dashboard');
        }

        $email = (string) $request->query('email', '');

        return view('member.auth.reset-password', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    public function resetPassword(Request $request)
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

        $key = 'reset-password:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Too many reset attempts. Please try again in '.$seconds.' seconds.',
                    'errors' => ['email' => ['Too many reset attempts. Please try again in '.$seconds.' seconds.']],
                ], 429);
            }

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Too many reset attempts. Please try again in '.$seconds.' seconds.',
                ]);
        }

        RateLimiter::hit($key, 60);

        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'password.required' => 'Please enter a new password.',
            'password.min' => 'Password must be at least 8 characters long.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->first();

        if (! $record || ! Hash::check($validated['token'], $record->token)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'This password reset link is invalid or has expired.',
                    'errors' => ['email' => ['This password reset link is invalid or has expired.']],
                ], 422);
            }

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'This password reset link is invalid or has expired.',
                ]);
        }

        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'This password reset link has expired. Please request a new one.',
                    'errors' => ['email' => ['This password reset link has expired. Please request a new one.']],
                ], 422);
            }

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'This password reset link has expired. Please request a new one.',
                ]);
        }

        $member = Member::where('email', $validated['email'])->first();

        if (! $member) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'No account found with this email address.',
                    'errors' => ['email' => ['No account found with this email address.']],
                ], 422);
            }

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'No account found with this email address.',
                ]);
        }

        $member->password = Hash::make($validated['password']);
        $member->save();

        DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();
        RateLimiter::clear($key);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Your password has been changed successfully.',
            ]);
        }

        return redirect()->route('member.login')
            ->with('success', 'Your password has been changed successfully.');
    }
}
