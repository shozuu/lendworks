<?php

namespace App\Http\Controllers;

use App\Models\RentalRequest;
use App\Models\PickupSchedule;
use App\Models\LenderPickupSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PickupScheduleController extends Controller
{
    public function store(Request $request, RentalRequest $rental)
    {
        $validated = $request->validate([
            'lender_pickup_schedule_id' => 'required|exists:lender_pickup_schedules,id',
            'pickup_date' => 'required|date|after_or_equal:today',
        ]);

        // Ensure the schedule belongs to the lender
        $lenderSchedule = LenderPickupSchedule::findOrFail($validated['lender_pickup_schedule_id']);
        if ($lenderSchedule->user_id !== $rental->listing->user_id) {
            abort(403);
        }

        // Create the pickup schedule
        $schedule = PickupSchedule::create([
            'rental_request_id' => $rental->id,
            'lender_pickup_schedule_id' => $validated['lender_pickup_schedule_id'],
            'pickup_datetime' => $validated['pickup_date'],
        ]);

        return back()->with('success', 'Pickup schedule added.');
    }

    public function destroy(RentalRequest $rental, PickupSchedule $schedule)
    {
        // Authorize that only lender can delete schedules
        if ($rental->listing->user_id !== Auth::id()) {
            abort(403);
        }

        if ($schedule->is_selected) {
            return back()->with('error', 'Cannot delete a selected schedule');
        }

        $schedule->delete();
        
        // Reload the rental with pickup_schedules relationship
        $rental->load('pickup_schedules');
        
        return back()->with('success', 'Schedule deleted successfully');
    }

    public function select(RentalRequest $rental, LenderPickupSchedule $lender_schedule)
    {
        // Validate that the rental belongs to the authenticated user
        if ($rental->renter_id !== Auth::id()) {
            abort(403);
        }

        DB::transaction(function () use ($rental, $lender_schedule) {
            // Calculate the next occurrence of the schedule's day
            $nextDate = Carbon::now();
            while ($nextDate->format('l') !== $lender_schedule->day_of_week) {
                $nextDate->addDay();
            }

            // Combine date with schedule time
            $pickupDatetime = Carbon::parse(
                $nextDate->format('Y-m-d') . ' ' . $lender_schedule->start_time
            );

            // Create or update pickup schedule with correct reference
            $pickup_schedule = $rental->pickup_schedules()->updateOrCreate(
                ['rental_request_id' => $rental->id],
                [
                    'lender_pickup_schedule_id' => $lender_schedule->id, // Fix: Use correct field name
                    'pickup_datetime' => $pickupDatetime,
                    'is_selected' => true,
                    'start_time' => $lender_schedule->start_time,
                    'end_time' => $lender_schedule->end_time
                ]
            );

            // Add timeline event
            $rental->timeline_events()->create([
                'actor_id' => Auth::id(),
                'event_type' => 'pickup_schedule_selected',
                'status' => $rental->status,
                'metadata' => [
                    'schedule_day' => $lender_schedule->day_of_week,
                    'schedule_time' => $lender_schedule->formatted_time_slot,
                    'pickup_datetime' => $pickupDatetime->format('Y-m-d H:i:s')
                ]
            ]);
        });

        return back()->with('success', 'Pickup schedule selected successfully.');
    }
}
