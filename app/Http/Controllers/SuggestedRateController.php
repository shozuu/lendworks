<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SuggestedRateController extends Controller
{
    public function calculateDailyRate(Request $request)
    {
        // Validate the input
        $request->validate([
            'itemValue' => 'required|numeric|min:1',
        ]);

        $itemValue = $request->input('itemValue');

        // Logic to calculate rates
        if ($itemValue < 25000) {
            $minRate = $itemValue * 0.03;
            $maxRate = $itemValue * 0.05;
        } elseif ($itemValue <= 100000) {
            $minRate = $itemValue * 0.02;
            $maxRate = $itemValue * 0.03;
        } else {
            $minRate = $itemValue * 0.01;
            $maxRate = $itemValue * 0.02;
        }

        // Return the calculated rates
        return response()->json([
            'minRate' => $minRate,
            'maxRate' => $maxRate,
        ]);
    }
}
