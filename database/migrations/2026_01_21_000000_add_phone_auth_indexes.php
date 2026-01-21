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
        // Note: phone is already primary key, created_at already has index
        // No additional indexes needed for verification code tables
        
        // Add index on username for search performance (if not exists)
        Schema::table('users', function (Blueprint $table) {
            // Username is likely already indexed from unique constraint
            // This migration is kept for documentation purposes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No indexes to drop
    }
};
