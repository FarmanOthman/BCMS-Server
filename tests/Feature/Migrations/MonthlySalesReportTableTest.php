<?php

declare(strict_types=1);

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('migrates the monthlysalesreport table correctly', function () {
    expect(Schema::hasTable('monthlysalesreport'))->toBeTrue();

    expect(Schema::hasColumns('monthlysalesreport', [
        'year',
        'month',
        'start_date',
        'end_date',
        'total_sales',
        'total_revenue',
        'total_profit',
        'avg_daily_profit',
        'best_day',
        'best_day_profit',
        'profit_margin',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});
