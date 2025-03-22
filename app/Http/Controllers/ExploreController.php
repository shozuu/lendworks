<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Category;
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
        if ($request->has('search') && $request->search !== null) {
            $query->filter(['search' => $request->search]);
        }

        if ($request->has('category') && $request->category !== null) {
            $query->where('category_id', $request->category);
        }

        // Price range filter
        if ($request->has('minPrice') && is_numeric($request->minPrice)) {
            $query->where('price', '>=', $request->minPrice);
        }

        if ($request->has('maxPrice') && is_numeric($request->maxPrice)) {
            $query->where('price', '<=', $request->maxPrice);
        }

        // Predefined price range filter
        if ($request->has('priceRange')) {
            switch ($request->priceRange) {
                case 'under500':
                    $query->where('price', '<', 500);
                    break;
                case '500to1000':
                    $query->whereBetween('price', [500, 1000]);
                    break;
                case '1000to2000':
                    $query->whereBetween('price', [1000, 2000]);
                    break;
                case '2000to5000':
                    $query->whereBetween('price', [2000, 5000]);
                    break;
                case 'over5000':
                    $query->where('price', '>', 5000);
                    break;
            }
        }

        // Time frame filter
        if ($request->has('timeFrame')) {
            switch ($request->timeFrame) {
                case 'recent':
                    $query->where('created_at', '>=', now()->subHours(1));
                    break;
                case '24h':
                    $query->where('created_at', '>=', now()->subHours(24));
                    break;
                case '7d':
                    $query->where('created_at', '>=', now()->subDays(7));
                    break;
                case '30d':
                    $query->where('created_at', '>=', now()->subDays(30));
                    break;
                case '6m':
                    $query->where('created_at', '>=', now()->subMonths(6));
                    break;
                case '1y':
                    $query->where('created_at', '>=', now()->subYear());
                    break;
                case 'over1y':
                    $query->where('created_at', '<', now()->subYear());
                    break;
            }
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

        $categories = Category::select('id', 'name')->get();

        return Inertia::render('Explore', [
            'listings' => $listings,
            'searchTerm' => $request->search,
            'categories' => $categories,
            'selectedCategory' => $request->category,
            'filters' => [
                'minPrice' => $request->minPrice,
                'maxPrice' => $request->maxPrice,
                'priceRange' => $request->priceRange,
                'timeFrame' => $request->timeFrame,
            ],
            'priceRanges' => [
                ['id' => 'under500', 'label' => 'Under ₱500'],
                ['id' => '500to1000', 'label' => '₱500 - ₱1,000'],
                ['id' => '1000to2000', 'label' => '₱1,000 - ₱2,000'],
                ['id' => '2000to5000', 'label' => '₱2,000 - ₱5,000'],
                ['id' => 'over5000', 'label' => 'Over ₱5,000'],
            ],
            'timeFrames' => [
                ['id' => 'recent', 'label' => 'Recently Available'],
                ['id' => '24h', 'label' => 'Last 24 hours'],
                ['id' => '7d', 'label' => 'Last 7 days'],
                ['id' => '30d', 'label' => 'Last 30 days'],
                ['id' => '6m', 'label' => 'Last 6 months'],
                ['id' => '1y', 'label' => 'Last year'],
                ['id' => 'over1y', 'label' => 'More than a year'],
            ],
        ]);
    }
}
