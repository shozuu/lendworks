<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rejection_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('code'); 
            $table->string('label');    
            $table->text('description');   
            $table->text('action_needed'); // predefined instructions for the user
            $table->timestamps();
        });

        // Create pivot table for listing rejections
        Schema::create('listing_rejections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained(); // foreign key to listings table
            $table->foreignId('rejection_reason_id')->constrained(); // foreign key to rejection_reasons table
            $table->text('custom_feedback')->nullable();  // admin's specific feedback
            $table->timestamps(); // when the rejection happened
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_rejections');
        Schema::dropIfExists('rejection_reasons');
    }
};
