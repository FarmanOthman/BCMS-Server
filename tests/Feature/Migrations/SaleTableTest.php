<?php

declare(strict_types=1);

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('migrates the sale table correctly', function () {
    expect(Schema::hasTable('sale'))->toBeTrue();

    expect(Schema::hasColumns('sale', [
        'id',
        'car_id',
        'buyer_id',
        'sale_price',
        'purchase_cost',
        'profit_loss',
        'sale_date',
        'notes',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});
