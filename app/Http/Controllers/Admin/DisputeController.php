<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RentalDispute;
use App\Models\HandoverDispute;
use App\Notifications\DisputeStatusUpdated;
use App\Notifications\DisputeResolved;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DisputeController extends Controller
{
    public function index()
    {
        $stats = [
            'total' => RentalDispute::count() + HandoverDispute::count(),
            'pending' => RentalDispute::where('status', 'pending')->count() + 
                        HandoverDispute::where('status', 'pending')->count(),
            'reviewed' => RentalDispute::where('status', 'reviewed')->count() +
                         HandoverDispute::where('status', 'reviewed')->count(),
            'resolved' => RentalDispute::where('status', 'resolved')->count() +
                         HandoverDispute::where('status', 'resolved')->count(),
        ];

        // Get both types of disputes and merge them
        $returnDisputes = RentalDispute::with(['rental.listing', 'rental.renter'])
            ->latest()
            ->get()
            ->map(fn($dispute) => array_merge($dispute->toArray(), ['type' => 'return']));

        $handoverDisputes = HandoverDispute::with(['rental.listing', 'rental.renter'])
            ->latest()
            ->get()
            ->map(fn($dispute) => array_merge($dispute->toArray(), ['type' => 'handover']));

        // Combine and paginate
        $allDisputes = $returnDisputes->concat($handoverDisputes)
            ->sortByDesc('created_at')
            ->values();

        return Inertia::render('Admin/Disputes', [
            'disputes' => [
                'data' => $allDisputes->slice(0, 10),
                'total' => $allDisputes->count(),
                'per_page' => 10,
                'current_page' => 1,
            ],
            'stats' => $stats
        ]);
    }

    public function show($id)
    {
        // Try to find dispute in either table
        $dispute = RentalDispute::find($id) ?? HandoverDispute::find($id);
        
        if (!$dispute) {
            abort(404);
        }

        $disputeType = $dispute instanceof RentalDispute ? 'return' : 'handover';
        
        // Get appropriate resolution options based on type
        $resolutionOptions = $disputeType === 'return' 
            ? $this->getReturnResolutionOptions()
            : $this->getHandoverResolutionOptions();

        // Update this line to include listing.user relationship
        $dispute->load(['rental.listing.user', 'rental.renter', 'resolvedBy']);
        
        return Inertia::render('Admin/DisputeDetails', [
            'dispute' => array_merge($dispute->toArray(), ['dispute_type' => $disputeType]),
            'resolutionOptions' => $resolutionOptions
        ]);
    }

    protected function getHandoverResolutionOptions()
    {
        return [
            ['value' => 'approved', 'label' => 'Approve Dispute'],
            ['value' => 'reschedule', 'label' => 'Reschedule Handover'],
            ['value' => 'rejected', 'label' => 'Reject Dispute'],
        ];
    }

    protected function getReturnResolutionOptions()
    {
        return [
            ['value' => 'deposit_deducted', 'label' => 'Deposit Deducted'],
            ['value' => 'rejected', 'label' => 'Reject Dispute'],
        ];
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

    public function resolve(Request $request, $id)
    {
        // Find dispute in either table
        $dispute = RentalDispute::find($id) ?? HandoverDispute::find($id);
        
        if (!$dispute) {
            abort(404);
        }

        // Validate based on dispute type
        if ($dispute instanceof RentalDispute) {
            return $this->resolveReturnDispute($request, $dispute);
        }

        return $this->resolveHandoverDispute($request, $dispute);
    }

    protected function resolveHandoverDispute(Request $request, HandoverDispute $dispute)
    {
        $validated = $request->validate([
            'resolution_type' => ['required', 'in:approved,reschedule,rejected'],
            'verdict' => ['required', 'string'],
            'verdict_notes' => ['required', 'string'],
        ]);

        DB::transaction(function () use ($dispute, $validated) {
            // Process based on resolution type
            switch ($validated['resolution_type']) {
                case 'approved':
                    if ($dispute->type === HandoverDispute::TYPE_LENDER_NO_SHOW) {
                        // Cancel rental with full refund
                        $dispute->rental->update([
                            'status' => 'cancelled',
                            'cancellation_reason' => 'lender_no_show'
                        ]);
                        // Create refund record
                        $dispute->rental->refunds()->create([
                            'amount' => $dispute->rental->total_price,
                            'reason' => 'Lender no-show dispute approved',
                            'status' => 'pending'
                        ]);
                    } else {
                        // Cancel with one day payment retention
                        $dailyRate = $dispute->rental->base_price / $dispute->rental->rental_duration;
                        $dispute->rental->update([
                            'status' => 'cancelled',
                            'cancellation_reason' => 'renter_no_show'
                        ]);
                        // Create partial refund record
                        $dispute->rental->refunds()->create([
                            'amount' => $dispute->rental->total_price - $dailyRate,
                            'reason' => 'Renter no-show dispute approved - 1 day payment retained',
                            'status' => 'pending'
                        ]);
                    }
                    break;

                case 'reschedule':
                    if ($dispute->type === HandoverDispute::TYPE_LENDER_NO_SHOW) {
                        // Extend rental period by 1 day
                        $newEndDate = Carbon::parse($dispute->rental->end_date)->addDay();
                        $dispute->rental->update([
                            'end_date' => $newEndDate,
                            'status' => 'to_handover'
                        ]);
                    } else {
                        // Keep original duration
                        $dispute->rental->update(['status' => 'to_handover']);
                    }
                    // Reset schedules for both cases
                    $dispute->rental->pickup_schedules()->update([
                        'is_selected' => false,
                        'is_confirmed' => false
                    ]);
                    break;

                case 'rejected':
                    // Just update status back to to_handover
                    $dispute->rental->update(['status' => 'to_handover']);
                    break;
            }

            // Update dispute record
            $dispute->update([
                'status' => 'resolved',
                'resolution_type' => $validated['resolution_type'],
                'verdict' => $validated['verdict'],
                'verdict_notes' => $validated['verdict_notes'],
                'resolved_at' => now(),
                'resolved_by' => Auth::id()
            ]);

            // Record timeline event
            $dispute->rental->recordTimelineEvent('handover_dispute_resolved', Auth::id(), [
                'resolution_type' => $validated['resolution_type'],
                'verdict' => $validated['verdict'],
                'verdict_notes' => $validated['verdict_notes'],
                'dispute_type' => $dispute->type
            ]);
        });

        return back()->with('success', 'Handover dispute resolved successfully.');
    }

    protected function resolveReturnDispute(Request $request, RentalDispute $dispute)
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

        DB::transaction(function () use ($dispute, $validated) {
            // Base dispute resolution data
            $resolutionData = [
                'status' => 'resolved',
                'verdict' => $validated['verdict'],
                'verdict_notes' => $validated['verdict_notes'],
                'resolution_type' => $validated['resolution_type'],
                'resolved_at' => now(),
                'resolved_by' => Auth::id()
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
                    'admin_id' => Auth::id()
                ]);

                // Force refresh rental model to recalculate remaining deposit
                $dispute->rental->refresh();

                // Create lender earnings adjustment
                $adjustment = $dispute->rental->lenderEarningsAdjustments()->create([
                    'type' => 'deposit_deduction',
                    'amount' => $validated['deposit_deduction'],
                    'description' => 'Deposit deduction from dispute resolution',
                    'reference_id' => (string) $dispute->id
                ]);

                // Update completion payment if it exists
                $completionPayment = $dispute->rental->completion_payments()
                    ->where('type', 'lender_payment')
                    ->first();

                if ($completionPayment) {
                    $newAmount = $completionPayment->amount + $validated['deposit_deduction'];
                    $completionPayment->update(['amount' => $newAmount]);
                }

                // Update rental record to reflect new total
                $dispute->rental->update([
                    'total_lender_earnings' => DB::raw("total_lender_earnings + {$validated['deposit_deduction']}")
                ]);
            }

            // Always update rental status for both rejection and deduction
            $dispute->rental->update(['status' => 'pending_final_confirmation']);

            // Record timeline event with appropriate data
            $timelineData = [
                'verdict' => $validated['verdict'],
                'verdict_notes' => $validated['verdict_notes'],
                'resolution_type' => $validated['resolution_type'],
                'resolved_by' => Auth::user()->name,
                'resolved_at' => now()->format('Y-m-d H:i:s'),
                'is_rejected' => $validated['resolution_type'] === 'rejected'
            ];

            // Add deduction data only if it's a deduction case
            if ($validated['resolution_type'] === 'deposit_deducted') {
                $timelineData['deposit_deduction'] = $validated['deposit_deduction'];
                $timelineData['deposit_deduction_reason'] = $validated['deposit_deduction_reason'];
            }

            $dispute->rental->recordTimelineEvent('dispute_resolved', Auth::id(), $timelineData);

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
