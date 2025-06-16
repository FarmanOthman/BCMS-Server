<?php

declare(strict_types=1);

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('migrates the makes table correctly', function () {
    expect(Schema::hasTable('makes'))->toBeTrue();

    expect(Schema::hasColumns('makes', [
        'id',
        'name',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});
