<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Buyer extends Model
{
    use HasFactory, HasUuids; // Using HasUuids for automatic UUID generation if not provided

    protected $table = 'buyer'; // Explicitly define table name

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id', // UUID, will be handled by HasUuids or database default
        'name',
        'phone',
        'address',
        'car_ids', // Array of car IDs that this buyer is interested in or has purchased
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'address' => 'string', // Ensure address is string, even if null
        'car_ids' => 'array', // Cast car_ids as array
    ];

    /**
     * Get the user who created the buyer.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the buyer.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
