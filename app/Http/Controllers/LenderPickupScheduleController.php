<?php

namespace App\Http\Controllers;

use App\Models\LenderPickupSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LenderPickupScheduleController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'day_of_week' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|string',
            'end_time' => 'required|string|after:start_time',
        ]);

        // Convert times to proper format
        try {
            $startTime = date('H:i:s', strtotime($validated['start_time']));
            $endTime = date('H:i:s', strtotime($validated['end_time']));

            $schedule = LenderPickupSchedule::create([
                'user_id' => Auth::id(),
                'day_of_week' => $validated['day_of_week'],
                'start_time' => $startTime,
                'end_time' => $endTime,
                'is_active' => true,
            ]);

            return back()->with('success', 'Pickup schedule added successfully.');
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Failed to add pickup schedule.');
        }
    }

    public function update(Request $request, LenderPickupSchedule $schedule)
    {
        if ($schedule->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'day_of_week' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|string',
            'end_time' => 'required|string|after:start_time',
        ]);

        try {
            $startTime = date('H:i:s', strtotime($validated['start_time']));
            $endTime = date('H:i:s', strtotime($validated['end_time']));

            $schedule->update([
                'day_of_week' => $validated['day_of_week'],
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
}
