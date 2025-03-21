<?php

namespace App\Http\Controllers;

use App\Models\RentalRequest;
use App\Models\PickupSchedule;
use App\Models\LenderPickupSchedule;
use App\Notifications\ScheduleActionNotification;
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
        if ($rental->renter_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'start_time' => 'required|string|date_format:H:i',
            'end_time' => 'required|string|date_format:H:i|after:start_time',
        ]);

        try {
            DB::transaction(function () use ($rental, $lender_schedule, $validated) {
                // Verify the selected time slot falls within the lender's schedule
                $lenderStart = Carbon::createFromFormat('H:i:s', $lender_schedule->start_time);
                $lenderEnd = Carbon::createFromFormat('H:i:s', $lender_schedule->end_time);
                $slotStart = Carbon::createFromFormat('H:i', $validated['start_time']);
                $slotEnd = Carbon::createFromFormat('H:i', $validated['end_time']);

                if ($slotStart->lt($lenderStart) || $slotEnd->gt($lenderEnd)) {
                    throw new \Exception('Selected time slot is outside the available schedule.');
                }

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

                // Update timeline event message based on schedule type
                $eventType = 'pickup_schedule_selected';
                $metadata = [
                    'datetime' => $pickupDatetime->format('Y-m-d H:i:s'),
                    'day_of_week' => $pickupDatetime->format('l'),
                    'date' => $pickupDatetime->format('F d, Y'),
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'from_lender_availability' => true // Add this flag
                ];

                $rental->recordTimelineEvent($eventType, Auth::id(), $metadata);
            });

            return back()->with('success', 'Schedule selected successfully.');
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Failed to select schedule: ' . $e->getMessage());
        }
    }

    public function suggest(Request $request, RentalRequest $rental)
    {
        if ($rental->renter_id !== Auth::id()) {
            abort(403);
        }

        // Validate pickup datetime matches rental start date
        $validated = $request->validate([
            'start_time' => 'required|string',
            'end_time' => 'required|string|after:start_time',
            'pickup_datetime' => [
                'required',
                'date',
                function ($attribute, $value, $fail) use ($rental) {
                    // Convert both dates to Y-m-d format for comparison
                    $pickupDate = Carbon::parse($value)->format('Y-m-d');
                    $rentalStart = Carbon::parse($rental->start_date)->format('Y-m-d');
                    
                    if ($pickupDate !== $rentalStart) {
                        $fail('Pickup date must be on the rental start date.');
                    }
                }
            ]
        ]);

        try {
            DB::transaction(function () use ($rental, $validated) {
                // Reset existing selections
                $rental->pickup_schedules()->update(['is_selected' => false]);

                // Create suggested schedule ensuring it uses rental start date
                $pickupDatetime = Carbon::parse($rental->start_date)
                    ->setTimeFromTimeString($validated['start_time']);

                $schedule = $rental->pickup_schedules()->create([
                    'pickup_datetime' => $pickupDatetime,
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'is_selected' => true,
                    'is_suggested' => true
                ]);

                // Add timeline event
                $rental->recordTimelineEvent('pickup_schedule_suggested', Auth::id(), [
                    'datetime' => $schedule->pickup_datetime,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'suggested_by' => 'renter'
                ]);
            });

            return back()->with('success', 'Schedule suggestion sent successfully. Waiting for lender confirmation.');
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Failed to suggest schedule.');
        }
    }

    public function confirmSchedule(RentalRequest $rental)
    {
        // Add validation to ensure only lender can confirm
        if ($rental->listing->user_id !== Auth::id()) {
            abort(403);
        }

        $schedule = $rental->pickup_schedules()
            ->where('is_selected', true)
            ->firstOrFail();

        try {
            DB::transaction(function () use ($rental, $schedule) {
                // Update schedule confirmation
                $schedule->update(['is_confirmed' => true]);
                
                // Update rental status to proceed with handover
                $rental->update(['status' => 'to_handover']);

                // Add notification here
                $schedule->rental->renter->notify(new ScheduleActionNotification(
                    $rental,
                    'pickup_confirmed',
                    [
                        'datetime' => $schedule->pickup_datetime,
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time
                    ]
                ));

                // Update the timeline event type based on whether it was a suggestion
                $eventType = $schedule->is_suggested 
                    ? 'pickup_schedule_suggestion_accepted' 
                    : 'pickup_schedule_confirmed';

                $metadata = [
                    'datetime' => $schedule->pickup_datetime,
                    'day_of_week' => Carbon::parse($schedule->pickup_datetime)->format('l'),
                    'date' => Carbon::parse($schedule->pickup_datetime)->format('F d, Y'),
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'from_lender_availability' => !$schedule->is_suggested
                ];

                $rental->recordTimelineEvent($eventType, Auth::id(), $metadata);
            });

            return back()->with('success', 'Schedule confirmed successfully.');
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Failed to confirm schedule.');
        }
    }
}
