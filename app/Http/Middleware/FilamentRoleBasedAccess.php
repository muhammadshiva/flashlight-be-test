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

        // Owner type can do everything
        if ($user->type === User::TYPE_OWNER) {
            return $next($request);
        }

        // Admin type restrictions
        if ($user->type === User::TYPE_ADMIN) {
            // Admin can't create, edit, or delete wash transactions
            if (
                str_contains($path, 'admin/wash-transactions/create') ||
                (str_contains($path, 'admin/wash-transactions/') && str_contains($path, '/edit'))
            ) {
                abort(403, 'Admins cannot create or edit wash transactions');
            }

            return $next($request);
        }

        // Cashier type restrictions
        if ($user->type === User::TYPE_CASHIER) {
            // Handle dashboard access specifically
            if ($path === 'admin' || $path === 'admin/dashboard') {
                return $next($request);
            }

            // Allow access to wash-transactions index
            if ($path === 'admin/wash-transactions') {
                return $next($request);
            }

            // Allow access to view individual wash transaction
            if (preg_match('#^admin/wash-transactions/\d+$#', $path)) {
                return $next($request);
            }

            // Block access to create, edit or delete operations
            if (
                str_contains($path, '/create') ||
                str_contains($path, '/edit') ||
                str_contains($path, '/delete')
            ) {
                abort(403, 'Cashiers can only view transactions');
            }

            // Block access to all other resources
            if (!str_starts_with($path, 'admin/wash-transactions')) {
                abort(403, 'Cashiers can only access transactions');
            }

            return $next($request);
        }

        return $next($request);
    }
}
