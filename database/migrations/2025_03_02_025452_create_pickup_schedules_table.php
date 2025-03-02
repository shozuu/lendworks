<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pickup_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('lender_pickup_schedule_id')->nullable()->constrained()->onDelete('cascade');
            $table->datetime('pickup_datetime');  // Changed from schedule_datetime
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_selected')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pickup_schedules');
    }
};
