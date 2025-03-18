<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function edit(Request $request) {
        $user = $request->user();
        $profile = Profile::where('user_id', $user->id)->first();
    
        return Inertia::render('Profile/Edit', [
            'user' => $request->user(),
            'profile' => Auth::user()->profile,
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
        
        // Profile fields
        'firstName' => ['nullable', 'string', 'max:100'],
        'lastName' => ['nullable', 'string', 'max:100'], 
        'barangay' => ['nullable', 'string', 'max:100'],
        'city' => ['nullable', 'string', 'max:100'],
    ]);

    // Get the user
    $user = $request->user();
    
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
    
    Profile::updateOrCreate(
        ['user_id' => $user->id],
        [
            'first_name' => $fields['firstName'],
            'last_name' => $fields['lastName'],
            'barangay' => $fields['barangay'],
            'city' => $fields['city'],
        ]
    );
    
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
