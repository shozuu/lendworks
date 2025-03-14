<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('completion_payments', function (Blueprint $table) {
            // First, copy any data from processed_by to admin_id if needed
            DB::statement('UPDATE completion_payments SET admin_id = processed_by WHERE admin_id IS NULL');
            
            // Drop the foreign key constraint first
            $table->dropForeign(['processed_by']);
            
            // Then drop the column
            $table->dropColumn('processed_by');
        });
    }

    public function down(): void
    {
        Schema::table('completion_payments', function (Blueprint $table) {
            $table->foreignId('processed_by')->after('proof_path')->constrained('users');
        });
    }
};
