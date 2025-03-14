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

    public function select(Request $request, RentalRequest $rental, LenderPickupSchedule $lender_schedule)
    {
        // Validate that the rental belongs to the authenticated user
        if ($rental->renter_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'start_time' => 'required|string',
            'end_time' => 'required|string'
        ]);

        DB::transaction(function () use ($rental, $lender_schedule, $validated) {
            // Reset existing selections
            $rental->pickup_schedules()->update(['is_selected' => false]);

            // Calculate the pickup datetime using rental start date and slot start time
            $pickupDatetime = Carbon::parse($rental->start_date)->setTimeFromTimeString($validated['start_time']);

            // Create pickup schedule with exact time slot
            $pickup_schedule = $rental->pickup_schedules()->create([
                'lender_pickup_schedule_id' => $lender_schedule->id,
                'pickup_datetime' => $pickupDatetime,
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'is_selected' => true
            ]);

            // Add timeline event
            $rental->recordTimelineEvent('pickup_schedule_selected', Auth::id(), [
                'day_of_week' => $pickupDatetime->format('l'),
                'date' => $pickupDatetime->format('F d, Y'), 
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'pickup_datetime' => $pickupDatetime->format('Y-m-d H:i:s')
            ]);
        });

        return back()->with('success', 'Pickup schedule selected successfully.');
    }
}
