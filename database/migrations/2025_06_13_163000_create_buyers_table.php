<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('buyer')) {
            Schema::create('buyer', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->string('name');
                $table->string('phone', 20)->unique();
                $table->text('address')->nullable();
                $table->timestampTz('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->timestampTz('updated_at')->default(DB::raw('CURRENT_TIMESTAMP'));
                
                $table->uuid('created_by')->nullable();
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                
                $table->uuid('updated_by')->nullable();
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            });
        } else {
            // Optionally, if the table exists, you might want to ensure certain columns or constraints are present.
            // For now, we assume if it exists, its schema is as expected from your SQL.
            // Log::info('Table "buyer" already exists. Skipping creation.'); // Example logging
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buyer');
    }
};
