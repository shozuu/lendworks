<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('rental_disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_request_id')->constrained('rental_requests')->onDelete('cascade');
            $table->string('reason');
            $table->text('description');
            $table->string('proof_path');
            $table->enum('status', ['pending', 'reviewed', 'resolved'])->default('pending');
            $table->foreignId('raised_by')->constrained('users');
            $table->text('verdict')->nullable();
            $table->text('verdict_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rental_disputes');
    }
};
