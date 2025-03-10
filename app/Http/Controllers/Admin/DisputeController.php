<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RentalDispute;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DisputeController extends Controller
{
    public function index()
    {
        $stats = [
            'total' => RentalDispute::count(),
            'pending' => RentalDispute::where('status', 'pending')->count(),
            'reviewed' => RentalDispute::where('status', 'reviewed')->count(),
            'resolved' => RentalDispute::where('status', 'resolved')->count(),
        ];

        $disputes = RentalDispute::with(['rental.listing', 'rental.renter'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Admin/Disputes', [
            'disputes' => $disputes,
            'stats' => $stats
        ]);
    }

    public function resolve(Request $request, RentalDispute $dispute)
    {
        $validated = $request->validate([
            'verdict' => ['required', 'string'],
            'verdict_notes' => ['required', 'string'],
        ]);

        $dispute->update([
            'status' => 'resolved',
            'verdict' => $validated['verdict'],
            'verdict_notes' => $validated['verdict_notes'],
            'resolved_at' => now(),
            'resolved_by' => auth()->id()
        ]);

        $dispute->rental->recordTimelineEvent('dispute_resolved', auth()->id(), [
            'verdict' => $validated['verdict'],
            'verdict_notes' => $validated['verdict_notes']
        ]);

        return back()->with('success', 'Dispute resolved successfully.');
    }
}
