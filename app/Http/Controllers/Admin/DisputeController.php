<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RentalDispute;
use App\Models\RentalRequest;  // Add this import
use App\Notifications\DisputeStatusUpdated;
use App\Notifications\DisputeResolved;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DisputeController extends Controller
{
    public function index(Request $request)
    {
        $query = RentalDispute::query()
            ->with(['rental.listing', 'rental.renter'])
            ->latest();

        // Apply search filter
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('rental.listing', function($q) use ($search) {
                      $q->where('title', 'like', "%{$search}%");
                  })
                  ->orWhereHas('rental.renter', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply status filter
        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        // Apply time period filter
        if ($period = $request->input('period')) {
            if ($period !== 'all') {
                $query->where('created_at', '>=', now()->subDays($period));
            }
        }

        $stats = [
            'total' => RentalDispute::count(),
            'pending' => RentalDispute::where('status', 'pending')->count(),
            'reviewed' => RentalDispute::where('status', 'reviewed')->count(),
            'resolved' => RentalDispute::where('status', 'resolved')->count(),
        ];

        $totalTransactions = RentalRequest::count();
        $totalDisputes = RentalDispute::count(); // Changed from Dispute to RentalDispute
        $disputeRate = $totalTransactions > 0 
            ? round(($totalDisputes / $totalTransactions) * 100, 1) 
            : 0;

        return Inertia::render('Admin/Disputes', [
            'disputes' => $query->paginate(10)->withQueryString(),
            'stats' => array_merge($stats, [
                'totalDisputes' => $totalDisputes,
                'totalTransactions' => $totalTransactions,
                'disputeRate' => $disputeRate
            ]),
            'filters' => $request->only(['search', 'status', 'period'])
        ]);
    }

    public function show(RentalDispute $dispute)
    {
        $dispute->load([
            'rental.handoverProofs',
            'rental.returnProofs',
            'rental.listing.user', // Add lender info
            'rental.renter',       // Add renter info
            'rental.depositDeductions',
            'rental.overdue_payment',
            'rental.disputes',  // Add this line to load all disputes
            'raisedBy',
            'resolvedBy',  // Ensure resolvedBy relationship is loaded
            'images'  // Add this line to load additional images
        ]);

        // Simplify the dispute data structure
        $disputeData = [
            'id' => $dispute->id,
            'status' => $dispute->status,
            'reason' => $dispute->reason,
            'description' => $dispute->description,
            'old_proof_path' => $dispute->old_proof_path,  // Changed from proof_path
            'additional_images' => $dispute->images->map(function($image) {
                return ['image_path' => $image->image_path];
            }),
            'created_at' => $dispute->created_at,
            'resolution_type' => $dispute->resolution_type,
            'verdict' => $dispute->verdict,
            'verdict_notes' => $dispute->verdict_notes,
            'resolved_at' => $dispute->resolved_at,
            'deposit_deduction' => $dispute->deposit_deduction,  // Add this line
            'deposit_deduction_reason' => $dispute->deposit_deduction_reason,  // Add this line
            'resolved_by' => [
                'name' => $dispute->resolvedBy?->name
            ],
            'raised_by_user' => [
                'name' => $dispute->raisedBy->name,
                'id' => $dispute->raisedBy->id
            ]
        ];

        // Simplify rental data
        $rentalData = [
            'id' => $dispute->rental->id,
            'handover_proofs' => $dispute->rental->handoverProofs,
            'return_proofs' => $dispute->rental->returnProofs,
            'lender' => [
                'name' => $dispute->rental->listing->user->name,
                'id' => $dispute->rental->listing->user->id
            ],
            'renter' => [
                'name' => $dispute->rental->renter->name,
                'id' => $dispute->rental->renter->id
            ],
            'deposit_fee' => $dispute->rental->deposit_fee,
            'remaining_deposit' => max(0, $dispute->rental->deposit_fee - ($dispute->rental->depositDeductions->sum('amount') ?? 0)),
            'has_deductions' => $dispute->rental->depositDeductions->isNotEmpty(),
            'base_price' => $dispute->rental->base_price,
            'discount' => $dispute->rental->discount,
            'service_fee' => $dispute->rental->service_fee,
            'overdue_fee' => $dispute->rental->overdue_fee,
        ];

        $disputeData['rental'] = $rentalData;

        return Inertia::render('Admin/DisputeDetails', [
            'dispute' => $disputeData
        ]);
    }

    public function updateStatus(Request $request, RentalDispute $dispute)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:reviewed,resolved'],
        ]);

        $dispute->update([
            'status' => $validated['status']
        ]);

        // Notify users
        $dispute->rental->renter->notify(new DisputeStatusUpdated($dispute));
        $dispute->rental->listing->user->notify(new DisputeStatusUpdated($dispute));

        return back()->with('success', 'Dispute status updated successfully.');
    }

    public function resolve(Request $request, RentalDispute $dispute)
    {
        $validated = $request->validate([
            'verdict' => ['required', 'string'],
            'verdict_notes' => ['required', 'string'],
            'resolution_type' => ['required', 'in:deposit_deducted,rejected'],
            'deposit_deduction' => [
                'required_if:resolution_type,deposit_deducted',
                'numeric',
                'min:0'
            ],
            'deposit_deduction_reason' => [
                'required_if:resolution_type,deposit_deducted',
                'exclude_if:resolution_type,rejected',
                'string'
            ],
        ]);

        \Log::info('Starting dispute resolution process', [
            'dispute_id' => $dispute->id,
            'resolution_type' => $validated['resolution_type'],
            'validation_data' => $validated
        ]);

        DB::transaction(function () use ($dispute, $validated) {
            // Base dispute resolution data
            $resolutionData = [
                'status' => 'resolved',
                'verdict' => $validated['verdict'],
                'verdict_notes' => $validated['verdict_notes'],
                'resolution_type' => $validated['resolution_type'],
                'resolved_at' => now(),
                'resolved_by' => auth()->id()
            ];

            if ($validated['resolution_type'] === 'rejected') {
                $resolutionData['deposit_deduction'] = 0;
                $resolutionData['deposit_deduction_reason'] = null;
                
                // Update rental status but don't delete disputes
                $dispute->rental->update([
                    'status' => 'pending_final_confirmation'
                ]);
            } elseif ($validated['resolution_type'] === 'deposit_deducted') {
                $resolutionData['deposit_deduction'] = $validated['deposit_deduction'];
                $resolutionData['deposit_deduction_reason'] = $validated['deposit_deduction_reason'];
                
                // Process deductions...
                // Create deposit deduction record with eager loading
                $deduction = $dispute->rental->depositDeductions()->create([
                    'amount' => $validated['deposit_deduction'],
                    'reason' => $validated['deposit_deduction_reason'],
                    'dispute_id' => $dispute->id,
                    'admin_id' => auth()->id()
                ]);

                // Force refresh rental model to recalculate remaining deposit
                $dispute->rental->refresh();

                \Log::info('Deposit Deduction Created', [
                    'rental_id' => $dispute->rental->id,
                    'deduction_amount' => $validated['deposit_deduction'],
                    'remaining_deposit' => $dispute->rental->remaining_deposit
                ]);

                // Create lender earnings adjustment
                $adjustment = $dispute->rental->lenderEarningsAdjustments()->create([
                    'type' => 'deposit_deduction',
                    'amount' => $validated['deposit_deduction'],
                    'description' => 'Deposit deduction from dispute resolution',
                    'reference_id' => (string) $dispute->id
                ]);

                \Log::info('Created lender earnings adjustment', [
                    'adjustment_id' => $adjustment->id,
                    'amount' => $validated['deposit_deduction']
                ]);

                // Update completion payment if it exists
                $completionPayment = $dispute->rental->completion_payments()
                    ->where('type', 'lender_payment')
                    ->first();

                if ($completionPayment) {
                    $newAmount = $completionPayment->amount + $validated['deposit_deduction'];
                    $completionPayment->update(['amount' => $newAmount]);

                    \Log::info('Updated completion payment', [
                        'payment_id' => $completionPayment->id,
                        'old_amount' => $completionPayment->amount,
                        'new_amount' => $newAmount
                    ]);
                }

                // Update rental record to reflect new total
                $dispute->rental->update([
                    'total_lender_earnings' => DB::raw("total_lender_earnings + {$validated['deposit_deduction']}")
                ]);

                \Log::info('Updated rental total lender earnings', [
                    'rental_id' => $dispute->rental->id,
                    'deduction_amount' => $validated['deposit_deduction']
                ]);
            }

            // Update dispute record without affecting others
            $dispute->update($resolutionData);

            // Record timeline event
            $timelineData = [
                'verdict' => $validated['verdict'],
                'verdict_notes' => $validated['verdict_notes'],
                'resolution_type' => $validated['resolution_type'],
                'resolved_by' => auth()->user()->name,
                'resolved_at' => now()->format('Y-m-d H:i:s'),
                'is_rejected' => $validated['resolution_type'] === 'rejected'
            ];

            if ($validated['resolution_type'] === 'deposit_deducted') {
                $timelineData['deposit_deduction'] = $validated['deposit_deduction'];
                $timelineData['deposit_deduction_reason'] = $validated['deposit_deduction_reason'];
            }

            $dispute->rental->recordTimelineEvent('dispute_resolved', auth()->id(), $timelineData);

            // Send notifications
            $dispute->rental->renter->notify(new DisputeResolved($dispute, false));
            $dispute->rental->listing->user->notify(new DisputeResolved($dispute, true));
        });

        return back()->with('success', $validated['resolution_type'] === 'rejected' 
            ? 'Dispute rejected successfully.' 
            : 'Dispute resolved with deduction successfully.');
    }
}