<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('rental_disputes', function (Blueprint $table) {
            $table->string('resolution_type')->nullable();
            $table->integer('deposit_deduction')->nullable();
            $table->string('deposit_deduction_reason')->nullable();
        });

        Schema::create('deposit_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_request_id')->constrained();
            $table->foreignId('dispute_id')->constrained('rental_disputes');
            $table->integer('amount');
            $table->string('reason');
            $table->foreignId('admin_id')->constrained('users');
            $table->timestamps();
        });

        Schema::create('lender_earnings_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_request_id')->constrained();
            $table->string('type');
            $table->integer('amount');
            $table->string('description');
            $table->string('reference_id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lender_earnings_adjustments');
        Schema::dropIfExists('deposit_deductions');
        Schema::table('rental_disputes', function (Blueprint $table) {
            $table->dropColumn(['resolution_type', 'deposit_deduction', 'deposit_deduction_reason']);
        });
    }
};
