<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

use function Laravel\Prompts\error;

class AuthenticateController extends Controller
{
    public function create() {
        return Inertia::render('Auth/Login', [
            'status' => session('status')
        ]);
    }

    public function store(Request $request) {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if(Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Redirect admin to dashboard, others to intended or home
            return Auth::user()->isAdmin() 
                ? redirect()->route('admin.dashboard') 
                : redirect()->intended(route('home'));
        }

        return back()->withErrors([
            'failed' => 'The provided credentials do not match our records.'
        ]);
    }

    public function destroy(Request $request) {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
