<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->onDelete('cascade');
            $table->foreignId('renter_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('rental_status_id')->constrained();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_price', 10, 2);
            $table->decimal('service_fee', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->text('renter_notes')->nullable();
            $table->text('lender_notes')->nullable();
            $table->timestamp('payment_received_at')->nullable();
            $table->timestamp('payment_released_at')->nullable();
            $table->timestamp('handover_at')->nullable();
            $table->timestamp('return_at')->nullable();
            $table->decimal('overdue_amount', 10, 2)->default(0);
            $table->timestamp('overdue_paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};