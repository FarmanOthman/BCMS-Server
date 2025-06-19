<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YearlySalesReport extends Model
{
    use HasFactory;

    protected $table = 'yearlysalesreport';
    protected $primaryKey = 'year';
    public $incrementing = false;
    protected $keyType = 'integer';    protected $fillable = [
        'year',
        'total_sales',
        'total_revenue',
        'total_profit',
        'avg_monthly_profit',
        'best_month',
        'best_month_profit',
        'profit_margin',
        'created_by',
        'updated_by',
    ];    protected $casts = [
        'year' => 'integer',
        'best_month' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'total_revenue' => 'decimal:2',
        'total_profit' => 'decimal:2',
        'avg_monthly_profit' => 'decimal:2',
        'best_month_profit' => 'decimal:2',
        'profit_margin' => 'decimal:2',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
