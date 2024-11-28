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
        $listings = Listing::whereHas('user', function(Builder $query) {
            $query->where('role', '!=', 'suspended');
        })
            ->with(['user', 'images'])
            ->where('approved', true)
            ->filter(request( ['search'] )) // or [$request->search]
            ->latest() // sort by created_at
            ->paginate(20)
            ->withQueryString(); // retains search query when navigating paginated links

        return Inertia::render('Explore', [
            'listings' => $listings,
            'searchTerm' => $request->search,
        ]);
    }
}
