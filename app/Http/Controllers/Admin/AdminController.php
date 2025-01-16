<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\RejectionReason;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Notifications\ListingApproved;
use App\Notifications\ListingRejected;
use App\Notifications\ListingTakenDown;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

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

    private function getFormattedRejectionReasons()
    {
        return RejectionReason::select('id', 'label', 'code', 'description', 'action_needed')
            ->get()
            ->map(fn($reason) => [
                'value' => (string) $reason->id, 
                'label' => $reason->label,
                'code' => $reason->code,
                'description' => $reason->description,
                'action_needed' => $reason->action_needed
            ])
            ->values()
            ->all();
    }

    public function listings()
    {
        $listings = Listing::with(['user', 'category', 'images', 'location'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Admin/Listings', [
            'listings' => $listings,
            'rejectionReasons' => $this->getFormattedRejectionReasons()
        ]);
    }

    public function showListing(Listing $listing)
    {
        return Inertia::render('Admin/ListingDetails', [
            'listing' => $listing->load(['user', 'category', 'location', 'images']),
            'rejectionReasons' => $this->getFormattedRejectionReasons()
        ]);
    }

    private function updateListingStatus(Listing $listing, $status, $reason = null)
    {
        $data = [
            'status' => $status,
            'is_available' => $status === 'approved'
        ];

        // Only include rejection_reason for rejected or taken down status
        if (in_array($status, ['rejected', 'taken_down'])) {
            $data['rejection_reason'] = $reason;
        }

        $listing->update($data);

        // Notify user based on status
        switch ($status) {
            case 'approved':
                $listing->user->notify(new ListingApproved($listing));
                break;
            case 'rejected':
                $listing->user->notify(new ListingRejected($listing));
                break;
            case 'taken_down':
                $listing->user->notify(new ListingTakenDown($listing));
                break;
        }
    }

    public function approveListing(Listing $listing)
    {
        $this->updateListingStatus($listing, 'approved');
        return back()->with('success', 'Listing approved successfully');
    }

    public function rejectListing(Request $request, Listing $listing)
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => ['required', 'exists:rejection_reasons,id'],
            'feedback' => [
                'required_if:rejection_reason,other',
                'nullable',
                'string',
                'min:10',
                'max:1000',
                // Prevent common XSS patterns
                'regex:/^[^<>{}]*$/',
                function ($attribute, $value, $fail) {
                    // Check for suspicious patterns
                    $suspicious = ['javascript:', 'onerror=', 'onclick=', 'eval('];
                    foreach ($suspicious as $pattern) {
                        if (stripos($value, $pattern) !== false) {
                            $fail('The feedback contains invalid content.');
                        }
                    }
                },
            ]
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        // Sanitize the feedback before saving
        if (!empty($validated['feedback'])) {
            $validated['feedback'] = Str::of($validated['feedback'])
                ->trim()
                ->replace(['<', '>', '{', '}', '[', ']'], '')
                ->limit(1000);
        }

        try {
            // Update listing status
            $listing->update(['status' => 'rejected']);

            // Create rejection record with sanitized feedback
            $listing->rejectionReasons()->attach($validated['rejection_reason'], [
                'custom_feedback' => $validated['feedback']
            ]);

            // Notify user
            $listing->user->notify(new ListingRejected($listing));

            return back()->with('success', 'Listing rejected successfully');
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Failed to reject listing. Please try again.');
        }
    }

    public function takedownListing(Request $request, Listing $listing)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:10|max:1000'
        ]);

        $this->updateListingStatus($listing, 'taken_down', $validated['reason']);
        return back()->with('success', 'Listing taken down successfully');
    }
}