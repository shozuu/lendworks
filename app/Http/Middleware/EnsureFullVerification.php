<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureFullVerification
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $path = $request->path();

         // List of routes that always require full verification
        $restrictedRoutes = [
            'listing.create',
            'listing.store',
            'rentals.store',
            'rental-request'
        ];
        
        // Allow verification routes to pass through
        if (str_starts_with($path, 'email/verify') || 
        $path === 'verification.notice' ||
        str_starts_with($path, 'verify-id') ||
        str_starts_with($path, 'verification-form')) {
        return $next($request);
    }
        // Check if the current route requires verification
    $currentRoute = $request->route()->getName();
    $requiresVerification = false;
    
    foreach ($restrictedRoutes as $route) {
        if (str_contains($currentRoute, $route)) {
            $requiresVerification = true;
            break;
        }
    }
    
    // Only perform verification checks if the route requires it
    if ($requiresVerification) {
        // Check email verification
        if (!$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }
        
        // Check ID verification
        if (!$user->hasVerifiedId()) {
            return redirect()->route('verify-id.show')
                ->with('message', 'Please verify your ID before continuing.');
        }
        
        // Check profile completion
        if (!$user->hasProfile()) {
            return redirect()->route('verification.form');
        }
    }
    
    return $next($request);
}
}