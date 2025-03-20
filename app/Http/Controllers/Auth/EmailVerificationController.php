<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmailVerificationController extends Controller
{
    public function notice() {
        return Inertia::render('Auth/VerifyEmail', [
            'status' => session('status')
        ]);
    }

    public function handler(EmailVerificationRequest $request) {
        $request->fulfill();
     
        // Check if the user has verified their ID yet
        $user = $request->user();
        if (!$user->hasVerifiedId()) {
            // Redirect to ID verification with a success message about email
            return redirect()->route('verify-id.show')
                ->with('status', 'Your email has been verified! Please complete your ID verification.');
        }
        
        // If ID is verified but profile isn't complete
        if (!$user->hasProfile()) {
            return redirect()->route('verification.form')
                ->with('status', 'Your email has been verified! Please complete your profile.');
        }
        
        // If everything is verified, go to home
        return redirect()->route('home')->with('status', 'Your email has been verified!');
    }

    public function resend(Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'A new verification link has been sent to your email.');
    }
}