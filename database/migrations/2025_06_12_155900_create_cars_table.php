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
        Schema::create('cars', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('make'); // Original column, will be removed by a later migration
            $table->string('model'); // Original column, will be removed by a later migration
            // Add any other original columns for the cars table here if they existed
            // For example:
            // $table->integer('year')->nullable();
            // $table->string('color')->nullable();
            // $table->string('vin')->unique()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
