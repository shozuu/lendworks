<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class FaceMatchController extends Controller
{
    private $validPhilippineIds = [
        'philsys' => 'Philippine Identification System (PhilSys) ID',
        'drivers' => 'Driver\'s License',
        'passport' => 'Philippine Passport',
        'sss' => 'SSS ID',
        'gsis' => 'GSIS ID',
        'postal' => 'Postal ID',
        'voters' => 'Voter\'s ID',
        'prc' => 'PRC ID',
        'philhealth' => 'PhilHealth ID',
        'tin' => 'TIN ID',
        'umid' => 'UMID'
    ];

        private $idsWithExpiration = [
        'drivers' => true,    // Driver's License - 5 or 10 years
        'passport' => true,   // Passport - 10 years (5 for minors)
        'umid' => true,       // UMID - 5 years if used as ATM
        'postal' => true,     // Postal ID - 3 years (Filipino), 1 year (foreigner)
        'prc' => true,        // PRC ID - 3 years
        'philhealth' => true, // PhilHealth ID - 5 years (laminated card)
        
        // IDs without expiration
        'philsys' => false,   // Philippine Identification System (PhilSys) - no expiration
        'sss' => false,       // SSS ID - no expiration
        'gsis' => false,      // GSIS ID - no expiration
        'voters' => false,    // Voter's ID - no expiration
        'tin' => false        // TIN ID - no expiration
    ];

    private $expirationPhrases = [
        'valid until', 'valid thru', 'valid to', 'validity', 'expiry', 'expiration', 
        'exp date', 'exp. date', 'date of expiry', 'date of expiration'
    ];

    private function saveVerificationImage($file, $directory, $userId)
    {
    $filename = $userId . '_' . time() . '.' . $file->getClientOriginalExtension();
    $path = $file->storeAs("verification/{$directory}", $filename, 'public');
    return $path;
    }


    public function show()
    {
        return Inertia::render('Auth/FaceMatcher', [
            'initialData' => [
                'matchScore' => null,
                'verified' => null,
                'validPhilippineIds' => $this->validPhilippineIds
            ]
        ]);
    }

    public function verifyLiveness(Request $request)
    {
        try {
            Log::info('Starting liveness detection', [
                'action' => $request->input('action')
            ]);

            $request->validate([
                'image' => 'required|image|mimes:jpeg,png|max:2048',
                'action' => 'required|string|in:smile,blink,turn_head'
            ]);

            if (!env('FACEPP_API_KEY') || !env('FACEPP_API_SECRET')) {
                Log::error('Face++ API credentials are missing');
                return response()->json([
                    'error' => 'Face verification service is not properly configured',
                    'message' => 'API credentials are missing'
                ], 500);
            }

            $imageBase64 = base64_encode(file_get_contents($request->file('image')));
            
            // Call Face++ API for liveness detection
            $response = Http::withoutVerifying()
                ->timeout(30)
                ->asForm()
                ->post('https://api-us.faceplusplus.com/facepp/v3/detect', [
                    'api_key' => env('FACEPP_API_KEY'),
                    'api_secret' => env('FACEPP_API_SECRET'),
                    'image_base64' => $imageBase64,
                    'return_attributes' => $this->getLivenessAttributes($request->input('action'))
                ]);

            if ($response->failed()) {
                Log::error('Face++ API request failed', [
                    'status' => $response->status(),
                    'response' => $response->json() ?? $response->body()
                ]);

                return response()->json([
                    'verified' => false,
                    'message' => $response->json()['error_message'] ?? 'Liveness check failed'
                ], 500);
            }

            $data = $response->json();
            $verificationResult = $this->verifyLivenessAction($data, $request->input('action'));

            Log::info('Liveness detection completed', [
                'action' => $request->input('action'),
                'verified' => $verificationResult['verified']
            ]);

            return response()->json($verificationResult);

        } catch (\Exception $e) {
            Log::error('Liveness detection error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'verified' => false,
                'message' => 'An error occurred during liveness detection'
            ], 500);
        }
    }

    private function getLivenessAttributes($action)
    {
        switch ($action) {
            case 'smile':
                return 'emotion,beauty';
            case 'blink':
                return 'eyestatus';
            default:
                return 'emotion,eyestatus';
        }
    }

    private function verifyLivenessAction($data, $action)
    {
        if (!isset($data['faces']) || empty($data['faces'])) {
            return [
                'verified' => false,
                'message' => 'No face detected'
            ];
        }

        $face = $data['faces'][0];
        $attributes = $face['attributes'];

        switch ($action) {
            case 'smile':
                $smileThreshold = 50;
                $happiness = $attributes['emotion']['happiness'] ?? 0;
                return [
                    'verified' => $happiness >= $smileThreshold,
                    'message' => $happiness >= $smileThreshold ? 
                        'Smile detected successfully' : 
                        'Please smile more naturally'
                ];

           case 'blink':
    // First, validate that we have all required eye status data
    if (!isset($attributes['eyestatus']) || 
        !isset($attributes['eyestatus']['left_eye_status']) || 
        !isset($attributes['eyestatus']['right_eye_status'])) {
        
        Log::error('Missing eye status data in Face++ response', [
            'attributes' => $attributes
        ]);
        
        return [
            'verified' => false,
            'message' => 'Could not analyze eye status. Please try again.',
            'debug_info' => ['error' => 'Missing eye data in API response']
        ];
    }
    
    // Extract all relevant eye metrics for comprehensive analysis
    $leftEyeClosed = max(
        $attributes['eyestatus']['left_eye_status']['eye_close'] ?? 0,
        $attributes['eyestatus']['left_eye_status']['no_glass_eye_close'] ?? 0
    );
    
    $rightEyeClosed = max(
        $attributes['eyestatus']['right_eye_status']['eye_close'] ?? 0,
        $attributes['eyestatus']['right_eye_status']['no_glass_eye_close'] ?? 0
    );
    
    $leftEyeOpen = max(
        $attributes['eyestatus']['left_eye_status']['eye_open'] ?? 0,
        $attributes['eyestatus']['left_eye_status']['no_glass_eye_open'] ?? 0
    );
    
    $rightEyeOpen = max(
        $attributes['eyestatus']['right_eye_status']['eye_open'] ?? 0,
        $attributes['eyestatus']['right_eye_status']['no_glass_eye_open'] ?? 0
    );
    
    // MUCH lower threshold - Face++ API often returns smaller values 
    $blinkThreshold = 5; // Reduced from 10
    
    // Multiple detection methods for better accuracy
    $bothEyesBlinked = ($leftEyeClosed >= $blinkThreshold && $rightEyeClosed >= $blinkThreshold);
    $oneEyeStronglyBlinked = ($leftEyeClosed >= $blinkThreshold * 2 || $rightEyeClosed >= $blinkThreshold * 2);
    
    // Important: ratio-based detection - often more reliable than absolute values
    $ratioThreshold = 0.8; // If closed value is 80% of the open value, count as blink
    $leftEyeRatioGood = ($leftEyeOpen > 0 && ($leftEyeClosed / ($leftEyeOpen + $leftEyeClosed)) >= $ratioThreshold);
    $rightEyeRatioGood = ($rightEyeOpen > 0 && ($rightEyeClosed / ($rightEyeOpen + $rightEyeClosed)) >= $ratioThreshold);
    $ratioBasedBlink = $leftEyeRatioGood || $rightEyeRatioGood;
    
    // Combine all methods - if ANY method detects a blink, count it as success
    $blinkDetected = $bothEyesBlinked || $oneEyeStronglyBlinked || $ratioBasedBlink;
    
    // Extensive logging for debugging
    Log::info('Blink detection values', [
        'left_eye_close' => $leftEyeClosed,
        'right_eye_close' => $rightEyeClosed,
        'left_eye_open' => $leftEyeOpen,
        'right_eye_open' => $rightEyeOpen,
        'threshold' => $blinkThreshold,
        'both_eyes_blinked' => $bothEyesBlinked,
        'one_eye_strongly_blinked' => $oneEyeStronglyBlinked,
        'left_eye_ratio' => $leftEyeOpen > 0 ? ($leftEyeClosed / ($leftEyeOpen + $leftEyeClosed)) : 0,
        'right_eye_ratio' => $rightEyeOpen > 0 ? ($rightEyeClosed / ($rightEyeOpen + $rightEyeClosed)) : 0,
        'ratio_based_blink' => $ratioBasedBlink,
        'blink_detected' => $blinkDetected
    ]);
    
    return [
        'verified' => $blinkDetected,
        'message' => $blinkDetected ?
            'Blink detected successfully' :
            'Please blink fully - close your eyes completely and then open them',
        'debug_info' => [
            'left_eye_close' => $leftEyeClosed,
            'right_eye_close' => $rightEyeClosed,
            'left_eye_open' => $leftEyeOpen,
            'right_eye_open' => $rightEyeOpen,
            'left_ratio' => $leftEyeOpen > 0 ? ($leftEyeClosed / ($leftEyeOpen + $leftEyeClosed)) : 0,
            'right_ratio' => $rightEyeOpen > 0 ? ($rightEyeClosed / ($rightEyeOpen + $rightEyeClosed)) : 0
        ]
    ];


            default:
                return [
                    'verified' => false,
                    'message' => 'Invalid action specified'
                ];
        }
    }

    public function matchFace(Request $request)
    {
        try {
            Log::info('Starting face match process', [
            'has_selfie' => $request->hasFile('selfie'),
            'has_primary_id' => $request->hasFile('id_card'),
            'has_secondary_id' => $request->hasFile('id_card_secondary'),
            'primary_id_type' => $request->input('id_type'),
            'secondary_id_type' => $request->input('id_type_secondary')
        ]);

          $request->validate([
            'selfie' => 'required|image|mimes:jpeg,png|max:2048',
            'id_card' => 'required|image|mimes:jpeg,png|max:2048',
            'id_type' => 'required|string|in:' . implode(',', array_keys($this->validPhilippineIds)),
            'id_card_secondary' => 'required|image|mimes:jpeg,png|max:2048',
            'id_type_secondary' => 'required|string|in:' . implode(',', array_keys($this->validPhilippineIds)),
        ]);

            if (!env('FACEPP_API_KEY') || !env('FACEPP_API_SECRET')) {
                Log::error('Face++ API credentials are missing');
                return response()->json([
                    'error' => 'Face verification service is not properly configured',
                    'message' => 'API credentials are missing'
                ], 500);
            }

            $selfieBase64 = base64_encode(file_get_contents($request->file('selfie')));
            $primaryIdBase64 = base64_encode(file_get_contents($request->file('id_card')));
            $secondaryIdBase64 = base64_encode(file_get_contents($request->file('id_card_secondary')));

             // Compare with primary ID
            $primaryResult = $this->compareFaces($selfieBase64, $primaryIdBase64);
            
            // Compare with secondary ID
            $secondaryResult = $this->compareFaces($selfieBase64, $secondaryIdBase64);
            
            // Calculate metrics
            $averageScore = ($primaryResult['score'] + $secondaryResult['score']) / 2;
            
            // Determine verification status - both must pass the threshold
            $bothVerified = $primaryResult['verified'] && $secondaryResult['verified'];

            // Save the images to storage and update user record
            $user = $request->user();
            $selfieImagePath = $this->saveVerificationImage($request->file('selfie'), 'selfies', $user->id);
            $primaryIdImagePath = $this->saveVerificationImage($request->file('id_card'), 'primary_ids', $user->id);
            $secondaryIdImagePath = $this->saveVerificationImage($request->file('id_card_secondary'), 'secondary_ids', $user->id);

            // Update user record with image paths and ID types
            $user->update([
                'selfie_image_path' => $selfieImagePath,
                'primary_id_image_path' => $primaryIdImagePath,
                'secondary_id_image_path' => $secondaryIdImagePath,
            ]);

             // Log results
            Log::info('Face match completed for both IDs', [
                'primary_score' => $primaryResult['score'],
                'secondary_score' => $secondaryResult['score'],
                'average_score' => $averageScore,
                'primary_verified' => $primaryResult['verified'],
                'secondary_verified' => $secondaryResult['verified'],
                'both_verified' => $bothVerified
            ]);

            if ($bothVerified) {
                $request->user()->forceFill([
                    'id_verified_at' => now()
                ])->save();
            
            // Run OCR on ID card images
            $primaryIdOcrResult = $this->runOcr($request->file('id_card'));
            $secondaryIdOcrResult = $this->runOcr($request->file('id_card_secondary'));
            
            // Extract user data using the OCR results
            $extractedData = $this->extractUserDataFromOCR(
                ['text' => $primaryIdOcrResult['text'], 'id_type' => $request->input('id_type')],
                ['text' => $secondaryIdOcrResult['text'], 'id_type' => $request->input('id_type_secondary')]
            );
            
            Session::put('verification_extracted_data', $extractedData);

            $redirectUrl = route('verification.form');
            
            return response()->json([
                'primary_id' => [
                    'match_score' => $primaryResult['score'],
                    'verified' => $primaryResult['verified']
                ],
                'secondary_id' => [
                    'match_score' => $secondaryResult['score'],
                    'verified' => $secondaryResult['verified']
                ],
                'average_match_score' => $averageScore,
                'verified' => $bothVerified,
                'message' => 'Face verification successful',
                'redirect' =>  $redirectUrl
            ]);
        }
        
        // If not verified, return the non-verified response
        return response()->json([
            'primary_id' => [
                'match_score' => $primaryResult['score'],
                'verified' => $primaryResult['verified']
            ],
            'secondary_id' => [
                'match_score' => $secondaryResult['score'],
                'verified' => $secondaryResult['verified']
            ],
            'average_match_score' => $averageScore,
            'verified' => false,
            'message' => 'Face verification failed'
        ]);
        
    } catch (\Exception $e) {
        Log::error('Face matching error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'error' => 'Server error',
            'message' => 'An unexpected error occurred while processing your request'
        ], 500);
    }
}

