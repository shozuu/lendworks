<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureIdVerification
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()->id_verified_at && $request->route()->getName() !== 'verify-id.show') {
            return redirect()->route('verify-id.show');
        }

        return $next($request);
    }
}
