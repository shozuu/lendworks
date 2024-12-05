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
            $table->unsignedInteger('base_price');
            $table->unsignedInteger('discount')->default(0);
            $table->unsignedInteger('service_fee');
            $table->unsignedInteger('total_price');
            $table->enum('payment_status', ['empty', 'pending', 'paid', 'released'])
                ->nullable()
                ->default(null);
            $table->timestamp('payment_received_at')->nullable();
            $table->timestamp('payment_released_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};