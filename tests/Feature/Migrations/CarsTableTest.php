<?php

declare(strict_types=1);

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('migrates the cars table correctly', function () {
    expect(Schema::hasTable('cars'))->toBeTrue();

    // Columns confirmed from migrations:
    // 2025_06_12_155900_create_cars_table.php: id, timestamps (created_at, updated_at)
    // 2025_06_12_160347_modify_cars_table_for_makes_and_models.php: make_id, model_id (drops make, model text)
    // 2025_06_13_170000_add_public_price_to_cars_table.php: public_price
    // Other columns like year, vin, cost_price, selling_price, status, created_by, updated_by, sold_at, sold_by
    // must be present from the initial create_cars_table.php or another migration not shown in detail.
    // Assuming they are part of the full schema based on their use in tests/models.

    expect(Schema::hasColumns('cars', [
        'id',             // uuid, primary
        'make_id',        // uuid, foreign key to makes
        'model_id',       // uuid, foreign key to models
        'public_price',   // decimal(10,2)
        'created_at',     // timestamp
        'updated_at',     // timestamp
        // The following columns are assumed to be part of the schema based on model usage and previous tests
        // If these are not in a migration, the test will (and should) fail.
        'year',           // integer - Assuming from Car model/factory usage
        'vin',            // string, unique - Assuming from Car model/factory usage
        'cost_price',     // decimal(10, 2) - Assuming from Car model/factory usage
        'selling_price',  // decimal(10, 2), nullable - Assuming from Car model/factory usage
        'status',         // string, default 'available' - Assuming from Car model/factory usage
        'created_by',     // uuid, nullable, foreign key to users - Assuming from Car model/factory usage
        'updated_by',     // uuid, nullable, foreign key to users - Assuming from Car model/factory usage
        'sold_at',        // timestamp, nullable - Assuming from Car model/factory usage
        'sold_by',        // uuid, nullable, foreign key to users - Assuming from Car model/factory usage
    ]))->toBeTrue();
});
