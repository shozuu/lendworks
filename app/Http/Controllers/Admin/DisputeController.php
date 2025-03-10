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
            'raisedBy',
            'resolvedBy'
        ]);

        // Calculate lender earnings
        $rental = $dispute->rental;
        $basePrice = $rental->base_price ?? 0;
        $discount = $rental->discount ?? 0;
        $serviceFee = $rental->service_fee ?? 0;
        $overdueFee = $rental->overdue_fee ?? 0;

        $currentEarnings = $basePrice - $discount - $serviceFee + $overdueFee;

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
                    'deposit_fee' => $dispute->rental->deposit_fee,
                    'remaining_deposit' => $dispute->rental->remaining_deposit,
                    'has_deductions' => $dispute->rental->depositDeductions()->exists(),
                    // Add payment details
                    'base_price' => $basePrice,
                    'discount' => $discount,
                    'service_fee' => $serviceFee,
                    'overdue_fee' => $overdueFee,
                    'current_earnings' => $currentEarnings
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

            // If deposit is being deducted, create a deduction record
            if ($validated['resolution_type'] === 'deposit_deducted') {
                $dispute->rental->depositDeductions()->create([
                    'amount' => $validated['deposit_deduction'],
                    'reason' => $validated['deposit_deduction_reason'],
                    'dispute_id' => $dispute->id,
                    'admin_id' => auth()->id()
                ]);

                // Add to lender's earnings
                $dispute->rental->lenderEarningsAdjustments()->create([
                    'amount' => $validated['deposit_deduction'],
                    'type' => 'deposit_deduction',
                    'description' => 'Deposit deduction from dispute resolution',
                    'reference_id' => $dispute->id
                ]);
            }

            // Record timeline event
            $dispute->rental->recordTimelineEvent('dispute_resolved', auth()->id(), [
                'verdict' => $validated['verdict'],
                'verdict_notes' => $validated['verdict_notes'],
                'resolution_type' => $validated['resolution_type'],
                'deposit_deduction' => $validated['deposit_deduction'] ?? null,
                'deposit_deduction_reason' => $validated['deposit_deduction_reason'] ?? null
            ]);

            // Notify users
            $dispute->rental->renter->notify(new DisputeResolved($dispute));
            $dispute->rental->listing->user->notify(new DisputeResolved($dispute));
        });

        return back()->with('success', 'Dispute resolved successfully.');
    }
}
