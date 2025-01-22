<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('takedown_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('label');
            $table->text('description');
            $table->timestamps();
        });

        Schema::create('listing_takedowns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('takedown_reason_id')->constrained();
            $table->foreignId('admin_id')->constrained('users');
            $table->text('custom_feedback')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_takedowns');
        Schema::dropIfExists('takedown_reasons');
    }
};
