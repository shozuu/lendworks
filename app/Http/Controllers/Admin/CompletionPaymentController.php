<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RentalRequest;
use App\Models\CompletionPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CompletionPaymentController extends Controller
{
    public function storeLenderPayment(Request $request, RentalRequest $rental)
    {
        Log::info('Received lender payment request', [
            'rental_id' => $rental->id,
            'data' => $request->all()
        ]);

        $validated = $request->validate([
            'reference_number' => ['required', 'string'],
            'proof_image' => ['required', 'image', 'max:2048'],
            'notes' => ['nullable', 'string'],
            'amount' => ['required', 'numeric']
        ]);

        try {
            DB::beginTransaction();

            $path = $request->file('proof_image')->store('payment-proofs', 'public');
            
            Log::info('Creating completion payment', [
                'rental_id' => $rental->id,
                'amount' => $validated['amount'],
                'path' => $path
            ]);

            // Calculate total amount including dispute deduction
            $baseEarnings = $rental->base_price - $rental->discount - $rental->service_fee;
            $overdueFee = $rental->overdue_payment ? $rental->overdue_payment->amount : 0;
            $disputeDeduction = $rental->dispute ? $rental->dispute->deposit_deduction : 0;
            
            $totalAmount = $baseEarnings + $overdueFee + $disputeDeduction;

            $payment = CompletionPayment::create([
                'rental_request_id' => $rental->id,
                'type' => 'lender_payment',
                'amount' => $totalAmount,
                'reference_number' => $validated['reference_number'],
                'proof_path' => $path,
                'admin_id' => Auth::id(),
                'notes' => $validated['notes'] ?? null,
                'processed_at' => now()
            ]);

            // Update rental status
            if ($rental->completion_payments()->where('type', 'deposit_refund')->exists()) {
                $rental->update(['status' => 'completed_with_payments']);
            } else {
                $rental->update(['status' => 'completed_pending_payments']);
            }

            $rental->recordTimelineEvent('lender_payment_processed', Auth::id(), [
                'amount' => $validated['amount'],
                'reference_number' => $validated['reference_number'],
                'proof_path' => $path,
                'processed_by' => Auth::user()->name,
                'processed_at' => now()->toDateTimeString()
            ]);

            DB::commit();
            
            Log::info('Lender payment processed successfully', [
                'rental_id' => $rental->id,
                'payment_id' => $payment->id
            ]);

            return redirect()->back()->with('success', 'Lender payment processed successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($path)) {
                Storage::disk('public')->delete($path);
            }
            
            Log::error('Failed to process lender payment', [
                'rental_id' => $rental->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Failed to process lender payment: ' . $e->getMessage());
        }
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

            // Calculate refund amount after deductions
            $depositAmount = $rental->deposit_fee;
            $deductionAmount = $rental->dispute ? $rental->dispute->deposit_deduction : 0;
            $refundAmount = $depositAmount - $deductionAmount;

            CompletionPayment::create([
                'rental_request_id' => $rental->id,
                'type' => 'deposit_refund',
                'amount' => $refundAmount,
                'proof_path' => $path,
                'admin_id' => Auth::id(), // Change to admin_id
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
