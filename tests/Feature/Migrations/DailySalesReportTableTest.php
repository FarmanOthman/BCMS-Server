<?php

declare(strict_types=1);

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('migrates the dailysalesreport table correctly', function () {
    expect(Schema::hasTable('dailysalesreport'))->toBeTrue();

    expect(Schema::hasColumns('dailysalesreport', [
        'report_date',
        'total_sales',
        'total_revenue',
        'total_profit',
        'avg_profit_per_sale',
        'most_profitable_car_id',
        'highest_single_profit',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});
