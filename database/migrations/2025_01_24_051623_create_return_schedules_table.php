<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('return_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_request_id')->constrained()->onDelete('cascade');
            $table->dateTime('return_datetime');
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->boolean('is_selected')->default(false);
            $table->boolean('is_confirmed')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('return_schedules');
    }
};
