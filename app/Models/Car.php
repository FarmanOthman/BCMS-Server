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
        'year',
        'base_price',
        'public_price', // Added public_price
        'sold_price',
        'transition_cost',
        'status',
        'vin',
        'metadata',
        'repair_costs',
        'created_by', // Assuming these are set programmatically
        'updated_by', // Assuming these are set programmatically
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'year' => 'integer',
        'base_price' => 'decimal:2',
        'public_price' => 'decimal:2', // Added public_price cast
        'sold_price' => 'decimal:2',
        'transition_cost' => 'decimal:2',
        'metadata' => 'array', // Changed from json to array for easier handling in Laravel
        'repair_costs' => 'array', // Changed from json to array
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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

    /**
     * Get the user who created the car.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the car.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
