<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlySalesReport extends Model
{    use HasFactory;

    protected $table = 'monthlysalesreport';
    // We'll use composite key of year and month
    public $incrementing = false;
    protected $primaryKey = 'year'; // Use year as primary key component
    
    protected $fillable = [
        'year',
        'month',
        'start_date',
        'end_date',        'total_sales',
        'total_revenue',
        'total_profit',
        'avg_daily_profit',
        'best_day',
        'best_day_profit',
        'profit_margin',
        'finance_cost',
        'total_finance_cost',
        'net_profit',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'best_day' => 'date:Y-m-d',        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'total_revenue' => 'decimal:2',
        'total_profit' => 'decimal:2',
        'avg_daily_profit' => 'decimal:2',
        'best_day_profit' => 'decimal:2',
        'profit_margin' => 'decimal:2',
        'finance_cost' => 'decimal:2',
        'total_finance_cost' => 'decimal:2',
        'net_profit' => 'decimal:2',
    ];

    // Define a scope for querying by year and month easily
    public function scopeForYearMonth($query, $year, $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
