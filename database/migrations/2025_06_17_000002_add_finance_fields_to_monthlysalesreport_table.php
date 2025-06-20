<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */    public function up(): void
    {
        Schema::table('monthlysalesreport', function (Blueprint $table) {
            // Only add total_finance_cost if it doesn't exist
            if (!Schema::hasColumn('monthlysalesreport', 'total_finance_cost')) {
                $table->decimal('total_finance_cost', 15, 2)->default(0)->after('finance_cost');
            }
            
            // Don't add finance_cost and net_profit, they already exist in the original migration
        });
    }

    /**
     * Reverse the migrations.
     */    public function down(): void
    {
        Schema::table('monthlysalesreport', function (Blueprint $table) {
            // Only drop total_finance_cost as it's the only one we added in this migration
            if (Schema::hasColumn('monthlysalesreport', 'total_finance_cost')) {
                $table->dropColumn('total_finance_cost');
            }
        });
    }
};
