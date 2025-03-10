<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RentalDispute;
use App\Notifications\DisputeStatusUpdated;
use App\Notifications\DisputeResolved;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DisputeController extends Controller
{
    public function index()
    {
        $stats = [
            'total' => RentalDispute::count(),
            'pending' => RentalDispute::where('status', 'pending')->count(),
            'reviewed' => RentalDispute::where('status', 'reviewed')->count(),
            'resolved' => RentalDispute::where('status', 'resolved')->count(),
        ];

        $disputes = RentalDispute::with(['rental.listing', 'rental.renter'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Admin/Disputes', [
            'disputes' => $disputes,
            'stats' => $stats
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
            'raisedBy',
            'resolvedBy'  // Ensure resolvedBy relationship is loaded
        ]);

        // Calculate remaining deposit accurately
        $rental = $dispute->rental;
        $totalDeductions = $dispute->deposit_deduction ?? 0;
        $remainingDeposit = $rental->deposit_fee - $totalDeductions;

        // Calculate payment details
        $basePrice = $rental->base_price ?? 0;
        $discount = $rental->discount ?? 0;
        $serviceFee = $rental->service_fee ?? 0;
        $overdueFee = $rental->overdue_fee ?? 0;
        $depositDeductions = $rental->depositDeductions()->sum('amount') ?? 0;
        $totalEarnings = $basePrice - $discount - $serviceFee + $overdueFee + $depositDeductions;

        \Log::info('Dispute Details - Calculations', [
            'rental_id' => $rental->id,
            'deposit_fee' => $rental->deposit_fee,
            'total_deductions' => $totalDeductions,
            'remaining_deposit' => $remainingDeposit,
            'base_price' => $basePrice,
            'total_earnings' => $totalEarnings
        ]);

        return Inertia::render('Admin/DisputeDetails', [
            'dispute' => [
                'id' => $dispute->id,
                'status' => $dispute->status,
                'reason' => $dispute->reason,
                'description' => $dispute->description,
                'proof_path' => $dispute->proof_path,
                'created_at' => $dispute->created_at,
                'resolution_type' => $dispute->resolution_type,  // Add this line
                'verdict' => $dispute->verdict,           // Add this
                'verdict_notes' => $dispute->verdict_notes, // Add this
                'resolved_at' => $dispute->resolved_at,   // Add this
                'resolved_by' => [                        // Add this
                    'name' => $dispute->resolvedBy?->name
                ],
                'raised_by_user' => [
                    'name' => $dispute->raisedBy->name,
                    'id' => $dispute->raisedBy->id       // Add this
                ],
                'rental' => [
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
                    'deposit_fee' => $rental->deposit_fee,
                    'remaining_deposit' => max(0, $remainingDeposit),
                    'has_deductions' => $totalDeductions > 0,
                    'total_deductions' => $totalDeductions,
                    // Add payment details
                    'base_price' => $basePrice,
                    'discount' => $discount,
                    'service_fee' => $serviceFee,
                    'overdue_fee' => $overdueFee,
                    'deposit_deductions' => $depositDeductions,
                    'current_earnings' => $totalEarnings
                ]
            ]
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

            // Update dispute record with correct handling for rejection
            if ($validated['resolution_type'] === 'rejected') {
                $resolutionData['deposit_deduction'] = 0;  // Ensure no deduction
                $resolutionData['deposit_deduction_reason'] = null;  // No reason needed
                
                // Update rental status
                $dispute->rental->update([
                    'status' => 'disputed'  // Keep as disputed to allow new dispute
                ]);
            } elseif ($validated['resolution_type'] === 'deposit_deducted') {
                $resolutionData['deposit_deduction'] = $validated['deposit_deduction'];
                $resolutionData['deposit_deduction_reason'] = $validated['deposit_deduction_reason'];
            }

            // Update dispute record
            $dispute->update($resolutionData);

            // Process deductions only for deposit_deducted type
            if ($validated['resolution_type'] === 'deposit_deducted') {
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

            // Always update rental status for both rejection and deduction
            $dispute->rental->update(['status' => 'pending_final_confirmation']);

            // Record timeline event with appropriate data
            $timelineData = [
                'verdict' => $validated['verdict'],
                'verdict_notes' => $validated['verdict_notes'],
                'resolution_type' => $validated['resolution_type'],
                'resolved_by' => auth()->user()->name,
                'resolved_at' => now()->format('Y-m-d H:i:s'),
                'is_rejected' => $validated['resolution_type'] === 'rejected'
            ];

            // Add deduction data only if it's a deduction case
            if ($validated['resolution_type'] === 'deposit_deducted') {
                $timelineData['deposit_deduction'] = $validated['deposit_deduction'];
                $timelineData['deposit_deduction_reason'] = $validated['deposit_deduction_reason'];
            }

            $dispute->rental->recordTimelineEvent('dispute_resolved', auth()->id(), $timelineData);

            // Send notifications
            $dispute->rental->renter->notify(new DisputeResolved($dispute, false));
            $dispute->rental->listing->user->notify(new DisputeResolved($dispute, true));
        });

        $message = $validated['resolution_type'] === 'rejected' 
            ? 'Dispute rejected successfully.'
            : 'Dispute resolved with deduction successfully.';

        return back()->with('success', $message);
    }
}
