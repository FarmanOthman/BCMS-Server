<?php

declare(strict_types=1);

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('migrates the buyer table correctly', function () {
    expect(Schema::hasTable('buyer'))->toBeTrue();

    expect(Schema::hasColumns('buyer', [
        'id',
        'name',
        'phone',
        'address',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});
