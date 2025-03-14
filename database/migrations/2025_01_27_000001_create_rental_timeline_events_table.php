<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('rental_timeline_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('actor_id')->constrained('users')->onDelete('cascade');
            $table->string('event_type');  // created, approved, rejected, cancelled, etc.
            $table->string('status');      // The resulting status after the event
            $table->json('metadata')->nullable(); // Store additional event-specific data
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rental_timeline_events');
    }
};
