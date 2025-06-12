<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Car extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'make_id',
        'model_id',
        // Add other car attributes here that are fillable
        // e.g., 'year', 'color', 'vin', etc.
    ];

    /**
     * Get the make of the car.
     */
    public function make(): BelongsTo
    {
        return $this->belongsTo(Make::class);
    }

    /**
     * Get the model of the car.
     */
    public function model(): BelongsTo
    {
        return $this->belongsTo(Model::class);
    }
}
