<?php

namespace App\Http\Controllers;

use App\Models\RentalRequest;
use App\Models\PickupSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PickupScheduleController extends Controller
{
    public function store(Request $request, RentalRequest $rental)
    {
        // Authorize that only lender can add schedules
        if ($rental->listing->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'pickup_datetime' => 'required|date',  // Changed to match frontend
        ]);

        $rental->pickup_schedules()->create([
            'pickup_datetime' => $validated['pickup_datetime'],
            'is_selected' => false
        ]);

        // Reload the rental with pickup_schedules relationship
        $rental->load('pickup_schedules');

        return back()->with('success', 'Schedule added successfully');
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

    public function select(RentalRequest $rental, PickupSchedule $schedule)
    {
        // Authorize that only renter can select schedules
        if ($rental->renter_id !== Auth::id()) {
            abort(403);
        }

        // Start a database transaction
        \DB::transaction(function () use ($rental, $schedule) {
            // Delete all other schedules
            $rental->pickup_schedules()
                ->where('id', '!=', $schedule->id)
                ->delete();

            // Mark the selected schedule
            $schedule->update(['is_selected' => true]);
        });

        // Reload the rental with pickup_schedules relationship
        $rental->load('pickup_schedules');

        return back()->with('success', 'Schedule selected successfully');
    }
}
