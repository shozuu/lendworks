<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE rental_requests MODIFY COLUMN status ENUM(
            'pending',
            'approved',
            'active',
            'completed',
            'rejected',
            'cancelled',
            'renter_paid',
            'pending_proof',
            'to_handover',
            'pending_return',
            'return_scheduled',
            'pending_return_confirmation',
            'pending_final_confirmation',
            'completed_pending_payments',
            'completed_with_payments',
            'disputed'
        ) NOT NULL");
    }

    public function down()
    {
        DB::statement("ALTER TABLE rental_requests MODIFY COLUMN status ENUM(
            'pending',
            'approved',
            'active',
            'completed',
            'rejected',
            'cancelled',
            'renter_paid',
            'pending_proof',
            'to_handover',
            'pending_return',
            'return_scheduled',
            'pending_return_confirmation',
            'pending_final_confirmation',
            'completed_pending_payments',
            'completed_with_payments'
        ) NOT NULL");
    }
};
