<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Category;
use Exception;
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

        // fetch newly listed for rent
        $newlyListed = Listing::whereHas('user', function (Builder $query) {
            $query->where('role', '!=', 'suspended');
        })
            ->with(['user', 'images'])
            ->where('approved', true)
            ->latest()
            ->limit(8)
            ->get();

        $categories = Category::select('id', 'name', 'description')->get();
        
        return Inertia::render('Home', [
            'CTAImage' => asset('storage/images/listing/CTA/mainCTA.jpg'),
            'listings' => $listings,
            'newlyListed' => $newlyListed,
            'categories' => $categories
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
    public function show($id)
    {
        try {
            $listing = Listing::whereHas('user', function (Builder $query) {
                $query->where('role', '!=', 'suspended');
            })
                ->with(['images', 'user', 'category'])
                ->where('approved', true)
                ->findOrFail($id); 
                
            $relatedListings = Listing::whereHas('user', function (Builder $query) {
                $query->where('role', '!=', 'suspended');
            })
                ->with(['images', 'user'])
                ->where('category_id', $listing->category_id)
                ->where('id', '!=', $id)
                ->where('approved', true)
                ->inRandomOrder()
                ->limit(4)
                ->get();

            return Inertia::render('Listing/Show', [
                'listing' => $listing,
                'relatedListings' => $relatedListings,
            ]);

        } catch (Exception $e) {
            // suggest random listings
            $suggestions = Listing::whereHas('user', function (Builder $query) {
                $query->where('role', '!=', 'suspended');
            })
                ->with(['images', 'user'])
                ->where('approved', true)
                ->inRandomOrder()
                ->limit(4)
                ->get();

            return Inertia::render('Listing/NotFound', [
                'message' => 'This listing has been removed or is no longer available.',
                'suggestions' => $suggestions
            ])->toResponse(request())->setStatusCode(404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Listing $listing)
    {
        $listing->load(['category', 'images']);
        $categories = Category::select('id', 'name')->get();

        return Inertia::render('Listing/Edit', [
            'listing' => $listing,
            'categories' => $categories
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Listing $listing)
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
        
        $listing->update($fields);

        if ($request->hasFile('images')) {
            // delete existing images from storage and database
            foreach ($listing->images as $image) {
                Storage::disk('public')->delete($image->image_path);
            }
            $listing->images()->delete();

            // Store new images
            foreach ($request->images as $index => $image) {
                $path = $image->store('images/listing', 'public');
                
                $listing->images()->create([
                    'image_path' => $path,
                    'order' => $index,
                ]);
            }
        }

        return redirect()->route('listing.show', $listing)->with('status', 'Listing updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Listing $listing)
    {
        // check if user owns the listing
        if ($listing->user_id !== $request->user()->id) {
            abort(403);
        }

        // delete images from storage
        foreach ($listing->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        // delete the listing (automatically deletes listing_images via cascade on delete)
        $listing->delete();

        return redirect()->route('my-rentals')->with('status', 'Listing deleted successfully.');
    }
}
