<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('dispute_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dispute_id');
            $table->string('image_path');
            $table->timestamps();

            $table->foreign('dispute_id')
                  ->references('id')
                  ->on('rental_disputes')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('dispute_images');
    }
};
