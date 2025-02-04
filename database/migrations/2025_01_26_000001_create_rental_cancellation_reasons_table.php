<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('rental_cancellation_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->text('description');
            $table->timestamps();
        });

        Schema::create('rental_request_cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('rental_cancellation_reason_id')
                ->constrained()
                ->onDelete('cascade')
                ->name('fk_rental_cancellation_reason'); 
            $table->text('custom_feedback')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rental_request_cancellations');
        Schema::dropIfExists('rental_cancellation_reasons');
    }
};
