<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class MyRentalsController extends Controller
{
    public function index(Request $request)
    {
        $statusCounts = [
            'all' => 0,
            'active' => 0,     // All ongoing rentals
            'upcoming' => 0,   // Approved but not started
            'pending' => 0,    // Awaiting approval or payment
            'completed' => 0,
            'cancelled' => 0
        ];

        $rentals = $request->user()
            ->rentals()
            ->with(['listing.images', 'listing.user', 'status'])
            ->latest()
            ->get();

        foreach ($rentals as $rental) {
            $statusCounts['all']++;
            
            if ($rental->status_id === 4) {
                $statusCounts['active']++;
            } elseif (in_array($rental->status_id, [2, 3])) {
                $statusCounts['upcoming']++;
            } elseif ($rental->status_id === 1) {
                $statusCounts['pending']++;
            } elseif ($rental->status_id === 5) {
                $statusCounts['completed']++;
            } elseif ($rental->status_id === 6) {
                $statusCounts['cancelled']++;
            }
        }

        return Inertia::render('MyRentals/Index', [
            'rentals' => $request->user()
                ->rentals()
                ->with(['listing.images', 'listing.user', 'status'])
                ->latest()
                ->paginate(10),
            'statuses' => $statusCounts
        ]);
    }
}