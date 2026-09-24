<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastSeenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth('member')->check()) {
            $member = auth('member')->user();

            if ($member && $member->isBlocked()) {
                auth('member')->logout();

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

                return redirect()->route('member.login')
                    ->with('error', $blockedMsg)
                    ->withErrors([
                        'email' => $blockedMsg,
                    ]);
            }

            if (! $member->last_seen_at || $member->last_seen_at->lt(now()->subMinute())) {
                $member->update(['last_seen_at' => now()]);
            }
        }

        return $next($request);
    }
}
