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
            'amount' => ['required', 'numeric'],
            'reference_number' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'proof_image' => ['required', 'image', 'max:5120']
        ]);

        try {
            DB::beginTransaction();

            // Calculate base earnings
            $baseEarnings = $rental->base_price - $rental->discount - $rental->service_fee;
            $overdueFee = $rental->overdue_payment ? $rental->overdue_fee : 0;
            $disputeDeduction = $rental->dispute && $rental->dispute->resolution_type === 'deposit_deducted' 
                ? $rental->dispute->deposit_deduction 
                : 0;

            // Ensure the amount matches our calculation
            $expectedAmount = $baseEarnings + $overdueFee + $disputeDeduction;
            if ($validated['amount'] != $expectedAmount) {
                throw new \Exception('Invalid payment amount');
            }

            // Store proof image
            $imagePath = $request->file('proof_image')->store('payment-proofs', 'public');

            // Create payment record
            $payment = CompletionPayment::create([
                'rental_request_id' => $rental->id,
                'type' => 'lender_payment',
                'amount' => $validated['amount'],
                'reference_number' => $validated['reference_number'],
                'proof_path' => $imagePath,
                'notes' => $validated['notes'],
                'admin_id' => Auth::id(),
                'processed_at' => now(),
            ]);

            // Add timeline event
            $rental->recordTimelineEvent('lender_payment_processed', Auth::id(), [
                'amount' => $validated['amount'],
                'reference_number' => $validated['reference_number'],
                'proof_path' => $imagePath,
                'processed_by' => Auth::id(),
                'processed_at' => now()->toDateTimeString()
            ]);

            // Check and update completion status
            $rental->checkCompletionPaymentStatus();

            // Check completion status and restore units if needed
            $this->checkAndRestoreUnits($rental);

            DB::commit();
            return back()->with('success', 'Lender payment processed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process lender payment', [
                'rental_id' => $rental->id,
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Failed to process payment. ' . $e->getMessage());
        }
    }

    public function storeDepositRefund(Request $request, RentalRequest $rental)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric'],
            'reference_number' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'proof_image' => ['required', 'image', 'max:5120']
        ]);

        try {
            DB::transaction(function () use ($rental, $request, $validated) {
                // Calculate refund amount (total deposit minus any deductions)
                $depositDeduction = $rental->dispute && $rental->dispute->resolution_type === 'deposit_deducted' 
                    ? $rental->dispute->deposit_deduction 
                    : 0;
                
                $expectedRefund = $rental->deposit_fee - $depositDeduction;
                
                if ($validated['amount'] != $expectedRefund) {
                    throw new \Exception('Invalid refund amount');
                }

                // Store proof image
                $imagePath = $request->file('proof_image')->store('payment-proofs', 'public');

                // Create payment record
                CompletionPayment::create([
                    'rental_request_id' => $rental->id,
                    'type' => 'deposit_refund',
                    'amount' => $validated['amount'],
                    'reference_number' => $validated['reference_number'],
                    'proof_path' => $imagePath,
                    'notes' => $validated['notes'],
                    'admin_id' => Auth::id(),
                    'processed_at' => now(),
                ]);

                // Add timeline event
                $rental->recordTimelineEvent('deposit_refund_processed', Auth::id(), [
                    'amount' => $validated['amount'],
                    'reference_number' => $validated['reference_number'],
                    'proof_path' => $imagePath,
                    'processed_by' => Auth::id(),
                    'processed_at' => now()->toDateTimeString()
                ]);

                // Check and update completion status
                $rental->checkCompletionPaymentStatus();

                // Check completion status and restore units if needed
                $this->checkAndRestoreUnits($rental);
            });

            return back()->with('success', 'Security deposit refund processed successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to process deposit refund', [
                'rental_id' => $rental->id,
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Failed to process refund. ' . $e->getMessage());
        }
    }

    protected function checkAndRestoreUnits(RentalRequest $rental)
    {
        // Check if both payments are completed
        $hasLenderPayment = $rental->completion_payments()
            ->where('type', 'lender_payment')
            ->exists();
            
        $hasDepositRefund = $rental->completion_payments()
            ->where('type', 'deposit_refund')
            ->exists();

        // If both payments exist, restore the units and update status
        if ($hasLenderPayment && $hasDepositRefund) {
            DB::transaction(function () use ($rental) {
                // Update rental status
                $rental->update(['status' => 'completed_with_payments']);

                // Get the listing and restore units
                $listing = $rental->listing;
                $listing->increment('available_quantity', $rental->quantity_approved);

                // Update listing rental status if needed
                if ($listing->available_quantity > 0) {
                    $listing->update(['is_rented' => false]);
                }

                // Add timeline event
                $rental->recordTimelineEvent('units_restored', Auth::id(), [
                    'restored_quantity' => $rental->quantity_approved,
                    'new_available_quantity' => $listing->available_quantity
                ]);
            });
        }
    }
}
