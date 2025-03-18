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
        
        // Add fallbacks for missing data
        if (empty($extractedData)) {
            Log::warning('No extracted data found in session');
        } else {
            Log::info('Retrieved extracted data from session', $extractedData);
        }
        
        // Format birthdate if exists
        if (!empty($extractedData['birthdate'])) {
            try {
                // Parse the date using Carbon for flexible date handling
                $date = Carbon::parse($extractedData['birthdate']);
                $extractedData['birthdate'] = $date->format('Y-m-d');
                
                Log::info('Formatted birthdate', [
                    'original' => $extractedData['birthdate'],
                    'formatted' => $date->format('Y-m-d')
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to parse birthdate', [
                    'birthdate' => $extractedData['birthdate'],
                    'error' => $e->getMessage()
                ]);
                $extractedData['birthdate'] = '';
            }
        }

        // Normalize gender
        if (!empty($extractedData['gender'])) {
            $gender = strtolower(trim($extractedData['gender']));
            
            // Enhanced gender mapping
            $genderMap = [
                'male' => ['m', 'male', 'lalaki'],
                'female' => ['f', 'female', 'babae']
            ];

            foreach ($genderMap as $normalizedGender => $variants) {
                if (in_array($gender, $variants) || str_contains($gender, $normalizedGender)) {
                    $extractedData['gender'] = $normalizedGender;
                    break;
                }
            }

            // If no match found, set to 'other'
            if (!in_array($extractedData['gender'], ['male', 'female'])) {
                $extractedData['gender'] = 'other';
            }

            Log::info('Normalized gender', [
                'original' => $gender,
                'normalized' => $extractedData['gender']
            ]);
        }
        
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