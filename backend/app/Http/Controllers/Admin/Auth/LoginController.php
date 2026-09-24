<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /**
     * Show Admin Login Form.
     */
    public function showLoginForm()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    /**
     * Handle Admin Login Request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $admin = Admin::where('email', $credentials['email'])->first();

        if (!$admin || !Hash::check($credentials['password'], $admin->password)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Invalid email or password.',
                    'errors' => [
                        'email' => ['Invalid email or password.'],
                    ],
                ], 422);
            }

            return back()->withErrors([
                'email' => 'Invalid email or password.',
            ])->onlyInput('email');
        }

        $remember = $request->boolean('remember');
        Auth::guard('admin')->login($admin, $remember);

        $request->session()->regenerate();

        try {
            $admin->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ])->save();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to update admin last login info: ' . $e->getMessage());
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            $isSuperAdmin = $admin->hasRole('super-admin');
            $roles = $admin->roles()->pluck('slug');
            $permissions = $isSuperAdmin
                ? ['*']
                : $admin->roles()->with('permissions')->get()->flatMap(fn($role) => $role->permissions->pluck('slug'))->unique()->values()->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Logged in successfully.',
                'admin' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'profile_photo' => $admin->profile_photo,
                    'status' => $admin->status,
                    'roles' => $roles,
                    'is_super_admin' => $isSuperAdmin,
                    'permissions' => $permissions,
                ],
            ]);
        }

        return redirect()->route('admin.dashboard');
    }

    /**
     * Handle Admin Logout Request.
     */
    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully.',
            ]);
        }

        return redirect()->route('admin.login');
    }
}
