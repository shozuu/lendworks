<?php

namespace App\Http\Controllers;

use App\Models\RentalRequest;
use App\Models\HandoverDispute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HandoverDisputeController extends Controller
{
    public function store(Request $request, RentalRequest $rental)
    {
        $request->validate([
            'type' => ['required', 'in:lender_no_show,renter_no_show'],
            'description' => ['required', 'string', 'min:10'],
            'proof_image' => ['required', 'image', 'max:5120'],
            'schedule_id' => ['required', 'exists:pickup_schedules,id']
        ]);

        // Validate the reporter is authorized
        if ($request->type === 'lender_no_show' && $rental->renter_id !== Auth::id()) {
            abort(403);
        }
        if ($request->type === 'renter_no_show' && $rental->listing->user_id !== Auth::id()) {
            abort(403);
        }

        // Store proof image
        $path = $request->file('proof_image')->store('handover-disputes', 'public');

        // Create dispute
        $rental->handover_dispute()->create([
            'type' => $request->type,
            'description' => $request->description,
            'proof_path' => $path,
            'schedule_id' => $request->schedule_id,
            'status' => 'pending'
        ]);

        // Record timeline event
        $rental->recordTimelineEvent($request->type . '_reported', Auth::id(), [
            'proof_path' => $path,
            'description' => $request->description
        ]);

        return back()->with('success', 'No-show report submitted successfully.');
    }

    public function resolve(Request $request, HandoverDispute $dispute)
    {
        $request->validate([
            'resolution_type' => ['required', 'in:approved,reschedule,rejected'],
            'resolution_notes' => ['required', 'string', 'min:10']
        ]);

        DB::transaction(function () use ($dispute, $request) {
            $resolutionType = $request->resolution_type;
            $isLenderNoShow = $dispute->type === HandoverDispute::TYPE_LENDER_NO_SHOW;

            if ($resolutionType === HandoverDispute::RESOLUTION_APPROVED) {
                if ($isLenderNoShow) {
                    // Full refund for lender no-show
                    $this->handleLenderNoShowApproval($dispute);
                } else {
                    // Partial refund for renter no-show (1-day payment)
                    $this->handleRenterNoShowApproval($dispute);
                }
            } elseif ($resolutionType === HandoverDispute::RESOLUTION_RESCHEDULE) {
                // Handle rescheduling logic
                $dispute->handleResolution($resolutionType);
            }

            // Update dispute record
            $dispute->update([
                'status' => 'resolved',
                'resolution_type' => $resolutionType,
                'resolution_notes' => $request->resolution_notes,
                'resolved_by' => Auth::id(),
                'resolved_at' => now()
            ]);

            // Record timeline event
            $dispute->rental->recordTimelineEvent('handover_dispute_resolved', Auth::id(), [
                'resolution_type' => $resolutionType,
                'resolution_notes' => $request->resolution_notes,
                'type' => $dispute->type,
                'is_approved' => $resolutionType === HandoverDispute::RESOLUTION_APPROVED
            ]);
        });

        return back()->with('success', 'Handover dispute resolved successfully.');
    }

    protected function handleLenderNoShowApproval(HandoverDispute $dispute)
    {
        $rental = $dispute->rental;
        
        // Cancel rental with full refund
        $rental->update([
            'status' => 'cancelled',
            'cancellation_reason' => 'lender_no_show'
        ]);

        // Create refund record
        $rental->refunds()->create([
            'amount' => $rental->total_price,
            'reason' => 'Lender no-show dispute approved',
            'status' => 'pending'
        ]);
    }

    protected function handleRenterNoShowApproval(HandoverDispute $dispute)
    {
        $rental = $dispute->rental;
        
        // Calculate one-day payment
        $dailyRate = $rental->base_price / $rental->rental_duration;
        
        // Cancel rental with partial refund
        $rental->update([
            'status' => 'cancelled',
            'cancellation_reason' => 'renter_no_show'
        ]);

        // Create partial refund record
        $rental->refunds()->create([
            'amount' => $rental->total_price - $dailyRate,
            'reason' => 'Renter no-show dispute approved - 1 day payment retained',
            'status' => 'pending'
        ]);
    }
}
