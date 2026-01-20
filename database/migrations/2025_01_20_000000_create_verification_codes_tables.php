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
        Schema::create('email_verification_codes', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('code', 6);
            $table->integer('attempts')->default(0);
            $table->timestamp('created_at')->nullable();
            
            $table->index('created_at');
        });

        Schema::create('password_reset_codes', function (Blueprint $table) {
            $table->string('email')->primary();
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
        Schema::dropIfExists('email_verification_codes');
        Schema::dropIfExists('password_reset_codes');
    }
};
