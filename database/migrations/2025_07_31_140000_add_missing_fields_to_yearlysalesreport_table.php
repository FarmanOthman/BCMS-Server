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
        Schema::table('yearlysalesreport', function (Blueprint $table) {
            // Add missing fields that are used in the GenerateYearlySalesReport command
            if (!Schema::hasColumn('yearlysalesreport', 'yoy_growth')) {
                $table->decimal('yoy_growth', 8, 2)->default(0)->after('profit_margin'); // Year-over-year growth percentage
            }
            if (!Schema::hasColumn('yearlysalesreport', 'total_finance_cost')) {
                $table->decimal('total_finance_cost', 15, 2)->default(0)->after('yoy_growth'); // Total finance costs for the year
            }
            if (!Schema::hasColumn('yearlysalesreport', 'total_net_profit')) {
                $table->decimal('total_net_profit', 15, 2)->default(0)->after('total_finance_cost'); // Net profit after finance costs
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('yearlysalesreport', function (Blueprint $table) {
            if (Schema::hasColumn('yearlysalesreport', 'yoy_growth')) {
                $table->dropColumn('yoy_growth');
            }
            if (Schema::hasColumn('yearlysalesreport', 'total_finance_cost')) {
                $table->dropColumn('total_finance_cost');
            }
            if (Schema::hasColumn('yearlysalesreport', 'total_net_profit')) {
                $table->dropColumn('total_net_profit');
            }
        });
    }
}; 