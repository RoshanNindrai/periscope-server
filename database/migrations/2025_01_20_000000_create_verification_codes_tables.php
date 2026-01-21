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
        // Phone verification codes (for account verification after registration)
        Schema::create('phone_verification_codes', function (Blueprint $table) {
            $table->string('phone', 20)->primary();
            $table->string('code', 6);
            $table->integer('attempts')->default(0);
            $table->timestamp('created_at')->nullable();
            
            $table->index('created_at');
        });

        // Login verification codes (OTP for passwordless login)
        Schema::create('login_verification_codes', function (Blueprint $table) {
            $table->string('phone', 20)->primary();
            $table->string('code', 6);
            $table->integer('attempts')->default(0);
            $table->timestamp('created_at')->nullable();
            
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phone_verification_codes');
        Schema::dropIfExists('login_verification_codes');
    }
};
