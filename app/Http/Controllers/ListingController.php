<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Http\Requests\UpdateListingRequest;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ListingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    // if this will render home, we should return the popular tools, categories, and newly listed for rent
    {
        $listings = Listing::whereHas('user', function (Builder $query) {
        $query->where('role', '!=', 'suspended');
    })
            ->with('user')
            ->where('approved', true)
            ->latest()
            ->limit(8)
            ->get();

        return Inertia::render('Home', [
            'listings' => $listings,
            'CTAImage' => asset('storage/images/listing/CTA/mainCTA.jpg'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::select('id', 'name')->get();
        return Inertia::render('Listing/Create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        dd($request);
        
    }

    /**
     * Display the specified resource.
     */
    public function show(Listing $listing)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Listing $listing)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateListingRequest $request, Listing $listing)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Listing $listing)
    {
        //
    }
}