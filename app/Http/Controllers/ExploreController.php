<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExploreController extends Controller
{
    public function index(Request $request)
    {
        // Base query for available listings
        $query = Listing::whereHas('user', function (Builder $query) {
            $query->where('status', '!=', 'suspended');
        })
        ->with(['images', 'user', 'category', 'location'])
        ->where('status', 'approved')
        ->where('is_available', true);

        // Apply search filters
        if ($request->has('search')) {
            $query->filter(['search' => $request->search]);
        }

        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->has('sort')) {
            switch ($request->sort) {
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;
                case 'value_asc':
                    $query->orderBy('value', 'asc');
                    break;
                case 'value_desc':
                    $query->orderBy('value', 'desc');
                    break;
                default:
                    $query->latest();
            }
        } else {
            $query->latest();
        }

        $listings = $query->paginate(20);

        return Inertia::render('Explore', [
            'listings' => $listings,
            'searchTerm' => $request->search
        ]);
    }
}
