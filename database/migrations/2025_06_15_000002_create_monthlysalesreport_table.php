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
        Schema::create('monthlysalesreport', function (Blueprint $table) {
            $table->integer('year');
            $table->integer('month');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('total_sales')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('total_profit', 15, 2)->default(0);
            $table->decimal('avg_daily_profit', 15, 2)->default(0);
            $table->date('best_day')->nullable();
            $table->decimal('best_day_profit', 15, 2)->nullable();
            $table->decimal('profit_margin', 5, 2)->nullable(); // Assuming percentage, e.g., 999.99%

            // Timestamps
            if (DB::getDriverName() === 'pgsql') {
                $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            } else {
                $table->timestamps();
            }

            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();

            // Primary key
            $table->primary(['year', 'month']);

            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Constraints are handled after table creation for broader compatibility
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE monthlysalesreport ADD CONSTRAINT monthlysalesreport_month_check CHECK (month >= 1 AND month <= 12);');
            DB::statement('ALTER TABLE monthlysalesreport ALTER COLUMN created_at SET DEFAULT CURRENT_TIMESTAMP;');
            DB::statement('ALTER TABLE monthlysalesreport ALTER COLUMN updated_at SET DEFAULT CURRENT_TIMESTAMP;');
        } else {
            // For SQLite, CHECK constraints can be added during table creation or via ALTER TABLE, but Laravel's schema builder might not directly support named constraints this way.
            // If this specific constraint is critical for SQLite tests and not handled by model validation, a raw statement might be needed.
            // However, for now, we'll rely on PostgreSQL for this constraint and application-level validation elsewhere.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthlysalesreport');
    }
};
