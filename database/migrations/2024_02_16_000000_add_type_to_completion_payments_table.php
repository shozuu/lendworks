<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('completion_payments', function (Blueprint $table) {
            $table->enum('type', ['lender_payment', 'deposit_refund'])->after('rental_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('completion_payments', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
