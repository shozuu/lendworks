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
        
        // Allow verification routes to pass through
        if (str_starts_with($path, 'email/verify') || 
            $path === 'verification.notice' ||
            str_starts_with($path, 'verify-id') ||
            str_starts_with($path, 'verification-form')) {
            
            // Check for proper order of verification
            if (str_starts_with($path, 'verify-id') && !$user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }
            
            if (str_starts_with($path, 'verification-form') && !$user->hasVerifiedId()) {
                return redirect()->route('verify-id.show');
            }
            
            return $next($request);
        }
        
        // Check email verification
        if (!$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }
        
        // Check ID verification
        if (!$user->hasVerifiedId()) {
            return redirect()->route('verify-id.show');
        }
        
        // Check profile completion
        if (!$user->hasProfile()) {
            return redirect()->route('verification.form');
        }
        
        return $next($request);
    }
}