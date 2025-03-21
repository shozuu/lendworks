<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('id_hashes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('primary_id_hash', 64)->index();
            $table->string('primary_id_type', 20);
            $table->string('secondary_id_hash', 64)->index();
            $table->string('secondary_id_type', 20);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            
            // Add index for faster duplicate checking
            $table->index(['primary_id_hash', 'secondary_id_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('id_hashes');
    }
};