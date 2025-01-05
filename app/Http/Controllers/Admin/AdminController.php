<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'totalUsers' => User::count(),
            'totalListings' => Listing::count(),
            'pendingApprovals' => Listing::where('approved', false)->count(),
            'activeUsers' => User::where('status', 'active')->count(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats
        ]);
    }

    public function users(Request $request)
    {
        $query = User::withCount('listings');

        // Search
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Status filter - only apply if status is not 'all'
        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        // Sort
        switch ($request->input('sortBy', 'latest')) {
            case 'oldest':
                $query->oldest();
                break;
            case 'name':
                $query->orderBy('name');
                break;
            case 'listings':
                $query->orderByDesc('listings_count');
                break;
            default:
                $query->latest();
        }

        $users = $query->paginate(10)->withQueryString();

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'filters' => $request->only(['search', 'status', 'sortBy'])
        ]);
    }

    public function showUser(User $user)
    {
        $user->load(['listings' => function($query) {
            $query->with(['category', 'images', 'user', 'location'])
                  ->latest();
        }]);

        return Inertia::render('Admin/UserDetails', [
            'user' => $user
        ]);
    }

    public function suspendUser(User $user)
    {
        $user->update(['status' => 'suspended']);
        return back()->with('success', 'User suspended successfully');
    }

    public function activateUser(User $user)
    {
        $user->update(['status' => 'active']);
        return back()->with('success', 'User activated successfully');
    }

    public function listings()
    {
        $listings = Listing::with(['user', 'category', 'images', 'location'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Admin/Listings', [
            'listings' => $listings
        ]);
    }

    public function showListing(Listing $listing)
    {
        return Inertia::render('Admin/ListingDetails', [
            'listing' => $listing->load(['user', 'category', 'location', 'images'])
        ]);
    }

    public function approveListing(Listing $listing)
    {
        $listing->update(['approved' => true]);
        return back()->with('success', 'Listing approved successfully');
    }

    public function rejectListing(Listing $listing)
    {
        $listing->update(['approved' => false]);
        return back()->with('success', 'Listing rejected successfully');
    }
}