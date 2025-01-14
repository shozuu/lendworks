<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\RejectionReason;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class MyListingsController extends Controller
{
    public function index(Request $request)
    {
        $listings = Listing::where('user_id', Auth::id())
            ->with(['category', 'images', 'location', 'latestRejection.rejectionReason'])
            ->latest()
            ->get();

        return Inertia::render('MyListings/MyListings', [
            'listings' => $listings,
            'rejectionReasons' => RejectionReason::select('id', 'label', 'code', 'description', 'action_needed')
                ->get()
                ->map(fn($reason) => [
                    'value' => (string) $reason->id,
                    'label' => $reason->label,
                    'description' => $reason->description,
                    'action_needed' => $reason->action_needed
                ])
        ]);
    }
}