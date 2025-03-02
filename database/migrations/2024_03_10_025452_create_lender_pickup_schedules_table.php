<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lender_pickup_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('day_of_week'); // monday, tuesday, etc.
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Modify existing pickup_schedules table
        Schema::table('pickup_schedules', function (Blueprint $table) {
            $table->foreignId('lender_pickup_schedule_id')->nullable()->constrained()->onDelete('cascade');
            // Keep existing columns
        });
    }

    public function down(): void
    {
        Schema::table('pickup_schedules', function (Blueprint $table) {
            $table->dropForeign(['lender_pickup_schedule_id']);
            $table->dropColumn('lender_pickup_schedule_id');
        });
        Schema::dropIfExists('lender_pickup_schedules');
    }
};
