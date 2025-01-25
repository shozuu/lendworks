<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->onDelete('cascade');
            $table->foreignId('renter_id')->constrained('users')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('base_price', 10, 2);
            $table->decimal('discount', 10, 2);
            $table->decimal('service_fee', 10, 2); // platform fee
            $table->decimal('total_price', 10, 2);
            $table->enum('status', [
                'pending',    // Initial state when request is made
                'approved',   // Owner approved, awaiting handover
                'active',     // Item has been handed over
                'completed', // Item has been returned and confirmed
                'rejected',  // Owner rejected the request
                'cancelled'  // Renter cancelled the request
            ])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('handover_at')->nullable();
            $table->timestamp('return_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_requests');
    }
};
