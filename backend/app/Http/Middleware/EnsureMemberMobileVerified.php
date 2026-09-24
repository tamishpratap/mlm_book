<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMemberMobileVerified
{
    public const UNVERIFIED_MESSAGE = 'Your phone number is not verified. Please complete mobile verification first to continue.';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $member = auth('member')->user();

        if (! $member || ! $member->isMobileVerified()) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => self::UNVERIFIED_MESSAGE,
                    'requires_mobile_verification' => true,
                ], 403);
            }

            return back()->withInput()->with('error', self::UNVERIFIED_MESSAGE);
        }

        return $next($request);
    }
}
