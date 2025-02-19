<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\RejectionReason;
use App\Models\TakedownReason;
use App\Models\User;
use App\Models\RentalRequest;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Notifications\ListingApproved;
use App\Notifications\ListingRejected;
use App\Notifications\ListingTakenDown;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

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
        // Get user counts first
        $userCounts = [
            'total' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'suspended' => User::where('status', 'suspended')->count(),
        ];

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

        $users = $query->paginate(10)->appends($request->query());

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'filters' => $request->only(['search', 'status', 'sortBy']),
            'userCounts' => $userCounts
        ]);
    }

    public function showUser(User $user)
    {
        $user->load(['listings' => function($query) {
            $query->with(['category', 'images', 'user', 'location'])
                  ->latest();
        }]);

        $listingCounts = [
            'total' => $user->listings->count(),
            'pending' => $user->listings->where('status', 'pending')->count(),
            'approved' => $user->listings->where('status', 'approved')->count(),
            'rejected' => $user->listings->where('status', 'rejected')->count(),
            'taken_down' => $user->listings->where('status', 'taken_down')->count(),
        ];

        return Inertia::render('Admin/UserDetails', [
            'user' => $user,
            'rejectionReasons' => $this->getFormattedRejectionReasons(),
            'listingCounts' => $listingCounts 
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

    // for select options
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

    private function getFormattedTakedownReasons()
    {
        return TakedownReason::select('id', 'label', 'code', 'description')
            ->get()
            ->map(fn($reason) => [
                'value' => (string) $reason->id,
                'label' => $reason->label,
                'code' => $reason->code,
                'description' => $reason->description
            ])
            ->values()
            ->all();
    }

    public function listings(Request $request)
    {
        $query = Listing::with([
            'user', 
            'category', 
            'images', 
            'location',
            'latestRejection.rejectionReason',
            'latestTakedown.takedownReason', // Simplified!
        ]);

        // Get total counts before applying filters
        $listingCounts = [
            'total' => Listing::count(),
            'pending' => Listing::where('status', 'pending')->count(),
            'approved' => Listing::where('status', 'approved')->count(),
            'rejected' => Listing::where('status', 'rejected')->count(),
            'taken_down' => Listing::where('status', 'taken_down')->count(), 
        ];

        // Apply search filter
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('desc', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        // Apply sorting
        switch ($request->input('sortBy', 'latest')) {
            case 'oldest':
                $query->oldest();
                break;
            case 'title':
                $query->orderBy('title');
                break;
            case 'price-high':
                $query->orderByDesc('price');
                break;
            case 'price-low':
                $query->orderBy('price');
                break;
            default: // latest
                $query->latest();
        }

        $listings = $query->paginate(10)->appends($request->query());

        // only load rejection reasons if there are pending listings
        $hasPendingListings = collect($listings->items())->contains('status', 'pending');

        return Inertia::render('Admin/Listings', [
            'listings' => $listings,
            'rejectionReasons' => $hasPendingListings ? $this->getFormattedRejectionReasons() : [],
            'filters' => $request->only(['search', 'status', 'sortBy']),
            'listingCounts' => $listingCounts 
        ]);
    }

    public function showListing(Listing $listing)
    {
        $listing->load([
            'user', 
            'category', 
            'location', 
            'images',
            'latestRejection.rejectionReason',
            'rejectionReasons' => function($query) {
                $query->select([
                    'rejection_reasons.*',
                    'listing_rejections.created_at as rejected_at',
                    'listing_rejections.custom_feedback',
                    'users.name as admin_name'
                ])
                ->leftJoin('users', 'listing_rejections.admin_id', '=', 'users.id')
                ->orderBy('listing_rejections.created_at', 'desc');
            }, // get all rejection history
            'takedownReasons' => function($query) { 
                $query->select([
                    'takedown_reasons.*',
                    'listing_takedowns.created_at as taken_down_at',
                    'listing_takedowns.custom_feedback',
                    'users.name as admin_name'
                ])
                ->leftJoin('users', 'listing_takedowns.admin_id', '=', 'users.id')
                ->orderBy('listing_takedowns.created_at', 'desc');
            }
        ]);

        return Inertia::render('Admin/ListingDetails', [
            'listing' => $listing,
            // for populating select options
            'rejectionReasons' => $listing->status === 'pending' ? $this->getFormattedRejectionReasons() : [],
            'takedownReasons' => $listing->status === 'approved' ? $this->getFormattedTakedownReasons() : []
        ]);
    }

    public function approveListing(Listing $listing)
    {
        try {
            // Update listing status directly
            $listing->update([
                'status' => 'approved',
                'is_available' => true
            ]);

            // Notify user
            $listing->user->notify(new ListingApproved($listing));

            return back()->with('success', 'Listing approved successfully');
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Failed to approve listing. Please try again.');
        }
    }

    public function rejectListing(Request $request, Listing $listing)
    {
        // Verify the user is an admin
        if (!Auth::user()->status === 'admin') {
            abort(403, 'Only administrators can reject listings.');
        }

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
            DB::beginTransaction();
            
            // Update listing status
            $listing->update(['status' => 'rejected']);

            // Create rejection record with admin info
            $listing->rejectionReasons()->attach($validated['rejection_reason'], [
                'custom_feedback' => $validated['feedback'],
                'admin_id' => Auth::id() // Add the admin ID
            ]);

            DB::commit();

            // Notify user (outside transaction since it's not a database operation)
            $listing->user->notify(new ListingRejected($listing));

            return back()->with('success', 'Listing rejected successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return back()->with('error', 'Failed to reject listing. Please try again.');
        }
    }

    public function takedownListing(Request $request, Listing $listing)
    {
        // Check if listing is already taken down
        if ($listing->status === 'taken_down') {
            return back()->with('error', 'This listing has already been taken down.');
        }

        if ($listing->status !== 'approved') {
            return back()->with('error', 'Only approved listings can be taken down.');
        }

        $validator = Validator::make($request->all(), [
            'takedown_reason' => ['required', 'exists:takedown_reasons,id'],
            'feedback' => [
                'required_if:takedown_reason,other',
                'nullable',
                'string',
                'min:10',
                'max:1000',
                'regex:/^[^<>{}]*$/',
            ]
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        try {
            DB::beginTransaction();

            // Update listing status and availability
            $listing->update([
                'status' => 'taken_down',
                'is_available' => false
            ]);

            // Create takedown record
            $listing->takedownReasons()->attach($validated['takedown_reason'], [
                'custom_feedback' => $validated['feedback'],
                'admin_id' => Auth::id()
            ]);

            DB::commit();

            // Notify user (outside transaction)
            $listing->user->notify(new ListingTakenDown($listing));

            return back()->with('success', 'Listing taken down successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return back()->with('error', 'Failed to take down listing. Please try again.');
        }
    }

    public function rentalTransactions(Request $request)
    {
        $stats = [
            'total' => RentalRequest::count(),
            'pending' => RentalRequest::where('status', 'pending')->count(),
            'approved' => RentalRequest::where('status', 'approved')->count(),
            'renter_paid' => RentalRequest::where('status', 'renter_paid')->count(),
            'active' => RentalRequest::where('status', 'active')->count(),
            'completed' => RentalRequest::where('status', 'completed')->count(),
            'rejected' => RentalRequest::where('status', 'rejected')->count(),
            'cancelled' => RentalRequest::where('status', 'cancelled')->count(),
        ];

        $query = RentalRequest::with([
            'listing' => fn($q) => $q->with(['images', 'user']), 
            'renter',
            'payment_request',
            'latestRejection.rejectionReason',
            'latestCancellation.cancellationReason'
        ]);

        // Apply search filter
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->whereHas('listing', function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%");
                })
                ->orWhereHas('listing.user', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('renter', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            });
        }

        // Apply period filter
        if ($request->period) {
            $query->where('created_at', '>=', now()->subDays($request->period));
        }

        // Apply status filter
        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $transactions = $query->latest()->paginate(10)->appends($request->query());

        return Inertia::render('Admin/RentalTransactions', [
            'transactions' => $transactions,
            'stats' => $stats,
            'filters' => $request->only(['search', 'period', 'status'])
        ]);
    }

    public function rentalTransactionDetails(RentalRequest $rental)
    {
        $rental->load([
            'listing' => fn($q) => $q->with(['images', 'category', 'location', 'user']),
            'renter',
            'latestRejection.rejectionReason',
            'latestCancellation.cancellationReason',
            'timelineEvents'
        ]);

        return Inertia::render('Admin/RentalTransactionDetails', [
            'rental' => $rental
        ]);
    }
}