/**
 * Run OCR on an ID image
 * 
 * @param \Illuminate\Http\UploadedFile $file
 * @return array OCR result
 */
private function runOcr($file)
{
    // Set PHP script timeout
     ini_set('max_execution_time', 120);
    // Get MIME type and map it to a file extension
    $mimeType = $file->getMimeType();
    $fileType = match ($mimeType) {
        'image/jpeg', 'image/jpg' => 'JPG',
        'image/png' => 'PNG',
        default => 'JPG'
    };

    // Make OCR request
    $response = Http::withoutVerifying()
      ->timeout(120) 
        ->attach(
            'file', 
            file_get_contents($file->path()), 
            'id_card.' . strtolower($fileType)
        )
        ->post('https://api.ocr.space/parse/image', [
            'apikey' => env('OCR_SPACE_API_KEY'),
            'language' => 'eng',
            'OCREngine' => 2,
        ]);

    if ($response->successful() && isset($response['ParsedResults'])) {
        return [
            'text' => $response['ParsedResults'][0]['ParsedText'] ?? '',
            'success' => true
        ];
    }
    
    return [
        'text' => '',
        'success' => false,
        'error' => $response['ErrorMessage'] ?? 'OCR processing failed'
    ];
}


  private function extractBirthdate($text)
{
    $lines = preg_split('/\r\n|\r|\n/', $text);
    $lines = array_map('trim', $lines);
    
    // Enhanced birthdate phrases with more variations
    $birthdatePhrases = [
        'date of birth', 'birth date', 'birthday', 'birth', 'dob', 'born',
        'petsa ng kapanganakan', 'birth day', 'birthdate', 'date/birth', 'b-day'
    ];
    
    // First try direct phrase approach
    foreach ($lines as $i => $line) {
        $lineLower = strtolower($line);
        
        foreach ($birthdatePhrases as $phrase) {
            if (stripos($lineLower, $phrase) !== false) {
                // Try to extract date from current line
                $parts = preg_split('/[:\-]/', $line, 2);
                if (count($parts) > 1) {
                    $dateStr = trim($parts[1]);
                    $date = $this->parseFlexibleDate($dateStr);
                    if ($date) {
                        Log::info("Successfully extracted birthdate", [
                            'line' => $line,
                            'extracted_date' => $date
                        ]);
                        return $date;
                    }
                }
                
                // Check next line if current line only contains the label
                if (isset($lines[$i + 1])) {
                    $nextLine = trim($lines[$i + 1]);
                    $date = $this->parseFlexibleDate($nextLine);
                    if ($date) {
                        Log::info("Successfully extracted birthdate from next line", [
                            'current_line' => $line,
                            'next_line' => $nextLine,
                            'extracted_date' => $date
                        ]);
                        return $date;
                    }
                }
            }
        }
    }
    
    // If no birthdate found, look for any date in the text that looks like a birthdate
    // (should be between 18-80 years old)
    $currentYear = (int)date('Y');
    $minYear = $currentYear - 80;
    $maxYear = $currentYear - 18;
    
    // Scan for standalone dates in the text
    foreach ($lines as $line) {
        $date = $this->parseFlexibleDate($line);
        if ($date) {
            $year = (int)$date->format('Y');
            if ($year >= $minYear && $year <= $maxYear) {
                Log::info("Found potential birthdate based on reasonable age range", [
                    'line' => $line,
                    'extracted_date' => $date,
                    'year' => $year
                ]);
                return $date;
            }
        }
    }
    
    return null;
}
 
  /**
 * Extract user data from OCR results
 * 
 * @param array $primaryIdResult
 * @param array $secondaryIdResult 
 * @return array
 */
