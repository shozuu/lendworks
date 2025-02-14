<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RentalRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RentalTransactionsController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');
        $period = $request->get('period', 30);

        $query = RentalRequest::with([
                'listing' => fn($q) => $q->with([
                    'images',
                    'category',
                    'location',
                    'user'
                ]),
                'renter',
                'latestRejection.rejectionReason',
                'latestCancellation.cancellationReason'
            ])
            ->withinPeriod($period)
            ->latest();

        // Apply filters
        $query = match($filter) {
            'rejected' => $query->rejected(),
            'cancelled' => $query->cancelled(),
            'needs_review' => $query->where(function($q) {
                $q->rejected()->orWhere(function($q) {
                    $q->cancelled();
                });
            }),
            default => $query
        };

        $transactions = $query->paginate(20);

        $stats = [
            'total' => RentalRequest::withinPeriod($period)->count(),
            'rejected' => RentalRequest::rejected()->withinPeriod($period)->count(),
            'cancelled' => RentalRequest::cancelled()->withinPeriod($period)->count(),
        ];

        return Inertia::render('Admin/RentalTransactions', [
            'transactions' => $transactions,
            'stats' => $stats,
            'filters' => [
                'current' => $filter,
                'period' => $period
            ]
        ]);
    }

    public function show(RentalRequest $rental)
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
