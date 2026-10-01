<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Models\Member;
use App\Models\MobileAccessToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMobileToken
{
    /**
     * Handle an incoming mobile API request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $requiredAudience = null): Response
    {
        $token = $request->bearerToken();

        if (blank($token)) {
            $token = $request->header('X-Mobile-Token') ?? $request->query('token');
        }

        if (blank($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated mobile request. Please log in.',
            ], 401);
        }

        $tokenHash = hash('sha256', trim($token));
        $tokenRecord = MobileAccessToken::where('token_hash', $tokenHash)->first();

        if (! $tokenRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired mobile session token.',
            ], 401);
        }

        if ($tokenRecord->expires_at && $tokenRecord->expires_at->isPast()) {
            $tokenRecord->delete();
            return response()->json([
                'success' => false,
                'message' => 'Mobile session expired. Please log in again.',
            ], 401);
        }

        if ($requiredAudience && $tokenRecord->audience !== $requiredAudience) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized audience for this endpoint.',
            ], 403);
        }

        // Resolve the actor based on audience
        $actor = null;
        if ($tokenRecord->audience === 'admin') {
            $actor = Admin::find($tokenRecord->actor_id);
            if (! $actor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin account not found.',
                ], 401);
            }
            if (auth()->guard('admin')) {
                auth('admin')->setUser($actor);
            }
        } else {
            $actor = Member::find($tokenRecord->actor_id);
            if (! $actor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member account not found.',
                ], 401);
            }
            if ($actor->isBlocked()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been blocked by an administrator.',
                ], 403);
            }
            if (auth()->guard('member')) {
                auth('member')->setUser($actor);
            }
        }

        // Update token activity asynchronously or directly
        $tokenRecord->update([
            'last_used_at' => now(),
            'last_ip' => $request->ip(),
        ]);

        // Attach resolved actor and token record to request attributes
        $request->attributes->set('mobile_actor', $actor);
        $request->attributes->set('mobile_token', $tokenRecord);
        $request->setUserResolver(fn () => $actor);

        return $next($request);
    }
}
