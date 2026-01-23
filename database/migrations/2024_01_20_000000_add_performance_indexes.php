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
        // Add indexes to users table for common queries
        Schema::table('users', function (Blueprint $table) {
            // Index for filtering verified users (phone-based auth)
            $table->index('phone_verified_at');
        });

        // Add foreign key constraint to sessions table (if foreign keys are enabled)
        if (config('database.connections.' . config('database.default') . '.foreign_key_constraints', true)) {
            Schema::table('sessions', function (Blueprint $table) {
                try {
                    $table->foreign('user_id')
                        ->references('id')
                        ->on('users')
                        ->onDelete('cascade');
                } catch (\Exception $e) {
                    // Foreign key may already exist, skip
                }
            });
        }

        // Add indexes to personal_access_tokens for token cleanup
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Index for finding expired tokens
            $table->index('expires_at');
            
            // Index for token rotation based on last_used_at
            $table->index('last_used_at');
        });

        // Add indexes to password_reset_tokens for cleanup
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            // Index for cleanup of old tokens
            $table->index('created_at');
            
            // Index for token lookup
            $table->index('token');
        });

        // Add composite index to jobs table for efficient job processing
        Schema::table('jobs', function (Blueprint $table) {
            // Composite index for queue processing: (queue, available_at)
            $table->index(['queue', 'available_at']);
            
            // Index for reserved_at timeout detection
            $table->index('reserved_at');
        });

        // Add index to failed_jobs for cleanup
        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->index('failed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['phone_verified_at']);
        });

        if (config('database.connections.' . config('database.default') . '.foreign_key_constraints', true)) {
            Schema::table('sessions', function (Blueprint $table) {
                try {
                    $table->dropForeign(['user_id']);
                } catch (\Exception $e) {
                    // Foreign key may not exist, skip
                }
            });
        }

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
            $table->dropIndex(['last_used_at']);
        });

        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['token']);
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex(['queue', 'available_at']);
            $table->dropIndex(['reserved_at']);
        });

        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->dropIndex(['failed_at']);
        });
    }
};
