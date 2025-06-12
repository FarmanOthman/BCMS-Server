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
        Schema::create('users', function (Blueprint $table) {
            // $table->id(); // Default Laravel auto-incrementing ID, not needed if Supabase ID is primary
            $table->uuid('id')->primary(); // Use UUID from Supabase as primary
            $table->string('name');
            $table->string('email')->unique();
            // $table->timestamp('email_verified_at')->nullable(); // Supabase handles this
            // $table->string('password'); // Supabase handles this
            // $table->rememberToken(); // Not used with token-based API auth
            $table->string('role')->nullable(); // Add role column
            $table->timestamps();
        });

        // Schema::create('password_reset_tokens', function (Blueprint $table) { // Supabase handles password resets
        //     $table->string('email')->primary();
        //     $table->string('token');
        //     $table->timestamp('created_at')->nullable();
        // });

        // Schema::create('sessions', function (Blueprint $table) { // Not typically used with stateless API tokens
        //     $table->string('id')->primary();
        //     $table->foreignId('user_id')->nullable()->index();
        //     $table->string('ip_address', 45)->nullable();
        //     $table->text('user_agent')->nullable();
        //     $table->longText('payload');
        //     $table->integer('last_activity')->index();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        // Schema::dropIfExists('password_reset_tokens');
        // Schema::dropIfExists('sessions');
    }
};
