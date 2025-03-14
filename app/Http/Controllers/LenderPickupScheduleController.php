<?php

namespace App\Http\Controllers;

use App\Models\LenderPickupSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LenderPickupScheduleController extends Controller
{
    public function storeBulk(Request $request)
    {
        $validated = $request->validate([
            'schedules' => 'required|array|min:1',
            'schedules.*.day_of_week' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'schedules.*.start_time' => 'required|string',
            'schedules.*.end_time' => 'required|string|after:schedules.*.start_time',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                foreach ($validated['schedules'] as $schedule) {
                    $startTime = date('H:i:s', strtotime($schedule['start_time']));
                    $endTime = date('H:i:s', strtotime($schedule['end_time']));

                    // Update or create schedule for each day
                    LenderPickupSchedule::updateOrCreate(
                        [
                            'user_id' => Auth::id(),
                            'day_of_week' => $schedule['day_of_week'],
                        ],
                        [
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'is_active' => true,
                        ]
                    );
                }
            });

            return back()->with('success', 'Pickup schedules created successfully.');
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Failed to create pickup schedules.');
        }
    }

    public function update(Request $request, LenderPickupSchedule $schedule)
    {
        if ($schedule->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'start_time' => 'required|string',
            'end_time' => 'required|string|after:start_time',
        ]);

        try {
            $startTime = date('H:i:s', strtotime($validated['start_time']));
            $endTime = date('H:i:s', strtotime($validated['end_time']));

            $schedule->update([
                'start_time' => $startTime,
                'end_time' => $endTime,
            ]);

            return back()->with('success', 'Pickup schedule updated successfully.');
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Failed to update pickup schedule.');
        }
    }

    public function destroy(LenderPickupSchedule $schedule)
    {
        if ($schedule->user_id !== Auth::id()) {
            abort(403);
        }

        $schedule->delete();
        return back()->with('success', 'Pickup schedule removed successfully.');
    }

    public function addTimeSlot(Request $request, $dayOfWeek)
    {
        $validated = $request->validate([
            'start_time' => 'required|string',
            'end_time' => 'required|string|after:start_time',
        ]);

        try {
            $startTime = date('H:i:s', strtotime($validated['start_time']));
            $endTime = date('H:i:s', strtotime($validated['end_time']));

            LenderPickupSchedule::create([
                'user_id' => Auth::id(),
                'day_of_week' => $dayOfWeek,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'is_active' => true,
            ]);

            return back()->with('success', 'Time slot added successfully.');
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Failed to add time slot.');
        }
    }

    public function toggleActive(LenderPickupSchedule $schedule)
    {
        if ($schedule->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            $schedule->update([
                'is_active' => !$schedule->is_active
            ]);

            return back()->with('success', 'Schedule availability updated.');
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Failed to update availability.');
        }
    }

    public function destroyTimeSlot(LenderPickupSchedule $schedule)
    {
        if ($schedule->user_id !== Auth::id()) {
            abort(403);
        }

        $schedule->delete();
        return back()->with('success', 'Time slot removed successfully.');
    }
}
