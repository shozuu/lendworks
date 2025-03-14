<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('completion_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('completion_payments', 'processed_by')) {
                $table->foreignId('processed_by')->after('proof_path')->constrained('users');
            }
            if (!Schema::hasColumn('completion_payments', 'notes')) {
                $table->text('notes')->nullable()->after('processed_by');
            }
        });
    }

    public function down()
    {
        Schema::table('completion_payments', function (Blueprint $table) {
            $table->dropColumn(['processed_by', 'notes']);
        });
    }
};
