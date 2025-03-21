<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use function file_get_contents;
use function md5;
use function sha1;

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

    private const MAX_LIVENESS_ATTEMPTS = 5;
    private const MAX_FACEMATCH_ATTEMPTS = 3;
    private const COOLDOWN_MINUTES = 30;

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

             // Check for rate limiting first
            $attemptsRemaining = $this->checkLivenessAttempts();
            if ($attemptsRemaining <= 0) {
                return response()->json([
                    'error' => 'Too many attempts',
                    'message' => 'You have reached the maximum number of liveness detection attempts. Please try again in 30 minutes.',
                    'cooldown_minutes' => self::COOLDOWN_MINUTES
                ], 429);
            }

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

             $this->recordLivenessAttempt();
            
            // On success, reset counter if verification succeeds
            if ($verificationResult['verified']) {
                $this->resetLivenessAttempts();
            }

            return response()->json($verificationResult);

        } catch (\Exception $e) {
            // Still record failed attempts due to errors
            $this->recordLivenessAttempt();
            
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

    private function checkLivenessAttempts()
    {
        $key = 'liveness_attempts_' . $this->getIpFingerprint();
        $attempts = Session::get($key, 0);
        $lastAttemptTime = Session::get($key . '_time');
        
        // Reset if cooldown period has passed
        if ($lastAttemptTime && now()->diffInMinutes($lastAttemptTime) >= self::COOLDOWN_MINUTES) {
            Session::forget($key);
            Session::forget($key . '_time');
            return self::MAX_LIVENESS_ATTEMPTS;
        }
        
        return self::MAX_LIVENESS_ATTEMPTS - $attempts;
    }

     /**
     * Record a liveness verification attempt
     */
    private function recordLivenessAttempt()
    {
        $key = 'liveness_attempts_' . $this->getIpFingerprint();
        $attempts = Session::get($key, 0);
        Session::put($key, $attempts + 1);
        Session::put($key . '_time', now());
    }
    
    /**
     * Reset liveness verification attempts after success
     */
    private function resetLivenessAttempts()
    {
        $key = 'liveness_attempts_' . $this->getIpFingerprint();
        Session::forget($key);
        Session::forget($key . '_time');
    }
    
    /**
     * Get a fingerprint combining IP and user agent
     * @return string Fingerprint hash
     */
    private function getIpFingerprint()
    {
        $ip = request()->ip();
        $userAgent = request()->userAgent();
        return md5($ip . $userAgent . (auth()->id() ?? 'guest'));
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
        Log::info('Starting face match process', [/* ... */]);

        // Rate limiting check
        $attemptsRemaining = $this->checkFaceMatchAttempts();
        if ($attemptsRemaining <= 0) {
            return response()->json([/* ... */], 429);
        }

        // Validation
        $request->validate([/* ... */]);

        // Check for reused IDs
        if ($this->isIdReused($request)) {
            Log::warning('Attempt to use previously registered ID', [/* ... */]);
            return response()->json([/* ... */], 400);
        }

        // API credentials check
        if (!env('FACEPP_API_KEY') || !env('FACEPP_API_SECRET')) {
            Log::error('Face++ API credentials are missing');
            return response()->json([/* ... */], 500);
        }

        // Encode images only once
        $selfieBase64 = base64_encode(file_get_contents($request->file('selfie')));
        $primaryIdBase64 = base64_encode(file_get_contents($request->file('id_card')));
        $secondaryIdBase64 = base64_encode(file_get_contents($request->file('id_card_secondary')));

        // Compare faces
        $primaryResult = $this->compareFaces($selfieBase64, $primaryIdBase64);
        $secondaryResult = $this->compareFaces($selfieBase64, $secondaryIdBase64);
        
        $primaryIdOcrResult = $this->runOcr($request->file('id_card'));
        $secondaryIdOcrResult = $this->runOcr($request->file('id_card_secondary'));

        // Calculate metrics
        $averageScore = ($primaryResult['score'] + $secondaryResult['score']) / 2;
        $bothVerified = $primaryResult['verified'] && $secondaryResult['verified'];

        // Save the images to storage and update user record
        $user = $request->user();
        $selfieImagePath = $this->saveVerificationImage($request->file('selfie'), 'selfies', $user->id);
        $primaryIdImagePath = $this->saveVerificationImage($request->file('id_card'), 'primary_ids', $user->id);
        $secondaryIdImagePath = $this->saveVerificationImage($request->file('id_card_secondary'), 'secondary_ids', $user->id);

        // Update user record with image paths
        $user->update([
            'selfie_image_path' => $selfieImagePath,
            'primary_id_image_path' => $primaryIdImagePath,
            'secondary_id_image_path' => $secondaryIdImagePath,
        ]);

        // Record this attempt
        $this->recordFaceMatchAttempt();

        // Process if verified
        if ($bothVerified) {
            $this->resetFaceMatchAttempts();
            
            // Store image hashes for future duplicate detection
            $this->storeIdHashes($request);
            
            $request->user()->forceFill([
                'id_verified_at' => now()
            ])->save();
            
            // Run OCR on ID card images
            $primaryIdOcrResult = $this->runOcr($request->file('id_card'));
            $secondaryIdOcrResult = $this->runOcr($request->file('id_card_secondary'));
            
            $extractedData = $this->extractUserDataFromOCR(
                ['text' => $primaryIdOcrResult['text'], 'id_type' => $idData['primary_id_type']],
                ['text' => $secondaryIdOcrResult['text'], 'id_type' => $idData['secondary_id_type']]
            );

            // Add this logging:
            Log::info('Storing extracted data in session', [
                'data' => $extractedData
            ]);

            Session::put('verification_extracted_data', $extractedData);
            Session::save();        
            $redirectUrl = route('verification.form');
            
            session()->flash('verification_status', 'success');
            
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
        
        // Non-verified response
        return response()->json([/* non-verified response */]);
    } catch (\Exception $e) {
        // Error handling
        $this->recordFaceMatchAttempt();
        Log::error('Face matching error', [/* error details */]);
        return response()->json([/* error response */], 500);
    }
}

 private function checkFaceMatchAttempts()
    {
        $key = 'facematch_attempts_' . $this->getIpFingerprint();
        $attempts = Session::get($key, 0);
        $lastAttemptTime = Session::get($key . '_time');
        
        // Reset if cooldown period has passed
        if ($lastAttemptTime && now()->diffInMinutes($lastAttemptTime) >= self::COOLDOWN_MINUTES) {
            Session::forget($key);
            Session::forget($key . '_time');
            return self::MAX_FACEMATCH_ATTEMPTS;
        }
        
        return self::MAX_FACEMATCH_ATTEMPTS - $attempts;
    }


 /**
     * Record a face match attempt
     */
    private function recordFaceMatchAttempt()
    {
        $key = 'facematch_attempts_' . $this->getIpFingerprint();
        $attempts = Session::get($key, 0);
        Session::put($key, $attempts + 1);
        Session::put($key . '_time', now());
    }

     /**
     * Reset face match attempts
     */
    private function resetFaceMatchAttempts()
    {
        $key = 'facematch_attempts_' . $this->getIpFingerprint();
        Session::forget($key);
        Session::forget($key . '_time');
    }

      /**
     * Check if an ID has been used for verification previously
     * 
     * @param Request $request
     * @return bool
     */
    private function isIdReused(Request $request)
{
    try {
        // Generate perceptual hashes of the uploaded ID images
        $primaryIdHash = $this->generateImageHash($request->file('id_card')->getPathname());
        $secondaryIdHash = $this->generateImageHash($request->file('id_card_secondary')->getPathname());
        
        Log::info('Checking for reused IDs', [
            'primary_hash' => substr($primaryIdHash, 0, 10) . '...',
            'secondary_hash' => substr($secondaryIdHash, 0, 10) . '...'
        ]);
        
        // Check if either hash exists in our database for any user other than current user
        $primaryIdUsed = \App\Models\IdHash::where('primary_id_hash', $primaryIdHash)
            ->where('user_id', '!=', auth()->id())
            ->exists();
            
        $secondaryIdUsed = \App\Models\IdHash::where('secondary_id_hash', $secondaryIdHash)
            ->where('user_id', '!=', auth()->id())
            ->exists();
            
        $primaryAsSecondaryUsed = \App\Models\IdHash::where('secondary_id_hash', $primaryIdHash)
            ->where('user_id', '!=', auth()->id())
            ->exists();
            
        $secondaryAsPrimaryUsed = \App\Models\IdHash::where('primary_id_hash', $secondaryIdHash)
            ->where('user_id', '!=', auth()->id())
            ->exists();
        
        $isReused = $primaryIdUsed || $secondaryIdUsed || $primaryAsSecondaryUsed || $secondaryAsPrimaryUsed;
        
        if ($isReused) {
            Log::warning('ID reuse detected', [
                'primary_used' => $primaryIdUsed,
                'secondary_used' => $secondaryIdUsed,
                'primary_as_secondary_used' => $primaryAsSecondaryUsed,
                'secondary_as_primary_used' => $secondaryAsPrimaryUsed
            ]);
        }
        
        return $isReused;
        
    } catch (\Exception $e) {
        Log::error('Error checking for reused IDs', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // If there's an error, we should not block the user
        return false;
    }
}
    
 /**
     * Store hashes of verified IDs
     * 
     * @param Request $request
     */
    private function storeIdHashes(Request $request)
{
    try {
        $primaryIdHash = $this->generateImageHash($request->file('id_card')->getPathname());
        $secondaryIdHash = $this->generateImageHash($request->file('id_card_secondary')->getPathname());
        
        Log::info('Storing ID hashes for user', [
            'user_id' => auth()->id(),
            'primary_id_type' => $request->input('id_type'),
            'secondary_id_type' => $request->input('id_type_secondary')
        ]);
        
        // Delete any existing hash records for this user first
        \App\Models\IdHash::where('user_id', auth()->id())->delete();
        
        // Create new hash record
        \App\Models\IdHash::create([
            'user_id' => auth()->id(),
            'primary_id_hash' => $primaryIdHash,
            'primary_id_type' => $request->input('id_type'),
            'secondary_id_hash' => $secondaryIdHash,
            'secondary_id_type' => $request->input('id_type_secondary'),
            'verified_at' => now()
        ]);
        
    } catch (\Exception $e) {
        Log::error('Error storing ID hashes', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}
   


/**
 * Run OCR on an ID image
 * 
 * @param \Illuminate\Http\UploadedFile $file
 * @return array OCR result
 */
private function runOcr($file, $isPath = false)
{
    try {
        // API credentials
        $apiKey = env('OCR_SPACE_API_KEY');
        if (!$apiKey) {
            Log::error('OCR API key is missing');
            return [
                'text' => '',
                'success' => false,
                'error' => 'OCR API credentials are missing'
            ];
        }

        // Prepare the image file
        $base64Image = '';
        $mimeType = '';

       if ($isPath) {
            if (!file_exists($file)) {
                return [
                    'text' => '',
                    'success' => false,
                    'error' => 'File not found at path: ' . $file
                ];
            }
            $base64Image = base64_encode(file_get_contents($file));
            $mimeType = mime_content_type($file);
        } else {
            // Handle file object
            $base64Image = base64_encode(file_get_contents($file->path()));
            $mimeType = $file->getMimeType();
        }

        // Prepare API request
        $url = 'https://api.ocr.space/parse/image';
        $data = [
            'apikey' => $apiKey,
            'language' => 'eng',
            'isOverlayRequired' => false,
            'base64Image' => 'data:' . $mimeType . ';base64,' . $base64Image,
            'scale' => true,
            'detectOrientation' => true,
        ];

        // Make API request
        Log::info('Sending OCR request', ['url' => $url]);
        $response = Http::withoutVerifying()  // Chain methods properly
            ->timeout(30)
            ->post($url, $data);
                // Process response
                $result = $response->json();
        
        if ($response->successful() && isset($result['ParsedResults'][0]['ParsedText'])) {
            $text = $result['ParsedResults'][0]['ParsedText'];
            Log::info('OCR successful', ['text_length' => strlen($text)]);
            
            return [
                'text' => $text,
                'success' => true
            ];
        }
        
        // Log error response
        Log::error('OCR failed', [
            'status' => $response->status(),
            'response' => $result
        ]);
        
        return [
            'text' => '',
            'success' => false,
            'error' => $result['ErrorMessage'] ?? 'OCR processing failed'
        ];
    } catch (\Exception $e) {
        Log::error('OCR exception', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return [
            'text' => '',
            'success' => false,
            'error' => 'OCR processing exception: ' . $e->getMessage()
        ];
    }
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
        $threshold = 75; // Your confidence threshold

        // Add detailed logging of the match score
        Log::info('Face comparison score', [
            'score' => $matchScore,
            'threshold' => $threshold,
            'verified' => $matchScore > $threshold,
            'difference_from_threshold' => $matchScore - $threshold
        ]);
        
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
     * Parse flexible date that handles various date formats
     * 
     * @param string $dateString String containing a date
     * @return \DateTime|null Parsed date object or null if parsing fails
     */

     private function parseFlexibleDate($dateString)
{
    if (empty($dateString)) {
        return null;
    }
    
    // Normalize the date string
    $dateString = trim($dateString);
    $dateString = preg_replace('/\s+/', ' ', $dateString);
    
    // Common date formats to try
    $formats = [
        // MM/DD/YYYY or DD/MM/YYYY
        'm/d/Y', 'd/m/Y', 
        // MM-DD-YYYY or DD-MM-YYYY
        'm-d-Y', 'd-m-Y',
        // MM.DD.YYYY or DD.MM.YYYY
        'm.d.Y', 'd.m.Y',
        // YYYY-MM-DD (ISO format)
        'Y-m-d',
        // YYYY/MM/DD
        'Y/m/d',
        // Month DD, YYYY
        'F j, Y', 'M j, Y',
        // DD Month YYYY
        'j F Y', 'j M Y',
        // With day of week
        'D, F j, Y', 'l, F j, Y',
    ];
    
    // Try each format until one works
    foreach ($formats as $format) {
        $date = \DateTime::createFromFormat($format, $dateString);
        if ($date && $date->format($format) == $dateString) {
            return $date;
        }
    }
    
    // Try natural language parsing as a fallback
    try {
        return new \DateTime($dateString);
    } catch (\Exception $e) {
        return null;
    }
}

   private function generateImageHash($imagePath)
{
    // Read the file contents
    $imageData = file_get_contents($imagePath);
    
    // Generate a hash from the image data - combine multiple algorithms for better uniqueness
    $md5Hash = md5($imageData);
    $sha1Hash = sha1($imageData);
    
    // Return a combined hash
    return $md5Hash . substr($sha1Hash, 0, 16);
}
    /**
     * Get keywords for ID type validation
     * 
     * @param string $idType The type of ID
     * @return array List of keywords for the ID type
     */
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

    /**
 * Show the ID verification view (first step)
 */
public function showIdVerification()
{
    return Inertia::render('Auth/IdVerifier', [
        'initialData' => [
            'validPhilippineIds' => $this->validPhilippineIds
        ]
    ]);
}

/**
 * Show the liveness verification view (second step)
 */
public function showLivenessVerification()
{
    // Check if ID data exists in session
    if (!Session::has('id_verification_data')) {
        return redirect()->route('verify-id.show')
            ->with('error', 'Please complete ID verification first');
    }
    
    return Inertia::render('Auth/LivenessVerifier');
}

/**
 * Store ID data temporarily in session
 */
public function storeIdData(Request $request)
{
    $request->validate([
        'id_card' => 'required|image|mimes:jpeg,png|max:2048',
        'id_type' => 'required|string|in:' . implode(',', array_keys($this->validPhilippineIds)),
        'id_card_secondary' => 'required|image|mimes:jpeg,png|max:2048',
        'id_type_secondary' => 'required|string|in:' . implode(',', array_keys($this->validPhilippineIds)),
    ]);

    // Check for reused IDs
    if ($this->isIdReused($request)) {
        Log::warning('Attempt to use previously registered ID', [
            'user_id' => auth()->id(),
            'primary_id_type' => $request->input('id_type'),
            'secondary_id_type' => $request->input('id_type_secondary')
        ]);

        return response()->json([
            'success' => false,
            'code' => 'duplicate_id',
            'message' => 'One or both of these IDs appear to have been used previously. Please use different identification.'
        ], 400);
    }

    // Store the ID files temporarily
    $primaryIdPath = $request->file('id_card')->store('temp/primary_ids', 'local');
    $secondaryIdPath = $request->file('id_card_secondary')->store('temp/secondary_ids', 'local');
    
    // Store information in session
    Session::put('id_verification_data', [
        'primary_id_path' => $primaryIdPath,
        'primary_id_type' => $request->input('id_type'),
        'secondary_id_path' => $secondaryIdPath,
        'secondary_id_type' => $request->input('id_type_secondary'),
        'created_at' => now()
    ]);
    
    return response()->json([
        'success' => true,
        'message' => 'ID data stored successfully'
    ]);
}

/**
 * Check if ID data exists and is valid
 */
public function checkIdData()
{
    $idData = Session::get('id_verification_data');
    
    // Check if data exists and hasn't expired
    $valid = false;
    if ($idData && isset($idData['created_at'])) {
        $createdAt = new \DateTime($idData['created_at']);
        $now = new \DateTime();
        $interval = $createdAt->diff($now);
        
        // Valid if created less than 30 minutes ago
        $valid = $interval->i < 30;
    }
    
    return response()->json([
        'valid' => $valid
    ]);
}

/**
 * Complete the verification process with selfie and face matching
 */
public function completeVerification(Request $request)
{
    try {
        Log::info('Starting final verification step');
        
        // Check if ID data exists
        $idData = Session::get('id_verification_data');
        if (!$idData) {
            return response()->json([
                'error' => 'Session expired',
                'message' => 'Your verification session has expired. Please start over.'
            ], 400);
        }
        
        // Validate selfie
        $request->validate([
            'selfie' => 'required|image|mimes:jpeg,png|max:2048',
        ]);
        
        // Check for rate limiting
        $attemptsRemaining = $this->checkFaceMatchAttempts();
        if ($attemptsRemaining <= 0) {
            return response()->json([
                'error' => 'Too many attempts',
                'message' => 'You have reached the maximum number of verification attempts. Please try again in 30 minutes.',
                'cooldown_minutes' => self::COOLDOWN_MINUTES
            ], 429);
        }
        
        // Get files from storage
        $selfieBase64 = base64_encode(file_get_contents($request->file('selfie')->path()));
        $primaryIdBase64 = base64_encode(file_get_contents(storage_path('app/'.$idData['primary_id_path'])));
        $secondaryIdBase64 = base64_encode(file_get_contents(storage_path('app/'.$idData['secondary_id_path'])));
        
        // Compare with primary ID
        $primaryResult = $this->compareFaces($selfieBase64, $primaryIdBase64);
        
        // Compare with secondary ID
        $secondaryResult = $this->compareFaces($selfieBase64, $secondaryIdBase64);
        
        // Calculate metrics
        $averageScore = ($primaryResult['score'] + $secondaryResult['score']) / 2;
        
        // Add detailed logging of all scores
        Log::info('Face verification scores', [
            'primary_match_score' => $primaryResult['score'],
            'primary_verified' => $primaryResult['verified'],
            'secondary_match_score' => $secondaryResult['score'],
            'secondary_verified' => $secondaryResult['verified'],
            'average_score' => $averageScore,
            'both_verified' => $primaryResult['verified'] && $secondaryResult['verified'],
            'user_id' => $request->user()->id,
            'primary_id_type' => $idData['primary_id_type'],
            'secondary_id_type' => $idData['secondary_id_type']
        ]);
        
        // Determine verification status - both must pass the threshold
        $bothVerified = $primaryResult['verified'] && $secondaryResult['verified'];
        
        // Record this attempt
        $this->recordFaceMatchAttempt();
        
        // Process verification if successful
        if ($bothVerified) {
            $user = $request->user();
            
            // Move temporary files to permanent storage
            $selfieImagePath = $this->saveVerificationImage($request->file('selfie'), 'selfies', $user->id);
            
            // Move from temp to permanent storage
            $primaryIdImagePath = 'verification/primary_ids/' . $user->id . '_' . time() . '_primary.' . 
                pathinfo(storage_path('app/'.$idData['primary_id_path']), PATHINFO_EXTENSION);
            
            $secondaryIdImagePath = 'verification/secondary_ids/' . $user->id . '_' . time() . '_secondary.' .
                pathinfo(storage_path('app/'.$idData['secondary_id_path']), PATHINFO_EXTENSION);
            
            // Copy from temp to public storage
            \Storage::copy(
                $idData['primary_id_path'], 
                'public/' . $primaryIdImagePath
            );
            
            \Storage::copy(
                $idData['secondary_id_path'], 
                'public/' . $secondaryIdImagePath
            );
            
            // Update user record
            $user->update([
                'selfie_image_path' => $selfieImagePath,
                'primary_id_image_path' => $primaryIdImagePath,
                'secondary_id_image_path' => $secondaryIdImagePath,
            ]);
            
            $user->forceFill([
                'id_verified_at' => now()
            ])->save();
            
            $this->resetFaceMatchAttempts();
            
            // Store image hashes for future duplicate detection
            $this->storeIdHashesFromPaths(
                storage_path('app/'.$idData['primary_id_path']),
                storage_path('app/'.$idData['secondary_id_path']),
                $idData['primary_id_type'],
                $idData['secondary_id_type']
            );
            
            // Run OCR on ID card images
            $primaryIdOcrResult = $this->runOcr(storage_path('app/'.$idData['primary_id_path']), true);
            $secondaryIdOcrResult = $this->runOcr(storage_path('app/'.$idData['secondary_id_path']), true); 
         
           $formattedData = [
                // Only include the fields you want
                'primaryIdType' => $idData['primary_id_type'] ?? '',
                'secondaryIdType' => $idData['secondary_id_type'] ?? '',
                'nationality' => 'Filipino',
                'email' => auth()->user()->email ?? '',
            ];

            Session::put('verification_extracted_data', $formattedData);
            Session::save();
            Log::info('Final data stored in session', [
                'formatted_data' => $formattedData
            ]);
            
            // Clean up temp files
            \Storage::delete($idData['primary_id_path']);
            \Storage::delete($idData['secondary_id_path']);
            Session::forget('id_verification_data');
            
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
                'verified' => true,
                'message' => 'Face verification successful',
                'redirect' => $redirectUrl,
                'session_preserved' => true,
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
        // Record failed attempt even for errors
        $this->recordFaceMatchAttempt();
        
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
 * Store hashes of verified IDs from file paths
 * 
 * @param string $primaryIdPath
 * @param string $secondaryIdPath
 * @param string $primaryIdType
 * @param string $secondaryIdType
 */
private function storeIdHashesFromPaths($primaryIdPath, $secondaryIdPath, $primaryIdType, $secondaryIdType)
{
    try {
        $primaryIdHash = $this->generateImageHash($primaryIdPath);
        $secondaryIdHash = $this->generateImageHash($secondaryIdPath);
        
        Log::info('Storing ID hashes for user from paths', [
            'user_id' => auth()->id(),
            'primary_id_type' => $primaryIdType,
            'secondary_id_type' => $secondaryIdType
        ]);
        
        // Delete any existing hash records for this user first
        \App\Models\IdHash::where('user_id', auth()->id())->delete();
        
        // Create new hash record
        \App\Models\IdHash::create([
            'user_id' => auth()->id(),
            'primary_id_hash' => $primaryIdHash,
            'primary_id_type' => $primaryIdType,
            'secondary_id_hash' => $secondaryIdHash,
            'secondary_id_type' => $secondaryIdType,
            'verified_at' => now()
        ]);
        
    } catch (\Exception $e) {
        Log::error('Error storing ID hashes from paths', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}
}
