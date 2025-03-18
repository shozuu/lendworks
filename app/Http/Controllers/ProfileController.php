<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProfileController extends Controller
{
   
    public function edit(Request $request) {
        $user = $request->user();
        
        return Inertia::render('Profile/Edit', [
            'user' => $user,
            'profile' => $user->profile,
            'status' => session('status')
        ]);
    }

   public function updateInfo(Request $request) {
    // Validate all fields together
    $fields = $request->validate([
        // User fields
        'name' => ['required', 'max:255'],
        'email' => ['required', 'email', 'max:255',
            Rule::unique(User::class)->ignore($request->user()->id)],
        
    ]);

    // Get the user
    $user = User::with('profile')->find(Auth::id());
    
    // Update user fields
    $userFields = [
        'name' => $fields['name'],
        'email' => $fields['email'],
    ];
    
    $user->fill($userFields);
    
    // Check if email is modified
    if ($user->isDirty('email')) {
        $user->email_verified_at = null;
    }
    
    // Save user changes
    $user->save();
    
   
    
    return redirect()->route('profile.edit')
        ->with('status', 'profile-updated');
}

    public function updatePassword(Request $request) {
        $fields = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:8', 
                'regex:/[a-z]/', 
                'regex:/[A-Z]/', 
                'regex:/[0-9]/',
                'regex:/[@$!%*?&]/'
            ]
        ]);

        $request->user()->update([
            'password' => Hash::make($fields['password'])
        ]);

        return redirect()->route('profile.edit');
    }

    public function destroy(Request $request) {
        $request->validate([
            'password' => ['required', 'current_password']
        ]);

        $user = $request->user();
        
        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
