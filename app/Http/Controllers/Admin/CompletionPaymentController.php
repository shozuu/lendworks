<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RentalRequest;
use App\Models\CompletionPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompletionPaymentController extends Controller
{
    public function storeLenderPayment(Request $request, RentalRequest $rental)
    {
        $validated = $request->validate([
            'proof_image' => 'required|image|max:5120',
            'reference_number' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        DB::transaction(function () use ($rental, $request, $validated) {
            $path = $request->file('proof_image')->store('completion-payments', 'public');

            CompletionPayment::create([
                'rental_request_id' => $rental->id,
                'type' => 'lender_payment',
                'amount' => $validated['amount'],
                'proof_path' => $path,
                'admin_id' => Auth::id(),
                'reference_number' => $validated['reference_number'],
                'notes' => $validated['notes'],
                'processed_at' => now()
            ]);

            // Update rental status if both payments are processed
            if ($rental->completion_payments()->where('type', 'deposit_refund')->exists()) {
                $rental->update(['status' => 'completed_with_payments']);
            }

            // Record timeline event with metadata
            $rental->recordTimelineEvent('lender_payment_processed', Auth::id(), [
                'amount' => $validated['amount'],
                'reference_number' => $validated['reference_number'],
                'proof_path' => $path,
                'processed_by' => Auth::user()->name,
                'processed_at' => now()->format('Y-m-d H:i:s')
            ]);
        });

        return back()->with('success', 'Lender payment processed successfully.');
    }

    public function storeDepositRefund(Request $request, RentalRequest $rental)
    {
        $validated = $request->validate([
            'proof_image' => 'required|image|max:5120',
            'reference_number' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        DB::transaction(function () use ($rental, $request, $validated) {
            $path = $request->file('proof_image')->store('completion-payments', 'public');

            CompletionPayment::create([
                'rental_request_id' => $rental->id,
                'type' => 'deposit_refund',
                'amount' => $validated['amount'],
                'proof_path' => $path,
                'admin_id' => Auth::id(),
                'reference_number' => $validated['reference_number'],
                'notes' => $validated['notes'],
                'processed_at' => now()
            ]);

            // Update rental status if both payments are processed
            if ($rental->completion_payments()->where('type', 'lender_payment')->exists()) {
                $rental->update(['status' => 'completed_with_payments']);
            }

            // Record timeline event with metadata
            $rental->recordTimelineEvent('deposit_refund_processed', Auth::id(), [
                'amount' => $validated['amount'],
                'reference_number' => $validated['reference_number'],
                'proof_path' => $path,
                'processed_by' => Auth::user()->name,
                'processed_at' => now()->format('Y-m-d H:i:s')
            ]);
        });

        return back()->with('success', 'Security deposit refund processed successfully.');
    }
}
