<?php

namespace App\Http\Controllers;

use App\Models\RentalRequest;
use App\Models\ReturnSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ReturnController extends Controller
{
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
            
            // Add metadata for better context
            $rental->recordTimelineEvent('return_initiated', Auth::id(), [
                'rental_end_date' => $rental->end_date->format('Y-m-d'),
                'days_from_end' => now()->diffInDays($rental->end_date, false),
                'initiated_by' => 'renter'
            ]);
        });

        return back()->with('success', 'Return process initiated.');
    }

    public function storeSchedule(Request $request, RentalRequest $rental)
    {
        \Log::info('=== Return Schedule Store Started ===');
        \Log::info('Raw request:', $request->all());
        \Log::info('Request data:', [
            'return_datetime' => $request->return_datetime,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time
        ]);

        if ($rental->renter_id !== Auth::id()) {
            abort(403);
        }

        try {
            \Log::info('Validating request data...');
            // Validate the request data
            $validated = $request->validate([
                'return_datetime' => ['required', 'date', 'after_or_equal:'.$rental->end_date],
                'start_time' => ['required', 'string'],
                'end_time' => ['required', 'string']
            ]);

            \Log::info('Validated data:', $validated);

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

                // Record timeline event
                $rental->recordTimelineEvent('return_schedule_proposed', Auth::id(), [
                    'datetime' => $schedule->return_datetime,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'day_of_week' => date('l', strtotime($schedule->return_datetime))
                ]);
            });

            return back()->with('success', 'Return schedule proposed.');
        } catch (\Exception $e) {
            \Log::error('Failed to store schedule: ' . json_encode([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]));
            
            return back()->withErrors(['error' => 'Failed to create return schedule.']);
        }
    }

    public function selectSchedule(RentalRequest $rental, ReturnSchedule $schedule)
    {
        if ($rental->renter_id !== Auth::id()) {
            abort(403);
        }

        $rental->return_schedules()->update(['is_selected' => false]);
        $schedule->update(['is_selected' => true]);

        $rental->recordTimelineEvent('return_schedule_selected', Auth::id(), [
            'datetime' => $schedule->return_datetime->format('Y-m-d H:i:s')
        ]);

        return back()->with('success', 'Return schedule selected.');
    }

    public function confirmSchedule(RentalRequest $rental, ReturnSchedule $schedule)
    {
        if ($rental->listing->user_id !== Auth::id()) {
            abort(403);
        }

        $schedule->update(['is_confirmed' => true]);
        $rental->update(['status' => 'return_scheduled']);

        $rental->recordTimelineEvent('return_schedule_confirmed', Auth::id(), [
            'datetime' => $schedule->return_datetime->format('Y-m-d H:i:s')
        ]);

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

        $path = $request->file('proof_image')->store('return-proofs', 'public');

        $rental->handoverProofs()->create([
            'type' => 'return',
            'proof_path' => $path,
            'user_id' => Auth::id()
        ]);

        $rental->update(['status' => 'pending_return_confirmation']);
        
        $rental->recordTimelineEvent('return_submitted', Auth::id(), [
            'proof_path' => $path
        ]);

        return back()->with('success', 'Return proof submitted.');
    }

    public function confirmReturn(Request $request, RentalRequest $rental)
    {
        if ($rental->listing->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'proof_image' => ['required', 'image', 'max:5120']
        ]);

        $path = $request->file('proof_image')->store('return-proofs', 'public');

        $rental->handoverProofs()->create([
            'type' => 'return_confirmation',
            'proof_path' => $path,
            'user_id' => Auth::id()
        ]);

        $rental->update([
            'status' => 'completed',
            'return_at' => now()
        ]);

        // Mark the listing as available again
        $rental->listing->update(['is_rented' => false]);

        $rental->recordTimelineEvent('return_confirmed', Auth::id(), [
            'proof_path' => $path
        ]);

        return back()->with('success', 'Return confirmed. Rental completed.');
    }
}
