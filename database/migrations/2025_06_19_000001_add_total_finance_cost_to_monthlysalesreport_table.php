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
        Schema::table('monthlysalesreport', function (Blueprint $table) {
            if (!Schema::hasColumn('monthlysalesreport', 'total_finance_cost')) {
                $table->decimal('total_finance_cost', 15, 2)->default(0)->after('finance_cost');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthlysalesreport', function (Blueprint $table) {
            if (Schema::hasColumn('monthlysalesreport', 'total_finance_cost')) {
                $table->dropColumn('total_finance_cost');
            }
        });
    }
};
