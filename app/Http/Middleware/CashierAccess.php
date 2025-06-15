<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CashierAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Log for debugging
        Log::info('Current path: ' . $request->path());
        Log::info('User type: ' . ($user ? $user->type : 'guest'));

        // Allow unauthenticated access to login pages
        if (!$user) {
            return $next($request);
        }

        // Always allow access to logout routes in any panel
        if ($request->is('*/auth/logout') || $request->is('*/logout')) {
            return $next($request);
        }

        // For cashier users
        if ($user->type === User::TYPE_CASHIER) {
            // If they try to access admin panel, redirect to cashier panel
            if ($request->is('admin*') && !$request->is('admin/auth/*')) {
                return redirect('/cashier');
            }
        }
        // For admin and owner users
        else if ($user->type === User::TYPE_OWNER || $user->type === User::TYPE_ADMIN) {
            // If they try to access cashier panel, redirect to admin panel
            if ($request->is('cashier*') && !$request->is('cashier/auth/*')) {
                return redirect('/admin');
            }
        }
        // For other user types
        else {
            // Staff and customers can't access admin or cashier panels
            if ($request->is('admin*') || $request->is('cashier*')) {
                abort(403, 'You do not have access to this area');
            }
        }

        return $next($request);
    }
}
