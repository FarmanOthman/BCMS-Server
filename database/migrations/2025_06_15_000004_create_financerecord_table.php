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
        Schema::create('financerecord', function (Blueprint $table) {
            if (DB::getDriverName() === 'pgsql') {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            } else {
                $table->uuid('id')->primary();
            }
            $table->text('type');
            $table->text('category');
            $table->decimal('cost', 15, 2); // Default precision and scale
            $table->text('description')->nullable();

            if (DB::getDriverName() === 'pgsql') {
                $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            } else {
                $table->timestamps();
            }

            $table->foreignUuid('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->onDelete('set null');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE financerecord ALTER COLUMN created_at SET DEFAULT CURRENT_TIMESTAMP;');
            DB::statement('ALTER TABLE financerecord ALTER COLUMN updated_at SET DEFAULT CURRENT_TIMESTAMP;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financerecord');
    }
};
