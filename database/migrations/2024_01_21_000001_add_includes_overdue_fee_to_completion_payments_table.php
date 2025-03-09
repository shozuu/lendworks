<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('completion_payments', function (Blueprint $table) {
            $table->boolean('includes_overdue_fee')->default(false)->after('amount');
        });
    }

    public function down()
    {
        Schema::table('completion_payments', function (Blueprint $table) {
            $table->dropColumn('includes_overdue_fee');
        });
    }
};
