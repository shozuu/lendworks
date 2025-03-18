<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Create rental_disputes first (with its foreign keys)
        Schema::create('rental_disputes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rental_request_id');
            $table->string('reason');
            $table->text('description');
            $table->string('proof_path');
            $table->enum('status', ['pending', 'reviewed', 'resolved'])->default('pending');
            $table->unsignedBigInteger('raised_by');
            $table->text('verdict')->nullable();
            $table->text('verdict_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->string('resolution_type')->nullable();
            $table->integer('deposit_deduction')->nullable();
            $table->string('deposit_deduction_reason')->nullable();
            $table->timestamps();

            // Add foreign key constraints after defining columns
            $table->foreign('rental_request_id')
                  ->references('id')
                  ->on('rental_requests')
                  ->onDelete('cascade');
            $table->foreign('raised_by')
                  ->references('id')
                  ->on('users');
            $table->foreign('resolved_by')
                  ->references('id')
                  ->on('users');
        });

        // Create deposit_deductions after rental_disputes
        Schema::create('deposit_deductions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rental_request_id');
            $table->unsignedBigInteger('dispute_id');
            $table->integer('amount');
            $table->string('reason');
            $table->unsignedBigInteger('admin_id');
            $table->timestamps();

            $table->foreign('rental_request_id')
                  ->references('id')
                  ->on('rental_requests');
            $table->foreign('dispute_id')
                  ->references('id')
                  ->on('rental_disputes');
            $table->foreign('admin_id')
                  ->references('id')
                  ->on('users');
        });

        // Create lender_earnings_adjustments last
        Schema::create('lender_earnings_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rental_request_id');
            $table->string('type');
            $table->integer('amount');
            $table->string('description');
            $table->string('reference_id');
            $table->timestamps();

            $table->foreign('rental_request_id')
                  ->references('id')
                  ->on('rental_requests');
        });
    }

    public function down()
    {
        Schema::dropIfExists('lender_earnings_adjustments');
        Schema::dropIfExists('deposit_deductions');
        Schema::dropIfExists('rental_disputes');
    }
};
