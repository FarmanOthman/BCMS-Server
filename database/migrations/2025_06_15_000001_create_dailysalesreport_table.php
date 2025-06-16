<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dailysalesreport', function (Blueprint $table) {
            $table->date('report_date')->primary();
            $table->integer('total_sales')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('total_profit', 15, 2)->default(0);
            $table->decimal('avg_profit_per_sale', 15, 2)->default(0);
            $table->foreignUuid('most_profitable_car_id')->nullable()->constrained('cars')->onDelete('set null');
            $table->decimal('highest_single_profit', 15, 2)->nullable();

            if (DB::getDriverName() === 'pgsql') {
                $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            } else {
                $table->timestamps(); // Standard Laravel way for SQLite
            }

            $table->foreignUuid('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->onDelete('set null');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE dailysalesreport ALTER COLUMN created_at SET DEFAULT CURRENT_TIMESTAMP;');
            DB::statement('ALTER TABLE dailysalesreport ALTER COLUMN updated_at SET DEFAULT CURRENT_TIMESTAMP;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dailysalesreport');
    }
};
