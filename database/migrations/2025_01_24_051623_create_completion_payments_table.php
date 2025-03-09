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
            $table->foreignId('admin_id')->constrained('users');
            $table->decimal('amount', 10, 2);
            $table->string('reference_number');
            $table->string('proof_path');
            $table->text('notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->foreign('verified_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('completion_payments');
    }
};