<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class MyListingsController extends Controller
{
    public function index(Request $request)
    {
        $listings = Listing::where('user_id', Auth::id())
            ->with(['category', 'images', 'location'])
            ->latest()
            ->get();

        return Inertia::render('MyListings/MyListings', [
            'listings' => $listings,
            'rejectionReasons' => Listing::getRejectionReasons()
        ]);
    }
}