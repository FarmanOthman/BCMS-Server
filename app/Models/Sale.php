<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'sale';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'car_id',
        'buyer_id',
        'sale_price',
        'purchase_cost',
        'profit_loss',
        'sale_date',
        'notes',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */    protected $casts = [
        'id' => 'string',
        'car_id' => 'string',
        'buyer_id' => 'string',
        'sale_price' => 'decimal:2', // Using 2 decimal places for currency values
        'purchase_cost' => 'decimal:2', // Using 2 decimal places for currency values
        'profit_loss' => 'decimal:2',   // Using 2 decimal places for currency values
        'sale_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    /**
     * Get the car associated with the sale.
     */
    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * Get the buyer associated with the sale.
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    /**
     * Get the user who created the sale record.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the sale record.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Consider adding an accessor for profit_loss if it should always be calculated
    // or ensure it's calculated and set before saving if not done by the database.
    // For example, if purchase_cost is derived from the car at the time of sale:
    // protected static function booted()
    // {
    //     static::creating(function ($sale) {
    //         if (empty($sale->purchase_cost) && $sale->car) {
    //             $sale->purchase_cost = $sale->car->base_price + $sale->car->transition_cost; // Or however it's calculated
    //         }
    //         if (isset($sale->sale_price) && isset($sale->purchase_cost)) {
    //             $sale->profit_loss = $sale->sale_price - $sale->purchase_cost;
    //         }
    //     });
    //     static::updating(function ($sale) {
    //         if ($sale->isDirty('sale_price') || $sale->isDirty('purchase_cost')) {
    //             if (isset($sale->sale_price) && isset($sale->purchase_cost)) {
    //                 $sale->profit_loss = $sale->sale_price - $sale->purchase_cost;
    //             }
    //         }
    //     });
    // }
}
