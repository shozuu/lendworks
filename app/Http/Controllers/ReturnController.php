<?php

namespace App\Http\Controllers;

use App\Models\RentalRequest;
use App\Models\ReturnSchedule;
use App\Models\ReturnProof;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\LenderPickupSchedule; // Add this import at the top

class ReturnController extends Controller
{
    // Simplify initiateReturn to just update status and create timeline event
    public function initiateReturn(RentalRequest $rental)
    {
        if ($rental->renter_id !== Auth::id()) {
            abort(403);
        }

        if ($rental->status !== 'active') {
            return back()->with('error', 'This rental is not active.');
        }

        DB::transaction(function () use ($rental) {
            $rental->update(['status' => 'pending_return']);
            
            // Enhanced metadata for initiation
            $rental->recordTimelineEvent('return_initiated', Auth::id(), [
                'rental_end_date' => $rental->end_date->format('Y-m-d'),
                'is_early_return' => now()->lt($rental->end_date),
                'initiated_by' => 'renter',
                'days_from_end' => now()->diffInDays($rental->end_date, false),
                'return_reason' => now()->lt($rental->end_date) ? 'early_return' : 'normal_return'
            ]);
        });

        return back()->with('success', 'Return process initiated.');
    }

    public function storeSchedule(Request $request, RentalRequest $rental)
    {
        if ($rental->renter_id !== Auth::id()) {
            abort(403);
        }

        // Log incoming request data
        \Log::info('Return Schedule Request:', $request->all());

        try {
            $validated = $request->validate([
                'return_datetime' => ['required', 'date', 'after_or_equal:'.$rental->end_date],
                'start_time' => ['required', 'string'],
                'end_time' => ['required', 'string']
            ]);

            DB::transaction(function () use ($rental, $validated) {
                // Deselect existing schedules
                $rental->return_schedules()->update(['is_selected' => false]);
                
                // Create new schedule
                $schedule = ReturnSchedule::create([
                    'rental_request_id' => $rental->id,
                    'return_datetime' => $validated['return_datetime'],
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'is_selected' => true
                ]);

                \Log::info('Created return schedule:', $schedule->toArray());

                $rental->recordTimelineEvent('return_schedule_selected', Auth::id(), [
                    'datetime' => $schedule->return_datetime,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'day_of_week' => date('l', strtotime($schedule->return_datetime))
                ]);
            });

            return back()->with('success', 'Return schedule selected.');
        } catch (\Exception $e) {
            \Log::error('Failed to store return schedule:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->withErrors(['error' => 'Failed to create return schedule.']);
        }
    }

    // Update confirmSchedule to not require schedule parameter
    public function confirmSchedule(RentalRequest $rental)
    {
        if ($rental->listing->user_id !== Auth::id()) {
            abort(403);
        }

        // Find the selected schedule
        $schedule = $rental->return_schedules()
            ->where('is_selected', true)
            ->firstOrFail();

        DB::transaction(function () use ($rental, $schedule) {
            $schedule->update(['is_confirmed' => true]);
            $rental->update(['status' => 'return_scheduled']);
            
            // Enhanced metadata for confirmation
            $rental->recordTimelineEvent('return_schedule_confirmed', Auth::id(), [
                'datetime' => $schedule->return_datetime,
                'day_of_week' => date('l', strtotime($schedule->return_datetime)),
                'date' => date('M d, Y', strtotime($schedule->return_datetime)),
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'confirmed_by' => 'lender',
                'confirmation_datetime' => now()->format('Y-m-d H:i:s'),
                'is_early_return' => Carbon::parse($schedule->return_datetime)->lt($rental->end_date)
            ]);
        });

        return back()->with('success', 'Return schedule confirmed.');
    }

    public function submitReturn(Request $request, RentalRequest $rental)
    {
        if ($rental->renter_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'proof_image' => ['required', 'image', 'max:5120']
        ]);

        DB::transaction(function () use ($rental, $request) {
            $path = $request->file('proof_image')->store('return-proofs', 'public');

            // Create return proof using the new model
            ReturnProof::create([
                'rental_request_id' => $rental->id,
                'type' => 'return',
                'proof_path' => $path,
                'submitted_by' => Auth::id()
            ]);

            $rental->update(['status' => 'pending_return_confirmation']);
            
            $rental->recordTimelineEvent('return_submitted', Auth::id(), [
                'proof_path' => $path,
                'submitted_by' => 'renter',
                'submission_datetime' => now()->format('Y-m-d H:i:s'),
                'notes' => $request->input('notes'),
                'location' => $request->input('location')
            ]);
        });

        return back()->with('success', 'Return proof submitted.');
    }

    public function confirmItemReceived(Request $request, RentalRequest $rental)
    {
        if ($rental->listing->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'proof_image' => ['required', 'image', 'max:5120']
        ]);

