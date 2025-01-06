<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Notifications\ListingApproved;
use App\Notifications\ListingRejected;
use App\Notifications\ListingTakenDown;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'totalUsers' => User::count(),
            'totalListings' => Listing::count(),
            'pendingApprovals' => Listing::where('status', 'pending')->count(),
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
        $listing->update([
            'status' => 'approved',
            'rejection_reason' => null
        ]);

        // Notify the user
        $listing->user->notify(new ListingApproved($listing));

        return back()->with('success', 'Listing approved successfully');
    }

    public function rejectListing(Request $request, Listing $listing)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:10|max:1000'
        ]);

        $listing->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason']
        ]);

        // Notify the user
        $listing->user->notify(new ListingRejected($listing));

        return back()->with('success', 'Listing rejected successfully');
    }

    public function takedownListing(Request $request, Listing $listing)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:10|max:1000'
        ]);

        $listing->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['reason'],
            'is_available' => false
        ]);

        // Notify the user
        $listing->user->notify(new ListingTakenDown($listing));

        return back()->with('success', 'Listing taken down successfully');
    }
}