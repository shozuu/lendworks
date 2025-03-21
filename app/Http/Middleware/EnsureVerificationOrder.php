<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class EnsureVerificationOrder
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If trying to access liveness verification without ID verification first
        if ($request->routeIs('verify-liveness.show') && !Session::has('id_verification_data')) {
            return redirect()->route('verify-id.show')
                ->with('error', 'Please complete ID verification first');
        }

        // If trying to access the verification form without completing ID verification
        if ($request->routeIs('verification.form') && !$request->user()->hasVerifiedId()) {
            return redirect()->route('verify-id.show')
                ->with('error', 'Please complete the verification process first');
        }

        return $next($request);
    }
}