<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('completion_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_request_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['lender_payment', 'deposit_refund']);
            $table->integer('amount');
            $table->boolean('includes_overdue_fee')->default(false);
            $table->integer('total_amount')->nullable();
            $table->string('proof_path');
            $table->foreignId('admin_id')->constrained('users'); // Change to admin_id for consistency
            $table->string('reference_number');
            $table->text('notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('completion_payments');
    }
};
