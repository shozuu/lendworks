<?php

namespace App\Http\Controllers;

use App\Models\RentalRequest;
use App\Models\HandoverProof;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class HandoverController extends Controller
{
    public function submitHandover(Request $request, RentalRequest $rental)
    {
        // Validate that the user is the lender
        if ($rental->listing->user_id !== Auth::id()) {
            abort(403, 'You are not authorized to perform this action.');
        }

        // Check if there's a selected pickup schedule
        if (!$rental->pickup_schedules()->where('is_selected', true)->exists()) {
            return back()->with('error', 'Cannot handover until renter selects a pickup schedule.');
        }

        $validated = $request->validate([
            'images.*' => 'required|image|max:2048', // 2MB max
        ]);

        try {
            DB::transaction(function () use ($rental, $request) {
                // Record the handover proofs
                foreach ($request->file('images') as $image) {
                    $path = $image->store('handover-proofs', 'public');
                    $rental->handoverProofs()->create([
                        'image_path' => $path,
                        'type' => 'handover',
                        'submitted_by' => Auth::id(),
                    ]);
                }

                // Update rental status
                $rental->update([
                    'status' => 'pending_proof',
                ]);

                // Record timeline event
                $rental->recordTimelineEvent('handover_submitted', Auth::id());
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

        return back()->with('success', 'Receive proof submitted successfully.');
    }
}
