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
        Schema::table('cars', function (Blueprint $table) {
            // Add new foreign key columns. 
            // Making them nullable initially is safer if data exists.
            // A separate data migration step would populate these from old columns.
            $table->foreignUuid('make_id')->nullable()->after('id')->constrained('makes')->onDelete('cascade');
            $table->foreignUuid('model_id')->nullable()->after('make_id')->constrained('models')->onDelete('cascade');
        });

        Schema::table('cars', function (Blueprint $table) {
            // Drop old text columns after ensuring data is migrated if necessary.
            $table->dropColumn('make');
            $table->dropColumn('model');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            // Add back old columns. These will be NOT NULL by default as per original schema.
            // This might cause issues if rolling back with data where these were not populated.
            $table->text('make')->after('id');
            $table->text('model')->after('make');
        });

        Schema::table('cars', function (Blueprint $table) {
            // Drop new columns and their foreign key constraints.
            $table->dropForeign(['model_id']);
            $table->dropColumn('model_id');
            
            $table->dropForeign(['make_id']);
            $table->dropColumn('make_id');
        });
    }
};
