<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenStreetMapController extends Controller
{
    public function search(Request $request)
    {
        try {
            // Validate input
            $query = $request->input('q');
            
            if (empty($query) || strlen($query) < 3) {
                return response()->json(['error' => 'Search query must be at least 3 characters'], 400);
            }

            // Log the request for debugging
            Log::info('OpenStreetMap search request', [
                'query' => $query,
                'user' => \Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::id() : 'guest'
            ]);
            
            // Skip SSL verification temporarily for testing
            $response = Http::withoutVerifying()
                ->timeout(15)
                ->withHeaders([
                    // Important: A proper user-agent is required by Nominatim's ToS
                    'User-Agent' => 'LendworksApp/1.0 (contact@example.com)', 
                    'Accept-Language' => 'en-US,en;q=0.9',
                    'Referer' => config('app.url', 'http://localhost')
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'countrycodes' => $request->input('countrycodes', 'ph'),
                    'limit' => $request->input('limit', 5)
                ]);
            
            // Log the response for debugging
            Log::info('OpenStreetMap response', [
                'status' => $response->status(),
                'content_length' => strlen($response->body()),
                'result_count' => is_array($response->json()) ? count($response->json()) : 'Not an array'
            ]);
            
            if ($response->successful()) {
                return response()->json($response->json());
            } else {
                Log::error('OpenStreetMap API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                return response()->json([
                    'error' => 'Error from address service: ' . $response->status()
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('OpenStreetMap exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()   
            ]);
            
            return response()->json([
                'error' => 'Server error processing your request'
            ], 500);
        }
    }
    public function reverseGeocode(Request $request)
{
    try {
        // Validate input
        $request->validate([
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
        ]);
        
        $lat = $request->input('lat');
        $lon = $request->input('lon');
        
        Log::info('OpenStreetMap reverse geocode request', [
            'lat' => $lat,
            'lon' => $lon,
            'user' => \Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::id() : 'guest'
        ]);
        
        // Skip SSL verification temporarily for testing
        $response = Http::withoutVerifying()
            ->timeout(15)
            ->withHeaders([
                'User-Agent' => 'LendworksApp/1.0 (contact@example.com)', 
                'Accept-Language' => 'en-US,en;q=0.9',
                'Referer' => config('app.url', 'http://localhost')
            ])
            ->get('https://nominatim.openstreetmap.org/reverse', [
                'lat' => $lat,
                'lon' => $lon,
                'format' => 'json',
                'addressdetails' => 1,
                'zoom' => 18
            ]);
        
         if ($response->successful()) {
            // Process the address data into a more usable format
            $data = $response->json();
            $address = $data['address'] ?? [];
            
            return response()->json([
                'success' => true,
                'display_name' => $data['display_name'] ?? '',
                'address' => [
                    'street' => $address['road'] ?? $address['street'] ?? '',
                    'building' => $address['house_number'] ?? $address['building'] ?? '',
                    'neighbourhood' => $address['neighbourhood'] ?? '',
                    'zone' => $address['zone'] ?? $address['quarter'] ?? '',
                    'barangay' => $address['suburb'] ?? $address['village'] ?? $address['hamlet'] ?? '',
                    'city' => $address['city'] ?? $address['town'] ?? $address['municipality'] ?? '',
                    'province' => $address['state'] ?? $address['province'] ?? $address['county'] ?? '',
                    'postal_code' => $address['postcode'] ?? '',
                    'country' => $address['country'] ?? ''
                ],
                'raw_response' => $data
            ]);
        } else {
            Log::error('OpenStreetMap reverse geocode API error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error from address service: ' . $response->status()
            ], 500);
        }
    } catch (\Exception $e) {
        Log::error('OpenStreetMap reverse geocode exception', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        
        return response()->json([
            'success' => false,
            'error' => 'Server error processing your request'
        ], 500);
    }
}
}
