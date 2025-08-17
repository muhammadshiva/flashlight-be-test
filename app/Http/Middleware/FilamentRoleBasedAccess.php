<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class FilamentRoleBasedAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        $path = $request->path();

        // Log the current path for debugging
        \Illuminate\Support\Facades\Log::info('Current path: ' . $path);
        \Illuminate\Support\Facades\Log::info('User type: ' . $user->type);
        \Illuminate\Support\Facades\Log::info('User roles: ' . $user->roles->pluck('name')->implode(', '));

        // Always allow access to authentication paths (logout, profile, etc)
        if (
            str_contains($path, 'admin/logout') ||
            str_contains($path, 'admin/profile') ||
            str_contains($path, 'admin/auth')
        ) {
            return $next($request);
        }

        // Staff and Customer users can't access admin panel
        if ($user->type === User::TYPE_STAFF || $user->type === User::TYPE_CUSTOMER) {
            abort(403, 'Staff and customers cannot access the admin dashboard');
        }

        // Owner and Admin types can access everything in admin panel
        if ($user->type === User::TYPE_OWNER || $user->type === User::TYPE_ADMIN) {
            return $next($request);
        }

        // Cashier type restrictions - only allow limited access
        if ($user->type === User::TYPE_CASHIER) {
            // Allow access to dashboard and wash transactions
            if (
                $path === 'admin' ||
                $path === 'admin/dashboard' ||
                str_starts_with($path, 'admin/wash-transactions')
            ) {
                return $next($request);
            }

            // Block access to all other resources
            abort(403, 'Cashiers can only access dashboard and wash transactions');
        }

        return $next($request);
    }
}
