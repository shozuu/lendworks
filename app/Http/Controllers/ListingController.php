<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Category;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ListingController extends Controller
{
    public function index(Request $request)
    {
        // Base query for active listings
        $baseQuery = Listing::whereHas('user', function (Builder $query) {
            $query->where('status', '!=', 'suspended');
        })
            ->with(['user', 'images'])
            ->where('approved', true)
            ->where('is_available', true);

        // Get featured listings
        $listings = (clone $baseQuery)
            ->latest()
            ->limit(8)
            ->get();

        // Get newly listed items
        $newlyListed = (clone $baseQuery)
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

    public function create()
    {
        $categories = Category::select('id', 'name')->get();
        $locations = Auth::user()->locations;  // get user's saved locations
        return Inertia::render('Listing/Create', [
            'categories' => $categories,
            'locations' => $locations,
        ]);
    }

    public function store(Request $request)
    {
        $fields = $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:100'],
            'desc' => ['required', 'string', 'min:10', 'max:1000'],
            'category_id' => ['required', 'string', 'exists:categories,id'],
            'location_id' => ['required_if:new_location,false', 'nullable', 'exists:locations,id'],
            'value' => ['required', 'integer', 'gt:0'],
            'price' => ['required', 'integer', 'gt:0'],
            'images' => ['required', 'array', 'min:1'], 
            'images.*' => ['required', 'image', 'file', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            // new location fields if creating new location
            'new_location' => ['required', 'boolean'],
            'location_name' => ['required_if:new_location,true', 'nullable', 'string', 'max:100'],
            'address' => ['required_if:new_location,true', 'nullable', 'string', 'max:255'],
            'city' => ['required_if:new_location,true', 'nullable', 'string', 'max:100'],
            'province' => ['required_if:new_location,true', 'nullable', 'string', 'max:100'],
            'postal_code' => ['required_if:new_location,true', 'nullable', 'string', 'max:20'],
        ]);

        // Create new location if requested
        if ($request->new_location) {
            $location = $request->user()->locations()->create([
                'name' => $request->location_name,
                'address' => $request->address,
                'city' => $request->city,
                'province' => $request->province,
                'postal_code' => $request->postal_code,
            ]);
            $fields['location_id'] = $location->id;
        }

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

        return redirect()->route('my-listings')->with('status', 'Listing created successfully.');
    }

    public function show(Request $request, $id)
    {
        try {
            // Base query without approved filter
            $query = Listing::whereHas('user', function (Builder $query) {
                $query->where('role', '!=', 'suspended');
            })
            ->with(['images', 'user', 'category', 'location'])
            ->where('id', $id);

            $listing = $query->first();

            if (!$listing) {
                throw new Exception('Listing not found');
            }

            // Check if user can view this listing
            if (!$listing->approved && (!Auth::check() || Auth::id() !== $listing->user_id)) {
                throw new Exception('Listing not available');
            }

            // Get related listings that are available (only for approved listings)
            $relatedListings = $listing->approved ? 
                Listing::whereHas('user', function (Builder $query) {
                    $query->where('role', '!=', 'suspended');
                })
                ->with(['images', 'user'])
                ->where('category_id', $listing->category_id)
                ->where('id', '!=', $id)
                ->where('approved', true)
                ->where('is_available', true)
                ->inRandomOrder()
                ->limit(4)
                ->get()
                : collect([]);

            return Inertia::render('Listing/Show', [
                'listing' => $listing,
                'relatedListings' => $relatedListings,
                'showPendingMessage' => !$listing->approved
            ]);

        } catch (Exception $e) {
            // Suggest only available listings
            $suggestions = Listing::whereHas('user', function (Builder $query) {
                $query->where('role', '!=', 'suspended');
            })
                ->with(['images', 'user'])
                ->where('approved', true)
                ->where('is_available', true)
                ->inRandomOrder()
                ->limit(4)
                ->get();

            return Inertia::render('Listing/NotFound', [
                'message' => 'This listing has been removed or is no longer available.',
                'suggestions' => $suggestions
            ])->toResponse(request())->setStatusCode(404);
        }
    }

    public function edit(Listing $listing)
    {
        $listing->load(['category', 'images', 'location']);
        $categories = Category::select('id', 'name')->get();
        $locations = Auth::user()->locations;

        return Inertia::render('Listing/Edit', [
            'listing' => $listing,
            'categories' => $categories,
            'locations' => $locations,
        ]);
    }

    public function update(Request $request, Listing $listing)
    {
        $fields = $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:100'],
            'desc' => ['required', 'string', 'min:10', 'max:1000'],
            'category_id' => ['required', 'string', 'exists:categories,id'],
            'location_id' => ['required_if:new_location,false', 'nullable', 'exists:locations,id'],
            'value' => ['required', 'integer', 'gt:0'],
            'price' => ['required', 'integer', 'gt:0'],
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['required', 'image', 'file', 'mimes:jpg,jpeg,png,webp', 'max:3072'],

            // new location fields if creating new location
            'new_location' => ['required', 'boolean'],
            'location_name' => ['required_if:new_location,true', 'nullable', 'string', 'max:100'],
            'address' => ['required_if:new_location,true', 'nullable', 'string', 'max:255'],
            'city' => ['required_if:new_location,true', 'nullable', 'string', 'max:100'],
            'province' => ['required_if:new_location,true', 'nullable', 'string', 'max:100'],
            'postal_code' => ['required_if:new_location,true', 'nullable', 'string', 'max:20'],
        ]);

        if ($request->new_location) {
            $location = $request->user()->locations()->create([
                'name' => $request->location_name,
                'address' => $request->address,
                'city' => $request->city,
                'province' => $request->province,
                'postal_code' => $request->postal_code,
            ]);
            $fields['location_id'] = $location->id;
        }

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
        return redirect()->route('my-listings')->with('status', 'Listing deleted successfully.');
    }

    public function toggleAvailability(Listing $listing)
    {
        // Ensure the user owns the listing
        if ($listing->user_id !== Auth::id()) {
            abort(403);
        }

        // Only allow toggling if listing is approved
        if (!$listing->approved) {
            return back()->with('error', 'Cannot change availability of unapproved listings.');
        }

        $listing->update([
            'is_available' => !$listing->is_available
        ]);

        return back()->with('success', 'Listing availability updated.');
    }
}
