<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Inertia\Inertia;
use Illuminate\Http\Request;

class MyListingsController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('MyListings/MyListings');
    }
}