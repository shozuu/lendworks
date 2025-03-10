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
            'resolvedBy'
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
                'raised_by_user' => [
                    'name' => $dispute->raisedBy->name
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
            'deposit_deduction' => ['required_if:resolution_type,deposit_deducted', 'numeric', 'min:0'],
            'deposit_deduction_reason' => ['required_if:resolution_type,deposit_deducted', 'string'],
        ]);

        \Log::info('Starting dispute resolution process', [
            'dispute_id' => $dispute->id,
            'resolution_type' => $validated['resolution_type'],
            'deduction_amount' => $validated['deposit_deduction'] ?? 0
        ]);

        DB::transaction(function () use ($dispute, $validated) {
            // Update dispute record
            $dispute->update([
                'status' => 'resolved',
                'verdict' => $validated['verdict'],
                'verdict_notes' => $validated['verdict_notes'],
                'resolution_type' => $validated['resolution_type'],
                'deposit_deduction' => $validated['deposit_deduction'] ?? null,
                'deposit_deduction_reason' => $validated['deposit_deduction_reason'] ?? null,
                'resolved_at' => now(),
                'resolved_by' => auth()->id()
            ]);

            if ($validated['resolution_type'] === 'deposit_deducted') {
                $deductionAmount = $validated['deposit_deduction'];

                // Create deposit deduction record with eager loading
                $deduction = $dispute->rental->depositDeductions()->create([
                    'amount' => $deductionAmount,
                    'reason' => $validated['deposit_deduction_reason'],
                    'dispute_id' => $dispute->id,
                    'admin_id' => auth()->id()
                ]);

                // Force refresh rental model to recalculate remaining deposit
                $dispute->rental->refresh();

                \Log::info('Deposit Deduction Created', [
                    'rental_id' => $dispute->rental->id,
                    'deduction_amount' => $deductionAmount,
                    'remaining_deposit' => $dispute->rental->remaining_deposit
                ]);

                // Create lender earnings adjustment
                $adjustment = $dispute->rental->lenderEarningsAdjustments()->create([
                    'type' => 'deposit_deduction',
                    'amount' => $deductionAmount,
                    'description' => 'Deposit deduction from dispute resolution',
                    'reference_id' => (string) $dispute->id
                ]);

                \Log::info('Created lender earnings adjustment', [
                    'adjustment_id' => $adjustment->id,
                    'amount' => $deductionAmount
                ]);

                // Update completion payment if it exists
                $completionPayment = $dispute->rental->completion_payments()
                    ->where('type', 'lender_payment')
                    ->first();

                if ($completionPayment) {
                    $newAmount = $completionPayment->amount + $deductionAmount;
                    $completionPayment->update(['amount' => $newAmount]);

                    \Log::info('Updated completion payment', [
                        'payment_id' => $completionPayment->id,
                        'old_amount' => $completionPayment->amount,
                        'new_amount' => $newAmount
                    ]);
                }

                // Update rental record to reflect new total
                $dispute->rental->update([
                    'total_lender_earnings' => DB::raw("total_lender_earnings + {$deductionAmount}")
                ]);

                \Log::info('Updated rental total lender earnings', [
                    'rental_id' => $dispute->rental->id,
                    'deduction_amount' => $deductionAmount
                ]);
            }

            // After updating dispute record, update rental status
            $dispute->rental->update([
                'status' => 'pending_final_confirmation'  // Change status back to allow completion
            ]);

            // Log the status change
            \Log::info('Updated rental status after dispute resolution', [
                'rental_id' => $dispute->rental->id,
                'old_status' => 'disputed',
                'new_status' => 'pending_final_confirmation',
                'dispute_id' => $dispute->id
            ]);

            // Record timeline event
            $dispute->rental->recordTimelineEvent('dispute_resolved', auth()->id(), [
                'verdict' => $validated['verdict'],
                'verdict_notes' => $validated['verdict_notes'],
                'resolution_type' => $validated['resolution_type'],
                'deposit_deduction' => $validated['deposit_deduction'] ?? null,
                'deposit_deduction_reason' => $validated['deposit_deduction_reason'] ?? null,
                'resolved_by' => auth()->user()->name,
                'resolved_at' => now()->format('Y-m-d H:i:s')
            ]);

            // Send notifications
            $dispute->rental->renter->notify(new DisputeResolved($dispute, false));
            $dispute->rental->listing->user->notify(new DisputeResolved($dispute, true));
        });

        return back()->with('success', 'Dispute resolved successfully.');
    }
}
