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
            if (!Schema::hasColumn('cars', 'public_price')) {
                // The CHECK constraint (public_price > 0) should ideally be managed directly in Supabase
                // or added via DB::statement if absolutely necessary and compatible.
                // For simplicity and because Supabase handles it, we just define the column type.
                $table->decimal('public_price', 10, 2)->nullable(); // Set precision and scale as appropriate, e.g., 10,2 for currency. Adjust if needed.
                                                              // Making it nullable here initially, validation will enforce it.
                                                              // If you want a DB-level NOT NULL, ensure it has a default or is handled before migration.
            }
        });

        // If you want to enforce NOT NULL and the CHECK constraint via migration (and it's not already on the DB)
        // This is more complex as it depends on existing data and DB specifics.
        // Example for PostgreSQL to add CHECK if not exists (more involved to check if constraint exists by name):
        // if (Schema::hasColumn('cars', 'public_price')) {
        //     DB::statement('ALTER TABLE cars ADD CONSTRAINT cars_public_price_check CHECK (public_price > 0);');
        //     DB::statement('ALTER TABLE cars ALTER COLUMN public_price SET NOT NULL;'); // Only if all existing rows can satisfy this
        // }
        // Given you added it in Supabase, the constraint is likely already there.
        // The main goal here is to make Laravel aware of the column type.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            if (Schema::hasColumn('cars', 'public_price')) {
                // To remove a check constraint, you need its name, which can be DB specific.
                // DB::statement('ALTER TABLE cars DROP CONSTRAINT IF EXISTS cars_public_price_check;'); // Example for PostgreSQL
                $table->dropColumn('public_price');
            }
        });
    }
};
