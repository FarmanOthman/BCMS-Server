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
        Schema::table('cars', function (Blueprint $table) {
            if (!Schema::hasColumn('cars', 'repair_items')) {
                // For PostgreSQL, use JSONB for better performance
                if (DB::getDriverName() === 'pgsql') {
                    DB::statement('ALTER TABLE cars ADD COLUMN repair_items JSONB DEFAULT \'[]\'::jsonb');
                } else {
                    // For other databases like MySQL, use JSON type
                    $table->json('repair_items')->nullable()->default(DB::raw('(JSON_ARRAY())'));
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            if (Schema::hasColumn('cars', 'repair_items')) {
                $table->dropColumn('repair_items');
            }
        });
    }
};
