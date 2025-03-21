<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Profile;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;


class VerificationFormController extends Controller
{
    public function show()
    {
        // Check if the user is ID-verified
        if (!auth()->user()->hasVerifiedId()) {
            return redirect()->route('verify-id.show')
                ->with('error', 'Please complete ID verification first');
        }
        
        return Inertia::render('Auth/VerificationForm');
    }
    
    public function extractData()
{
    $extractedData = Session::get('verification_extracted_data', []);
    
    // Retain only ID types, nationality, and email
    $filteredData = [
        // Keep these fields from extracted data if they exist
        'primaryIdType' => $extractedData['primaryIdType'] ?? '',
        'secondaryIdType' => $extractedData['secondaryIdType'] ?? '',
        'nationality' => $extractedData['nationality'] ?? 'Filipino', // Default to Filipino
        'email' => $extractedData['email'] ?? auth()->user()->email ?? '',
        
        // Empty all other fields
        'firstName' => '',
        'middleName' => '',
        'lastName' => '',
        'birthdate' => '',
        'gender' => '',
        'mobileNumber' => '',
        'streetAddress' => '',
        'barangay' => '',
        'city' => '',
        'province' => '',
        'postalCode' => '',
        'civilStatus' => '',
    ];

    Log::info('Returning filtered data with only ID types, nationality, and email', $filteredData);
    
    return response()->json($filteredData);
}

    public function submit(Request $request)
    {
        // Form validation
        $validated = $request->validate([
            'firstName' => 'required|string|max:100',
            'middleName' => 'nullable|string|max:100',
            'lastName' => 'required|string|max:100',
            'birthdate' => 'required|date',
            'gender' => 'required|string|in:male,female,other',
            'civilStatus' => 'required|string',
            'mobileNumber' => 'required|string|max:15',
            'email' => 'required|email',
            'streetAddress' => 'required|string|max:255',
            'barangay' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'postalCode' => 'required|string|max:10',
            'nationality' => 'required|string|max:100',
            'primaryIdType' => 'required|string|max:50', 
            'secondaryIdType' => 'required|string|max:50',
        ]);
        
        // Get current user
        $user = $request->user();
        
        // Create or update the user's profile
        $profile = Profile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => $validated['firstName'],
                'middle_name' => $validated['middleName'],
                'last_name' => $validated['lastName'],
                'birthdate' => $validated['birthdate'],
                'gender' => $validated['gender'],
                'civil_status' => $validated['civilStatus'],
                'mobile_number' => $validated['mobileNumber'],
                'street_address' => $validated['streetAddress'],
                'barangay' => $validated['barangay'],
                'city' => $validated['city'],
                'province' => $validated['province'],
                'postal_code' => $validated['postalCode'],
                'nationality' => $validated['nationality'],
                'primary_id_type' => $validated['primaryIdType'] ?? null,
                'secondary_id_type' => $validated['secondaryIdType'] ?? null,
            ]
        );
        
        // Update the user's email if it's different
        if ($user->email !== $validated['email']) {
            $user->email = $validated['email'];
            $user->email_verified_at = null; // Require re-verification
            $user->save();
        }
        
        // Clear the session data
        Session::forget('verification_extracted_data');
        
        return redirect()->route('home')
            ->with('success', 'Your profile has been successfully completed!');
    }
}