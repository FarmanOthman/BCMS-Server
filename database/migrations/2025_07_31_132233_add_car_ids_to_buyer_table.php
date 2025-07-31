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
        Schema::table('buyer', function (Blueprint $table) {
            if (!Schema::hasColumn('buyer', 'car_ids')) {
                // For PostgreSQL, use JSONB for better performance
                if (DB::getDriverName() === 'pgsql') {
                    DB::statement('ALTER TABLE buyer ADD COLUMN car_ids JSONB DEFAULT \'[]\'::jsonb');
                } else {
                    // For other databases like MySQL, use JSON type
                    $table->json('car_ids')->nullable()->default(DB::raw('(JSON_ARRAY())'));
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buyer', function (Blueprint $table) {
            if (Schema::hasColumn('buyer', 'car_ids')) {
                $table->dropColumn('car_ids');
            }
        });
    }
};
