<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Added for DB specific checks

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('make'); // Will be replaced by make_id in a later migration
            $table->string('model'); // Will be replaced by model_id in a later migration
            $table->integer('year');
            $table->string('vin')->unique();
            $table->decimal('cost_price', 10, 2);
            $table->decimal('transition_cost', 10, 2)->default(0)->nullable();
            $table->decimal('total_repair_cost', 10, 2)->default(0)->nullable();
            $table->decimal('selling_price', 10, 2)->nullable();
            $table->decimal('public_price', 10, 2)->nullable(); // Added based on factory
            $table->string('status')->default('available');
            
            if (DB::getDriverName() === 'pgsql') {
                $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->timestamp('sold_at')->nullable();
            } else {
                $table->timestamps();
                $table->timestamp('sold_at')->nullable();
            }

            $table->foreignUuid('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('sold_by')->nullable()->constrained('users')->onDelete('set null');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE cars ALTER COLUMN created_at SET DEFAULT CURRENT_TIMESTAMP;');
            DB::statement('ALTER TABLE cars ALTER COLUMN updated_at SET DEFAULT CURRENT_TIMESTAMP;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
