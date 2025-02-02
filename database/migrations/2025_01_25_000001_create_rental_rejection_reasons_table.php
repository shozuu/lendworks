<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('rental_rejection_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->text('description');
            $table->text('action_needed');
            $table->timestamps();
        });

        // pivot table for rental request rejections
        Schema::create('rental_request_rejections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('rental_rejection_reason_id')->constrained()->onDelete('cascade');
            $table->foreignId('lender_id')->constrained('users')->onDelete('cascade');
            $table->text('custom_feedback')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rental_rejection_reasons');
        Schema::dropIfExists('rental_request_rejections');
    }
};
