<?php

namespace App\Http\Controllers;

use App\Models\RentalRequest;
use App\Models\HandoverProof;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Notifications\HandoverActionNotification;

class HandoverController extends Controller
{
    public function submitHandover(Request $request, RentalRequest $rental)
    {
        // Validate that the user is the lender
        if ($rental->listing->user_id !== Auth::id()) {
            abort(403, 'You are not authorized to perform this action.');
        }

        // Check for confirmed schedule
        if (!$rental->hasConfirmedPickupSchedule()) {
            return back()->with('error', 'Please confirm the pickup schedule before proceeding with handover.');
        }

        // Validate that the rental is in the correct status
        if (!in_array($rental->status, ['to_handover', 'pending_proof'])) {
            abort(400, 'Invalid rental status for handover.');
        }

        $request->validate([
            'proof_image' => ['required', 'image', 'max:5120'], // 5MB max
        ]);

        try {
            DB::transaction(function () use ($request, $rental) {
                // Store the image
                $path = $request->file('proof_image')->store('handover-proofs', 'public');

                // Create handover proof
                HandoverProof::create([
                    'rental_request_id' => $rental->id,
                    'type' => 'handover',
                    'proof_path' => $path,
                    'submitted_by' => Auth::id(),
                ]);

                // Update rental status to pending_proof and keep it visible in To Receive tab
                $rental->update(['status' => 'pending_proof']);

                // Record timeline event
                $rental->recordTimelineEvent('handover', Auth::id(), ['proof_path' => $path]);

                // Add notification here
                $rental->lender->notify(new HandoverActionNotification($rental, 'handover_submitted'));
                $rental->renter->notify(new HandoverActionNotification($rental, 'handover_submitted'));
            });

            return back()->with('success', 'Handover proof submitted successfully.');
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Failed to submit handover proof.');
        }
    }

    public function submitReceive(Request $request, RentalRequest $rental)
    {
        // Validate that the user is the renter
        if ($rental->renter_id !== Auth::id()) {
            abort(403, 'You are not authorized to perform this action.');
        }

        // Validate that the rental is in the correct status
        if (!in_array($rental->status, ['to_handover', 'pending_proof'])) {
            abort(400, 'Invalid rental status for receive.');
        }

        $request->validate([
            'proof_image' => ['required', 'image', 'max:5120'], // 5MB max
        ]);

        // Store the image
        $path = $request->file('proof_image')->store('handover-proofs', 'public');

        // Create receive proof
        HandoverProof::create([
            'rental_request_id' => $rental->id,
            'type' => 'receive',
            'proof_path' => $path,
            'submitted_by' => Auth::id(),
        ]);

        // Update rental status to active and set handover_at timestamp
        $rental->update([
            'status' => 'active',
            'handover_at' => now(),
        ]);

        // Record timeline event
        $rental->recordTimelineEvent('receive', Auth::id(), ['proof_path' => $path]);

        $rental->listing->user->notify(new HandoverActionNotification($rental, 'receive_confirmed'));

        return back()->with('success', 'Receive proof submitted successfully.');
    }
}
