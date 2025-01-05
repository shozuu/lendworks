<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait ChecksSuspendedUsers
{
    protected function checkIfSuspended()
    {
        if (Auth::user()->status === 'suspended') {
            abort(403, 'Your account has been suspended. You cannot perform any transactions or modifications at this time. Please contact support.');
        }
    }
}