<?php

declare(strict_types=1);

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('migrates the financerecord table correctly', function () {
    expect(Schema::hasTable('financerecord'))->toBeTrue();

    expect(Schema::hasColumns('financerecord', [
        'id',
        'type',
        'category',
        'cost',
        'description',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
    ]))->toBeTrue();

    // Optional: Check column types if necessary
    expect(Schema::getColumnType('financerecord', 'id'))->toBeString(); // uuid is string
    expect(Schema::getColumnType('financerecord', 'cost'))->toBe(config('database.default') === 'sqlite' ? 'numeric' : 'numeric'); // Both SQLite and PostgreSQL use numeric for decimal
    expect(Schema::getColumnType('financerecord', 'created_at'))->toBe(config('database.default') === 'sqlite' ? 'datetime' : 'timestamp');
});