private function extractUserDataFromOCR($primaryIdResult, $secondaryIdResult)
{
    // Use the consolidated extraction method for each ID
    $primaryData = $this->extractAllUserData($primaryIdResult);
    $secondaryData = $this->extractAllUserData($secondaryIdResult);
    
      $nationality = 'Filipino';

    // Combine results, preferring primary ID data when available
    return [
        'firstName' => $primaryData['firstName'] ?: $secondaryData['firstName'],
        'middleName' => $primaryData['middleName'] ?: $secondaryData['middleName'],
        'lastName' => $primaryData['lastName'] ?: $secondaryData['lastName'],
        'birthdate' => $primaryData['birthdate'] ?: $secondaryData['birthdate'],
        'streetAddress' => $primaryData['streetAddress'] ?: $secondaryData['streetAddress'],
        'mobileNumber' => $primaryData['mobileNumber'] ?: $secondaryData['mobileNumber'],
        'nationality' => $nationality,
        'primaryIdType' => $primaryIdResult['id_type'] ?? null,
        'secondaryIdType' => $secondaryIdResult['id_type'] ?? null,
        'email' => auth()->user()->email,
    ];
}

/**
 * Extract all user data from OCR text using enhanced keyword detection
 * 
 * @param array $idData
 * @return array
 */
