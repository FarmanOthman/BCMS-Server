<?php

declare(strict_types=1);

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('migrates the yearlysalesreport table correctly', function () {
    expect(Schema::hasTable('yearlysalesreport'))->toBeTrue();

    expect(Schema::hasColumns('yearlysalesreport', [
        'year',
        'total_sales',
        'total_revenue',
        'total_profit',
        'avg_monthly_profit',
        'best_month',
        'best_month_profit',
        'profit_margin',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});
