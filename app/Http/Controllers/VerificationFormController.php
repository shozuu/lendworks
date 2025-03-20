<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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
    
    // Debug the data being retrieved
    Log::info('Extracted data from session', $extractedData);
    
    // Ensure consistent camelCase naming for frontend
    $formattedData = [
        'firstName' => $extractedData['first_name'] ?? $extractedData['firstName'] ?? '',
        'middleName' => $extractedData['middle_name'] ?? $extractedData['middleName'] ?? '',
        'lastName' => $extractedData['last_name'] ?? $extractedData['lastName'] ?? '',
        'birthdate' => '', // Format this below
        'gender' => $extractedData['gender'] ?? '',
        'mobileNumber' => $extractedData['mobile_number'] ?? $extractedData['mobileNumber'] ?? '',
        'streetAddress' => $extractedData['street_address'] ?? $extractedData['streetAddress'] ?? '',
        'barangay' => $extractedData['barangay'] ?? '',
        'city' => $extractedData['city'] ?? '',
        'province' => $extractedData['province'] ?? '',
        'postalCode' => $extractedData['postal_code'] ?? $extractedData['postalCode'] ?? '',
        'nationality' => $extractedData['nationality'] ?? 'Filipino',
        'primaryIdType' => $extractedData['primary_id_type'] ?? $extractedData['primaryIdType'] ?? '',
        'secondaryIdType' => $extractedData['secondary_id_type'] ?? $extractedData['secondaryIdType'] ?? '',
        'email' => $extractedData['email'] ?? auth()->user()->email ?? '',
    ];
    
    // Format birthdate if exists
    if (!empty($extractedData['birthdate'])) {
        try {
            $date = Carbon::parse($extractedData['birthdate']);
            $formattedData['birthdate'] = $date->format('Y-m-d');
        } catch (\Exception $e) {
            Log::error('Failed to parse birthdate', [
                'birthdate' => $extractedData['birthdate'],
                'error' => $e->getMessage()
            ]);
            $formattedData['birthdate'] = '';
        }
    }
    
    return response()->json($formattedData);

        
        // Ensure required fields exist even if empty
        $requiredFields = ['firstName', 'middleName', 'lastName', 'birthdate', 
                        'gender', 'mobileNumber', 'streetAddress', 'nationality',
                        'primaryIdType', 'secondaryIdType', 'email'];
                        
        foreach ($requiredFields as $field) {
            if (!isset($extractedData[$field])) {
                $extractedData[$field] = '';
            }
        }

        Log::info('Returning extracted data', $extractedData);
        
        return response()->json($extractedData);
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