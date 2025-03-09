<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('completion_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('completion_payments', 'admin_id')) {
                $table->foreignId('admin_id')->constrained('users');
            }
            if (!Schema::hasColumn('completion_payments', 'reference_number')) {
                $table->string('reference_number');
            }
            if (!Schema::hasColumn('completion_payments', 'proof_path')) {
                $table->string('proof_path');
            }
            if (!Schema::hasColumn('completion_payments', 'amount')) {
                $table->decimal('amount', 10, 2);
            }
            if (!Schema::hasColumn('completion_payments', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (!Schema::hasColumn('completion_payments', 'processed_at')) {
                $table->timestamp('processed_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('completion_payments', function (Blueprint $table) {
            $columns = [
                'admin_id',
                'reference_number', 
                'proof_path',
                'amount',
                'notes',
                'processed_at'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('completion_payments', $column)) {
                    if ($column === 'admin_id') {
                        $table->dropForeign(['admin_id']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
