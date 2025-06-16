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
        Schema::create('yearlysalesreport', function (Blueprint $table) {
            $table->integer('year');
            $table->integer('total_sales')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('total_profit', 15, 2)->default(0);
            $table->decimal('avg_monthly_profit', 15, 2)->default(0);
            $table->integer('best_month')->nullable();
            $table->decimal('best_month_profit', 15, 2)->nullable();
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
            $table->primary('year');

            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE yearlysalesreport ALTER COLUMN created_at SET DEFAULT CURRENT_TIMESTAMP;');
            DB::statement('ALTER TABLE yearlysalesreport ALTER COLUMN updated_at SET DEFAULT CURRENT_TIMESTAMP;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('yearlysalesreport');
    }
};
