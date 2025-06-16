<?php

declare(strict_types=1);

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('migrates the models table correctly', function () {
    expect(Schema::hasTable('models'))->toBeTrue();

    expect(Schema::hasColumns('models', [
        'id',
        'make_id',
        'name',
        'created_at',
        'updated_at',
    ]))->toBeTrue();

    // Optionally, check foreign key (this is a bit more involved with SQLite in tests)
    // You might need a helper or to inspect the SQLite master table for foreign key constraints
});
