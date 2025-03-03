<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // First, get the current enum values
        $currentValues = DB::select("SHOW COLUMNS FROM rental_requests WHERE Field = 'status'")[0]->Type;
        
        // Extract current values and add new ones
        preg_match('/^enum\((.*)\)$/', $currentValues, $matches);
        $currentValues = str_replace("'", '', $matches[1]);
        $values = explode(',', $currentValues);
        
        // Add new status values
        $newValues = array_merge($values, [
            'pending_return',
            'return_scheduled',
            'pending_return_confirmation'
        ]);
        
        // Create the new enum definition
        $enumString = "'" . implode("','", array_unique($newValues)) . "'";
        
        // Alter the column
        DB::statement("ALTER TABLE rental_requests MODIFY COLUMN status ENUM($enumString) NOT NULL");
    }

    public function down()
    {
        // Revert to original values without the new statuses
        $originalValues = [
            'pending',
            'approved',
            'active',
            'completed',
            'rejected',
            'cancelled',
            'to_handover',
            'pending_proof'
        ];
        
        $enumString = "'" . implode("','", $originalValues) . "'";
        
        DB::statement("ALTER TABLE rental_requests MODIFY COLUMN status ENUM($enumString) NOT NULL");
    }
};