private function extractAllUserData($idData)
    {
        $text = $idData['text'] ?? '';
        $idType = $idData['id_type'] ?? '';
        
        // Initialize results
        $results = [
            'firstName' => '',
            'middleName' => '',
            'lastName' => '',
            'birthdate' => '',
            'gender' => '',
            'civilStatus' => '',
            'nationality' => 'Filipino',
            'mobileNumber' => '',
            'streetAddress' => ''
        ];
        
        // Extract birthdate
        $birthdate = $this->extractBirthdate($text);
        if ($birthdate) {
            $results['birthdate'] = $birthdate;
        }
        
        // Special handling for Voter's ID format
        if ($idType === 'voters') {
            $nameIndex = -1;
            $lines = preg_split('/\r\n|\r|\n/', $text);
            $lines = array_map('trim', $lines);
            
            foreach ($lines as $i => $line) {
                if (preg_match('/^[A-Z]+$/', trim($line))) {
                    if ($nameIndex === -1) {
                        $results['lastName'] = trim($line);
                        $nameIndex = $i;
                    } else if ($i === $nameIndex + 1) {
                        $results['firstName'] = trim($line);
                    } else if ($i === $nameIndex + 2) {
                        $results['middleName'] = trim($line);
                    }
                }
            }
        }
        
        // Process each line for other fields
        foreach ($this->getFieldKeywordsForIdType($idType) as $field => $keywords) {
            foreach ($keywords as $keyword) {
                if ($field === 'birthdate' || ($idType === 'voters' && in_array($field, ['firstName', 'lastName', 'middleName']))) {
                    continue;
                }
                
                $this->extractFieldFromText($text, $field, $keyword, $results);
            }
        }
        
        return $results;
    }

    private function extractFieldFromText($text, $field, $keyword, &$results)
{
    $lines = preg_split('/\r\n|\r|\n/', $text);
    foreach ($lines as $i => $line) {
        if (preg_match('/^' . preg_quote($keyword, '/') . '\s*:?\s*(.+)$/i', $line, $matches)) {
            $value = trim($matches[1]);
            $results[$field] = $value;
            break;
        }
    }
    
    // Special handling for gender field if not found by keywords
    if ($field === 'gender' && empty($results['gender'])) {
        $this->extractGenderDirectly($text, $results);
    }
}

private function extractGenderDirectly($text, &$results)
{
    $textLower = strtolower($text);
    
    // Common gender patterns on IDs
    $patterns = [
        // Pattern: "SEX: M" or "SEX: F" anywhere in text
        '/sex\s*:?\s*(m|f|male|female)/i',
        // Pattern: "Gender: Male" anywhere in text
        '/gender\s*:?\s*(male|female|m|f)/i',
        // Pattern: "Sex/Gender: M" anywhere in text
        '/sex\s*[\/\-]?\s*gender\s*:?\s*(m|f|male|female)/i',
        // Pattern: standalone "M" or "F" label on IDs (careful with this one)
        '/\b(sex|gender)\s*[:\-]?\s*([mf])\b/i',
        // Pattern: spelled out as standalone words
        '/\b(male|female)\b/i',
        // Filipino terms
        '/kasarian\s*:?\s*(lalaki|babae)/i'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $textLower, $matches)) {
            $genderValue = strtolower(end($matches));
            $normalizedGender = $this->normalizeGender($genderValue);
            
            if (!empty($normalizedGender)) {
                $results['gender'] = $normalizedGender;
                Log::info("Extracted gender directly with pattern", [
                    'pattern' => $pattern,
                    'match' => $matches[0],
                    'gender_value' => $genderValue,
                    'normalized' => $normalizedGender
                ]);
                return true;
            }
        }
    }
    
    // Last resort: look for specific single character indicators
    if (preg_match('/\bsex\s*[:\-]?\s*([mf])\b/i', $textLower, $matches)) {
        $gender = strtolower($matches[1]);
        $results['gender'] = ($gender == 'm') ? 'male' : 'female';
        Log::info("Extracted gender from single character indicator", [
            'match' => $matches[0],
            'normalized' => $results['gender']
        ]);
        return true;
    }
    
    return false;
}

/**
 * Improved normalizeGender method with more variations
 */
private function normalizeGender($value)
{
    $value = strtolower(trim($value));
    
    // More comprehensive mapping
    $maleVariants = ['m', 'male', 'lalaki', 'l', 'ma', 'mal', 'masculino', 'gentleman', 'guy'];
    $femaleVariants = ['f', 'female', 'babae', 'fem', 'fmale', 'femele', 'feminino', 'lady', 'woman'];
    
    // Check for male variations (including partial matches)
    foreach ($maleVariants as $variant) {
        if ($value === $variant || strpos($value, $variant) === 0) {
            return 'male';
        }
    }
    
    // Check for female variations (including partial matches)
    foreach ($femaleVariants as $variant) {
        if ($value === $variant || strpos($value, $variant) === 0) {
            return 'female';
        }
    }
    
    // For numerical representations sometimes used in forms
    if ($value === '1' || $value === '0' || $value === 'true') {
        return 'male';  // Assuming 1/true represents male in binary systems
    } else if ($value === '2' || $value === 'false') {
        return 'female';  // Assuming 2/false represents female in binary systems
    }
    
    return '';
}


