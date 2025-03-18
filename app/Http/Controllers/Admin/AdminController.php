<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\PaymentRequest;
use App\Models\RejectionReason;
use App\Models\TakedownReason;
use App\Models\User;
use App\Models\RentalRequest;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Notifications\ListingApproved;
use App\Notifications\ListingRejected;
use App\Notifications\ListingTakenDown;
use App\Notifications\PaymentRejected;
use App\Notifications\PaymentVerified;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Change Log - AdminController.php
     * 
     * Changes Made:
     * 1. Enhanced dashboard() method with additional statistics:
     *    - Added recentActivity metrics for today's activities
     *    - Added categoryBreakdown for listing categories distribution
     *    - Added listingPriceDistribution for price range analysis
     *    - Added topLocations to show most active cities
     *    - Added mostActiveUsers to track top contributors
     *    - Added userGrowth metrics for different time periods
     * 
     * 2. Improved error handling:
     *    - Added null checks with fallback values
     *    - Added error handling for potentially missing relationships
     *    - Added proper data formatting for frontend consumption
     * 
     * 3. Optimized database queries:
     *    - Used proper aggregates and grouping
     *    - Implemented eager loading for relationships
     *    - Added efficient filtering for approved listings
     * 
     * 4. Added getCategoryFilters method to get categories with counts
     * 5. Modified listings method to include category filter
     * 6. Added category filter to query builder
     */

    public function dashboard()
    {
        // Base stats calculation
        $activeListingsCount = Listing::where('status', 'approved')
                                     ->where('is_available', true)
                                     ->count();
        
        $totalRejectedListings = Listing::where('status', 'rejected')->count();
        $totalTakenDownListings = Listing::where('status', 'taken_down')->count();

        // Update the revenueStats calculation
        $monthlyRevenue = RentalRequest::where(function($query) {
            $query->where('status', 'completed')
                  ->orWhere('status', 'completed_with_payments')
                  ->orWhere('status', 'completed_pending_payments');
        })
        ->where('created_at', '>=', now()->startOfMonth())
        ->sum(DB::raw('service_fee * 2')); // Multiply by 2 since we collect from both parties

        $stats = [
            'totalUsers' => User::count(),
            'totalListings' => Listing::count(),
            'pendingApprovals' => Listing::where('status', 'pending')->count(),
            'activeUsers' => User::where('status', 'active')->count(),
            'verifiedUsers' => User::where('email_verified_at', '!=' , null)->count(),
            'newUsersThisMonth' => User::whereMonth('created_at', now()->month)->count(),
            'activeListings' => Listing::where('status', 'approved')
                                     ->where('is_available', true)
                                     ->count(),
            'suspendedUsers' => User::where('status', 'suspended')->count(),
            'listingStats' => [
                'approved' => $activeListingsCount,
                'rejected' => $totalRejectedListings,
                'takenDown' => $totalTakenDownListings,
            ],
            'averageListingPrice' => Listing::where('status', 'approved')
                                          ->average('price') ?? 0,
            'highestListingPrice' => Listing::where('status', 'approved')
                                          ->max('price') ?? 0,
            'lowestListingPrice' => Listing::where('status', 'approved')
                                         ->where('price', '>', 0)
                                         ->min('price') ?? 0,
            'unverifiedUsers' => User::whereNull('email_verified_at')->count(),

            // Add recent activity stats
            'recentActivity' => [
                'newUsersToday' => User::whereDate('created_at', today())->count(),
                'newListingsToday' => Listing::whereDate('created_at', today())->count(),
                'pendingApprovalsToday' => Listing::where('status', 'pending')
                                                ->whereDate('created_at', today())
                                                ->count(),
            ],

            // Update category breakdown to include average price
            'categoryBreakdown' => Listing::where('status', 'approved')
                ->select(
                    'category_id',
                    DB::raw('count(*) as count'),
                    DB::raw('ROUND(AVG(price), 2) as average_price')
                )
                ->groupBy('category_id')
                ->with('category:id,name')
                ->get()
                ->map(fn($item) => [
                    'name' => $item->category->name ?? 'Uncategorized',
                    'count' => $item->count,
                    'average_price' => $item->average_price ?? 0,
                ]),

            // Add price distribution
            'listingPriceDistribution' => [
                'under100' => Listing::where('status', 'approved')
                                    ->where('price', '<', 100)->count(),
                'under500' => Listing::where('status', 'approved')
                                    ->whereBetween('price', [100, 499])->count(),
                'under1000' => Listing::where('status', 'approved')
                                     ->whereBetween('price', [500, 999])->count(),
                'over1000' => Listing::where('status', 'approved')
                                    ->where('price', '>=', 1000)->count(),
            ],

            // Add top locations
            'topLocations' => Listing::where('status', 'approved')
                ->select('location_id', DB::raw('count(*) as count'))
                ->groupBy('location_id')
                ->with('location:id,city')
                ->orderByDesc('count')
                ->limit(5)
                ->get()
                ->map(fn($item) => [
                    'city' => $item->location->city ?? 'Unknown',
                    'count' => $item->count,
                ]),

            // Add most active users
            'mostActiveUsers' => User::withCount(['listings' => function($query) {
                    $query->where('status', 'approved');
                }])
                ->orderByDesc('listings_count')
                ->limit(5)
                ->get(['id', 'name', 'email'])
                ->map(fn($user) => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'listings_count' => $user->listings_count,
                ]),

            // Growth stats
            'userGrowth' => [
                'lastWeek' => User::whereBetween('created_at', 
                    [now()->subWeek(), now()])->count(),
                'lastMonth' => User::whereBetween('created_at', 
                    [now()->subMonth(), now()])->count(),
                'last3Months' => User::whereBetween('created_at', 
                    [now()->subMonths(3), now()])->count(),
            ],

            'transactionStats' => [
                'completed' => RentalRequest::where('created_at', '>=', now()->subDays(30))
                    ->whereIn('status', [
                        'completed',
                        'completed_with_payments',
                        'completed_pending_payments'
                    ])
                    ->count(),
                'active' => RentalRequest::where('status', 'active')->count(),
            ],

            'revenueStats' => [
                'monthly' => $monthlyRevenue,
                'total' => RentalRequest::whereIn('status', [
                    'completed',
                    'completed_with_payments',
                    'completed_pending_payments'
                ])
                ->sum(DB::raw('service_fee * 2')), // Total all-time revenue
            ],

            'performanceStats' => [
                'successRate' => $this->calculateSuccessRate(),
                'avgResponseTime' => $this->calculateAverageResponseTime(),
                'disputeRate' => $this->calculateDisputeRate(),
                'onTimeReturnRate' => $this->calculateOnTimeReturnRate(),
            ],
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats
        ]);
    }

    private function calculateSuccessRate()
    {
        $total = RentalRequest::whereNotIn('status', ['pending', 'active'])->count();
        $completed = RentalRequest::whereIn('status', [
            'completed',
            'completed_with_payments',
            'completed_pending_payments'
        ])->count();
        
        return $total > 0 ? round(($completed / $total) * 100) : 0;
    }

    private function calculateAverageResponseTime()
    {
        // Get the average time between creation and first approval/rejection from timeline events
        return round(DB::table('rental_requests as r')
            ->join('rental_timeline_events as e', 'r.id', '=', 'e.rental_request_id')
            ->where('e.event_type', 'approved')
            ->orWhere('e.event_type', 'rejected')
            ->whereNotNull('r.created_at')
            ->whereNotNull('e.created_at')
            ->average(DB::raw('TIMESTAMPDIFF(HOUR, r.created_at, e.created_at)')) ?? 0);
    }

    private function calculateDisputeRate()
    {
        $totalTransactions = RentalRequest::count();
        $disputedTransactions = RentalRequest::whereHas('dispute')->count();
        
        return $totalTransactions > 0 
            ? round(($disputedTransactions / $totalTransactions) * 100, 1) 
            : 0;
    }

    private function calculateOnTimeReturnRate()
    {
        $completedRentals = RentalRequest::where('status', 'completed')->count();
        $onTimeReturns = RentalRequest::where('status', 'completed')
            ->whereRaw('return_at <= end_date')
            ->count();
        
        return $completedRentals > 0 
            ? round(($onTimeReturns / $completedRentals) * 100) 
            : 0;
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

        // Get user counts for filters
        $userCounts = [
            'total' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'suspended' => User::where('status', 'suspended')->count(),
            'verified' => User::whereNotNull('email_verified_at')->count(),
            'unverified' => User::whereNull('email_verified_at')->count(),
        ];

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

        // Verification filter
        if ($verified = $request->input('verified')) {
            if ($verified === 'verified') {
                $query->whereNotNull('email_verified_at');
            } elseif ($verified === 'unverified') {
                $query->whereNull('email_verified_at');
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
            'filters' => $request->only(['search', 'status', 'sortBy', 'verified']),
            'userCounts' => $userCounts // Add this line
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

        // Get categories with counts for this user's listings
        $categories = DB::table('categories')
            ->leftJoin('listings', function($join) use ($user) {
                $join->on('categories.id', '=', 'listings.category_id')
                     ->where('listings.user_id', '=', $user->id);
            })
            ->select('categories.id', 'categories.name', DB::raw('COUNT(listings.id) as count'))
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('categories.name')
            ->get();

        return Inertia::render('Admin/UserDetails', [
            'user' => $user,
            'rejectionReasons' => $this->getFormattedRejectionReasons(),
            'listingCounts' => $listingCounts,
            'categories' => $categories // Add this line
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

        // Get categories with their listing counts
        $categories = DB::table('categories')
            ->leftJoin('listings', 'categories.id', '=', 'listings.category_id')
            ->select('categories.id', 'categories.name', DB::raw('COUNT(listings.id) as count'))
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('categories.name')
            ->get();

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

        // Add category filter
        if ($category = $request->input('category')) {
            if ($category !== 'all') {
                $query->where('category_id', $category);
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
            'filters' => $request->only(['search', 'status', 'sortBy', 'category']), // Added category
            'listingCounts' => $listingCounts,
            'categories' => $categories, // Add categories with counts
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
            'renter_paid' => RentalRequest::where('status', 'to_handover')->count(),
            'active' => RentalRequest::where('status', 'active')->count(),
            'pending_return' => RentalRequest::where('status', 'pending_return')->count(),
            'return_scheduled' => RentalRequest::where('status', 'return_scheduled')->count(),
            'pending_return_confirmation' => RentalRequest::where('status', 'pending_return_confirmation')->count(),
            'completed' => RentalRequest::whereIn('status', ['completed', 'completed_with_payments', 'completed_pending_payments'])->count(),
            'rejected' => RentalRequest::where('status', 'rejected')->count(),
            'cancelled' => RentalRequest::where('status', 'cancelled')->count(),
        ];

        $query = RentalRequest::with([
            'listing:id,title,user_id',
            'listing.images:id,listing_id,image_path',
            'listing.user:id,name',
            'renter:id,name',
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

        // Update status filter to include all completed states
        if ($request->status && $request->status !== 'all') {
            if ($request->status === 'completed') {
                $query->whereIn('status', ['completed', 'completed_with_payments', 'completed_pending_payments']);
            } else {
                $query->where('status', $request->status);
            }
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
        // Add viewer_id to help with determining actions
        $rental->viewer_id = Auth::id();
        
        $rental->load([
            'listing' => fn($q) => $q->with(['images', 'category', 'location', 'user']),
            'renter',
            'latestRejection.rejectionReason',
            'latestCancellation.cancellationReason',
            'timelineEvents.actor',  // Make sure actor is loaded for timeline
            'payment_request',
            'completion_payments',
            'pickup_schedules' // Add this line
        ]);

        return Inertia::render('Admin/RentalTransactionDetails', [
            'rental' => $rental
        ]);
    }

    public function payments()
    {
        $payments = PaymentRequest::with(['rentalRequest.listing', 'rentalRequest.renter'])
            ->latest()
            ->paginate(10);

        $stats = [
            'total' => PaymentRequest::count(),
            'pending' => PaymentRequest::where('status', 'pending')->count(),
            'verified' => PaymentRequest::where('status', 'verified')->count(),
            'rejected' => PaymentRequest::where('status', 'rejected')->count(),
        ];

        return Inertia::render('Admin/PaymentRequests', [
            'payments' => $payments,
            'stats' => $stats
        ]);
    }

    public function revenue(Request $request)
    {
        $dateRange = $request->input('dateRange', 'last30');
        $sort = $request->input('sort', 'latest');
        $graphStartDate = $request->input('graphStartDate');
        $graphEndDate = $request->input('graphEndDate');

        // Base query for completed transactions
        $baseQuery = RentalRequest::whereIn('status', ['completed', 'completed_with_payments']);

        // Apply date range filter
        switch ($dateRange) {
            case 'today':
                $baseQuery->whereDate('created_at', today());
                break;
            case 'yesterday':
                $baseQuery->whereDate('created_at', today()->subDay());
                break;
            case 'last7':
                $baseQuery->where('created_at', '>=', now()->subDays(7));
                break;
            case 'last30':
                $baseQuery->where('created_at', '>=', now()->subDays(30));
                break;
            case 'thisMonth':
                $baseQuery->whereMonth('created_at', now()->month)
                         ->whereYear('created_at', now()->year);
                break;
            case 'lastMonth':
                $baseQuery->whereMonth('created_at', now()->subMonth()->month)
                         ->whereYear('created_at', now()->subMonth()->year);
                break;
            case 'last90':
                $baseQuery->where('created_at', '>=', now()->subDays(90));
                break;
            case 'thisYear':
                $baseQuery->whereYear('created_at', now()->year);
                break;
            case 'lastYear':
                $baseQuery->whereYear('created_at', now()->subYear()->year);
                break;
            case 'allTime':
                // No filter needed for all time
                break;
        }

        // Rest of the revenue calculation logic remains the same
        $revenue = [
            'total' => RentalRequest::whereIn('status', ['completed', 'completed_with_payments'])
                                   ->sum(DB::raw('service_fee * 2')),
            'monthly' => RentalRequest::whereIn('status', ['completed', 'completed_with_payments'])
                                     ->whereMonth('created_at', now()->month)
                                     ->sum(DB::raw('service_fee * 2')),
            'today' => RentalRequest::whereIn('status', ['completed', 'completed_with_payments'])
                                   ->whereDate('created_at', today())
                                   ->sum(DB::raw('service_fee * 2')),
            'average' => $baseQuery->clone()->avg(DB::raw('service_fee * 2')) ?? 0,
            'lastWeek' => RentalRequest::whereIn('status', ['completed', 'completed_with_payments'])
                                      ->where('created_at', '>=', now()->subDays(7))
                                      ->sum(DB::raw('service_fee * 2')),
            'lastMonth' => RentalRequest::whereIn('status', ['completed', 'completed_with_payments'])
                                       ->where('created_at', '>=', now()->subDays(30))
                                       ->sum(DB::raw('service_fee * 2')),
            'lastQuarter' => RentalRequest::whereIn('status', ['completed', 'completed_with_payments'])
                                         ->where('created_at', '>=', now()->subDays(90))
                                         ->sum(DB::raw('service_fee * 2')),
        ];

        // Apply sorting
        switch ($sort) {
            case 'oldest':
                $baseQuery->oldest();
                break;
            case 'highest':
                $baseQuery->orderByDesc('service_fee');
                break;
            case 'lowest':
                $baseQuery->orderBy('service_fee');
                break;
            default:
                $baseQuery->latest();
        }

        $transactions = $baseQuery->with(['listing:id,title', 'renter:id,name'])
                                ->paginate(10)
                                ->appends($request->query());

        // Add revenue trends data
        $trends = $this->getRevenueTrends($dateRange, $graphStartDate, $graphEndDate);

        return Inertia::render('Admin/Revenue', [
            'revenue' => $revenue,
            'transactions' => $transactions,
            'filters' => $request->only(['dateRange', 'sort', 'graphStartDate', 'graphEndDate']),
            'trends' => $trends // Add this line
        ]);
    }

    private function getRevenueTrends($dateRange, $graphStartDate = null, $graphEndDate = null)
    {
        $query = RentalRequest::whereIn('status', ['completed', 'completed_with_payments']);
        
        if ($graphStartDate && $graphEndDate) {
            // Use custom date range if provided
            $startDate = \Carbon\Carbon::parse($graphStartDate);
            $endDate = \Carbon\Carbon::parse($graphEndDate);
            $days = $startDate->diffInDays($endDate);
            
            $query->whereBetween('created_at', [
                $startDate->startOfDay(),
                $endDate->endOfDay()
            ]);
            
            $grouping = match(true) {
                $days <= 31 => 'day',
                $days <= 90 => 'week',
                default => 'month'
            };
        } else {
            // ...existing date range logic...
        }

        // Use custom date range if provided
        if ($graphStartDate && $graphEndDate) {
            $query->whereBetween('created_at', [
                $graphStartDate . ' 00:00:00',
                $graphEndDate . ' 23:59:59'
            ]);
            
            // Calculate difference in days for grouping
            $days = \Carbon\Carbon::parse($graphStartDate)->diffInDays(\Carbon\Carbon::parse($graphEndDate));
            
            if ($days <= 31) {
                $grouping = 'day';
            } elseif ($days <= 90) {
                $grouping = 'week';
            } else {
                $grouping = 'month';
            }
        } else {
            // Use existing date range logic
            switch ($dateRange) {
                case 'last7':
                    $days = 7;
                    $grouping = 'day';
                    break;
                case 'thisMonth':
                case 'lastMonth':
                case 'last30':
                    $days = 30;
                    $grouping = 'day';
                    break;
                case 'last90':
                    $days = 90;
                    $grouping = 'week';
                    break;
                case 'thisYear':
                case 'lastYear':
                    $days = 365;
                    $grouping = 'month';
                    break;
                default:
                    $days = 30;
                    $grouping = 'day';
            }
        }

        $format = match($grouping) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m'
        };

        $results = $query->select(
            DB::raw("DATE_FORMAT(created_at, '$format') as date"),
            DB::raw('SUM(service_fee * 2) as total')
        )
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        return [
            'labels' => $results->pluck('date')->map(fn($date) => 
                match($grouping) {
                    'day' => date('M d', strtotime($date)),
                    'week' => 'Week ' . substr($date, -2),
                    'month' => date('M Y', strtotime($date . '-01'))
                }
            ),
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $results->pluck('total'),
                    'borderColor' => '#10B981',
                    'backgroundColor' => '#10B98120',
                    'fill' => true,
                    'tension' => 0.4
                ]
            ]
        ];
    }

    private function getUserActionFlags($user)
    {
        $flags = [];
        
        if (!$user->email_verified_at) {
            $flags[] = 'Needs Verification';
        }
        
        if ($user->status === 'suspended') {
            $flags[] = 'Account Suspended';
        }
        
        if ($user->listings_count === 0) {
            $flags[] = 'No Listings';
        }
        
        return empty($flags) ? 'None' : implode('; ', $flags);
    }

    public function exportUsers()
    {
        try {
            // Get all users with related data
            $users = User::select([
                'id', 'name', 'email', 'status',
                'email_verified_at', 'created_at'
            ])
            ->withCount('listings')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($user) {
                return [
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                    'verification' => $user->email_verified_at ? 'Verified' : 'Unverified',
                    'join_date' => $user->created_at ? date('m/d/Y', strtotime($user->created_at)) : 'N/A',
                    'listings_count' => $user->listings_count,
                    'actions_required' => $this->getUserActionFlags($user)
                ];
            });

            return response()->json($users);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['error' => 'Export failed'], 500);
        }
    }

    public function exportListings()
    {
        try {
            // Get all listings with related data
            $listings = Listing::with(['category', 'user', 'location'])
                ->select([
                    'id', 'title', 'price', 'desc', 'status',
                    'category_id', 'user_id', 'location_id',
                    'is_available', 'created_at'
                ])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($listing) {
                    return [
                        'title' => $listing->title,
                        'category' => $listing->category?->name,
                        'price' => $listing->price,
                        'status' => $listing->status,
                        'lender' => $listing->user?->name,
                        'location' => $listing->location?->city,
                        'created_at' => $listing->created_at ? date('m/d/Y', strtotime($listing->created_at)) : 'N/A',
                        'is_available' => $listing->is_available,
                        'description' => $listing->desc
                    ];
                });

            return response()->json($listings);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['error' => 'Export failed'], 500);
        }
    }

    public function exportTransactions()
    {
        try {
            // Get all transactions with related data
            $transactions = RentalRequest::with(['listing:id,title,user_id', 'listing.user:id,name', 'renter:id,name'])
                ->select([
                    'id',
                    'listing_id',
                    'renter_id',
                    'total_price',
                    'service_fee',
                    'status',
                    'start_date',
                    'end_date',
                    'created_at'
                ])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($transaction) {
                    return [
                        'id' => $transaction->id,
                        'created_at' => $transaction->created_at ? date('m/d/Y H:i', strtotime($transaction->created_at)) : 'N/A',
                        'listing' => [
                            'title' => $transaction->listing->title ?? 'Unknown Listing',
                            'user' => [
                                'name' => $transaction->listing->user->name ?? 'Unknown Lender'
                            ]
                        ],
                        'renter' => [
                            'name' => $transaction->renter->name ?? 'Unknown Renter'
                        ],
                        'start_date' => $transaction->start_date ? date('m/d/Y', strtotime($transaction->start_date)) : 'N/A',
                        'end_date' => $transaction->end_date ? date('m/d/Y', strtotime($transaction->end_date)) : 'N/A',
                        'total_price' => $transaction->total_price,
                        'service_fee' => $transaction->service_fee,
                        'status' => $transaction->status
                    ];
                });

            return response()->json($transactions);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['error' => 'Export failed'], 500);
        }
    }

    public function exportRevenue()
    {
        try {
            // Get all completed transactions with related data
            $transactions = RentalRequest::with(['listing:id,title', 'renter:id,name'])
                ->whereIn('status', ['completed', 'completed_with_payments'])
                ->select([
                    'id', 
                    'listing_id', 
                    'renter_id',
                    'total_price',
                    'service_fee',
                    'status',
                    'created_at'
                ])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($transaction) {
                    return [
                        'date' => $transaction->created_at ? date('m/d/Y H:i', strtotime($transaction->created_at)) : 'N/A',
                        'listing' => $transaction->listing->title ?? 'Unknown Listing',
                        'renter' => $transaction->renter->name ?? 'Unknown Renter',
                        'rental_amount' => $transaction->total_price,
                        'platform_fee' => $transaction->service_fee,
                        'total_earnings' => $transaction->service_fee * 2,
                        'status' => $transaction->status
                    ];
                });

            return response()->json($transactions);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['error' => 'Export failed'], 500);
        }
    }

    public function exportPayments()
    {
        try {
            // Get all payments with related data
            $payments = PaymentRequest::with([
                'rentalRequest:id,listing_id,renter_id,total_price', 
                'rentalRequest.listing:id,title', 
                'rentalRequest.renter:id,name'
            ])
            ->select([
                'id',
                'rental_request_id',
                'reference_number',
                'status',
                'payment_proof_path',
                'verified_at',
                'created_at'
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($payment) {
                return [
                    'date' => $payment->created_at ? date('m/d/Y H:i', strtotime($payment->created_at)) : 'N/A',
                    'reference' => $payment->reference_number,
                    'listing' => $payment->rentalRequest->listing->title ?? 'Unknown Listing',
                    'renter' => $payment->rentalRequest->renter->name ?? 'Unknown Renter',
                    'amount' => $payment->rentalRequest->total_price ?? 0,
                    'status' => $payment->status,
                    'verified_at' => $payment->verified_at ? date('m/d/Y H:i', strtotime($payment->verified_at)) : 'N/A'
                ];
            });

            return response()->json($payments);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['error' => 'Export failed'], 500);
        }
    }
}