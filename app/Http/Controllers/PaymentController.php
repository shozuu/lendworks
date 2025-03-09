<?php

namespace App\Http\Controllers;

use App\Models\RentalRequest;
use App\Models\PaymentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = PaymentRequest::with(['rentalRequest.listing', 'rentalRequest.renter'])
            ->select(['id', 'rental_request_id', 'reference_number', 'payment_proof_path', 'status', 'admin_feedback', 'verified_by', 'verified_at', 'created_at'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments
        ]);
    }

    public function store(Request $request, RentalRequest $rental)
    {
        // Validate that the user is the renter
        if ($rental->renter_id !== Auth::id()) {
            abort(403);
        }

        // Validate that the rental is in approved status
        if ($rental->status !== 'approved') {
            return back()->with('error', 'This rental request is not in approved status.');
        }

        // Validate the request
        $validated = $request->validate([
            'reference_number' => ['required', 'string', 'max:255'],
            'payment_proof' => ['required', 'image', 'max:2048'] // max 2MB
        ]);

        try {
            DB::beginTransaction();

            // Store the payment proof image
            $path = $request->file('payment_proof')->store('images/payment-proofs', 'public');

            // Create payment request record 
            $paymentRequest = PaymentRequest::create([
                'rental_request_id' => $rental->id,
                'reference_number' => $validated['reference_number'],
                'payment_proof_path' => $path, 
                'status' => 'pending'
            ]);

            // Record timeline event
            $rental->recordTimelineEvent('payment_submitted', Auth::id(), [
                'reference_number' => $validated['reference_number'],
                'payment_request_id' => $paymentRequest->id
            ]);

            DB::commit();

            return back()->with('success', 'Payment submitted successfully! Please wait for verification.');

        } catch (\Exception $e) {
            DB::rollBack();
            // Delete uploaded file if it exists
            if (isset($path)) {
                Storage::disk('public')->delete($path);
            }
            return back()->with('error', 'Failed to submit payment. Please try again.');
        }
    }

    public function storeOverduePayment(Request $request, RentalRequest $rental)
    {
        if ($rental->renter_id !== Auth::id()) {
            abort(403);
        }

        if (!$rental->is_overdue) {
            return back()->with('error', 'This rental is not overdue.');
        }

        $validated = $request->validate([
            'reference_number' => ['required', 'string', 'max:255'],
            'payment_proof' => ['required', 'image', 'max:2048']
        ]);

        try {
            DB::beginTransaction();

            $path = $request->file('payment_proof')->store('images/payment-proofs', 'public');

            $paymentRequest = PaymentRequest::create([
                'rental_request_id' => $rental->id,
                'reference_number' => $validated['reference_number'],
                'payment_proof_path' => $path,
                'status' => 'pending',
                'type' => 'overdue',
                'amount' => $rental->overdue_fee
            ]);

            // Update timeline event metadata to include reference number
            $rental->recordTimelineEvent('overdue_payment_submitted', Auth::id(), [
                'reference_number' => $validated['reference_number'],
                'amount' => $rental->overdue_fee,
                'payment_request' => [
                    'id' => $paymentRequest->id,
                    'reference_number' => $paymentRequest->reference_number,
                    'payment_proof_path' => $paymentRequest->payment_proof_path,
                    'status' => $paymentRequest->status,
                    'amount' => $paymentRequest->amount
                ]
            ]);

            DB::commit();
            return back()->with('success', 'Overdue payment submitted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($path)) {
                Storage::disk('public')->delete($path);
            }
            return back()->with('error', 'Failed to submit payment.');
        }
    }

    public function verify(Request $request, PaymentRequest $payment)
    {
        try {
            DB::transaction(function () use ($payment) {
                $rentalRequest = $payment->rentalRequest;

                if ($payment->type === 'overdue') {
                    // Calculate and store overdue fee
                    $overdueFee = $rentalRequest->overdue_fee;

                    // Create/update overdue payment record
                    $overduePayment = $rentalRequest->overdue_payment()->create([
                        'amount' => $overdueFee,
                        'reference_number' => $payment->reference_number,
                        'proof_path' => $payment->payment_proof_path,
                        'verified_at' => now(),
                        'verified_by' => Auth::id()
                    ]);

                    // Update payment request
                    $payment->update([
                        'status' => 'verified',
                        'verified_by' => Auth::id(),
                        'verified_at' => now(),
                        'amount' => $overdueFee
                    ]);

                    // Record timeline event
                    $rentalRequest->recordTimelineEvent('overdue_payment_verified', Auth::id(), [
                        'payment_request' => [
                            'id' => $payment->id,
                            'reference_number' => $payment->reference_number,
                            'amount' => $overdueFee,
                            'verified_at' => now()->toDateTimeString()
                        ],
                        'verified_by' => Auth::user()->name,
                        'amount' => $overdueFee,
                        'overdue_days' => $rentalRequest->overdue_days
                    ]);

                    // Force refresh to ensure we have latest data
                    $rentalRequest->load('overdue_payment');
                    $rentalRequest->refresh();
                } else {
                    // For regular rental payments, update rental status to 'to_handover'
                    $payment->rentalRequest->update(['status' => 'to_handover']);
                    
                    $payment->rentalRequest->recordTimelineEvent('payment_verified', Auth::id(), [
                        'payment_request' => [
                            'id' => $payment->id,
                            'reference_number' => $payment->reference_number,
                            'payment_proof_path' => $payment->payment_proof_path,
                            'amount' => $payment->amount,
                            'verified_at' => now()->toDateTimeString()
                        ],
                        'verified_by' => Auth::user()->name,
                        'total_amount' => $payment->rentalRequest->total_price,
                        'is_initial_payment' => true
                    ]);
                }
            });

            return back()->with('success', 'Payment verified successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return back()->with('error', 'Failed to verify payment: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, PaymentRequest $payment)
    {
        $validated = $request->validate([
            'feedback' => ['required', 'string', 'min:10']
        ]);

        try {
            DB::beginTransaction();

            // Update payment request status
            $payment->update([
                'status' => 'rejected',
                'admin_feedback' => $validated['feedback'],
                'verified_by' => Auth::id(),
                'verified_at' => now()
            ]);

            // Key fix: Different handling based on payment type
            if ($payment->type === 'overdue') {
                // For overdue payments, DON'T change the rental status
                // Just record the rejection event
                $payment->rentalRequest->recordTimelineEvent('overdue_payment_rejected', Auth::id(), [
                    'payment_request_id' => $payment->id,
                    'amount' => $payment->amount,
                    'feedback' => $validated['feedback'],
                    'rejected_by' => Auth::user()->name
                ]);
            } else {
                // For regular rental payments
                $payment->rentalRequest->update(['status' => 'approved']);
                $payment->rentalRequest->recordTimelineEvent('payment_rejected', Auth::id(), [
                    'payment_request_id' => $payment->id,
                    'feedback' => $validated['feedback'],
                    'rejected_by' => Auth::user()->name
                ]);
            }
                
            DB::commit();
            return back()->with('success', 'Payment rejected successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to reject payment.');
        }
    }
}
