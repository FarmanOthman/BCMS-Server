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
        if (!Schema::hasTable('sale')) {
            Schema::create('sale', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->foreignUuid('car_id')->constrained('cars')->onDelete('cascade');
                $table->foreignUuid('buyer_id')->constrained('buyer')->onDelete('cascade'); // Assuming 'buyer' is the table name for buyers
                
                $table->decimal('sale_price', 10, 0); // CHECK (sale_price >= 0) - Handled by DB or validation
                $table->decimal('purchase_cost', 10, 0); // CHECK (purchase_cost >= 0) - Handled by DB or validation
                $table->decimal('profit_loss', 10, 0);
                
                $table->date('sale_date'); // CHECK (sale_date <= CURRENT_DATE) - Handled by DB or validation
                $table->text('notes')->nullable(); // Reverted to nullable
                
                $table->timestampTz('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->timestampTz('updated_at')->default(DB::raw('CURRENT_TIMESTAMP'));
                
                $table->uuid('created_by')->nullable();
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                
                $table->uuid('updated_by')->nullable();
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

                // Database specific CHECK constraints (e.g., sale_price >= 0) are best managed directly in Supabase
                // or added via DB::statement if necessary for the specific database driver.
                // Laravel's schema builder has limited direct support for complex CHECK constraints.
            });
        } else {
            // Log::info('Table "sale" already exists. Skipping creation.');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale');
    }
};
