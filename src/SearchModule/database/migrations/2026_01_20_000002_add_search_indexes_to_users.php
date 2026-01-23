<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add name index if it doesn't exist
            $indexes = collect(DB::select("SHOW INDEXES FROM users WHERE Key_name = 'users_name_index'"));
            if ($indexes->isEmpty()) {
                $table->index('name');
            }
            
            // Covering index for search queries (includes phone_verified_at for phone-based auth)
            if (Schema::hasColumn('users', 'phone_verified_at')) {
                $coveringIndexes = collect(DB::select("SHOW INDEXES FROM users WHERE Key_name = 'idx_users_search_covering'"));
                if ($coveringIndexes->isEmpty()) {
                    $table->index(['username', 'id', 'name', 'phone_verified_at'], 'idx_users_search_covering');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_search_covering');
            $table->dropIndex(['name']);
        });
    }
};
