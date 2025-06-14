<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlySalesReport extends Model
{
    use HasFactory;

    protected $table = 'monthlysalesreport';
    // Eloquent doesn't natively support composite primary keys for find() or findOrFail().
    // We will use where clauses for querying.
    public $incrementing = false;
    protected $primaryKey = null; // No single primary key, effectively composite ['year', 'month']

    protected $fillable = [
        'year',
        'month',
        'start_date',
        'end_date',
        'total_sales',
        'total_revenue',
        'total_profit',
        'avg_daily_profit',
        'best_day',
        'best_day_profit',
        'profit_margin',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'best_day' => 'date:Y-m-d',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'total_revenue' => 'decimal:2',
        'total_profit' => 'decimal:2',
        'avg_daily_profit' => 'decimal:2',
        'best_day_profit' => 'decimal:2',
        'profit_margin' => 'decimal:2',
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
