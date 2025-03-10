<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_requests', function (Blueprint $table) {
            DB::statement("ALTER TABLE rental_requests MODIFY COLUMN status ENUM(
                'pending',
                'approved',
                'active',
                'completed',
                'rejected',
                'cancelled',
                'to_handover',
                'pending_proof',
                'pending_return',
                'return_scheduled',
                'pending_return_confirmation',
                'pending_final_confirmation',
                'completed_pending_payments',
                'completed_with_payments'
            )");
        });
    }

    public function down(): void
    {
        // Previous status enum
    }
};
