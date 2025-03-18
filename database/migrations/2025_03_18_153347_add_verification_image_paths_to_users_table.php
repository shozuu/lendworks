<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('selfie_image_path')->nullable();
        $table->string('primary_id_image_path')->nullable();
        $table->string('secondary_id_image_path')->nullable();

    });
}

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn([
            'selfie_image_path',
            'primary_id_image_path',
            'secondary_id_image_path',
            'liveness_verified_at'
        ]);
    });
}
};
