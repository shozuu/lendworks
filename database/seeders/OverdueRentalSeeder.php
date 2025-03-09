<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RentalRequest;
use App\Models\PaymentRequest;
use App\Models\OverduePayment;
use App\Models\User;
use App\Models\Listing;
use Carbon\Carbon;

class OverdueRentalSeeder extends Seeder
{
    public function run()
    {
        // Get some existing users and listings
        $renters = User::where('status', 'active')->take(5)->get();
        $listings = Listing::where('status', 'approved')->take(5)->get();

        foreach ($renters as $index => $renter) {
            $listing = $listings[$index];
            $admin = User::where('role', 'admin')->first();

            // Create base rental 
            $rental = RentalRequest::create([
                'listing_id' => $listing->id,
                'renter_id' => $renter->id,
                'start_date' => Carbon::now()->subDays(30), // Started 30 days ago
                'end_date' => Carbon::now()->subDays(5), // Ended 5 days ago
                'base_price' => $listing->price * 25, // 25 days rental
                'discount' => 0,
                'service_fee' => ($listing->price * 25) * 0.1, // 10% service fee
                'deposit_fee' => $listing->price * 2, // 2x daily rate deposit
                'total_price' => ($listing->price * 25) * 1.1, // Total with service fee
                'status' => 'active',
                'handover_at' => Carbon::now()->subDays(30),
            ]);

            // Create timeline events
            $rental->recordTimelineEvent('created', $renter->id);
            $rental->recordTimelineEvent('approved', $listing->user_id);
            $rental->recordTimelineEvent('payment_verified', $admin->id);
            $rental->recordTimelineEvent('handover_confirmed', $listing->user_id);

            // For every other rental, create a verified overdue payment
            if ($index % 2 === 0) {
                // Create and verify overdue payment
                $paymentRequest = PaymentRequest::create([
                    'rental_request_id' => $rental->id,
                    'reference_number' => 'OVD' . str_pad($rental->id, 6, '0', STR_PAD_LEFT),
                    'payment_proof_path' => 'images/payment-proofs/sample.jpg',
                    'status' => 'verified',
                    'type' => 'overdue',
                    'amount' => $rental->overdue_fee,
                    'verified_by' => $admin->id,
                    'verified_at' => now()
                ]);

                OverduePayment::create([
                    'rental_request_id' => $rental->id,
                    'amount' => $rental->overdue_fee,
                    'reference_number' => $paymentRequest->reference_number,
                    'proof_path' => $paymentRequest->payment_proof_path,
                    'verified_at' => now(),
                    'verified_by' => $admin->id
                ]);

                $rental->recordTimelineEvent('overdue_payment_submitted', $renter->id);
                $rental->recordTimelineEvent('overdue_payment_verified', $admin->id);
            } else {
                // Create pending overdue payment
                PaymentRequest::create([
                    'rental_request_id' => $rental->id,
                    'reference_number' => 'OVD' . str_pad($rental->id, 6, '0', STR_PAD_LEFT),
                    'payment_proof_path' => 'images/payment-proofs/sample.jpg',
                    'status' => 'pending',
                    'type' => 'overdue',
                    'amount' => $rental->overdue_fee
                ]);

                $rental->recordTimelineEvent('overdue_payment_submitted', $renter->id);
            }
        }
    }
}
