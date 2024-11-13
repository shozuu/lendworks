<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function edit(Request $request) {
        return Inertia::render('Profile/Edit', [
            'user' => $request->user(),
            'status' => session('status')
        ]);
    }

    public function updateInfo(Request $request) {
        $fields = $request->validate([
            'name' => ['required', 'max:255'],
            'email' => ['required', 'email', 'max:255',
            Rule::unique(User::class)->ignore($request->user()->id)]
        ]);

        $request->user()->fill($fields);

        // check if email is modified
        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return redirect()->route('profile.edit');
    }
}