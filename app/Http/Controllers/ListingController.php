<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ListingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $listings = Listing::whereHas('user', function (Builder $query) {
            $query->where('role', '!=', 'suspended');
        })
            ->with(['user', 'images'])
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
        $fields = $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:100'],
            'desc' => ['required', 'string', 'min:10', 'max:1000'],
            'category_id' => ['required', 'string', 'exists:categories,id'],
            'value' => ['required', 'integer', 'gt:0'],
            'price' => ['required', 'integer', 'gt:0'],
            'images' => ['required', 'array', 'min:1'], 
            'images.*' => ['required', 'image', 'file', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $listing = $request->user()->listings()->create($fields);

        if ($request->hasFile('images')) {
            foreach ($request->images as $index => $image) {
                $path = $image->store('images/listing', 'public'); 

                // save image details to the database
                $listing->images()->create([
                    'image_path' => $path,
                    'order' => $index,
                ]);
            }
        }

        return redirect()->route('my-rentals')->with('status', 'Listing created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Listing $listing)
    {
        $listing = Listing::whereHas('user', function (Builder $query) {
            $query->where('role', '!=', 'suspended');
        })
            ->with(['images', 'user', 'category'])
            ->where('approved', true)
            ->findOrFail($listing->id); 
            // find listing or return 404 if not found
            
        $relatedListings = Listing::whereHas('user', function (Builder $query) {
            $query->where('role', '!=', 'suspended');
        })
            ->with('images')
            ->where('category_id', $listing->category_id) 
            ->where('id', '!=', $listing->id) 
            ->limit(4)
            ->get();

        return Inertia::render('Listing/Show', [
            'listing' => $listing,
            'relatedListings' => $relatedListings, 
        ]);
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
    public function update(Request $request, Listing $listing)
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
