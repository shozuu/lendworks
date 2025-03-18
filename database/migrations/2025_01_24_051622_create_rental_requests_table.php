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
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('renter_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->integer('base_price');
            $table->integer('discount');
            $table->integer('service_fee');
            $table->integer('deposit_fee');
            $table->integer('total_price');
            $table->integer('quantity_requested')->default(1);
            $table->integer('quantity_approved')->nullable();
            $table->enum('status', [
                'pending',
                'approved',
                'to_handover',
                'pending_proof',
                'active',
                'completed',
                'rejected',
                'cancelled',
                'pending_return',
                'return_scheduled',
                'pending_return_confirmation',
                'pending_final_confirmation',
                'completed_pending_payments',
                'completed_with_payments',
                'overdue',
                'paid_overdue',
                'disputed'
            ])->default('pending');
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
