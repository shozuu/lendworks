<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('handover_disputes', function (Blueprint $table) {
            $table->id();
            // Add check for rental_requests table existence
            if (Schema::hasTable('rental_requests')) {
                $table->foreignId('rental_request_id')
                    ->constrained()
                    ->onDelete('cascade');
            } else {
                // If rental_requests doesn't exist, create a regular column first
                $table->unsignedBigInteger('rental_request_id');
            }
            
            // Add check for pickup_schedules table existence
            if (Schema::hasTable('pickup_schedules')) {
                $table->foreignId('schedule_id')
                    ->constrained('pickup_schedules')
                    ->onDelete('cascade');
            } else {
                // If pickup_schedules doesn't exist, create a regular column first
                $table->unsignedBigInteger('schedule_id');
            }

            $table->enum('type', ['lender_no_show', 'renter_no_show']);
            $table->text('description');
            $table->string('proof_path');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->enum('resolution_type', ['cancel_with_refund', 'cancel_with_penalty', 'reschedule'])->nullable();
            $table->text('resolution_notes')->nullable();
            
            // Add check for users table existence
            if (Schema::hasTable('users')) {
                $table->foreignId('resolved_by')
                    ->nullable()
                    ->constrained('users');
            } else {
                // If users doesn't exist, create a regular column first
                $table->unsignedBigInteger('resolved_by')->nullable();
            }

            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        // Add foreign key constraints if tables weren't available during creation
        if (!Schema::hasTable('rental_requests')) {
            Schema::table('handover_disputes', function (Blueprint $table) {
                $table->foreign('rental_request_id')
                    ->references('id')
                    ->on('rental_requests')
                    ->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('pickup_schedules')) {
            Schema::table('handover_disputes', function (Blueprint $table) {
                $table->foreign('schedule_id')
                    ->references('id')
                    ->on('pickup_schedules')
                    ->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('users')) {
            Schema::table('handover_disputes', function (Blueprint $table) {
                $table->foreign('resolved_by')
                    ->references('id')
                    ->on('users');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('handover_disputes');
    }
};
