<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailySalesReport extends Model
{
    use HasFactory;

    protected $table = 'dailysalesreport'; // Explicitly define table name
    protected $primaryKey = 'report_date'; // Set the primary key
    public $incrementing = false;         // Primary key is not auto-incrementing
    protected $keyType = 'date';          // Primary key type

    protected $fillable = [
        'report_date',
        'total_sales',
        'total_revenue',
        'total_profit',
        'avg_profit_per_sale',
        'most_profitable_car_id',
        'highest_single_profit',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'report_date' => 'date:Y-m-d', // Ensure date is cast correctly
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'total_revenue' => 'decimal:2',
        'total_profit' => 'decimal:2',
        'avg_profit_per_sale' => 'decimal:2',
        'highest_single_profit' => 'decimal:2',
    ];

    // Relationship to User (optional, if you want to load creator/updater)
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Relationship to Car (for most_profitable_car_id)
    public function mostProfitableCar()
    {
        return $this->belongsTo(Car::class, 'most_profitable_car_id');
    }
}