        DB::transaction(function () use ($rental, $request) {
            $path = $request->file('proof_image')->store('return-proofs', 'public');

            ReturnProof::create([
                'rental_request_id' => $rental->id,
                'type' => 'return_receipt',
                'proof_path' => $path,
                'submitted_by' => Auth::id()
            ]);

            $rental->update(['status' => 'pending_final_confirmation']);
            
            $rental->recordTimelineEvent('return_receipt_confirmed', Auth::id(), [
                'proof_path' => $path,
                'confirmed_by' => 'lender',
                'confirmation_datetime' => now()->format('Y-m-d H:i:s'),
                'notes' => $request->input('notes'),
                'location' => $request->input('location'),
                'item_condition' => $request->input('item_condition', 'good')
            ]);
        });

        return back()->with('success', 'Return receipt confirmed.');
    }

    public function finalizeReturn(Request $request, RentalRequest $rental)
    {
        if ($rental->listing->user_id !== Auth::id()) {
            abort(403);
        }

        // Check if status is valid for finalization
        if (!in_array($rental->status, ['pending_final_confirmation', 'disputed'])) {
            return back()->with('error', 'Invalid rental status for finalization.');
        }

        // For disputed rentals, ensure dispute is resolved
        if ($rental->status === 'disputed') {
            if (!$rental->dispute || $rental->dispute->status !== 'resolved') {
                return back()->with('error', 'Cannot finalize return until dispute is resolved.');
            }
        }

        DB::transaction(function () use ($rental) {
            $rental->update([
                'status' => 'completed_pending_payments',
                'return_at' => now()
            ]);

            $rental->listing->update(['is_rented' => false]);

            // Add timeline event with dispute resolution info if applicable
            $metadata = [
                'completed_by' => 'lender',
                'completion_datetime' => now()->format('Y-m-d H:i:s'),
                'rental_duration' => $rental->rental_duration,
                'actual_return_date' => now()->format('Y-m-d')
            ];

            // Add dispute resolution info if applicable
            if ($rental->dispute) {
                $metadata['dispute_resolution'] = [
                    'type' => $rental->dispute->resolution_type,
                    'deduction_amount' => $rental->dispute->deposit_deduction,
                    'verdict' => $rental->dispute->verdict
                ];
            }

            $rental->recordTimelineEvent('rental_completed', Auth::id(), $metadata);
        });

        return back()->with('success', 'Rental completed. Awaiting payment processing.');
    }

    public function raiseDispute(Request $request, RentalRequest $rental)
    {
        if ($rental->listing->user_id !== Auth::id()) {
            abort(403);
        }

        \Log::info('Raising new dispute:', [
            'rental_id' => $rental->id,
            'current_status' => $rental->status,
            'has_dispute' => (bool) $rental->dispute,
            'dispute_status' => $rental->dispute?->status,
            'dispute_resolution' => $rental->dispute?->resolution_type
        ]);

        try {
            DB::transaction(function () use ($rental, $request) {
                $proofPath = $request->file('proof_image')->store('dispute-proofs', 'public');

                // Clear any existing dispute first if it was rejected
                if ($rental->dispute && $rental->dispute->resolution_type === 'rejected') {
                    $rental->dispute()->delete();
                }

                // Create new dispute record
                $dispute = $rental->dispute()->create([
                    'reason' => $request->reason,
                    'description' => $request->issue_description,
                    'proof_path' => $proofPath,
                    'status' => 'pending',
                    'raised_by' => auth()->id()
                ]);

                // Update rental status to disputed
                $rental->update(['status' => 'disputed']);

                // Record timeline event
                $rental->recordTimelineEvent('dispute_raised', auth()->id(), [
                    'reason' => $request->reason,
                    'description' => $request->issue_description,
                    'is_new_dispute' => true,
                    'after_rejection' => (bool) $rental->dispute
                ]);

                \Log::info('New dispute created:', [
                    'dispute_id' => $dispute->id,
                    'status' => $dispute->status
                ]);
            });

            return back()->with('success', 'New dispute has been raised successfully.');
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Failed to raise dispute. Please try again.');
        }
    }

    public function selectSchedule(Request $request, RentalRequest $rental, LenderPickupSchedule $lender_schedule)
    {
        if ($rental->renter_id !== Auth::id()) {
            abort(403);
        }

        // Add validation for the schedule data
        $validated = $request->validate([
            'return_datetime' => 'required|date',
            'start_time' => 'required|string',
            'end_time' => 'required|string'
        ]);

        DB::transaction(function () use ($rental, $lender_schedule, $validated) {
            // Reset existing selections
            $rental->return_schedules()->update(['is_selected' => false]);

            // Create new schedule with all required fields
            $schedule = ReturnSchedule::create([
                'rental_request_id' => $rental->id,
                'lender_pickup_schedule_id' => $lender_schedule->id,
                'return_datetime' => $validated['return_datetime'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'is_selected' => true
            ]);

            // Log the created schedule for debugging
            \Log::info('Return schedule created:', $schedule->toArray());

            $rental->recordTimelineEvent('return_schedule_selected', Auth::id(), [
                'datetime' => $schedule->return_datetime,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'day_of_week' => Carbon::parse($schedule->return_datetime)->format('l')
            ]);
        });

        return back()->with('success', 'Return schedule selected successfully.');
    }
}
