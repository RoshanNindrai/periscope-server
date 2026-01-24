<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Store phone_hash (SHA-256) for lookups; store phone encrypted for SMS (e.g. resend).
     */
    public function up(): void
    {
        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        if (!Schema::hasColumn('users', 'phone_hash')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone_hash', 64)->nullable()->after('phone');
            });
        }

        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['phone']);
            });
        } catch (\Throwable) {
            // Ignore if unique doesn't exist (e.g. SQLite)
        }

        if (!$isSqlite) {
            DB::statement('ALTER TABLE users MODIFY phone TEXT');

            foreach (DB::table('users')->get() as $row) {
                $plain = $row->phone;
                DB::table('users')->where('id', $row->id)->update([
                    'phone_hash' => hash('sha256', $plain),
                    'phone' => Crypt::encryptString($plain),
                ]);
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('phone_hash');
        });

        if (!$isSqlite) {
            DB::statement('ALTER TABLE users MODIFY phone_hash VARCHAR(64) NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (DB::table('users')->get() as $row) {
            try {
                $plain = Crypt::decryptString($row->phone);
                DB::table('users')->where('id', $row->id)->update([
                    'phone' => $plain,
                ]);
            } catch (\Throwable $e) {
                // Cannot decrypt (e.g. corrupted or already plain); leave as-is
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone_hash']);
            $table->dropColumn('phone_hash');
        });

        DB::statement('ALTER TABLE users MODIFY phone VARCHAR(20)');

        Schema::table('users', function (Blueprint $table) {
            $table->unique('phone');
        });
    }
};
