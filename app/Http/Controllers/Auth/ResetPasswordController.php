<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    public function requestPass() {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status')
        ]);
    }
    
    public function sendEmail(Request $request) {
        $request->validate(['email' => 'required|email']);
         
        $status = Password::sendResetLink(
            $request->only('email')
        );
        
        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __($status)]) // success
            : back()->withErrors(['email' => __($status)]); // failure
    }


}