/**
 * Get keywords for fields by ID type, with defaults plus ID-specific patterns
 */
private function getFieldKeywordsForIdType($idType)
{
    // Common keywords for all ID types
     $common = [
        'firstName' => ['first name:', 'given name:', 'first:', 'given:'],
        'middleName' => ['middle name:', 'middle:', 'mi:'],
        'lastName' => ['last name:', 'surname:', 'family name:', 'last:'],
        'birthdate' => ['date of birth:', 'birth date:', 'birthday:', 'birth:', 'dob:'],
        'gender' => ['sex:', 'gender:'],
        'civilStatus' => ['civil status:', 'marital status:'],
        'nationality' => ['nationality:', 'citizenship:', 'citizen:']
    ];
    
    // ID-specific keywords
   $specific = [
        'philsys' => [
            'firstName' => ['pangalan', 'given name', 'first name'],
            'middleName' => ['gitnang pangalan', 'middle name'],
            'lastName' => ['apelyido', 'surname', 'last name'],
            'birthdate' => ['petsa ng kapanganakan', 'date of birth', 'birth date'],
            'gender' => ['kasarian', 'sex'],
            'nationality' => ['nasyonalidad', 'nationality'],
            'idNumber' => ['philsys number', 'pcn', 'phn']
        ],
        
        'drivers' => [
            'firstName' => ['given name', 'first name'],
            'lastName' => ['surname', 'last name'],
            'birthdate' => ['birth date', 'date of birth'],
            'address' => ['address', 'residence'],
            'licenseNumber' => ['license no', 'dl number', 'license number'],
            'restrictions' => ['restrictions', 'condition'],
            'expiryDate' => ['expiry', 'valid until', 'expiration']
        ],
        
        'passport' => [
            'firstName' => ['given names', 'first name'],
            'lastName' => ['surname', 'last name'],
            'birthdate' => ['date of birth', 'birth date'],
            'birthPlace' => ['place of birth'],
            'passportNumber' => ['passport no', 'passport number'],
            'nationality' => ['nationality', 'citizenship'],
            'expiryDate' => ['date of expiry', 'expiry date', 'valid until']
        ],
        
        'sss' => [
            'firstName' => ['given name', 'first name'],
            'lastName' => ['surname', 'last name'],
            'birthdate' => ['date of birth', 'birth date'],
            'sssNumber' => ['ss number', 'sss no', 'social security number'],
            'issuedDate' => ['date issued', 'date of issue']
        ],
        
        'gsis' => [
            'firstName' => ['given name', 'first name'],
            'lastName' => ['surname', 'last name'],
            'birthdate' => ['date of birth', 'birth date'],
            'gsisNumber' => ['bp number', 'gsis number', 'id no'],
            'issuedDate' => ['date issued', 'date of issue']
        ],
        
        'postal' => [
            'firstName' => ['given name', 'first name'],
            'lastName' => ['surname', 'last name'],
            'birthdate' => ['date of birth', 'birth date'],
            'address' => ['present address', 'address'],
            'postalIdNumber' => ['id number', 'postal id no'],
            'expiryDate' => ['valid until', 'expiry date']
        ],
        
        'voters' => [
            'firstName' => [], // Handled by special case for all-caps format
            'lastName' => [], // Handled by special case for all-caps format
            'middleName' => [], // Handled by special case for all-caps format
            'birthdate' => ['date of birth', 'birth date'],
            'civilStatus' => ['civil status', 'marital status'],
            'nationality' => ['citizenship'],
            'votersId' => ['vin', 'voters id number', 'precinct no']
        ],
        
        'prc' => [
            'firstName' => ['given name', 'first name'],
            'lastName' => ['surname', 'last name'],
            'birthdate' => ['date of birth', 'birth date'],
            'prcNumber' => ['registration number', 'license no', 'prc no'],
            'profession' => ['profession', 'occupation'],
            'expiryDate' => ['valid until', 'expiry date']
        ],
        
        'philhealth' => [
            'firstName' => ['given name', 'first name'],
            'lastName' => ['surname', 'last name'],
            'birthdate' => ['date of birth', 'birth date'],
            'philhealthNumber' => ['pin', 'philhealth number', 'philhealth id'],
            'category' => ['membership category', 'category'],
            'expiryDate' => ['valid until', 'expiry date']
        ],
        
        'tin' => [
            'firstName' => ['given name', 'first name'],
            'lastName' => ['surname', 'last name'],
            'birthdate' => ['date of birth', 'birth date'],
            'tinNumber' => ['tin', 'tax identification number'],
            'address' => ['address', 'residence'],
            'issuedDate' => ['date issued', 'date of issue']
        ],
        
        'umid' => [
            'firstName' => ['given name', 'first name'],
            'lastName' => ['surname', 'last name'],
            'birthdate' => ['date of birth', 'birth date'],
            'address' => ['address', 'residence'],
            'umidNumber' => ['cca no', 'umid number', 'crn'],
            'issuedDate' => ['date issued', 'date of issue'],
            'cardNumber' => ['card number', 'card no']
        ]
    ];

    
    // Merge the common keywords with ID-specific ones if available
     $keywords = $common;
    if (isset($specific[$idType])) {
        foreach ($specific[$idType] as $field => $fieldKeywords) {
            // Check if the field exists in common keywords first,
            // if not, initialize it as an empty array
            if (!isset($keywords[$field])) {
                $keywords[$field] = [];
            }
            $keywords[$field] = array_merge($keywords[$field], $fieldKeywords);
        }
    }
    
    return $keywords;
}



    /**
     * Compare two face images and return the confidence score
     *
     * @param string $imageBase64First Base64 encoded image of first face
     * @param string $imageBase64Second Base64 encoded image of second face
     * @return array Result with score and verification status
     */
    private function compareFaces($imageBase64First, $imageBase64Second)
    {
        $response = Http::withoutVerifying()
            ->timeout(30)
            ->asForm()
            ->post('https://api-us.faceplusplus.com/facepp/v3/compare', [
                'api_key' => env('FACEPP_API_KEY'),
                'api_secret' => env('FACEPP_API_SECRET'),
                'image_base64_1' => $imageBase64First,
                'image_base64_2' => $imageBase64Second,
            ]);

        if ($response->failed() || !isset($response['confidence'])) {
            Log::error('Face++ API request failed', [
                'status' => $response->status(),
                'response' => $response->json() ?? $response->body()
            ]);
            
            return [
                'score' => 0,
                'verified' => false,
                'error' => $response->json()['error_message'] ?? 'Unknown error'
            ];
        }

        $matchScore = $response['confidence'];
        $threshold = 80; // Your confidence threshold
        
        return [
            'score' => $matchScore,
            'verified' => $matchScore > $threshold
        ];
    }

   public function validateIdType(Request $request)
{
    ini_set('max_execution_time', 120);
    try {
        Log::info('Starting ID validation process', [
            'request_has_primary_id' => $request->hasFile('id_card_primary'),
            'request_has_secondary_id' => $request->hasFile('id_card_secondary'),
            'selected_primary_id_type' => $request->input('id_type_primary'),
            'selected_secondary_id_type' => $request->input('id_type_secondary')
        ]);

        $request->validate([
            'id_card_primary' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'id_type_primary' => 'required|string|in:' . implode(',', array_keys($this->validPhilippineIds)),
            'id_card_secondary' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'id_type_secondary' => 'required|string|in:' . implode(',', array_keys($this->validPhilippineIds)),
        ]);

        if (empty(env('OCR_SPACE_API_KEY'))) {
            Log::error('OCR Space API key is missing');
            return response()->json([
                'error' => 'OCR service error',
                'message' => 'OCR service is not properly configured'
            ], 500);
        }

        // Process primary ID
        $primaryResult = $this->processIdCard(
            $request->file('id_card_primary'), 
            $request->input('id_type_primary')
        );
        
        // Process secondary ID
        $secondaryResult = $this->processIdCard(
            $request->file('id_card_secondary'), 
            $request->input('id_type_secondary')
        );
        
        // Return combined results
        return response()->json([
            'primary_id' => $primaryResult,
            'secondary_id' => $secondaryResult,
            'all_valid' => $primaryResult['is_valid'] && $secondaryResult['is_valid']
        ]);

    } catch (\Exception $e) {
        Log::error('An error occurred while validating ID types', [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'is_valid' => false,
            'message' => 'An error occurred while validating the IDs',
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Process and validate an individual ID card image
 * 
 * @param \Illuminate\Http\UploadedFile $file
 * @param string $idType
 * @return array
 */
private function processIdCard($file, $idType)
{
    try {

        ini_set('max_execution_time', 120);
        // Get MIME type and map it to a file extension
        $mimeType = $file->getMimeType();
        $fileType = match ($mimeType) {
            'image/jpeg', 'image/jpg' => 'JPG',
            'image/png' => 'PNG',
            default => 'JPG'
        };

        Log::info('Preparing OCR request', [
            'id_type' => $idType,
            'mime_type' => $mimeType,
            'file_type' => $fileType,
            'file_size' => $file->getSize()
        ]);

        // Make OCR request with SSL verification disabled and explicit file type
        $response = Http::withoutVerifying()
        ->timeout(60) 
            ->attach(
                'file', 
                file_get_contents($file->path()), 
                'id_card.' . strtolower($fileType)
            )
            ->post('https://api.ocr.space/parse/image', [
                'apikey' => env('OCR_SPACE_API_KEY'),
                'language' => 'eng',
                'OCREngine' => 2,
            ]);

        Log::info('OCR API Response received', [
            'status' => $response->status(),
            'has_parsed_results' => isset($response['ParsedResults']),
        ]);

        if ($response->failed() || !isset($response['ParsedResults'])) {
            Log::error('OCR.space API request failed for ID type: ' . $idType, [
                'status' => $response->status(),
                'response' => $response->json() ?? $response->body(),
            ]);

            return [
                'is_valid' => false,
                'message' => 'Failed to process ID image',
                'error' => $response->json()['ErrorMessage'] ?? 'Unknown error occurred',
                'id_type' => $idType,
                'id_name' => $this->validPhilippineIds[$idType] ?? 'Unknown ID Type'
            ];
        }

        $extractedText = $response['ParsedResults'][0]['ParsedText'] ?? '';

        Log::info('Text extracted from ID', [
            'text_length' => strlen($extractedText),
            'has_content' => !empty($extractedText),
            'id_type' => $idType
        ]);

          Log::info('Full text extracted from ID', [
            'id_type' => $idType,
            'text' => $extractedText  // Log the full text content
        ]);
        
        if (empty($extractedText)) {
            Log::warning('No text extracted from ID image for type: ' . $idType);
            return [
                'is_valid' => false,
                'message' => 'No text could be extracted from the image',
                'id_type' => $idType,
                'id_name' => $this->validPhilippineIds[$idType] ?? 'Unknown ID Type'
            ];
        }

        // After extracting text from OCR 
        // Check for expiration date if this ID type should have one
        $expirationInfo = $this->extractExpirationDate($extractedText, $idType);
        

        // Get the keywords for the selected ID type
        $keywords = $this->getKeywordsForIdType($idType);

        // Normalize extracted text for better matching
        $textLower = strtolower($extractedText);
        $textNormalized = preg_replace('/[^a-z0-9\s]/', ' ', $textLower);
        $textNormalized = preg_replace('/\s+/', ' ', $textNormalized);
        $textWords = explode(' ', $textNormalized);

        $found = false;
        $matchedKeywords = [];
        $matchDetails = [];

        foreach ($keywords as $keyword) {
            $keywordLower = strtolower($keyword);

            // Direct match
            if (strpos($textLower, $keywordLower) !== false) {
                $found = true;
                $matchedKeywords[] = $keyword;
                $matchDetails[] = [
                    'keyword' => $keyword,
                    'match_type' => 'exact',
                    'matched_text' => $keywordLower
                ];
                continue;
            }

            // No-space match
            $noSpaceText = str_replace(' ', '', $textLower);
            $noSpaceKeyword = str_replace(' ', '', $keywordLower);
            if (strlen($noSpaceKeyword) > 3 && strpos($noSpaceText, $noSpaceKeyword) !== false) {
                $found = true;
                $matchedKeywords[] = $keyword;
                $matchDetails[] = [
                    'keyword' => $keyword,
                    'match_type' => 'no-space',
                    'matched_text' => $noSpaceKeyword
                ];
                continue;
            }

            // Multi-word partial match
            if (str_word_count($keywordLower) > 1) {
                $keywordParts = explode(' ', $keywordLower);
                $matchedParts = 0;
                $matchedWords = [];

                foreach ($keywordParts as $part) {
                    if (strlen($part) > 3 && strpos($textLower, $part) !== false) {
                        $matchedParts++;
                        $matchedWords[] = $part;
                    }
                }

                $threshold = ceil(count($keywordParts) * 0.7);
                if ($matchedParts >= $threshold) {
                    $found = true;
                    $matchedKeywords[] = $keyword;
                    $matchDetails[] = [
                        'keyword' => $keyword,
                        'match_type' => 'partial',
                        'matched_parts' => $matchedWords,
                        'matched_count' => $matchedParts
                    ];
                    continue;
                }
            }

            // Levenshtein distance for similar words
            if (strlen($keywordLower) > 4) {
                foreach ($textWords as $word) {
                    if (strlen($word) > 3) {
                        $maxDistance = min(3, floor(strlen($keywordLower) / 4));
                        if (levenshtein($word, $keywordLower) <= $maxDistance) {
                            $found = true;
                            $matchedKeywords[] = $keyword;
                            $matchDetails[] = [
                                'keyword' => $keyword,
                                'match_type' => 'fuzzy',
                                'similar_word' => $word,
                                'levenshtein_distance' => levenshtein($word, $keywordLower)
                            ];
                            break;
                        }
                    }
                }
            }
           
        }
         $isValid = $found;

             if ($found && isset($this->idsWithExpiration[$idType]) && $this->idsWithExpiration[$idType]) {
            // If expiration date found and ID is expired
            if ($expirationInfo['found'] && $expirationInfo['is_expired']) {
                $isValid = false; // Invalid if expired
            }
        }

        Log::info('ID validation result for type: ' . $idType, [
            'found_match' => $found,
            'matched_keywords' => $matchedKeywords
        ]);

        // Construct appropriate message
        $message = '';
        if (!$found) {
            $message = 'Invalid or unrecognized ID. Please upload a valid Philippine government ID.';
        } else if ($expirationInfo['found'] && $expirationInfo['is_expired']) {
            $message = 'This ID has expired on ' . $expirationInfo['formatted_date'] . '. Please provide a valid non-expired ID.';
        } else if ($expirationInfo['found']) {
            $message = 'ID verified successfully. Valid until ' . $expirationInfo['formatted_date'] . '.';
        } else {
            // Either ID doesn't have expiration or couldn't be found
            $message = 'ID type verified successfully.';
            if ($expirationInfo['checked']) {
                $message .= ' ' . $expirationInfo['message'];
            }
        }

        return [
            'is_valid' => $isValid,
            'message' => $message,
            'id_type' => $idType,
            'id_name' => $this->validPhilippineIds[$idType] ?? 'Unknown ID Type',
            'extracted_text' => $extractedText,
            'text_length' => strlen($extractedText),
            'keywords_checked' => $keywords,
            'matched_keywords' => $matchedKeywords,
            'match_details' => $matchDetails,
            'normalized_text' => $textNormalized,
            'expiration' => $expirationInfo
        ];

    } catch (\Exception $e) {
        Log::error('Error processing ID card', [
            'id_type' => $idType,
            'error' => $e->getMessage()
        ]);
        
        return [
            'is_valid' => false,
            'message' => 'An error occurred while processing the ID',
            'error' => $e->getMessage(),
            'id_type' => $idType,
            'id_name' => $this->validPhilippineIds[$idType] ?? 'Unknown ID Type'
        ];
    }
}


/**
 * Extract and validate expiration date from OCR text
 * 
 * @param string $text The OCR extracted text
 * @param string $idType The type of ID
 * @return array The expiration information
 */
private function extractExpirationDate($text, $idType)
{
    // If this ID type doesn't have expiration, skip the check
    if (!isset($this->idsWithExpiration[$idType]) || !$this->idsWithExpiration[$idType]) {
        return [
            'found' => false,
            'date' => null,
            'is_expired' => false,
            'raw_match' => null,
            'checked' => false,
            'message' => 'This ID type does not have an expiration date'
        ];
    }

    Log::info('Checking expiration date for ' . $idType);
    
    // Normalize the text for consistent matching
    $textLower = strtolower($text);
    
    // Look for expiration context
    $expirationContext = '';
    $expirationFound = false;
    
    foreach ($this->expirationPhrases as $phrase) {
        if (strpos($textLower, $phrase) !== false) {
            // Extract context around the phrase (30 chars before and after)
            $position = strpos($textLower, $phrase);
            $start = max(0, $position - 30);
            $length = min(strlen($textLower) - $start, $position - $start + strlen($phrase) + 30);
            $expirationContext = substr($textLower, $start, $length);
            $expirationFound = true;
            break;
        }
    }
    
    // If no expiration phrase found, this is likely an issue with OCR
    if (!$expirationFound) {
        Log::info('No expiration phrase found for ' . $idType);
        return [
            'found' => false,
            'date' => null,
            'is_expired' => false,
            'raw_match' => null,
            'checked' => true,
            'message' => 'Could not locate expiration information on ID'
        ];
    }
    
    // Now extract the date from the context
    $dateString = null;
    
    // Define patterns for expiration dates
    $patterns = [
        // Format: MM/DD/YYYY or DD/MM/YYYY
        '/(\d{1,2}[\/\.\-]\d{1,2}[\/\.\-](19|20)\d{2})/' => 1,
        
        // Format: YYYY/MM/DD
        '/((19|20)\d{2}[\/\.\-]\d{1,2}[\/\.\-]\d{1,2})/' => 1,
        
        // Format: Month DD, YYYY (e.g., January 1, 2025)
        '/(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*\.?\s+\d{1,2},?\s+(19|20)\d{2}/i' => 0,
        
        // Format: DD Month YYYY (e.g., 01 January 2025)
        '/\d{1,2}\s+(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*\.?\s+(19|20)\d{2}/i' => 0
    ];
    
    foreach ($patterns as $pattern => $matchIndex) {
        if (preg_match($pattern, $expirationContext, $matches)) {
            $dateString = $matchIndex === 0 ? $matches[0] : $matches[$matchIndex];
            break;
        }
    }
    
    if (!$dateString) {
        // Fall back to searching the entire text if context didn't yield a date
        foreach ($patterns as $pattern => $matchIndex) {
            if (preg_match($pattern, $textLower, $matches)) {
                $dateString = $matchIndex === 0 ? $matches[0] : $matches[$matchIndex];
                break;
            }
        }
    }
    
    if (!$dateString) {
        Log::info('No date found in expiration context for ' . $idType);
        return [
            'found' => false,
            'date' => null,
            'is_expired' => false,
            'raw_match' => $expirationContext,
            'checked' => true,
            'message' => 'Could not extract a valid date from expiration information'
        ];
    }
    
    try {
        // Parse date with multiple formats
        $parsedDate = $this->parseFlexibleDate($dateString);
        
        if ($parsedDate) {
            $now = new \DateTime();
            $isExpired = $parsedDate < $now;
            
            Log::info('Expiration date found', [
                'id_type' => $idType,
                'date' => $parsedDate->format('Y-m-d'),
                'is_expired' => $isExpired
            ]);
            
            return [
                'found' => true,
                'date' => $parsedDate->format('Y-m-d'),
                'formatted_date' => $parsedDate->format('F j, Y'),
                'is_expired' => $isExpired,
                'raw_match' => $dateString,
                'checked' => true,
                'message' => $isExpired 
                    ? 'ID expired on ' . $parsedDate->format('F j, Y') 
                    : 'ID valid until ' . $parsedDate->format('F j, Y')
            ];
        }
    } catch (\Exception $e) {
        Log::warning('Failed to parse date: ' . $dateString, [
            'error' => $e->getMessage()
        ]);
    }
    
    return [
        'found' => false,
        'date' => null,
        'is_expired' => false,
        'raw_match' => $dateString,
        'checked' => true,
        'message' => 'Could not validate expiration date format'
    ];
}

/**
 * Flexible date parser that handles various date formats
 * 
 * @param string $dateString String containing a date
 * @return \DateTime|null Parsed date object or null if parsing fails
 */
private function parseFlexibleDate($dateString)
{
    if (empty($dateString)) {
        return null;
    }

    // Clean and normalize the date string
    $dateString = trim(preg_replace('/[^\w\s\/\-\.]/', '', $dateString));
    
    // Skip if the string contains non-date related words
    $nonDateWords = ['civil', 'status', 'gender', 'sex', 'name', 'address'];
    foreach ($nonDateWords as $word) {
        if (stripos($dateString, $word) !== false) {
            return null;
        }
    }

    Log::debug("Attempting to parse cleaned date string: {$dateString}");
    
    // Try common date formats
    $formats = [
        'd/m/Y', 'm/d/Y', 'Y/m/d',
        'd-m-Y', 'm-d-Y', 'Y-m-d',
        'd.m.Y', 'm.d.Y', 'Y.m.d',
        'F j Y', 'M j Y', 'j F Y', 'j M Y',
        'F j, Y', 'M j, Y'
    ];
    
    foreach ($formats as $format) {
        $date = \DateTime::createFromFormat($format, $dateString);
        if ($date && $date->format($format) === $dateString) {
            $year = (int)$date->format('Y');
            if ($year >= 1900 && $year <= date('Y') + 30) {
                // Return DateTime object, not a string
                return $date;
            }
        }
    }

    // Try strtotime as last resort
    $timestamp = strtotime($dateString);
    if ($timestamp !== false) {
        $date = new \DateTime();
        $date->setTimestamp($timestamp);
        $year = (int)$date->format('Y');
        if ($year >= 1900 && $year <= date('Y') + 30) {
            // Return DateTime object, not a string
            return $date;
        }
    }

    return null;
}

private function getKeywordsForIdType($idType)
{
    $keywords = [
        'philsys' => [
            'PAMBANSANG PAGKAKAKILANLAN', 'PhilSys Number'
        ],
        'drivers' => [
            'DRIVER\'S LICENSE', 'LTO'
        ],
        'passport' => [
            'Philippine Passport', 'DFA'
        ],
        'sss' => [
            'SSS Number', 'Social Security System'
        ],
        'gsis' => [
            'GSIS', 'BP Number'
        ],
        'postal' => [
            'Postal ID', 'PhilPost'
        ],
        'voters' => [
            'COMMISSION ON ELECTIONS'
        ],
        'prc' => [
            'PRC License', 'Professional Regulation Commission'
        ],
        'philhealth' => [
            'PhilHealth Number', 'Philippine Health Insurance Corporation'
        ],
        'tin' => [
            'BUREAU OF INTERNAL REVENUE'
        ],
        'umid' => [
            'UMID', 'Unified Multi-Purpose ID'
        ],
        'pwd' => [
            'PWD ID', 'Persons with Disability'
        ]
    ];

    return $keywords[$idType] ?? [];
}
}
