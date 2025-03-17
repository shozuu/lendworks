<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next)
    {
        // Skip check for admin routes and admin users
        if ($request->is('admin/*') || auth()->user()?->is_admin) {
            return $next($request);
        }

        // Check maintenance mode from cache
        if (Cache::get('maintenance_mode', false)) {
            // Return maintenance view as a full page response
            return response(view('maintenance'), 503);
        }

        return $next($request);
    }
}
